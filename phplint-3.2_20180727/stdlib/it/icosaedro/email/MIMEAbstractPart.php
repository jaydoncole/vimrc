<?php

namespace it\icosaedro\email;

require_once __DIR__ . "/../../../autoload.php";
require_once __DIR__ . "/../../../errors.php";

use OverflowException;
use InvalidArgumentException;
use it\icosaedro\containers\Printable;
use it\icosaedro\io\OutputStream;
use it\icosaedro\io\IOException;
use it\icosaedro\io\StringOutputStream;

/*. require_module 'pcre'; .*/

/**
 * MIME part base class that represents an email or one of its parts. Each part
 * contains an header as a list of fields, and a body whose structure is specific
 * of any derived class. Methods shared by all the derived classes are implemented
 * here.
 * 
 * <p>End-of-line markers are internally represented by a single line-feed
 * character "\n"; any carriage-return "\r" is silently removed from textual
 * contents. Properly encoded binary content (quoted-printable, Base64) is
 * obviously not affected by this internal convention.
 * It is assumed that each specific transport method will take care to restore
 * its own specific line-ending convention before actually send the message.
 * 
 * @author Umberto Salsi <salsi@icosaedro.it>
 * @version $Date: 2018/07/14 11:44:21 $
 */
abstract class MIMEAbstractPart implements Printable {
	
	/**
	 * SMTP max line length, end-of-line marker excluded (bytes).
	 */
	const MAX_LINE_LEN = 998;
	
	/**
	 * Header of this part.
	 * @var Header
	 */
	public $header;

	/**
	 * Encodes string to the requested format.
	 * @param string $s Subject string.
	 * @param string $encoding One of the ENCODING_* constants.
	 * @return string Encoded string.
	 * @throws InvalidArgumentException Unknown encoding.
	 */
	static function encodeString($s, $encoding)
	{
		switch($encoding) {
		  
		  case Header::ENCODING_7BIT:
			  $s = (string) str_replace("\000", "", $s);
			  $s = (string) str_replace("\r", "", $s);
			  $s = preg_replace("/[\x80-\xff]/", "", $s);
			  return wordwrap($s, self::MAX_LINE_LEN, "\n", TRUE);
		  
		  case Header::ENCODING_8BIT:
			  $s = (string) str_replace("\000", "", $s);
			  $s = (string) str_replace("\r", "", $s);
			  return wordwrap($s, self::MAX_LINE_LEN, "\n", TRUE);
			  
		  case Header::ENCODING_QUOTED_PRINTABLE:
			  return (string) str_replace("\r", "", quoted_printable_encode($s));
			
		  case Header::ENCODING_BASE64:
			  return chunk_split(base64_encode($s), 76, "\n");
		  
		  default:
			  throw new InvalidArgumentException("unknown encoding: $encoding");
		}
	}
	
	/**
	 * Creates a new MIME part.
	 * @param Header $header Header of this part.
	 */
	function __construct($header)
	{
		$this->header = $header;
	}
	
	
	/**
	 * Writes body lines of this part in RFC 822 format using the encoding as set
	 * in the header. Zero or more lines terminated by "\n" must be written.
	 * BEWARE: non terminated lines may corrupt the syntax of the e-mail.
	 * @param OutputStream $out
	 * @return void
	 * @throws IOException
	 */
	abstract function writeBody($out);
	
	/**
	 * Writes header and body lines of this part in RFC 822 format. Lines are
	 * terminated by a single new-line character "\n".
	 * @param OutputStream $out
	 * @return void
	 * @throws IOException
	 */
	function write($out)
	{
		$this->header->write($out);
		$out->writeBytes("\n");
		$this->writeBody($out);
	}
	
	/**
	 * Returns the textual representation of this part. It includes the header,
	 * a separator empty line, and a body possibly consisting of other nested
	 * sub-parts. Lines are all terminated by a single line-feed character "\n".
	 * @return string RFC 822 representation of this part.
	 */
	function __toString()
	{
		$buf = new StringOutputStream();
		try {
			$this->write($buf);
		}
		catch(IOException $e){
			error_log("$e");
			return "";
		}
		return $buf->__toString();
	}
	
	/**
	 * Returns the size of the un-encoded content of this part, including sub-parts.
	 * @return int Size of the content of this part (bytes).
	 * @throws OverflowException Integer precision overflow.
	 */
	abstract function getContentSize();
	
	/**
	 * Returns the estimated size of the encoded content of this part, including
	 * sub-parts.
	 * @return int Size of the encoded content of this part (bytes).
	 * @throws OverflowException Integer precision overflow.
	 */
	abstract function getEstimatedEncodedSize();
	
}
