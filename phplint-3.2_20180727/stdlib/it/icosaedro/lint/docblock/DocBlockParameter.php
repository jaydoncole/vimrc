<?php

namespace it\icosaedro\lint\docblock;
require_once __DIR__ . "/../../../../autoload.php";
use it\icosaedro\lint\types\Type;

/**
 * A single parameter from a "@param" line-tag. The general syntax of this
 * line-tag is <center><tt>@param TYPE [&amp;] [...] $VAR HTML</tt></center>
 * where the ampersand marks values passed by reference, and ellipsis marks
 * the variadic parameter.
 * @author Umberto Salsi <salsi@icosaedro.it>
 * @version $Date: 2015/06/29 21:15:00 $
 */
class DocBlockParameter {

	/**
	 * Type of the parameter. For a variadic parameter, this is the type of the
	 * elements.
	 * @var Type
	 */
	public $type;

	/**
	 * If the parameter is expected to be passed by reference, then the actual
	 * value must be an assignable value (LHS).
	 * @var boolean
	 */
	public $byref = FALSE;

	/**
	 * If this is the variadic parameter.
	 * @var boolean
	 */
	public $is_variadic = TRUE;

	/**
	 * Name of the parameter without dollar sign.
	 * @var string
	 */
	public $name;

	/**
	 * HTML text.
	 * @var string
	 */
	public $descr;
	
}
