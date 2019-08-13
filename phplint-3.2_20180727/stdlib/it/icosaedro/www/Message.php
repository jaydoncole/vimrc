<?php
namespace it\icosaedro\www;
require_once __DIR__ . "/../../../all.php";
use it\icosaedro\sql\ResultSet;
use it\icosaedro\sql\SQLException;
use it\icosaedro\web\Html;

/**
 * A message entered in the web commenting system.
 * 
 * <pre>
 * CREATE TABLE comments (
 * 	pk        SERIAL,
 * 	reference INTEGER, -- FK comments.pk
 * 	path      VARCHAR(100), -- ex.: "/m2/library.html"
 * 	time      INTEGER,  -- date of the msg as seconds from time()
 * 	name      VARCHAR(50), -- = users.name
 * 	current_name VARCHAR(50), -- = users.current_name
 * 	subject   VARCHAR(100),
 * 	body      TEXT
 * );
 * </pre>
 * 
 * @author Umberto Salsi <salsi@icosaedro.it>
 * @version $Date: 2018/05/09 10:11:31 $
 */
class Message
{
	/**
	 * Primary key of this message.
	 * @var int
	 */
	public $pk = 0;
	
	/**
	 * Referred message or zero if this is a root message.
	 * @var int
	 */
	public $reference = 0;
	
	/**
	 * Path to the referred resource. Example: "/phplint/download.html".
	 * No parameters.
	 * @var string
	 */
	public $path = "";
	
	/**
	 * When this message has been added or modified.
	 * @var int
	 */
	public $time = 0;
	
	/**
	 * Registered user's name of the author.
	 * @var string
	 */
	public $name = "";
	
	/**
	 * Displayed name of the author.
	 * @var string
	 */
	public $current_name = "";
	
	/**
	 * Subject.
	 * @var string
	 */
	public $subject = "";
	
	/**
	 * Body.
	 * @var string
	 */
	public $body = "";

	public function __construct()
	{
		$this->time = time();
	}
	
	/**
	 * 
	 * @param ResultSet $res
	 * @return self
	 * @throws SQLException
	 */
	static function fromResultSet($res)
	{
		$m = new self();
		$m->pk        = $res->getIntByName("pk");
		$m->reference = $res->getIntByName("reference");
		$m->path      = $res->getStringByName("path");
		$m->time      = $res->getIntByName("time");
		$m->name      = $res->getStringByName("name");
		$m->current_name = $res->getStringByName("current_name");
		$m->subject   = $res->getStringByName("subject");
		$m->body      = $res->getStringByName("body");
		return $m;
	}
	
	/**
	 * @param int $pk
	 * @return self
	 * @throws SQLException
	 */
	static function fromPk($pk)
	{
		$ps = SiteSpecific::getDB()->prepareStatement("select * from comments where pk=?");
		$ps->setInt(0, $pk);
		$res = $ps->query();
		if( $res->getRowCount() != 1 )
			throw new SQLException("no this comment: pk=$pk");
		$res->moveToRow(0);
		return self::fromResultSet($res);
	}
	
	
	/**
	 * 
	 * @throws SQLException
	 */
	function insert()
	{
		$ps = SiteSpecific::getDB()->prepareStatement("insert into comments (reference, path, time, name, current_name, subject, body) values (?, ?, ?, ?, ?, ?, ?)");
		$ps->setInt   (0, $this->reference);
		$ps->setString(1, $this->path);
		$ps->setInt   (2, $this->time);
		$ps->setString(3, $this->name);
		$ps->setString(4, $this->current_name);
		$ps->setString(5, $this->subject);
		$ps->setString(6, $this->body);
		$ps->update();
		$res = SiteSpecific::getDB()->query("select last_insert_id()");
		$res->moveToRow(0);
		$this->pk = $res->getIntByIndex(0);
	}
	
	
	/**
	 * 
	 * @throws SQLException
	 */
	function update()
	{
		$ps = SiteSpecific::getDB()->prepareStatement("update comments set"
			. " reference=?,"
			. " path=?,"
			. " time=?,"
			. " name=?,"
			. " current_name=?,"
			. " subject=?,"
			. " body=?"
			. " where pk=?"
		);
		$ps->setInt   (0, $this->reference);
		$ps->setString(1, $this->path);
		$ps->setInt   (2, $this->time);
		$ps->setString(3, $this->name);
		$ps->setString(4, $this->current_name);
		$ps->setString(5, $this->subject);
		$ps->setString(6, $this->body);
		$ps->setInt   (7, $this->pk);
		$ps->update();
	}
	
	
	/**
	 * 
	 * @throws SQLException
	 */
	function save()
	{
		if( $this->pk == 0 )
			$this->insert();
		else
			$this->update();
	}
	
	/**
	 * Returns a summary of this message.
	 * @return string Summary of the message as plain HTML.
	 */
	function getSummary()
	{
		$s = "<p>"
			.'<code><b>'. date("Y-m-d", $this->time)
			.'</b></code>'
			.' by '. Html::text($this->current_name) . '<br>'
			.'<b>'. Html::text($this->subject). '</b><br>';
		$b = $this->body;
		# Remove quoted part:
		$b = preg_replace("/(^|\n)(>[^\n]*\n)+/", " [...] ", $b);
		$b = preg_replace("/[ \n\t]+/", " ", $b);
		$b = Common::short($b, 200);
		$s .= Html::text($b) . '<p>';
		return $s;
	}
	
}
