#include <pwd.h>
#include <grp.h>
#include <stdio.h>
#include <string.h>
#include <stdlib.h>
#include <unistd.h>
#include <errno.h>
#include <sys/types.h>
#include <sys/stat.h>
#include <regex.h>
#include <libgen.h>

#define FILE_DEFAULT_PERMISSION 439   //octal 667
#define DIRECTORY_DEFAULT_PERMISSION 511    //octal 777

/* ---- Base64 Encoding/Decoding Table --- */
char b64[] = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/";

/* decodeblock - decode 4 '6-bit' characters into 3 8-bit binary bytes */
void decodeblock(unsigned char in[], char *clrstr) {
    unsigned char out[4];
    out[0] = in[0] << 2 | in[1] >> 4;
    out[1] = in[1] << 4 | in[2] >> 2;
    out[2] = in[2] << 6 | in[3] >> 0;
    out[3] = '\0';
    strncat(clrstr, out, sizeof(out));
}

void b64_decode(char *b64src, char *clrdst) {
    int c, phase, i;
    unsigned char in[4];
    char *p;

    clrdst[0] = '\0';
    phase = 0; i=0;
    while(b64src[i]) {
        c = (int) b64src[i];
        if(c == '=') {
            decodeblock(in, clrdst);
            break;
        }
        p = strchr(b64, c);
        if(p) {
            in[phase] = p - b64;
            phase = (phase + 1) % 4;
            if(phase == 0) {
                decodeblock(in, clrdst);
                in[0]=in[1]=in[2]=in[3]=0;
            }
        }
        i++;
    }
}
int main(int argc, char *argv[])
{
    if(strlen(argv[4])>1024) {
        fprintf(stderr,"Path length is more than 1024\n");
    }
    //decode path
    char destination_path[1024],parent_path[1024] = "";
    b64_decode(argv[4], destination_path);
    b64_decode(argv[4], parent_path);

    //find parent path to use in validation
    dirname(parent_path);

    regex_t umaskRegex;
    regex_t pathRegex;
    regex_t uidRegex;
    regex_t gidRegex;
    int reti;
#ifndef TEST
    //check regex for path
    reti = regcomp(&pathRegex, "^/itu/users/", REG_EXTENDED);
    if (reti) {
        perror("Could not compile regex rule for path");
        exit(1);
    }
    reti = regexec(&pathRegex, destination_path, 0, NULL, 0);
    if (reti == REG_NOMATCH) {
        fprintf(stderr,"Path is not matched with regex\n");
        exit(EXIT_FAILURE);
    }
    regfree(&pathRegex);
    char *actualpath = realpath(destination_path,NULL);
    if(actualpath != NULL) {
        //check regex for path
        reti = regcomp(&pathRegex, "^/itu/s0[1-8]d0[1-9]|1[0-1]/", REG_EXTENDED);
        if (reti) {
            perror("Could not compile regex rule for  real path");
            exit(1);
        }
        reti = regexec(&pathRegex, actualpath, 0, NULL, 0);
        if (reti == REG_NOMATCH) {
            fprintf(stderr,"Real path is not matched with regex\n");
            exit(EXIT_FAILURE);
        }
        regfree(&pathRegex);
    }

    //check regex for gid
    reti = regcomp(&gidRegex, "^300$", REG_EXTENDED);
    if (reti) {
        perror("Could not compile regex rule for gid");
        exit(1);
    }
    reti = regexec(&gidRegex, argv[2], 0, NULL, 0);
    if (reti == REG_NOMATCH) {
        fprintf(stderr,"GID is not matched with regex\n");
        exit(EXIT_FAILURE);
    }
    regfree(&gidRegex);
#endif
    //check regex for uid
    reti = regcomp(&uidRegex, "^[1-9][0-9]{3,5}$", REG_EXTENDED);
    if (reti) {
        perror("Could not compile regex rule for uid");
        exit(1);
    }
    reti = regexec(&uidRegex, argv[1], 0, NULL, 0);
    if (reti == REG_NOMATCH) {
        fprintf(stderr,"UID is not matched with regex\n");
        exit(EXIT_FAILURE);
    }

    //check regex for umask
    reti = regcomp(&umaskRegex, "^007$", REG_EXTENDED);
    if (reti) {
        perror("Could not compile regex rule for umask");
        exit(1);
    }
    reti = regexec(&umaskRegex, argv[3], 0, NULL, 0);
    if (reti == REG_NOMATCH) {
	fprintf(stderr,"Umask is not matched with regex\n");
	exit(EXIT_FAILURE);
    }
    regfree(&umaskRegex);

    int ownerUid=atoi(argv[1]);
    int ownerGid=atoi(argv[2]);

    //UID and GID should be same with parent directory owner UID and GID
    struct stat info;
    stat(parent_path, &info);  // Error check omitted
    if(info.st_uid != ownerUid) {
        fprintf(stderr,"UID not matched with parent directory's UID. Parent:%d - UID:%d\n",info.st_uid,ownerUid);
        exit(EXIT_FAILURE);
    }

    if(info.st_gid != ownerGid) {
        fprintf(stderr,"GID not matched with parent directory's GID. Parent:%d - GID:%d\n",info.st_gid,ownerGid);
        exit(EXIT_FAILURE);
    }

    //get owner of file from arguments
    struct stat cur_file;

    //check file is exist or not
    if (stat(destination_path, &cur_file) == -1) {
        perror("stat");
        exit(EXIT_FAILURE);
    }

    int res = chown(destination_path,(long) ownerUid, (long) ownerGid);

    //check result
    if(res==-1){
        perror("chown");
        exit(EXIT_FAILURE);
    }

    else{
        //if it is directory
        if(S_ISDIR(cur_file.st_mode)){
            chmod(destination_path,(DIRECTORY_DEFAULT_PERMISSION-strtol(argv[3],0,8)));
        }
        //if it is file
        else{
            chmod(destination_path,(FILE_DEFAULT_PERMISSION-strtol(argv[3],0,8)));
        }
    }
    exit(EXIT_SUCCESS);
}