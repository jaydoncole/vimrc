<?php

require_once __DIR__ . "/../../../../../stdlib/all.php";

use it\icosaedro\web\AcceptLanguage as AL;
use it\icosaedro\utils\TestUnit as TU;
use it\icosaedro\containers\Arrays;

TU::test(Arrays::implode(AL::parse(""), ", "), "");
TU::test(Arrays::implode(AL::parse("en"), ", "), "en;q=1");
TU::test(Arrays::implode(AL::parse("en, it"), ", "), "en;q=1, it;q=1");
TU::test(Arrays::implode(AL::parse("en;q=0.5,it"), ", "), "en;q=0.5, it;q=1");
TU::test(Arrays::implode(AL::parse("en-GB,en-US"), ", "), "en-gb;q=1, en-us;q=1");
TU::test(Arrays::implode(AL::parse("zh-cmn-xxx-yyy-Hans-CN"), ", "), "zh-cmn-xxx-yyy-hans-cn;q=1");

$preferred = AL::parse("");
$supported = AL::parse("en");
TU::test("".AL::bestSupportedLanguage($preferred, $supported), "en;q=1");

$preferred = AL::parse("en");
$supported = AL::parse("en");
TU::test("".AL::bestSupportedLanguage($preferred, $supported), "en;q=1");

$preferred = AL::parse("en,it");
$supported = AL::parse("en");
TU::test("".AL::bestSupportedLanguage($preferred, $supported), "en;q=1");

$preferred = AL::parse("it,en");
$supported = AL::parse("en");
TU::test("".AL::bestSupportedLanguage($preferred, $supported), "en;q=1");

$preferred = AL::parse("en,en-GB");
$supported = AL::parse("en");
TU::test("".AL::bestSupportedLanguage($preferred, $supported), "en;q=1");

$preferred = AL::parse("en-GB,en");
$supported = AL::parse("en");
TU::test("".AL::bestSupportedLanguage($preferred, $supported), "en;q=1");



/*
 * Case 1: server with support for a single language.
 */

$preferred = AL::parse("");
$supported = AL::parse("en");
TU::test("".AL::bestSupportedLanguage($preferred, $supported), "en;q=1");

$preferred = AL::parse("en");
$supported = AL::parse("en");
TU::test("".AL::bestSupportedLanguage($preferred, $supported), "en;q=1");

$preferred = AL::parse("it");
$supported = AL::parse("en");
TU::test("".AL::bestSupportedLanguage($preferred, $supported), "en;q=1");

$preferred = AL::parse("en,it");
$supported = AL::parse("en");
TU::test("".AL::bestSupportedLanguage($preferred, $supported), "en;q=1");

$preferred = AL::parse("it,en");
$supported = AL::parse("en");
TU::test("".AL::bestSupportedLanguage($preferred, $supported), "en;q=1");

$preferred = AL::parse("en,en-GB");
$supported = AL::parse("en");
TU::test("".AL::bestSupportedLanguage($preferred, $supported), "en;q=1");

$preferred = AL::parse("en-GB,en");
$supported = AL::parse("en");
TU::test("".AL::bestSupportedLanguage($preferred, $supported), "en;q=1");


/*
 * Case 2: server with support for 2 languages:
 */

$preferred = AL::parse("");
$supported = AL::parse("en, it");
TU::test("".AL::bestSupportedLanguage($preferred, $supported), "en;q=1");

$preferred = AL::parse("en");
$supported = AL::parse("en, it");
TU::test("".AL::bestSupportedLanguage($preferred, $supported), "en;q=1");

$preferred = AL::parse("it");
$supported = AL::parse("en, it");
TU::test("".AL::bestSupportedLanguage($preferred, $supported), "it;q=1");

// Ambiguous:
$preferred = AL::parse("en, it");
$supported = AL::parse("en, it");
TU::test("".AL::bestSupportedLanguage($preferred, $supported), "en;q=1");

$preferred = AL::parse("it, en");
$supported = AL::parse("en, it");
TU::test("".AL::bestSupportedLanguage($preferred, $supported), "en;q=1");

$preferred = AL::parse("en, en-GB, en-US");
$supported = AL::parse("en, en-GB");
TU::test("".AL::bestSupportedLanguage($preferred, $supported), "en-gb;q=1");

$preferred = AL::parse("en, en-US, en-GB");
$supported = AL::parse("en-GB, en");
TU::test("".AL::bestSupportedLanguage($preferred, $supported), "en-gb;q=1");
