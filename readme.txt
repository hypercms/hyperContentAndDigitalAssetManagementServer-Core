This file is part of
hyper Content Management Server - http://www.hypercms.com
Copyright (c) by hyper CMS Content Management Solutions GmbH
You should have received a copy of the License along with hyperCMS.


The following technical prerequisites must be fullfilled before installing hyper Content & Digital Asset Managment Server: 

On server-side:
- Operating System: Linux, UNIX-Derivates, MS Windows
- WebServer: Apache, Iplanet or MS IIS with PHP-Modul Version 5.3+ with mcrypt extension
- RDBMS (Database): MySQL 5+ (required for installation script), any database with ODBC support (not supported by installation script)

Optionally required for full Digital Asset Management (DAM) support on server-side:
- FFMPEG (for converting video and audio files)
- YAMDI (for meta data injection into FLV files)
- ImageMagick (for converting images)
- XPDF (for indexing PDF-documents)
- ANTIWORD (for indexing older Word-documents)
- ZIP/UNZIP (for packing and unpacking files)
- UNOCONV (for converting office files)
- EXIFTOOL (for reading meta data of files)
- TESSERACT (for OCR)

On client-side:
- hyperCMS is 100% browser based, no additional software is required
- Internet Explorer, Firefox, Chrome, Safari, Opera (latest stable release recommended for full support) 


Installation:
1. Extract and copy all files from the compressed ZIP file to the webservers root directory, so you can access them via browser. Do not rename any directories or files from the installation package.
2. Access http(s)://www.your-domain.com/hypercms/install and follow the installation process
3. After the successful installation you will be forwarded to the logon of the system. Use username "admin" and your password to access the system.


Manuals:
All hyperCMS manuals can be found in hypercms/help.


Screencasts:
Visit our Youtube channel: http://www.youtube.com/hyperCMS

