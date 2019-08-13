<?php

/**
 * This program performs FIPS 140-1 randomness tests on several commonly
 * available PHP built-in sources of random numbers:
 * 
 * - A bare text string.
 * 
 * - The mt_rand() function.
 * 
 * - The openssl_random_pseudo_bytes() function.
 * 
 * - The random_bytes() function.
 * 
 * Results are displayed on the screen.
 * These tests are not related to the PHPLint stdlib, which already provides
 * its own Random and SecureRandom classes, but it is a survey and comparison of
 * the available tools.
 */

require_once __DIR__ . "/RandomnessTests.php";

function withBareText()
{
	echo "\nRandomness test with bare text:\n";
	// Bare text with "oooo" trail added to pass at least the monobit test:
	$sample = "The quick brown fox jumps over a lazy dog. oooo";
	$sample = str_repeat($sample, (int) (2500 / strlen($sample)) + 1);
	$sample = substr($sample, 0, 2500);
	echo RandomnessTests::allTests($sample);
}

function withMersenne()
{
	echo "\nRandomness test with built-in mt_rand() function:\n";
	// Check if we can get at least 2 bytes per run:
	assert(mt_getrandmax() >= 0xffff);
	$sample = "";
	while(strlen($sample) < 2500){
		$sample .= pack("S", mt_rand());
	}
	$sample = substr($sample, 0, 2500);
	echo RandomnessTests::allTests($sample);
}

function withOpenSSL()
{
	echo "\nRandomness test with buit-in openssl_random_pseudo_bytes() function:\n";
	$strong = FALSE;
	$sample = openssl_random_pseudo_bytes(2500, $strong);
	if( ! $strong )
		echo "Warning: strong cryptography not available.\n";
	echo RandomnessTests::allTests($sample);
}

function withRandomBytes()
{
	echo "\nRandomness test with buit-in random_bytes() function:\n";
	$sample = random_bytes(2500);
	echo RandomnessTests::allTests($sample);
}

function main()
{
	withBareText();
	withMersenne();
	withOpenSSL();
	withRandomBytes();
}

//main();
