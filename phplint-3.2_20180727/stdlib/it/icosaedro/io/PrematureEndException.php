<?php

namespace it\icosaedro\io;

require_once __DIR__ . "/../../../all.php";

/**
 * End of the file encountered prematurely while expecting more data.
 * @author Umberto Salsi <salsi@icosaedro.it>
 * @version $Date: 2016/01/18 09:40:09 $
 */
class PrematureEndException extends CorruptedException {
	
	/**
	 * Last bytes read, possibly empty or NULL.
	 * @var string
	 */
	public $lastBytesRead;
	
	/**
	 * Creates a new premature end exception.
	 * @param string $lastReadBytes Last chunk of read bytes, possibly empty or
	 * NULL, but in a number less than expected.
	 * @param string $message
	 */
	public function __construct($lastReadBytes, $message) {
		parent::__construct($message);
		$this->lastBytesRead = $lastReadBytes;
	}
	
}
