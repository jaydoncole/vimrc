<?php

namespace it\icosaedro\lint\types;
require_once __DIR__ . "/../../../../all.php";

use it\icosaedro\lint\types\ClassType;
use it\icosaedro\containers\HashMap;
use it\icosaedro\lint\FullyQualifiedName;
use it\icosaedro\lint\Where;

/*. forward class ParameterType {} .*/

/**
 * Type parameter of a generic class.
 * @author Umberto Salsi <salsi@icosaedro.it>
 * @version $Date: 2016/03/06 13:29:40 $
 */
class ParameterType extends ClassType {
	
	/**
	 * Bare name of the parameter, that is "T" rather than "TemplateFQN#T".
	 * @var string
	 */
	public $short_name;
	
	/**
	 * Template this type parameter belongs to.
	 * @var ClassType
	 */
	public $template;
	
	/**
	 * Boundings classes. The first class can be concrete or abstract, the others
	 * must be interfaces.
	 * @var ClassType[int]
	 */
	private $bounds;
	
	/*.
	forward	public void function __construct(ClassType $generic_class, string $short_name, Where $decl_in, ClassType[int] $bounds);
	forward	public string function __toString();
	forward	public ClassType function actualize(HashMap $actual_types);
	forward	public ClassType[int] function getBounds();

	pragma 'suspend';
	.*/
	
	/**
	 * Creates a new type parameter type bound to a specified template class.
	 * @param ClassType $template Template class.
	 * @param string $short_name Name of the parameter.
	 * @param Where $decl_in
	 * @param ClassType[int] $bounds Boundings.
	 */
	public function __construct($template, $short_name, $decl_in, $bounds) {
		parent::__construct(new FullyQualifiedName($template->name->getName() . "#" . $short_name, FALSE), $decl_in, FALSE);
		$this->template = $template;
		$this->short_name = $short_name;
		$this->bounds = $bounds;
		// set "extends" and "implements" of this formal type:
		if( count($bounds) == 0 ){
			// no bounds
			$this->extended = self::getObject();
			$this->implemented = $bounds;
		} else if( $bounds[0]->is_interface ){
			// all bounds are interfaces
			$this->extended = self::getObject();
			$this->implemented = $bounds;
		} else {
			// first class is concrete or abstract
			$this->extended = $bounds[0];
			array_shift($bounds);
			$this->implemented = $bounds;
		}
	}
	
	
	/**
	 * 
	 * @return ClassType[int]
	 */
	public function getBounds() {
		return $this->bounds;
	}


	/**
	 * Readable description of this type parameter.
	 */
	public function __toString() {
		return $this->name->__toString();
	}
	
	/**
	 * @param Type $lhs
	 * @return boolean
	 */
	public function canCastTo($lhs) {
		return FALSE;
	}
	
	
	/**
	 * 
	 * @return boolean Always returns FALSE.
	 */
	public function isRealOrFullyActualized() {
		return FALSE;
	}
	
	
	/**
	 * Returns the actual class out of this type parameter.
	 * @param HashMap $replacements Actual types replacing the type parameters.
	 * @return ClassType If the replacements contain this formal type, returns the
	 * replacement, otherwise returns itself.
	 */
	public function actualize($replacements) {
		$actual_type = $replacements->get($this);
		if( $actual_type === NULL )
			return $this;
		else
			return cast(ClassType::class, $actual_type);
	}
	
}
