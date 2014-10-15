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
require_once ("language/template_edit.inc.php");


// input parameters
$template = getrequest_esc ("template", "objectname");
$site = getrequest ("site", "publicationname");
$cat = getrequest ("cat", "objectname");

// publication management config
if (valid_publicationname ($site)) require ($mgmt_config['abs_path_data']."config/".$site.".conf.php");

// ------------------------------ permission section --------------------------------

// check permissions
if ($globalpermission[$site]['template'] != 1 || $globalpermission[$site]['tpl'] != 1 || !valid_publicationname ($site)) killsession ($user);

// check session of user
checkusersession ($user, false);

// --------------------------------- logic section ----------------------------------

if ($template != "")
{
  // define template name
  if (strpos ($template, ".inc.tpl") > 0)
  {
    $templatename = substr ($template, 0, strpos ($template, ".inc.tpl"));
    $pagecomp = $text44[$lang];
    $cat = "inc";
  }
  elseif (strpos ($template, ".page.tpl") > 0)
  {
    $templatename = substr ($template, 0, strpos ($template, ".page.tpl"));
    $pagecomp = $text45[$lang];
    $cat = "page";
  }
  elseif (strpos ($template, ".comp.tpl") > 0)
  {
    $templatename = substr ($template, 0, strpos ($template, ".comp.tpl"));
    $pagecomp = $text46[$lang];
    $cat = "comp";
  }
  elseif (strpos ($template, ".meta.tpl") > 0)
  {
    $templatename = substr ($template, 0, strpos ($template, ".meta.tpl"));
    $pagecomp = $text64[$lang];
    $cat = "meta";
  }

  // load template file
  $templatedata = loadtemplate ($site, $template);
  
  // extract information
  $bufferarray = getcontent ($templatedata['content'], "<extension>"); 
  $extension = $bufferarray[0];
  $bufferarray = getcontent ($templatedata['content'], "<application>"); 
  $application = $bufferarray[0];  
  $bufferarray = getcontent ($templatedata['content'], "<content>"); 
  $contentfield = $bufferarray[0];
  
  // escape special characters (transform all special chararcters into their html/xml equivalents)
  $contentfield = str_replace ("&", "&amp;", $contentfield);
  $contentfield = str_replace ("<", "&lt;", $contentfield);
  $contentfield = str_replace (">", "&gt;", $contentfield);
}

// get charset
if (strpos (strtolower($contentfield), "charset") > 0) 
{
  $contenttype = getattribute ($contentfield, "content");
  $charset = trim (getattribute ($contenttype, "charset"));
}
else $charset = trim ($mgmt_config[$site]['default_codepage']);
?>
<!DOCTYPE html>
<html>
<head>
<title>hyperCMS</title>
<meta http-equiv="Content-Type" content="text/html; charset=<?php echo $charset; ?>">
<link rel="stylesheet" href="<?php echo getthemelocation(); ?>css/main.css">
<script src="javascript/main.js" type="text/javascript"></script>
</head>

<body class="hcmsWorkplaceGeneric">
<div id="WorkplaceFrameLayer" class="hcmsWorkplaceFrame">

  <table>
    <tr><td class="hcmsHeadline"><?php echo $pagecomp; ?>:</td><td><input name="extension" type="text" value="<?php echo $templatename; ?>" style="width:220px;" disabled="disabled" /></td></tr>
    <?php if ($cat == "page" || $cat == "comp") { ?>
    <tr><td class="hcmsHeadline"><?php echo $text8[$lang]; ?>:</td><td><input name="extension" type="text" value="<?php echo $extension; ?>" style="width:50" disabled="disabled" /></td></tr>
    <tr><td class="hcmsHeadline"><?php echo $text9[$lang]; ?>:</td><td>
    <select name="application" disabled>
      <option value="asp"<?php if ($application == "asp") echo "selected=\"selected\""; ?>>Active Server Pages (ASP)</option>
      <option value="xml"<?php if ($application == "xml") echo "selected=\"selected\""; ?>>Extensible Markup Language (XML) or Text</option>
      <option value="htm"<?php if ($application == "htm") echo "selected=\"selected\""; ?>>Hypertext Markup Language (HTML)</option>
      <option value="jsp"<?php if ($application == "jsp") echo "selected=\"selected\""; ?>>Java Server Pages (JSP)</option>
      <option value="php"<?php if ($application == "php") echo "selected=\"selected\""; ?>>PHP Hypertext Preprocessor (PHP)</option>
    </select>
    <br />
    </td></tr>
    <?php } ?>
    <tr>
      <td colspan=2>
        <textarea name="contentfield" style="width:720px; height:600px;" wrap="VIRTUAL"><?php echo $contentfield; ?></textarea>
      </td>
    </tr>
  </table>

</div> 
</body>
</html>
