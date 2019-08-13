<?php
/*. require_module 'hash'; .*/

namespace it\icosaedro\io;
require_once __DIR__ . "/../../../all.php";


/**
 * Input stream filter that decompresses using the ZLIB format (RFC 1950).
 * A ZLIB Compress stream contains an header (2 bytes), followed by 1 or more
 * bytes of data compressed with DEFLATE, then an Adler32 checksum of the original
 * data.
 * This final checksum is read and verified only at the end of the stream,
 * when the client code calls the close method; before this point, data returned
 * might be corrupted.
 * @author Umberto Salsi <salsi@icosaedro.it>
 * @version $Date: 2016/03/06 13:22:48 $
 */
class ZLIBCompressInputStream extends DeflateInputStream {
	
	/**
	 * We must keep the last 4 bytes of the input stream with the Adler-32 hash
	 * to be checked later, on close().
	 * @var KeepTailInputStream
	 */
	private $in;
	
	/**
	 * Adler-32 hash context.
	 * @var resource
	 */
	private $adler32;

	/**
	 * Creates a new ZLIB Compressed input stream decompressor. Random streams
	 * of data are detected and causes exception with an extimated rejection
	 * factor better than 99.95%.
	 * @param InputStream $in
	 * @throws IOException
	 * @throws CorruptedException Invalid header, not a ZLIB Compressed stream.
	 */
	public function __construct($in)
	{
		$header = $in->readFully(2);
		$CMF = ord($header[0]);
		$FLG = ord($header[1]);
		if( ($CMF * 256 + $FLG) % 31 != 0 )
			// a random file has 1/31 probability to pass this check
			throw new CorruptedException("bad header");
		$CM = $CMF & 0xf;
		$CINFO = $CMF >> 4;
		if( !( $CM == 8 && $CINFO <= 7) )
			// a random file has 1/32 probability to pass this check
			throw new CorruptedException("CM=$CM, CINFO=$CINFO");
		$FDICT = ($FLG & 0x20) != 0;
		if( $FDICT )
			// a random file has 1/2 probability to pass this check
			throw new CorruptedException("unsupported preset dictionary (FDICT=1)");
		// propability that a random file may pass all the checks above:
		// 1/31 * 1/32 * 1/2 = 1/1984 = 0.05%
//		$LEVEL = $FLG >> 6;  // informative, may ignore
		$this->adler32 = hash_init("adler32");
		$this->in = new KeepTailInputStream($in, 4);
		parent::__construct($this->in);
	}


	/**
	 * Reads one byte.
	 * @return int Byte read in [0,255], or -1 on end of file.
	 * @throws IOException
	 */
	public function readByte()
	{
		$b = parent::readByte();
		if( $b >= 0 )
			hash_update($this->adler32, chr($b));
		return $b;
	}


	/**
	 * Reads bytes.
	 * @param int $n Maximum number of bytes to read.
	 * @return string Bytes read, possibly in a number less than requested,
	 * either because the end of the file has been reached, or the input
	 * buffer is short but still data are available. If $n &le; 0 does nothing
	 * and the empty string is returned. If $n &gt; 0 and the returned string
	 * is NULL, the end of the file is reached.
	 * @throws IOException
	 */
	public function readBytes($n)
	{
		if( $n <= 0 )
			return "";
		$bytes = parent::readBytes($n);
		if( $bytes === NULL )
			return NULL;
		hash_update($this->adler32, $bytes);
		return $bytes;
	}


	/**
	 * Closes the file and checks the final Adler-32 hash.
	 * Does nothing if already closed.
	 * Once closed, this object cannot be used anymore.
	 * @return void
	 * @throws CorruptedException
	 * @throws IOException
	 */
	public function close()
	{
		if( $this->in === NULL )
			return;
		// Skip to the end, just in case the client stopped to read:
		while( $this->readBytes(512) !== NULL ) ;
		$tail = $this->in->getTail();
		if( strlen($tail) != 4 )
			throw new PrematureEndException($tail, "premature end");
		
		// Note that both the hash_final() result and the tail's hash
		// are in big-endian order:
		$adler32 = hash_final($this->adler32, TRUE);
		if( $adler32 !== $tail )
			throw new CorruptedException("invalid hash");
		
		parent::close();
		$this->in = NULL;
	}

}
