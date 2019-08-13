<?php

namespace it\icosaedro\lint\types;

require_once __DIR__ . "/../../../../all.php";

use it\icosaedro\containers\Printable;
use it\icosaedro\containers\Comparable;
use it\icosaedro\containers\HashMap;

/**
 * Abstract base class of any type defined under PHPLint.
 * @author Umberto Salsi <salsi@icosaedro.it>
 * @version $Date: 2016/03/06 13:29:54 $
 */
abstract class Type implements Printable, Comparable {
	
	/**
	 * Returns true if a value of this type can be assigned to a variable of
	 * type specified.
	 * @param Type $lhs Type of the assigned variable.
	 * @return boolean True if a value of this type can be assigned to a
	 * variable of type specified.
	 */
	public abstract function assignableTo($lhs);
	
	/**
	 * @param Type $lhs
	 * @return boolean
	 */
	public abstract function canCastTo($lhs);
	
	/**
	 * Returns true if values of this type have a string representation,
	 * then a variable of this type may be appended to string. Some PHP's simple
	 * types and all the classes implementing the __toString() method are printable.
	 * @return boolean
	 */
	public abstract function isPrintable();
	
	/**
	 * If this type does not contain formal type parameters, and then is a simple
	 * type, a real class, a fully actualized class, a class wildcard with fully
	 * actualized bounds, or an array of these.
	 * @return boolean
	 */
	public function isRealOrFullyActualized() {
		return TRUE;
	}
	
	/**
	 * Returns the actual type resolving any formal type of a generic class. Type
	 * classes that may contain type parameters of a generic class must override
	 * this method.
	 * @param HashMap $replacements Actual types replacing the type parameters.
	 * The key is the formal type to replace, the value is the replacement.
	 * @return self This implementation always returns $this.
	 */
	public function actualize($replacements) {
		return $this;
	}
	
}
