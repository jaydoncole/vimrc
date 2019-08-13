<?php

namespace it\icosaedro\containers;

require_once __DIR__ . "/../../../../../stdlib/autoload.php";

use RuntimeException;
use it\icosaedro\containers\Arrays;
use it\icosaedro\utils\Date;
use it\icosaedro\utils\TestUnit as TU;
use it\icosaedro\utils\Timer;
use it\icosaedro\utils\Statistics1D;


function testWithStringKeys()
{
	$m = new HashMap();

	$m->put("one", 1);
	$m->put("two", 2);
	$m->put("three", 3);
	$m->put("four", 4);

	$m->remove("three");

	TU::test($m->count(), 3);

	$keys = cast("string[int]", $m->getKeys());
	TU::test(
		Arrays::sortArrayOfString($keys),
		array("four", "one", "two"));

	$values = cast("int[int]", $m->getElements());
	TU::test(
		Arrays::sortArrayOfInt($values),
		array(1, 2, 4));

	if( ! ( $m->containsKey("one") and ! $m->containsKey("five") ) )
		throw new RuntimeException("failed");
}


function testWithDateKeys()
{
	$m = new HashMap();

	$other_day = new Date(1999, 12, 31);
	$m->put($other_day, "some day");
	$m->put(new Date(2012, 1, 1), "year 2012 begins");
	$m->put(new Date(2011, 12, 31), "year 2011 ends");
	$m->put(new Date(2012, 2, 29), "leap day of the 2012");

	$m->remove($other_day);

	TU::test($m->count(), 3);

//	echo "Keys:\n";
	$keys = $m->getKeys();
	$s = "";
	foreach($keys as $k)
		$s .= (string) $k . ", ";
	TU::test($s, "2012-02-29, 2012-01-01, 2011-12-31, ");

//	echo "Values:\n";
	$values = $m->getElements();
	$s = "";
	foreach($values as $v)
		$s .= (string) $v . ", ";
	TU::test($s, "leap day of the 2012, year 2012 begins, year 2011 ends, ");
	
//	echo "Pairs:\n";
	$pairs = $m->getPairs();
	$s = "";
	foreach($pairs as $pair)
		$s .= (string) $pair[0] . ": " . (string) $pair[1] . ", ";
	TU::test($s, "2012-02-29: leap day of the 2012, 2012-01-01: year 2012 begins, 2011-12-31: year 2011 ends, ");

	if( ! ( $m->containsKey(new Date(2012, 1, 1)) and ! $m->containsKey($other_day) ) )
		throw new RuntimeException("failed");
}


function testWithRandomNums()
{
	srand(1234);
	$m = new HashMap();
	$stat = new Statistics1D();
//	$t = new Timer(TRUE);
//	echo "Inserting... ";
	for($i = 10000; $i > 0; $i--){
		$r = rand(0,9999);
		$stat->put($r);
		$k = "s" . $r;
		$v = $r;
		$m->put($k, $v);
	}
//	echo "elapsed ", $t->elapsedMilliseconds(), " ms\n";
//	echo "Random nums stat: ", $stat, "\n";
	$sigma = 9999.0/sqrt(12.0);
	TU::test(abs($stat->deviation() - $sigma) < 0.1*$sigma, TRUE);
	TU::test(abs($stat->mean() - 5000) < $sigma, TRUE);

	srand(1234);
//	echo "Scanning... ";
//	$t->reset();
//	$t->start();
	for($i = 10000; $i > 0; $i--){
		$r = rand(0,9999);
		$k = "s" . $r;
		$v = $r;
		$v2 = $m->get($k);
		TU::test($v2, $v);
	}
//	echo "elapsed ", $t->elapsedMilliseconds(), " ms\n";
}

class testHashMap extends TU {
	function run()
	{
		testWithStringKeys();
		testWithDateKeys();
		testWithRandomNums();
	}
}

$tu = new testHashMap();
$tu->start();
