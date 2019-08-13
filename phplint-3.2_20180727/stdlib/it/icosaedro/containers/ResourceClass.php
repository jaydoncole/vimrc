<?php

namespace it\icosaedro\containers;

require_once __DIR__ . "/../../../autoload.php";
require_once __DIR__ . "/../../../cast.php";

/**
 * Immutable object that holds a reference to a resource.
 *
 * @author Umberto Salsi <salsi@icosaedro.it>
 * @version $Date: 2016/03/07 10:40:37 $
 */
class ResourceClass implements Comparable, Printable {
	
	/**
	 * @var resource
	 */
	private $value;
	
	/**
	 * @param resource $value
	 */
	public function __construct($value) {
		$this->value = $value;
	}
	
	/**
	 * Returnsthe description of this resource.
	 * @return string Something that might be like "Resource id #27: stream",
	 * altough not very useful.
	 */
	public function __toString() {
		/*. mixed .*/ $m = $this->value;
		return (string) $m . ": " . get_resource_type($this->value);
	}
	
	/**
	 * Returns the stored value.
	 * @return resource
	 */
	public function getValue() {
		return $this->value;
	}
	
	/**
	 * Returns true if this object represents the same value of the other.
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
	
}
