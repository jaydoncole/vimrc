<?php

/*. require_module 'core'; .*/

namespace it\icosaedro\sql;

/**
	Any exception thrown by SQL related classes.
	Includes: failed connection to the remote data base,
	missing data base,
	invalid login, 
	invalid SQL syntax,
	invalid arguments provided,
	accessing data from an already closed connection,
	invalid usage of the provided API.

	@author Umberto Salsi <salsi@icosaedro.it>
	@version $Date: 2016/01/26 12:26:57 $
*/
class SQLException extends \Exception {}
