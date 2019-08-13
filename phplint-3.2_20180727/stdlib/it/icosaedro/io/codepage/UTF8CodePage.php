<?php

namespace it\icosaedro\io\codepage;
require_once __DIR__ . "/../../../../autoload.php";
use it\icosaedro\utils\UString;

/**
 * UTF-8 code page translator. Since UString already internally uses
 * the same encoding, basically this class does not perform any conversion at
 * all, which is very efficient under Linux (where UTF-8 is the most common
 * encoding used nowadays) and under Windows + PHP &ge; 7.1.
 *
 * @author Umberto Salsi <salsi@icosaedro.it>
 * @version $Date: 2018/03/21 07:36:19 $
 */
class UTF8CodePage implements CodePageInterface {

	/**
	 * Returns encoding name implemented by this object.
	 * @return string Always returns "UTF-8".
	 */
	function __toString(){
		return "UTF-8";
	}


	/**
	 * Encode file name to the current code page table.
	 * @param UString $name Unicode name of the file.
	 * @return string Translated file name.
	 */
	function encode($name){
		return $name->toUTF8();
	}


	/**
	 * Decode file name from current code page table.
	 * @param string $name File name to decode.
	 * @return UString Translated file name.
	 */
	function decode($name){
		return UString::fromUTF8($name);
	}

}
