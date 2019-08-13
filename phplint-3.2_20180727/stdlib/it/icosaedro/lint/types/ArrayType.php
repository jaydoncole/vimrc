<?php

namespace it\icosaedro\lint\types;

require_once __DIR__ . "/../../../../all.php";

use it\icosaedro\lint\types\Type;
use it\icosaedro\containers\HashMap;
use InvalidArgumentException;


/**
 * Array type. An array has elements all of the same type and indeces of type
 * int, or string or mixed (both int and string). A factory method allows to
 * build new array types.
 * @author Umberto Salsi <salsi@icosaedro.it>
 * @version $Date: 2016/03/06 13:25:59 $
 */
final class ArrayType extends Type {
	
	/**
	 * Type of the index. Can be: int, string or mixed, this latter meaning
	 * both int and string.
	 * @var Type
	 */
	private $index;
	
	/**
	 * Type of the elements.
	 * @var Type
	 */
	private $elem;
	
	/**
	 * @param Type $index
	 * @param Type $elem 
	 * @return void
	 */
	private function __construct($index, $elem){
		$this->index = $index;
		$this->elem = $elem;
	}
	
	
	/**
	 * @return boolean
	 */
	public function isPrintable() {
		return FALSE;
	}
	
	
	/**
	 * Returns the type of the index of this array.
	 * @return Type Type of the index, that is int, string or mixed if both
	 * int and string keys are allowed for this array.
	 */
	public function getIndex(){
		return $this->index;
	}
	
	
	/**
	 * Returns the type of the elements.
	 * @return Type Type of the elements.
	 */
	public function getElem(){
		return $this->elem;
	}
	
	
	/**
	 * Factory method that builds a new array type.
	 * @param Type $index Must be int, string or mixed for both.
	 * @param Type $elem Any type except void.
	 * @return ArrayType
	 * @throws InvalidArgumentException Invalid index type. Element type is
	 * void.
	 */
	public static function factory($index, $elem){
		// FIXME: make a pool of the most common array types
		if( ! ($index instanceof IntType || $index instanceof StringType
			|| $index instanceof MixedType) )
			throw new InvalidArgumentException("invalid index type: " . $index);
		if( $index instanceof VoidType )
			throw new InvalidArgumentException("invalid element type: " . $elem);
		return new ArrayType($index, $elem);
	}
	
	/**
	 * Returns true if this array has the same structure of the other.
	 * @param object $o Other array type.
	 * @return boolean True if the types of the index and of the elements are
	 * equal.
	 */
	public function equals($o){
		if( $o === NULL )
			return FALSE;
		if( $this === $o )
			return TRUE;
		if( get_class($this) !== get_class($o) )
			return FALSE;
		$o2 = cast(__CLASS__, $o);
		return $this->index->equals($o2->index) && $this->elem->equals($o2->elem);
	}
	
	/**
	 * Returns a readable representation of this type.
	 * @return string Readable representation of this type in the form
	 * <code>E[K]</code> or <code>E[K1]...[Kn]</code> for a matrix, where
	 * E is the type of the elements and K, K1, ..., Kn are the keys of type
	 * int, string or empty for mixed (both).
	 */
	public function __toString(){
		$s = "";
		/*. Type .*/ $t = $this;
		do {
			$a = cast(__CLASS__, $t);
			if( $a->index instanceof MixedType )
				$s .= "[]";
			else
				$s .= "[" . $a->index->__toString() . "]";
			$t = $a->elem;
		} while( $t instanceof self );
		return $t->__toString() . $s;
	}
	
	
	/**
	 * Returns true if an array of this type (RHS) can be assigned to a
	 * variable of the given type (LHS).
	 * Returns true if the left hand side (LHS) is mixed, unknown, or an array
	 * with index type and elements type assignable with the index type and
	 * elements type of this object.
	 * @param Type $lhs Type of the LHS.
	 * @return boolean True if this type is assignable to the $lhs type.
	 */
	public function assignableTo($lhs){
		if( ($lhs instanceof MixedType)
		|| ($lhs instanceof UnknownType) )
			return TRUE;
		if( ! ($lhs instanceof ArrayType) )
			return FALSE;
		$lhs2 = cast(__CLASS__, $lhs);
		return ($this->index->assignableTo($lhs2->index)
			&& $this->elem->assignableTo($lhs2->elem));
	}
	
	/**
	 * @param Type $lhs
	 * @return boolean
	 */
	public function canCastTo($lhs) {
		if( ($lhs instanceof MixedType)
		|| ($lhs instanceof UnknownType) )
			return TRUE;
		if( ! ($lhs instanceof ArrayType) )
			return FALSE;
		$lhs2 = cast(__CLASS__, $lhs);
		return ($this->index->canCastTo($lhs2->index)
			&& $this->elem->canCastTo($lhs2->elem));
	}
	
	
	/**
	 * Returns true if the elements of this array are real or fully actualized.
	 * @return boolean
	 */
	public function isRealOrFullyActualized() {
		return $this->elem->isRealOrFullyActualized();
	}
	
	
	/**
	 * Returns the actual array type in a generic class by resolving the formal
	 * type of the elements.
	 * @param HashMap $replacements
	 * @return Type May return $this if the type of the elements is not generic.
	 */
	public function actualize($replacements) {
		$actual_elem = $this->elem->actualize($replacements);
		if( $actual_elem === $this->elem )
			return $this;
		else
			return self::factory($this->index, $actual_elem);
	}
	
}
