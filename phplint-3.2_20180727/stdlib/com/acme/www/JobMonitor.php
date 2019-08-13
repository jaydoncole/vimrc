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

use Exception;
use ErrorException;
use it\icosaedro\web\bt_\Form;
use it\icosaedro\web\bt_\UserSession;
use it\icosaedro\web\Http;
use it\icosaedro\web\Html;
use it\icosaedro\web\OfflineJob;
use it\icosaedro\web\FileDownload;
use com\acme\www\Common;

/**
 * Form to display the contents of a background job. From here the user may see
 * the current status of the job, he may see and doanload any file from the
 * job's working directory, and he may stop or delete the job at all.
 * Background jobs are implemented using the {@link it\icosaedro\web\OfflineJob}
 * class.
 */
class JobMonitor extends Form {
	
	
	/**
	 * @param string $ticket
	 */
	static function enter($ticket)
	{
		$f = new self();
		$f->setData("ticket", $ticket);
		$f->render();
	}
	
	
	/**
	 * @param string $fn
	 * @throws ErrorException
	 */
	static function download($fn)
	{
		$name = basename($fn);
		$type = FileDownload::getTypeFromFilename($name);
		FileDownload::sendHeaders($name, $type, TRUE);
		FileDownload::sendFile($fn);
	}
	
	
	/**
	 * Recursively lists files.
	 * @param string $base Job's working directory with trailing slash char.
	 * @param string $dir Relative path to the nested directory with trailing
	 * slash char. or empty.
	 * @return void
	 * @throws ErrorException
	 */
	private function echoWorkingDirectoryRecurse($base, $dir)
	{
		// Rule: we assume any directory path has a trailing slash char.
		$d = opendir("$base$dir");
		while( ($entry = readdir($d)) !== FALSE ){
			if( $entry === "." || $entry === ".." )
				continue;
			$full = "$base$dir$entry";
			if( is_dir($full) ){
				$this->echoWorkingDirectoryRecurse($base, "$dir$entry/");
			} else {
				$t = filectime($full);
				$len = filesize($full);
				// FIXME: file name encoding?
				echo "<tr>",
					"<td><tt>", date("c", $t), "</tt></td>",
					"<td align=right>$len</td>",
					"<td><tt>";
				UserSession::anchor(Html::text("$dir$entry"), self::class . "::download", $full);
				echo "</tt></td>";
			}
		}
	}
	
	
	/**
	 * 
	 * @param string $dir
	 * @throws ErrorException
	 */
	private function echoWorkingDirectory($dir)
	{
		echo "<h2>Working directory:</h2>";
		if( !file_exists($dir) ){
			echo "<i>Working directory $dir does not exist or cannot be accessed anymore.</i>";
			return;
		}
		
		echo "Contents of <tt>", Html::text($dir), "</tt>:<br>";
		echo "<table border=0 cellspacing=0 cellpadding=2>",
			"<tr><th>Changed</th><th>Length</th><th>Path</th></tr>";
		try {
			$this->echoWorkingDirectoryRecurse("$dir/", "");
		} catch (ErrorException $e) {
			echo "</table>";
			throw $e;
		}
		echo "</table>";
	}
	
	
	/**
	 * @param string $err
	 */
	function render($err = NULL)
	{
		$is_admin = UserSession::getSessionParameter("name") === "admin";
		$ticket = (string) $this->getData("ticket");
		$command = "--";
		$status = "--";
		$exit_status = "--";
		$stderr = "--";
		$stdout = "--";
		$stdin = "--";
		
		try {
			$job = new OfflineJob($ticket);
		} catch (Exception $e) {
			// Failing to retrieve basic jobs data is fatal. Feedback + return.
			Http::headerContentTypeHtmlUTF8();
			echo "<html><body><h1>Jobs Monitor - Ticket $ticket outcome</h1>";
			$this->open();
			Html::errorBox("<pre>" . Html::text("$e") . "</pre>");
			$this->button("Dismiss", "dismissButton");
			$this->close();
			echo "</body></html>";
			return;
		}
		
		try {
			$status = "$job";
			if( $job->getStatus() >= OfflineJob::STATUS_PREPARING ){
				if( $job->getStatus() >= OfflineJob::STATUS_STARTING ){
					$command = $job->propertyRead(OfflineJob::PROPERTY_COMMAND);
					if( $job->getStatus() >= OfflineJob::STATUS_RUNNING ){
						$stderr = $job->propertyRead(OfflineJob::PROPERTY_STDERR);
						$stdout = $job->propertyRead(OfflineJob::PROPERTY_STDOUT);
						$stdin = $job->propertyRead(OfflineJob::PROPERTY_STDIN);
						if( $job->getStatus() >= OfflineJob::STATUS_FINISHED ){
							$exit_status = $job->propertyRead(OfflineJob::PROPERTY_EXIT_STATUS);
						}
					}
				}
			}
		}
		catch(ErrorException $e){
			// Failing to retrieve *some* jobs property is not fatal.
			$err .= "\n$e";
		}
		
		Http::headerContentTypeHtmlUTF8();
		echo "<html><body><h1>Jobs Monitor - Ticket $ticket outcome</h1>";
		$this->open();
		
		if( strlen($err) > 0 )
			Html::errorBox("<pre>" . Html::text($err) . "</pre>");
		
		echo "<h2>Summary:</h2>";
		echo "Status: <b>$status</b><p>";
		echo "Exit status: ", Html::text($exit_status), "<p>";
		echo "Stderr: <pre>", Html::text($stderr), "</pre>";
		echo "Stdout: <pre>", Html::text($stdout), "</pre>";
		echo "Stdin: <pre>", Html::text($stdin), "</pre>";
		
		try {
			$this->echoWorkingDirectory(OfflineJob::$sessions_dir . "/$ticket");
		}
		catch(ErrorException $e){
			Html::errorBox("<pre>$e</pre>");
		}
		
		echo "<hr>";
		$this->button("Dismiss", "dismissButton");
		if( $is_admin ){
			if( $job->getStatus() == OfflineJob::STATUS_RUNNING ){
				Html::echoSpan(2);
				$this->button("Stop", "stopButton");
			}
			Html::echoSpan(2);
			$this->button("Delete", "deleteButton");
		}
		Html::echoSpan(2);
		$this->button("Update", "render");
		
		$this->close();
		
		echo Common::DISCLAIMER;
		echo "</body></html>";
	}
	
	
	function dismissButton()
	{
		UserSession::invokeCallBackward();
	}
	
	
	function stopButton()
	{
		$ticket = (string) $this->getData("ticket");
		try {
			$job = new OfflineJob($ticket);
			$job->kill();
		}
		catch(ErrorException $e){
			$this->render("$e");
			return;
		}
		$this->render();
	}
	
	
	function deleteButton()
	{
		$ticket = (string) $this->getData("ticket");
		try {
			$job = new OfflineJob($ticket);
			$job->delete();
		}
		catch(ErrorException $e){
			$this->render("$e");
			return;
		}
		UserSession::invokeCallBackward();
	}
	
}