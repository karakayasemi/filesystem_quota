# filesystem_quota
An ownCloud to use local filesystem quota.

The ownership of the files is given to the Linux user matching the username instead of "apache".

To control the quota, a service is required to provide the quota information of the user in file system.

Lastly, for the chown operation, the lib/chown file must be owned by root, and the file permissions must have the SUID bit.

