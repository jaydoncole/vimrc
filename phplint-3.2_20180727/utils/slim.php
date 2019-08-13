<?php

/**
 * Program that stripes or squeezes comments and spaces from a PHP source text.
 * Start the program with the --help option for more details.
 * @author Umberto Salsi <salsi@icosaedro.it>
 * @version $Date: 2018/06/06 10:00:18 $
 * @package slim
 */

require_once __DIR__ . "/../stdlib/all.php";
use it\icosaedro\utils\Strings;

/*. require_module 'tokenizer'; require_module 'pcre'; .*/

/**
 * @access private
 */
class Slim {

	private $strip_docblock = FALSE;
	private $strip_multiline_comment = FALSE;
	private $strip_inline_comment = FALSE;
	private $strip_whitespace = FALSE;

	private $squeeze_docblock = FALSE;
	private $squeeze_multiline_comment = FALSE;
	private $squeeze_inline_comment = FALSE;
	private $squeeze_whitespace = FALSE;
	
	private $backup = TRUE;
	
	/**
	 * Processed file.
	 * @var resource
	 */
	private $out;
	
	/**
	 * A FIFO of 3 consecutive symbols must be retained in order to determine
	 * if the middle space is or is not mandatory.
	 * @access private
	 */
	const
		IS_UNDEF = 0, // FIFO entry unused
		IS_HTML = 1, // FIFO entry contains in-line HTML (or whatever)
		IS_SPACE = 2, // FIFO entry contains a space or comment, or is empty
		IS_KEY = 3; // FIFO entry contains a token to keep
	
	// First entry of the FIFO (oldest symbol):
	private $code0 = self::IS_UNDEF;
	private $text0 = '';
	// Second entry of the FIFO (previous symbol):
	private $code1 = self::IS_UNDEF;
	private $text1 = '';
	
	/**
	 * PHP allowed bytes in identifier.
	 * @access private
	 */
	const ID_CHARSET = "[_0-9a-zA-Z\x80-\xff]";
	
	/**
	 * Tells if the string ends with a valid ID char.
	 * @param string $text
	 * @return boolean
	 */
	private static function endNeedsSeparator($text)
	{
		return preg_match("/" . self::ID_CHARSET . "\$/sD", $text) == 1;
	}
	
	/**
	 * Tells if the string begins with a valid ID char.
	 * @param string $text
	 * @return boolean
	 */
	private static function startNeedsSeparator($text)
	{
		return preg_match("/^" . self::ID_CHARSET . "/sD", $text) == 1;
	}
	
	
	/**
	 * Removes spaces preserving line-end markers.
	 * @param string $s
	 * @return string
	 */
	private static function squeeze($s)
	{
		return preg_replace("/[^\r\n]/", "", $s);
	}
	
	
	/**
	 * Outputs the symbol, accounting for spaces that might be mandatory between
	 * PHP tokens, for example "new MyClass". This requires to store 3 symbols:
	 * the current one and the two preceeding symbols. If the middle symbol is
	 * a white space to be removed, and the symbol to its left terminates with
	 * an ID char, and the symbol to its right begins with an ID char, then a
	 * space is mandatory.
	 * @param int $code Symbol type, one of the IS_* constants.
	 * @param string $text Text of the symbol.
	 * @throws ErrorException
	 * @return void
	 */
	private function write($code, $text)
	{
		if( $this->code1 > self::IS_UNDEF ){
			// 3 symbols available, 2 in the stack and the last one in the arguments.
			if( $this->code1 == self::IS_SPACE and $code == self::IS_SPACE ){
				// joins spaces or space-like symbols together:
				$this->text1 .= $text;
				return;
			}
			
			// pop-out first symbol:
			fwrite($this->out, $this->text0);
			
			// examine middle and last symbols:
			if( $this->code1 == self::IS_SPACE ){
				// middle symbol is space or space-like:
				if( strlen($this->text1) == 0 ){
					if( $this->code0 == self::IS_KEY and self::endNeedsSeparator($this->text0)
					and $code == self::IS_KEY and self::startNeedsSeparator($text) ){
						// mandatory space required:
						fwrite($this->out, " ");
					}
				} else {
					fwrite($this->out, $this->text1);
				}
				// only the current symbol remains in the stack:
				$this->code0 = $code;
				$this->text0 = $text;
				$this->code1 = self::IS_UNDEF;
			} else {
				// shift symbols by removing the first we already sent to output,
				// so that only the current one and the previous one remain:
				$this->code0 = $this->code1;
				$this->text0 = $this->text1;
				$this->code1 = $code;
				$this->text1 = $text;
			}
		} else if( $this->code0 > self::IS_UNDEF ){
			if( $this->code0 == self::IS_SPACE and $code == self::IS_SPACE ){
				$this->text0 .= $text;
				return;
			}
			$this->code1 = $code;
			$this->text1 = $text;
		} else {
			$this->code0 = $code;
			$this->text0 = $text;
		}
	}
	
	
	/**
	 * Sends symbols remaining in the stack.
	 * @throws ErrorException
	 * @return void
	 */
	private function flush()
	{
		if( $this->code0 != self::IS_UNDEF ){
			fwrite($this->out, $this->text0);
			if( $this->code1 != self::IS_UNDEF )
				// FIXME: check if space
				fwrite($this->out, $this->text1);
		}
	}
	
	
	/**
	 * Renames the file FILE in FILE.bak; if this latter file already exists,
	 * tries with FILE.1.bak up to FILE.10.bak before giving up.
	 * @param string $f
	 * @throws ErrorException
	 */
	private static function renameBackup($f)
	{
		$g = "$f.bak";
		$i = 0;
		while( file_exists($g) ){
			$i++;
			if( $i > 10 )
				throw new ErrorException("too many backup files of $f");
			$g = "$f.$i.bak";
		}
		rename($f, $g);
	}
	
	
	/**
	 * @param string $f
	 * @throws ErrorException
	 */
	private function slimFile($f)
	{
		$this->code0 = self::IS_UNDEF;
		$this->code1 = self::IS_UNDEF;
		$tokens = token_get_all(file_get_contents($f));
		if( $this->backup )
			self::renameBackup($f);
		$this->out = fopen($f, "wb");
		foreach($tokens as $token){
			$n = count($token);
			
			// Print symbol as is:
//			if( $n == 1 ){
//				echo "[single char: ", Strings::toLiteral((string)$token[0]), "]\n";
//			} else if( $n == 3 ){
//				echo "[", token_name((int)$token[0]), ": ", Strings::toLiteral((string)$token[1]), "]\n";
//			}
			
			if( $n == 1 ){
				$this->write(self::IS_KEY, (string) $token);
				
			} else if( $n == 3 ){
				$a = cast("mixed[int]", $token);
				$code = (int) $a[0];
				$text = (string) $a[1];
				
				if( $code == T_DOC_COMMENT ){
					if( $this->squeeze_docblock )
						$res = self::squeeze($text);
					else if( $this->strip_docblock )
						$res = "";
					else
						$res = $text;
					$this->write(self::IS_SPACE, $res);
					
				} else if( $code == T_COMMENT ){
					if( $this->squeeze_multiline_comment and Strings::startsWith($text, "/*")
						or $this->squeeze_inline_comment and Strings::startsWith($text, "//")
						or $this->squeeze_inline_comment and Strings::startsWith($text, "#")
					){
						$res = self::squeeze($text);
					} else if( $this->strip_multiline_comment and Strings::startsWith($text, "/*")
						or $this->strip_inline_comment and Strings::startsWith($text, "//")
						or $this->strip_inline_comment and Strings::startsWith($text, "#")
					){
						$res = "";
					} else {
						$res = $text;
					}
					$this->write(self::IS_SPACE, $res);
					
				} else if( $code == T_WHITESPACE ){
					if( $this->squeeze_whitespace ){
						$res = self::squeeze($text);
					} else if( $this->strip_whitespace ){
						$res = "";
					} else {
						$res = $text;
					}
					$this->write(self::IS_SPACE, $res);
				
				} else if( $code == T_INLINE_HTML ){
					$this->write(self::IS_HTML, $text);
					
				} else if( $code == T_END_HEREDOC ){
					$this->write(self::IS_KEY, "$text\n");
					
				} else {
					$this->write(self::IS_KEY, $text);
					
				}
			} else {
				throw new RuntimeException();
			}
		}
		$this->flush();
		fclose($this->out);
	}
	
	
	private static function help()
	{
		echo <<<EOT
strip.php - Removes spaces and comments from a PHP source file. Several options
allow to control what exactly is removed. By striping, the specified element is
removed, making the source shorter, possibly reducing all to a single long line
of text. By squeezing, on the contrary, the total number of lines is preserved
and only their content is removed, so, for example, stack trace still generates
the same line numbers as the original file. Line ending convention, either DOS
or Unix, is not changed. White space characters considered here are " \t\r\n".

SYNTAX:

	php strip.php [OPTION or file1.php]

OPTIONS:

--strip-docblock
	Removes DocBlocks /** */.

--strip-multiline-comment
	Removes multiline comments /* */.

--strip-inline-comment
	Removes in-line comments // and #.

--strip-whitespace
	Removes white spaces, including line ending; a single space is left only
	where mandatory, as in "new MyClass".

--strip-all
	Removes DocBlocks, multiline comments, in-line comments and any not
	mandatory whitespace. Typically this leaves all the source code on a single,
	very long line of text.

--squeeze-docblock
	Removes the content of any DocBlock leaving empty lines in place.

--squeeze-multiline-comment
	Removes the content of any multi-line comment leaving empty lines in place.

--squeeze-inline-comment
	Removes the content of any in-line comment leaving empty lines in place.

--squeeze-whitespace
	Removes redundant white spaces possibly leaving empty lines in place.

--squeeze-all
	Removes the content of any DocBlock, multi-line comment, in-line comment
	and white space leaving empty lines in place.

--backup
	Generates a backup file file.php.bak before processing file.php. This is the
	default behavior.

--no-backup
	Does not generate a backup file.

--help or -h
	Displays this help text.

EXAMPLE
		
Removing any DocBlock, comment and redundant white space preserving line numbers:

	php slim.php --squeeze-all MySource.php

This overwrites the MySource.php file with the processed text and saves a backup
file in MySource.php.bak.
EOT;
	}
	

	/**
	 * @param string[int] $argv
	 * @return void
	 * @throws ErrorException
	 */
	public function __construct($argv)
	{
		for($i = 1; $i < count($argv); $i++){
			$a = $argv[$i];
			switch($a){
				
				case "-h":  case "--help":  self::help();  break;
				case "--strip-docblock":  $this->strip_docblock = TRUE;  break;
				case "--strip-multiline-comment":  $this->strip_multiline_comment = TRUE;  break;
				case "--strip-inline-comment":  $this->strip_inline_comment = TRUE;  break;
				case "--strip-whitespace":  $this->strip_whitespace = TRUE;  break;
				case "--strip-all":
					$this->strip_docblock =
					$this->strip_multiline_comment =
					$this->strip_inline_comment =
					$this->strip_whitespace = TRUE;
					break;
				case "--squeeze-docblock":  $this->squeeze_docblock = TRUE;  break;
				case "--squeeze-multiline-comment":  $this->squeeze_multiline_comment = TRUE;  break;
				case "--squeeze-inline-comment":  $this->squeeze_inline_comment = TRUE;  break;
				case "--squeeze-whitespace":  $this->squeeze_whitespace = TRUE;  break;
				case "--squeeze-all":
					$this->squeeze_docblock =
					$this->squeeze_multiline_comment =
					$this->squeeze_inline_comment =
					$this->squeeze_whitespace = TRUE;
					break;
				case "--backup":  $this->backup = TRUE;  break;
				case "--no-backup":  $this->backup = FALSE;  break;
				default:  $this->slimFile($a);
			}
		}
	}

}


new Slim($argv);
