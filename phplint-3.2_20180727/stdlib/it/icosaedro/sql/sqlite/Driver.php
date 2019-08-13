<?php

namespace it\icosaedro\sql\sqlite;

require_once __DIR__ . "/../../../../all.php";

/*. require_module 'sqlite3'; .*/

use it\icosaedro\sql\SQLDriverInterface;
use it\icosaedro\sql\SQLException;
use it\icosaedro\sql\sqlite\ResultSet;
use it\icosaedro\sql\sqlite\PreparedStatement;
use Exception;
use SQLite3;

/**
	SQLite 3 specific implementation of the {@link it\icosaedro\sql\SQLDriverInterface} Interface.
	@author Umberto Salsi <salsi@icosaedro.it>
	@version $Date: 2018/03/11 11:29:40 $
*/
class Driver implements SQLDriverInterface {

	/**
	 * @var SQLite3
	 */
	private $conn;


	/**
	 * Creates an instance of the SQLite3 data base.
	 * @param string $filename
	 * @param bool $create
	 * @return void
	 * @throws SQLException
	 */
	function __construct($filename, $create = FALSE)
	{
		$mode = SQLITE3_OPEN_READWRITE | ($create? SQLITE3_OPEN_CREATE : 0);
		try {
			$this->conn = new SQLite3($filename, $mode);
			$this->conn->enableExceptions(TRUE);
			$this->conn->exec("PRAGMA encoding=\"UTF-8\";");
		}
		catch(Exception $e){
			throw new SQLException($e->getMessage());
		}
	}


	/**
	 * @param string $str
	 * @return string
	 */
	function escape($str)
	{
		if( $str === NULL )
			$str = "";
		return SQLite3::escapeString($str);
	}


	/**
	 * @param string $cmd
	 * @return int
	 * @throws SQLException
	 */
	function update($cmd)
	{
		try {
			/* $res = */ $this->conn->exec($cmd);
		}
		catch(Exception $e){
			throw new SQLException($e->getMessage());
		}
		return $this->conn->changes();
	}


	/**
	 * @param string $cmd
	 * @return \it\icosaedro\sql\ResultSet
	 * @throws SQLException
	 */
	function query($cmd)
	{
		try {
			$res = $this->conn->query($cmd);
		}
		catch(Exception $e){
			throw new SQLException($e->getMessage());
		}
		return new ResultSet($res);
	}

	
	/**
	 * @param string $cmd
	 * @return \it\icosaedro\sql\PreparedStatement
	 */
	function prepareStatement($cmd)
	{
		return new PreparedStatement($this, $cmd);
	}


	/**
	 * @return void
	 */
	function close()
	{
		if( $this->conn !== NULL ){
			$this->conn->close();
			$this->conn = NULL;
		}
	}
}
