<?php

require_once __DIR__ . "/../../../../../stdlib/all.php";

/*.
	require_module 'standard';
	require_module 'pcre';
.*/

use it\icosaedro\containers\FloatClass;
use it\icosaedro\utils\TestUnit as TU;


/**
 * 
 * @param float $f  Float number to test with.
 * @param string $exp_hex Expected hex representation.
 */
function test($f, $exp_hex) {
	// check float to hex string conversion:
	$got_hex = FloatClass::formatHex($f);
	TU::test($got_hex, $exp_hex);
	// check reverse:
	$f2 = FloatClass::parse($got_hex);
	TU::test($f2, $f);
}


test(NAN, "NAN");
test(INF, "INF");
test(-INF, "-INF");
test(0.0, "0x0p0");
// -0.0 = zero (PHP5.6), zero negative (PHP7.1)
// -1.0*0.0 = zero negative with both versions:
test(-1.0*0.0, "-0x0p0");
test(-128.0, "-0x8p4");
test(128.0, "0x8p4");
test(1.0, "0x8p-3");
test(0.0625, "0x8p-7");
test(1.0625, "0x8.8p-3");
//test(0.125, "0x1p-3");
test((float)65535, "0xf.fffp12");
test(0.1, "0xc.cccccccccccdp-7");
test(0.01, "0xa.3d70a3d70a3d8p-10");
test(0.001, "0x8.3126e978d4fep-13");
test(1.2345e20, "0xd.626d01de23f2p63");
test(1.2345e-20, "0xe.930c1c8f451c8p-70");




$zn = -1 * 0.0; // yields the zero negative -0.0

TU::test( FloatClass::compare(-INF, -INF) == 0, true );
TU::test( FloatClass::compare(-INF, $zn) < 0, true );
TU::test( FloatClass::compare(-INF, 0.0) < 0, true );
TU::test( FloatClass::compare(-INF, 1.0) < 0, true );
TU::test( FloatClass::compare(-INF, NAN) < 0, true );
TU::test( FloatClass::compare($zn, -INF) > 0, true );
TU::test( FloatClass::compare($zn, $zn) == 0, true );
TU::test( FloatClass::compare($zn, 0.0) < 0, true );
TU::test( FloatClass::compare($zn, 1.0) < 0, true );
TU::test( FloatClass::compare($zn, NAN) < 0, true );
TU::test( FloatClass::compare(0.0, -INF) > 0, true );
TU::test( FloatClass::compare(0.0, $zn) > 0, true );
TU::test( FloatClass::compare(0.0, 0.0) == 0, true );
TU::test( FloatClass::compare(0.0, 1.0) < 0, true );
TU::test( FloatClass::compare(0.0, NAN) < 0, true );
TU::test( FloatClass::compare(1.0, -INF) > 0, true );
TU::test( FloatClass::compare(1.0, $zn) > 0, true );
TU::test( FloatClass::compare(1.0, 0.0) > 0, true );
TU::test( FloatClass::compare(1.0, 1.0) == 0, true );
TU::test( FloatClass::compare(1.0, NAN) < 0, true );
TU::test( FloatClass::compare(NAN, -INF) > 0, true );
TU::test( FloatClass::compare(NAN, $zn) > 0, true );
TU::test( FloatClass::compare(NAN, 0.0) > 0, true );
TU::test( FloatClass::compare(NAN, 1.0) > 0, true );
TU::test( FloatClass::compare(NAN, NAN) == 0, true );

