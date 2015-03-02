<?php
/*
 * This file is part of
 * hyper Content Management Server - http://www.hypercms.com
 * Copyright (c) by hyper CMS Content Management Solutions GmbH
 *
 * You should have received a copy of the License along with hyperCMS.
 */

// session parameters
require ("../include/session.inc.php");
// management configuration
require ("../config.inc.php");
// hyperCMS API
require ("../function/hypercms_api.inc.php");
// hyperCMS UI
require ("../function/hypercms_ui.inc.php");


// input parameters
$location = getrequest_esc ("location", "locationname");
$page = getrequest_esc ("page", "objectname");
$contenttype = getrequest_esc ("contenttype");
$contentbot = getrequest ("contentbot");
$db_connect = getrequest_esc ("db_connect", "objectname");
$id = getrequest_esc ("id", "objectname");
$label = getrequest_esc ("label");
$tagname = getrequest_esc ("tagname", "objectname");
$value = getrequest_esc ("value");
$default = getrequest_esc ("default");
$token = getrequest ("token");

// get publication and category
$site = getpublication ($location);
$cat = getcategory ($site, $location);

// publication management config
if (valid_publicationname ($site)) require ($mgmt_config['abs_path_data']."config/".$site.".conf.php");

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

// create secure token
$token = createtoken ($user);
?>
<!DOCTYPE html>
<html>
<head>
<title>hyperCMS</title>
<meta http-equiv="Content-Type" content="text/html; charset=<?php echo $charset; ?>">
<link rel="stylesheet" href="<?php echo getthemelocation(); ?>css/main.css">
<script src="../javascript/main.js" type="text/javascript">
</script>
<script language="JavaScript">
<!--
function setsavetype(type)
{
  document.forms['editor'].elements['savetype'].value = type;
  document.forms['editor'].submit();
}
//-->
</script>
</head>

<body class="hcmsWorkplaceGeneric" leftmargin=3 topmargin=3 marginwidth=0 marginheight=0>
<?php
// read content using db_connect
if ($contentbot == "")
{
  if (!empty ($db_connect) && $db_connect != false && file_exists ($mgmt_config['abs_path_data']."db_connect/".$db_connect)) 
  {
    include ($mgmt_config['abs_path_data']."db_connect/".$db_connect);
    
    $db_connect_data = db_read_text ($site, $contentfile, "", $id, "", $user);
    
    if ($db_connect_data != false) $contentbot = $db_connect_data['text'];
    else $contentbot = false;
  }  
  else $contentbot = false;
  
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
}

// set default value given eventually by tag
if ($contentbot == "" && $default != "") $contentbot = $default;

if ($value == $contentbot) $checked = " checked";
else $checked = "";

if ($label == "") $label = $id;
?>

<!-- top bar -->
<?php echo showtopbar ($label, $lang, $mgmt_config['url_path_cms']."page_view.php?site=".url_encode($site)."&cat=".url_encode($cat)."&location=".url_encode($location_esc)."&page=".url_encode($page), "objFrame"); ?>

<!-- form for content -->
<div style="padding:0; width:100%; z-index:1;">
  <form name="editor" method="post" action="<?php echo $mgmt_config['url_path_cms']; ?>page_save.php">
    <input type="hidden" name="contenttype" value="<?php echo $contenttype; ?>">
    <input type="hidden" name="site" value="<?php echo $site; ?>">
    <input type="hidden" name="cat" value="<?php echo $cat; ?>">
    <input type="hidden" name="location" value="<?php echo $location_esc; ?>">
    <input type="hidden" name="page" value="<?php echo $page; ?>">
    <input type="hidden" name="db_connect" value="<?php echo $db_connect; ?>">
    <input type="hidden" name="tagname" value="<?php echo $tagname; ?>">
    <input type="hidden" name="id" value="<?php echo $id; ?>">
    <input type="hidden" name="value" value="<?php echo $value; ?>">
    <input type="hidden" name="savetype" value="">
    <input type="hidden" name="<?php echo $tagname."[".$id."]"; ?>" value="">
    <input type="hidden" name="token" value="<?php echo $token; ?>">
    
    <table border="0" cellspacing="2">
      <tr>
        <td>
        <img name="Button_so" src="<?php echo getthemelocation(); ?>img/button_save.gif" class="hcmsButton hcmsButtonSizeSquare" onClick="setsavetype('editorc_so');" alt="<?php echo getescapedtext ($hcms_lang['save'][$lang], $charset, $lang); ?>" title="<?php echo getescapedtext ($hcms_lang['save'][$lang], $charset, $lang); ?>" />
        <img name="Button_sc" src="<?php echo getthemelocation(); ?>img/button_saveclose.gif" class="hcmsButton hcmsButtonSizeSquare" onClick="setsavetype('editorc_sc');" alt="<?php echo getescapedtext ($hcms_lang['save-and-close'][$lang], $charset, $lang); ?>" title="<?php echo getescapedtext ($hcms_lang['save-and-close'][$lang], $charset, $lang); ?>" />
        <br />
        <input type="checkbox" name="<?php echo $tagname."[".$id."]"; ?>" value="<?php echo $value; ?>"<?php echo $checked; ?>> <?php echo $value; ?>
        </td>
      </tr>
    </table>
  </form>
</div>

</body>
</html>
