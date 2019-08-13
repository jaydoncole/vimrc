WELCOME TO THE ACME SAMPLE WEB SITE
===================================

This directory contains a fictional web site used as a sample of the
PHPLint standard library web tools, mainly sticky forms, bt_ and bt forms.
The tutorial available at http://www.icosaedro.it/phplint/web explains the basic
of the sticky forms, bt_ and bt forms tools.
This sample web site is not in any way related to the really existing Acme
web site, but I could not resist from using that name!


Contents of this directory
--------------------------

BtFormSample.php
Bt form that shows all the available controls.

Common.php
Site specific parameters and configuration.

Dashboard.php
User's landing page after login. From here it may invoke the other pages.

Dispatcher.php
Implements the bt_ dispatcher of the requests used by /bt/index.php.

JobMonitor.php
Displays the state of a specific background job. It is invoked by JobsMonitor.

JobsMonitor.php
Displays all the background jobs.

Login.php
Sticky form that displays the login page. Used by the /bt/login.php page.

ServerState.php
Another bt form to display the phpinfo() informations.

UserProfile.php
Allows the user to change its password. An nothing else, for now.

sampledb.sqlite
SQLite ACME data base created by Common.php. It only contains users login. Real
web applications may want to use some other real DBMS, though. SQLite is handy
because it is built-in in PHP, no need to install anything, so it is perfect for
a sample web site like this.


Installation
------------

You need a running web server like Apache on Linux or Wampserver on Windows.
Only two pages have to be installed: the login page, and the requests dispatcher
page. More in details:

1. Copy the phplint package *above* the document root of your web site.
   PHPLint and its library does not need and should not be accessible from the
   network.

2. Again in the directory *above* the document root, also create a directory
   named "BT_STATE". Here bt_ will save users' sessions. The path of this
   directory must match the com\acme\www\Common::BT_BASE_DIR constant, anyway,
   so change that configuration constant accordingly.

3. Move the bt/ directory *inside* the document root of your web site, typically
   /var/www/public_html (Linux) or C:\wamp\www (Wamp server on Windows) and
   change the file name extension of the two files it contains login.php.txt and
   index.php.txt to ".php" so that their name reads login.php and index.php
   respectively. These login page and the dispatcher page are the only 2 public
   documents of all the web site. You may change their name and their path, but
   remember to update Common accordingly.

4. You also need to adjust the path of the php.ini actually available
   in the phplint/stdlib/errors.php executable, apache2handler SAPI.

Done: now you have the PHPLint validation program available, all the standard
libraries under phplint/stdlib, along with the sample Acme web site.
Point your browser at http://localhost/bt/index.php to start the login.
At the first invocation of the login page a new SQLite data base is created with
these two accounts on it:

        admin (password "admin")
        guest (empty password)

If the login page does not shows up, check the log files of the web server.


     +-------------------| SECURITY ALERT |-----------------------+
     |                                                            |
     | The JobsMonitor.php form allows the "admin" user to start  |
     | *any* command on the system, so it might be a big security |
     | risk. You should immediately change the admin password and |
     | enable the HTTPS security layer. Or, disable that feature  |
     | at all in the Common.php class.                            |
     +------------------------------------------------------------+


References
----------

- PHPLint home page: download, updates and documentation.
  http://www.icosaedro.it/phplint

- PHPLint standard library documentation generated from the sources.
  http://www.icosaedro.it/phplint/phplint2/libraries.htm

- PHPLint web tools tutorial about sticky forms, bt_ and bt forms.
  http://www.icosaedro.it/phplint/web

- Working Acme sample web site built as shown here. Only "guest" account :-)
  http://www.icosaedro.it/bt


- Umberto Salsi
  Bologna, 2018-04-11
