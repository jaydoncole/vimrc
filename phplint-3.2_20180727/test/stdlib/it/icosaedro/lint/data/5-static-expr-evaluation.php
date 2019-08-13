<?php

// Since PHP 5.6.0, simple evaluation is allowed in static expressions:
// const, default value of formal parameter, case switch, class const.
// See http://php.net/manual/en/language.oop5.constants.php

/*. require_module 'core'; .*/
error_reporting(-1);

// Example #3 from the manual:
const ONE = 1;

class foo {
    // As of PHP 5.6.0
    const TWO = ONE * 2;
    const THREE = ONE + self::TWO;
    const SENTENCE = 'The value of THREE is '.self::THREE;
}


# Checking parsed values. Trick: if the bool expr is statically evaluated as TRUE
# as it should, then the following variable is definitely assigned and the echo
# command does not complain.
if( foo::TWO === 2 )
	$test1 = 1;
echo $test1;

if( foo::THREE === 3 )
	$test2 = 2;
echo $test2;

if( foo::SENTENCE === 'The value of THREE is 3' )
	$test3 = 3;
echo $test3;


# Check some more operator:

const C1 = (TRUE and TRUE or FALSE xor TRUE) || FALSE && TRUE; // TRUE
if( C1 )
	$test4 = 4;
echo $test4;

const C2 = (1 & 3) + (4 | 8) + (1 << 4) + (1024 >> 10); // 30
if( C2 === 30 )
	$test5 = 5;
echo $test5;

const C3 = 6/2 - 3; // 3.0
// FIXME: actually PHP evaluates int(3) here, so the following comparisong is
// true for PHPLint, but false for PHP:
//if( C3 === 0.0 ) ...
// To run this source without error, need to use weak comparison:
if( C3 == 0.0 )
	$test6 = 6;
echo $test6;

# Check static expr in several contexts:

switch(1){
	case 1&3: break;
	default:
}

function f($x = 1 <= 2){ echo $x; }
f();

