<?php

namespace it\icosaedro\lint\statements;
require_once __DIR__ . "/../../../../all.php";
use it\icosaedro\lint\Globals;
use it\icosaedro\lint\Symbol;
use it\icosaedro\lint\NamespaceResolver;

/**
 * @author Umberto Salsi <salsi@icosaedro.it>
 * @version $Date: 2016/02/22 08:52:11 $
 */
class UseStatement {
	
	/**
	 * Parse "use TARGET [ as ALIAS];".
	 * @param Globals $globals
	 * @return void
	 */
	public static function parse($globals){
		$pkg = $globals->curr_pkg;
		$scanner = $pkg->scanner;
		$scanner->readSym();
		while(TRUE){
			/* Parse target: */
			$globals->expect(Symbol::$sym_identifier, "expected namespace name");
			$target = $scanner->s;
			if( $pkg->resolver->inNamespace() ){
				if( NamespaceResolver::isAbsolute($target) ){
					$globals->logger->notice($scanner->here(),
						"useless leading `\\' in path namespace: path namespaces are always absolute");
					$target = substr($target, 1);
				}
			} else {
				if( NamespaceResolver::isAbsolute($target) ){
					$target = substr($target, 1);
				} else if( ! NamespaceResolver::isQualified($target) ){
					$globals->logger->error($scanner->here(),
						"the use statement with non-compound name '$target' has no effect."
						. " This is what PHP would write to stderr for unqualified,"
						. " non-absolute names, I don't understand why. Fix: either add"
						. " a leading back-slash, or simply remove this statement"
						. " if it does not define an alias name.");
				}
			}
			$scanner->readSym();

			/* Parse alias: */
			if( $scanner->sym === Symbol::$sym_as ){
				$scanner->readSym();
				$globals->expect(Symbol::$sym_identifier, "expected identifier");
				$alias = $scanner->s;
				if( ! NamespaceResolver::isIdentifier($alias) ){
					$globals->logger->error($scanner->here(), "expected identifier, found $alias");
					$alias = NULL; // recover
				}
				$scanner->readSym();
			} else {
				$alias = NULL;
			}
			$pkg->resolver->addUse($target, $alias, $scanner->here());

			if( $scanner->sym === Symbol::$sym_comma ){
				$scanner->readSym();
			} else {
				break;
			}
		}
		$globals->expect(Symbol::$sym_semicolon, "expected `;'");
		$scanner->readSym();
	}
	
}

