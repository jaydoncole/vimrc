<?php

namespace it\icosaedro\web\controls;

require_once __DIR__ . "/../../../../all.php";

use InvalidArgumentException;
use it\icosaedro\web\Html;

/**
 * HTML time input control. The time is represented as number of milliseconds
 * after midnight; the range of the allowed values and the step can be specified.
 * Generates an entry box where the user can set a time as hours, minutes ans
 * seconds. The box can be left empty or it can be emptied by the user.
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
 * @version $Date: 2018/04/09 13:35:59 $
 */
class Time extends Spinner {
	
	/**
	 * Maximum time value as milliseconds after midnight.
	 */
	const VALUE_MAX = 86399000;
	
	/**
	 * Set minimum, maximum and step values. All times are in ms.
	 * The allowed ranges are:
	 * <br>0 &le; min &le; max &le; {@link self::VALUE_MAX}
	 * <br>1 &le; step &le; 86400000
	 * <br>The default values are: min=0, max={@link self::VALUE_MAX},
	 * step=60000.
	 * <br>For example, to allow to select a time in the range from 09:00
	 * (included) up to 17:00 (excluded) with granularity of 15 minutes
	 * (09:00, 09:15, 09:30, ..., 16:45), use the values 9*60*60*1000,
	 * 17*60*60*1000-1, 15*60*1000 respectively.
	 * @param int $min Minimum time after midnight (ms).
	 * @param int $max Maximum time after midnight (ms).
	 * @param int $step Step (ms).
	 * @throws InvalidArgumentException Minimum greater than maximum. Step is
	 * less than 1. Step value greater than one day minus 1 ms.
	 */
	public function setMinMaxStep($min, $max, $step)
	{
		if( !(0 <= $min && $min <= $max && $max <= self::VALUE_MAX
				&& $step < 86400000) )
			throw new InvalidArgumentException("min=$min, max=$max, step=$step");
		parent::setMinMaxStep($min, $max, $step);
	}
	
	/**
	 * Parse and return the current value of the control.
	 * @return int Time as number of ms after midnight.
	 * @throws ParseException Value missing, empty, invalid or out of the range.
	 */
	function parse()
	{
		$s = $this->getValue();
		if( $s === NULL )
			throw new ParseException($this, ParseException::REASON_MISSING);
		else if( $s === "" )
			throw new ParseException($this, ParseException::REASON_EMPTY);
		$a = explode(":", $s);
		if( !(2 <= count($a) && count($a) <= 3) )
			throw new ParseException($this, ParseException::REASON_INVALID);
		$h = (int) $a[0];
		$m = (int) $a[1];
		$ms = (count($a) >= 3)? (int) (1000 * (float) $a[2] + 0.5) : 0;
		if( !(0 <= $h && $h <= 23 && 0 <= $m && $m <= 59 && 0 <= $ms && $ms <= 59999) )
			throw new ParseException($this, ParseException::REASON_INVALID);
		$t = 3600000 * $h + 60000 * $m + $ms;
		if( !($this->min <= $t && $t <= $this->max) )
			throw new ParseException($this, ParseException::REASON_RANGE);
		if( ($t - $this->min) % $this->step != 0 )
			throw new ParseException($this, ParseException::REASON_STEP);
		return $t;
	}
	
	/**
	 * Returns the time formatted as "HH:MM:SS.SSS".
	 * @param int $t Time as number of ms after midnight.
	 * @return string Time in the format "HH:MM:SS.SSS".
	 */
	static function format($t)
	{
		$h = (int) ($t / 3600000);  $t -= 3600000 * $h;
		$m = (int) ($t / 60000);    $t -= 60000 * $m;
		$s = (int) ($t / 1000);     $t -= 1000 * $s;
		$ms = $t;
		return sprintf("%02d:%02d:%02d.%03d", $h, $m, $s, $ms);
	}
	
	/**
	 * Set the value.
	 * @param int $t Seconds after midnight. The value is silently constrained
	 * to the [min,max] range and to the nearest step mark.
	 * @return void
	 */
	function setInt($t)
	{
		if( $t < $this->min )
			$t = $this->min;
		if( $t > $this->max )
			$t = $this->max;
		$t = $this->min + $this->step * (int) (($t - $this->min + 0.5 * $this->step) / $this->step);
		$this->setValue(self::format($t));
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
		$this->min = cast("int", $this->_form->getData($this->_name .".min"));
		$this->max = cast("int", $this->_form->getData($this->_name .".max"));
	}
	
	/**
	 * Send this control to the standard output.
	 * @return void
	 */
	function render()
	{
		$s = $this->getValue();
		if( strlen($s) > 0 ){
			try {
				$t = $this->parse();
				$s = self::format($t);
			} catch(ParseException $e) {
				// Keep current value, whichever it is.
			}
		}
		$step = sprintf("%d.%03d", (int) ($this->step / 1000), $this->step % 1000);
		echo "<input type=time name='", $this->_name,
				"' min='", self::format($this->min),
				"' max='", self::format($this->max),
				"' step=$step", // seconds with decimals ms
				" value='", Html::text($s),
				"' ", $this->_add_attributes, "'>";
	}
	
	/**
	 * Creates a new time control. The default range is the whole day with
	 * 1 minute step, then ranging from 00:00 up to 23:59.
	 * @param ContainerInterface $form
	 * @param string $name
	 */
	function __construct($form, $name) {
		parent::__construct($form, $name);
		$this->setMinMaxStep(0, self::VALUE_MAX, 60000);
	}
	
}
