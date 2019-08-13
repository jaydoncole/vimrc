<?php

namespace it\icosaedro\lint\statements;

require_once __DIR__ . "/../../../../all.php";

use it\icosaedro\lint\Globals;
use it\icosaedro\lint\Symbol;
use it\icosaedro\lint\Visibility;
use it\icosaedro\lint\docblock\DocBlockWrapper;
use it\icosaedro\lint\types\Type;
use it\icosaedro\lint\Result;
use it\icosaedro\lint\expressions\StaticExpression;
use it\icosaedro\lint\types\ClassProperty;

/**
 * Parses a property declaration.
 * @author Umberto Salsi <salsi@icosaedro.it>
 * @version $Date: 2016/02/09 00:04:54 $
 */
class ClassPropertyStatement {

	/**
	 * Parses a property declaration.
	 * We enter with sym_variable.
	 * @param Globals $globals Context of the parser.
	 * @param DocBlockWrapper $dbw DocBlock wrapper, possibly containing the
	 * empty DocBlock if not available.
	 * @param Visibility $visibility Visibility modifier.
	 * @param boolean $is_static Found static modifier.
	 * @param Type $t Type declared in meta-code, or NULL if not available.
	 * @return void
	 */
	public static function parse($globals, $dbw, $visibility, $is_static, $t)
	{
		$pkg = $globals->curr_pkg;
		$scanner = $pkg->scanner;
		$logger = $globals->logger;

		$c = $pkg->curr_class;

		// Scans list of properties:
		do {
			$globals->expect(Symbol::$sym_variable, "expected property name \$NAME");
			$name = $scanner->s;
			$here = $scanner->here();

			$p = $c->searchProperty($name);
			if( $p !== NULL ){
				// private properties are distinct from non-private
				// properties, so derived classes can re-define inherited
				// private properties. Then an object may store several props
				// with same name "p": the inherited private ones from parent
				// classes, and the local one.
				if( $p->class_ === $c )
					$logger->error($scanner->here(),
					"property $p already defined in "
					. $logger->reference($here, $p->decl_in));
				else if( $p->visibility !== Visibility::$private_ )
					$logger->error($scanner->here(),
					"cannot redefine inherited non-private property $p");
			}

			$p = new ClassProperty($here, $c, $dbw->getDocBlock(), $is_static, $visibility, $name, Result::getUnknown());

			if (!$globals->report_unused || $pkg->is_module)
				$p->used = 100;

			$scanner->readSym(); // skip name
			# Get property initial value:
			if ($scanner->sym === Symbol::$sym_assign) {
				$scanner->readSym();
				$r = StaticExpression::parse($globals);
				if ($t === NULL) {
					$p->value = $r;
					if ($r->isNull())
						$logger->error($scanner->here(),
						"cannot guess property's type from bare null value."
						."\nRequired either explicit type declaration, example:"
						."\n\t/*. string .*/ public \$x = NULL;"
						."\nor formal typecast on bare null value, example:"
						."\n\tpublic \$x = /*. (string) .*/ NULL;");
					if( $r->isArray() && $r->isEmptyArray() && $globals->logger->print_notices )
						$logger->notice($scanner->here(),
						"guessed property's type mixed[] from bare empty array value."
						."\nRecommended either type declaration, example:"
						."\n\t/** @var string[int] */ public \$x = array(); // array of strings with int index"
						."\nor formal typecast on empty array, examples:"
						."\n\tpublic \$x = /*. (MyClass[string]) .*/ array(); // associative array of MyClass objects"
						."\n\tpublic \$x = /*. (float[int][int]) .*/ array(); // matrix of floating-point numbers");
				} else {
					if ($r->assignableTo($t)) {
						$p->value = Result::factory($t, $r->getValue());
					} else {
						$p->value = Result::factory($t);
						$logger->error($scanner->here(), "incompatible value of type " . $r->getType());
					}
				}
			} else if ($t === NULL) {
				$logger->error($scanner->here(), "undefined type for property `\$$name'. Hint: you may indicate an explicit type (example: `/*.int.*/ \$$name') or assign a default value (example: `\$$name=123') or add a DocBlock line tag (example: `@var int').");
			} else {
				$p->value = Result::factory($t);
				if ( ! Globals::$null_type->assignableTo($t)) {
					$logger->error($scanner->here(), "property \$$name of type $t requires an initial value, otherwise it would be initialized to the invalid value NULL at runtime (PHPLint safety restriction)");
				}
			}

			$c->properties[$p->name] = $p;
			
			if( $is_static && ! $p->value->getType()->isRealOrFullyActualized() )
				$logger->error($here, "static property cannot be a partially actualized class");

			# More properties in list?
			if ($scanner->sym === Symbol::$sym_comma) {
				$scanner->readSym();
			} else {
				break;
			}
		} while (TRUE);

		$globals->expect(Symbol::$sym_semicolon, "expected ';'");
		$scanner->readSym();
	}

}
