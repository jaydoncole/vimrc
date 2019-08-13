<?php

require_once __DIR__ . "/../../../../../../stdlib/all.php";
require_once __DIR__ . "/../../../../../../stdlib/utf8.php";

use it\icosaedro\io\tar\TarWriter;
use it\icosaedro\io\tar\TarReader;
use it\icosaedro\io\tar\TarHeader;
use it\icosaedro\io\InputStream;
use it\icosaedro\io\OutputStream;
use it\icosaedro\io\StringOutputStream;
use it\icosaedro\io\FileOutputStream;
use it\icosaedro\io\FileInputStream;
use it\icosaedro\io\File;
use it\icosaedro\io\GZIPOutputStream;
use it\icosaedro\io\GZIPInputStream;
use it\icosaedro\io\BZip2OutputStream;
use it\icosaedro\io\IOException;
use it\icosaedro\utils\Strings;

// In auto-test mode, avoid any output and remove tmp files.
const AUTO_TEST = TRUE;

//Destination dir of the read TARs.
define("OUT_DIR", __DIR__ . "/out");
	
	
/**
 * Recursively deletes a directory and all its contents.
 * Only files of type "dir", "file" and "socket" are deleted; if any other
 * type of file "char", "block" or "unknown" is found, an
 * exception is thrown.
 * @access private
 * @param string $dir
 * @return void
 * @throws ErrorException Access failed to the file system. Found an unexpected
 * type of file.
 */
function recursivelyDeleteDirectory($dir)
{
	$d = opendir($dir);
	while( ($fn = readdir($d)) !== FALSE ){
		if( $fn === "." || $fn === ".." )
			continue;
		$full = "$dir/$fn";
		$type = filetype($full);
		if( $type === "dir" )
			recursivelyDeleteDirectory($full);
		else if( $type === "file" || $type === "socket" || $type === "link" || $type === "fifo" )
			unlink($full);
		else
			throw new ErrorException("unexpected type of file '$type' for $full");
	}
	closedir($d);
	rmdir($dir);
}
	

/**
 * 
 * @access private
 * @param string $s
 * @param string[int] $tails
 */
function stringEndsWithAnyOf($s, $tails) {
	foreach($tails as $tail)
		if( Strings::endsWith($s, $tail))
			return $tail;
	return NULL;
}


/**
 * 
 * @access private
 * @param string $fn
 * @return TarReader
 * @throws IOException
 */
function openTar($fn) {
	$name = File::fromLocaleEncoded($fn, File::getCWD());
	/*. InputStream .*/ $is = new FileInputStream($name);
	
	// apply decompression filter if required:
	if( stringEndsWithAnyOf($fn, array(".tar.gz", ".tgz")) !== NULL )
		$is = new GZIPInputStream($is);
	// BZip2InputStream is deprecated
//	else if( stringEndsWithAnyOf($fn, array(".tar.bz2", ".tbz", ".tbz2", ".tb2")) !== NULL )
//		$is = new BZip2InputStream($is);
	else if( ! Strings::endsWith ($fn, ".tar") )
		throw new \InvalidArgumentException("unknown or unsupported file type or compression method");
	
	return new TarReader($is);
}


/**
 * 
 * @access private
 * @param string $fn
 * @return TarWriter
 * @throws IOException
 */
function createTar($fn) {
	$name = File::fromLocaleEncoded($fn, File::getCWD());
	/*. OutputStream .*/ $os = new FileOutputStream($name);
	
	// apply compression filter if required:
	if( stringEndsWithAnyOf($fn, array(".tar.gz", ".tgz")) !== NULL )
		$os = new GZIPOutputStream($os);
	else if( stringEndsWithAnyOf($fn, array(".tar.bz2", ".tbz", ".tbz2", ".tb2")) !== NULL )
		$os = new BZip2OutputStream($os);
	else if( ! Strings::endsWith ($fn, ".tar") )
		throw new \InvalidArgumentException("unknown or unsupported file type or compression method");
	
	return new TarWriter($os);
}


/**
 * 
 * @access private
 * @param string $fn
 * @param boolean $show_content
 * @throws IOException
 */
function showTarContent($fn, $show_content = TRUE) {
	if( AUTO_TEST )
		return;
	echo "\nListing: $fn\n";
	$tar = openTar($fn);
	$dst_dir = File::fromLocaleEncoded(__DIR__ . "/out");
	while( ($header = $tar->readHeader()) !== NULL ){
		echo "    Header: $header\n";
		echo "    Destination: ", $tar->resolvePath($header->filename, $dst_dir, $dst_dir), "\n";
		if( $show_content ){
			while( ($data = $tar->readContent(100)) !== NULL ){
				echo "    Content: " . Strings::toLiteral($data) . "\n";
			}
		}
	}
	$tar->close();
}


/**
 * 
 * @access private
 * @param string $fn
 * @return void
 * @throws IOException
 * @throws ErrorException
 */
function extractTar($fn) {
	$dst_dir = File::fromLocaleEncoded(__DIR__ . "/out/EXTRACT-" . basename($fn));
	//echo "\nExtracting: $fn --> $dst_dir\n";
	$tar = openTar($fn);
	$tar->extractAll($dst_dir, $dst_dir);
	$tar->close();
}


/**
 * @access private
 * @throws IOException
 * @throws ErrorException
 */
function inMemoryHandling()
{
	$os = new StringOutputStream();
	$tar = new TarWriter($os);
	
	$params = array();
	$params['mtime'] = 1; // fixed mtime for easier diff
	
	$params['type'] = TarHeader::TYPE_DIRECTORY;
	$tar->writeEntry("project/", "", $params);
	
	$params['type'] = TarHeader::TYPE_NORMAL_FILE;
	$tar->writeEntry("project/readme.txt", "Content of the readme.txt file.", $params);
	
	$params['type'] = TarHeader::TYPE_NORMAL_FILE;
	$tar->writeEntry("project/document.txt", "Content of the document.txt file.", $params);
	
	$params['type'] = TarHeader::TYPE_DIRECTORY;
	$tar->writeEntry("project/subdir/", "", $params);
	$tar->writeNode(File::fromLocaleEncoded(__DIR__."/in/0-bytes-file.txt"), "project/subdir/file-empty.txt", $params);
	$tar->writeNode(File::fromLocaleEncoded(__DIR__."/in/512-bytes-file.txt"), "project/subdir/512-bytes-file.txt", $params);
	$tar->writeNode(File::fromLocaleEncoded(__DIR__."/in/513-bytes-file.txt"), "project/subdir/513-bytes-file.txt", $params);
	
	$tar->close();
	
	$fn = OUT_DIR . "/generated-in-memory.tar";
	file_put_contents($fn, $os->__toString());
	showTarContent($fn);
}


/**
 * 
 * @param string $title
 * @param string $data
 * @throws ErrorException
 */
function showRandomData($title, $data) {
	if( AUTO_TEST )
		return;
	echo "Random data test: $title\n";
	$fn = OUT_DIR."/random.tar";
	file_put_contents($fn, $data);
	try {
		showTarContent($fn);
	}
	catch(Exception $e){
		echo get_class($e), ": ", $e->getMessage(), "\n";
	}
}


/**
 * @throws ErrorException
 */
function randomFileReading() {
	showRandomData("Non-octal", str_repeat("?", 512));
	showRandomData("Octal digits 0", str_repeat("0", 512));
}


/**
 * @access private
 * @throws Exception
 */
function customTarCreation() {

	// Use a fixed timestamp so the results are comparable with expected result:
	$timestamp = (new DateTime("2000-01-01Z00:00:00"))->getTimestamp();

	// Using TarWriter:
	$tar_filename = OUT_DIR . "/created1.tar.gz";
	$tar = createTar($tar_filename);
	$tar->writeEntry("test1/empty.txt", "", array("mtime" => $timestamp));
	$tar->writeEntry("test1/512-bytes.txt", str_repeat("x", 512), array("mtime" => $timestamp));
	$tar->writeEntry("test1/file1.txt", "Content of file1.txt.", array("mtime" => $timestamp + 1));
	$tar->writeEntry("test1/file2.txt", "Content of file2.txt.", array("mtime" => $timestamp + 2));
	$tar->writeEntry("test1/file3dir/file3.txt", "Content of file3dir/file3.txt.", array("mtime" => $timestamp + 3));

	$long_name = "test1/file-with-very-long-name-" . str_repeat("1234567890", 10) . ".txt";
	$tar->writeEntry($long_name, "Content of $long_name.", array("mtime" => $timestamp + 4));

	$tar->writeEntry("test1/file5.txt", "Content of file5.txt.", array("mtime" => $timestamp + 5));
	$tar->close();
	
	showTarContent($tar_filename);
	extractTar($tar_filename);
}


/**
 * @throws Exception
 */
function main() {

	// Use relative paths as much as possible to compare results:
	chdir(__DIR__);
	
	if(file_exists(OUT_DIR))
		recursivelyDeleteDirectory(OUT_DIR);
	mkdir(OUT_DIR);
	
	// TarReader: parse and extract GNU tar samples:
	
	showTarContent("in/one-empty-file.tar");
	extractTar("in/one-empty-file.tar");
	
	showTarContent("in/two-files.tar");
	extractTar("in/two-files.tar");
	
	showTarContent("in/with-very-long-file-name.tar");
	extractTar("in/with-very-long-file-name.tar");
	
	showTarContent("in/with-subdir-and-symlink.tar");
	extractTar("in/with-subdir-and-symlink.tar");

	randomFileReading();
	
	inMemoryHandling();
	
	customTarCreation();

	
	if(AUTO_TEST && file_exists(OUT_DIR))
		recursivelyDeleteDirectory(OUT_DIR);
}

main();
