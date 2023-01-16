<?php
/*
 * This file is part of
 * hyper Content & Digital Management Server - http://www.hypercms.com
 * Copyright (c) by hyper CMS Content Management Solutions GmbH
 *
 * You should have received a copy of the license (license.txt) along with hyper Content & Digital Management Server
 */

// session
define ("SESSION", "create");
// management configuration
require ("config.inc.php");
// hyperCMS API
require ("function/hypercms_api.inc.php");


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
$convert_type = getrequest ("convert_type");
$convert_cfg = getrequest ("convert_cfg");
$toolbar = getrequest ("toolbar", "array");
$token = getrequest ("token");

// location has been provided
if ($location != "")
{
  // correct location for access permission
  if ($folder != "") $location_ACCESS = $location.$folder."/";
  else $location_ACCESS = $location;
  
  // get publication and category
  $site = getpublication ($location_ACCESS);
  $cat = getcategory ($site, $location_ACCESS); 
}
else
{
  $location_ACCESS = "";
  $site = "";
  $cat = "";
}

// publication management config
if (valid_publicationname ($site)) require ($mgmt_config['abs_path_data']."config/".$site.".conf.php");
// load publication configuration
if (valid_publicationname ($site)) $publ_config = parse_ini_file ($mgmt_config['abs_path_rep']."config/".$site.".ini");

// ------------------------------ permission section --------------------------------

// check access permissions
$ownergroup = accesspermission ($site, $location_ACCESS, $cat);
$setlocalpermission_ACCESS = setlocalpermission ($site, $ownergroup, $cat);

// check for general root element access since localpermissions are checked later
if ($virtual != 1 && (!valid_publicationname ($site) || !valid_locationname ($location))) killsession ($user);

// check session of user
checkusersession ($user);

// --------------------------------- logic section ----------------------------------

// initialize
$show = "";
$add_onload = "";
$usedby = "";
$pagename = "";
$file_info = Null;
$filetype = "";
$contentfile = "";
$container_id = "";
$media = "";
$multiobject_count = 0;
$media_info['ext'] = "";
$doc_rendering = false;
$img_rendering = false;
$vid_rendering = false;

// convert location
$location = deconvertpath ($location, "file");
$location_esc = convertpath ($site, $location, $cat);

// if multiobject
if ($multiobject != "")
{
  $multiobject_count = sizeof (link_db_getobject ($multiobject));

  if ($multiobject_count > 1)
  {
    $pagename = $multiobject_count." ".getescapedtext ($hcms_lang['objects-selected'][$lang]);
  }
  elseif ($multiobject_count == 1)
  {
    $object = getobject ($multiobject);

    if (is_dir ($location.$object))
    {
      $page = ".folder";
      $folder = $object;
      $file_info = getfileinfo ($site, $location.$folder, $cat);
      $pagename = $file_info['name'];
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
elseif ($location != "" && ($page != "" || $folder != ""))
{
  // folder (page = .folder)
  if (($folder != "" && is_dir ($location.$folder)) || $page == ".folder" || ($page == "" && is_dir ($location)))
  {
    $page = ".folder";
    $file_info = getfileinfo ($site, $location.$folder, $cat);
    $pagename = $file_info['name'];

    // define multiobject for download
    $multiobject = $location_esc.$folder;
  }
  // object
  elseif ($page != "")
  {
    $page = correctfile ($location, $page);
    $file_info = getfileinfo ($site, $page, $cat);
    $pagename = $file_info['name'];

    // define multiobject for download
    $multiobject = $location_esc.$page;
  } 
}

// load object file and get container and media file
if (!empty ($location_ACCESS) && !empty ($page))
{
  $objectdata = loadfile ($location_ACCESS, $page);
  $contentfile = getfilename ($objectdata, "content");
  $container_id = substr ($contentfile, 0, strpos ($contentfile, ".xml"));  
  $media = getfilename ($objectdata, "media");
}

// set local permissions for current location
$ownergroup = accesspermission ($site, $location, $cat);
$setlocalpermission = setlocalpermission ($site, $ownergroup, $cat);

// set local permission for parent folder
$location_down = getlocation ($location);
$location_down_esc = getlocation ($location_esc);
$ownergroup_down = accesspermission ($site, $location_down_esc, $cat);
$setlocalpermission_down = setlocalpermission ($site, $ownergroup_down, $cat);

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
    $zipFolder = $mgmt_config['abs_path_temp'];
    $multiobject_array = link_db_getobject ($multiobject);

    $result = zipfiles ($site, $multiobject_array, $zipFolder, $pagenew, $user);

    if ($result == true) $result = createmediaobject ($site, $location, $pagenew.".zip", $zipFolder.$pagenew.".zip", $user);
    else $result['result'] = false;

    if (!empty ($result['result']))
    {
      $add_onload = "parent.frames['mainFrame'].location.reload();";
      $show = getescapedtext ($hcms_lang['the-file-'][$lang].$pagenew.$hcms_lang['zip-was-created'][$lang]);
      $page = $result['object'];
      $pagename = $result['name'];
      $filetype = $result['objecttype'];  
    }
    else
    {
      // max file size default value is 2000 MB
      if (!isset ($mgmt_config['maxzipsize'])) $mgmt_config['maxzipsize'] = 2000;
      $show = getescapedtext ($hcms_lang['the-file-'][$lang].$pagenew.str_replace ("%filesize%", $mgmt_config['maxzipsize'], $hcms_lang['zip-could-not-be-created-max'][$lang]));
    }
  }
  // add to favorites
  elseif (($action == "page_favorite_add" || $action == "page_favorite_delete") && $setlocalpermission['root'] == 1) 
  {
    $multiobject_array = link_db_getobject ($multiobject);

    foreach ($multiobject_array as $temp)
    {
      if ($action == "page_favorite_add") createfavorite (getpublication ($temp), getlocation ($temp), getobject ($temp), "", $user);
      elseif ($action == "page_favorite_delete") deletefavorite (getpublication ($temp), getlocation ($temp), getobject ($temp), "", $user);
    }
  }
  // import text content or metadata from CSV file
  elseif ($action == "import" && $setlocalpermission['root'] == 1 && $setlocalpermission['create'] == 1)
  {
    $file_temp = $mgmt_config['abs_path_temp'].uniqid ("tmp").".csv";
    
    if (!empty ($_FILES["importfile"]) && move_uploaded_file ($_FILES["importfile"]["tmp_name"], $file_temp))
    {
      $import = importCSVtextcontent ($site, $location, $file_temp, $user, "", "", "", "utf-8", $createobject, $template);
      
      deletefile (getlocation ($file_temp), getobject ($file_temp), 0);
    }

    if (!empty ($import)) $show = getescapedtext ($hcms_lang['the-data-was-saved-successfully'][$lang]);
    else $show = getescapedtext ($hcms_lang['the-data-could-not-be-saved'][$lang]);
  }
  // export selected objects as CSV
  elseif ($action == "export" && $setlocalpermission['root'] == 1)
  {
    $multiobject_array = link_db_getobject ($multiobject);

    // get all text content/metadata as array
    $assoc_array = getmetadata_multiobjects ($multiobject_array, $user, false);

    // CSV export
    create_csv ($assoc_array, "export.csv", "php://output", ";", '"', "utf-8", "utf-8", true);
  }
  // save toolbar settings
  elseif ($action == "toolbar")
  {
    $settoolbar = settoolbarfunctions ($toolbar, $user);

    // reset toolbar functions in session
    if (!empty ($settoolbar)) $toolbarfunctions = getsession ("hcms_toolbarfunctions");

    if (!empty ($settoolbar)) $show = getescapedtext ($hcms_lang['the-data-was-saved-successfully'][$lang]);
    else $show = getescapedtext ($hcms_lang['the-data-could-not-be-saved'][$lang]);
  }
}

// define message if object is checked out by another user
if (!empty ($contentfile))
{
  $usedby_array = getcontainername ($contentfile);

  if (is_array ($usedby_array) && !empty ($usedby_array['user'])) $usedby = $usedby_array['user'];

  if ($usedby != "" && $usedby != $user) $show = getescapedtext ($hcms_lang['object-is-checked-out-by-user'][$lang])." '".$usedby."'";
}

// get file info
if (!empty ($page)) 
{
  // correct object file name
  $page = correctfile ($location_ACCESS, $page, $user);      
  // get file info
  $file_info = getfileinfo ($site, $location_ACCESS.$page, $cat);
  $filetype = $file_info['type'];
}

// define object category name
if ($filetype == "Page")
{
  $pagecomp = getescapedtext ($hcms_lang['page'][$lang]);
}
elseif ($filetype == "Component")
{
  $pagecomp = getescapedtext ($hcms_lang['component'][$lang]);
}
else
{
  $pagecomp = getescapedtext ($hcms_lang['asset'][$lang]);
}

// create secure token
$token_new = createtoken ($user);
?>
<!DOCTYPE html>
<html>
<head>
<title>hyperCMS</title>
<meta charset="<?php echo getcodepage ($lang); ?>" />
<link rel="stylesheet" href="<?php echo getthemelocation(); ?>css/main.css?v=<?php echo getbuildnumber(); ?>" />
<link rel="stylesheet" href="<?php echo getthemelocation()."css/".($is_mobile ? "mobile.css" : "desktop.css"); ?>?v=<?php echo getbuildnumber(); ?>" />
<script type="text/javascript" src="javascript/jquery/jquery.min.js"></script>
<script type="text/javascript" src="javascript/click.min.js"></script>
<script type="text/javascript" src="javascript/main.min.js?v=<?php echo getbuildnumber(); ?>"></script>
<script type="text/javascript" src="javascript/chat.min.js"></script>
<?php
// invert colors
if (!empty ($hcms_themeinvertcolors))
{
  echo "<style>";
  echo invertcolorCSS ($hcms_themeinvertcolors);
  echo "</style>";
}
?>
<script type="text/javascript">

var locklayer = false;

// verify sidebar
if (parent.document.getElementById('sidebarLayer') && parent.document.getElementById('sidebarLayer').style.display != 'none') var sidebar = true;
else var sidebar = false;

function submitToWindow (url, action, windowname, features, width, height)
{
  if (parent.frames['mainFrame'].document.forms['contextmenu_object'])
  {
    if (features == undefined) features = 'scrollbars=no,resizable=no';
    if (width == undefined) width = 400;
    if (height == undefined) height = 220;
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
  else alert ('<?php echo getescapedtext ($hcms_lang['error-occured'][$lang]); ?>');
}

function submitToPopup (url, action, id)
{
  if (parent.frames['mainFrame'].document.forms['contextmenu_object'])
  {
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
    form.target = id  + "Frame";

    var result = window.top.openPopup('empty.php', id);
    if (result) form.submit();
  }
  else alert ('<?php echo getescapedtext ($hcms_lang['error-occured'][$lang]); ?>');
}

function submitToMainFrame (url, action)
{
  if (parent.frames['mainFrame'].document.forms['contextmenu_object'])
  {
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
    form.target = "objectviewMain";

    parent.openMainView('empty.php');
    form.submit();
  }
  else alert ('<?php echo getescapedtext ($hcms_lang['error-occured'][$lang]); ?>');
}

function submitToFrame (url, action)
{
  if (parent.frames['mainFrame'].document.forms['contextmenu_object'])
  {
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
    form.target = "objectview";
    
    parent.openPopup('empty.php');
    form.submit();
  }
  else alert ('<?php echo getescapedtext ($hcms_lang['error-occured'][$lang]); ?>');
}

function submitToSelf (action)
{
  if (parent.frames['mainFrame'].document.forms['contextmenu_object'])
  {
    var form = parent.frames['mainFrame'].document.forms['contextmenu_object'];

    form.attributes['action'].value = '<?php echo $_SERVER['PHP_SELF']; ?>';
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
  else alert ('<?php echo getescapedtext ($hcms_lang['error-occured'][$lang]); ?>');
}

function unzip (id)
{
  var unzip = confirm('<?php echo getescapedtext ($hcms_lang['existing-objects-will-be-replaced'][$lang]); ?>');

  if (unzip)
  {
    window.top.openPopup('popup_action.php?action=unzip&site=<?php echo url_encode($site); ?>&cat=<?php echo url_encode($cat); ?>&location=<?php echo url_encode($location_esc); ?>&page=<?php echo url_encode($page); ?>&from_page=<?php echo url_encode($from_page); ?>&token=<?php echo $token_new; ?>', id);
  }
}

function checkForm_delete ()
{
  check = confirm ("<?php echo getescapedtext ($hcms_lang['are-you-sure-you-want-to-remove-the-item'][$lang]); ?>");

  if (check == true)
  {    
    <?php 
    if ((isset ($multiobject) && $multiobject_count > 0) || (isset ($folder) && $folder != "") || (isset ($page) && $page != ""))
    {
      echo "submitToPopup('popup_status.php', 'delete', 'delete".uniqid()."');\n";
    }
    ?>
  }
}

function checkForm_chars (text, exclude_chars)
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

		for (var i = 0; i < found.length; i++)
    {
			addText += found[i]+separator;
		}

		addText = addText.substr(0, addText.length-separator.length);
		alert (hcms_entity_decode("<?php echo getescapedtext ($hcms_lang['please-do-not-use-the-following-special-characters'][$lang]); ?> ") + addText);
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

  if (form.elements['foldernew'].value.trim() == "")
  {
    alert (hcms_entity_decode("<?php echo getescapedtext ($hcms_lang['a-name-is-required'][$lang]); ?>"));
    form.elements['foldernew'].focus();
    return false;
  }

  if (!checkForm_chars(form.elements['foldernew'].value, ".-_"))
  {
    form.elements['foldernew'].focus();
    return false;
  }

  // load screen
  if (document.getElementById('hcmsLoadScreen')) document.getElementById('hcmsLoadScreen').style.display = 'inline';
  
  form.submit();
  return true;
}

function checkForm_folder_rename()
{
  var form = document.forms['folder_rename'];

  if (form.elements['foldernew'].value.trim() == "")
  {
    alert (hcms_entity_decode("<?php echo getescapedtext ($hcms_lang['a-name-is-required'][$lang]); ?>"));
    form.elements['foldernew'].focus();
    return false;
  }

  if (!checkForm_chars(form.elements['foldernew'].value, ".-_"))
  {
    form.elements['foldernew'].focus();
    return false;
  }

  // load screen
  if (document.getElementById('hcmsLoadScreen')) document.getElementById('hcmsLoadScreen').style.display = 'inline';
  
  form.submit();
  return true;
}

function checkForm_page_rename()
{
  var form = document.forms['page_rename'];

  if (form.elements['pagenew'].value.trim() == "")
  {
    alert (hcms_entity_decode("<?php echo getescapedtext ($hcms_lang['a-name-is-required'][$lang]); ?>"));
    form.elements['pagenew'].focus();
    return false;
  }

  if (!checkForm_chars(form.elements['pagenew'].value, ".-_"))
  {
    form.elements['pagenew'].focus();
    return false;
  }

  // load screen
  if (document.getElementById('hcmsLoadScreen')) document.getElementById('hcmsLoadScreen').style.display = 'inline';

  form.submit()
  return true;
}

function checkForm_zip()
{
  var form = document.forms['page_zip'];

  if (form.elements['pagenew'].value == "")
  {
    alert (hcms_entity_decode("<?php echo getescapedtext ($hcms_lang['a-name-is-required'][$lang]); ?>"));
    form.elements['pagenew'].focus();
    return false;
  }

  if (!checkForm_chars(form.elements['pagenew'].value, ".-_"))    
  {
    form.elements['pagenew'].focus();
    return false;
  }

  // load screen
  if (document.getElementById('hcmsLoadScreen')) document.getElementById('hcmsLoadScreen').style.display = 'inline';

  form.submit();
  return true;
}

function docConvert (type)
{
  if (parent.frames['mainFrame'].document.forms['contextmenu_object'])
  {
    var form = parent.frames['mainFrame'].document.forms['contextmenu_object'];

    form.attributes['action'].value = '<?php echo $_SERVER['PHP_SELF']; ?>';
    form.elements['convert_type'].value = type;

    submitToSelf ('download');
    hcms_showHideLayers('downloadLayer','','show');
  }
  else return false; 
}

function imgConvert (type, config)
{
  if (parent.frames['mainFrame'].document.forms['contextmenu_object'])
  {
    var form = parent.frames['mainFrame'].document.forms['contextmenu_object'];

    form.attributes['action'].value = '<?php echo $_SERVER['PHP_SELF']; ?>';
    form.elements['convert_type'].value = type;
    form.elements['convert_cfg'].value = config;

    submitToSelf ('download');
    hcms_showHideLayers('downloadLayer','','show');
  }
  else return false; 
}

function vidConvert (type)
{
  if (parent.frames['mainFrame'].document.forms['contextmenu_object'])
  {
    var form = parent.frames['mainFrame'].document.forms['contextmenu_object'];

    form.attributes['action'].value = '<?php echo $_SERVER['PHP_SELF']; ?>';
    form.elements['convert_type'].value = type;

    submitToSelf ('download');
    hcms_showHideLayers('downloadLayer','','show');
  }
  else return false; 
}

function checkForm_import()
{
  var form = document.forms['import'];
  var filename = form.elements['importfile'].value;

  if (filename.trim() == "" || filename.substr((filename.lastIndexOf('.') + 1)).toLowerCase() != "csv")
  {
    alert (hcms_entity_decode("<?php echo getescapedtext ($hcms_lang['please-select-a-file-to-upload'][$lang]); ?>"));
    form.elements['foldernew'].focus();
    return false;
  }

  form.submit();
  return true;
}

function switchView (view)
{
  if (view == "large" || view == "medium" || view == "small" || view == "detail")
  {
    document.forms['memory'].elements['view'].value = view;

    // AJAX request to set view
    $.post("<?php echo $mgmt_config['url_path_cms']; ?>service/toggleview.php", {view: view});

    // change view in object list
    if (parent.frames['mainFrame'] && typeof parent.frames['mainFrame'].toggleview == 'function') parent.frames['mainFrame'].toggleview (view);

    // set icon
    document.getElementById('pic_obj_view').src="<?php echo getthemelocation($hcms_themeinvertcolors); ?>img/button_view_gallery_" + view + ".png";  
    document.getElementById('select_obj_view').style.visibility = 'hidden';

    return true;
  }
  else return false;
}

function switchSidebar ()
{  
  if (!sidebar) view = true;
  else view = false;

  if (view == true || view == false)
  {
    // AJAX request to set view
    $.post("<?php echo $mgmt_config['url_path_cms']; ?>service/togglesidebar.php", {view: view});

    // change view in object list
    if (parent.frames['mainFrame'] && parent.frames['sidebarFrame'])
    {
      if (view)
      {
        if (hcms_transitioneffect == true) parent.document.getElementById('mainLayer').style.transition = "0.3s";
        parent.document.getElementById('mainLayer').style.right = "350px";
        if (hcms_transitioneffect == true) parent.document.getElementById('sidebarLayer').style.transition = "0.3s";
        parent.document.getElementById('sidebarLayer').style.width = "350px";
        parent.frames['mainFrame'].resizecols();
        sidebar = true;
      }
      else
      {
        if (hcms_transitioneffect == true) parent.document.getElementById('mainLayer').style.transition = "0.3s";
        parent.document.getElementById('mainLayer').style.right = "0px";
        if (hcms_transitioneffect == true) parent.document.getElementById('sidebarLayer').style.transition = "0.3s";
        parent.document.getElementById('sidebarLayer').style.width = "0px";
        parent.frames['mainFrame'].resizecols();
        sidebar = false;
      }

      // set sidebar variable in object list 
      parent.frames['mainFrame'].hcms_setGlobalVar('sidebar', sidebar);
    }

    return true;
  }
  else return false;
}

function switchFilter ()
{
  if (document.getElementById('filterLayer'))
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

function setFilter (filter)
{
  if (document.forms['filter_set'])
  {
    var form = document.forms['filter_set'];

    form.submit();
    return true;
  }
  else return false;
}

function switchToolbar ()
{
  return hcms_switchSelector('toolbarSettingsLayer');
}

function setToolbar ()
{
  if (document.forms['toolbar_set'])
  {
    var form = document.forms['toolbar_set'];

    form.submit();
    return true;
  }
  else return false;
}

function openObjectView (location, object, view)
{
  if (location != "" && object != "" && parent.document.getElementById('objectview'))
  {
    parent.openObjectView(location, object, view);
  }
  else return false;
}

function hideselectors ()
{
  hcms_hideSelector('select_obj_view');
  hcms_hideSelector('select_obj_edit');
  hcms_hideSelector('select_obj_convert');
}

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
</script>
</head>

<body class="hcmsWorkplaceControl" onload="<?php echo $add_onload; ?>">

<!-- load screen --> 
<div id="hcmsLoadScreen" class="hcmsLoadScreen" style="display:none;"></div>

<!-- help infobox -->
<?php if (!$is_mobile) echo showinfobox ($hcms_lang['move-the-mouse-over-the-icons-to-get-more-information'][$lang], $lang, "position:fixed; top:10px; right:10px;"); ?>

<!-- message -->
<?php echo showmessage ($show, 660, 70, $lang, "position:fixed; left:10px; top:10px;"); ?>

<!-- toolbar settings -->
<?php if (!empty ($mgmt_config['toolbar_functions'])) { ?>
<div style="position:fixed; <?php if ($is_mobile) echo "top:-1px; right:2px;"; else echo "top:4px; right:6px;"; ?>">
  <?php echo "<img src=\"".getthemelocation()."img/admin.png\" class=\"hcmsButtonTiny hcmsIconList\" onclick=\"switchToolbar();\" title=\"".getescapedtext ($hcms_lang['customize-toolbar'][$lang])."\" alt=\"".getescapedtext ($hcms_lang['customize-toolbar'][$lang])."\" />"; ?>
</div>
<div id="toolbarSettingsLayer" class="hcmsMessage" style="position:fixed; top:6px; right:22px; min-width:<?php if ($is_mobile) echo "80%"; else echo "560px"; ?>; max-height:80px; margin:0; padding:0px 4px; overflow:auto; visibility:hidden;">
  <form name="toolbar_set" action="" method="post">
    <input type="hidden" name="action" value="toolbar" />
    <input type="hidden" name="location" value="<?php echo $location_esc; ?>" />
    <input type="hidden" name="virtual" value="<?php echo $virtual; ?>" />
    <input type="hidden" name="page" value="<?php echo $page; ?>" />
    <input type="hidden" name="folder" value="<?php echo $folder; ?>" />
    <input type="hidden" name="multiobject" value="<?php echo $multiobject ?>" />
    <input type="hidden" name="from_page" value="<?php echo $from_page; ?>" />
    <input type="hidden" name="token" value="<?php echo $token_new; ?>" />
    <!-- required for function settoolbarfunctions -->
    <input type="hidden" name="toolbar[toolbar]" value="1" />

    <div class="hcmsHeadline"><?php echo getescapedtext ($hcms_lang['customize-toolbar'][$lang]); ?> <img name="ButtonToolbarSettings" src="<?php echo getthemelocation(); ?>img/button_ok.png" onclick="setToolbar();" class="hcmsButtonTinyBlank hcmsIconList" onMouseOut="hcms_swapImgRestore()" onMouseOver="hcms_swapImage('ButtonToolbarSettings','','<?php echo getthemelocation(); ?>img/button_ok_over.png',1)" alt="OK" title="OK" /></div>
    <div style="display:inline-block; width:45%; min-width:220px; white-space:nowrap;">
      <label><input type="checkbox" name="toolbar[preview]" value="1" <?php if (empty ($toolbarfunctions) || !empty ($toolbarfunctions['preview'])) echo "checked=\"checked\""; ?>/>&nbsp;<img src="<?php echo getthemelocation(); ?>img/button_file_preview.png" class="hcmsIconList" style="vertical-align:middle;" /> <?php echo getescapedtext ($hcms_lang['preview'][$lang]."/".$hcms_lang['view-live'][$lang]); ?></label>&nbsp;&nbsp;<br/>
      <label><input type="checkbox" name="toolbar[edit]" value="1" <?php if (empty ($toolbarfunctions) || !empty ($toolbarfunctions['edit'])) echo "checked=\"checked\""; ?>/>&nbsp;<img src="<?php echo getthemelocation(); ?>img/button_edit.png" class="hcmsIconList" style="vertical-align:middle;" /> <?php echo getescapedtext ($hcms_lang['edit'][$lang]); ?></label>&nbsp;&nbsp;<br/>
      <label><input type="checkbox" name="toolbar[updownload]" value="1" <?php if (empty ($toolbarfunctions) || !empty ($toolbarfunctions['updownload'])) echo "checked=\"checked\""; ?>/>&nbsp;<img src="<?php echo getthemelocation(); ?>img/button_file_upload.png" class="hcmsIconList" style="vertical-align:middle;" /> <?php echo getescapedtext ($hcms_lang['upload'][$lang]."/".$hcms_lang['download'][$lang]); ?></label>&nbsp;&nbsp;<br/>
      <label><input type="checkbox" name="toolbar[create]" value="1" <?php if (empty ($toolbarfunctions) || !empty ($toolbarfunctions['create'])) echo "checked=\"checked\""; ?>/>&nbsp;<img src="<?php echo getthemelocation(); ?>img/button_file_new.png" class="hcmsIconList" style="vertical-align:middle;" /> <?php echo getescapedtext ($hcms_lang['create'][$lang]); ?></label>&nbsp;&nbsp;
    </div>
    <div style="display:inline-block; width:45%; min-width:220px; white-space:nowrap;">
      <label><input type="checkbox" name="toolbar[unzip]" value="1" <?php if (empty ($toolbarfunctions) || !empty ($toolbarfunctions['unzip'])) echo "checked=\"checked\""; ?>/>&nbsp;<img src="<?php echo getthemelocation(); ?>img/button_zip.png" class="hcmsIconList" style="vertical-align:middle;" /> <?php echo getescapedtext ($hcms_lang['compress-files'][$lang]."/".$hcms_lang['extract-file'][$lang]); ?></label>&nbsp;&nbsp;<br/>
      <label><input type="checkbox" name="toolbar[share]" value="1" <?php if (empty ($toolbarfunctions) || !empty ($toolbarfunctions['share'])) echo "checked=\"checked\""; ?>/>&nbsp;<img src="<?php echo getthemelocation(); ?>img/button_user_sendlink.png" class="hcmsIconList" style="vertical-align:middle;" /> <?php echo getescapedtext ($hcms_lang['share'][$lang]."/".$hcms_lang['send-mail-link'][$lang]); ?></label>&nbsp;&nbsp;<br/>
      <label><input type="checkbox" name="toolbar[unpublish]" value="1" <?php if (empty ($toolbarfunctions) || !empty ($toolbarfunctions['unpublish'])) echo "checked=\"checked\""; ?>/>&nbsp;<img src="<?php echo getthemelocation(); ?>img/button_file_publish.png" class="hcmsIconList" style="vertical-align:middle;" /> <?php echo getescapedtext ($hcms_lang['publish'][$lang]."/".$hcms_lang['unpublish'][$lang]); ?></label>&nbsp;&nbsp;<br/>
      <label><input type="checkbox" name="toolbar[imexport]" value="1" <?php if (empty ($toolbarfunctions) || !empty ($toolbarfunctions['imexport'])) echo "checked=\"checked\""; ?>/>&nbsp;<img src="<?php echo getthemelocation(); ?>img/button_import.png" class="hcmsIconList" style="vertical-align:middle;" /> <?php echo getescapedtext ($hcms_lang['import'][$lang]."/".$hcms_lang['export'][$lang]); ?></label>&nbsp;&nbsp;
    </div>
  </form>
</div>
<?php } ?>

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
if (($page != "" && $page != ".folder") || $multiobject_count > 1)
{
  $item = $pagecomp;
  $object_name = $pagename;
}
elseif ($folder != "")
{
  $item = getescapedtext ($hcms_lang['folder'][$lang]);
  $object_name = $pagename;
}
else
{
  $item = "&nbsp;";
  $object_name = "&nbsp;";
}
?>

<!-- location bar -->
<div class="hcmsLocationBar">
  <?php if (!$is_mobile) { ?>
  <table class="hcmsTableNarrow">
    <tr>
      <?php
      // location
      if ($cat == "page" || $cat == "comp")
      {
        echo "
      <td style=\"white-space:nowrap; width:20px;\"><img src=\"".getthemelocation()."img/folder.png\" title=\"".getescapedtext ($hcms_lang['location'][$lang])."\" class=\"hcmsIconList\" />&nbsp;</td>
      <td style=\"white-space:nowrap; overflow:hidden; text-overflow:ellipsis;\" title=\"".str_replace ("/", " &gt; ", trim ($location_name, "/"))."\">".str_replace ("/", " &gt; ", trim ($location_name, "/"))."</td>";
      }
      else 
      {
        echo "
      <td style=\"white-space:nowrap; width:20px;\">&nbsp;</td>
      <td>&nbsp;</td>";    
      }
      ?>
    </tr>
    <tr>
      <?php
      // object
      if (empty ($file_info['icon'])) $file_info['icon'] = "Null_media.gif";

      echo "
      <td style=\"white-space:nowrap; width:20px;\"><img src=\"".getthemelocation()."img/".$file_info['icon']."\" title=\"".$item."\" class=\"hcmsIconList\" />&nbsp;</td>
      <td style=\"white-space:nowrap; overflow:hidden; text-overflow:ellipsis;\">".$object_name."</td>";
      ?>
    </tr>
  </table>
  <?php } else { ?>
  <span style="width:100%; display:block; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;"><?php echo showshorttext (str_replace ("/", " &gt; ", trim ($location_name, "/"))." &gt; ".$object_name, 44, false); ?></span>
  <?php } ?>
</div>

<!-- toolbar -->
<div class="hcmsToolbar" style="<?php if (!$is_mobile) echo "white-space:nowrap; min-width:820px;"; else echo "max-height:100px;"; ?>">
  <div class="hcmsToolbarBlock">
    <?php
    // parent folder of current location
    if (
         (
           $from_page == "" && valid_locationname ($abs_path_root) && valid_locationname ($location_down) && substr_count ($abs_path_root, $location) < 1 && $setlocalpermission_down['root'] == 1
         ) 
         || 
         ( 
           linking_valid() && linking_inscope ($site, $location, "", $cat)
         )
       )
    {
       echo "
       <img ".
       "onclick=\"if (locklayer == false) parent.location='frameset_objectlist.php?site=".url_encode($site)."&cat=".url_encode($cat)."&location=".url_encode($location_down_esc)."';\" ".
       "class=\"hcmsButton hcmsButtonSizeSquare\" ".
       "name=\"pic_folder_back\" ".
       "src=\"".getthemelocation($hcms_themeinvertcolors)."img/button_back.png\" ".
       "alt=\"".getescapedtext($hcms_lang['go-to-parent-folder'][$lang])."\" title=\"".getescapedtext($hcms_lang['go-to-parent-folder'][$lang])."\" />";
    }
    // parent folder of current object (used for search results)
    elseif (
             $from_page != "" && $multiobject_count <= 1 && ($page != "" || $folder != "") && valid_locationname ($abs_path_root) && valid_locationname ($location_down) && @substr_count ($abs_path_root, $location) < 1 && $setlocalpermission_down['root'] == 1
           )
    {
      echo "
      <img ".
      "onclick=\"if (locklayer == false) parent.location='frameset_objectlist.php?site=".url_encode($site)."&cat=".url_encode($cat)."&location=".url_encode($location_esc)."';\" ".
      "class=\"hcmsButton hcmsButtonSizeSquare\" ".
      "name=\"pic_folder_back\" ".
      "src=\"".getthemelocation($hcms_themeinvertcolors)."img/button_back.png\" ".
      "alt=\"".getescapedtext($hcms_lang['go-to-parent-folder'][$lang])."\" title=\"".getescapedtext($hcms_lang['go-to-parent-folder'][$lang])."\" />";
    }
    else
    {
      echo "
      <img src=\"".getthemelocation($hcms_themeinvertcolors)."img/button_back.png\" class=\"hcmsButtonOff hcmsButtonSizeSquare\" />";
    }
    ?> 
  </div>
  
  <?php if (!$is_mobile) { ?>
  <div class="hcmsToolbarBlock">
    <?php
    // Filter
    if ($from_page == "" && $cat != "page")
    {
      echo "
    <img src=\"".getthemelocation($hcms_themeinvertcolors)."img/button_filter.png\" class=\"hcmsButton hcmsButtonSizeSquare\" onclick=\"switchFilter();\" title=\"".getescapedtext($hcms_lang['filter-by-file-type'][$lang])."\" alt=\"".getescapedtext($hcms_lang['filter-by-file-type'][$lang])."\" />";
    }
    else
    {
      echo "
    <img src=\"".getthemelocation($hcms_themeinvertcolors)."img/button_filter.png\" class=\"hcmsButtonOff hcmsButtonSizeSquare\" />";
    }
    ?>    
  </div>
  <?php } ?>

  <?php if (empty ($toolbarfunctions) || !empty ($toolbarfunctions['edit'])) { ?>
  <div class="hcmsToolbarBlock">
    <?php
    // object edit buttons
    echo "
    <div id=\"button_obj_edit\" onclick=\"hcms_hideSelector('select_obj_view'); hcms_hideSelector('select_obj_convert'); hcms_switchSelector('select_obj_edit');\" class=\"hcmsButton hcmsButtonSizeWide\">
      <img src=\"".getthemelocation($hcms_themeinvertcolors)."img/button_edit.png\" class=\"hcmsButtonSizeSquare\" id=\"pic_obj_edit\" name=\"pic_obj_edit\" alt=\"".getescapedtext($hcms_lang['edit'][$lang])."\" title=\"".getescapedtext($hcms_lang['edit'][$lang])."\" /><img src=\"".getthemelocation($hcms_themeinvertcolors)."img/pointer_select.png\" class=\"hcmsButtonSizeNarrow\" alt=\"".getescapedtext ($hcms_lang['edit'][$lang])."\" title=\"".getescapedtext ($hcms_lang['edit'][$lang])."\" />

      <div id=\"select_obj_edit\" class=\"hcmsSelector\" style=\"position:relative; top:-52px; left:36px; visibility:hidden; z-index:999; width:".(36*7)."px; max-height:38px; overflow:auto; overflow-x:auto; overflow-y:hidden; white-space:nowrap;\">";

    // Edit Button   
    if (
         $multiobject_count <= 1 && $page != "" && 
         $from_page != "recyclebin" && 
         (($media == "" && $setlocalpermission['root'] == 1 && $setlocalpermission['create'] == 1) || 
         ($media != "" && $setlocalpermission['root'] == 1 && $setlocalpermission['upload'] == 1))
    )
    {
      if (!empty ($mgmt_config['object_newwindow']))
      {
        if ($page != ".folder") $openlink = "hcms_openWindow('frameset_content.php?site=".url_encode($site)."&ctrlreload=yes&cat=".url_encode($cat)."&location=".url_encode($location_esc)."&page=".url_encode($page)."', '".$container_id."', 'location=no,status=yes,scrollbars=no,resizable=yes,titlebar=no', ".windowwidth("object").", ".windowheight("object")."); ";
        else $openlink = "hcms_openWindow('frameset_content.php?site=".url_encode($site)."&ctrlreload=yes&cat=".url_encode($cat)."&location=".url_encode($location_esc.$folder)."/&page=".url_encode($page)."', '".$container_id."', 'location=no,status=yes,scrollbars=no,resizable=yes,titlebar=no', ".windowwidth("object").", ".windowheight("object")."); ";
      }
      else
      {
        if ($page != ".folder") $openlink = "submitToMainFrame('frameset_content.php?site=".url_encode($site)."&ctrlreload=yes&cat=".url_encode($cat)."&location=".url_encode($location_esc)."&page=".url_encode($page)."', ''); ";
        else $openlink = "submitToMainFrame('frameset_content.php?site=".url_encode($site)."&ctrlreload=yes&cat=".url_encode($cat)."&location=".url_encode($location_esc.$folder)."/&page=".url_encode($page)."', ''); ";
      }

      echo "
      <img onclick=\"document.getElementById('button_obj_edit').display='none'; ".$openlink."\" class=\"hcmsButton hcmsButtonSizeSquare\" name=\"pic_obj_edit\" src=\"".getthemelocation()."img/button_edit.png\" alt=\"".getescapedtext($hcms_lang['edit'][$lang])."\" title=\"".getescapedtext ($hcms_lang['edit'][$lang])."\" />";
    }
    // Edit button to edit the fields which are equal across all selected objects
    elseif ($multiobject_count > 1 && $from_page != "recyclebin")
    {
      if (!empty ($mgmt_config['object_newwindow'])) $openlink = "submitToWindow('page_multiedit.php', '', 'multiedit', 'status=yes,scrollbars=yes,resizable=yes', ".windowwidth("object").", ".windowheight("object").");";
      else $openlink = "submitToMainFrame('page_multiedit.php', '');";

      echo "
      <img onclick=\"document.getElementById('button_obj_edit').display='none'; ".$openlink."\" class=\"hcmsButton hcmsButtonSizeSquare\" name=\"pic_obj_edit\" src=\"".getthemelocation()."img/button_edit.png\" alt=\"".getescapedtext ($hcms_lang['edit'][$lang])."\" title=\"".getescapedtext ($hcms_lang['edit'][$lang])."\" />";
    }
    else 
    {
      echo "
      <img src=\"".getthemelocation()."img/button_edit.png\" class=\"hcmsButtonOff hcmsButtonSizeSquare\" />";
    }
    
    // Rename Object Button
    if ($multiobject_count <= 1 && $from_page != "recyclebin" && $page != "" && $page != ".folder" && $setlocalpermission['root'] == 1 && $setlocalpermission['rename'] == 1)
    {
      echo "
      <img class=\"hcmsButton hcmsButtonSizeSquare\" onclick=\"hcms_showHideLayers(".
                                                      "'foldercreateLayer','','hide',".
                                                      "'folderrenameLayer','','hide',".
                                                      "'fileuploadLayer','','hide',".
                                                      "'objrenameLayer','','show',".
                                                      "'importLayer','','hide',".
                                                      "'hcms_messageLayer','','hide'); document.getElementById('button_obj_edit').display='none';\" name=\"pic_obj_rename\" src=\"".getthemelocation()."img/button_rename.png\" alt=\"".getescapedtext ($hcms_lang['rename'][$lang])."\" title=\"".getescapedtext ($hcms_lang['rename'][$lang])."\" />";
    }
    // Rename Folder Button
    elseif ($multiobject_count <= 1 && $from_page != "recyclebin" && $folder != "" && $setlocalpermission['root'] == 1 && $setlocalpermission['folderrename'] == 1)
    {
      echo "
      <img class=\"hcmsButton hcmsButtonSizeSquare\" onclick=\"if (locklayer == false) hcms_showHideLayers(".
                                                      "'foldercreateLayer','','hide',".
                                                      "'folderrenameLayer','','show',".
                                                      "'fileuploadLayer','','hide',".
                                                      "'objrenameLayer','','hide',".
                                                      "'importLayer','','hide',".
                                                      "'hcms_messageLayer','','hide'); document.getElementById('button_obj_edit').display='none';\" name=\"pic_folder_rename\" src=\"".getthemelocation()."img/button_rename.png\" alt=\"".getescapedtext ($hcms_lang['rename-folder'][$lang])."\" title=\"".getescapedtext ($hcms_lang['rename-folder'][$lang])."\" />";
    }
    else
    {
      echo "
      <img src=\"".getthemelocation()."img/button_rename.png\" class=\"hcmsButtonOff hcmsButtonSizeSquare\" />";
    }
    
    // Delete Button
    if (
         ($usedby == "" || $usedby == $user) && 
         (
           (($multiobject_count > 0 || ($page != ".folder" && $page != "")) && $setlocalpermission['root'] == 1 && $setlocalpermission['delete'] == 1) || 
           (($multiobject_count >  0 || $folder != "" || $page == ".folder") && $setlocalpermission['root'] == 1 && $setlocalpermission['folderdelete'] == 1)
         )
       )
    {
      echo "
      <img onclick=\"if (locklayer == false) checkForm_delete(); document.getElementById('button_obj_edit').display='none';\" ".
        "class=\"hcmsButton hcmsButtonSizeSquare\" name=\"pic_obj_delete\" src=\"".getthemelocation()."img/button_delete.png\" alt=\"".getescapedtext ($hcms_lang['delete'][$lang])."\" title=\"".getescapedtext ($hcms_lang['delete'][$lang])."\" />";
    }
    else
    {
      echo "
      <img src=\"".getthemelocation()."img/button_delete.png\" class=\"hcmsButtonOff hcmsButtonSizeSquare\" />";
    }    
    ?>
    
    <?php
    // Cut, Copy, Linked-Copy Button
    if (($multiobject_count > 0 || ($page != ".folder" && $page != "")) && $from_page != "recyclebin")
    {
      if ($setlocalpermission['root'] == 1 && $setlocalpermission['rename'] == 1)
      {
        echo "
        <img onclick=\"if (locklayer == false) submitToPopup('popup_action.php', 'cut', 'cut".uniqid()."'); document.getElementById('button_obj_edit').display='none';\" ".
          "class=\"hcmsButton hcmsButtonSizeSquare\" ".
          "name=\"pic_obj_cut\" ".
          "src=\"".getthemelocation()."img/button_file_cut.png\" alt=\"".getescapedtext ($hcms_lang['cut'][$lang])."\" title=\"".getescapedtext ($hcms_lang['cut'][$lang])."\" />";
      }
      else
      {
        echo "
        <img src=\"".getthemelocation()."img/button_file_cut.png\" class=\"hcmsButtonOff hcmsButtonSizeSquare\" />";
      }
  
      if ($setlocalpermission['root'] == 1 && $setlocalpermission['rename'] == 1)
      {
        echo "
        <img onclick=\"if (locklayer == false) submitToPopup('popup_action.php', 'copy', 'copy".uniqid()."'); document.getElementById('button_obj_edit').display='none';\" ".
          "class=\"hcmsButton hcmsButtonSizeSquare\" ".
          "name=\"pic_obj_copy\" ".
          "src=\"".getthemelocation()."img/button_file_copy.png\" alt=\"".getescapedtext ($hcms_lang['copy'][$lang])."\" title=\"".getescapedtext ($hcms_lang['copy'][$lang])."\" />";
      }
      else
      {
        echo "
        <img src=\"".getthemelocation()."img/button_file_copy.png\" class=\"hcmsButtonOff hcmsButtonSizeSquare\">";
      }
      
      if ($setlocalpermission['root'] == 1 && $setlocalpermission['rename'] == 1)
      {
        echo "
        <img onclick=\"if (locklayer == false) submitToPopup('popup_action.php', 'linkcopy', 'linkcopy".uniqid()."'); document.getElementById('button_obj_edit').display='none';\" ".
          "class=\"hcmsButton hcmsButtonSizeSquare\" ".
          "name=\"pic_obj_linkedcopy\" ".
          "src=\"".getthemelocation()."img/button_file_copylinked.png\" alt=\"".getescapedtext ($hcms_lang['connected-copy'][$lang])."\" title=\"".getescapedtext ($hcms_lang['connected-copy'][$lang])."\">";
      }
      else
      {
        echo "
        <img src=\"".getthemelocation()."img/button_file_copylinked.png\" class=\"hcmsButtonOff hcmsButtonSizeSquare\" />";
      }    
    }
    elseif (($multiobject_count > 0 || $folder != "") && $from_page != "recyclebin")
    {
      if ($setlocalpermission['root'] == 1 && $setlocalpermission['folderrename'] == 1)
      {
        echo "
        <img onclick=\"if (locklayer == false) submitToPopup('popup_action.php', 'cut', 'cut".uniqid()."'); document.getElementById('button_obj_edit').display='none';\" ".
          "class=\"hcmsButton hcmsButtonSizeSquare\" name=\"pic_obj_cut\" src=\"".getthemelocation()."img/button_file_cut.png\" alt=\"".getescapedtext ($hcms_lang['cut'][$lang])."\" title=\"".getescapedtext ($hcms_lang['cut'][$lang])."\" /></a>";
      }
      else
      {
        echo "
        <img src=\"".getthemelocation()."img/button_file_cut.png\" class=\"hcmsButtonOff hcmsButtonSizeSquare\" />";
      }
  
      if ($setlocalpermission['root'] == 1 && $setlocalpermission['folderrename'] == 1)
      {
        echo "
        <img onclick=\"if (locklayer == false) submitToPopup('popup_action.php', 'copy', 'copy".uniqid()."'); document.getElementById('button_obj_edit').display='none';\" ".
          "class=\"hcmsButton hcmsButtonSizeSquare\" name=\"pic_obj_copy\" src=\"".getthemelocation()."img/button_file_copy.png\" alt=\"".getescapedtext ($hcms_lang['copy'][$lang])."\" title=\"".getescapedtext ($hcms_lang['copy'][$lang])."\" /></a>";
      }
      else
      {
        echo "
        <img src=\"".getthemelocation()."img/button_file_copy.png\" class=\"hcmsButtonOff hcmsButtonSizeSquare\" />";
      }
      
      if ($setlocalpermission['root'] == 1 && $setlocalpermission['folderrename'] == 1 && $setlocalpermission['foldercreate'] == 1)
      {
        echo "
        <img onclick=\"if (locklayer == false) submitToPopup('popup_action.php', 'linkcopy', 'linkcopy".uniqid()."'); document.getElementById('button_obj_edit').display='none';\" ".
          "class=\"hcmsButton hcmsButtonSizeSquare\" name=\"pic_obj_linkedcopy\" src=\"".getthemelocation()."img/button_file_copylinked.png\" alt=\"".getescapedtext ($hcms_lang['connected-copy'][$lang])."\" title=\"".getescapedtext ($hcms_lang['connected-copy'][$lang])."\" />";
      }
      else
      {
        echo "
        <img src=\"".getthemelocation()."img/button_file_copylinked.png\" class=\"hcmsButtonOff hcmsButtonSizeSquare\" />";
      }      
    }
    else
    {
      echo "
      <img src=\"".getthemelocation()."img/button_file_cut.png\" class=\"hcmsButtonOff hcmsButtonSizeSquare\" />
      <img src=\"".getthemelocation()."img/button_file_copy.png\" class=\"hcmsButtonOff hcmsButtonSizeSquare\" />
      <img src=\"".getthemelocation()."img/button_file_copylinked.png\" class=\"hcmsButtonOff hcmsButtonSizeSquare\" />";
    }
     // Paste Button
    if ($from_page == "" && ($setlocalpermission['root'] == 1 && ($setlocalpermission['rename'] == 1 || $setlocalpermission['folderrename'] == 1)))
    {
      echo "
      <img onclick=\"if (locklayer == false) submitToPopup('popup_status.php', 'paste', 'paste".uniqid()."'); document.getElementById('button_obj_edit').display='none';\" ".
        "class=\"hcmsButton hcmsButtonSizeSquare\" name=\"pic_obj_paste\" src=\"".getthemelocation()."img/button_file_paste.png\" alt=\"".getescapedtext ($hcms_lang['paste'][$lang])."\" title=\"".getescapedtext ($hcms_lang['paste'][$lang])."\" />";
    }
    else
    {
      echo "
      <img src=\"".getthemelocation()."img/button_file_paste.png\" class=\"hcmsButtonOff hcmsButtonSizeSquare\" />";
    }

    echo "
      </div>
    </div>";
    ?>    
  </div>
  <?php } ?>

  <?php if (empty ($toolbarfunctions) || !empty ($toolbarfunctions['preview'])) { ?>
  <div class="hcmsToolbarBlock">
    <?php
    // Preview Button
    if ($multiobject_count <= 1 && $page != "" && $cat != "" && $setlocalpermission['root'] == 1)
    {
      if ($page != ".folder") echo "
      <img onclick=\"openObjectView('".$location_esc."', '".$page."', 'preview');\" class=\"hcmsButton hcmsButtonSizeSquare\" name=\"pic_obj_preview\" src=\"".getthemelocation($hcms_themeinvertcolors)."img/button_file_preview.png\" alt=\"".getescapedtext ($hcms_lang['preview'][$lang])."\" title=\"".getescapedtext ($hcms_lang['preview'][$lang])."\" />";
      elseif ($page == ".folder") echo "
      <img onclick=\"openObjectView('".$location_esc.$folder."/', '".$page."', 'preview');\" class=\"hcmsButton hcmsButtonSizeSquare\" name=\"pic_obj_preview\" src=\"".getthemelocation($hcms_themeinvertcolors)."img/button_file_preview.png\" alt=\"".getescapedtext ($hcms_lang['preview'][$lang])."\" title=\"".getescapedtext ($hcms_lang['preview'][$lang])."\" />";
    }
    else echo "
    <img src=\"".getthemelocation($hcms_themeinvertcolors)."img/button_file_preview.png\" class=\"hcmsButtonOff hcmsButtonSizeSquare\" />";

    // Live-View Button
    if ($multiobject_count <= 1 && $page != "" && $from_page != "recyclebin" && 
        !empty ($file_info['published']) && $page != ".folder" && valid_publicationname ($site) && 
        $setlocalpermission['root'] == 1 && 
        empty ($media) && $cat == "page"
    )
    {
      echo "
    <img onclick=\"openObjectView('".$location_esc."', '".$page."', 'liveview');\" class=\"hcmsButton hcmsButtonSizeSquare\" name=\"pic_obj_liveview\" src=\"".getthemelocation($hcms_themeinvertcolors)."img/button_file_liveview.png\" alt=\"".getescapedtext ($hcms_lang['view-live'][$lang])."\" title=\"".getescapedtext ($hcms_lang['view-live'][$lang])."\" />";
    }
    else echo "
    <img src=\"".getthemelocation($hcms_themeinvertcolors)."img/button_file_liveview.png\" class=\"hcmsButtonOff hcmsButtonSizeSquare\" />";
    ?>
  </div>
  <?php } ?>
  
  <?php if (empty ($toolbarfunctions) || !empty ($toolbarfunctions['updownload'])) { ?>
  <div class="hcmsToolbarBlock">
    <?php
    // Upload Button (HTML5 file upload)
    if ($from_page == "" && ($cat != "page" || !empty ($mgmt_config[$site]['upload_pages'])) && $setlocalpermission['root'] == 1 && $setlocalpermission['upload'] == 1)
    {
      echo "
      <img class=\"hcmsButton hcmsButtonSizeSquare\" onclick=\"window.top.openPopup('popup_upload_html.php?uploadmode=multi&site=".url_encode($site)."&cat=".url_encode($cat)."&location=".url_encode($location_esc)."', 'upload".substr(md5($location_esc), 0, 13)."');\" ".
        "name=\"pic_obj_upload\" src=\"".getthemelocation($hcms_themeinvertcolors)."img/button_file_upload.png\" alt=\"".getescapedtext ($hcms_lang['upload-file'][$lang])."\" title=\"".getescapedtext ($hcms_lang['upload-file'][$lang])."\" />";
    }
    else
    {
      echo "
      <img src=\"".getthemelocation($hcms_themeinvertcolors)."img/button_file_upload.png\" class=\"hcmsButtonOff hcmsButtonSizeSquare\" />";
    }

    // Download/Convert Button (also for folders)
    $media_info = getfileinfo ($site, $media, $cat);

    // get media file extension
    if (!empty ($media) && !empty ($mgmt_imagepreview) && is_array ($mgmt_imagepreview))
    {
      if (!empty ($media_info['ext']))
      {
        $doc_rendering = is_supported ($mgmt_docpreview, $media_info['ext']) && is_array ($mgmt_docconvert) && array_key_exists ($media_info['ext'], $mgmt_docconvert);

        foreach ($mgmt_imagepreview as $imgpreview_ext => $imgpreview)
        {
          // check file extension
          if (substr_count ($imgpreview_ext.".", $media_info['ext'].".") > 0 && trim ($imgpreview) != "")
          {
            // check if there are more options for providing the image in other formats
            if (!empty ($mgmt_imageoptions) && is_array ($mgmt_imageoptions) && !empty($mgmt_imageoptions))
            {	
              foreach ($mgmt_imageoptions as $config_fileext => $config_array) 
              {
                foreach ($config_array as $config_name => $value) 
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

        if (!empty ($mgmt_mediapreview)) $vid_rendering = is_supported ($mgmt_mediapreview, $media_info['ext']);
      }
    }

    // rendering options
    // based on permissions (only if location is the same)
    $perm_rendering = ($setlocalpermission['root'] == 1 && $setlocalpermission['download'] == 1);
    $lock_rendering = ($usedby == "" || $usedby == $user);
    
    $dropbox_rendering = (is_array ($mgmt_config) && array_key_exists ("dropbox_appkey", $mgmt_config) && !empty ($mgmt_config['dropbox_appkey']));
    
    if ($from_page != "recyclebin" && ($multiobject_count <= 1 && !empty ($page) && !empty ($media)) && $perm_rendering && $lock_rendering && ($doc_rendering || $img_rendering || $dropbox_rendering))
    {
      echo "
      <div id=\"button_obj_convert\" class=\"hcmsButton hcmsButtonSizeWide\" onclick=\"hcms_hideSelector('select_obj_view'); hcms_hideSelector('select_obj_edit'); hcms_switchSelector('select_obj_convert');\">
        <img src=\"".getthemelocation($hcms_themeinvertcolors)."img/button_file_download.png\" class=\"hcmsButtonTinyBlank hcmsButtonSizeSquare\" id=\"pic_obj_convert\" name=\"pic_obj_convert\" alt=\"".getescapedtext ($hcms_lang['download-file'][$lang])."\" title=\"".getescapedtext ($hcms_lang['download-file'][$lang])."\" /><img src=\"".getthemelocation($hcms_themeinvertcolors)."img/pointer_select.png\" class=\"hcmsButtonTinyBlank hcmsButtonSizeNarrow\" alt=\"".getescapedtext ($hcms_lang['download-file'][$lang])."\" title=\"".getescapedtext ($hcms_lang['download-file'][$lang])."\" />

        <div id=\"select_obj_convert\" class=\"hcmsSelector\" style=\"position:relative; top:-52px; left:36px; visibility:hidden; z-index:999; width:180px; max-height:".($is_mobile ? "50" : "72")."px; overflow:auto; overflow-x:hidden; overflow-y:auto; white-space:nowrap;\">";
        
      // original file
      if (empty ($downloadformats) || (!is_document ($media_info['ext']) && !is_image ($media_info['ext']) && !is_video ($media_info['ext'])) || (is_document ($media_info['ext']) && !empty ($downloadformats['document']['original'])) || (is_image ($media_info['ext']) && !empty ($downloadformats['image']['original'])) || (is_video ($media_info['ext']) && !empty ($downloadformats['video']['original'])))
      {
        // function imgConvert must be used in order to reset the rendering options
        echo "
          <div class=\"hcmsSelectorItem\" onclick=\"imgConvert ('','');\"><img src=\"".getthemelocation()."img/".$media_info['icon']."\" class=\"hcmsIconList\" /> ".getescapedtext ($hcms_lang['original'][$lang])."</div>";
      }
      
      // document download options
      if ($doc_rendering && !empty ($mgmt_docoptions) && is_array ($mgmt_docoptions))
      {
        foreach ($mgmt_docoptions as $ext => $value)
        {
          if ($ext != "" && $value != "")
          {
            $ext_array = explode (".", trim ($ext, "."));
            $doc_type = $ext_array[0];          
            $doc_info = getfileinfo ($site, "file".$ext, "comp");

            if ((empty ($downloadformats) || !empty ($downloadformats['document'][$doc_type])) && in_array ($ext, $mgmt_docconvert[$media_info['ext']]))
            {
              echo "
            <div class=\"hcmsSelectorItem\" onclick=\"docConvert ('".$doc_type."');\"><img src=\"".getthemelocation()."img/".$doc_info['icon']."\" class=\"hcmsIconList\" /> ".$doc_info['type']." (".strtoupper($doc_type).")</div>";
            }
          }
        }
      }
      
      // image download options
      if ($img_rendering && !empty ($mgmt_imageoptions) && is_array ($mgmt_imageoptions))
      {
        foreach ($mgmt_imageoptions as $ext => $config_array) 
        {
          if (is_array ($config_array)) 
          {
            $ext_array = explode (".", trim ($ext, "."));
            $image_type = $ext_array[0];
            $img_info = getfileinfo ($site, $media_info['filename'].".".$image_type, $cat);
            
            foreach ($config_array as $config_name => $config_parameter) 
            {
              if ((empty ($downloadformats) || !empty ($downloadformats['image'][$image_type][$config_name])) && $config_name != "thumbnail" && $config_name != "original") 
              {
                echo "
            <div class=\"hcmsSelectorItem\" onclick=\"imgConvert ('".$image_type."', '".$config_name."');\"><img src=\"".getthemelocation()."img/".$img_info['icon']."\" class=\"hcmsIconList\" /> ".strtoupper($image_type)." ".$config_name."</div>";
              }
            }
          }
        }
      }
      
      // video download options
      if ($vid_rendering && is_video ($media))
      {
        if (empty ($downloadformats) || !empty ($downloadformats['video']['origthumb'])) echo "
          <div class=\"hcmsSelectorItem\" onclick=\"vidConvert('origthumb');\"><img src=\"".getthemelocation()."img/file_mpg.png\" class=\"hcmsIconList\" /> ".getescapedtext ($hcms_lang['preview'][$lang])."</div>";
          
        if (empty ($downloadformats) || !empty ($downloadformats['video']['jpg'])) echo "
          <div class=\"hcmsSelectorItem\" onclick=\"vidConvert('jpg');\"><img src=\"".getthemelocation()."img/file_image.png\" class=\"hcmsIconList\" /> ".getescapedtext ($hcms_lang['images'][$lang])." (JPG)</div>";
          
        if (empty ($downloadformats) || !empty ($downloadformats['video']['png'])) echo "
          <div class=\"hcmsSelectorItem\" onclick=\"vidConvert('png');\"><img src=\"".getthemelocation()."img/file_image.png\" class=\"hcmsIconList\" /> ".getescapedtext ($hcms_lang['images'][$lang])." (PNG)</div>";
      }
			
			// save to dropbox
			if ($dropbox_rendering)
			{
				echo "
          <div class=\"hcmsSelectorItem\" onclick=\"submitToWindow('popup_save_dropbox.php', 'Save to Dropbox', '', 'location=no,menubar=no,toolbar=no,titlebar=no,status=yes,scrollbars=yes,resizable=yes,width=600,height=400', 600, 400); document.getElementById('button_obj_convert').click();a\"><img src=\"".getthemelocation()."img/file_dropbox.png\" class=\"hcmsIconList\" /> ".getescapedtext ($hcms_lang['dropbox'][$lang])."</div>";
			}
      
      echo "
        </div>
      </div>";
    }
    // file and folder download without options
    elseif ($from_page != "recyclebin" && 
      (
        ($multiobject_count <= 1 && !empty ($page) && !empty ($media) && $perm_rendering && $lock_rendering) || 
        (($multiobject_count > 1 || $page == ".folder") && ($from_page != "" || ($from_page == "" && $perm_rendering && $lock_rendering)))
      )
    )
    {
      echo "
      <div class=\"hcmsButton hcmsButtonSizeWide\" onclick=\"submitToSelf('download'); hcms_showHideLayers('downloadLayer','','show');\"><img class=\"hcmsButtonTinyBlank hcmsButtonSizeSquare\" name=\"pic_obj_liveview\" src=\"".getthemelocation($hcms_themeinvertcolors)."img/button_file_download.png\" alt=\"".getescapedtext ($hcms_lang['download-file'][$lang])."\" title=\"".getescapedtext ($hcms_lang['download-file'][$lang])."\" /><img src=\"".getthemelocation($hcms_themeinvertcolors)."img/pointer_select.png\" class=\"hcmsButtonTinyBlank hcmsButtonSizeNarrow\" alt=\"".getescapedtext ($hcms_lang['download-file'][$lang])."\" title=\"".getescapedtext ($hcms_lang['download-file'][$lang])."\" /></div>";
    }
    else
    {
      echo "
      <div class=\"hcmsButtonOff hcmsButtonSizeWide\"><img src=\"".getthemelocation($hcms_themeinvertcolors)."img/button_file_download.png\" class=\"hcmsButtonSizeSquare\" /><img src=\"".getthemelocation($hcms_themeinvertcolors)."img/pointer_select.png\" class=\"hcmsButtonSizeNarrow\" alt=\"".getescapedtext ($hcms_lang['download-file'][$lang])."\" title=\"".getescapedtext ($hcms_lang['download-file'][$lang])."\" /></div>";
    }
    ?>    
  </div>
  <?php } ?>
  
  <?php if (empty ($toolbarfunctions) || !empty ($toolbarfunctions['create'])) { ?>
  <div class="hcmsToolbarBlock">
    <?php
    // New Folder Button
    if ($from_page == "" && $setlocalpermission['root'] == 1 && $setlocalpermission['foldercreate'] == 1)
    {
      echo "
      <img class=\"hcmsButton hcmsButtonSizeSquare\" ".
           "onclick=\"if (locklayer == false) hcms_showHideLayers(".
                                                      "'select_obj_view','','hide',".
                                                      "'select_obj_edit','','hide',".
                                                      "'select_obj_convert','','hide',".
                                                      "'foldercreateLayer','','show',".
                                                      "'folderrenameLayer','','hide',".
                                                      "'fileuploadLayer','','hide',".
                                                      "'objrenameLayer','','hide',".
                                                      "'importLayer','','hide',".
                                                      "'hcms_messageLayer','','hide'".
                                                      ");\" name=\"pic_folder_create\" src=\"".getthemelocation($hcms_themeinvertcolors)."img/button_folder_new.png\" alt=\"".getescapedtext ($hcms_lang['create-folder'][$lang])."\" title=\"".getescapedtext ($hcms_lang['create-folder'][$lang])."\" />";
    }
    else
    {
      echo "
      <img src=\"".getthemelocation($hcms_themeinvertcolors)."img/button_folder_new.png\" class=\"hcmsButtonOff hcmsButtonSizeSquare\" />";
    }

    // New Object Button
    if ($from_page == "" && $setlocalpermission['root'] == 1 && $setlocalpermission['create'] == 1)
    {
      echo "
      <img class=\"hcmsButton hcmsButtonSizeSquare\" onclick=\"hcms_openWindow('frameset_content.php?site=".url_encode($site)."&cat=".url_encode($cat)."&location=".url_encode($location_esc)."', '', 'location=no,menubar=no,toolbar=no,titlebar=no,status=yes,scrollbars=no,resizable=yes', ".windowwidth("object").", ".windowheight("object").");\" name=\"pic_obj_new\" src=\"".getthemelocation($hcms_themeinvertcolors)."img/button_file_new.png\" alt=\"".getescapedtext ($hcms_lang['new-object'][$lang])."\" title=\"".getescapedtext ($hcms_lang['new-object'][$lang])."\" />";
    }
    else
    {
      echo "
      <img src=\"".getthemelocation($hcms_themeinvertcolors)."img/button_file_new.png\" class=\"hcmsButtonOff hcmsButtonSizeSquare\" />";
    }
    ?>    
  </div>
  <?php } ?>
  
  <?php if (empty ($toolbarfunctions) || !empty ($toolbarfunctions['unzip'])) { ?>
  <div class="hcmsToolbarBlock">
    <?php
    // ZIP Button
    if ($from_page == "" && !empty ($mgmt_compress['.zip']) && 
        ($usedby == "" || $usedby == $user) && 
         ($multiobject_count > 0 || $page != "") &&  
         $setlocalpermission['root'] == 1 && $setlocalpermission['create'] == 1 && $cat != "page"
       )
    {
      echo "
      <img class=\"hcmsButton hcmsButtonSizeSquare\" ".
           "onclick=\"if (locklayer == false) hcms_showHideLayers(".
                                                      "'select_obj_view','','hide',".
                                                      "'select_obj_edit','','hide',".
                                                      "'select_obj_convert','','hide',".
                                                      "'foldercreateLayer','','hide',".
                                                      "'folderrenameLayer','','hide',".
                                                      "'fileuploadLayer','','hide',".
                                                      "'objrenameLayer','','hide',".
                                                      "'importLayer','','hide',".
                                                      "'hcms_messageLayer','','hide',".
                                                      "'zipLayer','','show'".
                                                      ");\" name=\"pic_zip_create\" src=\"".getthemelocation($hcms_themeinvertcolors)."img/button_zip.png\" alt=\"".getescapedtext ($hcms_lang['compress-files'][$lang])."\" title=\"".getescapedtext ($hcms_lang['compress-files'][$lang])."\" />\n";
    }
    else echo "
    <img src=\"".getthemelocation($hcms_themeinvertcolors)."img/button_zip.png\" class=\"hcmsButtonOff hcmsButtonSizeSquare\" />";

    // UNZIP Button
    if ( 
         !empty ($mgmt_uncompress['.zip']) && $from_page == "" && 
         $multiobject_count <= 1 &&
         $filetype == "compressed" &&
         ($usedby == "" || $usedby == $user) && 
         $page != "" && 
         $setlocalpermission['root'] == 1
       )
    {    
      echo "
    <img class=\"hcmsButton hcmsButtonSizeSquare\" ".
        "onclick=\"if (locklayer == false) unzip('unzip".uniqid()."');\" ".
        "name=\"pic_unzip\" src=\"".getthemelocation($hcms_themeinvertcolors)."img/button_unzip.png\" alt=\"".getescapedtext ($hcms_lang['extract-file'][$lang])."\" title=\"".getescapedtext ($hcms_lang['extract-file'][$lang])."\" />";
    }
    else echo "
    <img src=\"".getthemelocation($hcms_themeinvertcolors)."img/button_unzip.png\" class=\"hcmsButtonOff hcmsButtonSizeSquare\" />";
    ?>    
  </div>
  <?php } ?>

  <?php if (empty ($toolbarfunctions) || !empty ($toolbarfunctions['share'])) { ?>
  <div class="hcmsToolbarBlock">       
    <?php    
    // Send Mail Button
    if (($multiobject_count > 0 || $page != "") && $from_page != "recyclebin" && !empty ($mgmt_config['smtp_host']) && !empty ($mgmt_config[$site]['sendmail']) && $setlocalpermission['root'] == 1 && $setlocalpermission['sendlink'] == 1 && !empty ($mgmt_config['db_connect_rdbms']))
    {
      echo "
    <img class=\"hcmsButton hcmsButtonSizeSquare\" name=\"pic_obj_preview\" ";
    
      if (!empty ($mgmt_config['message_newwindow'])) echo "onclick=\"submitToWindow('user_sendlink.php', '', 'sendlink', 'location=no,menubar=no,toolbar=no,titlebar=no,scrollbars=yes,resizable=no', 540, 800);\" ";
      else echo "onclick=\"submitToFrame('user_sendlink.php', 'sendlink');\" ";
      
      echo  "src=\"".getthemelocation($hcms_themeinvertcolors)."img/button_user_sendlink.png\" ".
      "alt=\"".getescapedtext ($hcms_lang['send-mail-link'][$lang])."\" title=\"".getescapedtext ($hcms_lang['send-mail-link'][$lang])."\" />";
    }
    else
    {
      echo "
    <img src=\"".getthemelocation($hcms_themeinvertcolors)."img/button_user_sendlink.png\" class=\"hcmsButtonOff hcmsButtonSizeSquare\" />";
    }

    // Send to Chat Button
    if ($is_mobile && !$is_iphone)
    {
      if (($multiobject_count > 0 || $page != "") && $from_page != "recyclebin" && $setlocalpermission['root'] == 1 && !empty ($mgmt_config['chat']))
      {
        if ($page != ".folder") $chatcontent = "hcms_openWindow(\\'frameset_content.php?site=".url_encode($site)."&ctrlreload=yes&cat=".url_encode($cat)."&location=".url_encode($location_esc)."&page=".url_encode($page)."\\', \\'\\', \\'location=no,menubar=no,toolbar=no,titlebar=no,status=yes,scrollbars=no,resizable=yes\\', ".windowwidth("object").", ".windowheight("object").");";
        elseif ($page == ".folder") $chatcontent = "hcms_openWindow(\\'frameset_content.php?site=".url_encode($site)."&ctrlreload=yes&cat=".url_encode($cat)."&location=".url_encode($location_esc.$folder)."/&page=".url_encode($page)."\\', \\'\\', \\'location=no,menubar=no,toolbar=no,titlebar=no,status=yes,scrollbars=no,resizable=yes\\', ".windowwidth("object").", ".windowheight("object").");";
        
        echo "
      <img onclick=\"sendtochat('".$chatcontent."');\" ".
        "class=\"hcmsButton hcmsButtonSizeSquare\" name=\"pic_obj_chat\" ".
        "src=\"".getthemelocation($hcms_themeinvertcolors)."img/button_chat.png\" ".
        "alt=\"".getescapedtext ($hcms_lang['send-to-chat'][$lang])."\" title=\"".getescapedtext ($hcms_lang['send-to-chat'][$lang])."\" />";
      }
      else
      {
        echo "
      <img src=\"".getthemelocation($hcms_themeinvertcolors)."img/button_chat.png\" class=\"hcmsButtonOff hcmsButtonSizeSquare\" />";
      }
    }
    ?>
  </div>
  <?php } ?>
  
  <?php if (empty ($toolbarfunctions) || !empty ($toolbarfunctions['unpublish'])) { ?>
  <div class="hcmsToolbarBlock">
    <?php
    // un/publish object
    if (($multiobject_count > 0 || $page != "") && $page != ".folder" && $from_page != "recyclebin")
    {
      if (($filetype != "" || $multiobject != "") && $setlocalpermission['root'] == 1 && $setlocalpermission['publish'] == 1)
      {
        echo "
      <img onclick=\"if (locklayer == false) submitToPopup('popup_publish.php', 'publish', 'publish".uniqid()."');\" ".
        "class=\"hcmsButton hcmsButtonSizeSquare\" name=\"pic_obj_publish\" src=\"".getthemelocation($hcms_themeinvertcolors)."img/button_file_publish.png\" alt=\"".getescapedtext ($hcms_lang['publish'][$lang])."\" title=\"".getescapedtext ($hcms_lang['publish'][$lang])."\" />\n";
      }
      else
      {
        echo "
      <img src=\"".getthemelocation($hcms_themeinvertcolors)."img/button_file_publish.png\" class=\"hcmsButtonOff hcmsButtonSizeSquare\" />";
      }
  
      if (($filetype != "" || $multiobject != "") && $setlocalpermission['root'] == 1 && $setlocalpermission['publish'] == 1)
      {
      	echo "
      <img onclick=\"if (locklayer == false) submitToPopup('popup_publish.php', 'unpublish', 'unpublish".uniqid()."');\" ".
        "class=\"hcmsButton hcmsButtonSizeSquare\" name=\"pic_obj_unpublish\" src=\"".getthemelocation($hcms_themeinvertcolors)."img/button_file_unpublish.png\" alt=\"".getescapedtext ($hcms_lang['unpublish'][$lang])."\" title=\"".getescapedtext ($hcms_lang['unpublish'][$lang])."\" />";
      }
      else
      {
      	echo "
      <img src=\"".getthemelocation($hcms_themeinvertcolors)."img/button_file_unpublish.png\" class=\"hcmsButtonOff hcmsButtonSizeSquare\" />";
      }
    }
    // un/publish folder
    elseif (($multiobject_count > 0 || $page != "") && $folder != "" && $from_page != "recyclebin")
    {
      if ($setlocalpermission_ACCESS['root'] == 1 && $setlocalpermission_ACCESS['publish'] == 1)
      {
        echo "
      <img onclick=\"if (locklayer == false) submitToPopup('popup_publish.php', 'publish', 'publish".uniqid()."');\" ".	
        "class=\"hcmsButton hcmsButtonSizeSquare\" name=\"pic_obj_publish\" src=\"".getthemelocation($hcms_themeinvertcolors)."img/button_file_publish.png\" alt=\"".getescapedtext ($hcms_lang['publish'][$lang])."\" title=\"".getescapedtext ($hcms_lang['publish'][$lang])."\" />\n";
      }
      else
      {
        echo "<img src=\"".getthemelocation($hcms_themeinvertcolors)."img/button_file_publish.png\" class=\"hcmsButtonOff hcmsButtonSizeSquare\" />\n";
      }
  
      if ($setlocalpermission_ACCESS['root'] == 1 && $setlocalpermission_ACCESS['publish'] == 1)
      {
      	echo "
      <img onclick=\"if (locklayer == false) submitToPopup('popup_publish.php', 'unpublish', 'unpublish".uniqid()."');\" ".	
      	"class=\"hcmsButton hcmsButtonSizeSquare\" name=\"pic_obj_unpublish\" src=\"".getthemelocation($hcms_themeinvertcolors)."img/button_file_unpublish.png\" alt=\"".getescapedtext ($hcms_lang['unpublish'][$lang])."\" title=\"".getescapedtext ($hcms_lang['unpublish'][$lang])."\">\n";
      }
      else
      {
        echo "
      <img src=\"".getthemelocation($hcms_themeinvertcolors)."img/button_file_unpublish.png\" class=\"hcmsButtonOff hcmsButtonSizeSquare\" />\n";
      }    
    }
    // deactivate buttons
    else
    {
      echo "
     <img src=\"".getthemelocation($hcms_themeinvertcolors)."img/button_file_publish.png\" class=\"hcmsButtonOff hcmsButtonSizeSquare\" />
     <img src=\"".getthemelocation($hcms_themeinvertcolors)."img/button_file_unpublish.png\" class=\"hcmsButtonOff hcmsButtonSizeSquare\" />";
    }
    ?>    
  </div>
  <?php } ?>
  
  <?php if (!$is_mobile && (empty ($toolbarfunctions) || !empty ($toolbarfunctions['imexport']))) { ?>
  <div class="hcmsToolbarBlock">
    <?php
    // CSV import
    if ($setlocalpermission['root'] == 1 && $setlocalpermission['create'] == 1 && $from_page == "")
    {
      echo "
    <img onclick=\"if (locklayer == false) hcms_showHideLayers(".
                                                      "'select_obj_view','','hide',".
                                                      "'select_obj_edit','','hide',".
                                                      "'select_obj_convert','','hide',".
                                                      "'foldercreateLayer','','hide',".
                                                      "'folderrenameLayer','','hide',".
                                                      "'fileuploadLayer','','hide',".
                                                      "'objrenameLayer','','hide',".
                                                      "'importLayer','','show',".
                                                      "'hcms_messageLayer','','hide'".
                                                      ");\" class=\"hcmsButton hcmsButtonSizeSquare\" name=\"media_import\" src=\"".getthemelocation($hcms_themeinvertcolors)."img/button_import.png\" alt=\"".getescapedtext ($hcms_lang['import-list-comma-delimited'][$lang])."\" title=\"".getescapedtext ($hcms_lang['import-list-comma-delimited'][$lang])."\" />";
    }
    else
    {
      echo "
    <img src=\"".getthemelocation($hcms_themeinvertcolors)."img/button_import.png\" class=\"hcmsButtonOff hcmsButtonSizeSquare\" />";
    }
    
    // CSV export
    if (($usedby == "" || $usedby == $user) && ($page != "" || $multiobject_count > 0) && $mgmt_config['db_connect_rdbms'] != "" && $from_page != "recyclebin")
    {
      echo "
    <img onclick=\"submitToSelf('export')\" class=\"hcmsButton hcmsButtonSizeSquare\" name=\"media_export\" src=\"".getthemelocation($hcms_themeinvertcolors)."img/button_export_page.png\" alt=\"".getescapedtext ($hcms_lang['export-list-comma-delimited'][$lang])."\" title=\"".getescapedtext ($hcms_lang['export-list-comma-delimited'][$lang])."\" />";
    }
    else
    {
      echo "
    <img src=\"".getthemelocation($hcms_themeinvertcolors)."img/button_export_page.png\" class=\"hcmsButtonOff hcmsButtonSizeSquare\" />";
    }
    ?>    
  </div>
  <?php } ?>
  
  <div class="hcmsToolbarBlock">
    <?php
    // reload button
    if ($from_page == "") $refresh = "location='explorer_objectlist.php?site=".url_encode($site)."&cat=".url_encode($cat)."&location=".url_encode($location_esc)."'";
    else $refresh = "location.reload();";
      
    echo "
    <img class=\"hcmsButton hcmsButtonSizeSquare\" onclick=\"if (locklayer == false) parent.frames['mainFrame'].".$refresh.";\" name=\"pic_obj_refresh\" src=\"".getthemelocation($hcms_themeinvertcolors)."img/button_view_refresh.png\" alt=\"".getescapedtext ($hcms_lang['refresh'][$lang])."\" title=\"".getescapedtext ($hcms_lang['refresh'][$lang])."\">";  
    ?>
  </div>
  
  <div class="hcmsToolbarBlock">
    <?php echo showhelpbutton ("usersguide", true, $lang, ""); ?>
  </div>
  
  <div style="float:right; <?php if (!$is_mobile || $is_iphone) echo "margin:0px 8px 0px 0px"; elseif (!$is_iphone) echo "margin:0px -2px 0px 0px;"; ?>">
    <?php
    // object list views
    echo "
    <form name=\"memory\" style=\"display:none;\">
      <input name=\"view\" type=\"hidden\" value=\"".$temp_explorerview."\" />
    </form>
    <div id=\"button_obj_view\" onclick=\"hcms_switchSelector('select_obj_view'); hcms_hideSelector('select_obj_edit'); hcms_hideSelector('select_obj_convert');\" class=\"hcmsButton hcmsButtonSizeWide\">
      <img src=\"".getthemelocation($hcms_themeinvertcolors)."img/button_view_gallery_".$temp_explorerview.".png\" class=\"hcmsButtonSizeSquare\" id=\"pic_obj_view\" name=\"pic_obj_view\" alt=\"".getescapedtext ($hcms_lang['thumbnail-gallery'][$lang])."\" title=\"".getescapedtext ($hcms_lang['thumbnail-gallery'][$lang])."\" /><img src=\"".getthemelocation($hcms_themeinvertcolors)."img/pointer_select.png\" class=\"hcmsButtonSizeNarrow\" alt=\"".getescapedtext ($hcms_lang['thumbnail-gallery'][$lang])."\" title=\"".getescapedtext ($hcms_lang['thumbnail-gallery'][$lang])."\" />

      <div id=\"select_obj_view\" class=\"hcmsSelector\" style=\"position:relative; top:-52px; left:-180px; visibility:hidden; z-index:999; width:180px; max-height:".($is_mobile ? "50" : "72")."px; overflow:auto; overflow-x:hidden; overflow-y:auto; white-space:nowrap;\">
        <div class=\"hcmsSelectorItem\" onclick=\"switchView ('large'); document.getElementById('button_obj_view').click();\"><img src=\"".getthemelocation()."img/button_view_gallery_large.png\" class=\"hcmsIconList\" /> ".getescapedtext ($hcms_lang['large-thumbnails'][$lang])."</div>
        <div class=\"hcmsSelectorItem\" onclick=\"switchView ('medium'); document.getElementById('button_obj_view').click();\"><img src=\"".getthemelocation()."img/button_view_gallery_medium.png\" class=\"hcmsIconList\" /> ".getescapedtext ($hcms_lang['medium-thumbnails'][$lang])."</div>
        <div class=\"hcmsSelectorItem\" onclick=\"switchView ('small'); document.getElementById('button_obj_view').click();\"><img src=\"".getthemelocation()."img/button_view_gallery_small.png\" class=\"hcmsIconList\" /> ".getescapedtext ($hcms_lang['small-thumbnails'][$lang])."</div>
        <div class=\"hcmsSelectorItem\" onclick=\"switchView ('detail'); document.getElementById('button_obj_view').click();\"><img src=\"".getthemelocation()."img/button_view_gallery_detail.png\" class=\"hcmsIconList\" /> ".getescapedtext ($hcms_lang['details'][$lang])."</div>
      </div>
    </div>";
    ?>
    
    <?php
    if (!$is_mobile)
    {
      // sidebar
      echo "
    <img src=\"".getthemelocation($hcms_themeinvertcolors)."img/button_sidebar.png\" class=\"hcmsButton hcmsButtonSizeSquare\" onclick=\"switchSidebar();\" title=\"".getescapedtext ($hcms_lang['preview-window'][$lang])."\" alt=\"".getescapedtext ($hcms_lang['preview-window'][$lang])."\" />";

      // search
      if ($location != "" && !empty ($mgmt_config['db_connect_rdbms']) && $from_page != "recyclebin")
      {
        echo "
    <img class=\"hcmsButton hcmsButtonSizeSquare\" onclick=\"if (locklayer == false) parent.setSearchLocation('".$location_esc."', '".getlocationname ($site, $location, $cat, "path")."');\" name=\"pic_obj_search\" src=\"".getthemelocation($hcms_themeinvertcolors)."img/button_search.png\" alt=\"".getescapedtext ($hcms_lang['search'][$lang])."\" title=\"".getescapedtext ($hcms_lang['search'][$lang])."\" />";
      }
      else
      {
        echo "
    <img src=\"".getthemelocation($hcms_themeinvertcolors)."img/button_search.png\" class=\"hcmsButtonOff hcmsButtonSizeSquare\" />";
      }
    }
    ?>
  </div>

</div>


<!-- filter bar -->
<?php if (!$is_mobile && $from_page == "" && $cat != "page") { ?>
<div id="filterLayer" style="position:fixed; bottom:3px; left:3px; margin:0; padding:0; visibility:<?php if (isset ($objectfilter) && is_array ($objectfilter) && sizeof ($objectfilter) > 0) echo "visible"; else echo "hidden"; ?>">
  <form name="filter_set" action="explorer_objectlist.php" target="mainFrame" method="get">
    <input type="hidden" name="location" value="<?php echo $location_esc; ?>" />
    <input type="hidden" name="virtual" value="<?php echo $virtual; ?>" />
    <img src="<?php echo getthemelocation($hcms_themeinvertcolors); ?>img/button_filter.png" class="hcmsIconList" style="vertical-align:middle;" />
    <input type="hidden" name="filter[dummy]" value="1" />
    <input type="checkbox" id="filter1" onclick="setFilter();" name="filter[comp]" value="1" <?php if (!empty ($objectfilter['comp'])) echo "checked=\"checked\""; ?>/>&nbsp;<label for="filter1" class="hcmsInvertColor"><?php echo getescapedtext ($hcms_lang['component'][$lang]); ?></label>&nbsp;&nbsp;
    <input type="checkbox" id="filter2" onclick="setFilter();" name="filter[image]" value="1" <?php if (!empty ($objectfilter['image'])) echo "checked=\"checked\""; ?>/>&nbsp;<label for="filter2" class="hcmsInvertColor"><?php echo getescapedtext ($hcms_lang['image'][$lang]); ?></label>&nbsp;&nbsp;
    <input type="checkbox" id="filter3" onclick="setFilter();" name="filter[document]" value="1" <?php if (!empty ($objectfilter['document'])) echo "checked=\"checked\""; ?>/>&nbsp;<label for="filter3" class="hcmsInvertColor"><?php echo getescapedtext ($hcms_lang['document'][$lang]); ?></label>&nbsp;&nbsp;
    <input type="checkbox" id="filter4" onclick="setFilter();" name="filter[video]" value="1" <?php if (!empty ($objectfilter['video'])) echo "checked=\"checked\""; ?>/>&nbsp;<label for="filter4" class="hcmsInvertColor"><?php echo getescapedtext ($hcms_lang['video'][$lang]); ?></label>&nbsp;&nbsp;
    <input type="checkbox" id="filter5" onclick="setFilter();" name="filter[audio]" value="1" <?php if (!empty ($objectfilter['audio'])) echo "checked=\"checked\""; ?>/>&nbsp;<label for="filter5" class="hcmsInvertColor"><?php echo getescapedtext ($hcms_lang['audio'][$lang]); ?></label>&nbsp;&nbsp;
  </form>
</div>
<?php } ?>

<!-- create folder -->
<div id="foldercreateLayer" class="hcmsMessage" style="position:absolute; width:<?php if ($is_mobile) echo "90%"; else echo "650px"; ?>; height:70px; z-index:1; left:10px; top:10px; visibility:hidden">
  <form name="folder_create" action="" method="post" onsubmit="return checkForm_folder_create();">
    <input type="hidden" name="location" value="<?php echo $location_esc; ?>" />
    <input type="hidden" name="action" value="folder_create" />
    <input type="hidden" name="token" value="<?php echo $token_new; ?>">
    
    <table class="hcmsTableNarrow" style="width:100%; height:60px;">
      <tr>
        <td>
          <span class="hcmsHeadline"><?php echo getescapedtext ($hcms_lang['create-folder'][$lang]); ?></span><br />
          <span style="white-space:nowrap;">
            <input type="text" name="foldernew" maxlength="<?php if (!empty ($mgmt_config['max_digits_filename']) && intval ($mgmt_config['max_digits_filename']) > 0) echo intval ($mgmt_config['max_digits_filename']); else echo "200"; ?>" style="width:<?php if ($is_mobile) echo "200px"; else echo "80%"; ?>;" placeholder="<?php echo getescapedtext ($hcms_lang['name'][$lang]); ?>" title="<?php echo getescapedtext ($hcms_lang['name'][$lang]); ?>" />
            <img name="Button1" src="<?php echo getthemelocation(); ?>img/button_ok.png" class="hcmsButtonTinyBlank hcmsButtonSizeSquare" onclick="checkForm_folder_create();" onMouseOut="hcms_swapImgRestore()" onMouseOver="hcms_swapImage('Button1','','<?php echo getthemelocation(); ?>img/button_ok_over.png',1)" alt="OK" title="OK" />
          </span>
        </td>
        <td style="width:38px; text-align:right; vertical-align:top;">
          <img name="hcms_mediaClose1" src="<?php echo getthemelocation(); ?>img/button_close.png" class="hcmsButtonTinyBlank hcmsButtonSizeSquare" alt="<?php echo getescapedtext ($hcms_lang['close'][$lang]); ?>" title="<?php echo getescapedtext ($hcms_lang['close'][$lang]); ?>" onMouseOut="hcms_swapImgRestore();" onMouseOver="hcms_swapImage('hcms_mediaClose1','','<?php echo getthemelocation(); ?>img/button_close_over.png',1);" onclick="hcms_showHideLayers('foldercreateLayer','','hide');" />
        </td>      
      </tr>
    </table>
  </form>
</div>

<!-- rename folder -->
<div id="folderrenameLayer" class="hcmsMessage" style="position:absolute; width:<?php if ($is_mobile) echo "90%"; else echo "650px"; ?>; height:70px; z-index:2; left:10px; top:10px; visibility:hidden;">
  <form name="folder_rename" action="" method="post" onsubmit="return checkForm_folder_rename();">
    <input type="hidden" name="location" value="<?php echo $location_esc; ?>" />
    <input type="hidden" name="folder" value="<?php echo $folder; ?>" />
    <input type="hidden" name="action" value="folder_rename" />
    <input type="hidden" name="token" value="<?php echo $token_new; ?>">
    
    <table class="hcmsTableNarrow" style="width:100%; height:60px;">
      <tr>
        <td>
          <span class="hcmsHeadline"><?php echo getescapedtext ($hcms_lang['rename-folder'][$lang]); ?></span><br />
          <span style="white-space:nowrap;">
            <input type="text" name="foldernew" maxlength="<?php if (!empty ($mgmt_config['max_digits_filename']) && intval ($mgmt_config['max_digits_filename']) > 0) echo intval ($mgmt_config['max_digits_filename']); else echo "200"; ?>" style="width:<?php if ($is_mobile) echo "200px"; else echo "80%"; ?>;" value="<?php echo $pagename; ?>" />
            <img name="Button2" src="<?php echo getthemelocation(); ?>img/button_ok.png" class="hcmsButtonTinyBlank hcmsButtonSizeSquare" onclick="checkForm_folder_rename();" onMouseOut="hcms_swapImgRestore()" onMouseOver="hcms_swapImage('Button2','','<?php echo getthemelocation(); ?>img/button_ok_over.png',1)" alt="OK" title="OK" />
          </span>
        </td>
        <td style="width:38px; text-align:right; vertical-align:top;">
          <img name="hcms_mediaClose2" src="<?php echo getthemelocation(); ?>img/button_close.png" class="hcmsButtonTinyBlank hcmsButtonSizeSquare" alt="<?php echo getescapedtext ($hcms_lang['close'][$lang]); ?>" title="<?php echo getescapedtext ($hcms_lang['close'][$lang]); ?>" onMouseOut="hcms_swapImgRestore();" onMouseOver="hcms_swapImage('hcms_mediaClose2','','<?php echo getthemelocation(); ?>img/button_close_over.png',1);" onclick="hcms_showHideLayers('folderrenameLayer','','hide');" />
        </td>        
      </tr>
    </table>
  </form>
</div>

<!-- rename object -->
<div id="objrenameLayer" class="hcmsMessage" style="position:absolute; width:<?php if ($is_mobile) echo "90%"; else echo "650px"; ?>; height:70px; z-index:3; left:10px; top:10px; visibility:hidden">
  <form name="page_rename" action="" onsubmit="return checkForm_page_rename();">
    <input type="hidden" name="location" value="<?php echo $location_esc; ?>" />
    <input type="hidden" name="page" value="<?php echo $page; ?>" />
    <input type="hidden" name="action" value="page_rename" />
    <input type="hidden" name="token" value="<?php echo $token_new; ?>">
    
    <table class="hcmsTableNarrow" style="width:100%; height:60px;">
      <tr>
        <td>
          <span class="hcmsHeadline"><?php echo getescapedtext ($hcms_lang['rename'][$lang]);  if ($filetype == "Page" || $filetype == "Component") echo " (".getescapedtext ($hcms_lang['name-without-ext'][$lang]).")"; ?></span><br />
          <span style="white-space:nowrap;">
            <input type="text" name="pagenew" maxlength="<?php if (!empty ($mgmt_config['max_digits_filename']) && intval ($mgmt_config['max_digits_filename']) > 0) echo intval ($mgmt_config['max_digits_filename']); else echo "200"; ?>" style="width:<?php if ($is_mobile) echo "200px"; else echo "80%"; ?>;" value="<?php echo substr ($pagename, 0, strrpos ($pagename, ".")); ?>" />
            <img name="Button5" src="<?php echo getthemelocation(); ?>img/button_ok.png" class="hcmsButtonTinyBlank hcmsButtonSizeSquare" onclick="checkForm_page_rename();" onMouseOut="hcms_swapImgRestore()" onMouseOver="hcms_swapImage('Button5','','<?php echo getthemelocation(); ?>img/button_ok_over.png',1)" alt="OK" title="OK" />
          </span>
        </td>
        <td style="width:38px; text-align:right; vertical-align:top;">
          <img name="hcms_mediaClose3" src="<?php echo getthemelocation(); ?>img/button_close.png" class="hcmsButtonTinyBlank hcmsButtonSizeSquare" alt="<?php echo getescapedtext ($hcms_lang['close'][$lang]); ?>" title="<?php echo getescapedtext ($hcms_lang['close'][$lang]); ?>" onMouseOut="hcms_swapImgRestore();" onMouseOver="hcms_swapImage('hcms_mediaClose3','','<?php echo getthemelocation(); ?>img/button_close_over.png',1);" onclick="hcms_showHideLayers('objrenameLayer','','hide');" />
        </td>      
      </tr>
    </table>
  </form>
</div>

<!-- create ZIP file -->
<div id="zipLayer" class="hcmsMessage" style="position:absolute; width:<?php if ($is_mobile) echo "90%"; else echo "650px"; ?>; height:70px; left:10px; top:10px; visibility:hidden">
  <form name="page_zip" action="" onsubmit="return checkForm_zip();">
    <input type="hidden" name="location" value="<?php echo $location_esc; ?>" />
    <input type="hidden" name="page" value="<?php echo $page; ?>" />
    <input type="hidden" name="folder" value="<?php echo $folder; ?>" />
    <input type="hidden" name="action" value="zip" />
    <input type="hidden" name="multiobject" value="<?php echo $multiobject ?>" />
    <input type="hidden" name="token" value="<?php echo $token_new; ?>" />
    
    <table class="hcmsTableNarrow" style="width:100%; height:60px;">
      <tr>
        <td>
          <span class="hcmsHeadline"><?php echo getescapedtext ($hcms_lang['create-zip-file-without-ext'][$lang]); ?></span><br />
          <span style="white-space:nowrap;">
            <input type="text" name="pagenew" maxlength="100" style="width:<?php if ($is_mobile) echo "200px"; else echo "80%"; ?>;" value="<?php echo substr ($pagename, 0, strrpos ($pagename, ".")); ?>" />
            <img name="Button6" src="<?php echo getthemelocation(); ?>img/button_ok.png" onclick="checkForm_zip();" class="hcmsButtonTinyBlank hcmsButtonSizeSquare" onMouseOut="hcms_swapImgRestore()" onMouseOver="hcms_swapImage('Button6','','<?php echo getthemelocation(); ?>img/button_ok_over.png',1)" alt="OK" title="OK" />
          </span>
        </td>
        <td style="width:38px; text-align:right; vertical-align:top;">
          <img name="hcms_mediaClose4" src="<?php echo getthemelocation(); ?>img/button_close.png" class="hcmsButtonTinyBlank hcmsButtonSizeSquare" alt="<?php echo getescapedtext ($hcms_lang['close'][$lang]); ?>" title="<?php echo getescapedtext ($hcms_lang['close'][$lang]); ?>" onMouseOut="hcms_swapImgRestore();" onMouseOver="hcms_swapImage('hcms_mediaClose4','','<?php echo getthemelocation(); ?>img/button_close_over.png',1);" onclick="hcms_showHideLayers('zipLayer','','hide');" />
        </td>       
      </tr>
    </table>
  </form>
</div>

<!-- import CSV data -->
<div id="importLayer" class="hcmsMessage" style="position:absolute; width:<?php if ($is_mobile) echo "90%"; else echo "850px"; ?>; height:85px; z-index:1; left:10px; top:10px; visibility:hidden">
  <form name="import" action="" method="post" enctype="multipart/form-data" onsubmit="return checkForm_import();">
    <input type="hidden" name="location" value="<?php echo $location_esc; ?>" />
    <input type="hidden" name="action" value="import" />
    <input type="hidden" name="token" value="<?php echo $token_new; ?>">
    
    <table class="hcmsTableNarrow" style="width:100%; height:75px;">
      <tr>
        <td>
          <span class="hcmsHeadline"><?php echo str_replace ("(", "<br/>(", getescapedtext ($hcms_lang['upload-csv-file'][$lang])); ?></span><br />
          <span style="white-space:nowrap;">
            <input name="importfile" type="file" style="width:<?php if ($is_mobile) echo "200px"; else echo "80%"; ?>;" accept="text/*" />
            <img name="Button7" src="<?php echo getthemelocation(); ?>img/button_ok.png" class="hcmsButtonTinyBlank hcmsButtonSizeSquare" onclick="checkForm_import();" onMouseOut="hcms_swapImgRestore()" onMouseOver="hcms_swapImage('Button7','','<?php echo getthemelocation(); ?>img/button_ok_over.png',1)" alt="OK" title="OK" />
          </span>
        </td>
        <td style="width:38px; text-align:right; vertical-align:top;">
          <img name="hcms_mediaClose7" src="<?php echo getthemelocation(); ?>img/button_close.png" class="hcmsButtonTinyBlank hcmsButtonSizeSquare" alt="<?php echo getescapedtext ($hcms_lang['close'][$lang]); ?>" title="<?php echo getescapedtext ($hcms_lang['close'][$lang]); ?>" onMouseOut="hcms_swapImgRestore();" onMouseOver="hcms_swapImage('hcms_mediaClose7','','<?php echo getthemelocation(); ?>img/button_close_over.png',1);" onclick="hcms_showHideLayers('importLayer','','hide');" />
        </td>      
      </tr>
    </table>
  </form>
</div>

<!-- download -->
<div id="downloadLayer" class="hcmsMessage" style="position:absolute; width:<?php if ($is_mobile) echo "80%"; else echo "650px"; ?>; height:70px; z-index:15; left:10px; top:10px; visibility:<?php echo ($action == 'download' ? 'visible' : 'hidden'); ?>;" >
  <table class="hcmsTableNarrow" style="width:100%; height:60px;">
    <tr>
      <td>
        <div style="width:100%; height:100%; overflow:auto;">
          <?php
          // iOS (iPhone, iPad) download
          if ($action == "download" && $is_iphone)
          { 
            $downloadlink = createmultidownloadlink ($site, $multiobject, $pagename, $user, $convert_type, $convert_cfg, "wrapper");
            
            echo "<a href=\"".$downloadlink."\" class=\"button hcmsButtonGreen\" target=\"_blank\">".getescapedtext ($hcms_lang['downloadview-file'][$lang])."</a>";
          }
          else
          {
            echo getescapedtext ($hcms_lang['please-wait-while-your-download-is-being-processed'][$lang]);
          }
          ?>
        </div>
      </td>
      <td style="width:38px; text-align:right; vertical-align:top;">
        <img name="hcms_mediaClose6" src="<?php echo getthemelocation(); ?>img/button_close.png" class="hcmsButtonTinyBlank hcmsButtonSizeSquare" alt="<?php echo getescapedtext ($hcms_lang['close'][$lang]); ?>" title="<?php echo getescapedtext ($hcms_lang['close'][$lang]); ?>" onMouseOut="hcms_swapImgRestore();" onMouseOver="hcms_swapImage('hcms_mediaClose6','','<?php echo getthemelocation(); ?>img/button_close_over.png',1);" onclick="hcms_showHideLayers('downloadLayer','','hide');" />
      </td>        
    </tr>
  </table>
</div>
<?php
// download for non iOS devices
if ($action == "download" && !$is_iphone)
{
  // for search (incl. favorites, checkedout items)
  if ($from_page == "search")
  {
    $pagename = "Download";
    $flatzip = true;
  }
  else $flatzip = false;

  $downloadlink = createmultidownloadlink ($site, $multiobject, $pagename, $user, $convert_type, $convert_cfg, "download", $flatzip);

  if ($downloadlink != "")
  {
?>
<script type="text/javascript">
function downloadFile()
{
  hcms_showHideLayers('downloadLayer','','hide');
  location.replace('<?php echo $downloadlink; ?>');
}

setTimeout('downloadFile()', 1000);
</script>
<?php
  }
  // download failed (zip file could not be created)
  else
  {
    echo showmessage (str_replace ("%filesize%", $mgmt_config['maxzipsize'], $hcms_lang['download-failed-max'][$lang]), 660, 70, $lang, "position:fixed; left:10px; top:10px;");
  }
}
?>

</body>
</html>
