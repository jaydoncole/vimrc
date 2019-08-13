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
use RuntimeException;
use InvalidArgumentException;
use it\icosaedro\web\bt_\Form;
use it\icosaedro\web\bt_\UserSession;
use it\icosaedro\web\controls\Text;
use it\icosaedro\web\Http;
use it\icosaedro\web\Html;
use it\icosaedro\web\Input;
use it\icosaedro\web\OfflineJob;
use it\icosaedro\containers\Arrays;
use it\icosaedro\containers\Sorter;
use com\acme\www\Common;

/**
 * A single background job. Also provides a method to retrieve all the jobs.
 * @access private
 */
class Job {
	/** @var string */
	public $ticket;
	/** @var int */
	public $status = -1;
	/** @var string */
	public $status_display;
	/** @var boolean */
	public $is_stale = FALSE;
	/** @var string */
	public $command;
	/** @var string */
	public $stdin_txt;
	/** @var int */
	public $started = 0;
	/** @var int */
	public $finished = 0;
	/** @var string */
	public $notes;
	
	/**
	 * Returns an abstract from the file.
	 * @param string $path File to read.
	 * @param int $max_len Max no. of bytes to read.
	 * @return string Abstract of the file, UTF8 encoded.
	 * @throws ErrorException
	 */
	function shorten($path, $max_len)
	{
		$s = file_get_contents($path, FALSE, NULL, 0, $max_len);
		if( strlen($s) == $max_len )
			$s .= "...";
		$s = (string) str_replace("\n", " ", $s);
		return Input::sanitizeLine($s);
	}
	
	/**
	 * @param string $ticket
	 * @throws RuntimeException
	 */
	private function __construct($ticket)
	{
		$this->ticket = $ticket;
		$this->status = -1;
		$this->command = "";
		$this->started = 0;
		$this->finished = 0;
		$this->notes = "";

		try {
			$job = new OfflineJob($ticket);
			$this->status = $job->getStatus();
			if( $this->status >= OfflineJob::STATUS_STARTING){
				$this->command = $job->propertyRead(OfflineJob::PROPERTY_COMMAND);
				$this->started = filectime($job->propertyPath(OfflineJob::PROPERTY_COMMAND));
			}
			if( $this->status == OfflineJob::STATUS_FINISHED )
				$this->finished = filectime($job->propertyPath(OfflineJob::PROPERTY_EXIT_STATUS));
			$this->status_display = "$job";
			switch($this->status){
				case OfflineJob::STATUS_STARTING:
					/*. missing_break ; .*/
				case OfflineJob::STATUS_RUNNING:
					/*. missing_break ; .*/
				case OfflineJob::STATUS_FINISHED:
					$this->command = $job->propertyRead(OfflineJob::PROPERTY_COMMAND);
					break;

				case OfflineJob::STATUS_PREPARING:
					break;

				default:
					throw new RuntimeException();
			}
			$this->is_stale = $job->isStale();
			$stdin_path = $job->propertyPath(OfflineJob::PROPERTY_STDIN);
			if(file_exists($stdin_path) )
				$this->stdin_txt = $this->shorten($stdin_path, 100);
			else
				$this->stdin_txt = "";
		}
		catch(ErrorException $e){
			$this->notes = "$e";
		}
	}
	
	
	/**
	 * Returns all the jobs.
	 * @return Job[int]
	 * @throws ErrorException
	 */
	static function retrieveAll()
	{
		$jobs = /*. (Job[int]) .*/ array();
		if( ! file_exists(OfflineJob::$sessions_dir) )
			return $jobs;
		$d = opendir(OfflineJob::$sessions_dir);
		while( ($fn = readdir($d)) !== FALSE ){
			if( $fn === "." || $fn === ".." )
				continue;
			$jobs[] = new Job($fn);
		}
		return $jobs;
	}
}


/**
 * Jobs table row sorter object. An instance of this class is created for each
 * column of the job's table, and provides the sorting criteria for the specific
 * type of data that column contains. The rows of the table are sorted using the
 * generic Arrays::sort() method, which needs just an instance of this class to
 * know how to compare rows.
 */
class JobSorter implements Sorter {
	
	private $field = "";
	private $asc = FALSE;
	
	/**
	 * Creates a comparer object.
	 * @param string $field Name of the jobs table column.
	 * @param boolean $asc Ascending (TRUE) or descending (FALSE) order?
	 */
	function __construct($field, $asc)
	{
		$this->field = $field;
		$this->asc = $asc;
	}
	
	/**
	 * Comparer invoked by the sorter method on each pair of rows.
	 * @param object $a First row.
	 * @param object $b Seconds row.
	 * @return int Result of the comparison: if negative the first column comes
	 * first; if positive the second column comes first; if zero the content of
	 * the column are equal.
	 */
	function compare($a, $b) {
		$x = cast(Job::class, $a);
		$y = cast(Job::class, $b);
		switch($this->field){
			case "ticket":
				$r = strcmp($x->ticket, $y->ticket); 
				break;
			case "is_stale":
				if( ! $x->is_stale && $y->is_stale )
					$r = -1;
				else if( $x->is_stale && ! $y->is_stale )
					$r = 1;
				else
					$r = 0;
				break;
			case "command":
				$r = strcmp($x->command, $y->command); 
				break;
			case "started":
				$r = $x->started - $y->started;
				break;
			case "finished":
				$r = $x->finished - $y->finished;
				break;
			case "status":
				$r = $x->status - $y->status;
				break;
			default:
				throw new RuntimeException("unknown field: " . $this->field);
		}
		return $this->asc? $r : -$r;
	}
}


/**
 * Jobs monitor mask. Bt form to display the list of all the jobs and their
 * current status. A detail sub-mask is also available to see the content of
 * each job's working directory and to stop or delete the job.
 */
class JobsMonitor extends Form {
	
	const SYMBOL_ORDER_DESCENDING = "▲";
	const SYMBOL_ORDER_ASCENDING = "▼";
	
	/** @var Text */
	private $stdin_text;
	
	/**
	 * Returns the system shell program.
	 * @return string
	 */
	function getShellCmd()
	{
		if( PHP_OS === "WINNT" )
			$sh = $_SERVER["COMSPEC"];
		else
			$sh = "/bin/sh";
		return $sh;
	}
	
	/**
	 * @param int $ts
	 */
	private static function formatTimestamp($ts)
	{
		return date("c", $ts);
	}
	
	function __construct()
	{
		parent::__construct();
		$this->stdin_text = new Text($this, "stdin_text");
	}
	
	
	static function enter()
	{
		$f = new self();
		$f->setData("sort_field", "finished");
		$f->setData("sort_asc", FALSE);
		$f->render();
	}
	
	
	/**
	 * @param string $err
	 * @param string $notice
	 */
	function render($err = NULL, $notice = NULL)
	{
		$is_admin = UserSession::getSessionParameter("name") === "admin";
		try {
			$jobs = Job::retrieveAll();
		}
		catch(ErrorException $e){
			$err .= "$e";
			$jobs = array();
		}
		$sort_field = (string) $this->getData("sort_field");
		$sort_asc = (boolean) $this->getData("sort_asc");
		$sorter = new JobSorter($sort_field, $sort_asc);
		$jobs = cast(Job::class . "[int]", Arrays::sortBySorter($jobs, $sorter));
		
		Http::headerContentTypeHtmlUTF8();
		echo "<html><body><h1>Jobs Monitor</h1>";
		
		$this->open();
		
		if( strlen($err) > 0 )
			Html::errorBox("<pre>" . Html::text($err) . "</pre>");
		
		if( strlen($notice) > 0 )
			Html::noticeBox(Html::text($notice));
		
		echo "<div style='padding: 0.5em; border: solid black 0.2em;'>";
		if( ! Common::JOBS_ALLOWS_START ){
			echo "<i>Starting new jobs disabled by configuration.</i>";
		} else if( Common::JOBS_ALLOWS_START && $is_admin ){
			echo "Standard input for the command shell:<br>";
			$this->stdin_text->addAttributes("cols=60 rows=3");
			$this->stdin_text->render();
			echo "<p>";
			$this->button("Execute", "executeButton");
		} else {
			echo "<i>You are not granted for starting new jobs, sorry.</i>";
		}
		echo "</div><p>";
		
		echo "<table border=1 cellpadding=2 cellspacing=0 width='100%'><tr>";
		$fields = ["ticket" => "Ticket", "command" => "Command",
				"started" => "Started", "finished" => "Finished",
			"status" => "Status"];
		foreach($fields as $field => $caption){
			echo "<th>";
			if( $sort_field === $field ){
				if( $sort_asc ){
					$caption .= " " . self::SYMBOL_ORDER_ASCENDING;
					$this->anchor($caption, "sortBy", $field, FALSE);
				} else {
					$caption .= " " . self::SYMBOL_ORDER_DESCENDING;
					$this->anchor($caption, "sortBy", $field, TRUE);
				}
			} else {
				$this->anchor($caption, "sortBy", $field, TRUE);
			}
			echo "</th>";
		}
		
		echo "<th>Actions</th></tr>";
		
		foreach($jobs as $job){
			$ticket = $job->ticket;
			$command = $job->command;
			$started = $job->started == 0? "--" : self::formatTimestamp($job->started);
			$finished = $job->finished == 0? "--" : self::formatTimestamp($job->finished);
			$status_display = $job->status_display;
			$notes = $job->notes;
			
			$color = "#fff";
			switch($job->status){
				case OfflineJob::STATUS_PREPARING: $color = "#777"; break;
				case OfflineJob::STATUS_STARTING: $color = "#f7f"; break;
				case OfflineJob::STATUS_RUNNING: $color = "#f77"; break;
				case OfflineJob::STATUS_FINISHED: $color = "#7f7"; break;
				default:
			}
			if( $job->is_stale ){
				$notes = "(STALE)\n$notes";
				$color = "#cb9";
			}
			
			echo "<tr bgcolor='$color'><td>", Html::text($ticket), "</td>",
				"<td><small><tt>", Html::text($command), "</tt><br>Stdin: <tt>", Html::text($job->stdin_txt), "</tt></small></td>",
				"<td>", Html::text($started), "</td>",
				"<td>", Html::text($finished), "</td>",
				"<td><tt>", Html::text($status_display . "\n" . $notes), "</tt></td>",
				"<td>";
			$this->anchor("Details", "detailsButton", $ticket);
			if( $is_admin ){
				echo " ";
				$this->anchor("Edit...", "editButton", $ticket);
			}
			echo "</td></tr>";
			
		}
		echo "</table>";
		
		echo "<hr>";
		$this->button("Dismiss", "dismissButton");
		if( $is_admin ){
			Html::echoSpan(2);
			$this->button("Delete stale", "deleteStaleButton");
		}
		Html::echoSpan(2);
		$this->button("Update", "render");
		
		$this->close();
		echo Common::DISCLAIMER;
		echo "</body></html>";
	}
	
	
	/**
	 * @param string $sort_field
	 * @param boolean $sort_asc
	 */
	function sortBy($sort_field, $sort_asc)
	{
		$this->setData("sort_field", $sort_field);
		$this->setData("sort_asc", $sort_asc);
		$this->render();
	}
	
	
	function dismissButton()
	{
		UserSession::invokeCallBackward();
	}
	
	
	function deleteStaleButton()
	{
		$err = "";
		try {
			OfflineJob::deleteStaleSessions();
		}
		catch(ErrorException $e){
			$err = "$e";
		}
		$this->render($err);
	}
	
	
	/**
	 * @param string $ticket
	 */
	function detailsButton($ticket)
	{
		$this->returnTo("render");
		JobMonitor::enter($ticket);
	}
	
	
	/**
	 * @param string $ticket
	 */
	function editButton($ticket)
	{
		try {
			$job = new OfflineJob($ticket);
			$this->stdin_text->setValue($job->propertyRead(OfflineJob::PROPERTY_STDIN));
		}
		catch(ErrorException $e){
			$this->render("$e");
		}
		$this->render(NULL, "Editing copy of ticket $ticket.");
	}
	
	
	function executeButton()
	{
		$err = "";
		try {
			$job = new OfflineJob();
			$shell_program = $this->getShellCmd();
			$stdin_text = $this->stdin_text->getValue();
			if( PHP_OS === "WINNT" )
				$stdin_text = (string) str_replace("\n", "\r\n", $stdin_text);
			$job->propertyWrite("stdin.txt", $stdin_text . "\n");
			$job->start($shell_program);
		}
		catch(ErrorException $e){
			$err = "$e";
		}
		catch(InvalidArgumentException $e){
			$err = "$e";
		}
		$this->render($err);
	}
	
}