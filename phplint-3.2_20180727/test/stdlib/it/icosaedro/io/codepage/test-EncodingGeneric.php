<?php
/*. require_module 'mbstring'; .*/

require_once __DIR__ . "/../../../../../../stdlib/all.php";
require_once __DIR__ . "/../../../../../../stdlib/utf8.php";

use it\icosaedro\utils\UString;
use it\icosaedro\utils\Strings;
use it\icosaedro\io\texts\EncodingGeneric;
use it\icosaedro\utils\TestUnit as TU;

const W_UTF8 = "perch\303\251"; # "why" in italian, UTF-8 encoding
const W_ISO = "perch\351"; # "why" in italian, ISO-8859-1
const W_ASCII = "perch?"; # "why" in italian, ASCII
const EURO = "\xE2\x82\xAC"; // EUR = U+20ac = \

// Constructor detects unknown/unsupported encoding (mb):
$got_exp_exception = false;
try {
	new EncodingGeneric("XXX", true, false);
} catch (InvalidArgumentException $ex) {
	// unsupported encoding XXX - OK
	$got_exp_exception = true;
}
if( ! $got_exp_exception )
	throw new RuntimeException();


// Constructor detects unknown/unsupported encoding (iconv):
$got_exp_exception = false;
try {
	new EncodingGeneric("XXX", false, true);
} catch (InvalidArgumentException $ex) {
	// unsupported encoding XXX - OK
	$got_exp_exception = true;
}
if( ! $got_exp_exception )
	throw new RuntimeException("missing expected exception");


// ISO-8859-1, mb
// ==============
$enc = new EncodingGeneric("ISO-8859-1", true, false);
TU::test($enc->encode(u("")), "");
TU::test($enc->decode("")->toUTF8(), "");
// encoding with unexpected char (EUR):
$s = UString::fromUTF8("\0" . W_UTF8 . EURO . "\0");
$bytes = $enc->encode($s);
TU::test($bytes, "\000perch\351?\000");
// decoding:
$s2 = $enc->decode($bytes);
TU::test($s2->toUTF8(), "\0" . W_UTF8 . "?" . "\0");
// decoding with invalid char (\200 = 128 = \x80) passes anyway:
TU::test($enc->decode("\000\200\000")->toUTF8(), "\000\302\200\000"); // \302\200 UTF8 = \x80


// ISO-8859-1, iconv
// =================
$enc = new EncodingGeneric("ISO-8859-1", false, true);
TU::test($enc->encode(u("")), "");
TU::test($enc->decode("")->toUTF8(), "");
// encoding with unexpected char (EUR):
$s = UString::fromUTF8("\0" . W_UTF8 . EURO . "\0");
$bytes = $enc->encode($s);
TU::test($bytes, "\000perch\351?\000"); // U+20ac --> "EUR"
// decoding:
$s2 = $enc->decode($bytes);
TU::test($s2->toUTF8(), "\0" . W_UTF8 . "?\0");
// decoding with invalid char (\x80) passes anyway:
TU::test($enc->decode("\000\200\207\000")->toUTF8(), "\000\302\200\302\207\000");


// UTF16-BR, mb
// ------------
//var_dump(mb_list_encodings());
$enc = new EncodingGeneric("UTF-16BE", true, false);
TU::test($enc->encode(u("")), "");
TU::test($enc->decode("")->toUTF8(), "");
$s = UString::fromUTF8("\0" . W_UTF8 . EURO . "\0");
$bytes = $enc->encode($s);
TU::test($bytes, "\000\000\000p\000e\000r\000c\000h\000\351 \254\000\000"); // " \254" --> "EUR"
// decoding:
$s2 = $enc->decode($bytes);
TU::test($s2->toUTF8(), "\0" . W_UTF8 . EURO . "\0");
// decoding with invalid char (\x80) passes anyway:
TU::test($enc->decode("\000\000\000\200\000\207\000\000")->toUTF8(), "\000\302\200\302\207\000");
// odd no. of bytes - trunk:
TU::test($enc->decode("\000Ax")->toUTF8(), "A");


// UTF16-BE, iconv
// ---------------
$enc = new EncodingGeneric("UTF-16BE", false, true);
TU::test($enc->encode(u("")), "");
TU::test($enc->decode("")->toUTF8(), "");
$s = UString::fromUTF8("\0" . W_UTF8 . EURO . "\0");
$bytes = $enc->encode($s);
TU::test($bytes, "\000\000\000p\000e\000r\000c\000h\000\351 \254\000\000"); // " \254" --> "EUR"
// decoding:
$s2 = $enc->decode($bytes);
TU::test($s2->toUTF8(), "\0" . W_UTF8 . EURO . "\0");
// decoding with invalid char (\x80) passes anyway:
TU::test($enc->decode("\000\000\000\200\000\207\000\000")->toUTF8(), "\000\302\200\302\207\000");
// odd no. of bytes:
TU::test($enc->decode("\000Ax")->toUTF8(), "A".UString::REPLACEMENT_CHARACTER_UTF8);


// mb stress test.
// ===============
$a = mb_list_encodings();
foreach($a as $encoding){
	if(in_array($encoding, array("auto")))
		continue;
	//echo "testing mb $encoding ...\n";
	$enc = new EncodingGeneric($encoding, true, false);
	
	// decode odd no. of bytes:
	$enc->decode("\000\000\000\000\000"); // 5 B
	
	// encode/decode single codepoint:
	for($cp = 0; $cp < 65536; $cp += 37){
		$u = UString::chr($cp);
		/* ignore = */ $enc->decode( $enc->encode($u) );
	}
	
	// decode random sequence of 1 B:
	for($b = 0; $b < 256; $b += 17)
		$enc->decode(chr($b));
	
	// decode random sequence of 2 B:
	for($b0 = 0; $b0 < 256; $b0 += 37)
		for($b1 = 0; $b1 < 256; $b1 += 37)
			$enc->decode(chr($b0).chr($b1));
	
	// decode random sequence of 3 B:
	for($b0 = 0; $b0 < 256; $b0 += 37)
		for($b1 = 0; $b1 < 256; $b1 += 37)
			for($b2 = 0; $b2 < 256; $b2 += 37)
				$enc->decode(chr($b0).chr($b1).chr($b2));
	
	// decode random sequence of 3 B:
	for($b0 = 0; $b0 < 256; $b0 += 37)
		for($b1 = 0; $b1 < 256; $b1 += 37)
			for($b2 = 0; $b2 < 256; $b2 += 37)
				for($b3 = 0; $b3 < 256; $b3 += 37)
					$enc->decode(chr($b0).chr($b1).chr($b2).chr($b3));
}

// iconv stress test.
// ==================
$a = mb_list_encodings(); // inconv() does not provide a list of encoding, using this instead
foreach($a as $encoding){
	// The POSIX iconv() does not support many encoding mb instead does:
	if(in_array($encoding, array("auto", "pass", "wchar", "byte2be", "byte2le",
		"byte4be", "byte4le", "BASE64", "UUENCODE", "HTML-ENTITIES",
		"Quoted-Printable", "7bit", "8bit", "UTF7-IMAP", "EUC-JP-2004",
		"SJIS-mac", "SJIS-2004", "JIS", "ISO-2022-JP-MS", "HZ",
		"JIS-ms", "ISO-2022-JP-2004"))
	or strpos($encoding, "#") !== FALSE // strange encodings
	or substr($encoding, 0, 2) === "CP") // Windows specific encodings
		continue;
	$enc = new EncodingGeneric($encoding, false, true);
//	echo "testing iconv $encoding (" . $enc->normalizedName() . ")...\n";
	
	// decode odd no. of bytes:
	$enc->decode("\000\000\000\000\000"); // 5 B
	
	// encode/decode single codepoint:
	for($cp = 0; $cp < 65536; $cp += 37){
		$u = UString::chr($cp);
		/* ignore = */ $enc->decode( $enc->encode($u) );
	}
	
	// decode random sequence of 1 B:
	for($b = 0; $b < 256; $b += 17)
		$enc->decode(chr($b));
	
	// decode random sequence of 2 B:
	for($b0 = 0; $b0 < 256; $b0 += 37)
		for($b1 = 0; $b1 < 256; $b1 += 37)
			$enc->decode(chr($b0).chr($b1));
	
	// decode random sequence of 3 B:
	for($b0 = 0; $b0 < 256; $b0 += 37)
		for($b1 = 0; $b1 < 256; $b1 += 37)
			for($b2 = 0; $b2 < 256; $b2 += 37)
				$enc->decode(chr($b0).chr($b1).chr($b2));
	
	// decode random sequence of 3 B:
	for($b0 = 0; $b0 < 256; $b0 += 37)
		for($b1 = 0; $b1 < 256; $b1 += 37)
			for($b2 = 0; $b2 < 256; $b2 += 37)
				for($b3 = 0; $b3 < 256; $b3 += 37)
					$enc->decode(chr($b0).chr($b1).chr($b2).chr($b3));
}
