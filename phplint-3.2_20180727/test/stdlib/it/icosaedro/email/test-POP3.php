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
 * Retrieve all the messages from the test mailbox.
 * Do not let the contents of the mailbox to grow too much.
 * @throws IOException
 */
function main()
{
	$host = "castorino.icosaedro.it:995"
		."?timeout=10"
		."&security=ssl"
		."&ca_certificate_path=" . __DIR__ . "/castorino-stunnel-ca.crt"
		."&user_authentication_method=PLAIN"
		."&user_name=email-testing"
		."&user_password=email-testing";
	$cp = new ConnectionParameters($host);
	POP3::$debug = ! AUTO_TEST;
	$pop3 = new POP3($cp);
	$capa = $pop3->getCapabilities();
	if( ! AUTO_TEST )
		echo "Capabilities: $capa\n";
	$sizes = $pop3->listSizes();
	$uidl = $pop3->listUniqueIdentifiers();
	$n = count($sizes);
	
	// Download and show all the messages:
	foreach($sizes as $msg_no => $msg_size){
		if( ! AUTO_TEST )
			echo "==> RETRIEVING MESSAGE NO. $msg_no, $msg_size bytes, UID ",
				$uidl[$msg_no], ":\n";
		$pop3->retrieveMessage($msg_no, 0);
		do {
			$line = $pop3->getLine();
			if( $line === NULL )
				break;
			if( ! AUTO_TEST )
				echo "$msg_no => $line\n";
		} while(TRUE);
	}
	
	// Do not let the testing mailbox grow too much -- leave last 9 msgs:
	$deleted = 0;
	foreach($sizes as $msg_no => $msg_size){
		if( $n - $deleted <= 9 )
			break;
		if( ! AUTO_TEST )
			echo "=> Deleting message no. $msg_no\n";
		$pop3->deleteMessage($msg_no);
		$deleted++;
	}
	
	$pop3->quit();
	
	if( ! AUTO_TEST )
		echo "Total $n messages found, $deleted deleted.\n";
}

main();
