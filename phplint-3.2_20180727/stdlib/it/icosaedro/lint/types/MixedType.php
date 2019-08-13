<?php

namespace it\icosaedro\lint\types;

require_once __DIR__ . "/../../../../all.php";

/**
 * Singleton instance of the mixed type, that may store anything.
 * @author Umberto Salsi <salsi@icosaedro.it>
 * @version $Date: 2016/03/06 13:28:59 $
 */
final class MixedType extends Type {
	
	/**
	 * @var MixedType 
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
	 * @return MixedType 
	 */
	public static function getInstance(){
		if( self::$instance == NULL )
			self::$instance = new MixedType();
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
		return "mixed";
	}
	
	
	/**
	 * Returns true if the left hand side (LHS) is mixed or unknown.
	 * @param Type $lhs Type of the LHS.
	 * @return boolean True if this type is assignable to the LHS type.
	 */
	public function assignableTo($lhs){
		return ($lhs instanceof MixedType)
		|| ($lhs instanceof UnknownType);
	}
	
	/**
	 * @param Type $lhs
	 * @return boolean
	 */
	public function canCastTo($lhs) {
		return TRUE;
	}
	
}
