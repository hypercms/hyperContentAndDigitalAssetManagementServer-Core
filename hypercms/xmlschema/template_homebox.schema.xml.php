<?xml version="1.0" encoding="utf-8" ?>
<template>
<name>HomeBox</name>
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
[hyperCMS:help id='help' value='Define the contents of a Home box. The content may provide a link to a collection of assets in a folder or a link to a single asset.']

[hyperCMS:scriptbegin
// Component Link
if (!empty ("[hyperCMS:components id='Link' onEdit='hidden']"))
{
  $complink = "[hyperCMS:components id='Link' pathtype='location' onEdit='hidden']";

  $linktype = "[hyperCMS:textl id='LinkType' onEdit='hidden']";

  if (!empty ($complink))
  {
    // create secure token
    $token = createtoken ($user);

    if ($linktype == "Open asset") $link = "hcms_openWindow('frameset_content.php?ctrlreload=yes&site=".getpublication ($complink)."&cat=comp&location=".getlocation ($complink)."&page=".getobject ($complink)."&token=".$token."', '', 'location=no,menubar=no,toolbar=no,titlebar=no,status=yes,scrollbars=no,resizable=yes', ".windowwidth("object").", ".windowheight("object").");";
    if ($linktype == "Display in browser") $link = "location.hypercms_href='".createwrapperlink (getpublication ($complink), getlocation ($complink), getobject ($complink), "comp")."';";
    if ($linktype == "Download") $link = "location.hypercms_href='".createdownloadlink (getpublication ($complink), getlocation ($complink), getobject ($complink), "comp")."';";
  }
  else $link = "";
}
// Media Link / Location
else
{
  // location for link
  $location = getlocation ("[hyperCMS:mediafile id='Background' onEdit='hidden' pathtype='location']");

  if (!empty ($location)) $link= "location.hypercms_href='frameset_objectlist.php?location=".url_encode ($location)."&virtual=0';";
  else $link = "";
}
scriptend]

  [hyperCMS:textl id='Size' label='Size of the home box' list='Small|Large' onPublish='hidden']
  [hyperCMS:components id='Link' label='Link to a single asset (optional)' onPublish='hidden']
  [hyperCMS:textl id='LinkType' label='Link type' list='Open asset|Display in browser|Download' onPublish='hidden']
  <div class="hcmsHomeBox hcmsTextShadow" onclick="[hyperCMS:scriptbegin echo $link; scriptend]" style="cursor:pointer; overflow:auto; width:<?php echo $width; ?>; height:400px; margin:10px; background-image:url('[hyperCMS:mediafile id='Background' label='Background image (link to collection)' mediatype='image' pathtype='wrapper']'); background-size:cover; background-repeat:no-repeat; background-position:center center;">
    <div style="padding:10px;">
      <h2 style="margin:0; padding:0;">[hyperCMS:textu id='Title' label='Title' height='30']</h2>
      <span>[hyperCMS:textu id='Description' label='Description' height='60']</span>
    </div>
  </div>

]]></content>
</template>