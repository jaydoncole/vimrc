<?php

require_once __DIR__ . "/../../../../../stdlib/autoload.php";

use it\icosaedro\bignumbers\BigInt;
use it\icosaedro\bignumbers\BigFloat;
use it\icosaedro\utils\TestUnit as TU;

ini_set("precision", "12");

define("EPSILON", 1e-9);

BigInt::$optimize = TRUE;


function specificTests() {
	// PHP 7 supports "zero negative", so -(0.0) becomes -0.0. Check:
	$n = new BigFloat(-0.0);
	TU::test("$n", "0");
	
	$n = new BigFloat("12.34");
	$b = new BigFloat("7");
	$q = $n->div_rem($b, 2, $r);
	TU::test("$q rem=$r", "0 rem=12.34");
	$q = $n->div_rem($b, 1, $r);
	TU::test("$q rem=$r", "0 rem=12.34");
	$q = $n->div_rem($b, -1, $r);
	TU::test("$q rem=$r", "1.7 rem=0.44");
	$q = $n->div_rem($b, -2, $r);
	TU::test("$q rem=$r", "1.76 rem=0.02");

	/*
	$price = new BigFloat("56.78");
	$VAT_rate = new BigFloat("0.20");
	$VAT = $price->mul($VAT_rate)->round(-2);
	$total = $price->add($VAT);
	echo "Price: ", $price->format(2), "\n";
	echo "VAT  : ", $VAT->format(2), "\n";
	echo "Total: ", $total->format(2), "\n";
	*/


	$n = new BigFloat("12.345");
	TU::test($n->trunc_rem(-2, $r)->__toString(), "12.34");
	TU::test($r->__toString(), "0.005");
	TU::test($n->trunc_rem( 1, $r)->__toString(), "10");
	TU::test($r->__toString(), "2.345");
	TU::test($n->trunc_rem( 2, $r)->__toString(), "0");
	TU::test($r->__toString(), "12.345");
	TU::test($n->trunc_rem(-9, $r)->__toString(), "12.345");
	TU::test($r->__toString(), "0");
	$n = new BigFloat("-12.345");
	TU::test($n->trunc_rem(-2, $r)->__toString(), "-12.34");
	TU::test($r->__toString(), "-0.005");

	$n = new BigFloat("12.345");
	TU::test($n->round(-2)->__toString(), "12.35");
	TU::test($n->round(-1)->__toString(), "12.3");

	$n = new BigFloat("45.678");
	TU::test($n->round(-2)->__toString(), "45.68");

	$n = new BigFloat("-12.345");
	TU::test($n->round(-2)->__toString(), "-12.35");
	TU::test($n->round(-1)->__toString(), "-12.3");

	$n = new BigFloat("-45.678");
	TU::test($n->round(-2)->__toString(), "-45.68");

	$n = new BigFloat("12.456");
	TU::test($n->format(2), "12.45");

	$n = new BigFloat("12.456");
	TU::test($n->format(2), "12.45");
	TU::test($n->format(0), "12");

	$n = new BigFloat("1200");
	TU::test($n->format(2), "1,200.00");
	TU::test($n->minus()->format(2), "-1,200.00");

	$n = new BigFloat("0.012");
	TU::test($n->format(2), "0.01");
	TU::test($n->format(3), "0.012");
	TU::test($n->format(4), "0.0120");

	$n = new BigFloat("100");
	TU::test($n->div( new BigFloat("3"), -5 )->format(2), "33.33");
	TU::test($n->div( new BigFloat("3"), -2 )->format(2), "33.33");
	TU::test($n->div( new BigFloat("3"), -1 )->format(2), "33.30");

	$n = new BigFloat("4");
	$d = new BigFloat("7");
	TU::test($n->div($d, -6)->format(6), "0.571428");
	TU::test($n->div($d, -6)->format(5), "0.57142");
	TU::test($n->div($d, -6)->round(-5)->format(5), "0.57143");

	/*
	$n = new BigFloat("100e20000000");
	$d = new BigFloat("30");
	$q = $n->div_rem($d, -2, $r);
	echo $q, " resto ", $r;
	exit;
	*/

//	$n = new BigFloat("1234");
//	$d = new BigFloat("890");
//	$q = $n->div_rem($d, -3, $r);
//	echo "$n/$d = $q ($r)\n";
//	echo "$q*$d + $r - $n = ", $q->mul($d)->add($r)->sub($n), "\n";
//
//	$n = new BigFloat("12340");
//	$d = new BigFloat("1.53");
//	$q = $n->div_rem($d, -3, $r);
//	echo "$n/$d = $q ($r)\n";
//	echo "$q*$d + $r - $n = ", $q->mul($d)->add($r)->sub($n), "\n";
//
//	$n = new BigFloat("4");
//	$d = new BigFloat("7");
//	$q = $n->div_rem($d, -3, $r);
//	echo "$n/$d = $q ($r)\n";
//	echo "$q*$d + $r - $n = ", $q->mul($d)->add($r)->sub($n), "\n";


	$n = new BigFloat("0");
	TU::test($n->__toString(), "0");

	$n = new BigFloat("000");
	TU::test($n->__toString(), "0");

	$n = new BigFloat("00.0000");
	TU::test($n->__toString(), "0");

	$n = new BigFloat("1");
	TU::test($n->__toString(), "1");

	$n = new BigFloat("-1");
	TU::test($n->__toString(), "-1");

	$n = new BigFloat("1.9");
	TU::test($n->__toString(), "1.9");

	$n = new BigFloat("1e6");
	TU::test($n->__toString(), "1000000");

	$n = new BigFloat("0e6");
	TU::test($n->__toString(), "0");

	$n = new BigFloat("0.0010");
	TU::test($n->__toString(), "0.001");

	$n = new BigFloat("-1.23");
	TU::test($n->__toString(), "-1.23");

	$n = new BigFloat("12.34e+5");
	TU::test($n->__toString(), "1234000");

	$n = new BigFloat("-12.34e-5");
	TU::test($n->__toString(), "-0.0001234");

	$n = new BigFloat("1230000");
	$n = $n->add( new BigFloat("456") );
	TU::test($n->__toString(), "1230456");

	$n = new BigFloat("12.30000");
	$n = $n->add( new BigFloat("0.00456") );
	TU::test($n->__toString(), "12.30456");

	$n = new BigFloat("0.01230000");
	$n = $n->add( new BigFloat("0.00000456") );
	TU::test($n->__toString(), "0.01230456");

	$n = new BigFloat( new BigInt(1000) );
	TU::test($n->__toString(), "1000");

	$n = new BigFloat("120");
	TU::test($n->ceil()->__toString(), "120");

	$n = new BigFloat("1.2");
	TU::test($n->ceil()->__toString(), "2");

	$n = new BigFloat("-1.2");
	TU::test($n->ceil()->__toString(), "-1");

	$n = new BigFloat("-120");
	TU::test($n->ceil()->__toString(), "-120");

	$n = new BigFloat("120");
	TU::test($n->floor()->__toString(), "120");

	$n = new BigFloat("1.2");
	TU::test($n->floor()->__toString(), "1");

	$n = new BigFloat("-1.2");
	TU::test($n->floor()->__toString(), "-2");

	$n = new BigFloat("-120");
	TU::test($n->floor()->__toString(), "-120");

	$n = new BigFloat("1.9");
	TU::test($n->toBigInt()->__toString(), "1");

	$n = new BigFloat("-1.9");
	TU::test($n->toBigInt()->__toString(), "-1");

	$n = new BigFloat("1.9");
	TU::test("".$n->toInt(), "1");

	$n = new BigFloat("-1.9");
	TU::test("".$n->toInt(), "-1");
}


function arithmeticTestsOnRange() {
	$range_a = -123;
	$range_b = +123;
	$scale = 0.01;
	//echo "Performing extensive tests in the range $range_a..$range_b:\n";

	for( $i=$range_a; $i <= $range_b; $i++ ){
	//	echo "Step $step of $steps_n (", (int)($step/$steps_n*100), "%)\n";

		$f = $scale * $i;

		$a = new BigFloat( (string) $f );

		# Testing conversion from/to string:
		TU::test($a->__toString(), (string) $f);

		# Testing minus():
		$c = $a->minus();
		// since PHP 7, -(0.0) becomes -0.0. Fix:
		TU::test($c->__toString(), $f === 0.0? "0" : (string) (-$f));

		for( $j=$range_a; $j <= $range_b; $j++ ){

			$g = $scale * $j;

			$b = new BigFloat( (string) $g );

			# Testing comparison:
			$cmp = $a->cmp($b);
			if( $cmp < 0  and  $f >= $g
			or $cmp == 0 and $f != $g
			or $cmp > 0 and $f <= $g )
				throw new RuntimeException("test cmp(): error comparing $f with $g: got $cmp");

			# Testing addition:
			$c = $a->add($b);
			###if( abs( (float) $c->__toString() - ($f+$g) ) > EPSILON ){
			TU::test($c->__toString(), (string) ($f+$g));

			# Testing subtraction:
			$c = $a->sub($b);
			###if( abs((float) $c->__toString() - ($f-$g) ) > EPSILON ){
			TU::test($c->__toString(), (string) ($f-$g));

			# Testing multiplication:
			$c = $a->mul($b);
			if( abs( (float) $c->__toString() - ($f*$g) ) > EPSILON )
				throw new RuntimeException("test mul(): error ($f)*($g) gives $c rather than ". ($f*$g));

			# Testing division:
			if( $j != 0 ){
				$c = $a->div_rem($b, -5, $rem);
				/*
				if( abs( (float) $c->__toString() - ((float)$i/$j) ) > EPSILON ){
					echo "test div_rem(): error (",
						$scale*$i, ")/(", $scale*$j, ") gives $c\n";
					$err++;
				}
				*/
				if( $c->mul($b)->add($rem)->sub($a)->sign() != 0 ){
					throw new RuntimeException(
						"test rem(): error ($f)%($g): invalid remainder: "
						. " $c*$b+$rem-$a = " . $c->mul($b)->add($rem)->sub($a));
				}
			}

		}

	}
}
/**
 * Calculates the square root of the squared parameter and compares the result
 * against the parameter withint the given precision.
 * @param string $sroot Square root.
 * @param int $precision Power of ten of the desired precision.
 * @throws RuntimeException Square root outside the expected precision range.
 */
function testSingleSqrt($sroot, $precision) {
	$epsilon = new BigFloat("1e$precision");
	$root = new BigFloat($sroot);
	$square = $root->mul($root);
	$computed_root = $square->sqrt($precision);
	if( $computed_root->sub($root)->abs()->cmp($epsilon) >= 0 )
		throw new RuntimeException("sqrt($root * $root) --> $computed_root");
}


/**
 * Test sqrt() on a range of values within the given precision.
 * @param int $precision Power of ten.
 */
function testSqrtPrecision($precision) {
	for($i=0; $i<1000; $i++){
//		if( $i % 100 == 0 )
//			echo "$i...\n";
		testSingleSqrt("$i", $precision);
	}
}


function testSqrt() {
	testSqrtPrecision(-1);
	testSqrtPrecision(0);
	testSqrtPrecision(1);
}


function main() {
	specificTests();
	arithmeticTestsOnRange();
	testSqrt();
}

main();
