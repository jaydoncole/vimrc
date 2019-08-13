<?php

namespace it\icosaedro\web;

require_once __DIR__ . "/../../../all.php";

/**
 * HTML routines.
 * @author Umberto Salsi <salsi@icosaedro.it>
 * @version $Date: 2018/04/09 13:25:18 $
 */
class Html {
	
	
	/**
	 * @param int $width_em
	 */
	static function echoSpan($width_em)
	{
		echo "<span style='display: inline-block; width: ", $width_em, "em;'></span>";
	}
	
	
	/**
	 * Renders verbatim string as regular HTML, UTF-8 encoded text. Replaces
	 * invalid sequences with the Unicode replacement character. The resulting
	 * string is safe for being put in verbatim HTML text and quoted or double-
	 * quoted attribute.
	 * Beware of the htmlspecialchars(), as by default silently returns the
	 * empty string if the subject string contains invalid encoding, which
	 * generally is not what you expect.
	 * @param string $s UTF-8 encoded string.
	 * @return string
	 */
	static function text($s)
	{
		return htmlspecialchars($s, ENT_SUBSTITUTE | ENT_QUOTES, "UTF-8");
	}
	
	/**
	 * Simple colored box.
	 * @param string $text_html Inner HTML text.
	 * @param string $color Background color, for example "#ffffff";
	 */
	static function coloredBox($text_html, $color)
	{
		echo "<div style='margin: 1em; border: 0.1em solid black; padding: 1em; background-color: $color;'>$text_html</div>";
	}
	
	
	/**
	 * Simple notice box.
	 * @param string $text_html Inner HTML text.
	 */
	static function noticeBox($text_html)
	{
		self::coloredBox($text_html, "#ffffaa");
	}
	
	/**
	 * Simple warning box.
	 * @param string $text_html Inner HTML text.
	 */
	static function warningBox($text_html)
	{
		self::coloredBox($text_html, "#ffee88");
	}
	
	/**
	 * Simple error box.
	 * @param string $text_html Inner HTML text.
	 */
	static function errorBox($text_html)
	{
		self::coloredBox($text_html, "#ff8888");
	}
}
