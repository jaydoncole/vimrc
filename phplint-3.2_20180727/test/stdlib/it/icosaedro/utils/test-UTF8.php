<?php

namespace it\icosaedro\utils;

require_once __DIR__ . "/../../../../../stdlib/all.php";

use it\icosaedro\utils\TestUnit as TU;

/**
 * @param int $codepoint
 */
function testCodepoint($codepoint) {
	$utf8 = UTF8::chr($codepoint);
	$codepoint2 = UTF8::codepointAtByteIndex($utf8, 0);
	TU::test($codepoint2, $codepoint);
	
	if( PHP_MAJOR_VERSION == 7 ){
		// Under PHP7, compare with Unicode escape \u{}:
		$s = eval(sprintf('return "\\u{%x}";', $codepoint));
		TU::test($s, $utf8);
	}
}

/**
 * @throws \ErrorException
 */
function main() {
	// Test chr(), codepointAtByteIndex():
	for($codepoint = 0; $codepoint <= UTF8::MAX_CODEPOINT;
		$codepoint = ($codepoint + 1) + ($codepoint >> 7) // inc by 129/128 factor
	){
		testCodepoint($codepoint);
	}
	
	// Test codepointIndex(), codepointAt() and charAt():
	$s = ""; // quite random UTF-8 string to build
	$codepoints = /*.(int[int]).*/ array(); // maps codepoint index to codepoint
	$byte_offsets = /*.(int[int]).*/ array(); // maps codepoint index to byte offset
	for($codepoint = 0; $codepoint <= UTF8::MAX_CODEPOINT;
		$codepoint = ($codepoint + 1) + ($codepoint >> 7) // inc by 129/128 factor
	){
		$codepoints[] = $codepoint;
		$seq = UTF8::chr($codepoint);
		$byte_offsets[] = strlen($s);
		$s .= $seq;
	}
	TU::test(UTF8::length($s), count($byte_offsets));
	for($codepoint_index = 0; $codepoint_index < count($byte_offsets); $codepoint_index++){
		// check codepointIndex():
		$codepoint_index2 = UTF8::codepointIndex($s, $byte_offsets[$codepoint_index]);
		TU::test($codepoint_index2, $codepoint_index);
		// check codepointAt():
		TU::test(UTF8::codepointAt($s, $codepoint_index), $codepoints[$codepoint_index]);
		// check charAt():
		$codepoint2 = UTF8::codepointAtByteIndex(UTF8::charAt($s, $codepoint_index), 0);
		TU::test($codepoint2, $codepoints[$codepoint_index]);
	}
	
	// Testing sanitize():
	TU::test(UTF8::sanitize(NULL, "?"), NULL);
	TU::test(UTF8::sanitize("", "?"), "");
	// ...with valid strings of a single codepoint:
	for($codepoint = 0; $codepoint <= UTF8::MAX_CODEPOINT; $codepoint += 10){
		$u = UTF8::chr($codepoint);
		TU::test(UTF8::sanitize($u, "?"), $u);
	}
	// ...with invalid seq:
	TU::test(UTF8::sanitize("\xC1ab", "?"), "?ab"); // invalid start of seq
	TU::test(UTF8::sanitize("ab\xC1", "?"), "ab?"); // invalid start of seq
	TU::test(UTF8::sanitize("\xe0\x80\x80", "?"), "???"); // non minimal seq
	TU::test(UTF8::sanitize("\xe0", "?"), "?"); // trunked seq
	TU::test(UTF8::sanitize("\xe0\xa0", "?"), "??"); // trunked seq
	TU::test(UTF8::sanitize("\xe0Z", "?"), "?Z"); // trunked seq
	TU::test(UTF8::sanitize("\xe0\xa0Z", "?"), "??Z"); // trunked seq
	TU::test(UTF8::sanitize("", "?"), "");
	
	// ...with quite long, valid strings:
	$s = file_get_contents(__FILE__); // ASCII only
	TU::test(UTF8::sanitize($s), $s);
	
	$s = str_repeat("The following text copied from a japanese web site, no idea of what does it mean:
 * このページへのブックマークコメントはまだありません。
 * The following text copied from a german web site, no idea of what does it mean:
 * Das ist die größte und schnellste
 * From Wikipedia (russian):
 * Избранная статья - Литургический кодекс", 1000);
	TU::test(UTF8::sanitize($s), $s);
	
	// ...with quite long, invalid string of random bytes:
	$s = "";
	srand(1234);
	for($i = 500; $i > 0; $i--)
		$s .= chr(rand(0,255)) . chr(rand(0,255));
	$s = str_repeat($s, 10);
	UTF8::sanitize($s, "?");
}

main();
