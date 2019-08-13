<?php

namespace it\icosaedro\email;

require_once __DIR__ . "/../../../../../stdlib/all.php";

use it\icosaedro\utils\TestUnit as TU;

/**
 * Text encoding/decoding test.
 * @param string $plain Plain text.
 * @param string $encoded Expected encoded text.
 */
function encodeForthAndBack($plain, $encoded)
{
	TU::test(Field::encodeWords($plain, "text"), $encoded);
	TU::test(Field::decodeWords($encoded), $plain);
}


function testEncodeDecodeWords()
{
	// Special cases:
	TU::test(Field::decodeWords("=?"), "=?");
	TU::test(Field::decodeWords("=??="), "=??=");
	TU::test(Field::decodeWords("=???="), "=???=");
	TU::test(Field::decodeWords("=????="), "=????=");
	TU::test(Field::decodeWords("=?ascii?q?abc?="), "abc");
	
	// Space between encoded words must be ignored:
	TU::test(Field::decodeWords(" =?ascii?q?a?= =?ascii?q?b?= "), " ab ");
	TU::test(Field::decodeWords("=?ISO-8859-1?Q?=E0=E8=EC=F2=F9?="), "àèìòù");
	TU::test(Field::decodeWords("=?UTF-8?B?w6DDqMOsw7LDuQ==?="), "àèìòù");
	
	// Wrong charset:
	TU::test(Field::decodeWords("=?XXX?Q?abc=C8?="), "abc");
	
	// Wrong method:
	TU::test(Field::decodeWords("=?UTF-8?X?abc?="), "=?UTF-8?X?abc?=");
	
	// More tests from RFC 2047, par. 8:
	TU::test(Field::decodeWords("(=?ISO-8859-1?Q?a?=)"), "(a)");
	TU::test(Field::decodeWords("(=?ISO-8859-1?Q?a?= b)"), "(a b)");
	TU::test(Field::decodeWords("(=?ISO-8859-1?Q?a?= =?ISO-8859-1?Q?b?=)"), "(ab)");
	TU::test(Field::decodeWords("(=?ISO-8859-1?Q?a?=  =?ISO-8859-1?Q?b?=)"), "(ab)");
	TU::test(Field::decodeWords("(=?ISO-8859-1?Q?a?=\r\n   =?ISO-8859-1?Q?b?=)"), "(ab)");
	TU::test(Field::decodeWords("(=?ISO-8859-1?Q?a_b?=)"), "(a b)");
	TU::test(Field::decodeWords("(=?ISO-8859-1?Q?a?= =?ISO-8859-2?Q?_b?=)"), "(a b)");
	
	/*
	 * Random brute force tests:
	 */
	
	encodeForthAndBack("", "");
	encodeForthAndBack("a", "a");
	
	// All ASCII printable set test:
	$ascii_printable = " !\"#\$%&'()*+,-./0123456789:;<=>?@ABCDEFGHIJKLMNOPQRSTUVWXYZ[\\]^_`abcdefghijklmnopqrstuvwxyz{|}~";
	encodeForthAndBack($ascii_printable,
		"=?UTF-8?Q?=20!\"#\$%&'()*+,-./0123456789:;<=3D>=3F@ABCDEFGHIJK?= =?UTF-8?Q?LMNOPQRSTUVWXYZ[\\]^=5F`abcdefghijklmnopqrstuvwxyz{?= =?UTF-8?Q?|}~?=");
	
	encodeForthAndBack("Quite long, ASCII-only subject but with spaces, so it can be split as required among several continuation lines, no need for special encoding.", "Quite long, ASCII-only subject but with spaces, so it can be split as required among several continuation lines, no need for special encoding.");
	
	// Overly long ASCII only string must be split into short encoded tokens,
	// so that the final header field can later be split into continuation lines:
	encodeForthAndBack("012345678901234567890123456789012345678901234567890123456789012345678901234567890123456789",
		"=?UTF-8?Q?01234567890123456789012345678901234567890123456789?= =?UTF-8?Q?0123456789012345678901234567890123456789?=");
	
	// ASCII only but with special marker "=?" needs encoding:
	encodeForthAndBack("=?UTF-8?B??=", "=?UTF-8?B?PT9VVEYtOD9CPz89?=");
	
	// Single non-ASCII char:
	encodeForthAndBack("à", "=?UTF-8?B?w6A=?=");
	
	// Mostly ASCII triggers Q encoding:
	encodeForthAndBack("aa aaè", "=?UTF-8?Q?aa=20aa=C3=A8?=");
	
	// Mostly non-ASCII triggers B encoding:
	encodeForthAndBack("aèaaè", "=?UTF-8?B?YcOoYWHDqA==?=");
	
	// "phrase":
	TU::test(Field::encodeWords($ascii_printable, "phrase"),
		"=?UTF-8?Q?=20!=22=23=24=25=26=27=28=29*+=2C-=2E/0123456789=3A?= =?UTF-8?Q?=3B=3C=3D=3E=3F=40ABCDEFGHIJKLMNOPQRSTUVWXYZ=5B=5C?= =?UTF-8?Q?=5D=5E=5F=60abcdefghijklmnopqrstuvwxyz=7B=7C=7D=7E?=");
}

/**
 * @param string[int][int] $addresses
 * @return string
 */
function addressesToString($addresses)
{
	$res = "";
	foreach($addresses as $a){
		if( strlen($res) > 0 )
			$res .= ", ";
		$res .= "a=" . $a[0] . " n=" . $a[1];
	}
	return $res;
}

function testDecodeAddresses()
{
	TU::test(addressesToString(Field::parseAddresses("")), "");
	TU::test(addressesToString(Field::parseAddresses("<u@d>")), "a=u@d n=");
	TU::test(addressesToString(Field::parseAddresses(
		"before<u@d>")), "a=u@d n=before");
	TU::test(addressesToString(Field::parseAddresses(
		"<u@d>after")), "a=u@d n=after");
	TU::test(addressesToString(Field::parseAddresses(
		" before<u@d>after ")), "a=u@d n=before after");
	TU::test(addressesToString(Field::parseAddresses(
		"(name) u@d")), "a=u@d n=name");
	TU::test(addressesToString(Field::parseAddresses(
		"u@d (name)")), "a=u@d n=name");
	
	// Bad addresses:
	TU::test(addressesToString(Field::parseAddresses("<u d>")), "");
	TU::test(addressesToString(Field::parseAddresses("u d")), "");
	TU::test(addressesToString(Field::parseAddresses("u@d>")), "");
	
	// Samples from RFC 2047:
	TU::test(addressesToString(Field::parseAddresses(
		"=?US-ASCII?Q?Keith_Moore?= <moore@cs.utk.edu>"
		.", =?ISO-8859-1?Q?Keld_J=F8rn_Simonsen?= <keld@dkuug.dk>"
		.", =?ISO-8859-1?Q?Andr=E9?= Pirard <PIRARD@vm1.ulg.ac.be>")),
		"a=moore@cs.utk.edu n=Keith Moore"
		.", a=keld@dkuug.dk n=Keld J\303\270rn Simonsen"
		.", a=PIRARD@vm1.ulg.ac.be n=André Pirard");
}

/**
 * Convert associative array to string.
 * @param string[string] $a
 * @return string
 */
function aToS($a)
{
	$s = "";
	foreach($a as $k => $v)
		$s .= "|$k=$v";
	return $s;
}

function testParseWordAndParameters()
{
	TU::test(aToS(Field::parseWordAndParameters("")), "");
	TU::test(aToS(Field::parseWordAndParameters("text/plain")), "|WORD=text/plain");
	TU::test(aToS(Field::parseWordAndParameters("text/plain ")), "|WORD=text/plain");
	TU::test(aToS(Field::parseWordAndParameters("text/plain ;")), "|WORD=text/plain");
	TU::test(aToS(Field::parseWordAndParameters("text/plain; charset")), "|WORD=text/plain|charset=");
	TU::test(aToS(Field::parseWordAndParameters("text/plain; charset=")), "|WORD=text/plain|charset=");
	TU::test(aToS(Field::parseWordAndParameters("text/plain; charset=UTF8")), "|WORD=text/plain|charset=UTF8");
	TU::test(aToS(Field::parseWordAndParameters("text/plain; charset=\"UTF8\"")), "|WORD=text/plain|charset=UTF8");
	TU::test(aToS(Field::parseWordAndParameters("attachment; filename=\"=?ISO-8859-1?Q?=E0=E8=EC=F2=F9?= =?ISO-8859-1?Q?=E0=E8=EC=F2=F9?=\"")), "|WORD=attachment|filename=àèìòùàèìòù");
}

function main()
{
	testEncodeDecodeWords();
	testDecodeAddresses();
	testParseWordAndParameters();
}

main();
