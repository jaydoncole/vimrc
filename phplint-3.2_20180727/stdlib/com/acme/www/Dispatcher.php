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

use Exception;
use it\icosaedro\web\Http;
use it\icosaedro\web\Log;
use it\icosaedro\web\bt_\UserSession;
use com\acme\www\Common;

/**
 * Implements the dispatcher function invoked by the /bt/index.php web page.
 * We could put these few line in that same page avoiding this file at all,
 * but it is easier for me to work on the "library side" of the project than
 * on the "web side", so I moved the code here.
 */
class Dispatcher {
	
	/**
	 * @throws Exception
	 */
	static function dispatchRequest()
	{
		Log::$logNotices = Common::LOG_NOTICES;
		
		if( Common::REQUIRE_HTTPS && ! Http::isSecureConnection() ){
			Http::headerStatus(Http::STATUS_FORBIDDEN);
			return;
		}

		new UserSession(
				NULL,
				Common::BT_BASE_DIR,
				Common::DISPATCHER_URL,
				Common::LOGIN_URL,
				Common::DASHBOARD_FUNCTION
		);
	}
	
}
