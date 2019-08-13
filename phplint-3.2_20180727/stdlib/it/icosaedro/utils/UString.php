<?php
/*.
	require_module 'core';
	require_module 'spl';
	#require_module 'array';
.*/

namespace it\icosaedro\utils;

require_once __DIR__ . "/../../../all.php";

use CastException;
use OutOfRangeException;
use Serializable;
use it\icosaedro\containers\Hash;
use it\icosaedro\containers\Hashable;
use it\icosaedro\containers\Printable;
use it\icosaedro\containers\UPrintable;
use it\icosaedro\containers\Sortable;
use it\icosaedro\utils\Codepoints;
use it\icosaedro\utils\UTF8;

/*. forward final class UString { public UString function toUString(); } .*/


/**
	Immutable, Unicode strings with encoding conversion utilities.

	An instance of this class holds an immutable string of Unicode codepoints,
	and provides a set of manipulation functions. All the codepoints in the range
	from 0x0 up to 0x10FFFF are allowed, including reserved and undefined codes.
	When a specific encoding is involved, some restriction to this range may apply.
	For example, UCS-2 only supports the Basic Multilingual Plane (BMP) range 0x0
	up to 0xffff; ISO-8859-1 and ASCII are even more limited.

	<p>The client application can build Unicode strings calling the appropriate
	factory method "fromXxx($u)" where Xxx is the encoding of the passed string
	$u. Then, the methods "toXxx()" allow to retrieve the internal string with
	the desired encoding.

	<p>Currently, the internal encoding is UTF-8 to allow for faster conversion
	from and to this encoding, so common in WEB applications. Obviously this
	choice causes some penalty performing heavy low-level string processing,
	but in my experience most WEB applications do not suffer from that, and can
	work effectively anyway.

	<p>Alternative but still compatible implementations might use another
	encoding, for example with wide characters.
	
	<p>Codepoints range from 0 up to 0x10FFFF. The sub-range 0xD800-0xDFFF is
	reserved for the UTF-16 encoding only and should not be used elsewhere;
	this library made the choice to allow this sub-range, with the only exception
	of the UTF-16 encoding, where this restriction applies.

	<p><b>Charset encoding conversions.</b>
	Unicode strings instance of this class are created calling one of the
	fromXxx($bytes) methods. Binary values that are not recognizable as valid
	characters according to the Xxx encoding are silently replaced with the
	standard Unicode Replacement character U+10FFFD. Vice-versa, Unicode strings
	can be converted back to the binary form calling one of the toXxx() methods;
	codepoints that have no a corresponding representation in the target Xxx
	encoding are silently replaced with a question mark "<tt>?</tt>".
	You may use this class to convert strings from one encoding XXX to another
	encoding:
	<pre>
	echo UString::fromXXX("abcdefg")-&gt;toYYY();
	</pre>

	where XXX is the source encoding and YYY is the resulting final encoding.
	Even more text encoding converters are available through other encoding
	classes, the most general one being {@link ../io/texts/EncodingGeneric.html
	EncodingGeneric}.
 
	<p><b>Reading texts from external sources.</b> Two classes
	{@link ../io/Reader.html Reader} and {@link ../io/Writer.html Writer} allows
	to access texts from/to an external source, like a file or network connection,
	and take care to handle BOM, encoding, line ending convention and automatic
	encoding detection.
		
	@author Umberto Salsi <salsi@icosaedro.it>
	@version $Date: 2017/03/16 19:30:19 $
*/
final class UString implements Printable, UPrintable, Sortable, Hashable, Serializable
{
	/**
	 * Unicode replacement character codepoint.
	 * Codepoint: U+FFFD.
	 * HTML entity: <tt>&amp;#xfffd;</tt> or <tt>&amp;65533;</tt>.
	 * Displayed: <tt>&#xfffd;</tt>
	 */
	const REPLACEMENT_CHARACTER_CODEPOINT = UTF8::REPLACEMENT_CHARACTER_CODEPOINT;
	
	/**
	 * Unicode replacement character (UTF-8 encoded bytes).
	 * PHP string: <tt>"\357\277\275" === "\xEF\xBF\xBD"</tt>
	 */
	const REPLACEMENT_CHARACTER_UTF8 = UTF8::REPLACEMENT_CHARACTER_UTF8;
	
	/**
	 * Value that tells if the string passed to the factory method has already
	 * been detected to be ASCII, non-ASCII or still unchecked.
	 * @access private
	 */
	const ASCII_UNCHECKED = 0,
		ASCII_OK = 1,
		ASCII_NO = 2;

	/** Immutable string, UTF-8 encoding. */
	private /*. string .*/ $s;
	
	/** If the string is ASCII. */
	private $is_ascii = FALSE;

	/** Length of the string, in codepoint units (-1 = still unknown). */
	private $s_len = -1;

	private /*. int .*/ $hash = 0;

	/* Cached empty string. */
	private static /*. self .*/ $empty;

	/* Cached single-codepoint strings; index is the codepoint. */
	private static $codepoints_cache = /*. (self[]) .*/ array();


	/**
		Set the internal string. The initial length is left to -1.
		Fast, but for internal use only.
		No checking performed, so the caller must be aware of the
		internal encoding used by this implementation of the class,
		that is UTF-8: that is why the constructor is made
		private and cannot be used in application code.
		@param string $s  The string, UTF-8 encoding, not NULL.
		@param boolean $is_ascii If the string is ASCII.
		@return void
	*/
	private function __construct($s, $is_ascii)
	{
		$this->s = $s;
		$this->s_len = -1;
		$this->is_ascii = $is_ascii;
	}


	/**
		Returns an UString out of a UTF-8 string. Shorter strings (currently
		those of length 0 or 1 codepoints) are cached internally to save memory
		space.
		@param string $s Assumed as UTF-8, possibly NULL.
		@param int $ascii_test One of ASCII_* constants telling if the string
		is ASCII, non-ASCII or still untested.
		@return self Object that wraps this string. If the argument is NULL,
		NULL is returned.
	*/
	private static function factory($s, $ascii_test = self::ASCII_UNCHECKED)
	{
		if( $s === NULL )
			return NULL;

		if( strlen($s) == 0 ){
			if( self::$empty === NULL )
				self::$empty = new self("", TRUE);
			return self::$empty;

		} else if( strlen($s) <= 4 and strlen($s) == UTF8::sequenceLength(ord($s)) ){
			# Single codepoint in string.
			$i = UTF8::codepointAtByteIndex($s, 0);
			if( array_key_exists($i, self::$codepoints_cache) ){
				return self::$codepoints_cache[$i];
			} else {
				$c = new self($s, strlen($s) <= 1);
				$c->s_len = 1;
				self::$codepoints_cache[$i] = $c;
				return $c;
			}

		} else {
			if( $ascii_test == self::ASCII_UNCHECKED )
				$is_ascii = Strings::isASCII($s);
			else
				$is_ascii = $ascii_test == self::ASCII_OK;
			return new self($s, $is_ascii);
		}
	}


	/**
		Maps a codepoint to its internal string representation.
		@param int $code  Codepoint.
		@return string String of bytes that represents the internal
		encoding of the codepoint, in this implementation is UTF-8.
		@throws OutOfRangeException  If the codepoint is invalid.
	*/
	private static function codepointToString($code)
	{
		if( array_key_exists($code, self::$codepoints_cache) ){
			return self::$codepoints_cache[$code]->s;
		}

		return UTF8::chr($code);
	}


	/**
		Return a single codepoint, given its code.
		@param int $code  Codepoint. Note that also
		undefined and forbidden codepoints can be generated.
		@return self  A string containing the codepoint.
		@throws OutOfRangeException  If the codepoint is invalid.
	*/
	static function chr($code)
	{
		if( $code < 0 or $code > UTF8::MAX_CODEPOINT )
			throw new OutOfRangeException("$code");

		if( array_key_exists($code, self::$codepoints_cache) )
			return self::$codepoints_cache[$code];

		$s = self::codepointToString($code);
		$c = new self($s, $code < 128);
		$c->s_len = 1;
		self::$codepoints_cache[$code] = $c;
		return $c;
	}


	/**
		Return the length of the string.
		@return int  Length of the string as number of codepoints.
	*/
	function length()
	{
		if( $this->s_len < 0 )
			$this->s_len = UTF8::length($this->s);
		return $this->s_len;
	}


	// byteIndex() uses these values for faster sequential access.
	private $cached_i = -1;
	private $cached_index = -1;


	/**
		Return the byte index given the UTF-8 sequence index.
		@param int $codepoint_index  Index of the UTF-8 sequence, ranging from
		0 (the first sequence) up to the length in codepoints of the
		string. Note that this last sequence does not exist because
		its byte index is just one byte above the last sequence so the
		index returned points to the byte just next to the end of the
		string.
		@return int  Byte index of the UTF-8 sequence.
		@throws OutOfRangeException  If the parameter is out of the
		range from 0 up to the length in codepoints of the string.
	*/
	private function byteIndex($codepoint_index)
	{
		$s_len = $this->length();
		if( $codepoint_index < 0 || $codepoint_index > $s_len)
			throw new OutOfRangeException("$codepoint_index");
		if( $this->is_ascii )
			return $codepoint_index;
		if( $this->cached_i < 0 or $this->cached_i > $codepoint_index ){
			$j = 0;
			$byte_index = 0;
		} else {
			$j = $this->cached_i;
			$byte_index = $this->cached_index;
		}
		while($j < $codepoint_index){
			$byte_index += UTF8::sequenceLength(ord($this->s[$byte_index]));
			$j++;
		}
		$this->cached_i = $codepoint_index;
		$this->cached_index = $byte_index;
		return $byte_index;
	}


	/**
		Returns the code of the codepoint at the given index.
		@param int $i  Index of the codepoint, in the range from 0
		up to the length of the string minus one. Note that for an
		empty string there is no valid range.
		@return int  Value of the codepoint in the range [0,65535].
		@throws OutOfRangeException  If the index is invalid.
	*/
	function codepointAt($i)
	{
		$s_len = $this->length();
		if( $i < 0 || $i > $s_len)
			throw new OutOfRangeException("$i");
		if( $this->is_ascii )
			return ord($this->s[$i]);
		else
			return UTF8::codepointAtByteIndex($this->s, $this->byteIndex($i));
	}


	/**
	 * Returns the character at the given index.
	 * @param int $i Index of the character in the range from 0 up to
	 * $s-&gt;length()-1.
	 * @return UString The character at the given index.
	 * @throws OutOfRangeException  If the index is invalid.
	 */
	function charAt($i)
	{
		$byte_index = $this->byteIndex($i);
		
		if( $this->is_ascii )
			return self::factory($this->s[$byte_index], self::ASCII_OK);
		
		$seq_len = UTF8::sequenceLength( ord($this->s[$byte_index]) );
		return self::factory(
			Strings::substring($this->s, $byte_index, $byte_index + $seq_len),
			$seq_len == 1? self::ASCII_OK : self::ASCII_NO );
	}


	/**
		Appends another string to this.
		@param self $other  String to append.
		@return self  This string with the other appended.
	*/
	function append($other)
	{
		$res = self::factory( $this->s . $other->s,
			$this->is_ascii && $other->is_ascii? self::ASCII_OK : self::ASCII_NO);
		$res->s_len = $this->length() + $other->length();
		return $res;
	}


	/**
		Returns a substring. You must indicate the range [$a,$b] of
		delimiting tick marks between codepoints, so that ($b-$a) is
		the resulting length of the substring.
		<center>{@img ./UString-substring.gif}</center>
		<pre>
		$s = UString-&gt;fromASCII("ABCDEFG");
		echo $s-&gt;substring(0,4);
		# ==&gt; "ABCD"
		echo $s-&gt;substring(3,3);
		# ==&gt; ""
		</pre>
		Note that the empty range generates the empty string and that
		it must be 0 &le; $a &le; $b &le; length().
		@param int $a  Index of the first codepoint.
		@param int $b  Index of the last codepoint (excluded).
		@return self  The substring, exactly ($b-$a) codepoints long.
		@throws OutOfRangeException Invalid range: must be 0 &le; $a &le; $b &le;
		length of the string.
	*/
	function substring($a, $b)
	{
		if( $a > $b )
			throw new OutOfRangeException("[$a,$b]");
		$index_a = $this->byteIndex($a);
		$index_b = $this->byteIndex($b);
		return self::factory( Strings::substring($this->s, $index_a, $index_b),
			$this->is_ascii? self::ASCII_OK : self::ASCII_UNCHECKED);
	}


	/**
	 * Removes a range of codepoints from this string. For example, if
	 * this string is "ABCD", removing the range (1,3) yields "AD".
	 * @param int $a Beginning of the range.
	 * @param int $b End of the range.
	 * @return self This string with the substring removed. The resulting
	 * string is ($b-$a) codepoints shorter than this. Note that the string
	 * removed is just $this-&gt;substring($a,$b).
	 * @throws OutOfRangeException Invalid range: must be 0 &le; $a &le; $b &le;
	 * length of the string.
	 */
	function remove($a, $b)
	{
		return $this->substring(0, $a)
		->append( $this->substring($b, $this->length()) );
	}


	/**
	 * Inserts a string in this string at a given position.
	 * @param self $s String to insert.
	 * @param int $at Index position.
	 * @return self This string with the given string inserted at the
	 * given position.
	 * @throws OutOfRangeException Invalid range: must be 0 &le; $at &le;
	 * $this-&gt;length().
	 */
	function insert($s, $at)
	{
		return $this->substring(0, $at)
		->append($s)
		->append( $this->substring($at, $this->length()) );
	}


	/**
		Check if this string begins with the given other string.
		@param self $head  The beginning.
		@return bool  True the this string begins with $head.
		The empty string is the beginning of any string.
		Every string starts with itself.
	*/
	function startsWith($head)
	{
		return Strings::startsWith($this->s, $head->s);
	}


	/**
		Check if this string ends with the given other string.
		@param self $tail  The ending.
		@return bool  True the this string ends with $tail.
		The empty string is the end of any string.
		Every string ends with itself.
	*/
	function endsWith($tail)
	{
		return Strings::endsWith($this->s, $tail->s);
	}


	/**
	 * Returns the starting position of the first occurrence of the target
	 * string in this string.
	 * @param self $target Target substring to search. The empty string can
	 * always be found at the very beginning of the search, so $from is
	 * returned.
	 * @param int $from Search target in the range [$from,$this-&gt;length()]
	 * of this string.
	 * @return int Index of the beginning first matching target, or -1 if
	 * not found.
	 * @throws \OutOfRangeException If $from outside [0,$this-&gt;length()].
	 */
	function indexOf($target, $from = 0)
	{
		if( $from < 0 or $from > $this->length() )
			throw new \OutOfRangeException("$from");
		if( $target->length() == 0 )
			return $from;
		$i = strpos($this->s, $target->s, $this->byteIndex($from));
		if( $i === FALSE )
			return -1;
		if( $this->is_ascii )
			return $i;
		else
			return UTF8::codepointIndex($this->s, $i);
	}


	/**
	 * Returns the starting position of the last occurrence of the target
	 * string in this string.
	 * @param self $target Target substring to search. The empty string
	 * can always be found at the beginning of the search, so the length
	 * of $this string is returned.
	 * @param int $from Search target in the range [0,$from] of this string.
	 * @return int Index of the beginning of the first matching target,
	 * or -1 if not found.
	 * @throws \OutOfRangeException If $from outside [0,$this-&gt;length()].
	 */
	function lastIndexOf($target, $from)
	{
		if( $from < 0 or $from > $this->length() )
			throw new \OutOfRangeException("$from");
		if( $target->length() == 0 )
			return $from;
		if( $from == 0 )
			return -1;
		$i = strrpos($this->s, $target->s,
			$this->byteIndex($from) - strlen($this->s) - 1);
		if( $i === FALSE )
			return -1;
		if( $this->is_ascii )
			return $i;
		else
			return UTF8::codepointIndex($this->s, $i);
	}


	/**
	 * Generates a compiled version of a set of codepoints. Compiled strings
	 * are cached for later reuse.
	 * @param self $codes Codepoints to be included in the set.  Ranges of
	 * codepoints can be indicated as "A..B".
	 * @return int[int] Compiled version of the set. The array contains an
	 * even number of paired entries; in each pair, the first number is the
	 * first codepoint of a range, the second number is the last element in
	 * the range. If, for example, the codes are "0..9,." then the result
	 * is array(48, 57, 44, 44, 46, 46).
	 */
	function compileCodepointSet($codes)
	{
		static $cache = /*. (int[string][int]) .*/ array();
		$key = "*" . $codes->s;
		if( array_key_exists($key, $cache) )
			return $cache[$key];

		$set = /*. (int[int]) .*/ array();
		$len = $codes->length();
		$i = 0;
		while( $i < $len ){
			$c = $codes->codepointAt($i);
			$set[] = $c;
			if( $i + 4 <= $len and $codes->codepointAt($i+1) == ord(".")
			and $codes->codepointAt($i+2) == ord(".") ){
				$set[] = $codes->codepointAt($i+3);
				$i += 4;
			} else {
				$set[] = $c;
				$i++;
			}
		}
		$cache[$key] = $set;
		return $set;
	}


	/**
	 * Returns true if the codepoint belongs to the set.
	 * @param int $c Codepoint code.
	 * @param int[int] $set Set of codepoints generated by {@link
	 * self::compileCodepointSet()}.
	 * @return bool True if the codepoint code belongs to the set.
	 */
	function codepointInSet($c, $set)
	{
		for( $i = count($set) - 2; $i >= 0; $i -= 2 ){
			if( $c >= $set[$i] and $c <= $set[$i+1] )
				return TRUE;
		}
		return FALSE;
	}


	/**
	 * Returns a copy of this string with leading and trailing codepoints
	 * specified removed.
	 * @param self $blacklist List of the codepoints to remove. The special
	 * sequence "A..B" specifies the range from "A" to "B".  If NULL or
	 * not specified, the default value includes: whitespace, HT, NL, CR,
	 * NUL, VT.
	 * @return self This string but with all the leading and trailing
	 * codepoints specified removed.
	 */
	function trim($blacklist = NULL)
	{
		if( $blacklist === NULL ){
			$is_ascii = $this->is_ascii? self::ASCII_OK : self::ASCII_NO;
			return self::factory( trim($this->s), $is_ascii );
		}

		if( $blacklist->length() == strlen($blacklist->s) )
			# Only ASCII chars in black list.
			return self::factory( trim($this->s, $blacklist->s) );

		# General algo:
		$set = self::compileCodepointSet($blacklist);
		$len = $this->length();
		$i = 0;
		while( $i < $len and self::codepointInSet($this->codepointAt($i), $set) )
			$i++;
		$j = $len;
		while( $j >= $i + 1 and self::codepointInSet($this->codepointAt($j-1), $set) )
			$j--;
		if( $j - $i == $len )
			return $this;
		else
			return $this->substring($i, $j);
	}


	/**
	 * Replaces any occurrence of the target string with the replacement
	 * string.  Search and replacement is performed scanning this string
	 * from left to right.
	 * @param self $target Any occurrence of this string is replaced.
	 * @param self $replacement Replacement string.
	 * @return self This string but with any occurrence of the target
	 * string replaced.
	 * @throws \InvalidArgumentException If the target is the empty string.
	 */
	function replace($target, $replacement)
	{
		# If target empty, str_replace() simply returns the subject string.
		if( strlen($target->s) == 0 )
			throw new \InvalidArgumentException("empty target");
		return self::factory(
			(string) str_replace($target->s, $replacement->s, $this->s) );
	}


	/**
	 * Compares this string with the other ignoring case.
	 * @param self $other The other string to compare with.
	 * @return bool True if the two strings are equal ignoring
	 * case differences.
	 */
	function equalsIgnoreCase($other)
	{
		$len = $this->length();
		if( $len != $other->length() )
			return FALSE;
		if( $this->s === $other->s )
			return TRUE;
		for( $i = $len - 1; $i >= 0; $i-- ){
			$a = $this->codepointAt($i);
			$b = $other->codepointAt($i);
			if( Codepoints::toFoldCase($a) != Codepoints::toFoldCase($b) )
				return FALSE;
		}
		return TRUE;
	}


	/**
	 * Returns this string in upper-case letters.
	 * @return self This string in upper-case letters.
	 */
	function toUpperCase()
	{
		$u = "";
		$l = $this->length();
		for($i = 0; $i < $l; $i++)
			$u .= self::codepointToString( Codepoints::toUpperCase( $this->codepointAt($i) ) );
		return self::factory($u);
	}


	/**
	 * Returns this string in lower-case letters.
	 * @return self This string in lower-case letters.
	 */
	function toLowerCase()
	{
		$u = "";
		$l = $this->length();
		for($i = 0; $i < $l; $i++)
			$u .= self::codepointToString( Codepoints::toLowerCase( $this->codepointAt($i) ) );
		return self::factory($u);
	}


	/**
	 * Explode this string in pieces.
	 * This string is scanned from left to right.
	 * @param self $separator Any non-empty string that separates pieces.
	 * @return self[int] Pieces of this string that were separated by the
	 * given separator.
	 * @throws \InvalidArgumentException Separator is empty.
	 */
	function explode($separator)
	{
		if( strlen($separator->s) == 0 )
			throw new \InvalidArgumentException("empty separator");
		$a = explode($separator->s, $this->s);
		$res = /*. (self[int]) .*/ array();
		foreach($a as $p)
			$res[] = self::factory($p);
		return $res;
	}


	/**
	 * Implode the array of strings.
	 * @param self[int] $pieces Strings to be joined.
	 * @param self $separator
	 * @return self
	 */
	static function implode($pieces, $separator)
	{
		$res = "";
		$n = 0;
		foreach($pieces as $p){
			if( $n == 0 ){
				$res = $p->s;
			} else {
				$res .= $separator->s;
				$res .= $p->s;
			}
			$n++;
		}
		return self::factory($res);
	}


	/**
		Compare strings. Implements the {@link it\icosaedro\containers\Sortable} interface.
		Strings are compared left to right based on their codepoints.
		@param object $other  The second string.
		@return int  An integer number whose sign depends on the
		alphabetical order of this string compared with the other.
		The comparison is made over the codepoint values.
		@throws CastException If the object passed is not {@link self}.
	*/
	function compareTo($other)
	{
		if( $other === NULL )
			throw new \CastException("NULL");
		$other2 = cast(__CLASS__, $other);
		if( $this === $other2 )
			return 0;
		# strcmp() is implemented with C memcmp() since
		# PHP 5.0 (2004), so it is not locale aware: good.
		return strcmp($this->s, $other2->s);
	}


	/**
		Compare strings, case-insensitive.
		Strings are compared left to right based on their folded codepoints.
		@param UString $other  The second string.
		@return int  An integer number whose sign depends on the
		alphabetical order of this string compared with the other.
		The comparison is made over the folded codepoint values.
	*/
	function compareIgnoreCaseTo($other)
	{
		if( $this === $other )
			return 0;
		$this_len = $this->length();
		$other_len = $other->length();
		$n = $this_len < $other_len? $this_len : $other_len;
		for($i = 0; $i < $n; $i++){
			$a = Codepoints::toFoldCase( $this->codepointAt($i) );
			$b = Codepoints::toFoldCase( $other->codepointAt($i) );
			if( $a != $b )
				return $a - $b;
		}
		return $this_len - $other_len;
	}


	/**
	 * Return an hash value of this string.
	 * @return int Hash value of this string. 
	 */
	function getHash()
	{
		if( $this->hash == 0 )
			$this->hash = Hash::hashOfString($this->s);
		return $this->hash;
	}


	/**
	 * Return a case-insensitive hash value of this string. The value
	 * <i>is not cached</i> and is computed again every time this method is called.
	 * This method is here just as an help to build higher level classes that
	 * handle case-insensitive strings.
	 * @return int Case-insensitive hash value of this string. 
	 */
	function getHashIgnoreCase()
	{
		$hash = 17;
		for($i = $this->length() - 1; $i >= 0; $i--){
			$hash = (31*$hash) ^ Codepoints::toFoldCase( $this->codepointAt($i) );
		}
		return $hash;
	}

	/**
		Return true if the two strings are equal.
		@param object $other  The other string.
		@return bool True if the other string is not NULL, belongs to this same
		class (not extended) and contains the same string of codepoints.
	*/
	function equals($other)
	{
		if( $other === NULL or get_class($other) !== __CLASS__ )
			return FALSE;
		$other2 = cast(__CLASS__, $other);
		return $this->s === $other2->s;
	}


	/**
	 * Factory method that takes an UTF-8 string as input.
	 * @param string $u Array of bytes that represents an UTF-8 well formed
	 * string, possibly NULL.
	 * @return self The resulting Unicode string. Invalid sequences, trunked
	 * sequences and non-minimal sequences are silently replaced with a
	 * question mark "?". If the argument is NULL, NULL is returned.
	 */
	static function fromUTF8($u)
	{
		if( $u === NULL )
			return NULL;
		$u = UTF8::sanitize($u, self::REPLACEMENT_CHARACTER_UTF8);
		return self::factory($u);
	}


	/**
	 * Encodes this string as UTF-8.
	 * @return string This string in UTF-8 encoding.
	 */
	function toUTF8()
	{
		return $this->s;
	}


	/**
		Factory method that takes an ASCII string as input.
		@param string $u  Array of bytes that represents an ASCII string,
		possibly NULL.
		@return self The resulting Unicode string. Invalid non-ASCII bytes are
		silently replaced with a question mark "?". If the argument is NULL,
		NULL is returned.
	*/
	static function fromASCII($u)
	{
		if( $u === NULL )
			return NULL;
		$u = preg_replace("/[\x80-\xff]/", self::REPLACEMENT_CHARACTER_UTF8, $u);
		$us = self::factory($u, self::ASCII_OK);
		# If $us comes from cache, len may or may not be already set, anyway:
		$us->s_len = strlen($u);
		return $us;
	}


	/**
		Encodes the string as ASCII.
		@return string  Array of bytes, ASCII encoding. Non-ASCII
		codes are rendered as question mark "?".
	*/
	function toASCII()
	{
		if( $this->is_ascii )
			return $this->s;
		return preg_replace("/[\xc0-\xff][\x80-\xbf]+/s", "?", $this->s);
	}


	/**
		Factory method that takes an ISO-8859-1 string as input.
		@param string $u  Array of bytes that represents an ISO-8859-1
		string, possibly NULL.
		@return self The resulting Unicode string. If the argument is NULL,
		NULL is returned.
	*/
	static function fromISO88591($u)
	{
		if( $u === NULL )
			return NULL;
		
		if( Strings::isASCII($u) )
			return self::factory($u, self::ASCII_OK);
		
		$u_len = strlen($u);
		$s = "";
		for($i = 0; $i < $u_len; $i++){
			$codepoint = ord($u[$i]);
			if( $codepoint <= 0x7f ){
				# ASCII.
				$s .= chr($codepoint);
			} else if( $codepoint <= 0x9f ){
				# Codes 0x80-0x9f are undefined.
				$s .= self::REPLACEMENT_CHARACTER_UTF8;
			} else {
				$s .= UTF8::chr($codepoint);
			}
		}
		$us = self::factory($s, self::ASCII_NO);
		# If $us from cache, len may or may not be already set, anyway:
		$us->s_len = strlen($u);
		return $us;

		/*
		# Faster, but requires the xml PHP extension for utf8_encoding().
		# Codes 0x80-0x9f are undefined, but utf8_encoding() passes them
		# anyway.
		$us = self::factory( utf8_encode($s) );
		$us->s_len = strlen($u);
		return $us;
		*/
	}


	/**
		Encodes this string as ISO-8859-1.
		@return string  Array of bytes, ISO-8859-1 encoding. Non-ISO-8859-1
		codes are rendered as question mark "?".
	*/
	function toISO88591()
	{
		if( $this->is_ascii )
			return $this->s;
		$len = $this->length();
		$res = "";
		for($i = 0; $i < $len; $i++){
			$codepoint = $this->codepointAt($i);
			if( $codepoint < 0x80 || (0xa0 <= $codepoint && $codepoint <= 0xff) )
				$res .= chr($codepoint);
			else
				$res .= "?";
		}
		return $res;
	}


	/**
		Factory method that takes an UCS2 little endian string as input.
		@param string $u Array of bytes that represents an UCS2 little endian
		string, possibly NULL. The length of the array of bytes should be even,
		otherwise the last odd byte is ignored and a replacement character is
		appended to the resulting string.
		@return self The resulting Unicode string. If the argument is NULL,
		NULL is returned.
	*/
	static function fromUCS2LE($u)
	{
		if( $u === NULL )
			return NULL;
		$s = "";
		$is_ascii = TRUE;
		$u_len = strlen($u);
		for($i = 0; $i < $u_len; $i += 2){
			$codepoint = ord($u[$i]) + (ord($u[$i+1]) << 8);
			$is_ascii = $is_ascii && $codepoint < 128;
			$s .= self::codepointToString($codepoint);
		}
		if( (strlen($u) & 1) != 0 )
			$s .= self::REPLACEMENT_CHARACTER_UTF8;
		$us = self::factory($s, $is_ascii? self::ASCII_OK : self::ASCII_NO);
		$us->s_len = (int) (($u_len + 1) / 2);
		return $us;
	}


	/**
	 * Encodes this string as UCS2 little endian. Codepoints beyond the BMP are
	 * replaced with the replacement character.
	 * @return string  Array of bytes, UCS2 LE encoding.
	 */
	function toUCS2LE()
	{
		$res = "";
		$s_len = $this->length();
		for($i = 0; $i < $s_len; $i++){
			$codepoint = $this->codepointAt($i);
			if( $codepoint > 0xffff )
				$codepoint = self::REPLACEMENT_CHARACTER_CODEPOINT;
			$res .= chr($codepoint & 255) . chr($codepoint >> 8);
		}
		return $res;
	}


	/**
		Factory method that takes an UCS2 big endian string as input.
		@param string $u Array of bytes that represents an UCS2 big endian
		string, possibly NULL. The length of the array of bytes should be even,
		otherwise the last odd byte is ignored and a replacement character is
		appended to the resulting string.
		@return self The resulting Unicode string. If the argument is NULL,
		NULL is returned.
	*/
	static function fromUCS2BE($u)
	{
		if( $u === NULL )
			return NULL;
		$s = "";
		$is_ascii = TRUE;
		$u_len = strlen($u);
		for($i = 0; $i < $u_len; $i += 2){
			$codepoint = (ord($u[$i]) << 8) + ord($u[$i+1]);
			$is_ascii = $is_ascii && $codepoint < 128;
			$s .= self::codepointToString($codepoint);
		}
		if( (strlen($u) & 1) != 0 )
			$s .= self::REPLACEMENT_CHARACTER_UTF8;
		$us = self::factory($s, $is_ascii? self::ASCII_OK : self::ASCII_NO);
		$us->s_len = (int) (($u_len + 1) / 2);
		return $us;
	}


	/**
		Encodes this string as UCS2 big endian. Codepoints beyond the BMP are
		replaced with the replacement character.
		@return string  Array of bytes, UCS2 BE encoding.
	*/
	function toUCS2BE()
	{
		$res = "";
		$s_len = $this->length();
		for($i = 0; $i < $s_len; $i++){
			$codepoint = $this->codepointAt($i);
			if( $codepoint > 0xffff )
				$codepoint = self::REPLACEMENT_CHARACTER_CODEPOINT;
			$res .= chr($codepoint >> 8) . chr($codepoint & 255);
		}
		return $res;
	}


	/**
		Factory method that takes an UTF-16 little endian string as input.
		Trunked strings and invalid surrogates are detected and the replacement
		character is inserted in an attempt to re-syncronize.
		@param string $u Array of bytes that represents an UTF-16 little endian
		string, possibly NULL. The length of the array of bytes should be even.
		@return self The resulting Unicode string. If the argument is NULL,
		NULL is returned.
	*/
	static function fromUTF16LE($u)
	{
		if( $u === NULL )
			return NULL;
		$s = "";
		$is_ascii = TRUE;
		$u_len = strlen($u);
		for($i = 2; $i <= $u_len; $i += 2){
			$codepoint = ord($u[$i-2]) + (ord($u[$i-1]) << 8);
			if( 0xD800 <= $codepoint && $codepoint <= 0xDBFF ){
				// high surrogate:
				$hi = $codepoint;
				$i += 2;
				if( $i > $u_len ){
					// missing low surrogate
					$s .= self::REPLACEMENT_CHARACTER_UTF8;
					break;
				}
				// low surrogate:
				$lo = ord($u[$i-2]) + (ord($u[$i-1]) << 8);
				if( !(0xDC00 <= $lo && $lo <= 0xDFFF) ){
					// not a low surrogate
					$s .= self::REPLACEMENT_CHARACTER_UTF8;
					$i -= 2; // skip HI and try re-sync
					continue;
				}
				// restore original codepoint:
				$codepoint = (($hi - 0xD800) << 10) + ($lo - 0xDC00) + 0x10000;
			} else if( 0xDC00 <= $codepoint && $codepoint <= 0xDFFF ){
				// low surrogate without high surrogate
				$codepoint = self::REPLACEMENT_CHARACTER_CODEPOINT;
			}
			$is_ascii = $is_ascii && $codepoint < 128;
			$s .= self::codepointToString($codepoint);
		}
		if( ($u_len & 1) != 0 )
			$s .= self::REPLACEMENT_CHARACTER_UTF8;
		return self::factory($s, $is_ascii? self::ASCII_OK : self::ASCII_NO);
	}


	/**
	 * Encodes this string as UTF-16 little endian. Forbidden codepoints in the
	 * range U+D800 - U+DBFF (which should never belong to an Unicode string
	 * anyway) are replaced with the replacement character.
	 * @return string  Array of bytes, UTF-16 LE encoding.
	 */
	function toUTF16LE()
	{
		$res = "";
		$s_len = $this->length();
		for($i = 0; $i < $s_len; $i++){
			$codepoint = $this->codepointAt($i);
			if( 0xD800 <= $codepoint && $codepoint <= 0xDFFF ){
				$codepoint = self::REPLACEMENT_CHARACTER_CODEPOINT;
			} else if( $codepoint >= 0x10000 ){
				$x = $codepoint - 0x10000;
				$hi = ($x >> 10) + 0xD800;
				$res .= chr($hi & 255) . chr($hi >> 8);
				$codepoint = ($x & 0x3FF) + 0xDC00;
			}
			$res .= chr($codepoint & 255) . chr($codepoint >> 8);
		}
		return $res;
	}


	/**
		Factory method that takes an UTF-16 big endian string as input.
		Trunked strings and invalid surrogates are detected and the replacement
		character is inserted in an attempt to re-syncronize.
		@param string $u Array of bytes that represents an UTF-16 big endian
		string, possibly NULL. The length of the array of bytes should be even.
		@return self The resulting Unicode string. If the argument is NULL,
		NULL is returned.
	*/
	static function fromUTF16BE($u)
	{
		if( $u === NULL )
			return NULL;
		$s = "";
		$is_ascii = TRUE;
		$u_len = strlen($u);
		for($i = 2; $i <= $u_len; $i += 2){
			$codepoint = (ord($u[$i-2]) << 8) + ord($u[$i-1]);
			if( 0xD800 <= $codepoint && $codepoint <= 0xDBFF ){
				// high surrogate:
				$hi = $codepoint;
				$i += 2;
				if( $i > $u_len ){
					// missing low surrogate
					$s .= self::REPLACEMENT_CHARACTER_UTF8;
					break;
				}
				// low surrogate:
				$lo = (ord($u[$i-2]) << 8) + ord($u[$i-1]);
				if( !(0xDC00 <= $lo && $lo <= 0xDFFF) ){
					// not a low surrogate
					$s .= self::REPLACEMENT_CHARACTER_UTF8;
					$i -= 2; // skip HI and try re-sync
					continue;
				}
				// restore original codepoint:
				$codepoint = (($hi - 0xD800) << 10) + ($lo - 0xDC00) + 0x10000;
			} else if( 0xDC00 <= $codepoint && $codepoint <= 0xDFFF ){
				// low surrogate without high surrogate
				$codepoint = self::REPLACEMENT_CHARACTER_CODEPOINT;
			}
			$is_ascii = $is_ascii && $codepoint < 128;
			$s .= self::codepointToString($codepoint);
		}
		if( ($u_len & 1) != 0 )
			$s .= self::REPLACEMENT_CHARACTER_UTF8;
		return self::factory($s, $is_ascii? self::ASCII_OK : self::ASCII_NO);
	}


	/**
	 * Encodes this string as UTF-16 big endian. Forbidden codepoints in the range
	 * U+D800 - U+DBFF (which should never belong to an Unicode string anyway) are
	 * replaced with the replacement character.
	 * @return string  Array of bytes, UTF-16 BE encoding.
	 */
	function toUTF16BE()
	{
		$res = "";
		$s_len = $this->length();
		for($i = 0; $i < $s_len; $i++){
			$codepoint = $this->codepointAt($i);
			if( 0xD800 <= $codepoint && $codepoint <= 0xDFFF ){
				$codepoint = self::REPLACEMENT_CHARACTER_CODEPOINT;
			} else if( $codepoint >= 0x10000 ){
				$x = $codepoint - 0x10000;
				$hi = ($x >> 10) + 0xD800;
				$res .= chr($hi >> 8) . chr($hi & 255);
				$codepoint = ($x & 0x3FF) + 0xDC00;
			}
			$res .=  chr($codepoint >> 8) . chr($codepoint & 255);
		}
		return $res;
	}


	/**
	 * Returns the internal representation of the string.
	 * @return string Array of bytes encoded as a PHP ASCII string with
	 * double quotes and escape sequences.
	 */
	function __toString()
	{
		return "\"" . addcslashes($this->s, "\000..\037\\\$\"\177..\377") . "\"";
	}


	/**
	 * Returns this string.
	 * @return self
	 */
	function toUString()
	{
		return $this;
	}


	/**
	 * Returns this string as a UTF-8, PHP-compliant literal string in
	 * double-quotes. Useful to display arbitrary strings that may contain
	 * control characters and non-ASCII codes.
	 * All the ASCII control characters 0-31,127 and the <code>$ \ "</code> characters
	 * are converted to the form \xxx, where xxx is the octal code of the
	 * byte, with the exception of the usual control characters as LF, CR etc.
	 * that are rendered as escape sequences "\n", "\r" etc.
	 * Example:<p>
	 * <code>UString::fromUTF8("abce\n")-&gt;toLiteral() ==&gt; "\"abce\\n\""</code>
	 * @return UString
	 */
	function toLiteral()
	{
		return self::fromUTF8("\"" . addcslashes($this->s, "\000..\037\\\$\"\177") . "\"");
	}


	/*. string .*/ function serialize()
	{
		return $this->s;
	}


	/*. void .*/ function unserialize(/*. string .*/ $serialized)
	{
		$u = self::fromUTF8($serialized);
		$this->s = $u->s;
		$this->s_len = $u->s_len;
	}


}
