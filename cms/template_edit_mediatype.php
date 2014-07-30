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
require_once ("language/template_edit_mediatype.inc.php");


// input parameters
$site = getrequest ("site", "publicationname");

// ------------------------------ permission section --------------------------------

// check permissions
if ($globalpermission[$site]['template'] != 1 || $globalpermission[$site]['tpl'] != 1 || !valid_publicationname ($site)) killsession ($user);

// check session of user
checkusersession ($user);

// --------------------------------- logic section ----------------------------------
?>
<!DOCTYPE html>
<html>
<head>
<title>hyperCMS</title>
<meta http-equiv="Content-Type" content="text/html; charset=<?php echo $lang_codepage[$lang]; ?>">
<link rel="stylesheet" href="<?php echo getthemelocation(); ?>css/main.css">
<script src="javascript/click.js" type="text/javascript"></script>
<script language="JavaScript">
<!--
function applyconstraints ()
{
  var constraint = document.forms['valid'].elements['mediatype'].value; 
  
  opener.document.forms['template_edit'].elements['constraints'].value = constraint;
  opener.format_tag('mediafile');
  self.close();
}
//-->
</script>
</head>

<body class="hcmsWorkplaceGeneric">
<div id="WorkplaceFrameLayer" class="hcmsWorkplaceFrame">

<form name="valid" onsubmit="return applyconstraints();">
  <input type="hidden" name="site" value="<?php echo $site; ?>" />
  
  <table border="0" cellspacing="2">
    <tr align="left" valign="top"> 
      <td colspan="3" nowrap="nowrap" class=hcmsHeadline><?php echo $text0[$lang]; ?></td>
    </tr>
    <tr align="left" valign="top"> 
      <td nowrap="nowrap"><?php echo $text1[$lang]; ?>:</td>
      <td nowrap="nowrap">
      <select name="mediatype">
        <option value=""><?php echo $text2[$lang]; ?></option>
        <option value="audio">audio</option>
        <option value="compressed">compressed</option>
        <option value="flash">flash</option>
        <option value="image">image</option>
        <option value="text">text</option>
        <option value="video">video</option>
      </select>
      </td>
    </tr>
    <tr align="left" valign="top"> 
      <td colspan="2" nowrap="nowrap">&nbsp;</td>
    </tr>    
    <tr align="left" valign="top">
      <td nowrap="nowrap">&nbsp;</td>
      <td nowrap="nowrap"><input name="apply" type="button" id="apply" value="<?php echo $text3[$lang]; ?>" onClick="applyconstraints();" />
      <input name="cancel" type="button" id="cancel" value="<?php echo $text4[$lang]; ?>" onClick="self.close();" /></td>
    </tr>  
  </table>
  
</form>

</div>
</body>
</html>