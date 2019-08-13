<?php

namespace it\icosaedro\lint\expressions;
require_once __DIR__ . "/../../../../all.php";
use it\icosaedro\lint\types\Type;
use it\icosaedro\lint\types\UnknownType;
use it\icosaedro\lint\types\TypeDescriptor;
use it\icosaedro\lint\Globals;
use it\icosaedro\lint\Symbol;
use it\icosaedro\utils\Strings;

/**
 * Parses the <code>cast(T,E)</code> magic function.
 * @author Umberto Salsi <salsi@icosaedro.it>
 * @version $Date: 2016/03/07 10:38:20 $
 */
class CastFunction {
	
	
	/**
	 * Checks if T is an allowed type. Void and null are not allowed.
	 * Array must always indicate the key type (integer, string or both).
	 * @param string $t Value of T.
	 * @return string Empty string, or the reason why this type isn't allowed.
	 */
	private static function checkType($t)
	{
		// void and null are not allowed:
		if( $t === "void"
		|| Strings::endsWith($t, "]void")
		|| Strings::startsWith($t, "void[") )
			return "void not allowed in cast()";
		if( $t === "null"
		|| Strings::endsWith($t, "]null")
		|| Strings::startsWith($t, "null[") )
			return "null not allowed in cast()";
		
		// Absolute class names not allowed:
		if( Strings::startsWith($t, "\\")
		|| strpos($t, "]\\") !== FALSE )
			return "absolute class name not allowed in cast()";
		
		// Generics not allowed:
		if( strpos($t, "<") !== FALSE )
			return "forbidden cast to generic type: $t";
		
		return "";
	}
	
	
	/**
	 * Parses the <code>cast(T,E)</code> magic function.
	 * @param Globals $globals
	 * @return Type
	 */
	public static function parse($globals)
	{
		$pkg = $globals->curr_pkg;
		$scanner = $pkg->scanner;
		$logger = $globals->logger;
		
		$scanner->readSym();

		/*
		 * Parses T.
		 * T must be a static expr only in the user's code. cast() is used also
		 * in its actual runtime implementation, where T is a variable.
		 * When PHPLint parses the implementation itself of the cast() function
		 * we must relax some requirement: T may not be statically evaluable and
		 * in general any cast from any type to any type is allowed.
		 */
		$inside_cast = $pkg->curr_func !== NULL
			&& $pkg->curr_func->name->equals(Globals::$CAST_FQN);
		if( $inside_cast ){
			// Inside cast() itself, T can be any expression, possibly
			// including variable parts: anything allowed there:
			/* $ignore = */ Expression::parse($globals);
			$t = Globals::$unknown_type;
			
		} else {
			// Called in user code: T must be a statically determinable string:
			$r = Expression::parse($globals);
			$t = Globals::$unknown_type;
			
			if( $r->isUnknown() ){
				//
			} else if( ! $r->isString() ){
				$logger->error($scanner->here(),
				"invalid type: expected string but found $t");

			} else if( $r->getValue() === NULL ){
				$logger->error($scanner->here(),
				"cannot evaluate type descriptor statically");

			} else {
				$t = TypeDescriptor::parse($logger, $scanner->here(),
					$r->getValue(), FALSE, $globals, TRUE);
				if( $t instanceof UnknownType ){
					// error already signaled
				} else if( ! $t->isRealOrFullyActualized() ){
					// detects C<T> and T (format type):
					$logger->error($scanner->here(), "forbidden cast to generic type: $t");
				} else {
					$err = self::checkType($r->getValue());
					if( $err !== "" ){
						$logger->error($scanner->here(), $err);
					}
				}
			}
		}

		$globals->expect(Symbol::$sym_comma, "expected `,'");
		$scanner->readSym();
		
		// Parses E:
		$e = Expression::parse($globals);
		if( ! ($inside_cast || $e->getType()->canCastTo($t)) ){
			$logger->error($scanner->here(),
				"forbidden cast from " . $e->getType() . " to $t");
		}

		$globals->expect(Symbol::$sym_rround, "expected `)'");
		$scanner->readSym();
		return $t;
	}

}
