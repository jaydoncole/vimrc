<?php

namespace it\icosaedro\web\controls;

require_once __DIR__ . "/../../../../all.php";

use it\icosaedro\web\Input;

/**
 * HTML multi-line text entry area element.
 * 
 * @author Umberto Salsi <salsi@icosaedro.it>
 * @copyright Copyright 2018 by icosaedro.it di Umberto Salsi
 * @version $Date: 2018/04/08 12:41:47 $
 */
class Text extends Control {
	
	/** @var string */
	private $value;
	
	/**
	 * Returns the current value.
	 * @return string String retrieved from the request, parsed and sanitized
	 * as UTF-8 encoded string using {@link it\icosaedro\web\Input::getText()}.
	 * A NULL value is returned if no data were found in the request.
	 * 
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
	
	function save()
	{
		$this->_form->setData($this->_name, $this->value);
	}
	
	function resume()
	{
		$this->value = cast("string", $this->_form->getData($this->_name));
	}
	
	/**
	 * Displays this control on the page.
	 * Generally you may want to set the "cols=C rows=R" attributes before
	 * invoking this method, see {@link self::addAttributes()}.
	 */
	function render()
	{
		echo "<textarea name='", $this->_name,
				"' ", $this->_add_attributes, ">\n",
				htmlspecialchars($this->value), "</textarea>";
	}
	
	/**
	 * Retrieves the value of this control from the request. If the value cannot
	 * be retrieved, the null string is set instead. If a value is available, it
	 * is sanitized using {@link it\icosaedro\web\Input::sanitizeText()}.
	 * @return void
	 */
	function retrieve()
	{
		$this->setValue( Input::getText($this->_name, NULL) );
	}
	
}
