<?php
/** WDDX Functions.

See: {@link http://www.php.net/manual/en/ref.wddx.php}
@package wddx
*/



/*. string .*/ function wddx_serialize_value(/*. mixed .*/ $var_ /*., args .*/){}
/*. string .*/ function wddx_serialize_vars(/*. mixed .*/ $var_name /*., args .*/){}
/*. int .*/ function wddx_packet_start( /*. args .*/){}
/*. string .*/ function wddx_packet_end(/*. int .*/ $packet_id){}
/*. int .*/ function wddx_add_vars(/*. int .*/ $packet_id, /*. mixed .*/ $var_names /*., args .*/){}
/*. mixed .*/ function wddx_deserialize(/*. mixed .*/ $packet){}
