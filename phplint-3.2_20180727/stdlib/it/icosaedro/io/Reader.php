<?php
namespace it\icosaedro\io;

require_once __DIR__ . "/../../../all.php";

use InvalidArgumentException;
use it\icosaedro\io\InputStream;
use it\icosaedro\io\IOException;
use it\icosaedro\io\texts\EncodingInterface;
use it\icosaedro\io\texts\EncodingASCII;
use it\icosaedro\io\texts\EncodingUTF8;
use it\icosaedro\io\texts\EncodingGeneric;
use it\icosaedro\io\texts\ReaderFilterInterface;
use it\icosaedro\io\texts\ReaderByWords;
use it\icosaedro\io\texts\ReaderByLines;
use it\icosaedro\utils\UString;
use it\icosaedro\utils\Strings;


/**
 * Reads text from a stream of bytes. Texts are represented as objects of
 * the {@link it\icosaedro\utils\UString} class. Decoding is performed with the
 * mbstring or iconv PHP extension (detected in this order) or ASCII only as a
 * fallback if none of these extensions is available.
 * 
 * <p>
 * The encoding to use can be automatically detected or can be specified.
 * The constructor tries to detect a BOM (byte order mark) by reading the
 * beginning of the input stream. If a BOM is detected, the corresponding encoding
 * is used. If no BOM is available, uses the encoding provided as parameter if
 * specified, or the system encoding if not provided.
 * 
 * <p>
 * Please note that the "system encoding" cited above is a quite arbitrary choice,
 * because the default encoding to use should be that of the system where the file
 * was generated. For files uploaded via WEB a good guess can be performed examining
 * the user agent: if the remote system is "Windows" chances are that the encoding
 * is "Windows-1252"; for Macintosh it can be "MACINTOSH"; for other remote systems
 * "ASCII" is the safest choice.
 * 
 * <p>
 * Invalid binary data are replaced with the Unicode replacement character U+FFFD
 * (question mark inside a diamond "&#xfffd;").
 * 
 * <p>This example reads and displays a text file:
 * <blockquote><pre>
 * use it\icosaedro\io\File;
 * use it\icosaedro\io\FileInputStream;
 * use it\icosaedro\io\Reader;
 * 
 * // Creates the name of the file to read:
 * $fn = File::fromLocaleEncoded(__DIR__ . "/data.txt");
 * 
 * // Opens the file as input stream of bytes:
 * $fis = new FileInputStream($fn);
 * 
 * // Creates a text reader on top of this input stream:
 * $r = new Reader($fis);
 * 
 * echo "Detected encoding: ", $r-&gt;getEncoding(), "\n";
 * echo "Found BOM: ", ($r-&gt;foundBOM()? "yes" : "no"), "\n";
 * echo "File content follows:\n";
 * while( ($line = $r-&gt;readLine()) !== NULL )
 *	echo $line-&gt;toUTF8(), "\n";
 * $r-&gt;close();
 * </pre></blockquote>
 * 
 * @author Umberto Salsi <salsi@icosaedro.it>
 * @version $Date: 2016/02/08 23:55:29 $
 */
class Reader {
	
	/*
	 * Byte order marks. UCS4LE is problematic to detect because, if only 2
	 * or 3 bytes from the header are available, it may be confused with
	 * UCS2LE. So, cross the fingers and try to detect UCS4LE first.
	 */
	private static $BOMS = array(
	//  normalized        std name   BOM
	//  ----------        ---------  ------------------
		"UCS4LE" => array("UCS-4LE", "\xff\xfe\x00\x00"),
		"UCS4BE" => array("UCS-4BE", "\x00\x00\xfe\xff"),
		"UTF8"   => array("UTF-8",   "\xef\xbb\xbf"),
		"UCS2BE" => array("UCS-2BE", "\xfe\xff"),
		"UCS2LE" => array("UCS-2LE", "\xff\xfe")
	);
	
	private $found_bom = FALSE;
	
	/**
	 * @var ReaderFilterInterface
	 */
	private $rfi;
	
	
	/**
	 * Returns true if a BOM matching the requested or detected encoding has
	 * been found and removed.
	 * @return boolean
	 */
	public function foundBOM() {
		return $this->found_bom;
	}


	/**
	 * Returns the encoding requested or detected to read this file.
	 * @return EncodingInterface
	 */
	public function getEncoding() {
		return $this->rfi->getEncoding();
	}
	
	
	/**
	 * Skip BOM bytes from the leading bytes of the stream. Immediately stops
	 * whenever an incompatible byte is found; in this case, the whole unmodified
	 * leading bytes are returned.
	 * @param InputStream $in
	 * @param string $buf Leading bytes read so far.
	 * @param string $bom Expected BOM.
	 * @return string Remaining leading bytes after BOM removal.
	 * @throws IOException
	 */
	private function skipBOM($in, $buf, $bom)
	{
		do {
			if( strlen($buf) >= strlen($bom) ){
				if( Strings::startsWith($buf, $bom) ){
					$this->found_bom = TRUE;
					return Strings::substring($buf, strlen($bom), strlen($buf));
				} else
					return $buf;
			} else if( ! Strings::startsWith($bom, $buf) ){
				return $buf;
			}
			$more = $in->readBytes(128 - strlen($buf));
			if( $more === NULL )
				return $buf;
			$buf .= $more;
		} while(TRUE);
	}
	
	
	/**
	 * Sets encoding, removes BOM, and instantiates the appropriate handler
	 * filter. If encoding still unknown, uses locale encoding or UTF-8 as
	 * default.
	 * @param InputStream $in
	 * @param string $encoding Encoding name.
	 * @param string $buf Leading bytes read so far, just to detect the BOM.
	 * @return void
	 * @throws IOException
	 */
	private function setEncoding($in, $encoding, $buf)
	{
		$normalized = EncodingInterface::normalize($encoding);
		// Skip BOM:
		if( isset(self::$BOMS[$normalized]))
			$buf = $this->skipBOM($in, $buf, self::$BOMS[$normalized][1]);
		// Create decoder object:
		if( $normalized === "ASCII" )
			/*. EncodingInterface .*/ $encoder = new EncodingASCII();
		else if( $normalized === "UTF8" )
			$encoder = new EncodingUTF8();
		else
			$encoder = new EncodingGeneric($encoding);
		switch($normalized){
			case "UCS2BE": case "UTF16BE":
			case "UCS2LE": case "UTF16LE":
			case "UCS4BE": case "UTF32BE":
			case "UCS4LE": case "UTF32LE":
				$this->rfi = new ReaderByWords($in, $buf, $encoder);
				break;
			default:
				$this->rfi = new ReaderByLines($in, $buf, $encoder);
		}
	}

	

	/**
	 * Creates a new text reader.
	 * @param InputStream $in Source stream of bytes.
	 * @param string $encoding Default encoding to use if no BOM is detected. This
	 * encoding name must be chosen among those available from the {@link mb_list_encodings()},
	 * for example "UTF-8", "ISO-8859-1", "ASCII". If not set, uses the default
	 * system encoding. The methods {@link self::getEncoding()} and {@link self::foundBOM()}
	 * tell the configuration actually used.
	 * @throws InvalidArgumentException Unknown encoding.
	 * @throws IOException
	 */
	public function __construct($in, $encoding = NULL)
	{
		// Try detecting BOM:
		$buf = "";
		while( strlen($buf) < 4 ){
			$more = $in->readBytes(4 - strlen($buf));
			if( $more === NULL ){
				// empty or very short file
				break;
			}
			$buf .= $more;
		}
		foreach(self::$BOMS as $bom){
			if( Strings::startsWith($buf, $bom[1]) ){
				// BOM detected
				$this->setEncoding($in, $bom[0], $buf);
				return;
			}
		}
		// No BOM.
		if( $encoding === NULL )
			$encoding = EncodingInterface::getLocaleEncoding();
		$this->setEncoding($in, $encoding, $buf);
	}
	
	
	/**
	 * Reads a single character.
	 * @return int Codepoint of the character read, or -1 on end of file.
	 * @throws IOException
	 */
	public function readCodepoint() {
		return  $this->rfi->readCodepoint();
	}
	
	
	/**
	 * Reads up to the given number of characters.
	 * @param int $n Maximum number of characters to read.
	 * @return UString Characters read, or NULL on end of file. If $n is &le; 0
	 * the empty string is returned.
	 * @throws IOException
	 * @throws InvalidArgumentException $n is negative.
	 */
	public function read($n) {
		return $this->rfi->read($n);
	}
	
	
	/**
	 * Reads a line. A line is either a sequence of characters terminated by
	 * the end of line marker "\r\n" or "\n" (properly encoded according to the
	 * current encoding used) or at least one character before the end of file.
	 * The end of line marker itself is not returned.
	 * @return UString Line read or NULL on end of file.
	 * @throws IOException
	 */
	public function readLine() {
		return $this->rfi->readLine();
	}
	
	
	/**
	 * @return void
	 * @throws IOException
	 */
	function close() {
		$this->rfi->close();
	}
	
}
