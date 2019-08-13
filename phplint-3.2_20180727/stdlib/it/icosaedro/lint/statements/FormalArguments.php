<?php

namespace it\icosaedro\lint\statements;
require_once __DIR__ . "/../../../../all.php";
use it\icosaedro\lint\Globals;
use it\icosaedro\lint\Symbol;
use it\icosaedro\lint\FormalArgument;
use it\icosaedro\lint\TypeDecl;
use it\icosaedro\lint\Signature;
use it\icosaedro\lint\Variable;
use it\icosaedro\lint\docblock\DocBlockWrapper;
use it\icosaedro\lint\expressions\StaticExpression;
use it\icosaedro\lint\ParseException;
use it\icosaedro\lint\types\ArrayType;
use it\icosaedro\lint\types\GuessType;

/**
 * Parses a list of formal arguments of function or method.
 * @author Umberto Salsi <salsi@icosaedro.it>
 * @version $Date: 2018/06/02 06:25:30 $
 */
class FormalArguments {

	/*.
	forward public static void function checkFormalArgsByReference(Globals $globals, boolean $on_return);
	forward public static void function parse(Globals $globals, DocBlockWrapper $dbw, Signature $sign);
	.*/

	/**
	 * Parses a formal argument.
	 * @param Globals $globals
	 * @param DocBlockWrapper $dbw
	 * @param boolean $opt_arg True if this argument is optional.
	 * @return FormalArgument The formal argument.
	 */
	private static function parseArg($globals, $dbw, $opt_arg)
	{
		$pkg = $globals->curr_pkg;
		$scanner = $pkg->scanner;

		$a = new FormalArgument();

		// Check `return' attribute:
		if ($scanner->sym === Symbol::$sym_x_return) {
			$a->reference_return = TRUE;
			$scanner->readSym();
		}

		// Parse type of the formal arg:
		$a->type = TypeDecl::parse($globals, TRUE);

		// Parse passing method by value or by reference:
		if ($scanner->sym === Symbol::$sym_bit_and) {
			$a->reference = TRUE;
			$scanner->readSym();
		} else {
			if ($a->reference_return) {
				$globals->logger->error($scanner->here(),
				"invalid `return' attribute for argument passed by value");
				$a->reference_return = FALSE;
			}
		}
		
		if( $scanner->sym === Symbol::$sym_ellipsis ){
			$a->is_variadic = TRUE;
			$a->is_mandatory = FALSE;
			$scanner->readSym();
			if( $a->reference ){
				$globals->logger->error($scanner->here(),
				"variadic parameter by reference is not supported (PHPLint limitation)");
				$a->reference = FALSE;
				$a->reference_return = FALSE;
			}
		}

		// Parse name of the formal arg:
		$globals->expect(Symbol::$sym_variable, "expected name of the formal argument in function declaration");
		$a->name = $scanner->s;

		// Collects DocBlock data for this parameter:
		$p = $dbw->getParameter($a->name);
		if( $p === NULL ){
//			if( $db !== NULL )
//				$db->where->error("missing parameter \$" . $a->name . " in DocBlock");

		} else if( $a->type === NULL ){
			$a->type = $p->type;
			if( $a->reference != $p->byref )
				$globals->logger->error($scanner->here(),
				"the by-reference passing mode `&' for parameter \$"
				. $a->name . ", if really required, must be indicated both in the DocBlock @param TYPE & \$VAR and in PHP code");
			if( $a->is_variadic != $p->is_variadic )
				$globals->logger->error($scanner->here(),
				"conflicting variadic passing mode `...' between DocBlock and actual code for parameter \$"
				. $a->name);
				
		} else {
			if( ! $p->type->equals($a->type) ){
				$globals->logger->error($scanner->here(),
				"the type of the parameter \$" . $a->name
				." as declared in the DocBlock differs from the type declare in-line"
				);
			}
		}

//		if ($p !== NULL) {
//			$p->used = TRUE;
//		}

		// Formal arguments are accounted as local variables.
		$v = $globals->searchVar($a->name);
		if ($v !== NULL)
			$globals->logger->error($scanner->here(),
			"duplicated parameter $" . $a->name);
		
		$v = new Variable($a->name, FALSE, $scanner->here(), $pkg->scope);
		$globals->addVar($v);
		
		$scanner->readSym();

		// Parse default value:
		$a->is_mandatory = ! $a->is_variadic;
		if ($scanner->sym === Symbol::$sym_assign) {
			if( $a->is_variadic )
				$globals->logger->error($scanner->here(), "variadic argument cannot have a default value");
			$a->is_mandatory = FALSE;
			$scanner->readSym();
			$r = StaticExpression::parse($globals);
			$a->value = $r;
			if ($r === Globals::$unknown_type) {
				// error already signaled
			} else if ($a->type === NULL) {
				$a->type = $r->getType();
			} else {
				if (!$r->assignableTo($a->type))
					$globals->logger->error($scanner->here(),
					"parameter \$" . $a->name . ": the type " . $r->getType()
					. " of the default value is incompatible with the declared type "
					. $a->type);
			}
		} else if ( ! $a->is_variadic && $opt_arg) {
			$globals->logger->error($scanner->here(),
			"missing default value for argument \$" . $a->name
			. ". Hint: mandatory arguments can't follow the default ones.");
		}

		if ($a->type === NULL) {
			$globals->logger->error($scanner->here(),
			"undefined type for argument $" . $a->name
			. ". Hint: you may"
			. " indicate an explicit type (example: `/*.int.*/ $" . $a->name . "')"
			. " or assign a default value (example: `$" . $a->name . "=123')"
			. " or add a DocBlock line tag (example: `@param int $"
			. $a->name . "').");
			$a->type = Globals::$unknown_type;
		}
		
		if( $a->is_variadic )
			$a->type = ArrayType::factory(Globals::$int_type, $a->type);
		
		$v->type = $a->type;
		
		// By-ref + default ==> implicit 'return' modifier:
		if( $a->reference && ! $a->is_mandatory )
			$a->reference_return = TRUE;
		
		$v->assigned = $v->assigned_once =
			! $a->is_mandatory
			|| ! $a->reference
			|| ! $a->reference_return;
		
		if ($pkg->is_module
		|| $pkg->curr_method !== NULL && $pkg->curr_method->is_abstract
		|| $a->reference_return
		) {
			$v->used = 100;
		}
		return $a;
	}

	
	/**
	 * Parses a list of formal args of a function or method.
	 * Formal arguments are accounted and added to the local scope, so the
	 * `scope' global variable must be properly initialized.
	 * Formal arguments passed by value are marked as assigned.
	 * Formal arguments passed by return+reference are marked as unassigned.
	 * Formal arguments passed by reference are marked as assigned.
	 * function_or_module: "function" or "method".
	 * @param Globals $globals
	 * @param DocBlockWrapper $dbw
	 * @param Signature $sign
	 * @return void
	 */
	public static function parse($globals, $dbw, $sign) {
		$pkg = $globals->curr_pkg;
		$scanner = $pkg->scanner;

		$globals->expect(Symbol::$sym_lround, "expected '(' in function declaration");
		$scanner->readSym();

		if ($scanner->sym === Symbol::$sym_rround) {
			# f().
		} else if ($scanner->sym === Symbol::$sym_x_args) {
			# f(args):
			$sign->more_args = TRUE;
			$scanner->readSym();
		} else {
			# f(one or more args):
			$opt_arg = FALSE;
			while (TRUE) {

				if ($scanner->sym === Symbol::$sym_x_args) {
					throw new ParseException($scanner->here(), "there must be a meta-code comma `,' separating the special symbol args from the other arguments");
				}

				$a = self::parseArg($globals, $dbw, $opt_arg);
				
				if( $a->is_variadic ){
					$sign->variadic = $a;
					break;
				}
				
				$sign->arguments[] = $a;
				if ($a->is_mandatory) {
					$sign->mandatory++;
				} else {
					$opt_arg = TRUE;
				}

				if ($scanner->sym === Symbol::$sym_comma) {
					$scanner->readSym();
				} else if ($scanner->sym === Symbol::$sym_x_comma) {
					$scanner->readSym();
					$globals->expect(Symbol::$sym_x_args, "expected `args'");
					$sign->more_args = TRUE;
					$scanner->readSym();
					break;
				} else {
					break;
				}
			}
		}
		
		// Check all DocBlock @param are actual arguments:
		$db = $dbw->getDocBlock();
		if( $db !== NULL && $db->params !== NULL && count($db->params) > 0 ){
			foreach($db->params as $param){
				$found = FALSE;
				foreach($sign->arguments as $arg){
					if( $arg->name === $param->name ){
						$found = TRUE;
						break;
					}
				}
				if( ! $found && $sign->variadic !== NULL && $sign->variadic->name === $param->name )
					$found = true;
				if( ! $found ){
					$globals->logger->error($db->decl_in,
					"@param \$" . $param->name . " is not an argument");
				}
			}
		}

		$globals->expect(Symbol::$sym_rround, "expected ')' or ',' in function declaration");
		$scanner->readSym();
		
		// PHP 7 return type declaration:
		if( $scanner->sym === Symbol::$sym_colon ){
			if( ! $globals->isPHP(7) )
				$globals->logger->error($scanner->here(), "return type declaration not allowed (PHP 7)");
			$scanner->readSym();
			$t = TypeDecl::parse($globals, TRUE, FALSE);
			if( $t === NULL ){
				$globals->logger->error($scanner->here(), "missing return type");
			} else {
				if( $sign->returns !== NULL
				&& ! $sign->returns instanceof GuessType
				&& ! $sign->returns->equals($t) )
					$globals->logger->error($scanner->here(), "return type differs from that already determined in DocBlock or meta-code");
				$sign->returns = $t;
			}
		}
	}
	
	
	
	/**
	 * Checks that all the formal arguments passed by reference and with the
	 * `return' attribute be actually assigned. This check should be performed
	 * after every `return' statement and at the end of the function body or
	 * method body (if the end of the body can be reached).
	 * @param Globals $globals
	 * @param boolean $on_return Set to true if the check occurs on a `return'
	 * statement, false if the check occurs at the end of the body of the
	 * function or method. On return, the error message makes reference to the
	 * return statement, otherwise the error message makes reference to the
	 * position where the formal argument is declared.
	 * @return void
	 */
	public static function checkFormalArgsByReference($globals, $on_return)
	{
		$pkg = $globals->curr_pkg;
		if( $pkg->is_module )
			return;
		$scanner = $pkg->scanner;
		$logger = $globals->logger;
		if( $pkg->curr_func !== NULL ){
			$arguments = $pkg->curr_func->sign->arguments;
		} else if( $pkg->curr_method !== NULL ){
			$arguments = $pkg->curr_method->sign->arguments;
		} else {
			return;
		}
		if( $arguments === NULL )
			return;
		foreach($arguments as $a){
			if( $a->reference_return ){
				$v = $globals->searchVarInScope($a->name, $pkg->scope);
				if( ! $v->assigned ){
					
					if( $on_return )
						$where = $scanner->here();
					else
						$where = $v->decl_in;
					$logger->error($where, "formal argument that returns by reference $v might not have been assigned");
				}
			}
		}
	}

}