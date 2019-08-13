<?php

namespace it\icosaedro\web\controls;

require_once __DIR__ . "/../../../../all.php";

use it\icosaedro\web\Html;

/**
 * Single-line HTML password entry control.
 * @author Umberto Salsi <salsi@icosaedro.it>
 * @copyright Copyright 2018 by icosaedro.it di Umberto Salsi
 * @version $Date: 2018/04/09 13:34:43 $
 */
class Password extends Line {
	
	/**
	 * Send this control to the standard output.
	 * @return void
	 */
	function render()
	{
		echo "<input type=password name='", $this->_name,
				"' value='", Html::text($this->getValue()),
				"' ", $this->_add_attributes, ">";
	}
	
}
