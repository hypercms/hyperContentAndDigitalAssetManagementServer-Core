<?php
/*
 * This file is part of
 * hyper Content Management Server - http://www.hypercms.com
 * Copyright (c) by hyper CMS Content Management Solutions GmbH
 *
 * You should have received a copy of the License along with hyperCMS.
 */

// session parameters
require ("include/session.inc.php");
// management configuration
require ("config.inc.php");
// hyperCMS API
require ("function/hypercms_api.inc.php");
// hyperCMS UI
require ("function/hypercms_ui.inc.php");


// input parameters
$action = getrequest ("action", "objectname");
$multiobject = getrequest ("multiobject");
$location = getrequest_esc ("location", "locationname");
$folder = getrequest_esc ("folder", "objectname");
$foldernew = getrequest_esc ("foldernew", "objectname");
$page = getrequest_esc ("page", "objectname");
$pagenew = getrequest_esc ("pagenew", "objectname");
$contexttype = getrequest_esc ("contexttype"); // contextmenu context-types (folder, object, media)
$from_page = getrequest ("from_page");
$virtual = getrequest ("virtual", "numeric");
$token = getrequest ("token");
$convert_type = getrequest ("convert_type");
$convert_cfg = getrequest ("convert_cfg");

// for mobile we access folders only via navigator
if ($is_mobile && substr_count ($location, "/") > 2)
{
  // split location to location and folder
  if ($folder == "" && $page == "")
  {
    $page = ".folder";
    $folder = getobject ($location);
    $location = getlocation ($location);
  }
}

// get publication and category
$site = getpublication ($location);
$cat = getcategory ($site, $location); 

// initalize object linking
if ($location == "" && is_array ($hcms_linking)) 
{
  $site = valid_publicationname ($hcms_linking['publication']);
  $cat = $hcms_linking['cat'];
  $location = valid_locationname ($hcms_linking['location']);
  if ($hcms_linking['object'] != "") $page = valid_objectname ($hcms_linking['object']);
  else $page = "";
}

// publication management config
if (valid_publicationname ($site)) require ($mgmt_config['abs_path_data']."config/".$site.".conf.php");
// load publication configuration
if (valid_publicationname ($site)) $publ_config = parse_ini_file ($mgmt_config['abs_path_rep']."config/".$site.".ini");

// ------------------------------ permission section --------------------------------

// correct location for access permission
if ($folder != "" || $page == ".folder")
{
  $location_ACCESS = deconvertpath ($location.$folder."/", "file");
  $location_ACCESS_esc = convertpath ($site, $location_ACCESS, $cat);
}
else
{
  $location_ACCESS = deconvertpath ($location, "file");
  $location_ACCESS_esc = convertpath ($site, $location_ACCESS, $cat);
}

// check access permissions
$ownergroup = accesspermission ($site, $location_ACCESS, $cat);
$setlocalpermission_ACCESS = setlocalpermission ($site, $ownergroup, $cat);

// we check for general root element access since localpermissions are checked later
if ($virtual != 1 && ($ownergroup == false || $setlocalpermission_ACCESS['root'] != 1 || !valid_publicationname ($site) || !valid_locationname ($location))) killsession ($user);

// check session of user
checkusersession ($user);

// --------------------------------- logic section ----------------------------------

$show = "";
$add_onload = "";
$usedby = "";

// convert location
$location = deconvertpath ($location, "file");
$location_esc = convertpath ($site, $location, $cat);

$multiobject_count = 0;

// if multiobject
if ($multiobject != "")
{
  $multiobject_count = sizeof (link_db_getobject ($multiobject));

  if ($multiobject_count > 1)
  {
    $pagename = $multiobject_count.$hcms_lang['objects-selected'][$lang];
  }
  elseif ($multiobject_count == 1)
  {
    $object = getobject ($multiobject);
    
    if (is_dir ($location.$object))
    {
      $page = ".folder";
      $folder = $object;
      $pagename = specialchr_decode ($folder);
      $media = "";
      $file_info = getfileinfo ($site, $location.$page, $cat);  
    }
    else
    {
      $location_esc = convertpath ($site, $location, $cat);
      $page = correctfile ($location, $object);
      $file_info = getfileinfo ($site, $location.$object, $cat);
      $pagename = $file_info['name'];
      $folder = "";
    }
  }
}
// if object
elseif ($location != "" && $page != "")
{
  // folder
  if ($folder != "")
  {
    $page = ".folder";
    $pagename = specialchr_decode ($folder);
    $file_info = getfileinfo ($site, $page, $cat);
  }
  // object
  else
  {
    $page = correctfile ($location, $page);
    $file_info = getfileinfo ($site, $page, $cat);
    $pagename = $file_info['name'];
  } 
}
else
{
  $pagename = "";
  $file_info = Null;
}

// load object file and get container and media file
$objectdata = loadfile ($location_ACCESS, $page);
$contentfile = getfilename ($objectdata, "content");
$container_id = substr ($contentfile, 0, strpos ($contentfile, ".xml"));  
$media = getfilename ($objectdata, "media");

// set local permissions for current location
$ownergroup = accesspermission ($site, $location, $cat);
$setlocalpermission = setlocalpermission ($site, $ownergroup, $cat);

// set local permission for parent folder
$location_down = getlocation ($location);
$location_down_esc = getlocation ($location_esc);
$ownergroup_down = accesspermission ($site, $location_down_esc, $cat);
$setlocalpermission_down = setlocalpermission ($site, $ownergroup_down, $cat);

// define message if object is checked out by another user
if (!empty ($contentfile))
{
  $usedby_array = getcontainername ($contentfile);
  
  if (is_array ($usedby_array) && !empty ($usedby_array['user'])) $usedby = $usedby_array['user'];
  else $usedby = "";
  
  if ($usedby != "" && $usedby != $user) $show = $hcms_lang['object-is-checked-out-by-user'][$lang]." '".$usedby."'";
  else $show = "";
}

// execute action
if (checktoken ($token, $user))
{
  // create folder
  if ($action == "folder_create" && $setlocalpermission['root'] == 1 && $setlocalpermission['foldercreate'] == 1) 
  {
    $result = createfolder ($site, $location, $foldernew, $user);
  
    $add_onload = $result['add_onload'];
    $show = $result['message'];
  }
  // rename folder
  elseif ($action == "folder_rename" && $setlocalpermission['root'] == 1 && $setlocalpermission['folderrename'] == 1) 
  {
    $result = renamefolder ($site, $location, $folder, $foldernew, $user);
    
    $add_onload = $result['add_onload'];
    $show = $result['message'];
    $folder = $result['folder'];
    $pagename = $result['name'];  
  }
  // rename object
  elseif ($action == "page_rename" && $page != ".folder" && $setlocalpermission['root'] == 1 && $setlocalpermission['rename'] == 1)
  {
    $result = renameobject ($site, $location, $page, $pagenew, $user);
    
    $add_onload = $result['add_onload'];
    $show = $result['message'];  
    $page = $result['object'];
    $pagename = $result['name'];
    $filetype = $result['objecttype'];
  }
  // create zip
  elseif ($action == "zip" && $setlocalpermission['root'] == 1)
  {
    $zipFolder = $mgmt_config['abs_path_cms']."temp/";
   
    if ($multiobject != "")
    {
      $multiobject_array = link_db_getobject ($multiobject);
    }
    elseif ($folder != "" && is_dir ($location.$folder))
    {
      $multiobject_array[0] = $location.$folder;
    }
    elseif ($page != "" && $page != ".folder" && is_file ($location.$page))
    {
      $multiobject_array[0] = $location.$page;
    }
  
    $result = zipfiles ($site, $multiobject_array, $zipFolder, $pagenew, $user);
  
    if ($result == true) $result = createmediaobject ($site, $location, $pagenew.".zip", $zipFolder.$pagenew.".zip", $user);
    else $result['result'] = false;
    
    if ($result['result'] == true)
    {
      $add_onload = "parent.frames['mainFrame'].location.reload();";
      $show = $hcms_lang['the-file-'][$lang].$pagenew.$hcms_lang['zip-was-created'][$lang];
      $page = $result['object'];
      $pagename = $result['name'];
      $filetype = $result['objecttype'];  
    }
    else
    {
      // max file size default value is 2000 MB
      if (!isset ($mgmt_config['maxzipsize'])) $mgmt_config['maxzipsize'] = 2000;
      $show = $hcms_lang['the-file-'][$lang].$pagenew.str_replace ("%filesize%", $mgmt_config['maxzipsize'], $hcms_lang['zip-could-not-be-created-max'][$lang]);
    }
  }
}

// get file info
if ($page != "") 
{
  // correct object file name
  $page = correctfile ($location_ACCESS, $page, $user);      
  // get file info
  $file_info = getfileinfo ($site, $location_ACCESS.$page, $cat);
  $filetype = $file_info['type'];
}
else
{
  $file_info = Null;
  $filetype = "";
  $pagename = "";
}

// define object category name and check directory and component access rights of user
if ($filetype == "Page")
{
  $pagecomp = $hcms_lang['page'][$lang];
}
elseif ($filetype == "Component")
{
  $pagecomp = $hcms_lang['component'][$lang];
}
elseif ($cat == "webdav")
{
  $pagecomp = $hcms_lang['webdav'][$lang];
}
else
{
  $pagecomp = $hcms_lang['file'][$lang];
}

// create secure token
$token_new = createtoken ($user);
?>
<!DOCTYPE html>
<html>
<head>
<title>hyperCMS</title>
<meta http-equiv="Content-Type" content="text/html; charset=<?php echo getcodepage ($lang); ?>">
<link rel="stylesheet" href="<?php echo getthemelocation(); ?>css/main.css" />
<script src="javascript/jquery/jquery-1.9.1.min.js" type="text/javascript"></script>
<script src="javascript/click.js" type="text/javascript"></script>
<script src="javascript/main.js" type="text/javascript"></script>
<script type="text/javascript" src="javascript/chat.js"></script>
<script type="text/javascript">
<!--
var locklayer = false;
var sidebar = <?php if ($temp_sidebar) echo "true"; else echo "false"; ?>;

function submitToWindow (url, action, windowname, features, width, height)
{
  if (eval (parent.frames['mainFrame'].document.forms['contextmenu_object']))
  {
    if (features == undefined) features = 'scrollbars=no,resizable=no';
    if (width == undefined) width = 400;
    if (height == undefined) height = 120;
    if (windowname == '') windowname = Math.floor(Math.random()*9999999);
    
    hcms_openWindow('', windowname, features, width, height);
    
    var form = parent.frames['mainFrame'].document.forms['contextmenu_object'];
    
    form.attributes['action'].value = url;
    form.elements['action'].value = action;
    form.elements['site'].value = '<?php echo $site; ?>';
    form.elements['cat'].value = '<?php echo $cat; ?>';
    form.elements['location'].value = '<?php echo $location_esc; ?>';
    form.elements['page'].value = '<?php echo $page; ?>';
    form.elements['pagename'].value = '<?php echo $pagename; ?>';
    form.elements['folder'].value = '<?php echo $folder; ?>';
    form.elements['force'].value = 'start';
    form.elements['token'].value = '<?php echo $token_new; ?>';
    form.target = windowname;
    form.submit();
  }
  else alert ('<?php echo $hcms_lang['please-close-the-search-window'][$lang]; ?>');
}

function submitToSelf (action)
{
  if (eval (parent.frames['mainFrame'].document.forms['contextmenu_object']))
  {
    var form = parent.frames['mainFrame'].document.forms['contextmenu_object'];
    
    form.attributes['action'].value = '<?php echo $_SERVER["PHP_SELF"]; ?>';
    form.elements['action'].value = action;
    form.elements['site'].value = '<?php echo $site; ?>';
    form.elements['cat'].value = '<?php echo $cat; ?>';
    form.elements['location'].value = '<?php echo $location_esc; ?>';
    form.elements['page'].value = '<?php echo $page; ?>';
    form.elements['pagename'].value = '<?php echo $pagename; ?>';
    form.elements['folder'].value = '<?php echo $folder; ?>';
    form.elements['media'].value = '<?php echo $media; ?>';
    form.elements['multiobject'].value = '<?php echo $multiobject; ?>';
    form.elements['filetype'].value = '<?php echo $filetype; ?>';
    form.elements['contexttype'].value = '<?php echo $contexttype; ?>';
    form.elements['force'].value = 'start';
    form.elements['token'].value = '<?php echo $token_new; ?>';
    form.target = 'controlFrame';
    form.submit();
  }
  else alert ('<?php echo $hcms_lang['please-close-the-search-window'][$lang]; ?>');
}

function checkForm_delete ()
{
  check = confirm ("<?php echo $hcms_lang['are-you-sure-you-want-to-remove-the-item'][$lang]; ?>");

  if (check == true)
  {    
    <?php 
    if ((isset ($multiobject) && $multiobject_count > 1) || (isset ($folder) && $folder != ""))
    {
      echo "submitToWindow('popup_status.php', 'delete', '', 'scrollbars=no,resizable=no', '400', '120');\n";
    }
    elseif (isset ($page) && $page != "" && $page != ".folder")
    {
      echo "hcms_openWindow('popup_action.php?site=".url_encode($site)."&cat=".url_encode($cat)."&action=delete&location=".url_encode($location_esc)."&page=".url_encode($page)."&multiobject=".url_encode($multiobject)."&token=".$token_new."','','scrollbars=no,resizable=no','400','120');\n";
    }
    ?>
  }
}

function checkForm_chars(text, exclude_chars)
{
	<?php if (isset ($mgmt_config[$site]['specialchr_disable']) && $mgmt_config[$site]['specialchr_disable']) { ?>
  exclude_chars = exclude_chars.replace(/[-[\]{}()*+?.,\\^$|#\s]/g, "\\$&");
  var expr = new RegExp ("[^a-zA-Z0-9" + exclude_chars + "]", "g");
  <?php } else { ?>
  var expr = new RegExp ('[,;/\\\\~`!@#$%^&:*?<>{}=|]', "g");
  <?php } ?>
	var separator = ', ';
	var found = text.match(expr); 
	
  if (found)
  {
		var addText = '';
    
		for(var i = 0; i < found.length; i++)
    {
			addText += found[i]+separator;
		}
    
		addText = addText.substr(0, addText.length-separator.length);
		alert (hcms_entity_decode("<?php echo $hcms_lang['please-do-not-use-the-following-special-characters'][$lang]; ?>: ") + addText);
		return false;
	}
  else
  {
		return true;
	}
}

function checkForm_folder_create()
{
  var form = document.forms['folder_create'];
  
  if (form.elements['foldernew'].value == "")
  {
    alert (hcms_entity_decode("<?php echo $hcms_lang['a-name-is-required'][$lang]; ?>"));
    form.elements['foldernew'].focus();
    return false;
  }
  
  if (!checkForm_chars(form.elements['foldernew'].value, ".-_"))
  {
    form.elements['foldernew'].focus();
    return false;
  }
  
  form.submit();
  return true;
}

function checkForm_folder_rename()
{
  var form = document.forms['folder_rename'];

  if (form.elements['foldernew'].value == "")
  {
    alert (hcms_entity_decode("<?php echo $hcms_lang['a-name-is-required'][$lang]; ?>"));
    form.elements['foldernew'].focus();
    return false;
  }
  
  if (!checkForm_chars(form.elements['foldernew'].value, ".-_"))
  {
    form.elements['foldernew'].focus();
    return false;
  }
  
  form.submit();
  return true;
}

function checkForm_page_rename()
{
  var form = document.forms['page_rename'];
  
  if (form.elements['pagenew'].value == "")
  {
    alert (hcms_entity_decode("<?php echo $hcms_lang['a-name-is-required'][$lang]; ?>"));
    form.elements['pagenew'].focus();
    return false;
  }
  
  if (!checkForm_chars(form.elements['pagenew'].value, ".-_"))
  {
    form.elements['pagenew'].focus();
    return false;
  }
  
  form.submit()
  return true;
}

function checkForm_zip()
{
  var form = document.forms['page_zip'];
  
  if (form.elements['pagenew'].value == "")
  {
    alert (hcms_entity_decode("<?php echo $hcms_lang['a-name-is-required'][$lang]; ?>"));
    form.elements['pagenew'].focus();
    return false;
  }
  
  if (!checkForm_chars(form.elements['pagenew'].value, ".-_"))    
  {
    form.elements['pagenew'].focus();
    return false;
  }
  
  form.submit();
  return true;
}

function docConvert (type)
{
  if (type != "" && eval (parent.frames['mainFrame'].document.forms['contextmenu_object']))
  {
    var form = parent.frames['mainFrame'].document.forms['contextmenu_object'];
    
    form.attributes['action'].value = '<?php echo $_SERVER["PHP_SELF"]; ?>';
    form.elements['convert_type'].value = type;
    
    submitToSelf ('download');
    
    return true;
  }
  else return false; 
}

function imgConvert (type, config)
{
  if (type != "" && config != "" && eval (parent.frames['mainFrame'].document.forms['contextmenu_object']))
  {
    var form = parent.frames['mainFrame'].document.forms['contextmenu_object'];
    
    form.attributes['action'].value = '<?php echo $_SERVER["PHP_SELF"]; ?>';
    form.elements['convert_type'].value = type;
    form.elements['convert_cfg'].value = config;
    
    
    submitToSelf ('download');
    
    return true;
  }
  else return false; 
}

function switchview (view)
{
  if (view == "large" || view == "medium" || view == "small" || view == "detail")
  {
    document.forms['memory'].elements['view'].value = view;
    
    // AJAX request to set view
    $.post("<?php echo $mgmt_config['url_path_cms']; ?>/service/toggleview.php", {view: view});
    
    // change view in object list
    if (eval (parent.frames['mainFrame']) && typeof parent.frames['mainFrame'].toggleview == 'function') parent.frames['mainFrame'].toggleview (view);
    
    // set icon
    document.getElementById('pic_obj_view').src="<?php echo getthemelocation(); ?>img/button_view_gallery_" + view + ".gif";  
    document.getElementById('select_obj_view').style.visibility = 'hidden';

    return true;
  }
  else return false;
}

function switchselector (id)
{
  if (eval (id))
  {
    var selector = document.getElementById(id);
    
    if (selector.style.visibility == 'hidden') selector.style.visibility = 'visible';
    else selector.style.visibility = 'hidden';
    
    return true;
  }
  else return false;
}

function switchsidebar ()
{  
  if (!sidebar) view = true;
  else view = false;
  
  if (view == true || view == false)
  {
    // AJAX request to set view
    $.post("<?php echo $mgmt_config['url_path_cms']; ?>/service/togglesidebar.php", {view: view});
    
    // change view in object list
    if (eval (parent.frames['mainFrame']) && eval (parent.frames['sidebarFrame']))
    {
      if (view)
      {
        parent.frames['sidebarFrame'].hcms_resizeFrameWidth('mainLayer', 75, 'sidebarLayer', 0, '%');
        sidebar = true;
      }
      else
      {
        parent.frames['sidebarFrame'].hcms_resizeFrameWidth('mainLayer', 100, 'sidebarLayer', 0, '%');
        sidebar = false;
      }
        
      // set sidebar variable in object list 
      parent.frames['mainFrame'].hcms_setGlobalVar('sidebar', sidebar);
    }

    return true;
  }
  else return false;
}

function switchfilter ()
{
  if (eval (document.getElementById('filterLayer')))
  {
    var filterset = document.getElementById('filterLayer');
    var form = document.forms['filter_set'];
    var elem = form.elements;
    var locked = false;

    for (var i=0; i<elem.length; i++)
    {
      if (elem[i].checked == true) locked = true;
    }

    if (filterset.style.visibility == 'hidden') filterset.style.visibility = 'visible';
    else if (locked == false) filterset.style.visibility = 'hidden';
     
    return true;
  }
  else return false;
}

function setfilter (filter)
{
  if (eval (document.forms['filter_set']))
  {
    var form = document.forms['filter_set'];
    var elem = form.elements;
    
    form.submit();
    return true;
  }
  else return false;
}

function escapevalue (value)
{
  if (value != "")
  {
    return encodeURIComponent (value);
  }
  else return "";
}

<?php if ($is_mobile && isset ($mgmt_config['chat']) && $mgmt_config['chat'] == true) { ?>
// start chat
var chat =  new Chat();

function sendtochat (text)
{
  if (text != "")
  {
    var username = '<?php echo $user; ?>';
    // strip tags
    username = username.replace(/(<([^>]+)>)/ig,"");
    chat.send(text, username);
  }
}
<?php } ?>
//-->
</script>
</head>

<body class="hcmsWorkplaceControlWallpaper" onLoad="<?php echo $add_onload; ?>">

<?php
// define location name
if ($cat == "page")
{
  $abs_path_root = $mgmt_config[$site]['abs_path_page'];      
  $location_name = getlocationname ($site, $location, $cat, "path");
}
elseif ($cat == "comp")
{
  $abs_path_root = $mgmt_config['abs_path_comp'];
  $location_name = getlocationname ($site, $location, $cat, "path");
}
else
{
  $abs_path_root = "";
  $location_name = "&nbsp;";
}

// define object name
if (($page != "" && $page != ".folder") || $multiobject )
{
  $item = $pagecomp.":";
  $object_name = $pagename;
}
elseif ($folder != "")
{
  $item = $hcms_lang['folder'][$lang].":";
  $object_name = specialchr_decode ($folder);
}
else
{
  $item = "&nbsp;";
  $object_name = "&nbsp;";
}
?>

<div class="hcmsLocationBar">
  <?php if (!$is_mobile) { ?>
  <table border=0 cellspacing=0 cellpadding=0>
    <tr>
      <?php
      // define location name
      if ($cat == "page" || $cat == "comp")
      {
        echo "    <td class=\"hcmsHeadline\" nowrap=\"nowrap\">".$hcms_lang['location'][$lang].": </td>
      <td class=\"hcmsHeadlineTiny\" nowrap=\"nowrap\">".$location_name."</td>\n";
      }
      else 
      {
        echo "    <td class=\"hcmsHeadline\" nowrap=\"nowrap\">".$hcms_lang['location'][$lang].": </td>
      <td class=\"hcmsHeadlineTiny\" nowrap=\"nowrap\">".$pagecomp."</td>\n";    
      }
      ?>
    </tr>
    <tr>
      <?php
      echo "    <td class=\"hcmsHeadline\" nowrap=\"nowrap\">".$item."</td>
      <td class=\"hcmsHeadlineTiny\" nowrap=\"nowrap\">".$object_name."</td>\n";
      ?>
    </tr>
  </table>
  <?php } else { ?>
  <span class="hcmsHeadlineTiny" style="display:block;"><?php echo $location_name.$object_name; ?></span>
  <?php } ?>
</div>

<!-- toolbar -->
<div class="hcmsToolbar">
  <?php if (!$is_mobile) { ?>
  <div class="hcmsToolbarBlock">
    <?php
    if (
         (
           !valid_locationname ($hcms_linking['location']) && valid_locationname ($abs_path_root) && valid_locationname ($location_down) && (@substr_count ($abs_path_root, $location) < 1 && $setlocalpermission_down['root'] == 1)
         ) 
         || 
         ( 
           valid_locationname ($hcms_linking['location']) && valid_locationname ($location) && substr_count ($hcms_linking['location'], $location) < 1
         )
       )
    {
       echo "<img ".
       "onClick=\"if (locklayer == false) parent.location.href='frameset_objectlist.php?site=".url_encode($site)."&cat=".url_encode($cat)."&location=".url_encode($location_down_esc)."';\" ".
       "class=\"hcmsButton hcmsButtonSizeSquare\" ".
       "name=\"pic_folder_back\" ".
       "src=\"".getthemelocation()."img/button_back.gif\" ".
       "alt=\"".$hcms_lang['go-to-parent-folder'][$lang]."\" title=\"".$hcms_lang['go-to-parent-folder'][$lang]."\" />\n";
    }
    else
    {
      echo "<img src=\"".getthemelocation()."img/button_back.gif\" class=\"hcmsButtonOff hcmsButtonSizeSquare\" />\n";
    }
    ?> 
  </div>
  <?php } ?>
  <div class="hcmsToolbarBlock">
    <?php
    // object list views
    if (!$is_mobile) $left = "65px";
    else $left = "35px";
    
    echo "
      <form name=\"memory\" style=\"display:none;\">
        <input name=\"view\" type=\"hidden\" value=\"".$temp_explorerview."\" />
      </form>
      <div onClick=\"switchselector('select_obj_view');\" class=\"hcmsButton hcmsButtonSizeWide\"><img src=\"".getthemelocation()."img/button_view_gallery_".$temp_explorerview.".gif\" style=\"padding:2px; width:18px; height:18px;\" id=\"pic_obj_view\" name=\"pic_obj_view\" alt=\"".$hcms_lang['thumbnail-gallery'][$lang]."\" title=\"".$hcms_lang['thumbnail-gallery'][$lang]."\" /><img src=\"".getthemelocation()."img/pointer_select.gif\" class=\" hcmsButtonSizeNarrow\" alt=\"".$hcms_lang['thumbnail-gallery'][$lang]."\" title=\"".$hcms_lang['thumbnail-gallery'][$lang]."\" /></div>
      <div id=\"select_obj_view\" class=\"hcmsSelector\" style=\"position:absolute; top:5px; left:".$left."; visibility:hidden; z-index:999; max-height:80px; overflow:auto; overflow-x:hidden; overflow-y:auto; white-space:nowrap;\">
        <div class=\"hcmsSelectorItem\" onclick=\"switchview ('large');\"><img src=\"".getthemelocation()."img/button_view_gallery_large.gif\" style=\"border:0; margin:0; padding:1px;\" align=\"absmiddle\" />".$hcms_lang['large-thumbnails'][$lang]."&nbsp;</div>
        <div class=\"hcmsSelectorItem\" onclick=\"switchview ('medium');\"><img src=\"".getthemelocation()."img/button_view_gallery_medium.gif\" style=\"border:0; padding:1px;\" align=\"absmiddle\" />".$hcms_lang['medium-thumbnails'][$lang]."&nbsp;</div>
        <div class=\"hcmsSelectorItem\" onclick=\"switchview ('small');\"><img src=\"".getthemelocation()."img/button_view_gallery_small.gif\" style=\"border:0; padding:1px;\" align=\"absmiddle\" />".$hcms_lang['small-thumbnails'][$lang]."&nbsp;</div>
        <div class=\"hcmsSelectorItem\" onclick=\"switchview ('detail');\"><img src=\"".getthemelocation()."img/button_view_gallery_detail.gif\" style=\"border:0; padding:1px;\" align=\"absmiddle\" />".$hcms_lang['details'][$lang]."&nbsp;</div>
      </div>\n";
    ?>
    
    <?php
    // sidebar
    if (!$is_mobile)
    {
        echo "
      <img src=\"".getthemelocation()."img/button_sidebar.gif\" class=\"hcmsButton hcmsButtonSizeSquare\" onclick=\"switchsidebar ();\" title=\"".$hcms_lang['preview-window'][$lang]."\" alt=\"".$hcms_lang['preview-window'][$lang]."\" />\n";
    }
    ?>
    
    <?php
    // filter
    if (!$is_mobile)
    {
      if ($from_page == "" && $cat != "page")
      {
        echo "
      <img src=\"".getthemelocation()."img/button_filter.gif\" class=\"hcmsButton hcmsButtonSizeSquare\" onclick=\"switchfilter();\" title=\"".$hcms_lang['filter-by-file-type'][$lang]."\" alt=\"".$hcms_lang['filter-by-file-type'][$lang]."\" />\n";
      }
      else
      {
        echo "
      <img src=\"".getthemelocation()."img/button_filter.gif\" class=\"hcmsButtonOff hcmsButtonSizeSquare\" />";
      }
    }
    ?>    
  </div>
  <div class="hcmsToolbarBlock">
    <?php
    // ZIP Button
    if ($hcms_linking['type'] != "Object" && $from_page == "" && $mgmt_compress['.zip'] != "" && 
        ($usedby == "" || $usedby == $user) && 
         $page != "" &&  
         $setlocalpermission['root'] == 1 && $cat != "page"
       )
    {
      echo "<img class=\"hcmsButton hcmsButtonSizeSquare\" ".
           "onClick=\"if (locklayer == false) hcms_showHideLayers(".
                                                      "'foldercreateLayer','','hide',".
                                                      "'folderrenameLayer','','hide',".
                                                      "'fileuploadLayer','','hide',".
                                                      "'objrenameLayer','','hide',".
                                                      "'hcms_messageLayer','','hide',".
                                                      "'zipLayer','','show'".
                                                      ");\" name=\"pic_zip_create\" src=\"".getthemelocation()."img/button_zip.gif\" alt=\"".$hcms_lang['compress-files'][$lang]."\" title=\"".$hcms_lang['compress-files'][$lang]."\" />\n";
    }
    else
    {
      echo "<img src=\"".getthemelocation()."img/button_zip.gif\" class=\"hcmsButtonOff hcmsButtonSizeSquare\" />\n";
    }
    
    // UNZIP Button
    if ( 
         $mgmt_uncompress['.zip'] != "" && $from_page == "" && 
         $multiobject_count <= 1 &&
         $filetype == "compressed" &&
         ($usedby == "" || $usedby == $user) && 
         $page != "" && 
         $setlocalpermission['root'] == 1
       )
    {
      echo "<img class=\"hcmsButton hcmsButtonSizeSquare\" ".
           "onClick=\"if (locklayer == false) hcms_openWindow('popup_action.php?action=unzip&site=".url_encode($site)."&cat=".url_encode($cat)."&location=".url_encode($location_esc)."&page=".url_encode($page)."&token=".$token_new."','','scrollbars=no,resizable=no','400','120');\" ".
           "name=\"pic_unzip\" src=\"".getthemelocation()."img/button_unzip.gif\" alt=\"".$hcms_lang['extract-file'][$lang]."\" title=\"".$hcms_lang['extract-file'][$lang]."\" />\n";
    }
    else
    {
      echo "<img src=\"".getthemelocation()."img/button_unzip.gif\" class=\"hcmsButtonOff hcmsButtonSizeSquare\" />\n";
    }
    ?>    
  </div>
  <div class="hcmsToolbarBlock">
    <?php
    // Preview Button
    if ($multiobject_count <= 1 && $page != "" && $cat != "" && $setlocalpermission['root'] == 1)
    {
      if ($page != ".folder") echo "<img onClick=\"hcms_openWindow('page_preview.php?site=".url_encode($site)."&cat=".url_encode($cat)."&location=".url_encode($location_esc)."&page=".url_encode($page)."','preview','scrollbars=yes,resizable=yes','800','600');\" class=\"hcmsButton hcmsButtonSizeSquare\" name=\"pic_obj_preview\" src=\"".getthemelocation()."img/button_file_preview.gif\" alt=\"".$hcms_lang['preview'][$lang]."\" title=\"".$hcms_lang['preview'][$lang]."\" />\n";
      elseif ($page == ".folder") echo "<img onClick=\"hcms_openWindow('page_preview.php?site=".url_encode($site)."&cat=".url_encode($cat)."&location=".url_encode($location_esc.$folder)."/&page=.folder','preview','scrollbars=yes,resizable=yes','800','600');\" class=\"hcmsButton hcmsButtonSizeSquare\" name=\"pic_obj_preview\" src=\"".getthemelocation()."img/button_file_preview.gif\" alt=\"".$hcms_lang['preview'][$lang]."\" title=\"".$hcms_lang['preview'][$lang]."\" />\n";
    }
    else
    {echo "<img src=\"".getthemelocation()."img/button_file_preview.gif\" class=\"hcmsButtonOff hcmsButtonSizeSquare\" />\n";}

    // define path varibales
    if ($site != "" && $media == "")
    {
      if ($cat == "page") $url_location = str_ireplace ($mgmt_config[$site]['abs_path_page'], $publ_config['url_publ_page'], $location).$page;
      elseif ($cat == "comp") $url_location = str_ireplace ($mgmt_config['abs_path_comp'], $publ_config['url_publ_comp'], $location).$page;
    }
    elseif ($media != "") $url_location = $mgmt_config['url_path_cms']."explorer_wrapper.php?site=".url_encode($site)."&name=".url_encode($pagename)."&media=".url_encode($site."/".$media)."&token=".hcms_crypt($site."/".$media);
    
    // LiveView Button
    if ($multiobject_count <= 1 && 
        $file_info['published'] == true && $page != ".folder" && $page != "" && 
        (($setlocalpermission['root'] == 1 && (($cat != "comp" && $media == "") || ($media != "" && $setlocalpermission['download'] == 1))))
    )
    {echo "<img onClick=\"hcms_openWindow('".$url_location."','preview','location=yes,status=yes,menubar=yes,scrollbars=yes,resizable=yes','800','600');\" class=\"hcmsButton hcmsButtonSizeSquare\" name=\"pic_obj_liveview\" src=\"".getthemelocation()."img/button_file_liveview.gif\" alt=\"".$hcms_lang['view-live'][$lang]."\" title=\"".$hcms_lang['view-live'][$lang]."\" />\n";}
    else
    {echo "<img src=\"".getthemelocation()."img/button_file_liveview.gif\" class=\"hcmsButtonOff hcmsButtonSizeSquare\" />\n";}
    
    // Download/Convert Button (also for folders) 
    // get media file extension
    $media_info = getfileinfo ($site, $media, $cat);
    
    $doc_rendering = false;
    $img_rendering = false;
    
    foreach ($mgmt_docpreview as $docpreview_ext => $docpreview)
    {
      // check file extension
      if (substr_count ($docpreview_ext.".", $media_info['ext'].".") > 0 ) $doc_rendering = true;      
    }
    
    $doc_rendering = $doc_rendering && is_array($mgmt_docconvert) && array_key_exists($media_info['ext'], $mgmt_docconvert);
    
    foreach ($mgmt_imagepreview as $imgpreview_ext => $imgpreview)
    {
      // check file extension
      if (substr_count ($imgpreview_ext.".", $media_info['ext'].".") > 0 )
  		{
  			// check if there are more options for providing the image in other formats
  			if (is_array ($mgmt_imageoptions) && !empty($mgmt_imageoptions))
  			{	
  				foreach ($mgmt_imageoptions as $config_fileext => $config_array) 
  				{
  					foreach ($config_array as $config_name => $config_parameter) 
  					{
  						if ($config_name != "thumbnail" && $config_name != "original") 
  						{
  							$img_rendering = true;
  							break 3;
  						}
  					}	
  				}
  			}
  		}      
    }
    
    // rendering options
    $perm_rendering = $setlocalpermission['root'] == 1 && $setlocalpermission['download'] == 1;
    $lock_rendering = ($usedby == "" || $usedby == $user);
    $dropbox_rendering = (is_array ($mgmt_config) && array_key_exists ("dropbox_appkey", $mgmt_config) && !empty ($mgmt_config['dropbox_appkey']));
    
    if (!$is_mobile) $left = "262px";
    else $left = "227px";
    
    if ($multiobject_count <= 1 && $perm_rendering && $lock_rendering && $page != "" && $media != "" && ($doc_rendering || $img_rendering || $dropbox_rendering))
    {
      echo "<div class=\"hcmsButton hcmsButtonSizeWide\" onClick=\"switchselector ('select_obj_convert');\"><img src=\"".getthemelocation()."img/button_file_download.gif\" class=\"hcmsButtonTinyBlank hcmsButtonSizeSquare\" id=\"pic_obj_convert\" name=\"pic_obj_convert\" alt=\"".$hcms_lang['download-file'][$lang]."\" title=\"".$hcms_lang['download-file'][$lang]."\" /><img src=\"".getthemelocation()."img/pointer_select.gif\" class=\"hcmsButtonTinyBlank hcmsButtonSizeNarrow\" alt=\"".$hcms_lang['download-file'][$lang]."\" title=\"".$hcms_lang['download-file'][$lang]."\" /></div>
        <div id=\"select_obj_convert\" class=\"hcmsSelector\" style=\"position:absolute; top:5px; left:".$left."; visibility:hidden; z-index:999; max-height:80px; overflow:auto; overflow-x:hidden; overflow-y:auto; white-space:nowrap;\">\n
        <div class=\"hcmsSelectorItem\" onclick=\"submitToSelf('download');\"><img src=\"".getthemelocation()."img/".$media_info['icon']."\" style=\"border:0; margin:0; padding:0;\" align=\"absmiddle\" />".$hcms_lang['original'][$lang]."&nbsp;</div>\n";
      
      // document download options
      if ($doc_rendering)
      {
        if (!empty ($mgmt_docoptions['.pdf']) && in_array('.pdf', $mgmt_docconvert[$media_info['ext']])) echo "        <div class=\"hcmsSelectorItem\" onclick=\"docConvert ('pdf');\"><img src=\"".getthemelocation()."img/file_pdf.gif\" style=\"border:0; margin:0; padding:1px;\" align=\"absmiddle\" />".$hcms_lang['pdf'][$lang]."&nbsp;</div>\n";
        if (!empty ($mgmt_docoptions['.doc']) && in_array('.doc', $mgmt_docconvert[$media_info['ext']])) echo "        <div class=\"hcmsSelectorItem\" onclick=\"docConvert ('doc');\"><img src=\"".getthemelocation()."img/file_doc.gif\" style=\"border:0; margin:0; padding:1px;\" align=\"absmiddle\" />".$hcms_lang['word'][$lang]."&nbsp;</div>\n";
        if (!empty ($mgmt_docoptions['.csv']) && in_array('.csv', $mgmt_docconvert[$media_info['ext']])) echo "        <div class=\"hcmsSelectorItem\" onclick=\"docConvert ('csv');\"><img src=\"".getthemelocation()."img/file_csv.gif\" style=\"border:0; margin:0; padding:1px;\" align=\"absmiddle\" />".$hcms_lang['csv'][$lang]."&nbsp;</div>\n";
        if (!empty ($mgmt_docoptions['.xls']) && in_array('.xls', $mgmt_docconvert[$media_info['ext']])) echo "        <div class=\"hcmsSelectorItem\" onclick=\"docConvert ('xls');\"><img src=\"".getthemelocation()."img/file_xls.gif\" style=\"border:0; margin:0; padding:1px;\" align=\"absmiddle\" />".$hcms_lang['excel'][$lang]."&nbsp;</div>\n";
        if (!empty ($mgmt_docoptions['.ppt']) && in_array('.ppt', $mgmt_docconvert[$media_info['ext']])) echo "        <div class=\"hcmsSelectorItem\" onclick=\"docConvert ('ppt');\"><img src=\"".getthemelocation()."img/file_ppt.gif\" style=\"border:0; margin:0; padding:1px;\" align=\"absmiddle\" />".$hcms_lang['power-point'][$lang]."&nbsp;</div>\n";
        if (!empty ($mgmt_docoptions['.html']) && in_array('.html', $mgmt_docconvert[$media_info['ext']])) echo "        <div class=\"hcmsSelectorItem\" onclick=\"docConvert ('html');\"><img src=\"".getthemelocation()."img/file_htm.gif\" style=\"border:0; margin:0; padding:1px;\" align=\"absmiddle\" />".$hcms_lang['webpage'][$lang]."&nbsp;</div>\n";
        if (!empty ($mgmt_docoptions['.odt']) && in_array('.odt', $mgmt_docconvert[$media_info['ext']])) echo "        <div class=\"hcmsSelectorItem\" onclick=\"docConvert ('odt');\"><img src=\"".getthemelocation()."img/file_odt.gif\" style=\"border:0; margin:0; padding:1px;\" align=\"absmiddle\" />".$hcms_lang['open-office-text'][$lang]."&nbsp;</div>\n";
        if (!empty ($mgmt_docoptions['.ods']) && in_array('.ods', $mgmt_docconvert[$media_info['ext']])) echo "        <div class=\"hcmsSelectorItem\" onclick=\"docConvert ('ods');\"><img src=\"".getthemelocation()."img/file_ods.gif\" style=\"border:0; margin:0; padding:1px;\" align=\"absmiddle\" />".$hcms_lang['open-office-spreadsheet'][$lang]."&nbsp;</div>\n";
        if (!empty ($mgmt_docoptions['.odp']) && in_array('.odp', $mgmt_docconvert[$media_info['ext']])) echo "        <div class=\"hcmsSelectorItem\" onclick=\"docConvert ('odp');\"><img src=\"".getthemelocation()."img/file_odp.gif\" style=\"border:0; margin:0; padding:1px;\" align=\"absmiddle\" />".$hcms_lang['open-office-presentation'][$lang]."&nbsp;</div>\n";
      }
      
      // image download options
      if ($img_rendering)
      {
        foreach ($mgmt_imageoptions as $config_fileext => $config_array) 
        {
          if (is_array ($config_array)) 
          {
            $tmpname = explode (".", $config_fileext);
            $showname = strtoupper ($tmpname[1]);
            $img_info = getfileinfo ($site, $media_info['filename'].".".$tmpname[1], $cat);
            
            foreach ($config_array as $config_name => $config_parameter) 
            {
              if ($config_name != "thumbnail" && $config_name != "original") 
              {
                echo "        <div class=\"hcmsSelectorItem\" onclick=\"imgConvert ('".$tmpname[1]."', '".$config_name."');\"><img src=\"".getthemelocation()."img/".$img_info['icon']."\" style=\"border:0; margin:0; padding:1px;\" align=\"absmiddle\" />".$showname." ".$config_name."&nbsp;</div>\n";
              }
            }
          }
        }
      }
			
			//save to dropbox
			if ($dropbox_rendering)
			{
				echo "<div class=\"hcmsSelectorItem\" onclick=\"submitToWindow('popup_save_dropbox.php', 'Save to Dropbox', '', 'status=yes,scrollbars=yes,resizable=yes,width=600,height=400', '600', '400');\"><img src=\"".getthemelocation()."img/file_dropbox.png\" style=\"border:0; margin:0; padding:1px;\" align=\"absmiddle\" />".$hcms_lang['dropbox'][$lang]."&nbsp;</div>\n";
			}
      
      echo "    </div>\n";
    }
    // folder/file download without options
    elseif ($perm_rendering && $lock_rendering && ($media != "" || $page == ".folder" || $multiobject_count > 1) && $page != "")
    {
      echo "<div class=\"hcmsButton hcmsButtonSizeWide\" onClick=\"submitToSelf('download');\">".
      "<img class=\"hcmsButtonTinyBlank hcmsButtonSizeSquare\" name=\"pic_obj_liveview\" src=\"".getthemelocation()."img/button_file_download.gif\" alt=\"".$hcms_lang['download-file'][$lang]."\" title=\"".$hcms_lang['download-file'][$lang]."\" /><img src=\"".getthemelocation()."img/pointer_select.gif\" class=\"hcmsButtonTinyBlank hcmsButtonSizeNarrow\" alt=\"".$hcms_lang['download-file'][$lang]."\" title=\"".$hcms_lang['download-file'][$lang]."\" /></div>\n";
    }
    else
    {
      echo "<div class=\"hcmsButtonOff hcmsButtonSizeWide\"><img src=\"".getthemelocation()."img/button_file_download.gif\" class=\"hcmsButtonSizeSquare\" /><img src=\"".getthemelocation()."img/pointer_select.gif\" class=\"hcmsButtonSizeNarrow\" alt=\"".$hcms_lang['download-file'][$lang]."\" title=\"".$hcms_lang['download-file'][$lang]."\" /></div>\n";
    }

    // Edit Button   
    if (
         $multiobject_count <= 1 && 
         $page != "" && 
         (($media == "" && $setlocalpermission['root'] == 1 && $setlocalpermission['create'] == 1) || 
         ($media != "" && $setlocalpermission['root'] == 1 && $setlocalpermission['upload'] == 1))
    )
    {
      if ($page != ".folder") echo "<img onClick=\"hcms_openWindow('frameset_content.php?site=".url_encode($site)."&ctrlreload=yes&cat=".url_encode($cat)."&location=".url_encode($location_esc)."&page=".url_encode($page)."','".$container_id."','status=yes,scrollbars=no,resizable=yes','800','600');\"\" class=\"hcmsButton hcmsButtonSizeSquare\" name=\"pic_obj_edit\" src=\"".getthemelocation()."img/button_file_edit.gif\" alt=\"".$hcms_lang['edit'][$lang]."\" title=\"".$hcms_lang['edit'][$lang]."\" />\n";
      elseif ($page == ".folder") echo "<img onClick=\"hcms_openWindow('frameset_content.php?site=".url_encode($site)."&ctrlreload=yes&cat=".url_encode($cat)."&location=".url_encode($location_esc.$folder)."/&page=".url_encode($page)."','".$container_id."','status=yes,scrollbars=no,resizable=yes','800','600');\"\" class=\"hcmsButton hcmsButtonSizeSquare\" name=\"pic_obj_edit\" src=\"".getthemelocation()."img/button_file_edit.gif\" alt=\"".$hcms_lang['edit'][$lang]."\" title=\"".$hcms_lang['edit'][$lang]."\" />\n";
    }
    // Edit button to edit the fileds which are equal across all selected files
    elseif ($multiobject_count > 1)
    {
      echo "<img onClick=\"submitToWindow('page_multiedit.php','', 'multiedit','status=yes,scrollbars=yes,resizable=yes','800','600');\"\" class=\"hcmsButton hcmsButtonSizeSquare\" name=\"pic_obj_edit\" src=\"".getthemelocation()."img/button_file_edit.gif\" alt=\"".$hcms_lang['edit'][$lang]."\" title=\"".$hcms_lang['edit'][$lang]."\" />\n";
    }
    else 
    {
      echo "<img src=\"".getthemelocation()."img/button_file_edit.gif\" class=\"hcmsButtonOff hcmsButtonSizeSquare\" />\n";
    }
    ?>    
  </div>
  <div class="hcmsToolbarBlock">       
    <?php    
    // SendMail Button
    if ($page != "" && $mgmt_config[$site]['sendmail'] && $setlocalpermission['root'] == 1 && $setlocalpermission['sendlink'] == 1 && $mgmt_config['db_connect_rdbms'] != "")
    {
      echo "<img onClick=\"submitToWindow('user_sendlink.php', '', 'sendlink', 'scrollbars=yes,resizable=no','600','680');\" ".
      "class=\"hcmsButton hcmsButtonSizeSquare\" name=\"pic_obj_preview\" ".
      "src=\"".getthemelocation()."img/button_user_sendlink.gif\" ".
      "alt=\"".$hcms_lang['send-mail-link'][$lang]."\" title=\"".$hcms_lang['send-mail-link'][$lang]."\" />\n";
    }
    else
    {
      echo "<img src=\"".getthemelocation()."img/button_user_sendlink.gif\" class=\"hcmsButtonOff hcmsButtonSizeSquare\" />\n";
    }

    // Send to Chat Button
    if ($is_mobile && $page != "" && $setlocalpermission['root'] == 1 && isset ($mgmt_config['chat']) && $mgmt_config['chat'] == true)
    {
      if ($page != ".folder") $chatcontent = "hcms_openWindow(\\'frameset_content.php?site=".url_encode($site)."&ctrlreload=yes&cat=".url_encode($cat)."&location=".url_encode($location_esc)."&page=".url_encode($page)."\\', \\'\\', \\'status=yes,scrollbars=no,resizable=yes\\', \\'800\\', \\'600\\');";
      elseif ($page == ".folder") $chatcontent = "hcms_openWindow(\\'frameset_content.php?site=".url_encode($site)."&ctrlreload=yes&cat=".url_encode($cat)."&location=".url_encode($location_esc.$folder)."/&page=".url_encode($page)."\\', \\'\\', \\'status=yes,scrollbars=no,resizable=yes\\', \\'800\\', \\'600\\');";
      
      echo "<img onClick=\"sendtochat('".$chatcontent."');\" ".
      "class=\"hcmsButton hcmsButtonSizeSquare\" name=\"pic_obj_chat\" ".
      "src=\"".getthemelocation()."img/button_chat.gif\" ".
      "alt=\"".$hcms_lang['click-here-to-viewdownload-the-file'][$lang]."\" title=\"".$hcms_lang['click-here-to-viewdownload-the-file'][$lang]."\" />\n";
    }
    elseif ($is_mobile)
    {
      echo "<img src=\"".getthemelocation()."img/button_chat.gif\" class=\"hcmsButtonOff hcmsButtonSizeSquare\" />\n";
    }
    ?>
  </div>
  <div class="hcmsToolbarBlock">
    <?php
    // New Folder Button
    if ($hcms_linking['type'] != "Object" && $from_page == "" && $setlocalpermission['root'] == 1 && $setlocalpermission['foldercreate'] == 1)
    {
      echo "<img class=\"hcmsButton hcmsButtonSizeSquare\" ".
           "onClick=\"if (locklayer == false) hcms_showHideLayers(".
                                                      "'foldercreateLayer','','show',".
                                                      "'folderrenameLayer','','hide',".
                                                      "'fileuploadLayer','','hide',".
                                                      "'objrenameLayer','','hide',".
                                                      "'hcms_messageLayer','','hide'".
                                                      ");\" name=\"pic_folder_create\" src=\"".getthemelocation()."img/button_folder_new.gif\" alt=\"".$hcms_lang['create-folder'][$lang]."\" title=\"".$hcms_lang['create-folder'][$lang]."\" />\n";
    }
    else
    {
      echo "<img src=\"".getthemelocation()."img/button_folder_new.gif\" class=\"hcmsButtonOff hcmsButtonSizeSquare\" />\n";
    }
    // Rename Folder Button
    if ($multiobject_count <= 1 && $folder != "" && $setlocalpermission['root'] == 1 && $setlocalpermission['folderrename'] == 1)
    {echo "<img class=\"hcmsButton hcmsButtonSizeSquare\" onClick=\"if (locklayer == false) hcms_showHideLayers('foldercreateLayer','','hide','folderrenameLayer','','show','fileuploadLayer','','hide','objrenameLayer','','hide','hcms_messageLayer','','hide');\" name=\"pic_folder_rename\" src=\"".getthemelocation()."img/button_folder_rename.gif\" alt=\"".$hcms_lang['rename-folder'][$lang]."\" title=\"".$hcms_lang['rename-folder'][$lang]."\" />\n";}
    else
    {echo "<img src=\"".getthemelocation()."img/button_folder_rename.gif\" class=\"hcmsButtonOff hcmsButtonSizeSquare\" />\n";}
    ?>    
  </div>
  <div class="hcmsToolbarBlock">
    <?php
    // New Object Button
    if ($hcms_linking['type'] != "Object" && $from_page == "" && $setlocalpermission['root'] == 1 && $setlocalpermission['create'] == 1)
    {echo "<img class=\"hcmsButton hcmsButtonSizeSquare\" onClick=\"hcms_openWindow('frameset_content.php?site=".url_encode($site)."&cat=".url_encode($cat)."&location=".url_encode($location_esc)."','','status=yes,scrollbars=no,resizable=yes,width=800,height=600','800','600');\" name=\"pic_obj_new\" src=\"".getthemelocation()."img/button_file_new.gif\" alt=\"".$hcms_lang['new-object'][$lang]."\" title=\"".$hcms_lang['new-object'][$lang]."\" />\n";}
    else
    {echo "<img src=\"".getthemelocation()."img/button_file_new.gif\" class=\"hcmsButtonOff hcmsButtonSizeSquare\" />";}

    // Upload Button
    if ($html5file) $popup_upload = "popup_upload_html.php";
    else $popup_upload = "popup_upload_swf.php";
    
    if ($hcms_linking['type'] != "Object" && $from_page == "" && $cat != "page" && $setlocalpermission['root'] == 1 && $setlocalpermission['upload'] == 1)
    {echo "<img class=\"hcmsButton hcmsButtonSizeSquare\" onClick=\"hcms_openWindow('".$popup_upload."?uploadmode=multi&site=".url_encode($site)."&cat=".url_encode($cat)."&location=".url_encode($location_esc)."','','status=yes,scrollbars=yes,resizable=yes,width=600,height=400','600','400');\" name=\"pic_obj_upload\" src=\"".getthemelocation()."img/button_file_upload.gif\" alt=\"".$hcms_lang['upload-file'][$lang]."\" title=\"".$hcms_lang['upload-file'][$lang]."\" />\n";}
    else
    {echo "<img src=\"".getthemelocation()."img/button_file_upload.gif\" class=\"hcmsButtonOff hcmsButtonSizeSquare\" />\n";}

    // RenameObject Button
    if ($multiobject_count <=1 && $page != "" && $page != ".folder" && $setlocalpermission['root'] == 1 && $setlocalpermission['rename'] == 1)
    {echo "<img class=\"hcmsButton hcmsButtonSizeSquare\" onClick=\"hcms_showHideLayers('foldercreateLayer','','hide','folderrenameLayer','','hide','fileuploadLayer','','hide','objrenameLayer','','show','hcms_messageLayer','','hide')\" name=\"pic_obj_rename\" src=\"".getthemelocation()."img/button_file_rename.gif\" alt=\"".$hcms_lang['rename'][$lang]."\" title=\"".$hcms_lang['rename'][$lang]."\" />\n";}
    else
    {echo "<img src=\"".getthemelocation()."img/button_file_rename.gif\" class=\"hcmsButtonOff hcmsButtonSizeSquare\" />\n";}
    ?>    
  </div>
  <div class="hcmsToolbarBlock">
    <?php
    // Delete Button
    if (
         ($usedby == "" || $usedby == $user) && 
         (
           ($page != "" && $page != ".folder" && $setlocalpermission['root'] == 1 && $setlocalpermission['delete'] == 1) || 
           ($folder != "" && $setlocalpermission['root'] == 1 && $setlocalpermission['folderdelete'] == 1)
         )
       )
    {echo "<img onClick=\"if (locklayer == false) checkForm_delete();\" ".
           "class=\"hcmsButton hcmsButtonSizeSquare\" name=\"pic_obj_delete\" src=\"".getthemelocation()."img/button_delete.gif\" alt=\"".$hcms_lang['delete'][$lang]."\" title=\"".$hcms_lang['delete'][$lang]."\" />";}
    else
    {echo "<img src=\"".getthemelocation()."img/button_delete.gif\" class=\"hcmsButtonOff hcmsButtonSizeSquare\" />\n";}    
    ?>
    
    <?php
    if ($page != "" && $page != ".folder")
    {
      if ($hcms_linking['type'] != "Object" && $setlocalpermission['root'] == 1 && $setlocalpermission['rename'] == 1)
      {echo "<img onClick=\"if (locklayer == false) submitToWindow('popup_action.php', 'cut', '');\" ".
                     "class=\"hcmsButton hcmsButtonSizeSquare\" ".
                     "name=\"pic_obj_cut\" ".
                     "src=\"".getthemelocation()."img/button_file_cut.gif\" alt=\"".$hcms_lang['cut'][$lang]."\" title=\"".$hcms_lang['cut'][$lang]."\" />\n";}
      else
      {echo "<img src=\"".getthemelocation()."img/button_file_cut.gif\" class=\"hcmsButtonOff hcmsButtonSizeSquare\" />";}
  
      if ($hcms_linking['type'] != "Object" && $setlocalpermission['root'] == 1 && $setlocalpermission['rename'] == 1)
      {echo "<img onClick=\"if (locklayer == false) submitToWindow('popup_action.php', 'copy', '');\" ".
                     "class=\"hcmsButton hcmsButtonSizeSquare\" ".
                     "name=\"pic_obj_copy\" ".
                     "src=\"".getthemelocation()."img/button_file_copy.gif\" alt=\"".$hcms_lang['copy'][$lang]."\" title=\"".$hcms_lang['copy'][$lang]."\" />\n";}
      else
      {echo "<img src=\"".getthemelocation()."img/button_file_copy.gif\" class=\"hcmsButtonOff hcmsButtonSizeSquare\">\n";}
      
      if ($hcms_linking['type'] != "Object" && $setlocalpermission['root'] == 1 && $setlocalpermission['rename'] == 1)
      {echo "<img onClick=\"if (locklayer == false) submitToWindow('popup_action.php', 'linkcopy', '');\" ".
                     "class=\"hcmsButton hcmsButtonSizeSquare\" ".
                     "name=\"pic_obj_linkedcopy\" ".
                     "src=\"".getthemelocation()."img/button_file_copylinked.gif\" alt=\"".$hcms_lang['connected-copy'][$lang]."\" title=\"".$hcms_lang['connected-copy'][$lang]."\">\n";}
      else
      {echo "<img src=\"".getthemelocation()."img/button_file_copylinked.gif\" class=\"hcmsButtonOff hcmsButtonSizeSquare\" />\n";}    
    }
    elseif ($folder != "")
    {
      if ($setlocalpermission['root'] == 1 && $setlocalpermission['folderrename'] == 1)
      {echo "<img onClick=\"if (locklayer == false) submitToWindow('popup_action.php', 'cut', '');\" ".
                     "class=\"hcmsButton hcmsButtonSizeSquare\" name=\"pic_obj_cut\" src=\"".getthemelocation()."img/button_file_cut.gif\" alt=\"".$hcms_lang['cut'][$lang]."\" title=\"".$hcms_lang['cut'][$lang]."\" /></a>\n";}
      else
      {echo "<img src=\"".getthemelocation()."img/button_file_cut.gif\" class=\"hcmsButtonOff hcmsButtonSizeSquare\" />";}
  
      if ($setlocalpermission['root'] == 1 && $setlocalpermission['folderrename'] == 1)
      {echo "<img onClick=\"if (locklayer == false) submitToWindow('popup_action.php', 'copy', '');\" ".
                     "class=\"hcmsButton hcmsButtonSizeSquare\" name=\"pic_obj_copy\" src=\"".getthemelocation()."img/button_file_copy.gif\" alt=\"".$hcms_lang['copy'][$lang]."\" title=\"".$hcms_lang['copy'][$lang]."\" /></a>\n";}
      else
      {echo "<img src=\"".getthemelocation()."img/button_file_copy.gif\" class=\"hcmsButtonOff hcmsButtonSizeSquare\" />\n";}
      
      if ($setlocalpermission['root'] == 1 && $setlocalpermission['folderrename'] == 1 && $setlocalpermission['foldercreate'] == 1)
      {echo "<img onClick=\"if (locklayer == false) submitToWindow('popup_action.php', 'linkcopy', '');\" ".
                     "class=\"hcmsButton hcmsButtonSizeSquare\" name=\"pic_obj_linkedcopy\" src=\"".getthemelocation()."img/button_file_copylinked.gif\" alt=\"".$hcms_lang['connected-copy'][$lang]."\" title=\"".$hcms_lang['connected-copy'][$lang]."\" />\n";}
      else
      {echo "<img src=\"".getthemelocation()."img/button_file_copylinked.gif\" class=\"hcmsButtonOff hcmsButtonSizeSquare\" />\n";}      
    }
    else
    {
      echo "<img src=\"".getthemelocation()."img/button_file_cut.gif\" class=\"hcmsButtonOff hcmsButtonSizeSquare\" />";
      echo "<img src=\"".getthemelocation()."img/button_file_copy.gif\" class=\"hcmsButtonOff hcmsButtonSizeSquare\" />\n";
      echo "<img src=\"".getthemelocation()."img/button_file_copylinked.gif\" class=\"hcmsButtonOff hcmsButtonSizeSquare\" />\n";
    }
     // Paste Button
    if ($hcms_linking['type'] != "Object" && $from_page == "" && ($setlocalpermission['root'] == 1 && ($setlocalpermission['rename'] == 1 || $setlocalpermission['folderrename'] == 1)))
    {echo "<img onClick=\"if (locklayer == false) submitToWindow('popup_status.php', 'paste', '');\" ".
                   "class=\"hcmsButton hcmsButtonSizeSquare\" name=\"pic_obj_paste\" src=\"".getthemelocation()."img/button_file_paste.gif\" alt=\"".$hcms_lang['paste'][$lang]."\" title=\"".$hcms_lang['paste'][$lang]."\" />\n";}
    else
    {echo "<img src=\"".getthemelocation()."img/button_file_paste.gif\" class=\"hcmsButtonOff hcmsButtonSizeSquare\" />\n";}    
    ?>    
  </div>
  <div class="hcmsToolbarBlock">
    <?php
    // un/publish object
    if ($page != "" && $page != ".folder")
    {
      if (($filetype != "" || $multiobject != "") && $setlocalpermission['root'] == 1 && $setlocalpermission['publish'] == 1)
      {
        echo "<img ".
        "onClick=\"if (locklayer == false) ";
        if ($mgmt_config['db_connect_rdbms'] != "") echo "submitToWindow('popup_publish.php', 'publish', '', 'scrollbars=no,resizable=no', '400', '320');";
        else echo "submitToWindow('popup_status.php', 'publish', '', 'scrollbars=no,resizable=no', '400', '320');";
        echo "\" ".
        "class=\"hcmsButton hcmsButtonSizeSquare\" name=\"pic_obj_publish\" src=\"".getthemelocation()."img/button_file_publish.gif\" alt=\"".$hcms_lang['publish'][$lang]."\" title=\"".$hcms_lang['publish'][$lang]."\" />\n";
      }
      else
      {echo "<img src=\"".getthemelocation()."img/button_file_publish.gif\" class=\"hcmsButtonOff hcmsButtonSizeSquare\" />\n";}
  
      if (($filetype != "" || $multiobject != "") && $setlocalpermission['root'] == 1 && $setlocalpermission['publish'] == 1)
      {
      	echo "<img onClick=\"if (locklayer == false) ";
        if ($mgmt_config['db_connect_rdbms'] != "") echo "submitToWindow('popup_publish.php', 'unpublish', '', 'scrollbars=no,resizable=no', '400', '320');";
        else echo "submitToWindow('popup_status.php', 'unpublish', '', 'scrollbars=no,resizable=no', '400', '320');";   		
      	echo "\" ".
      	"class=\"hcmsButton hcmsButtonSizeSquare\" ".
      	"name=\"pic_obj_unpublish\" ".
      	"src=\"".getthemelocation()."img/button_file_unpublish.gif\" alt=\"".$hcms_lang['unpublish'][$lang]."\" title=\"".$hcms_lang['unpublish'][$lang]."\" />\n";
      }
      else
      {
      	echo "<img src=\"".getthemelocation()."img/button_file_unpublish.gif\" class=\"hcmsButtonOff hcmsButtonSizeSquare\" />\n";
      }
    }
    // un/publish folder
    elseif ($folder != "")
    {
      if ($setlocalpermission_ACCESS['root'] == 1 && $setlocalpermission_ACCESS['publish'] == 1)
      {
        echo "<img onClick=\"if (locklayer == false) ";
        if ($mgmt_config['db_connect_rdbms'] != "") echo "submitToWindow('popup_publish.php', 'publish', '', 'scrollbars=no,resizable=no', '400', '320');";
        else echo "submitToWindow('popup_status.php', 'publish', '', 'scrollbars=no,resizable=no', '400', '320');"; 
        echo "\" ".
        "class=\"hcmsButton hcmsButtonSizeSquare\" name=\"pic_obj_publish\" src=\"".getthemelocation()."img/button_file_publish.gif\" alt=\"".$hcms_lang['publish'][$lang]."\" title=\"".$hcms_lang['publish'][$lang]."\" />\n";
      }
      else
      {
        echo "<img src=\"".getthemelocation()."img/button_file_publish.gif\" class=\"hcmsButtonOff hcmsButtonSizeSquare\" />\n";
      }
  
      if ($setlocalpermission_ACCESS['root'] == 1 && $setlocalpermission_ACCESS['publish'] == 1)
      {
      	echo "<img onClick=\"if (locklayer == false) ";
      	if ($mgmt_config['db_connect_rdbms'] != "") echo "submitToWindow('popup_publish.php', 'unpublish', '', 'scrollbars=no,resizable=no', '400', '320');";
        else echo "submitToWindow('popup_status.php', 'unpublish', '', 'scrollbars=no,resizable=no', '400', '320');";
      	echo "\" class=\"hcmsButton hcmsButtonSizeSquare\" name=\"pic_obj_unpublish\" src=\"".getthemelocation()."img/button_file_unpublish.gif\" alt=\"".$hcms_lang['unpublish'][$lang]."\" title=\"".$hcms_lang['unpublish'][$lang]."\">\n";
      }
      else
      {
        echo "<img src=\"".getthemelocation()."img/button_file_unpublish.gif\" class=\"hcmsButtonOff hcmsButtonSizeSquare\" />\n";
      }    
    }
    // deactivate buttons
    else
    {
      echo "<img src=\"".getthemelocation()."img/button_file_publish.gif\" class=\"hcmsButtonOff hcmsButtonSizeSquare\" />\n";
      echo "<img src=\"".getthemelocation()."img/button_file_unpublish.gif\" class=\"hcmsButtonOff hcmsButtonSizeSquare\" />\n";
    }
    ?>    
  </div>
  <?php if (!$is_mobile) { ?>
  <div class="hcmsToolbarBlock">
    <?php
    // if link references to an object and not a folder, disable search
    if ((!is_array ($hcms_linking) || $hcms_linking['type'] != "Object") && $from_page == "" && $mgmt_config['db_connect_rdbms'] != "") echo "<img class=\"hcmsButton hcmsButtonSizeSquare\" onClick=\"if (locklayer == false) parent.mainFrame.location.href='frameset_search.php?location=".url_encode($location_esc)."';\" name=\"pic_obj_search\" src=\"".getthemelocation()."img/button_search.gif\" alt=\"".$hcms_lang['search'][$lang]."\" title=\"".$hcms_lang['search'][$lang]."\" />\n";
    else echo "<img src=\"".getthemelocation()."img/button_search.gif\" class=\"hcmsButtonOff hcmsButtonSizeSquare\" />\n";
    ?>    
  </div>
  <?php } ?>
  <div class="hcmsToolbarBlock">
    <?php
    if (!$is_mobile)
    {
      // reload button
      if ($from_page == "") $refresh = "location.href='explorer_objectlist.php?site=".url_encode($site)."&cat=".url_encode($cat)."&location=".url_encode($location_esc)."'";
      else $refresh = "location.reload();";
      
      echo "<img class=\"hcmsButton hcmsButtonSizeSquare\" onClick=\"if (locklayer == false) parent.frames['mainFrame'].".$refresh.";\" name=\"pic_obj_refresh\" src=\"".getthemelocation()."img/button_view_refresh.gif\" alt=\"".$hcms_lang['refresh'][$lang]."\" title=\"".$hcms_lang['refresh'][$lang]."\">\n";
  
      // help button
      if (!$is_mobile && file_exists ("help/usersguide_".$hcms_lang_shortcut[$lang].".pdf"))
      {echo "<a href=# onMouseOut=\"hcms_swapImgRestore()\" onMouseOver=\"hcms_swapImage('pic_obj_help','','".getthemelocation()."img/button_help_over.gif',1)\" onClick=\"hcms_openWindow('help/usersguide_".$hcms_lang_shortcut[$lang].".pdf','help','scrollbars=no,resizable=yes,width=800,height=600','800','600');\"><img name=\"pic_obj_help\" src=\"".getthemelocation()."img/button_help.gif\" class=\"hcmsButtonBlank hcmsButtonSizeSquare\" alt=\"".$hcms_lang['help'][$lang]."\" title=\"".$hcms_lang['help'][$lang]."\" /></a>\n";}
      elseif (!$is_mobile && file_exists ("help/usersguide_en.pdf"))
      {echo "<a href=# onMouseOut=\"hcms_swapImgRestore()\" onMouseOver=\"hcms_swapImage('pic_obj_help','','".getthemelocation()."img/button_help_over.gif',1)\" onClick=\"hcms_openWindow('help/usersguide_en.pdf','help','scrollbars=no,resizable=yes,width=800,height=600','800','600');\"><img name=\"pic_obj_help\" src=\"".getthemelocation()."img/button_help.gif\" class=\"hcmsButtonBlank hcmsButtonSizeSquare\" alt=\"".$hcms_lang['help'][$lang]."\" title=\"".$hcms_lang['help'][$lang]."\" /></a>\n";}
    }   
    ?>
    <?php
    if (!$is_mobile && is_array ($hcms_linking))
    {
      // logout button
      echo "<img onClick=\"top.location.href='userlogout.php';\" class=\"hcmsButton hcmsButtonSizeSquare\" name=\"pic_obj_logout\" src=\"".getthemelocation()."img/button_logout.gif\" alt=\"".$hcms_lang['logout'][$lang]."\" title=\"".$hcms_lang['logout'][$lang]."\" /></td>\n";
    }
    ?>
  </div>
</div>

<!-- filter bar -->
<?php if (!$is_mobile && $from_page == "" && $cat != "page") { ?>
<div id="filterLayer" class="hcmsToolbarBlock" style="margin: 3px; visibility:<?php if (isset ($objectfilter) && is_array ($objectfilter) && sizeof ($objectfilter) > 0) echo "visible"; else echo "hidden"; ?>">
  <form name="filter_set" action="explorer_objectlist.php" target="mainFrame" method="get">
    <input type="hidden" name="location" value="<?php echo $location_esc; ?>" />
    <input type="hidden" name="virtual" value="<?php echo $virtual; ?>" />
    <img src="<?php echo getthemelocation(); ?>img/button_filter.gif" style="vertical-align:middle; width:18px; height:18px;" />
    <input type="hidden" name="filter[dummy]" value="1" />
    <input type="checkbox" onclick="setfilter();" name="filter[comp]" value="1" <?php if (isset ($objectfilter['comp']) && $objectfilter['comp'] == 1) echo "checked=\"checked\""; ?>/><label for="filter1" class="hcmsTextSmall"><?php echo $hcms_lang['component'][$lang]; ?></label>&nbsp;&nbsp;
    <input type="checkbox" onclick="setfilter();" name="filter[image]" value="1" <?php if (isset ($objectfilter['image']) && $objectfilter['image'] == 1) echo "checked=\"checked\""; ?>/><label for="filter2" class="hcmsTextSmall"><?php echo $hcms_lang['image'][$lang]; ?></label>&nbsp;&nbsp;
    <input type="checkbox" onclick="setfilter();" name="filter[document]" value="1" <?php if (isset ($objectfilter['document']) && $objectfilter['document'] == 1) echo "checked=\"checked\""; ?>/><label for="filter3" class="hcmsTextSmall"><?php echo $hcms_lang['document'][$lang]; ?></label>&nbsp;&nbsp;
    <input type="checkbox" onclick="setfilter();" name="filter[video]" value="1" <?php if (isset ($objectfilter['video']) && $objectfilter['video'] == 1) echo "checked=\"checked\""; ?>/><label for="filter4" class="hcmsTextSmall"><?php echo $hcms_lang['video'][$lang]; ?></label>&nbsp;&nbsp;
    <input type="checkbox" onclick="setfilter();" name="filter[audio]" value="1" <?php if (isset ($objectfilter['audio']) && $objectfilter['audio'] == 1) echo "checked=\"checked\""; ?>/><label for="filter5" class="hcmsTextSmall"><?php echo $hcms_lang['audio'][$lang]; ?></label>&nbsp;&nbsp;
  </form>
</div>
<?php } ?>

<?php
echo showmessage ($show, 650, 60, $lang, "position:fixed; left:15px; top:15px; ");
?>

<div id="foldercreateLayer" class="hcmsMessage" style="position:absolute; width:<?php if ($is_mobile) echo "90%"; else echo "650px"; ?>; height:60px; z-index:1; left:15px; top:15px; visibility:hidden">
<form name="folder_create" action="" method="post" onsubmit="return checkForm_folder_create();">
  <input type="hidden" name="location" value="<?php echo $location_esc; ?>" />
  <input type="hidden" name="action" value="folder_create" />
  <input type="hidden" name="token" value="<?php echo $token_new; ?>">
  
  <table width="100%" height="60" border="0" cellspacing="4" cellpadding="0">
    <tr>
      <td valign="middle">
        <?php echo $hcms_lang['create-folder'][$lang]; ?>:
        <input type="text" name="foldernew" maxlength="<?php if (!is_int ($mgmt_config['max_digits_filename'])) echo $mgmt_config['max_digits_filename']; else echo "200"; ?>" style="width:220px;" />
        <img name="Button1" src="<?php echo getthemelocation(); ?>img/button_OK.gif" class="hcmsButtonTinyBlank hcmsButtonSizeSquare" onclick="checkForm_folder_create();" onMouseOut="hcms_swapImgRestore()" onMouseOver="hcms_swapImage('Button1','','<?php echo getthemelocation(); ?>img/button_OK_over.gif',1)" align="absmiddle" alt="OK" title="OK" />
      </td>
      <td width="16" align="right" valign="top">
        <img name="hcms_mediaClose1" src="<?php echo getthemelocation(); ?>img/button_close.gif" class="hcmsButtonTinyBlank hcmsButtonSizeSquare" alt="<?php echo $hcms_lang['close'][$lang]; ?>" title="<?php echo $hcms_lang['close'][$lang]; ?>" onMouseOut="hcms_swapImgRestore();" onMouseOver="hcms_swapImage('hcms_mediaClose1','','<?php echo getthemelocation(); ?>img/button_close_over.gif',1);" onClick="hcms_showHideLayers('foldercreateLayer','','hide');" />
      </td>      
    </tr>
  </table>
</form>
</div>

<div id="folderrenameLayer" class="hcmsMessage" style="position:absolute; width:<?php if ($is_mobile) echo "90%"; else echo "650px"; ?>; height:60px; z-index:2; left:15px; top:15px; visibility:hidden">
<form name="folder_rename" action="" method="post" onsubmit="return checkForm_folder_rename();">
  <input type="hidden" name="location" value="<?php echo $location_esc; ?>" />
  <input type="hidden" name="folder" value="<?php echo $folder; ?>" />
  <input type="hidden" name="action" value="folder_rename" />
  <input type="hidden" name="token" value="<?php echo $token_new; ?>">
  
  <table width="100%" height="60" border="0" cellspacing="4" cellpadding="0">
    <tr>
      <td valign="middle">
        <?php echo $hcms_lang['rename-folder'][$lang]; ?>:
        <input type="text" name="foldernew" maxlength="<?php if (!is_int ($mgmt_config['max_digits_filename'])) echo $mgmt_config['max_digits_filename']; else echo "200"; ?>" style="width:220px;" value="<?php echo $pagename; ?>" />
        <img name="Button2" src="<?php echo getthemelocation(); ?>img/button_OK.gif" class="hcmsButtonTinyBlank hcmsButtonSizeSquare" onclick="checkForm_folder_rename();" onMouseOut="hcms_swapImgRestore()" onMouseOver="hcms_swapImage('Button2','','<?php echo getthemelocation(); ?>img/button_OK_over.gif',1)" align="absmiddle" alt="OK" title="OK" />
      </td>
      <td width="16" align="right" valign="top">
        <img name="hcms_mediaClose2" src="<?php echo getthemelocation(); ?>img/button_close.gif" class="hcmsButtonTinyBlank hcmsButtonSizeSquare" alt="<?php echo $hcms_lang['close'][$lang]; ?>" title="<?php echo $hcms_lang['close'][$lang]; ?>" onMouseOut="hcms_swapImgRestore();" onMouseOver="hcms_swapImage('hcms_mediaClose2','','<?php echo getthemelocation(); ?>img/button_close_over.gif',1);" onClick="hcms_showHideLayers('folderrenameLayer','','hide');" />
      </td>        
    </tr>
  </table>
</form>
</div>

<div id="objrenameLayer" class="hcmsMessage" style="position:absolute; width:<?php if ($is_mobile) echo "90%"; else echo "650px"; ?>; height:60px; z-index:3; left:15px; top:15px; visibility:hidden">
<form name="page_rename" action="" onsubmit="return checkForm_folder_rename();">
  <input type="hidden" name="location" value="<?php echo $location_esc; ?>" />
  <input type="hidden" name="page" value="<?php echo $page; ?>" />
  <input type="hidden" name="action" value="page_rename" />
  <input type="hidden" name="token" value="<?php echo $token_new; ?>">
  
  <table width="100%" height="60" border="0" cellspacing="2" cellpadding="0">
    <tr>
      <td valign="middle">
        <?php echo $hcms_lang['rename'][$lang];  if ($filetype == "Page" || $filetype == "Component") echo " ".$hcms_lang['name-without-ext'][$lang]; ?>: 
        <input type="text" name="pagenew" maxlength="<?php if (!is_int ($mgmt_config['max_digits_filename'])) echo $mgmt_config['max_digits_filename']; else echo "200"; ?>" style="width:220px;" value="<?php echo substr ($pagename, 0, strrpos ($pagename, ".")); ?>" />
        <img name="Button5" src="<?php echo getthemelocation(); ?>img/button_OK.gif" class="hcmsButtonTinyBlank hcmsButtonSizeSquare" onclick="checkForm_page_rename();" onMouseOut="hcms_swapImgRestore()" onMouseOver="hcms_swapImage('Button5','','<?php echo getthemelocation(); ?>img/button_OK_over.gif',1)" align="absmiddle" alt="OK" title="OK" />
      </td>
      <td width="16" align="right" valign="top">
        <img name="hcms_mediaClose3" src="<?php echo getthemelocation(); ?>img/button_close.gif" class="hcmsButtonTinyBlank hcmsButtonSizeSquare" alt="<?php echo $hcms_lang['close'][$lang]; ?>" title="<?php echo $hcms_lang['close'][$lang]; ?>" onMouseOut="hcms_swapImgRestore();" onMouseOver="hcms_swapImage('hcms_mediaClose3','','<?php echo getthemelocation(); ?>img/button_close_over.gif',1);" onClick="hcms_showHideLayers('objrenameLayer','','hide');" />
      </td>      
    </tr>
  </table>
</form>
</div>

<div id="zipLayer" class="hcmsMessage" style="position:absolute; width:<?php if ($is_mobile) echo "90%"; else echo "650px"; ?>; height:60px; z-index:4; left:15px; top:15px; visibility:hidden">
<form name="page_zip" action="">
  <input type="hidden" name="location" value="<?php echo $location_esc; ?>" />
  <input type="hidden" name="page" value="<?php echo $page; ?>" />
  <input type="hidden" name="folder" value="<?php echo $folder; ?>" />
  <input type="hidden" name="action" value="zip" />
  <input type="hidden" name="multiobject" value="<?php echo $multiobject ?>" />
  <input type="hidden" name="token" value="<?php echo $token_new; ?>" />
  
  <table width="100%" height="60" border="0" cellspacing="2" cellpadding="0">
    <tr>
      <td valign="middle">
        <?php echo $hcms_lang['create-zip-file-without-ext'][$lang]; ?>: 
        <input type="text" name="pagenew" maxlength="100" style="width:220px;" value="<?php echo substr ($pagename, 0, strrpos ($pagename, ".")); ?>" />
        <img name="Button6" src="<?php echo getthemelocation(); ?>img/button_OK.gif" onclick="checkForm_zip();" class="hcmsButtonTinyBlank hcmsButtonSizeSquare" onMouseOut="hcms_swapImgRestore()" onMouseOver="hcms_swapImage('Button6','','<?php echo getthemelocation(); ?>img/button_OK_over.gif',1)" align="absmiddle" alt="OK" title="OK" />
      </td>
      <td width="16" align="right" valign="top">
        <img name="hcms_mediaClose4" src="<?php echo getthemelocation(); ?>img/button_close.gif" class="hcmsButtonTinyBlank hcmsButtonSizeSquare" alt="<?php echo $hcms_lang['close'][$lang]; ?>" title="<?php echo $hcms_lang['close'][$lang]; ?>" onMouseOut="hcms_swapImgRestore();" onMouseOver="hcms_swapImage('hcms_mediaClose4','','<?php echo getthemelocation(); ?>img/button_close_over.gif',1);" onClick="hcms_showHideLayers('zipLayer','','hide');" />
      </td>       
    </tr>
  </table>
</form>
</div>

<div id="downloadLayer" class="hcmsMessage" style="position:absolute; width:<?php if ($is_mobile) echo "80%"; else echo "650px"; ?>; height:60px; z-index:15; left:15px; top:15px; visibility:<?php echo ($action == 'download' ? 'visible' : 'hidden'); ?>;" >
  <table width="100%" height="60" border=0 cellspacing=0 cellpadding=3 class="hcmsMessage">
    <tr>
      <td align="left" valign="middle">
        <div style="width:100%; height:100%; z-index:10; overflow:auto;">
          <?php
          // iPhone download
          if ($action == "download" && $is_iphone)
          { 
            $downloadlink = createmultidownloadlink ($site, $multiobject, $media, $location.$folder, $pagename, $user, $convert_type, $convert_cfg);
            
            echo "<a href=\"".$downloadlink."\" class=\"button hcmsButtonGreen\" target=\"_blank\">".$hcms_lang['click-here-to-viewdownload-the-file'][$lang]."</a>";
          }
          else
          {
            echo $hcms_lang['please-wait-while-your-download-is-being-processed'][$lang];
          }
          ?>
        </div>
      </td>
      <td width="16" align="right" valign="top">
        <img name="hcms_mediaClose5" src="<?php echo getthemelocation(); ?>img/button_close.gif" class="hcmsButtonTinyBlank hcmsButtonSizeSquare" alt="<?php echo $hcms_lang['close'][$lang]; ?>" title="<?php echo $hcms_lang['close'][$lang]; ?>" onMouseOut="hcms_swapImgRestore();" onMouseOver="hcms_swapImage('hcms_mediaClose5','','<?php echo getthemelocation(); ?>img/button_close_over.gif',1);" onClick="hcms_showHideLayers('downloadLayer','','hide');" />
      </td>        
    </tr>
  </table>
</div>
<?php 
if ($action == "download" && !$is_iphone)
{
  $downloadlink = createmultidownloadlink ($site, $multiobject, $media, $location.$folder, $pagename, $user, $convert_type, $convert_cfg);
  
  if ($downloadlink != "")
  {
?>
<script type="text/javascript">
<!--
function downloadFile()
{
  hcms_showHideLayers('downloadLayer','','hide');
  location.replace('<?php echo $downloadlink; ?>');
}

setTimeout('downloadFile()', 1000);
-->
</script>  
<?php
  }
  // download failed (zip file could not be created)
  else
  {
    echo showmessage (str_replace ("%filesize%", $mgmt_config['maxzipsize'], $hcms_lang['download-failed-max'][$lang]), 650, 60, $lang, "position:fixed; left:15px; top:15px; ");
  }
}
?>

</body>
</html>
