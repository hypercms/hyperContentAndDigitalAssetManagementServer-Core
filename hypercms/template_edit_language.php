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
$site = getrequest ("site", "publicationname");

// ------------------------------ permission section --------------------------------

// check permissions
if (!checkglobalpermission ($site, 'template') || !checkglobalpermission ($site, 'tpl') || !valid_publicationname ($site)) killsession ($user);

// check session of user
checkusersession ($user);

// --------------------------------- logic section ----------------------------------

// write and close session (non-blocking other frames)
suspendsession ();
?>
<!DOCTYPE html>
<html>
<head>
<title>hyperCMS</title>
<meta charset="<?php echo getcodepage ($lang); ?>" />
<link rel="stylesheet" href="<?php echo getthemelocation(); ?>css/main.css?v=<?php echo getbuildnumber(); ?>" />
<link rel="stylesheet" href="<?php echo getthemelocation()."css/".($is_mobile ? "mobile.css" : "desktop.css"); ?>?v=<?php echo getbuildnumber(); ?>" />
<script type="text/javascript" src="javascript/click.min.js"></script>
<script type="text/javascript">

function applylanguage ()
{
  var language_sessionvar = document.forms['language'].elements['language_sessionvar'].value;
  var language_sessionvalues = document.forms['language'].elements['language_sessionvalues'].value; 

  opener.document.forms['template_edit'].elements['language_sessionvar'].value = language_sessionvar;
  opener.document.forms['template_edit'].elements['language_sessionvalues'].value = language_sessionvalues;

  opener.language();
  self.close();
}
</script>
</head>

<body class="hcmsWorkplaceGeneric">

<div id="WorkplaceFrameLayer" class="hcmsWorkplaceFrame">
  <form name="language" onsubmit="return applylanguage();">
    <input type="hidden" name="site" value="<?php echo $site; ?>" />
    
    <table class="hcmsTableStandard">
      <tr> 
        <td colspan="2" style="white-space:nowrap; vertical-align:top;" class="hcmsHeadline"><?php echo getescapedtext ($hcms_lang['assign-language-information'][$lang]); ?></td>
      </tr>
      <tr> 
        <td style="white-space:nowrap; vertical-align:top;"><?php echo getescapedtext ($hcms_lang['session-variable-name'][$lang]); ?> </td>
        <td style="white-space:nowrap; vertical-align:top;"><input name="language_sessionvar" type="text" value="" /></td>
      </tr>  
      <tr>
        <td style="white-space:nowrap; vertical-align:top;"><?php echo getescapedtext ($hcms_lang['language-values'][$lang]); ?><br />(<?php echo getescapedtext ($hcms_lang['use-as-delimiter'][$lang]); ?>)</td>
        <td style="white-space:nowrap; vertical-align:top;"><input name="language_sessionvalues" type="text" value="" /></td>
      </tr>
      <tr> 
        <td colspan="2">&nbsp;</td>
      </tr>  
      <tr>
        <td style="white-space:nowrap; vertical-align:top;">&nbsp;</td>
        <td style="white-space:nowrap; vertical-align:top;">
          <input name="language_values" type="button" id="apply" value="<?php echo getescapedtext ($hcms_lang['apply'][$lang]); ?>" onclick="applylanguage();" />
          <input name="cancel" type="button" id="cancel" value="<?php echo getescapedtext ($hcms_lang['cancel'][$lang]); ?>" onclick="self.close();" />
        </td>
      </tr>      
    </table>
  </form>
</div>

<?php includefooter(); ?>

</body>
</html>
