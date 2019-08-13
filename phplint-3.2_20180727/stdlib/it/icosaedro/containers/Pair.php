<?php

namespace it\icosaedro\containers;


/**
 * Holds an ordered pair of objects.
 * @author Umberto Salsi <salsi@icosaedro.it>
 * @version $Date: 2016/02/08 22:21:32 $
 */
class Pair /*. <A,B> .*/ {
	
	/**
	 * @var A
	 */
	private $a;
	
	/**
	 * @var B
	 */
	private $b;
	
	/**
	 * 
	 * @param A $a
	 * @param B $b
	 */
	public function __construct($a, $b) {
		$this->a = $a;
		$this->b = $b;
	}
	
	public function getA() {
		return $this->a;
	}
	
	public function getB() {
		return $this->b;
	}
	
}