<?php
/** POSIX Functions.

See: {@link http://www.php.net/manual/en/ref.posix.php}
@package posix
*/

/*. if_php_ver_7 .*/
define("POSIX_RLIMIT_AS", 9);
define("POSIX_RLIMIT_CORE", 4);
define("POSIX_RLIMIT_CPU", 0);
define("POSIX_RLIMIT_DATA", 2);
define("POSIX_RLIMIT_FSIZE", 1);
define("POSIX_RLIMIT_INFINITY", -1);
define("POSIX_RLIMIT_LOCKS", 10);
define("POSIX_RLIMIT_MEMLOCK", 8);
define("POSIX_RLIMIT_MSGQUEUE", 12);
define("POSIX_RLIMIT_NICE", 13);
define("POSIX_RLIMIT_NOFILE", 7);
define("POSIX_RLIMIT_NPROC", 6);
define("POSIX_RLIMIT_RSS", 5);
define("POSIX_RLIMIT_RTPRIO", 14);
define("POSIX_RLIMIT_RTTIME", 15);
define("POSIX_RLIMIT_SIGPENDING", 11);
define("POSIX_RLIMIT_STACK", 3);

/*. bool .*/ function posix_setrlimit(/*. int .*/ $resource_, /*. int .*/ $softlimit, /*. int .*/ $hardlimit){}
/*. end_if_php_ver .*/

define("POSIX_F_OK", 0);
define("POSIX_R_OK", 4);
define("POSIX_S_IFBLK", 24576);
define("POSIX_S_IFCHR", 8192);
define("POSIX_S_IFIFO", 4096);
define("POSIX_S_IFREG", 32768);
define("POSIX_S_IFSOCK", 49152);
define("POSIX_W_OK", 2);
define("POSIX_X_OK", 1);

/*. bool .*/ function posix_kill(/*. int .*/ $pid, /*. int .*/ $sig){}
/*. int .*/ function posix_getpid(){}
/*. int .*/ function posix_getppid(){}
/*. int .*/ function posix_getuid(){}
/*. bool .*/ function posix_setuid(/*. int .*/ $uid){}
/*. int .*/ function posix_geteuid(){}
/*. bool .*/ function posix_seteuid(/*. int .*/ $uid){}
/*. int .*/ function posix_getgid(){}
/*. bool .*/ function posix_setgid(/*. int .*/ $uid){}
/*. int .*/ function posix_getegid(){}
/*. bool .*/ function posix_setegid(/*. int .*/ $uid){}
/*. array .*/ function posix_getgroups(){}
/*. string .*/ function posix_getlogin(){}
/*. int .*/ function posix_getpgrp(){}
/*. int .*/ function posix_setsid(){}
/*. bool .*/ function posix_setpgid(/*. int .*/ $pid, /*. int .*/ $pgid){}
/*. int .*/ function posix_getpgid(){}
/*. int .*/ function posix_getsid(){}
/*. array .*/ function posix_uname(){}
/*. array .*/ function posix_times(){}
/*. string .*/ function posix_ctermid(){}
/*. string .*/ function posix_ttyname(/*. int .*/ $fd){}
/*. bool .*/ function posix_isatty(/*. int .*/ $fd){}
/*. string .*/ function posix_getcwd(){}
/*. bool .*/ function posix_mkfifo(/*. string .*/ $pathname, /*. int .*/ $mode){}
/*. array .*/ function posix_getgrnam(/*. string .*/ $groupname){}
/*. array .*/ function posix_getgrgid(/*. int .*/ $gid){}
/*. array .*/ function posix_getpwnam(/*. string .*/ $groupname){}
/*. array .*/ function posix_getpwuid(/*. int .*/ $uid){}
/*. array .*/ function posix_getrlimit(){}
/*. int .*/ function posix_get_last_error(){}
/*. int .*/ function posix_errno(){}
/*. string .*/ function posix_strerror(/*. int .*/ $errno){}
/*. bool .*/ function posix_initgroups(/*. string .*/ $name, /*. int .*/ $base_group_id){}
