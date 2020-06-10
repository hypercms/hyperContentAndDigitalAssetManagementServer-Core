<?xml version="1.0" encoding="utf-8" ?>
<template>
<name>PDF-Generator</name>
<user>hypercms</user>
<category>comp</category>
<extension>pdf</extension>
<application>generator</application>
<content><![CDATA[
[hypercms:scriptbegin if ('%view%' != 'publish') { scriptend]
<!DOCTYPE html> 
<html>
  <head>
    <title>[hyperCMS:textu id='Title' height='30' infotype='meta']</title>
    <meta charset="utf-8" />
    [hyperCMS:tplinclude file='HTML2PDF-ServiceCSSDefinition.inc.tpl']
  </head>
  <body>
    [hyperCMS:textu id='Author' height='30' infotype='meta' onPublish='hidden']
    [hyperCMS:textu id='Subject' height='30' infotype='meta' onPublish='hidden']
    [hyperCMS:textu id='Keywords' height='30' infotype='meta' onPublish='hidden']

    <div style="width:210mm; margin:20px auto 0px auto;">[hyperCMS:components id='cover']</div>
    <br />
    <div style="width:210mm; margin:0 auto;">[hyperCMS:componentm id='pages']</div>
    <br />
  </body>
</html>
[hypercms:scriptbegin } elseif ('%view%' != 'template') {
  $cover = "[hyperCMS:components id='cover' onEdit='hidden' pathtype='file']";
  $pages = "[hyperCMS:componentm id='pages' onEdit='hidden' pathtype='file']";
  $dest_file = $mgmt_config['abs_path_temp'].uniqid().".pdf";

  // convert
  $result = html2pdf ($pages, $dest_file, $cover, $toc=false, $page_orientation="Portrait", $page_size="A4", $page_margin=10, $image_dpi=144, $image_quality=95, $smart_shrinking=true, $options="");

  // return PDF code
  if ($result) echo file_get_contents ($dest_file);
  else echo "Error, please see the event log for more details";

} scriptend]
]]></content>
</template>