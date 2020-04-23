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
$date = getrequest_esc ("date");
$source = getrequest_esc ("source");
$type = getrequest_esc ("type");
$errorcode = getrequest_esc ("errorcode");
$description = getrequest_esc ("description");

// ------------------------------ permission section --------------------------------

// check permissions
if (!checkrootpermission ('site')) killsession ($user);

// check session of user
checkusersession ($user, false);

// --------------------------------- logic section ----------------------------------

// replace tab space with line break
$description = str_replace ("\t", "<br />", $description);
?>
<!DOCTYPE html>
<html>
<head>
<title>hyperCMS</title>
<meta charset="<?php echo getcodepage ($lang); ?>" />
<meta name="theme-color" content="#000000" />
<meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=1" />
<link rel="stylesheet" href="<?php echo getthemelocation(); ?>css/main.css" />
<script>
function popupfocus ()
{
  self.focus();
  setTimeout('popupfocus()', 10);
}

popupfocus ();
</script>
</head>

<body class="hcmsWorkplaceGeneric">

<?php
echo showtopbar ("<img src=\"".getthemelocation()."img/info.png\" class=\"hcmsButtonSizeSquare\" />&nbsp;".getescapedtext ($hcms_lang['system-events'][$lang]), $lang);
?>
<div class="hcmsWorkplaceFrame">
  <div class="hcmsHeadline" style="margin-bottom:10px;">
  <?php
  // define event type name
  // error
  if ($type == "error")
  {
    $type_name = getescapedtext ($hcms_lang['error'][$lang]);
    $icon = "log_alert.png";
  }
  // warning
  elseif ($type == "warning")
  {
    $type_name = getescapedtext ($hcms_lang['warning'][$lang]);
    $icon = "log_warning.png";
  }
  // information
  else
  {
    $type_name = getescapedtext ($hcms_lang['information'][$lang]);
    $icon = "log_info.png";
  }

  echo "<img src=\"".getthemelocation()."img/".$icon."\" class=\"hcmsIconList\"> ".$type_name;
  ?>
  </div>
  <div class="hcmsHeadline"><?php echo getescapedtext ($hcms_lang['datetime'][$lang]); ?></div>
  <div><?php echo $date; ?></div>
  <hr/>
  <div class="hcmsHeadline"><?php echo getescapedtext ($hcms_lang['source'][$lang]); ?></div>
  <div><?php echo $source; ?></div>
  <hr/>
  <div class="hcmsHeadline"><?php echo getescapedtext ($hcms_lang['code'][$lang]); ?></div>
  <div><?php echo $errorcode; ?></div>
  <hr/>
  <div class="hcmsHeadline"><?php echo getescapedtext ($hcms_lang['description'][$lang]); ?></div>
  <div>
  <?php
  // extract IP and replace by link
  if (preg_match ('/\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}/', $description, $ip_match))
  {
    $description = str_replace ($ip_match[0], "<a href=\"page_info_ip.php?ip=".$ip_match[0]."\" target=_SELF>".$ip_match[0]."</a>", $description);
  }
  
  echo $description;
  ?>
  </div>
</div>

<?php include_once ("include/footer.inc.php"); ?>
</body>
</html>