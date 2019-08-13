<?php

/*.
	require_module 'spl';
	require_module 'pcre';
.*/

namespace it\icosaedro\utils;

require_once __DIR__ . "/../../../all.php";

use InvalidArgumentException;
use OutOfRangeException;
use RuntimeException;
use Serializable;
use CastException;
use it\icosaedro\containers\Printable;
use it\icosaedro\containers\Hashable;
use it\icosaedro\containers\Sortable;

/**
 * Holds a Gregorian date with year in the range [1583,9999].
 * Actually the Gregorian calendar was introduced in 1582, but in that year
 * ten days where dropped to realign the calendar with the astronomical year:
 * that transitional year is not supported by this class.
 * This value is immutable.
 * See also the DateTime and the DateTimeTZ classes.
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
class Date implements Printable, Hashable, Sortable, Serializable
{
	/**
	 * Year, month, day.
	 * @var int
	 */
	private $y = 0, $m = 0, $d = 0;
	
	/**
	 * Cached string formatted.
	 * @var string
	 */
	private $cached_as_string;
	
	/**
	 * Cached hash.
	 * @var int
	 */
	private $cached_hash = 0;
	
	/**
	 * Minimum supported year. The Gregorian calendar was introduced in 1582, but
	 * that year is special because 10 days of October where dropped, so this transitional
	 * year is not supported by this class. The minimum date is then 1583-01-01.
	 */
	const YEAR_MIN = 1583;
	
	/**
	 * Maximum supported year. Actually this class might support years well above
	 * this limit, but dates so far in the future are unlikely to be actual useful
	 * dates so it worth to signal them as errors. The maximum date is then 9999-12-31.
	 */
	const YEAR_MAX = 9999;


	/**
	 * Tells if the year is a leap year, that is, February has 29 days.
	 * @param int $y Year under test.
	 * @return bool True if the year is a leap year.
	 */
	static function isLeapYear($y)
	{
		return $y % 400 == 0
			|| $y % 100 != 0 and $y % 4 == 0;
	}


	/**
	 * Builds a new Gregorian date. The minimum date is 1583-01-01; the maximum
	 * date is 9999-12-31.
	 * @param int $y  Year in the range [1583,9999].
	 * @param int $m  Month in the range [1,12].
	 * @param int $d  Day in the range [1,31].
	 * @return void
	 * @throws OutOfRangeException
	 */
	function __construct($y, $m, $d)
	{
		if(
			! (
				self::YEAR_MIN <= $y and $y <= self::YEAR_MAX
				and 1 <= $m and $m <= 12
				and 1 <= $d and $d <= 31
			)
			or ( ($m == 4 or $m == 6 or $m == 9) and $d > 30 )
			or ( $m == 2 and ($d > 29 or ! self::isLeapYear($y) and $d > 28) )
		)
			throw new OutOfRangeException("invalid date: $y-$m-$d");

		$this->y = $y;
		$this->m = $m;
		$this->d = $d;
	}


	/**
	 * Parse a date.
	 * @param string $v Date in the format YYYY-MM-DD where YYYY are the 4
	 * digits of the year, MM are one or two digits of the month, and DD are one
	 * or two digits of the day. The ranges of the values
	 * are the same as of the constructor.
	 * @return self
	 * @throws InvalidArgumentException Invalid date.
	 * @throws OutOfRangeException
	 */
	static function parse($v)
	{
		if( 1 !== preg_match("/^[0-9]{4}-[0-9]{1,2}-[0-9]{1,2}\$/sD", $v) )
			throw new InvalidArgumentException("expected date YYYY-MM-DD but got: $v");
		$a = explode("-", $v);
		return new self((int) $a[0], (int) $a[1], (int) $a[2]);
	}


	/**
	 * Returns the year.
	 * @return int Year.
	 */
	function getYear()
	{
		return $this->y;
	}


	/**
	 * Returns the month.
	 * @return int Month in [1,12].
	 */
	function getMonth()
	{
		return $this->m;
	}


	/**
	 * Returns the day.
	 * @return int Day in [1,31].
	 */
	function getDay()
	{
		return $this->d;
	}
	
	
	/**
	 * Returns this date formatted as per the given format specificator.
	 * The only recognized charaters of the format specificator are the digits
	 * indicating the number of the field to report:<br>
	 * 1 = four digits of the year;<br>
	 * 2 = two digits of the month;<br>
	 * 3 = two digits of the day.<br>
	 * Any other character passes unmodified.
	 * @param string $fmt Format specificator.
	 */
	function format($fmt)
	{
		$s = "";
		for($i = 0; $i < strlen($fmt); $i++){
			$c = $fmt[$i];
			if( $c === "1" )
				$s .= sprintf("%04d", $this->y);
			else if( $c === "2" )
				$s .= sprintf("%02d", $this->m);
			else if( $c === "3" )
				$s .= sprintf("%02d", $this->d);
			else
				$s .= $c;
		}
		return $s;
	}


	/**
	 * Returns the date as a string.
	 * @return string The date in the form YYYY-MM-DD.
	 */
	function __toString()
	{
		if( $this->cached_as_string === NULL )
			$this->cached_as_string = $this->format("1-2-3");
		return $this->cached_as_string;
	}

	/**
	 * @return int
	 */
	function getHash()
	{
		if( $this->cached_hash == 0 )
			$this->cached_hash = 416 * $this->y + 32 * $this->m + $this->d;
		return $this->cached_hash;
	}


	/**
	 * Compares this date against another date. Example:
	 * <pre>
	 * if( $a-&gt;compareTo($b) &gt; 0 ){ ... }
	 * </pre>
	 * succeeds if $a &gt; $b.
	 * @param object $other The other date.
	 * @return int Negative if $this &lt; $other, positive if $this &gt; $other,
	 * zero if the same date.
	 * @throws CastException If $other is NULL or is not exactly instance of
	 * Date.
	 */
	function compareTo($other)
	{
		if( $other === NULL )
			throw new \CastException("NULL");
		if( get_class($other) !== __CLASS__ )
			throw new CastException("expected " . __CLASS__ . " but got "
			. get_class($other));
		$other2 = cast(__CLASS__, $other);
		$a = 416 * $this->y + 32 * $this->m + $this->d;
		$b = 416 * $other2->y + 32 * $other2->m + $other2->d;
		return $a - $b;
	}


	/**
	 * Tells if the another date equals this one.
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
		try {
			$d = self::parse($serialized);
		}
		catch(InvalidArgumentException $e){
			throw new RuntimeException($e->getMessage());
		}
		$this->y = $d->y;
		$this->m = $d->m;
		$this->d = $d->d;
	}
	
	
	/**
	 * Returns the number of days in the given month. No range check is performed
	 * because this is intended as a very low-level function.
	 * @param int $y Year.
	 * @param int $m Month.
	 * @return int 
	 */
	static function daysInMonth($y, $m) {
		if($m == 2){
			if(self::isLeapYear($y))
				$days = 29;
			else
				$days = 28;
		} else if($m == 4 || $m == 6 || $m == 9 || $m == 11)
			$days = 30;
		else
			$days = 31;
		return $days;
	}
	
	
	/**
	 * Returns the number of days elapsed from this date since another date.
	 * @param self $other Another date.
	 * @return int Number of days elapsed from this date since the other date,
	 * possibly negative if this date precedes the other.
	 */
	function daysSince($other) {
		// sorting dates so that date 1 <= date 2:
		if($this->compareTo($other) >= 0){
			$a = $other; $b = $this;
			$sign = +1;
		} else {
			$a = $this; $b = $other;
			$sign = -1;
		}
		$y1 = $a->y; $m1 = $a->m; $d1 = $a->d; // date 1
		$y2 = $b->y; $m2 = $b->m; $d2 = $b->d; // date 2
		$days = 0;
		// align year and month of date 1 with those of date 2, accounting days:
		while( $y1 < $y2 || $m1 < $m2){
			if( $y1 < $y2 && $m1 == 1 && $d1 == 1 ){
				// optimization: skip full year:
				$days += 365;
				if( self::isLeapYear($y1) )
					$days++;
				$y1++;
			} else {
				$days += self::daysInMonth($y1, $m1) - $d1 + 1;
				$d1 = 1;
				$m1++;
				if($m1 > 12){
					$y1++;
					$m1 = 1;
				}
			}
		}
		return $sign * ($days + $d2 - $d1);
	}
	
	
	/**
	 * Returns the day of the week of this date. The value returned assumes Monday
	 * be the first day of the week and assigns to it the number 0. Other possible
	 * conventions can be ISO 8601 which assigns Monday=1:
	 * <pre>$iso_day = $d-&gt;dayOfWeek() + 1</pre>
	 * or the Javascript getDay() method which assigns Sunday=0:
	 * <pre>$js_day = ($d-&gt;dayOfWeek() + 1) % 7;</pre>
	 * @return int Number of the day of the week, being Monday=0,
	 * Tuesday=1, ..., Sunday=6.
	 */
	function dayOfWeek() {
		// Implementation notes. Here we count the days elapsed sinche a well known
		// anchor date (date 1), then we take the reminder of the division by 7.
		// Other methods can be found at
		// https://en.wikipedia.org/wiki/Determination_of_the_day_of_the_week
		$anchor = new self(2000,1,3);
		$days = $this->daysSince($anchor);
		$wd = $days % 7;
		if($wd < 0)
			$wd = 7 + $wd;
		return $wd;
	}
	
	
	/**
	 * Returns a date which is this date plus the amount of days given.
	 * @param int $days Number of days to add, possibly negative.
	 * @return self This date with the amount of days given added.
	 * @throws OutOfRangeException The resulting year is above or below the
	 * supported range of years.
	 */
	function addDays($days) {
		if( $days == 0 ){
			return $this;
		} else if( $days > 0 ){
			$y = $this->y; $m = $this->m; $d = $this->d;
			while( $days > 0 ){
				$x = self::daysInMonth($y, $m) - $d;
				if( $days > $x ){
					// move to beginning next month
					$days -= $x + 1;
					$m++;
					if( $m > 12 ){
						$y++;
						if( $y > self::YEAR_MAX )
							throw new OutOfRangeException("too many days added");
						$m = 1;
					}
					$d = 1;
				} else {
					$d += $days;
					$days = 0;
				}
			}
			return new self($y, $m, $d);
		} else {
			$y = $this->y; $m = $this->m; $d = $this->d;
			$days = -$days;
			while( $days > 0 ){
				if( $days >= $d ){
					// move to end prev month
					$days -= $d;
					$m--;
					if( $m < 1 ){
						$y--;
						if( $y < self::YEAR_MIN )
							throw new OutOfRangeException("too many days subtracted");
						$m = 12;
					}
					$d = self::daysInMonth($y, $m);
				} else {
					$d -= $days;
					$days = 0;
				}
			}
			return new self($y, $m, $d);
		}
	}

}
