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
 * True: shows exceptions only, no logging nor feedback.
 * False: shows all.
 */
const AUTO_TEST = TRUE;

/**
 * @param string $mbox_content
 * @param string $exp
 * @throws IOException
 * @throws EmlFormatException
 */
function testFullEmailSample($mbox_content, $exp)
{
	$mbox = new ReaderFromTtBox( new StringInputStream($mbox_content) );
	$got = "";
	while( $mbox->next() ){
		$email = EmlParser::parse($mbox, NULL);
		$got .= "====\n$email";
		
//		echo "Another email:\n";
//		do {
//			$mbox->readLine();
//			$line = $mbox->getLine();
//			echo "> line="; var_dump($line);
//			if( $line === NULL ){
//				echo "THE END\n";
//				break;
//			}
//		} while(TRUE);
	}
	$mbox->close();
	if( $got !== $exp )
		throw new RuntimeException("test failed:\ngot = $got,\nexp = $exp\n");
}

/**
 * @param string $mbox_content
 * @param string $exp
 * @throws IOException
 * @throws EmlFormatException
 */
function testHeaderOnlySample($mbox_content, $exp)
{
	$mbox = new ReaderFromTtBox( new StringInputStream($mbox_content) );
	$got = "";
	while( $mbox->next() ){
		$header = EmlParser::parseHeader($mbox, NULL);
		$got .= "====\n$header";
	}
	$mbox->close();
	if( $got !== $exp )
		throw new RuntimeException("test failed:\ngot = $got,\nexp = $exp");
}

/**
 * @param string $mbox_path
 * @throws IOException
 * @throws EmlFormatException
 */
function testActualMboxFile($mbox_path)
{
	$mbox = new ReaderFromTtBox( new FileInputStream( File::fromLocaleEncoded($mbox_path) ) );
	while( $mbox->next() ){
		try {
			// Header only:
//			$header = EmlParser::parseHeader($mbox, NULL);
//			$d = $header->getDate();
//			echo "Date: $d\n";
//			echo "Subject: ", $header->getSubject(), "\n";
			
			// Full email parsing:
			$email = EmlParser::parse($mbox, NULL);
			if( ! AUTO_TEST ){
				echo "Subject: ", $email->header->getSubject(), "\n";
				echo "Size: ", $email->getContentSize(), "\n";
			}
		}
		catch(EmlFormatException $e){
			error_log("Failed parsing message in $mbox_path, $mbox:\n$e");
		}
		
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
		"Subject: Message 1\n\nabc\n.\n",
		"====\nSubject: Message 1\n\nabc\n");
	
	// 2 msgs with un-quoting required:
	testFullEmailSample(
			
		"Subject: Message 1\n"
		."\n"
		."..zzz\n"
		.".\n"
		."Subject: Message 2\n"
		."\n"
		."abc\n"
		.".\n",
		
		"====\n"
		."Subject: Message 1\n"
		."\n"
		.".zzz\n"
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
		"Subject: Message 1\n\nabc\n.\n",
		"====\nSubject: Message 1\n");
	
	// 2 msgs with un-quoting required:
	testHeaderOnlySample(
			
		"Subject: Message 1\n"
		."\n"
		."..zzz\n"
		.".\n"
		."Subject: Message 2\n"
		."\n"
		."abc\n"
		.".\n",
		
		"====\n"
		."Subject: Message 1\n"
		."====\n"
		."Subject: Message 2\n"
	);
	
// Tests disable as they depends on the path to the mbox on my PC :-)
// To perform these tests, change the paths and uncomment the following line:
//	testActualMboxFile("C:/Users/UmbertoSalsi/cygwinhome/.tt/archive/archiviati/msg-archive-20151231");
//	testActualMboxFile("C:/Users/UmbertoSalsi/cygwinhome/.tt/archive/archiviati/msg-archive-20141231");
//	testActualMboxFile("C:/Users/UmbertoSalsi/cygwinhome/.tt/archive/archiviati/msg-archive-20131231");
//	testActualMboxFile("C:/Users/UmbertoSalsi/cygwinhome/.tt/archive/archiviati/msg-archive-20031231");
//	testActualMboxFile("C:/Users/UmbertoSalsi/cygwinhome/.tt/archive/archiviati/msg-archive-20021231");
//	testActualMboxFile("C:/Users/UmbertoSalsi/cygwinhome/.tt/archive/archiviati/msg-archive-20011231");
}

main();
