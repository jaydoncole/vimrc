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
 * @version    $Date: 2016/01/26 12:26:56 $
 */

namespace com\github\phpoffice\phpexcelreader;

require_once __DIR__ . "/../../../../all.php";
/*. require_module 'array'; .*/

/**
 * Holds a set of random properties of the Excel document.
 */
class DocumentProperties {

	const PROPERTY_TYPE_BOOLEAN = 'b';
	const PROPERTY_TYPE_INTEGER = 'i';
	const PROPERTY_TYPE_FLOAT = 'f';
	const PROPERTY_TYPE_DATE = 'd';
	const PROPERTY_TYPE_STRING = 's';
	const PROPERTY_TYPE_UNKNOWN = 'u';

	/**
	 * Creator
	 *
	 * @var string
	 */
	private $creator = 'Unknown Creator';

	/**
	 * LastModifiedBy
	 *
	 * @var string
	 */
	private $lastModifiedBy;

	/**
	 * Created timestamp
	 *
	 * @var int
	 */
	private $created = 0;

	/**
	 * Modified timestamp
	 *
	 * @var int
	 */
	private $modified = 0;

	/**
	 * Title
	 *
	 * @var string
	 */
	private $title = 'Untitled Spreadsheet';

	/**
	 * Description
	 *
	 * @var string
	 */
	private $description = '';

	/**
	 * Subject
	 *
	 * @var string
	 */
	private $subject = '';

	/**
	 * Keywords
	 *
	 * @var string
	 */
	private $keywords = '';

	/**
	 * Category
	 *
	 * @var string
	 */
	private $category = '';

	/**
	 * Manager
	 *
	 * @var string
	 */
	private $manager = '';

	/**
	 * Company
	 *
	 * @var string
	 */
	private $company = 'Microsoft Corporation';

	/**
	 * Custom Properties
	 *
	 * @var mixed[string][string]
	 */
	private $customProperties = array();

	/**
	 * Create a new DocumentProperties
	 */
	public function __construct() {
		// Initialise values
		$this->lastModifiedBy = $this->creator;
		$this->created = time();
		$this->modified = time();
	}

	/**
	 * Get Creator
	 *
	 * @return string
	 */
	public function getCreator() {
		return $this->creator;
	}

	/**
	 * Set Creator
	 *
	 * @param string $pValue
	 * @return DocumentProperties
	 */
	public function setCreator($pValue) {
		$this->creator = $pValue;
		return $this;
	}

	/**
	 * Get Last Modified By
	 *
	 * @return string
	 */
	public function getLastModifiedBy() {
		return $this->lastModifiedBy;
	}

	/**
	 * Set Last Modified By
	 *
	 * @param string $pValue
	 * @return DocumentProperties
	 */
	public function setLastModifiedBy($pValue) {
		$this->lastModifiedBy = $pValue;
		return $this;
	}

	/**
	 * Get Created
	 *
	 * @return int Timestamp.
	 */
	public function getCreated() {
		return $this->created;
	}

	/**
	 * Set Created
	 *
	 * @param mixed $pValue
	 * @return DocumentProperties
	 * @throws PHPExcelException Invalid date/time format. Date outside the allowed
	 * range.
	 */
	public function setCreated($pValue) {
		if ($pValue === null) {
			$created = time();
		} elseif (is_int($pValue) or is_float($pValue)) {
			$created = (int) $pValue;
		} elseif (is_string($pValue)) {
			if (is_numeric($pValue)) {
				$created = (int) $pValue;
			} else {
				$created = SharedDate::parseDateTime((string) $pValue);
			}
		} else {
			throw new \InvalidArgumentException("invalid type: " . gettype($pValue));
		}

		$this->created = $created;
		return $this;
	}

	/**
	 * Get Modified
	 *
	 * @return int
	 */
	public function getModified() {
		return $this->modified;
	}

	/**
	 * Set Modified
	 *
	 * @param mixed $pValue
	 * @return DocumentProperties
	 * @throws PHPExcelException Invalid date/time format. Date outside the allowed
	 * range.
	 */
	public function setModified($pValue) {
		if ($pValue === null) {
			$modified = time();
		} elseif (is_int($pValue) or is_float($pValue)) {
			$modified = (int) $pValue;
		} elseif (is_string($pValue)) {
			if (is_numeric($pValue)) {
				$modified = (int) $pValue;
			} else {
				$modified = SharedDate::parseDateTime((string) $pValue);
			}
		} else {
			throw new \InvalidArgumentException("invalid type: " . gettype($pValue));
		}

		$this->modified = $modified;
		return $this;
	}

	/**
	 * Get Title
	 *
	 * @return string
	 */
	public function getTitle() {
		return $this->title;
	}

	/**
	 * Set Title
	 *
	 * @param string $pValue
	 * @return DocumentProperties
	 */
	public function setTitle($pValue) {
		$this->title = $pValue;
		return $this;
	}

	/**
	 * Get Description
	 *
	 * @return string
	 */
	public function getDescription() {
		return $this->description;
	}

	/**
	 * Set Description
	 *
	 * @param string $pValue
	 * @return DocumentProperties
	 */
	public function setDescription($pValue) {
		$this->description = $pValue;
		return $this;
	}

	/**
	 * Get Subject
	 *
	 * @return string
	 */
	public function getSubject() {
		return $this->subject;
	}

	/**
	 * Set Subject
	 *
	 * @param string $pValue
	 * @return DocumentProperties
	 */
	public function setSubject($pValue) {
		$this->subject = $pValue;
		return $this;
	}

	/**
	 * Get Keywords
	 *
	 * @return string
	 */
	public function getKeywords() {
		return $this->keywords;
	}

	/**
	 * Set Keywords
	 *
	 * @param string $pValue
	 * @return DocumentProperties
	 */
	public function setKeywords($pValue) {
		$this->keywords = $pValue;
		return $this;
	}

	/**
	 * Get Category
	 *
	 * @return string
	 */
	public function getCategory() {
		return $this->category;
	}

	/**
	 * Set Category
	 *
	 * @param string $pValue
	 * @return DocumentProperties
	 */
	public function setCategory($pValue) {
		$this->category = $pValue;
		return $this;
	}

	/**
	 * Get Company
	 *
	 * @return string
	 */
	public function getCompany() {
		return $this->company;
	}

	/**
	 * Set Company
	 *
	 * @param string $pValue
	 * @return DocumentProperties
	 */
	public function setCompany($pValue) {
		$this->company = $pValue;
		return $this;
	}

	/**
	 * Get Manager
	 *
	 * @return string
	 */
	public function getManager() {
		return $this->manager;
	}

	/**
	 * Set Manager
	 *
	 * @param string $pValue
	 * @return DocumentProperties
	 */
	public function setManager($pValue) {
		$this->manager = $pValue;
		return $this;
	}

	/**
	 * Get a List of Custom Property Names
	 *
	 * @return array of string
	 */
	public function getCustomProperties() {
		return array_keys($this->customProperties);
	}

	/**
	 * Check if a Custom Property is defined
	 *
	 * @param string $propertyName
	 * @return boolean
	 */
	public function isCustomPropertySet($propertyName) {
		return isset($this->customProperties[$propertyName]);
	}

	/**
	 * Get a Custom Property Value
	 *
	 * @param string $propertyName
	 * @return mixed
	 */
	public function getCustomPropertyValue($propertyName) {
		if (isset($this->customProperties[$propertyName])) {
			return $this->customProperties[$propertyName]['value'];
		} else {
			return NULL;
		}
	}

	/**
	 * Get a Custom Property Type
	 *
	 * @param string $propertyName
	 * @return string
	 */
	public function getCustomPropertyType($propertyName) {
		if (isset($this->customProperties[$propertyName])) {
			return (string) $this->customProperties[$propertyName]['type'];
		} else {
			return NULL;
		}
	}

	/**
	 * Set a Custom Property
	 *
	 * @param string $propertyName
	 * @param mixed $propertyValue
	 * @param string $propertyType
	 *      'i'    : Integer
	 *   'f' : Floating Point
	 *   's' : String
	 *   'd' : Date/Time
	 *   'b' : Boolean
	 * @return DocumentProperties
	 */
	public function setCustomProperty($propertyName, $propertyValue, $propertyType) {
		if (($propertyType === null) || (!in_array($propertyType, array(self::PROPERTY_TYPE_INTEGER,
					self::PROPERTY_TYPE_FLOAT,
					self::PROPERTY_TYPE_STRING,
					self::PROPERTY_TYPE_DATE,
					self::PROPERTY_TYPE_BOOLEAN)))) {
			if ($propertyValue === null) {
				$propertyType = self::PROPERTY_TYPE_STRING;
			} elseif (is_float($propertyValue)) {
				$propertyType = self::PROPERTY_TYPE_FLOAT;
			} elseif (is_int($propertyValue)) {
				$propertyType = self::PROPERTY_TYPE_INTEGER;
			} elseif (is_bool($propertyValue)) {
				$propertyType = self::PROPERTY_TYPE_BOOLEAN;
			} else {
				$propertyType = self::PROPERTY_TYPE_STRING;
			}
		}

		$this->customProperties[$propertyName] = array(
			'value' => $propertyValue,
			'type' => $propertyType
		);
		return $this;
	}

}
