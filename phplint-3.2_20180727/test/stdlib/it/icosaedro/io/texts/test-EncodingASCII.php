<?php

require_once __DIR__ . "/../../../../../../stdlib/all.php";
require_once __DIR__ . "/../../../../../../stdlib/utf8.php";

use it\icosaedro\io\texts\EncodingASCII;
use it\icosaedro\utils\TestUnit as TU;

$e = new EncodingASCII();

TU::test($e->decode(NULL), NULL);
TU::test($e->decode(""), u(""));
TU::test($e->decode("Hello, world!\n"), u("Hello, world!\n"));
TU::test($e->decode("\xff\xffABC"), u("\\ufffd\\ufffdABC"));
TU::test($e->decode("\x00\x7f\x80\xff"), u("\x00\x7f\\ufffd\\ufffd"));

TU::test($e->encode(NULL), NULL);
TU::test($e->encode(u("")), "");
TU::test($e->encode(u("ABCD\n")), "ABCD\n");
TU::test($e->encode(u("Euro: \\u20ac, Replacement char: \\ufffd")), "Euro: ?, Replacement char: ?");
