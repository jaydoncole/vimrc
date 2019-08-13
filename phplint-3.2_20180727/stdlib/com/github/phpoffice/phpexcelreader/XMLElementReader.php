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
 * @version    $Date: 2015/10/28 15:32:40 $
 */

namespace com\github\phpoffice\phpexcelreader;

require_once __DIR__ . "/../../../../all.php";

/*. require_module 'libxml';  require_module 'dom'; .*/

use DOMElement;
use DOMDocument;
use DOMXPath;

/**
 * XML element.
 * XML parser trimmed down for use with the PHPExcel's readers.
 * Based on DOM, that makes it about 2.5 times slower than the similar implementation
 * of this same class based on SimpleXML, but it is validable by PHPLint, and
 * DOM is the recommended extension to use in modern applications. All the strings
 * returns UTF-8 encoded.
 * @author Umberto Salsi <salsi@icosaedro.it>
 */
class XMLElementReader {
	
	/**
	 * @var DOMElement
	 */
	private $e;
	
	/**
	 * @var DOMXPath
	 */
	private $query;
	
	/**
	 * 
	 * @param DOMElement $e
	 * @param DOMXPath $query
	 */
	private function __construct($e, $query) {
		$this->e = $e;
		$this->query = $query;
	}
	
	
	/**
	 * Returns the local name of this element.
	 * @return string
	 */
	function getLocalName() {
		return $this->e->localName;
//		return $this->e->tagName;
	}
	
	
	/**
	 * Returns the text inside this element. If this element also includes other
	 * elements, the result is quite unpredictable.
	 * @return string
	 */
	function getText() {
		return $this->e->nodeValue;
	}
	
	
	function __toString() {
		$e = $this->e;
		$x = $e->getNodePath();
		$tag = $e->tagName;
		$text = $e->textContent;
		if( strlen($text) > 100 )
			$text = substr($text, 0, 50) . "...";
		return "node path=$x: <$tag>" . htmlspecialchars($text) . "</$tag>";
	}
	
	
	/**
	 * Returns the child elements of this element matching the given local name,
	 * regardless of their namespace.
	 * @param string $name Local name of the elements, or NULL for all.
	 * @return self[int]
	 */
	function getElements($name = NULL) {
		if( $name === NULL ){
			$name = "*";
		}
		$l = $this->e->getElementsByTagName($name);
		$named_elements = /*. (self[int]) .*/ array();
		foreach($l as $e)
			$named_elements[] = new self(cast("DOMElement", $e), $this->query);
		return $named_elements;
	}
	
	
	/**
	 * 
	 * @param string $name
	 * @return self
	 */
	function getElement($name) {
		$named_elements = $this->getElements($name);
		if( count($named_elements) == 0 )
			return NULL;
		else
			return $named_elements[0];
	}
	
	
	/**
	 * Returns all the attributes of this element with the given namespace or
	 * prefix.
	 * @param string $ns_or_prefix Namespace or prefix.
	 * @param boolean $is_prefix True if the first parameter is a prefix, otherwise
	 * it is a namespace (default).
	 * @return string[string] Associative array of attributes. The key is the
	 * local name of the attribute (without namespace or prefix), the value is
	 * its value.
	 */
	function attributes($ns_or_prefix = NULL, $is_prefix = FALSE) {
		if( $this->e->attributes === NULL )
			return array();
		$attributes = /*. (string[string]) .*/ array();
		for($i = 0; $i < $this->e->attributes->length; $i++){
			$attr = $this->e->attributes->item($i);
			if( $ns_or_prefix === NULL || ($is_prefix && $attr->localName === $ns_or_prefix || ! $is_prefix && $attr->namespaceURI === $ns_or_prefix) ){
				$attributes[$attr->localName] = $attr->nodeValue;
			}
		}
		return $attributes;
	}
	
	
	/**
	 * @param string $name
	 * @param string $default_
	 * @return string
	 */
	function getAttribute($name, $default_ = null) {
		if( $this->e->hasAttribute($name) ){
			return $this->e->getAttribute($name);
		} else {
			if( func_num_args() >= 2 )
				return $default_;
			else
				throw new \RuntimeException("no this attribute: $name");
		}
	}
	
	
	/**
	 * 
	 * @param string $prefix
	 * @param string $ns
	 * @return void
	 */
	function registerXPathNamespace($prefix, $ns) {
		if( $this->query === NULL )
			$this->query = new DOMXPath($this->e->ownerDocument);
		$this->query->registerNamespace($prefix, $ns);
	}
	
	
	/**
	 * 
	 * @param string $path
	 * @return self[int]
	 */
	function xpath($path) {
		if( $this->query === NULL )
			$this->query = new DOMXPath($this->e->ownerDocument);
		$l = $this->query->query($path, $this->e);
		$elements = /*. (self[int]) .*/ array();
		foreach($l as $e)
			$elements[] = new self(cast("DOMElement", $e), $this->query);
		return $elements;
	}
	
	
	/**
	 * 
	 * @param string $xml
	 * @return self
	 * @throws \ErrorException
	 */
	static function loadFromString($xml) {
		$dom = new DOMDocument();
		// The LIBXML_NONET flag prevents XXE vulnerability.
		// See also:
		// 
		// XML External Entity (XXE) Processing Vulnerability Test
		// https://www.owasp.org/index.php/XML_External_Entity_%28XXE%29_Processing
		// 
		// Security Advisory ZF2014-01: Potential XXE/XEE attacks using PHP functions:
		// simplexml_load_*, DOMDocument::loadXML, and xml_parse
		// http://framework.zend.com/security/advisory/ZF2014-01
		// (in particular, XEE attacks addressed by newer libxml).
		$dom->loadXML($xml, LIBXML_NONET | LIBXML_COMPACT);
		$root = $dom->documentElement;
		return new XMLElementReader($root, NULL);
	}
	
}
