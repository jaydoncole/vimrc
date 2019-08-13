<?php

namespace org\fpdf;

require_once __DIR__ . "/../../all.php";

/**
 * Acrobat Form Fields should implement this interface. This feature is not
 * finisched yet and not supported.
 * @author Umberto Salsi <salsi@icosaedro.it>
 * @version $Date: 2015/11/16 03:06:49 $
 */
abstract class Field {
	
	public $name = "";
	
	/**
	 * @var double[int]
	 */
	public $box;
	
	public $n = 0;
	
	/**
	 * @param string $name
	 * @param double[int] $box
	 */
	public function __construct($name, $box) {
		// FIXME: check name
		$this->name = $name;
		$this->box = $box;
	}
	
	
	/**
	 * Writes this field into the PDF document and sets the $n property.
	 * @param PdfObjWriterInterface $w
	 */
	public abstract function put($w);
	
}
