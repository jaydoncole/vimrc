<?php

/*. require_module 'spl'; .*/

class PharException extends Exception {}


/*. forward class PharData {} .*/

class Phar extends RecursiveDirectoryIterator implements Countable, ArrayAccess {

	const
		BZ2 = 8192,
		COMPRESSED = 61440,
		CURRENT_AS_FILEINFO = 0,
		CURRENT_AS_PATHNAME = 32,
		CURRENT_AS_SELF = 16,
		CURRENT_MODE_MASK = 240,
		FOLLOW_SYMLINKS = 512,
		GZ = 4096,
		KEY_AS_FILENAME = 256,
		KEY_AS_PATHNAME = 0,
		KEY_MODE_MASK = 3840,
		MD5 = 1,
		NEW_CURRENT_AND_KEY = 256,
		NONE = 0,
		OPENSSL = 16,
		OTHER_MODE_MASK = 12288,
		PHAR = 1,
		PHP = 0,
		PHPS = 1,
		SHA1 = 2,
		SHA256 = 3,
		SHA512 = 4,
		SKIP_DOTS = 4096,
		TAR = 2,
		UNIX_PATHS = 8192,
		ZIP = 3;

	/*. void .*/ function __construct(/*. string .*/ $fname, $flags = 0, /*. string .*/ $alias = NULL)
		/*. throws BadMethodCallException, UnexpectedValueException .*/
	{
		parent::__construct($fname);
	}
	/*. void .*/ function addEmptyDir(/*. string .*/ $dirname){}
	/*. void .*/ function addFile(/*. string .*/ $file, /*. string .*/ $localname = NULL)
		/*. throws Exception .*/{}
	/*. void .*/ function addFromString(/*. string .*/ $localname, /*. string .*/ $contents)
		/*. throws Exception .*/{}
	static /*. string .*/ function apiVersion(){}
	/*. string[string] .*/ function buildFromDirectory(/*. string .*/ $base_dir, /*. string .*/ $regex = NULL)
		/*. throws BadMethodCallException, PharException .*/{}
	/*. string[string] .*/ function buildFromIterator(Iterator $iter, /*. string .*/ $base_directory = NULL)
		/*. throws UnexpectedValueException, BadMethodCallException, PharException .*/ {}
	static /*. bool .*/ function canCompress($type = 0){}
	static /*. bool .*/ function canWrite(){}
	/*. Phar .*/ function compress(/*. int .*/ $compression, /*. string .*/ $extension = NULL)
		/*. throws BadMethodCallException .*/ {}
	/*. bool .*/ function compressAllFilesBZIP2()
		/*. throws BadMethodCallException .*/ {}
	/*. bool .*/ function compressAllFilesGZ()
		/*. throws BadMethodCallException .*/ {}
	/*. void .*/ function compressFiles(/*. int .*/ $compression)
		/*. throws BadMethodCallException .*/ {}
	/*. PharData .*/ function convertToData($format = 9021976, $compression = 9021976, /*. string .*/ $extension = NULL)
		/*. throws BadMethodCallException, PharException .*/ {}
	/*. Phar .*/ function convertToExecutable($format = 9021976, $compression = 9021976, /*. string .*/ $extension = NULL)
		/*. throws BadMethodCallException, UnexpectedValueException, PharException .*/ {}
	/*. bool .*/ function copy(/*. string .*/ $oldfile, /*. string .*/ $newfile)
		/*. throws BadMethodCallException, PharException .*/ {}
	/*. int .*/ function count(){}
	static /*. string .*/ function createDefaultStub(/*. string .*/ $indexfile = NULL, /*. string .*/ $webindexfile = NULL)
		/*. throws UnexpectedValueException .*/ {}
	/*. Phar .*/ function decompress(/*. string .*/ $extension = NULL)
		/*. throws BadMethodCallException .*/ {}
	/*. bool .*/ function decompressFiles()
		/*. throws BadMethodCallException .*/ {}
	/*. bool .*/ function delMetadata()
		/*. throws PharException .*/ {}
	/*. bool .*/ function delete(/*. string .*/ $entry)
		/*. throws PharException .*/ {}
	/*. bool .*/ function extractTo(/*. string .*/ $pathto, /*. mixed .*/ $files_or_dir = NULL, $overwrite = false)
		/*. throws PharException .*/ {}
	/*. mixed .*/ function getMetadata(){}
	/*. bool .*/ function getModified(){}
	/*. string[string] .*/ function getSignature(){}
	/*. string .*/ function getStub()
		/*. throws RuntimeException .*/ {}
	static /*. string[int] .*/ function getSupportedCompression(){}
	static /*. string[int] .*/ function getSupportedSignatures(){}
	/*. string .*/ function getVersion(){}
	/*. bool .*/ function hasMetadata(){}
	/*. void .*/ function interceptFileFuncs(){}
	/*. bool .*/ function isBuffering(){}
	/*. mixed .*/ function isCompressed(){}
	/*. bool .*/ function isFileFormat(/*. int .*/ $format)
		/*. throws PharException .*/ {}
	static /*. bool .*/ function isValidPharFilename(/*. string .*/ $filename, $executable = true){}
	/*. bool .*/ function isWritable(){}
	static /*. bool .*/ function loadPhar(/*. string .*/ $filename, /*. string .*/ $alias = NULL)
		/*. throws PharException .*/ {}
	static /*. bool .*/ function mapPhar(/*. string .*/ $alias = NULL, $dataoffset = 0)
		/*. throws PharException .*/ {}
	static /*. void .*/ function mount(/*. string .*/ $pharpath, /*. string .*/ $externalpath)
		/*. throws PharException .*/ {}
	static /*. void .*/ function mungServer(array $munglist)
		/*. throws UnexpectedValueException .*/ {}
	/** Note that the parameter must be mixed for compatibility with the implemented method,
	 * although this method actually expects string. */
	/*. bool .*/ function offsetExists(/*. mixed .*/ $offset){}
	/** Note that the first parameter must be mixed for compatibility with the implemented method,
	 * although this method actually expects string. */
	/*. int  .*/ function offsetGet(/*. mixed .*/ $offset){}
	/** Note that the parameters must be mixed for compatibility with the implemented method,
	 * although this method actually expects string. */
	/*. void .*/ function offsetSet(/*. mixed .*/ $offset, /*. mixed .*/ $value)
		/*. throws BadMethodCallException .*/ {}
	/** Note that the parameter must be mixed for compatibility with the implemented method,
	 * although this method actually expects string. */
	/*. void .*/ function offsetUnset(/*. mixed .*/ $offset)
		/*. throws BadMethodCallException .*/ {}
	static /*. string .*/ function running($retphar = true){}
	/*. bool .*/ function setAlias(/*. string .*/ $alias)
		/*. throws UnexpectedValueException .*/ {}
	/*. bool .*/ function setDefaultStub(/*. string .*/ $index = NULL, /*. string .*/ $webindex = NULL)
		/*. throws UnexpectedValueException, PharException .*/ {}
	/*. void .*/ function setMetadata(/*. mixed .*/ $metadata){}
	/*. void .*/ function setSignatureAlgorithm(/*. int .*/ $sigtype, /*. string .*/ $privatekey = NULL)
		/*. throws UnexpectedValueException .*/ {}
	/*. bool .*/ function setStub(/*. string .*/ $stub, $len = -1)
		/*. throws UnexpectedValueException, PharException .*/ {}
	/*. void .*/ function startBuffering(){}
	/*. void .*/ function stopBuffering()
		/*. throws PharException .*/ {}
	/*. bool .*/ function uncompressAllFiles()
		/*. throws BadMethodCallException .*/ {}
	static /*. bool .*/ function unlinkArchive(/*. string .*/ $archive)
		/*. throws PharException .*/ {}
	static /*. void .*/ function webPhar(/*. string .*/ $alias = NULL, $index = "index.php", /*. string .*/ $f404 = NULL, $mimetypes = /*. (string[string]) .*/ array(), $rewrites = /*. (string[string]) .*/ array())
		/*. throws PharException, UnexpectedValueException .*/ {}
	/*. void .*/ function __destruct(){}

	/** @deprecated Not documented in the manual. */
	public /*. mixed .*/ function getExtension(/*. args .*/){}
}


class PharData extends Phar {
	/*. void .*/ function __construct(/*. string .*/ $fname, $flags = 0, /*. string .*/ $alias = NULL, $format = Phar::TAR){
		parent::__construct($fname);
	}
}


class PharFileInfo extends SplFileInfo {
	/*. void .*/ function __construct(/*. string .*/ $entry)
		/*. throws BadMethodCallException, UnexpectedValueException .*/
	{
		parent::__construct($entry);
	}
	/*. void .*/ function chmod(/*. int .*/ $permissions){}
	/*. bool .*/ function compress(/*. int .*/ $compression){}
	/*. bool .*/ function decompress()
		/*. throws BadMethodCallException .*/ {}
	/*. bool .*/ function delMetadata()
		/*. throws BadMethodCallException, PharException .*/ {}
	/*. int .*/ function getCRC32()
		/*. throws BadMethodCallException .*/ {}
	/*. int .*/ function getCompressedSize(){}
	/*. mixed .*/ function getMetadata(){}
	/*. int .*/ function getPharFlags(){}
	/*. bool .*/ function hasMetadata(){}
	/*. bool .*/ function isCRCChecked(){}
	/*. bool .*/ function isCompressed($compression_type = 9021976){}
	/*. bool .*/ function isCompressedBZIP2(){}
	/*. bool .*/ function isCompressedGZ(){}
	/*. bool .*/ function setCompressedBZIP2()
		/*. throws BadMethodCallException .*/ {}
	/*. bool .*/ function setCompressedGZ()
		/*. throws BadMethodCallException .*/ {}
	/*. void .*/ function setMetadata(/*. mixed .*/ $metadata){}
	/*. bool .*/ function setUncompressed()
		/*. throws BadMethodCallException .*/ {}
}
