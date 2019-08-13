<?php

/*
 * PHP Options/Info Functions
 */

/*. require_module 'core'; .*/

const

// See assert():
ASSERT_ACTIVE = 1,
ASSERT_BAIL = 3,
ASSERT_CALLBACK = 2,
ASSERT_EXCEPTION = 6,
ASSERT_QUIET_EVAL = 5,
ASSERT_WARNING = 4,

// See phpcredits():
CREDITS_ALL = -1,
CREDITS_DOCS = 16,
CREDITS_FULLPAGE = 32,
CREDITS_GENERAL = 2,
CREDITS_GROUP = 1,
CREDITS_MODULES = 8,
CREDITS_QA = 64,
CREDITS_SAPI = 4,

// See phpinfo():
INFO_ALL = -1,
INFO_CONFIGURATION = 4,
INFO_CREDITS = 2,
INFO_ENVIRONMENT = 16,
INFO_GENERAL = 1,
INFO_LICENSE = 64,
INFO_MODULES = 8,
INFO_VARIABLES = 32,

// See INI constants:
INI_ALL = 7,
INI_PERDIR = 2,
INI_SYSTEM = 4,
INI_SCANNER_NORMAL = 1,
INI_SCANNER_RAW = 1,
INI_SCANNER_TYPED = 2,
INI_USER = 1;

/** @deprecated This constant available only on Windows. */
const PHP_WINDOWS_NT_DOMAIN_CONTROLLER = 1;
/** @deprecated This constant available only on Windows. */
const PHP_WINDOWS_NT_SERVER = 1;
/** @deprecated This constant available only on Windows. */
const PHP_WINDOWS_NT_WORKSTATION = 1;
/** @deprecated This constant available only on Windows. */
const PHP_WINDOWS_VERSION_BUILD = 1;
/** @deprecated This constant available only on Windows. */
const PHP_WINDOWS_VERSION_MAJOR = 1;
/** @deprecated This constant available only on Windows. */
const PHP_WINDOWS_VERSION_MINOR = 1;
/** @deprecated This constant available only on Windows. */
const PHP_WINDOWS_VERSION_PLATFORM = 1;
/** @deprecated This constant available only on Windows. */
const PHP_WINDOWS_VERSION_PRODUCTTYPE = 1;
/** @deprecated This constant available only on Windows. */
const PHP_WINDOWS_VERSION_SP_MAJOR = 1;
/** @deprecated This constant available only on Windows. */
const PHP_WINDOWS_VERSION_SP_MINOR = 1;
/** @deprecated This constant available only on Windows. */
const PHP_WINDOWS_VERSION_SUITEMASK = 1;

/*. mixed .*/ function assert_options(/*. int .*/ $what, /*. mixed .*/ $value = NULL){}

/*. if_php_ver_5 .*/
	/*. boolean .*/ function assert(/*. mixed .*/ $assertion, /*. string .*/ $description = NULL){}
/*. end_if_php_ver .*/

/*. if_php_ver_7 .*/
	/*. boolean .*/ function assert(/*. mixed .*/ $assertion, /*. Throwable .*/ $exception = NULL){}
/*. end_if_php_ver .*/


/*. string .*/ function cli_get_process_title(){}
/*. bool .*/ function cli_set_process_title(/*. string .*/ $title) /*. triggers E_WARNING .*/ {}
/*. bool .*/ function dl(/*. string .*/ $lib){}
/*. bool .*/ function extension_loaded(/*. string .*/ $extension_name){}
/*. void .*/ function gc_collect_cycles(){}
/*. void .*/ function gc_disable(){}
/*. void .*/ function gc_enable(){}
/*. bool .*/ function gc_enabled(){}
/*. int .*/ function gc_mem_caches(){}
/*. string .*/ function get_cfg_var(/*. string .*/ $option)/*. triggers E_WARNING .*/{}
/*. string.*/ function get_current_user(){}
/*. string[int] .*/ function get_declared_classes(){}
/*. string[int] .*/ function get_declared_interfaces(){}
/*. mixed[string] .*/ function get_defined_constants(){}
/*. string[string][int] .*/ function get_defined_functions(){}
/*. mixed[string] .*/ function get_defined_vars(){}
/*. string[int] .*/ function get_extension_funcs(/*. string .*/ $extension_name){}
/*. string.*/ function get_include_path(){}
/*. string[int] .*/ function get_included_files(){}
/*. string[int] .*/ function get_loaded_extensions(){}

/*. if_php_ver_5 .*/

	/** @deprecated Use {@link call_user_func_array()} instead. */
	/*. mixed .*/ function call_user_method(/*. string .*/ $method_name, /*. object .*/ &$obj /*., args .*/){}

	/** @deprecated Use {@link call_user_func_array()} instead. */
	/*. mixed .*/ function call_user_method_array(/*. string .*/ $method_name, /*. object .*/ &$obj, /*. array[int]mixed .*/ $paramarr){}

	/*. bool  .*/ function set_magic_quotes_runtime(/*. int .*/ $new_setting){}

	/*. int   .*/ function get_magic_quotes_runtime(){}

/*. end_if_php_ver .*/
	
/** @deprecated */
/*. int   .*/ function get_magic_quotes_gpc(){}
/*. string[int] .*/ function get_required_files(){}
/*. resource[int] .*/ function get_resources(/*. string .*/ $type = NULL){}
/*. string.*/ function getenv(/*. string .*/ $varname){}
/*. int .*/ function getlastmod(){}
/*. int .*/ function getmygid(){}
/*. int .*/ function getmyinode(){}
/*. int .*/ function getmypid(){}
/*. int .*/ function getmyuid(){}
/*. string[string][int] .*/ function getopt(/*. string .*/ $options /*., args .*/){}
/*. int[string] .*/ function getrusage($who = 0){}
/*. string.*/ function ini_alter(/*. string .*/ $varname, /*. string .*/ $newvalue){}
/*. mixed[string][string] .*/ function ini_get_all(/*. args .*/){}
/**
	Gets the value of a configuration option.
    Returns the value of the configuration option on success. Failure,
    such as querying for a non-existent value, will return an empty string.
    Boolean values are returned as "0" for FALSE, "1" for "TRUE".  Some
    parameters have the default value NULL, and this value gets returned
    if the paramenter isn't defined in the php.ini.  Some values uses a
    special format, for example "upload_max_filesize = 10M": take a look
    to the official WEB site for an example on how to parse these values.
    A typical example:
	<pre>
	if ( ini_get("magic_quotes_gpc") === "1" )
		$s = stripslashes($s);
	</pre>
*/ 
/*. string.*/ function ini_get(/*. string .*/ $varname){}
/*. void  .*/ function ini_restore(/*. string .*/ $varname){}
/*. string.*/ function ini_set(/*. string .*/ $varname, /*. string .*/ $newvalue){}
/*. int .*/ function memory_get_peak_usage(/*. args .*/){}
/*. int   .*/ function memory_get_usage(){}
/*. mixed[string] .*/ function parse_ini_file(/*. string .*/ $filename, $process_sections = false, $scanner_mode = INI_SCANNER_NORMAL){}
/*. mixed[string] .*/ function parse_ini_string(/*. string .*/ $ini,
	$process_sections = false, $scanner_mode = INI_SCANNER_NORMAL){}
/*. string.*/ function php_ini_loaded_file(){}
/*. string.*/ function php_ini_scanned_files(){}
/** @deprecated This function removed as of PHP 5.5.0 */
/*. string.*/ function php_logo_guid(){}
/*. string.*/ function php_sapi_name(){}
/*. string.*/ function php_uname($mode = "a"){}
/*. bool  .*/ function phpcredits($flag = CREDITS_ALL){}
/*. bool  .*/ function phpinfo($what = INFO_ALL){}
/*. string.*/ function phpversion(/*. args .*/){}
/*. bool  .*/ function putenv(/*. string .*/ $setting){}
/*. void  .*/ function restore_include_path(){}
/*. string.*/ function set_include_path(/*. string .*/ $new_inc_path){}
/*. void  .*/ function set_time_limit(/*. int .*/ $seconds){}
/*. string .*/ function sys_get_temp_dir()/*. triggers E_WARNING .*/{}
/** @deprecated This function removed as of PHP 5.5.0 */
/*. string.*/ function zend_logo_guid(){}
/*. int .*/ function zend_thread_id(){}
/*. string.*/ function zend_version(){}
