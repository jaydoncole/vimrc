<?php

// Detect incomplete type guessing from bare null and empty array().

$s1 = null;

$s2 = /*. (string) .*/ null;

$s2 = null; // known type

/*. string .*/ $s3 = null;

/** @var string */ $s4 = null;


$a1 = array();

$a2 = /*. (string[int]) .*/ array();

$a2 = null; // known type

$a2 = array(); // known type

/*. string[int] .*/ $a3 = array();

/** @var string[int] */ $a4 = [];
