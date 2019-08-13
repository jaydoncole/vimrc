<?php
/**
Apache-specific Functions.

See: {@link http://www.php.net/manual/en/ref.apache.php}
@package apache
*/

# required for E_WARNING:
/*. require_module 'core'; .*/


/*. bool .*/ function apache_child_terminate()/*. triggers E_WARNING .*/{}
/*. string[int] .*/ function apache_get_modules(){}
/*. string .*/ function apache_get_version(){}
/*. bool .*/ function apache_getenv(/*. string .*/ $variable, $walk_to_top = false){}
/*. object .*/ function apache_lookup_uri(/*. string .*/ $URI){}
/*. string .*/ function apache_note(/*. string .*/ $note_name, $note_value = ""){}
/*. string[string] .*/ function apache_request_headers(){}
/*. bool .*/ function apache_reset_timeout(){}
/*. string[string] .*/ function apache_response_headers(){}
/*. bool .*/ function apache_setenv(/*. string .*/ $variable, /*. string .*/ $value, $walk_to_top = false){}
/*. string[string] .*/ function getallheaders(){}
/*. bool .*/ function virtual(/*. string .*/ $filename){}
