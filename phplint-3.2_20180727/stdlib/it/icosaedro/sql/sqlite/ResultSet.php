<?php

namespace it\icosaedro\sql\sqlite;

require_once __DIR__ . '/../../../../all.php';

/*. require_module 'sqlite3'; .*/

use it\icosaedro\sql\SQLException;
use SQLite3Result;

/**
	SQLite3 specific implementation of the result set.
	@author Umberto Salsi <salsi@icosaedro.it>
	@version $Date: 2018/03/31 08:13:21 $
*/
class ResultSet extends \it\icosaedro\sql\ResultSet {

	/** @var SQLite3Result */
	private $res;

	/**
	 * -1 == not available yet.
	 * @var int
	 */
	private $row_count = -1;

	/** @var int */
	private $curr_row_index = -1;

	/** @var mixed[] */
	private $curr_row;


	/**
	 * @param SQLite3Result $res
	 * @return void
	 */
	function __construct($res)
	{
		$this->res = $res;
	}


	/**
	 * @return int
	 */
	function getRowCount()
	{
		if( $this->row_count < 0 ){
			// Count the number of rows:
			$n = $this->curr_row_index;
			if( $n < 0 )
				$n = 0;
			else
				$n++;
			while( $this->res->fetchArray(SQLITE3_NUM) !== FALSE )
				$n++;
			$this->row_count = $n;
			// Restore cursor position:
			$this->res->reset();
			$n = -1;
			while( $n < $this->curr_row_index ){
				$n++;
				$this->res->fetchArray(SQLITE3_NUM);
			}
		}
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
		if( $row_index < -1 or $row_index >= $this->getRowCount() )
			throw new SQLException("row index out of the range: $row_index");
		if( $row_index < $this->curr_row_index ){
			$this->res->reset();
			$this->curr_row_index = -1;
		}
		do {
			$row = $this->res->fetchArray(SQLITE3_NUM);
			$this->curr_row_index++;
		} while( $this->curr_row_index < $row_index );
		$this->curr_row = $row;
	}


	/**
	 * @return int
	 */
	function getColumnCount()
	{
		return $this->res->numColumns();
	}


	/**
	 * @param int $column_index
	 * @return string
	 * @throws SQLException
	 */
	function getColumnName($column_index)
	{
		if( !(0 <= $column_index && $column_index < $this->res->numColumns()) )
			throw new SQLException("column index out of the range: $column_index");
		return $this->res->columnName($column_index);
	}


	/**
	 * @return boolean
	 */
	function nextRow()
	{
		if( $this->curr_row_index < 0 ){
			$row = $this->res->fetchArray(SQLITE3_NUM);
			if( $row === FALSE ){
				$this->row_count = 0;
				return FALSE;
			} else {
				$this->curr_row_index = 0;
				return TRUE;
			}
		} else if( $this->row_count >= 0 && $this->curr_row_index >= $this->row_count ){
			return FALSE;
		} else {
			$row = $this->res->fetchArray(SQLITE3_NUM);
			if( $row === FALSE ){
				$this->row_count = $this->curr_row_index + 1;
				$this->curr_row = NULL;
				return FALSE;
			} else {
				$this->curr_row = $row;
				$this->curr_row_index++;
				return TRUE;
			}
		}
	}


	/**
	 * Returns a field from the current row. SQLite fields have a dynamic type
	 * so, as an optimization, each retrieveral method may avoid type conversion
	 * if the current type matched the expected one.
	 * @param int $column_index
	 * @return mixed Raw field value.
	 * @throws SQLException Row not selected. Column index out of the range.
	 */
	private function getMixedByIndex($column_index)
	{
		if( $this->curr_row === NULL )
			throw new SQLException("row not selected");
		if( $column_index < 0 or $column_index >= $this->res->numColumns() )
			throw new SQLException("column index out of the range: $column_index");
		$v = $this->curr_row[$column_index];
		$this->was_null = is_null($v);
		return $v;
	}


	/**
	 * 
	 * @param int $column_index
	 * @return string
	 * @throws SQLException
	 */
	function getStringByIndex($column_index)
	{
		$v = $this->getMixedByIndex($column_index);
		if( is_null($v) ){
			return NULL;
		} else if( is_string($v) ){
			return (string) $v;
		} else if( is_int($v) || is_float($v) ){
			return (string) $v;
		} else {
			throw new SQLException("field no. $column_index is not a string and cannot be converted to string: ("
				. gettype($v) . ") " . (string) $v);
		}
	}


	/**
	 * @param string $column_name
	 * @return string
	 * @throws SQLException
	 */
	function getStringByName($column_name)
	{
		return $this->getStringByIndex( $this->getColumnIndex($column_name) );
	}
	
	
	/**
	 * @param int $column_index
	 * @return int
	 * @throws SQLException
	 */
	function getIntByIndex($column_index)
	{
		$v = $this->getMixedByIndex($column_index);
		if( $v === NULL )
			return 0;
		else if( is_int($v) )
			return (int) $v;
		else
			return parent::getIntByIndex($column_index);
	}
	
	
	/**
	 * @param string $column_name
	 * @return int
	 * @throws SQLException
	 */
	function getIntByName($column_name)
	{
		return $this->getIntByIndex( $this->getColumnIndex($column_name) );
	}
	

	/**
	 * @param int $column_index
	 * @return float
	 * @throws SQLException
	 */
	function getFloatByIndex($column_index)
	{
		$v = $this->getMixedByIndex($column_index);
		if( $v === NULL )
			return 0.0;
		else if( is_int($v) || is_float($v) )
			return (float) $v;
		else
			return parent::getFloatByIndex($column_index);
	}
	
	
	/**
	 * @param string $column_name
	 * @return float
	 * @throws SQLException
	 */
	function getFloatByName($column_name)
	{
		return $this->getFloatByIndex( $this->getColumnIndex($column_name) );
	}


	/**
	 * @return void
	 */
	function close()
	{
		if( $this->res !== NULL ){
			$this->res->finalize();
			$this->res = NULL;
		}
	}

}
