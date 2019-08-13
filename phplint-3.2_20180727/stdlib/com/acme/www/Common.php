<?php
/**
 * PHPLint demo web site. This file is part of the sample fictional Acme web
 * site built using the PHPLint standard library web tools, and it is meant to
 * illustrate the basic usage of sticky forms, bt_, and bt forms; a tutorial is
 * also available at {@link http://www.icosaedro.it/phplint/web}.
 * @package SampleAcmeWebSite
 */

namespace com\acme\www;

require_once __DIR__ . "/../../../all.php";

/*. require_module 'hash'; .*/

use RuntimeException;
use it\icosaedro\sql\SQLDriverInterface as Driver;
use it\icosaedro\sql\SQLException;

/**
 * Customize according to your installation. This class collects some site
 * specific configuration parameter and some handy function needed by our
 * pages. Here we also create the initial SQLite data base and we made
 * available a data base connection implemented using the PHPLint SQL abstraction
 * library.
 */
class Common {
	
	/** If notices should be reported by the it\icosaedro\web\Log class. */
	const LOG_NOTICES = FALSE;
	
	/** If secure HTTP is mandatory. */
	const REQUIRE_HTTPS = FALSE;
	
	/**
	 * Bt_ working directory. BEWARE: this directory must be located outside
	 * the document root of the web server as it contains users' session
	 * sensitive data!
	 */
	const BT_BASE_DIR = __DIR__ . "/../../../../../BT_STATE/";
	
	/** URL of the dispatcher page. */
	const DISPATCHER_URL = "/bt/index.php";
	
	/** URL of the login page. */
	const LOGIN_URL = "/bt/login.php";
	
	/** Landing page function after login. */
	const DASHBOARD_FUNCTION = "com\\acme\\www\\Dashboard::enter";
	
	/**
	 * If JobsMonitor.php may start new jobs. BEWARE: enabling this feature can
	 * be a security risk. Before doing this, ensure HTTPS be enabled and change
	 * the admin password.
	 */
	const JOBS_ALLOWS_START = TRUE;
	
	/**
	 * Singleton instance of the DB.
	 * @var Driver
	 */
	private static $db;
	
	/**
	 * Returns the instance to a sample data base created using SQLite.
	 * Here we create a simple users table with name and the md5 of the name
	 * joined with the password.
	 * It is very important that the "name" column of the "users" table
	 * be compared using the binary collation. Under SQLite this is the
	 * default. Under MySQL you MUST add the attributes "collate utf8_bin"
	 * to the "name" column.
	 * Two users are added: "admin" with pass "admin", and "guest" with
	 * empty pass.
	 * @return Driver
	 * @throws SQLException
	 */
	private static function openDb()
	{
		$dbpath = __DIR__ . "/sampledb.sqlite";
		if( file_exists($dbpath) )
			return new \it\icosaedro\sql\sqlite\Driver($dbpath, FALSE);
		$db = new \it\icosaedro\sql\sqlite\Driver($dbpath, TRUE);
		$sql = <<< EOT
			create table users (
				id integer primary key autoincrement,
				name text unique not null,
				password_hash text not null
			)
EOT;
		$db->update($sql);
		$s = md5("adminadmin");
		$db->update("insert into users (name, password_hash) values ('admin', '$s')");
		$s = md5("guest");
		$db->update("insert into users (name, password_hash) values ('guest', '$s')");
		return $db;
	}
	
	/**
	 * Returns a singleton instance of the DB connection.
	 * @return Driver
	 * @throws SQLException
	 */
	static function getDB()
	{
		if( self::$db !== NULL )
			return self::$db;
		return self::$db = self::openDb();
	}

	/**
	 * Returns true if the login is valid.
	 * @param string $name
	 * @param string $pass
	 * @return boolean
	 * @throws SQLException
	 */
	static function isValidLogin($name, $pass)
	{
		if( strlen($name) == 0 || strlen($name) > 100 )
			return FALSE;
		$db = self::getDB();
		$ps = $db->prepareStatement("select name from users where name=? and password_hash=?");
		$ps->setString(0, $name);
		$ps->setString(1, md5($name . $pass));
		$rs = $ps->query();
		$n = $rs->getRowCount();
		if( $n == 0 )
			return FALSE;
		else if( $n == 1 )
			return TRUE;
		else
			throw new RuntimeException("found $n users with name $name");
	}
	
	
	const DISCLAIMER = <<< EOT
<hr><p align=justify><small>

Demo pages that illustrates the sample web site built using the bt_ tools as
described in the tutorial available at
<a href="http://www.icosaedro.it/phplint/web">www.icosaedro.it/phplint/web</a>.
			
Every single page is dynamically generated and the navigation path is forced by
bt_. Please, <u>do not use the navigation buttons</u> on your browser, this is
an application served through the web and not a real web site. So, the "back"
and "reload" buttons do not work as expected because you are not really navigating
a tree of static documents.

The source code of these pages along with the libraries these are made of are
part of the <a href="http://www.icosaedro.it/phplint">PHPLint standard library</a>.

A "guest" user is allowed to log-in with empty password but limited privileges
are granted.
</small></p>
EOT;
	
}
