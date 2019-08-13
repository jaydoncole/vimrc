<?php

require_once __DIR__ . "/../../../../../stdlib/all.php";
use it\icosaedro\email\Mailer;
use it\icosaedro\email\SMTP;
use it\icosaedro\io\IOException;
use it\icosaedro\io\StringOutputStream;
use it\icosaedro\io\StringInputStream;
use it\icosaedro\io\LineInputWrapper;
use it\icosaedro\utils\SimpleDiff;

/**
 * True: shows exceptions only, no logging nor feedback.
 * False: shows all.
 */
const AUTO_TEST = TRUE;

/**
 * Compares the message currently composed with the expected text.
 * @param Mailer $m Composed message.
 * @param string $exp Expected result; variable parts are blanked.
 * @throws Exception
 * @throws RuntimeException Resulting message does not match expected.
 */
function testResult($m, $exp)
{
	// Adjust EOL in the expected string, as it depends on the text editor:
	$exp = (string) str_replace("\r", "", $exp);
	$exp = (string) str_replace("\n", "\r\n", $exp);
	
	// Adjust got message by blanking variable parts:
	$out = new StringOutputStream();
	$m->sendByStream($out, TRUE);
	$got = "$out";
	// Message-ID is Base64 of 8+ bytes + "@host":
	$got = preg_replace("/Message-ID: <[0-9a-zA-Z+\\/=]{12,}@([^>\r]+)>/", "Message-ID: <...@\${1}>", $got);
	$got = preg_replace("/Date: [^\r]*/", "Date: ...", $got);
	// Boundary string is Base64 of 8+ bytes:
	$got = preg_replace("/=_[0-9a-zA-Z+\\/=]{12,}/", "...", $got);
	
	if( $got === $exp )
		return;
	
	echo "Got:-----------------------\n$got";
	echo "Expected:------------------\n$exp";
	echo "---------------------------\n";
	
	// Show line no. and column no. of the first different byte:
	$i = 0;
	$col_no = 1;
	$line_no = 1;
	while($i < strlen($exp) && $i < strlen($got) && $exp[$i] === $got[$i] ){
		if( ord($exp[$i]) == 10 ){
			$col_no = 0;
			$line_no++;
		}
		$col_no++;
		$i++;
	}
	echo "First difference at line $line_no, column $col_no: got byte: ";
	if( $i < strlen($got) )
		echo ord($got[$i]);
	else
		echo "(end of the string)";
	echo ", exp byte ";
	if( $i < strlen($exp) )
		echo ord($exp[$i]);
	else
		echo "(end of the string)";
	
	echo "\n";
	
	// Diff line by line:
	$out = new StringOutputStream();
	$got_stream = new LineInputWrapper(new StringInputStream($got));
	$exp_stream = new LineInputWrapper(new StringInputStream($exp));
	SimpleDiff::areEqual($got_stream, $exp_stream, $out);
	echo "Differences:\n$out\n";
	throw new RuntimeException("failed");
}


/**
 * @throws Exception
 */
function testEmptyMsg()
{
	$m = new Mailer();
	$m->setHostName("HostName");
	$exp = <<< EOT
From: Root User <root@localhost>
Subject: 
Message-ID: <...@HostName>
Date: ...
MIME-Version: 1.0
Content-Type: text/plain
Content-Transfer-Encoding: 7bit



EOT;
	testResult($m, $exp);
}


/**
 * @throws Exception
 */
function testEnvelopeFull()
{
	$m = new Mailer();
	$m->setSender("sender@domain");
	$m->setFrom("from@domain", "FromName");
	$m->setConfirmReadingTo("confirm@domain");
	$m->setHostName("HostName");
	$m->setSubject("TheSubject");
	$m->addAddress("to1@domain", "To1");
	$m->addAddress("to2@domain", "To2");
	$m->addCC("cc1@domain", "Cc1");
	$m->addCC("cc2@domain", "Cc2");
	$m->addBCC("bcc1@domain", "Bcc1");
	$m->addBCC("bcc2@domain", "Bcc2");
	$exp = <<< EOT
From: FromName <from@domain>
To: To1 <to1@domain>, To2 <to2@domain>
Cc: Cc1 <cc1@domain>, Cc2 <cc2@domain>
Bcc: Bcc1 <bcc1@domain>, Bcc2 <bcc2@domain>
Subject: TheSubject
Disposition-Notification-To: <confirm@domain>
Message-ID: <...@HostName>
Date: ...
MIME-Version: 1.0
Content-Type: text/plain
Content-Transfer-Encoding: 7bit



EOT;
	testResult($m, $exp);
}


/**
 * @throws Exception
 */
function testText7Bit()
{
	$m = new Mailer();
	$m->setHostName("HostName");
	$m->setTextMessage("Text message with ASCII only characters and lines not longer than 998 bytes can\nbe safely sent using the 7bit encoding.");
	$exp = <<< EOT
From: Root User <root@localhost>
Subject: 
Message-ID: <...@HostName>
Date: ...
MIME-Version: 1.0
Content-Type: text/plain; charset="UTF-8"
Content-Transfer-Encoding: 7bit

Text message with ASCII only characters and lines not longer than 998 bytes can
be safely sent using the 7bit encoding.

EOT;
	testResult($m, $exp);
}


/**
 * @throws Exception
 */
function testText8Bit()
{
	$m = new Mailer();
	$m->setHostName("HostName");
	$m->setTextMessage("Text message with non-ASCII characters like àèìòù and lines not longer than 998 bytes can be safely sent using the 8bit encoding.");
	$exp = <<< EOT
From: Root User <root@localhost>
Subject: 
Message-ID: <...@HostName>
Date: ...
MIME-Version: 1.0
Content-Type: text/plain; charset="UTF-8"
Content-Transfer-Encoding: 8bit

Text message with non-ASCII characters like àèìòù and lines not longer than 998 bytes can be safely sent using the 8bit encoding.

EOT;
	testResult($m, $exp);
}


/**
 * @throws Exception
 */
function testTextQP()
{
	$m = new Mailer();
	$m->setHostName("HostName");
	$m->setTextMessage("Text message with lines longer than 998 bytes cannot be safely sent over the net and must be encoded as quoted-printable to preserve the integrity of these lines. Quoted-printable must be used even with simple ASCII-only text messages like this one. By using QP, the readibility of the message is more or less preserved too, although nowadays nobody use dumb terminals anymore. Text message with lines longer than 998 bytes cannot be safely sent over the net and must be encoded as quoted-printable to preserve the integrity of these lines. Quoted-printable must be used even with simple ASCII-only text messages like this one. By using QP, the readibility of the message is more or less preserved too, although nowadays nobody use dumb terminals anymore. Text message with lines longer than 998 bytes cannot be safely sent over the net and must be encoded as quoted-printable to preserve the integrity of these lines. Quoted-printable must be used even with simple ASCII-only text messages like this one. By using QP, the readibility of the message is more or less preserved too, although nowadays nobody use dumb terminals anymore.");
	$exp = <<< EOT
From: Root User <root@localhost>
Subject: 
Message-ID: <...@HostName>
Date: ...
MIME-Version: 1.0
Content-Type: text/plain; charset="UTF-8"
Content-Transfer-Encoding: quoted-printable

Text message with lines longer than 998 bytes cannot be safely sent over th=
e net and must be encoded as quoted-printable to preserve the integrity of =
these lines. Quoted-printable must be used even with simple ASCII-only text=
 messages like this one. By using QP, the readibility of the message is mor=
e or less preserved too, although nowadays nobody use dumb terminals anymor=
e. Text message with lines longer than 998 bytes cannot be safely sent over=
 the net and must be encoded as quoted-printable to preserve the integrity =
of these lines. Quoted-printable must be used even with simple ASCII-only t=
ext messages like this one. By using QP, the readibility of the message is =
more or less preserved too, although nowadays nobody use dumb terminals any=
more. Text message with lines longer than 998 bytes cannot be safely sent o=
ver the net and must be encoded as quoted-printable to preserve the integri=
ty of these lines. Quoted-printable must be used even with simple ASCII-onl=
y text messages like this one. By using QP, the readibility of the message =
is more or less preserved too, although nowadays nobody use dumb terminals =
anymore.

EOT;
	testResult($m, $exp);
}


/**
 * @throws Exception
 */
function testBodyHtml()
{
	$m = new Mailer();
	$m->setHostName("HostName");
	$m->setHtmlMessage("<html><body></body></html>");
	$exp = <<< EOT
From: Root User <root@localhost>
Subject: 
Message-ID: <...@HostName>
Date: ...
MIME-Version: 1.0
Content-Type: text/html; charset="UTF-8"
Content-Transfer-Encoding: 7bit

<html><body></body></html>

EOT;
	testResult($m, $exp);
}


/**
 * @throws Exception
 */
function testBodyTextPlusAttachment()
{
	$m = new Mailer();
	$m->setHostName("HostName");
	$m->setTextMessage("Some text, no line ending on pourpose to check boundary.");
	$m->addAttachmentFromString("xxx", "application/pdf", NULL, "report.pdf");
	$exp = <<< EOT
From: Root User <root@localhost>
Subject: 
Message-ID: <...@HostName>
Date: ...
MIME-Version: 1.0
Content-Type: multipart/mixed; boundary="..."

--...
Content-Type: text/plain; charset="UTF-8"
Content-Transfer-Encoding: 7bit

Some text, no line ending on pourpose to check boundary.
--...
Content-Type: application/pdf
Content-Transfer-Encoding: base64
Content-Disposition: attachment; filename="=?UTF-8?Q?report=2Epdf?="

eHh4
--...--

EOT;
	testResult($m, $exp);
}

/**
 * @throws IOException
 */
function testSendBySMTP()
{
	$from = "phplint@icosaedro.it";
	$subject = "Testing Mailer::sendBySMTP(), time=" . time();
	$text = "Testing the Mailer::sendBySMTP() transport method.";
	
	$hosts = "castorino.icosaedro.it:587"
		."?timeout=10"
		."&security=ssl"
		."&ca_certificate_path=" . __DIR__ . "/castorino-stunnel-ca.crt";
	SMTP::$debug = ! AUTO_TEST;
	$m = new Mailer();
	$m->setFrom($from);
	
	$valid_recipients = array("email-testing@icosaedro.it");
	foreach($valid_recipients as $address)
		$m->addAddress($address);
	
	// These must be rejected by the address validator:
	$invalid_recipients = array(
		"InvalidCharacters<>",
		"Invalid Characters <>",
		"MissingDomain@",
		"@MissingUser"
	);
	foreach($invalid_recipients as $address){
		$detected = FALSE;
		try {
			$m->addAddress($address);
		}
		catch(InvalidArgumentException $e){
			$detected = TRUE;
		}
		if( ! $detected )
			throw new RuntimeException($address);
	}
	
	// These must be rejected by the SMTP server:
	$undeliverable_recipients = array(
		"NoThisUser@icosaedro.it", // unknown user
		"Guy@ExternalDomain.it" // relay not permitted to unauthorized user
	);
	foreach($undeliverable_recipients as $address)
		$m->addAddress($address);
	
	$m->setSubject($subject);
	$m->setTextMessage($text);
	$rejected_recipients = $m->sendBySMTP($hosts, FALSE);
	
	// Check expected rejected recipients:
	if( ! AUTO_TEST ){
		echo "Rejected recipients: ";
		var_dump($rejected_recipients);
	}
	$exp_rejected = " " . implode(" ", $undeliverable_recipients);
	$got_rejected = "";
	foreach($rejected_recipients as $r)
		$got_rejected .= " " . $r[0];
	if( $exp_rejected !== $got_rejected )
		throw new RuntimeException("\nexp rejected = $exp_rejected\ngot rejected = $got_rejected");
}


/**
 * @throws Exception
 */
function main()
{
	testEmptyMsg();
	testEnvelopeFull();
	testText7Bit();
	testText8Bit();
	testTextQP();
	testBodyHtml();
	testBodyTextPlusAttachment();
	testSendBySMTP();
}


main();
