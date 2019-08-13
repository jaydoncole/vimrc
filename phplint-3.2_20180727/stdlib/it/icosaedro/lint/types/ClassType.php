<?php

namespace it\icosaedro\lint\types;
require_once __DIR__ . "/../../../../all.php";
use it\icosaedro\lint\Where;
use it\icosaedro\lint\docblock\DocBlock;
use it\icosaedro\lint\types\ClassConstant;
use it\icosaedro\lint\types\ClassProperty;
use it\icosaedro\lint\types\ClassMethod;
use it\icosaedro\lint\FullyQualifiedName;
use it\icosaedro\containers\Sortable;
use it\icosaedro\containers\Hashable;
use it\icosaedro\containers\HashMap;
use it\icosaedro\lint\CaseInsensitiveString;
use it\icosaedro\containers\Arrays;
use CastException;
use InvalidArgumentException;

/**
 * Class type, that is a concrete class, an abstract class or an interface.
 * Classes can be real or generic:
 * 
 * <pre>
 * real class
 * template class
 *     partially actualized class
 *         default partially actualized class
 *     fully actualized class
 * </pre>
 * 
 * See the reference manual for the definitions of these terms.
 * 
 * <p>Real and template classes are collected in the global symbol table and are
 * reported in the generated documentation. Actualized classes are cached for
 * fast look-up and reused.
 * 
 * <p>For each template class, a default partially actualized class is always
 * created with its own formal parameters and added to the cache.
 * 
 * @author Umberto Salsi <salsi@icosaedro.it>
 * @version $Date: 2018/04/15 04:38:03 $
 */
class ClassType extends Type implements Sortable, Hashable {

	/**
	 * Fully qualified name of the class.
	 * @var FullyQualifiedName
	 */
	public $name;
	
	/**
	 * If this class is anonymous (PHP 7).
	 * @var boolean
	 */
	public $is_anonymous = FALSE;

	/**
	 * Where it has been declared.
	 * @var Where 
	 */
	public $decl_in;

	/**
	 * No. of usages outside itself.
	 * @var int
	 */
	public $used = 0;
	
	/**
	 * If this class extends Exception.
	 * @var boolean
	 */
	public $is_exception = FALSE;

	/**
	 * This class represents an unchecked exception. PHPLint does not tracks
	 * the propagation of the unchecked exceptions, does not mandates their
	 * declaration in the signature, and does not complains if these exceptions
	 * are not caught.
	 * @var boolean
	 */
	public $is_unchecked = FALSE;

	/**
	 * Dummy forward declaration encountered -- actual class still to be parsed.
	 * @var boolean
	 */
	public $is_forward = FALSE;

	/**
	 * Is this class private to the package where it is defined?
	 * @var boolean
	 */
	public $is_private = FALSE;

	/**
	 * @var boolean 
	 */
	public $is_final = FALSE;

	/**
	 * True if abstract class or interface.
	 * @var boolean 
	 */
	public $is_abstract = FALSE;

	/**
	 * @var boolean 
	 */
	public $is_interface = FALSE;
	
	/**
	 * Tells if the class is internal to the PHP core or it belongs to a module,
	 * and it is not a user's defined class. Internal classes may define private
	 * members that are not really used anywhere, but are simply defined to detect
	 * collisions with user's defined members in the extended classes.
	 * @var boolean
	 */
	public $is_internal = FALSE;

	/**
	 * Extended class. This field is NULL only for the "object" class and for
	 * the interfaces.
	 * @var ClassType 
	 */
	public $extended;

	/**
	 * Lists the implemented interfaces (if this is a regular or abstract
	 * class), or lists the extended interfaces (if this is an interface).
	 * The set of these interfaces, joined with the extended class is minimal,
	 * that is none is subclass of another.
	 * PHP allows to declare "... implements BaseInterface, DerivedInterface"
	 * in that order, but gives a fatal error if the order is reversed,
	 * see {@link https://bugs.php.net/bug.php?id=63816} and
	 * {@link https://bugs.php.net/bug.php?id=71358}.
	 * This program is more conservative and forbids redundant interfaces.
	 * The Documentator assumes this set be minimal.
	 * Can be NULL otherwise contains at least one interface.
	 * @var ClassType[int]
	 */
	public $implemented;
	
	/**
	 * Tells if this class is a template, that is it has type parameters and these
	 * type parameters are those of its declaration.
	 * @var boolean
	 */
	public $is_template = FALSE;
	
	/**
	 * If this class is a template, look-up table that maps formal type parameter
	 * name into its object. For non-generic and actualized classes, this is NULL.
	 * @var ParameterType[string]
	 */
	public $parameters_by_name;
	
	/**
	 * Type parameters, in the order. For templates, these are all formal type
	 * parameters. For actualized classes, these are classes or formal types.
	 * @var ClassType[int]
	 */
	public $parameters_by_index;
	
	/**
	 * Cached default actualization of this template class.
	 * @var self
	 */
	private $default_actualization;
	
	/**
	 * If this class has no type parameters or these parameters are all real or
	 * fully actualized classes.
	 * @var boolean
	 */
	public $is_real_or_fully_actualized = TRUE;
	
	/**
	 * If this class is the actualization of a template, this is that template,
	 * otherwise NULL.
	 * @var ClassType
	 */
	private $template;
	
	/**
	 * If this class is the "?" wildcard.
	 * @var boolean
	 */
	public $is_wildcard = FALSE;
	
	/**
	 * Set if this is a class wildcard with parent bound.
	 * @var ClassType
	 */
	public $parentClassOf;

	/**
	 * Maps constant names into constants.
	 * @var ClassConstant[string]
	 */
	public $constants;

	/**
	 * Maps property names into properties.
	 * @var ClassProperty[string]
	 */
	public $properties;

	/**
	 * Maps method names (CaseInsensitiveString) into methods (ClassMethod).
	 * @var HashMap 
	 */
	public $methods;

	/**
	 * Constructor method, or null if not available.
	 * @var ClassMethod 
	 */
	public $constructor;

	/**
	 * Set when we encounter "new C(...)" AND a specific constructor for this class
	 * has not been defined yet; in this case the constructor
	 * invoked can be the default constructor, or the inherited
	 * constructor or the proper constructor of this class. Eventually, if the
	 * proper constructor gets parsed and this variable is already set, it
	 * means we used the wrong constructor; the programmer needs to define
	 * a forward declaration.
	 * @var Where
	 */
	public $constructor_first_used_here;

	/**
	 * Destructor method.
	 * @var ClassMethod 
	 */
	public $destructor;

	/**
	 * True if this class defines its own constructor AND this new constructor
	 * called the parent one. Just to check if the new constructor contains
	 * "parent::__construct()".
	 * @var boolean
	 */
	public $parent_constructor_called = FALSE;

	/**
	 * True if this class defines its own destructor AND this new destructor
	 * called the parent one. Just to check if the new constructor contains
	 * "parent::__destruct()".
	 * @var boolean
	 */
	public $parent_destructor_called = FALSE;

	/**
	 * DocBlock of this class, possibly NULL if not available.
	 * @var DocBlock
	 */
	public $docblock;
	
	/**
	 * Singleton instance of the "object" class.
	 * @var ClassType 
	 */
	private static $object_class;
	
	/**
	 * Cache of generic classes, including actualized and wildcards.
	 * The key is the full name with parameters.
	 * @var HashMap
	 */
	private static $actualized_classes;

/*.
	forward void function __construct(FullyQualifiedName $name, Where $where, boolean $is_internal);
	forward public string function __toString();
	forward public ClassType function actualize(HashMap $actual_types);
	forward public boolean function assignableTo(Type $lhs);
	forward public int function compareTo(object $o) throws CastException;
	forward public static ClassType function createWildcard(ClassType $subclassOf, ClassType $parentClassOf) throws InvalidArgumentException;
	forward public boolean function equals(object $o);
	forward public boolean function extendsPrototype(ClassType $proto);
	forward public ClassType function getDefaultActualization();
	forward public int function getHash();
	forward public static ClassType function getObject();
	forward public static int function indexOf(ClassType $c, ClassType[int] $a);
	forward public static void function initActualizedClassesCache();
	forward public boolean function isPrintable();
	forward public boolean function isSubclassOf(ClassType $other);
	forward public boolean function isSubclassOfAny(ClassType[int] $others);
	forward public boolean function isParentClassOf(ClassType $other);
	forward public ClassMethod function parentConstructor();
	forward public ClassMethod function parentDestructor();
	forward public string function prototype();
	forward public ClassConstant function searchConstant(string $name);
	forward public ClassMethod function searchMethod(CaseInsensitiveString $name);
	forward public ClassProperty function searchProperty(string $name);
	
	pragma 'suspend';
.*/
	
	
	/**
	 * Builds a new class type.
	 * @param FullyQualifiedName $name Name of the class.
	 * @param Where $where Where it has been declared.
	 * @param boolean $is_internal False if user's defined, true if from core or
	 * module.
	 * @return void
	 */
	public function __construct($name, $where, $is_internal){
		$this->name = $name;
		$this->decl_in = $where;
		$this->is_internal = $is_internal;
		$this->constants = /*.(ClassConstant[string]).*/ array();
		$this->properties = /*. (ClassProperty[string]) .*/ array();
		$this->methods = new HashMap();
	}
	
	
	/**
	 * Creates a new class wildcard matching "?", "? extends B" or "? parent B".
	 * @param ClassType $subclassOf Set using "? extends B", or NULL.
	 * @param ClassType $parentClassOf Set using "? parent B", or NULL.
	 * @throws InvalidArgumentException Both subclass and parent class parameters
	 * specified.
	 */
	public static function createWildcard($subclassOf, $parentClassOf) {
		if( $subclassOf !== NULL && $parentClassOf !== NULL )
			throw new InvalidArgumentException("class wildcard cannot be both subclass and parent class");
		
		if( $subclassOf === self::$object_class )
			$subclassOf = NULL;
		
		// Build the readable name:
		//$name = "?#" . (self::$wildcard_no++);
		$name = "?";
		if( $subclassOf !== NULL )
			$name .= " extends $subclassOf";
		if( $parentClassOf !== NULL )
			$name .= " parent $parentClassOf";
		
		// Build wildcard class:
		$c = new ClassType(new FullyQualifiedName($name, FALSE), Where::getSomewhere(), FALSE);
		$c->is_wildcard = TRUE;
		if( $parentClassOf === NULL ){
			if( $subclassOf === NULL ){
				$c->extended = ClassType::getObject();
			} else if( $subclassOf->is_interface ){
				$c->extended = ClassType::getObject();
				$c->implemented = array($subclassOf);
			} else {
				$c->extended = $subclassOf;
			}
		} else {
			$c->extended = ClassType::getObject();
			$c->parentClassOf = $parentClassOf;
		}
		
		return $c;
	}
	
	
	/**
	 * Case-insensitive hash of the class name.
	 * @return int 
	 */
	public function getHash(){
		return $this->name->getHash();
	}
	
	
	/**
	 * True if this class is exactly the other one.
	 * @param object $o
	 * @return boolean 
	 */
	public function equals($o){
		// There is always one single instance of a given class.
		return $this === $o;
	}
	
	
	/**
	 * Compares the name of this class with the other.
	 * Non-exception classes come first and are sorted by FQN.
	 * Exception classes are sorted deep-first, then by FQN; in this way
	 * exceptions can be listed in the proper order for the <code>catch(){}</code>
	 * statement.
	 * @param object $o
	 * @return int
	 * @throws CastException 
	 */
	public function compareTo($o){
		if( $o === $this )
			return 0;
		if( $o === NULL )
			throw new CastException("NULL");
		$o2 = cast(__CLASS__, $o);
		if( $this->is_exception ){
			if( $o2->is_exception ){
				if( $this->isSubclassOf($o2) )
					return -1;
				else if( $o2->isSubclassOf($this) )
					return +1;
				else
					return $this->name->compareTo($o2->name);
			} else {
				return +1;
			}
		} else if( $o2->is_exception ){
			return -1;
		} else {
			return $this->name->compareTo($o2->name);
		}
	}
	
	
	/**
	 * Returns true if this class is a valid actual implementation of the
	 * prototype. The actual implementation may add an extended class and
	 * may add other implemented interfaces. The other flags must be equal:
	 * is interface / abstract / class, is private, is final, is exception,
	 * is unchecked. Names and the "is forward" flag are not compared.
	 * @param ClassType $proto Other class, typically a prototype.
	 * @return boolean True if this class has the same prototype of the other.
	 */
	public function extendsPrototype($proto)
	{
		if( ! (
			$this->is_exception == $proto->is_exception
			&& $this->is_unchecked == $proto->is_unchecked
			&& $this->is_private == $proto->is_private
			&& $this->is_final == $proto->is_final
			&& $this->is_abstract == $proto->is_abstract
			&& $this->is_interface == $proto->is_interface
			&& ($proto->extended === NULL
				|| $proto->extended === self::$object_class
				|| $proto->extended === $this->extended )
			&& phplint_count($this->implemented) >= phplint_count($proto->implemented) 
		) )
			return FALSE;
		if( phplint_count($proto->implemented) == 0 )
			return TRUE;
		// This must contains all the interfaces of the proto:
		foreach($proto->implemented as $proto_iface){
			$found = FALSE;
			foreach($this->implemented as $this_iface){
				if( $this_iface === $proto_iface ){
					$found = TRUE;
					break;
				}
			}
			if( ! $found )
				return FALSE;
		}
		return TRUE;
	}
	
	
	/**
	 * Returns the "prototype" that includes the part before the class members.
	 * @return string Prototype of this class, for example: <code>"private
	 * interface Name extends A implements B, C"</code>.
	 */
	public function prototype()
	{
		$s = "";
		if( $this->is_private )
			$s .= "private ";
		if( $this->is_final )
			$s .= "final ";
		if( $this->is_unchecked )
			$s .= "unchecked ";
		if( $this->is_interface )
			$s .= "interface ";
		else if( $this->is_abstract )
			$s .= "abstract class ";
		else
			$s .= "class ";
		$s .= $this;
		if( $this->extended !== NULL && $this->extended !== self::$object_class )
			$s .= " extends " . $this->extended;
		if( phplint_count($this->implemented) > 0 ){
			if( $this->is_interface )
				$s .= " extends ";
			else
				$s .= " implements ";
			$s .= Arrays::implode($this->implemented, ", ");
		}
		return $s;
	}
	
	
	/**
	 * Returns a readable description of the class.
	 * @return string FQN of this class and its (mangled) type parameter names,
	 * or "?" with bound if wildcard.
	 */
	public function __toString() {
		$s = $this->name->getFullyQualifiedName();
		if( phplint_count($this->parameters_by_index) > 0 )
			$s .= "<" . Arrays::implode($this->parameters_by_index, ",") . ">";
		return $s;
	}
	
	
	/**
	 * Returns the singleton instance of the <code>object</code> base class.
	 * @return ClassType 
	 */
	public static function getObject(){
		return self::$object_class;
	}
	
	
	/**
	 * Search a class in an array of classes.
	 * @param ClassType $c Class to search.
	 * @param ClassType[int] $a Array of classes, possibly NULL.
	 * @return int Index of the instance found, or -1 if not found.
	 */
	public static function indexOf($c, $a){
		for($i = phplint_count($a) - 1; $i >= 0; $i--)
			if( $a[$i]->equals($c) )
				return $i;
		return -1;
	}
	
	
	/**
	 * Returns true if this class is subclass of the other, possibly also equal.
	 * Every class or interface is subclass of <code>object</code>.
	 * @param ClassType $other Another class, possibly NULL.
	 * @return boolean True if this class is subclass of the other, possibly
	 * also equal.
	 */
	public function isSubclassOf($other){
		if( $other === NULL )
			return FALSE;
		if( $this === $other )
			return TRUE;
		if( $other->is_final )
			return FALSE;
		
		if( $this->is_interface ){
			if( $other->is_interface ){
				if( $this->implemented !== NULL ){
					foreach($this->implemented as $iface){
						if( $iface === $other || $iface->isSubclassOf($other) )
							return TRUE;
					}
				}
			} else if( $other === self::$object_class ){
				return TRUE;
			}
		} else {
			if( $other->is_interface ){
				if( $this->extended !== NULL
				&& $this->extended->isSubclassOf($other) )
					return TRUE;
				if( $this->implemented !== NULL ){
					foreach($this->implemented as $iface){
						if( $iface === $other || $iface->isSubclassOf($other) )
							return TRUE;
					}
				}
			} else {
				$extended = $this->extended;
				while( $extended !== NULL ){
					if( $extended === $other )
						return TRUE;
					$extended = $extended->extended;
				}
			}
		}
		return FALSE;
	}
	
	
	/**
	 * Returns true if this class is strictly parent class of the other, not equal.
	 * Note that a class is subclass of itself, but it is not parent class of
	 * itself. The <code>object</code> base class is parent class of any other
	 * class or interface.
	 * @param ClassType $other Another class, possibly NULL.
	 * @return boolean True if this class is strictly parent class of the other.
	 */
	public function isParentClassOf($other){
		if( $other === NULL )
			return FALSE;
		if( $this === $other )
			return FALSE;
		if( $this->parentClassOf === NULL )
			return $other->isSubclassOf($this);
		else
			return $other->isSubclassOf($this->parentClassOf);
	}
	
	
	/**
	 * Returns true if this class is subclass of at least one of the given
	 * classes.
	 * @param ClassType[int] $others Other classes, possibly NULL or empty.
	 * @return boolean True if this class is subclass of at least one of the
	 * given classes.
	 */
	public function isSubclassOfAny($others){
		if( $others === NULL )
			return FALSE;
		for($i = phplint_count($others) - 1; $i >= 0; $i--)
			if( $this->isSubclassOf($others[$i]) )
				return TRUE;
		return FALSE;
	}
	
	
	/**
	 * Static initializer of this class, do not call. 
	 */
	public static function static_init(){
		self::$object_class = new ClassType(
			new FullyQualifiedName("object", FALSE),
			Where::getSomewhere(), TRUE);
	}
	
	
	/**
	 * Returns true if this (for example Box&lt;?&gt;) "captures" the value
	 * (for example Box&lt;A&gt;).
	 * @param self $value
	 * @return boolean
	 */
	private function capture($value) {
		if( $this->template === NULL || $this->template !== $value->template )
			return FALSE;
		for($i = phplint_count($this->parameters_by_index)-1; $i >= 0; $i--){
			$a = $this->parameters_by_index[$i];
			$b = $value->parameters_by_index[$i];
			if( ! $a->is_wildcard )
				return FALSE;
			if( $a->parentClassOf !== NULL ){
				if( ! $a->parentClassOf->assignableTo($b) )
					return FALSE;
			} else {
				if( phplint_count($a->implemented) > 0 )
					$c = $a->implemented[0];
				else
					$c = $a->extended;
				if( ! $b->isSubclassOf($c) )
					return FALSE;
			}
				
		}
		return TRUE;
	}
	
	
	/**
	 * Returns true if this (right hand side) is subclass of the left hand side
	 * (LHS), or the LHS is mixed or unknown.
	 * @param Type $lhs Type of the LHS.
	 * @return boolean True if this type is assignable to the LHS type.
	 */
	public function assignableTo($lhs){
		if( ($lhs instanceof MixedType)
		|| ($lhs instanceof UnknownType) )
			return TRUE;
		if( $this === $lhs )
			return TRUE;
		if( ! ($lhs instanceof ClassType) )
			return FALSE;
		$lhs_class = cast(__CLASS__, $lhs);
		if( $this->template !== NULL && $this->template === $lhs_class->template )
			return $lhs_class->capture($this);
		else if( $lhs_class->parentClassOf !== NULL )
			return $this->isSubclassOf($lhs_class->parentClassOf);
		else
			return $this->isSubclassOf($lhs_class);
	}
	
	/**
	 * @param Type $lhs
	 * @return boolean
	 */
	public function canCastTo($lhs) {
		if( ($lhs instanceof MixedType)
		|| ($lhs instanceof UnknownType) )
			return TRUE;
		if( ! ($lhs instanceof ClassType) )
			return FALSE;
		$lhs_class = cast(__CLASS__, $lhs);
		if( $this->is_wildcard )
			return $lhs_class->isSubclassOf($this->extended);
		else
			return $lhs_class->isSubclassOf($this);
	}
	
	
	/**
	 * Search a constant, first looking in this class, then in the extended
	 * classes, and finally in the implemented interfaces.
	 * @param string $name Name of the constant.
	 * @return ClassConstant Found constant, or NULL if not found.
	 */
	public function searchConstant($name){
		if( array_key_exists($name, $this->constants) )
			return $this->constants[$name];
		
		if( $this->extended !== NULL ){
			$c = $this->extended->searchConstant($name);
			if( $c !== NULL )
				return $c;
		}
		
		for($i = phplint_count($this->implemented) - 1; $i >= 0; $i--){
			$c = $this->implemented[$i]->searchConstant($name);
			if( $c !== NULL )
				return $c;
		}
		
		return NULL;
	}
	
	
	/**
	 * Search a property, first looking in this class, then in the extended
	 * classes.
	 * @param string $name Name of the property, without leading dollar sign.
	 * @return ClassProperty Found property, or NULL if not found.
	 */
	public function searchProperty($name){
		if( array_key_exists($name, $this->properties) )
			return $this->properties[$name];
		if( $this->extended === NULL )
			return NULL;
		else
			return $this->extended->searchProperty($name);
	}
	
	
	/**
	 * Search a method, first looking in this class, then in the extended
	 * classes, and finally in the interfaces, in this order.
	 * @param CaseInsensitiveString $name Name of the method.
	 * @return ClassMethod Method found, or NULL if not found.
	 */
	public function searchMethod($name)
	{
		$m = cast(ClassMethod::class, $this->methods->get($name));
		if( $m !== NULL )
			return $m;
		
		if( $this->extended !== NULL ){
			$m = $this->extended->searchMethod($name);
			if( $m !== NULL )
				return $m;
		}
		
		if( $this->implemented !== NULL ){
			foreach($this->implemented as $iface){
				$m = $iface->searchMethod($name);
				if( $m !== NULL )
					return $m;
			}
		}
		
		return NULL;
	}
	
	
	/**
	 * Returns the first parent constructor.
	 * @return ClassMethod First parent constructor, or NULL.
	 */
	public function parentConstructor()
	{
		$c = $this->extended;
		while( $c !== NULL ){
			if( $c->constructor !== NULL )
				return $c->constructor;
			$c = $c->extended;
		}
		return NULL;
	}
	
	
	/**
	 * Returns the first parent destructor.
	 * @return ClassMethod First parent destructor, or NULL.
	 */
	public function parentDestructor()
	{
		$c = $this->extended;
		while( $c !== NULL ){
			if( $c->destructor !== NULL )
				return $c->destructor;
			$c = $c->extended;
		}
		return NULL;
	}
	
	
	public function isPrintable()
	{
		return $this->searchMethod(ClassMethod::$TO_STRING_NAME) !== NULL;
	}
	
	
	/**
	 * Tells if this class if real or fully actualized.
	 * @return boolean
	 */
	public function isRealOrFullyActualized() {
		return $this->is_real_or_fully_actualized;
	}
	
	
	/**
	 * Returns the mangled name of this generic class actualized with the actual
	 * types specified. The resulting name has form
	 * ThisClassName&lt;A,B,C&gt; being A,B,C the actual type parameters; the FQNs
	 * of the type parameters have the back-slash replaced with dot to avoid
	 * ambiguity with the base name inside the FullyQualifiedName class.
	 * @param Type[int] $actual_types Actual types replacing the type parameters.
	 * @return string
	 */
	private function actualizedName($actual_types) {
		return $this->name . "<" . Arrays::implode($actual_types, ",") . ">";
	}
	
	/**
	 * Initializes a new, empty actualized classes cache. Should be called
	 * by the global module before usis this class.
	 */
	public static function initActualizedClassesCache() {
		self::$actualized_classes = new HashMap();
	}
	
	
	/**
	 * Returns the actual class out of this generic class. Does nothing if this
	 * is a real class (not a template, not an actualized class). Results are
	 * cached internally.
	 * @param HashMap $replacements Actual types replacing the type parameters.
	 * @return ClassType Actualized class with all the specified parameters replaced.
	 */
	public function actualize($replacements) {
		if( $this->is_real_or_fully_actualized )
			return $this;
		
		// Actualize all the type parameters of this template or partially
		// actualized class:
		$actual_types = /*. (ClassType[int]) .*/ array();
		$is_actualized = FALSE;
		foreach($this->parameters_by_index as $formal_type){
			$actual_type = $formal_type->actualize($replacements);
			if( $actual_type !== $formal_type )
				$is_actualized = TRUE;
			$actual_types[] = $actual_type;
		}
		if( ! $is_actualized )
			return $this; // no replacements found, nothing to actualize
		
		// Build the actual name and search in the cache first:
		$actual_name = $this->actualizedName($actual_types);
		$actual_class = cast(__CLASS__, self::$actualized_classes->get($actual_name));
		if( $actual_class !== NULL )
			return $actual_class;
		
		// Create the actualized class:
		$actual_class = clone $this;
//		$actual_class->name = same name
//		$actual_class->is_anonymous = always FALSE for template
//		$actual_class->decl_in = copied
		$actual_class->used = 0; // usage accounting is made on the template only
//		$actual_class->is_exception = copied
//		$actual_class->is_unchecked = copied
//		$actual_class->is_forward = ????  FIXME
//		$actual_class->is_private, is_final, is_abstract, is_interface, is_internal = copied
		$actual_class->constructor_first_used_here = NULL;
//		$actual_class->parent_constructor_called = ignored; checked on template only
//		$actual_class->parent_destructor_called = ignored; checked on template only
		$actual_class->docblock = NULL; // useless, never reported
		$actual_class->template = $this;
		$actual_class->is_template = FALSE;
		$actual_class->parameters_by_name = NULL;
		$actual_class->parameters_by_index = $actual_types;
		
		// Set the "reality" property:
		$actual_class->is_real_or_fully_actualized = TRUE;
		foreach($actual_class->parameters_by_index as $parameter){
			if( ! $parameter->isRealOrFullyActualized() ){
				$actual_class->is_real_or_fully_actualized = FALSE;
				break;
			}
		}
		
		// Add actualized class to the cache before actualizing its extended
		// classes and members, which may reference this same actualization:
		self::$actualized_classes->put($actual_name, $actual_class);
		
		// Actualize extended class:
		if( $this->extended !== NULL )
			$actual_class->extended = $this->extended->actualize($replacements);
		
		// Actualize implemented classes:
		if( phplint_count($this->implemented) > 0 ){
			$actual_implemented = /*. (ClassType[int]) .*/ array();
			foreach($this->implemented as $implemented)
				$actual_implemented[] = $implemented->actualize($replacements);
			$actual_class->implemented = $actual_implemented;
		}
		
		// Actualize lowed boundary of "? parent B":
		if( $this->parentClassOf !== NULL )
			$this->parentClassOf = $this->parentClassOf->actualize($replacements);
		
		// Constants are simply copied:
		$actual_class->constants = $this->constants;
		
		// Actualize properties:
		$actual_properties = /*. (ClassProperty[string]) .*/ array();
		foreach($actual_class->properties as $property){
			if( $property->is_static ){
				$actual_property = $property;
			} else {
				$actual_property = $property->actualize($replacements);
				$actual_property->class_ = $actual_class;
				$actual_properties[$property->name] = $actual_property;
			}
		}
		$actual_class->properties = $actual_properties;
		
		// Actualize methods:
		$actual_methods = new HashMap();
		foreach($actual_class->methods as $method_mixed){
			$method = cast(ClassMethod::class, $method_mixed);
			if( $method->is_static ){
				$actual_method = $method;
			} else {
				$actual_method = $method->actualize($replacements);
				$actual_method->class_ = $actual_class;
				if( $method->is_constructor )
					$actual_class->constructor = $actual_method;
				else if( $method === $this->destructor )
					$actual_class->destructor = $actual_method;
			}
			$actual_methods->put($actual_method->name, $actual_method);
		}
		$actual_class->methods = $actual_methods;
		
		return $actual_class;
	}
	
	
	/**
	 * Returns the default actualization of this template class.
	 * @return self
	 */
	public function getDefaultActualization() {
		if( $this->default_actualization !== NULL )
			return $this->default_actualization;
		
		if( phplint_count($this->parameters_by_name) == 0 )
			return $this;
		
		$replacements = new HashMap();
		foreach($this->parameters_by_name as $formal_type){
			$bounds = $formal_type->getBounds();
			if( phplint_count($bounds) > 0 )
				$extended = $bounds[0];
			else
				$extended = NULL;
			$actual_type = ClassType::createWildcard($extended, NULL);
			$replacements->put($formal_type, $actual_type);
		}
		$raw_type = $this->actualize($replacements);
		return $this->default_actualization = $raw_type;
	}

}


ClassType::static_init();
