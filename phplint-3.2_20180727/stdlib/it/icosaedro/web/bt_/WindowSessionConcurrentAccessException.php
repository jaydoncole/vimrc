<?php

namespace it\icosaedro\web\bt_;

/*. require_module 'core'; .*/

use Exception;

/**
 * Concurrent access to the same window session has been detected.
 * 
 * @author Umberto Salsi <salsi@icosaedro.it>
 * @copyright Copyright 2018 by icosaedro.it di Umberto Salsi
 * @version $Date: 2018/02/21 09:48:14 $
 */
class WindowSessionConcurrentAccessException extends Exception {}
