<?php

namespace it\icosaedro\io\texts;
require_once __DIR__ . "/../../../../autoload.php";
use it\icosaedro\utils\UString;
use InvalidArgumentException;
use RuntimeException;
use ErrorException;

/*. require_module 'iconv'; .*/
/*. require_module 'mbstring'; .*/

/**
 * Text encoding translator. This class is based on the mbstring multi-byte or the
 * iconv PHP extension, detected in this order. At least one of these extensions
 * must be available in order to use this class. The Reader and Writer classes,
 * for example, use an encoder object of this type to read and write texts.
 * 
 * <p><i>Encoding</i> according to this class means translating a UString string
 * into a sequence of bytes. Encoding fails when a codepoint has no equivalent
 * representation in the target encoding; these codepoints are silently replaced
 * with a "?".
 * 
 * <p><i>Decoding</i> is the reverse process to translate a sequence of bytes
 * into UString. Decoding fails when a sequence of bytes does not match any valid
 * encoded codepoint; these invalid bytes are replaced with the Unicode replacement
 * character U+FFFD "&#xfffd;".
 * 
 * <p>This example shows how an encoding object can be created for a given target
 * encoding, and how UString strings can be converted from and to binary data:
 * <blockquote><pre>
 * use it\icosaedro\io\texts\EncodingGeneric;
 * $enc = new EncodingGeneric("ISO-8859-1");
 * // Converting string of bytes "BinaryDataHereSupposedlyISO88591" to Unicode UString:
 * $s = $enc-&gt;decode("BinaryDataHereSupposedlyISO88591");
 * // Converting UString back to bytes ISO-8859-1:
 * echo $enc-&gt;encode($s); // displays "BinaryDataHereSupposedlyISO88591"
 * </pre></blockquote>
 * 
 * @author Umberto Salsi <salsi@icosaedro.it>
 * @version $Date: 2016/03/07 10:39:42 $
 */
class EncodingGeneric extends EncodingInterface {

	/**
	 * Requested encoding.
	 * @var string
	 */
	private $encoding;
	
	/**
	 * @var string
	 */
	private $normalized;
	private $enable_mb = FALSE;
	private $enable_iconv = FALSE;
	private $require_2 = FALSE;
	private $require_4 = FALSE;
	
	/**
	 * Replacement charecter (target encoding). Codepoints that cannot be encoded
	 * are replaced by this.
	 * @var string
	 */
	private $replacement_character_encoded;


	/**
	 * Returns the encoding name implemented by this object.
	 * @return string Encoding name implemented by this object.
	 */
	function __toString(){
		return $this->encoding;
	}
	
	/**
	 * Returns the name of this encoding normalized with only capital latin
	 * letters and digits, any other character removed. For example, if the
	 * actual name is ISO-8859-1 or ISO8859_1 or ISO.8859.1, it will be
	 * returned as ISO88591.
	 * @return string
	 */
	function normalizedName() {
		return $this->normalized;
	}


	/**
	 * Low-level encoding routine.
	 * @param string $utf8 Source string (UTF-8).
	 * @return string Encoded bytes. When iconv is used, it may fail returning
	 * NULL either because the requested encoding is unknown or some unconvertible
	 * code is found; caller must cope with this.
	 * @throws RuntimeException Unknown or unsupported from/to encodings.
	 */
	private function encode2($utf8)
	{
		$from = "UTF-8";
		$to = $this->encoding;
		
		if( $this->enable_mb ) {
			//$sub = ini_set("mbstring.substitute_character", "none");
			//$sub = ini_set("mbstring.substitute_character", "long"); // --> U+HHHH
			//$sub = ini_set("mbstring.substitute_character", "65533");
			$sub = ini_set("mbstring.substitute_character", "".ord("?"));
			$str = ini_set("mbstring.strict_detection", "1");
			try {
				$res = mb_convert_encoding($utf8, $to, $from);
			}
			catch(ErrorException $e){
				throw new RuntimeException($e->getMessage());
			}
			finally {
				// restore mbstring settings:
				ini_set("mbstring.substitute_character", $sub);
				ini_set("mbstring.strict_detection", $str);
			}
			return $res;

		} else if ( $this->enable_iconv ) {
			try {
				$res = iconv($from, $to, $utf8);
				//$res = iconv($from, "$to//IGNORE", $utf8);
				//$res = iconv($from, "$to//TRANSLIT", $utf8);
			}
			catch(\ErrorException $e){
				//throw new RuntimeException("encoding to $to failed: " . $e->getMessage());
				//$res = $this->replacement; // FIXME: rejects invalid U+d813 also adding "//IGNORE"
				return NULL;
			}
			if( $res === FALSE )
				// should never happen when E_NOTICE mapped to ErrorException
				throw new RuntimeException();
			return $res;

		} else {
			// FIXME: set an ASCII fallback?
			throw new RuntimeException("both mbstring and iconv extension missing or disabled");
		}
	}


	/**
	 * Returns the encoded bytes.
	 * @param UString $s String to encode.
	 * @return string Encoded bytes.
	 */
	function encode($s){
		if( $s === NULL )
			return NULL;
		$res = $this->encode2($s->toUTF8());
		if( $res === NULL ){
			// iconv only: failed to encode some char, can't know exactly where.
			// instead to return an empty string, half split and applies
			// recursively to both halves. this might break some sequence...
			$len = $s->length();
			if( $len == 1 )
				$res = $this->replacement_character_encoded;
			else
				$res = $this->encode($s->substring(0, $len >> 1))
				. $this->encode($s->substring($len >> 1, $len));
		}
		return $res;
	}


	/**
	 * Low-level decoder.
	 * @param string $bytes Bytes to decode.
	 * @return string Decoded string (UTF-8).
	 * @throws RuntimeException Unknown or unsupported from/to encodings.
	 */
	private function decode2($bytes)
	{
		if( strlen($bytes) == 0 )
			return "";
		$from = $this->encoding;
		$to = "UTF-8";
		if( $this->enable_mb ) {
			// FIXME: check beyond Unicode BMP
			//$sub = ini_set("mbstring.substitute_character", "none");
			//$sub = ini_set("mbstring.substitute_character", "long"); // --> U+HHHH
			//$sub = ini_set("mbstring.substitute_character", "?");
			$sub = ini_set("mbstring.substitute_character", "".UString::REPLACEMENT_CHARACTER_CODEPOINT);
			$str = ini_set("mbstring.strict_detection", "1");
			try {
				//echo "(" . __METHOD__ . ": $from:", rawurlencode($bytes), " --> $to:", rawurlencode($res), ")\n";
				$res = mb_convert_encoding($bytes, $to, $from);
			}
			catch(ErrorException $e){
				throw new RuntimeException($e->getMessage());
			}
			finally {
				// restore mbstring settings:
				ini_set("mbstring.substitute_character", $sub);
				ini_set("mbstring.strict_detection", $str);
			}
			return $res;

		} else if ( $this->enable_iconv ) {
			try {
				$res = iconv($from, "$to", $bytes);
				//$res = iconv($from, "$to//IGNORE", $bytes);
				//$res = iconv($from, "$to//TRANSLIT", $bytes);
			}
			catch(\ErrorException $e){
				//throw new RuntimeException("encoding conversion from $from to $to failed: " . $e->getMessage());
				//$res = $this->replacement;
				$len = strlen($bytes);
				if( $this->require_4 and $len <= 4
				or $this->require_2 and $len <= 2
				or $len < 2 )
					return UString::REPLACEMENT_CHARACTER_UTF8;
				else
					// note that $len is even - see self::decode()
					return $this->decode2(substr($bytes, 0, $len >> 1))
					. $this->decode2(substr($bytes, $len >> 1, $len >> 1));
			}
			if( $res === FALSE )
				// should never happen when E_NOTICE maps to ErrorException
				throw new RuntimeException();
			return $res;

		} else {
			throw new RuntimeException("both mbstring and iconv extension missing or disabled");
		}
	}


	/**
	 * Returns the decoded string.
	 * @param string $bytes Encoded bytes.
	 * @return UString Decoded string.
	 */
	function decode($bytes){
		if( $bytes === NULL )
			return NULL;
		// iconv() raises E_NOTICE (and then ErrorException with errors.php)
		// if odd len when multiple of 2 or 4 bytes required. Workaround:
		if( ! $this->enable_mb and $this->enable_iconv ){
			if( $this->require_4 ){
				if( strlen($bytes) % 4 != 0 )
					$bytes = substr($bytes, 0, strlen($bytes) & (~3))
						. $this->replacement_character_encoded;
			} else if( $this->require_2 ){
				if( strlen($bytes) % 2 != 0 )
					$bytes = substr($bytes, 0, strlen($bytes) & (~1))
						. $this->replacement_character_encoded;
			}
		}
		return UString::fromUTF8( $this->decode2($bytes) );
	}


	/**
	 * Creates new text encoding translator.
	 * The mbstring and iconv extensions can be disabled selectively for testing
	 * purpouses; applications should leave both enabled (default).
	 * @param string $encoding File system encoding, for example "UTF-8".
	 * @param boolean $enable_mb If to enable the mbstring extension.
	 * @param boolean $enable_iconv If to enable the iconv extension.
	 * @return void
	 * @throws InvalidArgumentException Unknown encoding.
	 */
	function __construct($encoding, $enable_mb = true, $enable_iconv = true){
		$this->encoding = $encoding;
		$this->normalized = self::normalize($encoding);
		$this->enable_mb = $enable_mb and function_exists('mb_convert_encoding');
		$this->enable_iconv = $enable_iconv and function_exists('iconv');
		$this->require_2 = in_array($this->normalized, array(
			"UCS2", "UCS2BE", "UCS2LE", "UTF16", "UTF16BE", "UTF16LE"
		));
		$this->require_4 = in_array($this->normalized, array(
			"UCS4", "UCS4BE", "UCS4LE"
		));
		
		// Check if this encoding is supported by trying to encode a digit,
		// a symbol common to all the encodings:
		try {
			$z = $this->encode2("0");
		}
		catch(RuntimeException $e){
			throw new InvalidArgumentException($e->getMessage());
		}
		// with iconv, self::encode2() returns NULL if it fails - workaround:
		if( $z === NULL )
			throw new InvalidArgumentException("unsupported encoding $encoding");
		
		// Encode the replacement string, or use "?" if fails:
		try {
			$this->replacement_character_encoded = $this->encode2(UString::REPLACEMENT_CHARACTER_UTF8);
			// with iconv, self::encode2() returns NULL if it fails - workaround:
			if( $this->replacement_character_encoded === NULL )
				$this->replacement_character_encoded = "?";
		}
		catch(RuntimeException $e){
			throw new InvalidArgumentException($e->getMessage());
		}
	}
	
	// mb_list_encodings():
	// 7bit 8bit ASCII ArmSCII-8 BASE64 BIG-5
	// CP50220 CP50220raw CP50221 CP50222 CP51932 CP850 CP866 CP932 CP936
	// CP950 EUC-CN EUC-JP EUC-JP-2004 EUC-KR EUC-TW GB18030 HTML-ENTITIES HZ
	// ISO-2022-JP ISO-2022-JP-2004 ISO-2022-JP-MOBILE#KDDI ISO-2022-JP-MS
	// ISO-2022-KR ISO-8859-1 ISO-8859-10 ISO-8859-13 ISO-8859-14 ISO-8859-15
	// ISO-8859-16 ISO-8859-2 ISO-8859-3 ISO-8859-4 ISO-8859-5 ISO-8859-6
	// ISO-8859-7 ISO-8859-8 ISO-8859-9 JIS JIS-ms KOI8-R KOI8-U
	// Quoted-Printable SJIS SJIS-2004 SJIS-Mobile#DOCOMO SJIS-Mobile#KDDI
	// SJIS-Mobile#SOFTBANK SJIS-mac SJIS-win
	// UCS-2 UCS-2BE UCS-2LE UCS-4 UCS-4BE UCS-4LE UHC UTF-16 UTF-16BE UTF-16LE
	// UTF-32 UTF-32BE UTF-32LE UTF-7 UTF-8 UTF-8-Mobile#DOCOMO
	// UTF-8-Mobile#KDDI-A UTF-8-Mobile#KDDI-B UTF-8-Mobile#SOFTBANK UTF7-IMAP
	// UUENCODE Windows-1251 Windows-1252 Windows-1254
	// auto byte2be byte2le byte4be byte4le eucJP-win pass wchar

}
