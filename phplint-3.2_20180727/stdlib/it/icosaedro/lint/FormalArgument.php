<?php

namespace it\icosaedro\lint;
require_once __DIR__ . "/../../../all.php";
use it\icosaedro\lint\types\Type;
use it\icosaedro\lint\types\ClassType;
use it\icosaedro\containers\Comparable;
use it\icosaedro\containers\Printable;
use it\icosaedro\containers\HashMap;
use it\icosaedro\lint\types\StringType;
use it\icosaedro\utils\Strings;

/**
 * Represents a single formal argument of a function or method. A formal
 * argument can be passed by value or by reference, and may or may not have a
 * default value.
 * @author Umberto Salsi <salsi@icosaedro.it>
 * @version $Date: 2016/02/22 08:35:01 $
 */
class FormalArgument implements Comparable, Printable {
	
	/**
	 * If this formal argument is the variadic argument "... $a", then the last
	 * formal argument of a function or method.
	 * @var boolean
	 */
	public $is_variadic = FALSE;

	/**
	 * Name of the formal argument, without leading "$".
	 * @var string
	 */
	public $name;

	/**
	 * TRUE if the argument is passed by reference <i>and</i> its
	 * value is assigned by the function or method being
	 * called, for example<br>
	 * <code>/&#42;. return int .&#42;/ &amp; $arg</code><br>
	 * or the variable is passed by reference and a default value
	 * is assigned, for example<br>
	 * <code>&amp; $arg = EXPR</code><br>
	 * True also if the argument is passed by reference and a default value
	 * is available, example: <code>function f(&amp; $x = 0){}</code><br>
	 * The variable passed may be unassigned when the function
	 * or method is called, but it is guaranteed it is assigned
	 * when it returns. If the variable is not passed by
	 * reference, this flag is set to FALSE.
	 * @var boolean
	 */
	public $reference_return = FALSE;

	/**
	 * TRUE if the argument is passed by reference. For example<br>
	 * <code>/&#42;. int .&#42;/ &amp; $arg</code><br>
	 * @var boolean
	 */
	public $reference = FALSE;

	/**
	 * Type of the formal argument. If unknown or undetermined, it is set to
	 * the `UnknownType' to prevent errors from being logger every time that
	 * unknown argument is used in the source.
	 * For variadic argument, this is the type of each allowed actual argument.
	 * @var Type
	 */
	public $type;

	/**
	 * TRUE if this argument is mandatory. Mandatory arguments do not have a
	 * default value, with the only exception of the variadic argument which is
	 * not mandatory and has no default value.
	 * @var boolean
	 */
	public $is_mandatory = FALSE;

	/**
	 * NULL if the argument is mandatory, otherwise it is the default value.
	 * This property can be NULL if the expression cannot be parsed.
	 * Variadic argument cannot have a default value.
	 * @var Result
	 */
	public $value;
	
	/*.
	forward public boolean function callCompatibleWith(self $other);
	forward public string function __toString();
	forward public FormalArgument function actualize(HashMap $actual_types);
	forward public boolean function equals(object $other);
	pragma 'suspend';
	.*/
	
	
	/**
	 * Compares this formal argument with another for equality. Two formal
	 * arguments are equal to each other if their properties are equal,
	 * name apart.
	 * @param object $other Other formal argument.
	 * @return boolean True if this formal argument is exactly equals to the
	 * other, name apart.
	 */
	public function equals($other){
		if( $other === NULL )
			return FALSE;
		
		if( $this === $other )
			return TRUE;

		if( !($other instanceof self) )
			return FALSE;
		
		$other2 = cast(__CLASS__, $other);
		
		return
			$this->is_variadic == $other2->is_variadic
			&& $this->reference_return == $other2->reference_return
			&& $this->reference == $other2->reference
			&& $this->type->equals($other2->type)
			&& $this->is_mandatory == $other2->is_mandatory
			// FIXME: can't check actual default value of the formal argument
			// because sometimes it isn't fully represented here;
			// also check prototypes.
			//&& $this->value === $other2->value
			;
	}
	
	
	/**
	 * Returns the readable representation of this formal argument.
	 * @return string Readable representation of this formal argument in the
	 * form <code>[return] TYPE [&amp;] $NAME [= VALUE]</code>.
	 */
	public function __toString(){
		$s = "";
		if( $this->reference_return )
			$s .= "return ";
		$s .= $this->type->__toString() . " ";
		if( $this->reference )
			$s .= "& ";
		if( $this->is_variadic )
			$s .= "... ";
		$s .= "\$" . $this->name;
		if( ! $this->is_mandatory ){
			$s .= " = ";
			if( $this->value === NULL )
				$s .= "?";
			else {
				$v = $this->value->getValue();
				if( $this->type instanceof StringType ){
					if( $v === "NULL" ){
						// FIXME: what if the literal string is just "NULL"?
						// Unsatisfactory workaround; see also the comment about the
						// Result::$value property.
						$s .= "NULL";
					} else {
						$s .= Strings::toLiteral($v);
					}
				} else {
						$s .= $v;
				}
			}
		}
		return $s;
	}
	
	
	/**
	 * Checks if this argument is call-compatible with the other argument.
	 * That is, if this formal argument represents something compatible with
	 * another argument of function/method which is expecting $other.
	 * @param FormalArgument $other 
	 * @return boolean True if this argument is call-compatible with the other.
	 */
	public function callCompatibleWith($other)
	{
		if( $this->is_variadic != $other->is_variadic )
			return FALSE;
		
		if( $this->reference_return != $other->reference_return )
			return FALSE;
		
		if( $this->reference != $other->reference )
			return FALSE;
		
		if( $this->type instanceof ClassType
		&& $other->type instanceof ClassType ){
			$this_c = cast(ClassType::class, $this->type);
			$other_c = cast(ClassType::class, $other->type);
			if( ! $other_c->isSubclassOf($this_c) )
				return FALSE;
		} else {
			if( ! $this->type->equals($other->type) )
				return FALSE;
		}
		
		if( $this->is_mandatory && ! $other->is_mandatory )
			return FALSE;
		
		return TRUE;
	}
	
	
	/**
	 * Returns a brand new actualized formal argument out of this generic formal
	 * argument belonging to a method of a generic class.
	 * @param HashMap $actual_types Actual types replacing the type parameters.
	 * @return self May return $this if this formal argument is not generic.
	 */
	public function actualize($actual_types) {
		$actual_type = $this->type->actualize($actual_types);
		if( $actual_type === $this->type )
			return $this;
		$actual_param = clone $this;
		$actual_param->type = $actual_type;
//		echo "argomento $this --> $actual_param\n";
		return $actual_param;
	}

}
