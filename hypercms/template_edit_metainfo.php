<?php
/*
 * This file is part of
 * hyper Content & Digital Management Server - http://www.hypercms.com
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
<meta charset="<?php echo getcodepage ($lang); ?>" />
<link rel="stylesheet" href="<?php echo getthemelocation(); ?>css/main.css" />
<script src="javascript/click.js" type="text/javascript"></script>
<script>
<!--
function applyconstraints ()
{
  var constraint = document.forms['valid'].elements['metainfo'].value; 
  
  opener.document.forms['template_edit'].elements['constraints'].value = constraint;
  opener.metainfo();
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
      <td colspan="3" nowrap="nowrap" class=hcmsHeadline><?php echo getescapedtext ($hcms_lang['assign-meta-information'][$lang]); ?></td>
    </tr>
    <tr align="left" valign="top"> 
      <td nowrap="nowrap"><?php echo getescapedtext ($hcms_lang['select-meta-information'][$lang]); ?>:</td>
      <td>
        <select name="metainfo">
          <option value="pageauthor"><?php echo getescapedtext ($hcms_lang['page-author'][$lang]); ?></option>
          <option value="pagekeywords"><?php echo getescapedtext ($hcms_lang['page-keywords'][$lang]); ?></option>
          <option value="pagedescription"><?php echo getescapedtext ($hcms_lang['page-description'][$lang]); ?></option>
          <option value="pagelanguage"><?php echo getescapedtext ($hcms_lang['language'][$lang]); ?></option>
          <option value="pagecontenttype"><?php echo getescapedtext ($hcms_lang['content-type'][$lang]); ?></option>
        </select>
      </td>
    </tr>
    <tr align="left" valign="top"> 
      <td colspan="2">&nbsp;</td>
    </tr>    
    <tr align="left" valign="top">
      <td nowrap="nowrap">&nbsp;</td>
      <td nowrap="nowrap"><input name="apply" type="button" id="apply" value="<?php echo getescapedtext ($hcms_lang['apply'][$lang]); ?>" onClick="applyconstraints();">
      <input name="cancel" type="button" id="cancel" value="<?php echo getescapedtext ($hcms_lang['cancel'][$lang]); ?>" onClick="self.close();"></td>
    </tr>  
  </table>
  
</form>

</div>
</body>
</html>
