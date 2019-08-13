<?php
namespace it\icosaedro\www;
require_once __DIR__ . "/../../../all.php";
use it\icosaedro\sql\SQLException;
use it\icosaedro\web\bt_\Form;
use it\icosaedro\web\bt_\UserSession;
use it\icosaedro\web\controls\Line;
use it\icosaedro\web\controls\Password;
use it\icosaedro\web\controls\Text;

/**
 * Allows the registered user to set its preferences.
 * @author Umberto Salsi <salsi@icosaedro.it>
 * @version $Date: 2018/06/06 10:00:02 $
 */
class Preferences extends Form {
	
	/**
	 * Email regular expression as suggested in
	 * https://www.w3.org/TR/html5/sec-forms.html#email-state-typeemail
	 * See the reference for limitations.
	 * Note only ASCII allowed; internationalized email addresses should then be
	 * entered in punycode (RFC 5322).
	 */
	const EMAIL_REGEX = "/^[a-zA-Z0-9.!#\$%&'*+\\/=?^_`{|}~-]+@[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?(?:\\.[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?)*\$/sD";
	
	/**
	 * @var Password
	 */
	private $pass;
	
	/**
	 * @var string
	 */
	private $curr_email = "";
	
	/**
	 * @var Line
	 */
	private $email;
	
	/**
	 * @var Line
	 */
	private $current_name;
	
	/**
	 * @var Text
	 */
	private $signature;
	
	function __construct()
	{
		parent::__construct();
		$this->pass = new Password($this, "pass");
		$this->email = new Line($this, "email");
		$this->current_name = new Line($this, "current_name");
		$this->signature = new Text($this, "signature");
	}
	
	/**
	 * @param string $err
	 */
	function render($err = NULL)
	{
		Common::echoPageHeader();
		$this->open();
		echo "<h1>Preferences</h1>";
		
		if( strlen($err) > 0 )
			Common::info_box("/img/error.png", "Error", "<ul>$err</ul>");
		
		echo <<< EOT
<p><b>Change password.</b> The new password will replace the current one.<br>
EOT;
		$this->pass->render();
		
		echo <<< EOT
<p><b>Your email address.</b> It is not mandatory, but it allows to recover a
forgotten password. Email addresses are never displayed in the web site and
their are currenlty used only to recover a forgotten password.<br>
EOT;
		$this->email->addAttributes("size=60");
		$this->email->render();

		echo <<< EOT
<p><b>Enter your displayed name.</b> This name will be displayed as the "From"
value of your messages. If omitted, the login name will be used instead.<br>
EOT;
		$this->current_name->addAttributes("size=60");
		$this->current_name->render();
	
		echo <<< EOT
<p><b>Signature of your messages.</b> It is automatically appended to any your
new message.<br>
EOT;
		$this->signature->addAttributes("cols=80 rows=5");
		$this->signature->render();

		echo VSPACE;
		$this->button("Cancel", "cancelButton");
		echo HSPACE;
		$this->button("Save", "saveButton");

		$this->close();
		Common::echoPageFooter();
	}
	
	function cancelButton()
	{
		UserSession::invokeCallBackward();
	}
	
	/**
	 * @throws SQLException
	 */
	function saveButton()
	{
		$err = "";
		
		$pass = $this->pass->getValue();
		if( strlen($pass) > 100 )
			$err .= "<li>Password too long, max 100 bytes allowed.</li>";
		
		$email = $this->email->getValue();
		if( strlen($email) > 0 ){
			if( strlen($email) > 100 )
				$err .= "<li>Email too long, max 100 bytes allowed.</li>";
			else if( 1 != preg_match(self::EMAIL_REGEX, $email) ){
				$err .= "<li>Invalid email syntax.</li>";
			}
		}
		
		$current_name = $this->current_name->getValue();
		if( strlen($current_name) > 100 )
			$err .= "<li>Displayed name too long, max 100 bytes allowed.</li>";
		
		$signature = $this->signature->getValue();
		if( strlen($signature) > 400 )
			$err .= "<li>Signature too long, max 400 bytes allowed.</li>";
		
		if( strlen($err) > 0 ){
			$this->render($err);
			return;
		}
		
		$db = SiteSpecific::getDB();
		
		$name = UserSession::getSessionParameter("name");
		
		if( strlen($pass) > 0 ){
			$ps = $db->prepareStatement("update users set pass_hash=? where name=?");
			$ps->setString(0, md5($name.$pass));
			$ps->setString(1, $name);
			$ps->update();
		}
		
		if( strlen($current_name) == 0 )
			$current_name = $name;
		
		$ps = $db->prepareStatement("update users set current_name=?, signature=? where name=?");
		$ps->setString(0, $current_name);
		$ps->setString(1, $signature);
		$ps->setString(2, $name);
		$ps->update();
		
		UserSession::setSessionParameter('current_name', $current_name);
		UserSession::setSessionParameter('signature', $signature);
		
		if( $email !== $this->curr_email ){
			if( strlen($email) == 0 ){
				// Emptied email. Blindly store.
				$ps = $db->prepareStatement("update users set email='' where name=?");
				$ps->setString(0, $name);
				UserSession::setSessionParameter("email", "");
			} else {
				// New email requires confirmation.
				EmailConfirmation::enter($email);
				return;
			}
		}
		
		UserSession::invokeCallBackward();
	}
	
	function save()
	{
		parent::save();
		$this->setData("curr_email", $this->curr_email);
	}
	
	function resume()
	{
		parent::resume();
		$this->curr_email = (string) $this->getData("curr_email");
	}
	
	static function enter()
	{
		$f = new self();
		$f->curr_email = UserSession::getSessionParameter("email");
		$f->email->setValue($f->curr_email);
		$f->current_name->setValue( UserSession::getSessionParameter("current_name") );
		$f->signature->setValue( UserSession::getSessionParameter("signature") );
		$f->render();
	}
	
}
