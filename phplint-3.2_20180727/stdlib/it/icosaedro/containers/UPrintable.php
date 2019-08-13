<?php

namespace it\icosaedro\containers;

/*. forward interface UPrintable {} .*/
require_once __DIR__ . "/../utils/UString.php";
use it\icosaedro\utils\UString;


/**
 * Object capable to provide its own readable representation as Unicode
 * string.
 * @package UPrintable
 * @author Umberto Salsi <salsi@icosaedro.it>
 * @version $Date: 2016/01/15 06:48:22 $
 */
interface UPrintable {

	/**
	 * Return a readable representation of the object.
	 * @return UString Readable representation of the object.
	 */
	function toUString();

}
