<?php
namespace it\icosaedro\www;
require_once __DIR__ . "/../../../all.php";
use it\icosaedro\sql\SQLException;
use it\icosaedro\web\Html;
use it\icosaedro\web\bt_\Form;
use it\icosaedro\web\controls\Line;
use it\icosaedro\web\controls\Spinner;
use it\icosaedro\web\controls\ParseException;
use it\icosaedro\sql\ResultSet;


class UsersMask extends Form {
	
	/** @var Spinner */
	private $pk;
	
	/** @var Line */
	private $name;
	
	/** @var Line */
	private $current_name;
	
	/** @var Line */
	private $email;
	
	function __construct()
	{
		parent::__construct();
		$this->pk = new Spinner($this, "pk");
		$this->pk->setMinMaxStep(1, 0x7fffffff, 1);
		$this->name = new Line($this, "name");
		$this->current_name = new Line($this, "current_name");
		$this->email = new Line($this, "email");
	}
	
	/**
	 * @param ResultSet $res
	 * @throws SQLException
	 */
	private function showResult($res)
	{
		if( $res->getRowCount() == 0 ){
			echo "<p><i>No results found.</i></p>";
			return;
		}
		echo "<table cellpadding=3 cellspacing=0 border=1><tr>",
			"<th>pk</th>",
			"<th>name</th>",
			"<th>current_name</th>",
			"<th>email</th>",
			"<th>permissions</th>",
			"<th>last_login</th>",
			"</tr>";
		for($i = 0; $i < $res->getRowCount(); $i++){
			$res->moveToRow($i);
			$perms = $res->getStringByName("permissions");
			if( $perms === "0111" )
				$color = "#afa";
			else
				$color = "#ff5";
			$pk = $res->getIntByName("pk");
			echo "<tr>",
				"<td>";
			$this->anchor("$pk", "userButton", $pk);
			echo "</td>",
				"<td>", $res->getStringByName("name"), "</td>",
				"<td>", $res->getStringByName("current_name"), "</td>",
				"<td>", $res->getStringByName("email"), "</td>",
				"<td bgcolor='$color'>$perms</td>",
				"<td>", gmdate("Y-m-d, H:i", $res->getIntByName("last_login")), " UTC</td>",
				"</tr>";
		}
		echo "</table>";
	}
	
	/**
	 * @param string $l
	 */
	private function label($l)
	{
		echo "<br><span style='display: inline-block; min-width: 8em; text-align: right;'>$l:</span> ";
	}
	
	/**
	 * @param string $err
	 * @param ResultSet $res
	 */
	function render($err = NULL, $res = NULL)
	{
		Common::echoPageHeader();
		echo "<h1>Users search mask</h1>";
		if( strlen($err) > 0 )
			Common::info_box ("/img/error.png", "Error", $err);
		
		$this->open();
		$this->label("pk");           $this->pk->render();
		$this->label("name");         $this->name->render();
		$this->label("current_name"); $this->current_name->render();
		$this->label("email");        $this->email->render();
		echo "<br>";
		$this->button("Search", "searchButton");
		
		if( $res != NULL ){
			try {
				$this->showResult($res);
			}
			catch(SQLException $e){
				echo "<pre>", Html::text("$e"), "</pre>";
			}
		}
		
		$this->close();
		Common::echoPageFooter();
	}
	
	/**
	 * @return ResultSet
	 * @throws SQLException
	 */
	private function doSearch()
	{
		$sql = "select pk, name, current_name, email, permissions, last_login from users";
		$factors = /*. (string[int]) .*/ array();
		if( strlen($this->pk->getValue()) > 0 )
			$factors[] = "pk=?";
		if( strlen($this->name->getValue()) > 0 )
			$factors[] = "name=?";
		if( strlen($this->current_name->getValue()) > 0 )
			$factors[] = "current_name=?";
		if( strlen($this->email->getValue()) > 0 )
			$factors[] = "email=?";
		$where = implode(" and ", $factors);
		if( strlen($where) > 0 )
			$sql .= " where $where";
		$sql .= " order by name asc";
		$ps = SiteSpecific::getDB()->prepareStatement($sql);
		$i = 0;
		if( strlen($this->pk->getValue()) > 0 )
			$ps->setInt($i++, (int) $this->pk->getValue());
		if( strlen($this->name->getValue()) > 0 )
			$ps->setString($i++, $this->name->getValue());
		if( strlen($this->current_name->getValue()) > 0 )
			$ps->setString($i++, $this->current_name->getValue());
		if( strlen($this->email->getValue()) > 0 )
			$ps->setString($i++, $this->email->getValue());
		return $ps->query();
	}
	
	function searchButton()
	{
		try {
			$this->pk->parse();
		} catch (ParseException $e) {
			if( $e->getReason() != ParseException::REASON_EMPTY ){
				$this->render("pk: " . Html::text($e->getMessage()));
				return;
			}
		}
		
		try {
			$res = $this->doSearch();
		} catch (SQLException $e) {
			$this->render("<pre>" . Html::text("$e") . "</pre>");
			return;
		}
		
		$this->render("", $res);
	}
	
	/**
	 * @param int $pk
	 * @throws SQLException
	 */
	function userButton($pk)
	{
		$this->returnTo("render");
		UserMask::enter($pk);
	}
	
	static function enter()
	{
		(new self())->render();
	}
	
}
