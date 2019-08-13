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
 * @version    $Date: 2018/07/03 06:24:02 $
 */

namespace com\github\phpoffice\phpexcelreader;

require_once __DIR__ . "/../../../../all.php";

/* . require_module 'pcre'; . */

/**
 * Reader for SpreadsheetML (.xml), Excel 2003.
 */
class Excel2003XMLReader extends AbstractReader {
	
	// References:
	// XML Spreadsheet Reference - https://msdn.microsoft.com/en-us/library/aa140066%28office.10%29.aspx

	// Namespaces:
	// 
	//    Office                  xmlns:o="urn:schemas-microsoft-com:office:office"
	//    Excel                   xmlns:x="urn:schemas-microsoft-com:office:excel"
	//    XML Spreadsheet         xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet"
	//    Spreadsheet component   xmlns:c="urn:schemas-microsoft-com:office:component:spreadsheet"
	//    XML schema              xmlns:s="uuid:BDC6E3F0-6DA3-11d1-A2A3-00AA00C14882"
	//    XML data type           xmlns:dt="uuid:C2F41010-65B3-11d1-A29F-00AA00C14882"
	//    MS-persist recordset    xmlns:rs="urn:schemas-microsoft-com:rowset"
	//    Rowset                  xmlns:z="#RowsetSchema"
	//

	/**
	 * @access private
	 */
	const
		NS_SPREADSHEET = "urn:schemas-microsoft-com:office:spreadsheet";

	/**
	 * Formats
	 *
	 * @var string[string]
	 */
	protected $styles = array();

	/**
	 * Character set used in the file
	 *
	 * @var string
	 */
	protected $charSet = 'UTF-8';

	/**
	 * Create a new Excel2003XML
	 */
	public function __construct() {
//		$this->readFilter = new DefaultReadFilter();
	}

	/**
	 * Can the current reader read the file?
	 *
	 * @param     string         $pFilename
	 * @return     boolean
	 * @throws \ErrorException
	 */
	public function canRead($pFilename) {
        $signature = array(
			'<?xml version="1.0"',
//			'<?mso-application progid="Excel.Sheet"? >',
			'xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet"'
		);

		// Open file
		$fileHandle = fopen($pFilename, "rb");

		// Read sample data (first 2 KB will do)
		$data = fread($fileHandle, 2048);
		fclose($fileHandle);

		$valid = true;
		foreach ($signature as $match) {
			// every part of the signature must be present
			if (strpos($data, $match) === false) {
				$valid = false;
				break;
			}
		}

		//    Retrieve charset encoding
		if (1 == preg_match('/<?xml.*encoding=[\'"](.*?)[\'"].*?>/um', $data, $matches)) {
			$this->charSet = strtoupper((string) $matches[1]);
		}
//        echo 'Character Set is ', $this->charSet,'<br />';

		return $valid;
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
//		if (!$this->canRead($pFilename)) {
//			throw new PHPExcelException($pFilename . " is an Invalid Spreadsheet file.");
//		}
//
//		$worksheetNames = array();
//
//		$xml = XMLElementReader::loadFromString($this->securityScan(file_get_contents($pFilename)), Settings::getLibXmlLoaderOptions());
//		$namespaces = $xml->getNamespaces(true);
//
//		$xml_ss = $xml->children(self::SPREADSHEET_NS);
//		foreach ($xml_ss->Worksheet as $worksheet) {
//			$worksheet_ss = $worksheet->attributes(self::SPREADSHEET_NS);
//			$worksheetNames[] = self::convertStringEncoding((string) $worksheet_ss['Name'], $this->charSet);
//		}
//
//		return $worksheetNames;
//	}
//	/**
//	 * Return worksheet info (Name, Last Column Letter, Last Column Index, Total Rows, Total Columns)
//	 *
//	 * @param   string     $pFilename
//	 * @throws   PHPExcelException
//	 */
//	public function listWorksheetInfo($pFilename) {
//		// Check if file exists
//		if (!file_exists($pFilename)) {
//			throw new PHPExcelException("Could not open " . $pFilename . " for reading! File does not exist.");
//		}
//
//		$worksheetInfo = array();
//
//		$xml = XMLElementReader::loadFromString($this->securityScan(file_get_contents($pFilename)), Settings::getLibXmlLoaderOptions());
//		$namespaces = $xml->getNamespaces(true);
//
//		$worksheetID = 1;
//		$xml_ss = $xml->children(self::SPREADSHEET_NS);
//		foreach ($xml_ss->Worksheet as $worksheet) {
//			$worksheet_ss = $worksheet->attributes(self::SPREADSHEET_NS);
//
//			$tmpInfo = array();
//			$tmpInfo['worksheetName'] = '';
//			$tmpInfo['lastColumnLetter'] = 'A';
//			$tmpInfo['lastColumnIndex'] = 0;
//			$tmpInfo['totalRows'] = 0;
//			$tmpInfo['totalColumns'] = 0;
//
//			if (isset($worksheet_ss['Name'])) {
//				$tmpInfo['worksheetName'] = (string) $worksheet_ss['Name'];
//			} else {
//				$tmpInfo['worksheetName'] = "Worksheet_{$worksheetID}";
//			}
//
//			if (isset($worksheet->Table->Row)) {
//				$rowIndex = 0;
//
//				foreach ($worksheet->Table->Row as $rowData) {
//					$columnIndex = 0;
//					$rowHasData = false;
//
//					foreach ($rowData->Cell as $cell) {
//						if (isset($cell->Data)) {
//							$tmpInfo['lastColumnIndex'] = max($tmpInfo['lastColumnIndex'], $columnIndex);
//							$rowHasData = true;
//						}
//
//						++$columnIndex;
//					}
//
//					++$rowIndex;
//
//					if ($rowHasData) {
//						$tmpInfo['totalRows'] = max($tmpInfo['totalRows'], $rowIndex);
//					}
//				}
//			}
//
//			$tmpInfo['lastColumnLetter'] = Cell::stringFromColumnIndex($tmpInfo['lastColumnIndex']);
//			$tmpInfo['totalColumns'] = $tmpInfo['lastColumnIndex'] + 1;
//
//			$worksheetInfo[] = $tmpInfo;
//			++$worksheetID;
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
//	/**
//	 * pixel units to excel width units(units of 1/256th of a character width)
//	 * @param pxs
//	 * @return
//	 */
//	private static function pixel2WidthUnits($pxs) {
//		$UNIT_OFFSET_MAP = array(0, 36, 73, 109, 146, 182, 219);
//
//		$widthUnits = 256 * ($pxs / 7);
//		$widthUnits += $UNIT_OFFSET_MAP[($pxs % 7)];
//		return $widthUnits;
//	}
//	/**
//	 * excel width units(units of 1/256th of a character width) to pixel units
//	 * @param widthUnits
//	 * @return
//	 */
//	private static function widthUnits2Pixel($widthUnits) {
//		$pixels = ($widthUnits / 256) * 7;
//		$offsetWidthUnits = $widthUnits % 256;
//		$pixels += round($offsetWidthUnits / (256 / 7));
//		return $pixels;
//	}
//
//	private static function hex2str($hex) {
//		return chr(hexdec($hex[1]));
//	}

	/**
	 * 
	 * @param string $s
	 * @param string $charset
	 * @return string
	 * @throws PHPExcelException
	 */
	private static function convertStringEncoding($s, $charset) {
		if ($charset !== 'UTF-8') {
			return SharedString::ConvertEncoding($s, 'UTF-8', $charset);
		}
		return $s;
	}

//	private function parseRichText($is = '') {
//		$value = new RichText();
//
//		$value->createText(self::convertStringEncoding($is, $this->charSet));
//
//		return $value;
//	}

	/**
	 * Loads PHPExcel from file into PHPExcel instance.
	 *
	 * @param   string     $pFilename
	 * @param   Workbook   $workbook
	 * @return  Workbook
	 * @throws  PHPExcelException
	 * @throws  \ErrorException
	 */
	public function loadIntoExisting($pFilename, $workbook) {
		$fromFormats = array('\\-', '\\ ');
		$toFormats = array('-', ' ');

//		$underlineStyles = array(
//			StyleFont::UNDERLINE_NONE,
//			StyleFont::UNDERLINE_DOUBLE,
//			StyleFont::UNDERLINE_DOUBLEACCOUNTING,
//			StyleFont::UNDERLINE_SINGLE,
//			StyleFont::UNDERLINE_SINGLEACCOUNTING
//		);
//		$verticalAlignmentStyles = array(
//			StyleAlignment::VERTICAL_BOTTOM,
//			StyleAlignment::VERTICAL_TOP,
//			StyleAlignment::VERTICAL_CENTER,
//			StyleAlignment::VERTICAL_JUSTIFY
//		);
//		$horizontalAlignmentStyles = array(
//			StyleAlignment::HORIZONTAL_GENERAL,
//			StyleAlignment::HORIZONTAL_LEFT,
//			StyleAlignment::HORIZONTAL_RIGHT,
//			StyleAlignment::HORIZONTAL_CENTER,
//			StyleAlignment::HORIZONTAL_CENTER_CONTINUOUS,
//			StyleAlignment::HORIZONTAL_JUSTIFY
//		);
//		$timezoneObj = new \DateTimeZone('Europe/London');
//		$GMT = new \DateTimeZone('UTC');
		// Check if file exists
//		if (!file_exists($pFilename)) {
//			throw new PHPExcelException("Could not open " . $pFilename . " for reading! File does not exist.");
//		}

//		if (!$this->canRead($pFilename)) {
//			throw new PHPExcelException($pFilename . " is an Invalid Spreadsheet file.");
//		}

		$xml = file_get_contents($pFilename);
//		file_put_contents("$pFilename-DUMP.xml", $xml);
		$root = XMLElementReader::loadFromString($xml);
//		$namespaces = $xml->getNamespaces(true);

//		$docProps = $objPHPExcel->getProperties();
//		$DocumentProperties = $xml->getElement("DocumentProperties");
//		if (count($DocumentProperties) > 0) {
//			foreach ($DocumentProperties as $propertyName => $propertyValue) {
//				switch ($propertyName) {
//					case 'Title':
//						$docProps->setTitle(self::convertStringEncoding($propertyValue, $this->charSet));
//						break;
//					case 'Subject':
//						$docProps->setSubject(self::convertStringEncoding($propertyValue, $this->charSet));
//						break;
//					case 'Author':
//						$docProps->setCreator(self::convertStringEncoding($propertyValue, $this->charSet));
//						break;
//					case 'Created':
//						$creationDate = SharedDate::parseDateTime($propertyValue);
//						$docProps->setCreated($creationDate);
//						break;
//					case 'LastAuthor':
//						$docProps->setLastModifiedBy(self::convertStringEncoding($propertyValue, $this->charSet));
//						break;
//					case 'LastSaved':
//						$lastSaveDate = SharedDate::parseDateTime($propertyValue);
//						$docProps->setModified($lastSaveDate);
//						break;
//					case 'Company':
//						$docProps->setCompany(self::convertStringEncoding($propertyValue, $this->charSet));
//						break;
//					case 'Category':
//						$docProps->setCategory(self::convertStringEncoding($propertyValue, $this->charSet));
//						break;
//					case 'Manager':
//						$docProps->setManager(self::convertStringEncoding($propertyValue, $this->charSet));
//						break;
//					case 'Keywords':
//						$docProps->setKeywords(self::convertStringEncoding($propertyValue, $this->charSet));
//						break;
//					case 'Description':
//						$docProps->setDescription(self::convertStringEncoding($propertyValue, $this->charSet));
//						break;
//					default:
//						// ignore anything else
//				}
//			}
//		}
		
		
		
//		$CustomDocumentProperties = $xml->getElements("CustomDocumentProperties");
//		if (count($CustomDocumentProperties) > 0) {
//			foreach ($CustomDocumentProperties[0] as $propertyName => $propertyValue) {
//				$propertyAttributes = $propertyValue->attributes($namespaces['dt']);
//				$propertyName = preg_replace_callback('/_x([0-9a-z]{4})_/', 'Excel2003XML::hex2str', $propertyName);
//				$propertyType = DocumentProperties::PROPERTY_TYPE_UNKNOWN;
//				switch ((string) $propertyAttributes) {
//					case 'string':
//						$propertyType = DocumentProperties::PROPERTY_TYPE_STRING;
//						$propertyValue = trim($propertyValue);
//						break;
//					case 'boolean':
//						$propertyType = DocumentProperties::PROPERTY_TYPE_BOOLEAN;
//						$propertyValue = (bool) $propertyValue;
//						break;
//					case 'integer':
//						$propertyType = DocumentProperties::PROPERTY_TYPE_INTEGER;
//						$propertyValue = intval($propertyValue);
//						break;
//					case 'float':
//						$propertyType = DocumentProperties::PROPERTY_TYPE_FLOAT;
//						$propertyValue = floatval($propertyValue);
//						break;
//					case 'dateTime.tz':
//						$propertyType = DocumentProperties::PROPERTY_TYPE_DATE;
//						$propertyValue = SharedDate::parseDateTime(trim($propertyValue));
//						break;
//				}
//				$docProps->setCustomProperty($propertyName, $propertyValue, $propertyType);
//			}
//		}

		$Styles = $root->getElement("Styles");
		if( $Styles !== NULL ){
			$subStyles = $Styles->getElements("Style");
			foreach ($subStyles as $style) {
				$style_ss = $style->attributes(self::NS_SPREADSHEET);
				$styleID = $style_ss['ID'];
				if ($styleID === 'Default') {
					$this->styles['Default'] = "";
				} else {
					$this->styles[$styleID] = $this->styles['Default'];
				}
				foreach ($style->getElements() as $styleData) {
					$styleType = $styleData->getLocalName();
					$styleAttributes = $styleData->attributes(self::NS_SPREADSHEET);
					switch ($styleType) {
						case 'NumberFormat':
							foreach ($styleAttributes as $styleAttributeValue) {
								$styleAttributeValue = (string) str_replace($fromFormats, $toFormats, $styleAttributeValue);
								switch ($styleAttributeValue) {
									case 'General':
									case 'General Number':
									case 'Standard':
									case 'Percent':
									case 'Fixed':
									case 'Scientific':
										$styleAttributeValue = 'General';
										break;
									
									case "Yes/No":
									case "True/False":
									case "On/Off":
										$styleAttributeValue = 'General';
										break;
									
									case 'General Date':
									case 'Long Date':
									case 'Medium Date':
										$styleAttributeValue = 'dd/mm/yyyy hh:mm:ss';
										break;
									
									case 'Short Date':
										$styleAttributeValue = 'dd/mm/yyyy';
										break;
									
									case 'Long Time':
										$styleAttributeValue = 'hh:mm:ss';
										break;
									
									case 'Short Time':
										$styleAttributeValue = 'hh:mm';
										break;
									
									case 'Currency':
										$styleAttributeValue = "[\$]";
										break;
									
									case 'Euro Currency':
										// \342\202\254 = Euro UTF-8
										$styleAttributeValue = "[\$\342\202\254]";
										break;
																		
									default:
								}
								if (strlen($styleAttributeValue) > 0) {
									$this->styles[$styleID] = $styleAttributeValue;
								}
							}
							break;
//						case 'Protection':
//							foreach ($styleAttributes as $styleAttributeKey => $styleAttributeValue) {
//							}
//							break;
						default:
					}
				}
			}
		}

		$worksheetID = 0;
		$root->registerXPathNamespace("ss", self::NS_SPREADSHEET);
		$worksheets = $root->xpath("ss:Worksheet");
		foreach($worksheets as $worksheet_element){
			$worksheet_ss = $worksheet_element->attributes(self::NS_SPREADSHEET);

//			if ((isset($this->loadSheetsOnly)) && (isset($worksheet_ss['Name'])) &&
//					(!in_array($worksheet_ss['Name'], $this->loadSheetsOnly))) {
//				continue;
//			}

			// Create new Worksheet
			$worksheet = $workbook->createSheet();
			$workbook->setActiveSheetIndex($worksheetID);
			if (isset($worksheet_ss['Name'])) {
				$worksheetName = self::convertStringEncoding($worksheet_ss['Name'], $this->charSet);
				//    Use false for $updateFormulaCellReferences to prevent adjustment of worksheet references in
				//        formula cells... during the load, all formulae should be correct, and we're simply bringing
				//        the worksheet name in line with the formula, not the reverse
				$worksheet->setTitle($worksheetName, false);
			}

			$Table = $worksheet_element->getElement("Table");
			
//			$columnName = 'A';
//			if ($Table !== NULL && ($Columns = $worksheet_element->getElements("Column")) !== NULL) {
//				foreach ($Columns as $columnData) {
//					$columnData_ss = $columnData->attributes(self::NS_SPREADSHEET);
//					if (isset($columnData_ss['Index'])) {
//						$columnName = Cell::stringFromColumnIndex((int) $columnData_ss['Index'] - 1);
//					}
//					if (isset($columnData_ss['Width'])) {
//						$columnWidth = $columnData_ss['Width'];
//						$worksheet->getColumnDimension($columnName)->setWidth((float)$columnWidth / 5.4);
//					}
//					$columnName = Cell::incColID($columnName);
//				}
//			}

			$rowNumber = 1;
			$Rows = $Table->getElements("Row");
				$additionalMergedCells = 0;
				foreach ($Rows as $rowData) {
//					$rowHasData = false;
					$row_ss = $rowData->attributes(self::NS_SPREADSHEET);
					if (isset($row_ss['Index'])) {
						$rowNumber = (integer) $row_ss['Index'];
					}
//                    echo '<b>Row '.$rowID.'</b><br />';

					$columnNumber = 1;
					$Cells = $rowData->getElements("Cell");
					foreach ($Cells as $cell) {
						$cell_ss = $cell->attributes(self::NS_SPREADSHEET);
//						if (isset($cell_ss['Index'])) {
//							$columnName = Coordinates::columnName((int)$cell_ss['Index']);
//						}
//						$cellRange = $columnName . $rowNumber;
						$cellCoords = Coordinates::create($columnNumber, $rowNumber);

//						if ($this->getReadFilter() !== null) {
//							if (!$this->getReadFilter()->readCell($columnID, $rowID, $worksheetName)) {
//								continue;
//							}
//						}
//
//						if ((isset($cell_ss['MergeAcross'])) || (isset($cell_ss['MergeDown']))) {
//							$columnTo = $columnID;
//							if (isset($cell_ss['MergeAcross'])) {
//								$additionalMergedCells += (int) $cell_ss['MergeAcross'];
//								$columnTo = Cell::stringFromColumnIndex(Coordinates::columnNumber($columnID) + (int) $cell_ss['MergeAcross'] - 1);
//							}
//							$rowTo = $rowID;
//							if (isset($cell_ss['MergeDown'])) {
//								$rowTo = $rowTo + (int)$cell_ss['MergeDown'];
//							}
//							$cellRange .= ':' . $columnTo . $rowTo;
//							$worksheet->mergeCells($cellRange);
//						}

						$cellIsSet = $hasCalculatedValue = false;
						$cellDataFormula = '';
						if (isset($cell_ss['Formula'])) {
							$cellDataFormula = $cell_ss['Formula'];
							// added this as a check for array formulas
							if (isset($cell_ss['ArrayRange'])) {
//								$cellDataCSEFormula = $cell_ss['ArrayRange'];
//                                echo "found an array formula at ".$columnID.$rowID."<br />";
							}
							$hasCalculatedValue = true;
						}
						$Data = $cell->getElement("Data");
						if ($Data !== NULL) {
							$cellValue = $Data->getText(); // cell value as a string
							/*. mixed .*/ $cellValueMixed = $cellValue; // will be set as typed cell value
							$type = Cell::TYPE_STRING;
							$cellData_ss = $Data->attributes(self::NS_SPREADSHEET);
							if (isset($cellData_ss['Type'])) {
								$cellDataType = $cellData_ss['Type'];
								switch ($cellDataType) {
									case 'Number':
										$type = Cell::TYPE_NUMERIC;
										$cellValueAsFloat = (float) $cellValue;
										$cellValueAsInt   = (int) $cellValue;
										if ($cellValueAsFloat == $cellValueAsInt) {
											$cellValueMixed = $cellValueAsInt;
										} else {
											$cellValueMixed = $cellValueAsFloat;
										}
										break;
									case 'Boolean':
										$type = Cell::TYPE_BOOL;
										$cellValueMixed = ((int)$cellValue != 0);
										break;
									case 'DateTime':
										// FIXME: sometimes LibreOffice does not set the StyleID for these cells, so by setting a generic NUMERIC type we loose info about the format.
										// Doing so, LibreOffice also looses the difference between "DateTime" and date-only or time-only.
										// Should we create a style if the StyleID attribute does not exist?
										$type = Cell::TYPE_NUMERIC;
										if( preg_match("/^1899-12-31T/", $cellValue) == 1 ){
											// Time is reported as a date+time set to 1899-12-31Thh:mm:ss.sss,
											// which strtotime() cannot parse. Workaround:
											$dummyTime = substr($cellValue, 11);
											$cellValueMixed = SharedDate::parseDateTime($dummyTime) / 86400.0;
										} else {
											$cellValueMixed = SharedDate::PHPToExcel(SharedDate::parseDateTime($cellValue));
										}
										break;
									case 'Error':
										$type = Cell::TYPE_ERROR;
										$cellValueMixed = self::convertStringEncoding($cellValue, $this->charSet);
										break;
									case 'String':
										$type = Cell::TYPE_STRING;
										$s = self::convertStringEncoding($cellValue, $this->charSet);
										if( isset($cellData_ss['Ticked']) && $cellData_ss['Ticked'] !== '0' && strlen($s) > 0 && $s[0] === "'" ){
											$s = substr($s, 1);
										}
										$cellValueMixed = $s;
										break;
									default:
										$cellValueMixed = self::convertStringEncoding($cellValue, $this->charSet);
										$type = Cell::TYPE_STRING;
								}
							}

							if ($hasCalculatedValue) {
								$type = Cell::TYPE_FORMULA;
								if (substr($cellDataFormula, 0, 3) === 'of:') {
									$cellDataFormula = substr($cellDataFormula, 3);
									$temp = explode('"', $cellDataFormula);
									$key = false;
									foreach ($temp as &$value) {
										//    Only replace in alternate array entries (i.e. non-quoted blocks)
										if ($key = !$key) {
											$value = (string) str_replace(array('[.', '.', ']'), '', $value);
										}
									}
								} else {
									//    Convert R1C1 style references to A1 style references (but only when not quoted)
//                                    echo 'Before: ', $cellDataFormula,'<br />';
									$temp = explode('"', $cellDataFormula);
									$key = false;
									foreach ($temp as &$value) {
										//    Only replace in alternate array entries (i.e. non-quoted blocks)
										if ($key = !$key) {
											preg_match_all('/(R(\\[?-?\\d*\\]?))(C(\\[?-?\\d*\\]?))/', $value, $cellReferences, PREG_SET_ORDER + PREG_OFFSET_CAPTURE);
											//    Loop through each R1C1 style reference in turn, converting it to its A1 style equivalent,
											//        then modify the formula to use that new reference. We work right to left.through
											//        the formula
											for($i = count($cellReferences)-1; $i >= 0; $i--){
												$cellReference = $cellReferences[$i];
												$rowReference = $cellReference[2][0];
												//    Empty R reference is the current row
												if ($rowReference === '') {
													$rowReference = "$rowNumber";
												}
												//    Bracketed R references are relative to the current row
												if ($rowReference[0] === '[') {
													$rowReference = "".($rowNumber + (int)trim($rowReference, '[]'));
												}
												$columnReference = $cellReference[4][0];
												//    Empty C reference is the current column
												if ($columnReference === '') {
													$columnReference = "$columnNumber";
												}
												//    Bracketed C references are relative to the current column
												if ($columnReference[0] === '[') {
													$columnReference = "".($columnNumber + (int)trim($columnReference, '[]'));
												}
												$A1CellReference = Coordinates::columnName((int)$columnReference) . $rowReference;
												$value = (string) substr_replace($value, $A1CellReference, (int)$cellReference[0][1], strlen($cellReference[0][0]));
											}
										}
									}
								}
								//    Then rebuild the formula string
								$cellDataFormula = implode('"', $temp);
//                                echo 'After: ', $cellDataFormula,'<br />';
							}

//                          echo 'Cell '.$columnID.$rowID.' is a '.$type.' with a value of '.(($hasCalculatedValue) ? $cellDataFormula : $cellValue).'<br />';
//
							$c = $worksheet->getCell($cellCoords);
							if ($hasCalculatedValue) {
								$c->setValueExplicit($cellDataFormula, $type);
								$c->setCalculatedValue($cellValueMixed);
							} else {
								$c->setValueExplicit($cellValueMixed, $type);
							}
							$cellIsSet = true;
//							$rowHasData = true;
						}

//						$Comment = $cell->getElement("Comment");
//						if ($Comment !== NULL) {
//							$commentAttributes = $Comment->attributes(self::SPREADSHEET_NS);
//							$author = 'unknown';
//							if (isset($commentAttributes["Author"])) {
//								$author = $commentAttributes["Author"];
//							}
//							$node = $Comment->getElement("Data")->asXML();
//							$annotation = strip_tags($node);
//							$objPHPExcel->getActiveSheet()->getComment($columnID . $rowID)->setAuthor(self::convertStringEncoding($author, $this->charSet))->setText($this->parseRichText($annotation));
//						}

						if (($cellIsSet) && (isset($cell_ss['StyleID']))) {
							$style_name = $cell_ss['StyleID'];
							if (isset($this->styles[$style_name]) && !empty($this->styles[$style_name])) {
								if (!$worksheet->cellExists($cellCoords)) {
									$worksheet->getCell($cellCoords)->setValue(null);
								}
								$worksheet->getStyle($cellCoords)->applyFormat($this->styles[$style_name]);
							}
						}
						$columnNumber++;
						while ($additionalMergedCells > 0) {
							$columnNumber++;
							$additionalMergedCells--;
						}
					}

//					if ($rowHasData) {
////						if (isset($row_ss['StyleID'])) {
////							$rowStyle = $row_ss['StyleID'];
////						}
//						if (isset($row_ss['Height'])) {
//							$rowHeight = $row_ss['Height'];
////                            echo '<b>Setting row height to '.$rowHeight.'</b><br />';
//							$objPHPExcel->getActiveSheet()->getRowDimension($rowID)->setRowHeight($rowHeight);
//						}
//					}

					++$rowNumber;
				}
			++$worksheetID;
		}

		// Return
		return $workbook;
	}

	/**
	 * Loads PHPExcel from file
	 *
	 * @param  string $pFilename
	 * @return Workbook
	 * @throws PHPExcelException
	 * @throws \ErrorException
	 */
	public function load($pFilename) {
		// Create new PHPExcel
		$workbook = new Workbook();
//		$workbook->removeSheetByIndex(0);

		// Load into this instance
		return $this->loadIntoExisting($pFilename, $workbook);
	}

}
