<?php

namespace it\icosaedro\sql\postgresql;

require_once __DIR__ . '/../../../../all.php';

use it\icosaedro\sql\SQLException;
use it\icosaedro\utils\DateTimeTZ;


/**
 * PostgreSQL specific implementation of the prepared statement.
 * @author Umberto Salsi <salsi@icosaedro.it>
 * @version $Date: 2018/04/09 13:24:23 $
 */
class PreparedStatement extends \it\icosaedro\sql\PreparedStatement {


	/**
	 * Sets a parameter of type date with time.
	 * @param int $index  Index of the parameter to set, the first question
	 * mark is the number 0.
	 * @param DateTimeTZ $value  Value of the parameter. If NULL, then the SQL
	 * NULL value is set.
	 * @return void
	 * @throws SQLException  If the index is invalid.
	 */
	function setDateTime($index, $value)
	{
		if( $value === NULL ){
			$v = "NULL";
		} else {
			// Generate "YYYY-MM-DDTHH:mm" in UTC TZ:
			$v = $value->toTZ(0)->getDateTime()->__toString();
			// ...remove middle "T":
			$v = (string) str_replace("T", " ", $v);
			$v = "cast('$v' as TIMESTAMP)";
		}
		$this->setParameter($index, $v);
	}
	

	/**
	 * Sets a parameter of type binary (for example, an image).
	 * This implementation is specific of PostgreSQL and assumes a field
	 * of type BYTEA.
	 * @param int $index  Index of the parameter to set, the first question
	 * mark is the number 0.
	 * @param string $value  Value of the parameter as an array of bytes.
	 * If NULL, then the SQL NULL value is set.
	 * @return void
	 * @throws SQLException  If the index is invalid.
	 */
	function setBytes($index, $value)
	{
		if( $value === NULL )
			$v = "NULL";
		else
			$v = "decode('" . base64_encode($value) . "', 'base64')::bytea";
		$this->setParameter($index, $v);
	}


}
