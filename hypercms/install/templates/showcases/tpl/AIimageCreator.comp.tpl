<?xml version="1.0" encoding="utf-8" ?>
<template>
<name>AIimageCreator</name>
<user>sys</user>
<category>comp</category>
<extension>jpg</extension>
<application>generator</application>
<content><![CDATA[[hyperCMS:objectview name='formedit']
[hypercms:scriptbegin
if ('%view%' != 'publish')
{
scriptend]

  [hyperCMS:help id='Help' height='30' value='Provide a description that will be used to create an image with AI when published']
  [hyperCMS:textu id='Title' infotype='meta' height='30']
  [hyperCMS:textu id='Description' infotype='meta' label='Description (will be used to create an image with AI when published)' height='90']
  [hyperCMS:textu id='Keywords' infotype='meta' height='30']
  [hyperCMS:textu id='Copyright' label='Copyright' infotype='meta' height='30']
  [hyperCMS:textu id='Creator' label='Creator' infotype='meta' height='30']
  [hyperCMS:textd id='License' infotype='meta' label='License valid till']
  [hyperCMS:textl id='Quality' label='Quality' infotype='meta' height='30' mediatype='image' list='|Print|Web']
  [hyperCMS:commentf id='Comment' infotype='meta' height='100']
  [hyperCMS:componentm id='Related' label='Related assets']
  [hyperCMS:geolocation infotype='meta']

[hypercms:scriptbegin
}
elseif ('%view%' != 'template')
{
  // generate images using OpenAI’s DALL-E
  $image = createAIimage ("%publication", "[hyperCMS:textu id='Description' onEdit='hidden']", "image-alpha-001", 1);

  // output
  if (!empty ($image['content']))
  {
    echo $image['content'];
    exit;
  }
  else
  {
    $errcode = "10101";
    $error[] = $mgmt_config['today']."|AIimageCreator.comp.tpl|error|".$errcode."|Image creation failed: ".print_r ($image['data'], true);

    savelog ($error);
  }
}
scriptend]
]]></content>
</template>