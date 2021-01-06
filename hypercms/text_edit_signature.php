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

// define constraint
if ($constraint != "") $add_constraint = "check = validateForm('".$tagname."_".$id."','','".$constraint."');\n";
else $add_constraint = "check = true;\n";

// read content using db_connect
if (!empty ($db_connect) && $db_connect != false && file_exists ($mgmt_config['abs_path_data']."db_connect/".$db_connect)) 
{
  include ($mgmt_config['abs_path_data']."db_connect/".$db_connect);
  
  $db_connect_data = db_read_text ($site, $contentfile, "", $id, "", $user);
  
  if ($db_connect_data != false) $contentbot = $db_connect_data['text'];
}

// read content from content container
if (empty ($contentbot)) 
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
if (!isset ($contentbot) && $default != "") $contentbot = $default;

// size
$style = "";

if ($width > 0) $style .= "width:".$width."px; ";
if ($height > 0) $style .= "height:".$height."px; ";

// existing signature
if (!empty ($contentbot)) $signature_image = "<img id=\"signatureimage_".$tagname."_".$id."\" onclick=\"$('#signatureimage_".$tagname."_".$id."').hide(); $('#signaturefield_".$tagname."_".$id."').show();\" src=\"data:".$contentbot."\" class=\"hcmsTextArea\" style=\"".$style." display:none; padding:0 !important; max-width:100%; max-height:100%;\" />";
else $signature_image = "";

if ($label == "") $label = $id;

// create secure token
$token = createtoken ($user);
?>
<!DOCTYPE html>
<html>
<head>
  <title>hyperCMS</title>
  <meta charset="<?php echo $charset; ?>" />
  <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1" />
  <!-- mobile Chrome, Safari, FireFox, Opera Mobile -->
  <meta name="viewport" content="initial-scale=1.0, width=device-width, user-scalable=no, target-densitydpi=device-dpi" />
  <link rel="stylesheet" href="<?php echo getthemelocation(); ?>css/main.css" />
  <link rel="stylesheet" href="<?php echo getthemelocation()."css/".($is_mobile ? "mobile.css" : "desktop.css"); ?>" />
  <script type="text/javascript" src="javascript/main.min.js"></script>
  <script type="text/javascript" src="javascript/jquery/jquery-3.5.1.min.js"></script>
  <script type="text/javascript" src="javascript/signature/jSignature.min.js"></script>
  <!--[if lt IE 9]>
  <script type="text/javascript" src="javascript/signature/flashcanvas.js"></script>
  <![endif]-->
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

  function resetSignature (id)
  {
    // clears the canvas and rerenders the decor on it
    $('#signature_'+id).jSignature('reset');
    // empty hidden field
    $('#'+id).val('');

    return false;
  }

  function setsavetype (type)
  {
    <?php echo $add_constraint; ?>
    
    if (check == true)
    { 
      document.forms['hcms_formview'].elements['savetype'].value = type;
      document.forms['hcms_formview'].submit();
      return true;
    }  
    else return false;
  }

  $(document).ready(function() {
    // initialize the jSignature widget
    $('#signature_<?php echo $tagname."_".$id; ?>').jSignature({ 'lineWidth': 2, 'decor-color': 'transparent' });

    $('#signature_<?php echo $tagname."_".$id; ?>').bind('change', function(e) {
      // create image (image = PNG, svgbase64 = SVG)
      if ($('#signature_<?php echo $tagname."_".$id; ?>').jSignature('getData', 'native').length > 0) 
      {
        var imagedata = $('#signature_<?php echo $tagname."_".$id; ?>').jSignature('getData', 'image');      
        // set image data string
        $('#<?php echo $tagname."_".$id; ?>').val(imagedata);
      }
      else $('#<?php echo $tagname."_".$id; ?>').val('');
    });

    // show existing signature image and hide signature field
    if ($('#signatureimage_<?php echo $tagname."_".$id; ?>').length)
    {
      $('#signatureimage_<?php echo $tagname."_".$id; ?>').show();
      $('#signaturefield_<?php echo $tagname."_".$id; ?>').hide();
    }
    else
    {
      $('#signaturefield_<?php echo $tagname."_".$id; ?>').show();
    }
  });
  </script>
</head>

<body class="hcmsWorkplaceGeneric">

<!-- top bar -->
<?php echo showtopbar ($label, $lang, $mgmt_config['url_path_cms']."page_view.php?site=".url_encode($site)."&cat=".url_encode($cat)."&location=".url_encode($location_esc)."&page=".url_encode($page), "objFrame"); ?>

<!-- form for content -->
<div class="hcmsWorkplaceFrame">
  <form name="hcms_formview" id="hcms_formview" method="post" action="<?php echo $mgmt_config['url_path_cms']; ?>service/savecontent.php">
    <input type="hidden" name="contenttype" value="<?php echo $contenttype; ?>" />
    <input type="hidden" name="site" value="<?php echo $site; ?>" />
    <input type="hidden" name="cat" value="<?php echo $cat; ?>" />
    <input type="hidden" name="location" value="<?php echo $location_esc; ?>" />
    <input type="hidden" name="page" value="<?php echo $page; ?>" />
    <input type="hidden" name="db_connect" value="<?php echo $db_connect; ?>" />
    <input type="hidden" name="tagname" value="<?php echo $tagname; ?>" />
    <input type="hidden" name="id" value="<?php echo $id; ?>" />
    <input type="hidden" name="constraint" value="<?php echo $constraint; ?>" />
    <input type="hidden" name="width" value="<?php echo $width; ?>" />
    <input type="hidden" name="height" value="<?php echo $height; ?>" />
    <input type="hidden" name="savetype" value="" />
    <input type="hidden" name="token" value="<?php echo $token; ?>" />
    
    <table class="hcmsTableStandard">
      <tr>
        <td>
          <img name="Button_so" src="<?php echo getthemelocation(); ?>img/button_save.png" class="hcmsButton hcmsButtonSizeSquare" onClick="setsavetype('editors_so');" alt="<?php echo getescapedtext ($hcms_lang['save'][$lang], $charset, $lang); ?>" title="<?php echo getescapedtext ($hcms_lang['save'][$lang], $charset, $lang); ?>" />
          <img name="Button_sc" src="<?php echo getthemelocation(); ?>img/button_saveclose.png" class="hcmsButton hcmsButtonSizeSquare" onClick="setsavetype('editors_sc');" alt="<?php echo getescapedtext ($hcms_lang['save-and-close'][$lang], $charset, $lang); ?>" title="<?php echo getescapedtext ($hcms_lang['save-and-close'][$lang], $charset, $lang); ?>" />
         </td>
       </tr>
       <tr>
         <td>
          <?php echo $signature_image; ?>
          <div id="signaturefield_<?php echo $tagname."_".$id; ?>" style="<?php echo $style; ?>">
            <div id="signature_<?php echo $tagname."_".$id ?>" style="border:2px dotted black; background-color:#FFFFFF; color:darkblue;"></div>
            <div style="position:relative; float:right; margin:-36px 5px 0px 0px;">
              <img src="<?php echo getthemelocation(); ?>img/button_delete.png" onclick="resetSignature('<?php echo $tagname."_".$id; ?>');" class="hcmsButtonTiny hcmsButtonSizeSquare" alt="<?php echo getescapedtext ($hcms_lang['delete'][$lang], $charset, $lang); ?>" title="<?php echo getescapedtext ($hcms_lang['delete'][$lang], $charset, $lang); ?>" />
            </div>
            <input id="<?php echo $tagname."_".$id ?>" name="<?php echo $tagname."[".$id."]"; ?>" type="hidden" value="<?php echo $contentbot; ?>" />
          </div>
        </td>
      </tr>
    </table>
  </form>
</div>

<script type="text/javascript">


</script>

<?php includefooter(); ?>

</body>
</html>

