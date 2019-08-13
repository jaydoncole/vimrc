<?php

require_once __DIR__ . "/../../../../../stdlib/all.php";
require_once __DIR__ . "/RandomnessTests.php";

use it\icosaedro\utils\Random;
use it\icosaedro\utils\Histogram;
use it\icosaedro\utils\TestUnit as TU;

/**
 * If true: displays histograms and some feedback.
 * If false: only exceptions.
 */
const AUTO_TEST = TRUE;

const UNIFORM_AVERAGE_FAIL_THRESHOLD = 0.02;
const UNIFORM_VARIANCE_FAIL_THRESHOLD = 0.05;

/**
 * Test int PRNG uniform distribution.
 * @param int $min
 * @param int $max
 * @param int $number_of_samples
 */
function int_uniform_test($min, $max, $number_of_samples)
{
	if( ! AUTO_TEST )
		echo "Now performing int uniform test...\n";
	$histo = new Histogram($min, $max+1, 10);
	
	// Collect data from our PRNG.
	$prng = Random::getCommon();
	for($i = 0; $i < $number_of_samples; $i++){
		$r = $prng->randomInt($min, $max);
		if( !(is_int($r) && $min <= $r && $r <= $max) )
			throw new RuntimeException("r=$r");
		$histo->put($r);
	}
	
	// Retrieve got statistic:
	$got_average = $histo->mean();
	$got_variance = $histo->deviation() * $histo->deviation();
	if( ! AUTO_TEST )
		echo "Random int generator [$min,$max]:\n$histo";
	
	/*
	 * Expected statistics figures (using Mathematica or Mathics notation):
	 * n = max - min + 1
	 * exp_average = min + Sum[i, {i, 0, n-1}] / n = min + (n-1)/2
	 * exp_variance = Sum[(i - exp_average)^2, {i, 0, n-1}] / n = (n^2 - 1)/12
	 * exp_difference_average = Sum[ Sum[i-j, {j, 0, n-1}], {i, 0, n-1}] / n^2 = 0
	 * exp_difference_variance = Sum[ Sum[(i-j)^2, {j, 0, n-1}], {i, 0, n-1}] / n^2
	 *                         = (n^2 - 1) / 6
	 */
	$n = (double) $max - $min + 1;
	$exp_average = $min + ($n-1) / 2;
	$exp_variance = ($n*$n - 1) / 12;
	
	// Compare expected and got values:
	
	if( abs($got_average - $exp_average) / ($max - $min) > UNIFORM_AVERAGE_FAIL_THRESHOLD )
		throw new RuntimeException("got average $got_average, exp average $exp_average");
	if( abs($got_variance - $exp_variance) / $exp_variance > UNIFORM_VARIANCE_FAIL_THRESHOLD )
		throw new RuntimeException("got variance $got_variance, exp variance $exp_variance");
}

/**
 * Test float PRNG uniform distribution.
 * @param int $number_of_samples
 */
function float_uniform_test($number_of_samples)
{
	if( ! AUTO_TEST )
		echo "Now performing float uniform test...\n";
	$histo = new Histogram(0.0, 1.0, 100);
	
	// Collect data from our PRNG.
	$prng = Random::getCommon();
	for($i = 0; $i < $number_of_samples; $i++){
		$r = $prng->randomFloat();
		if( !(is_float($r) && 0.0 <= $r && $r < 1.0) )
			throw new RuntimeException("r=$r");
		$histo->put($r);
	}
	
	// Retrieve got statistic:
	$got_average = $histo->mean();
	$got_variance = $histo->deviation() * $histo->deviation();
	if( ! AUTO_TEST )
		echo "Random float generator:\n$histo";
	
	// Expected statistics figure:
	$exp_average = 0.5;
	$exp_variance = 1.0 / 12;
	
	// Compare expected and got values:
	
	if( abs($got_average - $exp_average) > UNIFORM_AVERAGE_FAIL_THRESHOLD )
		throw new RuntimeException("got average $got_average, exp average $exp_average");
	
	if( abs($got_variance - $exp_variance) / $exp_variance > UNIFORM_VARIANCE_FAIL_THRESHOLD )
		throw new RuntimeException("got variance $got_variance, exp variance $exp_variance");
}

/**
 * Simple but very effective test proposed by Joshua Bloch, "Effective Java",
 * second edition, p. 215. By generating 1 million of random numbers on a very
 * wide range of the int spectrum, about half of them should fall before the
 * middle. The typical naive implementation rand() % n to get a value in the
 * range [0,n-1] does not work and retrieves something like 666666 rather than
 * the expected 500000.
 */
function modulo_bias_test()
{
	if( ! AUTO_TEST )
		echo "Now performing modulo bias test...\n";
	$n = 100000;
	$max = 2 * (int)(PHP_INT_MAX / 3);
	$middle = $max >> 1;
	$low = 0;
	$prng = Random::getCommon();
	for($i = 0; $i < $n; $i++)
		if( $prng->randomInt(0, $max) < $middle )
			$low++;
	if( abs(($low - $n/2) / $n) > 0.02 )
		throw new RuntimeException("got low $low exp " . (int)($n/2));
}

function testRandomBytes()
{
	$no_bytes = 10000;
	$bytes = Random::getCommon()->randomBytes($no_bytes);
	TU::test(strlen($bytes), $no_bytes);
	$histo = new Histogram(0, 255, 255);
	for($i = 0; $i < $no_bytes; $i++)
		$histo->put(ord($bytes[$i]));
	if( ! AUTO_TEST )
		echo "Test randomBytes():\n$histo";
	
	// Retrieve got statistic:
	$got_average = $histo->mean();
	$got_variance = $histo->deviation() * $histo->deviation();
	
	$min = 0;
	$max = 255;
	$n = (double) $max - $min + 1;
	$exp_average = $min + ($n-1) / 2;
	$exp_variance = ($n*$n - 1) / 12;
	
	// Compare expected and got values:
	
	if( abs($got_average - $exp_average) / ($max - $min) > UNIFORM_AVERAGE_FAIL_THRESHOLD )
		throw new RuntimeException("got average $got_average, exp average $exp_average");
	if( abs($got_variance - $exp_variance) / $exp_variance > UNIFORM_VARIANCE_FAIL_THRESHOLD )
		throw new RuntimeException("got variance $got_variance, exp variance $exp_variance");
	
}


function FIPS_tests()
{
	if( ! AUTO_TEST )
		echo "Now performing FIPS 140-1 tests...\n";
	$sample = Random::getCommon()->randomBytes(2500);
	$report = RandomnessTests::allTests($sample);
	if( strlen($report) > 0 )
		throw new RuntimeException("FIPS tests failed:\n$report");
}


function write_uint16_LE($f, $uint)
{
	fwrite($f, pack("v", $uint));
}
function write_uint32_LE($f, $uint)
{
	fwrite($f, pack("V", $uint));
}

/**
 * Create a 256x256 gray scale image out of random bytes.
 * Visual check for: no texture; flat histogram.
 * References: BMP format, https://en.wikipedia.org/wiki/BMP_file_format
 */
function generateImage()
{
	$fn = "test-Random.bmp";
	if( ! AUTO_TEST )
		echo "Generating image $fn...\n";
	
	$width = 256;
	$height = 256;
	
	$f = fopen($fn, "wb");
	
	// header (14 bytes):
	fwrite($f, "BM");
	write_uint32_LE($f, 14 + 40 + 2 + $width * $height); // file size
	fwrite($f, "\000\000\000\000"); // reserved by creator app
	write_uint32_LE($f, 14 + 40 + 2 + 256*4); // data start offset
	
	// DIB header (40 bytes):
	write_uint32_LE($f, 40); // size of this header
	write_uint32_LE($f, $width); // width pixels
	write_uint32_LE($f, $height); // height pixels
	write_uint16_LE($f, 1); // no. of colors planes (must be 1)
	write_uint16_LE($f, 8); // no. of bits per pixel
	write_uint32_LE($f, 0); // compression method none (BI_RGB=0)
	write_uint32_LE($f, 0); // image size (can be zero for BI_RGB)
	write_uint32_LE($f, 2835); // horizontal resolution (pixels/meter)
	write_uint32_LE($f, 2835); // vertical resolution (pixels/meter)
	write_uint32_LE($f, 0); // no. of colors in palette
	write_uint32_LE($f, 0); // no. of important colors (ignored?)
	
	// Gray scale colors LUT (256*4 bytes):
	$alpha = 0;
	for($gray = 0; $gray < 256; $gray++)
		write_uint32_LE($f, $gray | ($gray << 8) | ($gray << 16) | ($alpha << 24));
	
	// 32-bits word alignment (2 bytes):
	fwrite($f, "\000\000");
	
	// Raw image (256*256 bytes):
	for($row_no = 0; $row_no < $height; $row_no++)
		fwrite($f, Random::getCommon()->randomBytes($width));
	
	fclose($f);
}

function main()
{
	// Testing limits and min=max ranges:
	$prng = Random::getCommon();
	TU::test($prng->randomInt(PHP_INT_MIN, PHP_INT_MIN), PHP_INT_MIN);
	TU::test($prng->randomInt(PHP_INT_MAX, PHP_INT_MAX), PHP_INT_MAX);
	TU::test($prng->randomInt(0, 0), 0);
	TU::test($prng->randomInt(123, 123), 123);
	TU::test($prng->randomInt(-123, -123), -123);
	
	int_uniform_test(0, 9, 10000);
	int_uniform_test(-99, 99, 10000);
	int_uniform_test(PHP_INT_MIN, 0, 10000);
	int_uniform_test(PHP_INT_MIN, PHP_INT_MAX, 10000);
	int_uniform_test(PHP_INT_MIN >> 1, PHP_INT_MAX, 10000);
	int_uniform_test(PHP_INT_MIN >> 1, PHP_INT_MAX >> 1, 10000);
	int_uniform_test(0, PHP_INT_MAX >> 1, 10000);
	
	float_uniform_test(10000);
	
	modulo_bias_test();
	testRandomBytes();
	FIPS_tests();
//	generateImage();
}

main();
