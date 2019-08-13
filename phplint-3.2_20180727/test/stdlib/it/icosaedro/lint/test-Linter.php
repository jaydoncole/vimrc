<?php

namespace it\icosaedro\lint;
require_once __DIR__ . "/../../../../../stdlib/all.php";
use it\icosaedro\io\File;
use it\icosaedro\io\OutputStream;
use it\icosaedro\io\FileInputStream;
use it\icosaedro\io\ResourceOutputStream;
use it\icosaedro\io\IOException;
use it\icosaedro\utils\UString;
use it\icosaedro\utils\SimpleDiff;
use it\icosaedro\io\FileOutputStream;
use it\icosaedro\io\LineInputWrapper;
use it\icosaedro\containers\Arrays;

/**
 * Runs PHPLint over all the *.php test files of the test/list/ directory.
 * The new report is saved in the file *.report.DIFFERS.txt of the same directory
 * and compared with the previous report file *.report.txt: if equal, the
 * new report is deleted; if differences are found, the new report is left
 * there, and is the responsability of the PHPLint developer to examine
 * carefully the differences found and possibly replace the old report with
 * the new one.
 * @author Umberto Salsi <salsi@icosaedro.it>
 * @version $Date: 2018/04/13 11:31:47 $
 */
class TestLinter {


	/**
	* @param OutputStream $os
	* @param int $php_version
	* @param File $fn 
	* @return void
	*/
	private static function test($os, $php_version, $fn)
	{
		try {
			// PHPLint base directory:
			$base = __DIR__ . "/../../../../..";
			$argv = array(
				"phplint.php",
				#"--version",
				"--print-path", "shortest",
				"--modules-path", "$base/modules",
				"--php-version", "$php_version",
				"--print-errors",
				"--print-warnings",
				"--print-notices",
				"--ascii-ext-check",
				"--ctrl-check",
				"--recursive",
				"--no-print-file-name",
				"--print-path", "relative",
				"--parse-phpdoc",
				"--print-context",
				"--print-source",
				"--print-line-numbers",
				"--report-unused",
				$fn->__toString()
			);
			
			$err = Linter::main($os, $argv);

		}
		catch(IOException $e){
			error_log($e->getMessage() . "\n");
			exit(1);
		}
	}


	/**
	 * Generates all the report files. Differences are sent to stdout.
	 * @param string[int] @files List of files to test in the data/ directory.
	 * @return void
	 * @throws IOException Something went wrong accessing the test files;
	 * fix and retry. 
	 */
	public static function main($argv)
	{
		$good = 0;  // new report equals old one
		$bad = 0; // new report differs
		$generated = 0;  // no old report available; report generated
		$tests_dir = File::fromLocaleEncoded(__DIR__ . "/data");
		$tests_dir->setCWD();
		if( count($argv) <= 1 ){
			$tests = $tests_dir->listFiles();
		} else {
			$tests = /*. (File[int]) .*/ array();
			for($i = 1; $i < count($argv); $i++)
				$tests[] = File::fromLocaleEncoded(__DIR__ . "/" . $argv[$i]);
			$tests = cast(File::class."[int]", Arrays::sort($tests));
		}
		$skip = 0; // how many initial tests to skip
		$os = new ResourceOutputStream(STDOUT);
		foreach($tests as $test){
			if( ! $test->getName()->endsWith( UString::fromASCII(".php") ) )
				continue;
			if( $test->getName()->startsWith(UString::fromASCII("5") ) )
				$php_version = 5;
			else if( $test->getName()->startsWith(UString::fromASCII("7") ) )
				$php_version = 7;
			else
				$php_version = 5;
			if( $skip > 0 ){
				$skip--;
				continue;
			}
			$report = new File($test->getBaseName()->append(
					UString::fromASCII(".report.txt")));
			// FIXME: if $report does not exist, we must generate it
			$report_new = new File($test->getBaseName()->append(
					UString::fromASCII(".report.DIFFERS.txt")));
			echo("\n==== $test: ====\n");
			if( $report->exists() ){
				//$os->writeBytes("   report: " . $report . "\n");
				$report_new_fos = new FileOutputStream( $report_new );
				self::test($report_new_fos, $php_version, $test);
				$report_new_fos->close();
				$t1 = new LineInputWrapper( new FileInputStream($report));
				$t2 = new LineInputWrapper( new FileInputStream($report_new));
				$ok = SimpleDiff::areEqual($t1, $t2, $os);
				$t1->close();
				$t2->close();
				if( $ok ){
					echo "notice: test passed\n";
					$report_new->delete();
					$good++;
				} else {
					echo "ERROR: differences found\n";
					$bad++;
				}
			} else {
				//$os->writeBytes("   report: " . $report . "\n");
				self::test(new FileOutputStream( $report ), $php_version, $test);
				echo "notice: test report generated\n";
				$generated++;
			}
		}
		//self::test($os, TRUE, File::fromLocaleEncoded(""));
		
		echo "Overall tests summary: $good passed, $bad differ, $generated generated.\n";
	}

}

TestLinter::main($argv);
