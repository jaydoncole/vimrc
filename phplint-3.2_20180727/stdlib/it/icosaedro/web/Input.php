<?php

namespace it\icosaedro\web;

require_once __DIR__ . "/../../../all.php";

use RuntimeException;
use it\icosaedro\utils\UTF8;


/**
 * HTTP input parameter acquisition and sanitization.
 * Returned strings are always properly UTF-8 encoded.
 * @author Umberto Salsi <salsi@icosaedro.it>
 * @version $Date: 2018/03/16 07:25:08 $
 */
class Input {
	
	/**
	 * Tells whether the given input parameter is available.
	 * @param string $name
	 */
	static function isAvailable($name)
	{
		return isset($_POST[$name]) || isset($_GET[$name]);
	}
	
	
	/**
	 * Sanitizes the subject string and returns a textual, UTF-8 encoded
	 * result. Invalid UTF-8 codes and sequences are replaced by the Unicode
	 * replacement character. All the ASCII control characters are silently
	 * removed but the tabulator and the line feed.
	 * @param string $s Subject string.
	 * @return string Sanitized single line, UTF-8 encoded string.
	 */
	static function sanitizeText($s)
	{
		// Remove invalid UTF-8 codes and sequences:
		$s = UTF8::sanitize($s);
		// Removes ASCII control chars except tabulator and line feed:
		$s = preg_replace("/[\\x00-\\x08\\x0B-\\x1F\\x7F]/", "", $s);
		return $s;
	}
	
	
	/**
	 * Sanitizes the subject string and returns a single line, UTF-8 encoded
	 * result. Invalid UTF-8 codes and sequences are replaced by the Unicode
	 * replacement character. All the ASCII control characters are silently
	 * removed. Finally, the string is trimmed.
	 * @param string $s
	 * @return string
	 */
	static function sanitizeLine($s)
	{
		// Remove invalid UTF-8 codes and sequences:
		$s = UTF8::sanitize($s);
		// Removes ASCII control chars:
		$s = preg_replace("/[\\x00-\\x1F\\x7F]/", "", $s);
		$s = trim($s);
		return $s;
	}
	
	
	/**
	 * Returns a POST or GET multi-line string sanitized using the
	 * [@link self::sanitizeText()} method.
	 * @param string $name Name of the GET or POST parameter.
	 * @param string $def Default value to blindly return if the input parameter
	 * is missing.
	 * @return string Sanitized text, UTF-8 encoded.
	 * @throws RuntimeException The parameter is missing from the HTTP request
	 * and no default value is specified.
	 */
	static function getText($name, $def = NULL)
	{
		if( isset($_POST[$name]) )
			$s = (string) $_POST[$name];
		else if( isset($_GET[$name]) )
			$s = (string) $_GET[$name];
		else if( func_num_args() > 1 )
			return $def;
		else
			throw new RuntimeException("missing POST and GET parameter and no default value set for $name");
		return self::sanitizeText($s);
	}
	

	/**
	 * Returns a POST or GET single line string sanitized using the
	 * {@link self::sanitizeLine()} method.
	 * @param string $name Name of the GET or POST parameter.
	 * @param string $def Default value to return if the input parameter is missing.
	 * @return string Sanitized line, UTF-8 encoded.
	 * @throws RuntimeException The parameter is missing from the HTTP request
	 * and no default value is specified.
	 */
	static function getLine($name, $def = NULL)
	{
		if( isset($_POST[$name]) )
			$s = (string) $_POST[$name];
		else if( isset($_GET[$name]) )
			$s = (string) $_GET[$name];
		else if( func_num_args() > 1 )
			return $def;
		else
			throw new RuntimeException("missing POST and GET parameter and no default value set for $name");
		return self::sanitizeLine($s);
	}
	
}