<?php
require_once __DIR__ . "/../stdlib/all.php";
use it\icosaedro\io\ResourceOutputStream;
use it\icosaedro\lint\Linter;

/**
 * PHPLint - Validator and documentator for PHP programs.
 * Runs PHPLint from the command line and displays the report
 * on standard output.
 * Syntax of the command:
 * <blockquote><pre>
 * php PHPLint.php [OPTIONS] file.php ...
 * </pre></blockquote>
 * To get a list of the available options, use the <code>--help</code>
 * option. The program exit status is 0 (no errors) or 1 (errors found).
 * Info, download and updates: {@link http://www.icosaedro.it/phplint}.
 * @author Umberto Salsi <salsi@icosaedro.it>
 * @copyright 2018 by icosaedro.it di Umberto Salsi <salsi@icosaedro.it>
 * @version $Date: 2018/04/03 08:21:31 $
 * @package PHPLint
 */

/*. private .*/ $os = new ResourceOutputStream( fopen("php://output", "wb") );
/*. private .*/ $err = Linter::main($os, $argv);
exit($err);
