<?php

namespace it\icosaedro\email;

require_once __DIR__ . "/../../../../../stdlib/all.php";

use RuntimeException;
use it\icosaedro\utils\TestUnit as TU;


/**
 * Testing quoted_printable_encode(), used in several places under the phpmailer
 * name space, but poorly documented in the manual. Looking at its C implementation
 * it seems it does the expected work, but also takes care to avoid split UTF-8
 * sequences, blindly assuming the passed string could carry such char encoding.
 * Reference: RFC 2045, 6.7.
 */
function test_quoted_printable_encode()
{
	TU::test(quoted_printable_encode(""), "");
	
	// each single ctrl byte should be encoded by its own:
	for($c = 0; $c <= 31; $c++)
		TU::test(quoted_printable_encode(chr($c)), sprintf("=%02X", $c));
	
	// CR+LF does not need encoding:
	TU::test(quoted_printable_encode("\r\n"), "\r\n");
	
	// LF+CR instead does:
	TU::test(quoted_printable_encode("\n\r"), "=0A=0D");
	
	TU::test(quoted_printable_encode("="), "=3D");
	
	// Lines longer than 76 bytes (75 in the PHP implementation) must be split
	// with a soft line break:
	TU::test(quoted_printable_encode(
		"01234567890123456789012345678901234567890123456789012345678901234567890123456789"),
		"012345678901234567890123456789012345678901234567890123456789012345678901234=\r\n56789");
	
	// Checking if really does not split UTF-8 sequences in any situation:
	// create increasingly long, non-breackable string full of a very long
	// UTF-8 sequences, then check sequences never get split in the middle:
	$seq = "\xf4\x81\x82\x83"; // random 4 bytes UTF-8 sequence
	$seq_qp = "=F4=81=82=83"; // ...and its QP encoded counterpart
	$s = str_repeat($seq, 99);
	$s_qp = quoted_printable_encode($s);
//echo $s_qp, "\n";
	// Check no line is longer than 76 bytes (including CR):
	if( preg_match("/^.{76,}\$/m", $s_qp) === 1 )
		throw new RuntimeException("QP test: found line longer than 76 bytes");
	// Check no sequence has been split:
	if( quoted_printable_decode((string) str_replace($seq_qp, "", $s_qp)) !== "" )
		throw new RuntimeException("QP test: detected split UTF-8 sequence");
	if( preg_match("/^abc\$/sD", "abc\r\n") === 1 )
		throw new RuntimeException();
}

function testParseContentType()
{
	// No Content-Type:
	$h = new Header();
	TU::test($h->getType(), "text/plain");
	TU::test($h->getCharset(), NULL);
	TU::test($h->getBoundary(), NULL);
	
	// Basic text:
	$h = new Header();
	$h->addLine("CONTENT-TYPE :   \t text/plain  \t");
	TU::test($h->getType(), "text/plain");
	TU::test($h->getCharset(), NULL);
	TU::test($h->getBoundary(), NULL);
	
	// Text with charset:
	$h = new Header();
	$h->addLine("Content-Type:   \t text/plain ; charset=CCC \t");
	TU::test($h->getType(), "text/plain");
	TU::test($h->getCharset(), "CCC");
	TU::test($h->getBoundary(), NULL);
	
	// Multipart:
	$h = new Header();
	$h->addLine("Content-Type: multipart/mixed; boundary=\"bbb\"");
	TU::test($h->getType(), "multipart/mixed");
	TU::test($h->getCharset(), NULL);
	TU::test($h->getBoundary(), "bbb");
	
	// Empty:
	$h = new Header();
	$h->addLine("Content-Type:");
	TU::test($h->getType(), "text/plain");
	TU::test($h->getCharset(), NULL);
	TU::test($h->getBoundary(), NULL);
	
	// Bad type:
	$h = new Header();
	$h->addLine("Content-Type: ????/????; ???=???");
	TU::test($h->getType(), "text/plain");
	TU::test($h->getCharset(), NULL);
	TU::test($h->getBoundary(), NULL);
	
	// Bad parameter name:
	$h = new Header();
	$h->addLine("Content-Type: xxx/xxx; ???=xxx");
	TU::test($h->getType(), "xxx/xxx");
	TU::test($h->getCharset(), NULL);
	TU::test($h->getBoundary(), NULL);
}

function testParseContentDisposition()
{
	// No Content-Disposition:
	$h = new Header();
	TU::test($h->isAttachment(), FALSE);
	TU::test($h->getFilename(), NULL);
	
	// Inline:
	$h = new Header();
	$h->addLine("Content-Disposition: inline");
	TU::test($h->isAttachment(), FALSE);
	TU::test($h->getFilename(), NULL);
	
	// Attachment:
	$h = new Header();
	$h->addLine("Content-Disposition: attachment");
	TU::test($h->isAttachment(), TRUE);
	TU::test($h->getFilename(), NULL);
	
	// Attachment + filename:
	$h = new Header();
	$h->addLine("Content-Disposition: attachment; filename=YourFile.pdf");
	TU::test($h->isAttachment(), TRUE);
	TU::test($h->getFilename(), "YourFile.pdf");
}

function testParseContentID()
{
	$h = new Header();
	TU::test($h->getContentID(), NULL);
	
	$h = new Header();
	$h->addLine("Content-ID:");
	TU::test($h->getContentID(), NULL);
	
	$h = new Header();
	$h->addLine("Content-ID: <abc>");
	TU::test($h->getContentID(), "abc");
}

function testGetEncoding()
{
	$h = new Header();
	TU::test($h->getEncoding(), "8bit");
	
	$h = new Header();
	$h->addLine("Content-Transfer-Encoding: xxxx");
	TU::test($h->getEncoding(), "8bit");
	
	$h = new Header();
	$h->addLine("Content-Transfer-Encoding: 7bit");
	TU::test($h->getEncoding(), "7bit");
	
	$h = new Header();
	$h->addLine("Content-Transfer-Encoding: 8bit");
	TU::test($h->getEncoding(), "8bit");
	
	$h = new Header();
	$h->addLine("Content-Transfer-Encoding: quoted-printable");
	TU::test($h->getEncoding(), "quoted-printable");
	
	$h = new Header();
	$h->addLine("Content-Transfer-Encoding: BASE64");
	TU::test($h->getEncoding(), "base64");
}

function testGetDate()
{
	$h = new Header();
	TU::test($h->getDate(), NULL);
	
	$h = new Header();
	$h->addLine("Date: xxxx");
	TU::test($h->getDate(), NULL);
	
	$h = new Header();
	$h->addLine("Date:   Fri,  29  Jun  2018  01:23:45 +1234 ");
	TU::test("".$h->getDate(), "2018-06-29T01:23:45.000+12:34");
	
	$h = new Header();
	$h->addLine("Date:   Fri,  29  Jun  2018  01:23:45 ");
	TU::test("".$h->getDate(), "2018-06-29T01:23:45.000+00:00");
	
	$h = new Header();
	$h->addLine("Date:29 Jun 2018 01:23");
	TU::test("".$h->getDate(), "2018-06-29T01:23:00.000+00:00");
	
	$h = new Header();
	$h->addLine("Date:29 Jun 2018 01:23 CEST");
	TU::test("".$h->getDate(), "2018-06-29T01:23:00.000+02:00");
	
	$h = new Header();
	$h->addLine("Date:29 Jun 2018 01:23 XXXX");
	TU::test("".$h->getDate(), "2018-06-29T01:23:00.000+00:00");
}

function main()
{
	test_quoted_printable_encode();
	testParseContentType();
	testParseContentDisposition();
	testParseContentID();
	testGetEncoding();
	testGetDate();
}

main();
