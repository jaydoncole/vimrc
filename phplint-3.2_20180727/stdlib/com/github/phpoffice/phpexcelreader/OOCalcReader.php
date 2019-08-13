<?php

/**
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation; either
 * version 2.1 of the License, or (at your option) any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public
 * License along with this library; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301  USA
 *
 * @package    PHPExcelReader
 * @copyright  Copyright (c) 2006 - 2015 PHPExcel (http://www.codeplex.com/PHPExcel)
 * @license    http://www.gnu.org/licenses/old-licenses/lgpl-2.1.txt    LGPL
 * @version    $Date: 2015/10/28 15:32:40 $
 */

namespace com\github\phpoffice\phpexcelreader;

require_once __DIR__ . "/../../../../all.php";

/*. require_module 'zip'; .*/

use ZipArchive;
use it\icosaedro\utils\Errors;

/**
 * Reader for OpenDocument Spreadsheet (.ods) file, the original file format of the
 * OpenOffice Calc program.
 */
class OOCalcReader extends AbstractReader {
	
	/**
	 * @access private
	 */
	const
		NS_MANIFEST = "urn:oasis:names:tc:opendocument:xmlns:manifest:1.0",
		NS_OFFICE   = "urn:oasis:names:tc:opendocument:xmlns:office:1.0",
		NS_DC       = "http://purl.org/dc/elements/1.1/",
		NS_META     = "urn:oasis:names:tc:opendocument:xmlns:meta:1.0",
		NS_TABLE    = "urn:oasis:names:tc:opendocument:xmlns:table:1.0",
		NS_TEXT     = "urn:oasis:names:tc:opendocument:xmlns:text:1.0";

//	/**
//	 * Formats
//	 *
//	 * @var array
//	 */
//	private $styles = array();

	/**
	 * OOCalcReader
	 */
	public function __construct() {
//		$this->readFilter = new DefaultReadFilter();
	}

	/**
	 * Can this reader read the file?
	 *
	 * @param  string $pFilename
	 * @return boolean
	 * @throws \ErrorException
	 */
	public function canRead($pFilename) {
		$mimeType = 'UNKNOWN';
		// Load file
		$zip = new ZipArchive();
		if ($zip->open($pFilename) === true) {
			// check if it is an OOXML archive
			$stat = $zip->statName('mimetype');
			if ($stat !== FALSE && ((int)$stat['size'] <= 255)) {
				$mimeType = $zip->getFromName((string)$stat['name']);
			} elseif (($stat = $zip->statName('META-INF/manifest.xml')) !== FALSE) {
				$xml = $zip->getFromName('META-INF/manifest.xml');
				$root = XMLElementReader::loadFromString($xml);
				$root->registerXPathNamespace("manifest", self::NS_MANIFEST);
				$manifest = $root->xpath("manifest:file-entry");
				foreach ($manifest as $manifestDataSet) {
					$manifestAttributes = $manifestDataSet->attributes(self::NS_MANIFEST);
					if ($manifestAttributes['full-path'] === '/') {
						$mimeType = $manifestAttributes['media-type'];
						break;
					}
				}
			}

			$zip->close();

			return ($mimeType === 'application/vnd.oasis.opendocument.spreadsheet');
		}

		return false;
	}

//	/**
//	 * Reads names of the worksheets from a file, without parsing the whole file to a PHPExcel object
//	 *
//	 * @param     string         $pFilename
//	 * @throws     PHPExcelException
//	 */
//	public function listWorksheetNames($pFilename) {
//		// Check if file exists
//		if (!file_exists($pFilename)) {
//			throw new PHPExcelException("Could not open " . $pFilename . " for reading! File does not exist.");
//		}
//
//		$zipClass = Settings::getZipClass();
//
//		$zip = new $zipClass;
//		if (!$zip->open($pFilename)) {
//			throw new PHPExcelException("Could not open " . $pFilename . " for reading! Error opening file.");
//		}
//
//		$worksheetNames = array();
//
//		$xml = new XMLReader();
//		$res = $xml->xml($this->securityScanFile('zip://' . realpath($pFilename) . '#content.xml'), null, Settings::getLibXmlLoaderOptions());
//		$xml->setParserProperty(2, true);
//
//		//    Step into the first level of content of the XML
//		$xml->read();
//		while ($xml->read()) {
//			//    Quickly jump through to the office:body node
//			while ($xml->name !== 'office:body') {
//				if ($xml->isEmptyElement) {
//					$xml->read();
//				} else {
//					$xml->next();
//				}
//			}
//			//    Now read each node until we find our first table:table node
//			while ($xml->read()) {
//				if ($xml->name == 'table:table' && $xml->nodeType == XMLReader::ELEMENT) {
//					//    Loop through each table:table node reading the table:name attribute for each worksheet name
//					do {
//						$worksheetNames[] = $xml->getAttribute('table:name');
//						$xml->next();
//					} while ($xml->name == 'table:table' && $xml->nodeType == XMLReader::ELEMENT);
//				}
//			}
//		}
//
//		return $worksheetNames;
//	}

//	/**
//	 * Return worksheet info (Name, Last Column Letter, Last Column Index, Total Rows, Total Columns)
//	 *
//	 * @param  string $pFilename
//	 * @throws PHPExcelException
//	 */
//	public function listWorksheetInfo($pFilename) {
//		// Check if file exists
//		if (!file_exists($pFilename)) {
//			throw new PHPExcelException("Could not open " . $pFilename . " for reading! File does not exist.");
//		}
//
//		$worksheetInfo = array();
//
//		$zipClass = Settings::getZipClass();
//
//		$zip = new $zipClass;
//		if (!$zip->open($pFilename)) {
//			throw new PHPExcelException("Could not open " . $pFilename . " for reading! Error opening file.");
//		}
//
//		$xml = new XMLReader();
//		$res = $xml->xml($this->securityScanFile('zip://' . realpath($pFilename) . '#content.xml'), null, Settings::getLibXmlLoaderOptions());
//		$xml->setParserProperty(2, true);
//
//		//    Step into the first level of content of the XML
//		$xml->read();
//		while ($xml->read()) {
//			//    Quickly jump through to the office:body node
//			while ($xml->name !== 'office:body') {
//				if ($xml->isEmptyElement) {
//					$xml->read();
//				} else {
//					$xml->next();
//				}
//			}
//			//    Now read each node until we find our first table:table node
//			while ($xml->read()) {
//				if ($xml->name == 'table:table' && $xml->nodeType == XMLReader::ELEMENT) {
//					$worksheetNames[] = $xml->getAttribute('table:name');
//
//					$tmpInfo = array(
//						'worksheetName' => $xml->getAttribute('table:name'),
//						'lastColumnLetter' => 'A',
//						'lastColumnIndex' => 0,
//						'totalRows' => 0,
//						'totalColumns' => 0,
//					);
//
//					//    Loop through each child node of the table:table element reading
//					$currCells = 0;
//					do {
//						$xml->read();
//						if ($xml->name == 'table:table-row' && $xml->nodeType == XMLReader::ELEMENT) {
//							$rowspan = $xml->getAttribute('table:number-rows-repeated');
//							$rowspan = empty($rowspan) ? 1 : $rowspan;
//							$tmpInfo['totalRows'] += $rowspan;
//							$tmpInfo['totalColumns'] = max($tmpInfo['totalColumns'], $currCells);
//							$currCells = 0;
//							//    Step into the row
//							$xml->read();
//							do {
//								if ($xml->name == 'table:table-cell' && $xml->nodeType == XMLReader::ELEMENT) {
//									if (!$xml->isEmptyElement) {
//										$currCells++;
//										$xml->next();
//									} else {
//										$xml->read();
//									}
//								} elseif ($xml->name == 'table:covered-table-cell' && $xml->nodeType == XMLReader::ELEMENT) {
//									$mergeSize = $xml->getAttribute('table:number-columns-repeated');
//									$currCells += $mergeSize;
//									$xml->read();
//								}
//							} while ($xml->name != 'table:table-row');
//						}
//					} while ($xml->name != 'table:table');
//
//					$tmpInfo['totalColumns'] = max($tmpInfo['totalColumns'], $currCells);
//					$tmpInfo['lastColumnIndex'] = $tmpInfo['totalColumns'] - 1;
//					$tmpInfo['lastColumnLetter'] = Cell::stringFromColumnIndex($tmpInfo['lastColumnIndex']);
//					$worksheetInfo[] = $tmpInfo;
//				}
//			}
//
////                foreach ($workbookData->table as $worksheetDataSet) {
////                    $worksheetData = $worksheetDataSet->children($namespacesContent['table']);
////                    $worksheetDataAttributes = $worksheetDataSet->attributes($namespacesContent['table']);
////
////                    $rowIndex = 0;
////                    foreach ($worksheetData as $key => $rowData) {
////                        switch ($key) {
////                            case 'table-row' :
////                                $rowDataTableAttributes = $rowData->attributes($namespacesContent['table']);
////                                $rowRepeats = (isset($rowDataTableAttributes['number-rows-repeated'])) ?
////                                        $rowDataTableAttributes['number-rows-repeated'] : 1;
////                                $columnIndex = 0;
////
////                                foreach ($rowData as $key => $cellData) {
////                                    $cellDataTableAttributes = $cellData->attributes($namespacesContent['table']);
////                                    $colRepeats = (isset($cellDataTableAttributes['number-columns-repeated'])) ?
////                                        $cellDataTableAttributes['number-columns-repeated'] : 1;
////                                    $cellDataOfficeAttributes = $cellData->attributes($namespacesContent['office']);
////                                    if (isset($cellDataOfficeAttributes['value-type'])) {
////                                        $tmpInfo['lastColumnIndex'] = max($tmpInfo['lastColumnIndex'], $columnIndex + $colRepeats - 1);
////                                        $tmpInfo['totalRows'] = max($tmpInfo['totalRows'], $rowIndex + $rowRepeats);
////                                    }
////                                    $columnIndex += $colRepeats;
////                                }
////                                $rowIndex += $rowRepeats;
////                                break;
////                        }
////                    }
////
////                    $tmpInfo['lastColumnLetter'] = Cell::stringFromColumnIndex($tmpInfo['lastColumnIndex']);
////                    $tmpInfo['totalColumns'] = $tmpInfo['lastColumnIndex'] + 1;
////
////                }
////            }
//		}
//
//		return $worksheetInfo;
//	}

//	private static function identifyFixedStyleValue($styleList, &$styleAttributeValue) {
//		$styleAttributeValue = strtolower($styleAttributeValue);
//		foreach ($styleList as $style) {
//			if ($styleAttributeValue == strtolower($style)) {
//				$styleAttributeValue = $style;
//				return true;
//			}
//		}
//		return false;
//	}

//	private function parseRichText($is = '') {
//		$value = new RichText();
//
//		$value->createText($is);
//
//		return $value;
//	}
	
	
	/**
	 * This function copied from Computation.php, apprently used only here (and in
	 * Computation.php itself, not required for a bare Excel file reader).
	 * @param string $fromSeparator
	 * @param string $toSeparator
	 * @param string $formula
	 * @param boolean & $inBraces
	 * @return string
	 */
	private static function translateSeparator($fromSeparator, $toSeparator, $formula, /*. return .*/ &$inBraces) {
		$inBraces = FALSE;
		$strlen = mb_strlen($formula);
		for ($i = 0; $i < $strlen; ++$i) {
			$chr = mb_substr($formula, $i, 1);
			switch ($chr) {
				case '{':
					$inBraces = true;
					break;
				case '}':
					$inBraces = false;
					break;
				default:
					if($chr === $fromSeparator){
						if (!$inBraces) {
							$formula = mb_substr($formula, 0, $i) . $toSeparator . mb_substr($formula, $i + 1);
						}
					}
			}
		}
		return $formula;
	}

	/**
	 * @param mixed $propertyValue
	 * @param string $propertyType
	 * @return mixed
	 * @throws PHPExcelException
	 */
	private static function convertProperty($propertyValue, $propertyType) {
		switch ($propertyType) {
			case 'empty':  //    Empty
				return '';
			case 'null':   //    Null
				return null;
			case 'i1':  //    1-Byte Signed Integer
			case 'i2':  //    2-Byte Signed Integer
			case 'i4':  //    4-Byte Signed Integer
			case 'i8':  //    8-Byte Signed Integer
			case 'int':	//    Integer
				return (int) $propertyValue;
			case 'ui1':	//    1-Byte Unsigned Integer
			case 'ui2':	//    2-Byte Unsigned Integer
			case 'ui4':	//    4-Byte Unsigned Integer
			case 'ui8':	//    8-Byte Unsigned Integer
			case 'uint':   //    Unsigned Integer
				return abs((int) $propertyValue);
			case 'r4':  //    4-Byte Real Number
			case 'r8':  //    8-Byte Real Number
			case 'decimal':   //    Decimal
				return (float) $propertyValue;
			case 'lpstr':  //    LPSTR
			case 'lpwstr': //    LPWSTR
			case 'bstr':   //    Basic String
				return $propertyValue;
			case 'date':   //    Date and Time
			case 'filetime':  //    File Time
				return SharedDate::parseDateTime((string) $propertyValue);
			case 'bool':  //    Boolean
				return ($propertyValue === 'true') ? true : false;
			case 'cy':	//    Currency
			case 'error': //    Error Status Code
			case 'vector':   //    Vector
			case 'array': //    Array
			case 'blob':  //    Binary Blob
			case 'oblob': //    Binary Blob Object
			case 'stream':   //    Binary Stream
			case 'ostream':  //    Binary Stream Object
			case 'storage':  //    Binary Storage
			case 'ostorage': //    Binary Storage Object
			case 'vstream':  //    Binary Versioned Stream
			case 'clsid': //    Class ID
			case 'cf':	//    Clipboard Data
				return $propertyValue;
			default:
				return $propertyValue;
		}
	}

	/**
	 * Loads PHPExcel from file into PHPExcel instance.
	 *
	 * @param string $pFilename
	 * @param Workbook $workbook
	 * @return Workbook
	 * @throws PHPExcelException
	 * @throws \ErrorException
	 */
	public function loadIntoExisting($pFilename, $workbook) {
		$zip = new ZipArchive();
		$err = $zip->open($pFilename);
		if ($err !== TRUE) {
			throw new PHPExcelException("Could not open $pFilename: ZipArchive::" . Errors::getClassConstantName("ZipArchive", "ER_", $err));
		}

		$xml = $zip->getFromName("meta.xml");
		$root = XMLElementReader::loadFromString($xml);
		$root->registerXPathNamespace("office", self::NS_OFFICE);
		$root->registerXPathNamespace("dc", self::NS_DC);
		$root->registerXPathNamespace("meta", self::NS_META);
		$root->registerXPathNamespace("table", self::NS_TABLE);
		$root->registerXPathNamespace("text", self::NS_TEXT);

		$docProps = $workbook->getProperties();
		$officeProperties = $root->xpath("office:*");
		foreach ($officeProperties as $officePropertyData) {
			$officePropertyDC = $officePropertyData->xpath("dc:*");
			foreach ($officePropertyDC as $property) {
				$propertyName = $property->getLocalName();
				$propertyValue = $property->getText();
				switch ($propertyName) {
					case 'title':
						$docProps->setTitle($propertyValue);
						break;
					case 'subject':
						$docProps->setSubject($propertyValue);
						break;
					case 'creator':
						$docProps->setCreator($propertyValue);
						$docProps->setLastModifiedBy($propertyValue);
						break;
					case 'date':
						$creationDate = SharedDate::parseDateTime($propertyValue);
						if ($creationDate !== FALSE) {
							$docProps->setCreated($creationDate);
							$docProps->setModified($creationDate);
						}
						break;
					case 'description':
						$docProps->setDescription($propertyValue);
						break;
					default:
				}
			}
			
			$officePropertyMeta = $officePropertyData->xpath("meta:*");
			foreach ($officePropertyMeta as $property) {
				$propertyName = $property->getLocalName();
				$propertyValue = $property->getText();
				$propertyValueAttributes = $property->attributes(self::NS_META);
				switch ($propertyName) {
					case 'initial-creator':
						$docProps->setCreator($propertyValue);
						break;
					case 'keyword':
						$docProps->setKeywords($propertyValue);
						break;
					case 'creation-date':
						$creationDate = SharedDate::parseDateTime($propertyValue);
						if ($creationDate !== FALSE) {
							$docProps->setCreated($creationDate);
						}
						break;
					case 'user-defined':
						$propertyValueName = isset($propertyValueAttributes['name'])? $propertyValueAttributes['name'] : "?";
						$propertyValueType = isset($propertyValueAttributes['value-type'])? $propertyValueAttributes['value-type'] : DocumentProperties::PROPERTY_TYPE_STRING;
						/*. mixed .*/ $propertyValueMixed = $propertyValue;
						switch ($propertyValueType) {
							case 'date':
								$propertyValueMixed = self::convertProperty($propertyValue, 'date');
								$propertyValueType = DocumentProperties::PROPERTY_TYPE_DATE;
								break;
							case 'boolean':
								$propertyValueMixed = self::convertProperty($propertyValue, 'bool');
								$propertyValueType = DocumentProperties::PROPERTY_TYPE_BOOLEAN;
								break;
							case 'float':
								$propertyValueMixed = self::convertProperty($propertyValue, 'r4');
								$propertyValueType = DocumentProperties::PROPERTY_TYPE_FLOAT;
								break;
							default:
								$propertyValueType = DocumentProperties::PROPERTY_TYPE_STRING;
						}
						$docProps->setCustomProperty($propertyValueName, $propertyValueMixed, $propertyValueType);
						break;
					default:
				}
			}
		}

		$xml = $zip->getFromName("content.xml");
//		file_put_contents($pFilename."-DUMP-content.xml", $xml);
		$root = XMLElementReader::loadFromString($xml);
		foreach ($root->xpath("office:body/office:spreadsheet") as $workbookData) {
			$worksheetID = 0;
			foreach ($workbookData->xpath("table:table") as $worksheetData) {
				$worksheetDataAttributes = $worksheetData->attributes(self::NS_TABLE);
				
				// Create new Worksheet
				$sheet = $workbook->createSheet();
				$workbook->setActiveSheetIndex($worksheetID);
				if (isset($worksheetDataAttributes['name'])) {
					$worksheetName = $worksheetDataAttributes['name'];
					//    Use false for $updateFormulaCellReferences to prevent adjustment of worksheet references in
					//        formula cells... during the load, all formulae should be correct, and we're simply
					//        bringing the worksheet name in line with the formula, not the reverse
					$sheet->setTitle($worksheetName, false);
				}

				$rowID = 1;
				foreach ($worksheetData->xpath("table:table-row") as $rowData) {
//					switch ($key) {
//						case 'table-header-rows':
//							foreach ($rowData as $key => $cellData) {
//								$rowData = $cellData;
//								break;
//							}
//						case 'table-row':
							$rowDataTableAttributes = $rowData->attributes(self::NS_TABLE);
							$rowRepeats = (isset($rowDataTableAttributes['number-rows-repeated'])) ? (int)$rowDataTableAttributes['number-rows-repeated'] : 1;
							$columnNumber = 1;
							foreach ($rowData->xpath("table:table-cell") as $cellData) {
//								if ($this->getReadFilter() !== null) {
//									if (!$this->getReadFilter()->readCell($columnID, $rowID, $worksheetName)) {
//										continue;
//									}
//								}

//								$cellDataOffice = $cellData->children($namespacesContent['office']);
								$cellDataOfficeAttributes = $cellData->attributes(self::NS_OFFICE);
								$cellDataTableAttributes = $cellData->attributes(self::NS_TABLE);
								$type = $formatting = $hyperlink = /*. (string) .*/ null;
								$hasCalculatedValue = false;
								$cellDataFormula = '';
								if (isset($cellDataTableAttributes['formula'])) {
									$cellDataFormula = $cellDataTableAttributes['formula'];
									$hasCalculatedValue = true;
								}

//								if (isset($cellDataOffice->annotation)) {
////                                    echo 'Cell has comment<br />';
//									$annotationText = $cellDataOffice->annotation->children($namespacesContent['text']);
//									$textArray = array();
//									foreach ($annotationText as $t) {
//										if (isset($t->span)) {
//											foreach ($t->span as $text) {
//												$textArray[] = (string) $text;
//											}
//										} else {
//											$textArray[] = (string) $t;
//										}
//									}
//									$text = implode("\n", $textArray);
////                                    echo $text, '<br />';
//									$objPHPExcel->getActiveSheet()->getComment($columnID . $rowID)->setText($this->parseRichText($text));
////                                                                    ->setAuthor( $author )
//								}

								$cellDataText = $cellData->xpath("text:p");
								$dataValue = /*. (mixed) .*/ null;
								if (count($cellDataText) > 0) {
									// Consolidate if there are multiple p records (maybe with spans as well)
									$dataArray = /*. (string[int]) .*/ array();
									// Text can have multiple text:p and within those, multiple text:span.
									// text:p newlines, but text:span does not.
									// Also, here we assume there is no text data is span fields are specified, since
									// we have no way of knowing proper positioning anyway.
									foreach ($cellDataText as $pData) {
										$spans = $pData->xpath("text:span");
										if (count($spans) > 0) {
											// span sections do not newline, so we just create one large string here
											$spanSection = "";
											foreach ($spans as $spanData) {
												$spanSection .= $spanData->getText();
											}
											$dataArray[] = $spanSection;
										} else {
											$dataArray[] = $pData->getText();
										}
									}
									$allCellDataText = implode("\n", $dataArray);

									switch ($cellDataOfficeAttributes['value-type']) {
										case 'string':
											$type = Cell::TYPE_STRING;
											$dataValue = $allCellDataText;
											// FIXME: BUG: $dataValue now is string: ->a could not exist
//											if (isset($dataValue->a)) {
//												$dataValue = $dataValue->a;
//												$cellXLinkAttributes = $dataValue->attributes($namespacesContent['xlink']);
//												$hyperlink = $cellXLinkAttributes['href'];
//											}
											break;
										case 'boolean':
											$type = Cell::TYPE_BOOL;
											$dataValue = ($allCellDataText === 'TRUE') ? true : false;
											break;
										case 'percentage':
											$type = Cell::TYPE_NUMERIC;
											$dataValueFloat = (float) $cellDataOfficeAttributes['value'];
											if (floor($dataValueFloat) == $dataValueFloat) {
												$dataValue = (integer) $dataValueFloat;
											} else {
												$dataValue = $dataValueFloat;
											}
											$formatting = Style::FORMAT_PERCENTAGE_00;
											break;
										case 'currency':
											$type = Cell::TYPE_NUMERIC;
											$dataValueFloat = (float) $cellDataOfficeAttributes['value'];
											if (floor($dataValueFloat) == $dataValueFloat) {
												$dataValue = (integer) $dataValueFloat;
											} else {
												$dataValue = $dataValueFloat;
											}
											// builds an Excel-like format descriptor, so the rest of the
											// code can recognize this type.
											// FIXME: this attribute evaluates to USD, EUR, etc. rather than the symbol.
											// Fix: there is a children element containing the text "$ 1,234.56": try to recover the symbol from there.
											$currency = $cellDataOfficeAttributes['currency'];
											$formatting = "[\$$currency]#,##0.00_-";
											break;
										case 'float':
											$type = Cell::TYPE_NUMERIC;
											$dataValueFloat = (float) $cellDataOfficeAttributes['value'];
											if (floor($dataValueFloat) == $dataValueFloat) {
												$dataValue = (integer) $dataValueFloat;
											} else {
												$dataValue = $dataValueFloat;
											}
											break;
										case 'date': // date only (yyyy-mm-dd) or date and time (yyyy-mm-dd something)
											$type = Cell::TYPE_NUMERIC;
//											echo "[date=", $cellDataOfficeAttributes['date-value'], "]";
											$unixTimestamp = SharedDate::parseDateTime($cellDataOfficeAttributes['date-value']);
											$excelTimestamp = SharedDate::PHPToExcel($unixTimestamp);
											if ($excelTimestamp != floor($excelTimestamp)) {
												// date and time
												$formatting = SharedDate::FORMAT_DATE_XLSX15 . ' ' . SharedDate::FORMAT_DATE_TIME4;
											} else {
												// date only
												$formatting = SharedDate::FORMAT_DATE_XLSX15;
											}
											$dataValue = $excelTimestamp;
											break;
										case 'time':
											$type = Cell::TYPE_NUMERIC;
											$s = $cellDataOfficeAttributes['time-value'];
//											echo "[time=$s]";
											$a = cast("mixed[int]", sscanf($s, 'PT%dH%dM%fS'));
											if( count($a) !== 3 )
												throw new PHPExcelException("cannot parse time: $s");
											if( !(is_int($a[0]) && is_int($a[1]) && is_float($a[2])) )
												throw new PHPExcelException("cannot parse time: $s");
//											$dt = sprintf("%02d:%02d:%02f", $a[0], $a[1], $a[2]);
//											$dataValue = SharedDate::PHPToExcel(SharedDate::parseDateTime($dt));
											$h = (int) $a[0];  $m = (int) $a[1];  $sec = (float) $a[2];
											// normalize time to Excel range 0 <= t < 1:
											$dataValue = (3600.0 * $h + 60.0 * $m + $sec) / 86400.0;
											$formatting = SharedDate::FORMAT_DATE_TIME4;
											break;
										default:
											$type = Cell::TYPE_STRING;
											$dataValue = $allCellDataText;
									}
								} else {
									$type = Cell::TYPE_NULL;
									$dataValue = null;
								}

								if ($hasCalculatedValue) {
									$type = Cell::TYPE_FORMULA;
									$cellDataFormula = substr($cellDataFormula, strpos($cellDataFormula, ':=') + 1);
									$temp = explode('"', $cellDataFormula);
									$tKey = false;
									foreach ($temp as $value) {
										//    Only replace in alternate array entries (i.e. non-quoted blocks)
										if ($tKey = !$tKey) {
											$value = preg_replace('/\\[([^\\.]+)\\.([^\\.]+):\\.([^\\.]+)\\]/Ui', '$1!$2:$3', $value); //  Cell range reference in another sheet
											$value = preg_replace('/\\[([^\\.]+)\\.([^\\.]+)\\]/Ui', '$1!$2', $value); //  Cell reference in another sheet
											$value = preg_replace('/\\[\\.([^\\.]+):\\.([^\\.]+)\\]/Ui', '$1:$2', $value); //  Cell range reference
											$value = preg_replace('/\\[\\.([^\\.]+)\\]/Ui', '$1', $value);   //  Simple cell reference
											$value = self::translateSeparator(';', ',', $value, $inBraces);
										}
									}
									//    Then rebuild the formula string
									$cellDataFormula = implode('"', $temp);
//                                    echo 'Adjusted Formula: ', $cellDataFormula, PHP_EOL;
								}

								$colRepeats = (isset($cellDataTableAttributes['number-columns-repeated'])) ? (int) $cellDataTableAttributes['number-columns-repeated'] : 1;
								if ($type !== null) {
									for ($i = 0; $i < $colRepeats; ++$i) {
										if ($i > 0) {
											$columnNumber++;
										}
										if ($type !== Cell::TYPE_NULL) {
											for ($rowAdjust = 0; $rowAdjust < $rowRepeats; ++$rowAdjust) {
												$rID = $rowID + $rowAdjust;
												$cellCoords = Coordinates::create($columnNumber, $rID);
												if( $hasCalculatedValue )
													$sheet->getCell($cellCoords)->setValueExplicit($cellDataFormula, $type);
												else
													$sheet->getCell($cellCoords)->setValueExplicit($dataValue, $type);
												if ($hasCalculatedValue) {
													$sheet->getCell($cellCoords)->setCalculatedValue($dataValue);
												}
												if ($formatting !== null) {
													$sheet->getStyle($cellCoords)->setFormatCode($formatting);
												} else {
													$sheet->getStyle($cellCoords)->setFormatCode(Style::FORMAT_GENERAL);
												}
//												if ($hyperlink !== null) {
//													$sheet->getCell($columnID . $rID)->getHyperlink()->setUrl($hyperlink);
//												}
											}
										}
									}
								}

//								//    Merged cells
//								if ((isset($cellDataTableAttributes['number-columns-spanned'])) || (isset($cellDataTableAttributes['number-rows-spanned']))) {
//									if (($type !== Cell::TYPE_NULL) || (!$this->readDataOnly)) {
//										$columnTo = $columnID;
//										if (isset($cellDataTableAttributes['number-columns-spanned'])) {
//											$columnTo = Cell::stringFromColumnIndex(Coordinates::columnNumber($columnID) + (int)$cellDataTableAttributes['number-columns-spanned'] - 2);
//										}
//										$rowTo = $rowID;
//										if (isset($cellDataTableAttributes['number-rows-spanned'])) {
//											$rowTo = $rowTo + (int)$cellDataTableAttributes['number-rows-spanned'] - 1;
//										}
//										$cellRange = $columnID . $rowID . ':' . $columnTo . $rowTo;
//										$sheet->mergeCells($cellRange);
//									}
//								}

								$columnNumber++;
							}
							$rowID += $rowRepeats;
//							break;
//					}
				}
				++$worksheetID;
			}
		}

		// Return
		return $workbook;
	}

	/**
	 * Loads PHPExcel from file.
	 *
	 * @param string $pFilename
	 * @return Workbook
	 * @throws PHPExcelException
	 * @throws \ErrorException
	 */
	public function load($pFilename) {
		// Create new PHPExcel
		$workbook = new Workbook();

		// Load into this instance
		return $this->loadIntoExisting($pFilename, $workbook);
	}

}
