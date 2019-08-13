<?php

/**
 * Request handler for all the web commenting system using bt_.
 * The web page that implements the request handler, for example
 * "/comments/rh.php", may simply consist of a single line of code requiring
 * this file.
 * If a session does not already exist (the normal situation for a new visitor)
 * a guest session is created; registered users may then click on the "Login"
 * link to turn the session into a register user session.
 * @author Umberto Salsi <salsi@icosaedro.it>
 * @version $Date: 2018/05/09 10:11:57 $
 * @package rh.php
 */

namespace it\icosaedro\www;
require_once __DIR__ . "/../../../all.php";
use it\icosaedro\web\Log;
use it\icosaedro\web\bt_\UserSession;

Log::$logNotices = SiteSpecific::LOG_NOTICES;

if( isset($_COOKIE[UserSession::COOKIE_NAME]) ){
	
	new UserSession(
			NULL,
			SiteSpecific::BT_BASE_DIR,
			SiteSpecific::DISPATCHER_URL,
			SiteSpecific::LOGIN_URL,
			Common::DASHBOARD_FUNCTION
	);

} else {
	
	new UserSession(
			"guest",
			SiteSpecific::BT_BASE_DIR,
			SiteSpecific::DISPATCHER_URL,
			SiteSpecific::GUEST_LOGIN_URL,
			Common::GUEST_DASHBOARD_FUNCTION
	);
	
}
