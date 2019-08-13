<?php
namespace it\icosaedro\web;

/*. require_module 'core';  require_module 'spl';  require_module 'pcre'; .*/

require_once __DIR__ . "/../containers/Printable.php";

use InvalidArgumentException;
use it\icosaedro\containers\Printable;

	
/**
 * HTTP Accept-Language header parsing and handling routines.
 * Along with each request, the browser may sent the list of preferred languages,
 * countries and quality factor for each of these entries. The quality factor
 * ranges from 0 up to 1.000; no more than three decimals are allowed.
 * The syntax of the "Accept-Language" header field consists in a list of
 * entries separated by comma; each entry must indicate a language and possibly
 * a list of country codes separated by hyphen. Examples:
 * 
 * <blockquote><pre>
 * Accept-Language: en
 * Accept-Language: en;q=1.0, it;q=0.5
 * Accept-Language: en-GB;q=1.0, en-US;q=1.0, fr;q=0.5, it;q=0.2
 * </pre></blockquote>
 * 
 * <p>The bestSupportedLanguageFromRequest() method compares the list of the
 * languages supported by the server against the list of the client's supported
 * languages and returns an object of this class containing the best match found;
 * the "language" field then contains the matching language, and the "country"
 * field contains the matching country.
 * 
 * <p>While searching the best match between the requested and the available
 * supported langueges, the following criteria are applied.
 * Only languages shared between preferred and supported are considered.
 * A matching server supported country scores the most; unspecified
 * server country matches less but matches any preferred country.
 * Server quality weights double than the preferred one, assuming that a
 * more complete and accurate information is preferred by the user rather
 * than a more easily readable one. Ambiguous cases that give the same
 * score are resolved returning the first highest match found. Examples:
 * 
 * <blockquote><pre>
 * // Preferred and supported lists are equal:
 * preferred: it, en
 * supported: en, it
 * result:    en;q=1
 * 
 * // Preferred and supported have reversed quality:
 * preferred: it;q=1, en;q=0.5
 * supported: en;q=1, it;q=0.5
 * result:    en;q=1
 * 
 * // First server language with highest score wins:
 * preferred: en-US, en-GB
 * supported: en, en-GB, en-US
 * result:    en-gb;q=1
 * </pre></blockquote>
 * 
 * <p>Actually the syntax of the language field can be MUCH more articulated.
 * For example "zh-cmn-Hans-CN" means Chinese (language=zh), Mandarin
 * (extension1=cmn), Simplified script (script=Hans), as used in China (region=CN).
 * 
 * <p>References: {@link https://en.wikipedia.org/wiki/IETF_language_tag}
 * 
 * @author Umberto Salsi <salsi@icosaedro.it>
 * @version $Date: 2018/06/06 09:58:46 $
 */
class AcceptLanguage implements Printable {
	
	/**
	 * Language code, from 2 to 8 small letters ISO 639 or BCP 47, mandatory,
	 * example: "en".
	 * @var string
	 */
	public $language;
	
	/**
	 * Extended language sub-tags, 3 small letters, default empty.
	 * @var string
	 */
	public $ext1, $ext2, $ext3;
	
	/**
	 * Script, 4 small letters (ISO 15924), default empty.
	 * @var string
	 */
	public $script;
	
	/**
	 * Country or regional code, from 2 to 8 small letters and digits (ISO 3166-1,
	 * UN M.49), example "gb"; default empty.
	 * @var string
	 */
	public $region;
	
	/**
	 * Quality factor in the range ]0.0,1.0].
	 * @var float
	 */
	public $quality = 0.0;
	
	/**
	 * @param string $language
	 * @param string $ext1
	 * @param string $ext2
	 * @param string $ext3
	 * @param string $script
	 * @param string $region
	 * @param float $quality
	 */
	function __construct($language, $ext1, $ext2, $ext3, $script, $region, $quality)
	{
		$this->language = $language;
		$this->ext1 = $ext1;
		$this->ext2 = $ext2;
		$this->ext3 = $ext3;
		$this->script = $script;
		$this->region = $region;
		$this->quality = $quality;
	}
	
	function __toString()
	{
		return $this->language
		. (strlen($this->ext1) > 0? "-" . $this->ext1 : "")
		. (strlen($this->ext2) > 0? "-" . $this->ext2 : "")
		. (strlen($this->ext3) > 0? "-" . $this->ext3 : "")
		. (strlen($this->script) > 0? "-" . $this->script : "")
		. (strlen($this->region) > 0? "-" . $this->region : "")
		. ";q=" . $this->quality;	
	}
	
	/**
	 * Parse contents of the Accept-Language HTTP header field.
	 * @param string $accept List of accepted language as found in the
	 * "Accept-Language" HTTP header.
	 * @return AcceptLanguage[int] Found accepted languages, possibly empty.
	 * @throws InvalidArgumentException
	 */
	static function parse($accept)
	{
		$res = /*. (AcceptLanguage[int]) .*/ array();
		$accept = trim($accept);
		if( strlen($accept) == 0 )
			return $res;
		$a = explode(",", $accept);
		foreach($a as $s){
			$s = strtolower(trim($s));
			if( preg_match("/^[a-z]{2,8}(-[a-z0-9]{2,8})*(;q=[01](\\.[0-9]{1,3})?)?\$/sD", $s) !== 1 )
				throw new InvalidArgumentException("invalid syntax: $accept");
			$quality = 1.0;
			$fields = explode(";", trim($s));
			if( count($fields) == 2 ){
				$quality = (float) substr($fields[1], 2);
				if( !(0.0 < $quality && $quality <= 1.0) )
					throw new InvalidArgumentException("invalid range in quality factor q=$quality in $accept");
			}
			$fields = explode("-", $fields[0]);
			$language = $fields[0];
			// Scan extended language subtags:
			$ext1 = $ext2 = $ext3 = "";
			$i = 1;
			if( $i < count($fields) && preg_match("/^[a-z]{3}\$/sD", $fields[$i]) === 1 ){
				$ext1 = $fields[$i++];
				if( $i < count($fields) && preg_match("/^[a-z]{3}\$/sD", $fields[$i]) === 1 ){
					$ext2 = $fields[$i++];
					if( $i < count($fields) && preg_match("/^[a-z]{3}\$/sD", $fields[$i]) === 1 ){
						$ext3 = $fields[$i++];
					}
				}
			}
			// Scan script:
			$script = "";
			if( $i < count($fields) && preg_match("/^[a-z]{4}\$/sD", $fields[$i]) === 1 )
				$script = $fields[$i++];
			// Scan region:
			$region = "";
			if( $i < count($fields) && preg_match("/^([a-z]{2})|([0-9][0-9a-z]{3})\$/sD", $fields[$i]) === 1 )
				$region = $fields[$i++];
			// Ignore remaining variant and extension sub-tags.
			$res[] = new self($language, $ext1, $ext2, $ext3, $script, $region, $quality);
		}
		return $res;
	}

	/**
	 * Return the best matching supported language chosen between preferred.
	 * @param self[int] $preferred Preferred languages.
	 * @param self[int] $supported Supported languages.
	 * @return self Best matching supported language. Returns NULL if the list
	 * of supported languages is empty. If no preferred language matches a
	 * supported language, the first listed supported language is returned.
	 */
	static function bestSupportedLanguage($preferred, $supported)
	{
		if( count($supported) == 0 )
			return NULL;
		$def = /*. (self) .*/ NULL;
		foreach($supported as $def)
			break;
		$best = /*. (self) .*/ NULL;
		$score = 0;
		foreach( $supported as $s ){
			foreach($preferred as $p){
				if( $p->language === $s->language ){
					$score2 = 0;
					
					if( strlen($s->ext1) == 0 )
						$score2 += 5;
					else if( $s->ext1 === $p->ext1 )
						$score2 += 10;
					
					if( strlen($s->ext2) == 0 )
						$score2 += 5;
					else if( $s->ext2 === $p->ext2 )
						$score2 += 10;
					
					if( strlen($s->ext3) == 0 )
						$score2 += 5;
					else if( $s->ext3 === $p->ext3 )
						$score2 += 10;
					
					if( strlen($s->script) == 0 )
						$score2 += 50;
					else if( $s->script === $p->script )
						$score2 += 100;
					
					if( strlen($s->region) == 0 )
						$score2 += 50; // region-agnostic server entry
					else if( $s->region === $p->region )
						$score2 += 100; // same region
					
					$score2 += (int) (55*$s->quality + 45*$p->quality);
					
					if( $best === NULL || $score2 > $score ){
						$best = $s;
						$score = $score2;
					}
				}
			}
		}
		return $best === NULL? $def : $best;
	}

	/**
	 * Return the preferred language between the preferred ones of the request.
	 * See method {@link self::bestSupportedLanguage()} for an explanation of
	 * the matching algorithm applied. Example:
	 * <blockquote><pre>
	 * $lang = AcceptLanguage::bestSupportedLanguageFromRequest("en, fr, it;q=0.5");
	 * echo "language: ", $lang-&gt;language, ", region: ", $lang-&gt;region;
	 * </pre></blockquote>
	 * In this example, if the user's accepted languages are "it;q=1, en;q=0.5"
	 * the ambiguity is resolved in favor of "en".
	 * @param string $supported Server supported languages, given as a string
	 * with the same syntax of the "Accept-Language" field.
	 * @return self Best matching supported language. If not even a partially
	 * matchinh language is found, returns the first in the list of supported
	 * languages. If the list of supported languages is empty, returns NULL.
	 * @throws InvalidArgumentException Invalid syntax in supported languages.
	 */
	static function bestSupportedLanguageFromRequest($supported)
	{
		$preferred_list = /*. (self[int]) .*/ array();
		if( isset( $_SERVER["HTTP_ACCEPT_LANGUAGE"] ) ){
			try {
				$preferred_list = self::parse( $_SERVER["HTTP_ACCEPT_LANGUAGE"] );
			}
			catch(InvalidArgumentException $e){
				// we may safely ignore invalid requests
			}
		}
		$supported_list = self::parse($supported);
		return self::bestSupportedLanguage($preferred_list, $supported_list);
	}
	
}