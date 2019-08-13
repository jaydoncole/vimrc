<?php

namespace it\icosaedro\email;

require_once __DIR__ . "/../../../all.php";

use it\icosaedro\io\IOException;
use it\icosaedro\io\InputStream;
use it\icosaedro\io\LineInputWrapper;
use it\icosaedro\utils\Strings;

/**
 * Email reader from InputStream wrapper. An object of this class allows the
 * email parser to scan an abstract InputStream source of bytes that come from
 * a file, from a string in memory, or other source.
 * 
 * @author Umberto Salsi <salsi@icosaedro.it>
 * @version $Date: 2018/07/16 07:31:48 $
 */
class ReaderFromStream implements ReaderInterface {
	
	/**
	 * @var LineInputWrapper
	 */
	private $in;
	
	/**
	 * @var string
	 */
	private $line;
	
	/**
	 * @var int
	 */
	private $line_no = 0;

	/**
	 * @param InputStream $in
	 */
	function __construct($in)
	{
		$this->in = new LineInputWrapper($in);
		$this->line_no = 0;
	}
	
	function __toString()
	{
		return "line " . $this->line_no;
	}
	
	/**
	 * @return void
	 * @throws IOException
	 */
	function readLine()
	{
		$line = $this->in->readLine();
		if( $line === NULL ){
			$this->line = NULL;
			return;
		}
		$this->line_no++;

		// Remove trailing end-of-line:
		if( Strings::endsWith($line, "\r\n") )
			$line = substr($line, 0, strlen($line) - 2);
		else if( Strings::endsWith($line, "\n") )
			$line = substr($line, 0, strlen($line) - 1);
		
		$this->line = $line;
	}
	
	function getLine()
	{
		return $this->line;
	}
	
	/**
	 * @return void
	 * @throws IOException
	 */
	function close()
	{
		$this->in = NULL;
	}
	
}
