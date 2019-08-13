<?php

namespace it\icosaedro\lint\statements;
require_once __DIR__ . "/../../../../all.php";
use it\icosaedro\lint\Globals;
use it\icosaedro\lint\Symbol;
use it\icosaedro\lint\expressions\StaticExpression;

/**
 * @author Umberto Salsi <salsi@icosaedro.it>
 * @version $Date: 2016/01/19 03:46:45 $
 */
class DeclareStatement {
	
	private static $DIRECTIVES = array("ticks", "encoding", "strict_types");

	 
	/**
	 * Parses next directive in a declare() statement.
	 * @param Globals $globals
	 * @return void
	 */
	private static function parseDirective($globals)
	{
		$pkg = $globals->curr_pkg;
		$scanner = $pkg->scanner;
		$logger = $globals->logger;
		
		// check directive name:
		$globals->expect(Symbol::$sym_identifier, "expected identifier");
		$directive = $scanner->s;
		if( !in_array($directive, self::$DIRECTIVES) )
			$logger->warning($scanner->here(), "unknown directive \"$directive\"."
				." Expected one of: " . join(", ", self::$DIRECTIVES));
		$scanner->readSym();

		// evaluate its value:
		$globals->expect(Symbol::$sym_assign, "expected `='");
		$scanner->readSym();

		$r = StaticExpression::parse($globals);
		
		if( $r->isUnknown() )
			return; // cannot parse, error already signaled
		
		if( $r->getValue() === NULL ){
			$logger->warning($scanner->here(), "cannot evaluate statically the expression, cannot check");
			return;
		}
		
		$v = $r->getValue();
		switch($directive){
			
			case "ticks":
				if( !($r->isInt() && (int) $v >= 0) )
					$logger->error($scanner->here(), "expected non-negative, int value");
				break;
				
			case "encoding":
				$logger->warning($scanner->here(),
					"may raise E_WARNING if Zend multibyte feature is turned off by settings."
					." There is no way to prevent this warning under PHPLint; simply, do not use this directive.");
				if( ! ($r->isString() && ! $r->isNull()) )
					$logger->error($scanner->here(), "expected string");
				break;
			
			case "strict_types":
				if( ! $globals->isPHP(7) )
					$logger->error ($scanner->here(), "unsupported directive (PHP 7)");
				if( !($r->isInt() && ((int) $v == 0 || (int) $v == 1)) )
					$logger->error($scanner->here(), "expected either 0 or 1");
				break;
			
			default:
				// unknown directive already signaled
		}
	}
	
	 
	/**
	 * Parses the directive()... statement.
	 * @param Globals $globals
	 * @return int See Flow class.
	 */
	public static function parse($globals)
	{
		$pkg = $globals->curr_pkg;
		$scanner = $pkg->scanner;
		$scanner->readSym();
		$globals->expect(Symbol::$sym_lround, "expected `('");
		$scanner->readSym();
		while(TRUE){
			self::parseDirective($globals);
			if( $scanner->sym === Symbol::$sym_comma ){
				$scanner->readSym();
			} else {
				break;
			}
		}
		$globals->expect(Symbol::$sym_rround, "expected `,' or `)'");
		$scanner->readSym();
		return CompoundStatement::parse($globals);
	}
	
}

