<?php

/*. require_module 'core'; .*/

namespace it\icosaedro\io;

/**
 *
 * Signals a generic I/O exception. Includes: invalid path; invalid file
 * name; access denied to the file or to some part of the path.
 *
 * @author Umberto Salsi <salsi@icosaedro.it>
 * @version $Date: 2016/01/26 12:26:56 $
 */
class IOException extends \Exception {}
