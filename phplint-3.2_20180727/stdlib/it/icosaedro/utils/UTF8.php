<?php
/*.
	require_module 'core';
	require_module 'spl';
	require_module 'pcre';
.*/

namespace it\icosaedro\utils;

require_once __DIR__ . "/Strings.php";

use OutOfRangeException;


/**
 * Utility functions for UTF-8 string encoding. This class only provides
 * very basic functions mostly intended to be used in others, higher level
 * packages.
 *
 * <p>BEWARE. These functions do not check for the actual encoding of the
 * passed strings and always blindly assume these strings are properly
 * UTF-8 encoded strings.  If arbitrary data are passed, unexpected results
 * may arise. Strings from untrusted sources should always be checked with the
 * {@link self::sanitize()} method. This class is intended to provide utilities
 * for other tools that already ensure every string is properly UTF-8 encoded
 * just from the beginning.
 *
 * @author Umberto Salsi <salsi@icosaedro.it>
 * @version $Date: 2018/06/27 08:55:57 $
 */
class UTF8 {
	
	/*
	 * UTF-8, valid sequences:
	 * 
	 * 1 bytes sequence, codepoints range [U+000000,U+00007f]:
	 * 
	 *      00-7f
	 * 
	 * 2 bytes sequence, codepoints range [U+000080,U+0007ff]:
	 * 
	 *      c2-df  80-bf
	 * 
	 * 3 bytes sequence, codepoints range [U+000800,U+00ffff]:
	 * 
	 *      e0     a0-bf  80-bf
	 *      e1-ef  80-bf  80-bf
	 * 
	 * 4 bytes sequence, codepoints range [U+010000,U+10ffff]:
	 * 
	 *      f0     90-bf  80-bf  80-bf
	 *      f1-f3  80-bf  80-bf  80-bf
	 *      f4     80-8f  80-bf  80-bf
	 */
	
	/**
	 * The highest defined codepoint is U+10ffff.
	 */
	const MAX_CODEPOINT = 0x10ffff;
	
	/**
	 * Unicode replacement codepoint.
	 * Codepoint: U+FFFD.
	 * HTML entity: <tt>&amp;#xfffd;</tt> or <tt>&amp;65533;</tt>.
	 * Displayed: <tt>&#xfffd;</tt>
	 */
	const REPLACEMENT_CHARACTER_CODEPOINT = 0xfffd;
	
	/**
	 * Unicode replacement codepoint, UTF-8 encoded.
	 * PHP string: <tt>"\357\277\275" === "\xEF\xBF\xBD"</tt>
	 */
	const REPLACEMENT_CHARACTER_UTF8 = "\xEF\xBF\xBD";

	/**
		Returns the codepoint as UTF-8 string of bytes.
		@param int $code  Codepoint [0,0x10ffff].
		@return string String of bytes that represents the given codepoint.
		@throws OutOfRangeException  If the codepoint is invalid.
	*/
	static function chr($code)
	{
		if( !(is_int($code) && 0 <= $code && $code <= self::MAX_CODEPOINT) )
			throw new OutOfRangeException("$code");

		if( $code < 128 ){
			$s = chr($code);
		} else if( $code < 2048 ){
			$s = chr(0xc0 | ($code >> 6))
				. chr(0x80 | ($code & 0x3f));
		} else if( $code < 65536 ){
			$s = chr(0xe0 | ($code >> 12))
				. chr(0x80 | ($code >> 6) & 0x3f)
				. chr(0x80 | ($code & 0x3f));
		} else {
			$s = chr(0xf0 | ($code >> 18))
				. chr(0x80 | ($code >> 12) & 0x3f)
				. chr(0x80 | ($code >> 6) & 0x3f)
				. chr(0x80 | ($code & 0x3f));
		}
		return $s;
	}


	/**
		Return true if the passed byte is the continuation byte of a
		UTF-8 sequence.
		@param int $b  Subject byte.
		@return bool  True if the subject byte is the continuation byte
		of a UTF-8 sequence.
	*/
	static function isCont($b)
	{
		return ($b & 0xc0) == 0x80;
	}


	/**
	 * Return the length of the UTF-8 sequence given its starting byte.
	 * Byte code ranges are as follows (by increasing code):
	 * <pre>
	 * [0x00,0x7f]  1 byte sequence (ASCII) returns 1
	 * [0x80,0xbf]  continuation byte -- returns 0
	 * [0xc0,0xc1]  unused byte codes -- returns 0
	 * [0xc2,0xdf]  2 bytes seq. starts -- returns 2
	 * [0xe0,0xef]  3 bytes seq. starts -- returns 3
	 * [0xf0,0xf4]  4 bytes seq. starts -- returns 4
	 * [0xf5,0xff]  unused byte codes -- returns 0
	 * </pre>
	 * @param int $b  First byte of the sequence in [0,255].
	 * @return int  Length of the sequence in bytes, that is 1, 2, 3 or 4.
	 * Returns 0 if the byte code is invalid or out of the range [0,255].
	 */
	static function sequenceLength($b)
	{
		if( $b < 0 ){
			return 0;
		} else if( $b <= 0x7f ){
			return 1;
		} else if( $b <= 0xc1 ){
			return 0;
		} else if( $b <= 0xdf ){
			return 2;
		} else if( $b <= 0xef ){
			return 3;
		} else if( $b <= 0xf4 ){
			return 4;
		} else {
			return 0;
		}
	}


	/**
	 * Returns the codepoint at a given position in a string.
	 * @param string $s UTF-8 encoded string.
	 * @param int $byte_index Byte index of the sequence.
	 * @return int The code of the codepoint.
	 * @throws OutOfRangeException  If the index is invalid.
	 */
	static function codepointAtByteIndex($s, $byte_index)
	{
		if( $byte_index < 0 or $byte_index >= strlen($s) )
			throw new OutOfRangeException("$byte_index");
		$b1 = ord($s[$byte_index]);
		$seqlen = self::sequenceLength($b1);
		if( $byte_index + $seqlen > strlen($s) )
			// not the beginning of a valid sequence
			return self::REPLACEMENT_CHARACTER_CODEPOINT;
		switch($seqlen){
			case 0:
				// trunked sequence
				return self::REPLACEMENT_CHARACTER_CODEPOINT;
			case 1:
				return $b1;
			case 2:
				$b2 = ord($s[$byte_index+1]);
				return (($b1 & 0x1f) << 6) + ($b2 & 0x3f);
			case 3:
				$b2 = ord($s[$byte_index+1]);
				$b3 = ord($s[$byte_index+2]);
				return (($b1 & 0x0f) << 12) + (($b2 & 0x3f) << 6) + ($b3 & 0x3f);
			case 4:
				$b2 = ord($s[$byte_index+1]);
				$b3 = ord($s[$byte_index+2]);
				$b4 = ord($s[$byte_index+3]);
				$codepoint = (($b1 & 7) << 18) + (($b2 & 0x3f) << 12)
					+ (($b3 & 0x3f) << 6) + ($b4 & 0x3f);
				if( $codepoint > 0x10ffff )
					// beyond defined Unicode range
					return self::REPLACEMENT_CHARACTER_CODEPOINT;
				return $codepoint;
			default:
				throw new \RuntimeException("seqlen=$seqlen");
		}
	}


	/**
	 * Return the byte index given the UTF-8 sequence index.
	 * @param string $s UTF-8 encoded string.
	 * @param int $codepoint_index  Index of the UTF-8 sequence, ranging from
	 * 0 (the first sequence) up to the length in codepoints of the
	 * string. Note that this last sequence does not exist because
	 * its byte index is just one byte above the last sequence so the
	 * index returned points to the byte just next to the end of the
	 * string.
	 * @return int  Byte index of the UTF-8 sequence.
	 * @throws OutOfRangeException  If the parameter is out of the
	 * range from 0 up to the length in codepoints of the string.
	 */
	static function byteIndex($s, $codepoint_index)
	{
		if( $codepoint_index < 0 )
			throw new OutOfRangeException("$codepoint_index");
		$s_len = strlen($s);
		$byte_index = 0;
		while( $codepoint_index > 0 ){
			if( $byte_index >= $s_len )
				throw new OutOfRangeException("$codepoint_index");
			$b = ord($s[$byte_index]);
			$seq_len = self::sequenceLength($b);
			if( $seq_len <= 0 )
				# Just skip invalid byte.
				$seq_len = 1;
			$byte_index += $seq_len;
			$codepoint_index--;
		}
		return $byte_index;
	}


	/**
	 * Return the length of the string as number of codepoints.
	 * @param string $s UTF-8 encoded string.
	 * @return int  Length of the string as number of codepoints.
	 */
	static function length($s)
	{
		return preg_match_all("/[^\x80-\xbf]/", $s);
	}


	/**
	 * Returns the codepoint index given its byte index.
	 * @param string $s UTF-8 encoded string.
	 * @param int $byte_index Byte index of the codepoint, in
	 * [0,strlen($this-&gt;s)].  Note that if $byte_index is exactly equal
	 * to strlen($this-&gt;s), then the result is the length of the string
	 * in codepoints.
	 * @return int Byte index of this codepoint, that is the number of UTF-8
	 * sequences from the beginning of the string up there.
	 * @throws OutOfRangeException  If $byte_index is out of the range
	 * [0,strlen($this-&gt;s)].
	 */
	static function codepointIndex($s, $byte_index)
	{
		if( $byte_index < 0 or $byte_index > strlen($s) )
			throw new OutOfRangeException("$byte_index");
		// substr($s, 0, $byte_index) returns false if $byte_index==0. Workaround:
		if( $byte_index == 0 )
			return 0;
		// counts how many codepoints there are before the offset:
		return self::length(substr($s, 0, $byte_index));
	}


	/**
	 * Returns the codepoint at the given index.
	 * @param string $s UTF-8 encoded string.
	 * @param int $codepoint_index  Index of the codepoint, in the range from 0
	 * up to the length of the string minus one. Note that for an empty string
	 * there is no valid range.
	 * @return int  Codepoint at the given index.
	 * @throws OutOfRangeException  Invalid index.
	 */
	static function codepointAt($s, $codepoint_index)
	{
		return self::codepointAtByteIndex($s, self::byteIndex($s, $codepoint_index));
	}


	/**
	 * Returns the codepoint at the given index encoded as UTF-8.
	 * @param string $s UTF-8 encoded string.
	 * @param int $i Index of the codepoint in the range from 0 up to
	 * UTF8::length($s)-1.
	 * @return string Codepoint as a UTF-8 string.
	 * @throws OutOfRangeException  If the index is invalid.
	 */
	static function charAt($s, $i)
	{
		try {
			$byte_index = self::byteIndex($s, $i);
		}
		catch(OutOfRangeException $e){
			throw new OutOfRangeException("$i");
		}
		if( $byte_index >= strlen($s) )
			throw new OutOfRangeException("$byte_index");
		$seq_len = self::sequenceLength( ord($s[$byte_index]) );
		if( $seq_len <= 0 or $byte_index + $seq_len > strlen($s) )
			return self::REPLACEMENT_CHARACTER_UTF8;
		return substr($s, $byte_index, $seq_len);
	}
	
	
	/**
	 * Maximum length in bytes of the UTF-8 string to sanitize as a whole.
	 * Strings longer that that are splitted in smaller pieces and each piece
	 * sanitized apart before joining the result.
	 * In fact, preg_match() fails returning false on PHP 5.6 (but not under PHP 7.1)
	 * attempting to match strings longer than about 900000 bytes. Here we set a
	 * much more conservative value to account for possible past and future changes
	 * to pcre implementation.
	 * @access private
	 */
	const SANITIZE_MAX_SUBJECT = 10000;
	

	/**
	 * Sanitizes the string replacing invalid bytes. Invalid bytes, incomplete
	 * UTF-8 sequences, non-minimal sequences and invalid codepoints
	 * are replaced.
	 * @param string $u The string to sanitize, possibly NULL.
	 * @param string $replacement Invalid bytes or sequences are replaced with
	 * this string of bytes. Default: Unicode replacement character.
	 * @return string Properly encoded UTF-8 string. If the subject string
	 * is NULL, NULL is returned as well.
	 */
	static function sanitize($u, $replacement = self::REPLACEMENT_CHARACTER_UTF8)
	{
		static $VALID_LEADING_SEQS = /*. (string) .*/ NULL;
		if( $VALID_LEADING_SEQS === NULL ){
			$C = "[\x80-\xBF]";
			$VALID_LEADING_SEQS = "/^("
				. "[\\x00-\x7F]"
				. "|[\xC2-\xDF]$C"
				. "|\xE0[\xA0-\xBF]$C"
				. "|[\xE1-\xEF]$C$C"
				. "|\xF0[\x90-\xBF]$C$C"
				. "|[\xF1-\xF3]$C$C$C"
				. "|\xF4[\x80-\x8F]$C$C"
				. ")*+/";
		}
		
		if( strlen($u) == 0 )
			return $u;  // either NULL or empty
		
		// Split very long strings and validate separately to overcome pcre_match()
		// limitations:
		if( strlen($u) > self::SANITIZE_MAX_SUBJECT ){
			$res = "";
			$end = 0;
			do {
				$start = $end;
				if( strlen($u) - $end <= self::SANITIZE_MAX_SUBJECT ){
					$end = strlen($u);
				} else {
					$end = $start + self::SANITIZE_MAX_SUBJECT;
					// Don't break sequences adjusting $end to the beginning of the
					// last sequence, but no more than 3 bytes backward:
					if( self::isCont(ord($u[$end])) )  $end--;
					if( self::isCont(ord($u[$end])) )  $end--;
					if( self::isCont(ord($u[$end])) )  $end--;
				}
				$chunk = substr($u, $start, $end - $start);
				$res .= self::sanitize($chunk, $replacement);
			} while($end < strlen($u));
			return $res;
		}
		
		// Try matching as many valid UTF-8 sequences as possible from the submitted
		// string $u. If the matching string is shorter than the whole $u, then
		// there are invalid sequences: get the sane leading part, drop a byte
		// and try again.
		$res = "";
		while(strlen($u) > 0){
			if( preg_match($VALID_LEADING_SEQS, $u, $matches) !== 1 )
				throw new \RuntimeException("preg_match() failed");
			$sane_lead = (string) $matches[0];
			$sane_lead_len = strlen($sane_lead);
			if( $sane_lead_len == 0 ){
				$u = Strings::substring($u, 1, strlen($u));
				$res .= $replacement;
			} else {
				$u = Strings::substring($u, $sane_lead_len, strlen($u));
				$res .= $sane_lead;
			}
		}
		return $res;
	}

}
