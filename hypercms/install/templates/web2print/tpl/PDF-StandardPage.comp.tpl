<?xml version="1.0" encoding="utf-8" ?>
<template>
<name>PDF-StandardPage</name>
<user>hypercms</user>
<category>comp</category>
<extension>page</extension>
<application>htm</application>
<content><![CDATA[[hyperCMS:tplinclude file='PDF-ServiceCSSDefinition.inc.tpl']
[hypercms:scriptbegin
if('%view%' == 'cmsview')
{
scriptend]
<!DOCTYPE html> 
<html>
	<head>
    <title>Standard Page</title>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
		<link rel="stylesheet" hypercms_href="[hyperCMS:scriptbegin echo getthemelocation(); scriptend]css/main.css" />
	</head>
	<body class="hcmsWorkplaceGeneric">
		<br />
[hypercms:scriptbegin
}
if('%view%' == 'cmsview' || '%view%' == 'preview')
{
//A4 by 72dpi is 595x842 => 2.5cm padding (0.5 = 14px by 72dpi) = 70px
scriptend]
		<div style="width:595px; height:842px;  background-color:#FFF; margin:50px auto 20px auto; -moz-box-shadow:1px 3px 5px #555; -webkit-box-shadow:1px 3px 5px #555; box-shadow:1px 3px 5px #555; overflow: hidden">
			<div style="width:455px; height:702px; margin:70px; overflow: hidden" >
				[hypercms:scriptbegin echo $css scriptend]
				<div class="body">
				[hyperCMS:textf id='MainText' dpi='300' colorspace='Gray' width='567' height='814']
				</div>
			</div>
		</div>
[hypercms:scriptbegin
}
if('%view%' == 'cmsview')
{
scriptend]
		<br />
	</body>
</html>
[hypercms:scriptbegin
}
if (isset($pdf) && is_object($pdf))
{
	// Add a page
	// This method has several options, check the source code documentation for more information.
	$pdf->AddPage();
	// retrieve content of MainText and dont forget to include PDF-ServiceCSSDefinition
	$html = $css."
<div class=\"body\">
	[hyperCMS:textf id='MainText' colorspace='Gray' onEdit='hidden']
<div />
";
	// Print text using writeHTMLCell()
	$pdf->writeHTMLCell($w=160, $h=247, $x=25, $y=25, $html, $border=0, $ln=1, $fill=0, $reset=true, $align='', $autopadding=true);
	
	//bookmarking for table of content => don't forget to include PDF-ServiceBookmark in the Page Container
	bookmark($pdf, "%container%", $text_id = "MainText");
	
	//calculate actual page no
	$pageOffset = 1;
	$pageNo =  $pdf->PageNo() + $pageOffset;
	//display pageNo
	$footer = '<span style="font-family:verdana,geneva,sans-serif; font-size: 10px;" >'.$pageNo.'</span>';
	$pdf->writeHTMLCell($w=10, $h=10, $x=195, $y=282, $footer, $border=0, $ln=1, $fill=0, $reset=true, $align='', $autopadding=true);
	
}
scriptend]]]></content>
</template>