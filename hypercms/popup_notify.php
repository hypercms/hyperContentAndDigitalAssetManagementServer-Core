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
require_once ("language/popup_notify.inc.php");


// input parameters
$multiobject = getrequest_esc ("multiobject");
$location = getrequest_esc ("location", "locationname");
$folder = getrequest_esc ("folder", "objectname");
$page = getrequest_esc ("page", "objectname");
$action = getrequest ("action");
$oncreate = getrequest ("oncreate");
$onedit = getrequest ("onedit");
$onmove = getrequest ("onmove");
$ondelete = getrequest ("ondelete");
$notify_id = getrequest ("notify_id", "array");
$token = getrequest_esc ("token");

// set current location
if ($folder != "") $location_curr = $location.$folder."/";
else $location_curr = $location;

// get publication and category
$site = getpublication ($location_curr);
$cat = getcategory ($site, $location_curr); 

// publication management config
if (valid_publicationname ($site)) require ($mgmt_config['abs_path_data']."config/".$site.".conf.php");

// ------------------------------ permission section --------------------------------

// check access permissions
$ownergroup = accesspermission ($site, $location_curr, $cat);
$setlocalpermission = setlocalpermission ($site, $ownergroup, $cat);

$access_allowed = true;
if ($ownergroup == false || $setlocalpermission['root'] != 1 || !valid_publicationname ($site) || !valid_locationname ($location)) $access_allowed = false;

// check session of user
checkusersession ($user, false);

// --------------------------------- logic section ----------------------------------

$message = "";

// show info if access is not allowed
if (!$access_allowed)
{
  echo showinfopage ($text9[$lang], $lang);
  exit;
}

// check if location is converted (escaped)
if (substr_count ($location, "%comp%") > 0 || substr_count ($location, "%page%") > 0) $location_esc = $location;
else $location_esc = convertpath ($site, $location, $cat);

// set notifications
if ($action == "save" && checktoken ($token, $user) && valid_publicationname ($site) && $cat != "" && valid_locationname ($location))
{
  // prepare multiobject array
  if ($multiobject != "")
  {
    $multiobject_array = link_db_getobject ($multiobject);
  }
  // or define multiobject array based on given location and object
  elseif ($site != "" && $location != "")
  {     
    if ($folder != "") $multiobject_array[] = convertpath ($site, $location.$folder."/.folder", $cat); 
    else $multiobject_array[] = convertpath ($site, $location.$page, $cat); 
  }

  // save in publish queue
  if (is_array ($multiobject_array))
  {  
    $result = false;
    
    $events = array();
    $events['oncreate'] = $oncreate;
    $events['onedit'] = $onedit;
    $events['onmove'] = $onmove;
    $events['ondelete'] = $ondelete;
       
    foreach ($multiobject_array as $multiobject)
    {
      if ($multiobject != "")
      {
        $result = rdbms_createnotification ($multiobject, $events, $user);
      }
    }
    
    if ($result == false) $message = $text7[$lang];
    else $message = "<script language=\"JavaScript\" type=\"text/javascript\"> window.close(); </script>";
  }
  else $message = $text8[$lang];
}
// remove notifications
elseif ($action == "delete" && checktoken ($token, $user) && is_array ($notify_id))
{
  foreach ($notify_id as $id)
  { 
    $result = rdbms_deletenotification ($id);
  }
  
  if ($result == false) $message = $text7[$lang];
}
?>
<!DOCTYPE html>
<html>
<head>
<title>hyperCMS</title>
<meta name="viewport" content="width=device-width; initial-scale=1.0; user-scalable=1;">
<meta http-equiv="Content-Type" content="text/html; charset=<?php echo $lang_codepage[$lang]; ?>">
<link rel="stylesheet" href="<?php echo getthemelocation(); ?>css/main.css">
<script src="javascript/main.js" type="text/javascript"></script>

<link rel="stylesheet" type="text/css" href="javascript/rich_calendar/rich_calendar.css">
<script language="JavaScript" type="text/javascript" src="javascript/rich_calendar/rich_calendar.js"></script>
<script language="JavaScript" type="text/javascript" src="javascript/rich_calendar/rc_lang_en.js"></script>
<script language="JavaScript" type="text/javascript" src="javascript/rich_calendar/rc_lang_de.js"></script>
<script language="Javascript" type="text/javascript" src="javascript/rich_calendar/domready.js"></script>
<script language="JavaScript" type="text/javascript">
<!--
function submitform ()
{
  if (document.forms['notify'].elements['oncreate'].checked == false && 
      document.forms['notify'].elements['onedit'].checked == false &&
      document.forms['notify'].elements['onmove'].checked == false &&
      document.forms['notify'].elements['ondelete'].checked == false
  )
  {
    alert(hcms_entity_decode("<?php echo $text6[$lang]; ?>"));
  }
  else
  { 
    document.forms['notify'].submit();
  }
}
-->
</script>
</head>

<body class="hcmsWorkplaceGeneric">

<!-- top bar -->
<?php
echo showtopbar ($text0[$lang], $lang);
?>

<?php echo showmessage ($message, 360, 70, $lang, "position:fixed; left:15px; top:15px;"); ?>

<form name="notify" method="post" action="<?php echo $_SERVER['PHP_SELF']; ?>">
  <input type="hidden" name="action" value="save" />      
  <input type="hidden" name="location" value="<?php echo $location_esc; ?>" />
  <input type="hidden" name="page" value="<?php echo correctfile ($location, $page, $user); ?>" />        
  <input type="hidden" name="folder" value="<?php echo $folder; ?>" />
  <input type="hidden" name="multiobject" value="<?php echo $multiobject; ?>" />
  <input type="hidden" name="token" value="<?php echo $token; ?>" /> 
  
  <table width="100%" border=0 cellpadding="3" cellspacing="0">
    <tr> 
      <td align="left">
        <input type="checkbox" name="oncreate" value="1"/> <?php echo $text1[$lang]; ?><br />
        <input type="checkbox" name="onedit"  value="1" /> <?php echo $text2[$lang]; ?><br />
        <input type="checkbox" name="onmove" value="1" /> <?php echo $text3[$lang]; ?><br />
        <input type="checkbox" name="ondelete" value="1" /> <?php echo $text4[$lang]; ?><br />
	    </td>
    </tr>
    <tr>  
      <td align="left">  
        &nbsp;<?php echo $text5[$lang]; ?>: <img name="Button" src="<?php echo getthemelocation(); ?>img/button_OK.gif" class="hcmsButtonTinyBlank hcmsButtonSizeSquare" onClick="submitform();" onMouseOut="hcms_swapImgRestore()" onMouseOver="hcms_swapImage('Button','','<?php echo getthemelocation(); ?>img/button_OK_over.gif',1)" align="absmiddle" title="OK" alt="OK" />
      </td>
    </tr>
  </table>
</form>

<?php
$notify_array = rdbms_getnotification ("", "", $user);

if (is_array ($notify_array))
{
  echo "  <form name=\"delete\" method=\"post\" action=\"".$_SERVER['PHP_SELF']."\">
  <input type=\"hidden\" name=\"action\" value=\"delete\" />
  <input type=\"hidden\" name=\"location\" value=\"".$location_esc."\" />
  <input type=\"hidden\" name=\"page\" value=\"".correctfile ($location, $page, $user)."\" />        
  <input type=\"hidden\" name=\"folder\" value=\"".$folder."\" />
  <input type=\"hidden\" name=\"multiobject\" value=\"".$multiobject."\" />
  <input type=\"hidden\" name=\"token\" value=\"".$token."\" />
  
  <div style=\"width:550px; margin:10px 4px 0px 4px;\">
  <table width=\"100%\" border=\"0\" cellpadding=\"2\" cellspacing=\"0\">
    <tr>
      <td><strong>".$text10[$lang]."</strong></td>
      <td width=\"22\"><img src=\"".getthemelocation()."img/button_file_new.gif\" align=\"absmiddle\" title=\"".$text1[$lang]."\" /></td>
      <td width=\"22\"><img src=\"".getthemelocation()."img/button_file_edit.gif\" align=\"absmiddle\" title=\"".$text2[$lang]."\" /></td>
      <td width=\"22\"><img src=\"".getthemelocation()."img/button_file_cut.gif\" align=\"absmiddle\" title=\"".$text3[$lang]."\" /></td>
      <td width=\"22\"><img src=\"".getthemelocation()."img/button_file_delete.gif\" align=\"absmiddle\" title=\"".$text4[$lang]."\" /></td>
    </tr>
  </table>
  </div>
  
  <div style=\"width:550px; height:200px; border:1px solid #000000; margin:0px 4px 4px 4px; overflow:auto;\">
  <table width=\"100%\" border=\"0\" cellpadding=\"2\" cellspacing=\"0\">\n";
  
  foreach ($notify_array as $notify)
  {
    $site = getpublication ($notify['objectpath']);
    $cat = getcategory ($site, $notify['objectpath']);
    $objectpath = getlocationname ($site, $notify['objectpath'], $cat);
    $objectinfo = getfileinfo ($site, $notify['objectpath'], $cat);
  
    echo "    <tr>
    <td width=\"22\"><input type=\"checkbox\" name=\"notify_id[]\" value=\"".$notify['notify_id']."\" /></td>
    <td><div title=\"".$objectpath."\"><img src=\"".getthemelocation()."img/".$objectinfo['icon']."\" align=\"absmiddle\" />&nbsp;".getobject($objectpath)."</div></td>
    <td width=\"22\"><input type=\"checkbox\" disabled=\"disabled\" ",($notify['oncreate'] > 0 ? "checked=\"checked\"" : ""),"\" /></td>
    <td width=\"22\"><input type=\"checkbox\" disabled=\"disabled\" ",($notify['onedit'] > 0 ? "checked=\"checked\"" : ""),"\" /></td>
    <td width=\"22\"><input type=\"checkbox\" disabled=\"disabled\" ",($notify['onmove'] > 0 ? "checked=\"checked\"" : ""),"\" /></td>
    <td width=\"22\"><input type=\"checkbox\" disabled=\"disabled\" ",($notify['ondelete'] > 0 ? "checked=\"checked\"" : ""),"\" /></td>
  </tr>\n";
  }
  
  echo "  </table>
  </div>
  </form>\n";
}

if (is_array ($notify_array)) echo "&nbsp;".$text11[$lang].": <img name=\"Button2\" src=\"".getthemelocation()."img/button_OK.gif\" class=\"hcmsButtonTinyBlank hcmsButtonSizeSquare\" onClick=\"document.forms['delete'].submit();\" onMouseOut=\"hcms_swapImgRestore()\" onMouseOver=\"hcms_swapImage('Button2','','".getthemelocation()."img/button_OK_over.gif',1)\" align=\"absmiddle\" title=\"OK\" alt=\"OK\" />\n";
?>

</body>
</html>