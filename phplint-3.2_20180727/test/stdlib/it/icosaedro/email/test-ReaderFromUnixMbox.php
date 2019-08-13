<?php

namespace it\icosaedro\email;

require_once __DIR__ . "/../../../../../stdlib/autoload.php";
require_once __DIR__ . "/../../../../../stdlib/errors.php";

use Exception;
use RuntimeException;
use it\icosaedro\io\IOException;
use it\icosaedro\io\FileInputStream;
use it\icosaedro\io\File;
use it\icosaedro\io\StringInputStream;

/**
 * @param string $mbox_content
 * @param string $exp
 * @throws IOException
 * @throws EmlFormatException
 */
function testFullEmailSample($mbox_content, $exp)
{
	$mbox = new ReaderFromUnixMbox( new StringInputStream($mbox_content) );
	$got = "";
	while( $mbox->next() ){
		
//		echo "Next message:\n";
//		do {
//			$mbox->readLine();
//			$line = $mbox->getLine();
//			echo "==> ";  var_dump($line);
//			if( $line === NULL ){
//				echo "End of the message.\n";
//				break;
//			}
//		} while(TRUE);
		
		$email = EmlParser::parse($mbox, NULL);
		$got .= "====\n$email";
	}
	$mbox->close();
	if( $got !== $exp ){
		echo "got = $got,\nexp = $exp\n";
		throw new RuntimeException("failed");
	}
}

/**
 * @param string $mbox_content
 * @param string $exp
 * @throws IOException
 * @throws EmlFormatException
 */
function testHeaderOnlySample($mbox_content, $exp)
{
	$mbox = new ReaderFromUnixMbox( new StringInputStream($mbox_content) );
	$got = "";
	while( $mbox->next() ){
		$header = EmlParser::parseHeader($mbox, NULL);
		$got .= "====\n$header";
	}
	$mbox->close();
	if( $got !== $exp )
		echo "got = $got,\nexp = $exp\n";
}

/**
 * @param string $mbox_path
 * @throws IOException
 * @throws EmlFormatException
 */
function testActualMboxFile($mbox_path)
{
	$mbox = new ReaderFromUnixMbox( new FileInputStream( File::fromLocaleEncoded($mbox_path) ) );
	while( $mbox->next() ){
		
		// Basic testing of the scanner:
//		echo "Next message:\n";
//		do {
//			$mbox->readLine();
//			$line = $mbox->getLine();
//			echo "==> ";  var_dump($line);
//			if( $line === NULL ){
//				echo "End of the message.\n";
//				break;
//			}
//		} while(TRUE);
		
		// Header only:
//		$header = EmlParser::parseHeader($mbox, NULL);
//		echo "Subject: ", $header->getSubject(), "\n";
			
		// Full email parsing:
		$email = EmlParser::parse($mbox, NULL);
		echo "Subject: ", $email->header->getSubject(), "\n";
		echo "Size: ", $email->getContentSize(), "\n";
		
	}
	$mbox->close();
}

/**
 * @throws Exception
 */
function main()
{
	// Empty mbox:
	testFullEmailSample(
		"",
		"");
	
	// 1 msg:
	testFullEmailSample(
		"From xxx\nSubject: Message 1\n\nabc\n\n",
		"====\nSubject: Message 1\n\nabc\n");
	
	// 2 msgs with un-quoting required (UN-QUOTING NOT POSSIBLE!):
	testFullEmailSample(
			
		"From xxx\n"
		."Subject: Message 1\n"
		."\n"
		.">From zzz\n"
		.">>From zzz\n"
		.">>>From zzz\n"
		."\n"
		."From xxx\n"
		."Subject: Message 2\n"
		."\n"
		."abc\n\n",
		
		"====\n"
		."Subject: Message 1\n"
		."\n"
		.">From zzz\n"
		.">>From zzz\n"
		.">>>From zzz\n"
		."====\n"
		."Subject: Message 2\n"
		."\n"
		."abc\n"
	);
	
	/*
	 * Same sample above but scanning headers only:
	 */
	
	// Empty mbox:
	testHeaderOnlySample(
		"",
		"");
	
	// 1 msg:
	testHeaderOnlySample(
		"From xxx\nSubject: Message 1\n\nabc\n\n",
		"====\nSubject: Message 1\n");
	
	// 2 msgs with un-quoting required (UN-QUOTING NOT POSSIBLE!):
	testHeaderOnlySample(
			
		"From xxx\n"
		."Subject: Message 1\n"
		."\n"
		.">From zzz\n"
		.">>From zzz\n"
		.">>>From zzz\n"
		."\n"
		."From xxx\n"
		."Subject: Message 2\n"
		."\n"
		."abc\n\n",
		
		"====\n"
		."Subject: Message 1\n"
		."====\n"
		."Subject: Message 2\n"
	);
	
	// 1 msg, missing ending empty line:
	testHeaderOnlySample(
		"From xxx\nSubject: Message 1\n\nabc\n",
		"====\nSubject: Message 1\n");
	
	// 2 msgs with missing ending empty line:
	testFullEmailSample(
			
		"From xxx\n"
		."Subject: Message 1\n"
		."\n"
		."abc\n"
		."From xxx\n"
		."Subject: Message 2\n"
		."\n"
		."def\n",
		
		"====\n"
		."Subject: Message 1\n"
		."\n"
		."abc\n"
		."====\n"
		."Subject: Message 2\n"
		."\n"
		."def\n"
	);
	
	// Test disable as it depends on the path to the mbox on my PC :-)
	// To perform this test, change the paths and uncomment the following line:
//	testActualMboxFile("/var/spool/mail/salsi");
}

main();
