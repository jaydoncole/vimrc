<?php

namespace it\icosaedro\web\controls;

require_once __DIR__ . "/../../../../all.php";

use InvalidArgumentException;
use it\icosaedro\bignumbers\BigFloat;
use it\icosaedro\web\Html;

/**
 * HTML currency entry control. Values are internally represented as
 * {@link it\icosaedro\bignumbers\BigFloat}. See the description of the
 * constructor for the default range, precision and formatting options.
 * The inherited setValue() and getValue() methods allows to set and retrieve
 * the current raw, unvalidated value. The setBigFloat() and parse() methods
 * provide a type-safe programming interface to set and retrieve the current
 * value.
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
 * @version $Date: 2018/06/10 10:20:34 $
 */
class Currency extends Line {
	
	/** @var BigFloat */
	private $min, $max, $step;
	
	/** @var int */
	private $decimals = 0;
	
	/** @var string */
	private $dec_sept, $thousands_sept;
	
	/**
	 * Set range and step the parse() method will use to check the validity of
	 * the entered data. For example, to match any SQL NUMERIC(11,2) type the
	 * arguments could be set to
	 * new BigFloat("-999999999.99"),
	 * new BigFloat("+999999999.99") and
	 * new BigFloat("0.01") respectively.
	 * @param BigFloat $min
	 * @param BigFloat $max
	 * @param BigFloat $step
	 * @throws InvalidArgumentException Max must be greater or equal than min.
	 * Step is zero or negative.
	 */
	function setMinMaxStep($min, $max, $step)
	{
		if( $min->compareTo($max) > 0 || $step->sign() <= 0 )
			throw new InvalidArgumentException("min=$min, max=$max, step=$step");
		$this->min = $min;
		$this->max = $max;
		$this->step = $step;
	}
	
	/**
	 * Returns min, max and step values.
	 * @param BigFloat & $min
	 * @param BigFloat & $max
	 * @param BigFloat & $step
	 */
	function getMinMaxStep(/*. return .*/ & $min, /*. return .*/ & $max, /*. return .*/ & $step)
	{
		$min = $this->min;
		$max = $this->max;
		$step = $this->step;
	}
	
	/**
	 * Set how values must be formatted and parsed.
	 * @param int $decimals Number of decimal digits. Ignored if negative.
	 * @param string $dec_sept  Separator string between integral part and
	 * fractional part.
	 * @param string $thousands_sept Separator string between thousands.
	 */
	function setFormat($decimals, $dec_sept, $thousands_sept)
	{
		$this->decimals = $decimals;
		$this->dec_sept = $dec_sept;
		$this->thousands_sept = $thousands_sept;
	}
	
	/**
	 * Returns current formatting options.
	 * @param int    & $decimals
	 * @param string & $dec_sept
	 * @param string & $thousands_sept
	 */
	function getFormat(/*. return .*/ & $decimals, /*. return .*/ & $dec_sept, /*. return .*/ & $thousands_sept)
	{
		$decimals = $this->decimals;
		$dec_sept = $this->dec_sept;
		$thousands_sept = $this->thousands_sept;
	}
	
	function save()
	{
		parent::save();
		$this->_form->setData($this->_name .".min", $this->min);
		$this->_form->setData($this->_name .".max", $this->max);
		$this->_form->setData($this->_name .".step", $this->step);
		$this->_form->setData($this->_name .".decimals", $this->decimals);
		$this->_form->setData($this->_name .".dec_sept", $this->dec_sept);
		$this->_form->setData($this->_name .".thousands_sept", $this->thousands_sept);
	}
	
	function resume()
	{
		parent::resume();
		$this->min = cast(BigFloat::class, $this->_form->getData($this->_name .".min"));
		$this->max = cast(BigFloat::class, $this->_form->getData($this->_name .".max"));
		$this->step = cast(BigFloat::class, $this->_form->getData($this->_name .".step"));
		$this->decimals = cast("int", $this->_form->getData($this->_name .".decimals"));
		$this->dec_sept = cast("string", $this->_form->getData($this->_name .".dec_sept"));
		$this->thousands_sept = cast("string", $this->_form->getData($this->_name .".thousands_sept"));
	}
	
	/**
	 * Parses and returns the current value of this control.
	 * @return BigFloat
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
				$bf = BigFloat::parse($s, $this->dec_sept, $this->thousands_sept);
			} catch (InvalidArgumentException $ex) {
				throw new ParseException($this, ParseException::REASON_INVALID);
			}
			if( $this->min->compareTo($bf) > 0
			|| $this->max->compareTo($bf) < 0 )
				throw new ParseException($this, ParseException::REASON_RANGE);
			$bf->sub($this->min)->div_rem($this->step, 0, $rem);
			if( $rem->sign() != 0 )
				throw new ParseException($this, ParseException::REASON_STEP);
			return $bf;
		}
	}
	
	
	/**
	 * Set the current value of the control. If outside the allowed range or step,
	 * the nearest value is assumed instead.
	 * @param BigFloat $bf Value to set, possibly NULL.
	 */
	function setBigFloat($bf)
	{
		if( $bf === NULL ){
			$this->setValue(NULL);
			return;
		}
		if( $this->min->compareTo($bf) > 0 )
			$bf = $this->min;
		else if( $this->max->compareTo($bf) < 0 )
			$bf = $this->max;
		$i = $bf->sub($this->min)->div_rem($this->step, 0, $rem);
		if( $rem->sign() != 0 )
			$bf = $i->mul($this->step);
		$this->setValue($bf->format($this->decimals, $this->dec_sept, $this->thousands_sept));
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
	 * minimum value: 0; maximum value: 999,999,999.99; step: 0.01;
	 * decimals separator: "."; thousand separator: ",".
	 * @param ContainerInterface $form Container form or panel.
	 * @param string $name The "name" attribute of this control.
	 * @throws InvalidArgumentException Invalid name. Duplicated name.
	 */
	function __construct($form, $name)
	{
		parent::__construct($form, $name);
		$this->setMinMaxStep(new BigFloat("0"), new BigFloat("999999999.99"), new BigFloat("0.01"));
		$this->setFormat(2, ".", ",");
	}
	
}
