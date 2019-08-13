<?php

namespace it\icosaedro\lint;

require_once __DIR__ . "/../../../autoload.php";

use it\icosaedro\lint\types\Type;
use it\icosaedro\lint\types\ArrayType;
use it\icosaedro\lint\types\ClassType;
use it\icosaedro\lint\types\UnknownType;
use it\icosaedro\lint\types\ParameterType;
use it\icosaedro\containers\HashMap;

/**
 * Parses the type declaration, that may be part PHP code and part PHPLint
 * meta-code. Example:
 * <blockquote><pre>
 * /&#42;. int .&#42;/ function indexOf(array/&#42;. [int]string .&#42;/ $s) { ... }
 * </pre></blockquote>
 * Note that the argument of the function uses PHP type-hint, so we may have to
 * parse mixed PHP/PHPLint code.
 * @author Umberto Salsi <salsi@icosaedro.it>
 * @version $Date: 2018/06/01 14:28:43 $
 */
class TypeDecl {
	
	/*.
	forward public static Type function parse(Globals $globals, boolean $allow_type_hinting, boolean $allow_meta_code =);
	forward public static ClassType function parseClassType(Globals $globals);
	pragma 'suspend';
	.*/
	
	/**
	 * Parse a class type in PHPLint's meta-code.
	 * @param Globals $globals
	 * @return ClassType Parsed class or NULL if an error has been detected and
	 * signaled.
	 */
	private static function parseClassTypeInMetaCode($globals) {
		$pkg = $globals->curr_pkg;
		$scanner = $pkg->scanner;
		$logger = $globals->logger;
		$t = self::parse($globals, FALSE, TRUE);
		if( $t === NULL ){
			$logger->error($scanner->here(), "expected class name");
			return NULL;
		} else if( $t instanceof UnknownType ){
			// error already signaled
			return NULL;
		} else if( $t instanceof ClassType ){
			return cast(ClassType::class, $t);
		} else {
			$logger->error($scanner->here(), "not a class: $t");
			return NULL;
		}
	}
	
	
	/**
	 * Parse a type parameter of a generic class, either class or "?" wildcard
	 * with bound.
	 * @param Globals $globals
	 * @return ClassType Parsed actual type parameter or NULL on error.
	 */
	private static function parseActualTypeParameter($globals) {
		$pkg = $globals->curr_pkg;
		$scanner = $pkg->scanner;
		if( $scanner->sym === Symbol::$sym_x_question ){
			$scanner->readSym();
			if( $scanner->sym === Symbol::$sym_x_extends ){
				// Class wildcard with lower bound: ? extends C
				$scanner->readSym();
				$subclassOf = self::parseClassTypeInMetaCode($globals);
				if( $subclassOf === NULL )
					return NULL;
				return ClassType::createWildcard($subclassOf, NULL);
			} else if( $scanner->sym === Symbol::$sym_x_parent ){
				// Class wildcard with upper bound: ? parent C
				$scanner->readSym();
				$parentClassOf = self::parseClassTypeInMetaCode($globals);
				if( $parentClassOf === NULL )
					return NULL;
				return ClassType::createWildcard(NULL, $parentClassOf);
			} else {
				// Class wildcard without bound: ?
				return ClassType::createWildcard(NULL, NULL);
			}
		} else {
			// Regular class type:
			return self::parseClassTypeInMetaCode($globals);
		}
	}
	
	
	/**
	 * Parse type parameters of a generic class. Example:
	 *      /&#42;. &lt;C1,C2,? parent C3&lt; .&#42;/
	 * We enter with the symbol &lt; if there are actual type params, or anything
	 * otherwise; in this latter common case simply returns $c.
	 * Non-generic classes cannot have
	 * actual parameters. Generic classes without actual parameters are allowed
	 * only if the formal parameters have no boundaries, and in this case all the
	 * actual parameters are assumed to be 'object'.
	 * @param Globals $globals
	 * @param ClassType $c The generic class.
	 * @return Type Actualized class.
	 * @throws ParseException The $c parameter is not a generic class.
	 */
	private static function parseActualTypeParameters($globals, $c) {
		$pkg = $globals->curr_pkg;
		$scanner = $pkg->scanner;
		$logger = $globals->logger;
		// Detects and resolve special cases:
		if( ! $c->is_template ){
			if( $scanner->sym === Symbol::$sym_x_lt ){
				// 1. Non-template class with actual parameters - fatal.
				throw new ParseException($scanner->here(), "class $c is not a template, no type parameters allowed");
			} else {
				// 2. Non-template class, no actual parameters, the most common case.
				return $c;
			}
		} else {
			if( $scanner->sym === Symbol::$sym_x_lt ){
				// 3. Template class with actual parameters.
				$scanner->readSym();
				$actual_types = new HashMap();
				// on error return unknown type:
				$error = FALSE;
				for($i = 0; ; $i++){
					$actual_type = self::parseActualTypeParameter($globals);
					if( $actual_type === NULL ){
						// error already signaled
						$error = TRUE;
					} else {
						if( $i < count($c->parameters_by_index) ){
							$formal_type = cast(ParameterType::class, $c->parameters_by_index[$i]);
							foreach($formal_type->getBounds() as $bounding){
								if( ! $actual_type->isSubclassOf($bounding) ){
									$logger->error($scanner->here(), "$actual_type is not $bounding");
									$error = TRUE;
								}
							}
							$actual_types->put($formal_type, $actual_type);
						} else {
							$logger->error($scanner->here(), "too many actual type parameters for $c");
							$error = TRUE;
						}
					}
					if( $scanner->sym === Symbol::$sym_x_comma ){
						$scanner->readSym();
					} else {
						break;
					}
				}
				if( ! $error && $actual_types->count() < count($c->parameters_by_index) ){
					$logger->error($scanner->here(), "missing actual type parameters for $c");
					$error = TRUE;
				}
				$globals->expect(Symbol::$sym_x_gt, "expected ',' or '>'");
				$scanner->readSym();
				if( $error ){
					// cannot create a consistent, safe result:
					return UnknownType::getInstance();
				}
				return $c->actualize($actual_types);
				
			} else {
				// 4. Template class without type parameters.
				return $c->getDefaultActualization();
			}
		}
	}

	/**
	 * Attempts to parse a type name. Type names may have several forms
	 * that ranges from a simple "int", up to a fully qualified class name,
	 * "namespace" operator or even "self", "parent", and may appear both in
	 * PHP code as type hint, or PHPLint meta-code. We enter with an arbitrary
	 * symbol.
	 * @param Globals $globals
	 * @param boolean $allow_type_hinting
	 * @param boolean $allow_meta_code Normally TRUE, allows to declare the type
	 * using PHPLint meta-code. Set to FALSE only for 'new TYPE()', where TYPE
	 * must be parsable by PHP.
	 * @return Type If the current symbol(s) look like a type, returns that
	 * type, possibly <code>UnknownType</code> if something went wrong and the
	 * name found cannot be recognized or it is not defined. Returns NULL if
	 * there is not a type at all here, which is perfectly valid in most cases
	 * encountered by the parser: client code must establish if this is allowed
	 * or not.
	 */
	private static function parseName($globals, $allow_type_hinting, $allow_meta_code) {
		$pkg = $globals->curr_pkg;
		$scanner = $pkg->scanner;

		if ($scanner->sym === Symbol::$sym_namespace) {
			$globals->resolveNamespaceOperator();
		} else if ($scanner->sym === Symbol::$sym_x_namespace) {
			$globals->resolveNamespaceOperatorInMetaCode();
		}

		switch ($scanner->sym->__toString()) {

			case "sym_x_void": $scanner->readSym();
				if( ! $allow_meta_code )
					$globals->logger->error($scanner->here(), "meta-code type not allowed in this context");
				return Globals::$void_type;
			case "sym_x_boolean": $scanner->readSym();
				if( ! $allow_meta_code )
					$globals->logger->error($scanner->here(), "meta-code type not allowed in this context");
				return Globals::$boolean_type;
			case "sym_x_int": $scanner->readSym();
				if( ! $allow_meta_code )
					$globals->logger->error($scanner->here(), "meta-code type not allowed in this context");
				return Globals::$int_type;
			case "sym_x_float": $scanner->readSym();
				if( ! $allow_meta_code )
					$globals->logger->error($scanner->here(), "meta-code type not allowed in this context");
				return Globals::$float_type;
			case "sym_x_string": $scanner->readSym();
				if( ! $allow_meta_code )
					$globals->logger->error($scanner->here(), "meta-code type not allowed in this context");
				return Globals::$string_type;
			case "sym_x_mixed": $scanner->readSym();
				if( ! $allow_meta_code )
					$globals->logger->error($scanner->here(), "meta-code type not allowed in this context");
				return Globals::$mixed_type;
			case "sym_x_resource": $scanner->readSym();
				if( ! $allow_meta_code )
					$globals->logger->error($scanner->here(), "meta-code type not allowed in this context");
				return Globals::$resource_type;
			case "sym_x_object": $scanner->readSym();
				if( ! $allow_meta_code )
					$globals->logger->error($scanner->here(), "meta-code type not allowed in this context");
				return Globals::$object_type;

			case "sym_object":
				$globals->logger->error($scanner->here(), "`object' keyword not allowed as type, allowed only as typecast `(object)'");
				if (!$allow_type_hinting)
					$globals->logger->error($scanner->here(), "invalid syntax");
				$scanner->readSym();
				return Globals::$object_type;

			case "sym_x_identifier":
				if( ! $allow_meta_code )
					$globals->logger->error($scanner->here(), "meta-code type not allowed in this context");
				$c = $globals->searchClassOrTypeParameter($scanner->s);
				if ($c === NULL) {
					$globals->logger->error($scanner->here(), "unknown type `" . $scanner->s . "'");
					$scanner->readSym();
					return Globals::$unknown_type;
				}
				$globals->accountClass($c);
				$scanner->readSym();
				return self::parseActualTypeParameters($globals, $c);
			case "sym_identifier":
				if (!$allow_type_hinting)
					$globals->logger->error($scanner->here(), "invalid syntax");
				$c = $globals->searchClassOrTypeParameter($scanner->s);
				if ($c === NULL) {
					$globals->logger->error($scanner->here(), "unknown type `" . $scanner->s . "'");
					$scanner->readSym();
					return Globals::$unknown_type;
				}
				if( $c instanceof ParameterType )
					$globals->logger->error($scanner->here(), "formal type parameter in actual PHP code: $c");
				$globals->accountClass($c);
				$scanner->readSym();
				return self::parseActualTypeParameters($globals, $c);
			case "sym_self":
				if ( ! $allow_type_hinting )
					// Maybe "self::..." ?
					return NULL;
				if ($pkg->curr_class == NULL) {
					$globals->logger->error($scanner->here(), "`self': not inside a class");
					return NULL;
				}
				$scanner->readSym();
				return self::parseActualTypeParameters($globals, $pkg->curr_class);
			case "sym_x_self":
				if( ! $allow_meta_code )
					$globals->logger->error($scanner->here(), "meta-code type not allowed in this context");
				if ($pkg->curr_class == NULL) {
					$globals->logger->error($scanner->here(), "`self': not inside a class");
					return NULL;
				}
				$scanner->readSym();
				return self::parseActualTypeParameters($globals, $pkg->curr_class);

			case "sym_parent":
			case "sym_x_parent":
				if ( $scanner->sym === Symbol::$sym_parent && ! $allow_type_hinting )
					// Maybe "parent::..." ?
					return NULL;
				if ( $scanner->sym === Symbol::$sym_x_parent && ! $allow_meta_code )
					$globals->logger->error($scanner->here(), "meta-code type not allowed in this context");
				if ($pkg->curr_class === NULL) {
					$globals->logger->error($scanner->here(), "`parent': not inside a class");
					return NULL;
				}
				$c = $pkg->curr_class->extended;
				if ($c === NULL) {
					$globals->logger->error($scanner->here(), "`parent': no parent class");
					return NULL;
				}
				$globals->accountClass($c);
				$scanner->readSym();
				return self::parseActualTypeParameters($globals, $c);

			default:
				return NULL;
		}
	}
	

	/**
	 * Parses a sequence of indeces "...[K][K]" possibly ending with a elements
	 * type if new syntax or "...[K][K]E" if old syntax. We enter here with
	 * sym="[".
	 * @param Globals $globals
	 * @param Type $e With the new array syntax, this is the type of the
	 * elements, which is known from the very beginning of the parsing of the
	 * type. If it is the old array syntax, set to NULL and then the type of
	 * the elements has to be parsed at the end.
	 * @return Type
	 */
	private static function parseIndeces($globals, $e) {
		// Parse type of this index "[int]", "[string]" or "[]":
		$pkg = $globals->curr_pkg;
		$scanner = $pkg->scanner;
		$scanner->readSym();
		$index_type = /*.(Type).*/ NULL;
		if ($scanner->sym === Symbol::$sym_x_int) {
			$index_type = Globals::$int_type;
			$scanner->readSym();
		} else if ($scanner->sym === Symbol::$sym_x_string) {
			$index_type = Globals::$string_type;
			$scanner->readSym();
		} else if ($scanner->sym === Symbol::$sym_x_rsquare) {
			$index_type = Globals::$mixed_type;
		}
		$globals->expect(Symbol::$sym_x_rsquare, "expected index type `int', `string' or `]'");
		$scanner->readSym();

		// Parse next index or elements type:
		if ($scanner->sym === Symbol::$sym_x_lsquare) {
			$elem_type = self::parseIndeces($globals, $e);
		} else if ($e === NULL) {
			// Old syntax. Looks for an elements type:
			$elem_type = self::parseName($globals, FALSE, TRUE);
			if ($elem_type === NULL)
			// Found "array[k][k]" without elements type.
				$elem_type = Globals::$mixed_type;
		} else {
			// Finished new array type decl.
			$elem_type = $e;
		}
		return ArrayType::factory($index_type, $elem_type);
	}

	/**
	 * Attempts to parse a declaration of type. Type names may have several forms
	 * that ranges from a simple "int", up to a fully qualified class name,
	 * "namespace" operator or even "self", "parent", and may appear both in
	 * PHP code as type hint, or PHPLint meta-code. We enter with an arbitrary
	 * symbol.
	 * 
	 * <p>
	 * May trigger class autoloading, if enabled.
	 * 
	 * <blockquote><pre>
	 * type = T { index } | "array" [ index {index} T ];
	 * </pre></blockquote>
	 *
	 * BUG: void[] should be forbidden.
	 * 
	 * @param Globals $globals
	 * @param boolean $allow_type_hinting Set to true while parsing formal
	 * arguments of function or method. The first symbol may then be either
	 * "array" or the name of a class in PHP code, the rest, if any, must still
	 * be PHPLint meta-code. Example: <code>function f(array/&#42;. [int]string
	 * .&#42;/ \$a){}</code>.
	 * @param boolean $allow_meta_code Normally TRUE, allows to declare the type
	 * using PHPLint meta-code. Set to FALSE only for 'new TYPE()', where TYPE
	 * must be parsable by PHP.
	 * @return Type Type parsed, possibly UnknownType if an error was
	 * detected (and then reported). Returns NULL if there is not a type at all
	 * here, which is perfectly valid in most cases encountered by the parser:
	 * client code must establish if this is allowed or not.
	 */
	public static function parse($globals, $allow_type_hinting, $allow_meta_code = TRUE) {
		$pkg = $globals->curr_pkg;
		$scanner = $pkg->scanner;
		
		/*
		 * Check PHP 7 scalar declaration: "void", "bool", "int", "float". Note that
		 * "boolean", "integer", "double" and "real" are not allowed.
		 */
		if( $allow_type_hinting ){
			if( $scanner->sym === Symbol::$sym_void ){
				if( ! $globals->isPHP(7) )
					$globals->logger->error($scanner->here(), "scalar type declaration not allowed (PHP 7)");
				$scanner->readSym();
				return Globals::$void_type;
				
			} else if( $scanner->sym === Symbol::$sym_boolean ){
				
				if( ! $globals->isPHP(7) )
					$globals->logger->error($scanner->here(), "scalar type declaration not allowed (PHP 7)");
				else if( strtolower($scanner->s) !== "bool" )
					$globals->logger->error($scanner->here(), "expected bool");
				
				$scanner->readSym();
				return Globals::$boolean_type;
				
			} else if( $scanner->sym === Symbol::$sym_int ){
				
				if( ! $globals->isPHP(7) )
					$globals->logger->error($scanner->here(), "scalar type declaration not allowed (PHP 7)");
				else if( strtolower($scanner->s) !== "int" )
					$globals->logger->error($scanner->here(), "expected int; integer not allowed");
				
				$scanner->readSym();
				return Globals::$int_type;
				
			} else if( $scanner->sym === Symbol::$sym_float ){
				
				if( ! $globals->isPHP(7) )
					$globals->logger->error($scanner->here(), "scalar type declaration not allowed (PHP 7)");
				else if( strtolower($scanner->s) !== "float" )
					$globals->logger->error($scanner->here(), "expected float; real and double are not allowed");
				
				$scanner->readSym();
				return Globals::$float_type;
				
			} else if( $scanner->sym === Symbol::$sym_string ){
				
				if( ! $globals->isPHP(7) )
					$globals->logger->error($scanner->here(), "scalar type declaration not allowed (PHP 7)");
				
				$scanner->readSym();
				return Globals::$string_type;
			}
		}
		
		if (($scanner->sym === Symbol::$sym_x_array)
				|| ($scanner->sym === Symbol::$sym_array)) {
			# Old type syntax array[K][K]E:
			if ($scanner->sym === Symbol::$sym_array && !$allow_type_hinting)
				return NULL;
			$scanner->readSym();
			if ($scanner->sym !== Symbol::$sym_x_lsquare)
				return ArrayType::factory(Globals::$mixed_type, Globals::$mixed_type);
			return self::parseIndeces($globals, NULL);
			
		} else {
			# Type T or new array syntax T[][]:
			$t = self::parseName($globals, $allow_type_hinting, $allow_meta_code);
			if ($t == NULL)
				return NULL;
			if ($scanner->sym === Symbol::$sym_x_lsquare)
				$t = self::parseIndeces($globals, $t);
			return $t;
		}
	}
	
	
	/**
	 * Parse a class type in PHP actual code, possibly with actual type parameters.
	 * To be used in contexts where PHP is expectiong a class name and PHPLint is
	 * expecting a class name and possible type parameters.
	 * @param Globals $globals
	 * @return ClassType Parsed class, possibly actualized, or NULL if parsing
	 * failed and error signaled.
	 */
	public static function parseClassType($globals)
	{
		$t = self::parse($globals, TRUE, FALSE);
		if( $t === NULL ){
			$globals->logger->error($globals->curr_pkg->scanner->here(),
				"expected class");
			return NULL;
		}
		if( $t instanceof UnknownType ){
			return NULL; // error already signaled
		}
		if( ! $t instanceof ClassType ){
			$globals->logger->error($globals->curr_pkg->scanner->here(),
				"not a class: $t");
			return NULL;
		}
		return cast(ClassType::class, $t);
	}

}
