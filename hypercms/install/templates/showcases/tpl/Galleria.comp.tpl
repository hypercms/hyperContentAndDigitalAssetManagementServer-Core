<?xml version="1.0" encoding="utf-8" ?>
<template>
<name>Galleria</name>
<user>admin</user>
<category>comp</category>
<extension>php</extension>
<application>php</application>
<content><![CDATA[[hyperCMS:objectview name='inlineview']
[hyperCMS:tplinclude file='ServiceCollectMedia.inc.tpl']
[hyperCMS:scriptbegin
global $mgmt_config;	

// INIT
$uniqid = uniqid();
$site = "%publication%";
$abs_comp = "%abs_comp%";
$container_id = "%container_id%";
$view = "%view%";
$hash = "%objecthash%";

// picture - file extensions
$picture_extensions = ".jpg.png.gif.bmp";

// User entry - picture / folder
$picture = "[hyperCMS:mediafile id='picture' onEdit='hidden']";
$pictureTagId = "picture";

// Metadata IDs to display
$metaTitleId = "Title";
$metaDescriptionId = "Description";

// USER ENTRIES
$galleriaWidth = "[hyperCMS:textu id='galleriaWidth' onEdit='hidden']";
$galleriaHeight = "[hyperCMS:textu id='galleriaHeight' onEdit='hidden']";
$showInfo = "[hyperCMS:textc id='showInfo' onEdit='hidden']";
$filtername = "[hyperCMS:textl id='filterName' onEdit='hidden']";
$filtervalue = "[hyperCMS:textu id='filterValue' onEdit='hidden']";

// SET FILTER
if ("[hyperCMS:textl id='filterName' onEdit='hidden']" != "")
{
  $filter = array ("name" => $filtername, "value" => $filtervalue);
}
else $filter = "";

// CMS VIEW => get user entry and create iframe code
if ($view == "cmsview")
{
scriptend]
<!DOCTYPE html>
<html>
  <head>
    <title>hyperCMS.com</title>
    <meta charset='utf-8'/>
    <link rel="stylesheet" hypercms_href="[hyperCMS:scriptbegin echo getthemelocation("night"); scriptend]css/main.css" />
    <link rel="stylesheet" hypercms_href="[hyperCMS:scriptbegin echo getthemelocation("night")."css/".($is_mobile ? "mobile.css" : "desktop.css"); scriptend]" />
  </head>
  <body class="hcmsWorkplaceGeneric">
    <div class="hcmsWorkplaceFrame">
      <br />
      <table>
        <tr>
          <td>Select Picture / Folder <!-- [hyperCMS:mediafile id='picture' label='Picture (folder)' mediatype='image' onPublish='hidden'] --></td><td>[hyperCMS:scriptbegin if (strpos ("[hyperCMS:mediafile id='picture' onEdit='hidden']", "Null_media") == false) echo "Done"; scriptend]</td>
        </tr>
        <tr>
          <td>Width of stage</td><td><div style="display:inline-block; padding:2px; border:1px solid #000;">[hyperCMS:textu id='galleriaWidth' label='Width of stage' constraint='isNum' default='800' height='15' width='100']</div> px</td>
        </tr>
        <tr>
          <td>Height of stage</td><td><div style="display:inline-block; padding:2px; border:1px solid #000;">[hyperCMS:textu id='galleriaHeight' label='Height of stage' constraint='isNum' default='600' height='15' width='100']</div> px</td>
        </tr>
        <tr>
          <td>Show info</td><td><div style="display:inline-block; padding:2px; border:1px solid #000;">[hyperCMS:textc id='showInfo' value='true' default='false']</div></td>
        </tr>
        <tr>
          <td>Filter by</td><td>Field-ID:<div style="display:inline-block; padding:2px; border:1px solid #000;">[hyperCMS:textl id='filterName' label='Name' list='|Title|Description|Keywords|Copyright|Creator|License']</div> contains <div style="display:inline-block; padding:2px; border:1px solid #000;">[hyperCMS:textu id='filterValue' label='Value']</div></td>
        </tr>
        <tr>
          <td>&nbsp;</td><td><button class="hcmsButtonGreen" type="button" onClick="location.reload();" >generate code</button></td>
        </tr>
      </table>
      <p>Please do not forget to publish this page after changing the parameters!</p>
      <hr/>
[hyperCMS:scriptbegin
  // check if component is published
  $objectpath = correctfile ("%abs_location%", "%object%");
  $compinfo = getfileinfo ($site, $objectpath, "comp");

  if ($compinfo['published'])
  {
    $embed_code = "<iframe id='frame_$uniqid' src='".cleandomain ($mgmt_config['url_path_cms'])."?wl=".$hash."' frameborder='0' style='border:0; width:".$galleriaWidth."px; height:".$galleriaHeight."px; overflow:hidden;'></iframe>";
  }
  else
  {
    $embed_code = "Component is not published yet!";
  }
scriptend]
      <strong>HTML body segment</strong>
      <br />
      Mark and copy the code from the text area box (keys ctrl + A and Ctrl + C for copy or right mouse button -> copy).  Insert this code into your HTML Body of the page, where the snippet will be integrated (keys Crtl + V or right mouse button -> insert).
      <br />
      <br />
      <textarea id="codesegment" wrap="VIRTUAL" style="height:80px; width:98%">[hyperCMS:scriptbegin echo html_encode($embed_code); scriptend]</textarea>
      <br />
      <hr/>
      <strong>Online view</strong>
      <br />
      [hyperCMS:scriptbegin if ($compinfo['published']) echo "<iframe id='frame_$uniqid' src='".cleandomain ($mgmt_config['url_path_cms'])."?wl=$hash' frameborder='0' style='width:".$galleriaWidth."px; height:".$galleriaHeight."px; border:1px solid grey; background-color:#000000; overflow:hidden;'></iframe>"; scriptend]
    </div>
  </body>
</html>
[hyperCMS:scriptbegin
}
elseif ($view == "publish" || $view == "preview")
{
  //published file should be a valid html
scriptend]
<!DOCTYPE html>
<html>
  <head>
    <title>hyperCMS.com</title>
    <meta charset='utf-8'/>
    <script type="text/javascript" src="[hyperCMS:scriptbegin echo $mgmt_config['url_path_cms']; scriptend]javascript/jquery/jquery.min.js"></script>
    <script type="text/javascript" src="[hyperCMS:scriptbegin echo $mgmt_config['url_path_cms']; scriptend]javascript/jquery/jquery-migrate.min.js"></script>
    <script type="text/javascript" src="[hyperCMS:scriptbegin echo $mgmt_config['url_path_cms']; scriptend]javascript/iframe_galleria/galleria-1.2.9.min.js"></script>
    <style>
        body {
          margin: 0px;
          padding: 0px;
        }
        
        #galleria {
          width: [hyperCMS:scriptbegin echo $galleriaWidth; scriptend]px;
          height: [hyperCMS:scriptbegin echo $galleriaHeight; scriptend]px;
        }
    </style>
  </head>
  <body>

  <div id="galleria"></div>

<script type="text/javascript">
var data = [
[hyperCMS:scriptbegin
  // check if picture (folder) is choosen or if it exsists
  if (!empty ($picture) && substr_count ($picture, "Null_media.gif") != 1)
  {
    if (function_exists("collectMedia")) $mediaFiles = collectMedia ($site, $container_id, $pictureTagId, $abs_comp, $picture_extensions, $metaTitleId, $metaDescriptionId, $filter);

    if (!empty ($mediaFiles))
    {
      $i = 0;

      foreach ($mediaFiles as $media)
      {
        if ($i > 0) echo "    ,\r\n";
scriptend]
    {
        image: '[hyperCMS:scriptbegin echo $media['link']; scriptend]',
        thumb: '[hyperCMS:scriptbegin echo $media['thumb_link']; scriptend]',
        title: '[hyperCMS:scriptbegin echo (empty($media['title']) ? $media['name'] : $media['title']); scriptend]',
        description: '[hyperCMS:scriptbegin  echo (empty($media['description']) ? $media['name'] : $media['description']); scriptend]'
    }
[hyperCMS:scriptbegin

        $i++;
      }
    }
  }
scriptend]
];

  // Load the classic theme
  Galleria.loadTheme("[hyperCMS:scriptbegin echo $mgmt_config['url_path_cms']; scriptend]javascript/iframe_galleria/galleria.classic.min.js");

  // Initialize Galleria
  $('#galleria').galleria({
     lightbox: false,
     thumbnails: 'lazy',
     showInfo: [hyperCMS:scriptbegin if (!empty ($showInfo)) echo "true"; else echo "false";  scriptend],
     dataSource: data,

     // extend options              
     extend: function() {
       this.lazyLoadChunks(10);
       this.setPlaytime(1000);
    }
  });
</script>

  </body>
</html>
[hyperCMS:scriptbegin 
}
scriptend]
]]></content>
</template>