<?php

/*. require_module 'spl'; .*/

namespace it\icosaedro\containers;

require_once __DIR__ . "/../../../../../stdlib/all.php";

use RuntimeException;
use it\icosaedro\utils\Floats;


function testArrayOfInt()
{
//	echo "\nTesting array of int:\n";
//	echo "Building array...\n";
	# Always generate the same sequence for testing:
	srand(1726354);
	$a = /*. (array[int]int) .*/ array();
	for($i = 0; $i < 1000; $i++)
		$a[$i] = rand(0, 999);

//	echo "Sorting...\n";
	$r = Arrays::sortArrayOfInt($a);
//	echo "finished.\n";

//	echo "Check ordering...\n";
	$n = count($r);
	for($i = 0; $i < $n-1; $i++)
		if( $r[$i] > $r[$i+1] )
			throw new RuntimeException( "error at index $i");
}


function testArrayOfFloat()
{
//	echo "\nTesting array of float:\n";
//	echo "Building array...\n";
	# Always generate the same sequence for testing:
	srand(1726354);
	$a = /*. (array[int]float) .*/ array();
	for($i = 0; $i < 1000; $i++)
		$a[$i] = (rand(0, 999) - 500.0)/10.0;

//	echo "Sorting...\n";
	$r = Arrays::sortArrayOfFloat($a);
//	echo "finished.\n";

//	echo "Check ordering...\n";
	$n = count($r);
	for($i = 0; $i < $n-1; $i++)
		if( Floats::compare($r[$i], $r[$i+1]) > 0 )
			throw new RuntimeException( "error at index $i");
}


/*. void .*/ function print_arr(/*. array[int]IntClass .*/ $a)
{
	for($i = 0; $i < count($a); $i++)
		echo $a[$i], " ";
}


function testArrayOfIntClass()
{
//	echo "\nTesting array of IntClass:\n";
//	echo "Building array...\n";
	# Always generate the same sequence for testing:
	srand(1726354);
	$a = /*. (array[int]IntClass) .*/ array();
	for($i = 0; $i < 1000; $i++)
		$a[$i] = new IntClass(rand(0, 999));

//	echo "Sorting...\n";
	$r = cast(IntClass::class."[int]", Arrays::sort($a));
//	echo "finished.\n";

	#print_arr($r);

//	echo "Check ordering...\n";
	$n = count($r);
	for($i = 0; $i < $n-1; $i++)
		if( $r[$i]->getValue() > $r[$i+1]->getValue() )
			throw new RuntimeException("error at index $i");
}


class IntClassComparator implements Sorter {

	/*. int .*/ function compare(/*. object .*/ $a, /*. object .*/ $b)
	{
		$ai = cast(IntClass::class, $a)->getValue();
		$bi = cast(IntClass::class, $b)->getValue();
		# May overflow, giving wrong result:
		#return $a - $b;
		# Compare int avoiding overflow:
		if( $ai < $bi )
			return -1;
		else if( $ai == $bi )
			return 0;
		else
			return +1;
	}

}


function testArrayOfIntClassWithComparator()
{
//	echo "\nTesting array of IntClass with Comparator:\n";
//	echo "Building array...\n";
	# Always generate the same sequence for testing:
	srand(1726354);
	$a = /*. (array[int]IntClass) .*/ array();
	for($i = 0; $i < 1000; $i++)
		$a[$i] = new IntClass(rand(0, 999));

//	echo "Sorting...\n";
	$r = cast(IntClass::class."[int]", Arrays::sortBySorter($a, new IntClassComparator()));
//	echo "finished.\n";

	#print_arr($r);

//	echo "Check ordering...\n";
	$n = count($r);
	for($i = 0; $i < $n-1; $i++)
		if( $r[$i]->getValue() > $r[$i+1]->getValue() )
			throw new RuntimeException( "error at index $i");
}


class testArrays extends \it\icosaedro\utils\TestUnit {

	function run()
	{
		testArrayOfInt();
		testArrayOfFloat();
		testArrayOfIntClass();
		testArrayOfIntClassWithComparator();
	}
	
}

$tu = new testArrays();
$tu->start();
