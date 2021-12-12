<?xml version="1.0" encoding="utf-8" ?>
<template>
<name>GalleriaSearch</name>
<user>admin</user>
<category>comp</category>
<extension>php</extension>
<application>php</application>
<content><![CDATA[[hyperCMS:objectview name='inlineview']
[hyperCMS:fileinclude file='%abs_hypercms%/config.inc.php']
[hyperCMS:fileinclude file='%abs_hypercms%/function/hypercms_api.inc.php']
[hyperCMS:tplinclude file='ServiceSearchMedia.inc.tpl']
<?php
global $mgmt_config;

// Input
$search = getrequest_esc ("search");

// Init
$uniqid = uniqid();
$site = "%publication%";
$abs_comp = "%abs_comp%/";
$container_id = "%container_id%";
$object = "%object%";
$view = "%view%";
$hash = "%objecthash%";

// file extension
$file_ext = strtolower (strrchr ($object, "."));

// picture - file extensions
$picture_extensions = ".jpg.png.gif.bmp";

// User entry - picture / folder
$picture = "[hyperCMS:mediafile id='picture' onEdit='hidden']";
$pictureTagId = "picture";

// Metadata IDs to display
$metaTitleId = "Title";
$metaDescriptionId = "Description";

// Use search instead of all images in directory
$mgmt_config['publicsearch'] = true;

// USER ENTRIES
$galleriaWidth = "[hyperCMS:textu id='galleriaWidth' onEdit='hidden']";
$galleriaHeight = "[hyperCMS:textu id='galleriaHeight' onEdit='hidden']";
$showInfo = "[hyperCMS:textc id='showInfo' onEdit='hidden']";
$filtername = "[hyperCMS:textl id='filterName' onEdit='hidden']";
$filtervalue = "[hyperCMS:textu id='filterValue' onEdit='hidden']";

// SET FILTER
if ("[hyperCMS:textl id='filterName' onEdit='hidden']" != "")
{
  $filter = array ($filtername => $filtervalue);
}
elseif ($search != "")
{
  $filter = array ($search);
}
else $filter = "";

// CREATE VIEW FILE
if (is_file ("%abs_location%/%object%") && !empty ($file_ext))
{
  // create file in view directory
  if (!is_file ($mgmt_config['abs_path_view'].$hash.$file_ext))
  {
    copy ("%abs_location%/%object%", $mgmt_config['abs_path_view'].$hash.$file_ext);
  }
  // update file in view directory on publish 
  elseif ($view == "publish" && filemtime ($mgmt_config['abs_path_view'].$hash.$file_ext) < filemtime ("%abs_location%/%object%"))
  {
    copy ("%abs_location%/%object%", $mgmt_config['abs_path_view'].$hash.$file_ext);
  }
}

// CMS VIEW => get user entry and create iframe code
if ($view == "cmsview")
{
?>
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
      <p>
        Please do not forget to publish this page after changing the parameters!<br/>
        This component is only supported if used inside a CMs publication!
      </p>
      <hr/>
<?php
  //check if component is published
  $objectpath = correctfile ("%abs_location%", "%object%");
  $compinfo = getfileinfo ($site, $objectpath, "comp");

  if ($compinfo['published'])
  {
    // the search requires a CMS publication with direct http access to the component!
    $embed_code = "<iframe id='frame_$uniqid' src='".$mgmt_config['url_path_view'].$hash.$file_ext."' frameborder='0' style='border:0; width:".$galleriaWidth."px; height:".$galleriaHeight."px; overflow:hidden;'></iframe>";
  }
  else
  {
    $embed_code = "Component is not published yet!";
  }
?>
      <strong>HTML body segment</strong>
      <br />
      Mark and copy the code from the text area box (keys ctrl + A and Ctrl + C for copy or right mouse button -> copy).  Insert this code into your HTML Body of the page, where the snippet will be integrated (keys Crtl + V or right mouse button -> insert).
      <br />
      <br />
      <textarea id="codesegment" wrap="VIRTUAL" style="height:80px; width:98%"><?php echo html_encode($embed_code); ?></textarea>
      <br />
      <hr/>
      <strong>Online view</strong>
      <br />
      <?php if ($compinfo['published']) echo "<iframe id='frame_$uniqid' src='".$mgmt_config['url_path_view'].$hash.$file_ext."' frameborder='0' style='border:1px solid grey; background-color:#000000; width:".$galleriaWidth."px; height:".$galleriaHeight."px; overflow:hidden;'></iframe>"; ?>
    </div>
  </body>
</html>

<?php
}
// published file
elseif ($view == "publish" || $view == "preview")
{
?>
<!DOCTYPE html>
<html>
  <head>
    <title>hyperCMS.com</title>
    <meta charset='utf-8'/>
    <script type="text/javascript" src="[hyperCMS:scriptbegin echo $mgmt_config['url_path_cms']; scriptend]javascript/jquery/jquery-3.5.1.min.js"></script>
    <script type="text/javascript" src="[hyperCMS:scriptbegin echo $mgmt_config['url_path_cms']; scriptend]javascript/jquery/jquery-migrate-3.3.0.min.js"></script>
    <script type="text/javascript" src="[hyperCMS:scriptbegin echo $mgmt_config['url_path_cms']; scriptend]javascript/iframe_galleria/galleria-1.2.9.min.js"></script>
    <style>
        body {
          margin: 0px;
          padding: 0px;
          background-color: #000000;
        }

        #search {
          padding: 10px;
        }

        #search input {
          padding: 5px;
        }

        #search button {
          padding: 5px;
          border: 0;
          background-color: #666666;
          color: #FFFFFF;
        }
        
        #galleria {
          width: <?php if ($galleriaWidth > 0) echo $galleriaWidth; else echo "800"; ?>px;
          height: <?php if ($galleriaHeight > 0) echo $galleriaHeight; else echo "600"; ?>px;
        }
    </style>
  </head>
  <body>

<?php if (!empty ($mgmt_config['publicsearch']) && "[hyperCMS:textl id='filterName' onEdit='hidden']" == "") { ?>
  <div id="search">
    <form action="<?php if ("%view%" != "publish") echo $mgmt_config['url_path_view'].$hash.$file_ext; ?>">
      <input type="text" name="search" value="<?php echo $search; ?>" placeholder="Expression" />
      <button>Search</button>
    </form>
  </div>
<?php } ?>

  <div id="galleria"></div>

<script type="text/javascript">
var data = [
<?php
if ("%view%" == "publish")
{
  if (!empty ($filter) && sizeof ($filter) > 0)
  {
    $mediaFiles = searchMedia ($site, $container_id, $pictureTagId, $abs_comp, $picture_extensions, $metaTitleId, $metaDescriptionId, $filter);

    if (!empty ($mediaFiles))
    {
      $i = 0;

      foreach ($mediaFiles as $media)
      {
        if ($i > 0) echo "    ,\r\n";
?>
    {
        image: '<?php echo $media['link']; ?>',
        thumb: '<?php echo $media['thumb_link']; ?>',
        title: '<?php echo (empty($media['title']) ? $media['name'] : $media['title']); ?>',
        description: '<?php  echo (empty($media['description']) ? $media['name'] : $media['description']); ?>'
    }
<?php

        $i++;
      }
    }
  }
}
?>
];

  // Load the classic theme
  Galleria.loadTheme("[hyperCMS:scriptbegin echo $mgmt_config['url_path_cms']; scriptend]javascript/iframe_galleria/galleria.classic.min.js");

  // Initialize Galleria
  $('#galleria').galleria({
     lightbox: false,
     thumbnails: 'lazy',
     showInfo: <?php if (!empty ($showInfo)) echo "true"; else echo "false";  ?>,
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
<?php
}
?>]]></content>
</template>