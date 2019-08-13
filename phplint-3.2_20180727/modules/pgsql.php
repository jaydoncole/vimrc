<?php
/** PostgreSQL Functions.

See: {@link http://www.php.net/manual/en/ref.pgsql.php}
@package pgsql
*/

/*. require_module 'core'; .*/

define("PGSQL_ASSOC", 1);
define("PGSQL_BAD_RESPONSE", 5);
define("PGSQL_BOTH", 3);
define("PGSQL_COMMAND_OK", 1);
define("PGSQL_CONNECTION_AUTH_OK", 5);
define("PGSQL_CONNECTION_AWAITING_RESPONSE", 4);
define("PGSQL_CONNECTION_BAD", 1);
define("PGSQL_CONNECTION_MADE", 3);
define("PGSQL_CONNECTION_OK", 0);
define("PGSQL_CONNECTION_SETENV", 6);
define("PGSQL_CONNECTION_STARTED", 2);
define("PGSQL_CONNECT_ASYNC", 4);
define("PGSQL_CONNECT_FORCE_NEW", 2);
define("PGSQL_CONV_FORCE_NULL", 4);
define("PGSQL_CONV_IGNORE_DEFAULT", 2);
define("PGSQL_CONV_IGNORE_NOT_NULL", 8);
define("PGSQL_COPY_IN", 4);
define("PGSQL_COPY_OUT", 3);
define("PGSQL_DIAG_CONTEXT", 87);
define("PGSQL_DIAG_INTERNAL_POSITION", 112);
define("PGSQL_DIAG_INTERNAL_QUERY", 113);
define("PGSQL_DIAG_MESSAGE_DETAIL", 68);
define("PGSQL_DIAG_MESSAGE_HINT", 72);
define("PGSQL_DIAG_MESSAGE_PRIMARY", 77);
define("PGSQL_DIAG_SEVERITY", 83);
define("PGSQL_DIAG_SOURCE_FILE", 70);
define("PGSQL_DIAG_SOURCE_FUNCTION", 82);
define("PGSQL_DIAG_SOURCE_LINE", 76);
define("PGSQL_DIAG_SQLSTATE", 67);
define("PGSQL_DIAG_STATEMENT_POSITION", 80);
define("PGSQL_DML_ASYNC", 1024);
define("PGSQL_DML_ESCAPE", 4096);
define("PGSQL_DML_EXEC", 512);
define("PGSQL_DML_NO_CONV", 256);
define("PGSQL_DML_STRING", 2048);
define("PGSQL_EMPTY_QUERY", 0);
define("PGSQL_ERRORS_DEFAULT", 1);
define("PGSQL_ERRORS_TERSE", 0);
define("PGSQL_ERRORS_VERBOSE", 2);
define("PGSQL_FATAL_ERROR", 7);
define("PGSQL_LIBPQ_VERSION", '9.4beta2');
define("PGSQL_LIBPQ_VERSION_STR", 'PostgreSQL 9.4beta2 on i686-pc-linux-gnu, compiled by gcc (GCC) 4.8.2, 32-bit');
define("PGSQL_NONFATAL_ERROR", 6);
define("PGSQL_NOTICE_ALL", 2);
define("PGSQL_NOTICE_CLEAR", 3);
define("PGSQL_NOTICE_LAST", 1);
define("PGSQL_NUM", 2);
define("PGSQL_POLLING_ACTIVE", 4);
define("PGSQL_POLLING_FAILED", 0);
define("PGSQL_POLLING_OK", 3);
define("PGSQL_POLLING_READING", 1);
define("PGSQL_POLLING_WRITING", 2);
define("PGSQL_SEEK_CUR", 1);
define("PGSQL_SEEK_END", 2);
define("PGSQL_SEEK_SET", 0);
define("PGSQL_STATUS_LONG", 1);
define("PGSQL_STATUS_STRING", 2);
define("PGSQL_TRANSACTION_ACTIVE", 1);
define("PGSQL_TRANSACTION_IDLE", 0);
define("PGSQL_TRANSACTION_INERROR", 3);
define("PGSQL_TRANSACTION_INTRANS", 2);
define("PGSQL_TRANSACTION_UNKNOWN", 4);
define("PGSQL_TUPLES_OK", 2);

/*. int    .*/ function pg_affected_rows(/*. resource .*/ $result){}
/*. bool   .*/ function pg_cancel_query(/*. resource .*/ $connection){}
/*. string .*/ function pg_client_encoding( /*. args .*/){}
/*. bool   .*/ function pg_close(/*. args .*/){}
/*. resource .*/ function pg_connect(/*.string.*/ $conn_str /*., args .*/)
	/*. triggers E_WARNING .*/{}
/*. bool   .*/ function pg_connection_busy(/*. resource .*/ $connection){}
/*. bool   .*/ function pg_connection_reset(/*. resource .*/ $connection){}
/*. int    .*/ function pg_connection_status(/*. resource .*/ $connnection){}
/*. array  .*/ function pg_convert(/*. resource .*/ $db, /*. string .*/ $table, /*. array .*/ $values /*., args .*/){}
/*. bool   .*/ function pg_copy_from(/*. resource .*/ $connection, /*. string .*/ $table_name , /*. array .*/ $rows /*., args .*/){}
/*. array  .*/ function pg_copy_to(/*. resource .*/ $connection, /*. string .*/ $table_name /*., args .*/){}
/*. string .*/ function pg_dbname( /*. args .*/){}
/*. mixed  .*/ function pg_delete(/*. resource .*/ $db, /*. string .*/ $table, /*. array .*/ $ids /*., args .*/){}
/*. bool   .*/ function pg_end_copy( /*. args .*/){}
/*. string .*/ function pg_escape_bytea(/*.args.*/){}
/*. string .*/ function pg_escape_identifier(/*.args.*/){}
/*. string .*/ function pg_escape_literal(/*.args.*/){}
/*. string .*/ function pg_escape_string(/*.args.*/){}
/*. resource .*/ function pg_execute(/*. resource .*/ $connection, /*. string .*/ $stmtname, /*. array[int]string .*/ $params)/*. triggers E_WARNING .*/{}
/*. array[int]string .*/ function pg_fetch_all_columns(/*. resource .*/ $result, $column = 0){}
/*. array[int][string]string .*/ function pg_fetch_all(/*. resource .*/ $result){}
/*. array[]string .*/ function pg_fetch_array(/*.resource.*/ $res /*., args .*/){}
/*. array[string]string .*/ function pg_fetch_assoc(/*.resource.*/ $res /*., args .*/){}
/*. object .*/ function pg_fetch_object(/*. resource .*/ $result /*., args .*/){}
/*. string .*/ function pg_fetch_result(/*.resource.*/ $res /*., args .*/){}
/*. array[int]string .*/ function pg_fetch_row(/*. resource .*/ $result /*., args .*/){}
/*. int    .*/ function pg_field_is_null(/*. resource .*/ $result /*., args .*/){}
/*. string .*/ function pg_field_name(/*. resource .*/ $result, /*. int .*/ $field_number)/*. triggers E_WARNING .*/{}
/*. int    .*/ function pg_field_num(/*. resource .*/ $result, /*. string .*/ $field_name){}
/*. int    .*/ function pg_field_prtlen (/*. resource .*/ $result, /*. int .*/ $row_number, /*. mixed .*/ $field_name_or_number){}
/*. int    .*/ function pg_field_size(/*. resource .*/ $result, /*. int .*/ $field_number){}
/*. string .*/ function pg_field_type(/*. resource .*/ $result, /*. int .*/ $field_number){}
/*. int    .*/ function pg_field_type_oid(/*. resource .*/ $result, /*. int .*/ $field_number){}
/*. bool   .*/ function pg_free_result(/*.resource.*/ $res){}
/*. array  .*/ function pg_get_notify( /*. args .*/){}
/*. int    .*/ function pg_get_pid( /*. args .*/){}
/*. resource .*/ function pg_get_result(/*. resource .*/ $connection){}
/*. string .*/ function pg_host( /*. args .*/){}
/*. mixed  .*/ function pg_insert(/*. resource .*/ $db, /*. string .*/ $table, /*. array[string]string .*/ $values, $options = PGSQL_DML_EXEC){}
/*. string .*/ function pg_last_error(/*. args .*/){}
/*. string .*/ function pg_last_notice(/*.resource.*/ $res){}
/*. string .*/ function pg_last_oid(/*. resource .*/ $result){}
/*. bool   .*/ function pg_lo_close(/*. resource .*/ $large_object){}
/*. int    .*/ function pg_lo_create( /*. args .*/){}
/*. bool   .*/ function pg_lo_export( /*. args .*/){}
/*. int    .*/ function pg_lo_import( /*. args .*/){}
/*. resource .*/ function pg_lo_open( /*. args .*/){}
/*. int    .*/ function pg_lo_read_all(/*. resource .*/ $large_object){}
/*. string .*/ function pg_lo_read(/*. resource .*/ $large_object /*., args .*/){}
/*. bool   .*/ function pg_lo_seek(/*. resource .*/ $large_object, /*. int .*/ $offset /*., args .*/){}
/*. int    .*/ function pg_lo_tell(/*. resource .*/ $large_object){}
/*. bool   .*/ function pg_lo_unlink( /*. args .*/){}
/*. int .*/ function pg_lo_write(/*. resource .*/ $large_object, /*. string .*/ $buf /*., args .*/){}
/*. array[string][string]mixed .*/ function pg_meta_data(/*. resource .*/ $db, /*. string .*/ $table)/*. triggers E_WARNING .*/{}
/*. int    .*/ function pg_num_fields(/*. resource .*/ $result){}
/*. int    .*/ function pg_num_rows(/*.resource.*/ $res){}
/*. int    .*/ function pg_numrows(/*.resource.*/ $res){}
/*. string .*/ function pg_options( /*. args .*/){}
/*. string .*/ function pg_parameter_status( /*. args .*/){}
/*. resource .*/ function pg_pconnect(/*. args .*/){}
/*. bool   .*/ function pg_ping( /*. args .*/){}
/*. int    .*/ function pg_port( /*. args .*/){}
/*. resource .*/ function pg_prepare (/*. resource .*/ $connection, /*. string .*/ $stmtname, /*. string .*/ $query)/*. triggers E_WARNING .*/{}
/*. bool   .*/ function pg_put_line( /*. args .*/){}
/*. resource .*/ function pg_query(/*.resource.*/ $conn, /*.string.*/ $query)
	/*. triggers E_WARNING .*/{}
/*. resource .*/ function pg_query_params(/*. resource .*/ $connection, /*. string .*/ $query, /*. array[int]string .*/ $params)/*. triggers E_WARNING .*/{}
/*. string .*/ function pg_result_error_field(/*.resource.*/ $res){}
/*. string .*/ function pg_result_error(/*. resource .*/ $result){}
/*. bool   .*/ function pg_result_seek(/*. resource .*/ $result, /*. int .*/ $offset){}
/*. mixed  .*/ function pg_result_status(/*. resource .*/ $result /*., args .*/){}
/*. mixed  .*/ function pg_select(/*. resource .*/ $db, /*. string .*/ $table, /*. array .*/ $ids /*., args .*/){}
/*. bool   .*/ function pg_send_execute(/*. resource .*/ $connection, /*. string .*/ $stmtname, /*. array[int]string .*/ $params)/*. triggers E_WARNING .*/{}
/*. bool   .*/ function pg_send_prepare(/*. resource .*/ $connection, /*. string .*/ $stmtname, /*. string .*/ $query)/*. triggers E_WARNING .*/{}
/*. bool   .*/ function pg_send_query(/*. resource .*/ $connection, /*. string .*/ $qeury){}
/*. bool   .*/ function pg_send_query_params(/*. resource .*/ $connection, /*. string .*/ $query, /*. array[int]string .*/ $params)/*. triggers E_WARNING .*/{}
/*. int    .*/ function pg_set_client_encoding( /*. args .*/){}
/*. int    .*/ function pg_set_error_verbosity(/*. resource .*/ $connection, /*. int .*/ $verbosity){}
/*. bool   .*/ function pg_trace(/*. string .*/ $filename /*., args .*/){}
/*. int    .*/ function pg_transaction_status(/*. resource .*/ $connection){}
/*. string .*/ function pg_tty( /*. args .*/){}
/*. string .*/ function pg_unescape_bytea(/*. string .*/ $data)/*. triggers E_WARNING .*/{}
/*. bool   .*/ function pg_untrace( /*. args .*/){}
/*. mixed  .*/ function pg_update(/*. resource .*/ $db, /*. string .*/ $table, /*. array .*/ $fields, /*. array .*/ $ids /*., args .*/)
	/*. triggers E_WARNING .*/{}
/*. array[string]mixed  .*/ function pg_version( /*. args .*/){}
/*. mixed .*/ function pg_field_table(/*. resource .*/ $result, /*. int .*/ $field_number /*. , args .*/){}
