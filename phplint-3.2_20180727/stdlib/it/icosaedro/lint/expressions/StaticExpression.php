<?php

namespace it\icosaedro\lint\expressions;
require_once __DIR__ . "/../../../../all.php";
use it\icosaedro\lint\types\ClassType;
use it\icosaedro\lint\TypeDecl;
use it\icosaedro\lint\Globals;
use it\icosaedro\lint\Symbol;
use it\icosaedro\lint\Result;
use it\icosaedro\lint\ParseException;
use it\icosaedro\lint\PhpVersion;
use RuntimeException;

/**
 * Parses static expression in constant definition, property initial value,
 * default value of formal argument, case switch.
 * Since PHP 5.6.0 static expressions allows simple evaluation, see
 * {@link http://php.net/manual/en/language.oop5.constants.php}.
 * @author Umberto Salsi <salsi@icosaedro.it>
 * @version $Date: 2016/02/08 23:57:59 $
 */
class StaticExpression {
	
	/*. forward public static Result function parse(Globals $globals); .*/

	/**
	 * @param Globals $globals
	 * @param ClassType $c
	 * @return Result
	 */
	private static function parseClassConst($globals, $c)
	{
		$pkg = $globals->curr_pkg;
		$scanner = $pkg->scanner;
		$scanner->readSym();
		
		if( $scanner->sym === Symbol::$sym_class ){
			$scanner->readSym();
			// Consts cannot be dereferenced by '['.
			return Result::factory(Globals::$string_type, $c->__toString());
		
		} else if( $scanner->sym === Symbol::$sym_identifier ){
			$co = $c->searchConstant($scanner->s);
			if( $co === NULL ){
				$globals->logger->error($scanner->here(),
				"unknown constant $c::" . $scanner->s);
				$scanner->readSym();
				return Result::getUnknown();
			} else {
				$globals->accountClassConstant($co);
			}
			$scanner->readSym();
			return $co->value;
		
		} else {
			throw new ParseException($scanner->here(),
			"expected name of class constant");
		}
	}
	 
	
	/**
	 * @param Globals $globals
	 * @return Result
	 */
	public static function parseTerm($globals)
	{
		$pkg = $globals->curr_pkg;
		$scanner = $pkg->scanner;
		
		if( $scanner->sym === Symbol::$sym_namespace )
			$globals->resolveNamespaceOperator();

		if( $scanner->sym === Symbol::$sym_null ){
			$r = Result::factory(Globals::$null_type, "NULL");
			$scanner->readSym();

		} else if( $scanner->sym === Symbol::$sym_false ){
			$r = Result::factory(Globals::$boolean_type, "FALSE");
			$scanner->readSym();

		} else if( $scanner->sym === Symbol::$sym_true ){
			$r = Result::factory(Globals::$boolean_type, "TRUE");
			$scanner->readSym();

		} else if( $scanner->sym === Symbol::$sym_plus ){
			$scanner->readSym();
			$r = self::parseTerm($globals);
			$r = $r->unaryPlus($globals->logger, $scanner->here());
		
		} else if( $scanner->sym === Symbol::$sym_minus ){
			$scanner->readSym();
			$r = self::parseTerm($globals);
			$r = $r->unaryMinus($globals->logger, $scanner->here());

		} else if( $scanner->sym === Symbol::$sym_lit_int ){
			$r = Result::factory(Globals::$int_type, $scanner->s);
			$scanner->readSym();

		} else if( $scanner->sym === Symbol::$sym_lit_float ){
			$r = Result::factory(Globals::$float_type, $scanner->s);
			$scanner->readSym();

		} else if( $scanner->sym === Symbol::$sym_single_quoted_string ){
			$r = Result::factory(Globals::$string_type, $scanner->s);
			$scanner->readSym();

		} else if( $scanner->sym === Symbol::$sym_double_quoted_string ){
			$r = Result::factory(Globals::$string_type, $scanner->s);
			$scanner->readSym();

		} else if( $scanner->sym === Symbol::$sym_here_doc ){
			$r = Result::factory(Globals::$string_type, $scanner->s);
			$scanner->readSym();

		} else if( $scanner->sym === Symbol::$sym_identifier ){
			$id = $scanner->s;
			$scanner->readSym();
			if( $scanner->sym === Symbol::$sym_double_colon ){
				$c = $globals->searchClass($id);
				if( $c === NULL ){
					$globals->logger->error($scanner->here(),
					"unknown class $id");
					SkipUnknown::anything($globals);
					$r = Result::getUnknown();
				} else {
					$r = self::parseClassConst($globals, $c);
				}

			} else {
				$co = $globals->searchConstant($id);
				if( $co === NULL ){
					$globals->logger->error($scanner->here(),
					"unknown constant $id");
					$r = Result::getUnknown();
				} else {
					$globals->accountConstant($co);
					if( $co->is_magic )
						$r = MagicConstants::resolve($globals, $co);
					else
						$r = $co->value;
				}
			}

		} else if( $scanner->sym === Symbol::$sym_self ){
			if( $pkg->curr_class === NULL )
				throw new ParseException($scanner->here(),
				"`self::': not inside a class");
			$scanner->readSym();
			$globals->expect(Symbol::$sym_double_colon, "expected `::'");
			$r = self::parseClassConst($globals, $pkg->curr_class);

		} else if( $scanner->sym === Symbol::$sym_parent ){
			$globals->logger->error($scanner->here(),
			"`parent::' in static expression cannot be resolved at parse time (PHP limitation)");
			if( $pkg->curr_class === NULL ){
				$globals->logger->error($scanner->here(),
				"`parent::': not inside a class");
				$r = Result::getUnknown();
			} else {
				$parent_ = $pkg->curr_class->extended;
				if( $parent_ === NULL )
					$globals->logger->error($scanner->here(),
					"invalid `parent::': class `"
					. $pkg->curr_class->name . "' has not parent");
				$scanner->readSym();
				$globals->expect(Symbol::$sym_double_colon, "expected `::'");
				$r = self::parseClassConst($globals, $parent_);
			}

		} else if( $scanner->sym === Symbol::$sym_array ){
			$scanner->readSym();
			if( $scanner->sym !== Symbol::$sym_lround )
				throw new ParseException($scanner->here(),
				"expected `(' after `array'");
			$r = ArrayConstructor::parse($globals, TRUE, FALSE);

		} else if( $scanner->sym === Symbol::$sym_lsquare ){
			$r = ArrayConstructor::parse($globals, TRUE, TRUE);
		
		} else if( $scanner->sym === Symbol::$sym_lround ){
			$scanner->readSym();
			$r = self::parse($globals);
			$globals->expect(Symbol::$sym_rround, "expected `)'");
			$scanner->readSym();

		} else if( $scanner->sym === Symbol::$sym_x_lround ){
			/* Formal typecast. */
			$scanner->readSym();
			$t = TypeDecl::parse($globals, FALSE);
			if( $t === NULL ){
				$globals->logger->error($scanner->here(),
				"missing type specifier");
				$t = Globals::$unknown_type;
			}
			$globals->expect(Symbol::$sym_x_rround,
			"expected closing `)' in formal typecast");
			$scanner->readSym();
			$r = self::parse($globals);
			if( $r->isNull() || $r->isEmptyArray() ){
				# ok
			} else {
				$globals->logger->error($scanner->here(), "formal typecast allowed only if applied to NULL or empty array array()");
			}
			$r = $r->typeCast($globals->logger, $scanner->here(), $t);

		} else {
			throw new ParseException($scanner->here(), "invalid static expression -- expected string, constant or static array");
		}

		return $r;
	}


	/**
	 * Parses unary operators.
	 * @param Globals $globals
	 * @return Result
	 */
	private static function e17($globals){
		$scanner = $globals->curr_pkg->scanner;
		
		if( $scanner->sym === Symbol::$sym_not ){
			$scanner->readSym();
			$r = self::e17($globals)->booleanNot($globals->logger, $scanner->here());
			
		} else if( $scanner->sym === Symbol::$sym_plus ){
			$scanner->readSym();
			$r = self::e17($globals)->unaryPlus($globals->logger, $scanner->here());
			
		} else if( $scanner->sym === Symbol::$sym_minus ){
			$scanner->readSym();
			$r = self::e17($globals)->unaryMinus($globals->logger, $scanner->here());
			
		} else if( $scanner->sym === Symbol::$sym_bit_not ){
			$scanner->readSym();
			$r = self::e17($globals)->bitNot($globals->logger, $scanner->here());
			
		} else if( $scanner->sym === Symbol::$sym_at ){
			$globals->curr_pkg->enteringSilencer();
			$scanner->readSym();
			$r = self::e17($globals);
			$globals->curr_pkg->exitingSilencer();
			
		} else {
			$r = self::parseTerm($globals);
		}
		return $r;
	}


	/**
	 * Parses multiplicative operators.
	 * @param Globals $globals
	 * @return Result
	 */
	private static function e16($globals){
		$scanner = $globals->curr_pkg->scanner;
		$r = self::e17($globals);
		while( $scanner->sym === Symbol::$sym_times
		|| $scanner->sym === Symbol::$sym_div
		|| $scanner->sym === Symbol::$sym_mod ){
			$op = $scanner->sym;
			$scanner->readSym();
			$t = self::e17($globals);
			switch( $op->__toString() ){
			case "sym_times": $r = $r->times($globals->logger, $scanner->here(), $t);  break;
			case "sym_div":   $r = $r->divide($globals->logger, $scanner->here(), $t);  break;
			case "sym_mod":   $r = $r->modulus($globals->logger, $scanner->here(), $t);  break;
			default: throw new RuntimeException();
			}
		}
		return $r;
	}


	/**
	 * Parses additive operators.
	 * @param Globals $globals
	 * @return Result
	 */
	private static function e15($globals)
	{
		$scanner = $globals->curr_pkg->scanner;
		$r = self::e16($globals);
		while( $scanner->sym === Symbol::$sym_plus
		|| $scanner->sym === Symbol::$sym_minus
		|| ($scanner->sym === Symbol::$sym_period) ){
			$op = $scanner->sym;
			$where = $scanner->here();
			$scanner->readSym();
			$q = self::e16($globals);
			switch( $op->__toString() ){
			case "sym_plus":   $r = $r->plus($globals->logger, $where, $q);  break;
			case "sym_minus":  $r = $r->minus($globals->logger, $where, $q);  break;
			case "sym_period": $r = $r->dot($globals->logger, $where, $q);  break;
			default: throw new RuntimeException("$op");
			}
		}
		return $r;
	}


	/**
	 * Parses bitwise shift operators.
	 * @param Globals $globals
	 * @return Result
	 */
	private static function e14($globals){
		$scanner = $globals->curr_pkg->scanner;
		$r = self::e15($globals);
		while( $scanner->sym === Symbol::$sym_lshift
		|| $scanner->sym === Symbol::$sym_rshift ){
			$op = $scanner->sym;
			$scanner->readSym();
			$t = self::e15($globals);
			if( $op === Symbol::$sym_lshift ){
				$r = $r->leftShift($globals->logger, $scanner->here(), $t);
			} else {
				$r = $r->rightShift($globals->logger, $scanner->here(), $t);
			}
		}
		return $r;
	}


	/**
	 * Parses weak sorting operators.
	 * @param Globals $globals
	 * @return Result
	 */
	private static function e13($globals){
		$scanner = $globals->curr_pkg->scanner;
		$r = self::e14($globals);
		switch( $scanner->sym->__toString() ){
		case "sym_lt":  $n = "<";  break;
		case "sym_le":  $n = "<=";  break;
		case "sym_gt":  $n = ">";  break;
		case "sym_ge":  $n = ">=";  break;
		default: return $r;
		}
		$scanner->readSym();
		return $r->weakCompare($globals->logger, $scanner->here(), self::e14($globals), $n);
	}


	/**
	 * Parses weak and strong equality operators.
	 * @param Globals $globals
	 * @return Result
	 */
	private static function e12($globals){
		$scanner = $globals->curr_pkg->scanner;
		$r = self::e13($globals);
		switch( $scanner->sym->__toString() ){

		case "sym_eq":
			$scanner->readSym();
			return $r->weakCompare($globals->logger, $scanner->here(), self::e13($globals), "==");

		case "sym_ne":
			$scanner->readSym();
			return $r->weakCompare($globals->logger, $scanner->here(), self::e13($globals), "!=");
		
		case "sym_spaceship":
			if( $globals->php_ver === PhpVersion::$php5 )
				$globals->logger->error($scanner->here (), "spaceship operator `<=>' available only in PHP 7");
			$scanner->readSym();
			return $r->spaceshipCompare($globals->logger, $scanner->here(), self::e13($globals));

		case "sym_eeq":
			$scanner->readSym();
			return $r->strongCompare($globals->logger, $scanner->here(), self::e13($globals), "===");
			
		case "sym_nee":
			$scanner->readSym();
			return $r->strongCompare($globals->logger, $scanner->here(), self::e13($globals), "!==");

		default:
			return $r;
		}
	}


	/**
	 * Parses bitwise "&amp;".
	 * @param Globals $globals
	 * @return Result
	 */
	private static function e11($globals){
		$scanner = $globals->curr_pkg->scanner;
		$r = self::e12($globals);
		while( $scanner->sym === Symbol::$sym_bit_and ){
			$scanner->readSym();
			$r = $r->bitAnd($globals->logger, $scanner->here(), self::e12($globals));
		}
		return $r;
	}


	/**
	 * Parses bitwise "^".
	 * @param Globals $globals
	 * @return Result
	 */
	private static function e10($globals){
		$scanner = $globals->curr_pkg->scanner;
		$r = self::e11($globals);
		while( $scanner->sym === Symbol::$sym_bit_xor ){
			$scanner->readSym();
			$r = $r->bitXor($globals->logger, $scanner->here(), self::e11($globals));
		}
		return $r;
	}


	/**
	 * Parses bitwise "|".
	 * @param Globals $globals
	 * @return Result
	 */
	private static function e9($globals){
		$scanner = $globals->curr_pkg->scanner;
		$r = self::e10($globals);
		while( $scanner->sym === Symbol::$sym_bit_or ){
			$scanner->readSym();
			$r = $r->bitOr($globals->logger, $scanner->here(), self::e10($globals));
		}
		return $r;
	}


	/**
	 * Parses logic "&amp;&amp;".
	 * @param Globals $globals
	 * @return Result
	 */
	private static function e8($globals){
		$scanner = $globals->curr_pkg->scanner;
		$r = self::e9($globals);
		while( $scanner->sym === Symbol::$sym_and ){
			$scanner->readSym();
			$r = $r->booleanAnd($globals->logger, $scanner->here(), self::e9($globals), "&&");
		}
		return $r;
	}


	/**
	 * Parses logic "||". About the name of this method: there are now 23 level
	 * of precedence in PHP and counting, and I just had to add onother one in
	 * between :-)
	 * @param Globals $globals
	 * @return Result
	 */
	private static function e7_5($globals){
		$scanner = $globals->curr_pkg->scanner;
		$r = self::e8($globals);
		while( $scanner->sym === Symbol::$sym_or ){
			$scanner->readSym();
			$r = $r->booleanOr($globals->logger, $scanner->here(), self::e8($globals), "||");
		}
		return $r;
	}


	/**
	 * Parses coalesce operator "??".
	 * @param Globals $globals
	 * @return Result
	 */
	private static function e7($globals){
		$scanner = $globals->curr_pkg->scanner;
		$a = self::e7_5($globals);
		if( $scanner->sym === Symbol::$sym_coalesce ){
			$here = $scanner->here();
			if( $globals->php_ver === PhpVersion::$php5 )
				$globals->logger->error($here, "the null coalesce operator `??' is not available under PHP 5");
			$scanner->readSym();
			$b = self::e7($globals);
			if( ! $a->getType()->equals($b->getType()) ){
				$globals->logger->error($here, "`EXPR1 ?? EXPR2': type mismatch: EXPR1 is "
				. $a->getType() . ", EXPR2 is " . $b->getType());
			}
			$a = Result::factory($a->getType());
		}
		return $a;
	}


	/**
	 * Parses ternary operator E?B:C.
	 * @param Globals $globals
	 * @return Result
	 */
	private static function e6($globals){
		$scanner = $globals->curr_pkg->scanner;
		$r = self::e7($globals);
		while( $scanner->sym === Symbol::$sym_question ){
			$r->checkExpectedType($globals->logger, $scanner->here(), Globals::$boolean_type);
			$scanner->readSym();
			if( $scanner->sym === Symbol::$sym_colon ){
				throw new ParseException($scanner->here(), "unsupported short ternary operator ?:");
			}
			$a = self::e7($globals);
			$globals->expect(Symbol::$sym_colon, "expected `:'");
			$scanner->readSym();
			$b = self::e7($globals);
			if( ! $a->getType()->equals($b->getType()) ){
				$globals->logger->error($scanner->here(), "`...? EXPR1 : EXPR2': type mismatch: EXPR1 is "
				. $a->getType() . ", EXPR2 is " . $b->getType());
			}
			if( $r->isTrue() )
				$r = $a;
			else if( $r->isFalse() )
				$r = $b;
			else
				$r = Result::factory($a->getType());
		}
		return $r;
	}


	/**
	 * Parse logic "and".
	 * @param Globals $globals
	 * @return Result
	 */
	private static function e4($globals){
		$scanner = $globals->curr_pkg->scanner;
		$r = self::e6($globals);
		while( $scanner->sym === Symbol::$sym_and2 ){
			$scanner->readSym();
			$r = $r->booleanAnd($globals->logger, $scanner->here(), self::e6($globals), "and");
		}
		return $r;
	}


	/**
	 * Parses logic "xor".
	 * @param Globals $globals
	 * @return Result
	 */
	private static function e3($globals){
		$scanner = $globals->curr_pkg->scanner;
		$r = self::e4($globals);
		while( $scanner->sym === Symbol::$sym_xor ){
			$scanner->readSym();
			$r = $r->booleanXor($globals->logger, $scanner->here(), self::e4($globals));
		}
		return $r;
	}

	
	/**
	 * Parses a static expression. At this higher level its form is "x or y or z...".
	 * @param Globals $globals
	 * @return Result
	 */
	public static function parse($globals){
		$scanner = $globals->curr_pkg->scanner;
		$r = self::e3($globals);
		while( $scanner->sym === Symbol::$sym_or2 ){
			$scanner->readSym();
			$r = $r->booleanOr($globals->logger, $scanner->here(), self::e3($globals), "or");
		}
		return $r;
	}
	
	
}
