<?php /*. require_module 'core'; require_module 'spl'; .*/

class C1 {
	/*. int .*/ function m1(/*. string .*/ $s){ return 123; }
}

interface IF1 {
	/*. string .*/ function m2(/*. int .*/ $i);
}

class X /*. <A extends C1, B extends C1 & IF1, C> .*/
{
	/*. int .*/ function m3(){ return 456; }
}

//if(new X()); // FIXME: fatal because X's parameters have boundaries

class C2 extends C1 {}
class C3 extends C1 implements IF1 {
	/*. string .*/ function m2(/*. int .*/ $i){ return "$i"; }
}
class C4 {
	/*. int .*/ function m4(){ return 789; }
}

$o = new X/*.<C2,C3,C4>.*/();
if($o);

if(new X/*.<object,object,object>.*/);


class Pair/*.<T>.*/
{
	/**
	 * @var T
	 */
	private $first, $second;
	
	public function __construct(/*. T .*/ $first, /*. T .*/ $second) {
		$this->first = $first;
		$this->second = $second;
	}
	
	public /*. T .*/ function getFirst() { return $this->first; }
	
	/**
	 * @return T
	 */
	public function getSecond() { return $this->second; }
	
	public function setFirst(/*. T .*/ $newValue) { $this->first = $newValue; }
	
	/**
	 * @param T $newValue
	 */
	public function setSecond($newValue) { $this->second = $newValue; }
}

$p = new Pair/*.<C1>.*/(new C1(), new C2);
if($p);

$q = new Pair/*.<C1>.*/(new C1(), new C1);
if($q);
$p = $q;
$q = $p;

if($q->first);

if($q->getFirst());

// Primitive types not allowed as actual type param:
new Pair/*.<int>.*/();
/** @return Pair<float> */ function f(/*. Pair<string> .*/ $p1){}

//----------------------------------------------------------------------------

/** Exception as boundary: */
class MyException extends Exception {
	public $realCause = "";
	function setRealCause(/*. string .*/ $realCause){
		$this->realCause = $realCause;
	}
	function getRealCause() {
		return $this->realCause;
	}
}

class C5/*.<T extends MyException>.*/ {
	
	function m1(/*. T .*/ $e) /*. throws T .*/ {
		$e->setRealCause("cannot do m1");
		throw $e;
	}
	
	/**
	 * @param T $e
	 * @throws T
	 */
	function m2($e) {
		$e->setRealCause("cannot do m2");
		throw $e;
	}
	
}

$o5 = new C5 /*.<MyException>.*/();
try {
	$o5->m1(new MyException("failed doing m1"));
}
catch(MyException $e){
	echo $e->realCause;
}

//---------------------------------------------------------------------------
// Template generating instances of type param (POC).

abstract class SelfCreator/*.<T>.*/ {
	function __construct(){} // just to check parent ctor call in extended class
	public abstract /*. T .*/ function create();
	public /*. T[int] .*/ function create10(){
		/*. T[int] .*/ $a = array();
		for($i = 0; $i < 10; $i++)
			$a[] = $this->create();
		return $a;
	}
}

class C6 { public $p6 = 0; }
class C7 { public $p7 = 0; }

class SelfCreator6 extends SelfCreator/*.<C6>.*/ {
	function __construct(){} // missing call to parent ctor
	public /*. C6 .*/ function create()
		{ $o = new C6(); $o->p6 = 666; return $o; }
}

class SelfCreator7 extends SelfCreator/*.<C7>.*/ {
	function __construct(){ parent::__construct(); }
	public /*. C7 .*/ function create()
		{ $o = new C7(); $o->p7 = 666; return $o; }
}

$o6 = new SelfCreator6(); // --> C6[int]
if($o6->create10());

$o7 = new SelfCreator7(); // --> C7[int]
if($o7->create10());

//---------------------------------------------------------------------------
// Inheritance rules.

class C8/*.<T>.*/ {}

class C9/*.<T>.*/ extends C8/*.<T>.*/ {}

class C10 {}
class C11 {}

/*. C8<C10> .*/ $o8 = new C9/*.<C10>.*/(); // ok
$o8 = new C9/*.<C11>.*/(); // not subclass

class C12 extends C9/*.<C10>.*/ {}
class C13 extends C9/*.<C11>.*/ {}
$o8 = new C12(); // ok
$o8 = new C13(); // not subclass

//---------------------------------------------------------------------------
// Dependency from different actualizations of the same interface.
// Not detected, but there is no way to implements both without error.

class C14 {}
class C15 {}
interface IF16/*.<T>.*/ { /*. T .*/ function m(/*. T .*/ $t); }
class C16 implements IF16/*.<C14>.*/, IF16/*.<C15>.*/ {
	/*. C14 .*/ function m(/*. C14 .*/ $t){ return NULL; } // not compatible with IF16<C15>::m
//	/*. C15 .*/ function m(/*. C15 .*/ $t){ return NULL; } // not compatible with IF16<C14>::m
}

//---------------------------------------------------------------------------
// Template accounting.
/** @access private */
class C17 {} // used
/** @access private */
class C18/*.<T>.*/ {} // unused
/** @access private */
class C19/*.<T>.*/ {}  // used
new C19/*.<C17>.*/();

//---------------------------------------------------------------------------
// Instanceof: type params not allowed:
class C20/*.<T>.*/{}
if( $p instanceof C20/*.<xxx>.*/); // FATAL
