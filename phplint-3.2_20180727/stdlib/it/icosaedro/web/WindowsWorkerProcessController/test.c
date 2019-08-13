#include <stdlib.h>
#include <stdio.h>
#include <string.h>
#include <time.h>
#include <unistd.h>
#include <errno.h>

static void help()
{
	printf("Program to test process handling.\n"
"Syntax of the command:\n"
"\n"
"    test ...\n"
"\n"
"where the arguments are the commands. Available commands:\n"
"\n"
"    i    Reads a line from stdin and sends it to stdout.\n"
"    o P  Sends P to stdout followed by new-line.\n"
"    e P  Sends P to stderr followed by new-line.\n"
"    s P  Sleep P seconds.\n"
"    b P  Busy loop for P seconds.\n"
"    x P  Causes a fatal segmentation fault; P ignored.\n"
"    r P  Sets the return status code P (default: 0).\n");
}


int main(int argc, char **argv)
{
	int i, return_status = 0;
	for(i=1; i < argc; i++){
		char *a = argv[i];
		char *p = i+1 < argc? argv[i+1] : NULL;
		if( strcmp(a, "-h") == 0 || strcmp(a, "--help") == 0 ){
			help();
		} else if( strcmp(a, "i") == 0 ){
			char line[999];
			if( fgets(line, sizeof(line), stdin) == NULL ){
				if( ferror(stdin) )
					fprintf(stderr, "ERROR reading stdin: %s\n", strerror(errno));
				else
					fprintf(stderr, "EOF reading stdin.\n");
			} else {
				printf("%s", line);
			}
		} else if( strcmp(a, "o") == 0 ){
			printf("%s\n", p);
			i++;
		} else if( strcmp(a, "e") == 0 ){
			fprintf(stderr, "%s\n", p);
			i++;
		} else if( strcmp(a, "s") == 0 ){
			sleep(atoi(p));
			i++;
		} else if( strcmp(a, "b") == 0 ){
			int t = time(NULL) + atoi(p);
			while( time(NULL) < t );
			i++;
		} else if( strcmp(a, "x") == 0 ){
			i = *((int *) NULL);
		} else if( strcmp(a, "r") == 0 ){
			return_status = atoi(p);
			i++;
		} else {
			fprintf(stderr, "ERROR: unknown command: %s\n", a);
			return_status = 1;
			break;
		}
	}
	return return_status;
}