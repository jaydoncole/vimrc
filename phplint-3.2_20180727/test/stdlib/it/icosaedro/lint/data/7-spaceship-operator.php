<?php

if( 1 <=> 2 );

if( 0 <=> 3.14 );

if( "a" <=> "b" );

/*. resource .*/ function f(){ return NULL; }
if( array() <=> f() );

