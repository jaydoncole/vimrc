<?php

namespace it\icosaedro\lint;
require_once __DIR__ . "/../../../all.php";
use it\icosaedro\containers\Comparable;
use it\icosaedro\containers\Printable;
use it\icosaedro\containers\HashMap;
use it\icosaedro\lint\types\Type;
use it\icosaedro\lint\types\VoidType;
use it\icosaedro\lint\types\GuessType;
use it\icosaedro\lint\types\ArrayType;
use it\icosaedro\lint\types\ClassType;

/**
 * Signature of function or method. Includes: arguments, return type, triggered
 * errors, thrown exceptions.
 * @author Umberto Salsi <salsi@icosaedro.it>
 * @version $Date: 2016/02/22 08:37:48 $
 */
class Signature implements Comparable, Printable {

	/**
	 * Type of the returned value.
	 * @var Type 
	 */
	public $returns;

	/**
	 * Function returns a reference: function &amp; f(...
	 * @var boolean 
	 */
	public $reference = FALSE;

	/**
	 * No. of mandatory arguments.
	 * @var int 
	 */
	public $mandatory = 0;

	/**
	 * Formal arguments.
	 * @var FormalArgument[int] 
	 */
	public $arguments;
	
	/**
	 * If not NULL, it is the last formal argument of a variadic function or
	 * method. For example in function f($a, ...$b) the last formal argument
	 * is $b. The type T is the type of each allowed variadic actual argument.
	 * Obviously, the formal argument will be assumed to be an array T[int].
	 * For variadic functions, the $more_args property is never set, so you may
	 * either use the old "args" meta-code keyword or the new "..." PHP syntax,
	 * not both.
	 * @var FormalArgument
	 */
	public $variadic;

	/**
	 * Allows an arbitrary number of arguments beyond those explicitly
	 * listed in the PHP signature. These arguments can then be retrieved
	 * using {@link func_num_args()} and {@link func_get_arg()}. Under
	 * PHPLint the special notation <code>/&#42;. args .&#42;/</code> must
	 * be indicated.
	 * @var boolean
	 */
	public $more_args = FALSE;
	
	/**
	 * Triggered errors set.
	 * @var ErrorsSet 
	 */
	public $errors;
	
	/**
	 * Thrown exceptions.
	 * @var ExceptionsSet 
	 */
	public $exceptions;
	
	/*.
	forward public void function __construct();
	forward public string function __toString();
	forward public Signature function actualize(HashMap $replacements);
	forward public string function callCompatibleWithReason(Signature $other);
	forward public boolean function equals(object $other);
	pragma 'suspend';
	.*/
	
	
	/**
	 * Creates a new default signature "$RETURN_TYPE_STILL_TO_GUESS()".
	 * WARNING. The errors set and the exceptions set are the singleton,
	 * immutable instances of their respective set classes; functions and
	 * methods that trigger errors or throw exceptions MUST create a specific
	 * instance of the involved set.
	 * @return void
	 */
	public function __construct()
	{
		$this->returns = GuessType::getInstance();
		$this->reference = FALSE;
		$this->mandatory = 0;
		$this->arguments = /*.(FormalArgument[int]).*/ array();
		$this->variadic = NULL;
		$this->more_args = FALSE;
		$this->errors = ErrorsSet::getEmpty();
		$this->exceptions = ExceptionsSet::getEmpty();
	}
	

	/**
	 * Returns this signature as a string. The syntax is:
	 * 
	 * <pre>
	 *	TYPE[&amp;]([&amp;]TYPE, [&amp;]TYPE, TYPE =, TYPE =, ...)
	 * </pre>
	 * 
	 * For example,
	 * 
	 * <pre>
	 *	/&#42;. int .&#42;/ function f(&amp;$a, $b=4 /&#42;., args.&#42;/)
	 * </pre>
	 * 
	 * returns
	 * 
	 * <pre>
	 *	int(&amp;mixed, int =, ...)
	 * </pre>
	 * 
	 * @return string
	 */
	public function __toString(){
		$s = $this->returns->__toString();
		if( $this->reference ){
			$s .= " &";
		}
		$s .= "(";
		
		$need_comma = FALSE; // put comma after first arg

		# Mandatory and default args:
		foreach($this->arguments as $a){
			if( $need_comma )
				$s .= ", ";
			$need_comma = TRUE;
			if( $a->reference_return )
				$s .= "return ";
			$s = $s . $a->type;
			if( $a->reference )
				$s .= " &";
			if( ! $a->is_mandatory )
				$s .= " =";
		}
		
		if( $this->variadic !== NULL ){
			if( $need_comma )
				$s .= ", ";
			$need_comma = TRUE;
			$a = $this->variadic;
			if( $a->reference_return )
				$s .= "return ";
			$s = $s . cast(ArrayType::class, $a->type)->getElem() . " ";
			if( $a->reference )
				$s .= "& ";
			$s .= "...";
		}

		if( $this->more_args ){
			if( $need_comma )
				$s .= ", ";
			$need_comma = TRUE;
			$s .= "...";
		}
		
		$s = $s . ")";
		
		if( ! $this->errors->isEmpty() )
			$s .= " triggers " . $this->errors;
		
		if( ! $this->exceptions->isEmpty() )
			$s .= " throws " . $this->exceptions;
		
		return $s;
	}
	
	
	/**
	 * Compares this signature with another for equality.
	 * @param object $other Other signature.
	 * @return boolean True if this signature equals the other.
	 */
	public function equals($other){
		if( $other === NULL )
			return FALSE;
		
		if( $this === $other )
			return TRUE;

		if( ! ($other instanceof Signature) )
			return FALSE;
		
		$other2 = cast(__CLASS__, $other);
		
		if( ! (
			$this->returns->equals($other2->returns)
			&& $this->reference == $other2->reference
			&& $this->mandatory == $other2->mandatory
			&& count($this->arguments) == count($other2->arguments)
			&& ($this->variadic === NULL && $other2->variadic === NULL
				|| $this->variadic !== NULL && $this->variadic->equals($other2->variadic))
			&& $this->more_args == $other2->more_args
			&& $this->errors->equals($other2->errors)
			&& $this->exceptions->equals($other2->exceptions)
		) )
			return FALSE;
		
		for($i = count($this->arguments) - 1; $i >= 0; $i--){
			$a = $this->arguments[$i];
			$b = $other2->arguments[$i];
			if( ! $a->equals($b) )
				return FALSE;
		}
		
		return TRUE;
	}
	
	
//	/**
//	 * Checks if this signature is call-compatible with another signature.
//	 * This signature is call-compatible with the other signature if an actual
//	 * call to the function/method with the other signature is also valid
//	 * for this signature, that is this is a valid overriding or implementing
//	 * signature.
//	 * @param Signature $other Signature of the called function/method.
//	 * @return boolean True if this method is call-compatible with the other.
//	 */
//	public function callCompatibleWith($other)
//	{
//		if( $this->reference != $other->reference )
//			return FALSE;
//		
//		if( $this->reference ){
//			if( ! $this->returns->equals($other->returns) )
//				return FALSE;
//		} else {
//			if( ! (
//				$this->returns instanceof VoidType
//					&& $other->returns instanceof VoidType
//				|| $this->returns->assignableTo($other->returns)
//			) )
//				return FALSE;
//		}
//		
//		/*
//		 * Or $this allows an arbitrary number of arguments, or $other does not,
//		 * so that, for example, m($x,args) implements m($x) and m($x,args),
//		 * while m($x) can only implement m($x) itself:
//		 */
//		if( !( $this->more_args || ! $other->more_args ) )
//			return FALSE;
//		
//		/*
//		 * $this may set default values for existing mandatory args,
//		 * and may also add more arguments with default value:
//		 */
//		if( ! ( $this->mandatory <= $other->mandatory
//			&& count($this->arguments) >= count($other->arguments) ) )
//			return FALSE;
//		
//		// Minimum no. of common args:
//		$common = count($other->arguments);
//		
//		for($i = 0; $i < $common; $i++)
//			if( ! $this->arguments[$i]->callCompatibleWith($other->arguments[$i]) )
//				return FALSE;
//		
//		if( ! $other->errors->containsAll($this->errors) )
//			return FALSE;
//		
//		if( ! $other->exceptions->containsAll($this->exceptions) )
//			return FALSE;
//		
//		return TRUE;
//	}
	
	
	/**
	 * Checks if this signature is call-compatible with another signature.
	 * This signature is call-compatible with the other signature if any actual
	 * call to the method with the other signature is also valid
	 * for this signature, that is this is a valid overriding or implementing
	 * signature.
	 * @param Signature $other Signature of the called method.
	 * @return string Empty string if compatible, otherwise the description
	 * of the incompatibility.
	 */
	public function callCompatibleWithReason($other)
	{
		if( $this->reference != $other->reference )
			return "return by-reference/by-value differs";
		
		if( $this->reference ){
			if( ! $this->returns->equals($other->returns) )
				return "return types differs";
		} else {
			if( ! (
				$this->returns instanceof VoidType
					&& $other->returns instanceof VoidType
				|| $this->returns->assignableTo($other->returns)
			) )
				return "return type "
				. $this->returns . " cannot be assigned to "
				. $other->returns;
		}
		
		/*
		 * Or $this allows an arbitrary number of arguments, or $other does not,
		 * so that, for example, m($x,args) implements m($x) and m($x,args),
		 * while m($x) can only implement m($x) itself:
		 */
		if( !( $this->more_args || ! $other->more_args ) )
			return "incompatible args dummy argument";
		
		/*
		 * $this may set default values for existing mandatory args,
		 * and may also add more arguments with default value:
		 */
		if( $this->mandatory > $other->mandatory )
			return "too many mandatory arguments";
		if( count($this->arguments) < count($other->arguments) )
			return "too few default arguments";
		
		// Minimum no. of common args:
		$common = count($other->arguments);
		
		for($i = 0; $i < $common; $i++)
			if( ! $this->arguments[$i]->callCompatibleWith($other->arguments[$i]) )
				return "argument no. " . ($i+1) . " is not call-compatible";
		
		// $this may add a variadic parameter:
		if( $other->variadic === NULL ){
			// $this may add a variadic parameter of arbitrary type
		} else {
			if( $this->variadic === NULL )
				return "missing variadic parameter";
			else if( ! $this->variadic->callCompatibleWith($other->variadic))
				return "the type of the variadic parameter is not call-compatible";
		}
		
		// This can trigger less errors, not more:
		if( ! $other->errors->containsAll($this->errors) )
			return "unexpected triggered errors";
		
		// This can throw the same ex. or more specialized:
		if( ! $this->exceptions->callCompatibleWith($other->exceptions) )
			return "unexpected thrown checked exceptions";
		
		return "";
	}
	
	
	/**
	 * Returns a brand new actualized signature out of this generic signature
	 * belonging to a method of a generic class.
	 * @param HashMap $replacements Actual types replacing the type parameters.
	 * @return self May return $this if this signature does not contain type
	 * parameters.
	 */
	public function actualize($replacements) {
		$found_generic = FALSE;
		
		// Actualizes the return type:
		$actual_returns = $this->returns->actualize($replacements);
		$found_generic = $found_generic || $actual_returns !== $this->returns;
		
		// Actualizes the formal arguments:
		$actual_arguments = /*. (FormalArgument[int]) .*/ array();
		foreach($this->arguments as $argument){
			$actual_argument = $argument->actualize($replacements);
			$found_generic = $found_generic || $actual_argument !== $argument;
			$actual_arguments[] = $actual_argument;
//			echo "argument $actual_argument\n";
		}
		
		// Actualizes the variadic argument:
		if( $this->variadic !== NULL ){
			$actual_variadic = $this->variadic->actualize($replacements);
			$found_generic = $found_generic || $actual_variadic !== $this->variadic;
		} else {
			$actual_variadic = NULL;
		}
		
		// Actualize thrown exceptions:
		if( $this->exceptions->isEmpty() ){
			$actual_exceptions = ExceptionsSet::getEmpty();
		} else {
			$actual_exceptions = new ExceptionsSet();
			foreach($this->exceptions as $mixed_ex){
				$ex = cast(ClassType::class, $mixed_ex);
				$actual_ex = $ex->actualize($replacements);
				$actual_exceptions->put($actual_ex);
				$found_generic = $found_generic || $actual_ex !== $ex;
			}
			$actual_exceptions->close();
		}
		
		if( ! $found_generic )
			return $this;
		
		$actual_sign = clone $this;
		$actual_sign->returns = $actual_returns;
		$actual_sign->arguments = $actual_arguments;
		$actual_sign->variadic = $actual_variadic;
		$actual_sign->exceptions = $actual_exceptions;
		return $actual_sign;
	}

}
