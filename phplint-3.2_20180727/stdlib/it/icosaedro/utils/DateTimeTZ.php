<?php

/*.
	require_module 'date';
	require_module 'spl';
	require_module 'pcre';
.*/

namespace it\icosaedro\utils;

require_once __DIR__ . "/../../../all.php";

use InvalidArgumentException;
use OutOfRangeException;
use OverflowException;
use RuntimeException;
use Serializable;
use CastException;
use it\icosaedro\containers\Printable;
use it\icosaedro\containers\Hashable;
use it\icosaedro\containers\Sortable;

/**
 * Holds a Gregorian date with time and time zone. This class is derived by
 * composition with the {@link it\icosaedro\utils\DateTime} class, so it
 * inherits the same features and limitations. This value is immutable.
 * Examples:
 * <pre>
 * $now = DateTimeTZ::now();
 * echo "Current local time: $now";
 * echo "Local time zone offset (minutes): ", $now-&gt;getTZ();
 * echo "Current UTC (or Zulu) time: ", $now-&gt;toTZ(0);
 * echo "Camberra current time: ", $now-&gt;toTz(10*60);
 * </pre>
 * 
 * See also the Date and the DateTime classes.
 * 
 * <p>This class is part of a set of classes that represent a date, a time and
 * a time zone; the picture below illustrates their relationship:
 * <pre>
 *   ISO-8601: 2017-11-23T12:34+01:00
 *       Date: ^^^^^^^^^^
 *   DateTime: ^^^^^^^^^^^^^^^^
 * DateTimeTZ: ^^^^^^^^^^^^^^^^^^^^^^
 * </pre>
 * 
 * @author Umberto Salsi <salsi@icosaedro.it>
 * @version $Date: 2018/06/06 09:58:03 $
 */
class DateTimeTZ implements Printable, Hashable, Sortable, Serializable
{
	/** @var DateTime */
	private $dt;
	
	/**
	 * Time zone (minutes).
	 * @var int
	 */
	private $tz = 0;
	
	/**
	 * Cached string formatted.
	 * @var string
	 */
	private $cached_as_string;
	
	/**
	 * Cached timestamp as minutes since Unix epoch. On 32-bits systems this
	 * value does not overflow on the entire range of supported years.
	 * @var int
	 */
	private $cached_timestamp_minutes = 0;


	/**
	 * Builds a new Gregorian date time with time zone.
	 * @param DateTime $dt  Date time this date time with time zone is based on.
	 * @param int $tz Time zone as number of minutes in the range [-1439,+1439].
	 * @return void
	 * @throws InvalidArgumentException
	 */
	function __construct($dt, $tz)
	{
		if( !(-1439 <= $tz && $tz <= 1439) )
			throw new InvalidArgumentException("invalid TZ: $tz");
		$this->dt = $dt;
		$this->tz = $tz;
	}


	/**
	 * Parse a date time with time zone.
	 * @param string $v Date time with time zone. The date and time part must
	 * have the same syntax supported by DateTime. The trailing time zone part
	 * must have format "+12:34" with "+" being the sign.
	 * @return self
	 * @throws InvalidArgumentException Invalid date time with time zone.
	 * @throws OutOfRangeException Year, month or day out of range.
	 */
	static function parse($v)
	{
		if( preg_match("/[-+][0-9]{2}:[0-9]{2}\$/sD", $v) !== 1 )
			throw new InvalidArgumentException("missing or invalid time zone part: $v");
		$dt_part = substr($v, 0, strlen($v) - 6);
		$tz_part = substr($v, strlen($dt_part));
		$dt = DateTime::parse($dt_part);
		$h = (int) substr($tz_part, 1, 2);
		$i = (int) substr($tz_part, 4);
		if( !(0 <= $h && $h <= 23 && 0 <= $i && $i <= 59) )
			throw new InvalidArgumentException("invalid time zone part: $v");
		$tz = 60 * $h + $i;
		if( $tz_part[0] === "-" )
			$tz = -$tz;
		return new self($dt, $tz);
	}


	/**
	 * Returns the date time part referred to the current time zone.
	 * @return DateTime
	 */
	function getDateTime()
	{
		return $this->dt;
	}


	/**
	 * Returns the time zone.
	 * @return int Minutes in the range [-1439,1439].
	 */
	function getTZ()
	{
		return $this->tz;
	}
	
	
	/**
	 * Returns this date time with time zone formatted as per the given format
	 * specificator.
	 * The only recognized charaters of the format specificator are the digits
	 * indicating the number of the field to report:<br>
	 * 1 = four digits of the year;<br>
	 * 2 = two digits of the month;<br>
	 * 3 = two digits of the day;<br>
	 * 4 = two digits of the hour;<br>
	 * 5 = two digits of the minutes;<br>
	 * 6 = two digits of the seconds;<br>
	 * 7 = three digits of the milliseconds;<br>
	 * 8 = six characters of the time zone.<br>
	 * Any other character passes unmodified.
	 * @param string $fmt Format specificator.
	 */
	function format($fmt)
	{
		$s = "";
		for($i = 0; $i < strlen($fmt); $i++){
			$c = $fmt[$i];
			if( $c === "8" ){
				$tz_abs = (int) abs($this->tz);
				$h = (int) ($tz_abs / 60);
				$i = $tz_abs % 60;
				$s .= sprintf("%s%02d:%02d", $this->tz < 0? "-" : "+", $h, $i);
			} else
				$s .= $this->dt->format($c);
		}
		return $s;
	}


	/**
	 * Returns the date as a string.
	 * @return string This date time with time zone parsable with the parse
	 * method.
	 */
	function __toString()
	{
		if( $this->cached_as_string === NULL )
			$this->cached_as_string = $this->dt->__toString() . $this->format("8");
		return $this->cached_as_string;
	}


	/**
	 * @return string
	 */
	function serialize()
	{
		return $this->__toString();
	}

	
	/**
	 * @param string $serialized
	 * @throws RuntimeException
	 */
	function unserialize($serialized)
	{
		$dt = self::parse($serialized);
		$this->dt = $dt->dt;
		$this->tz = $dt->tz;
	}
	
	/**
	 * Returns a new date time with the same time zone and the time added.
	 * @param int $h Number of hours to add. For example, -48 subtracts
	 * two days.
	 * @param int $i Number of minutes to add. For example, 1440 adds a day.
	 * @param int $ms Number of milliseconds to add.
	 * @return self New date time with the time added.
	 * @throws OutOfRangeException
	 */
	function addTime($h, $i, $ms = 0)
	{
		return new self($this->dt->addTime($h, $i, $ms), $this->tz);
	}
	
	/**
	 * Convert this date to the corresponding date in the given time zone.
	 * For example, to get the UTC date time, set the time zone to zero.
	 * @param int $tz Target time zone as number of minutes.
	 * @return self Corresponding date in the given time zone.
	 */
	function toTZ($tz)
	{
		return $this->tz == $tz? $this
		: new self($this->dt->addTime(0, -$this->tz + $tz), $tz);
	}
	
	/**
	 * Returns the timestamp of this date time as minutes since the Unix epoch.
	 * @return int Number of minutes elapsed since the "Unix epoch"
	 * 1970-01-01T00:00:00+00:00, possibly negative, disregarding seconds.
	 */
	function getTimestampMinutes()
	{
		static $epoch = /*. (DateTime) .*/ NULL;
		if( $epoch === NULL )
			$epoch = DateTime::parse("1970-01-01T00:00");
		if( $this->cached_timestamp_minutes == 0 )
			$this->cached_timestamp_minutes = $this->toTZ(0)->getDateTime()
				->getMinutesSince($epoch);
		return $this->cached_timestamp_minutes;
	}
	
	/**
	 * Returns the Unix timestamp of this date time with time zone.
	 * @return int Number of seconds elapsed since the "Unix epoch", possibly
	 * negative, disregarding milliseconds.
	 * @throws OverflowException May happen on 32-bits systems only if this date
	 * is before 1901-12-13T20:45:52 or above 2038-01-19T03:14:07; the
	 * milliseconds part does not matter being ignored anyway.
	 */
	function getTimestamp()
	{
		$ts = 60 * $this->getTimestampMinutes()
			+ (int)($this->getDateTime()->getMilliseconds() / 1000);
		if( is_float($ts) ){
			$f = $ts + 0.0; // defeats PHPLint strict type validation
			if( (int) $f != $f )
				throw new OverflowException("$this --> $ts seconds");
			$ts = (int) $f;
		}
		return $ts;
	}
	
	/**
	 * Converts a timestamp minutes into a date time.
	 * @param int $ts_minutes Minutes since the "Unix epoch". Negative allowed.
	 * @return self
	 * @throws OutOfRangeException Beyond allowed years range.
	 */
	static function fromTimestampMinutes($ts_minutes)
	{
		$ts = $ts_minutes;
		$y = 1970;
		do {
			$miy = 1440 * (Date::isLeapYear($y)? 366 : 365);
			if( $ts < $miy )
				break;
			$ts -= $miy;
			$y++;
		} while(TRUE);
		while($ts < 0){
			$y--;
			$ts += 1440 * (Date::isLeapYear($y)? 366 : 365);
		}
		$m = 1;
		do {
			$mim = 1440 * Date::daysInMonth($y, $m);
			if( $ts < $mim )
				break;
			$ts -= $mim;
			$m++;
		} while(TRUE);
		$d = (int) ($ts / 1440) + 1;  $ts %= 1440;
		$h = (int) ($ts / 60);  $ts %= 60;
		$dtz = new self(new DateTime(new Date($y, $m, $d), $h, $ts), 0);
		$dtz->cached_timestamp_minutes = $ts_minutes;
		return $dtz;
	}
	
	/**
	 * Converts a Unix timestamp into a date time.
	 * @param int $ts Seconds since the "Unix epoch". Negative allowed.
	 * @return self
	 * @throws OutOfRangeException Beyond allowed years range.
	 */
	static function fromTimestamp($ts)
	{
		if( $ts < 0 )
			$ts_minutes = (int) (($ts - 59) / 60);
		else
			$ts_minutes = (int) ($ts / 60);
		$dt = self::fromTimestampMinutes($ts_minutes);
		$dtz = $dt->addTime(0, 0, 1000 * (($ts % 60 + 60) % 60));
		$dtz->cached_timestamp_minutes = $ts_minutes;
		return $dtz;
	}
	
	/**
	 * Returns the number of minutes elapsed since another date time.
	 * @param self $other
	 * @return int Minutes elapsed since another date time.
	 */
	function getMinutesSince($other)
	{
		return $this->getTimestampMinutes() - $other->getTimestampMinutes();
	}

	/**
	 * Factory method that returns the locale date time according to the current
	 * server configuration. Use the {@link self::toTZ()} to translate
	 * this local time to the local time of any other time zone.
	 * @return self Locale date time with time zone as per locale configuration.
	 */
	static function now()
	{
		$a = gettimeofday();
		$dtz = self::fromTimestamp($a["sec"]);
		$dtz = $dtz->addTime(0, 0, (int)(($a["usec"] + 500) / 1000));
		$dtz->tz = -$a["minuteswest"];
		return $dtz;
	}

	/**
	 * @return int
	 */
	function getHash()
	{
		return $this->getTimestampMinutes();
	}

	/**
	 * Compares this date time with time zone with time zone against another.
	 * Two dates referring to the same UTC date and time are equal. Example:
	 * <pre>
	 * if( $a-&gt;compareTo($b) &gt; 0 ){ ... }
	 * </pre>
	 * succeeds if $a &gt; $b.
	 * @param object $other The other date time with time zone.
	 * @return int Negative if $this &lt; $other, positive if $this &gt; $other,
	 * zero if the same UTC time.
	 * @throws CastException If $other is NULL or is not exactly instance of
	 * this class.
	 */
	function compareTo($other)
	{
		$other2 = cast(__CLASS__, $other);
		$r = $this->getTimestampMinutes() - $other2->getTimestampMinutes();
		if( $r != 0 )
			return $r;
		else
			return $this->getDateTime()->getMilliseconds()
				- $other2->getDateTime()->getMilliseconds();
	}

	/**
	 * Tells if another date time equals this one. Two dates are equal if
	 * refer to the same UTC date and time.
	 * @param object $other Another date.
	 * @return bool True if the other date is not NULL, belongs to this same
	 * exact class (not extended) and contains the same date.
	 */
	function equals($other)
	{
		if( $other === NULL )
			return FALSE;
		# Can't throw exceptions by contract:
		try {
			return $this->compareTo($other) == 0;
		}
		catch(CastException $e){}
		return FALSE;
	}

}
