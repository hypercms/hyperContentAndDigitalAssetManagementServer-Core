<?xml version="1.0" encoding="utf-8" ?>
<template>
<name>BrandGuidelines</name>
<user>sys</user>
<category>comp</category>
<extension>php</extension>
<application>php</application>
<content><![CDATA[
<?php
// ---------------------- BRAND GUIDELINES ---------------------

// management configuration
require ("%abs_hypercms%/config.inc.php");
// hyperCMS API
require ("%abs_hypercms%/function/hypercms_api.inc.php");

// design theme
$theme = "day";

// print PDF
$html2pdf = false;

// layer size definitions
$width_top = 36;
$width_navigation = 220;

// do not display the HTML, HEAD and BODY of components
$guidelines = true;

// mobile browser
if (!isset ($is_mobile)) $is_mobile = is_mobilebrowser ();

// downloadlink
if (!empty ($html2pdf) && !empty ($mgmt_config['html2pdf']))
{
  $downloadlink = createdownloadlink ("", "", "", "", "%object_id%", "", "", "");
  if ($downloadlink != "") $downloadlink = $downloadlink."&type=pdf";
}
?>
<!DOCTYPE html>
<html>
  <head>
    <title>hyperCMS.com</title>
    <meta charset='utf-8'/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=0" />
    <link rel="stylesheet" hypercms_href="<?php echo getthemelocation($theme); ?>css/main.css" />
    <link rel="stylesheet" hypercms_href="<?php echo getthemelocation($theme)."css/".($is_mobile ? "mobile.css" : "desktop.css"); ?>" />
    <link rel="stylesheet" hypercms_href="%url_hypercms%/theme/brandguide.css" />
    <script type="text/javascript" src="%url_hypercms%/javascript/main.min.js"></script>
    <script type="text/javascript">

    function minNavFrame ()
    {
        if (document.getElementById('navLayer'))
        {
            width = 36;
            
            if (hcms_transitioneffect == true) document.getElementById('navLayer').style.transition = "0.3s";
            document.getElementById('navLayer').style.width = width + 'px';

            document.getElementById('toc').style.display = 'none';
        }
    }

    function maxNavFrame ()
    {
        if (document.getElementById('navLayer'))
        {
            width = 200;
            
            if (hcms_transitioneffect == true) document.getElementById('navLayer').style.transition = "0.3s";
            document.getElementById('navLayer').style.width = width + 'px';

            document.getElementById('toc').style.display = 'inline';
        }
    }

    function switchNav ()
    {
        if (document.getElementById('navLayer'))
        {
            if (document.getElementById('navLayer').style.width == '200px')
            {
                minNavFrame ();
            }
            else
            {
                maxNavFrame ();
            }
        }
    }
    </script>
  </head>

  <body class="hcmsWorkplaceGeneric" onload="hcms_createTOC('contents', 'toc', 2);" onresize="maxNavFrame();">

    <!-- Navigation -->
    <div id="navLayer">
      <div style="display:block;">
        <img src="<?php echo getthemelocation($theme); ?>img/button_explorer.png" id="navigation" class="hcmsButton hcmsButtonSizeSquare" onclick="switchNav();" alt="<?php echo getescapedtext ($hcms_lang['navigate'][$lang]); ?>" title="<?php echo getescapedtext ($hcms_lang['navigate'][$lang]); ?>" />
        <?php if (!empty ($downloadlink)) { ?>
        <img src="<?php echo getthemelocation($theme); ?>img/button_file_download.png" class="hcmsButton hcmsButtonSizeSquare" onclick="location.hypercms_href='<?php echo $downloadlink; ?>';" alt="<?php echo getescapedtext ($hcms_lang['download-file'][$lang]); ?>" title="<?php echo getescapedtext ($hcms_lang['download-file'][$lang]); ?>" />
        <?php } ?>
        <img src="<?php echo getthemelocation($theme); ?>img/button_print.png" class="hcmsButton hcmsButtonSizeSquare" onClick="window.print();" alt="<?php echo getescapedtext ($hcms_lang['print'][$lang]); ?>" title="<?php echo getescapedtext ($hcms_lang['print'][$lang]); ?>" />
      </div>
      <div style="display:block; height:12px; clear:both;"></div>
      <div id="toc" class="toc"></div>
    </div>

    <!-- Content -->
    <div id="workplLayer">
      <div id="contents">
        [hyperCMS:componentm id='Pages' label='Select Pages' mediatype='component']
      </div>
    </div>

  </body>
</html>
]]></content>
</template>