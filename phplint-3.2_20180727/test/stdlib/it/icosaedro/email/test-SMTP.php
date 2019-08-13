<?php
namespace it\icosaedro\email;
require_once __DIR__ . "/../../../../../stdlib/all.php";
use it\icosaedro\io\IOException;

/**
 * True: shows exceptions only, no logging nor feedback.
 * False: shows all.
 */
const AUTO_TEST = TRUE;

/**
 * @throws IOException
 */
function main()
{
	$from = "phplint@icosaedro.it";
	$to = "email-testing@icosaedro.it";
	$subject = "Testing SMTP and POP3 classes, time=" . time();
	$host = "castorino.icosaedro.it:587"
		."?timeout=10"
		."&security=ssl"
		."&ca_certificate_path=" . __DIR__ . "/castorino-stunnel-ca.crt";
	$hp = new ConnectionParameters($host);
	SMTP::$debug = ! AUTO_TEST;
	$smtp = new SMTP($hp);
	$smtp->mail($from);
	$smtp->recipient($to);
	$smtp->data_open();
	$smtp->data_write("From: $from\n");
	$smtp->data_write("To: $to\n");
	$smtp->data_write("Content-Type: text/plain; charser=ASCII\n");
	$smtp->data_write("MIME-Version: 1.0\n");
	$smtp->data_write("Subject: $subject\n");
	$smtp->data_write("\n");
	$smtp->data_write("This is a test of the SMTP class.\nBye.\n");
	$smtp->data_close();
	$smtp->quit();
}

main();
