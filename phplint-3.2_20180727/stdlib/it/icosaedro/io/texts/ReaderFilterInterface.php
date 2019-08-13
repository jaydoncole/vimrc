<?php
namespace it\icosaedro\io\texts;

require_once __DIR__ . "/../../../../all.php";

use it\icosaedro\io\IOException;
use it\icosaedro\utils\UString;
use it\icosaedro\containers\Printable;
use it\icosaedro\io\texts\EncodingInterface;

/**
 * Service interface for the {@link ../Reader.html Reader} class. Objects
 * implementing this interface allow to retrieve text by decoding bytes coming
 * from a stream of bytes encoded with a given encoding.
 * 
 * @author Umberto Salsi <salsi@icosaedro.it>
 * @version $Date: 2016/01/15 06:49:18 $
 */
interface ReaderFilterInterface extends Printable {
	
	/**
	 * Returns the encoding used.
	 * @return EncodingInterface
	 */
	function getEncoding();
	
	/**
	 * Returns a single codepoint read.
	 * @return int Codepoint in the range [0,65535], or -1 at the end of the
	 * stream.
	 * @throws IOException
	 */
	function readCodepoint();
	
	/**
	 * Reads the given number of characters / codepoints.
	 * @param int $n Maximum number of characters / codepoints to read.
	 * @return UString Returns a number of characters not greater than requested,
	 * or NULL at the end of the file.
	 * @throws IOException
	 */
	function read($n);
	
	/**
	 * Reads a line. A line is either a sequence of characters terminated by
	 * the end of line marker "\r\n" or "\n" (properly encoded according to the
	 * expected encoding) or the end of file. The end of line marker itself is
	 * not returned.
	 * @return UString Line read or NULL at the end of file.
	 * @throws IOException
	 */
	public function readLine();
	
	/**
	 * Closes this source. If called more that once has no effect. Once closed,
	 * this object cannot be used anymore.
	 * @return void
	 * @throws IOException
	 */
	function close();
}