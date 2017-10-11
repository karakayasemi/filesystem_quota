#include <pwd.h>
#include <stdio.h>
#include <string.h>
#include <stdlib.h>
#include <unistd.h>
#include <errno.h>
#include <sys/types.h>
#include <sys/stat.h>
#define FILE_DEFAULT_PERMISSION 439   //octal 667
#define DIRECTORY_DEFAULT_PERMISSION 511    //octal 777

int main(int argc, char *argv[])
{
    struct passwd pwd;
    struct passwd *result;
    char *buf;
    size_t bufsize;
    int s;

    bufsize = sysconf(_SC_GETPW_R_SIZE_MAX);
    if (bufsize == -1)          /* Value was indeterminate */
        bufsize = 16384;        /* Should be more than enough */

    buf = (char*) malloc(bufsize);
    if (buf == NULL) {
        perror("malloc");
        exit(EXIT_FAILURE);
    }

    s = getpwnam_r(argv[1], &pwd, buf, bufsize, &result);
    if (result == NULL) {
        if (s == 0)
            printf("Not found\n");
        else {
            errno = s;
            perror("getpwnam_r");
        }
        exit(EXIT_FAILURE);
    }

    struct stat cur_file;

    //check file is exist or not
    if (stat(argv[4], &cur_file) == -1) {
        perror("stat");
        exit(EXIT_FAILURE);
    }

    int res = chown(argv[4],(long) pwd.pw_uid, atoi(argv[2]));

    //check result
    if(res==-1){
        perror("chown");
        exit(EXIT_FAILURE);
    }

    else{
        //if it is directory
        if(S_ISDIR(cur_file.st_mode)){
            chmod(argv[4],(DIRECTORY_DEFAULT_PERMISSION-strtol(argv[3],0,8)));
        }
        //if it is ffile
        else{
            chmod(argv[4],(FILE_DEFAULT_PERMISSION-strtol(argv[3],0,8)));
        }
    }
    exit(EXIT_SUCCESS);
}