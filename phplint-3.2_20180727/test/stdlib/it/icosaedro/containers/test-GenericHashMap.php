<?php

require_once __DIR__ . "/../../../../../stdlib/autoload.php";

use it\icosaedro\containers\Arrays;
use it\icosaedro\containers\GenericArray;
use it\icosaedro\containers\GenericHashMap;
use it\icosaedro\containers\IntClass;
use it\icosaedro\containers\StringClass;
use it\icosaedro\utils\Date;
use it\icosaedro\utils\TestUnit as TU;
use it\icosaedro\utils\Timer;


function testWithStringKeys()
{
	$m = new GenericHashMap/*.<StringClass,IntClass>.*/();

	$m->put(new StringClass("one"), new IntClass(1));
	$m->put(new StringClass("two"), new IntClass(2));
	$m->put(new StringClass("three"), new IntClass(3));
	$m->put(new StringClass("four"), new IntClass(4));

	$m->remove(new StringClass("three"));

	TU::test($m->count(), 3);

	$keys = $m->getKeys();
	TU::test(
		Arrays::sort($keys),
		array(new StringClass("four"), new StringClass("one"), new StringClass("two")));

	$values = $m->getElements();
	TU::test(
		Arrays::sort($values),
		array(new IntClass(1), new IntClass(2), new IntClass(4)));

	if( ! ( $m->containsKey(new StringClass("one")) and ! $m->containsKey(new StringClass("five")) ) )
		throw new RuntimeException("failed");
}


function testWithDateKeys()
{
	$m = new GenericHashMap/*.<Date,StringClass>.*/();

	$m->put(new Date(2012, 1, 1), new StringClass("year 2012 begins"));
	$m->put(new Date(2011, 12, 31), new StringClass("year 2011 ends"));
	$m->put(new Date(2012, 2, 29), new StringClass("leap day of the 2012"));

	$m->remove(new Date(2011, 12, 31));

	TU::test($m->count(), 2);

//	echo "Keys:\n";
	$keys = $m->getKeys();
	TU::test(Arrays::implode(cast(Date::class."[int]", Arrays::sort($keys)), ", "),
		"2012-01-01, 2012-02-29");

//	echo "Values:\n";
//	$values = $m->getElements();
//	foreach($values as $v)
//		echo "   $v\n";
	
//	echo "Pairs:\n";
//	$pairs = $m->getPairs();
//	foreach($pairs as $pair)
//		echo "   ", (string) $pair[0], ": ", (string) $pair[1], "\n";

	if( ! $m->containsKey(new Date(2012, 1, 1)) )
		throw new RuntimeException("failed");

	if( $m->containsKey(new Date(1999, 1, 1)) )
		throw new RuntimeException("failed");
}

function testWithGenericArray() {
	# Example 2: maps (Date,string) pairs:
	$quotes = new GenericHashMap/*. <Date,GenericArray<StringClass>> .*/();
	
	// Add quotes for 2012-01-01:
	$a1 = new GenericArray/*.<StringClass>.*/();
	$a1->append(new StringClass("q1"));
	$a1->append(new StringClass("q2"));
	$d1 = new Date(2012, 1, 1);
	$quotes->put($d1, $a1);
	
	// Add quotes for 2012-01-01:
	$a2 = new GenericArray/*.<StringClass>.*/();
	$a2->append(new StringClass("q3"));
	$a2->append(new StringClass("q4"));
	$d2 = new Date(2012, 1, 2);
	$quotes->put($d2, $a2);
	
	TU::test($quotes->get($d1), $a1);
	TU::test($quotes->get($d2), $a2);
	TU::test($quotes->get(new Date(1999, 1, 1)), NULL);
	
	// Retrieve element and check if it equals a completely new array:
	$a3 = new GenericArray/*.<StringClass>.*/();
	$a3->append(new StringClass("q1"));
	$a3->append(new StringClass("q2"));
	TU::test($quotes->get($d1), $a3);

	# Displays quote of the day:
	$today = new Date(2012, 1, 1);
	$quotes_of_day = $quotes->get($today);
	TU::test("$quotes_of_day", "it\\icosaedro\\containers\\GenericArray[array(0=>q1, 1=>q2)]");

	// Test iterator:
	$s = "";
	foreach($quotes as $date => $quotes_of_day){
		$s .= "$quotes_of_day ";
	}
	TU::test($s, "it\\icosaedro\\containers\\GenericArray[array(0=>q1, 1=>q2)] it\\icosaedro\\containers\\GenericArray[array(0=>q3, 1=>q4)] ");
}


function testWithRandomNums()
{
	$m = new GenericHashMap/*.<IntClass,IntClass>.*/();
	$t = new Timer(TRUE);
	$n = 1000;
//	echo "Inserting... ";
	srand(1234);
	for($i = $n; $i > 0; $i--){
		$r = rand(0,9999);
		$v = new IntClass($r);
		$m->put($v, $v);
	}

//	echo "Checking... ";
	srand(1234);
	$t->reset();
	$t->start();
	for($i = $n; $i > 0; $i--){
		$r = rand(0,9999);
		$v = new IntClass($r);
		$v2 = $m->get($v);
		TU::test($v2, $v);
	}
}

function main() {
	testWithStringKeys();
	testWithDateKeys();
	testWithGenericArray();
	testWithRandomNums();
}

main();
