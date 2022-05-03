# Welcome

This page provides basic information about the Free Edition of the hyper Content & Digital Asset Management Server. Please visit our website https://www.hypercms.com for more details.
All manuals are provided as PDF documents on our website or in the directory hypercms/help in the repository or the compressed installation file that can be downloaded from our website.

## The following technical prerequisites must be fullfilled before installing the hyper Content & Digital Asset Management Server:

**On server-side:**

* Operating System: Linux, UNIX-Derivates, MS Windows
* WebServer: Apache 2.4 with support of htaccess files or Microsoft IIS with support of web.config files, PHP Version 7+ and the following PHP modules: bcmath, calendar, Core, ctype, curl, date, dom, exif, fileinfo, filter, ftp, gd, gettext, hash, iconv, json, ldap, libxml, mbstring, mysqli, mysqlnd, openssl, pcntl, pcre, Phar, posix, readline, Reflection, session, shmop, SimpleXML, soap, sockets, SPL, standard, sysvmsg, sysvsem, sysvshm, tokenizer, wddx, xml, xmlreader, xmlwriter, xsl, zip, zlib, Zend OPcache
* RDBMS (Database): MariaDB or MySQL 5+ (required for installation script), any database with ODBC support (not supported by installation script)

**Optionally required for full Digital Asset Management (DAM) support on server-side:**

* FFMPEG (for converting video and audio files)
* YAMDI (for meta data injection into FLV files)
* UFRAW or DCRAW (for raw images from digital cameras)
* ImageMagick (for converting images)
* WebP (for webp image format support)
* XPDF (for indexing PDF-documents)
* ANTIWORD (for indexing older Word-documents)
* ZIP/UNZIP (for packing and unpacking files)
* LIBREOFFICE and UNOCONV (for converting office files)
* EXIFTOOL (for reading meta data of files)
* TESSERACT (for OCR)
* WKHTMLTOPDF (convert HTML to PDF)
* X-Server (used for WKHTMLTOPDF)
* PDFTK (merge PDF files)

**On client-side:**

* hyperCMS is 100% browser based, no additional software is required
* Internet Explorer, Firefox, Chrome, Safari, Opera (latest stable release recommended for full support) 

## Installation

1. Extract and copy all files from the compressed ZIP file to the webservers root directory, so you can access them via browser. Do not rename any directories or files from the installation package.
2. Access https://www.your-domain.com/hypercms/install and follow the installation process
3. After the successful installation you will be forwarded to the logon of the system. Use username "admin" and your password to access the system.

We also recommend taking a look at the Installation Guide (PDF) regarding the configuration of php.ini in order to handle large files.

## Manuals

All hyperCMS manuals can be found in hypercms/help.

## Screencasts

Visit our Youtube channel: http://www.youtube.com/hyperCMS

## How to install additional software packages

We recommend using Linux for production. Depending on the Linux distribution the installation process of the additional software packages can vary. 
The following examples are based on Aptitude and Debian 9.
All packages except FFMPEG are already included in Debian.

### You can easily install them using: ###
```
apt-get install xpdf
apt-get install antiword
apt-get install ufraw-batch (for Debian 8,9, 10)
apt-get install dcraw (Debian 11)
apt-get install imagemagick
apt-get install yamdi
apt-get install zip
apt-get install tesseract-ocr
apt-get install tesseract-ocr-all
apt-get install libreoffice
apt-get install unoconv
apt-get install pdftk
```

### How to install WKHTMLTOPDF? ###
The current distribution of wkhtmltopdf in apt-get is not patched with the latest version of Qt, and does not support multiple input files and other options. To solve this, you can manually install the updated wkhtmltopdf from the official website itself: http://wkhtmltopdf.org/downloads.html
If you want to use the package provided by the distribution, use:
```
apt-get install wkhtmltopdf
apt-get install xvfb
```

### How to add and install FFMPEG? ###

Add the Multimedia Repository to your sources in /etc/apt/sources.list.
This will also guarantee you to receive all the software updates for FFMPEG.
```
deb http://www.deb-multimedia.org stretch main non-free
```

Install public key:
```
apt-get update
apt-get install deb-multimedia-keyring
```

Aptitude update:
```
apt-get update
```

Finally install FFMPEG:
```
apt-get install ffmpeg
```


Have fun!