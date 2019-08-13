@echo off
rem Applies phpl to all the .php files of the current directory, recursively.
rem Set your preferred options here once for all, and check periodically
rem your code base.
for /r %%i in (*.php) do (
	call phpl ^
		--php-version 7 ^
		--print-file-name ^
		--print-path relative ^
		--no-print-notices ^
		--no-print-source ^
		--no-overall ^
		%%i
)
