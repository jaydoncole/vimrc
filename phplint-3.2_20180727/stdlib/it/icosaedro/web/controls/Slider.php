<?php

namespace it\icosaedro\web\controls;

require_once __DIR__ . "/../../../../all.php";

/**
 * HTML slider input control. Generates a slider cursor tab the user can move
 * to set an integer number. The range of the allowed values and the step can be
 * specified. The user is not allowed to "unset" or "empty" that field.
 * The setValue() and getValue() methods inherited from the Line class provide
 * access to the current raw value as set by the program or retrieved from the
 * postback; a NULL value on a postback means the data was missing from the
 * request. The setInt() and parse() methods provide a type-safe programming
 * interface to set and retrieve the current value.
 * 
 * <p>To validate this control invoke the parse() method to parse and retrieve
 * the value.
 * 
 * @author Umberto Salsi <salsi@icosaedro.it>
 * @copyright Copyright 2018 by icosaedro.it di Umberto Salsi
 * @version $Date: 2018/04/08 12:28:24 $
 */
class Slider extends Spinner {
	
	/**
	 * Send this control to the standard output.
	 * @return void
	 */
	function render()
	{
		echo "<input type=range name='", $this->_name,
				"' min=", $this->min,
				" max=", $this->max,
				" step=", $this->step,
				" value=", $this->getValue(),
				" ", $this->_add_attributes, ">";
	}
	
}
