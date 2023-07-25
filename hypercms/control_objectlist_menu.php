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

// enable or disable button labels
$mgmt_config['showbuttonlabel'] = false;

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
      
      if (is_file ($file_temp)) deletefile (getlocation ($file_temp), getobject ($file_temp), 0);
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
}

// define message if object is checked out by another user
if (!empty ($contentfile))
{
  $usedby_array = getcontainername ($contentfile);

  if (is_array ($usedby_array) && !empty ($usedby_array['user'])) $usedby = $usedby_array['user'];

  if ($usedby != "" && $usedby != $user) $show = getescapedtext ($hcms_lang['object-is-checked-out-by-user'][$lang])." '".$usedby."'";
}

// get file info
if (empty ($folder) && !empty ($page)) 
{
  // correct object file name
  $page = correctfile ($location_ACCESS, $page, $user);

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
<meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0"></meta>
<link rel="stylesheet" href="<?php echo getthemelocation(); ?>css/main.css?v=<?php echo getbuildnumber(); ?>" />
<link rel="stylesheet" href="<?php echo getthemelocation()."css/".($is_mobile ? "mobile.css" : "desktop.css"); ?>?v=<?php echo getbuildnumber(); ?>" />
<script type="text/javascript" src="javascript/click.min.js"></script>
<script type="text/javascript" src="javascript/main.min.js?v=<?php echo getbuildnumber(); ?>"></script>
<style type="text/css">
.hcmsButtonLabel
{
  <?php
  if (empty ($mgmt_config['showbuttonlabel'])) echo "display: none !important;";
  else echo "display: block !important;";
  ?>
}

.hcmsMenuItem
{
  padding: 2px 2px 1px 2px;
  min-width: 190px;
  width: 10%;
  float: left;
}

.hcmsMenu
{
  width: 100%;
}

@media screen and (min-width: 1201px)
{
  .hcmsMenu
  {
    width: 1200px;
  }
}

<?php
// inverted main colors
if (!empty ($hcms_themeinvertcolors))
{
  if (!empty ($hcms_hoverinvertcolors)) $invertonhover = false;
  else $invertonhover = true;

  echo invertcolorCSS ($hcms_themeinvertcolors, ".hcmsInvertColor", true, $invertonhover);
  echo invertcolorCSS ($hcms_themeinvertcolors, ".hcmsInvertPrimaryColor", true, false);
}
// inverted hover colors
elseif (!empty ($hcms_hoverinvertcolors))
{
  echo invertcolorCSS ($hcms_hoverinvertcolors, ".hcmsInvertColor", false, true);
  echo invertcolorCSS ($hcms_hoverinvertcolors, ".hcmsInvertHoverColor", true, false);
}
?>
</style>
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

function openInMainFrame (url, action)
{
  if (typeof parent.openMainView == 'function')
  {
    parent.openMainView(url);
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

function emptyRecycleBin (token)
{
  if (typeof parent.frames['mainFrame'].hcms_emptyRecycleBin === 'function')
  {
    parent.frames['mainFrame'].hcms_emptyRecycleBin(token);
  }
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
    hcms_showHideLayers('downloadLayer');
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
    // deprecated: $.post("<?php echo $mgmt_config['url_path_cms']; ?>service/toggleview.php", {'objectlistview': view});
    hcms_ajaxService('<?php echo $mgmt_config['url_path_cms']; ?>service/toggleview.php?explorerview=' + encodeURIComponent(view));

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
    // deprecated: $.post("<?php echo $mgmt_config['url_path_cms']; ?>service/togglesidebar.php", {'view': view});
    hcms_ajaxService('<?php echo $mgmt_config['url_path_cms']; ?>service/togglesidebar.php?view=' + encodeURIComponent(view));

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
    var filteractive = false;

    for (var i=0; i<elem.length; i++)
    {
      if (elem[i].checked == true) filteractive = true;
    }

    if (filterset.style.visibility == 'hidden' || filteractive == true)
    {
      openSubMenu('filterLayer');
    }
    else if (filteractive == false)
    {
      closeSubMenu('filterLayer');
    }
     
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
  hcms_hideSelector('select_obj_convert');
}

// send to chat
function sendtochat (text)
{
  top.sendtochat (text);
}

function openSubMenu (id)
{
  // hide boxes
  hcms_showHideLayers('select_obj_view','','hide', 
                      'select_obj_convert','','hide',
                      'foldercreateLayer','','hide',
                      'folderrenameLayer','','hide',
                      'fileuploadLayer','','hide',
                      'objrenameLayer','','hide',
                      'importLayer','','hide',
                      'hcms_messageLayer','','hide',
                      'zipLayer','','hide');

  // open box or menu
  if (id != "")
  {
    hcms_showFormLayer(id, 0);
    hcms_showHideLayers(id, '', 'show');
  }

  if (typeof parent.hcms_openSubMenu == "function") parent.hcms_openSubMenu();
}

function closeSubMenu (id)
{
  // close menu
  if (id != "")
  {
    hcms_hideFormLayer(id);
  }

  // hide boxes
  hcms_showHideLayers('select_obj_view','','hide', 
                      'select_obj_convert','','hide',
                      'foldercreateLayer','','hide',
                      'folderrenameLayer','','hide',
                      'fileuploadLayer','','hide',
                      'objrenameLayer','','hide',
                      'importLayer','','hide',
                      'hcms_messageLayer','','hide',
                      'zipLayer','','hide');

  if (typeof parent.hcms_closeSubMenu == "function") parent.hcms_closeSubMenu();
}

// init
parent.hcms_closeSubMenu();
</script>
</head>

<body class="hcmsWorkplaceControl" onload="<?php echo $add_onload; ?> <?php if (isset ($objectfilter) && is_array ($objectfilter) && sizeof ($objectfilter) > 0) echo "openSubMenu('filterLayer');"; ?>">

<!-- load screen --> 
<div id="hcmsLoadScreen" class="hcmsLoadScreen" style="display:none;"></div>

<!-- new buttons infobox since version 10.2.0 -->
<?php if (!$is_mobile && !empty ($hcms_lang['info-plus-icon'][$lang]) && !empty ($hcms_lang['info-action-icon'][$lang]))
{
  echo showinfobox ("<img src='".getthemelocation($hcms_themeinvertcolors)."img/button_new.png' class='hcmsIconList'> ".$hcms_lang['info-plus-icon'][$lang]."<br/><img src='".getthemelocation($hcms_themeinvertcolors)."img/button_menu.png' class='hcmsIconList'> ".$hcms_lang['info-action-icon'][$lang], $lang, "position:fixed; top:10px; left:260px;", "hcms_infobox_plus_3dots");
}
?>

<!-- help infobox -->
<?php if (!$is_mobile) echo showinfobox ($hcms_lang['move-the-mouse-over-the-icons-to-get-more-information'][$lang], $lang, "position:fixed; top:10px; right:10px;", "hcms_infobox_mouseover"); ?>

<!-- message -->
<?php echo showmessage ($show, 660, 65, $lang, "position:fixed; left:5px; top:5px;"); ?>

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
<div class="hcmsLocationBar" style="width:100%;">
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
<?php } else { 
  // use location name if no object has been selected
  if ($object_name == "&nbsp;") $object_name = str_replace ("/", " &gt; ", trim ($location_name, "/"));

  // support for older iPhones 7 and less (shorten text due to display issues)
  $browser_info = getbrowserinfo();
  if (!empty ($browser_info['safari']) && $browser_info['safari'] < 13) $object_name = showshorttext ($object_name, 44, false);
?>
  <div style="max-width:95%; display:block; white-space:nowrap; overflow-x:auto; -webkit-overflow-scrolling:touch;"><?php echo $object_name; ?></div>
<?php } ?>
</div>

<!-- toolbar -->
<div class="hcmsToolbar" style="<?php if (!$is_mobile) echo "white-space:nowrap; min-width:580px;"; else echo "max-height:100px;"; ?>">
  <div class="hcmsToolbarBlock">
    <div class="hcmsButtonFrame">
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
       <div class=\"hcmsButton hcmsHoverColor hcmsInvertColor hcmsButtonSizeSquare\">
        <img ".
        "onclick=\"if (locklayer == false) parent.location='frameset_objectlist.php?site=".url_encode($site)."&cat=".url_encode($cat)."&location=".url_encode($location_down_esc)."';\" ".
        "class=\"hcmsButtonSizeSquare\" ".
        "id=\"pic_folder_back\" ".
        "src=\"".getthemelocation($hcms_themeinvertcolors)."img/button_back.png\" ".
        "alt=\"".getescapedtext($hcms_lang['go-to-parent-folder'][$lang])."\" title=\"".getescapedtext($hcms_lang['go-to-parent-folder'][$lang])."\" />
       </div>";
    }
    // parent folder of current object (used for search results)
    elseif (
             $from_page != "" && $multiobject_count <= 1 && ($page != "" || $folder != "") && valid_locationname ($abs_path_root) && valid_locationname ($location_down) && @substr_count ($abs_path_root, $location) < 1 && $setlocalpermission_down['root'] == 1
           )
    {
      echo "
      <div class=\"hcmsButton hcmsHoverColor hcmsInvertColor hcmsButtonSizeSquare\">
        <img ".
        "onclick=\"if (locklayer == false) parent.location='frameset_objectlist.php?site=".url_encode($site)."&cat=".url_encode($cat)."&location=".url_encode($location_esc)."';\" ".
        "class=\"hcmsButtonSizeSquare\" ".
        "id=\"pic_folder_back\" ".
        "src=\"".getthemelocation($hcms_themeinvertcolors)."img/button_back.png\" ".
        "alt=\"".getescapedtext($hcms_lang['go-to-parent-folder'][$lang])."\" title=\"".getescapedtext($hcms_lang['go-to-parent-folder'][$lang])."\" />
      </div>";
    }
    else
    {
      echo "
      <img src=\"".getthemelocation($hcms_themeinvertcolors)."img/button_back.png\" class=\"hcmsButtonOff hcmsButtonSizeSquare\" />";
    }
    ?>
      <br/><div class="hcmsButtonLabel"><?php if (!empty ($mgmt_config['showbuttonlabel'])) echo getescapedtext(showshorttext($hcms_lang['back'][$lang], 11, 2)); ?></div>
    </div>
  </div>
  
  <?php if (!$is_mobile) { ?>
  <div class="hcmsToolbarBlock">
    <div class="hcmsButtonFrame">
    <?php
    // Filter
    if ($from_page == "" && $cat != "page")
    {
      echo "
      <div class=\"hcmsButton hcmsHoverColor hcmsInvertColor hcmsButtonSizeSquare\">
        <img src=\"".getthemelocation($hcms_themeinvertcolors)."img/button_filter.png\" class=\"hcmsButtonSizeSquare\" onclick=\"switchFilter();\" title=\"".getescapedtext($hcms_lang['filter-by-file-type'][$lang])."\" alt=\"".getescapedtext($hcms_lang['filter-by-file-type'][$lang])."\" />
      </div>";
    }
    else
    {
      echo "
        <img src=\"".getthemelocation($hcms_themeinvertcolors)."img/button_filter.png\" class=\"hcmsButtonOff hcmsButtonSizeSquare\" />";
    }
    ?>
      <br/><div class="hcmsButtonLabel"><?php if (!empty ($mgmt_config['showbuttonlabel'])) echo getescapedtext(showshorttext($hcms_lang['filter-by-file-type'][$lang], 11, 2)); ?></div>
    </div>
  </div>
  <?php } ?>
  
  <div class="hcmsToolbarBlock">
    <div class="hcmsButtonFrame">
    <?php
    // Upload Button (HTML5 file upload)
    if ($from_page == "" && ($cat != "page" || !empty ($mgmt_config[$site]['upload_pages'])) && $setlocalpermission['root'] == 1 && $setlocalpermission['upload'] == 1)
    {
      echo "
      <div class=\"hcmsButton hcmsHoverColor hcmsInvertColor hcmsButtonSizeSquare\">
        <img class=\"hcmsButtonSizeSquare\" onclick=\"window.top.openPopup('popup_upload_html.php?uploadmode=multi&site=".url_encode($site)."&cat=".url_encode($cat)."&location=".url_encode($location_esc)."', 'upload".md5($location_esc)."');\" ".
        "src=\"".getthemelocation($hcms_themeinvertcolors)."img/button_file_upload.png\" alt=\"".getescapedtext ($hcms_lang['upload-file'][$lang])."\" title=\"".getescapedtext ($hcms_lang['upload-file'][$lang])."\" />
      </div>";
    }
    else
    {
      echo "
      <img src=\"".getthemelocation($hcms_themeinvertcolors)."img/button_file_upload.png\" class=\"hcmsButtonOff hcmsButtonSizeSquare\" />";
    }
    ?>
      <br/><div class="hcmsButtonLabel"><?php if (!empty ($mgmt_config['showbuttonlabel'])) echo getescapedtext(showshorttext($hcms_lang['upload'][$lang], 11, 2)); ?></div>
    </div>
    <div class="hcmsButtonFrame">
    <?php
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
    
    // download options
    if ($from_page != "recyclebin" && ($multiobject_count <= 1 && !empty ($page) && !empty ($media)) && $perm_rendering && $lock_rendering && ($doc_rendering || $img_rendering || $dropbox_rendering))
    {
      echo "
      <div class=\"hcmsButton hcmsHoverColor hcmsInvertColor hcmsButtonSizeSquare\">
        <img src=\"".getthemelocation($hcms_themeinvertcolors)."img/button_file_download.png\" class=\"hcmsButtonSizeSquare\" onclick=\"openSubMenu('downloadselectLayer');\" alt=\"".getescapedtext ($hcms_lang['download-file'][$lang])."\" title=\"".getescapedtext ($hcms_lang['download-file'][$lang])."\" />
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
      <div class=\"hcmsButton hcmsHoverColor hcmsInvertColor hcmsButtonSizeSquare\">
        <img src=\"".getthemelocation($hcms_themeinvertcolors)."img/button_file_download.png\" class=\"hcmsButtonSizeSquare\" onclick=\"submitToSelf('download'); hcms_showHideLayers('downloadLayer','','show');\" alt=\"".getescapedtext ($hcms_lang['download-file'][$lang])."\" title=\"".getescapedtext ($hcms_lang['download-file'][$lang])."\" />
      </div>";
    }
    else
    {
      echo "
        <img class=\"hcmsButtonOff hcmsButtonSizeSquare\" src=\"".getthemelocation($hcms_themeinvertcolors)."img/button_file_download.png\" />";
    }
    ?>
      <br/><div class="hcmsButtonLabel"><?php if (!empty ($mgmt_config['showbuttonlabel'])) echo getescapedtext(showshorttext($hcms_lang['download'][$lang], 13, 2)); ?></div>
    </div> 
  </div>

  <div class="hcmsToolbarBlock">
    <div class="hcmsButtonFrame">  
    <?php
    // Create new objects
    if ($from_page == "" && $setlocalpermission['root'] == 1 && ($setlocalpermission['create'] == 1 || $setlocalpermission['foldercreate'] == 1))
    {
      echo "
      <div class=\"hcmsButton hcmsHoverColor hcmsInvertColor hcmsButtonSizeSquare\">
        <img src=\"".getthemelocation($hcms_themeinvertcolors)."img/button_new.png\" class=\"hcmsButtonSizeSquare\" onclick=\"openSubMenu('createLayer');\" title=\"".getescapedtext($hcms_lang['create'][$lang])."\" alt=\"".getescapedtext($hcms_lang['create'][$lang])."\" />
      </div>";
    }
    else
    {
      echo "
      <img src=\"".getthemelocation($hcms_themeinvertcolors)."img/button_new.png\" class=\"hcmsButtonOff hcmsButtonSizeSquare\" />";
    }
    ?>
      <br/><div class="hcmsButtonLabel"><?php if (!empty ($mgmt_config['showbuttonlabel'])) echo getescapedtext(showshorttext($hcms_lang['create'][$lang], 11, 2)); ?></div>
    </div>
    <div class="hcmsButtonFrame">
    <?php
    // Other actions
    if ($multiobject_count > 0 || !empty ($page))
    {
      echo "
      <div class=\"hcmsButton hcmsHoverColor hcmsInvertColor hcmsButtonSizeSquare\">
        <img src=\"".getthemelocation($hcms_themeinvertcolors)."img/button_menu.png\" class=\"hcmsButtonSizeSquare\" onclick=\"openSubMenu('actionsLayer');\" title=\"".getescapedtext($hcms_lang['action'][$lang])."\" alt=\"".getescapedtext($hcms_lang['action'][$lang])."\" />
      </div>";
    }
    else
    {
      echo "
      <img src=\"".getthemelocation($hcms_themeinvertcolors)."img/button_menu.png\" class=\"hcmsButtonOff hcmsButtonSizeSquare\" />";
    }
    ?>
      <br/><div class="hcmsButtonLabel"><?php if (!empty ($mgmt_config['showbuttonlabel'])) echo getescapedtext(showshorttext($hcms_lang['action'][$lang], 11, 2)); ?></div>
    </div>
    <div class="hcmsButtonFrame">
    <?php
    // Paste Button
    if ($from_page == "" && ($setlocalpermission['root'] == 1 && ($setlocalpermission['rename'] == 1 || $setlocalpermission['folderrename'] == 1)) && !empty (getsession ('hcms_temp_clipboard')))
    {
      echo "
      <div class=\"hcmsButton hcmsHoverColor hcmsInvertColor hcmsButtonSizeSquare\">
        <img onclick=\"if (locklayer == false) submitToPopup('popup_status.php', 'paste', 'paste".uniqid()."');\" ".
        "class=\"hcmsButtonSizeSquare\" id=\"pic_obj_paste\" src=\"".getthemelocation($hcms_themeinvertcolors)."img/button_file_paste.png\" alt=\"".getescapedtext ($hcms_lang['paste'][$lang])."\" title=\"".getescapedtext ($hcms_lang['paste'][$lang])."\" />
      </div>";
    }
    else
    {
      echo "
        <img src=\"".getthemelocation($hcms_themeinvertcolors)."img/button_file_paste.png\" class=\"hcmsButtonOff hcmsButtonSizeSquare\" />";
    }
    ?>
      <br/><div class="hcmsButtonLabel"><?php if (!empty ($mgmt_config['showbuttonlabel'])) echo getescapedtext(showshorttext($hcms_lang['paste'][$lang], 11, 2)); ?></div>
    </div>
  </div>

  <div class="hcmsToolbarBlock">
    <div class="hcmsButtonFrame"> 
    <?php
    // reload button
    if ($from_page == "") $refresh = "location='explorer_objectlist.php?site=".url_encode($site)."&cat=".url_encode($cat)."&location=".url_encode($location_esc)."&ts=".time()."'";
    else $refresh = "location.reload();";
  
    echo "
    <div class=\"hcmsButton hcmsHoverColor hcmsInvertColor hcmsButtonSizeSquare\">
      <img class=\"hcmsButtonSizeSquare\" onclick=\"if (locklayer == false) parent.frames['mainFrame'].".$refresh.";\" src=\"".getthemelocation($hcms_themeinvertcolors)."img/button_view_refresh.png\" alt=\"".getescapedtext ($hcms_lang['refresh'][$lang])."\" title=\"".getescapedtext ($hcms_lang['refresh'][$lang])."\">
    </div>";  
    ?>
      <br/><div class="hcmsButtonLabel"><?php if (!empty ($mgmt_config['showbuttonlabel'])) echo getescapedtext(showshorttext($hcms_lang['refresh'][$lang], 11, 2)); ?></div>
    </div>
  </div>
  
  <div class="hcmsToolbarBlock">
    <div class="hcmsButtonFrame"> 
      <?php echo showhelpbutton ("usersguide", true, $lang, "", "hcmsHoverColor hcmsInvertColor"); ?>
      <br/><div class="hcmsButtonLabel"><?php if (!empty ($mgmt_config['showbuttonlabel'])) echo getescapedtext(showshorttext($hcms_lang['help'][$lang], 11, 2)); ?></div>
    </div>
  </div>

  <div style="float:right; <?php if (!$is_mobile || $is_iphone) echo "margin:0px 8px 0px 0px"; elseif (!$is_iphone) echo "margin:0px -2px 0px 0px;"; ?>">
    <?php
    // object list views
    echo "
    <form name=\"memory\" style=\"display:none;\">
      <input name=\"view\" type=\"hidden\" value=\"".$temp_explorerview."\" />
    </form>
    <div id=\"button_obj_view\" onclick=\"hcms_switchSelector('select_obj_view'); hcms_hideSelector('select_obj_convert');\" class=\"hcmsButton hcmsHoverColor hcmsInvertColor hcmsButtonSizeWide\">
      <img src=\"".getthemelocation($hcms_themeinvertcolors)."img/button_view_gallery_".$temp_explorerview.".png\" class=\"hcmsButtonSizeSquare\" id=\"pic_obj_view\" alt=\"".getescapedtext ($hcms_lang['thumbnail-gallery'][$lang])."\" title=\"".getescapedtext ($hcms_lang['thumbnail-gallery'][$lang])."\" /><img src=\"".getthemelocation($hcms_themeinvertcolors)."img/pointer_select.png\" class=\"hcmsButtonSizeNarrow\" alt=\"".getescapedtext ($hcms_lang['thumbnail-gallery'][$lang])."\" title=\"".getescapedtext ($hcms_lang['thumbnail-gallery'][$lang])."\" />

      <div id=\"select_obj_view\" class=\"hcmsSelector\" style=\"position:relative; top:-52px; left:-180px; visibility:hidden; z-index:999; width:180px; max-height:".($is_mobile ? "50" : "72")."px; overflow:auto; overflow-x:hidden; overflow-y:auto; white-space:nowrap;\">";
      if (!$is_mobile) echo "
        <div class=\"hcmsSelectorItem hcmsInvertHoverColor\" onclick=\"switchView ('large'); document.getElementById('button_obj_view').click();\">
          <img src=\"".getthemelocation($hcms_hoverinvertcolors)."img/button_view_gallery_large.png\" class=\"hcmsIconList\" /> 
          <span class=\"\">".getescapedtext ($hcms_lang['large-thumbnails'][$lang])."</span>
        </div>";
      echo "
        <div class=\"hcmsSelectorItem hcmsInvertHoverColor\" onclick=\"switchView ('medium'); document.getElementById('button_obj_view').click();\">
          <img src=\"".getthemelocation($hcms_hoverinvertcolors)."img/button_view_gallery_medium.png\" class=\"hcmsIconList\" /> 
          <span class=\"\">".getescapedtext ($hcms_lang['medium-thumbnails'][$lang])."</span>
        </div>
        <div class=\"hcmsSelectorItem hcmsInvertHoverColor\" onclick=\"switchView ('small'); document.getElementById('button_obj_view').click();\">
          <img src=\"".getthemelocation($hcms_hoverinvertcolors)."img/button_view_gallery_small.png\" class=\"hcmsIconList\" /> 
          <span class=\"\">".getescapedtext ($hcms_lang['small-thumbnails'][$lang])."</span>
        </div>
        <div class=\"hcmsSelectorItem hcmsInvertHoverColor\" onclick=\"switchView ('detail'); document.getElementById('button_obj_view').click();\">
          <img src=\"".getthemelocation($hcms_hoverinvertcolors)."img/button_view_gallery_detail.png\" class=\"hcmsIconList\" /> 
          <span class=\"\">".getescapedtext ($hcms_lang['details'][$lang])."</span>
        </div>
      </div>
    </div>";
    ?>

    <?php
    if (!$is_mobile)
    {
      // sidebar
      echo "
      <div class=\"hcmsButton hcmsHoverColor hcmsInvertColor hcmsButtonSizeSquare\">
        <img src=\"".getthemelocation($hcms_themeinvertcolors)."img/button_sidebar.png\" class=\"hcmsButtonSizeSquare\" onclick=\"switchSidebar();\" title=\"".getescapedtext ($hcms_lang['preview-window'][$lang])."\" alt=\"".getescapedtext ($hcms_lang['preview-window'][$lang])."\" />
      </div>";

      // search
      if (linking_valid() == false)
      {
        if ($location != "" && !empty ($mgmt_config['db_connect_rdbms']) && $from_page != "recyclebin")
        {
          echo "
      <div class=\"hcmsButton hcmsHoverColor hcmsInvertColor hcmsButtonSizeSquare\">
        <img class=\"hcmsButtonSizeSquare\" onclick=\"if (locklayer == false) parent.setSearchLocation('".$location_esc."', '".getlocationname ($site, $location, $cat, "path")."');\" id=\"pic_obj_search\" src=\"".getthemelocation($hcms_themeinvertcolors)."img/button_search.png\" alt=\"".getescapedtext ($hcms_lang['search'][$lang])."\" title=\"".getescapedtext ($hcms_lang['search'][$lang])."\" />
      </div>";
        }
        else
        {
          echo "
      <img src=\"".getthemelocation($hcms_themeinvertcolors)."img/button_search.png\" class=\"hcmsButtonOff hcmsButtonSizeSquare\" />";
        }
      }
    }
    ?>
  </div>

</div>




<!-- Download select menu -->
<div id="downloadselectLayer" class="hcmsWorkplaceControl" style="position:absolute; left:0px; <?php if (!$is_mobile) echo "top:36px;"; else echo "top:0px;"; ?> width:100%; <?php if (!$is_mobile) echo "height:64px;"; else echo "height:100%;"; ?> padding:0; margin:0; z-index:1; display:none; overflow:auto;">
  <div style="position:fixed; right:2px; <?php if (!$is_mobile) echo "top:36px;"; else echo "top:2px;"; ?> width:32px; height:32px; z-index:91;">
    <img name="hcms_downloadselectLayerClose" src="<?php echo getthemelocation($hcms_themeinvertcolors); ?>img/button_close.png" class="hcmsButtonTinyBlank hcmsButtonSizeSquare" alt="<?php echo getescapedtext ($hcms_lang['close'][$lang]); ?>" title="<?php echo getescapedtext ($hcms_lang['close'][$lang]); ?>" onMouseOut="hcms_swapImgRestore();" onMouseOver="hcms_swapImage('hcms_downloadselectLayerClose','','<?php echo getthemelocation(); ?>img/button_close_over.png',1);" onclick="closeSubMenu('downloadselectLayer');" />
  </div>

  <div class="hcmsMenu">
    <div class="hcmsInvertPrimaryColor hcmsMenuItem">
      <span class="hcmsHeadline"><?php echo getescapedtext ($hcms_lang['download'][$lang]); ?></span>
      <img src="<?php echo getthemelocation($hcms_themeinvertcolors); ?>img/button_arrow_right.png" class="hcmsIconList" />
    </div>
    <?php

    if ($from_page != "recyclebin" && ($multiobject_count <= 1 && !empty ($page) && !empty ($media)) && $perm_rendering && $lock_rendering && ($doc_rendering || $img_rendering || $dropbox_rendering))
    {    
      // original file
      if (empty ($downloadformats) || (!is_document ($media_info['ext']) && !is_image ($media_info['ext']) && !is_video ($media_info['ext'])) || (is_document ($media_info['ext']) && !empty ($downloadformats['document']['original'])) || (is_image ($media_info['ext']) && !empty ($downloadformats['image']['original'])) || (is_video ($media_info['ext']) && !empty ($downloadformats['video']['original'])))
      {
        // function imgConvert must be used in order to reset the rendering options
        echo "
          <div class=\"hcmsButton hcmsHoverColor hcmsInvertColor hcmsMenuItem\" onclick=\"imgConvert ('','');\">
            <img src=\"".getthemelocation()."img/".$media_info['icon']."\" class=\"hcmsIconList\" />
            <span class=\"\">".getescapedtext ($hcms_lang['original'][$lang])."</span>
          </div>";
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
        <div class=\"hcmsButton hcmsHoverColor hcmsInvertColor hcmsMenuItem\" onclick=\"docConvert ('".$doc_type."');\">
          <img src=\"".getthemelocation()."img/".$doc_info['icon']."\" class=\"hcmsIconList\" />
          <span class=\"\">".$doc_info['type']." (".strtoupper($doc_type).")</span>
        </div>";
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
        <div class=\"hcmsButton hcmsHoverColor hcmsInvertColor hcmsMenuItem\" onclick=\"imgConvert ('".$image_type."', '".$config_name."');\">
          <img src=\"".getthemelocation()."img/".$img_info['icon']."\" class=\"hcmsIconList\" />
          <span class=\"\">".strtoupper($image_type)." ".$config_name."</span>
        </div>";
              }
            }
          }
        }
      }
      
      // video download options
      if ($vid_rendering && is_video ($media))
      {
        if (empty ($downloadformats) || !empty ($downloadformats['video']['origthumb'])) echo "
          <div class=\"hcmsButton hcmsHoverColor hcmsInvertColor hcmsMenuItem\" onclick=\"vidConvert('origthumb');\">
            <img src=\"".getthemelocation()."img/file_mpg.png\" class=\"hcmsIconList\" />
            <span class=\"\">".getescapedtext ($hcms_lang['preview'][$lang])."</span>
          </div>";
          
        if (empty ($downloadformats) || !empty ($downloadformats['video']['jpg'])) echo "
          <div class=\"hcmsButton hcmsHoverColor hcmsInvertColor hcmsMenuItem\" onclick=\"vidConvert('jpg');\">
            <img src=\"".getthemelocation()."img/file_image.png\" class=\"hcmsIconList\" />
            <span class=\"\">".getescapedtext ($hcms_lang['images'][$lang])." (JPG)</span>
          </div>";
          
        if (empty ($downloadformats) || !empty ($downloadformats['video']['png'])) echo "
          <div class=\"hcmsButton hcmsHoverColor hcmsInvertColor hcmsMenuItem\" onclick=\"vidConvert('png');\">
            <img src=\"".getthemelocation()."img/file_image.png\" class=\"hcmsIconList\" />
            <span class=\"\">".getescapedtext ($hcms_lang['images'][$lang])." (PNG)</span>
          </div>";
      }
			
			// save to dropbox
			if ($dropbox_rendering)
			{
				echo "
          <div class=\"hcmsButton hcmsHoverColor hcmsInvertColor hcmsMenuItem\" onclick=\"submitToWindow('popup_save_dropbox.php', 'Save to Dropbox', '', 'location=no,menubar=no,toolbar=no,titlebar=no,status=yes,scrollbars=yes,resizable=yes,width=600,height=400', 600, 400); document.getElementById('button_obj_convert').click();a\">
            <img src=\"".getthemelocation($hcms_themeinvertcolors)."img/file_dropbox.png\" class=\"hcmsIconList\" />
            <span class=\"\">".getescapedtext ($hcms_lang['dropbox'][$lang])."</span>
          </div>";
			}

      echo "
        </div>
      </div>";
    }
    ?>
  </div>
</div>




<!-- Create new objects menu -->
<div id="createLayer" class="hcmsWorkplaceControl" style="position:absolute; left:0px; <?php if (!$is_mobile) echo "top:36px;"; else echo "top:0px;"; ?> width:100%; <?php if (!$is_mobile) echo "height:64px;"; else echo "height:100%;"; ?> padding:0; margin:0; z-index:1; display:none; overflow:auto;">
  <div style="position:fixed; right:2px; <?php if (!$is_mobile) echo "top:36px;"; else echo "top:2px;"; ?> width:32px; height:32px; z-index:91;">
    <img name="hcms_createLayerClose" src="<?php echo getthemelocation($hcms_themeinvertcolors); ?>img/button_close.png" class="hcmsButtonTinyBlank hcmsButtonSizeSquare" alt="<?php echo getescapedtext ($hcms_lang['close'][$lang]); ?>" title="<?php echo getescapedtext ($hcms_lang['close'][$lang]); ?>" onMouseOut="hcms_swapImgRestore();" onMouseOver="hcms_swapImage('hcms_createLayerClose','','<?php echo getthemelocation(); ?>img/button_close_over.png',1);" onclick="closeSubMenu('createLayer');" />
  </div>

  <div class="hcmsMenu">
    <div class="hcmsInvertPrimaryColor hcmsMenuItem">
      <span class="hcmsHeadline"><?php echo getescapedtext ($hcms_lang['create'][$lang]); ?></span>
      <img src="<?php echo getthemelocation($hcms_themeinvertcolors); ?>img/button_arrow_right.png" class="hcmsIconList" />
    </div>
    <?php
    // New Folder Button
    if ($from_page == "" && $setlocalpermission['root'] == 1 && $setlocalpermission['foldercreate'] == 1)
    {
      echo "
      <div class=\"hcmsButton hcmsHoverColor hcmsInvertColor hcmsMenuItem\" onclick=\"if (locklayer == false) openSubMenu('foldercreateLayer');\">
        <img class=\"hcmsIconList\" src=\"".getthemelocation($hcms_themeinvertcolors)."img/button_folder_new.png\" alt=\"".getescapedtext ($hcms_lang['create-folder'][$lang])."\" title=\"".getescapedtext ($hcms_lang['create-folder'][$lang])."\" />
        <span class=\"\">".getescapedtext ($hcms_lang['create-folder'][$lang])."</span>
      </div>";
    }
    ?>

    <?php
    // New Object Button
    if ($from_page == "" && $setlocalpermission['root'] == 1 && $setlocalpermission['create'] == 1)
    {
      echo "
      <div class=\"hcmsButton hcmsHoverColor hcmsInvertColor hcmsMenuItem\" onclick=\"hcms_openWindow('frameset_content.php?site=".url_encode($site)."&cat=".url_encode($cat)."&location=".url_encode($location_esc)."', '', 'location=no,menubar=no,toolbar=no,titlebar=no,status=yes,scrollbars=no,resizable=yes', ".windowwidth("object").", ".windowheight("object").");\">
        <img class=\"hcmsIconList\" src=\"".getthemelocation($hcms_themeinvertcolors)."img/button_file_new.png\" alt=\"".getescapedtext ($hcms_lang['new-object'][$lang])."\" title=\"".getescapedtext ($hcms_lang['new-object'][$lang])."\" />
        <span class=\"\">".getescapedtext ($hcms_lang['new-object'][$lang])."</span>
      </div>";
    }
    ?>

    <?php
    // CSV import
    if ($from_page == "" && $setlocalpermission['root'] == 1 && $setlocalpermission['create'] == 1)
    {
      echo "
      <div class=\"hcmsButton hcmsHoverColor hcmsInvertColor hcmsMenuItem\" onclick=\"if (locklayer == false) openSubMenu('importLayer');\">
        <img class=\"hcmsIconList\" src=\"".getthemelocation($hcms_themeinvertcolors)."img/button_import.png\" alt=\"".getescapedtext ($hcms_lang['import-list-comma-delimited'][$lang])."\" title=\"".getescapedtext ($hcms_lang['import-list-comma-delimited'][$lang])."\" />
        <span class=\"\">".getescapedtext ($hcms_lang['import-list-comma-delimited'][$lang])."</span>
      </div>";
    }
    ?>
  </div>
</div>



<!-- Other actions menu for selected objects -->
<div id="actionsLayer" class="hcmsWorkplaceControl" style="position:absolute; left:0px; <?php if (!$is_mobile) echo "top:36px;"; else echo "top:0px;"; ?> width:100%; <?php if (!$is_mobile) echo "height:64px;"; else echo "height:100%;"; ?> padding:0; margin:0; z-index:1; display:none; overflow:auto;">
  <div style="position:fixed; right:2px; <?php if (!$is_mobile) echo "top:36px;"; else echo "top:2px;"; ?> width:32px; height:32px; z-index:91;">
    <img name="hcms_actionsLayerClose" src="<?php echo getthemelocation($hcms_themeinvertcolors); ?>img/button_close.png" class="hcmsButtonTinyBlank hcmsButtonSizeSquare" alt="<?php echo getescapedtext ($hcms_lang['close'][$lang]); ?>" title="<?php echo getescapedtext ($hcms_lang['close'][$lang]); ?>" onMouseOut="hcms_swapImgRestore();" onMouseOver="hcms_swapImage('hcms_actionsLayerClose','','<?php echo getthemelocation(); ?>img/button_close_over.png',1);" onclick="closeSubMenu('actionsLayer');" />
  </div>

  <div class="hcmsMenu">
    <div class="hcmsInvertPrimaryColor hcmsMenuItem">
      <span class="hcmsHeadline"><?php echo getescapedtext ($hcms_lang['action'][$lang]); ?></span>
      <img src="<?php echo getthemelocation($hcms_themeinvertcolors); ?>img/button_arrow_right.png" class="hcmsIconList" />
    </div>
    <?php
    // Preview Button
    if ($multiobject_count <= 1 && $page != "" && $cat != "" && $setlocalpermission['root'] == 1)
    {
      if ($page != ".folder") echo "
      <div class=\"hcmsButton hcmsHoverColor hcmsInvertColor hcmsMenuItem\" onclick=\"openObjectView('".$location_esc."', '".$page."', 'preview');\">
        <img class=\"hcmsIconList\" src=\"".getthemelocation($hcms_themeinvertcolors)."img/button_file_preview.png\" alt=\"".getescapedtext ($hcms_lang['preview'][$lang])."\" title=\"".getescapedtext ($hcms_lang['preview'][$lang])."\" />
        <span class=\"\">".getescapedtext ($hcms_lang['preview'][$lang])."</span>
      </div>";
      elseif ($page == ".folder") echo "
      <div class=\"hcmsButton hcmsHoverColor hcmsInvertColor hcmsMenuItem\" onclick=\"openObjectView('".$location_esc.$folder."/', '".$page."', 'preview');\">
        <img class=\"hcmsIconList\" src=\"".getthemelocation($hcms_themeinvertcolors)."img/button_file_preview.png\" alt=\"".getescapedtext ($hcms_lang['preview'][$lang])."\" title=\"".getescapedtext ($hcms_lang['preview'][$lang])."\" />
        <span class=\"\">".getescapedtext ($hcms_lang['preview'][$lang])."</span>
      </div>";
    }
    ?>
    <?php
    // Live-View Button
    if ($multiobject_count <= 1 && $page != "" && $from_page != "recyclebin" && 
        !empty ($file_info['published']) && $page != ".folder" && valid_publicationname ($site) && 
        $setlocalpermission['root'] == 1 && 
        empty ($media) && $cat == "page"
    )
    {
      echo "
      <div class=\"hcmsButton hcmsHoverColor hcmsInvertColor hcmsMenuItem\" onclick=\"openObjectView('".$location_esc."', '".$page."', 'liveview');\">
        <img class=\"hcmsIconList\" id=\"pic_obj_liveview\" src=\"".getthemelocation($hcms_themeinvertcolors)."img/button_file_liveview.png\" alt=\"".getescapedtext ($hcms_lang['view-live'][$lang])."\" title=\"".getescapedtext ($hcms_lang['view-live'][$lang])."\" />
        <span class=\"\">".getescapedtext ($hcms_lang['view-live'][$lang])."</span>
      </div>";
    }
    ?>

    <?php
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
        if ($page != ".folder") $openlink = "openInMainFrame('frameset_content.php?site=".url_encode($site)."&ctrlreload=yes&cat=".url_encode($cat)."&location=".url_encode($location_esc)."&page=".url_encode($page)."', ''); ";
        else $openlink = "openInMainFrame('frameset_content.php?site=".url_encode($site)."&ctrlreload=yes&cat=".url_encode($cat)."&location=".url_encode($location_esc.$folder)."/&page=".url_encode($page)."', ''); ";
      }

      echo "
      <div class=\"hcmsButton hcmsHoverColor hcmsInvertColor hcmsMenuItem\" onclick=\"".$openlink."\">
        <img class=\"hcmsIconList\" id=\"pic_obj_edit\" src=\"".getthemelocation($hcms_themeinvertcolors)."img/button_edit.png\" alt=\"".getescapedtext($hcms_lang['edit'][$lang])."\" title=\"".getescapedtext ($hcms_lang['edit'][$lang])."\" />
        <span class=\"\">".getescapedtext ($hcms_lang['edit'][$lang])."</span>
      </div>";
    }
    // Edit button to edit the fields which are equal across all selected objects
    elseif ($multiobject_count > 1 && $from_page != "recyclebin")
    {
      if (!empty ($mgmt_config['object_newwindow'])) $openlink = "submitToWindow('page_multiedit.php', '', 'multiedit', 'status=yes,scrollbars=yes,resizable=yes', ".windowwidth("object").", ".windowheight("object").");";
      else $openlink = "submitToMainFrame('page_multiedit.php', '');";

      echo "
      <div class=\"hcmsButton hcmsHoverColor hcmsInvertColor hcmsMenuItem\" onclick=\"".$openlink."\">
        <img class=\"hcmsIconList\" id=\"pic_obj_edit\" src=\"".getthemelocation($hcms_themeinvertcolors)."img/button_edit.png\" alt=\"".getescapedtext ($hcms_lang['edit'][$lang])."\" title=\"".getescapedtext ($hcms_lang['edit'][$lang])."\" />
        <span class=\"\">".getescapedtext ($hcms_lang['edit'][$lang])."</span>
      </div>";
    }
    
    // Rename Object Button
    if (($usedby == "" || $usedby == $user) && $multiobject_count <= 1 && $from_page != "recyclebin" && $page != "" && $page != ".folder" && $setlocalpermission['root'] == 1 && $setlocalpermission['rename'] == 1)
    {
      echo "
      <div class=\"hcmsButton hcmsHoverColor hcmsInvertColor hcmsMenuItem\" onclick=\"openSubMenu('objrenameLayer');\">
        <img class=\"hcmsIconList\" id=\"pic_obj_rename\" src=\"".getthemelocation($hcms_themeinvertcolors)."img/button_rename.png\" alt=\"".getescapedtext ($hcms_lang['rename'][$lang])."\" title=\"".getescapedtext ($hcms_lang['rename'][$lang])."\" />
        <span class=\"\">".getescapedtext ($hcms_lang['rename'][$lang])."</span>
      </div>";
    }
    // Rename Folder Button
    elseif (($usedby == "" || $usedby == $user) && $multiobject_count <= 1 && $from_page != "recyclebin" && $folder != "" && $setlocalpermission['root'] == 1 && $setlocalpermission['folderrename'] == 1)
    {
      echo "
      <div class=\"hcmsButton hcmsHoverColor hcmsInvertColor hcmsMenuItem\" onclick=\"if (locklayer == false) openSubMenu('folderrenameLayer');\">
        <img class=\"hcmsIconList\" id=\"pic_folder_rename\" src=\"".getthemelocation($hcms_themeinvertcolors)."img/button_rename.png\" alt=\"".getescapedtext ($hcms_lang['rename-folder'][$lang])."\" title=\"".getescapedtext ($hcms_lang['rename-folder'][$lang])."\" />
        <span class=\"\">".getescapedtext ($hcms_lang['rename'][$lang])."</span>
      </div>";
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
      <div class=\"hcmsButton hcmsHoverColor hcmsInvertColor hcmsMenuItem\" onclick=\"if (locklayer == false) checkForm_delete();\">
        <img class=\"hcmsIconList\" id=\"pic_obj_delete\" src=\"".getthemelocation($hcms_themeinvertcolors)."img/button_delete.png\" alt=\"".getescapedtext ($hcms_lang['delete'][$lang])."\" title=\"".getescapedtext ($hcms_lang['delete'][$lang])."\" />
        <span class=\"\">".getescapedtext ($hcms_lang['delete'][$lang])."</span>
      </div>";
    } 

    // Cut, Copy, Linked-Copy Button
    // if object (object permissions)
    if (($multiobject_count > 0 || ($page != ".folder" && $page != "")) && $from_page != "recyclebin")
    {
      if ($setlocalpermission['root'] == 1 && $setlocalpermission['rename'] == 1)
      {
        echo "
        <div class=\"hcmsButton hcmsHoverColor hcmsInvertColor hcmsMenuItem\" onclick=\"if (locklayer == false) submitToPopup('popup_action.php', 'cut', 'cut".uniqid()."');\">
          <img class=\"hcmsIconList\" id=\"pic_obj_cut\" ".
          "src=\"".getthemelocation($hcms_themeinvertcolors)."img/button_file_cut.png\" alt=\"".getescapedtext ($hcms_lang['cut'][$lang])."\" title=\"".getescapedtext ($hcms_lang['cut'][$lang])."\" />
          <span class=\"\">".getescapedtext ($hcms_lang['cut'][$lang])."</span>
        </div>";
      }

      if ($setlocalpermission['root'] == 1 && $setlocalpermission['rename'] == 1)
      {
        echo "
        <div class=\"hcmsButton hcmsHoverColor hcmsInvertColor hcmsMenuItem\" onclick=\"if (locklayer == false) submitToPopup('popup_action.php', 'copy', 'copy".uniqid()."');\">
          <img class=\"hcmsIconList\" id=\"pic_obj_copy\" ".
          "src=\"".getthemelocation($hcms_themeinvertcolors)."img/button_file_copy.png\" alt=\"".getescapedtext ($hcms_lang['copy'][$lang])."\" title=\"".getescapedtext ($hcms_lang['copy'][$lang])."\" />
          <span class=\"\">".getescapedtext ($hcms_lang['copy'][$lang])."</span>
        </div>";
      }

      if ($setlocalpermission['root'] == 1 && $setlocalpermission['rename'] == 1)
      {
        echo "
        <div class=\"hcmsButton hcmsHoverColor hcmsInvertColor hcmsMenuItem\" onclick=\"if (locklayer == false) submitToPopup('popup_action.php', 'linkcopy', 'linkcopy".uniqid()."');\">
          <img class=\"hcmsIconList\" id=\"pic_obj_linkedcopy\" ".
          "src=\"".getthemelocation($hcms_themeinvertcolors)."img/button_file_copylinked.png\" alt=\"".getescapedtext ($hcms_lang['connected-copy'][$lang])."\" title=\"".getescapedtext ($hcms_lang['connected-copy'][$lang])."\">
          <span class=\"\">".getescapedtext ($hcms_lang['connected-copy'][$lang])."</span>
        </div>";
      }
    }
    // if folder (folder permissions)
    elseif (($multiobject_count > 0 || $folder != "") && $from_page != "recyclebin")
    {
      if ($setlocalpermission['root'] == 1 && $setlocalpermission['folderrename'] == 1)
      {
        echo "
        <div class=\"hcmsButton hcmsHoverColor hcmsInvertColor hcmsMenuItem\" onclick=\"if (locklayer == false) submitToPopup('popup_action.php', 'cut', 'cut".uniqid()."');\">
          <img class=\"hcmsIconList\" id=\"pic_obj_cut\" src=\"".getthemelocation($hcms_themeinvertcolors)."img/button_file_cut.png\" alt=\"".getescapedtext ($hcms_lang['cut'][$lang])."\" title=\"".getescapedtext ($hcms_lang['cut'][$lang])."\" />
          <span class=\"\">".getescapedtext ($hcms_lang['cut'][$lang])."</span>
        </div>";
      }

      if ($setlocalpermission['root'] == 1 && $setlocalpermission['folderrename'] == 1)
      {
        echo "
        <div class=\"hcmsButton hcmsHoverColor hcmsInvertColor hcmsMenuItem\" onclick=\"if (locklayer == false) submitToPopup('popup_action.php', 'copy', 'copy".uniqid()."');\">
          <img class=\"hcmsIconList\" id=\"pic_obj_copy\" src=\"".getthemelocation($hcms_themeinvertcolors)."img/button_file_copy.png\" alt=\"".getescapedtext ($hcms_lang['copy'][$lang])."\" title=\"".getescapedtext ($hcms_lang['copy'][$lang])."\" />
          <span class=\"\">".getescapedtext ($hcms_lang['copy'][$lang])."</span>
        </div>";
      }

      if ($setlocalpermission['root'] == 1 && $setlocalpermission['folderrename'] == 1 && $setlocalpermission['foldercreate'] == 1)
      {
        echo "
        <div class=\"hcmsButton hcmsHoverColor hcmsInvertColor hcmsMenuItem\" onclick=\"if (locklayer == false) submitToPopup('popup_action.php', 'linkcopy', 'linkcopy".uniqid()."');\">
          <img class=\"hcmsIconList\" id=\"pic_obj_linkedcopy\" src=\"".getthemelocation($hcms_themeinvertcolors)."img/button_file_copylinked.png\" alt=\"".getescapedtext ($hcms_lang['connected-copy'][$lang])."\" title=\"".getescapedtext ($hcms_lang['connected-copy'][$lang])."\" />
          <span class=\"\">".getescapedtext ($hcms_lang['connected-copy'][$lang])."</span>
        </div>";
      }     
    }
    // if Recycle Bin
    elseif ($from_page == "recyclebin") 
    {
      // empty recycle bin
      echo "
      <div class=\"hcmsButton hcmsHoverColor hcmsInvertColor hcmsMenuItem\" onclick=\"emptyRecycleBin('".$token_new."');\">
        <img class=\"hcmsIconList\" id=\"pic_obj_emptybin\" src=\"".getthemelocation($hcms_themeinvertcolors)."img/button_recycle_bin.png\" alt=\"".getescapedtext ($hcms_lang['empty-recycle-bin'][$lang])."\" title=\"".getescapedtext ($hcms_lang['empty-recycle-bin'][$lang])."\" />
        <span class=\"\">".getescapedtext ($hcms_lang['empty-recycle-bin'][$lang])."</span>
      </div>";

      // restore from bin
      if ($multiobject_count > 0 || $folder != "" || $page != "")
      {
        echo "
        <div class=\"hcmsButton hcmsHoverColor hcmsInvertColor hcmsMenuItem\" onclick=\"if (locklayer == false) submitToPopup('popup_status.php', 'restore', 'restore".uniqid()."');\">
          <img class=\"hcmsIconList\" id=\"pic_obj_restorebin\" src=\"".getthemelocation($hcms_themeinvertcolors)."img/button_import.png\" alt=\"".getescapedtext ($hcms_lang['restore'][$lang])."\" title=\"".getescapedtext ($hcms_lang['restore'][$lang])."\" />
          <span class=\"\">".getescapedtext ($hcms_lang['restore'][$lang])."</span>
        </div>";
      }
    }
    ?>

    <?php
    // ZIP Button
    if ($from_page == "" && !empty ($mgmt_compress['.zip']) && 
        ($usedby == "" || $usedby == $user) && 
        ($multiobject_count > 0 || $page != "") &&  
        $setlocalpermission['root'] == 1 && $setlocalpermission['create'] == 1 && $cat != "page"
      )
    {
      echo "
      <div class=\"hcmsButton hcmsHoverColor hcmsInvertColor hcmsMenuItem\" onclick=\"if (locklayer == false) openSubMenu('zipLayer');\">
        <img class=\"hcmsIconList\" src=\"".getthemelocation($hcms_themeinvertcolors)."img/button_zip.png\" alt=\"".getescapedtext ($hcms_lang['compress-files'][$lang])."\" title=\"".getescapedtext ($hcms_lang['compress-files'][$lang])."\" />
        <span class=\"\">".getescapedtext ($hcms_lang['compress-files'][$lang])."</span>
      </div>";
    }
    ?>
    <?php
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
      <div class=\"hcmsButton hcmsHoverColor hcmsInvertColor hcmsMenuItem\"onclick=\"if (locklayer == false) unzip('unzip".uniqid()."');\">
        <img class=\"hcmsIconList\" src=\"".getthemelocation($hcms_themeinvertcolors)."img/button_unzip.png\" alt=\"".getescapedtext ($hcms_lang['extract-file'][$lang])."\" title=\"".getescapedtext ($hcms_lang['extract-file'][$lang])."\" />
        <span class=\"\">".getescapedtext ($hcms_lang['extract-file'][$lang])."</span>
      </div>";
    }
    ?>

    <?php    
    // Send Mail Button
    if (($multiobject_count > 0 || $page != "") && $from_page != "recyclebin" && !empty ($mgmt_config['smtp_host']) && !empty ($mgmt_config[$site]['sendmail']) && $setlocalpermission['root'] == 1 && $setlocalpermission['sendlink'] == 1 && !empty ($mgmt_config['db_connect_rdbms']))
    {
      echo "
      <div class=\"hcmsButton hcmsHoverColor hcmsInvertColor hcmsMenuItem\" ";
        if (!empty ($mgmt_config['message_newwindow'])) echo "onclick=\"submitToWindow('user_sendlink.php', '', 'sendlink', 'location=no,menubar=no,toolbar=no,titlebar=no,scrollbars=yes,resizable=no', 540, 800);\" ";
        else echo "onclick=\"submitToFrame('user_sendlink.php', 'sendlink');\" ";
        echo ">
        <img class=\"hcmsIconList\" ".
        "src=\"".getthemelocation($hcms_themeinvertcolors)."img/button_user_sendlink.png\" ".
        "alt=\"".getescapedtext ($hcms_lang['send-mail-link'][$lang])."\" title=\"".getescapedtext ($hcms_lang['send-mail-link'][$lang])."\" />
        <span class=\"\">".getescapedtext ($hcms_lang['send-mail-link'][$lang])."</span>
      </div>";
    }
    ?>
    <?php
    // Send to Chat Button
    if ($multiobject_count <= 1 && $page != "" && $from_page != "recyclebin" && $setlocalpermission['root'] == 1 && !empty ($mgmt_config['chat']))
    {
      if ($page != ".folder") $chatcontent = "hcms_openWindow(\\'frameset_content.php?site=".url_encode($site)."&ctrlreload=yes&cat=".url_encode($cat)."&location=".url_encode($location_esc)."&page=".url_encode($page)."\\', \\'\\', \\'location=no,menubar=no,toolbar=no,titlebar=no,status=yes,scrollbars=no,resizable=yes\\', ".windowwidth("object").", ".windowheight("object").");";
      elseif ($page == ".folder") $chatcontent = "hcms_openWindow(\\'frameset_content.php?site=".url_encode($site)."&ctrlreload=yes&cat=".url_encode($cat)."&location=".url_encode($location_esc.$folder)."/&page=".url_encode($page)."\\', \\'\\', \\'location=no,menubar=no,toolbar=no,titlebar=no,status=yes,scrollbars=no,resizable=yes\\', ".windowwidth("object").", ".windowheight("object").");";
      
      echo "
      <div class=\"hcmsButton hcmsHoverColor hcmsInvertColor hcmsMenuItem\" onclick=\"sendtochat('".$chatcontent."');\">
        <img class=\"hcmsIconList\" ".
        "src=\"".getthemelocation($hcms_themeinvertcolors)."img/button_chat.png\" ".
        "alt=\"".getescapedtext ($hcms_lang['send-to-chat'][$lang])."\" title=\"".getescapedtext ($hcms_lang['send-to-chat'][$lang])."\" />
        <span class=\"\">".getescapedtext ($hcms_lang['send-to-chat'][$lang])."</span>
      </div>";
    }
    ?>

    <?php
    // publish
    if (($multiobject_count > 0 || $page != "") && $from_page != "recyclebin" && $setlocalpermission['root'] == 1 && $setlocalpermission['publish'] == 1)
    {
      echo "
      <div class=\"hcmsButton hcmsHoverColor hcmsInvertColor hcmsMenuItem\" onclick=\"if (locklayer == false) submitToPopup('popup_publish.php', 'publish', 'publish".uniqid()."');\">
        <img class=\"hcmsIconList\" src=\"".getthemelocation($hcms_themeinvertcolors)."img/button_file_publish.png\" alt=\"".getescapedtext ($hcms_lang['publish'][$lang])."\" title=\"".getescapedtext ($hcms_lang['publish'][$lang])."\" />
        <span class=\"\">".getescapedtext ($hcms_lang['publish'][$lang])."</span>
      </div>";
    }
    ?>
    <?php
    // unpublish
    if (($multiobject_count > 0 || $page != "") && $from_page != "recyclebin" && $setlocalpermission['root'] == 1 && $setlocalpermission['publish'] == 1)
    {
      echo "
      <div class=\"hcmsButton hcmsHoverColor hcmsInvertColor hcmsMenuItem\" onclick=\"if (locklayer == false) submitToPopup('popup_publish.php', 'unpublish', 'unpublish".uniqid()."');\">
        <img class=\"hcmsIconList\" src=\"".getthemelocation($hcms_themeinvertcolors)."img/button_file_unpublish.png\" alt=\"".getescapedtext ($hcms_lang['unpublish'][$lang])."\" title=\"".getescapedtext ($hcms_lang['unpublish'][$lang])."\" />
        <span class=\"\">".getescapedtext ($hcms_lang['unpublish'][$lang])."</span>
      </div>";
    }
    ?>

    <?php
    // CSV export
    if (($usedby == "" || $usedby == $user) && ($page != "" || $multiobject_count > 0) && !empty ($mgmt_config['db_connect_rdbms']) && $from_page != "recyclebin")
    {
      echo "
      <div class=\"hcmsButton hcmsHoverColor hcmsInvertColor hcmsMenuItem\" onclick=\"submitToSelf('export');\"> 
        <img class=\"hcmsIconList\" src=\"".getthemelocation($hcms_themeinvertcolors)."img/button_export_page.png\" alt=\"".getescapedtext ($hcms_lang['export-list-comma-delimited'][$lang])."\" title=\"".getescapedtext ($hcms_lang['export-list-comma-delimited'][$lang])."\" />
        <span class=\"\">".getescapedtext ($hcms_lang['export-list-comma-delimited'][$lang])."</span>
      </div>";
    }
    ?>
  </div>
</div>



<!-- filter bar -->
<?php if (!$is_mobile && $from_page == "" && $cat != "page") { ?>
<div id="filterLayer" style="position:fixed; bottom:3px; left:3px; margin:0; padding:0px; visibility:hidden;">
  <form name="filter_set" action="explorer_objectlist.php" target="mainFrame" method="get">
    <input type="hidden" name="location" value="<?php echo $location_esc; ?>" />
    <input type="hidden" name="virtual" value="<?php echo $virtual; ?>" />
    <img src="<?php echo getthemelocation($hcms_themeinvertcolors); ?>img/button_filter.png" class="hcmsIconList" style="vertical-align:middle;" />
    <input type="hidden" name="filter[dummy]" value="1" />
    <input type="checkbox" id="filter1" onclick="setFilter();" name="filter[comp]" value="1" <?php if (!empty ($objectfilter['comp'])) echo "checked=\"checked\""; ?>/>&nbsp;<label for="filter1" class="hcmsInvertColor"><span><?php echo getescapedtext ($hcms_lang['component'][$lang]); ?></span></label>&nbsp;&nbsp;
    <input type="checkbox" id="filter2" onclick="setFilter();" name="filter[image]" value="1" <?php if (!empty ($objectfilter['image'])) echo "checked=\"checked\""; ?>/>&nbsp;<label for="filter2" class="hcmsInvertColor"><span><?php echo getescapedtext ($hcms_lang['image'][$lang]); ?></span></label>&nbsp;&nbsp;
    <input type="checkbox" id="filter3" onclick="setFilter();" name="filter[document]" value="1" <?php if (!empty ($objectfilter['document'])) echo "checked=\"checked\""; ?>/>&nbsp;<label for="filter3" class="hcmsInvertColor"><span><?php echo getescapedtext ($hcms_lang['document'][$lang]); ?></span></label>&nbsp;&nbsp;
    <input type="checkbox" id="filter4" onclick="setFilter();" name="filter[video]" value="1" <?php if (!empty ($objectfilter['video'])) echo "checked=\"checked\""; ?>/>&nbsp;<label for="filter4" class="hcmsInvertColor"><span><?php echo getescapedtext ($hcms_lang['video'][$lang]); ?></span></label>&nbsp;&nbsp;
    <input type="checkbox" id="filter5" onclick="setFilter();" name="filter[audio]" value="1" <?php if (!empty ($objectfilter['audio'])) echo "checked=\"checked\""; ?>/>&nbsp;<label for="filter5" class="hcmsInvertColor"><span><?php echo getescapedtext ($hcms_lang['audio'][$lang]); ?></span></label>&nbsp;&nbsp;
  </form>
</div>
<?php } ?>

<!-- create folder -->
<div id="foldercreateLayer" class="hcmsMessage" style="position:absolute; left:5px; top:5px; max-width:95%; z-index:1; visibility:hidden;">
  <form name="folder_create" action="" method="post" onsubmit="return checkForm_folder_create();">
    <input type="hidden" name="location" value="<?php echo $location_esc; ?>" />
    <input type="hidden" name="action" value="folder_create" />
    <input type="hidden" name="token" value="<?php echo $token_new; ?>">
    
    <table class="hcmsTableNarrow" style="width:100%; min-height:60px;">
      <tr>
        <td>
          <span class="hcmsHeadline"><?php echo getescapedtext ($hcms_lang['create-folder'][$lang]); ?></span><br />
          <span style="white-space:nowrap;">
            <input type="text" name="foldernew" maxlength="<?php if (!empty ($mgmt_config['max_digits_filename']) && intval ($mgmt_config['max_digits_filename']) > 0) echo intval ($mgmt_config['max_digits_filename']); else echo "200"; ?>" style="width:<?php if ($is_mobile) echo "200px"; else echo "300px"; ?>;" placeholder="<?php echo getescapedtext ($hcms_lang['name'][$lang]); ?>" title="<?php echo getescapedtext ($hcms_lang['name'][$lang]); ?>" />
            <img name="Button1" src="<?php echo getthemelocation(); ?>img/button_ok.png" class="hcmsButtonTinyBlank hcmsButtonSizeSquare" onclick="checkForm_folder_create();" onMouseOut="hcms_swapImgRestore()" onMouseOver="hcms_swapImage('Button1','','<?php echo getthemelocation(); ?>img/button_ok_over.png',1)" alt="OK" title="OK" />
          </span>
        </td>
        <td style="width:38px; text-align:right; vertical-align:top;">
          <img name="hcms_foldercreateLayerClose" src="<?php echo getthemelocation(); ?>img/button_close.png" class="hcmsButtonTinyBlank hcmsButtonSizeSquare" alt="<?php echo getescapedtext ($hcms_lang['close'][$lang]); ?>" title="<?php echo getescapedtext ($hcms_lang['close'][$lang]); ?>" onMouseOut="hcms_swapImgRestore();" onMouseOver="hcms_swapImage('hcms_foldercreateLayerClose','','<?php echo getthemelocation(); ?>img/button_close_over.png',1);" onclick="hcms_showHideLayers('foldercreateLayer','','hide');" />
        </td>      
      </tr>
    </table>
  </form>
</div>

<!-- rename folder -->
<div id="folderrenameLayer" class="hcmsMessage" style="position:absolute; left:5px; top:5px; max-width:95%; z-index:2; visibility:hidden;">
  <form name="folder_rename" action="" method="post" onsubmit="return checkForm_folder_rename();">
    <input type="hidden" name="location" value="<?php echo $location_esc; ?>" />
    <input type="hidden" name="folder" value="<?php echo $folder; ?>" />
    <input type="hidden" name="action" value="folder_rename" />
    <input type="hidden" name="token" value="<?php echo $token_new; ?>">
    
    <table class="hcmsTableNarrow" style="width:100%; min-height:60px;">
      <tr>
        <td>
          <span class="hcmsHeadline"><?php echo getescapedtext ($hcms_lang['rename-folder'][$lang]); ?></span><br />
          <span style="white-space:nowrap;">
            <input type="text" name="foldernew" maxlength="<?php if (!empty ($mgmt_config['max_digits_filename']) && intval ($mgmt_config['max_digits_filename']) > 0) echo intval ($mgmt_config['max_digits_filename']); else echo "200"; ?>" style="width:<?php if ($is_mobile) echo "200px"; else echo "300px"; ?>;" value="<?php echo $pagename; ?>" />
            <img name="Button2" src="<?php echo getthemelocation(); ?>img/button_ok.png" class="hcmsButtonTinyBlank hcmsButtonSizeSquare" onclick="checkForm_folder_rename();" onMouseOut="hcms_swapImgRestore()" onMouseOver="hcms_swapImage('Button2','','<?php echo getthemelocation(); ?>img/button_ok_over.png',1)" alt="OK" title="OK" />
          </span>
        </td>
        <td style="width:38px; text-align:right; vertical-align:top;">
          <img name="hcms_folderrenameLayerClose" src="<?php echo getthemelocation(); ?>img/button_close.png" class="hcmsButtonTinyBlank hcmsButtonSizeSquare" alt="<?php echo getescapedtext ($hcms_lang['close'][$lang]); ?>" title="<?php echo getescapedtext ($hcms_lang['close'][$lang]); ?>" onMouseOut="hcms_swapImgRestore();" onMouseOver="hcms_swapImage('hcms_folderrenameLayerClose','','<?php echo getthemelocation(); ?>img/button_close_over.png',1);" onclick="hcms_showHideLayers('folderrenameLayer','','hide');" />
        </td>        
      </tr>
    </table>
  </form>
</div>

<!-- rename object -->
<div id="objrenameLayer" class="hcmsMessage" style="position:absolute; left:5px; top:5px; max-width:95%; z-index:3; visibility:hidden;">
  <form name="page_rename" action="" onsubmit="return checkForm_page_rename();">
    <input type="hidden" name="location" value="<?php echo $location_esc; ?>" />
    <input type="hidden" name="page" value="<?php echo $page; ?>" />
    <input type="hidden" name="action" value="page_rename" />
    <input type="hidden" name="token" value="<?php echo $token_new; ?>">
    
    <table class="hcmsTableNarrow" style="width:100%; min-height:60px;">
      <tr>
        <td>
          <span class="hcmsHeadline"><?php echo getescapedtext ($hcms_lang['rename'][$lang]);  if ($filetype == "Page" || $filetype == "Component") echo " (".getescapedtext ($hcms_lang['name-without-ext'][$lang]).")"; ?></span><br />
          <span style="white-space:nowrap;">
            <input type="text" name="pagenew" maxlength="<?php if (!empty ($mgmt_config['max_digits_filename']) && intval ($mgmt_config['max_digits_filename']) > 0) echo intval ($mgmt_config['max_digits_filename']); else echo "200"; ?>" style="width:<?php if ($is_mobile) echo "200px"; else echo "300px"; ?>;" value="<?php echo substr ($pagename, 0, strrpos ($pagename, ".")); ?>" />
            <img name="Button5" src="<?php echo getthemelocation(); ?>img/button_ok.png" class="hcmsButtonTinyBlank hcmsButtonSizeSquare" onclick="checkForm_page_rename();" onMouseOut="hcms_swapImgRestore()" onMouseOver="hcms_swapImage('Button5','','<?php echo getthemelocation(); ?>img/button_ok_over.png',1)" alt="OK" title="OK" />
          </span>
        </td>
        <td style="width:38px; text-align:right; vertical-align:top;">
          <img name="hcms_objrenameLayerClose" src="<?php echo getthemelocation(); ?>img/button_close.png" class="hcmsButtonTinyBlank hcmsButtonSizeSquare" alt="<?php echo getescapedtext ($hcms_lang['close'][$lang]); ?>" title="<?php echo getescapedtext ($hcms_lang['close'][$lang]); ?>" onMouseOut="hcms_swapImgRestore();" onMouseOver="hcms_swapImage('hcms_objrenameLayerClose','','<?php echo getthemelocation(); ?>img/button_close_over.png',1);" onclick="hcms_showHideLayers('objrenameLayer','','hide');" />
        </td>      
      </tr>
    </table>
  </form>
</div>

<!-- create ZIP file -->
<div id="zipLayer" class="hcmsMessage" style="position:absolute; left:5px; top:5px; max-width:95%; visibility:hidden;">
  <form name="page_zip" action="" onsubmit="return checkForm_zip();">
    <input type="hidden" name="location" value="<?php echo $location_esc; ?>" />
    <input type="hidden" name="page" value="<?php echo $page; ?>" />
    <input type="hidden" name="folder" value="<?php echo $folder; ?>" />
    <input type="hidden" name="action" value="zip" />
    <input type="hidden" name="multiobject" value="<?php echo $multiobject ?>" />
    <input type="hidden" name="token" value="<?php echo $token_new; ?>" />
    
    <table class="hcmsTableNarrow" style="width:100%; min-height:60px;">
      <tr>
        <td>
          <span class="hcmsHeadline"><?php echo getescapedtext ($hcms_lang['create-zip-file-without-ext'][$lang]); ?></span><br />
          <span style="white-space:nowrap;">
            <input type="text" name="pagenew" maxlength="100" style="width:<?php if ($is_mobile) echo "200px"; else echo "300px"; ?>;" value="<?php echo substr ($pagename, 0, strrpos ($pagename, ".")); ?>" />
            <img name="Button6" src="<?php echo getthemelocation(); ?>img/button_ok.png" onclick="checkForm_zip();" class="hcmsButtonTinyBlank hcmsButtonSizeSquare" onMouseOut="hcms_swapImgRestore()" onMouseOver="hcms_swapImage('Button6','','<?php echo getthemelocation(); ?>img/button_ok_over.png',1)" alt="OK" title="OK" />
          </span>
        </td>
        <td style="width:38px; text-align:right; vertical-align:top;">
          <img name="hcms_zipLayerClose" src="<?php echo getthemelocation(); ?>img/button_close.png" class="hcmsButtonTinyBlank hcmsButtonSizeSquare" alt="<?php echo getescapedtext ($hcms_lang['close'][$lang]); ?>" title="<?php echo getescapedtext ($hcms_lang['close'][$lang]); ?>" onMouseOut="hcms_swapImgRestore();" onMouseOver="hcms_swapImage('hcms_zipLayerClose','','<?php echo getthemelocation(); ?>img/button_close_over.png',1);" onclick="hcms_showHideLayers('zipLayer','','hide');" />
        </td>       
      </tr>
    </table>
  </form>
</div>

<!-- import CSV data -->
<div id="importLayer" class="hcmsMessage" style="position:absolute; left:5px; top:5px; max-width:95%; z-index:1; visibility:hidden;">
  <form name="import" action="" method="post" enctype="multipart/form-data" onsubmit="return checkForm_import();">
    <input type="hidden" name="location" value="<?php echo $location_esc; ?>" />
    <input type="hidden" name="action" value="import" />
    <input type="hidden" name="token" value="<?php echo $token_new; ?>">
    
    <table class="hcmsTableNarrow" style="width:100%; min-height:75px;">
      <tr>
        <td>
          <span class="hcmsHeadline"><?php echo str_replace ("(", "<br/>(", getescapedtext ($hcms_lang['upload-csv-file'][$lang])); ?></span><br />
          <span style="white-space:nowrap;">
            <input name="importfile" type="file" style="width:<?php if ($is_mobile) echo "200px"; else echo "300px"; ?>;" accept="text/*" />
            <img name="Button7" src="<?php echo getthemelocation(); ?>img/button_ok.png" class="hcmsButtonTinyBlank hcmsButtonSizeSquare" onclick="checkForm_import();" onMouseOut="hcms_swapImgRestore()" onMouseOver="hcms_swapImage('Button7','','<?php echo getthemelocation(); ?>img/button_ok_over.png',1)" alt="OK" title="OK" />
          </span>
        </td>
        <td style="width:38px; text-align:right; vertical-align:top;">
          <img name="hcms_importLayerClose" src="<?php echo getthemelocation(); ?>img/button_close.png" class="hcmsButtonTinyBlank hcmsButtonSizeSquare" alt="<?php echo getescapedtext ($hcms_lang['close'][$lang]); ?>" title="<?php echo getescapedtext ($hcms_lang['close'][$lang]); ?>" onMouseOut="hcms_swapImgRestore();" onMouseOver="hcms_swapImage('hcms_importLayerClose','','<?php echo getthemelocation(); ?>img/button_close_over.png',1);" onclick="hcms_showHideLayers('importLayer','','hide');" />
        </td>      
      </tr>
    </table>
  </form>
</div>

<!-- download -->
<div id="downloadLayer" class="hcmsMessage" style="position:absolute; left:5px; top:5px; max-width:95%; z-index:15; visibility:<?php echo ($action == 'download' ? 'visible' : 'hidden'); ?>;" >
  <table class="hcmsTableNarrow" style="width:100%; min-height:40px;">
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
        <img name="hcms_downloadLayerClose" src="<?php echo getthemelocation(); ?>img/button_close.png" class="hcmsButtonTinyBlank hcmsButtonSizeSquare" alt="<?php echo getescapedtext ($hcms_lang['close'][$lang]); ?>" title="<?php echo getescapedtext ($hcms_lang['close'][$lang]); ?>" onMouseOut="hcms_swapImgRestore();" onMouseOver="hcms_swapImage('hcms_downloadLayerClose','','<?php echo getthemelocation(); ?>img/button_close_over.png',1);" onclick="hcms_showHideLayers('downloadLayer','','hide');" />
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
    echo showmessage (str_replace ("%filesize%", $mgmt_config['maxzipsize'], $hcms_lang['download-failed-max'][$lang]), 660, 65, $lang, "position:fixed; left:5px; top:5px;");
  }
}
?>

</body>
</html>
