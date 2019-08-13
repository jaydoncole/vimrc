<?php

require_once __DIR__."/../../../../../stdlib/all.php";

use it\icosaedro\web\OfflineJob;

/**
 * @param string $cmd
 * @return string
 * @throws Exception
 */
function jobCreate($cmd)
{
	echo "Creating job: $cmd...\n";
	$job = new OfflineJob();
	$job->start($cmd);
	$ticket = $job->getTicket();
	echo "   ticket = $ticket\n";
	return $ticket;
}

/**
 * @param string $ticket
 * @throws Exception
 */
function jobDelete($ticket)
{
	echo "Deleting job $ticket...\n";
	$job = new OfflineJob($ticket);
	$job->delete();
}

/**
 * @param string $ticket
 * @throws Exception
 */
function jobMonitoring($ticket)
{
	echo "Monitoring job $ticket:\n";
	$job = new OfflineJob($ticket);
	$i = 0;
	while(TRUE){
		$i++;
		$job->updateStatus();
		echo "   $i) $job\n";
		if( $job->getStatus() == OfflineJob::STATUS_FINISHED )
			break;
		sleep(1);
	}
}

/**
 * @param string $ticket
 * @throws Exception
 */
function jobOutcome($ticket)
{
	echo "Outcome of the job $ticket:\n";
	$job = new OfflineJob($ticket);
	echo "   stdout: ", (string) str_replace("\n", "\n   stdout: ", $job->propertyRead(OfflineJob::PROPERTY_STDOUT)), "\n";
	echo "   stderr: ", (string) str_replace("\n", "\n   stderr: ", $job->propertyRead(OfflineJob::PROPERTY_STDERR)), "\n";
	echo "   exit status: ", $job->propertyRead(OfflineJob::PROPERTY_EXIT_STATUS), "\n";
}

/**
 * @param string $cmd
 * @throws Exception
 */
function simpleCommand($cmd)
{
	$ticket = jobCreate($cmd);
	jobMonitoring($ticket);
	jobOutcome($ticket);
	jobDelete($ticket);
}

/**
 * @throws Exception
 */
function LinuxSpecificTests()
{
	simpleCommand(
<<< EOT
echo -n "The current working directory is "
pwd
echo "Its contents are:"
echo PATH=\$PATH
echo "User identity: "
id
EOT
	);
}

/**
 * @throws Exception
 */
function WindowsSpecificTests()
{
	simpleCommand("c:/windows/system32/cmd.exe /c echo %path%");
	simpleCommand("c:/windows/system32/tasklist.exe /FI \"STATUS eq running\"");
	
	// Testing execution of a .bat:
	$job = new OfflineJob();
	$job->propertyWrite("myscript.bat",
			"echo The current directory is %cd%\r\n"
			."echo Its contents are\r\n"
			."dir\r\n"
			."echo User identity:\r\n"
			."whoami /all\r\n"
	);
	$job->start("c:/windows/system32/cmd.exe /c myscript.bat");
	$ticket = $job->getTicket();
	jobMonitoring($ticket);
	jobOutcome($ticket);
	jobDelete($ticket);
}

/**
 * @throws Exception
 */
function main()
{
	$test_exe = SRC_BASE_DIR . "/it/icosaedro/web/WindowsWorkerProcessController/test.exe";
	if( !file_exists($test_exe) )
		throw new RuntimeException("test program does not exist: $test_exe");
	
	// Simple worker writing to stdout, stderr and exit status 123:
	simpleCommand("$test_exe s 5 o onstdout e onstderr r 123");
	
	// Prematurely terminate and delete worker:
	$ticket = jobCreate("$test_exe s 50");
	$delay = 5;
	echo "Sleeping $delay s ...\n";
	sleep($delay);
	jobDelete($ticket);
	
	// Worker failing to start:
	simpleCommand("does_not_exist");
	
	if( PHP_OS === "Linux" ){
		LinuxSpecificTests();
	} else if( PHP_OS === "WINNT" ){
		WindowsSpecificTests();
	}
	
	OfflineJob::deleteStaleSessions();
}

main();
