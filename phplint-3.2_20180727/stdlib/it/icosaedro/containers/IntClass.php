<?php

namespace it\icosaedro\containers;

require_once __DIR__ . "/../../../autoload.php";
/*. require_module 'spl'; require_module 'pcre'; .*/
use InvalidArgumentException;
use CastException;


/*. forward class IntClass implements Sortable {} .*/

/**
 * @access private
 */
class IntClassSorter implements GenericSorter/*. <IntClass> .*/ {
	function compare(/*. IntClass .*/ $a, /*. IntClass .*/ $b) {
		return $a->compareTo($b);
	}
}


/**
 * Immutable object that holds an integer number.
 *
 * @author Umberto Salsi <salsi@icosaedro.it>
 * @version $Date: 2018/06/06 09:52:53 $
 */
class IntClass implements Hashable, Sortable, Printable {
	
	/**
	 * Number of bytes per int value. This value depends on the current
	 * installation.
	 */
	const BYTES = PHP_INT_SIZE;
	
	/**
	 * Int max value. This value depends on the current installation.
	 */
	const MAX_VALUE = PHP_INT_MAX;
	
	/**
	 * Int min value. This value depends on the current installation.
	 */
	const MIN_VALUE = ~PHP_INT_MAX;
//	PHP_INT_MIN is available only since PHP 7
	
	/**
	 * @var GenericSorter<IntClass>
	 */
	private static $defaultSorter;
	
	/**
	 * @var int
	 */
	private $value = 0;
	
	/**
	 * @param int $value
	 */
	public function __construct($value) {
		// If $value is the result of the evaluation of an expression, PHP might
		// have converted to float! It is safe to check the type:
		if( ! is_int($value) )
			throw new InvalidArgumentException("value is not int: ".gettype($value));
		$this->value = $value;
	}
	
	/**
	 * Returns this int value in its common 10-base representation, possibly with
	 * a minus sign.
	 * @return string
	 */
	public function __toString() {
		return (string) $this->value;
	}
	
	/**
	 * Returns the stored int value.
	 * @return int
	 */
	public function getValue() {
		return $this->value;
	}
	
	/**
	 * Returns the hash for this value.
	 * @return int
	 */
	public function getHash() {
		return Hash::hashOfInt($this->value);
	}
	
	/**
	 * Returns true if this object represents the same int value of the other.
	 * @param object $other
	 * @return boolean
	 */
	public function equals($other) {
		if( $other === NULL )
			return FALSE;
		if( $this === $other )
			return TRUE;
		if( get_class($other) !== __CLASS__ )
			return FALSE;
		$other2 = cast(__CLASS__, $other);
		return $other2->value == $this->value;
	}
	
	/**
	 * Compares this int with the other.
	 * @param object $other The other object.
	 * @return int Negative, zero or positive if $this is less, equal or
	 * greater than $other respectively.
	 * @throws CastException If the other object belongs to a different
	 * class and cannot be compared with this.
	 */
	function compareTo($other) {
		if ($other === NULL)
			throw new CastException("NULL");
		if (get_class($other) !== __CLASS__)
			throw new CastException("expected " . __CLASS__ . " but got " . get_class($other));
		$other2 = cast(__CLASS__, $other);
		$cmp = $this->value - $other2->value; // may overflow int becoming float: ok anyway
		if( $cmp < 0 )
			return -1;
		else if( $cmp > 0 )
			return +1;
		else
			return 0;
	}
	
	
	/**
	 * Returns a sorter that orders the numbers in increasing values.
	 */
	static function getDefaultSorter() {
		if( self::$defaultSorter === NULL )
			self::$defaultSorter = new IntClassSorter();
		return self::$defaultSorter;
	}
	

	/**
	 * Parses the given string as an int number in 10 base with sign.
	 * @param string $s  The string that represents the int number. Spaces are
	 * not allowed: only an optional sign mark +/- followed by one or more
	 * digits is allowed.
	 * @return int
	 * @throws InvalidArgumentException Not a valid int syntax. Value too large,
	 * cannot fit int.
	 */
	static function parse($s)
	{
		# Avoid regex explosion on untrusted data:
		if( strlen($s) > 999 )
			throw new InvalidArgumentException("invalid int: too long");
		
		if( preg_match("/^[-+]?[0-9]+$/sD", $s) !== 1 )
			throw new InvalidArgumentException("invalid int syntax: $s");
		
		# Remove sign:
		$sign = FALSE;
		if( $s[0] === "+" )
			$s = substr($s, 1);
		else if( $s[0] === "-" ){
			$s = substr($s, 1);
			$sign = TRUE;
		}

		# Remove useless leading zeroes (needed for later comparison):
		$s = ltrim("$s", "0");
		if( strlen($s) == 0 )
			return 0;

		# Restore sign:
		if( $sign )
			$s = "-$s";

		$i = (int) $s;
		if( (string) $i !== $s )
			throw new InvalidArgumentException("int out of range: $s");
		return $i;
	}
	
}
