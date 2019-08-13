<?php

define("CA1", [1,2,3]);

const
	CA2 = [1,2,3],
	CA3 = [[9]],
	HEX = "0123456789abcdef";

echo CA1[0];
echo CA2[0];
echo CA3[0][0];
echo HEX[15];

class C1 {
	const CA4 = [1,2,3], CA5 = [[99]];
	
	static function m() { echo self::CA4[2], self::CA5[0][0]; }
}

echo C1::CA4[0], C1::CA5[0][0];
C1::m();
