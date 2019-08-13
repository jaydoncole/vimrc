<?php

namespace it\icosaedro\utils;

require_once __DIR__ . "/../../../../../stdlib/all.php";

use it\icosaedro\utils\DateTime as DT;
use it\icosaedro\utils\TestUnit as TU;

// addTime() bug test:
$d = "2000-01-01T00:00:00.000";
TU::test("".DT::parse($d)->addTime(0, 0,      0), "2000-01-01T00:00:00.000");
TU::test("".DT::parse($d)->addTime(0, 0,     -1), "1999-12-31T23:59:59.999");
TU::test("".DT::parse($d)->addTime(0, 0, -59999), "1999-12-31T23:59:00.001");
TU::test("".DT::parse($d)->addTime(0, 0, -60000), "1999-12-31T23:59:00.000");
TU::test("".DT::parse($d)->addTime(0, 0, -60001), "1999-12-31T23:58:59.999");
TU::test("".DT::parse($d)->addTime(0, 0,      1), "2000-01-01T00:00:00.001");
TU::test("".DT::parse($d)->addTime(0, 0,  59999), "2000-01-01T00:00:59.999");
TU::test("".DT::parse($d)->addTime(0, 0,  60000), "2000-01-01T00:01:00.000");
TU::test("".DT::parse($d)->addTime(0, 0,  60001), "2000-01-01T00:01:00.001");

TU::test("".DT::parse($d)->addTime(0,   -1), "1999-12-31T23:59:00.000");
TU::test("".DT::parse($d)->addTime(0,  -59), "1999-12-31T23:01:00.000");
TU::test("".DT::parse($d)->addTime(0,  -60), "1999-12-31T23:00:00.000");
TU::test("".DT::parse($d)->addTime(0,  -61), "1999-12-31T22:59:00.000");
TU::test("".DT::parse($d)->addTime(0, -119), "1999-12-31T22:01:00.000");
TU::test("".DT::parse($d)->addTime(0, -120), "1999-12-31T22:00:00.000");
TU::test("".DT::parse($d)->addTime(0, -121), "1999-12-31T21:59:00.000");

TU::test("".DT::parse($d)->addTime(-1,   0), "1999-12-31T23:00:00.000");
TU::test("".DT::parse($d)->addTime(-23,  0), "1999-12-31T01:00:00.000");
TU::test("".DT::parse($d)->addTime(-24,  0), "1999-12-31T00:00:00.000");
TU::test("".DT::parse($d)->addTime(-25,  0), "1999-12-30T23:00:00.000");
