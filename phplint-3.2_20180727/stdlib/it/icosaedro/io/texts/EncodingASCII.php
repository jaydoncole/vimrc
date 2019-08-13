<?php

namespace it\icosaedro\io\texts;
require_once __DIR__ . "/../../../../autoload.php";
use it\icosaedro\utils\UString;

/**
 * ASCII text encoder/decoder for UString strings. For those that do not have
 * the multi-byte nor the iconv extension installed or are simply not interested
 * to the internationalization issues.
 * 
 * <p>Decoding a stream of bytes, it assumes 1 byte per character; non ASCII
 * codes are translated to the Unicode replacement symbol "&#xfffd;".
 * 
 * <p>Encoding a stream of bytes, non ASCII codepoints are translated to the
 * question mark "?".
 * 
 * <p>Example:
 * <blockquote><pre>
 * use it\icosaedro\io\texts\EncodingASCII;
 * $enc = new EncodingASCII();
 * // Converting string of bytes "Hello, world!" to Unicode UString:
 * $s = $enc-&gt;decode("Hello, world!");
 * // Converting UString back to bytes:
 * echo $enc-&gt;encode($s); // displays "Hello, world!"
 * </pre></blockquote>
 * 
 * <p>The Reader and Writer classes use this encoder/decoder when the ASCII
 * encoding is specified.
 * 
 * @author Umberto Salsi <salsi@icosaedro.it>
 * @version $Date: 2015/03/11 06:08:22 $
 */
class EncodingASCII extends EncodingInterface {

	/**
	 * @return string
	 */
	function __toString() {
		return "ASCII";
	}
	
	
	function normalizedName() {
		return "ASCII";
	}


	/**
	 * @param UString $s
	 * @return string
	 */
	function encode($s){
		if( $s === NULL )
			return NULL;
		else
			return $s->toASCII();
	}


	/**
	 * @param string $bytes
	 * @return UString
	 */
	function decode($bytes) {
		return UString::fromASCII($bytes);
	}
	
}
