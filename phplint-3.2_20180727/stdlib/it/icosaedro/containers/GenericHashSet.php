<?php

/*. require_module 'spl'; .*/

namespace it\icosaedro\containers;

require_once __DIR__ . "/../../../all.php";

use Iterator;

/**
 * Holds a set of elements that can be quickly retrieved through their
 * hash code.
 * 
 * @author Umberto Salsi <salsi@icosaedro.it>
 * @version $Date: 2018/04/12 08:49:44 $
 */
class GenericHashSet/*.<E extends Hashable>.*/
implements \Countable, Iterator, Printable, Comparable {

	/* Default initial length of the slots table. */
	/*. private .*/ const INITSIZE = 10;

	/*
		Holds one slot for each distinct hash value. First index is the hash
		shared by all the elements contained in the slot. The second index is an
		array of the elements.
	*/
	private /*. E[int][int] .*/ $slots;


	/* Number of elements in the set. */
	private $load = 0;
	

	/* Cursor of the iterator: */

	/* - selected slot: */
	private $iter_slots_index = 0;

	/* - selected element in current slot: */
	private $iter_slot_index = 0;


	/*
		Creates a slots table of the given length.
	*/
	private /*. E[int][int] .*/ function new_array(/*. int .*/ $size)
	{
		$a = /*. (E[int][int]) .*/ array();
		for($i = 0; $i < $size; $i++)
			$a[$i] = NULL;
		return $a;
	}
	
	
	/**
	 * @return void
	 */
	public function __clone()
	{
		$this->iter_slots_index = 0;
		$this->iter_slot_index = 0;
		// FIXME: trim array of slots according to actual size
	}


	/**
	 * Creates a new empty set.
	 * @return void
	 */
	function __construct()
	{
		$this->slots = $this->new_array(self::INITSIZE);
		$this->load = 0;
	}


	/* Calculates the slot index from the hash of the element. */
	private /*. int .*/ function indexOf(/*. Hashable .*/ $elem)
	{
		return ($elem->getHash() & PHP_INT_MAX) % count($this->slots);
	}


	/**
	 * Empty the set.
	 * @return void
	 */
	function clear()
	{
		$this->slots = $this->new_array(self::INITSIZE);
		$this->load = 0;
	}


	/**
	 * Tests if the given element belongs to the set.
	 * @param Hashable $element The element to test.
	 * @return bool True if the element belongs to the set.
	 */
	function contains($element)
	{
		$i = $this->indexOf($element);

		$slot = $this->slots[$i];
		for($i = phplint_count($slot)-1; $i >= 0; $i--)
			if( $slot[$i]->equals($element) )
				return TRUE;
		return FALSE;
	}


	/**
	 * Adds the element to the set. If an equal element is found,
	 * does nothing.
	 * @param E $element The element to add to the set.
	 * @return boolean TRUE if the element was not present in the set;
	 * FALSE if the element is already present in the set.
	 */
	function put($element)
	{
		$i = $this->indexOf($element);
		if( $this->slots[$i] === NULL ){
			$this->slots[$i] = array($element);
			$this->load++;
			return TRUE;
		}

		$slot = $this->slots[$i];

		for($j = phplint_count($slot)-1; $j >= 0; $j--)
			if( $slot[$j]->equals($element) )
				return FALSE;

		# Element not found - add entry:
		$this->slots[$i][] = $element;
		$this->load++;
		
		$size = count($this->slots);
		if( $this->load > $size && $size < PHP_INT_MAX ){
			# Expands slots table to prevent element hash collisions:
			if( $size > (PHP_INT_MAX >> 1) )
				$size = PHP_INT_MAX;
			else
				$size = 2*$size;
			$old = $this->slots;
			$this->slots = $this->new_array($size);
			$this->load = 0;
			for($i = phplint_count($old)-1; $i >= 0; $i--){
				$slot = $old[$i];
				for($j = phplint_count($slot)-1; $j >= 0; $j--){
					$this->put($slot[$j]);
				}
			}
		}
		
		return TRUE;
	}


	/**
	 * Removes an element from the set.
	 * @param E $element The element to remove.
	 * @return void
	 */
	function remove($element)
	{
		$i = $this->indexOf($element);

		$slot = $this->slots[$i];

		$n = phplint_count($slot);
		$j = $n - 1;
		for(; $j >= 0; $j--)
			if( $slot[$j]->equals($element) )
				break;
		if( $j >= 0 ){
			# Element found - remove entry:
			if( $n == 1 ){
				# Slot contains only this element - empty the slot:
				$this->slots[$i] = NULL;
			} else {
				# Slot contains at least another element.
				# Replace removed entry $j with the last one:
				if( $j+1 < phplint_count($slot) ){
					# There is at least one element next to that to remove.
					# Replace this element with the last one of the slot:
					$this->slots[$i][$j] = $this->slots[$i][$n-1];
				}
				# Remove last element:
				unset($this->slots[$i][$n-1]);
			}
			$this->load--;
		}
	}


	/**
	 * Returns the number of elements in the set.
	 * @return int Number of elements in the set.
	 */
	function count()
	{
		return $this->load;
	}


	/**
	 * Returns all the elements as an array.
	 * @return E[int] All the elements.
	 */
	function getElements()
	{
		$elements = /*. (E[int]) .*/ array();
		for($j = count($this->slots)-1; $j >= 0; $j--){
			$slot = $this->slots[$j];
			for($i = phplint_count($slot)-1; $i >= 0; $i--)
				$elements[] = $slot[$i];
		}
		return $elements;
	}


	/*. string .*/ function __toString()
	{
		$e = self::getElements();
		$s = "";
		for($i = 0; $i < phplint_count($e); $i++){
			if( $i > 0 )
				$s .= ", ";
			$s .= \it\icosaedro\utils\TestUnit::dump($e[$i]);
		}
		return __CLASS__ . "($s)";
	}


	/**
	 * Resets the position of the iterator to the first element of the set.
	 * If valid() returns false, the the set is empty.
	 * @return void
	 */
	function rewind()
	{
		$this->iter_slots_index = 0;
		$this->iter_slot_index = -1;
		$this->next();
	}


	/**
	 * Checks if the iterator is on a valid element. If valid, use current()
	 * to retrieve the element.
	 * @return bool True if the iterator is currently on an element of the set,
	 * in which case current() returns that element. Returns false if the set is
	 * empty or the internal cursor was already moved past the last element.
	 */
	function valid()
	{
		if( $this->iter_slots_index >= count($this->slots) )
			return FALSE;
		$slot = $this->slots[ $this->iter_slots_index ];
		if( $this->iter_slot_index >= phplint_count($slot) )
			return FALSE;
		return TRUE;
	}


	/**
	 * Returns the element currently selected by the iterator.
	 * @return E Element currently selected or NULL if no element selected.
	 * @throws \RuntimeException No element selected: missing call to rewind()
	 * or next() or data changed while accessing the iterator, either adding
	 * or removing elements.
	 */
	function current()
	{
		if( ! $this->valid() )
			throw new \RuntimeException("no element selected - missing call to rewind()?");
		return $this->slots[ $this->iter_slots_index ][ $this->iter_slot_index ];
	}


	/**
	 * Returns the element currently selected by the iterator. In
	 * this implementation this method is simply synonym of the {@link
	 * self::current()} and then it may return any type of data. This
	 * violates a bit the contract of the interface as stated in
	 * the PHP manual, which specifies the value returned must be a
	 * "scalar".
	 * @return E Element currently under the cursor.
	 * @throws \RuntimeException No element selected: missing call to rewind()
	 * or next() or data changed while accessing the iterator, either adding
	 * or removing elements.
	 */
	function key()
	{
		return $this->current();
	}


	/**
	 * Moves the iterator to the next element. Does nothing if the set is
	 * empty or the iterator ran past the last element.
	 * @return void
	 */
	function next()
	{
		while( $this->iter_slots_index < count($this->slots) ){
			$this->iter_slot_index++;
			$slot = $this->slots[ $this->iter_slots_index ];
			if( $this->iter_slot_index >= phplint_count($slot) ){
				$this->iter_slots_index++;
				$this->iter_slot_index = -1;
			} else {
				return;
			}
		}
	}

	
	/**
	 * Compare this set with another set for equality.
	 * @param object $other The other set.
	 * @return bool True if $other is not NULL, belongs to this class (possibly
	 * extended) and the two sets contain the same elements according.
	 */
	function equals($other)
	{
		if( ! ($other instanceof self) )
			return FALSE;
		$other2 = cast(__CLASS__, $other);
		if( $other2->count() != $this->count() )
			return FALSE;
		foreach($this as $e){
			if( ! $other2->contains($e) )
				return FALSE;
		}
		return TRUE;
	}


	/**
	 * Adds all the elements of another set to this set.
	 * @param self<? extends E> $s The set to add to this.
	 * @return void
	 */
	function putSet($s)
	{
//		foreach($s->slots as $slot)
//			for($i = phplint_count($slot)-1; $i >= 0; $i--)
//				$this->put($slot[$i]);
		foreach($s as $e)
			$this->put($e);
	}


}
