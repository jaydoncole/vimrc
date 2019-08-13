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
 * @version $Date: 2018/06/06 09:50:58 $
 */

namespace com\github\phpoffice\phpexcelreader;

require_once __DIR__ . "/../../../../all.php";

/**
 * Represents a cell in a worksheet. This class implements two abstraction levels
 * for the type and value if the data. At the Excel level, a cell has a value, a
 * type and a format (this latter held by a style object); the actual meaning of
 * the value depends on all these informations put together. Moreover, this class
 * also provides a PHPExcelReader point of view where a cell has only a type and
 * a value, where the available types closely match the expectations of a normal
 * application program or a data base: null, number, date, time, date with time
 * and text.
 */
class Cell implements \it\icosaedro\containers\Printable
{

	/**
	 * Excel idea of a cell containing a string, possibly shared between other cells.
	 */
	const TYPE_STRING = 's';
	/**
	 * Excel idea of a cell containing a formula.
	 */
	const TYPE_FORMULA = 'f';
	/**
	 * Excel idea of a cell containing a number, either int or float. Numbers can
	 * also represent several types of higher level data depending on the format
	 * of the cell, like date, time and currency.
	 */
	const TYPE_NUMERIC = 'n';
	/**
	 * Excel idea of a cell containing a boolean value FALSE or TRUE.
	 */
	const TYPE_BOOL = 'b';
	/**
	 * Excel idea of a cell whose value is NULL, that is not set.
	 */
	const TYPE_NULL = 'null';
	/**
	 * Excel idea of a cell containing a string.
	 */
	const TYPE_INLINE = 'inlineStr';
	/**
	 * Excel idea of a cell containing an error, encoded as a string.
	 */
	const TYPE_ERROR = 'e';
	
	
	/**
	 * PHPExcelReader idea of a cell whose value is not set.
	 */
	const PARSED_TYPE_NULL = 1;
	/**
	 * PHPExcelReader idea of a cell containing an Excel error message.
	 */
	const PARSED_TYPE_ERROR = 2;
	/**
	 * PHPExcelReader idea of a cell containing a boolean.
	 */
	const PARSED_TYPE_BOOLEAN = 3;
	/**
	 * PHPExcelReader idea of a cell containing a number.
	 */
	const PARSED_TYPE_NUMBER = 4;
	/**
	 * PHPExcelReader idea of a cell containing a date.
	 */
	const PARSED_TYPE_DATE = 5;
	/**
	 * PHPExcelReader idea of a cell containing a time.
	 */
	const PARSED_TYPE_TIME = 6;
	/**
	 * PHPExcelReader idea of a cell containing a date with time.
	 */
	const PARSED_TYPE_DATETIME = 7;
	/**
	 * PHPExcelReader idea of a cell containing a currency.
	 */
	const PARSED_TYPE_CURRENCY = 8;
	/**
	 * PHPExcelReader idea of a cell containing a text.
	 */
	const PARSED_TYPE_TEXT = 9;
	/**
	 * PHPExcelReader idea of a cell containing a formula.
	 */
	const PARSED_TYPE_FORMULA = 10;
	
	private $parsed_type = 0;
	
	/**
	 * @access private
	 */
	const FIXME_CELL_CALCULATION_DISABLED = NULL; // FIXME: cell calculation disabled

	/**
	 * Default range variable constant
	 */
	const DEFAULT_RANGE = 'A1:A1';
	
	/**
	 * @var Coordinates
	 */
	private $coordinates;

	/**
	 * Type of the cell data, see constants Cell::TYPE_*.
	 *
	 * @var string
	 */
	private $dataType;

	/**
	 * Value of the cell, depending on its type:
	 * 
	 * <table>
	 * <tr> <th>Type</th> <th>Content</th> </tr>
	 * <tr> <td>NULL</td> <td>NULL</td> </tr>
	 * <tr> <td>BOOL</td> <td>FALSE, TRUE</td> </tr>
	 * <tr> <td>STRING, STRING2, FORMULA (?), INLINE, ERROR</td> <td>string, UTF-8</td> </tr>
	 * <tr> <td>NUMERIC</td> <td>int, float (number, time or date and time depending on the style)</td> </tr>
	 * </table>
	 *
	 * @var mixed
	 */
	private $value;

	/**
	 * Calculated value of the cell (used for caching).
	 * This returns the value last calculated by MS Excel or whichever spreadsheet
	 * program was used to create the original spreadsheet file.
	 * Note that this value is not guaranteed to reflect the actual calculated value
	 * because it is possible that auto-calculation was disabled in the original
	 * spreadsheet, and underlying data values used by the formula have changed
	 * since it was last calculated.
	 *
	 * @var mixed
	 */
	private $calculatedValue;

	/**
	 * Cells' storage, that is my own worksheet.
	 *
	 * @var Worksheet
	 */
	private $worksheet;

	/**
	 * Index to cellXf
	 *
	 * @var int
	 */
	private $xfIndex = 0;

	/**
	 * List of error codes
	 *
	 * @var array
	 */
	private static $errorCodes = array(
		'#NULL!' => 0,
		'#DIV/0!' => 1,
		'#VALUE!' => 2,
		'#REF!' => 3,
		'#NAME?' => 4,
		'#NUM!' => 5,
		'#N/A' => 6
	);

/*.
	forward public string    function __toString();
	forward public Coordinates function getCoordinate();
	forward public void      function setValue(string $pValue) throws PHPExcelException;
	forward public void      function setValueExplicit(mixed $pValue, string $pDataType) throws PHPExcelException;
	forward public static string[int][int] function splitRange(string $pRange);
	forward public void      function setXfIndex(int $pValue);
	forward public int       function getXfIndex();
	forward public Style     function getStyle();
	forward public static string function buildRange(string[int][int] $pRange);
	forward public void      function __construct(Worksheet $worksheet, Coordinates $coordinates);
	forward public void      function setCalculatedValue(mixed $pValue);
	forward public mixed     function getValue();
	forward public string    function getDataType();
	forward public string    function getFormattedValue() throws PHPExcelException;

	pragma 'suspend';

.*/

	/**
	 * Get list of error codes
	 *
	 * @return array
	 */
	public static function getErrorCodes() {
		return self::$errorCodes;
	}

	/**
	 * Check a value that it is a valid error code
	 *
	 * @param string $s Value to sanitize to an Excel error code
	 * @return string  Sanitized value
	 */
	public static function checkErrorCode($s) {
		if (!array_key_exists($s, self::$errorCodes)) {
			$s = '#NULL!';
		}

		return $s;
	}

//	public function detach() {
//		$this->worksheet = null;
//	}

	/**
	 * Get cell coordinates.
	 *
	 * @return Coordinates
	 */
	public function getCoordinate() {
		return $this->coordinates;
	}
	
	
	/**
	 * Detect data type from cell's value.
	 *
	 * @param   string $value
	 * @param   mixed  & $valueCast Here returns the value cast to the detected type.
	 * @return  string Detected data type, see constabts TYPE_*.
	 * @throws  PHPExcelException Unknown data type.
	 */
	public static function dataTypeForValue($value, /*. return .*/ & $valueCast) {
		if ($value === null) {
			$valueCast = NULL;
			return self::TYPE_NULL;
		} else {
			$valueCast = $value;
			if (strlen($value) == 0) {
				return self::TYPE_STRING;
			} else if (array_key_exists($value, self::getErrorCodes())) {
				return self::TYPE_ERROR;
			} else if ($value[0] === '=' && strlen($value) > 1) {
				return self::TYPE_FORMULA;
			} else if (1 == preg_match('/^[\\+\\-]?([0-9]+\\.?[0-9]*|[0-9]*\\.?[0-9]+)([Ee][\\-\\+]?[0-2]?\\d{1,3})?$/sD', $value)) {
				$tValue = ltrim($value, '+-');
				if ($tValue[0] === '0' && strlen($tValue) > 1 && $tValue[1] !== '.') {
					return self::TYPE_STRING;
				} else if ((strpos($value, '.') === false) && ((float) $value > PHP_INT_MAX)) {
					return self::TYPE_STRING;
				} else {
					$valueFloat = (float) $value;
					if( $valueFloat == (int) $valueFloat){
						$valueCast = (int) $valueFloat;
					} else {
						$valueCast = $valueFloat;
					}
					return self::TYPE_NUMERIC;
				}
			} else {
				return self::TYPE_STRING;
			}
		}
	}

	/**
	 * Get cell value, depending on its type.
	 * 
	 * <table>
	 * <tr> <th>Type</th> <th>Content</th> </tr>
	 * <tr> <td>NULL</td> <td>NULL</td> </tr>
	 * <tr> <td>BOOL</td> <td>FALSE, TRUE</td> </tr>
	 * <tr> <td>STRING, STRING2, FORMULA (?), INLINE, ERROR</td> <td>string, UTF-8</td> </tr>
	 * <tr> <td>NUMERIC</td> <td>int, float (number, time or date and time depending on the style)</td> </tr>
	 * </table>
	 *
	 * @return mixed
	 */
	public function getValue() {
		return $this->value;
	}

	/**
	 * Get calculated cell value
	 *
	 * @deprecated Since version 1.7.8 for planned changes to cell for array formula handling
	 *
	 * @param boolean $resetLog Whether the calculation engine logger should be reset or not
	 * @return mixed
	 * @throws PHPExcelException
	 */
	public function getCalculatedValue($resetLog = true) {
		if ($this->dataType === Cell::TYPE_FORMULA) {
//			try {
//				$result = Calculation::getInstance(
//								$this->getWorksheet()->getParent()
//						)->calculateCellValue($this, $resetLog);
//				// We don't yet handle array returns
//				if (is_array($result)) {
//					while (is_array($result)) {
//						$result = array_pop($result);
//					}
//				}
//			} catch (PHPExcelException $ex) {
//				if (($ex->getMessage() === 'Unable to access External Workbook') && ($this->calculatedValue !== null)) {
//					return $this->calculatedValue; // Fallback for calculations referencing external files.
//				}
//				$result = '#N/A';
//				throw new CalculationException(
//					$this->getWorksheet()->getTitle() . '!' . $this->getCoordinate() . ' -> ' . $ex->getMessage()
//				);
//			}
//
//			if ($result === '#Not Yet Implemented') {
//				return $this->calculatedValue; // Fallback if calculation engine does not support the formula.
//			}
//			return $result;
			return self::FIXME_CELL_CALCULATION_DISABLED;
//		} else if ($this->value instanceof RichText) {
//			return cast(RichText::class, $this->value)->getPlainText();
		} else {
			return $this->value;
		}
	}
	

	/**
	 * Get cell style
	 *
	 * @return Style
	 */
	public function getStyle() {
		$workbook = $this->worksheet->getWorkbook();
		return $workbook->getCellXfByIndex($this->xfIndex);
	}
	

	/**
	 * Returns the PHPExcelReader idea of the type of the data in this cell.
	 * The result combines together the value, the Excel type and the format of
	 * the cell in order to guess the actual meaning of the data.
	 * @return int One of the contants self::PARSED_TYPE_*.
	 */
	public function getType() {
		if( $this->parsed_type != 0 )
			return $this->parsed_type;
		$type = 0;
		switch($this->dataType){
			case Cell::TYPE_NULL: $type = self::PARSED_TYPE_NULL;  break;
			case Cell::TYPE_BOOL: $type = self::PARSED_TYPE_BOOLEAN;  break;
			case Cell::TYPE_ERROR: $type = self::PARSED_TYPE_ERROR;  break;
			
			case Cell::TYPE_INLINE:
			case Cell::TYPE_STRING:
				$type = self::PARSED_TYPE_TEXT; break;
				
			case Cell::TYPE_FORMULA: $type = self::PARSED_TYPE_FORMULA;  break;
			
			case Cell::TYPE_NUMERIC:
				$format = $this->getStyle()->getFormatCode();
				$date_time_mask = SharedDate::identifyDateAndOrTimeFormatCode($format);
				$currency = Style::identifyCurrencyName($format);
				if( $date_time_mask > 0 && strlen($currency) > 0 ){
					// Conflicting data/time and currency format specifiers detected.
					// Currency detection seems to be safer because of the $ sign.
					$type = self::PARSED_TYPE_CURRENCY;
					break;
				}
				if( strlen($currency) > 0 ){
					$type = self::PARSED_TYPE_CURRENCY;
					break;
				}
				if( $date_time_mask > 0 ){
					$x = (float) $this->value;
					if( 0.0 <= $x && $x < 1.0 && $date_time_mask == 2 )
						$type = self::PARSED_TYPE_TIME;
					else if( $x >= 1.0 && $date_time_mask == 1 )
						$type = self::PARSED_TYPE_DATE;
					else if( $x >= 1.0 && $date_time_mask == 3 )
						$type = self::PARSED_TYPE_DATETIME;
					else
						// Conflicting negative number with date/time formatting.
						$type = self::PARSED_TYPE_NUMBER;
					break;
				}
				$type = self::PARSED_TYPE_NUMBER;
				break;
				
			default:
				throw new \RuntimeException("unexpected cell type: ".$this->dataType);
		}
		return $this->parsed_type = $type;
	}
	
	
	/**
	 * Tells if the cell contains an error code.
	 * @return boolean
	 */
	public function isError() {
		return $this->getType() == self::PARSED_TYPE_ERROR;
	}
	
	
	/**
	 * Tells if the cell contains NULL.
	 * @return boolean
	 */
	public function isNull() {
		return $this->getType() == self::PARSED_TYPE_NULL;
	}
	
	
	/**
	 * Tells if the cell contains a boolean value.
	 * @return boolean
	 */
	public function isBoolean() {
		return $this->getType() == self::PARSED_TYPE_BOOLEAN;
	}
	

	/**
	 * Identify if the cell contains a formula, that is a string.
	 * @return boolean
	 */
	public function isFormula() {
		return $this->getType() == self::PARSED_TYPE_FORMULA;
	}
	

	/**
	 * Identify if the cell contains a string.
	 * @return boolean
	 */
	public function isText() {
		return $this->getType() == self::PARSED_TYPE_TEXT;
	}
	

	/**
	 * Identify if the cell contains a number. In this case the value of the cell
	 * is either int or float.
	 * @return boolean
	 */
	public function isNumber() {
		return $this->getType() == self::PARSED_TYPE_NUMBER;
	}
	

	/**
	 * Identify if the cell contains a date and time. If true, the value can be
	 * converted to a {@link \DateTime} object set in GMT time zone through the
	 * {@link \com\github\phpoffice\phpexcelreader\SharedDate::ExcelToDateTime()} method.
	 * @return boolean
	 */
	public function isDateTime() {
		return $this->getType() == self::PARSED_TYPE_DATETIME;
	}
	

	/**
	 * Identify if the cell contains a date only. If true, the value can be
	 * converted to a {@link \DateTime} object set in GMT time zone through the
	 * {@link \com\github\phpoffice\phpexcelreader\SharedDate::ExcelToDateTime()} method.
	 * @return boolean
	 */
	public function isDate() {
		return $this->getType() == self::PARSED_TYPE_DATE;
	}
	

	/**
	 * Identify if the cell contains a time. If true, the value is a floating
	 * point number in the range between 0.0 (corresponding to time 00:00:00)
	 * and 1.0 (excluded, corresponding to time 24:00:00). The
	 * {@link \com\github\phpoffice\phpexcelreader\SharedDate::ExcelToDateTime()}
	 * method can be used to retrieve a {@link \DateTime} object set in GMT time
	 * zone indicating an hour of the day 1970-01-01 GMT, where obviously the date
	 * part should be ignored.
	 * @return boolean
	 */
	public function isTime() {
		return $this->getType() == self::PARSED_TYPE_TIME;
	}
	
	
	/**
	 * Identify if the cell contains a currency. The currency name can be retrieved
	 * by the getCurrency() method.
	 * @return boolean
	 */
	public function isCurrency() {
		return $this->getType() == self::PARSED_TYPE_CURRENCY;
	}
	
	
	/**
	 * Returns the currency name if this cell contains a currency value. Note that
	 * the same currency name can be returned in several ways, for example:
	 * $ or USD, € or EUR, etc.
	 * @return string Currency name used to format this currency value. An empty
	 * string is returned if this cell does not contain a currency. The question
	 * mark '?' is returned if the currency name is not explicitly indicated,
	 * in which case the remote client default locale currency (which is unknown)
	 * should be assumed instead.
	 */
	public function getCurrency() {
		$format = $this->getStyle()->getFormatCode();
		return Style::identifyCurrencyName($format);
	}
	

	/**
	 * Get cell as a value that best matches a corresponding well known PHP type.
	 * NULL is returned as NULL.
	 * Boolean is returned as boolean.
	 * Numbers are returned as int or float.
	 * Strings and errors are returned as string.
	 * Dates and date/times are returned as {@link \DateTime} with time zone GMT.
	 * Times are returned as {@link \DateTime} with day set to 1970-01-01 GMT.
	 * Currency is returned as string "$ 12.34", where "$" is the specific currency
	 * name and "12.34" is the value.
	 * @return mixed
	 * @throws PHPExcelException Date conversion failed.
	 */
	public function getPHPValue() {
		$type = $this->getType();
		switch($type){
			
			case self::PARSED_TYPE_NULL:
			case self::PARSED_TYPE_BOOLEAN:
			case self::PARSED_TYPE_ERROR:
			case self::PARSED_TYPE_TEXT:
			case self::PARSED_TYPE_FORMULA:
			case self::PARSED_TYPE_NUMBER:
				return $this->value;
			
			case self::PARSED_TYPE_DATE:
			case self::PARSED_TYPE_TIME:
			case self::PARSED_TYPE_DATETIME:
				return SharedDate::ExcelToDateTime((float) $this->value);
			
			case self::PARSED_TYPE_CURRENCY:
				return $this->getCurrency() . " " . (float) $this->value;
				
			default:
				throw new \RuntimeException("unexpected cell type: $type");
		}
	}
	

	/**
	 * Get cell value formatted according to the style set by user. This function
	 * does its best to retrieve what the user actually see on its screen, but
	 * there might be some differences as this function uses the current locale
	 * setting (for example, month names) that might differ from the user's one.
	 * 
	 * <p>BEWARE. Some ambiguities may arise trying to parse the resulting value,
	 * some due to the specific Excel built-in or user's custom format used, and
	 * some due to the different locale set in the remote client computer.
	 * <br>In some dates, month and day order cannot be recognized safely; things
	 * worsen when 2-digit year is used.
	 * <br>Some time formats that uses "h" (12-hours format) but not AM/PM may
	 * bring to ambiguous values, for example "12:00" can represent midnight but
	 * also midday.
	 * <br>Month names and day names depends on the locale set on the server and
	 * then may differ from the locale set in the remote client computer.
	 * 
	 * <p>See also the {@link self::__toString()} method which returns a more
	 * standardized representation for each type of data.
	 * <p>See also the {@link self::getPHPValue()} method which returns the value
	 * of the cell as a specific PHP type for each specific type of cell.
	 * @return string
	 * @throws PHPExcelException Date conversion failed.
	 */
	public function getFormattedValue() {
		$type = $this->getType();
		switch($type){
			
			case self::PARSED_TYPE_NULL:
				return "";
				
			case self::PARSED_TYPE_BOOLEAN:
				return (bool) $this->value? "TRUE" : "FALSE";
				
			case self::PARSED_TYPE_ERROR:
			case self::PARSED_TYPE_TEXT:
			case self::PARSED_TYPE_FORMULA:
				return (string) $this->value;
				
			case self::PARSED_TYPE_NUMBER:
			case self::PARSED_TYPE_DATE:
			case self::PARSED_TYPE_TIME:
			case self::PARSED_TYPE_DATETIME:
			case self::PARSED_TYPE_CURRENCY:
				return Style::toFormattedString(
					$this->getCalculatedValue(),
					$this->getStyle()->getFormatCode());
				
			default:
				throw new \RuntimeException("unexpected cell type: $type");
		}
	}

	/**
	 * Set cell value, automatically determining the datatype.
	 * @param string $pValue Value
	 * @return void
	 * @throws PHPExcelException
	 */
	public function setValue($pValue) {
		// sanitize UTF-8 strings
		if (is_string($pValue)) {
			try {
				$pValue = SharedString::SanitizeUTF8($pValue);
			}
			catch(\ErrorException $e){
				throw new PHPExcelException($e->getMessage());
			}
//		} elseif (is_object($pValue)) {
//			// Handle any objects that might be injected
//			if ($pValue instanceof \DateTime) {
//				$pValue = cast("DateTime", $pValue)->format('Y-m-d H:i:s');
//			} elseif (!($pValue instanceof RichText)) {
//				$pValue = (string) $pValue;
//			}
		}
		
		$type = Cell::dataTypeForValue($pValue, $valueCast);
		$this->setValueExplicit($valueCast, $type);
	}
	

	/**
	 * Set the value for a cell, with the explicit data type passed to the method
	 * bypassing any use of the value binder.
	 *
	 * @param mixed $pValue Value
	 * @param string $pDataType Explicit data type
	 * @return void
	 * @throws PHPExcelException
	 */
	public function setValueExplicit($pValue, $pDataType) {
		// set the value according to data type
		switch ($pDataType) {
			case Cell::TYPE_NULL:
				if( ! is_null($pValue) )
					throw new PHPExcelException("not NULL: ".  gettype($pValue));
				$this->value = NULL;
				break;
			case Cell::TYPE_STRING:
				$pDataType = Cell::TYPE_STRING;
			/*. missing_break; .*/
			// Synonym for string
			case Cell::TYPE_INLINE:
				if( ! is_string($pValue) )
					throw new PHPExcelException("not string: ".  gettype($pValue));
				// FIXME: here is the right point where check UTF-8 encoding
				$this->value = SharedString::checkString((string) $pValue);
				break;
			case Cell::TYPE_NUMERIC:
				if( !(is_int($pValue) || is_float($pValue)) )
					throw new PHPExcelException("not int|float: ".  gettype($pValue));
				$this->value = $pValue;
				break;
			case Cell::TYPE_FORMULA:
				if( ! is_string($pValue) )
					throw new PHPExcelException("not string: ".  gettype($pValue));
				$this->value = $pValue;
				break;
			case Cell::TYPE_BOOL:
				if( !is_bool($pValue) )
					throw new PHPExcelException("not bool: ".  gettype($pValue));
				$this->value = $pValue;
				break;
			case Cell::TYPE_ERROR:
				if( ! is_string($pValue) )
					throw new PHPExcelException("not string: ".  gettype($pValue));
				$this->value = Cell::checkErrorCode((string) $pValue);
				break;
			default:
				throw new PHPExcelException('Invalid datatype: ' . $pDataType);
		}

		$this->dataType = $pDataType;
	}
	

	/**
	 * Set old calculated value (cached).
	 *
	 * @param mixed $pValue String, integer or float value.
	 * @return void
	 */
	public function setCalculatedValue($pValue) {
		$this->calculatedValue = $pValue;
	}

	/**
	 * Get old calculated value (cached).
	 * This returns the value last calculated by MS Excel or whichever spreadsheet
	 * program was used to create the original spreadsheet file.
	 * Note that this value is not guaranteed to reflect the actual calculated
	 * value because it is possible that auto-calculation was disabled in the original
	 * spreadsheet, and underlying data values used by the formula have changed
	 * since it was last calculated.
	 *
	 * @return mixed
	 */
	public function getOldCalculatedValue() {
		return $this->calculatedValue;
	}

	/**
	 * Get cell data type, see constants Cell::TYPE_*.
	 *
	 * @return string One of the Cell::TYPE_* constants.
	 */
	public function getDataType() {
		return $this->dataType;
	}

	/**
	 * Split range into coordinate strings.
	 *
	 * @param string $pRange e.g. 'B4:D9' or 'B4:D9,H2:O11' or 'B4'.
	 * @return string[int][int] Array containg one or more arrays containing
	 * one or two coordinate strings e.g. array('B4','D9') or
	 * array(array('B4','D9'),array('H2','O11'))
	 * or array('B4').
	 */
	public static function splitRange($pRange) {
		// Ensure $pRange is a valid range
		if (empty($pRange)) {
			$pRange = self::DEFAULT_RANGE;
		}

		$ranges = explode(',', $pRange);
		$counter = count($ranges);
		$exploded = /*. (string[int][int]) .*/ array();
		for ($i = 0; $i < $counter; ++$i) {
			$exploded[$i] = explode(':', $ranges[$i]);
		}
		return $exploded;
	}

	/**
	 * Build range from coordinate strings.
	 *
	 * @param string[int][int] $pRange Array containg one or more arrays containing
	 * one or two coordinate strings.
	 */
	public static function buildRange($pRange) {
		$imploded = /*. (string[int]) .*/ array();
		$counter = count($pRange);
		for ($i = 0; $i < $counter; ++$i) {
			$imploded[$i] = implode(':', $pRange[$i]);
		}
		return implode(',', $imploded);
	}

//	/**
//	 * Calculate range boundaries
//	 *
//	 * @param string $pRange Cell range (e.g. A1:A1)
//	 * @return array Range coordinates array(Start Cell, End Cell)
//	 * where Start Cell and End Cell are arrays (Column ID, Row Number)
//	 */
//	public static function getRangeBoundaries($pRange) {
//		// Ensure $pRange is a valid range
//		if (empty($pRange)) {
//			$pRange = self::DEFAULT_RANGE;
//		}
//
//		// Uppercase coordinate
//		$pRange = strtoupper($pRange);
//
//		// Extract range
//		if (strpos($pRange, ':') === false) {
//			$rangeA = $rangeB = $pRange;
//		} else {
//			$a = explode(':', $pRange);
//			$rangeA = $a[0];
//			$rangeB = $a[1];
//		}
//
//		return array(self::coordinateFromString($rangeA), self::coordinateFromString($rangeB));
//	}

	/**
	 * Get index to cellXf
	 *
	 * @return int
	 */
	public function getXfIndex() {
		return $this->xfIndex;
	}

	/**
	 * Set index to cellXf
	 *
	 * @param int $pValue
	 * @return void
	 */
	public function setXfIndex($pValue) {
		$this->xfIndex = $pValue;
	}


	/**
	 * Returns a readable representation of the value of this cell using a standard
	 * notation for each type of value.
	 * NULL is returned as empty string.
	 * Boolean is returned as "FALSE" or "TRUE".
	 * Numbers are returned in their PHP usual notation, possibly scientific form.
	 * Date with time is returned in ISO 8601 format.
	 * Time is returned in HH:MM:SS format.
	 * Currency is returned as string "$ 12.34", where "$" is the specific currency
	 * name and "12.34" is the value.
	 * @return string
	 * @throws PHPExcelException Date conversion failed.
	 */
	public function toString() {
		$type = $this->getType();
		switch($type){
			case self::PARSED_TYPE_NULL:
				return "";
				
			case self::PARSED_TYPE_BOOLEAN:
				return (bool) $this->value? "TRUE" : "FALSE";
				
			case self::PARSED_TYPE_ERROR:
			case self::PARSED_TYPE_TEXT:
				return preg_replace("/[\\x00-\x08\x0b-\x0c\x0e-\x1f]/", "?", (string)$this->value);
				
			case self::PARSED_TYPE_FORMULA:
				return "";
			
			case self::PARSED_TYPE_NUMBER:
				return (string) $this->value;
			
			case self::PARSED_TYPE_DATE:
				$t = (float) $this->value;
				$dt = SharedDate::ExcelToDateTime($t);
				return $dt->format("Y-m-d");
				
			case self::PARSED_TYPE_TIME:
				$t = (float) $this->value;
				$dt = SharedDate::ExcelToDateTime($t);
				return $dt->format("H:i:s");
				
			case self::PARSED_TYPE_DATETIME:
				$t = (float) $this->value;
				$dt = SharedDate::ExcelToDateTime($t);
				return $dt->format("Y-m-d H:i:s");
			
			case self::PARSED_TYPE_CURRENCY:
				return $this->getCurrency() . " " . (float) $this->value;
				
			default:
				throw new \RuntimeException("unexpected cell type: $type");
		}
	}


	/**
	 * Returns a readable representation of the value of this cell using a standard
	 * notation for each type of value.
	 * NULL is returned as empty string.
	 * Boolean is returned as "FALSE" or "TRUE".
	 * Numbers are returned in their PHP usual notation, possibly scientific form.
	 * Date with time is returned in ISO 8601 format.
	 * Time is returned in HH:MM:SS format.
	 * Currency is returned as string "$ 12.34", where "$" is the specific currency
	 * name and "12.34" is the value.
	 * @return string
	 */
	public function __toString() {
		try {
			return $this->toString();
		}
		catch(PHPExcelException $e){
			logCapturedException($e);
			return '#VALUE!'; // FIXME:
		}
	}
	

	/**
	 * Create a new empty cell of type null.
	 *
	 * @param Worksheet $worksheet
	 * @param Coordinates $coordinates
	 */
	public function __construct($worksheet, $coordinates) {
		$this->worksheet = $worksheet;
		$this->coordinates = $coordinates;
		$this->value = NULL;
		$this->dataType = Cell::TYPE_NULL;
	}

}
