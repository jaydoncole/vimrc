<?php
namespace it\icosaedro\www;
require_once __DIR__ . "/../../../all.php";
use ErrorException;
use it\icosaedro\utils\Random;
use it\icosaedro\sql\SQLException;
use it\icosaedro\web\Html;
use it\icosaedro\web\bt_\Form;
use it\icosaedro\web\bt_\UserSession;
use it\icosaedro\web\controls\Line;

/**
 * Dialog box to set a new password, as we cannot really recover the original
 * password. In order to set a new password, this dialog silently assumes the
 * user remembers exactly its login name and its email address as entered in the
 * preferences dialog. No feedback is given if these data does not match any
 * current user or if the specific user did not entered its email at all.
 * The entry point is the entry method, as usual.
 * @author Umberto Salsi <salsi@icosaedro.it>
 * @version $Date: 2018/07/23 12:41:56 $
 */
class ForgotPasswordMask extends Form {
	
	/**
	 * @var Line
	 */
	private $pretended_name;
	
	/**
	 * @var Line
	 */
	private $pretended_email;
	
	function __construct() {
		parent::__construct();
		$this->pretended_name = new Line($this, "pretended_name");
		$this->pretended_email = new Line($this, "pretended_email");
	}
	
	/**
	 * 
	 * @param string $err
	 */
	function render($err = NULL)
	{
		Common::echoPageHeader();
		$this->open();
		echo "<h1>Forgot password</h1>";
		if( strlen($err) > 0 )
			Html::errorBox("<ul>$err</ul>");
		echo "<p>So you forgot your login password. It happens, sometimes. But you should at least remember your exact login name and your exact email address. Please enter them again here below and you will receive by email a new generated password.</p>";
		echo "<p><b>Please enter your login name:</b><br>";
		$this->pretended_name->render();
		echo "<p><b>Please enter the email address you set in the preferences;</b> if you did not set an email address in your preferences dialog box, sorry but there is not a fallback:<br>";
		$this->pretended_email->addAttributes("size=50");
		$this->pretended_email->render();
		echo "<p>By pressing the confirmation button here below, a new randomly generated password is sent to you and replaces the current one.</p>";
		$this->button("Cancel", "cancelButton");
		Html::echoSpan(5);
		$this->button("Send me new password", "sendNewPasswordButton");
		$this->close();
		Common::echoPageFooter();
	}
	
	
	function cancelButton()
	{
		UserSession::invokeCallBackward();
	}
	
	/**
	 * 
	 * @throws SQLException
	 * @throws ErrorException
	 */
	function sendNewPasswordButton()
	{
		$err = "";
		
		// Validate pretended name:
		$pretended_name = $this->pretended_name->getValue();
		if( strlen($pretended_name) == 0 )
			$err .= "<li>You must specify your login name.</li>";
		
		// Validate pretended email:
		$pretended_email = $this->pretended_email->getValue();
		if( strlen($pretended_email) == 0 )
			$err .= "<li>You must specify your email address.</li>";
		
		if( strlen($err) > 0 ){
			$this->render($err);
			return;
		}
		
		// Lets the user wait a constant interval of time:
		$wait_until = time() + 5;
		
		// Check if that name + email do really exist:
		$ps = SiteSpecific::getDB()->prepareStatement("select name, email from users where name=? and email=?");
		$ps->setString(0, $pretended_name);
		$ps->setString(1, $pretended_email);
		$res = $ps->query();
		
		// Generate new password; avoid any feedback to the remote user:
		if( $res->getRowCount() == 1 ){
			$res->moveToRow(0);
			$name = $res->getStringByName("name");
			$email = $res->getStringByName("email");
			$pass = base64_encode( Random::getCommon()->randomBytes(6) );
			$ps = SiteSpecific::getDB()->prepareStatement("update users where name=? set pass_hash=?");
			$ps->setString(0, $name);
			$ps->setString(1, md5($name . $pass));
			mail($email, "Recovered data", $pass, "From: " . SiteSpecific::ADMIN_EMAIL);
		}

		// Mostly fake confirmation feedback:
		sleep($wait_until - time());
		Common::notice("New password for user " . Html::text($pretended_name)
			. " sent to " . Html::text($pretended_email) . ". But beware: if either the login name OR the email address were not found, actually nothing has been really sent!");
	}
	
	/**
	 * Entry point of the password recovery dialog box.
	 * @param string $name Initial value of the user name text box, typically
	 * coming from the login mask the user was trying to complete with password.
	 * This dialog allows to edit that name, anyway.
	 */
	static function enter($name)
	{
		$f = new self();
		$f->pretended_name->setValue($name);
		$f->render();
	}
	
}
