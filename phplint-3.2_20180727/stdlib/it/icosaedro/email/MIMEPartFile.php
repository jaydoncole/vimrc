<?php

namespace it\icosaedro\email;

require_once __DIR__ . "/../../../autoload.php";
require_once __DIR__ . "/../../../errors.php";

use Exception;
use InvalidArgumentException;
use OverflowException;
use ErrorException;
use RuntimeException;
use UnimplementedException;
use it\icosaedro\io\OutputStream;
use it\icosaedro\io\IOException;

/**
 * MIME part represented by a file. Files have a local path on the sender system,
 * may have a readable name proposed to the user, may have a specific MIME type,
 * and may have a content-ID for being referred by other parts of an articulated
 * message. File processing is performed in streamed mode.
 * 
 * @author Umberto Salsi <salsi@icosaedro.it>
 * @version $Date: 2018/07/14 11:44:55 $
 */
class MIMEPartFile extends MIMEAbstractPart {
	
	/**
	 * Path of the file (local system encoding).
	 * @var string
	 */
	private $path;
	
	/**
	 * @param Header $header
	 * @param string $path
	 */
	function __construct($header, $path)
	{
		parent::__construct($header);
		$this->path = $path;
	}
	
	/**
	 * Creates a file MIME part.
	 * @param string $path Path of the file (local system encoding).
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
	static function build($path, $type, $charset, $name, $cid, $encoding, $is_attachment)
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
		
		return new self($header, $path);
	}
	
	
	/**
	 * @param OutputStream $out
	 * @param boolean $seven_bits_only
	 * @throws ErrorException
	 * @throws IOException
	 */
	private function writeBinary($out, $seven_bits_only)
	{
		$f = fopen($this->path, "rb");
		$buf = "";
		do {
			
			// Feed the buffer:
			if( strlen($buf) < self::MAX_LINE_LEN ){
				$chunk = fread($f, 2 * self::MAX_LINE_LEN);
				if( is_string($chunk) && strlen($chunk) > 0 ){
					$chunk = (string) str_replace("\000", "", $chunk);
					$chunk = (string) str_replace("\r", "", $chunk);
					if( $seven_bits_only )
						$chunk = preg_replace("/[\x80-\xff]/", "", $chunk);
					$buf .= $chunk;
				}
			}
			if( strlen($buf) == 0 )
				break;
			
			// Split buffer into lines no more than MAX_LINE_LEN bytes long:
			$eol = strrpos($buf, "\n");
			if( $eol === FALSE ){
				$chunk = "$buf\n";
				$buf = "";
			} else {
				$eol++;
				$chunk = substr($buf, 0, $eol);
				$buf = substr($buf, $eol);
			}
			$chunk = wordwrap($chunk, self::MAX_LINE_LEN, "\n", TRUE);
			$out->writeBytes($chunk);
		}while(TRUE);
		fclose($f);
	}
	
	
	/**
	 * @param OutputStream $out
	 * @throws ErrorException
	 * @throws IOException
	 */
	private function writeQP($out)
	{
		$f = fopen($this->path, "rb");
		do {
			$chunk = fread($f, 1024);
			if( ! is_string($chunk) || strlen($chunk) == 0 )
				break;
			$chunk = (string) str_replace("\r", "", quoted_printable_encode($chunk));
			$out->writeBytes($chunk . "=\n");
		}while(TRUE);
		fclose($f);
	}
	
	
	/**
	 * @param OutputStream $out
	 * @throws ErrorException
	 * @throws IOException
	 */
	private function writeBase64($out)
	{
		$f = fopen($this->path, "rb");
		do {
			$chunk = fread($f, 57);
			if( ! is_string($chunk) || strlen($chunk) == 0 )
				break;
			$out->writeBytes( base64_encode($chunk) . "\n" );
		}while(TRUE);
		fclose($f);
	}
	
	
	/**
	 * @param OutputStream $out
	 * @return void
	 * @throws IOException
	 */
	function writeBody($out)
	{
		try {
			switch($this->header->getEncoding()){
				case Header::ENCODING_7BIT:
					$this->writeBinary($out, TRUE);
					break;
				case Header::ENCODING_8BIT:
					$this->writeBinary($out, FALSE);
					break;
				case Header::ENCODING_QUOTED_PRINTABLE:
					$this->writeQP($out);
					break;
				case Header::ENCODING_BASE64:
					$this->writeBase64($out);
					break;
				default:
					throw new \RuntimeException();
			}
		}
		catch(ErrorException $e){
			throw new IOException($e->getMessage());
		}
	}
	
	/**
	 * Returns the size of the un-encoded content of this part.
	 * @return int Size of the content of this part (bytes).
	 * @throws OverflowException Integer precision overflow.
	 */
	function getContentSize()
	{
		try {
			$size = filesize($this->path);
		}
		catch(ErrorException $e){
			return 0;
		}
		// FIXME: unfortunately, on 32-bits systems filesize() returns quite
		// random numbers with files larger than 2GB rather than triggering an
		// error, so these cases cannot be detected; here we do our best:
		if( ! is_int($size) || $size < 0 )
			throw new OverflowException("int overflow: size=$size");
		return $size;
	}
	
	/**
	 * Returns the size of the un-encoded content of this part.
	 * @return int Size of the content of this part (bytes).
	 * @throws OverflowException Integer precision overflow.
	 */
	function getEstimatedEncodedSize()
	{
		$encoding = $this->header->getEncoding();
		switch($encoding){
			
			case Header::ENCODING_7BIT:
			case Header::ENCODING_8BIT:
				$size = (float) $this->getContentSize();
				// Assuming no CR in content and average line length of 40:
				$size = 1.025 * $size;
				break;
			
			case Header::ENCODING_QUOTED_PRINTABLE:
				$size = 0.0;
				$non_ascii_count = 0;
				try {
					$f = fopen($this->path, "rb");
					do {
						$chunk = fread($f, 8000);
						if( ! is_string($chunk) || strlen($chunk) == 0 )
							break;
						$size += strlen($chunk);
						$non_ascii_count += strlen( preg_replace("/[\\000-\x7f]/", "", $chunk) );
					} while(TRUE);
					fclose($f);
				}
				catch(ErrorException $e){
					// ignore and return zero
				}
				// Assuming no CR in content and average line length of 75:
				$size = 1.04 * ($size + 2 * $non_ascii_count);
				break;
				
			case Header::ENCODING_BASE64:
				$size = (float) $this->getContentSize();
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
