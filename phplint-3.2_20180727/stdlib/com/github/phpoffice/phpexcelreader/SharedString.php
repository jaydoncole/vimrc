<?php

/**
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation; either
 * version 2.1 of the License, or (at your option) any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public
 * License along with this library; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301  USA
 *
 * @package    PHPExcelReader
 * @copyright  Copyright (c) 2006 - 2015 PHPExcel (http://www.codeplex.com/PHPExcel)
 * @license    http://www.gnu.org/licenses/old-licenses/lgpl-2.1.txt    LGPL
 * @version    $Date: 2016/01/26 12:26:56 $
 */

namespace com\github\phpoffice\phpexcelreader;

require_once __DIR__ . "/../../../../all.php";
/*.
	require_module 'iconv';
	require_module 'mbstring';
	require_module 'pcre';
	require_module 'array';
.*/

/**
 * Utility functions to handle strings.
 */
class SharedString {

	/**
	 * Control characters array
	 *
	 * @var string[]
	 */
	private static $controlCharacters = array();

	/**
	 * Is mbstring extension available?
	 *
	 * @var boolean
	 */
	private static $isMbstringEnabled = FALSE;

	/**
	 * Is iconv extension avalable?
	 *
	 * @var boolean
	 */
	private static $isIconvEnabled = FALSE;

	/**
	 * @throws \ErrorException
	 */
	public static function static_init() {
		self::$isMbstringEnabled = function_exists('mb_convert_encoding');

		// determinate if iconv extension is available and working:
		self::$isIconvEnabled = FALSE;
		do {
			// Fail if iconv doesn't exist
			if (!function_exists('iconv'))
				break;

			// Sometimes iconv is not working, and e.g. iconv('UTF-8', 'UTF-16LE', 'x') just returns false,
			if (iconv('UTF-8', 'UTF-16LE', 'x') === false)
				break;

			// Sometimes iconv_substr('A', 0, 1, 'UTF-8') just returns false in PHP 5.2.0
			// we cannot use iconv in that case either (http://bugs.php.net/bug.php?id=37773)
			if (iconv_substr('A', 0, 1, 'UTF-8') === false)
				break;

			// CUSTOM: IBM AIX iconv() does not work
			if (defined('PHP_OS') && stristr(PHP_OS, 'AIX') !== false && defined('ICONV_IMPL') && (strcasecmp(ICONV_IMPL, 'unknown') == 0) && defined('ICONV_VERSION') && (strcasecmp(ICONV_VERSION, 'unknown') == 0))
				break;

			// If we reach here no problems were detected with iconv
			self::$isIconvEnabled = true;
		} while (FALSE);
	}

	/**
	 * Get whether mbstring extension is available
	 *
	 * @return boolean
	 */
	public static function getIsMbstringEnabled() {
		return self::$isMbstringEnabled;
	}

	/**
	 * Get whether iconv extension is available
	 *
	 * @return boolean
	 */
	public static function getIsIconvEnabled() {
		return self::$isIconvEnabled;
	}

	/**
	 * Convert from OpenXML escaped control character to PHP control character
	 *
	 * Excel 2007 team:
	 * ----------------
	 * That's correct, control characters are stored directly in the shared-strings table.
	 * We do encode characters that cannot be represented in XML using the following escape sequence:
	 * _xHHHH_ where H represents a hexadecimal character in the character's value...
	 * So you could end up with something like _x0008_ in a string (either in a cell value (&lt;v&gt;)
	 * element or in the shared string &lt;t&gt; element.
	 *
	 * @param     string    $value    Value to unescape
	 * @return     string
	 */
	public static function ControlCharacterOOXML2PHP($value) {
		return (string) str_replace(array_keys(self::$controlCharacters), array_values(self::$controlCharacters), $value);
	}

	/**
	 * Try to sanitize UTF8, stripping invalid byte sequences. Not perfect. Does not surrogate characters.
	 *
	 * @param string $value
	 * @return string
	 * @throws \ErrorException
	 */
	public static function SanitizeUTF8($value) {
		if (self::getIsIconvEnabled()) {
			$value = iconv('UTF-8', 'UTF-8', $value);
			return $value;
		}

		if (self::getIsMbstringEnabled()) {
			$value = mb_convert_encoding($value, 'UTF-8', 'UTF-8');
			return $value;
		}

		// else, no conversion
		return $value;
	}

	/**
	 * Get character count. First try mbstring, then iconv, finally strlen
	 *
	 * @param string $value
	 * @param string $enc Encoding
	 * @return int Character count
	 */
	public static function CountCharacters($value, $enc = 'UTF-8') {
		if (self::getIsMbstringEnabled()) {
			return mb_strlen($value, $enc);
		}

		if (self::getIsIconvEnabled()) {
			return iconv_strlen($value, $enc);
		}

		// else strlen
		return strlen($value);
	}

	/**
	 * Decode UTF-16 encoded strings.
	 *
	 * Can handle both BOM'ed data and un-BOM'ed data.
	 * Assumes Big-Endian byte order if no BOM is available.
	 * This function was taken from http://php.net/manual/en/function.utf8-decode.php
	 * and $bom_be parameter added.
	 *
	 * @param   string  $str  UTF-16 encoded data to decode.
	 * @return  string  UTF-8 / ISO encoded data.
	 * @version 0.2 / 2010-05-13
	 * @author  Rasmus Andersson {@link http://rasmusandersson.se/}
	 * @author vadik56
	 */
	private static function utf16_decode($str, $bom_be = true) {
		if (strlen($str) < 2) {
			return $str;
		}
		$c0 = ord($str[0]);
		$c1 = ord($str[1]);
		if ($c0 == 0xfe && $c1 == 0xff) {
			$str = substr($str, 2);
		} elseif ($c0 == 0xff && $c1 == 0xfe) {
			$str = substr($str, 2);
			$bom_be = false;
		}
		$len = strlen($str);
		$newstr = '';
		for ($i = 0; $i < $len; $i+=2) {
			if ($bom_be) {
				$val = ord($str[$i]) << 4;
				$val += ord($str[$i + 1]);
			} else {
				$val = ord($str[$i + 1]) << 4;
				$val += ord($str[$i]);
			}
			$newstr .= ($val == 0x228) ? "\n" : chr($val);
		}
		return $newstr;
	}

	/**
	 * Convert string from one encoding to another. First try mbstring, then iconv, finally strlen
	 *
	 * @param string $value
	 * @param string $to Encoding to convert to, e.g. 'UTF-8'
	 * @param string $from Encoding to convert from, e.g. 'UTF-16LE'
	 * @return string
	 * @throws PHPExcelException
	 */
	public static function ConvertEncoding($value, $to, $from) {
		if (self::getIsIconvEnabled()) {
			try {
				return iconv($from, "$to//IGNORE", $value);
			}
			catch(\ErrorException $e){
				throw new PHPExcelException($e->getMessage());
			}
		}

		if (self::getIsMbstringEnabled()) {
			try {
				return mb_convert_encoding($value, $to, $from);
			}
			catch(\ErrorException $e){
				throw new PHPExcelException($e->getMessage());
			}
		}

		if ($from === 'UTF-16LE') {
			return self::utf16_decode($value, false);
		} elseif ($from === 'UTF-16BE') {
			return self::utf16_decode($value);
		}
		// else, no conversion
		return $value;
	}

	/**
	 * Get a substring of a UTF-8 encoded string. First try mbstring, then iconv, finally strlen
	 *
	 * @param string $pValue UTF-8 encoded string
	 * @param int $pStart Start offset
	 * @param int $pLength Maximum number of characters in substring
	 * @return string
	 */
	public static function Substring($pValue, $pStart, $pLength) {
		if (self::getIsMbstringEnabled()) {
			return mb_substr($pValue, $pStart, $pLength, 'UTF-8');
		}

		if (self::getIsIconvEnabled()) {
			return iconv_substr($pValue, $pStart, $pLength, 'UTF-8');
		}

		// else substr
		return substr($pValue, $pStart, $pLength);
	}

	/**
	 * Convert a UTF-8 encoded string to title/proper case
	 *    (uppercase every first character in each word, lower case all other characters)
	 *
	 * @param string $pValue UTF-8 encoded string
	 * @return string
	 */
	public static function StrToTitle($pValue) {
		if (function_exists('mb_convert_case')) {
			return mb_convert_case($pValue, MB_CASE_TITLE, "UTF-8");
		}
		return ucwords($pValue);
	}

	/**
	 * Check a string that it satisfies Excel requirements
	 *
	 * @param string $pValue Value to sanitize to an Excel string
	 * @return string Sanitized value
	 */
	public static function checkString($pValue) {
//        if ($pValue instanceof RichText) {
//            // TODO: Sanitize Rich-Text string (max. character count is 32,767)
//            return $pValue;
//        }
		// string must never be longer than 32,767 characters, truncate if necessary
		$pValue = SharedString::Substring($pValue, 0, 32767);

		// we require that newline is represented as "\n" in core, not as "\r\n" or "\r"
		$pValue = (string) str_replace(array("\r\n", "\r"), "\n", $pValue);

		return $pValue;
	}
	
}

try {
	SharedString::static_init();
}
catch(\ErrorException $e){
	// maps checked to unchecked exception:
	throw new \RuntimeException($e->getMessage(), $e->getCode(), $e);
}