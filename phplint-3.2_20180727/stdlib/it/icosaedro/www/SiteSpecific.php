<?php
namespace it\icosaedro\www;
require_once __DIR__ . "/../../../all.php";
use it\icosaedro\sql\SQLDriverInterface as SQLDriver;
use it\icosaedro\sql\SQLException;

/**
 * Site specific parameters and constants. To support this web commenting
 * system, the only actual web page the web site must provide is the request
 * handle. In our example that page is "/comments/rh.php" and the only thing it
 * has to do is to require the rh.php file in this same directory which contains
 * the actual code.
 * @author Umberto Salsi <salsi@icosaedro.it>
 * @version $Date: 2018/05/09 10:11:57 $
 */
class SiteSpecific {
	
	/**
	 * Notices should be enabled only for debugging.
	 */
	const LOG_NOTICES = FALSE;
	
	/**
	 * Important events are notified to this email address.
	 * Events include: new registered user, new message.
	 * Comma separated list of addresses also allowed.
	 * Empty string to disable sending email.
	 */
	const ADMIN_EMAIL = "";
	
	/**
	 * Base URL of all the pages.
	 */
	const WEB_BASE = "http://localhost:81";
	
	/**
	 * Document root path of the web site.
	 */
	const PATH_BASE = "c:\\wamp\\www";
	
	/**
	 * Bt_ working directory. BEWARE: this directory must be located outside
	 * the document root of the web server as it contains users' session
	 * sensitive data!
	 */
	const BT_BASE_DIR = "c:\\wamp\\BT_STATE";
	
	/**
	 * URL of the bt_ dispatcher page.
	 */
	const DISPATCHER_URL = "/comments/rh.php";
	
	/**
	 * URL of the login page. Our special dispatcher automatically creates a
	 * 'guest' session, avoiding the login procedure to the visitors. An actual
	 * login page for registered users is available in the menu. This page is
	 * invoked by bt_ only if the session cookie exists but it is invalid for
	 * some reason.
	 */
	const LOGIN_URL = "/comments/rh.php";
	
	/**
	 * URL of the login page. For visitors we create a 'guest' session, no login.
	 */
	const GUEST_LOGIN_URL = "it\\icosaedro\\www\\PageComments::guestEnter";
	
	/**
	 * Secret key to calculate the HMAC code. Each page of the web site may
	 * invoke the web commenting system with a URL parameter carrying the path
	 * of the page itself; an HMAC code protect that value from tampering,
	 * so users cannot comment arbitrary paths provided by their themself.
	 */
	const HMAC_KEY = "abcdefghij";
	
	/**
	 * Cached database connection.
	 * @var SQLDriver
	 */
	private static $cached_db;

	/**
	 * Returns an instance of the database connection. The result is cached.
	 * If the connection fails, the general database check and creation is
	 * invoked in an attempt to create the initial structure. If even this
	 * fails, it is a fatal error.
	 * @return SQLDriver
	 * @throws SQLException
	 */
	static function getDB()
	{
		if( self::$cached_db !== NULL )
			return self::$cached_db;
		try {
			// Regular direct access to existing database:
			self::$cached_db = new \it\icosaedro\sql\mysql\Driver(array("localhost", "root", "", "icodb"));
		}
		catch(SQLException $e){
			// Try again checking and possibly creating the database:
			self::$cached_db = DBManagement::checkAll();
		}
		return self::$cached_db;
	}
}
