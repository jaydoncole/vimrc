<?php

namespace it\icosaedro\web\controls;

require_once __DIR__ . "/../../../../all.php";

use it\icosaedro\utils\Date as XDATE;

use InvalidArgumentException;
use OutOfRangeException;

/**
 * HTML date entry control. Generates an entry box where the user can
 * enter a Gregorian date. Dates are internally represented with
 * {@link it\icosaedro\utils\Date}. The range of the allowed values can be
 * specified. The inherited setValue() and getValue() methods provide access to
 * the raw, unvalidated value of the control. The setDate() and parse() methods
 * provide a more specific type-safe programming interface.
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
 * @version $Date: 2018/06/10 10:20:58 $
 */
class Date extends Line {
	
	/** @var XDATE */
	private $min, $max;
	
	/**
	 * Set the date range for the parse() method.
	 * @param XDATE $min
	 * @param XDATE $max
	 * @throws InvalidArgumentException Max date must be greater or equal than
	 * min date.
	 */
	function setMinMax($min, $max)
	{
		if( $min->compareTo($max) > 0 )
			throw new InvalidArgumentException("min date greater than max");
		$this->min = $min;
		$this->max = $max;
	}
	
	/**
	 * Returns the current min, max values.
	 * @param XDATE & $min
	 * @param XDATE & $max
	 */
	function getMinMax(/*. return .*/ & $min, /*. return .*/ & $max)
	{
		$min = $this->min;
		$max = $this->max;
	}
	
	/**
	 * Set the current value of the control. If outside the allowed range,
	 * the nearest value is assumed instead.
	 * @param XDATE $d Value to set, possibly NULL.
	 */
	function setDate($d)
	{
		if( $d === NULL ){
			$this->setValue(NULL);
			return;
		}
		if( $this->min->compareTo($d) > 0 )
			$d = $this->min;
		else if( $this->max->compareTo($d) < 0 )
			$d = $this->max;
		$this->setValue($d->__toString());
	}
	
	/**
	 * Retrieves the current value of this control.
	 * @return XDATE
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
				$value = XDATE::parse($s);
			} catch (InvalidArgumentException $e) {
				throw new ParseException($this, ParseException::REASON_INVALID);
			} catch (OutOfRangeException $e) {
				throw new ParseException($this, ParseException::REASON_INVALID);
			}
			if( $this->min->compareTo($value) > 0
			|| $this->max->compareTo($value) < 0 )
				throw new ParseException($this, ParseException::REASON_RANGE);
			return $value;
		}
	}
	
	/**
	 * Send this control to the standard output.
	 * @return void
	 */
	function render()
	{
		echo "<input type=date name='", $this->_name,
				"' value='", $this->getValue(),
				"' min='", $this->min,
				"' max='", $this->max,
				"' step=1 ",
				$this->_add_attributes, ">";
	}
	
	/**
	 * Builds a new date control and registers to receive events.
	 * @param ContainerInterface $form Container form or panel. Here this new
	 * control registers itself to receive events later, and here it saves and
	 * resumes its state.
	 * @param string $name The "name" attribute of this control. Must be not empty;
	 * any PHP ID allowed, including fully qualified name with namespace, plus
	 * dot.
	 * @throws InvalidArgumentException Invalid name. Duplicated name.
	 */
	function __construct($form, $name) {
		parent::__construct($form, $name);
		$this->setMinMax(new XDATE(2000,1,1), new XDATE(2099,12,31));
	}
	
	
	function save() {
		parent::save();
		$this->_form->setData($this->_name .".min", $this->min);
		$this->_form->setData($this->_name .".max", $this->max);
	}
	
	
	function resume() {
		parent::resume();
		$this->min = cast(XDATE::class, $this->_form->getData($this->_name .".min"));
		$this->max = cast(XDATE::class, $this->_form->getData($this->_name .".max"));
	}
	
}
