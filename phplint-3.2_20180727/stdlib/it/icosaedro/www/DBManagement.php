<?php
namespace it\icosaedro\www;
require_once __DIR__ . "/../../../all.php";
use it\icosaedro\sql\SQLDriverInterface as SQLDriver;
use it\icosaedro\sql\SQLException;

/*. require_module 'hash'; .*/

/**
 * Data base connection and management routines.
 * @author Umberto Salsi <salsi@icosaedro.it>
 * @version $Date: 2018/05/09 10:11:31 $
 */
class DBManagement {
	
	const DB_NAME = "icodb";
	
	/**
	 * @return SQLDriver
	 * @throws SQLException
	 */
	static function connect()
	{
		return new \it\icosaedro\sql\mysql\Driver( array("localhost", "root", "") );
	}
	
	/**
	 * Tells if the table exists in the current database.
	 * @param SQLDriver $db Database connection.
	 * @param string $table Name of the table.
	 * @return boolean True if the table exists, false if it does not.
	 * @throws SQLException
	 */
	private static function tableExists($db, $table)
	{
		$dbname = self::DB_NAME;
		try {
			$db->query("select 1 from $dbname.$table limit 1");
		} catch (SQLException $e) {
			if( strpos($e->getMessage(), "Table '$dbname.$table' doesn't exist") !== FALSE ){
				return FALSE;
			} else {
				throw $e;
			}
		}
		return TRUE;
	}
	
	/**
	 * @param SQLDriver $db
	 * @throws SQLException
	 */
	private static function createUsersTable($db)
	{
//		$db->update("drop table if exists users");
		if( self::tableExists($db, "users") )
			return;
		$sql = <<< EOT
			create table users (
				pk           SERIAL,
				name         VARCHAR(50) UNIQUE COLLATION utf8_bin,
				pass_hash    TEXT COLLATION utf8_bin,
				current_name VARCHAR(100) COLLATION utf8_bin,
				email        VARCHAR(100) DEFAULT '' COLLATION utf8_bin,
				permissions  VARCHAR(100) DEFAULT '' COLLATION utf8_bin,
				signature    VARCHAR(100) DEFAULT '' COLLATION utf8_bin,
				last_login   INTEGER DEFAULT 0
			)
EOT;
		$db->update($sql);
		
		$sql = <<< EOT
			insert into users (name, pass_hash, current_name, email, permissions, signature, last_login)
			values (?, ?, ?, ?, ?)
EOT;
		$ps = $db->prepareStatement($sql);
		
		$ps->setString(0, 'admin');
		$ps->setString(1, md5('admin'.'admin'));
		$ps->setString(2, 'Administrator');
		$ps->setString(3, 'admin@domain.it');
		$ps->setString(4, '1111');
		$ps->update();
		
		$ps->setString(0, 'guest');
		$ps->setString(1, '');
		$ps->setString(2, 'Guest');
		$ps->setString(3, 'junk@domain.it');
		$ps->setString(4, '0010');
		$ps->update();
	}
	
	/**
	 * @param SQLDriver $db
	 * @throws SQLException
	 */
	private static function createCommentsTable($db)
	{
//		$db->update("drop table if exists comments");
		if( self::tableExists($db, "comments") )
			return;
		$sql = <<< EOT
			create table comments (
				pk        SERIAL,
				reference INTEGER, -- FK comments.pk
				path      VARCHAR(100) COLLATION utf8_bin, -- ex.: "/m2/library.html"
				time      INTEGER,  -- timestamp
				name      VARCHAR(50) COLLATION utf8_bin, -- registered user name
				current_name VARCHAR(50) COLLATION utf8_bin, -- displayed user name
				subject   VARCHAR(100) COLLATION utf8_bin,
				body      TEXT COLLATION utf8_bin
			)
EOT;
		$db->update($sql);
	}
	
	/**
	 * @param SQLDriver $db
	 * @throws SQLException
	 */
	private static function createAttachmentsTable($db)
	{
//		$db->update("drop table if exists attachments");
		if( self::tableExists($db, "attachments") )
			return;
		$sql = <<< EOT
			create table attachments (
				pk        SERIAL,
				comments_pk INTEGER,
				name      TEXT,
				type      TEXT,
				length    INTEGER, -- (2^31 - 1) max supported
				content   LONGTEXT
			)
EOT;
		$db->update($sql);
	}
	
	/**
	 * @param SQLDriver $db
	 * @throws SQLException
	 */
	private static function createDataBase($db)
	{
		$dbname = self::DB_NAME;
//		$db->update("drop database if exists $dbname");
		$db->update("create database $dbname character set utf8 collate utf8_general_ci");
		$db->update("use $dbname");
		self::createCommentsTable($db);
	}
	
	/**
	 * @return SQLDriver
	 * @throws SQLException
	 */
	static function checkAll()
	{
		$db = self::connect();
		
		// Create database:
		$dbname = self::DB_NAME;
		try {
			$db->update("use $dbname");
		}
		catch(SQLException $e){
			if( strpos($e->getMessage(), "Unknown database '$dbname'") !== FALSE ){
				self::createDataBase($db);
			} else {
				throw $e;
			}
		}
		
		self::createUsersTable($db);
		self::createCommentsTable($db);
		self::createAttachmentsTable($db);
		return $db;
	}
	
}
