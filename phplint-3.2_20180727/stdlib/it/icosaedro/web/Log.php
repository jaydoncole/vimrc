<?php

namespace it\icosaedro\web;

/*. require_module 'core'; .*/

/**
 * Loggin utilities.
 * @author Umberto Salsi <salsi@icosaedro.it>
 * @version $Date: 2018/03/20 14:16:59 $
 */
class Log {
	
	static public $logNotices = TRUE;
	
	/**
	 * Quotes ASCII control characters and "extended" codes above 0x80 using
	 * C-style backslash sequences. Horizontal tabulator (0x09), line feed (0x0A)
	 * and carriage-return (0x0D) are left.
	 * @param string $s
	 * @return string
	 */
	static function escape($s)
	{
		// Escape otherwise invisible conrol chars and extended ASCII:
		$s = addcslashes($s, "\x00..\x08\x0b\x0c\x0e..\x1f\\\x7f..\xff");
		// Indent continuation lines:
		$s = (string) str_replace("\n", "\n    ", $s);
		return $s;
	}
	
	
	/**
	 * 
	 * @param string $msg
	 * @return void
	 */
	static function notice($msg)
	{
		if( self::$logNotices )
			error_log("notice: " . self::escape($msg) . "\n");
	}
	
	/**
	 * 
	 * @param string $msg
	 * @return void
	 */
	static function warning($msg)
	{
		error_log("Warning: " . self::escape($msg) . "\n");
	}


	/**
	 * 
	 * @param string $msg
	 * @return void
	 */
	static function error($msg)
	{
		error_log("ERROR: " . self::escape($msg) . "\n");
	}
	
}