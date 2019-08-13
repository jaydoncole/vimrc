<?php
namespace it\icosaedro\www;
require_once __DIR__ . "/../../../all.php";
use ErrorException;
use it\icosaedro\sql\SQLException;
use it\icosaedro\web\Html;
use it\icosaedro\web\bt_\Form;
use it\icosaedro\web\bt_\UserSession;
use it\icosaedro\web\controls\Line;

/**
 * New email confirmation dialog box. The entry method allows to specify the new
 * email address to be confirmed; the method sends to that email address a
 * randomly generated confirmation code the user should receive; the user must
 * then copy that confirmation code into the dialog box. Once the expected
 * confirmation code as been provided, this mask saves the new email.
 * @author Umberto Salsi <salsi@icosaedro.it>
 * @version $Date: 2018/07/02 16:14:00 $
 */
class EmailConfirmation extends Form {
	
	/**
	 * @var string
	 */
	private $email;
	
	/**
	 * @var string
	 */
	private $exp_code;
	
	/**
	 * @var Line
	 */
	private $got_code;
	
	function __construct() {
		parent::__construct();
		$this->got_code = new Line($this, "got_code");
	}
	
	function save()
	{
		$this->setData("email", $this->email);
		$this->setData("exp_code", $this->exp_code);
	}
	
	function resume()
	{
		$this->email = (string) $this->getData("email");
		$this->exp_code = (string) $this->getData("exp_code");
	}
	
	/**
	 * @param string $err
	 */
	function render($err = NULL)
	{
		Common::echoPageHeader();
		$this->open();
		echo "<h1>Preferences - Email confirmation</h1>";
		
		if( strlen($err) > 0 )
			Common::info_box ("/img/error.png", "Error", "<ul>$err</ul>");
		
		echo "<p>A confirmation code has been sent to your new email address:</p>",
			"<blockquote>", Html::text($this->email), "</blockquote>",
			"Please, copy the confirmation code you received in your mailbox into the box here below:<br>";
		
		$this->got_code->render();

		echo VSPACE;
		$this->button("Cancel", "cancelButton");
		echo HSPACE;
		$this->button("Confirm", "confirmButton");

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
	function confirmButton()
	{
		$err = "";
		
		$got_code = $this->got_code->getValue();
		if( strlen($got_code) == 0 )
			$err .= "<li>Missing confirmation code.</li>";
		else if( $got_code !== $this->exp_code )
			$err .= "<li>Invalid confirmation code.</li>";
		
		if( strlen($err) > 0 ){
			$this->render($err);
			return;
		}
		
		$ps = SiteSpecific::getDB()->prepareStatement("update users set email=? where name=?");
		$ps->setString(0, $this->email);
		$ps->setString(1, UserSession::getSessionParameter("name"));
		$ps->update();
		
		UserSession::setSessionParameter('email', $this->email);
		UserSession::invokeCallBackward();
	}
	
	/**
	 * @param string $email
	 */
	static function enter($email)
	{
		$f = new self();
		$f->email = $email;
		$f->exp_code = "";
		while( strlen($f->exp_code) < 9 )
			$f->exp_code .= "" . mt_rand(100, 999);
		// FIXME: simple test to check for actual smtp server availability:
		if( strlen(SiteSpecific::ADMIN_EMAIL) > 0 )
			try {
				mail($email, "Confirmation code", $f->exp_code, "From: " . SiteSpecific::ADMIN_EMAIL);
			}
			catch(ErrorException $e){
				error_log("$e");
			}
		$f->render();
	}
}
