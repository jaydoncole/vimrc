<?php

namespace it\icosaedro\containers;

require_once __DIR__ . "/../../../all.php";

/*. require_module 'spl';  require_module 'array'; .*/

use Countable;
use Iterator;
use OutOfRangeException;
use RuntimeException;
use it\icosaedro\containers\Comparable;
use it\icosaedro\containers\Printable;
use it\icosaedro\utils\TestUnit;

/**
 * Holds an indexed array of values.
 * @author Umberto Salsi <salsi@icosaedro.it>
 * @version $Date: 2016/02/24 19:50:34 $
 */
class GenericArray/*.<E extends Comparable>.*/
implements Countable, Iterator, Comparable, Printable {
	
	private $iterator_index = 0;
	
	/** @var E[int] */
	private $elements = array();
	
	/**
	 * Returns the number of elements in the array.
	 */
	public function count() {
		return count($this->elements);
	}
	
	/**
	 * Add element to the end of the array.
	 * @param E $e
	 */
	public function append($e) {
		$this->elements[] = $e;
	}
	
	/**
	 * Insert element at the specified offset.
	 * @param int $i Offset in the range from 0 up to {@link self::count()}.
	 * A zero value adds the element as the first of the array.
	 * The value {@link self::count()} adds the element to the end of the array
	 * with the same effect of {@link self::append()}.
	 * @param E $e
	 * @throws OutOfRangeException
	 */
	public function insert($i, $e) {
		if( !(0 <= $i && $i <= count($this->elements)))
			throw new OutOfRangeException("i=$i");
		array_splice($this->elements, $i, 0, array($e));
	}
	
	/**
	 * Removed the element at the given offset.
	 * @param int $i Offset in the range from 0 up to {@link self::count()}-1.
	 */
	public function remove($i) {
		array_splice($this->elements, $i, 1);
	}
	
	/**
	 * Returns the element at the given offset.
	 * @param int $i Offset in the range from 0 up to {@link self::count()}-1.
	 * @return E
	 * @throws OutOfRangeException
	 */
	public function get($i) {
		if( !(0 <= $i && $i < count($this->elements)))
			throw new OutOfRangeException("i=$i");
		return $this->elements[$i];
	}
	
	/**
	 * Replaces an element at the given offset.
	 * @param int $i Offset in the range from 0 up to {@link self::count()}.
	 * @param E $e
	 * @throws OutOfRangeException
	 */
	public function set($i, $e) {
		if( !(0 <= $i && $i < count($this->elements)))
			throw new OutOfRangeException("i=$i");
		$this->elements[$i] = $e;
	}
	
	/**
	 * Returns all the elements ar PHP array, in the order.
	 * @return E[int]
	 */
	public function asArray() {
		return $this->elements;
	}
	
	private /*. void .*/ function sortRecurse(
		/*. E[int] .*/ & $a,
		/*. int .*/ $l,
		/*. int .*/ $r,
		/*. GenericSorter<E> .*/ $c)
	{
		if( $r - $l == 1 ){
			if( $c->compare($a[$l], $a[$r]) > 0 ){
				$w = $a[$l]; $a[$l] = $a[$r]; $a[$r] = $w;
			}
			return;
		}
		$i = $l;
		$j = $r;
		$m = $l + (int) (($r - $l)/2);
		$p = $a[$m];
		do {
			while( $c->compare($a[$i], $p) < 0 )  $i++;
			while( $c->compare($p, $a[$j]) < 0 )  $j--;
			if( $i < $j ){
				$w = $a[$i]; $a[$i] = $a[$j]; $a[$j] = $w;
			}
			if( $i <= $j ){
				$i++;
				$j--;
			} else {
				break;
			}
		} while( $i < $j );
		if( $l < $j)
			$this->sortRecurse($a, $l, $j, $c);
		if( $i < $r )
			$this->sortRecurse($a, $i, $r, $c);
	}
	
	/**
	 * Sort the elements using the specified sorting criteria.
	 * @param GenericSorter<E> $sorter
	 */
	public function sort($sorter) {
		$n = count($this->elements);
		if( $n < 2 )
			return;
		$this->sortRecurse($this->elements, 0, $n-1, $sorter);
	}
	
	/**
	 * Returns true if the other object is instance of this same classe and
	 * contains the same elements in the same order.
	 * @param object $other
	 */
	function equals($other) {
		if( $other === NULL )
			return FALSE;
		if( $this === $other )
			return TRUE;
		if( get_class($other) !== __CLASS__ )
			return FALSE;
		$other2 = cast(self::class, $other);
		if( $other2->count() != $this->count() )
			return FALSE;
		for($i = $this->count()-1; $i >= 0; $i--)
			if( ! $this->elements[$i]->equals($other2->get($i)) )
				return FALSE;
		return TRUE;
	}
	
	function rewind() {
		$this->iterator_index = 0;
	}
	
	function valid() {
		return 0 <= $this->iterator_index && $this->iterator_index < count($this->elements);
	}
	
	public function current() {
		if( ! $this->valid() )
			throw new RuntimeException("no current element available");
		return $this->elements[$this->iterator_index];
	}
	
	function key() {
		if( ! $this->valid() )
			throw new RuntimeException("no current element available");
		return $this->iterator_index;
	}
	
	function next() {
		if( $this->iterator_index < count($this->elements) )
			$this->iterator_index++;
	}
	
	function __toString() {
		return __CLASS__."[" . TestUnit::dump($this->elements) ."]";
	}
	
}
