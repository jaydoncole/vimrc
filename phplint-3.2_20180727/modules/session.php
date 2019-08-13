<?php
/** Session Handling Functions.

See: {@link http://www.php.net/manual/en/ref.session.php}
@package session
*/

/*. require_module 'core'; .*/


define('SID', '?');

/*. string.*/ function session_name(/*. args .*/){}
/*. string.*/ function session_module_name(/*. args .*/){}
/*. string.*/ function session_save_path(/*. args .*/){}
/*. string.*/ function session_id(/*. args .*/){}
/*. bool .*/ function session_regenerate_id(){}
/*. bool .*/ function session_decode(/*. string .*/ $data){}

/*. string .*/ function session_encode(){}
/*. bool  .*/ function session_start(
	/*. if_php_ver_7 .*/
		/*. mixed[string] .*/ $options = NULL
	/*. end_if_php_ver .*/
){}
/*. bool  .*/ function session_destroy(){}
/*. void  .*/ function session_unset(){}
/*. void .*/ function session_set_save_handler(/*. args .*/){}
/*. string .*/ function session_cache_limiter( /*. args .*/){}
/*. int .*/ function session_cache_expire( /*. args .*/){}
/*. void .*/ function session_set_cookie_params(/*. int .*/ $lifetime /*., args .*/){}
/*. mixed[string] .*/ function session_get_cookie_params(){}
/*. void  .*/ function session_write_close(){}
/*. void  .*/ function session_commit(){}


define('PHP_SESSION_DISABLED', 1);
define('PHP_SESSION_NONE', 1);
define('PHP_SESSION_ACTIVE', 1);
/*. int .*/ function session_status(){}
/*. void .*/ function session_register_shutdown()/*. triggers E_WARNING .*/{}

/**
 * FIXME: it seems that all these methods may trigger error, and
 * this should be declared in some way in the interface.
 */
interface SessionHandlerInterface {
	public /*. bool .*/ function close();
	public /*. bool .*/ function destroy(/*. string .*/ $session_id);
	public /*. bool .*/ function gc(/*. int .*/ $maxlifetime);
	public /*. bool .*/ function open(/*. string .*/ $save_path, /*. string .*/ $name);
	public /*. string .*/ function read(/*. string .*/ $session_id);
	public /*. bool .*/ function write(/*. string .*/ $session_id, /*. string .*/ $session_data);
}


class SessionHandler implements SessionHandlerInterface {
	public /*. bool .*/ function close(){}
	public /*. bool .*/ function destroy(/*. string .*/ $session_id){}
	public /*. bool .*/ function gc(/*. int .*/ $maxlifetime){}
	public /*. bool .*/ function open(/*. string .*/ $save_path, /*. string .*/ $session_id){}
	public /*. string .*/ function read(/*. string .*/ $session_id){}
	public /*. bool .*/ function write(/*. string .*/ $session_id, /*. string .*/ $session_data){}
}
