<?php

/*
 * Language and locale functions.
 */

define('ABDAY_1', 131072);
define('ABDAY_2', 131073);
define('ABDAY_3', 131074);
define('ABDAY_4', 131075);
define('ABDAY_5', 131076);
define('ABDAY_6', 131077);
define('ABDAY_7', 131078);
define('ABMON_1', 131086);
define('ABMON_10', 131095);
define('ABMON_11', 131096);
define('ABMON_12', 131097);
define('ABMON_2', 131087);
define('ABMON_3', 131088);
define('ABMON_4', 131089);
define('ABMON_5', 131090);
define('ABMON_6', 131091);
define('ABMON_7', 131092);
define('ABMON_8', 131093);
define('ABMON_9', 131094);
define('CRNCYSTR', 262159);
define('LC_ALL', 6);
define('LC_COLLATE', 3);
define('LC_CTYPE', 0);
define('LC_MESSAGES', 5);
define('LC_MONETARY', 4);
define('LC_NUMERIC', 1);
define('LC_TIME', 2);

/*. string.*/ function nl_langinfo(/*. int .*/ $item){}
/*. string.*/ function setlocale(/*. int .*/ $category, /*. mixed .*/ $locale /*., args .*/){}
/*. array[string]mixed .*/ function localeconv(){}
/*. array[]int .*/ function localtime($timestamp=0, $is_associative=FALSE)
/*. triggers E_NOTICE, E_WARNING .*/{}
/*. string.*/ function soundex(/*. string .*/ $str){}
/*. int   .*/ function levenshtein(/*. string .*/ $str1, /*. string .*/ $str2 /*., args .*/){}
/*. string.*/ function convert_cyr_string(/*. string .*/ $str, /*. string .*/ $from, /*. string .*/ $to){}
/*. string.*/ function metaphone(/*. string .*/ $str /*., args .*/){}
/*. string .*/ function hebrev(/*. string .*/ $str, $max_chars_per_line = 0){}
/*. string .*/ function hebrevc(/*. string .*/ $str, $max_chars_per_line = 0){}