<?php

// interface const can only be public:
interface IF0 {
	private const A = 1;
	protected const B = 1;
	public const C = 1;
}

interface IF1 {
	public const C = 1;
}

interface IF2 {
	public const C = 2;
}

abstract class ABS1 {
	public const C = 2;
}

// colliding inherited constants from IF or ABS class:
class C_IF1_IF2 extends ABS1 implements IF1, IF2 { }

class A implements IF1 {
	private const C = 9;
	private const X = 1;
	protected const Y = 2;
	public const Z = 3;
	
	static function m(){
		echo A::X, A::Y, A::Z;
	}
}

class B extends A {
	
	public const Z = 4;
	
	static function n(){
		echo B::X, B::Y, B::Z;
	}
	
}

A::m();
B::m();
B::n();
echo A::X, A::Y, A::Z;
echo B::X, B::Y, B::Z;
