<?php

namespace it\icosaedro\io;

require_once __DIR__ . "/../../../../../stdlib/autoload.php";
require_once __DIR__ . "/../../../../../stdlib/utf8.php";
use it\icosaedro\utils\UString;
use it\icosaedro\utils\TestUnit as TU;
use RuntimeException;

/**
 * This source file is itself a UTF-8 encoded sample WITHOUT BOM.
 * 
 * The following text copied from a japanese web site, no idea of what does it mean:
 * 
 * このページへのブックマークコメントはまだありません。
 * 
 * The following text copied from a german web site, no idea of what does it mean:
 * 
 * Das ist die größte und schnellste
 * 
 * From Wikipedia (russian):
 * 
 * Избранная статья - Литургический кодекс
 */
class testReader extends TU {
	
	function run() /*. throws \Exception .*/
	{
		$r = new Reader(new StringInputStream(""), "UTF-8");
		TU::test($r->read(100), NULL);
		
		$r = new Reader(new StringInputStream("A"), "UTF-8");
		TU::test($r->read(100)->toUTF8(), "A");
		
		$r = new Reader(new StringInputStream("AB"), "UTF-8");
		TU::test($r->read(100)->toUTF8(), "AB");
		
		$r = new Reader(new StringInputStream(u("\\u20ac")->toUTF8()), "UTF-8");
		TU::test($r->read(100)->toUTF8(), u("\\u20ac")->toUTF8());
		
		$line_end = UString::fromASCII("\r\n");
		
		$toUnlink = /*. (File[int]) .*/ array();
		
		// Copy myself and write into UCS2BE with BOM file.
		$fn = File::fromLocaleEncoded(__FILE__);
		$r = new Reader( new FileInputStream($fn), "UTF-8" );
		//echo "Reader: ", $r->getEncoding(), "\n";
		$fn = File::fromLocaleEncoded(__FILE__ . "-UCS2BE-BOM.txt");
		$toUnlink[] = $fn;
		$w = new Writer( new FileOutputStream($fn), "UCS-2BE", TRUE, $line_end);
		while( ($line = $r->readLine()) !== NULL ){
			//echo "> $line\n";
			$w->writeLine($line);
		}
		
		// Read UCS2BE with BOM created above and write into UTF8 with BOM file:
		$r = new Reader(
			new FileInputStream(File::fromLocaleEncoded(__FILE__ . "-UCS2BE-BOM.txt")) );
		if( ! $r->foundBOM() )
			throw new RuntimeException();
		//echo "Reader: ", $r->getEncoding(), "\n";
		$fn = File::fromLocaleEncoded(__FILE__ . "-UTF8-BOM.txt");
		$toUnlink[] = $fn;
		$w = new Writer( new FileOutputStream($fn), "UTF-8", TRUE, $line_end);
		while( ($line = $r->readLine()) !== NULL ){
			//echo "> $line\n";
			$w->writeLine($line);
		}
		
		// Read UTF8 with BOM created above and write ISO88591 file:
		$r = new Reader(
			new FileInputStream(File::fromLocaleEncoded(__FILE__)),
			"UTF-8" );
		if( $r->foundBOM() )
			throw new RuntimeException();
		//echo "Reader: ", $r->getEncoding(), "\n";
		$fn = File::fromLocaleEncoded(__FILE__ . "-ISO88591.txt");
		$toUnlink[] = $fn;
		$w = new Writer( new FileOutputStream($fn), "ISO-8859-1", TRUE, $line_end);
		while( ($line = $r->readLine()) !== NULL ){
			//echo "> $line\n";
			$w->writeLine($line);
		}
		
		// Write myself into UTF8 without BOM:
		$r = new Reader(
			new FileInputStream(File::fromLocaleEncoded(__FILE__)),
			"UTF-8" );
		//echo "Reader: ", $r->getEncoding(), "\n";
		$fn = File::fromLocaleEncoded(__FILE__ . "-UTF8.txt");
		$toUnlink[] = $fn;
		$w = new Writer( new FileOutputStream($fn), "utf-8", FALSE);
		while( ($line = $r->readLine()) !== NULL ){
			//echo "> $line\n";
			$w->writeLine($line);
		}
		
		
//		$t = new \it\icosaedro\utils\Timer();
//		$t->start();
//		$r = new Reader(
//			new FileInputStream(File::fromLocaleEncoded("/home/salsi/verylong.txt")),
//			"UTF-8" );
//		echo "Reader: ", $r->getEncoding(), "\n";
//		while( ($line = $r->readLine()) !== NULL ){
//			//echo "> $line\n";
//		}
//		echo "Time: $t\n";
		
		foreach($toUnlink as $fn)
			$fn->delete();
	}
	
}


$tu = new testReader();
$tu->start();
