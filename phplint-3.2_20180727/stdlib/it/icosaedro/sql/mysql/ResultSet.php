<?php

namespace it\icosaedro\sql\mysql;

require_once __DIR__ . '/../../../../all.php';

/*. require_module 'mysqli'; .*/

use it\icosaedro\sql\SQLException;
use mysqli_result;
use ErrorException;

/**
	MySQL specific implementation of the result set.
	@author Umberto Salsi <salsi@icosaedro.it>
	@version $Date: 2018/03/31 08:13:21 $
*/
class ResultSet extends \it\icosaedro\sql\ResultSet {

	/** @var mysqli_result */
	private $res;

	/** @var int */
	private $row_count = 0;

	/** @var int */
	private $curr_row_index = -1;

	/** @var string[int] */
	private $curr_row;

	/**
	 * @param mysqli_result $res
	 * @return void
	 */
	function __construct($res)
	{
		$this->res = $res;
		$this->row_count = mysqli_num_rows($res);
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
		else {
			if( ! mysqli_data_seek($this->res, $row_index) )
				throw new SQLException("no this row index: $row_index");
			$this->curr_row = cast("array[int]string",
				mysqli_fetch_row($this->res));
		}
	}


	/**
	 * @return int
	 */
	function getColumnCount()
	{
		return mysqli_num_fields($this->res);
	}


	/**
	 * @param int $column_index
	 * @return string
	 * @throws SQLException
	 */
	function getColumnName($column_index)
	{
		try {
			return mysqli_fetch_field_direct($this->res, $column_index)->name;
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
			if( ! mysqli_data_seek($this->res, $this->curr_row_index) )
				return FALSE;
			$this->curr_row = cast("string[int]", mysqli_fetch_row($this->res));
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
		if( $column_index < 0 or $column_index >= mysqli_num_fields($this->res) )
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
		$v = $this->curr_row[ $this->getColumnIndex($column_name) ];
		$this->was_null = $v === NULL;
		return $v;
	}


	/**
	 * @return void
	 */
	function close()
	{
		if( $this->res !== NULL ){
			mysqli_free_result($this->res);
			$this->res = NULL;
		}
	}

}
