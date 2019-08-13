<?php
namespace it\icosaedro\www;
require_once __DIR__ . "/../../../all.php";
use ErrorException;
use it\icosaedro\sql\SQLException;
use it\icosaedro\web\Log;
use it\icosaedro\web\Html;
use it\icosaedro\web\bt_\Form;
use it\icosaedro\web\bt_\UserSession;
use it\icosaedro\web\controls\Line;
use it\icosaedro\web\controls\Text;
use it\icosaedro\web\controls\TuringTest;
use it\icosaedro\web\controls\ParseException;

/**
 * Message mask to enter a new message or a reply.
 * @author Umberto Salsi <salsi@icosaedro.it>
 * @version $Date: 2018/05/15 04:15:00 $
 */
class MessageMask extends Form {
	
	/**
	 * @var int
	 */
	private $reference = 0;
	
	/**
	 * @var string
	 */
	private $path;
	
	/**
	 * @var Line
	 */
	private $current_name;
	
	/**
	 * @var Line
	 */
	private $subject;
	
	/**
	 * @var TuringTest
	 */
	private $human;
	
	/**
	 * @var Text
	 */
	private $body;
	
	function __construct() {
		parent::__construct();
		if( Common::isGuest() )
			$this->human = new TuringTest($this, "human");
		$this->current_name = new Line($this, "current_name");
		$this->subject = new Line($this, "subject");
		$this->body = new Text($this, "body");
	}
	
	/**
	 * 
	 * @param string $err
	 */
	function render($err = NULL)
	{
		Common::echoPageHeader();
		$this->open();
		
		echo "<h1>New comment</h1>";
		
		if( strlen($err) > 0 )
			Html::errorBox("<ul>$err</ul>");
		

		echo "<table cellspacing=3 cellpadding=0 border=0>\n";
		echo '<tr><td>From:</td><td>', Html::text( UserSession::getSessionParameter('current_name') ), '</td></tr>';
		echo '<tr><td>Date:</td><td>', gmdate("Y-m-d, H:i", time()), ' UTC</td></tr>';
		echo '<tr><td>Page:</td><td><code><a target=blank href="', $this->path, '">',
			SiteSpecific::WEB_BASE, $this->path, '</a></code></td></tr>';
		echo '<tr><td>Message-ID:</td><td><i>still not assigned</i></td></tr>';

		echo '<tr><td>Reference:</td><td>';
		if( $this->reference == 0 ){
			echo '<i>no reference - that\'s a new thread</i>';
		} else {
			echo $this->reference;
		}
		echo "</td></tr>";
		
		if( $this->human !== NULL ){
			echo "<tr><td>Turing test:</td><td>";
			$this->human->render();
			echo '</td></tr>';
		}

		echo "<tr><td>From:</td><td>";
		$this->current_name->addAttributes("size=80 maxlength=100");
		$this->current_name->render();
		echo "</td></tr>";

		echo "<tr><td>Subject:</td><td>";
		$this->subject->addAttributes("size=80 maxlength=100");
		$this->subject->render();
		echo "</td></tr>";

		echo "</table></div>\n";

		echo "<p>";
		$this->body->addAttributes("cols=80 rows=15");
		$this->body->render();
		
		echo "<hr>";
		$this->button("Cancel", "cancelButton");
		Html::echoSpan(5);
		$this->button("Send", "sendButton");

		echo "<p><small><b>NOTE.</b> Once sent, your message will be appended to the page <code>", $this->path, "</code> and will be visible to all the visitors of the WEB site. For personal messages intended to be sent only to the owner of this WEB site, please use the link <u>Contact</u> available on any WEB page, or send an email to Umberto Salsi (see the link below).</small></p>";
		if( Common::isGuest() ){
			echo "<p><small><b>NOTE.</b> Please note that since you aren't a registered user, once the message has been sent you will not be able to delete it. Only registered users may delete their own messages.</small></p>";
		}
		$this->close();
		Common::echoPageFooter();
	}
	
	
	function cancelButton()
	{
		UserSession::invokeCallBackward();
	}
	
	
	function save() {
		parent::save();
		$this->setData("resource", $this->path);
		$this->setData("reference", $this->reference);
	}
	
	
	function resume() {
		parent::resume();
		$this->path = (string) $this->getData("resource");
		$this->reference = (int) $this->getData("reference");
	}
	
	/**
	 * 
	 * @throws SQLException
	 * @throws ErrorException
	 */
	function sendButton()
	{
		$err = "";
		
		// Validate Turing test reply:
		if( $this->human !== NULL ){
			try {
				$this->human->parse();
			} catch (ParseException $e) {
				$err .= "<li>Missing or invalid reply to the Turing test.</li>";
			}
		}
		
		// Validate current name:
		$current_name = $this->current_name->getValue();
		$current_name = Common::short($current_name, 100);
		$this->current_name->setValue($current_name);
		
		// Validate subject:
		$subject = $this->subject->getValue();
		$subject = Common::short($subject, 100);
		if( strlen($subject) == 0 )
			$err .= "<li>Missing subject. Please, provide a meaningful description of the contents of your message.</li>";
		
		// Validate body:
		$body = trim( $this->body->getValue() );
		if( strlen($body) == 0 )
			$err .= "<li>The message is empty.</li>";
		else if( strlen($body) > 50000 )
			$err .= "<li>The message is too long, max 50000 bytes allowed.</li>";

		if( strlen($err) > 0 ){
			$this->render($err);
			return;
		}

		if( strlen(SiteSpecific::ADMIN_EMAIL) > 0 )
			mail(SiteSpecific::ADMIN_EMAIL,
				"Comment on " . $this->path . ": $subject",
				wordwrap(UserSession::getSessionParameter('name') . " wrote:\n\n"
				. $this->body->getValue(), 75),
				"From: " . SiteSpecific::ADMIN_EMAIL
				. "\r\nMIME-Version: 1.0"
				. "\r\nContent-Type: text/plain; charset=\"UTF-8\""
			);
		
		$m = new Message();
		$m->reference = $this->reference;
		$m->body = $this->body->getValue();
		$m->subject = $this->subject->getValue();
		$m->current_name = $this->current_name->getValue();
		$m->name = UserSession::getSessionParameter("name");
		$m->path = $this->path;
		$m->time = time();
		$m->save();

		// "touch" the page:
		$path = $this->path;
		$full_path = SiteSpecific::PATH_BASE . $path;
		if(file_exists($full_path) )
			touch($full_path);
		else
			Log::warning("[WCS] file $full_path does not exits (anymore?)");

		Common::notice("Your comment has been successfully added to the page"
			." <code>$path</code> and the page has been updated accordingly."
			."<p>You may need to reload that page"
			." in order to see the comment you just added.");
	}
	
	
	/**
	 * Entry point.
	 * @param string $path Path or the resource of the page to comment.
	 * @param int $reference PK of the msg we are replying to, or zero if this is
	 * a new thread.
	 * @throws SQLException
	 */
	static function enter($path, $reference)
	{
		$f = new self();
		$f->path = $path;
		$f->reference = $reference;
		$f->current_name->setValue( UserSession::getSessionParameter("current_name") );
		
		if( $reference != 0 ){
			$r = Message::fromPk($reference);
			$f->reference = $reference;
			$f->subject->setValue("Re: ". preg_replace("@^Re: ?@", "", $r->subject));
			$f->body->setValue($r->current_name . " wrote:\n\n> "
				. preg_replace("@\n@", "\n> ", wordwrap($r->body, 75) ) );
		}
		
		$f->body->setValue( $f->body->getValue()
			. UserSession::getSessionParameter("signature", "") );
		
		$f->render();
	}
	
}
