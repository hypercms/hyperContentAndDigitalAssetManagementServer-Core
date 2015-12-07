<?php
// hyperCMS Main Configuration File
// Please add a slash at the end of each path entries, see examples.

// Attention: All variable values must not include "#"!

$mgmt_config = array();
$mgmt_lang_name = array();
$mgmt_lang_shortcut = array();
$mgmt_parser = array();
$mgmt_uncompress = array();
$mgmt_compress = array();
$mgmt_docpreview = array();
$mgmt_docoptions = array();
$mgmt_docconvert = array();
$mgmt_imagepreview = array();
$mgmt_imageoptions = array();
$mgmt_mediametadata = array();
$mgmt_maxsizepreview = array();

// ------------------------------------ Path settings ----------------------------------------

// Please note: use always slash (/) in path settings

// Depending how the user accessed our page we are setting our protocol
$mgmt_config['url_protocol'] = (!empty($_SERVER['HTTPS'])) ? 'https://' : 'http://';

// URL and asolute path to hyperCMS on your webserver
$mgmt_config['url_path_cms'] = $mgmt_config['url_protocol']."%url_path_cms%";
$mgmt_config['abs_path_cms'] = "%abs_path_cms%";

// URL and absolute path to the external repository on your webserver
// Used for the storage of external content management information
$mgmt_config['url_path_rep'] = $mgmt_config['url_protocol']."%url_path_rep%";
$mgmt_config['abs_path_rep'] = "%abs_path_rep%";

// URL and absolute path to the internal repository on your webserver
// Used for the storage of internal content management information
$mgmt_config['url_path_data'] = $mgmt_config['url_protocol']."%url_path_data%";
$mgmt_config['abs_path_data'] = "%abs_path_data%";


// ATTENTION: Usually you do not have to change the following path variables!

// Absolute path to the temporary directory
// Used for the storage of temporary data
$mgmt_config['url_path_temp'] = $mgmt_config['url_path_data']."temp/";
$mgmt_config['abs_path_temp'] = $mgmt_config['abs_path_data']."temp/";

// URL and absolute path to the view directory
// Used for the storage of views and files that can be accessed without being logged in to the system
$mgmt_config['url_path_view'] = $mgmt_config['url_path_temp']."view/";
$mgmt_config['abs_path_view'] = $mgmt_config['abs_path_temp']."view/";

// URL and absolute path to the template repository
// Do not change this settings!
// (e.g. http://www.yourdomain.com/hypercms/template/)
// (e.g. /home/domain/hypercms/template/)
$mgmt_config['url_path_template'] = $mgmt_config['url_path_data']."template/";
$mgmt_config['abs_path_template'] = $mgmt_config['abs_path_data']."template/";

// URL and absolute path to the content media repository
// For media mass storage over multiple harddisks the multimedia files can be
// distributed on the given devices (max. 10 devices).
// Special rules can be defined in $mgmt_config['abs_path_data']/media/getmedialocation.inc.php
// The configuration of hyperCMS for multiple storage devices will effect
// the development of templates in terms of referring to multimedia files.
// It is therefore recommended to configure getmedialocation to save all
// digital assests of a website on one harddisk.
// (e.g. http://www.yourdomain.com/data/media_cnt/)
// (e.g. /home/domain/data/media_cnt/)
$mgmt_config['url_path_media'] = $mgmt_config['url_path_rep']."media_cnt/";
$mgmt_config['abs_path_media'] = $mgmt_config['abs_path_rep']."media_cnt/";
// $mgmt_config['url_path_media'][1] = $mgmt_config['url_path_rep']."media_cnt1/";
// $mgmt_config['abs_path_media'][1] = $mgmt_config['abs_path_rep']."media_cnt1/";
// $mgmt_config['url_path_media'][2] = $mgmt_config['url_path_rep']."media_cnt2/";
// $mgmt_config['abs_path_media'][2] = $mgmt_config['abs_path_rep']."media_cnt2/";

// URL to services / Load balancing
// To enable load balancing for file upload, storing content and rendering files
// the system need to be alled on several physical servers.
// In order to enable load balancing an array providing the URL to the servers services
// need to be defined.
// One physical server provides the GUI and splits the load. Only this server need to be
// configured for load balancing.
// Make sure that all servers store the files in the same central repository and use the same database.
// There is no limit for the amount of physical servers in the load balancing array.
// (e.g. http://www.yourdomain.com/service/)
// $mgmt_config['url_path_service'][1] = "http://server1/hypercms/service/";
// $mgmt_config['url_path_service'][2] = "http://server2/hypercms/service/";

// URL and absolute path to the template media repository
// Do not change this settings!
// (e.g. http://www.yourdomain.com/data/media_tpl/)
// (e.g. /home/domain/data/media_tpl/)
$mgmt_config['url_path_tplmedia'] = $mgmt_config['url_path_rep']."media_tpl/";
$mgmt_config['abs_path_tplmedia'] = $mgmt_config['abs_path_rep']."media_tpl/";

// URL and absolute path to the page component repository
// Do not change this settings!
// (e.g. http://www.yourdomain.com/data/component/)
// (e.g. /home/domain/data/component/)
$mgmt_config['url_path_comp'] = $mgmt_config['url_path_rep']."component/";
$mgmt_config['abs_path_comp'] = $mgmt_config['abs_path_rep']."component/";

// URL and absolute path to the XML-content-repository
// Do not change this settings!
// (e.g. http://www.yourdomain.com/data/component/)
// (e.g. /home/domain/data/component/)
$mgmt_config['url_path_content'] = $mgmt_config['url_path_data']."content/";
$mgmt_config['abs_path_content'] = $mgmt_config['abs_path_data']."content/";

// URL and absolute path to the link index
// Do not change this settings!
// (e.g. http://www.yourdomain.com/data/link/)
// (e.g. /home/domain/data/link/)
$mgmt_config['url_path_link'] = $mgmt_config['url_path_rep']."link/";
$mgmt_config['abs_path_link'] = $mgmt_config['abs_path_rep']."link/";

// URL and absolute path to hyperCMS plugins
// Plugins are used to extend the system
// Do not change this settings!
// (e.g. http://www.yourdomain.com/plugin/)
// (e.g. /home/domain/hyperCMS/plugin/)
$mgmt_config['url_path_plugin'] = $mgmt_config['url_path_cms']."plugin/";
$mgmt_config['abs_path_plugin'] = $mgmt_config['abs_path_cms']."plugin/";

// ------------------------------------ GUI settings ----------------------------------------

// Allow (true) or disable (false) html tags used in text editor for unformatted text
$mgmt_config['editoru_html'] = true;

// Define videoplayer name, leave empty for the default player (VIDEO.JS) or use "projekktor" as alternative
$mgmt_config['videoplayer'] = "";

// Define the default view for object editing
// "formedit": use form for content editing
// "cmsview": view of page based on template, includes hyperCMS specific code (buttons)
// "inlineview": view of page based on template, includes hyperCMS specific code (buttons) and inline text editing
$mgmt_config['objectview'] = "inlineview";

// Define standard view for explorer object list ("detail" = detail view; "small", "medium", "large" = thumbnail gallery view)
$mgmt_config['explorerview'] = "detail";

// How many items (folders and objects) should be displayed in the explorer object list initally
$mgmt_config['explorer_list_maxitems'] = 500;

// Define if sidebar for object preview should be displayed (true) by default or not (false)
$mgmt_config['sidebar'] = true;

// Define if chat should be enabled (true) or disabled (false)
$mgmt_config['chat'] = true;

// Define standard mail link type ("access" = access-link; "download" = download-link)
$mgmt_config['maillink'] = "download";

// Define name of the theme/design for the UI 
// Themes are located in directory hypercms/theme/
$mgmt_config['theme'] = "";

// Define alternative logo (image file name) for top frame. the file must be in cms/images
$mgmt_config['logo_top'] = "";

// Show (true) or hide (false) information boxes to provide additional information to the user
$mgmt_config['showinfobox'] = true;

// Define home boxes to show for each user if no indiviual selection has been made (use ; as seperarator)
// Home boxes are located in directory hypercms/box/
$mgmt_config['homeboxes'] = "news;tasks;recent_objects;up_and_downloads;recent_downloads;recent_uploads";

// Define URL to show in welcome/news home box
$mgmt_config['welcome'] = "https://cms.hypercms.net/home/update_info_en.xhtml";

// Check for duplicate entries based on MD5 hash of files (true) or not (false)
$mgmt_config['check_duplicates'] = true;

// Enable AutoSave to autoamtically save the text of the edior each given value in seconds
// Set value to 0 to disable autosave
$mgmt_config['autosave'] = 0;

// --------------------------------- Language settings ---------------------------------------

// Language Settings
// Define the languages which should be active in hyperCMS
// Delete or comment language entries that should not be visible

// Albanian
$mgmt_lang_name['sq'] = "Albanian";
$mgmt_lang_shortcut['sq'] = "sq";

// Arabic
$mgmt_lang_name['ar'] = "Arabic";
$mgmt_lang_shortcut['ar'] = "ar";

// Czech
$mgmt_lang_name['cz'] = "Czech";
$mgmt_lang_shortcut['cz'] = "cz";

// Bengali
$mgmt_lang_name['bn'] = "Bengali";
$mgmt_lang_shortcut['bn'] = "bn";

// Bulgarian
$mgmt_lang_name['bg'] = "Bulgarian";
$mgmt_lang_shortcut['bg'] = "bg";

// Chinese (simplified)
$mgmt_lang_name['zh-s'] = "Chinese (simplified)";
$mgmt_lang_shortcut['zh-s'] = "zh-s";

// Danish
$mgmt_lang_name['da'] = "Danish";
$mgmt_lang_shortcut['da'] = "da";

// Dutch
$mgmt_lang_name['nl'] = "Dutch";
$mgmt_lang_shortcut['nl'] = "nl";

// English
$mgmt_lang_name['en'] = "English";
$mgmt_lang_shortcut['en'] = "en";

// Finnish
$mgmt_lang_name['fi'] = "Finnish";
$mgmt_lang_shortcut['fi'] = "fi";

// French
$mgmt_lang_name['fr'] = "French";
$mgmt_lang_shortcut['fr'] = "fr";

// German
$mgmt_lang_name['de'] = "German";
$mgmt_lang_shortcut['de'] = "de";

// Greek
$mgmt_lang_name['el'] = "Greek";
$mgmt_lang_shortcut['el'] = "el";

// Hebrew
$mgmt_lang_name['he'] = "Hebrew";
$mgmt_lang_shortcut['he'] = "he";

// Hindi
$mgmt_lang_name['hi'] = "Hindi";
$mgmt_lang_shortcut['hi'] = "hi";

// Hungarian
$mgmt_lang_name['hu'] = "Hungarian";
$mgmt_lang_shortcut['hu'] = "hu";

// Indonesian
$mgmt_lang_name['id'] = "Indonesian";
$mgmt_lang_shortcut['id'] = "id";

// Italian
$mgmt_lang_name['it'] = "Italian";
$mgmt_lang_shortcut['it'] = "it";

// Japanese
$mgmt_lang_name['ja'] = "Japanese";
$mgmt_lang_shortcut['ja'] = "ja";

// Korean
$mgmt_lang_name['ko'] = "Korean";
$mgmt_lang_shortcut['ko'] = "ko";

// Malay
$mgmt_lang_name['ms'] = "Malay";
$mgmt_lang_shortcut['ms'] = "ms";

// Norwegian
$mgmt_lang_name['no'] = "Norwegian";
$mgmt_lang_shortcut['no'] = "no";

// Malay
$mgmt_lang_name['ko'] = "Malay";
$mgmt_lang_shortcut['ko'] = "ko";

// Polish
$mgmt_lang_name['pl'] = "Polish";
$mgmt_lang_shortcut['pl'] = "pl";

// Portuguese
$mgmt_lang_name['pt'] = "Portuguese";
$mgmt_lang_shortcut['pt'] = "pt";

// Romanian
$mgmt_lang_name['ro'] = "Romanian";
$mgmt_lang_shortcut['ro'] = "ro";

// Russian
$mgmt_lang_name['ru'] = "Russian";
$mgmt_lang_shortcut['ru'] = "ru";

// Serbian
$mgmt_lang_name['sr'] = "Serbian";
$mgmt_lang_shortcut['sr'] = "sr";

// Slovak
$mgmt_lang_name['sk'] = "Slovak";
$mgmt_lang_shortcut['sk'] = "sk";

// Slovenian
$mgmt_lang_name['sl'] = "Slovenian";
$mgmt_lang_shortcut['sl'] = "sl";

// Spanish
$mgmt_lang_name['es'] = "Spanish";
$mgmt_lang_shortcut['es'] = "es";
// Swedish
$mgmt_lang_name['sv'] = "Swedish";
$mgmt_lang_shortcut['sv'] = "sv";

// Thai
$mgmt_lang_name['th'] = "Thai";
$mgmt_lang_shortcut['th'] = "th";

// Turkish
$mgmt_lang_name['tr'] = "Turkish";
$mgmt_lang_shortcut['tr'] = "tr";

// Somali
$mgmt_lang_name['so'] = "Somali";
$mgmt_lang_shortcut['so'] = "so";

// Swedish
$mgmt_lang_name['sv'] = "Swedish";
$mgmt_lang_shortcut['sv'] = "sv";

// Ukrainian
$mgmt_lang_name['uk'] = "Ukrainian";
$mgmt_lang_shortcut['uk'] = "uk";

// Urdu
$mgmt_lang_name['ur'] = "Urdu";
$mgmt_lang_shortcut['ur'] = "ur";

// Default Language
$mgmt_lang_shortcut_default = "en";

// --------------------------------- Technical parameters ---------------------------------------

// Define operating system (OS) on content management server ("UNIX" for all UNIX and Linux OS or "WIN" for MS Windows)
// Please note: MS PWS cannot handle multiple HTTP-requests at the same time! since version 3.0 PWS will not be supplied anymore
$mgmt_config['os_cms'] = "%os_cms%";

// Define date format for error logging and get local date today (jjjj-mm-dd)
$mgmt_config['today'] = date ("Y-m-d H:i", time());

// Supported Applications
// Set value to true if your content management server supports rendering of objects
// using program- and script-technologies like PHP, JSP, ASP. Otherwise set false
$mgmt_config['application']['php'] = true;
$mgmt_config['application']['jsp'] = false;
$mgmt_config['application']['asp'] = false;

// File Upload
// Maximum file size in MB allowed for upload. set value to 0 to enable all sizes
// Check webserver and php.ini restrictions too!
$mgmt_config['maxfilesize'] = 0;

// ZIP File
// Maximum file size to be compressed in ZIP file in MB. set value to 0 to disable limit
$mgmt_config['maxzipsize'] = 2000;

// Maximum digits for file names (applies for createobject and uploadfile).
$mgmt_config['max_digits_filename'] = 200;

// Which types of files (file extensions)are not allowed for upload, example ".asp.jsp.php.pl.sql"
$mgmt_config['exclude_files'] = ".php.pl.jsp.asp.aspx.exe.sql.sh.bash";

// Save Metadata to Files
// Save IPTC tags to image files (true) or not (false)
$mgmt_config['iptc_save'] = true;

// Save XMP tags to image files (true) or not (false)
$mgmt_config['xmp_save'] = true;

// Save ID3 tags to audio files (true) or not (false)
$mgmt_config['id3_save'] = true;

// Versioning of Containers
// Save versions of published containers and media files (true) or disable versioning (false)
$mgmt_config['contentversions'] = true;

// Save versions of saved containers and media files (true) or do not create a new version (false)
$mgmt_config['contentversions_all'] = false;

// Public Download
// Allow access to download and wrapper links without logon session (true) or not (false)
// This setting must be enabled if users want to provide wrapper or download links to the public
$mgmt_config['publicdownload'] = true;

// FTP Upload
// Allow FTP file download (true) or not (false)
$mgmt_config['ftp_download'] = true;

// Document Viewer
// Allow the view of documents by the doc viewer (true) or not (false)
$mgmt_config['docviewer'] = true;

// Strong Passwords
// Enable (true) or disable (false) strong passwords for users
// If enabled, passwords will be checked regarding minimum security requirements
$mgmt_config['strongpassword'] = false;

// Encryption
// Encryption strength (weak, standard, strong)
$mgmt_config['crypt_level'] = "standard";

// Key used for en/decryption of temporary system data (key length must be 8, 16, or 32)
$mgmt_config['crypt_key'] = "h1y2p3e4r5c6m7s8";

// Data and file encryption is always based on strong AES 256
// You need to define a key with 32 digits for en/decryption
// If a key server is used please use the commented line to access the key:
// $mgmt_config['aes256_key'] = file_get_contents ("https://key-server/aes256-key.key");
$mgmt_config['aes256_key'] = "h1y2p3e4r5c6m7s8s9m0c1r2e3p4y5h6";

// Template code
// Cleaning level of template code from none = 0 to strong = 3 (no cleaning = 0, basic set of disabled functions = 1, 1 + file access functions = 2, 2 + include functions = 3)
$mgmt_config['template_clean_level'] = 1;

// Logon Timeout
// How many minutes will an IP and user combination be locked after 10 failed attempts
// A value of 0 means there is no timeout
$mgmt_config['logon_timeout'] = 10; 

// CSRF Protection
// Define allowed requests per minute.
$mgmt_config['requests_per_minute'] = 500;

// Security Token
// Define lifetime of security token in seconds (min. 60 sec.)
$mgmt_config['token_lifetime'] = 86400;

// Instances
// Instances don't share the same database, internal and external repository.
// Enable multiple hyperCMS instances by providing a path to the instance configuration directory.
// For distributed systems the directory must be located on a central resource that can be accessed by every system node.
$mgmt_config['instances'] = "%instances%";

// Enable writing of session data for third party load balancers in order to enable session synchronization
$mgmt_config['writesessiondata'] = false;

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
// If more extension will be supported by the same executable, use "." as delimiter. 

// Define PDF parsing (Extension: pdf)
// PDF documents could be parsed via XPDF (binary) which is platform independent
// or a PHP class can be used for parsing (causes sometimes troubles on Win32 OS).
// For XPDF define value "xpdf", for PHP class define value "php".
// The path to the executable is usually /usr/bin/pdftotext.
$mgmt_parser['.pdf'] = "%pdftotext%";

// Define MS Word parsing (Extension: doc)
// To parse Word Documents you have to define the path to ANTIWORD executable.
// The path to the executable is usually /usr/bin/antiword.
$mgmt_parser['.doc'] = "%antiword%";

// Define Uncompression (Extension: gz)
// To uncompress files you have to define the path to the UNZIP executable.
// It is recommended tu use GUNZIP (on Linux OS) to decompress files. GUNZIP can currently 
// decompress files created by gzip, zip, compress, compress -H or pack.
// The path to the executable is usually /usr/bin/gunzip.
$mgmt_uncompress['.gz'] = "%gunzip%";

// Define ZIP-Uncompression (Extension: zip)
// If a ZIP-file has several members UNZIP should be used to uncompress the ZIP-file.
// The path to the executable is usually /usr/bin/unzip.
$mgmt_uncompress['.zip'] = "%unzip%";

// Define ZIP-Compression
// To compress files to a ZIP-file.
// The path to the executable is usually /usr/bin/zip.
$mgmt_compress['.zip'] = "%zip%";

// Define document conversion using UNOCONV
// Convert between any document format supported by OpenOffice (use command 'unoconv --show' for details).
// ATTENTION: The webserver user (e.g. www-data) needs to have write permission in his home directory (e.g. /var/www)!
// The path to the executable is usually /usr/bin/unoconv
$mgmt_docpreview['.bib.doc.docx.dot.ltx.odd.odt.odg.odp.ods.ppt.pptx.pxl.psw.pts.rtf.sda.sdc.sdd.sdw.sxw.txt.htm.html.xhtml.xls.xlsx'] = "%unoconv%";
// Define the supported target formats for documents:
$mgmt_docoptions['.pdf'] = "-f pdf";
$mgmt_docoptions['.doc'] = "-f doc";
$mgmt_docoptions['.csv'] = "-f csv";
$mgmt_docoptions['.xls'] = "-f xls";
$mgmt_docoptions['.ppt'] = "-f ppt";
$mgmt_docoptions['.odt'] = "-f odt";
$mgmt_docoptions['.ods'] = "-f ods";
$mgmt_docoptions['.odp'] = "-f odp";
// Define the mapping of the source and target formats for documents:
$mgmt_docconvert['.doc'] = array('.pdf', '.odt');
$mgmt_docconvert['.docx'] = array('.pdf', '.odt');
$mgmt_docconvert['.xls'] = array('.pdf', '.csv', '.ods');
$mgmt_docconvert['.xlsx'] = array('.pdf', '.csv', '.ods');
$mgmt_docconvert['.ppt'] = array('.pdf', '.odp');
$mgmt_docconvert['.pptx'] = array('.pdf', '.odp');
$mgmt_docconvert['.odt'] = array('.pdf', '.doc');
$mgmt_docconvert['.ods'] = array('.pdf', '.csv', '.xls');
$mgmt_docconvert['.odp'] = array('.pdf', '.ppt');

// Define Image Preview using the GD Library or ImageMagick

// Options:
// -s ... output size in width x height in pixel (WxH)
// -f ... output format (file extension without dot [jpg, png, gif])
// -d ... image density (DPI) for vector graphics and EPS files, common values are 72, 96 dots per inch for screen, while printers typically support 150, 300, 600, or 1200 dots per inch
// -q ... quality for compressed image formats like JPEG (1 to 100)
// -c ... crop x and y coordinates (XxY)
// -b ... image brightness
// -k .... image contrast
// -cs ... color space of image, e.g. RGB, CMYK, gray
// -rotate ... rotate image
// -fv ... flip image in the vertical direction
// -fh ... flop image in the horizontal direction
// -sharpen ... sharpen image, e.g. one pixel size sharpen: -sharpen 0x1.0
// -sketch ... skecthes an image, e.g. -sketch 0x20+120
// -sepia-tone ... apply -sepia-tone on image, e.g. -sepia-tone 80%
// -monochrome ... transform image to black and white
// -wm ... watermark in watermark image->positioning->geometry, e.g. image.png->topleft->+30

// Define image preview using the GD Library and PHP (thumbnail generation)
// The GD Library only supports jpg, png and gif images as output, set value to "GD" to use it.
// $mgmt_imagepreview['.gif.jpg.jpeg.png'] = "GD";

// Define image preview using ImageMagick and GhostScript (thumbnail generation)
// The path to the executable is usually /usr/bin/convert
$mgmt_imagepreview['.ai.aai.act.art.art.arw.avs.bmp.bmp2.bmp3.cals.cgm.cin.cit.cmyk.cmyka.cpt.cr2.crw.cur.cut.dcm.dcr.dcx.dib.djvu.dng.dpx.emf.epdf.epi.eps.eps2.eps3.epsf.epsi.ept.exr.fax.fig.fits.fpx.gif.gplt.gray.hdr.hpgl.hrz.ico.info.inline.jbig.jng.jp2.jpc.jpe.jpg.jpeg.jxr.man.mat.miff.mono.mng.mpc.mpr.mrw.msl.mtv.mvg.nef.orf.otb.p7.palm.pam.clipboard.pbm.pcd.pcds.pcl.pcx.pdb.pdf.pef.pfa.pfb.pfm.pgm.picon.pict.pix.pjpeg.png.png8.png00.png24.png32.png48.png64.pnm.ppm.ps.ps2.ps3.psb.psd.psp.ptif.pwp.pxr.rad.raf.raw.rgb.rgba.rla.rle.sct.sfw.sgi.shtml.sid.mrsid.sparse-color.sun.svg.tga.tif.tiff.tim.ttf.txt.uil.uyvy.vicar.viff.wbmp.wdp.webp.wmf.wpg.x.xbm.xcf.xpm.xwd.x3f.ycbcr.ycbcra.yuv'] = "%convert%";

// If an image file is uploaded hyperCMS will try to generate a thumbnail file for preview:
$mgmt_imageoptions['.jpg.jpeg']['thumbnail'] = "-s 180x180 -f jpg";
// Define the supported target formats for image editing:
$mgmt_imageoptions['.jpg.jpeg']['original'] = "-f jpg";
$mgmt_imageoptions['.gif']['original'] = "-f gif";
$mgmt_imageoptions['.png']['original'] = "-f png";
// Define additional download formats besides the original image:
$mgmt_imageoptions['.jpg.jpeg']['1920x1080px'] = '-s 1920x1080 -f jpg';
$mgmt_imageoptions['.jpg.jpeg']['1024x768px'] = '-s 1024x768 -f jpg';
$mgmt_imageoptions['.jpg.jpeg']['640x480px'] = '-s 640x480 -f jpg';

// Define media preview using FFMPEG (Audio/Video formats)
// If a video or audio file is uploaded, hyperCMS will try to generate a smaler streaming video file for preview.

// Audio Options:
// -ac ... number of audio channels
// -an ... disable audio
// -ar ... audio sampling frequency (default = 44100 Hz)
// -b:a ... audio bitrate (default = 64k)
// -c:a ... audio codec (e.g. libmp3lame, libfaac, libvorbis)
// Video Options:
// -b:v ... video bitrate in bit/s (default = 200 kb/s)
// -c:v ... video codec (e.g. libx264)
// -cmp ... full pel motion estimation compare function (used for mp4)
// -f ... force file format (like flv, mp4, ogv, webm, mp3)
// -flags ... specific options for video encoding
// -mbd ... macroblock decision algorithm (high quality mode)
// -r ... frame rate in Hz (default = 25)
// -s:v ... frame size in pixel (WxH)
// -sh ... sharpness (blur -1 up to 1 sharpen)
// -gbcs ... gamma, brightness, contrast, saturation (neutral values are 0.0:1:0:0.0:1.0)
// -wm .... watermark image and watermark positioning (PNG-file-reference->positioning [topleft, topright, bottomleft, bottomright] e.g. image.png->topleft)
// -rotate ... rotate video
// -fv ... flip video in vertical direction
// -fh ... flop video in horizontal direction
      
// The path to the executable is usually /usr/bin/ffmpeg
$mgmt_mediapreview['.3g2.3gp.4xm.a64.aac.ac3.act.adf.adts.adx.aea.aiff.alaw.alsa.amr.anm.apc.ape.apr.asf.asf_stream.ass.au.audio.avi.avm2.avs.bethsoftvid.bfi.bin.bink.bit.bmv.c93.caf.cavsvideo.cdg.cdxl.crc.daud.dfa.dirac.dnxhd.dsicin.dts.dv.dv1394.dvd.dxa.dwd.ea.ea_cdata.eac3.f32be.f32le.f4v.f64be.f64le.fbdev.ffm.ffmetadata.film_cpk.filmstrip.flac.flic.flv.framecrc.framemd5.g722.g723_1.g729.gif.gsm.gxf.h261.h263.h264.hls.ico.idcin.idf.iff.ilbc.image2.image2pipe.ingenient.ipmovie.ipod.ismv.iss.iv8.ivf.jack.jacosub.jv.la.latm.lavfi.libcdio.libdc1394.lmlm4.loas.lxf.m4a.m4b.m4p.m4r.m4v.matroska.md5.mgsts.microdvd.mid.mj2.mjpeg.mkvtimestamp_v2.mlp.mm.mmf.mov.mp2.mp3.mp4.mp4v.mpc.mpc8.mpeg.mpg.mpeg1video.mpeg2video.mpegts.mpegtsraw.mpegvideo.mpjpeg.msnwctcp.mtv.mulaw.mvi.mxf.mxf_d10.mxg.nc.nsv.null.nut.nuv.oga.ogg.ogv.oma.oss.ots.pac.paf.pmp.psp.psxstr.pva.qcp.r3d.ra.rawvideo.rcv.realtext.rka.rl2.rm.roq.rpl.rso.rtp.rtsp.s16be.s16le.s24be.s24le.s32be.s32le.s8.sami.sap.sbg.sdl.sdp.segment.shn.siff.smjpeg.smk.smush.sol.sox.spdif.srt.subviewer.svcd.swa.swf.thp.tiertexseq.tmv.truehd.tta.tty.txd.u16be.u16le.u24be.u24le.u32be.u32le.u8.vc1.vc1test.vcd.vmd.vob.voc.vox.vqf.w64.wav.wc3movie.webm.webvtt.wma.wmv.wsaud.wsvqa.wtv.wv.x11grab.xa.xbin.xmv.xwma.yop.yuv4mpegpipe'] = "%ffmpeg%";
// If a video or audio file is uploaded hyperCMS will try to generate a thumbnail video/audio file for preview
$mgmt_mediaoptions['thumbnail-video'] = "-b:v 768k -s:v 480x320 -f mp4 -c:a libfaac -b:a 64k -ac 2 -c:v libx264 -mbd 2 -flags +loop+mv4 -cmp 2 -subcmp 2"; 
$mgmt_mediaoptions['thumbnail-audio'] = "-f mp3 -c:a libmp3lame -b:a 64k -ar 22500";
// Define the supported target formats for video/audio editing (please use the variables %videobitrate%, %audiobitrate%, %width%, %height%)
// Video formats:
$mgmt_mediaoptions['.flv'] = "-b:v %videobitrate% -s:v %width%x%height% -f flv -c:a libmp3lame -b:a %audiobitrate% -ac 2 -ar 22050";
$mgmt_mediaoptions['.mp4'] = "-b:v %videobitrate% -s:v %width%x%height% -f mp4 -c:a libfaac -b:a %audiobitrate% -ac 2 -c:v libx264 -mbd 2 -flags +loop+mv4 -cmp 2 -subcmp 2";
$mgmt_mediaoptions['.mpeg'] = "-b:v %videobitrate% -s:v %width%x%height% -f mpeg -c:v h263 -c:a aac -b:a %audiobitrate% -ac 2 -target ntsc-vcd";
$mgmt_mediaoptions['.ogv'] = "-b:v %videobitrate% -s:v %width%x%height% -f ogg -c:a libvorbis -b:a %audiobitrate% -ac 2";
$mgmt_mediaoptions['.webm'] = "-b:v %videobitrate% -s:v %width%x%height% -f webm -c:a libvorbis -b:a %audiobitrate% -ac 2";
// Audio formats:
$mgmt_mediaoptions['.flac'] = "-f flac -c:a flac -b:a %audiobitrate%";
$mgmt_mediaoptions['.mp3'] = "-f mp3 -c:a libmp3lame -b:a %audiobitrate%";
$mgmt_mediaoptions['.oga'] = "-f ogg -c:a libvorbis -b:a %audiobitrate%";
$mgmt_mediaoptions['.wav'] = "-c:a pcm_u8 -b:a %audiobitrate%";

// Define Metadata Injection
// YAMDI to inject metadata (play length) into the generated flash video file (FFMPEG discards metadata)
// The path to the executable is usually /usr/bin/yamdi
$mgmt_mediametadata['.flv'] = "%yamdi%";

// Use EXIFTOOL to inject metadata into the generated image file (ImageMagick discards metadata)
// The path to the executable is usually /usr/bin/exiftool
$mgmt_mediametadata['.3fr.3g2.3gp2.3gp.3gpp.acr.afm.acfm.amfm.ai.ait.aiff.aif.aifc.ape.arw.asf.avi.bmp.dib.btf.tiff.tif.chm.cos.cr2.crw.ciff.cs1.dcm.dc3.dic.dicm.dcp.dcr.dfont.divx.djvu.djv.dng.doc.dot.docx.docm.dotx.dotm.dylib.dv.dvb.eip.eps.epsf.ps.erf.exe.dll.exif.exr.f4a.f4b.f4p.f4v.fff.fff.fla.flac.flv.fpf.fpx.gif.gz.gzip.hdp.wdp.hdr.html.htm.xhtml.icc.icm.idml.iiq.ind.indd.indt.inx.itc.j2c.jpc.jp2.jpf.j2k.jpm.jpx.jpg.jpeg.k25.kdc.key.kth.la.lnk.m2ts.mts.m2t.ts.m4a.m4b.m4p.m4v.mef.mie.miff.mif.mka.mkv.mks.modd.mos.mov.qt.mp3.mp4.mpc.mpeg.mpg.m2v.mpo.mqv.mrw.mxf.nef.nmbtemplate.nrw.numbers.odb.odc.odf.odg,.odi.odp.ods.odt.ofr.ogg.ogv.orf.otf.pac.pages.pcd.pdf.pef.pfa.pfb.pfm.pgf.pict.pct.pjpeg.plist.pmp.png.jng.mng.ppm.pbm.pgm.ppt.pps.pot.potx.potm.ppsx.ppsm.pptx.pptm.psd.psb.psp.pspimage.qtif.qti.qif.ra.raf.ram.rpm.rar.raw.raw.riff.rif.rm.rv.rmvb.rsrc.rtf.rw2.rwl.rwz.so.sr2.srf.srw.svg.swf.thm.thmx.tiff.tif.ttf.ttc.vob.vrd.vsd.wav.webm.webp.wma.wmv.wv.x3f.xcf.xls.xlt.xlsx.xlsm.xlsb.xltx.xltm.xmp.zip'] = "%exiftool%";

// Define max. file size in MB for thumbnail/video generation for certain file extensions
$mgmt_maxsizepreview['.pdf'] = 10;
$mgmt_maxsizepreview['.psd'] = 10;
$mgmt_maxsizepreview['.doc'] = 4;
$mgmt_maxsizepreview['.docx'] = 4;
$mgmt_maxsizepreview['.ppt'] = 4;
$mgmt_maxsizepreview['.pptx'] = 4;
$mgmt_maxsizepreview['.xls'] = 4;
$mgmt_maxsizepreview['.xlsx'] = 4;

// Try to regenerate previews of multimedia files in explorer list if the thumbnail file doesn't exist.
// This seeting can be used to avoid recurring kernel problems with GhostScript if ImageMagick fails to create a thumbnail of a PDF file.
$mgmt_config['recreate_preview'] = false;

// -------------------------------- Relational Database Connectivity ----------------------------------

// MySQL integration (or other relational databases via ODBC)
// The file "db_connect_rdbms.php" provides MySQL and ODBC DB Connectivity.
// Run the installation or create a database with UTF-8 support and run the SQL script for table definitions manually.

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

// --------------------------------- SMTP Mail System Configuration -----------------------------------

// SMTP parameters for sending e-mails via a given SMTP server
$mgmt_config['smtp_host']     = "%smtp_host%";
$mgmt_config['smtp_username'] = "%smtp_username%";
$mgmt_config['smtp_password'] = "%smtp_password%";
$mgmt_config['smtp_port']     = "%smtp_port%";
$mgmt_config['smtp_sender']   = "%smtp_sender%";

// ------------------------------------ Import / Export ----------------------------------------

// Define password for Import and Export
$mgmt_config['passcode'] = "";

// --------------------------------------- App Keys --------------------------------------------

// Youtube integration
// Please provide Google API credentials in order to upload videos to Youtube
$mgmt_config['youtube_oauth2_client_id'] = "";
$mgmt_config['youtube_oauth2_client_secret'] = "";
$mgmt_config['youtube_appname'] = "";

// DropBox integration
// Keep in mind that the domain needs to be added to your Dropbox developer account in order to use the app-key
// or you create your own Dropbox app-key and set it here
$mgmt_config['dropbox_appname'] = "";
$mgmt_config['dropbox_appkey'] = "";

// -------------------------------------- LDAP Connectivity -------------------------------------------

// If you are using LDAP, you can specify the ldap_connect.php file where you can connect to an LDAP directory
// to verify users and update user settings.
// $ldap_connect = "";

// ----------------------------------- File System Permissions -----------------------------------------

// Set permissions for files that will be created by hyperCMS in the file system. Only important on UNIX systems.
// Default value is 0757.
$mgmt_config['fspermission'] = 0757;
?>