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
$site = getrequest ("site", "publicationname");

// ------------------------------ permission section --------------------------------

// check permissions
if (!checkglobalpermission ($site, 'template') || !checkglobalpermission ($site, 'tpl') || !valid_publicationname ($site)) killsession ($user);

// check session of user
checkusersession ($user);

// --------------------------------- logic section ----------------------------------
?>
<!DOCTYPE html>
<html>
<head>
<title>hyperCMS</title>
<meta http-equiv="Content-Type" content="text/html; charset=<?php echo getcodepage ($lang); ?>">
<link rel="stylesheet" href="<?php echo getthemelocation(); ?>css/main.css">
<script src="javascript/click.js" type="text/javascript"></script>
<script language="JavaScript">
<!--
function applyconstraints ()
{
  var constraint = "";
  
  if (document.forms['valid'].elements['required'].checked) constraint = constraint + document.forms['valid'].elements['required'].value; 
  
  fields = document.forms[0];
 
  for (i = 0; i < fields.elements.length; i++)
  {
    if (fields.elements[i].name == "accept")
    {
      if (fields.elements[i].checked && fields.elements[i].value != "inRange")
      {
        constraint = constraint + fields.elements[i].value;
      } 
      else if (fields.elements[i].checked && fields.elements[i].value == "inRange")
      {
        constraint = constraint + fields.elements[i].value + document.forms['valid'].elements['min'].value + ":" + document.forms['valid'].elements['max'].value;
      }
    }  
  }  
  
  opener.document.forms['template_edit'].elements['constraints'].value = constraint;
  opener.format_tag('textu');
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
      <td colspan="3" nowrap="nowrap" class="hcmsHeadline"><?php echo getescapedtext ($hcms_lang['unformatted-text-constraints'][$lang]); ?></td>
    </tr>
    <tr align="left" valign="top"> 
      <td nowrap="nowrap"><?php echo getescapedtext ($hcms_lang['value'][$lang]); ?>:</td>
      <td nowrap="nowrap"><input name="required" type="checkbox" id="required" value="R" />&nbsp;<?php echo getescapedtext ($hcms_lang['required'][$lang]); ?></td>
    </tr>
    <tr align="left" valign="top"> 
      <td nowrap="nowrap"><?php echo getescapedtext ($hcms_lang['accept'][$lang]); ?>:</td>
      <td nowrap="nowrap"><input type="radio" name="accept" value="" checked="checked" />&nbsp;<?php echo getescapedtext ($hcms_lang['anything'][$lang]); ?></td>
    </tr>
    <tr align="left" valign="top"> 
      <td nowrap="nowrap">&nbsp;</td>
      <td nowrap="nowrap"><input type="radio" name="accept" value="isEmail" />&nbsp;<?php echo getescapedtext ($hcms_lang['e-mail-address'][$lang]); ?></td>
    </tr>
    <tr align="left" valign="top"> 
      <td nowrap="nowrap">&nbsp;</td>
      <td nowrap="nowrap"><input type="radio" name="accept" value="isNum" />&nbsp;<?php echo getescapedtext ($hcms_lang['number'][$lang]); ?></td>
    </tr>  
    <tr align="left" valign="top"> 
      <td nowrap="nowrap">&nbsp;</td>
      <td nowrap="nowrap"><input type="radio" name="accept" value="inRange" />&nbsp;<?php echo getescapedtext ($hcms_lang['number-between'][$lang]); ?> 
      <input name="min" type="text" id="min" size="8" />&nbsp;<?php echo getescapedtext ($hcms_lang['and'][$lang]); ?>&nbsp;<input name="max" type="text" id="max" size="8" /></td>
    </tr> 
    <tr align="left" valign="top"> 
      <td colspan="2" nowrap="nowrap">&nbsp;</td>
    </tr>    
    <tr align="left" valign="top">
      <td nowrap="nowrap">&nbsp;</td>
      <td nowrap="nowrap"><input name="apply" type="button" id="apply" value="<?php echo getescapedtext ($hcms_lang['apply-constraints'][$lang]); ?>" onClick="applyconstraints();" />
      <input name="cancel" type="button" id="cancel" value="<?php echo getescapedtext ($hcms_lang['cancel'][$lang]); ?>" onClick="self.close();" /></td>
    </tr>
  </table>
</form>

</div>
</body>
</html>
