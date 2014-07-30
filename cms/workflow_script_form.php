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
require_once ("language/workflow_script_form.inc.php");


// input parameters
$site = getrequest_esc ("site", "publicationname");
$cat = getrequest_esc ("cat", "objectname");
$wf_file = getrequest ("wf_file", "objectname");
$wf_name = getrequest_esc ("wf_name", "objectname");
$usermax = getrequest_esc ("usermax", "numeric");
$scriptmax = getrequest_esc ("scriptmax", "numeric");
$save = getrequest ("save");
$preview = getrequest ("preview");
$wfscript_data = getrequest ("wfscript_data");
$token = getrequest ("token");

// publication management config
if (valid_publicationname ($site)) require ($mgmt_config['abs_path_data']."config/".$site.".conf.php");

// ------------------------------ permission section --------------------------------

// check permissions
if ($globalpermission[$site]['workflow'] != 1 || $globalpermission[$site]['workflowscript'] != 1 || $globalpermission[$site]['workflowscriptedit'] != 1 || !valid_publicationname ($site)) killsession ($user);

// check session of user
checkusersession ($user);

// --------------------------------- logic section ----------------------------------

$show = "";

// save or load workflow script file
// check if file name is an attribute of a sent string
if (strpos ($wf_file, ".php?") > 0)
{
  // extract file name
  $wf_file = getattribute ($wf_file, "wf_file");
}
// ceck for workflow name
elseif (valid_objectname ($wf_name) && $wf_file == "" && valid_publicationname ($site))
{
  // define workflow file
  $wf_file = $site.".".$wf_name.".xml";
}

// check file
if (valid_objectname ($wf_file))
{
  // check if file exists
  if (!is_file ($mgmt_config['abs_path_data']."workflow_master/".$wf_file)) $wf_file = "";
  
  // get workflow name
  if ($wf_file != "")
  {
    $wf_name = substr ($wf_file, strpos ($wf_file, ".")+1);
    $wf_name = substr ($wf_name, 0, strpos ($wf_name, ".inc.php"));
  }
}
else $wf_file = "";

// save workflow
if ($save == "yes" && $wf_file != "" && checktoken ($token, $user))
{
  // trim
  $wfscript_data_save = trim ($wfscript_data); 

  // unescape & < >
  $wfscript_data_save = str_replace (array("&amp;", "&lt;", "&gt;"), array("&", "<", ">"), $wfscript_data_save);
  
  // remove php-script identifier entered
  $wfscript_data_save = str_replace (array("<?php", "<?", "?>"), array("", "", ""), $wfscript_data_save);
  
  // set highest cleaning level is not provided
  if (!isset ($mgmt_config['template_clean_level'])) $mgmt_config['template_clean_level'] = 3;

  // check code (php tags need to be added!)
  $wfscript_data_check = scriptcode_clean_functions ("<?".$wfscript_data_save."?>", $mgmt_config['template_clean_level']);

   // save pers file
  if ($wfscript_data_check['result'] == true)
  {
    // append php-script identifier
    $wfscript_start = "<?php function execute_script (\$site, \$location, \$object)\n{\nglobal \$mgmt_config;\n\n// --- hyperCMS workflow script ---\n";
    $wfscript_end = "// --- hyperCMS workflow script ---\n} ?>";
           
    $wfscript_data_save = $wfscript_start.$wfscript_data_save."\n".$wfscript_end;
    
     // save pers file
    $savefile = savefile ($mgmt_config['abs_path_data']."workflow_master/", $wf_file, $wfscript_data_save);
  
    if ($savefile == false) $show = "<span class=hcmsHeadline>".$text3[$lang]."</span><br />".$text4[$lang];
    else $show = "<span class=hcmsHeadline>".$text5[$lang]."</span>";
  }
  else $show = "<span class=hcmsHeadline>".$text3[$lang]."</span><br />\n".$text6[$lang].": <span style=\"color:red;\">".$wfscript_data_check['found']."</span>";
}
// load workflow
elseif (isset ($wf_file) && file_exists ($mgmt_config['abs_path_data']."workflow_master/".$wf_file))
{
  // load pers file
  $wfscript_data_save = loadfile ($mgmt_config['abs_path_data']."workflow_master/", $wf_file);
  
  list ($wfscript_start, $wfscript_data_save, $wfscript_end) = explode ("// --- hyperCMS workflow script ---", $wfscript_data_save);

  if ($wfscript_data_save != "")
  {
    // remove php-script identifier entered
    $wfscript_data_save = str_replace (array ("<?php", "<?", "?>"), array("", "", ""), $wfscript_data_save);
    
    // escape & < >
    $wfscript_data_save = str_replace (array("&", "<", ">"), array ("&amp;", "&lt;", "&gt;"), $wfscript_data_save);
  
    // trim
    $wfscript_data = trim ($wfscript_data_save);
  }  
}

// define php script for form action
if ($preview == "no")
{
  $action = "workflow_script_form.php";
}
elseif ($preview == "yes")
{
  $action = "";
}

// create secure token
$token_new = createtoken ($user);
?>
<!DOCTYPE html>
<html>
<head>
<title>hyperCMS</title>
<meta http-equiv="Content-Type" content="text/html; charset=<?php echo $lang_codepage[$lang]; ?>">
<link rel="stylesheet" href="<?php echo getthemelocation(); ?>css/main.css">
<script src="javascript/main.js" type="text/javascript"></script>
<script language="JavaScript">
<!--
function openHelp ()
{
  help = window.open('<?php echo $mgmt_config['url_path_cms']."workflow_script_help.php?site=".url_encode($site); ?>','help','resizable=yes,scrollbars=yes,width=640,height=400');
  help.moveTo(screen.width/2-640/2, screen.height/2-400/2);
  help.focus();
}
//-->
</script>
</head>

<body class="hcmsWorkplaceGeneric">
<div id="WorkplaceFrameLayer" class="hcmsWorkplaceFrame">

<?php
echo showmessage ($show, 500, 70, $lang, "position:absolute; left:15px; top:100px;")
?>

<p class="hcmsHeadline"><?php echo $text1[$lang]; ?>: <?php echo $wf_name; ?></p>
<form name="editor" method="post" action="<?php echo $action; ?>" />
  <input type="hidden" name="site" value="<?php echo $site; ?>" />
  <input type="hidden" name="cat" value="<?php echo $cat; ?>" />
  <input type="hidden" name="wf_file" value="<?php echo html_encode ($wf_file); ?>" />
  <input type="hidden" name="save" value="yes" />
  <input type="hidden" name="preview" value="no" />
  <input type="hidden" name="token" value="<?php echo $token_new; ?>" />
  
  <table cellspacing="0" cellpadding="0" style="border:1px solid #000000; margin:2px;">
    <tr>
      <td align="left">
        <?php if ($preview == "no") echo "<img onclick=\"document.forms['editor'].submit();\" name=\"Button\" src=\"".getthemelocation()."img/button_save.gif\" class=\"hcmsButton hcmsButtonSizeSquare\" alt=\"".$text2[$lang]."\" title=\"".$text2[$lang]."\" />"; ?>
      </td>
      <td width="26" align="right">
        <a href=# onMouseOut="hcms_swapImgRestore();" onMouseOver="hcms_swapImage('pic_obj_help','','<?php echo getthemelocation(); ?>img/button_help_over.gif',1);" onClick="openHelp();">
          <img name="pic_obj_help" src="<?php echo getthemelocation(); ?>img/button_help.gif" class="hcmsButtonBlank hcmsButtonSizeSquare" alt="<?php echo $text7[$lang]; ?>" title="<?php echo $text7[$lang]; ?>" />
        </a>
      </td>
    </tr>
    <tr>
      <td colspan="2">
        <textarea name="wfscript_data" wrap="VIRTUAL" style="width:730px;" rows=20<?php if ($preview == "yes") echo " disabled=\"disabled\""; ?>><?php echo $wfscript_data; ?></textarea>
      </td>
    </tr>
  </table>  
</form>

</div>
</body>
</html>
