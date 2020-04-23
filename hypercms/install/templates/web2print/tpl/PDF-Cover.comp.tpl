<?xml version="1.0" encoding="utf-8" ?>
<template>
<name>PDF-Cover</name>
<user>hypercms</user>
<category>comp</category>
<extension>page</extension>
<application>htm</application>
<content><![CDATA[[hypercms:scriptbegin
function pixelToMm ($pixel, $dpi)
{
  return (25.4/$dpi * $pixel / 0.24);
}

if ('%view%' == 'cmsview')
{
scriptend]
<!DOCTYPE html> 
<html>
  <head>
    <title>Cover Page</title>
    <meta charset="utf-8" />
    <link rel="stylesheet" hypercms_href="[hyperCMS:scriptbegin echo getthemelocation("day"); scriptend]css/main.css" />
  </head>
  
  <body class="hcmsWorkplaceGeneric">
  <br />
  <div style="width:595px; margin:0;"><img src="[hyperCMS:mediafile id='BackgroundImage' mediatype='image' colorspace='Gray' dpi='300']" width="0" height="0" /></div>
[hypercms:scriptbegin
}

if ('%view%' == 'cmsview' || '%view%' == "preview")
{
scriptend]
    <div style="width:567px; height:814px; margin: 20px auto 20px auto; padding:24px; background-color:#FFF; background-image: url('[hyperCMS:mediafile id='BackgroundImage'  colorspace='Gray' dpi='300' mediatype='image' onEdit='hidden']'); background-size:cover; -moz-box-shadow:1px 3px 5px #555; -webkit-box-shadow:1px 3px 5px #555; box-shadow:1px 3px 5px #555; overflow:hidden;">
      <div style="top: 300px; left: 70px; position: relative; " >
        <img src="[hyperCMS:mediafile id='Logo' mediatype='image' colorspace='Gray' dpi='300']" width="[hyperCMS:mediawidth id='Logo']" height="[hyperCMS:mediaheight id='Logo']">
      </div>
      <div style="top: 410px; left: 70px; height: 130px; position: relative; overflow: hidden;">[hyperCMS:textf id='Version' toolbar='PDF']</div>
      <div style="top: 410px; left: 240px; height: 80px; position: relative; overflow: hidden">[hyperCMS:textf id='Title' toolbar='PDF']</div>
    </div>
[hypercms:scriptbegin
}

if ('%view%' == 'cmsview')
{
scriptend]
    <br />
    <br />
  </body>
</html>
[hypercms:scriptbegin
}

if ('%view%'=='publish' && !empty($pdf))
{
  // Add a page
  // This method has several options, check the source code documentation for more information.
  $pdf->AddPage();
  
  // add background image
  $pdf->Image ($file="[hyperCMS:mediafile id='BackgroundImage' mediatype='image' colorspace='Gray' dpi='300' onEdit='hidden']", $x=0, $y=0, $w=210, $h=297, $type='', $link='', $align='',  $resize=2, $dpi=300, $palign='', $ismask=false, $imgmask=false, $border=0, $fitbox=false, $hidden=false, $fitonpage=false, $alt=false, $altimgs=array());
  
  // add logo image	
  $pdf->Image ($file="[hyperCMS:mediafile id='Logo' mediatype='image' colorspace='Gray'  dpi='300' onEdit='hidden']", $x=25, $y=100, $w=$pdf->pixelsToUnits([hyperCMS:mediawidth id='Logo']), $h='', $type='', $link='', $align='',  $resize=true, $dpi=300, $palign='', $ismask=false, $imgmask=false, $border=0, $fitbox=false, $hidden=false, $fitonpage=false, $alt=false, $altimgs=array());

  // fetch textf content
  $html_version = "[hyperCMS:textf id='Version' toolbar='PDF' onEdit='hidden']";
  $html_title = "[hyperCMS:textf id='Title' toolbar='PDF' onEdit='hidden']";

  // Print textf content using writeHTMLCell()
  $pdf->writeHTMLCell($w=100, $h=35, $x='25', $y=170, $html_version, $border=0, $ln=1, $fill=0, $reset=true, $align='', $autopadding=true);
  $pdf->writeHTMLCell($w=110, $h=15, $x='75', $y=220, $html_title, $border=0, $ln=1, $fill=0, $reset=true, $align='', $autopadding=true);
}
scriptend]]]></content>
</template>