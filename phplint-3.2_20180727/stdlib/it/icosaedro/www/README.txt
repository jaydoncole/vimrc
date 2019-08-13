Web Commenting System
=====================

This directory contains all the code and classes needed to implement the WCS
as available at the www.icosaedro.it web site. The WCS allows visitors to add
comments to any page. Users may also register themself with a very short
procedure: only a login name and a login password is required. Registered users
may then also access a specific preferences dialog box where other preferences
can be set.

All the WCS is implemented using the bt_ technology, making the code simple and
safe. The SQL abstraction layer provided by the PHPLint standard library is also
used.

A database server is required, and this implementation assumes MySQL, but other
engines can be used as well chosing among PostgreSQL and SQLite. Support for
even more database engines requires to extend the SQL abstraction library.

Only one actual web page is required: the dispatcher page. This page can be
located for example at "/comments/rh.php" as explained in the SiteSpecific
class. The same SiteSpecific class should be customized according to the
specific configuration. It is particularly important to set the HMAC_KEY
constant to some random value.

Each page of the web site may then display a summary of the comments added to
that page and may contain links to access the WCS. The proposed code follows:

<?php
	require_once __DIR__ . "/../phplint/stdlib/all.php";
	use it\icosaedro\www\Summary;
	
	Summary::echoAnchorToComments("", "Comments");
	echo "<hr>";
	Summary::showSummary();
?>

Here, the echoAnchorToComments() creates a link that opens another page with all
the comments, and allows the visitor to enter even more comments.
The showSummary() method displays a summary of the latest comments added to the
page.

- Umberto Salsi

