<?php

// http://www.php.net/manual/en/language.oop5.anonymous.php

interface IF1 {
	function m1();
}

abstract class AC1 {
	abstract function m2();
}

$o1 = new class {
	function m0(){ echo __METHOD__, "\n"; }
};

$o1->m0();

$o2 = new class(1) implements IF1 {
	function m1(){ echo __METHOD__, "\n"; }
};

$o2->m1();

$o3 = new class(1, 2) extends AC1 {
	/**
	 * @return void
	 */
	function m2(){ echo __METHOD__, "\n"; }
};

$o3->m2();

$o4 = new class() {
	function __construct(){ echo __METHOD__, "\n"; }
	function m4(){ echo __METHOD__, "\n"; }
};

$o4->m4();

// Anon class in function context:
function f() {
	$o = new class {
		function m1(){}
	};
	
	$o->m1();
	return $o;
}

f()->m1();
if(f());

// Anon class in method context:
class C {

	function m3() {
		$o = new class{
			function m4(){}
		};
		
		$o->m4();
		return $o;
	}
	
	function m5(){}

}

$c = new C();
$c->m3();
$c->m5();
$c->m3()->m4();
if( $c->m3() );



// ERROR DETECTION -- INVALID PHP 7 CODE FROM HERE ON


$o5 = new class { // access to non-public ctor
	private function __construct(){}
};

$o6 = new class(1) {
	function __construct(int $i){}
};

$o7 = new class() { // missing mandatory param
	function __construct(int $i){}
};

$o8 = new class(1, 2) {
	function __construct(int $i /*. , args .*/){}
};

$o9 = new class(1, 2, 3) {
	function __construct(int $i, int ...$iii){}
};

$o10 = new class(1, 2, 3, "aa") {  // invalid variadic param
	function __construct(int $i, int ...$iii){}
};

$o11 = new class("a") { // invalid type
	function __construct(int $i){}
};

$o12 = new class() {
	function __construct($i = 0){}
};
