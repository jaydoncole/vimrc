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

use ErrorException;
use it\icosaedro\web\bt_\Form;
use it\icosaedro\web\bt_\UserSession;
use it\icosaedro\web\Http;
use it\icosaedro\web\Html;
use com\acme\www\Common;

/**
 * Bt form page that shows the usual phpinfo(), one section at a time.
 */
class ServerState extends Form {
	
	/**
	 * Section of phpinfo() currently shown. One of the INFO_* constants.
	 * Zero for nothing. Illustrates how form parameters can be saved and
	 * resumed from the user session between HTTP requests; see also the
	 * save() and resume() methods below.
	 * @var int
	 */
	private $phpinfo_what = 0;
	
	/**
	 * Displays phpinfo() output by stripping unneeded HTML elements.
	 * @param int $what One of the INFO_* constants.
	 */
	private function echoPhpInfo($what)
	{
		ob_start();
		phpinfo($what);
		$s = ob_get_clean();
		$s = preg_replace("/^.*?<body>/s", "", $s);
		$s = preg_replace("/<\\/body>.*/s", "", $s);
		$s = preg_replace("/ class=\".\"/", "", $s);
		$s = preg_replace("/<table>/s", "<table border=1 cellspacing=0 cellpadding=3>", $s);
		echo $s;
	}
	
	/**
	 * Displays the phpinfo() section currently selected.
	 */
	function render()
	{
		Http::headerContentTypeHtmlUTF8();
		echo "<html><head><title>Server State</title></head><body><h1>Server State</h1>";
		$this->open();
		
		echo "<h2>PHP Info</h2>";
		$details = [
			0 => "Nothing",
			INFO_GENERAL => "INFO_GENERAL",
			INFO_CREDITS => "INFO_CREDITS",
			INFO_CONFIGURATION => "INFO_CONFIGURATION",
			INFO_MODULES => "INFO_MODULES",
			INFO_ENVIRONMENT => "INFO_ENVIRONMENT",
			INFO_VARIABLES => "INFO_VARIABLES",
			INFO_LICENSE => "INFO_LICENSE"
			// , INFO_ALL => "INFO_ALL"
		];
		foreach($details as $what_code => $what_descr){
			if( $what_code == 0 )
				continue;
			if( $what_code == $this->phpinfo_what )
				echo "▼ ";
			else
				echo "► ";
			$this->anchor($what_descr, "phpInfoToggleButton", $what_code);
			if( $what_code == $this->phpinfo_what )
				$this->echoPhpInfo($what_code);
			echo "<br>";
		}
		
		echo "<h2>\$_ENV</h2><pre>";
		try {
			echo htmlspecialchars( var_export($_ENV, TRUE) );
		}
		catch(ErrorException $e){
			echo htmlspecialchars("$e");
		}
		echo "</pre>";
			
		echo "<hr>";
		$this->button("Dismiss", "dismissButton");
		Html::echoSpan(5);
		$this->button("Update", "render");
		$this->close();
		echo Common::DISCLAIMER;
		echo "</body></html>";
	}
	
	/**
	 * Method invoked by bt form to save the state of the form in the user
	 * session. Applications may want to <u>extend</u> this method to save their
	 * specific internal state. Note we used the word "extend", not "override":
	 * remember to invoke the parent save() method of the form, as the Form
	 * itself needs to save its state!
	 * Here we save the currently opened section of phpinfo().
	 */
	function save()
	{
		parent::save(); // <-- DO NOT FORGET THIS!
		$this->setData("phpinfo_what", $this->phpinfo_what);
	}
	
	/**
	 * Method invoked by bt form to resume the state of the form the user
	 * session. Here again, we <u>extend</u> (not override) the parent method.
	 * Here we recover the currenlty openend section of phpinfo().
	 */
	function resume() {
		parent::resume(); // <-- DO NOT FORGET THIS!
		$this->phpinfo_what = (int) $this->getData("phpinfo_what");
	}
	
	
	/**
	 * Section selection handler.
	 * @param int $what
	 */
	function phpInfoToggleButton($what)
	{
		$this->phpinfo_what = ($what == $this->phpinfo_what)? 0 : $what;
		$this->render();
	}
	
	/**
	 * Dismiss button handler. Invokes the backward call on the top of the
	 * bt stack, which in our specific case should return to the dashboard
	 * page. Since we do not have specific values to return to the caller,
	 * the list of arguments of invokeCallBackward() is left empty.
	 */
	function dismissButton()
	{
		UserSession::invokeCallBackward();
	}
	
	/**
	 * Entry point of this form. External code (the dashboard page, in our case)
	 * may invoke this static function though bt_ to display this form. All this
	 * fuction has to do is to create and instance of our specific bt form and
	 * invoke its render method. It is assumed the invoker had first saved its
	 * own return point in the bt stack, as the "Dismiss" button of this form will
	 * return to that backward call (the dashboard page, again). See the
	 * Dashboard.php file for an example of how this form can be invoked and how
	 * a return point on the bt stack can be set.
	 */
	static function enter()
	{
		$f = new self();
		$f->render();
	}
	
}
