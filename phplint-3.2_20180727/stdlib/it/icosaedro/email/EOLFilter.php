<?php

namespace it\icosaedro\email;

require_once __DIR__ . "/../../../autoload.php";
require_once __DIR__ . "/../../../errors.php";

use it\icosaedro\io\OutputStream;
use it\icosaedro\io\IOException;

/**
 * Stream filter to convert NL into CR+NL. Used to convert from the internal
 * end-of-line marker (NL only) to the RFC 822 end-of-line marker (CR+NL).
 * @author Umberto Salsi <salsi@icosaedro.it>
 * @version $Date: 2018/07/14 11:42:47 $
 */
class EOLFilter extends OutputStream {
	
	/**
	 * @var OutputStream
	 */
	private $out;
	
	/**
	 * Creates a writer object through the DATA command session of an SMTP object.
	 * The DATA session must be already open.
	 * @param OutputStream $out
	 */
	function __construct($out)
	{
		$this->out = $out;
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
	 * @param string $bytes
	 * @return void
	 * @throws IOException
	 */
	function writeBytes($bytes)
	{
		$bytes = (string) str_replace("\r", "", $bytes);
		$bytes = (string) str_replace("\n", "\r\n", $bytes);
		$this->out->writeBytes($bytes);
	}
}
