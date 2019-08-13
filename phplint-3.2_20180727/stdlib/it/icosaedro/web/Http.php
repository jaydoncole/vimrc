<?php

namespace it\icosaedro\web;

require_once __DIR__ . "/../../../all.php";

use ErrorException;
use RuntimeException;
use InvalidArgumentException;

/**
 * HTTP routines.
 * @author Umberto Salsi <salsi@icosaedro.it>
 * @version $Date: 2018/05/15 04:36:45 $
 */
class Http {
	
	/**
	 * Tells if the current connection is safe for exchanging private data.
	 * Secure connections are: CLI SAPI, HTTPS.
	 * @return boolean
	 */
	static function isSecureConnection()
	{
		return PHP_SAPI === "cli" || isset($_SERVER["HTTPS"]);
	}
	
	/**
	 * Sends the "no-store" cache directive. This directive seems to be mostly
	 * ignored by recent browsers, and they keep silently serving the same cached
	 * page again as the user navigates its browser's history. See the no-store
	 * cache directive instead.
	 */
	static function headerCacheControlNoCache()
	{
		header("Cache-Control: no-cache");
	}
	
	/**
	 * Send the "no-store" cache directive. If the user tries navigating the
	 * browser history, the browser displays a warning so the user is aware
	 * it is doing something the web site does not recommends to do. Web sites
	 * where pages are all dynamically generated shoud send this directive.
	 */
	static function headerCacheControlNoStore()
	{
		header("Cache-Control: no-store");
	}
	
	
	static function headerContentTypeHtmlUTF8()
	{
		header("Content-Type: text/html; charset=\"UTF-8\"");
	}
	
	
	public static $REASONS = [
		100 => "Continue",
		101 => "Switching Protocols",
		200 => "OK",
		201 => "Created",
		202 => "Accepted",
		203 => "Non-Authoritative Information",
		204 => "No Content",
		205 => "Reset Content",
		206 => "Partial Content",
		300 => "Multiple Choices",
		301 => "Moved Permanently",
		302 => "Found",
		303 => "See Other",
		304 => "Not Modified",
		305 => "Use Proxy",
		307 => "Temporary Redirect",
		400 => "Bad Request",
		401 => "Unauthorized",
		402 => "Payment Required",
		403 => "Forbidden",
		404 => "Not Found",
		405 => "Method Not Allowed",
		406 => "Not Acceptable",
		407 => "Proxy Authentication Required",
		408 => "Request Time-out",
		409 => "Conflict",
		410 => "Gone",
		411 => "Length Required",
		412 => "Precondition Failed",
		413 => "Request Entity Too Large",
		414 => "Request-URI Too Large",
		415 => "Unsupported Media Type",
		416 => "Requested range not satisfiable",
		417 => "Expectation Failed",
		500 => "Internal Server Error",
		501 => "Not Implemented",
		502 => "Bad Gateway",
		503 => "Service Unavailable",
		504 => "Gateway Time-out",
		505 => "HTTP Version not supported"
	];
	
	// Selected sub-set of common status codes:
	const
		STATUS_OK = 200,
		STATUS_NO_CONTENT = 204,
		STATUS_MOVED_PERMANENTLY = 301,
		STATUS_FOUND = 302,
		STATUS_SEE_OTHER = 303,
		STATUS_NOT_MODIFIED = 304,
		STATUS_TEMPORARY_REDIRECT = 307,
		STATUS_BAD_REQUEST = 400,
		STATUS_FORBIDDEN = 403,
		STATUS_NOT_FOUND = 404,
		STATUS_METHOD_NOT_ALLOWED = 405,
		STATUS_INTERNAL_SERVER_ERROR = 500,
		STATUS_NOT_IMPLEMENTED = 501,
		STATUS_SERVICE_UNAVAILABLE = 503;
	
	
	/**
	 * Sends an HTTP status code.
	 * @param int $code HTTP status code; see STATUS_* constants for a selected
	 * subset of the most common codes.
	 * @param string $reason Description of the status code. NULL to set the
	 * default defined reason according to RFC 2616. Non-empty reason is
	 * mandatory for custom status codes.
	 * @throws InvalidArgumentException Code out of the range [100,999].
	 * Using custom status code with empty reason.
	 */
	static function headerStatus($code, $reason = NULL)
	{
		if( !(100 <= $code && $code <= 999) )
			throw new InvalidArgumentException("invalid HTTP status code range: $code");
		if( strlen($reason) == 0 ){
			if( array_key_exists($code, self::$REASONS) )
				$reason = self::$REASONS[$code];
			else
				throw new InvalidArgumentException("empty reason");
		}
		header($_SERVER["SERVER_PROTOCOL"] . "/1.1 $code $reason");
	}
	
	
	/**
	 * Sends a Not Found status code and a possble alternative URL.
	 * @param string $alternative_url Alternative URL if not empty.
	 */
	static function headerNotFound($alternative_url = NULL)
	{
		self::headerStatus(self::STATUS_NOT_FOUND);
		header("Cache-Control: no-cache");
		if( strlen($alternative_url) > 0 && $_SERVER['REQUEST_METHOD'] !== "HEAD" ){
			self::headerContentTypeHtmlUTF8();
			header("Content-Type: text/html; charset=UTF-8");
			echo "<html><head><title>404 Not Found</title></head><body><h1>Resource not found</h1>\n",
				"Suggested alternative URL:\n",
				"<pre>\n\n</pre>\n",
				"<center><a href=\"$alternative_url\" target=_top>$alternative_url</a></center>\n",
				"</body></html>\n";
		}
	}


	/**
	 * Sends a Moved Permanently header with new URL.
	 * @param string $new_url
	 */
	static function headerMovedPermanently($new_url)
	{
		header("HTTP/1.1 301 Moved Permanently");
		self::headerStatus(self::STATUS_MOVED_PERMANENTLY);
		header("Location: $new_url");
		header("Cache-Control: no-cache");
		if( $_SERVER['REQUEST_METHOD'] !== "HEAD" ){
			self::headerContentTypeHtmlUTF8();
			echo "<html><head><title>301 Moved Permanently</title></head><body>Resource moved to: <a href='$new_url'>$new_url</a>.</body></html>\n";
		}
	}


	/**
	 * Sends the Last-Modified header and managed a possible If-Modified-Since
	 * conditional request. The Last-Modified date is the modification time of
	 * the $_SERVER['SCRIPT_FILENAME'] file. If a conditional request is
	 * received, compares the remotely cached date versus the current modification
	 * date; if they match, a 304 status response (Not Modified) is sent and the
	 * script exit. This function can be used by scripts that basically behaves
	 * like static pages.
	 * @return void Does not return if the conditional request contains a date
	 * equal or greater than the last modified date of the script.
	 */
	static function headerLastModified()
	{
		// Retrieve last modified date from script file:
		try {
			$modified = filemtime( $_SERVER['SCRIPT_FILENAME'] );
		}
		catch(ErrorException $e){
			throw new RuntimeException($e->getMessage(), $e->getCode(), $e);
		}

		// Recommended date format as per RFC 2616, par. 3.3:
		$modified_str = gmdate("D, d M Y H:i:s", $modified) ." GMT";
		header("Last-Modified: $modified_str");

		// Retrieves If-Modified-Since from request:
		if( ! isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) )
			return;
		$if_modified_str = $_SERVER['HTTP_IF_MODIFIED_SINCE'];

		// If the remotely cached date equals our, simply do not execute script:
		if( strcmp($if_modified_str, $modified_str) == 0 ){
			self::headerStatus(self::STATUS_NOT_MODIFIED);
			exit();
		}

		// Date strings differ, possibly because of the different format.
		// Decode and compare timestamps:
		$if_modified = strtotime($if_modified_str);
		if( $if_modified === FALSE ){
			Log::warning("failed parsing If-Modified-Since: $if_modified_str");
			return;
		} else if( $modified <= $if_modified ){
			self::headerStatus(self::STATUS_NOT_MODIFIED);
			exit();
		}
	}
	
}
