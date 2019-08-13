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
 * @version    $Date: 2018/07/03 06:23:38 $
 */

namespace com\github\phpoffice\phpexcelreader;

require_once __DIR__ . "/../../../../all.php";

/*. require_module 'pcre'; require_module 'ctype'; .*/

class ReferenceHelper {
	/*    Constants                */
	/*    Regular Expressions      */

	const REFHELPER_REGEXP_CELLREF = '((\\w*|\'[^!]*\')!)?(?<![:a-z\\$])(\\$?[a-z]{1,3}\\$?\\d+)(?=[^:!\\d\'])';
	const REFHELPER_REGEXP_CELLRANGE = '((\\w*|\'[^!]*\')!)?(\\$?[a-z]{1,3}\\$?\\d+):(\\$?[a-z]{1,3}\\$?\\d+)';
	const REFHELPER_REGEXP_ROWRANGE = '((\\w*|\'[^!]*\')!)?(\\$?\\d+):(\\$?\\d+)';
	const REFHELPER_REGEXP_COLRANGE = '((\\w*|\'[^!]*\')!)?(\\$?[a-z]{1,3}):(\\$?[a-z]{1,3})';

	/**
	 * Instance of this class
	 *
	 * @var ReferenceHelper
	 */
	private static $instance;

	/**
	 * Singleton.
	 */
	private function __construct() {
		
	}

	/**
	 * Get an instance of this class
	 *
	 * @return ReferenceHelper
	 */
	public static function getInstance() {
		if (!isset(self::$instance) || (self::$instance === null)) {
			self::$instance = new ReferenceHelper();
		}

		return self::$instance;
	}

	/**
	 * Parse the absolute or relative cell address and returns the result split
	 * into column and row. For example the absolute address "A1" translates into
	 * array("A", "1"), while the relative address "$A$1" translates into array("$A", "$1").
	 * Range of addresses is not allowed.
	 *
	 * @param string $pCoordinateString
	 * @return string[int] Array containing column and row (indexes 0 and 1 respectively).
	 * @throws PHPExcelException
	 */
	private static function coordinateFromString($pCoordinateString) {
		if (1 == preg_match("/^([$]?[A-Z]{1,3})([$]?\\d{1,7})$/sD", $pCoordinateString, $matches))
			return array((string) $matches[1], (string) $matches[2]);
		else
			throw new PHPExcelException('Invalid cell coordinate ' . $pCoordinateString);
	}
	
	
	/**
	 * Returns the coordinate from string. For example "A1" translates into
	 * array("A", "1"), while "$A$1" translates to array("$A", "$1").
	 * 
	 * @param string $coordinate
	 * @param string & $col The column part. May contain a leading '$' sign.
	 * @param string & $row The row part. May contain a leading '$' sign.
	 * @return void
	 * @throws PHPExcelException
	 */
	private static function getCoordinateFromString($coordinate, /*. return .*/ & $col, /*. return .*/ & $row) {
		$a = self::coordinateFromString($coordinate);
		$col = $a[0];
		$row = $a[1];
	}

	/**
	 * Update single cell reference
	 *
	 * @param    string    $pCellReference        Single cell reference
	 * @param    string    $pBefore            Insert before this one
	 * @param    int        $pNumCols            Number of columns to increment
	 * @param    int        $pNumRows            Number of rows to increment
	 * @return    string    Updated cell reference
	 * @throws    PHPExcelException
	 */
	private function updateSingleCellReference($pCellReference, $pBefore, $pNumCols, $pNumRows) {
		if (strpos($pCellReference, ':') === false && strpos($pCellReference, ',') === false) {
			// Get coordinates of $pBefore
			self::getCoordinateFromString($pBefore, $beforeColumn, $beforeRow);

			// Get coordinates of $pCellReference
			self::getCoordinateFromString($pCellReference, $newColumn, $newRow);

			// Verify which parts should be updated
			$updateColumn = (($newColumn[0] !== '$') && ($beforeColumn[0] !== '$') && (Coordinates::columnNumber($newColumn) >= Coordinates::columnNumber($beforeColumn)));
			$updateRow = (($newRow[0] !== '$') && ($beforeRow[0] !== '$') && (int)$newRow >= (int)$beforeRow);

			// Create new column reference
			if ($updateColumn) {
				$newColumn = Coordinates::columnName(Coordinates::columnNumber($newColumn) + $pNumCols);
			}

			// Create new row reference
			if ($updateRow) {
				$newRow = "" . ((int)$newRow + $pNumRows);
			}

			// Return new reference
			return $newColumn . $newRow;
		} else {
			throw new PHPExcelException("Only single cell references may be passed to this method.");
		}
	}

    /**
     * Update cell range
     *
     * @param    string    $pCellRange            Cell range    (e.g. 'B2:D4', 'B:C' or '2:3')
     * @param    string     $pBefore            Insert before this one
     * @param    int        $pNumCols            Number of columns to increment
     * @param    int        $pNumRows            Number of rows to increment
     * @return    string    Updated cell range
     * @throws    PHPExcelException
     */
    private function updateCellRange($pCellRange, $pBefore, $pNumCols, $pNumRows)
    {
        if (strpos($pCellRange, ':') !== false || strpos($pCellRange, ',') !== false) {
            // Update range
            $range = Cell::splitRange($pCellRange);
            $ic = count($range);
            for ($i = 0; $i < $ic; ++$i) {
                $jc = count($range[$i]);
                for ($j = 0; $j < $jc; ++$j) {
                    if (ctype_alpha($range[$i][$j])) {
                        $r = self::coordinateFromString($this->updateSingleCellReference($range[$i][$j].'1', $pBefore, $pNumCols, $pNumRows));
                        $range[$i][$j] = $r[0];
                    } elseif (ctype_digit($range[$i][$j])) {
                        $r = self::coordinateFromString($this->updateSingleCellReference('A'.$range[$i][$j], $pBefore, $pNumCols, $pNumRows));
                        $range[$i][$j] = $r[1];
                    } else {
                        $range[$i][$j] = $this->updateSingleCellReference($range[$i][$j], $pBefore, $pNumCols, $pNumRows);
                    }
                }
            }

            // Recreate range string
            return Cell::buildRange($range);
        } else {
            throw new PHPExcelException("Only cell ranges may be passed to this method.");
        }
    }

	/**
	 * Update cell reference
	 *
	 * @param    string    $pCellRange            Cell range
	 * @param    string     $pBefore            Insert before this one
	 * @param    int        $pNumCols            Number of columns to increment
	 * @param    int        $pNumRows            Number of rows to increment
	 * @return    string    Updated cell range
	 * @throws    PHPExcelException
	 */
	private function updateCellReference($pCellRange, $pBefore, $pNumCols, $pNumRows) {
		// Is it in another worksheet? Will not have to update anything.
		if (strpos($pCellRange, "!") !== false) {
			return $pCellRange;
			// Is it a range or a single cell?
		} elseif (strpos($pCellRange, ':') === false && strpos($pCellRange, ',') === false) {
			// Single cell
			return $this->updateSingleCellReference($pCellRange, $pBefore, $pNumCols, $pNumRows);
		} elseif (strpos($pCellRange, ':') !== false || strpos($pCellRange, ',') !== false) {
			// Range
			return $this->updateCellRange($pCellRange, $pBefore, $pNumCols, $pNumRows);
		} else {
			// Return original
			return $pCellRange;
		}
	}

	/**
	 * Update references within formulas
	 *
	 * @param    string    $pFormula    Formula to update
	 * @param    string     $pBefore    Insert before this one
	 * @param    int        $pNumCols    Number of columns to insert
	 * @param    int        $pNumRows    Number of rows to insert
	 * @param   string  $sheetName  Worksheet name/title
	 * @return    string    Updated formula
	 * @throws    PHPExcelException
	 */
	public function updateFormulaReferences($pFormula, $pBefore, $pNumCols, $pNumRows, $sheetName = '') {
		//    Update cell references in the formula
		$formulaBlocks = explode('"', $pFormula);
		$i = false;
		foreach ($formulaBlocks as &$formulaBlock) {
			//    Ignore blocks that were enclosed in quotes (alternating entries in the $formulaBlocks array after the explode)
			if ($i = !$i) {
				$adjustCount = 0;
				$newCellTokens = $cellTokens = /*.(string[string]).*/ array();
				//    Search for row ranges (e.g. 'Sheet1'!3:5 or 3:5) with or without $ absolutes (e.g. $3:5)
				$matchCount = preg_match_all('/' . self::REFHELPER_REGEXP_ROWRANGE . '/i', ' ' . $formulaBlock . ' ', $matches, PREG_SET_ORDER);
				if ($matchCount > 0) {
					foreach ($matches as $match) {
						$fromString = (strlen($match[2]) > 0) ? $match[2] . '!' : '';
						$fromString .= $match[3] . ':' . $match[4];
						$modified3 = substr($this->updateCellReference('$A' . $match[3], $pBefore, $pNumCols, $pNumRows), 2);
						$modified4 = substr($this->updateCellReference('$A' . $match[4], $pBefore, $pNumCols, $pNumRows), 2);

						if ($match[3] . ':' . $match[4] !== $modified3 . ':' . $modified4) {
							if ((strlen($match[2]) == 0) || (trim($match[2], "'") === $sheetName)) {
								$toString = (strlen($match[2]) > 0) ? $match[2] . '!' : '';
								$toString .= $modified3 . ':' . $modified4;
								//    Max worksheet size is 1,048,576 rows by 16,384 columns in Excel 2007, so our adjustments need to be at least one digit more
								$column = 100000;
								$row = 10000000 + (int) trim($match[3], '$');
								$cellIndex = $column . $row;

								$newCellTokens[$cellIndex] = preg_quote($toString);
								$cellTokens[$cellIndex] = '/(?<!\\d\\$\\!)' . preg_quote($fromString) . '(?!\\d)/i';
								++$adjustCount;
							}
						}
					}
				}
				//    Search for column ranges (e.g. 'Sheet1'!C:E or C:E) with or without $ absolutes (e.g. $C:E)
				$matchCount = preg_match_all('/' . self::REFHELPER_REGEXP_COLRANGE . '/i', ' ' . $formulaBlock . ' ', $matches, PREG_SET_ORDER);
				if ($matchCount > 0) {
					foreach ($matches as $match) {
						$fromString = (strlen($match[2]) > 0) ? $match[2] . '!' : '';
						$fromString .= $match[3] . ':' . $match[4];
						$modified3 = substr($this->updateCellReference($match[3] . '$1', $pBefore, $pNumCols, $pNumRows), 0, -2);
						$modified4 = substr($this->updateCellReference($match[4] . '$1', $pBefore, $pNumCols, $pNumRows), 0, -2);

						if ($match[3] . ':' . $match[4] !== $modified3 . ':' . $modified4) {
							if ((strlen($match[2]) == 0) || (trim($match[2], "'") === $sheetName)) {
								$toString = (strlen($match[2]) > 0) ? $match[2] . '!' : '';
								$toString .= $modified3 . ':' . $modified4;
								//    Max worksheet size is 1,048,576 rows by 16,384 columns in Excel 2007, so our adjustments need to be at least one digit more
								$column = Coordinates::columnNumber(trim($match[3], '$')) + 100000;
								$row = 10000000;
								$cellIndex = $column . $row;

								$newCellTokens[$cellIndex] = preg_quote($toString);
								$cellTokens[$cellIndex] = '/(?<![A-Z\\$\\!])' . preg_quote($fromString) . '(?![A-Z])/i';
								++$adjustCount;
							}
						}
					}
				}
				//    Search for cell ranges (e.g. 'Sheet1'!A3:C5 or A3:C5) with or without $ absolutes (e.g. $A1:C$5)
				$matchCount = preg_match_all('/' . self::REFHELPER_REGEXP_CELLRANGE . '/i', ' ' . $formulaBlock . ' ', $matches, PREG_SET_ORDER);
				if ($matchCount > 0) {
					foreach ($matches as $match) {
						$fromString = (strlen($match[2]) > 0) ? $match[2] . '!' : '';
						$fromString .= $match[3] . ':' . $match[4];
						$modified3 = $this->updateCellReference($match[3], $pBefore, $pNumCols, $pNumRows);
						$modified4 = $this->updateCellReference($match[4], $pBefore, $pNumCols, $pNumRows);

						if ($match[3] . $match[4] !== $modified3 . $modified4) {
							if ((strlen($match[2]) == 0) || (trim($match[2], "'") === $sheetName)) {
								$toString = (strlen($match[2]) > 0) ? $match[2] . '!' : '';
								$toString .= $modified3 . ':' . $modified4;
								$a = self::coordinateFromString($match[3]);
								$column_s = $a[0];
								$row_s = $a[1];
								//    Max worksheet size is 1,048,576 rows by 16,384 columns in Excel 2007, so our adjustments need to be at least one digit more
								$column = Coordinates::columnNumber(trim($column_s, '$')) + 100000;
								$row = (int) trim($row_s, '$') + 10000000;
								$cellIndex = $column . $row;

								$newCellTokens[$cellIndex] = preg_quote($toString);
								$cellTokens[$cellIndex] = '/(?<![A-Z]\\$\\!)' . preg_quote($fromString) . '(?!\\d)/i';
								++$adjustCount;
							}
						}
					}
				}
				//    Search for cell references (e.g. 'Sheet1'!A3 or C5) with or without $ absolutes (e.g. $A1 or C$5)
				$matchCount = preg_match_all('/' . self::REFHELPER_REGEXP_CELLREF . '/i', ' ' . $formulaBlock . ' ', $matches, PREG_SET_ORDER);

				if ($matchCount > 0) {
					foreach ($matches as $match) {
						$fromString = (strlen($match[2]) > 0) ? $match[2] . '!' : '';
						$fromString .= $match[3];

						$modified3 = $this->updateCellReference($match[3], $pBefore, $pNumCols, $pNumRows);
						if ($match[3] !== $modified3) {
							if ((strlen($match[2]) == 0) || (trim($match[2], "'") === $sheetName)) {
								$toString = (strlen($match[2]) > 0) ? $match[2] . '!' : '';
								$toString .= $modified3;
								$a = self::coordinateFromString($match[3]);
								$column_s = $a[0];
								$row_s = $a[1];
								//    Max worksheet size is 1,048,576 rows by 16,384 columns in Excel 2007, so our adjustments need to be at least one digit more
								$column = Coordinates::columnNumber(trim($column_s, '$')) + 100000;
								$row = (int) trim($row_s, '$') + 10000000;
								$cellIndex = $row . $column;

								$newCellTokens[$cellIndex] = preg_quote($toString);
								$cellTokens[$cellIndex] = '/(?<![A-Z\\$\\!])' . preg_quote($fromString) . '(?!\\d)/i';
								++$adjustCount;
							}
						}
					}
				}
				if ($adjustCount > 0) {
					if ($pNumCols > 0 || $pNumRows > 0) {
						krsort($cellTokens);
						krsort($newCellTokens);
					} else {
						ksort($cellTokens);
						ksort($newCellTokens);
					}   //  Update cell references in the formula
					$formulaBlock = (string) str_replace('\\', '', preg_replace($cellTokens, $newCellTokens, $formulaBlock));
				}
			}
		}

		//    Then rebuild the formula string
		return implode('"', $formulaBlocks);
	}

//    /**
//     * Update named formulas (i.e. containing worksheet references / named ranges)
//     *
//     * @param PHPExcel $pPhpExcel    Object to update
//     * @param string $oldName        Old name (name to replace)
//     * @param string $newName        New name
//     */
//    public function updateNamedFormulas($pPhpExcel, $oldName = '', $newName = '')
//    {
//        if ($oldName === '') {
//            return;
//        }
//
//        foreach ($pPhpExcel->getWorksheetIterator() as $sheet) {
//            foreach ($sheet->getCellCollection(false) as $cellID) {
//                $cell = $sheet->getCell($cellID);
//                if (($cell !== null) && ($cell->getDataType() == Cell::TYPE_FORMULA)) {
//                    $formula = $cell->getValue();
//                    if (strpos($formula, $oldName) !== false) {
//                        $formula = str_replace("'" . $oldName . "'!", "'" . $newName . "'!", $formula);
//                        $formula = str_replace($oldName . "!", $newName . "!", $formula);
//                        $cell->setValueExplicit($formula, Cell::TYPE_FORMULA);
//                    }
//                }
//            }
//        }
//    }

	/**
	 * __clone implementation. Cloning should not be allowed in a Singleton!
	 *
	 * @throws    PHPExcelException
	 */
	final public function __clone() {
		throw new PHPExcelException("Cloning a Singleton is not allowed!");
	}

}
