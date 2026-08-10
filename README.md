# VirtualDisk

## Description

With VirtualDisk, we can manage files and folders from distincts origins using the same metodology, and having the availability of change it, or use a few of then without the need to apply any change into the code.

Creating a Virtual disk access (specially into a local disk), this lib use the **origin** as a **root** folder, avoiding the posibility of access using _"../"_

VirtualDisk provide, actually, access to:

- Real local file system
- Zip files, using his internal folder structure as a disk
- Shared network origins using SAMBA
- FTP/FTPS/SFTP servers
- WebDav services
- Dropbox service

## Install

```bash
composer require juanchosl/virtualdisk
```

## How to use

The Engines, are requiring a path or uri for use as the **root**, is the only distinction, when the access is opened, all instructions are equals for all engines

### Origins

#### Local file system

A path, with the standard UNIX path structure

```php
$disk = new NativeLocalEngine("/path/to/desired/shared/folder");
```

#### Local ZIP/PHAR file

The full file path, we have available some Engines, using class ZipArchive from zip module, when it is available or PharData for Zip based files. For pure PHAR files, we have available the ZipPharSystemFile, that use phar streams and file system functions

```php
$disk = new ZipPharEngine("/path/to/desired/zip_file.zip");
```

#### SAMBA shared folder

A shared folder, with the standard UNIX path structure

```php
$disk = new SambaFileSystemEngine("//user:password@shared_name/path/to/desired/remote/shared/folder");
```

#### FTP remote server

An access uri to the server, with access credentials, and the desired protocol to use (ftp, ftps, sftp)

```php
$disk = new FtpEngine("ftp://user:password@host:port/path/to/desired/remote/shared/folder");
```

#### WebDav remote server

An access uri to the server, with access credentials, and the desired protocol to use (http, https)

```php
$disk = new WebDavEngine("https://user:password@host:port/path/to/desired/remote/shared/folder");
```

### Access

Once the access is provided, we can explore the existing data, special reminder that the provided entry point is **/** into VirtualDisk, avoiding the posibility of navigate to a _virtual root parent folder_. All functions that are accepting paths, can receive a fullpath (_/dir1/dir2/file_), a relative path from the current working dir (_../parent/dir1/file_)

> If any internal provided path, try to access out of entry point, an error is throwed

#### Explore

- ls
- chdir
- cwdir
- isDir
- isFile
- exists

#### Directories

- mkdir: creates the required dir, if a final slash is not provided, it is auto appened, ensuring does not create a file
- rmdir: delete the required dir, if a final slash is not provided, it is auto appened, ensuring does not remove a file
- truncate: delete recursively all contents of the selected subdirectory

#### Files

- read: get file contents and returns as a string
- write: put data into a file, if it does not exists, create it
- remove: delete the required file 
