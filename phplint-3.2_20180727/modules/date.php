<?php
/*
 * Advanced date and time functions.
 * Note that the more common date() and time() functions are still in the core
 * module 'standard'.
 */

// Need 'Exception':
/*. require_module 'core'; .*/

define("DATE_ATOM", 'Y-m-d\\TH:i:sP');
define("DATE_COOKIE", 'l, d-M-Y H:i:s T');
define("DATE_ISO8601", 'Y-m-d\\TH:i:sO');
define("DATE_RFC1036", 'D, d M y H:i:s O');
define("DATE_RFC1123", 'D, d M Y H:i:s O');
define("DATE_RFC2822", 'D, d M Y H:i:s O');
define("DATE_RFC3339", 'Y-m-d\\TH:i:sP');
define("DATE_RFC3339_EXTENDED", 'Y-m-d\\TH:i:s.vP');
define("DATE_RFC822", 'D, d M y H:i:s O');
define("DATE_RFC850", 'l, d-M-y H:i:s T');
define("DATE_RSS", 'D, d M Y H:i:s O');
define("DATE_W3C", 'Y-m-d\\TH:i:sP');
define('DAY_1', 131079);
define('DAY_2', 131080);
define('DAY_3', 131081);
define('DAY_4', 131082);
define('DAY_5', 131083);
define('DAY_6', 131084);
define('DAY_7', 131085);
define('MON_1', 131098);
define('MON_10', 131107);
define('MON_11', 131108);
define('MON_12', 131109);
define('MON_2', 131099);
define('MON_3', 131100);
define('MON_4', 131101);
define('MON_5', 131102);
define('MON_6', 131103);
define('MON_7', 131104);
define('MON_8', 131105);
define('MON_9', 131106);
define('SUNFUNCS_RET_DOUBLE', 2);
define('SUNFUNCS_RET_STRING', 1);
define('SUNFUNCS_RET_TIMESTAMP', 0);


/*. forward class DateTime{} .*/

/** See: {@link http://www.php.net/manual/en/ref.datetime.php} */
class DateTimeZone
{
	const
		AFRICA = 1,
		AMERICA = 2,
		ANTARCTICA = 4,
		ARCTIC = 8,
		ASIA = 16,
		ATLANTIC = 32,
		AUSTRALIA = 64,
		EUROPE = 128,
		INDIAN = 256,
		PACIFIC = 512,
		UTC = 1024,
		ALL = 2047,
		ALL_WITH_BC = 4095,
		PER_COUNTRY = 4096;

	static /*. array .*/ function listAbbreviations(){}
	static /*. array[int]string .*/ function listIdentifiers(
		/*. int .*/ $what = DateTimeZone::ALL,
		/*. string .*/ $country = NULL){}
	/*. string .*/ function getName(){}
	/*. int .*/ function getOffset(/*. DateTime .*/ $datetime){}
	/*. void .*/ function __construct(/*. string .*/ $timezone)/*. throws Exception .*/{}
	/**
	 * This method may return array[string][string]mixed or FALSE on failure without
	 * raising errors or notices, so the return type must be a generic mixed.
	 */
	/*. mixed .*/ function getTransitions($timestamp_begin = 0, $timestamp_end = 0){}
	/*. mixed[string] .*/ function getLocation(){}
}


class DateInterval {
	public $y = 0;
	public $m = 0;
	public $d = 0;
	public $h = 0;
	public $i = 0;
	public $s = 0;
	public $invert = 0;
	public /*. mixed .*/ $days;
	public /*. void .*/ function __construct(/*. string .*/ $interval_spec){}
	public static /*. DateInterval .*/ function createFromDateString(/*. string .*/ $time){}
	public /*. string .*/ function format(/*. string .*/ $format){}
}


/*. DateInterval .*/ function date_interval_create_from_date_string(/*. string .*/ $time){}

/**
 * @deprecated Missing definition in the PHP manual, please use the methods
 * from the {@link DateInterval} class instead.
 */
/*. mixed .*/ function date_interval_format(/*. args .*/){}


/** See: {@link http://www.php.net/manual/en/ref.datetime.php} */
class DateTime
{
	/* These constants moved in DateTimeInterface since PHP 7.2.0 */
	/*. if_php_ver_5 .*/
	const
		ATOM  = "Y-m-d\\TH:i:sP",
		COOKIE = "l, d-M-Y H:i:s T",
		ISO8601 = "Y-m-d\\TH:i:sO",
		RFC822 = "D, d M y H:i:s O",
		RFC850 = "l, d-M-y H:i:s T",
		RFC1036 = "D, d M y H:i:s O",
		RFC1123 = "D, d M Y H:i:s O",
		RFC2822 = "D, d M Y H:i:s O",
		RFC3339 = "Y-m-d\\TH:i:sP",
		RFC3339_EXTENDED = "Y-m-d\\TH:i:s.vP",
		RSS = "D, d M Y H:i:s O",
		W3C = "Y-m-d\\TH:i:sP";
	/*. end_if_php_ver .*/

	/*. void .*/ function __construct(
		/*. string .*/ $time = "now",
		/*. DateTimeZone .*/ $timezone = NULL)
		/*. throws Exception .*/ {}
	/*. void .*/ function setDate(/*. int .*/ $year, /*. int .*/ $month, /*. int .*/ $day){}
	/*. string .*/ function format(/*. string .*/ $format){}
	/*. void .*/ function setISODate(/*. int .*/ $year, /*. int .*/ $week, $day = 1){}
	/*. void .*/ function modify(/*. string .*/ $modify){}
	/*. int .*/ function getOffset(){}
	/*. void .*/ function setTime(/*. int .*/ $hour, /*. int .*/ $minute, $second = 0){}
	/*. DateTimeZone .*/ function getTimezone(){}
	/*. void .*/ function setTimezone(/*. DateTimeZone .*/ $tz){}
	/*. DateTime .*/ function add(/*. DateInterval .*/ $interval){}
	/*. DateTime .*/ function sub(/*. DateInterval .*/ $interval){}
	/*. DateInterval .*/ function diff(/*. DateTime .*/ $datetime, $absolute = FALSE){}
	static /*. DateTime .*/ function createFromFormat(/*. string .*/ $format, /*. string .*/ $time, /*. DateTimeZone .*/ $timezone=NULL){}
	static /*. array[string]mixed .*/ function getLastErrors(){}
	/*. int .*/ function getTimestamp(){}
	/*. DateTime .*/ function setTimestamp(/*. int .*/ $unixtimestamp){}
	public static /*. DateTime .*/ function __set_state(/*. array .*/ $array_){}
}


interface DateTimeInterface {
	/* These constants moved here from DateTime since PHP 7.2.0 */
	/*. if_php_ver_7 .*/
	const
		ATOM  = "Y-m-d\\TH:i:sP",
		COOKIE = "l, d-M-Y H:i:s T",
		ISO8601 = "Y-m-d\\TH:i:sO",
		RFC822 = "D, d M y H:i:s O",
		RFC850 = "l, d-M-y H:i:s T",
		RFC1036 = "D, d M y H:i:s O",
		RFC1123 = "D, d M Y H:i:s O",
		RFC2822 = "D, d M Y H:i:s O",
		RFC3339 = "Y-m-d\\TH:i:sP",
		RFC3339_EXTENDED = "Y-m-d\\TH:i:s.vP",
		RSS = "D, d M Y H:i:s O",
		W3C = "Y-m-d\\TH:i:sP";
	/*. end_if_php_ver .*/
	
	public /*. DateInterval .*/ function diff(DateTimeInterface $datetime2, $absolute = false);
	public /*. string .*/ function format(/*. string .*/ $format);
	public /*. int .*/ function getOffset();
	public /*. int .*/ function getTimestamp();
	public /*. DateTimeZone .*/ function getTimezone();
}

class DateTimeImmutable implements DateTimeInterface {
	public /*. void .*/ function __construct($time = "now", DateTimeZone $timezone = NULL){}
	public /*. DateTimeImmutable .*/ function add(DateInterval $interval){}
	public static /*. DateTimeImmutable .*/ function createFromFormat(/*. string .*/ $format, /*. string .*/ $time, DateTimeZone $timezone = NULL){}
	public static /*. DateTimeImmutable .*/ function createFromMutable(DateTime $datetime){}
	public static /*. array .*/ function getLastErrors(){}
	public /*. DateTimeImmutable .*/ function modify(/*. string .*/ $modify){}
	public static /*. DateTimeImmutable .*/ function __set_state(/*. array .*/ $array_){}
	public /*. DateTimeImmutable .*/ function setDate(/*. int .*/ $year, /*. int .*/ $month, /*. int .*/ $day){}
	public /*. DateTimeImmutable .*/ function setISODate(/*. int .*/ $year, /*. int .*/ $week, $day = 1){}
	public /*. DateTimeImmutable .*/ function setTime(/*. int .*/ $hour, /*. int .*/ $minute, $second = 0){}
	public /*. DateTimeImmutable .*/ function setTimestamp(/*. int .*/ $unixtimestamp){}
	public /*. DateTimeImmutable .*/ function setTimezone(DateTimeZone $timezone){}
	public /*. DateTimeImmutable .*/ function sub(DateInterval $interval){}
	public /*. DateInterval .*/ function diff(DateTimeInterface $datetime2, $absolute = false){}
	public /*. string .*/ function format(/*. string .*/ $format){}
	public /*. int .*/ function getOffset(){}
	public /*. int .*/ function getTimestamp(){}
	public /*. DateTimeZone .*/ function getTimezone(){}
	public /*. void .*/ function __wakeup(){}
}

/*. DateTimeImmutable .*/ function date_create_immutable($time = "now", /*. DateTimeZone .*/ $timezone = NULL){}
/*. DateTimeImmutable .*/ function date_create_immutable_from_format(/*. string .*/ $format, /*. string .*/ $time, /*. DateTimeZone .*/ $timezone = NULL){}

class DatePeriod
{
	const EXCLUDE_START_DATE = 1;

	/*. void .*/ function __construct(/*. args .*/){}
}

/*. DateTime .*/ function date_create($time = "now", /*. DateTimeZone .*/ $timezone = NULL){}
/*. void .*/ function date_date_set(/*. DateTime .*/ $obj, /*. int .*/ $year, /*. int .*/ $month, /*. int .*/ $day){}
/*. string .*/ function date_default_timezone_get(){}
/*. bool .*/ function date_default_timezone_set(/*. string .*/ $timezone_identifier){}
/*. string .*/ function date_format(DateTime $obj, /*. string .*/ $format){}
/*. void .*/ function date_isodate_set(/*. DateTime .*/ $obj, /*. int .*/ $year, /*. int .*/ $week, $day = 1){}
/*. void .*/ function date_modify(DateTime $obj, /*. string .*/ $modify){}
/*. int .*/ function date_offset_get(/*. DateTime .*/ $obj){}
/*. array[string]mixed .*/ function date_parse(/*. string .*/ $date){}
/*. array[string]string .*/ function date_sun_info(/*. int .*/ $time, /*. float .*/ $latitude, /*. float .*/ $longitude){}
/*. void .*/ function date_time_set(/*. DateTime .*/ $obj, /*. int .*/ $hour, /*. int .*/ $minute, $second = 0){}
/*. DateTimeZone .*/ function date_timezone_get(/*. DateTime .*/ $obj){}
/*. void .*/ function date_timezone_set(/*. DateTime .*/ $obj, /*. DateTimeZone .*/ $tz){}
/*. array .*/ function timezone_abbreviations_list(){}
/*. array[int]string .*/ function timezone_identifiers_list(){}
/*. string .*/ function timezone_name_from_abbr(/*. string .*/ $abbr, $gmtOffset = -1, $isdst = -1){}
/*. string .*/ function timezone_name_get(/*. DateTimeZone .*/ $obj){}
/*. int .*/ function timezone_offset_get(/*. DateTimeZone .*/ $tz, /*. DateTime .*/ $date){}
/*. DateTimeZone .*/ function timezone_open(/*. string .*/ $timezone){}
/*. array[string][string]mixed .*/ function timezone_transitions_get(/*. DateTimeZone .*/ $tz){}
/*. DateTime .*/ function date_add(/*. DateTime .*/ $dt, /*. DateInterval .*/ $interval){}
/*. DateTime .*/ function date_sub(/*. DateTime .*/ $dt, /*. DateInterval .*/ $interval){}
/*. array[string]mixed .*/ function date_parse_from_format(/*. string .*/ $format, /*. string .*/ $date){}
/*. string .*/ function timezone_version_get(){}
/*. array[string]int .*/ function gettimeofday(){}
/*. mixed .*/ function date_sunrise(/*. int .*/ $time /*., args .*/){}
/*. mixed .*/ function date_sunset(/*. int .*/ $time /*., args .*/){}
/*. DateTime .*/ function date_create_from_format(/*. string .*/ $format, /*. string .*/ $time, /*. DateTimeZone .*/ $timezone = NULL){}
/*. DateInterval .*/ function date_diff(/*. DateTimeInterface .*/ $datetime1, /*. DateTimeInterface .*/ $datetime2, $absolute = false){}
/*. int .*/ function date_timestamp_get(/*. DateTimeInterface .*/ $dti){}
/*. DateTime .*/ function date_timestamp_set(/*. int .*/ $unixtimestamp){}
/*. mixed[string] .*/ function date_get_last_errors(){}