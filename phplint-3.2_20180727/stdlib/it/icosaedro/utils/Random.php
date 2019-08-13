<?php

namespace it\icosaedro\utils;

/*.
	require_module 'core';
	require_module 'spl';
	require_module 'phpinfo';
.*/

use InvalidArgumentException;

/**
 * Pseudo-random number generator suitable non non-cryptographic applications.
 * An object of this class becomes an independent source of random numbers.
 * An handy pre-allocated random generator is also provided for on-spot needs.
 * Methods to retrieve random integers and random strings of bytes are provided.
 * 
 * <h2>Example</h2>
 * Displays 100 random numbers in the range from 1 to 10:
 * <blockquote><pre>
 * $rand = Random::getCommon();
 * for($i = 0; $i &lt; 100; $i++)
 *     echo $rand-&gt;randomInt(1,10), "\n";
 * </pre></blockquote>
 * 
 * <h2>Specifications</h2>
 * This class generates pseudo-random integer numbers using the traditional
 * linear congruential generator known as "ANSI C" or "gcc libc". This algorithm
 * passes several randomness tests, including FIPS 140-1 (see the test code
 * directory).
 * 
 * <h2>Motivations</h2>
 * The built-in mt_rand() function has several flaws and limitations:
 * modulo bias bug before PHP 7.2; only numbers in the range [0,mt_getrandmax()]
 * are truly random, so the whole int range is not properly covered;
 * the mt_getrandmax() is implementation dependent and could vary over time;
 * because of the single shared global seed, resetting the seed with mt_srand()
 * could affect other parts of the application or libraries that generate their
 * own random numbers; missing random floating point number generator;
 * missing random string of bytes generator.
 * <br>The built-in random_bytes() and random_int() claim to be a cryptographically
 * secure source of random bytes, but:
 * they require PHP 7; they depends on several other modules; they behave
 * differently on different OSs with unpredictable results and poorly documented;
 * could be a waste of entropy in some common applications that does not require
 * such a level of security; could be very slow or, if the given amount of entropy
 * is not available, a linear congruential generator is used anyway; there is no
 * guarantee these functions may really return cryptographically secure random
 * bytes anyway.
 * 
 * <h2>References</h2>
 * <ul>
 * <li>Pseudorandom number generator,
 * {@link https://en.wikipedia.org/wiki/Pseudorandom_number_generator}.</li>
 * <li>Linear congruential generator,
 * {@link https://en.wikipedia.org/wiki/Linear_congruential_generator}.</li>
 * </ul>
 * 
 * @author Umberto Salsi <salsi@icosaedro.it>
 * @copyright Copyright 2018 by icosaedro.it di Umberto Salsi
 * @version $Date: 2018/07/27 10:42:26 $
 */
class Random {
	
	/**
	 * Number of random bits per generated word.
	 * @access private
	 */
	const BITS_PER_RANDOM = 15;
	
	/**
	 * Bits mask matching random bits generated. Generated values are in the
	 * range from zero up to this maximum.
	 * @access private
	 */
	const RANDOM_MASK = (1 << self::BITS_PER_RANDOM) - 1;
	
	/**
	 * Seed for the next random number to generate.
	 * @var int
	 */
	private $seed = 0;
	
	/**
	 * Handy globally accessible common instance to a random generator randomly
	 * seeded.
	 * @var self
	 */
	private static $common;
	
	/**
	 * Creates a new random number generator. Without argument, a completely
	 * random sequence will be generated, otherwise the same sequence is generated
	 * for each given seed value.
	 * @param int $seed
	 */
	function __construct($seed = 0)
	{
		if( func_num_args() == 0 ){
			$seed = getmypid();
			$seed = ($seed << 5) ^ $seed ^ crc32( spl_object_hash($this) );
			$seed = ($seed << 5) ^ $seed ^ mt_rand();
			$seed = ($seed << 5) ^ $seed ^ crc32( (string) microtime() );
			if( self::$common !== NULL )
				$seed = ($seed << 5) ^ $seed ^ self::$common->seed;
		}
		$this->seed = $seed & 0xffffffff;
	}
	
	/**
	 * Returns next pseudo-random number based on the current seed.
	 * @return int Random number in the range [0,RANDOM_MASK].
	 */
	private function rand()
	{
		$a = 1103515245;
		$b = $this->seed;
		$c = 12345;
		/*
		 * Now we are going to calculate
		 *     seed = a*b + c
		 * as a 32-bit unsigned expression, ignoring any overflow. Challenging
		 * to do in PHP because we must cope with: 32 and 64 bits systems;
		 * automatic conversion to IEEE 754 floating point numbers with "only"
		 * 53 bits of mantissa, so we can't directly calculate the product.
		 * We then split each number into 16 bits hi and 16 low parts and perform
		 * the product and the sum piece by piece:
		 */
		$a1 = ($a >> 16) & 0xffff;  $a0 = $a & 0xffff;
		$b1 = ($b >> 16) & 0xffff;  $b0 = $b & 0xffff;
		$this->seed = ((($a1*$b0 + $a0*$b1) << 16) + $a0*$b0 + $c) & 0xffffffff;
		return ($this->seed >> 16) & self::RANDOM_MASK;
	}
	
	/**
	 * Returns at least $needed random bits in the lower part of an int.
	 * @param int $needed Min no. of random bits to generate, in the range 1
	 * to 8*PHP_INT_SIZE.
	 * @return int
	 */
	private function randomBits($needed)
	{
		$r = $this->rand();
		$needed -= self::BITS_PER_RANDOM;
		while($needed > 0){
			$r2 = $this->rand();
			$r = ($r << self::BITS_PER_RANDOM) | $r2;
			$needed -= self::BITS_PER_RANDOM;
		}
		return $r;
	}
	
	/**
	 * Returns a random int in the range [0,$max]. Note that only positive numbers
	 * can be generated.
	 * @param int $max Max value to generate.
	 * @return int Random value in the range [0,$max].
	 */
	private function randomPositive($max)
	{
		$max_bits = 8*PHP_INT_SIZE - 1;
		if( $max == PHP_INT_MAX )
			return $this->randomBits($max_bits) & PHP_INT_MAX;
		// Compute bits mask that covers the requested range [0,$max]:
		$mask = self::RANDOM_MASK;
		$mask_len = self::BITS_PER_RANDOM;
		while( $mask < $max ){
			$mask_len += self::BITS_PER_RANDOM;
			if( $mask_len >= $max_bits ){
				$mask_len = $max_bits;
				$mask = PHP_INT_MAX;
				break;
			}
			$mask = ($mask << self::BITS_PER_RANDOM) | self::BITS_PER_RANDOM;
		}
		/* Basically returns the classic
		 *    randomBits($mask_len) % ($max+1)
		 * to fold the random bits to the requested range [0,max], but values
		 * beyond the threshold below must be discarded to prevent modulo bias:
		 *    bias_threshold = mask - (mask + 1) % (max + 1)
		 * Since (mask + 1) above may overflow, it can be replaced by
		 * (mask + 1 - (max + 1)) = (mask - max) to get the same modulo.
		 */
		$bias_threshold = $mask - ($mask - $max) % ($max + 1);
		do {
			$bits = $this->randomBits($mask_len) & $mask;
		} while( $bits > $bias_threshold );
		return $bits % ($max + 1);
	}
	
	/**
	 * Returns a random number in the specified range. The whole int range
	 * [PHP_INT_MIN,PHP_INT_MAX] is supported.
	 * @param int $min Minimum value to generate (included).
	 * @param int $max Maximum value to generate (included).
	 * @return int Random number in the range [$min,$max].
	 */
	function randomInt($min, $max)
	{
		if( $min > $max )
			throw new InvalidArgumentException("min=$min greater than max=$max");
		/*
		 * Basically, if the range is small enough, we can use the simple and
		 * fast randomPositive() function. Otherwise, for very large range we
		 * generate full random int numbers and then select the first that fits
		 * into the range ("brute force" algo); only about 25% of the generated
		 * values are then discarded.
		 */
		if( $min >= 0 ){
			return $min + $this->randomPositive($max - $min);
		} else if( $min == PHP_INT_MIN ){
			if( $max == PHP_INT_MIN )
				return PHP_INT_MIN;
			else if( $max < 0 )
				return PHP_INT_MIN + $this->randomPositive(-($min - $max));
			// else brute force -- see below
		} else if( $max < 0 || $min > (PHP_INT_MIN >> 1) && $max < (PHP_INT_MAX >> 1) ){
			return $min + $this->randomPositive($max - $min);
		}
		// Very large range. Use the brute force algo.
		do {
			$res = self::randomBits(8*PHP_INT_SIZE);
			if( $min <= $res && $res <= $max )
				return $res;
		} while(TRUE);
	}
	
	/**
	 * Returns pseudo-random bytes. For speed optimization reasons, this method
	 * returns different results depending on the word size and endianess of the
	 * processor.
	 * @param int $n Number of bytes requested.
	 * @return string Requested random bytes.
	 */
	function randomBytes($n)
	{
		$bytes = "";
		while(strlen($bytes) < $n)
			$bytes .= pack("i", $this->randomBits(8*PHP_INT_SIZE));
		if( strlen($bytes) > $n )
			$bytes = substr($bytes, 0, $n);
		return $bytes;
	}
	
	/**
	 * Returns a random floating-point number in the range [0.0,1.0[.
	 * @return float Random floating-point number in the range [0.0,1.0[.
	 */
	function randomFloat()
	{
		/*
		 * Generate the 53 bits of a IEEE 754 number by joining two
		 * 26 bits float (leaving out only one bit, but we can live with that).
		 * Note that 2^26 = 67108864, 2^52 = 4503599627370496.
		 */
		$hi = $this->randomBits(26) & 67108863;
		$lo = $this->randomBits(26) & 67108863;
		return
			($hi * 67108864.0 // left shift 26 bits high part
			+ $lo) // add lower 26 bits
			/ 4503599627370496.0; // normalize to [0.0,1.0[ range
	}
	
	/**
	 * Returns the reference to an instance of this class, randomly seeded.
	 * Applications that does not need to repeat a specific sequence, or simply
	 * need a random number "on the spot", may use this handy object.
	 * @return self
	 */
	static function getCommon()
	{
		if( self::$common === NULL )
			self::$common = new self();
		return self::$common;
	}
	
}
