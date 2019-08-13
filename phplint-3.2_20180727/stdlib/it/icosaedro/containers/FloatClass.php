<?php

namespace it\icosaedro\containers;

require_once __DIR__ . "/../../../autoload.php";
/*. require_module 'spl'; require_module 'pcre'; .*/
use InvalidArgumentException;
use CastException;


/*. forward class FloatClass implements Sortable {} .*/

/**
 * @access private
 */
class FloatClassSorter implements GenericSorter/*. <FloatClass> .*/ {
	function compare(/*. FloatClass .*/ $a, /*. FloatClass .*/ $b) {
		return $a->compareTo($b);
	}
}

/**
 * Immutable object that holds a floating-point number.
 * Imposes a total ordering that overrides the comparison made by the IEEE 754
 * and by PHP, assuming NAN greater than any other value (including +INF);
 * NAN, +INF and -INF are each one equal to itself. Then we have:
 * <pre>-INF &lt; -0.0 &lt; 0.0 &lt; +INF &lt; NAN</pre>
 *
 * @author Umberto Salsi <salsi@icosaedro.it>
 * @version $Date: 2018/06/06 09:52:53 $
 */
class FloatClass implements Hashable, Sortable, Printable {
	
	/**
	 * @var float
	 */
	private $value = 0.0;
	
	/**
	 * @var GenericSorter<FloatClass>
	 */
	private static $defaultSorter;
	
	/**
	 * Creates a new object with the given value.
	 * @param float $value
	 */
	public function __construct($value) {
		if( !is_float($value) )
			throw new InvalidArgumentException("value is not float: ".gettype($value));
		$this->value = $value;
	}
	
	/**
	 * Returns the common representation of the float value in ten base.
	 * The string always contains either the decimal point or the exponent part
	 * of the scientific notation, so that the result can be distinguished from
	 * an integer number. The special values "NAN", "INF" and "-INF" may also be
	 * returned.
	 * @return string
	 */
	public function __toString() {
		$f = $this->value;
		
		if( is_nan($f) )
			return "NAN";

		if( is_infinite($f) ){
			if( $f > 0.0 )
				return "INF";
			else
				return "-INF";
		}

		$old_precision = ini_set("serialize_precision", "16");
		$s = serialize($f);
		ini_set("serialize_precision", $old_precision);
		$s = substr($s, 2, strlen($s) - 3);

		if( strstr($s, ".") === FALSE and strstr($s, "e") === FALSE )
			# "float" must have at least either a "." or a "e", otherwise
			# 1.0 becomes "1" which looks like a int rather than "float".
			return "$s.0";
		else
			return $s;
	}
	
	/**
	 * 
	 * @return float
	 */
	public function getValue() {
		return $this->value;
	}
	
	/**
	 * 
	 * @return int
	 */
	public function getHash() {
		return Hash::hashOfString((string)$this->value);
	}

	/*. private .*/ const LT = -1, EQ = 0, GT = 1;


	/**
	 * Compares two floating point numbers. Imposes a total ordering that
	 * overrides the comparison made by the IEEE 754 and by PHP, assuming NAN
	 * greater than any other value (including +INF); NAN, +INF and -INF are
	 * each one equal to itself. Then we have:
	 * <pre>-INF &lt; -0.0 &lt; 0.0 &lt; +INF &lt; NAN</pre>
	 * @param float $a
	 * @param float $b
	 * @return int Negative value, zero or positive value if $a is less, equal
	 * or greater then $b respectively, according to the comparison rules
	 * stated above.
	 */
	static function compare($a, $b)
	{
		/*
			At least under PHP 5.3, these expressions are all TRUE:

				INF === INF
				INF < INF (!)
				NAN < NAN (!)

			while these are FALSE:

				INF == INF (!)
				NAN == NAN (!)
				NAN === NAN

			so we walk over eggs...
		*/
		if( is_nan($a) ){
			if( is_nan($b) ){
				return self::EQ;
			} else {
				return self::GT;
			}
		} else if( is_nan($b) ){
			return self::LT;
		} else if( $a === +INF ){
			if( $b === +INF ){
				return self::EQ;
			} else {
				return self::GT;
			}
		} else if( $a === -INF ){
			if( $b === -INF ){
				return self::EQ;
			} else {
				return self::LT;
			}
		} else {

			/* 
			 * Problem of the zero negative (see https://bugs.php.net/bug.php?id=52355):
			 * $zn = -1.0 * 0.0;
			 * yields -0.0 and gets printed as "-0" if converted to string,
			 * but $zn === 0.0 is true and $zn < 0.0 is false under PHP.
			 * Workaround (note that "$zn" === "-0"): first check if both
			 * $a and $b are zero, and only then performs the quite expensive
			 * conversions to string:
			 */
			if( $a === 0.0 and $b === 0.0 ){
				if( "$a" === "-0" ){
					if( "$b" === "-0" )
						return self::EQ;
					else
						return self::LT;
				} else {
					if( "$b" === "-0" )
						return self::GT;
					else
						return self::EQ;
				}
			}

			// Normal comparison between finite, non zero, numbers:
			if( $a < $b )
				return self::LT;
			else if( $a > $b )
				return self::GT;
			else
				return self::EQ;
		}
	}
	
	
	/**
	 * Returns true if this value is really equal to the other. Note having imposed
	 * a total ordering, also the special value NAN equals itself, differently
	 * from what happend with the normal equality operator `=='.
	 * @param object $other
	 * @return boolean
	 */
	public function equals($other) {
		if( $other === NULL )
			return FALSE;
		if( $this === $other )
			return TRUE;
		if( get_class($other) !== __CLASS__ )
			return FALSE;
		$other2 = cast(__CLASS__, $other);
		return self::compare($this->value, $other2->value) == 0;
	}
	
	
	/**
	 * Compares this float with the other.
	 * @param object $other The other object.
	 * @return int Negative, zero or positive if $this is less, equal or
	 * greater than $other respectively.
	 * @throws CastException If the other object belongs to a different
	 * class and cannot be compared with this.
	 */
	function compareTo($other) {
		if ($other === NULL)
			throw new CastException("NULL");
		if (get_class($other) !== __CLASS__)
			throw new CastException("expected " . __CLASS__ . " but got " . get_class($other));
		$other2 = cast(__CLASS__, $other);
		return self::compare($this->value, $other2->value);
	}
	
	
	/**
	 * Returns a default sorter that sorts numbers in increasing value; NAN is
	 * less than any other value.
	 */
	static function getDefaultSorter() {
		if( self::$defaultSorter === NULL )
			self::$defaultSorter = new FloatClassSorter();
		return self::$defaultSorter;
	}
	

	/**
	 * Parses a floating point number from its hexadecimal representation.
	 * The mantissa is expressend with hexadecimal digits with a leading "0x"
	 * and possibly a fractional part. An exponent of 2 part may also be present.
	 * Examples:
	 * <blockquote>
	 * <pre>
	 * 0x0   -0x1.23ab   0xabc.dep-5
	 * </pre>
	 * </blockquote>
	 * Parsing is made case-insensitive, so "p" and "P" or "INF" and "inf" are
	 * the same.
	 * Note that the integral part always contains at least one digit.
	 * Note that the fractional part, if present, must contain at least one digit.
	 * Note that the power part gives the power of 2, NOT the power of 16.
	 * @param string $s The floating point number is hexadecimal representation.
	 * @return float
	 * @throws InvalidArgumentException The string does not contain a valid
	 * hexadecimal representation of a floating point number.
	 */
	private static function parseHex($s) {
		static $REGEX = "";
		if( $REGEX === "" ){
			$HEX = "[0-9A-F]+";
			$REGEX = "/^[-+]?0X($HEX)(\\.$HEX)?(P[-+]?$HEX)?\$/sD";
		}
		$s = strtoupper($s);
//		if( $s === "NAN" )
//			return NAN;
//		if( $s === "INF" || $s === "+INF" )
//			return INF;
//		if( $s === "-INF" )
//			return -INF;
		if( preg_match_all($REGEX, $s, $matches) !== 1 )
			throw new InvalidArgumentException("not a hex float: $s");
		$f = 0.0;
		$decimals = $matches[2][0];
		if( strlen($decimals) > 0 ){
			for($i = strlen($decimals)-1; $i >= 1; $i--){
				$f = 0.0625*$f + hexdec($decimals[$i]);
			}
		}
		$integral = $matches[1][0];
		$e = 0;
		for($i = strlen($integral)-1; $i >= 0; $i--){
			$f = hexdec($integral[$i]) + 0.0625*$f;
		}
		$exponent = $matches[3][0];
		if( strlen($exponent) > 0 ){
			if( strlen($exponent) > 5 )
				throw new InvalidArgumentException("hex exponent overflow: $s");
			if( $exponent[1] === "+" )
				$e = (int) substr($exponent, 2);
			else if( $exponent[1] === "-" )
				$e = - (int) substr($exponent, 2);
			else
				$e = (int) substr($exponent, 1);
			while( $e >= 4 ){
				$f *= 16.0;
				$e -= 4;
			}
			while( $e > 0 ){
				$f *= 2.0;
				$e--;
			}
			while( $e <= -4 ){
				$f *= 0.0625;
				$e += 4;
			}
			while( $e < 0 ){
				$f *= 0.5;
				$e++;
			}
		}
		if( $s[0] === "-" )
			// Beware: -$f works under PHP7.1 does not work on 0.0 under PHP5.6;
			// -1.0 * $f works on both:
			return -1.0 * $f;
		else
			return $f;
	}


	/**
	 * Parse the given string as floating point number. The decimal and hexadecimal
	 * notations as described below are allowed:
	 * <ul>
	 * <li>
	 * <b>Decimal notation:</b> optional sign +/- followed by at least one digit
	 * possibly followed by a decimal part and exponent of ten. Examples:
	 * <tt>1 -1.2 123.456e-5</tt>
	 * </li>
	 * <li>
	 * <b>Exadecimal notation:</b> must start with "0x" and at least one hexadecimal
	 * digit must follow, possibly followed by a fractional part and the exponent
	 * of 2 (NOT the exponent of 16). Examples:
	 * <tt>-0x56.789ab 0xf.fffp12</tt>
	 * </li>
	 * <li>
	 * <b>Special values:</b> NAN, INF, -INF and the negative zero are recognized.
	 * </li>
	 * </ul>
	 * @param string $s  String that represents a floating point number.
	 * Spaces are not allowed, so apply trim() if required. The given string is
	 * evaluated case-insensitive. Huge numbers may give INF or -INF.
	 * @return float
	 * @throws InvalidArgumentException Invalid syntax..
	 */
	static function parse($s)
	{
		# Avoid regex explosion on untrusted data:
		if( strlen($s) > 999 )
			throw new InvalidArgumentException("invalid float: too long");
		
		$s = strtoupper($s);
		if( $s === "NAN" ) return NAN;
		if( $s === "INF" ) return INF;
		if( $s === "-INF" ) return -INF;
		if(strpos($s, "0X") !== FALSE ){
			return self::parseHex($s);
		} else {
			if( preg_match("/^[-+]?[0-9]+(\\.[0-9]+)?(E[-+]?[0-9]+)?\$/sD", $s) !== 1 )
				throw new InvalidArgumentException("invalid float: $s");
			return (float) $s;
		}
	}
	

	/**
	 * Returns the floating point number in its hexadecimal representation.
	 * The string may start with a minus sign, followed by an integral part
	 * (in hexadecimal base with leading <code>"0x"</code>), possibly followed
	 * by a fractional part with at least one digit, then possibly followed
	 * by a "p" and the power of 2 (in decimal base). So for example:
	 * <pre>
	 * -0x3.ap4
	 * </pre>
	 * is the number -(3+10/16)*2^4 = 58 with -0x3.a being the mantissa and
	 * 4 being the exponent of 2.
	 * Note that the exponent part is the power of 2, NOT the power of 16.
	 * Note that any possible value of a floating point number can be expressed
	 * exactly with this hexadecimal representation.
	 * Either a fractional part or exponent part is always present to differentiate
	 * a the literal float so generated from a hexadecimal integer value.
	 * @param float $f  The floating point number to convert in hexadecimal
	 * representation, possibly NAN, INF or -INF.
	 * @return string The floating point number in hexadecimal representation.
	 */
	public static function formatHex($f) {
		if(is_nan($f) )
			return "NAN";
		if($f === INF)
			return "INF";
		if($f === -INF )
			return "-INF";
		// Get rid of the zero:
		if( $f === 0.0 ){ // true for both 0.0 and -0.0.
			// Detecting zero negative is a bit tricky: in fact -0.0 if
			// parsed as zero negative under PHP7.1, but becomes zero under
			// PHP5.6; the expression -1.0*0.0 evaluates zero neg. with both:
			if( FloatClass::compare($f, -1.0 * 0.0) == 0 )
				return "-0x0p0";
			else
				return "0x0p0";
		}
		// make $f a non-negative number and keep the sign:
		if( $f < 0 ){
			$sign = "-";
			$f = -$f;
		} else {
			$sign = "";
		}
		// Normalize $f to 8.0 <= $f < 16.0:
		$e = 0; // exponent of 2
		while($f < 1.0){
			$e -= 4;
			$f *= 16.0; // left shift 4 bits
		}
		while($f < 8.0){
			$e--;
			$f *= 2.0; // left shift 1 bit
		}
		while($f >= 128.0){
			$e += 4;
			$f *= 0.0625; // right shit 4 bits
		}
		while($f >= 16.0){
			$e++;
			$f *= 0.5; // right shit 1 bit
		}
		// Spit hex digits one by one:
		$m = "";
		while($f > 0.0){
			$digit = (int) $f;
			$m .= dechex($digit);
			$f = ($f - $digit)*16.0;
		}
		if( strlen($m) == 1 ){
			return $sign . "0x$m" . "p$e";
		} else {
			return $sign . "0x" . $m[0] . "." . substr($m, 1) . ($e == 0? "" : "p$e");
		}
	}
	
}
