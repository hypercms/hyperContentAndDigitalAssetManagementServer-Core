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
// load template engine
require ("function/hypercms_tplengine.inc.php");


// input parameters
$site = getrequest ("site", "publicationname");
$cat = getrequest ("cat", "objectname");
$location = getrequest_esc ("location", "locationname");
$page = getrequest_esc ("page", "objectname");
$compare_1 = getrequest ("compare_1", "objectname");
$compare_2 = getrequest ("compare_2", "objectname");
$token = getrequest ("token");

// publication management config
if (valid_publicationname ($site)) require ($mgmt_config['abs_path_data']."config/".$site.".conf.php");

// ------------------------------ permission section --------------------------------

// check access permissions
$ownergroup = accesspermission ($site, $location, $cat);
$setlocalpermission = setlocalpermission ($site, $ownergroup, $cat);  
if ($ownergroup == false || $setlocalpermission['root'] != 1 || !valid_publicationname ($site) || !valid_locationname ($location)) killsession ($user);

// check session of user
checkusersession ($user, false);

// --------------------------------- logic section ----------------------------------

// convert location
$location = deconvertpath ($location, "file");

// get location name
$location_name = getlocationname ($site, $location, $cat);

// get file info
$file_info = getfileinfo ($site, $location.$page, "$cat");
$pagename = $file_info['name'];

// compare given container versions
if ($compare_1 != "" && $compare_2 != "" && checktoken ($token, $user))
{
  // sort logic
  if (substr_count ($compare_1, ".v_") == 0)
  {
    $compare_array = array ($compare_2, $compare_1);    
  }
  elseif (substr_count ($compare_2, ".v_") == 0)
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
  
  foreach ($compare_array as $container)
  {
    // construct time stamp
    if (strpos ($container, ".v_") > 0)
    {
      $file_v_ext = substr (strrchr ($container, "."), 3);
      $date = substr ($file_v_ext, 0, strpos ($file_v_ext, "_"));
      $time = substr ($file_v_ext, strpos ($file_v_ext, "_") + 1);
      $time = str_replace ("-", ":", $time);
      $date_array[$i] = $date." ".$time;
    }
    else $date_array[$i] = getescapedtext ($hcms_lang['current-version'][$lang]);

    // load container
    $contentdata = loadcontainer ($container, "version", $user);

    if ($contentdata != "")
    {
      // get charset
      $charset_result = getcharset ($site, $contentdata);
      if (!empty ($charset_result['charset'])) $charset = $charset_result['charset'];
      
      // extract multimedia content if available
      $multimedianodes = getcontent ($contentdata, "<multimedia>");
      
      if (is_array ($multimedianodes) && $multimedianodes[0] != "")
      {
        $buffer = getcontent ($multimedianodes[0], "<content>");
        
        if (is_array ($buffer) && $buffer[0] != "")
        {
          $contentbot = $buffer[0];
          // remove html tags              
          $content_array['Multimedia content'][$i] = trim (strip_tags ($contentbot));
        }
      }
    
      // extract text id and content
      $textnodes = getcontent ($contentdata, "<text>");
    
      if (is_array ($textnodes))
      {
        foreach ($textnodes as $text)
        {
          $buffer = getcontent ($text, "<text_id>");
          
          if (is_array ($buffer) && $buffer[0] != "") $id = $buffer[0];
          else $id = "";
          
          $buffer = getcontent ($text, "<textuser>");
          
          if (is_array ($buffer) && $buffer[0] != "") $textuser[$id][$i] = $buffer[0];
          else $textuser[$id][$i] = "";
          
          $buffer = getcontent ($text, "<textcontent>");
          
          if ($id != "" && is_array ($buffer))
          {
            $contentbot = $buffer[0];
            
            // replace the media variables with the media root
            $contentbot = str_replace ("%media%", substr ($mgmt_config['url_path_media'], 0, strlen ($mgmt_config['url_path_media'])-1), $contentbot);
            
            // replace the object variables with the URL of the page root
            $contentbot = str_replace ("%page%/".$site, substr ($mgmt_config[$site]['url_path_page'], 0, strlen ($mgmt_config[$site]['url_path_page'])-1), $contentbot);
            
            // replace the url_comp variables with the URL of the component root
            $contentbot = str_replace ("%comp%", substr ($mgmt_config['url_path_comp'], 0, strlen ($mgmt_config['url_path_comp'])-1), $contentbot);
            
            // remove html tags              
            $content_array[$id][$i] = trim (strip_tags ($contentbot));
            
            // get charset
            if (empty ($charset))
            {
              if (strpos (strtolower ($content_array[$id][$i]), "charset") > 0) 
              {
                $contenttype = getattribute ($content_array[$id][$i], "content");
                $charset = trim (getattribute ($contenttype, "charset"));
              }
              else $charset = trim ($mgmt_config[$site]['default_codepage']);
            }
          }         
        }
      }
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
<link rel="stylesheet" href="<?php echo getthemelocation(); ?>css/main.css" />
<script src="javascript/main.js" type="text/javascript"></script>
<?php 
if (is_audio ($page)) echo showaudioplayer_head (false);
else echo showvideoplayer_head (false);
?>
</head>

<body class="hcmsWorkplaceGeneric">

<!-- top bar -->
<?php echo showtopbar ($hcms_lang['location'][$lang].": ".$location_name.$pagename, $lang); ?>

<!-- content -->
<div class="hcmsWorkplaceFrame">
<?php
if (is_array ($date_array)) echo "<p><span class=\"hcmsHeadline\">".getescapedtext ($hcms_lang['comparison-of-versions'][$lang]).": </span>".$date_array[0]." / ".$date_array[1]."</p>\n";

$showmedia_array = array();
$i = 0;

// get object info of versions and get media view
foreach ($compare_array as $container)
{
  $objectinfo = getobjectinfo ($site, $location, $page, $user, $container);
  
  // show thumbnails
  if (!empty ($objectinfo['media']))
  {
    $showmedia_array[$i] = showmedia ($site."/".$objectinfo['media'], convertchars ($objectinfo['name'], $hcms_lang_codepage[$lang], $charset), "preview_no_rendering", "media_".$i, 180);
    $i++;
  }
}

if (sizeof ($showmedia_array) > 1)
{
  echo "
  <table>
    <tr>
      <td style=\"vertical-align:top;\">".$showmedia_array[0]."</td>
      <td width=\"40\">&nbsp;</td>
      <td style=\"vertical-align:top;\">".$showmedia_array[1]."</td>
    </tr>
  </table>
  ";
}

if (is_array ($content_array))
{  
  // compare old version to new version
  foreach ($content_array as $id => $content)
  {
    if (empty ($content[0])) $content[0] = "";
    if (empty ($content[1])) $content[1] = "";

    $result_diff = html_diff ($content[0], $content[1]);
    
    if (!empty ($hcms_lang['by-user'][$lang]) && !empty ($textuser[$id][1])) $user_compare = "(".$hcms_lang['by-user'][$lang].": ".$textuser[$id][0]." &#10095; ".$textuser[$id][1].")";
    else $user_compare = "";
    
    $result[$id] = "<p><span class=\"hcmsHeadline\">".$id."</span>".$user_compare."<br /><div style=\"margin:2px; padding:2px; width:760px; border:1px solid #000000; background:#FFFFFF; min-height:18px;\">".$result_diff."</div></p>";
  }
  
  // output results
  if (is_array ($result))
  {
    sort ($result);
    reset ($result);
    
    foreach ($result as $print) echo $print;
  }
}
else showmessage ($hcms_lang['error-occured-no-text-based-content-could-be-found'][$lang], 600, 70, $lang, "position:fixed; left:5px; top:100px;");
?>
</div>

</body>
</html>