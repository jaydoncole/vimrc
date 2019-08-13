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

use RuntimeException;
use it\icosaedro\web\bt_\Form;
use it\icosaedro\web\bt_\UserSession;
use it\icosaedro\web\controls\Password;
use it\icosaedro\web\Html;
use it\icosaedro\web\Http;
use it\icosaedro\sql\SQLException;
use com\acme\www\Common;

/**
 * User personal page where the user may set its preferences. Currently it may
 * only change its password and nothing else.
 * 
 * @author Umberto Salsi <salsi@icosaedro.it>
 * @version $Date: 2018/04/11 04:49:25 $
 */
class UserProfile extends Form {
	
	/**
	 * @var Password
	 */
	private $curr_pass, $new_pass1, $new_pass2;
	
	
	function __construct()
	{
		parent::__construct();
		$this->curr_pass = new Password($this, "cp");
		$this->new_pass1 = new Password($this, "p1");
		$this->new_pass2 = new Password($this, "p2");
	}
	
	
	/**
	 * @param string $err
	 */
	function render($err = NULL)
	{
		$name = UserSession::getSessionParameter('name');
		Http::headerContentTypeHtmlUTF8();
		echo "<html><head><title>User Profile</title></head><body><h1>User Profile</h1>";
		if( strlen($err) > 0 )
			Html::errorBox ("<ul>$err</ul>");
		$this->open();
		echo "Name: ", htmlentities($name), "<p>";
		echo "Current password: "; $this->curr_pass->render(); echo "<p>";
		echo "New password: "; $this->new_pass1->render(); echo "<p>";
		echo "Retype new password: "; $this->new_pass2->render(); echo "<p>";
		echo "<hr>";
		$this->button("Cancel", "cancelButton");
		Html::echoSpan(5);
		$this->button("Change password", "changePasswordButton");
		$this->close();
		echo Common::DISCLAIMER;
		echo "</body></html>";
	}
	
	
	function cancelButton()
	{
		UserSession::invokeCallBackward();
	}
	
	/**
	 * 
	 * @throws SQLException
	 */
	function changePasswordButton()
	{
		$name = UserSession::getSessionParameter('name');
		$curr_pass = $this->curr_pass->getValue();
		$new_pass1 = $this->new_pass1->getValue();
		$new_pass2 = $this->new_pass2->getValue();
		$err = "";
		if( strlen($curr_pass) == 0 )
			$err .= "<li>Missing current password.</li>";
		if( strlen($new_pass1) == 0 )
			$err .= "<li>Missing new password.</li>";
		else if( strlen($new_pass1) < 5 )
			$err .= "<li>The new password must be at least 5 characters long.</li>";
		else if( $new_pass1 !== $new_pass2 ){
			$err .= "<li>The two copies of the password differ.</li>";
			$this->new_pass1->setValue("");
			$this->new_pass2->setValue("");
		}
		if( strlen($err) > 0 ){
			$this->render($err);
			return;
		}
		$db = Common::getDB();
		$ps = $db->prepareStatement("update users set password_hash=? where name=? and password_hash=?");
		$ps->setString(0, md5($name . $new_pass1));
		$ps->setString(1, $name);
		$ps->setString(2, md5($name . $curr_pass));
		$n = $ps->update();
		if( $n < 1 )
			$this->render("<li>Password change failed, check the current password again.</li>");
		else if( $n > 1 )
			throw new RuntimeException("multiple users '$name', found $n");
		else
			UserSession::invokeCallBackward();
	}
	
	/**
	 * This form entry point.
	 */
	static function enter()
	{
		(new self())->render();
	}
	
}
