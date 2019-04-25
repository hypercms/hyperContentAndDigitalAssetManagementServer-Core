<?php
/*
 * This file is part of
 * hyper Content & Digital Management Server - http://www.hypercms.com
 * Copyright (c) by hyper CMS Content Management Solutions GmbH
 *
 * You should have received a copy of the license (license.txt) along with hyper Content & Digital Management Server
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
<script type="text/javascript" src="javascript/main.js"></script>
<style>
table td
{
  text-align: left;
  vertical-align: top;
}
</style>
</head>

<body class="hcmsWorkplaceGeneric">

<!-- top bar -->
<?php
$menu_array = array ($hcms_lang['list-of-hypercms-tags'][$lang] => 'onclick="hcms_showHideLayers(\'Layer1\',\'\',\'show\',\'Layer2\',\'\',\'hide\'); window.scrollTo(0, 0);"', "hyperCMS API Reference" => 'onclick="hcms_showHideLayers(\'Layer1\',\'\',\'hide\',\'Layer2\',\'\',\'show\'); window.scrollTo(0, 0);"');

echo showtopmenubar ($hcms_lang['help'][$lang], $menu_array, $lang);
?>

<!-- content -->
<div id="Layer1" class="hcmsWorkplaceFrame" style="position:absolute; top:32px; left:0px;">
  <table style="table-layout:fixed; border-collapse:separate; border:0; border-spacing:2px; padding:2px; width:100%;">
    
    <!-- article -->
    <tr> 
      <td colspan="2" class="hcmsRowHead1"><b> <?php echo getescapedtext ($hcms_lang['article'][$lang]); ?></b> 
        <?php echo getescapedtext ($hcms_lang['tag-prefix'][$lang]); ?><br />
        <span class="hcmsTextSmall"><?php echo getescapedtext ($hcms_lang['can-be-used-as-prefix-for-all-text-media-link-and-component-tags-excl'][$lang]); ?></span></td>
    </tr>
    <tr> 
      <td class="hcmsRowHead2"> <?php echo getescapedtext ($hcms_lang['article'][$lang]); ?></td>
      <td class="hcmsRowData1">[hyperCMS:art...<span class="hcmsTextSmall">tag</span>...]</td>
    </tr>
    
    <!-- text -->
    <tr> 
      <td class="hcmsRowHead1" colspan="2"><b><?php echo getescapedtext ($hcms_lang['text'][$lang]); ?></b> 
        <?php echo getescapedtext ($hcms_lang['tag-set'][$lang]); ?></td>
    </tr>
    <tr> 
      <td class="hcmsRowHead2"><?php echo getescapedtext ($hcms_lang['unformatted-text'][$lang]); ?></td>
      <td class="hcmsRowData1">[hyperCMS:textu id='...']</td>
    </tr>
    <tr> 
      <td class="hcmsRowHead2"> <?php echo getescapedtext ($hcms_lang['formatted-text'][$lang]); ?></td>
      <td class="hcmsRowData1">[hyperCMS:textf id='...']</td>
    </tr>
    <tr> 
      <td class="hcmsRowHead2"> <?php echo getescapedtext ($hcms_lang['text-option-from-text-list'][$lang]); ?></td>
      <td class="hcmsRowData1">[hyperCMS:textl id='...' list='...' file='...']</td>
    </tr>
    <tr> 
      <td class="hcmsRowHead2"> <?php echo getescapedtext ($hcms_lang['checkbox'][$lang]); ?></td>
      <td class="hcmsRowData1">[hyperCMS:textc id='...' value='...']</td>
    </tr>
    <tr> 
      <td class="hcmsRowHead2"> <?php echo getescapedtext ($hcms_lang['date'][$lang]).", ".getescapedtext ($hcms_lang['date-format-optional'][$lang]); ?></td>
      <td class="hcmsRowData1">[hyperCMS:textd id='...' format='%Y-%m-%d']</td>
    </tr>
    <tr> 
      <td class="hcmsRowHead2"> <?php echo getescapedtext ($hcms_lang['keywords-with-optional-mandatory-or-open-list'][$lang]); ?></td>
      <td class="hcmsRowData1">[hyperCMS:textk id='...' list='...' file='...' onlylist='yes/no']</td>
    </tr>
    <tr> 
      <td class="hcmsRowHead2"> <?php echo getescapedtext ($hcms_lang['unformatted-comment'][$lang]); ?></td>
      <td class="hcmsRowData1">[hyperCMS:commentu id='...']</td>
    </tr>
    <tr> 
      <td class="hcmsRowHead2"> <?php echo getescapedtext ($hcms_lang['formatted-comment'][$lang]); ?></td>
      <td class="hcmsRowData1">[hyperCMS:commentf id='...']</td>
    </tr>
    <tr> 
      <td class="hcmsRowHead2"><?php echo getescapedtext ($hcms_lang['display-name-optional'][$lang]); ?></td>
      <td class="hcmsRowData1">label='...'</td>
    </tr> 
    <tr> 
      <td class="hcmsRowHead2"><?php echo getescapedtext ($hcms_lang['content-cannot-be-edited-optional'][$lang]); ?></td>
      <td class="hcmsRowData1">onEdit='hidden'</td>
    </tr>
    <tr> 
      <td class="hcmsRowHead2"><?php echo getescapedtext ($hcms_lang['hide-content-optional'][$lang]); ?></td>
      <td class="hcmsRowData1">onPublish='hidden'</td>
    </tr>
    <tr> 
      <td class="hcmsRowHead2"><?php echo getescapedtext ($hcms_lang['read-only-content'][$lang]); ?></td>
      <td class="hcmsRowData1">readonly='readonly'</td>
    </tr>
    <tr> 
      <td class="hcmsRowHead2"><?php echo getescapedtext ($hcms_lang['width-of-editorfield-in-pixel-optional'][$lang]); ?></td>
      <td class="hcmsRowData1">width='...'</td>
    </tr>
    <tr> 
      <td class="hcmsRowHead2"><?php echo getescapedtext ($hcms_lang['height-of-editorfield-in-pixel-optional'][$lang]); ?></td>
      <td class="hcmsRowData1">height='...'</td>
    </tr>
    <tr> 
      <td class="hcmsRowHead2"><?php echo getescapedtext ($hcms_lang['toolbar-selection-for-richtext-editor-optional'][$lang]); ?></td>
      <td class="hcmsRowData1">toolbar='...'</td>
    </tr>
    <tr> 
      <td class="hcmsRowHead2"><?php echo getescapedtext ($hcms_lang['constraints-definitions-for-non-formatted-text-optional'][$lang]); ?></td>
      <td class="hcmsRowData1">constraint='...'</td>
    </tr>
    <tr> 
      <td class="hcmsRowHead2"><?php echo getescapedtext ($hcms_lang['meta-information-type-optional'][$lang]); ?></td>
      <td class="hcmsRowData1">infotype='meta'</td>
    </tr>
    <tr>
      <td class="hcmsRowHead2"><?php echo getescapedtext ($hcms_lang['default-value-optional'][$lang]); ?></td>
      <td class="hcmsRowData1">default='...'</td>
    </tr>
     <tr>
      <td class="hcmsRowHead2"><?php echo getescapedtext ($hcms_lang['prefix-and-suffix-will-be-added-to-content-if-it-is-not-empty'][$lang]); ?></td>
      <td class="hcmsRowData1">prefix='...' suffix='...'</td>
    </tr>
     <tr>
      <td class="hcmsRowHead2"><?php echo getescapedtext ($hcms_lang['search-and-replace'][$lang]); ?></td>
      <td class="hcmsRowData1">replace='search=>replace|search=>replace'</td>
    </tr>
    <tr> 
      <td class="hcmsRowHead2"> <?php echo getescapedtext ($hcms_lang['valid-language-value-optional'][$lang]); ?></td>
      <td class="hcmsRowData1">language='...'</td>
    </tr>
    <tr> 
      <td class="hcmsRowHead2"> <?php echo getescapedtext ($hcms_lang['permission-for-certain-user-groups-optional'][$lang]); ?></td>
      <td class="hcmsRowData1">groups='...'</td>
    </tr>
    <tr> 
      <td class="hcmsRowHead2"><?php echo getescapedtext ($hcms_lang['media-type-'][$lang]); ?></td>
      <td class="hcmsRowData1">mediatype='...'</td>
    </tr>
    <tr> 
      <td class="hcmsRowHead2"><?php echo getescapedtext ($hcms_lang['path-type-optional'][$lang]); ?></td>
      <td class="hcmsRowData1">pathtype='...'</td>
    </tr>
    <tr> 
      <td class="hcmsRowHead2"> <?php echo getescapedtext ($hcms_lang['dpi-value-to-autoscale-images-optional'][$lang]); ?></td>
      <td class="hcmsRowData1">dpi='...'</td>
    </tr>
    <tr> 
      <td class="hcmsRowHead2"> <?php echo getescapedtext ($hcms_lang['autoconvert-images-to-the-given-colorspace-'][$lang]); ?></td>
      <td class="hcmsRowData1">colorspace='...'</td>
    </tr>
    <tr> 
      <td class="hcmsRowHead2"> <?php echo getescapedtext ($hcms_lang['autoconvert-images-to-the-given-icc-profile-'][$lang]); ?></td>
      <td class="hcmsRowData1">iccprofile='...'</td>
    </tr>
    <tr> 
      <td class="hcmsRowHead2"> <?php echo getescapedtext ($hcms_lang['preview-window'][$lang]); ?> (textu)</td>
      <td class="hcmsRowData1">preview='url'</td>
    </tr>
    
    <!-- media links -->
    <tr> 
      <td colspan="2" class="hcmsRowHead1"><b><?php echo getescapedtext ($hcms_lang['media'][$lang]); ?></b> 
        <?php echo getescapedtext ($hcms_lang['tag-set'][$lang]); ?></td>
    </tr>
    <tr> 
      <td class="hcmsRowHead2"> <?php echo getescapedtext ($hcms_lang['file'][$lang]); ?></td>
      <td class="hcmsRowData1">[hyperCMS:mediafile id='...']</td>
    </tr>
    <tr> 
      <td class="hcmsRowHead2"> <?php echo getescapedtext ($hcms_lang['alignment'][$lang]); ?></td>
      <td class="hcmsRowData1">[hyperCMS:mediaalign id='...']</td>
    </tr>
    <tr> 
      <td class="hcmsRowHead2"> <?php echo getescapedtext ($hcms_lang['width'][$lang]); ?></td>
      <td class="hcmsRowData1">[hyperCMS:mediawidth id='...']</td>
    </tr>
    <tr> 
      <td class="hcmsRowHead2"> <?php echo getescapedtext ($hcms_lang['height'][$lang]); ?></td>
      <td class="hcmsRowData1">[hyperCMS:mediaheight id='...']</td>
    </tr>
    <tr> 
      <td class="hcmsRowHead2"> <?php echo getescapedtext ($hcms_lang['alternative-text'][$lang]); ?></td>
      <td class="hcmsRowData1">[hyperCMS:mediaalttext id='...']</td>
    </tr>
    <tr> 
      <td class="hcmsRowHead2"><?php echo getescapedtext ($hcms_lang['display-name-optional'][$lang]); ?></td>
      <td class="hcmsRowData1">label='...'</td>
    </tr>   
    <tr> 
      <td class="hcmsRowHead2"><?php echo getescapedtext ($hcms_lang['content-cannot-be-edited-optional'][$lang]); ?></td>
      <td class="hcmsRowData1">onEdit='hidden'</td>
    </tr>
    <tr> 
      <td class="hcmsRowHead2"><?php echo getescapedtext ($hcms_lang['hide-content-optional'][$lang]); ?></td>
      <td class="hcmsRowData1">onPublish='hidden'</td>
    </tr>
    <tr> 
      <td class="hcmsRowHead2"><?php echo getescapedtext ($hcms_lang['read-only-content'][$lang]); ?></td>
      <td class="hcmsRowData1">readonly='readonly'</td>
    </tr>
    <tr> 
      <td class="hcmsRowHead2"><?php echo getescapedtext ($hcms_lang['media-type-'][$lang]); ?></td>
      <td class="hcmsRowData1">mediatype='...'</td>
    </tr>
    <tr> 
      <td class="hcmsRowHead2"><?php echo getescapedtext ($hcms_lang['meta-information-type-optional'][$lang]); ?></td>
      <td class="hcmsRowData1">infotype='meta'</td>
    </tr>
    <tr> 
      <td class="hcmsRowHead2"><?php echo getescapedtext ($hcms_lang['path-type-optional'][$lang]); ?></td>
      <td class="hcmsRowData1">pathtype='...'</td>
    </tr>
    <tr>
      <td class="hcmsRowHead2"><?php echo getescapedtext ($hcms_lang['use-thumbnail-image'][$lang]); ?></td>
      <td class="hcmsRowData1">thumbnail='yes'</td>
    </tr>
    <tr> 
      <td class="hcmsRowHead2"> <?php echo getescapedtext ($hcms_lang['valid-language-value-optional'][$lang]); ?></td>
      <td class="hcmsRowData1">language='...'</td>
    </tr>
    <tr> 
      <td class="hcmsRowHead2"> <?php echo getescapedtext ($hcms_lang['permission-for-certain-user-groups-optional'][$lang]); ?></td>
      <td class="hcmsRowData1">groups='...'</td>
    </tr>
    <tr> 
      <td class="hcmsRowHead2"> <?php echo getescapedtext ($hcms_lang['dpi-value-to-autoscale-images-optional'][$lang]); ?></td>
      <td class="hcmsRowData1">dpi='...'</td>
    </tr>
    <tr> 
      <td class="hcmsRowHead2"> <?php echo getescapedtext ($hcms_lang['autoconvert-images-to-the-given-colorspace-'][$lang]); ?></td>
      <td class="hcmsRowData1">colorspace='...'</td>
    </tr>
    <tr> 
      <td class="hcmsRowHead2"> <?php echo getescapedtext ($hcms_lang['autoconvert-images-to-the-given-icc-profile-'][$lang]); ?></td>
      <td class="hcmsRowData1">iccprofile='...'</td>
    </tr>
    
    <!-- page links -->
    <tr> 
      <td colspan="2" class="hcmsRowHead1"><b> <?php echo getescapedtext ($hcms_lang['link'][$lang]); ?></b> 
        <?php echo getescapedtext ($hcms_lang['tag-set'][$lang]); ?></td>
    </tr>
    <tr> 
      <td class="hcmsRowHead2"> <?php echo getescapedtext ($hcms_lang['hyper-reference'][$lang]); ?></td>
      <td class="hcmsRowData1">[hyperCMS:linkhref id='...']</td>
    </tr>
    <tr> 
      <td class="hcmsRowHead2"> <?php echo getescapedtext ($hcms_lang['frame-target'][$lang]); ?></td>
      <td class="hcmsRowData1">[hyperCMS:linktarget id='...']</td>
    </tr>
    <tr> 
      <td class="hcmsRowHead2"> <?php echo getescapedtext ($hcms_lang['link-text'][$lang]); ?></td>
      <td class="hcmsRowData1">[hyperCMS:linktext id='...']</td>
    </tr>
    <tr> 
      <td class="hcmsRowHead2"><?php echo getescapedtext ($hcms_lang['display-name-optional'][$lang]); ?></td>
      <td class="hcmsRowData1">label='...'</td>
    </tr>    
    <tr> 
      <td class="hcmsRowHead2"><?php echo getescapedtext ($hcms_lang['content-cannot-be-edited-optional'][$lang]); ?></td>
      <td class="hcmsRowData1">onEdit='hidden'</td>
    </tr> 
    <tr> 
      <td class="hcmsRowHead2"><?php echo getescapedtext ($hcms_lang['hide-content-optional'][$lang]); ?></td>
      <td class="hcmsRowData1">onPublish='hidden'</td>
    </tr>
    <tr> 
      <td class="hcmsRowHead2"><?php echo getescapedtext ($hcms_lang['read-only-content'][$lang]); ?></td>
      <td class="hcmsRowData1">readonly='readonly'</td>
    </tr>
    <tr> 
      <td class="hcmsRowHead2"><?php echo getescapedtext ($hcms_lang['meta-information-type-optional'][$lang]); ?></td>
      <td class="hcmsRowData1">infotype='meta'</td>
    </tr>
    <tr> 
      <td class="hcmsRowHead2"> <?php echo getescapedtext ($hcms_lang['valid-language-value-optional'][$lang]); ?></td>
      <td class="hcmsRowData1">language='...'</td>
    </tr>
    <tr> 
      <td class="hcmsRowHead2"> <?php echo getescapedtext ($hcms_lang['permission-for-certain-user-groups-optional'][$lang]); ?></td>
      <td class="hcmsRowData1">groups='...'</td>
    </tr>
    
    <!-- component links --> 
    <tr> 
      <td colspan="2" class="hcmsRowHead1"><b> <?php echo getescapedtext ($hcms_lang['component'][$lang]); ?></b> 
        <?php echo getescapedtext ($hcms_lang['tag-set'][$lang]); ?></td>
    </tr>
    <tr> 
      <td class="hcmsRowHead2"> <?php echo getescapedtext ($hcms_lang['single-component'][$lang]); ?></td>
      <td class="hcmsRowData1">[hyperCMS:components id='...']</td>
    </tr>
    <tr> 
      <td class="hcmsRowHead2"> <?php echo getescapedtext ($hcms_lang['multi-component'][$lang]); ?></td>
      <td class="hcmsRowData1">[hyperCMS:componentm id='...']</td>
    </tr>
    <tr> 
      <td class="hcmsRowHead2"><?php echo getescapedtext ($hcms_lang['display-name-optional'][$lang]); ?></td>
      <td class="hcmsRowData1">label='...'</td>
    </tr>    
    <tr> 
      <td class="hcmsRowHead2"><?php echo getescapedtext ($hcms_lang['content-cannot-be-edited-optional'][$lang]); ?></td>
      <td class="hcmsRowData1">onEdit='hidden'</td>
    </tr>
    <tr> 
      <td class="hcmsRowHead2"><?php echo getescapedtext ($hcms_lang['hide-content-optional'][$lang]); ?></td>
      <td class="hcmsRowData1">onPublish='hidden'</td>
    </tr>
    <tr> 
      <td class="hcmsRowHead2"><?php echo getescapedtext ($hcms_lang['read-only-content'][$lang]); ?></td>
      <td class="hcmsRowData1">readonly='readonly'</td>
    </tr>
    <tr> 
      <td class="hcmsRowHead2"> <?php echo getescapedtext ($hcms_lang['static-include-optional'][$lang]); ?></td>
      <td class="hcmsRowData1">include='static'</td>
    </tr>
    <tr> 
      <td class="hcmsRowHead2"> <?php echo getescapedtext ($hcms_lang['hide-icons-on-edit-optional'][$lang]); ?></td>
      <td class="hcmsRowData1"> icon='hidden'</td>
    </tr>
    <tr> 
      <td class="hcmsRowHead2"><?php echo getescapedtext ($hcms_lang['meta-information-type-optional'][$lang]); ?></td>
      <td class="hcmsRowData1">infotype='meta'</td>
    </tr>
    <tr>
      <td class="hcmsRowHead2"><?php echo getescapedtext ($hcms_lang['default-value-optional'][$lang]); ?></td>
      <td class="hcmsRowData1">default='...'</td>
    </tr>  
    <tr> 
      <td class="hcmsRowHead2"> <?php echo getescapedtext ($hcms_lang['valid-language-value-optional'][$lang]); ?></td>
      <td class="hcmsRowData1">language='...'</td>
    </tr>
    <tr> 
      <td class="hcmsRowHead2"> <?php echo getescapedtext ($hcms_lang['permission-for-certain-user-groups-optional'][$lang]); ?></td>
      <td class="hcmsRowData1">groups='...'</td>
    </tr>  
    <tr> 
      <td class="hcmsRowHead2"><?php echo getescapedtext ($hcms_lang['media-type-'][$lang]); ?></td>
      <td class="hcmsRowData1">mediatype='...'</td>
    </tr>
    
    <!-- page title -->  
    <tr> 
      <td colspan="2" class="hcmsRowHead1"><b> <?php echo getescapedtext ($hcms_lang['title'][$lang]); ?></b> 
        <?php echo getescapedtext ($hcms_lang['tag-set'][$lang]); ?></td>
    </tr>
    <tr> 
      <td class="hcmsRowHead2"> <?php echo getescapedtext ($hcms_lang['page-title'][$lang]); ?></td>
      <td class="hcmsRowData1">[hyperCMS:pagetitle]</td>
    </tr>
    <tr> 
      <td class="hcmsRowHead2"><?php echo getescapedtext ($hcms_lang['display-name-optional'][$lang]); ?></td>
      <td class="hcmsRowData1">label='...'</td>
    </tr>   
    <tr> 
      <td class="hcmsRowHead2"> <?php echo getescapedtext ($hcms_lang['default-value-optional'][$lang]); ?></td>
      <td class="hcmsRowData1">default='...'</td>
    </tr>  
    <tr> 
      <td class="hcmsRowHead2"> <?php echo getescapedtext ($hcms_lang['valid-language-value-optional'][$lang]); ?></td>
      <td class="hcmsRowData1">language='...'</td>
    </tr>  
    
    <!-- meta info -->
    <tr> 
      <td colspan="2" class="hcmsRowHead1"><b> <?php echo getescapedtext ($hcms_lang['meta-information'][$lang]); ?></b> 
        <?php echo getescapedtext ($hcms_lang['tag-set'][$lang]); ?></td>
    </tr>
    <tr> 
      <td class="hcmsRowHead2"> <?php echo getescapedtext ($hcms_lang['author'][$lang]); ?></td>
      <td class="hcmsRowData1">[hyperCMS:pageauthor]</td>
    </tr>
    <tr> 
      <td class="hcmsRowHead2"> <?php echo getescapedtext ($hcms_lang['keywords'][$lang]); ?></td>
      <td class="hcmsRowData1">[hyperCMS:pagekeywords]</td>
    </tr>
    <tr> 
      <td class="hcmsRowHead2"> <?php echo getescapedtext ($hcms_lang['description'][$lang]); ?></td>
      <td class="hcmsRowData1">[hyperCMS:pagedescription]</td>
    </tr>
    <tr> 
      <td class="hcmsRowHead2"> <?php echo getescapedtext ($hcms_lang['content-type'][$lang]); ?></td>
      <td class="hcmsRowData1">[hyperCMS:pagecontenttype]<br />
        [hyperCMS:compcontenttype content='...'] </td>
    </tr>
    <tr> 
      <td class="hcmsRowHead2"> <?php echo getescapedtext ($hcms_lang['language'][$lang]); ?></td>
      <td class="hcmsRowData1">[hyperCMS:pagelanguage]</td>
    </tr>
    <tr> 
      <td class="hcmsRowHead2"><?php echo getescapedtext ($hcms_lang['display-name-optional'][$lang]); ?></td>
      <td class="hcmsRowData1">label='...'</td>
    </tr>   
    <tr> 
      <td class="hcmsRowHead2"> <?php echo getescapedtext ($hcms_lang['default-value-optional'][$lang]); ?></td>
      <td class="hcmsRowData1">default='...'</td>
    </tr>
    <tr> 
      <td class="hcmsRowHead2"> <?php echo getescapedtext ($hcms_lang['permission-for-certain-user-groups-optional'][$lang]); ?></td>
      <td class="hcmsRowData1">groups='...'</td>
    </tr>
    <tr> 
      <td class="hcmsRowHead2"><?php echo getescapedtext ($hcms_lang['read-only-content'][$lang]); ?></td>
      <td class="hcmsRowData1">readonly='readonly'</td>
    </tr>
    
    <!-- geo location --> 
    <tr> 
      <td colspan="2" class="hcmsRowHead1"><b> <?php echo getescapedtext ($hcms_lang['geo-location'][$lang]); ?></td>
    </tr>
    <tr> 
      <td class="hcmsRowHead2"> <?php echo getescapedtext ($hcms_lang['geo-location'][$lang]); ?></td>
      <td class="hcmsRowData1">[hyperCMS:geolocation infotype='meta']</td>
    </tr>
    <tr> 
      <td class="hcmsRowHead2"><?php echo getescapedtext ($hcms_lang['display-name-optional'][$lang]); ?></td>
      <td class="hcmsRowData1">label='...'</td>
    </tr>
    <tr> 
      <td class="hcmsRowHead2"><?php echo getescapedtext ($hcms_lang['content-cannot-be-edited-optional'][$lang]); ?></td>
      <td class="hcmsRowData1">onEdit='hidden'</td>
    </tr>
    <tr> 
      <td class="hcmsRowHead2"><?php echo getescapedtext ($hcms_lang['hide-content-optional'][$lang]); ?></td>
      <td class="hcmsRowData1">onPublish='hidden'</td>
    </tr>
    <tr> 
      <td class="hcmsRowHead2"><?php echo getescapedtext ($hcms_lang['read-only-content'][$lang]); ?></td>
      <td class="hcmsRowData1">readonly='readonly'</td>
    </tr>
    <tr> 
      <td class="hcmsRowHead2"> <?php echo getescapedtext ($hcms_lang['permission-for-certain-user-groups-optional'][$lang]); ?></td>
      <td class="hcmsRowData1">groups='...'</td>
    </tr>
    
    <!-- language session setting --> 
    <tr> 
      <td colspan="2" class="hcmsRowHead1"><b> <?php echo getescapedtext ($hcms_lang['language-session-setting'][$lang]); ?></td>
    </tr>
    <tr> 
      <td class="hcmsRowHead2"> <?php echo getescapedtext ($hcms_lang['session-name-and-values'][$lang]); ?></td>
      <td class="hcmsRowData1">[hyperCMS:language name='...' list='...']</td>
    </tr> 
    <tr> 
      <td class="hcmsRowHead2"> <?php echo getescapedtext ($hcms_lang['display-name-optional'][$lang]); ?></td>
      <td class="hcmsRowData1">label='...'</td>
    </tr>
    <tr> 
      <td class="hcmsRowHead2"> <?php echo getescapedtext ($hcms_lang['default-value-optional'][$lang]); ?></td>
      <td class="hcmsRowData1">default='...'</td>
    </tr>
    <tr> 
      <td class="hcmsRowHead2"> <?php echo getescapedtext ($hcms_lang['valid-language-value-optional'][$lang]); ?></td>
      <td class="hcmsRowData1">language='...'</td>
    </tr>
    
    <!-- page tracking -->      
    <tr> 
      <td colspan="2" class="hcmsRowHead1"><b> <?php echo getescapedtext ($hcms_lang['personalization'][$lang]); ?></b> 
        <?php echo getescapedtext ($hcms_lang['tag-set'][$lang]); ?><br /> <span class="hcmsTextSmall"> <?php echo getescapedtext ($hcms_lang['content-can-be-personalized-for-customers-based-on-the-customer-profile-of-a-component'][$lang]); ?></span></td>
    </tr>
    <tr> 
      <td class="hcmsRowHead2"> <?php echo getescapedtext ($hcms_lang['customer-tracking'][$lang]); ?></td>
      <td class="hcmsRowData1">[hyperCMS:pagetracking]</td>
    </tr>
    <tr> 
      <td class="hcmsRowHead2"> <?php echo getescapedtext ($hcms_lang['default-value-optional'][$lang]); ?></td>
      <td class="hcmsRowData1">default='...'</td>
    </tr>
    <tr> 
      <td class="hcmsRowHead2"> <?php echo getescapedtext ($hcms_lang['permission-for-certain-user-groups-optional'][$lang]); ?></td>
      <td class="hcmsRowData1">groups='...'</td>
    </tr>    
    
    <!-- script -->
    <tr> 
      <td colspan="2" class="hcmsRowHead1"><b> <?php echo getescapedtext ($hcms_lang['script'][$lang]); ?></b> 
        <?php echo getescapedtext ($hcms_lang['tag-set'][$lang]); ?></td>
    </tr>
    <tr> 
      <td class="hcmsRowHead2"> <?php echo getescapedtext ($hcms_lang['template-script'][$lang]." (PHP/API)"); ?></td>
      <td class="hcmsRowData1">[hyperCMS:scriptbegin ... scriptend]</td>
    </tr>
    <tr> 
      <td class="hcmsRowHead2"> <?php echo getescapedtext ("JavaScript (form-views)"); ?></td>
      <td class="hcmsRowData1">[JavaScript:scriptbegin ... scriptend]</td>
    </tr>
    
    <!-- dbconnect -->
    <tr> 
      <td colspan="2" class="hcmsRowHead1"><b> <?php echo getescapedtext ($hcms_lang['database-connectivity'][$lang]); ?></b> 
        <?php echo getescapedtext ($hcms_lang['tag-set'][$lang]); ?><br /> <span class="hcmsTextSmall"> <?php echo getescapedtext ($hcms_lang['db-connect-file-has-to-be-defined'][$lang]); ?></span></td>
    </tr>
    <tr> 
      <td class="hcmsRowHead2"> <?php echo getescapedtext ($hcms_lang['db-connectivity'][$lang]); ?></td>
      <td class="hcmsRowData1">[hyperCMS:dbconnect file='...<span class="hcmsTextSmall"> 
        <?php echo getescapedtext ($hcms_lang['file'][$lang]); ?></span> ...']</td>
    </tr>
    
    <!-- workflow -->
    <tr> 
      <td colspan="2" class="hcmsRowHead1"><b> <?php echo getescapedtext ($hcms_lang['workflow'][$lang]); ?></b> 
        <?php echo getescapedtext ($hcms_lang['tag-set'][$lang]); ?><br /> <span class="hcmsTextSmall"> <?php echo getescapedtext ($hcms_lang['workflow-must-be-defined'][$lang]); ?></span></td>
    </tr>
    <tr> 
      <td class="hcmsRowHead2"> <?php echo getescapedtext ($hcms_lang['assign-workflow'][$lang]); ?></td>
      <td class="hcmsRowData1">[hyperCMS:workflow name='...']</td>
    </tr>
    
    <!-- stylesheet -->
    <tr> 
      <td colspan="2" class="hcmsRowHead1"><b> <?php echo getescapedtext ($hcms_lang['stylesheet-references-for-components'][$lang]); ?></b> 
        <?php echo getescapedtext ($hcms_lang['tag-set'][$lang]); ?></td>
    </tr>
    <tr> 
      <td class="hcmsRowHead2"> <?php echo getescapedtext ($hcms_lang['styelsheet-optional'][$lang]); ?></td>
      <td class="hcmsRowData1">[hyperCMS:compstylesheet file='...'] </td>
    </tr>
    
    <!-- template and file include -->
    <tr> 
      <td colspan="2" class="hcmsRowHead1"><b> <?php echo getescapedtext ($hcms_lang['template-and-file-inclusion'][$lang]); ?> 
        </b> <?php echo getescapedtext ($hcms_lang['tag-set'][$lang]); ?> </td>
    </tr>
    <tr> 
      <td class="hcmsRowHead2"> <?php echo getescapedtext ($hcms_lang['template-include'][$lang]); ?></td>
      <td nowrap class="hcmsRowData1">[hyperCMS:tplinclude file='...<span class="hcmsTextSmall"><?php echo getescapedtext ($hcms_lang['file'][$lang]); ?> 
        ...']</span></td>
    </tr>
    <tr> 
      <td class="hcmsRowHead2"> <?php echo getescapedtext ($hcms_lang['file-include'][$lang]); ?></td>
      <td nowrap class="hcmsRowData1">[hyperCMS:fileinclude file='...<span class="hcmsTextSmall"><?php echo getescapedtext ($hcms_lang['file'][$lang]); ?> 
        ...']</span></td>
    </tr>
    
    <!-- view -->
    <tr> 
      <td colspan="2" class="hcmsRowHead1"><b> <?php echo getescapedtext ($hcms_lang['view-option-of-the-object'][$lang]); ?> 
        </b> <?php echo getescapedtext ($hcms_lang['tag-set'][$lang]); ?> </td>
    </tr>
    <tr> 
      <td class="hcmsRowHead2"> <?php echo getescapedtext ($hcms_lang['name-of-the-view-cmsview-inlineview-preview-formedit-formmeta-formlock-template-publish'][$lang]); ?></td>
      <td nowrap class="hcmsRowData1">[hyperCMS:objectview name='...']</span></td>
    </tr>  

    <!-- note -->
    <tr>
      <td colspan="2"><span class="hcmsHeadline"><?php echo getescapedtext ($hcms_lang['note'][$lang]); ?> </span><br />
      <?php echo getescapedtext ($hcms_lang['each-content-identification-name-of-a-text-media-link-or-component-tag-set-must-be-unique-inside-a-page-component-or-article'][$lang]); ?></td>
    </tr>
  </table>
</div>


<div id="Layer2" class="hcmsWorkplaceFrame" style="position:absolute; top:32px; left:0px; visibility:hidden;">
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
      <?php if (is_file ($mgmt_config['abs_path_cms']."report/hypercms_report.inc.php")) { ?>
      <li><a href="#report">Report API Functions</a></li>
      <?php } ?>
      <?php if (is_file ($mgmt_config['abs_path_cms']."project/hypercms_project.inc.php")) { ?>
      <li><a href="#project">Project API Functions</a></li>
      <?php } ?>
      <?php if (is_file ($mgmt_config['abs_path_cms']."task/hypercms_task.inc.php")) { ?>
      <li><a href="#task">Task API Functions</a></li>
      <?php } ?>
      <?php if (is_file ($mgmt_config['abs_path_cms']."workflow/hypercms_workflow.inc.php")) { ?>
      <li><a href="#workflow">Workflow API Functions</a></li>
      <?php } ?>
      <?php if (is_file ($mgmt_config['abs_path_cms']."connector/cloud/hypercms_cloud.inc.php")) { ?>
      <li><a href="#workflow">Cloud Storage API Functions</a></li>
      <?php } ?>
      <?php if (is_file ($mgmt_config['abs_path_cms']."connector/imexport/hypercms_imexport.inc.php")) { ?>
      <li><a href="#imexport">Import/Export API Functions</a></li>
      <?php } ?>
    </ol>
    
    <a name="main"></a>
    <h2><a href="#index"><img src="<?php echo getthemelocation(); ?>img/button_moveup.png" class="hcmsButton hcmsIconList" /></a> Main API Functions</h2>
    <?php
    $file = $mgmt_config['abs_path_cms']."function/hypercms_main.inc.php";
    echo showAPIdocs ($file);
    ?>
    
    <a name="get"></a>
    <h2><a href="#index"><img src="<?php echo getthemelocation(); ?>img/button_moveup.png" class="hcmsButton hcmsIconList" /></a> Get API Functions</h2>
    <?php
    $file = $mgmt_config['abs_path_cms']."function/hypercms_get.inc.php";
    echo showAPIdocs ($file);
    ?>
    
    <a name="set"></a>
    <h2><a href="#index"><img src="<?php echo getthemelocation(); ?>img/button_moveup.png" class="hcmsButton hcmsIconList" /></a> Set API Functions</h2>
    <?php
    $file = $mgmt_config['abs_path_cms']."function/hypercms_set.inc.php";
    echo showAPIdocs ($file);
    ?>
    
    <a name="connect"></a>
    <h2><a href="#index"><img src="<?php echo getthemelocation(); ?>img/button_moveup.png" class="hcmsButton hcmsIconList" /></a> Connect API Functions</h2>
    <?php
    $file = $mgmt_config['abs_path_cms']."function/hypercms_connect.inc.php";
    echo showAPIdocs ($file);
    ?>
    
    <a name="sec"></a>
    <h2><a href="#index"><img src="<?php echo getthemelocation(); ?>img/button_moveup.png" class="hcmsButton hcmsIconList" /></a> Security API Functions</h2>
    <?php
    $file = $mgmt_config['abs_path_cms']."function/hypercms_sec.inc.php";
    echo showAPIdocs ($file);
    ?>
    
    <a name="media"></a>
    <h2><a href="#index"><img src="<?php echo getthemelocation(); ?>img/button_moveup.png" class="hcmsButton hcmsIconList" /></a> Media API Functions</h2>
    <?php
    $file = $mgmt_config['abs_path_cms']."function/hypercms_media.inc.php";
    echo showAPIdocs ($file);
    ?>
    
    <a name="meta"></a>
    <h2><a href="#index"><img src="<?php echo getthemelocation(); ?>img/button_moveup.png" class="hcmsButton hcmsIconList" /></a> Metadata API Functions</h2>
    <?php
    $file = $mgmt_config['abs_path_cms']."function/hypercms_meta.inc.php";
    echo showAPIdocs ($file);
    ?>
    
    <a name="link"></a>
    <h2><a href="#index"><img src="<?php echo getthemelocation(); ?>img/button_moveup.png" class="hcmsButton hcmsIconList" /></a> Link API Functions</h2>
    <?php
    $file = $mgmt_config['abs_path_cms']."function/hypercms_link.inc.php";
    echo showAPIdocs ($file);
    ?>
    
    <a name="plugin"></a>
    <h2><a href="#index"><img src="<?php echo getthemelocation(); ?>img/button_moveup.png" class="hcmsButton hcmsIconList" /></a> Plugin API Functions</h2>
    <?php
    $file = $mgmt_config['abs_path_cms']."function/hypercms_plugin.inc.php";
    echo showAPIdocs ($file);
    ?>
    <a name="ui"></a>
    <h2><a href="#index"><img src="<?php echo getthemelocation(); ?>img/button_moveup.png" class="hcmsButton hcmsIconList" /></a> User Interface API Functions</h2>
    <?php
    $file = $mgmt_config['abs_path_cms']."function/hypercms_ui.inc.php";
    echo showAPIdocs ($file);
    ?>
    
    <a name="tplengine"></a>
    <h2><a href="#index"><img src="<?php echo getthemelocation(); ?>img/button_moveup.png" class="hcmsButton hcmsIconList" /></a> Template Engine API Functions</h2>
    <?php
    $file = $mgmt_config['abs_path_cms']."function/hypercms_tplengine.inc.php";
    echo showAPIdocs ($file);
    ?>
    
    <a name="xml"></a>
    <h2><a href="#index"><img src="<?php echo getthemelocation(); ?>img/button_moveup.png" class="hcmsButton hcmsIconList" /></a> XML API Functions</h2>
    <?php
    $file = $mgmt_config['abs_path_cms']."function/hypercms_xml.inc.php";
    echo showAPIdocs ($file);
    ?>
    
    <?php if (is_file ($mgmt_config['abs_path_cms']."report/hypercms_report.inc.php")) { ?>
    <a name="report"></a>
    <h2><a href="#index"><img src="<?php echo getthemelocation(); ?>img/button_moveup.png" class="hcmsButton hcmsIconList" /></a> Report API Functions</h2>
    <?php
    $file = $mgmt_config['abs_path_cms']."report/hypercms_report.inc.php";
    echo showAPIdocs ($file);
    } ?>
    
    <?php if (is_file ($mgmt_config['abs_path_cms']."project/hypercms_project.inc.php")) { ?>
    <a name="project"></a>
    <h2><a href="#index"><img src="<?php echo getthemelocation(); ?>img/button_moveup.png" class="hcmsButton hcmsIconList" /></a> Project API Functions</h2>
    <?php
    $file = $mgmt_config['abs_path_cms']."project/hypercms_project.inc.php";
    echo showAPIdocs ($file);
    } ?>
    
    <?php if (is_file ($mgmt_config['abs_path_cms']."task/hypercms_task.inc.php")) { ?>
    <a name="task"></a>
    <h2><a href="#index"><img src="<?php echo getthemelocation(); ?>img/button_moveup.png" class="hcmsButton hcmsIconList" /></a> Task API Functions</h2>
    <?php
    $file = $mgmt_config['abs_path_cms']."task/hypercms_task.inc.php";
    echo showAPIdocs ($file);
    } ?>
    
    <?php if (is_file ($mgmt_config['abs_path_cms']."workflow/hypercms_workflow.inc.php")) { ?>
    <a name="workflow"></a>
    <h2><a href="#index"><img src="<?php echo getthemelocation(); ?>img/button_moveup.png" class="hcmsButton hcmsIconList" /></a> Workflow API Functions</h2>
    <?php
    $file = $mgmt_config['abs_path_cms']."workflow/hypercms_workflow.inc.php";
    echo showAPIdocs ($file);
    } ?>
    
    <?php if (is_file ($mgmt_config['abs_path_cms']."connector/cloud/hypercms_cloud.inc.php")) { ?>
    <a name="cloud"></a>
    <h2><a href="#index"><img src="<?php echo getthemelocation(); ?>img/button_moveup.png" class="hcmsButton hcmsIconList" /></a> Cloud Storage API Functions</h2>
    <?php
    $file = $mgmt_config['abs_path_cms']."connector/cloud/hypercms_cloud.inc.php";
    echo showAPIdocs ($file);
    } ?>
    
    <?php if (is_file ($mgmt_config['abs_path_cms']."connector/imexport/hypercms_imexport.inc.php")) { ?>
    <a name="imexport"></a>
    <h2><a href="#index"><img src="<?php echo getthemelocation(); ?>img/button_moveup.png" class="hcmsButton hcmsIconList" /></a> Import/Export API Functions</h2>
    <?php
    $file = $mgmt_config['abs_path_cms']."connector/imexport/hypercms_imexport.inc.php";
    echo showAPIdocs ($file);
    } ?>
  </div>
</div>

</body>
</html>