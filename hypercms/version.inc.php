<?php
/*
new features of major releases:

version 1.0.x
release 05/2002 - 10/2002
features:
- XML based repository
- user and role management
- basic link management
- media database

version 2.0.x
release 12/2002 - 03/2003
new features:
- personalization
- task management
- new GUI, includes toolbar
- link management
- cut/copy/paste objects
- search functionality
- site management

version 3.0.x
release 03/2003 - 06/2003
new features:
- better personalization integrated in GUI
- complete new template engine with new tag-set and tag wizard
- new XML schemas
- hyperCMS API
- better application integration due to new template engine (hidden elements)
- better link management
- un/publish and delete all items in a folder
- workflow management
- new adminisrtation GUI of sites, users and groups
- cluster operation
- new repository structure
- check in/out of objects
- webdav support
- DB Connectivity
- better search engine
- new features in wysiwyg editor
- staging due to publication target definitions

version 4.0.x
release 07/2003 - 10/2003
new features:
- new hyperCMS Dashboard in WinXP look
- EasyEdit mode allows browsing through site and editing content
- un/publishing of folders and all their items
- cut/copy/paste folders and all their items
- workflow items can be set to users or usergroups
- (4.0.4) tamino can be used as integrated search engine
- (4.0.6) checked out items can be used as private section

version 4.1.0
release 11/2003
- configuration is stored in an array
- hyperCMS Event System for automatization based on events
- changes in hyperCMS API
- support for JSP, ASP, HTML/XML/Plain Text Files

version 4.2.0
release 12/2003
new features:
- new icons and overworked template engine with support of editing multiple
  components directly in EasyEdit, article buttons were changed to clock icon
- PRE amd POST events in hyperCMS Event System
- bug fix for double entries for usergroups when creating the same group
  several times

version 4.3.x
release 01/2004 - 02/2004
new features:
- new inheritance settings (for components and also templates)
- improvements in template engine
- improvements and changes in workflow management
- new navigation tree supports incremental generation of subtrees (faster)
- wallpapers for navigation tree are no more available
- multiple components are not restricted by string length of an URL (GET-method)
- memory function for page and component linking (last visited location in page 
  and component folder structure will be remembered)
- improved publication management (user will see publication without relogin)

version 4.4.x
release 03/2004 - 01/2007
new features:
- New features in publishing of objects. Connected objects in the same publication 
  will be published automatically, if one object will be published or unpublished. 
  Also pages without dynamic component linking will be automatically republished after 
  a used component will be published. 
- extended support for editing XML-objects with EasyEdit
- switch from WYSIWYG to form view for editing content
- seperated APIs for filesystem (virtual server) and Tamino (server)
- cut, copy and paste of objects between several publications (server version only)
- new feature "Journey through Time" that allows a visit of the website in the past
- changes in the Database Connectivity when writing data to external data sources 
- changes in the Tamino Database Connectivity due to a Tamino bug concerning writing
  XML documents in Tamino (Tamino can not determine the character set!)
- changes and improvments in the database connectivity and event system
- advanced support for other browser 
- image gallery view

version 4.5.x
release 04/2007 - 08/2007
new features:
- automatically uncompress uploaded files

version 5.0.0
release 09/2007
new features:
- DAM functionality (former medie database does not exist anymore, new architecture based on multimedia components)
- upload and index content from documents
- upload and thumbnail generation for images, PDF-docs
- preview of uploaded videos using streaming technology (FLV-Format)
- new meta data templates for folders and multmedia objects
- multimedia components/objects are stored in the component structure and replaces the old media database 
- integration of RDBMS for search functionality (MySQL)
- detailed search on the basis of template text-fields

version 5.1.0
release 03/2008
new features:
- multiple selection of items in object lists holding control key or shift key and mouse click
- permisson settings for all desktop entries
- permission settings for mail-link to components and pages

version 5.2.x
release 04/2008 - 09/2008
new features:
- history of all recipients of sent objects
- report of recipients in object info
- context menu for user administration
- send link to multiple users and to group members

version 5.3.x
release 10/2008 - 08/2010
new features:
- upload of multiple files at once and upload progress bar (Flash plugin required)
- new SWFUpload for Flash Player 10 support
- remoteclient support
- multiple storage device support (configuration in config.inc.php)
- zip-compression of files on server side
- download of multiple files using temporary zip-compressed file
- zip-extraction of zip-files contents on server side
- sending multiple files links via e-mail
- sending multiple files as attachment via e-mail
- new content repository structure (new sublevel for 10.000 blocks)
- fixed problem: multimedia components from other publications had the wrong publication
- new media player
- media/video rendering based on user settings saved in a media player config file
- media/image rendering in size during upload of new image files and manipulation of size and format
  of existing image files
- WebDAV support for multimeda files
- integration of ODBC besides mySQL in DB Connectivity

version 5.4.x
release 09/2010 - 06/2012
new features:
- publishing queue for time management
- import and export of content via import/export directory
- bug fixes in checkin
- autosave function for content
- expand and collapse of content controls
- individual video resolution for FLV video rendering
- support for content indexing of word 2007 (docx), powerpoint (ppt, pptx)
- db_connect function rdbms_setconent will encode texte to UTF-8, table textnodes will provide a search index in UTF-8
- search from for RDBMS is therefore also UTF-8 encoded
- new crop images feature 
- minor bug fixes
- improved send link form (including user search and tabs)
- new CKeditor as rich text editor integrated to suppoprt Google Chrone and Safari
- redesign of GUI incl. shadow effects
- hashcode for users for webdav support
- access to (nested) folders can be excluded via folder access defined in groups
- improved export/import incl. link transformation in text content nodes
- added new function in db_connect 
- added new functions in hypercms_api

version 5.5.x
release 07/2012 - 02/2013
new features:
- getconnectedobjects function works with contentobjects of the given container and without link management database (faster)
- full WebDAV support for multimedia components
- support of special characters in object and folder names
- new html5 video player with flash fallback
- integration of videos in rich text editor
- prepare for PHP 5.3 regarding deprecated register_globals and eregi_replace support 
- improved and fixed contextmenu
- paging in objectlist explorer
- load publication config in hyperCMS and not via main config
- meta data keyword and description generator in API
- secure token used to save content
- modernized JS code for form and elements access
- document viewer based on google service
- language settings support, new hypercms-tag for language
- link management database will not be managed if link management is disabled
- improved security by killing the users session if a permission is missing
- text diff of content versions
- explorer object list with new views (large, medium, small thumbnails and details)
- new download-events in event system
- daily statistics of file access (new table dailystat)
- secure password option to prevent users from using simple passwords
- logging and locking of client IP on logon (10 failed attempts lead to banning of the IP)
- video converter extended to support the follwing formats: flv, mp4, ogg, webm
- different video player configs for each formats in new popup
- indexing of Excel (xlsx) files and Open Office Text files
- file based search support removed, search is only supported if a database is in use
- meta data of images will be indexed automatically and not by call in event systems. the mapping file can be found in /data/config
- inline editing in easyedit mode (need to be activated in config.inc.php)
- document converter based on UNOCONV
- image rotation for original images
- media file attributes (file size, with, height, image colors, image color key, image 6type) are saved in media table (requires database)
- images search (image size, image main color, image type)
- new image brigthness and contrast setting and preview before saving the image
- bugfix in API function "manipulateobject" when link management is turned off and copy & paset is used
- search based on content in file explorers (insert link and media)
- new functions for reading request parameters
- accumulated access statistics and file size statistics for folders
- case-insensitive xml parser functions in API (not used)
- meta data mapping can be freely defined using mapping in tree navigation 'meta data templates'
- editing permission for content based on group names (new 'groups' attribute in hyperCMS tags)
- advanced search for object-id and container-id
- bug fix: media and image rendering buttons can only be seen if localpermission is set
- task management included in send mail-link as option
- new hyperCMS date tag to support date picker
- CSRF protection: maximum request per minute and per session
- video cut based on video time stamps
- improved security by removing the container name in GET requests

version 5.5.9
release 03/2013
changelog:
- several bug fixes in the template engine (component preview was not working in some cases, links were not transformed after 'showinlineeditor').
- js-code for video in inline editing mode was escaped like the rest of script-code after saving.
- bug fix in object list when ctrl-key selection was used on objects where one name was the substring of the other name.
- several changes in language files.
- bug fix in media explorer when going back to root element.
- bug fix in character encoding when html code was allowed in unformatted text areas.
- check of 'localpermission' on the level of content control and not only on actions level if DAM is enabled.
- permission problem with view of inherited objects.
- bug fix for copy & paste of multimedia objects (media attributes were not copied/created in the database).

version 5.5.10
release 03/2013
changelog:
- bug fix in user form, two message layers were shown.
- changes in language files.
- bug fix in editorf. content-type was JS-escaped which caused charset problem in IE (IE used default charset).
- bug fix in template engine. html & body tag was included in preview of components which were included in pages.
- bug fix in file upload. error message for file names exceeding the max. digits value was not shown.
- removing js-code and form code from formlock-view in template engine.
- new security token in user, group, workflow, personalization, and template management.
- removing and escaping of html/JS code in meta data template preview.

version 5.5.11
release 03/2013
changelog:
- bug fix in 'convertlink'. wrong array key for the publication configuration.
- remove direct link from object info if DAM only usage is activated.
- remove task if object does not exist anymore.
- bug fix in 'hcms_crypt'. the first 8 digits were used instead of the last 8 of a given string.
- removed template upload functionality.
- new security token concept with a general token for all actions instead of individual tokens.
- new hypercms-tag providing comment functionality for pages and components.
- upload files directly in the component explorer.
- search engine provides default search in meta data and object names (search restriction 'only object names' can still be used).
- live view of files including javascript code is disabled.
- bug fix in object list when ctrl-key selection was used on objects. when selecting, deselecting and selecting the same object an empty entry was left in 'multipbject'.

version 5.5.12
release 04/2013
changelog:
- super admin can be set in main user administration
- loadlockfile supports unlocking of files locked by other users after a given number of seconds
- bug fix in 'workflowaccept' and 'workflowreject'. the query did not include the combination of publication and group membership in the memberof-node.
- bug fix in user objectlist. the query did not include the combination of publication and group membership in the memberof-node.
- bug fix in userlogin. the session was not destroyed if user was not logged in.
- bug fix in explorer_wrapper. only script-tags were checked for preview in DAM, which still leads to security issues if JS is used in an uploaded file.
- default link-type in user_sendlink is the download link.
- user have to submit their old password to change the password. User administrators with permissions to edit users can change the password of other users without providing the old password.
- publishing and workflow can be applied on multimedia components
- bug fix in editgroup (some desktop permissions arrary keys were not correct)

version 5.5.13
release 05/2013
changelog:
- seperation of API (hypercms_api.inc.php) and UI elements (hypercms_UI.inc.php)
- bug fix in rdbms_searchcontent (db_connect). The SQL syntax used an inner join to the search in textnodes and object names. the search in textnodes and object names uses OR operator.
- send mail-links including lifetime / period of validity: time token can be used in access-, download- and wrapper-links.
- bug fix: container in content version and buildview was missing
- improvements in RDBMS connectivity. new attribute filetype in table media to avoid the use of lame mySQL string function.
- new function getvideoinfo in hyperCMS API

version 5.5.14
release 05/2013
changelog:
- new search in top frame for search over all objects of all publications
- new loading bar image
- remove file size in info-tab of pages and page folders
- send mail link to object directly from control_content_menu
- new video player code based on iframe and several new
- calculation of file size and number in rdbms_getfilesize, new function getfilesize in hyperCMS API
- limited packing of source files in a zip files based on the source file size (see config.inc.php)
- bug fix: search for user files didn't work due to missing parameter and missing check of action parameter
- bug fix: icon of checked out items are not grey

version 5.6.0
release 07/2013
changelog:
- themes for hyperCMS GUI
- video screen capture (thumbnail jpeg)
- document preview based on PDF reader plugin of browsers (google docs service for older browser still in use)
- several changes in CSS classes
- mobile client for content editing
- bug fix: infotype=meta was not set if used in hyperCMS Tag Wizard
- bug fix: body end tag to component views was not added in template engine
- error message for zip-file downloads larger than max. zip-size
- IPTC meta data injector for image files
- alert message when functions in the objectlist control are blocked by the search window
- new HTML5 multi file upload with drag & drop support, SWFUpload as fallback
- Component strcuture was renamed to Asset structure
- filters for file-types in object list
- bug-fix: updating files (change of the file extension) with special characters in their file name resulted into wrong names
- media viewer based on jquery plugin (images carousel and zoom)
- if download link exceeds limit an access to the system will be provided using a default user
- bug fix: status popup window didn't close automatically
- bug fix: problems with tabs and z-index in IE
- bug fix: encoding problem in text area of video embed code in IE

version 5.6.1
release 07/2013
changelog:
- download of predefined image sizes and formats (defined in config.inc.php)
- user interface for license notification configuration
- shortened access/download/wrapper-links and URL-forward in index.php
- no folders in list view in mobile edition
- support of mail-links to open mobile edition

version 5.6.2
release 07/2013
changelog:
- bug fix: file upload in component explorer was using the old file name for the uploader
- show meta information (EXIF, IPTC, XMP) in info-tab
- size of media files in 72 dpi and 300 dpi resolution is shown by showmedia
- new unique and secure hash codes for link access. encryption is not used anymore to create access/download/wrapper links.
- bug-fix: imagemagick removed metadata from files, metadata will be restored by EXIFTOOL
- write XMP data to files using EXIFTOOL
- web2print
- bug fix: clipboard was set to false on cut/copyobject, only one object was copied if more where selected
- new parameter in buildview to execute the script code in templates
- public download setting enables/disables download and wrapper links in info-tab, if disabled only mail-links (access/dwonload) can be used
- bug fix: view/download buttons in search_objectlist had wrong style-class
- bug fix: general search when access via mail-link was not working due to error in framest_objectlist
- unpublished objects are greyed out, locked objects use the locked icon
- create all videos for video player at once
- new media and image rendering layout with overlapping menu
- plugin system
- bug fix: function zipfiles used echo and caused bad download
- function settext saves multimedia files saved by editor in the link index
- link editing: preview of the selected page
- licensenotfication supports different date formats (same as date picker textd)
- sender of mail-links will be stored in table recipients

version 5.6.3
release 08/2013
changelog:
- table recipients includes sender information (user name) and date includes time as well
- bug fix: copy & paste of images didn't include all video formats and the video config file
- new variable %hcms% for the URL of hyperCMS, used for video embedding in editor
- new function convertimage as wrapper for createmedia. useful to convert images to other color spaces for PDF-rendering
- sendlink setting in publication enables and disables the sendlink function as well
- bug fix: video player didn't play video, explorer_wrapper optimsation for the straming of videos
- converting images for tcpdf to greyscale, CMYK color space
- flip/flop images vertically and horizontally, add effects (sharpen, greyscale, sepia-tone, sketch, monochrome) to images, change colorspace (RGB, CMYK, Gray) using imagemagick
- bug fix: using non UTF-8 charsets, the special characters in the form view are not properly escaped for links-texts and media-alttexts. convertchars is used in link_edit_page and medie_edit_page
- allowed IP addresses for media access can be defined in publication settings
- theme can be defined in publication settings (global in config.inc.php, publication scope in publication config, user scope in user setting)
- bug fix: in createmedia, the buffer file was created for thumbnails if the thumbnail file didn't exist.
- access statistics show total hits and filesize
- bug fix: manipulateobject could not delete files when link-DB was deactivated ($allow_delete = true;) 
- use alt-key to switch to download links in object lists
- new windows have the container id of their object
- new info box for hints regarding the system usage, see function showinfobox
- generate google sitemap with function getgooglesitemap
- openSEARCH implementation
- bug fix for audio-files
- bug fix: zipfiles didn't check access permissions of user
- bug fix: download of files using alt-key didn't ceck access permissions of use

version 5.6.4
release 11/2013
changelog:
- geo location in access statistics
- new onaccess event to analyze download, wrapper and access link requests
- gallery view component template
- 360 image view component template
- publish videos to youtube
- OpenAPI (Webservice for executing functions, like: create/edit/delete of users/folders/objects and file upload/download)
- notification alerts to logged in user based on folder settings and events

version 5.6.5
release 02/2014
changelog:
- edit several selected objects
- DropBox integration, see: https://www.dropbox.com/developers/dropins
- publish videos, images, text to facebook (using Facebook SDK)
- object and folder names are trimed on create and rename
- check for duplicate entries on file upload using MD5 hash of files
- geo location of files (images) and search based on map with dragable selection
- create individual videos (new size wxh file extension instead of thumb)
- import/export GUI
- bug fix: rdbms_searchcontent ignored text_id in advanced search
- bug fix: rdbms_getmedia used wrong refered for SQL-query
- bug fix: editing of multiple objects didn't include checking constraints of unformatted fields
- bug fix: popups of template_edit submitted forms instead of only calling a js-function(input type of button was "submit") which lead to logout
- bug fix: create hidden folders was not performed when super admin logged in
- bug fix: check of hidden folders looked in the whole file system path and not only in the root given by the publication (this led to hidden folders that should be visible)
- bug fix: rdbms_createobject fired before successful creation of a new object file (this led to duplicates of same object in DB)

version 5.6.6
release 03/2014
changelog:
- versioning of media files
- bug fix: injectmetadata (CDATA was missing)
- CKEditor version 4.3.3 implemented (support of DIV and IFRAME, support for IE 11)
- versioning of media files when using workplace integration (WebDAV) to edit and save files (content versioning need to be enabled)
- relocation of config.inc.php to config directory
- install procedure for easy installation

version 5.6.7
release 05/2014
changelog:
- code improvement (undeclared variables, PHP global in function input)
- bug fix: rdbms_getdailystat did compare datetime with time (convert datime to time)
- bug fix: duplicate names were not converted to readbale names
- bug fix: single file upload produces error message about the same file existing already
- change in createthumbnail_indesign to create a valid thumbnail (use last thumbnail of indesign file)
- bug fix: set unset variables (error notices for unset variables in php log)
- bug fix: media category could not be deleted
- bug fix: the encoding in containers was not set to UTF-8 on media files and folders
- bug fix: advanced search didn't get the ownergroup and could therefore not check the content access permissions (attribute groups for hyperCMS tags)
- bug fix: search for image size range did include the given width as additional SQL where clause and led to wrong search results
- bug fix: name of variable was not correct in WebDAV function deleteGlobal

version 5.6.8
release 07/2014
changelog:
- bug fix: the content of date fields was always converted to the international date format, this led to a wrong format after loading the container
- bug fix: %comp% was not converted to the component URL in the template engine for single component when onEdit attribute was set to "hidden"
- bug fix: %comp% was not converted to the right publication or management component URL depending on the view in the template engine for components when onEdit attribute was set to "hidden"
- bug fix: selector layer for download on mobile devices was not properly positioned
- bug fix: download of files on iphone is not supported, mime-type was changed to the actual mime-type of the file to show it in the browser
- bug fix: getthemelocation used wrong session variable name, mobile CSS were not used
- bug fix: component_curr lead to too long request-URI when editing multiple components. the component_curr request variable was redundant and has been removed to solve the problem.
- improvements in mobile CSS
- bug fix: empty inheritance database blocked createpublication
- improvements of creating a unique token if a session_id is not available (session_id used for un/locking files)
- implementation of installation process including search engine 
- bug fix: limit was not set in rdbms_searchcontent
- bug fix: template engine issue with language session variable
- improvements in compare content versions, multimedia content from files will be compared as well as meta data (taken from content container)
- seperation of template includes (new expression) from page and component templates in the GUI
- bug fix: search_objectlist.php used wrong input parameter for function rdbms_searchcontent
- bug fix: object name was missing in event log when removing objects 
- bug fix: the template of folders (should be meta) was set to the wrong category
- bug fix: edittemplate did not trim the file extension which could lead to white spaces after the file name end that can hardly be recognized
- edit and save text based content from template media files
- bug fix: edittemplate transformed &amp; not correctly to &
- bug fix: control frame has not been reloaded in easy edit mode 
- bug fix: history buttons for next/previous navigation has been disabled for object and not for files
- bug fix: template_editor showed commentu and commentf for component and page templates but should only be used for metadata templates
- bug fix: downloadfile did not exit if file ressouce is missing
- bug fix: user_edit did not check siteaccess permissions of the user
- bug fix: sort of sites in user_edit did not use natural case sort
- bug fix: settext did not check the link index properbly which led to wrong entries in error logs
- bug fix: undefined varibale hypercms_livelink_set in template engine
- bug fix: delete of media reference in media_edit_page did not work for IE
- implementation of download/upload statistics of all DAM publications on home screen

version 5.6.9
release 08/2014
changelog:
- new template variables for the external repository in template engine
- tranformation of special characters in deconvertpath
- new checksum in user session file to verify permissions set in session
- bug fix: getfileinfo did not set filename_only and published state for files without file extensions
- bug fix: wrong variable names led to undefined variables in createpublication
- bug fix: several undefined variables 
- font of unformatted text remains unchanged when using inline editing (CSS optimization in showinlineeditor_head)
- deletepublication removes files in external repository, page and component root folders
- new cleaning level management config setting to check and clean template code
- bug fix: in thumbnail generation using the GD library since aspect ratio has not been preserved, different behaviour compared to resize parameter in ImageMagick
- automatic resize of textarea in inline editing mode 
- bug fix: setmedia replaced publication media path first, which led to wrong media reference since the ediot passes the full path incl. the domain
- bug fix: installation prozedure added two / instead of one if a subdirectory is used for the installation root
- bug fix: toolbar for editor in DAM mode could not be set
- improvement in function scriptcode_extract (removes comments in scriptcode)
- install procedure migration to mysqli
- removed trim from all load- and savefile functions since it led to bad behaviour when line break need to be preserved at file end
- bug fix: checkout objects did not support sidebar
- bug fix: error in function getconnectedobject which caused the creation of working containers for checked out objects (loadcontainer required user as global input)
- changes in job for licensenotification, monthly: next month will be checked each 1st of the month, weekly: next week will be checked each sunday
- support for DPI, colorspace, ICC profiles in formatted texts (textf-tag) and images (mediafile-tag)
- bug fix: error in call to js-function hcms_validateform in function showinlineeditor_head
- bug fix: remove reseting of image size if scaling is used in CKEditor image-plugin
- bug fix: inline editing mode issue when onedit and onpublish attributes are used for the same hypercms tag

version 5.6.10
release 10/2014
changelog:
- improvements in plugin management
- new individual button in UI function showtopbar
- bug fix: function downloadfile called dailystat for each partial file download (only first partial download triggers dailystat)
- new Simple Statistics plugin (not part of standard software package)
- bug fix: exception for CSS and JS files in follow link (page_view)
- new Keyword Statistics plugin (not part of standard software package)
- changed order of meta data extraction of files due to issue with Adobe XMP special characters, new order: EXIF, XMP, IPTC
- bug fix: function showtopbar did not show individual button
- bug fix: function downloadfile did not provide proper dailystat, if requested start bytes were not zero
- bug fix: page_multiedit didn't show label names defined in template and applied constraints on unformatted text fields even if they were disabled
- implementation of multiple hyperCMS instances using different databases, internal and external repositories
- implementation of instance manager GUI to manage multiple hyperCMS instances (as part of connector)
- implementation of DB connect in multiedit
- improvement in notification of user, if user is owner of an object no notification will be sent
- bug fix: missing check of globalpermission in explorer_objectlist
- bug fix: added removing of IPTC meta data using EXIFTOOL in function iptc_writefile before writing new IPTC data to file
- bug fix: function notifyusers used file owner and not current user to compare with the notified user
- bug fix: new version of FFMPEG requires new option names (e.g. -b:v for the video bit rate instead of -b), options for FFMPEG have been changed in config.inc.php, media_rendering.php and function createmedia
- bug fix: new version of FFMPEG does not support an audio sample rate of 44100 Hz for FLV files, changed default to 22050 Hz, options for FFMPEG have been changed in config.inc.php, media_rendering.php and function createmedia
- bug fix: deleteobjects did not remove individual video files with sub-file-extension .media
- implementation of new function getoption for extracting values of a string holding options (used for image/audio/video options)
- creating a thumbnail image on initial upload of a video file
- bug fix: function loadcontainer did not return the container information for versions of a content container
- improvements in task management for broken links
- new theme namend colorful
- bug fix: download of other file formats than original did not work
- bug fix: undefined variables in several scripts
- bug fix: click on object shows wrong object in sidebar if sort has been applied
- bug fix: clicking on an object in gallery-view highlights wrong object if sort has been applied
- improvements in all object list views
- improvements in preview of object / sidebar
- bug fix: image brightness was set to -100 for image editing due to a wrong variable name
- Improvements in object lists regarding alignments of list/gallery items
- creating new components when adding components to a page by the component explorer
- securing all shell input parameters
- support for video files with no configuration file in repository
- bug fix: video cut was not able to process hours, minutes and seconds of one digit
- several improvements in video editing including an infobox
- new home screen and home navigation item in navigator
- new boxes on home screen for recent tasks and recent objects of logged in user
- improvements in mobile style sheets
- bug fix: function getfiletype searched for a substring in file extension definitions without a delimiter, this lead to wrong file-type in media table
- bug fix: new function shellcmd_encode to solve tilde issue with function escapeshellcmd in various files
- reorganization of functions in the hyperCMS API
- implementation of new check permissions functions
- bug fix: DB connect RDBMS did not provide hash keys for all search operations
- bug fix: permission issues in explorer for publishing queue, log-list and plugins
- bug fix: implementation of updated function getbrowserinfo (old function was outdated and did not detect the browsers correctly)
- implementation of sort order for workflow folder form
- bug fix: removed double sort from search result
- bug fix: search for user files in user management did not use proper frameset and resulted in an error on click on an object
- bug fix: removed : from meta data listing if label is empty
- bug fix: checkadminpermission expected input parameter
- set variable lang to "en" if no value is given
- bug fix: link_explorer of editor passed wrong input to function rdbms_searchcontent
- improvements in DB connect RDBMS
- bug fix: scrolling in link and media explorer did not work due to changes in the CSS class
- implementation of new function checkpublicationpermission
- bug fix: convert of formats did not work due to missing convert-type and convert-config inputs in context menu for checkout, queue and search object list
- bug fix: set min-height of fields to avoid collapsing of empty fields in version comparison
- bug fix: the user edit permission for a specific publication was not checked properly and led to killsession

version 5.7.0
release 11/2014
changelog:
- implementation of user chat with object-link exchange
- new configuration setting for chat
- integration of top in frameset_main and replacement of frames by iframes
- implementation of logout on window close (not supported by all browsers)
- bug fix: charset declaration was missing in explorer
- removed timeout.js from all controls and js-library
- integration of chat in mobile edition
- optimizations in main and contextmenu JS library
- implementation of new settings for background and alpha when converting PDF to image (due to black background issue)
- replacement of all framesets by iframes
- bug fix: error messages on group_access_form, worklfow_folder_form were not shown since object IDs and object paths were mixed up
- implementation of resize function for group_access_explorer
- bug fix: search_explorer used wrong inital_dir for the component root
- improvements in inline editing, dynamically adjust width and height of textarea after loading the inline element

version 5.7.1
release 12/2014
changelog:
- new function processobjects replaces publishallobjects and can handle the actions 'publish', 'unpublish' and 'delete' to process queue entries
- new feature "delete from server" on upload of new files in order to remove them again on a certain date and time
- no resize of thumbnail if original image is smaller than defined thumbnail size
- propagate all results from function createmediaobject to function uploadfile
- function showmedia shows original thumbnail size if the thumbnail is smaller than the defined thumbnail size due to a small original image
- improvement of information in main config
- bug fix: when using the GD library instead of ImageMagick, the aspect ration of images have been changed when creating other formats
- bug fix: undefined variables in indexes in control_queue_menu and function manipulateobject
- change personal theme in GUI immediately and not after next logon
- removed statistics on home screen of mobile edition
- bug fix: hyperCMS API used deprecated JS function hcms_openBrWindow
- bug fix: top-bar spacing issues in mobile edition 
- replacing framesets by iframes in instance manager and import/export manager
- define date and time to remove files from system on upload
- set default language and lang variable in function userlogin
- bug fix: relocation version information on home screen for better support of small mobile screen resolutions
- bug fix: JS function hcms_showContextmenu did not disable icons for notfication and chat in context menu if no object has been selected
- bug fix: explorer_objectlist did hide notfication and chat completety in context menu if user has no permissions
- improvements in hyperCMS UI document viewer regarding size of view
- bug fix: funtion getusersonline returned WebDAV users, these users are not able to chat and must not be returned#
- fixed showmessage boxes positioning in GUI
- rounded corners of showmessage boxes
- improved design of context menu for all themes
- removed mobile theme as an option in publication management
- bug fix: the template engine did not include the publication configuration if the link management has been disabled, therefore component have not been included by function insertcomponent
- bug fix: access permission has not been when using the editor to select a media file. this led to issues with the video preview.
- if the provided thumbnail size of the main config is greater than the original video size the original video size will be used for video rendering
- implementation of loading screen for file unzip
- bug fix: function deconvertlink removed host name from page links twice. this led to a miss-converted link since the host name has been cut of again without using the function cleandomain.

version 5.7.2
release 01/2015
changelog:
- encryption for content containers to secure data an server side
- new publication setting for container and media file encryption
- new function loadfile_header to load file partially in order to determine if fill is encrypted
- removed Tamino support
- removed all $_SERVER['PHP_SELF'] in all forms due to XSS weakness
- bug fix: working content container could be checked out by several users
- bug fix: function loadcontainer restored working container if it was locked by another user
- bug fix: CSS issue with chat in IE 8 and 9
- function copymetadata has been removed from explorer_download and explorer_wrapper and has been integrated in function createdocument
- new functions encryptfile and decryptfile
- watermarking for images and videos based on image and video options in the main config file
- function createmedia supports gamma, sharpness, brightness, contrast and saturation for video editing 
- bug fix: impementation of binary-safe encryption and decryption to en/decrypt binary files
- bug fix: popup_action did not hide loading layer if an error occured after unzip
- 2 new input parameters (object_id, objectpath) for function rdbms_getqeueentries
- implementation of information regarding the auto-remove of files/objects in mail form, info tab of objects and sidebar object viewer
- function getcontainername supports container ID or container name as input
- improved implementations of functions hcms_encrypt and hcms_decrypt for standard and strong en/decryption
- function showmedia provides additional information about owner, last published date and date of deletion
- implementation of file caching to reduce I/O for action chains that would save data in each step
- bug fix: checked out file for user has not been created and deleted in function createuser and deleteuser
- improvement of page_checkedout that creates checked out user file if it does not exist
- using database media information as primary source for all media displays
- bug fix: sidebar on checkout objects list was not displayed properly
- freegeoip.net stopped providing it's service, changed to API of ip-api.com
- linkengine is automatically disabled in the publication management for DAM configurations
- function showmedia support new type "preview_download", which only enables the download in media previews
- videos can be downloaded and embedded in video editor as well
- function getvideoinfo extracts audio information as well
- new column createdate in table container, change of column date in table container to type datetime (requires update script)
- function rdbms_getmedia provides extended media information
- function downloadfile supports new type "noheader" without any HTML headers tp provide file download for WebDAV
- function image_getdata has been renamed to extractmetadata
- new function id3_getdata, id3_create and id3_writefile to support ID3 tags of audio files (e.g. mp files)
- support for ID3 tags of audio files in mapping
- support for thumbnails of audio files
- new input parameters for function getgooglesitemap to show or hide the frequency and priority tags
- optimized database attributes

version 5.7.3
release 02/2015
changelog:
- add original video files of type MP4, WebM, OGG/OGV to source of HTML5 player
- bug fix: encryption level for file must be 'strong' in order to be binary-safe
- bug fix: video start poster could not be defined if the original file was added as source to the HTML5 video player
- update of HTML5 video player to video.js version 4.11.4
- bug fix: copy & paste not working, function manipulateobject did not set init-input for function savecontainer
- function getcontentlocation adds missing zeros to container ID to correct the containers directory name
- additional check if object exists when publishing it
- improvements of several functions in hypercms_main
- bug fix: in order to support older versions, the original source need to be added to the video player, not only in case of an existing config.orig file
- function rdbms_getobject_hash supports also object ID and container ID as input
- function createdownloadlink and createwrapperlink supports also object ID and container ID as input
- change to MP4 format as the standard for video thumbnail files in function createmedia
- link for the video start poster of the embed code will be converted to a wrapper link if publication is a DAM in order to have access to the image file
- implementation of RAW image support for formats: arw, cr2, crw, dcr, mrw, nef, orf, uyvy
- bug fix: remove _original files from media repository created by EXIFTOOL
- redesign of video player with big play button in center
- bug fix: ok button of task list was not displayed properly
- bug fix: function publishobject did not set init for savecontainer to true
- implementation of a new language management system
- implementation of new languages. besides English and German the following languages for the UI are now supported: Albanian, Arabic, Bengali, Bulgarian, Chinese (simplified), Czech, Danish, Dutch, English, Finnish, French, German, Greek, Hebrew, Hindi, Hungarian, Indonesian, Italian, Japanese, Korean, Malay, Norwegian, Polish, Portuguese, Romanian, Russian, Serbian, Slovak, Slovenian, Spanish, Somali, Swedish, Thai, Turkish, Ukrainian, Urdu
- new help logic to set 'en' as default help/manual
- implementation of function html_encode with multibyte character set support
- bug fix: user_sendlink mixed up languages in e-mail message
- implementation of new 'flat' design theme
- minor changes in other design themes

version 5.7.4
release 03/2015
changelog:
- implementation of favorites feature (create and manage favorites)
- function rdbms_getobject_id supports object path and hash as input
- implementation of new functions createfavorite, getfavorites and deletefavorite
- new permission for favorites management
- change of checked out button behaviour in control_content
- implementation of new function getlockedobjects for checked out objects
- removed file page_checkdout
- implementation of natural case sort for function getlockedobjects and getfavorites
- new function getescapetext to HTML encode specific texts from the language files (needed when presentation uses other character set than the language file)
- implementation of escapetext in template engine and UI instead of converting all texts of a language file
- bug fix: html_encode was double encoding if ASCII was selected as encoding
- bug fix: content-type has not been set for various input forms
- implementation of management of home boxes for each user on home screen
- implementation of new function setboxex and getboxes
- implementation of JS function hcms_switchSelector in main.js
- implementation of new homeboxes for recent downloads and uploads of a user

version 5.7.5
release 03/2015
changelog:
- bug fix: bulgarian language file used double quote in string
- bug fix: html_encode used wrong variable name
- implementation of media preview for select of multiple objects
- select, edit/render and save multiple images and videos
- implementation of new services renderimage and rendervideo replace old media rendering logic 
- implementation of new service savecontent 
- bug fix: richcalender language files needed to be converted to UTF-8
- bug fix: function manipultaobject did not check if both page states (published and unpublished) exists already in the same location on rename
- bug fix: undefined variable in function rdbms_getobject_id
- bug fix: text of JS prompt messages in template_edit was not html decoded
- template editor supports include-, script-, workflow-tags for meta data templates
- meta data templates for media files will be assigned to the application 'media'
- implementation of hyperCMS scripts for multimedia objects
- bug fix: correct undefined characters in key names and eliminate double expressions in all language files
- new uploadfile service that replaces upload_multi
- improvements in design themes (CSS)
- implementation of transcoding for video files to audio files
- implementation of a new hyperCMS connect API for FTP support
- implementation of file download from FTP servers
- implementation of new function is_date
- implementation of date validation in function rdbms_createqueueentry
- bug fix: function getmimetype did not return proper mime-type for object/file versions
- implementation of object versions support for function getfileinfo
- bug fix: diplay proper file icons in versions tab
- bug fix: pop_status failed to authorize the action when publishing the root folder of pages
- update of TCPDF to version 6.2.6
- implementation of download formats for download/access links and attachments
- implementation of new function convertmedia (wrapper for createdocument and createmedia)
- bug fix: source location input parameter of function createimage was not verified
- implementation of new functions is_document, is_image, is_rawimage, is_video, is_audio
- bug fix: function unlockfile, lockfile, savelockfile, loadlockfile used global variable user which overwrites the input variable
- bug fix: simple keywords plugin refered to old search objectlist location
- implementation of new function createversion
- support for versioning of thumbnail files
- bug fix: saving multiple media files was not working when media files are not of same type
- implementation of delete for thumbnail file versions in function manipulateobject and version_content
- implementation of video rotation and video flip for video editing
- improvements in video editor layout
- implementation of force_reload paramter for function showvideoplayer to force reloading the video sources
- new configuration parameter for default video and audio previev files (type = origthumb)
- modifications of function createmedia to support new configuration paramaters
- implementation of new function deletemediafiles (deletes all derivates of a media ressource)

version 5.7.6
release 04/2015
changelog:
- implementation of new function checkworkflow in hypercms_main used by function buildworkflow
- update of pdf viewer to version 1.0.1040
- bug fix: improved CSS definition using filter for hcmsInfoBox due to issues on home screen
- bug fix: select media in form view did not work due to missing evaluation of selectbox in JS function getSelectedOption
- bug fix: media_view did not validate form fields for width and height in control frame
- bug fix: function buildview of templateengine did reset tag variables for each tag found in the template
- implementation of media object evaluation in media_edit_page and link object evaluation in link_edit_page
- improvements in function setmedia by using function loadfile_fast for object loading
- bug fix: wrong content-type specification in version_template
- update of HTML5 video player to video.js version 4.12.5
- bug fix: undefined variables in search_objectlist, hypercms_tplengine, db_connect_rdbms
- implementation of new hypercms_tcpdf.class.php file with class hcmsPDF to extend standard TCPDF functionality
- implementation of new function drawCropbox in class hcmsPDF
- removed deprecated pdfsearch.class.php
- bug fix: explorer_download did not check original-type and tried to convert media
- bug fix: when providing a colorspace or ICC-profile in media- or textf-tags the images would have been converted multiple times, depending on the occurrence of the tag in the template
- implementation of new hyperCMS tag attribute 'pathtype' for media tags to declare path as file system path, URL, absolute path (URL without protocol and domain)
- implementation of new media functions mm2px, px2mm, inch2px, px2inch
- improved function convertimage which supports new input parameters and rendering features
- implementation of timeout in media_view to ensure the media size fields in the control frame will be updated
- removed convert to intermediate BMP file in function createmedia to keep transparency of images
- implementation of thumbnail support for file generator in function buildview
- implementation of error reporting for the generator in the template engine (function buildview)
- implementation of new function placeImage in class hcmsPDF
- implementation of new function is_aiimage to determine if a certain file is a vector-based Adobe Illustrator (AI) or AI-compatible EPS file
- implementation of image denisty and quality for image rendering in function createmedia and convertimage

version 5.7.7
release 05/2015
- implementation of load balancing for file upload and rendering files
- implementation of new setting for load balancing in main configuration
- implementation of new function HTTP_Proxy and loadbalancer
- implementation of new function getserverload
- implementation of new service getserverinfo (based on function getserverload)
- temp and view directories can be set in main configuration
- moved temp and view directory to the internal repository (for load balancer)
- moved instance configurations to the internal repository (for load balancer)
- new function createusersession in include/session.inc.php
- implementation of main management config in hyperCMS API loader
- changed include order for every file: config.inc.php -> hypercms_api.inc.php -> session.inc.php
- implementation of new function writesessiondata for load balancer
- function setsession supports 3rd argument to write session data for load balancer
- variable $appsupport has been replaced by $mgmt_config['application'] in main configuration
- improvement in function getescapedtext to escape special characters if no encoding is provided
- use of function getescapedtext for all text strings used in JS code 
- implementation of new ICC profiles for ECI offset 2009
- support for transparent background of SVG files
- bug fix: frame resizer in control_content did not work

version 5.7.8
release 06/2015
- implementation of multi assets tag for DAM usage
- bug fix: correct and optimize html code in form views of template engine
- bug fix: horizontal and vertical flip of images in multiedit mode not working
- implementation of new function getobjectid
- function setcomplink converts multimedia object paths to object IDs and saves them in the content container (not in the link index) in order to support component links in DAM that is not using a link index
- implementation of object ID support for single and multiple components in template engine, component_edit_page_single and component_edit_page_multi
- implementation of accesspermissions in function compexplorer for DAM usage
- changes in showcase templates in install directory to use the new view directory setting
- bug fix: download of original files for access links failed if media file was not an image or document
- optimizations in function rdbms_searchcontent regarding joins of tables
- implementation of search format support in search of function compexplorer
- bug fix: media_rendering for audio files did verify non-existing fields that caused JS error
- bug fix: audio data in config files for ogg files have been missing
- audio quality setting has been enabled for editing of audio files
- validation of theme path in function getthemelocation
- various improvements in search_api for website search functionality
- bug fix: if no application was defined in the content containers of assets the template engine did not execute the published object

version 5.7.9
release 07/2015
- optimization of language files
- new input tag settings in main CSS for all themes
- update of jquery from version 1.9.1 to 1.10.2
- implementation of keyword tags with optional mandatory or open list of keywords using the new hyperCMS tag "textk"
- improved functions loadfile and loadlockfile to reduce CPU time
- removed default frequenzy settings for audio rendering in main configuration due to issue with OGA files
- corrections in german language files
- implementation of getescapetext for all files of the graphical user interface
- bug fix: function createmedia did not execute rdbms_setmedia if $mgmt_maxfilesize limit has been reached for a file 
- bug fix: installation procedure checked temp and view directory before they were created
- update of videoplayer Video JS to version 4.12.7
- fullscreen mode in video player has been disabled for side bar
- bug fix: Video JS css did not properly support fullscreen when used in iframes, fullscreen is disabled in CMS views
- implementation of extended error logging in function uploadfile
- improvement of input validation in function splitstring
- removed media_update input parameter from function uploadfile. media updates require the object name as input

version 5.7.10
release 08/2015
- implementation of new youtube connector to support Google OAuth
- removed youtube token from management publication configuration file (function editpublication), the token will be saved in the config directory as youtube_token.json for all publications since the refresh_token will only provided once by Google
- implementation of new function editpublicationsetting to edit a single setting of a publication
- function selectcontent, selectxmlcontent, selecticontent and selectxmlicontent use case-insensitve conditional value
- larger youtube upload window
- implementation of meta data content from videos into the youtube upload form
- removed hypercms_eventsystem file from function directory
- new youtube video link in page_info in case the video was uploaded to youtube
- bug fix: function showvideoplayer created wrapperlink for the video poster image, this caused the video to be loaded as the poster
- bug fix: page_multiedit did not fully support keywords (list, file, listonly attributes)
- function showmessage provided id of DIV holding the message (use ID suffix _text)
- improvements in import connector regarding special characters in file names
- function createpublication creates default media mapping definition file
- function deletepublication deletes media mapping file
- implementation of object file count and size (in info-tab of object) for pages based on function getfilesize
- implementation of duplicate check in function rdbms_createobject
- implementation of input paramter for object ID for function rdbms_deleteobject
- implementation of logname as input parameter for function deletelog
- implementation of new custom log viewer plugin to display individual logs
- improvements in plugin management viewer
- changed log viewer details popup from GET to POST in order to display longer messages (strings)
- impementation of custom log manager in admin node of each publication
- new text for 'custom-system-events' in all language files
- bug fix: the event onpublishobject_pre has not been fired if the application tag of the underlying template was empty
- improvements in the eventysystem reg. creating the search index for PDF files
- removed location bar from control_objectlist_menu in mobile edition to avoid scrolling on smaller screens
- changes in CSS of mobile edition
- removed personalization and template management from explorer of mobile edition
- added edit button to objects in objectlist for mobile edition
- bug fix: delete-favorite icon in context menu has not been grayed out if no object was selected
- bug fix: content versions of media files did not point to correct media file if only the meta data has been changed and published
- support for file name changes in content versioning
- function getobjectinfo supports content versions
- implementation of function getmediafile
- implementation of media preview when comparing media content versions

version 5.7.11
release 09/2015
- implementation of new function getcontainerversions and gettemplateversions
- implementation of WebVTT support for videos including WebVVT editor for videos
- renamed text ID for uploade Youtube videos from "youtube_id2 to "Youtube-ID"
- bug fix: milliseconds of a video timestamp was not correct for video start
- implementation of event log entries for sent e-mails of tasks and notifications
- update of VIDEO-JS to version 4.12.11 due to issue with WebVTT on all browsers except Chrome
- bug fix: function rdbms_searchcontent did not provid correct search for date limits
- bug fix: limit max file size in function createdocument
- bug fix: function showmedia renames preview pdf files to .thumb.pdf is function createdocument failed to do so
- check of storage limit could lead to extensive delays due to function rdbms_getfilesize, memory file filesize.dat is used to store storage size for 24 hours
- function getvideoinfo returns duration including milliseconds as well
- bug fix: container_id_duplicate has not been defined in function rdbms_createobject
- replaced AUDIO JS with VIDEO JS player
- bug fix: content compare was not working for multimedia versions due to including .xml as version file extension
- function showaudioplayer support width, height and poster as input parameters
- new CSS class hcmsButtonMenuActive used for buttons in top bar (see function showtopmenubar)
- implementation of additional options button below media player in editing mode
- removed embed button below media player in editing mode
- improvements in options menu of media editing mode
- support saving media file as original file with support of file versions
- bug fix: version_content displayed .folder instead of folder name
- function getobjectinfo provides icon in result array
- bug fix: template engine used template media URL from mgmt_config instead of publ_config
- defined support of conversion to MPEG formats in config.inc.php
- bug fix: template_edit used double quote inside the double quote of mouse event of help button
- bug fix: set frameBorder=0 for all iframes to support borderless iframes in IE 8
- bug fix: sidebar of keyword plugin did not open and close
- changed character set of folders from UTF-8 (hardcoded like for multimedia assets) to the given characters set of the publication or template
- bug fix: doctype generated by template engine included a double quote
- reworked graphics for flat design theme
- bug fix: mouseover on OK buttons in version_template did not work due to same name
- check of empty search string in general search form in top bar has been implemented
- implementation of function getlanguageoptions to get all languages and their 2-digit codes sorted by the language name
- bug fix: page_multiedit did not set UTF-8 as character set for multimedia objects
- bug fix: implementation of natural case sort for media_edit_explorer
- bug fix: template media preview provided by function showmedia dit not present any information of template media files

version 5.7.12
release 09/2015
- improved graphics for flat design theme
- improved CX-showcase-template for zoom viewer in the installation directory
- improved CX-showcase-template for 360 degree viewer in the installation directory
- improved CX-showcase-template for gallery viewer in the installation directory
- implementation of new home box with the favorites of a user
- changed function createinstance to support username of user account and create the user as superuser
- bug fix: function createinstance did not check for special characters in the instance name
- bug fix: function createinstance tested the CMS config directory for wriote permissions and not the instance directory
- bug fix: function copyrecursive did not verify file handler
- changed setting of strongpassword for new instances to false
- bug fix: function registerinstance refered to config and not to the instance directory
- bug fix: include of session had to be relocated to API loader in order to load the instance configuration file
- bug fix: instances setting of main configuration file has not been set in instance configuration file by function createinstance
- improvements in keyword plugin
- bug fix: function _loadSiteConfig did not check if config file exists which can cause a fatal error if a publication has been deleted
- bug fix: JS function setVTTtime used wrong player id
- bug fix: function showmedia did not provide preview of PSD files
- bug fix: function createmedia used the crop option before the source PSD file which led to a wrong result
- bug fix: function notifyusers did not load the language of a recipient
- bug fix: frameset_main_linking used deprecated logo file
- improvements in user_sendlink
- implementation of language loader for function createtask, notifyuser and licensenotification

version 5.7.13
release 10/2015
- improvements in workflow_manager
- improvements in user_sendlink
- improvements in user_objectlist
- removed double entries in language files
- replaced JS based height calculation of div layers by CSS based layer positioning for all objectlist views
- update of PDF viewer (PDF.JS) to version 1.1.215
- update of TCPDF to version 6.2.11
- set default link for function medialinks_to_complinks
- function medialinks_to_complinks returns only first valid link ressource and not a link array
- set default link for function complinks_to_medialinks
- index page of the system is using configured domain for redirect in order to avoid session issues with multiple domains used to access the system
- bug fix: control_content_menu used 2nd parameter in location.replace, only one parameter is supported
- failed FFMPEG commands are reported in event log
- improvements in function createmedia to create player config file and create media database entry in case the conversion of a media file failed after upload
- moved from head tags to text-IDs for meta data in attitude templates

version 5.7.14
release 10/2015
- improvements on mobile home screen
- bug fix: user_sendlink did not validate array for email recipients
- improvements in template engine to avoid line break of edit icons in "cmsview" and "inlineview"
- improvements in template engine regarding language session handling
- bug fix: txt file extension has been defined as clear text format and image format
- bug fix: function showmedia did not properly convert non UTF8 strings
- updated PHPWord library to version 0.12.0
- removed unused library charsetconversion
- bug fix: function downloadobject did not get page via HTTP view and failed to render it
- implementation of search expression logging in function rdbms_searchcontent
- implementation of search expression statistics plugin
- rework of icons in all themes
- bug fix: keyword plugin always selected english language version
- bug fix: keyword plugin only stores assets or pages keywords in stats file and did not join them
- updates in keyword analysis plugin to support new language file format
- bug fix: pagecontenttype select in template engine has not been added to the form item string
- updates in simple stats plugin to support new language file format
- updates in test plugin to support new language file format

version 5.8.0
release 11/2015
- implementation of search expressions recommender based on the search history of all users
- bug fix: deleting folders caused workplace control to display wrong location (folder has been added to location for each step of popup_status)
- bug fix: deleting folders using the context menu could cause deleting other folders since the values of the context menu has not been locked for writing
- implementation of new JS functions hcms_lockContext and hcms_isLockedContext
- improvements in JS library for context menu
- improvements in frameset_main_linking to support search expression recommender, removed dynamical framesets, implementation of sidebar configuration check
- implementation of max keyword length of 255 digits to avoid long strings that have beem imported as keywords (e.g. Adobe Indesign documents with unreadable keyword strings)
- bug fix: location has been undefined in popup_status if no folder has been provided as input request
- changed max search hist from 1000 to 500 in top bar search forms
- bug fix: user_sendlink did not define upper case letter in password to fullfill strong password criteria
- bug fix: user_sendlink did not set all general_error's as array element
- implementation of a new standard design theme
- bug fix: undefined variable $type and $thumb_pdf_exists in function showmedia in hyperCMS UI
- new set of user manuals
- implementation of new main JS function hcms_getURLparameter
- implementation of "Remember me" feature for logon, using the local storage of the browser
- implementation of new text in all language files for "Remember me" feature
- implementation of workflow as a module that is part of the Standard and Enterprise Edition
- implementation of absolute path check for references to manuals
- implementation of absolute URL in hypercms_main API as referrer to empty.php

version 5.8.1
release 11/2015
- bug fix: function checkworkflow did not exclude .folder of the folder path for comparison
- update of CKEditor to version 4.5.4 due to issue with source code view in MS Edge browser
- implementation of the Youtube plugin for CKEditor in all toolbar configurations, except DAM and PDF
- optimizations in rich text editor UI
- implementation of Spellchecker and Scayt plugin for CKEditor in all toolbar configurations, except DAM
- implementation of share link generator
- implementation of social media share link function in connect API: createsharelink_facebook, createsharelink_twitter, createsharelink_googleplus, createsharelink_linkedin, createsharelink_pinterest
- implementation of social media sharing for media files in hyperCMS UI
- implementation of new publication setting for social media sharing
- implementation of JS functions for social share links: hcms_sharelinkFacebook, hcms_sharelinkTwitter, hcms_sharelinkGooglePlus, hcms_sharelinkLinkedin, hcms_sharelinkPinterest
- implementation of JS function hcms_getcontentByName to get value of form field by its name
- implementation of share links for media files in template engine
- new organisation of directory structure for connector module and changes in youtube connector
- add new text to language files for social media sharing
- optimizations in HTML5 file upload
- change to HTTPS for IP Geo location finder in Google maps

version 5.8.2
release 11/2015
- implementation of AES 256 encrpytion based on OpenSSL as standard strong encryption with fallback to Mcrypt (CBC), Mcrypt uses base64 incoding to be binary-safe, this leads to larger encrypted files and is therefore deprecated since version 5.8.2
- changed encryption of container data to strong as default (same as file encryption)
- implementation of config setting for the key for AES 256 encrpytion: $mgmt_config['aes256_key']
- removed base64 encoding from function encryptfile, decryptfile, creattempfile and movetempfile in order to reduce the size of encrypted files
- implementation of binary mode for writing files using function savefile and savelockfile due to encryption without base64 encoding
- removed default base64 encoding from standard encryption in function hcms_encrypt
- improvements in template engine for autosave
- bug fix: reset of medaview variable in function showmedia has been removed
- bug fix: medianame has not been converted to UTF-8 for media viewer in template engine 
- function hcms_encrypt and hcms_decrpyt will base64 en/decode the string if 'url' encoding is requested in order to be binary safe
- bug fix: the character set of the form has not been set to UTF-8 in the template engine in case of editing media files
- implementation of file locking in function iptc_writefile
- implementation of file stats (rdbms_setmedia) in function iptc_writefile, xmp_writefile and id3_writefile to update MD5 hash and filesize in DB
- bug fix: removed trim of encrypted data from function savecontainer, this is a manipulation of the data string and could lead to decryption issues when handling binary data
- implementation of additional MD5 hash comparison of encrypted file and temporary unencrypted file in function createtempfile
- bug fix: function rdbms_setmedia did not update MD5 hash since wrong variable name has been used for value check
- various improvements in function iptc_writefile, xmp_writefile and id3_writefile
- bug fix: function xmp_writefile did also write data to file if an error occured
- bug fix: previous create of temporary unencrypted file has been checked for moving file back into encrpyted version, this caused the file not being encrypted and moved again by function iptc_writefile, xmp_writefile and id3_writefile
- bug fix: function iptc_writefile did a reset of the input array $iptc
- bug fix: undefined variables and undefined hidden field for 'filetype' in popup_message
- bug fix: undefined variable 'mediafile' in template engine
- implementation of movetempfile input paramter in function iptc_writefile, id3_writefile and xmp_writefile due to file collision when using encryption and moving temporary unencrypted file back to encrypted file 
- implementation of media file statisticts update and encryption of file into service savecontent
- bug fix: webdav function _runFuncWithGlobals requires 0 and 1 instead of false and true in order to pass those values to the API function
- changes in language files
- implementation of function avoidfilecollision due to issues when manipulating encrypted files with e.g. function createmedia and the shell execute file process has not been finished
- removed file encryption feature from free to standard and enterprise edition
*/

// current version
$version = "Version 5.8.2";
?>