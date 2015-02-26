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


// input parameters
$multiobject = getrequest_esc ("multiobject");
$location = getrequest_esc ("location", "locationname");
$folder = getrequest_esc ("folder", "objectname");
$page = getrequest_esc ("page", "objectname");
$filetype = getrequest_esc ("filetype", "objectname");
$media = getrequest_esc ("media", "objectname");
$action = getrequest_esc ("action");
$publish = getrequest ("publish");
$published_only = getrequest_esc ("published_only");
$publishdate = getrequest_esc ("publishdate");
$virtual = getrequest ("virtual");
$token = getrequest ("token");

// set current location
if ($action == "publish" || $action == "unpublish" || $mgmt_config[$site]['dam'] == true) $location_curr = $location.$folder."/";
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
if ($virtual != 1 && ($ownergroup == false || $setlocalpermission['root'] != 1 || $setlocalpermission['publish'] != 1 || !valid_publicationname ($site) || !valid_locationname ($location))) $access_allowed = false;

// check session of user
checkusersession ($user, false);

// --------------------------------- logic section ----------------------------------

$message = "";

// show info if access is not allowed
if (!$access_allowed)
{
  echo showinfopage ($hcms_lang['you-do-not-have-access-permissions-to-this-object'][$lang], $lang);
  exit;
}

// check if location is converted (escaped)
if (substr_count ($location, "%comp%") > 0 || substr_count ($location, "%page%") > 0) $location_esc = $location;
else $location_esc = convertpath ($site, $location, $cat);

if ($action != "" && valid_publicationname ($site) && $cat != "" && valid_locationname ($location) && $publish != "")
{
  // prepare queue entries for later publishing
  if ($publish == "later")
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
    
    // publish only already published objects
    if ($published_only == "") $published_only = "0";
    else $published_only = "1";    
    
    // save in publish queue
    if (is_array ($multiobject_array))
    {  
      $result = false;
         
      foreach ($multiobject_array as $multiobject)
      {
        if ($multiobject != "")
        {
          $result = rdbms_createqueueentry ($action, $multiobject, $publishdate, $published_only, $user);
        }
      }
      
      if ($result == false) $message = $hcms_lang['the-publishing-queue-could-not-be-saved'][$lang];
      else $message = "<script language=\"JavaScript\" type=\"text/javascript\"> window.close(); </script>";
    }
    else $message = $hcms_lang['no-objects-to-publish'][$lang];
  }
}
?>
<!DOCTYPE html>
<html>
<head>
<title>hyperCMS</title>
<meta name="viewport" content="width=device-width; initial-scale=1.0; user-scalable=1;">
<meta http-equiv="Content-Type" content="text/html; charset=<?php echo getcodepage ($lang); ?>">
<link rel="stylesheet" href="<?php echo getthemelocation(); ?>css/main.css">
<script src="javascript/main.js" type="text/javascript"></script>

<link rel="stylesheet" type="text/css" href="javascript/rich_calendar/rich_calendar.css">
<script language="JavaScript" type="text/javascript" src="javascript/rich_calendar/rich_calendar.js"></script>
<script language="JavaScript" type="text/javascript" src="javascript/rich_calendar/rc_lang_en.js"></script>
<script language="JavaScript" type="text/javascript" src="javascript/rich_calendar/rc_lang_de.js"></script>
<script language="Javascript" type="text/javascript" src="javascript/rich_calendar/domready.js"></script>
<script language="JavaScript" type="text/javascript">
<!--
var cal_obj = null;
var format = '%Y-%m-%d %H:%i';

// show calendar
function show_cal (el)
{
	if (cal_obj) return;

  var text_field = document.getElementById("text_field");

	cal_obj = new RichCalendar();
	cal_obj.start_week_day = 1;
	cal_obj.show_time = true;
	cal_obj.language = '<?php echo getcalendarlang ($lang); ?>';
	cal_obj.user_onchange_handler = cal_on_change;
	cal_obj.user_onautoclose_handler = cal_on_autoclose;
	cal_obj.parse_date(text_field.value, format);
	cal_obj.show_at_element(text_field, "adj_left-bottom");
}

// user defined onchange handler
function cal_on_change (cal, object_code)
{
	if (object_code == 'day')
	{
		document.getElementById("text_field").value = cal.get_formatted_date(format);
		document.getElementById("publishdate").value = cal.get_formatted_date(format);
		cal.hide();
		cal_obj = null;
	}
}

// user defined onautoclose handler
function cal_on_autoclose (cal)
{
	cal_obj = null;
}

function submitform ()
{
  if (document.forms['publish'].publish[0].checked == true)
  {
    document.forms['publish'].attributes['action'].value = "popup_status.php";
    document.forms['publish'].submit();
  }
  else if (document.forms['publish'].publish[1].checked == true)
  {
    if (document.forms['publish'].elements['publishdate'].value == "")
    {
      alert(hcms_entity_decode("<?php echo $hcms_lang['please-set-a-date-for-publishing'][$lang]; ?>"));
    }
    else
    {
      document.forms['publish'].attributes['action'].value = "popup_publish.php";      
      document.forms['publish'].submit();
    }
  }
}
-->
</script>
</head>

<body class="hcmsWorkplaceGeneric" leftmargin=3 topmargin=3 marginwidth=0 marginheight=0>

<!-- top bar -->
<?php
if ($action == "publish") echo $headline = $hcms_lang['publish-content'][$lang];
elseif ($action == "unpublish") echo $headline = $hcms_lang['unpublish-content'][$lang];

echo showtopbar ($headline, $lang);
?>

<?php echo showmessage ($message, 360, 70, $lang, "position:fixed; left:15px; top:15px;"); ?>

<form name="publish" method="post" action="">
  <input type="hidden" name="action" value="<?php echo $action; ?>" />
  <input type="hidden" name="force" value="start" />      
  <input type="hidden" name="location" value="<?php echo $location_esc; ?>" />
  <input type="hidden" name="page" value="<?php echo correctfile ($location, $page, $user); ?>" />
  <input type="hidden" name="filetype" value="<?php echo $filetype; ?>" />          
  <input type="hidden" name="media" value="<?php echo $media; ?>" />
  <input type="hidden" name="folder" value="<?php echo $folder; ?>" />
  <input type="hidden" name="multiobject" value="<?php echo $multiobject; ?>" />
  <input type="hidden" name="token" value="<?php echo $token; ?>" /> 
  
  <table width="100%" border=0 cellpadding="3" cellspacing="0">
    <tr> 
      <td align="left">
        <input name="publish" type="radio" value="now" checked="checked" /> <?php echo $hcms_lang['now'][$lang]; ?>
	    </td>
    </tr>
    <tr> 
      <td align="left">		
        <input name="publish" type="radio" value="later" /> <?php echo $hcms_lang['on-date'][$lang]; ?> 
        <input type="hidden" name="publishdate" id="publishdate" value="<?php echo $publishdate; ?>" />
        <input type="text" id="text_field" value="<?php echo $publishdate; ?>" disabled="disabled" />
        <img name="datepicker" src="<?php echo getthemelocation(); ?>img/button_datepicker.gif" onclick="show_cal(this);" align="absmiddle" class="hcmsButtonTiny hcmsButtonSizeSquare" alt="<?php echo $hcms_lang['select-date'][$lang]; ?>" title="<?php echo $hcms_lang['select-date'][$lang]; ?>" />
	    </td>
    </tr>
    <?php if ($action == "publish") { ?>
    <tr> 
      <td align="left">
        <input type="checkbox" name="published_only" value="1" /> <?php echo $hcms_lang['only-already-published-content'][$lang]; ?>
	    </td>
    </tr>
    <?php } ?>
    <tr>  
      <td align="left">  
        &nbsp;<?php echo $headline; ?>: <img name="Button" src="<?php echo getthemelocation(); ?>img/button_OK.gif" class="hcmsButtonTinyBlank hcmsButtonSizeSquare" onClick="submitform();" onMouseOut="hcms_swapImgRestore()" onMouseOver="hcms_swapImage('Button','','<?php echo getthemelocation(); ?>img/button_OK_over.gif',1)" align="absmiddle" title="OK" alt="OK" />
      </td>
    </tr>
  </table>
</form>

</body>
</html>