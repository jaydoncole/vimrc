<?php

// EXAMPLES FOR THE TEMPLATE.HTM REFERENCE MANUAL
// ==============================================

require_once __DIR__ . "/../../../../../../stdlib/all.php";

/**
 * An instance of this class stores an object of type T.
 */
class Box /*. <T> .*/ {

	/**
	 * Stored object.
	 * @var T
	 */
	private $v;

	/**
	 * Stores the given object.
	 * @param T $v Object to store.
	 */
	function __construct(/*. T .*/ $v){ $this->v = $v; }

	/**
	 * Replaces the currently stored object.
	 * @param T $v Replacement object.
	 */
	function set($v){ $this->v = $v; }

	/**
	 * Returns the currently stored object.
	 * @return T The stored object.
	 */
	function get(){ return $this->v; }
}

# Two classes to be used in our examples:
class A { public $aNumber = 123; }
class B { public $aString = "hello"; }

# We create specific objects and arrays out of the generic Box class:
$a = new Box/*. <A> .*/(new A());
$b = new Box/*. <B> .*/(new B());
$manyBoxedA = /*. (Box<A>[int]) .*/ array();

# PHPLint is aware of the real nature of the these variables:
echo "The number is ", $a->get()->aNumber;
echo "The string is ", $b->get()->aString;
$manyBoxedA[0] = new Box/*. <A> .*/(new A());

//---------------------------------------------------------------------------

class Pair2 /*. <First,Second> .*/ {

	private /*. First .*/ $first;
	private /*. Second .*/ $second;

	/*. void .*/ function __construct(/*. First .*/ $first, /*. Second .*/ $second) {
		$this->first = $first;
		$this->second = $second;
	}

	/*. First  .*/ function getFirst()  { return $this->first;   }
	/*. Second .*/ function getSecond() { return $this->second; }
}
	
$p = new Pair2/*. <A,B> .*/(new A(), new B());
echo "The number is ", $p->getFirst()->aNumber;
echo "The string is ", $p->getSecond()->aString;

//---------------------------------------------------------------------------


/*.
	require_module 'core';
	require_module 'spl';
	require_module 'array';
.*/

use it\icosaedro\containers\Sortable;
use it\icosaedro\containers\Printable;

class SortedList /*. <E extends Sortable> .*/ implements Countable {

	private /*. E[int] .*/ $elements = array();

	public  /*. int .*/ function count(){ return count($this->elements); }

	public  /*. void .*/ function put(/*. E .*/ $e){
		// Bisection method.
		$l = 0;
		$r = count($this->elements);
		do {
			if( $l == $r ){
				// Insert at offset $l:
				array_splice($this->elements, $l, 0, array($e));
				return;
			}
			$m = (int) (($l + $r)/2);
			# Here is where we need to know that E implements Sortable:
			$cmp = $e->compareTo($this->elements[$m]);
			if( $cmp < 0 )
				$r = $m;
			else if( $cmp > 0 )
				$l = $m+1;
			else
				$l = $r = $m;
		} while(TRUE);
	}

	public /*. E .*/ function get(/*. int .*/ $i){ return $this->elements[$i]; }
	
	public /*. E[int] .*/ function getArray(){ return $this->elements; }
}

require_once SRC_BASE_DIR . "/utf8.php"; // handy u() function
use it\icosaedro\containers\Arrays;
use it\icosaedro\utils\Date;
use it\icosaedro\utils\UString;

# Ordered list of dates:
$holidays = new SortedList/*. <Date> .*/();
$holidays->put(new Date(2015, 12, 8) );
$holidays->put(new Date(2016, 1, 1) );
$holidays->put(new Date(2016, 1, 6) );
echo "Holidays: ", Arrays::implode($holidays->getArray(), ", "), "\n";

# Ordered list of Unicode strings:
$countries = new SortedList/*. <UString> .*/();
$countries->put(u("Ireland"));
$countries->put(u("Denmark"));
$countries->put(u("Poland"));
$countries->put(u("Austria"));
$countries->put(u("Italy"));
$countries->put(u("France"));
$countries->put(u("United Kingdom"));
$countries->put(u("Germany"));
$countries->put(u("Portugal"));
$countries->put(u("Spain"));
$countries->put(u("Greece"));
$countries->put(u("Norway"));
$countries->put(u("Sweden"));
$countries->put(u("Finland"));
echo "Countries:\n", Arrays::implode($countries->getArray(), "\n"), "\n";

//---------------------------------------------------------------------------


// inheritance test:
class BoxA extends Box/*.<A>.*/ {}

function randomTestsNotToBeExecuted()
{
	/*. BoxA .*/ $x = new Box/*.<A>.*/(new A()); // ERR: Box<A> is not BoxA
	/*. Box<A> .*/ $y = new BoxA(new A()); // ok
	
	$sl3 = new SortedList(); // creates SortedList<? extends Sortable>
	$sl3->put(new Date(2000,1,1)); // ERR: Date is not ? extends Sortable
	$sl3->put(new A()); // ERR: A is not ? extends Sortable
	/*. Sortable .*/ $aSortable3 = $sl3->get(0); // ok
	
	$sl4 = new SortedList/*.<Sortable>.*/();
	$sl4->put(new Date(2000,1,1)); // ok
	$sl4->put(new A()); // ERR: A is not Sortable
	/*. Sortable .*/ $aSortable4 = $sl4->get(0); // ok
}

//---------------------------------------------------------------------------
// The it\icosaedro\containers namespace contains some generic classes and the
// corresponding tests are examples of concrete applications it worths to check:

require_once __DIR__ . "/../../containers/test-GenericArray.php";
require_once __DIR__ . "/../../containers/test-GenericHashSet.php";
require_once __DIR__ . "/../../containers/test-GenericHashMap.php";