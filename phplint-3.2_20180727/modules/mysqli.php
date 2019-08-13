<?php
/** MySQL Improved Extension.

See: {@link http://www.php.net/manual/en/ref.mysqli.php}
@package mysqli
*/

/*.  require_module 'core'; .*/

define("MYSQLI_ASSOC", 1);
define("MYSQLI_ASYNC", 8);
define("MYSQLI_AUTO_INCREMENT_FLAG", 512);
define("MYSQLI_BINARY_FLAG", 128);
define("MYSQLI_BLOB_FLAG", 16);
define("MYSQLI_BOTH", 3);
define("MYSQLI_CLIENT_CAN_HANDLE_EXPIRED_PASSWORDS", 4194304);
define("MYSQLI_CLIENT_COMPRESS", 32);
define("MYSQLI_CLIENT_FOUND_ROWS", 2);
define("MYSQLI_CLIENT_IGNORE_SPACE", 256);
define("MYSQLI_CLIENT_INTERACTIVE", 1024);
define("MYSQLI_CLIENT_NO_SCHEMA", 16);
define("MYSQLI_CLIENT_SSL", 2048);
define("MYSQLI_CLIENT_SSL_DONT_VERIFY_SERVER_CERT", 64);
define("MYSQLI_CLIENT_SSL_VERIFY_SERVER_CERT", 1073741824);
define("MYSQLI_CURSOR_TYPE_FOR_UPDATE", 2);
define("MYSQLI_CURSOR_TYPE_NO_CURSOR", 0);
define("MYSQLI_CURSOR_TYPE_READ_ONLY", 1);
define("MYSQLI_CURSOR_TYPE_SCROLLABLE", 4);
define("MYSQLI_DATA_TRUNCATED", 101);
define("MYSQLI_DEBUG_TRACE_ENABLED", 0);
define("MYSQLI_ENUM_FLAG", 256);
define("MYSQLI_GROUP_FLAG", 32768);
define("MYSQLI_INIT_COMMAND", 3);
define("MYSQLI_MULTIPLE_KEY_FLAG", 8);
define("MYSQLI_NOT_NULL_FLAG", 1);
define("MYSQLI_NO_DATA", 100);
define("MYSQLI_NO_DEFAULT_VALUE_FLAG", 4096);
define("MYSQLI_NUM", 2);
define("MYSQLI_NUM_FLAG", 32768);
define("MYSQLI_ON_UPDATE_NOW_FLAG", 8192);
define("MYSQLI_OPT_CAN_HANDLE_EXPIRED_PASSWORDS", 29);
define("MYSQLI_OPT_CONNECT_TIMEOUT", 0);
define("MYSQLI_OPT_INT_AND_FLOAT_NATIVE", 201);
define("MYSQLI_OPT_LOCAL_INFILE", 8);
define("MYSQLI_OPT_NET_CMD_BUFFER_SIZE", 202);
define("MYSQLI_OPT_NET_READ_BUFFER_SIZE", 203);
define("MYSQLI_OPT_SSL_VERIFY_SERVER_CERT", 21);
define("MYSQLI_PART_KEY_FLAG", 16384);
define("MYSQLI_PRI_KEY_FLAG", 2);
define("MYSQLI_READ_DEFAULT_FILE", 4);
define("MYSQLI_READ_DEFAULT_GROUP", 5);
define("MYSQLI_REFRESH_BACKUP_LOG", 2097152);
define("MYSQLI_REFRESH_GRANT", 1);
define("MYSQLI_REFRESH_HOSTS", 8);
define("MYSQLI_REFRESH_LOG", 2);
define("MYSQLI_REFRESH_MASTER", 128);
define("MYSQLI_REFRESH_SLAVE", 64);
define("MYSQLI_REFRESH_STATUS", 16);
define("MYSQLI_REFRESH_TABLES", 4);
define("MYSQLI_REFRESH_THREADS", 32);
define("MYSQLI_REPORT_ALL", 255);
define("MYSQLI_REPORT_ERROR", 1);
define("MYSQLI_REPORT_INDEX", 4);
define("MYSQLI_REPORT_OFF", 0);
define("MYSQLI_REPORT_STRICT", 2);
define("MYSQLI_SERVER_PS_OUT_PARAMS", 4096);
define("MYSQLI_SERVER_PUBLIC_KEY", 27);
define("MYSQLI_SERVER_QUERY_NO_GOOD_INDEX_USED", 16);
define("MYSQLI_SERVER_QUERY_NO_INDEX_USED", 32);
define("MYSQLI_SERVER_QUERY_WAS_SLOW", 2048);
define("MYSQLI_SET_CHARSET_DIR", 6);
define("MYSQLI_SET_CHARSET_NAME", 7);
define("MYSQLI_SET_FLAG", 2048);
define("MYSQLI_STMT_ATTR_CURSOR_TYPE", 1);
define("MYSQLI_STMT_ATTR_PREFETCH_ROWS", 2);
define("MYSQLI_STMT_ATTR_UPDATE_MAX_LENGTH", 0);
define("MYSQLI_STORE_RESULT", 0);
define("MYSQLI_STORE_RESULT_COPY_DATA", 16);
define("MYSQLI_TIMESTAMP_FLAG", 1024);
define("MYSQLI_TRANS_COR_AND_CHAIN", 1);
define("MYSQLI_TRANS_COR_AND_NO_CHAIN", 2);
define("MYSQLI_TRANS_COR_NO_RELEASE", 8);
define("MYSQLI_TRANS_COR_RELEASE", 4);
define("MYSQLI_TRANS_START_READ_ONLY", 4);
define("MYSQLI_TRANS_START_READ_WRITE", 2);
define("MYSQLI_TRANS_START_WITH_CONSISTENT_SNAPSHOT", 1);
define("MYSQLI_TYPE_BIT", 16);
define("MYSQLI_TYPE_BLOB", 252);
define("MYSQLI_TYPE_CHAR", 1);
define("MYSQLI_TYPE_DATE", 10);
define("MYSQLI_TYPE_DATETIME", 12);
define("MYSQLI_TYPE_DECIMAL", 0);
define("MYSQLI_TYPE_DOUBLE", 5);
define("MYSQLI_TYPE_ENUM", 247);
define("MYSQLI_TYPE_FLOAT", 4);
define("MYSQLI_TYPE_GEOMETRY", 255);
define("MYSQLI_TYPE_INT24", 9);
define("MYSQLI_TYPE_INTERVAL", 247);
define("MYSQLI_TYPE_JSON", 245);
define("MYSQLI_TYPE_LONG", 3);
define("MYSQLI_TYPE_LONGLONG", 8);
define("MYSQLI_TYPE_LONG_BLOB", 251);
define("MYSQLI_TYPE_MEDIUM_BLOB", 250);
define("MYSQLI_TYPE_NEWDATE", 14);
define("MYSQLI_TYPE_NEWDECIMAL", 246);
define("MYSQLI_TYPE_NULL", 6);
define("MYSQLI_TYPE_SET", 248);
define("MYSQLI_TYPE_SHORT", 2);
define("MYSQLI_TYPE_STRING", 254);
define("MYSQLI_TYPE_TIME", 11);
define("MYSQLI_TYPE_TIMESTAMP", 7);
define("MYSQLI_TYPE_TINY", 1);
define("MYSQLI_TYPE_TINY_BLOB", 249);
define("MYSQLI_TYPE_VAR_STRING", 253);
define("MYSQLI_TYPE_YEAR", 13);
define("MYSQLI_UNIQUE_KEY_FLAG", 4);
define("MYSQLI_UNSIGNED_FLAG", 32);
define("MYSQLI_USE_RESULT", 1);
define("MYSQLI_ZEROFILL_FLAG", 64);

class mysqli_field {
	public /*. string .*/ $name;
	public /*. string .*/ $orgname;
	public /*. string .*/ $table;
	public /*. string .*/ $orgtable;
	public /*. string .*/ $def;
	public /*. int .*/    $max_length = 0;  # dummy initial value
	public /*. int .*/    $length = 0;  # dummy initial value
	public /*. int .*/    $charsetnr = 0;  # dummy initial value
	public /*. int .*/    $flags = 0;  # dummy initial value
	public /*. string .*/ $type;
	public /*. int .*/    $decimals = 0;  # dummy initial value
}


/*. require_module 'spl'; .*/


class mysqli_result implements Iterator {

	public /*. int .*/    $current_field = 0;  # dummy initial value
	public /*. int .*/    $field_count = 0;  # dummy initial value
	public /*. array[int]int .*/ $lengths;
	public /*. int .*/    $num_rows = 0;  # dummy initial value
	public /*. int .*/ $type = 0;  # dummy initial value

	/*. void .*/ function close(){}
	/*. bool .*/ function data_seek(/*. int .*/ $offset){}
	/*. array[string]string .*/ function fetch_array(/*. args .*/){}
	/*. array[]string .*/ function fetch_assoc(){}
	/*. mysqli_field .*/ function fetch_field(){}
	/*. array[int]mysqli_field .*/ function fetch_fields(){}
	/*. mysqli_field .*/ function fetch_field_direct(/*. int .*/ $offset){}
	/*. object .*/ function fetch_object(){}
	/*. array[int]string .*/ function fetch_row(){}
	/*. int .*/ function field_seek(/*. int .*/ $fieldnr){}
	/*. void .*/ function free(){}
	/*. void .*/ function free_result(){}
	/*. array .*/ function fetch_all(/*. args .*/){}

	# Implements Iterator:
	/*. void .*/ function rewind(){}
	/*. bool .*/ function valid(){}
	/*. mixed .*/ function key(){}
	/*. mysqli_result .*/ function current(){}
	/*. void .*/ function next(){}
}


class mysqli_stmt {

	public /*. int .*/     $affected_rows = 0;  # dummy initial value
	public /*. int .*/     $errno = 0;  # dummy initial value
	public /*. mixed[string] .*/ $error_list;
	public /*. string .*/  $error;
	public /*. int .*/     $field_count = 0;  # dummy initial value
	public /*. int .*/     $insert_id = 0;  # dummy initial value
	public /*. int .*/     $num_rows = 0;  # dummy initial value
	public /*. int .*/     $param_count = 0;  # dummy initial value
	public /*. string .*/  $sqlstate;

	/*. int .*/ function attr_get(/*. int .*/ $attr){}
	/*. bool .*/ function attr_set(/*. int .*/ $attr, /*. int .*/ $mode){}
	/*. bool .*/ function bind_param(/*. string .*/ $types, /*. mixed .*/ $variable /*., args .*/){}
	/*. bool .*/ function bind_result(/*. mixed .*/ $var_ /*., args .*/){}
	/*. bool .*/ function close(){}
	/*. void .*/ function data_seek(/*. int .*/ $offset){}
	/*. bool .*/ function execute(){}
	/*. bool .*/ function fetch(){}
	/*. void .*/ function free_result(){}
	/*. mysqli_result .*/ function get_result(){}
	/*. object .*/ function get_warnings(/*. mysqli_stmt .*/ $stmt){}
	/*. bool .*/ function more_results(){}
	/*. bool .*/ function next_result(){}
	/*. int  .*/ function num_rows(){}
	/*. bool .*/ function prepare(/*. string .*/ $query){}
	/*. bool .*/ function reset(){}
	/*. mysqli_result .*/ function result_metadata(){}
	/*. bool .*/ function send_long_data(/*. int .*/ $param_nr, /*. string .*/ $data){}
	/*. bool .*/ function store_result(){}
}


class mysqli_warning {
	public /*. string .*/ $message;
	public /*. string .*/ $sqlstate;
	public $errno = 0;
	public /*. void .*/ function next (){} /* what's this? */
}



class mysqli {

	public /*. int .*/    $affected_rows = 0;  # dummy initial value
	public /*. string .*/ $client_info;
	public /*. int .*/    $client_version = 0;  # dummy initial value
	public /*. int .*/    $connect_errno = 0;  # dummy initial value
	public /*. string .*/ $connect_error;
	public /*. int .*/    $errno = 0;  # dummy initial value
	public /*. mixed[string] .*/ $error_list;
	public /*. string .*/ $error;
	public /*. int .*/    $field_count = 0;  # dummy initial value
	public /*. string .*/ $host_info;
	public /*. string .*/ $info;
	public /*. mixed .*/  $insert_id;
	public /*. string .*/ $protocol_version;
	public /*. string .*/ $server_info;
	public /*. int .*/    $server_version = 0;  # dummy initial value
	public /*. string .*/ $sqlstate;
	public /*. int .*/    $thread_id = 0;  # dummy initial value
	public /*. int .*/    $warning_count = 0;  # dummy initial value

	/*. void .*/ function __construct(
		$host = "ini_get('mysqli.default_host')",
		$username = "ini_get('mysqli.default_user')",
		$passwd = "ini_get('mysqli.default_pw')",
		$dbname = "",
		$port = 0 /* ini_get("mysqli.default_port") */,
		$socket = "ini_get('mysqli.default_socket')")
		/*. triggers E_WARNING .*/ {}
	/*. bool .*/ function autocommit(/*. bool .*/ $mode){}
	/*. bool .*/ function begin_transaction($flags = 0, /*. string .*/ $name = NULL){}
	/*. bool .*/ function change_user(/*. string .*/ $user, /*. string .*/ $password, /*. string .*/ $database)
		/*. triggers E_WARNING .*/ {}
	/*. string .*/ function character_set_name(){}
	/*. bool .*/ function close(){}
	/*. bool .*/ function commit(){}
	/** @deprecated Not documented in the manual. */
	/*. mixed .*/ function connect(/*. args .*/){}
	/*. void .*/ function debug(/*. string .*/ $debug){}
	/*. bool .*/ function dump_debug_info(){}
	/*. object .*/ function get_charset(){}
	/*. string .*/ function get_client_info(){}
	/*. mysqli_warning .*/ function get_warnings(){}
	/*. string .*/ function get_host_info(){}
	/*. string .*/ function get_server_info(){}
	/*. int .*/ function get_server_version(){}
	/*. mysqli .*/ function init(){}
	/*. string .*/ function info(){}
	/*. bool .*/ function kill(/*. int .*/ $processid){}
	/*. bool .*/ function multi_query(/*. string .*/ $query)
		/*. triggers E_WARNING .*/ {}
	/*. bool .*/ function more_results(){}
	/*. bool .*/ function next_result(/*. mysqli .*/ $link){}
	/*. bool .*/ function options(/*. int .*/ $flags, /*. mixed .*/ $values){}
	/*. bool .*/ function ping(){}
	static /*. int .*/ function poll(array &$read, array &$error, array &$reject, /*. int .*/ $sec , $usec = 0){}
	/*. mysqli_stmt .*/ function prepare(/*. string .*/ $query)
		/*. triggers E_WARNING .*/ {}
	/*. mixed .*/ function query(/*. string .*/ $query /*., args .*/)
		/*. triggers E_WARNING .*/ {}
	/*. bool .*/ function real_connect(/*. args .*/){}
	/*. string .*/ function escape_string(/*. string .*/ $escapestr){}
	/*. bool .*/ function real_query(/*. string .*/ $query){}
	/*. mysqli_result .*/ function reap_async_query(){}
	/*. string .*/ function real_escape_string(/*. string .*/ $escapestr){}
	/*. bool .*/ function refresh(/*. int .*/ $options){}
	/*. bool .*/ function release_savepoint(/*. string .*/ $name){}
	/*. bool .*/ function rollback(){}
	/*. int .*/ function rpl_query_type(/*. string .*/ $query){}
	/*. bool .*/ function savepoint(/*. string .*/ $name){}
	/*. bool .*/ function select_db(/*. string .*/ $dbname){}
	/*. bool .*/ function send_query(/*. string .*/ $query){}
	/*. bool .*/ function set_charset(/*. string .*/ $charset){}
	/** @deprecated Not documented in the manual. */
	/*. mixed .*/ function set_opt(/*. args .*/){}
	/*. bool .*/ function ssl_set(/*. string .*/ $key ,/*. string .*/ $cert ,/*. string .*/ $ca ,/*. string .*/ $capath ,/*. string .*/ $cipher){}
	/*. string .*/ function stat(){}
	/*. mysqli_stmt .*/ function stmt_init(){}
	/*. mysqli_result .*/ function store_result(){}
	/*. bool .*/ function thread_safe(){}
	/*. mysqli_result .*/ function use_result(){}
	/*. array[string]int .*/ function get_connection_stats(){}
}


/*. mysqli .*/ function mysqli_connect( /*. args .*/)
		/*. triggers E_WARNING .*/ {}
/*. mysqli .*/ function mysqli_embedded_connect(){}
/*. int .*/ function mysqli_connect_errno(){}
/*. string .*/ function mysqli_connect_error(){}
/*. array[]string .*/ function mysqli_fetch_array(/*. mysqli_result .*/ $result /*., args .*/){}
/*. array[string]string .*/ function mysqli_fetch_assoc(/*. mysqli_result .*/ $result){}
/*. object .*/ function mysqli_fetch_object(/*. mysqli_result .*/ $result){}
/*. bool .*/ function mysqli_multi_query(/*. mysqli .*/ $link, /*. string .*/ $query)
		/*. triggers E_WARNING .*/ {}
/*. mixed .*/ function mysqli_query(/*. mysqli .*/ $link, /*. string .*/ $query /*., args .*/)
		/*. triggers E_WARNING .*/ {}
/*. int .*/ function mysqli_affected_rows(/*. mysqli .*/ $link){}
/*. bool .*/ function mysqli_autocommit(/*. mysqli .*/ $link, /*. bool .*/ $mode){}
/*. int .*/ function mysqli_stmt_attr_get(/*. mysqli_stmt .*/ $stmt, /*. int .*/ $attr){}
/*. bool .*/ function mysqli_stmt_attr_set(/*. mysqli_stmt .*/ $stmt, /*. int .*/ $attr, /*. int .*/ $mode){}
/*. mysqli_result .*/ function mysqli_stmt_get_result(/*. mysqli_stmt .*/ $stmt){}
/*. bool .*/ function mysqli_stmt_bind_param(/*. mysqli_stmt .*/ $stmt, /*. string .*/ $types, /*. mixed .*/ $variable /*., args .*/){}
/*. bool .*/ function mysqli_stmt_bind_result(/*. mysqli_stmt .*/ $stmt, /*. mixed .*/ $var_ /*., args .*/){}
/*. bool .*/ function mysqli_change_user(/*. mysqli .*/ $link, /*. string .*/ $user, /*. string .*/ $password, /*. string .*/ $database){}
/*. string .*/ function mysqli_character_set_name(/*. mysqli .*/ $link){}
/*. bool .*/ function mysqli_close(/*. mysqli .*/ $link){}
/*. bool .*/ function mysqli_commit(/*. mysqli .*/ $link){}
/*. bool .*/ function mysqli_data_seek(/*. mysqli_result .*/ $result, /*. int .*/ $offset){}
/*. void .*/ function mysqli_debug(/*. string .*/ $debug){}
/*. bool .*/ function mysqli_dump_debug_info(/*. mysqli .*/ $link){}
/*. int .*/ function mysqli_errno(/*. mysqli .*/ $link){}
/*. string .*/ function mysqli_error(/*. mysqli .*/ $link){}
/*. bool .*/ function mysqli_stmt_execute(/*. mysqli_stmt .*/ $stmt){}
/*. mixed .*/ function mysqli_stmt_fetch(/*. mysqli_stmt .*/ $stmt){}
/*. mysqli_field .*/ function mysqli_fetch_field(/*. mysqli_result .*/ $result){}
/*. array[int]mysqli_field .*/ function mysqli_fetch_fields(/*. mysqli_result .*/ $result){}
/*. mysqli_field .*/ function mysqli_fetch_field_direct(/*. mysqli_result .*/ $result, /*. int .*/ $offset)/*. triggers E_WARNING .*/{}
/*. array[int]int .*/ function mysqli_fetch_lengths(/*. mysqli_result .*/ $result){}
/*. array .*/ function mysqli_fetch_row(/*. mysqli_result .*/ $result){}
/*. int .*/ function mysqli_field_count(/*. mysqli .*/ $link){}
/*. int .*/ function mysqli_field_seek(/*. mysqli_result .*/ $result, /*. int .*/ $fieldnr){}
/*. int .*/ function mysqli_field_tell(/*. mysqli_result .*/ $result){}
/*. void .*/ function mysqli_free_result(/*. mysqli_result .*/ $result){}
/*. string .*/ function mysqli_get_client_info(){}
/*. int .*/ function mysqli_get_client_version(){}
/*. string .*/ function mysqli_get_host_info(/*. mysqli .*/ $link){}
/*. int .*/ function mysqli_get_proto_info(/*. mysqli .*/ $link){}
/*. string .*/ function mysqli_get_server_info(/*. mysqli .*/ $link){}
/*. int .*/ function mysqli_get_server_version(/*. mysqli .*/ $link){}
/*. string .*/ function mysqli_info(/*. mysqli .*/ $link){}
/*. resource .*/ function mysqli_init(){}
/*. int .*/ function mysqli_insert_id(/*. mysqli .*/ $link){}
/*. bool .*/ function mysqli_kill(/*. mysqli .*/ $link, /*. int .*/ $processid){}
/*. bool .*/ function mysqli_set_local_infile_handler(/*. mysqli .*/ $link, /*. mixed .*/ $read_func){}
/*. bool .*/ function mysqli_more_results(/*. mysqli .*/ $link){}
/*. bool .*/ function mysqli_next_result(/*. mysqli .*/ $link){}
/*. int .*/ function mysqli_num_fields(/*. mysqli_result .*/ $result){}
/*. int .*/ function mysqli_num_rows(/*. mysqli_result .*/ $result){}
/*. bool .*/ function mysqli_options(/*. mysqli .*/ $link, /*. int .*/ $flags, /*. mixed .*/ $values){}
/*. bool .*/ function mysqli_ping(/*. mysqli .*/ $link){}
/*. mysqli_stmt .*/ function mysqli_prepare(/*. mysqli .*/ $link, /*. string .*/ $query)
		/*. triggers E_WARNING .*/ {}
/*. bool .*/ function mysqli_real_connect(/*. mysqli .*/ $link /*., args .*/){}
/*. bool .*/ function mysqli_real_query(/*. mysqli .*/ $link, /*. string .*/ $query)
		/*. triggers E_WARNING .*/ {}
/*. string .*/ function mysqli_real_escape_string(/*. mysqli .*/ $link, /*. string .*/ $escapestr){}
/*. bool .*/ function mysqli_rollback(/*. mysqli .*/ $link){}
/*. bool .*/ function mysqli_server_init(){}
/*. void .*/ function mysqli_server_end(){}
/*. int .*/ function mysqli_stmt_affected_rows(/*. mysqli_stmt .*/ $stmt){}
/*. bool .*/ function mysqli_stmt_close(/*. mysqli_stmt .*/ $stmt){}
/*. void .*/ function mysqli_stmt_data_seek(/*. mysqli_stmt .*/ $stmt, /*. int .*/ $offset){}
/*. int .*/ function mysqli_stmt_field_count(/*. mysqli_stmt .*/ $stmt){}
/*. void .*/ function mysqli_stmt_free_result(/*. mysqli_stmt .*/ $stmt){}
/*. int .*/ function mysqli_stmt_insert_id(/*. mysqli_stmt .*/ $stmt){}
/*. int .*/ function mysqli_stmt_param_count(/*. mysqli_stmt .*/ $stmt){}
/*. bool .*/ function mysqli_stmt_reset(/*. mysqli_stmt .*/ $stmt){}
/*. int .*/ function mysqli_stmt_num_rows(/*. mysqli_stmt .*/ $stmt){}
/*. bool .*/ function mysqli_select_db(/*. mysqli .*/ $link, /*. string .*/ $dbname){}
/*. string .*/ function mysqli_sqlstate(/*. mysqli .*/ $link){}
/*. bool .*/ function mysqli_ssl_set(/*. mysqli .*/ $link ,/*. string .*/ $key ,/*. string .*/ $cert ,/*. string .*/ $ca ,/*. string .*/ $capath ,/*. string .*/ $cipher){}
/*. string .*/ function mysqli_stat(/*. mysqli .*/ $link){}
/*. int .*/ function mysqli_stmt_errno(/*. mysqli_stmt .*/ $stmt){}
/*. string .*/ function mysqli_stmt_error(/*. mysqli_stmt .*/ $stmt){}
/*. mysqli_stmt .*/ function mysqli_stmt_init(/*. mysqli .*/ $link){}
/*. bool .*/ function mysqli_stmt_prepare(/*. mysqli_stmt .*/ $stmt, /*. string .*/ $query){}
/*. mysqli_stmt .*/ function mysqli_stmt_result_metadata(/*. mysqli_stmt .*/ $stmt){}
/*. bool .*/ function mysqli_stmt_store_result(/*. mysqli_stmt .*/ $stmt){}
/*. string .*/ function mysqli_stmt_sqlstate(/*. mysqli_stmt .*/ $stmt){}
/*. mysqli_result .*/ function mysqli_store_result(/*. mysqli .*/ $link){}
/*. int .*/ function mysqli_thread_id(/*. mysqli .*/ $link){}
/*. bool .*/ function mysqli_thread_safe(){}
/*. mysqli_result .*/ function mysqli_use_result(/*. mysqli .*/ $link){}
/*. int .*/ function mysqli_warning_count(/*. mysqli .*/ $link){}
/** @deprecated Removed. */ 
/*. void .*/ function mysqli_disable_reads_from_master(/*. mysqli .*/ $link){}
/** @deprecated Removed. */ 
/*. void .*/ function mysqli_disable_rpl_parse(/*. mysqli .*/ $link){}
/** @deprecated Removed. */ 
/*. void .*/ function mysqli_enable_reads_from_master(/*. mysqli .*/ $link){}
/** @deprecated Removed. */ 
/*. void .*/ function mysqli_enable_rpl_parse(/*. mysqli .*/ $link){}
/** @deprecated Removed. */ 
/*. bool .*/ function mysqli_master_query(/*. mysqli .*/ $link, /*. string .*/ $query){}
/** @deprecated Removed. */ 
/*. int .*/ function mysqli_rpl_parse_enabled(/*. mysqli .*/ $link){}
/** @deprecated Removed. */ 
/*. bool .*/ function mysqli_rpl_probe(/*. mysqli .*/ $link){}
/** @deprecated Removed. */ 
/*. int .*/ function mysqli_rpl_query_type(/*. string .*/ $query){}
/** @deprecated Removed. */ 
/*. bool .*/ function mysqli_send_query(/*. mysqli .*/ $link, /*. string .*/ $query){}
/** @deprecated Removed. */ 
/*. bool .*/ function mysqli_slave_query(/*. mysqli .*/ $link, /*. string .*/ $query){}
/*. bool .*/ function mysqli_set_charset (/*. mysqli .*/ $link, /*. string .*/ $charset){}
/*. array .*/ function mysqli_fetch_all(/*. mysqli_result .*/ $result /*., args .*/){}
/*. array[string]int .*/ function mysqli_get_connection_stats(/*. mysqli .*/ $link){}

class mysqli_driver {
	public /*. string .*/ $client_info;
	public /*. string .*/ $client_version;
	public /*. string .*/ $driver_version;
	public /*. string .*/ $embedded;
	public $reconnect = FALSE;
	public $report_mode = 0;
	/*. void .*/ function embedded_server_end(){}
	/*. bool .*/ function embedded_server_start(/*. bool .*/ $start, /*. array .*/ $arguments, /*. array .*/ $groups){}
}

/*. unchecked .*/ class mysqli_sql_exception extends RuntimeException {
	protected /*. string .*/ $sqlstate;
}
