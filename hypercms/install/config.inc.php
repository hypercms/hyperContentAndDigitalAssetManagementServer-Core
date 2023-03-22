<?php
// hyperCMS Main Configuration File
// Please add a slash at the end of each path entries, see examples

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
$mgmt_config['url_protocol'] = !empty ($_SERVER['HTTPS']) ? 'https://' : 'http://';

// Define the URL for requests to the local host, usually "http://localhost"
// If left empty the URLs defined below will be used
$mgmt_config['localhost'] = "";

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

// URL and absolute path to the content media repository
// For media mass storage of the multimedia files on multiple HDDs/SSDs an array can be defined for max. 10 devices.
// Special rules can be defined in $mgmt_config['abs_path_data']/media/getmedialocation.inc.php
// Be aware that the configuration for multiple storage devices will effect the development of 
// templates in terms of referring to multimedia files.
// It is therefore recommended to configure getmedialocation to save all digital assests of a website on one harddisk.
// (e.g. http://www.yourdomain.com/data/media_cnt/)
// (e.g. /home/domain/data/media_cnt/)
$mgmt_config['url_path_media'] = $mgmt_config['url_path_rep']."media_cnt/";
$mgmt_config['abs_path_media'] = $mgmt_config['abs_path_rep']."media_cnt/";
// harddisk/mountpoint array (start index must be 1 and the index must not be higher than 10)
// $mgmt_config['url_path_media'][1] = $mgmt_config['url_path_rep']."media_cnt1/";
// $mgmt_config['abs_path_media'][1] = $mgmt_config['abs_path_rep']."media_cnt1/";
// $mgmt_config['url_path_media'][2] = $mgmt_config['url_path_rep']."media_cnt2/";
// $mgmt_config['abs_path_media'][2] = $mgmt_config['abs_path_rep']."media_cnt2/";

// ------------------------------------ Cloud storage settings ----------------------------------------

// ATTENTION: The following settings only applies for the Enterprise Edition!
// If you are using AWS S3 or Google Cloud as media repository, the system will save all media files
// using the SDK client of the cloud service provider.
// Please note, that you need a cloud service account with your cloud service provider.
// In order to connect with the cloud service, you need provide the credentials for the cloud service.

// For AWS S3 use:
// Enable (true) or disable (false) the AWS S3 Cloud API
$mgmt_config['aws_api'] = false;
// Provide credentials for access
$mgmt_config['aws_access_key_id'] = "";
$mgmt_config['aws_secret_access_key'] = "";
// Provide region code, see also: http://docs.aws.amazon.com/AWSEC2/latest/UserGuide/using-regions-availability-zones.html#concepts-available-regions
$mgmt_config['aws_region'] = "";
// Provide the name of your AWS S3 bucket (mandatory for cloud storage)
$mgmt_config['aws_bucket'] = "";

// For Google Cloud Platform use:
// Enable (true) or disable (false) the Google Cloud API
$mgmt_config['gs_api'] = false;
// Provide credentials for Google API access (path to Google API JSON key file)
$mgmt_config['gs_access_json'] = "";
// Define default language code to be used for speech-to-text
// See all the supported lanuage codes here: https://cloud.google.com/speech-to-text/docs/languages
$mgmt_config['gs_speech2text_langcode'] = "en-US";
// Provide region code for the storage, see also: https://cloud.google.com/compute/docs/zones
$mgmt_config['gs_region'] = "";
// Provide the name of your Google Cloud Storage bucket (mandatory for cloud storage)
$mgmt_config['gs_bucket'] = "";
// Optionally provide the path to autoload.php of the Google Cloud API (https://github.com/GoogleCloudPlatform/google-cloud-php) if you are not using the included API
// $mgmt_config['google_cloud_api'] = "vendor/autoload.php";

// For MS Azure Blob Storage use:
// Enable (true) or disable (false) the MS Azure Cloud API
$mgmt_config['azure_api'] = false;
// Provide credentials for access (connection statement: DefaultEndpointsProtocol=https;AccountName=myAccount;AccountKey=myKey;)
$mgmt_config['azure_access_key'] = "";
// Provide the name of your Azure container (mandatory for cloud storage)
$mgmt_config['azure_container'] = "";

// Define daily synchronization for delayed saving of media files in cloud storage (true) or save media files immediately (false)
// If the daily synchronization has been enabled the media files will not be saved in the cloud storage immediately!
$mgmt_config['storage_dailycloudsnyc'] = false;

// Define a time frame in hours for the synchronization with the cloud storage in order to keep/store media files locally based on their age
$mgmt_config['storage_synctime']  = 24;

// Keep the temporary preview images (true) or delete the images after 24 hours of age (false)
// In order to reduce read and transfer activities with the Cloud Storage it might be useful to keep the preview images locally,
// especially if you use the face recognition service.
$mgmt_config['keep_previews'] = false;

// ------------------------------------ Load balancing settings ----------------------------------------

// URL to services / Load balancing
// To enable load balancing for file upload, storing content and rendering files the system need to be installed on multiple servers.
// In order to enable load balancing an array providing the URL to the servers services need to be defined.
// One physical server provides the GUI and splits the load. Only this server need to be configured for load balancing.
// Make sure that all servers store the files in the same central repository and use the same database.
// There is no limit for the amount of physical servers in the load balancing array.
// Example: array ("http://server1.domain.com/hypercms/service/", "http://server2.domain.com/hypercms/service/")
$mgmt_config['url_path_service'] = array();

// ------------------------------------ GUI settings ----------------------------------------

// Enable (true) or disable (false) html tags used in text editor for unformatted text
$mgmt_config['editoru_html'] = true;

// Define the alternative image editor "minipaint", leave empty for the default image editor of the system
$mgmt_config['imageeditor'] = "minipaint";

// Define the default view for object editing
// "formedit": use form for content editing
// "cmsview": view of page based on template, includes hyperCMS specific code (buttons)
// "inlineview": view of page based on template, includes hyperCMS specific code (buttons) and inline text editing
$mgmt_config['objectview'] = "inlineview";

// Define standard view for explorer object list ("detail" = detail view; "small", "medium", "large" = thumbnail gallery view)
$mgmt_config['explorerview'] = "medium";

// How many items (folders and objects) should be displayed in the explorer object list initally.
$mgmt_config['explorer_list_maxitems'] = 500;

// Enable paging (true) or expand object list (false)
// More than 500 objects per page does not perform well with most browsers
$mgmt_config['explorer_paging'] = true;

// Open objects in new window (true) or same window (false)
$mgmt_config['object_newwindow'] = false;

// Open messages in new window (true) or same window (false)
$mgmt_config['message_newwindow'] = false;

// Open users in new window (true) or same window (false)
$mgmt_config['user_newwindow'] = false;

// Window size for objects in pixel (for new window)
$mgmt_config['window_object_width'] = 1280;
$mgmt_config['window_object_height'] = 1000;

// Preview and annotation width for documents, images, and videos in pixel or use "original" for original image width
$mgmt_config['preview_document_width'] = 695;
$mgmt_config['preview_image_width'] = 1024;
$mgmt_config['preview_video_width'] = 854;

// Should metadata on mouse over be displayed in the explorer object list if the sidebar is not displayed
$mgmt_config['explorer_list_metadata'] = false;

// Define if sidebar for object preview should be displayed (true) by default or not (false)
$mgmt_config['sidebar'] = true;

// Define if chat should be enabled (true) or disabled (false)
$mgmt_config['chat'] = true;

// Define if the chat should be "public" for all users of the same publication of "private" (only invited users can see the messages)
$mgmt_config['chat_type'] = "public";

// Define support user name for chat that will always be present for chat or leave empty
$mgmt_config['chat_support'] = "";

// Define chat update interval in ms
$mgmt_config['chat_update_interval'] = 1600;

// Define if markers for images and videos and annotations for images and documents should be enabled (true) or disabled (false)
$mgmt_config['annotation'] = false;

// Define if face detection and recognition for images and videos should be enabled (true) or disabled (false)
$mgmt_config['facerecognition'] = false;

// Define URL of your system using a different subdomain or domain in order to run the service indepenendtly (non-blocking).
// Otherwise the client service will be operated in the same domain as the users client session (blocking).
// Use SSL for the service if your installation uses SSL. Do not mix HTTP and HTTPS since the browsers will block mixed content.
$mgmt_config['facerecognition_service_url'] = "";

// Run the face recognition service on the clients of the defined users (use ";" as separator for the user names) in the background or leave empty for all user clients.
$mgmt_config['facerecognition_service_users'] = "";

// Exclude assets of publications from the face recognition service (use ";" as separator for the publication names) or leave empty for all publications.
$mgmt_config['facerecognition_service_exclude'] = "";

// Define standard mail link type ("access" = access-link; "download" = download-link)
$mgmt_config['maillink'] = "download";

// Define the name of the theme/design for the UI for all publications
// The standard themes are located in directory hypercms/theme/ and the Portal themes are in repository/portal/[publication].
$mgmt_config['theme'] = "";

// Define alternative logo (URL notation) for top frame.
$mgmt_config['logo_top'] = "";

// Define alternative wallpaper image (must be an URL) for the logon and home screen.
$mgmt_config['wallpaper'] = "";

// Show (true) or hide (false) information boxes to provide additional information to the user.
$mgmt_config['showinfobox'] = true;

// Define home boxes to show for each user if no individual selection has been made (use ";" as separator).
// Home boxes are located in directory hypercms/box/.
$mgmt_config['homeboxes'] = "search;news;tasks;recent_objects;up_and_downloads;recent_downloads;recent_uploads";

// Define URL to show in welcome/news home box.
$mgmt_config['homebox_welcome'] = "https://cloud.hypercms.net/home/update_info_en.xhtml";

// Define a directory for individual home boxes (components) that are based on a template and can be edited.
$mgmt_config['homeboxes_directory'] = "HomeBoxes";

// Enable (true) or disable (false) the toolbar personalization for all users.
$mgmt_config['toolbar_functions'] = true;

// Brand Guidelines
// Define a directory for your brand guidelines (components) that are based on a template and can be edited.
$mgmt_config['brandguide_directory'] = "BrandGuidelines";

// File upload
// Check for duplicate entries based on MD5 hash of files (true) or not (false) as default value for the checkbox.
$mgmt_config['check_duplicates'] = true;

// File upload
// Allow existing files to be overwritten (true) or not (false).
$mgmt_config['overwrite_files'] = true;

// File upload
// Allow resumable file uploads (true) or not (false).
$mgmt_config['resume_uploads'] = true;

// Display owner column in content and template version history (true) or hide the owner (false)
// Requires to read the owner from the XML content for each version and requires some time for longer version histories.
$mgmt_config['version_owner'] = false;

// Enable AutoSave to autoamtically save the text of the edior each given value in seconds.
// Set value to 0 to disable autosave
$mgmt_config['autosave'] = 0;

// Enable (true) or disable (false) recycle bin.
$mgmt_config['recyclebin'] = false;

// Delete objects from recycle bin permanently after certain amount of days or 0 for never.
$mgmt_config['recycledays'] = 5;

// Define screen/viewport sizes for screen and mobile browser emulation.
$mgmt_config['screensize'] = array();
// Notebook/desktop screen sizes
$mgmt_config['screensize']['desktop']['10&quot; Netbook'] = "1024 x 600";
$mgmt_config['screensize']['desktop']['12&quot; Netbook'] = "1024 x 768";
$mgmt_config['screensize']['desktop']['13&quot; Notebook'] = "1280 x 800";
$mgmt_config['screensize']['desktop']['15&quot; Notebook'] = "1366 x 768";
$mgmt_config['screensize']['desktop']['19&quot; Desktop'] = "1440 x 900";
$mgmt_config['screensize']['desktop']['20&quot; Desktop'] = "1600 x 900";
$mgmt_config['screensize']['desktop']['22&quot; Desktop'] = "1680 x 1050";
// Tablet screen sizes
$mgmt_config['screensize']['tablet']['Apple iPad (All)'] = "768 x 1024";
$mgmt_config['screensize']['tablet']['Apple iPad Pro'] = "1024 x 1366";
$mgmt_config['screensize']['tablet']['Google Nexus 7'] = "603 x 966";
$mgmt_config['screensize']['tablet']['Google Nexus 9'] = "768 x 1024";
$mgmt_config['screensize']['tablet']['Google Chromebook Pixel'] = "1280 x 850";
$mgmt_config['screensize']['tablet']['Kindle Fire HD 7&quot;'] = "533 x 853";
$mgmt_config['screensize']['tablet']['Kindle Fire'] = "600 x 800";
$mgmt_config['screensize']['tablet']['Kindle Fire HD 8.9&quot;'] = "800 x 1280";
$mgmt_config['screensize']['tablet']['Samsung Galaxy Tab'] = "600 x 1024";
$mgmt_config['screensize']['tablet']['Samsung Galaxy Tab 10'] = "800 x 1280";
// Phone screen sizes
$mgmt_config['screensize']['phone']['Apple iPhone 3/4'] = "320 x 480";
$mgmt_config['screensize']['phone']['Apple iPhone 5'] = "320 x 568";
$mgmt_config['screensize']['phone']['Apple iPhone 6'] = "375 x 667";
$mgmt_config['screensize']['phone']['Apple iPhone 6 Plus'] = "414 x 736";
$mgmt_config['screensize']['phone']['Apple iPhone 7'] = "375 x 667";
$mgmt_config['screensize']['phone']['Apple iPhone 7 Plus'] = "414 x 736";
$mgmt_config['screensize']['phone']['Apple iPhone 8'] = "375 x 667";
$mgmt_config['screensize']['phone']['Apple iPhone 8 Plus'] = "414 x 736";
$mgmt_config['screensize']['phone']['Apple iPhone 11'] = "390 x 844";
$mgmt_config['screensize']['phone']['Apple iPhone 12'] = "414 x 895";
$mgmt_config['screensize']['phone']['Apple iPhone 12 Pro'] = "390 x 844";
$mgmt_config['screensize']['phone']['Apple iPhone 12 Pro Max'] = "428 x 926";
$mgmt_config['screensize']['phone']['Apple iPhone X'] = "375 x 812";
$mgmt_config['screensize']['phone']['Apple iPhone XR'] = "	414 x 896";
$mgmt_config['screensize']['phone']['Apple iPhone XS'] = "375 x 812";
$mgmt_config['screensize']['phone']['Apple iPhone XS Max'] = "414 x 896";
$mgmt_config['screensize']['phone']['ASUS Galaxy 7'] = "320 x 533";
$mgmt_config['screensize']['phone']['BlackBerry 8300'] = "320 x 240";
$mgmt_config['screensize']['phone']['Google Nexus 5X'] = "412 x 732";
$mgmt_config['screensize']['phone']['Google Nexus 6P'] = "412 x 732";
$mgmt_config['screensize']['phone']['Google Pixel'] = "412 x 732";
$mgmt_config['screensize']['phone']['Google Pixel XL'] = "412 x 732";
$mgmt_config['screensize']['phone']['Google Pixel 2 XL'] = "412 x 732";
$mgmt_config['screensize']['phone']['Google Pixel 3'] = "412 x 824";
$mgmt_config['screensize']['phone']['Google Pixel 3 XL'] = "412 x 847";
$mgmt_config['screensize']['phone']['Google Pixel 4'] = "412 x 869";
$mgmt_config['screensize']['phone']['Google Pixel 4 XL'] = "412 x 869";
$mgmt_config['screensize']['phone']['Google Pixel 5'] = "393 x 851";
$mgmt_config['screensize']['phone']['LG Optimus S'] = "320 x 480";
$mgmt_config['screensize']['phone']['LG G5'] = "480 x 853";
$mgmt_config['screensize']['phone']['One Plus 3'] = "480 x 853";
$mgmt_config['screensize']['phone']['Samsung Galaxy S2'] = "320 x 533";
$mgmt_config['screensize']['phone']['Samsung Galaxy S3/4'] = "320 x 640";
$mgmt_config['screensize']['phone']['Samsung Galaxy S5'] = "360 x 640";
$mgmt_config['screensize']['phone']['Samsung Galaxy S7'] = "360 x 640";
$mgmt_config['screensize']['phone']['Samsung Galaxy S7 Edge'] = "360 x 640";
$mgmt_config['screensize']['phone']['Samsung Galaxy S8'] = "360 x 740";
$mgmt_config['screensize']['phone']['Samsung Galaxy S8+'] = "360 x 740";
$mgmt_config['screensize']['phone']['Samsung Galaxy S9'] = "360 x 740";
$mgmt_config['screensize']['phone']['Samsung Galaxy Note 5'] = "480 x 853";
$mgmt_config['screensize']['phone']['Samsung Galaxy Note 9'] = "360 x 740";
$mgmt_config['screensize']['phone']['Samsung Galaxy Note 10'] = "412 x 869";

// Define the tag/content IDs for the source and destination container for the relationsship (source -> destination multimedia object) when using copy and paste.
// Leave empty if you do not want to save the relationship.
$mgmt_config['relation_source_id'] = "Related";
$mgmt_config['relation_destination_id'] = "Related";

// Enable (true) or disable (false) loops for workflows.
$mgmt_config['workflow_loop'] = false;

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
$mgmt_lang_name['cs'] = "Czech";
$mgmt_lang_shortcut['cs'] = "cs";

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

// Define operating system (OS) on content management server ("UNIX" for all UNIX and Linux OS or "WIN" for MS Windows).
// Please note: MS PWS cannot handle multiple HTTP-requests at the same time! since version 3.0 PWS will not be supplied anymore.
$mgmt_config['os_cms'] = "%os_cms%";

// Define date format for error logging.
$mgmt_config['today'] = date ("Y-m-d H:i:s", time());

// Define the unit for the duration (float value) of tasks, use "d" for days, "h" for hours.
$mgmt_config['taskunit'] = "h";

// Define the database tables to be used in reports, use ";" as delimiter.
$mgmt_config['report_tables'] = "object;textnodes;dailystat;project;task;recipient;accesslink";

// Supported Applications
// Set value to true if your content management server supports rendering of objects
// using program- and script-technologies like PHP, JSP, ASP. Otherwise set false.
$mgmt_config['application']['php'] = true;
$mgmt_config['application']['jsp'] = false;
$mgmt_config['application']['asp'] = false;

// File Upload
// Maximum file size in MB allowed for upload. set value to 0 to enable all sizes.
// Check webserver and php.ini restrictions too!
$mgmt_config['maxfilesize'] = 0;

// ZIP File
// Maximum file size to be compressed in ZIP file in MB. Set value to 0 to disable limit.
$mgmt_config['maxzipsize'] = 0;

// Maximum digits for file and folder names (applies for createobject and uploadfile)
// Most file systems does not support more than 255 bytes.
// A value of not more than 200 digits is recommended since the system adds suffixes for the container ID, versioning and file locking to the file names.
$mgmt_config['max_digits_filename'] = 200;

// Which types of files (file extensions) are not allowed for upload, example ".asp.jsp.php.pl.sql".
$mgmt_config['exclude_files'] = ".php.phtml.pl.jsp.asp.aspx.exe.sql.sh.bash";

// Save GPS coordinates (latitude, longitude) provided by the original media file in the database (true) or not (false).
// This will overwrite the geo location provided by the user.
$mgmt_config['gps_save'] = true;

// Save Metadata to Files
// Save IPTC tags to image files (true) or not (false).
$mgmt_config['iptc_save'] = true;

// Save XMP tags to image files (true) or not (false).
$mgmt_config['xmp_save'] = true;

// Save ID3 tags to audio files (true) or not (false).
$mgmt_config['id3_save'] = true;

// Versioning of Containers
// Save versions of published containers and media files (true) or disable versioning (false).
$mgmt_config['contentversions'] = true;

// Save versions of saved containers and media files (true) or do not create a new version (false).
$mgmt_config['contentversions_all'] = false;

// Max. number of versions to be saved (0 means no limit).
$mgmt_config['contentversions_max'] = 0;

// Public Download
// Allow access to download and wrapper links without logon session (true) or not (false).
// This setting must be enabled if users want to provide wrapper or download links to the public.
// Otherwise the object need to be published in order to provide public access.
$mgmt_config['publicdownload'] = true;

// FTP Upload
// Allow FTP file download (true) or not (false).
$mgmt_config['ftp_download'] = true;

// Document Viewer
// Allow the view of documents by the doc viewer (true) or not (false).
$mgmt_config['docviewer'] = true;

// Support for synonyms in search
// Enable synonyms (true) or not (false).
$mgmt_config['search_synonym'] = true;

// Operator for text based search queries
// Only AND or OR are allowed as possible values or leave empty for default setting.
$mgmt_config['search_operator'] = "";

// Use "like" or "match" for full-text search queries
// "match" has performance advantages if no wildcard-character is used and permits the use of special operators.
// Please keep in mind that "match" uses the stopword list of the database and the "like" operator will be used in combination
// in order to avoid empty search results due to stopwords restrictions of the database.
// "like" cant use a fulltext search index and will be slower but will lead to more or the same results since it uses the text content as is.
$mgmt_config['search_query_match'] = "match";

// Maximum number of search results (per page/request).
$mgmt_config['search_max_results'] = 300;

// Strong Passwords
// Enable (true) or disable (false) strong passwords for users.
// If enabled, passwords will be checked regarding minimum security requirements.
$mgmt_config['strongpassword'] = true;

// Define the minimum password length
// The maximum password length is 100 characters and can't be changed.
$mgmt_config['passwordminlength'] = 10;

// Password dictionary of passwords that must not be used (blacklist), use "," as delimiter. 
$mgmt_config['passwordblacklist'] = "";

// Number of passwords in the history that can't be reused again.
$mgmt_config['passwordhistory'] = 0;

// Password expiration in number of days (0 means it never expires).
$mgmt_config['passwordexpires'] = 0;

// Enable (true) or disable (false) the password reset on logon screen.
$mgmt_config['passwordreset'] = true;

// Enable (true) or disable (false) multi-factor authentication.
$mgmt_config['multifactorauth'] = false;

// Registration of new users
// Enable (true) or disable (false) the registration link for new users in the sign-in mask.
$mgmt_config['userregistration'] = false;

// User account expiration in number of days (0 means it never expires).
// If no user activity has been logged for a certain time, the user can't login anymore
// IMPORTANT: This feature requires $mgmt_config['user_log'] = true
$mgmt_config['userexpires'] = 0;

// Enable (true) or disable (false) concurrent users using the same account.
$mgmt_config['userconcurrent'] = true;

// Enable (true) or disable (false) the permanent deletion of users that exceeded the valid date.
$mgmt_config['userdelete'] = true;

// Log level for system events
// Define the log level (all, warning, error, none) for the logging of system events.
$mgmt_config['loglevel'] = "all";

// Publication specific log files
// Enable (true) or disable (false) publication specific log files besides the standard event log.
$mgmt_config['publication_log'] = false;

// User specific log files
// Enable (true) or disable (false) user specific log files besides the standard event log.
$mgmt_config['user_log'] = false;

// User e-mail notification on system errors or warnings
// Provide a list of users by thier user names (use "," or ";" as separator) for automated notification, or leave empty.
$mgmt_config['eventlog_notify'] = "";

// Define users by their user name (use ";" as separator) that should be excluded as senders from the automatic notifications (function notifyusers in Main API).
$mgmt_config['notify_exclude_users'] = "sys";

// Encryption
// Encryption strength (weak, standard, strong).
$mgmt_config['crypt_level'] = "strong";

// Key used for en/decryption of temporary system data (key length must be 8, 16, or 32).
$mgmt_config['crypt_key'] = "h1y2p3e4r5c6m7s8";

// Data and file encryption is always based on strong AES 256.
// You need to define a key with 32 digits for en/decryption.
// If a key server is used please use the commented line to access the key:
// $mgmt_config['aes256_key'] = file_get_contents ("https://key-server/aes256-key.key");
$mgmt_config['aes256_key'] = "h1y2p3e4r5c6m7s8s9m0c1r2e3p4y5h6";

// Template code
// Use $mgmt_config['publication-name']['template_clean_level'] for publication specific settings
// Cleaning level of template code from none = 0 to strong = 5
// (no cleaning = 0, basic set of disabled functions = 1, 1 + file access functions = 2, 2 + include functions = 3, 3 + hyperCMS API file functions = 4, No server side script allowed = 5)
$mgmt_config['template_clean_level'] = 3;

// Logon Timeout
// How many minutes will an IP and user combination be locked after 10 failed attempts.
// A value of 0 means there is no timeout.
$mgmt_config['logon_timeout'] = 10; 

// CSRF Protection
// Define allowed requests per minute.
$mgmt_config['requests_per_minute'] = 500;

// Security Token
// Define lifetime of security token in seconds (min. 60 sec.)
$mgmt_config['token_lifetime'] = 86400;

// Workplace Integration (WebDAV)
// Define the time in seconds for the users session cache in seconds (a password change will take effect after the session lifetime)
// The min. supported value is 10 seconds and the default/fallback value is 3600 seconds
$mgmt_config['webdav_lifetime'] = 3600;

// Enable (true) or disable (false) the WebDAV file locking
$mgmt_config['webdav_lock'] = true;

// Support password
// Set a support password for the support log file access
$mgmt_config['support_pass'] = "";

// Instances
// Instances don't share the same database, internal and external repository.
// Enable multiple hyperCMS instances by providing a path to the instance configuration directory.
// For distributed systems the directory must be located on a central resource that can be accessed by every system node.
$mgmt_config['instances'] = "%instances%";

// Enable (true) or disable (false) writing of session data for third party load balancers in order to support session synchronization
$mgmt_config['writesessiondata'] = false;

// Factor to correct the used storage space due to the fact that the system only tracks the uploaded original file size
// and does not include annotation images, video previews and versions, file versions of the same asset, thumbnails, and temporary files.
// Correction factors per publication can be defined as well by $mgmt_config['publicationname']['storagefactor'] = 1.5;
$mgmt_config['storagefactor'] = 1.15;

// Clean domain from URL
// Enable (true) or disable (false) the function cleandomain to convert the URL to a relative path.
// Only disable the function if you are using hyperCMS function in an external webapplication that sends requests to the hyperCMS server. 
$mgmt_config['cleandomain'] = true;

// ------------------------------------ Executable Linking -------------------------------------

// hyperCMS uses third party PlugIns to parse, convert or uncompress files. The Windows binaries can
// be found in the cms/bin directory of the hyperCMS distribution. For other platforms like
// Linux or UNIX derivates you should install the package of the vendors:
// Antiword - Word Parser http://www.winfield.demon.nl
// XPDF - PDF Parser http://www.foolabs.com/xpdf
// ZIP/UNZIP - ZIP Compression http://www.info-zip.org
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

// Define OCR
// Define file types that should be indexed by OCR using Tesseract and ImageMagick (can be any kind of image that is supported by ImageMagick).
// The path to the executable is usually /usr/bin/tesseract
// You need to install the Tesseract language pack in order to use the language.
// Install tesseract-ocr-all for all languages, or install seperately, e.g. -deu, -eng, -fra, -ita, -ndl, -por, -spa, -vie.
$mgmt_parser['.ai.aai.act.art.arw.avs.bmp.bmp2.bmp3.cals.cgm.cin.cit.cmyk.cmyka.cpt.cr2.crw.cur.cut.dcm.dcr.dcx.dib.djvu.dng.dpx.emf.epdf.epi.eps.eps2.eps3.epsf.epsi.ept.exr.fax.fig.fits.fpx.gif.gplt.gray.hdr.hpgl.hrz.ico.info.inline.jbig.jng.jp2.jpc.jpe.jpg.jpeg.jxr.man.mat.miff.mono.mng.mpc.mpr.mrw.msl.mvg.nef.orf.otb.p7.palm.pam.clipboard.pbm.pcd.pcds.pcl.pcx.pdb.pdf.pef.pfa.pfb.pfm.pgm.picon.pict.pix.pjpeg.png.png8.png00.png24.png32.png48.png64.pnm.ppm.ps.ps2.ps3.psb.psd.psp.ptif.pwp.pxr.rad.raf.raw.rgb.rgba.rla.rle.sct.sfw.sgi.shtml.sid.mrsid.sparse-color.sun.svg.tga.tif.tiff.tim.ttf.txt.uil.uyvy.vicar.viff.wbmp.wdp.webp.wmf.wpg.x.xbm.xcf.xpm.xwd.x3f.ycbcr.ycbcra.yuv'] = "%tesseract%";

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
// The path to the executable is usually /usr/bin/unoconv.
$mgmt_docpreview['.bib.doc.docx.dot.ltx.odd.odt.odg.odp.ods.ppt.pptx.pxl.psw.pts.rtf.sda.sdc.sdd.sdw.sxw.txt.htm.html.xhtml.xls.xlsx'] = "%unoconv%";

// Define the supported target formats for documents
$mgmt_docoptions['.pdf'] = "-f pdf";
$mgmt_docoptions['.doc'] = "-f doc";
$mgmt_docoptions['.csv'] = "-f csv";
$mgmt_docoptions['.xls'] = "-f xls";
$mgmt_docoptions['.ppt'] = "-f ppt";
$mgmt_docoptions['.odt'] = "-f odt";
$mgmt_docoptions['.ods'] = "-f ods";
$mgmt_docoptions['.odp'] = "-f odp";
$mgmt_docoptions['.png'] = "-f png";
$mgmt_docoptions['.jpg'] = "-f jpg";
$mgmt_docoptions['.rtf'] = "-f pdf";
$mgmt_docoptions['.txt'] = "-f txt";

// Define the mapping of the source and target formats for documents
$mgmt_docconvert['.doc'] = array('.png', '.pdf', '.odt');
$mgmt_docconvert['.docx'] = array('.png', '.pdf', '.odt');
$mgmt_docconvert['.xls'] = array('.png', '.pdf', '.csv', '.ods');
$mgmt_docconvert['.xlsx'] = array('.png', '.pdf', '.csv', '.ods');
$mgmt_docconvert['.ppt'] = array('.png', '.pdf', '.odp');
$mgmt_docconvert['.pptx'] = array('.png', '.pdf', '.odp');
$mgmt_docconvert['.odt'] = array('.png', '.pdf', '.doc');
$mgmt_docconvert['.ods'] = array('.png', '.pdf', '.csv', '.xls');
$mgmt_docconvert['.odp'] = array('.png', '.pdf', '.ppt');
$mgmt_docconvert['.rtf'] = array('.png', '.pdf', '.doc', '.odt');
$mgmt_docconvert['.txt'] = array('.png', '.pdf', '.doc', '.odt');

// Define Image Preview using the ImageMagick (GD Library as fallback with limited features)

// Options:
// -s ... output size in width x height in pixel (WxH), e.g. -s 1028x768
// -f ... output format (file extension without dot: jpg, png, gif), e.g. -f png
// -d ... image density (DPI) for vector graphics and EPS files, common values are 72, 96 dots per inch for screen, while printers typically support 150, 300, 600, or 1200 dots per inch, e.g. -d 300
// -q ... quality for compressed image formats like JPEG (1 to 100), e.g. -q 95
// -c ... crop x and y coordinates (XxY), e.g. -c 100x100
// -g ... gravity (NorthWest, North, NorthEast, West, Center, East, SouthWest, South, SouthEast) for the placement of an image, e.g. -g west
// -ex ... extent/enlarge image, unfilled areas are set to the background color, to position the image, use offsets in the geometry specification or precede with a gravity setting, -ex 1028x768
// -bg ... background color, the default background color (if none is specified or found in the image) is white, e.g. -bg black
// -b ... image brightness from -100 to 100, e.g. -b 10
// -k .... image contrast from -100 to 100, e.g. -k 5
// -cs ... color space of image, e.g. RGB, CMYK, gray, e.g. -cs CMYK
// -rotate ... rotate image in positive degrees, e.g. -rotate 90
// -fv ... flip image in the vertical direction (no value required)
// -fh ... flip image in the horizontal direction (no value required)
// -sh ... sharpen image, e.g. one pixel size sharpen, e.g. -sh 0x1.0
// -bl ... blur image with a Gaussian or normal distribution using the given radius and sigma value, e.g. -bl 1x0.1
// -pa ... apply paint effect by replacing each pixel by the most frequent color in a circular neighborhood whose width is specified with radius, e.g. -pa 2
// -sk ... sketches an image, e.g. -sk 0x20+120
// -sep ... apply sepia-tone on image from 0 to 99.9%, e.g. -sep 80%
// -monochrome ... transform image to black and white (no value required)
// -wm ... watermark image->positioning->geometry, e.g. /logo/image.png->topleft->+30

// Define image preview using the GD Library and PHP (thumbnail generation)
// The GD Library only supports jpg, png and gif images as output, set value to "GD" to use it.
// $mgmt_imagepreview['.gif.jpg.jpeg.png'] = "GD";

// Use "dcraw" or "ufraw" to convert RAW images to JPEG images.
// Package dcraw replaces ufraw that is no longer maintained in newer Linux distributions (e.g. Debian 11).
// Please make sure that the package dcraw or ufraw is installed.
$mgmt_imagepreview['rawimage'] = "dcraw";

// Define image preview using ImageMagick and GhostScript (thumbnail generation)
// The path to the executable is usually /usr/bin/convert.
$mgmt_imagepreview['.ai.aai.act.art.arw.avs.bmp.bmp2.bmp3.cals.cgm.cin.cit.cmyk.cmyka.cpt.cr2.crw.cur.cut.dcm.dcr.dcx.dib.djvu.dng.dpx.emf.epdf.epi.eps.eps2.eps3.epsf.epsi.ept.exr.fax.fig.fits.fpx.gif.gplt.gray.hdr.hpgl.hrz.ico.info.inline.jbig.jfif.jng.jp2.jpc.jpe.jpg.jpeg.jxr.man.mat.miff.mono.mng.mpc.mpr.mrw.msl.mvg.nef.orf.otb.p7.palm.pam.clipboard.pbm.pcd.pcds.pcl.pcx.pdb.pdf.pef.pfa.pfb.pfm.pgm.picon.pict.pix.pjpeg.png.png8.png00.png24.png32.png48.png64.pnm.ppm.ps.ps2.ps3.psb.psd.psp.ptif.pwp.pxr.rad.raf.raw.rgb.rgba.rla.rle.sct.sfw.sgi.shtml.sid.mrsid.sparse-color.sun.svg.tga.tif.tiff.tim.ttf.uil.uyvy.vicar.viff.wbmp.wdp.webp.wmf.wpg.x.xbm.xcf.xpm.xwd.x3f.ycbcr.ycbcra.yuv'] = "%convert%";

// If a file was uploaded, the system will try to create a thumbnail image for the preview.
$mgmt_imageoptions['.jpg.jpeg']['thumbnail'] = "-s 380x220 -q 95 -f jpg";

// Define the supported target formats for image editing
$mgmt_imageoptions['.bmp']['original'] = "-f bmp";
$mgmt_imageoptions['.gif']['original'] = "-f gif";
$mgmt_imageoptions['.jpg.jpeg']['original'] = "-f jpg";
$mgmt_imageoptions['.png']['original'] = "-f png";
$mgmt_imageoptions['.tif.tiff']['original'] = "-f tiff";
$mgmt_imageoptions['.webp']['original'] = "-f webp";

// Define additional download formats besides the original image
$mgmt_imageoptions['.jpg.jpeg']['1920x1080px'] = '-s 1920x1080 -q 95 -f jpg';
$mgmt_imageoptions['.jpg.jpeg']['1024x768px'] = '-s 1024x768 -q 95 -f jpg';
$mgmt_imageoptions['.jpg.jpeg']['640x480px'] = '-s 640x480 -q 95 -f jpg';

// Define media preview using FFMPEG (Audio/Video formats)
// If a video or audio file is uploaded, hyperCMS will try to generate a smaler streaming video file for preview.

// Audio Options:
// -ac ... number of audio channels
// -an ... disable audio
// -ar ... audio sampling frequency (default = 44100 Hz)
// -b:a ... audio bitrate (default = 64k)
// -c:a ... audio codec (e.g. aac, libmp3lame, libvorbis)
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
      
// The path to the executable is usually /usr/bin/ffmpeg.
$mgmt_mediapreview['.3g2.3gp.4xm.a64.aac.ac3.act.adf.adts.adx.aea.aiff.alaw.alsa.amr.amv.anm.apc.ape.apr.asf.asf_stream.ass.au.audio.avi.avm2.avs.bethsoftvid.bfi.bin.bink.bit.bmv.c93.caf.cavsvideo.cdg.cdxl.crc.daud.dfa.dirac.dnxhd.dsicin.dts.dv.dv1394.dvd.dxa.dwd.ea.ea_cdata.eac3.f32be.f32le.f4v.f64be.f64le.fbdev.ffm.ffmetadata.film_cpk.filmstrip.flac.flic.flv.framecrc.framemd5.g722.g723_1.g729.gsm.gxf.h261.h263.h264.hls.ico.idcin.idf.iff.ilbc.image2.image2pipe.ingenient.ipmovie.ipod.ismv.iss.iv8.ivf.jack.jacosub.jv.la.latm.lavfi.libcdio.libdc1394.lmlm4.loas.lxf.m4a.m4b.m4p.m4r.m4v.matroska.md5.mgsts.microdvd.mid.mj2.mjpeg.mkv.mlp.mm.mmf.mov.mp2.mp3.mp4.mp4v.mpc.mpc8.mpeg.mpg.mpeg1video.mpeg2video.mpegts.mpegtsraw.mpegvideo.mpjpeg.msnwctcp.mts.mtv.mulaw.mvi.mxf.mxf_d10.mxg.nc.nsv.null.nut.nuv.oga.ogg.ogm.ogv.oma.oss.ots.pac.paf.pmp.psp.psxstr.pva.qcp.r3d.ra.rawvideo.rcv.realtext.rka.rl2.rm.roq.rpl.rso.rtp.rtsp.s16be.s16le.s24be.s24le.s32be.s32le.s8.sami.sap.sbg.sdl.sdp.segment.shn.siff.smjpeg.smk.smush.sol.sox.spdif.subviewer.svcd.swa.swf.thp.tiertexseq.tmv.truehd.tta.tty.txd.u16be.u16le.u24be.u24le.u32be.u32le.u8.vc1.vc1test.vcd.vmd.vob.voc.vox.vqf.w64.wav.wc3movie.webm.webvtt.wma.wmv.wsaud.wsvqa.wtv.wv.x11grab.xa.xbin.xmv.xwma.yop.yuv4mpegpipe'] = "%ffmpeg%";

// If a video or audio file was uploaded, the system will try to create a thumbnail video/audio file for the preview.
$mgmt_mediaoptions['thumbnail-video'] = "-b:v 768k -s:v 854x480 -f mp4 -c:a aac -b:a 64k -ac 2 -c:v libx264 -mbd 2 -flags +loop+mv4 -cmp 2 -subcmp 2"; 
$mgmt_mediaoptions['thumbnail-audio'] = "-f mp3 -c:a libmp3lame -b:a 64k";

// Auto rotate video if a rotation has been detected (true) or leave video in it's original state (false)
// Keep in mind that most recent FFMPEG version autorotate the video.
$mgmt_mediaoptions['autorotate-video'] = true;

// Define the supported target formats for video/audio editing (please use the variables %videobitrate%, %audiobitrate%, %width%, %height%)
// Video formats:
$mgmt_mediaoptions['.flv'] = "-b:v %videobitrate% -s:v %width%x%height% -f flv -c:a libmp3lame -b:a %audiobitrate% -ac 2 -ar 22050";
$mgmt_mediaoptions['.mov'] = "-b:v %videobitrate% -s:v %width%x%height% -f mov";
$mgmt_mediaoptions['.mp4'] = "-b:v %videobitrate% -s:v %width%x%height% -f mp4 -c:a aac -b:a %audiobitrate% -ac 2 -c:v libx264 -mbd 2 -flags +loop+mv4 -cmp 2 -subcmp 2";
$mgmt_mediaoptions['.mpeg'] = "-b:v %videobitrate% -s:v %width%x%height% -f mpeg -c:a aac -b:a %audiobitrate% -ac 2";
$mgmt_mediaoptions['.ogv'] = "-b:v %videobitrate% -s:v %width%x%height% -f ogg -c:a libvorbis -b:a %audiobitrate% -ac 2";
$mgmt_mediaoptions['.webm'] = "-b:v %videobitrate% -s:v %width%x%height% -f webm -c:a libvorbis -b:a %audiobitrate% -ac 2";

// Audio formats:
$mgmt_mediaoptions['.flac'] = "-f flac -c:a flac -b:a %audiobitrate%";
$mgmt_mediaoptions['.mp3'] = "-f mp3 -c:a libmp3lame -b:a %audiobitrate%";
$mgmt_mediaoptions['.oga'] = "-f ogg -c:a libvorbis -b:a %audiobitrate%";
$mgmt_mediaoptions['.wav'] = "-c:a pcm_u8 -b:a %audiobitrate%";

// Define Metadata Injection
// YAMDI to inject metadata (play length) into the generated flash video file (FFMPEG discards metadata).
// The path to the executable is usually /usr/bin/yamdi.
$mgmt_mediametadata['.flv'] = "%yamdi%";

// Use EXIFTOOL to inject metadata into the generated image file (ImageMagick discards metadata)
// The path to the executable is usually /usr/bin/exiftool.
$mgmt_mediametadata['.3fr.3g2.3gp2.3gp.3gpp.acr.afm.acfm.amfm.ai.ait.aiff.aif.aifc.ape.arw.asf.avi.bmp.dib.btf.tiff.tif.chm.cos.cr2.crw.ciff.cs1.dcm.dc3.dic.dicm.dcp.dcr.dfont.divx.djvu.djv.dng.doc.dot.docx.docm.dotx.dotm.dylib.dv.dvb.eip.eps.epsf.ps.erf.exe.dll.exif.exr.f4a.f4b.f4p.f4v.fff.fff.fla.flac.flv.fpf.fpx.gif.gz.gzip.hdp.wdp.hdr.html.htm.xhtml.icc.icm.idml.iiq.ind.indd.indt.inx.itc.j2c.jpc.jp2.jpf.j2k.jpm.jpx.jpg.jpeg.k25.kdc.key.kth.la.lnk.m2ts.mts.m2t.ts.m4a.m4b.m4p.m4v.mef.mie.miff.mif.mka.mkv.mks.modd.mos.mov.qt.mp3.mp4.mpc.mpeg.mpg.m2v.mpo.mqv.mrw.mxf.nef.nmbtemplate.nrw.numbers.odb.odc.odf.odg,.odi.odp.ods.odt.ofr.ogg.ogv.orf.otf.pac.pages.pcd.pdf.pef.pfa.pfb.pfm.pgf.pict.pct.pjpeg.plist.pmp.png.jng.mng.ppm.pbm.pgm.ppt.pps.pot.potx.potm.ppsx.ppsm.pptx.pptm.psd.psb.psp.pspimage.qtif.qti.qif.ra.raf.ram.rpm.rar.raw.raw.riff.rif.rm.rv.rmvb.rtf.rsrc.rw2.rwl.rwz.so.sr2.srf.srw.svg.swf.thm.thmx.tiff.tif.ttf.ttc.vob.vrd.vsd.wav.webm.webp.wma.wmv.wv.x3f.xcf.xls.xlt.xlsx.xlsm.xlsb.xltx.xltm.xmp.zip'] = "%exiftool%";

// Define max. file size in MB for thumbnail/video generation for certain file extensions.
$mgmt_maxsizepreview['.pdf'] = 500;
$mgmt_maxsizepreview['.psd'] = 500;
$mgmt_maxsizepreview['.doc'] = 100;
$mgmt_maxsizepreview['.docx'] = 100;
$mgmt_maxsizepreview['.ppt'] = 100;
$mgmt_maxsizepreview['.pptx'] = 100;
$mgmt_maxsizepreview['.xls'] = 50;
$mgmt_maxsizepreview['.xlsx'] = 50;

// Enable (true) or disable (false) the creation of the preview when opening the object list of a location
// Try to recreate previews of multimedia files in object list if the thumbnail file doesn't exist.
$mgmt_config['recreate_preview'] = false;

// Use WKHTMLTOPDF to convert HTML to PDF
// The path to the executable is usually /usr/bin/wkhtmltopdf.
$mgmt_config['html2pdf'] = "%html2pdf%";

// Use X11-Server (fot the WKHTMLTOPDF not patched QT version)
// The path to the executable is usually /usr/bin/xvfb-run.
$mgmt_config['x11'] = "%x11%";

// Use PDFTK to merge PDF files
// The path to the executable is usually /usr/bin/pdftk.
$mgmt_config['mergepdf'] = "%mergepdf%";

// -------------------------------- Relational Database Connectivity ----------------------------------

// MySQL integration (or other relational databases via ODBC)
// The file "db_connect_rdbms.php" provides MySQL/MariaDB and ODBC DB Connectivity.
// Run the installation or create a database with UTF-8 support and run the SQL script for table definitions manually.

// Define Database Access
// You can define a persistent database connection by providing "p:dbhost" for 'dbhost'.
$mgmt_config['db_connect_rdbms'] = "db_connect_rdbms.php";
$mgmt_config['dbconnect'] = "mysql"; // values: mysql, odbc
$mgmt_config['dbhost'] = "%dbhost%";
$mgmt_config['dbuser'] = "%dbuser%";
$mgmt_config['dbpasswd'] = "%dbpasswd%";
$mgmt_config['dbname'] = "%dbname%";
$mgmt_config['dbcharset'] = "utf8";

// RDBMS Log
// Log queries and their executing time in logs/sql.log (true) or do not log (false).
$mgmt_config['rdbms_log'] = false;

// Optimize database
// Optimize the database automatically once per year on 1st of January (true) or not (false).
// It is recommended to create a backup of the database before the execution of the job.
$mgmt_config['rdbms_optimize'] = false;

// --------------------------------- SMTP Mail System Configuration -----------------------------------

// SMTP parameters for sending e-mails via a given SMTP server
$mgmt_config['smtp_host']     = "%smtp_host%";
$mgmt_config['smtp_username'] = "%smtp_username%";
$mgmt_config['smtp_password'] = "%smtp_password%";
$mgmt_config['smtp_port']     = "%smtp_port%";
$mgmt_config['smtp_sender']   = "%smtp_sender%";

// ------------------------------------ Import / Export ----------------------------------------

// Define password for Import and Export REST API (requires Connector module)
$mgmt_config['passcode'] = "";

// Restore exported media files to the media repository if requested (true) or leave the media files at their current export location (false).
// Note: The media file will always be restored if any modifications will be applied.
$mgmt_config['restore_exported_media'] = true;

// --------------------------------------- App Keys --------------------------------------------

// YouTube integration (requires Connector module)
// Please provide the Google API credentials in order to upload videos to YouTube.
// Visit: https://developers.google.com/youtube/registering_an_application
$mgmt_config['youtube_oauth2_client_id'] = "";
$mgmt_config['youtube_oauth2_client_secret'] = "";
$mgmt_config['youtube_appname'] = "";

// DropBox integration
// Please provide a valid Dropbox app-name and app-key.
// Keep in mind that the domain needs to be added to your Dropbox developer account in order to use the app-key.
// Visit: https://www.dropbox.com/developers/apps/create
$mgmt_config['dropbox_appname'] = "";
$mgmt_config['dropbox_appkey'] = "";

// Google Maps integration
// Provide a valid key for Google Maps.
// Visit: https://developers.google.com/maps/documentation/embed/get-api-key
$mgmt_config['googlemaps_appkey'] = "%googlemaps_appkey%";

// Google Analytics integration
// Provide a valid key in order to track the users behaviour with Google Analytics.
// Visit: https://support.google.com/analytics/answer/7476135
$mgmt_config['googleanalytics_key'] = "";

// --------------------------------- Authentication Connectivity -------------------------------------

// LDAP/AD Integration
// If you are using LDAP, Active Directory, or any other user directory, you can specify the file name without extension to be used for the connector.
// The standard connector file "ldap_connect.inc.php" is located in /hypercms/connector/authconnect/.
// Besides the standard connector, you can make a copy of the file "ldap_connect.inc.php" and paste it in /data/connect/ in order to modify the code and define your own connector.
// The system will look in /data/connect/ for the specified connector file and will fallback to /hypercms/connector/authconnect/.

// Use "ldap_connect" for LDAP/AD
// Specify the file name "ldap_connect" located in /data/connect/ or /hypercms/connector/authconnect/ in order to connect to an LDAP or AD directory and verify users.
// Alternatively you can create your own connector file in /data/connect/ and refer to it. Make sure you use the file extension .inc.php and use the same function name and parameters.
$mgmt_config['authconnect'] = "";

// Enable (true) or disable (false) the connectivity for all publications
// If enabled the below AD/LDAP settings need to be defined.
// If disabled the AD/LDAP settings need to be defined in the publication management (per publication).
$mgmt_config['authconnect_all'] = false;

// Define a LDAP/AD user with general read permissions
// This LDAP/AD user is only required if you want to use SSO using the OAuth remoteclient
$mgmt_config['ldap_admin_username'] = "";
$mgmt_config['ldap_admin_password'] = "";

// Define the connection parameters
// Port 389 is for LDAP over TLS. Port 636 is for LDAP over SSL, which is deprecated.
// LDAP works from port 389 and when you issue the StartTLS (with ldap_start_tls()) it encrypts the connection.
// Example: ldapserver.name
$mgmt_config['ldap_servers'] = "ldapserver.name";
// Example: domain.com
$mgmt_config['ldap_userdomain'] = "";
// Example: OU=Departments,DC=MYDOMAIN,DC=COM
$mgmt_config['ldap_base_dn'] = "";
// Example: 2 or 3
$mgmt_config['ldap_version'] = 3;
// Example: 389 (for TLS) or 636 (for SSL)
$mgmt_config['ldap_port'] = "";
$mgmt_config['ldap_follow_referrals'] = false;
$mgmt_config['ldap_use_ssl'] = true;
$mgmt_config['ldap_use_tls'] = false;

// Define the LDAP/AD user filter for the LDAP bind or leave empty if the LDAP server support the user name for the bind
// This setting will not be applied if MS AD or a user domain is used.
// Use %user% as placeholder for the user name.
// Example: uid=%user%,cn=users
$mgmt_config['ldap_username_dn'] = "";

// Define the user filter for the search in LDAP/AD
// For Active Directory define "sAMAccountName" 
$mgmt_config['ldap_user_filter'] = "sAMAccountName";

// Enable (true) or disable (false) the sync of LDAP users with the system users
// The user information such as name, email, telephone is queried and synchronized.
// If the user’s publication and group membership should also be synchronized according to certain rules, 
// this must be specified in the $mgmt_config['ldap_sync_publications_mapping'] and $mgmt_config['ldap_sync_groups_mapping']
// otherwise the memberships are retained as stored in the system.
$mgmt_config['ldap_sync'] = false;

// Define the user attributes you want so sync with LDAP/AD
// Supported attributes for the sync are 'memberof', 'givenname', 'sn', 'telephonenumber', and 'mail'
// memberof ... user memberships in LDAP/AD
// givenname ... firstname
// sn ... surename/lastname
// telephonenumber ... phone
// mail ... e-mail address
$mgmt_config['ldap_user_attributes'] = array('memberof', 'givenname', 'sn', 'telephonenumber', 'mail');

// Delete the user if it does not exist in the LDAP/AD directory (true) or leave user (false)
$mgmt_config['ldap_delete_user'] = false;

// Keep existing group memberships of user (true) or not (false)
// Enable this setting if groups are defined manually and by LDAP/AD (mix of groups)
// Keep in mind that enabling this setting has security implications, since LDAP/AD groups will not be removed anymore once assigned 
$mgmt_config['ldap_keep_groups'] = false;

// Synchronize AD/LDAP groups with publications of the user
// Define mapping based on a search string that defines the users publication membership, use "," as separator for the assignment to multiple publications 
// Mapping: "LDAP search string" => "Publication-name-A,Publication-name-B"
// Example: $mgmt_config['ldap_sync_publications_mapping'] = array("DC=domain,DC=de"=>"Publication-name-A,Publication-name-B", "DC=domain,DC=uk"=>"Publication-name-C");
$mgmt_config['ldap_sync_publications_mapping'] = array();

// Synchronize AD/LDAP groups with user groups of the user
// Define mapping based on a search string that defines the users group membership, use "," as separator for the assignment to multiple groups 
// Mapping: "LDAP search string" => "Publication-name-A/Group-name-A,Publication-name-B/Group-name-B"
// Example for general groups for all publications: $mgmt_config['ldap_sync_groups_mapping'] = array("OU=MANAGER GROUP"=>"ChiefEditor", "OU=ALL GROUPS"=>"Editor");
// Example for specific groups per publication: $mgmt_config['ldap_sync_groups_mapping'] = array("OU=MANAGER GROUP"=>"Publication/ChiefEditor", "OU=ALL GROUPS"=>"Publication/Editor");
$mgmt_config['ldap_sync_groups_mapping'] = array();

// Signature template
// If the user data should be used to create a signature for the  e-mails you can use the following template.
// Leave empty or comment if you don't want to use the signature template.
// Use %firstname%, $lastname%, %email%, and %phone% for the provided user data from LDAP/AD.
$mgmt_config['ldap_user_signature'] = "Best regards
%firstname% %lastname%
E: %email%
T: %phone%
  
This message is intended for the individual named above and is confidential and may also be privileged. If you are not the intended 
recipient, please do not read, copy, use or disclose this communication to others. For electronically send information and pieces of advice, 
which are not confirmed by following written execution, in principle no adhesion is taken over.
";

// OAuth 1.0 can be used for Single-Sign-On (SSO requires the Connector module)
// In order to use SSO, the remote webserver need to support PHP and the OAuth remote client file in hypercms/connector/sso/outh_remoteclient.php need to be copied to the remote server.
// The OAuth configuration of the remote client must be the same as of the OAuth server (see settings below).
// The OAuth remote client must be copied to an IIS server with activated Integrated Windows authentication and PHP, and need to be accessed by the users using MS IE or MS Edge via HTTP(S). 
// The OAuth client will provide the user ID of the authentication user in a Windows network to the server and will forward the user on success.
// This user need to be verified using LDAP/AD. Keep in mind that a LDAP/AD user with general read permissions is required in order to verify the user, 
// see setting $mgmt_config['ldap_admin_username'] and $mgmt_config['ldap_admin_password']
// If SSO is activated  and all 3 OAuth settings are not empty, the multi-factor authentication will be enabled by default.

// Enable (true) or disable (false) SSO
$mgmt_config['sso'] = false;

// Enable (true) or disable (false) SSO IP validation for WebDAV (do not use if users share the same IP address)
$mgmt_config['sso_ip_validation'] = false;

// Provide the OAuth consumer key
// Example: hypercms
$mgmt_config['oauth_consumer_key'] = "";

// Provide the OAuth consumer secret
// Example: XMB9APEytVnxoAkLcRGqKQkxptw3rVVY
$mgmt_config['oauth_secret'] = "";

// Define the default timezone for OAuth
// Example: Europe/Vienna
$mgmt_config['oauth_timezone'] = "";

// ----------------------------------- File System Permissions -----------------------------------------

// Set permissions for files that will be created by hyperCMS in the file system. Only important on UNIX systems.
// Default value is 0757.
$mgmt_config['fspermission'] = 0757;

// ----------------------------------- Software Updates -----------------------------------------

// Display update information on home screen for all users (true) or hide (false)
// Keep in mind that only updates installed by function update_software will be tracked by the system and can be used to display and hide the information.
$mgmt_config['update_info'] = true;

// Define an alternative URL for the software update service
$mgmt_config['update_url'] = "";
?>