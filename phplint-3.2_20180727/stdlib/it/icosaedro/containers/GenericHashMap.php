<?php

/*. require_module 'spl'; .*/

namespace it\icosaedro\containers;

require_once __DIR__ . "/../../../all.php";

use LogicException;

/**
 * Holds a set of (K=key,E=element) pairs and allows fast retrieval of one element
 * given its key. Keys are univocal in the map, but elements can be duplicated
 * with different keys and may also be NULL. The following example maps dates into
 * strings:
 * <blockquote><pre>
 * use it\icosaedro\containers\GenericHashMap;
 * use it\icosaedro\containers\StringClass;
 * use it\icosaedro\utils\Date;
 * $quotes = new GenericHashMap/&#42;. &lt;Date,StringClass&gt; .&#42;/();
 * $quotes-&gt;put(new Date(2012,  1,  1), new StringClass("year 2012 begins"));
 * $quotes-&gt;put(new Date(2011, 12, 31), new StringClass("year 2011 ends"));
 * $quotes-&gt;put(new Date(2012,  2, 29), new StringClass("leap day of the 2012"));
 * 
 * # Displays quote of the day:
 * $today = Date::today();
 * $quote = $quotes-&gt;get($today);
 * if( $quote !== NULL )
 * 	echo "Quote of the day: $quote\n";
 * 
 * echo "Next notable dates:\n";
 * foreach($quotes as $date =&gt; $quote){
 * 	if( $date-&gt;compareTo($today) &gt; 0 ){
 * 		echo "$date: $quote\n";
 * 	}
 * }
 * </pre></blockquote>
 * 
 * @author Umberto Salsi <salsi@icosaedro.it>
 * @version $Date: 2018/04/12 08:49:44 $
 */
class GenericHashMap /*. <K extends Hashable, V extends Comparable> .*/
implements \Countable, GenericIterator/*.<K,V>.*/, Printable, Comparable {

	/* Default initial length of the slots table. */
	/*. private .*/ const INITSIZE = 10;

	/*
		Holds one slot for each distinct hash value. The index is the hash
		shared by all the keys contained in the slot.
		...
	*/
	private /*. Pair<K,V>[int][int] .*/ $slots;

	/* Number of elements in the slots table. */
	private $load = 0;


	/*
		Creates a slots table of the given length.
	*/
	private /*. Pair<K,V>[int][int] .*/ function new_array(/*. int .*/ $size)
	{
		$a = /*. (Pair<K,V>[int][int]) .*/ array();
		for($i = 0; $i < $size; $i++)
			$a[$i] = NULL;
		return $a;
	}


	/**
	 * Creates a new empty hash map.
	 * @return void
	 */
	function __construct()
	{
		$this->slots = $this->new_array(self::INITSIZE);
		$this->load = 0;
	}


	/**
	 * Empty the map.
	 * @return void
	 */
	function clear()
	{
		$this->slots = $this->new_array(self::INITSIZE);
		$this->load = 0;
	}


	/* Calculates the slot index from the key. */
	private /*. int .*/ function indexOf(/*. Hashable .*/ $key)
	{
		return ($key->getHash() & PHP_INT_MAX) % count($this->slots);
	}


	/**
	 * Tests if a key does exist in the map.
	 * @param Hashable $key The key.
	 * @return bool True if the key belongs to this map.
	 */
	function containsKey($key)
	{
		$i = $this->indexOf($key);
		$slot = $this->slots[$i];
		for($i = phplint_count($slot)-1; $i >= 0; $i--)
			if( $key->equals($slot[$i]->getA()) )
				return TRUE;
		return FALSE;
	}


	/**
	 * Tests if an element does exist in the map. This method performs a
	 * linear search over the whole map, so it is very inefficient.
	 * @param Comparable $element Element we are looking for, possibly NULL.
	 * @return bool True if at least one (key,element) pair has an element
	 * equal to the given value.
	 */
	function containsElement($element)
	{
		for($j = count($this->slots)-1; $j >= 0; $j--){
			$slot = $this->slots[$j];
			for($i = phplint_count($slot)-1; $i >= 0; $i--)
				if( $element === NULL && $slot[$i] === NULL
				|| $element->equals($slot[$i]) )
					return TRUE;
		}
		return FALSE;
	}


	/**
	 * Retrieves an element given its key.
	 * @param Hashable $key Key of the element.
	 * @return V The element, or NULL if not found. Note that the NULL
	 * value may be a valid element, so in this case you may want to check
	 * with {@link self::containsKey($k)} if the key is there.
	 */
	function get($key)
	{
		$i = $this->indexOf($key);
		$slot = $this->slots[$i];
		for($i = phplint_count($slot)-1; $i >= 0; $i--)
			if( $key->equals($slot[$i]->getA()) )
				return $slot[$i]->getB();
		return NULL;
	}


	/**
	 * Inserts a (key,element) pair in the map. If a pair with the same
	 * key already exists, the new element replaces the old one.
	 * @param K $key The key.
	 * @param V $element The element, possibly NULL.
	 * @return void
	 */
	function put($key, $element)
	{
		$i = $this->indexOf($key);
		if( $this->slots[$i] === NULL ){
			$this->slots[$i] = array(new Pair/*.<K,V>.*/($key, $element));
			$this->load++;
			return;
		}

		$slot = $this->slots[$i];

		$j = phplint_count($slot)-1;
		for(; $j >= 0; $j--)
			if( $key->equals($slot[$j]->getA()) )
				break;

		if( $j >= 0 ){
			# Key found - replace element:
			$this->slots[$i][$j] = new Pair/*.<K,V>.*/($key, $element);
		} else {
			# Key not found - add entry:
			$this->slots[$i][] = new Pair/*.<K,V>.*/($key, $element);
			$this->load++;
			
			$size = count($this->slots);
			if( $this->load > $size && $size < PHP_INT_MAX ){
				# Expands array of slots to prevent key hash collisions:
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
						$this->put($slot[$j]->getA(), $slot[$j]->getB());
					}
				}
			}
		}
	}


	/**
	 * Add all the (key,element) pairs of another map to this map.
	 * On key collision, replace the element on this map.
	 * @param GenericHashMap<K,V> $m Map to add to this one.
	 * @return void
	 */
	function putMap($m)
	{
		foreach($m->slots as $slot)
			for($i = phplint_count($slot)-1; $i >= 0; $i--)
				$this->put($slot[$i]->getA(), $slot[$i]->getB());
	}


	/**
	 * Remove a (key,element) pair from the map given its key. Does nothing if
	 * the key does not exist.
	 * @param K $key Key of the pair to remove.
	 * @return void
	 */
	function remove($key)
	{
		$i = $this->indexOf($key);

		$slot = $this->slots[$i];

		$n = phplint_count($slot);
		# Search entry $slot[$j] with the same key:
		$j = $n - 1;
		for(; $j >= 0; $j--)
			if( $key->equals($slot[$j]->getA()) )
				break;
		if( $j >= 0 ){
			# Key found - remove entry:
			if( $n == 1 ){
				# Slot contains only this pair - empty the slot:
				$this->slots[$i] = NULL;
			} else {
				# Slot contains at least another pair.
				# Replace removed entry $j with the last one:
				if( $j+1 < $n ){
					# There is at least one pair next to that to remove.
					# Replace this pair with the last pair of the slot:
					$this->slots[$i][$j] = $this->slots[$i][$n-1];
				}
				# Remove last pair:
				unset($this->slots[$i][$n-1]);
			}
			$this->load--;
		}
	}


	/**
	 * Returns the number of (key,element) pairs in the map.
	 * @return int Number of (key,element) pairs in the map.
	 */
	function count()
	{
		return $this->load;
	}


	/**
	 * Returns all the keys as an array.
	 * @return K[int] All the keys.
	 */
	function getKeys()
	{
		$keys = /*. (K[int]) .*/ array();
		for($j = count($this->slots)-1; $j >= 0; $j--){
			$slot = $this->slots[$j];
			for($i = phplint_count($slot)-1; $i >= 0; $i--)
				$keys[] = $slot[$i]->getA();
		}
		return $keys;
	}


	/**
	 * Returns all the elements as an array.
	 * @return V[int] All the elements. Note that there may be duplicates
	 * of equal elements with different keys.
	 */
	function getElements()
	{
		$elements = /*. (V[int]) .*/ array();
		for($j = count($this->slots)-1; $j >= 0; $j--){
			$slot = $this->slots[$j];
			for($i = phplint_count($slot)-1; $i >= 0; $i--)
				$elements[] = $slot[$i]->getB();
		}
		return $elements;
	}


	/**
	 * Returns all the (key,element) pairs.
	 * @return Pair<K,V>[int] All the (key,element) pairs. The first index is
	 * the pair, the second index evaluates to 0 for the key and 1 for the
	 * element.
	 */
	function getPairs()
	{
		$pairs = /*. (Pair<K,V>[int]) .*/ array();
		for($i = count($this->slots)-1; $i >= 0; $i--){
			$slot = $this->slots[$i];
			for($j = phplint_count($slot)-1; $j >= 0; $j--)
				$pairs[] = $slot[$j];
		}
		return $pairs;
	}


	/*. string .*/ function __toString()
	{
		$pairs = self::getPairs();
		$s = "";
		for($i = 0; $i < phplint_count($pairs); $i++){
			if( $i > 0 )
				$s .= ", ";
			$s .= "(" . \it\icosaedro\utils\TestUnit::dump($pairs[$i]->getA())
				. \it\icosaedro\utils\TestUnit::dump($pairs[$i]->getB()) . ")";
		}
		return __CLASS__ . "($s)";
	}

	
	/**
	 * Compare this map with another map for equality.
	 * @param object $other The other map.
	 * @return bool True if the other map is not NULL, belongs to this same
	 * exact class (not extended) and the two maps contains the same
	 * (key,element) pairs.
	 */
	function equals($other)
	{
		if( $other === NULL or get_class($other) !== __CLASS__ )
			return FALSE;
		try {
			$other2 = cast(__CLASS__, $other);
		}
		catch(\CastException $e){
			return FALSE;
		}
		if( $other2->count() != $this->count() )
			return FALSE;
		for($i = count($this->slots)-1; $i >= 0; $i--){
			$slot = $this->slots[$i];
			for($j = phplint_count($slot)-1; $j >= 0; $j--){
				$pair = $slot[$j];
				$key = $pair->getA();
				$e2 = $other2->get($key);
				if( $e2 === NULL ){
					// Either the value is just NULL or the entry does not exist at all.
					if( $other2->containsKey($key) ){
						if( $pair->getB() !== NULL )
							return FALSE;
					} else {
						return FALSE;
					}
				} else {
					if( ! $e2->equals($pair->getB()) )
						return FALSE;
				}
			}
		}
		return TRUE;
	}


	/* Cursor of the iterator: */

	/* - selected slot: */
	private $iter_slots_index = 0;

	/* - selected key in current slot: */
	private $iter_slot_index = 0;


	/**
	 * Resets the position of the iterator to the first element of the map.
	 * If valid() returns false, the map is empty.
	 * @return void
	 */
	function rewind()
	{
		$this->iter_slots_index = 0;
		$this->iter_slot_index = -1;
		$this->next();
	}


	/**
	 * Checks if the iterator is on a valid (key,element) pair. If valid, use
	 * key() and current() to retrieve the pair.
	 * @return bool True if the iterator is currently on a pair of the map.
	 * Returns false if the map is empty or the internal cursor was already
	 * moved past the last pair.
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
	 * Returns the key of the pair currently selected by the iterator.
	 * @return K Key or the pair currently under the cursor.
	 * @throws LogicException No pair selected: missing call to rewind()
	 * or next() or data changed while accessing the iterator, either adding
	 * or removing elements.
	 */
	function key()
	{
		if( ! $this->valid() )
			throw new LogicException("no element selected - missing call to rewind()?");
		return $this->slots[ $this->iter_slots_index ][ $this->iter_slot_index ]->getA();
	}


	/**
	 * Returns the element currently selected by the iterator.
	 * @return V Element currently selected or NULL if no element
	 * selected.
	 * @throws LogicException No element selected: missing call to rewind()
	 * or next() or data changed while accessing the iterator, either adding
	 * or removing elements.
	 */
	function current()
	{
		if( ! $this->valid() )
			throw new LogicException("no element selected - missing call to rewind()?");
		return $this->slots[ $this->iter_slots_index ][ $this->iter_slot_index ]->getB();
	}


	/**
	 * Moves the iterator to the next pair. Does nothing if the map is
	 * empty or the iterator ran past the last pair.
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

}
