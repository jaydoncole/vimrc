<?php

list($x,$y) = array();

// $x,$y are now assigned, but of unknown type:
if($x && $y);

// Assigning non-array:
list($x,$y) = 1;

// Assigning unknown:
list($x,) = xxxx;

