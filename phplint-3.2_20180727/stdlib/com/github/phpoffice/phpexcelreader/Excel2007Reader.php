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
 * @version    $Date: 2018/04/13 09:55:29 $
 */

namespace com\github\phpoffice\phpexcelreader;

require_once __DIR__ . "/../../../../all.php";

/*. require_module 'zip'; .*/

use ZipArchive;
use it\icosaedro\utils\Errors;


/**
 * @access private
 */
class Excel2007Reader_SharedFormula {
	/**
	 *
	 * @var Coordinates
	 */
	public $coordinates;
	/**
	 *
	 * @var string
	 */
	public $formula;
}


/**
 * Reader for Office Open XML (.xlsx), Excel 2007 and above.
 */
class Excel2007Reader extends AbstractReader {

	/**
	 * ReferenceHelper instance
	 *
	 * @var ReferenceHelper
	 */
	private $referenceHelper = null;

//	/**
//	 * PHPExcel_Reader_Excel2007Reader_Theme instance
//	 *
//	 * @var PHPExcel_Reader_Excel2007Reader_Theme
//	 */
//	private static $theme = null;

	/**
	 * Create a new ReaderExcel2007Reader instance
	 */
	public function __construct() {
//		$this->readFilter = new DefaultReadFilter();
		$this->referenceHelper = ReferenceHelper::getInstance();
	}

	/**
	 * 
	 * @param ZipArchive $archive
	 * @param string $fileName
	 * @return string
	 */
	private function getFromZipArchive($archive, $fileName) {
		// Root-relative paths
//		if (strpos($fileName, '//') !== false) {
//			$fileName = substr($fileName, strpos($fileName, '//') + 1);
//		}
//		$fileName = SharedFile::realpath($fileName);

		// Apache POI fixes
		$contents = $archive->getFromName($fileName);
		if ($contents === false) {
			$contents = $archive->getFromName(substr($fileName, 1));
		}

		return $contents;
	}

	/**
	 * Can the current reader read the file?
	 *
	 * @param     string         $pFilename
	 * @return     boolean
	 * @throws \ErrorException
	 */
	public function canRead($pFilename) {
		$xl = false;
		// Load file
		$zip = new ZipArchive();
		if ($zip->open($pFilename) === true) {
			// check if it is an OOXML archive
			$xml = $this->getFromZipArchive($zip, "_rels/.rels");
			$rels = XMLElementReader::loadFromString($xml);
//			if ($rels !== false) {
				foreach ($rels->getElements("Relationship") as $rel) {
					switch ($rel->getAttribute("Type")) {
						case "http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument":
							if (basename($rel->getAttribute("Target")) === 'workbook.xml') {
								$xl = true;
							}
							break;
						default:
					}
				}
//			}
			$zip->close();
		}

		return $xl;
	}

	/**
	 * @param XMLElementReader $c
	 * @return boolean
	 */
	private static function castToBoolean($c) {
		$v = $c->getElement("v");
		$value =  $v !== NULL? $v->getText() : /*. (string) .*/ null;
		if ($value === '0') {
			return false;
		} elseif ($value === '1') {
			return true;
		} else {
			throw new \RuntimeException("expected 0 or 1: $value");
		}
	}

	/**
	 * @param XMLElementReader $c
	 * @return string
	 */
	private static function castToError($c) {
		$v = $c->getElement("v");
		return  $v !== NULL? $v->getText() : /*. (string) .*/ null;
	}


	/**
	 * @param XMLElementReader $c
	 * @return string
	 */
	private static function castToString($c) {
		$v = $c->getElement("v");
		return  $v !== NULL? $v->getText() : /*. (string) .*/ null;
	}
	
	/**
	 * 
	 * @param XMLElementReader $c
	 * @param Coordinates $r
	 * @param string & $cellDataType
	 * @param mixed & $value Returns the value as a string. Formal type set to
	 * 'mized' to simplify the calling code, where $value is 'mixed'.
	 * @param mixed & $calculatedValue
	 * @param Excel2007Reader_SharedFormula[int] & $sharedFormulas
	 * @param string $castBaseType
	 * @return void
	 * @throws PHPExcelException
	 */
	private function castToFormula($c, $r, /*. return .*/ &$cellDataType, /*. return .*/ &$value, /*. return .*/ &$calculatedValue, &$sharedFormulas, $castBaseType) {
		$cellDataType = 'f';
		$f = $c->getElement("f");
		$value = "=".$f->getText();
		
		// LibreOffice (and probably Excell too) saves boolean values FALSE and
		// TRUE as statically evaluable formulas we can easily resolve here:
		if( $value === "=FALSE()" ){
			$cellDataType = Cell::TYPE_BOOL;
			$value = FALSE;
			$calculatedValue = NULL;
			return;
		}
		if( $value === "=TRUE()" ){
			$cellDataType = Cell::TYPE_BOOL;
			$value = TRUE;
			$calculatedValue = NULL;
			return;
		}
		
		switch($castBaseType){
			case "castToBoolean": $calculatedValue = self::castToBoolean($c); break;
			case "castToError": $calculatedValue = self::castToError($c); break;
			case "castToString": $calculatedValue = self::castToString($c); break;
			default: throw new \RuntimeException("unknown cast type: $castBaseType");
		}

		// Shared formula?
		$shared = $f->getAttribute("t", NULL);
		if ($shared !== NULL && strtolower($shared) === 'shared') {
			$instance = (int) $f->getAttribute("si");
			if (!isset($sharedFormulas[$instance])) {
				$shared_formula = new Excel2007Reader_SharedFormula();
				$shared_formula->coordinates = $r;
				$shared_formula->formula = (string) $value;
				$sharedFormulas[$instance] = $shared_formula;
			} else {
				$shared_formula = $sharedFormulas[$instance];
				$master = $shared_formula->coordinates;
				$current = $r;
				$difference_col = $current->getColumnNumber() - $master->getColumnNumber();
				$difference_row = $current->getRow() - $master->getRow();
				$value = $this->referenceHelper->updateFormulaReferences(
					$shared_formula->formula, 'A1',
					$difference_col, $difference_row
				);
			}
		}
	}

	
//	/**
//	 * 
//	 * @param SimpleXMLElementReader[int] $array_
//	 * @param int $key
//	 * @return string
//	 */
//	private static function getArrayItem($array_, $key = 0) {
//		return (isset($array_[$key]) ? $array_[$key] : null);
//	}

	/**
	 * 
	 * @param mixed $value
	 * @return boolean
	 */
	private static function parseBoolean($value) {
		if (is_object($value)) {
			$value = (string) $value;
		}
		if (is_numeric($value)) {
			return (bool) $value;
		}
		return ($value === 'true' || $value === 'TRUE');
	}

	/**
	 * Collect and join all the RichText runs, discarding any formatting option.
	 * @param XMLElementReader $is
	 * @return string
	 */
	private function parseRichText($is) {

		$t = $is->getElement("t");
		if ($t !== NULL) {
			$value = SharedString::ControlCharacterOOXML2PHP($t->getText());
		} else {
			$value = "";
			foreach ($is->getElements("r") as $run) {
				$value .= SharedString::ControlCharacterOOXML2PHP($run->getElement("t")->getText());
			}
		}

		return $value;
	}

	/**
	 * Loads PHPExcel from file
	 *
	 * @param   string  $pFilename
	 * @return  Workbook
	 * @throws  PHPExcelException
	 * @throws  \ErrorException
	 */
	public function load($pFilename) {
		// Initialisations
		$excel = new Workbook();
//		$excel->removeSheetByIndex(0);
//		if (!$this->readDataOnly) {
//			$excel->removeCellStyleXfByIndex(0); // remove the default style
//			$excel->removeCellXfByIndex(0); // remove the default style
//		}

		$zip = new ZipArchive();
		$err = $zip->open($pFilename);
		if ($err !== TRUE) {
			throw new PHPExcelException("Could not open $pFilename: ZipArchive::" . Errors::getClassConstantName("ZipArchive", "ER_", $err));
		}

//		//    Read the theme first, because we need the colour scheme when reading the styles
//		$wbRels = XMLElementReader::loadFromString($this->securityScan($this->getFromZipArchive($zip, "xl/_rels/workbook.xml.rels"))); //~ http://schemas.openxmlformats.org/package/2006/relationships");
//		foreach ($wbRels->Relationship as $rel) {
//			switch ($rel["Type"]) {
//				case "http://schemas.openxmlformats.org/officeDocument/2006/relationships/theme":
//					$themeOrderArray = array('lt1', 'dk1', 'lt2', 'dk2');
//					$themeOrderAdditional = count($themeOrderArray);
//
//					$xmlTheme = XMLElementReader::loadFromString($this->securityScan($this->getFromZipArchive($zip, "xl/{$rel['Target']}")));
//					if (is_object($xmlTheme)) {
//						$xmlThemeName = $xmlTheme->attributes();
//						$xmlTheme = $xmlTheme->children("http://schemas.openxmlformats.org/drawingml/2006/main");
//						$themeName = (string) $xmlThemeName['name'];
//
//						$colourScheme = $xmlTheme->themeElements->clrScheme->attributes();
//						$colourSchemeName = (string) $colourScheme['name'];
//						$colourScheme = $xmlTheme->themeElements->clrScheme->children("http://schemas.openxmlformats.org/drawingml/2006/main");
//
//						$themeColours = array();
//						foreach ($colourScheme as $k => $xmlColour) {
//							$themePos = array_search($k, $themeOrderArray);
//							if ($themePos === false) {
//								$themePos = $themeOrderAdditional++;
//							}
//							if (isset($xmlColour->sysClr)) {
//								$xmlColourData = $xmlColour->sysClr->attributes();
//								$themeColours[$themePos] = $xmlColourData['lastClr'];
//							} elseif (isset($xmlColour->srgbClr)) {
//								$xmlColourData = $xmlColour->srgbClr->attributes();
//								$themeColours[$themePos] = $xmlColourData['val'];
//							}
//						}
//						self::$theme = new PHPExcel_Reader_Excel2007Reader_Theme($themeName, $colourSchemeName, $themeColours);
//					}
//					break;
//			}
//		}

		$rels = XMLElementReader::loadFromString($this->getFromZipArchive($zip, "_rels/.rels")); //~ http://schemas.openxmlformats.org/package/2006/relationships");
		foreach ($rels->getElements("Relationship") as $rel) {
			switch ($rel->getAttribute("Type")) {
				case "http://schemas.openxmlformats.org/package/2006/relationships/metadata/core-properties":
					$xml = $this->getFromZipArchive($zip, $rel->getAttribute('Target'));
					$xmlCore = XMLElementReader::loadFromString($xml);
					if (is_object($xmlCore)) {
						$xmlCore->registerXPathNamespace("dc", "http://purl.org/dc/elements/1.1/");
						$xmlCore->registerXPathNamespace("dcterms", "http://purl.org/dc/terms/");
						$xmlCore->registerXPathNamespace("cp", "http://schemas.openxmlformats.org/package/2006/metadata/core-properties");
						$docProps = $excel->getProperties();
						if( count($elems = $xmlCore->xpath("dc:creator")) > 0 )
							$docProps->setCreator($elems[0]->getText());
						if( count($elems = $xmlCore->xpath("cp:lastModifiedBy")) > 0 )
							$docProps->setLastModifiedBy($elems[0]->getText());
						if( count($elems = $xmlCore->xpath("dcterms:created")) > 0 )
							$docProps->setCreated(SharedDate::parseDateTime($elems[0]->getText())); //! respect xsi:type
						if( count($elems = $xmlCore->xpath("dcterms:modified")) > 0 )
							$docProps->setModified(SharedDate::parseDateTime($elems[0]->getText())); //! respect xsi:type
						if( count($elems = $xmlCore->xpath("dc:title")) > 0 )
							$docProps->setTitle($elems[0]->getText());
						if( count($elems = $xmlCore->xpath("dc:description")) > 0 )
							$docProps->setDescription($elems[0]->getText());
						if( count($elems = $xmlCore->xpath("dc:subject")) > 0 )
							$docProps->setSubject($elems[0]->getText());
						if( count($elems = $xmlCore->xpath("cp:keywords")) > 0 )
							$docProps->setKeywords($elems[0]->getText());
						if( count($elems = $xmlCore->xpath("cp:category")) > 0 )
							$docProps->setCategory($elems[0]->getText());
					}
					break;
//				case "http://schemas.openxmlformats.org/officeDocument/2006/relationships/extended-properties":
//					$xmlCore = XMLElementReader::loadFromString($this->securityScan($this->getFromZipArchive($zip, $rel->getAttribute('Target'))));
//					if (is_object($xmlCore)) {
//						$docProps = $excel->getProperties();
//						if (isset($xmlCore->Company)) {
//							$docProps->setCompany((string) $xmlCore->Company);
//						}
//						if (isset($xmlCore->Manager)) {
//							$docProps->setManager((string) $xmlCore->Manager);
//						}
//					}
//					break;
//				case "http://schemas.openxmlformats.org/officeDocument/2006/relationships/custom-properties":
//					$xmlCore = XMLElementReader::loadFromString($this->securityScan($this->getFromZipArchive($zip, $rel->getAttribute('Target'))));
//					if (is_object($xmlCore)) {
//						$docProps = $excel->getProperties();
//						foreach ($xmlCore as $xmlProperty) {
//							$cellDataOfficeAttributes = $xmlProperty->attributes();
//							if (isset($cellDataOfficeAttributes['name'])) {
//								$propertyName = (string) $cellDataOfficeAttributes['name'];
//								$cellDataOfficeChildren = $xmlProperty->children('http://schemas.openxmlformats.org/officeDocument/2006/docPropsVTypes');
//								$attributeType = $cellDataOfficeChildren->getName();
//								$attributeValue = (string) $cellDataOfficeChildren->{$attributeType};
//								$attributeValue = DocumentProperties::convertProperty($attributeValue, $attributeType);
//								$attributeType = DocumentProperties::convertPropertyType($attributeType);
//								$docProps->setCustomProperty($propertyName, $attributeValue, $attributeType);
//							}
//						}
//					}
//					break;
//				//Ribbon
//				case "http://schemas.microsoft.com/office/2006/relationships/ui/extensibility":
//					$customUI = $rel['Target'];
//					if (!is_null($customUI)) {
//						$this->readRibbon($excel, $customUI, $zip);
//					}
//					break;
				case "http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument":
					$dir = dirname($rel->getAttribute("Target"));
					$relsWorkbook = XMLElementReader::loadFromString($this->getFromZipArchive($zip, "$dir/_rels/" . basename($rel->getAttribute("Target")) . ".rels"));  //~ http://schemas.openxmlformats.org/package/2006/relationships");
					$relsWorkbook->registerXPathNamespace("rel", "http://schemas.openxmlformats.org/package/2006/relationships");
					$sharedStrings = /*. (string[int]) .*/ array();
					$xpath = $relsWorkbook->xpath("rel:Relationship[@Type='http://schemas.openxmlformats.org/officeDocument/2006/relationships/sharedStrings']");
					if( count($xpath) > 0 ){
						$xmlStrings = XMLElementReader::loadFromString($this->getFromZipArchive($zip, "$dir/" . $xpath[0]->getAttribute("Target")));  //~ http://schemas.openxmlformats.org/spreadsheetml/2006/main");
						foreach ($xmlStrings->getElements("si") as $val) {
							$t = $val->getElement("t");
							if ($t !== NULL) {
								$sharedStrings[] = SharedString::ControlCharacterOOXML2PHP($t->getText());
							} elseif ($val->getElement("r") !== NULL) {
								$sharedStrings[] = $this->parseRichText($val);
							}
						}
					}

					$worksheets = /*. (string[string]) .*/ array();
//					$macros = $customUI = null;
					foreach ($relsWorkbook->getElements("Relationship") as $ele) {
						switch ($ele->getAttribute('Type')) {
							case "http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet":
								$worksheets[$ele->getAttribute("Id")] = $ele->getAttribute("Target");
								break;
//							// a vbaProject ? (: some macros)
//							case "http://schemas.microsoft.com/office/2006/relationships/vbaProject":
//								$macros = $ele["Target"];
//								break;
							default:
						}
					}

//					if (!is_null($macros)) {
//						$macrosCode = $this->getFromZipArchive($zip, 'xl/vbaProject.bin'); //vbaProject.bin always in 'xl' dir and always named vbaProject.bin
//						if ($macrosCode !== false) {
//							$excel->setMacrosCode($macrosCode);
//							$excel->setHasMacros(true);
//							//short-circuit : not reading vbaProject.bin.rel to get Signature =>allways vbaProjectSignature.bin in 'xl' dir
//							$Certificate = $this->getFromZipArchive($zip, 'xl/vbaProjectSignature.bin');
//							if ($Certificate !== false) {
//								$excel->setMacrosCertificate($Certificate);
//							}
//						}
//					}
					$styles = /*. (Style[int]) .*/ array();
//					$cellStyles = array();
					$xpath = $relsWorkbook->xpath("rel:Relationship[@Type='http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles']");
					
					$xml = $this->getFromZipArchive($zip, "$dir/" . $xpath[0]->getAttribute("Target"));
					//file_put_contents($pFilename."-DUMP-styles.xml", $xml);
					$xmlStyles = XMLElementReader::loadFromString($xml); //~ http://schemas.openxmlformats.org/spreadsheetml/2006/main");

					$numFmts = $xmlStyles->getElement("numFmts");
					$numFmts->registerXPathNamespace("sml", "http://schemas.openxmlformats.org/spreadsheetml/2006/main");
//					if (!$this->readDataOnly) {
						foreach ($xmlStyles->getElement("cellXfs")->getElements("xf") as $xf) {
							$numFmt = Style::FORMAT_GENERAL;

							$numFmtId = (int) $xf->getAttribute("numFmtId", "-1");
							if ($numFmtId >= 0) {
//								if (isset($numFmts)) {
									$tmpNumFmt = $numFmts->xpath("sml:numFmt[@numFmtId=$numFmtId]");

									if (count($tmpNumFmt) > 0 && $tmpNumFmt[0]->getAttribute("formatCode", NULL) !== NULL) {
										$numFmt = $tmpNumFmt[0]->getAttribute("formatCode");
									}
//								}

								// We shouldn't override any of the built-in MS Excel values (values below id 164)
								//  But there's a lot of naughty homebrew xlsx writers that do use "reserved" id values that aren't actually used
								//  So we make allowance for them rather than lose formatting masks
								if ($numFmtId < 164 && Style::builtInFormatCode($numFmtId) !== NULL) {
									$numFmt = Style::builtInFormatCode($numFmtId);
								}
							}
//							$quotePrefix = (boolean) $xf->getAttribute("quotePrefix", "FALSE");
							// add style to cellXf collection
							$style = new Style();
							$style->setFormatCode($numFmt);
							$excel->addCellXf($style);
							$styles[] = $style;
						}

//						foreach ($xmlStyles->getElement("cellStyleXfs")->getElements("xf") as $xf) {
//							$numFmt = Style::FORMAT_GENERAL;
//							if ($xf->getAttribute("numFmtId",NULL) !== NULL) {
//								$xfNumFmtId = (int) $xf->getAttribute("numFmtId");
//								$tmpNumFmts = $numFmts->xpath("sml:numFmt[@numFmtId=$xfNumFmtId]");
//								if (count($tmpNumFmts) > 0 && $tmpNumFmts[0]->getAttribute("formatCode", NULL) !== NULL) {
//									$numFmt = $tmpNumFmts[0]->getAttribute("formatCode");
//								} elseif ($xfNumFmtId < 165) {
//									$numFmt = Style::builtInFormatCode($xfNumFmtId);
//								}
//							}
//							
//
//							$cellStyle = array(
//										"numFmt" => $numFmt,
//										"font" => $xmlStyles->fonts->font[intval($xf["fontId"])],
//										"fill" => $xmlStyles->fills->fill[intval($xf["fillId"])],
//										"border" => $xmlStyles->borders->border[intval($xf["borderId"])],
//										"alignment" => $xf->alignment,
//										"protection" => $xf->protection,
//										"quotePrefix" => $quotePrefix,
//							);
//							$cellStyles[] = $cellStyle;
//
//							// add style to cellStyleXf collection
//							$objStyle = new Style;
//							self::readStyle($objStyle, $cellStyle);
//							$excel->addCellStyleXf($objStyle);
//						}
//					}

//					$dxfs = array();
//					if (!$this->readDataOnly) {
//						//    Conditional Styles
//						$dxfs = $xmlStyles->getElement("dxfs");
//						if ($dxfs !== NULL) {
//							foreach ($dxfs->getElements("dxf") as $dxf) {
//								$style = new Style(false, true);
//								self::readStyle($style, $dxf);
//								$dxfs[] = $style;
//							}
//						}
//						//    Cell Styles
//						$cellStylesElement = $xmlStyles->getElement("cellStyles");
//						if ($cellStylesElement !== NULL) {
//							foreach ($cellStylesElement->getElements("cellStyle") as $cellStyle) {
//								if (intval($cellStyle->getAttribute('builtinId')) == 0) {
//									$xfId = (int) $cellStyle->getAttribute('xfId');
//									if (isset($cellStyles[$xfId])) {
//										// Set default style
//										$style = new Style;
//										self::readStyle($style, $cellStyles[$xfId]);
//
//										// normal style, currently not using it for anything
//									}
//								}
//							}
//						}
//					}

					$xmlWorkbook = XMLElementReader::loadFromString($this->getFromZipArchive($zip, $rel->getAttribute('Target')));  //~ http://schemas.openxmlformats.org/spreadsheetml/2006/main");
					// Set base date
					$workbookPr = $xmlWorkbook->getElement("workbookPr");
					if ($workbookPr !== NULL) {
						if (self::parseBoolean($workbookPr->getAttribute("date1904", "false"))) {
							SharedDate::setExcelCalendar(SharedDate::CALENDAR_MAC_1904);
						} else {
							SharedDate::setExcelCalendar(SharedDate::CALENDAR_WINDOWS_1900);
						}
					}

//					$sheetId = 0; // keep track of new sheet id in final workbook
					$oldSheetId = -1; // keep track of old sheet id in final workbook
					$countSkippedSheets = 0; // keep track of number of skipped sheets
					$mapSheetId = /*. (int[int]) .*/ array(); // mapping of sheet ids from old to new

//					$charts = $chartDetails = array();

					$sheets = $xmlWorkbook->getElement("sheets");
					if ($sheets !== NULL) {
						foreach ($sheets->getElements("sheet") as $eleSheet) {
							++$oldSheetId;

//							// Check if sheet should be skipped
//							if (isset($this->loadSheetsOnly) && !in_array($eleSheet->getAttribute("name"), $this->loadSheetsOnly)) {
//								++$countSkippedSheets;
//								$mapSheetId[$oldSheetId] = -1;
//								continue;
//							}

							// Map old sheet id in original workbook to new sheet id.
							// They will differ if loadSheetsOnly() is being used
							$mapSheetId[$oldSheetId] = $oldSheetId - $countSkippedSheets;

							// Load sheet
							$docSheet = $excel->createSheet();
							//    Use false for $updateFormulaCellReferences to prevent adjustment of worksheet
							//        references in formula cells... during the load, all formulae should be correct,
							//        and we're simply bringing the worksheet name in line with the formula, not the
							//        reverse
							$docSheet->setTitle($eleSheet->getAttribute("name"), false);
							$fileWorksheet = $worksheets[$eleSheet->attributes("http://schemas.openxmlformats.org/officeDocument/2006/relationships")["id"]];
							$xml = $this->getFromZipArchive($zip, "$dir/$fileWorksheet");
//							file_put_contents($pFilename."-DUMP-sheets.xml", $xml);
							$xmlSheet = XMLElementReader::loadFromString($xml);  //~ http://schemas.openxmlformats.org/spreadsheetml/2006/main");
							
							$sharedFormulas = /*. (Excel2007Reader_SharedFormula[int]) .*/ array();

							$state = $eleSheet->getAttribute("state", NULL);
							if ($state !== NULL && strlen($state) > 0) {
								$docSheet->setSheetState($state);
							}

//							if (isset($xmlSheet->sheetViews) && isset($xmlSheet->sheetViews->sheetView)) {
//								if (isset($xmlSheet->sheetViews->sheetView['zoomScale'])) {
//									$docSheet->getSheetView()->setZoomScale(intval($xmlSheet->sheetViews->sheetView['zoomScale']));
//								}
//								if (isset($xmlSheet->sheetViews->sheetView['zoomScaleNormal'])) {
//									$docSheet->getSheetView()->setZoomScaleNormal(intval($xmlSheet->sheetViews->sheetView['zoomScaleNormal']));
//								}
//								if (isset($xmlSheet->sheetViews->sheetView['view'])) {
//									$docSheet->getSheetView()->setView((string) $xmlSheet->sheetViews->sheetView['view']);
//								}
//								if (isset($xmlSheet->sheetViews->sheetView['showGridLines'])) {
//									$docSheet->setShowGridLines(self::boolean((string) $xmlSheet->sheetViews->sheetView['showGridLines']));
//								}
//								if (isset($xmlSheet->sheetViews->sheetView['showRowColHeaders'])) {
//									$docSheet->setShowRowColHeaders(self::boolean((string) $xmlSheet->sheetViews->sheetView['showRowColHeaders']));
//								}
//								if (isset($xmlSheet->sheetViews->sheetView['rightToLeft'])) {
//									$docSheet->setRightToLeft(self::boolean((string) $xmlSheet->sheetViews->sheetView['rightToLeft']));
//								}
//								if (isset($xmlSheet->sheetViews->sheetView->pane)) {
//									if (isset($xmlSheet->sheetViews->sheetView->pane['topLeftCell'])) {
//										$docSheet->freezePane((string) $xmlSheet->sheetViews->sheetView->pane['topLeftCell']);
//									} else {
//										$xSplit = 0;
//										$ySplit = 0;
//
//										if (isset($xmlSheet->sheetViews->sheetView->pane['xSplit'])) {
//											$xSplit = 1 + intval($xmlSheet->sheetViews->sheetView->pane['xSplit']);
//										}
//
//										if (isset($xmlSheet->sheetViews->sheetView->pane['ySplit'])) {
//											$ySplit = 1 + intval($xmlSheet->sheetViews->sheetView->pane['ySplit']);
//										}
//
//										$docSheet->freezePaneByColumnAndRow($xSplit, $ySplit);
//									}
//								}
//
//								if (isset($xmlSheet->sheetViews->sheetView->selection)) {
//									if (isset($xmlSheet->sheetViews->sheetView->selection['sqref'])) {
//										$sqref = (string) $xmlSheet->sheetViews->sheetView->selection['sqref'];
//										$sqref = explode(' ', $sqref);
//										$sqref = $sqref[0];
//										$docSheet->setSelectedCells($sqref);
//									}
//								}
//							}

							$sheetPr = $xmlSheet->getElement("sheetPr");
//							if ($sheetPr !== NULL && isset($xmlSheet->sheetPr->tabColor)) {
//								if (isset($xmlSheet->sheetPr->tabColor['rgb'])) {
//									$docSheet->getTabColor()->setARGB((string) $xmlSheet->sheetPr->tabColor['rgb']);
//								}
//							}
							if ($sheetPr !== NULL && $sheetPr->getAttribute('codeName', NULL) !== NULL) {
								$docSheet->setCodeName($sheetPr->getAttribute('codeName'));
							}
//							if ($sheetPr !== NULL && isset($xmlSheet->sheetPr->outlinePr)) {
//								if (isset($xmlSheet->sheetPr->outlinePr['summaryRight']) &&
//										!self::parseBoolean((string) $xmlSheet->sheetPr->outlinePr['summaryRight'])) {
//									$docSheet->setShowSummaryRight(false);
//								} else {
//									$docSheet->setShowSummaryRight(true);
//								}
//
//								if (isset($xmlSheet->sheetPr->outlinePr['summaryBelow']) &&
//										!self::parseBoolean((string) $xmlSheet->sheetPr->outlinePr['summaryBelow'])) {
//									$docSheet->setShowSummaryBelow(false);
//								} else {
//									$docSheet->setShowSummaryBelow(true);
//								}
//							}

							$sheetData = $xmlSheet->getElement("sheetData");
							if ($sheetData !== NULL ){
								foreach ($sheetData->getElements("row") as $row) {
//									$row_number = intval($row->getAttribute("r"));
//									if ($row->getAttribute("ht", NULL) !== NULL && !$this->readDataOnly) {
//										$docSheet->getRowDimension(intval($row->getAttribute("ht")))->setRowHeight(floatval($row->getAttribute("ht")));
//									}
//									if (self::parseBoolean($row->getAttribute("hidden", "false")) && !$this->readDataOnly) {
//										$docSheet->getRowDimension($row_number)->setVisible(false);
//									}
//									if (self::parseBoolean($row->getAttribute("collapsed", "false"))) {
//										$docSheet->getRowDimension($row_number)->setCollapsed(true);
//									}
//									$outlineLevel = intval($row->getAttribute("outlineLevel", "0"));
//									if ($outlineLevel > 0) {
//										$docSheet->getRowDimension($row_number)->setOutlineLevel($outlineLevel);
//									}
//									if ($row->getAttribute("s", NULL) !== NULL && !$this->readDataOnly) {
//										$docSheet->getRowDimension($row_number)->setXfIndex(intval($row->getAttribute("s")));
//									}

									foreach($row->getElements("c") as $c) {
										$coords = $c->getAttribute("r");
										$cellCoords = Coordinates::parse($coords);
										$cellDataType = $c->getAttribute("t", "");
										$value = /*. (mixed) .*/ null;
										$calculatedValue = /*. (mixed) .*/ null;

//										// Read cell?
//										if ($this->getReadFilter() !== null) {
//											$coordinates = Cell::coordinateFromString($r);
//
//											if (!$this->getReadFilter()->readCell($coordinates[0], $coordinates[1], $docSheet->getTitle())) {
//												continue;
//											}
//										}

										// Read cell!
										$f = $c->getElement("f");
										switch ($cellDataType) {
											case "s":
												$cellDataType = Cell::TYPE_STRING;
												$s = $c->getElement("v")->getText();
												if (strlen($s) > 0) {
													$value = $sharedStrings[intval($s)];

//													if ($value instanceof RichText) {
//														$value = clone $value;
//													}
												} else {
													$value = '';
												}
												break;
											case "b":
												if ($f === NULL) {
													$cellDataType = Cell::TYPE_BOOL;
													$value = self::castToBoolean($c);
												} else {
													$cellDataType = Cell::TYPE_FORMULA;
													$this->castToFormula($c, $cellCoords, $cellDataType, $value, $calculatedValue, $sharedFormulas, 'castToBoolean');
//													if ($f->getAttribute("t", NULL) !== NULL) {
//														$att = array();
//														$att = $f;
//														$docSheet->getCell($r)->setFormulaAttributes($att);
//													}
												}
												break;
											case "inlineStr":
												if ($f !== NULL) {
													$cellDataType = Cell::TYPE_FORMULA;
													$this->castToFormula($c, $cellCoords, $cellDataType, $value, $calculatedValue, $sharedFormulas, 'castToFormula');
												} else {
													$cellDataType = Cell::TYPE_INLINE;
													$value = $this->parseRichText($c->getElement("is"));
												}
												break;
											case "e":
												if ($f === NULL) {
													$cellDataType = Cell::TYPE_ERROR;
													$value = self::castToError($c);
												} else {
													$cellDataType = Cell::TYPE_FORMULA;
													$this->castToFormula($c, $cellCoords, $cellDataType, $value, $calculatedValue, $sharedFormulas, 'castToError');
												}
												break;
											default:
												if ($f === NULL) {
													$value = self::castToString($c);
													if( $value === NULL )
														$cellDataType = Cell::TYPE_NULL;
													else
														$cellDataType = Cell::TYPE_STRING;
												} else {
													$cellDataType = Cell::TYPE_FORMULA;
													$this->castToFormula($c, $cellCoords, $cellDataType, $value, $calculatedValue, $sharedFormulas, 'castToString');
												}
												break;
										}
										
										// Check for numeric values
//										if (is_numeric($value) && $cellDataType !== 's') {
//											if( is_string($value) ){
//												$value = (float) $value;
//											}
//										}
										if( ($cellDataType === Cell::TYPE_INLINE || $cellDataType === Cell::TYPE_STRING)
										&& is_numeric($value) ){
											$cellDataType = Cell::TYPE_NUMERIC;
											$valueFloat = (float) $value;
											if( $valueFloat == (int) $valueFloat ){
												$value = (int) $valueFloat;
											} else {
												$value = $valueFloat;
											}
										}

//										// Rich text?
//										if ($value instanceof RichText && $this->readDataOnly) {
//											$value = $value->getPlainText();
//										}

										$cell = $docSheet->getCell($cellCoords);
										// Assign value
										$cell->setValueExplicit($value, $cellDataType);
										if ($calculatedValue !== null) {
											$cell->setCalculatedValue($calculatedValue);
										}

										// Style information?
										$i = (int) $c->getAttribute("s", "-1");
										if ($i >= 0 && isset($styles[$i])) {
											$cell->setXfIndex($styles[$i]->getIndex());
										}
									}
								}
							}

//							$conditionals = array();
//							if (!$this->readDataOnly && $xmlSheet && $xmlSheet->conditionalFormatting) {
//								foreach ($xmlSheet->conditionalFormatting as $conditional) {
//									foreach ($conditional->cfRule as $cfRule) {
//										if (((string) $cfRule["type"] == PHPExcel_Style_Conditional::CONDITION_NONE || (string) $cfRule["type"] == PHPExcel_Style_Conditional::CONDITION_CELLIS || (string) $cfRule["type"] == PHPExcel_Style_Conditional::CONDITION_CONTAINSTEXT || (string) $cfRule["type"] == PHPExcel_Style_Conditional::CONDITION_EXPRESSION) && isset($dxfs[intval($cfRule["dxfId"])])) {
//											$conditionals[(string) $conditional["sqref"]][intval($cfRule["priority"])] = $cfRule;
//										}
//									}
//								}
//
//								foreach ($conditionals as $ref => $cfRules) {
//									ksort($cfRules);
//									$conditionalStyles = array();
//									foreach ($cfRules as $cfRule) {
//										$objConditional = new PHPExcel_Style_Conditional();
//										$objConditional->setConditionType((string) $cfRule["type"]);
//										$objConditional->setOperatorType((string) $cfRule["operator"]);
//
//										if ((string) $cfRule["text"] != '') {
//											$objConditional->setText((string) $cfRule["text"]);
//										}
//
//										if (count($cfRule->formula) > 1) {
//											foreach ($cfRule->formula as $formula) {
//												$objConditional->addCondition((string) $formula);
//											}
//										} else {
//											$objConditional->addCondition((string) $cfRule->formula);
//										}
//										$objConditional->setStyle(clone $dxfs[intval($cfRule["dxfId"])]);
//										$conditionalStyles[] = $objConditional;
//									}
//
//									// Extract all cell references in $ref
//									foreach (Cell::extractAllCellReferencesInRange($ref) as $reference) {
//										$docSheet->getStyle($reference)->setConditionalStyles($conditionalStyles);
//									}
//								}
//							}

//							$aKeys = array("sheet", "objects", "scenarios", "formatCells", "formatColumns", "formatRows", "insertColumns", "insertRows", "insertHyperlinks", "deleteColumns", "deleteRows", "selectLockedCells", "sort", "autoFilter", "pivotTables", "selectUnlockedCells");
//							if (!$this->readDataOnly && $xmlSheet && $xmlSheet->sheetProtection) {
//								foreach ($aKeys as $key) {
//									$method = "set" . ucfirst($key);
//									$docSheet->getProtection()->$method(self::parseBoolean((string) $xmlSheet->sheetProtection[$key]));
//								}
//							}

//							if (!$this->readDataOnly && $xmlSheet && $xmlSheet->sheetProtection) {
//								$docSheet->getProtection()->setPassword((string) $xmlSheet->sheetProtection["password"], true);
//								if ($xmlSheet->protectedRanges->protectedRange) {
//									foreach ($xmlSheet->protectedRanges->protectedRange as $protectedRange) {
//										$docSheet->protectCells((string) $protectedRange["sqref"], (string) $protectedRange["password"], true);
//									}
//								}
//							}

//							if ($xmlSheet && $xmlSheet->autoFilter && !$this->readDataOnly) {
//								$autoFilterRange = (string) $xmlSheet->autoFilter["ref"];
//								if (strpos($autoFilterRange, ':') !== false) {
//									$autoFilter = $docSheet->getAutoFilter();
//									$autoFilter->setRange($autoFilterRange);
//
//									foreach ($xmlSheet->autoFilter->filterColumn as $filterColumn) {
//										$column = $autoFilter->getColumnByOffset((integer) $filterColumn["colId"]);
//										//    Check for standard filters
//										if ($filterColumn->filters) {
//											$column->setFilterType(PHPExcel_Worksheet_AutoFilter_Column::AUTOFILTER_FILTERTYPE_FILTER);
//											$filters = $filterColumn->filters;
//											if ((isset($filters["blank"])) && ($filters["blank"] == 1)) {
//												//    Operator is undefined, but always treated as EQUAL
//												$column->createRule()->setRule(null, '')->setRuleType(PHPExcel_Worksheet_AutoFilter_Column_Rule::AUTOFILTER_RULETYPE_FILTER);
//											}
//											//    Standard filters are always an OR join, so no join rule needs to be set
//											//    Entries can be either filter elements
//											foreach ($filters->filter as $filterRule) {
//												//    Operator is undefined, but always treated as EQUAL
//												$column->createRule()->setRule(null, (string) $filterRule["val"])->setRuleType(PHPExcel_Worksheet_AutoFilter_Column_Rule::AUTOFILTER_RULETYPE_FILTER);
//											}
//											//    Or Date Group elements
//											foreach ($filters->dateGroupItem as $dateGroupItem) {
//												$column->createRule()->setRule(
//																//    Operator is undefined, but always treated as EQUAL
//																null, array(
//															'year' => (string) $dateGroupItem["year"],
//															'month' => (string) $dateGroupItem["month"],
//															'day' => (string) $dateGroupItem["day"],
//															'hour' => (string) $dateGroupItem["hour"],
//															'minute' => (string) $dateGroupItem["minute"],
//															'second' => (string) $dateGroupItem["second"],
//																), (string) $dateGroupItem["dateTimeGrouping"]
//														)
//														->setRuleType(PHPExcel_Worksheet_AutoFilter_Column_Rule::AUTOFILTER_RULETYPE_DATEGROUP);
//											}
//										}
//										//    Check for custom filters
//										if ($filterColumn->customFilters) {
//											$column->setFilterType(PHPExcel_Worksheet_AutoFilter_Column::AUTOFILTER_FILTERTYPE_CUSTOMFILTER);
//											$customFilters = $filterColumn->customFilters;
//											//    Custom filters can an AND or an OR join;
//											//        and there should only ever be one or two entries
//											if ((isset($customFilters["and"])) && ($customFilters["and"] == 1)) {
//												$column->setJoin(PHPExcel_Worksheet_AutoFilter_Column::AUTOFILTER_COLUMN_JOIN_AND);
//											}
//											foreach ($customFilters->customFilter as $filterRule) {
//												$column->createRule()->setRule(
//																(string) $filterRule["operator"], (string) $filterRule["val"]
//														)
//														->setRuleType(PHPExcel_Worksheet_AutoFilter_Column_Rule::AUTOFILTER_RULETYPE_CUSTOMFILTER);
//											}
//										}
//										//    Check for dynamic filters
//										if ($filterColumn->dynamicFilter) {
//											$column->setFilterType(PHPExcel_Worksheet_AutoFilter_Column::AUTOFILTER_FILTERTYPE_DYNAMICFILTER);
//											//    We should only ever have one dynamic filter
//											foreach ($filterColumn->dynamicFilter as $filterRule) {
//												$column->createRule()->setRule(
//																//    Operator is undefined, but always treated as EQUAL
//																null, (string) $filterRule["val"], (string) $filterRule["type"]
//														)
//														->setRuleType(PHPExcel_Worksheet_AutoFilter_Column_Rule::AUTOFILTER_RULETYPE_DYNAMICFILTER);
//												if (isset($filterRule["val"])) {
//													$column->setAttribute('val', (string) $filterRule["val"]);
//												}
//												if (isset($filterRule["maxVal"])) {
//													$column->setAttribute('maxVal', (string) $filterRule["maxVal"]);
//												}
//											}
//										}
//										//    Check for dynamic filters
//										if ($filterColumn->top10) {
//											$column->setFilterType(PHPExcel_Worksheet_AutoFilter_Column::AUTOFILTER_FILTERTYPE_TOPTENFILTER);
//											//    We should only ever have one top10 filter
//											foreach ($filterColumn->top10 as $filterRule) {
//												$column->createRule()->setRule(
//																(((isset($filterRule["percent"])) && ($filterRule["percent"] == 1)) ? PHPExcel_Worksheet_AutoFilter_Column_Rule::AUTOFILTER_COLUMN_RULE_TOPTEN_PERCENT : PHPExcel_Worksheet_AutoFilter_Column_Rule::AUTOFILTER_COLUMN_RULE_TOPTEN_BY_VALUE
//																), (string) $filterRule["val"], (((isset($filterRule["top"])) && ($filterRule["top"] == 1)) ? PHPExcel_Worksheet_AutoFilter_Column_Rule::AUTOFILTER_COLUMN_RULE_TOPTEN_TOP : PHPExcel_Worksheet_AutoFilter_Column_Rule::AUTOFILTER_COLUMN_RULE_TOPTEN_BOTTOM
//																)
//														)
//														->setRuleType(PHPExcel_Worksheet_AutoFilter_Column_Rule::AUTOFILTER_RULETYPE_TOPTENFILTER);
//											}
//										}
//									}
//								}
//							}

//							if ($xmlSheet && $xmlSheet->mergeCells && $xmlSheet->mergeCells->mergeCell && !$this->readDataOnly) {
//								foreach ($xmlSheet->mergeCells->mergeCell as $mergeCell) {
//									$mergeRef = (string) $mergeCell["ref"];
//									if (strpos($mergeRef, ':') !== false) {
//										$docSheet->mergeCells((string) $mergeCell["ref"]);
//									}
//								}
//							}

//							if ($xmlSheet && $xmlSheet->pageMargins && !$this->readDataOnly) {
//								$docPageMargins = $docSheet->getPageMargins();
//								$docPageMargins->setLeft(floatval($xmlSheet->pageMargins["left"]));
//								$docPageMargins->setRight(floatval($xmlSheet->pageMargins["right"]));
//								$docPageMargins->setTop(floatval($xmlSheet->pageMargins["top"]));
//								$docPageMargins->setBottom(floatval($xmlSheet->pageMargins["bottom"]));
//								$docPageMargins->setHeader(floatval($xmlSheet->pageMargins["header"]));
//								$docPageMargins->setFooter(floatval($xmlSheet->pageMargins["footer"]));
//							}

//							if ($xmlSheet && $xmlSheet->pageSetup && !$this->readDataOnly) {
//								$docPageSetup = $docSheet->getPageSetup();
//
//								if (isset($xmlSheet->pageSetup["orientation"])) {
//									$docPageSetup->setOrientation((string) $xmlSheet->pageSetup["orientation"]);
//								}
//								if (isset($xmlSheet->pageSetup["paperSize"])) {
//									$docPageSetup->setPaperSize(intval($xmlSheet->pageSetup["paperSize"]));
//								}
//								if (isset($xmlSheet->pageSetup["scale"])) {
//									$docPageSetup->setScale(intval($xmlSheet->pageSetup["scale"]), false);
//								}
//								if (isset($xmlSheet->pageSetup["fitToHeight"]) && intval($xmlSheet->pageSetup["fitToHeight"]) >= 0) {
//									$docPageSetup->setFitToHeight(intval($xmlSheet->pageSetup["fitToHeight"]), false);
//								}
//								if (isset($xmlSheet->pageSetup["fitToWidth"]) && intval($xmlSheet->pageSetup["fitToWidth"]) >= 0) {
//									$docPageSetup->setFitToWidth(intval($xmlSheet->pageSetup["fitToWidth"]), false);
//								}
//								if (isset($xmlSheet->pageSetup["firstPageNumber"]) && isset($xmlSheet->pageSetup["useFirstPageNumber"]) &&
//										self::parseBoolean((string) $xmlSheet->pageSetup["useFirstPageNumber"])) {
//									$docPageSetup->setFirstPageNumber(intval($xmlSheet->pageSetup["firstPageNumber"]));
//								}
//							}

//							if ($xmlSheet && $xmlSheet->headerFooter && !$this->readDataOnly) {
//								$docHeaderFooter = $docSheet->getHeaderFooter();
//
//								if (isset($xmlSheet->headerFooter["differentOddEven"]) &&
//										self::parseBoolean((string) $xmlSheet->headerFooter["differentOddEven"])) {
//									$docHeaderFooter->setDifferentOddEven(true);
//								} else {
//									$docHeaderFooter->setDifferentOddEven(false);
//								}
//								if (isset($xmlSheet->headerFooter["differentFirst"]) &&
//										self::parseBoolean((string) $xmlSheet->headerFooter["differentFirst"])) {
//									$docHeaderFooter->setDifferentFirst(true);
//								} else {
//									$docHeaderFooter->setDifferentFirst(false);
//								}
//								if (isset($xmlSheet->headerFooter["scaleWithDoc"]) &&
//										!self::parseBoolean((string) $xmlSheet->headerFooter["scaleWithDoc"])) {
//									$docHeaderFooter->setScaleWithDocument(false);
//								} else {
//									$docHeaderFooter->setScaleWithDocument(true);
//								}
//								if (isset($xmlSheet->headerFooter["alignWithMargins"]) &&
//										!self::parseBoolean((string) $xmlSheet->headerFooter["alignWithMargins"])) {
//									$docHeaderFooter->setAlignWithMargins(false);
//								} else {
//									$docHeaderFooter->setAlignWithMargins(true);
//								}
//
//								$docHeaderFooter->setOddHeader((string) $xmlSheet->headerFooter->oddHeader);
//								$docHeaderFooter->setOddFooter((string) $xmlSheet->headerFooter->oddFooter);
//								$docHeaderFooter->setEvenHeader((string) $xmlSheet->headerFooter->evenHeader);
//								$docHeaderFooter->setEvenFooter((string) $xmlSheet->headerFooter->evenFooter);
//								$docHeaderFooter->setFirstHeader((string) $xmlSheet->headerFooter->firstHeader);
//								$docHeaderFooter->setFirstFooter((string) $xmlSheet->headerFooter->firstFooter);
//							}

//							if ($xmlSheet && $xmlSheet->rowBreaks && $xmlSheet->rowBreaks->brk && !$this->readDataOnly) {
//								foreach ($xmlSheet->rowBreaks->brk as $brk) {
//									if ($brk["man"]) {
//										$docSheet->setBreak("A$brk[id]", Worksheet::BREAK_ROW);
//									}
//								}
//							}
//							if ($xmlSheet && $xmlSheet->colBreaks && $xmlSheet->colBreaks->brk && !$this->readDataOnly) {
//								foreach ($xmlSheet->colBreaks->brk as $brk) {
//									if ($brk["man"]) {
//										$docSheet->setBreak(Cell::stringFromColumnIndex((string) $brk["id"]) . "1", Worksheet::BREAK_COLUMN);
//									}
//								}
//							}

//							if ($xmlSheet && $xmlSheet->dataValidations && !$this->readDataOnly) {
//								foreach ($xmlSheet->dataValidations->dataValidation as $dataValidation) {
//									// Uppercase coordinate
//									$range = strtoupper($dataValidation["sqref"]);
//									$rangeSet = explode(' ', $range);
//									foreach ($rangeSet as $range) {
//										$stRange = $docSheet->shrinkRangeToFit($range);
//
//										// Extract all cell references in $range
//										foreach (Cell::extractAllCellReferencesInRange($stRange) as $reference) {
//											// Create validation
//											$docValidation = $docSheet->getCell($reference)->getDataValidation();
//											$docValidation->setType((string) $dataValidation["type"]);
//											$docValidation->setErrorStyle((string) $dataValidation["errorStyle"]);
//											$docValidation->setOperator((string) $dataValidation["operator"]);
//											$docValidation->setAllowBlank($dataValidation["allowBlank"] != 0);
//											$docValidation->setShowDropDown($dataValidation["showDropDown"] == 0);
//											$docValidation->setShowInputMessage($dataValidation["showInputMessage"] != 0);
//											$docValidation->setShowErrorMessage($dataValidation["showErrorMessage"] != 0);
//											$docValidation->setErrorTitle((string) $dataValidation["errorTitle"]);
//											$docValidation->setError((string) $dataValidation["error"]);
//											$docValidation->setPromptTitle((string) $dataValidation["promptTitle"]);
//											$docValidation->setPrompt((string) $dataValidation["prompt"]);
//											$docValidation->setFormula1((string) $dataValidation->formula1);
//											$docValidation->setFormula2((string) $dataValidation->formula2);
//										}
//									}
//								}
//							}

							// Add hyperlinks
							$hyperlinks = /*. (string[string]) .*/ array();
							// Locate hyperlink relations
							if ($zip->locateName(dirname("$dir/$fileWorksheet") . "/_rels/" . basename($fileWorksheet) . ".rels") !== FALSE) {
								$relsWorksheet = XMLElementReader::loadFromString($this->getFromZipArchive($zip, dirname("$dir/$fileWorksheet") . "/_rels/" . basename($fileWorksheet) . ".rels")); //~ http://schemas.openxmlformats.org/package/2006/relationships");
								foreach ($relsWorksheet->getElements("Relationship") as $ele) {
									if ($ele->getAttribute("Type") === "http://schemas.openxmlformats.org/officeDocument/2006/relationships/hyperlink") {
										$hyperlinks[$ele->getAttribute("Id")] = $ele->getAttribute("Target");
									}
								}
							}

//							// Loop through hyperlinks
//							if ($xmlSheet !== NULL && $xmlSheet->getElement("hyperlinks") !== NULL) {
//								foreach ($xmlSheet->getElement("hyperlinks")->getElements("hyperlink") as $hyperlink) {
//									// Link url
//									$linkRel = $hyperlink->attributes('http://schemas.openxmlformats.org/officeDocument/2006/relationships');
//
//									foreach (Cell::extractAllCellReferencesInRange($hyperlink->getAttribute('ref')) as $cellReference) {
//										$cell = $docSheet->getCell($cellReference);
//										if (isset($linkRel['id'])) {
//											$hyperlinkUrl = $hyperlinks[$linkRel['id']];
//											if (($location = $hyperlink->getAttribute('location', NULL)) !== NULL) {
//												$hyperlinkUrl .= "#$location";
//											}
//											$cell->getHyperlink()->setUrl($hyperlinkUrl);
//										} elseif (($location = $hyperlink->getAttribute('location', NULL)) !== NULL) {
//											$cell->getHyperlink()->setUrl("sheet://$location");
//										}
//
//										// Tooltip
//										if (($tooltip = $hyperlink->getAttribute('tooltip')) !== NULL) {
//											$cell->getHyperlink()->setTooltip($tooltip);
//										}
//									}
//								}
//							}

//							// Add comments
//							$comments = array();
//							$vmlComments = array();
//							if (!$this->readDataOnly) {
//								// Locate comment relations
//								if ($zip->locateName(dirname("$dir/$fileWorksheet") . "/_rels/" . basename($fileWorksheet) . ".rels")) {
//									$relsWorksheet = XMLElementReader::loadFromString($this->securityScan($this->getFromZipArchive($zip, dirname("$dir/$fileWorksheet") . "/_rels/" . basename($fileWorksheet) . ".rels"))); //~ http://schemas.openxmlformats.org/package/2006/relationships");
//									foreach ($relsWorksheet->Relationship as $ele) {
//										if ($ele["Type"] == "http://schemas.openxmlformats.org/officeDocument/2006/relationships/comments") {
//											$comments[(string) $ele["Id"]] = (string) $ele["Target"];
//										}
//										if ($ele["Type"] == "http://schemas.openxmlformats.org/officeDocument/2006/relationships/vmlDrawing") {
//											$vmlComments[(string) $ele["Id"]] = (string) $ele["Target"];
//										}
//									}
//								}
//
//								// Loop through comments
//								foreach ($comments as $relName => $relPath) {
//									// Load comments file
//									$relPath = SharedFile::realpath(dirname("$dir/$fileWorksheet") . "/" . $relPath);
//									$commentsFile = XMLElementReader::loadFromString($this->securityScan($this->getFromZipArchive($zip, $relPath)));
//
//									// Utility variables
//									$authors = array();
//
//									// Loop through authors
//									foreach ($commentsFile->authors->author as $author) {
//										$authors[] = (string) $author;
//									}
//
//									// Loop through contents
//									foreach ($commentsFile->commentList->comment as $comment) {
//										if (!empty($comment['authorId'])) {
//											$docSheet->getComment((string) $comment['ref'])->setAuthor($authors[(string) $comment['authorId']]);
//										}
//										$docSheet->getComment((string) $comment['ref'])->setText($this->parseRichText($comment->text));
//									}
//								}
//
//								// Loop through VML comments
//								foreach ($vmlComments as $relName => $relPath) {
//									// Load VML comments file
//									$relPath = SharedFile::realpath(dirname("$dir/$fileWorksheet") . "/" . $relPath);
//									$vmlCommentsFile = XMLElementReader::loadFromString($this->securityScan($this->getFromZipArchive($zip, $relPath)));
//									$vmlCommentsFile->registerXPathNamespace('v', 'urn:schemas-microsoft-com:vml');
//
//									$shapes = $vmlCommentsFile->xpath('//v:shape');
//									foreach ($shapes as $shape) {
//										$shape->registerXPathNamespace('v', 'urn:schemas-microsoft-com:vml');
//
//										if (isset($shape['style'])) {
//											$style = (string) $shape['style'];
//											$fillColor = strtoupper(substr((string) $shape['fillcolor'], 1));
//											$column = null;
//											$row = null;
//
//											$clientData = $shape->xpath('.//x:ClientData');
//											if (is_array($clientData) && !empty($clientData)) {
//												$clientData = $clientData[0];
//
//												if (isset($clientData['ObjectType']) && (string) $clientData['ObjectType'] == 'Note') {
//													$temp = $clientData->xpath('.//x:Row');
//													if (is_array($temp)) {
//														$row = $temp[0];
//													}
//
//													$temp = $clientData->xpath('.//x:Column');
//													if (is_array($temp)) {
//														$column = $temp[0];
//													}
//												}
//											}
//
//											if (($column !== null) && ($row !== null)) {
//												// Set comment properties
//												$comment = $docSheet->getCommentByColumnAndRow((string) $column, $row + 1);
//												$comment->getFillColor()->setRGB($fillColor);
//
//												// Parse style
//												$styleArray = explode(';', (string) str_replace(' ', '', $style));
//												foreach ($styleArray as $stylePair) {
//													$stylePair = explode(':', $stylePair);
//
//													if ($stylePair[0] == 'margin-left') {
//														$comment->setMarginLeft($stylePair[1]);
//													}
//													if ($stylePair[0] == 'margin-top') {
//														$comment->setMarginTop($stylePair[1]);
//													}
//													if ($stylePair[0] == 'width') {
//														$comment->setWidth($stylePair[1]);
//													}
//													if ($stylePair[0] == 'height') {
//														$comment->setHeight($stylePair[1]);
//													}
//													if ($stylePair[0] == 'visibility') {
//														$comment->setVisible($stylePair[1] == 'visible');
//													}
//												}
//											}
//										}
//									}
//								}
//
//								// Header/footer images
//								if ($xmlSheet && $xmlSheet->legacyDrawingHF && !$this->readDataOnly) {
//									if ($zip->locateName(dirname("$dir/$fileWorksheet") . "/_rels/" . basename($fileWorksheet) . ".rels")) {
//										$relsWorksheet = XMLElementReader::loadFromString($this->securityScan($this->getFromZipArchive($zip, dirname("$dir/$fileWorksheet") . "/_rels/" . basename($fileWorksheet) . ".rels"))); //~ http://schemas.openxmlformats.org/package/2006/relationships");
//										$vmlRelationship = '';
//
//										foreach ($relsWorksheet->Relationship as $ele) {
//											if ($ele["Type"] == "http://schemas.openxmlformats.org/officeDocument/2006/relationships/vmlDrawing") {
//												$vmlRelationship = self::dirAdd("$dir/$fileWorksheet", $ele["Target"]);
//											}
//										}
//
//										if ($vmlRelationship != '') {
//											// Fetch linked images
//											$relsVML = XMLElementReader::loadFromString($this->securityScan($this->getFromZipArchive($zip, dirname($vmlRelationship) . '/_rels/' . basename($vmlRelationship) . '.rels'))); //~ http://schemas.openxmlformats.org/package/2006/relationships");
//											$drawings = array();
//											foreach ($relsVML->Relationship as $ele) {
//												if ($ele["Type"] == "http://schemas.openxmlformats.org/officeDocument/2006/relationships/image") {
//													$drawings[(string) $ele["Id"]] = self::dirAdd($vmlRelationship, $ele["Target"]);
//												}
//											}
//
//											// Fetch VML document
//											$vmlDrawing = XMLElementReader::loadFromString($this->securityScan($this->getFromZipArchive($zip, $vmlRelationship)));
//											$vmlDrawing->registerXPathNamespace('v', 'urn:schemas-microsoft-com:vml');
//
//											$hfImages = array();
//
//											$shapes = $vmlDrawing->xpath('//v:shape');
//											foreach ($shapes as $idx => $shape) {
//												$shape->registerXPathNamespace('v', 'urn:schemas-microsoft-com:vml');
//												$imageData = $shape->xpath('//v:imagedata');
//												$imageData = $imageData[$idx];
//
//												$imageData = $imageData->attributes('urn:schemas-microsoft-com:office:office');
//												$style = self::toCSSArray((string) $shape['style']);
//
//												$hfImages[(string) $shape['id']] = new PHPExcel_Worksheet_HeaderFooterDrawing();
//												if (isset($imageData['title'])) {
//													$hfImages[(string) $shape['id']]->setName((string) $imageData['title']);
//												}
//
//												$hfImages[(string) $shape['id']]->setPath("zip://" . SharedFile::realpath($pFilename) . "#" . $drawings[(string) $imageData['relid']], false);
//												$hfImages[(string) $shape['id']]->setResizeProportional(false);
//												$hfImages[(string) $shape['id']]->setWidth($style['width']);
//												$hfImages[(string) $shape['id']]->setHeight($style['height']);
//												if (isset($style['margin-left'])) {
//													$hfImages[(string) $shape['id']]->setOffsetX($style['margin-left']);
//												}
//												$hfImages[(string) $shape['id']]->setOffsetY($style['margin-top']);
//												$hfImages[(string) $shape['id']]->setResizeProportional(true);
//											}
//
//											$docSheet->getHeaderFooter()->setImages($hfImages);
//										}
//									}
//								}
//							}
//
//							// TODO: Autoshapes from twoCellAnchors!
//							if ($zip->locateName(dirname("$dir/$fileWorksheet") . "/_rels/" . basename($fileWorksheet) . ".rels")) {
//								$relsWorksheet = XMLElementReader::loadFromString($this->securityScan($this->getFromZipArchive($zip, dirname("$dir/$fileWorksheet") . "/_rels/" . basename($fileWorksheet) . ".rels"))); //~ http://schemas.openxmlformats.org/package/2006/relationships");
//								$drawings = array();
//								foreach ($relsWorksheet->Relationship as $ele) {
//									if ($ele["Type"] == "http://schemas.openxmlformats.org/officeDocument/2006/relationships/drawing") {
//										$drawings[(string) $ele["Id"]] = self::dirAdd("$dir/$fileWorksheet", $ele["Target"]);
//									}
//								}
//								if ($xmlSheet->drawing && !$this->readDataOnly) {
//									foreach ($xmlSheet->drawing as $drawing) {
//										$fileDrawing = $drawings[(string) self::getArrayItem($drawing->attributes("http://schemas.openxmlformats.org/officeDocument/2006/relationships"), "id")];
//										$relsDrawing = XMLElementReader::loadFromString($this->securityScan($this->getFromZipArchive($zip, dirname($fileDrawing) . "/_rels/" . basename($fileDrawing) . ".rels"))); //~ http://schemas.openxmlformats.org/package/2006/relationships");
//										$images = array();
//
//										if ($relsDrawing && $relsDrawing->Relationship) {
//											foreach ($relsDrawing->Relationship as $ele) {
//												if ($ele["Type"] == "http://schemas.openxmlformats.org/officeDocument/2006/relationships/image") {
//													$images[(string) $ele["Id"]] = self::dirAdd($fileDrawing, $ele["Target"]);
//												} elseif ($ele["Type"] == "http://schemas.openxmlformats.org/officeDocument/2006/relationships/chart") {
//													if ($this->includeCharts) {
//														$charts[self::dirAdd($fileDrawing, $ele["Target"])] = array(
//															'id' => (string) $ele["Id"],
//															'sheet' => $docSheet->getTitle()
//														);
//													}
//												}
//											}
//										}
//										$xmlDrawing = XMLElementReader::loadFromString($this->securityScan($this->getFromZipArchive($zip, $fileDrawing)))->children("http://schemas.openxmlformats.org/drawingml/2006/spreadsheetDrawing");
//
//										if ($xmlDrawing->oneCellAnchor) {
//											foreach ($xmlDrawing->oneCellAnchor as $oneCellAnchor) {
//												if ($oneCellAnchor->pic->blipFill) {
//													$blip = $oneCellAnchor->pic->blipFill->children("http://schemas.openxmlformats.org/drawingml/2006/main")->blip;
//													$xfrm = $oneCellAnchor->pic->spPr->children("http://schemas.openxmlformats.org/drawingml/2006/main")->xfrm;
//													$outerShdw = $oneCellAnchor->pic->spPr->children("http://schemas.openxmlformats.org/drawingml/2006/main")->effectLst->outerShdw;
//													$objDrawing = new PHPExcel_Worksheet_Drawing;
//													$objDrawing->setName((string) self::getArrayItem($oneCellAnchor->pic->nvPicPr->cNvPr->attributes(), "name"));
//													$objDrawing->setDescription((string) self::getArrayItem($oneCellAnchor->pic->nvPicPr->cNvPr->attributes(), "descr"));
//													$objDrawing->setPath("zip://" . SharedFile::realpath($pFilename) . "#" . $images[(string) self::getArrayItem($blip->attributes("http://schemas.openxmlformats.org/officeDocument/2006/relationships"), "embed")], false);
//													$objDrawing->setCoordinates(Cell::stringFromColumnIndex((string) $oneCellAnchor->from->col) . ($oneCellAnchor->from->row + 1));
//													$objDrawing->setOffsetX(PHPExcel_Shared_Drawing::EMUToPixels($oneCellAnchor->from->colOff));
//													$objDrawing->setOffsetY(PHPExcel_Shared_Drawing::EMUToPixels($oneCellAnchor->from->rowOff));
//													$objDrawing->setResizeProportional(false);
//													$objDrawing->setWidth(PHPExcel_Shared_Drawing::EMUToPixels(self::getArrayItem($oneCellAnchor->ext->attributes(), "cx")));
//													$objDrawing->setHeight(PHPExcel_Shared_Drawing::EMUToPixels(self::getArrayItem($oneCellAnchor->ext->attributes(), "cy")));
//													if ($xfrm) {
//														$objDrawing->setRotation(PHPExcel_Shared_Drawing::angleToDegrees(self::getArrayItem($xfrm->attributes(), "rot")));
//													}
//													if ($outerShdw) {
//														$shadow = $objDrawing->getShadow();
//														$shadow->setVisible(true);
//														$shadow->setBlurRadius(PHPExcel_Shared_Drawing::EMUTopixels(self::getArrayItem($outerShdw->attributes(), "blurRad")));
//														$shadow->setDistance(PHPExcel_Shared_Drawing::EMUTopixels(self::getArrayItem($outerShdw->attributes(), "dist")));
//														$shadow->setDirection(PHPExcel_Shared_Drawing::angleToDegrees(self::getArrayItem($outerShdw->attributes(), "dir")));
//														$shadow->setAlignment((string) self::getArrayItem($outerShdw->attributes(), "algn"));
//														$shadow->getColor()->setRGB(self::getArrayItem($outerShdw->srgbClr->attributes(), "val"));
//														$shadow->setAlpha(self::getArrayItem($outerShdw->srgbClr->alpha->attributes(), "val") / 1000);
//													}
//													$objDrawing->setWorksheet($docSheet);
//												} else {
//													//    ? Can charts be positioned with a oneCellAnchor ?
//													$coordinates = Cell::stringFromColumnIndex((string) $oneCellAnchor->from->col) . ($oneCellAnchor->from->row + 1);
//													$offsetX = PHPExcel_Shared_Drawing::EMUToPixels($oneCellAnchor->from->colOff);
//													$offsetY = PHPExcel_Shared_Drawing::EMUToPixels($oneCellAnchor->from->rowOff);
//													$width = PHPExcel_Shared_Drawing::EMUToPixels(self::getArrayItem($oneCellAnchor->ext->attributes(), "cx"));
//													$height = PHPExcel_Shared_Drawing::EMUToPixels(self::getArrayItem($oneCellAnchor->ext->attributes(), "cy"));
//												}
//											}
//										}
//										if ($xmlDrawing->twoCellAnchor) {
//											foreach ($xmlDrawing->twoCellAnchor as $twoCellAnchor) {
//												if ($twoCellAnchor->pic->blipFill) {
//													$blip = $twoCellAnchor->pic->blipFill->children("http://schemas.openxmlformats.org/drawingml/2006/main")->blip;
//													$xfrm = $twoCellAnchor->pic->spPr->children("http://schemas.openxmlformats.org/drawingml/2006/main")->xfrm;
//													$outerShdw = $twoCellAnchor->pic->spPr->children("http://schemas.openxmlformats.org/drawingml/2006/main")->effectLst->outerShdw;
//													$objDrawing = new PHPExcel_Worksheet_Drawing;
//													$objDrawing->setName((string) self::getArrayItem($twoCellAnchor->pic->nvPicPr->cNvPr->attributes(), "name"));
//													$objDrawing->setDescription((string) self::getArrayItem($twoCellAnchor->pic->nvPicPr->cNvPr->attributes(), "descr"));
//													$objDrawing->setPath("zip://" . SharedFile::realpath($pFilename) . "#" . $images[(string) self::getArrayItem($blip->attributes("http://schemas.openxmlformats.org/officeDocument/2006/relationships"), "embed")], false);
//													$objDrawing->setCoordinates(Cell::stringFromColumnIndex((string) $twoCellAnchor->from->col) . ($twoCellAnchor->from->row + 1));
//													$objDrawing->setOffsetX(PHPExcel_Shared_Drawing::EMUToPixels($twoCellAnchor->from->colOff));
//													$objDrawing->setOffsetY(PHPExcel_Shared_Drawing::EMUToPixels($twoCellAnchor->from->rowOff));
//													$objDrawing->setResizeProportional(false);
//
//													if ($xfrm) {
//														$objDrawing->setWidth(PHPExcel_Shared_Drawing::EMUToPixels(self::getArrayItem($xfrm->ext->attributes(), "cx")));
//														$objDrawing->setHeight(PHPExcel_Shared_Drawing::EMUToPixels(self::getArrayItem($xfrm->ext->attributes(), "cy")));
//														$objDrawing->setRotation(PHPExcel_Shared_Drawing::angleToDegrees(self::getArrayItem($xfrm->attributes(), "rot")));
//													}
//													if ($outerShdw) {
//														$shadow = $objDrawing->getShadow();
//														$shadow->setVisible(true);
//														$shadow->setBlurRadius(PHPExcel_Shared_Drawing::EMUTopixels(self::getArrayItem($outerShdw->attributes(), "blurRad")));
//														$shadow->setDistance(PHPExcel_Shared_Drawing::EMUTopixels(self::getArrayItem($outerShdw->attributes(), "dist")));
//														$shadow->setDirection(PHPExcel_Shared_Drawing::angleToDegrees(self::getArrayItem($outerShdw->attributes(), "dir")));
//														$shadow->setAlignment((string) self::getArrayItem($outerShdw->attributes(), "algn"));
//														$shadow->getColor()->setRGB(self::getArrayItem($outerShdw->srgbClr->attributes(), "val"));
//														$shadow->setAlpha(self::getArrayItem($outerShdw->srgbClr->alpha->attributes(), "val") / 1000);
//													}
//													$objDrawing->setWorksheet($docSheet);
//												} elseif (($this->includeCharts) && ($twoCellAnchor->graphicFrame)) {
//													$fromCoordinate = Cell::stringFromColumnIndex((string) $twoCellAnchor->from->col) . ($twoCellAnchor->from->row + 1);
//													$fromOffsetX = PHPExcel_Shared_Drawing::EMUToPixels($twoCellAnchor->from->colOff);
//													$fromOffsetY = PHPExcel_Shared_Drawing::EMUToPixels($twoCellAnchor->from->rowOff);
//													$toCoordinate = Cell::stringFromColumnIndex((string) $twoCellAnchor->to->col) . ($twoCellAnchor->to->row + 1);
//													$toOffsetX = PHPExcel_Shared_Drawing::EMUToPixels($twoCellAnchor->to->colOff);
//													$toOffsetY = PHPExcel_Shared_Drawing::EMUToPixels($twoCellAnchor->to->rowOff);
//													$graphic = $twoCellAnchor->graphicFrame->children("http://schemas.openxmlformats.org/drawingml/2006/main")->graphic;
//													$chartRef = $graphic->graphicData->children("http://schemas.openxmlformats.org/drawingml/2006/chart")->chart;
//													$thisChart = (string) $chartRef->attributes("http://schemas.openxmlformats.org/officeDocument/2006/relationships");
//
//													$chartDetails[$docSheet->getTitle() . '!' . $thisChart] = array(
//														'fromCoordinate' => $fromCoordinate,
//														'fromOffsetX' => $fromOffsetX,
//														'fromOffsetY' => $fromOffsetY,
//														'toCoordinate' => $toCoordinate,
//														'toOffsetX' => $toOffsetX,
//														'toOffsetY' => $toOffsetY,
//														'worksheetTitle' => $docSheet->getTitle()
//													);
//												}
//											}
//										}
//									}
//								}
//							}
//
//							// Loop through definedNames
//							if ($xmlWorkbook->definedNames) {
//								foreach ($xmlWorkbook->definedNames->definedName as $definedName) {
//									// Extract range
//									$extractedRange = (string) $definedName;
//									$extractedRange = preg_replace('/\'(\w+)\'\!/', '', $extractedRange);
//									if (($spos = strpos($extractedRange, '!')) !== false) {
//										$extractedRange = substr($extractedRange, 0, $spos) . (string) str_replace('$', '', substr($extractedRange, $spos));
//									} else {
//										$extractedRange = (string) str_replace('$', '', $extractedRange);
//									}
//
//									// Valid range?
//									if (stripos((string) $definedName, '#REF!') !== false || $extractedRange == '') {
//										continue;
//									}
//
//									// Some definedNames are only applicable if we are on the same sheet...
//									if ((string) $definedName['localSheetId'] != '' && (string) $definedName['localSheetId'] == $sheetId) {
//										// Switch on type
//										switch ((string) $definedName['name']) {
//											case '_xlnm._FilterDatabase':
//												if ((string) $definedName['hidden'] !== '1') {
//													$extractedRange = explode(',', $extractedRange);
//													foreach ($extractedRange as $range) {
//														$autoFilterRange = $range;
//														if (strpos($autoFilterRange, ':') !== false) {
//															$docSheet->getAutoFilter()->setRange($autoFilterRange);
//														}
//													}
//												}
//												break;
//											case '_xlnm.Print_Titles':
//												// Split $extractedRange
//												$extractedRange = explode(',', $extractedRange);
//
//												// Set print titles
//												foreach ($extractedRange as $range) {
//													$matches = array();
//													$range = (string) str_replace('$', '', $range);
//
//													// check for repeating columns, e g. 'A:A' or 'A:D'
//													if (preg_match('/!?([A-Z]+)\:([A-Z]+)$/', $range, $matches)) {
//														$docSheet->getPageSetup()->setColumnsToRepeatAtLeft(array($matches[1], $matches[2]));
//													} elseif (preg_match('/!?(\d+)\:(\d+)$/', $range, $matches)) {
//														// check for repeating rows, e.g. '1:1' or '1:5'
//														$docSheet->getPageSetup()->setRowsToRepeatAtTop(array($matches[1], $matches[2]));
//													}
//												}
//												break;
//											case '_xlnm.Print_Area':
//												$rangeSets = explode(',', $extractedRange);  // FIXME: what if sheetname contains comma?
//												$newRangeSets = array();
//												foreach ($rangeSets as $rangeSet) {
//													$range = explode('!', $rangeSet); // FIXME: what if sheetname contains exclamation mark?
//													$rangeSet = isset($range[1]) ? $range[1] : $range[0];
//													if (strpos($rangeSet, ':') === false) {
//														$rangeSet = $rangeSet . ':' . $rangeSet;
//													}
//													$newRangeSets[] = (string) str_replace('$', '', $rangeSet);
//												}
//												$docSheet->getPageSetup()->setPrintArea(implode(',', $newRangeSets));
//												break;
//
//											default:
//												break;
//										}
//									}
//								}
//							}
//
//							// Next sheet id
//							++$sheetId;
						}

//						// Loop through definedNames
//						if ($xmlWorkbook->definedNames) {
//							foreach ($xmlWorkbook->definedNames->definedName as $definedName) {
//								// Extract range
//								$extractedRange = (string) $definedName;
//								$extractedRange = preg_replace('/\'(\w+)\'\!/', '', $extractedRange);
//								if (($spos = strpos($extractedRange, '!')) !== false) {
//									$extractedRange = substr($extractedRange, 0, $spos) . (string) str_replace('$', '', substr($extractedRange, $spos));
//								} else {
//									$extractedRange = (string) str_replace('$', '', $extractedRange);
//								}
//
//								// Valid range?
//								if (stripos((string) $definedName, '#REF!') !== false || $extractedRange == '') {
//									continue;
//								}
//
//								// Some definedNames are only applicable if we are on the same sheet...
//								if ((string) $definedName['localSheetId'] != '') {
//									// Local defined name
//									// Switch on type
//									switch ((string) $definedName['name']) {
//										case '_xlnm._FilterDatabase':
//										case '_xlnm.Print_Titles':
//										case '_xlnm.Print_Area':
//											break;
//										default:
//											if ($mapSheetId[(integer) $definedName['localSheetId']] !== null) {
//												$range = explode('!', (string) $definedName);
//												if (count($range) == 2) {
//													$range[0] = (string) str_replace("''", "'", $range[0]);
//													$range[0] = (string) str_replace("'", "", $range[0]);
//													if ($worksheet = $docSheet->getParent()->getSheetByName($range[0])) {
//														$extractedRange = (string) str_replace('$', '', $range[1]);
//														$scope = $docSheet->getParent()->getSheet($mapSheetId[(integer) $definedName['localSheetId']]);
//														$excel->addNamedRange(new PHPExcel_NamedRange((string) $definedName['name'], $worksheet, $extractedRange, true, $scope));
//													}
//												}
//											}
//											break;
//									}
//								} elseif (!isset($definedName['localSheetId'])) {
//									// "Global" definedNames
//									$locatedSheet = null;
//									$extractedSheetName = '';
//									if (strpos((string) $definedName, '!') !== false) {
//										// Extract sheet name
//										$extractedSheetName = Worksheet::extractSheetTitle((string) $definedName, true);
//										$extractedSheetName = $extractedSheetName[0];
//
//										// Locate sheet
//										$locatedSheet = $excel->getSheetByName($extractedSheetName);
//
//										// Modify range
//										$range = explode('!', $extractedRange);
//										$extractedRange = isset($range[1]) ? $range[1] : $range[0];
//									}
//
//									if ($locatedSheet !== null) {
//										$excel->addNamedRange(new PHPExcel_NamedRange((string) $definedName['name'], $locatedSheet, $extractedRange, false));
//									}
//								}
//							}
//						}
					}

					// active sheet index
					$activeTab = intval($xmlWorkbook->getElement("bookViews")->getElement("workbookView")->getAttribute("activeTab", NULL)); // refers to old sheet index
					// keep active sheet index if sheet is still loaded, else first sheet is set as the active
					if($activeTab !== NULL && isset($mapSheetId[$activeTab]) && $mapSheetId[$activeTab] >= 0) {
						$excel->setActiveSheetIndex($mapSheetId[$activeTab]);
					} else {
						if ($excel->getSheetCount() == 0) {
							$excel->createSheet();
						}
						$excel->setActiveSheetIndex(0);
					}
					break;
					
				default:
			}
		}

//		if (!$this->readDataOnly) {
//			$contentTypes = XMLElementReader::loadFromString($this->securityScan($this->getFromZipArchive($zip, "[Content_Types].xml")));
//			foreach ($contentTypes->Override as $contentType) {
//				switch ($contentType["ContentType"]) {
//					case "application/vnd.openxmlformats-officedocument.drawingml.chart+xml":
//						if ($this->includeCharts) {
//							$chartEntryRef = ltrim($contentType['PartName'], '/');
//							$chartElements = XMLElementReader::loadFromString($this->securityScan($this->getFromZipArchive($zip, $chartEntryRef)));
//							$objChart = PHPExcel_Reader_Excel2007Reader_Chart::readChart($chartElements, basename($chartEntryRef, '.xml'));
//
////                            echo 'Chart ', $chartEntryRef, '<br />';
////                            var_dump($charts[$chartEntryRef]);
////
//							if (isset($charts[$chartEntryRef])) {
//								$chartPositionRef = $charts[$chartEntryRef]['sheet'] . '!' . $charts[$chartEntryRef]['id'];
////                                echo 'Position Ref ', $chartPositionRef, '<br />';
//								if (isset($chartDetails[$chartPositionRef])) {
////                                    var_dump($chartDetails[$chartPositionRef]);
//
//									$excel->getSheetByName($charts[$chartEntryRef]['sheet'])->addChart($objChart);
//									$objChart->setWorksheet($excel->getSheetByName($charts[$chartEntryRef]['sheet']));
//									$objChart->setTopLeftPosition($chartDetails[$chartPositionRef]['fromCoordinate'], $chartDetails[$chartPositionRef]['fromOffsetX'], $chartDetails[$chartPositionRef]['fromOffsetY']);
//									$objChart->setBottomRightPosition($chartDetails[$chartPositionRef]['toCoordinate'], $chartDetails[$chartPositionRef]['toOffsetX'], $chartDetails[$chartPositionRef]['toOffsetY']);
//								}
//							}
//						}
//				}
//			}
//		}

		$zip->close();

		return $excel;
	}

}
