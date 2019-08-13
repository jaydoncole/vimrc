<?php

namespace it\icosaedro\web\controls;

require_once __DIR__ . "/../../../../all.php";

use RuntimeException;
use InvalidArgumentException;

/**
 * Controls that can store form data and register nested controls must implement
 * this interface. This is the case of the Form and Panel classes.
 */
interface ContainerInterface {
	
	/**
	 * Returns the name of the storage. This name is used only to build univocal
	 * data keys IDs by joining the names of the containers, example:
	 * "Form.panel1.thedata".
	 * @return string
	 */
	function getName();
	
	/**
	 * Registers a nested control. Nested controls will then receive the save,
	 * resume, and retrieve events; handler methods in nested panels will also
	 * receive dispatching requests.
	 * @param Control $control
	 * @return void
	 * @throws InvalidArgumentException Duplicated control name.
	 */
	function addControl($control);
	
	/**
	 * Stores a name/value pair to be saved along with the form state.
	 * @param string $name Name of the data to store.
	 * @param mixed $value Value to store. Only data that can be serialized
	 * should be put here; resources are not serializable, for example, and are
	 * then retrieved as integer zero.
	 * @return void
	 */
	function setData($name, $value);
	
	/**
	 * Retrieves the value of a name/value pair saved along with the state of
	 * this form.
	 * @param string $name Name of the value.
	 * @param mixed $default_value Default value to return if missing.
	 * @return mixed
	 * @throws RuntimeException Missing data and no default value specified.
	 */
	function getData($name, $default_value = NULL);
}