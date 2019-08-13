<?php
namespace it\icosaedro\email;

require_once __DIR__ . "/../../../autoload.php";

use it\icosaedro\io\IOException;

/**
 * Mailbox scanner and e-mail parser interface. Implementations of this interface
 * allow the EmlParser class to scan a mailbox file (or any other source of bytes)
 * containing several e-mails. Basically, an object of this interface provides
 * the next() methods that skips to the next message if available, then the same
 * object can be feed to the EmlParser to parse that message by invoking the
 * readLine() and getLine() methods defined in the ReaderInterface. The getLine()
 * method will return NULL once read the last line of the current message; the
 * client code may then invoke next() again to process the next message.
 * For example, assuming SpecificMboxReader be a class implementing this interface,
 * this skeleton of code displays the headers of all the messages:
 * 
 * <blockquote><pre>
 * $mbox = SpecificMboxReader(...);
 * while( $mbox-&gt;next() ){
 *     echo "Next message:\n";
 *     do {
 *         $mbox-&gt;readLine();
 *         $line = $mbox-&gt;getLine();
 *         if( $line === NULL || $line === "" )
 *             break;
 *         echo "$line\n";
 *     } while(TRUE);
 * }
 * $mbox-&gt;close();
 * </pre></blockquote>
 * 
 * Note that $line === "" detects the separation line between header and body,
 * while $line === NULL detects the end of the message in case the body is
 * missing. To fully parse each e-mail the skeleton becomes:
 * 
 * <blockquote><pre>
 * $mbox = SpecificMboxReader(...);
 * while( $mbox-&gt;next() ){
 *     $email = EmlParser::parse($mbox, NULL);
 *     ...
 * }
 * $mbox-&gt;close();
 * </pre></blockquote>
 * 
 * <p>Note that the EmlParser::parse() method can throw the EmlFormatException;
 * this exception could be captured and possibly ignored, and scanning may
 * continue with the next message. IOException exception always indicates a
 * fatal failure accessing the mailbox.
 * 
 * <br>For faster scanning of a mailbox, only the headers could be parsed using
 * the EmlParser::parseHeader() method rather than EmlParser::parse(); the next()
 * method will take care to skip all the body parts. For example, this skeleton
 * of code first parses the header, then conditionally parses the body to get the
 * full message:
 * 
 * <blockquote><pre>
 * $mbox = SpecificMboxReader(...);
 * while( $mbox-&gt;next() ){
 *     $header = EmlParser::parseHeader($mbox, NULL);
 *     if( $header-&gt;getSubject() === "Today report" ){
 *         $email = EmlParser::parseBody($mbox, $header, NULL);
 *         ...
 *     }
 * }
 * $mbox-&gt;close();
 * </pre></blockquote>
 * 
 * @author Umberto Salsi <salsi@icosaedro.it>
 * @version $Date: 2018/07/16 07:30:24 $
 */
interface ReaderFromMboxInterface extends ReaderInterface {
	
	/**
	 * Skip to the beginning of the next message, so that readLine() will
	 * read the first line of that message. Please note that the next() method
	 * must be invoked to access the first email.
	 * @return boolean True if a next message does really exist, possibly even
	 * empty (zero lines); false at the end of the mailbox.
	 * @throws IOException Failed reading from the mailbox.
	 * @throws EmlFormatException Something badly wrong with the format of the
	 * mailbox.
	 */
	function next();
	
}
