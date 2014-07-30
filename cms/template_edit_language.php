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
require_once ("language/template_edit_metainfo.inc.php");


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
function applylanguage ()
{
  var language_sessionvar = document.forms['language'].elements['language_sessionvar'].value;
  var language_sessionvalues = document.forms['language'].elements['language_sessionvalues'].value; 

  opener.document.forms['template_edit'].elements['language_sessionvar'].value = language_sessionvar;
  opener.document.forms['template_edit'].elements['language_sessionvalues'].value = language_sessionvalues;

  opener.language();
  self.close();
}
//-->
</script>
</head>

<body class="hcmsWorkplaceGeneric">
<div id="WorkplaceFrameLayer" class="hcmsWorkplaceFrame">

<form name="language" onsubmit="return applylanguage();">
  <input type="hidden" name="site" value="<?php echo $site; ?>" />
  
  <table border="0" cellspacing="2">
    <tr align="left" valign="top"> 
      <td colspan="2" nowrap="nowrap" class=hcmsHeadline><?php echo $text9[$lang]; ?></td>
    </tr>
    <tr align="left" valign="top"> 
      <td nowrap="nowrap"><?php echo $text10[$lang]; ?>:</td>
      <td><input name="language_sessionvar" type="text" value="" /></td>
    </tr>  
    <tr align="left" valign="top">
      <td nowrap="nowrap"><?php echo $text11[$lang]; ?>:<br /><?php echo $text12[$lang]; ?></td>
      <td><input name="language_sessionvalues" type="text" value="" /></td>
    </tr>
    <tr align="left" valign="top"> 
      <td colspan="2">&nbsp;</td>
    </tr>  
    <tr align="left" valign="top">
      <td nowrap="nowrap">&nbsp;</td>
      <td nowrap="nowrap">
        <input name="language_values" type="button" id="apply" value="<?php echo $text7[$lang]; ?>" onClick="applylanguage();" />
        <input name="cancel" type="button" id="cancel" value="<?php echo $text8[$lang]; ?>" onClick="self.close();" />
      </td>
    </tr>      
  </table>
  
</form>

</div>
</body>
</html>
