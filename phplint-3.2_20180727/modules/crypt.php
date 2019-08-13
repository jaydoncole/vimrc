<?php

/*
 * Cryptografic functions.
 */


define('CRYPT_BLOWFISH', 1);
define('CRYPT_EXT_DES', 1);
define('CRYPT_MD5', 1);
define('CRYPT_SALT_LENGTH', 123);
define('CRYPT_SHA256', 1);
define('CRYPT_SHA512', 1);
define('CRYPT_STD_DES', 1);


/*. string.*/ function crypt(/*. string .*/ $str, $salt = ""){}