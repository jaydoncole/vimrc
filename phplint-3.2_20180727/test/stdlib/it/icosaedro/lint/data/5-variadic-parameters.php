<?php

require_once __DIR__ . "/../../../../../../stdlib/errors.php";

function f(/*. int .*/ ...$a)
{
	if( isset($a[0]) && $a[0] );
}

f();
f(1, 2, 3);
f('a');

class MyClass{}

function g(MyClass ... $a){}

g();
g(new MyClass);
//g(new MyClass, NULL);

function f1(/*. mixed .*/ ... $a){ if($a); }

class A{}

function f5(/*. int .*/ $dummy, A ... $a){ if($a); }

f5(0);
f5(0, new A());
f5(0, new A(), new A());

/**
 * @param int $dummy
 * @param A ... $a
 */
function f6($dummy, $a){ if($a); }

/**
 * @param int $dummy
 * @param A ... $a
 */
function f7($dummy, ...$a){ if($a); }

/**
 * @param int $dummy
 * @param A $a
 */
function f8($dummy, ...$a){ if($a); }

/*. forward void function f9(int ...$a); .*/
function f9(/*. int .*/ ...$a){}

/*. forward void function f10(int ...$a); .*/
function f10(/*. int .*/ $a){}


/*. forward void function f11(int $a); .*/
function f11(/*. int .*/ ...$a){}

class B {
	/*. forward public void function m1(int ...$a); .*/
	public function m1(/*. int .*/ ...$a){}
	
	/*. forward public void function m2(int ...$a); .*/
	public function m2(/*. int .*/ $a){}
	
	/*. forward public void function m3(int $a); .*/
	public function m3(/*. int .*/ ...$a){}
}

interface IF1 {
	function m1(/*. string .*/ ... $a);
	function m2(/*. int .*/ $a, /*. string .*/ ... $b);
	function m3(/*. int .*/ $a = 0, /*. string .*/ ... $b);
	function m4(/*. int .*/ $a, $b = 0, /*. string .*/ ... $c);
}

// ok
class C1 implements IF1 {
	function m1(/*. string .*/ ... $a){}
	function m2(/*. int .*/ $a, /*. string .*/ ... $b){}
	function m3(/*. int .*/ $a = 0, /*. string .*/ ... $b){}
	function m4(/*. int .*/ $a, $b = 0, /*. string .*/ ... $c){}
}

// missing ... in variadic arg
class C2 implements IF1 {
	function m1(/*. string .*/ $a){}
	function m2(/*. int .*/ $a, /*. string .*/ $b){}
	function m3(/*. int .*/ $a = 0, /*. string .*/ $b){}
	function m4(/*. int .*/ $a, $b = 0, /*. string .*/ $c){}
}

// wrong type of the variadic arg
class C3 implements IF1 {
	function m1(/*. resource .*/ ... $a){}
	function m2(/*. int .*/ $a, /*. resource .*/ ... $b){}
	function m3(/*. int .*/ $a = 0, /*. resource .*/ ... $b){}
	function m4(/*. int .*/ $a, $b = 0, /*. resource .*/ ... $c){}
}

// variadic param becomes default
class C4 implements IF1 {
	function m1(/*. string .*/ $a=NULL){}
	function m2(/*. int .*/ $a, /*. string .*/ $b=NULL){}
	function m3(/*. int .*/ $a = 0, /*. string .*/ $b=NULL){}
	function m4(/*. int .*/ $a, $b = 0, /*. string .*/ $c=NULL){}
}

// gracefully complains about unsupported features:
f(...[1,2,3]);
function f12(/*. mixed .*/ & ... $a){ if($a); }
/*. forward void function f13(string & ... $a); .*/

// Bug fix: crash on unknown type of variadic parameter:
function f14(...$a){ if($a); }
f14(1,2,3);
