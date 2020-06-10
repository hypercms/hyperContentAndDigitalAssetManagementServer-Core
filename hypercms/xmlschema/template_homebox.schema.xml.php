<?xml version="1.0" encoding="utf-8" ?>
<template>
<name>HomeBoxSmall</name>
<user>sys</user>
<category>comp</category>
<extension>php</extension>
<application>php</application>
<content><![CDATA[[hyperCMS:objectview name='formedit']
<?php
// ---------------------- HOME BOX SMALL ---------------------

$size = "[hyperCMS:textl id='Size' onEdit='hidden']";

if (!empty ($is_mobile)) $width = "92%";
elseif ($size == "Large") $width = "670px";
else $width = "320px";
?>

[hyperCMS:scriptbegin

// location for link
$location = getlocation ("[hyperCMS:mediafile id='Background' onEdit='hidden' pathtype='location']");

if (!empty ($location)) $link= "frameset_objectlist.php?location=".url_encode ($location)."&virtual=0";
else $link = "";

// only for preview purposes
if ("%view%" != "publish")
{
scriptend]
<!DOCTYPE html>
<html>
  <head>
    <title>hyperCMS.com</title>
    <meta charset='utf-8'/>
    <link rel="stylesheet" hypercms_href="[hyperCMS:scriptbegin echo getthemelocation(); scriptend]css/main.css" />
    <link rel="stylesheet" href="[hyperCMS:scriptbegin echo getthemelocation()."css/".($is_mobile ? "mobile.css" : "desktop.css"); scriptend]" />
  </head>
  <body class="hcmsWorkplaceGeneric">
    <div class="hcmsWorkplaceFrame">
      <br />
[hyperCMS:scriptbegin
}
scriptend]

  [hyperCMS:textl id='Size' label='Size of the home box' list='Small|Large' onPublish='hidden']
  <div class="hcmsHomeBox hcmsTextShadow" onclick="location.href='[hyperCMS:scriptbegin echo $link; scriptend]';" style="cursor:pointer; overflow:auto; margin:10px; width:<?php echo $width; ?>; height:400px; float:left; background-image:url('[hyperCMS:mediafile id='Background' label='Background and Link' mediatype='image' pathtype='wrapper']'); background-size:cover; background-repeat:no-repeat; background-position:center center;">
    <div style="padding:10px;">
      <h2 style="margin:0; padding:0;">[hyperCMS:textu id='Title' label='Title' height='30']</h2>
      <span>[hyperCMS:textu id='Description' label='Description' height='60']</span>
    </div>
  </div>

[hyperCMS:scriptbegin
// only for preview purposes
if ("%view%" != "publish")
{
scriptend]
  </body>
</html>
[hyperCMS:scriptbegin
}
scriptend]]]></content>
</template>