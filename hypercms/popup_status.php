<?php
/*
 * This file is part of
 * hyper Content Management Server - http://www.hypercms.com
 * Copyright (c) by hyper CMS Content Management Solutions GmbH
 *
 * You should have received a copy of the License along with hyperCMS.
 */

// session
define ("SESSION", "create");
// management configuration
require ("config.inc.php");
// hyperCMS API
require ("function/hypercms_api.inc.php");
// hyperCMS UI
require ("function/hypercms_ui.inc.php");


// input parameters
$multiobject = getrequest ("multiobject");
$location_orig = getrequest_esc ("location", "locationname");
$folder = getrequest_esc ("folder", "objectname");
$page = getrequest_esc ("page", "objectname");
$force = getrequest_esc ("force");
$action = getrequest_esc ("action");
$method = getrequest_esc ("method");
$maxcount = getrequest_esc ("maxcount", "numeric");
$published_only = getrequest ("published_only");
$tempfile = getrequest_esc ("tempfile", "locationname");
$token = getrequest ("token");

// set current location (for action = paste the folder is not part of the location to paste)
if ($folder != "" && $action != "paste")
{
  $location = $location_orig.$folder."/";
}
else $location = $location_orig;

// get publication and category
$site = getpublication ($location);
$cat = getcategory ($site, $location);

// publication management config
if (valid_publicationname ($site)) require ($mgmt_config['abs_path_data']."config/".$site.".conf.php");

// convert location
$location = deconvertpath ($location, "file");
$location_esc = convertpath ($site, $location, $cat);

// ------------------------------ permission section --------------------------------

// check access permissions
$ownergroup = accesspermission ($site, $location, $cat);
$setlocalpermission = setlocalpermission ($site, $ownergroup, $cat);

if ($ownergroup == false || $setlocalpermission['root'] != 1 || !valid_publicationname ($site) || !valid_locationname ($location)) killsession ($user);

// check session of user
checkusersession ($user, false);

// --------------------------------- logic section ----------------------------------

$add_javascript = "";
$count = 0;

// check authorization of requested action
$authorized = false;

if ($setlocalpermission['root'] == 1 && checktoken ($token, $user))
{
  if ($action == "delete" && $setlocalpermission['folderdelete'] == 1 && $setlocalpermission['delete'] == 1) $authorized = true;
  elseif ($action == "paste" && $setlocalpermission['folderrename'] == 1 && $setlocalpermission['rename'] == 1) $authorized = true;
  elseif (($action == "publish" || $action == "unpublish") && $setlocalpermission['publish'] == 1) $authorized = true;
  
  // check if folder or object exists
  if ($force == "start" && $action != "paste")
  {
    if ($location != "" && $page != "" && !is_file ($location.correctfile ($location, $page, $user))) $authorized = false;
    elseif ($location != "" && $folder != "" && !is_file ($location.".folder")) $authorized = false;
    elseif ($location != "" && $page == "" && $folder == "") $authorized = false;
  }
}

// execute action
if ($authorized == true || $force == "stop")
{   
  // start/continue process
  if ($force == "start" || $force == "continue")
  { 
    // define multiobject array as input
    if ($force == "start")
    {
      // multiobjects are not possible if action = paste 
      if ($multiobject != "" && $action != "paste")
      {
        $multiobject_array = link_db_getobject ($multiobject);
      }
      elseif ($site != "" && $location != "")
      {
        if ($folder != "") $multiobject_array[0] = convertpath ($site, $location.".folder", $cat); 
        $multiobject_array[0] = convertpath ($site, $location.$page, $cat);
      }
    }
    else $multiobject_array = Null;

    // for publish and unpublish
    if ($published_only != "1") $published_only = "0";
    else $published_only = "1";

    // process objects:
    // method is used for action = paste -> methods: cut, copy, linkcopy
    // $source_root and $source_folder are passed as global variables to the functions and are needed
    // if action = paste.
    $result = manipulateallobjects ($action, $multiobject_array, "$method", $force, $published_only, $user, $tempfile);

    // if manipulation was successful
    if ($result['result'] != false) 
    {
      if ($maxcount == "") $maxcount = $result['maxcount'];

      if (isset ($result['count'])) $count = $result['count']; 
      else $count =  0;
      
      if (!empty ($maxcount) && !empty ($count)) $status = ($maxcount - $count)." / ".$maxcount." ".getescapedtext ($hcms_lang['items'][$lang]);
      else $status = "";
      
      if (isset ($result['working'])) $working = $result['working'];
      else $working = false;
      
      if (isset ($result['method'])) $method = $result['method'];
      else $method = "";
      
      if (isset ($result['tempfile'])) $tempfile = $result['tempfile'];   
      else $tempfile = "";
     
      // define next process
      if ($working == true)
      {
        $add_javascript = "document.location.href='".$mgmt_config['url_path_cms']."popup_status.php?force=continue&action=".url_encode($action)."&tempfile=".url_encode($tempfile)."&method=".url_encode($method)."&maxcount=".url_encode($maxcount)."&site=".url_encode($site)."&cat=".url_encode($cat)."&location=".url_encode($location_orig)."&folder=".url_encode($folder)."&page=".url_encode($page)."&token=".$token."';\n";
      }
      elseif ($working == false)
      {
        $add_javascript = "document.location.href='".$mgmt_config['url_path_cms']."popup_status.php?force=finish&action=".url_encode($action)."&tempfile=".url_encode($tempfile)."&method=".url_encode($method)."&maxcount=".url_encode($maxcount)."&site=".url_encode($site)."&cat=".url_encode($cat)."&location=".url_encode($location_orig)."&folder=".url_encode($folder)."&page=".url_encode($page)."&token=".$token."';\n"; 
      }    
    }
    // if an error occured
    else 
    {
      if (!isset ($maxcount)) $maxcount = $result['maxcount'];
      
      if (isset ($result['count'])) $count = $result['count']; 
      else $count =  0;
      
      if (!empty ($result['message'])) $status = strip_tags ($result['message']);    
      else $status = getescapedtext ($hcms_lang['error-occured'][$lang]);
      
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
      $add_javascript = "if (eval (opener.parent.frames['mainFrame'])) opener.parent.frames['mainFrame'].location.reload();  
    self.close();\n";
    }
    elseif ($action == "paste" && $method == "cut")
    {
      // not suitable for EasyEdit
      $add_javascript = "if (eval (opener.parent.frames['mainFrame'])) opener.parent.frames['mainFrame'].location.reload();  
    self.close();\n";
    }  
    elseif ($action == "delete")
    {
      // not suitable for EasyEdit
      $add_javascript = "if (eval (opener.parent.frames['mainFrame'])) opener.parent.frames['controlFrame'].location.href='control_objectlist_menu.php?site=".url_encode($site)."&cat=".url_encode($cat)."&location=".url_encode($location_orig)."';
    opener.parent.frames['mainFrame'].location.reload();  
    self.close();\n";
    }  
    elseif ($action == "publish" || $action == "unpublish")
    {
      // not suitable for EasyEdit
      $add_javascript = "
        if (opener && opener.parent)
        {
          // called from objectlist
          if (opener.parent.frames['mainFrame'] && opener.parent.frames['mainFrame'].location) opener.parent.frames['mainFrame'].location.reload(); 
          
          // called from navigator
          if (opener.parent.frames['workplFrame'])
          {
            if (opener.parent.frames['workplFrame'].frames['mainFrame'] && opener.parent.frames['workplFrame'].frames['mainFrame'].location) opener.parent.frames['workplFrame'].frames['mainFrame'].location.reload();
            // Sadly we can't just use the location to reload the controlFrame, we must redefine all variables here
            // if (opener.parent.frames['workplFrame'].frames['controlFrame'] && opener.parent.frames['workplFrame'].frames['controlFrame'].location) opener.parent.frames['workplFrame'].frames['controlFrame'].location = opener.parent.frames['workplFrame'].frames['controlFrame'].location;
          }
          
          // called from the control content
          if (opener.parent.frames['objFrame'] && opener.parent.frames['objFrame'].location) opener.parent.frames['objFrame'].location.reload();

          // should possible also reload the opener of the content edit
          if (opener.parent.opener && opener.parent.opener.top && opener.parent.opener.top.frames['workplFrame'])
          {
            // if (opener.parent.opener.top.frames['workplFrame'].frames['mainFrame'] && opener.parent.opener.top.frames['workplFrame'].frames['mainFrame'].location) opener.parent.opener.top.frames['workplFrame'].frames['mainFrame'].location.reload();
            // Sadly we can't just use the location to reload the controlFrame, we must redefine all variables here
            // if (opener.parent.opener.top.frames['workplFrame'].frames['controlFrame'] && opener.parent.opener.top.frames['workplFrame'].frames['controlFrame'].location) opener.parent.opener.top.frames['workplFrame'].frames['controlFrame'].location = opener.parent.opener.top.frames['workplFrame'].frames['controlFrame'].location;
          }
        }

        self.close();\n";
    }       
    else
    {
      $add_javascript = "self.close();\n";  
    }
  
    $status = $maxcount." / ".$maxcount." ".getescapedtext ($hcms_lang['items'][$lang]);
  }
  // cancel process
  elseif ($force == "stop")
  {
    deletefile ($mgmt_config['abs_path_temp'], session_id().".coll.dat", 1);
  
    $add_javascript = "self.close();\n";
  
    $status = ($maxcount - $count)." / ".$maxcount." ".getescapedtext ($hcms_lang['items'][$lang]);
  }
}
else
{
  $status = getescapedtext ($hcms_lang['you-do-not-have-permissions-to-execute-this-function'][$lang]);
}

// define progress bar
if ($maxcount > 0) $progress = (($maxcount - $count) / $maxcount) * 100;
else $progress = 0;

if ($progress == 0) $progress = 1;
?>
<!DOCTYPE html>
<html>
<head>
<title>hyperCMS</title>
<meta name="viewport" content="width=device-width; initial-scale=1.0; user-scalable=1;">
<meta http-equiv="Content-Type" content="text/html; charset=<?php echo getcodepage ($lang); ?>">
<link rel="stylesheet" href="<?php echo getthemelocation(); ?>css/main.css">
<script src="javascript/click.js" type="text/javascript"></script>
<script language="JavaScript">
<!--
window.innerHeight = 120;

function closepopup ()
{
  <?php echo $add_javascript; ?>
}

setTimeout('closepopup()', 1000);
//-->
</script>
</head>

<body class="hcmsWorkplaceGeneric">

<div align="center">
  <p align="center"><span class="hcmsHeadlineTiny"><?php echo getescapedtext ($hcms_lang['status'][$lang]); ?>: </span><?php echo $status; ?></p>
  
  <table width="80%" border="1" cellspacing="1" cellpadding="0" bgcolor="#CCCCCC">
    <tr> 
      <td>
        <table width="<?php echo $progress; ?>%" border="0" cellspacing="0" cellpadding="0">
          <tr> 
            <td bgcolor="#0033CC">&nbsp;</td>
          </tr>
        </table>
      </td>
    </tr>
  </table><br />
  
  <form name="stop" action="" method="post">
    <input type="hidden" name="force" value="stop" />
    <input type="hidden" name="action" value="<?php echo $action; ?>" />
    <input type="hidden" name="location" value="<?php echo $location_orig; ?>" />
    <input type="hidden" name="folder" value="<?php echo $folder; ?>" />
    <input type="hidden" name="page" value="<?php echo $page; ?>" />
    <input type="hidden" name="count" value="<?php echo $count; ?>" />
    <input type="hidden" name="maxcount" value="<?php echo $maxcount; ?>" />
    <input type="hidden" name="tempfile" value="<?php echo $tempfile; ?>" />
    <input type="hidden" name="token" value="<?php echo $token; ?>" />
    <button class="hcmsButtonBlue" type="submit" /><?php echo getescapedtext ($hcms_lang['cancel'][$lang]); ?></button>
  </form>
</div>

</body>
</html>
