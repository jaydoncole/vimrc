<?php
namespace it\icosaedro\io\texts;

require_once __DIR__ . "/../../../../all.php";

use it\icosaedro\io\InputStream;
use it\icosaedro\io\IOException;
use it\icosaedro\utils\UString;
use it\icosaedro\utils\Strings;
use it\icosaedro\io\texts\EncodingInterface;
use RuntimeException;


/**
 * Service class for the Reader class; should not be used in applications.
 * Generic reader for the Reader class that handles encodings with fixed
 * number of bytes per char, for example UCS-2/UTF-16, UCS-4/UTF-32.
 * 
 * @author Umberto Salsi <salsi@icosaedro.it>
 * @version $Date: 2015/03/11 06:08:22 $
 */
class ReaderByWords implements ReaderFilterInterface {
	
	/**
	 * @var InputStream
	 */
	private $in;
	
	/**
	 * @var EncodingInterface
	 */
	private $encoding;
	
	/**
	 * @var string
	 */
	private $buf;
	
	/**
	 * End of line marker, encoded.
	 * @var string
	 */
	private $nl, $crnl;
	
	/**
	 * Sequence length of a codepoint in B.
	 * @var int
	 */
	private $word_size = 0;
	
	
	function __toString() {
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
	function __construct($in, $leading_bytes, $encoding) {
		$this->in = $in;
		$this->buf = $leading_bytes;
		$this->encoding = $encoding;
		$this->nl = $encoding->encode(UString::fromASCII("\n"));
		$this->crnl = $encoding->encode(UString::fromASCII("\r\n"));
		$this->word_size = strlen($this->nl);
	}
	
	
	/**
	 * Reads bytes and add to $buf.
	 * @return boolean True if at least 1 byte read; false at EOF.
	 * @throws IOException
	 */
	private function readMore()
	{
		do {
			// In some app, lines are overly long - tune the read forward
			// buffer accordingly:
			$n = (int) min(max(strlen($this->buf), 100), 10000);
			$more = $this->in->readBytes($n);
			if( $more === NULL )
				return FALSE;
		} while( strlen($more) == 0 );
		$this->buf .= $more;
		return TRUE;
	}


	/**
	 * Returns a codepoint.
	 * @return int
	 * @throws IOException
	 */
	function readCodepoint()
	{
		while( strlen($this->buf) < $this->word_size ){
			if( ! $this->readMore() ){
				if( strlen($this->buf) == 0 ){
					return -1;
				} else {
					$this->buf = "";
					// trailing garbage bytes at EOF
					return UString::REPLACEMENT_CHARACTER_CODEPOINT;
				}
			}
		}
		$ch_bytes = Strings::substring($this->buf, 0, $this->word_size);
		$this->buf = Strings::substring($this->buf, $this->word_size, strlen($this->buf));
		$ch = $this->encoding->decode($ch_bytes);
		if( $ch->length() != 1 )
			throw new RuntimeException("unexpected no. of codepoints decoding "
			. Strings::toLiteral($ch_bytes) . " with " . $this->encoding);
		return $ch->codepointAt(0);
	}
	
	
	/**
	 * Reads a strings of characters.
	 * @param int $n Read no more that this amount of characters/codepoints.
	 * @return UString
	 * @throws IOException
	 */
	function read($n) {
		if( $n <= 0 )
			return UString::fromASCII("");
		while( strlen($this->buf) < $this->word_size ){
			if( ! $this->readMore() ){
				if( strlen($this->buf) == 0 ){
					return NULL;
				} else {
					$this->buf = "";
					// FIXME: cache
					// trailing garbage bytes at EOF
					return UString::chr(UString::REPLACEMENT_CHARACTER_CODEPOINT);
				}
			}
		}
		$available = (int) (strlen($this->buf) / $this->word_size);
		if( $n > $available )
			$n = $available;
		$bytes = Strings::substring($this->buf, 0, $n * $this->word_size);
		$this->buf = Strings::substring($this->buf, $n * $this->word_size, strlen($this->buf));
		return $this->encoding->decode($bytes);
	}
	
	
	/**
	 * Reads a line.
	 * @return UString Line read or NULL on end of file.
	 * @throws IOException
	 */
	public function readLine() {
		// seach offset $nl of the first NL
		do {
			$offset = 0;
			do {
				$nl = strpos($this->buf, $this->nl, $offset);
				if( $nl === FALSE or $nl % $this->word_size == 0 )
					break;
				$offset = $nl + $this->word_size;
			} while(TRUE);
			
			if( $nl !== FALSE )
				break;
			
			if( $this->readMore() )
				continue;
			
			if( strlen($this->buf) == 0 )
				// EOF
				return NULL;
			
			// unterminated last line of the stream
			$n_words = (int) (strlen($this->buf) / $this->word_size);
			$bytes = Strings::substring($this->buf, 0, $n_words * $this->word_size);
			$line = $this->encoding->decode($bytes);
			if( $n_words * $this->word_size < strlen($this->buf) )
				// trailing garbage present
				// FIXME: cache
				$line = $line->append(UString::chr(UString::REPLACEMENT_CHARACTER_CODEPOINT));
			$this->buf = "";
			return $line;
		} while(TRUE);
			
		// remove [CR] NL
		$crnl = $nl - $this->word_size;
		if( $crnl >= 0 and Strings::substring($this->buf, $crnl, $crnl + strlen($this->crnl)) === $this->crnl)
			$eol = $crnl;
		else
			$eol = $nl;
		$bytes = Strings::substring($this->buf, 0, $eol);
		$this->buf = Strings::substring($this->buf, $nl + strlen($this->nl), strlen($this->buf));
		return $this->encoding->decode($bytes);
	}
	
	
	/**
	 * @return void
	 * @throws IOException
	 */
	function close() {
		if( $this->in !== NULL ){
			$this->in->close();
			$this->in = NULL;
		}
	}
	
}