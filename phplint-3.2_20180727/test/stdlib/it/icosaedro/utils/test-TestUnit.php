<?php

require_once __DIR__ . "/../../../../../stdlib/all.php";

use it\icosaedro\utils\TestUnit as TU;


function maxRecursionOnArrays()
{
	$a = [[[[[[[[[[1]]]]]]]]]];
	TU::test(TU::dump($a, 10, 0), "array(...)");
	TU::test(TU::dump($a, 10, 1), "array(0=>array(...))");
}


class MyClass{
	/** @var MyClass */
	public $p;
}


function maxRecursionOnObjects()
{
	$o = new MyClass();
	for($i = 5; $i > 0; $i--)
		$o = ($o->p = $o);
	TU::test(TU::dump($o, 10, 0), "MyClass{...}");
	TU::test(TU::dump($o, 10, 1), "MyClass{\$p=MyClass{...};}");
}


function main()
{
	TU::test(TU::dump(NULL), "NULL");
	TU::test(TU::dump(FALSE), "FALSE");
	TU::test(TU::dump(TRUE), "TRUE");
	TU::test(TU::dump(0), "0");
	TU::test(TU::dump(-1), "-1");
	TU::test(TU::dump(0.0), "0.0");
	TU::test(TU::dump(1E99), "1.0E+99");
	TU::test(TU::dump(NAN), "NAN");
	TU::test(TU::dump(INF), "INF");
	TU::test(TU::dump(-INF), "-INF");
	TU::test(TU::dump(0.01 * (-1) * 0), "-0.0");
	maxRecursionOnArrays();
	maxRecursionOnObjects();
}

main();
