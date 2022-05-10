<?php
/*
 * This file is part of
 * hyper Content & Digital Management Server - http://www.hypercms.com
 * Copyright (c) by hyper CMS Content Management Solutions GmbH
 */

// session
define ("SESSION", "create");
// management configuration
require ("config.inc.php");
// hyperCMS API
require ("function/hypercms_api.inc.php");


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
$list = getrequest_esc ("list");
$file = getrequest ("file");
$onlylist = getrequest ("onlylist");
$display = getrequest ("display");
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

// initialize
$add_onload = "";
$add_constraint = "";
$editor = "";

// load object file and get container
$objectdata = loadfile ($location, $page);
$contentfile = getfilename ($objectdata, "content");
$container_id = substr ($contentfile, 0, strpos ($contentfile, ".xml")); 

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

header ('Content-Type: text/html; charset='.$charset);

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
  $filedata = loadcontainer ($contentfile, "work", $user);

  if ($filedata != "")
  {
    $temp_array = selectcontent ($filedata, "<text>", "<text_id>", $id);
    if (!empty ($temp_array[0])) $temp_array = getcontent ($temp_array[0], "<textcontent>");
    if (!empty ($temp_array[0])) $contentbot = $temp_array[0];
  }
}

// set default value given eventually by tag
if (empty ($contentbot) && !empty ($default)) $contentbot = $default;

// encode script code
$contentbot = scriptcode_encode ($contentbot);

// get list options
$list_result = "";

if (!empty ($list) || !empty ($file))
{
  // replace | by comma
  $list_result .= str_replace ("|", ",", $list);

  // extract source file (file path or URL) for text list
  if ($file != "")
  {
    $list_result .= ",".getlistelements ($file);
  }

  // get list entries
  $list_result = trim ($list_result, ",");
}

// create secure token
$token = createtoken ($user);

// label
if ($label == "") $label = $id;

// taxonomy tree view
if ($display == "taxonomy")
{
  // list_sourcefile must be a valid taxonomy path: %taxonomy%/site/language-code/taxonomy-ID/taxonomy-child-levels
  $editor = "
  <div class=\"hcmsFormRowLabel ".$tagname."_".$id."\">
  <b>".$label."</b>
  </div>
  <div class=\"hcmsFormRowContent ".$tagname."_".$id."\" style=\"position:relative; width:".$width.(strpos ($width, "%") > 0 ? "" : "px")."; height:".$height."px;\">
    ".showtaxonomytree ($site, $container_id, $id, $tagname, $lang, $file, $width, $height, $charset)."
  </div>";
}
// keyword list view (default)
else
{
  // constraints do not apply for the taxonomy tree (checkboxes)
  if ($constraint != "") $add_constraint = "check = validateForm('".$tagname."_".$id."', '".$label."', '".$constraint."');";

  // get list entries
  if (!empty ($list_result))
  {
    // replace line breaks
    $list_result = str_replace ("\r\n", ",", $list_result);
    $list_result = str_replace ("\n", ",", $list_result);
    $list_result = str_replace ("\r", ",", $list_result);
    // escape single quotes
    $list_result = str_replace ("'", "\\'", $list_result);
    // create array
    $list_array = explode (",", $list_result);
    // create keyword string for Javascript
    $keywords = "['".implode ("', '", $list_array)."']";

    $keywords_tagit = "availableTags:".$keywords.", ";

    if (strtolower ($onlylist) == "yes" || strtolower ($onlylist) == "true" || strtolower ($onlylist) == "1")
    {
      $keywords_tagit .= "beforeTagAdded: function(event, ui) { if ($.inArray(ui.tagLabel, ".$keywords.") == -1) { return false; } }, ";
    }
  }
  else $keywords_tagit = "availableTags:[], ";

  // onload event
  $add_onload = "$('#".$tagname."_".$id."').tagit({".$keywords_tagit.(!empty ($readonly) ? "readOnly:true, " : "")."singleField:true, allowSpaces:true, singleFieldDelimiter:',', singleFieldNode:$('#".$tagname."_".$id."')});";

  $editor = "
  <div class=\"hcmsFormRowLabel ".$tagname."_".$id."\">
    <b>".$label."</b>
    </div>
    <div class=\"hcmsFormRowContent ".$tagname."_".$id."\" style=\"position:relative; width:".$width.(strpos ($width, "%") > 0 ? "" : "px").";\">
    <input type=\"hidden\" name=\"".$tagname."[".$id."]\" value=\"".$contentbot."\" />
    <input type=\"text\" id=\"".$tagname."_".$id."\" name=\"".$tagname."_".$id."\" style=\"width:".$width."px;\" value=\"".$contentbot."\" />
    <div id=\"".$tagname."_".$id."_protect\" style=\"position:absolute; top:0; left:0; width:".$width."px; height:100%; display:none;\"></div>
  </div>";
}
?>
<!DOCTYPE html>
<html>
<head>
<title>hyperCMS</title>
<meta charset="<?php echo $charset; ?>" />
<link rel="stylesheet" href="<?php echo getthemelocation(); ?>css/main.css?v=<?php echo getbuildnumber(); ?>" />
<link rel="stylesheet" href="<?php echo getthemelocation()."css/".($is_mobile ? "mobile.css" : "desktop.css"); ?>?v=<?php echo getbuildnumber(); ?>" />
<script type="text/javascript" src="javascript/main.min.js?v=<?php echo getbuildnumber(); ?>"></script>
<!-- JQuery and JQuery UI -->
<script type="text/javascript" src="<?php echo cleandomain ($mgmt_config['url_path_cms']); ?>javascript/jquery/jquery.min.js"></script>
<script type="text/javascript" src="<?php echo cleandomain ($mgmt_config['url_path_cms']); ?>javascript/jquery-ui/jquery-ui.min.js"></script>
<link type="text/css" rel="stylesheet" href="<?php echo cleandomain ($mgmt_config['url_path_cms']); ?>javascript/jquery-ui/jquery-ui.min.css" />
<!-- Tagging -->
<script type="text/javascript" src="<?php echo cleandomain ($mgmt_config['url_path_cms']); ?>javascript/tag-it/tag-it.min.js"></script>
<link rel="stylesheet" type="text/css" href="<?php echo cleandomain ($mgmt_config['url_path_cms']); ?>javascript/tag-it/jquery.tagit.css" />
<link rel="stylesheet" type="text/css" href="<?php echo cleandomain ($mgmt_config['url_path_cms']); ?>javascript/tag-it/tagit.ui-zendesk.css" />
<script type="text/javascript">

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
          if (p<1 || p==(val.length-1)) errors+='<?php echo getescapedtext ($hcms_lang['value-must-contain-an-e-mail-address'][$lang], $charset, $lang); ?>.\n';
        } 
        else if (test!='R') 
        { 
          num = parseFloat(val);
          if (isNaN(val)) errors+='<?php echo getescapedtext ($hcms_lang['value-must-contain-a-number'][$lang], $charset, $lang); ?>.\n';
          if (test.indexOf('inRange') != -1) 
          { 
            p=test.indexOf(':');
            if(test.substring(0,1) == 'R') {
              min=test.substring(8,p); 
            } else {
              min=test.substring(7,p); 
            }
            max=test.substring(p+1);
            if (num<min || max<num) errors+='<?php echo getescapedtext ($hcms_lang['value-must-contain-a-number-between'][$lang], $charset, $lang); ?> '+min+' - '+max+'.\n';
          } 
        } 
      } 
      else if (test.charAt(0) == 'R') errors += '<?php echo getescapedtext ($hcms_lang['a-value-is-required'][$lang], $charset, $lang); ?>.\n'; 
    }
  } 
    
  if (errors) 
  {
    alert(hcms_entity_decode('<?php echo getescapedtext ($hcms_lang['the-input-is-not-valid'][$lang], $charset, $lang); ?>:\n'+errors));
    return false;
  }  
  else return true;
}
  
function submitText (selectname, targetname)
{
  if (document.forms['hcms_formview'].elements[targetname])
  {
    document.forms['hcms_formview'].elements[targetname].value = document.forms['hcms_formview'].elements[selectname].value;
  }
}

function setsavetype (type)
{
  var check = true;
  <?php echo $add_constraint; ?>

  if (check == true)
  { 
    document.forms['hcms_formview'].elements['savetype'].value = type;
    <?php if ($display != "taxonomy") { ?>
    submitText ('<?php echo $tagname."_".$id ?>', '<?php echo $tagname."[".$id."]"; ?>');
    <?php } ?>
    document.forms['hcms_formview'].submit();
    return true;
  }  
  else return false;
}

function hcms_saveEvent ()
{
  setsavetype('editork_so');
}
</script>
</head>

<body class="hcmsWorkplaceGeneric" onload="<?php if (!empty ($add_onload)) echo $add_onload; ?>">

<!-- top bar -->
<?php echo showtopbar ($label, $lang, $mgmt_config['url_path_cms']."page_view.php?site=".url_encode($site)."&cat=".url_encode($cat)."&location=".url_encode($location_esc)."&page=".url_encode($page), "objFrame"); ?>

<!-- form for content -->
<div class="hcmsWorkplaceFrame">
  <form name="hcms_formview" method="post" action="<?php echo $mgmt_config['url_path_cms']; ?>service/savecontent.php">
    <input type="hidden" name="contenttype" value="<?php echo $contenttype; ?>" />
    <input type="hidden" name="site" value="<?php echo $site; ?>" />
    <input type="hidden" name="cat" value="<?php echo $cat; ?>" />
    <input type="hidden" name="location" value="<?php echo $location_esc; ?>" />
    <input type="hidden" name="page" value="<?php echo $page; ?>" />
    <input type="hidden" name="db_connect" value="<?php echo $db_connect; ?>" />
    <input type="hidden" name="tagname" value="<?php echo $tagname; ?>" />
    <input type="hidden" name="id" value="<?php echo $id; ?>" />
    <input type="hidden" name="list" value="<?php echo $list; ?>" />
    <input type="hidden" name="file" value="<?php echo $file; ?>" />
    <input type="hidden" name="onlylist" value="<?php echo $onlylist; ?>" />
    <input type="hidden" name="display" value="<?php echo $display; ?>" />
    <input type="hidden" name="constraint" value="<?php echo $constraint; ?>" />
    <input type="hidden" name="width" value="<?php echo $width; ?>" />
    <input type="hidden" name="height" value="<?php echo $height; ?>" />
    <input type="hidden" name="savetype" value="" />
    <input type="hidden" name="token" value="<?php echo $token; ?>" />

    <table class="hcmsTableStandard">
      <tr>
        <td>
          <img name="Button_so" src="<?php echo getthemelocation(); ?>img/button_save.png" class="hcmsButton hcmsButtonSizeSquare" onClick="setsavetype('editork_so');" alt="<?php echo getescapedtext ($hcms_lang['save'][$lang], $charset, $lang); ?>" title="<?php echo getescapedtext ($hcms_lang['save'][$lang], $charset, $lang); ?>" />    
          <img name="Button_sc" src="<?php echo getthemelocation(); ?>img/button_saveclose.png" class="hcmsButton hcmsButtonSizeSquare" onClick="setsavetype('editork_sc');" alt="<?php echo getescapedtext ($hcms_lang['save-and-close'][$lang], $charset, $lang); ?>" title="<?php echo getescapedtext ($hcms_lang['save-and-close'][$lang], $charset, $lang); ?>" />
         </td>
        </tr>
        <tr>
          <td>
            <?php echo $editor; ?>
        </td>
      </tr>
    </table>
  </form>
</div>

<?php includefooter(); ?>

</body>
</html>