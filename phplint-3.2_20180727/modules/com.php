<?php
/**
 * COM and .Net (Windows only).
 * 
 * All the classes defined in this module have magic, dynamically created
 * properties and methods depending on the specific COM object, so programs
 * based on this module can be hardly validated with PHPLint. Using the
 * user_func_call*() functions is required in order to pass validation, but
 * errors have to handled at runtime (missing members, invalid arguments, etc.)
 * so PHPLint is of little help here.
 * 
 * <p><b>WARNING.</b> This module is mostly incomplete.
 * 
 * <p><b>BEWARE.</b> The COM extension must be enabled in your php.ini by adding
 * this line:  <blockquote><tt>extension=php_com_dotnet.dll</tt></blockquote>
 * 
 * <p>See: {@link http://www.php.net/manual/en/book.com.php}
 * 
 * @package com
 */

/*. require_module 'core'; .*/

define("CLSCTX_INPROC_SERVER", 	1);
define("CLSCTX_INPROC_HANDLER", 	2);
define("CLSCTX_LOCAL_SERVER", 	4);
define("CLSCTX_REMOTE_SERVER", 	16);
define("CLSCTX_SERVER", 	21);
define("CLSCTX_ALL", 	23);

define("VT_NULL", 	1);
define("VT_EMPTY", 	0);
define("VT_UI1", 	17);
define("VT_I2", 	2);
define("VT_I4", 	3);
define("VT_R4", 	4);
define("VT_R8", 	5);
define("VT_BOOL", 	11);
define("VT_ERROR", 	10);
define("VT_CY", 	6);
define("VT_DATE", 	7);
define("VT_BSTR", 	8);
define("VT_DECIMAL", 	14);
define("VT_UNKNOWN", 	13);
define("VT_DISPATCH", 	9);
define("VT_VARIANT", 	12);
define("VT_I1", 	16);
define("VT_UI2", 	18);
define("VT_UI4", 	19);
define("VT_INT", 	22);
define("VT_UINT", 	23);
define("VT_ARRAY", 	8192);
define("VT_BYREF", 	16384);

define("CP_ACP", 	0);
define("CP_MACCP", 	2);
define("CP_OEMCP", 	1);
define("CP_UTF7", 	65000);
define("CP_UTF8", 	65001);
define("CP_SYMBOL", 	42);
define("CP_THREAD_ACP", 	3);

define("VARCMP_LT", 	0);
define("VARCMP_EQ", 	1);
define("VARCMP_GT", 	2);
define("VARCMP_NULL", 	3);

define("NORM_IGNORECASE", 	1);
define("NORM_IGNORENONSPACE", 	2);
define("NORM_IGNORESYMBOLS", 	4);
define("NORM_IGNOREWIDTH", 	131072);
define("NORM_IGNOREKANATYPE", 	65536);
define("NORM_IGNOREKASHIDA", 	262144);

define("DISP_E_DIVBYZERO", 	-2147352558);
define("DISP_E_OVERFLOW", 	-2147352566);

define("MK_E_UNAVAILABLE", 	-2147221021);

class com_exception extends Exception{}

class VARIANT {
	function __construct(
			/*. mixed .*/ $value, /*. int .*/ $type = VT_UNKNOWN, $codepage = CP_ACP)/*. throws com_exception .*/{}
}

/**
 * BEWARE: this is a magic class with hidden methods that depends on the specific
 * object being created. To succesfully validate with PHPLint, these obscured
 * magical methods have to be invoked through the call_user_func*() functions.
 */
class COM extends VARIANT {
	
	function __construct(
			/*. string .*/ $module_name,
			/*. mixed .*/ $server_name = NULL,
			$codepage = CP_ACP,
			/*. string .*/ $typelib = NULL)
			/*. throws com_exception .*/{}
	
	/**
	 * @return void
	 * @deprecated You should never need to use this method. It exists as a logical
	 * complement to the Release() method.
	 */
	function AddRef(){}
	
	/**
	 * @return void
	 * @deprecated You should never need to use this method. Its existence in PHP
	 * is a bug designed to work around a bug that keeps COM objects running longer
	 * than they should. 
	 */
	function Release(){}
	
	/** @deprecated Still not fully implemented under PHP, do not use. */
	/*. VARIANT .*/ function All()/*. throws com_exception .*/{}
	
	/*. VARIANT .*/ function Next()/*. throws com_exception .*/{}
	/*. VARIANT .*/ function Prev()/*. throws com_exception .*/{}
	/*. void    .*/ function Reset()/*. throws com_exception .*/{}
}

/**
 * BEWARE: this is a magic class with hidden methods that depends on the specific
 * object being created. To succesfully validate with PHPLint, these obscured
 * magical methods have to be invoked through the call_user_func*() functions.
 */
class DOTNET extends VARIANT {
	function __construct(
			/*. string .*/ $assembly_name,
			/*. string .*/ $class_name,
			$codepage = CP_ACP)
			/*. throws com_exception .*/{}
}


/*. object .*/ function com_get_active_object(/*. string .*/ $progid /*., args .*/){}
/*. string .*/ function com_create_guid(){}
/*. bool .*/ function com_event_sink(/*. object .*/ $comobject, /*. object .*/ $sinkobject /*., args .*/){}
/*. bool .*/ function com_print_typeinfo(/*. args .*/){}
/*. bool .*/ function com_message_pump( /*. args .*/){}
/*. bool .*/ function com_load_typelib(/*. string .*/ $typelib_name /*., args .*/){}
