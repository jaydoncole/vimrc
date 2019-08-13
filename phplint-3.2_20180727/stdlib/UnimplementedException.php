<?php
/*. require_module 'core'; .*/

/**
 * Unimplemented functions and methods may throw this exception.
 * Typical code sample:
 * <pre>
 * throw new UnimplementedException(__FUNCTION__);  // inside function
 * throw new UnimplementedException(__METHOD__);  // inside method
 * </pre>
 * @author salsi
 */
/*. unchecked .*/ class UnimplementedException extends Exception {
	
	function __construct($message = "") {
		parent::__construct($message);
	}
	
}
