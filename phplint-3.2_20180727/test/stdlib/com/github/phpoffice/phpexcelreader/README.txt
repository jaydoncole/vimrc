PHPExcelReader TEST DIRECTORY README
====================================

USAGE OF THIS DIR
=================
The a.txt file is the reference output of the test script. Modify the test
script by setting the constant AUTO_TEST to FALSE for interactive mode, then
start the script to generate a new report and compare vs. a.txt:

$ php test-PHPExcelReader.php > b.txt && diff a.txt b.txt

The two files could differ for the line ending convention, as the CVS repository
could have converted NL to CR+NL, but the diff program should not care about
that.

The test script also accepts one or more Excel files to parse for quick tests:

$ php test-PHPExcelReader.php mysample.xls


TODO & BUGS
===========

- 2015-09-02 Currently aligned with https://github.com/PHPOffice/PHPExcel

- Periodically check for updates at https://github.com/PHPOffice/PHPExcel
  as with the planned 2.0 version there will be several structural changes:
  using namespaces; using closures; using late state binding; switching from
  SimpleXML to XMLReader.

- References:
	How to use dates and times in Excel - https://support.microsoft.com/en-us/kb/214094
	Dates and times in Excel - http://www.cpearson.com/excel/datetime.htm

- Check encoding of all strings; should be UTF-8; consider to use UString.

- Several FIXME to look at.

- Formulas: sometimes the client-side calculated value is available: use these!

- IDEA: Event model to support stream reading.

- BUG: SharedDate: there is no way to detect if PC or Mac date offset applies,
because the origin of the file is unknown.

- BUG: SharedDate: date calculation is performed with Unix timestamps, which do not
allow values in the range 1900-1970. What happen in this case?

- BUG: CSVReader: "delimiter" and "enclosure" should be specified as UTF-8 and
  then encoded accordingly to the set or detected encoding.


TO BE REPORTED
==============

- REPORT to the dev team (CSV encoding and BOM): CSVReader:
  1. BOMs must be detected in the proper order, because UTF-16LE and UTF-32LE
     share the same 2 leading bytes.
  2. if a BOM is detected, use that regardless to the input encoding.

- REPORT to the dev team (missing error detection): a statement like
  if( ! $zip->open(...) ){ report error }
  does not work as expected because ZipArchive::open() returns TRUE on success,
  but return non-zero int values on error, so the error is never reported. Change to:
  if( $zip->open(...) !== TRUE ){ report error }

- REPORT to the dev team (OOCalc: dead code): in OOCalc.php near line 545:
    $allCellDataText = implode($dataArray, "\n");
    ...
	$dataValue = $allCellDataText;
	if (isset($dataValue->a)) {
		$dataValue = $dataValue->a;
		$cellXLinkAttributes = $dataValue->attributes($namespacesContent['xlink']);
		$hyperlink = $cellXLinkAttributes['href'];
	}

  the compound code of the if() statement is never executed because $dataValue,
  at this point, is certainly a string resulting from the implode() function,
  then the "->a" property cannot exist and isset() always returns FALSE.
  The whole if(){...} statement can be removed because useless (or fixed in some way).


ALREADY REPORTED
================
- ISSUE already reported at github:

Subject: Minor formal issue with the type of Supervisor::$parent

I'm trying to formally validate PHPExcel, but I'm stuck on a formal issue about how all the classes are related to each other. To be more specific, the method PHPExcel::__construct() calls

        $this->cellXfSupervisor->bindParent($this)

Being PHPExcel::$cellXfSupervisor of type PHPExcel_Style, the actual method called is

        PHPExcel_Style_Supervisor::bindParent(PHPExcel $parent, PHPExcel_Style_Supervisor $parentPropertyName = null)

which in turn sets

       PHPExcel_Style_Supervisor::$parent

of type PHPExcel_Style. At run-time, that property gets assigned with objects of type PHPExcel_Style and PHPExcel as well, but these two classes are not related in any way.
Now, there is an inconsistency between the type of this latter property, the type of the formal argument of bindParent(), and the usage of this method. Then, which is the correct type of the property?


THE END
