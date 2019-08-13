<?php

namespace it\icosaedro\email;

require_once __DIR__ . "/../../../autoload.php";
require_once __DIR__ . "/../../../errors.php";

use RuntimeException;
use InvalidArgumentException;
use OverflowException;
use it\icosaedro\io\OutputStream;
use it\icosaedro\io\IOException;

/**
 * MIME part representing some content in memory. This content may have a readable
 * name proposed to the user, may have a specific MIME type, and may have a
 * content-ID for being referred by other parts of an articulated message.
 * 
 * @author Umberto Salsi <salsi@icosaedro.it>
 * @version $Date: 2018/07/14 11:45:16 $
 */
class MIMEPartMemory extends MIMEAbstractPart {
	
	/**
	 * Binary content of this part. The header tells the type and, for text and
	 * HTML types, the character set used.
	 * @var string
	 */
	public $content;
	
	/**
	 * @param Header $header
	 */
	function __construct($header)
	{
		parent::__construct($header);
		$this->content = NULL;
	}
	
	/**
	 * Create a custom part from data in memory.
	 * @param string $content
	 * @param string $type MIME type of the file. See the description of the
	 * {@link it\icosaedro\email\Header::isValidContentType()} method for
	 * the allowed syntax.
	 * @param string $charset Character set of the content, or empty not applicable.
	 * @param string $name Proposed name of the file (UTF-8).
	 * @param string $cid Assigned content ID to this part. Must have the same
	 * syntax of an e-mail address.
	 * @param string $encoding Content encoding to be used, one of the
	 * ENCODING_* constants.
	 * @param boolean $is_attachment If this is an attachment rather than an
	 * embedded part of the message.
	 * @return self Built part.
	 * @throws InvalidArgumentException Invalid MIME type syntax. Invalid CID
	 * syntax.
	 */
	static function build($content, $type, $charset, $name, $cid, $encoding, $is_attachment)
	{
		$type = strtolower($type);
		if( ! Header::isValidContentType($type) )
			throw new InvalidArgumentException("invalid MIME type syntax: $type");
		if( strlen($cid) > 0 ){
			$reason = Field::checkEmailAddress($cid);
			if( $reason !== NULL )
				throw new InvalidArgumentException("invalid cid=$cid: $reason");
		}
		
		$header = new Header();
		
		// Content-Type:
		$ct = "Content-Type: $type";
		if( strlen($charset) > 0 )
			$ct .= "; charset=\"$charset\"";
		$header->addLine($ct);
		
		// Content-Transfer-Encoding:
		$header->addLine("Content-Transfer-Encoding: $encoding");
		
		// Content-ID:
		if( strlen($cid) > 0 )
			$header->addLine("Content-ID: <$cid>");
		
		// Content-Disposition:
		if( $is_attachment || strlen($name) > 0 ){
			$cd = "Content-Disposition: " . ($is_attachment? "attachment" : "inline");
			if( strlen($name) > 0 )
				$cd .= "; filename=\"" . Field::encodeWords($name, "phrase") . "\"";
			$header->addLine($cd);
		}
		
		$part = new self($header);
		$part->content = $content;
		return $part;
	}
	
	
	/**
	 * @param OutputStream $out
	 * @return void
	 * @throws IOException
	 */
	function writeBody($out)
	{
		$out->writeBytes(rtrim(self::encodeString($this->content, $this->header->getEncoding())) . "\n");
	}
	
	/**
	 * Returns the size of the un-encoded content of this part.
	 * @return int Size of the content of this part (bytes).
	 * @throws OverflowException Integer precision overflow.
	 */
	function getContentSize()
	{
		return strlen($this->content);
	}
	
	/**
	 * Returns the size of the un-encoded content of this part.
	 * @return int Size of the content of this part (bytes).
	 * @throws OverflowException Integer precision overflow.
	 */
	function getEstimatedEncodedSize()
	{
		$encoding = $this->header->getEncoding();
		$size = (float) $this->getContentSize();
		switch($encoding){
			case Header::ENCODING_7BIT:
			case Header::ENCODING_8BIT:
				// Assuming no CR in content and average line length of 40:
				$size = 1.025 * $size;
				break;
			case Header::ENCODING_QUOTED_PRINTABLE:
				// Assuming no CR in content and average line length of 75:
				$non_ascii_count = strlen( preg_replace("/[\\000-\x7f]/", "", $this->content) );
				$size = 1.04 * ($size + 2 * $non_ascii_count);
				break;
			case Header::ENCODING_BASE64:
				// 57 bytes --> 76 encoded + CR + LF = 78:
				$size = $size / 57 * 78;
				break;
			default:
				throw new RuntimeException($encoding);
		}
		$size = floor($size);
		if( $size > PHP_INT_MAX )
			throw new OverflowException("int overflow: size=$size");
		return (int) $size;
	}
		
}
