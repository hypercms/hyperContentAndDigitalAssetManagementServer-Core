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
// extension definitions
include ("include/format_ext.inc.php");


// input parameters
$location = getrequest ("location", "locationname");
$page = getrequest_esc ("page", "objectname");
$type = getrequest ("type");
$title = getrequest ("title");

// get publication and category
$site = getpublication ($location);
$cat = getcategory ($site, $location); 

// publication management config
if (valid_publicationname ($site)) require ($mgmt_config['abs_path_data']."config/".$site.".conf.php");

// ------------------------------ permission section --------------------------------

// check access permissions
$ownergroup = accesspermission ($site, $location, $cat);
$setlocalpermission = setlocalpermission( $site, $ownergroup, $cat );
if ($ownergroup == false || $setlocalpermission['root'] != 1 || $setlocalpermission['create'] != 1 || !valid_publicationname ($site)) killsession ($user);
// check session of user
checkusersession ($user);

// --------------------------------- logic section ----------------------------------

// convert location
$location = deconvertpath ($location, "file");
$location_esc = convertpath ($site, $location, $cat);

// generate random id for div-tag
$frameid = rand_secure() + time();
?>
<!DOCTYPE html>
<html>
<head>
<title>hyperCMS</title>
<meta charset="<?php echo getcodepage ($lang);?>" />
<link rel="stylesheet" href="<?php echo getthemelocation(); ?>css/main.css" type="text/css" />
<script src="javascript/main.js" type="text/javascript"></script>
<script type="text/javascript">
function updateCodeSegment()
{
  var title = document.getElementById("title").value;
  var hide = document.getElementById("hide").checked;
  var width = document.getElementById("width").value;
  var height = document.getElementById("height").value;
  var wrapperlink = document.getElementById("wrapperlink").value;
  var download = document.getElementById("download").checked;
  var downloadlink = document.getElementById("downloadlink").value;
  var zoom = document.getElementById("zoom").checked;

  var newurl = "";
  var display_div = "";
  var display_title = "";
  var download_button = "";
  var zoom_action = "";

  if (hide == false && title != "") display_title = "<div style=\"padding:4px; background-color:rgba(0,0,0,.5); color:white;\" title=\"Title\">" + title + "</div>";
  else display_title = "";

  if (width != "") width = "width:" + width + "; ";
  if (height != "") height = "height:" + height + "; ";
  
  if (download == true) download_button = "<img src=\"<?php echo $mgmt_config['url_path_cms']."theme/night/img/button_file_download.png"; ?>\" style=\"margin:8px; padding:2px; width:32px; height:32px; cursor:pointer; background-color:rgba(0,0,0,.5);\" title=\"Download\" onclick=\"location.href='" + downloadlink + "';\" />";
  else download_button = "";

  if (zoom == true) zoom_action = "onclick=\"var n=this.firstChild.childNodes; if(this.style.width=='90%') { this.style.cssText='transition:width 0.3s; " + width + height + "'; this.firstChild.style.backgroundSize='cover'; if(n[0]) n[0].style.display='block'; if(n[1]) n[1].style.display='block'; } else { this.style.cssText='position:fixed; z-index:9999; width:90%; height:90%; top:50%; left:50%; transform:translate(-50%, -50%); transition:width 1s;'; this.firstChild.style.backgroundSize='contain'; if(n[0]) n[0].style.display='none'; if(n[1]) n[1].style.display='none'; }\"";
  else zoom_action = "";

  var code = "<div id=\"<?php echo $frameid; ?>\" style=\"" + width + height + "\" " + zoom_action + "><div style=\"width:100%; height:100%; background-image:url('" + wrapperlink + "'); background-size:cover; background-repeat:no-repeat; background-attachment:scroll; background-position:center center;\" title=\"" + title + "\">" + display_title + download_button + "</div></div>";

  document.getElementById('codesegment').innerHTML = code;
  document.getElementById('codepreview').innerHTML = code;

  <?php if (!$is_mobile) { ?>
  if (hide == false && title != "") display_title = "<div style=\"padding:4px; background-color:rgba(0,0,0,.5); color:white;\" title=\"Title\">" + title + "</div>";
  else display_title = "";

  if (download == true) download_button = "<img src=\"<?php echo $mgmt_config['url_path_cms']."theme/night/img/button_file_download.png"; ?>\" style=\"position:absolute; margin:8px; padding:2px; width:32px; height:32px; cursor:pointer; background-color:rgba(0,0,0,.5);\" title=\"Download\" onclick=\"location.href='" + downloadlink + "';\" />";
  else download_button = "";

  if (display_title != "" || download_button != "") display_div = "<div style=\"position:absolute; margin-bottom:-32px; " + width + "height:32px;\">" + display_title + download_button + "</div>";

  if (zoom == true) newurl = "&controls=true";
  else newurl = "&controls=false";

  var code360 = display_div + "<iframe id=\"<?php echo $frameid; ?>\" style=\"" + width + height + " border:0;\" src=\"<?php echo $mgmt_config['url_path_cms']; ?>media_360view.php?type=image&link=" + wrapperlink + newurl + "\" title=\"" + title + "\" frameborder=\"0\" allowFullScreen=\"true\" webkitallowfullscreen=\"true\" mozallowfullscreen=\"true\"></iframe>";

  document.getElementById('codesegment360').innerHTML = code360;
  document.getElementById('codepreview360').innerHTML = code360;
  <?php } ?>
}
</script>
<style>
#settings
{
  width: 24%;
  min-width: 280px;
}

#preview
{
  width: 72%;
  min-width: 640px;
  height: 700px; 
}

@media screen and (max-width: 1080px)
{
  #settings
  {
    width: 100%;
  }

  #preview
  {
    width: 100%;
  }
}
</style>
</head>
    
<body class="hcmsWorkplaceGeneric" onload="updateCodeSegment();">

<!-- top bar -->
<?php
echo showtopbar ($hcms_lang['media-player-configuration'][$lang], $lang, $mgmt_config['url_path_cms']."page_view.php?site=".url_encode($site)."&cat=".url_encode($cat)."&location=".url_encode($location_esc)."&page=".url_encode($page));
?>

<!-- content -->
<div class="hcmsWorkplaceFrame">
  <!-- form  -->
  <div id="settings" style="padding:0px 20px 10px 0px; float:left;">
    <div>
      <label for="title"><?php echo getescapedtext ($hcms_lang['title'][$lang]);?> </label><br/>
      <input type="text" onchange="updateCodeSegment();" id="title" value="<?php echo $title; ?>" style="width:280px;" />
    </div>
    <div style="margin-top:10px;">
      <label><input type="checkbox" onchange="updateCodeSegment();" id="hide" /> <?php echo getescapedtext ($hcms_lang['hide-content'][$lang]);?> </label>
    </div>
    <div style="margin-top:10px;">
      <label for="width"><?php echo getescapedtext ($hcms_lang['width'][$lang]);?> </label> (px, cm, mm, in, pt, pc)<br/>
      <input type="text" onchange="updateCodeSegment();" id="width" value="380px" style="width:280px;" />
    </div>
    <div style="margin-top:10px;">
      <label for="height"><?php echo getescapedtext ($hcms_lang['height'][$lang]);?> </label> (px, cm, mm, in, pt, pc)<br/>
      <input type="text" onchange="updateCodeSegment();" id="height" value="380px" style="width:280px;" />
    </div>
    <?php
    // image options
    if (!empty ($mgmt_imageoptions) && is_array ($mgmt_imageoptions))
    {
      echo "
    <div style=\"margin-top:10px;\">
      <label for=\"wrapperlink\">".getescapedtext ($hcms_lang['image-type'][$lang])."</label><br/>
      <select id=\"wrapperlink\" onchange=\"updateCodeSegment();\" style=\"width:280px;\">";

      echo "
      <option value=\"".createwrapperlink ($site, $location, $page, "comp")."\">".getescapedtext ($hcms_lang['original'][$lang])."</option>";

      foreach ($mgmt_imageoptions as $ext => $config_array) 
      {
        if (is_array ($config_array)) 
        {
          $ext_array = explode (".", trim ($ext, "."));
          $image_type = $ext_array[0];

          foreach ($config_array as $config_name => $config_parameter) 
          {
            if ($config_name != "thumbnail" && $config_name != "original") 
            {
              echo "
          <option value=\"".createwrapperlink ($site, $location, $page, "comp", "", "", $image_type, $config_name)."\">".strtoupper($image_type)." ".$config_name."</option>";
            }
          }
        }
      }

      echo "
      </select>
    </div>";
    }
    ?>
    <div style="margin-top:10px; margin-bottom:10px;">
      <label><input type="checkbox" onchange="updateCodeSegment(); if (this.checked == true) document.getElementById('downloadlinkLayer').style.display='inline'; else document.getElementById('downloadlinkLayer').style.display='none';" id="download" /> <?php echo getescapedtext ($hcms_lang['download-file'][$lang]);?> </label>
    </div>
    <?php
    // image download options
    if (!empty ($mgmt_imageoptions) && is_array ($mgmt_imageoptions))
    {
      echo "
    <div id=\"downloadlinkLayer\" style=\"display:none;\">
      <label for=\"downloadlink\">".getescapedtext ($hcms_lang['download-formats'][$lang])."</label><br/>
      <select id=\"downloadlink\" onchange=\"updateCodeSegment();\" style=\"width:280px;\">";

      echo "
      <option value=\"".createdownloadlink ($site, $location, $page, "comp")."\">".getescapedtext ($hcms_lang['original'][$lang])."</option>";

      foreach ($mgmt_imageoptions as $ext => $config_array) 
      {
        if (is_array ($config_array)) 
        {
          $ext_array = explode (".", trim ($ext, "."));
          $image_type = $ext_array[0];

          foreach ($config_array as $config_name => $config_parameter) 
          {
            if ($config_name != "thumbnail" && $config_name != "original") 
            {
              echo "
          <option value=\"".createdownloadlink ($site, $location, $page, "comp", "", "", $image_type, $config_name)."\">".strtoupper($image_type)." ".$config_name."</option>";
            }
          }
        }
      }

      echo "
      </select>
    </div>";
    }
    ?>
    <div style="margin-top:10px; margin-bottom:10px;">
      <label><input type="checkbox" onchange="updateCodeSegment();" id="zoom" /> <?php echo getescapedtext ($hcms_lang['enable-fullscreen'][$lang]);?> </label>
    </div>
    <hr />

    <strong><?php echo getescapedtext ($hcms_lang['html-body-segment'][$lang]); ?></strong> (<?php echo getescapedtext ($hcms_lang['character-set'][$lang])." ".strtoupper (getcodepage ($lang)); ?>)<br />
    <?php echo getescapedtext ($hcms_lang['mark-and-copy-the-code-from-the-text-area-box-keys-ctrl-a-and-ctrl-c-for-copy-or-right-mouse-button-copy'][$lang]); ?><br /><br />
    <?php echo getescapedtext ($hcms_lang['image'][$lang]); ?><br/>
    <textarea id="codesegment" style="height:140px; width:98%" wrap="VIRTUAL"></textarea><br/><br/>
    <?php if (!$is_mobile) { ?>
    <?php echo getescapedtext ("360 ".$hcms_lang['image'][$lang]); ?><br/>
    <textarea id="codesegment360" style="height:140px; width:98%" wrap="VIRTUAL"></textarea><br/>
    <?php } ?>
    <hr />
  </div>

  <!-- preview -->
  <div id="preview" style="float:left; scrolling:auto;">
    <span class="hcmsHeadline"><?php echo getescapedtext ($hcms_lang['preview'][$lang]); ?></span><br/><br/>
    <?php echo getescapedtext ($hcms_lang['image'][$lang]); ?><br/>
    <div id="codepreview" style="margin-bottom:10px;"></div><br/>
    <?php if (!$is_mobile) { ?>
    <?php echo getescapedtext ("360 ".$hcms_lang['image'][$lang]); ?><br/>
    <div id="codepreview360"></div>
    <?php } ?>
  </div>

</div>

<?php include_once ("include/footer.inc.php"); ?>
</body>
</html>