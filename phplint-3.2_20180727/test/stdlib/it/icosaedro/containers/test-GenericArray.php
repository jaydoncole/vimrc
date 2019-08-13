<?php

require_once __DIR__ . "/../../../../../stdlib/all.php";

use it\icosaedro\containers\GenericArray;
use it\icosaedro\containers\Arrays;
use it\icosaedro\containers\GenericSorter;
use it\icosaedro\containers\StringClass;
use it\icosaedro\containers\IntClass;
use it\icosaedro\utils\TestUnit as TU;

/*. require_module 'array'; .*/

function testInsert() {
	$l = new GenericArray/*.<StringClass>.*/();
	$l->insert(0, new StringClass("b"));
	$l->insert(0, new StringClass("a"));
	$l->insert(2, new StringClass("d"));
	$l->insert(2, new StringClass("c"));
	TU::test(TU::dump($l->asArray()), "array(0=>a, 1=>b, 2=>c, 3=>d)");
}

function testRemove() {
	$l = new GenericArray/*.<StringClass>.*/();
	$l->append(new StringClass("a"));
	$l->append(new StringClass("b"));
	$l->append(new StringClass("c"));
	$l->append(new StringClass("d"));
	// remove in middle:
	$l->remove(1);
	TU::test(TU::dump($l->asArray()), "array(0=>a, 1=>c, 2=>d)");
	// remove first:
	$l->remove(0);
	TU::test(TU::dump($l->asArray()), "array(0=>c, 1=>d)");
	// remove last:
	$l->remove(1);
	TU::test(TU::dump($l->asArray()), "array(0=>c)");
	// remove last:
	$l->remove(0);
	TU::test(TU::dump($l->asArray()), "array()");
}


/**
 * @param string[int] $words
 */
function testSort($words) {
	$a = new GenericArray/*.<StringClass>.*/();
	foreach($words as $word)
		$a->append(new StringClass($word));
	$a->sort(StringClass::getDefaultSorter());
	sort($words);
	TU::test(Arrays::implode($a->asArray(), ", "), implode(", ", $words));
}


function testArrayOfIntClass()
{
	# Always generate the same sequence for testing:
	srand(1726354);
	$a = new GenericArray/*.<IntClass>.*/();
	for($i = 0; $i < 1000; $i++)
		$a->append(new IntClass(rand(0, 999)));

	$a->sort(IntClass::getDefaultSorter());

	$n = $a->count();
	for($i = 0; $i < $n-1; $i++)
		if( $a->get($i)->getValue() > $a->get($i+1)->getValue() )
			throw new RuntimeException("error at index $i");
}


/**
 * @param string[int] $words
 */
function testIterator($words) {
	$a = new GenericArray/*.<StringClass>.*/();
	foreach($words as $word)
		$a->append(new StringClass($word));
	$s = "";
	foreach($a as $e)
		$s .= $e;
	TU::test($s, implode("", $words));
}

function main()
{
	testInsert();
	testRemove();
	testSort(/*.(string[int]).*/ array());
	testSort(array("zero"));
	testSort(array("zero", "one"));
	testSort(array("one", "zero"));
	testSort(array("zero", "zero"));
	testSort(array("zero", "zero", "zero"));
	testSort(array("zero", "one", "two"));
	testSort(array("zero", "one", "two", "three", "four", "five",
		"six", "seven", "eight", "nine", "ten",
		"eleven", "twelve", "thirteen", "fourteen", "fifteen",
		"sixteen", "seventeen", "eighteen"));
	testArrayOfIntClass();
	testIterator(/*.(string[int]).*/ array());
	testIterator(array("zero"));
	testIterator(array("zero", "one"));
}

main();
