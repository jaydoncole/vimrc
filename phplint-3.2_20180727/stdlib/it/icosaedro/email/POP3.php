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

require_once __DIR__ . "/../../../all.php";

use ErrorException;
use InvalidArgumentException;
use it\icosaedro\io\IOException;

/**
 * Allows to connect and communicate with the POP3 server. This example downloads
 * and shows all the messages in the mailbox:
 * 
 * <blockquote><pre>
 * use it\icosaedro\email\POP3;
 * use it\icosaedro\email\ConnectionParameters;
 * $params = "pop3.myisp.com:110"
 *     ."?user_authentication_method=PLAIN"
 *     ."&amp;user_name=MyName"
 *     ."&amp;user_password=MyPassword"
 * $pop3 = new POP3( new ConnectionParameters($params) );
 * $sizes = $pop3-&gt;listSizes();
 * foreach($sizes as $msg_number =&gt; $msg_size){
 *     echo "MESSAGE NUMBER $msg_number, $msg_size BYTES:\n";
 *     $pop3-&gt;retrieveMessage($msg_number, -1);
 *     do {
 *         $line = $pop3-&gt;getLine();
 *         if( $line === NULL )  break;
 *         echo "$line\n";
 *     } while(TRUE);
 * }
 * $pop3-&gt;quit();
 * </pre></blockquote>
 * 
 * <p>References:
 * <br>- POP3, RFC 1939.
 * 
 * @version $Date: 2018/07/19 06:08:36 $
 * @author Umberto Salsi
 */
class POP3
{
	/**
	 * Enable debugging mode by sending to stderr all the client/server dialogue.
	 * @var bool
	 */
	public static $debug = FALSE;

	/**
	 * Socket connection to the server.
	 * @var resource
	 */
	private $conn;
	
	/**
	 * If currently in listing mode reading a multi-lines reply from the server.
	 * Client code may safely invoke the getLine() method to retrieve these lines
	 * as far as this flag is set. If new command is sent before all the end of
	 * the multi-line reply, remaining lines are automatically flushed away.
	 * @var boolean
	 */
	private $listing_mode = FALSE;

	/**
	 * Closes the socket and cleans up the state of the class. Does nothing if
	 * the socket has been already closed. Client code should normally issue a
	 * QUIT command instead.
	 * @return void
	 * @throws IOException
	 */
	function close()
	{
		if( $this->conn !== NULL ) {
			try {
				fclose($this->conn);
			}
			catch(ErrorException $e){
				$this->conn = NULL;
				throw new IOException($e->getMessage());
			}
			$this->conn = NULL;
		}
	}

	/**
	 * Reads one line from the server.
	 * @return string Single line without trailing CRLF.
	 * @throws IOException Error reading. Time-out waiting for data.
	 */
	private function getBareLine()
	{
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
		$line = rtrim($line, "\r\n");
		if( self::$debug )
			error_log("POP3 Server: $line\n");
		return $line;
	}

	/**
	 * Reads one line from the server reply list. This method allows the client
	 * code to retrieve the listing reply from the server one line at a time,
	 * typically the message. NULL is returned at the end of the listing or when
	 * not currently in listing mode. If a new POP3 command is sent before the
	 * end of this listing, pending listing is automatically flushed away to
	 * keep client/server in synch.
	 * @return string Single line without trailing CRLF and with leading dot
	 * removed. NULL is returned at the end of the listing or if no currently in
	 * listing mode.
	 * @throws IOException Error reading. Time-out waiting for data.
	 */
	function getLine()
	{
		if( ! $this->listing_mode )
			return NULL;
		$line = $this->getBareLine();
		if( $line === "." ){
			$this->listing_mode = FALSE;
			return NULL;
		}
		if( strlen($line) > 0 && $line[0] === "." )
			$line = substr($line, 1);
		return $line;
	}

	/**
	 * Send a command line to the server.
	 * @param string $line
	 * @throws IOException
	 */
	private function putLine($line)
	{
		// Flushing pending listing mode:
		while( $this->listing_mode )
			$this->getLine();
		
		if(self::$debug)
			error_log("POP3 Client: $line");
		$line .= "\r\n";
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
	 * @param string $rply
	 * @return boolean
	 */
	private function isOK($rply)
	{
		return strlen($rply) >= 3 && substr($rply, 0, 3) === "+OK";
	}
	
	/**
	 * Send a single line command and wait for the expected +OK feedback.
	 * @param string $cmd POP3 command.
	 * @return string Reply from the server.
	 * @throws IOException Failed sending the command. Time-out waiting for
	 * the reply. Unexpected reply.
	 */
	private function cmd($cmd)
	{
		$this->putLine($cmd);
		$rply = $this->getBareLine();
		if( ! $this->isOK($rply) )
			throw new IOException("POP3 command \"$cmd\" failed: $rply");
		return $rply;
	}

	
	/**
	 * Performs POP3 user's authentication.
	 * @param string $method User's authentication method: "PLAIN", "APOP".
	 * @param string $username
	 * @param string $password
	 * @param string $announcement Announcement line from the server, from which
	 * the APOP timestamp string can be retrieved.
	 * @return void
	 * @throws IOException
	 */
	private function authenticate($method, $username, $password, $announcement)
	{
		switch($method){
			
		case "PLAIN":
			$this->cmd("USER $username");
			$this->cmd("PASS $password");
			break;
			
		case "APOP":
			if( preg_match("/(<[^>]+>)/sD", $announcement, $matches_mixed) !== 1 )
				throw new IOException("APOP user's authentication not supported by server -- announcement line does not contain the expected <...> timestamp: \"$announcement\"");
			$matches = cast("string[int]", $matches_mixed);
			$timestamp = $matches[1];
			$digest = md5($timestamp.$password);
			$this->cmd("APOP $username $digest");
			break;
		
		case "CRAM-MD5":
			$this->putLine("AUTH CRAM-MD5");
			$rply = trim( $this->getBareLine() );
			if( ! (strlen($rply) > 2 && substr($rply, 0, 2) === "+ ") )
				throw new IOException("CRAM-MD5 user's authentication method rejected: $rply");
			$challenge = base64_decode(substr($rply, 2));
			$response = $username . " " . hash_hmac("md5", $challenge, $password);
			$this->cmd(base64_encode($response));
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
	 * @throws InvalidArgumentException Invalid connection parameters: missing
	 * or unknown user's authentication method; unknown security parameter.
	 * @throws IOException TCP connection failed.
	 * Time-out waiting for the connection. Failed reading the certificate
	 * files. Secure authentication of the server failed. User authentication
	 * rejected.
	 */
	function __construct($hp)
	{
		// Parse connection data:
		if( strlen($hp->params["user_authentication_method"]) == 0 )
			throw new InvalidArgumentException("$hp: missing user_authentication_method parameter");
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
			throw new IOException("$hp: " . $e->getMessage());
		}
		if( $socket === FALSE )
			throw new IOException("$hp: $errstr ($errno)");
		stream_set_blocking($socket, TRUE);
		stream_set_timeout($socket, $timeout);
		$this->conn = $socket;
		$this->listing_mode = FALSE;
		
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
				throw new IOException("$hp: secure protocol handshaking failed: " . $e->getMessage());
			}
			if( $secure !== TRUE )
				throw new IOException("$hp: secure protocol handshaking failed");
		}
		
//		if( self::$debug )
//			error_log("POP3 socket meta data: " . var_export(socket_get_status($this->conn), TRUE));

		// Get announcement:
		$announcement = $this->getBareLine();
		if( ! $this->isOK($announcement) )
			throw new IOException("$hp: unexpected announcement from server: $announcement");

		// User's authentication:
		if( strlen($hp->params["user_authentication_method"]) > 0 )
			$this->authenticate($hp->params["user_authentication_method"],
					$hp->params["user_name"], $hp->params["user_password"],
					$announcement);
	}
	
	/**
	 * Retrieve the list of capabilities of the server.
	 * @return string List of capabilities; a LF is added after each line.
	 * @throws IOException
	 */
	function getCapabilities()
	{
		$this->putLine("CAPA");
		$line = $this->getBareLine();
		if( strlen($line) >= 4 && substr($line, 0, 4) === "-ERR" )
			return ""; // CAPA not supported
		if( ! $this->isOK($line) )
			throw new IOException("unexpected reply to the CAPA command: $line");
		$this->listing_mode = TRUE;
		$res = "";
		do {
			$line = $this->getLine();
			if( $line === NULL )
				break;
			$res .= "$line\n";
		} while(TRUE);
		return $res;
	}
	
	/**
	 * Retrieve the list of the available messages sizes. Messages that have been
	 * marked for deletion in this connection session are not retrieved.
	 * @return int[int] The key is the messages number, the value is its size in
	 * bytes.
	 * @throws IOException
	 */
	function listSizes()
	{
		$this->cmd("LIST");
		$this->listing_mode = TRUE;
		$sizes = /*. (int[int]) .*/ array();
		do {
			$line = $this->getLine();
			if( $line === NULL )
				break;
			$line = trim($line);
			if( preg_match("/^([0-9]+) +([0-9]+)/sD", $line, $matches_mixed) !== 1 )
				throw new IOException("cannot parse reply to the LIST command: $line");
			$matches = cast("string[int]", $matches_mixed);
			$sizes[(int) $matches[1]] = (int) $matches[2];
		} while(TRUE);
		return $sizes;
	}
	
	/**
	 * Retrieve the list of the unique message identifiers (UIDs). Messages that
	 * have been marked for deletion in this connection session are not retrieved.
	 * @return string[int] The key is the number of the message, the value is its
	 * unique identifier.
	 * @throws IOException
	 */
	function listUniqueIdentifiers()
	{
		$this->cmd("UIDL");
		$this->listing_mode = TRUE;
		$uids = /*. (string[int]) .*/ array();
		do {
			$line = $this->getLine();
			if( $line === NULL )
				break;
			$line = trim($line);
			if( preg_match("/^([0-9]+) +([\x21-\xfe]+)/sD", $line, $matches_mixed) !== 1 )
				throw new IOException("cannot parse reply to the UIDL command: $line");
			$matches = cast("string[int]", $matches_mixed);
			$uids[(int) $matches[1]] = $matches[2];
		} while(TRUE);
		return $uids;
	}
	
	/**
	 * Retrieve a message. Once invoked this method, the client may retrieve
	 * all the lines of the message one by one through the getLine() method until
	 * a NULL line is returned. By sending another POP3 command before the end of
	 * the reply, the pending transfer is flushed.
	 * @param int $n Number of the message to retrieve. Available message numbers
	 * are returned along with either the sizes or the UIDs.
	 * @param int $r Number of lines of the body to retrieve. A negative value
	 * means the whole message is retrieved; zero means only the header and the
	 * empty separation line (if non empty body) is retrieved.
	 * @throws IOException
	 */
	function retrieveMessage($n, $r = -1)
	{
		if( $r < 0 )
			$this->cmd("RETR $n");
		else
			$this->cmd("TOP $n $r");
		$this->listing_mode = TRUE;
	}
	
	/**
	 * Mark the specified message number for deletion. Messages are actually
	 * deleted from the mailbox after a regular QUIT command.
	 * @param int $n No. of the message to mark for deletion.
	 * @throws IOException
	 */
	function deleteMessage($n)
	{
		$this->cmd("DELE $n");
	}

	/**
	 * Sends the QUIT command to the server and then closes the socket.
	 * @return void
	 * @throws IOException QUIT command rejected.
	 */
	function quit()
	{
		$this->cmd("QUIT");
		$this->close();
	}
	
	function __destruct()
	{
		if( $this->conn !== NULL ){
			try { $this->close(); }
			catch( IOException $e ){ }
		}
	}

}
