<?php

namespace it\icosaedro\web\controls;

require_once __DIR__ . "/../../../../all.php";

use it\icosaedro\web\Html;
use it\icosaedro\web\Input;

/**
 * Single-line HTML text entry control.
 * 
 * @author Umberto Salsi <salsi@icosaedro.it>
 * @copyright Copyright 2018 by icosaedro.it di Umberto Salsi
 * @version $Date: 2018/04/09 13:33:28 $
 */
class Line extends Control {
	
	/** @var string */
	private $value;
	
	/**
	 * Returns the current value.
	 * @return string String retrieved from the request, parsed and sanitized
	 * as UTF-8 encoded string using {@link it\icosaedro\web\Input::getLine()}.
	 * A NULL value is returned if no data were found in the request.
	 */
	function getValue()
	{
		return $this->value;
	}
	
	/**
	 * Set the value.
	 * @param string $value Normally a UTF-8 string or the NULL value.
	 * @return void
	 */
	function setValue($value)
	{
		$this->value = $value;
	}
	
	/**
	 * Save the current value in the form data. For internal use only of the
	 * Form class.
	 */
	function save()
	{
		$this->_form->setData($this->_name .".value", $this->value);
	}
	
	/**
	 * 
	 * Retrieve the value from the form data. For internal use only of the
	 * Form class.
	 */
	function resume()
	{
		$this->value = cast("string", $this->_form->getData($this->_name .".value"));
	}
	
	/**
	 * Send this control to the standard output.
	 * @return void
	 */
	function render()
	{
		echo "<input type=text name='", $this->_name,
				"' value='", Html::text($this->value),
				"' ", $this->_add_attributes, ">";
	}
	
	/**
	 * Retrieves the value of this control from the request. If the value cannot
	 * be retrieved, the null string is set instead. If a value is available, it
	 * is sanitized using {@link it\icosaedro\web\Input::sanitizeLine()}.
	 * @return void
	 */
	function retrieve()
	{
		$this->value = Input::getLine($this->_name, NULL);
	}
	
}
