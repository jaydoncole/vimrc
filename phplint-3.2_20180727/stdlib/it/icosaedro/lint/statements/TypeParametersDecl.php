<?php

namespace it\icosaedro\lint\statements;

require_once __DIR__ . "/../../../../all.php";

use it\icosaedro\lint\Globals;
use it\icosaedro\lint\Symbol;
use it\icosaedro\lint\TypeDecl;
use it\icosaedro\lint\NamespaceResolver;
use it\icosaedro\lint\ParseException;
use it\icosaedro\lint\types\ClassType;
use it\icosaedro\lint\types\UnknownType;
use it\icosaedro\lint\types\ParameterType;

/**
 * Parses type parameters declaration belonging to the current template. Does
 * nothing if there are no type parameters. Example:
 * <tt>/&#42;. &lt;T1, T2 extends C1, T3 extends C2 &amp; IF1&gt; .&#42;/</tt>
 * @author Umberto Salsi <salsi@icosaedro.it>
 * @version $Date: 2016/02/22 08:51:59 $
 */
class TypeParametersDecl {
	
	
	/**
	 * Parse type parameters of a generic class.
	 * @param Globals $globals
	 * @param ClassType $c
	 * @return void
	 */
	public static function parse($globals, $c)
	{
		$pkg = $globals->curr_pkg;
		$logger = $globals->logger;
		$scanner = $pkg->scanner;
		$pkg->curr_class = $c;
		
		if( $scanner->sym !== Symbol::$sym_x_lt ){
			$c->is_real_or_fully_actualized = TRUE;
			return;
		}
		
		$c->is_template = TRUE;
		$c->is_real_or_fully_actualized = FALSE;
		$c->parameters_by_name = /*. (ParameterType[string]) .*/ array();
		
		// Parse formal type parameters:
		$is_exception = FALSE;
		$is_unchecked = FALSE;
		while(TRUE){
			$scanner->readSym();
			
			// Parse type parameter's name:
			$globals->expect(Symbol::$sym_x_identifier,
				"expected type name in generic class parameter");
			$parameter_name = $scanner->s;
			$decl_in = $scanner->here();
			if (!NamespaceResolver::isIdentifier($parameter_name))
				throw new ParseException($scanner->here(),
					"type parameter's name must be a simple identifier");
			if(array_key_exists($parameter_name, $c->parameters_by_name) )
				$logger->error($decl_in, "duplicated type name");
			
			$scanner->readSym();
			
			// Parse bounding classes:
			$bounds = /*. (ClassType[int]) .*/ array();
			if( $scanner->sym === Symbol::$sym_x_extends ){
				do {
					$scanner->readSym();
					
					// Parse bounding class:
					$bound = /*. (ClassType) .*/ NULL;
					$t = TypeDecl::parse($globals, FALSE, TRUE);
					if( $t instanceof UnknownType ){
						// Error already signaled.
					} else if( $t instanceof ClassType ){
						$bound = cast(ClassType::class, $t);
					} else {
						$logger->error($scanner->here(),
							"expected bounding class but found $t");
					}
					
					// Add bound to parameter:
					if( $bound !== NULL ){
						if( !( count($bounds) == 0 || $bound->is_interface ) ){
							$logger->error($scanner->here(),
								"bound classes after the first one must be interfaces: $bound");
						} else {
							$bounds[] = $bound;
						}
						if( $bound->is_exception ){
							$is_exception = TRUE;
							$is_unchecked = $bound->is_unchecked;
						}
					}
					
					// Continue with next bound:
					if( $scanner->sym === Symbol::$sym_x_bit_and ){
					} else {
						break;
					}
				} while(TRUE);
			}
			
			// Add parameter to the template:
			$p = new ParameterType($c, $parameter_name, $decl_in, $bounds);
			$p->is_exception = $is_exception;
			$p->is_unchecked = $is_unchecked;
			$c->parameters_by_index[] = $p;
			$c->parameters_by_name[$parameter_name] = $p;
			
			if( $scanner->sym === Symbol::$sym_x_gt ){
				$scanner->readSym();
				break;
			}
			$globals->expect(Symbol::$sym_x_comma,
				"invalid syntax in generic type declaration");
		};
		
//		$c->addToActualized();
	}
	
}
