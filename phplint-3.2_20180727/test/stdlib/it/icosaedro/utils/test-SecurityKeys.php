<?php

namespace it\icosaedro\utils;

use Exception;
use RuntimeException;

require_once __DIR__ . "/../../../../../stdlib/all.php";

/**
 * @param string $block
 * @param boolean $encrypt
 */
function testEncode2($block, $encrypt)
{
	$encoded = SecurityKeys::encode($block, $encrypt);
	$decoded = SecurityKeys::decode($encoded, $encrypt);
	if( $decoded !== $block )
		throw new RuntimeException("failed encode/decode:\n"
			."exp: " . rawurlencode($block)
			."\ngot: " . rawurlencode($decoded)
		);
}

/**
 * @param string $block
 */
function testEncode($block)
{
	testEncode2($block, FALSE);
	testEncode2($block, TRUE);
}


/**
 * @throws Exception
 */
function main()
{
	testEncode("");
	testEncode("a");
	testEncode("ab");
	testEncode("abc");
	testEncode("abcd");
	testEncode("abcde");
	testEncode("abcdef");
	testEncode("abcdefg");
	testEncode("abcdefgh");
}

main();
