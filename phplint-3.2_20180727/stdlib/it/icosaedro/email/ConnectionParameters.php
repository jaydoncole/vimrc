<?php

namespace it\icosaedro\email;

require_once __DIR__ . "/../../../autoload.php";
require_once __DIR__ . "/../../../errors.php";

/*. require_module 'core'; require_module 'spl'; .*/
use InvalidArgumentException;
use it\icosaedro\containers\Printable;

/**
 * Server (mostly SMTP or POP3) connection parameters string parser.
 * The following features can be specified: SMTP host and TCP port; server
 * authentication through custom CA; client certificate; SSL encryption;
 * SMTP STARTTLS negotiation; user's authentication; timeout.
 * The constructor parses the connection string and the resulting object makes
 * the result available to the program.
 * 
 * <h2>Encryption</h2>
 * 
 * <p>Three security modes are available:
 * 
 * <blockquote>
 * <b>plaintex:</b> (SMTP, POP3) the communication channel is not encrypted, no
 * server nor client authentication is performed.
 * <p>
 * <b>ssl:</b> (SMTP, POP3) the SSL protocol is enabled; server authentication
 * is always performed; client authentication can be enabled.
 * <p>
 * <b>tls:</b> (SMTP only) communication is first established in plain text mode, then
 * encryption is negotiated with the SMTP server by issuing the STARTTLS command;
 * the encryption protocol, server and client authentication are the same as the
 * ssl mode above.
 * </blockquote>
 * 
 * 
 * <h2>Server authentication</h2>
 * <p>When the ssl or tls encryption is enabled, the signature of the server
 * certificate is checked against the pretended certification authority by
 * using the standard OpenSSL mechanism as configured in the system (see the
 * openssl.cafile and openssl.capath directives of the php.ini). If the CA is not
 * found in the standard store, a custom CA certificate can also be specified,
 * which is handy as here you may set your dummy CA of your self-signed server
 * certificate. The name of the host used for the connection is also assumed
 * being the "common name" CN of the authenticated service.
 * 
 * 
 * <h2>Client authentication</h2>
 * <p>Client authentication is not normally required. In the case, a specific
 * parameter allows to set the client certificate.
 * 
 * 
 * <h2>User's authentication</h2>
 * <p>The following user's authentication methods are available:
 * PLAIN (SMTP, POP3), LOGIN, (SMTP) CRAM-MD5 (SMTP) and APOP (POP3).
 * By default no user authentication is performed.
 * 
 * 
 * <h2>Syntax of the connection string</h2>
 * 
 * <p>The syntax of the connection string is very similar to an URL but without
 * the schema part: there must be an host name, possibly a TCP port number, and
 * possibly several URL-like key=value parameters. Time-out, encryption, client
 * authentication, server authentication and user authentication parameters can
 * be set.
 * 
 * <p>Examples of connection strings:
 * <blockquote><pre>
 * smtp.domain.com
 * smtp.domain.com:123
 * smtp.domain.com:123?security=ssl&amp;ca_certificate_path=C:\\MyCA.crt
 * </pre></blockquote>
 * 
 * <p>The value part of each name=value pair can be encoded using the rawurlencode()
 * function of PHP.
 * 
 * <h2>Detailed list of connection parameters</h2>
 * 
 * <p>The default TCP port is 25 (default SMTP TCP port). Other commonly used
 * by SMTP/SSL servers are 465 and 587, but there is not general consensus about
 * which one for SSL and SSL+STARTTLS; the default. The standard POP3 port is
 * 110, or 995 for POP3/SSL.
 * 
 * <p>The allowed parameters are:
 * <ul>
 * <li>"timeout": TCP connection time-out and command reply time-out (seconds).
 * Default 30 s.</li>
 * 
 * <li>"security": "plaintext" (default), "ssl" or "tls".</li>
 * 
 * <li>"client_name": client host name for "hello" SMTP announcement; default
 * "localhost.localdomain".</li>
 * 
 * <li>"client_certificate_path": file path of the client certificate (that is,
 * the client public key); it may also contain the client secret key; empty for
 * no client authentication (default).</li>
 * 
 * <li>"client_key_path": file path of the client secret key; empty if the key is
 * already available in the certificate or for no client authentication at all.</li>
 * 
 * <li>"client_key_passphrase": client key pass-phrase; empty for plain
 * text client secret key (default).</li>
 * 
 * <li>"ca_certificate_path": file path of the specific CA certificate; if empty,
 * or the certificate does not match the CA found in the server certificate, the
 * default OpenSSL CA store is used -- see the openssl.cafile and openssl.capath
 * directives of the php.ini for more.</li>
 * 
 * <li>"user_authentication_method": user's authentication method, one of "PLAIN",
 * "LOGIN", "CRAM-MD5"; empty for no user authentication (default).</li>
 * 
 * <li>"user_name": login user name.</li>
 * 
 * <li>"user_password": login user password.</li>
 * 
 * </ul>
 * 
 * @author Umberto Salsi <salsi@icosaedro.it>
 * @version $Date: 2018/07/16 07:22:32 $
 */
class ConnectionParameters implements Printable {
	
	/**
	 * Server host name.
	 * @var string
	 */
	public $server_hostname;
	
	/**
	 * Server TCP port number.
	 * @var int
	 */
	public $server_port = 25;
	
	/**
	 * Parameters.
	 * @var string[string]
	 */
	public $params = [
		"timeout" => "30",
		"security" => "plaintext",
		"client_name" => "localhost.localdomain",
		"client_certificate_path" => "",
		"client_key_path" => "",
		"client_key_passphrase" => "",
		"ca_certificate_path" => "",
		"user_authentication_method" => "",
		"user_name" => "",
		"user_password" => ""
	];
	
	/**
	 * Returns a readable representation of this object mostly similar to the
	 * constructor one, but with blank secret data for security.
	 * @return string Connection parameters string.
	 */
	function __toString()
	{
		$s = $this->server_hostname;
		if( $this->server_port != 25 )
			$s .= ":" . $this->server_port;
		if( count($this->params) > 0 ){
			$sep = "?";
			foreach($this->params as $k => $v){
				if( strlen($v) == 0 )
					continue;
				if( $k === "user_name" || $k === "user_password" || $k === "client_key_passphrase" )
					$v = "x";
				$s .= "$sep$k=" . rawurlencode($v);
				$sep = "&";
			}
		}
		return $s;
	}
	
	/**
	 * Initializes and parse a server connection string.
	 * @param string $connection_string Server connection string.
	 * @throws InvalidArgumentException Invalid syntax. Unknown parameter.
	 * Certificate or key file not readable. User's authentication required but
	 * either name or password empty or missing.
	 */
	function __construct($connection_string)
	{
		$a = explode("?", $connection_string);
		if( count($a) > 2 )
			throw new InvalidArgumentException("too many '?': $connection_string");
		$b = explode(":", $a[0]);
		$this->server_hostname = $b[0];
		if( count($b) > 2 )
			throw new InvalidArgumentException("too many ':': $connection_string");
		if( count($b) >= 2 )
			$this->server_port = (int) $b[1];
		if( count($a) >= 2 ){
			$b = explode("&", $a[1]);
			foreach($b as $v){
				$c = explode("=", $v);
				if( count($c) != 2 )
					throw new InvalidArgumentException("invalid parameter syntax: $connection_string");
				$name = $c[0];
				$value = rawurldecode($c[1]);
				if( !array_key_exists($name, $this->params) )
					throw new InvalidArgumentException("unknown parameter '$name': $connection_string");
				$this->params[$name] = $value;
			}
		}
		
		// Some consistency checks:
		
		if( strlen($this->params["client_certificate_path"]) > 0
		&& ! is_readable($this->params["client_certificate_path"]) )
			throw new InvalidArgumentException("client certificate not readable: " . $this->params["client_certificate_path"]);
		
		if( strlen($this->params["client_key_path"]) > 0
		&& ! is_readable($this->params["client_key_path"]) )
			throw new InvalidArgumentException("client key not readable: " . $this->params["client_key_path"]);
		
		if( strlen($this->params["ca_certificate_path"]) > 0
		&& ! is_readable($this->params["ca_certificate_path"]) )
			throw new InvalidArgumentException("CA certificate not readable: " . $this->params["ca_certificate_path"]);
		
		if( strlen($this->params["user_authentication_method"]) == 0 ){
			if( strlen($this->params["user_name"]) != 0
			|| strlen($this->params["user_password"]) != 0 )
				throw new InvalidArgumentException("user_name or user_password specified but missing user_authentication_method");
		} else {
			if( strlen($this->params["user_name"]) == 0
			|| strlen($this->params["user_password"]) == 0 )
				throw new InvalidArgumentException("user_authentication_method specified but either missing user_name or user_password");
		}
	}
	
}
