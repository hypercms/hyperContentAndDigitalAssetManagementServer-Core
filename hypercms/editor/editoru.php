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
// load language file
require_once ("../language/editoru.inc.php");


// input parameters
$location = getrequest_esc ("location", "locationname");
$page = getrequest_esc ("page", "objectname");
$contenttype = getrequest_esc ("contenttype");
$db_connect = getrequest_esc ("db_connect", "objectname");
$id = getrequest_esc ("id", "objectname", "", true);
$label = getrequest_esc ("label");
$tagname = getrequest_esc ("tagname", "objectname", "", true);
$width = getrequest_esc ("width", "numeric");
$height = getrequest_esc ("height", "numeric");
$constraint = getrequest_esc ("constraint", false, "", true);
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

// include publication target settings
if (valid_publicationname ($site)) $publ_config = parse_ini_file ($mgmt_config['abs_path_rep']."config/".$site.".ini"); 

// define content-type if not set
if ($contenttype == "") 
{
  $contenttype = "text/html; charset=".$mgmt_config[$site]['default_codepage'];
  $charset = $mgmt_config[$site]['default_codepage'];
}
elseif (strpos ($contenttype, "charset") > 0) $charset = getattribute ($contenttype, "charset");

// define constraint
if ($constraint != "") $add_constraint = "check = validateForm('".$tagname."_".$id."','','".$constraint."');\n";
else $add_constraint = "check = true;\n";

// read content using db_connect
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

// escape special characters
$contentbot = str_replace (array("\"", "<", ">"), array("&quot;", "&lt;", "&gt;"), $contentbot);   

// define default editor size
if ($height == false || $height <= 0) $height = "300";
if ($width == false || $width <= 0) $width = "600";

// create secure token
$token = createtoken ($user);
?>
<!DOCTYPE html>
<html>
<head>
<title>hyperCMS</title>
<meta http-equiv="Content-Type" content="<?php echo $contenttype; ?>">
<link rel="stylesheet" href="<?php echo getthemelocation(); ?>css/main.css">
<script src="../javascript/jquery/jquery-1.7.1.min.js" type="text/javascript"></script>
<script src="../javascript/main.js" type="text/javascript"></script>
<script language="JavaScript">
<!--
function validateForm() 
{
  var i,p,q,nm,test,num,min,max,errors='',args=validateForm.arguments;
  
  for (i=0; i<(args.length-2); i+=3) 
  { 
    test=args[i+2]; val=hcms_findObj(args[i]);
    
    if (val) 
    { 
      nm=val.name; 
      
      if ((val=val.value)!="") 
      {
        if (test.indexOf('isEmail')!=-1) 
        { 
          p=val.indexOf('@');
          if (p<1 || p==(val.length-1)) errors+='<?php echo $text3[$lang]; ?>.\n';
        } 
        else if (test!='R') 
        { 
          num = parseFloat(val);
          if (isNaN(val)) errors+='<?php echo $text4[$lang]; ?>.\n';
          if (test.indexOf('inRange') != -1) 
          { 
            p=test.indexOf(':');
            if(test.substring(0,1) == 'R') {
              min=test.substring(8,p); 
            } else {
              min=test.substring(7,p); 
            }
            max=test.substring(p+1);
            if (num<min || max<num) errors+='<?php echo $text5[$lang]; ?> '+min+' - '+max+'.\n';
          } 
        } 
      } 
      else if (test.charAt(0) == 'R') errors += '<?php echo $text6[$lang]; ?>.\n'; 
    }
  } 
  
  if (errors) 
  {
    alert(hcms_entity_decode('<?php echo $text7[$lang]; ?>:\n'+errors));
    return false;
  }  
  else return true;
}

function submitText(selectname, targetname)
{
  document.forms['hcms_formview'].elements[targetname].value = document.forms['hcms_formview'].elements[selectname].value;
}

function setsavetype(type)
{
  <?php echo $add_constraint; ?>
  
  if (check == true)
  { 
    document.forms['hcms_formview'].elements['savetype'].value = type;
    submitText ('<?php echo $tagname."_".$id ?>', '<?php echo $tagname."[".$id."]"; ?>');
    document.forms['hcms_formview'].submit();
    return true;
  }  
  else return false;
}
<?php if (intval ($mgmt_config['autosave']) > 0) { ?>
function autosave ()
{
	var test = $("#autosave").is(":checked");
  
	if (test == true)
  {
		hcms_showHideLayers('messageLayer','','show');
		$("#savetype").val('auto');
    submitText ('<?php echo $tagname."_".$id ?>', '<?php echo $tagname."[".$id."]"; ?>');
    
    <?php echo $add_constraint; ?>
    
    if(check == true) {
      $.post(
        "<?php echo $mgmt_config['url_path_cms']; ?>page_save.php", 
        $("#hcms_formview").serialize(), 
        function(data)
        {
          if(data.message.length !== 0)
          {
            alert(hcms_entity_decode(data.message));
          }				
          setTimeout("hcms_showHideLayers('messageLayer','','hide')", 1500);
        }, 
        "json"
      );
    } else {
      hcms_showHideLayers('messageLayer','','hide');
    }
	}
	setTimeout('autosave()', <?php echo intval ($mgmt_config['autosave']) * 1000; ?>);
}
setTimeout('autosave()', <?php echo intval ($mgmt_config['autosave']) * 1000; ?>);
<?php } ?>
//-->
</script>
</head>

<body class="hcmsWorkplaceGeneric" leftmargin=3 topmargin=3 marginwidth=0 marginheight=0>

  <!-- top bar -->
  <?php
  if ($label == "") $label = $id;
  
  echo showtopbar ($label, $lang, $mgmt_config['url_path_cms']."page_view.php?site=".url_encode($site)."&cat=".url_encode($cat)."&location=".url_encode($location_esc)."&page=".url_encode($page), "objFrame");
  ?>

  <!-- form for content -->
  <div style="padding:0; width:100%; z-index:1;">
    <form name="hcms_formview" id="hcms_formview" method="post" action="<?php echo $mgmt_config['url_path_cms']; ?>page_save.php">
      <input type="hidden" name="contenttype" value="<?php echo $contenttype; ?>">
      <input type="hidden" name="site" value="<?php echo $site; ?>">
      <input type="hidden" name="cat" value="<?php echo $cat; ?>">
      <input type="hidden" name="location" value="<?php echo $location_esc; ?>">
      <input type="hidden" name="page" value="<?php echo $page; ?>">
      <input type="hidden" name="db_connect" value="<?php echo $db_connect; ?>">
      <input type="hidden" name="tagname" value="<?php echo $tagname; ?>">
      <input type="hidden" name="id" value="<?php echo $id; ?>">
      <input type="hidden" name="constraint" value="<?php echo $constraint; ?>">
      <input type="hidden" name="width" value="<?php echo $width; ?>">
      <input type="hidden" name="height" value="<?php echo $height; ?>">
      <input type="hidden" id="savetype" name="savetype" value="">
      <input type="hidden" name="token" value="<?php echo $token; ?>">
      
      <table border="0" cellspacing="2">
        <tr>
          <td nowrap="nowrap">
          <img name="Button_so" src="<?php echo getthemelocation(); ?>img/button_save.gif" class="hcmsButton hcmsButtonSizeSquare" onClick="setsavetype('editoru_so');" alt="<?php echo $text1[$lang]; ?>" title="<?php echo $text1[$lang]; ?>" align="absmiddle" />   
          <img name="Button_sc" src="<?php echo getthemelocation(); ?>img/button_saveclose.gif" class="hcmsButton hcmsButtonSizeSquare" onClick="setsavetype('editoru_sc');" alt="<?php echo $text2[$lang]; ?>" title="<?php echo $text2[$lang]; ?>" align="absmiddle" /> 
          <?php if (intval ($mgmt_config['autosave']) > 0) { ?>
            <div class="hcmsButton" style="height:22px;">
    		      <input type="checkbox" id="autosave" name="autosave" value="yes" checked="checked" /><label for="autosave">&nbsp;<?php echo $text8[$lang]; ?></label>
            </div>        
          <?php } ?>
          </td>
        </tr>
        <tr>
          <td colspan="2"> 
          <input type="hidden" name="<?php echo $tagname."[".$id."]"; ?>" />
          <textarea id="<?php echo $tagname."_".$id ?>" name="<?php echo $tagname."_".$id ?>" style="width:<?php echo $width; ?>px; height:<?php echo $height; ?>px;"><?php echo $contentbot; ?></textarea>
          </td>
        </tr>
      </table>
    </form>
  </div>
  
  <!-- auto save --> 
  <div id="messageLayer" style="position:absolute; width:300px; height:40px; z-index:6; left: 150px; top: 120px; visibility: hidden">
    <table width="300" height="40" border=0 cellspacing=0 cellpadding=3 class="hcmsMessage">
      <tr>
        <td align="center" valign="top">
          <div style="width:100%; height:100%; z-index:10; overflow:auto;">
          <?php echo $text8[$lang]; ?>
          </div>
        </td>
      </tr>
    </table>
  </div>

</body>
</html>
