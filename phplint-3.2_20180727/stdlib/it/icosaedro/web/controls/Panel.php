<?php

namespace it\icosaedro\web\controls;

require_once __DIR__ . "/../../../../all.php";

use Exception;
use RuntimeException;
use InvalidArgumentException;

/**
 * Panel nested in a form. A panel may contain other nested panels. The Form
 * class takes care to invoke the retrieve and render methods recursively, in
 * unspecified order.
 * @author Umberto Salsi <salsi@icosaedro.it>
 * @copyright Copyright 2018 by icosaedro.it di Umberto Salsi
 * @version $Date: 2018/06/10 10:21:46 $
 */
abstract class Panel extends Control implements ContainerInterface {
	
	/**
	 * Nested controls.
	 * @var Control[string]
	 */
	private $controls;
	
	/**
	 * Create a new panel.
	 * @param ContainerInterface $form
	 * @param string $name Name of this panel.
	 */
	function __construct($form, $name)
	{
		$this->controls = array();
		parent::__construct($form, $form->getName() . ".$name");
	}
	
	/**
	 * Stores a name/value pair to be saved along with the form state.
	 * @param string $name Name of the data to store.
	 * @param mixed $value Value to store. Only data that can be serialized
	 * should be put here; resources are not serializable, for example, an are
	 * then retrieved az integer zero.
	 */
	function setData($name, $value)
	{
		$this->_form->setData($this->_name . ".$name", $value);
	}
	
	/**
	 * Retrieves the value of a name/value pair saved along the state of this form.
	 * @param string $name Name of the value.
	 * @param mixed $default_value Default value to return if missing.
	 * @return mixed
	 * @throws RuntimeException Missing data and no default value specified.
	 */
	function getData($name, $default_value = NULL)
	{
		$key = $this->_name . ".$name";
		if(func_num_args() == 1)
			return $this->_form->getData($key);
		else
			return $this->_form->getData($key, $default_value);
	}
	
	/**
	 * Implements the container interface that allows nested controls to register
	 * themselves under this container. Client code does not have normally need
	 * to invoke this method.
	 * @param Control $control Nested control to register.
	 */
	function addControl($control)
	{
		$name = $control->getName();
		if( isset($this->controls[$name]) )
			throw new RuntimeException("duplicated control: $name");
		$this->controls[$name] = $control;
	}
	
	function save()
	{
		foreach($this->controls as $c)
			$c->save();
	}
	
	function resume()
	{
		foreach($this->controls as $c)
			$c->resume();
	}
	
	function retrieve()
	{
		foreach($this->controls as $c)
			$c->retrieve();
	}
	
	/**
	 * Sends to standard output a button. The name of the handling method and its
	 * arguments can be specified; arguments are serialized and added to the
	 * state of the form.
	 * @param string $text Caption of the button.
	 * @param string $func Name of the method of this object to invoke if that
	 * button is clicked; arguments may follow.
	 * @throws InvalidArgumentException The handler method does not exist.
	 */
	function button($text, $func /*. , args .*/)
	{
		$a = func_get_args();
		$a[1] = $this->_name . " " . (string) $a[1];
		$cb = /*. (mixed[int]) .*/ array();
		$cb[0] = $this->_form;
		$cb[1] = "button";
		try {
			call_user_func_array($cb, $a);
		} catch (InvalidArgumentException $e){
			throw $e;
		} catch (Exception $e) {
			throw new RuntimeException($e->getMessage(), 1, $e);
		}
	}
	
	/**
	 * Sends to standard output an anchor. The name of the handling method and its
	 * arguments can be specified; arguments are serialized and added to the
	 * state of the form.
	 * @param string $text_html Link caption.
	 * @param string $func Name of the method of this object to invoke if that
	 * link is clicked; arguments may follow.
	 * @throws InvalidArgumentException The handler method does not exist.
	 */
	function anchor($text_html, $func /*. , args .*/)
	{
		$a = func_get_args();
		$a[1] = $this->_name . " " . (string) $a[1];
		$cb = /*. (mixed[int]) .*/ array();
		$cb[0] = $this->_form;
		$cb[1] = "anchor";
		try {
			call_user_func_array($cb, $a);
		} catch (InvalidArgumentException $e){
			throw $e;
		} catch (Exception $e) {
			throw new RuntimeException($e->getMessage(), 1, $e);
		}
	}

}
