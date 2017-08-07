<?php
/*
 * This file is part of
 * hyper Content & Digital Management Server - http://www.hypercms.com
 * Copyright (c) by hyper CMS Content Management Solutions GmbH
 *
 * You should have received a copy of the License along with hyperCMS.
 */

// session
define ("SESSION", "create");
// management configuration
require ("../config.inc.php");
// hyperCMS API
require ("../function/hypercms_api.inc.php");


// input parameters
$location = getrequest_esc ("location", "locationname");
$page = getrequest_esc ("page", "objectname");
$contenttype = getrequest_esc ("contenttype");
$db_connect = getrequest_esc ("db_connect", "objectname");
$id = getrequest_esc ("id", "objectname");
$label = getrequest_esc ("label");
$tagname = getrequest_esc ("tagname", "objectname");
$width = getrequest_esc ("width", "numeric");
$height = getrequest_esc ("height", "numeric");
$dpi = getrequest ("dpi", "numeric", "72");
$toolbar = getrequest_esc ("toolbar");
$default = getrequest_esc ("default");
$token = getrequest ("token");

// get publication and category
$site = getpublication ($location);
$cat = getcategory ($site, $location);

// publication management config
if (valid_publicationname ($site)) require ($mgmt_config['abs_path_data']."config/".$site.".conf.php");
// include publication target settings
if (valid_publicationname ($site)) $publ_config = parse_ini_file ($mgmt_config['abs_path_rep']."config/".$site.".ini"); 

// ------------------------------ permission section --------------------------------

// convert location
$location = deconvertpath ($location, "file");
$location_esc = convertpath ($site, $location, $cat);

// check access permissions
$ownergroup = accesspermission ($site, $location, $cat);
$setlocalpermission = setlocalpermission ($site, $ownergroup, $cat);  
if ($ownergroup == false || $setlocalpermission['root'] != 1 || $setlocalpermission['create'] != 1) killsession ($user);

// check session of user
checkusersession ($user);

// --------------------------------- logic section ----------------------------------

// load object file and get container
$objectdata = loadfile ($location, $page);
$contentfile = getfilename ($objectdata, "content");

// define content-type if not set
if ($contenttype == "")
{
  $contenttype = "text/html; charset=".$mgmt_config[$site]['default_codepage'];
  $charset = $mgmt_config[$site]['default_codepage'];
}
elseif (strpos ($contenttype, "charset") > 0)
{
  $charset = getattribute ($contenttype, "charset");
}
else $charset = $mgmt_config[$site]['default_codepage'];

// read content using db_connect
if (!empty ($db_connect) && $db_connect != false && file_exists ($mgmt_config['abs_path_data']."db_connect/".$db_connect))
{
  include ($mgmt_config['abs_path_data']."db_connect/".$db_connect);
  
  $db_connect_data = db_read_text ($site, $contentfile, "", $id, "", $user);
  
  if ($db_connect_data != false)
  {
    $contentbot = $db_connect_data['text'];
  }
  else
  {
    $contentbot = false;
  }
}
else
{
  $contentbot = false;
}

// read content from content container
if ($contentbot == false)
{
  $container_id = substr ($contentfile, 0, strpos ($contentfile, ".xml")); 

  $filedata = loadcontainer ($contentfile, "work", $user);
  
  if ($filedata != "")
  {  
    $contentarray = selectcontent ($filedata, "<text>", "<text_id>", $id);
    $contentarray = getcontent ($contentarray[0], "<textcontent>");
    $contentbot = $contentarray[0];
  }
}

// set default value given eventually by tag
if ($contentbot == "" && $default != "") $contentbot = $default;

// ========================================== replace template variables =============================================        
// replace the media varibales in the template with the images-url
$contentbot = str_replace ("%media%", substr ($mgmt_config['url_path_media'], 0, strlen ($mgmt_config['url_path_media'])-1), $contentbot);

// transform links in old versions before 5.5.5 (%url_page%, %url_comp%)
$contentbot = str_replace ("%url_page%", "%page%/".$site, $contentbot);
$contentbot = str_replace ("%url_comp%", "%comp%", $contentbot);

// replace the object varibales in the template with the URL of the page root
$contentbot = str_replace ("%page%/".$site, substr ($mgmt_config[$site]['url_path_page'], 0, strlen ($mgmt_config[$site]['url_path_page'])-1), $contentbot);       

// replace the url_comp varibales in the template with the URL of the component root
$contentbot = str_replace ("%comp%", substr ($mgmt_config['url_path_comp'], 0, strlen ($mgmt_config['url_path_comp'])-1), $contentbot); 

// transform cms link used for video player
$contentbot = str_replace ("%hcms%", substr ($mgmt_config['url_path_cms'], 0, strlen ($mgmt_config['url_path_cms'])-1), $contentbot);
   
// register site for editor
$site_editor = $site;
$contenttype_editor = $contenttype;
$_SESSION['site_editor'] = $site_editor;
$_SESSION['contenttype_editor'] = $contenttype_editor;

// define default editor size
if ($height == false || $height <= 0) $height = "200";
if ($width == false || $width <= 0) $width = "600";

// create secure token
$token = createtoken ($user);
?>
<!DOCTYPE html>
<html>
  <head>
    <title>hyperCMS</title>
    <meta charset="<?php echo $charset; ?>" />
    <meta name="robots" content="noindex, nofollow" />
    <link rel="stylesheet" href="<?php echo getthemelocation(); ?>css/main.css" />
    <script src="../javascript/jquery/jquery-1.10.2.min.js" type="text/javascript"></script>
    <script type="text/javascript" src="ckeditor/ckeditor.js"></script>
    <script type="text/javascript" src="../javascript/main.js" ></script>
    <script type="text/javascript">
    function setsavetype(type)
    {
      document.forms['hcms_formview'].elements['savetype'].value = type;
      document.forms['hcms_formview'].submit();
      return true;
    }
    </script>
    <?php echo showvideoplayer_head (false); ?>
  </head>
  
  <body class="hcmsWorkplaceGeneric">

    <!-- top bar -->
    <?php
    if ($label == "") $label = $id;
    
    echo showtopbar ($label, $lang, $mgmt_config['url_path_cms']."page_view.php?site=".url_encode($site)."&cat=".url_encode($cat)."&location=".url_encode($location_esc)."&page=".url_encode($page), "objFrame");
    ?>

    <!-- form for content -->
    <div class="hcmsWorkplaceFrame">
      <form action="<?php echo $mgmt_config['url_path_cms']; ?>service/savecontent.php" method="post" name="hcms_formview" id="hcms_formview">
        <input type="hidden" name="contenttype" value="<?php echo $contenttype; ?>"> 
        <input type="hidden" name="site" value="<?php echo $site; ?>"> 
        <input type="hidden" name="cat" value="<?php echo $cat; ?>"> 
        <input type="hidden" name="location" value="<?php echo $location_esc; ?>">
        <input type="hidden" name="page" value="<?php echo $page; ?>">
        <input type="hidden" name="db_connect" value="<?php echo $db_connect; ?>">
        <input type="hidden" name="tagname" value="<?php echo $tagname; ?>"> 
        <input type="hidden" name="id" value="<?php echo $id; ?>"> 
        <input type="hidden" name="width" value="<?php echo $width; ?>"> 
        <input type="hidden" name="height" value="<?php echo $height; ?>">
        <input type="hidden" name="toolbar" value="<?php echo $toolbar; ?>"> 
        <input type="hidden" id="savetype" name="savetype" value="">
        <input type="hidden" name="token" value="<?php echo $token; ?>">
        
        <table border="0" cellspacing="2">
          <tr>
            <td nowrap="nowrap" align="left">
              <img name="Button_so" src="<?php echo getthemelocation(); ?>img/button_save.png" class="hcmsButton hcmsButtonSizeSquare" onClick="setsavetype('editorf_so');" alt="<?php echo getescapedtext ($hcms_lang['save'][$lang], $charset, $lang); ?>" title="<?php echo getescapedtext ($hcms_lang['save'][$lang], $charset, $lang); ?>" align="absmiddle" />   
              <img name="Button_sc" src="<?php echo getthemelocation(); ?>img/button_saveclose.png" class="hcmsButton hcmsButtonSizeSquare" onClick="setsavetype('editorf_sc');" alt="<?php echo getescapedtext ($hcms_lang['save-and-close'][$lang], $charset, $lang); ?>" title="<?php echo getescapedtext ($hcms_lang['save-and-close'][$lang], $charset, $lang); ?>" align="absmiddle" />
              <?php if (intval ($mgmt_config['autosave']) > 0) { ?>
              <div class="hcmsButton hcmsButtonSizeHeight" style="line-height:28px;">
                &nbsp;<label for="autosave"><input type="checkbox" id="autosave" name="autosave" value="yes" checked="checked" />&nbsp;<?php echo getescapedtext ($hcms_lang['autosave'][$lang], $charset, $lang); ?>&nbsp;</label>
              </div>
              <?php } ?>
            </td>
            <td nowrap="nowrap" align="right">
              <?php echo showtranslator ($site, $tagname."_".$id, "f", $charset, $lang); ?>
            </td>
          </tr>
          <tr>
            <td colspan="2"> 
              <?php echo showeditor ($site, $tagname, $id, $contentbot, $width, $height, $toolbar, $lang, $dpi); ?>
            </td>
          </tr>
        </table>
      </form>
    </div>
    
    <!-- auto save -->
    <div id="messageLayer" style="position:absolute; width:300px; height:40px; z-index:999999; left: 150px; top: 120px; visibility: hidden">
      <table width="300" height="40" border=0 cellspacing=0 cellpadding=3 class="hcmsMessage">
        <tr>
          <td align="center" valign="top">
            <div style="width:100%; height:100%; z-index:10; overflow:auto;">
              <?php echo getescapedtext ($hcms_lang['autosave'][$lang], $charset, $lang); ?>
            </div>
          </td>
        </tr>
      </table>
    </div>
    
    <?php if (intval ($mgmt_config['autosave']) > 0) { ?>
    <script type="text/javascript">
    function autosave ()
    {
      var test = $("#autosave").is(":checked");
      
      if (test == true)
      {
        for (var i in CKEDITOR.instances)
        {
          CKEDITOR.instances[i].updateElement();
        }
        
        hcms_showHideLayers ('messageLayer','','show');
        $("#savetype").val('auto');
        
        $.post(
          "<?php echo $mgmt_config['url_path_cms']; ?>service/savecontent.php", 
          $("#hcms_formview").serialize(), 
          function (data)
          {
            if (data.message.length !== 0)
            {
              alert (hcms_entity_decode(data.message));
            }
            		
            setTimeout ("hcms_showHideLayers('messageLayer','','hide')", 1500);
          }, 
          "json"
        );
      }
      
      setTimeout ('autosave()', <?php echo intval ($mgmt_config['autosave']) * 1000; ?>);
    }
    
    setTimeout ('autosave()', <?php echo intval ($mgmt_config['autosave']) * 1000; ?>);
    </script>
    <?php } ?>
    
  </body>
</html>
