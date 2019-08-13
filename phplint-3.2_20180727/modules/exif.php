<?php
/**
Exif Functions.

See: {@link http://www.php.net/manual/en/ref.exif.php}
@package exif
*/

define("EXIF_USE_MBSTRING", 1);

/*. mixed .*/ function exif_tagname(/*. string .*/ $index){}

/**
 * @param mixed $stream Image file name or stream (PHP 7.2.0+ only).
 * @param string $sections
 * @param boolean $arrays
 * @param boolean $thumbnail
 * @return mixed Associative array or FALSE on error or specified section were
 * not found.
 * @triggers E_WARNING Failed accessing file or stream data.
 */
function exif_read_data($stream, $sections = NULL, $arrays = FALSE,
	$thumbnail = FALSE){}

/*. mixed .*/ function exif_thumbnail(/*. string .*/ $stream, /*. return int .*/ &$width = 0, /*. return int .*/ &$height = 0, /*. return int .*/ &$imagetype = 0)/*. triggers E_WARNING .*/{}

/**
 * @param string $imagefile
 * @return int On error returns FALSE, but this does not care too much PHPLint
 * users as they always map errors into exceptions.
 * @triggers E_WARNING Failed accessing image file.
 */
function exif_imagetype($imagefile){}