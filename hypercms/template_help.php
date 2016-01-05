<?php
/*
 * This file is part of
 * hyper Content Management Server - http://www.hypercms.com
 * Copyright (c) by hyper CMS Content Management Solutions GmbH
 *
 * You should have received a copy of the License along with hyperCMS.
 */

// session
define ("SESSION", "create");
// management configuration
require ("config.inc.php");
// hyperCMS API
require ("function/hypercms_api.inc.php");


// input parameters
$site = getrequest ("site", "publicationname");

// ------------------------------ permission section --------------------------------

// check permissions
if (!checkglobalpermission ($site, 'template') || !checkglobalpermission ($site, 'tpl') || !valid_publicationname ($site)) killsession ($user);

// check session of user
checkusersession ($user, false);

// --------------------------------- logic section ----------------------------------
?>
<!DOCTYPE html>
<html>
<head>
<title>hyperCMS</title>
<meta charset="<?php echo getcodepage ($lang); ?>" />
<link rel="stylesheet" href="<?php echo getthemelocation(); ?>css/main.css" />
<script src="javascript/main.js" type="text/javascript"></script>
</head>

<body class="hcmsWorkplaceGeneric">

<!-- top bar -->
<?php
$menu_array = array ($hcms_lang['list-of-hypercms-tags'][$lang] => 'onclick="hcms_showInfo(\'Layer1\',0); hcms_hideInfo(\'Layer2\');"', "hyperCMS API Reference" => 'onclick="hcms_showInfo(\'Layer2\',0); hcms_hideInfo(\'Layer1\');"');

echo showtopmenubar ($hcms_lang['help'][$lang], $menu_array, $lang);
?>

<!-- content -->
<div id="Layer1" class="hcmsWorkplaceFrame">
  <table border="0" cellspacing="2" cellpadding="2" width="100%">
    
    <!-- article -->
    <tr align="left" valign="top"> 
      <td colspan="2" class="hcmsRowHead1"><b> <?php echo getescapedtext ($hcms_lang['article'][$lang]); ?></b> 
        <?php echo getescapedtext ($hcms_lang['tag-prefix'][$lang]); ?><br /> <font size="1"><?php echo getescapedtext ($hcms_lang['can-be-used-as-prefix-for-all-text-media-link-and-component-tags-excl'][$lang]); ?></font></td>
    </tr>
    <tr align="left" valign="top"> 
      <td class="hcmsRowHead2"> <?php echo getescapedtext ($hcms_lang['article'][$lang]); ?></td>
      <td class="hcmsRowData1">[hyperCMS:art...<font size="1">tag</font>...]</td>
    </tr>
    
    <!-- text -->
    <tr align="left" valign="top"> 
      <td class="hcmsRowHead1" colspan="2"><b><?php echo getescapedtext ($hcms_lang['text'][$lang]); ?></b> 
        <?php echo getescapedtext ($hcms_lang['tag-set'][$lang]); ?></td>
    </tr>
    <tr align="left" valign="top"> 
      <td class="hcmsRowHead2"><?php echo getescapedtext ($hcms_lang['unformatted-text'][$lang]); ?></td>
      <td class="hcmsRowData1">[hyperCMS:textu id='...']</td>
    </tr>
    <tr align="left" valign="top"> 
      <td class="hcmsRowHead2"> <?php echo getescapedtext ($hcms_lang['formatted-text'][$lang]); ?></td>
      <td class="hcmsRowData1">[hyperCMS:textf id='...']</td>
    </tr>
    <tr align="left" valign="top"> 
      <td class="hcmsRowHead2"> <?php echo getescapedtext ($hcms_lang['text-option-from-text-list'][$lang]); ?></td>
      <td class="hcmsRowData1">[hyperCMS:textl id='...' list='...']</td>
    </tr>
    <tr align="left" valign="top"> 
      <td class="hcmsRowHead2"> <?php echo getescapedtext ($hcms_lang['checkbox'][$lang]); ?></td>
      <td class="hcmsRowData1">[hyperCMS:textc id='...' value='...']</td>
    </tr>
    <tr align="left" valign="top"> 
      <td class="hcmsRowHead2"> <?php echo getescapedtext ($hcms_lang['date'][$lang]).", ".getescapedtext ($hcms_lang['date-format-optional'][$lang]); ?></td>
      <td class="hcmsRowData1">[hyperCMS:textd id='...' format='%Y-%m-%d']</td>
    </tr>
    <tr align="left" valign="top"> 
      <td class="hcmsRowHead2"> <?php echo getescapedtext ($hcms_lang['keywords-with-optional-mandatory-or-open-list'][$lang]); ?></td>
      <td class="hcmsRowData1">[hyperCMS:textk id='...' list='...' file='...' onlylist=1/0]</td>
    </tr>
    <tr align="left" valign="top"> 
      <td class="hcmsRowHead2"><?php echo getescapedtext ($hcms_lang['display-name-optional'][$lang]); ?></td>
      <td class="hcmsRowData1">label='...'</td>
    </tr> 
    <tr align="left" valign="top"> 
      <td class="hcmsRowHead2"><?php echo getescapedtext ($hcms_lang['content-cannot-be-edited-optional'][$lang]); ?></td>
      <td class="hcmsRowData1">onEdit='hidden'</td>
    </tr>
    <tr align="left" valign="top"> 
      <td class="hcmsRowHead2"><?php echo getescapedtext ($hcms_lang['hide-content-optional'][$lang]); ?></td>
      <td class="hcmsRowData1">onPublish='hidden'</td>
    </tr>
    <tr align="left" valign="top"> 
      <td class="hcmsRowHead2"><?php echo getescapedtext ($hcms_lang['width-of-editorfield-in-pixel-optional'][$lang]); ?></td>
      <td class="hcmsRowData1">width='...'</td>
    </tr>
    <tr align="left" valign="top"> 
      <td class="hcmsRowHead2"><?php echo getescapedtext ($hcms_lang['height-of-editorfield-in-pixel-optional'][$lang]); ?></td>
      <td class="hcmsRowData1">height='...'</td>
    </tr>
    <tr align="left" valign="top"> 
      <td class="hcmsRowHead2"><?php echo getescapedtext ($hcms_lang['toolbar-selection-for-richtext-editor-optional'][$lang]); ?></td>
      <td class="hcmsRowData1">toolbar='...'</td>
    </tr>
    <tr align="left" valign="top"> 
      <td class="hcmsRowHead2"><?php echo getescapedtext ($hcms_lang['constraints-definitions-for-non-formatted-text-optional'][$lang]); ?></td>
      <td class="hcmsRowData1">constraint='...'</td>
    </tr>
    <tr align="left" valign="top"> 
      <td class="hcmsRowHead2"><?php echo getescapedtext ($hcms_lang['meta-information-type-optional'][$lang]); ?></td>
      <td class="hcmsRowData1">infotype='meta'</td>
    </tr>
    <tr align="left" valign="top">
      <td class="hcmsRowHead2"><?php echo getescapedtext ($hcms_lang['default-value-optional'][$lang]); ?></td>
      <td class="hcmsRowData1">default='...'</td>
    </tr>
     <tr align="left" valign="top">
      <td class="hcmsRowHead2"><?php echo getescapedtext ($hcms_lang['prefix-and-suffix-will-be-added-to-content-if-it-is-not-empty'][$lang]); ?></td>
      <td class="hcmsRowData1">prefix='...' suffix='...'</td>
    </tr>
    <tr align="left" valign="top"> 
      <td class="hcmsRowHead2"> <?php echo getescapedtext ($hcms_lang['valid-language-value-optional'][$lang]); ?></td>
      <td class="hcmsRowData1">language='...'</td>
    </tr>
    <tr align="left" valign="top"> 
      <td class="hcmsRowHead2"> <?php echo getescapedtext ($hcms_lang['editwrite-permission-for-certain-user-groups-optional'][$lang]); ?></td>
      <td class="hcmsRowData1">groups='...'</td>
    </tr>
    <tr align="left" valign="top"> 
      <td class="hcmsRowHead2"> <?php echo getescapedtext ($hcms_lang['dpi-value-to-autoscale-images-optional'][$lang]); ?></td>
      <td class="hcmsRowData1">dpi='...'</td>
    </tr>
    <tr align="left" valign="top"> 
      <td class="hcmsRowHead2"> <?php echo getescapedtext ($hcms_lang['autoconvert-images-to-the-given-colorspace-'][$lang]); ?></td>
      <td class="hcmsRowData1">colorspace='...'</td>
    </tr>
    <tr align="left" valign="top"> 
      <td class="hcmsRowHead2"> <?php echo getescapedtext ($hcms_lang['autoconvert-images-to-the-given-icc-profile-'][$lang]); ?></td>
      <td class="hcmsRowData1">iccprofile='...'</td>
    </tr>  
    
    <!-- media links -->
    <tr align="left" valign="top"> 
      <td colspan="2" class="hcmsRowHead1"><b><?php echo getescapedtext ($hcms_lang['media'][$lang]); ?></b> 
        <?php echo getescapedtext ($hcms_lang['tag-set'][$lang]); ?></td>
    </tr>
    <tr align="left" valign="top"> 
      <td class="hcmsRowHead2"> <?php echo getescapedtext ($hcms_lang['file'][$lang]); ?></td>
      <td class="hcmsRowData1">[hyperCMS:mediafile id='...']</td>
    </tr>
    <tr align="left" valign="top"> 
      <td class="hcmsRowHead2"> <?php echo getescapedtext ($hcms_lang['alignment'][$lang]); ?></td>
      <td class="hcmsRowData1">[hyperCMS:mediaalign id='...']</td>
    </tr>
    <tr align="left" valign="top"> 
      <td class="hcmsRowHead2"> <?php echo getescapedtext ($hcms_lang['width'][$lang]); ?></td>
      <td class="hcmsRowData1">[hyperCMS:mediawidth id='...']</td>
    </tr>
    <tr align="left" valign="top"> 
      <td class="hcmsRowHead2"> <?php echo getescapedtext ($hcms_lang['height'][$lang]); ?></td>
      <td class="hcmsRowData1">[hyperCMS:mediaheight id='...']</td>
    </tr>
    <tr align="left" valign="top"> 
      <td class="hcmsRowHead2"> <?php echo getescapedtext ($hcms_lang['alternative-text'][$lang]); ?></td>
      <td class="hcmsRowData1">[hyperCMS:mediaalttext id='...']</td>
    </tr>
    <tr align="left" valign="top"> 
      <td class="hcmsRowHead2"><?php echo getescapedtext ($hcms_lang['display-name-optional'][$lang]); ?></td>
      <td class="hcmsRowData1">label='...'</td>
    </tr>   
    <tr align="left" valign="top"> 
      <td class="hcmsRowHead2"><?php echo getescapedtext ($hcms_lang['content-cannot-be-edited-optional'][$lang]); ?></td>
      <td class="hcmsRowData1">onEdit='hidden'</td>
    </tr>
    <tr align="left" valign="top"> 
      <td class="hcmsRowHead2"><?php echo getescapedtext ($hcms_lang['hide-content-optional'][$lang]); ?></td>
      <td class="hcmsRowData1">onPublish='hidden'</td>
    </tr>
    <tr align="left" valign="top"> 
      <td class="hcmsRowHead2"><?php echo getescapedtext ($hcms_lang['media-type-'][$lang]); ?></td>
      <td class="hcmsRowData1">mediatype='...'</td>
    </tr>
    <tr align="left" valign="top"> 
      <td class="hcmsRowHead2"><?php echo getescapedtext ($hcms_lang['meta-information-type-optional'][$lang]); ?></td>
      <td class="hcmsRowData1">infotype='meta'</td>
    </tr>
    <tr align="left" valign="top"> 
      <td class="hcmsRowHead2"><?php echo getescapedtext ($hcms_lang['path-type-optional'][$lang]); ?></td>
      <td class="hcmsRowData1">pathtype='...'</td>
    </tr>
    <tr align="left" valign="top">
      <td class="hcmsRowHead2"><?php echo getescapedtext ($hcms_lang['use-thumbnail-image'][$lang]); ?></td>
      <td class="hcmsRowData1">thumbnail='yes'</td>
    </tr>
    <tr align="left" valign="top"> 
      <td class="hcmsRowHead2"> <?php echo getescapedtext ($hcms_lang['valid-language-value-optional'][$lang]); ?></td>
      <td class="hcmsRowData1">language='...'</td>
    </tr>
    <tr align="left" valign="top"> 
      <td class="hcmsRowHead2"> <?php echo getescapedtext ($hcms_lang['editwrite-permission-for-certain-user-groups-optional'][$lang]); ?></td>
      <td class="hcmsRowData1">groups='...'</td>
    </tr>
    <tr align="left" valign="top"> 
      <td class="hcmsRowHead2"> <?php echo getescapedtext ($hcms_lang['dpi-value-to-autoscale-images-optional'][$lang]); ?></td>
      <td class="hcmsRowData1">dpi='...'</td>
    </tr>
    <tr align="left" valign="top"> 
      <td class="hcmsRowHead2"> <?php echo getescapedtext ($hcms_lang['autoconvert-images-to-the-given-colorspace-'][$lang]); ?></td>
      <td class="hcmsRowData1">colorspace='...'</td>
    </tr>
    <tr align="left" valign="top"> 
      <td class="hcmsRowHead2"> <?php echo getescapedtext ($hcms_lang['autoconvert-images-to-the-given-icc-profile-'][$lang]); ?></td>
      <td class="hcmsRowData1">iccprofile='...'</td>
    </tr>  
    
    <!-- page links -->
    <tr align="left" valign="top"> 
      <td colspan="2" class="hcmsRowHead1"><b> <?php echo getescapedtext ($hcms_lang['link'][$lang]); ?></b> 
        <?php echo getescapedtext ($hcms_lang['tag-set'][$lang]); ?></td>
    </tr>
    <tr align="left" valign="top"> 
      <td class="hcmsRowHead2"> <?php echo getescapedtext ($hcms_lang['hyper-reference'][$lang]); ?></td>
      <td class="hcmsRowData1">[hyperCMS:linkhref id='...']</td>
    </tr>
    <tr align="left" valign="top"> 
      <td class="hcmsRowHead2"> <?php echo getescapedtext ($hcms_lang['frame-target'][$lang]); ?></td>
      <td class="hcmsRowData1">[hyperCMS:linktarget id='...']</td>
    </tr>
    <tr align="left" valign="top"> 
      <td class="hcmsRowHead2"> <?php echo getescapedtext ($hcms_lang['link-text'][$lang]); ?></td>
      <td class="hcmsRowData1">[hyperCMS:linktext id='...']</td>
    </tr>
    <tr align="left" valign="top"> 
      <td class="hcmsRowHead2"><?php echo getescapedtext ($hcms_lang['display-name-optional'][$lang]); ?></td>
      <td class="hcmsRowData1">label='...'</td>
    </tr>    
    <tr align="left" valign="top"> 
      <td class="hcmsRowHead2"><?php echo getescapedtext ($hcms_lang['content-cannot-be-edited-optional'][$lang]); ?></td>
      <td class="hcmsRowData1">onEdit='hidden'</td>
    </tr> 
    <tr align="left" valign="top"> 
      <td class="hcmsRowHead2"><?php echo getescapedtext ($hcms_lang['hide-content-optional'][$lang]); ?></td>
      <td class="hcmsRowData1">onPublish='hidden'</td>
    </tr>
    <tr align="left" valign="top"> 
      <td class="hcmsRowHead2"><?php echo getescapedtext ($hcms_lang['meta-information-type-optional'][$lang]); ?></td>
      <td class="hcmsRowData1">infotype='meta'</td>
    </tr>
    <tr align="left" valign="top"> 
      <td class="hcmsRowHead2"> <?php echo getescapedtext ($hcms_lang['valid-language-value-optional'][$lang]); ?></td>
      <td class="hcmsRowData1">language='...'</td>
    </tr>
    <tr align="left" valign="top"> 
      <td class="hcmsRowHead2"> <?php echo getescapedtext ($hcms_lang['editwrite-permission-for-certain-user-groups-optional'][$lang]); ?></td>
      <td class="hcmsRowData1">groups='...'</td>
    </tr>
    
    <!-- component links --> 
    <tr align="left" valign="top"> 
      <td colspan="2" class="hcmsRowHead1"><b> <?php echo getescapedtext ($hcms_lang['component'][$lang]); ?></b> 
        <?php echo getescapedtext ($hcms_lang['tag-set'][$lang]); ?></td>
    </tr>
    <tr align="left" valign="top"> 
      <td class="hcmsRowHead2"> <?php echo getescapedtext ($hcms_lang['single-component'][$lang]); ?></td>
      <td class="hcmsRowData1">[hyperCMS:components id='...']</td>
    </tr>
    <tr align="left" valign="top"> 
      <td class="hcmsRowHead2"> <?php echo getescapedtext ($hcms_lang['multi-component'][$lang]); ?></td>
      <td class="hcmsRowData1">[hyperCMS:componentm id='...']</td>
    </tr>
    <tr align="left" valign="top"> 
      <td class="hcmsRowHead2"><?php echo getescapedtext ($hcms_lang['display-name-optional'][$lang]); ?></td>
      <td class="hcmsRowData1">label='...'</td>
    </tr>    
    <tr align="left" valign="top"> 
      <td class="hcmsRowHead2"><?php echo getescapedtext ($hcms_lang['content-cannot-be-edited-optional'][$lang]); ?></td>
      <td class="hcmsRowData1">onEdit='hidden'</td>
    </tr>
    <tr align="left" valign="top"> 
      <td class="hcmsRowHead2"><?php echo getescapedtext ($hcms_lang['hide-content-optional'][$lang]); ?></td>
      <td class="hcmsRowData1">onPublish='hidden'</td>
    </tr>
    <tr align="left" valign="top"> 
      <td class="hcmsRowHead2"> <?php echo getescapedtext ($hcms_lang['static-include-optional'][$lang]); ?></td>
      <td class="hcmsRowData1">include='static'</td>
    </tr>
    <tr align="left" valign="top"> 
      <td class="hcmsRowHead2"> <?php echo getescapedtext ($hcms_lang['hide-icons-on-edit-optional'][$lang]); ?></td>
      <td class="hcmsRowData1"> icon='hidden'</td>
    </tr>
    <tr align="left" valign="top"> 
      <td class="hcmsRowHead2"><?php echo getescapedtext ($hcms_lang['meta-information-type-optional'][$lang]); ?></td>
      <td class="hcmsRowData1">infotype='meta'</td>
    </tr>
    <tr align="left" valign="top">
      <td class="hcmsRowHead2"><?php echo getescapedtext ($hcms_lang['default-value-optional'][$lang]); ?></td>
      <td class="hcmsRowData1">default='...'</td>
    </tr>  
    <tr align="left" valign="top"> 
      <td class="hcmsRowHead2"> <?php echo getescapedtext ($hcms_lang['valid-language-value-optional'][$lang]); ?></td>
      <td class="hcmsRowData1">language='...'</td>
    </tr>
    <tr align="left" valign="top"> 
      <td class="hcmsRowHead2"> <?php echo getescapedtext ($hcms_lang['editwrite-permission-for-certain-user-groups-optional'][$lang]); ?></td>
      <td class="hcmsRowData1">groups='...'</td>
    </tr>  
    
    <!-- page title -->  
    <tr align="left" valign="top"> 
      <td colspan="2" class="hcmsRowHead1"><b> <?php echo getescapedtext ($hcms_lang['title'][$lang]); ?></b> 
        <?php echo getescapedtext ($hcms_lang['tag-set'][$lang]); ?></td>
    </tr>
    <tr align="left" valign="top"> 
      <td class="hcmsRowHead2"> <?php echo getescapedtext ($hcms_lang['page-title'][$lang]); ?></td>
      <td class="hcmsRowData1">[hyperCMS:pagetitle]</td>
    </tr>
    <tr align="left" valign="top"> 
      <td class="hcmsRowHead2"><?php echo getescapedtext ($hcms_lang['display-name-optional'][$lang]); ?></td>
      <td class="hcmsRowData1">label='...'</td>
    </tr>   
    <tr align="left" valign="top"> 
      <td class="hcmsRowHead2"> <?php echo getescapedtext ($hcms_lang['default-value-optional'][$lang]); ?></td>
      <td class="hcmsRowData1">default='...'</td>
    </tr>  
    <tr align="left" valign="top"> 
      <td class="hcmsRowHead2"> <?php echo getescapedtext ($hcms_lang['valid-language-value-optional'][$lang]); ?></td>
      <td class="hcmsRowData1">language='...'</td>
    </tr>  
    
    <!-- meta info -->
    <tr align="left" valign="top"> 
      <td colspan="2" class="hcmsRowHead1"><b> <?php echo getescapedtext ($hcms_lang['meta-information'][$lang]); ?></b> 
        <?php echo getescapedtext ($hcms_lang['tag-set'][$lang]); ?></td>
    </tr>
    <tr align="left" valign="top"> 
      <td class="hcmsRowHead2"> <?php echo getescapedtext ($hcms_lang['author'][$lang]); ?></td>
      <td class="hcmsRowData1">[hyperCMS:pageauthor]</td>
    </tr>
    <tr align="left" valign="top"> 
      <td class="hcmsRowHead2"> <?php echo getescapedtext ($hcms_lang['keywords'][$lang]); ?></td>
      <td class="hcmsRowData1">[hyperCMS:pagekeywords]</td>
    </tr>
    <tr align="left" valign="top"> 
      <td class="hcmsRowHead2"> <?php echo getescapedtext ($hcms_lang['description'][$lang]); ?></td>
      <td class="hcmsRowData1">[hyperCMS:pagedescription]</td>
    </tr>
    <tr align="left" valign="top"> 
      <td class="hcmsRowHead2"> <?php echo getescapedtext ($hcms_lang['content-type'][$lang]); ?></td>
      <td class="hcmsRowData1">[hyperCMS:pagecontenttype]<br />
        [hyperCMS:compcontenttype content='...'] </td>
    </tr>
    <tr align="left" valign="top"> 
      <td class="hcmsRowHead2"> <?php echo getescapedtext ($hcms_lang['language'][$lang]); ?></td>
      <td class="hcmsRowData1">[hyperCMS:pagelanguage]</td>
    </tr>
    <tr align="left" valign="top"> 
      <td class="hcmsRowHead2"><?php echo getescapedtext ($hcms_lang['display-name-optional'][$lang]); ?></td>
      <td class="hcmsRowData1">label='...'</td>
    </tr>   
    <tr align="left" valign="top"> 
      <td class="hcmsRowHead2"> <?php echo getescapedtext ($hcms_lang['default-value-optional'][$lang]); ?></td>
      <td class="hcmsRowData1">default='...'</td>
    </tr>
    <tr align="left" valign="top"> 
      <td class="hcmsRowHead2"> <?php echo getescapedtext ($hcms_lang['editwrite-permission-for-certain-user-groups-optional'][$lang]); ?></td>
      <td class="hcmsRowData1">groups='...'</td>
    </tr>  
    
    <!-- language session setting --> 
    <tr align="left" valign="top"> 
      <td colspan="2" class="hcmsRowHead1"><b> <?php echo getescapedtext ($hcms_lang['language-session-setting'][$lang]); ?></td>
    </tr>
    <tr align="left" valign="top"> 
      <td class="hcmsRowHead2"> <?php echo getescapedtext ($hcms_lang['session-name-and-values'][$lang]); ?></td>
      <td class="hcmsRowData1">[hyperCMS:language name='...' list='...']</td>
    </tr> 
    <tr align="left" valign="top"> 
      <td class="hcmsRowHead2"> <?php echo getescapedtext ($hcms_lang['display-name-optional'][$lang]); ?></td>
      <td class="hcmsRowData1">label='...'</td>
    </tr>
    <tr align="left" valign="top"> 
      <td class="hcmsRowHead2"> <?php echo getescapedtext ($hcms_lang['default-value-optional'][$lang]); ?></td>
      <td class="hcmsRowData1">default='...'</td>
    </tr>
    <tr align="left" valign="top"> 
      <td class="hcmsRowHead2"> <?php echo getescapedtext ($hcms_lang['valid-language-value-optional'][$lang]); ?></td>
      <td class="hcmsRowData1">language='...'</td>
    </tr>
    
    <!-- page tracking -->      
    <tr align="left" valign="top"> 
      <td colspan="2" class="hcmsRowHead1"><b> <?php echo getescapedtext ($hcms_lang['personalization'][$lang]); ?></b> 
        <?php echo getescapedtext ($hcms_lang['tag-set'][$lang]); ?><br /> <font size="1"> <?php echo getescapedtext ($hcms_lang['content-can-be-personalized-for-customers-based-on-the-customer-profile-of-a-component'][$lang]); ?></font></td>
    </tr>
    <tr align="left" valign="top"> 
      <td class="hcmsRowHead2"> <?php echo getescapedtext ($hcms_lang['customer-tracking'][$lang]); ?></td>
      <td class="hcmsRowData1">[hyperCMS:pagetracking]</td>
    </tr>
    <tr align="left" valign="top"> 
      <td class="hcmsRowHead2"> <?php echo getescapedtext ($hcms_lang['default-value-optional'][$lang]); ?></td>
      <td class="hcmsRowData1">default='...'</td>
    </tr>
    <tr align="left" valign="top"> 
      <td class="hcmsRowHead2"> <?php echo getescapedtext ($hcms_lang['editwrite-permission-for-certain-user-groups-optional'][$lang]); ?></td>
      <td class="hcmsRowData1">groups='...'</td>
    </tr>    
    
    <!-- script -->
    <tr align="left" valign="top"> 
      <td colspan="2" class="hcmsRowHead1"><b> <?php echo getescapedtext ($hcms_lang['script'][$lang]); ?></b> 
        <?php echo getescapedtext ($hcms_lang['tag-set'][$lang]); ?></td>
    </tr>
    <tr align="left" valign="top"> 
      <td class="hcmsRowHead2"> <?php echo getescapedtext ($hcms_lang['template-script'][$lang]); ?></td>
      <td class="hcmsRowData1">[hyperCMS:scriptbegin ... scriptend]</td>
    </tr>
    
    <!-- dbconnect -->
    <tr align="left" valign="top"> 
      <td colspan="2" class="hcmsRowHead1"><b> <?php echo getescapedtext ($hcms_lang['database-connectivity'][$lang]); ?></b> 
        <?php echo getescapedtext ($hcms_lang['tag-set'][$lang]); ?><br /> <font size="1"> <?php echo getescapedtext ($hcms_lang['db-connect-file-has-to-be-defined'][$lang]); ?></font></td>
    </tr>
    <tr align="left" valign="top"> 
      <td class="hcmsRowHead2"> <?php echo getescapedtext ($hcms_lang['db-connectivity'][$lang]); ?></td>
      <td class="hcmsRowData1">[hyperCMS:dbconnect file='...<font size="1"> 
        <?php echo getescapedtext ($hcms_lang['file'][$lang]); ?></font> ...']</td>
    </tr>
    
    <!-- workflow -->
    <tr align="left" valign="top"> 
      <td colspan="2" class="hcmsRowHead1"><b> <?php echo getescapedtext ($hcms_lang['workflow'][$lang]); ?></b> 
        <?php echo getescapedtext ($hcms_lang['tag-set'][$lang]); ?><br /> <font size="1"> <?php echo getescapedtext ($hcms_lang['workflow-must-be-defined'][$lang]); ?></font></td>
    </tr>
    <tr align="left" valign="top"> 
      <td class="hcmsRowHead2"> <?php echo getescapedtext ($hcms_lang['assign-workflow'][$lang]); ?></td>
      <td class="hcmsRowData1">[hyperCMS:workflow name='...']</td>
    </tr>
    
    <!-- stylesheet -->
    <tr align="left" valign="top"> 
      <td colspan="2" class="hcmsRowHead1"><b> <?php echo getescapedtext ($hcms_lang['stylesheet-references-for-components'][$lang]); ?></b> 
        <?php echo getescapedtext ($hcms_lang['tag-set'][$lang]); ?></td>
    </tr>
    <tr align="left" valign="top"> 
      <td class="hcmsRowHead2"> <?php echo getescapedtext ($hcms_lang['styelsheet-optional'][$lang]); ?></td>
      <td class="hcmsRowData1">[hyperCMS:compstylesheet file='...'] </td>
    </tr>
    
    <!-- template and file include -->
    <tr align="left" valign="top"> 
      <td colspan="2" class="hcmsRowHead1"><b> <?php echo getescapedtext ($hcms_lang['template-and-file-inclusion'][$lang]); ?> 
        </b> <?php echo getescapedtext ($hcms_lang['tag-set'][$lang]); ?> </td>
    </tr>
    <tr align="left" valign="top"> 
      <td class="hcmsRowHead2"> <?php echo getescapedtext ($hcms_lang['template-include'][$lang]); ?></td>
      <td nowrap class="hcmsRowData1">[hyperCMS:tplinclude file='...<font size="1"><?php echo getescapedtext ($hcms_lang['file'][$lang]); ?> 
        ...']</font></td>
    </tr>
    <tr align="left" valign="top"> 
      <td class="hcmsRowHead2"> <?php echo getescapedtext ($hcms_lang['file-include'][$lang]); ?></td>
      <td nowrap class="hcmsRowData1">[hyperCMS:fileinclude file='...<font size="1"><?php echo getescapedtext ($hcms_lang['file'][$lang]); ?> 
        ...']</font></td>
    </tr>
    
    <!-- view -->
    <tr align="left" valign="top"> 
      <td colspan="2" class="hcmsRowHead1"><b> <?php echo getescapedtext ($hcms_lang['view-option-of-the-object'][$lang]); ?> 
        </b> <?php echo getescapedtext ($hcms_lang['tag-set'][$lang]); ?> </td>
    </tr>
    <tr align="left" valign="top"> 
      <td class="hcmsRowHead2"> <?php echo getescapedtext ($hcms_lang['name-of-the-view-cmsview-inlineview-preview-formedit-formmeta-formlock-template-publish'][$lang]); ?></td>
      <td nowrap class="hcmsRowData1">[hyperCMS:objectview name='...']</font></td>
    </tr>  
  </table>
  <br />  
  <table border="0" cellspacing="0" cellpadding="3" width="100%">
    <tr >
      <td align="left" valign="top" class="hcmsHeadline"><?php echo getescapedtext ($hcms_lang['note'][$lang]); ?>:</td>
      <td align="left" valign="top"><?php echo getescapedtext ($hcms_lang['each-content-identification-name-of-a-text-media-link-or-component-tag-set-must-be-unique-inside-a-page-component-or-article'][$lang]); ?></td>
    </tr>
  </table>
</div>


<div id="Layer2" class="hcmsWorkplaceFrame" style="display:none;">
  <div style="padding:5px;">
    <a name="index"></a>
    <h1>hyperCMS API Function Reference</h1>
    
    <ol>
      <li><a href="#main">Main API Functions</a></li>
      <li><a href="#get">Get API Functions</a></li>
      <li><a href="#set">Set API Functions</a></li>
      <li><a href="#connect">Connect API Functions</a></li>
      <li><a href="#sec">Security API Functions</a></li>
      <li><a href="#media">Media API Functions</a></li>
      <li><a href="#meta">Metadata API Functions</a></li>
      <li><a href="#link">Link API Functions</a></li>
      <li><a href="#plugin">Plugin API Functions</a></li>
      <li><a href="#ui">User Interface API Functions</a></li>
      <li><a href="#tplengine">Template Engine API Functions</a></li>
      <li><a href="#xml">XML API Functions</a></li>
    </ol>
    
    <a name="main"></a>
    <h2><a href="#index"><img src="<?php echo getthemelocation(); ?>img/button_moveup_over.gif" class="hcmsButton" /></a> Main API Functions</h2>
    <?php
    $file = $mgmt_config['abs_path_cms']."function/hypercms_main.inc.php";
    echo showAPIdocs ($file);
    ?>
    <a name="get"></a>
    <h2><a href="#index"><img src="<?php echo getthemelocation(); ?>img/button_moveup_over.gif" class="hcmsButton" /></a> Get API Functions</h2>
    <?php
    $file = $mgmt_config['abs_path_cms']."function/hypercms_get.inc.php";
    echo showAPIdocs ($file);
    ?>
    <a name="set"></a>
    <h2><a href="#index"><img src="<?php echo getthemelocation(); ?>img/button_moveup_over.gif" class="hcmsButton" /></a> Set API Functions</h2>
    <?php
    $file = $mgmt_config['abs_path_cms']."function/hypercms_set.inc.php";
    echo showAPIdocs ($file);
    ?>
    <a name="connect"></a>
    <h2><a href="#index"><img src="<?php echo getthemelocation(); ?>img/button_moveup_over.gif" class="hcmsButton" /></a> Connect API Functions</h2>
    <?php
    $file = $mgmt_config['abs_path_cms']."function/hypercms_connect.inc.php";
    echo showAPIdocs ($file);
    ?>
    <a name="sec"></a>
    <h2><a href="#index"><img src="<?php echo getthemelocation(); ?>img/button_moveup_over.gif" class="hcmsButton" /></a> Security API Functions</h2>
    <?php
    $file = $mgmt_config['abs_path_cms']."function/hypercms_sec.inc.php";
    echo showAPIdocs ($file);
    ?>
    <a name="media"></a>
    <h2><a href="#index"><img src="<?php echo getthemelocation(); ?>img/button_moveup_over.gif" class="hcmsButton" /></a> Media API Functions</h2>
    <?php
    $file = $mgmt_config['abs_path_cms']."function/hypercms_media.inc.php";
    echo showAPIdocs ($file);
    ?>
    <a name="meta"></a>
    <h2><a href="#index"><img src="<?php echo getthemelocation(); ?>img/button_moveup_over.gif" class="hcmsButton" /></a> Metadata API Functions</h2>
    <?php
    $file = $mgmt_config['abs_path_cms']."function/hypercms_meta.inc.php";
    echo showAPIdocs ($file);
    ?>
    <a name="link"></a>
    <h2><a href="#index"><img src="<?php echo getthemelocation(); ?>img/button_moveup_over.gif" class="hcmsButton" /></a> Link API Functions</h2>
    <?php
    $file = $mgmt_config['abs_path_cms']."function/hypercms_link.inc.php";
    echo showAPIdocs ($file);
    ?>
    <a name="plugin"></a>
    <h2><a href="#index"><img src="<?php echo getthemelocation(); ?>img/button_moveup_over.gif" class="hcmsButton" /></a> Plugin API Functions</h2>
    <?php
    $file = $mgmt_config['abs_path_cms']."function/hypercms_plugin.inc.php";
    echo showAPIdocs ($file);
    ?>
    <a name="ui"></a>
    <h2><a href="#index"><img src="<?php echo getthemelocation(); ?>img/button_moveup_over.gif" class="hcmsButton" /></a> User Interface API Functions</h2>
    <?php
    $file = $mgmt_config['abs_path_cms']."function/hypercms_ui.inc.php";
    echo showAPIdocs ($file);
    ?>
    <a name="tplengine"></a>
    <h2><a href="#index"><img src="<?php echo getthemelocation(); ?>img/button_moveup_over.gif" class="hcmsButton" /></a> Template Engine API Functions</h2>
    <?php
    $file = $mgmt_config['abs_path_cms']."function/hypercms_tplengine.inc.php";
    echo showAPIdocs ($file);
    ?>
    <a name="xml"></a>
    <h2><a href="#index"><img src="<?php echo getthemelocation(); ?>img/button_moveup_over.gif" class="hcmsButton" /></a> XML API Functions</h2>
    <?php
    $file = $mgmt_config['abs_path_cms']."function/hypercms_xml.inc.php";
    echo showAPIdocs ($file);
    ?>
  </div>
</div>

</body>
</html>