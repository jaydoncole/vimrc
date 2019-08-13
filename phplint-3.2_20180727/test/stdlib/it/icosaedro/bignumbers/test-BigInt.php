<?php

require_once __DIR__ . "/../../../../../stdlib/autoload.php";

/*.
	require_module 'standard';
.*/

use it\icosaedro\bignumbers\BigInt;
use it\icosaedro\utils\TestUnit as TU;

BigInt::$optimize = TRUE;

$n = new BigInt("1");
TU::test($n->__toString(), "1");
TU::test($n->format(0), "1");
TU::test($n->format(1), "0.1");
TU::test($n->format(2), "0.01");

$n = new BigInt("-1");
TU::test($n->__toString(), "-1");
TU::test($n->format(0), "-1");
TU::test($n->format(1), "-0.1");
TU::test($n->format(2), "-0.01");

$n = new BigInt("0000");
TU::test($n->__toString(), "0");
TU::test($n->format(0), "0");
TU::test($n->format(1), "0.0");
TU::test($n->format(2), "0.00");

$n = new BigInt("1");
TU::test($n->format(3), "0.001");

$n = new BigInt("12345678900");
TU::test($n->format(), "12,345,678,900");
$m = $n->mul($n);
TU::test($m->__toString(), "152415787501905210000");
TU::test($m->div($n)->__toString(), "12345678900");
TU::test($m->div(new BigInt("321"))->__toString(), "474815537389112803");

$n = new BigInt("2");
TU::test($n->pow(0)->__toString(), "1");
TU::test($n->pow(1)->__toString(), "2");
TU::test($n->pow(2)->__toString(), "4");
TU::test($n->pow(3)->__toString(), "8");


$start = time();
$range_a = -123;
$range_b = +123;
$steps_n = $range_b-$range_a+1;
//echo "Performing extensive tests in the range $range_a..$range_b:\n";

for( $i=$range_a; $i <= $range_b; $i++ ){

	$step = $i-$range_a+1;
//	echo "Step $step of $steps_n (", (int)($step/$steps_n*100), "%)\n";

	set_time_limit(100);

	$a = new BigInt( $i );

	# Testing conversion from/to string:
	TU::test($a->__toString(), (string) $i);

	# Testing minus():
	$c = $a->minus();
	TU::test($c->__toString(), (string) (-$i));

	for( $j=$range_a; $j <= $range_b; $j++ ){
		#echo "i=$i j=$j\n";

		$b = new BigInt( $j );

		# Testing comparison:
		$cmp = $a->cmp($b);
		TU::test($cmp < 0  and  $i < $j
		or $cmp == 0 and $i == $j
		or $cmp > 0 and $i > $j, TRUE);

		# Testing addition:
		$c = $a->add($b);
		TU::test($c->__toString(), (string) ($i+$j));
		
		# Testing subtraction:
		$c = $a->sub($b);
		TU::test($c->__toString(), (string) ($i-$j));
		
		# Testing multiplication:
		$c = $a->mul($b);
		TU::test($c->__toString(), (string) ($i*$j));

		# Testing division:
		if( $j != 0 ){
			$c = $a->div_rem($b, $rem);
			TU::test($c->__toString(), (string) ((int)($i/$j)));
			TU::test($rem->__toString(), (string) ($i%$j));
		}
		
	}
}


// Base conversion tests
// =====================

/**
 * Test $n in base $a to base $b and back.
 * @param string $n
 * @param int $a
 * @param int $b
 * @access private
 */
function baseConvBackAndForth($n, $a, $b)
{
	TU::test( BigInt::fromBase( BigInt::fromBase($n, $a)->toBase($b) , $b)->toBase($a), $n);
}

TU::test(BigInt::fromBase("0", 10)->toBase(10), "0");
TU::test(BigInt::fromBase("1", 10)->toBase(16), "1");
TU::test(BigInt::fromBase("128", 10)->toBase(2), "10000000");
TU::test(BigInt::fromBase("128", 10)->toBase(16), "80");
TU::test(BigInt::fromBase("-128", 10)->toBase(16), "-80");
TU::test(BigInt::fromBase("128", 10)->toBase(32), "40");
TU::test(BigInt::fromBase("000128", 10)->toBase(16), "80");
TU::test(BigInt::fromBase("01011010", 2)->toBase(16), "5a");
baseConvBackAndForth("123456789", 10, 16);
baseConvBackAndForth("123456789", 10, 17);
baseConvBackAndForth("-123456789", 10, 18);
baseConvBackAndForth("123456789", 10, 35);
baseConvBackAndForth("123456789", 10, 36);
baseConvBackAndForth("abcdefghistuvwxywz", 36, 10);
