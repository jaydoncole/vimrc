<?php

class A {

	public static function m(){
		// Using constructor before definition:
		return new self();
	}

	public function __construct() {}

}


class B extends A {

	public static function m(){
		// Using inherited constructor before definition:
		return new self();
	}

	public function __construct() {}

}

