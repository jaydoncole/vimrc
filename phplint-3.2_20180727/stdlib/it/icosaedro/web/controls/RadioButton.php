<?php

namespace it\icosaedro\web\controls;

require_once __DIR__ . "/../../../../all.php";

/**
 * HTML radio button element. Radio buttons of a group share the same name but
 * each one must have a distinct ordinal number.
 * 
 * @author Umberto Salsi <salsi@icosaedro.it>
 * @copyright Copyright 2018 by icosaedro.it di Umberto Salsi
 * @version $Date: 2018/03/24 11:14:34 $
 */
class RadioButton extends Control {
	
	/**
	 * Value of the "name" attribute.
	 * @var string
	 */
	private $name_attribute;
	
	/** @var int */
	private $value = 0;
	
	/** @var string */
	private $display_html;
	
	/** @var boolean */
	private $is_selected = FALSE;
	
	/**
	 * Tells if selected.
	 * @return boolean
	 */
	function isSelected()
	{
		return $this->is_selected;
	}
	
	/**
	 * Set the selected status. Only one button in a group should be selected.
	 * @param boolean $value
	 * @return void
	 */
	function setSelected($value)
	{
		$this->is_selected = $value;
	}
	
	function save()
	{
		$this->_form->setData($this->_name, $this->is_selected);
	}
	
	function resume()
	{
		$this->is_selected = (boolean) $this->_form->getData($this->_name);
	}
	
	/**
	 * Sends this control to the standard output.
	 * @return void
	 */
	function render()
	{
		if( strlen($this->display_html) > 0 )
			echo "<label>";
		echo "<input type=radio name='", $this->name_attribute,
				"' value=", $this->value,
				($this->is_selected? " checked" : ""),
				" ", $this->_add_attributes, ">";
		if( strlen($this->display_html) > 0 )
			echo $this->display_html, "</label>";
	}
	
	/**
	 * Retrieves the state of this control from the request. If missing, the
	 * unselected status is assumed.
	 * @return void
	 */
	function retrieve()
	{
		$this->setSelected( isset($_POST[$this->name_attribute])
			&& $this->value == (int) $_POST[$this->name_attribute] );
	}
	
	
	/**
	 * Create a new unselected radio button control.
	 * @param ContainerInterface $form  Container form or panel.
	 * @param string $name Name attribute. Related radio buttons belonging to the
	 * same group must share exactly the same name.
	 * @param int $value Value set for this control. Related radio buttons
	 * belonging to the same group must have distinct numbers assigned.
	 * @param string $display_html Clickable caption text, HTML format
	 */
	function __construct($form, $name, $value, $display_html)
	{
		parent::__construct($form, "$name.$value");
		$this->name_attribute = $name;
		$this->value = $value;
		$this->display_html = $display_html;
	}
	
}
