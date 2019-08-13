<?php

namespace it\icosaedro\containers;

require_once __DIR__ . "/../../../autoload.php";
use CastException;


/*. forward class StringClass implements Sortable {} .*/

/**
 * @access private
 */
class StringClassSorter implements GenericSorter/*. <StringClass> .*/ {
	function compare(/*. StringClass .*/ $a, /*. StringClass .*/ $b) {
		return $a->compareTo($b);
	}
}

/**
 * Immutable object that holds a string.
 *
 * @author Umberto Salsi <salsi@icosaedro.it>
 * @version $Date: 2016/02/08 22:13:29 $
 */
class StringClass implements Hashable, Sortable, Printable {
	
	/**
	 * @var string
	 */
	private $value;
	
	private $hash = 0;
	
	/**
	 * @var GenericSorter<StringClass>
	 */
	private static $defaultSorter;
	
	/**
	 * @param string $value Can also be NULL.
	 */
	public function __construct($value) {
		$this->value = $value;
	}
	
	/**
	 * @return string
	 */
	public function __toString() {
		return $this->value;
	}
	
	/**
	 * 
	 * @return string
	 */
	public function getValue() {
		return $this->value;
	}
	
	/**
	 * 
	 * @return int
	 */
	public function getHash() {
		if( $this->hash == 0 )
			$this->hash = Hash::hashOfString($this->value);
		return $this->hash;
	}
	
	/**
	 * Here, the empty string and the null string are different.
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
		return $other2->value === $this->value;
	}
	
	/**
	 * Compares this string with the other, byte by byte. The null string is "less"
	 * that any other not-null string.
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
		if( $this->value === NULL ){
			if( $other2->value === NULL )
				return 0;
			else
				return -1;
		} else {
			if( $other2->value === NULL )
				return +1;
			else
				return strcmp($this->value, $other2->value);
		}
	}
	
	
	/**
	 * Returns a sorter based on the internal strcmp() function.
	 */
	static function getDefaultSorter() {
		if( self::$defaultSorter === NULL )
			self::$defaultSorter = new StringClassSorter();
		return self::$defaultSorter;
	}

}
