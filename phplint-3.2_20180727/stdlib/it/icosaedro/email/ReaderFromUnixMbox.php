<?php

namespace it\icosaedro\email;

require_once __DIR__ . "/../../../autoload.php";
require_once __DIR__ . "/../../../errors.php";

use it\icosaedro\io\IOException;
use it\icosaedro\io\InputStream;
use it\icosaedro\io\LineInputWrapper;
use it\icosaedro\utils\Strings;

/**
 * Implements the ReaderFromMboxInterface to scan the messages from a Unix-like
 * mailbox. The Unix-like format is used by most of the Unix and Linux e-mail
 * servers like exim, sendmail, ...
 * 
 * <p>Description of the Unix-like mailbox format follows along with the behavior
 * of this class on every normal and edge case:
 * <br>- An Unix-like mailbox file contains zero or more messages; each message
 * has a preamble line, the lines of the original message, and an empty line added.
 * <br>- Lines are terminated by a single LF code. This class also copes with
 * CRLF combination as well.
 * <br>- The preamble line starts with the "From&nbsp;" leading string followed
 * by the sender address, followed by space, followed by a date. This
 * implementation ignores anything after the leading "From&nbsp;" string and
 * assumes this line as the beginning of a new message. This preamble line can
 * be retrieved by invoking the getLine() method just after next(), but can be
 * normally ignored.
 * <br>- Lines of the original message that started with the leading string
 * "From&nbsp;" have a leading "&gt;" character added. This results in an
 * ambiguity, as lines of the original messages beginning with "&gt;From&nbsp;"
 * are not escaped and cannot be distinguished from quoted lines anymore. This
 * class does not attempt to address this issue and returns those lines verbatim.
 * <br>- This class recognizes the final empty line followed by either the end
 * of the file or followed by another preamble as the end of the current message,
 * then getLine() returns NULL. A missing empty line before the end of the file
 * or before the next preamble is tolerated and getLine() returns NULL as well.
 * 
 * <p>Example to read the full content of Unix-like mailbox file:
 * 
 * <blockquote><pre>
 * use it\icosaedro\io\FileInputStream;
 * use it\icosaedro\io\File;
 * use it\icosaedro\email\ReaderFromUnixMbox;
 * use it\icosaedro\email\EmlParser;
 * ...
 * $mbox = new ReaderFromTtBox( new FileInputStream( File::fromLocaleEncoded("/var/spool/mail/myname") ) );
 * while( $mbox-&gt;next() ){
 *     $email = EmlParser::parse($mbox, NULL);
 *     ...
 * }
 * $mbox-&gt;close();
 * </pre></blockquote>
 * 
 * <p>Please read the documentation about the implemented interface for further
 * details and examples.
 * 
 * <p>References:
 * <br>The application/mbox Media Type, RFC 4155,
 * https://www.rfc-editor.org/info/rfc4155
 * <br>Mbox, https://en.wikipedia.org/wiki/Mbox
 * <br>Exim Specifications,
 * http://www.exim.org/exim-html-3.20/doc/html/spec_15.html
 *
 * @author Umberto Salsi <salsi@icosaedro.it>
 * @version $Date: 2018/07/16 07:32:42 $
 */
class ReaderFromUnixMbox implements ReaderFromMboxInterface {
	
	/*
	 * IMPLEMENTATION NOTES.
	 * We keep a buffer of three lines along with their line no. and the preamble
	 * detection for each line. In this way each line can be:
	 * - NULL: end of the file.
	 * - Empty: possibly the end of the message if followed by either NULL or
	 *   the preamble, otherwise it is a normal message line.
	 * - Preamble.
	 * - Anything else is a line of the message.
	 * 
	 * The current status of each line (line0,line1,line2) allows to detect:
	 * - Beginning of the message: (preamble,*,*).
	 * - End of the message: (NULL,*,*) or (empty,NULL,*) or (empty,preamble,*).
	 * - End of the file: (NULL,*,*).
	 */
	
	/**
	 * @var LineInputWrapper
	 */
	private $in;
	
	/**
	 * Current line to return to the client.
	 * @var string
	 */
	private $line0;
	
	/**
	 * Look-ahead line 1 next to the current line.
	 * @var string
	 */
	private $line1;
	
	/**
	 * Look-ahead line 2 next to line 1.
	 * @var string
	 */
	private $line2;
	
	private $line0_no = 0;
	private $line1_no = 0;
	private $line2_no = 0;
	
	private $line0_is_from = FALSE;
	private $line1_is_from = FALSE;
	private $line2_is_from = FALSE;
	
	function __toString()
	{
		return "line " . $this->line0_no;
	}
	
	/**
	 * Read the next line 2, remove EOL marker, detect end of the message, updates
	 * line number.
	 * @return void
	 * @throws IOException
	 */
	private function readLineLowLevel()
	{
		$line = $this->in->readLine();
		if( $line === NULL ){
			$this->line2 = NULL;
			$this->line2_is_from = FALSE;
			return;
		}
		$this->line2_no++;

		// Remove trailing end-of-line:
		if( Strings::endsWith($line, "\r\n") )
			$line = substr($line, 0, strlen($line) - 2);
		else if( Strings::endsWith($line, "\n") )
			$line = substr($line, 0, strlen($line) - 1);
		
		// Detect message end marker:
		$this->line2_is_from = Strings::startsWith($line, "From ");
		
		$this->line2 = $line;
	}
	
	/**
	 * Shift look-ahead buffer of lines and reads next line 2.
	 * @return void
	 * @throws IOException
	 */
	private function feed()
	{
		// Line 0 becomes line 1:
		$this->line0 = $this->line1;
		$this->line0_no = $this->line1_no;
		$this->line0_is_from = $this->line1_is_from;
		
		// Line 1 becomes line 2:
		$this->line1 = $this->line2;
		$this->line1_no = $this->line2_no;
		$this->line1_is_from = $this->line2_is_from;
		
		// Line 2 becomes the new read line:
		$this->readLineLowLevel();
	}

	/**
	 * Initializes this reader. The next() method should be invoked to detect
	 * the presence of the first message.
	 * @param InputStream $in
	 * @throws IOException
	 * @throws EmlFormatException
	 */
	function __construct($in)
	{
		$this->in = new LineInputWrapper($in);
		$this->feed();
		if( $this->line2 === NULL ){
			// Empty file.
		} else if( $this->line2_is_from ){
			// Mbox format, at least one message.
		} else {
			throw new EmlFormatException("not an mbox formatted file");
		}
		$this->feed();
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
			$this->feed();
		} while( $this->line0 !== NULL && ! $this->line0_is_from );
		return $this->line0_is_from;
	}
	
	/**
	 * @return void
	 * @throws IOException
	 */
	function readLine()
	{
		if( $this->line1 === NULL || $this->line1_is_from ){
			$this->line0 = ""; // just in case of missing empty ending line
			return;
		}
		$this->feed();
	}
	
	/**
	 * @return string
	 */
	function getLine()
	{
		if( $this->line0 === NULL
		|| $this->line0 === "" && ($this->line1 === NULL || $this->line1_is_from) )
			return NULL;
		else
			return $this->line0;
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
