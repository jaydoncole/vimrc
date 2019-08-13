<?php

/* 
 * Checking missing implementation of:
 * - functions declared 'forward'
 * - class declared 'forward'
 * - methods declared 'forward'
 * - inherited interface and abstract methods
 */

/*. forward void function f(); .*/

/*. forward class X {} .*/

interface MyInterface {  function m1(); }

interface MyInterface2 extends MyInterface {}

abstract class MyAbs { abstract function m2(); }

abstract class MyAbs2 extends MyAbs {}

/*.
	forward class A extends MyAbs2 implements MyInterface2 {
		void function m4();
	}
.*/

class A extends MyAbs2 implements MyInterface2 {
	/*. forward void function m3(); .*/
}
