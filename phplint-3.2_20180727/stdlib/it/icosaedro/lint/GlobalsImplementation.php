<?php

namespace it\icosaedro\lint;
require_once __DIR__ . "/../../../all.php";
use it\icosaedro\io\File;
use it\icosaedro\io\FileException;
use it\icosaedro\io\IOException;


/**
 * Extends the context of the parser (Globals) implementing the package
 * loader. This implementation, then, joins the global data structures with
 * the PHPLint parser.
 * @author Umberto Salsi <salsi@icosaedro.it>
 * @version $Date: 2015/11/16 04:13:08 $
 */
class GlobalsImplementation extends Globals {

	/**
	 * Initializes the parsing context and defines some PHP built-in items.
	 * @param Logger $logger Writer of the report.
	 * @return void
	 */
	public function __construct($logger){
		parent::__construct($logger);
	}
	
	
	/**
	 * Load a package or a module. Exceptions thrown by the scanner and by the
	 * parser are captured here and logged as fatal errors.
	 * @param File $fn File of the package or module.
	 * @param boolean $is_module True if this package is loaded from
	 * <code>require_module</code> or user set the <code>--is-module</code>
	 * command line flag; false if <code>require_once</code>.
	 * @return void 
	 */
	public function loadPackage($fn, $is_module)
	{
		$pkg = $this->getPackage($fn);
		if ($pkg !== NULL)
			return;
		
		$saved_print_notices = $this->logger->print_notices;
		if( $this->recursion_level > 0 )
			$this->logger->print_notices = FALSE;
		
		$saved_print_source = $this->logger->print_source;
		if( $this->recursion_level > 0 )
			$this->logger->print_source = FALSE;
		
		$saved_curr_pkg = $this->curr_pkg;
		
		$this->recursion_level++;
		
		$pkg_parser = new PackageParser($this);
		
		try {
			$pkg_parser->parse($fn, $is_module);
			$this->total_source_length += $fn->length();
		}
		catch(FileException $e){
			if( $saved_curr_pkg === NULL )
				$here = new Where($fn);
			else
				$here = $saved_curr_pkg->scanner->here();
			$this->logger->error($here, $e->getMessage());
		}
		catch(IOException $e){
			$here = new Where($fn);
			$this->logger->error($here, "(FATAL) " . $e->getMessage());
		}
		catch(ParseException $e){
			$this->logger->error($e->getWhere(), "(FATAL) " . $e->getMessage());
		}
		catch(ScannerException $e){
			$this->logger->error($e->getWhere(), "(FATAL) " . $e->getMessage());
		}
		
		$this->recursion_level--;
		
		$this->curr_pkg = $saved_curr_pkg;
		
		$this->logger->print_source = $saved_print_source;
		
		$this->logger->print_notices = $saved_print_notices;
	}

	
	/**
	 * Packages whose parsing has been suspended (see "pragma 'suspend';").
	 * @var PackageParser[int]
	 */
	private $suspended = array();
	
	/**
	 * 
	 * @param PackageParser $package_parser
	 */
	public function addSuspended($package_parser) {
		$this->suspended[] = $package_parser;
	}
	
	
	/**
	 * Resume parsing of packages suspended by "pragma 'suspend';".
	 * @return void
	 */
	public function continueParse() {
		// Note that the list of suspended packages may grow while resuming the
		// suspended packages, so the final index must be recalculated at each
		// iteration of the loop.
		for($i = 0; $i < count($this->suspended); $i++){
			$pkg_parser = $this->suspended[$i];
			$saved_print_notices = $this->logger->print_notices;
			$this->recursion_level = $pkg_parser->recursion_level;
			if( $this->recursion_level > 0 )
				$this->logger->print_notices = FALSE;

			$saved_print_source = $this->logger->print_source;
			if( $this->recursion_level > 0 )
				$this->logger->print_source = FALSE;

			$saved_curr_pkg = $this->curr_pkg;

//			$this->recursion_level++;

//			$pkg_parser = new PackageParser($this);

			try {
				$pkg_parser->continueParse();
			}
//			catch(FileException $e){
//				if( $saved_curr_pkg === NULL )
//					$here = new Where($fn);
//				else
//					$here = $saved_curr_pkg->scanner->here();
//				$this->logger->error($here, $e->getMessage());
//			}
			catch(IOException $e){
				$here = new Where($pkg_parser->getPackage()->fn);
				$this->logger->error($here, "(FATAL) " . $e->getMessage());
			}
			catch(ParseException $e){
				$this->logger->error($e->getWhere(), "(FATAL) " . $e->getMessage());
			}
			catch(ScannerException $e){
				$this->logger->error($e->getWhere(), "(FATAL) " . $e->getMessage());
			}

//			$this->recursion_level--;

			$this->curr_pkg = $saved_curr_pkg;

			$this->logger->print_source = $saved_print_source;

			$this->logger->print_notices = $saved_print_notices;
		}
		$this->suspended = array();
	}

}
