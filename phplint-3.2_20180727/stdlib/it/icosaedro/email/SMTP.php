<?php

namespace it\icosaedro\email;
/*.
	require_module 'standard';
	require_module 'streams';
	require_module 'sockets';
	require_module 'openssl';
	require_module 'pcre';
	require_module 'hash';
.*/

require_once __DIR__ . "/../../../autoload.php";
require_once __DIR__ . "/../../../errors.php";

use ErrorException;
use it\icosaedro\io\IOException;
use InvalidArgumentException;

/**
 * Allows to connect and communicate with any SMTP server. It implements
 * all the SMTP functions defined in RFC 821 except SEND, SAML, SOML, TURN.
 * 
 * <p>E-mail addresses are sent verbatim, but UTF-8 encoded strings are assumed;
 * whenever the server announces being SMTPUTF8 capable (RFC 6531), the SMTPUTF8
 * option is also sent along with the MAIL command to explicity request support
 * for UTF-8 encoded addresses. We expect that non UTF8 capable SMTP server may
 * either reject non-ASCII addresses, or may blindly accept them at least for the
 * user name part; most Linux and Unix server simply try matching bytes against
 * local user names, which are usually UTF-8 encoded anyway.
 * 
 * <p>References:
 * <br>- SMTP, RFC 821 (obsolete), RFC 2821 (obsolete), RFC 5321.
 * <br>- Internationalized e-mail addresses, RFC 6530, RFC 6531, RFC 6532.
 * 
 * @version $Date: 2018/07/16 07:35:00 $
 * @author Umberto Salsi
 */
class SMTP
{
	/**
	 * Enable debugging messages by sending to stderr all the client/server
	 * dialogue.
	 * @var bool
	 */
	public static $debug = FALSE;
	
	/**
	 * Last SMTP status code retrieved from server as set by get_lines().
	 * @var int
	 */
	public $code = 0;

	/**
	 * Socket connection with the SMTP server.
	 * @var resource
	 */
	private $conn;
	
	/**
	 * If server supports SMTPUTF8 option to MAIL cmd (RFC 6531).
	 * @var boolean
	 */
	private $support_smtputf8 = FALSE;

	/* **********************************************************
						  CONNECTION FUNCTIONS					*
	  **********************************************************/

	/**
	 * Closes the socket and cleans up the state of the class. It is not
	 * considered good to use this function without first trying to use QUIT.
	 * @return void
	 * @throws IOException
	 */
	function close()
	{
		if( $this->conn !== NULL ) {
			$conn = $this->conn;
			$this->conn = NULL;
			try {
				fclose($conn);
			}
			catch(ErrorException $e){
				throw new IOException($e->getMessage());
			}
		}
	}

	/**
	 * @param string $line
	 * @throws IOException
	 */
	private function put_line($line)
	{
		$line .= "\r\n";
		if(self::$debug)
			error_log("SMTP Client: $line");
		do {
			try {
				$written = fwrite($this->conn, $line);
			}
			catch(ErrorException $e){
				throw new IOException($e->getMessage());
			}
			if( $written === FALSE ){
				$info = socket_get_status($this->conn);
				if( $info['timed_out'] === TRUE )
					throw new IOException("time-out writing data");
				else if( $info["eof"] === TRUE )
					throw new IOException("server side close");
				else
					throw new IOException("unexpected FALSE returned by fwrite()");
			}
			if( $written >= strlen($line) )
				return;
			$line = substr($line, $written);
		}while(TRUE);
	}

	
	/**
	 * Read in as many lines as possible from the SMTP server until either the
	 * last line of the reply or the time-out. Also sets the status code property.
	 * @return string Raw reply from the server.
	 * @throws IOException Error reading. Time-out waiting for data. Invalid
	 * syntax of the reply.
	 */
	private function get_lines()
	{
		$rply = "";
		$this->code = 0;
		while(TRUE) {
			try {
				$line = fgets($this->conn);
			}
			catch(ErrorException $e){
				throw new IOException($e->getMessage());
			}
			if( $line === FALSE ){
				$info = socket_get_status($this->conn);
				if( $info['timed_out'] === TRUE )
					throw new IOException("time-out waiting for data");
				else if( $info["eof"] === TRUE )
					throw new IOException("server side close");
				else
					throw new IOException("unexpected FALSE returned by fgets()");
			}
			if( self::$debug )
				error_log("SMTP Server: $line\n");
			$rply .= $line;
			if( preg_match("/^[0-9]{3}[- ]/", $line) === 1 ){
				$this->code = (int) substr($line, 0, 3);
				if( substr($line, 3, 1) === " " )
					break;
			} else {
				$this->code = 0;
				throw new IOException("invalid line syntax: $line");
			}
		}
		return $rply;
	}
	
	
	/**
	 * Send a single line command and wait for the expected status code.
	 * @param string $descr Prose describing the command.
	 * @param string $cmd SMTP command.
	 * @param int $exp_status Expected reply status code.
	 * @return string Reply from the server.
	 * @throws IOException Failed sending the command. Time-out waiting for
	 * the reply. Unexpected reply or invalid status code.
	 */
	private function cmd($descr, $cmd, $exp_status)
	{
		$this->put_line($cmd);
		$rply = $this->get_lines();
		if( $this->code != $exp_status )
			throw new IOException("$descr: $rply");
		return $rply;
	}

	
	/**
	 * Sends the HELO command to the SMTP server. This makes sure that we and
	 * the server are in the same known state.
	 * @param string $host
	 * @return string Reply from server.
	 * @throws IOException
	 */
	private function hello($host)
	{
		try {
			return $this->cmd("EHLO command", "EHLO $host", 250);
		}
		catch ( IOException $e ) {
			return $this->cmd("HELO command", "HELO $host", 250);
		}
	}

	
	/**
	 * Performs SMTP user's authentication.
	 * @param string $method User's authentication method: "PLAIN", "LOGIN",
	 * "CRAM-MD5".
	 * @param string $username
	 * @param string $password
	 * @return void
	 * @throws IOException
	 */
	private function authenticate($method, $username, $password)
	{
		switch($method){
			
		case "PLAIN":
			$this->cmd("PLAIN authentication",
				"AUTH PLAIN " . base64_encode("\000$username\000$password"), 235);
			break;
			
		case "LOGIN":
			$this->cmd("LOGIN authentication", "AUTH LOGIN", 334);
			$this->cmd("LOGIN authentication", base64_encode($username), 334);
			$this->cmd("LOGIN authentication", base64_encode($password), 235);
			break;
		
		case "CRAM-MD5":
			$rply = $this->cmd("CRAM-MD5 authentication", "AUTH CRAM-MD5", 334);
			$challenge = base64_decode(substr($rply, 4));
			$response = $username . " " . hash_hmac("md5", $challenge, $password);
			$this->cmd("CRAM-MD5 authentication", base64_encode($response), 235);
			break;
		
		default:
			throw new InvalidArgumentException("unknown/unsupported user's authentication method: $method");
		}
	}

	
	/**
	 * Connect to the server specified and performs encryption, client and server
	 * authentication, and user's authentication.
	 * @param ConnectionParameters $hp Connection parameters.
	 * @return void
	 * @throws IOException TCP connection failed.
	 * Time-out waiting for the connection. Failed reading the certificate
	 * files. Secure authentication of the server failed. "Hello" client name
	 * rejected. User authentication rejected.
	 */
	function __construct($hp)
	{
		// Parse connection data:
		$timeout = (int) $hp->params["timeout"];
		$security = $hp->params["security"];
		switch($security){
			
			case "plaintext":
				$method = 0; // ignored
				$wrapper = "plaintext";
				break;
			
			case "ssl":
				$method = STREAM_CRYPTO_METHOD_ANY_CLIENT;
				$wrapper = "ssl";
				break;
			
			case "tls":
				$method = STREAM_CRYPTO_METHOD_ANY_CLIENT;
				$wrapper = "ssl";
				break;
			
			default:
				throw new InvalidArgumentException("$hp: unknown security parameter: $security");
		}
		
		// Establishing TCP channel:
		try {
			$socket = fsockopen($hp->server_hostname, $hp->server_port,
				 $errno, $errstr,
				 $timeout);
		}
		catch(ErrorException $e){
			throw new IOException($e->getMessage());
		}
		if( $socket === FALSE )
			throw new IOException("$hp: $errstr ($errno)");
		stream_set_blocking($socket, TRUE);
		stream_set_timeout($socket, $timeout);
		$this->conn = $socket;
		
		// Begins STARTTLS negotiation:
		if( $security === "tls" ){
			$announcement = $this->get_lines();
			if( $this->code != 220 )
				throw new IOException("unexpected announcement: $announcement");
			
			$this->hello($hp->params["client_name"]);
			
			$this->cmd("STARTTLS command", "STARTTLS", 220);
		}
		
		// Apply security to the channel:
		if( $security !== "plaintext" ){
			/*
			 * If openssl not loaded, stream_socket_enable_crypto() fails only after
			 * a very long time-out period without a clear indication of the actual
			 * reason, so check and save some headache:
			 */
			if( ! extension_loaded("openssl") )
				throw new \RuntimeException("openssl extension not loaded");
			
			stream_context_set_option($socket, $wrapper, 'verify_peer', TRUE);
			stream_context_set_option($socket, $wrapper, 'verify_peer_name', TRUE);
			stream_context_set_option($socket, $wrapper, 'peer_name', $hp->server_hostname);
			stream_context_set_option($socket, $wrapper, 'allow_self_signed', FALSE);

			// Custom CA for server authentication:
			if( strlen($hp->params["ca_certificate_path"]) > 0 ){
				stream_context_set_option($socket, $wrapper, 'cafile', $hp->params["ca_certificate_path"]);
//				stream_context_set_option($socket, $wrapper, 'capath', "c:\\");
			}

			// Client authentication:
			if( strlen($hp->params["client_certificate_path"]) > 0 ){
				stream_context_set_option($socket, $wrapper, 'local_cert', $hp->params["client_certificate_path"]);
				if( strlen($hp->params["client_key_path"]) > 0 )
					stream_context_set_option($socket, $wrapper, 'local_pk', $hp->params["server_key_path"]);
				if( strlen($hp->params["client_key_passphrase"]) > 0 )
					stream_context_set_option($socket, $wrapper, 'passphrase', $hp->params["server_key_passphrase"]);
			}

			// Try establishing the secure connection:
			try {
				$secure = stream_socket_enable_crypto($socket, TRUE, $method);
			}
			catch(ErrorException $e){
				throw new IOException($e->getMessage());
			}
			if( $secure !== TRUE )
				throw new IOException("$hp: secure protocol handshaking failed");
		}
		
//		if( self::$debug )
//			error_log("SMTP socket meta data: " . var_export(socket_get_status($this->conn), TRUE));

		// Get announcement:
		if( $security === "plaintext" || $security === "ssl" ){
			$announcement = $this->get_lines();
			if( $this->code != 220 )
				throw new IOException("unexpected announcement: $announcement");
		}
		
		$features = $this->hello($hp->params["client_name"]);
		$this->support_smtputf8 = preg_match("/250[- ]SMTPUTF8\r\n/", $features) === 1;

		// User's authentication:
		if( strlen($hp->params["user_authentication_method"]) > 0 )
			$this->authenticate($hp->params["user_authentication_method"],
					$hp->params["user_name"], $hp->params["user_password"]);
	}

	/**
	 * Starts a DATA command session. See also the methods: data_write(),
	 * data_close().
	 * @throws IOException
	 */
	function data_open()
	{
		$this->cmd("DATA command", "DATA", 354);
	}
	
	
	/**
	 * Send a number of lines within a DATA command session. The DATA session
	 * must have already been started by invoking the data_open() method.
	 * @param string $s Lines to send. Lines must be separated by line-feed "\n".
	 * The client must take care to limit the maximum length of each line to not
	 * more than 998 bytes.
	 * @throws IOException
	 */
	function data_write($s)
	{
		// An empty string contains zero lines to send:
		if( strlen($s) == 0 )
			return;
		// Remove any carriage-return:
		$s = (string) str_replace("\r", "", $s);
		// Trailing line-feed must not introduce another empty line:
		if( strlen($s) > 0 && $s[strlen($s)-1] === "\n" )
			$s = substr($s, 0, strlen($s) - 1);
		// Finally send each line in turn:
		$lines = explode("\n", $s);
		foreach($lines as $line){
			if( strlen($line) > 0 && $line[0] === "." )
				$line = ".$line";
			$this->put_line($line);
		}
	}
	
	
	/**
	 * Terminates the DATA command session.
	 * @throws IOException
	 */
	function data_close()
	{
		$this->cmd("end of the DATA command", ".", 250);
	}

	
	/**
	 * Expand takes the name and asks the server to list all the
	 * people who are members of the _list_. Expand will return
	 * back and array; each entry has the following format:
	 * [ &lt;full-name&gt; &lt;SP&gt; ] &lt;path&gt;
	 * &lt;path&gt; is defined in RFC 821.
	 * @param string $name
	 * @return string[int]
	 * @throws IOException
	 */
	function expand($name)
	{
		$rply = $this->cmd("EXPN command", "EXPN " . $name, 250);
		$entries = explode("\r\n", $rply);
		$list_ = /*. (string[int]) .*/ array();
		foreach($entries as $l) {
			$list_[] = substr($l,4);
		}
		return $list_;
	}

	
	/**
	 * Gets help information on the keyword specified. If the keyword is not
	 * specified then returns generic help, usually containing a list of keywords
	 * that help is available on. This function returns the results back to the
	 * user. It is up to the user to handle the returned data.
	 * @param string $keyword
	 * @return string
	 * @throws IOException
	 */
	function help($keyword="")
	{
		$extra = "";
		if(!empty($keyword)) {
			$extra = " " . $keyword;
		}

		$this->put_line("HELP" . $extra);

		$rply = $this->get_lines();

		if($this->code != 211 && $this->code != 214)
			throw new IOException("HELP command: $rply");

		return $rply;
	}

	
	/**
	 * Sends a MAIL FROM command. If the server supports UTF-8 e-mail addresses,
	 * the SMTPUTF8 option is added to the command to explicitly trigger this
	 * feature, otherwise we still blindly send verbatim addresses anyway.
	 * @param string $from Sender email address, UTF-8 encoded.
	 * @return void
	 * @throws IOException
	 */
	function mail($from)
	{
		$options = "";
		if( $this->support_smtputf8 )
			$options = " SMTPUTF8";
		$this->cmd("MAIL FROM command", "MAIL FROM: <$from>$options", 250);
	}

	
	/**
	 * Sends the command NOOP to the SMTP server.
	 * @return void
	 * @throws IOException
	 */
	function noop()
	{
		$this->cmd("NOOP command", "NOOP", 250);
	}

	
	/**
	 * Sends the quit command to the server and then closes the socket.
	 * @return void
	 * @throws IOException QUIT command rejected.
	 */
	function quit()
	{
		if( $this->conn === NULL )
			return;
		
		$this->cmd("QUIT command", "QUIT", 221);
		$this->close();
	}

	
	/**
	 * Sends the command RCPT TO to the SMTP server.
	 * @param string $to Recipient email address, UTF-8 encoded.
	 * @return string NULL if the recipient has been accepted by the server, or
	 * a string reporting the raw response from the server if the recipient has
	 * been rejected.
	 * @throws IOException
	 */
	function recipient($to)
	{
		$this->put_line("RCPT TO: <$to>");

		$rply = $this->get_lines();

		if($this->code == 250 || $this->code == 251)
			return NULL;
		else
			return $rply;
	}

	
	/**
	 * Sends the RSET command to abort and transaction that is currently in
	 * progress.
	 * @return void
	 * @throws IOException
	 */
	function reset()
	{
		$this->cmd("RSET command", "RSET", 250);
	}

	
	/**
	 * Verifies that the name is recognized by the server.
	 * Returns the result code from the server.
	 * @param string $name
	 * @return int Result code from the server.: 250 and 251 means the user is
	 * recognized; anything else means error.
	 * @throws IOException
	 */
	function verify($name)
	{
		$this->put_line("VRFY $name");
		/* $rply = */ $this->get_lines();
		return $this->code;
	}
	
	
	function __destruct()
	{
		if( $this->conn !== NULL ){
			try { $this->close(); }
			catch( IOException $e ){ }
		}
	}

}
