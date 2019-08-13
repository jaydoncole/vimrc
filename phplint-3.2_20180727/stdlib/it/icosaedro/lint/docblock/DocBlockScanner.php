<?php

namespace it\icosaedro\lint\docblock;
require_once __DIR__ . "/../../../../autoload.php";
use it\icosaedro\utils\Strings;
use it\icosaedro\utils\StringBuffer;
use it\icosaedro\lint\Logger;
use it\icosaedro\lint\Where;
use it\icosaedro\lint\ErrorsSet;
use it\icosaedro\lint\ClassResolver;
use it\icosaedro\regex\Pattern;
use it\icosaedro\lint\types\Type;
use it\icosaedro\lint\types\UnknownType;
use it\icosaedro\lint\types\ClassType;
use it\icosaedro\lint\types\TypeDescriptor;
use it\icosaedro\lint\docblock\DocBlockParameter;

/**
 * Scanner for PHP multi-line comments carrying DocBlocks.
 * @author Umberto Salsi <salsi@icosaedro.it>
 * @version $Date: 2018/03/19 10:17:23 $
 */
final class DocBlockScanner {
	
	/**
	 * @var Logger
	 */
	private $logger;
	
	/**
	 * Class name resolver.
	 * @var ClassResolver
	 */
	private $resolver;
	
	/**
	 * Location of this DocBlock.
	 * @var Where
	 */
	private $where;
	
	/**
	 * Text of the PHP multi-line comment.
	 * @var string 
	 */
	private $comment;
	
	/**
	 * Index at $t of the beginning of the next line to scan.
	 * @var int 
	 */
	private $comment_pos = 0;
	
	/**
	 * Current line scanned, with chracters "\r" and "\n" removed.
	 * Evaluates to NULL at the end.
	 * @var string 
	 */
	private $line;
	
	/**
	 * Index at $line of the next char to scan.
	 * @var int 
	 */
	private $line_pos = 0;
			
	/**
	 * Line no. of the current line in the original source file.
	 * @var int
	 */
	private $line_no = 0;
	
	/**
	 * True if the line just retrieved by <code>nextLine()</code> method
	 * contains a line tag, that is a leading "@" as the first non-space
	 * character.
	 * @var boolean 
	 */
	private $is_tag = FALSE;
	
	/**
	 * DocBlock being built.
	 * @var DocBlock 
	 */
	private $db;
	
	
	/**
	 * Moves the position of the scanner to the beginning of the next line.
	 * Check the <code>$line</code> for the actual existence of a next line:
	 * that property is set to NULL at the end of the DocBlock.
	 * Sets public properties: $line, $is_tag.
	 * @return void 
	 */
	private function nextLine(){
		if( $this->comment_pos >= strlen($this->comment) ){
			$this->line = NULL;
			return;
		}
		
		// Get next line:
		$nl = strpos($this->comment, "\n", $this->comment_pos);
		if( $nl === FALSE ){
			$line = substr($this->comment, $this->comment_pos);
			$this->comment_pos = strlen($this->comment);
		} else {
			$line = substr($this->comment, $this->comment_pos, $nl - $this->comment_pos);
			$this->comment_pos = $nl + 1;
			$this->line_no++;
		}
		
		// Remove leading "*" if present:
		if( Strings::startsWith(trim($line), "*") ){
			$star = strpos($line, "*");
			$line = Strings::substring($line, $star+1, strlen($line));
			// also remove next space, commonly added for readability,
			// but preserve further leading spaces for indentation:
			if( Strings::startsWith($line, " ") )
				$line = Strings::substring($line, 1, strlen($line));
		}
		
		// If line tag, remove leading spaces for simpler detection:
		$this->is_tag = FALSE;
		if( Strings::startsWith(trim($line), "@") ){
			$at = strpos($line, "@");
			$line = Strings::substring($line, $at, strlen($line));
			$this->is_tag = TRUE;
		}
		
		$this->line = $line;
		$this->line_pos = 0;
	}
	
	
	/**
	 *
	 * @param string $c
	 * @return boolean 
	 */
	private static function isSpace($c){
		return $c === " " || $c === "\t";
	}
	
	
	/**
	 * Retrieves the next word from the current line. A word is any sequence
	 * of non-space characters.
	 * @return string Next word, or NULL if no more words in current line.
	 */
	private function getWord(){
		if( $this->line === NULL || $this->line_pos >= strlen($this->line) ){
			return NULL;
		}
		$this->is_tag = FALSE;
		$line = $this->line;
		$line_pos = $this->line_pos;
		
		// Skip spaces:
		while($line_pos < strlen($line) && self::isSpace($line[$line_pos]) )
			$line_pos++;
		if( $line_pos >= strlen($line) ){
			$this->line_pos = $line_pos;
			return NULL;
		}
		
		// Detect end of the word:
		$start = $line_pos;
		$line_pos++;
		while($line_pos < strlen($line) && ! self::isSpace($line[$line_pos]))
			$line_pos++;
		$this->line_pos = $line_pos;
		return substr($line, $start, $line_pos - $start);
	}
	
	
	/**
	 * Retrieves text. The text is any text starting from the
	 * current position in the current line and continuing up to the next
	 * line tag "@" or the end of the DocBlock.
	 * @return string Next text, or NULL if end of DocBlock.
	 */
	private function getText(){
		if( $this->line === NULL )
			return NULL;
		$this->is_tag = FALSE;
		$b = new StringBuffer();
		if( $this->line_pos < strlen($this->line) )
			$b->append(substr($this->line, $this->line_pos));
		$this->nextLine();
		while( $this->line !== NULL && ! $this->is_tag ){
			$b->append("\n");
			$b->append($this->line);
			$this->nextLine();
		}
//		return trim($b->__toString());
		return $b->__toString();
	}
	
	
	/**
	 * Logs an error found in the HTML formatting.
	 * @param string $msg Description of the error.
	 * @param string $t Text under HTML test.
	 * @param int $i Offset of the error in the text.
	 * @return void
	 */
	private function htmlError($msg, $t, $i){
		// Determine line number of the line involved:
		$line_no = $this->line_no - substr_count($t, "\n", $i);
		// Determine beginning of the involved line:
		$line_start = strrpos(substr($t, 0, $i), "\n");
		if( $line_start === FALSE )
			$line_start = 0;
		else
			$line_start++;
		// Determine ending of the involved line:
		$line_end = strpos($t, "\n", $i);
		if( $line_end === FALSE )
			$line_end = strlen($t);
		// Determine the involved line:
		$line = Strings::substring($t, $line_start, $line_end);
		// Determine line column position:
		$col = $i - $line_start
			// Add 7 for every HT:
			// FIXME: we should use Logger::$tab_size - 1
			+ 7 * substr_count(substr($line, 0, $i-$line_start), "\t");
		// Displays the error:
		$where = new Where($this->where->getFile(), $line_no, $col, $line);
		$this->logger->error($where, $msg);
	}
	
	/**
	 * Associative array of the HTML tags we recognize. The key is the tag,
	 * the value is ignored by this program.
	 */
	private static $known_tags = array(
		"h1" => 0,
		"h2" => 0,
		"h3" => 0,
		"h4" => 0,
		"h5" => 0,
		"h6" => 0,
		"u" => 0,
		"b" => 0,
		"pre" => 0,
		"i" => 0,
		"code" => 0,
		"sub" => 0,
		"sup" => 0,
		"tt" => 0,
		"kbd" => 0,
		"blockquote" => 0,
		"center" => 0,
		"pre" => 0,
		"br" => 0, // does not check </br>
		//"p" => 0, // <p> and </p> skipped and their proper nesting ignored
		"ol" => 0,
		"ul" => 0,
		"li" => 0,
		"table" => 0,
		"tr" => 0,
		"th" => 0,
		"td" => 0);
	
	/**
	 * Matches HTML text without tags.
	 * @var Pattern 
	 */
	private static $html_text_pattern = NULL;
	
	/**
	 * Check validity of HTML text. Displays errors found.
	 * @param string $t Text to be checked for HTML validity.
	 * @param int $i Index first char where to start validation.
	 * @return int Index past the last char successfully parsed, or -1 if an
	 * error has been already found and reported. Then, the text passed is
	 * valid HTML if the returned value is non-negative, any other value being
	 * for internal use only of this function.
	 */
	private function checkHtml($t, $i){
		
		if( self::$html_text_pattern === NULL ){
			$amp = "&(amp|lt|le|gt|ge|nbsp|copy|#{0-9}+|#{xX}{0-9a-fA-F}+);";
			$text = "($amp|{!<>&})";
			self::$html_text_pattern = new Pattern($text."+");
		}
		
		/*
		 * Implementation notes.
		 * The text is a sequence of 3 types of symbols: text (T), open tags
		 * (O) and close tags (C). Here we simply check the proper nesting of
		 * the tags. This function scans TO, calls itself to skip up to the
		 * C and then the loop continues with next TO, recursive call, C and
		 * so on. If an error is found, returns -1 so to inform the recursive
		 * pending calls that must leave.
		 */
		while( 0 <= $i && $i < strlen($t) ){
			
			if( self::$html_text_pattern->match($t, $i) ){
				$i = self::$html_text_pattern->end();
				
			} else if( $t[$i] === "<" ){
				$j = strpos($t, ">", $i+1);
				if( $j === FALSE || $j == $i+1 ){
					$this->htmlError("unclosed or empty tag", $t, $i);
					return -1;
				}
				// Since $i < $j strictly, substring() cannot return FALSE
				// for empty range; moreover, $tag results not empty.
				$tag = substr($t, $i+1, $j - $i - 1);
				if( $tag === "p" || $tag === "/p" || $tag === "br" ){
					$i = $j + 1;
					continue;
				}
				if( $tag[0] === "/" )
					// Close tag.
					return $i;
				// Open tag.
				if( ! array_key_exists($tag, self::$known_tags) ){
					$this->htmlError("unknown opening tag <$tag>", $t, $i);
					return -1;
				}
				$i = $this->checkHtml($t, $j+1);
				if( $i < 0 )
					return -1;
				$j = $i + strlen($tag) + 3; // $i + len of "</$tag>"
				if( $j > strlen($t) || substr($t, $i, $j - $i) !== "</$tag>" ){
					$this->htmlError("expected </$tag>", $t, $i);
					return -1;
				}
				$i = $j;
			} else {
				$this->htmlError("unexpected character `". $t[$i] ."'", $t, $i);
				return -1;
			}
		}
		return $i;
		
	}
	
	
	/**
	 * Retrieves HTML. The text retrieved by the {@link self::getText()} method
	 * is checked before being returned; errors are sent to the logger but no
	 * attemps to correct them is performed.
	 * @return string Next HTML text, or NULL if end of DocBlock; the string is
	 * trimmed by <code>trim()</code>.
	 */
	private function getHtml(){
		$x = $this->getText();
		if( $x === NULL )
			return NULL;
		/* ignore = */ $this->checkHtml($x, 0);
		return trim($x);
	}
	
	
	/**
	 * Returns the current location of this scanner in the source file, inside
	 * the multi-line PHP comment being scanned.
	 * @return Where Current location of this scanner in the source file.
	 */
	private function here(){
		return new Where($this->where->getFile(), $this->line_no, $this->line_pos,
				$this->line);
	}
	
	
	/**
	 * Creates a new DocBlock content scanner. The scanner only returns the
	 * contents of the DocBlock, not the comment delimiters itself
	 * <code>/&#42;* &#42;/</code>. The initial position is the start of the
	 * first line, just after the <code>/&#42;*</code> opening marker.
	 * @param Logger $logger
	 * @param Where $where Position of the DocBlock symbol.
	 * @param string $comment Text of the multi-line PHP comment supposedly
	 * containing a DocBlock.
	 * @param ClassResolver $resolver Class name resolver.
	 * @return void
	 * @throws \InvalidArgumentException Not a DocBlock: the string provided
	 * does not start with <code>/&#42;*</code> or does not end with
	 * <code>&#42;/</code> or it is less that 5 characters long.
	 */
	private function __construct($logger, $where, $comment, $resolver){
		$this->logger = $logger;
		$this->where = $where;
		$this->line_no = $where->getLineNo() - substr_count($comment, "\n") - 1;
		$comment = (string) str_replace("\r", "", $comment);
		if( ! ( strlen($comment) >= 5 && Strings::startsWith($comment, "/**") && Strings::endsWith($comment, "*/") ) )
			throw new \InvalidArgumentException("not a DocBlock: "
				. Strings::toLiteral(substr($comment, 20)));
		$this->comment = Strings::substring($comment, 3, strlen($comment)-2);
		$this->resolver = $resolver;
		$this->nextLine();
	}
	
	
	/**
	 * Checks trailing text after tag that does not allow a descriptive part.
	 * In case, logs an error message. Also moves the DocBlock scanner to the
	 * next line tag or the end of the DocBlock. Then for every line tag,
	 * either the getDescr() method of the scanner or this method MUST be
	 * called to move to next line tag.
	 * @param string $tag
	 * @return void
	 */
	private function checkTrailingTextInTag($tag){
		$s = $this->getText();
		if( strlen(trim($s)) > 0 )
			$this->logger->error($this->here(),
			"trailing text not allowed in tag $tag");
	}
	
	
	/**
	 * Adds this known line tag and all its content to the $others[] array.
	 * Moves the scanner to the next line tag
	 * or end of the DocBlock.
	 * @param string $tag 
	 * @return void
	 */
	private function accountTag($tag){
		$this->db->others[] = $tag;
		$this->db->others[] = $this->getText();
	}
	
	
	/**
	 * Displays error about an unknown line tag. Adds this line tag and all its
	 * content to the $others[] array. Moves the scanner to the next line tag
	 * or end of the DocBlock.
	 * @param string $tag 
	 * @return void
	 */
	private function unknownTag($tag){
		$this->logger->error($this->here(), "unknown line-tag $tag");
		$this->accountTag($tag);
	}
	
	
	/**
	 *
	 * @param string $s
	 * @return string 
	 */
	private static function trimToNull($s){
		$s = trim($s);
		if( strlen($s) == 0 )
			return NULL;
		else
			return $s;
	}
	
	
	/**
	 * Basic syntax check on a variable name. Only checks if the name starts
	 * with a dollar sign and contains at least one character in its name:
	 * very basic, but missing dollar sign or missin var name at all are the
	 * common mistakes. Client code is in charge to check for the actual
	 * existence of the variable comparing with variable from source.
	 * @param string $v Name of the variable being checked.
	 * @return boolean True if $v is looks like a valid variable name.
	 */
	private function isValidVarName($v){
		if( strlen($v) >= 2 && $v[0] === "\$" ){
			return true;
		} else {
			$this->logger->error($this->here(),
			"expected variable name \$name but found $v");
			return false;
		}
	}
	
	
	/**
	 * Parse a type.
	 * @return Type
	 */
	private function getType() {
		$word = $this->getWord();
		$where = $this->here();
		if(strpos($word, '<') !== FALSE ){
			while( strpos($word, '>') === FALSE ){
				$word2 = $this->getWord();
				if( $word2 === NULL )
					// Missing '>' in type parameters.
					// Leave to the type parse to complain.
					break;
				$word .= " $word2";
				$where = $this->here();
			}
		}
		return TypeDescriptor::parse($this->logger, $where, $word, TRUE, $this->resolver, FALSE);
	}
	
	
	/**
	 * Parses a DocBlock.
	 * @param Logger $logger
	 * @param Where $where Location of the DocBlock symbol as returned by the
	 * PHPLint scanner.
	 * @param string $comment Text of the multi-line comment retrieved by the
	 * scanner.
	 * @param ClassResolver $resolver Class name resolver.
	 * @return DocBlock Parsed DocBlock.
	 */
	public static function parse($logger, $where, $comment, $resolver){
		$dbs = new DocBlockScanner($logger, $where, $comment, $resolver);
		$db = new DocBlock();
		$dbs->db = $db;
		$db->decl_in = $where;
		
		// Retrieves short and long descr:
		if( $dbs->line !== NULL && ! $dbs->is_tag ){
			
			// Retrieve short and long descr up to next line tag or end:
			$s = $dbs->getHtml();
			
			// Search first dot followed by blank or end of line.
			$i = 0;
			do {
				$dot = strpos($s, ".", $i);
				if( $dot === FALSE || $dot == strlen($s) - 1 )
					break;
				$next = $s[$dot];
				if( strpos(" \t\n\r", $next) !== FALSE )
					break;
				$i = $dot + 1;
			} while(TRUE);
			
			// FIXME: reference manual requires to limit short descr to 3 lines
			// or the first line, and require to stop after the first empty
			// line.
			// FIXME: splitting a chunk of HTML at arbitrary point we risk to
			// broke a tag pair.
			if( $dot === FALSE ){
				$db->short_descr = self::trimToNull($s);
			} else {
				$db->short_descr = self::trimToNull(substr($s, 0, $dot+1));
				$db->long_descr = self::trimToNull(Strings::substring($s, $dot+1, strlen($s)));
			}
		}
		
		while( $dbs->line !== NULL ){
			if( ! $dbs->is_tag )
				throw new \RuntimeException();
			$tag = $dbs->getWord();
			
			switch($tag){
			
			case "@access":
				if( $db->is_private || $db->is_protected || $db->is_public )
					$logger->error($dbs->here(), "multiple $tag");
				$w = $dbs->getWord();
				switch("X$w"){
				case "Xprivate": $db->is_private = TRUE; break;
				case "Xprotected": $db->is_protected = TRUE; break;
				case "Xpublic": $db->is_public = TRUE; break;
				default: $logger->error($dbs->here(), "invalid $tag $w");
				}
				$dbs->checkTrailingTextInTag($tag);
				break;
			
			case "@deprecated":
				if( $db->deprecated_descr !== NULL )
					$logger->error($dbs->here(), "multiple $tag");
				$db->deprecated_descr = $dbs->getHtml();
				break;
			
			case "@package":
				if( $db->package_word !== NULL )
					$logger->error($dbs->here(), "multiple $tag");
				$db->package_word = $dbs->getWord();
				$dbs->checkTrailingTextInTag($tag);
				break;
			
			case "@param":
				$t = $dbs->getType();
				$w = $dbs->getWord();
				
				$byref = FALSE;
				if( $w === "&" ){
					$byref = TRUE;
					$w = $dbs->getWord();
				}
				
				$is_variadic = FALSE;
				if( $w === "..." ){
					$is_variadic = TRUE;
					$w = $dbs->getWord();
				}
				
				if( $dbs->isValidVarName($w) ){
					$v = substr($w, 1);
					if( $dbs->db->getParameter($v) !== NULL ){
						$logger->error($dbs->here(), "multiple @param $t \$$v");
						$dbs->getText(); // skip descr
					} else {
						$p = new DocBlockParameter();
						$p->type = $t;
						$p->byref = $byref;
						$p->is_variadic = $is_variadic;
						$p->name = $v;
						$p->descr = $dbs->getHtml();
						$db->params[] = $p;
					}
					
				} else {
					$dbs->getText(); // skip descr
				}
				break;
				
			case "@return":
				if( $db->return_type !== NULL )
					$logger->error($dbs->here(), "multiple $tag");
				$db->return_type = $dbs->getType();
				$db->return_descr = $dbs->getHtml();
				break;
				
			case "@throws":
				$t = $dbs->getType();
				$w_location = $dbs->here();
				$descr = $dbs->getHtml();
				if( $t instanceof UnknownType ){
					// Error already signaled by TypeDescriptor::parse().
				} else if( $t instanceof ClassType ){
					$c = cast(ClassType::class, $t);
					if( $c->is_exception ){
						if( ClassType::indexOf($c, $db->throws_exceptions) >= 0 ){
							$logger->error($w_location, "@throws $t: multiple declarations of the same thrown exception");
						} else {
							$db->throws_exceptions[] = $c;
							$db->throws_descrs[] = $descr;
						}
					} else {
						$logger->error($w_location, "not an exception: $c");
					}
				} else {
					$logger->error($w_location, "@throws $t: invalid type");
				}
				break;
			
			case "@triggers":
				$w = $dbs->getWord();
				$w_location = $dbs->here();
				$descr = $dbs->getHtml();
				try {
					/* check_only = */ ErrorsSet::parse($w);
					$db->triggers_names[] = $w;
					$db->triggers_descrs[] = $descr;
				}
				catch(\InvalidArgumentException $e){
					$logger->error($w_location, "unknown error label: $w");
				}
				break;
				
			case "@var":
				if( $db->var_type !== NULL )
					$logger->error($dbs->here(), "multiple $tag");
				$db->var_type = $dbs->getType();
				$dbs->checkTrailingTextInTag($tag);
				break;
			
			/* Silently collects others well known line tags: */
			case "@author":
			case "@copyright":
			case "@license":
			case "@link":
			case "@see":
			case "@since":
			case "@todo":
			case "@version":
				$dbs->accountTag($tag);
				break;
				
			default:
				$dbs->unknownTag($tag);
			}
			
		}
		
		return $db;
		
	}
	
}
