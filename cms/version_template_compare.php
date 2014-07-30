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
// load template engine
require ("function/hypercms_tplengine.inc.php");
// language file
require_once ("language/version_template.inc.php");


// input parameters
$site = getrequest_esc ("site", "publicationname");
$cat = getrequest_esc ("cat", "objectname");
$template = getrequest_esc ("template", "objectname");
$compare_1 = getrequest ("compare_1", "objectname");
$compare_2 = getrequest ("compare_2", "objectname");
$token = getrequest ("token");

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
    $cat = "inc";
  }
  elseif (strpos ($template, ".page.tpl") > 0)
  {
    $templatename = substr ($template, 0, strpos ($template, ".page.tpl"));
    $cat = "page";
  }
  elseif (strpos ($template, ".comp.tpl") > 0)
  {
    $templatename = substr ($template, 0, strpos ($template, ".comp.tpl"));
    $cat = "comp";
  }
  elseif (strpos ($template, ".meta.tpl") > 0)
  {
    $templatename = substr ($template, 0, strpos ($template, ".meta.tpl"));
    $cat = "meta";
  }
}

  // compare given container versions
if ($compare_1 != "" && $compare_2 != "" && checktoken ($token, $user))
{
  // sort logic
  if (substr_count ($compare_1, ".tpl.v_") == 0)
  {
    $compare_array = array ($compare_2, $compare_1);    
  }
  elseif (substr_count ($compare_2, ".tpl.v_") == 0)
  {
    $compare_array = array ($compare_1, $compare_2);  
  }
  else
  {
    $compare_array = array ($compare_1, $compare_2);  
    sort ($compare_array);   
  }
  
  reset ($compare_array);
  $i = 0;
  
  foreach ($compare_array as $tpl_container)
  {
    // construct time stamp
    if (strpos ($tpl_container, ".tpl.v_") > 0)
    {
      $file_v_ext = substr (strrchr ($tpl_container, "."), 3);
      $date = substr ($file_v_ext, 0, strpos ($file_v_ext, "_"));
      $time = substr ($file_v_ext, strpos ($file_v_ext, "_") + 1);
      $time = str_replace ("-", ":", $time);
      $date_array[$i] = $date." ".$time;
    }
    else $date_array[$i] = $text12[$lang];
  
    // load template file
    $templatedata = loadtemplate ($site, $tpl_container);
    
    // extract information
    $bufferarray = getcontent ($templatedata['content'], "<extension>"); 
    $extension_array[$i] = $bufferarray[0];
    $bufferarray = getcontent ($templatedata['content'], "<application>"); 
    $application_array[$i] = $bufferarray[0];  
    $bufferarray = getcontent ($templatedata['content'], "<content>"); 
    $content_array[$i] = $bufferarray[0];

    // extract text id and content
    if ($content_array[$i] != "")
    {
      // get charset
      if (strpos (strtolower ($content_array[$i]), "charset") > 0) 
      {
        $contenttype = getattribute ($templatedata['content'], "content");
        
        if ($contenttype != "") $charset = trim (getattribute ($contenttype, "charset"));
        else $charset = trim ($mgmt_config[$site]['default_codepage']);
      }
      else $charset = trim ($mgmt_config[$site]['default_codepage']);
    }
    
    $i++; 
  }
}
?>
<!DOCTYPE html>
<html>
<head>
<title>hyperCMS</title>
<meta http-equiv="Content-Type" content="text/html; charset=<?php echo $charset; ?>">
<link rel="stylesheet" href="<?php echo getthemelocation(); ?>css/main.css">
<script src="javascript/main.js" type="text/javascript"></script>
<script src="javascript/fclick.js" type="text/javascript"></script>
</head>

<body class="hcmsWorkplaceGeneric">

<!-- top bar -->
<?php echo showtopbar ($text21[$lang].": ".$templatename, $lang); ?>

<!-- content -->
<div class="hcmsWorkplaceFrame">
<?php
if (is_array ($date_array)) echo "<p style=\"margin:2px; padding:2px;\">".$text19[$lang].": ".$date_array[0]." / ".$date_array[1]."</p>\n";

if (is_array ($content_array))
{  
  $result = "";
  
  // compare old version to new version
  // extension
  $extension_diff = html_diff ($extension_array[0], $extension_array[1]);  
  $result .= "<p><div class=\"hcmsHeadline\" style=\"margin:2px; padding:2px; width:160px; height:16px; float:left;\">".$text22[$lang]."</div><div style=\"margin:2px; padding:2px; width:360px; height:16px; float:left; border:1px solid #000000; background:#FFFFFF;\">".$extension_diff."</div><br /></p>";
  
  //application
  $application_diff = html_diff ($application_array[0], $application_array[1]);    
  $result .= "<p><div class=\"hcmsHeadline\" style=\"margin:2px; padding:2px; width:160px; height:16px; float:left;\">".$text23[$lang]."</div><div style=\"margin:2px; padding:2px; width:360px; height:16px; float:left; border:1px solid #000000; background:#FFFFFF;\">".$application_diff."</div><br /></p>";
  
  // content
  $content_array[0] = str_replace (array("<",">"), array("&lt;","&gt;"), $content_array[0]);
  $content_array[1] = str_replace (array("<",">"), array("&lt;","&gt;"), $content_array[1]);
  
  $content_diff = html_diff ($content_array[0], $content_array[1]);
  $content_diff = str_replace ("\n", "<br />\n", $content_diff);  
  $result .= "<p><div style=\"margin:2px; padding:2px; width:760px; min-height:16px; border:1px solid #000000; background:#FFFFFF;\">".$content_diff."</div></p>";
  
  // output results
  if ($result != "") echo $result;
}
else showmessage ($text20[$lang], 600, 70, $lang, "position:absolute; left:5px; top:100px;");
?>
</div>

</body>
</html>