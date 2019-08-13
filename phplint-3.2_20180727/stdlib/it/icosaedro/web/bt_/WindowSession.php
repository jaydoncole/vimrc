<?php

namespace it\icosaedro\web\bt_;

require_once __DIR__ . "/../../../../all.php";

/*.
	require_module 'core';
	require_module 'array';
	require_module 'file';
	require_module 'zlib';
.*/

use Exception;
use RuntimeException;
use ErrorException;
use it\icosaedro\utils\Random;
use it\icosaedro\web\Log;


/**
 * User's window session state. Each user may have one or more windows assigned,
 * each window with its own specific window parameters, bt file and bt stack.
 * The user's session directory contains one sub-directory for each window
 * session where the state of this object is saved to or restored from.
 * 
 * @author Umberto Salsi <salsi@icosaedro.it>
 * @copyright Copyright 2018 by icosaedro.it di Umberto Salsi
 * @version $Date: 2018/07/23 12:41:41 $
 */
class WindowSession {
	
	/**
	 * @access private
	 */
	const MAX_WINDOWS = 1000;
	
	/**
	 * Path of the session directory. The session directory contains one file
	 * for each user's parameter: the name of the file is the name of the parameter,
	 * and the content of the file is its value. This directory also contains
	 * one or more window session sub-directories.
	 * @var string
	 */
	private $session_dir;
	
	/**
	 * Ordinal number assigned to this window sub-directory.
	 * @var int
	 */
	public $window = 0;
	
	/**
	 * Path of the directory containing this window session. This directory
	 * contains the window's specific parameters and the calls stack.
	 * @var string
	 */
	public $window_dir;
	
	/**
	 * Open lock file inside the window session directory. Once this object is
	 * created and for the whole span of its existance, a lock on this file is
	 * acquired to prevent concurrent accesses to the same bt file and bt stack,
	 * possibly causing corruption or performing contracdictory things.
	 * This lock is released only when this object is destroyed, typically at the
	 * end of the HTTP request.
	 * @var resource
	 */
	private $window_session_lock;
	
	/**
	 * Path of the bt file, containing the forward calls related to all the
	 * anchors and buttons of the current user's window.
	 * @var string
	 */
	private $bt_file;
	
	/**
	 * Path of the bt stack, containing the backward calls.
	 * @var string
	 */
	private $bt_stack;
	
	/**
	 * Next forward call number in the bt file.
	 * @var int 
	 */
	private $bt_idx = 0;
	
	/**
	 * The currently open bt file.
	 * @var resource
	 */
	private $bt_fd = NULL;
	
	
	/**
	 * Returns the value serialized and encoded in a bare ASCII string without
	 * spaces. Used to encode the arguments of the call-forward and call-backward
	 * in the bt file and bt stack.
	 * @param mixed $x
	 * @return string
	 * @throws ErrorException
	 */
	private static function encode($x)
	{
		// Faster, readable and then easily debuggable but potentially large
		// result:
		//return rawurlencode( serialize($x) );
		
		// Slower, unreadable and then harder to debug, but space savvy:
		return base64_encode( gzcompress( serialize($x) ) );
	}
	
	
	/**
	 * Returns the value decoded and unserialized by the encode function.
	 * @param string $s
	 * @return mixed
	 * @throws ErrorException
	 */
	private static function decode($s)
	{
		//return unserialize( rawurldecode($s) );
		return unserialize( gzuncompress( base64_decode($s) ) );
	}


	/**
	 * Creates a new window session.
	 * @throws RuntimeException Too many window sessions open.
	 * @throws ErrorException Failed accessing the file system.
	 */
	private function createWindowSession()
	{
		$w = 1;
		while( file_exists( $this->session_dir . "window-$w" ) ){
			$w++;
			if( $w > self::MAX_WINDOWS )
				throw new RuntimeException("too many window sessions under " . $this->session_dir);
		}
		$this->window = $w;
		$this->window_dir = $this->session_dir . "window-$w/";
		mkdir( $this->window_dir, 0700 );
	}
	
	
	/**
	 * Resumes the status of the window session from the "w" parameter of the
	 * HTTP request. If that parameter is missing, creates a new window session
	 * instead.
	 * @throws RuntimeException Invalid "w" parameter in the HTTP request. Too
	 * many window sessions open.
	 * @throws ErrorException Failed accessing the file system.
	 */
	private function resumeWindowSession()
	{
		if( isset( $_REQUEST['w'] ) ){
			$this->window = (int) $_REQUEST['w'];
			if( !( 1 <= $this->window && $this->window <= self::MAX_WINDOWS ) )
				throw new RuntimeException("invalid window number parameter: w=" . $this->window);
			$this->window_dir = $this->session_dir . "window-" . $this->window. "/";
			if( ! file_exists( $this->window_dir ) )
				throw new RuntimeException("requested window session number does not exis: w=" . $this->window);
		} else {
			$this->createWindowSession();
		}
	}


	/**
	 * Initializes the window session.
	 * @param string $session_dir Path of the user's session directory. The path
	 * must end with a slash character '/'.
	 * @param boolean $new_window Whether a brand new window has to be created
	 * or the existing one has to be retrieved.
	 * @return void
	 * @throws RuntimeException Invalid "w" parameter in the HTTP request. Too
	 * many window sessions open.
	 * @throws ErrorException Failed accessing the file system.
	 * @throws WindowSessionConcurrentAccessException
	 */
	function __construct($session_dir, $new_window)
	{
		// Detect browser "back" starting from random number:
		$this->bt_idx = Random::getCommon()->randomInt(1, 9999);
		$this->session_dir = $session_dir;

		if( $new_window ){
			$this->createWindowSession();
		} else {
			$this->resumeWindowSession();
		}
		
		// Prevent concurrent access:
		$this->window_session_lock = fopen($this->window_dir . "window_session_lock", "wb");
		if( ! flock($this->window_session_lock, LOCK_EX | LOCK_NB) ){
			fclose($this->window_session_lock);
			$this->window_session_lock = NULL;
			throw new WindowSessionConcurrentAccessException("concurrent access to the window session directory");
		}
		
		$this->bt_file = $this->window_dir . "bt_file";
		$this->bt_stack = $this->window_dir . "bt_stack";

		# Update last access time.
		try {
			touch($this->session_dir);
		}
		catch(ErrorException $e){
			throw new RuntimeException($e->getMessage());
		}
	}


	/**
	 * Appends a new call-forward to the bt file.
	 * @param string $func Name of the call-forward.
	 * @param mixed[int] $args_ Arguments of the call-forward.
	 * @return int Number assigned to this call-forward in the bt file.
	 * @throws ErrorException Failed access to the file system.
	 */
	function appendCallForward($func, $args_)
	{
		if( $this->bt_fd === NULL )
			$this->bt_fd = fopen($this->bt_file, "w");
		fwrite($this->bt_fd, $this->bt_idx . " forw " . rawurlencode($func) );
		$n = count($args_);
		for( $i=0; $i<$n; $i++ ){
			fwrite($this->bt_fd, " ");
			fwrite($this->bt_fd, self::encode( $args_[$i] ));
		}
		fwrite($this->bt_fd, "\n");

		// Returns assigned "i" number, but also prepare next number:
		$i = $this->bt_idx++;
		return $i;
	}


	/**
	 * Sets the call-backward for the next call-forward. Invoke this method to
	 * prepare a return point before an anchor or button. This call-bacward will
	 * be put on top of the bt stack before invoking the call-forward it is
	 * related to.
	 * @param string $func Name of the call-backward function.
	 * @param mixed[int] $args_ Arguments of the call-backward.
	 * @return void
	 * @throws ErrorException
	 */
	function appendCallBackward($func, $args_)
	{
		if( $this->bt_fd === NULL )
			$this->bt_fd = fopen($this->bt_file, "w");
		fwrite($this->bt_fd, $this->bt_idx .' back ' . rawurlencode($func) );
		$n = count($args_);
		for( $i=1; $i<$n; $i++ ){
			fwrite($this->bt_fd, " ");
			fwrite($this->bt_fd, self::encode( $args_[$i] ) );
		}
		fwrite($this->bt_fd, "\n");
	}
	
	
	/**
	 * Resets the stack. The dashboard page of the web site may want to reset
	 * the stack to remove pending stale entries.
	 * @throws ErrorException
	 */
	function stackReset()
	{
		file_put_contents($this->bt_stack, "");
	}


	/**
	 * Appends a call-backward to the bt stack.
	 * @param string $func
	 * @param mixed[int] $args_
	 * @return void
	 * @throws ErrorException
	 */
	function stackPush($func, $args_)
	{
		$fd = fopen($this->bt_stack, "a");
		fwrite($fd, rawurlencode($func) );
		$n = count($args_);
		for( $i=0; $i<$n; $i++ ){
			fwrite($fd, " ");
			fwrite($fd, self::encode( $args_[$i] ) );
		}
		fwrite($fd, "\n");
		fclose($fd);
	}


	/**
	 * Returns the top of the stack.
	 * @return mixed[int] The first element is the name of the call-backward
	 * function, the remaining elements are its arguments.
	 * @throws ErrorException Failed accessing the file system.
	 * @throws RuntimeException The stack is empty.
	 */
	function stackPop()
	{
		$a = file($this->bt_stack);
		if( count($a) == 0 )
			throw new RuntimeException("bt stack is empty");
		$tos = trim( (string) array_pop($a), "\n");
		$fd = fopen($this->bt_stack, "w");
		foreach($a as $l)
			fwrite($fd, $l);
		fclose($fd);
		$a = explode(" ", $tos);
		/*. mixed[int] .*/ $r = array( rawurldecode($a[0]) );
		for( $i = 1; $i < count($a); $i++ )
			$r[$i] = self::decode($a[$i]);
		return $r;
	}


	/**
	 * Invokes a function.
	 * @param string $f Function to call.
	 * @param mixed[int] $arguments Arguments of the function.
	 * @return void
	 * @throws Exception
	 */
	private function call($f, $arguments)
	{
		if( Log::$logNotices )
			Log::notice("calling $f");

		/* $ignore =*/ call_user_func_array($f, $arguments);
	}
	
	
	function getCurrentCallForwardNumber()
	{
		return $this->bt_idx;
	}
	
	
	/**
	 * Invokes a call-forward from the bt file. If an associated call-backward
	 * does exist, put it on the stack first.
	 * @param int $i Number of the call-forward entry.
	 * @return boolean True if the call-forward has been detected and invoked.
	 * False if a call-forward with such a number does not exist.
	 * @throws Exception Failed accessing the file system. The invocation
	 * of the call forward caused an exception.
	 */
	function invokeCallForward($i)
	{
		$bt_file = file($this->bt_file);

		// Retrieve associated call-backward and put on the bt stack:
		$f = "";
		$target = "$i back ";
		foreach( $bt_file as $bt_file_line ){
			if( strlen($bt_file_line) >= strlen($target)
			&& substr_compare($target, $bt_file_line, 0, strlen($target)) == 0 ){
				$f = $bt_file_line;
				break;
			}
		}
		if( $f !== "" ){
			$fd = fopen($this->bt_stack, "a");
			fwrite($fd, substr($f, strlen($target)) );
			fclose($fd);
		}

		// Retrieve call-forward from the bt file:
		$f = "";
		$target = "$i forw ";
		foreach($bt_file as $bt_file_line){
			if( strlen($bt_file_line) >= strlen($target)
			and substr_compare($target, $bt_file_line, 0, strlen($target)) == 0 ){
				$f = $bt_file_line;
				break;
			}
		}
		if( $f === "" ){
			Log::warning("missing call-forward no. $i");
			return FALSE;
		}
		
		// Invoke the call-forward:
		$a = explode(" ", trim($f, "\n") );
		$func = rawurldecode( $a[2] );
		$arguments = /*. (array[int]mixed) .*/ array();
		for( $i = 3; $i < count($a); $i++ ){
			$arguments[$i-3] = self::decode($a[$i]);
		}
		$this->call($func, $arguments);
		return TRUE;
	}


	/**
	 * Invoke the function on the top of the bt stack. Further arguments can be
	 * added.
	 * @param mixed[int] $args_ Arguments to add to the call-backward retrieved
	 * from the stack.
	 * @return void
	 * @throws Exception Failed access to the file system. The invocation
	 * of the call forward caused an exception.
	 */
	function invokeCallBackward($args_)
	{
		$a = $this->stackPop();
		$f = (string) array_shift($a);
		for( $i=0; $i < count($args_); $i++ ){
			$a[] = $args_[$i];
		}
		$this->call($f, $a);
	}
	
	
	/**
	 * Releases any locked and open file in this window session, so allowing to
	 * safely delete the window session directory or even the whole user session.
	 * It is safe to call this method more more than once.
	 */
	function close()
	{
		try {
			if( $this->window_session_lock !== NULL ){
				fclose($this->window_session_lock);
				$this->window_session_lock = NULL;
			}

			if( $this->bt_fd !== NULL ){
				fclose($this->bt_fd);
				$this->bt_fd = NULL;
			}
		}
		catch(ErrorException $e){
			Log::error("failed closing window session: $e");
		}
	}
	
	
	function __destruct()
	{
		// Under Windows, locked files cannot be deleted, so stale sessions
		// cannot be removed. Workaround:
		$this->close();
	}

}