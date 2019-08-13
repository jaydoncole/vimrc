<?php
namespace it\icosaedro\io\texts;

require_once __DIR__ . "/../../../../all.php";

use it\icosaedro\io\InputStream;
use it\icosaedro\io\IOException;
use it\icosaedro\utils\UString;
use it\icosaedro\utils\Strings;
use it\icosaedro\io\texts\EncodingInterface;


/**
 * Service class for the Reader class; should not be used in applications.
 * Generic reader for the Reader class that handles encodings with variable
 * number of bytes per char but where \n or \r\n still marks the end of line.
 * In this case a whole line is read and decoded safely without the risk to
 * break sequences. This is the case, for example, of the UTF-8 encoding.
 * 
 * @author Umberto Salsi <salsi@icosaedro.it>
 * @version $Date: 2015/03/11 06:49:51 $
 */
class ReaderByLines implements ReaderFilterInterface {
	
	/**
	 * @var InputStream
	 */
	private $in;
	
	/**
	 * @var EncodingInterface
	 */
	private $encoding;
	
	/**
	 * Still undecoded bytes. Once a full line has been detected, it gets
	 * decoded and saved in $line. readLine() does it all, the other methods
	 * simply take from $line.
	 * @var string
	 */
	private $buf;
	
	/**
	 * Decoded line, possibly including EOL.
	 * @var UString
	 */
	private $line;
	
	
	function __toString()
	{
		return $this->encoding->__toString();
	}
	
	
	function getEncoding() {
		return $this->encoding;
	}
	
	
	/**
	 * @param InputStream $in
	 * @param string $leading_bytes Leading bytes from the file.
	 * @param EncodingInterface $encoding
	 */
	function __construct($in, $leading_bytes, $encoding)
	{
		$this->in = $in;
		$this->buf = $leading_bytes;
		$this->encoding = $encoding;
	}
	
	
	/**
	 * @return UString Line read or NULL on end of file.
	 * @throws IOException
	 */
	public function readLine()
	{
		while($this->line === NULL) {
			$nl = strpos($this->buf, "\n");
			if( $nl === FALSE ){
				$n = (int) min(max(strlen($this->buf), 100), 10000);
				$more = $this->in->readBytes($n);
				if( $more === NULL ){
					if( strlen($this->buf) == 0 )
						return NULL;
					$this->line = $this->encoding->decode($this->buf);
					$this->buf = "";
					break;
				} else if( strlen($more) == 0 ){
					continue;
				}
				$this->buf .= $more;
			} else {
				$raw_line = Strings::substring($this->buf, 0, $nl + 1);
				$this->buf = Strings::substring($this->buf, $nl + 1, strlen($this->buf));
				$this->line = $this->encoding->decode($raw_line);
				break;
			}
		}
		$line = $this->line;
		$this->line = NULL;
		// remove EOL:
		$l = $line->length();
		if( $l > 0 && $line->codepointAt($l - 1) == 10 ){
			if( $l >= 2 and $line->codepointAt($l - 2) == 13 )
				$line = $line->substring(0, $l - 2);
			else
				$line = $line->substring(0, $l - 1);
		}
		return $line;
	}
	
	
	/**
	 * @return int
	 * @throws IOException
	 */
	function readCodepoint()
	{
		if( $this->line === NULL )
			$this->line = $this->readLine();
		if( $this->line === NULL )
			return -1;
		$cp = $this->line->codepointAt(0);
		if( $this->line->length() == 1 )
			$this->line = NULL;
		else
			$this->line = $this->line->remove(0, 1);
		return $cp;
	}
	
	
	/**
	 * @param int $n
	 * @return UString
	 * @throws IOException
	 */
	function read($n)
	{
		if( $n <= 0 )
			return UString::fromASCII("");
		// read chars from currently available line
		if( $this->line === NULL )
			$this->line = $this->readLine();
		if( $this->line === NULL )
			return NULL;
		if( $n >= $this->line->length() ){
			$res = $this->line;
			$this->line = NULL;
		} else {
			$res = $this->line->substring(0, $n);
			$this->line = $this->line->remove(0, $n);
		}
		return $res;
	}
	
	
	/**
	 * Implements {@link it\icosaedro\io\texts\ReaderFilterInterface::close()}.
	 * @return void
	 * @throws IOException
	 */
	function close()
	{
		if( $this->in !== NULL ){
			$this->in->close();
			$this->in = NULL;
		}
	}
}