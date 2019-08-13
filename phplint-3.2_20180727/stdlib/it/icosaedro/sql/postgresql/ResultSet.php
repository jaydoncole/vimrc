<?php

/*.
	require_module 'core';
	require_module 'pgsql';
.*/

namespace it\icosaedro\sql\postgresql;

require_once __DIR__ . '/../../../../all.php';

use it\icosaedro\sql\SQLException;
use ErrorException;
use RuntimeException;


/**
	PostgreSQL specific implementation of the result set.
	@author Umberto Salsi <salsi@icosaedro.it>
	@version $Date: 2018/03/31 08:13:21 $
*/
class ResultSet extends \it\icosaedro\sql\ResultSet {

	/** @var resource */
	private $res;

	/** @var int */
	private $row_count = 0;

	/** @var int */
	private $curr_row_index = -1;

	/** @var string[int] */
	private $curr_row;
	

	/**
		Constructor invoked from the driver. Should never be called
		from user's code.
		@param resource $res PostrgreSQL result set.
		@return void
	    @throws SQLException
	*/
	function __construct($res)
	{
		$this->res = $res;
		$this->row_count = pg_num_rows($res);
	}


	/**
	 * @return int
	 */
	function getRowCount()
	{
		return $this->row_count;
	}


	/**
	 * @param int $row_index
	 * @return void
	 * @throws SQLException
	 */
	function moveToRow($row_index)
	{
		if( $row_index === $this->curr_row_index )
			return;
		if( $row_index < -1 or $row_index >= $this->row_count )
			throw new SQLException("row index out of the range: $row_index");
		$this->curr_row_index = $row_index;
		if( $row_index < 0 )
			$this->curr_row = NULL;
		else
			$this->curr_row = cast("array[int]string",
				pg_fetch_row($this->res, $row_index));
	}


	/**
	 * @return int
	 */
	function getColumnCount()
	{
		return pg_num_fields($this->res);
	}


	/**
	 * @param int $column_index
	 * @return string
	 * @throws SQLException
	 */
	function getColumnName($column_index)
	{
		try {
			return pg_field_name($this->res, $column_index);
		}
		catch(ErrorException $e){
			throw new SQLException($e->getMessage());
		}
	}


	/**
	 * @return boolean
	 */
	function nextRow()
	{
		if( $this->curr_row_index + 1 >= $this->row_count ){
			return FALSE;
		} else {
			$this->curr_row_index++;
			$this->curr_row = cast("array[int]string",
				pg_fetch_row($this->res, $this->curr_row_index));
			return TRUE;
		}
			
	}


	/**
	 * @param int $column_index
	 * @return string
	 * @throws SQLException
	 */
	function getStringByIndex($column_index)
	{
		if( $this->curr_row === NULL )
			throw new SQLException("row not selected");
		if( $column_index < 0 or $column_index >= count($this->curr_row) )
			throw new SQLException("column index out of the range: $column_index");
		$v = $this->curr_row[$column_index];
		$this->was_null = $v === NULL;
		return $v;
	}


	/**
	 * @param string $column_name
	 * @return string
	 * @throws SQLException
	 */
	function getStringByName($column_name)
	{
		if( $this->curr_row === NULL )
			throw new SQLException("row not selected");
		$column_index = $this->getColumnIndex($column_name);
		$v = $this->curr_row[$column_index];
		$this->was_null = $v === NULL;
		return $v;
	}


	/**
	 * @param string $v
	 * @return string
	 */
	private function decodeBytea($v)
	{
		if( $v === NULL )
			return NULL;
		else if( strlen($v) > 2 and substr($v, 0, 2) === "\\x" ){
			// hex encoding (the default in PG >= 9)
			try {
				return hex2bin( substr($v, 2) );
			}
			catch(ErrorException $e){
				throw new RuntimeException($e->getMessage());
			}
		} else {
			// oct encoding (the only available one in pg <= 8)
			try {
				return pg_unescape_bytea($v);
			}
			catch(ErrorException $e){
				throw new RuntimeException($e->getMessage());
			}
		}
	}
	

	/**
		Retrieves a field of type binary (for example, an image).
		This implementation is specific of PostgreSQL and assumes a field
		of type BYTEA.
		@param int $column_index  Index of the column, starting from 0.
		@return string Value of the field, possibly containing arbitrary
		sequences of bytes. Returns PHP NULL for SQL NULL. 
		@throws SQLException  Failed to retrieve data from SQL server.
		Invalid Base64 encoding. 
	*/
	function getBytesByIndex($column_index)
	{
		$v = $this->getStringByIndex($column_index);
		return $this->decodeBytea($v);
	}


	/**
		Retrieves a field of type binary (for example, an image).
		This implementation is specific of PostgreSQL and assumes a field
		of type BYTEA.
		@param string $column_name  Name of the column.
		@return string Value of the field, possibly containing arbitrary
		sequences of bytes. Returns PHP NULL for SQL NULL. 
		@throws SQLException  Failed to retrieve data from SQL server.
		Invalid Base64 encoding. 
	*/
	function getBytesByName($column_name)
	{
		$v = $this->getStringByName($column_name);
		return $this->decodeBytea($v);
	}


	/**
	 * @return void
	 */
	function close()
	{
		if( $this->res !== NULL ){
			pg_free_result($this->res);
			$this->res = NULL;
		}
	}

}
