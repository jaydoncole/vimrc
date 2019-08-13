<?php
/**
Bzip2 Compression Functions.

See: {@link http://www.php.net/manual/en/ref.bzip2.php}
@package bz2
*/

/**
 * Check for BZIP2 encoding or decoding errors.
 * @return int Error code to be used with bzerrstr() to retrieve the text.
 * Zero on success.
 * @param resource $bz
 */
function bzerrno($bz){}

/**
 * Returns the error message about the last operation on stream opened with bzopen().
 * @param resource $bz Stream opened with bzopen().
 * @return string The possible values are:
 * "OK": no error;
 * "DATA_ERROR_MAGIC": not a BZIP2 file;
 * "DATA_ERROR": corrupted data;
 * "UNEXPECTED_EOF": premature end of the data.
 * Any other value is unexpected and should be regarded as an internal bug.
 */
function bzerrstr($bz){}

/*. array    .*/ function bzerror(/*. resource .*/ $bz){}
/*. mixed    .*/ function bzcompress(/*. string .*/ $source, $blocksize = 4, $workfactor = 0){}

/**
 * WARNING. Looking at the C source code, on error this function may return
 * several types of results and values, including: FALSE, int.
 */
/*. mixed    .*/ function bzdecompress(/*. string .*/ $source, $small = FALSE){}


/**
 * Opens a BZIP2 compressed file (.bz2 extension). If this function succeeds,
 * you must also check bzerrno() for possible other BZIP2 specific errors,
 * especially when reading existing file; this may happen, for example, attempting
 * to read a file which is not really BZIP2 encoded or is corrupted.
 * @param string $filename
 * @param string $mode Can be "r" for reading or "w" for writing.
 * @return resource Resource that can be used later with the other functions of
 * this module. May also return FALSE if it fails to open or create the file, in
 * which case an error of level E_WARNING is triggered.
 * @triggers E_WARNING Failed to open or create the file. Invalid mode.
 */
function bzopen($filename, $mode){}

/**
 * Read and decode data. If this function succeeds, you must also check bzerrno()
 * for possible other BZIP2 specific errors; this may happen, for example, attempting
 * to read a file which is not really BZIP2 encoded or is corrupted.
 * @param resource $bz BZIP2 resource created with bzopen().
 * @param int $length If not specified, will read 1024 (uncompressed) bytes at a
 * time. A maximum of 8192 uncompressed bytes will be read at a time. 
 * @return string Decoded data.
 * Possible results are:
 * <ul>
 * <li>Empty string: end of the file or BZIP2 deconding error. Use feof($bz) to
 * detect the first case, and use bzerrno($bz) != 0 to detect the latter.</li>
 * <li>Non empty string: decoded data. Check bzerror($bz) != 0 for possible BZIP2
 * decoding errors. Data may still be garbage because the BZIP2 checksum is checked
 * only after a whole block of BZIP2 data has been read or at the end of the stream,
 * so in general data read cannot be trusted until the whole content of the file
 * has been read.</li>
 * <li>FALSE: error reading from the stream. An error of level E_WARNING is also
 * raised in this case.</li>
 * </ul>
 * @triggers E_WARNING The parameter $bz is not a valid resource. The parameter
 * $length is not positive. Failed reading compressed data from the source.
 */
function bzread($bz, $length = 1024){}

/*. int      .*/ function bzwrite(/*. resource .*/ $bz, /*. string .*/ $data, $length = -1)/*. triggers E_WARNING .*/{}
/*. boolean  .*/ function bzflush(/*. resource .*/ $bz)/*. triggers E_WARNING .*/{}
/*. int      .*/ function bzclose(/*. resource .*/ $bz)/*. triggers E_WARNING .*/{}
