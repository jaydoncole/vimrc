<?php

/*.
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
use it\icosaedro\containers\Hash;
use it\icosaedro\containers\Hashable;
use it\icosaedro\containers\Printable;
use it\icosaedro\containers\Sortable;

/**
 * Holds a Gregorian date with time. This class is derived by composition with
 * the {@link it\icosaedro\utils\Date} class, so it inherits the same features
 * and limitations.
 * The time part granularity is milliseconds. This value is immutable.
 * See also the Date and the DateTimeTZ classes.
 * 
 * <p>This class is part of a set of classes that represent a date, a time and
 * a time zone; the picture below illustrates their relationship:
 * <pre>
 *   ISO-8601: 2017-11-23T12:34:56.789+01:00
 *       Date: ^^^^^^^^^^
 *   DateTime: ^^^^^^^^^^^^^^^^^^^^^^^
 * DateTimeTZ: ^^^^^^^^^^^^^^^^^^^^^^^^^^^^^
 * </pre>
 * 
 * @author Umberto Salsi <salsi@icosaedro.it>
 * @version $Date: 2018/06/10 10:19:28 $
 */
class DateTime implements Printable, Hashable, Sortable, Serializable
{
	/**
	 * Date part.
	 * @var Date
	 */
	private $d;
	
	/**
	 * Hour [0,23], minutes [0,59] and milliseconds [0,59999].
	 * @var int
	 */
	private $h = 0, $i = 0, $ms = 0;
	
	/**
	 * Cached formatted string.
	 * @var string
	 */
	private $cached_as_string;
	
	/**
	 * Cached hash.
	 * @var int
	 */
	private $cached_hash = 0;


	/**
	 * Builds a new Gregorian date and time.
	 * @param Date $d  Date.
	 * @param int $h  Hour in the range [0,23].
	 * @param int $i  Minutes in the range [0,59].
	 * @param int $ms Milliseconds in the range [0,59999].
	 * @return void
	 * @throws OutOfRangeException
	 */
	function __construct($d, $h, $i, $ms = 0)
	{
		if( !(0 <= $h && $h <= 23 && 0 <= $i && $i <= 59 && 0 <= $ms && $ms <= 59999) )
			throw new OutOfRangeException("h=$h, i=$i, ms=$ms");
		$this->d = $d;
		$this->h = $h;
		$this->i = $i;
		$this->ms = $ms;
	}


	/**
	 * Parse a date time like "2018-04-10T12:34". A further optional seconds
	 * parts ":56" or seconds and decimals part ":56.789" can be present, but
	 * digits beyond the third decimal are ignored and the result is then
	 * trunked to the ms granularity. The range of each single field is the same
	 * of the arguments of the constructor.
	 * @param string $v Date time to parse.
	 * @return self
	 * @throws InvalidArgumentException Invalid syntax.
	 * @throws OutOfRangeException See limitations of the
	 * {@link it\icosaedro\utils\Date} constructor.
	 */
	static function parse($v)
	{
		$a = explode("T", $v);
		if( count($a) != 2
		|| preg_match("/^[0-9]{1,2}:[0-9]{1,2}(:[0-9]{2}(\\.[0-9]+)?)?\$/sD", $a[1]) !== 1 )
			throw new InvalidArgumentException("invalid date time syntax: $v");
		$d = Date::parse($a[0]);
		$a = explode(":", $a[1]);
		$ms = 0;
		if( count($a) >= 3 ){
			$b = explode(".", $a[2]);
			$ms = 1000 * (int) $b[0];
			if( count($b) >= 2 ){
				$decimals = $b[1];
				if( strlen($decimals) < 3 )
					$decimals .= "00";
				$ms += (int) substr($decimals, 0, 3);
			}
		}
		return new self($d, (int) $a[0], (int) $a[1], $ms);
	}


	/**
	 * Returns the date part of this date time.
	 * @return Date
	 */
	function getDate()
	{
		return $this->d;
	}


	/**
	 * Returns the hour.
	 * @return int Hour in [0,23].
	 */
	function getHour()
	{
		return $this->h;
	}


	/**
	 * Returns the minutes.
	 * @return int Minutes in [0,59].
	 */
	function getMinutes()
	{
		return $this->i;
	}


	/**
	 * Returns the milliseconds.
	 * @return int Milliseconds in [0,59999].
	 */
	function getMilliseconds()
	{
		return $this->ms;
	}
	
	
	/**
	 * Returns this date time formatted as per the given format specificator.
	 * The only recognized charaters of the format specificator are the digits
	 * indicating the number of the field to report:<br>
	 * 1 = four digits of the year;<br>
	 * 2 = two digits of the month;<br>
	 * 3 = two digits of the day;<br>
	 * 4 = two digits of the hour;<br>
	 * 5 = two digits of the minutes;<br>
	 * 6 = two digits of the seconds;<br>
	 * 7 = three digits of the milliseconds.<br>
	 * Any other character passes unmodified.
	 * @param string $fmt Format specificator.
	 */
	function format($fmt)
	{
		$s = "";
		for($i = 0; $i < strlen($fmt); $i++){
			$c = $fmt[$i];
			if( $c === "4" )
				$s .= sprintf("%02d", $this->h);
			else if( $c === "5" )
				$s .= sprintf("%02d", $this->i);
			else if( $c === "6" )
				$s .= sprintf("%02d", (int)($this->ms / 1000));
			else if( $c === "7" )
				$s .= sprintf("%03d", $this->ms % 1000);
			else
				$s .= $this->d->format($c);
		}
		return $s;
	}


	/**
	 * Returns this date time as a string.
	 * @return string This date time parsable with the parse() method.
	 */
	function __toString()
	{
		if( $this->cached_as_string === NULL )
			$this->cached_as_string = $this->d->__toString() . $this->format("T4:5:6.7");
		return $this->cached_as_string;
	}

	
	/**
	 * @return int
	 */
	function getHash()
	{
		if( $this->cached_hash == 0 )
			$this->cached_hash = Hash::combine($this->d->getHash(),
					3600000*$this->h + 60000*$this->i + $this->ms);
		return $this->cached_hash;
	}


	/**
	 * Compares this date time against another date time. Example:
	 * <pre>
	 * if( $a-&gt;compareTo($b) &gt; 0 ){ ... }
	 * </pre>
	 * succeeds if $a &gt; $b.
	 * @param object $other The other date time.
	 * @return int Negative if $this &lt; $other, positive if $this &gt; $other,
	 * zero if the same date.
	 * @throws CastException If $other is NULL or is not exactly instance of
	 * this class.
	 */
	function compareTo($other)
	{
		$other2 = cast(__CLASS__, $other);
		$r = $this->d->compareTo($other2->d);
		if( $r != 0 )
			return $r;
		return 3600000 * ($other2->h - $this->h)
			+ 60000 * ($other2->i - $this->i) + $other2->ms - $this->ms;
	}


	/**
	 * Tells if another date time equals this one.
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
		$this->d = $dt->d;
		$this->h = $dt->h;
		$this->i = $dt->i;
		$this->ms = $dt->ms;
	}
	
	
	/**
	 * Adds the given number of days.
	 * @param int $d Number of days to add.
	 * @return self New date time with the given number of days added.
	 * @throws OutOfRangeException
	 */
	function addDays($d)
	{
		return new self($this->d->addDays($d), $this->h, $this->i);
	}
	
	
	/**
	 * Returns a new date time with the time added.
	 * @param int $h Number of hours to add. For example, -48 subtracts
	 * two days.
	 * @param int $i Number of minutes to add. For example, 1440 adds a day.
	 * @param int $ms Number of milliseconds to add.
	 * @return self New date time with the time added.
	 * @throws OutOfRangeException
	 */
	function addTime($h, $i, $ms = 0)
	{
		$h = $this->h + $h;
		$i = $this->i + $i;
		$ms = $this->ms + $ms;
		if( $ms < 0 )
			$i += (int) (($ms - 59999) / 60000);
		else
			$i += (int) ($ms / 60000);
		$ms = ($ms % 60000 + 60000) % 60000;
		if( $i < 0 )
			$h += (int) (($i - 59) / 60);
		else
			$h += (int) ($i / 60);
		$i = ($i % 60 + 60) % 60;
		if( $h < 0 )
			$d = (int) (($h - 23) / 24);
		else
			$d = (int) ($h / 24);
		$h = ($h % 24 + 24) % 24;
		return new self($this->d->addDays($d), $h, $i, $ms);
	}
	
	/**
	 * Returns the number of minutes elapsed since another date time.
	 * @param self $other
	 * @return int Minutes elapsed since another date time, disregarding the
	 * seconds part.
	 */
	function getMinutesSince($other)
	{
		$days = $this->d->daysSince($other->d);
		return 1440 * $days + 60 * ($this->h - $other->h) + $this->i - $other->i;
	}
	
	/**
	 * Returns the number of seconds elapsed since another date time.
	 * @param self $other
	 * @return int Seconds elapsed since another date time, disregarding the
	 * milliseconds part.
	 * @throws OverflowException May happen on 32-bits systems only.
	 */
	function getSecondsSince($other)
	{
		$s = 60 * $this->getMinutesSince($other)
			+ (int)($this->ms / 1000) - (int)($other->ms / 1000);
		if( is_float($s) ){
			$f = $s + 0.0; // defeats PHPLint strict type validation
			if( (int) $f != $f )
				throw new OverflowException("$this --> $s seconds");
			$s = (int) $f;
		}
		return $s;
	}
	
	/**
	 * Returns the number of milliseconds elapsed since another date time.
	 * @param self $other
	 * @return int Seconds elapsed since another date time, disregarding the
	 * milliseconds part.
	 * @throws OverflowException May happen on 32-bits systems only.
	 */
	function getMillisecondsSince($other)
	{
		$ms = 60000 * $this->getMinutesSince($other) + $this->ms - $other->ms;
		if( is_float($ms) ){
			$f = $ms + 0.0; // defeats PHPLint strict type validation
			if( (int) $f != $f )
				throw new OverflowException("$this --> $ms seconds");
			$ms = (int) $f;
		}
		return $ms;
	}

}
