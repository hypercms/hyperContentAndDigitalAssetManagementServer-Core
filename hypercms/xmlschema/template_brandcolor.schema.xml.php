<?xml version="1.0" encoding="utf-8" ?>
<template>
<name>BrandColor</name>
<user>sys</user>
<category>comp</category>
<extension>php</extension>
<application>php</application>
<content><![CDATA[[hyperCMS:objectview name='formedit']

[hyperCMS:compstylesheet file='%url_hypercms%/theme/day/css/main.css']
[hyperCMS:compstylesheet file='%url_hypercms%/theme/brandguide.css'] 

    <!-- Color -->
    [hyperCMS:scriptbegin
    $rgb = hex2rgb ("[hyperCMS:textu id='Color' onEdit='hidden']");
    $cmyk = rgb2cmyk ($rgb);

    if (is_array ($rgb)) $rgb = implode (", ", $rgb);
    if (is_array ($cmyk)) $cmyk = implode (", ", $cmyk);
    scriptend]

    <div class="color_container">
      <svg width="100%" height="60" style="border:1px solid #e3e3e3;">
        <rect width="100%" height="100%" style="fill:[hyperCMS:textu id='Color' label='Color in Hex format' onEdit='hidden'];"/>
      </svg>
      <br/>
      <div class="color_text">
        <b>[hyperCMS:textu id='ColorName' label='Color name' height='30']</b><br/>
        <div style="display:inline-block; width:40px;">HEX</div> <div style="display:inline-block;"><b>[hyperCMS:textu id='Color' label='Color in HEX format'width='100' height='30']</b></div><br/>
        <div style="display:inline-block; width:40px;">RGB</div> <div style="display:inline-block;"><b>[hyperCMS:scriptbegin if (!empty ($rgb)) echo $rgb; scriptend]</b></div><br/>
        <div style="display:inline-block; width:40px;">CMYK</div> <div style="display:inline-block;"><b>[hyperCMS:scriptbegin if (!empty ($cmyk)) echo $cmyk; scriptend]</b></div>
      </div>
    </div>

]]></content>
</template>