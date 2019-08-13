<?php

namespace it\icosaedro\email;

require_once __DIR__ . "/../../../autoload.php";
require_once __DIR__ . "/../../../errors.php";

use Exception;
use OverflowException;
use it\icosaedro\io\OutputStream;
use it\icosaedro\io\IOException;
use it\icosaedro\utils\Random;

/**
 * MIME part with zero or more nested parts.
 * 
 * @author Umberto Salsi <salsi@icosaedro.it>
 * @version $Date: 2018/07/14 11:44:37 $
 */
class MIMEMultiPart extends MIMEAbstractPart {
	
	/**
	 * Several unrelated parts are listed. Typically the first part is the
	 * user's readable message, the following parts are attachments.
	 */
	const MULTIPART_MIXED = "multipart/mixed";
	
	/**
	 * Several related parts are listed. Typically the first part refers to the
	 * others parts by their content-ID.
	 */
	const MULTIPART_RELATED = "multipart/related";
	
	/**
	 * Several parts are listed carrying the same content in different formats.
	 * Parts should be listed in increasing order of preference, for example
	 * plain text first, HTML next.
	 */
	const MULTIPART_ALTERNATIVE = "multipart/alternative";
	
	/**
	 * @var MIMEAbstractPart[int]
	 */
	public $parts;
	
	/**
	 * @param Header $header
	 */
	function __construct($header)
	{
		parent::__construct($header);
		$this->parts = array();
	}
	
	/**
	 * @param string $type One of the MULTIPART_* constants.
	 * @return self
	 */
	static function build($type)
	{
		$header = new Header();
		$boundary = "=_" . base64_encode( Random::getCommon()->randomBytes(12) );
		$header->addLine("Content-Type: $type; boundary=\"$boundary\"");
		return new self($header);
	}
	
	/**
	 * Appends another part to this part.
	 * @param MIMEAbstractPart $part
	 */
	function appendPart($part)
	{
		$this->parts[] = $part;
	}
	
	
	/**
	 * @param OutputStream $out
	 * @return void
	 * @throws IOException
	 */
	function writeBody($out)
	{
		$boundary = $this->header->getBoundary();
		foreach($this->parts as $part){
			$out->writeBytes("--$boundary\n");
			$part->write($out);
		}
		$out->writeBytes("--$boundary--\n");
	}
	
	/**
	 * Returns the total size of the un-encoded content of all sub-parts.
	 * @return int Total size of the contents of this part (bytes).
	 */
	function getContentSize()
	{
		$size = 0;
		foreach($this->parts as $part)
			$size += $part->getContentSize();
		return $size;
	}
	
	/**
	 * Returns the total estimated size of the encoded content of all sub-parts.
	 * @return int Total estimated size of the encoded contents of this part (bytes).
	 * @throws OverflowException Integer precision overflow.
	 */
	function getEstimatedEncodedSize()
	{
		$size = 0;
		foreach($this->parts as $part)
			$size += $part->getEstimatedEncodedSize();
		if( is_float($size) || $size < 0 )
			throw new OverflowException("int overflow: size=$size");
		return $size;
	}
		
}
