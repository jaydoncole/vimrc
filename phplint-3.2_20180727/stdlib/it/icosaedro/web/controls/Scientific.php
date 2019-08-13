<?php

namespace it\icosaedro\web\controls;

require_once __DIR__ . "/../../../../all.php";

use InvalidArgumentException;
use it\icosaedro\containers\FloatClass;
use it\icosaedro\web\Html;

/**
 * HTML floating-point entry control.
 * 
 * @author Umberto Salsi <salsi@icosaedro.it>
 * @copyright Copyright 2018 by icosaedro.it di Umberto Salsi
 * @version $Date: 2018/04/09 13:17:00 $
 */
class Scientific extends Line {
	
	/** @var float */
	private $min = 0.0, $max = 0.0;
	
	/**
	 * Set range.
	 * @param float $min
	 * @param float $max
	 * @throws InvalidArgumentException Max must be greater or equal than min.
	 */
	function setMinMax($min, $max)
	{
		if( $min > $max )
			throw new InvalidArgumentException("min=$min, max=$max");
		$this->min = $min;
		$this->max = $max;
	}
	
	/**
	 * Returns current allowed range.
	 * @param float & $min
	 * @param float & $max
	 */
	function getMinMax(/*. return .*/ & $min, /*. return .*/ & $max)
	{
		$min = $this->min;
		$max = $this->max;
	}
	
	function save()
	{
		parent::save();
		$this->_form->setData($this->_name .".min", $this->min);
		$this->_form->setData($this->_name .".max", $this->max);
	}
	
	function resume()
	{
		parent::resume();
		$this->min = cast("float", $this->_form->getData($this->_name .".min"));
		$this->max = cast("float", $this->_form->getData($this->_name .".max"));
	}
	
	/**
	 * Parses and retrieves the current value of this control.
	 * @return float
	 * @throws ParseException
	 */
	function parse()
	{
		$s = $this->getValue();
		if( $s === NULL ){
			throw new ParseException($this, ParseException::REASON_MISSING);
		} else if( $s === "" ){
			throw new ParseException($this, ParseException::REASON_EMPTY);
		} else {
			try {
				$f = FloatClass::parse($s);
			} catch (InvalidArgumentException $ex) {
				throw new ParseException($this, ParseException::REASON_INVALID);
			}
			if( !($this->min <= $f && $f <= $this->max) )
				throw new ParseException($this, ParseException::REASON_RANGE);
			return $f;
		}
	}
	
	
	/**
	 * Set the current value of the control. If outside the allowed range, the
	 * nearest value is assumed instead.
	 * @param float $f Value to set.
	 */
	function setFloat($f)
	{
		if( $f < $this->min )
			$f = $this->min;
		else if( $f > $this->max )
			$f = $this->max;
		$this->setValue("$f");
	}
	
	
	/**
	 * Send this control to the standard output.
	 * @return void
	 */
	function render()
	{
		echo "<input type=text name='", $this->_name,
				"' value='", Html::text($this->getValue()),
				"' ", $this->_add_attributes, ">";
	}
	
	/**
	 * Builds a new control and registers to receive events. Defaults:
	 * minimum value: -INF; maximum value: INF.
	 * @param ContainerInterface $form Container form or panel.
	 * @param string $name The "name" attribute of this control.
	 * @throws InvalidArgumentException Invalid name. Duplicated name.
	 */
	function __construct($form, $name)
	{
		parent::__construct($form, $name);
		$this->setMinMax(-INF, INF);
	}
	
}
