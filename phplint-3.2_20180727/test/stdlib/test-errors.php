<?php
require_once __DIR__ . "/../../stdlib/errors.php";

// Expected exception names:
const EE = "ErrorException", IE = "InternalException";

/**
 * Checks if the given chunk of code throws the expected exception.
 * @param string $php_code Chunk of PHP code.
 * @param string $ex Name of the expected exception.
 * @return void
 * @throws RuntimeException
 */
function test($php_code, $ex) {
	if( PHP_MAJOR_VERSION == 5 ){
		try { eval($php_code); }
		catch(Exception $e) {
			if(get_class($e) === $ex )
				return;
			else
				throw new RuntimeException("test failed:"
					. "\ngot: ". get_class($e)
					. "\nexp: $ex"
					. "\ndetails: $e");
		}
	} else if( PHP_MAJOR_VERSION == 7 ){
		try { eval($php_code); }
		catch(Throwable $e) {
			if(get_class($e) === $ex )
				return;
			else
			{
				$e = new RuntimeException("test failed:"
					. "\ngot: ". get_class($e)
					. "\nexp: $ex"
					. "\ndetails: $e");
				echo "===> $e\n";
				return;
			}
//				throw new RuntimeException("test failed:"
//					. "\ngot: ". get_class($e)
//					. "\nexp: $ex"
//					. "\ndetails: $e");
		}
	} else {
		throw new RuntimeException("unsupported PHP version " . PHP_MAJOR_VERSION);
	}
	throw new RuntimeException("test: failed: missing expected exception $ex");
}

//echo (1 / 0); // PHP5.6.3 and PHP7.1: E_WARNING
//echo (1.0 / 0.0); // PHP5.6.3 and PHP7.1: E_WARNING
//echo (1 / 0.0); // PHP5.6.3 and PHP7.1: E_WARNING
//echo (1.0 / 0); // PHP5.6.3 and PHP7.1: E_WARNING
//echo (1 % 0); // PHP5.6.3: E_WARNING; PHP7.1: DivisionByZeroError
//echo (1 % 0.0); // PHP5.6.3: E_WARNING; PHP7.1: DivisionByZeroError
//exit(0);

// unchecked:
test("\$x = 1 / 0;", IE);
test("\$x = 1 / 0.0;", IE);
test("\$x = 1.0 / 0;", IE);
if( PHP_MAJOR_VERSION == 5 ){
	test("\$x = 1 % 0;", IE);
	test("\$x = 1 % 0.0;", IE);
	test("call_user_func('??');", EE);
	test("include '???';", EE);
} else if( PHP_MAJOR_VERSION == 7 ){
	test("\$x = 1 % 0;", "DivisionByZeroError");
	test("\$x = 1 % 0.0;", "DivisionByZeroError");
	test("call_user_func('??');", IE);
	test("include '???';", IE);
} else {
	throw new RuntimeException("unsupported PHP version " . PHP_MAJOR_VERSION);
}

// Next error should never happen on a validated source, but see call_user_func():
if( PHP_MAJOR_VERSION == 5 )
	$exp = IE;
else if( PHP_MAJOR_VERSION == 7 )
	$exp = "ArgumentCountError";
else
	$exp = "ThisTestDoesNotSuppportPhpMajorVersion" . PHP_MAJOR_VERSION;
test("function f2(\$x){} f2();", $exp);


test("\$a=[1,2,3]; echo \$a[999];", IE);
test("\$a=[1,2,3]; echo \$a[fopen('".__FILE__."', 'r')];", IE);
test("\$a=[1,2,3]; echo \$a[array()];", IE);
//test("\$a=NULL; \$a[0];", IE); // PASSES! (there is already a bug open for that marked as feature)
test("\$x = \$undef_var;", IE);
test("\$a=NULL; \$a->p;", IE);
//test("\$a=NULL; \$a->m();", IE);  // FATAL!
//test("require '???';", IE); // FATAL!
test("\$o=new stdClass(); echo \$o;", IE);
test("const M_PI = 0;", IE);
test("\$x = \$unknownvar;", IE); // should never happen on a validated source
test("\$x = \$unknownvar[0];", IE); // should never happen on a validated source
test("\$x = \$_GET[0];", IE);
test("\$s = '' . array();", IE);

// checked:
test("fopen(NULL, 'r');", EE);
test("fopen(array(), 'r');", EE);
test("fopen(3.14, 'r');", EE);
test("fopen('', 'r');", EE);
test("fopen('???', 'r');", EE);
test("unserialize('???');", EE);
test("hex2bin('??');", EE);
test("array_chunk(array(),-1);", EE);