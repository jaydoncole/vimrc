<?php

namespace it\icosaedro\io\texts;
require_once __DIR__ . "/../../../../autoload.php";
use it\icosaedro\utils\UString;

/**
 * UTF-8 text encoder/decoder for UString strings. Uses the conversion methods
 * of UString and then does not depends on any additional extension.
 * 
 * <p><i>Encoding</i> according to this class means translating an Unicode
 * UString into its UTF-8 binary representation.
 * 
 * <p><i>Decoding</i> is the reverse process to translate a sequence of bytes
 * into UString. Decoding may fail when a sequence of bytes does not match any
 * valid UTF-8 sequence; these invalid bytes are replaced with the Unicode
 * replacement character U+FFFD "&#xfffd;".
 * 
 * <p>This example shows how an UTF-8 encoding object can be created and how
 * UString strings can be converted from and to binary UTF-8 data:
 * <blockquote><pre>
 * use it\icosaedro\io\texts\EncodingUTF8;
 * $enc = new EncodingUTF8();
 * // Converting string of bytes "BinaryDataHereSupposedlyUTF8" to Unicode UString:
 * $s = $enc-&gt;decode("BinaryDataHereSupposedlyUTF8");
 * // Converting UString back to bytes ISO-8859-1:
 * echo $enc-&gt;encode($s); // displays "BinaryDataHereSupposedlyUTF8"
 * </pre></blockquote>
 * 
 * <p>The Reader and Writer classes use this encoder/decoder when the ASCII
 * encoding is specified.
 * 
 * @author Umberto Salsi <salsi@icosaedro.it>
 * @version $Date: 2015/03/11 06:08:22 $
 */
class EncodingUTF8 extends EncodingInterface {

	/**
	 * Returns encoding name implemented by this object.
	 * @return string Encoding name implemented by this object.
	 */
	function __toString() {
		return "UTF-8";
	}
	
	
	function normalizedName() {
		return "UTF8";
	}


	/**
	 * @param UString $s
	 * @return string
	 */
	function encode($s){
		if( $s === NULL )
			return NULL;
		else
			return $s->toUTF8();
	}


	/**
	 * @param string $bytes
	 * @return UString
	 */
	function decode($bytes) {
		return UString::fromUTF8($bytes);
	}
	
}
