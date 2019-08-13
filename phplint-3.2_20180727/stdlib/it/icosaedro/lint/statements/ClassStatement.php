<?php

namespace it\icosaedro\lint\statements;

require_once __DIR__ . "/../../../../all.php";

use it\icosaedro\lint\Globals;
use it\icosaedro\lint\Symbol;
use it\icosaedro\lint\Visibility;
use it\icosaedro\lint\FullyQualifiedName;
use it\icosaedro\lint\NamespaceResolver;
use it\icosaedro\lint\TypeDecl;
use it\icosaedro\lint\ParseException;
use it\icosaedro\lint\docblock\DocBlockScanner;
use it\icosaedro\lint\docblock\DocBlockWrapper;
use it\icosaedro\lint\types\ClassType;
use it\icosaedro\lint\types\ClassMethod;

/**
 * Parses class declaration.
 * @author Umberto Salsi <salsi@icosaedro.it>
 * @version $Date: 2018/06/01 14:32:13 $
 */
class ClassStatement {

	/*.
	forward public static void function
		parseFromExtendsOn(Globals $globals, ClassType $c, ClassType $proto);
	forward public static void function
		parse(Globals $globals, boolean $is_private);
	.*/


	/**
	 * Check implementation of iterators.
	 * @param Globals $globals
	 * @param ClassType $c
	 * @return void
	 */
	private static function checkTraversableUsage($globals, $c)
	{
		if (!$c->isSubclassOf($globals->builtin->TraversableClass))
			return;

		$is_iterator = $c->isSubclassOf($globals->builtin->IteratorClass);
		$is_iterator_aggregate = $c->isSubclassOf($globals->builtin->IteratorAggregateClass);

		if ($is_iterator && $is_iterator_aggregate)
			$globals->logger->error($c->decl_in,
			"cannot implement both Iterator and IteratorAggregate");

		if ($is_iterator || $is_iterator_aggregate)
			// OK: Traversable with one of the real implementations
			return;

		if( ! $globals->curr_pkg->is_module )
			$globals->logger->error($c->decl_in, "classes that implement `Traversable' must also either implement `Iterator' or `IteratorAggregate'");
	}
	
		
	/**
	 * Parses class constant, property or method.
	 * @param Globals $globals
	 * @return void
	 */
	private static function parseMember($globals)
	{
		$pkg = $globals->curr_pkg;
		$logger = $globals->logger;
		$scanner = $pkg->scanner;
		$c = $pkg->curr_class;
		
		if ($scanner->sym === Symbol::$sym_x_forward) {
			ForwardStatement::parse($globals);
			return;
		}

		// Get DocBlock, or use default values from DocBlock wrapper:
		if ($scanner->sym === Symbol::$sym_x_docBlock) {
			if( $globals->parse_phpdoc ){
				$db = DocBlockScanner::parse($globals->logger, $scanner->here(), $scanner->s, $globals);
				$dbw = new DocBlockWrapper($globals->logger, $db);
			} else {
				$dbw = DocBlockWrapper::getEmpty();
			}
			$scanner->readSym();
		} else {
			$dbw = DocBlockWrapper::getEmpty();
		}

		# Class consts may have an @access line-tag:
		$db_visibility = /*.(Visibility).*/ NULL;
		if ($dbw->isPrivate())
			$db_visibility = Visibility::$private_;
		else if ($dbw->isProtected())
			$db_visibility = Visibility::$protected_;
		else if ($dbw->isPublic())
			$db_visibility = Visibility::$public_;

		# PHP modifiers:
		$visibility = /*.(Visibility).*/ NULL;
		$is_abstract = FALSE;
		$is_static = FALSE;
		$is_final = FALSE;

		// Loops over modifiers of this member:
		$done = FALSE;
		do {

			switch ($scanner->sym->__toString()) {

				case "sym_x_public":
					if ($db_visibility !== NULL)
						$logger->error($scanner->here(),
						"visibility modifier already set");
					$db_visibility = Visibility::$public_;
					break;

				case "sym_x_protected":
					if ($db_visibility !== NULL)
						$logger->error($scanner->here(),
						"visibility modifier already set");
					$db_visibility = Visibility::$protected_;
					break;

				case "sym_x_private":
					if ($db_visibility !== NULL)
						$logger->error($scanner->here(),
						"visibility modifier already set");
					$db_visibility = Visibility::$private_;
					break;

				case "sym_abstract":
					if ($is_abstract)
						$logger->error($scanner->here(),
						"abstract modifier already set");
					$is_abstract = TRUE;
					break;

				case "sym_public":
					if ($visibility !== NULL)
						$logger->error($scanner->here(),
						"visibility modifier already set");
					$visibility = Visibility::$public_;
					break;

				case "sym_protected":
					if ($visibility !== NULL)
						$logger->error($scanner->here(),
						"visibility modifier already set");
					$visibility = Visibility::$protected_;
					break;

				case "sym_private":
					if ($visibility !== NULL)
						$logger->error($scanner->here(),
						"visibility modifier already set");
					$visibility = Visibility::$private_;
					break;

				case "sym_static":
					if ($is_static)
						$logger->error($scanner->here(),
						"static modifier already set");
					$is_static = TRUE;
					break;

				case "sym_final":
					if ($is_final)
						$logger->error($scanner->here(),
						"final modifier already set");
					$is_final = TRUE;
					break;

				default:
					$done = TRUE;
			}

			if ($done)
				break;
			else
				$scanner->readSym();
		} while (TRUE);

		# Parses type:
		$meta_type = TypeDecl::parse($globals, FALSE);
		$db_type = $dbw->getVarType();
		if ($db_type === NULL)
			$db_type = $dbw->getReturnType();
		if ($meta_type !== NULL && $db_type !== NULL) {
			$logger->error($scanner->here(),
			"type declaration both in DocBlock and PHPLint meta-code");
		}
		if ($meta_type === NULL)
			$meta_type = $db_type;

		# Const:
		if ($scanner->sym === Symbol::$sym_const) {
			$dbw->checkLineTagsForClassConstant();
			if ($meta_type !== NULL) {
				$logger->error($scanner->here(),
				"explicit type declaration not allowed for class constant");
			}
			// check class const attributes:
			if( $globals->isPHP(5) ){
				if ($is_abstract || $visibility !== NULL || $is_static || $is_final)
					$logger->error($scanner->here(), "invalid modifiers. Only /*.public|protected|private.*/ is allowed for class constant.");
				if ($db_visibility === NULL)
					$visibility = Visibility::$public_;
				else
					$visibility = $db_visibility;
				
			} else {
				if( $db_visibility !== NULL ){
					// FIXME: allows @access for const in PHP 7, by now raises "notice" for BC PHP 5
					$logger->notice($scanner->here(),
					"@access line-tag not allowed (PHP 5 only)");
					if( $visibility === NULL )
						$visibility = $db_visibility;
				}
				if( $visibility === NULL )
					$visibility = Visibility::$public_;
				if ($is_abstract || $is_static || $is_final)
					$logger->error($scanner->here(), "invalid modifiers. Only public|protected|private is allowed for class constant.");
			}
			ClassConstantStatement::parse($globals, $dbw, $visibility);

		} else if ($scanner->sym === Symbol::$sym_var) {
			$dbw->checkLineTagsForProperty();
			$logger->error($scanner->here(), "invalid modifier `var', use `public'");
			$scanner->readSym();
			if ($meta_type === NULL)
				$meta_type = TypeDecl::parse($globals, FALSE);
			$globals->expect(Symbol::$sym_variable,
			"expected property name \$xxx");
			if ($db_visibility !== NULL)
				$visibility = $db_visibility;
			if ($visibility === NULL)
				$visibility = Visibility::$public_;
			ClassPropertyStatement::parse($globals, $dbw, $visibility, $is_static, $meta_type);

		# Property:
		} else if ($scanner->sym === Symbol::$sym_variable) {
			$dbw->checkLineTagsForProperty();
			if ($db_visibility !== NULL) {
				$logger->error($scanner->here(), "cannot use meta-code or DocBlock @access line-tag to set visibility, use proper language keywords");
			} else if ($is_abstract || $is_final) {
				$logger->error($scanner->here(), "properties cannot be abstract nor final");
			} else if ($visibility === NULL && !$is_static) {
				$logger->error($scanner->here(),
				"property requires visibility modifier or static modifier");
			}
			ClassPropertyStatement::parse($globals, $dbw,
				$visibility === NULL? Visibility::$public_ : $visibility,
				$is_static, $meta_type);

		# Method:
		} else if ($scanner->sym === Symbol::$sym_function) {
			$dbw->checkLineTagsForMethod();
			if ($db_visibility !== NULL) {
				$logger->error($scanner->here(), "invalid meta-code or DocBlock @access line-tag to set visibility, use proper language keywords");
			}
			if ($is_abstract && !$c->is_abstract) {
				$logger->error($scanner->here(), "abstract method in non-abstract class");
				$is_abstract = FALSE;
			}
			ClassMethodStatement::parse($globals, $dbw, $is_abstract,
				$visibility === NULL? Visibility::$public_ : $visibility,
				$is_static, $is_final, $meta_type);
			
		} else {
			throw new ParseException($scanner->here(),
			"unexpected symbol " . $scanner->sym);
		}
		
	}
	
	
	/**
	 * Parses all the members of the concrete or abstract class. Returns when either
	 * the end of the class declaration has been found, or the 'suspend' pragma has
	 * been found.
	 * @param Globals $globals
	 * @return void
	 */
	public static function continueParsing($globals) {
		$pkg = $globals->curr_pkg;
		$scanner = $pkg->scanner;
		$logger = $globals->logger;
		$c = $pkg->curr_class;

		// Parse all class members:
		do {

			if ($scanner->sym === Symbol::$sym_rbrace) {
				$scanner->readSym();
				break;
				
			} else if ($scanner->sym === Symbol::$sym_x_pragma ){
				// Possibly a request to suspend parsing of this class.
				PragmaStatement::parse($globals);
				if( $pkg->is_suspended ){
//					$pkg->curr_class = NULL;
					return;
				}
				
			} else {
				self::parseMember($globals);
			}

		} while (TRUE);

//	if( ! $class_->is_abstract ){
//		CheckImplementedMethods(class);
//	}
		
		// Check missing implementation of methods declared forward:
		foreach($c->methods as $method_mixed){
			$method = cast(ClassMethod::class, $method_mixed);
			if( $method->is_forward ){
				$logger->error($c->decl_in, "missing method " . $method->name
					. " declared forward in "
					. $logger->reference($c->decl_in, $method->decl_in));
			}
		}

//	Template.MangleNamesOfSurrogateClasses(class, surrogates);

		$pkg->curr_class = NULL;

		$missing = ClassInheritance::missingImplementations($c);
		if( $missing !== "" )
			$logger->error($c->decl_in, "missing implementations in $c:$missing");
	}
	
	
	/**
	 * Parse class definition starting from the "extends", "implements" or "{"
	 * symbol. Used to parse concrete classes, abstract classes and anonymous
	 * classes.
	 * @param Globals $globals Context of the parser.
	 * @param ClassType $c The class we are parsing, that can be concrete,
	 * abstract or anonymous.
	 * @param ClassType $proto For concrete and abstract classes, this parameter
	 * is the found prototype or NULL.
	 * @return void
	 */
	public static function parseFromExtendsOn($globals, $c, $proto) {
		$pkg = $globals->curr_pkg;
		$scanner = $pkg->scanner;
		$logger = $globals->logger;
		$pkg->curr_class = $c;
		
		/*
			Extends?
		*/
		if( $scanner->sym === Symbol::$sym_extends ){
			$scanner->readSym();
			$parent_ = TypeDecl::parseClassType($globals);
			if( $parent_ === NULL ){
				// parsing failed, error already signaled
			} else if( $parent_->isSubclassOf($c) ){
				$logger->error($scanner->here(),
				"class $c cannot extend child class $parent_: forbidden circular reference");
			} else if( $parent_->is_final ){
				$logger->error($scanner->here(),
				"cannot extend final class $parent_");
			} else if( $parent_->is_interface ){
				$logger->error($scanner->here(),
				"cannot extend interface class $parent_");
			} else {
				if( $proto !== NULL
				&& $proto->extended !== NULL
				&& $proto->extended !== $parent_
				&& ! $parent_->isSubclassOf($proto->extended)
				){
					$logger->error($scanner->here(),
					"$parent_ is not subclass of "
					. $proto->extended
					. " according to the forward declaration in "
					. $logger->reference($scanner->here(), $proto->decl_in));
				}
				$c->extended = $parent_;
				$globals->accountClass($parent_);
				$c->is_exception = $parent_->is_exception;
			}
		}

		// Implements?
		if( $scanner->sym === Symbol::$sym_implements ){
			$scanner->readSym();
			do {
				$iface = TypeDecl::parseClassType($globals);
				if( $iface === NULL ){
					// parsing failed, error already signaled
				} else if( ! $iface->is_interface ){
					$logger->error($scanner->here(),
					"the class $iface isn't an interface");
				} else if( $iface === $globals->builtin->TraversableClass
				&& ! $pkg->is_module ){
					// Traversable can be implemented only in modules, not in
					// user's code:
					$logger->error($scanner->here(),
					"implementing Traversable is forbidden in user's code; use either Iterator or IteratorAggregate instead");
				} else {
					$err = ClassInheritance::addInterfaceToClass($c, $iface);
					if( $err !== "" )
						$logger->error($scanner->here(), $err);
					$globals->accountClass($iface);
				}
				if( $iface !== NULL && $iface->is_exception )
					$c->is_exception = TRUE;
				if( $scanner->sym === Symbol::$sym_comma ){
					$scanner->readSym();
				} else {
					break;
				}
			} while(TRUE);
		}
		
		if( $c->is_exception && $c->is_template )
			$logger->error($scanner->here (), "exceptions cannot be generic");
		
		// Check "unchecked" modifier:
		if( $c->is_unchecked ){
			if( ! $c->is_exception ){
				$logger->error($scanner->here(),
				"invalid `unchecked' modifier for non-exception class");
				$c->is_unchecked = FALSE;
			}
			
		} else {
			if( $c->is_exception
			&& $c->extended !== NULL
			&& $c->extended->is_unchecked ){
				$logger->error($scanner->here(),
				"missing `unchecked' modifier for exception extending uncheked exception");
				$c->is_unchecked = TRUE;
			}
			
		}
		
		if( $proto === NULL ){
			$globals->classes->put($c->name, $c);
			
		} else {
			if( ! $c->extendsPrototype($proto) )
				$logger->error($c->decl_in, "declaration of class $c as\n"
				. $c->prototype()
				. "\ndoes not match the forward declaration in "
				. $logger->reference($c->decl_in, $proto->decl_in) . " as\n"
				. $proto->prototype());
			$proto->is_forward = FALSE;
			$proto->decl_in = $c->decl_in;
			$proto->docblock = $c->docblock;
			if( $c->extended->isSubclassOf($proto) ){
				$logger->error($c->decl_in,
				"$c: detected circular reference with " . $c->extended);
				$c->extended = ClassType::getObject();
				// FIXME: check circular references also in interfaces
			}
			$proto->extended = $c->extended;
			$proto->implemented = $c->implemented;
			$c = $proto;
			$pkg->curr_class = $proto;
		}
		
		$globals->builtin->detect($c, $globals->php_ver);
		
		self::checkTraversableUsage($globals, $c);
		
		if( $c->is_exception && ! $pkg->is_module && $globals->isPHP(7)
		&& ! ($c->isSubclassOf($globals->builtin->ErrorClass)
			|| $c->isSubclassOf($globals->builtin->ExceptionClass) ) )
			$logger->error($c->decl_in,
			"class $c cannot implement interface Throwable directly, extend Exception or Error instead");
		
		$colliding = ClassInheritance::checkCollidingConstants($c);
		if( $colliding !== "" )
			$logger->error($c->decl_in,
			"colliding inherited constants:$colliding");
		
		ClassInheritance::checkIncompatibleInheritedMethods($c);

		$globals->expect(Symbol::$sym_lbrace, "expected '{' in class declaration");
		$scanner->readSym();
		
		self::continueParsing($globals);
	}
	

	/**
	 * Parses class declaration.
	 * @param Globals $globals Context of the parser.
	 * @param boolean $is_private Found the meta-code "private" modifier.
	 * @return void
	 */
	public static function parse($globals, $is_private) {

		$pkg = $globals->curr_pkg;
		$scanner = $pkg->scanner;
		$logger = $globals->logger;

		if ($pkg->scope > 0)
			$logger->warning($scanner->here(), "class declaration inside a function. The namespace of the classes is still global so the function cannot be called once more.");
		if ($pkg->curr_class !== NULL)
			throw new ParseException($scanner->here(), "nested classes are not allowed");

		$dbw = $pkg->curr_docblock;
		$dbw->checkLineTagsForClass();

		if ($is_private && $dbw->isPrivate())
			$logger->error($scanner->here(), "`private' modifier both in DocBlock and PHPLint meta-code");
		$is_private = $is_private || $dbw->isPrivate();

		$is_abstract = FALSE;
		$is_final = FALSE;
		$is_unchecked = FALSE;
		// Collects abstract, final and unchecked exception modifiers:
		do {
			if ($scanner->sym === Symbol::$sym_abstract) {
				if ($is_abstract)
					$logger->notice($scanner->here(), "multiple `abstract' modifiers");
				$is_abstract = TRUE;
				$scanner->readSym();
			} else if ($scanner->sym === Symbol::$sym_final) {
				if ($is_final)
					$logger->notice($scanner->here(), "multiple `final' modifiers");
				$is_final = TRUE;
				$scanner->readSym();
			} else if ($scanner->sym === Symbol::$sym_x_unchecked) {
				if ($is_unchecked)
					$logger->notice($scanner->here(), "multiple `unchecked' modifiers");
				$is_unchecked = TRUE;
				$scanner->readSym();
			} else {
				break;
			}
		} while (TRUE);

		if ($is_final && $is_abstract) {
			$logger->error($scanner->here(), "a class cannot be both final and abstract");
			$is_final = FALSE; // keeps consistency
		}

		$globals->expect(Symbol::$sym_class, "expected `class'");
		$scanner->readSym();

		// Class name:
		$globals->expect(Symbol::$sym_identifier, "expected class name");
		$here = $scanner->here();
		$s = $scanner->s;
		if (!NamespaceResolver::isIdentifier($s))
			throw new ParseException($here, "class name must be a simple identifier");
		$s = $pkg->resolver->absolute($s);
		$fqn = new FullyQualifiedName($s, FALSE);

		// Check proto or re-definition of the same class:
		$proto = $globals->getClass($fqn);
		if( $proto !== NULL ){
			if( ! $proto->is_forward ){
				$logger->error($here, "class " . $proto->name
				. " already declared in "
				. $logger->reference($here, $proto->decl_in));
				$proto = NULL;
			}
		}

		$c = new ClassType($fqn, $here, $pkg->is_module);
		$c->decl_in = $here;
		$c->is_unchecked = $is_unchecked;
		$c->is_private = $is_private;
		$c->is_final = $is_final;
		$c->is_abstract = $is_abstract;
		if( ! $globals->report_unused )
			$c->used = 100;
		$c->docblock = $dbw->getDocBlock();
		$dbw->clear();

		$scanner->readSym(); // skip class name
		
		TypeParametersDecl::parse($globals, $c);
		
		/*
		 * Abstract and concrete classes extend object. This is true only
		 * inside PHPLint: the test "$v instanceof object"
		 * always fails; use is_object($v) to test if $v is an object.
		 */
		$c->extended = ClassType::getObject();
		
		self::parseFromExtendsOn($globals, $c, $proto);
	}

}

