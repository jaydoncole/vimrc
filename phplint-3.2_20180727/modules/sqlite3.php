<?php
/** SQLite3 Functions.

See: {@link http://www.php.net/manual/en/book.sqlite3.php}
@package sqlite3
*/

/*. require_module 'core'; .*/

define('SQLITE3_ASSOC', 1);
define('SQLITE3_NUM', 2);
define('SQLITE3_BOTH', 3);
define('SQLITE3_INTEGER', 1);
define('SQLITE3_FLOAT', 2);
define('SQLITE3_TEXT', 3);
define('SQLITE3_BLOB', 4);
define('SQLITE3_NULL', 5);
define('SQLITE3_OPEN_READONLY', 1);
define('SQLITE3_OPEN_READWRITE', 2);
define('SQLITE3_OPEN_CREATE', 4);

class SQLite3Result
{
	/**
	 * Returns the name of the column.
	 * 
	 *    BEWARE: if the index of the requested column is out of the
	 *    allowed range, false is returned instead; no error and no
	 *    exception whatsoever is raised, so be careful.
	 * 
	 * @param int $column_number Number of the requested column, in the range
	 * from 0 to numColumns()-1.
	 * @return string Name of the column.
	 */
    function columnName($column_number){}
	
	/**
	 * Returns the type of the specified column of the latest fetched row.
	 * 
	 *    BEWARE: if there is no a currently fetched row, this function
	 *    always returns SQLITE3_NULL. This also happens for a just got
	 *    result set, for a just reset result set, and for a empty result set.
	 *    Note that there is no way to retrieve the type of a column
	 *    if the result set is empty; moreover, the type of a given column
	 *    changes depending on its current value (any value can be NULL, for
	 *    example). So, this function is pretty useless.
	 * 
	 *    BEWARE: if the index of the requested column is out of the
	 *    allowed range, SQLITE3_NULL is returned instead; no error and no
	 *    exception whatsoever is raised, so be careful.
	 * 
	 * @param int $column_number Number of the requested column, in the range
	 * from 0 to numColumns()-1.
	 * @return int One of SQLITE3_INTEGER, SQLITE3_FLOAT, SQLITE3_TEXT,
	 * SQLITE3_BLOB, or SQLITE3_NULL.
	 */
    function columnType($column_number){}
	
	/**
	 * Returns the next row from the result set.
	 * @param int $mode Set the type of the keys of the returned array:
	 * zero-based integer (SQLITE3_NUM), column name (SQLITE3_ASSOC),
	 * or both (SQLITE3_BOTH, default).
	 * @return array Next row from the result set. FALSE is returned at the end
	 * of the result set; a further call to this function automatically resets
	 * and restarts returning the first row again.
	 * Values are encoded as follows: integers are mapped to integer if they fit
	 * into the range PHP_INT_MIN..PHP_INT_MAX, and to string otherwise;
	 * floats are mapped to float; NULL values are mapped to null; strings and
	 * blobs are mapped to string. 
	 */
    function fetchArray($mode = SQLITE3_BOTH){}
	
    /*. boolean .*/ function finalize(){}
    /*. integer .*/ function numColumns(){}
    /*. boolean .*/ function reset(){}
}

class SQLite3Stmt
{
    /*. boolean .*/ function bindParam(/*. mixed .*/ $sql_param, /*. mixed .*/ $param, /*. integer .*/ $type = 0){}
	
	/**
	 * Bind a placeholder to a value in the prepared statement. Placeholder that
	 * are not bound are assumed to be NULL.
	 * @param mixed $param Name of the placeholder (string) or integer number
	 * of the placeholder. If the name does not appear in the prepared statement,
	 * the function returns false and no binding is performed. If the integer
	 * number does not appear in the prepared statement, this function returns
	 * true anyway, but no binding is performed!
	 * @param mixed $value A NULL value always passes as a verbatim SQL null;
	 * any other type is converted to the specified type indicated in the
	 * next parameter.
	 * @param int $type Type of the parameter, one of SQLITE3_INTEGER,
	 * SQLITE3_FLOAT, SQLITE3_TEXT (default), SQLITE3_BLOB, or SQLITE3_NULL.
	 * @return boolean True if binding succeeded. The actual conversion of the
	 * value to the internal representation of SQLite take place only at
	 * execution time. Pay attention to always check this function returns true
	 * because it does not generate error nor exception.
	 */
    function bindValue($param, $value, $type = SQLITE3_TEXT){}
	
    /*. boolean .*/ function clear(){}
	
    /*. boolean .*/ function close(){}
	
	/**
	 * Performes this query.
	 */
    /*. SQLite3Result .*/ function execute()
		/*. triggers E_WARNING, E_RECOVERABLE_ERROR throws Exception .*/{}
		
    /*. integer .*/ function paramCount(){}
	
	/*. boolean .*/ function readOnly(){}
	
    /*. boolean .*/ function reset(){}
}

class SQLite3
{
    /*. boolean .*/ function busyTimeout(/*. integer .*/ $msecs){}
	
    /*. integer .*/ function changes(){}
	
    /*. boolean .*/ function close(){}
	
    /*. void .*/ function __construct(/*. string .*/ $filename, $flags = SQLITE3_OPEN_READWRITE | SQLITE3_OPEN_CREATE, /*. string .*/ $encryption_key = NULL)/*. throws Exception .*/{}
	
    /*. boolean .*/ function createAggregate(/*. string .*/ $name, /*. mixed .*/ $step_callback, /*. mixed .*/ $final_callback, /*. integer .*/ $argument_count = -1){}
	
	/*. boolean .*/ function createCollation(/*. string .*/ $name, /*. mixed .*/ $callback ){}
	
    /*. boolean .*/ function createFunction(/*. string .*/ $name, /*. mixed .*/ $callback, /*. integer .*/ $argument_count = -1, $flags = -1){}
	
	/**
	 * Set errors or exception generation. By default, E_WARNING error is
	 * triggered on error; by setting true, Exception exception are thrown instead.
	 */
	/*. boolean .*/ function enableExceptions($enableExceptions = false){}
	
    static /*. string  .*/ function escapeString(/*. string .*/ $value){}
    /*. boolean .*/ function exec(/*. string .*/ $query)
		/*. triggers E_WARNING throws Exception .*/{}
		
    /*. integer .*/ function lastErrorCode(){}
	
    /*. string  .*/ function lastErrorMsg(){}
	
    /*. integer .*/ function lastInsertRowID(){}
	
    /*. boolean .*/ function loadExtension(/*. string .*/ $shared_library)
		/*. triggers E_WARNING throws Exception .*/{}
		
    /*. void    .*/ function open(/*. string .*/ $filename,
		/*. integer .*/ $flags = SQLITE3_OPEN_READWRITE | SQLITE3_OPEN_CREATE,
		/*. string .*/ $encryption_key = NULL)
		/*. triggers E_WARNING throws Exception .*/{}
		
	/*. resource.*/ function openBlob(/*. string .*/ $table,
		/*. string .*/ $column, /*. int .*/ $rowid, $dbname = "main",
		$flags = SQLITE3_OPEN_READONLY)
		/*. triggers E_WARNING throws Exception .*/{}
		
    /*. SQLite3Stmt .*/ function prepare(/*. string .*/ $query)
		/*. triggers E_WARNING throws Exception .*/{}
		
    /*. SQLite3Result .*/ function query(/*. string .*/ $query)
		/*. triggers E_WARNING throws Exception .*/{}
		
    /*. mixed .*/ function querySingle(/*. string .*/ $query, $entire_row = false)
		/*. triggers E_WARNING throws Exception .*/{}
		
    static /*. mixed[string] .*/ function version(){}
}
