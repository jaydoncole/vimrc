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
 * @version $Date: 2018/04/13 09:23:59 $
 */

namespace com\github\phpoffice\phpexcelreader;

require_once __DIR__ . "/../../../../all.php";

/*. require_module 'standard_reflection'; .*/

/**
 * Reader for Excel files.
 * 
 * Several reader classes are provided to support the following file formats:
 * Excel 97 (.xls), Excel 2003 XML (.xml), Excel 2007 (.xlsx), OpenDocument Spreadsheet (.ods)
 * and CSV (.csv). Each reader can be used by its own, or the helper functions of
 * this class can be used instead to automatically detect the file format and apply
 * the appropriate reader.
 * 
 * <p>The following sample of code reads an Excel file and displays its content:
<pre>
$workbook = PHPExcelReader::load("data.xlsx");
foreach($workbook-&gt;getAllSheets() as $worksheet){
	echo $worksheet-&gt;getTitle(), ":\n";
	$rows = $worksheet-&gt;getHighestRow();
	$cols = $worksheet-&gt;getHighestColumnNumber();
	for($r = 1; $r &lt;= $rows; $r++){
		for($c = 1; $c &lt;= $cols; $c++){
			$cell = $worksheet-&gt;getCellByColumnAndRow($c, $r);
			$coord = $cell-&gt;getCoordinate();
			$humanReadable = $cell-&gt;getFormattedValue();
			echo "$coord: $humanReadable\n";
		}
	}
}
</pre>
 * 
 * <p>In the example above, cells' values are displayed using the general formatting
 * function, which has several limitations (see the documentation for more).
 * The Cell class also provides methods to detect and retrieve the value of a cell
 * in a format suitable for further manipulation in the program, like strings, numbers,
 * dates and times.
 * 
 * <p>The reader implemented here always read in the whole Excel file in memory
 * to made it available to the application program as a data structure of sheets
 * and cell. This in not very efficient when very large quantities of data are involved.
 * Consider instead to use simple CSV files in these cases.
 * 
 * <p>This implementation is based on the PHPExcel 1.8 library
 * available at {@link https://github.com/PHPOffice/PHPExcel} but tailored for use
 * with PHPLint and its standard library. A list of the main differences follows:
 * <ul>
 * <li>Only data read is implemented. You cannot create Excel files with this library.</li>
 * <li>Formulas are not evaluated.</li>
 * <li>All the strings entering and exiting from this library are assumed UTF-8 encoded.</li>
 * <li>Data formats are parsed allowing to retrieve a more precise description of
 * the values, differentiating between time, date and time, currency and other types.
 * See the methods of the {@link \com\github\phpoffice\phpexcelreader\Cell Cell}
 * class for more.
 * </li>
 * <li>File formats that require XML parsing now uses the PHP's DOM extension
 * in place of the old simplexml extension.</li>
 * <li>The CSV reader properly parses UTF encoded text files, with or without BOM.</li>
 * <li>This package can be validated with PHPLint and integrates with its library of
 * tools, including class autoloading and error mapping into exceptions.</li>
 * </ul>
 * 
 * @author Umberto Salsi <salsi@icosaedro.it> (porting to PHPLint).
 * @version $Date: 2018/04/13 09:23:59 $
 */
class PHPExcelReader {
	
	/**
	 * Available readers. The key is the class name of the reader, the values are
	 * the known file name extensions in lower-case letters. The same extension
	 * can be shared between different readers.
	 * @var string[string]
	 */
	public static $availableReaders = array(
		"com\\github\\phpoffice\\phpexcelreader\\Excel5Reader" => "xls,xlt",
		"com\\github\\phpoffice\\phpexcelreader\\Excel2003XMLReader" => "xml",
		"com\\github\\phpoffice\\phpexcelreader\\Excel2007Reader" => "xlsx,xlsm,xltx,xltm",
		"com\\github\\phpoffice\\phpexcelreader\\OOCalcReader" => "ods,ots",
		"com\\github\\phpoffice\\phpexcelreader\\CSVReader" => "csv,txt,text",
	);
	
	
	/**
	 * Returns an instance of a class whose constructor has no mandatory arguments.
	 * Note that only concrete classes with public or default constructor can be
	 * instantiated.
	 * @param string $className
	 * @return object
	 * @throws \InvalidArgumentException
	 * Unknow class or failed to autoload the class.
	 * The class is an internal class that cannot be instantiated in user's code.
	 * The class is an interface or it is abstract or its constructor is not public.
	 * The class constructor has mandatory arguments.
	 * @throws \Exception Class constructor thrown an exception.
	 */
	private static function createInstanceZeroArguments($className) {
		$c = new \ReflectionClass($className);
		if( ! $c->isInstantiable() )
			throw new \InvalidArgumentException("$className is interface, or abstract or its constructor is not public");
		$init = $c->getConstructor();
		if( $init !== NULL && $init->getNumberOfRequiredParameters() > 0 )
			throw new \InvalidArgumentException("$className constructor has mandatory parameters");
		return $c->newInstanceArgs(array());
	}
	
	
	/**
	 * Create reader for file using automatic reader class resolution. Client programs
	 * may want to call this method to recognize the type of the file or to set
	 * specific options of the reader before actually parse the file.
	 * @param string $pFilename The name of the spreadsheet file.
	 * @return AbstractReader
	 * @throws PHPExcelException All the available readers failed; the message
	 * contains a detailed description of all the attempts made and the reason of
	 * the failure.
	 */
	public static function createReaderForFile($pFilename) {
		// Failed attempts: class name => description:
		$failed = /*. (string[string]) .*/ array();
		
		// First, lucky guess by inspecting file extension
		$pathinfo = pathinfo($pFilename);
		if (isset($pathinfo['extension'])) {
			$extension = strtolower($pathinfo['extension']);
			foreach(self::$availableReaders as $className => $extensions_list){
				$extensions = explode(",", $extensions_list);
				if( in_array($extension, $extensions) ){
					try {
						$reader = cast(AbstractReader::class, self::createInstanceZeroArguments($className));
						if( $reader->canRead($pFilename) )
							return $reader;
						else
							$failed[$className] = "Cannot read.";
					}
					catch(\Exception $e){
						$failed[$className] = "$e";
					}
				}
			}
		}
		
		// Then, try all the available readers we did not tested above:
		foreach(self::$availableReaders as $className => $extensions_list){
			if( ! isset($failed[$className]) ){
				try {
					$reader = cast(AbstractReader::class, self::createInstanceZeroArguments($className));
					if( $reader->canRead($pFilename) )
						return $reader;
					else
						$failed[$className] = "Cannot read.";
				}
				catch(\Exception $e){
					$failed[$className] = "$e";
				}
			}
		}
		
		// All the readers failed.
		$attempts = "";
		foreach($failed as $className => $description)
			$attempts .= "\n$className: $description";
		throw new PHPExcelException("None of the available readers can read $pFilename: $attempts");
	}
	

	/**
	 * Identify file type using automatic reader class resolution.
	 * @param string $pFilename The name of the spreadsheet file to identify.
	 * @return string Fully qualified name of the reader class.
	 * @throws PHPExcelException All the available readers failed. The message
	 * contains a detailed description of all the attempts made and the reason of
	 * the failure.
	 */
	public static function identify($pFilename) {
		$reader = self::createReaderForFile($pFilename);
		return get_class($reader);
	}

	
	/**
	 * Loads PHPExcel from file using automatic reader class resolution.
	 * @param string $pFilename The name of the spreadsheet file.
	 * @return Workbook May return NULL if no suitable reader is available.
	 * @throws PHPExcelException All the available readers failed. The message
	 * contains a detailed description of all the attempts made and the reason of
	 * the failure.
	 * @throws \ErrorException Failed accessing the file. Internal failure of the
	 * reader.
	 */
	public static function load($pFilename) {
		$reader = self::createReaderForFile($pFilename);
		return $reader->load($pFilename);
	}

}
