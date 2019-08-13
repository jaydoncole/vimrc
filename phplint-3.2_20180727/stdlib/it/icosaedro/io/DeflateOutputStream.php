<?php

namespace it\icosaedro\io;
require_once __DIR__ . "/../../../all.php";

use InvalidArgumentException;
use ErrorException;


/**
 * Output stream filter that compresses using the DEFLATE algorithm (RFC 1951).
 * This compressed format is not used normally alone, but it is the base of
 * several other safer file formats, like ZLIB Compress (RFC 1950) and GZIP
 * (RFC 1952).
 * @author Umberto Salsi <salsi@icosaedro.it>
 * @version $Date: 2016/03/07 10:40:04 $
 */
class DeflateOutputStream extends ResourceOutputStream {

	/**
	 * @var resource
	 */
	private $gz;

	/**
	 * Creates a new DEFLATE compressor stream writer.
	 * @param OutputStream $out Destination of the compressed stream.
	 * @param int $level Compression level ranging from 0 (no compression) up to
	 * 9 (maximum compression level, possibly slower); -1 is the internal
	 * default of the library.
	 * @throws IOException
	 * @throws InvalidArgumentException Invalid level.
	 */
	public function __construct($out, $level = -1)
	{
		if( $level < -1 || $level > 9 )
			throw new InvalidArgumentException("level=$level");
		try {
			$this->gz = OutputStreamAsResource::get($out);
			
			stream_filter_append($this->gz, 'zlib.deflate',
				STREAM_FILTER_WRITE,
				array('level' => $level, 'window' => -15, 'memory' => 9) );
		}
		catch(ErrorException $e){
			throw new IOException($e->getMessage(), 0, $e);
		}
		parent::__construct($this->gz);
	}


	/**
	 * Closes the file. Does nothing if already closed.
	 * Once closed, this object cannot be used anymore.
	 * @return void
	 * @throws IOException
	 */
	public function close()
	{
		if( $this->gz === NULL )
			return;
		try {
			fclose($this->gz);
		}
		catch(ErrorException $e){
			throw new IOException($e->getMessage(), 0, $e);
		}
		$this->gz = NULL;
	}
	

}
