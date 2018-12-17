<?xml version="1.0" encoding="utf-8" ?>
<template>
<name>360view</name>
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
$correctFile = correctfile("%abs_location%", "%object%");
// picture - file extensions
$picture_extensions = ".jpg.png.gif.bmp";
// User entry - picture / folder
$picture = "[hyperCMS:mediafile id='picture' onEdit='hidden']";
$pictureTagId = "picture";

//USER ENTRIES
$stageWidth = "[hyperCMS:textu id='stageWidth' onEdit='hidden' default='400']";
$stageHeight = "[hyperCMS:textu id='stageHeight' onEdit='hidden' default='274']";

// CMS VIEW => get user entry and create iframe code
if($view == "cmsview")
{
scriptend]
<!DOCTYPE html>
<html>
  <head>
    <title>hyperCMS.com</title>
    <meta charset='utf-8'/>
    <link rel="stylesheet" hypercms_href="[hyperCMS:scriptbegin echo getthemelocation(); scriptend]css/main.css" />
  </head>
  <body class="hcmsWorkplaceGeneric">
    <div class="hcmsWorkplaceFrame">
    <br />
    <table>
        <tr>
          <td>Select Picture / Folder <!-- [hyperCMS:mediafile id='picture' label='Picture (folder)' mediatype='image' onPublish='hidden'] --></td><td>[hyperCMS:scriptbegin if (strpos ("[hyperCMS:mediafile id='picture' onEdit='hidden']", "Null_media") == false) echo "Done"; scriptend]</td>
        </tr>
        <tr>
          <td>Width of stage </td><td><div style="display:inline-block; padding:2px; border:1px solid #000;">[hyperCMS:textu id='stageWidth' label='Width of stage' default='800' constraint='isNum' height='15' width='100']</div> px</td>
        </tr>
        <tr>
          <td>Height of stage </td><td><div style="display:inline-block; padding:2px; border:1px solid #000;">[hyperCMS:textu id='stageHeight' label='Height of stage' default='600' constraint='isNum' height='15' width='100']</div> px</td>
        </tr>
        <tr>
          <td>&nbsp;</td><td><button class="hcmsButtonGreen" type="button" onClick="location.reload();" >generate code</button></td>
        </tr>
      </table>
      <p>
          Please do not forget to publish this page after changing the parameters!
      </p>
      <hr/>
[hyperCMS:scriptbegin
  // check if component is published
  $objectpath = correctfile ("%abs_location%", "%object%");
  $compinfo = getfileinfo ($site, $objectpath, "comp");

  if ($compinfo['published'])
  {
    $embed_code = "<iframe id='frame_$uniqid' src='".$mgmt_config['url_path_cms']."?wl=".$hash."' scrolling='no' frameborder=0 border=0 width='".$stageWidth."' height='".$stageHeight."'></iframe>";
  }
  else
  {
    $embed_code = "Component is not published yet!";
  }
scriptend]
      <strong>HTML body segment</strong>
      <br />
      Mark and copy the code from the text area box (keys ctrl + A and Ctrl + C for copy or right mouse button -> copy).  insert this code into your HTML Body of the page, where the image-zoom will be integrated (keys Crtl + V or right mouse button -> insert).
      <br />
      <br />
      <textarea id="codesegment" wrap="VIRTUAL" style="height:80px; width:98%">[hyperCMS:scriptbegin echo html_encode($embed_code); scriptend]</textarea>
      <br />
      <hr/>
      <strong>Online view</strong>
      <br />
      [hyperCMS:scriptbegin if ($compinfo['published']) echo "<iframe id='frame_$uniqid' src='".$mgmt_config['url_path_cms']."?wl=$hash' scrolling='no' frameborder=0 border=0 width='".$stageWidth."' height='".$stageHeight."' style='border:1px solid grey;'></iframe>"; scriptend]
    </div>
    </div>
  </body>
</html>
[hyperCMS:scriptbegin
}
else
{
  if ($view == "publish")
  {
    //published file should be a valid html
scriptend]
<!DOCTYPE html>
<html>
  <head>
    <title>hyperCMS.com</title>
    <meta charset='utf-8' />
[hyperCMS:scriptbegin
  }
scriptend]
    <script type="text/javascript" src="[hyperCMS:scriptbegin echo $mgmt_config['url_path_cms']; scriptend]javascript/jquery/jquery-1.12.4.min.js"></script>
    <script type="text/javascript" src="[hyperCMS:scriptbegin echo $mgmt_config['url_path_cms']; scriptend]javascript/iframe_360view/jquery.reel-min.js"></script>
    <style>
        body {
          margin: 0px;
          padding: 0px;
        }
        
        #image_[hyperCMS:scriptbegin echo $uniqid; scriptend]-reel {
          cursor: url([hyperCMS:scriptbegin echo $mgmt_config['url_path_cms']; scriptend]javascript/iframe_360view/icon.png), move !important;
        }
 
        .reel-panning, .reel-panning * {
          cursor: url([hyperCMS:scriptbegin echo $mgmt_config['url_path_cms']; scriptend]javascript/iframe_360view/icon.png), move !important;
        }
    </style>
[hyperCMS:scriptbegin
  if ($view == "publish")
  {
scriptend]
  </head>
  <body>
[hyperCMS:scriptbegin
  }

  // check if picture (folder) is choosen or if it exsists
  if (substr_count ($picture, "Null_media.gif") == 1)
  {
scriptend]
    <p>No media file selected!</p>
[hyperCMS:scriptbegin
  }
  else
  {
    $mediaFiles = collectMedia ($site, $container_id, $pictureTagId, $abs_comp, $picture_extensions);

    if (empty ($mediaFiles))
    {
scriptend]
 <p>Folder could not be read!</p>
[hyperCMS:scriptbegin		
    }
    else
    {
      //build image url for simple implode
      $imageURLs = array();
      
      foreach ($mediaFiles as $media)
      { 
        copy ($media['abspath'].$media['filename'], $mgmt_config['abs_path_view'].$media['filename']);
        $imageURLs[] = $mgmt_config['url_path_view'].$media['filename'];
      }
scriptend]
<img id="image_[hyperCMS:scriptbegin echo $uniqid; scriptend]" src="[hyperCMS:scriptbegin echo $imageURLs[0]; scriptend]" width="[hyperCMS:scriptbegin echo $stageWidth; scriptend]" height="[hyperCMS:scriptbegin echo $stageHeight; scriptend]"/>
<script>
  $(function(){ // when DOM ready
      $("#image_[hyperCMS:scriptbegin echo $uniqid; scriptend]").reel({
        cw: true,
        frames: [hyperCMS:scriptbegin echo count($imageURLs); scriptend],
        images: ["[hyperCMS:scriptbegin echo implode('", "', $imageURLs); scriptend]"],
        opening: 2,
        entry: 1
      });
  });
</script>
[hyperCMS:scriptbegin	
    }
  }		
  if($view == "publish") {
scriptend]
  </body>
</html>
[hyperCMS:scriptbegin 
  }
}
scriptend]]]></content>
</template>