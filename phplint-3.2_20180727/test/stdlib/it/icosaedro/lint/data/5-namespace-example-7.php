<?php
# Accessing global classes, functions and constants from within a namespace
/*. require_module 'core'; .*/
namespace Foo;

/*. string .*/ function strlen(/*. string .*/ $s) { return $s; }
const E_NOTICE = "my new constant"; // note it is string rather than int on purpose, so following err msgs differs
class Exception {}

if( \strlen('hi') ); // calls global function strlen
if( \E_NOTICE ); // accesses global constant E_NOTICE
if( new \Exception('error') ); // instantiates global class Exception

if( strlen('xx') );
if( E_NOTICE );
if( new Exception() );
