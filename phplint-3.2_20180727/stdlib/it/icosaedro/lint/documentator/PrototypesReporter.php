<?php

namespace it\icosaedro\lint\documentator;

require_once __DIR__ . "/../../../../all.php";

use it\icosaedro\lint\Globals;
use it\icosaedro\lint\Visibility;
use it\icosaedro\lint\Signature;
use it\icosaedro\lint\FormalArgument;
use it\icosaedro\lint\types\Type;
use it\icosaedro\lint\types\IntType;
use it\icosaedro\lint\types\StringType;
use it\icosaedro\lint\types\ArrayType;
use it\icosaedro\lint\types\ClassType;
use it\icosaedro\lint\types\ParameterType;
use it\icosaedro\lint\types\ClassMethod;
use it\icosaedro\io\IOException;
use it\icosaedro\io\File;
use it\icosaedro\io\OutputStream;
use it\icosaedro\containers\Arrays;

/**
 * Reports the prototype of the classes and their methods for a specified
 * package. This report may alleviate the boring task to add 'forward'
 * declarations.
 * 
 * @author Umberto Salsi <salsi@icosaedro.it>
 * @version $Date: 2018/04/15 04:40:24 $
 */
class PrototypesReporter {
	
	/**
	 * @var Globals
	 */
	private $globals;
	
	/**
	 *
	 * @var OutputStream
	 */
	public $os;
	
	/**
	 * @param Globals $globals
	 * @param OutputStream $os
	 */
	function __construct($globals, $os) {
		$this->globals = $globals;
		$this->os = $os;
	}
	
	
	/**
	 * 
	 * @param string $s
	 * @throws IOException 
	 */
	private function write($s) {
		$this->os->writeBytes($s);
	}

	
	/**
	 * Writes an HTML link to some class.
	 * @param ClassType $c Class to be reported and linked.
	 * @throws IOException 
	 */
	private function writeClassType($c){
		if( $c === ClassType::getObject() ){
			$this->write("object");
			return;
		}
		if( $c->is_anonymous ){
			// Anonymous class. Displays its mangled name.
			// Should never appear in the document anyway.
			$fqn = $c->name->getName();
			$this->write($fqn);
			
		} else if( $c instanceof ParameterType ){
			// Formal type on a template.
			$parameter = cast(ParameterType::class, $c);
			$this->write($parameter->short_name);
			
		} else {
			// Normal class.
			$this->write($c->name->getName());
		}
		
		// Normal classes and anonymous class may have type parameters.
		if( phplint_count($c->parameters_by_index) > 0 ){
			// Is generic, but not a formal parameter - show actual params:
			$this->write("<");
			for($i = 0; $i < count($c->parameters_by_index); $i++){
				if( $i > 0 )
					$this->write(",");
				$this->writeClassType($c->parameters_by_index[$i]);
			}
			$this->write(">");
		}
	}
	
	
	/*. forward private void function writeType(Type $t) throws IOException; .*/
	
	
	/**
	 * Writes an array type.
	 * @param ArrayType $a 
	 * @throws IOException 
	 */
	private function writeArrayType($a){
		$s = "";
		do {
			if( $a->getIndex() instanceof IntType )
				$s .= "[int]";
			else if( $a->getIndex() instanceof StringType )
				$s .= "[string]";
			else
				$s .= "[]";
			if( $a->getElem() instanceof ArrayType )
				$a = cast(ArrayType::class, $a->getElem());
			else {
				$this->writeType($a->getElem());
				$this->write($s);
				return;
			}
		} while(TRUE);
	}
	
	
	/**
	 * Writes a type.
	 * @param Type $t 
	 * @throws IOException 
	 */
	private function writeType($t){
		if( $t instanceof ClassType ){
			$this->writeClassType(cast(ClassType::class, $t));
		} else if( $t instanceof ArrayType ){
			$this->writeArrayType(cast(ArrayType::class, $t));
		} else {
			$this->write($t->__toString());
		}
	}
	
	
	/**
	 * Writes a formal argument.
	 * @param FormalArgument $a
	 * @throws IOException 
	 */
	private function writeFormalArgument($a){
		if( $a->reference_return )
			$this->write("return ");
		$this->writeType($a->type);
		$this->write(" ");
		if( $a->reference )
			$this->write("& ");
		if( $a->is_variadic )
			$this->write("... ");
		$this->write("\$" . $a->name);
		if( ! $a->is_mandatory && ! $a->is_variadic ){
			$this->write(" =");
		}
	}
	
	
	/**
	 * Wites the prototype of a function or method.
	 * @param string $name Name Trailing name of the function (without NS) or
	 * name of the method terminated by "()".
	 * @param string $title Value for the 'title' attribute. Shoud be the FQN
	 * of the function or FQN() of class followed by "::METHOD()".
	 * @param Signature $sign 
	 * @throws IOException 
	 */
	private function writePrototype($name, $title, $sign){
		
		$this->writeType($sign->returns);
		if( $sign->reference )
			$this->write(" &");
		$this->write(" function $name(");

		# Mandatory and default args:
		for($i = 0; $i < count($sign->arguments); $i++){
			if( $i > 0 )
				$this->write(", ");
			$this->writeFormalArgument($sign->arguments[$i]);
		}

		if( $sign->variadic !== NULL ){
			if( count($sign->arguments) > 0 )
				$this->write(", ");
			$this->writeFormalArgument($sign->variadic);
		}

		if( $sign->more_args ){
			if( count($sign->arguments) > 0 )
				$this->write(", ");
			$this->write("args");
		}
		
		$this->write(")");
		
		if( ! $sign->errors->isEmpty() )
			$this->write(" triggers " . $sign->errors);
		
		if( ! $sign->exceptions->isEmpty() ){
			$this->write(" throws ");
			$sign->exceptions;
			$es = cast(ClassType::class."[int]", $sign->exceptions->getElements());
			$es = cast(ClassType::class."[int]", Arrays::sort($es));
			for($i = 0; $i < count($es); $i++){
				if( $i > 0 )
					$this->write(", ");
				$this->writeType($es[$i]);
			}
		}
		
		$this->write(";");
	}
	
	
	/**
	 * Writes a method.
	 * @param ClassMethod $m
	 * @throws IOException 
	 */
	private function docMethod($m){
		$long = $m->__toString() . "()";
		$short = $m->name->__toString();
		$this->write("\t" . $m->visibility . " ");
		if( $m->is_abstract && ! $m->class_->is_interface )
			$this->write("abstract ");
		if( $m->is_static )
			$this->write("static ");
		if( $m->is_final )
			$this->write("final ");
		$this->writePrototype($short, $long, $m->sign);
		$this->write("\n");
	}
	
	
	/**
	 * Documents non-private methods. Private methods are collected to be
	 * listed next.
	 * @param ClassType $c
	 * @throws IOException 
	 */
	private function docMethods($c){
		// Methods to be reported:
		$ms = /*. (ClassMethod[int]) .*/ array();
		
		$ctor = $c->constructor;
		if( $ctor === NULL )
			$ctor = $c->parentConstructor();
		if( $ctor !== NULL && $ctor->visibility === Visibility::$private_ )
			$ctor = NULL;
		
		foreach($c->methods as $mm){
			$m = cast(ClassMethod::class, $mm);
			if( $m->visibility !== Visibility::$private_ )
				$ms[] = $m;
		}
		
		if( count($ms) == 0 && $ctor === NULL )
			return;
		
		if( $ctor !== NULL
		&& $ctor->visibility !== Visibility::$private_ )
			$this->docMethod($ctor);
		
		if( $c->destructor !== NULL
		&& $c->destructor->visibility !== Visibility::$private_ )
			$this->docMethod($c->destructor);
		
		$ms = cast(ClassMethod::class."[int]", Arrays::sort($ms));
		foreach($ms as $m){
			if( !( $m === $c->constructor || $m === $c->destructor ) )
				$this->docMethod($m);
		}
	}
	
	
	/**
	 * Writes a class.
	 * @param ClassType $c
	 * @throws IOException 
	 */
	private function docClass($c){
		if( $c->is_private )
			$this->write("private ");
		if( $c->is_unchecked )
			$this->write("unchecked ");
		if( $c->is_interface )
			$this->write("interface ");
		else if( $c->is_abstract )
			$this->write("abstract class ");
		else {
			if( $c->is_final )
				$this->write("final ");
			$this->write("class ");
		}
		$this->write($c->name->getName());
		
		// Template's parameters:
		if( $c->is_template ){
			$this->write("<");
			for($i = 0; $i < count($c->parameters_by_index); $i++){
				if( $i > 0 )
					$this->write(", ");
				$parameter = cast(ParameterType::class, $c->parameters_by_index[$i]);
				$this->write($parameter->short_name);
				$bounds = $parameter->getBounds();
				if( count($bounds) > 0 ){
					$this->write(" extends ");
					for($j = 0; $j < count($bounds); $j++){
						if( $j > 0 )
							$this->write(" & ");
						$this->writeClassType($bounds[$j]);
					}
				}
			}
			$this->write(">");
		}
		$this->write("\n{\n");
		$this->docMethods($c);
		$this->write("}\n");
	}


	/**
	 * @param File $fn
	 * @throws IOException 
	 */
	function report($fn) {
		$this->write("--------------------------------------------\n");
		$this->write("Prototypes from $fn:\n");
		$cs = /*. (ClassType[int]) .*/ array();
		foreach($this->globals->classes as $mc){
			$c = cast(ClassType::class, $mc);
			if( $c->decl_in->getFile() === $fn ){
//				if( $c->is_anonymous ){
//					// anonymous classes never reported
//				} else if( $c->is_private ){
//					// private classes listed -- their name "pollutes" the NS
//					$this->private_classes[] = $c;
//				} else {
					// class to fully report
					$cs[] = $c;
//				}
			}
		}
		if( count($cs) == 0 )
			return;
		$cs = cast(ClassType::class."[int]", Arrays::sort($cs));
		foreach($cs as $c){
			$this->docClass($c);
		}
		$this->write("--------------------------------------------\n");
	}
	
}
