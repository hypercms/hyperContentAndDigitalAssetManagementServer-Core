<?xml version="1.0" encoding="utf-8" ?>
<template>
<name>BrandColor</name>
<user>sys</user>
<category>comp</category>
<extension>php</extension>
<application>php</application>
<content><![CDATA[

[hyperCMS:compstylesheet file='%url_hypercms%/theme/day/css/main.css']
[hyperCMS:compstylesheet file='%url_hypercms%/theme/brandguide.css'] 

    <!-- Page -->
    <div class="page_container">
      <div class="text">[hyperCMS:textf id='Content' label='Content' height='800' toolbar='Default']</div>
      <div class="colors">[hyperCMS:componentm id='Colors' label='Select Colors' mediatype='component']</div>
      <div class="downloads">[hyperCMS:componentm id='Downloads' label='Select Downloads' mediatype='component']</div>
      <div style="clear:both;"></div>
      <hr/>
    </div>

]]></content>
</template>