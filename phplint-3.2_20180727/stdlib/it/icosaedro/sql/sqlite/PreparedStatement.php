<?php

namespace it\icosaedro\sql\sqlite;

require_once __DIR__ . '/../../../../all.php';

use it\icosaedro\utils\Date;
use it\icosaedro\utils\DateTimeTZ;
use it\icosaedro\sql\SQLException;
use it\icosaedro\bignumbers\BigInt;
use it\icosaedro\bignumbers\BigFloat;


/**
 * SQLite3 specific implementation of the prepared statement.
 * 
 * SQLite does not really support the DATE and DATETIME SQL types, so this
 * implementation assumes these values are stored as TEXT.
 * 
 * SQLite does not have a NUMERIC type, so BigInt and BigFloat are always saved
 * as strings and fields of type TEXT is assumed so precision can be preserved
 * avoiding rounding issues with the IEEE 754 floating point.
 *
 * @author Umberto Salsi <salsi@icosaedro.it>
 * @version $Date: 2018/04/09 13:24:23 $
 */
class PreparedStatement extends \it\icosaedro\sql\PreparedStatement {

	/**
	 * Sets a parameter of type gregorian date.
	 * @param int $index  Index of the parameter to set, the first question
	 * mark is the number 0.
	 * @param Date $value  Value of the parameter. If NULL, then the SQL
	 * NULL value is set.
	 * @return void
	 * @throws SQLException  If the index is invalid.
	 */
	function setDate($index, $value)
	{
		if( $value === NULL )
			$v = "NULL";
		else
			$v = "'" . $value->__toString() . "'";
		$this->setParameter($index, $v);
	}


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
			// Generate "YYYY-MM-DDTHH:mm":
			$v = $value->toTZ(0)->getDateTime()->__toString();
			// ...remove middle "T":
			$v = (string) str_replace("T", " ", $v);
			$v = "'$v'";
		}
		$this->setParameter($index, $v);
	}


	/**
	 * Sets a parameter of type BigInt.
	 * @param int $index  Index of the parameter to set, the first question
	 * mark is the number 0.
	 * @param BigInt $value  Value of the parameter. If NULL, then the SQL
	 * NULL value is set.
	 * @return void
	 * @throws SQLException  If the index is invalid.
	 */
	function setBigInt($index, $value)
	{
		if( $value === NULL )
			$v = "NULL";
		else
			$v = "'" . $value->__toString() . "'";
		$this->setParameter($index, $v);
	}


	/**
	 * Sets a parameter of type BigFloat.
	 * @param int $index  Index of the parameter to set, the first question
	 * mark is the number 0.
	 * @param BigFloat $value  Value of the parameter. If NULL, then the SQL
	 * NULL value is set.
	 * @return void
	 * @throws SQLException  If the index is invalid.
	 */
	function setBigFloat($index, $value)
	{
		if( $value === NULL )
			$v = "NULL";
		else
			$v = "'" . $value->__toString() . "'";
		$this->setParameter($index, $v);
	}
	
}
