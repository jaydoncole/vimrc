<?php

namespace it\icosaedro\web\controls;

require_once __DIR__ . "/../../../../all.php";

use Exception;

/**
 * Control failed to parse incoming data.
 * 
 * @author Umberto Salsi <salsi@icosaedro.it>
 * @copyright Copyright 2018 by icosaedro.it di Umberto Salsi
 * @version $Date: 2018/04/09 13:34:18 $
 */
class ParseException extends Exception {
	
	/** No data at all from the postback. */
	const REASON_MISSING = 0;
	
	/** Empty field. */
	const REASON_EMPTY = 1;
	
	/** Invalid format. */
	const REASON_INVALID = 2;
	
	/** Out of the range. */
	const REASON_RANGE = 3;
	
	/** Not a valid quantization step. */
	const REASON_STEP = 4;
	
	private static $REASON_DESCRIPTION = [
		0 => "browser did not returned any data for the field",
		1 => "the field is empty",
		2 => "invalid format",
		3 => "out of the range",
		4 => "not a valid step"
	];
	
	/**
	 * Control that thow this exception.
	 * @var Control
	 */
	private $control;
	
	/**
	 * Reason of the exception - one of the REASON_* constants.
	 * @var int
	 */
	private $reason = 0;
	
	/**
	 * Returns the control that thow this exception.
	 * @return Control
	 */
	function getControl()
	{
		return $this->control;
	}
	
	/**
	 * Returns the reason of the failed parsing.
	 * @return int One othe REASON_* constants.
	 */
	function getReason()
	{
		return $this->reason;
	}
	
	/**
	 * Creates a new control parse failed exception.
	 * @param Control $control Control that thow this exception.
	 * @param int $reason One of the REASON_* constants.
	 */
	function __construct($control, $reason)
	{
		$this->control = $control;
		$this->reason = $reason;
		parent::__construct($this->control->getName()
			. ": " . self::$REASON_DESCRIPTION[$this->reason]);
	}
}
