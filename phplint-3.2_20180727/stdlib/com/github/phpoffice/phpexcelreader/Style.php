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
 * @version    $Date: 2018/07/03 06:22:31 $
 */

namespace com\github\phpoffice\phpexcelreader;

require_once __DIR__ . "/../../../../all.php";

/*. require_module 'pcre';  require_module 'mbstring'; require_module 'math'; .*/

/**
 * An object of this class stores the format string applied to a single cell or
 * group of cells. Also provides some low-level functions to detect the type of
 * data and to format the value of the cell accordingly; application programs
 * should use the functions provided by the Cell class instead.
 */
class Style {
	/* Pre-defined formats */

	const FORMAT_GENERAL = 'General';
	const FORMAT_TEXT = '@';
	const FORMAT_NUMBER = '0';
	const FORMAT_NUMBER_00 = '0.00';
	const FORMAT_NUMBER_COMMA_SEPARATED1 = '#,##0.00';
	const FORMAT_NUMBER_COMMA_SEPARATED2 = '#,##0.00_-';
	const FORMAT_PERCENTAGE = '0%';
	const FORMAT_PERCENTAGE_00 = '0.00%';
	
	const FORMAT_CURRENCY_USD_SIMPLE = '"$"#,##0.00_-';
	const FORMAT_CURRENCY_USD = '$#,##0_-';
	const FORMAT_CURRENCY_EUR_SIMPLE = '[$EUR ]#,##0.00_-';

	/**
	 * Excel built-in formats.
	 *  [MS-OI29500: Microsoft Office Implementation Information for ISO/IEC-29500 Standard Compliance]
	 *  18.8.30. numFmt (Number Format)
	 *
	 *  The ECMA standard defines built-in format IDs
	 *      14: "mm-dd-yy"
	 *      22: "m/d/yy h:mm"
	 *      37: "#,##0 ;(#,##0)"
	 *      38: "#,##0 ;[Red](#,##0)"
	 *      39: "#,##0.00;(#,##0.00)"
	 *      40: "#,##0.00;[Red](#,##0.00)"
	 *      47: "mmss.0"
	 *      KOR fmt 55: "yyyy-mm-dd"
	 *  Excel defines built-in format IDs
	 *      14: "m/d/yyyy"
	 *      22: "m/d/yyyy h:mm"
	 *      37: "#,##0_);(#,##0)"
	 *      38: "#,##0_);[Red](#,##0)"
	 *      39: "#,##0.00_);(#,##0.00)"
	 *      40: "#,##0.00_);[Red](#,##0.00)"
	 *      47: "mm:ss.0"
	 *      KOR fmt 55: "yyyy/mm/dd"
	 *
	 * @var string[int]
	 */
	private static $BUILTIN_FORMATS = array(
		// General
		0 => self::FORMAT_GENERAL,
		1 => '0',
		2 => '0.00',
		3 => '#,##0',
		4 => '#,##0.00',

		9 => '0%',
		10 => '0.00%',
		11 => '0.00E+00',
		12 => '# ?/?',
		13 => '# ??/??',
		14 => 'm/d/yyyy',   // Despite ECMA 'mm-dd-yy',
		15 => 'd-mmm-yy',
		16 => 'd-mmm',
		17 => 'mmm-yy',
		18 => 'h:mm AM/PM',
		19 => 'h:mm:ss AM/PM',
		20 => 'h:mm',
		21 => 'h:mm:ss',
		22 => 'm/d/yyyy h:mm', // Despite ECMA 'm/d/yy h:mm',

		37 => '#,##0_),(#,##0)',  //  Despite ECMA '#,##0 ,(#,##0)',
		38 => '#,##0_),[Red](#,##0)',   //  Despite ECMA '#,##0 ,[Red](#,##0)',
		39 => '#,##0.00_),(#,##0.00)',  //  Despite ECMA '#,##0.00,(#,##0.00)',
		40 => '#,##0.00_),[Red](#,##0.00)',   //  Despite ECMA '#,##0.00,[Red](#,##0.00)',

		44 => '_("$"* #,##0.00_),_("$"* \\(#,##0.00\\),_("$"* "-"??_),_(@_)',
		45 => 'mm:ss',
		46 => '[h]:mm:ss',
		47 => 'mm:ss.0', //  Despite ECMA 'mmss.0',
		48 => '##0.0E+0',
		49 => '@',

		// CHT
		27 => '[$-404]e/m/d',
		30 => 'm/d/yy',
		36 => '[$-404]e/m/d',
		50 => '[$-404]e/m/d',
		57 => '[$-404]e/m/d',

		// THA
		59 => 't0',
		60 => 't0.00',
		61 => 't#,##0',
		62 => 't#,##0.00',
		67 => 't0%',
		68 => 't0.00%',
		69 => 't# ?/?',
		70 => 't# ??/??'
	);

	/**
	 * Supervisor?
	 *
	 * @var boolean
	 */
	private $isSupervisor = FALSE;

	/**
	 * Parent. Only used for supervisor
	 *
	 * @var Workbook
	 */
	private $workbook;

	/**
	 * Index of style in collection. Only used for real style.
	 *
	 * @var int
	 */
	protected $index = 0;

	/**
	 * Format Code
	 *
	 * @var string
	 */
	private $formatCode = self::FORMAT_GENERAL;
	

	/**
	 * Get built-in format code.
	 *
	 * @param int $pIndex
	 * @return string Possibly NULL if not defined.
	 */
	public static function builtInFormatCode($pIndex) {
		if (isset(self::$BUILTIN_FORMATS[$pIndex])) {
			return self::$BUILTIN_FORMATS[$pIndex];
		}

		return NULL;
	}
	
	
	/**
	 * Detects the currency code from the formatting string.
	 * @param string $format
	 * @return string Currency name, for example '$' or 'EUR' etc. If the
	 * formatting string is not related to a currency value, the empty string is
	 * returned. If the currency is not explicitly indicated, the remote client
	 * default currency should be assumed instend, but since that value is unknown,
	 * the question mark '?' is returned instead.
	 */
	public static function identifyCurrencyName($format) {
		// For 'General' format code, we just pass the value although this is not entirely the way Excel does it,
		// it seems to round numbers to a total of 10 digits.
		if (($format === self::FORMAT_GENERAL) || ($format === self::FORMAT_TEXT)) {
			return '';
		}

		if ($format === self::FORMAT_CURRENCY_EUR_SIMPLE) {
			return "EUR"; // FIXME: use the Unicode EUR symbol
		}

		// Convert any other escaped characters to quoted strings, e.g. (\T to "T")
		$format = preg_replace('/(\\\\(.))(?=(?:[^"]|"[^"]*")*$)/u', '"${2}"', $format);

		// Strip color information
//		$color_regex = '/^\\[[a-zA-Z]+\\]/';
//		$format = preg_replace($color_regex, '', $format);

		// Some non-number strings are quoted, so we'll get rid of the quotes, likewise any positional * symbols
		$format = (string) str_replace(array('"', '*'), '', $format);

		if (1 == preg_match('/\\[\\$([^\\]]*)\\]/u', $format, $matches)) {
			$currency = explode('-', (string)$matches[1])[0];
			if ($currency === '') {
				return '?';
			} else {
				return $currency;
			}
		} else {
			return '';
		}
	}

	/**
	 *
	 * @param boolean $isSupervisor Flag indicating if this is a supervisor or not.
	 * Leave this value at default unless you understand exactly what
	 * its ramifications are.
	 */
	public function __construct($isSupervisor = false) {
		$this->isSupervisor = $isSupervisor;
	}

	/**
	 * Get own index in style collection
	 *
	 * @return int
	 */
	public function getIndex() {
		return $this->index;
	}

	/**
	 * Set own index in style collection
	 *
	 * @param int $pValue
	 */
	public function setIndex($pValue) {
		$this->index = $pValue;
	}
	
	/*.
	forward public self       function setFormatCode(string $pValue) throws PHPExcelException;
	forward public static string function toFormattedString(mixed $value_, string $format) throws PHPExcelException;
	forward public string     function getSelectedCells();
	forward public self       function bindParent(Workbook $parent_);
	forward public string     function getFormatCode();
	forward public self       function applyFormat(string $format) throws PHPExcelException;
	
	pragma 'suspend';
	.*/

	/**
	 * Get the currently active sheet. Only used for supervisor.
	 *
	 * @return Worksheet
	 */
	private function getActiveSheet() {
		return $this->workbook->getActiveSheet();
	}

	/**
	 * Get the currently active cell coordinate in currently active sheet.
	 * Only used for supervisor
	 *
	 * @return string E.g. 'A1'
	 */
	public function getSelectedCells() {
		return $this->getActiveSheet()->getSelectedCells();
	}

	/**
	 * Bind parent. Only used for supervisor
	 *
	 * @param Workbook $parent_
	 * @return self
	 */
	public function bindParent($parent_) {
		$this->workbook = $parent_;
		return $this;
	}

	/**
	 * Get the shared style component for the currently active cell in currently
	 * active sheet. Only used for style supervisor
	 *
	 * @return self
	 */
	private function getSharedComponent() {
		$activeSheet = $this->getActiveSheet();
		$selectedCell = $activeSheet->getActiveCell();

		if ($activeSheet->cellExists($selectedCell)) {
			$xfIndex = $activeSheet->getCell($selectedCell)->getXfIndex();
		} else {
			$xfIndex = 0;
		}

		return $this->workbook->getCellXfByIndex($xfIndex);
	}

	/**
	 * Get Format Code
	 *
	 * @return string
	 */
	public function getFormatCode() {
		if ($this->isSupervisor)
			return $this->getSharedComponent()->getFormatCode();
		else
			return $this->formatCode;
	}

//	/**
//	 * Get Built-In Format Code index
//	 *
//	 * @return int Built-in format code index, or -1 if custom format code.
//	 */
//	public function getBuiltInFormatCode() {
//		if ($this->isSupervisor)
//			return $this->getSharedComponent()->getBuiltInFormatCode();
//		else
//			return self::builtInFormatCodeIndex($this->formatCode);
//	}

	/**
	 * Set format code for the selected cells.
	 *
	 * @param string $pValue Format code.
	 * @return self
	 * @throws PHPExcelException
	 */
	public function setFormatCode($pValue) {
		if ($this->isSupervisor) {
			$this->getActiveSheet()->getStyleOfRange($this->getSelectedCells())->applyFormat($pValue);
		} else {
			$this->formatCode = $pValue;
		}
		return $this;
	}
	
	
	/**
	 * Used only by applyFormat().
	 * @param self $other
	 */
	private function applyFormat_equals($other) {
		return $this->isSupervisor === $other->isSupervisor
			&& $this->formatCode === $other->formatCode;
	}
	

	/**
	 * Search an existing XF style with the same given format. Used only by applyFormat().
	 *
	 * @param self $other
	 * @return Style The found style, or NULL if not found.
	 */
	private function applyFormat_searchEqualXf($other) {
		foreach ($this->workbook->getCellXfCollection() as $cellXf) {
			if ($other->applyFormat_equals($cellXf))
				return $cellXf;
		}
		return NULL;
	}
	

	/**
	 * Apply cell format to all the currently selected cells of the currently
	 * selected worksheet. Used only by Excel2003XMLReader.
	 * @param  string $format
	 * @return self
	 * @throws PHPExcelException
	 */
	public function applyFormat($format) {
		if ($this->isSupervisor) {
			$range = $this->getSelectedCells();

			// Uppercase coordinate
			$range = strtoupper($range);

			// Is it a cell range or a single cell?
			if (strpos($range, ':') === false) {
				$rangeA = $range;
				$rangeB = $range;
			} else {
				$a = explode(':', $range);
				$rangeA = $a[0];
				$rangeB = $a[1];
			}

			// Calculate range outer borders
			$rangeStart = Coordinates::parse($rangeA);
			$rangeEnd = Coordinates::parse($rangeB);

			// SIMPLE MODE:
			// Selection type, inspect
			if (1 == preg_match('/^[A-Z]+1:[A-Z]+1048576$/sD', $range)) {
				$selectionType = 'COLUMN';
			} elseif (1 == preg_match('/^A[0-9]+:XFD[0-9]+$/sD', $range)) {
				$selectionType = 'ROW';
			} else {
				$selectionType = 'CELL';
			}

			// First loop through columns, rows, or cells to find out which styles
			// are affected by this operation
			$oldXfIndexes = /*. (boolean[int]) .*/ array();
			switch ($selectionType) {
				case 'COLUMN':
//					for ($col = $rangeStart[0]; $col <= $rangeEnd[0]; ++$col) {
//						$oldXfIndexes[$this->getActiveSheet()
//								->getColumnDimensionByColumn($col)
//								->getXfIndex()] = true;
//					}
					break;
				case 'ROW':
//					for ($row = $rangeStart[1]; $row <= $rangeEnd[1]; ++$row) {
//						if ($this->getActiveSheet()->getRowDimension($row)->getXfIndex() === null) {
//							$oldXfIndexes[0] = true; // row without explicit style should be formatted based on default style
//						} else {
//							$oldXfIndexes[$this->getActiveSheet()->getRowDimension($row)->getXfIndex()] = true;
//						}
//					}
					break;
				case 'CELL':
					for ($col = $rangeStart->getColumnNumber(); $col <= $rangeEnd->getColumnNumber(); ++$col) {
						for ($row = $rangeStart->getRow(); $row <= $rangeEnd->getRow(); ++$row) {
							$oldXfIndexes[$this->getActiveSheet()->getCellByColumnAndRow($col, $row)->getXfIndex()] = true;
						}
					}
					break;
				default: throw new \RuntimeException();
			}

			// clone each of the affected styles, apply the style array, and add
			// the new styles to the workbook
			$workbook = $this->workbook;
			$newXfIndexes = /*. (int[int]) .*/ array();
			foreach ($oldXfIndexes as $oldXfIndex => $dummy) {
				$style = $workbook->getCellXfByIndex($oldXfIndex);
				$newStyle = clone $style;
				$newStyle->applyFormat($format);

				$existingStyle = $this->applyFormat_searchEqualXf($newStyle);
				if ($existingStyle !== NULL) {
					// there is already such cell Xf in our collection
					$newXfIndexes[$oldXfIndex] = $existingStyle->getIndex();
				} else {
					// we don't have such a cell Xf, need to add
					$workbook->addCellXf($newStyle);
					$newXfIndexes[$oldXfIndex] = $newStyle->getIndex();
				}
			}

			// Loop through columns, rows, or cells again and update the XF index
			switch ($selectionType) {
				case 'COLUMN':
//					for ($col = $rangeStart[0]; $col <= $rangeEnd[0]; ++$col) {
//						$columnDimension = $this->getActiveSheet()->getColumnDimensionByColumn($col);
//						$oldXfIndex = $columnDimension->getXfIndex();
//						$columnDimension->setXfIndex($newXfIndexes[$oldXfIndex]);
//					}
					break;

				case 'ROW':
//					for ($row = $rangeStart[1]; $row <= $rangeEnd[1]; ++$row) {
//						$rowDimension = $this->getActiveSheet()->getRowDimension($row);
//						$oldXfIndex = $rowDimension->getXfIndex() === null ?
//								0 : $rowDimension->getXfIndex(); // row without explicit style should be formatted based on default style
//						$rowDimension->setXfIndex($newXfIndexes[$oldXfIndex]);
//					}
					break;

				case 'CELL':
					for ($col = $rangeStart->getColumnNumber(); $col <= $rangeEnd->getColumnNumber(); ++$col) {
						for ($row = $rangeStart->getRow(); $row <= $rangeEnd->getRow(); ++$row) {
							$cell = $this->getActiveSheet()->getCellByColumnAndRow($col, $row);
							$oldXfIndex = $cell->getXfIndex();
							$cell->setXfIndex($newXfIndexes[$oldXfIndex]);
						}
					}
					break;
				default: throw new \RuntimeException();
			}
		} else {
			$this->setFormatCode($format);
		}
		return $this;
	}

	/**
	 * Search/replace values to convert Excel date/time format masks to PHP format
	 * masks.
	 *
	 * @var array
	 */
	private static $dateFormatReplacements = array(
		// first remove escapes related to non-format characters
		'\\' => '',
		//    12-hour suffix
		'am/pm' => 'A',
		//    4-digit year
		'e' => 'Y',
		'yyyy' => 'Y',
		//    2-digit year
		'yy' => 'y',
		//    first letter of month - no php equivalent
		'mmmmm' => 'M',
		//    full month name
		'mmmm' => 'F',
		//    short month name
		'mmm' => 'M',
		//    mm is minutes if time, but can also be month w/leading zero
		//    so we try to identify times be the inclusion of a : separator in the mask
		//    It isn't perfect, but the best way I know how
		':mm' => ':i',
		'mm:' => 'i:',
		//    month leading zero
		'mm' => 'm',
		//    month no leading zero
		'm' => 'n',
		//    full day of week name
		'dddd' => 'l',
		//    short day of week name
		'ddd' => 'D',
		//    days leading zero
		'dd' => 'd',
		//    days no leading zero
		'd' => 'j',
		//    seconds
		'ss' => 's',
		//    fractional seconds - no php equivalent
		'.s' => ''
	);

	/**
	 * Search/replace values to convert Excel date/time format masks hours to PHP format masks (24 hr clock)
	 *
	 * @var array
	 */
	private static $dateFormatReplacements24 = array(
		'hh' => 'H',
		'h' => 'G'
	);

	/**
	 * Search/replace values to convert Excel date/time format masks hours to PHP format masks (12 hr clock)
	 *
	 * @var string[string]
	 */
	private static $dateFormatReplacements12 = array(
		'hh' => 'h',
		'h' => 'g'
	);

	/**
	 * 
	 * @param string[int] $matches
	 * @return string
	 */
	private static function setLowercaseCallback($matches) {
		return mb_strtolower($matches[0], "UTF-8");
	}

	/**
	 * 
	 * @param string[int] $matches
	 * @return string
	 */
	private static function escapeQuotesCallback($matches) {
		return '\\' . implode('\\', str_split($matches[1]));
	}

	/**
	 * 
	 * @param float $value
	 * @param string $format
	 * @return string
	 * @throws PHPExcelException
	 */
	private static function formatAsDate($value, $format) {
		// strip off first part containing e.g. [$-F800] or [$USD-409]
		// general syntax: [$<Currency string>-<language info>]
		// language info is in hexadecimal
		$format = preg_replace('/^(\\[\\$[A-Z]*-[0-9A-F]*\\])/i', '', $format);

		// OpenOffice.org uses upper-case number formats, e.g. 'YYYY', convert to lower-case;
		//    but we don't want to change any quoted strings
		$format = preg_replace_callback('/(?:^|")([^"]*)(?:$|")/', array('self', 'setLowercaseCallback'), $format);

		// Only process the non-quoted blocks for date format characters
		$blocks = explode('"', $format);
		foreach ($blocks as $key => &$block) {
			if ($key % 2 == 0) {
				$block = strtr($block, self::$dateFormatReplacements);
				if (strpos($block, 'A') === FALSE) {
					// 24-hour time format
					$block = strtr($block, self::$dateFormatReplacements24);
				} else {
					// 12-hour time format
					$block = strtr($block, self::$dateFormatReplacements12);
				}
			}
		}
		$format = implode('"', $blocks);

		// escape any quoted characters so that DateTime format() will render them correctly
		$format = preg_replace_callback('/"(.*)"/U', array('self', 'escapeQuotesCallback'), $format);

		$dateObj = SharedDate::ExcelToDateTime($value);
		return $dateObj->format($format);
	}

	/**
	 * 
	 * @param float $value
	 * @param string $format
	 * @return string
	 */
	private static function formatAsPercentage($value, $format) {
		if ($format === self::FORMAT_PERCENTAGE) {
			return round((100 * $value), 0) . '%';
		} else {
			if (1 == preg_match('/\\.[#0]+/i', $format, $m)) {
				$s = "." . (strlen((string)$m[0]) - 1);
				$format = (string) str_replace((string)$m[0], $s, $format);
			}
			if (1 == preg_match('/^[#0]+/', $format, $m)) {
				$format = (string) str_replace((string)$m[0], strlen((string)$m[0]), $format);
			}
			$format = '%' . (string) str_replace('%', 'f%%', $format);

			return sprintf($format, 100 * $value);
		}
	}

	/**
	 * @param float $value
	 * @param string $format
	 * @return string
	 */
	private static function formatAsFraction($value, $format) {
		return (string) $value;
		
		// FIXME: not implemented -- code below is bugged, anyway
//		$sign = ($value < 0) ? '-' : '';
//
//		$integerPart = (string) floor(abs($value));
//		// FIXME: BUG: trim('0.00123', '0.') gives '123', not the expected '00123'!
//		$decimalPart = trim((string) fmod(abs($value), 1), '0.');
//		$decimalLength = strlen($decimalPart);
//		$decimalDivisor = pow(10, $decimalLength);
//
//		$GCD = PHPExcel_Calculation_MathTrig::GCD($decimalPart, $decimalDivisor);
//
//		$adjustedDecimalPart = $decimalPart / $GCD;
//		$adjustedDecimalDivisor = $decimalDivisor / $GCD;
//
//		if ((strpos($format, '0') !== false) || (strpos($format, '#') !== false) || (substr($format, 0, 3) === '? ?')) {
//			if ($integerPart === '0') {
//				$integerPart = '';
//			}
//			return "$sign$integerPart $adjustedDecimalPart/$adjustedDecimalDivisor";
//		} else {
//			$adjustedDecimalPart += $integerPart * $adjustedDecimalDivisor;
//			return "$sign$adjustedDecimalPart/$adjustedDecimalDivisor";
//		}
	}

	/**
	 * 
	 * @param float $number
	 * @param string $mask
	 * @param int $level
	 * @return string
	 */
	private static function complexNumberFormatMask($number, $mask, $level = 0) {
		// FIXME: not implemented
		return "$number";
//		$sign = ($number < 0.0);
//		$number = abs($number);
//		if (strpos($mask, '.') !== false) {
//			$numbers = explode('.', $number . '.0');
//			$masks = explode('.', $mask . '.0');
//			$result1 = self::complexNumberFormatMask($numbers[0], $masks[0], 1);
//			$result2 = strrev(self::complexNumberFormatMask(strrev($numbers[1]), strrev($masks[1]), 1));
//			return (($sign) ? '-' : '') . $result1 . '.' . $result2;
//		}
//
//		$r = preg_match_all('/0+/', $mask, $result, PREG_OFFSET_CAPTURE);
//		if ($r > 1) {
//			$result = array_reverse($result[0]);
//
//			foreach ($result as $block) {
//				$divisor = 1 . $block[0];
//				$size = strlen($block[0]);
//				$offset = $block[1];
//
//				$blockValue = sprintf(
//						'%0' . $size . 'd', fmod($number, $divisor)
//				);
//				$number = floor($number / $divisor);
//				$mask = substr_replace($mask, $blockValue, $offset, $size);
//			}
//			if ($number > 0) {
//				$mask = substr_replace($mask, $number, $offset, 0);
//			}
//			$result = $mask;
//		} else {
//			$result = $number;
//		}
//
//		return (($sign) ? '-' : '') . $result;
	}

	/**
	 * Returns the value of this cell formatted as it would be displayed to the
	 * user. This function tries to do its best, but there might be differences,
	 * as this function uses the current locale setting (for example, month names)
	 * that might differ from the user's one.
	 *
	 * @param mixed  $value_  Value to format
	 * @param string $format Format code
	 * @return string Formatted string
	 * @throws PHPExcelException
	 */
	public static function toFormattedString($value_, $format) {
// For now we do not treat strings although section 4 of a format code affects strings
		if( $value_ === NULL ){
			return "";
		} else if( is_int($value_) || is_float($value_) ){
			$value = (float) $value_;
		} else if( is_bool($value_) ){
			if( (boolean) $value_ )
				return "TRUE";
			else
				return "FALSE";
		} else if( is_string($value_) ){
//			if( is_numeric($value_) )
//				$value = (float) $value_;
//			else
				return (string) $value_;
		} else {
			throw new \InvalidArgumentException("invalid type: ".gettype($value_));
		}
		
		// it is int, double or string containing something that looks like a number

		// For 'General' format code, we just pass the value although this is not entirely the way Excel does it,
		// it seems to round numbers to a total of 10 digits.
		if (($format === self::FORMAT_GENERAL) || ($format === self::FORMAT_TEXT)) {
			return (string) $value;
		}

		// Convert any other escaped characters to quoted strings, e.g. (\T to "T")
		$format = preg_replace('/(\\\\(.))(?=(?:[^"]|"[^"]*")*$)/u', '"${2}"', $format);

		// Get the sections, there can be up to four sections, separated with a semi-colon (but only if not a quoted literal)
		$sections_ = preg_split('/(;)(?=(?:[^"]|"[^"]*")*$)/u', $format);
		$sections = /*. (string[int]) .*/ array();
		foreach($sections_ as $section)
			$sections[] = (string) $section;

		// Extract the relevant section depending on whether number is positive, negative, or zero?
		// Text not supported yet.
		// Here is how the sections apply to various values in Excel:
		//   1 section:   [POSITIVE/NEGATIVE/ZERO/TEXT]
		//   2 sections:  [POSITIVE/ZERO/TEXT] [NEGATIVE]
		//   3 sections:  [POSITIVE/TEXT] [NEGATIVE] [ZERO]
		//   4 sections:  [POSITIVE] [NEGATIVE] [ZERO] [TEXT]
		switch (count($sections)) {
			case 1:
				$format = $sections[0];
				break;
			case 2:
				$format = ($value >= 0) ? $sections[0] : $sections[1];
				$value = abs($value); // Use the absolute value
				break;
			case 3:
				$format = ($value > 0) ?
						$sections[0] : ( ($value < 0) ?
								$sections[1] : $sections[2]);
				$value = abs($value); // Use the absolute value
				break;
			case 4:
				$format = ($value > 0) ?
						$sections[0] : ( ($value < 0) ?
								$sections[1] : $sections[2]);
				$value = abs($value); // Use the absolute value
				break;
			default:
				// something is wrong, just use first section
				$format = $sections[0];
				break;
		}

		// In Excel formats, "_" is used to add spacing,
		//    The following character indicates the size of the spacing, which we can't do in HTML, so we just use a standard space
		$format = preg_replace('/_./', ' ', $format);

		// Strip color information
		$color_regex = '/^\\[[a-zA-Z]+\\]/';
		$format = preg_replace($color_regex, '', $format);

		// Let's begin inspecting the format and converting the value to a formatted string
		//  Check for date/time characters (not inside quotes)
		if (1 == preg_match('/(\\[\\$[A-Z]*-[0-9A-F]*\\])*[hmsdy](?=(?:[^"]|"[^"]*")*$)/miu', $format, $matches)) {
			// datetime format
			$res = self::formatAsDate($value, $format);
		} elseif (1 == preg_match('/%$/', $format)) {
			// % number format
			$res = self::formatAsPercentage($value, $format);
		} else {
			if ($format === self::FORMAT_CURRENCY_EUR_SIMPLE) {
				$res = 'EUR ' . sprintf('%1.2f', $value);
			} else {
				// Some non-number strings are quoted, so we'll get rid of the quotes, likewise any positional * symbols
				$format = (string) str_replace(array('"', '*'), '', $format);

				// Find out if we need thousands separator
				// This is indicated by a comma enclosed by a digit placeholder:
				//        #,#   or   0,0
				$useThousands = 1 == preg_match('/(#,#|0,0)/', $format);
				if ($useThousands) {
					$format = preg_replace('/0,0/', '00', $format);
					$format = preg_replace('/#,#/', '##', $format);
				}

				// Scale thousands, millions,...
				// This is indicated by a number of commas after a digit placeholder:
				//        #,   or    0.0,,
				$scale = 1.0; // same as no scale
				$matches = array();
				if (1 == preg_match('/(#|0)(,+)/', $format, $matches)) {
					$scale = pow(1000, strlen((string)$matches[2]));

					// strip the commas
					$format = preg_replace('/0,+/', '0', $format);
					$format = preg_replace('/#,+/', '#', $format);
				}

				if (1 == preg_match('/#?.*\\?\\/\\?/', $format)) {
					if ($value != (int) $value) {
						$res = self::formatAsFraction($value, $format);
					} else {
						$res = (string) $value;
					}
				} else {
					// Handle the number itself
					// scale number
					$value = $value / $scale;

					// Strip #
					$format = preg_replace('/\\#/', '0', $format);

					$n = "/\\[[^\\]]+\\]/";
					$m = preg_replace($n, '', $format);
					$number_regex = "/(0+)(\\.?)(0*)/";
					if (1 == preg_match($number_regex, $m, $matches)) {
						$left = (string) $matches[1];
						$dec = (string) $matches[2];
						$right = (string) $matches[3];

						// minimun width of formatted number (including dot)
						$minWidth = strlen($left) + strlen($dec) + strlen($right);
						if ($useThousands) {
							$res = number_format($value, strlen($right), '.', ',');
							$res = preg_replace($number_regex, $res, $format);
						} else {
							if (1 == preg_match('/[0#]E[+-]0/i', $format)) {
								//    Scientific format
								$res = sprintf('%5.2E', $value);
							} elseif (1 == preg_match('/0([^\\d\\.]+)0/', $format)) {
								$res = self::complexNumberFormatMask($value, $format);
							} else {
								$sprintf_pattern = "%0$minWidth." . strlen($right) . "f";
								$res = sprintf($sprintf_pattern, $value);
								$res = preg_replace($number_regex, $res, $format);
							}
						}
					} else {
						$res = (string) $value;
					}
				}
				if (1 == preg_match('/\\[\\$([^\\]]*)\\]/u', $format, $matches)) {
					//  Currency or Accounting
//					$currencyFormat = $matches[0];
					$currencyCode = (string) $matches[1];
					$currencyCode = explode('-', $currencyCode)[0];
					if ($currencyCode === '') {
//						$currencyCode = SharedString::getCurrencyCode();
						// Default currency on the remote client unknown.
						$currencyCode = '?';
					}
//					$res = preg_replace('/\\[\\$([^\\]]*)\\]/u', $currencyCode, $value);
					$res = "$currencyCode$value";
				}
			}
		}

//		// Escape any escaped slashes to a single slash
//		$format = preg_replace("/\\\\/u", '\\', $format);

		return $res;
	}

}
