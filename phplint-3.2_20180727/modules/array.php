<?php

/*
 * Array functions.
 * Note that array_key_exists() is still in the core module as it is assumed to
 * be a very basic low-level function programs cannot make without; all the
 * utility functions defined here, instead, can also be reproduced in user's code.
 */

/*. require_module 'core'; .*/

define('ARRAY_FILTER_USE_BOTH', 1);
define('ARRAY_FILTER_USE_KEY', 2);
define('SORT_ASC', 4);
define('SORT_DESC', 3);
define('SORT_FLAG_CASE', 8);
define('SORT_LOCALE_STRING', 5);
define('SORT_NATURAL', 6);
define('SORT_NUMERIC', 1);
define('SORT_REGULAR', 0);
define('SORT_STRING', 2);

/*. bool  .*/ function ksort(/*. array .*/ $array_arg, $sort_flags = SORT_REGULAR){}
/*. bool  .*/ function krsort(/*. array .*/ $array_arg, $sort_flags = SORT_REGULAR){}
/*. void  .*/ function natsort(/*. array .*/ $array_arg){}
/*. void  .*/ function natcasesort(/*. array .*/ $array_arg){}
/*. bool  .*/ function asort(/*. array .*/ $a, $sort_flags = SORT_REGULAR){}
/*. bool  .*/ function arsort(/*. array .*/ $a, $sort_flags = SORT_REGULAR){}
/**
	Warning: indexes must really be int.
	That is because in any case this function changes the type of the array
	into array[int].
*/
/*. bool  .*/ function sort(/*. array[int] .*/ $a, $sort_flags = SORT_REGULAR){}

/**
	Warning: indexes must really be int.
	That is because in any case this function changes the type of the array
	into array[int].
*/
/*. bool  .*/ function rsort(/*. array[int] .*/ $a,  $sort_flags = SORT_REGULAR){}

/**
	Warning: indexes must really be int.
	That is because in any case this function changes the type of the array
	into array[int].
*/
/*. bool  .*/ function usort(/*. array[int] .*/ $a, /*. mixed .*/ $cmp_func)
{}
/*. bool  .*/ function uasort(/*. array .*/ $a, /*. mixed .*/ $cmp_func){}
/*. bool  .*/ function uksort(/*. array .*/ $a, /*. mixed .*/ $cmp_func){}
/*. bool  .*/ function shuffle(/*. array .*/ $array_arg){}
/*. bool  .*/ function array_walk(/*. array .*/ & $input, /*. mixed .*/ $callback, /*. mixed .*/ $userdata = NULL){}
/*. bool  .*/ function array_walk_recursive(/*. array .*/ $input, /*. mixed .*/ $callback, /*. mixed .*/ $userdata = NULL){}
/*. mixed .*/ function array_search(/*. mixed .*/ $needle, /*. array .*/ $hatstack, $strict = false){}
/*. array  .*/ function array_change_key_case(/*. array .*/ $a, $case_ = CASE_LOWER){}
/*. array[int][]mixed .*/ function array_chunk(/*. array .*/ $a, /*. int .*/ $size, $preserve_keys = FALSE)
	/*. triggers E_WARNING .*/{}
/*. array .*/ function  array_column(/*. array .*/ $input, /*. mixed .*/ $column_key, /*. mixed .*/ $index_key = null){}
/*. array .*/ function array_combine(/*. array .*/ $keys, /*. array .*/ $values)
	/*. triggers E_WARNING .*/{}
/*. array .*/ function array_count_values(/*. array .*/ $input)
	/*. triggers E_WARNING .*/{}
/*. array .*/ function array_diff(/*. array .*/ $arr1, /*. array .*/ $arr2 /*., args .*/){}
/*. array .*/ function array_diff_key(/*. array .*/ $arr1, /*. array .*/ $arr2 /*., args .*/){}
/*. array .*/ function array_diff_assoc(/*. array .*/ $arr1, /*. array .*/ $arr2 /*., args .*/){}
/*. array .*/ function array_diff_uassoc(/*. array .*/ $arr1, /*. array .*/ $arr2 /*., args .*/){}
/*. array .*/ function array_fill(/*. int .*/ $start_key, /*. int .*/ $num, /*. mixed .*/ $val)
	/*. triggers E_WARNING .*/{}
/*. array .*/ function array_filter(/*. array .*/ $input, /*. mixed .*/ $callback, $flag = 0){}
/*. array .*/ function array_flip(/*. array .*/ $input){}
/*. array .*/ function array_intersect(/*. array .*/ $arr1, /*. array .*/ $arr2 /*., args .*/){}
/*. array .*/ function array_intersect_assoc(/*. array .*/ $arr1, /*. array .*/ $arr2 /*., args .*/){}
/*. array .*/ function array_intersect_uassoc(/*. array .*/ $arr1, /*. array .*/ $arr2 /*., args .*/){}
/*. array[int]mixed .*/ function array_keys(/*. array .*/ $input, /*. mixed .*/ $search_value = null, $strict = false){}
/*. array .*/ function array_map(/*. mixed .*/ $callback, /*. array .*/ $a1 /*., args .*/){}
/*. array .*/ function array_merge(/*. array .*/ $a1, /*. array .*/ $a2 /*., args .*/){}
/*. array .*/ function array_merge_recursive(/*. array .*/ $arr1, /*. array .*/ $arr2 /*., args .*/){}
/*. bool  .*/ function array_multisort(/*. array .*/ $ar1 /*., args .*/){}
/*. array .*/ function array_pad(/*. array .*/ $input, /*. int .*/ $pad_size, /*. mixed .*/ $pad_value){}
/*. mixed .*/ function array_pop(/*. array .*/ $stack){}
/*. int   .*/ function array_push(/*. array .*/ $stack, /*. mixed .*/ $var_ /*., args .*/){}
/*. mixed .*/ function array_rand(/*. array .*/ $input, $num = 1){}
/*. mixed .*/ function array_reduce(/*. array .*/ $input, /*. mixed .*/ $callback, /*. mixed .*/ $initial = NULL){}
/*. array .*/ function array_reverse(/*. array .*/ $input, $preserve_keys = false){}
/*. mixed .*/ function array_shift(/*. array .*/ $a){}
/*. array .*/ function array_slice(/*. array .*/ $input, /*. int .*/ $offset, $length = 0, $preserve_keys = false){}
/*. array .*/ function array_splice(/*. array .*/ $input, /*. int .*/ $offset, $length = 0, $replacement = array()){}
/*. float .*/ function array_sum(/*. array .*/ $input){}
/*. array .*/ function array_udiff(/*. array .*/ $arr1, /*. array .*/ $arr2 /*., args .*/){}
/*. array .*/ function array_udiff_assoc(/*. array .*/ $arr1, /*. array .*/ $arr2 /*., args .*/){}
/*. array .*/ function array_udiff_uassoc(/*. array .*/ $arr1, /*. array .*/ $arr2 /*., args .*/){}
/*. array .*/ function array_uintersect(/*. array .*/ $arr1, /*. array .*/ $arr2 /*., args .*/){}
/*. array .*/ function array_uintersect_assoc(/*. array .*/ $arr1, /*. array .*/ $arr2 /*., args .*/){}
/*. array .*/ function array_uintersect_uassoc(/*. array .*/ $arr1, /*. array .*/ $arr2 /*., args .*/){}
/*. array .*/ function array_unique(/*. array .*/ $input){}
/*. int   .*/ function array_unshift(/*. array .*/ $stack, /*. mixed .*/ $var_ /*., args .*/){}
/*. array .*/ function array_values(/*. array .*/ $input){}
/*. array .*/ function array_fill_keys(/*. array .*/ $keys, /*. mixed .*/ $value){}
/*. array .*/ function array_intersect_key(/*. array .*/ $array1, /*. array .*/ $array2 /*., args .*/){}
/*. array .*/ function array_intersect_ukey(/*. array .*/ $array1, /*. array .*/ $array2 /*., args .*/){}
/*. array .*/ function array_diff_ukey(/*. array .*/ $array1, /*. array .*/ $array2 /*., args .*/){}
/*. float .*/ function array_product(/*. array .*/ $array_){}
/*. array .*/ function array_replace(/*. array .*/ $arr, /*. array .*/ $arr1 /*., args .*/){}
/*. array .*/ function array_replace_recursive(/*. array .*/ $arr, /*. array .*/ $arr1 /*., args .*/){}
/*. array .*/ function range(/*. mixed .*/ $low, /*. mixed .*/ $high /*., args .*/){}