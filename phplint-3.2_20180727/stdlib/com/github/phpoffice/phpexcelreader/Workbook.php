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
 * @version    $Date: 2015/10/30 10:43:38 $
 */

namespace com\github\phpoffice\phpexcelreader;

require_once __DIR__ . "/../../../../all.php";

use OutOfBoundsException;
use InvalidArgumentException;

/**
 * Holds collections of document's properties, shared styles and worksheets.
 */
class Workbook implements \it\icosaedro\containers\Printable {

	/**
	 * Document properties
	 *
	 * @var DocumentProperties
	 */
	private $properties;

	/**
	 * Collection of Worksheet objects. The key is the sheet index 0, 1, ...
	 *
	 * @var Worksheet[int]
	 */
	private $workSheetCollection = array();

	/**
	 * Active sheet index, or -1 if not available.
	 *
	 * @var integer
	 */
	private $activeSheetIndex = 0;
	
	/**
	 * Active sheet, or NULL if not available.
	 * 
	 * @var Worksheet
	 */
	private $activeSheet = NULL;

	/**
	 * CellXf supervisor
	 *
	 * @var Style
	 */
	private $cellXfSupervisor;

	/**
	 * Cell extended format collection
	 *
	 * @var Style[int]
	 */
	private $cellXfCollection = array();

	/**
	 * CellStyleXf collection
	 *
	 * @var Style[int]
	 */
	private $cellStyleXfCollection = array();

	/*.
	forward public void    function __construct();
	forward public int     function getSheetCount();
	forward public boolean function sheetNameExists(string $pSheetName);
	forward public boolean function sheetCodeNameExists(string $pSheetName);
	forward public void    function setActiveSheetIndex(int $pIndex) throws InvalidArgumentException;
	forward public DocumentProperties function getProperties();
	forward public void    function addCellStyleXf(Style $pStyle);
	forward public void    function addCellXf(Style $style);
	forward public Worksheet function createSheet(int $iSheetIndex =) throws PHPExcelException;
	forward	public Style   function getCellXfByIndex(int $pIndex);
	forward	public Style[int] function getCellXfCollection();
	forward public Style   function getCellXfSupervisor();
	forward public Style   function getDefaultStyle() throws PHPExcelException;
	forward public int     function getIndex(Worksheet $pSheet) throws InvalidArgumentException;
	forward public void    function removeSheetByIndex(int $pIndex) throws PHPExcelException;
	forward public Worksheet function getActiveSheet();
	forward public Worksheet[int] function getAllSheets();

	pragma 'suspend';
	.*/

	/**
	 * Get sheet by code name. Warning : sheet don't have always a code name !
	 *
	 * @param string $pName Sheet name
	 * @return Worksheet
	 */
	public function getSheetByCodeName($pName) {
		$worksheetCount = count($this->workSheetCollection);
		for ($i = 0; $i < $worksheetCount; ++$i) {
			if ($this->workSheetCollection[$i]->getCodeName() === $pName) {
				return $this->workSheetCollection[$i];
			}
		}

		return null;
	}

	/**
	 * Check if a sheet with a specified code name already exists
	 *
	 * @param string $pSheetCodeName  Name of the worksheet to check
	 * @return boolean
	 */
	public function sheetCodeNameExists($pSheetCodeName) {
		return ($this->getSheetByCodeName($pSheetCodeName) !== null);
	}

	/**
	 * Get properties
	 *
	 * @return DocumentProperties
	 */
	public function getProperties() {
		return $this->properties;
	}

	/**
	 * Set properties
	 *
	 * @param DocumentProperties $pValue
	 */
	public function setProperties($pValue) {
		$this->properties = $pValue;
	}

	/**
	 * Get sheet count
	 *
	 * @return int
	 */
	public function getSheetCount() {
		return count($this->workSheetCollection);
	}

	/**
	 * Get sheet by index
	 *
	 * @param  int $pIndex Sheet index
	 * @return Worksheet
	 * @throws OutOfBoundsException Invalid index.
	 */
	public function getSheet($pIndex) {
		if (!isset($this->workSheetCollection[$pIndex])) {
			$numSheets = $this->getSheetCount();
			throw new OutOfBoundsException(
			"Your requested sheet index: $pIndex is out of bounds. The actual number of sheets is $numSheets."
			);
		}

		return $this->workSheetCollection[$pIndex];
	}

	/**
	 * Get active sheet.
	 *
	 * @return Worksheet Active sheet, or NULL.
	 */
	public function getActiveSheet() {
		return $this->activeSheet;
	}

	/**
	 * Get sheet by name
	 *
	 * @param  string $pName Sheet name
	 * @return Worksheet
	 */
	public function getSheetByName($pName) {
		$worksheetCount = count($this->workSheetCollection);
		for ($i = 0; $i < $worksheetCount; ++$i) {
			if ($this->workSheetCollection[$i]->getTitle() === $pName) {
				return $this->workSheetCollection[$i];
			}
		}

		return null;
	}

	/**
	 * Check if a sheet with a specified name already exists
	 *
	 * @param  string $pSheetName  Name of the worksheet to check
	 * @return boolean
	 */
	public function sheetNameExists($pSheetName) {
		return ($this->getSheetByName($pSheetName) !== null);
	}

	/**
	 * Add sheet.
	 *
	 * @param  Worksheet $pSheet
	 * @param  int $iSheetIndex Index where sheet should go (0,1,..., or -1 for last).
	 * @return Worksheet The worksheet just added.
	 * @throws PHPExcelException
	 */
	private function addSheet($pSheet, $iSheetIndex = -1) {
		if ($this->sheetNameExists($pSheet->getTitle())) {
			throw new PHPExcelException(
			"Workbook already contains a worksheet named " . $pSheet->getTitle() . ". Rename this worksheet first."
			);
		}

		if ($iSheetIndex < 0) {
			$this->workSheetCollection[] = $pSheet;
			if ($this->activeSheetIndex < 0) {
				$this->activeSheetIndex = count($this->workSheetCollection) - 1;
				$this->activeSheet = $pSheet;
			}
		} else if($iSheetIndex < count($this->workSheetCollection)){
			// Insert the sheet at the requested index
			array_splice(
					$this->workSheetCollection, $iSheetIndex, 0, array($pSheet)
			);

			// Adjust active sheet index if necessary
			if ($this->activeSheetIndex < 0) {
				$this->activeSheetIndex = $iSheetIndex;
				$this->activeSheet = $pSheet;
			} else if ($this->activeSheetIndex >= $iSheetIndex) {
				++$this->activeSheetIndex;
			}
		} else {
			throw new PHPExcelException("sheet index above limit: $iSheetIndex");
		}

//		if ($pSheet->getParent() === null) {
//			$pSheet->rebindParent($this);
//		}

		return $pSheet;
	}

	/**
	 * Create sheet and add it to this workbook
	 *
	 * @param int $iSheetIndex Index where sheet should go (0,1,..., or -1 for last).
	 * @return Worksheet Worksheet just created.
	 * @throws PHPExcelException
	 */
	public function createSheet($iSheetIndex = -1) {
		$newSheet = new Worksheet($this);
		$this->addSheet($newSheet, $iSheetIndex);
		return $newSheet;
	}

	/**
	 * Remove sheet by index
	 *
	 * @param  int $pIndex Active sheet index
	 * @throws PHPExcelException
	 */
	public function removeSheetByIndex($pIndex) {
		if( ! isset($this->workSheetCollection[$pIndex]) )
			throw new PHPExcelException("no this sheet index: $pIndex");
		
		array_splice($this->workSheetCollection, $pIndex, 1);
		// Adjust active sheet index if necessary
		if (($this->activeSheetIndex >= $pIndex) &&
				($pIndex > count($this->workSheetCollection) - 1)) {
			--$this->activeSheetIndex;
			if( $this->activeSheetIndex >= 0 )
				$this->activeSheet = $this->workSheetCollection[$this->activeSheetIndex];
			else
				$this->activeSheet = NULL;
		}
	}
	
	
	/**
	 * Removes a worksheet.
	 * @param Worksheet $worksheet Worksheet to remove.
	 * @return void
	 * @throws PHPExcelException No this sheet.
	 */
	public function removeSheet($worksheet) {
		foreach($this->workSheetCollection as $index => $ws){
			if( $ws === $worksheet ){
				$this->removeSheetByIndex($index);
				return;
			}
		}
		throw new PHPExcelException("no this sheet");
	}
	

	/**
	 * Get all sheets
	 *
	 * @return Worksheet[int]
	 */
	public function getAllSheets() {
		return $this->workSheetCollection;
	}

	/**
	 * Get index for sheet
	 *
	 * @param  Worksheet $pSheet
	 * @return int Sheet index
	 * @throws InvalidArgumentException Sheet not in this workbook. 
	 */
	public function getIndex($pSheet) {
		foreach ($this->workSheetCollection as $key => $value) {
			if ($value === $pSheet) {
				return $key;
			}
		}

		throw new InvalidArgumentException("Sheet does not exist.");
	}

	/**
	 * Get active sheet index.
	 *
	 * @return int Active sheet index, or -1 if not set.
	 */
	public function getActiveSheetIndex() {
		if( $this->activeSheet === NULL )
			return -1;
		foreach($this->workSheetCollection as $index => $worksheet)
			if( $worksheet === $this->activeSheet )
				return $index;
		return -1;
	}

	/**
	 * Set active sheet index
	 *
	 * @param  int $pIndex Active sheet index
	 * @return void
	 * @throws InvalidArgumentException No this sheet index.
	 */
	public function setActiveSheetIndex($pIndex) {
		if( ! isset($this->workSheetCollection[$pIndex]) )
			throw new InvalidArgumentException("sheet does not exit: $pIndex");
		$this->activeSheetIndex = $pIndex;
		$this->activeSheet = $this->workSheetCollection[$pIndex];
			
//		$numSheets = count($this->workSheetCollection);
//
//		if ($pIndex > $numSheets - 1) {
//			throw new PHPExcelException(
//			"You tried to set a sheet active by the out of bounds index: $pIndex. The actual number of sheets is $numSheets."
//			);
//		} else {
//			$this->activeSheetIndex = $pIndex;
//		}
	}

//	/**
//	 * Set active sheet index by name
//	 *
//	 * @param  string $pValue Sheet title
//	 * @return void
//	 * @throws PHPExcelException
//	 */
//	public function setActiveSheetIndexByName($pValue) {
//		$worksheet = $this->getSheetByName($pValue);
//		if ($worksheet === NULL)
//			throw new PHPExcelException('Workbook does not contain sheet:' . $pValue);
//		$this->setActiveSheetIndex($this->getIndex($worksheet));
//	}

	/**
	 * Get sheet names
	 *
	 * @return string[int]
	 */
	public function getSheetNames() {
		$returnValue = /*. (string[int]) .*/ array();
		$worksheetCount = $this->getSheetCount();
		for ($i = 0; $i < $worksheetCount; ++$i) {
			$returnValue[] = $this->getSheet($i)->getTitle();
		}

		return $returnValue;
	}

	/**
	 * Get the workbook collection of cellXfs
	 *
	 * @return Style[int]
	 */
	public function getCellXfCollection() {
		return $this->cellXfCollection;
	}

	/**
	 * Get cellXf by index
	 *
	 * @param  int $pIndex
	 * @return Style
	 */
	public function getCellXfByIndex($pIndex) {
		return $this->cellXfCollection[$pIndex];
	}

	/**
	 * Get default style.
	 *
	 * @return Style
	 * @throws PHPExcelException
	 */
	public function getDefaultStyle() {
		if (isset($this->cellXfCollection[0])) {
			return $this->cellXfCollection[0];
		}
		throw new PHPExcelException('No default style found for this workbook');
	}

	/**
	 * Add a cellXf to the workbook.
	 *
	 * @param Style $style
	 */
	public function addCellXf($style) {
		$this->cellXfCollection[] = $style;
		$style->setIndex(count($this->cellXfCollection) - 1);
	}

	/**
	 * Get the cellXf supervisor
	 *
	 * @return Style
	 */
	public function getCellXfSupervisor() {
		return $this->cellXfSupervisor;
	}

	/**
	 * Add a cellStyleXf to the workbook
	 *
	 * @param Style $pStyle
	 */
	public function addCellStyleXf($pStyle) {
		$this->cellStyleXfCollection[] = $pStyle;
		$pStyle->setIndex(count($this->cellStyleXfCollection) - 1);
	}

	/**
	 * Remove cellStyleXf by index
	 *
	 * @param integer $pIndex Index to cellXf
	 * @throws PHPExcelException
	 */
	public function removeCellStyleXfByIndex($pIndex) {
		if ($pIndex > count($this->cellStyleXfCollection) - 1) {
			throw new PHPExcelException("CellStyleXf index is out of bounds.");
		} else {
			array_splice($this->cellStyleXfCollection, $pIndex, 1);
		}
	}

	/**
	 * Create a new empty workbook.
	 */
	public function __construct() {
		// Initialise worksheet collection
		$this->workSheetCollection = array();
		$this->activeSheetIndex = -1;
		$this->activeSheet = NULL;

		// Create document properties
		$this->properties = new DocumentProperties();

		// Create the cellXf supervisor
		$this->cellXfSupervisor = new Style(true);
		$this->cellXfSupervisor->bindParent($this);

		// Create the default style
		$this->addCellXf(new Style());
		$this->addCellStyleXf(new Style());
	}
	
	
	/**
	 * Returns a readable text describing the content of this workbook, including
	 * all its worksheets and their content. For debugging only.
	 * @return string
	 */
	public function __toString() {
		$s = "Workbook:\n";
		$sheets = $this->getAllSheets();
		foreach($sheets as $sheet){
			$s .= "\n$sheet";
		}
		return $s;
	}

}
