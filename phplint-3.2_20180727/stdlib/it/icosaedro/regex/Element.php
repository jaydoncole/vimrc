<?php

namespace it\icosaedro\regex;

require_once __DIR__ . "/../../../all.php";

use OutOfRangeException;
use it\icosaedro\containers\Printable;

/**
 * Interface to access one matched element of a regular expression.
 * For example, the regex "a(b)+c" matches the subject string "abbbczz" generating
 * a matching element "abbbc", containing one group "bbb" that contains 3 matching
 * elements "b".
 * @author Umberto Salsi <salsi@icosaedro.it>
 * @version $Date: 2016/02/22 09:11:05 $
 */
interface Element extends Printable {

	/**
	 * Returns the starting offset of the element in the subject string.
	 * @return int Starting offset of the element in the subject string.
	 */
	function start();

	/**
	 * Returns the ending offset of the element in the subject string.
	 * @return int Ending offset of the element in the subject string.
	 */
	function end();

	/**
	 * Returns the element as a string of bytes.
	 * @return string The element as a string of bytes. The returned string
	 * is exactly <code>(end()-start())</code> bytes long.
	 */
	function value();

	/**
	 * Returns the number of nested groups. For example, the element
	 * <code>(A)B(C)+Z</code> contains two nested groups: <code>(A)</code>
	 * and <code>(C)+</code>; the first group has index 0, the second group
	 * has index 1. Note that groups are enumerated statically versus the given
	 * regex, and it does not depend on the specific subject string.
	 * @return int Number of nested groups.
	 */
	function count();

	/**
	 * Returns a nested group.
	 * @param int $g Index of the nested group in the range <code>0 &le; $g &lt;
	 * count()</code>. The first group of this element has index 0.
	 * @return Group The requested group. If the specified group.
	 * @throws OutOfRangeException Index out the range.
	 */
	function group($g);
}
