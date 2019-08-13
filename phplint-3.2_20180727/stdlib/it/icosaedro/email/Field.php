<?php

namespace it\icosaedro\email;

require_once __DIR__ . "/../../../all.php";

/*. require_module 'iconv';  require_module 'pcre'; .*/

use ErrorException;
use InvalidArgumentException;
use it\icosaedro\containers\Printable;
use it\icosaedro\utils\Strings;

/**
 * Single header field split into name and value. General methods to decode and
 * encode field values are also provided. Methods related to specific fields are
 * implemented in the Header class.
 * 
 * <p>The encoding process follows the RFC 2047 specifications, taking UTF-8
 * encoded strings to retrieve bare ASCII for maximum compatibility.
 * 
 * <p>The decoding process must be more tolerant. We accepts non-ASCII codes
 * assuming UTF-8 as default as per the RFC 6532; the resulting sanitized strings
 * are then decoded as per the RFC 2047.
 * 
 * @author Umberto Salsi <salsi@icosaedro.it>
 * @version $Date: 2018/07/14 11:43:04 $
 */
class Field implements Printable {
	
	/**
	 * Name of this field.
	 * @var string
	 */
	public $name;
	
	/**
	 * Value of this field.
	 * @var string
	 */
	public $value;
	
	/**
	 * 
	 * @param string $name
	 * @param string $value
	 */
	function __construct($name, $value)
	{
		$this->name = $name;
		$this->value = $value;
	}
	
	/**
	 * Returns this field as a RFC 822 header formatted string. Long lines are
	 * folded to continuation lines.
	 * @return string RFC 822 header line(s) terminates by a single "\n".
	 */
	function __toString()
	{
		return $this->name . ": " . wordwrap($this->value, 75, "\n ", FALSE) . "\n";
	}
	
	/**
	 * Convert the string to UTF-8, replaces HT with SP, and remove any remaining
	 * ASCII control code. Ignores any error, possibly returning the same
	 * verbatim string (without control codes).
	 * @param string $s Data to convert.
	 * @param string $charset Charset of the data.
	 * @return string Converted string, UTF-8 encoded, no ASCII controls.
	 */
	static function toUTF8($s, $charset)
	{
		try {
			$s = iconv($charset, "UTF-8//IGNORE", $s);
		}
		catch(ErrorException $e){
			// "Wrong charset or conversion not allowed". Remove any non-ASCII:
			$s = preg_replace("/[\x80-\xff]/", "", $s);
		}
		// Replace HT with SP:
		$s = (string) str_replace("\t", " ", $s);
		// Remove remaining ASCII control chars:
		return preg_replace("/[\\000-\x1f\x7f]/", "", $s);
	}
	
	/**
	 * Decodes a string of "encoded words" as per the RFC 2047, that is header
	 * fields containing non-ASCII characters. The string to decode can be the
	 * whole field value (for example the Subject) or part of a field value.
	 * Note that encoded words must be decoded only after the syntax of the
	 * specific field has been parsed, and only some fields are allowed to use
	 * this encoding. If the string contains "ASCII-extended" codes, it is
	 * assumed UTF-8 encoded (RFC 6532).
	 * @param string $s Encode words to decode.
	 * @return string Decoded field value, UTF-8 encoded. ASCII control codes
	 * are removed; improperly encoded characters are removed.
	 */
	static function decodeWords($s)
	{
		$s = self::toUTF8($s, "UTF-8"); // clean encoding and remove ctrls
		$encoded_words = is_int(strpos($s, "=?"));
		if( ! $encoded_words )
			return $s;
		$res = "";
		// White spaces between encoded words must be ignored; so, remember
		// here if we already found a word before:
		$found_word = FALSE;
		while( strlen($s) > 0 ){
			// Search possible beginning of the next encoded word in $s:
			$start = Strings::indexOf($s, "=?");
			if( $start < 0 ){
				// No more encoded words.
				$res .= $s;
				break;
			} else if( $start > 0 ){
				$plain = substr($s, 0, $start);
				$found_spaces = strlen(trim($plain)) == 0? strlen($plain) : 0;
				$res .= $plain;
				$s = substr($s, $start);
			} else {
				$found_spaces = 0;
			}
			// Split "=?CHARSET?ENCODING?DATA?=...":
			$a = explode("?", $s, 5);
			if( count($a) < 5 || ! Strings::startsWith($a[4], "=") ){
				$res .= $s;
				break;
			}
			$charset = $a[1];
			$encoding = strtoupper($a[2]);
			$data = $a[3];
			if( !(strlen($charset) > 0 && ($encoding === "B" || $encoding === "Q")) ){
				$res .= "=?";
				$s = substr($s, 2);
				$found_word = FALSE;
				continue;
			}
			// Decode data to UTF-8:
			if( $encoding === "B" ){
				$data = base64_decode($data);
			} else {
				$data = (string) str_replace("_", " ", $data);
				$data = quoted_printable_decode($data);
			}
			$data = self::toUTF8($data, $charset);
			if( $found_word && $found_spaces > 0 )
				// Remove white spaces after previous word:
				$res = substr($res, 0, strlen($res) - $found_spaces);
			$res .= $data;
			$s = substr($s, 7 + strlen($charset) + strlen($a[3]));
			$found_word = TRUE;
		}
		return $res;
	}
	
	/**
	 * Returns a "field quoted-printable" encoded string.
	 * This means that byte codes [0,32], '=', '?' and [127,255] are encoded as
	 * "=HH" where HH are upper-case hexadecimal digits; any other byte passes
	 * unmodified. Very long resulting strings are split into shorter chunks
	 * separated by space. Note that the string must be UTF-8 because we need to
	 * know how multi-byte sequences are made to prevent to split them, so that
	 * each chunk shall contain integral UTF-8 sequences.
	 * @param string $s String to encode, UTF-8 encoded.
	 * @param string $position Either "text" or "phrase".
	 * @return string Field quoted printable string.
	 */
	private static function quotedPrintableFieldEncoding($s, $position)
	{
		if( $position === "text" )
			// Allowed ASCII chars are marked with "x":
			//     "................................ !"#$%&'()*+,-./0123456789:;<=>?@ABCDEFGHIJKLMNOPQRSTUVWXYZ[\]^_`abcdefghijklmnopqrstuvwxyz{|}~"
			$set = "                                 xxxxxxxxxxxxxxxxxxxxxxxxxxxx x xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx";
		else
			$set = "                                 x        xx x xxxxxxxxxxx       xxxxxxxxxxxxxxxxxxxxxxxxxx      xxxxxxxxxxxxxxxxxxxxxxxxxx    ";
		$res = "";
		$chunk_len = 0;
		$len = strlen($s);
		for($i = 0; $i < $len; $i++){
			$c = ord($s[$i]);
			
			// If chunk too long at UTF-8 sequence beginning, split with space:
			if( $chunk_len >= 50
			&& (($c & 0x80) == 0 || ($c & 0xA0) != 0xA0) ){
				$res .= " ";
				$chunk_len = 0;
			}
			
			// Encode byte:
			if( 0 <= $c && $c <= 32 || 127 <= $c || $set[$c] !== "x" ){
				$res .= sprintf("=%02X", $c);
				$chunk_len += 3;
			} else {
				$res .= chr($c);
				$chunk_len++;
			}
		}
		return $res;
	}

	/**
	 * MIME encode a header field sub-part. Since here we are dealing only with
	 * a sub-part, overly long lines should be split by the caller.
	 * @param string $s Sub-part to encode (UTF-8).
	 * @param string $position Either "text" (default) or "phrase", see RFC 2047
	 * par. 5 for more.
	 * @return string MIME encode field sub-part containing only the allowed
	 * ASCII printable characters; spaces are added to allow to split long lines
	 * later once the value of the field is complete.
	 */
	static function encodeWords($s, $position = 'text')
	{
		$charset = "UTF-8";
		
		// Evaluate how many bytes need encoding:
		switch ($position) {
			case 'phrase':
				$bad_set = "/[\\000-\x1f\x22-\x29\x2c\x2e\x3a-\x40\x5b-\x60\x7b-\xff]/";
				break;
			case 'text':
				$bad_set = "/[\\000-\x1f\x3d\x3f\x5f\x7f-\xff]/";
				break;
			default:
				throw new InvalidArgumentException("unknonw position: $position");
		}
		$bad_count = preg_match_all($bad_set, $s);
		
		/*
		 * Overly long string shall be split at spaces once the field is completed;
		 * here we set a quite arbitrary safe limit; here we don't know the
		 * whole field but only its sub-part, so we stay conservative about this
		 * chunk len and we set a quite arbitrary limit of 40 bytes per chunk.
		 * Must be multiple of 4 to properly split Base64 chunks too.
		 */
		$max_chunk = 40;

		/*
		 * No MIME header field encoding needed at all if:
		 * 1. the string contains only allowed ASCII printable chars, and
		 * 2. there are no "=?" sequences that may confuse the remote parser, and
		 * 3. there are no overly long chunks that cannot be safely split.
		 */
		if ($bad_count == 0 && strpos($s, "=?") === FALSE
		&& preg_match("/[^ ]{". ($max_chunk+1) .",}/", $s) === 0
		) {
			return $s;
		}
		
		/*
		 * Use Q method when most of the bytes (2/3 or more) are printable
		 * ASCII to preserve readability; use B method otherwise to save space:
		 */
		if (strlen($s) < 3 * $bad_count) {
			$method = 'B';
			$encoded = base64_encode($s);
			$maxlen = $max_chunk - 7 - strlen($charset);
			$maxlen -= $maxlen % 4; // Base64 can be safely split at each 4 bytes
			$encoded = trim(chunk_split($encoded, $maxlen, " "));
		} else {
			$method = 'Q';
			$encoded = self::quotedPrintableFieldEncoding($s, $position);
		}
		
		/*
		 * If chunks separated by space where introduced, each chunk becomes an
		 * encoded token by its own:
		 */
		$encoded = (string) str_replace(" ", "?= =?$charset?$method?", $encoded);

		return "=?$charset?$method?$encoded?=";
	}
	
	/**
	 * Returns a regular expression for valid e-mail syntax.
	 * See DocBlock of the method below for further details.
	 */
	private static function emailRegex()
	{
		static $address_regex = /*.(string).*/ NULL;
		if( $address_regex === NULL ){
			// RFC 5322 addr-spec rule, but without quoted-string, c
			// without domain-literal, and without obsolete syntax;
			// ASCII extended chars added asper RFC 6532:
			$atext = "[-A-Za-z0-9!#\$%&'*+\\/=?^_`{|}~\x80-\xff]";
			$dot_atom_text = "$atext+(\\.$atext+)*";
			$address_regex = "$dot_atom_text@$dot_atom_text";
		}
		return $address_regex;
	}

	/**
	 * Check syntax and encoding of an e-mail address and returns a diagnostic
	 * description of the issue found.
	 * Basically it is the RFC 5322 syntax with the following restrictions:
	 * no quoted strings; no obsolete syntax; no domain literal; no spaces.
	 * "ASCII extended" codes passes, assuming UTF-8 be checked properly
	 * elsewhere. The resulting syntax is as follows:
	 * <blockquote><pre>
	 * addr-spec = dot-atom-text "@" dot-atom-text;
	 * dot-atom-text = atext {atext} {"." atext {atext}};
	 * </pre></blockquote>
	 * where the atext characters set includes: letters, digits, ASCII-extended
	 * codes, and the following:
	 * <blockquote><tt>! # $ % &amp; ' * + - / = ? ^ _ ` { | } ~</tt></blockquote>
	 * 
	 * The e-mail address can be rejected for one of these reasons:
	 * it's the empty string;
	 * it's too long (more than 200 bytes);
	 * invalid UTF-8 encoding;
	 * invalid syntax;
	 * contains the special sequences =? or ?=.
	 * 
	 * @param string $address Email address to check, UTF-8.
	 * @return string Outcome of the check: NULL if ok, or description of the
	 * issue otherwise.
	 */
	static function checkEmailAddress($address)
	{
		static $address_regex = /*.(string).*/ NULL;
		if( $address_regex === NULL )
			$address_regex = "/^" . self::emailRegex() . "\$/sD";
		if( strlen($address) < 1 )
			return "empty";
		if( strlen($address) > 200 ) // enough?
			return "too long";
		if( self::toUTF8($address, "UTF-8") !== $address )
			return "invalid UTF-8 encoding";
		if( preg_match($address_regex, $address) !== 1 )
			return "invalid syntax or forbidden characters";
		if( strpos($address, "=?") !== FALSE || strpos($address, "?=") !== FALSE )
			return "detected special sequence =? or ?=";
		return NULL;
	}

	/**
	 * Return true if the address is a "valid" email address according to the
	 * {@link self::checkEmailAddress()} method.
	 * @param string $address Email address to validate, UTF-8.
	 * @return bool
	 */
	static function isValidEmailAddress($address)
	{
		return self::checkEmailAddress($address) === NULL;
	}
	
	/**
	 * Decodes a list of addresses like in the "From", "To" or "Cc" header fields.
	 * This parser only support a subset of syntaxes for each entry of the list:
	 * <ul>
	 * <li>Name &lt;Address&gt;</li>
	 * <li>&lt;Address&gt; Name</li>
	 * <li>Name1 &lt;Address&gt; Name2</li>
	 * <li>(Name) Address</li>
	 * <li>Address (Name)</li>
	 * <li>Address</li>
	 * </ul>
	 * The Address must contain only ASCII printable characters, no white spaces.
	 * Entries that do not follow any of the patterns above are silently ignored.
	 * @param string $s Value of the field.
	 * @return string[int][int] Decoded list of addresses. Each entry of the array
	 * contains one more array with two elements: at index 0 is the address; at
	 * index 1 is the name or comment. Empty addresses are silently ignored.
	 * Addresses contain only ASCII printable characters, no white spaces.
	 * Names are UTF-8 encoded, possibly empty.
	 */
	static function parseAddresses($s)
	{
		$address_regex = self::emailRegex();
		$res = /*. (string[int][int]) .*/ array();
		$a = explode(",", $s);
		foreach($a as $entry){
			$entry = trim($entry);
			if( preg_match("/<($address_regex)>/", $entry, $matches, PREG_OFFSET_CAPTURE) === 1 ){
				// Found address; anything else is the name.
				$matches2 = cast("mixed[int][int]", $matches);
				$address = (string) $matches2[1][0];
				if( ! self::isValidEmailAddress($address) )
					continue;
				$i = (int) $matches2[1][1];
				$name = trim( self::decodeWords( substr($entry, 0, $i - 1) . " " . substr($entry, $i + strlen($address) + 1) ) );
				$res[] = array($address, $name);
				
			} else if( preg_match("/\\(([^)]*)\\)/", $entry, $matches, PREG_OFFSET_CAPTURE) === 1 ){
				// Found name; anything else is the address.
				$matches2 = cast("mixed[int][int]", $matches);
				$name = (string) $matches2[1][0];
				$i = (int) $matches2[1][1];
				$address = trim( substr($entry, 0, $i - 1) . " " . substr($entry, $i + strlen($name) + 1) );
				if( ! self::isValidEmailAddress($address) )
					continue;
				$name = trim( self::decodeWords($name) );
				$res[] = array($address, $name);
				
			} else if( preg_match("/^$address_regex\$/sD", $entry) === 1 ){
				// Bare address found.
				$address = $entry;
				if( ! self::isValidEmailAddress($address) )
					continue;
				$res[] = array($address, "");
			} // else: cannot parse -- ignore
		}
		return $res;
	}
	
	/**
	 * Decodes a parameter value, possibly double-quoted.
	 * @param string $value
	 * @return string UTF-8 encoded value of the parameter.
	 */
	static function decodeParameterValue($value)
	{
		// FIXME: RFC 2047 explicitly forbids word encoding in double-quoted strings
		// but it seems the only way to encode file names and works with any
		// client I tested, so:
		return self::decodeWords( trim( trim($value), "\"") );
	}
	
	/**
	 * Parse a word possibly followed by options, like in "WORD; p1=v1; p2=v2".
	 * Example of fields that can be parsed by this method are: Content-Type,
	 * Content-Disposition.
	 * @param string $value
	 * @return string[string] Associative array of the word and parameters.
	 * Each key is only alphanumeric characters plus hyphen, underscore and slash
	 * turned into lower-case letters only. Values are UTF-8 encoded.
	 * The "WORD" entry contains the word. Anything that cannot be parsed is
	 * silently ignored. If nothing at all can be parsed, the empty array is
	 * returned.
	 */
	static function parseWordAndParameters($value)
	{
		$res = /*. (string[string]) .*/ array();
		$a = explode(";", $value);
		if( count($a) < 1 )
			return $res;
		$word_syntax = "/^[-0-9A-Za-z_\\/*]+\$/sD";
		$word = strtolower(trim($a[0]));
		if( preg_match($word_syntax, $word) !== 1 )
			return $res;
		$res["WORD"] = $word;
		for($i = 1; $i < count($a); $i++){
			$b = explode("=", $a[$i], 2);
			$name = strtolower( trim($b[0]) );
			if( preg_match($word_syntax, $name) !== 1 )
				continue;
			if( count($b) == 1 )
				$value = "";
			else
				$value = self::decodeParameterValue($b[1]);
			$res[$name] = $value;
		}
		return $res;
	}
	
	/**
	 * Parse a field. HT is replaced with SP; any remaining control character is
	 * removed. Anything before the first colon is the name; if no colon, the
	 * whole line is the name; the name is then right-trimmed. Anything after
	 * the first colon ":" is the value; the value is trimmed; if no colon, the
	 * value is the empty string.
	 * @param string $line Line, unfolded.
	 * @return self Parsed field.
	 */
	static function parse($line)
	{
		$line = (string) str_replace("\t", " ", $line);
		$line = preg_replace("/[\\000-\x1f\x7f]/", "", $line);
		$a = explode(":", $line, 2);
		$name = rtrim($a[0]);
		$value = count($a) == 2? trim($a[1]) : "";
		return new self($name, $value);
	}
	
}
