<?php

namespace it\icosaedro\web\controls;

require_once __DIR__ . "/../../../../all.php";

use InvalidArgumentException;

/**
 * HTML integral number input control. Generates an entry box where the user can
 * enter an integer number. The range of the allowed values and the step can be
 * specified. The box can be left empty or it can be emptied by the user.
 * The setValue() and getValue() methods inherited from the Line class provide
 * access to the current raw value as set by the program or retrieved from the
 * postback; a NULL value on a postback means the data was missing from the
 * request. The setInt() and parse() methods provide a type-safe programming
 * interface to set and retrieve the current value.
 * 
 * <p>Validation of incoming data should then be performed as follows:
 * <ol>
 * <li>Check if the data is missing from the postback, that is getValue()
 * returns NULL. This should never happen in normal situations and, where not
 * expected, it may indicate corrupted pages or corrupted retrieved data.
 * Application may silently handle this case as unset field (see next point).</li>
 * <li>Check if the field is empty. This means the user emptied the box.</li>
 * <li>Invoke the parse() method to parse and retrieve the value, where a
 * value is available. If the field is mandatory, this is the only necessary
 * step to do.</li>
 * </ol>
 * 
 * @author Umberto Salsi <salsi@icosaedro.it>
 * @copyright Copyright 2018 by icosaedro.it di Umberto Salsi
 * @version $Date: 2018/04/09 13:35:28 $
 */
class Spinner extends Line {
	
	protected $min = 0, $max = 100, $step = 1;
	
	/**
	 * Retrieves the value of this control from the request. The value is always
	 * constrained to the current range and step or an exception is thrown.
	 * @return int
	 * @throws ParseException Value missing, empty, invalid, out of the range
	 * or invalid step.
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
				$res = \it\icosaedro\containers\IntClass::parse($s);
			} catch (InvalidArgumentException $ex) {
				throw new ParseException($this, ParseException::REASON_INVALID);
			}
			if( !($this->min <= $res && $res <= $this->max
				&& ($res - $this->min) % $this->step == 0) )
				throw new ParseException($this, ParseException::REASON_STEP);
			return $res;
		}
	}
	
	/**
	 * Set the value.
	 * @param int $value The value is silently constrained to the [min,max]
	 * range and to the nearest step mark.
	 * @return void
	 */
	function setInt($value)
	{
		if( $value < $this->min )
			$value = $this->min;
		if( $value > $this->max )
			$value = $this->max;
		$value = $this->min + $this->step * (int) (($value - $this->min + 0.5 * $this->step) / $this->step);
		$this->setValue("$value");
	}
	
	/**
	 * Set minimum, maximum and step values. The defaults are 0, 100, 1
	 * respectively. The current value, if a valid one is available, is
	 * constrained to the nearest limit and nearest step.
	 * @param int $min Minimum value.
	 * @param int $max Maximum value. Must be greater or equal than the minimum.
	 * @param int $step Must be at least 1.
	 * @throws InvalidArgumentException Minimum greater than maximum. Step is
	 * less than 1.
	 */
	public function setMinMaxStep($min, $max, $step)
	{
		if( !($min <= $max && $step >= 1) )
			throw new InvalidArgumentException("min=$min, max=$max, step=$step");
		$this->min = $min;
		$this->max = $max;
		$this->step = $step;
		if( strlen($this->getValue()) == 0 )
			return;
		try {
			$value = $this->parse();
		}
		catch(ParseException $e){
			return;
		}
		$this->setInt($value);
	}
	
	/**
	 * Returns the current minimum, maximum and step values.
	 * @param int & $min
	 * @param int & $max
	 * @param int & $step
	 */
	public function getMinMaxStep(/*. return .*/ &$min, /*. return .*/ &$max, /*. return .*/ &$step)
	{
		$min = $this->min;
		$max = $this->max;
		$step = $this->step;
	}
	
	/**
	 * Send this control to the standard output.
	 * @return void
	 */
	function render()
	{
		echo "<input type=number name='", $this->_name,
				"' min=", $this->min,
				" max=", $this->max,
				" step=", $this->step,
				" value=", $this->getValue(),
				" ", $this->_add_attributes, ">";
	}
	
	
	function save() {
		parent::save();
		$this->_form->setData($this->_name .".min", $this->min);
		$this->_form->setData($this->_name .".max", $this->max);
		$this->_form->setData($this->_name .".step", $this->step);
	}
	
	
	function resume() {
		parent::resume();
		$this->min = cast("int", $this->_form->getData($this->_name .".min"));
		$this->max = cast("int", $this->_form->getData($this->_name .".max"));
		$this->step = cast("int", $this->_form->getData($this->_name .".step"));
	}
	
}
