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
use it\icosaedro\web\Html;
use it\icosaedro\web\Http;
use it\icosaedro\web\Form;
use it\icosaedro\web\Log;
use it\icosaedro\web\controls\Line;
use it\icosaedro\web\controls\Password;
use it\icosaedro\web\bt_\UserSession;

/**
 * Login page implemented as a sticky form. Once the user has been successfully
 * authenticated, a new bt_ session is started and the user is sent to its
 * dashboard page.
 * @author Umberto Salsi <salsi@icosaedro.it>
 * @version $Date: 2018/04/11 04:49:25 $
 */
class Login extends Form {

	/**
	 * @var Line
	 */
	private $name;

	/**
	 * @var Password
	 */
	private $pass;
	
	function __construct()
	{
		parent::__construct(FALSE);
		$this->name = new Line($this, "name");
		$this->pass = new Password($this, "pass");
	}

	/**
	 * @param string $err
	 */
	function render($err = NULL)
	{
		Log::$logNotices = Common::LOG_NOTICES;
		
		if( Common::REQUIRE_HTTPS && ! Http::isSecureConnection() ){
			Http::headerStatus(Http::STATUS_FORBIDDEN);
			return;
		}
		
		Http::headerContentTypeHtmlUTF8();
		echo "<html><body><h1>Login</h1>";
		$this->open();
		if( strlen($err) > 0 )
			Html::errorBox("<p>ERROR: ". htmlspecialchars($err) ."</p>");
		echo "Name: ";
		$this->name->addAttributes("required autofocus=autofocus");
		$this->name->render();
		echo "<p>Password: ";
		$this->pass->render();
		echo "<p>";
		$this->button("Login", "loginButton");
		$this->close();
		echo Common::DISCLAIMER;
		echo "</body></html>";
	}
	
	/**
	 * @throws Exception
	 */
	function loginButton()
	{
		$name = $this->name->getValue();
		$pass = $this->pass->getValue();
		if( Common::isValidLogin($name, $pass) ){
			new UserSession(
				$name,
				Common::BT_BASE_DIR,
				Common::DISPATCHER_URL,
				Common::LOGIN_URL,
				Common::DASHBOARD_FUNCTION
			);
		} else {
			$this->render("Invalid login, check name and password.");
		}
	}
	
}
