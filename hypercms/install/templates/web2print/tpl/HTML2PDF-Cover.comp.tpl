<?xml version="1.0" encoding="utf-8" ?>
<template>
<name>PDF-Cover</name>
<user>hypercms</user>
<category>comp</category>
<extension>page</extension>
<application>htm</application>
<content><![CDATA[
[hypercms:scriptbegin if ('%view%' != 'preview') { scriptend]
<!DOCTYPE html> 
<html>
  <head>
    <title>Cover Page</title>
    <meta charset="utf-8" />
    [hyperCMS:tplinclude file='HTML2PDF-ServiceCSSDefinition.inc.tpl']
  </head>
  <body>
[hypercms:scriptbegin } scriptend]
    <div class="page" style="background-color:#FFF; background-image: url('[hyperCMS:mediafile id='BackgroundImage' colorspace='Gray' dpi='300' mediatype='image']'); background-size:cover;">
      <div style="top:120mm; left:20mm; position:relative;" >
        <img src="[hyperCMS:mediafile id='Logo' mediatype='image' colorspace='Gray' dpi='300']" width="[hyperCMS:mediawidth id='Logo']" height="[hyperCMS:mediaheight id='Logo']">
      </div>
      <div style="top:150mm; left:10mm; height:30mm; position:relative; overflow:hidden;">[hyperCMS:textf id='Version' toolbar='PDF']</div>
      <div style="top:150mm; left:60mm; height:20mm; position:relative; overflow:hidden;">[hyperCMS:textf id='Title' toolbar='PDF']</div>
    </div>
[hypercms:scriptbegin if ('%view%' != 'preview') { scriptend]
  </body>
</html>
[hypercms:scriptbegin } scriptend]
]]></content>
</template>