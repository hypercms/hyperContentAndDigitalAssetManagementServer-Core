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
$view = getrequest_esc ("view");
$compcat = getrequest_esc ("compcat", "objectname");
$location = getrequest_esc ("location", "locationname");
$page = getrequest_esc ("page", "objectname");
$db_connect = getrequest_esc ("db_connect", "objectname");
$id = getrequest_esc ("id", "objectname", "", true);
$label = getrequest_esc ("label");
$tagname = getrequest_esc ("tagname", "objectname");
$component = getrequest_esc ("component", "locationname");
$condition = getrequest ("condition", "objectname");

// get publication and category
$site = getpublication ($location);
$cat = getcategory ($site, $location); 

// publication management config
if (valid_publicationname ($site)) require ($mgmt_config['abs_path_data']."config/".$site.".conf.php");
// load publication configuration
if (valid_publicationname ($site)) $publ_config = parse_ini_file ($mgmt_config['abs_path_rep']."config/".$site.".ini"); 

// ------------------------------ permission section --------------------------------

// convert location
$location = deconvertpath ($location, "file");
$location_esc = convertpath ($site, $location, $cat);

// check access permissions
$ownergroup = accesspermission ($site, $location, $cat);
$setlocalpermission = setlocalpermission ($site, $ownergroup, $cat);  
if ($ownergroup == false || $setlocalpermission['root'] != 1 || $setlocalpermission['create'] != 1 || !valid_publicationname ($site) || !valid_locationname ($location) || !valid_objectname ($page)) killsession ($user);

// check session of user
checkusersession ($user);

// --------------------------------- logic section ----------------------------------

// load object file and get container
$objectdata = loadfile ($location, $page);
$contentfile = getfilename ($objectdata, "content");

// get file info
$file_info = getfileinfo ($site, $location.$page, $cat);

// create secure token
$token = createtoken ($user);

$temp = link_db_getobject ($component);
if (!empty ($temp[0])) $component = $temp[0];

// convert object ID to object path
$component = getobjectlink ($component);

// get name
$component_name = getlocationname ($site, $component, "comp", "path");

// shorten path
if (strlen ($component_name) > 36) $component_name_short = "...".substr (substr ($component_name, -36), strpos (substr ($component_name, -36), "/"));
else $component_name_short = $component_name;

if (substr_count ($tagname, "art") == 1) $art = "art";
else $art = "";

if (empty ($label)) $label = $id;
?>
<!DOCTYPE html>
<html>
<head>
<title>hyperCMS</title>
<meta charset="<?php echo getcodepage ($lang); ?>" />
<link rel="stylesheet" href="<?php echo getthemelocation(); ?>css/main.css?v=<?php echo getbuildnumber(); ?>" />
<link rel="stylesheet" href="<?php echo getthemelocation()."css/".($is_mobile ? "mobile.css" : "desktop.css"); ?>?v=<?php echo getbuildnumber(); ?>" />
<script type="text/javascript" src="javascript/main.min.js?v=<?php echo getbuildnumber(); ?>"></script>
<script type="text/javascript" src="javascript/click.min.js"></script>
<script type="text/javascript">

function correctnames ()
{
  if (document.forms['component'].elements['component']) document.forms['component'].elements['component'].name = "<?php echo $art; ?>component[<?php echo $id; ?>]";
  if (document.forms['component'].elements['condition']) document.forms['component'].elements['condition'].name = "<?php echo $art; ?>condition[<?php echo $id; ?>]";
  return true;
}

function openBrWindowComp (winName,features, type)
{
  var url = document.forms['component'].elements['component'].value;
  var theURL = "";

  if (url != "")
  {
    if (type == "preview")
    {
      if (url.indexOf('://') == -1)
      {
        var position1 = url.indexOf("/");
        theURL = '<?php echo $publ_config['url_publ_comp']; ?>' + url.substring (position1+1, url.length);
      }
    }
    else if (type == "cmsview")  
    {
      if (url.indexOf('://') == -1)
      {      
        var position1 = url.indexOf("/");
        position2 = url.lastIndexOf("/");
        
        var location_comp = "%comp%/" + url.substring (position1+1, position2+1);
        
        var location_site = url.substring (position1+1, url.length);              
        location_site = location_site.substring(0, location_site.indexOf('/'));

        var page_comp = url.substr (position2+1, url.length);
        
        theURL = '<?php echo $mgmt_config['url_path_cms']; ?>frameset_content.php?ctrlreload=yes&cat=comp&site=' + encodeURIComponent(location_site) + '&location=' + encodeURIComponent(location_comp) + '&page=' + encodeURIComponent(page_comp) + '&user=<?php echo url_encode($user); ?>';
      }
      else alert(hcms_entity_decode('<?php echo getescapedtext ($hcms_lang['this-is-an-external-component-link'][$lang]); ?>'));
    }
    
    if (theURL != "") hcms_openWindow (theURL, winName, features, <?php echo windowwidth ("object"); ?>, <?php echo windowheight ("object"); ?>);
  }
  else alert(hcms_entity_decode('<?php echo getescapedtext ($hcms_lang['no-component-selected'][$lang]); ?>'));  
}

function deleteEntry(select)
{
  select.elements['component'].value = "";
  select.elements['comp_name'].value = "";
}

function submitSingleComp ()
{
  correctnames ();
  document.forms['component'].submit();
  return true;
}

function hcms_saveEvent ()
{
  submitSingleComp();
}
</script>
</head>

<body class="hcmsWorkplaceGeneric">

<!-- top bar -->
<?php 
echo showtopbar ($label, $lang, $mgmt_config['url_path_cms']."page_view.php?view=".url_encode($view)."&site=".url_encode($site)."&cat=".url_encode($cat)."&location=".url_encode($location_esc)."&page=".url_encode($page), "objFrame");
?>

<div class="hcmsWorkplaceFrame">
<form name="component" action="service/savecontent.php" target="_parent" method="post">
  <input type="hidden" name="view" value="<?php echo $view; ?>" />
  <input type="hidden" name="site" value="<?php echo $site; ?>" />
  <input type="hidden" name="cat" value="<?php echo $cat; ?>" />
  <input type="hidden" name="location" value="<?php echo $location_esc; ?>" />
  <input type="hidden" name="page" value="<?php echo $page; ?>" />
  <input type="hidden" name="contentfile" value="<?php echo $contentfile; ?>" />
  <input type="hidden" name="db_connect" value="<?php echo $db_connect; ?>" />
  <input type="hidden" name="id" value="<?php echo $id; ?>" />
  <input type="hidden" name="tagname" value="<?php echo $tagname; ?>" />
  <input type="hidden" name="component" value="<?php echo $component; ?>" />
  <input type="hidden" name="token" value="<?php echo $token; ?>">
  
  <table class="hcmsTableStandard">
    <tr>
      <td style="white-space:nowrap;" colspan="2" class="hcmsHeadlineTiny"><?php echo getescapedtext ($hcms_lang['single-component'][$lang]); ?> </td>
    </tr>   
    <tr>
      <td style="white-space:nowrap;"><?php echo getescapedtext ($hcms_lang['selected-component'][$lang]); ?> </td>
      <td style="white-space:nowrap;">
        <input type="text" name="comp_name" style="width:220px;" value="<?php echo $component_name_short; ?>" title="<?php echo $component_name; ?>" readonly="readonly" />
        <img onClick="openBrWindowComp('','location=no,menubar=no,toolbar=no,titlebar=no,scrollbars=yes,resizable=yes,status=yes', 'cmsview');" class="hcmsButtonTiny hcmsButtonSizeSquare" name="ButtonEdit" src="<?php echo getthemelocation(); ?>img/button_edit.png" alt="<?php echo getescapedtext ($hcms_lang['select'][$lang]); ?>" title="<?php echo getescapedtext ($hcms_lang['select'][$lang]); ?>" />                          
        <img onClick="deleteEntry(document.forms['component']);" class="hcmsButtonTiny hcmsButtonSizeSquare" name="ButtonDelete" src="<?php echo getthemelocation(); ?>img/button_delete.png" alt="<?php echo getescapedtext ($hcms_lang['delete'][$lang]); ?>" title="<?php echo getescapedtext ($hcms_lang['delete'][$lang]); ?>" />
        <img onclick="submitSingleComp();" name="Button" src="<?php echo getthemelocation(); ?>img/button_ok.png" class="hcmsButtonTinyBlank hcmsButtonSizeSquare" onMouseOut="hcms_swapImgRestore();" onMouseOver="hcms_swapImage('Button','','<?php echo getthemelocation(); ?>img/button_ok_over.png',1);" alt="OK" title="OK" />    
      </td>
    </tr>
    <?php if (!$mgmt_config[$site]['dam']) { ?>
    <tr>
      <td colspan="2">&nbsp;</td>
    </tr>
    <tr>
      <td style="white-space:nowrap;" class="hcmsHeadlineTiny" colspan="2"><?php echo getescapedtext ($hcms_lang['condition-for-personalization'][$lang]); ?> </td>
    </tr>
    <tr>
      <td style="white-space:nowrap;"><?php echo getescapedtext ($hcms_lang['customer-profile'][$lang]); ?> </td>
      <td style="white-space:nowrap;">
        <select name="condition" style="width:220px;">
          <option value=""><?php echo getescapedtext ($hcms_lang['select'][$lang]); ?></option>
          <?php
          $dir_item = @dir ($mgmt_config['abs_path_data']."customer/".$site."/");

          if ($dir_item != false)
          {
            $i = 0;
            $item_files = array();
          
            while ($entry = $dir_item->read())
            {
              if ($entry != "." && $entry != ".." && !is_dir ($entry))
              {
                if (strpos ($entry, ".prof.dat") > 0)
                {
                  $item_files[$i] = $entry;
                }
                $i++;
              }
            }

            $dir_item->close();

            if (sizeof ($item_files) >= 1)
            {
              sort ($item_files);
              reset ($item_files);

              foreach ($item_files as $persfile)
              {
                $cond_name = substr ($persfile, 0, strpos ($persfile, ".prof.dat"));
                if ($cond_name == $condition) $selected = "selected=\"selected\"";
                else $selected = "";
                echo "<option value=\"".$cond_name."\" ".$selected.">".$cond_name."</option>\n";
              }
            }
          }
          ?>
        </select>
      </td>
    </tr>
    <?php } ?>
  </table>
</form> 
</div>

<?php includefooter(); ?>

</body>
</html>
