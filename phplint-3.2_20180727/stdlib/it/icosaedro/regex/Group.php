<?php

namespace it\icosaedro\regex;

require_once __DIR__ . "/../../../all.php";

/*. require_module 'spl'; .*/

use OutOfRangeException;
use it\icosaedro\containers\Printable;

/*. forward interface Group{} .*/

/**
 * Interface to access a matched group of elements in a regular expression.
 * A group is an element possibly followed by a quantifier, for example
 * <code>(X)*+</code>. Since the element <code>(X)</code> may match several
 * times, this interface allows to access every matched occurrence of the
 * element. The count() method returns the number of times the element
 * matched.
 * For example, the regex "a(b)+c" matches the subject string "abbbczz" generating
 * a matching element "abbbc", containing one group "bbb" that contains 3 matching
 * elements "b".
 * @author Umberto Salsi <salsi@icosaedro.it>
 * @version $Date: 2016/02/22 09:11:25 $
 */
interface Group extends Printable {

	/**
	 * Returns the number of matched elements. This number depends on the
	 * quantifier specified in the regex, and may range from 0 up to the
	 * maximum allowed by the implementation.
	 * @return int Number of matched elements for this group, possibly 0
	 * if the quantifier minimum value is zero.
	 */
	function count();

	/**
	 * Returns the specified matched element.
	 * @param int $i Index of the matched element, the first being the
	 * number 0 and the last being the number (count()-1).
	 * @return Element The element.
	 * @throws OutOfRangeException The index is out the range.
	 */
	function elem($i);

}


