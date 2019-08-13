<?php

namespace it\icosaedro\lint;
require_once __DIR__ . "/../../../all.php";
use it\icosaedro\lint\Enum;

/**
 * @author Umberto Salsi <salsi@icosaedro.it>
 * @version $Date: 2016/01/15 06:53:00 $
 */
class PhpVersion extends Enum {
	
	public static /*. PhpVersion .*/ $php5;
	public static /*. PhpVersion .*/ $php7;
	
	
	private /*. string .*/ $name;
	
	public /*. void .*/ function __construct(/*. string .*/ $name){
		$this->name = $name;
	}
	
	
	public /*. string .*/ function __toString(){
		return $this->name;
	}
}

PhpVersion::$php5 = new PhpVersion("5");
PhpVersion::$php7 = new PhpVersion("7");
