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
// language file
require_once ("language/group_edit_script.inc.php");


// input parameters
$site = getrequest_esc ("site", "publicationname");
$cat = getrequest_esc ("cat", "objectname");
$group_name = getrequest_esc ("group_name", "objectname");
$permission = getrequest ("permission");
$access_new = getrequest ("access_new");
$sender = getrequest ("sender");
$token = getrequest ("token");

// publication management config
if (valid_publicationname ($site)) require ($mgmt_config['abs_path_data']."config/".$site.".conf.php");

// ------------------------------ permission section --------------------------------

// check group permissions
if (!checkglobalpermission ($site, 'group') || (!checkglobalpermission ($site, 'groupcreate') && !checkglobalpermission ($site, 'groupedit')) || !valid_publicationname ($site)) killsession ($user);

// check session of user
checkusersession ($user);

// --------------------------------- logic section ----------------------------------

// ------------------------------ permission settings -------------------------------
if ($sender == "settings" && checktoken ($token, $user))
{
  $result = editgroup ($site, $group_name, "", "", $permission, $user);

  if ($result['result'] == true)
  {
    if ($cat == "page") 
    {
      $target_href = "frameset_group_access.php?site=".url_encode($site)."&group_name=".url_encode($group_name)."&cat=page";
      $target_frame = "self";
    }
    elseif ($cat == "comp") 
    {
      $target_href = "frameset_group_access.php?site=".url_encode($site)."&group_name=".url_encode($group_name)."&cat=comp";
      $target_frame = "self";
    }
    elseif ($cat == "settings") 
    {
      $target_href = "group_edit_form.php?site=".url_encode($site)."&group_name=".url_encode($group_name)."&preview=no";
      $target_frame = "mainFrame";
    }
    
    $add_onload = "parent.frames['mainFrame'].location.href='".$target_href."'; ";
    $show = $result['message']."<br />\n<a href=\"group_edit_form.php?site=".url_encode($site)."&group_name=".url_encode($group_name)."&preview=no\">".$text2[$lang]."</a><br />\n";      
  }
  else
  {
    $add_onload = $result['add_onload'];
    $show = $result['message'];
  }
}  
// ----------------------------------- folder access ---------------------------------
elseif ($sender == "access" && checktoken ($token, $user))
{
  $access_array = null;
  
  // deserialize access string
  if ($access_new != "")
  {
    if (substr ($access_new, -1) == "|") $access_new = substr ($access_new, 0, strlen ($access_new)-1);
    $access_array = explode ("|", $access_new);
  }
  else $access_array[0] = "";
  
  // define variables depending on content category
  if ($cat == "page")
  {
    $result = editgroup ($site, $group_name, $access_array, "", "", $user);
  }
  elseif ($cat == "comp")
  {
    $result = editgroup ($site, $group_name, "", $access_array, "", $user);
  }

  if (is_array ($result))
  {
    $add_onload = $result['add_onload'];
    $show = $result['message'];
  }
}
?>
<!DOCTYPE html>
<html>
<head>
<title>hyperCMS</title>
<meta http-equiv="Content-Type" content="text/html; charset=<?php echo $lang_codepage[$lang]; ?>">
<link rel="stylesheet" href="<?php echo getthemelocation(); ?>css/main.css">
<script src="javascript/click.js" type="text/javascript"></script>
</head>

<body class="hcmsWorkplaceGeneric" <?php if ($add_onload != "") echo "onLoad=\"".$add_onload."\""; ?>>
<?php
echo showmessage ($show, 600, 70, $lang, "position:fixed; left:20px; top:20px;");
?>
</body>
</html>
