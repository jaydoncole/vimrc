<?php

namespace it\icosaedro\sql\mysql;

require_once __DIR__ . "/../../../../all.php";

/*. require_module 'mysqli'; .*/

use it\icosaedro\sql\SQLDriverInterface;
use it\icosaedro\sql\SQLException;
use it\icosaedro\sql\mysql\ResultSet;
use it\icosaedro\sql\mysql\PreparedStatement;
use Exception;
use ErrorException;
use mysqli;


/**
	MySQL specific implementation of the {@link it\icosaedro\sql\SQLDriverInterface} Interface.
	@author Umberto Salsi <salsi@icosaedro.it>
	@version $Date: 2018/02/23 07:19:12 $
*/
class Driver implements SQLDriverInterface {

	/** @var mysqli */
	private $conn;


	/**
		Establishes a new client connection with a remote MySQL 4/5 data
		base server.
		@param mixed[int] $parameters Lists the connection parameters to
		be sent as arguments to the {@link mysqli::__construct()} method.
		@param string $encoding  Character encoding for every string exchanged
		by this client connection and the remote server. The default is
		"UTF-8". All the functions of this library that accept or retrieve
		strings assume this encoding, unless otherwise specified. Example:
		<pre>
		use it\icosaedro\sql\mysql\Driver;
		$db = new Driver( array("localhost", "theuser", "pass", "dbname") );
		</pre>
		@return void
		@throws SQLException
	*/
	function __construct($parameters, $encoding = "UTF8")
	{
		try {
			$conn = call_user_func_array("mysqli_connect", $parameters);
		}
		catch(Exception $e){
			throw new SQLException($e->getMessage() . ": " . mysqli_connect_error());
		}
		$this->conn = cast("mysqli", $conn);
		if( ! mysqli_set_charset($this->conn, $encoding) )
			throw new SQLException("mysqli_set_charset($encoding): unknown charset");
	}


	/**
	 * @param string $str
	 * @return string
	 */
	function escape($str)
	{
		if( $str === NULL )
			$str = "";
		return mysqli_real_escape_string($this->conn, $str);
	}


	/**
	 * @param string $cmd
	 * @return int
	 * @throws SQLException
	 */
	function update($cmd)
	{
		try {
			/* $res = */ mysqli_query($this->conn, $cmd);
		}
		catch(ErrorException $e){
			throw new SQLException($e->getMessage());
		}
		$err = mysqli_error($this->conn);
		if( strlen($err) > 0 )
			throw new SQLException($err);
		return mysqli_affected_rows($this->conn);
	}


	/**
	 * @param string $cmd
	 * @return \it\icosaedro\sql\ResultSet
	 * @throws SQLException
	 */
	function query($cmd)
	{
		try {
			$res = mysqli_query($this->conn, $cmd);
		}
		catch(ErrorException $e){
			throw new SQLException($e->getMessage());
		}
		$err = mysqli_error($this->conn);
		if( strlen($err) > 0 )
			throw new SQLException($err);
		return new ResultSet(cast("mysqli_result", $res));
	}

	
	/**
	 * @param string $cmd
	 * @return \it\icosaedro\sql\PreparedStatement
	 */
	function prepareStatement($cmd)
	{
		return new PreparedStatement($this, $cmd);
	}


	function close()
	{
		if( $this->conn !== NULL ){
			mysqli_close($this->conn);
			$this->conn = NULL;
		}
	}
}
