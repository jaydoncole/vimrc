<?php

require_once __DIR__ . "/../../../../../stdlib/all.php";
use it\icosaedro\web\StoredFile;

StoredFile::$STORE = __DIR__ . "/store";

if( !file_exists(StoredFile::$STORE) )
	mkdir(StoredFile::$STORE);

for($j = 1; $j > 0; $j--) {
	$err = StoredFile::report();
	if( $err > 0 ){
		echo "You should fix these errors before continuing.\n";
		exit(1);
	}
	for($i = 10; $i > 0; $i--){
		$sf = StoredFile::fromData("abcd $i ".time(), "text/plain");
		// only some files confirmed, the other assumed temp.
//		if(rand(0, 5) == 1)
//			$sf->setConfirmed();
	}
	sleep(1);
	StoredFile::deleteStale(5);
}
$err = StoredFile::report();
exit($err == 0? 0 : 1);
