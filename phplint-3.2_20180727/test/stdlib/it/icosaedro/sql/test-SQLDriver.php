<?php

namespace it\icosaedro\sql;

require_once __DIR__ . "/../../../../../stdlib/autoload.php";

use it\icosaedro\utils\TestUnit as TU;
use it\icosaedro\utils\Date;
use it\icosaedro\utils\DateTimeTZ;
use it\icosaedro\utils\Strings;
use it\icosaedro\sql\SQLDriverInterface;
use it\icosaedro\sql\ResultSet;
use it\icosaedro\sql\PreparedStatement;
use it\icosaedro\sql\SQLException;
use it\icosaedro\bignumbers\BigInt;
use it\icosaedro\bignumbers\BigFloat;
use Exception;
use InvalidArgumentException;
use RuntimeException;
use ErrorException;


/**
 * Displays a result set.
 * @param ResultSet $rs
 * @throws SQLException
 */
function echoResultSet($rs)
{
	$nRows = $rs->getRowCount();
	for($r = 0; $r < $nRows; $r++){
		$rs->moveToRow($r);
		echo "Record no. $r:\n";
		for( $c = 0; $c < $rs->getColumnCount(); $c++ ){
			echo "    ", $rs->getColumnName($c), " = ", $rs->getStringByIndex($c), "\n";
		}
	}
}


/**
 * Performs general tests over a data base connection. The DB must be empty
 * and already set. UTF-8 encoding is assumed, so the DB must be opened and
 * configured accordingly.
 * @param SQLDriverInterface $db
 * @throws SQLException
 */
function genericTests($db)
{
	// Checking types:
	$ps = $db->prepareStatement("select ?");
	
	// NULL:
	$ps->setNull(0);
	$rs = $ps->query();
	TU::test($rs->getRowCount(), 1);
	$rs->moveToRow(0);
	TU::test($rs->getStringByIndex(0), NULL);
	$rs->close();
	
	// boolean FALSE:
	$ps->setBoolean(0, FALSE);
	$rs = $ps->query();
	TU::test($rs->getRowCount(), 1);
	$rs->moveToRow(0);
	TU::test($rs->getBooleanByIndex(0), FALSE);
	$rs->close();
	// boolean TRUE:
	$ps->setBoolean(0, TRUE);
	$rs = $ps->query();
	TU::test($rs->getRowCount(), 1);
	$rs->moveToRow(0);
	TU::test($rs->getBooleanByIndex(0), TRUE);
	$rs->close();
	
	// int:
	$intValue = 12345;
	$ps->setInt(0, $intValue);
	$rs = $ps->query();
	TU::test($rs->getRowCount(), 1);
	$rs->moveToRow(0);
	TU::test($rs->getIntByIndex(0), $intValue);
	$rs->close();
	
	// float:
	$floatValue = 0.5;
	$ps->setFloat(0, $floatValue);
	$rs = $ps->query();
	TU::test($rs->getRowCount(), 1);
	$rs->moveToRow(0);
	TU::test($rs->getFloatByIndex(0), $floatValue);
	$rs->close();
	
	// string:
	$stringValue = "  with leading and trailing spaces and non-ASCII àèìòù  ";
	$ps->setString(0, $stringValue);
	$rs = $ps->query();
	TU::test($rs->getRowCount(), 1);
	$rs->moveToRow(0);
	TU::test($rs->getStringByIndex(0), $stringValue);
	$rs->close();
	
	// BigInt (note we require more than 64 bits int precision):
	$biValue = new BigInt("123456789012345678901234567890");
	$ps->setBigInt(0, $biValue);
	$rs = $ps->query();
	TU::test($rs->getRowCount(), 1);
	$rs->moveToRow(0);
	try {
		TU::test($rs->getBigIntByIndex(0), $biValue);
	}
	catch(SQLException $e){
		if( $db instanceof sqlite\Driver
		&& $e->getMessage() === "invalid argument `1.2345678901235E+29'")
			echo "Warning: SQLite still does not really support NUMERIC() and relies on approximated floating point numbers instead, hence BigInt cannot be safely supported.\n";
		else
			throw $e;
	}
	$rs->close();
	
	// BigFloat (note the number of digits is greater than IEEE 754 double precision):
	$bfValue = new BigFloat("1234567890.123456789");
	$ps->setBigFloat(0, $bfValue);
	$rs = $ps->query();
	TU::test($rs->getRowCount(), 1);
	$rs->moveToRow(0);
	try {
		TU::test($rs->getBigFloatByIndex(0), $bfValue);
	}
	catch(RuntimeException $e){
		echo "---> ", rawurlencode($e->getMessage()), "\n";
		if( $db instanceof sqlite\Driver
		&& $e->getMessage() === "\n     GOT: 1234567890.1235\nEXPECTED: 1234567890.123456789\n")
			echo "Warning: SQLite still does not really support NUMERIC() and relies on approximated floating point numbers instead, hence BigFloat is supported only within the IEEE 754 double-precision precision.\n";
		else
			throw $e;
	}
	$rs->close();
	
	// Gregorian date (note the possibly ambiguous month/day order):
	$dateValue = new Date(2018, 1, 2);
	$ps->setDate(0, $dateValue);
	$rs = $ps->query();
	$rs->moveToRow(0);
	TU::test($rs->getDateByIndex(0), $dateValue);
	$rs->close();
	
	// Gregorian date and time (note the possibly ambiguous month/day order,
	// and note the exotic TZ):
	$dateTimeValue = DateTimeTZ::parse("2018-01-02T03:04:05-05:00");
	$ps->setDateTime(0, $dateTimeValue);
	$rs = $ps->query();
	$rs->moveToRow(0);
	TU::test($rs->getDateTimeByIndex(0), $dateTimeValue);
	$rs->close();
	
	// Bytes:
	$bytesValue = "\x00\x01\xff";
	$ps->setBytes(0, $bytesValue);
	$rs = $ps->query();
	$rs->moveToRow(0);
	TU::test($rs->getBytesByIndex(0), $bytesValue);
	$rs->close();
	
	
	/*
	 * Create a table with columns of all the supported types, insert values
	 * and chect the returned values and their type.
	 */
	
	if( $db instanceof sqlite\Driver ){
		$numeric_40_type = "text";
		$numeric_40_20_type = "text";
		$date_type = "text";
		$datetime_type = "text";
	} else {
		$numeric_40_type = "numeric(40)";
		$numeric_40_20_type = "numeric(40,20)";
		$date_type = "date";
		$datetime_type = "datetime";
	}
	if( $db instanceof postgresql\Driver ){
		$float_type = "double precision";
		$datetime_type = "timestamp";
	} else {
		$float_type = "double";
		$datetime_type = "datetime";
	}
	
	$sql = <<< EOT
		create table allTypes (
			null_field int,
			boolean_false_field boolean,
			boolean_true_field boolean,
			int_field int,
			float_field $float_type,
			string_field text,
			bi_field $numeric_40_type,
			bf_field $numeric_40_20_type,
			date_field $date_type,
			date_time_field $datetime_type,
			bytes_field text
		)
EOT;
	$db->update($sql);
	
	// ...insert values:
	$psString = <<< EOT
		insert into allTypes (
			null_field,
			boolean_false_field,
			boolean_true_field,
			int_field,
			float_field,
			string_field,
			bi_field,
			bf_field,
			date_field,
			date_time_field,
			bytes_field
		) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
EOT;
	$ps = $db->prepareStatement($psString);
	$ps->setNull(0);
	$ps->setBoolean(1, FALSE);
	$ps->setBoolean(2, TRUE);
	$ps->setInt(3, $intValue);
	$ps->setFloat(4, $floatValue);
	$ps->setString(5, $stringValue);
	$ps->setBigInt(6, $biValue);
	$ps->setBigFloat(7, $bfValue);
	$ps->setDate(8, $dateValue);
	$ps->setDateTime(9, $dateTimeValue);
	$ps->setBytes(10, $bytesValue);
	$ps->update();
	
	// ...check returned values:
	$rs = $db->query("select * from allTypes");
	$rs->moveToRow(0);
	TU::test($rs->getStringByName("null_field"), NULL);
	TU::test($rs->getBooleanByName("boolean_false_field"), FALSE);
	TU::test($rs->getBooleanByName("boolean_true_field"), TRUE);
	TU::test($rs->getIntByName("int_field"), $intValue);
	TU::test($rs->getFloatByName("float_field"), $floatValue);
	TU::test($rs->getStringByName("string_field"), $stringValue);
	TU::test($rs->getBigIntByName("bi_field"), $biValue);
	TU::test($rs->getBigFloatByName("bf_field"), $bfValue);
	TU::test($rs->getDateByName("date_field"), $dateValue);
	TU::test($rs->getDateTimeByName("date_time_field"), $dateTimeValue);
	TU::test($rs->getBytesByName("bytes_field"), $bytesValue);
	
	$rs->close();
	$db->update("drop table allTypes");
	
	$db->update("create table t1 (id int, x text)");
	$db->update("insert into t1 values(1, 'a111')");
	$db->update("insert into t1 values(2, 'àèìòù')");
	
	$db->update("create table t2 (id int, y text)");
	$db->update("insert into t2 values(3, 'b333èèè')");
	$db->update("insert into t2 values(4, 'b444')");
	
	/*
	 * Testing column name collision detection on 'id':
	 */
	$rs = $db->query("select t1.*, t2.* from t1, t2");
	TU::test($rs->getRowCount(), 4);
//	echoResultSet($rs);
	$rs->moveToRow(0);
	$collision_detected = TRUE;
	try {
		$rs->getStringByName("id");
	}
	catch(SQLException $e){
		if( Strings::startsWith($e->getMessage(), "ambiguous column name") )
			$collision_detected = TRUE;
		else
			throw $e;
	}
	if( ! $collision_detected )
		throw new RuntimeException("failed to detect column name collision");
	$rs->close();
	
	$rs = $db->query("select id, x as the_value from t1 where id = 2");
	TU::test($rs->getColumnName(0), "id");
	TU::test($rs->getColumnName(1), "the_value");
	TU::test($rs->getRowCount(), 1);
	$rs->moveToRow(0);
	TU::test($rs->getIntByName("id"), 2);
	TU::test($rs->getStringByName("the_value"), "àèìòù");
	$rs->close();
	
	// All result sets MUST be closed at this time, or "database is locked"
	// exception is thrown:
	$db->update("drop table t1");
	$db->update("drop table t2");
}


/**
 * @throws SQLException
 */
function mysql()
{
	$host = "localhost";
	$user = "root";
	$pass = "";
	$dbname = "phplint_test_1234";
	$db = new mysql\Driver( array($host, $user, $pass) );
	$db->update("drop database if exists $dbname");
	$db->update("create database $dbname character set utf8 collate utf8_general_ci");
	$db->update("use $dbname");
	genericTests($db);
	$db->update("drop database $dbname");
	$db->close();
}


/**
 * @throws SQLException
 */
function postgresql()
{
	$host = "localhost";
	$port = 5432;
	$user = "root";
	$pass = "";
	$dbname = "phplint_test_1234";
	
//	$db = new postgresql\Driver("host=$host port=$port user=$user password=$pass");
	$db = new postgresql\Driver("dbname=template1");
	$db->update("drop database if exists $dbname");
	
	// Creating db. No "use" statement in PostgreSQL; need to close and re-open
	// to the specific DB:
	$db->update("create database $dbname");
	$db->close();
	$db = new postgresql\Driver("dbname=$dbname");
	genericTests($db);
	
	// Cannot drop current db; need to re-open the connection:
	$db->close();
	$db = new postgresql\Driver("dbname=template1");
	$db->update("drop database $dbname");
	$db->close();
}


/**
 * @throws SQLException
 * @throws ErrorException
 */
function sqlite()
{
	$fn = "test.sqlite";
	if(file_exists($fn) )
		unlink($fn);
	$db = new sqlite\Driver($fn, TRUE);
	genericTests($db);
	$db->close();
	unlink($fn);
}


/**
 * @throws SQLException
 * @throws ErrorException
 */
function main()
{
	try {
		if( !function_exists("mysqli_connect") )
			throw new RuntimeException("function mysqli_connect() not available - missing module?");
		mysql();
	}
	catch(Exception $e){
		echo "MySQL test failed:\n$e\n";
	}
	try {
		if( !function_exists("pg_connect") )
			throw new RuntimeException("function pg_connect() not available - missing module?");
		postgresql();
	}
	catch(Exception $e){
		echo "PostgreSQL test failed:\n$e\n";
	}
	try {
		if( !class_exists("SQLite3") )
			throw new RuntimeException("class SQLite3 not available - missing module?");
		sqlite();
	}
	catch(Exception $e){
		echo "SQLite3 test failed:\n$e\n";
	}
}

main();
