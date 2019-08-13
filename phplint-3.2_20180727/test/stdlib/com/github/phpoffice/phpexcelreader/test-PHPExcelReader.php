<?php

require_once __DIR__ . "/../../../../../../stdlib/all.php";

use com\github\phpoffice\phpexcelreader\PHPExcelReader;
use com\github\phpoffice\phpexcelreader\Coordinates;
use com\github\phpoffice\phpexcelreader\Cell;
use com\github\phpoffice\phpexcelreader\SharedDate;
use com\github\phpoffice\phpexcelreader\Excel5Reader;
use com\github\phpoffice\phpexcelreader\Excel2003XMLReader;
use com\github\phpoffice\phpexcelreader\Excel2007Reader;
use com\github\phpoffice\phpexcelreader\OOCalcReader;
use com\github\phpoffice\phpexcelreader\CSVReader;
use com\github\phpoffice\phpexcelreader\PHPExcelException;
use it\icosaedro\io\File;
use it\icosaedro\io\FileInputStream;
use it\icosaedro\io\FileOutputStream;
use it\icosaedro\io\Reader;
use it\icosaedro\io\Writer;
use it\icosaedro\utils\UString;
use it\icosaedro\utils\TestUnit as TU;

/**
 * If true we are running as test script: only fatal errors are displayed on stderr.
 * If false we are interactive: display each processed file, the result of the
 * parsing and more.
 */
const AUTO_TEST = TRUE;

// Unix epoch expressed as Excel timestamp, that is number of days elapsed between
// 1899-12-31 and 1970-01-01 plus 1 (Lotus 123 bug assumed 1900 as leap year):
const EXCEL_TO_UNIX_EPOCH = 25569.0;

//// Sample of code for the DocBlock of the PHPExcelReader class:
//$workbook = PHPExcelReader::load("excel2007-example-alltypes.xlsx");
//foreach($workbook->getAllSheets() as $worksheet){
//	echo $worksheet->getTitle(), ":\n";
//	$rows = $worksheet->getHighestRow();
//	$cols = $worksheet->getHighestColumnNumber();
//	for($r = 1; $r <= $rows; $r++){
//		for($c = 1; $c <= $cols; $c++){
//			$cell = $worksheet->getCellByColumnAndRow($c, $r);
//			$coord = $cell->getCoordinate();
//			$humanReadable = $cell->getFormattedValue();
//			echo "$coord: $humanReadable\n";
//		}
//	}
//}
//exit(0);

//// Sample code for the CSVReader class DocBLock:
//$reader = new CSVReader();
//$reader->setInputEncoding("Windows-1252");
//$reader->setDelimiter(";");
//$workbook = $reader->load("csv-sample-simple.csv");
//echo $workbook;
//exit(0);


/**
 * Reads and displays the content of an Excel file.
 * @param string $fn
 * @param string $exp_exception Expected exception name, or NULL.
 * @throws Exception
 */
function test($fn, $exp_exception)
{
	if( ! AUTO_TEST ) echo "\n\nReading ", $fn, ":\n";
	
	// determines suitable reader for this file:
	try {
	$reader = PHPExcelReader::createReaderForFile($fn);
	}
	catch(Exception $e){
		if( $exp_exception === get_class($e) ){
			if( ! AUTO_TEST )
				echo "Expected exception: " . $e->getMessage() . "\n";
		}
		return;
	}
//	if( $exp_exception !== NULL )
//		throw new RuntimeException("expected $exp_exception but got none!");
	
	if( ! AUTO_TEST ) echo "using reader: ", get_class($reader), "\n";
	
	// set specific reader's options:
	if( $reader instanceof CSVReader ){
		$csv_reader = cast(CSVReader::class, $reader);
		$csv_reader->setInputEncoding("UTF-8");
	}
	
	// read the file:
	$workbook = $reader->load($fn);
	
	if( ! AUTO_TEST ) echo $workbook;
}




/**
 * @throws Exception
 */
function allTests()
{
	// Trick to force PHPLint to parse all the readers. The "if(FALSE)" avoids
	// to trigger class autoloading at runtime when not really required; PHPLint
	// will parse all anyway.
	if(FALSE){
		Excel5Reader::class;
		Excel2003XMLReader::class;
		Excel2007Reader::class;
		OOCalcReader::class;
		CSVReader::class;
	}

	// var_export() uses the serialize_precision parameter of php.ini rather than
	// the precision parameter -- avoid too many useless trailing decimal digits:
	ini_set('serialize_precision', '14');
	
	// Coordinates class test.
	// =======================
	TU::test(Coordinates::columnName(1), 'A');
	TU::test(Coordinates::columnName(26), 'Z');
	TU::test(Coordinates::columnName(27), 'AA');
	TU::test(Coordinates::columnName(26 + 26*26), 'ZZ');
	TU::test(Coordinates::columnName(26 + 26*26 + 1), 'AAA');
	TU::test(Coordinates::columnName(26 + 26*26 + 26*26*26), 'ZZZ');
	
	TU::test(Coordinates::columnNumber('A'), 1);
	TU::test(Coordinates::columnNumber('Z'), 26);
	TU::test(Coordinates::columnNumber('AA'), 27);
	TU::test(Coordinates::columnNumber('ZZ'), 26 + 26*26);
	TU::test(Coordinates::columnNumber('AAA'), 26 + 26*26 + 1);
	TU::test(Coordinates::columnNumber('ZZZ'), 26 + 26*26 + 26*26*26);

	// Test date and time functions.
	// =============================
	SharedDate::setExcelCalendar(SharedDate::CALENDAR_WINDOWS_1900); // already the default

	$ts = SharedDate::PHPToExcel(86400); // 1970-01-02 00:00:00 GMT
	TU::test($ts, EXCEL_TO_UNIX_EPOCH + 1.0);

	$dt = SharedDate::ExcelToDateTime(EXCEL_TO_UNIX_EPOCH + 1.0);
	TU::test($dt->format("c"), "1970-01-02T00:00:00+00:00");

	// Parse date: missing time and TZ --> assumes 00:00:00 GMT:
	$ts = SharedDate::parseDateTime("1970-01-02");
	TU::test($ts, 86400);

	// Parse date: missing TZ --> assumes GMT:
	$ts = SharedDate::parseDateTime("1970-01-02 01:02:03.000");
	TU::test($ts, 86400 + 3600 + 120 + 3);

	// Parse date: missing time --> assumes 00:00:00:
	$ts = SharedDate::parseDateTime("1970-01-02 GMT");
	TU::test($ts, 86400);

	// Parse date: missing date --> assumes 1970-01-01:
	$ts = SharedDate::parseDateTime("01:00:00 GMT");
	TU::test($ts, 3600);

	// Parse date: missing date and TZ --> assumes 1970-01-01 GMT:
	$ts = SharedDate::parseDateTime("01:00:00");
	TU::test($ts, 3600);

	// Parse date: tested lower limit on 32-bits PHP:
	$ts = SharedDate::parseDateTime("1901-12-14");
	TU::test($ts, -2147472000);

	// Parse date: tested upper limit on 32-bits PHP:
	$ts = SharedDate::parseDateTime("2038-01-19");
	TU::test($ts, 2147472000);

	//$dt = SharedDate::ExcelToDateTime(1.0);
	//TU::test($dt->format("c"), "1900-01-01T00:00:00+00:00");

	// Example from https://support.microsoft.com/en-us/kb/214094:
	$dt = SharedDate::ExcelToDateTime(39452.0);
	TU::test($dt->format("c"), "2008-01-05T00:00:00+00:00");

	// Examples from http://www.brighthub.com/computing/windows-platform/articles/26358.aspx:
	$dt = SharedDate::ExcelToDateTime(32331.06);
	TU::test($dt->format("c"), "1988-07-07T01:26:24+00:00");
	$dt = SharedDate::ExcelToDateTime(0.5784722222);
	TU::test($dt->format("c"), "1970-01-01T13:53:00+00:00");
	$dt = SharedDate::ExcelToDateTime(40074.2916666667);
	TU::test($dt->format("c"), "2009-09-18T07:00:00+00:00");

	// set CWD so that all files and error msgs will have relative path, and
	// the report be independent from the installation dir:
	chdir(__DIR__);
	
	// CSV format
	// ==========
	test("empty-file-unknown-extension", NULL);
	test("csv-sample-unknown-extension", NULL);
	test("binary-file-unknown-extension", NULL);
	test("binary-file.csv", NULL);
	test("csv-sample-utf8-bom.csv", NULL);
	
	// generate a CSV file with UTF-16LE and BOM:
	$in_name = File::fromLocaleEncoded("csv-sample-utf8-bom.csv", File::getCWD());
	$in = new FileInputStream($in_name);
	$r = new Reader($in, "UTF-8");
	$out_name = File::fromLocaleEncoded("csv-sample-utf16le-bom.csv", File::getCWD());
	$out = new FileOutputStream($out_name);
	$w = new Writer($out, "UTF-16LE", TRUE, UString::fromASCII("\r\n"));
	while( ($line = $r->readLine()) !== NULL )
		$w->writeLine($line);
	$r->close();
	$w->close();
	test("csv-sample-utf16le-bom.csv", NULL);
	unlink("csv-sample-utf16le-bom.csv");
	
	test("csv-sample-simple.csv", NULL);
	
	// OpenOffice format
	// =================
	test("empty-file.ods", NULL);
	test("binary-file.ods", PHPExcelException::class);
	test("oo-example-alltypes.ods", NULL);
	test("oo-sample1.ods", NULL);

	// BIFF format
	// ===========
	test("empty-file.xls", NULL);
	test("binary-file.xls", PHPExcelException::class);
	test("excel5-example-alltypes.xls", NULL);
	test("excel5-test.xls", NULL);
	test("excel5-sample-1.xls", NULL);
	//test("excel5-custom_number_formats.xls", NULL);
	test("excel5-climate_data.xls", NULL);
	test("excel5-SampleData.xls", NULL);
	test("excel5-rich-text.xls", NULL);
	test("excel5-sample.xls", NULL);

	// Excel 2003 XML format
	// =====================
	test("empty-file.xml", NULL);
	test("binary-file.xml", PHPExcelException::class);
	test("excel2003xml-example-alltypes.xml", NULL);

	// Excel 2007 format
	// =================
	test("empty-file.xlsx", NULL);
	test("binary-file.xlsx", PHPExcelException::class);
	test("excel2007-example-alltypes.xlsx", NULL);
	test("excel2007-amazon_referrals_2009.xlsx", NULL);
}


/**
 * @param string[int] $argv
 * @throws Exception
 */
function main($argv) {
	if( count($argv) > 1 ){
		// If cmfd line args available, apply test() function to them:
		for($i = 1; $i < count($argv); $i++)
			test($argv[$i]);

	} else {
		// No cmd line args. Perform standard tests.
		allTests();
	}
}

main($argv);