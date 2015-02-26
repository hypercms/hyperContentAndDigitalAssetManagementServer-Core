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


// input parameters
$multiobject = getrequest ("multiobject");
$location = getrequest_esc ("location", "locationname");
$page = getrequest_esc ("page", "objectname");
$wf_token = getrequest ("wf_token");
$action = getrequest_esc ("action");
$message = getrequest ("message");
$priority = getrequest ("priority");
$intention = getrequest ("intention");
$token = getrequest ("token");

// get publication and category
$site = getpublication ($location);
$cat = getcategory ($site, $location); 

// publication management config
if (valid_publicationname ($site)) require ($mgmt_config['abs_path_data']."config/".$site.".conf.php");

// get workflow id and release
if ($wf_token != "")
{
  $wf_string = hcms_decrypt ($wf_token);
  if ($wf_string != "" && strpos ($wf_string, ":") > 0) list ($wf_id, $wf_role) = explode (":", $wf_string);
}

// ------------------------------ permission section --------------------------------

// check permissions, user must have general root access to pages and components
if ($wf_id == "" || $wf_role < 1 || ($cat == "comp" && !checkglobalpermission ($site, 'component')) || ($cat == "page" && !checkglobalpermission ($site, 'page')) || !valid_publicationname ($site) || !valid_locationname ($location) || !valid_objectname ($page)) killsession ($user);

// check session of user
checkusersession ($user);

// --------------------------------- logic section ----------------------------------

// icons and default messages
if ($action == "accept")
{
  $icon = getthemelocation()."img/button_workflow_accept.gif";
  $message_default = $hcms_lang['please-check-the-content'][$lang];
}
else
{
  $icon = getthemelocation()."img/button_workflow_reject.gif";
  $message_default = $hcms_lang['your-content-has-been-rejected'][$lang];
}

// check authorization
if ($intention == "send" && ($action == "accept" || $action == "reject") && checktoken ($token, $user))
{
  // message
  if ($message == "") $message = $message_default;

  // workflow accept
  if ($action == "accept") 
  {
    if ($wf_role >= 3 && $wf_role <= 4)
    {
      $result = publishobject ($site, $location, $page, $user);
      
      $add_onload = $result['add_onload'];
      $show = $result['message'];      
    }
    
    $result = acceptobject ($site, $location, $page, $wf_id, $user, $message, $mgmt_config[$site]['sendmail'], $priority);
    
    $add_onload = $result['add_onload'];
    $show = $result['message'];  
  }
  // workflow reject
  elseif ($action == "reject") 
  {
    $result = rejectobject ($site, $location, $page, $wf_id, $user, $message, $mgmt_config[$site]['sendmail'], $priority);
    
    $add_onload = $result['add_onload'];
    $show = $result['message'];  
  }
  
  // javascript code
  if ($result['result'] != false)
  {
    $add_javascript = $add_onload."  
function popupclose ()
{
  self.close();
}

setTimeout('popupclose()', 1000);\n";  
  }  
}

// security token
$token_new = createtoken ($user);
?>
<!DOCTYPE html>
<html>
<head>
<title>hyperCMS</title>
<meta name="viewport" content="width=device-width; initial-scale=1.0; user-scalable=1;">
<meta http-equiv="Content-Type" content="text/html; charset=<?php echo getcodepage ($lang); ?>">
<link rel="stylesheet" href="<?php echo getthemelocation(); ?>css/main.css">
<script src="javascript/main.js" type="text/javascript"></script>
</head>

<body class="hcmsWorkplaceGeneric">

<?php if ($show == "") { ?>
<form name="message" method="post" action="">
  <input type="hidden" name="action" value="<?php echo $action; ?>">      
  <input type="hidden" name="location" value="<?php echo $location; ?>">
  <input type="hidden" name="page" value="<?php echo $page; ?>">
  <input type="hidden" name="filetype" value="<?php echo $filetype; ?>">          
  <input type="hidden" name="wf_token" value="<?php echo $wf_token; ?>">
  <input type="hidden" name="intention" value="send">
  <input type="hidden" name="token" value="<?php echo $token_new; ?>" />
  
  <table width="100%" height="100%" border=0 cellpadding="3" cellspacing="0">
    <tr class="hcmsWorkplaceControl"> 
      <td align="left" valign="top">
        <img src="<?php echo $icon; ?>" align="absmiddle"/><span class="hcmsHeadline"><?php echo $hcms_lang['message'][$lang]; ?>:</span><br />    
      </td>
    </tr>
    <tr> 
      <td align="left" valign="top">
        <textarea name="message" rows="7" style="width:380px; height:100px;"><?php echo $message_default; ?></textarea>
      </td>
    </tr>    
    <tr>  
      <td align="left" valign="top">
			  <div style="width:100px; float:left;"><?php echo $hcms_lang['priority'][$lang]; ?></div>
        <select name="priority">
          <option value="low"><?php echo $hcms_lang['low'][$lang]; ?></option>
          <option value="medium" selected="selected"><?php echo $hcms_lang['medium'][$lang]; ?></option>
          <option value="high"><?php echo $hcms_lang['high'][$lang]; ?></option>
        </select>
      </td>
    </tr>        
    <tr>  
      <td align="left" valign="top">        
        <div style="width:100px; float:left;"><?php echo $hcms_lang['send'][$lang]; ?>:</div> <img name="Button" src="<?php echo getthemelocation(); ?>img/button_OK.gif" class="hcmsButtonTinyBlank hcmsButtonSizeSquare" onclick="document.forms['message'].submit();" onMouseOut="hcms_swapImgRestore()" onMouseOver="hcms_swapImage('Button','','<?php echo getthemelocation(); ?>img/button_OK_over.gif',1)" align="absmiddle" title="OK" alt="OK">
      </td>
    </tr>
  </table>
</form>
<?php } else { ?>
<table width="100%" height="100%" border=0 cellpadding="3" cellspacing="0">
  <tr>
    <td class="hcmsWorkplaceControl" align="left" valign="top" width="20"><img src="<?php echo getthemelocation(); ?>img/info.gif" align="absmiddle" /></td>
    <td align="left" valign="middle"><?php echo $show; ?></td>
  </tr>
</table>
<?php } ?>

<script language="JavaScript">
<!--
<?php echo $add_javascript; ?>
//-->
</script>

</body>
</html>