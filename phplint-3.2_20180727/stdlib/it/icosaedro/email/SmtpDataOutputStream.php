<?php

namespace it\icosaedro\email;

require_once __DIR__ . "/../../../autoload.php";
require_once __DIR__ . "/../../../errors.php";

use it\icosaedro\io\OutputStream;
use it\icosaedro\io\IOException;

/**
 * Implements an OutputStream interface that writes through the DATA command
 * session of the SMTP object.
 * @author Umberto Salsi <salsi@icosaedro.it>
 * @version $Date: 2018/07/16 07:35:27 $
 */
class SmtpDataOutputStream extends OutputStream {
	
	/**
	 * @var SMTP
	 */
	private $smtp;
	
	/**
	 * Creates a writer object through the DATA command session of an SMTP object.
	 * The DATA session must be already open.
	 * @param SMTP $smtp
	 */
	function __construct($smtp)
	{
		$this->smtp = $smtp;
	}
	
	/**
	 * Not implemented -- not used by Mailer anyway.
	 * @param int $b
	 * @return void
	 * @throws \RuntimeException
	 */
	function writeByte($b)
	{
		throw new \RuntimeException("not implemented");
	}
	
	
	/**
	 * Send one or more lines of test through the DATA session. Lines must be
	 * terminated by a single new-line character "\n". This implementation always
	 * sends at least one line of data, even if the data sent does not really
	 * terminate with a new-line. The client must take care to limit the maximum
	 * length of each line to not more than 998 bytes.
	 * @param string $bytes Data to send. Does nothing if NULL or empty string.
	 * @return void
	 * @throws IOException
	 */
	function writeBytes($bytes)
	{
		if( strlen($bytes) > 0 && $bytes[strlen($bytes) - 1] !== "\n" )
			throw new \RuntimeException("the string must be either empty or terminated by new-line");
		$this->smtp->data_write($bytes);
	}
}
