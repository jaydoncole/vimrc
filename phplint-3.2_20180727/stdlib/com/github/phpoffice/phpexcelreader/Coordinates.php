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
 * @version $Date: 2018/07/03 06:23:13 $
 */

namespace com\github\phpoffice\phpexcelreader;

require_once __DIR__ . "/../../../../all.php";
/*. require_module 'pcre'; .*/

use it\icosaedro\containers\Printable;
use OutOfRangeException;

/**
 * Holds the coordinates of a cell, and also provides some useful functions to
 * manipulate cell coordinates. Columns and rows are numbered starting from 1.
 * Columns may also be indicated with a name in the usual way, with 'A' corresponding
 * to the column number 1.
 */
class Coordinates implements Printable {
	
	private $columnNumber = 1;
	private $columnName = 'A';
	private $row = 1;
	private $display = 'A1';
	
	
	/**
	 * 
	 * @return string
	 */
	public function __toString() {
		return $this->display;
	}
	
	/**
	 * Returns the column number.
	 * @return int Number in [1,18278].
	 */
	public function getColumnNumber() {
		return $this->columnNumber;
	}
	
	
	/**
	 * Returns the column name.
	 * @return string COlumn name from 'A' up to 'ZZZ'.
	 */
	public function getColumnName() {
		return $this->columnName;
	}
	
	
	/**
	 * Returns the row number.
	 * @return int Number &ge; 1.
	 */
	public function getRow() {
		return $this->row;
	}
	

	/**
	 * Returns the column name given its number.
	 *
	 * @param int $number Column number in the range from 1 up to 18278.
	 * @return string Column name ranging from 'A' up to 'ZZZ'.
	 * @throws \OutOfRangeException
	 */
	public static function columnName($number) {
		if( !( 1<= $number && $number <= 18278) )
			throw new \OutOfRangeException("invalid column number: $number");
		$number--;
		// Using a lookup cache adds a slight memory overhead, but boosts speed
		// caching using a static within the method is faster than a class static,
		// though it's additional memory overhead
		static $_indexCache = /*. (string[int]) .*/ array();

		if (!isset($_indexCache[$number])) {
			if ($number < 26) {
				$_indexCache[$number] = chr(65 + $number);
			} elseif ($number < 702) {
				$_indexCache[$number] = chr(64 + (int) ($number / 26)) .
						chr(65 + $number % 26);
			} else {
				$_indexCache[$number] = chr(64 + (int) (($number - 26) / 676)) .
						chr(65 + (int) ((($number - 26) % 676) / 26)) .
						chr(65 + $number % 26);
			}
		}
		return $_indexCache[$number];
	}
	

	/**
	 * Converts the column name into number.
	 *
	 * @param string $name Column name, ranging from 'A' up to 'ZZZ'. All the letters
	 * must be upper-case.
	 * @return int Column index ranging from 1 up to 18278.
	 * @throws \InvalidArgumentException Invalid column name.
	 */
	public static function columnNumber($name) {
		if( preg_match("/^[A-Z]{1,3}\$/sD", $name) != 1 )
			throw new \InvalidArgumentException("not a column name: $name");
		$l = strlen($name);
		if ($l <= 1) {
			return ord($name) - 64;
		} elseif ($l <= 2) {
			return 26*(ord($name[0]) - 64) + ord($name[1]) - 64;
		} else {
			return 676*(ord($name[0]) - 64) + 26*(ord($name[1]) - 64) + ord($name[2]) - 64;
		}
	}
	
	
	/**
	 * @param int $col Column number, starting from 1.
	 * @param int $row Row number, starting from 1.
	 * @throws OutOfRangeException Column or row number less than 1.
	 */
	private function __construct($col, $row) {
		if( !(1 <= $col && $col <= 18278 && 1 <= $row) )
			throw new OutOfRangeException("cell coordinates out of range: col=$col, row=$row");
		$this->columnNumber = $col;
		$this->columnName = self::columnName($col);
		$this->row = $row;
		$this->display = $this->columnName . $row;
	}
	
	
	/**
	 * Parses an absolute cell address.
	 * @param string $coordinates Cell address ranging from "A1" up to "ZZZ9999999".
	 * Only upper-case letters allowed.
	 * @return self
	 * @throws PHPExcelException
	 */
	public static function parse($coordinates) {
		if (1 != preg_match("/^([A-Z]{1,3})(\\d{1,7})$/sD", $coordinates, $matches))
			throw new PHPExcelException('Invalid cell coordinate ' . $coordinates);
		$col = self::columnNumber((string)$matches[1]);
		$row = (int) $matches[2];
		return new self($col, $row);
	}
	
	
	/**
	 * Creates a cell address for given column number and row number.
	 * @param int $col Column number in [1,18278].
	 * @param int $row Row number, starting from 1.
	 */
	public static function create($col, $row) {
		return new self($col, $row);
	}
	
}