<?php

namespace it\icosaedro\io\tar;

require_once __DIR__ . "/../../../../all.php";

use it\icosaedro\io\IOException;

/**
 * Malicious path detected in the TAR file being read. The malicious path extends
 * above the boundary directory set (normally, the directory where the files have
 * to be extracted). Both the filename and the link fields of each TAR header are
 * checked for possible malicious paths.
 * 
 * @author Umberto Salsi <salsi@icosaedro.it>
 * @version $Date: 2016/01/18 10:02:20 $
 */
class TarMaliciousPathException extends IOException {}
