<?php
/** Zlib Compression Functions.

See: {@link http://www.php.net/manual/en/function.zlib-encode.php}
@package zlib
*/

/*.
	require_module 'file'; # for SEEK_SET
.*/

/*. if_php_ver_7 .*/

/**
 * This flush mode available only if ZLIB_VERNUM &ge; 0x1240.
 */
define("ZLIB_BLOCK", 5);

define("ZLIB_FINISH", 4);
define("ZLIB_FULL_FLUSH", 3);
define("ZLIB_NO_FLUSH", 0);
define("ZLIB_PARTIAL_FLUSH", 1);
define("ZLIB_SYNC_FLUSH", 2);
/*. end_if_php_ver .*/

define('FORCE_GZIP', 31);
define('FORCE_DEFLATE', 15);
define("ZLIB_DEFAULT_STRATEGY", 0);
define("ZLIB_ENCODING_DEFLATE", 15);
define("ZLIB_ENCODING_GZIP", 31);
define("ZLIB_ENCODING_RAW", -15);
define("ZLIB_FILTERED", 1);
define("ZLIB_FIXED", 4);
define("ZLIB_HUFFMAN_ONLY", 2);
define("ZLIB_RLE", 3);
define("ZLIB_VERNUM", 4736);
define("ZLIB_VERSION", '1.2.8');



/*. bool  .*/ function gzclose(/*.resource.*/ $zp)
	/*. triggers E_WARNING .*/ {}
	
/*. string.*/ function gzcompress(/*. string .*/ $data, $level = -1, $encoding = ZLIB_ENCODING_DEFLATE)
	/*. triggers E_WARNING .*/ {}
	
/*. string.*/ function gzdeflate(/*. string .*/ $data, $level = -1, $encoding = ZLIB_ENCODING_RAW)
	/*. triggers E_WARNING .*/ {}
	
/*. string .*/ function gzdecode(/*. string .*/ $data, $length=-1)
	/*. triggers E_WARNING .*/ {}
	
/*. string.*/ function gzencode(/*. string .*/ $data, $level = -1, $encoding_mode = FORCE_GZIP)
	/*. triggers E_WARNING .*/ {}
	
/*. bool  .*/ function gzeof(/*. resource .*/ $f)
	/*. triggers E_WARNING .*/ {}

/*. array[int]string .*/ function gzfile(/*. string .*/ $filename, $use_include_path = 0)
	/*. triggers E_WARNING .*/ {}
	
/*. mixed.*/ function gzgetc(/*. resource .*/ $h)
	/*. triggers E_WARNING .*/ {}

/*. string.*/ function gzgets(/*. resource .*/ $f, /*. int .*/ $length)
	/*. triggers E_WARNING .*/ {}

/*. string .*/ function gzgetss(/*. resource .*/ $zp , /*. int .*/ $length, /*. string .*/ $allowable_tags = NULL)
	/*. triggers E_WARNING .*/ {}

/*. string.*/ function gzinflate(/*. string .*/ $data, $length = 0)
	/*. triggers E_WARNING .*/ {}

/*. resource .*/ function gzopen(/*. string .*/ $filename, /*. string .*/ $mode, $use_include_path = 0)
	/*. triggers E_WARNING .*/ {}

/*. int .*/ function gzpassthru(/*. resource .*/ $zp)
	/*. triggers E_WARNING .*/ {}

/*. int .*/ function gzputs(/*. resource .*/ $zp, /*. string .*/ $string_, $length=-1)
	/*. triggers E_WARNING .*/ {}
	
/*. string .*/ function gzread(/*. resource .*/ $zp, /*. int .*/ $length)
	/*. triggers E_WARNING .*/ {}

/*. bool .*/ function gzrewind(/*. resource .*/ $zp )
	/*. triggers E_WARNING .*/ {}

/*. int .*/ function gzseek(/*. resource .*/ $zp, /*. int .*/ $offset, $whence = SEEK_SET)
	/*. triggers E_WARNING .*/ {}

/*. int .*/ function gztell(/*. resource .*/ $zp)
	/*. triggers E_WARNING .*/ {}

/*. string.*/ function gzuncompress(/*. string .*/ $data, $length = 0)
	/*. triggers E_WARNING .*/ {}

/*. int .*/ function gzwrite(/*. resource .*/ $zp, /*. string .*/ $string_, $length=-1)
	/*. triggers E_WARNING .*/ {}

/*. int   .*/ function readgzfile(/*. string .*/ $filename, $use_include_path = 0)
	/*. triggers E_WARNING .*/ {}
	
/*. string .*/ function zlib_decode(/*. string .*/ $data, $max_decoded_len=-1)
	/*. triggers E_WARNING .*/ {}

/*. string .*/ function zlib_encode(/*. string .*/ $data, /*. string .*/ $encoding, $level = -1)
	/*. triggers E_WARNING .*/ {}
	
/*. mixed.*/ function zlib_get_coding_type(){}

/*. if_php_ver_7 .*/

	/**
	 * Initialize an incremental deflate context using the specified encoding.
	 * Note that the 'window' option here only sets the window size of the algorithm,
	 * differently from the zlib filters where the same parameter also set the
	 * encoding to use; the encoding must be set with the $encoding parameter.
	 * Limitation: there is no currently way to set the header informations on a
	 * GZIP compressed stream, which are set as follows: GZIP signature (“\x1f\x8B”);
	 * compression method (“\x08” == DEFLATE); 6 zero bytes; the operating system
	 * set to the current system (“\x00” = Windows, “\x03” = Unix, etc.).
	 * @param int $encoding One of:
	 * ZLIB_ENCODING_RAW (DEFLATE algorithm as per RFC 1951),
	 * ZLIB_ENCODING_DEFLATE (ZLIB compress algorithm as per RFC 1950) or
	 * ZLIB_ENCODING_GZIP (GZIP algorithm as per RFC 1952).
	 * Note the unfortunate, misleading names of these constants.
	 * @param int[string] $options Allowed entries are:<br>
	 * 'level': compression level, range -1..9, default -1;<br>
	 * 'memory': compression memory level, range 1..9, default 8;<br>
	 * 'window': zlib window size (logarithm), range 8..15, default 15;<br>
	 * 'strategy': one of ZLIB_FILTERED, ZLIB_HUFFMAN_ONLY, ZLIB_RLE, ZLIB_FIXED
	 * or ZLIB_DEFAULT_STRATEGY (default);
	 * 'dictionary': a string or an array of strings of the preset dictionary
	 * (default: no preset dictionary).
	 * @return resource Deflate context, or FALSE on error.
	 * @triggers E_WARNING Invalid option. Failed to create the context.
	 */
	function deflate_init($encoding, $options = array()){}
	
	
	/**
	 * Incrementally deflate data in the specified context.
	 * @param resource $context Context created with deflate_init().
	 * @param string $data Chunk of data to compress.
	 * @param int $flush_mode One of: ZLIB_BLOCK, ZLIB_NO_FLUSH, ZLIB_PARTIAL_FLUSH,
	 * ZLIB_SYNC_FLUSH (default), ZLIB_FULL_FLUSH, ZLIB_FINISH. Normally you will
	 * want to set ZLIB_NO_FLUSH here to maximize compression, and ZLIB_FINISH to
	 * terminate with the last chunk of data. See the ZLIB manual at
	 * {@link http://www.zlib.net/manual.html} for a detailed description of
	 * these constants.
	 * @return string Chunk of dompressed data, or FALSE on error.
	 * @triggers E_WARNING Invalid parameters.
	 */
	function deflate_add($context, $data, $flush_mode = ZLIB_SYNC_FLUSH){}
	
	
	/**
	 * Initialize an incremental inflate context with the specified encoding.
	 * @param int $encoding One of:
	 * ZLIB_ENCODING_RAW (DEFLATE algorithm as per RFC 1951),
	 * ZLIB_ENCODING_DEFLATE (ZLIB compress algorithm as per RFC 1950) or
	 * ZLIB_ENCODING_GZIP (GZIP algorithm as per RFC 1952).
	 * Note the unfortunate, misleading names of these constants.
	 * @param int[string] $options Allowed entries are:<br>
	 * 'level': compression level, range -1..9, default -1;<br>
	 * 'memory': compression memory level, range 1..9, default 8;<br>
	 * 'window': zlib window size (logarithm), range 8..15, default 15;<br>
	 * 'strategy': one of ZLIB_FILTERED, ZLIB_HUFFMAN_ONLY, ZLIB_RLE, ZLIB_FIXED
	 * or ZLIB_DEFAULT_STRATEGY (default);
	 * 'dictionary': a string or an array of strings of the preset dictionary
	 * (default: no preset dictionary).
	 * @return resource Inflate context, or FALSE on error.
	 * @triggers E_WARNING Invalid encoding. Invalid option. Failed to allocate
	 * the context.
	 */
	function inflate_init($encoding, $options = array()){}
	
	/**
	 * Incrementally inflate encoded data in the specified context.
	 * Limitation: header informations from GZIP compressed data are not made
	 * available.
	 * @param resource $context Context created with inflate_init().
	 * @param string $encoded_data Chunk of compressed data.
	 * @param int $flush_mode One of: ZLIB_BLOCK, ZLIB_NO_FLUSH, ZLIB_PARTIAL_FLUSH,
	 * ZLIB_SYNC_FLUSH (default), ZLIB_FULL_FLUSH, ZLIB_FINISH. Normally you will
	 * want to set ZLIB_NO_FLUSH to maximize speed, and ZLIB_FINISH to
	 * terminate with the last block of data. See the ZLIB manual at
	 * {@link http://www.zlib.net/manual.html} for a detailed description of
	 * these constants.
	 * @return string Chunk of decompressed data, or FALSE on error.
	 * @triggers E_WARNING Invalid parameters. Inflating this data requires a preset
	 * dictionary, please specify it in the options array of inflate_init().
	 * Corrupted compressed stream or invalid checksum (whenever available).
	 */
	function inflate_add($context, $encoded_data, $flush_mode = ZLIB_SYNC_FLUSH){}
	
	/*. int .*/ function inflate_get_status(/*. resource .*/ $context)/*. triggers E_WARNING .*/{}
	/*. int .*/ function inflate_get_read_len(/*. resource .*/ $context)/*. triggers E_WARNING .*/{}

/*. end_if_php_ver .*/