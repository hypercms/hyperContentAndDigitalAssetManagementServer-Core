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

// verify the permissions of the user in the API functions
$mgmt_config['api_checkpermission'] = true;


// input parameters
$action = getrequest ("action");
$action_esc = getrequest_esc ("action");
$multiobject = getrequest ("multiobject");
$targetlocation = getrequest_esc ("targetlocation", "locationname");
$location_orig = getrequest_esc ("location", "locationname");
$folder = getrequest_esc ("folder", "objectname");
$page = getrequest_esc ("page", "objectname");
$force = getrequest_esc ("force");
$method = getrequest_esc ("method");
$maxcount = getrequest_esc ("maxcount", "numeric");
$published_only = getrequest_esc ("published_only");
$process = getrequest_esc ("process", "objectname");
$from_page = getrequest_esc ("from_page");
$token= getrequest_esc ("token");

// get location from multiobject
if (is_string ($multiobject) && strlen ($multiobject) > 6)
{
  $multiobject_array = link_db_getobject ($multiobject);
  $location = $multiobject_array[0];
}

// initialize
$result = array();
$count = 0;
$status_progress = "";
$status_text = "";
$add_javascript = "";

// flush in order to display load screen
// do not use it for action "publish" since the output will interfere with the session_start used in the template engine
if ($force == "start" && $action != "publish")
{
  @ob_implicit_flush (true);
  while (@ob_end_flush());
}

// ==================================== stage 1 (only for cut, copy, linkcopy) ====================================

// if action includes 2 stages (cut or copy and paste)
if ($force == "start" && substr_count ($action, "->") == 1)
{
  list ($method, $action) = explode ("->", $action);

  // correct location for access permission
  $location = $location_orig;

  // get publication and category
  $site = getpublication ($location);
  $cat = getcategory ($site, $location);

  // publication management config
  if (valid_publicationname ($site)) require_once ($mgmt_config['abs_path_data']."config/".$site.".conf.php");

  // convert location
  $location = deconvertpath ($location, "file");
  $location_esc = convertpath ($site, $location, $cat);

  // ------------------------------ permission section --------------------------------

  // check authorization
  $authorized = false;

  // check access permissions
  $ownergroup = accesspermission ($site, $location, $cat);
  $setlocalpermission = setlocalpermission ($site, $ownergroup, $cat);

  if ($setlocalpermission['root'] == 1 && checktoken ($token, $user))
  {
    if (($method == "cut" || $method == "copy" || $method == "linkcopy") && ($setlocalpermission['rename'] == 1 || $setlocalpermission['folderrename'] == 1)) $authorized = true;
  }

  // --------------------------------- logic section ----------------------------------

  // execute action
  if ($authorized == true)
  {
    // empty clipboard
    setsession ('hcms_temp_clipboard', "");

    if ($multiobject != "")
    {
      $multiobject_array = link_db_getobject ($multiobject);
      $result['result'] = true;

      foreach ($multiobject_array as $objectpath)
      {
        if ($objectpath != "" && !empty ($result['result']))
        {
          $temp_site = getpublication ($objectpath);
          $temp_location = getlocation ($objectpath);
          $temp_page = getobject ($objectpath);

          if ($temp_site != "" && $temp_location != "" && $temp_page != "")
          {
            if ($method == "cut") $result = cutobject ($temp_site, $temp_location, $temp_page, $user, true);
            elseif ($method == "copy") $result = copyobject ($temp_site, $temp_location, $temp_page, $user, true);
            elseif ($method == "linkcopy") $result = copyconnectedobject ($temp_site, $temp_location, $temp_page, $user, true);
          }
        }
      }
    }
    elseif ($folder != "")
    {
      if ($method == "cut") $result = cutobject ($site, $location, $folder, $user);
      elseif ($method == "copy") $result = copyobject ($site, $location, $folder, $user);
      elseif ($method == "linkcopy") $result = copyconnectedobject ($site, $location, $folder, $user);
    }     
    elseif ($page != "")
    {
      if ($method == "cut") $result = cutobject ($site, $location, $page, $user);
      elseif ($method == "copy") copyobject ($site, $location, $page, $user);
      elseif ($method == "linkcopy") $result = copyconnectedobject ($site, $location, $page, $user);
    } 

    if (!empty ($result['message'])) $status_text = $result['message']; 

    // use target location for paste
    $location_orig = $targetlocation;
    $folder = "";
    $page = "";
  }
}

// ==================================== stage 2 ====================================

// set current location (for action "paste" the folder is not part of the location to paste)
if ($folder != "" && $action != "paste")
{
  $location = $location_orig.$folder."/";
}
else $location = $location_orig;

// get publication and category
$site = getpublication ($location);
$cat = getcategory ($site, $location);

// publication management config
if (valid_publicationname ($site)) require_once ($mgmt_config['abs_path_data']."config/".$site.".conf.php");

// convert location
$location = deconvertpath ($location, "file");
$location_esc = convertpath ($site, $location, $cat);

// ------------------------------ permission section --------------------------------

// check access permissions
$ownergroup = accesspermission ($site, $location, $cat);
$setlocalpermission = setlocalpermission ($site, $ownergroup, $cat);

if ($action != "emptybin" && (!valid_publicationname ($site) || !valid_locationname ($location))) killsession ($user);

// check session of user
checkusersession ($user, false);

// --------------------------------- logic section ----------------------------------

// check authorization of requested action
$authorized = false;

if (($setlocalpermission['root'] == 1 || $action == "emptybin") && checktoken ($token, $user))
{
  // recycle bin should be emptied
  if ($action == "emptybin")
  {
    $authorized = true;
  }
  // other actions
  else
  {
    if (($action == "delete" || $action == "deletemark" || $action == "restore") && ($setlocalpermission['folderdelete'] == 1 || $setlocalpermission['delete'] == 1)) $authorized = true;
    elseif ($action == "paste" && ($setlocalpermission['folderrename'] == 1 || $setlocalpermission['rename'] == 1)) $authorized = true;
    elseif (($action == "publish" || $action == "unpublish") && $setlocalpermission['publish'] == 1) $authorized = true;
    
    // check if folder or object exists
    if ($force == "start" && $action != "paste")
    {
      if ($location != "" && $page != "" && !is_file ($location.correctfile ($location, $page, $user))) $authorized = false;
      elseif ($location != "" && $folder != "" && !is_dir ($location)) $authorized = false;
    }
  }
}

// execute action
if ($authorized == true || $force == "stop")
{
  // start/continue process
  if ($force == "start" || $force == "continue")
  {
    $multiobject_temp = array();
    $multiobject_array = array();

    // define multiobject array as input
    if ($force == "start")
    {
      // multiobjects are not possible if action = paste
      if (is_string ($multiobject) && strlen ($multiobject) > 6) $multiobject_temp = link_db_getobject ($multiobject);

      if (is_array ($multiobject_temp) && sizeof ($multiobject_temp) > 0 && $action != "paste")
      {
        $multiobject_array = $multiobject_temp;
      }
      elseif ($site != "" && $location != "" && strlen ($location) > 6)
      {
        if ($folder != "") $multiobject_array[0] = convertpath ($site, $location.".folder", $cat); 
        else $multiobject_array[0] = convertpath ($site, $location.$page, $cat);
      }
    }

    // for publish and unpublish
    if ($published_only != "1") $published_only = "0";
    else $published_only = "1";

    // process objects:
    // method is used for action = paste -> methods: cut, copy, linkcopy
    // $source_root and $source_folder are passed as global variables to the functions and are needed if action = paste.
    if ($from_page != "recyclebin" && $action == "delete" && !empty ($mgmt_config['recyclebin'])) $action = "deletemark";

    // reset action for system user when deleting objects in the recylce bin
    if ($user == "sys" && $action == "deletemark" && getsession ("hcms_temp_sys_recyclebin") == "1") $action = "delete";

    $result = manipulateallobjects ($action, $multiobject_array, "$method", $force, $published_only, $user, $process);
    
    // if manipulation was successful
    if (!empty ($result['result'])) 
    {
      if ($maxcount == "") $maxcount = $result['maxcount'];

      if (isset ($result['count'])) $count = $result['count']; 
      else $count =  0;
      
      if (!empty ($maxcount) && !empty ($count))
      {
        $status_progress = ($maxcount - $count)." / ".$maxcount;
        $status_text = ($maxcount - $count)." / ".$maxcount." ".getescapedtext ($hcms_lang['items'][$lang]);
      }

      if (isset ($result['working'])) $working = $result['working'];
      else $working = false;
      
      if (isset ($result['method'])) $method = $result['method'];
      else $method = "";
      
      if (isset ($result['process'])) $process = $result['process'];   
      else $process = "";

      if (!empty ($result['report']) && is_array ($result['report']) && sizeof ($result['report']) > 0)
      {
        $report = "<div><b>".getescapedtext ($hcms_lang['the-following-errors-occurred'][$lang])."</b><br/>\n".implode ("<br/>", $result['report'])."</div>";
      }
     
      // define next process
      if ($working == true)
      {
        $add_javascript = "document.location='".cleandomain ($mgmt_config['url_path_cms'])."popup_status.php?force=continue&action=".url_encode($action)."&process=".url_encode($process)."&method=".url_encode($method)."&maxcount=".url_encode($maxcount)."&site=".url_encode($site)."&cat=".url_encode($cat)."&location=".url_encode($location_orig)."&folder=".url_encode($folder)."&page=".url_encode($page)."&from_page=".url_encode($from_page)."&token=".url_encode($token)."';\n";
      }
      elseif ($working == false)
      {
        $add_javascript = "document.location='".cleandomain ($mgmt_config['url_path_cms'])."popup_status.php?force=finish&action=".url_encode($action)."&process=".url_encode($process)."&method=".url_encode($method)."&maxcount=".url_encode($maxcount)."&site=".url_encode($site)."&cat=".url_encode($cat)."&location=".url_encode($location_orig)."&folder=".url_encode($folder)."&page=".url_encode($page)."&from_page=".url_encode($from_page)."&token=".url_encode($token)."';\n"; 
      }    
    }
    // if an error occured
    else 
    {
      if (!isset ($maxcount)) $maxcount = $result['maxcount'];
      
      if (isset ($result['count'])) $count = $result['count']; 
      else $count =  0;
      
      if (!empty ($result['message'])) $status_text = strip_tags ($result['message'], '<br>');    
      else $status_text = getescapedtext ($hcms_lang['error-occured'][$lang]);
      
      $working = "error";
      
      $meta_refresh = "";
    } 
  }
  // finish process
  elseif ($force == "finish")
  {
    if ($action == "paste" && $method != "cut")
    {
      // not suitable for EasyEdit
      $add_javascript = "
    // reload objectlist in objectlist frame
    if (window.top.frames['workplFrame'] && window.top.frames['workplFrame'].frames['mainFrame']) window.top.frames['workplFrame'].frames['mainFrame'].location.reload();

    // close popup in main frame
    popupclose();
    ";
    }
    elseif ($action == "paste" && $method == "cut")
    {
      // not suitable for EasyEdit
      $add_javascript = "
    // reload root node in explorer (deprecated since Version 10.0.3)
    // if (window.top.frames['navFrame'] && window.top.frames['navFrame'].document.getElementById('a_".$cat."_".$site."')) window.top.frames['navFrame'].document.getElementById('a_".$cat."_".$site."').click();

    // reload objectlist in objectlist frame
    if (window.top.frames['workplFrame'] && window.top.frames['workplFrame'].frames['mainFrame']) window.top.frames['workplFrame'].frames['mainFrame'].location.reload();

    // close popup in main frame
    popupclose();
    ";
    }  
    elseif ($action == "delete" || $action == "deletemark" || $action == "deleteunmark" || $action == "restore" || $action == "emptybin")
    {
      // for recycle bin
      if ($from_page == "recyclebin")
      {
        // not suitable for EasyEdit
        $add_javascript = "
    // reload control in objectlist frame
    if (window.top.frames['workplFrame'] && window.top.frames['workplFrame'].frames['controlFrame']) window.top.frames['workplFrame'].frames['controlFrame'].location='control_objectlist_menu.php?virtual=1&action=recyclebin';

    // reload objectlist in objectlist frame
    if (window.top.frames['workplFrame'] && window.top.frames['workplFrame'].frames['mainFrame']) window.top.frames['workplFrame'].frames['mainFrame'].location.reload();

    // close popup in main frame
    popupclose();
    ";
      }
      else
      {
        // not suitable for EasyEdit
        $add_javascript = "
    // reload control in objectlist frame
    if (window.top.frames['workplFrame'] && window.top.frames['workplFrame'].frames['controlFrame']) window.top.frames['workplFrame'].frames['controlFrame'].location='control_objectlist_menu.php?site=".url_encode($site)."&cat=".url_encode($cat)."&location=".url_encode($location_orig)."';

    // reload objectlist in objectlist frame
    if (window.top.frames['workplFrame'] && window.top.frames['workplFrame'].frames['mainFrame']) window.top.frames['workplFrame'].frames['mainFrame'].location.reload();

    // close popup in main frame
    popupclose();
    ";
      }
    }
    elseif ($action == "publish" || $action == "unpublish")
    {
      // not suitable for EasyEdit
      $add_javascript = "
    // reload object view and close popup opened by control content in content frame
    if (window.parent && window.parent.frames['objFrame'])
    {
      // reload object view
      window.parent.frames['objFrame'].location.reload();

      // close popup in content frame
      if (window.parent && typeof window.parent.closePopup == 'function') window.parent.closePopup();
    }
    // close popup when called by objectlist or control objectlist
    else if (window.top.frames['workplFrame'] && window.top.frames['workplFrame'].frames['mainFrame'])
    {
      // deprecated since version 10.1.1
      // if (window.top.frames['workplFrame'].frames['mainFrame'].location.pathname.indexOf('explorer_objectlist.php') > -1)
      // {
      //  window.top.frames['workplFrame'].frames['mainFrame'].location='explorer_objectlist.php?site=".url_encode($site)."&cat=".url_encode($cat)."&location=".url_encode($location_orig)."';
      // }

      // close popup in main frame
      if (typeof popupclose == 'function') popupclose();
    }
    ";
    }       
    else
    {
      $add_javascript = "
      // close popup in main frame
      popupclose();
    ";  
    }
  
    $status_progress = $maxcount." / ".$maxcount;
    $status_text = $maxcount." / ".$maxcount." ".getescapedtext ($hcms_lang['items'][$lang]);
  }
  // cancel process
  elseif ($force == "stop")
  {
    if (is_file ($mgmt_config['abs_path_temp'].session_id().".coll.dat"))
    {
      deletefile ($mgmt_config['abs_path_temp'], session_id().".coll.dat", true);
    }
  
    $add_javascript = "
    popupclose();
    ";
  
    $status_progress = ($maxcount - $count)." / ".$maxcount;
    $status_text = ($maxcount - $count)." / ".$maxcount." ".getescapedtext ($hcms_lang['items'][$lang]);
  }
}
// permission not granted
else
{
  $add_javascript = "
  popupclose();
  ";

  $status_text = getescapedtext ($hcms_lang['you-do-not-have-permissions-to-execute-this-function'][$lang]);
}

// define progress bar
if ($maxcount > 0 && $count >= 0)
{
  $progress = (($maxcount - $count) / $maxcount) * 100;

  if (empty ($progress)) $progress = 1;
  else $progress = ceil ($progress);
}
?>
<!DOCTYPE html>
<html>
<head>
<title>hyperCMS</title>
<meta charset="<?php echo getcodepage ($lang); ?>" />
<meta name="theme-color" content="#000000" />
<meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=1" />
<link rel="stylesheet" href="<?php echo getthemelocation(); ?>css/main.css?v=<?php echo getbuildnumber(); ?>" />
<link rel="stylesheet" href="<?php echo getthemelocation()."css/".($is_mobile ? "mobile.css" : "desktop.css"); ?>?v=<?php echo getbuildnumber(); ?>" />
<script type="text/javascript" src="javascript/click.min.js"></script>
<script>
// close popup frame
function popupclose ()
{
  // close popup in content frame
  if (window.top && typeof window.top.closePopup == 'function') window.top.closePopup();

  // get id of popup iframe
  var id = parent.document.getElementById(window.name).id;

  // close popup in main frame
  if (id.indexOf('Frame') > 0)
  {
    id = id.substring(0, id.length - 5);
    if (parent.document.getElementById(id) && typeof parent.closePopup == 'function') parent.closePopup(id);
  }
}
</script>
</head>

<body class="hcmsWorkplaceGeneric" style="overflow:hidden;">

<!-- load screen --> 
<div id="hcmsLoadScreen" class="hcmsLoadScreen" style="display:inline;"></div>

<!-- top bar -->
<?php
if ($action == "page_favorites_create") $headline = getescapedtext ($hcms_lang['add-to-favorites'][$lang]);
elseif ($action == "page_favorites_delete") $headline = getescapedtext ($hcms_lang['delete-favorite'][$lang]);
elseif ($action == "page_unlock") $headline = getescapedtext ($hcms_lang['check-in'][$lang]);
elseif ($action == "unzip") $headline = getescapedtext ($hcms_lang['uncompress-files'][$lang]);
elseif ($action == "cut") $headline = getescapedtext ($hcms_lang['cut'][$lang]);
elseif ($action == "copy") $headline = getescapedtext ($hcms_lang['copy'][$lang]);
elseif ($action == "linkcopy") $headline = getescapedtext ($hcms_lang['connected-copy'][$lang]);
elseif ($action == "paste") $headline = getescapedtext ($hcms_lang['paste'][$lang]);
elseif ($action == "delete" || $action == "deletemark") $headline = getescapedtext ($hcms_lang['delete'][$lang]);
elseif ($action == "emptybin") $headline = getescapedtext ($hcms_lang['empty-recycle-bin'][$lang]);
elseif ($action == "restore" || $action == "deleteunmark") $headline = getescapedtext ($hcms_lang['restore'][$lang]);
elseif ($action == "publish") $headline = getescapedtext ($hcms_lang['publish'][$lang]);
elseif ($action == "unpublish") $headline = getescapedtext ($hcms_lang['unpublish'][$lang]);
else $headline = "";

echo showtopbar ($headline." ".$status_progress, $lang);
?>

<div style="display:block; width:100%; height:100%; text-align:center; vertical-align:middle;">

  <!-- title -->
  <div class="hcmsHeadline" style="margin:10px;"><?php echo getescapedtext ($hcms_lang['status'][$lang]); ?></span></div>

  <!-- status -->
  <div style="margin:15px; 10px; 5px; 10px;"><?php echo $status_text; ?></div>

  <!-- location -->
  <?php if (!empty ($location)) { ?>
  <div style="margin:5px 10px;">
    <?php 
    if ($from_page == "recyclebin") echo "<img src=\"".getthemelocation()."img/recycle_bin.png\" title=\"".getescapedtext ($hcms_lang['location'][$lang])."\" class=\"hcmsIconList\" /> ".getescapedtext ($hcms_lang['recycle-bin'][$lang]);
    else echo "<img src=\"".getthemelocation()."img/folder.png\" title=\"".getescapedtext ($hcms_lang['location'][$lang])."\" class=\"hcmsIconList\" /> ".showshorttext (getlocationname ($site, $location, $cat), 220, true);
    ?>
  </div>
  <?php } ?>
  
  <?php if (!empty ($progress) && intval ($progress) >= 0) { ?>
  <!-- progress bar --> 
  <div style="display:block; width:80%; height:22px; margin:20px auto; border:1px solid #000000;">
    <div class="hcmsRowHead1" style="width:<?php echo $progress; ?>%; height:100%;"></div>
  </div>
  <?php } ?>
  
  <form name="stop" action="" method="post">
    <input type="hidden" name="force" value="stop" />
    <input type="hidden" name="action" value="<?php echo $action_esc; ?>" />
    <input type="hidden" name="cat" value="<?php echo $cat; ?>" />
    <input type="hidden" name="location" value="<?php echo $location_orig; ?>" />
    <input type="hidden" name="folder" value="<?php echo $folder; ?>" />
    <input type="hidden" name="page" value="<?php echo $page; ?>" />
    <input type="hidden" name="from_page" value="<?php echo $from_page; ?>" />
    <input type="hidden" name="count" value="<?php echo $count; ?>" />
    <input type="hidden" name="maxcount" value="<?php echo $maxcount; ?>" />
    <input type="hidden" name="process" value="<?php echo $process; ?>" />
    <input type="hidden" name="token" value="<?php echo $token; ?>" />
    <button class="hcmsButtonBlue" type="submit"><?php echo getescapedtext ($hcms_lang['cancel'][$lang]); ?></button>
  </form>

  <?php if (!empty ($report)) echo $report; ?>

</div>

<script type="text/javascript">
// load screen
if (document.getElementById('hcmsLoadScreen')) document.getElementById('hcmsLoadScreen').style.display = 'none';

// reload status window
function refreshpopup ()
{
  <?php echo $add_javascript; ?>
}

setTimeout ('refreshpopup()', <?php if (!empty ($report)) echo "5000"; else echo "1000"; ?>);
</script>

</body>
</html>
