<?php

namespace it\icosaedro\containers;

require_once __DIR__ . "/../../../autoload.php";

use CastException;

/**
	Generic interface that defines a sorter object among the objects
	of a given set. Sorting algorithms may then use such an object
	to establish an ordering among generic objects. For objects that
	already implements the Sortable interface, a sorter object allows
	to implement even more specialized sorting criteria beside that
	these objects already implement. See for example the
	Arrays::sortBySorter() method.
	@author Umberto Salsi <salsi@icosaedro.it>
	@version $Date: 2016/02/20 00:03:23 $
*/
interface Sorter {

	/**
		Compare two objects.
		@param object $a The first object.
		@param object $b The second object.
		@return int Negative, zero or positive if $a is less, equal or
		greater than $b respectively.
		@throws CastException If $a or $b does not belong to the expected
		class or extended class.
	*/
	function compare($a, $b);

}
