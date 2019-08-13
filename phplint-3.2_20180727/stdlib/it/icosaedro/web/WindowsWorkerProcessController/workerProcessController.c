/**
 * Worker process controller to start background processes under Windows.
 * With this program, any executable can be started with a given working
 * directory set and its stdin, stdout and stderr streams redirected to files
 * beneath that directory; the PID and the exit status code can be retrieved
 * on files as well.
 * 
 * To run this controller process in background, the "start" command can be used.
 * So, ad example, to execute myworker.exe with arguments a1 a2 a3 under the
 * directory session001 from the console:
 * 
 * <pre>
 * c:\> mkdir session001
 * c:\> echo "my input data" > session001/input.txt
 * c:\> start /b workerProcessController.exe --session-dir session001 --worker myworker.exe a1 a2 a3
 * </pre>
 * 
 * If the "start" command succeeds, and the "pid.txt" file appears after a
 * reasonable amount of time, then the worker process was successfully started;
 * if not, something gone wrong but it might be hard to figure out exactly what.
 * 
 * @author Umberto Salsi <salsi@icosaedro.it>
 * @version $Date: 2018/04/05 14:46:10 $
 */

#include <stdio.h>
#include <stdlib.h>
#include <strings.h>
#include <windows.h>
#include <io.h>

#define SESSION_EXIT_STATUS "exit_status.txt"
#define SESSION_STDIN "stdin.txt"
#define SESSION_STDOUT "stdout.txt"
#define SESSION_STDERR "stderr.txt"
#define SESSION_PID "pid.txt"


static char *prog_name;


static void help()
{
	printf("%s - Worker process launcher.\n", prog_name);
	printf(
"This program launches a background worker process and then waits for its\n"
"termination. The working directory must be specified, and here a file named\n"
"stdin.txt is expected; several other files are created as detailed below.\n"
"Command line arguments, some mandatory and in the order:\n"
"\n"
"  --session-dir DIR   Sets the session directory; mandatory.\n"
"\n"
"  --ticket TICKET     Formal name as displayed along with the command line\n"
"                      that starts the worker; optional. Default: first argument\n"
"                      after --worker. It can be an univocal ID to safely\n"
"                      identify the worker in the processes' table.\n"
"\n"
"  --worker CMD ...    Sets the command line that starts the worker\n"
"                      along with its arguments. Mandatory. It must be the\n"
"                      last argument, as all the following argumets are sworker process.\n"
"  /? or -h or --help  Shows this help and exits.\n"
"\n"
"The session directory and the file stdin.txt therein must be already\n"
"set before invoking this process.\n"
"The worker process is launched with the working directory set to the\n"
"session directory. All the following files are put there as well:\n"
"\n"
"  pid.txt             PID of the worker once successfully started.\n"
"  stdin.txt           REQUIRED EXISTING. Set as stdin of the worker.\n"
"  stdout.txt          Generated stdout of the worker.\n"
"  stderr.txt          Generated stderr of the worker.\n"
"  exit_status.txt     Exit status code of the worker once terminated.\n"
"\n"
"This process exits either because of an error setting the environment,\n"
"or the worker process is terminated; the exit code is zero for success,\n"
"non-zero for error and the worker process has not been created.\n"
"A missing pid.txt file means the worker process has not been created yet.\n"
"A missing exit_status.txt means the worker process is still running.\n");
}


static char *errorCodeToString(int code) {
	static char s[999];
	char win_err_descr[900];
	DWORD err = FormatMessageA(
		FORMAT_MESSAGE_FROM_SYSTEM,
		NULL,
		code,
		LANG_SYSTEM_DEFAULT,
		win_err_descr,
		sizeof (win_err_descr),
		NULL
		);
	if (err > 0) {
		snprintf(s, sizeof (s), "%s (Windows error code %d)", win_err_descr, code);
	} else {
		snprintf(s, sizeof (s), "error code %d (description not available: FormatMessageA() failed with code %lu)", code, err);
	}
	return s;
}


static char *quote_buffer;
static int quote_buffer_capacity;
static int quote_buffer_length;

static void quoteBufferSetCapacity(int size)
{
	if( quote_buffer_capacity >= size )
		return;
	quote_buffer_capacity = size + 100;
	quote_buffer = realloc(quote_buffer, quote_buffer_capacity);
}


static void quoteInsertChar(int c, int i)
{
	quoteBufferSetCapacity(quote_buffer_length + 1 + 1);
	int j;
	for(j = quote_buffer_length + 1; j > i; j--)
		quote_buffer[j] = quote_buffer[j-1];
	quote_buffer[i] = c;
	quote_buffer_length++;
}


static char *quote(char *s)
{
	// Copy the string into the quote buffer:
	int l = strlen(s);
	quoteBufferSetCapacity(l + 1);
	strcpy(quote_buffer, s);
	quote_buffer_length = l;
	
	if( strchr(quote_buffer, ' ') == NULL )
		return quote_buffer;
	quoteInsertChar('"', l);
	quoteInsertChar('"', 0);
	return quote_buffer;
}


static void writeIntToFile(int i, char *fn)
{
	FILE *f = fopen(fn, "wb");
	if( f == NULL ){
		fprintf(stderr, "%s: failed creating %s file\n", prog_name, fn);
		return;
	}
	fprintf(f, "%d", i);
	fclose(f);
}


static void writeUint32ToFile(DWORD i, char *fn)
{
	FILE *f = fopen(fn, "wb");
	if( f == NULL ){
		fprintf(stderr, "%s: failed creating %s file\n", prog_name, fn);
		return;
	}
	fprintf(f, "%lu", i);
	fclose(f);
}

static int writeExitStatus(int code) {
	writeIntToFile(code, SESSION_EXIT_STATUS);
	return code;
}


static HANDLE createStream(char *path, int for_write)
{
	SECURITY_ATTRIBUTES sa;
	memset(&sa, 0, sizeof(sa));
	sa.bInheritHandle = TRUE;
	
	HANDLE h = CreateFile(
		path,
		for_write? GENERIC_WRITE : GENERIC_READ,
		FILE_SHARE_WRITE | FILE_SHARE_READ,
		&sa,
		for_write? CREATE_ALWAYS : OPEN_EXISTING,
		FILE_ATTRIBUTE_NORMAL,
		NULL
	);
	if( h == INVALID_HANDLE_VALUE ){
		fprintf(stderr, "%s: failed opening %s for %s: %s\n",
			prog_name, path,
			for_write? "write" : "read", errorCodeToString(GetLastError()));
		exit(writeExitStatus(128));
	}
	return h;
}


int main(int argc, char** argv) {
	char *session_dir = NULL;
	char *ticket = NULL;
	char *worker_path = NULL;
	char *cmdLine = NULL;
	int i;

	prog_name = argv[0];

	// Avoid any GUI feedback (hopefully...):
	/* ignore = */ SetErrorMode(SEM_FAILCRITICALERRORS);

	// Parse and check command line arguments:
	for (i = 1; i < argc; i++) {
		char *opt = argv[i];
		char *arg = i < argc ? argv[i + 1] : NULL;
		if (strcmp(opt, "/?") == 0 || strcmp(opt, "-h") == 0 || strcmp(opt, "--help") == 0) {
			help();
			return 0;
		} else if (strcmp(opt, "--session-dir") == 0) {
			session_dir = arg;
			i++;
		} else if (strcmp(opt, "--ticket") == 0) {
			ticket = arg;
			i++;
		} else if (strcmp(opt, "--worker") == 0) {
			worker_path = arg;
			if( ticket == NULL )
				ticket = arg;
			int cmdLineCapacity = strlen(ticket) + 1000;
			cmdLine = malloc(cmdLineCapacity);
			strcpy(cmdLine, ticket);
			for( i = i+2; i < argc; i++ ){
				strncat(cmdLine, " ", cmdLineCapacity);
				strncat(cmdLine, quote(argv[i]), cmdLineCapacity);
			}
			////////printf("command: %s.\n", cmdLine);
			////////return 0;
		} else {
			fprintf(stderr, "%s: unknown option: %s\n", prog_name, opt);
			return 128;
		}
	}

	// Set the working dir. with the session dir:
	if (session_dir == NULL) {
		fprintf(stderr, "%s: missing mandatory --session-dir option\n", prog_name);
		return 128;
	}
	if (!SetCurrentDirectory(session_dir)) {
		fprintf(stderr, "%s: failed setting current session directory %s: %s\n",
			prog_name, session_dir, errorCodeToString(GetLastError()));
		return 128;
	}
				
	// FIXME: what on error?
	freopen(SESSION_STDERR, "a", stderr);

	if( worker_path == NULL ){
		fprintf(stderr, "%s: missing mandatory --worker option\n", prog_name);
		return writeExitStatus(128);
	}

	// Create stdxxx files and streams:
	STARTUPINFO si;
	memset(&si, 0, sizeof(si));
	si.cb = sizeof(si);
	si.dwFlags = STARTF_USESTDHANDLES;
	si.hStdInput = createStream(SESSION_STDIN, 0);
	si.hStdOutput = createStream(SESSION_STDOUT, 1);
	si.hStdError = createStream(SESSION_STDERR, 1);

	// Create child process:
	PROCESS_INFORMATION pi;
	if( ! CreateProcess(
		worker_path,
		cmdLine,
		NULL,
		NULL,
		TRUE, // inherit handles
		CREATE_NO_WINDOW | CREATE_NEW_PROCESS_GROUP,
		NULL, // envars
		NULL, // working dir
		&si,
		&pi
	) ){
		fprintf(stderr, "%s: failed creating worker process %s: %s\n",
			prog_name, worker_path, errorCodeToString(GetLastError()));
		return writeExitStatus(128);
	}

	// Recommended closing unused handles:
	CloseHandle(pi.hThread);
	
	// Save child process ID to file:
	writeIntToFile(pi.dwProcessId, SESSION_PID);
	
	// Wait for child termination:
	WaitForSingleObject(pi.hProcess, INFINITE);
	
	// Close stdxxx handles to flush buffers:
	CloseHandle(si.hStdInput);
	CloseHandle(si.hStdOutput);
	CloseHandle(si.hStdError);
	
	// Save child exit status code to file:
	DWORD worker_exit_status;
	GetExitCodeProcess(pi.hProcess, &worker_exit_status);
	writeUint32ToFile(worker_exit_status, SESSION_EXIT_STATUS);
	
	return 0;
}
