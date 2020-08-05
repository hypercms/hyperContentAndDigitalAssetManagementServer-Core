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
$site = getrequest_esc ("site", "publicationname");
$cat = getrequest_esc ("cat", "objectname");
$save = getrequest ("save");
$persfile = getrequest ("persfile", "objectname");
$persdata = getrequest ("persdata");
$token = getrequest ("token");

// publication management config
if (valid_publicationname ($site)) require ($mgmt_config['abs_path_data']."config/".$site.".conf.php");

// ------------------------------ permission section --------------------------------

// check permissions
if (
     !valid_objectname ($cat) || !checkglobalpermission ($site, 'pers') || 
     ($cat == "tracking" && !checkglobalpermission ($site, 'perstrack')) || 
     ($cat == "profile" && !checkglobalpermission ($site, 'persprof')) || 
     $mgmt_config[$site]['dam'] == true || !valid_publicationname ($site)
   ) killsession ($user);

// edit permission defines view mode
if (
     ($cat == "profile" && checkglobalpermission ($site, 'persprofedit')) || 
     ($cat == "tracking" && checkglobalpermission ($site, 'perstrackedit'))
   ) $preview = "no";
else $preview = "yes";

// check session of user
checkusersession ($user);

// --------------------------------- logic section ----------------------------------

$show = "";

// check if file name is an attribute of a sent string
if (strpos ($persfile, ".php") > 0)
{
  // extract file name
  $persfile = getattribute ($persfile, "persfile");
}

// define category name and extract pers name
if ($cat == "tracking")
{
  $regpro = getescapedtext ($hcms_lang['customer-tracking'][$lang]);
  if ($persfile != "") $pers_name = substr ($persfile, 0, strpos ($persfile, ".track.dat"));
}
elseif ($cat == "profile")
{
  $regpro = getescapedtext ($hcms_lang['customer-profile'][$lang]);
  if ($persfile != "") $pers_name = substr ($persfile, 0, strpos ($persfile, ".prof.dat"));
}

// check file
if (valid_objectname ($persfile))
{
  // check if file exists
  if (!is_file ($mgmt_config['abs_path_data']."customer/".$site."/".$persfile)) $persfile = "";
}
else $persfile = "";

// load ini
$publ_config = parse_ini_file ($mgmt_config['abs_path_rep']."config/".$site.".ini");
// to replace the page/comp variables in the template
$url_page = $publ_config['url_publ_page'];
$abs_page = $publ_config['abs_publ_page'];
$url_comp = $publ_config['url_publ_comp'];
$abs_comp = $publ_config['abs_publ_comp'];

// save or load pers file
if ($save == "yes" && $persfile != "" && checktoken ($token, $user))
{
  // unescape & < > 
  $persdata_save = str_replace (array ("&amp;", "&lt;", "&gt;"), array("&", "<", ">"), $persdata);
  
  // remove php-script identifier entered
  $persdata_save = str_replace (array ("<?php", "<?", "?>"), array("", "", ""), $persdata_save);  
  
  // replace the url_page variables in the template with the URL of the page root
  $persdata_save = str_replace ("%url_page%", substr ($url_page, 0, strlen ($url_page)-1), $persdata_save); 
  
  // replace the abs_page variables in the template with the abs. path to the page root
  $persdata_save = str_replace ("%abs_page%", substr ($abs_page, 0, strlen ($abs_page)-1), $persdata_save); 
  
  // replace the url_comp variables in the template with the URL of the component root
  $persdata_save = str_replace ("%url_comp%", substr ($url_comp, 0, strlen ($url_comp)-1), $persdata_save); 
  
  // replace the abs_comp variables in the template with the abs. path to the component root
  $persdata_save = str_replace ("%abs_comp%", substr ($abs_comp, 0, strlen ($abs_comp)-1), $persdata_save);
  
  // replace the publication varibales in the template with the used publication
  $persdata_save = str_replace ("%publication%", $site, $persdata_save);

  // set highest cleaning level if not provided
  if ($site != "" && isset ($mgmt_config[$site]['template_clean_level'])) $cleanlevel = $mgmt_config[$site]['template_clean_level'];
  elseif (isset ($mgmt_config['template_clean_level'])) $cleanlevel = $mgmt_config['template_clean_level'];
  else $cleanlevel = 4;
  
  // check code (php tags need to be added!)
  $persdata_check = scriptcode_clean_functions ("<?".$persdata_save."?>", $cleanlevel);
        
   // save pers file
  if ($persdata_check['result'] == true)
  {
    $savefile = savefile ($mgmt_config['abs_path_data']."customer/".$site."/", $persfile, $persdata_save);

    if ($savefile == false) $show = "<span class=\"hcmsHeadline\">".getescapedtext ($hcms_lang['the-data-could-not-be-saved'][$lang])."</span><br />\n".getescapedtext ($hcms_lang['you-do-not-have-write-permissions'][$lang]);
    else $show = "<span class=\"hcmsHeadline\">".getescapedtext ($hcms_lang['the-data-was-saved-successfully'][$lang])."</span>";
  }
  else $show = "<span class=\"hcmsHeadline\">".getescapedtext ($hcms_lang['the-data-could-not-be-saved'][$lang])."</span><br />\n".getescapedtext ($hcms_lang['there-are-insecure-functions-in-the-code'][$lang]).": <span style=\"color:red;\">".$persdata_check['found']."</span>";
}
else
{
  // load pers file
  $persdata = loadfile ($mgmt_config['abs_path_data']."customer/".$site."/", $persfile);
  
  if ($persdata != "")
  {
    // remove php-script identifier entered
    $persdata = str_replace (array("<?php", "<?", "?>"), array("", "", ""), $persdata);
    
    // escape & < >
    $persdata = str_replace (array("&", "<", ">"), array("&amp;", "&lt;", "&gt;"), $persdata);
    
    // replace the url_page variables in the template with the URL of the page root
    $persdata = str_replace (substr ($url_page, 0, strlen ($url_page)-1), "%url_page%", $persdata); 
    
    // replace the abs_page variables in the template with the abs. path to the page root
    $persdata = str_replace (substr ($abs_page, 0, strlen ($abs_page)-1), "%abs_page%", $persdata); 
    
    // replace the url_comp variables in the template with the URL of the component root
    $persdata = str_replace (substr ($url_comp, 0, strlen ($url_comp)-1), "%url_comp%", $persdata); 
    
    // replace the abs_comp variables in the template with the abs. path to the component root
    $persdata = str_replace (substr ($abs_comp, 0, strlen ($abs_comp)-1), "%abs_comp%", $persdata);
    
    // replace the publication varibales in the template with the used publication
    $persdata = str_replace ($site, "%publication%", $persdata);
      
    // trim
    $persdata = trim ($persdata);
  }   
}

// define script for form action
if ($preview == "no")
{
  $action = "pers_form.php";
}
else
{
  $action = "";
}
?>
<!DOCTYPE html>
<html>
<head>
<title>hyperCMS</title>
<meta charset="<?php echo $mgmt_config[$site]['default_codepage']; ?>" />
<link href="<?php echo getthemelocation(); ?>css/main.css" rel="stylesheet" />
<link rel="stylesheet" href="<?php echo getthemelocation()."css/".($is_mobile ? "mobile.css" : "desktop.css"); ?>" />
<script src="javascript/main.js" type="text/javascript"></script>
<script type="text/javascript">
function openHelp ()
{
  help = window.open('<?php echo $mgmt_config['url_path_cms']."pers_help.php?site=".url_encode($site)."&cat=".url_encode($cat); ?>','help','resizable=yes,scrollbars=yes,width=640,height=400');
  help.moveTo(screen.width/2-640/2, screen.height/2-400/2);
  help.focus();
}
</script>
</head>

<body class="hcmsWorkplaceGeneric">

<!-- saving --> 
<div id="savelayer" class="hcmsLoadScreen"></div>

<div id="WorkplaceFrameLayer" class="hcmsWorkplaceFrame">

<?php echo showmessage ($show, 600, 70, $lang, "position:fixed; left:15px; top:100px;"); ?>

<p class="hcmsHeadline"><?php echo $regpro; ?>: <?php echo getescapedtext ($pers_name); ?></p>
<hr />

<form id="editor" name="editor" method="post" action="<?php echo $action; ?>">
  <input type="hidden" name="site" value="<?php echo $site; ?>" />
  <input type="hidden" name="cat" value="<?php echo $cat; ?>" />
  <input type="hidden" name="persfile" value="<?php echo html_encode ($persfile); ?>" />
  <input type="hidden" name="save" value="yes" />
  <input type="hidden" name="preview" value="no" />
  <input type="hidden" name="token" value="<?php echo createtoken ($user); ?>" />
  
  <table class="hcmsTableNarrow" style="width:100%; margin:2px;">
    <tr>
      <td style="text-align:left;">
        <?php if ($preview == "no") echo "<img onclick=\"hcms_showFormLayer ('savelayer', 0); document.forms['editor'].submit();\" name=\"save\" src=\"".getthemelocation()."img/button_save.png\" class=\"hcmsButton hcmsButtonSizeSquare\" alt=\"".getescapedtext ($hcms_lang['save'][$lang])."\" title=\"".getescapedtext ($hcms_lang['save'][$lang])."\" />"; ?>
      </td>
      <td style="text-align:right; width:36px;">
        <img onClick="openHelp();" name="pic_obj_help" src="<?php echo getthemelocation(); ?>img/button_help.png" class="hcmsButton hcmsButtonSizeSquare" alt="<?php echo getescapedtext ($hcms_lang['help'][$lang]); ?>" title="<?php echo getescapedtext ($hcms_lang['help'][$lang]); ?>" />
      </td>
    </tr>
    <tr>
      <td colspan="2" style="text-align:left;">
        <textarea id="hcmsContent" name="persdata" wrap="VIRTUAL" style="width:100%; min-height:500px; -webkit-box-sizing:border-box; -moz-box-sizing:border-box; box-sizing:border-box;" <?php if ($preview == "yes") echo " disabled=\"disabled\""; ?>><?php echo $persdata; ?></textarea>
      </div>
      </td>
    </tr>
  </table>  
</form>

</div>

<?php include_once ("include/footer.inc.php"); ?>
</body>
</html>
