<?xml version="1.0" encoding="utf-8" ?>
<template>
<name>PDF-ServiceBookmark</name>
<user>hypercms</user>
<category>inc</category>
<extension></extension>
<application></application>
<content><![CDATA[[hypercms:scriptbegin
  function bookmark ($pdf, $container, $text_id)
  {	
    if (!isset($pdf) || !is_object($pdf) || empty($text_id))
    {
      return false;
    }
    
    // bookmarking for table of content
    $xmldata =loadcontainer ($container, "work", "sys");
    if ($xmldata != false) $xmldata = selectcontent ($xmldata, "<text>", "<text_id>", $text_id);
    if ($xmldata != false) $xmldata = getcontent ($xmldata[0], "<textcontent>");
    $pattern = '#<h([1-3])[^>]*>(.*?)</h\1>#i';
    
    if (is_array($xmldata) && array_key_exists("0", $xmldata) && preg_match_all($pattern, $xmldata[0], $arr))
    {
      foreach($arr[2] as $key=>$value)
      {
        //strip html tags 
        $title = strip_tags($value);
        
        //decode and replace &nbsp
        $title = html_entity_decode($title, ENT_COMPAT, 'UTF-8');
        
        $level = ($arr[1][$key]-1);
        $pdf->Bookmark($title, $level, $y=-1, $page='' , $style = '', $color = array(0,0,0), $x=-1, $link = '');
      }
    }
  }
  
  // define styles for various bookmark levels
  $bookmark_templates = array();
  
  // level 0 = h1
  $bookmark_templates[0]='<table border="0" cellpadding="0" cellspacing="0" style="font-family:verdana,geneva,sans-serif; font-size: 10px;">
      <tr><td colspan="4" height="3mm"></td></tr>
      <tr>
        <td width="22mm">&nbsp;</td>
        <td width="143mm" align="left">#TOC_DESCRIPTION#</td>
        <td width="20mm" align="right">#TOC_PAGE_NUMBER#</td>
        <td width="25mm">&nbsp;</td>
      </tr>
      <tr><td colspan="4" height="3mm"></td></tr>
    </table>';
  // level 2 = h2
  $bookmark_templates[1]='<table border="0" cellpadding="0" cellspacing="0" style="font-family:verdana,geneva,sans-serif; font-size: 10px;">
      <tr>
        <td width="27mm">&nbsp;</td>
        <td width="138mm" align="left">#TOC_DESCRIPTION#</td>
        <td width="20mm" align="right">#TOC_PAGE_NUMBER#</td>
        <td width="25mm">&nbsp;</td>
      </tr>
    </table>';
  $bookmark_templates[2]='<table border="0" cellpadding="0" cellspacing="0" style="font-family:verdana,geneva,sans-serif; font-size: 10px;">
      <tr>
        <td width="32mm">&nbsp;</td>
        <td width="133mm" align="left">#TOC_DESCRIPTION#</td>
        <td width="20mm" align="right">#TOC_PAGE_NUMBER#</td>
        <td width="25mm">&nbsp;</td>
      </tr>
    </table>';
scriptend]]]></content>
</template>