<?php

namespace it\icosaedro\io\texts;
/*. require_module 'pcre'; require_module 'locale'; .*/
require_once __DIR__ . "/../../../../autoload.php";
use it\icosaedro\utils\UString;
use it\icosaedro\containers\Printable;

/**
 * Text encoders and decoders may implement this interface to translate binary
 * data to and from {@link it\icosaedro\utils\UString} respectively.
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
 * <p>The interface provided by this class is the same as that provided by the
 * more specialized {@link ./CodePageInterface CodePageInterface} which is
 * intended for file names alone. In that case the conversion process is more
 * restrictive, as only exact conversions are allowed handling file names.
 * 
 * <p>The __toString() method returns the original name of the encoding as
 * specified by the application, for example "Utf-8" or "ISO-8859_1".
 * 
 * <p>The normalizedName() method returns the normalized name of the encoding
 * represented by this instance, that is with only capital letters and digits
 * and any other character removed, for example "UTF8" or "ISO88591". Normalized
 * names make easier to compare encoding names that are just the same with only
 * some punctuation character of difference. Hopefully no two different encodings
 * names will differ only by a punctuation character.
 * 
 * @author Umberto Salsi <salsi@icosaedro.it>
 * @version $Date: 2016/01/26 12:26:56 $
 */
abstract class EncodingInterface implements Printable {
	
	/**
	 * Returns the name of this encoding normalized with only capital latin
	 * letters and digits, any other character removed. For example, if the
	 * actual name is ISO-8859-1 or ISO8859_1 or ISO.8859.1, it will be
	 * returned as ISO88591.
	 * @return string
	 */
	abstract function normalizedName();

	/**
	 * Encodes the string to bytes.
	 * @param UString $s String to encode.
	 * @return string Encoded bytes. If the string is NULL, NULL is returned.
	 */
	abstract function encode($s);

	/**
	 * Decodes bytes to a string.
	 * @param string $bytes Bytes to decode.
	 * @return UString Decoded string. If $bytes is NULL, NULL is returned.
	 */
	abstract function decode($bytes);
	
	
	/**
	 * Returns the string normalized as required by the normalizedName()
	 * method. Implementation classes should use this function to retrieve the
	 * normalized encoding name from the encoding name or application submitted
	 * encoding name.
	 * @param string $original
	 */
	public static function normalize($original) {
		return strtoupper(preg_replace("/[^a-zA-Z0-9]/", "", $original));
	}
	
	
	/**
	 * Returns the system locale encoding name.
	 * @return string Name of the system locale encoding, possibly the empty
	 * string, or NULL if system locale not set.
	 */
	public static function getLocaleEncoding()
	{
		/* Extracts codeset part from current LC_CTYPE locale:
		 *
		 *     language[_territory][.codeset][@modifiers]
		 *
		 * where:
		 *
		 * language is the ISO639 code;
		 * territory is the ISO3166 country code;
		 * codeset is the encoding ID, like "ISO-8859-1" or
		 * code page number under Windows;
		 * modifiers are the format modifiers.
		 */
		$ctype = setlocale(LC_CTYPE, 0 );
		if( $ctype === FALSE )
			# No locale set.
			return NULL;
			
		$dot = strpos($ctype, ".");
		if( $dot === FALSE )
			# No codeset part in locale.
			return NULL;
		
		$codeset = substr($ctype, $dot + 1);
		# Remove optional modifies after "@":
		$at = strpos($codeset, "@");
		if( $at !== FALSE )
			$codeset = substr($codeset, 0, $at);
		if ( 'WIN' === substr( PHP_OS, 0, 3 ) ){
			// Windows - translate codeset no. to encoding:
			if( in_array($codeset, array("1251", "1252", "1254"), true) ){
				// The MB extension names these "Windows-*".
				$encoding = "Windows-$codeset";
			} else if( in_array($codeset, array("932", "936",
				"950", "50220", "50221", "50222", "51932", "850", "866"), true) ){
				// The MB extension names these "CP*".
				$encoding = "CP$codeset";
			} else {
				$encoding = "CP$codeset"; // just an attempt
			}
		} else {
			// Unix/Linux/MacOSX
			$encoding = $codeset;
		}
		return $encoding;
	}

}
