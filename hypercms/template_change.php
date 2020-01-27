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
$location = getrequest_esc ("location", "locationname");
$object = getrequest_esc ("page", "objectname");
$template = getrequest ("template", "objectname");
$action = getrequest ("action");
$type = getrequest ("type");
$token = getrequest ("token");

// get publication and category
$site = getpublication ($location);
$cat = getcategory ($site, $location); 

// publication management config
if (valid_publicationname ($site)) require ($mgmt_config['abs_path_data']."config/".$site.".conf.php");

// ------------------------------ permission section --------------------------------

// check access permissions
$ownergroup = accesspermission ($site, $location, $cat);
$setlocalpermission = setlocalpermission ($site, $ownergroup, $cat);  
if ($ownergroup == false || $setlocalpermission['root'] != 1 || $setlocalpermission['create'] != 1 || !valid_publicationname ($site) || !valid_locationname ($location) || !valid_objectname ($object)) killsession ($user);

// check session of user
checkusersession ($user);

// --------------------------------- logic section ----------------------------------

// convert location
$location = deconvertpath ($location, "file");
$location_esc = convertpath ($site, $location, $cat);

// extract template file name
if (strpos ($template, ".php?") > 0)
{
  $template = getattribute ($template, "template");
}

// load object file and get media file
$objectdata = loadfile ($location, $object);
$media = getfilename ($objectdata, "media");
if ($template == "") $template = getfilename ($objectdata, "template");

// check if template exists
$load_template = loadtemplate ($site, $template);

if (is_array ($load_template))
{
  if ($load_template['result'] == false)
  {
    $template = "";
  }
  else
  {
    $bufferdata = getcontent ($load_template['content'], "<application>");
    $application = $bufferdata[0];
  }
}
else
{
  $template = "";
  $application = "";
}

// change template
if ($action == "change" && $objectdata != "" && valid_objectname ($template) && ($media == "" || ($media != "" && (strpos ($template, ".meta.tpl") > 0 || strpos ($template, ".comp.tpl") > 0))) && checktoken ($token, $user))
{
  // change all objects
  if ($object == ".folder" && $type == "all")
  {
    // set new template
     settemplate ($site, $location, $object, $template, true);
  }
  // change single object
  else
  {
    // set new template
     settemplate ($site, $location, $object, $template, false);
  }
}

// get template name
if (strpos ($template, ".page.tpl") > 0)
{
  $tpl_name = substr ($template, 0, strpos ($template, ".page.tpl"));
}
elseif (strpos ($template, ".comp.tpl") > 0)
{
  $tpl_name = substr ($template, 0, strpos ($template, ".comp.tpl"));
}
elseif (strpos ($template, ".meta.tpl") > 0)
{
  $tpl_name = substr ($template, 0, strpos ($template, ".meta.tpl"));
}
else $tpl_name = "";

// create secure token
$token_new = createtoken ($user);
?>
<!DOCTYPE html>
<html>
<head>
<title>hyperCMS</title>
<meta charset="<?php echo getcodepage ($lang); ?>" />
<link rel="stylesheet" href="<?php echo getthemelocation(); ?>css/main.css" />
<script src="javascript/main.js" type="text/javascript"></script>
<script src="javascript/click.js" type="text/javascript"></script>
<script src="javascript/main.js" type="text/javascript"></script>
</head>

<body class="hcmsWorkplaceGeneric" onLoad="hcms_preloadImages('<?php echo getthemelocation(); ?>img/button_ok_over.png')">

<div class="hcmsWorkplaceFrame">
  <form name="template_change" action="" method="post">
    <input type="hidden" name="site" value="<?php echo $site; ?>" />
    <input type="hidden" name="cat" value="<?php echo $cat; ?>" />
    <input type="hidden" name="location" value="<?php echo $location_esc; ?>" />
    <input type="hidden" name="page" value="<?php echo $object; ?>" />
    <input type="hidden" name="action" value="change" />
    <input type="hidden" name="token" value="<?php echo $token_new; ?>">
    
    <table class="hcmsTableStandard">
      <tr>
        <td style="white-space:nowrap;"><?php echo getescapedtext ($hcms_lang['template-in-use'][$lang]); ?> </td>
        <td style="white-space:nowrap;" class="hcmsHeadlineTiny">&nbsp; <?php echo $tpl_name; ?></td>
      </tr>
      <tr>
        <td style="white-space:nowrap;"><?php echo getescapedtext ($hcms_lang['change-template'][$lang]); ?> </td>
        <td>
          <select name="template" onChange="hcms_jumpMenu('parent.frames[\'mainFrame2\']',this,0)">
            <?php
            if ($application == "generator") $cat = "comp";
            elseif ($media != "" || $object == ".folder") $cat = "meta";
            
            $template_array = gettemplates ($site, $cat);
            
            if (is_array ($template_array))
            {
              foreach ($template_array as $value)
              {
                if (strpos ($value, ".page.tpl") > 0) $tpl_name = substr ($value, 0, strpos ($value, ".page.tpl"));
                elseif (strpos ($value, ".comp.tpl") > 0) $tpl_name = substr ($value, 0, strpos ($value, ".comp.tpl"));
                elseif (strpos ($value, ".meta.tpl") > 0) $tpl_name = substr ($value, 0, strpos ($value, ".meta.tpl"));
                
                echo "<option value=\"template_view.php?site=".url_encode($site)."&cat=".url_encode($cat)."&template=".url_encode($value)."\"".($value == $template ? "selected=\"selected\"" : "").">".$tpl_name."</option>\n";
              }
            }
            else 
            {
              echo "<option value=\"\">".getescapedtext ($hcms_lang['none'][$lang])."</option>\n";
            }
            ?>
          </select>
          <?php if ($object == ".folder") echo "&nbsp;&nbsp;<label><input type=\"checkbox\" name=\"type\" value=\"all\" /> ".getescapedtext ($hcms_lang['all'][$lang]." ".$hcms_lang['objects'][$lang])."</label>&nbsp;&nbsp;"; ?>
          <img name="Button3" src="<?php echo getthemelocation(); ?>img/button_ok.png" class="hcmsButtonTinyBlank hcmsButtonSizeSquare" onclick="document.forms['template_change'].submit();" onMouseOut="hcms_swapImgRestore()" onMouseOver="hcms_swapImage('Button3','','<?php echo getthemelocation(); ?>img/button_ok_over.png',1)" title="OK" alt="OK" />
          </td>
      </tr>
    </table>
  </form>
</div>

<?php include_once ("include/footer.inc.php"); ?>
</body>
</html>
