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
// load template engine
require ("function/hypercms_tplengine.inc.php");


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
if (!checkglobalpermission ($site, 'template') || !checkglobalpermission ($site, 'tpl') || !valid_publicationname ($site)) killsession ($user);

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
    else $date_array[$i] = getescapedtext ($hcms_lang['template-component'][$lang]);
  
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

// set character set in header
if (!empty ($charset)) header ('Content-Type: text/html; charset='.$charset);
?>
<!DOCTYPE html>
<html>
<head>
<title>hyperCMS</title>
<meta charset="<?php echo $charset; ?>" />
<link rel="stylesheet" href="<?php echo getthemelocation(); ?>css/main.css" />
<link rel="stylesheet" href="<?php echo getthemelocation()."css/".($is_mobile ? "mobile.css" : "desktop.css"); ?>" />
<script type="text/javascript" src="javascript/main.min.js"></script>
<script type="text/javascript" src="javascript/click.min.js"></script>
</head>

<body class="hcmsWorkplaceGeneric">

<!-- top bar -->
<?php echo showtopbar ($hcms_lang['template'][$lang].": ".$templatename, $lang); ?>

<!-- content -->
<div class="hcmsWorkplaceFrame">
<?php
if (is_array ($date_array)) echo "<p style=\"margin:2px; padding:2px;\">".getescapedtext ($hcms_lang['comparison-of-versions'][$lang]).": ".$date_array[0]." / ".$date_array[1]."</p>\n";

if (is_array ($content_array))
{  
  $result = "";
  
  // compare old version to new version
  // extension
  $extension_diff = html_diff ($extension_array[0], $extension_array[1]);  
  $result .= "<p><div class=\"hcmsHeadline\" style=\"display:inline-block; margin:2px; padding:2px; width:160px; height:16px;\">".getescapedtext ($hcms_lang['file-extension'][$lang])."</div><div class=\"hcmsTextArea\" style=\"display:inline-block; margin:2px; width:360px; height:32px;\">".$extension_diff."</div></p>";
  
  //application
  $application_diff = html_diff ($application_array[0], $application_array[1]);    
  $result .= "<p><div class=\"hcmsHeadline\" style=\"display:inline-block; margin:2px; padding:2px; width:160px; height:16px;\">".getescapedtext ($hcms_lang['application'][$lang])."</div><div class=\"hcmsTextArea\" style=\"display:inline-block; margin:2px; width:360px; height:32px;\">".$application_diff."</div></p>";
  
  // content
  if (!empty ($content_array[0])) $content_array[0] = str_replace (array("<",">"), array("&lt;","&gt;"), $content_array[0]);
  else $content_array[0] = "";

  if (!empty ($content_array[1])) $content_array[1] = str_replace (array("<",">"), array("&lt;","&gt;"), $content_array[1]);
  else $content_array[1] = "";
  
  $content_diff = html_diff ($content_array[0], $content_array[1]);
  $content_diff = str_replace ("\n", "<br />\n", $content_diff);  
  $result .= "<p><div class=\"hcmsTextArea\" style=\"display:table; overflow:scroll; margin:2px; width:98%; min-height:32px; white-space:normal;\">".$content_diff."</div></p>";
  
  // output results
  if ($result != "") echo $result;
}
else showmessage ($hcms_lang['error-occured-no-text-based-content-could-be-found'][$lang], 600, 70, $lang, "position:fixed; left:5px; top:100px;");
?>
</div>

<?php includefooter(); ?>
</body>
</html>