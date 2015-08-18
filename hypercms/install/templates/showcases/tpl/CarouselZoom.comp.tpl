<?xml version="1.0" encoding="utf-8" ?>
<template>
<name>CarouselZoom</name>
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
$pictureTagId = "picture";
$picture = "[hyperCMS:mediafile id='picture' onEdit='hidden']";
// picture - file extensions
$picture_extensions = ".jpg.png.gif.bmp";

// User entries
$title = "[hyperCMS:textu id='title' onEdit='hidden']";
$numOfPicture = "[hyperCMS:textu id='numOfPicture' onEdit='hidden' default='1']";
$imageContainer_width = "[hyperCMS:textu id='imageContainer_width' onEdit='hidden' default='400']";
$imageContainer_height = "[hyperCMS:textu id='imageContainer_height' onEdit='hidden' default='274']";
$zoomWindow_width = "[hyperCMS:textu id='zoomWindow_width' onEdit='hidden' default='400']";
$zoomWindow_height = "[hyperCMS:textu id='zoomWindow_height' onEdit='hidden' default='400']";
$zoomWindow_border = "[hyperCMS:textu id='zoomWindow_border' onEdit='hidden' default='3']";
$thumbnails_height = "[hyperCMS:textu id='thumbnails_height' onEdit='hidden' default='70']";

// CMS VIEW => get user entries => title,height,widht,picture amaount...
if ($view == "cmsview")
{
scriptend]
<!DOCTYPE html>
<html>
	<head>
		<link rel="stylesheet" hypercms_href="[hyperCMS:scriptbegin echo getthemelocation(); scriptend]css/main.css" />
	</head>
	<body class="hcmsWorkplaceGeneric">
		<div class="hcmsWorkplaceFrame">
			<br />
			<table>
				<tr>
					<td>Select Picture / Folder <!-- [hyperCMS:mediafile id='picture' label='Picture (folder)' mediatype='image' onPublish='hidden'] --></td><td><img src="[hyperCMS:mediafile id='picture' label='Picture (folder)' mediatype='image' thumbnail='yes' onEdit='hidden']" /></td>
				</tr>
				<tr>
					<td>Amount of pictures</td><td><div style="display:inline-block; padding:2px; border:1px solid #000;">[hyperCMS:textu id='numOfPicture' label='Amount of pictures' constraint='isNum' height='15' width='100']</div></td>
				</tr>
				<tr>
					<td>Width of preview</td><td><div style="display:inline-block; padding:2px; border:1px solid #000;">[hyperCMS:textu id='imageContainer_width' label='Width of preview' constraint='isNum' default='320' height='15' width='100']</div> px</td>
				</tr>
				<tr>
					<td>Height of preview</td><td><div style="display:inline-block; padding:2px; border:1px solid #000;">[hyperCMS:textu id='imageContainer_height' label='Height of preview' constraint='isNum' default='260' height='15' width='100']</div> px</td>
				</tr>
				<tr>
					<td>Width of zoom-window</td><td><div style="display:inline-block; padding:2px; border:1px solid #000;">[hyperCMS:textu id='zoomWindow_width' label='Width of zoom-window' constraint='isNum' default='800' height='15' width='100']</div> px</td>
				</tr>
				<tr>
					<td>Height of zoom-window</td><td><div style="display:inline-block; padding:2px; border:1px solid #000;">[hyperCMS:textu id='zoomWindow_height' label='Height of zoom-window' constraint='isNum' default='600' height='15' width='100']</div> px</td>
				</tr>
				<tr>
					<td>Border of zoom-window</td><td><div style="display:inline-block; padding:2px; border:1px solid #000;">[hyperCMS:textu id='zoomWindow_border' label='Border of zoom-window' constraint='isNum' default='1' height='15' width='100']</div> px</td>
				</tr>
				<tr>
					<td>Height of thumbnails</td><td><div style="display:inline-block; padding:2px; border:1px solid #000;">[hyperCMS:textu id='thumbnails_height' label='Height of thumbnails' constraint='isNum' default='80' height='15' width='100']</div> px</td>
				</tr>
				<tr>
					<td>&nbsp;</td><td><button class="hcmsButtonGreen" type="button" onClick="location.reload();" >generate code</button></td>
				</tr>
			</table>
			<p>
			<tr>
					Please do not forget to publish this page after changing the parameters!
				</tr>
			</p>
			<hr>
[hyperCMS:scriptbegin
	
	//check if component is published
	$compinfo = getfileinfo($site, $correctFile, "comp");
  
	if ($compinfo['published'])
  {
		//create embed code
		$iframe_standard_height = $thumbnails_height + $imageContainer_height + 30;
    
		if ($iframe_standard_height < ($zoomWindow_height + (2 * $zoomWindow_border)))
			$iframe_zoom_height = $zoomWindow_height + (2 * $zoomWindow_border);
		else
			$iframe_zoom_height = $iframe_standard_height;
      
		$iframe_zoom_width = 	$imageContainer_width + $zoomWindow_width + (2 * $zoomWindow_border)+1;
		$embed_code = "<div style=\"width: {$imageContainer_width}px; height: {$iframe_standard_height}px; position: relative; z-index: 2147483647;\" onmouseover=\"document.getElementById('frame_$uniqid').style.height = $iframe_zoom_height + 'px'; document.getElementById('frame_$uniqid').style.width = $iframe_zoom_width + 'px';\" onmouseout=\"document.getElementById('frame_$uniqid').style.height = $iframe_standard_height + 'px'; document.getElementById('frame_$uniqid').style.width = $imageContainer_width + 'px';\"><iframe id='frame_$uniqid' src='{$mgmt_config['url_path_cms']}?wl=$hash' scrolling='no' frameborder=0 border=0 style='width: {$imageContainer_width}px; height: {$iframe_standard_height}px;'></iframe></div>";
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
			<textarea id="codesegment" wrap="VIRTUAL" style="height:140px; width:98%">[hyperCMS:scriptbegin echo html_encode($embed_code); scriptend]</textarea>
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
		<meta charset='utf-8'/>	
[hyperCMS:scriptbegin
	}
scriptend]
		<script type="text/javascript" src="[hyperCMS:scriptbegin echo $mgmt_config['url_path_cms']; scriptend]javascript/jquery/jquery-1.10.2.min.js"></script>
		<script type="text/javascript" src="[hyperCMS:scriptbegin echo $mgmt_config['url_path_cms']; scriptend]javascript/iframe_carouselzoom/jquery.elevateZoom-2.5.5.min.js"></script>
		<script type="text/javascript" src="[hyperCMS:scriptbegin echo $mgmt_config['url_path_cms']; scriptend]javascript/iframe_carouselzoom/modernizr.custom.17475.js"></script>
		<script type="text/javascript" src="[hyperCMS:scriptbegin echo $mgmt_config['url_path_cms']; scriptend]javascript/iframe_carouselzoom/jquerypp.custom.js"></script>
		<script type="text/javascript" src="[hyperCMS:scriptbegin echo $mgmt_config['url_path_cms']; scriptend]javascript/iframe_carouselzoom/jquery.elastislide.js"></script>
		<link rel="stylesheet" type="text/css" [hyperCMS:scriptbegin if($view == 'preview')echo 'hypercms_'; scriptend]href="[hyperCMS:scriptbegin echo $mgmt_config['url_path_cms']; scriptend]/javascript/iframe_carouselzoom/elastislide.css" />
		<style>
				body {
					margin: 0px;
					padding: 0px;
				}
				
				#gal_[hyperCMS:scriptbegin echo $uniqid; scriptend] a {
					text-decoration: none;
					background: #000;
					font-size: 0px;
				}
				
				#gal_[hyperCMS:scriptbegin echo $uniqid; scriptend] a img {
					border: none;
					opacity: 0.6;
				}
				
				#gal_[hyperCMS:scriptbegin echo $uniqid; scriptend] .active img {
					opacity: 1;
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
	if (substr_count($picture, "Null_media.gif") == 1)
  {
scriptend]
		<p>No media file selected!<p>
[hyperCMS:scriptbegin
	}
	else
  {
		$mediaFiles = collectMedia($site, $container_id, $pictureTagId, $abs_comp, $picture_extensions );
		if(empty($mediaFiles))
		{
scriptend]
	<p>Folder could not be read!</p>
[hyperCMS:scriptbegin		
		}
		else
		{	
scriptend]
		<img id="zoom_[hyperCMS:scriptbegin echo $uniqid; scriptend]" src="[hyperCMS:scriptbegin echo $mediaFiles[0]['link']; scriptend]" data-zoom-image="[hyperCMS:scriptbegin echo $mediaFiles[0]['link']; scriptend]" width="[hyperCMS:scriptbegin echo $imageContainer_width; scriptend]px" height="[hyperCMS:scriptbegin echo $imageContainer_height; scriptend]px"/>
[hyperCMS:scriptbegin
			if($numOfPicture > 1 && count($mediaFiles) > 1)
			{
scriptend]		
		<div style="width: [hyperCMS:scriptbegin echo $imageContainer_width; scriptend]px;">
			<ul id="gal_[hyperCMS:scriptbegin echo $uniqid; scriptend]" class="elastislide-list">
[hyperCMS:scriptbegin
				$i = 1;
				foreach($mediaFiles as $media)
				{
					//termination if picture amount reached
					if($i > $numOfPicture) break;
scriptend]
				<li>
					<a href="#" data-image="[hyperCMS:scriptbegin echo $media['link']; scriptend]" data-zoom-image="[hyperCMS:scriptbegin echo $media['link']; scriptend]" [hyperCMS:scriptbegin if($i == 1) echo 'class="active"'; scriptend]>
						<img  src="[hyperCMS:scriptbegin echo $media['thumb_link']; scriptend]" height="[hyperCMS:scriptbegin echo $thumbnails_height scriptend]" />
					</a>
				</li>
[hyperCMS:scriptbegin
					$i++;
				}
scriptend]
			</ul>
		</div>
		<script>
			$("#zoom_[hyperCMS:scriptbegin echo $uniqid; scriptend]").elevateZoom({
						gallery : "gal_[hyperCMS:scriptbegin echo $uniqid; scriptend]",
						galleryActiveClass: "active",
						zoomWindowWidth:[hyperCMS:scriptbegin echo $zoomWindow_width; scriptend],
						zoomWindowHeight:[hyperCMS:scriptbegin echo $zoomWindow_height; scriptend],
						borderSize: [hyperCMS:scriptbegin echo $zoomWindow_border; scriptend]						
						});
			$( '#gal_[hyperCMS:scriptbegin echo $uniqid; scriptend]' ).elastislide();						
		</script>
[hyperCMS:scriptbegin
			}
			else
			{
scriptend]
		<script>
			$("#zoom_[hyperCMS:scriptbegin echo $uniqid; scriptend]").elevateZoom();
		</script>
[hyperCMS:scriptbegin						
			}
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