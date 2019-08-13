<?php

namespace it\icosaedro\lint\types;

require_once __DIR__ . "/../../../../all.php";
use it\icosaedro\lint\ParseException;
use it\icosaedro\lint\Where;
use it\icosaedro\containers\HashMap;

/**
 * Singleton instance of the "still unknown" type.
 * When a function or a method does not explicitly declares its returned type,
 * PHPLint tries to guess the returned type from the first "return" statement
 * it found in the source. In the meanwhile, the type returned is set to
 * the singleton instance of this class.
 * The Signature class sets this type as default return type. Only the
 * code that parses functions, methods and the "return" statement are aware
 * of its existance and handle it properly in the attempt to guess automatically
 * the right returned type.
 * @author Umberto Salsi <salsi@icosaedro.it>
 * @version $Date: 2016/03/06 13:28:27 $
 */
final class GuessType extends Type {
	
	/**
	 * @var GuessType 
	 */
	private static $instance;
	
	private /*. void .*/ function __construct(){
	}
	
	
	/**
	 * @return boolean
	 */
	public function isPrintable() {
		return FALSE;
	}
	
	
	/**
	 *
	 * @return GuessType 
	 */
	public static function getInstance(){
		if( self::$instance == NULL )
			self::$instance = new GuessType();
		return self::$instance;
	}
	
	/**
	 *
	 * @param object $o
	 * @return boolean 
	 */
	public function equals($o){
		return $o !== NULL and $this === $o;
	}
	
	/**
	 *
	 * @return string 
	 */
	public function __toString(){
		return "\$RETURN_TYPE_STILL_TO_GUESS";
	}
	
	
	/**
	 * Always return false. This garantees that if the function or method
	 * appears in any expression, it is an error.
	 * @param Type $lhs Type of the LHS.
	 * @return boolean Always false
	 */
	public function assignableTo($lhs){
		return false;
	}
	
	/**
	 * @param Type $lhs
	 * @return boolean
	 */
	public function canCastTo($lhs) {
		return false;
	}
	
	
	/**
	 * Always throws ParseException because we cannot establish if this type still
	 * to guess will be real or fully actualized.
	 * @return boolean
	 */
	public function isRealOrFullyActualized() {
		// FIXME: cannot report where!
		throw new ParseException(Where::getSomewhere(), "still unknown type, cannot guess if it is real or fully actualized");
	}
	
	
	/**
	 * Returns the actual type resolving any formal type of a generic class. Type
	 * classes that may contain type parameters of a generic class must override
	 * this method.
	 * @param HashMap $actual_types Actual types replacing the type parameters.
	 * The index is the ordinal of the parameter, zero being the first one.
	 * @return self This implementation always returns $this.
	 */
	public function actualize($actual_types) {
		// FIXME: cannot report where!
		throw new ParseException(Where::getSomewhere(), "cannot actualize still unknown type");
	}
	
}
