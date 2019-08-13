<?php

namespace it\icosaedro\utils;

require_once __DIR__ . "/../../../../../stdlib/all.php";

use it\icosaedro\utils\DateTimeTZ as DTZ;
use it\icosaedro\utils\TestUnit as TU;
use OverflowException;

$dtz = new DTZ(new DateTime(new Date(2018, 4, 8), 21, 15), 2*60 + 1);

// date -d"2018-04-08T21:15+02:01" +%s --> 1523214840
TU::test($dtz->getTimestamp(), 1523214840);
TU::test("".DTZ::fromTimestamp(1523214840)->toTZ(121), "2018-04-08T21:15:00.000+02:01");

// date -d"1950-04-08T21:15+02:01" +%s --> 1523214840
TU::test(DTZ::parse("1950-04-08T21:15+02:01")->getTimestamp(), -622701960);
TU::test("".DTZ::fromTimestamp(-622701960)->toTZ(121), "1950-04-08T21:15:00.000+02:01");

TU::test(DTZ::parse("2018-04-08T21:15:00.000+02:01")->equals($dtz), TRUE);
TU::test($dtz->compareTo(DTZ::parse("1970-01-01T00:00:00.000+00:00")) > 0, TRUE);
TU::test("".$dtz, "2018-04-08T21:15:00.000+02:01");
TU::test("".$dtz->toTZ(0), "2018-04-08T19:14:00.000+00:00");
TU::test("".$dtz->toTZ(10*60), "2018-04-09T05:14:00.000+10:00");


//$now = DTZ::now();
//echo "Locale:   ", $now, "\n";
//echo "Zulu:     ", $now->toTZ(0), "\n";
//echo "Canberra: ", $now->toTZ(10*60), "\n";


TU::test("".DTZ::fromTimestamp(  0), "1970-01-01T00:00:00.000+00:00");
TU::test("".DTZ::fromTimestamp(  1), "1970-01-01T00:00:01.000+00:00");
TU::test("".DTZ::fromTimestamp( 59), "1970-01-01T00:00:59.000+00:00");
TU::test("".DTZ::fromTimestamp( 60), "1970-01-01T00:01:00.000+00:00");
TU::test("".DTZ::fromTimestamp( 61), "1970-01-01T00:01:01.000+00:00");
TU::test("".DTZ::fromTimestamp( -1), "1969-12-31T23:59:59.000+00:00");
TU::test("".DTZ::fromTimestamp(-59), "1969-12-31T23:59:01.000+00:00");
TU::test("".DTZ::fromTimestamp(-60), "1969-12-31T23:59:00.000+00:00");
TU::test("".DTZ::fromTimestamp(-61), "1969-12-31T23:58:59.000+00:00");

// Check limits on 32-bits systems:
if( PHP_INT_SIZE == 4 ){
	TU::test("".DTZ::fromTimestamp(PHP_INT_MIN), "1901-12-13T20:45:52.000+00:00");
	TU::test("".DTZ::fromTimestamp(PHP_INT_MAX), "2038-01-19T03:14:07.000+00:00");
	
	$failed = FALSE;
	try {
		/* ignore = */ DTZ::parse("1901-12-13T20:45:51+00:00")->getTimestamp();
	} catch (OverflowException $ex) {
		$failed = TRUE;
	}
	TU::test($failed, TRUE);
	
	$failed = FALSE;
	try {
		/* ignore = */ DTZ::parse("2038-01-19T03:14:08+00:00")->getTimestamp();
	} catch (OverflowException $ex) {
		$failed = TRUE;
	}
	TU::test($failed, TRUE);
}

// Several brute force consistency tests on a range of timestamps:
for($ts = -2000000000; $ts <= 2000000000; $ts += 17 * 86400 + 12345){
	$dtz = DTZ::fromTimestamp($ts);
	TU::test($dtz->getTimestamp(), $ts); // ts to date back and forth
	TU::test(DTZ::parse("$dtz"), $dtz);  // parsing back and forth
}