<?php

namespace it\icosaedro\email;

require_once __DIR__ . "/../../../all.php";

/*. require_module 'array'; .*/

use it\icosaedro\containers\Printable;
use it\icosaedro\utils\DateTimeTZ;
use it\icosaedro\utils\Strings;
use it\icosaedro\io\IOException;
use it\icosaedro\io\OutputStream;
use it\icosaedro\io\StringOutputStream;
use InvalidArgumentException;
use OutOfRangeException;

/**
 * Header as a list of fields. Methods to build and parse specific fields are
 * also implemented.
 * 
 * @author Umberto Salsi <salsi@icosaedro.it>
 * @version $Date: 2018/07/14 11:43:21 $
 */
class Header implements Printable {
	
	/**
	 * ASCII only content encoding. Invalid bytes (zero and any code above 127)
	 * are silently discarded; carriage-return (ASCII 13) is removed and only
	 * line-feed (ASCII 10) is used as line separator.
	 */
	const ENCODING_7BIT = "7bit";
	
	/**
	 * Non-ASCII content encoding. The zero byte is silently removed. The
	 * carriage-return (ASCII 13) is removed and only line-feed (ASCII 10) is
	 * used as line separator. Very long lines are split, possibly at white space
	 * where available.
	 */
	const ENCODING_8BIT = "8bit";
	
	/**
	 * Transparent, binary-safe content encoding suitable for textual, mostly
	 * ASCII contents.
	 */
	const ENCODING_QUOTED_PRINTABLE = "quoted-printable";
	
	/**
	 * Transparent, binary-safe content encoding.
	 */
	const ENCODING_BASE64 = "base64";
	
	/**
	 * @var Field[int]
	 */
	public $fields;
	
	/**
	 * @var string
	 */
	private $cached_content_type_type = NULL;
	
	/**
	 * @var string
	 */
	private $cached_content_type_charset = NULL;
	
	/**
	 * @var string
	 */
	private $cached_content_type_boundary = NULL;
	
	/**
	 * @var string
	 */
	private $cached_content_type_name = NULL;
	
	/**
	 * "yes", "no", NULL if still unknown.
	 * @var string
	 */
	private $cached_content_disposition_attachment = NULL;
	
	/**
	 * @var string
	 */
	private $cached_content_disposition_filename = NULL;
	
	/**
	 * Add the field to this header.
	 * @param Field $field
	 */
	function addField($field)
	{
		$this->fields[] = $field;
	}
	
	/**
	 * Parse the header line and add the resulting filed to this header.
	 * @param string $line
	 */
	function addLine($line)
	{
		$this->addField( Field::parse($line) );
	}
	
	function __construct()
	{
		$this->fields = array();
	}
	
	/**
	 * @param string $name
	 * @return Field[int]
	 */
	function getFields($name)
	{
		$found = /*. (Field[int]) .*/ array();
		foreach($this->fields as $field)
			if( strcasecmp($name, $field->name) == 0 )
				$found[] = $field;
		return $found;
	}
	
	/**
	 * @param string $name
	 * @return string
	 */
	function getFieldValue($name)
	{
		foreach($this->fields as $field)
			if( strcasecmp($name, $field->name) == 0 )
				return $field->value;
		return NULL;
	}
	
	
	/**
	 * Checks the given MIME type can be safely set as a value of the Content-Type
	 * header field. This string is case-insensitive. This function does not
	 * check the type does really exist; it only check the set of characters
	 * allowed by RFC 6838 par. 4.2 and the basic structure type/subtype.
	 * @param string $type Value of the Content-Type header field to check.
	 * @return boolean TRUE if valid.
	 */
	static function isValidContentType($type)
	{
		if( strlen($type) > 200 )
			return FALSE;
		$restricted_name_chars = "[-+!#\$&^_.a-z0-9]";
		return preg_match("/^$restricted_name_chars+\\/$restricted_name_chars+\$/sD", $type) === 1;
	}
	
	/**
	 * Parse "Content-Type" and set "cached_content_type_*" properties.
	 */
	private function parseContentType()
	{
		if( $this->cached_content_type_type !== NULL )
			return;
		$this->cached_content_type_type = "text/plain";
		$this->cached_content_type_charset = NULL;
		$this->cached_content_type_boundary = NULL;
		$this->cached_content_type_name = NULL;
		$ct = $this->getFieldValue("Content-Type");
		if( $ct === NULL )
			return;
		$a = Field::parseWordAndParameters($ct);
//		echo "  >> PARSE $ct: "; var_dump($a);
		if( ! (isset($a["WORD"]) && self::isValidContentType($a["WORD"])) )
			return;
		$this->cached_content_type_type = $a["WORD"];
		if( isset($a["charset"]) && preg_match("/^[-=0-9a-zA-Z.]{1,50}\$/sD", $a["charset"]) === 1 )
			$this->cached_content_type_charset = $a["charset"];
		if( isset($a["boundary"]) && preg_match("/^[ -\xff]{1,99}\$/sD", $a["boundary"]) === 1 )
			$this->cached_content_type_boundary = $a["boundary"];
		if( isset($a["name"]) )
			$this->cached_content_type_name = $a["name"];
	}
	
	/**
	 * Returns the MIME type of the content.
	 * @return string MIME type matching the syntax described in the
	 * isValidContentType() method. A "text/plain" type is returned if the current
	 * type is not valid or not available.
	 */
	function getType()
	{
		$this->parseContentType();
		return $this->cached_content_type_type;
	}
	
	/**
	 * Returns the character set of the content.
	 * @return string Character set of the content, or NULL if not available.
	 */
	function getCharset()
	{
		$this->parseContentType();
		return $this->cached_content_type_charset;
	}
	
	/**
	 * Returns the boundary for this multi-part body.
	 * @return string Boundary string, or NULL if not available.
	 */
	function getBoundary()
	{
		$this->parseContentType();
		return $this->cached_content_type_boundary;
	}
	
	/**
	 * Returns the "name" parameter set for this part.
	 * @return string Nameparameter, UTF-8 encoded, or NULL if not available.
	 */
	function getName()
	{
		$this->parseContentType();
		return $this->cached_content_type_name;
	}
	
	/**
	 * Parse "Content-Disposition" and set "cached_content_disposition_*" properties.
	 */
	private function parseContentDisposition()
	{
		if( $this->cached_content_disposition_attachment !== NULL )
			return;
		$this->cached_content_disposition_attachment = "no";
		$this->cached_content_disposition_filename = NULL;
		$cd = $this->getFieldValue("Content-Disposition");
		if( $cd === NULL )
			return;
		$a = Field::parseWordAndParameters($cd);
//		echo "  >> PARSE $cd: "; var_dump($a);
		if( ! isset($a["WORD"]) )
			return;
		if( $a["WORD"] === "attachment" )
			$this->cached_content_disposition_attachment = "yes";
		else
			$this->cached_content_disposition_attachment = "no";
		
		/*
		 * File name.
		 * Thunderbird encodes the file name as parameter "filename*0*"
		 * with value "utf-8''".rawurlencode($fn) where $fn is the UTF-8 file
		 * name. Very long names are split among several "filename*1*",
		 * "filename*2*", ... parameters. Lets this first with this one:
		 */
		$fn = "";
		$i = 0;
		while( isset($a["filename*$i*"]) ){
			$fn .= $a["filename*$i*"];
			$i++;
		} while( isset($a["filename*$i*"]) );
		if( Strings::startsWith($fn, "utf-8''") ){
			$this->cached_content_disposition_filename
				= Field::toUTF8( rawurldecode( substr($fn, 7) ), "UTF-8");
		
		/*
		 * Fall-back to the standard "filename" parameter if available.
		 */
		} else if( isset($a["filename"]) ){
			$this->cached_content_disposition_filename = $a["filename"];
		}
	}
	
	/**
	 * Tells if this part is an attachment.
	 * @return boolean True if this part is an attachment.
	 */
	function isAttachment()
	{
		$this->parseContentDisposition();
		return $this->cached_content_disposition_attachment === "yes";
	}
	
	/**
	 * Returns the file name of this part.
	 * <p><b>BEWARE:</b> although properly UTF-8 encoded, this string can be
	 * empty "", may contain arbitrary characters including controls, may contain
	 * arbitrary paths or special file names ("NUL", "C:\\NUL", ".", "..", etc.)
	 * or forbidden characters for the current file system ("C:\\???").
	 * @return string File name of this part, UTF-8 encoded, or NULL if not
	 * available.
	 */
	function getFilename()
	{
		$this->parseContentDisposition();
		return $this->cached_content_disposition_filename;
	}
	
	/**
	 * Returns the content ID of this part. See RFC 2392 AND the associated errata.
	 * @return string Value of the content ID, UTF-8 encoded. No syntax check is
	 * performed.
	 */
	function getContentID()
	{
		$cid = $this->getFieldValue("Content-ID");
		if( $cid === NULL )
			return NULL;
		$cid = Field::toUTF8(trim(trim($cid), "<>"), "UTF-8");
		if( strlen($cid) > 0 )
			return $cid;
		else
			return NULL;
	}
	
	/**
	 * Returns the content encoding of this part.
	 * @return string One of the ENCODING_* constants. If not available, the 8bit
	 * encoding is assumed.
	 */
	function getEncoding()
	{
		$e = $this->getFieldValue("Content-Transfer-Encoding");
		$e = strtolower( trim($e) );
		if( $e === self::ENCODING_7BIT || $e === self::ENCODING_BASE64
		|| $e === self::ENCODING_QUOTED_PRINTABLE )
			return $e;
		else
			return self::ENCODING_8BIT;
	}
	
	/**
	 * Returns the subject, possibly empty if not available.
	 * @return string Subject, UTF-8 encoded. ASCII control codes
	 * are removed; improperly encoded characters are removed.
	 */
	function getSubject()
	{
		return Field::decodeWords( trim( $this->getFieldValue("Subject") ) );
	}
	
	/**
	 * Returns the date.
	 * @return DateTimeTZ Parsed date, or NULL if missing or cannot parse.
	 */
	function getDate()
	{
		$d = $this->getFieldValue("Date");
		if( $d === NULL )
			return NULL;
		$WS = "[ \t]";
		if( preg_match(
			"/^$WS*([A-Za-z]{3}$WS*,)?$WS*"
			. "([0-9]{1,2})$WS+"
			. "([A-Za-z]{3})$WS+"
			. "([0-9]{2}|[0-9]{4})$WS+"
			. "([0-9]{2}:[0-9]{2}(:[0-9]{2})?)"
			. "($WS+[-+][0-9]{4})?"
			. "($WS+[^ \t]+)?/sD"
			, $d, $matches_mixed) !== 1 )
			return NULL;
//		var_dump($matches_mixed);
		$matches = cast("string[int]", $matches_mixed);
		$day = $matches[2];
		$month_name = $matches[3];
		$year = $matches[4];
		if( strlen($year) == 2 )
			$year = "19$year";
		$hms = $matches[5];
		$tz_offset = isset($matches[7])? trim($matches[7]) : "";
		$tz_name = isset($matches[8])? trim($matches[8]) : "";
		$month_mixed = array_search(strtoupper($month_name),
			array("JAN", "FEB", "MAR", "APR", "MAY", "JUN", "JUL", "AUG",
				"SEP", "OCT", "NOV", "DEC"));
		if( $month_mixed === FALSE )
			return NULL;
		$month = sprintf("%02d", (int) $month_mixed + 1);
		if( empty($tz_offset) ){
			if( empty($tz_name) ){
				$tz_offset = "+0000";
			} else {
				$tzs = array(
						// RFC 822:
						"UT" => "+0000",   "GMT" => "+0000",
						"EST" => "-0500",  "EDT" => "-0400",
						"CST" => "-0600",  "CDT" => "-0500",
						"MST" => "-0700",  "MDT" => "-0600",
						"PST" => "-0800",  "PDT" => "-0700",

						// Other common TZ names:
						"UTC" => "+0000", "CET" => "+0100", "CEST" => "+0200"
					);
				$tz_name = strtoupper($tz_name);
				if( isset($tzs[$tz_name]) )
					$tz_offset = $tzs[$tz_name];
				else
					$tz_offset = "+0000";
			}
		}
		$iso = "$year-$month-$day" . "T$hms"
			. substr($tz_offset, 0, 3) . ":" . substr($tz_offset, 3);
		try {
			return DateTimeTZ::parse($iso);
		}
		catch(InvalidArgumentException $e){
			return NULL;
		}
		catch(OutOfRangeException $e){
			return NULL;
		}
	}
	
	/**
	 * Writes header lines in RFC 822 format. Lines are terminated by a single
	 * new-line character "\n".
	 * @param OutputStream $out
	 * @return void
	 * @throws IOException
	 */
	function write($out)
	{
		foreach($this->fields as $field)
			$out->writeBytes($field->__toString());
	}
	
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
	
}
