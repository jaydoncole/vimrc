<?php

require_once __DIR__ . "/../../../../../stdlib/autoload.php";

#use RuntimeException;
use it\icosaedro\containers\GenericHashSet;
use it\icosaedro\containers\Arrays;
use it\icosaedro\containers\IntClass;
use it\icosaedro\containers\StringClass;
use it\icosaedro\utils\Date;
use it\icosaedro\utils\TestUnit as TU;
use it\icosaedro\utils\Timer;
use it\icosaedro\utils\Statistics1D;


function testWithStrings()
{
	$hs = new GenericHashSet/*.<StringClass>.*/();

	$hs->put(new StringClass("one"));
	$hs->put(new StringClass("two"));
	$hs->put(new StringClass("three"));
	$hs->put(new StringClass("four"));

	$hs->remove(new StringClass("three"));
	$hs->remove(new StringClass("does not exist"));
	TU::test($hs->count(), 3);
	
	$elems = $hs->getElements();
	$elems = cast(StringClass::class."[int]", Arrays::sort($elems));
	TU::test($elems, array(new StringClass("four"), new StringClass("one"), new StringClass("two")));

	# Test iterator:
	$a = /*. (string[int]) .*/ array();
	foreach($hs as $e){
		#echo "   ", (string) $e, "\n";
		$a[] = (string) $e;
	}
	$a = Arrays::sortArrayOfString($a);
	TU::test($a, array("four", "one", "two"));

	TU::test($hs->contains(new StringClass("one")) and ! $hs->contains(new StringClass("")), TRUE);
}


function testWithDates()
{
	$hs = new GenericHashSet/*.<Date>.*/();
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
	$hs = new GenericHashSet/*.<IntClass>.*/();
//	echo "Inserting... ";
	for($i = 10000; $i > 0; $i--){
		$r = rand(0,9999);
		$hs->put(new IntClass($r));
	}

	srand(1234);
//	echo "Checking... ";
	for($i = 10000; $i > 0; $i--){
		$r = rand(0,9999);
		TU::test($hs->contains(new IntClass($r)), TRUE);
	}
}


function main()
{
	testWithStrings();
	testWithDates();
	testWithRandomNums();
}

main();
