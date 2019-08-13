<?php require_once __DIR__ . "/../../../../../../stdlib/cast.php";

class Box/*. <T> .*/ {
	private /*. T .*/ $v;
	function __construct(/*. T .*/ $v) { $this->v = $v; }
	function get(){  return $this->v; }
	function set(/*. T .*/ $v) { $this->v = $v; }
}

class A {}
class B extends A {}
class C extends B {}
class Z {}

function f(/*. Box<?> .*/ $box) {
	/*. object .*/ $o = $box->get();
	/*. A .*/ $a = $box->get(); $a = cast(A::class, $box->get());
	/*. B .*/ $b = $box->get(); $b = cast(B::class, $box->get());
	/*. C .*/ $c = $box->get(); $c = cast(C::class, $box->get());
	/*. Z .*/ $z = $box->get(); $z = cast(Z::class, $box->get());
	$box->set(new A());
	$box->set(new B());
	$box->set(new C());
	$box->set(new Z());
}

function g(/*. Box<? extends B> .*/ $box) {
	/*. object .*/ $o = $box->get();
	/*. A .*/ $a = $box->get(); $a = cast(A::class, $box->get());
	/*. B .*/ $b = $box->get(); $b = cast(B::class, $box->get());
	/*. C .*/ $c = $box->get(); $c = cast(C::class, $box->get());
	/*. Z .*/ $z = $box->get(); $z = cast(Z::class, $box->get());
	$box->set(new A());
	$box->set(new B());
	$box->set(new C());
	$box->set(new Z());
}

function h(/*. Box<? parent B> .*/ $box) {
	/*. object .*/ $o = $box->get();
	/*. A .*/ $a = $box->get(); $a = cast(A::class, $box->get());
	/*. B .*/ $b = $box->get(); $b = cast(B::class, $box->get());
	/*. C .*/ $c = $box->get(); $c = cast(C::class, $box->get());
	/*. Z .*/ $z = $box->get(); $z = cast(Z::class, $box->get());
	$box->set(new A());
	$box->set(new B());
	$box->set(new C());
	$box->set(new Z());
}

f(NULL);
g(NULL);
h(NULL);

f(new Box/*.<A>.*/(NULL));
f(new Box/*.<B>.*/(NULL));
f(new Box/*.<C>.*/(NULL));
f(new Box/*.<Z>.*/(NULL));

g(new Box/*.<A>.*/(NULL));
g(new Box/*.<B>.*/(NULL));
g(new Box/*.<C>.*/(NULL));
g(new Box/*.<Z>.*/(NULL));

h(new Box/*.<A>.*/(NULL));
h(new Box/*.<B>.*/(NULL));
h(new Box/*.<C>.*/(NULL));
h(new Box/*.<Z>.*/(NULL));

function assignment_rules() {
	
	/*. Box<?> .*/ $a1 = /*.(Box<?>).*/ NULL; // ok
	/*. Box<?> .*/ $a2 = /*.(Box<? extends object>).*/ NULL; // ok
	/*. Box<?> .*/ $a3 = /*.(Box<? extends B>).*/ NULL; // ok
	/*. Box<?> .*/ $a4 = /*.(Box<? parent object>).*/ NULL; // ok
	/*. Box<?> .*/ $a5 = /*.(Box<? parent B>).*/ NULL; // ok
	/*. Box<?> .*/ $a6 = /*.(Box<object>).*/ NULL; // ok
	/*. Box<?> .*/ $a7 = /*.(Box<A>).*/ NULL; // ok
	/*. Box<?> .*/ $a8 = /*.(Box<B>).*/ NULL; // ok
	/*. Box<?> .*/ $a9 = /*.(Box<C>).*/ NULL; // ok
	/*. Box<?> .*/ $a0 = /*.(Box<Z>).*/ NULL; // ok
	
	/*. Box<? extends object> .*/ $b1 = /*.(Box<?>).*/ NULL; // ok
	/*. Box<? extends object> .*/ $b2 = /*.(Box<? extends object>).*/ NULL; // ok
	/*. Box<? extends object> .*/ $b3 = /*.(Box<? extends B>).*/ NULL; // ok
	/*. Box<? extends object> .*/ $b4 = /*.(Box<? parent object>).*/ NULL; // ok
	/*. Box<? extends object> .*/ $b5 = /*.(Box<? parent B>).*/ NULL; // ok
	/*. Box<? extends object> .*/ $b6 = /*.(Box<object>).*/ NULL; // ok
	/*. Box<? extends object> .*/ $b7 = /*.(Box<A>).*/ NULL; // ok
	/*. Box<? extends object> .*/ $b8 = /*.(Box<B>).*/ NULL; // ok
	/*. Box<? extends object> .*/ $b9 = /*.(Box<C>).*/ NULL; // ok
	/*. Box<? extends object> .*/ $b0 = /*.(Box<Z>).*/ NULL; // ok
	
	/*. Box<? extends B> .*/ $c1 = /*.(Box<?>).*/ NULL; // ERR
	/*. Box<? extends B> .*/ $c2 = /*.(Box<? extends object>).*/ NULL; // ERR
	/*. Box<? extends B> .*/ $c3 = /*.(Box<? extends B>).*/ NULL; // ok
	/*. Box<? extends B> .*/ $c4 = /*.(Box<? parent object>).*/ NULL; // ERR
	/*. Box<? extends B> .*/ $c5 = /*.(Box<? parent B>).*/ NULL; // ERR
	/*. Box<? extends B> .*/ $c6 = /*.(Box<object>).*/ NULL; // ERR
	/*. Box<? extends B> .*/ $c7 = /*.(Box<A>).*/ NULL; // ERR
	/*. Box<? extends B> .*/ $c8 = /*.(Box<B>).*/ NULL; // ok
	/*. Box<? extends B> .*/ $c9 = /*.(Box<C>).*/ NULL; // ok
	/*. Box<? extends B> .*/ $c0 = /*.(Box<Z>).*/ NULL; // ERR
	
	/*. Box<? parent object> .*/ $d1 = /*.(Box<?>).*/ NULL; // ERR
	/*. Box<? parent object> .*/ $d2 = /*.(Box<? extends object>).*/ NULL; // ERR
	/*. Box<? parent object> .*/ $d3 = /*.(Box<? extends B>).*/ NULL; // ERR
	/*. Box<? parent object> .*/ $d4 = /*.(Box<? parent object>).*/ NULL; // ok
	/*. Box<? parent object> .*/ $d5 = /*.(Box<? parent B>).*/ NULL; // ERR
	/*. Box<? parent object> .*/ $d6 = /*.(Box<object>).*/ NULL; // ok
	/*. Box<? parent object> .*/ $d7 = /*.(Box<A>).*/ NULL; // ERR
	/*. Box<? parent object> .*/ $d8 = /*.(Box<B>).*/ NULL; // ERR
	/*. Box<? parent object> .*/ $d9 = /*.(Box<C>).*/ NULL; // ERR
	/*. Box<? parent object> .*/ $d0 = /*.(Box<Z>).*/ NULL; // ERR
	
	/*. Box<? parent B> .*/ $e1 = /*.(Box<?>).*/ NULL; // ERR
	/*. Box<? parent B> .*/ $e2 = /*.(Box<? extends object>).*/ NULL; // ERR
	/*. Box<? parent B> .*/ $e3 = /*.(Box<? extends B>).*/ NULL; // ERR
	/*. Box<? parent B> .*/ $e4 = /*.(Box<? parent object>).*/ NULL; // ok
	/*. Box<? parent B> .*/ $e5 = /*.(Box<? parent B>).*/ NULL; // ok
	/*. Box<? parent B> .*/ $e6 = /*.(Box<object>).*/ NULL; // ok
	/*. Box<? parent B> .*/ $e7 = /*.(Box<A>).*/ NULL; // ok
	/*. Box<? parent B> .*/ $e8 = /*.(Box<B>).*/ NULL; // ok
	/*. Box<? parent B> .*/ $e9 = /*.(Box<C>).*/ NULL; // ERR
	/*. Box<? parent B> .*/ $e0 = /*.(Box<Z>).*/ NULL; // ERR
	
	/*. Box<object> .*/ $f1 = /*.(Box<?>).*/ NULL; // ERR
	/*. Box<object> .*/ $f2 = /*.(Box<? extends object>).*/ NULL; // ERR
	/*. Box<object> .*/ $f3 = /*.(Box<? extends B>).*/ NULL; // ERR
	/*. Box<object> .*/ $f4 = /*.(Box<? parent object>).*/ NULL; // ERR
	/*. Box<object> .*/ $f5 = /*.(Box<? parent B>).*/ NULL; // ERR
	/*. Box<object> .*/ $f6 = /*.(Box<object>).*/ NULL; // ok
	/*. Box<object> .*/ $f7 = /*.(Box<A>).*/ NULL; // ERR
	/*. Box<object> .*/ $f8 = /*.(Box<B>).*/ NULL; // ERR
	/*. Box<object> .*/ $f9 = /*.(Box<C>).*/ NULL; // ERR
	/*. Box<object> .*/ $f0 = /*.(Box<Z>).*/ NULL; // ERR
	
	/*. Box<A> .*/ $g1 = /*.(Box<?>).*/ NULL; // ERR
	/*. Box<A> .*/ $g2 = /*.(Box<? extends object>).*/ NULL; // ERR
	/*. Box<A> .*/ $g3 = /*.(Box<? extends B>).*/ NULL; // ERR
	/*. Box<A> .*/ $g4 = /*.(Box<? parent object>).*/ NULL; // ERR
	/*. Box<A> .*/ $g5 = /*.(Box<? parent B>).*/ NULL; // ERR
	/*. Box<A> .*/ $g6 = /*.(Box<object>).*/ NULL; // ERR
	/*. Box<A> .*/ $g7 = /*.(Box<A>).*/ NULL; // ok
	/*. Box<A> .*/ $g8 = /*.(Box<B>).*/ NULL; // ERR
	/*. Box<A> .*/ $g9 = /*.(Box<C>).*/ NULL; // ERR
	/*. Box<A> .*/ $g0 = /*.(Box<Z>).*/ NULL; // ERR
	
	/*. Box<B> .*/ $h1 = /*.(Box<?>).*/ NULL; // ERR
	/*. Box<B> .*/ $h2 = /*.(Box<? extends object>).*/ NULL; // ERR
	/*. Box<B> .*/ $h3 = /*.(Box<? extends B>).*/ NULL; // ERR
	/*. Box<B> .*/ $h4 = /*.(Box<? parent object>).*/ NULL; // ERR
	/*. Box<B> .*/ $h5 = /*.(Box<? parent B>).*/ NULL; // ERR
	/*. Box<B> .*/ $h6 = /*.(Box<object>).*/ NULL; // ERR
	/*. Box<B> .*/ $h7 = /*.(Box<A>).*/ NULL; // ERR
	/*. Box<B> .*/ $h8 = /*.(Box<B>).*/ NULL; // ok
	/*. Box<B> .*/ $h9 = /*.(Box<C>).*/ NULL; // ERR
	/*. Box<B> .*/ $h0 = /*.(Box<Z>).*/ NULL; // ERR
	
	/*. Box<C> .*/ $i1 = /*.(Box<?>).*/ NULL; // ERR
	/*. Box<C> .*/ $i2 = /*.(Box<? extends object>).*/ NULL; // ERR
	/*. Box<C> .*/ $i3 = /*.(Box<? extends B>).*/ NULL; // ERR
	/*. Box<C> .*/ $i4 = /*.(Box<? parent object>).*/ NULL; // ERR
	/*. Box<C> .*/ $i5 = /*.(Box<? parent B>).*/ NULL; // ERR
	/*. Box<C> .*/ $i6 = /*.(Box<object>).*/ NULL; // ERR
	/*. Box<C> .*/ $i7 = /*.(Box<A>).*/ NULL; // ERR
	/*. Box<C> .*/ $i8 = /*.(Box<B>).*/ NULL; // ERR
	/*. Box<C> .*/ $i9 = /*.(Box<C>).*/ NULL; // ok
	/*. Box<C> .*/ $i0 = /*.(Box<Z>).*/ NULL; // ERR
	
	/*. Box<Z> .*/ $j1 = /*.(Box<?>).*/ NULL; // ERR
	/*. Box<Z> .*/ $j2 = /*.(Box<? extends object>).*/ NULL; // ERR
	/*. Box<Z> .*/ $j3 = /*.(Box<? extends B>).*/ NULL; // ERR
	/*. Box<Z> .*/ $j4 = /*.(Box<? parent object>).*/ NULL; // ERR
	/*. Box<Z> .*/ $j5 = /*.(Box<? parent B>).*/ NULL; // ERR
	/*. Box<Z> .*/ $j6 = /*.(Box<object>).*/ NULL; // ERR
	/*. Box<Z> .*/ $j7 = /*.(Box<A>).*/ NULL; // ERR
	/*. Box<Z> .*/ $j8 = /*.(Box<B>).*/ NULL; // ERR
	/*. Box<Z> .*/ $j9 = /*.(Box<C>).*/ NULL; // ERR
	/*. Box<Z> .*/ $j0 = /*.(Box<Z>).*/ NULL; // ok
	
}
