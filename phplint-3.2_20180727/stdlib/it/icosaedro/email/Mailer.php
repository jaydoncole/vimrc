<?php

namespace it\icosaedro\email;

/*.
	require_module 'core';
	require_module 'spl';
	require_module 'pcre';
.*/

require_once __DIR__ . "/../../../autoload.php";
require_once __DIR__ . "/../../../errors.php";

use ErrorException;
use RuntimeException;
use InvalidArgumentException;
use OverflowException;
use it\icosaedro\io\FileName;
use it\icosaedro\io\IOException;
use it\icosaedro\io\OutputStream;
use it\icosaedro\io\StringOutputStream;
use it\icosaedro\utils\Random;

/**
 * Email composition and transport.
 * 
 * <h2>Characters set and characters encoding</h2>
 * All strings are assumed UTF-8 encoded where not stated otherwise.
 * File paths are encoded in the <i>system encoding</i>, that is they must be
 * accepted by "fopen()".
 * 
 * <h2>Internationalized e-mail addresses</h2>
 * Non-ASCII e-mail addresses are sent verbatim in their UTF-8 encoding to the
 * chosen transport method. SMTP servers that support the SMTPUTF8 option
 * (RFC 6531) should accept UTF-8 addresses both in the user part and domain part;
 * SMTP servers that do not yet support this option may or may not accept
 * non-ASCII addresses. The other transport methods may or may not accept UTF-8
 * addresses.
 * The syntax allowed for an e-mail addresses is described in
 * {@link it\icosaedro\email\Field::checkEmailAddress()}.
 * 
 * <h2>Composing the envelope of the message</h2>
 * The real sender, the reply-to addresses (Reply-To), the primary
 * recipients (To), the secondary recipients (Cc) and the undisclosed recipients
 * (Bcc) can be specified using the addXxx() methods. Several clearXxx() methods
 * allow to reset these lists.
 * 
 * <h2>Composing the body of the message</h2>
 * The body of the message may include: one textual part; one HTML part,
 * possibly with in-line related contents (images, sounds, etc.) and several
 * attachments. You may also set as an attachment any abstract MIME part built
 * by your own; if this part is the sole part of all the message, this gives you
 * maximum flexibility about the structure of the message.
 * <p>If both the textual and the HTML readable parts are specified, the
 * remote e-mail client program can display to the user the preferred part.
 * <p>If none of these parts is specified, an empty text-only message is sent.
 *
 * <h2>Sending the message</h2>
 * Messages can be sent via:
 * <ul>
 * <li>The PHP built-in mail() function (see the {@link self::sendByMail()}
 * method).</li>
 * <li>The "sendmail" program available along with the Exim, Postfix and
 * Sendmail SMTP servers (see the {@link self::sendBySendmail()} method).</li>
 * <li>The SMTP protocol to the chosen SMTP server (see the self::sendBySMTP()}
 * method).</li>
 * <li>Any OutputStream object, for example to get the raw RFC822 formatted
 * message in a string in memory or to save an .EML file (see the
 * {@link self::sendByStream()} method}.</li>
 * </ul>
 * 
 * <p>The sendByXxx() methods can be used to send or re-send the same message
 * through different transport methods; the message sent is the same with same
 * message ID and same date as far as the message (apart from the Bcc recipients)
 * does not change.
 * 
 * <p>By using the SMTP transport method, the following features are also
 * available: SSL/TLS encryption possibly with client and server authentication
 * through certificates, user authentication (plain text, login and CRAM-MD5
 * methods supported). 
 * 
 * 
 * <h2>Example</h2>
 * Composing and sending a message requires three steps:
 * 
 * <p>1. Create a new object of this class:
 * <blockquote><pre>
 * $m = new Mailer();
 * </pre></blockquote>
 * 
 * <p>2. Compose the message using the setXxx() and addXxx() methods:
 * <blockquote><pre>
 * $m-&gt;setFrom("myname@mycompany.it", "My Name");
 * $m-&gt;setSubject("Using Mailer, best practices and examples");
 * $m-&gt;addAddress("someone@acme.com");
 * $m-&gt;setTextMessage("The document you requested is attached.\nRegards.");
 * $m-&gt;addAttachmentFromFile("/home/docs/manual.pdf", "application/pdf",
 * 	"Using Mailer, best practices and examples.pdf");
 * $msg_id = $m-&gt;getMessageID();
 * $msg_ts = $m-&gt;getMessageTimestamp();
 * </pre></blockquote>
 * 
 * <p>3. Send the message using one or more transport methods sendByXxx():
 * <blockquote><pre>
 * $rejected = $m-&gt;sendBySMTP("localhost:25");
 * if( count($rejected) &gt; 0 ){
 * 	echo "These recipients were rejected:\n";
 * 	foreach($rejected as $r)
 * 		echo "Recipient email: ", $r[0], ", reason: ", $r[1], "\n";
 * }
 * </pre></blockquote>
 * 
 * <p>If an exception occurs at this step, the message has not been sent to any
 * recipient.
 * 
 * <p>The same message can be re-sent using different transport methods, for
 * example to archive the message on a file (see the sendByStream() method).
 * 
 * <p>Once sent, you may change any property of the message and send it again to
 * others recipients (step 2); by only changing the Bcc recipients, the message
 * ID and date are preserved. Or, got to step 1 to compose a brand new message.
 * 
 * <p>A tutorial about Mailer with further examples and references is
 * available at {@link http://www.icosaedro.it/phplint/mailer-tutorial}.
 * 
 * 
 * <h2>Message ID and Date</h2>
 * 
 * <p>A new random Message-ID field is lazy created when required either to
 * compose the header or requested by the client. A new Message-ID is created
 * when the envelope (that is, the sender or recipients) or the content change.
 * The getMessageID() method returns the current value of the message ID.
 * 
 * <p>The Date field should match the time the message was finished to be composed,
 * not the time it was sent (or re-sent), so even this field is lazy initialized
 * when required to compose the header or requested by the client. E new Date
 * is created when the envelope or the content change. The getMessageTimestamp()
 * returns the current timestamp used for the Date field.
 * 
 * <p>Setting or resetting the Bcc list does not trigger a message change, so
 * that the same message with same date and message ID can be re-sent to different
 * undisclosed recipients or sent through different transport methods.
 * 
 * <h2>Common notes</h2>
 * 
 * <p>1. Some methods require the path to a file; file paths use the current
 * <i>system file name encoding</i> as it is sent directly to the PHP I/O
 * functions. This means file name encoding could depend on the current configured
 * locale settings, on the OS and on the PHP version; have a look at the
 * {@link it\icosaedro\io\FileName} class for more about this issue.
 * 
 * <p>2. Some methods require a MIME type; the allowed syntax is described as a
 * comment to the {@link it\icosaedro\email\Header::isValidContentType()}
 * method. See {@link http://www.iana.org/assignments/media-types/index.html} for
 * a complete list of the registered media types.
 * The it\icosaedro\web\FileDownload::getTypeFromFilename() method could be used
 * to guess the type from the bare file name extension of the file.
 * 
 * <p>3. In-line content requires an univocal ID; RFC 2392 requires be "world
 * univocal", but a "message univocal" ID should suffice. The syntax of the
 * content ID must be the same of an e-mail address accepted by this program.
 * In practice, something like "image1@png", "image2@png" or even simply "img@1",
 * "img@2" will just fit.
 * 
 * @author Umberto Salsi
 * @version $Date: 2018/07/19 06:05:05 $
 */
class Mailer
{
	
/*
 * IMPLEMENTATION NOTES
 * 
 * 1. This class and all the related classes operate assuming and enforcing "\n"
 * as end-of-line character, which is easier to detect. Conversion to "\r\n" is
 * performed only when required by the transport method, that is: SMTP, mail,
 * stream. Sendmail and qmail already perform the conversion by their own.
 */

/**
 * Default charset for any component of the message. This default charset is
 * assumed for any submitted string, including e-mail addresses.
 */
const DEFAULT_CHARSET = "UTF-8";

/**
 * "From" email address of the message.
 * @var string
 */
private $from;

/**
 * "From" name of the message.
 * @var string
 */
private $from_name;

/**
 * Real sender address.
 * @var string
 */
private $sender;

/**
 * Subject.
 * @var string
 */
private $subject;

/**
 * Textual readable part of the message.
 * @var MIMEPartMemory
 */
private $text_part;

/**
 * HTML readable part of the message.
 * @var MIMEPartMemory
 */
private $html_part;

/**
 * Built message without envelope informations.
 * @var MIMEAbstractPart
 */
private $message_part;

/**
 * Email address for the message disposition notification.
 * @var string
 */
private $confirm_reading_to;

/** Sender host name. */
private $host_name = "";

/**
 * Current message ID to be set in the "Message-ID" header field.
 * @var string
 */
private $cached_message_id = NULL;

/**
 * Current message timestamp to be set in the "Date" header field.
 * @var int
 */
private $cached_message_timestamp = 0;

/**
 * Current SMTP connection.
 * @var SMTP
 */
private $smtp;

/** @var string[int][int] */
private $to;

/** @var string[int][int] */
private $cc;

/** @var string[int][int] */
private $bcc;

/** @var string[int][int] */
private $replyTo;

/** @var MIMEAbstractPart[int] */
private $attachments;

/** @var MIMEAbstractPart[int] */
private $inlines;

/** @var string[int][int] */
private $customFields;

/**
 * Reset message ID and message timestamp, so new updated values will be generated.
 * This function must be invoked each time the content of the message changes.
 */
private function resetHeader()
{
	$this->cached_message_id = NULL;
	$this->cached_message_timestamp = 0;
}

/**
 * Reset message ID, message timestamp and built message part, so new updated
 * values will be generated. This function must be invoked each time the content
 * of the message changes.
 */
private function resetBody()
{
	$this->resetHeader();
	$this->message_part = NULL;
}

/**
 * Initializes a new, empty message.
 * The "From" field is set to the "root@localhost" address with "Root User" name.
 * The sender address, confirm reading address, reply-to addresses are all NULL.
 * The Subject is the empty string. No text or HTML parts are set.
 */
function __construct()
{
	$this->from = "root@localhost";
	$this->from_name = "Root User";
	$this->sender = NULL;
	$this->subject = "";
	$this->text_part = NULL;
	$this->html_part = NULL;
	$this->confirm_reading_to = NULL;
	
}

/**
 * Returns the client host name.
 * @return string
 */
private function getClientHostname()
{
	if ( ! empty($this->host_name) )
		$result = $this->host_name;
	else if ( isset($_SERVER['SERVER_NAME']) )
		$result = $_SERVER['SERVER_NAME'];
	else if( strlen( php_uname("n") ) > 0 )
		$result = php_uname("n");
	/*
	else if( function_exists("gethostname") and gethostname() !== FALSE )
		# gethostname() exists only since PHP 5.3.0
		$result = gethostname();
	*/
	else
		$result = "localhost.localdomain";

	return $result;
}

/**
 * Return the latest generated message ID to be used for the "Message-ID" header
 * field. A new message ID is generated each time the content of the message
 * changes, either in the header or body, so the client may invoke this function
 * only once the message has been fully composed. Changing the list of BCC
 * recipients or the transport method or re-sending the same message does not
 * changes this value.
 * @return string Latest generated message ID, including angular brackets.
 */
function getMessageID()
{
	if( strlen($this->cached_message_id) > 0 )
		return $this->cached_message_id;
	$id_left = base64_encode( Random::getCommon()->randomBytes(9) );
	$id_right = $this->getClientHostname();
	return $this->cached_message_id = "<$id_left@$id_right>";
}

/**
 * Return the latest generated message timestamp to be used for the "Date" header
 * field. A new message timestamp is generated each time the content of the
 * message changes, either in the header or body, so the client may invoke this
 * function only once the message has been fully composed. Changing the list of BCC
 * recipients or the transport method or re-sending the same message does not
 * changes this value.
 * @return int Latest generated message timestamp.
 */
function getMessageTimestamp()
{
	if( $this->cached_message_timestamp == 0 )
		$this->cached_message_timestamp = time();
	return $this->cached_message_timestamp;
}

/**
 * Set the sender host name. Used to compose the Message-ID and as default HELO
 * string. If empty, tries $_SERVER['SERVER_NAME'], then {@link php_uname("n")}
 * and finally uses 'localhost.localdomain'.
 * @param string $host_name
 */
function setHostName($host_name)
{
	$this->resetHeader();
	$this->host_name = $host_name;
}

/**
 * Throws exception if invalid email.
 * @param string $address Email address to check.
 * @return void
 * @throws InvalidArgumentException
 */
private static function rejectInvalidEmailAddress($address)
{
	$outcome = Field::checkEmailAddress($address);
	if( $outcome !== NULL )
		throw new InvalidArgumentException("bad email address $address: $outcome");
}

/**
 * Set the "From" sender address. The default is "root@localhost".
 * @param string $address
 * @param string $name
 * @return void
 * @throws InvalidArgumentException Bad address, see {@link it\icosaedro\email\Field::checkEmailAddress()}.
 */
function setFrom($address, $name = "")
{
	$this->resetHeader();
	self::rejectInvalidEmailAddress($address);
	$this->from = $address;
	$this->from_name = $name;
}

/**
 * Set the envelope sender address where bounced mails are sent on error.
 * A "Return-path" header field is always added with the sender address if
 * available, otherwise the "from" address is set instead, but see below.
 * By default the sender address is not set and the resulting envelope sender
 * depends on the transport method used:
 * <br>The "SMTP" transport method set this address as "MAIL FROM" envelope
 * sender; if not set, the current "from" address is used instead.
 * <br>The "sendmail" and mail transport methods: see the comments about the
 * sendByXxx() methods.
 * @param string $address Envelope sender address, or NULL to unset (default).
 * @return void
 * @throws InvalidArgumentException Bad address, see {@link it\icosaedro\email\Field::checkEmailAddress()}.
 */
function setSender($address)
{
	$this->resetHeader();
	if( $address !== NULL )
		self::rejectInvalidEmailAddress($address);
	$this->sender = $address;
}

/**
 * 
 * Sets the email address for the message disposition notification to be set in
 * the "Content-Disposition-To" header field. If empty (the default) this field
 * is not set. See {@link http://tools.ietf.org/html/rfc3798 RFC3798}.
 * @param string $address Confirm address, or NULL to unset (default).
 * @throws InvalidArgumentException Bad address, see {@link it\icosaedro\email\Field::checkEmailAddress()}.
 */
function setConfirmReadingTo($address)
{
	$this->resetHeader();
	if( $address !== NULL )
		self::rejectInvalidEmailAddress($address);
	$this->confirm_reading_to = $address;
}

/**
 * Adds a primary recipient "To".
 * @param string $address Recipient address.
 * @param string $name Recipient current name, encoded using the default
 * character set. If empty, no current name is added (the default).
 * @return void
 * @throws InvalidArgumentException Bad address, see {@link it\icosaedro\email\Field::checkEmailAddress()}.
 */
function addAddress($address, $name = "")
{
	$this->resetHeader();
	self::rejectInvalidEmailAddress($address);
	$this->to[] = array($address, $name);
}

/**
 * Adds a secondary recipient "Cc".
 * @param string $address Recipient address.
 * @param string $name Recipient current name, encoded using the default
 * character set. If empty, no current name is added (the default).
 * @return void
 * @throws InvalidArgumentException Bad address, see {@link it\icosaedro\email\Field::checkEmailAddress()}.
 */
function addCC($address, $name = "")
{
	$this->resetHeader();
	self::rejectInvalidEmailAddress($address);
	$this->cc[] = array($address, $name);
}


/**
 * Adds an undisclosed recipient "Bcc". The message sent over the net never
 * contains this list of undisclosed recipients. By adding Bcc recipients, the
 * current message ID and date are preserved.
 * @param string $address Recipient address.
 * @param string $name Recipient current name, encoded using the default
 * character set. If empty, no current name is added (the default).
 * @return void
 * @throws InvalidArgumentException Bad address, see {@link it\icosaedro\email\Field::checkEmailAddress()}.
 */
function addBCC($address, $name = "")
{
	self::rejectInvalidEmailAddress($address);
	$this->bcc[] = array($address, $name);
}


/**
 * Adds a "Reply-To" recipient address. Several reply-to recipient can be added.
 * @param string $address Recipient address.
 * @param string $name Recipient current name, encoded using the default
 * character set. If empty, no current name is added (the default).
 * @return void
 * @throws InvalidArgumentException Bad address, see {@link it\icosaedro\email\Field::checkEmailAddress()}.
 */
function addReplyTo($address, $name = "")
{
	$this->resetHeader();
	self::rejectInvalidEmailAddress($address);
	$this->replyTo[] = array($address, $name);
}

/**
 * Adds a custom header field. This method accepts only one field at a time;
 * more fields can be added by calling this method several times.
 * @param string $field Custom field to add to the header. Field name and
 * field body must be separated by a colon; no new-lines are required as them are
 * replaced by white space anyway; longer lines are eventually split to the limits;
 * non-ASCII characters are encoded as per the default character set.
 * @return void
 */
function addCustomHeaderField($field)
{
	$this->resetHeader();
	$field = (string) str_replace("\r", "", $field);
	$field = (string) str_replace("\n", " ", $field);
	$this->customFields[] = explode(":", $field, 2);
}


/**
 * Set the "Subject" field value.
 * @param string $subject Subject, encoded using the default character set.
 * Empty by default.
 */
function setSubject($subject)
{
	$this->resetHeader();
	$this->subject = $subject;
}


/**
 * Returns the recommended encoding for the given textual content.
 * If the text does not contain overly long lines, 7bit encoding is returned for
 * ASCII only text, 8bit encoding is returned if "extended ASCII" is detected.
 * If overly long lines are detected, quoted-printable is returned to preserve
 * readability for non-MIME capable readers, Base64 is returned to save space
 * if the text contains essentially non-ASCII only text.
 * @param string $s Text or HTML content to evaluate.
 */
private static function chooseTextEncoding($s)
{
	$ascii_extended = preg_match("/[\x80-\xff]/", $s) === 1;
	$long_lines = preg_match("/(^|\n)[^\n]{" . MIMEAbstractPart::MAX_LINE_LEN .",}/S", $s) === 1;
	if( ! $long_lines )
		return $ascii_extended? Header::ENCODING_8BIT : Header::ENCODING_7BIT;
	$ascii_extended_count = preg_match_all("/[\x80-\xff]/", $s);
	$qp_estimated_size = strlen($s) + 2*$ascii_extended_count;
	$base64_estimated_size = 1.34*strlen($s);
	if( strlen($s) > 200 && $qp_estimated_size / $base64_estimated_size < 2 )
		return Header::ENCODING_QUOTED_PRINTABLE;
	else
		return Header::ENCODING_BASE64;
}


/**
 * Set the plain text part of the message. The default character set is assumed.
 * Lines of the text can be of any length and can be terminated by either LF or
 * CR+LF. The NUL and CR could be removed, so this method is not safe to send
 * binary contents.
 * @param string $text Plain text message. If NULL, no textual part is sent
 * (the default).
 * @return void
 */
function setTextMessage($text)
{
	$this->resetHeader();
	if( $text === NULL ){
		$this->text_part = NULL;
	} else {
		$encoding = self::chooseTextEncoding($text);
		$this->text_part = MIMEPartMemory::build($text,
			"text/plain", self::DEFAULT_CHARSET, "", "", $encoding, FALSE);
	}
}


/**
 * Set the HTML part or the message. HTML may include in-line resources (for
 * example, images) that can be added using the addInlineXxx() methods.
 * The default character set is assumed. Lines of the text can be of any length
 * and can be terminated by either LF or CR+LF.
 * @param string $html HTML message. If NULL, no HTML part is sent (the default).
 * @return void
 */
function setHtmlMessage($html)
{
	$this->resetHeader();
	if( $html === NULL ){
		$this->html_part = NULL;
	} else {
		$encoding = self::chooseTextEncoding($html);
		$this->html_part = MIMEPartMemory::build($html,
			"text/html", self::DEFAULT_CHARSET, "", "", $encoding, FALSE);
	}
}

/**
 * Encode field sub-string to "words".
 * @param string $s String to encode.
 * @param string $position Either "text" (default) or "phrase".
 * @return string Encoded words.
 */
private function encodeWords($s, $position = 'text')
{
	return Field::encodeWords(Field::toUTF8($s, self::DEFAULT_CHARSET), $position);
}

/**
 * Returns an addresses list field.
 * @param string $field_name "To", "Cc", etc.
 * @param string[int][int] $addresses Array of [address,name] entries.
 * @return Field
 */
private function buildAddressesField($field_name, $addresses)
{
	$value = "";
	foreach($addresses as $address){
		if( strlen($value) > 0 )
			$value .= ", ";
		if( strlen($address[1]) > 0 )
			$value .= $this->encodeWords($address[1], "phrase") . " ";
		$value .= "<" . $address[0] . ">";
	}
	return new Field($field_name, $value);
}


/**
 * Adds an attachment from a file.
 * This example will add an image as an attachment:
 * 
 * <pre>
 * $m-&gt;addAttachmentFromFile("C:\\images\\photo.jpeg", "image/jpeg");
 * </pre>
 * 
 * @param string $path Path of the file; see class note 1.
 * @param string $type MIME type of the file; see class note 2.
 * @param string $charset Character set of the content, or empty not applicable.
 * @param string $name Name proposed to the remote user whenever he decides
 * to save this file. If empty, uses the basename of the file. This file name is
 * assumed encoded as the default character set and should not contain a path.
 * @param string $encoding One of the Header::ENCODING_* constants.
 * @return void
 * @throws InvalidArgumentException Invalid type syntax.
 */
function addAttachmentFromFile($path, $type, $charset = NULL, $name = NULL, $encoding = Header::ENCODING_BASE64)
{
	$this->resetBody();
	
	// The MIMEPartFile needs an UTF-8 name:
	if( strlen($name) == 0 ){
		try {
			$name = FileName::decode(basename($path))->toUTF8();
		}
		catch( IOException $e ){
			// should never happen with valid file paths, but...
			throw new RuntimeException($e->getMessage(), 1, $e);
		}
	} else {
		$name = Field::toUTF8($name, self::DEFAULT_CHARSET);
	}
	
	$this->attachments[] = MIMEPartFile::build($path, $type, $charset, $name, "", $encoding, TRUE);
}


/**
 * Adds a string or binary attachment from data in memory.
 * @param string $content Text or binary data to attach.
 * @param string $type MIME type of the content; see class note 2.
 * @param string $charset Character set of the content, or empty not applicable.
 * @param string $name Name proposed to the remote user whenever he decides
 * to save this file. This file name is assumed encoded as the default character
 * set and should not contain a path.
 * @param string $encoding One of the Header::ENCODING_* constants.
 * @return void
 * @throws InvalidArgumentException Invalid type syntax.
 */
function addAttachmentFromString($content, $type, $charset, $name, $encoding = Header::ENCODING_BASE64)
{
	$this->resetBody();
	$name = Field::toUTF8($name, self::DEFAULT_CHARSET);
	$this->attachments[] = MIMEPartMemory::build($content, $type, $charset, $name, "", $encoding, TRUE);
}


/**
 * Adds generic part.
 * @param MIMEAbstractPart $part
 */
function addAttachmentFromPart($part)
{
	$this->resetBody();
	$this->attachments[] = $part;
}


/**
 * Adds an inline attachment to the HTML message, typically an image.
 * Example:
 * 
 * <blockquote><pre>
 * $m = new Mailer();
 * $cid = "image1@png";
 * $m-&gt;setHtmlMessage("&lt;html&gt;&lt;body&gt;Photo: &lt;img src='cid:$cid'&gt; &lt;/body&gt;&lt;/html&gt;");
 * $m-&gt;addInlineFromFile("C:\\images\\photo.jpg", "image/jpeg", NULL, $cid);
 * </pre></blockquote>
 * 
 * @param string $path Path of the file; see class note 1.
 * @param string $type MIME type of the file; see class note 2.
 * @param string $charset Character set of the content, or empty not applicable.
 * @param string $name Name proposed to the remote user whenever he decides to
 * save this content. This file name is assumed encoded as the default character
 * set and should not contain a path. If empty, uses the basename of the file.
 * @param string $cid Content ID of the attachment. See note 3.
 * @param string $encoding One of the Header::ENCODING_* constants.
 * @return void
 * @throws InvalidArgumentException Invalid type syntax.
 */
function addInlineFromFile($path, $type, $charset, $cid, $name = NULL, $encoding = Header::ENCODING_BASE64)
{
	$this->resetBody();
	
	// The MIMEPartFile needs an UTF-8 name:
	if( strlen($name) == 0 ){
		try {
			$name = FileName::decode(basename($path))->toUTF8();
		}
		catch( IOException $e ){
			// should never happen, but...
			throw new RuntimeException($e->getMessage(), 1, $e);
		}
	} else {
		$name = Field::toUTF8($name, self::DEFAULT_CHARSET);
	}
	
	$this->inlines[] = MIMEPartFile::build($path, $type, $charset, $name, $cid, $encoding, TRUE);
}


/**
 * Adds an inline related attachment to the HTML message, typically an image.
 * Example:
 * 
 * <blockquote><pre>
 * $m = new Mailer();
 * $cid = "image1@png";
 * $m-&gt;setHtmlMessage("&lt;html&gt;&lt;body&gt;Photo: &lt;img src='cid:$cid'&gt; &lt;/body&gt;&lt;/html&gt;");
 * $m-&gt;addInlineFromString("photo.jpg", "image/jpeg", NULL, $cid);
 * </pre></blockquote>
 * 
 * @param string $content Binary content of the inline related attachment.
 * @param string $type MIME type of the content; see class note 2.
 * @param string $charset Character set of the content, or empty not applicable.
 * @param string $cid Content ID of the attachment. See note 3.
 * @param string $name Name proposed to the remote user whenever he decides to
 * save this content. This file name is assumed encoded as the default character
 * set and should not contain a path.
 * @param string $encoding One of the Header::ENCODING_* constants.
 * @return void
 * @throws InvalidArgumentException Invalid type syntax.
 */
function addInlineFromString($content, $type, $charset, $cid, $name, $encoding = Header::ENCODING_BASE64)
{
	$this->resetBody();
	
	$this->inlines[] = MIMEPartMemory::build($content, $type, $charset, $name, $cid, $encoding, TRUE);
}

/**
 * Assembles the envelope part of the message header + other "technical" fields
 * beside those already set by the MIME parts classes.
 * @param string $mailer Chosen mailer: "mail", "smtp", "sendmail" or "stream".
 * @param boolean $include_bcc If Bcc field must be included.
 * @return Header Envelope fields.
 */
private function buildEnvelope($mailer, $include_bcc)
{
	$envelope = new Header();

	// "From" field:
	$from = array( array($this->from, $this->from_name) );
	$envelope->addField( $this->buildAddressesField("From", $from) ); 
	
	// Recipients "To", "Cc", "Bcc" fields:
	if( $mailer !== "mail" && phplint_count($this->to) > 0 )
		$envelope->addField( $this->buildAddressesField("To", $this->to) );
	if( phplint_count($this->cc) > 0 )
		$envelope->addField( $this->buildAddressesField("Cc", $this->cc) );
	if( $include_bcc && phplint_count($this->bcc) > 0 )
		$envelope->addField( $this->buildAddressesField("Bcc", $this->bcc) );

	// "Reply-To" field:
	if( phplint_count($this->replyTo) > 0 )
		$envelope->addField( $this->buildAddressesField("Reply-to", $this->replyTo) );

	// Subject field:
	if( $mailer !== "mail" )
		$envelope->addField( new Field("Subject", $this->encodeWords($this->subject)) );
	
	// "Disposition-Notification-To" field:
	if( strlen($this->confirm_reading_to) > 0 )
		$envelope->addField( new Field("Disposition-Notification-To", 
			"<" . $this->confirm_reading_to . ">"));

	// Custom header fields:
	for($index = 0; $index < phplint_count($this->customFields); $index++)
		$envelope->addField( new Field($this->customFields[$index][0], 
			$this->encodeWords($this->customFields[$index][1]) ) );
	
	$envelope->addField( new Field("Message-ID", $this->getMessageID()));
	$envelope->addField( new Field("Date", date(DATE_RFC2822, $this->getMessageTimestamp())));
	$envelope->addField( new Field("MIME-Version", "1.0") );

	return $envelope;
}


/////////////////////////////////////////////////
// MAIL SENDING METHODS
/////////////////////////////////////////////////


/**
 * Returns the full message. "Envelope" header fields are not set here. The following
 * elements of the message body are considered: plain text, HTML text with
 * possible related contents (images, etc.), attachments. The resulting structure
 * of the message depends on which of these elements have been set; if none, an
 * empty message body is generated.
 * @return MIMEAbstractPart The assembled message.
 */
private function buildMessage()
{
	if( $this->message_part !== NULL )
		return $this->message_part;
	
	# Creates text part:
	$text_part = /*. (MIMEAbstractPart) .*/ NULL;
	if( $this->text_part !== NULL )
		$text_part = $this->text_part;
	
	# Creates HTML + inline part:
	$html_part = /*. (MIMEAbstractPart) .*/ NULL;
	if( $this->html_part !== NULL ){
		$html_part = $this->html_part;
		if( phplint_count($this->inlines) > 0 ){
			# Add inline parts:
			$related = MIMEMultiPart::build(MIMEMultiPart::MULTIPART_RELATED);
			$related->appendPart($html_part);
			foreach($this->inlines as $r)
				$related->appendPart($r);
			$html_part = $related;
		}
	}
	
	# Create the part the user will read:
	$readable_part = /*. (MIMEAbstractPart) .*/ NULL;
	if( $text_part === NULL ){
		if( $html_part === NULL ){
			// No readable part at all.
		} else {
			// HTML part only.
			$readable_part = $html_part;
		}
	} else {
		if( $html_part === NULL ){
			// Text part only.
			$readable_part = $text_part;
		} else {
			// Both text and HTML versions of the same message are available.
			$alternative = MIMEMultiPart::build(MIMEMultiPart::MULTIPART_ALTERNATIVE);
			$alternative->appendPart($text_part);
			$alternative->appendPart($html_part);
			$readable_part = $alternative;
		}
	}
	
	# Assembles the readable part and attachments:
	$body = /*. (MIMEAbstractPart) .*/ NULL;
	if( $readable_part === NULL ){
		if( phplint_count($this->attachments) == 0 ){
			// Empty body.
			$body = MIMEPartMemory::build("", "text/plain", NULL, "", "",
				Header::ENCODING_7BIT, FALSE);
		} else if( phplint_count($this->attachments) == 1 ){
			// A single attachment or part.
			$body = $this->attachments[0];
		} else {
			// 2+ attachments.
			$mp = MIMEMultiPart::build(MIMEMultiPart::MULTIPART_MIXED);
			foreach($this->attachments as $r)
				$mp->appendPart($r);
			$body = $mp;
		}
	} else {
		if( phplint_count($this->attachments) == 0 ){
			// Readable part only.
			$body = $readable_part;
		} else {
			// Readable part + attachments.
			$mp = MIMEMultiPart::build(MIMEMultiPart::MULTIPART_MIXED);
			$mp->appendPart($readable_part);
			foreach($this->attachments as $r)
				$mp->appendPart($r);
			$body = $mp;
		}
	}

	$this->message_part = $body;
	return $body;
}

/**
 * Command shell string escaping for string intended to be inserted between
 * double quotes. This function throws exception if the string cannot be escaped.
 * Used here only to check and sanitize the sender address to be passed as a
 * command shell argument to the mail() or sendmail transport methods.
 * @param string $s Sender address.
 * @return string Escaped string ready to be inserted between double-quotes.
 * @throws InvalidArgumentException Too long. Cannot safely escape.
 */
private static function shellEscapeForDoubleQuotes($s)
{
	// Check max length:
	if( strlen($s) > 200 )
		throw new InvalidArgumentException("sender address too long: $s");
	
	// Pass only harmless chars and chars we know how to escape:
	if( PHP_OS === "WINNT" )
		// Troublesome chars: " % \
		// "Extended" ASCII apparently gets converted to locale encoding, so it
		// is not transparent for codes >= 128.
		$safeset = "/^[ !#\$&-[\\]-~]*\$/sD";
	else if( PHP_OS === "Linux" )
		// Bash troublesome chars: ! (only if history expansion enabled; no way to escape)
		// will require escaping: $ ` " \
		$safeset = "/^[ \"-~]*\$/sD";
	else
		// Anything else: stay conservative:
		$safeset = "/^[-+_0-9.@ A-Za-z]*\$/sD";
	if( preg_match($safeset, $s) !== 1 )
		throw new InvalidArgumentException(PHP_OS . " shell command injection prevention: unsafe characters detected in sender address \"$s\"; safe syntax is $safeset");
	
	// Escape Bash specials:
	if( PHP_OS === "Linux" )
		$s = preg_replace("/([\$`\"\\\\])/", "\\\\\\1", $s);
	
	return $s;
}

/**
 * Send the message via the PHP mail() function. If this method succeeds, that is
 * no exception is thrown, the message has been accepted by the SMTP server for
 * delivery to at least one of the recipients; there is no way to know which
 * recipients where rejected, so the only reliable way to send the same message
 * to different recipients is to send several messages, possibly using the Bcc
 * field (see the addBcc() and clearBCCs() methods). This method throws
 * ErrorException only if ALL the recipients were rejected (or on other severe
 * failure); some feedback about the reason of the failure could be retrieved
 * through messages bounced to the From or Sender address and by examining the
 * log files of the SMTP server.
 * @return void
 * @throws InvalidArgumentException A sender address has been set, but it contains
 * characters that cannot be safely put on the command line.
 * @throws ErrorException The mail() function failed, message not sent.
 * @throws IOException Message composition failed, message not sent.
 */
function sendByMail()
{
	if((phplint_count($this->to) + phplint_count($this->cc) + phplint_count($this->bcc)) < 1)
		return;

	$message = $this->buildMessage();
	
	// Build header string:
	$buf = new StringOutputStream();
	$out = new EOLFilter( new StringOutputStream() );
	$this->buildEnvelope("mail", TRUE)->write($out);
	$message->header->write($out);
	$header = $buf->__toString();

	// Build body string:
	$buf = new StringOutputStream();
	$out = new EOLFilter( new StringOutputStream() );
	$message->writeBody($out);
	$body = $buf->__toString();
	
	$to = "";
	for($i = 0; $i < phplint_count($this->to); $i++)
	{
		if($i != 0) { $to .= ", "; }
		$to .= $this->to[$i][0];
	}

	$err = "";
	if( strlen($this->sender) > 0 ){
		$shell_safe_sender = self::shellEscapeForDoubleQuotes($this->sender);
		$old_from = ini_get("sendmail_from");
		ini_set("sendmail_from", $this->sender);
		$params = sprintf("-oi -f \"%s\"", $shell_safe_sender);
		$rt = mail($to, $this->encodeWords($this->subject), $body, $header, $params);
		if( error_get_last() !== NULL )
			$err = (string) error_get_last()['message'];
		// Restore original value:
		ini_set("sendmail_from", $old_from);
	} else {
		$rt = mail($to, $this->encodeWords($this->subject), $body, $header);
		if( error_get_last() !== NULL )
			$err = (string) error_get_last()['message'];
	}

	if( ! $rt ){
		if( strlen($err) == 0 )
			$err = "no further details are available; check Sender/From mailbox and SMTP log files for more info";
		throw new ErrorException("mail() failed: $err");
	}
}

/**
 * Gracefully closes the current SMTP connection, if it does exist.
 * @return void
 */
function smtpClose()
{
	if($this->smtp === NULL)
		return;
	try { $this->smtp->quit(); } catch(IOException $e){}
	$this->smtp = NULL;
}


/**
 * Initiates a connection to an SMTP server.
 * @param string $hosts
 * @return void
 * @throws InvalidArgumentException Invalid host syntax.
 * @throws IOException None of the hosts accepted the connection and possibly
 * the user authentication.
 */
private function smtpConnect($hosts)
{
	// Try each host in turn:
	$this->smtpClose();
	$hosts_array = explode(";", $hosts);
	$errs = "";
	for( $i = 0; $i < count($hosts_array); $i++ ){
		$hp = new ConnectionParameters($hosts_array[$i]);
		if( $hp->params["client_name"] === "localhost.localdomain" )
			$hp->params["client_name"] = $this->getClientHostname();
		try {
			$this->smtp = new SMTP($hp);
			return;
		}
		catch ( IOException $e ) {
			// If only 1 host, report detailed exception:
			if( count($hosts_array) == 1 )
				throw $e;
			// ...otherwise do a summary:
			$errs .= "\n$hp: " . $e->getMessage();
		}
	}

	// All the hosts failed:
	throw new IOException($errs);
}

/**
 * Send the message via SMTP server. The connection string may list one or more
 * servers along with its specific parameters and they are tested in the order,
 * until the first successful connection is established or failure.
 * An existing connection can also be re-used if available, depending on the
 * keep-alive flag.
 * On success, the list of rejected recipients is returned, which means the
 * message has been sent only to part of the intended recipients; please note
 * that the returned value of this method should always be checked cause it could
 * succeed (that is, not throwing exception) even if all the recipients where
 * rejected. On exception, the message is not sent to any recipient.
 * @param string $hosts SMTP hosts. Several servers separated by semicolon; for
 * each host the syntax is described in the documentation of the
 * {@link it\icosaedro\email\ConnectionParameters} class; white spaces
 * around semicolons are ignored. Example:
 * <tt>"host1; host2:port2; host3?timeout=10&amp;user_authentication_method=PLAIN&amp;user_name=MyName&amp;user_password=xyz"</tt>.
 * @param boolean $keep_alive If true: try re-using the existing SMTP connection
 * if available and still working; do not close the connection once finished
 * sending this message; see also the smtpClose() method to explicitly close any
 * pending connection.
 * If false: close current connection if still open; close the connection once
 * finished sending this message.
 * @return string[int][int] List of rejected recipients. For each entry: at index
 * zero the rejected recipient email; at index 1 the raw response from the server,
 * that may contain several lines separated by "\r\n", each line starting with
 * the SMTP status code; the specific content varies depending on the reason.
 * @throws IOException Failed connection to SMTP server. Failed communicating
 * over the SMTP channel. Failed reading attachment file. Failed accessing
 * attachment file.
 */
function sendBySMTP($hosts, $keep_alive)
{
	$accepted = 0;
	$rejected = /*. (string[int][int]) .*/ array();
	
	if((phplint_count($this->to) + phplint_count($this->cc) + phplint_count($this->bcc)) < 1)
		return $rejected;
	
	if( ! $keep_alive )
		$this->smtpClose();

	if( $this->smtp == NULL ){
		// Establishing new connection:
		$this->smtpConnect($hosts);
	} else {
		// Checking current connection is still working; also resets possibly
		// interrupted of failed previous envelope session:
		try {
			$this->smtp->reset();
		}
		catch( IOException $e ){
			// Current connection isn't working anymore -- refreshing:
			$this->smtpConnect($hosts);
		}
	}

	$smtp_from = (strlen($this->sender) == 0)? $this->from : $this->sender;
	$this->smtp->mail($smtp_from);
	
	// Collects all recipients:
	$recipients = /*. (string[int]) .*/ array();
	if( is_array($this->to) )
		foreach($this->to as $recipient)
			$recipients[] = $recipient[0];
	if( is_array($this->cc) )
		foreach($this->cc as $recipient)
			$recipients[] = $recipient[0];
	if( is_array($this->bcc) )
		foreach($this->bcc as $recipient)
			$recipients[] = $recipient[0];

	// Send all recipients:
	foreach($recipients as $email){
		$rply = $this->smtp->recipient($email);
		if( $rply === NULL )
			$accepted++;
		else
			$rejected[] = [$email, $rply];
		
		// Give up on non-recipient specific error:
		$abort = FALSE;
		switch($this->smtp->code){
		case 250: // Requested mail action okay, completed
			break;
		case 251: // User not local; will forward to <forward-path>
			break;
		case 421: // <domain> Service not available, closing transmission channel
			$abort = TRUE;
			break;
		case 450: // Requested mail action not taken: mailbox unavailable
			break;
		case 451: // Requested action aborted: local error in processing
			$abort = TRUE;
			break;
		case 452: // Requested action not taken: insufficient system storage
			      // or too many recipients
			$abort = TRUE;
			break;
		case 500: // Syntax error, command unrecognised
			$abort = TRUE;
			break;
		case 501: // Syntax error in parameters or arguments
			$abort = TRUE;
			break;
		case 503: // Bad sequence of commands
			$abort = TRUE;
			break;
		case 521: // Server/host/domain does not accept mail (RFC 1846, RFC 7504)
			$abort = TRUE;
			break;
		case 550: // Requested action not taken: mailbox unavailable
			break;
		case 551: // User not local; please try <forward-path>
			break;
		case 552: // Requested mail action aborted: exceeded storage allocation
			$abort = TRUE;
			break;
		case 553: // Requested action not taken: mailbox name not allowed
			break;
		case 556: // Target relay host does not accept connections (RFC 7504)
			break;
		default:  // Unexpected reply.
			$abort = TRUE;
			break;
		}
		if( $abort )
			throw new IOException("server failure while issuing RCPT TO <$email>: $rply");
	}

	if( $accepted > 0 ){
		$this->smtp->data_open();
		$out = new SmtpDataOutputStream($this->smtp);
		$this->buildEnvelope("smtp", FALSE)->write($out);
		$this->buildMessage()->write($out);
		$out->close();
		$this->smtp->data_close();
	}

	if( ! $keep_alive)
		$this->smtpClose();
	
	return $rejected;
}

/**
 * Send the messages via the "sendmail" program. A "sendmail" program is available
 * along with the Exim, Postfix and Sendmail SMTP servers. If this method completes
 * successfully (that is, no exception) then the message has been accepted for
 * delivery to at least some of the recipients; there is no way to know which
 * recipients have been accepted and which have been rejected; for each rejected
 * recipient normally a bounce error message is delivered to the sender.
 * <p>As a partial workaround of the limitation above, additional options can be
 * added to the path of the command; for example with "/usr/sbin/sendmail -oeq -oep"
 * fails and no message is sent (at least with Exim and Sendmail) if even one
 * single recipient has been rejected; the error message of the exception contains
 * the list of these rejected addresses and the reason; no bounce email in this
 * case is sent.
 * <p>Hint: <tt>ini_get("sendmail_path")</tt> returns the command used by mail()
 * which contains the path to the configured sendmail program.
 * <p>Hint: each email sent through mail() or through this method contains in
 * the header the name of the specific system SMTP server; look at the last
 * "Received:" header field.
 * <p>On most systems only a privileged account (typically "root") may set an
 * arbitrary sender address; on these systems the sender addresses might be silently
 * overridden with the real user identity (typically the web server identity).
 * @param string $sendmail Path to the sendmail program, possibly with options.
 * @return void
 * @throws InvalidArgumentException A sender address has been set, but it contains
 * characters that cannot be safely put on the command line.
 * @throws IOException Failed accessing attachment file.
 * Failed starting the sendmail process.
 * Failed communicating with the sendmail process.
 * The process returned non-zero error exit status or some error message or both.
 */
function sendBySendmail($sendmail)
{
	if((phplint_count($this->to) + phplint_count($this->cc) + phplint_count($this->bcc)) < 1)
		return;
	
	if( strlen($this->sender) > 0 ){
		$shell_safe_sender = self::shellEscapeForDoubleQuotes($this->sender);
		$cmd = sprintf("%s -oi -f \"%s\" -t", $sendmail, $shell_safe_sender);
	} else {
		$cmd = sprintf("%s -oi -t", $sendmail);
	}
	
	$out = new ProcOutputStream($cmd);
	$this->buildEnvelope("sendmail", TRUE)->write($out);
	$this->buildMessage()->write($out);
	$out->close();
	if( $out->getExitCode() != 0 || ! $out->eofStderr() )
		throw new IOException($out->getStateDescr());
}

/**
 * Write the message to a stream in RFC822 format (.EML). This method can be
 * used to store the message on a string or on a file, for example:
 * <blockquote><pre>
 * use it\icosaedro\email\Mailer;
 * use it\icosaedro\io\StringOutputStream;
 * use it\icosaedro\io\FileOutputStream;
 * use it\icosaedro\io\File;
 * $m-&gt;new Mailer();
 * ...compose the message as usual...
 * 
 * // Retrieve the message as a string:
 * $out_string = new StringOutputStream();
 * $m-&gt;sendByStream($out_string, TRUE);
 * $message_as_string = "$out_string";
 * 
 * // Save the message on a file:
 * $out_file = new FileOutputStream(File::fromLocaleEncoded("mail.eml"));
 * $m-&gt;sendByStream($out_file, TRUE);
 * </pre></blockquote>
 * 
 * @param OutputStream $out Destination of the message in raw RFC 822 format.
 * @param boolean $include_bcc If the Bcc recipients have to be included.
 * @return void
 * @throws IOException Failed accessing attachment file. Failed writing.
 */
function sendByStream($out, $include_bcc)
{
	$out = new EOLFilter($out);
	$this->buildEnvelope("stream", $include_bcc)->write($out);
	$this->buildMessage()->write($out);
}


/////////////////////////////////////////////////
// MESSAGE RESET METHODS
/////////////////////////////////////////////////

/**
 * Clears all recipients assigned in the TO array.
 * @return void
 */
function clearAddresses()
{
	$this->resetHeader();
	$this->to = NULL;
}

/**
 * Clears all recipients assigned in the CC array.
 * @return void
 */
function clearCCs()
{
	$this->resetHeader();
	$this->cc = NULL;
}

/**
 * Clears all recipients assigned in the BCC array. The current message ID and
 * date are preserved.
 * @return void
 */
function clearBCCs()
{
	$this->bcc = NULL;
}

/**
 * Clears all recipients assigned in the ReplyTo array.
 * @return void
 */
function clearReplyTos()
{
	$this->resetHeader();
	$this->replyTo = NULL;
}

/**
 * Clears all recipients assigned in the TO, CC and BCC array.
 * @return void
 */
function clearAllRecipients()
{
	$this->resetHeader();
	$this->to = NULL;
	$this->cc = NULL;
	$this->bcc = NULL;
}

/**
 * Clears all previously set attachments.
 * @return void
 */
function clearAttachments()
{
	$this->resetBody();
	$this->attachments = NULL;
}

/**
 * Clears all previously set inline related attachments.
 * @return void
 */
function clearInline()
{
	$this->resetBody();
	$this->inlines = NULL;
}

/**
 * Clears all custom header fields.
 * @return void
 */
function clearCustomHeaderFields()
{
	$this->resetHeader();
	$this->customFields = NULL;
}

/**
 * Returns the total size of the un-encoded content of this message, including
 * textual parts, in-line parts and attachments.
 * @return int Total size of this message (bytes).
 * @throws OverflowException Integer precision overflow.
 */
function getContentSize()
{
	return $this->buildMessage()->getContentSize();
}

/**
 * Returns the total estimated encoded size of the body of this message,
 * including textual parts, in-line parts and attachments. This is the total
 * estimated length of the body of the message excluding the header of the
 * message and excluding the header of each part.
 * @return int Total estimated size of the body (bytes).
 * @throws OverflowException Integer precision overflow.
 */
function getEstimatedEncodedSize()
{
	return $this->buildMessage()->getEstimatedEncodedSize();
}

}
