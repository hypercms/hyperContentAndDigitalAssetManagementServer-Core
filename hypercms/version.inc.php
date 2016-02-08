<?php
/*
new features of major releases:

Version 1.0.x
Release 05/2002 - 10/2002
- XML based repository
- User and role management
- Basic link management
- Media database

Version 2.0.x
Release 12/2002 - 03/2003
- Personalization
- Task management
- New GUI, includes toolbar
- Link management
- Cut/copy/paste objects
- Search functionality
- Site management

Version 3.0.x
Release 03/2003 - 06/2003
- Better personalization integrated in GUI
- Complete new template engine with new tag-set and tag wizard
- New XML schemas
- hyperCMS API
- Better application integration due to new template engine (hidden elements)
- Better link management
- Un/publish and delete all items in a folder
- Workflow management
- New adminisrtation GUI of sites, users and groups
- Cluster operation
- New repository structure
- Check in/out of objects
- WebDAV support
- DB Connectivity
- Better search engine
- New features in wysiwyg editor
- Staging due to publication target definitions

Version 4.0.x
Release 07/2003 - 10/2003
- New hyperCMS Dashboard in WinXP look
- EasyEdit mode allows browsing through site and editing content
- Un/publishing of folders and all their items
- Cut/copy/paste folders and all their items
- Workflow items can be set to users or usergroups
- (4.0.4) tamino can be used as integrated search engine
- (4.0.6) checked out items can be used as private section

Version 4.1.0
Release 11/2003
- Configuration is stored in an array
- hyperCMS Event System for automatization based on events
- Changes in hyperCMS API
- Support for JSP, ASP, HTML/XML/Plain Text Files

Version 4.2.0
Release 12/2003
- New icons and overworked template engine with support of editing multiple components directly in EasyEdit, article buttons were changed to clock icon
- PRE amd POST events in hyperCMS Event System
- Bug fix for double entries for usergroups when creating the same group several times

Version 4.3.x
Release 01/2004 - 02/2004
- New inheritance settings (for components and also templates)
- Improvements in template engine
- Improvements and changes in workflow management
- New navigation tree supports incremental generation of subtrees (faster)
- Wallpapers for navigation tree are no more available
- Multiple components are not restricted by string length of an URL (GET-method)
- Memory function for page and component linking (last visited location in page and component folder structure will be remembered)
- Improved publication management (user will see publication without relogin)

Version 4.4.x
Release 03/2004 - 01/2007
- New features in publishing of objects. Connected objects in the same publication will be published automatically, if one object will be published or unpublished. Also pages without dynamic component linking will be automatically republished after a used component will be published. 
- Extended support for editing XML-objects with EasyEdit
- Switch from WYSIWYG to form view for editing content
- Seperated APIs for filesystem (virtual server) and Tamino (server)
- Cut, copy and paste of objects between several publications (server version only)
- New feature "Journey through Time" that allows a visit of the website in the past
- Changes in the Database Connectivity when writing data to external data sources 
- Changes in the Tamino Database Connectivity due to a Tamino bug concerning writing
  XML documents in Tamino (Tamino can not determine the character set!)
- Changes and improvments in the database connectivity and event system
- Advanced support for other browser 
- Image gallery view

Version 4.5.x
Release 04/2007 - 08/2007
- Automatically uncompress uploaded files

Version 5.0.0
Release 09/2007
- DAM functionality (former medie database does not exist anymore, new architecture based on multimedia components)
- Upload and index content from documents
- Upload and thumbnail generation for images, PDF-docs
- Preview of uploaded videos using streaming technology (FLV-Format)
- New meta data templates for folders and multmedia objects
- Multimedia components/objects are stored in the component structure and replaces the old media database 
- Integration of RDBMS for search functionality (MySQL)
- Detailed search on the basis of template text-fields

Version 5.1.0
Release 03/2008
- Multiple selection of items in object lists holding control key or shift key and mouse click
- Permisson settings for all desktop entries
- Permission settings for mail-link to components and pages

Version 5.2.x
Release 04/2008 - 09/2008
- history of all recipients of sent objects
- Report of recipients in object info
- Context menu for user administration
- Send link to multiple users and to group members

Version 5.3.x
Release 10/2008 - 08/2010
- Upload of multiple files at once and upload progress bar (Flash plugin required)
- New SWFUpload for Flash Player 10 support
- Remoteclient support
- Multiple storage device support (configuration in config.inc.php)
- zip-compression of files on server side
- Download of multiple files using temporary zip-compressed file
- zip-extraction of zip-files contents on server side
- Sending multiple files links via e-mail
- Sending multiple files as attachment via e-mail
- New content repository structure (new sublevel for 10.000 blocks)
- Fixed problem: Multimedia components from other publications had the wrong publication
- New media player
- Media/video rendering based on user settings saved in a media player config file
- Media/image rendering in size during upload of new image files and manipulation of size and format of existing image files
- WebDAV support for multimeda files
- Integration of ODBC besides mySQL in DB Connectivity

Version 5.4.x
Release 09/2010 - 06/2012
- Publishing queue for time management
- Import and export of content via import/export directory
- Bug fixes in checkin
- Autosave function for content
- Expand and collapse of content controls
- Individual video resolution for FLV video rendering
- Support for content indexing of word 2007 (docx), powerpoint (ppt, pptx)
- Db_connect function rdbms_setconent will encode texte to UTF-8, table textnodes will provide a search index in UTF-8
- Search from for RDBMS is therefore also UTF-8 encoded
- New crop images feature 
- Minor bug fixes
- Improved send link form (including user search and tabs)
- New CKeditor as rich text editor integrated to suppoprt Google Chrone and Safari
- Redesign of GUI incl. shadow effects
- hashcode for users for webdav support
- Access to (nested) folders can be excluded via folder access defined in groups
- Improved export/import incl. link transformation in text content nodes
- Added new function in db_connect 
- Added new functions in hypercms_api

Version 5.5.x
Release 07/2012 - 02/2013
- Getconnectedobjects function works with contentobjects of the given container and without link management database (faster)
- Full WebDAV support for multimedia components
- Support of special characters in object and folder names
- New html5 video player with flash fallback
- Integration of videos in rich text editor
- Prepare for PHP 5.3 regarding deprecated register_globals and eregi_replace support 
- Improved and fixed contextmenu
- Paging in objectlist explorer
- Load publication config in hyperCMS and not via main config
- Meta data keyword and description generator in API
- Secure token used to save content
- Modernized JS code for form and elements access
- Document viewer based on google service
- Language settings support, new hypercms-tag for language
- Link management database will not be managed if link management is disabled
- Improved security by killing the users session if a permission is missing
- Text diff of content versions
- Explorer object list with new views (large, medium, small thumbnails and details)
- New download-events in event system
- Daily statistics of file access (new table dailystat)
- Secure password option to prevent users from using simple passwords
- Logging and locking of client IP on logon (10 failed attempts lead to banning of the IP)
- Video converter extended to support the follwing formats: Flv, mp4, ogg, webm
- Different video player configs for each formats in new popup
- Indexing of Excel (xlsx) files and Open Office Text files
- File based search support removed, search is only supported if a database is in use
- Meta data of images will be indexed automatically and not by call in event systems. the mapping file can be found in /data/config
- Inline editing in easyedit mode (need to be activated in config.inc.php)
- Document converter based on UNOCONV
- Image rotation for original images
- Media file attributes (file size, with, height, image colors, image color key, image 6type) are saved in media table (requires database)
- Images search (image size, image main color, image type)
- New image brigthness and contrast setting and preview before saving the image
- Bugfix in API function "manipulateobject" when link management is turned off and copy & paset is used
- Search based on content in file explorers (insert link and media)
- New functions for reading request parameters
- Accumulated access statistics and file size statistics for folders
- Case-insensitive xml parser functions in API (not used)
- Meta data mapping can be freely defined using mapping in tree navigation 'meta data templates'
- Editing permission for content based on group names (new 'groups' attribute in hyperCMS tags)
- Advanced search for object-id and container-id
- Bug fix: Media and image rendering buttons can only be seen if localpermission is set
- Task management included in send mail-link as option
- New hyperCMS date tag to support date picker
- CSRF protection: Maximum request per minute and per session
- Video cut based on video time stamps
- Improved security by removing the container name in GET requests

Version 5.5.9
Release 03/2013
- Several bug fixes in the template engine (component preview was not working in some cases, links were not transformed after 'showinlineeditor').
- js-code for video in inline editing mode was escaped like the rest of script-code after saving.
- Bug fix in object list when ctrl-key selection was used on objects where one name was the substring of the other name.
- Several changes in language files.
- Bug fix in media explorer when going back to root element.
- Bug fix in character encoding when html code was allowed in unformatted text areas.
- Check of 'localpermission' on the level of content control and not only on actions level if DAM is enabled.
- Permission problem with view of inherited objects.
- Bug fix for copy & paste of multimedia objects (media attributes were not copied/created in the database).

Version 5.5.10
Release 03/2013
- Bug fix in user form, two message layers were shown.
- Changes in language files.
- Bug fix in editorf. content-type was JS-escaped which caused charset problem in IE (IE used default charset).
- Bug fix in template engine. html & body tag was included in preview of components which were included in pages.
- Bug fix in file upload. error message for file names exceeding the max. digits value was not shown.
- Removing js-code and form code from formlock-view in template engine.
- New security token in user, group, workflow, personalization, and template management.
- Removing and escaping of html/JS code in meta data template preview.

Version 5.5.11
Release 03/2013
- Bug fix in 'convertlink'. wrong array key for the publication configuration.
- Remove direct link from object info if DAM only usage is activated.
- Remove task if object does not exist anymore.
- Bug fix in 'hcms_crypt'. the first 8 digits were used instead of the last 8 of a given string.
- Removed template upload functionality.
- New security token concept with a general token for all actions instead of individual tokens.
- New hypercms-tag providing comment functionality for pages and components.
- Upload files directly in the component explorer.
- Search engine provides default search in meta data and object names (search restriction 'only object names' can still be used).
- Live view of files including javascript code is disabled.
- Bug fix in object list when ctrl-key selection was used on objects. when selecting, deselecting and selecting the same object an empty entry was left in 'multipbject'.

Version 5.5.12
Release 04/2013
- Super admin can be set in main user administration
- Loadlockfile supports unlocking of files locked by other users after a given number of seconds
- Bug fix in 'workflowaccept' and 'workflowreject'. the query did not include the combination of publication and group membership in the memberof-node.
- Bug fix in user objectlist. the query did not include the combination of publication and group membership in the memberof-node.
- Bug fix in userlogin. the session was not destroyed if user was not logged in.
- Bug fix in explorer_wrapper. only script-tags were checked for preview in DAM, which still leads to security issues if JS is used in an uploaded file.
- Default link-type in user_sendlink is the download link.
- User have to submit their old password to change the password. User administrators with permissions to edit users can change the password of other users without providing the old password.
- Publishing and workflow can be applied on multimedia components
- Bug fix in editgroup (some desktop permissions arrary keys were not correct)

Version 5.5.13
Release 05/2013
- Seperation of API (hypercms_api.inc.php) and UI elements (hypercms_UI.inc.php)
- Bug fix in rdbms_searchcontent (db_connect). The SQL syntax used an inner join to the search in textnodes and object names. the search in textnodes and object names uses OR operator.
- Send mail-links including lifetime / period of validity: Time token can be used in access-, download- And wrapper-links.
- Bug fix: Container in content version and buildview was missing
- Improvements in RDBMS connectivity. new attribute filetype in table media to avoid the use of lame mySQL string function.
- New function getvideoinfo in hyperCMS API

Version 5.5.14
Release 05/2013
- New search in top frame for search over all objects of all publications
- New loading bar image
- Remove file size in info-tab of pages and page folders
- Send mail link to object directly from control_content_menu
- New video player code based on iframe and several new
- Calculation of file size and number in rdbms_getfilesize, new function getfilesize in hyperCMS API
- Limited packing of source files in a zip files based on the source file size (see config.inc.php)
- Bug fix: Search for user files didn't work due to missing parameter and missing check of action parameter
- Bug fix: Icon of checked out items are not grey

Version 5.6.0
Release 07/2013
- Themes for hyperCMS GUI
- Video screen capture (thumbnail jpeg)
- Document preview based on PDF reader plugin of browsers (google docs service for older browser still in use)
- Several changes in CSS classes
- Mobile client for content editing
- Bug fix: Infotype=meta was not set if used in hyperCMS Tag Wizard
- Bug fix: Body end tag to component views was not added in template engine
- Error message for zip-file downloads larger than max. zip-size
- IPTC meta data injector for image files
- Alert message when functions in the objectlist control are blocked by the search window
- New HTML5 multi file upload with drag & drop support, SWFUpload as fallback
- Component strcuture was renamed to Asset structure
- Filters for file-types in object list
- Bug-fix: Updating files (change of the file extension) with special characters in their file name resulted into wrong names
- Media viewer based on jquery plugin (images carousel and zoom)
- If download link exceeds limit an access to the system will be provided using a default user
- Bug fix: Status popup window didn't close automatically
- Bug fix: Problems with tabs and z-index in IE
- Bug fix: Encoding problem in text area of video embed code in IE

Version 5.6.1
Release 07/2013
- Download of predefined image sizes and formats (defined in config.inc.php)
- User interface for license notification configuration
- Shortened access/download/wrapper-links and URL-forward in index.php
- No folders in list view in mobile edition
- Support of mail-links to open mobile edition

Version 5.6.2
Release 07/2013
- Bug fix: File upload in component explorer was using the old file name for the uploader
- Show meta information (EXIF, IPTC, XMP) in info-tab
- Size of media files in 72 dpi and 300 dpi resolution is shown by showmedia
- New unique and secure hash codes for link access. encryption is not used anymore to create access/download/wrapper links.
- Bug-fix: Imagemagick removed metadata from files, metadata will be restored by EXIFTOOL
- Write XMP data to files using EXIFTOOL
- Web2print
- Bug fix: Clipboard was set to false on cut/copyobject, only one object was copied if more where selected
- New parameter in buildview to execute the script code in templates
- Public download setting enables/disables download and wrapper links in info-tab, if disabled only mail-links (access/dwonload) can be used
- Bug fix: view/download buttons in search_objectlist had wrong style-class
- Bug fix: general search when access via mail-link was not working due to error in framest_objectlist
- Unpublished objects are greyed out, locked objects use the locked icon
- Create all videos for video player at once
- New media and image rendering layout with overlapping menu
- Plugin system
- Bug fix: Function zipfiles used echo and caused bad download
- Function settext saves multimedia files saved by editor in the link index
- Link editing: Preview of the selected page
- Licensenotfication supports different date formats (same as date picker textd)
- Sender of mail-links will be stored in table recipients

Version 5.6.3
Release 08/2013
- Table recipients includes sender information (user name) and date includes time as well
- Bug fix: Copy & paste of images didn't include all video formats and the video config file
- New variable %hcms% for the URL of hyperCMS, used for video embedding in editor
- New function convertimage as wrapper for createmedia. useful to convert images to other color spaces for PDF-rendering
- Sendlink setting in publication enables and disables the sendlink function as well
- Bug fix: video player didn't play video, explorer_wrapper optimsation for the straming of videos
- Converting images for tcpdf to greyscale, CMYK color space
- Flip/flop images vertically and horizontally, add effects (sharpen, greyscale, sepia-tone, sketch, monochrome) to images, change colorspace (RGB, CMYK, Gray) using imagemagick
- Bug fix: Using non UTF-8 charsets, the special characters in the form view are not properly escaped for links-texts and media-alttexts. convertchars is used in link_edit_page and medie_edit_page
- Allowed IP addresses for media access can be defined in publication settings
- Theme can be defined in publication settings (global in config.inc.php, publication scope in publication config, user scope in user setting)
- Bug fix: In createmedia, the buffer file was created for thumbnails if the thumbnail file didn't exist.
- Access statistics show total hits and filesize
- Bug fix: Manipulateobject could not delete files when link-DB was deactivated ($allow_delete = true;) 
- Use alt-key to switch to download links in object lists
- New windows have the container id of their object
- New info box for hints regarding the system usage, see function showinfobox
- Generate google sitemap with function getgooglesitemap
- OpenSEARCH implementation
- Bug fix for audio-files
- Bug fix: zipfiles didn't check access permissions of user
- Bug fix: Download of files using alt-key didn't ceck access permissions of use

Version 5.6.4
Release 11/2013
- Geo location in access statistics
- New onaccess event to analyze download, wrapper and access link requests
- Gallery view component template
- 360 image view component template
- Publish videos to youtube
- OpenAPI (Webservice for executing functions, like: Create/edit/delete of users/folders/objects and file upload/download)
- Notification alerts to logged in user based on folder settings and events

Version 5.6.5
Release 02/2014
- Edit several selected objects
- DropBox integration, see: https://www.dropbox.com/developers/dropins
- Publish videos, images, text to facebook (using Facebook SDK)
- Object and folder names are trimed on create and rename
- Check for duplicate entries on file upload using MD5 hash of files
- Geo location of files (images) and search based on map with dragable selection
- Create individual videos (new size wxh file extension instead of thumb)
- Import/export GUI
- Bug fix: rdbms_searchcontent ignored text_id in advanced search
- Bug fix: rdbms_getmedia used wrong refered for SQL-query
- Bug fix: Editing of multiple objects didn't include checking constraints of unformatted fields
- Bug fix: Popups of template_edit submitted forms instead of only calling a js-function(input type of button was "submit") which lead to logout
- Bug fix: Create hidden folders was not performed when super admin logged in
- Bug fix: Check of hidden folders looked in the whole file system path and not only in the root given by the publication (this led to hidden folders that should be visible)
- Bug fix: rdbms_createobject fired before successful creation of a new object file (this led to duplicates of same object in DB)

Version 5.6.6
Release 03/2014
- Versioning of media files
- Bug fix: Injectmetadata (CDATA was missing)
- CKEditor version 4.3.3 implemented (support of DIV and IFRAME, support for IE 11)
- Versioning of media files when using workplace integration (WebDAV) to edit and save files (content versioning need to be enabled)
- Relocation of config.inc.php to config directory
- Install procedure for easy installation

Version 5.6.7
Release 05/2014
- Code improvement (undeclared variables, PHP global in function input)
- Bug fix: rdbms_getdailystat did compare datetime with time (convert datime to time)
- Bug fix: Duplicate names were not converted to readbale names
- Bug fix: Single file upload produces error message about the same file existing already
- Change in createthumbnail_indesign to create a valid thumbnail (use last thumbnail of indesign file)
- Bug fix: Set unset variables (error notices for unset variables in php log)
- Bug fix: Media category could not be deleted
- Bug fix: The encoding in containers was not set to UTF-8 on media files and folders
- Bug fix: Advanced search didn't get the ownergroup and could therefore not check the content access permissions (attribute groups for hyperCMS tags)
- Bug fix: Search for image size range did include the given width as additional SQL where clause and led to wrong search results
- Bug fix: name of variable was not correct in WebDAV function deleteGlobal

Version 5.6.8
Release 07/2014
- Bug fix: The content of date fields was always converted to the international date format, this led to a wrong format after loading the container
- Bug fix: %comp% was not converted to the component URL in the template engine for single component when onEdit attribute was set to "hidden"
- Bug fix: %comp% was not converted to the right publication or management component URL depending on the view in the template engine for components when onEdit attribute was set to "hidden"
- Bug fix: Selector layer for download on mobile devices was not properly positioned
- Bug fix: Download of files on iphone is not supported, mime-type was changed to the actual mime-type of the file to show it in the browser
- Bug fix: getthemelocation used wrong session variable name, mobile CSS were not used
- Bug fix: Component_curr lead to too long request-URI when editing multiple components. the component_curr request variable was redundant and has been removed to solve the problem.
- Improvements in mobile CSS
- Bug fix: Empty inheritance database blocked createpublication
- Improvements of creating a unique token if a session_id is not available (session_id used for un/locking files)
- Implementation of installation process including search engine 
- Bug fix: Limit was not set in rdbms_searchcontent
- Bug fix: Template engine issue with language session variable
- Improvements in compare content versions, multimedia content from files will be compared as well as meta data (taken from content container)
- Seperation of template includes (new expression) from page and component templates in the GUI
- Bug fix: Search_objectlist.php used wrong input parameter for function rdbms_searchcontent
- Bug fix: object name was missing in event log when removing objects 
- Bug fix: The template of folders (should be meta) was set to the wrong category
- Bug fix: Edittemplate did not trim the file extension which could lead to white spaces after the file name end that can hardly be recognized
- Edit and save text based content from template media files
- Bug fix: Edittemplate transformed &amp; not correctly to &
- Bug fix: Control frame has not been reloaded in easy edit mode 
- Bug fix: history buttons for next/previous navigation has been disabled for object and not for files
- Bug fix: Template_editor showed commentu and commentf for component and page templates but should only be used for metadata templates
- Bug fix: Downloadfile did not exit if file ressouce is missing
- Bug fix: User_edit did not check siteaccess permissions of the user
- Bug fix: Sort of sites in user_edit did not use natural case sort
- Bug fix: Settext did not check the link index properbly which led to wrong entries in error logs
- Bug fix: Undefined varibale hypercms_livelink_set in template engine
- Bug fix: Delete of media reference in media_edit_page did not work for IE
- Implementation of download/upload statistics of all DAM publications on home screen

Version 5.6.9
Release 08/2014
- New template variables for the external repository in template engine
- Tranformation of special characters in deconvertpath
- New checksum in user session file to verify permissions set in session
- Bug fix: getfileinfo did not set filename_only and published state for files without file extensions
- Bug fix: Wrong variable names led to undefined variables in createpublication
- Bug fix: Several undefined variables 
- Font of unformatted text remains unchanged when using inline editing (CSS optimization in showinlineeditor_head)
- Deletepublication removes files in external repository, page and component root folders
- New cleaning level management config setting to check and clean template code
- Bug fix: In thumbnail generation using the GD library since aspect ratio has not been preserved, different behaviour compared to resize parameter in ImageMagick
- Automatic resize of textarea in inline editing mode 
- Bug fix: Setmedia replaced publication media path first, which led to wrong media reference since the ediot passes the full path incl. the domain
- Bug fix: Installation prozedure added two / instead of one if a subdirectory is used for the installation root
- Bug fix: Toolbar for editor in DAM mode could not be set
- Improvement in function scriptcode_extract (removes comments in scriptcode)
- Install procedure migration to mysqli
- Removed trim from all load- And savefile functions since it led to bad behaviour when line break need to be preserved at file end
- Bug fix: Checkout objects did not support sidebar
- Bug fix: Error in function getconnectedobject which caused the creation of working containers for checked out objects (loadcontainer required user as global input)
- Changes in job for licensenotification, monthly: next month will be checked each 1st of the month, weekly: next week will be checked each sunday
- Support for DPI, colorspace, ICC profiles in formatted texts (textf-tag) and images (mediafile-tag)
- Bug fix: Error in call to js-function hcms_validateform in function showinlineeditor_head
- Bug fix: remove reseting of image size if scaling is used in CKEditor image-plugin
- Bug fix: Inline editing mode issue when onedit and onpublish attributes are used for the same hypercms tag

Version 5.6.10
Release 10/2014
- Improvements in plugin management
- New individual button in UI function showtopbar
- Bug fix: Function downloadfile called dailystat for each partial file download (only first partial download triggers dailystat)
- New Simple Statistics plugin (not part of standard software package)
- Bug fix: Exception for CSS and JS files in follow link (page_view)
- New Keyword Statistics plugin (not part of standard software package)
- Changed order of meta data extraction of files due to issue with Adobe XMP special characters, new order: EXIF, XMP, IPTC
- Bug fix: Function showtopbar did not show individual button
- Bug fix: Function downloadfile did not provide proper dailystat, if requested start bytes were not zero
- Bug fix: Page_multiedit didn't show label names defined in template and applied constraints on unformatted text fields even if they were disabled
- Implementation of multiple hyperCMS instances using different databases, internal and external repositories
- Implementation of instance manager GUI to manage multiple hyperCMS instances (as part of connector)
- Implementation of DB connect in multiedit
- Improvement in notification of user, if user is owner of an object no notification will be sent
- Bug fix: Missing check of globalpermission in explorer_objectlist
- Bug fix: Added removing of IPTC meta data using EXIFTOOL in function iptc_writefile before writing new IPTC data to file
- Bug fix: Function notifyusers used file owner and not current user to compare with the notified user
- Bug fix: new version of FFMPEG requires new option names (e.g. -b:v for the video bit rate instead of -b), options for FFMPEG have been changed in config.inc.php, media_rendering.php and function createmedia
- Bug fix: new version of FFMPEG does not support an audio sample rate of 44100 Hz for FLV files, changed default to 22050 Hz, options for FFMPEG have been changed in config.inc.php, media_rendering.php and function createmedia
- Bug fix: Deleteobjects did not remove individual video files with sub-file-extension .media
- Implementation of new function getoption for extracting values of a string holding options (used for image/audio/video options)
- Creating a thumbnail image on initial upload of a video file
- Bug fix: Function loadcontainer did not return the container information for versions of a content container
- Improvements in task management for broken links
- New theme namend colorful
- Bug fix: Download of other file formats than original did not work
- Bug fix: Undefined variables in several scripts
- Bug fix: Click on object shows wrong object in sidebar if sort has been applied
- Bug fix: Clicking on an object in gallery-view highlights wrong object if sort has been applied
- Improvements in all object list views
- Improvements in preview of object / sidebar
- Bug fix: Image brightness was set to -100 for image editing due to a wrong variable name
- Improvements in object lists regarding alignments of list/gallery items
- Creating new components when adding components to a page by the component explorer
- Securing all shell input parameters
- Support for video files with no configuration file in repository
- Bug fix: video cut was not able to process hours, minutes and seconds of one digit
- Several improvements in video editing including an infobox
- New home screen and home navigation item in navigator
- New boxes on home screen for recent tasks and recent objects of logged in user
- Improvements in mobile style sheets
- Bug fix: Function getfiletype searched for a substring in file extension definitions without a delimiter, this lead to wrong file-type in media table
- Bug fix: new function shellcmd_encode to solve tilde issue with function escapeshellcmd in various files
- Reorganization of functions in the hyperCMS API
- Implementation of new check permissions functions
- Bug fix: DB connect RDBMS did not provide hash keys for all search operations
- Bug fix: Permission issues in explorer for publishing queue, log-list and plugins
- Bug fix: Implementation of updated function getbrowserinfo (old function was outdated and did not detect the browsers correctly)
- Implementation of sort order for workflow folder form
- Bug fix: removed double sort from search result
- Bug fix: Search for user files in user management did not use proper frameset and resulted in an error on click on an object
- Bug fix: removed : From meta data listing if label is empty
- Bug fix: Checkadminpermission expected input parameter
- Set variable lang to "en" if no value is given
- Bug fix: Link_explorer of editor passed wrong input to function rdbms_searchcontent
- Improvements in DB connect RDBMS
- Bug fix: Scrolling in link and media explorer did not work due to changes in the CSS class
- Implementation of new function checkpublicationpermission
- Bug fix: Convert of formats did not work due to missing convert-type and convert-config inputs in context menu for checkout, queue and search object list
- Bug fix: Set min-height of fields to avoid collapsing of empty fields in version comparison
- Bug fix: The user edit permission for a specific publication was not checked properly and led to killsession

Version 5.7.0
Release 11/2014
- Implementation of user chat with object-link exchange
- New configuration setting for chat
- Integration of top in frameset_main and replacement of frames by iframes
- Implementation of logout on window close (not supported by all browsers)
- Bug fix: Charset declaration was missing in explorer
- Removed timeout.js from all controls and js-library
- Integration of chat in mobile edition
- Optimizations in main and contextmenu JS library
- Implementation of new settings for background and alpha when converting PDF to image (due to black background issue)
- Replacement of all framesets by iframes
- Bug fix: Error messages on group_access_form, worklfow_folder_form were not shown since object IDs and object paths were mixed up
- Implementation of resize function for group_access_explorer
- Bug fix: Search_explorer used wrong inital_dir for the component root
- Improvements in inline editing, dynamically adjust width and height of textarea after loading the inline element

Version 5.7.1
Release 12/2014
- New function processobjects replaces publishallobjects and can handle the actions 'publish', 'unpublish' and 'delete' to process queue entries
- New feature "delete from server" on upload of new files in order to remove them again on a certain date and time
- No resize of thumbnail if original image is smaller than defined thumbnail size
- Propagate all results from function createmediaobject to function uploadfile
- Function showmedia shows original thumbnail size if the thumbnail is smaller than the defined thumbnail size due to a small original image
- Improvement of information in main config
- Bug fix: When using the GD library instead of ImageMagick, the aspect ration of images have been changed when creating other formats
- Bug fix: Undefined variables in indexes in control_queue_menu and function manipulateobject
- Change personal theme in GUI immediately and not after next logon
- Removed statistics on home screen of mobile edition
- Bug fix: hyperCMS API used deprecated JS function hcms_openBrWindow
- Bug fix: Top-bar spacing issues in mobile edition 
- Replacing framesets by iframes in instance manager and import/export manager
- Define date and time to remove files from system on upload
- Set default language and lang variable in function userlogin
- Bug fix: relocation version information on home screen for better support of small mobile screen resolutions
- Bug fix: JS function hcms_showContextmenu did not disable icons for notfication and chat in context menu if no object has been selected
- Bug fix: Explorer_objectlist did hide notfication and chat completety in context menu if user has no permissions
- Improvements in hyperCMS UI document viewer regarding size of view
- Bug fix: Funtion getusersonline returned WebDAV users, these users are not able to chat and must not be returned#
- Fixed showmessage boxes positioning in GUI
- Rounded corners of showmessage boxes
- Improved design of context menu for all themes
- Removed mobile theme as an option in publication management
- Bug fix: The template engine did not include the publication configuration if the link management has been disabled, therefore component have not been included by function insertcomponent
- Bug fix: Access permission has not been when using the editor to select a media file. this led to issues with the video preview.
- If the provided thumbnail size of the main config is greater than the original video size the original video size will be used for video rendering
- Implementation of loading screen for file unzip
- Bug fix: Function deconvertlink removed host name from page links twice. this led to a miss-converted link since the host name has been cut of again without using the function cleandomain.

Version 5.7.2
Release 01/2015
- Encryption for content containers to secure data an server side
- New publication setting for container and media file encryption
- New function loadfile_header to load file partially in order to determine if fill is encrypted
- Removed Tamino support
- Removed all $_SERVER['PHP_SELF'] in all forms due to XSS
- Bug fix: Working content container could be checked out by several users
- Bug fix: Function loadcontainer restored working container if it was locked by another user
- Bug fix: CSS issue with chat in IE 8 and 9
- Function copymetadata has been removed from explorer_download and explorer_wrapper and has been integrated in function createdocument
- New functions encryptfile and decryptfile
- Watermarking for images and videos based on image and video options in the main config file
- Function createmedia supports gamma, sharpness, brightness, contrast and saturation for video editing 
- Bug fix: Impementation of binary-safe encryption and decryption to en/decrypt binary files
- Bug fix: Popup_action did not hide loading layer if an error occured after unzip
- 2 new input parameters (object_id, objectpath) for function rdbms_getqeueentries
- Implementation of information regarding the auto-remove of files/objects in mail form, info tab of objects and sidebar object viewer
- Function getcontainername supports container ID or container name as input
- Improved implementations of functions hcms_encrypt and hcms_decrypt for standard and strong en/decryption
- Function showmedia provides additional information about owner, last published date and date of deletion
- Implementation of file caching to reduce I/O for action chains that would save data in each step
- Bug fix: Checked out file for user has not been created and deleted in function createuser and deleteuser
- Improvement of page_checkedout that creates checked out user file if it does not exist
- Using database media information as primary source for all media displays
- Bug fix: Sidebar on checkout objects list was not displayed properly
- Freegeoip.net stopped providing it's service, changed to API of ip-api.com
- Linkengine is automatically disabled in the publication management for DAM configurations
- Function showmedia support new type "preview_download", which only enables the download in media previews
- Videos can be downloaded and embedded in video editor as well
- Function getvideoinfo extracts audio information as well
- New column createdate in table container, change of column date in table container to type datetime (requires update script)
- Function rdbms_getmedia provides extended media information
- Function downloadfile supports new type "noheader" without any HTML headers tp provide file download for WebDAV
- Function image_getdata has been renamed to extractmetadata
- New function id3_getdata, id3_create and id3_writefile to support ID3 tags of audio files (e.g. mp files)
- Support for ID3 tags of audio files in mapping
- Support for thumbnails of audio files
- New input parameters for function getgooglesitemap to show or hide the frequency and priority tags
- Optimized database attributes

Version 5.7.3
Release 02/2015
- Add original video files of type MP4, WebM, OGG/OGV to source of HTML5 player
- Bug fix: Encryption level for file must be 'strong' in order to be binary-safe
- Bug fix: video start poster could not be defined if the original file was added as source to the HTML5 video player
- Update of HTML5 video player to video.js version 4.11.4
- Bug fix: Copy & paste not working, function manipulateobject did not set init-input for function savecontainer
- Function getcontentlocation adds missing zeros to container ID to correct the containers directory name
- Additional check if object exists when publishing it
- Improvements of several functions in hypercms_main
- Bug fix: In order to support older versions, the original source need to be added to the video player, not only in case of an existing config.orig file
- Function rdbms_getobject_hash supports also object ID and container ID as input
- Function createdownloadlink and createwrapperlink supports also object ID and container ID as input
- Change to MP4 format as the standard for video thumbnail files in function createmedia
- Link for the video start poster of the embed code will be converted to a wrapper link if publication is a DAM in order to have access to the image file
- Implementation of RAW image support for formats: Arw, cr2, crw, dcr, mrw, nef, orf, uyvy
- Bug fix: remove _original files from media repository created by EXIFTOOL
- Redesign of video player with big play button in center
- Bug fix: ok button of task list was not displayed properly
- Bug fix: Function publishobject did not set init for savecontainer to true
- Implementation of a new language management system
- Implementation of new languages. besides English and German the following languages for the UI are now supported: Albanian, Arabic, Bengali, Bulgarian, Chinese (simplified), Czech, Danish, Dutch, English, Finnish, French, German, Greek, Hebrew, Hindi, Hungarian, Indonesian, Italian, Japanese, Korean, Malay, Norwegian, Polish, Portuguese, Romanian, Russian, Serbian, Slovak, Slovenian, Spanish, Somali, Swedish, Thai, Turkish, Ukrainian, Urdu
- New help logic to set 'en' as default help/manual
- Implementation of function html_encode with multibyte character set support
- Bug fix: User_sendlink mixed up languages in e-mail message
- Implementation of new 'flat' design theme
- Minor changes in other design themes

Version 5.7.4
Release 03/2015
- Implementation of favorites feature (create and manage favorites)
- Function rdbms_getobject_id supports object path and hash as input
- Implementation of new functions createfavorite, getfavorites and deletefavorite
- New permission for favorites management
- Change of checked out button behaviour in control_content
- Implementation of new function getlockedobjects for checked out objects
- Removed file page_checkdout
- Implementation of natural case sort for function getlockedobjects and getfavorites
- New function getescapetext to HTML encode specific texts from the language files (needed when presentation uses other character set than the language file)
- Implementation of escapetext in template engine and UI instead of converting all texts of a language file
- Bug fix: html_encode was double encoding if ASCII was selected as encoding
- Bug fix: Content-type has not been set for various input forms
- Implementation of management of home boxes for each user on home screen
- Implementation of new function setboxex and getboxes
- Implementation of JS function hcms_switchSelector in main.js
- Implementation of new homeboxes for recent downloads and uploads of a user

Version 5.7.5
Release 03/2015
- Bug fix: Bulgarian language file used double quote in string
- Bug fix: html_encode used wrong variable name
- Implementation of media preview for select of multiple objects
- Select, edit/render and save multiple images and videos
- Implementation of new services renderimage and rendervideo replace old media rendering logic 
- Implementation of new service savecontent 
- Bug fix: richcalender language files needed to be converted to UTF-8
- Bug fix: Function manipultaobject did not check if both page states (published and unpublished) exists already in the same location on rename
- Bug fix: Undefined variable in function rdbms_getobject_id
- Bug fix: Text of JS prompt messages in template_edit was not html decoded
- Template editor supports include-, script-, workflow-tags for meta data templates
- Meta data templates for media files will be assigned to the application 'media'
- Implementation of hyperCMS scripts for multimedia objects
- Bug fix: Correct undefined characters in key names and eliminate double expressions in all language files
- New uploadfile service that replaces upload_multi
- Improvements in design themes (CSS)
- Implementation of transcoding for video files to audio files
- Implementation of a new hyperCMS connect API for FTP support
- Implementation of file download from FTP servers
- Implementation of new function is_date
- Implementation of date validation in function rdbms_createqueueentry
- Bug fix: Function getmimetype did not return proper mime-type for object/file versions
- Implementation of object versions support for function getfileinfo
- Bug fix: Diplay proper file icons in versions tab
- Bug fix: Pop_status failed to authorize the action when publishing the root folder of pages
- Update of TCPDF to version 6.2.6
- Implementation of download formats for download/access links and attachments
- Implementation of new function convertmedia (wrapper for createdocument and createmedia)
- Bug fix: Source location input parameter of function createimage was not verified
- Implementation of new functions is_document, is_image, is_rawimage, is_video, is_audio
- Bug fix: Function unlockfile, lockfile, savelockfile, loadlockfile used global variable user which overwrites the input variable
- Bug fix: Simple keywords plugin refered to old search objectlist location
- Implementation of new function createversion
- Support for versioning of thumbnail files
- Bug fix: Saving multiple media files was not working when media files are not of same type
- Implementation of delete for thumbnail file versions in function manipulateobject and version_content
- Implementation of video rotation and video flip for video editing
- Improvements in video editor layout
- Implementation of force_reload paramter for function showvideoplayer to force reloading the video sources
- New configuration parameter for default video and audio previev files (type = origthumb)
- Modifications of function createmedia to support new configuration paramaters
- Implementation of new function deletemediafiles (deletes all derivates of a media ressource)

Version 5.7.6
Release 04/2015
- Implementation of new function checkworkflow in hypercms_main used by function buildworkflow
- Update of pdf viewer to version 1.0.1040
- Bug fix: Improved CSS definition using filter for hcmsInfoBox due to issues on home screen
- Bug fix: Select media in form view did not work due to missing evaluation of selectbox in JS function getSelectedOption
- Bug fix: Media_view did not validate form fields for width and height in control frame
- Bug fix: Function buildview of templateengine did reset tag variables for each tag found in the template
- Implementation of media object evaluation in media_edit_page and link object evaluation in link_edit_page
- Improvements in function setmedia by using function loadfile_fast for object loading
- Bug fix: Wrong content-type specification in version_template
- Update of HTML5 video player to video.js version 4.12.5
- Bug fix: Undefined variables in search_objectlist, hypercms_tplengine, db_connect_rdbms
- Implementation of new hypercms_tcpdf.class.php file with class hcmsPDF to extend standard TCPDF functionality
- Implementation of new function drawCropbox in class hcmsPDF
- Removed deprecated pdfsearch.class.php
- Bug fix: Explorer_download did not check original-type and tried to convert media
- Bug fix: When providing a colorspace or ICC-profile in media- Or textf-tags the images would have been converted multiple times, depending on the occurrence of the tag in the template
- Implementation of new hyperCMS tag attribute 'pathtype' for media tags to declare path as file system path, URL, absolute path (URL without protocol and domain)
- Implementation of new media functions mm2px, px2mm, inch2px, px2inch
- Improved function convertimage which supports new input parameters and rendering features
- Implementation of timeout in media_view to ensure the media size fields in the control frame will be updated
- Removed convert to intermediate BMP file in function createmedia to keep transparency of images
- Implementation of thumbnail support for file generator in function buildview
- Implementation of error reporting for the generator in the template engine (function buildview)
- Implementation of new function placeImage in class hcmsPDF
- Implementation of new function is_aiimage to determine if a certain file is a vector-based Adobe Illustrator (AI) or AI-compatible EPS file
- Implementation of image denisty and quality for image rendering in function createmedia and convertimage

Version 5.7.7
Release 05/2015
- Implementation of load balancing for file upload and rendering files
- Implementation of new setting for load balancing in main configuration
- Implementation of new function HTTP_Proxy and loadbalancer
- Implementation of new function getserverload
- Implementation of new service getserverinfo (based on function getserverload)
- Temp and view directories can be set in main configuration
- Moved temp and view directory to the internal repository (for load balancer)
- Moved instance configurations to the internal repository (for load balancer)
- New function createusersession in include/session.inc.php
- Implementation of main management config in hyperCMS API loader
- Changed include order for every file: Config.inc.php -> hypercms_api.inc.php -> session.inc.php
- Implementation of new function writesessiondata for load balancer
- Function setsession supports 3rd argument to write session data for load balancer
- Variable $appsupport has been replaced by $mgmt_config['application'] in main configuration
- Improvement in function getescapedtext to escape special characters if no encoding is provided
- Use of function getescapedtext for all text strings used in JS code 
- Implementation of new ICC profiles for ECI offset 2009
- Support for transparent background of SVG files
- Bug fix: Frame resizer in control_content did not work

Version 5.7.8
Release 06/2015
- Implementation of multi assets tag for DAM usage
- Bug fix: Correct and optimize html code in form views of template engine
- Bug fix: horizontal and vertical flip of images in multiedit mode not working
- Implementation of new function getobjectid
- Function setcomplink converts multimedia object paths to object IDs and saves them in the content container (not in the link index) in order to support component links in DAM that is not using a link index
- Implementation of object ID support for single and multiple components in template engine, component_edit_page_single and component_edit_page_multi
- Implementation of accesspermissions in function compexplorer for DAM usage
- Changes in showcase templates in install directory to use the new view directory setting
- Bug fix: Download of original files for access links failed if media file was not an image or document
- Optimizations in function rdbms_searchcontent regarding joins of tables
- Implementation of search format support in search of function compexplorer
- Bug fix: Media_rendering for audio files did verify non-existing fields that caused JS error
- Bug fix: Audio data in config files for ogg files have been missing
- Audio quality setting has been enabled for editing of audio files
- Validation of theme path in function getthemelocation
- Various improvements in search_api for website search functionality
- Bug fix: If no application was defined in the content containers of assets the template engine did not execute the published object

Version 5.7.9
Release 07/2015
- Optimization of language files
- New input tag settings in main CSS for all themes
- Update of jquery from version 1.9.1 to 1.10.2
- Implementation of keyword tags with optional mandatory or open list of keywords using the new hyperCMS tag "textk"
- Improved functions loadfile and loadlockfile to reduce CPU time
- Removed default frequenzy settings for audio rendering in main configuration due to issue with OGA files
- Corrections in german language files
- Implementation of getescapetext for all files of the graphical user interface
- Bug fix: Function createmedia did not execute rdbms_setmedia if $mgmt_maxfilesize limit has been reached for a file 
- Bug fix: Installation procedure checked temp and view directory before they were created
- Update of videoplayer Video JS to version 4.12.7
- Fullscreen mode in video player has been disabled for side bar
- Bug fix: Video JS css did not properly support fullscreen when used in iframes, fullscreen is disabled in CMS views
- Implementation of extended error logging in function uploadfile
- Improvement of input validation in function splitstring
- Removed media_update input parameter from function uploadfile. media updates require the object name as input

Version 5.7.10
Release 08/2015
- Implementation of new youtube connector to support Google OAuth
- Removed youtube token from management publication configuration file (function editpublication), the token will be saved in the config directory as youtube_token.json for all publications since the refresh_token will only provided once by Google
- Implementation of new function editpublicationsetting to edit a single setting of a publication
- Function selectcontent, selectxmlcontent, selecticontent and selectxmlicontent use case-insensitve conditional value
- Larger youtube upload window
- Implementation of meta data content from videos into the youtube upload form
- Removed hypercms_eventsystem file from function directory
- New youtube video link in page_info in case the video was uploaded to youtube
- Bug fix: Function showvideoplayer created wrapperlink for the video poster image, this caused the video to be loaded as the poster
- Bug fix: Page_multiedit did not fully support keywords (list, file, listonly attributes)
- Function showmessage provided id of DIV holding the message (use ID suffix _text)
- Improvements in import connector regarding special characters in file names
- Function createpublication creates default media mapping definition file
- Function deletepublication deletes media mapping file
- Implementation of object file count and size (in info-tab of object) for pages based on function getfilesize
- Implementation of duplicate check in function rdbms_createobject
- Implementation of input paramter for object ID for function rdbms_deleteobject
- Implementation of logname as input parameter for function deletelog
- Implementation of new custom log viewer plugin to display individual logs
- Improvements in plugin management viewer
- Changed log viewer details popup from GET to POST in order to display longer messages (strings)
- Impementation of custom log manager in admin node of each publication
- New text for 'custom-system-events' in all language files
- Bug fix: The event onpublishobject_pre has not been fired if the application tag of the underlying template was empty
- Improvements in the eventysystem reg. creating the search index for PDF files
- Removed location bar from control_objectlist_menu in mobile edition to avoid scrolling on smaller screens
- Changes in CSS of mobile edition
- Removed personalization and template management from explorer of mobile edition
- Added edit button to objects in objectlist for mobile edition
- Bug fix: Delete-favorite icon in context menu has not been grayed out if no object was selected
- Bug fix: Content versions of media files did not point to correct media file if only the meta data has been changed and published
- Support for file name changes in content versioning
- Function getobjectinfo supports content versions
- Implementation of function getmediafile
- Implementation of media preview when comparing media content versions

Version 5.7.11
Release 09/2015
- Implementation of new function getcontainerversions and gettemplateversions
- Implementation of WebVTT support for videos including WebVVT editor for videos
- Renamed text ID for uploade Youtube videos from "youtube_id2 to "Youtube-ID"
- Bug fix: Milliseconds of a video timestamp was not correct for video start
- Implementation of event log entries for sent e-mails of tasks and notifications
- Update of VIDEO-JS to version 4.12.11 due to issue with WebVTT on all browsers except Chrome
- Bug fix: Function rdbms_searchcontent did not provid correct search for date limits
- Bug fix: Limit max file size in function createdocument
- Bug fix: Function showmedia renames preview pdf files to .thumb.pdf is function createdocument failed to do so
- Check of storage limit could lead to extensive delays due to function rdbms_getfilesize, memory file filesize.dat is used to store storage size for 24 hours
- Function getvideoinfo returns duration including milliseconds as well
- Bug fix: Container_id_duplicate has not been defined in function rdbms_createobject
- Replaced AUDIO JS with VIDEO JS player
- Bug fix: Content compare was not working for multimedia versions due to including .xml as version file extension
- Function showaudioplayer support width, height and poster as input parameters
- New CSS class hcmsButtonMenuActive used for buttons in top bar (see function showtopmenubar)
- Implementation of additional options button below media player in editing mode
- Removed embed button below media player in editing mode
- Improvements in options menu of media editing mode
- Support saving media file as original file with support of file versions
- Bug fix: version_content displayed .folder instead of folder name
- Function getobjectinfo provides icon in result array
- Bug fix: Template engine used template media URL from mgmt_config instead of publ_config
- Defined support of conversion to MPEG formats in config.inc.php
- Bug fix: Template_edit used double quote inside the double quote of mouse event of help button
- Bug fix: Set frameBorder=0 for all iframes to support borderless iframes in IE 8
- Bug fix: Sidebar of keyword plugin did not open and close
- Changed character set of folders from UTF-8 (hardcoded like for multimedia assets) to the given characters set of the publication or template
- Bug fix: Doctype generated by template engine included a double quote
- Reworked graphics for flat design theme
- Bug fix: Mouseover on OK buttons in version_template did not work due to same name
- Check of empty search string in general search form in top bar has been implemented
- Implementation of function getlanguageoptions to get all languages and their 2-digit codes sorted by the language name
- Bug fix: Page_multiedit did not set UTF-8 as character set for multimedia objects
- Bug fix: Implementation of natural case sort for media_edit_explorer
- Bug fix: Template media preview provided by function showmedia dit not present any information of template media files

Version 5.7.12
Release 09/2015
- Improved graphics for flat design theme
- Improved CX-showcase-template for zoom viewer in the installation directory
- Improved CX-showcase-template for 360 degree viewer in the installation directory
- Improved CX-showcase-template for gallery viewer in the installation directory
- Implementation of new home box with the favorites of a user
- Changed function createinstance to support username of user account and create the user as superuser
- Bug fix: Function createinstance did not check for special characters in the instance name
- Bug fix: Function createinstance tested the CMS config directory for wriote permissions and not the instance directory
- Bug fix: Function copyrecursive did not verify file handler
- Changed setting of strongpassword for new instances to false
- Bug fix: Function registerinstance refered to config and not to the instance directory
- Bug fix: Include of session had to be relocated to API loader in order to load the instance configuration file
- Bug fix: Instances setting of main configuration file has not been set in instance configuration file by function createinstance
- Improvements in keyword plugin
- Bug fix: Function _loadSiteConfig did not check if config file exists which can cause a fatal error if a publication has been deleted
- Bug fix: JS function setVTTtime used wrong player id
- Bug fix: Function showmedia did not provide preview of PSD files
- Bug fix: Function createmedia used the crop option before the source PSD file which led to a wrong result
- Bug fix: Function notifyusers did not load the language of a recipient
- Bug fix: Frameset_main_linking used deprecated logo file
- Improvements in user_sendlink
- Implementation of language loader for function createtask, notifyuser and licensenotification

Version 5.7.13
Release 10/2015
- Improvements in workflow_manager
- Improvements in user_sendlink
- Improvements in user_objectlist
- Removed double entries in language files
- Replaced JS based height calculation of div layers by CSS based layer positioning for all objectlist views
- Update of PDF viewer (PDF.JS) to version 1.1.215
- Update of TCPDF to version 6.2.11
- Set default link for function medialinks_to_complinks
- Function medialinks_to_complinks returns only first valid link ressource and not a link array
- Set default link for function complinks_to_medialinks
- Index page of the system is using configured domain for redirect in order to avoid session issues with multiple domains used to access the system
- Bug fix: Control_content_menu used 2nd parameter in location.replace, only one parameter is supported
- Failed FFMPEG commands are reported in event log
- Improvements in function createmedia to create player config file and create media database entry in case the conversion of a media file failed after upload
- Moved from head tags to text-IDs for meta data in attitude templates

Version 5.7.14
Release 10/2015
- Improvements on mobile home screen
- Bug fix: User_sendlink did not validate array for email recipients
- Improvements in template engine to avoid line break of edit icons in "cmsview" and "inlineview"
- Improvements in template engine regarding language session handling
- Bug fix: Txt file extension has been defined as clear text format and image format
- Bug fix: Function showmedia did not properly convert non UTF8 strings
- Updated PHPWord library to version 0.12.0
- Removed unused library charsetconversion
- Bug fix: Function downloadobject did not get page via HTTP view and failed to render it
- Implementation of search expression logging in function rdbms_searchcontent
- Implementation of search expression statistics plugin
- Rework of icons in all themes
- Bug fix: Keyword plugin always selected english language version
- Bug fix: Keyword plugin only stores assets or pages keywords in stats file and did not join them
- Updates in keyword analysis plugin to support new language file format
- Bug fix: Pagecontenttype select in template engine has not been added to the form item string
- Updates in simple stats plugin to support new language file format
- Updates in test plugin to support new language file format

Version 5.8.0
Release 11/2015
- Implementation of search expressions recommender based on the search history of all users
- Bug fix: Deleting folders caused workplace control to display wrong location (folder has been added to location for each step of popup_status)
- Bug fix: Deleting folders using the context menu could cause deleting other folders since the values of the context menu has not been locked for writing
- Implementation of new JS functions hcms_lockContext and hcms_isLockedContext
- Improvements in JS library for context menu
- Improvements in frameset_main_linking to support search expression recommender, removed dynamical framesets, implementation of sidebar configuration check
- Implementation of max keyword length of 255 digits to avoid long strings that have been imported as keywords (e.g. Adobe Indesign documents with unreadable keyword strings)
- Bug fix: Location has been undefined in popup_status if no folder has been provided as input request
- Changed max search hits from 1000 to 500 in top bar search forms
- Bug fix: User_sendlink did not define upper case letter in password to fullfill strong password criteria
- Bug fix: User_sendlink did not set all general_errors as array elements
- Implementation of the new standard design theme
- Bug fix: Undefined variable $type and $thumb_pdf_exists in function showmedia in hyperCMS UI
- New set of user manuals
- Implementation of new main JS function hcms_getURLparameter
- Implementation of "Remember me" feature for logon, using the local storage of the browser
- Implementation of new text in all language files for "Remember me" feature
- Implementation of workflow as a module that is part of the Standard and Enterprise Edition
- Implementation of absolute path check for references to manuals
- Implementation of absolute URL in hypercms_main API as referrer to empty.php

Version 5.8.1
Release 11/2015
- Bug fix: Function checkworkflow did not exclude .folder of the folder path for comparison
- Update of CKEditor to version 4.5.4 due to issue with source code view in MS Edge browser
- Implementation of the YouTube plugin for CKEditor in all toolbar configurations, except DAM and PDF
- Optimizations in rich text editor UI
- Implementation of Spellchecker and Scayt plugin for CKEditor in all toolbar configurations, except DAM
- Implementation of share link generator
- Implementation of social media share link function in connect API: Createsharelink_facebook, createsharelink_twitter, createsharelink_googleplus, createsharelink_linkedin, createsharelink_pinterest
- Implementation of social media sharing for media files in hyperCMS UI
- Implementation of new publication setting for social media sharing
- Implementation of JS functions for social share links: hcms_sharelinkFacebook, hcms_sharelinkTwitter, hcms_sharelinkGooglePlus, hcms_sharelinkLinkedin, hcms_sharelinkPinterest
- Implementation of JS function hcms_getcontentByName to get value of form field by its name
- Implementation of share links for media files in template engine
- New organisation of directory structure for connector module and changes in youtube connector
- Add new text to language files for social media sharing
- Optimizations in HTML5 file upload
- Change to HTTPS for IP geo location finder in Google maps

Version 5.8.2
Release 11/2015
- Implementation of AES 256 encrpytion based on OpenSSL as standard strong encryption with fallback to Mcrypt (CBC), Mcrypt uses base64 incoding to be binary-safe, this leads to larger encrypted files and is therefore deprecated since version 5.8.2
- Changed encryption of container data to strong as default (same as file encryption)
- Implementation of config setting for the key for AES 256 encrpytion: $mgmt_config['aes256_key']
- Removed base64 encoding from function encryptfile, decryptfile, creattempfile and movetempfile in order to reduce the size of encrypted files
- Implementation of binary mode for writing files using function savefile and savelockfile due to encryption without base64 encoding
- Removed default base64 encoding from standard encryption in function hcms_encrypt
- Improvements in template engine for autosave
- Bug fix: reset of medaview variable in function showmedia has been removed
- Bug fix: Medianame has not been converted to UTF-8 for media viewer in template engine 
- Function hcms_encrypt and hcms_decrpyt will base64 en/decode the string if 'url' encoding is requested in order to be binary safe
- Bug fix: The character set of the form has not been set to UTF-8 in the template engine in case of editing media files
- Implementation of file locking in function iptc_writefile
- Implementation of file stats (rdbms_setmedia) in function iptc_writefile, xmp_writefile and id3_writefile to update MD5 hash and filesize in DB
- Bug fix: removed trim of encrypted data from function savecontainer, this is a manipulation of the data string and could lead to decryption issues when handling binary data
- Implementation of additional MD5 hash comparison of encrypted file and temporary unencrypted file in function createtempfile
- Bug fix: Function rdbms_setmedia did not update MD5 hash since wrong variable name has been used for value check
- Various improvements in function iptc_writefile, xmp_writefile and id3_writefile
- Bug fix: Function xmp_writefile did also write data to file if an error occured
- Bug fix: Previous create of temporary unencrypted file has been checked for moving file back into encrpyted version, this caused the file not being encrypted and moved again by function iptc_writefile, xmp_writefile and id3_writefile
- Bug fix: Function iptc_writefile did a reset of the input array $iptc
- Bug fix: Undefined variables and undefined hidden field for 'filetype' in popup_message
- Bug fix: Undefined variable 'mediafile' in template engine
- Implementation of movetempfile input paramter in function iptc_writefile, id3_writefile and xmp_writefile due to file collision when using encryption and moving temporary unencrypted file back to encrypted file 
- Implementation of media file statisticts update and encryption of file into service savecontent
- Bug fix: Webdav function _runFuncWithGlobals requires 0 and 1 instead of false and true in order to pass those values to the API function
- Changes in language files
- Implementation of function avoidfilecollision due to issues when manipulating encrypted files with e.g. function createmedia and the shell execute file process has not been finished
- Removed file encryption feature from free to standard and enterprise edition
- Improvements in hyperCMS UI
- Bug fix: Function creatmedia did not render edited videos properly if the file has been encrypted
- Bug fix: Function creatmedia passed wrong file name to createversion if the file has been encrypted
- Change of watermarking in function createmedia to keep original media file without watermark
- Bug fix: Function creatmedia did not use comma as separator when using multiple FFMPEG video filters at once
- Bug fix: The install script did not empty the %instances% place holder
- Implementation of instances path verification in userlogin

Version 5.8.3
Release 12/2015
- Updates in black UI theme
- Updates in colorful UI theme
- Updates in livelink for PHP
- Improvements in language selector styles of template engine to avoid changes in font-style in WYSIWYG inline editing mode
- Improvements in function errorhandler to support error notices
- Improvements in template engine to skip further execution if an error occured
- Improvements in function showinlineeditor to avoid changes in font-style in WYSIWYG inline editing mode
- Bug fix: Targetlist has not been read from the request in link_edit_page
- Bug fix: Undefined variable list_array in link_edit_page
- Improvements in template editor
- Implementation of new attribute prefix ans suffix for all hypercms text tags; prefix and suffix will be appended to the not empty text content
- Implementation of third secure input parameter for function getattribute to secure (XSS) return value or not
- Added new prefix and suffix attributes to template_help
- Added new text for prefix and suffix attribute to all language files
- Fixed head edit buttons position for WYSIWYG view modes of template engine
- Implementation of user information in content comparison
- Implementation on new versions of content on save (new configuration paramater $mgmt_config['contentversions_all'])
- Bug fix: Function createversion used working container file extension when creating a new version
- Bug fix: Original media with and height file parameters have not been correctly set by function showmedia
- Bug fix: Reduced minimal thumbnail size from 400 to 10 bytes for thumbnail image file size check due to thumbnails that can be smaller than 400 bytes

Version 5.8.4
Release 12/2015
- Improvements in template engine regarding HTML tags
- Implementation of new task management with data storage in database instead of XML files. New features include support of start/finish date and status for tasks.
- Implementation of tabs for task management for the management of the users tasks and tasks the user assigned to others 
- Implementation fo new RDBMS functions rdbms_createtask, rdbms_settask, rdbms_gettask, rdbms_deletetask
- Implementation of update function update_tasks_v584 that is executed in function userlogin
- Implementation of new parameters for startdate and finishdate in function createtask
- Removed xmlschema/task.schema.xml.php
- Minor correction in English language file
- Improvemens in page_info_ip
- Implementation of new CSS class hcmsPriorityAlarm
- Implementation of an alarm if the finish date of a task has been reached
- Update of task home box to work with new task functions
- New text for task management in all language files
- Bug fix: Undefined variable charset in user_sendlink
- Minor improvements imn hyperCMs UI
- Changed max. task description length from 1600 to 3600 digits
- Implementation of HTML support for e-mail notifications and task notifications
- Moved task management from Free to Standard Edition
- Added support for task name and task time to database and task management
- Added support for array in DB Connect function escape_string
- Implementation of new function tasknotification
- Implementation of task notification in daily jobs
- Implementation of support of loop, muted and controls support for videoplayer. New input parameters loop, muted and controls for function showvideoplayer and showaudioplayer.
- Implementation of loop, muted and controls support in CKEditor Video Plugin.
- Implementation of loop, muted and controls support in media_playerconfig
- Removed keyboard controls support for videoplayer (since only supported by PROJEKKTOR)
- Implementation of new text in all language files
- Bug fix: If the task has been activated in user_sendlink the access link type has been checked automatically without unchecking all other types
- Improvements in template engine for comments
- Improvements in function getmetadata to add space after comma in order to allow automatic line breaks

Version 5.8.5
Release 01/2016
- Bug fix: Sort order in navigation template of demo website did not sort numbers
- Buf fix: Function showmedia did not apply line break for media name after media view
- Bug fix: Function showmedia did not activate video player controls
- Implementation of force_reload in function showmedia
- Bug fix: Function showvideoplayer did not set loop to true or false for VIDEO.JS player
- Bug fix: Changed controls to true by default in videoplayer to provide controls if the controls pararmeter has not been set
- Bug fix: Function url_encode has not been applied on wrapper link parameter wl and download link parameter dl in index.php
- Changed default crypt_level setting to "strong" in main configuration
- Bug fix: Function showmedia did read video information from original video config file with preview settings. In order to get the correct video information the original video file need to be analyzed.
- Display only unfinished tasks in home box
- Changed escape character from "~" to "." for url encoded strings in function hcms_encrypt and hcms_decrypt, since tilde should not be used in an URL.
- Improvements in function appendfile to avoid file collision
- Removed seek and pause support from function showvideoplayer since only PROJEKKTOR supported these features
- Removed seek and pause support from videoplayer since only PROJEKKTOR supported these features
- Improvements in task management: superadmin can access all tasks, short object names with full name in href title
- Implementation of user filter for task management
- Implementation of paging for task management
- Added hcmsMore CSS class to main.css
- Improved user detection of task owner (sender) in function rdbms_createtask
- Implementation of new Plugin to display access statistics for the favorites of a user
- Implementation of URL rewriting, new function rewrite_targetURI and rewrite_homepage, new "rewrite" folder in install directory holding an example configuration for URL rewriting
- Added support for permament links to function getgooglesitemap
- Added support for permament links to template engine
- Bug fix: function publishobject did not check the result of the template engine for errors
- Implementation of wrapper and download link support for hyperCMS media tags in template engine (use "wrapper" or "download" for pathytpe attribute)
- Changed "abs" to "uri" as pathtype value in template engine ("abs" still supported but deprecated)
- Update of all language files
- Improvement in XML API to support tags and tag names as input
- Implementation of function showAPIdocs to generate API function documentation based of a file (part of hyperCMS UI)
- Implementation of page location memory for page explorer of the rich text editor
- Update of the search_api for websites with improvements and bug fixes in function cleancontent and searchindex
- Implementation of full hyperCMS API Function Reference generator in help/api_functions
- Implementation of full hyperCMS API Function Reference in template_help
- Bug fix: Workflow script help used a wrong reference to the help file

Version 6.0.0
Release 01/2016
- Update of function getdescription to limit description length
- Implementation of new function is_emptyfolder
- Implementation of new UI functions readnavigation, createnavigation and shownavigation (gernerating navigations for websites)
- hyperCMS UI API will be loaded in hyperCMS API and has been removed from all files
- Updates in Navigation template of demo website to work with new UI navigation functions
- Implementation of new input paramater to enable and disable search expression logging for function rdbms_searchcontent
- Improved error handling in template engine to display errors and render document without errors
- Implementation of search history log for website search
- Implementation of adLDAP version 4.0.4 (MS Active Directory support) in the Connector module.
- Implementation of new function rdbms_gettableinfo in DB-Connect
- Implementation of new function sql_clean_functions in Security API
- Implementation of new function rdbms_externalquery in DB-Connect
- Implementation of new function create_csv in Main API
- Implementation of new function analyzeSQLselect in Report API
- Implementation of report management in connector module of Enterprise Edition. The report management can be used to define and generate reports.
- Implemetation of new functions createreport, editreport, loadreport, deletereport in Report API of Report module
- Implementation of getrequest_esc for task name and description in task_list
- Implementation of exact name for value extraction using explode in function readmediaplayer_config
- Implementation of new functions showpiechart, showcolumnchart, showtimelinechart, showgeolocationchart in Report module
- Update of all language files to include text for Report module
- Changed max. length of user name to 60 digits instead of 20
- Changes in database field length for user
- Implementation of update function for database to add new fields to table textnodes and alter various other fields in tables
- Renamed column sender to from_user and user to to_user in table recipient and DB connect
- Implementation of object_id in table textnodes for object references
- Implementation of date, media alt-text and link-text in function buildsearchform of template engine
- Bug fix: removed deprected hidden date field from function buildview in template engine
- Bug fix: frenach and russian language version or Rich Calendar included wrong characters at the end of the language file
- Various improvements in template engine regarding the date picker JS functions
- Implementation of new function getdirectoryfiles
- Implementation of home box for all reports

Version 6.0.1
Release 01/2016
- Update of old http-equiv meta tags with new charset meta tags
- Implementation of new charset support in function getcharset
- Bug fix: function buildview of the template engine included two charset meta tags
- Improvements in function gethtmltag
- Improvements in function searchindex (search engine for websites)
- Changed default zoom parameter for function showgelocationchart from 10 to 4
- Bug fix: Function showcolumnchart did not verify the 2nd and 3rd y-values if their titles have been defined
_ Display title on top of table in reports
- Bug fix: Function buildview of template engine did use JS function name for show the rich calendar for articles
- Bug fix: user_sendlink referred to old "taskmgmt" directory
- Improvements for date picker (rich calender functions) for articel_edit, user_sendlink, opup_publish and function buildview
- Improvements in rich calendar JS library
- Implementation of object creation for database in case the object hash could not be retrieved by function getwrapperlink and getdownloadlink
- Bug fix: Function rdbms_getfilesize did use DISTINCT in count function when counting objects which led to zero as result
- Bug fix: Funtion rdbms_searchcontent did include the publication twice for the search in objectpath when searching for a location 
- Added new column "planned" effort and renamed "duration" to "actual" effort in table "task"
- Implementation of new field for planned effort into task management
- Implementation of function update_database_v601 for update to version 6.0.1
- Implementation of new main management configuration setting $mgmt_config['taskunit']
- Implementation of function correctnumber
- Bug fix: Removed task management permission from group_edit_form if task management module is not installed
- Moved workflow functions to separate file
- Moved task functions to separate file
- Joined object and object_id as input parameter for DB Connect functions rdbms_createtask, rdbms_settask and rdbms_gettask due to new support for object path as input
- Removed publication and location column from task management tables and added this information as mouseover title to object
- Added date picker to task management
- Improvements in function createtoken
- Implementation of OCR (based on tesseract) to index the text in any kind of images, implemented in main confi file, installation routine and function indexcontent
- Moved function inexcontent and unindexcontent from main to media API
- Implementation of new main configuration setting for tables including into report management: $mgmt_config['report_tables']
- Implementation of search feature in explorer
- Replaced all single dates fields in all search forms with date picker
- Improved search result sorting in function rdbms_searchcontent by sorting only the object names and not the object path
- Bug fix: table sorting JS funtion hcms_sortTable die not clean html tags proberly if a line break was used in a tag. JS function hcms_stripHTML removes all line breaks before stripping the tags from the string.
- New input paramater for CSS-display in function buildsearchform in template engine
- Bug fix: Function createuser did still check for file system based task list of user

Version 6.0.2
Release 01/2016
- Implementation of new project management feature
- Implementation of new RDBMS functions rdbms_createproject, rdbms_setproject, rdbms_getproject, rdbms_deleteproject
- Update of all language files for new project management feature
- Implementation of new permission "desktopprojectmgmt" in group_edit_form, function editgroup and rootpermission
- Updates in xmlschema/usergroup for new project management permissions in all default groups
- Integration of project management in explorer
- Removed sensor from Google Maps API loader
- Bug fix: serach_form_rdbms did load default page template without checking if it exists
- Various improvements in function rdbms_searchcontent in DB Connect
- Implementation of new help bubble info for workplace controls
- Added various video file extensions to include/format_ext
- Removed maximum number of results from search form
- Improvements in main.js regarding the evaluation of the input paramaters of Js functions
- Excluded geo location search for mobile edition
- Bug fix: hcms_showInfo an dhcms_hideInfo on the buttons caused form elements to be disabled in explorer when switching from search to navigator and back again
- Changed to JS function hcms_showHideLayers in template_help
- Minor changes in CSS of standard theme
- Function showinfobox shows infobox as long as user did not close it and remembers the close action in localstorage of browser
- Bug fix: new infoboxes for workplace controls used onload event to close the infobox, this interferred with the existing onload event of the workplace control
- Placed sort of search result in search_objectlist and removed order by from rdbms_searchcontent
- Bug fix: List value and text has not been supported for textl tag in function buildsearchform in template engine
- Design changes in popup_status 
- Changes in function uploadfile, editpublication in order to support meta data editing during upload process
- Changes in site_edit_form to support new configuration setting for meta data editing during upload process
- Changes in function createtask and rdbms_createtask to support the planned effort as input
- Changes in task_list due to the support of tasks without an object_id
- Design improvements in task management
- Implementation of function showganttchart into project management module
- Improved documentation of API functions

Version 6.0.3
Release 01/2016
- Minor improvements in media_select of rich text editor
- Improvements of usability in popup_publish
- Bug fix: Function rootpermission did not read project management permission correctly
- Implementation of SQL statement for error reports in function query of DB Connect
- Implementation of double quotes for numerical conditions in several functions of DB Connect in order to improve error reporting
- Implementation of validation of onedit and infotype attributes for template view in template engine
- Bug fix: Function createtask used wrong variable name for object path
- Bug fix: Function createtask used wrong variable name for error reporting
- Implementation of warning suppression for function rootpermission, globalpermission, and localpermission in case of unset permissions
- Implementation of labels for all checkboxes in user_sendlink
- Design optimizations in user_sendlink
- Bug fix: The default main configuration file includes Somaly and Swedish language twice
- Implementation of bubble titles for all tabs in the system
- Design changes in the top bar of the system
- Corrections in Japanese language file
- Update of user manuals: Installation guide and users guide
- Bug fix: Function indexcontent did not use the content container from function setmetadata, causing meta data from files not be saved
- Implementation of new input parameter for content container and container save in function setmetadata
- Bug fix: Setting $mgmt_config[$site_name]['upload_userinput'] in site_edit_from has not been initalized
- Implementation of result validation of object in function uploadfile

Version 6.0.4
Release 02/2016
- Minor improvements in function indexcontent
- Bug fix: WebDAV function createObject did not proberly pass the paramaters to function uploadfile, causing a check of duplicates when cut and paste was used via WebdAV
- Bug fix: JS function setSaveType of template engine did not forward to target URL when selecting media
- Modifications in navigator CSS
- Bug fix: Function transformlink of template engine did transform links used in JS functions of template engine
- Bug fix: Function HTTP_Post did not verify the fsockopen result
- Bug fix: Install script could not create demo website due to a reset of the $mgmt_config array
- Changed license verification in function userlogin to avoid issue when license server can not be accessed
- Bug fix: Initalization of output varibles in function showobject
- Bug fix: Verification of variable $tpl_name has been missing in explorer
- Improvements in function tpl_globals_extended of the template engine
- Bug fix: Button for image and media editing used AJAX save methode of form data and did not forward to image or media editing view
- Improvement in explorer_download to verify temporary file age before compression

Version 6.0.5
Release 02/2016
- Presenting download links for folders in info tab
- Updates and changes in language files and workplace controls
- Implementation of new function sendmessage in main API
- Implementation of e-mail messaging for chat invitations
- Implementation of support user for chat that will always be visible
- New main configuration setting for chat support user
- Added support for iPad scrolling for frameset_content
- Removed chat for iPad and iPhone due to display issues with JQuery mobile panel for chat
- Bug fix: Save and close button did not use post methode and therfore did not close the form view created by the template engine
- Improvements in popup_publish
- Bug fix: JS function hcms_openWindow used in various files declared windows size as string and not as integer
- Bug: MS Edge opens window in same dimension of parent window if not in fullscreen mode. This is an issue of MS Edge and can not be solved.

Version 6.0.6
Release 02/2016
- Bug fix: Verification of $compaccess has been missing in webdav function getChildsForLocation
- Bug fix: Report CSV export button targeted wrong file name
- Implementation of table column resizing in all objectlist views
- Bug fix: File version_template used wrong reference to external JS file
- Bug fix: File template_edit used single quote for width paramater in function hcms_openWindow call
- Bug fix: Logviewer plugin used wrong parameter for function showinfobox call
- Implementation of table column resizing for log viewer
- Implementation of table column resizing for logviewer plugin
- Implemen tation of new streaming logic in function downloadfile
- Included download events into function downloadfile and removed them from explorer_download and explorer_wrapper
- Improvements in hypercms_api loader for language file
- Implementation of new media streaming service "mediastreaming" for video player to solve issues with streaming on mobile browsers
- Bug fix: UI Function showmessage did not provide text container with ID "message_text" used by YouTube connector
- Implementation of new function createviewlink
- Replacement of harcoded logic for links by function creatviewlink in all files
- Moved explorer_download and explorer_wrapper as mediadownload and mediawrapper to service
- Changes in UI function showvideplayer and showaudioplayer to support new streaming service
- Bug fix: Function copymetadata used wrong filename for error reporting
- Bug fix: Several language files did not use proper %user% variable in the text strings
*/

// current version
$version = "Version 6.0.6";
?>