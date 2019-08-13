<?php
/** GMP Functions.

See: {@link http://www.php.net/manual/en/ref.gmp.php}
@package gmp
*/

/*. require_module 'spl'; .*/

# All these constants are dummy values
define('GMP_ROUND_ZERO', 1);
define('GMP_ROUND_PLUSINF', 1);
define('GMP_ROUND_MINUSINF', 1);
define('GMP_VERSION', '0');

final class GMP implements Serializable {
	private function __construct(){}
	/*. string .*/ function serialize(){}
	/*. void .*/ function unserialize(/*. string .*/ $serialized){}
}


/*. GMP .*/ function gmp_abs(/*. GMP .*/ $a){}
/*. GMP .*/ function gmp_add(/*. GMP .*/ $a, /*. GMP .*/ $b){}
/*. GMP .*/ function gmp_and(/*. GMP .*/ $a, /*. GMP .*/ $b){}
/*. void .*/ function gmp_clrbit(/*. GMP .*/ &$a, /*. int .*/ $index){}
/*. int .*/ function gmp_cmp(/*. GMP .*/ $a, /*. GMP .*/ $b){}
/*. GMP .*/ function gmp_com(/*. GMP .*/ $a){}
/*. GMP .*/ function gmp_div_q(/*. GMP .*/ $a, /*. GMP .*/ $b, $round = GMP_ROUND_ZERO ){}
/*. float[int] .*/ function gmp_div_qr(/*. GMP .*/ $a, /*. GMP .*/ $b, $round = GMP_ROUND_ZERO){}
/*. GMP .*/ function gmp_div_r(/*. GMP .*/ $a, /*. GMP .*/ $b, $round = GMP_ROUND_ZERO){}
/*. GMP .*/ function gmp_div(/*. GMP .*/ $a, /*. GMP .*/ $b, $round = GMP_ROUND_ZERO ){}
/*. GMP .*/ function gmp_divexact(/*. GMP .*/ $a, /*. GMP .*/ $b){}
/*. string .*/ function gmp_export(/*. GMP .*/ $gmpnumber, /*. int .*/ $word_size, /*. int .*/ $options){}
/*. GMP .*/ function gmp_fact(/*. mixed .*/ $a){}
/*. GMP .*/ function gmp_gcd(/*. GMP .*/ $a, /*. GMP .*/ $b){}
/*. GMP[string] .*/ function gmp_gcdext(/*. GMP .*/ $a, /*. GMP .*/ $b){}
/*. int .*/ function gmp_hamdist(/*. GMP .*/ $a, /*. GMP .*/ $b){}
/*. GMP .*/ function gmp_import(/*. string .*/ $data, /*. int .*/ $word_size, /*. int .*/ $options)
		/*. triggers E_WARNING .*/{}
/*. GMP .*/ function gmp_init(/*. mixed .*/ $number_, $base=0){}
/*. int .*/ function gmp_intval(/*. GMP .*/ $gmpnumber){}
/*. GMP .*/ function gmp_invert(/*. GMP .*/ $a, /*. GMP .*/ $b){}
/*. int .*/ function gmp_jacobi(/*. GMP .*/ $a, /*. GMP .*/ $b){}
/*. int .*/ function gmp_legendre(/*. GMP .*/ $a, /*. GMP .*/ $b){}
/*. GMP .*/ function gmp_mod(/*. GMP .*/ $a, /*. GMP .*/ $b){}
/*. GMP .*/ function gmp_mul(/*. GMP .*/ $a, /*. GMP .*/ $b){}
/*. GMP .*/ function gmp_neg(/*. GMP .*/ $a){}
/*. GMP .*/ function gmp_nextprime(/*. int .*/ $a){}
/*. GMP .*/ function gmp_or(/*. GMP .*/ $a, /*. GMP .*/ $b){}
/*. bool .*/ function gmp_perfect_square(/*. GMP .*/ $a){}
/*. int .*/ function gmp_popcount(/*. GMP .*/ $a){}
/*. GMP .*/ function gmp_pow(/*. GMP .*/ $base, /*. int .*/ $exp){}
/*. GMP .*/ function gmp_powm(/*. GMP .*/ $base, /*. GMP .*/ $exp, /*. GMP .*/ $mod){}
/*. int .*/ function gmp_prob_prime(/*. GMP .*/ $a /*., args .*/){}
/*. GMP .*/ function gmp_random_bits(/*. int .*/ $bits){}
/*. GMP .*/ function gmp_random_range(/*. GMP .*/ $min, /*. GMP .*/ $max){}
/*. if_php_ver_7 .*/
/*. mixed .*/ function gmp_random_seed(/*. mixed .*/ $seed){}
/*. end_if_php_ver .*/
/*. GMP .*/ function gmp_random( /*. args .*/){}
/*. GMP .*/ function gmp_root(/*. GMP .*/ $a, /*. int .*/ $nth){}
/*. GMP[int] .*/ function gmp_rootrem(/*. GMP .*/ $a, /*. int .*/ $nth){}
/*. int .*/ function gmp_scan0(/*. GMP .*/ $a, /*. int .*/ $start){}
/*. int .*/ function gmp_scan1(/*. GMP .*/ $a, /*. int .*/ $start){}
/*. void .*/ function gmp_setbit(/*. GMP .*/ &$a, /*. int .*/ $index /*., args .*/){}
/*. int .*/ function gmp_sign(/*. GMP .*/ $a){}
/*. GMP .*/ function gmp_sqrt(/*. GMP .*/ $a){}
/*. array .*/ function gmp_sqrtrem(/*. GMP .*/ $a){}
/*. string .*/ function gmp_strval(/*. GMP .*/ $gmpnumber /*., args .*/){}
/*. GMP .*/ function gmp_sub(/*. GMP .*/ $a, /*. GMP .*/ $b){}
/*. bool .*/ function gmp_testbit(/*. GMP .*/ $a, /*. int .*/ $index){}
/*. GMP .*/ function gmp_xor(/*. GMP .*/ $a, /*. GMP .*/ $b){}

