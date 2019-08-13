<?php

/*. require_module 'core'; .*/

/**
	Exception thrown if the magic function cast(T,V) failed the test
	because the passed value V does not match the expected type T.
	@author Umberto Salsi <salsi@icosaedro.it>
	@version $Date: 2016/01/26 12:26:56 $
*/
/*. unchecked .*/ class CastException extends Exception {}
