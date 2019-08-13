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
	POP3::$debug = FALSE;
	$pop3 = new POP3($cp);
	
	$mbox = new ReaderFromPOP3($pop3);
	while($mbox->next()){
		if( ! AUTO_TEST ){
			echo "===> NEXT MESSAGE:\n";
			echo "     size: ", $mbox->getSize(), " bytes\n";
			echo "     uid:  ", $mbox->getUniqueIdentifier(), "\n";
		}
		do {
			$mbox->readLine();
			$line = $mbox->getLine();
			if( $line === NULL )
				break;
			if( ! AUTO_TEST )
				echo "$line\n";
		} while(TRUE);
	}
	$mbox->close();
	$pop3->quit();
}

main();
