<?php

namespace it\icosaedro\utils;

require_once __DIR__ . "/../../../../../stdlib/all.php";

use it\icosaedro\utils\UString;
use it\icosaedro\utils\TestUnit as TU;


/*. UString .*/ function fu(/*. string .*/ $s)
{
	return UString::fromUTF8($s);
}

const W_UTF8 = "perch\303\251"; # "why" in italian, UTF-8 encoding
const W_ISO = "perch\351"; # "why" in italian, ISO-8859-1
const W_ASCII = "perch?"; # "why" in italian, ASCII
const EURO = "\xE2\x82\xAC"; # Euro sign, UTF-8
const RC = UString::REPLACEMENT_CHARACTER_UTF8;


function main() /*. throws \Exception .*/
{
	# Test invalid UTF-8 encodings:
	# ----------------------------
	# Invalid start of sequence:
	TU::test( fu("\x80zz")->toUTF8(), RC."zz");
	TU::test( fu("\xbfzz")->toUTF8(), RC."zz");
	TU::test( fu("\xc0zz")->toUTF8(), RC."zz");
	TU::test( fu("\xf0zz")->toUTF8(), RC."zz");
	# Non-minimal 2-bytes encoding:
	TU::test( fu("a\xc0\x80")->toUTF8(), "a".RC.RC);
	# Non-minimal 3-bytes encoding:
	TU::test( fu("a\xe0\x80\x80")->toUTF8(), "a".RC.RC.RC);
	# Trunked 2-bytes sequence:
	TU::test( fu("A\xc2")->toUTF8(), "A".RC);
	# Invalid cont. in 2-bytes sequence:
	TU::test( fu("A\xc2Z")->toUTF8(), "A".RC."Z");
	# Trunked 3-bytes sequence:
	TU::test( fu("A\xe1")->toUTF8(), "A".RC);
	TU::test( fu("A\xe1\x80Z")->toUTF8(), "A".RC.RC."Z");
	# Invalid cont. in 3-bytes sequence:
	TU::test( fu("A\xe1YZ")->toUTF8(), "A".RC."YZ");
	TU::test( fu("A\xe1\x80Z")->toUTF8(), "A".RC.RC."Z");

	$empty = fu("");

	$us = $empty;
	TU::test($us->toUTF8(), "");

	$us = fu(W_UTF8);
	TU::test($us->length(), 6);
	TU::test($us->toUTF8(), W_UTF8);
	TU::test($us->toISO88591(), W_ISO);
	TU::test($us->toASCII(), W_ASCII);

	$us = UString::fromISO88591(W_ISO);
	TU::test($us->toUTF8(), W_UTF8);
	TU::test($us->toISO88591(), W_ISO);
	TU::test($us->toASCII(), W_ASCII);

	# charAt():
	$res = $empty;
	for($i = 0; $i < $us->length(); $i++)
		$res = $res->append( $us->charAt($i) );
	TU::test( $res, $us );

	# substring():
	TU::test($empty->substring(0,0)->equals($empty), TRUE);
	TU::test($us->substring(0,0), $empty);
	TU::test($us->substring(1,1), $empty);
	TU::test($us->substring(6,6), $empty);
	TU::test($us->substring(0,6), $us);

	# append():
	TU::test( fu("a")->append(fu(""))->append(fu("b")), fu("ab"));
	$us = $us->append($us)->append($us);
	$us = $us->append($us)->append($us);
	$us = $us->append($us)->append($us);
	$us = $us->append($us)->append($us);
	#echo "Building char by char, ", $us->length(), " codepoints: ";
	#$t = new Timer(TRUE);
	$res = $empty;
	for($i = 0; $i < $us->length(); $i++){
		$c = $us->substring($i, $i+1);
		$res = $res->append($c);
	}
	TU::test($res->equals($us), TRUE);
	#$t->stop();
	#echo $t, "\n";

	# remove():
	TU::test( $empty->remove(0,0), $empty );
	$abc = fu("abc");
	TU::test( $abc->remove(0,0), $abc );
	TU::test( $abc->remove(1,1), $abc );
	TU::test( $abc->remove(3,3), $abc );
	TU::test( $abc->remove(0,3), $empty );
	TU::test( $abc->remove(0,1), fu("bc") );
	TU::test( $abc->remove(1,2), fu("ac") );
	TU::test( $abc->remove(2,3), fu("ab") );

	# insert():
	$abc = fu("abc");
	TU::test( $empty->insert($abc, 0), $abc );
	TU::test( $abc->insert($empty, 0), $abc );
	TU::test( $abc->insert($empty, 1), $abc );
	TU::test( $abc->insert($empty, 3), $abc );
	TU::test( $abc->insert(fu("z"), 0), fu("zabc") );
	TU::test( $abc->insert(fu("z"), 1), fu("azbc") );
	TU::test( $abc->insert(fu("z"), 3), fu("abcz") );

	# startsWith():
	TU::test( $empty->startsWith($empty), TRUE );
	TU::test( $empty->startsWith($us), FALSE );
	TU::test( $us->startsWith($empty), TRUE );
	TU::test( $us->startsWith($us), TRUE );
	TU::test( $us->startsWith(fu("p")), TRUE );
	TU::test( $us->startsWith(fu("per")), TRUE );
	TU::test( $us->startsWith(fu("xxx")), FALSE );

	# endsWith():
	TU::test( $empty->endsWith($empty), TRUE );
	TU::test( $us->endsWith($empty), TRUE );
	TU::test( $us->endsWith($us), TRUE );
	TU::test( $us->endsWith(fu("\303\251")), TRUE );
	TU::test( $us->endsWith(fu("ch\303\251")), TRUE );
	TU::test( $us->endsWith(fu("xxx")), FALSE );

	# indexOf():
	TU::test( $empty->indexOf($empty), 0 );
	$q = fu(W_UTF8 . W_UTF8);
	TU::test( $empty->indexOf($q), -1 );
	TU::test( $q->indexOf($empty), 0 );
	TU::test( $q->indexOf(fu("p")), 0 );
	TU::test( $q->indexOf(fu("per")), 0 );
	TU::test( $q->indexOf(fu("er")), 1 );
	TU::test( $q->indexOf(fu("h\303\251")), 4 );
	TU::test( $q->indexOf(fu("\303\251p")), 5 );
	TU::test( $q->indexOf(fu("\303\251pe")), 5 );
	TU::test( $q->indexOf(fu("\303\251perch\303\251")), 5 );
	TU::test( $q->indexOf($q), 0 );
	TU::test( $q->indexOf(fu("xxx")), -1 );
	TU::test( $q->indexOf(fu("p"), 0), 0);
	TU::test( $q->indexOf(fu("p"), 1), 6);
	TU::test( $q->indexOf(fu("p"), 6), 6);

	# lastIndexOf():
	TU::test( $empty->lastIndexOf($empty, 0), 0 );
	TU::test( $empty->lastIndexOf(fu("xx"), 0), -1 );
	TU::test( $q->lastIndexOf($empty, 12), 12 );
	TU::test( $q->lastIndexOf($empty, 11), 11 );
	TU::test( $q->lastIndexOf(fu("p"), 12), 6 );
	TU::test( $q->lastIndexOf(fu("p"), 7), 6 );
	TU::test( $q->lastIndexOf(fu("p"), 6), 0 );
	TU::test( $q->lastIndexOf(fu("\303\251"), 12), 11 );
	TU::test( $q->lastIndexOf(fu("\303\251"), 6), 5 );
	TU::test( $q->lastIndexOf($q, 12), 0 );

	# trim():
	TU::test( fu("abc")->trim(), fu("abc") );
	TU::test( fu(" abc ")->trim(), fu("abc") );
	TU::test( fu(" abc ")->trim(fu(" ")), fu("abc") );
	TU::test( fu(" abc ")->trim(fu(" ac")), fu("b") );
	TU::test( fu(" abc ")->trim(fu(" a..z")), fu("") );
	$fffd = UString::chr(0xfffd);
	$fffe = UString::chr(0xfffe);
	$ffff = UString::chr(65535);
	$subj = $fffd->append($fffe)->append(fu("abc"))->append($ffff);
	$black =  $fffd->append(fu(".."))->append($ffff);
	TU::test( $subj->trim($black), fu("abc") );

	# replace():
	# TU::test( fu("abaco")->replace("a", ""), fu("bco"));
	
	# UCS2 encoding conversions:
	$c = fu("AB");
	TU::test( $c->toUCS2LE(), "A\000B\000");
	TU::test( $c->toUCS2BE(), "\000A\000B");
	TU::test( UString::fromUCS2LE( $us->toUCS2LE() ), $us );
	TU::test( UString::fromUCS2BE( $us->toUCS2BE() ), $us );

	# Build very long string with samples of any codepoint in [0,65535]:
	$big = $empty;
	for($cp = 0; $cp < 65536; $cp += 100 ){
		$big = $big->append( UString::chr($cp) );
	}
	#   Check resulting length:
	TU::test( $big->length(), (int) (65536 / 100) + 1 );
	#   Scan and check every codepoint:
	for($i = 0; $i < $big->length(); $i++ ){
		TU::test( $big->codepointAt($i), 100*$i );
	}
	#   Convert to and from any full-range capable encoding:
	TU::test( fu( $big->toUTF8() ), $big );
	TU::test( UString::fromUCS2LE( $big->toUCS2LE() ), $big );
	TU::test( UString::fromUCS2BE( $big->toUCS2BE() ), $big );

	# Case-sensitive routines:
	TU::test( fu("AbCÈè")->toUpperCase(), fu("ABCÈÈ"));
	TU::test( fu("AbCÈè")->toLowerCase(), fu("abcèè"));
	TU::test( fu("AbCÈè")->equalsIgnoreCase(fu("abcèè")), TRUE);
	TU::test( fu("AbCÈè")->equalsIgnoreCase(fu("xbcèè")), FALSE);

	# serialize(), unserialize():
	TU::test( unserialize( serialize($us) ), $us );
	
	# Test toASCII() optimization:
	TU::test( fu("AbCÈè".EURO."z")->toASCII(), "AbC???z");
	
	# Stress test on preg_*() limits:
	$n = 350000;
	$s = str_repeat("AÈ".RC /* 1+2+3 B, 3 codepoints */, $n);
	$u = fu($s);
	TU::test($u->length(), 3*$n);
	for($i = 0; $i < $u->length(); $i += 3*1000){
		TU::test($u->codepointAt($i), 65);
		TU::test($u->codepointAt($i+2), UString::REPLACEMENT_CHARACTER_CODEPOINT);
	}
	
	
	// UTF16 - Build a test string with these codepoints:
	$codepoints = array(0, 127, 128, 255, 256, 65535, 65536, 0x10fffe, 0x10ffff);
	$u = UString::fromASCII("");
	foreach($codepoints as $codepoint)
		$u = $u->append(UString::chr($codepoint));

	// Check the built string actually contains that codepoints, no more, no less:
	TU::test($u->length(), count($codepoints));
	for($i = 0; $i < count($codepoints); $i++){
		TU::test($u->codepointAt($i), $codepoints[$i]);
	}

	// Testing UTF16 by converting forth and back:
	TU::test(UString::fromUTF16LE($u->toUTF16LE()), $u);
	TU::test(UString::fromUTF16BE($u->toUTF16BE()), $u);
}

class testUString extends TU {
	function run() /*. throws \Exception .*/
	{
		main();
	}
}

$tu = new testUString();
$tu->start();

// THE END
