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
$user =  getsession ("hcms_user");
$passwd =  getsession ("hcms_passwd");
$lang =  getsession ("hcms_lang", "en");
// access parameter (array)
$siteaccess =  getsession ("hcms_siteaccess");
$pageaccess =  getsession ("hcms_pageaccess");
$compaccess =  getsession ("hcms_compaccess");
// permission parameter (array)
$rootpermission =  getsession ("hcms_rootpermission");
$globalpermission =  getsession ("hcms_globalpermission"); 
$localpermission =  getsession ("hcms_localpermission");
$adminpermission =  getsession ("hcms_superadmin");
$hiddenfolder =  getsession ("hcms_hiddenfolder");
// download formats
$downloadformats =  getsession ("hcms_downloadformats");
// mobile browser
$is_mobile =  getsession ("hcms_mobile");
$is_iphone =  getsession ("hcms_iphone");
// HTML5 file support
$html5file =  getsession ("hcms_html5file");
// mail linking parameter (array)
$hcms_linking =  getsession ("hcms_linking");
// other temporary session parameters
$temp_sessiontime =  getsession ("hcms_temp_sessiontime");
$temp_clipboard =  getsession ("hcms_temp_clipboard");
$temp_explorerview =  getsession ("hcms_temp_explorerview");
$temp_objectview =  getsession ("hcms_temp_objectview");
$temp_sidebar =  getsession ("hcms_temp_sidebar");
$temp_site =  getsession ("hcms_temp_site");
$temp_user =  getsession ("hcms_temp_user");
$temp_pagelocation =  getsession ("hcms_temp_pagelocation");
$temp_complocation =  getsession ("hcms_temp_complocation");
$temp_latitude =  getsession ("hcms_temp_latitude");
$temp_longitude =  getsession ("hcms_temp_longitude");
$temp_chatstate =  getsession ("hcms_temp_chatstate");
$temp_balancer_id = getsession ("hcms_temp_balancer_id");
// the temporary storage is used to pass container content of files between functions
// and to trigger saving of the file [yes/no]
$temp_cache =  getsession ("hcms_temp_cache", "");
$temp_save =  getsession ("hcms_temp_save", "yes");
// security token
$temp_token =  getsession ("hcms_temp_token");
// hyperCMS theme
$hcms_themename =  getsession ("hcms_themename");
$hcms_themelocation =  getsession ("hcms_themelocation");
// filter options for object list
$objectfilter =  getsession ("hcms_objectfilter");
?>