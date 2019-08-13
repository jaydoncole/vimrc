<?php

namespace it\icosaedro\lint\docblock;
require_once __DIR__ . "/../../../../all.php";
use it\icosaedro\lint\types\Type;
use it\icosaedro\lint\Logger;
use it\icosaedro\lint\Where;
use it\icosaedro\lint\docblock\DocBlock;
use it\icosaedro\lint\docblock\DocBlockParameter;

/**
 * Wrapper to DocBlock with several convenience methods.
 * Also handles missing DocBlock and missing line tag, providing accessor
 * methods that return a default, safe value.
 * @author Umberto Salsi <salsi@icosaedro.it>
 * @version $Date: 2018/03/05 13:51:12 $
 */
class DocBlockWrapper {
	
	/*. private .*/ const
		ACCESS_TAG = 1,
		PACKAGE_TAG = 2,
		PARAM_TAG = 4,
		RETURN_TAG = 8,
		THROWS_TAG = 16,
		TRIGGERS_TAG = 32,
		VAR_TAG = 64;
	
	/**
	 * @var Logger
	 */
	private $logger;
	
	/**
	 * @var Where 
	 */
	private $where;

	/**
	 * @var DocBlock
	 */
	private $db;
	
	
	/**
	 * Singleton instance representing an empty DocBlock.
	 * @var self
	 */
	private static $empty_docblock;

	/**
	 * Initializes new DocBlock wrapper.
	 * @param Logger $logger
	 * @param DocBlock $db DocBlock to wrap, or NULL if not available.
	 * @return void
	 */
	public function __construct($logger, $db) {
		$this->logger = $logger;
		if( $db == NULL )
			$this->where = Where::getNowhere();
		else
			$this->where = $db->decl_in;
		$this->db = $db;
	}
	
	
	/**
	 * Returns the singleton instance of an empty DocBlock. For performances,
	 * client code should call this when the DocBlock is missing intead to call
	 * the constructor.
	 * @return self Singleton instance representing an empty DocBlock.
	 */
	public static function getEmpty() {
		if( self::$empty_docblock === NULL )
			// we may safely set the logger param to NULL because not used
			// for empty wrapper:
			self::$empty_docblock = new self(NULL, NULL);
		return self::$empty_docblock;
	}
	
	
	/**
	 * @return void
	 */
	public function clear(){
		$this->where = Where::getNowhere();
		$this->db = NULL;
	}
	
	
	/**
	 * Returns the wrapped DocBlock, possibly NULL if missing.
	 * @return DocBlock 
	 */
	public function getDocBlock()
	{
		return $this->db;
	}
	
	
	/**
	 *
	 * @param string $tag
	 * @param boolean $found
	 * @param boolean $allowed 
	 * @return void
	 */
	private function checkLineTag($tag, $found, $allowed)
	{
		if( $found && ! $allowed )
			$this->logger->error($this->where,
				"unexpected DocBlock line tag `$tag'");
	}
	
	
	/**
	 *
	 * @param int $allowed_set 
	 * @return void
	 */
	private function checkLineTags($allowed_set)
	{
		$db = $this->db;
		if( $db === NULL )
			return;
			
		$this->checkLineTag("@access",
			$db->is_public || $db->is_protected || $db->is_private,
			($allowed_set & self::ACCESS_TAG) != 0);
		
		$this->checkLineTag("@package",
			$db->package_word !== NULL,
			($allowed_set & self::PACKAGE_TAG) != 0);
		
		$this->checkLineTag("@param",
			$db->params !== NULL && count($db->params) > 0,
			($allowed_set & self::PARAM_TAG) != 0);
		
		$this->checkLineTag("@return",
			$db->return_type !== NULL,
			($allowed_set & self::RETURN_TAG) != 0);
		
		$this->checkLineTag("@throws",
			$db->throws_exceptions !== NULL && count($db->throws_exceptions) > 0,
			($allowed_set & self::THROWS_TAG) != 0);
		
		$this->checkLineTag("@var",
			$db->var_type !== NULL,
			($allowed_set & self::VAR_TAG) != 0);
		
		$this->checkLineTag("@triggers",
			$db->triggers_names !== NULL && count($db->triggers_names) > 0,
			($allowed_set & self::TRIGGERS_TAG) != 0);
	}
	
	
	/**
	 * Reports error for line tags forbidden in package's DocBlock.
	 * @return void
	 */
	public function checkLineTagsForPackage()
	{
		if( $this->db === NULL )
			return;
		$allowed_set = self::PACKAGE_TAG;
		$this->checkLineTags($allowed_set);
		if( $this->db->package_word === NULL )
			throw new \RuntimeException("missing @package");
	}
	
	/**
	 * Reports error for line tags forbidden in constant's DocBlock.
	 * @return void
	 */
	public function checkLineTagsForConstant()
	{
		if( $this->db === NULL )
			return;
		$allowed_set = self::ACCESS_TAG;
		$this->checkLineTags($allowed_set);
		if( $this->db->is_protected )
			$this->logger->error($this->where,
			"invalid line tag `@access protected': only public or private allowed");
	}
	
	
	/**
	 * Reports error for line tags forbidden in variable's DocBlock.
	 * @return void
	 */
	public function checkLineTagsForVariable()
	{
		if( $this->db === NULL )
			return;
		$allowed_set = self::ACCESS_TAG | self::VAR_TAG;
		$this->checkLineTags($allowed_set);
		if( $this->db->is_protected )
			$this->logger->error($this->where,
			"invalid line tag `@access protected': only public or private allowed");
	}
	
	
	/**
	 * Reports error for line tags forbidden in function's DocBlock.
	 * @return void
	 */
	public function checkLineTagsForFunction()
	{
		if( $this->db === NULL )
			return;
		$allowed_set = self::ACCESS_TAG | self::PARAM_TAG | self::RETURN_TAG
			| self::THROWS_TAG | self::TRIGGERS_TAG;
		$this->checkLineTags($allowed_set);
		if( $this->db->is_protected )
			$this->logger->error($this->where,
			"invalid line tag `@access protected': only public or private allowed");
	}
	
	
	/**
	 * Reports error for line tags forbidden in class and interface DocBlock.
	 * @return void
	 */
	public function checkLineTagsForClass()
	{
		if( $this->db === NULL )
			return;
		$allowed_set = self::ACCESS_TAG;
		$this->checkLineTags($allowed_set);
		if( $this->db->is_protected )
			$this->logger->error($this->where,
			"invalid line tag `@access protected': only public or private allowed");
	}
	
	
	/**
	 * Reports error for line tags forbidden in class constant's DocBlock.
	 * @return void
	 */
	public function checkLineTagsForClassConstant()
	{
		if( $this->db === NULL )
			return;
		$allowed_set = self::ACCESS_TAG;
		$this->checkLineTags($allowed_set);
		if( $this->db->is_protected )
			$this->logger->error($this->where,
			"invalid line tag `@access protected': only public or private allowed");
	}
	
	
	/**
	 * Reports error for line tags forbidden in property's DocBlock.
	 * @return void
	 */
	public function checkLineTagsForProperty()
	{
		$allowed_set = self::VAR_TAG;
		$this->checkLineTags($allowed_set);
	}
	
	
	/**
	 * Reports error for line tags forbidden in method's DocBlock.
	 * @return void
	 */
	public function checkLineTagsForMethod()
	{
		$allowed_set = self::PARAM_TAG | self::RETURN_TAG | self::TRIGGERS_TAG;
		$allowed_set |= self::THROWS_TAG;
		$this->checkLineTags($allowed_set);
	}
	
	
	/**
	 * @return boolean 
	 */
	public function isPrivate(){
		return $this->db !== NULL && $this->db->is_private;
	}
	
	
	/**
	 * @return boolean 
	 */
	public function isProtected(){
		return $this->db !== NULL && $this->db->is_protected;
	}
	
	
	/**
	 * @return boolean 
	 */
	public function isPublic(){
		return $this->db !== NULL && $this->db->is_public;
	}
	
	
	/**
	 * @return boolean 
	 */
	public function isDeprecated(){
		return $this->db !== NULL && $this->db->deprecated_descr !== NULL;
	}
	
	
	/**
	 * @return boolean 
	 */
	public function isPackage(){
		return $this->db !== NULL && $this->db->package_word !== NULL;
	}
	
	
	/**
	 *
	 * @return Type 
	 */
	public function getVarType()
	{
		if( $this->db !== NULL )
			return $this->db->var_type;
		else
			return NULL;
	}
	
	
	/**
	 *
	 * @return Type 
	 */
	public function getReturnType()
	{
		if( $this->db !== NULL )
			return $this->db->return_type;
		else
			return NULL;
	}
	
	
	/**
	 * Returns the DocBlock parameter given its name.
	 * @param string $name Name of the parameter without leading dollar sign.
	 * @return DocBlockParameter Found parameter, or NULL if missing.
	 */
	public function getParameter($name)
	{
		if( $this->db === NULL )
			return NULL;
		return $this->db->getParameter($name);
	}

}
