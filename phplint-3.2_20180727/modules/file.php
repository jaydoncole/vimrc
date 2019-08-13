<?php

/*
 * File access and logging.
 */

define('DEFAULT_INCLUDE_PATH', '.:');
define('DIRECTORY_SEPARATOR', '/');
define('FILE_APPEND', 8);
define('FILE_IGNORE_NEW_LINES', 2);
define('FILE_NO_DEFAULT_CONTEXT', 16);
define('FILE_SKIP_EMPTY_LINES', 4);
define('FILE_USE_INCLUDE_PATH', 1);
define('LOCK_EX', 2);
define('LOCK_NB', 4);
define('LOCK_SH', 1);
define('LOCK_UN', 3);
define('LOG_ALERT', 1);
define('LOG_AUTH', 32);
define('LOG_AUTHPRIV', 80);
define('LOG_CONS', 2);
define('LOG_CRIT', 2);
define('LOG_CRON', 72);
define('LOG_DAEMON', 24);
define('LOG_DEBUG', 7);
define('LOG_EMERG', 0);
define('LOG_ERR', 3);
define('LOG_INFO', 6);
define('LOG_KERN', 0);
define('LOG_LOCAL0', 128);
define('LOG_LOCAL1', 136);
define('LOG_LOCAL2', 144);
define('LOG_LOCAL3', 152);
define('LOG_LOCAL4', 160);
define('LOG_LOCAL5', 168);
define('LOG_LOCAL6', 176);
define('LOG_LOCAL7', 184);
define('LOG_LPR', 48);
define('LOG_MAIL', 16);
define('LOG_NDELAY', 8);
define('LOG_NEWS', 56);
define('LOG_NOTICE', 5);
define('LOG_NOWAIT', 16);
define('LOG_ODELAY', 4);
define('LOG_PERROR', 32);
define('LOG_PID', 1);
define('LOG_SYSLOG', 40);
define('LOG_USER', 8);
define('LOG_UUCP', 64);
define('LOG_WARNING', 4);
define('PATHINFO_BASENAME', 2);
define('PATHINFO_DIRNAME', 1);
define('PATHINFO_EXTENSION', 4);
define('PATH_SEPARATOR', ':');
define('SCANDIR_SORT_ASCENDING', 0);
define('SCANDIR_SORT_DESCENDING', 1);
define('SCANDIR_SORT_NONE', 2);
define('SEEK_CUR', 1);
define('SEEK_END', 2);
define('SEEK_SET', 0);
/*. array .*/ function realpath_cache_get(){}
/*. string.*/ function basename(/*. string .*/ $path, $suffix = ""){}
/*. string.*/ function dirname(/*. string .*/ $path
	/*. if_php_ver_7 .*/ , $levels = 1 /*. end_if_php_ver .*/ ){}
/*. array[string]string .*/ function pathinfo(/*. string .*/ $path /*., args .*/){}
/*. string.*/ function readlink(/*. string .*/ $path){}
/*. int   .*/ function linkinfo(/*. string .*/ $path){}
/*. bool  .*/ function symlink(/*. string .*/ $target, /*. string .*/ $link){}
/*. bool  .*/ function link(/*. string .*/ $target, /*. string .*/ $link){}
/*. bool  .*/ function unlink(/*. string .*/ $filename, /*. resource .*/ $context = NULL)
/*. triggers E_WARNING .*/{}
/*. int   .*/ function readfile(/*. string .*/ $filename, $use_include_path = false, /*. resource .*/ $context = NULL)
/*. triggers E_WARNING .*/{}
/*. bool  .*/ function rewind(/*. resource .*/ $handle){}
/*. bool  .*/ function rmdir(/*. string .*/ $dirname, /*. resource .*/ $context = NULL)
/*. triggers E_WARNING .*/{}
/*. int   .*/ function umask($mask = 0){}
/*. bool  .*/ function fclose(/*.resource.*/ $f)
/*. triggers E_WARNING .*/{}
/*. bool  .*/ function feof(/*. resource .*/ $f){}
/*. string.*/ function fgetc(/*. resource .*/ $h)
/*. triggers E_WARNING .*/{}
/*. string.*/ function fgets(/*. resource .*/ $f, $length = -1)
/*. triggers E_WARNING .*/{}
/*. string.*/ function fgetss(/*. resource .*/ $f, $length=-1, /*. string .*/ $allowable_tags=NULL)
/*. triggers E_WARNING .*/{}
/*. string.*/ function fread(/*.resource.*/ $f, /*.int.*/ $length)
/*. triggers E_WARNING .*/{}
/*.resource.*/function fopen(/*.string.*/ $filename, /*.string.*/ $mode, $use_include_path = false, /*. resource .*/ $context=NULL)
/*. triggers E_WARNING .*/{}
/*. int   .*/ function fpassthru(/*. resource .*/ $handle)
/*. triggers E_WARNING .*/{}
/*. bool  .*/ function ftruncate(/*. resource .*/ $handle, /*. int .*/ $size)
/*. triggers E_WARNING .*/{}
/*. array[string]int .*/ function fstat(/*. resource .*/ $handle)
/*. triggers E_WARNING .*/{}
/*. int   .*/ function fseek(/*. resource .*/ $handle, /*. int .*/ $offset, $whence = SEEK_SET)
/*. triggers E_WARNING .*/{}
/*. int   .*/ function ftell(/*. resource .*/ $handle)
/*. triggers E_WARNING .*/{}
/*. bool  .*/ function fflush(/*. resource .*/ $handle)
/*. triggers E_WARNING .*/{}
/*. int   .*/ function fwrite(/*. resource .*/ $handle, /*. string .*/ $s, $length =-1)
/*. triggers E_WARNING .*/{}
/*. int   .*/ function fputs(/*. resource .*/ $handle, /*. string .*/ $s, $length=-1)
/*. triggers E_WARNING .*/{}
/*. bool  .*/ function mkdir(/*. string .*/ $pathname, $mode = 0777, $recursive = false, /*. resource .*/ $context=NULL)
/*. triggers E_WARNING .*/{}
/*. bool  .*/ function rename(/*. string .*/ $oldname, /*. string .*/ $newname, /*. resource .*/ $context=NULL)
/*. triggers E_WARNING .*/{}
/*. bool  .*/ function copy(/*. string .*/ $source, /*. string .*/ $dest, /*. resource .*/ $context=NULL)
/*. triggers E_WARNING .*/{}
/*. string.*/ function tempnam(/*. string .*/ $dir, /*. string .*/ $prefix){}
/*. resource .*/ function tmpfile(){}
/*. array[int]string .*/ function file(/*. string .*/ $filename, $flags=0, /*. resource .*/ $context=NULL)
/*. triggers E_WARNING .*/{}
/*. string.*/ function file_get_contents(/*.string.*/ $fn, $use_include_path = false, /*.resource.*/ $context=NULL, $offset = 0, $maxlen=-1)
/*. triggers E_WARNING .*/{}
/*. int   .*/ function file_put_contents(/*.string.*/ $fn, /*.string.*/ $data, $flags = 0, /*.resource.*/ $context=NULL)
/*. triggers E_WARNING .*/{}
/*. array[int]string .*/ function fgetcsv(/*. resource .*/ $handle, $length = 0, $delimiter = ",", $enclosure = '"', $escape = "\\")
/*. triggers E_WARNING .*/{}
/*. bool  .*/ function flock(/*. resource .*/ $handle, /*. int .*/ $op, /*. return int .*/ &$wouldblock=0)
/*. triggers E_WARNING .*/{}
/*. int   .*/ function set_file_buffer(/*. resource .*/ $stream, /*. int .*/ $buffer){}
/*. string.*/ function realpath(/*. string .*/ $path){}
/*. resource .*/ function opendir(/*. string .*/ $path, /*. resource .*/ $context=NULL)
/*. triggers E_WARNING .*/{}
/*. void  .*/ function closedir(/*. resource .*/ $dirhandle){}
/*. bool  .*/ function chdir(/*. string .*/ $dir)
/*. triggers E_WARNING .*/{}
/*. bool  .*/ function chroot(/*. string .*/ $dir)
/*. triggers E_WARNING .*/{}
/*. string.*/ function getcwd()/*. triggers E_WARNING .*/{}
/*. void  .*/ function rewinddir(/*. resource .*/ $dir_handle)
/*. triggers E_WARNING .*/{}
/*. string.*/ function readdir(/*. resource .*/ $dir_handle)
/*. triggers E_WARNING .*/{}

class Directory {
	public /*. string .*/ $path;
	public /*. resource .*/ $handle;
	public /*. string .*/ function read(){}
	public /*. void .*/ function rewind(){}
	public /*. void .*/ function close(){}
}

/*. Directory .*/ function dir(/*. string .*/ $directory, /*. resource .*/ $context=NULL)
/*. triggers E_WARNING .*/{}
/*. array[int]string .*/ function scandir(/*. string .*/ $dir, $sorting_order = SCANDIR_SORT_ASCENDING, /*.resource.*/ $context=NULL)
/*. triggers E_WARNING .*/{}
/*. array[int]string .*/ function glob(/*. string .*/ $pattern, $flags = 0){}
/*. int   .*/ function fileatime(/*. string .*/ $fn)
/*. triggers E_WARNING .*/{}
/*. int   .*/ function filectime(/*. string .*/ $fn)
/*. triggers E_WARNING .*/{}
/*. int   .*/ function filegroup(/*. string .*/ $fn)
/*. triggers E_WARNING .*/{}
/*. int   .*/ function fileinode(/*. string .*/ $fn)
/*. triggers E_WARNING .*/{}
/*. int   .*/ function filemtime(/*. string .*/ $fn)
/*. triggers E_WARNING .*/{}
/*. int   .*/ function fileowner(/*. string .*/ $fn)
/*. triggers E_WARNING .*/{}
/*. int   .*/ function fileperms(/*. string .*/ $fn)
/*. triggers E_WARNING .*/{}
/*. int   .*/ function filesize(/*. string .*/ $filename)
/*. triggers E_WARNING .*/{}
/*. string.*/ function filetype(/*. string .*/ $fn)
/*. triggers E_WARNING .*/{}
/*. bool  .*/ function file_exists(/*.string.*/ $fn){}
/*. bool  .*/ function is_writable(/*. string .*/ $fn){}
/*. bool  .*/ function is_writeable(/*. string .*/ $fn){}
/*. bool  .*/ function is_readable(/*. string .*/ $fn){}
/*. bool  .*/ function is_executable(/*. string .*/ $fn){}
/*. bool  .*/ function is_file(/*. string .*/ $fn){}
/*. bool  .*/ function is_dir(/*. string .*/ $fn){}
/*. bool  .*/ function is_link(/*. string .*/ $fn){}
/*. array[]int .*/ function stat(/*. string .*/ $fn)
/*. triggers E_WARNING .*/{}
/*. array[]int .*/ function lstat(/*. string .*/ $fn)
/*. triggers E_WARNING .*/{}
/*. bool  .*/ function chown(/*. string .*/ $fn, /*. mixed .*/ $user)
/*. triggers E_WARNING .*/{}
/*. bool  .*/ function chgrp(/*. string .*/ $fn, /*. mixed .*/ $group)
/*. triggers E_WARNING .*/{}
/*. bool  .*/ function chmod(/*. string .*/ $fn, /*. int .*/ $mode)
/*. triggers E_WARNING .*/{}
/*. bool  .*/ function touch(/*. string .*/ $fn, $time = -1, $atime = -1)
/*. triggers E_WARNING .*/{}
/*. void  .*/ function clearstatcache($clear_realpath_cache = false, $filename = ""){}
/*. float .*/ function disk_total_space(/*. string .*/ $dir)
/*. triggers E_WARNING .*/{}
/*. float .*/ function disk_free_space(/*. string .*/ $dir)
/*. triggers E_WARNING .*/{}
/*. float .*/ function diskfreespace(/*. string .*/ $dir)
/*. triggers E_WARNING .*/{}
/*. bool  .*/ function openlog(/*. string .*/ $ident, /*. int .*/ $option, /*. int .*/ $facility){}
/*. bool  .*/ function syslog(/*.int.*/ $priority, /*.string.*/ $msg){}
/*. bool  .*/ function closelog(){}
/*. int   .*/ function fprintf(/*.resource.*/ $f, /*.string.*/ $fmt /*., args .*/){}
/*. int   .*/ function vfprintf(/*. resource .*/ $handle, /*. string .*/ $format, /*. array .*/ $args_){}
/*. mixed .*/ function fscanf(/*. resource .*/ $handle, /*. string .*/ $format /*., args .*/){}
/*. int   .*/ function version_compare(/*. string .*/ $ver1, /*. string .*/ $ver2, $operator=""){}