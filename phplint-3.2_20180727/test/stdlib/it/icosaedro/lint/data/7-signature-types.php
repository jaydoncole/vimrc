<?php

/*. require_module 'core'; .*/

declare(ticks=3);
declare(encoding="xxx");
declare(strict_types=0);

error_reporting(-1);

class MyClass {}

// old type aliases are still here in PHP 7, but not allowed in scalar type decls!
echo (integer) 88.8;
echo (double) 1;
echo (real) 1;

/**
 * @param bool $b
 * @param int $i
 * @param float $f
 * @param string $s
 * @param MyClass $o
 * @return double
 */
function f(bool $b, int $i, float $f, string $s, MyClass $o): float
{
	return 3;
}

var_dump(f(FALSE, 123, 1.2, "abc", new MyClass()));
