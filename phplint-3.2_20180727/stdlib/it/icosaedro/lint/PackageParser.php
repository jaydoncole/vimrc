<?php

namespace it\icosaedro\lint;
use it\icosaedro\io\File;
use it\icosaedro\io\FileInputStream;
use it\icosaedro\io\FileNotFoundException;
use it\icosaedro\io\FilePermissionException;
use it\icosaedro\io\IOException;
use it\icosaedro\lint\Globals;
use it\icosaedro\lint\Flow;
use it\icosaedro\lint\statements\Statement;
use it\icosaedro\lint\statements\EchoBlockStatement;
use it\icosaedro\lint\statements\ClassStatement;
use it\icosaedro\utils\Strings;
use it\icosaedro\regex\Pattern;
use it\icosaedro\lint\docblock\DocBlockScanner;
use it\icosaedro\lint\docblock\DocBlockWrapper;
use it\icosaedro\lint\ParseException;
use it\icosaedro\lint\types\ClassType;

require_once __DIR__ . "/../../../all.php";

/**
 * Parses a package..
 * 
 * A <b>package</b> is a single PHP source file.
 * 
 * A <b>library</b> is a package that provide programming tools to other
 * client code. Then, a package <i>is not</i> a library if any of these
 * conditins is detected:
 * contains the initial BOM (byte ordering mark for Unicode encoding);
 * contains text (typically HTML) surrounding the PHP code;
 * contains the <code>return</code> statement at global scope;
 * triggers errors;
 * throws checked exceptions.
 * @author Umberto Salsi <salsi@icosaedro.it>
 * @version $Date: 2018/06/27 08:55:23 $
 */
class PackageParser {
	
	/**
	 *
	 * @var Globals 
	 */
	private $globals;
	
	/**
	 *
	 * @var Package
	 */
	private $package;
	
	/**
	 * Recursion level of this program while parsing sources. Level 0 is the top
	 * level of the sources whose parsing was originally asked for (that is, files
	 * listed in the command line). Level 1 are those files that are requested from
	 * those at level 0 and so on. Since the introduction of the 'suspend' pragma,
	 * a single global variable in not enough because here we must store the original
	 * level of each suspended package.
	 * Purpose: Report the source only for level 0 packages. If recursive inclusion
	 * is disabled (--no-recursive), the restriction applies only to level 0 package.
	 * @var int
	 */
	public $recursion_level = 0;
	
	
	/*.
	forward public void function __construct(Globals $globals);
	forward public Package function getPackage();
	forward public void function parse(File $fn, boolean $is_module)
		throws FileNotFoundException , FilePermissionException, IOException,
			ParseException, ScannerException;
	forward public void function continueParse() throws IOException;
	
	pragma 'suspend';
	.*/
	
	/**
	 * Returns the package being parsed.
	 * @return Package
	 */
	public function getPackage() {
		return $this->package;
	}
	
	
	/**
	 *
	 * @param Globals $globals 
	 * @return void
	 */
	public function __construct($globals){
		$this->globals = $globals;
	}
	
	
	/**
	 * Checks if the current DocBlock has been consumed, then clears current
	 * DocBlock so it cannot be used again.
	 * @return void
	 */
	private function disposeDocBlock(){
		$pkg = $this->globals->curr_pkg;
		$db = $pkg->curr_docblock->getDocBlock();
		if( $db != NULL ){
			$this->globals->logger->error($db->decl_in, "unused DocBlock");
			$pkg->curr_docblock = DocBlockWrapper::getEmpty();
		}
	}
	
	
	/**
	 * @return void
	 */
	private function setDocBlock(){
		$pkg = $this->globals->curr_pkg;
		$scanner = $pkg->scanner;
		$here = $scanner->here();
		$db = DocBlockScanner::parse($this->globals->logger, $here, $scanner->s, $this->globals);
		$pkg->curr_docblock = new DocBlockWrapper($this->globals->logger, $db);
		
		// Consume immediately package DocBlock:
		if( $pkg->curr_docblock->isPackage() ){
			
			// Check if this package already has a DocBlock:
			if( $pkg->docblock !== NULL )
				$this->globals->logger->error($here, "multiple @package DocBlocks");
			
			// Assign the pkg DocBlock to this pkg:
			$pkg->curr_docblock->checkLineTagsForPackage();
			$pkg->docblock = $db;
			
			// Reset curr DocBlock:
			$pkg->curr_docblock->clear();
		}
	}
	
	
	/**
	 * 
	 * @return void
	 * @throws IOException
	 */
	public function continueParse()
	{
		$pkg = $this->package;
		$this->globals->curr_pkg = $this->package;
		$scanner = $pkg->scanner;
		$logger = $this->globals->logger;
		
		// Init PHP opening tag detection flag. If this pkg suspended, start with TRUE.
		$code_found = $pkg->is_suspended;
		
		// Reset the suspended parsing flag.
		$pkg->is_suspended = FALSE;
		
		// Check if still parsing class members.
		if( $pkg->curr_class !== NULL ){
			// Resume suspended parsing of the class.
			ClassStatement::continueParsing($this->globals);
			if( $pkg->is_suspended ){
				// Another request of suspension inside the class.
				$this->globals->addSuspended($this);
				return;
			}
		}
		
		/*
		 * Parsing global statement.
		 * Main loop of the package parser. Its main tasks are: collect
		 * DocBlocks at scope level 0, check if actual PHP code is present,
		 * and detect end of file.
		 */
		$res = Flow::NEXT_MASK;
		while(TRUE) {
			$sym = $scanner->sym;
			if( $sym === Symbol::$sym_open_tag ){
				$code_found = TRUE;
				$scanner->readSym();
				
			} else if( $sym === Symbol::$sym_open_tag_with_echo ){
				$code_found = TRUE;
				EchoBlockStatement::parse($this->globals);
			
			} else if( $sym === Symbol::$sym_x_docBlock ){
				if( $this->globals->parse_phpdoc ){
					$this->disposeDocBlock();
					$this->setDocBlock();
				}
				$scanner->readSym();
				
			} else if( $sym === Symbol::$sym_eof ){
				break;
			
			} else {
				// Parse next statement.
				if( ($res & Flow::NEXT_MASK) == 0 )
					$logger->error($scanner->here(), "unreachable statement");
			
				$res = Statement::parse($this->globals);
				$this->disposeDocBlock();
				if( $pkg->is_suspended ){
					$this->globals->addSuspended($this);
					return;
				}
			}
		}
		
		// Check for unused final DocBlock:
		$this->disposeDocBlock();

		if (!$code_found)
			$logger->notice($scanner->here(), "no PHP code found at all");


		$pkg->resolver->close($logger);

		$fn = $pkg->fn;

		/*
			Check missing implementation of functions declared 'forward'
			in this package. Note that prototype and actual code must be
			in the same package file.
		*/
		
		foreach($this->globals->functions as $f_mixed){
			$f = cast(Function_::class, $f_mixed);
			if( $f->is_forward && $f->decl_in->getFile()->equals($fn) ){
				$logger->error($f->decl_in, "missing implementation of the forward function " . $f->name);
			}
		}

		/*
			Check missing implementation of classes declared 'forward'
			in this package. Note that prototype and actual code must be
			in the same package file.
		*/

		foreach($this->globals->classes as $c_mixed){
			$c = cast(ClassType::class, $c_mixed);
			if( $c->is_forward && $c->decl_in->getFile()->equals($fn) ){
				$logger->error($c->decl_in, "missing implementation of the forward class " . $c->name);
			}
		}
		
		if( ! $pkg->is_library )
			$logger->notice(new Where($fn),
			"this package is not a library:\n" . $pkg->why_not_library);
		
		$pkg->scanner->close();
		$pkg->scanner = NULL;
		
		if( $logger->print_source )
			$logger->printVerbatim("END parsing of "
			. $logger->formatFileName($fn) . "\n");

		if ($pkg->loop_level !== "") {
			$logger->error(NULL, "phplint: INTERNAL ERROR: loop_level="
			. $pkg->loop_level);
		}

		if( $pkg->scope != 0 ){
			$logger->error(NULL, "phplint: INTERNAL ERROR: scope="
				. $pkg->scope);
		}

		if( $pkg->silencer_level != 0 ){
			$logger->error(NULL, "phplint: INTERNAL ERROR: silencer_level="
				. $pkg->silencer_level);
		}
	}
	

	/**
	 * Parse a package, that is a PHP source file.
	 * @param File $fn File name of the PHP source.
	 * @param boolean $is_module True if it is a module.
	 * @return void
	 * @throws FileNotFoundException
	 * @throws FilePermissionException
	 * @throws IOException
	 * @throws ParseException
	 * @throws ScannerException
	 */
	public function parse($fn, $is_module)
	{
		$logger = $this->globals->logger;
		if( $logger->print_source )
			$logger->printVerbatim("BEGIN parsing of "
			. $logger->formatFileName($fn) . "\n");

		$f = new FileInputStream($fn);
		$pkg = new Package($this->globals, $fn, $f, $is_module);
		// FIXME: this should go in Globals::loadPackage()
		$this->recursion_level = $this->globals->recursion_level - 1;
		$this->package = $pkg;
		$this->globals->packages->put($fn, $pkg);
		$this->globals->curr_pkg = $pkg;

		$pkg->scope = 0;
		$scanner = $pkg->scanner;

		// Report and skip initial text:
		if ($scanner->sym === Symbol::$sym_text) {
			$text = Strings::toLiteral(substr($scanner->s, 0, 20));

			if ($is_module) {
				$pkg->notLibrary("Found leading text in file before opening PHP tag: $text.");
				// FIXME: leading text in modules is not an error because ignored. ok?
			} else if (Pattern::matches("#!{ -\xff}+?\n", $scanner->s)) {
				$pkg->notLibrary("Unix CGI executable script detected: $text.");
			} else {
				$bom = array(
					/* BOM patterns and description: */
					array("\xfe\xff", "UTF-16 BE"),
					array("\xff\xfe", "UTF-16 LE"),
					array("\xef\xbb\xbf", "UTF-8"),
					array("\x00\x00\xfe\xff", "UTF-32 BE"),
					array("\xff\xfe\x00\x00", "UTF-32 LE")
				);

				for ($i = count($bom) - 1; $i >= 0; $i--)
					if (Strings::startsWith($pkg->scanner->s, $bom[$i][0]))
						break;

				if ($i >= 0) {
					$msg = "Unicode " . $bom[$i][1] . " BOM sequence detected: $text.";
					$pkg->notLibrary($msg);
					$logger->error($scanner->here(), "unsupported $msg");
				} else {
					$pkg->notLibrary("Bare textual content detected before PHP opening tag: $text.");
				}
			}
		}
		
		$this->continueParse();
	}
	
}
