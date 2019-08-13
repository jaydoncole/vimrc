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
 * @version    $Date: 2016/02/21 23:00:55 $
 */

namespace com\github\phpoffice\phpexcelreader;

require_once __DIR__ . "/../../../../all.php";

/**
 * Holds a collection of cells in a rectangular grid.
 */
class Worksheet implements \it\icosaedro\containers\Printable
{

	/* Sheet state */
	const SHEETSTATE_VISIBLE = 'visible';
	const SHEETSTATE_HIDDEN = 'hidden';
	const SHEETSTATE_VERYHIDDEN = 'veryHidden';

	/**
	 * Invalid characters in sheet title
	 *
	 * @var string[int]
	 */
	private static $invalidCharacters = array('*', ':', '/', '\\', '?', '[', ']');

	/**
	 * Parent spreadsheet
	 *
	 * @var Workbook
	 */
	private $workbook;

	/**
	 * CodeName
	 *
	 * @var string
	 */
	private $codeName = null;

	/**
	 * Worksheet title
	 *
	 * @var string
	 */
	private $title;

	/**
	 * Sheet state
	 *
	 * @var string
	 */
	private $sheetState;

	/**
	 * Active cell. (Only one!)
	 *
	 * @var Coordinates
	 */
	private $activeCell;

	/**
	 * Selected cells
	 *
	 * @var string
	 */
	private $selectedCells = 'A1';

	/**
	 * Cached highest column
	 *
	 * @var string
	 */
	private $cachedHighestColumn = 'A';

	/**
	 * Cached highest row
	 *
	 * @var int
	 */
	private $cachedHighestRow = 1;

	/**
	 * An array of cells for the worksheet cells held in this cache,
	 * and indexed by their coordinate address within the worksheet.
	 *
	 * @var Cell[string]
	 */
	private $cellCache = array();
	
	
	
	/*.

	forward public void function __construct(Workbook $pParent, string $pTitle =) throws PHPExcelException;
	forward public Workbook function getWorkbook();
	forward public string  function getTitle();
	forward public string  function getSelectedCells();
	forward public Coordinates function getActiveCell();
	forward public string  function getCodeName();
	forward public string  function getHighestColumnName(int $row =);
	forward public int     function getHighestColumnNumber(int $row =);
	forward public int     function getHighestRow(string $column =);
	forward public boolean function cellExists(Coordinates $pCoordinate);
	forward public Style   function getStyleOfRange(string $pCellCoordinate) throws PHPExcelException;
	forward public Style   function getStyle(Coordinates $pCellCoordinate) throws PHPExcelException;
	forward public Cell    function getCellByColumnAndRow(int $pColumn, int $pRow);
	forward public Cell    function getCell(Coordinates $pCoordinate);
	forward public void    function setSheetState(string $value);
	forward public void    function setTitle(string $pValue, boolean $updateFormulaCellReferences =) throws PHPExcelException;
	forward public void    function setCodeName(string $pValue) throws PHPExcelException;
	forward public string  function calculateWorksheetDataDimension();
	
	pragma 'suspend';

	.*/
	

	/**
	 * Add or Update a cell in cache identified by coordinate address.
	 *
	 * @param string $pCoord Coordinate address of the cell to update.
	 * @param Cell $cell Cell to update.
	 * @return void
	 */
	private function addCacheData($pCoord, $cell) {
		$this->cellCache[$pCoord] = $cell;
	}

	/**
	 * Get cell at a specific coordinate.
	 *
	 * @param Coordinates $pCoord Coordinate of the cell.
	 * @return Cell Cell that was found, or null if not found.
	 */
	private function getCacheData($pCoord) {
		if (!isset($this->cellCache[$pCoord->__toString()])) {
			return null;
		}
		return $this->cellCache[$pCoord->__toString()];
	}

	/**
	 * Get a list of all cell addresses currently held in cache.
	 *
	 * @return string[int]
	 */
	public function getCellList() {
		return cast("string[int]", array_keys($this->cellCache));
	}

	/**
	 * Sort the list of all cell addresses currently held in cache by row and column.
	 *
	 * @return string[int]
	 */
	public function getSortedCellList() {
		$sortKeys = /*.(string[string]).*/ array();
		foreach ($this->getCellList() as $coord) {
			$column = "";
			$row = 0;
			sscanf($coord, '%[A-Z]%d', $column, $row);
			$sortKeys[sprintf('%09d%3s', $row, $column)] = $coord;
		}
		ksort($sortKeys);

		return cast("string[int]", array_values($sortKeys));
	}

	/**
	 * Get highest worksheet column and highest row that have cell records.
	 *
	 * @return string[string] Highest column name ('row' key) and highest row number
	 * ('column' key).
	 */
	public function getHighestRowAndColumn() {
		$highestRow = 1;
		$highestColumn = '1A';
		foreach ($this->getCellList() as $coord) {
			$c = "";
			$r = 0;
			sscanf($coord, '%[A-Z]%d', $c, $r);
			$c = strlen($c) . $c;
			if ($r > $highestRow)
				$highestRow = $r;
			if (strcmp($c, $highestColumn) > 0)
				$highestColumn = $c;
		}
		return array(
			'row' => "$highestRow",
			'column' => substr($highestColumn, 1)
		);
	}

	/**
	 * Get active cell
	 *
	 * @return Coordinates
	 */
	public function getActiveCell() {
		return $this->activeCell;
	}

	/**
	 * Get selected cells
	 *
	 * @return string
	 */
	public function getSelectedCells() {
		return $this->selectedCells;
	}

	/**
	 * Select a range of cells.
	 *
	 * @param string $pCoordinate   Cell range, examples: 'A1', 'B2:G5', 'A:C', '3:6'.
	 * @throws PHPExcelException
	 * @return void
	 */
	private function setSelectedCells($pCoordinate) {
		// Uppercase coordinate
		$pCoordinate = strtoupper($pCoordinate);

		// Convert 'A' to 'A:A'
		$pCoordinate = preg_replace('/^([A-Z]+)$/', '${1}:${1}', $pCoordinate);

		// Convert '1' to '1:1'
		$pCoordinate = preg_replace('/^([0-9]+)$/', '${1}:${1}', $pCoordinate);

		// Convert 'A:C' to 'A1:C1048576'
		$pCoordinate = preg_replace('/^([A-Z]+):([A-Z]+)$/', '${1}1:${2}1048576', $pCoordinate);

		// Convert '1:3' to 'A1:XFD3'
		$pCoordinate = preg_replace('/^([0-9]+):([0-9]+)$/', 'A${1}:XFD${2}', $pCoordinate);

		if (strpos($pCoordinate, ':') !== false || strpos($pCoordinate, ',') !== false) {
			$a = Cell::splitRange($pCoordinate);
			$first = $a[0];
			$this->activeCell = Coordinates::parse($first[0]);
		} else {
			$this->activeCell = Coordinates::parse($pCoordinate);
		}
		$this->selectedCells = $pCoordinate;
	}

	/**
	 * Check sheet code name for valid Excel syntax
	 *
	 * @param string $pValue The string to check
	 * @return string The valid string
	 * @throws PHPExcelException Invalid sheet code name.
	 */
	private static function checkSheetCodeName($pValue) {
		$CharCount = SharedString::CountCharacters($pValue);
		if ($CharCount == 0) {
			throw new PHPExcelException('Sheet code name cannot be empty.');
		}
		// Some of the printable ASCII characters are invalid:  * : / \ ? [ ] and  first and last characters cannot be a "'"
		if ((str_replace(self::$invalidCharacters, '', $pValue) !== $pValue) ||
				(SharedString::Substring($pValue, -1, 1) === '\'') ||
				(SharedString::Substring($pValue, 0, 1) === '\'')) {
			throw new PHPExcelException('Invalid character found in sheet code name');
		}

		// Maximum 31 characters allowed for sheet title
		if ($CharCount > 31) {
			throw new PHPExcelException('Maximum 31 characters allowed in sheet code name.');
		}

		return $pValue;
	}

	/**
	 * Check sheet title for valid Excel syntax
	 *
	 * @param string $pValue The string to check
	 * @return string The valid string
	 * @throws PHPExcelException
	 */
	private static function checkSheetTitle($pValue) {
		// Some of the printable ASCII characters are invalid:  * : / \ ? [ ]
		if (str_replace(self::$invalidCharacters, '', $pValue) !== $pValue) {
			throw new PHPExcelException('Invalid character found in sheet title');
		}

		// Maximum 31 characters allowed for sheet title
		if (SharedString::CountCharacters($pValue) > 31) {
			throw new PHPExcelException('Maximum 31 characters allowed in sheet title.');
		}

		return $pValue;
	}

	/**
	 * Get workbook
	 *
	 * @return Workbook
	 */
	public function getWorkbook() {
		return $this->workbook;
	}

	/**
	 * Get title
	 *
	 * @return string
	 */
	public function getTitle() {
		return $this->title;
	}

	/**
	 * Set title.
	 *
	 * @param string $pValue String containing the dimension of this worksheet.
	 * @param boolean $updateFormulaCellReferences Whether cell references in formulae
	 * should be updated to reflect the new sheet name. This should be left as the
	 * default true, unless you are certain that no formula cells on any worksheet
	 * contain references to this worksheet.
	 * @return void
	 * @throws PHPExcelException Invalid title.
	 */
	public function setTitle($pValue, $updateFormulaCellReferences = true) {
		// Is this a 'rename' or not?
		if ($this->getTitle() === $pValue) {
			return;
		}

		// Syntax check
		self::checkSheetTitle($pValue);

//		// Old title
//		$oldTitle = $this->getTitle();

		if ($this->workbook !== NULL) {
			// Is there already such sheet name?
			if ($this->workbook->sheetNameExists($pValue)) {
				// Use name, but append with lowest possible integer

				if (SharedString::CountCharacters($pValue) > 29) {
					$pValue = SharedString::Substring($pValue, 0, 29);
				}
				$i = 1;
				while ($this->workbook->sheetNameExists($pValue . ' ' . $i)) {
					++$i;
					if ($i == 10) {
						if (SharedString::CountCharacters($pValue) > 28) {
							$pValue = SharedString::Substring($pValue, 0, 28);
						}
					} elseif ($i == 100) {
						if (SharedString::CountCharacters($pValue) > 27) {
							$pValue = SharedString::Substring($pValue, 0, 27);
						}
					}
				}

				$altTitle = $pValue . ' ' . $i;
				$this->setTitle($altTitle, $updateFormulaCellReferences);
			}
		}

		// Set title
		$this->title = $pValue;
//		$this->dirty = true;

//		if ($this->workbook !== NULL) {
//			// New title
//			$newTitle = $this->getTitle();
//			Calculation::getInstance($this->parent_)
//					->renameCalculationCacheForWorksheet($oldTitle, $newTitle);
//            if ($updateFormulaCellReferences) {
//                ReferenceHelper::getInstance()->updateNamedFormulas($this->parent_, $oldTitle, $newTitle);
//            }
//		}
	}

	/**
	 * Get sheet state
	 *
	 * @return string Sheet state (visible, hidden, veryHidden)
	 */
	public function getSheetState() {
		return $this->sheetState;
	}

	/**
	 * Set sheet state
	 *
	 * @param string $value Sheet state (visible, hidden, veryHidden)
	 * @return void
	 */
	public function setSheetState($value) {
		$this->sheetState = $value;
	}

	/**
	 * Get highest worksheet column that contains data. The "smallest" column is
	 * always assumed to be 'A'.
	 *
	 * @param int $row Return the highest data column for the specified row,
	 * or the highest data column of any row if no row number is passed.
	 * @return string Highest column name that contains data
	 */
	public function getHighestDataColumnName($row = -1) {
		$hi = 1;
		foreach($this->cellCache as $cell){
			$cellCoords = $cell->getCoordinate();
			if ($row >= 1 && $cellCoords->getRow() != $row)
				continue;
			if ($cellCoords->getColumnNumber() > $hi)
				$hi = $cellCoords->getColumnNumber();
		}
		return Coordinates::columnName($hi);
	}

	/**
	 * Get highest worksheet column. The "smallest" column is always assumed to
	 * be 'A'.
	 *
	 * @param int $row Return the data highest column for the specified row,
	 * or the highest column of any row if no row number is passed.
	 * @return string Highest column name.
	 */
	public function getHighestColumnName($row = -1) {
		if ($row == -1) {
			return $this->cachedHighestColumn;
		}
		return $this->getHighestDataColumnName($row);
	}

	/**
	 * Get highest worksheet column. The "smallest" column is always assumed to
	 * be 1.
	 *
	 * @param int $row Return the data highest column for the specified row,
	 * or the highest column of any row if no row number is passed.
	 * @return int Highest column number.
	 */
	public function getHighestColumnNumber($row = -1) {
		return Coordinates::columnNumber($this->getHighestColumnName($row));
	}

	/**
	 * Get highest worksheet row that contains data. The "smallest" row is always
	 * assumed to be 1.
	 *
	 * @param string $column Return the highest data row for the specified column,
	 * or the highest data row of any column if no column letter is passed.
	 * @return int Highest row number that contains data.
	 */
	public function getHighestDataRow($column = null) {
		$hi = 1;
		foreach($this->cellCache as $cell){
			$cellCoords = $cell->getCoordinate();
			if ($column !== null && $cellCoords->getColumnName() !== $column)
				continue;
			if ($cellCoords->getRow() > $hi)
				$hi = $cellCoords->getRow();
		}
		return $hi;
	}

	/**
	 * Get highest worksheet row. The "smallest" row is always assumed to be 1.
	 *
	 * @param string $column Return the highest data row for the specified column,
	 * or the highest row of any column if no column letter is passed.
	 * @return int Highest row number.
	 */
	public function getHighestRow($column = null) {
		if ($column === null) {
			return $this->cachedHighestRow;
		}
		return $this->getHighestDataRow($column);
	}

	/**
	 * Calculate worksheet data dimension.
	 *
	 * @return string String containing the dimension of this worksheet that actually
	 * contain data.
	 */
	public function calculateWorksheetDataDimension() {
		return 'A1' . ':' . $this->getHighestDataColumnName() . $this->getHighestDataRow();
	}

	/**
	 * Create a new cell at the specified coordinate.
	 *
	 * @param Coordinates $coordinate Coordinate of the cell.
	 * @return Cell Cell that was created.
	 */
	private function createNewCell($coordinate) {
		$cell = new Cell($this, $coordinate);
		$this->addCacheData($coordinate->__toString(), $cell);
		if (Coordinates::columnNumber($this->cachedHighestColumn) < $coordinate->getColumnNumber()) {
			$this->cachedHighestColumn = $coordinate->getColumnName();
		}
		$this->cachedHighestRow = (int) max($this->cachedHighestRow, $coordinate->getRow());
		return $cell;
	}

	/**
	 * Get cell at a specific coordinate by using numeric cell coordinates. If the
	 * cell does not yet exist, a new, empty cell is created.
	 *
	 * @param int $columnNumber Numeric column coordinate of the cell, starting from 1.
	 * @param int $row Numeric row coordinate of the cell, starting from 1.
	 * @return Cell Cell that was found or new cell created.
	 */
	public function getCellByColumnAndRow($columnNumber, $row) {
		$cellCoords = Coordinates::create($columnNumber, $row);
		$cell = $this->getCacheData($cellCoords);
		if( $cell !== NULL )
			return $cell;
		else
			return $this->createNewCell($cellCoords);
	}

	/**
	 * Get cell at a specific coordinate.
	 *
	 * @param Coordinates $pCoordinate Coordinate of the cell
	 * @return Cell Cell that was found, or new empty cell if it does not already
	 * exists.
	 */
	public function getCell($pCoordinate) {
		$cell = $this->getCacheData($pCoordinate);
		if( $cell !== NULL )
			return $cell;

//		// Worksheet reference?
//		if (strpos($pCoordinate, '!') !== false) {
//			$worksheetReference = Worksheet::extractSheetTitle($pCoordinate, true);
//			return $this->parent_->getSheetByName($worksheetReference[0])->getCell(strtoupper($worksheetReference[1]));
//		}
//
//		// Named range?
//		if ((1 != preg_match('/^' . Calculation::CALCULATION_REGEXP_CELLREF . '$/i', $pCoordinate, $matches)) &&
//				(1 == preg_match('/^' . Calculation::CALCULATION_REGEXP_NAMEDRANGE . '$/i', $pCoordinate, $matches))) {
//			$namedRange = PHPExcel_NamedRange::resolveRange($pCoordinate, $this);
//			if ($namedRange !== null) {
//				$pCoordinate = $namedRange->getRange();
//				return $namedRange->getWorksheet()->getCell($pCoordinate);
//			}
//		}
//		// Uppercase coordinate
//		$pCoordinate = strtoupper($pCoordinate);
//
//		if (strpos($pCoordinate, ':') !== false || strpos($pCoordinate, ',') !== false) {
//			throw new PHPExcelException('Cell coordinate can not be a range of cells.');
//		} elseif (strpos($pCoordinate, '$') !== false) {
//			throw new PHPExcelException('Cell coordinate must be absolute.');
//		}

		// Create new cell object
		return $this->createNewCell($pCoordinate);
	}

	/**
	 * Does the cell at a specific coordinate exist?
	 *
	 * @param Coordinates $pCoordinate  Coordinate of the cell
	 * @return boolean
	 */
	public function cellExists($pCoordinate) {
		return $this->getCell($pCoordinate) !== NULL;
	}

//	/**
//	 * Cell at a specific coordinate by using numeric cell coordinates exists?
//	 *
//	 * @param int $pColumn Numeric column coordinate of the cell (0-based).
//	 * @param int $pRow Numeric row coordinate of the cell (1-based).
//	 * @return boolean
//	 */
//	public function cellExistsByColumnAndRow($pColumn, $pRow) {
//		return $this->cellExists(Cell::stringFromColumnIndex($pColumn) . $pRow);
//	}

	/**
	 * Get style for a range of cells.
	 *
	 * @param string $pCellCoordinate Cell coordinate (or range) to get style for.
	 * @return Style
	 * @throws PHPExcelException Invalid range.
	 */
	public function getStyleOfRange($pCellCoordinate) {
		// set this sheet as active
		$this->workbook->setActiveSheetIndex($this->workbook->getIndex($this));

		// set cell coordinate as active
		$this->setSelectedCells(strtoupper($pCellCoordinate));

		return $this->workbook->getCellXfSupervisor();
	}

	/**
	 * Get style for a specific cell.
	 *
	 * @param Coordinates $pCellCoordinate Cell coordinate to get style for.
	 * @return Style
	 * @throws PHPExcelException Invalid range.
	 */
	public function getStyle($pCellCoordinate) {
		// set this sheet as active
		$this->workbook->setActiveSheetIndex($this->workbook->getIndex($this));

		// set cell coordinate as active
		$this->setSelectedCells($pCellCoordinate->__toString());

		return $this->workbook->getCellXfSupervisor();
	}

	/**
	 * Return the code name of the sheet
	 *
	 * @return string
	 */
	public function getCodeName() {
		return $this->codeName;
	}

	/**
	 * Define the code name of the sheet
	 *
	 * @param string $pValue Same rule as Title minus space not allowed (but,
	 * like Excel, change silently space to underscore).
	 * @return void
	 * @throws PHPExcelException
	 */
	public function setCodeName($pValue) {
		// Is this a 'rename' or not?
		if ($this->getCodeName() === $pValue) {
			return;
		}
		$pValue = (string) str_replace(' ', '_', $pValue); //Excel does this automatically without flinching, we are doing the same
		// Syntax check
		// throw an exception if not valid
		self::checkSheetCodeName($pValue);

		// We use the same code that setTitle to find a valid codeName else not using a space (Excel don't like) but a '_'

		// Is there already such sheet name?
		if ($this->workbook->sheetCodeNameExists($pValue)) {
			// Use name, but append with lowest possible integer

			if (SharedString::CountCharacters($pValue) > 29) {
				$pValue = SharedString::Substring($pValue, 0, 29);
			}
			$i = 1;
			while ($this->workbook->sheetCodeNameExists($pValue . '_' . $i)) {
				++$i;
				if ($i == 10) {
					if (SharedString::CountCharacters($pValue) > 28) {
						$pValue = SharedString::Substring($pValue, 0, 28);
					}
				} elseif ($i == 100) {
					if (SharedString::CountCharacters($pValue) > 27) {
						$pValue = SharedString::Substring($pValue, 0, 27);
					}
				}
			}

			$pValue = $pValue . '_' . $i; // ok, we have a valid name
			//codeName is'nt used in formula : no need to call for an update
			//return $this->setTitle($altTitle, $updateFormulaCellReferences);
		}

		$this->codeName = $pValue;
	}

	/**
	 * Create a new worksheet
	 *
	 * @param Workbook $workbook
	 * @param string $title
	 * @throws PHPExcelException Invalid title.
	 */
	public function __construct($workbook, $title = 'Worksheet') {
		$this->activeCell = Coordinates::create(1, 1);
		// Set parent and title
		$this->workbook = $workbook;
		$this->setTitle($title, false);
		// setTitle can change $pTitle
		$this->setCodeName($this->getTitle());
		$this->setSheetState(self::SHEETSTATE_VISIBLE);
	}
	
	
	/**
	 * Actual implementation of __toString() that may fail with exception.
	 * @return string
	 * @throws \Exception
	 */
	private function toString() {
		$s = "Worksheet"
			. "\n    code name: ". $this->getCodeName()
			. "\n    title:     ". $this->getTitle();
		$rows = $this->getHighestRow();
		$cols = $this->getHighestColumnNumber();
		$s .= "\n    size:      " . $this->calculateWorksheetDataDimension();
		// If there are too many rows, only print the beginning and the end by
		// removing the middle range:
		$dropStart = PHP_INT_MAX;
		$dropEnd = PHP_INT_MAX;
		if( $rows > 30 ){
			$dropStart = 21;
			$dropEnd = $rows - 3;
		}
		for($r = 1; $r <= $rows; $r++){
			$s .= "\n";
			if( $dropStart == $r ){
				$r = $dropEnd;
				$s .= "[rows in range $dropStart to $dropEnd skipped]";
				continue;
			}
			for($c = 1; $c <= $cols; $c++){
				$cell = $this->getCellByColumnAndRow($c, $r);
				$coord = $cell->getCoordinate()->__toString();
				$type = $cell->getDataType();
				$value_raw = \it\icosaedro\utils\TestUnit::dump($cell->getValue());
				$s .=  ($c > 1? "\t" : "") . "[$coord,$type,$value_raw] $cell";
			}
		}
		return $s;
	}
	
	
	/**
	 * Returns a readable representation of this worksheet, mostly for debugging.
	 * @return string
	 */
	function __toString() {
		// Exceptions inside __toString() method causes a fatal error. Our only
		// chanche to get the error reported is to capture any exception, log it
		// to stderr, then terminate the program. Client code cannot recover because
		// there is no way to safely report the error.
		try {
			return $this->toString();
		}
		catch(\Exception $e){
			logCapturedException($e);
			exit(1);
		}
	}

}
