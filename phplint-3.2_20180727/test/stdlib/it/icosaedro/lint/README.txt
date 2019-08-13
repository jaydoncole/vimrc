Linter test files
=================

The "data" directory contains samples of source code to be parsed by the test-Lint.php
program (.php) along with the resulting original output (.report.txt) and the current
output to be compared with (.report.DIFFERS.txt). A digit at the beginning of the
sample file name indicates the major version of PHP to be assumed while parsing:

   5 - PHP 5
   7 - PHP 7
   anything else: PHP 5.

Example:

	5-arguments-by-reference.php (test source)
	5-arguments-by-reference.report.txt (original output from Lint)
	5-arguments-by-reference.report.DIFFERS.txt (current output from Lint)

If the original output is exactly equal to the current output, then the test passed
and the current output file is removed. Otherwise, you must compare the original
and the current output for differences and fix Lint if required; eventually either
remove the wrong current output or confirm its validity removing the original
output and renaming the current output by removing the ".report.DIFFERS" words
in its name.

These tests should be run after any change of Lint, and before any public release
of the PHPLint package.

To run the test on all data/*.php files:

	$ php test-Linter.php

To run the test only on specific samples:

	$ php test-Linter.php data/sample1.php data/sample2.php ...
