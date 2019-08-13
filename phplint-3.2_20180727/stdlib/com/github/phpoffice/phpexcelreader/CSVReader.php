<?php

/**
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation; either
 * version 2.1 of the License, or (at your option) any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU
 * Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public
 * License along with this library; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA
 *
 * @package PHPExcelReader
 * @copyright Copyright (c) 2006 - 2015 PHPExcel (http://www.codeplex.com/PHPExcel)
 * @license http://www.gnu.org/licenses/old-licenses/lgpl-2.1.txt LGPL
 * @version $Date: 2015/10/28 15:32:40 $
 */

namespace com\github\phpoffice\phpexcelreader;

require_once __DIR__ . "/../../../../all.php";

use ErrorException;
use it\icosaedro\io\IOException;
use it\icosaedro\io\File;
use it\icosaedro\io\FileInputStream;
use it\icosaedro\io\Reader;

/**
 * Reader for CSV files. Files starting with a BOM are recognized and the corresponding
 * UTF character encoding is used, otherwise a default character encoding can be
 * specified. Since on CSV files no types nor styles are available, the content of
 * each cell is always returned as a UTF-8 string.
 * Example:
 * <pre>
$reader = new CSVReader();
$reader-&gt;setInputEncoding("Windows-1252");
$reader-&gt;setDelimiter(";");
$workbook = $reader-&gt;load("data.csv");
echo $workbook;
 * </pre>
 */
class CSVReader extends AbstractReader {

	/**
	 * Input encoding to use if the file does not contains a BOM.
	 * NULL means the current system locale encoding.
	 * @var string
	 */
	private $inputEncoding = NULL;

	/**
	 * Fields separator character.
	 * @var string
	 */
	private $delimiter = ',';

	/**
	 * Enclosure character.
	 * @var string
	 */
	private $enclosure = '"';
	

	/**
	 * Can this reader read the file? Are readable those file that have one of the
	 * following extensions: csv, txt, text, or files that are empty (resulting
	 * in a worksheet with only one A1 NULL cell), or files containing at least
	 * 76% of printable ASCII characters.
	 *
	 * @param string $pFilename
	 * @return boolean
	 * @throws ErrorException
	 */
	public function canRead($pFilename) {
		if( !file_exists($pFilename) )
			return FALSE;

		// Check well known extensions:
		$pathinfo = pathinfo($pFilename);
		if( isset($pathinfo['extension']) ){
			$extension = strtolower($pathinfo['extension']);
			if( in_array($extension, array('csv', 'txt', 'text')) )
				return TRUE;
		}

		// Read few bytes from the file:
		$fileHandle = fopen($pFilename, "rb");
		$buf = fread($fileHandle, 1000);
		fclose($fileHandle);
		$buf_len = strlen($buf);
		
		// Empty files are assumed CSV:
		if( $buf_len == 0 )
			return TRUE; // empty file can be assumed to be empty worksheet
		
		// Try to detect ASCII or quasi-ASCII file. Between the "printable" characters
		// here we include the ASCII codes 10, 13, 32-126, for a total of 98 codes.
		// Then, a sequence of completely random bytes contains a ratio of printable
		// chars of 98/255 = 38%. We assume that if the file contains a ratio of
		// printable chars which is twice this value (76%) it is an ASCII or quasi-
		// ASCII text file, possibly UTF-8, ISO-8859-* or Windows-* encoded.
		$ascii_printable_count = strlen(preg_replace("/[^ -\x7e\r\n\t]/", "", $buf));
		return $ascii_printable_count / $buf_len >= 0.76;
	}

	/**
	 * Set fields separator character. By default it is , (comma).
	 * @param string $pValue
	 * @return self
	 */
	public function setDelimiter($pValue) {
		$this->delimiter = $pValue;
		return $this;
	}

	/**
	 * Set enclosurem character. By default it is " (double-quote).
	 * @param string $enclosure
	 * @return self
	 */
	public function setEnclosure($enclosure) {
		$this->enclosure = $enclosure;
		return $this;
	}

	/**
	 * Set input encoding to be used if the file does not contain a BOM. The
	 * default is NULL, which means the system locale encoding. Other common
	 * encodings you may use are "Windows-1252" for files generated on Windows
	 * in some western country, "MACINTOSH" for files generated on Macintosh;
	 * "UTF-8" can be assumed otherwise, but "ASCII" might be the safer choice.
	 * Invalid binary data are replaced with the Unicode replacement character U+FFFD
	 * (question mark inside a diamond "&#xfffd;").
	 * @param string $inputEncoding
	 */
	public function setInputEncoding($inputEncoding) {
		$this->inputEncoding = $inputEncoding;
		return $this;
	}

	/**
	 * Loads worksheet from file.
	 *
	 * @param string $pFilename
	 * @return Workbook Contains a single sheet.
	 * @throws PHPExcelException
	 * @throws ErrorException
	 */
	public function load($pFilename) {
		
		// Implementation notes and issues.
		// 
		// We use the it\icosaedro\io\Reader to read and decode the text file,
		// and then str_getcsv() to split each line into fields. Using str_getcsv()
		// leave us the task to detect new-line characters into field, which is bit
		// tricky (see comment below for more).
		// 
		// str_getcsv() here is applied to the UTF-8 encoded line. Although str_getcsv()
		// is locale-aware, the result should be independent from the actual locale
		// as far as the enclosure and delimiter characters are simple 1-byte ASCII chars
		// (not really sure about that, should investigate further).
		// 
		// The exact behavior of str_getcsv() is not specified in the manual, in particular
		// when the enclosure and escaped enclosure chars are involved. The double
		// double-quote character "" is resolved as per the RFC 4180, but here we also
		// accept \" which LibreOffice (and probably Excel too) actually uses.
		
		try {
		
		$in = new FileInputStream(File::fromLocaleEncoded($pFilename, File::getCWD()));
		$r = new Reader($in, $this->inputEncoding);
		$workbook = new Workbook();
		$sheet = $workbook->createSheet();
		$escapeEnclosures = "\\" . $this->enclosure;
		$enclosureCountRegex = "/^" . $this->enclosure . "|[^" . $this->enclosure . "]" . $this->enclosure . "/"; // Basically '/^"|[^"]"/'
		$rowNumber = 1;
		do {
			$line = $r->readLine();
			if( $line === NULL )
				break;
			$line_utf8 = $line->toUTF8();
			
			// Read possible continuation lines after unclosed last quoted field.
			// Strategy: if the current line contains an ODD number of "enclosure"
			// chars ("" and \" apart), then we assume the last field is unclosed
			// and the next line mast be joined. Eventually, line joining continues
			// if the read lines contain an EVEN number of "enclosure" chars.
			// FIXME: BUG: if the file contains an unclosed "enclosure" char, or the
			// heuristic applied here fails, the whole file will be read as a last
			// single huge field.
			$line2_utf8 = $line_utf8;
			$enclosure_count_parity = 0; // 0=even number, 1=odd number of enclosures
			do {
				$enclose_count = preg_match_all($enclosureCountRegex, $line2_utf8);
				if( $enclose_count % 2 == $enclosure_count_parity )
					break;
				$enclosure_count_parity = 1;
				$line2 = $r->readLine();
				if( $line2 === NULL )
					break;
				$line2_utf8 = $line2->toUTF8();
				$line_utf8 = "$line_utf8\n$line2_utf8";
			} while(TRUE);
			
			// Parse line into fields:
			$rowData = str_getcsv($line_utf8, $this->delimiter, $this->enclosure);
			// For empty lines, str_getcsv() returns array(NULL) rather than just array(). Fix:
			if( count($rowData) == 1 && $rowData[0] === NULL )
				$rowData = array();
			
			// Add row to the worksheet:
			$columnNumber = 1;
			foreach ($rowData as $rowDatum) {
				// Unescape enclosures
				$rowDatum = (string) str_replace($escapeEnclosures, $this->enclosure, $rowDatum);
				$cellCoords = Coordinates::create($columnNumber, $rowNumber);
				$sheet->getCell($cellCoords)->setValue($rowDatum);
				$columnNumber++;
			}
			++$rowNumber;
		} while(TRUE);
		$r->close();
		return $workbook;
		
		} catch(IOException $e){
			throw new ErrorException($e->getMessage(), $e->getCode(), 0, $e->getFile(), $e->getLine(), $e);
		}
	}

}
