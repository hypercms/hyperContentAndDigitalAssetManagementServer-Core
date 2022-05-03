This file is part of
hyper Content & Digital Asset Management Server - http://www.hypercms.com
by hyper CMS Content Management Solutions GmbH
You should have received a copy of the License along with hyperCMS.


The following technical prerequisites must be fulfilled before installing the hyper Content & Digital Asset Management Server: 

On server-side:
- Operating System: Linux, UNIX-Derivates, MS Windows
- WebServer: Apache 2.4 with support of htaccess files or Microsoft IIS with support of web.config files, PHP Version 7+ and the following PHP modules: bcmath, calendar, Core, ctype, curl, date, dom, exif, fileinfo, filter, ftp, gd, gettext, hash, iconv, json, ldap, libxml, mbstring, mysqli, mysqlnd, openssl, pcntl, pcre, Phar, posix, readline, Reflection, session, shmop, SimpleXML, soap, sockets, SPL, standard, sysvmsg, sysvsem, sysvshm, tokenizer, wddx, xml, xmlreader, xmlwriter, xsl, zip, zlib, Zend OPcache
- RDBMS (Database): MariaDB 10.1+ or MySQL 5+ (required for installation script), any database with ODBC support (not supported by installation script)

Optionally required for full Digital Asset Management (DAM) support on server-side:
- FFMPEG (for converting video and audio files)
- YAMDI (for meta data injection into FLV files)
- UFRAW or DCRAW (for raw images from digital cameras)
- ImageMagick (for converting images)
- WebP (for webp image format support)
- XPDF (for indexing PDF-documents)
- ANTIWORD (for indexing older Word-documents)
- ZIP/UNZIP (for packing and unpacking files)
- LIBREOFFICE and UNOCONV (for converting office files)
- EXIFTOOL (for reading meta data of files)
- TESSERACT (for OCR)
- OpenSSL (for encryption)
- WKHTMLTOPDF (convert HTML to PDF)
- X-Server (used for WKHTMLTOPDF)
- PDFTK (merge PDF files)


On client-side:
- hyperCMS is 100% browser based, no additional software is required
- MS Edge, Firefox, Chrome, Safari, Opera (latest stable release recommended for full support) 


Installation:
1. Extract and copy all files from the compressed ZIP file to the webservers root directory, so you can access them via browser. Do not rename any directories or files from the installation package.
2. Access http(s)://www.your-domain.com/hypercms/install and follow the installation process
3. After the successful installation you will be forwarded to the logon of the system. Use username "admin" and your password to access the system.


Manuals:
All manuals can be found in hypercms/help.


Screencasts:
Visit our Youtube channel: http://www.youtube.com/hyperCMS

