<?php

namespace it\icosaedro\web\controls;

require_once __DIR__ . "/../../../../all.php";

use it\icosaedro\containers\Comparable;
use RuntimeException;

/**
 * Displays an HTML list box with multiple selection. For each option, a display
 * string and a value can be specified. The display string is what the user will
 * see; the value is any {@link it\icosaedro\containers\Comparable} object.
 * Values are never sent to the remote client and are not saved, so these must
 * be rebuilt for each page requests; only the selected values are saved.
 * 
 * @author Umberto Salsi <salsi@icosaedro.it>
 * @copyright Copyright 2018 by icosaedro.it di Umberto Salsi
 * @version $Date: 2018/03/24 11:14:34 $
 */
class SelectMultiple extends Control {
	
	/** @var int */
	private $size = 1;
	
	/** @var boolean */
	private $list_selected_first = FALSE;
	
	/**
	 * Entries as seen by the user, listed in the order of the index.
	 * @var string[int]
	 */
	private $displays;
	
	/**
	 * Values the program associates to each entry of the list.
	 * @var Comparable[int]
	 */
	private $values;
	
	/**
	 * Values currently selected.
	 * @var Comparable[int]
	 */
	private $selected_values;
	
	/**
	 * Returns the index of the first occurrence of the value in the list.
	 * @param Comparable $value
	 * @return int Index of the value in the list of the values.
	 * @throws RuntimeException Value not found in the list.
	 */
	private function searchValue($value)
	{
		foreach($this->values as $i => $v)
			if( $v->equals($value) )
				return $i;
		throw new RuntimeException("not found");
	}
	
	/**
	 * Returns the index of the first occurrence of the value in the list of
	 * the selected values.
	 * @param Comparable $value
	 * @return int Index of the value in the list of the selected values, or -1
	 * if not found.
	 */
	private function searchSelectedValue($value)
	{
		foreach($this->selected_values as $i => $v)
			if( $v->equals($value) )
				return $i;
		return -1;
	}
	
	/**
	 * Returns true if the value is selected.
	 * @param Comparable $value
	 * @return boolean True if the selected values include that value.
	 */
	function isSelected($value)
	{
		return $this->searchSelectedValue($value) >= 0;
	}
	
	/**
	 * Set if the value is selected.
	 * @param Comparable $value The value.
	 * @param boolean $selected True to select the value, false to unselect.
	 * @throws RuntimeException Not a value in the list.
	 */
	function setSelectedValue($value, $selected)
	{
		$this->searchValue($value); // merely checks if it exist
		$i = $this->searchSelectedValue($value);
		if( $selected ){
			if( $i < 0 )
				$this->selected_values[] = $value;
		} else {
			if( $i >= 0 )
				unset($this->selected_values[$i]);
		}
	}
	
	/**
	 * Returns the selected values.
	 * @return Comparable[int] Zero-based index of selected values, possibly empty.
	 */
	function getSelectedValues()
	{
		// Renumbering indeces starting from zero:
		$a = /*. (Comparable[int]) .*/ array();
		foreach($this->selected_values as $v)
			$a[] = $v;
		$this->selected_values = $a;
		return $a;
	}
	
	/**
	 * Set the currently selected values.
	 * @param Comparable[int] $selected_values Selected values.
	 * @return void
	 * @throws RuntimeException Some value not in the list.
	 */
	function setSelectedValues($selected_values)
	{
		$this->selected_values = array();
		foreach($selected_values as $value)
			$this->setSelectedValue($value, TRUE);
	}
	
	function save()
	{
		$this->_form->setData($this->_name, $this->selected_values);
	}
	
	function resume()
	{
		$this->selected_values = cast(Comparable::class . "[int]", $this->_form->getData($this->_name));
	}
	
	/**
	 * @param int $i
	 * @param boolean $selected
	 * @param string $display
	 */
	private function renderEntry($i, $selected, $display)
	{
		echo "<option value=$i", ($selected? " selected" : ""),
			">", htmlspecialchars($display), "</option>";
	}
	
	/**
	 * Send this control to the standard output.
	 * @return void
	 */
	function render()
	{
		echo "<select name='", $this->_name,
			"[]' multiple size=", $this->size,
			" ", $this->_add_attributes, ">";
		if( $this->list_selected_first ){
			foreach($this->displays as $i => $display){
				if( $this->isSelected($this->values[$i]) )
					$this->renderEntry($i, TRUE, $display);
			}
			foreach($this->displays as $i => $display){
				if( ! $this->isSelected($this->values[$i]) )
					$this->renderEntry($i, FALSE, $display);
			}
		} else {
			foreach($this->displays as $i => $display){
				$this->renderEntry($i, $this->isSelected($this->values[$i]), $display);
			}
		}
		echo "</select>";
	}
	
	
	/**
	 * Sets the selected values according to the request.
	 * @return void
	 */
	function retrieve()
	{
		if( ! (isset($_REQUEST[$this->_name]) && is_array($_REQUEST[$this->_name])) ){
			$this->selected_values = /*. (Comparable[int]) .*/ array();
			return;
		}
		$a = cast("string[int]", $_REQUEST[$this->_name]);
		$this->selected_values = array();
		foreach($a as $s){
			$i = (int) $s;
			if( 0 <= $i && $i < count($this->values) )
				$this->setSelectedValue($this->values[$i], TRUE);
		}
	}
	
	
	/**
	 * Appends an entry to the list.
	 * @param string $display Displayes description.
	 * @param Comparable $value Associated value.
	 */
	function addEntry($display, $value)
	{
		$this->displays[] = $display;
		$this->values[] = $value;
	}
	
	
	/**
	 * Whether currently selected values have to be displayed first in the list.
	 * If not set (default), entries are listed in the order of definition.
	 * @param boolean $enable
	 */
	function listSelectedFirst($enable)
	{
		$this->list_selected_first = $enable;
	}
	
	
	/**
	 * Creates and empty list of values.
	 * @param ContainerInterface $form Container form or panel.
	 * @param string $name Name attribute.
	 * @param int $size How many lines are visible.
	 */
	function __construct($form, $name, $size)
	{
		parent::__construct($form, $name);
		$this->size = $size;
		$this->selected_values = array();
	}
	
}
