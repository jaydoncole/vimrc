<?php

namespace it\icosaedro\email;

require_once __DIR__ . "/../../../autoload.php";
require_once __DIR__ . "/../../../errors.php";

use it\icosaedro\io\IOException;
use it\icosaedro\io\InputStream;
use it\icosaedro\io\LineInputWrapper;
use it\icosaedro\utils\Strings;

/**
 * Implements the ReaderFromMboxInterface to scan the messages from a "mboxrd"
 * mailbox. The "mboxrd" format is used by: Thundebird, Mozilla, Netscape.
 * Example to read the full content of a mbox file:
 * 
 * <blockquote><pre>
 * use it\icosaedro\io\FileInputStream;
 * use it\icosaedro\io\File;
 * use it\icosaedro\email\ReaderFromMbox;
 * use it\icosaedro\email\EmlParser;
 * ...
 * $mbox = new ReaderFromMbox( new FileInputStream( File::fromLocaleEncoded("C:/path/to/mbox/2018") ) );
 * while( $mbox-&gt;next() ){
 *     $email = EmlParser::parse($mbox, NULL);
 *     ...
 * }
 * $mbox-&gt;close();
 * </pre></blockquote>
 * 
 * <br>A getLine() just after a next() will return the marker line that starts
 * each message; applications normally do not care about it.
 * 
 * <p>Please read the documentation about the implemented interface for further
 * details and examples.
 *
 * @author Umberto Salsi <salsi@icosaedro.it>
 * @version $Date: 2018/07/16 07:30:01 $
 */
class ReaderFromMbox implements ReaderFromMboxInterface {

/*
 * IMPLEMENTATION NOTES.
 *  
 * 1. The Mboxrd format.
 * A mboxrd file contains zero or more messages.
 * Each message has one marker line followed by zero or more lines of the email.
 * The marker line line starts with the string "From " possibly followed by
 * other characters on the same line that are here ignored.
 * The email may contain zero or more line that represent the RFC 822 email
 * message; lines of the original email that start with "From " or any number
 * of ">" characters followed by "From " are quoted by another ">"character to
 * avoid being confused with the marker line.
 */
	
	/**
	 * @var LineInputWrapper
	 */
	private $in;
	
	/**
	 * Latest line read. NULL at the EOF.
	 * @var string
	 */
	private $line;
	
	/**
	 * @var int
	 */
	private $line_no = 0;
	
	/**
	 * Tells if the current line is the "From " marker.
	 * @var boolean
	 */
	private $at_marker = FALSE;
	
	function __toString()
	{
		return "line " . $this->line_no;
	}
	
	/**
	 * Read next line, remove EOL marker, detect message marker, updates line
	 * number, un-quote marker from lines.
	 * @return void
	 * @throws IOException
	 */
	private function readLineLowLevel()
	{
		$line = $this->in->readLine();
		if( $line === NULL ){
			$this->line = NULL;
			$this->at_marker = FALSE;
			return;
		}
		$this->line_no++;

		// Remove trailing end-of-line:
		if( Strings::endsWith($line, "\r\n") )
			$line = substr($line, 0, strlen($line) - 2);
		else if( Strings::endsWith($line, "\n") )
			$line = substr($line, 0, strlen($line) - 1);
		
		// Detect messages separation line:
		$this->at_marker = Strings::startsWith($line, "From ");
		
		// Un-quote marker lines:
		if( strlen($line) > 0 && $line[0] === ">"
		&& preg_match("/^>+From /sD", $line) === 1 )
			$line = substr($line, 1);
		
		$this->line = $line;
	}

	/**
	 * Initializes the mbox reader and start reading the first line.
	 * If the file is empty, the fist line results NULL, otherwise it must
	 * contain the first message marker.
	 * @param InputStream $in
	 * @throws IOException
	 * @throws EmlFormatException First line found, but it is not a marker.
	 */
	function __construct($in)
	{
		$this->in = new LineInputWrapper($in);
		$this->line_no = 0;
		
		$this->readLineLowLevel();
		if( $this->line !== NULL && ! $this->at_marker )
			throw new EmlFormatException("not a mbox file");
	}
	
	/**
	 * Move to the beginning of the next message, so that readLine() will
	 * read the first line of that message.
	 * @return boolean True if a next message does really exist, possibly even
	 * empty (zero lines); false at EOF.
	 * @throws IOException
	 */
	function next()
	{
		do {
			if( $this->line === NULL )
				return FALSE;
			if( $this->at_marker ){
				$this->at_marker = FALSE;
				return TRUE;
			}
			$this->readLineLowLevel();
		} while(TRUE);
	}
	
	/**
	 * @return void
	 * @throws IOException
	 */
	function readLine()
	{
		if( $this->line === NULL || $this->at_marker )
			return;
		$this->readLineLowLevel();
	}
	
	/**
	 * @return string
	 */
	function getLine()
	{
		if( $this->line === NULL || $this->at_marker )
			return NULL;
		else
			return $this->line;
	}
	
	/**
	 * @return void
	 * @throws IOException
	 */
	function close()
	{
		if( $this->in === NULL )
			return;
		$in = $this->in;
		$this->in = NULL;
		$in->close();
	}
	
}
