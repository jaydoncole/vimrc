<?php

namespace it\icosaedro\web\controls;

require_once __DIR__ . "/../../../../all.php";

/**
 * HTML checkbox element.
 * @author Umberto Salsi <salsi@icosaedro.it>
 * @copyright Copyright 2018 by icosaedro.it di Umberto Salsi
 * @version $Date: 2018/03/24 11:14:34 $
 */
class CheckBox extends Control {
	
	/** @var boolean */
	private $is_checked = FALSE;
	
	/** @var string */
	private $label_html;
	
	/**
	 * Tells if checked.
	 * @return boolean
	 */
	function isChecked()
	{
		return $this->is_checked;
	}
	
	/**
	 * Sets the checked status.
	 * @param boolean $value
	 * @return void
	 */
	function setChecked($value)
	{
		$this->is_checked = $value;
	}
	
	function save()
	{
		$this->_form->setData($this->_name, $this->is_checked);
	}
	
	function resume()
	{
		$this->is_checked = (boolean) $this->_form->getData($this->_name);
	}
	
	/**
	 * @return void
	 */
	function retrieve()
	{
		$this->is_checked = isset($_REQUEST[$this->_name]);
	}
	
	/**
	 * @return void
	 */
	function render()
	{
		if( strlen($this->label_html) > 0 )
			echo "<label>";
		echo "<input type=checkbox name='", $this->_name,
				"' value=x",
				$this->is_checked? " checked" : "",
				" ", $this->_add_attributes,
				">";
		if( strlen($this->label_html) > 0 )
			echo $this->label_html, "</label>";
	}
	
	
	/**
	 * Create a new, unchecked checbox.
	 * @param ContainerInterface $form Container form or panel.
	 * @param string $name Name attribute.
	 * @param string $label_html Displayed description of this checkbox as HTML
	 * code.
	 */
	function __construct($form, $name, $label_html)
	{
		parent::__construct($form, $name);
		$this->label_html = $label_html;
	}
	
}
