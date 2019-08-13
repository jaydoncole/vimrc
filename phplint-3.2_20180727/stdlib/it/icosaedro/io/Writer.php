<?php
namespace it\icosaedro\io;

require_once __DIR__ . "/../../../all.php";

use InvalidArgumentException;
use OutOfRangeException;
use it\icosaedro\io\OutputStream;
use it\icosaedro\io\IOException;
use it\icosaedro\utils\UString;
use it\icosaedro\io\texts\EncodingInterface;
use it\icosaedro\io\texts\EncodingGeneric;
use it\icosaedro\io\texts\EncodingASCII;
use it\icosaedro\io\texts\EncodingUTF8;


/**
 * Writes strings of Unicode characters on a stream of bytes. Texts are
 * represented as objects of the {@link it\icosaedro\utils\UString} class.
 * ASCII and UTF-8 encoding is performed using the methods of the UString class
 * itself. Other encodings are implemented through the mbstring or iconv PHP
 * extension, detected in this order. Characters and codepoints that cannot be
 * converted to the target encoding are replaced with a question mark "?".
 * 
 * <p>Allows to set the encoding, the BOM and the line ending. If the encoding
 * is not provided, takes the current system locale encoding which is normally
 * set, or just ASCII otherwise.
 * 
 * <p>This example writes a file on the current system using the current system
 * locale encoding and the system line ending default:
 * 
 * <blockquote><pre>
 * use it\icosaedro\io\File;
 * use it\icosaedro\io\FileOutputStream;
 * use it\icosaedro\io\Writer;
 * 
 * // Creates the name of the file to write:
 * $fn = File::fromLocaleEncoded(__DIR__ . "/Sample.txt");
 * 
 * // Opens the file as output stream of bytes:
 * $fos = new FileOutputStream($fn);
 * 
 * // Creates a text writer on top of this output stream:
 * $w = new Writer($fos);
 * 
 * // Writes just one line:
 * $w-&gt;writeLine( UString::fromASCII("This is the first line of the file.") );
 * $w-&gt;close();
 * </pre></blockquote>
 * 
 * The following example sends a text file to the remote client using the UTF-8
 * encoding with BOM and DOS/Windows line ending:
 * 
 * <blockquote><pre>
 * header("Content-Type: text/plain");
 * header("Content-Disposition: attachment; filename=\"Sample.txt\"");
 * $ros = new ResourceOutputStream( fopen("php://output") );
 * $with_bom = TRUE;
 * $line_end = UString::fromASCII("\r\n");
 * $w = new Writer($ros, "UTF-8", $with_bom, $line_end);
 * $w-&gt;writeLine( UString::fromASCII("This is the first line of the file.") );
 * $w-&gt;close();
 * </pre></blockquote>
 * 
 * For maximum portability of text files across different systems, the UTF-8
 * encoding with BOM and DOS/Windows line ending is strongly recommended.
 * 
 * @author Umberto Salsi <salsi@icosaedro.it>
 * @version $Date: 2015/03/11 06:09:04 $
 */
class Writer {
	
	/**
	 * @var OutputStream
	 */
	private $out;
	
	/**
	 * If set, the text encoder to use.
	 * @var EncodingInterface
	 */
	private $encoder;
	
	/**
	 * Line ending (destination encoding).
	 * @var string
	 */
	private $line_ending;
	
	
	/**
	 * Creates a new text writer object.
	 * @param OutputStream $out
	 * @param string $encoding Text encoding. If null, it is determined automatically.
	 * @param boolean $with_bom If a BOM marker has to be written. Meaningful
	 * only where allowed by encoding, ignored otherwise.
	 * @param UString $line_ending String to write as line termination marker.
	 * If null, it is determined automatically.
	 * @throws IOException
	 * @throws InvalidArgumentException Invalid encoding or not available.
	 */
	public function __construct($out, $encoding = NULL, $with_bom = TRUE,
			$line_ending = NULL)
	{
		$this->out = $out;
		
		// determines encoder:
		if( $encoding === NULL )
			$encoding = EncodingInterface::getLocaleEncoding();
		$normalized = EncodingInterface::normalize($encoding);
		if( strlen($normalized) == 0 or $normalized === "ASCII" )
			/*. EncodingInterface .*/ $encoder = new EncodingASCII();
		else if( $normalized === "UTF8" )
			$encoder = new EncodingUTF8();
		else
			$encoder = new EncodingGeneric($encoding);
		$this->encoder = $encoder;
		
		// writes BOM:
		if( $with_bom ){
			switch($normalized){
				case "UTF8":  $bom = "\xef\xbb\xbf";  break;
				case "UCS2BE": case "UTF16BE":  $bom = "\xfe\xff";  break;
				case "UCS2LE": case "UTF16LE":  $bom = "\xff\xfe";  break;
				case "UCS4BE": case "UTF32BE":  $bom = "\x00\x00\xfe\xff";  break;
				case "UCS4LE": case "UTF32LE":  $bom = "\xff\xfe\x00\x00";  break;
				default:  $bom = NULL;
			}
			if( $bom !== NULL )
				$out->writeBytes($bom);
		}
		
		// determines line ending marker:
		if( $line_ending === NULL ){
			if( DIRECTORY_SEPARATOR === "\\" )
				$line_ending = UString::fromASCII("\r\n");
			else
				$line_ending = UString::fromASCII("\n");
		}
		$this->line_ending = $encoder->encode($line_ending);
	}


	/**
	 * Writes a single codepoint, given its code.
	 * @param int $code  Codepoint in the range [0,65535]. Note that also
	 * undefined, reserved and forbidden codepoints are allowed.
	 * @return void
	 * @throws OutOfRangeException  If the codepoint is invalid.
	 * @throws IOException
	 */
	public function writeCodepoint($code) {
		$this->out->writeBytes( $this->encoder->encode(UString::chr($code)) );
	}
	
	
	/**
	 * Writes a string.
	 * @param UString $s String to write. If null, has no effect.
	 * @return void
	 * @throws IOException
	 */
	public function write($s) {
		$this->out->writeBytes( $this->encoder->encode($s) );
	}
	
	
	/**
	 * Writes a string followed by a new line code.
	 * @param UString $s If null, only writes the new line code.
	 * @return void
	 * @throws IOException
	 */
	public function writeLine($s = NULL) {
		if( $s !== NULL )
			$this->out->writeBytes( $this->encoder->encode($s) );
		$this->out->writeBytes($this->line_ending);
	}
	
	
	/**
	 * Closes this writer and the underlying output stream. Once closed, this
	 * object cannot be used anymore. Calling this method more than once has no
	 * effect.
	 * @return void
	 * @throws IOException
	 */
	public function close() {
		if( $this->out === NULL )
			return;
		$this->out->close();
		$this->out = NULL;
	}
	
}
