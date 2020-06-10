<?xml version="1.0" encoding="utf-8" ?>
<template>
<name>PDF-Generator</name>
<user>hypercms</user>
<category>comp</category>
<extension>pdf</extension>
<application>generator</application>
<content><![CDATA[[hyperCMS:tplinclude file='PDF-ServiceBookmark.inc.tpl']
[hyperCMS:tplinclude file='PDF-ServiceCSSDefinition.inc.tpl']
[hypercms:scriptbegin
if ('%view%' != 'publish')
{
scriptend]
<!DOCTYPE html> 
<html>
  <head>
    <title>[hyperCMS:textu id='Title' height='30' infotype='meta']</title>
    <meta charset="utf-8" />
    <link rel="stylesheet" hypercms_href="[hyperCMS:scriptbegin echo getthemelocation("night"); scriptend]css/main.css" />
    <link rel="stylesheet" href="[hyperCMS:scriptbegin echo getthemelocation("night")."css/".($is_mobile ? "mobile.css" : "desktop.css"); scriptend]" />
  </head>
  
  <body class="hcmsWorkplaceGeneric">
    [hyperCMS:textu id='Author' height='30' infotype='meta' onPublish='hidden']
    [hyperCMS:textu id='Title' height='30' infotype='meta' onPublish='hidden']
    [hyperCMS:textu id='Subject' height='30' infotype='meta' onPublish='hidden']
    [hyperCMS:textu id='Keywords' height='30' infotype='meta' onPublish='hidden']
    [hyperCMS:textu id='TOC_title' height='30' label='Title of TOC' infotype='meta' default='Table of content' onPublish='hidden']
    <br />
    <div style="width: 595px; margin: 0 auto 0 auto;">[hyperCMS:componentm id='pages']</div>
    <br />
    <br />
  </body>
</html>
[hypercms:scriptbegin
}
elseif ('%view%' != 'template')
{
  // include tcpdf lib and language files
  require_once($mgmt_config['abs_path_cms']."function/hypercms_tcpdf.class.php");

  // create new PDF document 
  $pdf = new hcmsPDF($orientation='P', $unit='mm', $format='A4', $unicode=true, $encoding='UTF-8', $diskcache=false, $pdfa=false);

  // set document information
  $pdf->SetCreator(PDF_CREATOR);
  $pdf->SetAuthor("[hyperCMS:textu id='Author' onEdit='hidden']");
  $pdf->SetTitle("[hyperCMS:textu id='Title' onEdit='hidden']");
  $pdf->SetSubject("[hyperCMS:textu id='Subject' onEdit='hidden']");
  $pdf->SetKeywords("[hyperCMS:textu id='Keywords' onEdit='hidden']");

  // remove default header/footer
  $pdf->setPrintHeader(false);
  $pdf->setPrintFooter(false);
  
  // set default monospaced font
  $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);

  // set margins
  $pdf->SetMargins(5, 5, 5);

  // set auto page breaks
  $pdf->SetAutoPageBreak(TRUE, 5);

  // set image scale factor
  $pdf->setImageScale(1);

  // ---------------------------------------------------------
  // Set font
  // dejavusans is a UTF-8 Unicode font, if you only need to
  // print standard ASCII chars, you can use core fonts like
  // helvetica or times to reduce file size.
  $pdf->addFont('helvetica');
  $pdf->addFont('helvetica', 'B');

scriptend]	
  [hyperCMS:componentm id='pages']
[hypercms:scriptbegin

  // . . . . . . . . . . . . . . . . . . . . . . . . . . . . . .
  // add a new page for TOC
  $pdf->addTOCPage();

  // retrieve content of MainText and dont forget to include PDF-ServiceCSSDefinition
  $html = $css."
<div class=\"body\">
  <h1>[hyperCMS:textu id='TOC_title' label='Title of TOC' onEdit='hidden' default='Table of content']</h1>
<div />
";
  // Print text using writeHTMLCell()
  $pdf->writeHTMLCell($w=160, $h='', $x=25, $y=25, $html, $border=0, $ln=1, $fill=0, $reseth=true, $align='', $autopadding=true);
  //$pdf->Ln();

  // add a HTML Table Of Content at first page => don't forget to include PDF-ServiceBookmark where the templates are defined
  $pdf->addHTMLTOC($page = 2, $toc_name = 'TOC', $templates = $bookmark_templates, $correct_align = true, $style = '', $color = array(0,0,0));

  // end of TOC page
  $pdf->endTOCPage();
  // ---------------------------------------------------------
  // Close and output PDF document
  // This method has several options, check the source code documentation for more information.
  echo $pdf->Output('%object%.pdf', 'S');

  //============================================================+
  // END OF FILE
  //============================================================+
  }
scriptend]]]></content>
</template>