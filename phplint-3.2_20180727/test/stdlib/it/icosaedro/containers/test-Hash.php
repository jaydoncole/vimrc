<?php

use it\icosaedro\containers\Hash;
use it\icosaedro\utils\Histogram;

require_once __DIR__ . "/../../../../../stdlib/all.php";

function main() {
	
	// Generate hash values from (not so) random strings then translate these
	// hash to indeces in the range [0,$n[ of some hash table.
	// Ideally, these indeces will the uniformly distributed over the range of
	// the hash table.
	
	// Size of the hash table:
	$table_size = 100;
	// Number of samples:
	$no_samples = 1000;
	$h = new Histogram(0.0, $table_size, 10);
	for($i = 0; $i < $no_samples; $i++){
		$hash = 17;
		$hash = Hash::combine($hash, Hash::hashOfInt($i));
		$s = "$i"; // not-so-random random string
		$hash = Hash::combine($hash, Hash::hashOfString($s));
		$index = Hash::getIndex($hash, $table_size);
		$h->put($index);
	}
//	echo $h;
	// The mean index should be $table_size/2 within 2%:
	$mean = $h->mean();
	if( abs($mean/$table_size - 0.5) > 0.02 )
		throw new RuntimeException("invalid mean:\n$h");
	// The standard deviation should be $table_size/sqrt(12) within 10%:
	$deviation = $h->deviation();
	if( abs($deviation/$table_size - sqrt(1/12)) > 0.1 )
		throw new RuntimeException("invalid deviation:\n$h");
}


main();
