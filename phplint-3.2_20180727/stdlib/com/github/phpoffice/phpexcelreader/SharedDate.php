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
 * @version    $Date: 2018/06/06 09:50:58 $
 */

namespace com\github\phpoffice\phpexcelreader;

require_once __DIR__ . "/../../../../all.php";

/*. require_module 'pcre'; require_module 'date'; .*/

/**
 * Date and time conversion functions.
 * 
 * <p><b>Unix timestamp:</b> also called here "PHP timestamp", is the number of
 * seconds elapsed since the "Unix epoch" 1970-01-01 00:00:00 GMT.
 * 
 * <p><b>Excel timestamp:</b> a number in [0,1[ indicate a fraction of a day of
 * unspecified year and time zone, while a number grater or equal to 1 indicates
 * the number of days elapsed since 1899-12-31 assuming the year 1900 as leap year
 * to comply with an old bug of Lotus 123. This program always stores dates as
 * Excel timestamp.
 * 
 * <p>Dates and times read from a cell may or may not have an explicit time zone
 * indicated. While parsing a string that represents a timestamp, the date, the
 * time and the time zone may be missing. If some of these parts is missing, the
 * defaults are as follows:
 * default date: 1901-01-01;
 * default time: 00:00:00;
 * default time zone: UTC, that is "Europe/London", or even "+00:00".
 */
class SharedDate {

	const CALENDAR_WINDOWS_1900 = 1900;  //    Base date of 1st Jan 1900 = 1.0
	const CALENDAR_MAC_1904 = 1904;   //    Base date of 2nd Jan 1904 = 1.0
	
	// BEWARE. Every format that uses "h" (12-hours format) but not AM/PM may
	// bring to ambiguous values, for example 12:00 can represent midnight but
	// also midday.
	
	const FORMAT_GENERAL = 'General';
	
	// Date only:
	const FORMAT_DATE_YYYYMMDD2 = 'yyyy-mm-dd';
	const FORMAT_DATE_YYYYMMDD = 'yy-mm-dd';
	const FORMAT_DATE_DDMMYYYY = 'dd/mm/yy';
	const FORMAT_DATE_DMYSLASH = 'd/m/y';
	const FORMAT_DATE_DMYMINUS = 'd-m-y';
	const FORMAT_DATE_DMMINUS = 'd-m';
	const FORMAT_DATE_MYMINUS = 'm-y';
	const FORMAT_DATE_XLSX14 = 'mm-dd-yy';
	const FORMAT_DATE_XLSX15 = 'd-mmm-yy';
	const FORMAT_DATE_XLSX16 = 'd-mmm';
	const FORMAT_DATE_XLSX17 = 'mmm-yy';
	const FORMAT_DATE_YYYYMMDDSLASH = 'yy/mm/dd;@';
	
	// Date and time:
	const FORMAT_DATE_XLSX22 = 'm/d/yy h:mm';
	const FORMAT_DATE_DATETIME = 'd/m/y h:mm';
	
	// Time only:
	const FORMAT_DATE_TIME1 = 'h:mm AM/PM';
	const FORMAT_DATE_TIME2 = 'h:mm:ss AM/PM';
	const FORMAT_DATE_TIME3 = 'h:mm';
	const FORMAT_DATE_TIME4 = 'h:mm:ss';
	const FORMAT_DATE_TIME5 = 'mm:ss';
	const FORMAT_DATE_TIME6 = 'h:mm:ss';
	const FORMAT_DATE_TIME7 = 'i:s.S';
	const FORMAT_DATE_TIME8 = 'h:mm:ss;@';

//	/**
//	 * Names of the months of the year, indexed by shortname
//	 * Planned usage for locale settings
//	 *
//	 * @var    string[string]
//	 */
//	private static $monthNames = array(
//		'Jan' => 'January',
//		'Feb' => 'February',
//		'Mar' => 'March',
//		'Apr' => 'April',
//		'May' => 'May',
//		'Jun' => 'June',
//		'Jul' => 'July',
//		'Aug' => 'August',
//		'Sep' => 'September',
//		'Oct' => 'October',
//		'Nov' => 'November',
//		'Dec' => 'December',
//	);

//	/**
//	 * Names of the months of the year, indexed by shortname
//	 * Planned usage for locale settings
//	 *
//	 * @var    string[int]
//	 */
//	private static $numberSuffixes = array(
//		'st',
//		'nd',
//		'rd',
//		'th',
//	);

	/*
	 * Base calendar year to use for calculations.
	 *
	 * @var    int
	 */
	private static $excelBaseDate = self::CALENDAR_WINDOWS_1900;

	/**
	 * Set the Excel calendar (Windows 1900 or Mac 1904)
	 *
	 * @param     integer    $baseDate           Excel base date (1900 or 1904)
	 * @return    boolean                        Success or failure
	 */
	public static function setExcelCalendar($baseDate) {
		if (($baseDate == self::CALENDAR_WINDOWS_1900) ||
				($baseDate == self::CALENDAR_MAC_1904)) {
			self::$excelBaseDate = $baseDate;
			return true;
		}
		return false;
	}

	/**
	 * Return the Excel calendar (Windows 1900 or Mac 1904)
	 *
	 * @return     integer    Excel base date (1900 or 1904)
	 */
	public static function getExcelCalendar() {
		return self::$excelBaseDate;
	}
	
	
	/**
	 * Converts a human-readable date and time string into Unix timestamp. Any value
	 * accepted by {@link strtotime()} the PHP function is allowed. This string
	 * may contain a date, a time and a time zone specifier.
	 * Can parse date back in time up to 1901-12-14 and up to 2038-01-19 in the
	 * future before throwing exception on 32-bits PHP installation.
	 * @param string $datetime Human-readable date and time, for example
	 * "2015-09-26 12:00:00 +02:00".
	 * Default for missing informations are as follows:
	 * default date: 1970-01-01;
	 * default time: 00:00:00;
	 * default time zone: UTC.
	 * @return int Unix timestamp.
	 * @throws PHPExcelException Invalid date/time format. Date outside the allowed
	 * range.
	 */
	public static function parseDateTime($datetime) {
		// set default time zone to UTC:
		$saved_tz = date_default_timezone_get();
		date_default_timezone_set("UTC");
		// parse date/time assuming 1970-01-01 as default date:
		if( preg_match("/^\\d{4}-\\d{2}-\\d{2}T\\d{2}:\\d{2}:\\d{2}\\.\\d{8,}\$/sD", $datetime) == 1 ){
			// PHP BUG #69122 workaround: remove redundant fractional part of second.
			// Some timestamps from OOCalcReader contain such a form of timestamp
			// with more than 7 fractional digits, on which strtotime() fails with error.
			$datetime = substr($datetime, 0, 19);
		}
		$ts = strtotime($datetime, 0);
		if( $ts === FALSE )
			throw new PHPExcelException("cannot parse date/time: $datetime");
		// restore time zone:
		date_default_timezone_set($saved_tz);
		return $ts;
	}
	

	/**
	 * Convert a date from Excel to Unix timestamp of time zone GMT.
	 *
	 * @param  float $dateValue Excel timestamp.
	 * @return int Unix timestamp. The value can be negative for dates before the
	 * Unix epoch. If the Excel value represents a time, the date part is assumed
	 * 1970-01-01.
	 * @throws PHPExcelException The parameter exceeds the range of allowed Unix
	 * timestamps, either in the past or in the future. The value is negative.
	 */
	public static function ExcelToUnixTimestamp($dateValue) {
		// Perform conversion
		if( $dateValue < 0.0 ){
			throw new PHPExcelException("negative Excel timestamp not allowed: $dateValue");
		} else if( $dateValue < 1.0 ){
			// time only
			return (int) round(86400.0 * $dateValue);
		} else {
			// date and time
			if (self::$excelBaseDate == self::CALENDAR_WINDOWS_1900) {
				$myexcelBaseDate = 25569;
				// Adjust for the spurious 29-Feb-1900 (Day 60) for compatibility with
				// old Lotus 123 bug, see http://www.cpearson.com/excel/datetime.htm
				if ($dateValue < 60) {
					--$myexcelBaseDate;
				}
			} else {
				$myexcelBaseDate = 24107;
			}
			$unixDays = $dateValue - $myexcelBaseDate;
			$unixSeconds = round($unixDays * 86400);
			if( $unixSeconds > PHP_INT_MAX )
				throw new PHPExcelException("date too far in the future cannot be converted to Unix timestamp");
			if( $unixSeconds < -PHP_INT_MAX )
				throw new PHPExcelException("date too far in the past cannot be converted to Unix timestamp");
			return (int) $unixSeconds;
		}
	}

	/**
	 * Convert a date from Excel to DateTime.
	 *
	 * @param  float $dateValue Excel timestamp.
	 * @return \DateTime PHP date/time object.
	 * @throws PHPExcelException The parameter exceeds the range of allowed Unix
	 * timestamps, either in the past or in the future. The value is negative.
	 */
	public static function ExcelToDateTime($dateValue) {
		$timestamp = self::ExcelToUnixTimestamp($dateValue);
		try {
			$dt = new \DateTime("@$timestamp");
		}
		catch(\Exception $e){
			// can't happen; anyway:
			throw new \RuntimeException($e->getMessage());
		}
		return $dt;
	}

	/**
	 * @param  integer $year
	 * @param  integer $month
	 * @param  integer $day
	 * @param  integer $hours
	 * @param  integer $minutes
	 * @param  integer $seconds
	 * @return float   Excel date/time value as number of days synce 1900-1-1; the
	 * fractional part is the fraction of a day, for example 0.5 is 12 hours.
	 */
	private static function FormattedPHPToExcel($year, $month, $day, $hours = 0, $minutes = 0, $seconds = 0) {
		if (self::$excelBaseDate == self::CALENDAR_WINDOWS_1900) {
			//
			//    Fudge factor for the erroneous fact that the year 1900 is treated as a Leap Year in MS Excel
			//    This affects every date following 28th February 1900
			//
            $excel1900isLeapYear = 1;
			if (($year == 1900) && ($month <= 2)) {
				$excel1900isLeapYear = 0;
			}
			$myexcelBaseDate = 2415020;
		} else {
			$myexcelBaseDate = 2416481;
			$excel1900isLeapYear = 0;
		}

		//    Julian base date Adjustment
		if ($month > 2) {
			$month -= 3;
		} else {
			$month += 9;
			--$year;
		}

		//    Calculate the Julian Date, then subtract the Excel base date (JD 2415020 = 31-Dec-1899 Giving Excel Date of 0)
		$century = (int) ($year / 100);
		$decade = $year % 100;
		$excelDate = floor((146097 * $century) / 4)
				+ floor((1461 * $decade) / 4)
				+ floor((153 * $month + 2) / 5)
				+ $day + 1721119 - $myexcelBaseDate + $excel1900isLeapYear;

		$excelTime = (($hours * 3600) + ($minutes * 60) + $seconds) / 86400;

		return $excelDate + $excelTime;
	}

	/**
	 * Convert a date from PHP to Excel.
	 *
	 * @param mixed $dateValue PHP timestamp or {@link DateTime} object.
	 * @return float Excel date/time value as number of days synce 1900-1-1; the
	 * fractional part is the fraction of a day, for example 0.5 is 12 hours.
	 * @throws \InvalidArgumentException Invalid type for the date.
	 */
	public static function PHPToExcel($dateValue) {
		$saveTimeZone = date_default_timezone_get();
		date_default_timezone_set('UTC');
		if ($dateValue instanceof \DateTime) {
			$dt = cast("DateTime", $dateValue);
			$retValue = self::FormattedPHPToExcel((int)$dt->format('Y'), (int)$dt->format('m'), (int)$dt->format('d'), (int)$dt->format('H'), (int)$dt->format('i'), (int)$dt->format('s'));
		} else if (is_numeric($dateValue)) {
			$t = (int) $dateValue;
			$retValue = self::FormattedPHPToExcel((int)date('Y', $t), (int)date('m', $t), (int)date('d', $t), (int)date('H', $t), (int)date('i', $t), (int)date('s', $t));
		} else {
			date_default_timezone_set($saveTimeZone);
			throw new \InvalidArgumentException("expected either DateTime or number for the date");
		}
		date_default_timezone_set($saveTimeZone);

		return $retValue;
	}

//	/**
//	 * @access private
//	 */
//	const POSSIBLE_DATE_FORMAT_CHARS = 'eymdHs';

	/**
	 * @access private
	 */
	const POSSIBLE_DATE_ONLY_FORMAT_CHARS = 'eyd';

	/**
	 * @access private
	 */
	const POSSIBLE_TIME_ONLY_FORMAT_CHARS = 'Hs';

	/**
	 * Identifies if the given format string represents a date, a time or both.
	 *
	 * @param   string    $pFormatCode
	 * @return  int 1=date only; 2=time only; 3=date and time; 0=something else.
	 */
	public static function identifyDateAndOrTimeFormatCode($pFormatCode) {
		if (strtolower($pFormatCode) === strtolower(self::FORMAT_GENERAL)) {
			//    "General" contains an epoch letter 'e', so we trap for it explicitly here (case-insensitive check)
			return 0;
		}
		if (preg_match('/[0#]E[+-]0/i', $pFormatCode) == 1) {
			//    Scientific format
			return 0;
		}
		
		//    Explicitly defined date formats
		switch ($pFormatCode) {
			
			// Date only:
			case self::FORMAT_DATE_YYYYMMDD:
			case self::FORMAT_DATE_YYYYMMDD2:
			case self::FORMAT_DATE_DDMMYYYY:
			case self::FORMAT_DATE_DMYSLASH:
			case self::FORMAT_DATE_DMYMINUS:
			case self::FORMAT_DATE_DMMINUS:
			case self::FORMAT_DATE_MYMINUS:
			case self::FORMAT_DATE_YYYYMMDDSLASH:
			case self::FORMAT_DATE_XLSX14:
			case self::FORMAT_DATE_XLSX15:
			case self::FORMAT_DATE_XLSX16:
			case self::FORMAT_DATE_XLSX17:
				return 1;
			
			// Time only:
			case self::FORMAT_DATE_TIME1:
			case self::FORMAT_DATE_TIME2:
			case self::FORMAT_DATE_TIME3:
			case self::FORMAT_DATE_TIME4:
			case self::FORMAT_DATE_TIME5:
//			case self::FORMAT_DATE_TIME6: // duplicates TIME4 above
			case self::FORMAT_DATE_TIME7:
			case self::FORMAT_DATE_TIME8:
				return 2;
			
			// Date and time:
			case self::FORMAT_DATE_XLSX22:
			case self::FORMAT_DATE_DATETIME:
				return 3;
			
			default:
		}

		//    Typically number, currency or accounting (or occasionally fraction) formats
		if ((substr($pFormatCode, 0, 1) === '_') || (substr($pFormatCode, 0, 2) === '0 ')) {
			return 0;
		}
		// Try checking for any of the date formatting characters that don't appear within square braces
		$date_found = preg_match('/(^|\\])[^\\[]*[' . self::POSSIBLE_DATE_ONLY_FORMAT_CHARS . ']/i', $pFormatCode) == 1;
		$time_found = preg_match('/(^|\\])[^\\[]*[' . self::POSSIBLE_TIME_ONLY_FORMAT_CHARS . ']/i', $pFormatCode) == 1;
		
		if (($date_found || $time_found) && strpos($pFormatCode, '"') !== false) {
			//    We have a format mask containing quoted strings...
			//        we don't want to test for any of our characters within the quoted blocks
			$segMatcher = false;
			$date_found = false;
			$time_found = false;
			foreach (explode('"', $pFormatCode) as $subVal) {
				//    Only test in alternate array entries (the non-quoted blocks)
				if ($segMatcher = !$segMatcher){
					if( ! $date_found && preg_match('/(^|\\])[^\\[]*[' . self::POSSIBLE_DATE_ONLY_FORMAT_CHARS . ']/i', $subVal) == 1 ){
						$date_found = true;
					}
					if( ! $time_found && preg_match('/(^|\\])[^\\[]*[' . self::POSSIBLE_TIME_ONLY_FORMAT_CHARS . ']/i', $subVal) == 1 ){
						$time_found = true;
					}
				}
			}
		}
		
		return ($date_found? 1 : 0) + ($time_found? 2 : 0);
	}

}
