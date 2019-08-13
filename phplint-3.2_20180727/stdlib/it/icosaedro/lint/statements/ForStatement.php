<?php

namespace it\icosaedro\lint\statements;
require_once __DIR__ . "/../../../../all.php";
use it\icosaedro\lint\Globals;
use it\icosaedro\lint\Flow;
use it\icosaedro\lint\Result;
use it\icosaedro\lint\Symbol;
use it\icosaedro\lint\expressions\Expression;

/**
 * Parses the for(EXPR1; EXPR2; EXPR3){} statement.
 * @author Umberto Salsi <salsi@icosaedro.it>
 * @version $Date: 2018/06/27 08:55:23 $
 */
class ForStatement {
	
	
	 
	/**
	 * Parses a comma-separated list of expressions.
	 * @param Globals $globals
	 * @return Result Result of the last expression.
	 */
	private static function parseExprList($globals)
	{
		$pkg = $globals->curr_pkg;
		$scanner = $pkg->scanner;
		do {
			$r = Expression::parse($globals);
			if( $scanner->sym === Symbol::$sym_comma )
				$scanner->readSym();
			else
				break;
		} while(TRUE);
		return $r;
	}
	 
	/**
	 * Parses the for(EXPR1; EXPR2; EXPR3){} statement.
	 * @param Globals $globals
	 * @return int See {@link it\icosaedro\lint\Flow}
	 */
	public static function parse($globals)
	{
		$pkg = $globals->curr_pkg;
		$scanner = $pkg->scanner;
		$scanner->readSym();
		$globals->expect(Symbol::$sym_lround, "expected '('");
		$scanner->readSym();

		# Parse EXPR1:
		if( $scanner->sym !== Symbol::$sym_semicolon ){
			/* $r = */ self::parseExprList($globals);
		}
		$globals->expect(Symbol::$sym_semicolon, "expected `;'");
		$scanner->readSym();

		# Parse EXPR2:
		if( $scanner->sym !== Symbol::$sym_semicolon ){
			$expr2 = self::parseExprList($globals);
			$expr2->checkExpectedType($globals->logger, $scanner->here(), Globals::$boolean_type);
		} else {
			$expr2 = NULL;
		}
		$globals->expect(Symbol::$sym_semicolon, "expected `;'");
		$scanner->readSym();

		$before = new AssignedVars($globals);

		# Parse EXPR3:
		if( $scanner->sym !== Symbol::$sym_rround ){
			$r = self::parseExprList($globals);
		}
		$globals->expect(Symbol::$sym_rround, "expected closing `)' of the `for' statement");
		$scanner->readSym();

		$pkg->loop_level .= "F";
		$p = CompoundStatement::parse($globals);
		$pkg->loopExit();

		if( $expr2 === NULL || $expr2->isTrue() ){
			// either missing EXPR2 or evaluates to TRUE means loop forever
			$res = 0;
			# Translate `break' in `next':
			if( ($p & Flow::BREAK_MASK) !== 0 )
				$res = Flow::NEXT_MASK;
			# Copy `return':
			$res = $res | ($p & Flow::RETURN_MASK);
			# keep current set of assigned vars
		} else {
			$res = Flow::NEXT_MASK;
			# Copy `return':
			$res = $res | ($p & Flow::RETURN_MASK);
			$before->restore();
		}

		return $res;
	}
	
}

