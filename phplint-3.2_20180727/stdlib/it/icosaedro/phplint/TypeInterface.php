<?php

namespace it\icosaedro\phplint;

require_once __DIR__ . "/../containers/Printable.php";

use it\icosaedro\containers\Printable;

/**
	Interface to an object that describes a PHPLint type of data. For every
	type of data (int, float, string, array, ...) a specific implementation
	class must then be provided.
	The Types::parseType() method uses this class to represent the result
	of the compilation of a textual type descriptor. The cast(T,V) magic function
	uses these pre-compiled types to quickly test is the given value V actually
	matches the expected type T.
	<p>
	All the classes under this namespace belong to the run-time code that implements
	the cast(T,V) magic function, and are not actually related to the PHPLint
	program itself.
	@author Umberto Salsi <salsi@icosaedro.it>
	@version $Date: 2015/06/29 21:19:18 $
*/
interface TypeInterface extends Printable {

	/**
		Checks if the passed value matches the expected type. The expected type
		depends on the specific implementation of this interface.
		@param mixed $v Any expression or variable.
		@return bool True if the passed value matches the expected type.
	*/
	function check($v);
	
	# NOTE: PHP does not allow to extend an abs method: here it
	# would be useful to describe this "implementation".
	# Maybe, in some future release of PHP, ...
	/* *
		Returns the descriptive string that represents this type.
		@return string Descriptive string that represents this type.
		For example "int", "float", "it\\icosaedro\\phplint\\BigInt",
		"string[int]", ... See class it\icosaedro\phplint\Types
		for a more detailed description of the syntax.
	*/
	#function __toString();

}
