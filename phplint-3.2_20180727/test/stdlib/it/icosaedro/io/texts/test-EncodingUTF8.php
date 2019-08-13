<?php

require_once __DIR__ . "/../../../../../../stdlib/all.php";
require_once __DIR__ . "/../../../../../../stdlib/utf8.php";

use it\icosaedro\io\texts\EncodingUTF8;
use it\icosaedro\utils\TestUnit as TU;

$e = new EncodingUTF8();

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
