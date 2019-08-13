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

use it\icosaedro\utils\DateTimeTZ;
use it\icosaedro\web\Http;
use it\icosaedro\web\Html;
use it\icosaedro\web\FileDownload;
use it\icosaedro\web\bt_\Form;
use it\icosaedro\web\bt_\UserSession;
use it\icosaedro\web\controls\Control;
use it\icosaedro\web\controls\CheckBox;
use it\icosaedro\web\controls\ContainerInterface;
use it\icosaedro\web\controls\Currency;
use it\icosaedro\web\controls\Date as DateControl;
use it\icosaedro\web\controls\DateTime as DateTimeControl;
use it\icosaedro\web\controls\Hidden;
use it\icosaedro\web\controls\Line;
use it\icosaedro\web\controls\Spinner;
use it\icosaedro\web\controls\Panel;
use it\icosaedro\web\controls\RadioButton;
use it\icosaedro\web\controls\Scientific;
use it\icosaedro\web\controls\Select;
use it\icosaedro\web\controls\SelectMultiple;
use it\icosaedro\web\controls\Slider;
use it\icosaedro\web\controls\Text;
use it\icosaedro\web\controls\Time;
use it\icosaedro\web\controls\FileUpload;
use it\icosaedro\web\controls\ParseException;
use it\icosaedro\containers\IntClass;
use it\icosaedro\containers\StringClass;
use it\icosaedro\bignumbers\BigFloat;
use ErrorException;

/**
 * Sample of an articulated custom control built using the Panel class. A Panel
 * if basically like a Form, but it allow to build custom controls that can be
 * used inside forms just like any other control. Events on sub-panels are
 * reported to the parent form invoking its "eventOnControl()" method.
 */
class PanelSample extends Panel {
	
	/** @var Line */
	private $something;
	
	/**
	 * @param ContainerInterface $form
	 * @param string $name
	 */
	function __construct($form, $name) {
		parent::__construct($form, $name);
		$this->something = new Line($this, "something");
	}
	
	function render()
	{
		echo "<fieldset><legend>Sample ", Panel::class, " contents</legend>";
		echo "Anything you enter in the entry box here below is copied into the entry box of the parent form:<p>";
		$this->something->render();
		$this->button("OK", "okButton");
		echo "</fieldset>";
	}
	
	function okButton()
	{
		// No need to do anything; this event already triggers eventOnControl().
	}
	
	function getSomething()
	{
		return $this->something->getValue();
	}
	
}


/**
 * Sample bt form. It contains a sample of each available web control.
 */
class BtFormSample extends Form {
	
	/*
	 * The form stores its controls as private properties.
	 */
	
	/** @var CheckBox */
	private $checkbox1;
	
	/** @var Hidden */
	private $hdn1;
	
	/** @var Line */
	private $line1;
	
	/** @var RadioButton */
	private $radio0, $radio1, $radio2, $radio3;
	
	/** @var Select */
	private $menu1;
	
	/** @var SelectMultiple */
	private $menu_multiple1;
	
	/** @var DateControl */
	private $date1;
	
	/** @var DateTimeControl */
	private $datetime1;
	
	/** @var Spinner */
	private $number1;
	
	/** @var Slider */
	private $slider1;
	
	/** @var Time */
	private $time1;
	
	/** @var Text */
	private $text1;
	
	/** @var Currency */
	private $currency1;
	
	/** @var Scientific */
	private $scientific1;
	
	/** @var FileUpload */
	private $file1;
	
	/** @var PanelSample */
	private $panel1;
	
	
	/**
	 * The constructor of the form is responsible for the creation of all the
	 * controls it contains.
	 */
	function __construct()
	{
		parent::__construct();
		
		$this->checkbox1 = new CheckBox($this, "cb1", "Caption of the checkbox here");
		$this->hdn1 = new Hidden($this, "hdn1");
		
		$this->line1 = new Line($this, "line1");
		
		$this->radio0 = new RadioButton($this, "radio", 0, "<i>none</i>"); 
		$this->radio1 = new RadioButton($this, "radio", 1, "Red"); 
		$this->radio2 = new RadioButton($this, "radio", 2, "Green");
		$this->radio3 = new RadioButton($this, "radio", 3, "Blue");
		
		$this->menu1 = new Select($this, "menu1");
		$this->menu1->addValue("--", new StringClass(NULL));
		$this->menu1->addValue("Red", new StringClass("#f00"));
		$this->menu1->addValue("Green", new StringClass("#0f0"));
		$this->menu1->addValue("Blue", new StringClass("#00f"));
		
		$this->menu_multiple1 = new SelectMultiple($this, "menu_multiple1", 3);
		$this->menu_multiple1->listSelectedFirst(TRUE);
		$week = array("Sunday", "Monday", "Tuesday", "Wednesday", "Thursday",
				"Friday", "Saturday");
		foreach($week as $day_number => $day_display)
			$this->menu_multiple1->addEntry($day_display, new IntClass($day_number));
		
		$this->date1 = new DateControl($this, "date1");
		
		$this->datetime1 = new DateTimeControl($this, "datetime1");
		
		$this->number1 = new Spinner($this, "number1");
		
		$this->slider1 = new Slider($this, "range1");
		$this->slider1->setMinMaxStep(0, 100, 10);
		
		$this->time1 = new Time($this, "time1");
		
		$this->text1 = new Text($this, "text1");
		
		$this->currency1 = new Currency($this, "currency1");
		
		$this->scientific1 = new Scientific($this, "scientific1");
		
		$this->file1 = new FileUpload($this, "file1", TRUE);
		
		$this->panel1 = new PanelSample($this, "panel1");
	}
	
	
	/**
	 * Displays this form. This method is invoked any time the page on the remote
	 * client needs to be refreshed.
	 * @param string $err_html Error message to display.
	 */
	function render($err_html = NULL)
	{
		Http::headerContentTypeHtmlUTF8();
		echo "<html>\n",
			"<body>\n",
			"<h1>Form demo page</h1>\n",
			"This page shows a sample of all the available controls.";
		
		if( strlen($err_html) > 0 ){
			echo "<div style='margin: 1em; border: 0.1em solid black; padding: 1em; background-color: #ff6666;'>Some quite random validation messages:",
				$err_html, "</div>";
		}
		
		$this->addAttributes("accept='image/png'");
		$this->open();
		
		// First column.
		echo "<table cellpadding=10><tr><td valign=top>";
		
		echo "\n<p>", get_class($this->checkbox1), ":<br>\n";
		$this->checkbox1->render(); echo "<p>";
		
		$this->hdn1->render();
		
		echo "\n<p>", get_class($this->line1), ":<br>\n";
		$this->line1->render();
		
		echo "\n<p>", get_class($this->currency1), ":<br>\n";
		$this->currency1->render();
		
		echo "\n<p>", get_class($this->scientific1), ":<br>\n";
		$this->scientific1->render();
		
		echo "\n<p>", get_class($this->radio1), ":<br>\n";
		$this->radio0->render();
		$this->radio1->render();
		$this->radio2->render();
		$this->radio3->render();
		
		echo "\n<p>", get_class($this->menu1), ":<br>\n";
		$this->menu1->render();
		
		echo "\n<p>", get_class($this->menu_multiple1), ":<br>\n";;
		$this->menu_multiple1->render();
		
		// Seconds column.
		echo "</td><td valign=top>";
		
		echo "\n<p>", get_class($this->text1), ":<br>\n";
		$this->text1->addAttributes("cols=40 rows=3");
		$this->text1->render();
		
		echo "<fieldset><legend>", get_class($this->file1), "</legend>";
		if( $this->file1->isAvailable() ){
			// Displays current file, allows download and delete actions:
			$path = $this->file1->getTemporaryFilename();
			$name = $this->file1->getFilename();
			$type = $this->file1->getType();
			$size = $this->file1->getSize();
			echo "Current: ";
			UserSession::anchor(htmlentities($name), self::class . "::downloadFileButton", $path, $name, $type);
			echo ", $size B, $type &larr; ";
			$this->anchor("Delete", "deleteCurrentFileButton");
			echo "<br>";
		}
		// File upload (new replaces current):
		$max_size = FileUpload::maxUploadFileSize();
		$max_upload_time = FileUpload::maxUploadTime();
		$bandwidth = (int) ($max_size / $max_upload_time);
		echo "New file (max size $max_size B, estimated min bandwidth required $bandwidth B/s):<br>";
		$this->file1->render();
		echo " (", htmlentities($this->file1->status_description), ")";
		echo "</fieldset>";
		
		$this->panel1->render();
		
		// Third column: HTML5 specific controls.
		echo "</td><td valign=top>";
		
		echo "\n<p>", get_class($this->time1), ":<br>\n";
		$this->time1->render();
		echo "<br>(restricted range to: [09:00,17:00[)";
		
		echo "\n<p>", get_class($this->date1), ":<br>\n";
		$this->date1->render();
		
		echo "\n<p>", get_class($this->datetime1), ":<br>\n";
		$this->datetime1->render();
		
		echo "\n<p>", get_class($this->number1), ":<br>\n";
		$this->number1->render();
		
		echo "\n<p>", get_class($this->slider1), ":<br>\n";
		$this->slider1->render();
		
		echo "</td></tr></table>";
		
		echo "<hr>";
		$this->button("Cancel", "cancelButton", $this);
		Html::echoSpan(5);
		$this->button("Save", "saveButton");
		Html::echoSpan(5);
		$this->anchor("Save", "saveButton");
		
		echo "<p alig=justify><small>";
		echo "<b>Cancel</b> button sends to the sticky form tutorial.";
		echo " <b>Save</b> button does not really save anything, it's only a demo!";
		echo " A Save button rendered as an anchor is also displayed.";
		echo " The source code of this sticky form mask is available for download ";
		$this->anchor("HERE", "downloadFileButton", __FILE__, "StikyFormSample.php", "application/x-php");
		echo "</small></p>";
		
		$this->close();
		
		echo Common::DISCLAIMER;
		echo "</body></html>";
	}
	
	
	/**
	 * Sub-panel triggered an event. Each sub-panel (just one, in this sample)
	 * invoke this method to signalo their state is changed and needs attention
	 * from the main logic of the form.
	 * @param Control $control Panel that triggered the event.
	 */
	function eventOnControl($control) {
		if( $control === $this->panel1 );
			$this->line1->setValue($this->panel1->getSomething());
		$this->render();
	}
	
	
	/**
	 * Delete currently uploaded file button handler. The user asked us to delete
	 * the file he just uploaded.
	 */
	function deleteCurrentFileButton()
	{
		$this->file1->delete();
		$this->render();
	}
	
	
	/**
	 * Save button handler. Performs fields validation relying on the automated
	 * parse methods. where available; more specific validation should be made
	 * in real applications. If validation succeeds, real applications should
	 * either return to the caller on the bt stack, or display another empty
	 * form for more data entry, or send the user to the some next page.
	 * Since here we do not really save anything, we simply display again the
	 * same form with the data just entered, which is handy for testing and
	 * debugging.
	 */
	function saveButton()
	{
		// Validation:
		$err = "";  // collect here list of errors
		
		// Getting multiple selection:
		if( count($this->menu_multiple1->getSelectedValues()) == 0 )
			$err .= "<li>At least one preferred day MUST be selected.</li>";
		
		try { /* ignore = */ $this->date1->parse(); }
		catch(ParseException $e){ $err .= "<li>" . Html::text($e->getMessage()); }
		
		try { /* ignore = */ $this->datetime1->parse(); }
		catch(ParseException $e){ $err .= "<li>" . Html::text($e->getMessage()); }
		
		try { /* ignore = */ $this->number1->parse(); }
		catch(ParseException $e){ $err .= "<li>" . Html::text($e->getMessage()); }
		
		try { /* ignore = */ $this->slider1->parse(); }
		catch(ParseException $e){ $err .= "<li>" . Html::text($e->getMessage()); }
		
		try { /* ignore = */ $this->time1->parse(); }
		catch(ParseException $e){ $err .= "<li>" . Html::text($e->getMessage()); }
		
		try { /* ignore = */ $this->currency1->parse(); }
		catch(ParseException $e){ $err .= "<li>" . Html::text($e->getMessage()); }
		
		try { /* ignore = */ $this->scientific1->parse(); }
		catch(ParseException $e){ $err .= "<li>" . Html::text($e->getMessage()); }
		
		// ...and so on checking mandatory fields and their syntax.
		
		if( strlen($err) > 0 ){
			// Validation failed.
			$this->render("<ul>$err</ul>");
			return;
		}
		
		// Validation succeeded. Here we should save the form; and remember
		// there might be also an uploaded file $this->file1 to save somewhere:
		//
		//     ...scribble...
		//     ...scribble...
		// 
		// and finally either go back to the caller function:
		// 
		//     UserSession::invokeCallBackward();
		//
		// or create a new empty form:
		//
		//     self::enter();
		//
		// or send the user to some other page:
		//
		//     AnotherClass::enterMethodOfThatClass();
		//
		// We do not really save anything here, and we simply display the form
		// again:
		
		$this->render();
	}
	
	
	/**
	 * Cancel button handler. The user asked us to leave this boring form and to
	 * return to the previous page. Here we expect to retrieve the previous page
	 * from hte bt stack.
	 */
	function cancelButton()
	{
		$this->file1->delete();
		UserSession::invokeCallBackward();
	}
	
	
	/**
	 * Generic file download handler. Allows the user to get back the file he
	 * just uploaded.
	 * @param string $path
	 * @param string $name
	 * @param string $type
	 * @throws ErrorException
	 */
	static function downloadFileButton($path, $name, $type)
	{
		FileDownload::sendHeaders($name, $type, TRUE);
		FileDownload::sendFile($path);
	}
	
	
	/**
	 * Entry point of this bt form that initializes and displays the form.
	 * External code may invoke this static method through bt_ to display and
	 * start the interaction with this form. A return point is assumed to be
	 * already prepared on the bt stack; this form does not return values on
	 * the bt stack.
	 */
	static function enter()
	{
		$f = new self();
		$f->checkbox1->setChecked(FALSE);
		$f->hdn1->setValue("");
		$f->line1->setValue("Hello!");
		$f->radio1->setSelected(TRUE);
		$f->radio2->setSelected(FALSE);
		$f->radio3->setSelected(FALSE);
		$f->text1->setValue("Multi-line entry box:\n\t- tabulator allowed;\n\t- new-line allowed.");
		
		// Initialize the time field with the number of ms after midnight.
		// Here we assume the TZ of the user be +0200.
		// The value will be adjusted to the nearest time step (15 min, in our
		// example).
		$now = DateTimeTZ::now()->toTZ(120);
		$secondsAfterMidnight = 3600 * $now->getDateTime()->getHour()
				+ 60 * $now->getDateTime()->getMinutes();
		// Time range from 09:00 (included) up to 17:00 (excluded), step 15 min:
		$f->time1->setMinMaxStep(9*60*60*1000, 17*60*60*1000-1, 15*60*1000);
		// Set the time closer to current user time:
		$f->time1->setInt(1000 * $secondsAfterMidnight);
		
		$f->date1->setMinMax($now->getDateTime()->getDate(), $now->getDateTime()->getDate()->addDays(2));
		$f->date1->setDate($now->getDateTime()->getDate());
		
		$f->datetime1->setDateTimeTZ($now);
		
		$f->number1->setMinMaxStep(0, 100, 10);
		
		//$f->currency1->setFormat(2, ",", "'");
		$f->currency1->setBigFloat(new BigFloat("123456789.00"));
		
		$f->scientific1->setFloat(0.1);
		
		$f->render();
	}
	
}
