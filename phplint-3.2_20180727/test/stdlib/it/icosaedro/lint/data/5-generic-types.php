<?php

// Checking all the contexts where a type parameter is allowed.

require_once __DIR__ . "/../../../../../../stdlib/all.php";

class BareClass {}
class Pair/*. <A,B> .*/ {}

interface IF1 /*. <IF1_T> .*/ {
	/*. IF1_T .*/ function m(/*. IF1_T .*/ $x); // ok
}

class C1 /*. <C1_T> .*/  {
	function m1(/*. C1_T .*/ $x){}
}

class C2 /*. <C2_T> .*/
	extends C1 /*. <C2_T> .*/
	implements IF1/*.<C2_T>.*/
{
	
	function m(/*. C2_T .*/ $x){ return $x; } // ok
	function m1(C2_T $x){} // <-- ERROR: type parameter in actual PHP code
	
}

cast("C1", 1); // ERROR: generic in cast()
cast("C1<BareClass>", 1); // ERROR: generic in cast()
cast("C1<BareClass>[int]", 1); // ERROR: generic in cast()
cast("array[]C1", 1); // ERROR: generic in cast()



class BAD /*. <A,A> .*/ {} // <-- ERROR: duplicated format type

class MyException/*.<T>.*/ extends Exception {} // <-- ERROR: exception cannot be generic

class C3/*. <T> .*/ {
	public /*. T .*/ $p1;
	static /*. T .*/ $p2;
	static function m(/*. T .*/ $x){}
	static /*. T .*/ function m2(/*. T .*/ $x){ return NULL; }
	/** @param T $x */
	static function m3($x){} // <-- ERROR: generic param in static method
	/** @return T */
	static function m4(){ return NULL; } // <-- ERROR: generic return in static method
	
	function m5(T $x): T{} // <-- ERROR: type parameter in actual PHP code

	function m6(){
		$m = /*. (mixed) .*/ NULL;
		cast("T", $m); // <-- ERROR: cast requires normal class
		cast("Pair<object,T>", $m); // <-- ERROR: cast requires normal class
		cast("Pair<object,object>", $m); // <-- ERROR: cast requires normal class
	}
}
