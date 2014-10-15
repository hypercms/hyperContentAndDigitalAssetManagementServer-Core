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
require_once ("language/workflow_build.inc.php");


// input parameters
$site = getrequest_esc ("site", "publicationname");
$wf_name = getrequest_esc ("wf_name", "objectname");
$usermax = getrequest_esc ("usermax", "numeric");
$scriptmax = getrequest_esc ("scriptmax", "numeric");
$item = getrequest_esc ("item", "array");
$active = getrequest ("active", "array");
$type = getrequest_esc ("type", "array");
$wfuser = getrequest_esc ("wfuser", "array");
$wfgroup = getrequest_esc ("wfgroup", "array");
$role = getrequest_esc ("role", "array");
$file = getrequest_esc ("file", "objectname");
$predecessor = getrequest_esc ("predecessor", "array");
$successor = getrequest_esc ("successor", "array");
$token = getrequest ("token");

// publication management config
if (valid_publicationname ($site)) require ($mgmt_config['abs_path_data']."config/".$site.".conf.php");

// ------------------------------ permission section --------------------------------

// check permissions
if ($globalpermission[$site]['workflow'] != 1 || $globalpermission[$site]['workflowproc'] != 1 || $globalpermission[$site]['workflowprocedit'] != 1 || !valid_publicationname ($site)) killsession ($user);

// check session of user
checkusersession ($user);

// --------------------------------- logic section ----------------------------------

// save master worklow
if (valid_publicationname ($site) && valid_objectname ($wf_name) && @is_file ($mgmt_config['abs_path_data']."workflow_master/".$site.".".$wf_name.".xml") && checktoken ($token, $user))
{
  // create items
  $items = "";
  
  // set lowest values
  $user_count = 0;
  $script_count = 0;
  
  // process workflow items
  if (is_array ($item) && sizeof ($item) > 0)
  {
    foreach ($item as $id)
    {
      if (isset ($active[$id]) && $active[$id] == 1)
      {
        if ($type[$id] == "user") $user_count++;
        elseif ($type[$id] == "script") $script_count++;
  
        $pre_xml = "";
        
        if (isset ($predecessor[$id]) && sizeof ($predecessor[$id]) >= 1)
        {    
          foreach ($predecessor[$id] as $pre)
          {  
            $pre_xml .= "<pre>".$pre."</pre>\n";
          }
        }
        
        $suc_xml = "";
        
        if (isset ($successor[$id]) && sizeof ($successor[$id]) >= 1)
        {
          foreach ($successor[$id] as $suc)
          {  
            $suc_xml .= "<suc>".$suc."</suc>\n";
          }      
        }
        
        $items .= "<item>
  <id>".$item[$id]."</id>\n".
  $pre_xml.$suc_xml.
  "<type>".$type[$id]."</type>
  <user>".$wfuser[$id]."</user>
  <group>".$wfgroup[$id]."</group>
  <role>".$role[$id]."</role>
  <script>".$file[$id]."</script>
  <passed></passed>
  <date>-</date>
  </item>\n";      
      }
    }
  }
  
  // set min. values
  if ($usermax < $user_count) $usermax = $user_count;
  if ($scriptmax < $script_count) $scriptmax = $script_count;
  
  // create workflow and insert items
  $workflow_data = "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>
  <workflow>
  <name>".$wf_name."</name>
  <usermax>".$usermax."</usermax>
  <scriptmax>".$scriptmax."</scriptmax>
  <items>\n".
  $items.
  "</items>
  </workflow>";

  $savefile = savefile ($mgmt_config['abs_path_data']."workflow_master/", $site.".".$wf_name.".xml", $workflow_data);
}
else $savefile = false;

if ($savefile == false)
{
  $show = "<p class=hcmsHeadline>".$text0[$lang]."</p>\n".$text1[$lang]."\n";
}
else
{
  $show = "<p class=hcmsHeadline>".$text2[$lang]."</p>\n".$text3[$lang]."\n";
}
?>
<!DOCTYPE html>
<html>
<head>
<title>hyperCMS</title>
<meta http-equiv="Content-Type" content="text/html; charset=<?php echo $lang_codepage[$lang]; ?>">
<?php 
if ($savefile != false) echo "<meta http-equiv=\"refresh\" content=\"0; URL=".$mgmt_config['url_path_cms']."workflow_manager.php?site=".url_encode($site)."&wf_name=".url_encode($wf_name)."\">"; 
?>
<link rel="stylesheet" href="<?php echo getthemelocation(); ?>css/main.css">
<script src="javascript/click.js" type="text/javascript"></script>
</head>

<body class="hcmsWorkplaceGeneric">

<?php
if ($show != "") echo showmessage ($show, 600, 70, $lang, "position:absolute; left:20px; top:20px;");
?>

</body>
</html>
