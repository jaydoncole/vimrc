<?php

namespace it\icosaedro\utils;

require_once __DIR__ . "/../../../../../stdlib/all.php";

use it\icosaedro\utils\Date;
use it\icosaedro\utils\TestUnit as TU;

const CN = "it\\icosaedro\\utils\\Date";

class testDate extends TU {
	function run() /*. throws \Exception .*/
	{
		$d = new Date(1583, 1, 1);
		TU::test($d."", "1583-01-01");

		$d = new Date(9999, 12, 31);
		TU::test($d."", "9999-12-31");

		$d = new Date(2012, 2, 29);
		TU::test($d."", "2012-02-29");

		$d = Date::parse("2012-02-29");
		TU::test($d."", "2012-02-29");

		$d = Date::parse("2012-2-29");
		TU::test($d."", "2012-02-29");

		$a = new Date(2012, 02, 29);
		$a2 = new Date(2012, 02, 29);
		$b = new Date(2012, 03, 01);
		TU::test($a->compareTo($b) < 0, TRUE);
		TU::test($b->compareTo($a) > 0, TRUE);
		TU::test($a->compareTo($a) == 0, TRUE);
		TU::test($a->equals($a), TRUE);
		TU::test($a->equals($b), FALSE);
		TU::test($b->equals($b), TRUE);
		TU::test($a->equals($a2), TRUE);
		TU::test($a2->equals($a), TRUE);

		TU::test( cast(CN, unserialize( serialize($a) ) )->equals($a), TRUE);
		TU::test( cast(CN, unserialize( serialize($b) ) )->equals($b), TRUE);
		TU::test( cast(CN, unserialize( serialize($a2) ) )->equals($a), TRUE);
		
		// testing dayOfWeek():
		$d = new Date(2000, 1, 1);
		TU::test($d->dayOfWeek(), 5);
		$d = new Date(2000, 1, 2);
		TU::test($d->dayOfWeek(), 6);
		$d = new Date(2000, 1, 3);
		TU::test($d->dayOfWeek(), 0);
		$d = new Date(2000, 1, 4);
		TU::test($d->dayOfWeek(), 1);
		$d = new Date(1999, 12, 31);
		TU::test($d->dayOfWeek(), 4);
		$d = new Date(1999, 12, 25);
		TU::test($d->dayOfWeek(), 5);
		$d = new Date(1999, 12, 24);
		TU::test($d->dayOfWeek(), 4);
		$d = new Date(1999, 12, 23);
		TU::test($d->dayOfWeek(), 3);
		$d = new Date(1966, 03, 28);
		TU::test($d->dayOfWeek(), 0);
		$d = new Date(1800, 01, 01); // Wednesday according to wikipedia
		TU::test($d->dayOfWeek(), 2);
		$d = new Date(2017, 01, 04);
		TU::test($d->dayOfWeek(), 2);
		
		// testing addDays() by comparison with daysSince():
		$d = new Date(2017,1,7);
		TU::test($d->daysSince($d), 0);
		TU::test($d->addDays(0)->compareTo($d), 0);
		for($days = - 2*365; $days < 2*365; $days += 3){
			$x = $d->addDays($days);
			TU::test($x->daysSince($d), $days);
		}
	}
}
$tu = new testDate();
$tu->start();
