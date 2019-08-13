<?php
namespace it\icosaedro\www;
require_once __DIR__ . "/../../../all.php";
use RuntimeException;
use ErrorException;
use it\icosaedro\sql\SQLException;
use it\icosaedro\web\Html;
use it\icosaedro\web\bt_\Form;
use it\icosaedro\web\bt_\UserSession;
use it\icosaedro\web\Log;

/*. require_module 'hash'; .*/

/**
 * Displays all the comments about a given page. Also allows to:
 * add new messages; reply to existing messages; delete user's message.
 * @author Umberto Salsi <salsi@icosaedro.it>
 * @version $Date: 2018/05/15 04:15:20 $
 */
class PageComments extends Form {
	
	/**
	 * Path of the referred resource, for example "/my/page.php".
	 * @var string
	 */
	private $path;
	
	/**
	 * 
	 * @param Message $m
	 */
	private function showMessage($m)
	{
		echo "<pre>\n</pre>";
		echo "<a name=", $m->pk, "></a>\n",
			"<div style='background-color: #ee5;'>",
			"<table cellspacing=0 cellpadding=2 border=0>\n",
			"<tr><td>From:</td><td>", Html::text($m->current_name), "</td></tr>\n",
			"<tr><td>Date:</td><td>", gmdate("Y-m-d, H:i", $m->time), " UTC</td></tr>\n",
			"<tr><td>Message-ID:</td><td>", $m->pk, "</td></tr>\n",
			"</div>";

		echo "<tr><td>Reference:</td><td>";
		if( $m->reference == 0 ){
			echo '<i>no reference - that\'s a new thread</i>';
		} else {
			echo "<a href='#", $m->reference, "'>", $m->reference, '</a>';
		}
		echo "</td></tr>\n";

		echo '<tr><td>Subject:</td><td><b>', Html::text($m->subject), "</b></td></tr>\n",
			"</table>\n",
			"</div>\n",
			'<blockquote><pre>', Html::text(wordwrap($m->body, 85)),
			'</pre></blockquote>';
		echo "<p>";
		$this->button("Reply...", "addMessageButton", $m->pk);

		if( Common::checkPermission(Common::PERMISSION_IS_ADMIN)
		|| (Common::checkPermission(Common::PERMISSION_MAY_DELETE)
			&& strcmp($m->name, UserSession::getSessionParameter('name')) == 0)
		){
			Html::echoSpan(5);
			$this->button("Delete...", "deleteButton", $m->pk);
		}
		echo "<p>";
	}
	
	/**
	 * Form page renderer that may throw exception.
	 * @throws SQLException
	 */
	private function render_ll()
	{
		$ps = SiteSpecific::getDB()
		->prepareStatement("SELECT * FROM comments WHERE path=? ORDER BY time DESC");
		$ps->setString(0, $this->path);
		$res = $ps->query();
		
		
		Common::echoPageHeader();
		$this->open();
		echo '<h1>Page: <a href="',
			SiteSpecific::WEB_BASE, $this->path , '#comments">',
			$this->path, '</a></h1>';
		
		
		$this->button("Add comment...", "addMessageButton", 0);

		$n = $res->getRowCount();
		if( $n == 0 ){
			echo '<p align=center><i>Still no comments available for this page.',
				'</i></p>';
			$this->returnTo("render");
			$this->button("New...", "addMessageButton", 0);

		} else {

			for( $i = 0; $i < $n; $i++ ){
				$res->moveToRow($i);
				$m = Message::fromResultSet($res);
				$this->showMessage($m);
			}
		}
		
		$this->close();

		Common::echoPageFooter();
	}
	
	/**
	 * Actual form page renderer method, which cannot throw exception by contract.
	 */
	function render()
	{
		UserSession::stackReset();
		try {
			$this->render_ll();
		} catch (SQLException $e) {
			throw new RuntimeException($e->getMessage(), $e->getCode(), $e);
		}
	}
	
	function save()
	{
		parent::save();
		$this->setData("path", $this->path);
	}
	
	function resume() {
		parent::resume();
		$this->path = (string) $this->getData("path");
	}
	
	/**
	 * 
	 * @param int $reference
	 * @throws SQLException
	 */
	function addMessageButton($reference)
	{
		$this->returnTo("render");
		MessageMask::enter($this->path, $reference);
	}
	
	/**
	 * @param int $pk
	 * @throws SQLException
	 */
	function deleteButton($pk)
	{
		$summary = Message::fromPk($pk)->getSummary();
		Common::echoPageHeader();
		$this->open();
		Common::info_box("/img/warning.png", "Delete message",
			"Are you sure to delete this message?<blockquote>$summary</blockquote>");
		echo VSPACE;
		$this->button("Cancel", "render");
		echo HSPACE;
		$this->button("Delete", "confirmDeleteButton", $pk);
		$this->close();
		Common::echoPageFooter();
	}
	
	/**
	 * @param int $pk
	 * @throws SQLException
	 */
	function confirmDeleteButton($pk)
	{
		// Mark page as changed:
		$m = Message::fromPk($pk);
		$full_path = SiteSpecific::PATH_BASE . $m->path;
		if(file_exists($full_path) ){
			try {
				touch($full_path);
			} catch (ErrorException $e) {
				Log::error("[WCS] touch($full_path) failed: " . $e->getMessage());
			}
		}
		
		// Delete the message:
		$ps = SiteSpecific::getDB()->prepareStatement("delete from comments where pk=?");
		$ps->setInt(0, $pk);
		$ps->update();
		$this->render();
	}

	/**
	 * Retrieves the referred resource from the query parameters.
	 * @return string
	 */
	private static function getResource()
	{
		if( isset($_SERVER["HTTP_REFERER"]) )
			$page = $_SERVER["HTTP_REFERER"];
		else
			$page = "[missing Referer header]";

		if( ! isset( $_GET["resource"] ) or ! isset( $_GET["cod"] ) ){
			Log::error("missing parameter resource or cod from page $page");
			return NULL;
		}

		$path = (string) $_GET["resource"];
		$cod  = (string) $_GET["cod"];
		if( $cod !== substr( md5($path . SiteSpecific::HMAC_KEY), 0, 8 ) ){
			Log::error("path=$path, cod=$cod: invalid HMAC from page $page");
			return NULL;
		}

		return $path;
	}
	
	/**
	 * @param string $path
	 */
	static function enterOnPage($path)
	{
		$f = new self();
		$f->path = $path;
		$f->render();
	}
	
	/**
	 * Entry point for registered users or already existing sessions.
	 * @throws SQLException
	 */
	static function enter()
	{
		$path = self::getResource();
		if( $path === NULL ){
			Pages::enter();
			return;
		}
		self::enterOnPage($path);
	}
	
}
