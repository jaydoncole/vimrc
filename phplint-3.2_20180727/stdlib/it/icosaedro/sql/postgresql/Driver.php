<?php

/*.  require_module 'pgsql'; .*/

namespace it\icosaedro\sql\postgresql;

require_once __DIR__ . '/../../../../all.php';

use it\icosaedro\sql\SQLException;
use ErrorException;


/**
	PostgreSQL specific implementation of the
	{@link \it\icosaedro\sql\SQLDriverInterface} interface.
	@author Umberto Salsi <salsi@icosaedro.it>
	@version $Date: 2018/03/30 16:50:45 $
*/
class Driver implements \it\icosaedro\sql\SQLDriverInterface {

	/** @var resource */
	private $conn;


	/**
		Establishes a new client connection with a remote PostgreSQL 8/9 data
		base server.
		Also sets the date format to ISO.
		@param string $parameters Lists the connection parameters, for example:
		<code>"host=localhost port=5432 dbname=mary user=lamb
		password=foo"</code>.
		@param string $encoding  Character encoding for every string exchanged
		by this client connection and the remote server. The default is
		"UTF-8". All the functions of this library that accept or retrieve
		strings assume this encoding, unless otherwise specified.
		@return void
		@throws SQLException
	*/
	function __construct($parameters, $encoding = "UTF-8")
	{
		try {
			$this->conn = pg_connect($parameters);
		}
		catch(ErrorException $e){
			throw new SQLException($e->getMessage());
		}

		if( pg_set_client_encoding($this->conn, $encoding) != 0 )
			throw new SQLException(pg_last_error($this->conn));

		try {
			pg_query($this->conn, "set datestyle to iso");
		}
		catch(ErrorException $e){
			throw new SQLException(pg_last_error($this->conn));
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
		return pg_escape_string($this->conn, $str);
	}


	/**
	 * @param string $cmd
	 * @return int
	 * @throws SQLException
	 */
	function update($cmd)
	{
		try {
			$res = pg_query($this->conn, $cmd);
		}
		catch(ErrorException $e){
			throw new SQLException(pg_last_error($this->conn));
		}
		return pg_affected_rows($res);
	}


	/**
	 * @param string $cmd
	 * @return \it\icosaedro\sql\ResultSet
	 * @throws SQLException
	 */
	function query($cmd)
	{
		try {
			$res = pg_query($this->conn, $cmd);
		}
		catch(ErrorException $e){
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
			pg_close($this->conn);
			$this->conn = NULL;
		}
	}

}
