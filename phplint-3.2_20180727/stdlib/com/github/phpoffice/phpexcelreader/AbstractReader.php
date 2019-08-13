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

/**
 * Each specific Excel's format reader should implement this abstract class in order
 * to allow the PHPExcelReader class to automatically detect and load a file.
 */
abstract class AbstractReader {
		
	/**
	 * Can the current reader class implementation read the file?
	 *
	 * @param string $pFilename
	 * @return boolean True if it can.
	 * @throws \ErrorException Failed access to the file.
	 */
	public abstract function canRead($pFilename);

	/**
	 * Loads Excel file
	 *
	 * @param string $pFilename
	 * @return Workbook
	 * @throws PHPExcelException
	 * @throws \ErrorException
	 */
	public abstract function load($pFilename);

}
