<?php

require_once __DIR__ . "/../../../../../stdlib/autoload.php";

#use RuntimeException;
use it\icosaedro\containers\HashSet;
use it\icosaedro\containers\Arrays;
use it\icosaedro\utils\Date;
use it\icosaedro\utils\TestUnit as TU;
use it\icosaedro\utils\Timer;
use it\icosaedro\utils\Statistics1D;


function testWithStrings()
{
	$hs = new HashSet();

	$hs->put("one");
	$hs->put("two");
	$hs->put("three");
	$hs->put("four");

	$hs->remove("three");
	$hs->remove("does not exist");
	TU::test($hs->count(), 3);
	
	$elems = cast("string[int]", $hs->getElements());
	$elems = Arrays::sortArrayOfString($elems);
	TU::test($elems, array("four", "one", "two"));

	# Test iterator:
	$a = /*. (string[int]) .*/ array();
	foreach($hs as $e){
		#echo "   ", (string) $e, "\n";
		$a[] = (string) $e;
	}
	$a = Arrays::sortArrayOfString($a);
	TU::test($a, array("four", "one", "two"));

	TU::test($hs->contains("one") and ! $hs->contains(""), TRUE);
}


function testWithDates()
{
	$hs = new HashSet();
	$hs->put(new Date(2012, 1, 1));
	$hs->put(new Date(2011, 12, 31));
	$hs->put(new Date(2012, 2, 29));

	$hs->remove(new Date(2011, 12, 31));

	# Test iterator:
	$a = /*. (string[int]) .*/ array();
	foreach($hs as $e){
		$a[] = (string) $e;
	}
	$a = cast("string[int]", Arrays::sortArrayOfString($a));
	TU::test(TU::dump($a),
		"array(0=>\"2012-01-01\", 1=>\"2012-02-29\")");
}


function testWithRandomNums()
{
	srand(1234);
	$hs = new HashSet();
	$stat = new Statistics1D();
//	echo "Inserting... ";
	for($i = 10000; $i > 0; $i--){
		$r = rand(0,9999);
		$stat->put($r);
		$hs->put($r);
	}
//	echo $t->elapsedMilliseconds(), " ms\n";
//	echo "Count: ", $hs->count(), "\n";
//	echo "Random nums stat: ", $stat, "\n";
	TU::test(abs($hs->count() - 6300) < 500, TRUE);
	$sigma = 9999.0/sqrt(12);
	TU::test(abs($stat->deviation() - $sigma) < 0.1*$sigma, TRUE);

	srand(1234);
//	echo "Checking... ";
	for($i = 10000; $i > 0; $i--){
		$r = rand(0,9999);
		TU::test($hs->contains($r), TRUE);
	}
}


class testHashSet extends TU {
	function run()
	{
		testWithStrings();
		testWithDates();
		testWithRandomNums();
	}
}

$tu = new testHashSet();
$tu->start();
