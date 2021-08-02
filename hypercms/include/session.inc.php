<?php
/*
 * This file is part of
 * hyper Content Management Server - http://www.hypercms.com
 * Copyright (c) by hyper CMS Content Management Solutions GmbH
 *
 * You should have received a copy of the License along with hyperCMS.
 */

// ================= create SESSION ===================

createsession ();
 
// ============= read SESSION parameters ==============

// instance parameter
$instance =  getsession ("hcms_instance");

// user parameter
$user = getsession ("hcms_user");
$passwd = getsession ("hcms_passwd");
$lang = getsession ("hcms_lang", "en");
$timezone = getsession ("hcms_timezone");

// service parameter for the registered services by a user (array)
$services = getsession ("hcms_services");

// access parameter (array)
$siteaccess = getsession ("hcms_siteaccess");
$pageaccess = getsession ("hcms_pageaccess");
$compaccess = getsession ("hcms_compaccess");
$pluginaccess = getsession ("hcms_pluginaccess");

// permission parameter (array)
$rootpermission = getsession ("hcms_rootpermission");
$globalpermission = getsession ("hcms_globalpermission"); 
$localpermission = getsession ("hcms_localpermission");
$adminpermission = getsession ("hcms_superadmin");
$hiddenfolder = getsession ("hcms_hiddenfolder");

// download formats
$downloadformats = getsession ("hcms_downloadformats");

// mobile browser
$is_mobile = getsession ("hcms_mobile");
$is_iphone = getsession ("hcms_iphone");
$viewportwidth = getsession ("hcms_temp_viewportwidth");

// HTML5 file support
$html5file = getsession ("hcms_html5file");

// mail linking parameter (array)
$hcms_linking = getsession ("hcms_linking");

// portal (public access link)
$hcms_portal = getsession ("hcms_portal");
$hcms_favorites = getsession ("hcms_favorites");

// asset browser
$hcms_assetbrowser = getsession ("hcms_assetbrowser");
$hcms_assetbrowser_location = getsession ("hcms_assetbrowser_location");
$hcms_assetbrowser_object = getsession ("hcms_assetbrowser_object");

// other temporary session parameters
$temp_sessiontime = getsession ("hcms_temp_sessiontime");
$temp_clipboard = getsession ("hcms_temp_clipboard");
$temp_explorerview = getsession ("hcms_temp_explorerview");
$temp_objectview = getsession ("hcms_temp_objectview");
$temp_sidebar = getsession ("hcms_temp_sidebar");
$temp_site = getsession ("hcms_temp_site");
$temp_user = getsession ("hcms_temp_user");
$temp_pagelocation = getsession ("hcms_temp_pagelocation");
$temp_complocation = getsession ("hcms_temp_complocation");
$temp_latitude = getsession ("hcms_temp_latitude");
$temp_longitude = getsession ("hcms_temp_longitude");
$temp_chatstate = getsession ("hcms_temp_chatstate");
$temp_balancer_id = getsession ("hcms_temp_balancer_id");
$temp_project_id = getsession ("hcms_temp_project_id");
$temp_appendcontent = getsession ("hcms_temp_appendcontent");

// the temporary storage is used to pass container content of files between functions
// and to trigger saving of the file [yes/no]
$temp_cache = getsession ("hcms_temp_cache", "");
$temp_save = getsession ("hcms_temp_save", "yes");

// security token
$temp_token = getsession ("hcms_temp_token");

// hyperCMS theme
$hcms_themename = getsession ("hcms_themename");
$hcms_themelocation = getsession ("hcms_themelocation");
$hcms_themeinvertcolors = getsession ("hcms_themeinvertcolors");

// filter options for object list
$objectfilter = getsession ("hcms_objectfilter");

// definition of objectlist columns
$objectlistcols = getsession ("hcms_objectlistcols");

// definition of toolbar functions/icons
$toolbarfunctions = getsession ("hcms_toolbarfunctions");

// definition of labels from templates
$labels = getsession ("hcms_labels");
?>