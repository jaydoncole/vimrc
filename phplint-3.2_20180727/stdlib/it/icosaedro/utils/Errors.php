<?php

namespace it\icosaedro\utils;
/*. require_module 'standard_reflection'; .*/
require_once __DIR__ . "/Strings.php";
require_once __DIR__ . "/TestUnit.php";

/**
 * Errors and exceptions utilities.
 * @author Umberto Salsi <salsi@icosaedro.it>
 * @version $Date: 2015/10/22 04:18:39 $
 */
class Errors {
	
	/**
	 * Retrieves the name of a global constant given name prefix and value.
	 * This function allows to build a more descriptive message of errors codes
	 * returned by some system functions.
	 * @param string $name_prefix Prefix of the name of the constant.
	 * @param mixed $value Value of the constant.
	 * @return string Detected name of the constant, or the textual representation
	 * of the value if a global constant with this prefix has not been found.
	 */
	public static function getConstantName($name_prefix, $value) {
		$constants = get_defined_constants();
		foreach ($constants as $name => $value2) {
			if( Strings::startsWith($name, $name_prefix) && $value2 === $value ){
				return $name;
			}
		}
		return TestUnit::dump($value);
	}

	/**
	 * Retrieves the name of a class constant given class name and class constant
	 * name prefix. This function allows to build a more descriptive message of
	 * errors codes returned by some system functions.
	 * @param string $class_fqn Fully qualified name of the class.
	 * @param string $name_prefix Prefix of the name of the class constant, possibly
	 * inherited.
	 * @param mixed $value Value of the constant.
	 * @return string Detected name of the constant, or the textual representation
	 * of the value if a class constant with this prefix has not been found.
	 */
	public static function getClassConstantName($class_fqn, $name_prefix, $value) {
		$r = new \ReflectionClass($class_fqn);
		$constants = $r->getConstants();
		foreach ($constants as $name => $value2) {
			if( Strings::startsWith($name, $name_prefix) && $value2 === $value ){
				return $name;
			}
		}
		return TestUnit::dump($value);
	}
	
}
