<?php

namespace it\icosaedro\lint\expressions;
require_once __DIR__ . "/../../../../all.php";
use it\icosaedro\lint\Symbol;
use it\icosaedro\lint\types\ClassType;
use it\icosaedro\lint\types\ArrayType;
use it\icosaedro\lint\Globals;
use it\icosaedro\lint\Result;
use it\icosaedro\lint\ParseException;
use it\icosaedro\lint\TypeDecl;
use it\icosaedro\lint\statements\ClassStatement;
use it\icosaedro\lint\FullyQualifiedName;
use it\icosaedro\lint\types\Type;
use it\icosaedro\lint\Where;
use it\icosaedro\lint\TriggerErrors;
use it\icosaedro\lint\ThrowExceptions;
use it\icosaedro\lint\ExceptionsSet;


/**
 * Type and position of the actual parameter of the anonymous class ctor.
 * Anomymous class ctor params are collected from 'new class(p1,p2,p3){...}'
 * then checks later, once the anonymous class has been fully parsed and the
 * actual ctor is then known.
 * FIXME:
 * Unfortunately, while parsing these params we can't contextually signal errors,
 * nor we can recognize params passed by reference (these latter cannot be supported
 * with this strategy).
 * @access private
 */
class NewOperator_AnonymousCtorParam {
	
	/**
	 * @var Type
	 */
	public $type;
	
	/**
	 * @var Where
	 */
	public $where;
	
	/**
	 * @param Type $type
	 * @param Where $where
	 */
	function __construct($type, $where) {
		$this->type = $type;
		$this->where = $where;
	}
}


/**
 * Parses the <code>new</code> operator.
 * @author Umberto Salsi <salsi@icosaedro.it>
 * @version $Date: 2018/06/27 08:55:23 $
 */
class NewOperator {
	
	/*. forward static Result function parse(Globals $globals); .*/
	
	/**
	 * Parse object creation out of a concrete class.
	 * @param Globals $globals
	 * @return Result
	 */
	private static function parseConcrete($globals)
	{
		$pkg = $globals->curr_pkg;
		$logger = $globals->logger;
		$scanner = $pkg->scanner;
		
		$c = TypeDecl::parseClassType($globals);
		if( $c === NULL ){
			// error already signaled
			if( SkipUnknown::canSkip($scanner->sym) )
				SkipUnknown::anything($globals);
			return Result::getUnknown();
		}

		if( $c->is_abstract ){
			$logger->error($scanner->here(), "cannot instantiate abstract class $c");
		}

		if( $c->is_interface ){
			$logger->error($scanner->here(), "cannot instantiate interface class $c");
		}
		
		$globals->accountClass($c);

		/*
			Search the constructor of `class'; mark as invoked the default
			or inherited constructor of any extended class, so we may
			detect if any actual constructor gets parsed only after its
			usage:
		*/
		$ctor = $c->constructor;
		if( $ctor === NULL ){
			if( $c->constructor_first_used_here === NULL ){
				// First call to the default or inherited constructor.
				$c->constructor_first_used_here = $scanner->here();
			}
			$ctor = $c->parentConstructor();
		}

		if( $ctor === NULL ){ # no constructor for this class
			/* Invoke default constructor void(): */
			if( $scanner->sym === Symbol::$sym_lround ){
				$scanner->readSym();
				if( $scanner->sym !== Symbol::$sym_rround ){
					$logger->error($scanner->here(), "expected `)', found " . $scanner->sym
					. ". The class $c does not have a constructor, so no arguments are required");
					# skip unexpected args and continue:
					while(TRUE){
						/* $ignore = */ Expression::parse($globals);
						if( $scanner->sym === Symbol::$sym_comma ){
							$scanner->readSym();
						} else {
							break;
						}
					}
				}
				$globals->expect(Symbol::$sym_rround, "expected `)'");
				$scanner->readSym();
			}

		} else { # there is a constructor for this class
			if( $scanner->sym === Symbol::$sym_lround ){
				Call::parseMethodCall($globals, $ctor->class_, $ctor->name, FALSE, TRUE);
			} else if( $ctor->sign->mandatory > 0 ){
				$logger->error($scanner->here(),
				"missing required arguments for constructor $ctor declared in "
				. $logger->reference($scanner->here(), $ctor->decl_in));
			} else {
				$logger->notice($scanner->here(),
				"missing parentheses after class name. Although "
				. "the constructor $ctor"
				. " has no mandatory arguments, it's a good habit"
				. " to provide these parentheses.");
			}

		}
		return Result::factory($c);
	}
	
	
	private static $anonymous_class_counter = 0;
	
	/**
	 * Parse object creation out of an anonymous class.
	 * We enter with the symbol "class".
	 * @param Globals $globals
	 * @return Result
	 */
	private static function parseAnonymous($globals)
	{
		$pkg = $globals->curr_pkg;
		$logger = $globals->logger;
		$scanner = $pkg->scanner;
		$where = $scanner->here();
		
		if( $globals->isPHP(5) )
			$logger->error ($where, "anonymous classes not allowed (PHP 7)");
		
		$fqn = new FullyQualifiedName("AnonymousClass#" . (self::$anonymous_class_counter++), FALSE);
		$c = new ClassType($fqn, $where, $pkg->is_module);
		$c->is_anonymous = TRUE;
		$c->is_private = TRUE;
		$c->is_final = TRUE;
		$globals->accountClass($c);
		$scanner->readSym();
		
		// Collect actual params for the ctor we don't know yet and check later:
		$ctor_actual_params = /*. (NewOperator_AnonymousCtorParam[int]) .*/ array();
		if( $scanner->sym === Symbol::$sym_lround ){
			$scanner->readSym();
			if( $scanner->sym === Symbol::$sym_rround ){
				$scanner->readSym();
			} else {
				do {
					$r = Expression::parse($globals);
					$ctor_actual_params[] = new NewOperator_AnonymousCtorParam($r->getType(), $scanner->here());
					if( $scanner->sym === Symbol::$sym_comma )
						$scanner->readSym();
					else
						break;
				} while(TRUE);
				$globals->expect(Symbol::$sym_rround, "expected `)'");
				$scanner->readSym();
			}
		}
		
		// Save context of the parser:
		$pkg = $globals->curr_pkg;
//		$saved_curr_dockblock = $pkg->curr_docblock;  $pkg->curr_docblock = NULL;
		$saved_curr_func   = $pkg->curr_func;    $pkg->curr_func = NULL;
		$saved_curr_class  = $pkg->curr_class;   $pkg->curr_class = NULL;
		$saved_curr_method = $pkg->curr_method;  $pkg->curr_method = NULL;
		
		$saved_exceptions_set = $pkg->exceptions; $pkg->exceptions = new ExceptionsSet();
		$saved_loop_level = $pkg->loop_level;  $pkg->loop_level = "";
		// scope?
		$saved_silencer_level = $pkg->silencer_level; $pkg->silencer_level = 0;
		$saved_try_block_nesting_level =  $pkg->try_block_nesting_level;  $pkg->try_block_nesting_level = 0;
		
		// Parse anonymous class:
		ClassStatement::parseFromExtendsOn($globals, $c, NULL);
		
		// Restore context of the parser:
//		$pkg->curr_docblock = $saved_curr_dockblock;
		$pkg->curr_func     = $saved_curr_func;
		$pkg->curr_class    = $saved_curr_class;
		$pkg->curr_method   = $saved_curr_method;
		$pkg->exceptions    = $saved_exceptions_set;
		$pkg->loop_level    = $saved_loop_level;
		$pkg->silencer_level= $saved_silencer_level;
		$pkg->try_block_nesting_level = $saved_try_block_nesting_level;
		
		// Now check ctor call:
		$ctor = $c->constructor;
		if( $ctor === NULL )
			$ctor = $c->parentConstructor();
		if( $ctor === NULL ){
			if( count($ctor_actual_params) > 0 )
				$logger->error($where, "the anonymous class has no contructor, no actual parameters required");
		} else {
			$globals->accountMethod($ctor); // this also also checks access to private ctor
			$ctor_sign = $ctor->sign;
			for($i = 0; $i < count($ctor_actual_params); $i++){
				$a = $ctor_actual_params[$i];
				if( $i >= count($ctor_sign->arguments) ){
					if( $ctor_sign->more_args ){
						break;
					} else if( $ctor_sign->variadic !== NULL ){
						$variadic_type = cast(ArrayType::class, $ctor_sign->variadic->type)->getElem();
						if( ! $a->type->assignableTo($variadic_type) )
							$logger->error($a->where, "expected ".$variadic_type
								." but found ".$a->type);
					} else {
						$logger->error($a->where, "too many arguments");
					}
				} else {
					$formal = $ctor_sign->arguments[$i];
					if( $formal->reference )
						$logger->error($a->where,
						"unsupported passing method by reference to anonymous class constructor (PHPLint limitation)");
					else if( ! $a->type->assignableTo($formal->type) )
						$logger->error($a->where, "expected ".$formal->type
							." but found ".$a->type);
				}
			}
			TriggerErrors::all($globals, $ctor_sign->errors);
			ThrowExceptions::all($globals, $ctor_sign->exceptions);
		}
		
		return Result::factory($c);
	}
	
	
	/**
	 * Parses the <code>new</code> operator.
	 * @param Globals $globals
	 * @return Result
	 */
	public static function parse($globals)
	{
		$scanner = $globals->curr_pkg->scanner;
		$scanner->readSym();
		
		if( $scanner->sym === Symbol::$sym_variable ){
			throw new ParseException($globals->curr_pkg->scanner->here(),
			"variable class name not allowed (PHPLint restriction)");
		} else if( $scanner->sym === Symbol::$sym_class ){
			return self::parseAnonymous($globals);
		} else {
			return self::parseConcrete($globals);
		}
	}
	
}
