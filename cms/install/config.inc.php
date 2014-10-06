<?php
// hyperCMS Main Configuration File
// please add a slash at the end of each path entries, see examples.

// Attention: all variable values set here must not include "#" !

// ------------------------------------ Content Management Server ----------------------------------------
// please note: use always slash (/) in path settings

// Depending how the user accessed our page we are setting our protocol
$mgmt_config['url_protocol'] = (!empty($_SERVER['HTTPS'])) ? 'https://' : 'http://';

// url and asolute path to hyperCMS on your webserver (e.g. /home/domain/hyperCMS/)
$mgmt_config['url_path_cms'] = $mgmt_config['url_protocol']."%url_path_cms%";
$mgmt_config['abs_path_cms'] = "%abs_path_cms%";

// url and absolute path to the external repository on your webserver (e.g. /home/domain/repository/)
// used for the storage of external content management information
$mgmt_config['url_path_rep'] = $mgmt_config['url_protocol']."%url_path_rep%";
$mgmt_config['abs_path_rep'] = "%abs_path_rep%";

// url and absolute path to the internal repository on your webserver (e.g. /home/domain/hyperCMS/data/)
// used for the storage of internal content management information
$mgmt_config['url_path_data'] = $mgmt_config['url_protocol']."%url_path_data%";
$mgmt_config['abs_path_data'] = "%abs_path_data%";

// ATTENTION: usually you do not have to change the following path variables!
// --------------------------------------------------------------------------
// url and absolute path to the template repository
// do not change this settings!
// (e.g. http://www.yourdomain.com/hypercms/template/)
// (e.g. /home/domain/hypercms/template/)
$mgmt_config['url_path_template'] = $mgmt_config['url_path_data']."template/";
$mgmt_config['abs_path_template'] = $mgmt_config['abs_path_data']."template/";

// url and absolute path to the content media repository
// for media mass storage over multiple harddisks the multimedia files can be
// distributed on the given devices (max. 10 devices!).
// special rules can be defined in $mgmt_config['abs_path_data']/media/getmedialocation.inc.php
// the configuration of hyperCMS for multiple storage devices will effect
// the development of templates in terms of referring to multimedia files!
// it is therefore recommended to configure getmedialocation to save all
// digital assests of a website on one harddisk.
// (e.g. http://www.yourdomain.com/data/media_cnt/)
// (e.g. /home/domain/data/media_cnt/)
$mgmt_config['url_path_media'] = $mgmt_config['url_path_rep']."media_cnt/";
$mgmt_config['abs_path_media'] = $mgmt_config['abs_path_rep']."media_cnt/";

// $mgmt_config['url_path_media'][1] = $mgmt_config['url_path_rep']."media_cnt1/";
// $mgmt_config['abs_path_media'][1] = $mgmt_config['abs_path_rep']."media_cnt1/";
// $mgmt_config['url_path_media'][2] = $mgmt_config['url_path_rep']."media_cnt2/";
// $mgmt_config['abs_path_media'][2] = $mgmt_config['abs_path_rep']."media_cnt2/";

// url and absolute path to the template media repository
// do not change this settings!
// (e.g. http://www.yourdomain.com/data/media_tpl/)
// (e.g. /home/domain/data/media_tpl/)
$mgmt_config['url_path_tplmedia'] = $mgmt_config['url_path_rep']."media_tpl/";
$mgmt_config['abs_path_tplmedia'] = $mgmt_config['abs_path_rep']."media_tpl/";

// url and absolute path to the page component repository
// do not change this settings!
// (e.g. http://www.yourdomain.com/data/component/)
// (e.g. /home/domain/data/component/)
$mgmt_config['url_path_comp'] = $mgmt_config['url_path_rep']."component/";
$mgmt_config['abs_path_comp'] = $mgmt_config['abs_path_rep']."component/";

// url and absolute path to the XML-content-repository
// do not change this settings!
// (e.g. http://www.yourdomain.com/data/component/)
// (e.g. /home/domain/data/component/)
$mgmt_config['url_path_content'] = $mgmt_config['url_path_data']."content/";
$mgmt_config['abs_path_content'] = $mgmt_config['abs_path_data']."content/";

// url and absolute path to the link index
// do not change this settings!
// (e.g. http://www.yourdomain.com/data/link/)
// (e.g. /home/domain/data/link/)
$mgmt_config['url_path_link'] = $mgmt_config['url_path_rep']."link/";
$mgmt_config['abs_path_link'] = $mgmt_config['abs_path_rep']."link/";

// url and absolute path to hyperCMS plugins
// plugins are used to extend the system
// do not change this settings!
// (e.g. http://www.yourdomain.com/plugin/)
// (e.g. /home/domain/hyperCMS/plugin/)
$mgmt_config['url_path_plugin'] = $mgmt_config['url_path_cms']."plugin/";
$mgmt_config['abs_path_plugin'] = $mgmt_config['abs_path_cms']."plugin/";

// relative path to the WYSIWYG editor in hyperCMS directory (default value: editor/)
$mgmt_config['rel_path_editor'] = "editor/";

// allow (true) or disable (false) html tags used in text editor for unformatted text
$mgmt_config['editoru_html'] = false;

// video player
// define videoplayer name, leave empty for the default player (VIDEO.JS) or use "projekktor" as alternative
$mgmt_config['videoplayer'] = "";

// define the default view for object editing
// "formedit": use form for content editing
// "cmsview": view of page based on template, includes hyperCMS specific code (buttons)
// "inlineview": view of page based on template, includes hyperCMS specific code (buttons) and inline text editing
$mgmt_config['objectview'] = "inlineview";

// define standard view for explorer object list ("detail" = detail view; "small", "medium" = thumbnail gallery view)
$mgmt_config['explorerview'] = "detail";

// define if sidebar for object preview should be enabled (true) or disabled (false)
$mgmt_config['sidebar'] = false;

// define standard mail link type ("access" = access-link; "download" = download-link)
$mgmt_config['maillink'] = "download";

// define name of the theme/design for the UI 
$mgmt_config['theme'] = "standard";

// define alternative logo (image file name) for top frame. the file must be in cms/images.
$mgmt_config['logo_top'] = "";

// show (true) or hide (false) information boxes to provide usage information to th user.
$mgmt_config['showinfobox'] = true;

// define URL to show in welcome page
$mgmt_config['welcome'] = "https://cms.hypercms.net/home/update_info_en.xhtml";

// ------------------------------------------------------------------------
// define operating system (OS) on content management server ("UNIX" for all UNIX and Linux OS or "WIN" for MS Windows)
// please note: MS PWS cannot handle multiple HTTP-requests at the same time! since version 3.0 PWS will not be supplied anymore.
$mgmt_config['os_cms'] = "%os_cms%";

// define date format for error logging and get local date today (jjjj-mm-dd)
$mgmt_config['today'] = date ("Y-m-d H:i", time());

// Language Settings
// define the languages and their codepages that are available in hyperCMS
// use the language shortcut as array key, e.g. $lang_name['en'] = "english";
// It is strongly recommended to use UTF-8 as character set for all languages to 
// support file names in all different languages based on UTF-8!

// English Version
$lang_name['en'] = "English";
$lang_shortcut['en'] = "en";
$lang_codepage['en'] = "utf-8";
$lang_date['en'] = 'Y-m-d H:i:s';

// German Version
$lang_name['de'] = "German";
$lang_shortcut['de'] = "de";
$lang_codepage['de'] = "utf-8";
$lang_date['de'] = 'd.m.Y H:i:s';

// Default Language
// if a user is created that language will be default until a change in the setting
$lang_shortcut_default = "en";

// Supported Applications
// set value to true if your content management server supports rendering of objects
// using program- and script-technologies like PHP, JSP, ASP. otherwise set false.
$appsupport['php'] = true;
$appsupport['jsp'] = false;
$appsupport['asp'] = false;

// File Upload
// maximum file size in MB allowed for upload. set value to 0 to enable all sizes
// check webserver restrictions too!
$mgmt_config['maxfilesize'] = 0;

// check for duplicate entries based on MD5 hash of files (true) or not (false)
$mgmt_config['check_duplicates'] = true;

// ZIP File
// maximum file size to be compressed in ZIP file in MB. set value to 0 to disable limit.
$mgmt_config['maxzipsize'] = 2000;

// maximum digits for file names (applies for createobject and uploadfile)
$mgmt_config['max_digits_filename'] = 200;

// Explorer Objectlist
// how many items (folders and objects) should be displayed in the list initally .
$mgmt_config['explorer_list_maxitems'] = 500;

// which types of files (file extensions)are not allowed for upload, example ".asp.jsp.php.pl.sql"
$mgmt_config['exclude_files'] = ".php.pl.jsp.asp.aspx.exe.sql.sh.bash";

// AutoSave
// formated and unformated text (textf, textu) will be saved autoamtically 
// each given value in seconds. set value to 0 to disable autosave.
$mgmt_config['autosave'] = 0;

// Save Metadata to Files
// save IPTC tags to image files (true) or not (false).
$mgmt_config['iptc_save'] = true;

// save XMP tags to image files (true) or not (false).
$mgmt_config['xmp_save'] = true;

// Versioning of Containers
// save versions of published containers and media files (true) or disable versioning (false).
$mgmt_config['contentversions'] = true;

// Public Download
// allow access to download and wrapper links without logon session (true) or not (false).
// this setting must be enabled if users want to provide wrapper or download links to the public.
$mgmt_config['publicdownload'] = true;

// Document Viewer
// allow the view of documents by the doc viewer (true) or not (false).
$mgmt_config['docviewer'] = true;

// Strong Passwords
// enable (true) or disable (false) strong passwords for users.
// if enabled, passwords will be checked regarding minimum security requirements.
$mgmt_config['strongpassword'] = true;

// Encryption
// encryption strength (weak, standard, strong)
$mgmt_config['crypt_level'] = "standard";

// Template code
// cleaning level of template code from none = 0 to strong = 3 (no cleaning = 0, basic set of disabled functions = 1, 1 + file access functions = 2, 2 + include functions = 3)
$mgmt_config['template_clean_level'] = 1;

// Logon Timeout
// how many minutes will an IP and user combination be locked after 10 failed attempts.
// a value of 0 means there is no timeout.
$mgmt_config['logon_timeout'] = 10; 

// CSRF Protection
// define allowed requests per minute
$mgmt_config['requests_per_minute'] = 1000;

// Security Token
// define lifetime of security token in seconds (min. 60 sec.)
$mgmt_config['token_lifetime'] = 86400;

// ------------------------------------ Import / Export ----------------------------------------
// define password, necessary for Import and Export execution.
$mgmt_config['passcode'] = "";

// ------------------------------------ App Keys ----------------------------------------

// Youtube integration
// Please provide app-key in order to upload videos to Youtube.
$mgmt_config['youtube_appname'] = "";
$mgmt_config['youtube_appkey'] = "";

// DropBox integration
// Keep in mind that the domain needs to be added to your Dropbox developer account in order to use the app-key.
// or you create your own Dropbox app-key and set it here.
$mgmt_config['dropbox_appname'] = "";
$mgmt_config['dropbox_appkey'] = "";

// ------------------------------------ Executable Linking -------------------------------------
// hyperCMS uses third party PlugIns to parse, convert or uncompress files. The Windows binaries can
// be found in the cms/bin directory of the hyperCMS distribution. For other platforms like
// Linux or UNIX derivates you should install the package of the vendors:
// Antiword - Word Parser http://www.winfield.demon.nl
// XPDF - PDF Parser http://www.foolabs.com/xpdf
// ZIP/UNZIP - ZIP Compression http://www.info-zip.org
// GZIP/GUNZIP - GZIP Compression http://www.gzip.org
// ImageMagick - Image Converter http://www.imagemagick.org
// Ghost Script - PostScript language and PDF interpreter http://www.ghostscript.com
// FFMPEG - Video/Audio Converter http://www.ffmpeg.org

// Please adopt the path to the executables according your installation on the server.
// If more extension are supported by the same program use "," as delimiter. 

// define PDF parsing (Extension: pdf)
// PDF documents could be parsed via XPDF (binary) which is platform independent
// or a PHP class can be used for parsing (causes sometimes troubles on Win32 OS).
// for XPDF define value "xpdf", for PHP class define value "php"
$mgmt_parser['.pdf'] = "%pdftotext%";

// define MS Word parsing (Extension: doc)
// To parse Word Documents you have to define the path to ANTIWORD executable
$mgmt_parser['.doc'] = "%antiword%";

// define Uncompression (Extension: gz)
// To uncompress files you have to define the path to the UNZIP executable
// It is recommended tu use GUNZIP (on Linux OS) to decompress files. GUNZIP  can  currently 
// decompress  files created by gzip, zip, compress, compress -H or pack.
$mgmt_uncompress['.gz'] = "%gunzip%";

// define ZIP-Uncompression (Extension: zip)
// If a ZIP-file has several members UNZIP should be used to uncompress the ZIP-file
$mgmt_uncompress['.zip'] = "%unzip%";

// define ZIP-Compression
// To compress files to a ZIP-file
$mgmt_compress['.zip'] = "%zip%";

// define document conversion using UNOCONV
// Convert between any document format supported by OpenOffice (use command 'unoconv --show' for details)
// home directory of webserver user (e.g. www-data) needs to have write permission in his home directory (e.g. /var/www)!
$mgmt_docpreview['.bib.doc.docx.dot.ltx.odd.odt.odg.odp.ods.ppt.pptx.pxl.psw.pts.rtf.sda.sdc.sdd.sdw.sxw.txt.htm.html.xhtml.xls.xlsx'] = "%unoconv%";
$mgmt_docoptions['.pdf'] = "-f pdf";
$mgmt_docoptions['.doc'] = "-f doc";
$mgmt_docoptions['.csv'] = "-f csv";
$mgmt_docoptions['.xls'] = "-f xls";
$mgmt_docoptions['.ppt'] = "-f ppt";
$mgmt_docoptions['.odt'] = "-f odt";
$mgmt_docoptions['.ods'] = "-f ods";
$mgmt_docoptions['.odp'] = "-f odp";
$mgmt_docconvert['.doc'] = array('.pdf', '.odt');
$mgmt_docconvert['.docx'] = array('.pdf', '.odt');
$mgmt_docconvert['.xls'] = array('.pdf', '.csv', '.ods');
$mgmt_docconvert['.xlsx'] = array('.pdf', '.csv', '.ods');
$mgmt_docconvert['.ppt'] = array('.pdf', '.odp');
$mgmt_docconvert['.pptx'] = array('.pdf', '.odp');
$mgmt_docconvert['.odt'] = array('.pdf', '.doc');
$mgmt_docconvert['.ods'] = array('.pdf', '.csv', '.xls');
$mgmt_docconvert['.odp'] = array('.pdf', '.ppt');

// define Image Preview using the GD Library and PHP (thumbnail generation)
// The GD Library only supports jpg, png and gif images, set value to "GD" if GD should be used
// Only JPG, PNG and GIF format can be generated as output
// Options:
// -s ... output size in width x height in pixel
// -f ... output format (file extension without dot [jpg, png, gif])
// -c ... cropy size
// -r ... rotate image
// -b ... image brightness
// -k .... image contrast
// -cs ... color space of image, e.g. RGB, CMYK, gray
// -flip ... flip image in the vertical direction
// -flop ... flop image in the horizontal direction
// -sharpen ... sharpen image, e.g. one pixel size sharpen: -sharpen 0x1.0
// -sketch ... skecthes an image, e.g. -sketch 0x20+120
// -sepia-tone ... apply -sepia-tone on image, e.g. -sepia-tone 80%
// -monochrome ... transform image to black and white
// $mgmt_imagepreview['.gif.jpg.jpeg.png'] = "GD";
// $mgmt_imageoptions['.jpeg.jpg']['original'] = "-s 180x180 -f jpg";
// $mgmt_imageoptions['.gif']['original'] = "-s 180x180 -f gif";
// $mgmt_imageoptions['.png']['original'] = "-s 180x180 -f png";

// define Image Preview using ImageMagick and GhostScript (thumbnail generation)
// If an image file is uploaded hyperCMS will try to generate a thumbnail file for preview:
//   $mgmt_imageoptions['.jpg.jpeg']['thumbnail'] = "-s 180x180 -f jpg";
// to define the supported formats for image editing please use:
//   $mgmt_imageoptions['.jpg.jpeg']['original'] = "-f jpg";
$mgmt_imagepreview['.ai.aai.act.art.art.arw.avs.bmp.bmp2.bmp3.cals.cgm.cin.cit.cmyk.cmyka.cpt.cr2.crw.cur.cut.dcm.dcr.dcx.dib.djvu.dng.dpx.emf.epdf.epi.eps.eps2.eps3.epsf.epsi.ept.exr.fax.fig.fits.fpx.gif.gplt.gray.hdr.hpgl.hrz.html.ico.info.inline.jbig.jng.jp2.jpc.jpe.jpg.jpeg.jxr.man.mat.miff.mono.mng.mpc.mpr.mrw.msl.mtv.mvg.nef.orf.otb.p7.palm.pam.clipboard.pbm.pcd.pcds.pcl.pcx.pdb.pdf.pef.pfa.pfb.pfm.pgm.picon.pict.pix.pjpeg.png.png8.png00.png24.png32.png48.png64.pnm.ppm.ps.ps2.ps3.psb.psd.psp.ptif.pwp.pxr.rad.raf.raw.rgb.rgba.rla.rle.sct.sfw.sgi.shtml.sid.mrsid.sparse-color.sun.svg.tga.tif.tiff.tim.ttf.txt.uil.uyvy.vicar.viff.wbmp.wdp.webp.wmf.wpg.x.xbm.xcf.xpm.xwd.x3f.ycbcr.ycbcra.yuv'] = "%convert%";
$mgmt_imageoptions['.jpg.jpeg']['thumbnail'] = "-s 180x180 -f jpg";
$mgmt_imageoptions['.jpg.jpeg']['original'] = "-f jpg";
$mgmt_imageoptions['.gif']['original'] = "-f gif";
$mgmt_imageoptions['.png']['original'] = "-f png";
// define additional download formats besides the original image
$mgmt_imageoptions['.jpg.jpeg']['1920x1080px'] = '-s 1920x1080 -f jpg';
$mgmt_imageoptions['.jpg.jpeg']['1024x768px'] = '-s 1024x768 -f jpg';
$mgmt_imageoptions['.jpg.jpeg']['640x480px'] = '-s 640x480 -f jpg';

// define Media Preview using FFMPEG (Audio/Video formats)
// If a video or audio file is uploaded hyperCMS will try to generate a smaler streaming video file
// for preview
$mgmt_mediapreview['.asf.avi.flv.mpg.mpeg.mp4.m4v.mp4v.m4a.m4b.m4p.m4r.mov.wmv.mp3.ogv.wav.vob'] = "%ffmpeg%";
$mgmt_mediaoptions['.flv'] = "-b:v 768k -s:v 480x320 -f flv -c:a libmp3lame -b:a 64k -ac 2 -ar 22050";
$mgmt_mediaoptions['.mp4'] = "-b:v 768k -s:v 480x320 -f mp4 -c:a libfaac -b:a 64k -ac 2 -c:v libx264 -mbd 2 -flags +loop+mv4 -cmp 2 -subcmp 2";
$mgmt_mediaoptions['.ogv'] = "-b:v 768k -s:v 480x320 -f ogv -c:a libvorbis -b:a 64k -ac 2";
$mgmt_mediaoptions['.webm'] = "-b:v 768k -s:v 480x320 -f webm -c:a libvorbis -b:a 64k -ac 2";
$mgmt_mediaoptions['.mp3'] = "-f mp3 -c:a libmp3lame -b:a 64k -ar 44100";

// define Metadata Injection
// YAMDI to inject metadata (play length) into the generated flash video file (FFMPEG discards metadata)
$mgmt_mediametadata['.flv'] = "%yamdi%";

// EXIFTOOL to inject metadata into the generated image file (ImageMagick discards metadata)
$mgmt_mediametadata['.3fr.3g2.3gp2.3gp.3gpp.acr.afm.acfm.amfm.ai.ait.aiff.aif.aifc.ape.arw.asf.avi.bmp.dib.btf.tiff.tif.chm.cos.cr2.crw.ciff.cs1.dcm.dc3.dic.dicm.dcp.dcr.dfont.divx.djvu.djv.dng.doc.dot.docx.docm.dotx.dotm.dylib.dv.dvb.eip.eps.epsf.ps.erf.exe.dll.exif.exr.f4a.f4b.f4p.f4v.fff.fff.fla.flac.flv.fpf.fpx.gif.gz.gzip.hdp.wdp.hdr.html.htm.xhtml.icc.icm.idml.iiq.ind.indd.indt.inx.itc.j2c.jpc.jp2.jpf.j2k.jpm.jpx.jpg.jpeg.k25.kdc.key.kth.la.lnk.m2ts.mts.m2t.ts.m4a.m4b.m4p.m4v.mef.mie.miff.mif.mka.mkv.mks.modd.mos.mov.qt.mp3.mp4.mpc.mpeg.mpg.m2v.mpo.mqv.mrw.mxf.nef.nmbtemplate.nrw.numbers.odb.odc.odf.odg,.odi.odp.ods.odt.ofr.ogg.ogv.orf.otf.pac.pages.pcd.pdf.pef.pfa.pfb.pfm.pgf.pict.pct.pjpeg.plist.pmp.png.jng.mng.ppm.pbm.pgm.ppt.pps.pot.potx.potm.ppsx.ppsm.pptx.pptm.psd.psb.psp.pspimage.qtif.qti.qif.ra.raf.ram.rpm.rar.raw.raw.riff.rif.rm.rv.rmvb.rsrc.rtf.rw2.rwl.rwz.so.sr2.srf.srw.svg.swf.thm.thmx.tiff.tif.ttf.ttc.vob.vrd.vsd.wav.webm.webp.wma.wmv.wv.x3f.xcf.xls.xlt.xlsx.xlsm.xlsb.xltx.xltm.xmp.zip'] = "%exiftool%";

// define max. file size in MB for thumbnail/video generation for certain file extensions
$mgmt_maxsizepreview['.pdf'] = 10;
$mgmt_maxsizepreview['.psd'] = 10;

// try to regenerate previews of multimedia files in explorer list if the thumbnail file doesn't exist
// this seeting can be used to avoid recurring kernel problems with GhostScript if ImageMagick fails to create a thumbnail of a PDF file
$mgmt_config['recreate_preview'] = false;


// ---------------------------------- Relational Database Connectivity -----------------------------------
// MySQL integration (or other relational databases via ODBC)
// if you specify the file "db_connect_rdbms.php" the MySQL DB Connectivity will be used.
// create a database named "hypercms" and run the SQL script for table definitions. tables will be in UTF-8.

// Define Database Access:
$mgmt_config['db_connect_rdbms'] = "db_connect_rdbms.php";
$mgmt_config['dbconnect'] = "mysql"; // values: mysql, odbc
$mgmt_config['dbhost'] = "%dbhost%";
$mgmt_config['dbuser'] = "%dbuser%";
$mgmt_config['dbpasswd'] = "%dbpasswd%";
$mgmt_config['dbname'] = "%dbname%";
$mgmt_config['dbcharset'] = "utf8";

// RDBMS Log
// Log queries and their executing time in logs/sql.log
$mgmt_config['rdbms_log'] = false;

// ------------------------------------ Tamino Database Connectivity -------------------------------------
// Tamino integration (or other databases)
// you can write and read all XML containers into and from the XML database Tamino from Software AG.
// if you specify the file "db_connect_tamino.php" the Tamino DB Connectivity will be used for all containers.
// create a database named "hypercms" and define the collection "live" and "work" using the schemas in
// the "tamino" directory. 
// $mgmt_config['db_connect_tamino'] = "db_connect_tamino.php";

// only if Tamino is used as database: 
// URL of hyperCMS database in Tamino (http://host/tamino/database/).
// please note: create a database named "hypercms" and define the collections "work" and "live".
// $mgmt_config['url_tamino'] = "http://atr10135/tamino/HyperCMS_Test/";

// ---------------------------------- SMTP Mail System Configuration -----------------------------------
// SMTP parameters for sending e-mails via a given SMTP server
$mgmt_config['smtp_host']     = "%smtp_host%";
$mgmt_config['smtp_username'] = "%smtp_username%";
$mgmt_config['smtp_password'] = "%smtp_password%";
$mgmt_config['smtp_port']     = "%smtp_port%";
$mgmt_config['smtp_sender']   = "%smtp_sender%";

// ------------------------------------------ LDAP Connectivity ------------------------------------------
// if you are using LDAP, you can specify the ldap_connect.php file where you can connect to an LDAP directory
// to verify users and update user settings.
// $ldap_connect = "";

// ------------------------------------ File System Permissions ------------------------------------------
// set permissions for files that will be created by hyperCMS in the file system. only important on UNIX systems.
// default value is 0757.
$mgmt_config['fspermission'] = 0757;
?>