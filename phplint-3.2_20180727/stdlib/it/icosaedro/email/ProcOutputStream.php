<?php

namespace it\icosaedro\email;

require_once __DIR__ . "/../../../autoload.php";
require_once __DIR__ . "/../../../errors.php";

/*. require_module 'spl'; .*/

use ErrorException;
use LogicException;
use InvalidArgumentException;
use it\icosaedro\io\OutputStream;
use it\icosaedro\io\IOException;
use it\icosaedro\utils\Random;

/**
 * Send data in streamed mode to a background running process, capturing stdout,
 * stderr and exit status code.
 * 
 * <p>By writing into this object through the writeByte*() methods, the client
 * may send data to the standard input of the process.
 * 
 * <p>The standard output and standard error streams of the process can be read
 * incrementally at any time, even after process termination and closed.
 * 
 * <p>Once closed, the exit status of the process can be retrieved.
 * 
 * <p>Since the client and the controlled background process run in
 * separated processes, their communication is asynchronous; no specific
 * synchronization tool is provided by this class; access to output and
 * error streams are mostly intended to retrieve the final result of the
 * processing and to to help diagnosing possible issues.
 * 
 * <h2>Example</h2>
 * Sending email through the "sendmail" program available with Exim, Postfix and
 * Sendmail SMTP servers:
 * <blockquote><pre>
 * $p = new ProcOutputStream("/usr/sbin/sendmail -oi -t");
 * $p-&gt;writeBytes("From: me@domain.it\n");
 * $p-&gt;writeBytes("To: you@domain.it\n");
 * $p-&gt;writeBytes("Subject: Testing ProcOutputStream\n");
 * $p-&gt;writeBytes("\nHi!\n");
 * $p-&gt;close();
 * $err_code = $p-&gt;getExitCode();
 * $err_msg = $p-&gt;readStderr();
 * if( $err_code == 0 )
 *	echo "Message accepted for delivery.";
 * else
 *	echo "Failed sending the message: $err_msg (code $err_code)."
 * </pre></blockquote>
 * 
 * <p>Some programs terminate only once their stdin stream is closed. This is
 * just the case of sendmail with the -oi option added, so the only way to normally
 * terminate the program is to close() this object, which in turn closes the
 * stdin stream which is sensed by sendmail as the end of the message.
 *
 * <h2>Errors and exceptions handling</h2>
 * The methods of this class allows to control the process and may throw an
 * exception only if they fail doing that; lacking of exceptions does not mean
 * the process succeeded; client code should always check the process exit
 * status and the process stderr to detect if the process succeeded performing
 * the required task. For example, trying to start a program that does not exit
 * may result in an exit status of 127 from the Linux shell or 9009 from the
 * Windows shell and a corresponding error message on stderr; an exception is
 * throws only if the client attempts to write data on a died (or never started)
 * process.
 * 
 * <h2>Windows batch</h2>
 * The command to execute is inserted into a little script to capture its specific
 * exit status code. For this reason if the command to invoke is, in turn, another
 * batch script myscript.bat, then that command MUST be invoked with with "call
 * myscript.bat" to get the expected "go-sub" behavior; without the "call" it is
 * much like a "go-to" and then the originating script never completes.
 * 
 * @author Umberto Salsi <salsi@icosaedro.it>
 * @version $Date: 2018/07/14 11:45:33 $
 */
class ProcOutputStream extends OutputStream {
	
/*
 * IMPLEMENTATION NOTES.
 * 
 * 1. Basically, this class wraps the PHP popen()/fwrite()/pclose() functions
 * to implement the OutputStream interface. Unfortunately, these functions do
 * not allow to detected buffer full condition, so fwrite() simply blocks if the
 * output buffer is full until some space becomes available of the process
 * terminates. If the process terminates, fwrite() return zero, and this value
 * is used to detect premature process termination while still sending data.
 * If the process is still alive, but isn't processing input data anymore, this
 * blocks forever (or until PHP timeout). There is no way to set a timeout and
 * to let the program to know what's happening.
 * 
 * Setting the non-blocking mode with stream_set_blocking() succeeds under Linux
 * (but fails under Windows) but does not make any difference and fwrite() still
 * blocks.
 * 
 * 2. The only way to capture stdout and stderr of the process is to save its
 * output on a file. This class does just that and offers the readStdxxx() and
 * eofStdxxx() methods to incrementally read the stdout and stderr as they were
 * real pipes.
 * 
 * 3. The alternative to popen() is proc_open() which offers a higher degree of
 * control and allows to avoid using files, but pipes are still blocking under
 * Windows.
 * 
 * 4. To detect process termination, we create a little shell script that
 * starts the process and saves the exit status code on a file; when this
 * latter file contains data, it means the process is terminates.
 * 
 * About these issues, see also:
 * 
 * proc_open on Windows hangs forever
 * (actually, fread() hangs, not proc_open)
 * https://bugs.php.net/bug.php?id=51800 (closed, but still does not work)
 * 
 * stream_set_blocking() does not work with pipes opened with proc_open()
 * https://bugs.php.net/bug.php?id=47918 (wont fix)
 */
	
	/**
	 * Shell input stream open with popen().
	 * @var resource
	 */
	private $proc;
	
	/**
	 * Temporary script where to save the commands to execute.
	 * @var string
	 */
	private $script_path;
	
	/**
	 * Temporary stdout file. Process stdout is sent here.
	 * @var string
	 */
	private $stdout_path;
	
	/**
	 * Current stdout file offset to simulate reading from a pipe.
	 * @var int
	 */
	private $stdout_pos = 0;
	
	/**
	 * Temporary stderr file. Process stderr is sent here.
	 * @var string
	 */
	private $stderr_path;
	
	/**
	 * Current stderr file offset to simulate reading from a pipe.
	 * @var int
	 */
	private $stderr_pos = 0;
	
	/**
	 * Once finished, the script saves here the process exit status.
	 * @var string
	 */
	private $exit_code_path;
	
	/**
	 * Process exit code. Has no meaning while still running.
	 * @var int
	 */
	private $exit_code = 0;
	
	/**
	 * Creates a stream writer through the given process.
	 * @param string $cmd Shell command. The command should not contain redirection
	 * options as them are set here for internal use (capturing stdout and stderr
	 * of the process).
	 * @throws IOException Failed opening the shell for the command.
	 */
	function __construct($cmd)
	{
		// Creates all the temporary files for script, stdout, stderr and exit code:
		$base = bin2hex( Random::getCommon()->randomBytes(8) );
		try {
			$base = sys_get_temp_dir() . DIRECTORY_SEPARATOR . "$base";
			$this->stdout_path = "$base-out.txt";     touch($this->stdout_path);
			$this->stderr_path = "$base-err.txt";     touch($this->stderr_path);
			$this->exit_code_path = "$base-ext.txt";
		}
		catch(ErrorException $e){
			throw new IOException($e->getMessage());
		}
		
		// Create shell script that captures exit status code of the process:
		if( PHP_OS === "WINNT" ){
			$this->script_path = "$base.bat";
			$script =
				"@echo off\r\n"
				. "$cmd\r\n"
				. "echo %errorlevel% >" . $this->exit_code_path . "\r\n";
			$start_cmd = $this->script_path . " >" . $this->stdout_path . " 2>" . $this->stderr_path;
		} else {
			$this->script_path = "$base.sh";
			$script =
				"$cmd\n"
				. "echo \$? >" . $this->exit_code_path . "\n";
			$start_cmd = $this->script_path . " >" . $this->stdout_path . " 2>" . $this->stderr_path;
		}
		try {
			file_put_contents($this->script_path, $script);
			chmod($this->script_path, 0700);
		}
		catch(ErrorException $e){
			throw new IOException($e->getMessage());
		}
		
		// Execute our script, which in turn starts the process:
		$this->proc = popen($start_cmd, "w");
		if( $this->proc === FALSE )
			throw new IOException("$cmd: process opening failed");
	}
	
	/**
	 * Tells if the process is still running.
	 * @return boolean
	 * @throws IOException
	 */
	function isRunning()
	{
		return $this->proc !== NULL && ! file_exists($this->exit_code_path);
	}
	
	/**
	 * Returns the exist code of the process. Exit code of the process is available
	 * only after close.
	 * @return int
	 * @throws LogicException Still open, exit code not available yet.
	 */
	function getExitCode()
	{
		if( $this->proc !== NULL )
			throw new LogicException("process still open, exit code not available yet");
		return $this->exit_code;
	}
	
	/**
	 * Tells if we are at the EOF of the stdout data coming from the process.
	 * @return boolean TRUE if no more data available; FALSE if more data available.
	 * @throws IOException
	 */
	function eofStdout()
	{
		try {
			return filesize($this->stdout_path) <= $this->stdout_pos;
		}
		catch(ErrorException $e){
			throw new IOException($e->getMessage());
		}
	}
	
	/**
	 * Read new data from stdout.
	 * @return string Latest available data from stdout, possibly the empty
	 * string if no data available.
	 * @throws IOException
	 */
	function readStdout()
	{
		try {
			$s = file_get_contents($this->stdout_path, FALSE, NULL, $this->stdout_pos);
		}
		catch(ErrorException $e){
			throw new IOException($e->getMessage());
		}
		$this->stdout_pos += strlen($s);
		return $s;
	}
	
	/**
	 * Tells if we are at the EOF of the stderr data coming from the process.
	 * @return boolean TRUE if no more data available; FALSE if more data available.
	 * @throws IOException
	 */
	function eofStderr()
	{
		try {
			return filesize($this->stderr_path) <= $this->stderr_pos;
		}
		catch(ErrorException $e){
			throw new IOException($e->getMessage());
		}
	}
	
	/**
	 * Read new data from stderr.
	 * @return string Latest available data from stderr, possibly the empty
	 * string if no data available.
	 * @throws IOException
	 */
	function readStderr()
	{
		try {
			$s = file_get_contents($this->stderr_path, FALSE, NULL, $this->stderr_pos);
		}
		catch(ErrorException $e){
			throw new IOException($e->getMessage());
		}
		$this->stderr_pos += strlen($s);
		return $s;
	}
	
	/**
	 * Returns a summary of the state of this object.
	 * @return string State of this object: if still open or terminated with
	 * some exit code; also stderr.
	 */
	function getStateDescr()
	{
		// Report process status:
		try {
			if( $this->isRunning() )
				$s = "running";
			else
				$s = "terminated with exit code " . $this->exit_code;
		}
		catch(IOException $e){
			$s = "unknown state: " . $e->getMessage();
		}
		// Report pending error messages:
		try {
			if( ! $this->eofStderr() )
				$s .= ", stderr: " . $this->readStderr();
		}
		catch(IOException $e){
			$s .= ", failed accessing stderr: " . $e->getMessage();
		}
		return $s;
	}
	
	
	/**
	 * Send data to the stdin of the process. This method may block forever if
	 * the process is blocked or it is not processing input anymore.
	 * @param string $bytes Data to send. Does nothing if NULL or empty string.
	 * @return void
	 * @throws LogicException Process already closed.
	 * @throws IOException Communication with the process failed or process died
	 * prematurely. After this exception, the client may close this object and
	 * retrieve the process exit code and process stderr to try diagnosing the
	 * issue.
	 */
	function writeBytes($bytes)
	{
		if( strlen($bytes) == 0 )
			return;
		if( $this->proc === NULL )
			throw new LogicException("process closed");
		do {
			try {
				$n = fwrite($this->proc, $bytes);
			}
			catch(ErrorException $e){
				throw new IOException("write failed; " . $this->getStateDescr());
			}
			if( ! is_int($n) )
				throw new IOException("write failed; process state: " . $this->getStateDescr());
			if( $n == 0 ){
				$this->close();
				throw new IOException("process died prematurely while still writing data; " . $this->getStateDescr());
			}
			if( $n <= 0 )
				throw new IOException("fwrite() returns unexpected value $n; process state: " . $this->getStateDescr());
			if( $n >= strlen($bytes) )
				break;
			$bytes = substr($bytes, $n);
		} while(TRUE);
	}
	
	/**
	 * Send a single byte to the stdin of the process. This method may block
	 * forever if the process is blocked or it is not processing input anymore.
	 * @param int $b Byte value in the range [0,255].
	 * @return void
	 * @throws InvalidArgumentException Byte value out of the range.
	 * @throws IOException Communication with the process failed or process died
	 * prematurely. After this exception, the client may close this object and
	 * retrieve the process exit code and process stderr to try diagnosing the
	 * issue.
	 */
	function writeByte($b)
	{
		if( !(0 <= $b && $b <= 255) )
			throw new InvalidArgumentException("b=$b");
		$this->writeBytes(chr($b));
	}
	
	/**
	 * Waits for process termination and closes the communication channel.
	 * Does nothing is already closed. May block forever if the process is
	 * blocked. Once closed, the exit status code of the process can be retrieved;
	 * the stdout and stderr pipes are still readable to retrieve the latest
	 * output from the process.
	 * @throws IOException Failed handling the process status or the shell controlling
	 * the process.
	 */
	function close()
	{
		parent::close();
		if( $this->proc === NULL )
			return;

		// Closing the shell and retrieving process outcome are two different
		// tasks; we must collect errors from both and report them all:
		$err_msg = "";

		// Closes stdin, wait shell termination and close shell:
		$err_code = pclose($this->proc);
		$this->proc = NULL;
		if( $err_code != 0 )
			$err_msg .= " pclose() returned $err_code";

		// Retrieve process exit status code:
		try {
			if( file_exists($this->exit_code_path) )
				$this->exit_code = (int) file_get_contents($this->exit_code_path);
			else {
				$this->exit_code = -2;
				$err_msg .= " Process exit code not available";
			}
		}
		catch(ErrorException $e){
			$this->exit_code = -1;
			$err_msg .= " Failed retrieving process exit status: " . $e->getMessage();
		}

		if( strlen($err_msg) > 0 )
			throw new IOException($err_msg . "; " . $this->getStateDescr());
	}
	
	/**
	 * @param string $path
	 */
	private static function deleteFile($path)
	{
		try {
			if( $path !== NULL )
				unlink($path);
		}
		catch(ErrorException $e){}
	}
	
	function __destruct()
	{
		try {
			$this->close();
		}
		catch(IOException $e){}
		
		self::deleteFile($this->script_path);
		self::deleteFile($this->stdout_path);
		self::deleteFile($this->stderr_path);
		self::deleteFile($this->exit_code_path);
	}
	
}
