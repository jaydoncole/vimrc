<?php

namespace it\icosaedro\lint\expressions;
require_once __DIR__ . "/../../../../all.php";
use it\icosaedro\lint\Globals;
use it\icosaedro\lint\Symbol;
use it\icosaedro\lint\Result;
use it\icosaedro\lint\ParseException;

/**
 * Parses the "parent::" operator.
 * @author Umberto Salsi <salsi@icosaedro.it>
 * @version $Date: 2018/07/27 10:20:55 $
 */
class ParentOperator {
	
	/**
	 * Parses the "parent::" operator.
	 * @param Globals $globals
	 * @return Result
	 */
	public static function parse($globals)
	{
		$pkg = $globals->curr_pkg;
		$scanner = $pkg->scanner;
		$scanner->readSym();
		$globals->expect(Symbol::$sym_double_colon, "expected `::' after `parent'");
		$c = $pkg->curr_class;
		if( $c === NULL )
			throw new ParseException($scanner->here(), "invalid `parent::': not inside a class");
		$parent_ = $c->extended;
		if( $parent_ === NULL )
			throw new ParseException($scanner->here(), "no parent class for $c");
		return ClassStaticAccess::parse($globals, $parent_);
	}

}
