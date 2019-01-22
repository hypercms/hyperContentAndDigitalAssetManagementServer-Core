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

$show = "";

// icons and default messages
if ($action == "accept")
{
  $icon = getthemelocation()."img/button_workflow_accept.png";
  $message_default = getescapedtext ($hcms_lang['please-check-the-content'][$lang]);
}
else
{
  $icon = getthemelocation()."img/button_workflow_reject.png";
  $message_default = getescapedtext ($hcms_lang['your-content-has-been-rejected'][$lang]);
}

// check authorization
if ($intention == "send" && ($action == "accept" || $action == "reject") && checktoken ($token, $user))
{
  // message
  if ($message == "") $message = $message_default;

  // workflow accept
  if ($action == "accept" && function_exists ("acceptobject")) 
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
  elseif ($action == "reject" && function_exists ("rejectobject")) 
  {
    $result = rejectobject ($site, $location, $page, $wf_id, $user, $message, $mgmt_config[$site]['sendmail'], $priority);
    
    $add_onload = $result['add_onload'];
    $show = $result['message'];  
  }
}

// security token
$token_new = createtoken ($user);
?>
<!DOCTYPE html>
<html>
<head>
<title>hyperCMS</title>
<meta charset="<?php echo getcodepage ($lang); ?>" />
<meta name="theme-color" content="#000000" />
<meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=1" />
<link rel="stylesheet" href="<?php echo getthemelocation(); ?>css/main.css" />
<script src="javascript/main.js" type="text/javascript"></script>
</head>

<body class="hcmsWorkplaceGeneric">

<?php if ($show == "") { ?>
  
  <!-- top bar -->
  <?php
  echo showtopbar ("<img src=\"".$icon."\" class=\"hcmsButtonSizeSquare\" /> ".$hcms_lang['message'][$lang], $lang);
  ?>

  <!-- content -->
  <div class="hcmsWorkplaceFrame" style="display:block; width:420px; margin:20px auto;">
    <form name="message" method="post" action="">
      <input type="hidden" name="action" value="<?php echo $action; ?>">      
      <input type="hidden" name="location" value="<?php echo $location; ?>">
      <input type="hidden" name="page" value="<?php echo $page; ?>">       
      <input type="hidden" name="wf_token" value="<?php echo $wf_token; ?>">
      <input type="hidden" name="intention" value="send">
      <input type="hidden" name="token" value="<?php echo $token_new; ?>" />
      
      <div class="hcmsFormRowContent">
        <textarea name="message" style="width:380px; height:200px;" placeholder="<?php echo $message_default; ?>"></textarea><br />
      </div>
      <div class="hcmsFormRowContent">
  	    <?php echo getescapedtext ($hcms_lang['priority'][$lang]); ?>
      </div>
      <div class="hcmsFormRowContent">
        <select name="priority">
          <option value="low"><?php echo getescapedtext ($hcms_lang['low'][$lang]); ?></option>
          <option value="medium" selected="selected"><?php echo getescapedtext ($hcms_lang['medium'][$lang]); ?></option>
          <option value="high"><?php echo getescapedtext ($hcms_lang['high'][$lang]); ?></option>
        </select> <img name="Button" src="<?php echo getthemelocation(); ?>img/button_ok.png" class="hcmsButtonTinyBlank hcmsButtonSizeSquare" onclick="document.forms['message'].submit();" onMouseOut="hcms_swapImgRestore()" onMouseOver="hcms_swapImage('Button','','<?php echo getthemelocation(); ?>img/button_ok_over.png',1)" title="OK" alt="OK">
     </div>
    </form>
  </div>
<?php } else { ?>
  <!-- top bar -->
  <?php
  echo showtopbar ("<img src=\"".getthemelocation()."img/info.png\" class=\"hcmsButtonSizeSquare\" /> ".$hcms_lang['information'][$lang], $lang);
  ?>
  <!-- content -->
  <div class="hcmsWorkplaceFrame">
    <?php echo $show; ?>
  </div>
<?php } ?>

</body>
</html>