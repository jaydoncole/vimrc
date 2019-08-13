<?php
namespace it\icosaedro\www;
require_once __DIR__ . "/../../../all.php";
use RuntimeException;
use it\icosaedro\sql\SQLException;
use it\icosaedro\web\Html;
use it\icosaedro\web\controls\Line;
use it\icosaedro\web\controls\Password;
use it\icosaedro\web\bt_\Form;
use it\icosaedro\web\bt_\UserSession;

/*. require_module 'hash'; .*/

/**
 * Login mask.
 * @author Umberto Salsi <salsi@icosaedro.it>
 * @version $Date: 2018/05/15 04:14:10 $
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
		parent::__construct();
		$this->name = new Line($this, "name");
		$this->pass = new Password($this, "pass");
	}

	/**
	 * 
	 * @param string $err
	 */
	function render($err = NULL)
	{
		UserSession::stackReset();
		Common::echoPageHeader();
		$this->open();
		echo "<h1>Login</h1>";
		if( strlen($err) > 0 )
			Html::errorBox($err);
		echo CENTER;
		echo '<table cellspacing=10><tr><td>Name:</td><td>';
		$this->name->addAttributes("autofocus=autofocus");
		$this->name->render();
		echo '</td></tr><tr><td>Password:</td><td>';
		$this->pass->render();
		echo '</td></tr></table>';
		echo VSPACE;
		$this->button("Login", "loginButton");
		echo CENTER_;
		echo VSPACE, "<p align=right>";
		$this->anchor("Forgot password...", "forgotPasswordButton");
		echo "</p>";
		$this->close();
		Common::echoPageFooter();
	}
	

	/**
	 * 
	 * @throws SQLException
	 */
	function loginButton()
	{
		$name = $this->name->getValue();
		$pass = $this->pass->getValue();
		
		if(strlen($name) == 0 || strlen($pass) == 0 ){
			$this->render("Invalid name/password combination.");
			return;
		}
		
		$db = SiteSpecific::getDB();
		
		$ps = $db->prepareStatement("SELECT * FROM users WHERE name=? AND pass_hash=?");
		$ps->setString(0, $name);
		$ps->setString(1, md5($name.$pass));
		$res = $ps->query();
		if( $res->getRowCount() != 1 ){
			$this->render("Invalid name/password combination.");
			return;
		}

		$res->moveToRow(0);
		$name = $res->getStringByName('name'); // safer to get the verbatim value
		$last_login = $res->getIntByName("last_login");
		
		UserSession::setSessionParameter('user_pk', $res->getStringByName('pk'));
		UserSession::setSessionParameter('name', $name);
		UserSession::setSessionParameter('email', $res->getStringByName('email'));
		UserSession::setSessionParameter('current_name', $res->getStringByName('current_name'));
		UserSession::setSessionParameter('permissions', $res->getStringByName('permissions'));
		UserSession::setSessionParameter('signature', $res->getStringByName('signature'));
		
		$ps = $db->prepareStatement("update users set last_login=? where name=?");
		$ps->setInt(0, time());
		$ps->setString(1, $name);
		$ps->update();

		UserSession::stackPush("it\\icosaedro\\www\\Pages::enter", array());

		Common::notice("Wellcome back to the icosaedro.it WEB Commenting System!"
			."<br>Your last login was at "
			.gmdate("Y-m-d, H:i", $last_login) . " UTC.");
	}
	
	function defaultButton()
	{
		try {
			$this->loginButton();
		}
		catch(SQLException $e){
			throw new RuntimeException($e->getMessage(), $e->getCode(), $e);
		}
	}
	
	/**
	 * Performs an automatic login for user 'guest' and displays the comments
	 * related to the requested page.
	 * @throws SQLException
	 */
	static function guestLogin()
	{
		$name = "guest";
		
		$db = SiteSpecific::getDB();
		
		$ps = $db->prepareStatement("SELECT * FROM users WHERE name=?");
		$ps->setString(0, $name);
		$res = $ps->query();
		if( $res->getRowCount() < 1 )
			throw new RuntimeException("missing 'guest' user in users table");
		$res->moveToRow(0);
		$name = $res->getStringByName('name'); // safer to get the verbatim value
		UserSession::setSessionParameter('user_pk', $res->getStringByName('pk'));
		UserSession::setSessionParameter('name', $name);
		UserSession::setSessionParameter('email', $res->getStringByName('email'));
		UserSession::setSessionParameter('current_name', $res->getStringByName('current_name'));
		UserSession::setSessionParameter('permissions', $res->getStringByName('permissions'));
		UserSession::setSessionParameter('signature', $res->getStringByName('signature'));
		
		$ps = $db->prepareStatement("update users set last_login=? where name=?");
		$ps->setInt(0, time());
		$ps->setString(1, $name);
		$ps->update();

		PageComments::enter();
	}
	
	
	function forgotPasswordButton()
	{
		$this->returnTo("render");
		ForgotPasswordMask::enter( $this->name->getValue() );
	}
	
	/**
	 * 
	 * @param string $name
	 */
	static function enter($name)
	{
		$f = new self();
		$f->name->setValue($name);
		$f->render();
	}


	static function logout()
	{
		Common::echoPageHeader();
		UserSession::formOpen();
		echo VSPACE;
		Common::info_box("/img/warning.png", "Logout", "Are you sure to log-out?");
		echo VSPACE, CENTER;
		UserSession::button("Cancel", "it\\icosaedro\\www\\Pages::enter");
		echo HSPACE;
		UserSession::button("Logout", UserSession::class . "::logout");
		echo CENTER_, VSPACE;
		UserSession::formClose();
		Common::echoPageFooter();
	}

}
