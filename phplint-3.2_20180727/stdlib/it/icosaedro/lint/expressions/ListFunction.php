<?php

namespace it\icosaedro\lint\expressions;
require_once __DIR__ . "/../../../../all.php";
use it\icosaedro\lint\Globals;
use it\icosaedro\lint\Symbol;
use it\icosaedro\lint\Result;
use it\icosaedro\lint\ParseException;

/**
 * Parses the "list()" function. Actually not implemented and always gives error.
 * 
 * @author Umberto Salsi <salsi@icosaedro.it>
 * @version $Date: 2015/09/29 10:51:37 $
 */
class ListFunction {
	
	/**
	 * Parses the "list()" function.
	 * @param Globals $globals
	 * @return Result
	 */
	public static function parse($globals)
	{
		$logger = $globals->logger;
		$scanner = $globals->curr_pkg->scanner;
		
		$logger->error($scanner->here(), "list(...) is unimplemented");
		
		$scanner->readSym(); // skip "list"
		$globals->expect(Symbol::$sym_lround, "expected '(' after 'list'");
		do {
			$scanner->readSym();
			if( $scanner->sym === Symbol::$sym_variable ){
				Assignable::parse($globals, Globals::$unknown_type, TRUE);
			}
			if( $scanner->sym === Symbol::$sym_comma ){
				/* more elements in list */
			} else if( $scanner->sym === Symbol::$sym_rround ){
				$scanner->readSym();
				break;
			} else {
				throw new ParseException($scanner->here(), "expected variable name or closing ')' inside list()");
			}
		} while(TRUE);
		$globals->expect(Symbol::$sym_assign, "expected '=' after list()");
		$scanner->readSym();
		$r = Expression::parse($globals);
		if( $r->isArray() ){
			// ok
		} else if( $r->isUnknown() ){
			// ignore
		} else {
			$logger->error($scanner->here(), "invalid type assigned to list(): " . $r->getType());
		}
		return $r;
	}

}
