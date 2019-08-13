TAR
===

TODO
----
- BUG: the UStar version '00' is not set.
- Reader: set mtime(), uid, gid, uname, gname.
- Reader: utility to detect compression method and apply de-compressor.



Pre-POSIX.1-1988 tar header:
Offset Size Description
------ ---- -----------
  0     100   File name
100       8   File mode
108       8   Owner's numeric user ID
116       8   Group's numeric user ID
124      12   File size in bytes (octal base)
136      12   Last modification time in numeric Unix time format (octal)
148       8   Checksum for header record
156       1   Link indicator (file type)
157     100   Name of linked file
       ----
        257

UStar tar header:
Offset Size Description
------ ---- -----------
  0     156   (as in old format)
156       1   Type flag
157     100   (as in old format)
257       6   UStar indicator "ustar"
263       2   UStar version "00" (GNU tar always set 2 spaces here for any type of entry)
265      32   Owner user name
297      32   Owner group name
329       8   Device major number
337       8   Device minor number
345     155   Filename prefix
       ----
	    500
