<?php

/*.
	require_module 'core';
	require_module 'spl';
	require_module 'pcre';
.*/

namespace it\icosaedro\bignumbers;

require_once __DIR__ . "/../../../all.php";

use it\icosaedro\containers\Printable;
use it\icosaedro\containers\Sortable;
use it\icosaedro\containers\Hashable;
use it\icosaedro\containers\Hash;
use InvalidArgumentException;

/**
 * Implements integer numbers of arbitrary length.
 * 
 * BigInt are integer numbers with sign and arbitrary length suitable
 * for monetary calculations and other numerical non-intensive tasks. For
 * currencies allowing fractional parts (as USD and EUR) calculations can
 * be done using cents as unit, or even smaller fractions if required for
 * higher precision.
 * 
 * With ordinary values that may be involved in typical business
 * applications, BigInt performs several thousands of operations per second
 * on a common PC. Moreover, the source is short, fast to be parsed and
 * included, simple to use and maintain. If you need a faster implementation
 * of big numbers, look for the BCMath or the GMP extensions of PHP.
 * 
 * A note about the interface provided by this class. Every object of
 * the class holds a big number, so most of the methods have this value as
 * implicit argument, here represented with the word "<code>$this</code>".
 * Methods that require two or more big numbers take <code>$this</code>
 * as the first argument. Once created, an object is never changed, i.e. it
 * is immutable.
 * 
 * Example:
 * 
 * <pre>
 * $n = new BigInt("123456789");
 * echo $n-&gt;pow(3), "\n";          # displays 1881676371789154860897069
 *
 * $price = new BigInt("123456");  # 1,234.56 EUR
 * $VAT = $price-&gt;mul( new BigInt(20) )     # apply VAT 20%
 *            -&gt;add( new BigInt(50) )     # rounding
 *            -&gt;div( new BigInt(100) );   # rescale
 * $total = $price-&gt;add($VAT);
 * echo "You are going to spend ",
 * $total-&gt;format(2);   # displays 1,481.40
 * </pre>
 * 
 * <b>See also:</b> {@link ./BigFloat.html BigFloat} is a class
 * that performs calculations with floating point numbers and arbitrary
 * precision.
 * 
 * @author Umberto Salsi <salsi@icosaedro.it>
 * @copyright 2007 by icosaedro.it di Umberto Salsi
 * @version $Date: 2018/06/06 09:52:53 $
 * @license http://www.icosaedro.it/license/bsd-style.html
 * www.icosaedro.it/license/bsd-style.html BSD-style.
 */
class BigInt implements Printable, Sortable, Hashable
{
	/* Trick to preserve basic version info also if source gets stripped: */
	const VERSION = '$Date: 2018/06/06 09:52:53 $';

	/**
	 * Calculations on small numbers are performed using int.
	 * 
	 * When only small numbers are involved and there is no risk of
	 * overflow, calculations are made using <code>int</code> numbers for
	 * better performances.  It can be set to FALSE for testing purposes
	 * of the class, but should be left to TRUE for common usage.
	 */
	static $optimize = TRUE;

	/**
	 * The sign: +1 for numbers &ge; 0, -1 otherwise.
	 * @var int
	 */
	private $sgn = 0;

	/**
	 * The mantissa: list of decimal digits, most significant is $n[0].
	 * @var string
	 */
	private $m;

	/**
	 * @var int
	 */
	private $hash = 0;


	/**
	 * Returns TRUE if the string represents a valid BigInt.
	 * 
	 * Valid BigInt numbers may have a sign +/- followed by one or more
	 * decimal digits.  Spaces and any other character are not allowed,
	 * so you should apply the {@link trim()} function to user submitted
	 * strings:
	 * <pre>
	 * $n = trim( $_POST['n'] );
	 * if( BigInt::isValid($n) ) ...
	 * </pre>
	 * Examples of valid numbers: <code>"0"  "-1"  "+12345"</code>
	 *
	 * @param string $n  The string to be evaluated as BigInt.
	 * @return bool  TRUE if the string represents a valid BigInt.
	 */
	static function isValid($n)
	{
		return
			is_string($n)
			and strlen($n) <= 10000
			and preg_match("/^[-+]?[0-9]+\$/sD", $n) == 1;
	}


	private function normalize()
	{
		# Remove useless leading zeroes:
		$l = strlen($this->m);
		$i = 0;
		while( $i <= $l-2  and  $this->m[$i] === "0" ){
			$i++;
		}
		if( $i > 0 )
			$this->m = substr($this->m, $i-$l);

		# "-0" becomes "+0":
		if( $this->m === "0"  and  $this->sgn < 0 )
			$this->sgn = +1;
	}

	/**
	 * Builds a BigInt.
	 * 
	 * The arguments can be int, float or string, otherwise an exception
	 * is thrown. <b>int</b> numbers can always be converted exactly
	 * into BigInt numbers.
	 * 
	 * If $x is a <b>string</b>, always use {@link ::isValid()} before
	 * passing arbitrary strings, i.e. user submitted input. Spaces
	 * are not allowed, so use {@link trim()}.
	 * 
	 * If $x is a <b>float</b> it gets truncated, so 1.9 evaluates as 1 and
	 * -1.9 evaluates as -1. INF and NAN yield exception as they cannot
	 * be represented internally. WARNING: avoid to use floating-point
	 * numbers as they may give unexpected results. For example on my PC
	 * the function printf("%.0F", 1e23) prints "99999999999999991611392"
	 * rather than the expected "1" followed by 23 zeroes because that
	 * is the best internal binary representation assigned by the PHP
	 * interpreter when the instruction was parsed; in other words it
	 * is not an issue of printf() nor an issue of the BigInt class
	 * but one of the several limitation of the floating-point numbers.
	 *
	 * @param mixed $x  The value to be converted to BigInt. It may be:
	 *      int, float, string.
	 * @return void
	 * @throws InvalidArgumentException  The argument passed is of the
	 *       wrong type, or it is a non-finite float (NAN, INF or -INF),
	 *       or the string does not represent a valid number.
	 */
	function __construct($x)
	{
		if( is_int($x) ){

			$i = (int) $x;

			if( $i >= 0 ){
				$this->sgn = +1;
				$this->m = (string) $i;
			} else {
				$this->sgn = -1;
				$s = (string) $i;
				$this->m = substr($s, 1);
			}

		} else if( is_string($x) ){

			$s = (string) $x;

			if( ! self::isValid($s) )
				throw new InvalidArgumentException("invalid argument `$s'");

			if( $s[0] === "+" ){
				$this->sgn = +1;
				$this->m = substr($s, 1);
			} else if( $s[0] === "-" ){
				$this->sgn = -1;
				$this->m = substr($s, 1);
			} else {
				$this->sgn = +1;
				$this->m = $s;
			}

			$this->normalize();

		} else if( is_float($x) ){

			$f = (float) $x;

			if( ! is_finite($f) )
				throw new InvalidArgumentException("argument infinite or not-a-number");

			if( $f >= 0.0 )
				$this->sgn = +1;
			else {
				$f = - $f;
				$this->sgn = -1;
			}

			$this->m = sprintf("%.0F", floor($f));

		} else {
			throw new InvalidArgumentException("argument of invalid type "
				. gettype($x));
		}
	}


	/**
	 * Returns the number represented as string.
	 * 
	 * @return string  The string that represents the BigInt:
	 *       a possible "<code>-</code>" sign is followed by one or more digits.
	 */
	function __toString()
	{
		if( $this->sgn < 0 )
			return "-" . $this->m;
		else
			return $this->m;
	}


	/**
	 * Formats the big number. Examples:
	 *
	 * <pre>
	 * $n = new BigInt("1234567890");
	 * echo $n-&gt;format();  # 1,234,567,890
	 * echo $n-&gt;format(2);  # 12,345,678.90
	 * $n = new BigInt("1");
	 * echo $n-&gt;format(2);  # 0.01
	 * </pre>
	 *
	 * @param int $decimals  Number of rightmost digits to be considered
	 * as fractional part. Ignored if not positive.
	 * @param string $dec_sept  Separator string between integral part
	 * and fractional part. Ignored if the number of decimals is not positive.
	 * @param string $thousands_sept  Separator string between thousands.
	 * @return string  The BigInt formatted.
	 */
	function format(
		$decimals = 0,
		$dec_sept = ".",
		$thousands_sept = ",")
	{
		$decimals = (int) max($decimals, 0);
		$l = strlen( $this->m );
		$m = $this->m;
		$sgn = ($this->sgn < 0)? "-" : "";

		$d = $l - $decimals;  # offset of the decimal point

		# Format the integer part:
		if( $d <= 0 ){
			$s = "0";
		} else {
			$s = "";
			$i = $d;
			do {
				$j = $i - 3;
				if( $j < 0 ) $j = 0;
				$s = substr($m, $j, $i - $j)
				. (empty($s)? "" : ($thousands_sept . $s));
				$i = $j;
			} while( $i > 0 );
		}

		# Adds the fractional part:
		if( $decimals > 0 ){
			$s .= $dec_sept;
			if( $d < 0 )
				$s .= str_repeat("0", -$d);
			$s .= substr($m, (int) max($d, 0));
		}

		return $sgn . $s;
	}
	
	
	/**
	 * Parse a string as a BigInt number.
	 * @param string $s String to parse.
	 * @param int $decimals  Number of rightmost digits to be considered
	 * as fractional part. Ignored if not positive.
	 * @param string $dec_sept  Separator string between integral part
	 * and fractional part. Ignored if the number of decimals is not positive.
	 * @param string $thousands_sept  Separator string between thousands.
	 * @return self
	 * @throws InvalidArgumentException Failed to parse.
	 */
	static function parse($s, $decimals = 0, $dec_sept = ".", $thousands_sept = ",")
	{
		if( strlen($thousands_sept) > 0 )
			$s = (string) str_replace($thousands_sept, "", $s);
		$decimals = (int) max($decimals, 0);
		if( $decimals > 0 ){
			$a = explode($dec_sept, $s);
			if( count($a) == 1 ){
				// No decimals.
				$s .= str_repeat("0", $decimals);
			} else if( count($a) == 2 ){
				if( strlen($a[1]) > $decimals )
					throw new InvalidArgumentException("too many decimals: $s");
				$s = $a[0] . $a[1] . str_repeat("0", $decimals - strlen($a[1]));
			} else {
				throw new InvalidArgumentException("cannot parse: $s");
			}
		}
		return new self($s);
	}


	/**
	 * Returns the sign of the number.
	 *
	 * @return int  +1 if the number is positive, -1 if negative, 0 if zero.
	 */
	function sign()
	{
		if( $this->m === "0" )
			return 0;
		else
			return $this->sgn;
	}


	/**
	 * Returns the scale factor.
	 *
	 * @return int  The number of digits minus one. The scale factor
	 *       of 1 is zero, the scale factor of 10 is 1.
	 */
	function scale()
	{
		return strlen($this->m) - 1;
	}


	/**
	 * @param string $a_m
	 * @param string $b_m
	 * @return int
	 */
	static private function cmp_m($a_m, $b_m)
	{
		$a_l = strlen($a_m);
		$b_l = strlen($b_m);
		if( $a_l < $b_l )
			return -1;
		else if( $a_l > $b_l )
			return +1;
		else
			return strcmp($a_m, $b_m);
	}


	/**
	 * Compare $this with $b.
	 * 
	 * @param self $b  The number to be compared.
	 * @return int  Negative if $this is less than $b, positive if
	 *       $this is greater than $b, zero if they are equal.
	 */
	function cmp($b)
	{
		$a = $this;
		if( $a->sgn < 0 ){
			if( $b->sgn < 0 ){
				return -self::cmp_m($a->m, $b->m);
			} else {
				return -1;
			}
		} else {
			if( $b->sgn < 0 ){
				return +1;
			} else {
				return self::cmp_m($a->m, $b->m);
			}
		}
	}


	/**
	 * Implements the Comparable interface.
	 * @param object $other Another BigInt number to compare.
	 * @return int Negative if $this &lt; $other, positive if
	 * $this &gt; $other, zero if they are equal.
	 * @throws \CastException If the object passed is NULL or is not an exact
	 * instance of this class.
	 */
	function compareTo($other)
	{
		if( $other === NULL )
			throw new \CastException("NULL");
		if( get_class($other) !== __CLASS__ )
			throw new \CastException("expected " . __CLASS__ . " but got " . get_class($other));
		$other2 = cast(__CLASS__, $other);
		return $this->cmp($other2);
	}


	/**
	 * Returns the number without the sign.
	 *
	 * @return self  The number without the sign.
	 */
	function abs()
	{
		if( $this->sgn > 0 )
			return $this;

		$n = clone $this;
		$n->sgn = +1;
		return $n;
	}


	/**
	 * Returns the number with the sign reversed.
	 *
	 * @return self  The number with the sign reversed.
	 */
	function minus()
	{
		if( $this->sign() == 0 )
			return $this;

		$n = clone $this;
		$n->sgn = -$n->sgn;
		return $n;
	}


	/**
	 * @param string $a
	 * @param string $b
	 * @return string
	 */
	private static function add2($a, $b)
	{
		$al = strlen($a);
		$bl = strlen($b);
		if( $al < $bl ){
			$a = str_repeat("0", $bl-$al) . $a;
			$l = $bl;
		} else if( $al > $bl ){
			$b = str_repeat("0", $al-$bl) . $b;
			$l = $al;
		} else {
			$l = $al;
		}
		$s = "";
		$carry = 0;
		for( $i = $l-1; $i >= 0; $i-- ){
			$d = (int) $a[$i] + (int) $b[$i] + $carry;
			if( $d <= 9 ){
				$carry = 0;
			} else {
				$carry = 1;
				$d -= 10;
			}
			$s = (string) $d . $s;
		}
		if( $carry > 0 )
			return "1" . $s;
		else
			return $s;
	}


	/**
	 * @param string $a
	 * @param string $b
	 * @return string
	 */
	private static function sub2($a, $b)
	{
		$al = strlen($a);
		$bl = strlen($b);
		if( $al < $bl ){
			$a = str_repeat("0", $bl-$al) . $a;
			$l = $bl;
		} else if( $al > $bl ){
			$b = str_repeat("0", $al-$bl) . $b;
			$l = $al;
		} else {
			$l = $al;
		}
		if( strcmp($a, $b) >= 0 ){
			$sign = "";
		} else {
			$c = $a; $a = $b; $b = $c;
			$sign = "-";
		}
		$s = "";
		$carry = 0;
		for( $i = $l-1; $i >= 0; $i-- ){
			$d = (int) $a[$i] - (int) $b[$i] - $carry;
			if( $d < 0 ){
				$carry = 1;
				$d += 10;
			} else {
				$carry = 0;
			}
			$s = (string) $d . $s;
		}
		return $sign . $s;
	}


	/**
	 * Addition.
	 *
	 * @param self $b  The second term to add.
	 * @return self  The sum $this+$b.
	 */
	function add($b)
	{
		if( self::$optimize ){
			if( strlen($this->m) <= 9  and  strlen($b->m) <= 9 ){
				return new BigInt( (int) $this->__toString()
					+ (int) $b->__toString() );
			}
		}

		$a = $this;
		if( $a->sgn < 0 ){
			if( $b->sgn < 0 ){
				$c = "-" . self::add2($a->m, $b->m);
			} else {
				$c = self::sub2($b->m, $a->m);
			}
		} else {
			if( $b->sgn < 0 ){
				$c = self::sub2($a->m, $b->m);
			} else {
				$c = self::add2($a->m, $b->m);
			}
		}
		return new self($c);
	}


	/**
	 * Subtraction.
	 *
	 * @param self $b  The term to subtract.
	 * @return  self  The difference $this-$b.
	 */
	function sub($b)
	{
		if( self::$optimize ){
			if( strlen($this->m) <= 9  and  strlen($b->m) <= 9 ){
				return new BigInt( (int) $this->__toString()
					- (int) $b->__toString() );
			}
		}

		$a = $this;
		if( $a->sgn < 0 ){
			if( $b->sgn < 0 ){
				$c = self::sub2($b->m, $a->m);
			} else {
				$c = "-" . self::add2($a->m, $b->m);
			}
		} else {
			if( $b->sgn < 0 ){
				$c = self::add2($a->m, $b->m);
			} else {
				$c = self::sub2($a->m, $b->m);
			}
		}
		return new self($c);
	}


	/**
	 * Multiplication.
	 *
	 * @param self $b  The second factor.
	 * @return self  The product $this*$b.
	 */
	function mul($b)
	{
		if( self::$optimize ){
			if( strlen($this->m) <= 4  and  strlen($b->m) <= 4 ){
				$i = (int) $this->__toString();
				$j = (int) $b->__toString();
				return new self( $i * $j );
			}
		}

		$a = $this;
		if( strlen($a->m) >= strlen($b->m) ){
			$a_m = $a->m;
			$b_m = $b->m;
		} else {
			$a_m = $b->m;
			$b_m = $a->m;
		}
		$r = "0";
		$z = "";
		for( $i = strlen($b_m)-1; $i >= 0; $i-- ){
			$bd = (int) $b_m[$i];
			$carry = 0;
			$p = "";
			for( $j = strlen($a_m)-1; $j >= 0; $j-- ){
				$ad = (int) $a_m[$j];
				$pd = $ad * $bd + $carry;
				if( $pd <= 9 ){
					$carry = 0;
				} else {
					$carry = (int) ($pd / 10);
					$pd = $pd % 10;
				}
				$p = (string) $pd . $p;
			}
			if( $carry > 0 )
				$p = (string) $carry . $p;
			$p = $p . $z;
			$z .= "0";
			$r = self::add2($r, $p);
		}
		if( $a->sgn * $b->sgn < 0 )
			$r = "-" . $r;
		return new self($r);
	}


	/**
	 * Calculate quotient and remainder of the division.
	 *
	 * @param self $b  The divisor.
	 * @param self & $rem  Here returns the resulting remainder.
	 * @return self  The quotient, that is a number $q so that
	 *       $this = $q * $b + $rem, being $rem a number of module
	 *       minor than $q.
	 * @throws InvalidArgumentException if $b is zero.
	 */
	function div_rem($b, /*. return .*/ & $rem)
	{
		if( $b->sign() == 0 )
			throw new InvalidArgumentException("division by zero");

		if( self::$optimize ){
			if( strlen($this->m) <= 9  and  strlen($b->m) <= 9 ){
				$i = (int) $this->__toString();
				$j = (int) $b->__toString();
				$rem = new self( $i % $j );
				return new self( (int) ($i / $j) );
			}
		}

		$q_sgn = $this->sgn * $b->sgn;
		$qm = "0";
		$a = $this->abs();
		$b = $b->abs();

		if( $a->cmp($b) < 0 ){
			$rem = $this;
			return new self(0);
		}

		while( TRUE ){
			if( $a->cmp($b) < 0 )
				break;
			$delta = strlen($a->m) - strlen($b->m);
			if( $delta >= 1 ){
				$zeroes = str_repeat("0", $delta);
				$b2 = $b->m . $zeroes;
				if( strcmp($a->m, $b2) >= 0 ){
					$qm = self::add2($qm, "1" . $zeroes);
					$a = $a->sub( new self($b2) );
				} else {
					$zeroes = str_repeat("0", $delta-1);
					$qm = self::add2($qm, "1" . $zeroes);
					$a = $a->sub( new self($b->m . $zeroes) );
				}
			} else {
				$a = $a->sub($b);
				$qm = self::add2($qm, "1");
			}
		}

		if( $this->sgn < 0 )
			$rem = $a->minus();
		else
			$rem = $a;

		if( $q_sgn < 0 )
			$qm = "-" . $qm;

		return new self($qm);
	}


	/**
	 * Calculate the quotient of the division.
	 *
	 * @param self $b  The divisor.
	 * @return self  The quotient.
	 * @throws InvalidArgumentException if $b is zero.
	 */
	function div($b)
	{
		return self::div_rem($b, $ignore_rem);
	}


	/**
	 * Calculate the remainder of the division.
	 * 
	 * @param self $b  The divisor.
	 * @return self  The remainder.
	 * @throws InvalidArgumentException if $b is zero.
	 */
	function rem($b)
	{
		$ignore_div = self::div_rem($b, $rem);
		return $rem;
	}


	/**
	 * Returns the base $this raised to the power $e.
	 * 
	 * @param int $e  The exponent of $this.
	 * @return self  $this raised to the power $e. Please note that
	 *       $e is a simple int number, not a BigInt.
	 */
	function pow($e)
	{
		if( $e == 0 ) return new self(1);
		if( $e <  0 ) return new self(0);
		$base = $this;
		$p = new self(1);
		while( TRUE ){
			if( ($e & 1) == 1 ) $p = $p->mul($base);
			$e >>= 1;
			if( $e == 0 ) break;
			$base = $base->mul($base);
		}
		return $p;
	}


	/**
	 * Scale to a given power of ten.
	 *
	 * @param int $p  The exponent of 10.
	 * @return self  $this * 10^$p.
	 */
	function shift($p)
	{
		if( $p == 0 )
			return $this;

		else if( $p > 0 )
			return new self( $this->__toString() . str_repeat("0", $p) );

		$l = strlen($this->m);
		if( -$p >= $l )
			return new self(0);

		return new self( (($this->sgn < 0)? "-":"") . substr($this->m, 0, $l+$p) );
	}


	/**
	 * Return the BigInt as int.
	 *
	 * PHP provides the constant {@link PHP_INT_MAX} that contains the
	 * maximum positive <b>int</b> number, typically 2^32-1 =
	 * 2147483647. Since the 2-complement representation is used, the
	 * minimum negative number then is -PHP_INT_MAX-1. This function
	 * returns the equivalent <b>int</b> number, or throws an
	 * exception if the BigInt number is too big to be
	 * represented as <b>int</b>.
	 *
	 * @return int  The int that represents $this.
	 * @throws \OutOfRangeException if $this is too big to fit int.
	 */
	function toInt()
	{
		static
			$INT_MIN = /*. (BigInt) .*/ NULL,
			$INT_MAX = /*. (BigInt) .*/ NULL;
		
		if( $INT_MIN === NULL ){
			# Init static vars.
			$im = new BigInt( PHP_INT_MAX );
			$INT_MAX = $im;
			$INT_MIN = $im->add( new BigInt(1) )->minus();
		}
		if( $this->cmp($INT_MIN) < 0  or  $this->cmp($INT_MAX) > 0 )
			throw new \OutOfRangeException("(BigInt)$this does not fit into int");
		return (int) $this->__toString();
	}


	/**
	 * Return the BigInt as float without loss of precision.
	 *
	 * A floating point number can store exactly an integer number
	 * larger than <b>int</b>. On most platforms, <b>int</b> is a
	 * 32-bit, 2-complement value, whereas <b>float</b> is a 53-bit,
	 * signed value in the IEEE 754, double precision, representation,
	 * that can store an integer number of modulus up to 2^53-1 =
	 * 9007199254740991. This function determinates dynamically the
	 * size of the mantissa of the underlying platform, so that an
	 * exception is thrown if the BigInt is too big and
	 * cannot be represented with a <b>float</b> without loss of
	 * precision.
	 *
	 * @return float  The float that represents $this.
	 * @throws \OutOfRangeException if $this is too big to fit float
	 *       without loss of precision.
	 */
	function toFloat()
	{
		static $FLOAT_MAX_INT = /*. (BigInt) .*/ NULL;

		if( $FLOAT_MAX_INT === NULL ){
			# Init static vars.

			# Compute float mantissa size $n:
			$n = 1;
			$eps = 0.5;
			while( 1.0 + $eps > 1.0 ){
				$n++;
				$eps *= 0.5;
			}

			$two = new BigInt(2);
			$FLOAT_MAX_INT = $two
				->pow( $n )
				->sub( new BigInt(1) );
		}
		if( $this->abs()->cmp($FLOAT_MAX_INT) > 0 )
			throw new \OutOfRangeException("(BigInt)$this does not fit into float");
		return (float) $this->__toString();
	}


	/**
	 * @return int
	 */
	function getHash()
	{
		if( $this->hash == 0 )
			$this->hash = Hash::hashOfString($this->m)
				^ Hash::hashOfInt($this->sgn);
		return $this->hash;
	}


	/**
	 * Compares this number with another for equality.
	 * @param object $other The other BigInt.
	 * @return bool True only if the other number is not NULL, is exactly
	 * instance of this class (not an extended one) and carries the same
	 * value.
	 */
	function equals($other)
	{
		if( $other === NULL )
			return FALSE;
		if( get_class($other) !== __CLASS__ )
			return FALSE;
		$other2 = cast("it\\icosaedro\\bignumbers\\BigInt", $other);
		return $this->cmp($other2) == 0;
	}

	
	/**
	 * Parses an integer number in the given base.
	 * @param string $n Number as a string, consisting in a possible leading
	 * sign followed by one or more digits in the given base. "Digits" are the
	 * usual ten digits from "0" up to "9" and the letters from "a" up to "z".
	 * @param int $b Base in the range from 2 up to 36.
	 * @return BigInt
	 * @throws InvalidArgumentException Non a valid number in the given base.
	 * Base out of the allowed range.
	 */
	static function fromBase($n, $b)
	{
		if( strlen($n) == 0 )
			throw new InvalidArgumentException("the empty string is not a number");
		if( preg_match("/^[-+]?[0-9a-zA-Z]+\$/sD", $n) !== 1 )
			throw new InvalidArgumentException("invalid characters in number in base $b");
		if( !(2 <= $b && $b <= 36) )
			throw new InvalidArgumentException("base must be in [2,36], $b given");
		$n = strtolower($n);
		$sign = FALSE;
		if( $n[0] === "+" )
			$n = substr($n, 1);
		else if( $n[0] === "-" ){
			$n = substr($n, 1);
			$sign = TRUE;
		}
		$b_big = new BigInt($b);
		$p = new BigInt(1);
		$res = new BigInt(0);
		for($i = strlen($n)-1; $i >= 0; $i--){
			$c = ord($n[$i]);
			if( ord("0") <= $c && $c <= ord("9") )
				$d = $c - ord("0");
			else
				$d = $c - ord("a") + 10;
			$res = $res->add( $p->mul(new BigInt($d)) );
			if( $i > 0 )
				$p = $p->mul($b_big);
		}
		if( $sign )
			$res = $res->minus();
		return $res;
	}


	/**
	 * Returns this number represented in the given base.
	 * @param int $b Base in the range [2,36].
	 * @return string Possible minus sign followed by digits of the base.
	 * "Digits" are the usual ten digits from "0" up to "9" and the letters
	 * from "a" up to "z".
	 */
	function toBase($b)
	{
		if( !(2 <= $b && $b <= 36) )
			throw new InvalidArgumentException("base must be in [2,36], $b given");
		$sign = $this->sign();
		if( $sign == 0 )
			return "0";
		$n = $this;
		if( $sign < 0 )
			$n = $n->minus();
		$b_big = new BigInt($b);
		$res = "";
		$digits = "0123456789abcdefghijklmnopqrstuvwxyz";
		while($n->sign() != 0){
			$n = $n->div_rem($b_big, $rem);
			$res = $digits[$rem->toInt()] . $res;
		}
		if( $sign < 0 )
			$res = "-$res";
		return $res;
	}

}
