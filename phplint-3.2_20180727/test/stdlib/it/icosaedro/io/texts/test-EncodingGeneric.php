<?php

require_once __DIR__ . "/../../../../../../stdlib/all.php";
require_once __DIR__ . "/../../../../../../stdlib/utf8.php";

use it\icosaedro\io\texts\EncodingGeneric;
use it\icosaedro\utils\TestUnit as TU;

////$ori = ini_set("mbstring.substitute_character", "none");
////ini_set("mbstring.substitute_character", "long"); // --> U+HHHH
//$ori = ini_set("mbstring.substitute_character", "?");
////$ori = ini_set("mbstring.substitute_character", "".ord("?"));
//ini_set("mbstring.strict_detection", "1");
//mb_substitute_character(0xfffd);
//$res = mb_convert_encoding("\xff", "UTF-8", "ASCII");
//echo "(", rawurlencode($res), ")\n";


// ASCII
// =====
// it should behave just like EncodingASCII:
$e = new EncodingGeneric("ASCII", true, false);

TU::test($e->decode(NULL), NULL);
TU::test($e->decode(""), u(""));
TU::test($e->decode("Hello, world!\n"), u("Hello, world!\n"));
// FAILS: \xff becomes U+00ff rather than replacement char:
// 2015-03-11 https://bugs.php.net/bug.php?id=69217
//TU::test($e->decode("\xff\xffABC"), u("\\ufffd\\ufffdABC"));
// FAILS: \x80 becomes U+0080 rather than replacement char:
// 2015-03-11 https://bugs.php.net/bug.php?id=69217
//TU::test($e->decode("\x00\x7f\x80\xff"), u("\x00\x7f\\ufffd\\ufffd"));

TU::test($e->encode(NULL), NULL);
TU::test($e->encode(u("")), "");
TU::test($e->encode(u("ABCD\n")), "ABCD\n");
TU::test($e->encode(u("Euro: \\u20ac, Replacement char: \\ufffd")), "Euro: ?, Replacement char: ?");



// ISO-8859-1:
// ==========
$e = new EncodingGeneric("ISO-8859-1", true, false);

TU::test($e->decode(NULL), NULL);
TU::test($e->decode(""), u(""));
TU::test($e->decode("ABCD\n"), u("ABCD\n"));
TU::test($e->decode("\xa1\xe8"), u("\\u00a1\\u00e8"));
// FAILS: \x80 becomes U+0080 rather than replacement char:
// 2015-03-11 https://bugs.php.net/bug.php?id=69217
//TU::test($e->decode("\x80"), u("\\ufffd"));

TU::test($e->encode(NULL), NULL);
TU::test($e->encode(u("")), "");
TU::test($e->encode(u("ABCD\n")), "ABCD\n");
TU::test($e->encode(u("Euro: \\u20ac, Replacement char: \\ufffd")), "Euro: ?, Replacement char: ?");


// UTF-8:
// =====
$e = new EncodingGeneric("UTF-8", true, false);

TU::test($e->decode(NULL), NULL);
TU::test($e->decode(""), u(""));
TU::test($e->decode("ABCD\n"), u("ABCD\n"));
TU::test($e->decode("\xc2\xa1\xc3\xa8"), u("\\u00a1\\u00e8"));
TU::test($e->decode("\xc2"), u("\\ufffd")); // trunked sequence
TU::test($e->decode("\xc2\x00"), u("\\ufffd\x00")); // invalid continuation byte
TU::test($e->decode("\x80A"), u("\\ufffdA")); // invalid leading byte
TU::test($e->decode("\x80\x80A"), u("\\ufffd\\ufffdA")); // 2 consecutive invalid leading bytes
TU::test($e->decode("\xc1\x80A"), u("\\ufffd\\ufffdA")); // non-shortest 2 B encoding 1 ASCII char "@"
TU::test($e->decode("\xe0\x81\x80A"), u("\\ufffd\\ufffd\\ufffdA")); // non-shortest 3 B encoding 1 ASCII char "@"
TU::test($e->decode("\xe0\x83\xa8A"), u("\\ufffd\\ufffd\\ufffdA")); // non-shortest 3 B encoding ISO-8859-1 \xe8
TU::test($e->decode("\x80A"), u("\\ufffdA")); // re-sync
TU::test($e->decode("\xc1A"), u("\\ufffdA")); // re-sync
TU::test($e->decode("\xe0A"), u("\\ufffdA")); // re-sync
TU::test($e->decode("\xe0\x83A"), u("\\ufffd\\ufffdA")); // re-sync

TU::test($e->encode(NULL), NULL);
TU::test($e->encode(u("")), "");
TU::test($e->encode(u("ABCD\n")), "ABCD\n");
TU::test($e->encode(u("Euro: \\u20ac, Replacement char: \\ufffd")), "Euro: \xe2\x82\xac, Replacement char: \xef\xbf\xbd");

// THE END
