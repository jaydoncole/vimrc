<?php

/*. require_module 'spl'; .*/

/**
 * U.S. FIPS 140-1 statistical tests for randomness as explained in
 * Handbook of Applied Cryptography, 5.31, 5.32.
 * 
 * Usage: create a sample of 2500 random bytes to test and submit to the
 * allTests() function to get the report.
 * 
 * @author Umberto Salsi <salsi@icosaedro.it>
 * @version $Date: 2018/07/23 12:44:10 $
 */
class RandomnessTests {
	
	/**
	 * Remember here if the low-level routines of this class succeeded evaluating
	 * the sample of random numbers given by the mentioned book. This internal
	 * test is done once for all before evaluating any other sample.
	 * @var boolean
	 */
	private static $internal_check_passed = FALSE;

	/**
	 * Returns the total number of bits set.
	 * @param string $sample Sample of random bits.
	 * @return int Total number of bits set.
	 */
	private static function monobit_test($sample)
	{
		static $ones_per_nibble = [0,1,1,2,1,2,2,3,1,2,2,3,2,3,3,4];
		$ones = 0;
		for($i = strlen($sample) - 1; $i >= 0; $i--){
			$byte = ord($sample[$i]);
			$ones += $ones_per_nibble[$byte >> 4] + $ones_per_nibble[$byte & 15];
		}
		return $ones;
	}


	/**
	 * Performs the poker test.
	 * @param string $sample Sample of random bites32-bits random words.
	 * @param int $m Length in bits of each subsample.
	 * @return float Poker test statistic figure.
	 */
	private static function poker_test($sample, $m)
	{
		// There are 2^m = 1<<m possible m-bits long subsamples:
		$number_of_subsamples = 1 << $m;

		// Reset the table of occurrences of each possible m-bits subsample.
		$n = /*. (int[int]) .*/ array();
		for($i = 0; $i < $number_of_subsamples; $i++)
			$n[] = 0;

		// Build table n[] of occurrences of each subsample. Scans the words of the
		// sample and the bits of each word one by one while building each subsample;
		// when a subsample of m bits is complete, increment its entry n[subsample].
		$subsample = 0; // here build m-bits subsample
		$subsample_len = 0; // current no. of bits in the subsample
		for($i = 0; $i < strlen($sample); $i++){
			$byte = ord($sample[$i]);
			for($j = 0; $j < 8; $j++){
				$bit = $byte & 1;
				$byte = $byte >> 1;
				$subsample = ($subsample << 1) | $bit;
				$subsample_len++;
				if( $subsample_len == $m ){
					$n[$subsample]++;
					$subsample = 0;
					$subsample_len = 0;
				}
			}
		}

		$sum = 0.0;
		for($i = 0; $i < $number_of_subsamples; $i++){
			$sum += $n[$i] * $n[$i];
		}
		$k = (int) (8 * strlen($sample) / $m);
		return $sum * $number_of_subsamples / $k - $k;
	}


	/**
	 * Performs the long run test.
	 * @param string $sample Sample of random bits.
	 * @param int $runs_len Number of entries in the runs array.
	 * @return int[int][int] Runs lengths counts found in the sample.
	 * runs[0] are the gaps (sequences of zeros), runs[1] are the blocks (sequences
	 * of ones). runs[*][i] is the number of consecutive zeros or ones of length
	 * "i"; runs[*][0] always returns zero; the last entry runs[*][runs_len-1]
	 * accounts for runs that are (runs_len-1) or more bits long.
	 */
	private static function runs_test($sample, $runs_len)
	{
		$runs = /*. (int[int][int]) .*/ array();
		for($bit = 0; $bit <= 1; $bit++)
			for($i = 0; $i < $runs_len; $i++)
				$runs[$bit][$i] = 0;

		$curr_run_bit = 0;
		$curr_run_len = 0;
		for($i = 0; $i < strlen($sample); $i++){
			$byte = ord($sample[$i]);
			for($j = 0; $j < 8; $j++){
				$bit = $byte & 1;
				$byte = $byte >> 1;
				if( $curr_run_len == 0 ){
					$curr_run_bit = $bit;
					$curr_run_len = 1;
				} else {
					if( $bit == $curr_run_bit ){
						$curr_run_len++;
					} else {
						if( $curr_run_len < $runs_len )
							$runs[$curr_run_bit][$curr_run_len]++;
						else
							$runs[$curr_run_bit][$runs_len-1]++;
						$curr_run_bit = $bit;
						$curr_run_len = 1;
					}
				}
			}
		}
		if( $curr_run_len > 0 ){
			if( $curr_run_len < $runs_len )
				$runs[$curr_run_bit][$curr_run_len]++;
			else
				$runs[$curr_run_bit][$runs_len-1]++;
		}
		return $runs;
	}


	/**
	 * Performs the FIPS test on the sample proposed in the book, 5.31.
	 * This to check the integrity of this class.
	 * @throws RuntimeException This test routines are broken!
	 */
	private static function FIPS_internal_check()
	{
		if( self::$internal_check_passed )
			return;

		// The book uses this sample of bits that must be repeated four times.
		// Note that the first char of the string here is the LSB.
		// 11100 01100 01000 10100 11101 11100 10010 01001
		$sample = str_repeat("\xc7\x88\x72\x4f\x92", 4);

		$ones = self::monobit_test($sample);
		if( $ones != 76 )
			throw new RuntimeException("(internal check) monobit test: found $ones ones");

		$X3 = self::poker_test($sample, 3);
		if( abs($X3 - 9.6415) > 0.001 )
			throw new RuntimeException("(internal check) poker test: X3=$X3");

		$got_runs = self::runs_test($sample, 6);
		// Example 5.31-iv: here we also add the count for blocks of length 4 (7);
		// extra entry for blocks of length >=5 (zero).
//		$exp_runs = [0, 25+8, 4+20, 5+12, 7, 0];
		$exp_gaps = [0, 8, 20, 12, 0, 0];
		$exp_blocks = [0, 25, 4, 5, 7, 0];
		if( $got_runs[0] !== $exp_gaps ){
			echo "got_gaps = ";  var_dump($got_runs[0]);
			echo "exp_gaps = ";  var_dump($exp_gaps);
			throw new RuntimeException("(internal check) poker test: differences found");
		}
		if( $got_runs[1] !== $exp_blocks ){
			echo "got_blocks = ";  var_dump($got_runs[1]);
			echo "exp_blocks = ";  var_dump($exp_blocks);
			throw new RuntimeException("(internal check) poker test: differences found");
		}

		self::$internal_check_passed = TRUE;
	}
	
	
	/**
	 * Performs the monobit test. Basically, on a given sample of random bits
	 * there should be and equal number of zeros and ones.
	 * @param string $sample Sample of exactly 2500 random bytes (20000 bits).
	 * @return string Empty string on success; report of failed tests on failure.
	 */
	static function monobitTest($sample)
	{
		if( strlen($sample) != 2500 )
			throw new InvalidArgumentException("the sample must be exactly 2500 bytes long");

		// First check if our routines do really work comparing with example 5.31:
		self::FIPS_internal_check();

		// Collect errors here:
		$report = "";

		// Monobit test.
		$ones = self::monobit_test($sample);
		if( !(9654 < $ones && $ones < 10346) ){
			$report .= "Monobit test: found $ones ones, expected [9655,10345]\n";
		}
		return $report;
	}
	
	
	/**
	 * Performs the poker test. Basically, on a sample of random bits, each
	 * sequence of 4 bits 0000, 0001, 0010, ... should appear the same number
	 * of times.
	 * @param string $sample Sample of exactly 2500 random bytes (20000 bits).
	 * @return string Empty string on success; report of failed tests on failure.
	 */
	static function pokerTest($sample)
	{
		if( strlen($sample) != 2500 )
			throw new InvalidArgumentException("the sample must be exactly 2500 bytes long");

		// First check if our routines do really work comparing with example 5.31:
		self::FIPS_internal_check();

		// Collect errors here:
		$report = "";

		// Poker test.
		$X3 = self::poker_test($sample, 4);
		if( !(1.03 < $X3 && $X3 < 57.4) ){
			$report .= "Poker test: got X3=$X3, expected [1.03,57.4]\n";
		}
		
		return $report;
	}
	
	
	/**
	 * Performs the runs test and the long run test. Basically, on a sample of
	 * random bits, each sequence of a given number of equal bits should follow
	 * an expected distribution law, with probability decreasing as the length
	 * of the sequence considered increases.
	 * @param string $sample Sample of exactly 2500 random bytes (20000 bits).
	 * @return string Empty string on success; report of failed tests on failure.
	 */
	static function runsTest($sample)
	{
		if( strlen($sample) != 2500 )
			throw new InvalidArgumentException("the sample must be exactly 2500 bytes long");

		// First check if our routines do really work comparing with example 5.31:
		self::FIPS_internal_check();

		// Collect errors here:
		$report = "";

		// Runs test.
		$got_fips_runs = self::runs_test($sample, 7);
		$exp_fips_runs_min = [0, 2267, 1079, 502, 223, 90, 90]; // 5.32-iii
		$exp_fips_runs_max = [0, 2733, 1421, 748, 402, 223, 223]; // 5.32-iii
		for($bit = 0; $bit <= 1; $bit++){
			for($i = 0; $i < 7; $i++){
				if( !($exp_fips_runs_min[$i] <= $got_fips_runs[$bit][$i]
				&& $got_fips_runs[$bit][$i] <= $exp_fips_runs_max[$i]) ){
					$report .= sprintf("Runs test: run len %d of bits $bit count got %d, exp [%d,%d]\n",
						$i, $got_fips_runs[$bit][$i], $exp_fips_runs_min[$i], $exp_fips_runs_max[$i]);
				}
			}
		}

		// Long run test.
		for($bit = 0; $bit <= 1; $bit++){
			if( $got_fips_runs[0][6] > 0 ){
				$got_fips_long_run = self::runs_test($sample, 35);
				if( $got_fips_long_run[$bit][34] != 0 ){
					$report .= sprintf("Long run test 34-bits or more set to $bit: got %d subsamples, exp zero\n", $got_fips_long_run[$bit][34]);
				}
			}
		}
		
		return $report;
	}


	/**
	 * Performs all the tests: monobit, poker and runs.
	 * @param string $sample Sample of exactly 2500 random bytes (20000 bits).
	 * @return string Empty string on success; report of failed tests on failure.
	 */
	static function allTests($sample)
	{
		return self::monobitTest($sample)
			. self::pokerTest($sample)
			. self::runsTest($sample);
	}

}