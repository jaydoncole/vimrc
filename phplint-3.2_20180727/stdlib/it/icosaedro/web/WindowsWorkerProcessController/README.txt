This directory contains tools required by the it\icosaedro\web\OfflineJob class:
a worker process controller (required under Windows) and a test program
(required only for testing). More in detail:


Makefile
--------
"make" file generated automatically by the make-makefile tool available
from

    http://cvs.icosaedro.it:8080/viewvc/public/tools


workerProcessController.c
-------------------------
Required only to launch and control background processes under Windows.
Can be compiled into the workerProcessController.exe executable program using
the MinGW development system under Windows. Type

    make workerProcessController.exe

or simply

    make

to generate all. The OfflineJob class expects that executable be exactly under
this directory. For a detailed list of its features, type:

    workerProcessController.exe --help

With the help of this program, background processes can be launched under
Windows, their stdin, stdout and stderr can be redirected to files, and the
exit status code of the worker process can be captured and saved. Apparently
there is not a simpler way to do all that by using only the tools provided by
PHP and Windows alone.


test.c
------
Simple test program required only by the test program of the OfflineJob class;
not required in order to use the OfflineJob class. Can be compiled under
Linux and under Windows+MinGW by typing this command:

    make test.exe

Type:

    test.exe --help

for a detailed list of its features. Basically, this program may simulate a
worker process: it may write something, it may sleep, it may perform busy loop,
it may simulate a crash (segmentation fault) and finally it may exit with a
specific status code, exactly what we need to test job control.


- Umberto Salsi
