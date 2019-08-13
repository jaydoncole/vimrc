<?php

namespace it\icosaedro\web\bt_;

require_once __DIR__ . "/../../../../all.php";

/*. require_module 'pcre'; .*/

use Exception;
use RuntimeException;
use ErrorException;
use it\icosaedro\utils\SecureRandom;
use it\icosaedro\web\Http;
use it\icosaedro\web\Log;


/**
 * Implements the "web by functions" web programming paradigm, also known as bt_.
 * Basically, each anchor and each button of the page the user is currently
 * viewing can be associated to a specific function of the program along with
 * its arguments; by clicking that anchor or button, the user trigger the
 * corresponding call to that function, here names "call-forward.
 * Every anchor and every button get its unique number assigned (the "i"
 * parameter) and a file server side (name "bt file") keeps the binding between
 * that number and the corresponding call-forward.
 * 
 * <p>Note that the function names and their arguments are never send to the
 * remote client, so the application can trust on their values and no further
 * validation is needed on them; since these values never leave the server,
 * bandwidth is not affected by their size.
 * 
 * <p>A stack of function calls is also implemented through a file named "bt
 * stack". Each entry of the stack contains what is here named "call-backward"
 * function. The bt stack allows to implement reusable groups of web pages
 * just like subroutines of a programming language.
 * 
 * <p>Call-forward functions can be implemented as regular PHP functions;
 * static methods of class are even better because you may take advantage of the
 * class auto-loading mechanism and give a well structured shape to the source
 * tree of your application.
 * 
 * <p>Each logged-in user has its own session, represented by an instance of
 * this class, and one or more window sessions for each open window over the
 * same application (represented by the WindowSession class under this same
 * namespace). Each window session has its own bt file and bt stack.
 * 
 * <p>New user sessions can be created by generating a new object of this class
 * and setting the name of the user; this is normally performed after login.
 * Existing user sessions can be resumed by creating a new object and setting
 * NULL as user name; this is normally performed by some specific dispatcher
 * page that routes any following postback request.
 * 
 * <p>Example of a simple class that implements two pages; the user can swith from
 * one page to the other:
 * 
 * <pre>
 * use it\icosaedro\web\UserSession;
 * 
 * class HelloWorld {
 * 
 *	static function page1() {
 *		echo "&lt;html&gt;&lt;body&gt;";
 *		echo "&lt;h1&gt;Page 1&lt;/h1&gt;"
 *		UserSession::anchor("See page 2", "HelloWorld::page2");
 *		echo "&lt;/body&gt;&lt;/html&gt;";
 *	}
 * 
 *	static function page2() {
 *		echo "&lt;html&gt;&lt;body&gt;";
 *		echo "&lt;h1&gt;Page 2&lt;/h1&gt;"
 *		UserSession::anchor("See page 1", "HelloWorld::page1");
 *		echo "&lt;/body&gt;&lt;/html&gt;";
 *	}
 * 
 * }
 * </pre>
 * 
 * <p>Several sets of name/value pairs of parameters are also implemented:
 * 
 * <ul>
 * <li>Application parameters are permanently stored.</li>
 * <li>User's parameters keeps user's preferences and are permanently stored.</li>
 * <li>Session parameters and window parameters are associated to the corresponding
 * user's session and window's session and then deleted as the session ends.</li>
 * </ul>
 * 
 * <p>Normally a complete web application needs only two pages: the login page
 * and the dispacth page. The login page performs user's authetication and
 * then creates an instance of this class with its name. All the user's
 * requests from here on are then sent to the dispatcher page, which in turn
 * creates an instance of this class without the user's name; the constructor
 * takes care to resume the user's session, the window session, and finally to
 * call the appropriate call-forward function.
 * 
 * <p>See also: tutorial about bt_ is available at
 * {@link http://www.icosaedro.it/phplint/web}.
 * 
 * @author Umberto Salsi <salsi@icosaedro.it>
 * @copyright Copyright 2018 by icosaedro.it di Umberto Salsi
 * @version $Date: 2018/06/06 09:58:46 $
 */
class UserSession {
	
	/**
	 * Name of the session cookie.
	 */
	const COOKIE_NAME = "BTSESSION";
	
	/**
	 * Name of the window session property where the number of the default
	 * forward call is stored. The default forward call is invoked when the
	 * "i" parameter from the request is invalid.
	 * @access private
	 */
	const DEFAULT_CALL_FORWARD = "bt_default_call_forward";
	
	/**
	 * Value of the session cookie.
	 * @var string
	 */
	private $cookie_value;
	
	/**
	 * Session cookie duration (s). Zero means the browser will delete the cookie
	 * exiting the browser program. If an application preference is available,
	 * its value is set instead.
	 * @var int
	 */
	private $session_duration = 0;
	
	/**
	 * URL of the page performing the dispatching of the client requests.
	 * Anchors and form actions will use this URL. The dispacher page should
	 * simply instantiate this object, wich in turn will renew the session
	 * cookie and will call the requested call-forward function.
	 * @var string
	 */
	private $dispatcher_page;
	
	/**
	 * URL of the login page. It can also be the bare path of the resource, for
	 * example "/login.php".
	 * @var string
	 */
	private $login_page;
	
	/**
	 * URL of the user's dashboard function. It can also be the bare path of the
	 * resource, for example "/dashboard.php".
	 * @var string
	 */
	private $dashboard_function;
	
	/**
	 * Directory where application preferences are stored. The string ends
	 * with a slash character '/'.
	 * @var string
	 */
	private $application_dir;
	
	/**
	 * Directory where users' preferences are stored. The string ends with a
	 * slash character '/'.
	 * @var string
	 */
	private $users_dir;
	
	/**
	 * Directory where users' sessions are stored. The string ends with a
	 * slash character '/'.
	 * @var string
	 */
	private $users_sessions_dir;
	
	/**
	 * Directory where the user's specific session is stored. The string ends
	 * with a slash character '/'.
	 * @var string
	 */
	private $user_session_dir;
	
	/**
	 * Cache of session's parameters. The key is the name of the parameter; the
	 * value is its value.
	 * @var string[string]
	 */
	private $session_parameters_cache;
	
	/**
	 * Current window session.
	 * @var WindowSession
	 */
	private $window_session;
	
	/**
	 * Whether a for entity is currently open.
	 * @var boolean
	 */
	private $form_open = FALSE;
	
	/**
	 * Singleton instance of this class, so that handy static functions can work.
	 * The constructor saves here the instance of itself.
	 * @var self
	 */
	private static $us;
	

	/**
	 * Saves an application parameter.
	 * @param string $name Name of the parameter.
	 * @param string $value Value of the parameter.
	 * @return void
	 * @throws RuntimeException Failed accessing the file system.
	 */
	static function setApplicationParameter($name, $value)
	{
		try {
			if( ! file_exists( self::$us->application_dir ) )
				mkdir(self::$us->application_dir, 0700);
			file_put_contents(self::$us->application_dir . $name, $value);
		}
		catch(ErrorException $e){
			throw new RuntimeException($e->getMessage());
		}
	}


	/**
	 * Retrieves an application parameter. If the parameter is missing,
	 * the default value is returned instead, otherwide it is an exception.
	 * @param string $name Name of the parameter.
	 * @param string $def Default value returned if the parameter is missing.
	 * @return string Value of the parameter.
	 * @throws RuntimeException Parameter is missing and no default value set.
	 * Failed accessing file.
	 */
	static function getApplicationParameter($name, $def = NULL)
	{
		$fn = self::$us->application_dir . $name;
		if( file_exists($fn) ){
			try {
				return file_get_contents($fn);
			}
			catch(ErrorException $e){
				throw new RuntimeException($e->getMessage());
			}
		} else if(func_num_args() == 2 ){
			return $def;
		} else {
			throw new RuntimeException("missing application parameter: $name");
		}
	}


	/**
	 * Retrieves a parameter from the user's session. If the parameter is missing,
	 * the default value is returned instead, otherwide it is an exception.
	 * @param string $name Name of the parameter.
	 * @param string $def Default value returned if the parameter is missing.
	 * @return string Value of the parameter.
	 * @throws RuntimeException Parameter is missing and no default value set.
	 * Failed accessing file.
	 */
	static function getSessionParameter($name, $def = NULL)
	{
		if( isset( self::$us->session_parameters_cache["$name"] ) )
			return self::$us->session_parameters_cache["$name"];

		$fn = self::$us->user_session_dir . "/$name";
		if( file_exists($fn) ){
			try {
				$value = file_get_contents($fn);
			}
			catch(ErrorException $e){
				throw new RuntimeException($e->getMessage());
			}
		} else if(func_num_args() == 2 ){
			$value = $def;
		} else {
			throw new RuntimeException("user session parameter is missing: $name");
		}
		self::$us->session_parameters_cache["$name"] = $value;
		return $value;
	}


	/**
	 * Saves an user's session parameter.
	 * @param string $name Name of the parameter.
	 * @param string $value Value of the parameter.
	 * @return void
	 * @throws RuntimeException Failed accessing the file system.
	 */
	static function setSessionParameter($name, $value)
	{
		try {
			file_put_contents(self::$us->user_session_dir . "/$name", $value);
		}
		catch(ErrorException $e){
			throw new RuntimeException($e->getMessage());
		}
		self::$us->session_parameters_cache["$name"] = $value;
	}
	
	
	/**
	 * Resets the stack. The dashboard page of the web site may want to reset
	 * the stack to remove pending stale entries. See also comments to the
	 * {@link ::setDefaultCallForward()} method.
	 */
	static function stackReset()
	{
		try {
			self::$us->window_session->stackReset();
		}
		catch(ErrorException $e){
			throw new RuntimeException($e->getMessage(), $e->getCode(), $e);
		}
	}


	/**
	 * Appends a call-backward to the bt stack of the current window session.
	 * @param string $func
	 * @param mixed[int] $args_
	 * @return void
	 */
	static function stackPush($func, $args_)
	{
		$a = func_get_args();
		array_shift($a);
		try {
			self::$us->window_session->stackPush($func, $args_);
		}
		catch(ErrorException $e){
			throw new RuntimeException($e->getMessage(), $e->getCode(), $e);
		}
	}


	/**
	 * Returns the top of the stack for the current window session.
	 * @return mixed[int] The first element is the name of the call-backward
	 * function, the remaining elements are its arguments.
	 * @throws ErrorException Failed accessing the file system.
	 * @throws RuntimeException The stack is empty.
	 */
	static function stackPop()
	{
		return self::$us->window_session->stackPop();
	}


	/**
	 * Invoke the function on the top of the bt stack. Further arguments can be
	 * added.
	 * @return void
	 */
	static function invokeCallBackward(/*.args.*/)
	{
		try {
			self::$us->window_session->invokeCallBackward( func_get_args() );
		}
		catch(Exception $e){
			throw new RuntimeException($e->getMessage(), 1, $e);
		}
	}


	/**
	 * Appends a new call-forward to the bt file and returns its URL.
	 * @param string $func Name of the call-forward.
	 * @param mixed[int] $args_ Arguments of the function.
	 * @return string URL to the dispatcher page that invokes this call-forward.
	 * @throws ErrorException
	 */
	static function link($func, $args_)
	{
		return self::$us->dispatcher_page
		. '?w=' . self::$us->window_session->window
		. '&i=' . self::$us->window_session->appendCallForward($func, $args_);
	}
	
	
	/** @var string */
	private static $add_attributes;
	
	/**
	 * Set further attributes for the next anchor, form or button HTML element.
	 * Once used, the value will be reset to the empty string.
	 * @param string $value Verbatim string to add to the element, for example:
	 * "id='save_button' class='my_button_style'".
	 */
	static function addAttributes($value)
	{
		self::$add_attributes = $value;
	}
	
	
	/**
	 * Anchor inside form clicks on this invisible button ID.
	 * @var int
	 */
	private static $postback_button_id = 0;


	/**
	 * Sends to standard output an HTML anchor entity which, if clicked by user,
	 * triggers the invokation of the specified call-forward function.
	 * Inside a currenlty open form, performs a postback of the form data
	 * (JavaScript is required in order for this to work, though).
	 * @param string $text_html HTML text the user will read.
	 * @param string $func Function to call if this anchor is clicked; arguments
	 * may follow.
	 * @return void
	 * @throws RuntimeException
	 */
	static function anchor($text_html, $func /*., args .*/)
	{
		try {
			$a = func_get_args();
			if( ! self::$us->form_open ){
				// Not inside a form. Put a regular anchor.
				array_shift($a); // skip $text_html
				array_shift($a); // skip $func
				$href = self::$us->dispatcher_page
				. '?w=' . self::$us->window_session->window
				. '&i=' . self::$us->window_session->appendCallForward($func, $a);
				echo "<a href='", htmlspecialchars($href), "' ",
					self::$add_attributes, ">$text_html</a>";
			} else {
				// Inside form. Trick: put invisible button that actually performs
				// POST and then an anchor that clicks that button.
				$saved_add_attributes = self::$add_attributes;
				$button_id = "bt_anchor_click_" . self::$postback_button_id++;
				self::$add_attributes = " id=$button_id style='display: none;'";
				$a[0] = "";
				call_user_func_array(self::class . "::button", $a);
				echo "<a href='#' ", $saved_add_attributes,
					" onclick=\"document.getElementById('$button_id').click();\">$text_html</a>";
			}
			self::$add_attributes = NULL;
		} catch (Exception $e) {
			throw new RuntimeException($e->getMessage(), 1, $e);
		}
	}


	/**
	 * Sends to standard output the HTML form opening entity.
	 * @return void
	 * @throws RuntimeException Form already open.
	 */
	static function formOpen()
	{
		if( self::$us->form_open )
			throw new RuntimeException("form already open");
		self::$us->form_open = TRUE;
		echo "<form enctype='multipart/form-data' method=post ",
			"action='", htmlspecialchars(self::$us->dispatcher_page), "' ",
			self::$add_attributes, ">";
		self::$add_attributes = NULL;
		echo "<input type=hidden name=w value=", self::$us->window_session->window, ">";
	}
	
	
	/**
	 * Sends to standard output the HTML form closing entity.
	 * @return void
	 * @throws RuntimeException No currently open form.
	 */
	static function formClose()
	{
		if( ! self::$us->form_open )
			throw new RuntimeException("no currently open form");
		self::$us->form_open = FALSE;
		echo "</form>";
	}


	/**
	 * Sends to standard output an HTML button entity which, if clicked by user,
	 * triggers the invokation of a call-forward function.
	 * @param string $text Text the user will read inside the button.
	 * @param string $func Function to call if this button is clicked. Function
	 * arguments may follow.
	 * @return void
	 * @throws RuntimeException No currently open form. Failed accessing the
	 * file system.
	 */
	static function button($text, $func /*., args .*/)
	{
		if( ! self::$us->form_open )
			throw new RuntimeException("no currently open form");
		$a = func_get_args();
		array_shift($a); // skip $text
		array_shift($a); // skip $func
		try {
			$i = self::$us->window_session->appendCallForward($func, $a);
		}
		catch(ErrorException $e){
			throw new RuntimeException($e->getMessage(), 1, $e);
		}
		echo "<input type=submit name=bt_button_$i value='",
			htmlspecialchars($text), "' ", self::$add_attributes, ">";
		self::$add_attributes = NULL;
	}


	/**
	 * Sets the call-backward for the next call-forward. Invoke this method to
	 * prepare a return point <u>after</u> an anchor or button.
	 * This call-backward will be put on top of the bt stack before invoking the
	 * call-forward it is associated to.
	 * @param string $func Name of the call-backward function; arguments may
	 * follow.
	 * @return void
	 */
	static function setCallBackward($func /*., args .*/)
	{
		$a = func_get_args();
		array_shift($a);
		try {
			self::$us->window_session->appendCallBackward($func, $a);
		}
		catch(ErrorException $e){
			throw new RuntimeException($e->getMessage(), $e->getCode(), $e);
		}
	}


	/**
	 * Saves an user's preference parameter. These parameters are permanently
	 * stored on the file system and are shared among all the session of the
	 * current user.
	 * @param string $name Name of the parameter.
	 * @param string $value Value of the parameter.
	 * @return void
	 * @throws RuntimeException Failed accessing the file system.
	 */
	static function setUserPreferenceParameter($name, $value)
	{
		try {
			$dir = self::$us->users_dir;
			if( ! file_exists( $dir ) )  mkdir($dir, 0700);

			$dir = $dir . self::getSessionParameter('name');
			if( ! file_exists( $dir ) )  mkdir($dir, 0700);

			$dir = $dir . "/prefs";
			if( ! file_exists( $dir ) )  mkdir($dir, 0700);

			file_put_contents("$dir/$name", $value);
		}
		catch(ErrorException $e){
			throw new RuntimeException($e->getMessage());
		}
	}


	/**
	 * Retrieves an user's preference parameter. If the parameter is missing,
	 * the default value is returned instead, otherwide it is an exception.
	 * @param string $name Name of the parameter.
	 * @param string $def Default value returned if the parameter is missing.
	 * @return string Value of the parameter.
	 * @throws RuntimeException Parameter is missing and no default value set.
	 * Failed accessing file.
	 */
	static function getUserPreferenceParameter($name, $def = NULL)
	{
		$fn = self::$us->users_dir . self::getSessionParameter('name') . "/prefs/$name";
		if( file_exists($fn) ){
			try {
				return file_get_contents($fn);
			}
			catch(ErrorException $e){
				throw new RuntimeException($e->getMessage());
			}
		} else if(func_num_args() == 2 ){
			return $def;
		} else {
			throw new RuntimeException("missing user preference parameter: $name");
		}
	}


	/**
	 * Saves a window's session parameter.
	 * @param string $name Name of the parameter.
	 * @param string $value Value of the parameter.
	 * @return void
	 * @throws RuntimeException Failed accessing the file system.
	 */
	static function setWindowParameter($name, $value)
	{
		$fn = self::$us->window_session->window_dir . $name;
		try {
			file_put_contents($fn, $value);
		}
		catch(ErrorException $e){
			throw new RuntimeException($e->getMessage());
		}
	}


	/**
	 * Retrieves a parameter from the window's session. If the parameter is missing,
	 * the default value is returned instead, otherwide it is an exception.
	 * @param string $name Name of the parameter.
	 * @param string $def Default value returned if the parameter is missing.
	 * @return string Value of the parameter.
	 * @throws RuntimeException Parameter is missing and no default value set.
	 * Failed accessing file.
	 */
	static function getWindowParameter($name, $def = NULL)
	{
		$fn = self::$us->window_session->window_dir . $name;
		if( file_exists($fn) ){
			try {
				return file_get_contents($fn);
			}
			catch(ErrorException $e){
				throw new RuntimeException($e->getMessage());
			}
		} else if(func_num_args() == 2 ){
			return $def;
		} else {
			throw new RuntimeException("missing window's parameter: $name");
		}
	}
	
	
	/**
	 * Marks the next forward call as the default forward call of the bt file to
	 * invoke if the "i" parameter is invalid. If the user retrieves pages from
	 * the browser history trying to subvert the navigation path set by the
	 * application, this default call forward should represent the safer choice
	 * in the current form, typically a button like Cancel or Dismiss or OK.
	 * <br>BEWARE: do not set as default forward call a function that retrieves
	 * form data because the request may contain arbitrary fields from expired
	 * pages.
	 */
	static function setDefaultCallForward()
	{
		self::setWindowParameter(self::DEFAULT_CALL_FORWARD, "".self::$us->window_session->getCurrentCallForwardNumber());
	}
	
	
	private function setCookie()
	{
		if( $this->session_duration == 0 )
			$max_age = 86400;
		else
			$max_age = $this->session_duration;
		setcookie(self::COOKIE_NAME, $this->cookie_value, time() + $max_age);
		$_COOKIE[self::COOKIE_NAME] = $this->cookie_value;
	}
	
	
	/**
	 * Checks if the string is a formally acceptable session cookie value.
	 * Acceptable values can include only digits and lower-case letters and
	 * must be from 32 up to 50 characters long. Note that only lower-case
	 * letters are allowed to cope with OSs whose file system is case-insensitive
	 * (Windows).
	 * @param string $x
	 * @return boolean True if the string is formally acceptable.
	 */
	private function isValidSessionValueFormat($x)
	{
		return preg_match("/^[0-9a-f]{10,100}\$/sD", $x) === 1;
	}


	private function isSessionAvailable()
	{
		if( ! isset( $_COOKIE[self::COOKIE_NAME] ) )
			return FALSE;

		$cookie_value = (string) $_COOKIE[self::COOKIE_NAME];

		if( ! $this->isValidSessionValueFormat($cookie_value) )
			return FALSE;
		
		$user_session_dir = $this->users_sessions_dir . $cookie_value . "/";
		if( ! file_exists($user_session_dir) )
			return FALSE;
		
		$this->cookie_value = $cookie_value;
		$this->user_session_dir = $user_session_dir;
		$this->setCookie();

		return TRUE;
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
			throw new ErrorException("directory deletion not implemented on this OS: " . PHP_OS);
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
	 * Delete stale sessions directories. If the session duration is zero, 24h
	 * of inactivity is assumed.
	 * @throws ErrorException
	 */
	private function staleSessionsCleanup()
	{
		$lock_path = $this->users_sessions_dir . "sessions_cleanup_lock";
		$lock = fopen($lock_path, "wb");
		if( ! flock($lock, LOCK_EX | LOCK_NB) ){
			// Some other instance already performing cleanup.
			fclose($lock);
			return;
		}
		
		if( $this->session_duration == 0 )
			$max_age = 86400;
		else
			$max_age = $this->session_duration;
		
		$d = dir($this->users_sessions_dir);
		$now = time();
		do {
			$entry = $d->read();
			if ($entry === FALSE)
				break;
			if( $this->isValidSessionValueFormat($entry) ) {
				$session_dir = $this->users_sessions_dir . $entry . "/";
				if( ! is_dir($session_dir) )
					continue;
				$last_access_time_file = $session_dir . "last_access_time";
				if( file_exists($last_access_time_file) ){
					$last_access_time = (int) file_get_contents($last_access_time_file);
					if( $now - $last_access_time < $max_age )
						continue;
				}
				$this->deleteDirectory($session_dir);
			}
		} while (TRUE);
		
		fclose($lock);
	}


	/**
	 * Creates a new user's session. The following user's session parameters are
	 * also set:
	 * "name": name of the user;
	 * "time_login": current timestamp (seconds since Unix epoch);
	 * "last_access_time": current timestamp (will be updated after any access
	 * to this user session);
	 * "remote_address": address of the client host.
	 * Also checks and deletes stale sessions.
	 * @param string $name User's name.
	 * @return void
	 * @throws ErrorException
	 */
	private function login($name)
	{
		try {
			$this->staleSessionsCleanup();
		}
		catch(ErrorException $e){
			Log::error("stale sessions cleanup failed: $e");
		}

		Log::notice("LOGIN user $name");
		
		// Create and send cookie:
		$this->cookie_value = bin2hex( SecureRandom::randomBytes(16) );
		$this->user_session_dir = $this->users_sessions_dir . $this->cookie_value . "/";
		$this->setCookie();

		// Create session directory:
		mkdir(self::$us->user_session_dir, 0700);
		
		// Save some specific user's session parameters:
		self::setSessionParameter("name", $name);
		self::setSessionParameter("time_login", (string) time());
		self::setSessionParameter("last_access_time", (string) time());
		self::setSessionParameter("remote_address", $_SERVER["REMOTE_ADDR"]);
	}


	/**
	 * Delete user's session.
	 * @return void
	 */
	static function logout()
	{
		if( self::$us === NULL )
			return;
		
		self::$us->window_session->close();

		# Delete user's session directory:
		try {
			self::$us->deleteDirectory(self::$us->user_session_dir);
		}
		catch(ErrorException $e){
			Log::error("failed to delete session directory on logout: $e");
		}

		# Delete cookie:
		unset( $_COOKIE['session'] );
		setcookie(self::COOKIE_NAME, "0", time() - 365*24*60*60);
		header("Location: " . self::$us->login_page);
		
		self::$us = NULL;
	}
	
	
	/**
	 * Manages a GET or POST request from the client and invokes the corresponding
	 * call-forward function. If a call-backward is associated, put it on the stack.
	 * @return boolean True if the request contains a valid forward call number,
	 * false if invalid number or no number at all.
	 * @throws UserSessionInvalidHttpMethodException
	 * @throws Exception
	 */
	private function postback()
	{
		// Retrieve call-forward index from the "i" parameter:
		$method = $_SERVER['REQUEST_METHOD'];
		switch( $method ){

		case 'GET':
			if( ! isset($_GET['i']) )
				return FALSE;
			$i = (int) $_GET["i"];
			break;

		case 'POST':
			// Search button named "bt_button_N" where N is the number of
			// the call-forward function in the bt file:
			$i = -1;
			foreach($_POST as $k => $v){
				if( strlen($k) > 10 && substr_compare($k, "bt_button_", 0, 10) == 0 ){
					$i = (int) substr($k, 10);
					break;
				}
			}
			if( $i == -1 )
				return FALSE;
			break;

		default:
			throw new UserSessionInvalidHttpMethodException("unexpected method '$method'");
		}
		
		return self::$us->window_session->invokeCallForward($i);
	}
	
	
	/**
	 * Initializes the user's session.
	 * @param string $login_name Name of the user just logged-in. If NULL or empty,
	 * tries to resume the user's session based on the current session cookie.
	 * If no valid session is available, redirects to the login page.
	 * @param string $base_dir BT_ working directory, where users' sessions,
	 * users' parameters and application parameters are stored.
	 * @param string $dispatcher_page URL of the dispatcher page. It can also
	 * be the bare path of the resource, for example "/dispatcher.php".
	 * @param string $login_page URL of the login page. It can also be the bare
	 * path of the resource, for example "/login.php".
	 * @param string $dashboard_function User's dashboard. It can also be the bare
	 * path of the resource, for example "/dashboard.php".
	 * @throws UserSessionInvalidHttpMethodException Unsupported HTTP method;
	 * expected either "GET" or "POST".
	 * @throws Exception
	 */
	function __construct($login_name, $base_dir, $dispatcher_page, $login_page, $dashboard_function)
	{
		$method = $_SERVER['REQUEST_METHOD'];
		if( !($method === "GET" || $method === "POST") )
			throw new UserSessionInvalidHttpMethodException("unsupported HTTP method: $method");
		
		// Check and normalize $base_dir:
		$normalized = realpath($base_dir);
		if( $normalized === FALSE )
			throw new RuntimeException("the directory does not exist: $base_dir");
		$base_dir = "$normalized/";
		
		$this->dispatcher_page = $dispatcher_page;
		$this->login_page = $login_page;
		$this->dashboard_function = $dashboard_function;
		
		// Create the application directory:
		$this->application_dir = $base_dir . "application/";
		if( !file_exists($this->application_dir) )
			mkdir($this->application_dir);
		
		// Create users' directory:
		$this->users_dir = $base_dir . "users/";
		if( !file_exists($this->users_dir) )
			mkdir($this->users_dir);
		
		// Create users' sessions directory:
		$this->users_sessions_dir = $base_dir . "sessions/";
		if( ! file_exists($this->users_sessions_dir) )
			mkdir($this->users_sessions_dir);
		
		// Initialize session parameters cache:
		$this->session_parameters_cache = array();
		
		// Make this object available by handy static methods of this class:
		self::$us = $this;
		
		$this->session_duration = (int) self::getApplicationParameter("session_duration", "0");
		
		Http::headerCacheControlNoStore();

		if( strlen($login_name) > 0 ){
			// Performs login.
			$this->login($login_name);
			self::setSessionParameter("last_access_time", (string) time());
			try {
				self::$us->window_session = new WindowSession($this->user_session_dir, TRUE);
			}
			catch(WindowSessionConcurrentAccessException $e){
				// Should never happen; anyway:
				throw new RuntimeException($e->getMessage(), 1, $e);
			}
			call_user_func($this->dashboard_function);
			
		} else if( $this->isSessionAvailable() ){
			// Postback.
			self::setSessionParameter("last_access_time", (string) time());
			try {
				// Retrieve window session from the "w" parameter:
				self::$us->window_session = new WindowSession($this->user_session_dir, FALSE);
				$success = $this->postback();
				if( ! $success ){
					// "i" parameter missing or invalid (user "back"?).
					// Try default forward call.
					$i = (int) self::getWindowParameter(self::DEFAULT_CALL_FORWARD, "-1");
					if( $i >= 0 ){
						Log::warning("default forward call no. $i");
						$success = $this->window_session->invokeCallForward($i);
					}
				}
			} catch(WindowSessionConcurrentAccessException $e){
				Http::headerStatus(Http::STATUS_SERVICE_UNAVAILABLE, "Bt_ still busy by previous request");
				header("Retry-After: 2");
				header("Content-Type: text/plain");
				echo "Server still busy replying to your previous request, please wait a few seconds and then try reloading the page.";
				$success = TRUE; // do not invoke anything!
			}
			if( ! $success ){
				call_user_func($this->dashboard_function);
			}
			
		} else {
			// No session available.
			header("Location: " . $this->login_page);
		}
	}

}
