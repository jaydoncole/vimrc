<?php

namespace it\icosaedro\io\tar;

require_once __DIR__ . "/../../../../all.php";

/*.
	require_module 'core';
	require_module 'posix';
	require_module 'pcre';
.*/

use ErrorException;
use RuntimeException;
use InvalidArgumentException;
use it\icosaedro\io\CorruptedException;
use it\icosaedro\io\InputStream;
use it\icosaedro\io\IOException;
use it\icosaedro\io\File;
use it\icosaedro\utils\UString;
use it\icosaedro\io\FileName;

/**
 * Reads a TAR archive, UStar sub-format (POSIX.1-1988) from the given input stream.
 * 
 * A TAR archive contains a sequence of zero or more entries representing nodes of
 * a *nix files system. Usually an entry represents a normal file or a directory,
 * but other special nodes are also allowed. Each entry, in turn, consists of an
 * header and, possibly, the binary content of the file.
 * 
 * <p><b>Encoding.</b> All the node paths, including symbolic links, contained in
 * a TAR archive should be ASCII, but this class assumes that anything else is UTF-8.
 * 
 * <p><b>Maximum file size.</b> The maximum size of each file in a TAR archive is
 * 8 GB. 32-bits PHP installations have a smaller limit of 2 GB; files larger than
 * that causes an exception. There is no limit to the length of a TAR archive.
 * 
 * <p><b>Data integrity.</b> Each header is protected by a weak checksum that may
 * only detect severe damages. File contents and the TAR archive as a whole are not
 * protected at all; only their size is checked against the size declared in the
 * header.
 * 
 * <p>See the companion {@link ./TarWriter.html TarWriter} class for more details about
 * the TAR file format, and {@link ./TarHeader.html TarHeader} for more details about
 * the TAR header.
 * 
 * <h3>Usage</h3>
 * This class allows to parse the TAR archive either by scanning its entries
 * one by one and retrieving only some entry of interest, or by extracting the
 * whole content of the TAR on a given local directory.
 * 
 * <p>The {@link self::extractAll()} method allows to extract the whole content
 * of the TAR archive to a given local extraction directory. Single entries of
 * the TAR archive can be extracted with the {@link self::extract()} method.
 * The following example shows how to extract the content of the MyProject.tar file
 * into the current working directory:
 * 
 * <blockquote><pre>
 * $cwd = File::getCWD();
 * $dst = $cwd;
 * $is = new FileInputStream(File::fromLocaleEncoded('MyProject.tar'), $cwd);
 * $tar = new TarReader($is);
 * $tar-&gt;extractAll($dst, $dst);
 * $tar-&gt;close();
 * </pre></blockquote>
 * 
 * <p>The {@link self::readHeader()} method returns the next TAR header and the
 * {@link self::readContent()} method allows to read the corresponding content.
 * The following example shows how to search for a file whose name contains the
 * word "updates" and extract him to a local file named "found_updates.dat":

 * <blockquote><pre>
 * $dst = File::getCWD();
 * $is = new FileInputStream(File::fromLocaleEncoded('MyProject.tar'), $cwd);
 * $tar = new TarReader($is);
 * while( ($header = $tar-&gt;readHeader()) !== NULL ){
 *	if( $header-&gt;type === TarHeader::TYPE_NORMAL_FILE
 *	&amp;&amp; strpos($header-&gt;filename, 'updates') !== FALSE ){
 *		$dst = File::fromLocaleEncoded('found_updates.dat', $cwd);
 *		$tar-&gt;extract($header, $dst);
 *		break;
 *	}
 * }
 * $tar-&gt;close();
 * </pre></blockquote>
 * 
 * <p>If the TAR file is compressed, the input stream must be filtered with the
 * corresponding decompressor filter. Example:

 * <blockquote><pre>
 * /&#42;. InputStream .&#42;/ $is = new FileInputStream(File::fromLocaleEncoded('MyProject.tar.gz');
 * $is = new GZIPInputStream($is);
 * $tar = new TarReader($is);
 * ...as above...
 * </pre></blockquote>
 * 
 * @author Umberto Salsi <salsi@icosaedro.it>
 * @version $Date: 2018/06/06 09:52:53 $
 */
class TarReader {
	
	/**
	 * @var InputStream
	 */
	private $is;
	
	/*
	 * Status of the reader. If false we are ready to read the next TAR header,
	 * otherwise we are reading the content part of the entry and the next 2
	 * properties give the total size and the current position in this content.
	 */
	private $status_reading_content = FALSE;
	private $status_current_file_size = 0;
	private $status_current_file_read = 0;
	
	/**
	 * Initializes the TAR reader.
	 * @param InputStream $is Source of the TAR stream.
	 */
	public function __construct($is) {
		$this->is = $is;
		$this->status_reading_content = FALSE;
	}

	/**
	 * Reads a block of 512 bytes.
	 * @return string Block of 512 bytes or NULL at the end of the TAR.
	 * @throws CorruptedException Incomplete 512-bytes block.
	 * @throws IOException Failed to read.
	 */
	private function readBlock() {
		$block = $this->is->readFully(512);
		$block_len = strlen($block);
		if( $block === NULL )
			return NULL;
		else if( $block_len == 512 )
			return $block;
		else
			throw new CorruptedException("incomplete 512-bytes block");
	}
	
	
	/**
	 * Reads a chunk of bytes from the current content. Call this method to retrieve
	 * the content of the current entry, normally just after having retrieved the
	 * corresponding header.
	 * @param int $n Non-negative maximum number of bytes to read.
	 * @return string Bytes read, not more of the number requested. Returns NULL
	 * at the end of the current content.
	 * @throws InvalidArgumentException $n is negative.
	 * @throws CorruptedException
	 * @throws IOException
	 */
	public function readContent($n) {
		if( ! $this->status_reading_content )
			return NULL;
		if( $n < 0 )
			throw new InvalidArgumentException("n=$n");
		$left = $this->status_current_file_size - $this->status_current_file_read;
		if( $left <= 0 ){
			$this->status_reading_content = FALSE;
			return NULL;
		}
		if( $n > $left )
			$n = $left;
		$data = $this->is->readBytes($n);
		if( $data === NULL )
			throw new CorruptedException("premature end");
		$data_len = strlen($data);
		$this->status_current_file_read += $data_len;
		$left = $this->status_current_file_size - $this->status_current_file_read;
		if( $left == 0 ){
			$this->status_reading_content = FALSE;
			$padding_len = (512 - $this->status_current_file_size % 512) & 511;
			$this->is->readFully($padding_len);
		}
		return $data;
	}
	
	
	/**
	 * Reads a chunk of bytes from the current content. Call this method to retrieve
	 * the content of the current entry, normally just after having retrieved the
	 * corresponding header.
	 * @param int $n
	 * @return string Exactly $n bytes read. A shorter chunk or NULL indicates the
	 * end of this content has been reached.
	 * @throws InvalidArgumentException
	 * @throws CorruptedException
	 * @throws IOException
	 */
	public function readFully($n) {
		// FIXME: for very big $n, use StringBuffer
		/*. string .*/ $s = NULL;
		do {
			$b = $this->readContent($n);
			if( $b === NULL )
				return $s;
			$s .= $b;
			$n -= strlen($b);
			if( $n <= 0 )
				return $s;
		} while(TRUE);
	}
	
	
	/**
	 * @throws CorruptedException
	 * @throws IOException
	 */
	private function skipContent() {
		while($this->status_reading_content) {
			$this->readContent(1024);
		}
	}
	
	
	/**
	 * @param string $s
	 * @return int
	 */
	private static function calcChecksum($s) {
		$res = 0;
		for($i = strlen($s) - 1; $i >= 0; $i--)
			$res += ord($s[$i]);
		return $res;
	}
	
	
	/**
	 * @param string $s
	 * @return int
	 * @throws CorruptedException
	 * @throws RuntimeException
	 */
	private static function parseOctal($s){
		$s = trim($s);
		if(preg_match("/^[0-7]+\$/sD", $s) !== 1)
			throw new CorruptedException("not an octal number: $s");
		$f = octdec($s);
		if( ! is_int($f) )
			throw new RuntimeException("octal number $s exceeds int capacity");
		return (int) $f;
	}
	

	/**
	 * Reads next TAR entry header.
	 * @return TarHeader TAR header or NULL at EOF or terminating TAR block.
	 * @throws CorruptedException
	 * @throws IOException
	 * @throws RuntimeException The value of a numeric field of the TAR header
	 * exceeds current int capacity.
	 */
	private function readRawHeader() {
		$block = $this->readBlock();
		if ($block === NULL) {
			// FIXME: should detect missing terminating block as corrupted.
			return NULL; // EOF
		}
		
		$entry = new TarHeader();
		
		$checksum = self::calcChecksum(substr($block, 0, 148))
			+ 8 * ord(' ')  // assuming 8 spaces for the checksum field
			+ self::calcChecksum(substr($block, 156, 356));

		if (version_compare(PHP_VERSION, "5.5.0-dev") < 0) {
			$fmt = "a100filename/a8mode/a8uid/a8gid/a12size/a12mtime/" .
					"a8checksum/a1typeflag/a100link/a6magic/a2version/" .
					"a32uname/a32gname/a8devmajor/a8devminor/a131prefix";
		} else {
			$fmt = "Z100filename/Z8mode/Z8uid/Z8gid/Z12size/Z12mtime/" .
					"Z8checksum/Z1typeflag/Z100link/Z6magic/Z2version/" .
					"Z32uname/Z32gname/Z8devmajor/Z8devminor/Z131prefix";
		}
		$data = cast("string[string]", unpack($fmt, $block));
		
		// Detect TAR termination marker block.
		if( $checksum == 256 && preg_match("/^\\000{512}\$/sD", $block) === 1 )
			return NULL;
		
		// Check checksum:
		$entry_checksum = self::parseOctal($data['checksum']);
		if ($entry_checksum !== $checksum)
			throw new CorruptedException('invalid checksum');

		// Extract fields:
		$filename = $data["filename"];
		$prefix = $data["prefix"];
		if (strlen($prefix) > 0) {
			$filename = "$prefix/$filename";
		}
		$entry->filename = $filename;
		$entry->mode = self::parseOctal($data['mode']);
		$entry->uid = self::parseOctal($data['uid']);
		$entry->gid = self::parseOctal($data['gid']);
		$entry->size = self::parseOctal($data['size']);
		$entry->mtime = self::parseOctal($data['mtime']);
		$entry->type = $data['typeflag'];
		$entry->link = trim($data['link']);
		if( trim($data['magic']) === 'ustar' ){
			$entry->isUStar = TRUE;
			$entry->UStarVersion = trim($data['version']);
			$entry->uname = trim($data['uname']);
			$entry->gname = trim($data['gname']);
			// GNU tar always sets dev* to blank; prevent parsing error adding leading '0':
			$entry->devmajor = self::parseOctal('0'.$data['devmajor']);
			$entry->devminor = self::parseOctal('0'.$data['devminor']);
		} else {
			$entry->isUStar = FALSE;
		}
		
		// Set state to read file content.
		$this->status_reading_content = TRUE;
		$this->status_current_file_size = $entry->size;
		$this->status_current_file_read = 0;
		
		return $entry;
	}
	

	/**
	 * Returns the next TAR header. If the header contains a node of interest
	 * for the client, the client may then call the {@link self::readContent()}
	 * method to read the file content, or just call this method again for the
	 * next header.
	 * @return TarHeader Next TAR header, or NULL at the end of the TAR.
	 * @throws IOException
	 * @throws RuntimeException The value of a numeric field of the TAR header
	 * exceeds current int capacity.
	 */
	public function readHeader() {
		// Skip current content if not fully read by client:
		$this->skipContent();
	
		// Read header:
		$header = $this->readRawHeader();
		if( $header === NULL )
			return NULL; // EOF

		// NOTE: for long file names, by default GNU tar creates a UStar entry
		// of type "L" and the file name in the content of the file as NUL
		// terminated string.
		// Instead, original Tar class creates an USstar header of type 'L' but
		// without the 'ustar' signature, then does bot check flag isUStar:
		
		// UStar entry of type 'L' carries the long name of the next file:
		if (/*$header->isUStar &&*/ $header->type === 'L')
		{
			$filename = rtrim($this->readFully($header->size), "\000");
			$next_header = $this->readRawHeader();
			if( $next_header === NULL )
				throw new CorruptedException("premature end");
			$next_header->filename = $filename;
			$header = $next_header;
		}
		
		return $header;
	}
	
	
	/**
	 * Resolved a node path of the TAR into a node of the local file system.
	 * @param string $path Filename, normally the filename field or link field of
	 * the last TAR header read. Relative paths are resolved against the extraction
	 * directory. Assumed UTF-8 encoded.
	 * @param File $extraction_directory Extraction directory. Does not need
	 * to really exist, as this function only performs abstract paths calculations.
	 * @param File $boundary Boundary directory the resolved paths must be
	 * confined to. Normally it is the local directory where the TAR archive is
	 * going to be expanded. If the resulting path refers to any location above
	 * this boundary, an exception is thrown.
	 * @return File Basically returns the extraction path joined with the given
	 * path.
	 * @throws TarMaliciousPathException
	 */
	public function resolvePath($path, $extraction_directory, $boundary = NULL) {
		$path_file = new File(UString::fromUTF8($path), $extraction_directory);

		// Check malicious reference above boundary:
		if( $boundary !== NULL ){
			// Strategy: $path must be either the boundary or must have
			// the boundary as parent:
			$dir = $path_file;
			do {
				if( $dir->equals($boundary) )
					break; // good
				$dir = $dir->getParentFile();
				if( $dir === NULL )
					throw new TarMaliciousPathException("$path_file");
			} while(TRUE);
		}
		
		return $path_file;
	}
	
	
	/**
	 * Creates all the parent directories of the given node, if required.
	 * Adding a single node with path using the GNU tar command, it creates a TAR
	 * entry without an explicit dir entry, so we must always check that all
	 * the dirs in the path do exist and create them as required. The mkdir()
	 * function already has an handy recursive feature that can be enabled.
	 * @param File $f
	 * @throws ErrorException
	 * @throws IOException
	 * @return void
	 */
	private function createParentDirs($f) {
		$parent_dir = $f->getParentFile();
		if( $parent_dir !== NULL ){
			$filename = $parent_dir->getLocaleEncoded();
			if( !file_exists($filename) )
				mkdir($filename, 0755, TRUE);
		}
	}
	
	
	/**
	 * Extracts to normal file the next content.
	 * @param TarHeader $header
	 * @param File $dst
	 * @return void
	 * @throws IOException
	 */
	private function extractAsNormalFile($header, $dst) {
		try {
			$this->createParentDirs($dst);
			$dst_locale_encoded = $dst->getLocaleEncoded();
			$f = fopen($dst_locale_encoded, "wb");
			while( ($chunk = $this->readContent(1024)) !== NULL )
				fwrite($f, $chunk);
			fclose($f);
			chmod($dst_locale_encoded, $header->mode);
		}
		catch(ErrorException $e){
			throw new IOException($e->getMessage());
		}
	}
	
	
	/**
	 * Extracts symbolic link. The link path contained in the header is checked,
	 * normalized, made relative if it is absolute, and encoded for the locale
	 * file system before being written in the link node.
	 * @param TarHeader $header
	 * @param File $dst Link file to create.
	 * @param File $boundary
	 * @return void
	 * @throws TarMaliciousPathException
	 * @throws IOException
	 */
	private function extractAsSymbolicLink($header, $dst, $boundary) {
		$parent_dir = $dst->getParentFile();
		
		// Resolve relative link:
		$link = $this->resolvePath($header->link, $parent_dir, $boundary);
		
		// Convert link to a relative path; convert to locale encoding:
		$link_relative = FileName::encode($link->relativeTo($parent_dir));
		
		// Create symlink:
		try {
			$this->createParentDirs($dst);
			symlink($link_relative, $dst->getLocaleEncoded());
		} catch(ErrorException $e){
			throw new IOException($e->getMessage());
		}
	}
	
	
	/**
	 * Extracts to the local file system the current entry from the TAR archive.
	 * <br><b>Limitations.</b> Only directories, normal files, symbolic links
	 * and FIFO are actually extracted; any other type of node is ignored.
	 * In particular, these types are ignored: character special, block special,
	 * hard link.
	 * <br>On Windows, symbolic links are ignored too because there is no reliable
	 * way to enable the permissions to do that; furthermore, the link must refer
	 * to an already existing node, which might be defined only next in the TAR
	 * or might exist at all. For further details, search for the <tt>SeCreateSymbolicLinkPrivilege</tt>
	 * privilege on Windows.
	 * @param TarHeader $header Header of the entry we are going to extract.
	 * Normally it is the header as just read by this class, but the client has
	 * a chance for some fine tuning.
	 * @param File $dst Destination name of the extracted node. Note that this is
	 * not just the "destination directory", it is really the whole filename with
	 * path of the node to create.
	 * @param File $boundary Boundary directory where all extracted paths must be
	 * confined to. This boundary applies also to links and to the node they point
	 * to. Normally it is the local directory where the TAR archive is going to
	 * be expanded. If a path refers to any location above this boundary, an
	 * exception is thrown.
	 * @return void
	 * @throws TarMaliciousPathException
	 * @throws IOException
	 */
	public function extract($header, $dst, $boundary = NULL) {
		switch($header->type) {
			
			case TarHeader::TYPE_DIRECTORY:
				if( ! $dst->exists() ){
					try {
						$this->createParentDirs($dst);
						mkdir($dst->getLocaleEncoded(), $header->mode);
					}
					catch(ErrorException $e){
						throw new IOException($e->getMessage());
					}
				}
				break;
			
			case TarHeader::TYPE_NORMAL_FILE:
			case TarHeader::TYPE_CONTIGUOUS_FILE:
				$this->extractAsNormalFile($header, $dst);
				break;
			
			case TarHeader::TYPE_SYMBOLIC_LINK:
				if( DIRECTORY_SEPARATOR === "\\" )
					return; // Windows: give up.
				$this->extractAsSymbolicLink($header, $dst, $boundary);
				break;
			
			case TarHeader::TYPE_FIFO:
				try {
					$this->createParentDirs($dst);
				}
				catch(ErrorException $e){
					throw new IOException($e->getMessage());
				}
				if( ! posix_mkfifo($dst->getLocaleEncoded(), $header->mode) )
					throw new IOException("creating FIFO $dst: "
						. posix_strerror(posix_errno()));
				break;
				
			case TarHeader::TYPE_BLOCK_SPECIAL:
			case TarHeader::TYPE_CHARACTER_SPECIAL:
			case TarHeader::TYPE_HARD_LINK:
				/* ignore */
				break;
			
			default:
				/* ignore */
		}
	}
	
	
	/**
	 * Extracts to the local file system all the remaining entries from the TAR archive.
	 * Uses the {@link self::extract()} method to extract each entry; see the details
	 * of that method for limitations.
	 * @param File $extraction_directory Local extraction directory of the extracted
	 * nodes. If this directory does not exist, or some of the sub-directories in
	 * its path does not exist, them will be created using the current user identity
	 * and permissions.
	 * @param File $boundary Local boundary directory where all extracted paths
	 * must be confined. This boundary applies also to the links and to the nodes
	 * they point to. Normally it is the extraction directory. If a path refers
	 * to any location above this boundary, an exception is thrown.
	 * @return void
	 * @throws TarMaliciousPathException
	 * @throws CorruptedException
	 * @throws IOException
	 * @throws RuntimeException The value of a numeric field of the TAR header
	 * exceeds current int capacity.
	 */
	public function extractAll($extraction_directory, $boundary = NULL) {
		do {
			$header = $this->readHeader();
			if( $header === NULL )
				return;
			$fn = $this->resolvePath($header->filename, $extraction_directory, $extraction_directory);
			$this->extract($header, $fn, $boundary);
		} while(TRUE);
	}
	
	
	/**
	 * Closes the input stream. Does nothing if already closed.
	 * @throws IOException
	 */
	public function close() {
		if( $this->is === NULL )
			return;
		$this->is->close();
		$this->is = NULL;
	}
	
}
