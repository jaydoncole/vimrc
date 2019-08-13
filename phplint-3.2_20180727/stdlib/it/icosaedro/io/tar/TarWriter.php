<?php

namespace it\icosaedro\io\tar;

require_once __DIR__ . "/../../../../all.php";

/*.
	require_module 'core';
	require_module 'posix';
.*/

use RuntimeException;
use ErrorException;
use InvalidArgumentException;
use it\icosaedro\io\OutputStream;
use it\icosaedro\io\IOException;
use it\icosaedro\io\File;
use it\icosaedro\containers\Arrays;

/**
 * Writes a TAR file, UStar sub-format (POSIX.1-1988).
 * 
 * <h3>TAR file format limitations</h3>
 * A TAR file contains a sequence of 0+ entries and a terminator marker, each entry
 * representing a file or a directory, or node for short. Each entry, in turn, consists of an header
 * and a content. The header contains the node name, the type, the owner and the
 * permission flags: these are the typical attributes common to any *nix file system.
 * For regular files, the content part of the entry contains the content of the
 * file. The maximum file length in TAR entry is 8 GB, but on 32-bits PHP installations
 * there is a further 2 GB limit per single file entry.
 * 
 * <p>
 * Node names written in a TAR file should always be relative paths; putting absolute
 * paths in a TAR file is discouraged but not forbidden by this class (see also
 * "tar bomb"). Normally a TAR file contains only a directory and all the nodes
 * and sub-directories beneath it. The TAR format does not allow to specify the
 * encoding of these node names, but nowaday UTF-8 is the common default encoding
 * on most *nix and this class follows this same practice.
 * Entries can be added in any order, but for maximum portability directories
 * should precede any node beneath them. Duplicated entries are allowed; readers just
 * will pitch the last one.
 * 
 * <p>
 * For more details about the TAR file format, see
 * {@link https://en.wikipedia.org/wiki/Tar_%28computing%29}.
 * 
 * <h3>Usage</h3>
 * The resulting TAR-encoded stream of bytes is written to a generic output stream
 * you must supply in the constructor method. By chaining several stream filters, it
 * is quite easy to send the resulting stream to an actual file, to some compressor,
 * or directly in a buffer in memory for further processing.
 * <p>
 * Once created, the Tar object represents an empty TAR file. Several methods allows
 * to append entries to the archive, either from data in memory or from actual files.
 * Once finished generating the archive, the close() method must be invoked to write
 * the TAR terminator marker and to close the output stream.
 * <p>
 * The following example creates a TAR archive in a buffer in memory containing
 * a directory "myproject/" and with one file "myproject/README.txt" in it:
 * 
 * <blockquote><pre>
	// Creates a destination output stream in memory:
	$os = new StringOutputBuffer();
	// Creates a TAR writer:
	$tar = new TarWriter($os);
	// Add a directory:
	$params = array();
	$params['type'] = TarHeader::TYPE_DIRECTORY;
	$tar-&gt;writeEntry('myproject/', '', $params);
	// Add a file to that directory:
	$tar-&gt;writeEntry('myproject/README.txt', 'This is my project!');
	// Closes the TAR stream.
	$tar-&gt;close();
	// Retrieve the binary TAR:
	$tar_file_content = $os-&gt;__toString();
 * </pre></blockquote>
 * 
 * <p>
 * The following example combines the file writer output stream with a GZIP
 * compressor to write the "myproject.tar.gz" file on disk; the whole content
 * of the "myproject" directory is put in the file, and a little "RELEASE_NOTES.txt"
 * entry is also added on the fly:
 * 
 * <blockquote><pre>
	// Create a GZIP compressed file output stream:
	$dst_name = __DIR__ . 'myproject.tar.gz';
	/&#42;. OutputStream .&#42;/ $os = new FileOutputStream(File::fromLocaleEncoded($dst_name));
	$os = new GZIPOutputStream($os);
	// Creates a TAR writer:
	$tar = new TarWriter($os);
	// Add a directory and its content:
	$node = File::fromLocaleEncoded(__DIR__ . '/myproject');
	$tar-&gt;writeRecursive('', $node);
	// Add a file generated on the fly:
	$tar-&gt;writeEntry('myproject/RELEASE_NOTES.txt', 'It works!');
	// Closes the file.
	$tar-&gt;close();
 * </pre></blockquote>
 * 
 * <h3>Custom parameters</h3>
 * Some methods allows to specify an array of custom parameters as an associative
 * array whose keys are the names of the parameters. If not specified otherwise,
 * these parameters and their meaning is as follows:
 * <blockquote>
 * mtime =&gt; the datetime (replaces datetime above if it exists)<br>
 * perms =&gt; the permissions on the node (600 by default)<br>
 * type =&gt; type of the node, one of the constants {@link \it\icosaedro\io\tar\TarHeader}::TYPE_*
 * or a capital letter, normally a customized file or a directory
 * (default = {@link \it\icosaedro\io\tar\TarHeader::TYPE_NORMAL_FILE})<br>
 * link =&gt; if this entry represents a link, it is the target node name
 * (default = '')<br>
 * uid =&gt; the user ID of the node (default = 0 = root)<br>
 * gid =&gt; the group ID of the node (default = 0 = root)<br>
 * uname =&gt; the user name of the node (default = "root")<br>
 * gname =&gt; the group name of the node (default = "root")<br>
 * devmajor =&gt; the major device number of the node (default = 0)<br>
 * devminor =&gt; the minor device number of the node (default = 0)<br>
 * </blockquote>
 * 
 * <h3>Common TAR file name extensions</h3>
 * <tt>.tar</tt> Plain TAR file.<br>
 * <tt>.tar.gz, .tgz</tt> gzip compressed TAR.<br>
 * <tt>.tar.bz2, .tbz, .tbz2, .tb2</tt> bzip2 compressed TAR.<br>
 * <tt>.tar.Z, .taz, .tz</tt> Ancient *nix 'compress' program.<br>
 * <tt>.tar.lzma, .tar.lz, .tlz</tt> lzma compressed TAR.<br>
 * <tt>.tar.xz, .txz</tt> xz compressed TAR.<br>
 * 
 * @author Umberto Salsi <salsi@icosaedro.it>
 * @version $Date: 2016/01/26 12:26:58 $
 */
class TarWriter {
	
	/**
	 * Destination of the generated TAR.
	 * @var OutputStream
	 */
	private $os;
	
	
	/**
	 * Creates e new, empty TAR archive.
	 * @param OutputStream $os Destination of the TAR.
	 */
	public function __construct($os) {
		$this->os = $os;
	}
	
	
	/**
	 * Writes a 512-bytes long block.
	 * @param string $block
	 * @throws IOException
	 * @throws RuntimeException
	 */
	private function writeBlock($block) {
		if( strlen($block) != 512 )
			throw new RuntimeException("invalid block size");
		$this->os->writeBytes($block);
	}
	
	
	/**
	 * Simple checksum used in heach header.
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
	 * Writes an header block.
	 * @param string $filename
	 * @param int $perms
	 * @param int $uid
	 * @param int $gid
	 * @param int $size
	 * @param int $mtime
	 * @param string $typeflag
	 * @param string $link
	 * @param string $magic
	 * @param string $version
	 * @param string $uname
	 * @param string $gname
	 * @param int $devmajor
	 * @param int $devminor
	 * @param string $prefix
	 * @return void
	 * @throws InvalidArgumentException
	 * @throws IOException
	 */
	private function writeRawHeader($filename, $perms, $uid, $gid, $size, $mtime,
			$typeflag, $link, $magic, $version, $uname, $gname, $devmajor,
			$devminor, $prefix)
	{
		// FIXME: detect strings containing NUL or terminated with space.
		if( strlen($filename) > 99 )
			throw new InvalidArgumentException("node name longer than 99 bytes");
		if( !(0 <= $perms && $perms <= 07777777) )
			throw new InvalidArgumentException("perms = $perms");
		if( !(0 <= $uid && $uid <= 07777777) )
			throw new InvalidArgumentException("uid = $uid");
		if( !(0 <= $gid && $gid <= 07777777) )
			throw new InvalidArgumentException("gid = $gid");
		// check $size range: be carefull in the transition from 32 to 64 bits PHP:
		if( !(is_int($size) && 0 <= $size && $size <= 077777777777) )
			throw new InvalidArgumentException("size = $size");
		if( strlen($typeflag) != 1 )
			throw new InvalidArgumentException("typeflag = $typeflag (expected 1 char)");
		if( strlen($link) > 99 )
			throw new InvalidArgumentException("link name longer than 99 bytes");
		if( strlen($magic) > 6 )
			throw new InvalidArgumentException("magic = $magic (max 6 chars)");
		if( strlen($version) > 2 )
			throw new InvalidArgumentException("version = $version (max 2 chars)");
		if( strlen($uname) > 32 )
			throw new InvalidArgumentException("uname = $uname (max 32 chars)");
		if( strlen($gname) > 32 )
			throw new InvalidArgumentException("gname = $gname (max 32 chars)");
		if( !(0 <= $devmajor && $devmajor <= 07777777) )
			throw new InvalidArgumentException("devmajor = $devmajor");
		if( !(0 <= $devminor && $devminor <= 07777777) )
			throw new InvalidArgumentException("devminor = $devminor");
		if( strlen($prefix) > 154 )
			throw new InvalidArgumentException("prefix longer than 154 bytes");
		
		// before checksum:
		$block_before = pack(
				"a100a8a8a8a12a12",
				$filename,
				sprintf("%07o", $perms),
				sprintf("%07o", $uid),
				sprintf("%07o", $gid),
				sprintf("%011o", $size),
				sprintf("%011o", $mtime)
		);
		// after checksum:
		$block_after = pack(
				"a1a100a6a2a32a32a8a8a155a12",
				$typeflag,
				$link,
				$magic,
				$version,
				$uname,
				$gname,
				sprintf("%07o", $devmajor),
				sprintf("%07o", $devminor),
				$prefix,
				''
		);
		
		// checksum:
		$checksum = self::calcChecksum($block_before)
			+ 8 * ord(' ')  // assuming 8 spaces for the checksum field
			+ self::calcChecksum($block_after);
		$block_checksum = pack("a8", sprintf("%07o", $checksum));

		$this->writeBlock($block_before . $block_checksum . $block_after);
	}
	

	/**
	 * Writes 'long filename' entry.
	 * The caller should then write a regular entry with a shortened node
	 * name, compatible with the old TAR format.
	 * @param string $filename
	 * @return void
	 * @throws IOException
	 */
	private function writeLongHeaderAndFileName($filename) {
		$size = strlen($filename);
		$type = 'L';
		$link = '';
		$magic = 'ustar';
		$version = '';
		$uname = '';
		$gname = '';
		$devmajor = 0;
		$devminor = 0;
		$prefix = '';
		$this->writeRawHeader('././@LongLink', 0, 0, 0, $size, 0, $type, $link,
				$magic, $version, $uname, $gname, $devmajor, $devminor, $prefix);
		
		// Write the filename as content of the next block
		$i = 0;
		while (strlen($buffer = substr($filename, (($i++) * 512), 512)) > 0) {
			$binary_data = pack("a512", $buffer);
			$this->writeBlock($binary_data);
		}
	}
	
	
	private static $VALID_PARAMS = array("perms", "uid", "gid", "mtime", "type",
		"link", "uname", "gname", "devmajor", "devminor");
	

	/**
	 * Adds a generic header, possibly using the UStar double TAR header if the
	 * filename is too long.
	 *
	 * @param string $filename
	 * @param int $size
	 * @param mixed[] $params Custom parameters (see general description of this
	 * class for more).
	 * @return void
	 * @throws IOException
	 * @throws InvalidArgumentException Supplied custom parameter does not exist
	 * or its value is not valid.
	 */
	private function writeHeader($filename, $size, $params)
	{
		foreach($params as $k => $v)
			if( !in_array($k, self::$VALID_PARAMS) )
				throw new InvalidArgumentException("unknown parameter " .(string)$k);
			
		// $filename = ...
		$uid = isset($params["uid"]) ? (int) $params["uid"] : 0;
		$gid = isset($params["gid"]) ? (int) $params["gid"] : 0;
		// $size = ...
		$mtime = isset($params["mtime"]) ? (int) $params["mtime"] : time();
		$type = isset($params["type"]) ? (string) $params["type"] : TarHeader::TYPE_NORMAL_FILE;
		$perms = isset($params["perms"]) ? (int) $params["perms"]
				: ($type === TarHeader::TYPE_DIRECTORY? 0700 : 0600);
		$link = isset($params["link"]) ? (string) $params["link"] : '';
		$uname = isset($params["uname"]) ? (string) $params["uname"] : "root";
		$gname = isset($params["gname"]) ? (string) $params["gname"] : "root";
		$devmajor = isset($params["devmajor"]) ? (int) $params["devmajor"] : 0;
		$devminor = isset($params["devminor"]) ? (int) $params["devminor"] : 0;
		$prefix = '';
		
		if (strlen($filename) > 99) {
			$this->writeLongHeaderAndFileName($filename);
			$filename = substr($filename, 0, 99);
		}
		
		$magic = 'ustar ';
		$version = ' ';
		$this->writeRawHeader($filename, $perms, $uid, $gid, $size, $mtime,
				$type, $link, $magic, $version, $uname, $gname, $devmajor,
				$devminor, $prefix);
	}
	

	/**
	 * Adds a single entry at the end of the existing archive.
	 * If the archive does not yet exists it is created.
	 * This method allows to write pretty anything to the TAR file, with the only
	 * limitation that the 'size' field of the header will match the actual size
	 * of the content -- a minimal requirement that allows the reader to skip
	 * this entry for the next.
	 *
	 * @param string $filename Filename of the node in the TAR archive.
	 * About the character encoding of this string, see the general description of
	 * this class.
	 * For maximum compatibility with older tar programs, a directory name should
	 * always terminate with a forward slash character '/'.
	 * @param string $content If a regular file, this is its content, otherwise
	 * this parameter should be the empty string or NULL.
	 * @param mixed[] $params Custom parameters (see general description of this
	 * class for more).
	 * @return void
	 * @throws IOException
	 * @throws InvalidArgumentException Supplied custom parameter does not exist
	 * or its value is not valid.
	 */
	public function writeEntry($filename, $content, $params = array()) {
		$this->writeHeader($filename, strlen($content), $params);

		$i = 0;
		while (strlen($buffer = substr($content, (($i++) * 512), 512)) > 0) {
			$block = pack("a512", $buffer);
			$this->writeBlock($block);
		}
	}
	
	
	/**
	 * Bit masks for the "mode" field of lstat(). Mostly copied from the manual
	 * page of the C's stat() function. Hopefully valid on any system.
	 * @access private
	 */
	const
		S_IFMT   =  0170000, //   bit mask for the node type bit fields
		S_IFSOCK =  0140000, //   socket
		S_IFLNK  =  0120000, //   symbolic link
		S_IFREG  =  0100000, //   regular file
		S_IFBLK  =  0060000, //   block device
		S_IFDIR  =  0040000, //   directory
		S_IFCHR  =  0020000, //   character device
		S_IFIFO  =  0010000, //   FIFO
		USER_PERMS = 07777;  //   bit masks for user's perms bits

	
	/**
	 * Writes a node of the local file system to the TAR archive.
	 * All the properties of the node (owner, permissions, type, device) are read
	 * from the node itself.
	 * Note that by writing a directory, only the bare empty directory entry is
	 * added to the archive, not its content. See also the {@link self::writeRecursive()}
	 * method.
	 * @param File $node Filename of the node to add.
	 * @param string $stored_filename Filename of the node in the TAR archive.
	 * About the character encoding of this string, see the general description of
	 * this class.
	 * For maximum compatibility with older tar programs, a directory name should
	 * always terminate with a forward slash character '/'.
	 * @param mixed[] $params Custom parameters (see general description of this
	 * class for more).
	 * @return void
	 * @throws InvalidArgumentException Supplied custom parameter does not exist
	 * or its value is not valid.
	 * @throws IOException Failed to access the specified node on the system.
	 * File larger that 2 GB on a 32-bits PHP system. Failed to write the TAR.
	 */
	public function writeNode($node, $stored_filename, $params = array()) {
		// Gather file info:
		try {
			// FIXME: lstat() caches the values; do clearstatcache()?
			$info = lstat($node->getLocaleEncoded());
		}
		catch(ErrorException $e){
			throw new IOException($e->getMessage());
		}
		$uid = $info[4]; // Windows: always 0
		$gid = $info[5]; // Windows: always 0
		$mode = $info['mode'];
		$perms = $mode & self::USER_PERMS;
		$type_bits = $mode & self::S_IFMT;
		if( $type_bits == self::S_IFREG )
			$type = TarHeader::TYPE_NORMAL_FILE;
		else if( $type_bits == self::S_IFDIR )
			$type = TarHeader::TYPE_DIRECTORY;
		else if( $type_bits == self::S_IFLNK )
			$type = TarHeader::TYPE_SYMBOLIC_LINK;
		else if( $type_bits == self::S_IFBLK )
			$type = TarHeader::TYPE_BLOCK_SPECIAL;
		else if( $type_bits == self::S_IFCHR )
			$type = TarHeader::TYPE_CHARACTER_SPECIAL;
		else if( $type_bits == self::S_IFIFO )
			$type = TarHeader::TYPE_FIFO;
		else if( $type_bits == self::S_IFSOCK )
			return;
		else
			throw new \RuntimeException("unexpected node type $type_bits for $node");
		
		// $nlink = $info['nlink'];
		$mtime = $info['mtime'];
		
		// C provides the major() and minor() macros to retrieve minor and
		// major number, but these are not available under PHP. This seems
		// to work:
		$devmajor = $info['rdev'] >> 8;
		$devminor = $info['rdev'] & 255;
		
		// FIXME: for files > 2GB on Win, the number is trunked to 32 bits
		// bringing to a quite random result (see comment to the fstat man page).
		// The best I can do is this:
		$size = $info['size'];
		if( ! is_int($size) || $size < 0 )
			throw new \RuntimeException("unexpected size for file $node: $size");
		
		if (function_exists('posix_getpwuid')) {
			$userinfo = posix_getpwuid($uid);
			$groupinfo = posix_getgrgid($gid);
			$uname = (string) $userinfo['name'];
			$gname = (string) $groupinfo['name'];
		} else {
			$uname = '';
			$gname = '';
		}
		
		// Override with client's params (with some exceptions):
//		if ( ! isset($params['type']) )
			$params['type'] = $type;
		
		if ( ! isset($params['perms']) )
			$params['perms'] = $mode & self::USER_PERMS;
		
		if ( ! isset($params['uid']) )
			$params['uid'] = $uid;
		
		if ( ! isset($params['gid']) )
			$params['gid'] = $gid;
		
		if ( ! isset($params['uid']) )
			$params['uid'] = $uid;
		
		if ( ! isset($params['perms']) )
			$params['perms'] = $perms;
		
		if ( ! isset($params['mtime']) )
			$params['mtime'] = $mtime;
		
		if ( ! isset($params['devmajor']) )
			$params['devmajor'] = $devmajor;
		
		if ( ! isset($params['devminor']) )
			$params['devminor'] = $devminor;
		
		if ( ! isset($params['uname']) )
			$params['uname'] = $uname;
		
		if ( ! isset($params['gname']) )
			$params['gname'] = $gname;
		
		if( $params['type'] !== TarHeader::TYPE_NORMAL_FILE )
			$size = 0;
		
		if( $params['type'] === TarHeader::TYPE_SYMBOLIC_LINK ) {
			if( ! isset($params['link']) )
				$params['link'] = readlink($node->getLocaleEncoded());
		} else {
			$params['link'] = "";
		}
			
		// Write header:
		$this->writeHeader($stored_filename, $size, $params);
		
		// Write content:
		if ( $size > 0 ) {
			try {
				$file = fopen($node->getLocaleEncoded(), "rb");
				$read_size = 0;
				do {
					$buffer = fread($file, 512);
					$buffer_len = strlen($buffer);
					$read_size += $buffer_len;
					if( $read_size > $size )
						throw new IOException("$node: file length grew while reading - including myself in TAR?");
					if( $buffer_len == 0 ){
						// EOF - file size multiple of 512 bytes
						break;
					} else if( $buffer_len < 512 ){
						// EOF - write trailing bytes + padding
						$block = pack("a512", $buffer);
						$this->writeBlock($block);
						break;
					} else {
						$this->writeBlock($buffer);
					}
				} while(TRUE);
				if( $read_size < $size )
					throw new IOException("$node: file shortened while reading");
				fclose($file);
			}
			catch(ErrorException $e){
				throw new IOException($e->getMessage());
			}
		}
	}


	/**
	 * Recursively adds a node of the file system to the archive. The path of the
	 * node as written in the archive is the prefix joined with the base name of
	 * the node.
	 * @param string $prefix Optional prefix to add to the base name of the node.
	 * About the character encoding of this string, see the general description of
	 * this class. So, if the prefix is "MyPrefix" and the node represents a file
	 * with path "/home/myname/MyFile", then an entry with filename
	 * "MyPrefixMyFile" will be added to the TAR. Normally the prefix will be
	 * either empty or a string terminated by the *nix directory separator character
	 * '/'.
	 * @param File $node Node to add. Directories are explored recursively, adding
	 * any node they contain.
	 * @param mixed[] $params Custom parameters. This same set of parameters is
	 * used recursivery for any sub-node (see general description of this
	 * class for more).
	 * @return void
	 * @throws InvalidArgumentException Supplied custom parameter does not exist.
	 * @throws IOException Failed to access the specified node on the system or
	 * one of its sub-nodes if directory. Failed to write the TAR.
	 */
	function writeRecursive($prefix, $node, $params = array()) {
		$filename = $prefix . $node->getName()->toUTF8();
		if($node->isDirectory()){
			$filename .= "/";
			$this->writeNode($node, $filename, $params);
			$subnodes = $node->listFiles();
			$subnodes = cast(File::class."[int]", Arrays::sort($subnodes));
			foreach($subnodes as $subnode){
				$this->writeRecursive($filename, $subnode);
			}
		} else {
			$this->writeNode($node, $filename);
		}
	}
	
	
	/**
	 * Appends the closing blocks to the TAR and closes the output stream.
	 * If already closed, does nothing.
	 * @return void
	 * @throws IOException Failed writing the output stream.
	 */
	public function close() {
		if( $this->os === NULL )
			return;
		$block = pack('a512', '');
		$this->writeBlock($block);
		$this->writeBlock($block);
		$this->os->close();
		$this->os = NULL;
	}
	
}
