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
// hyperCMS UI
require ("function/hypercms_ui.inc.php");


// input parameters
$view = getrequest_esc ("view");
$compcat = getrequest_esc ("compcat", "objectname");
$location = getrequest_esc ("location", "locationname");
$page = getrequest_esc ("page", "objectname");
$db_connect = getrequest_esc ("db_connect", "objectname");
$id = getrequest_esc ("id", "objectname", "", true);
$label = getrequest_esc ("label");
$tagname = getrequest_esc ("tagname", "objectname");
$component_curr = getrequest_esc ("component_curr", "locationname");
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

// get name
$component_name = getlocationname ($site, $component, "comp", "path");
if (strlen ($component_name) > 50) $component_name = "...".substr (substr ($component_name, -50), strpos (substr ($component_name, -50), "/")); 

if (substr_count ($tagname, "art") == 1) $art = "art";
else $art = "";

if ($label == "") $label = $id;
?>
<!DOCTYPE html>
<html>
<head>
<title>hyperCMS</title>
<meta http-equiv="Content-Type" content="text/html; charset=<?php echo getcodepage ($lang); ?>">
<link rel="stylesheet" href="<?php echo getthemelocation(); ?>css/main.css">
<script src="javascript/main.js" type="text/javascript"></script>
<script src="javascript/click.js" type="text/javascript"></script>
<script language="JavaScript">
<!--
function correctnames ()
{
  if (eval (document.forms['component'].elements['component'])) document.forms['component'].elements['component'].name = "<?php echo $art; ?>component[<?php echo $id; ?>]";
  if (eval (document.forms['component'].elements['component_curr'])) document.forms['component'].elements['component_curr'].name = "<?php echo $art; ?>component_curr[<?php echo $id; ?>]";
  if (eval (document.forms['component'].elements['condition'])) document.forms['component'].elements['condition'].name = "<?php echo $art; ?>condition[<?php echo $id; ?>]";
  return true;
}

function openBrWindowComp(winName,features, type)
{
  theURL = document.forms['component'].elements['component'].value;

  if (theURL != "")
  {
    if (type == "preview")
    {
      if (theURL.indexOf('://') == -1)
      {
        position1 = theURL.indexOf("/");
        theURL = '<?php echo $publ_config['url_publ_comp']; ?>' + theURL.substring (position1+1, theURL.length);
      }
  
      popup = window.open(theURL,winName,features);
      popup.moveTo(screen.width/2-800/2, screen.height/2-600/2);
      popup.focus();
    }
    else if (type == "cmsview")  
    {
      if (theURL.indexOf('://') == -1)
      {      
        position1 = theURL.indexOf("/");
        position2 = theURL.lastIndexOf("/");
        
        location_comp = "%comp%/" + theURL.substring (position1+1, position2+1);
        location_comp = escape (location_comp);
        
        location_site = theURL.substring (position1+1, theURL.length);              
        location_site = location_site.substring(0, location_site.indexOf('/'));
        location_site = escape (location_site);

        page_comp = theURL.substr (position2+1, theURL.length);
        page_comp = escape (page_comp);
        
        theURL = '<?php echo $mgmt_config['url_path_cms']; ?>frameset_content.php?ctrlreload=yes&cat=comp&site=' + location_site + '&location=' + location_comp + '&page=' + page_comp + '&user=<?php echo $user; ?>';

        popup = window.open(theURL,winName,features);
        popup.moveTo(screen.width/2-800/2, screen.height/2-600/2);
        popup.focus();
      }
      else alert(hcms_entity_decode('<?php echo getescapedtext ($hcms_lang['this-is-an-external-component-link'][$lang]); ?>'));
    }
  }
  else alert(hcms_entity_decode('<?php echo getescapedtext ($hcms_lang['no-component-selected'][$lang]); ?>'));  
}

function deleteEntry(select)
{
  select.elements['component'].value = "";
  select.elements['comp_name'].value = "";
}

function submitSingleComp(select)
{
  correctnames ();
  document.forms['component'].submit();
  return true;
}
//-->
</script>
</head>

<body class="hcmsWorkplaceGeneric" leftmargin=3 topmargin=3 marginwidth=0 marginheight=0>

<!-- top bar -->
<?php 
echo showtopbar ($label, $lang, $mgmt_config['url_path_cms']."page_view.php?view=".url_encode($view)."&site=".url_encode($site)."&cat=".url_encode($cat)."&location=".url_encode($location_esc)."&page=".url_encode($page), "objFrame");
?>

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
  <input type="hidden" name="component_curr" value="<?php echo $component_curr; ?>" />
  <input type="hidden" name="component" value="<?php echo $component; ?>" />
  <input type="hidden" name="token" value="<?php echo $token; ?>">
  <table border="0" cellspacing="2" cellpadding="0">
    <tr>
      <td nowrap="nowrap" colspan="2" class="hcmsHeadlineTiny"><?php echo $hcms_lang['single-component'][$lang]; ?></td>
    </tr>   
    <tr>
      <td nowrap="nowrap"><?php echo $hcms_lang['selected-component'][$lang]; ?>:</td>
      <td>
        <input type="text" name="comp_name" style="width:265px;" value="<?php echo $component_name; ?>" disabled="disabled" />
        <img onClick="openBrWindowComp('','scrollbars=yes,resizable=yes,width=800,height=600,status=yes', 'cmsview');" class="hcmsButtonTiny hcmsButtonSizeSquare" name="ButtonEdit" src="<?php echo getthemelocation(); ?>img/button_file_edit.gif" align="absmiddle" alt="<?php echo $hcms_lang['select'][$lang]; ?>" title="<?php echo $hcms_lang['select'][$lang]; ?>" />                          
        <img onClick="deleteEntry(document.forms['component']);" class="hcmsButtonTiny hcmsButtonSizeSquare" name="ButtonDelete" src="<?php echo getthemelocation(); ?>img/button_delete.gif" align="absmiddle" alt="<?php echo $hcms_lang['delete'][$lang]; ?>" title="<?php echo $hcms_lang['delete'][$lang]; ?>" />
        <img onclick="submitSingleComp(document.forms['component']);" name="Button" src="<?php echo getthemelocation(); ?>img/button_OK.gif" class="hcmsButtonTinyBlank hcmsButtonSizeSquare" onMouseOut="hcms_swapImgRestore();" onMouseOver="hcms_swapImage('Button','','<?php echo getthemelocation(); ?>img/button_OK_over.gif',1);" align="absmiddle" alt="OK" title="OK" />    
      </td>
    </tr>
    <tr>
      <td nowrap="nowrap" colspan="2">&nbsp;</td>
    </tr>
    <tr>
      <td nowrap="nowrap" class="hcmsHeadlineTiny" colspan="2"><?php echo $hcms_lang['condition-for-personalization'][$lang]; ?></td>
    </tr>
    <tr>
      <td nowrap="nowrap"><?php echo $hcms_lang['customer-profile'][$lang]; ?>:</td>
      <td>
        <select name="condition" style="width:265px;">
          <option value="">--- <?php echo $hcms_lang['select'][$lang]; ?> ---</option>
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
    <tr>
      <td colspan="2">&nbsp;</td>
    </tr>
  </table>
</form>  

</body>
</html>
