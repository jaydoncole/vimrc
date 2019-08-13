<?php

// Ternary operator is left-associative, that is
//     A?B:C?D:E
// is evaluated as
//     (A?B:C)?D:E
// Use flow analysis to detect which case holds:
if ((true ? false : true ? 1 : 2) == 2)
	$gotTrue = 0;
else
	$gotFalse = 0;
echo $gotTrue, $gotFalse;
// Possible results:
// $gotTrue undefined: expression evaluated FALSE
// $gotFalse undefined: expression evaluated TRUE
// bot undefined: expression can't be statically evaluated
// Expected result: expression evaluates to TRUE, then $gotFalse undef