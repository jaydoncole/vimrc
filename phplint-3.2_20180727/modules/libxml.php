<?php
/** libxml Functions.

See: {@link http://www.php.net/manual/en/ref.libxml.php}
@package libxml
*/

/*. if_php_ver_7 .*/
define("LIBXML_BIGLINES", 4194304);
/*. end_if_php_ver .*/

define("LIBXML_COMPACT", 65536);
define("LIBXML_DOTTED_VERSION", '2.9.1');
define("LIBXML_DTDATTR", 8);
define("LIBXML_DTDLOAD", 4);
define("LIBXML_DTDVALID", 16);
define("LIBXML_ERR_ERROR", 2);
define("LIBXML_ERR_FATAL", 3);
define("LIBXML_ERR_NONE", 0);
define("LIBXML_ERR_WARNING", 1);
define("LIBXML_HTML_NODEFDTD", 4);
define("LIBXML_HTML_NOIMPLIED", 8192);
define("LIBXML_LOADED_VERSION", '20901');
define("LIBXML_NOBLANKS", 256);
define("LIBXML_NOCDATA", 16384);
define("LIBXML_NOEMPTYTAG", 4);
define("LIBXML_NOENT", 2);
define("LIBXML_NOERROR", 32);
define("LIBXML_NONET", 2048);
define("LIBXML_NOWARNING", 64);
define("LIBXML_NOXMLDECL", 2);
define("LIBXML_NSCLEAN", 8192);
define("LIBXML_PARSEHUGE", 524288);
define("LIBXML_PEDANTIC", 128);
define("LIBXML_SCHEMA_CREATE", 1);
define("LIBXML_VERSION", 20901);
define("LIBXML_XINCLUDE", 1024);

class LibXMLError
{
	public /*. int .*/ $code = 0;  # dummy initial value
	public /*. int .*/ $column = 0;  # dummy initial value
	public /*. string .*/ $file;
	public /*. int .*/ $level = 0;  # dummy initial value
	public /*. int .*/ $line = 0;  # dummy initial value
	public /*. string .*/ $message;
}

/*. void .*/ function libxml_set_streams_context(/*. resource .*/ $streams_context){}
/*. boolean .*/ function libxml_use_internal_errors($use_errors = FALSE){}
/*. LibXMLError .*/ function libxml_get_last_error(){}
/*. LibXMLError[int] .*/ function libxml_get_errors(){}
/*. void .*/ function libxml_clear_errors(){}
/*. boolean .*/ function libxml_disable_entity_loader($disable = TRUE){}
