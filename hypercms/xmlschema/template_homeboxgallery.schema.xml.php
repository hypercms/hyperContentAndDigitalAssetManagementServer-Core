<?xml version="1.0" encoding="utf-8" ?>
<template>
<name>HomeBox-Gallery</name>
<user>sys</user>
<category>comp</category>
<extension>php</extension>
<application>php</application>
<content><![CDATA[[hyperCMS:objectview name='formedit']
<?php
// ---------------------- HOME BOX ---------------------

$size = "[hyperCMS:textl id='Size' onEdit='hidden']";

// box width
if (!empty ($is_mobile)) $width = "320px";
elseif ($size == "Large") $width = "670px";
else $width = "320px";
?>
[hyperCMS:help id='help' value='Define a collection of assets for the presentation and download in the Home box.']

[hyperCMS:scriptbegin
// Component Link
if (!empty ("[hyperCMS:componentm id='Collection' onEdit='hidden']"))
{
  $complink = "[hyperCMS:componentm id='Collection' pathtype='location' onEdit='hidden']";

  if (!empty ($complink))
  {
    $multiobject = link_db_getobject ($complink);    
    $gallery = showgallery ($multiobject, 140, "download");
  }
}
scriptend]

  [hyperCMS:textl id='Size' label='Size of the home box' list='Small|Large' onPublish='hidden']
  [hyperCMS:componentm id='Collection' label='Select assets (optional)' onPublish='hidden']
  <div class="hcmsHomeBox hcmsTextShadow" style="overflow:auto; width:<?php echo $width; ?>; height:400px; margin:10px; background-image:url('[hyperCMS:mediafile id='Background' label='Background image (link to collection)' mediatype='image' pathtype='wrapper']'); background-size:cover; background-repeat:no-repeat; background-position:center center;">
    <div style="padding:10px;">
      <h2 style="margin:0; padding:0;">[hyperCMS:textu id='Title' label='Title' height='30']</h2>
      <div style="margin-bottom:10px;">[hyperCMS:textu id='Description' label='Description' height='60']</div>
      <div>[hyperCMS:scriptbegin if (!empty ($gallery)) echo $gallery; scriptend]</div>
    </div>
  </div>

]]></content>
</template>