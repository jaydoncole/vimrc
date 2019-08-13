<?php

namespace it\icosaedro\sql\mysql;

require_once __DIR__ . '/../../../../all.php';

use it\icosaedro\sql\SQLException;


/**
	MySQL specific implementation of the prepared statement.
	@author Umberto Salsi <salsi@icosaedro.it>
	@version $Date: 2018/03/30 16:52:59 $
*/
class PreparedStatement extends \it\icosaedro\sql\PreparedStatement {
	
	/*. forward void function __construct(Driver $db, string $cmd); .*/

	/**
	 * @param Driver $db
	 * @param string $cmd
	 * @return void
	 */
	function __construct($db, $cmd)
	{
		# This method merely enforces type checking over the $db param,
		# so that it is a MySQL driver.
		parent::__construct($db, $cmd);
	}

}
