<?xml version="1.0" encoding="utf-8" ?>
<template>
<name>PDF-StandardPage</name>
<user>hypercms</user>
<category>comp</category>
<extension>page</extension>
<application>htm</application>
<content><![CDATA[
[hypercms:scriptbegin if ('%view%' != 'preview') { scriptend]
<!DOCTYPE html>
<html>
  <head>
    <title>[hyperCMS:textu id='Title' height='30' infotype='meta']</title>
    <meta charset="utf-8" />
    [hyperCMS:tplinclude file='HTML2PDF-ServiceCSSDefinition.inc.tpl']
  </head>
  <body>
[hypercms:scriptbegin } scriptend]
    <div class="page">
      [hyperCMS:textf id='MainText' dpi='300' colorspace='Gray']
    </div>
[hypercms:scriptbegin if ('%view%' != 'preview') { scriptend]
  </body>
</html>
[hypercms:scriptbegin } scriptend]
]]></content>
</template>