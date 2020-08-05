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

     <!-- Size -->
    [hyperCMS:textl id='Size' label='Size of the download box' list='Small|Large' onPublish='hidden']

    <!-- Download -->
    <div class="download_container" onclick="location.hypercms_href='[hyperCMS:mediafile id='Download' pathtype='download']';">
        <div class="download_box">
          <img class="download_icon" style="max-width:50px; max-height:50px;" <img src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAADIAAAAyCAYAAAAeP4ixAAAABmJLR0QA/wD/AP+gvaeTAAABCUlEQVRoge3ZvQ3CMBBA4QeigAZEARIjZAs6pqJhK0pWQAg6VuCngQ4KuCYixEns+BzdJ7khwfYr7YAx5p9eoHmXwBqY5H6/ARtgF2hdr0bAFXgVjAswjLa7CjKKI2Rkvhft+57QcU7v64YIicJCtLEQbSxEGwvRxkK0sRBtOhMyqPGfIZ/DU5GxwxxjYPrn+QN4VtlUHTNgT/nhqe44AYvQEaFjWo0QvmOiRAhfMVEjRNMYFRGiboyqCFE1RmWEcI1RHSHKYpKIEEUxSUWIfEySEUJiko4Q8+8wxpjmfn0MXeF2XI3pDmzLXjrg/+Tnexzym+7MLUpnQn5dBx1p4SqmoXPsDRiTmjcXLd16/YI0/gAAAABJRU5ErkJggg=="/>
        </div>
        <div class="download_text">
          <div style="margin:auto 5px;"><b>[hyperCMS:textu id='Name' label='Name' height='30']</b></div>
        </div>
    </div>

]]></content>
</template>