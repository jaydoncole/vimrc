<?php

class C { public $p = 1; }

function f(C $c = NULL) {
	$o = $c ?? new C();
	echo ($c ?? new C())->p;
}

if( 1 ?? 2 );
if( 1 ?? 2.3 );
if( array() ?? array() );
if( array(1) ?? array("a") );

$name = $_GET['name'] ?? 'name';