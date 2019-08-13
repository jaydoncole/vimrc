<?php

namespace it\icosaedro\web;

require_once __DIR__ . "/../../../all.php";

/*.
	require_module 'core';
	require_module 'pcre';
	require_module 'file';
.*/

use RuntimeException;
use ErrorException;
use it\icosaedro\web\Log;

/**
 * Routines to send a file to the browser.
 * @author Umberto Salsi <salsi@icosaedro.it>
 * @copyright Copyright 2018 by icosaedro.it di Umberto Salsi
 * @version $Date: 2018/07/27 09:57:38 $
 */
class FileDownload {
	
	/**
	 * Sends to the browser the headers to send a generic content.
	 * @param string $name Suggested name of the file, UTF-8 encoded.
	 * @param string $type MIME type. For "text/plain" and "text/html" PHP
	 * automatically adds the default_charset as specified in the configuration
	 * file; you may want to override that setting by specifying your own
	 * encoding, for example "text/plain; charset=UTF-8".
	 * @param boolean $isAttachment True if the browser should prompt the user
	 * to save the content locally, rather than display it immediately.
	 */
	static function sendHeaders($name, $type, $isAttachment)
	{
		if(array_key_exists("HTTP_USER_AGENT", $_SERVER) )
			$agent = $_SERVER["HTTP_USER_AGENT"];
		else
			$agent = "USER_AGENT_NOT_AVAILABLE";
		
		# Replace ASCII control chars in name:
		$name = preg_replace("/[\\x00-\\x1f\\x7f]/", "_", $name);
		
		# Replace OS-specific reserved or forbidden chars:
		if( is_int(strpos($agent, "Windows")) )
			$name = preg_replace("/[:\\x5c\\/*?\"<>|]/", "_", $name);
		else
			$name = preg_replace("/[\\/]/", "_", $name);
		
		$name = trim($name);
		
		// Leave the browser to cope with possibly evil, OS-specific file names
		// like ".", ".." or even "COM1", "NUL", "NUL." etc.
		
		if( is_int(strpos($agent, "MSIE")) ){  // MSIE <= 6
			# Non-standard URL encoding of the UTF-8 file name:
			$filename = "filename=" . rawurlencode($name);

		} else { // Any other modern browser.
			# RFC 2616 ASCII-only encoding:
			$name_ascii = preg_replace("/[\\x80-\\xff]/", "_", $name);
			$name_ascii = (string) str_replace("\\", "\\\\", $name_ascii);
			$name_ascii = (string) str_replace("\"", "\\\"", $name_ascii);
			$filename = "filename=\"$name_ascii\"";
			# Also add RFC 2231, 5987, 6266 and leave the browser choose:
			$filename .= "; filename*=UTF-8''" . rawurlencode($name);
		}
		
		header("Content-Type: $type");
		header("Content-Disposition: "
			. ($isAttachment? "attachment" : "inline")
			. "; $filename");
	}
	
	
	/**
	 * Sends the raw content of a file. A previous call to sendHeaders() is
	 * recommended if not mandatory to properly set the type and suggested
	 * file name.
	 * @param string $path Path of the file on the server. This name has nothing
	 * to do with the name suggested to the user.
	 * @throws ErrorException Failed access to the file system.
	 */
	static function sendFile($path)
	{
		$size = filesize($path);
		header("Content-Length: $size");
		$f = fopen($path, "rb");
		while(TRUE){
			if( feof($f) )
				break;
			$chunk = fread($f, 4196);
			if( !( is_string($chunk) && strlen($chunk) > 0 ) )
				break;
			echo $chunk;
		}
		fclose($f);
	}
	
	
	/**
	 * Returns the extension of the file name. The extension is anything that
	 * follows the last dot in the path and containing latin letters, digits
	 * and underscore.
	 * @param string $path
	 * @return string Filename extension converted to uppercase letters, or the
	 * empty string if no suitable extension is found.
	 */
	static function getFilenameExtension($path)
	{
		$dot = strrpos($path, ".");
		if( $dot === FALSE )
			return "";
		$ext = substr($path, $dot + 1);
		if( preg_match("/[a-zA-Z0-9_]+\$/sD", $ext) != 1 )
			return "";
		return strtolower($ext);
	}
	
	
	/**
	 * Parses the Apache MIME types file.
	 * @param string $path
	 * @return string[string] Associative array that maps lower-case file name
	 * extensions to their corresponding MIME type.
	 * @throws ErrorException
	 */
	static function parseMimeTypesFile($path)
	{
		$f = fopen($path, "r");
		$types = /*. (string[string]) .*/ array();
		while( ($line = fgets($f)) !== FALSE ){
			$line = trim($line);
			if( strlen($line) == 0 || $line[0] === "#" )
				continue;
			$line = (string) str_replace("\t", " ", $line);
			$line = preg_replace("/ {2,}/", " ", $line);
			$a = explode(" ", $line);
			for($i = count($a) - 1; $i >= 1; $i--){
				$ext = strtolower($a[$i]);
				// There are collisions only for these extensions: wmz, sub.
				//if(array_key_exists($ext, $types) )
				//	error_log("multiple extension $ext: " . $a[0] . ", " . $types[$ext]);
				$types[$ext] = $a[0];
			}
		}
		fclose($f);
		return $types;
	}
	
	
	/**
	 * Default generic MIME type for unknown files.
	 */
	const APPLICATION_OCTET_STREAM = "application/octet-stream";
	
	
	/**
	 * Maps lower-case letters only file name extension to MIME type.
	 * @var string[string]
	 */
	private static $cached_extensions;
	
	/**
	 * Returns a guess for the type of the file based on the given file name
	 * extension.
	 * @param string $ext File name extension, for example "txt".
	 * @return string Corresponding MIME type.
	 * @throws RuntimeException
	 */
	static function getTypeFromExtension($ext)
	{
		if( self::$cached_extensions === NULL ){
			try {
				self::$cached_extensions = self::parseMimeTypesFile(__DIR__ . "/mime-types.txt");
			}
			catch(ErrorException $e){
				throw new RuntimeException($e->getMessage(), 1, $e);
			}
		}
		$ext = strtolower($ext);
		if(array_key_exists($ext, self::$cached_extensions) )
			return self::$cached_extensions[$ext];
		else
			return self::APPLICATION_OCTET_STREAM;
	}
	
	
	/**
	 * Returns a guess for the type of the file based on its filename extension.
	 * Note that this function relies only on the file name extension alone,
	 * then it does not pretend a corresponding file does actually exist.
	 * On the contrary, the standard mime_content_type() does really access the
	 * file with that name, which might not really exist (user's submitted files
	 * should always be saved under some safe, harmful temporary file name).
	 * @param string $filename
	 * @return string Best guessed MIME type of the file.
	 * @throws RuntimeException
	 */
	static function getTypeFromFilename($filename)
	{
		return self::getTypeFromExtension( self::getFilenameExtension($filename) );
	}
	
}
