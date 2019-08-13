<?php

namespace it\icosaedro\lint;

require_once __DIR__ . "/../../../all.php";

use it\icosaedro\lint\PhpVersion;
use it\icosaedro\lint\types\ClassType;

/**
 * Special classes that are built-in in PHP5 are listed here. Fully qualified
 * names of these classes allows the code that parses classes to easily
 * recognize them (the FullyQualifiedName class provides an equals() method
 * just to do that in the right way). Once detected, references to these
 * special classes are stored here.
 * @author Umberto Salsi <salsi@icosaedro.it>
 * @version $Date: 2016/03/01 12:06:03 $
 */
class BuiltinClasses {
	
	/**
	 * Read-only FQN of some special, built-in classes.
	 * @var FullyQualifiedName
	 */
	private static
		$THROWABLE_FQN,
		$ERROR_FQN,
		$EXCEPTION_FQN,
		$TRAVERSABLE_FQN,
		$ITERATOR_FQN,
		$ITERATORAGGREGATE_FQN,
		$COUNTABLE_FQN,
		$ARRAYACCESS_FQN;
	
	/**
	 * Some special classes. These variables are set only if the specific
	 * module is loaded, that is 'standard' and 'spl'. Normally NULL.
	 * @var ClassType
	 */
	public
		/* Classes from standard module: */
		$ThrowableClass,
		$ErrorClass,
		$ExceptionClass,
		/* Classes from spl module: */
		$TraversableClass,
		$IteratorClass,
		$IteratorAggregateClass,
		$CountableClass,
		$ArrayAccessClass;
	
	
	/**
	 * Static initializer, do no call.
	 * @return void
	 */
	public static function static_init()
	{
		self::$THROWABLE_FQN = new FullyQualifiedName("Throwable", FALSE);
		self::$ERROR_FQN = new FullyQualifiedName("Error", FALSE);
		self::$EXCEPTION_FQN = new FullyQualifiedName("Exception", FALSE);
		self::$TRAVERSABLE_FQN = new FullyQualifiedName("Traversable", FALSE);
		self::$ITERATOR_FQN = new FullyQualifiedName("Iterator", FALSE);
		self::$ITERATORAGGREGATE_FQN = new FullyQualifiedName("IteratorAggregate", FALSE);
		self::$COUNTABLE_FQN = new FullyQualifiedName("Countable", FALSE);
		self::$ARRAYACCESS_FQN = new FullyQualifiedName("ArrayAccess", FALSE);
	}
	
	
	/**
	 * Detects if the given class is one of the special classes and, if so,
	 * store it for later reference.
	 * @param ClassType $c Class parsed right now.
	 * @param PhpVersion $php_ver
	 * @return void
	 */
	public function detect($c, $php_ver = NULL)
	{
		if( $php_ver === PhpVersion::$php7 ){
			if( $c->name->equals(self::$THROWABLE_FQN) ){
				$this->ThrowableClass = $c;
				$c->is_exception = TRUE;
				return;
			} else if( $c->name->equals(self::$ERROR_FQN) ){
				$this->ErrorClass = $c;
				return;
			}
		}
		
		if( $c->name->equals(self::$EXCEPTION_FQN) ){
			$this->ExceptionClass = $c;
			$c->is_exception = TRUE;
		} else if( $c->name->equals(self::$TRAVERSABLE_FQN) ){
			$this->TraversableClass = $c;
		} else if( $c->name->equals(self::$ITERATOR_FQN) ){
			$this->IteratorClass = $c;
		} else if( $c->name->equals(self::$ITERATORAGGREGATE_FQN) ){
			$this->IteratorAggregateClass = $c;
		} else if( $c->name->equals(self::$COUNTABLE_FQN) ){
			$this->CountableClass = $c;
		} else if( $c->name->equals(self::$ARRAYACCESS_FQN) ){
			$this->ArrayAccessClass = $c;
		}
	}

}

BuiltinClasses::static_init();
