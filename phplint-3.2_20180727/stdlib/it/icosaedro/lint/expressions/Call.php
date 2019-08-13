<?php

namespace it\icosaedro\lint\expressions;

require_once __DIR__ . "/../../../../all.php";

use it\icosaedro\lint\Globals;
use it\icosaedro\lint\Signature;
use it\icosaedro\lint\Where;
use it\icosaedro\lint\Symbol;
use it\icosaedro\lint\CaseInsensitiveString;
use it\icosaedro\lint\types\Type;
use it\icosaedro\lint\types\ClassType;
use it\icosaedro\lint\types\GuessType;
use it\icosaedro\lint\types\ArrayType;
use it\icosaedro\lint\ParseException;
use it\icosaedro\lint\TriggerErrors;
use it\icosaedro\lint\ThrowExceptions;

/**
 * Parses function call and method call.
 * @author Umberto Salsi <salsi@icosaedro.it>
 * @version $Date: 2016/01/14 08:57:14 $
 */
class Call {

	/*.

	forward public static Type function parseFuncCall(
		Globals $globals, string $name);

	forward public static Type function parseMethodCall(
		Globals $globals, ClassType $c, CaseInsensitiveString $name,
		boolean $static_access, boolean $from_new_operator = );

	.*/
	

	/**
	 * Parse actual arguments of a function or method.
	 * @param Globals $globals
	 * @param string $name Name of the function/method for error reporting.
	 * @param Signature $sign Signature of the function/method.
	 * @param Where $decl_in Where the function/method is declared.
	 * @return void
	 */
	private static function parseActualArgs($globals, $name, $sign, $decl_in)
	{
		$scanner = $globals->curr_pkg->scanner;
		$scanner->readSym(); # Skip '('
		$logger = $globals->logger;

		if ($scanner->sym === Symbol::$sym_rround) {
			if ($sign->mandatory > 0) {
				$here = $scanner->here();
				$logger->error($here, "$name declared in "
				. $logger->reference($here, $decl_in)
				. " requires arguments");
			}
			$scanner->readSym();
			return;
		}
		$i = 0;
		do {
			if ($scanner->sym === Symbol::$sym_ellipsis) {
				$logger->error($scanner->here(), "parameters unpacking `...' not supported (PHPLint limitation)");
				$scanner->readSym();
			}
			if ($i < count($sign->arguments)) {
				$a = $sign->arguments[$i];
				if ($a->reference) {
					Assignable::parse($globals, $a->type, $a->reference_return);
				} else {
					$r = Expression::parse($globals);
					$lhs = $a->type;
					if ( ! $r->assignableTo($lhs)){
						$here = $scanner->here();
						$rhs = $r->getType();
						$logger->error($here, "calling $name declared in "
						. $logger->reference($here, $decl_in)
						. ", argument no. " . ($i + 1)
						. ": found type $rhs is not assignment compatible with \$" . $a->name . " of type $lhs");
					}
				}
			} else if( $sign->variadic !== NULL ){
				$a = $sign->variadic;
				if ($a->reference) {
					Assignable::parse($globals, $a->type, $a->reference_return);
				} else {
					$r = Expression::parse($globals);
					$lhs = cast(ArrayType::class, $a->type)->getElem();
					if ( ! $r->assignableTo($lhs)){
						$here = $scanner->here();
						$rhs = $r->getType();
						$logger->error($here, "calling $name declared in "
						. $logger->reference($here, $decl_in)
						. ", argument no. " . ($i + 1)
						. ": found type $rhs is not assignment compatible with \$" . $a->name . " of type $lhs");
					}
				}
			} else {
				if ($i == count($sign->arguments) && !$sign->more_args) {
					$here = $scanner->here();
					$logger->error($here, "$name declared in "
					. $logger->reference($here, $decl_in)
					. ": too many arguments");
				}
				$r = Expression::parse($globals);
			}
			if ($scanner->sym === Symbol::$sym_comma) {
				$scanner->readSym();
				$i++;
			} else {
				break;
			}
		} while (TRUE);

		if ($scanner->sym === Symbol::$sym_rround) {
			if ($i + 1 < $sign->mandatory) {
				$here = $scanner->here();
				$logger->error($here, "$name declared in "
				. $logger->reference($here, $decl_in)
				. " requires more arguments");
			}
			$scanner->readSym();
		} else {
			throw new ParseException($scanner->here(), "unexpected symbol " . $scanner->sym);
		}
	}

	/**
	 * Parses a function call. We enter with symbol "(".
	 * @param Globals $globals
	 * @param string $name Name of the function as scanned from the source.
	 * Might require NS resolution.
	 * @return Type
	 */
	public static function parseFuncCall($globals, $name)
	{
		$pkg = $globals->curr_pkg;
		$scanner = $pkg->scanner;
		$logger = $globals->logger;
		$f = $globals->searchFunc($name);
		if ($f === NULL) {
			$logger->error($scanner->here(),
			"unresolved function $name");
			return SkipUnknown::anything($globals);
		}

		$globals->accountFunction($f);
		
		TriggerErrors::all($globals, $f->sign->errors);
		ThrowExceptions::all($globals, $f->sign->exceptions);
		
		// Detect call to special function:
		if ( $f->name->equals(Globals::$CAST_FQN) ) {
			$t = CastFunction::parse($globals);
		
		} else {
			// Handle generic call to function:
			self::parseActualArgs($globals, $f->name->__toString(), $f->sign, $f->decl_in);

			$t = $f->sign->returns;
			if( $t instanceof GuessType ){
				$logger->error($scanner->here(),
				"return type of the function $f has not been determined yet. Hint: declare explicitly the returned type using DocBlock `@return TYPE' or PHPLint meta-code `/*. TYPE .*/ function f(...){}'.");
				$t = Globals::$unknown_type;
			}
		}

		if( $scanner->sym === Symbol::$sym_arrow ){
			$t = Dereference::parse($globals, $t, FALSE);
		} else if( $scanner->sym === Symbol::$sym_lsquare ){
			$t = Dereference::parse($globals, $t, FALSE);
		}
		
		return $t;
	}
	

	/**
	 * Parses a method call. We enter with sym_lround.
	 * @param Globals $globals
	 * @param ClassType $c
	 * @param CaseInsensitiveString $name Name of the method.
	 * @param boolean $static_access True if static access: CLASS_NAME::m(),
	 * self::m(), parent::m(); false for $instance-&gt;m().
	 * @param boolean $from_new_operator It's a direct call to constructor
	 * from the "new" operator. In fact, this method is called also after a "new"
	 * operator, which implies a call to the constructor. But there are some subtle
	 * differences with an ordinary method call this flag allows to account for.
	 * @return Type Type of the returned value.
	 */
	public static function parseMethodCall($globals, $c, $name, $static_access,
		$from_new_operator = FALSE)
	{
		if( $c === NULL )
			throw new \RuntimeException("class null");
		$pkg = $globals->curr_pkg;
		$scanner = $pkg->scanner;
		$logger = $globals->logger;
		$m = $c->searchMethod($name);
		if( $m === NULL ){
			$logger->error($scanner->here(),
			"unknown method $c::$name");
			return SkipUnknown::anything($globals);
		}
		
		$globals->checkSpellMethod($m, $name);

		$globals->accountMethod($m);
		
		if( $static_access && ! $m->is_static && ! $globals->isNonStaticContextOf($c) )
			$logger->error($scanner->here(),
			"static access to non-static method $m is allowed only from non-static context of its class or subclass");
		else if( $static_access && $m->is_abstract )
			$logger->error($scanner->here(), "static access to abstract method $m");
		
		# Detects forbidden direct call to a constructor:
		if ($m->is_constructor && ! $from_new_operator
		&& (
			$pkg->curr_class === NULL  # not inside a class
			|| !$pkg->curr_method->is_constructor  # not inside a constructor
			|| !$pkg->curr_class->isSubclassOf($m->class_) # not the constr. of a derived class
		)) {
			$logger->error($scanner->here(),
			"the method $m is a class constructor, it can be called explicity only inside the constructor of an extending class");
		}

		# Remember call to the parent constructor from the extending class:
		if ($m->is_constructor && ! $from_new_operator
		&& $pkg->curr_class !== NULL
		&& $pkg->curr_method === $pkg->curr_class->constructor
		) {
			$pkg->curr_class->parent_constructor_called = TRUE;
		}

		# Check direct call to a destructor:
		if ($m->is_destructor   # direct call to a destructor
		&& (
			$pkg->curr_class === NULL  # not inside a class
			|| $pkg->curr_method !== $pkg->curr_class->destructor  # not inside a destructor
			|| ! $pkg->curr_class->isSubclassOf($m->class_) # not the destr. of a derived class
		)) {
			$logger->error($scanner->here(),
			"the method $m is a destructor, it can be called explicity only inside the destructor of a derived class");
		}

		# Remember call to the parent destructor from the overridding one:
		if ($m->is_destructor
				&& $pkg->curr_class !== NULL
				&& $pkg->curr_method->is_destructor
		) {
			$pkg->curr_class->parent_destructor_called = TRUE;
		}

		self::parseActualArgs($globals, $m->__toString(), $m->sign, $m->decl_in);

		
		$t = $m->sign->returns;
		if( $t instanceof GuessType ){
			$logger->error($scanner->here(),
			"return type of the method $m has not been determined yet. Hint: declare explicitly the returned type using DocBlock `@return TYPE' or PHPLint meta-code `/*. TYPE .*/ function m(...){}'.");
			$t = Globals::$unknown_type;
		}
		
		if( $scanner->sym === Symbol::$sym_arrow ){
			$t = Dereference::parse($globals, $t, FALSE);
		} else if( $scanner->sym === Symbol::$sym_lsquare ){
			$t = Dereference::parse($globals, $t, FALSE);
		}
		
		TriggerErrors::all($globals, $m->sign->errors);
		ThrowExceptions::all($globals, $m->sign->exceptions);
		
		return $t;
	}

}