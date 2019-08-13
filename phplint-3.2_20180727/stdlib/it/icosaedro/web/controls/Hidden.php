<?php

namespace it\icosaedro\web\controls;

require_once __DIR__ . "/../../../../all.php";

use it\icosaedro\web\Html;

/**
 * HTML hidden element. Using either sticky form or bt_ form, there is little
 * need for hidden fields. Still, them can be useful to exchange data with JS
 * code running client-side. Remember that data coming from the remote client
 * can't be trusted and always need validation. Arbitrary strings of bytes can
 * be set and retrieved by the application.
 * 
 * @author Umberto Salsi <salsi@icosaedro.it>
 * @copyright Copyright 2018 by icosaedro.it di Umberto Salsi
 * @version $Date: 2018/04/08 12:38:21 $
 */
class Hidden extends Line {
	
	/**
	 * Send this control to the standard output.
	 * @return void
	 */
	function render()
	{
		echo "<input type=hidden name='", $this->_name,
				"' value='", Html::text($this->getValue()),
				"' ", $this->_add_attributes, ">";
	}
	
	/**
	 * Retrieves the value of this control from the request. If the value cannot
	 * be retrieved, the null string is set instead. No sanitization is performed,
	 * so arbitrary strings of bytes can be set.
	 * @return void
	 */
	function retrieve()
	{
		$name = $this->_name;
		if( isset($_POST[$name]) )
			$s = (string) $_POST[$name];
		else if( isset($_GET[$name]) )
			$s = (string) $_GET[$name];
		else
			$s = NULL;
		$this->setValue($s);
	}
	
	/**
	 * Returns the current value.
	 * @return string String retrieved from the request, possibly arbitrary
	 * bytes. A NULL value is returned if no data were found in the request.
	 */
	function getValue()
	{
		// The only purpose of this method is the warning in the comment above.
		return parent::getValue();
	}
	
}
