<?php

namespace it\icosaedro\email;

require_once __DIR__ . "/../../../autoload.php";
require_once __DIR__ . "/../../../errors.php";

use LogicException;
use it\icosaedro\io\IOException;

/**
 * @access private
 */
class POP3MsgSummary {
	public $number = 0; // no. of the msg in the POP3 server
	public $size = 0; // size in bytes
	public $uid = ""; // UID
}

/**
 * Implements the ReaderFromMboxInterface to scan the messages from a POP3
 * mailbox. An object of type POP3 containing an open connection to the POP3
 * server must be feed to the constructor of this class.The next() method moves
 * to the next message in the mailbox, that becomes the current message.
 * Several methods allows to retrieve the size and the unique identifier of the
 * current message; the current message can be marked for deletion when the POP3
 * session gets terminated by the POP3::quit() method. Example that reads all
 * the mailbox, message by message:
 * 
 * <blockquote><pre>
 * $cp = new ConnectionParameters("pop3.myisp.com:110");
 * $pop3 = new POP3($hp);
 * $mbox = new ReaderFromPOP3($pop3);
 * while( $mbox-&gt;next() ){
 *     $email = EmlParser::parse($mbox, NULL);
 *     ...
 * }
 * $mbox-&gt;close();
 * $pop3-&gt;quit();
 * </pre></blockquote>
 * 
 * <p>It is very important to cleanly terminate the POP3 connection by invoking
 * its close() method to confirm the deletion of the marked messages and properly
 * terminate the session with the server.
 * 
 * <p>A getLine() just after a next() will return a dummy non-empty line;
 * applications normally do not care about it.
 * 
 * <p>Please read the documentation about the implemented interface for further
 * details and examples.
 *
 * @author Umberto Salsi <salsi@icosaedro.it>
 * @version $Date: 2018/07/23 12:38:24 $
 */
class ReaderFromPOP3 implements ReaderFromMboxInterface {
	
	/**
	 * @var POP3
	 */
	private $pop3;
	
	/**
	 * All the messages.
	 * @var POP3MsgSummary[int]
	 */
	private $messages;
	
	/**
	 * Index to the current message in the $messages array.
	 * @var int
	 */
	private $curr_msg_index = 0;
	
	/**
	 * Current message.
	 * @var POP3MsgSummary
	 */
	private $curr_msg;
	
	/**
	 * If the header only has been requested for the current message.
	 * This is an optimization when client code only needs to scan headers:
	 * if the size is "big", this flag is set and only the header is requested;
	 * eventually, if the client requests a line past the header, then this flag
	 * is turned off, the whole message is requested, the header is skipped,
	 * and the client transparently may continue reading the first body line.
	 * @var boolean
	 */
	public $header_only = FALSE;
	
	/**
	 * Latest line read. NULL at the end of the current message.
	 * @var string
	 */
	private $line;
	
	function __toString()
	{
		if( $this->curr_msg === NULL )
			return "no message selected";
		else
			return "message no. " . $this->curr_msg_index;
	}

	/**
	 * Initializes the reader from the given POP3 object.
	 * @param POP3 $pop3 Already open POP3 connection.
	 * @throws IOException
	 */
	function __construct($pop3)
	{
		$this->pop3 = $pop3;
		$sizes = $pop3->listSizes();
		$uids = $pop3->listUniqueIdentifiers();
		$this->messages = array();
		foreach($sizes as $number => $size){
			$msg = new POP3MsgSummary();
			$msg->number = $number;
			$msg->size = $size;
			if( ! isset($uids[$number]) )
				throw new IOException("UID not available for message no. $number");
			$msg->uid = $uids[$number];
			$this->messages[] = $msg;
		}
		$this->curr_msg_index = -1;
		$this->curr_msg = NULL;
		$this->line = NULL;
	}
	
	/**
	 * Move to the beginning of the next message, so that readLine() will
	 * read the first line of that message.
	 * @return boolean True if a next message does really exist, possibly even
	 * empty (zero lines); false at the end of the mailbox.
	 * @throws IOException
	 */
	function next()
	{
		if( $this->curr_msg_index + 1 >= count($this->messages) )
			return FALSE;
		$this->curr_msg_index++;
		$this->curr_msg = $this->messages[$this->curr_msg_index];
		$this->header_only = $this->curr_msg->size > 10000;
		$this->pop3->retrieveMessage($this->curr_msg->number, $this->header_only? 0 : -1);
		$this->line = "X-POP3-Message: ";
		return TRUE;
	}
	
	/**
	 * Returns the size of the current message.
	 * @return int
	 * @throws LogicException
	 */
	function getSize()
	{
		if( $this->curr_msg === NULL )
			throw new LogicException;
		return $this->curr_msg->size;
	}
	
	/**
	 * Returns the UID of the current messages, normally an hash.
	 * @return string
	 * @throws LogicException
	 */
	function getUniqueIdentifier()
	{
		if( $this->curr_msg === NULL )
			throw new LogicException;
		return $this->curr_msg->uid;
	}
	
	/**
	 * Mark the current message for deletion. Messages are actually deleted
	 * after a QUIT -- see the POP3::quit() method.
	 * @return void
	 * @throws LogicException
	 * @throws IOException
	 */
	function delete()
	{
		if( $this->curr_msg === NULL )
			throw new LogicException;
		$this->pop3->deleteMessage($this->curr_msg->number);
	}
	
	/**
	 * Reads another line from the current message.
	 * @return void
	 * @throws IOException
	 */
	function readLine()
	{
		if( $this->line === NULL )
			return;
		$this->line = NULL; // so stops on exception
		$line = $this->pop3->getLine();
		$this->line = $line;
		// If transfer finished because we requested header only, retrieve the
		// whole message, skip the header, and invoke myself again to get the
		// first line of the body.
		if( $line !== NULL || ! $this->header_only )
			return;
		// Retrieve whole message:
		$this->header_only = FALSE;
		$this->pop3->retrieveMessage($this->curr_msg->number, -1);
		// Skip header:
		do {
			$line = $this->pop3->getLine();
			if( $line === NULL )
				return; // no separation line, no body
			if( $line === "" )
				break;
		} while(TRUE);
		$this->line = "";
		self::readLine();
	}
	
	/**
	 * Returns the current line from the current message.
	 * @return string Current line from the current message, or NULL at the end
	 * of the message.
	 */
	function getLine()
	{
		return $this->line;
	}
	
	/**
	 * Closes this object so it cannot be used anymore.
	 * This method DOES NOT close the POP3 connection; the connection with the
	 * POP3 server should be closed by invoking the POP3::quit() method, which
	 * sends the QUIT command and actually deletes the messages that were marked
	 * for deletion.
	 * @return void
	 * @throws IOException
	 */
	function close()
	{
		if( $this->pop3 === NULL )
			return;
		$this->pop3 = NULL;
	}
	
}
