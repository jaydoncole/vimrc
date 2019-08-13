<?php

/*
 * PHP core definitions.
 */

const
ALT_DIGITS = 131119,
AM_STR = 131110,
CASE_LOWER = 0,
CASE_UPPER = 1,
CHAR_MAX = 127,
CODESET = 14,
CONNECTION_ABORTED = 1,
CONNECTION_NORMAL = 0,
CONNECTION_TIMEOUT = 2,
COUNT_NORMAL = 0,
COUNT_RECURSIVE = 1,
D_FMT = 131113,
D_T_FMT = 131112,
ENT_COMPAT = 2,
ENT_DISALLOWED = 128,
ENT_HTML401 = 0,
ENT_HTML5 = 48,
ENT_IGNORE = 4,
ENT_NOQUOTES = 0,
ENT_QUOTES = 3,
ENT_SUBSTITUTE = 8,
ENT_XHTML = 32,
ENT_XML1 = 16,
ERA = 131116,
ERA_D_FMT = 131118,
ERA_D_T_FMT = 131120,
ERA_T_FMT = 131121,
EXTR_IF_EXISTS = 6,
EXTR_OVERWRITE = 0,
EXTR_PREFIX_ALL = 3,
EXTR_PREFIX_IF_EXISTS = 5,
EXTR_PREFIX_INVALID = 4,
EXTR_PREFIX_SAME = 2,
EXTR_REFS = 256,
EXTR_SKIP = 1,
E_ALL = 32767,
E_COMPILE_ERROR = 64,
E_COMPILE_WARNING = 128,
E_CORE_ERROR = 16,
E_CORE_WARNING = 32,
E_DEPRECATED = 8192,
E_ERROR = 1,
E_NOTICE = 8,
E_PARSE = 4,
E_RECOVERABLE_ERROR = 4096,
E_STRICT = 2048,
E_USER_DEPRECATED = 16384,
E_USER_ERROR = 256,
E_USER_NOTICE = 1024,
E_USER_WARNING = 512,
E_WARNING = 2,
FNM_CASEFOLD = 16,
FNM_NOESCAPE = 2,
FNM_PATHNAME = 1,
FNM_PERIOD = 4,
GLOB_BRACE = 1024,
GLOB_ERR = 1,
GLOB_MARK = 2,
GLOB_NOCHECK = 16,
GLOB_NOESCAPE = 64,
GLOB_NOSORT = 4,
GLOB_ONLYDIR = 8192,
HTML_ENTITIES = 1,
HTML_SPECIALCHARS = 0,
NOEXPR = 327681,
PEAR_EXTENSION_DIR = '/usr/local/php-5.0.1/lib/php/extensions/no-debug-non-zts-20040412',
PEAR_INSTALL_DIR = '',
PHP_BINARY = '/opt/php-7/bin/php',
PHP_BINDIR = '/opt/php-7/bin',
PHP_CONFIG_FILE_PATH = '/opt/php-7',
PHP_CONFIG_FILE_SCAN_DIR = '',
PHP_DATADIR = '/opt/php-7/share/php',
PHP_DEBUG = 0,
PHP_EOL = "\n",
PHP_EXTENSION_DIR = '/opt/php-7/lib/php/extensions/no-debug-non-zts-20151012',
PHP_EXTRA_VERSION = '-dev',
PHP_INT_MAX = 2147483647,

/*
 * By defining specific version constants for each specific PHP version, the
 * definite assignment analysis verification algorithm of PHPLint can work at
 * its best, as some conditional statements are evaluated depinding on the
 * value of these constants. For example:
 * 
 *    if( PHP_VERSION_ID < 70000 ){
 *        // here code PHP 5 specific
 *    } else {
 *        // here code PHP 7 specific
 *    }
 * 
 * In this way, depending on the --php-version 5|7 command line option, the
 * right set of constants is assigned.
 * The constants here below are the lastest PHP versions available as of this
 * update.
 */

/*. if_php_ver_5 .*/
PHP_VERSION = '5.6.35',
PHP_VERSION_ID = 50535,
PHP_MAJOR_VERSION = 5,
PHP_MINOR_VERSION = 6,
PHP_RELEASE_VERSION = 35,
/*. end_if_php_ver .*/
		
/*. if_php_ver_7 .*/
PHP_VERSION = '7.2.4',
PHP_VERSION_ID = 70204,
PHP_MAJOR_VERSION = 7,
PHP_MINOR_VERSION = 2,
PHP_RELEASE_VERSION = 4,
PHP_INT_MIN = -2147483648,
PHP_OS_FAMILY = "?",
PHP_FD_SETSIZE = 1024,
/*. end_if_php_ver .*/
		
PHP_INT_SIZE = 4,
PHP_LIBDIR = '/opt/php-7/lib/php',
PHP_LOCALSTATEDIR = '/opt/php-7/var',
PHP_MANDIR = '/opt/php-7/php/man',
PHP_MAXPATHLEN = 4096,
PHP_OS = 'Linux',
PHP_OUTPUT_HANDLER_CLEAN = 2,
PHP_OUTPUT_HANDLER_CLEANABLE = 16,
PHP_OUTPUT_HANDLER_CONT = 0,
PHP_OUTPUT_HANDLER_DISABLED = 8192,
PHP_OUTPUT_HANDLER_END = 8,
PHP_OUTPUT_HANDLER_FINAL = 8,
PHP_OUTPUT_HANDLER_FLUSH = 4,
PHP_OUTPUT_HANDLER_FLUSHABLE = 32,
PHP_OUTPUT_HANDLER_REMOVABLE = 64,
PHP_OUTPUT_HANDLER_START = 1,
PHP_OUTPUT_HANDLER_STARTED = 4096,
PHP_OUTPUT_HANDLER_STDFLAGS = 112,
PHP_OUTPUT_HANDLER_WRITE = 0,
PHP_PREFIX = '/opt/php-7',
PHP_QUERY_RFC1738 = 1,
PHP_QUERY_RFC3986 = 2,
PHP_ROUND_HALF_DOWN = 2,
PHP_ROUND_HALF_EVEN = 3,
PHP_ROUND_HALF_ODD = 4,
PHP_ROUND_HALF_UP = 1,
PHP_SAPI = 'cli',
PHP_SHLIB_SUFFIX = 'so',
PHP_SYSCONFDIR = '/opt/php-7/etc',
PHP_URL_FRAGMENT = 7,
PHP_URL_HOST = 1,
PHP_URL_PASS = 4,
PHP_URL_PATH = 5,
PHP_URL_PORT = 2,
PHP_URL_QUERY = 6,
PHP_URL_SCHEME = 0,
PHP_URL_USER = 3,
PHP_ZTS = 1,
PM_STR = 131111,
RADIXCHAR = 65536,
STR_PAD_BOTH = 2,
STR_PAD_LEFT = 0,
STR_PAD_RIGHT = 1,
THOUSEP = 65537,
T_FMT = 131114,
T_FMT_AMPM = 131115,
UPLOAD_ERR_CANT_WRITE = 7,
UPLOAD_ERR_EXTENSION = 8,
UPLOAD_ERR_FORM_SIZE = 2,
UPLOAD_ERR_INI_SIZE = 1,
UPLOAD_ERR_NO_FILE = 4,
UPLOAD_ERR_NO_TMP_DIR = 6,
UPLOAD_ERR_OK = 0,
UPLOAD_ERR_PARTIAL = 3,
YESEXPR = 327680,
ZEND_DEBUG_BUILD = false,
ZEND_THREAD_SAFE = false,
__COMPILER_HALT_OFFSET__ = 1; // FIXME: magic const set only if the source contains __halt_compiler().

/*. if_php_ver_7 .*/

	interface Throwable {
		public /*. string .*/ function getMessage();
		public /*. mixed  .*/ function getCode();
		public /*. string .*/ function getFile();
		public /*. int    .*/ function getLine();
		public /*. mixed[int][string] .*/ function getTrace();
		public /*. string .*/ function getTraceAsString();
		public /*. Throwable .*/ function getPrevious();
		public /*. string .*/ function __toString();
	}


	class Error implements Throwable {
		protected /*. string .*/ $message;
		protected $code = 0;
		protected /*. string .*/ $file;
		protected $line = 0;
		private /*. mixed .*/ $trace;
		private /*. Throwable .*/ $previous;

		public /*. void .*/ function __construct($message = "", $code = 0, /*. Throwable .*/ $previous = NULL){}
		public final /*. string .*/ function getMessage(){}
		public final /*. Throwable .*/ function getPrevious(){}
		public final /*. mixed  .*/ function getCode(){}
		public final /*. string .*/ function getFile(){}
		public final /*. int    .*/ function getLine(){}
		public final /*. mixed[int][string] .*/ function getTrace(){}
		public final /*. string .*/ function getTraceAsString(){}
		public       /*. string .*/ function __toString(){}
		private      /*. void   .*/ function __clone(){}
	}

	/*. unchecked .*/ class ParseError extends Error {}
	/*. unchecked .*/ class TypeError  extends Error {}
	/*. unchecked .*/ class ArithmeticError extends Error {}
	/*. unchecked .*/ class DivisionByZeroError extends ArithmeticError {}
	/*. unchecked .*/ class AssertionError extends Error {}


	/*. int .*/ function intdiv(/*. int .*/ $dividend, /*. int .*/ $divisor){}
	/*. void .*/ function error_clear_last(){}

/*. end_if_php_ver .*/


class Exception
/*. if_php_ver_7 .*/ implements Throwable /*. end_if_php_ver .*/
{
	/** Exception message */
	protected $message = "";
	
	/** User defined exception code */
	protected $code = 0;
	
	/** Source filename of exception */
	protected /*.string.*/ $file;
	
	/** Source line of exception */
	protected /*.int   .*/ $line = 0;
	
	/** Backtrace */
	private /*. mixed .*/ $trace;
	
	/** Previous exception if nested exception */
	/*. if_php_ver_5 .*/
		private /*. Exception .*/ $previous;
	/*. end_if_php_ver .*/
	/*. if_php_ver_7 .*/
		private /*. Throwable .*/ $previous;
	/*. end_if_php_ver .*/

	/*. if_php_ver_5 .*/
		/*.void.*/ function __construct($message = "", $code = 0,
			Exception $previous = NULL){}
	/*. end_if_php_ver .*/
	/*. if_php_ver_7 .*/
		/*.void.*/ function __construct($message = "", $code = 0,
			Throwable $previous = NULL){}
	/*. end_if_php_ver .*/


	/** Message of exception */
	final /*.string.*/ function getMessage(){}

	/** Return previous exception, or NULL if there is not */ 
	/*. if_php_ver_5 .*/
		final /*. Exception .*/ function getPrevious(){}
	/*. end_if_php_ver .*/
	/*. if_php_ver_7 .*/
		final /*. Throwable .*/ function getPrevious(){}
	/*. end_if_php_ver .*/

	/** Code of exception */
	final /*. int .*/ function getCode(){}
	
	/** Source filename */ 
	/*. if_php_ver_5 .*/
		final /*.string.*/ function getFile(){}
	/*. end_if_php_ver .*/
	/*. if_php_ver_7 .*/
		final /*.string.*/ function getFile(){}
	/*. end_if_php_ver .*/
	
	/** Source line */
	final /*. int .*/ function getLine(){}
	
	/** An array of the backtrace() */ 
	final /*.mixed[int][string].*/ function getTrace(){}
	
	/** Formatted string of trace */
	final /*.string.*/ function getTraceAsString(){}

	/** Formatted string for display */ 
	/*.string.*/ function __toString(){}

	/**
		Always gives a fatal error: exceptions are not clonable.
	    FIXME: this method is `private' in official manual: why?
	*/ 
	final public /*. void .*/ function __clone(){}
}

/**
	Generic exception into which errors can be mapped.
	PHPLint uses just it extensively, see the cast() magic function int
	the stdlib/errors.php package.
*/
class ErrorException extends Exception
{
	protected /*. int .*/ $severity = 0;

	/** Encapsulate errors in exceptions

		$message is the human-readable description of the
		exception, while $code lets the program to detect the
		specific condition occurred. The $severity is the usual
		log level (see the E_* constants).  $filename and $lineno
		lets to indicate an alternate position; customized
		error handling functions can indicate the real source
		of the error rather that the point where the exception
		was thrown.
	*/
	/*. void .*/ function __construct(
		$message = "",
		$code = 0,
		$severity = E_ERROR,
		$filename = /*. (string) .*/ NULL,
		$lineno = 0,
		$previous = /*. (Exception) .*/ NULL
	)
	{ parent::__construct(); }

	/*. int .*/ function getSeverity(){}
}

/* FIXME: $argc,$argv are actually defined only in the CLI and CGI versions
and are set only if "register_argc_argv = on" in php.ini. */
$argc = 0;
$argv = /*. (array[int]string) .*/ array();

/*. bool  .*/ function array_key_exists(/*. mixed .*/ $key, /*. array .*/ $search){}
/*. mixed .*/ function constant(/*. string .*/ $name){}
/*. string.*/ function bin2hex(/*. string .*/ $s){}
/*. string.*/ function hex2bin(/*. string .*/ $s)/*. triggers E_WARNING .*/{}
/*. int   .*/ function sleep(/*. int .*/ $secs){}
/*. void  .*/ function usleep(/*. int .*/ $microsecs){}
/*. mixed .*/ function time_nanosleep(/*.int.*/ $secs, /*.int.*/ $nanosecs){}
/*. int   .*/ function time(){}
/**
 * The default values -1 actually means the current value of the corresponding
 * time element.
 */
/*. int .*/ function mktime($hour = -1, $minute = -1, $second = -1, $month = -1, $day = -1, $year = -1){}
/*. int   .*/ function gmmktime($hour = -1, $minute = -1, $second = -1, $month = -1, $day = -1, $year = -1){}
/*. string.*/ function strftime(/*.string .*/ $fmt, $time = -1){}
/*. string.*/ function gmstrftime(/*. string .*/ $fmt, $time = -1){}
/*. int   .*/ function strtotime(/*. string .*/ $time, $now = -1){}
/*. string.*/ function date(/*. string .*/ $fmt, $time = -1){}
/*. int   .*/ function idate(/*. string .*/ $fmt, $time = -1){}
/*. string.*/ function gmdate(/*. string .*/ $fmt, $time = -1){}
/*. mixed[] .*/ function getdate($time = -1){}
/*. bool  .*/ function checkdate(/*.int.*/ $month, /*.int.*/ $day, /*.int.*/ $year){}
/*. void  .*/ function flush(){}
/*. string.*/ function wordwrap(/*.string.*/ $s, $width = 75, $break_ = "\n", $cut = FALSE){}
/*. string.*/ function htmlspecialchars(/*. string .*/ $s, $quote_style = ENT_COMPAT, $charset = "ISO-8859-1", $double_encode = TRUE){}
/*. string.*/ function htmlspecialchars_decode(/*. string .*/ $s, $quote_style = ENT_COMPAT){}
/*. string.*/ function htmlentities(/*. string .*/ $s, $quote_style = ENT_COMPAT, $charset = "ISO-8859-1", $double_encode = TRUE){}
/*. string.*/ function html_entity_decode(/*. string .*/ $s, $quote_style = ENT_COMPAT, $charset = "ISO-8859-1"){}
/*. array[string]string .*/ function get_html_translation_table($table = HTML_SPECIALCHARS, $quote_style = ENT_COMPAT){}
/*. int .*/ function realpath_cache_size(){}
/*. int   .*/ function crc32(/*. string .*/ $s){}
/*. int   .*/ function strnatcmp(/*.string.*/ $s1, /*.string.*/ $s2){}
/*. int   .*/ function strnatcasecmp(/*.string.*/ $s1, /*.string.*/ $s2){}
/*. int   .*/ function strcasecmp(/*.string.*/ $s1, /*.string.*/ $s2){}
/*. int   .*/ function substr_count(/*.string.*/ $haystack, /*.string.*/ $needle, $offset = 0, $length = -1){}
/*. int   .*/ function strspn(/*. string .*/ $subject, /*. string .*/ $mask, $start =0, $length = -1){}
/*. int   .*/ function strcspn(/*. string .*/ $subject, /*. string .*/ $mask, $start =0, $length = -1){}
/*. string.*/ function strtok(/*. string .*/ $s, /*. string .*/ $token){}
/*. string.*/ function strtoupper(/*. string .*/ $s){}
/*. string.*/ function strtolower(/*. string .*/ $s){}
/*. int .*/ function strpos(/*.string.*/ $haystack, /*.mixed.*/ $needle, $offset = 0){}
/*. int .*/ function stripos(/*. string .*/ $haystack, /*. string .*/ $needle, $offset = 0){}
/*. int .*/ function strrpos(/*. string .*/ $haystack, /*. string .*/ $needle, $offset = 0){}
/*. int .*/ function strripos(/*. string .*/ $haystack, /*. string .*/ $needle, $offset = 0){}
/*. string.*/ function strrev(/*. string .*/ $s){}
/*. string.*/ function nl2br(/*. string .*/ $s, $is_xhtml = TRUE){}
/*. string.*/ function stripslashes(/*.string.*/ $s){}
/*. string.*/ function stripcslashes(/*. string .*/ $s){}
/*. string.*/ function strstr(/*.string.*/ $haystack, /*.mixed.*/ $needle, $before_needle = false){}
/*. string.*/ function strchr(/*.string.*/ $haystack, /*.string.*/ $needle){}
/*. string.*/ function stristr(/*.string.*/ $haystack, /*.mixed.*/ $needle, $before_needle = false){}
/*. string.*/ function strrchr(/*.string.*/ $haystack, /*.mixed.*/ $needle){}
/*. string.*/ function str_shuffle(/*. string .*/ $s){}
/*. mixed .*/ function str_word_count(/*. string .*/ $s, $format = 0, /*. string .*/ $charlist = ""){}
/*. array[int]string .*/ function str_split(/*.string.*/ $s, $split_length = 1){}
/*. string.*/ function strpbrk(/*. string .*/ $haystack, /*.string.*/ $charlist){}
/*. int   .*/ function substr_compare(/*. string .*/ $main_str, /*. string .*/ $s, /*. int .*/ $offset, $length = -1, $case_insensitivity = false){}
/*. int   .*/ function strcoll(/*. string .*/ $str1, /*. string .*/ $str2){}
/** @deprecated This function is not available on Windows. */
/*. string.*/ function money_format(/*. string .*/ $format, /*. float .*/ $number_){}
/*. string.*/ function substr(/*.string.*/ $s, /*.int.*/ $start, $length = -1){}
/*. mixed .*/ function substr_replace(/*. mixed .*/ $s, /*. string .*/ $replacement, /*. int .*/ $start, $length = 0){}
/*. string.*/ function quotemeta(/*. string .*/ $s){}
/*. string.*/ function ucfirst(/*.string.*/ $s){}
/*. string.*/ function ucwords(/*.string.*/ $s){}
/*. string.*/ function strtr(/*.string.*/ $s, /*.mixed.*/ $x /*., args .*/){}
/*. string.*/ function addslashes(/*.string.*/ $s){}
/*. string.*/ function addcslashes(/*.string.*/ $s, /*.string.*/ $charlist){}
/*. string.*/ function rtrim(/*.string.*/ $s, $charlist = " \t\n\r\0\x0b"){}
/*. string.*/ function chop(/*.string.*/ $s, $charlist = " \t\n\r\0\x0b"){}
/*. mixed .*/ function str_replace(/*.mixed.*/ $search, /*.mixed.*/ $replace, /*.mixed.*/ $subject /*., args .*/){}
/*. mixed .*/ function str_ireplace(/*.mixed.*/ $search, /*.mixed.*/ $replace, /*.mixed.*/ $subject /*., args .*/){}
/*. string.*/ function str_repeat(/*.string.*/ $s, /*.int.*/ $n){}
/*. mixed .*/ function count_chars(/*. string .*/ $s, $mode = 0){}
/*. string.*/ function chunk_split(/*.string.*/ $body, $chunklen = 76, $end = "\r\n"){}
/*. string.*/ function trim(/*.string.*/ $s, $charlist = " \t\n\r\0\x0b"){}
/*. string.*/ function ltrim(/*. string .*/ $s, $charlist = " \t\n\r\0\x0b"){}
/*. string.*/ function strip_tags(/*. string .*/ $s, $allowable_tags=""){}
/*. int   .*/ function similar_text(/*. string .*/ $first, /*. string .*/ $second, /*. return float .*/ &$percent=0.0){}
/*. array[int]string .*/ function explode(/*.string.*/ $sep, /*.string.*/ $s, $limit = PHP_INT_MAX){}

/** ATTENTION!
	The actual PHP function also accepts one single array assuming glue to be the
	empty string. Rewrite as implode("", array) to comply with this declaration.
*/ 
/*. string.*/ function implode(/*.string.*/ $glue, /*. array[]string .*/ $pieces){}

/*. string.*/ function join(/*.string.*/ $glue, /*. array[]string .*/ $pieces){}
/*. string.*/ function chr(/*.int.*/ $c){}
/*. int   .*/ function ord(/*.string.*/ $s){}
/*. void  .*/ function parse_str(/*. string .*/ $s /*., args .*/){}
/*. string.*/ function str_pad(/*. string .*/ $input, /*. int .*/ $pad_length, $pad_string = " ", $pad_type = STR_PAD_RIGHT){}
/*. string.*/ function sprintf(/*.string.*/ $fmt /*., args .*/){}
/*. string.*/ function printf(/*.string.*/ $fmt /*., args .*/){}
/*. int   .*/ function vprintf(/*. string .*/ $format, /*. array .*/ $args_){}
/*. string.*/ function vsprintf(/*. string .*/ $format, /*. array .*/ $args_){}
/*. mixed .*/ function sscanf(/*.string.*/ $s, /*.string.*/ $fmt /*., args .*/){}

/**
 * This function may return pretty anything depending on its arguments:
 * parse_url("ftp://user:password@host:port"); # FALSE
 * parse_url("http://xxx", PHP_URL_PORT); # NULL
 * parse_url("http://xxx:1234", PHP_URL_PORT); # 1234
 * That's why, for safety, its return type here has been set to mixed.
 */
/*. mixed .*/ function parse_url(/*. string .*/ $url, $component = -1){}

/*. string.*/ function urlencode(/*. string .*/ $s){}
/*. string.*/ function urldecode(/*. string .*/ $s){}
/*. string.*/ function rawurlencode(/*. string .*/ $s){}
/*. string.*/ function rawurldecode(/*. string .*/ $s){}
/*. string.*/ function http_build_query(/*. array .*/ $formdata, $numeric_prefix="", $arg_separator="", $enc_type = PHP_QUERY_RFC1738){}
/*. string.*/ function exec(/*. string .*/ $command, /*. return string[int] .*/ &$output = NULL, /*. return int .*/ &$return_var = 0){}
/*. string.*/ function system(/*. string .*/ $command, /*. return int .*/ &$return_var = 0){}
/*. string.*/ function escapeshellcmd(/*. string .*/ $command){}
/*. string.*/ function escapeshellarg(/*. string .*/ $arg){}
/*. void  .*/ function passthru(/*. string .*/ $command, /*. return int .*/ &$return_var = 0){}
/*. string.*/ function shell_exec(/*. string .*/ $cmd){}
/*. resource .*/ function proc_open(/*. string .*/ $cmd, /*. string[int][int] .*/ $descriptorspec, /*. return resource[int] .*/ &$pipes, /*. string .*/ $cwd = NULL, /*. string[string] .*/ $env = NULL, /*. mixed[string] .*/ $other_options = NULL){}
/*. int   .*/ function proc_close(/*. resource .*/ $process){}
/*. int   .*/ function proc_terminate(/*. resource .*/ $process, $signal = 15){}
/*. array[string]mixed .*/ function proc_get_status(/*. resource .*/ $process)/*. triggers E_WARNING .*/{}
/*. bool  .*/ function proc_nice(/*. int .*/ $increment){}
/*. int   .*/ function rand(/*. args .*/){}
/*. void  .*/ function srand(/*. args .*/){}
/*. int   .*/ function getrandmax(){}
/*. int   .*/ function mt_rand(/*. args .*/){}
/*. void  .*/ function mt_srand(/*. args .*/){}
/*. int   .*/ function mt_getrandmax(){}
/*. string.*/ function base64_decode(/*. string .*/ $s, $strict = FALSE){}
/*. string.*/ function base64_encode(/*. string .*/ $s){}
/*. string.*/ function convert_uuencode(/*. string .*/ $data){}
/*. string.*/ function convert_uudecode(/*. string .*/ $data){}

/**
	Returns the absolute value of the argument.
	<b>Warning.</b>
	This function can be applied to int values as well when an int result is
	expected, but this is not the case when the int value is the maximum
	negative int, in fact the result is float:
	<pre>abs(-PHP_INT_MAX-1) ==&gt; float(2147483648)</pre>
	and applying the typecast required by PHPLint we end with an unexpected
	negative number:
	<pre>(int) abs(-PHP_INT_MAX-1) ==&gt; int(-2147483648)</pre>
	rather than the expected 0 as a result of the 2-complement. If you really
	need the common behaviour of a true abs() function applied to int, consider
	this expression instead that calculates the 2-complement:
	<pre>$i &gt;= 0? $i : (($i ^ PHP_INT_MAX) + 1) &amp; PHP_INT_MAX</pre>
	or even:
	<pre>$i == -PHP_INT_MAX - 1? 0 : (int) abs($i)</pre>
	If, instead, you are only interested to drop the sign, simply do this:
	<pre>$i ^ PHP_INT_MAX</pre>
	@param float $n
	@return float  The absolute value of the argument.
*/
function abs($n){}
/*. float .*/ function ceil(/*. float .*/ $x){}
/*. float .*/ function floor(/*. float .*/ $x){}
/*. float .*/ function round(/*. float .*/ $x, $precision = 0, $mode = PHP_ROUND_HALF_UP){}
/*. float .*/ function fmod(/*. float .*/ $x, /*. float .*/ $y){}
/*. bool  .*/ function is_finite(/*. float .*/ $val){}
/*. bool  .*/ function is_nan(/*. float .*/ $val){}
/*. bool  .*/ function is_infinite(/*. float .*/ $val){}
/*. float .*/ function bindec(/*. string .*/ $binary){}
/*. float .*/ function hexdec(/*. string .*/ $hex){}
/*. float .*/ function octdec(/*. string .*/ $oct){}
/*. string.*/ function decbin(/*. int .*/ $number_){}
/*. string.*/ function decoct(/*. int .*/ $number_){}
/*. string.*/ function dechex(/*. int .*/ $number_){}
/*. string.*/ function base_convert(/*. string .*/ $number_, /*. int .*/ $frombase, /*. int .*/ $tobase){}

/**
	WARNING. This function cannot be called with 3 arguments. Instead,
	you MUST either provide one, two or four arguments.
*/
/*. string.*/ function number_format(/*.float.*/ $n, $decimals = 0, $dec_point = ".", $thousands_sep = ","){}

/*. mixed.*/ function microtime($get_as_float = FALSE){}
/*. string.*/ function uniqid($prefix = "", $more_entropy = FALSE){}
/*. string.*/ function quoted_printable_decode(/*. string .*/ $s){}
/*. string.*/ function quoted_printable_encode(/*. string .*/ $s){}
/*. bool  .*/ function error_log(/*. string .*/ $message, $message_type = 0, $destination="", $extra_headers=""){}

/*. mixed .*/ function call_user_func(/*. mixed .*/ $func /*., args .*/)/*. triggers E_WARNING throws Exception .*/{}
	
/*. mixed .*/ function call_user_func_array(/*. mixed .*/ $func, /*. mixed[int] .*/ $param_arr)/*. triggers E_WARNING throws Exception .*/{}
/*. string.*/ function serialize(/*. mixed .*/ $value){}

/**
	You should apply <tt>cast()</tt> to the result.
	For example, if the intended result must be an object:
	<p>
	<tt>$obj = cast("MyClass", unserialize($serialized_data));</tt>
	<p>
	or if the expected result must be an array:
	<p>
	<tt>$arr = cast("array[int]MyClass", unserialize($serialized_data)(;</tt>

	<p>
	<b>Warning.</b> Raises E_NOTICE if the data are corrupted.
*/
/*. mixed .*/ function unserialize(/*. string .*/ $s
	/*. if_php_ver_7 .*/
		, /*. mixed .*/ $options = NULL
	/*. end_if_php_ver .*/
	) /*. triggers E_NOTICE .*/{}

/**
 * Dumps to standard output information about one or more expressions.
 * Recursive references are detected and reported literally as <tt>*RECURSION*</tt>.
 * @param mixed $x
 * @return void
 */
function var_dump($x /*., args .*/){}

/**
 * Outputs or returns a parsable string representation of a variable.
 * @param mixed $x
 * @param boolean $return_
 * @return string Textual representation if the $return_ parameter is true, otherwise NULL.
 * @triggers E_WARNING Data contains a recursive reference, for example a property
 * containing a reference to the same object.
 */
function var_export($x, $return_ = FALSE){}

/*. void  .*/ function debug_zval_dump(/*. mixed .*/ $variable){}
/*. mixed .*/ function print_r(/*. mixed .*/ $expr, $return_ = FALSE){}
/*. void  .*/ function register_shutdown_function(/*. mixed .*/ $func /*., args .*/){}

/** @deprecated See the manual for details. */
/*. bool  .*/ function register_tick_function(/*. mixed .*/ $func /*., args .*/){}

/*. void  .*/ function unregister_tick_function(/*. string .*/ $func){}
/*. mixed .*/ function highlight_file(/*. string .*/ $filename, $return_ = false){}
/*. mixed .*/ function show_source(/*. string .*/ $filename, $return_ = false){}
/*. mixed .*/ function highlight_string(/*. string .*/ $s, $return_ = false){}
/*. string.*/ function php_strip_whitespace(/*. string .*/ $filename){}

/**
	@deprecated For technical reasons, this function is deprecated and
	removed from PHP.  Instead, use <code>php -l somefile.php</code> from
	the commandline. Or, even better, use PHPLint.
*/
/*. bool  .*/ function php_check_syntax(/*. string .*/ $filename, /*. return string .*/ &$error_message){}
/*. bool  .*/ function setcookie(/*. string .*/ $name, $value = "", $expire = 0, $path = "", $domain = "", $secure = false, $httponly = false){}
/*. bool  .*/ function setrawcookie(/*. string .*/ $name, $value = "", $expire = 0, $path = "", $domain = "", $secure = false, $httponly = false){}
/*. void  .*/ function header(/*. string .*/ $s, $replace = true, $http_response_code = -1){}
/*. void  .*/ function header_remove(/*. string .*/ $name = NULL){}
/*. bool  .*/ function headers_set(/*. args .*/){}
/*. array[int]string .*/ function headers_list(){}
/*. int   .*/ function connection_aborted(){}
/*. int   .*/ function connection_status(){}
/*. int   .*/ function ignore_user_abort($value = false){}
/*. bool  .*/ function is_uploaded_file(/*. string .*/ $fn){}
/*. bool  .*/ function move_uploaded_file(/*. string .*/ $fn, /*. string .*/ $dst)/*. triggers E_WARNING .*/{}
/*. bool  .*/ function boolval(/*. mixed .*/ $v){}
/*. int   .*/ function intval(/*. mixed .*/ $v, $base = 10){}
/*. float .*/ function floatval(/*. mixed .*/ $v){}
/*. float .*/ function doubleval(/*. mixed .*/ $v){}
/*. string.*/ function strval(/*. mixed .*/ $v){}

/**
 * BEWARE: gettype() returns "unknown type" for a closed resource,
 * or "resource (closed)" since PHP 7.2.0. Using is_resource() might not help
 * either to detect at runtime the type of the variable.
 */
/*. string.*/ function gettype(/*. mixed .*/ $variable){}

/** @deprecated The variable's type is always statically determined under PHPLint and cannot be changed at runtime to arbitrary types. */
/*. bool  .*/ function settype(/*. mixed .*/ &$v, /*. string .*/ $type){}

/*. bool  .*/ function is_null(/*. mixed .*/ $v){}

/**
 * BEWARE: is_resource() returns FALSE for closed resources! See also gettype().
 */
/*. bool  .*/ function is_resource(/*.mixed.*/ $v){}

/*. bool  .*/ function is_bool(/*.mixed.*/ $v){}
/*. bool  .*/ function is_long(/*.mixed.*/ $v){}
/*. bool  .*/ function is_float(/*.mixed.*/ $v){}
/*. bool  .*/ function is_int(/*.mixed.*/ $v){}
/*. bool  .*/ function is_integer(/*.mixed.*/ $v){}
/*. bool  .*/ function is_double(/*.mixed.*/ $v){}
/*. bool  .*/ function is_real(/*.mixed.*/ $v){}
/*. bool  .*/ function is_numeric(/*.mixed.*/ $v){}
/*. bool  .*/ function is_string(/*.mixed.*/ $v){}
/*. bool  .*/ function is_array(/*.mixed.*/ $v){}
/*. bool  .*/ function is_object(/*.mixed.*/ $v){}
/*. bool  .*/ function is_scalar(/*.mixed.*/ $v){}
/*. bool  .*/ function is_callable(/*.mixed.*/ $f, $syntax_only = false, /*. return string .*/ &$callable_name = NULL){}
/*. int   .*/ function pclose(/*. resource .*/ $handle){}
/*. resource .*/ function popen(/*. string .*/ $cmd, /*. string .*/ $mode){}
/*. array[string]string .*/ function get_meta_tags(/*. string .*/ $fn, $use_include_path = false)
/*. triggers E_WARNING .*/{}
/*. bool  .*/ function fnmatch(/*. string .*/ $pattern, /*. string .*/ $s, $flags = 0){}
/*. string.*/ function pack(/*. string .*/ $format /*., args .*/){}
/*. mixed[] .*/ function unpack(/*. string .*/ $format, /*. string .*/ $data){}
/*. mixed .*/ function get_browser($user_agent="", $return_array = false){}
/*. bool  .*/ function mail(/*. string .*/ $to, /*. string .*/ $subj, /*. string .*/ $msg, $additional_headers="", $additional_parameters="")/*. triggers E_WARNING .*/{}
/*. int   .*/ function ezmlm_hash(/*. string .*/ $addr){}
/*. float .*/ function lcg_value(){}
/*. bool  .*/ function ob_start(/*. mixed .*/ $output_callback = NULL, $chunk_size = 0, $flags = PHP_OUTPUT_HANDLER_STDFLAGS){}
/*. void  .*/ function ob_flush(){}
/*. void  .*/ function ob_clean(){}
/*. bool  .*/ function ob_end_flush(){}
/*. bool  .*/ function ob_end_clean(){}
/*. bool  .*/ function ob_get_flush(){}
/*. string.*/ function ob_get_clean(){}
/*. int   .*/ function ob_get_length(){}
/*. int   .*/ function ob_get_level(){}
/*. array .*/ function ob_get_status($full_status = FALSE){}
/*. string.*/ function ob_get_contents(){}
/*. void  .*/ function ob_implicit_flush($flag = true){}
/*. array .*/ function ob_list_handlers(){}
/*. string.*/ function ob_gzhandler(/*. string .*/ $s, /*. int .*/ $mode){}
/*. int   .*/ function count(/*. mixed .*/ $array_or_countable, $mode = COUNT_NORMAL){}
/*. int   .*/ function sizeof(/*. mixed .*/ $array_or_countable, $mode = COUNT_NORMAL){}
/*. mixed .*/ function end(/*. array .*/ $a){}
/*. mixed .*/ function prev(/*. array .*/ $a){}
/*. mixed .*/ function next(/*. array .*/ $a){}
/*. mixed .*/ function reset(/*. array .*/ $a){}
/*. mixed .*/ function current(/*. array .*/ $a){}
/*. mixed .*/ function key(/*. array .*/ $array_arg){}
/*. float .*/ function min(/*. args .*/){}
/*. float .*/ function max(/*. args .*/){}
/*. bool  .*/ function in_array(/*. mixed .*/ $needle, /*. array .*/ $haystack, $strict = FALSE){}
/** @deprecated It may overwrite existing variables with unpredictable types of data. */
/*. int   .*/ function extract(/*. mixed[string] .*/ $array_, $flags = EXTR_OVERWRITE, /*. string .*/ $prefix = NULL){}
/*. array[string]mixed .*/ function compact(/*. mixed .*/ $var_names /*., args .*/){}
/*. int   .*/ function ftok(/*. string .*/ $pathname, /*. string .*/ $proj){}
/*. string.*/ function str_rot13(/*. string .*/ $s){}
/*. bool  .*/ function output_add_rewrite_var(/*. string .*/ $name, /*. string .*/ $value){}
/*. bool  .*/ function output_reset_rewrite_vars(){}
### This function is built-in in PHPLint:
###/*. bool  .*/ function trigger_error(/*.string.*/ $msg /*., args .*/){}
/*. int   .*/ function strlen(/*.string.*/ $s){}
/*. int   .*/ function strcmp(/*.string.*/ $a, /*.string.*/ $b){}
/*. bool  .*/ function ctype_cntrl(/*. string .*/ $text){}
/*. int   .*/ function func_num_args(){}
/*. mixed .*/ function func_get_arg(/*. int .*/ $n){}
/*. array[int]mixed .*/ function func_get_args(){}
/*. array .*/ function each(/*. array .*/ $a){}
/*. bool  .*/ function empty(/*. mixed .*/ $variable){}
/*. void  .*/ function unset(/*. mixed .*/ $var_ /*., args .*/){}

/** @deprecated The contents of the eval() cannot be parsed by PHPLint. */
/*. mixed .*/ function eval(/*. string .*/ $cmd){}

/*. bool  .*/ function defined(/*. string.*/ $c){}
	/*. bool  .*/ function headers_sent(/*. return string .*/ &$file='', /*. return int .*/ &$line=0){}
/*. bool  .*/ function header_register_callback(/*. mixed .*/ $callback){}
/*. int   .*/ function http_response_code($response_code = 0){}
/*. mixed .*/ function set_error_handler(/*.mixed.*/ $callback, $error_types = -1){}
/*. int   .*/ function error_reporting(/*. args .*/){}
/*. bool  .*/ function function_exists(/*. string .*/ $func_name){}
/*. bool  .*/ function method_exists(/*. mixed .*/ $obj, /*. string .*/ $method){}
/*. string.*/ function get_class(/*. object .*/ $obj=NULL){}
/*. bool  .*/ function is_subclass_of(/*. mixed .*/ $obj, /*.string.*/ $class_name){}


class stdClass{}

/**
 * Trying to retrieve an object from $_SESSION[] whose class is undefined
 * we end with an instance of this dummy class. Original properties are
 * restored, but the original class name and the methods do not, so the
 * class is unusable. Trying to call any method on this class causes these
 * messages:
 *
 * PHP Notice:  main(): The script tried to execute a method or access a
 * property of an incomplete object.  Please ensure that the class definition
 * "MyClass" of the object you are trying to operate on was loaded _before_
 * unserialize() gets called or provide a __autoload() function to load the
 * class definition  in /home/www.icosaedro.it/public_html/mypage.html on
 * line 34
 * 
 * PHP Fatal error:  main(): The script tried to execute a method or access
 * a property of an incomplete object. [again, same hint]
 */
class __PHP_Incomplete_Class {}

/*. int   .*/ function strncmp(/*. string .*/ $str1, /*. string .*/ $str2, /*. int .*/ $len){}
/*. int   .*/ function strncasecmp(/*. string .*/ $str1, /*. string .*/ $str2, /*. int .*/ $len){}
/*. string.*/ function get_parent_class(/*. mixed .*/ $object_=NULL){}
/*. bool  .*/ function is_a(/*. object .*/ $object_, /*. string .*/ $class_name){}
/*. array[string]mixed .*/ function get_class_vars(/*. string .*/ $class_name){}
/*. array[string]mixed .*/ function get_object_vars(/*. object .*/ $obj){}
/*. array[int]string .*/ function get_class_methods(/*. mixed .*/ $class_){}
/*. bool  .*/ function class_exists(/*. string .*/ $classname, /*.bool.*/ $autoload=TRUE){}
/*. bool  .*/ function interface_exists(/*. string .*/ $classname, /*.bool.*/ $autoload= TRUE){}
/*. void  .*/ function restore_error_handler(){}
/*. string.*/ function create_function(/*. string .*/ $args_, /*. string .*/ $code){}
/*. string.*/ function get_resource_type(/*. resource .*/ $res){}
define("DEBUG_BACKTRACE_PROVIDE_OBJECT", 1);
define("DEBUG_BACKTRACE_IGNORE_ARGS", 2);
/*. void  .*/ function debug_print_backtrace($options = 0, $limit = 0){}
/*. array[int][string]mixed .*/ function debug_backtrace(
	$options = DEBUG_BACKTRACE_PROVIDE_OBJECT, $limit = 0){}
/*. string.*/ function confirm_extname_compiled(/*. string .*/ $arg){}
/*. string.*/ function uuencode(/*. string .*/ $data){}
/*. string.*/ function uudecode(/*. string .*/ $data){}
/*. string[] .*/ function get_headers(/*. string .*/ $url, $format = 0)/*. triggers E_WARNING .*/{}
/*. array[string]mixed .*/ function error_get_last(){}
/*. bool  .*/ function time_sleep_until(/*. float .*/ $timestamp)
/*. triggers E_WARNING .*/{}
/*. string.*/ function set_exception_handler(/*. mixed .*/ $exception_handler){}
/*. void  .*/ function restore_exception_handler(){}
/*. bool .*/ function property_exists(/*. mixed .*/ $class_, /*. string .*/ $property){}
/*. bool .*/ function class_alias(/*. string .*/ $original , /*. string .*/ $alias, $autoload = TRUE){}
/*. string .*/ function get_called_class(){}
#/*. void .*/ function forward_static_call(){}
#/*. void .*/ function forward_static_call_array(){}
/*. array[int]string .*/ function str_getcsv(/*. string .*/ $input, $delimiter = ",", $enclosure = '"', $escape = "\\"){}
/*. string .*/ function lcfirst(/*. string .*/ $s){}


/*. if_php_ver_7 .*/

	/**
	 * @param int $length Must be positive.
	 * @return string
	 * @throws Exception No appropriate randomness source.
	 * @throws TypeError Invalid parameter.
	 * @throws Error Length parameter is not positive. Failed reading from the
	 * entropy source, or no enough entropy available.
	 */
	function random_bytes($length){}

	/**
	 * @param int $min
	 * @param int $max
	 * @return int
	 * @throws Exception No appropriate randomness source.
	 * @throws TypeError Invalid parameter.
	 * @throws Error $max is less than $min. Failed reading from the
	 * entropy source, or no enough entropy available.
	 */
	function random_int($min, $max){}
	
	/* Followinf utf8_*() functions moved from xml module since PHP 7.2.0: */
	/*. string.*/ function utf8_encode(/*. string .*/ $data){}
	/*. string.*/ function utf8_decode(/*. string .*/ $data){}
	
	/*. bool .*/ function is_iterable(/*. mixed .*/ $x){}

/*. end_if_php_ver .*/
