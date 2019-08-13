<?php

namespace it\icosaedro\utils;

use Exception;
use RuntimeException;

require_once __DIR__ . "/../../../../../stdlib/all.php";

/**
 * Returns a 16-bit int, cryptographically secure, random number.
 * @return int Crypto-secure ramdom number in the range [-2^16, 2^16-1].
 */
function cryptorand()
{
	return cast("int[int]", unpack("s", SecureRandom::randomBytes(2)))[1];
}


/**
 * Very rudimentary randomness test, just to detect if something is evidently
 * wrong: the average of our random numbers must be around zero.
 * @throws RuntimeException
 */
function testRandomBytes()
{
	$histo = new Histogram(-32768, 32768, 16);
	$n = 10000;
	for($i = $n; $i > 0; $i--){
		$x = cryptorand();
		$histo->put($x);
	}
	$average_got = $histo->mean();
	$average_exp = 0.0;
	$dev_got = $histo->deviation();
	$dev_exp = 65536 / sqrt(12);
	// Quite arbitrary 10% matching rule applied:
	if( abs($average_got - $average_exp) > $dev_exp / 10
	|| abs($dev_got - $dev_exp) > $dev_exp / 10 ){
		echo "$histo";
		throw new RuntimeException("average $average_got, deviation $dev_got");
	}
}


/**
 * @throws Exception
 */
function main()
{
	testRandomBytes();
}

main();
