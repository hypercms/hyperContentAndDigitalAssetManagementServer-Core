<?php
/*
 * This file is part of
 * hyper Content Management Server - http://www.hypercms.com
 * Copyright (c) by hyper CMS Content Management Solutions GmbH
 *
 * You should have received a copy of the License along with hyperCMS.
 */

// session parameters
require ("include/session.inc.php");
// management configuration
require ("config.inc.php");
// hyperCMS API
require ("function/hypercms_api.inc.php");
// hyperCMS UI
require ("function/hypercms_ui.inc.php");
// language file
require_once ("language/template_help.inc.php");


// input parameters
$site = getrequest ("site", "publicationname");

// ------------------------------ permission section --------------------------------

// check permissions
if ($globalpermission[$site]['template'] != 1 || $globalpermission[$site]['tpl'] != 1 || !valid_publicationname ($site)) killsession ($user);

// check session of user
checkusersession ($user, false);

// --------------------------------- logic section ----------------------------------
?>
<!DOCTYPE html>
<html>
<head>
<title>hyperCMS</title>
<meta http-equiv="Content-Type" content="text/html; charset=<?php echo $lang_codepage[$lang]; ?>">
<link rel="stylesheet" href="<?php echo getthemelocation(); ?>css/main.css">
</head>

<body class="hcmsWorkplaceGeneric">

<!-- top bar -->
<?php echo showtopbar ($subtext0[$lang], $lang); ?>

<!-- content -->
<div id="WorkplaceFrameLayer" class="hcmsWorkplaceFrame">

<table border="0" cellspacing="2" cellpadding="2" width="100%">
  
  <!-- article -->
  <tr align="left" valign="top"> 
    <td colspan="2" class="hcmsRowHead1"><b> <?php echo $subtext30[$lang]; ?></b> 
      <?php echo $subtext31[$lang]; ?><br /> <font size="1"><?php echo $subtext32[$lang]; ?></font></td>
  </tr>
  <tr align="left" valign="top"> 
    <td class="hcmsRowHead2"> <?php echo $subtext30[$lang]; ?></td>
    <td class="hcmsRowData1">[hyperCMS:art...<font size="1">tag</font>...]</td>
  </tr>
  
  <!-- text -->
  <tr align="left" valign="top"> 
    <td class="hcmsRowHead1" colspan="2"><b><?php echo $subtext1[$lang]; ?></b> 
      <?php echo $subtext11[$lang]; ?></td>
  </tr>
  <tr align="left" valign="top"> 
    <td class="hcmsRowHead2"><?php echo $subtext35[$lang]; ?></td>
    <td class="hcmsRowData1">[hyperCMS:textu id='...']</td>
  </tr>
  <tr align="left" valign="top"> 
    <td class="hcmsRowHead2"> <?php echo $subtext3[$lang]; ?></td>
    <td class="hcmsRowData1">[hyperCMS:textf id='...']</td>
  </tr>
  <tr align="left" valign="top"> 
    <td class="hcmsRowHead2"> <?php echo $subtext5[$lang]; ?></td>
    <td class="hcmsRowData1">[hyperCMS:textl id='...' list='...']</td>
  </tr>
  <tr align="left" valign="top"> 
    <td class="hcmsRowHead2"> <?php echo $subtext60[$lang]; ?></td>
    <td class="hcmsRowData1">[hyperCMS:textc id='...' value='...']</td>
  </tr>
  <tr align="left" valign="top"> 
    <td class="hcmsRowHead2"> <?php echo $subtext69[$lang]; ?></td>
    <td class="hcmsRowData1">[hyperCMS:textd id='...']</td>
  </tr>
  <tr align="left" valign="top"> 
    <td class="hcmsRowHead2"><?php echo $subtext70[$lang]; ?></td>
    <td class="hcmsRowData1">format='...'</td>
  </tr> 
  <tr align="left" valign="top"> 
    <td class="hcmsRowHead2"><?php echo $subtext64[$lang]; ?></td>
    <td class="hcmsRowData1">label='...'</td>
  </tr> 
  <tr align="left" valign="top"> 
    <td class="hcmsRowHead2"><?php echo $subtext57[$lang]; ?></td>
    <td class="hcmsRowData1">onEdit='hidden'</td>
  </tr>
  <tr align="left" valign="top"> 
    <td class="hcmsRowHead2"><?php echo $subtext58[$lang]; ?></td>
    <td class="hcmsRowData1">onPublish='hidden'</td>
  </tr>
  <tr align="left" valign="top"> 
    <td class="hcmsRowHead2"><?php echo $subtext51[$lang]; ?></td>
    <td class="hcmsRowData1">width='...'</td>
  </tr>
  <tr align="left" valign="top"> 
    <td class="hcmsRowHead2"><?php echo $subtext52[$lang]; ?></td>
    <td class="hcmsRowData1">height='...'</td>
  </tr>
  <tr align="left" valign="top"> 
    <td class="hcmsRowHead2"><?php echo $subtext53[$lang]; ?></td>
    <td class="hcmsRowData1">toolbar='...'</td>
  </tr>
  <tr align="left" valign="top"> 
    <td class="hcmsRowHead2"><?php echo $subtext54[$lang]; ?></td>
    <td class="hcmsRowData1">constraint='...'</td>
  </tr>
  <tr align="left" valign="top"> 
    <td class="hcmsRowHead2"><?php echo $subtext55[$lang]; ?></td>
    <td class="hcmsRowData1">infotype='meta'</td>
  </tr>
  <tr align="left" valign="top">
    <td class="hcmsRowHead2"><?php echo $subtext59[$lang]; ?></td>
    <td class="hcmsRowData1">default='...'</td>
  </tr>
  <tr align="left" valign="top"> 
    <td class="hcmsRowHead2"> <?php echo $subtext67[$lang]; ?></td>
    <td class="hcmsRowData1">language='...'</td>
  </tr>
  <tr align="left" valign="top"> 
    <td class="hcmsRowHead2"> <?php echo $subtext68[$lang]; ?></td>
    <td class="hcmsRowData1">groups='...'</td>
  </tr>
  <tr align="left" valign="top"> 
    <td class="hcmsRowHead2"> <?php echo $subtext71[$lang]; ?></td>
    <td class="hcmsRowData1">dpi='...'</td>
  </tr>
  <tr align="left" valign="top"> 
    <td class="hcmsRowHead2"> <?php echo $subtext72[$lang]; ?></td>
    <td class="hcmsRowData1">colorspace='...'</td>
  </tr>
  <tr align="left" valign="top"> 
    <td class="hcmsRowHead2"> <?php echo $subtext73[$lang]; ?></td>
    <td class="hcmsRowData1">iccprofile='...'</td>
  </tr>  
  
  <!-- media links -->
  <tr align="left" valign="top"> 
    <td colspan="2" class="hcmsRowHead1"><b><?php echo $subtext16[$lang]; ?></b> 
      <?php echo $subtext11[$lang]; ?></td>
  </tr>
  <tr align="left" valign="top"> 
    <td class="hcmsRowHead2"> <?php echo $subtext2[$lang]; ?></td>
    <td class="hcmsRowData1">[hyperCMS:mediafile id='...']</td>
  </tr>
  <tr align="left" valign="top"> 
    <td class="hcmsRowHead2"> <?php echo $subtext4[$lang]; ?></td>
    <td class="hcmsRowData1">[hyperCMS:mediaalign id='...']</td>
  </tr>
  <tr align="left" valign="top"> 
    <td class="hcmsRowHead2"> <?php echo $subtext6[$lang]; ?></td>
    <td class="hcmsRowData1">[hyperCMS:mediawidth id='...']</td>
  </tr>
  <tr align="left" valign="top"> 
    <td class="hcmsRowHead2"> <?php echo $subtext7[$lang]; ?></td>
    <td class="hcmsRowData1">[hyperCMS:mediaheight id='...']</td>
  </tr>
  <tr align="left" valign="top"> 
    <td class="hcmsRowHead2"> <?php echo $subtext8[$lang]; ?></td>
    <td class="hcmsRowData1">[hyperCMS:mediaalttext id='...']</td>
  </tr>
  <tr align="left" valign="top"> 
    <td class="hcmsRowHead2"><?php echo $subtext64[$lang]; ?></td>
    <td class="hcmsRowData1">label='...'</td>
  </tr>   
  <tr align="left" valign="top"> 
    <td class="hcmsRowHead2"><?php echo $subtext57[$lang]; ?></td>
    <td class="hcmsRowData1">onEdit='hidden'</td>
  </tr>
  <tr align="left" valign="top"> 
    <td class="hcmsRowHead2"><?php echo $subtext58[$lang]; ?></td>
    <td class="hcmsRowData1">onPublish='hidden'</td>
  </tr>
  <tr align="left" valign="top"> 
    <td class="hcmsRowHead2"><?php echo $subtext56[$lang]; ?></td>
    <td class="hcmsRowData1">mediatype='...'</td>
  </tr>
  <tr align="left" valign="top"> 
    <td class="hcmsRowHead2"><?php echo $subtext55[$lang]; ?></td>
    <td class="hcmsRowData1">infotype='meta'</td>
  </tr>
  <tr align="left" valign="top">
    <td class="hcmsRowHead2"><?php echo $subtext63[$lang]; ?></td>
    <td class="hcmsRowData1">thumbnail='yes'</td>
  </tr>
  <tr align="left" valign="top"> 
    <td class="hcmsRowHead2"> <?php echo $subtext67[$lang]; ?></td>
    <td class="hcmsRowData1">language='...'</td>
  </tr>
  <tr align="left" valign="top"> 
    <td class="hcmsRowHead2"> <?php echo $subtext68[$lang]; ?></td>
    <td class="hcmsRowData1">groups='...'</td>
  </tr>
  <tr align="left" valign="top"> 
    <td class="hcmsRowHead2"> <?php echo $subtext71[$lang]; ?></td>
    <td class="hcmsRowData1">dpi='...'</td>
  </tr>
  <tr align="left" valign="top"> 
    <td class="hcmsRowHead2"> <?php echo $subtext72[$lang]; ?></td>
    <td class="hcmsRowData1">colorspace='...'</td>
  </tr>
  <tr align="left" valign="top"> 
    <td class="hcmsRowHead2"> <?php echo $subtext73[$lang]; ?></td>
    <td class="hcmsRowData1">iccprofile='...'</td>
  </tr>  
  
  <!-- page links -->
  <tr align="left" valign="top"> 
    <td colspan="2" class="hcmsRowHead1"><b> <?php echo $subtext9[$lang]; ?></b> 
      <?php echo $subtext11[$lang]; ?></td>
  </tr>
  <tr align="left" valign="top"> 
    <td class="hcmsRowHead2"> <?php echo $subtext12[$lang]; ?></td>
    <td class="hcmsRowData1">[hyperCMS:linkhref id='...']</td>
  </tr>
  <tr align="left" valign="top"> 
    <td class="hcmsRowHead2"> <?php echo $subtext14[$lang]; ?></td>
    <td class="hcmsRowData1">[hyperCMS:linktarget id='...']</td>
  </tr>
  <tr align="left" valign="top"> 
    <td class="hcmsRowHead2"> <?php echo $subtext19[$lang]; ?></td>
    <td class="hcmsRowData1">[hyperCMS:linktext id='...']</td>
  </tr>
  <tr align="left" valign="top"> 
    <td class="hcmsRowHead2"><?php echo $subtext64[$lang]; ?></td>
    <td class="hcmsRowData1">label='...'</td>
  </tr>    
  <tr align="left" valign="top"> 
    <td class="hcmsRowHead2"><?php echo $subtext57[$lang]; ?></td>
    <td class="hcmsRowData1">onEdit='hidden'</td>
  </tr> 
  <tr align="left" valign="top"> 
    <td class="hcmsRowHead2"><?php echo $subtext58[$lang]; ?></td>
    <td class="hcmsRowData1">onPublish='hidden'</td>
  </tr>
  <tr align="left" valign="top"> 
    <td class="hcmsRowHead2"><?php echo $subtext55[$lang]; ?></td>
    <td class="hcmsRowData1">infotype='meta'</td>
  </tr>
  <tr align="left" valign="top"> 
    <td class="hcmsRowHead2"> <?php echo $subtext67[$lang]; ?></td>
    <td class="hcmsRowData1">language='...'</td>
  </tr>
  <tr align="left" valign="top"> 
    <td class="hcmsRowHead2"> <?php echo $subtext68[$lang]; ?></td>
    <td class="hcmsRowData1">groups='...'</td>
  </tr>
  
  <!-- component links --> 
  <tr align="left" valign="top"> 
    <td colspan="2" class="hcmsRowHead1"><b> <?php echo $subtext10[$lang]; ?></b> 
      <?php echo $subtext11[$lang]; ?></td>
  </tr>
  <tr align="left" valign="top"> 
    <td class="hcmsRowHead2"> <?php echo $subtext13[$lang]; ?></td>
    <td class="hcmsRowData1">[hyperCMS:components id='...']</td>
  </tr>
  <tr align="left" valign="top"> 
    <td class="hcmsRowHead2"> <?php echo $subtext15[$lang]; ?></td>
    <td class="hcmsRowData1">[hyperCMS:componentm id='...']</td>
  </tr>
  <tr align="left" valign="top"> 
    <td class="hcmsRowHead2"><?php echo $subtext64[$lang]; ?></td>
    <td class="hcmsRowData1">label='...'</td>
  </tr>    
  <tr align="left" valign="top"> 
    <td class="hcmsRowHead2"><?php echo $subtext57[$lang]; ?></td>
    <td class="hcmsRowData1">onEdit='hidden'</td>
  </tr>
  <tr align="left" valign="top"> 
    <td class="hcmsRowHead2"><?php echo $subtext58[$lang]; ?></td>
    <td class="hcmsRowData1">onPublish='hidden'</td>
  </tr>
  <tr align="left" valign="top"> 
    <td class="hcmsRowHead2"> <?php echo $subtext49[$lang]; ?></td>
    <td class="hcmsRowData1">include='static'</td>
  </tr>
  <tr align="left" valign="top"> 
    <td class="hcmsRowHead2"> <?php echo $subtext50[$lang]; ?></td>
    <td class="hcmsRowData1"> icon='hidden'</td>
  </tr>
  <tr align="left" valign="top"> 
    <td class="hcmsRowHead2"><?php echo $subtext55[$lang]; ?></td>
    <td class="hcmsRowData1">infotype='meta'</td>
  </tr>
  <tr align="left" valign="top">
    <td class="hcmsRowHead2"><?php echo $subtext59[$lang]; ?></td>
    <td class="hcmsRowData1">default='...'</td>
  </tr>  
  <tr align="left" valign="top"> 
    <td class="hcmsRowHead2"> <?php echo $subtext67[$lang]; ?></td>
    <td class="hcmsRowData1">language='...'</td>
  </tr>
  <tr align="left" valign="top"> 
    <td class="hcmsRowHead2"> <?php echo $subtext68[$lang]; ?></td>
    <td class="hcmsRowData1">groups='...'</td>
  </tr>  
  
  <!-- page title -->  
  <tr align="left" valign="top"> 
    <td colspan="2" class="hcmsRowHead1"><b> <?php echo $subtext20[$lang]; ?></b> 
      <?php echo $subtext11[$lang]; ?></td>
  </tr>
  <tr align="left" valign="top"> 
    <td class="hcmsRowHead2"> <?php echo $subtext22[$lang]; ?></td>
    <td class="hcmsRowData1">[hyperCMS:pagetitle]</td>
  </tr>
  <tr align="left" valign="top"> 
    <td class="hcmsRowHead2"><?php echo $subtext64[$lang]; ?></td>
    <td class="hcmsRowData1">label='...'</td>
  </tr>   
  <tr align="left" valign="top"> 
    <td class="hcmsRowHead2"> <?php echo $subtext59[$lang]; ?></td>
    <td class="hcmsRowData1">default='...'</td>
  </tr>  
  <tr align="left" valign="top"> 
    <td class="hcmsRowHead2"> <?php echo $subtext67[$lang]; ?></td>
    <td class="hcmsRowData1">language='...'</td>
  </tr>  
  
  <!-- meta info -->
  <tr align="left" valign="top"> 
    <td colspan="2" class="hcmsRowHead1"><b> <?php echo $subtext18[$lang]; ?></b> 
      <?php echo $subtext11[$lang]; ?></td>
  </tr>
  <tr align="left" valign="top"> 
    <td class="hcmsRowHead2"> <?php echo $subtext21[$lang]; ?></td>
    <td class="hcmsRowData1">[hyperCMS:pageauthor]</td>
  </tr>
  <tr align="left" valign="top"> 
    <td class="hcmsRowHead2"> <?php echo $subtext23[$lang]; ?></td>
    <td class="hcmsRowData1">[hyperCMS:pagekeywords]</td>
  </tr>
  <tr align="left" valign="top"> 
    <td class="hcmsRowHead2"> <?php echo $subtext24[$lang]; ?></td>
    <td class="hcmsRowData1">[hyperCMS:pagedescription]</td>
  </tr>
  <tr align="left" valign="top"> 
    <td class="hcmsRowHead2"> <?php echo $subtext26[$lang]; ?></td>
    <td class="hcmsRowData1">[hyperCMS:pagecontenttype]<br />
      [hyperCMS:compcontenttype content='...'] </td>
  </tr>
  <tr align="left" valign="top"> 
    <td class="hcmsRowHead2"> <?php echo $subtext28[$lang]; ?></td>
    <td class="hcmsRowData1">[hyperCMS:pagelanguage]</td>
  </tr>
  <tr align="left" valign="top"> 
    <td class="hcmsRowHead2"><?php echo $subtext64[$lang]; ?></td>
    <td class="hcmsRowData1">label='...'</td>
  </tr>   
  <tr align="left" valign="top"> 
    <td class="hcmsRowHead2"> <?php echo $subtext59[$lang]; ?></td>
    <td class="hcmsRowData1">default='...'</td>
  </tr>
  <tr align="left" valign="top"> 
    <td class="hcmsRowHead2"> <?php echo $subtext68[$lang]; ?></td>
    <td class="hcmsRowData1">groups='...'</td>
  </tr>  
  
  <!-- language session setting --> 
  <tr align="left" valign="top"> 
    <td colspan="2" class="hcmsRowHead1"><b> <?php echo $subtext65[$lang]; ?></td>
  </tr>
  <tr align="left" valign="top"> 
    <td class="hcmsRowHead2"> <?php echo $subtext66[$lang]; ?></td>
    <td class="hcmsRowData1">[hyperCMS:language name='...' list='...']</td>
  </tr> 
  <tr align="left" valign="top"> 
    <td class="hcmsRowHead2"> <?php echo $subtext64[$lang]; ?></td>
    <td class="hcmsRowData1">label='...'</td>
  </tr>
  <tr align="left" valign="top"> 
    <td class="hcmsRowHead2"> <?php echo $subtext59[$lang]; ?></td>
    <td class="hcmsRowData1">default='...'</td>
  </tr>
  <tr align="left" valign="top"> 
    <td class="hcmsRowHead2"> <?php echo $subtext67[$lang]; ?></td>
    <td class="hcmsRowData1">language='...'</td>
  </tr>
  
  <!-- page tracking -->      
  <tr align="left" valign="top"> 
    <td colspan="2" class="hcmsRowHead1"><b> <?php echo $subtext36[$lang]; ?></b> 
      <?php echo $subtext11[$lang]; ?><br /> <font size="1"> <?php echo $subtext37[$lang]; ?></font></td>
  </tr>
  <tr align="left" valign="top"> 
    <td class="hcmsRowHead2"> <?php echo $subtext38[$lang]; ?></td>
    <td class="hcmsRowData1">[hyperCMS:pagetracking]</td>
  </tr>
  <tr align="left" valign="top"> 
    <td class="hcmsRowHead2"> <?php echo $subtext59[$lang]; ?></td>
    <td class="hcmsRowData1">default='...'</td>
  </tr>
  <tr align="left" valign="top"> 
    <td class="hcmsRowHead2"> <?php echo $subtext68[$lang]; ?></td>
    <td class="hcmsRowData1">groups='...'</td>
  </tr>    
  
  <!-- script -->
  <tr align="left" valign="top"> 
    <td colspan="2" class="hcmsRowHead1"><b> <?php echo $subtext45[$lang]; ?></b> 
      <?php echo $subtext11[$lang]; ?></td>
  </tr>
  <tr align="left" valign="top"> 
    <td class="hcmsRowHead2"> <?php echo $subtext46[$lang]; ?></td>
    <td class="hcmsRowData1">[hyperCMS:scriptbegin ... scriptend]</td>
  </tr>
  
  <!-- dbconnect -->
  <tr align="left" valign="top"> 
    <td colspan="2" class="hcmsRowHead1"><b> <?php echo $subtext39[$lang]; ?></b> 
      <?php echo $subtext11[$lang]; ?><br /> <font size="1"> <?php echo $subtext40[$lang]; ?></font></td>
  </tr>
  <tr align="left" valign="top"> 
    <td class="hcmsRowHead2"> <?php echo $subtext41[$lang]; ?></td>
    <td class="hcmsRowData1">[hyperCMS:dbconnect file='...<font size="1"> 
      <?php echo $subtext2[$lang]; ?></font> ...']</td>
  </tr>
  
  <!-- workflow -->
  <tr align="left" valign="top"> 
    <td colspan="2" class="hcmsRowHead1"><b> <?php echo $subtext42[$lang]; ?></b> 
      <?php echo $subtext11[$lang]; ?><br /> <font size="1"> <?php echo $subtext43[$lang]; ?></font></td>
  </tr>
  <tr align="left" valign="top"> 
    <td class="hcmsRowHead2"> <?php echo $subtext44[$lang]; ?></td>
    <td class="hcmsRowData1">[hyperCMS:workflow name='...']</td>
  </tr>
  
  <!-- stylesheet -->
  <tr align="left" valign="top"> 
    <td colspan="2" class="hcmsRowHead1"><b> <?php echo $subtext47[$lang]; ?></b> 
      <?php echo $subtext11[$lang]; ?></td>
  </tr>
  <tr align="left" valign="top"> 
    <td class="hcmsRowHead2"> <?php echo $subtext48[$lang]; ?></td>
    <td class="hcmsRowData1">[hyperCMS:compstylesheet file='...'] </td>
  </tr>
  
  <!-- template and file include -->
  <tr align="left" valign="top"> 
    <td colspan="2" class="hcmsRowHead1"><b> <?php echo $subtext25[$lang]; ?> 
      </b> <?php echo $subtext11[$lang]; ?> </td>
  </tr>
  <tr align="left" valign="top"> 
    <td class="hcmsRowHead2"> <?php echo $subtext27[$lang]; ?></td>
    <td nowrap class="hcmsRowData1">[hyperCMS:tplinclude file='...<font size="1"><?php echo $subtext2[$lang]; ?> 
      ...']</font></td>
  </tr>
  <tr align="left" valign="top"> 
    <td class="hcmsRowHead2"> <?php echo $subtext29[$lang]; ?></td>
    <td nowrap class="hcmsRowData1">[hyperCMS:fileinclude file='...<font size="1"><?php echo $subtext2[$lang]; ?> 
      ...']</font></td>
  </tr>
  
  <!-- view -->
  <tr align="left" valign="top"> 
    <td colspan="2" class="hcmsRowHead1"><b> <?php echo $subtext61[$lang]; ?> 
      </b> <?php echo $subtext11[$lang]; ?> </td>
  </tr>
  <tr align="left" valign="top"> 
    <td class="hcmsRowHead2"> <?php echo $subtext62[$lang]; ?></td>
    <td nowrap class="hcmsRowData1">[hyperCMS:objectview name='...']</font></td>
  </tr>  
</table>
<br />

<table border="0" cellspacing="0" cellpadding="3" width="100%">
  <tr >
    <td align="left" valign="top" class="hcmsHeadline"><?php echo $subtext33[$lang]; ?>:</td>
    <td align="left" valign="top"><?php echo $subtext34[$lang]; ?></td>
  </tr>
</table>

</div>

</body>
</html>