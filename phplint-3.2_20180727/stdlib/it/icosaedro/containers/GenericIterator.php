<?php

/*. require_module 'spl'; .*/

namespace it\icosaedro\containers;

require_once __DIR__ . "/../../../all.php";

use Iterator;
use LogicException;

/**
 * Generic iterator over a collection of (key,value) pairs. This interface
 * differs from the usual Interator interface in two ways: only objects are
 * allowed for keys and values; a specific exception is thrown operating on
 * an undefined cursor position. Classes implementing this interface are also
 * iterable in the foreach() statement.
 * @author Umberto Salsi <salsi@icosaedro.it>
 * @version $Date: 2016/02/21 22:47:42 $
 */
interface GenericIterator/*.<K,V>.*/ extends Iterator {

	/**
	 * Moves the cursor to the first (key,element) pair.
	 * @return void
	 */
	function rewind();

	/**
	 * Check if the cursor is on a valid element.
	 * @return bool
	 */
	function valid();

	/**
	 * Returns the key of the current (key,element) pair under the cursor.
	 * @return K
	 * @throws LogicException Not over an element.
	 */
	function key();

	/**
	 * Returns the element of the current (key,element) pair under the cursor.
	 * @return V
	 * @throws LogicException Not over an element.
	 */
	function current();

	/**
	 * Move the cursor to the next (key,element) pair, if any. If the cursor
	 * is already beyond the last pair, does nothing.
	 * @return void
	 */
	function next();
	
}
