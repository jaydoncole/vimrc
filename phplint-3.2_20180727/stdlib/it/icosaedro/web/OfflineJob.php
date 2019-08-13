<?php

namespace it\icosaedro\web;

require_once __DIR__ . "/../../../all.php";

/*. require_module 'pcre';  require_module 'posix';  require_module 'com';.*/

use RuntimeException;
use ErrorException;
use InvalidArgumentException;
use COM;
use com_exception;
use Exception;
use it\icosaedro\utils\SecureRandom;
use it\icosaedro\containers\IntClass;

	
/**
 * Allows to start and control an offline worker process. Worker processes are
 * univocally identified by a ticket and survive the termination of the parent
 * process that create them. Their current status can be recovered later using
 * the same assigned ticket. Support for Linux and Windows OSs is provided.
 * 
 * <p><b>The session directory</b><br>
 * A new session directory is created for each worker. There, data files can be
 * created before launching the worker. The session directory is set as the
 * current working directory of the worker process. Several other files, here
 * named "properties", are created under the same session directory; their names
 * are also available as constants of this class:
 * 
 * <ul>
 * <li><tt>stdin.txt</tt> is set as the standard input of the worker. If missing,
 * an empty file is created.</li>
 * <li><tt>stdout.txt</tt> and <tt>stderr.txt</tt> are set as the standard output
 * and standard error of the worker.</li>
 * <li><tt>exit_status.txt</tt> reports the exit status code of the worker.</li>
 * <li><tt>command.txt</tt> contains the issued command that started the worker.</li>
 * </ul>
 * 
 * <p>The client application may also prepare several specific "properties"
 * under the session directory (either files or sub-directories) before starting
 * the worker.
 * 
 * 
 * <p><b>Creating and launching a new job</b><br>
 * By invoking the constructor without arguments, a new session directory is
 * created. Data files, if required, can be set using the property*() methods
 * that allow to write, read and recover the path of a property (that is, a file)
 * under the session directory. Once the session has been prepared, the start()
 * method can be invoked to start the execution of the worker process.
 * Finally, the getTicket() method returns the assigned ticket that allows to
 * retrieve the status of the job later. Example:
 * 
 * <blockquote><pre>
 * $job = new OfflineJob();
 * $mydatapath = $job-&gt;propertyPath("mydata.csv");
 * file_put_contents($mydatapath, ".....");
 * $job-&gt;start("c:\\wamp\\bin\\myprogram.exe mydata.csv myresults.xml");
 * $ticket = $job-&gt;getTicket();
 * </pre></blockquote>
 * 
 * <p>The ticket can be stored in the data base or in the user's web session,
 * as it allows to poll later for the status of the job and retrieve the result.
 * 
 * 
 * <p><b>Polling for the status of the job</b><br>
 * By invoking the constructor with the ticket as an argument, the state of the
 * job is retrieved:
 * 
 * <blockquote><pre>
 * $job = new OfflineJob($ticket);
 * echo "Status of the job: $job\n";
 * if( $job-&gt;getStatus() == OfflineJob::STATUS_FINISHED ){
 *     echo "Exit status is: ", $job-&gt;propertyRead(OfflineJob::PROPERTY_EXIT_STATUS, "\n";
 *     echo "Stdout: ", $job-&gt;propertyRead(OfflineJob::PROPERTY_STDOUT), "\n";
 *     echo "Stderr: ", $job-&gt;propertyRead(OfflineJob::PROPERTY_STDERR), "\n";
 *     if( $job-&gt;propertyExists("myresults.xml") )
 *         echo "Generated data path: ", $job-&gt;propertyPath("myresults.xml"), "\n";
 *     $job-&gt;delete();
 * }
 * </pre></blockquote>
 * 
 * <p><b>Terminating and deleting a job</b><br>
 * Two methods are provided to kill the worker process (leaving intact the current
 * contentt of the working directory) and to delete the the working directory
 * recursively (also killing the worker process if still running).
 * 
 * <p><b>Behavior on system crash or restart</b><br>
 * If the system is restarted, all the worker processes are terminated in some
 * more or less polite way, and the standard error and the exit status code may
 * or may not indicate this event. By recovering the state of these jobs, if
 * they were terminated regularly the resulting job will be "finished" although
 * their standard error and exit status code may indicate they failed to complete
 * their task. If the worker processes were terminated abruptly (that is, the
 * process does not exist anymore and the exit status property file is missing)
 * a message is logged in the job's standard error and then it is forced in the
 * finished state with exit code 128.
 * 
 * <p><b>Monitoring running workers in the processes table</b><br>
 * Workers are started with formal name "OfflineJob-TICKET", where "TICKET" is
 * the assigned ticket. They can be identified in the processes table by looking
 * for these names. Under Linux:
 * 
 * <blockquote><tt>pgrep -f ^OfflineJob-</tt></blockquote>
 * 
 * Under Windows:
 * 
 * <blockquote><tt>wmic process where "commandline like 'OfflineJob-%'"</tt></blockquote>
 * or open the task manager and, in the processes table, add the column "Command
 * line" where background jobs will be displayed as "OfflineJob-TICKET".
 * 
 * @author Umberto Salsi <salsi@icosaedro.it>
 * @version $Date: 2018/06/06 09:58:46 $
 */
class OfflineJob {
	
	/**
	 * File in the session directory containing the issued command that started
	 * the worker.
	 */
	const PROPERTY_COMMAND = "command.txt";
	
	/**
	 * File in the session directory whose existance indicate the worker process
	 * actually started. It contains the actual PID of the worker only under
	 * Windows, but its value is not used by this class anyway.
	 */
	const PROPERTY_PID = "pid.txt";
	
	/**
	 * File in the session directory containing the exit status code of the
	 * worker.
	 */
	const PROPERTY_EXIT_STATUS = "exit_status.txt";
	
	/**
	 * File in the session directory where the client application may prepare
	 * the standard input of the worker.
	 */
	const PROPERTY_STDIN = "stdin.txt";
	
	/**
	 * File in the session directory where the worker sends its standard output.
	 */
	const PROPERTY_STDOUT = "stdout.txt";
	
	/**
	 * File in the session directory where the worker sends its standard error.
	 */
	const PROPERTY_STDERR = "stderr.txt";
	
	/**
	 * Timeout for worker statup (s). If the worker process did not started
	 * within this time, the status of this object is forced to finished.
	 * This either means the system is very busy, or some other nasty event
	 * passed undetected (this mostly happens on Windows, where managing
	 * background processes is very tricky).
	 * @access private
	 */
	const STARTUP_TIMEOUT = 5;
	
	/**
	 * Base directory where jobs' temporary directories are created. Initialized
	 * to the system temporary directory by the static constructor of this class.
	 * The client can set this parameter with its own preferred sessions directory
	 * before invoking any method of this class.
	 * @var string 
	 */
	public static $sessions_dir;
	
	/** New instance of the job created; no command issued yet. */
	const STATUS_PREPARING = 0;
	
	/** Worker process is starting. */
	const STATUS_STARTING = 1;
	
	/** Worker process is still running. */
	const STATUS_RUNNING = 2;
	
	/** Worker process is ended. */
	const STATUS_FINISHED = 3;
	
	/** @var string */
	private $ticket;
	
	/** @var string */
	private $session_dir;
	
	/** @var int */
	private $status = self::STATUS_PREPARING;
	
	
	/**
	 * Returns the ticket identifying this job. It is a 16 characters long
	 * alphanumerical string randomly generated.
	 * @return string The ticket.
	 */
	function getTicket()
	{
		return $this->ticket;
	}
	
	
	/**
	 * Returns the status of this job.
	 * @return int Status of this job, see constants STATUS_*.
	 */
	function getStatus()
	{
		return $this->status;
	}
	
	
	/**
	 * Returns the path of a property file inside this job session directory.
	 * This file may or may not be already existing.
	 * @param string $name Name of the property file.
	 * @return string Path of the property.
	 */
	function propertyPath($name)
	{
		return $this->session_dir . "/$name";
	}
	
	
	/**
	 * Tells if a property with this name exists inside the session directory of
	 * this job.
	 * @param string $name Name of the property file.
	 * @return boolean
	 */
	function propertyExists($name)
	{
		return file_exists($this->propertyPath($name));
	}
	
	
	/**
	 * Retrieves the contents of the property.
	 * @param string $name Name of the property file.
	 * @return string Retrieved contents of the property.
	 * @throws ErrorException Failed accessing the file system. The property does
	 * not exist.
	 */
	function propertyRead($name)
	{
		return file_get_contents($this->propertyPath($name));
	}
	
	
	/**
	 * Writes the content of a property.
	 * @param string $name Name of the property file.
	 * @param string $value Contents of the property to write.
	 * @throws ErrorException Failed accessing the file system.
	 */
	function propertyWrite($name, $value)
	{
		file_put_contents($this->propertyPath($name), $value);
	}
	
	
	/**
	 * Returns a string containing the status description of this job.
	 * @return string Single-word describing the status of this job.
	 */
	function __toString()
	{
		switch($this->status){
			case self::STATUS_PREPARING: return "preparing";
			case self::STATUS_STARTING: return "starting";
			case self::STATUS_RUNNING: return "running";
			case self::STATUS_FINISHED: return "finished";
			default: throw new RuntimeException();
		}
	}
	
	
	/**
	 * Starts a process under this job.
	 * The exact syntax of the command depends on the specific OS, but generally
	 * it consists of a single line of arguments separated by at least one white
	 * space; the first arguments must be the full path of the program to start.
	 * 
	 * <p>The quoting rules of the arguments are here NOT specified. The general
	 * recommendation is to avoid any "strange" character, use simple plain ASCII,
	 * and rely on the data files to store more exotic characters instead.
	 * 
	 * <p>BEWARE. Never put untrusted user's submitted strings into the command
	 * line; nobody really knowns a 100% safe way to escape shell arguments.
	 * Untrusted user's submitted data should be saved in the session directory
	 * on a file with a simple name and that name passed as an argument to the
	 * worker process.
	 * 
	 * <p>Under Linux only, the command string can be any valid chunk of shell
	 * script, even consisting of several lines, so that the usual commands'
	 * path resolution algorithm applies (but check the PATH envar!). The
	 * saved exist status of the job is the exit status of the last command
	 * issued in the string.
	 * 
	 * <p>Under Windows only, the command line must invoke a single command, and
	 * the first argument must be the full path of the executable. To execute
	 * your own .bat script, first create that script in the session directory
	 * and then invoke the shell on it, for example:
	 * 
	 * <blockquote><pre>
	 * $job = new OfflineJob();
	 * $job-&gt;propertyWrite("myscript.bat",
	 *       "echo The current directory is %cd%\r\n"
	 *       ."echo Its contents are\r\n"
	 *       ."dir\r\n");
	 * $job-&gt;start("c:\\windows\\system32\\cmd.exe /c myscript.bat");
	 * </pre></blockquote>
	 * 
	 * In this case the exist status code saved by this class will be the return
	 * code of the cmd.exe program, not the actual exit code of your script.
	 * Please note that the end line marker in a .bat file is just "\r\n".
	 * 
	 * @param string $command Command that starts the worker process.
	 * @throws ErrorException Failed accessing the filesystem.
	 * @throws InvalidArgumentException Empty command string.
	 * @throws RuntimeException Command already issued. Unsupported OS. Missing
	 * worker process controller (Windows only).
	 */
	public function start($command)
	{
		/*
		 * Here we must:
		 * - Start a detached process, that is does not terminate if the parent
		 *   terminates.
		 * - Set the apparent name of the command to "OfflineJob-TICKET" so it
		 *   can safely be recognized in the processes table (the PID might be
		 *   reused by some other unaware process, or the system might crash
		 *   and be restarted).
		 * - Capture its stdout, stderr and exit status, even if that process
		 *   crashes.
		 * There is no an easy way to do all this using the available PHP functions
		 * only (if I'm wrong, please let my know).
		 */
		
		if( $this->status !== self::STATUS_PREPARING )
			throw new RuntimeException("invalid status -- command already issued");
		
		$command = trim($command);
		if( strlen($command) == 0 )
			throw new InvalidArgumentException("empty command");
		$this->propertyWrite(self::PROPERTY_COMMAND, $command);
		
		// The stdin file is mandatory:
		if( ! $this->propertyExists(self::PROPERTY_STDIN) )
			$this->propertyWrite(self::PROPERTY_STDIN, "");
		
		if( PHP_OS === "Linux" ){
			$command_sh =
				"echo -n unknown_do_not_care >" . self::PROPERTY_PID . "\n"
				. "cd '" . $this->session_dir . "' || exit 128\n"
				. "exec < " . self::PROPERTY_STDIN ."\n"
				. "exec > " . self::PROPERTY_STDOUT ."\n"
				. "exec 2> " . self::PROPERTY_STDERR ."\n"
				. "$command\n"
				. "echo -n \$? >" . self::PROPERTY_EXIT_STATUS . "\n";
			$this->propertyWrite("command.sh", $command_sh);
			// See recipe to start a background process by Colin McKinnon at
			// http://symcbean.blogspot.it/2010/02/php-and-long-running-processes.html
			$exec_command =
				"( echo \"SHELL=/bin/bash bash -c 'exec -a OfflineJob-" . $this->ticket
				. " bash " . $this->session_dir . "/command.sh'\""
				. " | at -M now 2>&1"
				. " && echo unknown_do_not_care > " . $this->session_dir . "/" . self::PROPERTY_PID ." ) 2>&1";
			$exec_output = /*. (string[int]) .*/ array();
			$exec_ret_var = 0;
			$exec_last_line = exec($exec_command, $exec_output, $exec_ret_var);
			$exec_output_imploded = implode("\n", $exec_output);
			if( $exec_ret_var !== 0 )
				throw new ErrorException($exec_last_line . "; detailed output:\n".implode("\n", $exec_output));
		
		} else if( PHP_OS === "WINNT" ){
			$controller = __DIR__ . "\\WindowsWorkerProcessController\\workerProcessController.exe";
			if( !file_exists($controller) )
				throw new RuntimeException("missing worker process controller $controller");
			$exec_command = $controller
					. " --session-dir " . $this->session_dir
					. " --ticket OfflineJob-" . $this->ticket
					. " --worker $command";
			try {
				$ws = new COM("WScript.Shell");
				$cb = /*. (mixed[int]) .*/ array();
				$cb[] = $ws;
				$cb[] = "exec";
				$exe = call_user_func($cb, $exec_command);
			}
			catch(com_exception $e){
				throw new ErrorException($e->getMessage());
			}
			// By the signature of call_user_func(), this is formally needed:
			catch(Exception $e){
				throw new ErrorException($e->getMessage());
			}
			
		} else {
			throw new RuntimeException("unsupported OS: " . PHP_OS);
		}
		
		$this->status = self::STATUS_STARTING;
	}
	
	
	/**
	 * Append a line to a possibly already existing property.
	 * @param string $name
	 * @param string $data
	 * @throws ErrorException Failed accessing the file system.
	 */
	private function propertyAppend($name, $data)
	{
		$fn = $this->propertyPath($name);
		if( file_exists($fn) )
			file_put_contents($fn, "\n$data", FILE_APPEND);
		else
			file_put_contents($fn, $data);
	}
	
	
	/**
	 * Tells if the worker process is still alive.
	 * @return boolean True if the worker process is alive.
	 * @throws ErrorException Failed querying the processes table.
	 */
	private function isAlive()
	{
		if( $this->status == self::STATUS_STARTING
		&& time() - filectime($this->propertyPath(self::PROPERTY_COMMAND)) > self::STARTUP_TIMEOUT ){
			$this->propertyAppend(self::PROPERTY_STDERR,
				"Timeout waiting for worker process to start.\n");
			$this->propertyWrite(self::PROPERTY_EXIT_STATUS, "128");
			// These files are expected to exist:
			$this->propertyWrite(self::PROPERTY_STDOUT, "");
			$this->propertyWrite(self::PROPERTY_PID, "0");
			
			$this->status = self::STATUS_FINISHED;
		}
		
		if( PHP_OS === "Linux" ){
			$cmd = "pgrep -f \"^OfflineJob-" . $this->ticket . "\\b\"";
			$output = /*. (string[int]) .*/ array();
			$ret_val = 0;
			$last_line = exec("$cmd 2>&1", $output, $ret_val);
			if( $ret_val == 0 ){
				return TRUE;
			} else if( $ret_val == 1 ){
				return FALSE;
			} else {
				// Anything else.
				throw new ErrorException("\"$cmd\" returned code is $ret_val: $last_line, " . implode("\n", $output));
			}
		
		} else if( PHP_OS === "WINNT" ){
			$wmic = "c:/windows/system32/wbem/wmic.exe";
			if( ! file_exists($wmic) )
				$wmic = "wmic"; // hope it is in the %path%
			$cmd = "$wmic process where \"commandline like 'OfflineJob-"
				. $this->ticket . "%'\"";
			$output = /*. (string[int]) .*/ array();
			$ret_val = 0;
			$last_line = exec("$cmd 2>&1", $output, $ret_val);
			$output_imploded = implode("\n", $output);
			if( $ret_val == 0 ){
				// Command succeeded.
			} else {
				// Anything else.
				throw new ErrorException("\"$cmd\" returned code is $ret_val: $last_line, $output_imploded");
			}
			return strpos($output_imploded, $this->ticket) !== FALSE;
			
		} else {
			throw new RuntimeException("unsupported OS: " . PHP_OS);
		}
	}
	
	
	/**
	 * Under Linux only, kills the given PID and any child. Does
	 * nothing if the process does not exist.
	 * @param int $pid
	 * @return void
	 * @throws ErrorException
	 */
	private static function linuxRecursiveKill($pid)
	{
		$cmd = "ps -o pid --no-headers --ppid $pid";
		$output = /*. (string[int]) .*/ array();
		$ret_val = 0;
		$last_line = exec("$cmd 2>&1", $output, $ret_val);
		if( $ret_val == 0 ){
			// Process signaled.
		} else if( $ret_val == 1 ){
			// Process does not exist anymore.
		} else {
			// Anything else.
			throw new ErrorException("\"$cmd\" returned code is $ret_val: $last_line, " . implode("\n", $output));
		}
		$some_child_signaled = FALSE;
		foreach($output as $line){
			try {
				$child = IntClass::parse(trim($line));
			} catch (InvalidArgumentException $e) {
				continue;
			}
			try {
				self::linuxRecursiveKill($child);
			}
			catch(ErrorException $e){
				// can't do very much here
			}
			$some_child_signaled = TRUE;
		}
		if( $some_child_signaled )
			sleep(1);
		posix_kill($pid, 15 /* SIGTERM */);
	}
	
	
	/**
	 * Under Windows only, kills the given PID and any child. Does
	 * nothing if the process does not exist.
	 * @param int $pid
	 * @return void
	 * @throws ErrorException
	 */
	private static function windowsRecursiveKill($pid)
	{
		/*
		 * Note: "taskkill /f /t /pid $pid" still complains there are still running
		 * child despite all the flags which seem ignored. So we must read
		 * the whole process tree starting from $pid and terminate each child
		 * one by one in depth-first order using the "wmic" command.
		 */
		$wmic = "c:/windows/system32/wbem/wmic.exe";
		if( ! file_exists($wmic) )
			$wmic = "wmic"; // hope it is in the %path%
		
		// Killing any child of $pid recursively:
		$cmd = "$wmic process where (ParentProcessId=$pid) get ProcessId";
		$output = /*. (string[int]) .*/ array();
		$ret_val = 0;
		$last_line = exec("$cmd 2>&1", $output, $ret_val);
		if( $ret_val == 0 ){
			// Process signaled, or no matching process found.
		} else {
			// Anything else.
			throw new ErrorException("\"$cmd\" returned code is $ret_val: $last_line, ".implode("\n", $output));
		}
		$some_child_signaled = FALSE;
		for($i = 1; $i < count($output); $i++){
			try {
				$child = IntClass::parse(trim($output[$i]));
			} catch (InvalidArgumentException $e) {
				continue;
			}
			try {
				self::windowsRecursiveKill($child);
			}
			catch(ErrorException $e){
				// can't do very much here
			}
			$some_child_signaled = TRUE;
		}
		
		// Killing $pid:
		if( $some_child_signaled )
			sleep(1);
		$cmd = "$wmic process where \"ProcessId=$pid\" call terminate";
		$output = /*. (string[int]) .*/ array();
		$ret_val = 0;
		$last_line = exec("$cmd 2>&1", $output, $ret_val);
		if( $ret_val == 0 ){
			// Process signaled.
		} else {
			// Anything else.
			throw new ErrorException("\"$cmd\" returned code is $ret_val: $last_line, ".implode("\n", $output));
		}
	}
	
	
	/**
	 * Issue a worker process termination request.
	 * @throws ErrorException Failed accessing the file system. Failed invoking
	 * process termination.
	 */
	private function sendKillRequest()
	{
		if( $this->propertyExists(self::PROPERTY_EXIT_STATUS) )
			return; // worker terminated in the meanwhile
		
		if( PHP_OS === "Linux" ){
			$cmd = "pgrep -f \"^OfflineJob-" . $this->ticket . "\\b\"";
			$output = /*. (string[int]) .*/ array();
			$ret_val = 0;
			$last_line = exec("$cmd 2>&1", $output, $ret_val);
			if( $ret_val == 0 ){
				if( count($output) > 0 ){
					try {
						$pid = IntClass::parse(trim($output[0]));
					} catch (InvalidArgumentException $e) {
						// ...then?
						return;
					}
					self::linuxRecursiveKill($pid);
				}
			} else if( $ret_val == 1 ){
				// Process does not exist or already terminated.
			} else {
				// Anything else.
				throw new ErrorException("\"$cmd\" returned code is $ret_val: $last_line, " . implode("\n", $output));
			}
		
		} else if( PHP_OS === "WINNT" ){
			$wmic = "c:/windows/system32/wbem/wmic.exe";
			if( ! file_exists($wmic) )
				$wmic = "wmic"; // hope it is in the %path%
			$cmd = "$wmic process where \"commandline like 'OfflineJob-" . $this->ticket
				."%'\" get ProcessId";
			$output = /*. (string[int]) .*/ array();
			$ret_val = 0;
			$last_line = exec("$cmd 2>&1", $output, $ret_val);
			if( $ret_val == 0 ){
				$pid = IntClass::parse(trim($output[1]));
				self::windowsRecursiveKill($pid);
			} else {
				// Anything else.
				throw new ErrorException("\"$cmd\" returned code is $ret_val: $last_line, ".implode("\n", $output));
			}
			
		} else {
			throw new RuntimeException("unsupported OS: " . PHP_OS);
		}
	}
	
	
	/**
	 * Updates the status of this object by looking at the contents of the properties
	 * and at the processes actually running on the system.
	 * @throws ErrorException Failed accessing the file system.
	 */
	function updateStatus()
	{
		if( $this->propertyExists(self::PROPERTY_EXIT_STATUS) )
			$this->status = self::STATUS_FINISHED;
		else if( $this->propertyExists(self::PROPERTY_PID) )
			$this->status = self::STATUS_RUNNING;
		else if( $this->propertyExists(self::PROPERTY_COMMAND) )
			$this->status = self::STATUS_STARTING;
		else
			$this->status = self::STATUS_PREPARING;
		
		if( $this->status == self::STATUS_STARTING
		&& time() - filectime($this->propertyPath(self::PROPERTY_COMMAND)) > self::STARTUP_TIMEOUT ){
			$this->propertyAppend(self::PROPERTY_STDERR, "Timeout waiting for worker process to start.\n");
			$this->propertyWrite(self::PROPERTY_EXIT_STATUS, "128");
			// These files are expected to exist:
			$this->propertyWrite(self::PROPERTY_STDOUT, "");
			$this->propertyWrite(self::PROPERTY_PID, "0");
			
			$this->status = self::STATUS_FINISHED;
			
		} else if( $this->status == self::STATUS_RUNNING ){
			// Check if worker process is still alive, but avoid the expensive
			// call to isAlive(). Save in the "is_alive" change time the outcome.
			$is_alive = $this->propertyPath("is_alive");
			$last_seen_alive = file_exists($is_alive)? filectime($is_alive) : filectime($this->propertyPath(self::PROPERTY_COMMAND));
			if( time() - $last_seen_alive > 5 ){ // avoid expensive call to isAlive()
				if( $this->isAlive() ){
					touch($is_alive);
				} else if( $this->propertyExists(self::PROPERTY_EXIT_STATUS) ){
					$this->status = self::STATUS_FINISHED;
				} else {
					$this->propertyAppend(self::PROPERTY_STDERR, "Worker process died unexpectedly.\n");
					$this->propertyWrite(self::PROPERTY_EXIT_STATUS, "128");
					$this->status = self::STATUS_FINISHED;
				}
			}
		}
	}
	
	
	/**
	 * Sends a termination request to the worker process. Does nothing if the
	 * job is in preparing of finished status. If still starting, waits few
	 * seconds and tries again. 
	 * @return void
	 * @throws ErrorException
	 */
	function kill()
	{
		// Performs several attempts to kill before givin up.
		$i = 3; // no. of attempts
		do {
			$this->updateStatus();
			switch($this->status){

				case self::STATUS_PREPARING:
					return;

				case self::STATUS_STARTING:
					break;
				
				case self::STATUS_RUNNING:
					$this->sendKillRequest();
					break;
				
				case self::STATUS_FINISHED:
					return;
					
				default:
					throw new RuntimeException();
			}
			$i--;
			if( $i <= 0 )
				return;
			sleep(2);
		}while(TRUE);
	}
	
	
	/**
	 * Recursively deletes a directory.
	 * @param string $directory Path of the directory.
	 * @throws ErrorException Operation failed.
	 */
	private function deleteDirectory($directory)
	{
		$directory_escaped = escapeshellarg($directory);
		if( PHP_OS === "Linux" ){
			$cmd = "rm -fr -- $directory_escaped >/dev/null";
		} else if( PHP_OS === "WINNT" ){
			$cmd = "rmdir /S /Q $directory_escaped >NUL";
		} else {
			throw new RuntimeException("directory deletion not implemented on this OS: " . PHP_OS);
		}
		$res = system($cmd, $exit_status);
		if( $exit_status != 0 || $res === FALSE ){
			throw new ErrorException(
				"directory deletion failed:\n"
				. "  command: $cmd\n"
				. "  last line of output: " . $res . "\n"
				. "  exit status: $exit_status");
		}
	}
	

	/**
	 * Delete this job from the jobs directory. Also kills the worker, if still
	 * running.
	 * @throws ErrorException
	 */
	function delete()
	{
		$this->kill();
		$this->status = self::STATUS_FINISHED;
		$this->deleteDirectory($this->session_dir);
		// Invalidate usage of this object from here on:
		$this->session_dir = "/?";
	}
	
	
	/**
	 * Static initializer of this class, automatically invoked.
	 */
	static function static_construct()
	{
		try {
			self::$sessions_dir = sys_get_temp_dir() . "/OfflineJobs";
			if( !file_exists(self::$sessions_dir) )
				mkdir(self::$sessions_dir);
		}
		catch(ErrorException $e){
			// Static initialize cannot throw exceptions.
			if( PHP_OS === "Linux" )
				self::$sessions_dir = "/tmp";
			else if( PHP_OS === "WINNT" )
				self::$sessions_dir = "c:/temp";
			else
				self::$sessions_dir = "/?";
		}
	}
	
	
	/**
	 * Creates a new job or retrieves an existing one. If the ticket is missing
	 * or NULL, a new session directory in "prepare" status is created. If a
	 * ticket is specified, the current state of the job is retrieved.
	 * @param string $ticket Ticket to retrieve or NULL for a new session.
	 * @throws InvalidArgumentException Invalid ticket syntax.
	 * @throws ErrorException Failed accessing the file system. No job with this
	 * ticket, or the job has already been deleted.
	 */
	public function __construct($ticket = NULL)
	{
		if( $ticket === NULL ){
			// Create session.
			$ticket = bin2hex( SecureRandom::randomBytes(8) );
			$this->session_dir = self::$sessions_dir . "/$ticket";
			mkdir($this->session_dir); // throws ErrorException on dir name collision
			$this->ticket = $ticket;
			$this->status = self::STATUS_PREPARING;
			
		} else {
			// Resume session.
			if(preg_match("/^[0-9a-f]{16,}\$/sD", $ticket) !== 1 )
				throw new InvalidArgumentException("invalid session syntax: $ticket");

			$this->ticket = $ticket;
			$this->session_dir = self::$sessions_dir . "/$ticket";
			if( ! file_exists($this->session_dir) )
				throw new ErrorException("session does not exist: " . $this->session_dir);
			$this->updateStatus();
		}
	}
	
	
	/**
	 * Tells is the job is stale and could be deleted to recover disk space.
	 * @return boolean
	 * @throws ErrorException Failed accessing the file system.
	 */
	function isStale()
	{
		switch($this->status){
			
			case self::STATUS_PREPARING:
				// Job created, but client missed to start any worker process.
				return time() - filectime($this->session_dir) > 3600;
			
			case self::STATUS_STARTING:
				// Something unusual happened starting the worker.
				return time() - filectime($this->propertyPath(self::PROPERTY_COMMAND)) > 60;
			
			case self::STATUS_RUNNING:
				// Worker is taking to much time to complete.
				return time() - filectime($this->propertyPath(self::PROPERTY_COMMAND)) > 4 * 3600;
			
			case self::STATUS_FINISHED:
				// Nobody took care of to retrieve the results since a very long time.
				return time() - filectime($this->session_dir) > 8 * 86400;
			
			default:
				throw new RuntimeException();
		}
	}
	
	
	/**
	 * Delete stale job sessions, recovering disk space and possibly killing
	 * hung worker processes and sessions the application forgot about.
	 * This method should be invoked from time to time as part of the general
	 * system maintenance tasks.
	 * @throws ErrorException Failed accessing the file system. Failed accessing
	 * or deleting some job session.
	 */
	static function deleteStaleSessions()
	{
		$err = "";
		$d = opendir(self::$sessions_dir);
		while( ($fn = readdir($d)) !== FALSE ){
			if( $fn === "." || $fn === ".." )
				continue;
			try {
				$job = new self($fn);
				if( $job->isStale() )
					$job->delete();
			}
			catch(ErrorException $e){
				$err .= "\n" . $e->getMessage();
			}
		}
		if( strlen($err) > 0 )
			throw new ErrorException($err);
	}
	
}

OfflineJob::static_construct();
