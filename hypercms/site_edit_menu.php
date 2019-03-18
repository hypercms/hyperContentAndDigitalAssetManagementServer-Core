<?php
/*
 * This file is part of
 * hyper Content & Digital Management Server - http://www.hypercms.com
 * Copyright (c) by hyper CMS Content Management Solutions GmbH
 */

// session
define ("SESSION", "create");
// management configuration
require ("config.inc.php");
// hyperCMS API
require ("function/hypercms_api.inc.php");


// input parameters
$site = getrequest_esc ("site"); // site can be *Null*
$site_name = getrequest_esc ("site_name", "publicationname");
$preview = getrequest_esc ("preview");

// publication management config
if (valid_publicationname ($site)) require ($mgmt_config['abs_path_data']."config/".$site.".conf.php");

// ------------------------------ permission section --------------------------------

// check permissions
if (!checkrootpermission ('site')) killsession ($user);

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
<script src="javascript/main.js" type="text/javascript"></script>
</head>

<body class="hcmsWorkplaceControl">

  <div id="tabLayer" class="hcmsTabContainer" style="position:absolute; z-index:10; visibility:visible; left:0px; top:1px">
    <div id="tab1" class="hcmsTabActive">
      <a href="site_edit_form.php?site=<?php echo $site; ?>&preview=<?php echo $preview; ?>&site_name=<?php echo $site_name; ?>" target="mainFrame2" onClick="hcms_ElementbyIdStyle('tab1','hcmsTabActive'); hcms_ElementbyIdStyle('tab2','hcmsTabPassive');" title="<?php echo getescapedtext ($hcms_lang['configuration'][$lang]); ?>"><?php echo getescapedtext ($hcms_lang['configuration'][$lang]); ?></a>
    </div>
    <div id="tab2" class="hcmsTabPassive">
      <a href="site_edit_inheritance.php?site=<?php echo $site; ?>&preview=<?php echo $preview; ?>&site_name=<?php echo $site_name; ?>" target="mainFrame2" onClick="hcms_ElementbyIdStyle('tab1','hcmsTabPassive'); hcms_ElementbyIdStyle('tab2','hcmsTabActive');" title="<?php echo getescapedtext ($hcms_lang['inheritance'][$lang]); ?>"><?php echo getescapedtext ($hcms_lang['inheritance'][$lang]); ?></a>
    </div>
  </div>

</body>
</html>