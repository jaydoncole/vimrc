<?php
/** FTP Functions.

See: {@link http://www.php.net/manual/en/ref.ftp.php}
@package ftp
*/

/*. require_module 'core'; .*/

# These values are all dummy:
define('FTP_ASCII', 1);
define('FTP_TEXT', 2);
define('FTP_BINARY', 3);
define('FTP_IMAGE', 4);
define('FTP_AUTORESUME', 5);
define('FTP_TIMEOUT_SEC', 6);
define('FTP_AUTOSEEK', 7);
define('FTP_FAILED', 8);
define('FTP_FINISHED', 9);
define('FTP_MOREDATA', 10);

/*. resource .*/ function ftp_connect(/*. string .*/ $host /*., args .*/){}
/*. resource .*/ function ftp_ssl_connect(/*. string .*/ $host /*., args .*/){}
/*. bool .*/ function ftp_login(/*. resource .*/ $stream, /*. string .*/ $username, /*. string .*/ $password)
	/*. triggers E_WARNING .*/{}
/*. string .*/ function ftp_pwd(/*. resource .*/ $stream){}
/*. bool .*/ function ftp_cdup(/*. resource .*/ $stream){}
/*. bool .*/ function ftp_chdir(/*. resource .*/ $stream, /*. string .*/ $directory)
	/*. triggers E_WARNING .*/{}
/*. bool .*/ function ftp_exec(/*. resource .*/ $stream, /*. string .*/ $command){}
/*. array .*/ function ftp_raw(/*. resource .*/ $stream, /*. string .*/ $command){}
/*. string .*/ function ftp_mkdir(/*. resource .*/ $stream, /*. string .*/ $directory){}
/*. bool .*/ function ftp_rmdir(/*. resource .*/ $stream, /*. string .*/ $directory){}
/*. int .*/ function ftp_chmod(/*. resource .*/ $stream, /*. int .*/ $mode, /*. string .*/ $filename){}
/*. bool .*/ function ftp_alloc(/*. resource .*/ $stream, /*. int .*/ $size /*., args .*/){}
/*. array .*/ function ftp_nlist(/*. resource .*/ $stream, /*. string .*/ $directory){}
/*. array .*/ function ftp_rawlist(/*. resource .*/ $stream, /*. string .*/ $directory /*., args .*/){}
/*. string .*/ function ftp_systype(/*. resource .*/ $stream){}
/*. bool .*/ function ftp_fget(/*. resource .*/ $stream, /*. resource .*/ $fp, /*. string .*/ $remote_file, /*. int .*/ $mode /*., args .*/){}
/*. int .*/ function ftp_nb_fget(/*. resource .*/ $stream, /*. resource .*/ $fp, /*. string .*/ $remote_file, /*. int .*/ $mode /*., args .*/){}
/*. bool .*/ function ftp_pasv(/*. resource .*/ $stream, /*. bool .*/ $pasv){}
/*. bool .*/ function ftp_get(/*. resource .*/ $stream, /*. string .*/ $local_file, /*. string .*/ $remote_file, /*. int .*/ $mode /*., args .*/){}
/*. int .*/ function ftp_nb_get(/*. resource .*/ $stream, /*. string .*/ $local_file, /*. string .*/ $remote_file, /*. int .*/ $mode /*., args .*/){}
/*. int .*/ function ftp_nb_continue(/*. resource .*/ $stream){}
/*. bool .*/ function ftp_fput(/*. resource .*/ $stream, /*. string .*/ $remote_file, /*. resource .*/ $fp, /*. int .*/ $mode /*., args .*/){}
/*. int .*/ function ftp_nb_fput(/*. resource .*/ $stream, /*. string .*/ $remote_file, /*. resource .*/ $fp, /*. int .*/ $mode /*., args .*/){}
/*. bool .*/ function ftp_put(/*. resource .*/ $stream, /*. string .*/ $remote_file, /*. string .*/ $local_file, /*. int .*/ $mode /*., args .*/){}
/*. int .*/ function ftp_nb_put(/*. resource .*/ $stream, /*. string .*/ $remote_file, /*. string .*/ $local_file, /*. int .*/ $mode /*., args .*/){}
/*. int .*/ function ftp_size(/*. resource .*/ $stream, /*. string .*/ $filename){}
/*. int .*/ function ftp_mdtm(/*. resource .*/ $stream, /*. string .*/ $filename){}
/*. bool .*/ function ftp_rename(/*. resource .*/ $stream, /*. string .*/ $src, /*. string .*/ $dest){}
/*. bool .*/ function ftp_delete(/*. resource .*/ $stream, /*. string .*/ $file){}
/*. bool .*/ function ftp_site(/*. resource .*/ $stream, /*. string .*/ $cmd){}
/*. bool .*/ function ftp_close(/*. resource .*/ $stream){}
/*. bool .*/ function ftp_set_option(/*. resource .*/ $stream, /*. int .*/ $option, /*. mixed .*/ $value){}
/*. mixed .*/ function ftp_get_option(/*. resource .*/ $stream, /*. int .*/ $option)
	/*. triggers E_WARNING .*/{}
/*. bool .*/ function ftp_append(/*. resource .*/ $stream, /*. string .*/ $remote_file , /*. string .*/ $local_file, $mode = FTP_IMAGE){}