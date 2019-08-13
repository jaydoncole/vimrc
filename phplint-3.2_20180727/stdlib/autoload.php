<?php

/**
	Class autoloading package. This package provides class autoloading, and also
    makes aware PHPLint that class
	autoloading is enabled and how to fetch the required classes. The PHPLint
	reference manual explains in more details how the class autoloading
	mechanism works and when class autoloading is triggered by PHPLint and by
	PHP.

	This file should be located in the base directory of the libraries tree. In
	turn, every library should then import this package using something like:
	<pre>require_once __DIR__ . "/../../../autoload.php";</pre> Note that we
	<b>must</b> use the special constant __DIR__ because at runtime the current
	working directory is set by the client package (that is, the original PHP
	program that was started first) and might not be the directory of the
	library itself.

	Another feature of this package is that it verify that the correct
	PHP configuration file be loaded. The php.ini file MUST be located
	in the same directory of this package. If not found there, the
	program exits with die().

	@package autoload.php
	@author Umberto Salsi <salsi@icosaedro.it>
	@version $Date: 2018/04/14 12:40:27 $
*/

/*.
	require_module 'core';
	require_module 'phpinfo';
	require_module 'spl';
.*/

require_once __DIR__ . "/AutoloadException.php";

/**
	Base directory of all the libraries, that is the directory of this package.
	Actually, the value is set dynamically at runtime, so there is no need to
	set this for every specific environment. Client packages may then use this
	constant to load other packages from the library that provide only
	constants and functions or classes that does not require the autoload
	mechanism:
	<pre>
	require_once __DIR__ . "/../../../autoload.php";
	require_once SRC_BASE_DIR . "/errors.php";
	require_once SRC_BASE_DIR . "/it/icosaedro/bignumbers/BigFloat.php";
	require_once SRC_BASE_DIR . "/com/acme/web/SessionHandlingFuncs.php";
	require_once SRC_BASE_DIR . "/com/acme/db/OurDBConnectionParams.php";
	</pre>
*/
const SRC_BASE_DIR = __DIR__;


/**
	Performs class autoloading. Uses 'schema1' and search for the
	class in the same directory where this package resides,
	adding ".php" at the end of the string.
	This function is intended to be called automatically by the PHP
	interpreter, and should never be called explicitly from user code.
	@param string $className  Fully qualified class name.
	@return void
	@throws AutoloadException  If the class file cannot be found or
	cannot be read.
*/
function phplint_autoload($className)
{
	# The 'autoload' pragma explains to PHPLint how to resolve classes
	# absolute names into absolute file names:
	/*. pragma
		'autoload'
		'schema1'  # currently, the only value allowed here
		'.'        # dir of the classes; if relative, use the dir of this file
		'/'        # maps abs class names path sparator \ to this string
		'.php';    # append this string
	.*/

	# Actual PHP code that performs absolute class name resolution
	# into absolute file name.
	# Note: at runtime, the CWD here is that of the PHP file from which
	# autoload was triggered, that's why I use SRC_BASE_DIR instead of __DIR__:
	$fn = SRC_BASE_DIR . "/"
		. (string) str_replace("\\", "/", $className) . ".php";
	if( ! is_readable($fn) )
		throw new AutoloadException("failed loading class $className: file $fn does not exist or it is not readable");

	# Uncomment this to debug path problems at runtime:
	#echo "[autoload($className) => $fn]";

	require_once $fn;
}

spl_autoload_register("phplint_autoload", TRUE, TRUE);


/**
 * Returns the number of elements in the array. At least up to PHP 7.1
 * count(NULL) gives 0, but it triggers E_WARNING as of PHP 7.2. Most of
 * the PHPLint stdlib relies on the first behavior in order to save some memory
 * space for empty arrays, so the need for this replacement.
 * @param array $a
 * @return int
 */
function phplint_count($a)
{
	return $a === NULL? 0 : count($a);
}
