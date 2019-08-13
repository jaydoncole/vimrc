<?php

namespace it\icosaedro\containers;

require_once __DIR__ . "/../../../all.php";

use RuntimeException;
use it\icosaedro\containers\Hashable;

/**
	Hashing functions. For every type T on which one may want to calculate an
	hash, a function named hashOfT() is provided. Classes that implement the
	{@link it\icosaedro\containers\Hashable} interface may then easily implement
	the getHash() method combining the hash of each property:
	
	<pre>
	class MyClass implements Hashable {

		private $hash = 0;

		...

		function getHash()
		{
			if( $this-&gt;hash != 0 )
				return $this-&gt;hash;
			$hash = 0;
			$hash = Hash::combine($hash, Hash::hashOfInt($this-&gt;int_property));
			$hash = Hash::combine($hash, Hash::hashOfString($this-&gt;string_property));
			$hash = Hash::combine($hash, $this-&gt;hashable_object_propery-&gt;getHash());
			$this-&gt;hash = $hash;
			return $hash;
		}
	}
	</pre>
	
	Note that the same properties should appear in the implementation of the
	equals() method.
	@author Umberto Salsi <salsi@icosaedro.it>
	@version $Date: 2015/10/22 04:23:04 $
*/
class Hash {

	/**
		Hash of a boolean value.
		@param bool $b The value.
		@return int Hash of the value. Implementation note: merely returns 1 if
		true, 0 if false, so you may want to save time simply avoiding to call
		this function at all :-)
	*/
	static function hashOfBoolean($b)
	{
		return $b? 1 : 0;
	}


	/**
		Hash of an integer value.
		@param int $i The value.
		@return int Hash of the value. Implementation note: merely returns the
		value itself, so you may want to save time simply avoiding to call this
		function at all :-)
	*/
	static function hashOfInt($i)
	{
		return $i;
	}


	/**
		Hash of a string value.
		@param string $s The value.
		@return int Hash of the value, or zero if NULL. Implementation note: if the
		string is not NULL, returns the CRC32 of its bytes.
	*/
	static function hashOfString($s)
	{
		return crc32($s);
	}


	/**
		Returns an hash of an object. To be used for objects that do not
		already implement the {@link it\icosaedro\containers\Hashable} interface.
		@param object $obj The value.
		@return int If NULL, otherwise returns the CRC32 of the has given by the
		{@link spl_object_hash()} function.
	*/
	static function hashOfObject($obj)
	{
		if( $obj === NULL )
			return 0;
		else
			return crc32( spl_object_hash($obj) );
	}


	/**
		Returns the hash of the expression passed, typically a key. The value
		is calculated applying the methods hashOfT() of this class according to
		the actual type of the value.
		@param mixed $x Any value of type null, boolean, int, string or object.
		Types float, array and resource are not supported and give exception.
		@return int The hash of the value.
		@throws RuntimeException Type not supported float, array or resource.
	*/
	static function hashOf($x)
	{
		if( is_null($x) )
			return 0;
		else if( is_object($x) ){
			if( $x instanceof Hashable ){
				$h = cast("it\\icosaedro\\containers\\Hashable", $x);
				return $h->getHash();
			} else {
				return self::hashOfObject( cast("object", $x) );
			}
		} else if( is_bool($x) )
			return self::hashOfBoolean( (boolean) $x );
		else if( is_int($x) )
			return self::hashOfInt( (int) $x );
		else if( is_string($x) )
			return self::hashOfString( (string) $x );
		else if( is_float($x) )
			throw new RuntimeException("FIXME: unsupported hash for float");
		else if( is_array($x) )
			throw new RuntimeException("FIXME: unsupported hash for array");
		else if( is_resource($x) )
			throw new RuntimeException("FIXME: unsupported hash for resource");
		else
			throw new RuntimeException("cannot hash unexpected type: "
				. gettype($x));
	}
	
	
	/**
	 * Combines two hash codes into one.
	 * @param int $a First hash.
	 * @param int $b Second hash.
	 * @return int Hash code resulting combining the two hash codes.
	 */
	public static function combine($a, $b)
	{
//		return (31 * $a + $b) & PHP_INT_MAX;
//		return ($a + $b) & PHP_INT_MAX;
//		return (($a << 5) - $a) ^ $b;
		return $a ^ ($a << 5) ^ $b;
	}
	
	
	/**
	 * Returns an index out of an hash value.
	 * @param int $hash Hash value.
	 * @param int $n Expected range of the index from 0 to $n-1.
	 * @return int Index in the range from 0 to $n-1.
	 */
	public static function getIndex($hash, $n)
	{
		$i = $hash % $n;
		if( $i < 0 )
			$i = -$i;
		return $i;
	}
	
}
