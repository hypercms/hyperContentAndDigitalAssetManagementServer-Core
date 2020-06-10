<?xml version="1.0" encoding="utf-8" ?>
<template>
<name>PDF-ServiceCSSDefinition</name>
<user>hypercms</user>
<category>inc</category>
<extension></extension>
<application></application>
<content><![CDATA[
<style>
html, body
{
  font-family: verdana,geneva,sans-serif;
  font-size: 12pt;
  width: 100%;
  height: 100%;
  background-color: #666;
}

h1
{
  font-size: 16pt;
}

h2
{
  font-size: 14pt;
}

.page
{
  width: 190mm;
  height: 277mm;
  padding: 10mm;
  border: 1px solid black;
  margin: 0 auto;
  background-color: #FFF;
  overflow: hidden;
}

@page
{
  size: A4;
  margin: 0;
}

@media print
{
  html, body
  {
    width: 210mm;
    height: 297mm;
    font-family: verdana,geneva,sans-serif;
    font-size: 12pt;
    background-color: #FFF;
    padding: 0;
    margin: 0;
  }

  h1
  {
    font-size: 16pt;
  }

  h2
  {
    font-size: 14pt;
  }

  .page
  {
    width: 100%;
    height: 100%;
    border: 0;
    padding: 0;
    margin: 0;
  }
}
</style>
]]></content>
</template>