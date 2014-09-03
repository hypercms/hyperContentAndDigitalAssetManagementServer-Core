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
- cut/copy/paste folders and all thier items
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
- new Icons and overworked template engine with support of editing multiple
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
- bug fix: view/download buttons in search_objetclist had wrong style-class
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
- bug fix: search_script_rdbms.php used wrong input parameter for function rdbms_searchcontent
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

version 5.6.10 PREVIEW
planed release 09/2014
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

preview:
- definition of target formats when using send mail-link
- add meta data to files based on metadata template on upload on checkbox to change metadata after upload
- save color code (hex) and percentage for images in table media to present 5 main colors for the image
- WebDAV ressource seperation (original files, reduced files/images) vs. MS Plugins for Outlook, Word, Powerpoint
- structured lists (one list element leads to a second list with sub-elements) 
- video comments/keywords for video frames
- keyword fields 'textk' to edit keywords with recommender function ala youtube. Define keyword lists under meta data templates that can or must be used.
- watermark for images and videos
- video transcoding (e.g. video with different audio layers)
- users online und live chat
- speech2text for video text indexing
- Windows Federated Search for WebDAV / Windows Explorer (complicated)
*/

// current version
$version = "Version 5.6.9";
?>