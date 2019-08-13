<?php

namespace it\icosaedro\io\tar;

require_once __DIR__ . "/../../../../all.php";

use it\icosaedro\containers\Printable;
use it\icosaedro\containers\Sortable;
use it\icosaedro\utils\Strings;
use CastException;

/**
 * Header read from a TAR archive. Some fields are set only for
 * entries that use the extended "UStar" format, as detailed in the comments below.
 * For more details see {@link https://en.wikipedia.org/wiki/Tar_%28computing%29}.
 * 
 * @author Umberto Salsi <salsi@icosaedro.it>
 * @version $Date: 2016/01/08 18:29:56 $
 */
class TarHeader implements Printable, Sortable {
	
	/**
	 * Normal file.
	 */
	const TYPE_NORMAL_FILE = '0';
	/**
	 * Hard link. The $link field should contain the name of the first file or
	 * directory this hard link refers to.
	 */
	const TYPE_HARD_LINK = '1';
	/**
	 * Symbolic link. The $link field should contain the referred target.
	 */
	const TYPE_SYMBOLIC_LINK = '2';
	/**
	 * Character special.
	 */
	const TYPE_CHARACTER_SPECIAL = '3';
	/**
	 * Block special.
	 */
	const TYPE_BLOCK_SPECIAL = '4';
	/**
	 * Directory.
	 */
	const TYPE_DIRECTORY = '5';
	/**
	 * FIFO.
	 */
	const TYPE_FIFO = '6';
	/**
	 * Contiguous file. Reserved in POSIX standard and not supported by GNU either;
	 * mostly to be assumed just as a normal file.
	 */
	const TYPE_CONTIGUOUS_FILE = '7';
	/**
	 * Global extender header with meta data (POSIX.1-2001).
	 */
	const TYPE_GLOBAL_EXTENDER_HEADER = 'g';
	/**
	 * Extended header with meta data for the next file in the archive (POSIX.1-2001).
	 */
	const TYPE_EXTENDED_HEADER = 'x';
	
	/**
	 * Name of the file. Normally it is a path relative to some extraction directory
	 * where the contents of this archive will be extracted later. The directory
	 * separator character is the forward slash '/'. Any byte is valid with the
	 * only exception of the NUL byte, that terminates the string. TAR specifications
	 * allows ASCII encoding only, as there is no other way to detect the actual
	 * encoding used; though, on many *nix systems nowaday UTF-8 is the system encoding
	 * used.
	 * @var string
	 */
	public $filename = '';
	/**
	 * Unix file access permission flags. Normally only the 9 least significant
	 * bits are used for read, write and execution permission of the user, the
	 * group and for others respectively. Range: 0 - 07777777.
	 * @var int
	 */
	public $mode = 0;
	/**
	 * User ID number. Normally set to 0 which stands for the "root" user.
	 * Since any other value has no match on other systems, readers may assume
	 * the current user instead. Range: 0 - 07777777.
	 * @var int
	 */
	public $uid = 0;
	/**
	 * Group ID number. Normally set to 0 which stands for the "root" group.
	 * Since any other value has no match on other systems, readers may assume
	 * the current group instead. Range: 0 - 07777777.
	 * @var int
	 */
	public $gid = 0;
	/**
	 * Size of the file (B). This number matches the size of the content that
	 * follows this header. The maximum value allowed is 8 GB (minus one).
	 * On 32-bits PHP it is limited to 2 GB (minus one) and an exception is thrown
	 * if a bigger value is read. Range: 0 - 077777777777.
	 * @var int
	 */
	public $size = 0;
	/**
	 * Time of last modification of the file (Unix timestamp). Range: 0 - 077777777777.
	 * @var int
	 */
	public $mtime = 0;
	
	/**
	 * Type of this entry. One of the TYPE_* constants, or 'A'–'Z' for vendor
	 * specific extensions (POSIX.1-1988). 1 byte.
	 * @var string
	 */
	public $type = '';
	/**
	 * Name of the linked or hard-linked file. Normally a relative path. About
	 * encoding, see the discussion about the filename field. 99 characters max.
	 * @var string
	 */
	public $link = '';
	
	/**
	 * If this entry belongs to an extender, UStar TAR header. Some fields (UStarVersion,
	 * uname, gname, devminor and devmajor) are set only if this flag is TRUE.
	 * @var boolean
	 */
	public $isUStar = FALSE;
	
	/**
	 * Version of the UStar record, normally "00". This field is not-NULL only
	 * for UStar entries. 2 bytes.
	 * @var string
	 */
	public $UStarVersion;
	
	/**
	 * User name. This field is not empty only for UStar entries. Max 31 characters
	 * of unspecified encoding (normally ASCII).
	 * @var string
	 */
	public $uname;
	
	/**
	 * Group name. This field is not empty only for UStar entries. Max 31 characters
	 * of unspecified encoding (normally ASCII).
	 * @var string
	 */
	public $gname;
	
	/**
	 * Device major number. This field is not-NULL only for UStar entries and
	 * is 0 otherwise. Range: 0 - 07777777.
	 * @var int
	 */
	public $devmajor = 0;
	
	/**
	 * Device minor number. This field is not-NULL only for UStar entries and
	 * is 0 otherwise. Range: 0 - 07777777.
	 * @var int
	 */
	public $devminor = 0;
	
	
	/**
	 * Returns a textual representation of this object mostly useful for debugging.
	 * @return string
	 */
	function __toString() {
		$s = sprintf("filename=%s, mode=0%03o, uid=%d, gid=%d, size=%d, mtime=%s, type=%s, link=%s",
			Strings::toLiteral($this->filename), $this->mode, $this->uid, $this->gid,
			$this->size, gmdate("c", $this->mtime), $this->type,
			Strings::toLiteral($this->link));
		if( $this->isUStar )
			$s .= sprintf(", UStar version=%s, uname=%s, gname=%s, devmajor=%d, devminor=%d",
				Strings::toLiteral($this->UStarVersion),
				Strings::toLiteral($this->uname),
				Strings::toLiteral($this->gname),
				$this->devmajor, $this->devminor);
		return $s;
	}
	
	
	/**
	 * Two entries are equals if their file names matches exactly.
	 * @param object $other
	 * @return boolean
	 */
	function equals($other) {
		// FIXME: not normalized paths may refer to the same file, ex. "./x" and "x".
		// FIXME: on Windows, file names are case-insensitive.
		if( $other === NULL )
			return FALSE;
		if( $this === $other )
			return TRUE;
		if( get_class($other) !== __CLASS__ )
			return FALSE;
		$other2 = cast(__CLASS__, $other);
		return $this->filename === $other2->filename;
	}
	
	
	/**
	 * Compares only the filename fields using {@link strcmp()}.
	 * @param object $other Another TarHeader object.
	 * @return int
	 */
	function compareTo($other) {
		if( $other === NULL )
			throw new CastException("NULL");
		if( get_class($other) !== __CLASS__ )
			throw new CastException("expected " . __CLASS__
			. " but got " . get_class($other));
		$other2 = cast(__CLASS__, $other);
		return strcmp($this->filename, $other2->filename);
	}
	
}
