<?php
/** PHP Data Objects (PDO).


See: {@link http://www.php.net/manual/en/book.pdo.php}
@package pdo
*/

/*. require_module 'spl'; .*/


class PDOException extends Exception
{
	/**
	 * Corresponds to PDO::errorInfo() or PDOStatement::errorInfo().
	 */
	public $errorInfo = /*. (array[int]mixed) .*/ NULL;
}


class PDOStatement
#implements Traversable
# FIXME: cannot implement Traversable in user defined classes...
#        Why they did not implement Iterator or IteratorAggregate instead?
{

public /*. string .*/ $queryString;

/*. bool .*/ function bindColumn(/*. mixed .*/ $column, /*. return mixed .*/ &$param
	/*., args .*/){}
/*. bool .*/ function bindParam(/*. mixed .*/ $parameter, /*. return mixed .*/ &$variable /*., args .*/){}
/*. bool .*/ function bindValue(/*. mixed .*/ $parameter, /*. mixed .*/ $value
	/*., args .*/){}
/*. bool .*/ function closeCursor(){}
/*. int .*/ function columnCount(){}
/*. void .*/ function debugDumpParams(){}
/*. string .*/ function errorCode(){}
/*. array[int]mixed .*/ function errorInfo(){}
/*. bool .*/ function execute(/*. args .*/){}
/*. mixed .*/ function fetch(/*. args .*/){}
/*. array[int]mixed .*/ function fetchAll(/*. args .*/){}
/*. string .*/ function fetchColumn(/*. args .*/){}
/*. mixed .*/ function fetchObject($class_name = "stdClass", /*. mixed[int] .*/ $ctor_args = NULL){}
/*. mixed .*/ function getAttribute(/*. int .*/ $attribute){}
/** EXPERIMENTAL: please read the manual. */
/*. mixed .*/ function getColumnMeta(/*. int .*/ $column){}
/*. bool .*/ function nextRowset(){}
/*. int .*/ function rowCount(){}
/*. bool .*/ function setAttribute(/*. int .*/ $attribute, /*. mixed .*/ $value){}
/*. bool .*/ function setFetchMode(/*. int .*/ $_see__manual_ /*., args .*/){}

}


class PDO
{

	const
		ATTR_AUTOCOMMIT = 0,
		ATTR_CASE = 8,
		ATTR_CLIENT_VERSION = 5,
		ATTR_CONNECTION_STATUS = 7,
		ATTR_CURSOR = 10,
		ATTR_CURSOR_NAME = 9,
		ATTR_DEFAULT_FETCH_MODE = 19,
		ATTR_DRIVER_NAME = 16,
		ATTR_EMULATE_PREPARES = 20,
		ATTR_ERRMODE = 3,
		ATTR_FETCH_CATALOG_NAMES = 15,
		ATTR_FETCH_TABLE_NAMES = 14,
		ATTR_MAX_COLUMN_LEN = 18,
		ATTR_ORACLE_NULLS = 11,
		ATTR_PERSISTENT = 12,
		ATTR_PREFETCH = 1,
		ATTR_SERVER_INFO = 6,
		ATTR_SERVER_VERSION = 4,
		ATTR_STATEMENT_CLASS = 13,
		ATTR_STRINGIFY_FETCHES = 17,
		ATTR_TIMEOUT = 2,
		CASE_LOWER = 2,
		CASE_NATURAL = 0,
		CASE_UPPER = 1,
		CURSOR_FWDONLY = 0,
		CURSOR_SCROLL = 1,
		ERRMODE_EXCEPTION = 2,
		ERRMODE_SILENT = 0,
		ERRMODE_WARNING = 1,
		ERR_NONE = "00000",
		FETCH_ASSOC = 2,
		FETCH_BOTH = 4,
		FETCH_BOUND = 6,
		FETCH_CLASS = 8,
		FETCH_CLASSTYPE = 262144,
		FETCH_COLUMN = 7,
		FETCH_FUNC = 10,
		FETCH_GROUP = 65536,
		FETCH_INTO = 9,
		FETCH_KEY_PAIR = 12,
		FETCH_LAZY = 1,
		FETCH_NAMED = 11,
		FETCH_NUM = 3,
		FETCH_OBJ = 5,
		FETCH_ORI_ABS = 4,
		FETCH_ORI_FIRST = 2,
		FETCH_ORI_LAST = 3,
		FETCH_ORI_NEXT = 0,
		FETCH_ORI_PRIOR = 1,
		FETCH_ORI_REL = 5,
		FETCH_PROPS_LATE = 1048576,
		FETCH_SERIALIZE = 524288,
		FETCH_UNIQUE = 196608,
		MYSQL_ATTR_COMPRESS = 1003,
		MYSQL_ATTR_DIRECT_QUERY = 1004,
		MYSQL_ATTR_FOUND_ROWS = 1005,
		MYSQL_ATTR_IGNORE_SPACE = 1006,
		MYSQL_ATTR_INIT_COMMAND = 1002,
		MYSQL_ATTR_LOCAL_INFILE = 1001,
		MYSQL_ATTR_MULTI_STATEMENTS = 1013,
		MYSQL_ATTR_SERVER_PUBLIC_KEY = 1012,
		MYSQL_ATTR_SSL_CA = 1009,
		MYSQL_ATTR_SSL_CAPATH = 1010,
		MYSQL_ATTR_SSL_CERT = 1008,
		MYSQL_ATTR_SSL_CIPHER = 1011,
		MYSQL_ATTR_SSL_KEY = 1007,
		MYSQL_ATTR_USE_BUFFERED_QUERY = 1000,
		NULL_EMPTY_STRING = 1,
		NULL_NATURAL = 0,
		NULL_TO_STRING = 2,
		PARAM_BOOL = 5,
		PARAM_EVT_ALLOC = 0,
		PARAM_EVT_EXEC_POST = 3,
		PARAM_EVT_EXEC_PRE = 2,
		PARAM_EVT_FETCH_POST = 5,
		PARAM_EVT_FETCH_PRE = 4,
		PARAM_EVT_FREE = 1,
		PARAM_EVT_NORMALIZE = 6,
		PARAM_INPUT_OUTPUT = -2147483648,
		PARAM_INT = 1,
		PARAM_LOB = 3,
		PARAM_NULL = 0,
		PARAM_STMT = 4,
		PARAM_STR = 2,
		PGSQL_ATTR_DISABLE_PREPARES = 1000,
		PGSQL_TRANSACTION_ACTIVE = 1,
		PGSQL_TRANSACTION_IDLE = 0,
		PGSQL_TRANSACTION_INERROR = 3,
		PGSQL_TRANSACTION_INTRANS = 2,
		PGSQL_TRANSACTION_UNKNOWN = 4;

	/*. void .*/ function __construct(/*. string .*/ $dsn /*., args .*/)
		/*. throws PDOException .*/ {}
	/*. bool .*/ function beginTransaction()/*. throws PDOException .*/{}
	/*. bool .*/ function commit()/*. throws PDOException .*/{}
	/*. string .*/ function errorCode(){}
	/*. array[int]string .*/ function errorInfo(){}
	/*. mixed .*/ function exec(/*. string .*/ $statement){}
	/*. mixed .*/ function getAttribute(/*. int .*/ $attribute){}
	static /*. string[int] .*/ function getAvailableDrivers(){}
	/*. bool .*/ function inTransaction(){}
	/*. string .*/ function lastInsertId(/*. args .*/){}
	/** WARNING: it returns FALSE on error. */ 
	/*. PDOStatement .*/ function prepare(/*. string .*/ $statement /*., args .*/)
		/*. throws PDOException .*/{}
	/** WARNING: read the manual for more arguments and return values. */
	/*. PDOStatement .*/ function query(/*. string .*/ $statement /*., args .*/)
		/*. throws PDOException .*/{}
	/*. string .*/ function quote(/*. string .*/ $str /*., args .*/){}
	/*. bool .*/ function rollBack()/*. throws PDOException .*/{}
	/*. bool .*/ function setAttribute(/*. int .*/ $attribute, /*. mixed .*/ $value)/*. triggers E_WARNING .*/{}

}

/**
 * @deprecated Objects of this class might be returned by PDO, but its meaning
 * is still not documented.
 */
class PDORow {}
