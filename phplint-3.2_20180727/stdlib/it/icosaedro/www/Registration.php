<?php
namespace it\icosaedro\www;
require_once __DIR__ . "/../../../all.php";
use ErrorException;
use it\icosaedro\sql\SQLException;
use it\icosaedro\web\bt_\Form;
use it\icosaedro\web\bt_\UserSession;
use it\icosaedro\web\controls\Line;
use it\icosaedro\web\controls\Password;
use it\icosaedro\web\controls\TuringTest;
use it\icosaedro\web\controls\ParseException;

/**
 * Allows the guest user to register itself in the system. Only name and pass
 * required. Once registered, the state of the current session is changed
 * accordingly, so the new registered user can continue navigating this web
 * commenting system.
 * @author Umberto Salsi <salsi@icosaedro.it>
 * @version $Date: 2018/07/02 16:15:17 $
 */
class Registration extends Form {
	
	/**
	 * @var Line
	 */
	public $name;
	
	/**
	 * @var Password
	 */
	public $pass;
	
	/**
	 * @var TuringTest
	 */
	public $human;
	
	function __construct()
	{
		parent::__construct();
		$this->name = new Line($this, "name");
		$this->pass = new Password($this, "pass");
		$this->human = new TuringTest($this, "human");
	}


	/**
	 * @param string $err
	 */
	function render($err = NULL)
	{
		Common::echoPageHeader();
		$this->open();
		echo "<h1>Registration</h1>";
		
		if( strlen($err) > 0 )
			Common::info_box("/img/error.png", "Registration error", "<ul>$err</ul>");

		echo <<< EOT
<p>
Why to register into the icosaedro.it WEB Commenting System? Registered users:
<br>- Do not have to reply to a boring Turing-test for each new message they submit.
<br>- May delete their own messages.
<br>- Have their choosen display name "From:" automatically set in each new message.
<br>- Have their preferred signature automatically set at the end of each new message.
<br>Once registered you have access to the preferences dialog box where the
password can be changed and the displayed name, the signature and your email
address can be set. Setting the email address in the preferences dialog box is
recommended as this allows to recover a forgotten password.
</p>
EOT;

		echo <<< EOT
<p><b>Choose your login name.</b>
The login name is the only information you will not be able to change in the
future, so chose it carefully.<br>
EOT;
		$this->name->render();
		
		echo <<< EOT
<p><b>Choose the login password.</b> It can be changed later from your preferences
dialog box. If you forgot your password, a recovering procedure do exist as
explained in the preferences dialog box.<br>
EOT;
		$this->pass->render();
		
		echo <<< EOT
<p><b>Are you human (or a very intelligent machine)?</b> Reply to the following
question to prove you are not a boring spam bot.<br>
EOT;
		$this->human->render();

		echo VSPACE;
		$this->button("Cancel", "cancelButton");
		echo HSPACE;
		$this->button("Register", "registerButton");

		$this->close();
		Common::echoPageFooter();
	}


	/**
	 * 
	 * @throws SQLException
	 */
	function registerButton()
	{
		$err = "";
		
		$name = $this->name->getValue();
		$pass = $this->pass->getValue();
		
		if( strlen($name) == 0 )
			$err .= "<li>Missing login name.</li>";
		else if( strlen($name) > 100 )
			$err .= "<li>Login name too long, max 100 bytes allowed.</li>";
		
		if( strlen($pass) == 0 )
			$err .= "<li>Missing password.</li>";
		else if( strlen($pass) > 100 )
			$err .= "<li>Password too long, max 100 bytes allowed.</li>";
		
		try {
			$this->human->parse();
		} catch (ParseException $e) {
			$err .= "<li>Missing or invalid reply to the human test.</li>";
		}
		
		if( strlen($err) > 0 ){
			$this->render($err);
			return;
		}
		
		sleep(2);
		
		$current_name = $name;
		$email = "";
		$permissions = "0111";
		
		$ps = SiteSpecific::getDB()->prepareStatement("select pk from users where name=?");
		$ps->setString(0, $name);
		$res = $ps->query();
		if( $res->getRowCount() > 0 ){
			$this->render("<li>Sorry, this name is already taken, choose another one.</li>");
			return;
		}
		
		if( strlen(SiteSpecific::ADMIN_EMAIL) > 0 )
			try {
				mail(SiteSpecific::ADMIN_EMAIL, "New registered user",
					self::class . "\nname: $name", "From: " . SiteSpecific::ADMIN_EMAIL);
			}
			catch(ErrorException $e){
				error_log("$e");
			}
		
		$sql = <<< EOT
			insert into users (name, pass_hash, current_name, email, permissions)
			values (?, ?, ?, ?, ?)
EOT;
		$ps = SiteSpecific::getDB()->prepareStatement($sql);
		$ps->setString(0, $name);
		$ps->setString(1, md5($name.$pass));
		$ps->setString(2, $current_name);
		$ps->setString(3, $email);
		$ps->setString(4, $permissions);
		$ps->update();

		UserSession::setSessionParameter('name', $name);
		UserSession::setSessionParameter('current_name', $current_name);
		UserSession::setSessionParameter('email', $email);
		UserSession::setSessionParameter('permissions', $permissions);

		UserSession::stackPush("it\\icosaedro\\www\\Pages::enter", array());
		Common::notice("Your registration is now complete. You may now check your preferences; setting a valid email address is recommended but not mandatory. Welcome aboard.");
	}
	
	
	function cancelButton()
	{
		UserSession::invokeCallBackward();
	}
	
	
	static function enter()
	{
		(new self())->render();
	}

}