<?php

namespace it\icosaedro\lint\types;
require_once __DIR__ . "/../../../../all.php";
use it\icosaedro\utils\Strings;
use it\icosaedro\lint\Scanner;
use it\icosaedro\lint\ClassResolver;
use it\icosaedro\lint\Logger;
use it\icosaedro\lint\Where;
use it\icosaedro\containers\HashMap;
use Exception;

/**
 * @access private
 */
class TypeDescriptorException extends Exception {}

/**
 * Parses a type descriptor given as a string. Used to parse DocBlock types
 * and the first argument of the magic <code>cast(T,V)</code> function.
 * A type descriptor can be described in EBNF notation as follows:
 *
 * <blockquote><pre>
 * type = name | array_old_syntax | array_new_syntax;
 * 
 * array_old_syntax = "array" [ index {index} [name] ];
 * 
 * array_new_syntax = name index {index};
 * 
 * index = "[]" | "[int]" | "[string]";
 * 
 * name = "void"     | "bool"   | "boolean" | "int" | "integer"
 *      | "float"    | "double" | "real"    | "string"
 *      | "resource" | "mixed"  | "object"  | class_name [ type_parameters ]
 *      | "self"     | "parent";
 * 
 * class_name = ["\\"] identifier {"\\" identifier};
 * 
 * type_parameters = "&lt;" actual_param {"," actual_param } "&gt;";
 * 
 * actual_param = class_name | "?" [ ("extends" | "parent") class_name];
 * </pre></blockquote>
 * 
 * <p>Names matching is case-sensitive; improperly mixing upper- and lower-case
 * letters is signaled as an error.
 * 
 * <p>Spaces and tabulator are allowed only inside actual types of a template;
 * these spaces do not hurt the runtime implementation of cast() because generics
 * are not allowed there.
 * 
 * <p>Examples:
 * 
 * <blockquote><pre>
 * int
 * resource
 * float[int][int]
 * array[int][int]float
 * Exception
 * \some\name\space\MyClass&lt;AnotherClass, ? extends C&gt;
 * </pre></blockquote>
 * @author Umberto Salsi <salsi@icosaedro.it>
 * @version $Date: 2018/06/06 09:58:03 $
 */
class TypeDescriptor {
	
	/**
	 * Predefined types. The key is the name of the type.
	 * @var Type[string]
	 */
	private static $predefined = NULL;
	
	/**
	 * Cached pattern matching an ASCII name.
	 * @var string
	 */
	private static $ascii_name_pattern;
	
	/**
	 * Class name resolver.
	 * @var ClassResolver
	 */
	private $resolver;
	
	/**
	 * If true, assumes the name be already fully qualified or absolute and does
	 * not applies the namespace resolution algorithm. Set to true only to resolve
	 * classes in the magic <code>cast(T,V)</code>, where <code>T</code> must be
	 * resolvable at runtime outside the current namespace context.
	 * @var boolean
	 */
	private $is_fqn = FALSE;
	
	/**
	 * Line to scan containing the type.
	 * @var string
	 */
	private $scan_line;
	
	/**
	 * Offset current char to scan.
	 * @var int
	 */
	private $scan_index = 0;
	
	/**
	 * Spaces are allowed only inside actual parameters of a generic type to
	 * support the class wildcard with bound. This does not hurt the runtime
	 * code implementing cast() because generics are not allowed there.
	 * @var boolean
	 */
	private $scan_allow_spaces = FALSE;
	
	/**
	 * Last symbol scanned.
	 * @var int
	 */
	private $scan_sym = 0;
	
	/**
	 * Type of symbol found by the scanner.
	 * @access private
	 */
	const
		SYM_EOT = 0,
		SYM_WORD = 2,
		SYM_KEY = 3, // [XXX]
		SYM_LT = 6, // <
		SYM_GT = 7, // >
		SYM_COMMA = 8, // ,
		SYM_WILDCARD = 9; // ?
	
	/**
	 * Last word scanned, valid for SYM_WORD and SYM_KEY.
	 * @var string
	 */
	private $scan_word;
	
	/*. forward private Type function parseNameWithTypeParameters() throws TypeDescriptorException; .*/
	
	/**
	 * Scans next symbol from the scan line.
	 * @return void
	 * @throws TypeDescriptorException
	 */
	private function nextSym() {
		$start = $this->scan_index;
		$line = $this->scan_line;
		if( $start >= strlen($line) ){
			$this->scan_sym = self::SYM_EOT;
			$this->scan_word = "";
			return;
		}
		$c = $line[$start];
		
		// Skip spaces inside generic actual parameters:
		while( $c === ' ' || $c === "\t" ){
			if( ! $this->scan_allow_spaces )
				throw new TypeDescriptorException("spaces not allowed in type descriptor");
			$start++;
			if( $start >= strlen($line) ){
				$this->scan_sym = self::SYM_EOT;
				$this->scan_word = "";
				return;
			}
			$c = $line[$start];
		}
		
		if( $c === '[' ){
			$end = strpos($line, ']', $start);
			if( $end === FALSE )
				throw new TypeDescriptorException("unbalanced `[]'");
			$this->scan_word = substr($line, $start, $end - $start + 1);
			$this->scan_sym = self::SYM_KEY;
			$this->scan_index = $end + 1;
		} else if( $c === '<' ){
			$this->scan_allow_spaces = TRUE;
			$this->scan_sym = self::SYM_LT;
			$this->scan_index++;
		} else if( $c === '>' ){
			$this->scan_allow_spaces = FALSE;
			$this->scan_sym = self::SYM_GT;
			$this->scan_index++;
		} else if( $c === ',' ){
			$this->scan_sym = self::SYM_COMMA;
			$this->scan_index++;
		} else if( $c === '?' ){
			$this->scan_sym = self::SYM_WILDCARD;
			$this->scan_index++;
		} else {
			$i = $start + 1;
			while( $i < strlen($line) ){
				$c = $line[$i];
				if( $c === '[' || $c === '<' || $c === '>' || $c === ',' || $c === ' ' || $c === "\t" )
					break;
				$i++;
			}
			$this->scan_sym = self::SYM_WORD;
			$this->scan_word = substr($line, $start, $i - $start);
			$this->scan_index = $i;
		}
		
	}
	
	/**
	 * Creates an instance of the tiny type declaration scanner.
	 * @param string $scan_line Type descriptor to scan.
	 * @param ClassResolver $resolver Class name resolver.
	 * @param boolean $is_fqn If true, assumes the name be already fully
	 * qualified or absolute and does not applies the namespace resolution
	 * algorithm. Set to true only to resolve classes in the magic
	 * <code>cast(T,V)</code>, where <code>T</code> must be resolvable
	 * at runtime outside the current namespace context.
	 * @throws TypeDescriptorException
	 */
	private function __construct($scan_line, $resolver, $is_fqn) {
		$this->scan_line = $scan_line;
		$this->resolver = $resolver;
		$this->is_fqn = $is_fqn;
		$this->scan_allow_spaces = FALSE;
		$this->scan_index = 0;
		$this->nextSym();
	}
	
	
	/**
	 * Parses a type name, including simple types, class name, "self" and "parent".
	 * Expected current symbol: SYM_WORD.
	 * @return Type Parsed type, or UnknownType if invalid.
	 * @throws TypeDescriptorException
	 */
	private function parseName(){
		if( $this->scan_sym != self::SYM_WORD )
			throw new TypeDescriptorException("invalid type syntax");
		
		$name = $this->scan_word;
		$this->nextSym();
		
		// Try fast look-up:
		if( array_key_exists($name, self::$predefined) )
			return self::$predefined[$name];
		
		// Check mispelled case letters:
		$name_low = strtolower($name);
		if( array_key_exists($name_low, self::$predefined) ){
			throw new TypeDescriptorException("spelling check: expected `$name_low' but found `$name'");
		}
		
		// Detects some common mistakes:
		if( $name_low === "null" || $name_low === 'false' ){
			throw new TypeDescriptorException("`$name' is not a type");
		}
		
		// Check non-ASCII:
		if( Scanner::$ascii_ext_check && preg_match(self::$ascii_name_pattern, $name) !== 1 )
				throw new TypeDescriptorException("non-ASCII characters in identifier: " . Strings::toLiteral($name));
		
		// Resolve class name:
		$c = $this->resolver->searchClassOrTypeParameter($name, $this->is_fqn);
		if( $c === NULL ){
			throw new TypeDescriptorException("unknown type $name");
		} else {
			$this->resolver->accountClass($c);
			return $c;
		}
	}
	
	
	/**
	 * Actualizes the given template class using the actual types as replacement.
	 * If the given class is not a template, the actual parameters must be NULL
	 * and the the given class is returned. If the given class is a template,
	 * the actual parameters can be NULL or set; if NULL, the default actualization
	 * of the template is returned; if set, the actual types must mach in number
	 * and relationship with the formal types.
	 * @param ClassType $c Real class or template class to actualize.
	 * @param ClassType[int] $actual_types Actual types, or NULL.
	 * @return ClassType Actualized class.
	 * @throws TypeDescriptorException
	 */
	private function actualizeClass($c, $actual_types) {
		if( $c->is_template ){
			// Template.
			if( $actual_types !== NULL ){
				// Template class with actual parameters.
				if( phplint_count($actual_types) > phplint_count($c->parameters_by_index) )
					throw new TypeDescriptorException("too many actual type parameters for $c");
				if( phplint_count($actual_types) < phplint_count($c->parameters_by_index) )
					throw new TypeDescriptorException("too few actual type parameters for $c");
				$replacements = new HashMap();
				for($i = 0; $i < phplint_count($actual_types); $i++){
					// Add $actual_type to the replacements:
					$actual_type = $actual_types[$i];
					$formal_type = cast(ParameterType::class, $c->parameters_by_index[$i]);
					foreach($formal_type->getBounds() as $bounding){
						if( ! $actual_type->isSubclassOf($bounding) ){
							throw new TypeDescriptorException("$actual_type is not $bounding");
						}
					}
					$replacements->put($formal_type, $actual_type);
				}
				return $c->actualize($replacements);
				
			} else {
				// Template class without actual parameters.
				return $c->getDefaultActualization();
			}
		} else {
			// Real class, not a template.
			if( $actual_types !== NULL )
				throw new TypeDescriptorException("class $c is not a template, no type parameters allowed");
			return $c;
		}
	}
	
	
	/**
	 * Parse a class name.
	 * @return ClassType
	 * @throws TypeDescriptorException
	 */
	private function parseClassName() {
		$t = $this->parseNameWithTypeParameters();
		if( $t instanceof ClassType )
			return cast(ClassType::class, $t);
		else
			throw new TypeDescriptorException("not a class: $t");
	}
	
	
	/**
	 * Parse an actual type, which can be a class or a class wildcard.
	 * @return ClassType
	 * @throws TypeDescriptorException
	 */
	private function parseActualType() {
		if( $this->scan_sym == self::SYM_WILDCARD ){
			$this->nextSym();
			if( $this->scan_sym == self::SYM_WORD ){
				if( $this->scan_word === "extends" ){
					// ? extends C
					$this->nextSym();
					$bound = $this->parseClassName();
					return ClassType::createWildcard($bound, NULL);

				} else if( $this->scan_word === "parent" ){
					// ? parent C
					$this->nextSym();
					$bound = $this->parseClassName();
					return ClassType::createWildcard(NULL, $bound);

				} else {
					throw new TypeDescriptorException("unexpected symbol after `?'");
				}

			} else {
				// ?
				return ClassType::createWildcard(NULL, NULL);
			}
		} else if( $this->scan_sym == self::SYM_WORD ){
			// C
			return $this->parseClassName();

		} else {
			throw new TypeDescriptorException("expected either class name or class wildcard in actual type parameters");
		}
	}
	
	
	/**
	 * Parse the actual types.
	 * @return ClassType[int] List of 1+ actual types.
	 * @throws TypeDescriptorException
	 */
	private function parseActualTypes() {
		$this->nextSym(); // skip '<'
		$actual_types = /*. (ClassType[int]) .*/ [];
		do {
			$actual_types[] = $this->parseActualType();
			if( $this->scan_sym == self::SYM_COMMA ){
				$this->nextSym();
			} else {
				break;
			}
		} while(TRUE);
		if( $this->scan_sym != self::SYM_GT )
			throw new TypeDescriptorException("syntax error in actual parameters");
		$this->nextSym();
		return $actual_types;
	}
	
	
	/**
	 * Parses a non-array type. This function can parse a simple type as "int"
	 * up to a class name with possible actual type parameters.
	 * Expected current symbol: SYM_WORD.
	 * @return Type Resulting parsed type, possibly actualized class.
	 * @throws TypeDescriptorException
	 */
	private function parseNameWithTypeParameters() {
		// Parse name of the type:
		$t = self::parseName();

		// Parse actual type parameters:
		if( $this->scan_sym == self::SYM_LT ){
			$actual_types = $this->parseActualTypes();
		} else {
			$actual_types = NULL;
		}

		// Actualize the template if required:
		if( $t instanceof ClassType ){
			$c = cast(ClassType::class, $t);
			return $this->actualizeClass($c, $actual_types);
		} else {
			if( phplint_count($actual_types) > 0 )
				throw new TypeDescriptorException("actual type parameters for non-template $t");
			return $t;
		}
			
	}
	
	
	/**
	 * Parses old array syntax recursively scanning indeces and possible final
	 * type of the elements.
	 * @return Type Resulting parsed array.
	 * @throws TypeDescriptorException
	 */
	private function parseOldArraySyntaxRecurse() {
		if( $this->scan_sym != self::SYM_KEY )
			throw new TypeDescriptorException("invalid type syntax");
		$w = $this->scan_word;
		if( $w === "[]" )
			/*. Type .*/ $key = MixedType::getInstance();
		else if( $w === "[int]" )
			$key = IntType::getInstance();
		else if( $w === "[string]" )
			$key = StringType::getInstance();
		else
			throw new TypeDescriptorException("expected index specifier [], [int] or [string] but found $w");
		$this->nextSym();
		if( $this->scan_sym === self::SYM_KEY )
			$elem = self::parseOldArraySyntaxRecurse();
		else if( $this->scan_sym == self::SYM_EOT )
			$elem = MixedType::getInstance();
		else if( $this->scan_sym == self::SYM_WORD )
			$elem = self::parseNameWithTypeParameters();
		else
			throw new TypeDescriptorException("invalid array syntax");
		return ArrayType::factory($key, $elem);
	}
	
	
	/**
	 * Parse old array syntax.
	 * @return Type Resulting parsed array.
	 * @throws TypeDescriptorException
	 */
	private function parseOldArraySyntax() {
		$this->nextSym(); // skip "array"
		if( $this->scan_sym == self::SYM_EOT ){
			$mixed_type = MixedType::getInstance();
			return ArrayType::factory($mixed_type, $mixed_type);
		} else if( $this->scan_sym == self::SYM_KEY ){
			return self::parseOldArraySyntaxRecurse();
		} else {
			throw new TypeDescriptorException("invalid array syntax");
		}
	}
	
	
	/**
	 * Parse new array syntax.
	 * @param Type $elem Type of the elements.
	 * @return Type Resulting parsed array.
	 * @throws TypeDescriptorException
	 */
	private function parseNewArraySyntax($elem) {
		if( $this->scan_sym != self::SYM_KEY )
			throw new TypeDescriptorException("invalid type syntax");
		$w = $this->scan_word;
		if( $w === "[]" )
			/*. Type .*/ $key = MixedType::getInstance();
		else if( $w === "[int]" )
			$key = IntType::getInstance();
		else if( $w === "[string]" )
			$key = StringType::getInstance();
		else
			throw new TypeDescriptorException("not an index specifier, expected [], [int] or [string] but found $w");
		$this->nextSym();
		if( $this->scan_sym === self::SYM_KEY )
			$elem = self::parseNewArraySyntax($elem);
		return ArrayType::factory($key, $elem);
	}
	
	
	/**
	 * Parse the type using the tiny scanner.
	 * @return Type
	 * @throws TypeDescriptorException
	 */
	private function parseType() {
		if( $this->scan_sym !== self::SYM_WORD )
			throw new TypeDescriptorException("invalid type syntax");
		$w = $this->scan_word;
		$w_low = strtolower($w);
		if( $w === "array" ){
			$t = self::parseOldArraySyntax();
		} else if( $w_low === "array" ){
			throw new TypeDescriptorException("spelling check: expected `array' but found `$w'");
			
		} else {
			$t = self::parseNameWithTypeParameters();
		}
		
		// Parse indeces:
		if( $this->scan_sym == self::SYM_KEY )
			$t = self::parseNewArraySyntax($t);
		
		if( $this->scan_sym != self::SYM_EOT && ! $t instanceof UnknownType ){
			throw new TypeDescriptorException("invalid type syntax: " . Strings::toLiteral($this->scan_line));
		}
		
		return $t;
	}
	
	
	/**
	 * Parses a type descriptor.
	 * @param Logger $logger
	 * @param Where $where Location in the source. Error messages are reported
	 * through this object.
	 * @param string $s Type descriptor.
	 * @param boolean $resolve_ns True if identifiers and qualified names can
	 * be resolved in the current namespace; false if all the names must be
	 * fully qualified. Namespace resolution can be performed in DocBlocks.
	 * the <code>cast()</code> requires a fully qualified namespace.
	 * @param ClassResolver $resolver Class name resolver.
	 * @param boolean $is_fqn If true, assumes the name be already fully
	 * qualified or absolute and does not applies the namespace resolution
	 * algorithm. Set to true only to resolve classes in the magic
	 * <code>cast(T,V)</code>, where <code>T</code> must be resolvable
	 * at runtime outside the current namespace context.
	 * @return Type Parsed type. On error, the {@link it\icosaedro\lint\types\UnknownType}
	 * singleton instance is returned instead.
	 */
	public static function parse($logger, $where, $s, $resolve_ns, $resolver, $is_fqn){
		
		if( self::$predefined === NULL ){
			// Static init.
			self::$predefined["void"] = VoidType::getInstance();
			self::$predefined["bool"] = BooleanType::getInstance();
			self::$predefined["boolean"] = BooleanType::getInstance();
			self::$predefined["int"] = IntType::getInstance();
			self::$predefined["integer"] = IntType::getInstance();
			self::$predefined["float"] = FloatType::getInstance();
			self::$predefined["double"] = FloatType::getInstance();
			self::$predefined["real"] = FloatType::getInstance();
			self::$predefined["string"] = StringType::getInstance();
			self::$predefined["resource"] = ResourceType::getInstance();
			self::$predefined["object"] = ClassType::getObject();
			self::$predefined["mixed"] = MixedType::getInstance();
			
			// Pattern matching an ASCII identifier:
			self::$ascii_name_pattern = "/^[_a-zA-Z0-9\\\\]+\$/sD";
		}
		
		// Attempt fast look-up for simple types:
		if(array_key_exists($s, self::$predefined) )
			return self::$predefined[$s];
		
		// Detect multiple types "string|false":
		$v_bar = strpos($s, "|");
		if( $v_bar !== FALSE ){
			$s = substr($s, 0, $v_bar);
			$logger->error($where, "multiple types not supported; assuming $s");
		}
		
		// Applies the general, slower parser:
		try {
			$type_parser = new self($s, $resolver, $is_fqn);
			return $type_parser->parseType();
		}
		catch(TypeDescriptorException $e){
			$logger->error($where, $e->getMessage());
			return UnknownType::getInstance();
		}
	}
	
}
