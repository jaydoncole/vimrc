<?php

namespace it\icosaedro\lint\expressions;
require_once __DIR__ . "/../../../../all.php";
use it\icosaedro\lint\Globals;
use it\icosaedro\lint\Symbol;
use it\icosaedro\lint\Result;
use it\icosaedro\lint\ParseException;

/**
 * Parses "self::".
 * @author Umberto Salsi <salsi@icosaedro.it>
 * @version $Date: 2016/02/21 23:50:40 $
 */
class SelfOperator {
	
	
	/**
	 * Parses "self::".
	 * @param Globals $globals
	 * @return Result
	 */
	public static function parse($globals)
	{
		$pkg = $globals->curr_pkg;
		$scanner = $pkg->scanner;
		$scanner->readSym();
		$globals->expect(Symbol::$sym_double_colon, "expected `::' after `self'");
		if( $pkg->curr_class === NULL ){
			throw new ParseException($scanner->here(), "invalid `self::': not inside a class");
		} else {
			return ClassStaticAccess::parse($globals, $pkg->curr_class);
		}
	}

}
