<?php

namespace it\icosaedro\web\controls;

require_once __DIR__ . "/../../../../all.php";

use it\icosaedro\utils\DateTimeTZ;
use it\icosaedro\web\Html;
use it\icosaedro\web\Input;
use InvalidArgumentException;
use OutOfRangeException;

/**
 * HTML date and time entry control. Generates an entry box where the user can
 * enter a Gregorian date and time with time zone. Dates are internally
 * represented with {@link it\icosaedro\utils\DateTimeTZ}. The range of the
 * allowed values can be specified. The inherited setValue() and getValue()
 * methods provide access to the raw, unvalidated value of the control.
 * The setDateTimeTZ() and parse() methods provide a more specific type-safe
 * programming interface.
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
 * @version $Date: 2018/06/10 10:21:16 $
 */
class DateTime extends Line {
	
	/*
	 * Implementation note 1.
	 * ----------------------
	 * Some browser still does not support HTML5 type=datetime-local and keeps
	 * presenting a type=text field instead. HTML5 compliant browsers behaves
	 * as follows:
	 * - The value of the field must be set with a date time UTC without TZ,
	 *   for example "2018-04-10T12:00:00.000".
	 * - The browser converts the UTC datetime into locale datetime.
	 * - The browser removes redundant zeroes and allows the user to set the
	 *   value within the given range and step.
	 * - On submit, the browser converts back to UTC without TZ.
	 * The workaround proposed here works as follows:
	 * - The value of the field is set to UTC without TZ as above.
	 * - Client side, a JS routine (see render() method) detects if the browser
	 *   supports datetime-local.
	 *   If not, it parses the field and converts to locale date time without TZ
	 *   and sets an hidden field with the locale TZ.
	 * - On submit (see retrieve() method) we detect server side a TZ hidden
	 *   field is present and then we adjust back the value applying the reverse
	 *   TZ offset to get back the UTC date time.
	 * The user still has to enter a date time in ISO-8601 format, but at least
	 * it may use its own TZ.
	 */
	
	/** Max step is 1 day (ms). */
	const STEP_MAX = 86400000;
	
	/**
	 * @var DateTimeTZ
	 */
	private $min, $max;
	
	/**
	 * Step (milliseconds).
	 * @var int
	 */
	private $step = 1;
	
	/**
	 * Returns the distance of the given date from the previous step mark.
	 * That is, the remainder between the time distance of $dtz from min
	 * divided by step. This could overflow even on 64-bits systems with very
	 * large time ranges and very large steps, so we must take some care.
	 * So, we split products and additions with these rules:
	 *     (a*b)%m == ((a%m)*(b%m))%m   and
	 *     (a+b)%m == ((a%m)+(b%m))%m
	 * with special care to the case of negative terms. In this way the larger
	 * value we can get is m*m. Being m the step, and having limited the step
	 * to 86400000 ms, this product does not overflow the float precision
	 * 2^53-1.
	 * @param DateTimeTZ $dtz
	 */
	private function remainder($dtz)
	{
		$a = 60000;
		$b = $dtz->getMinutesSince($this->min);
		$c = $dtz->getDateTime()->getMilliseconds() - $this->min->getDateTime()->getMilliseconds();
		$m = $this->step;
		// Basically returns ($a*$b+$c) % $m but preventing overflow:
		if( $c < 0 )
			$c = $m + $c % $m;
		return ( (int) fmod(($a % $m) * ($b % $m), $m) + $c % $m) % $m;
	}
	
	/**
	 * Retrieves the current value of this control.
	 * @return DateTimeTZ Value successfully parsed, in the range and step.
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
				$dtz = DateTimeTZ::parse("$s+00:00");
			} catch (InvalidArgumentException $e) {
				throw new ParseException($this, ParseException::REASON_INVALID);
			} catch (OutOfRangeException $e) {
				throw new ParseException($this, ParseException::REASON_RANGE);
			}
			if( $this->min->compareTo($dtz) > 0
			|| $this->max->compareTo($dtz) < 0 )
				throw new ParseException($this, ParseException::REASON_RANGE);
			if( $this->remainder($dtz) != 0 )
				throw new ParseException($this, ParseException::REASON_STEP);
			return $dtz;
		}
	}
	
	/**
	 * Set the value. Silently forces the value to the current range and step.
	 * @param DateTimeTZ $dtz Date and time to set, possibly NULL.
	 */
	function setDateTimeTZ($dtz)
	{
		if( $dtz === NULL ){
			$this->setValue(NULL);
			return;
		}
		if( $dtz->compareTo($this->min) < 0 )
			$dtz = $this->min;
		else if( $dtz->compareTo($this->max) > 0 )
			$dtz = $this->max;
		else {
			$r = $this->remainder($dtz);
			if( $r != 0 ){
				// Adjust to the closer step:
				if( $r < ($this->step >> 1))
					$dtz = $dtz->addTime(0, 0, -$r);
				else {
					$dtz = $dtz->addTime(0, 0, $this->step - $r);
					if( $dtz->compareTo($this->max) > 0 )
						$dtz = $dtz->addTime(0, 0, -$this->step);
				}
			}
		}
		$this->setValue("".$dtz->toTZ(0)->getDateTime());
	}
	
	/**
	 * Set range and time step for the parse() method.
	 * @param DateTimeTZ $min Minimum allowed date.
	 * @param DateTimeTZ $max Maximum allowed date.
	 * @param int $step Allowed step (ms). Must be in the range [1,self::STEP_MAX].
	 * @throws InvalidArgumentException Min is greater than max. Step out of the
	 * allowed range.
	 */
	function setMinMaxStep($min, $max, $step)
	{
		if( !($min->compareTo($max) <= 0 && 1 <= $step && $step <= self::STEP_MAX) )
			throw new InvalidArgumentException("min=$min, max=$max, step=$step");
		$this->min = $min;
		$this->max = $max;
		$this->step = $step;
		$value = $this->getValue();
		if( strlen($value) > 0 ){
			try {
				$dtz = DateTimeTZ::parse("$value+00:00");
			} catch (InvalidArgumentException $e) {
				return;
			} catch (OutOfRangeException $e) {
				return;
			}
			$this->setValue("".$dtz->toTZ(0)->getDateTime());
		}
	}
	
	/**
	 * Returns the current range and step.
	 * @param DateTimeTZ & $min Minimum allowed date.
	 * @param DateTimeTZ & $max Maximum allowed date.
	 * @param int & $step Allowed step (ms).
	 */
	function getMinMaxStep(/*. return .*/ & $min, /*. return .*/ & $max, /*. return .*/ & $step)
	{
		$min = $this->min;
		$max = $this->max;
		$step = $this->step;
	}
	
	/**
	 * How many times the workaround script as been used.
	 * See implementation note 1.
	 * @var int
	 */
	private static $workaround_script_no = 0;
	
	/**
	 * Send this control to the standard output.
	 * @return void
	 */
	function render()
	{
		echo "<input type=datetime-local name='", $this->_name,
				"' min='", $this->min->toTZ(0)->getDateTime(),
				"' max='", $this->max->toTZ(0)->getDateTime(),
				"' step='", sprintf("%.3f", $this->step / 1000),
				"' value='", Html::text($this->getValue()),
				"' ", $this->_add_attributes, ">";
		// See implementation note 1.
		self::$workaround_script_no++;
		if( self::$workaround_script_no == 1 ){
			/*
			 * Adjusts UTC date time to local date time of a datetime-local
			 * input control on browser that do not support this control.
			 * Set the phplint_workaround_tz_min hidden field to warn server
			 * the date time it will retrieve is local.
			 */
?>
<input type=hidden name=phplint_workaround_tz_min>
<script>
	function phplint_workaround_datetime(c){
		if(c.type == 'datetime-local')
			return;
		var ts_ms = Date.parse(c.value + "+00:00");
		if( ts_ms == NaN )
			return;
		var tz_min = - (new Date).getTimezoneOffset();
		document.getElementsByName('phplint_workaround_tz_min')[0].value = tz_min;
		var shift = new Date(ts_ms + 60000 * tz_min);
		var iso = shift.toISOString();
		var s = iso.substring(0, iso.length - 1);
		if( /:00\.000$/.test(s) )
			s = s.substring(0, s.length - 7);
		else if( /\.000$/.test(s) )
			s = s.substring(0, s.length - 4);
		c.value = s;
	}
</script>
<?php
		}
		echo "<script>phplint_workaround_datetime(document.getElementsByName('". $this->_name ."')[0]);</script>";
	}
	
	
	function retrieve()
	{
		parent::retrieve();
		// See implementation note 1: convert local time to UTC:
		$tz = (int) Input::getLine("phplint_workaround_tz_min", "0");
		if( !($tz != 0 && -1439 <= $tz && $tz <= 1439) )
			return;
		try {
			$dtz = DateTimeTZ::parse($this->getValue() . "+00:00");
		} catch (InvalidArgumentException $e) {
			return;
		} catch (OutOfRangeException $e) {
			return;
		}
		$dtz = $dtz->addTime(0, -$tz);
		$this->setValue("".$dtz->toTZ(0)->getDateTime());
	}
	
	
	/**
	 * The default range is from year 2000 up to year 2099 with 60000 ms of
	 * step (1 minute).
	 * @param ContainerInterface $form The form.
	 * @param string $name Name of this control.
	 */
	function __construct($form, $name)
	{
		parent::__construct($form, $name);
		$min = DateTimeTZ::parse("2000-01-01T00:00+00:00");
		$max = DateTimeTZ::parse("2099-12-31T23:59+00:00");
		$this->setMinMaxStep($min, $max, 60000);
	}
	
	
	function save() {
		parent::save();
		$this->_form->setData($this->_name .".min", $this->min);
		$this->_form->setData($this->_name .".max", $this->max);
		$this->_form->setData($this->_name .".step", $this->step);
	}
	
	
	function resume() {
		parent::resume();
		$this->min = cast(DateTimeTZ::class, $this->_form->getData($this->_name .".min"));
		$this->max = cast(DateTimeTZ::class, $this->_form->getData($this->_name .".max"));
		$this->step = cast("int", $this->_form->getData($this->_name .".step"));
	}
	
}
