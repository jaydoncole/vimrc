<?php

namespace it\icosaedro\email;

require_once __DIR__ . "/../../../all.php";

use it\icosaedro\containers\Printable;
use it\icosaedro\io\IOException;

/**
 * Email reader interface wrapper. An RFC 822 email is basically a binary file
 * because it may contain arbitrary bytes in several parts encoding different
 * character sets, but them are also structured by lines terminated by CR+NL or
 * NL only (to be a bit tolerant about line ending convention).
 * Objects implementing this interface are used by the email parser class to
 * scan the email line by line just like the it\icosaedro\io\LineInputWrapper
 * class, but with one important difference: fetching another line and getting
 * that line are two distinct methods, which is handy while parsing nested MIME
 * parts looking for the boundary of the parent part.
 * 
 * @author Umberto Salsi <salsi@icosaedro.it>
 * @version $Date: 2018/07/14 11:45:48 $
 */
interface ReaderInterface extends Printable {
	
	/**
	 * Returns readable current position. Implementations may return the current
	 * line number and possibly other informations to build meaningful error
	 * messages.
	 * @return string
	 */
	function __toString();
	
	/**
	 * Reads one more line, that becomes the current line.
	 * @return void
	 * @throws IOException
	 */
	function readLine();
	
	/**
	 * Returns the current line.
	 * @return string Current line without EOL marker CR+NL or NL;
	 * NULL at EOF.
	 */
	function getLine();
	
	/**
	 * Closes this source.
	 * @return void
	 * @throws IOException
	 */
	function close();
	
}
