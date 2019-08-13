<?php

namespace it\icosaedro\email;

require_once __DIR__ . "/../../../../../stdlib/autoload.php";
require_once __DIR__ . "/../../../../../stdlib/errors.php";

/*. require_module 'hash'; .*/

use Exception;
use RuntimeException;
use it\icosaedro\io\IOException;
use it\icosaedro\utils\Strings;
use it\icosaedro\utils\TestUnit as TU;


/**
 * Displays all file names on this part and any sub-part, recursively.
 * @param MIMEAbstractPart $part
 * @return void
 */
function echoFileNames($part)
{
	if( $part instanceof MIMEMultiPart ){
		$mp = cast(MIMEMultiPart::class, $part);
		foreach($mp->parts as $p)
			echoFileNames($p);
	} else {
		$fn = $part->header->getFilename();
		if( $fn !== NULL ){
			if( $part->header->isAttachment() )
				echo "Attachment: $fn\n";
			else
				echo "In-line: $fn\n";
		}
	}
}

/**
 * @param Header $header
 * @param string $field_name
 */
function echoRecipients($header, $field_name)
{
	$value = $header->getFieldValue($field_name);
	if( $value === NULL )
		return;
	$addresses = Field::parseAddresses($value);
	if( count($addresses) == 0 )
		return;
	echo "$field_name:";
	foreach($addresses as $a)
		echo " <", $a[0], ">(", $a[1], ")\n";
}

/**
 * @param MIMEAbstractPart $email
 */
function echoEmailAbstract($email)
{
	echo "Date: ", $email->header->getFieldValue("Date"), "\n";
	$header = $email->header;
	$d = $header->getDate();
	if( $d === NULL )
		throw new RuntimeException("failed parsing date: " . $header->getFieldValue("Date"));
	echo "Date parsed as: $d\n";
	echoRecipients($header, "From");
	echoRecipients($header, "To");
	echoRecipients($header, "Cc");
	echoRecipients($header, "Bcc");
	echo "Subject: ", $header->getSubject(), "\n";
	echo "Content-Type: ", $email->header->getType(),
		"; charset=", $header->getCharset(), "\n";
	echo "Body type: ", get_class($email), "\n";
	echoFileNames($email);
}

/**
 * Test some very basic samples and edge cases.
 * @throws IOException
 * @throws EmlFormatException
 */
function testBasicSamples()
{
	// Empty email:
	$email = EmlParser::parseString("");
	TU::test("$email", "\n\n");
	TU::test($email->header->getType(), "text/plain");
	TU::test($email->header->getCharset(), NULL);
	TU::test($email->header->getBoundary(), NULL);
	TU::test($email->header->getDate(), NULL);
	TU::test($email->header->getSubject(), "");
	TU::test($email->header->getEncoding(), "8bit");
	TU::test($email->header->getContentID(), NULL);
	TU::test($email->header->getFilename(), NULL);
	TU::test($email->header->getName(), NULL);
	
	// Empty header + text body:
	$email = EmlParser::parseString("\r\nBody\r\n");
	TU::test("$email", "\nBody\n");
	
	// Header + missing body:
	$email = EmlParser::parseString("Subject: hello\r\n");
	TU::test("$email", "Subject: hello\n\n\n");
	
	// Multipart/mixed content type + no parts:
	$email = EmlParser::parseString("Content-Type: multipart/mixed; boundary=bbb\r\n\r\n--bbb--\r\n");
	TU::test("$email", "Content-Type: multipart/mixed; boundary=bbb\n\n--bbb--\n");
	
	// Multipart/mixed content type + no parts with preamble and epilogue to skip:
	$email = EmlParser::parseString("Content-Type: multipart/mixed; boundary=bbb\r\n\r\npreamble\r\n--bbb--\r\nepilogue\r\n");
	TU::test("$email", "Content-Type: multipart/mixed; boundary=bbb\n\n--bbb--\n");
	
	// Multipart/mixed content type + 1 text part with preamble and epilogue to skip:
	$email = EmlParser::parseString("Content-Type: multipart/mixed; boundary=bbb\r\n\r\npreamble\r\n--bbb\r\nContent-Type: text/plain\r\n\r\nSome text\r\n--bbb--\r\nepilogue\r\n");
	TU::test("$email", "Content-Type: multipart/mixed; boundary=bbb\n\n--bbb\nContent-Type: text/plain\n\nSome text\n--bbb--\n");
	
	// Subject + body:
	$email = EmlParser::parseString("Subject: Test1\r\n\r\nBody1\r\n");
	TU::test("$email", "Subject: Test1\n\nBody1\n");
	
	// Check header fields specialized parser routines:
	$email = EmlParser::parseString(
			"Content-Type: mytype/mysubtype; charset=mycharset; boundary=\"bbb\"; name=myname\r\n"
			."Date: XXX, 2 Jan 2123 12:34:56 -1234\r\n"
			."Subject:   UTF-8 chars like àèìòù\r\n"
			."Content-Transfer-Encoding: 7bit\r\n"
			."Content-Disposition: inline; filename=\"mytext.txt\"\r\n"
			."Content-ID: <a.b@c.d>\r\n"
			."\r\n"
	);
//	TU::test("$email", "\n\n");
	TU::test($email->header->getType(), "mytype/mysubtype");
	TU::test($email->header->getCharset(), "mycharset");
	TU::test($email->header->getBoundary(), "bbb");
	TU::test($email->header->getDate()."", "2123-01-02T12:34:56.000-12:34");
	TU::test($email->header->getSubject(), "UTF-8 chars like àèìòù");
	TU::test($email->header->getEncoding(), "7bit");
	TU::test($email->header->getContentID(), "a.b@c.d");
	TU::test($email->header->getFilename(), "mytext.txt");
	TU::test($email->header->getName(), "myname");
}

/**
 * Parse sample1.eml generated by Thunderbird. It contains:
 * - HTML readable part with in-line image;
 * - one attachment with very long, non-ASCII file name.
 * @throws IOException
 * @throws EmlFormatException
 */
function testSample1()
{
	$path = __DIR__ . "/sample1.eml";
	
	// First, check the sample file is the same (CVS may corrupt CR):
	TU::test(md5((string) str_replace("\r", "", file_get_contents($path))),
		"1b7f98849d364593dcf34b92e9c0b597");
	
	// Parse:
	$email = EmlParser::parseFile($path);
//	echoEmailAbstract($email);
	
	// Check expected header fields:
	$header = $email->header;
	TU::test($header->getSubject(), "EmlParse clàss tèsting sample 1");
	TU::test(Field::parseAddresses($header->getFieldValue("From")),
		[["salsi@icosaedro.it", "Umberto Salsi"]]);
	TU::test(Field::parseAddresses($header->getFieldValue("To")),
		[["recipient1@icosaedro.it", "Rècipient1"], ["recipient2@icosaedro.it", "Rècipient2"]]);
	TU::test(Field::parseAddresses($header->getFieldValue("Cc")),
		[["recipient3@icosaedro.it", ""]]);
	
	// Check the expected message structure:
	$body = cast(MIMEMultiPart::class, $email);
	TU::test($body->header->getType(), "multipart/mixed");
	TU::test(count($body->parts), 2);
	
	$readable = cast(MIMEMultiPart::class, $body->parts[0]);
	TU::test($readable->header->getType(), "multipart/related");
	TU::test(count($readable->parts), 2);
	
	$html = cast(MIMEPartMemory::class, $readable->parts[0]);
	TU::test($html->header->getType(), "text/html");
	TU::test($html->header->getCharset(), "utf-8");
	
	// In-line content is the PHPLint logo:
	$img = cast(MIMEPartMemory::class, $readable->parts[1]);
	TU::test($img->header->isAttachment(), FALSE);
	TU::test($img->header->getType(), "image/png");
	TU::test($img->header->getCharset(), NULL);
	TU::test($img->header->getName(), "phplint.png");
	TU::test($img->header->getFilename(), "phplint.png");
	TU::test($img->header->getContentID(), "part1.CEA9FCDA.743F5BD8@icosaedro.it");
	TU::test(md5($img->content), "7b34bd6edb00346d7abaed229cb5d8fa");
	
	// Attachment is a small text file:
	$attachment = cast(MIMEPartMemory::class, $body->parts[1]);
	TU::test($attachment->header->isAttachment(), TRUE);
	TU::test($attachment->header->getType(), "text/plain");
	TU::test($attachment->header->getCharset(), "UTF-8");
	TU::test($attachment->header->getName(), "WayTooLongFileNameWithNonASCIICharactersLikeàèìòù-WayTooLongFileNameWithNonASCIICharactersLikeàèìòù.txt");
	TU::test($attachment->header->getFilename(), "WayTooLongFileNameWithNonASCIICharactersLikeàèìòù-WayTooLongFileNameWithNonASCIICharactersLikeàèìòù.txt");
	TU::test($attachment->header->getContentID(), NULL);
	TU::test($attachment->content, "Sample1, line 1.\r\nSample1, line2.\r\n");
}


/**
 * @param string $path
 * @throws Exception
 */
function testParseFile($path)
{
	echo "====> $path:\n";
	$email = EmlParser::parseFile($path);
	echoEmailAbstract($email);
}

/**
 * Testing with may mailbox :-)
 * @throws Exception
 */
function testMyMailbox()
{
	testParseFile("C:\\Users\\UmbertoSalsi\\Desktop\\ORIGINAL.eml");
	testParseFile("C:\\Users\\UmbertoSalsi\\Desktop\\OPENPGP-MIME.eml");
	testParseFile("C:\\Users\\UmbertoSalsi\\Desktop\\COND. UNIONE.eml");
	
	// Parse a directory of EML files:
	$path = "C:\\Users\\UmbertoSalsi\\Desktop\\EmlParserTest";
	$d = opendir($path);
	while( ($f = readdir($d)) !== FALSE ){
		if( ! Strings::endsWith($f, ".eml") )
			continue;
		testParseFile("$path\\$f");
	}
	
	// Parse a directory of EML files:
	$path = "C:\\Users\\UmbertoSalsi\\Desktop\\EmlParserTest2";
	$d = opendir($path);
	while( ($f = readdir($d)) !== FALSE ){
		if( ! Strings::endsWith($f, ".eml") )
			continue;
		testParseFile("$path\\$f");
	}
}

/**
 * @throws Exception
 */
function main()
{
	testBasicSamples();
	
	// Parse articulated sample1.eml:
	testSample1();
	
//	testMyMailbox();
}

main();
