<?php
namespace it\icosaedro\www;
require_once __DIR__ . "/../../../all.php";
use it\icosaedro\sql\SQLException;
use it\icosaedro\web\Html;
use it\icosaedro\web\bt_\Form;
use it\icosaedro\web\bt_\UserSession;
use it\icosaedro\web\controls\CheckBox;
use it\icosaedro\web\controls\Line;
use it\icosaedro\web\controls\Password;


class UserMask extends Form {
	
	/** @var int */
	private $pk = 0;
	
	/** @var Line */
	private $name;
	
	/** @var Password */
	private $pass;
	
	/** @var Line */
	private $current_name;
	
	/** @var Line */
	private $email;
	
	/** @var CheckBox */
	private $perm_is_admin;
	
	/** @var CheckBox */
	private $perm_prefs;
	
	/** @var CheckBox */
	private $perm_post;
	
	/** @var CheckBox */
	private $perm_delete;
	
//	/** @var Text */
//	private $signature;
//	
//	/** @var Spinner */
//	private $last_login;
	
	function __construct()
	{
		parent::__construct();
		$this->pk = 0;
		$this->name = new Line($this, "name");
		$this->pass = new Password($this, "pass");
		$this->current_name = new Line($this, "current_name");
		$this->email = new Line($this, "email");
		$this->perm_is_admin = new CheckBox($this, "perm_is_admin", "is admin");
		$this->perm_prefs = new CheckBox($this, "perm_prefs", "may set preferences");
		$this->perm_post = new CheckBox($this, "perm_post", "may post new messages");
		$this->perm_delete = new CheckBox($this, "perm_delete", "may delete its own messages");
//		$this->signature = new Text($this, "signature");
//		$this->last_login = new Spinner($this, "last_login");
	}
	
	function save()
	{
		parent::save();
		$this->setData("pk", $this->pk);
	}
	
	function resume()
	{
		parent::resume();
		$this->pk = (int) $this->getData("pk");
	}
	
	/**
	 * @param string $err
	 */
	function render($err = NULL)
	{
		Common::echoPageHeader();
		echo "<h1>User mask</h1>";
		if( strlen($err) > 0 )
			Common::info_box ("/img/error.png", "Error", $err);
		
		$this->open();
		echo "pk: ", $this->pk;
		echo "<br>name: "; $this->name->render();
		echo "<br>pass: "; $this->pass->render();
		$this->current_name->addAttributes("size=50");
		echo "<br>current_name: "; $this->current_name->render();
		$this->email->addAttributes("size=50");
		echo "<br>email: "; $this->email->render();
		echo "<br>Permissions:";
		echo "<br>";  $this->perm_is_admin->render();
		echo "<br>";  $this->perm_prefs->render();
		echo "<br>";  $this->perm_post->render();
		echo "<br>";  $this->perm_delete->render();
		echo VSPACE;
		$this->button("Cancel", "cancelButton");
		echo HSPACE;
		$this->button("Delete...", "deleteButton");
		echo HSPACE;
		$this->button("Save", "saveButton");
		$this->close();
		Common::echoPageFooter();
	}
	
	function cancelButton()
	{
		UserSession::invokeCallBackward();
	}
	
	function deleteButton()
	{
		// Ask confirmation and invoke deleteConfirm().
		$this->returnTo("deleteConfirm");
		Common::confirm("User deletion",
			"Are you sure to delete the user <p><center><b>"
			. Html::text($this->name->getValue())
			. "</b> ?</center>", "Cancel", "Delete");
	}
	
	/**
	 * Deletes the user if confirmed in the dialog box.
	 * @param boolean $confirmed TRUE to delete, FALSE to ignore.
	 * @throws SQLException
	 */
	function deleteConfirm($confirmed)
	{
		if( ! $confirmed ){
			$this->render();
			return;
		}
		$ps = SiteSpecific::getDB()->prepareStatement("delete from users where pk=?");
		$ps->setInt(0, $this->pk);
		$ps->update();
		UserSession::invokeCallBackward();
	}
	
	/**
	 * @throws SQLException
	 */
	function saveButton()
	{
		$perms =
			($this->perm_is_admin->isChecked()? "1":"0").
			($this->perm_prefs   ->isChecked()? "1":"0").
			($this->perm_post    ->isChecked()? "1":"0").
			($this->perm_delete  ->isChecked()? "1":"0");
		$ps = SiteSpecific::getDB()->prepareStatement("update users set name=?, current_name=?, email=?, permissions=? where pk=?");
		$ps->setString(0, $this->name->getValue());
		$ps->setString(1, $this->current_name->getValue());
		$ps->setString(2, $this->email->getValue());
		$ps->setString(3, $perms);
		$ps->setInt(4, $this->pk);
		$ps->update();
		
		$pass = $this->pass->getValue();
		if( strlen($pass) > 0 ){
			$ps = SiteSpecific::getDB()->prepareStatement("update users set pass_hash=? where pk=?");
			$ps->setString(0, md5($this->name->getValue() . $pass));
			$ps->update();
		}
		
		UserSession::invokeCallBackward();
	}
	
	/**
	 * @param int $pk Users PK.
	 * @throws SQLException
	 */
	static function enter($pk)
	{
		$f = new self();
		$f->pk = $pk;
		$res = SiteSpecific::getDB()->query("select name, current_name, email, permissions from users where pk=$pk");
		$res->moveToRow(0);
		$f->name->setValue($res->getStringByName("name"));
		$f->current_name->setValue($res->getStringByName("current_name"));
		$f->email->setValue($res->getStringByName("email"));
		$perms = $res->getStringByName("permissions");
		if( strlen($perms) < 4 )
			$perms .= str_repeat ("0", 4 - strlen($perms));
		$f->perm_is_admin->setChecked($perms[0] === "1");
		$f->perm_prefs   ->setChecked($perms[1] === "1");
		$f->perm_post    ->setChecked($perms[2] === "1");
		$f->perm_delete  ->setChecked($perms[3] === "1");
		$f->render();
	}
	
}
