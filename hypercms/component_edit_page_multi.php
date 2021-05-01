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
$tagname = getrequest_esc ("tagname", "objectname");
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

// initialize
$contentbot = false;
$component = "";
$component_curr = "";

// load object file and get container
$objectdata = loadfile ($location, $page);
$contentfile = getfilename ($objectdata, "content");

// get file info
$file_info = getfileinfo ($site, $location.$page, $cat);

// create secure token
$token = createtoken ($user);

if (substr_count ($tagname, "art") == 1) $art = "art";
else $art = "";

// read content using db_connect
if (!empty ($db_connect) && valid_objectname ($db_connect) && is_file ($mgmt_config['abs_path_data']."db_connect/".$db_connect)) 
{
  include ($mgmt_config['abs_path_data']."db_connect/".$db_connect);
  
  $db_connect_data = db_read_component ($site, $contentfile, "", $id, "", $user);
  
  if (!empty ($db_connect_data['file'])) $contentbot = $db_connect_data['file'];
}

// read content from content container
if (empty ($contentbot))
{
  $container_id = substr ($contentfile, 0, strpos ($contentfile, ".xml")); 

  $filedata = loadcontainer ($container_id, "work", $user);

  $temp = selectcontent ($filedata, "<component>", "<component_id>", $id);
  if (!empty ($temp[0])) $temp = getcontent ($temp[0], "<componentfiles>");
  if (!empty ($temp[0])) $contentbot = $temp[0];
}

// define current components string
$component_curr = $contentbot;

// convert object ID to object path
$component_curr = getobjectlink ($component_curr);
?>
<!DOCTYPE html>
<html>
<head>
<title>hyperCMS</title>
<meta charset="<?php echo getcodepage ($lang); ?>" />
<link rel="stylesheet" href="<?php echo getthemelocation(); ?>css/main.css" />
<link rel="stylesheet" href="<?php echo getthemelocation()."css/".($is_mobile ? "mobile.css" : "desktop.css"); ?>" />
<script type="text/javascript" src="javascript/main.min.js"></script>
<script type="text/javascript" src="javascript/click.min.js"></script>
<script type="text/javascript">

function correctnames ()
{
  if (document.forms['component'].elements['component']) document.forms['component'].elements['component'].name = "<?php echo $art; ?>component[<?php echo $id; ?>]";
  if (document.forms['component'].elements['condition']) document.forms['component'].elements['condition'].name = "<?php echo $art; ?>condition[<?php echo $id; ?>]";
  return true;
}

function insertOption (newtext, newvalue)
{
  newentry = new Option (newtext, newvalue, false, true);
  selectbox = document.forms['component'].elements['component_array'];
  var i;
  
  if (selectbox.length > 0)
  {  
    var position = -1;
    
    for (i=0; i<selectbox.length; i++)
    {
      if (selectbox.options[i].selected) position = i;
    }
    
    if (position != -1)
    {
      selectbox.options[selectbox.length] = new Option();
    
      for (i=selectbox.length-1; i>position; i--)
      {
        selectbox.options[i].text = selectbox.options[i-1].text;
        selectbox.options[i].value = selectbox.options[i-1].value;
      }
      
      selectbox.options[position+1] = newentry;
    }
    else selectbox.options[selectbox.length] = newentry;
  }
  else selectbox.options[selectbox.length] = newentry;
}

function moveSelected (select, down)
{
  if (select.selectedIndex != -1)
  {
    if (down)
    {
      if (select.selectedIndex != select.options.length - 1)
        var i = select.selectedIndex + 1;
      else
        return;
    }
    else
    {
      if (select.selectedIndex != 0)
        var i = select.selectedIndex - 1;
      else
        return;
    }

    var swapOption = new Object();

    swapOption.text = select.options[select.selectedIndex].text;
    swapOption.value = select.options[select.selectedIndex].value;
    swapOption.selected = select.options[select.selectedIndex].selected;

    for (var property in swapOption) select.options[select.selectedIndex][property] = select.options[i][property];
    for (var property in swapOption) select.options[i][property] = swapOption[property];
  }
}

function deleteSelected (select)
{
  if (select.length > 0)
  {
    for(var i=0; i<select.length; i++)
    {
      if (select.options[i].selected == true) select.remove(i);
    }
  }
}

function submitMultiComp(select)
{
  var component = "";

  if (select.options.length > 0)
  {
    for(var i=0; i<select.options.length; i++)
    {
      component = component + select.options[i].value + "|";
    }
  }
  
  document.forms['component'].elements['component'].value = component;
  correctnames ();
  document.forms['component'].submit();
  return true;
}

function openBrWindowComp (winName, features, type)
{
  var url = document.forms['component'].elements['component_array'].value;
  var theURL  = "";
  
  if (url != "")
  {
    if (type == "preview")
    {
      if (url.indexOf('://') == -1)
      {
        var position1 = url.indexOf("/");
        theURL = '<?php echo $mgmt_config['url_path_comp']; ?>' + url.substring(position1+1, url.length);
      }
    }
    else if (type == "cmsview")  
    {
      if (url.indexOf('://') == -1)
      {      
        var position1 = url.indexOf("/");
        var position2 = url.lastIndexOf("/");
        
        var location_comp = "%comp%/" + url.substring (position1 + 1, position2 + 1);
        
        var location_site = theURL.substring (position1+1, url.length-position1);              
        location_site = location_site.substring(0, location_site.indexOf('/'));
        
        var page_comp = url.substr (position2 + 1, url.length);
        
        theURL = '<?php echo $mgmt_config['url_path_cms']; ?>frameset_content.php?ctrlreload=yes&cat=comp&site=' + encodeURIComponent(location_site) + '&location=' + encodeURIComponent(location_comp) + '&page=' + encodeURIComponent(page_comp) + '&user=<?php echo url_encode($user); ?>';
      }
      else alert (hcms_entity_decode('<?php echo getescapedtext ($hcms_lang['this-is-an-external-component-link'][$lang]); ?>'));
    }
    
    if (theURL != "") hcms_openWindow (theURL, winName, features, <?php echo windowwidth ("object"); ?>, <?php echo windowheight ("object"); ?>);
  }
  else alert (hcms_entity_decode('<?php echo getescapedtext ($hcms_lang['no-component-selected'][$lang]); ?>'));  
}
</script>
</head>

<body class="hcmsWorkplaceGeneric">

<!-- top bar -->
<?php echo showtopbar ($id, $lang, $mgmt_config['url_path_cms']."page_view.php?view=".url_encode($view)."&site=".url_encode($site)."&cat=".url_encode($cat)."&location=".url_encode($location_esc)."&page=".url_encode($page), "objFrame"); ?>

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
  <input type="hidden" name="token" value="<?php echo $token; ?>" />
    
  <table class="hcmsTableStandard">  
    <tr>
      <td colspan="2" class="hcmsHeadlineTiny" style="white-space:nowrap;"><?php echo getescapedtext ($hcms_lang['multiple-component'][$lang]); ?> </td>
    </tr>  
    <tr>
      <td colspan="2" style="white-space:nowrap;"><?php echo getescapedtext ($hcms_lang['selected-components'][$lang]); ?> </td>
    </tr>  
    <tr>
      <td colspan="2">
        <table class="hcmsTableNarrow">
          <tr>
            <td>
              <select name="component_array" size="10" style="width:290px;">
                <?php
                if (!empty ($component_curr))
                {
                  $component = trim ($component_curr, "|");
 
                  // split component string into array
                  $component_array = explode ("|", $component);

                  foreach ($component_array as $comp_entry)
                  {
                    if ($comp_entry != "")
                    {
                      $comp_entry_name = getlocationname ($site, $comp_entry, "comp", "path");
                      
                      // shorten path
                      if (strlen ($comp_entry_name) > 36) $comp_entry_name_short = "...".substr (substr ($comp_entry_name, -36), strpos (substr ($comp_entry_name, -36), "/"));
                      else $comp_entry_name_short = $comp_entry_name;
                                       
                      echo "
                <option value=\"".$comp_entry."\" title=\"".$comp_entry_name."\">".$comp_entry_name_short."</option>";
                    }
                  }
                }
                ?>
              </select>
            </td>
            <td>
              <img onClick="moveSelected(document.forms['component'].elements['component_array'], false)" class="hcmsButtonTiny hcmsButtonSizeSquare" name="ButtonUp" src="<?php echo getthemelocation(); ?>img/button_moveup.png" class="hcmsButtonTinyBlank hcmsButtonSizeSquare" alt="<?php echo getescapedtext ($hcms_lang['move-up'][$lang]); ?>" title="<?php echo getescapedtext ($hcms_lang['move-up'][$lang]); ?>" /></a><br />
              <img onClick="openBrWindowComp('','location=no,menubar=no,toolbar=no,titlebar=no,scrollbars=yes,resizable=yes,status=yes', 'cmsview');" class="hcmsButtonTiny hcmsButtonSizeSquare" name="ButtonEdit" src="<?php echo getthemelocation(); ?>img/button_edit.png" alt="<?php echo getescapedtext ($hcms_lang['edit'][$lang]); ?>" title="<?php echo getescapedtext ($hcms_lang['edit'][$lang]); ?>" /><br />                          
              <img onClick="deleteSelected(document.forms['component'].elements['component_array'])" class="hcmsButtonTiny hcmsButtonSizeSquare" name="ButtonDelete" src="<?php echo getthemelocation(); ?>img/button_delete.png" alt="<?php echo getescapedtext ($hcms_lang['delete'][$lang]); ?>" title="<?php echo getescapedtext ($hcms_lang['delete'][$lang]); ?>" /><br />            
              <img onClick="moveSelected(document.forms['component'].elements['component_array'], true)" class="hcmsButtonTiny hcmsButtonSizeSquare" name="ButtonDown" src="<?php echo getthemelocation(); ?>img/button_movedown.png" class="hcmsButtonTinyBlank hcmsButtonSizeSquare" alt="<?php echo getescapedtext ($hcms_lang['move-down'][$lang]); ?>" title="<?php echo getescapedtext ($hcms_lang['move-down'][$lang]); ?>" /><br />
              <img onclick="submitMultiComp(document.forms['component'].elements['component_array']);" name="Button" src="<?php echo getthemelocation(); ?>img/button_ok.png" class="hcmsButtonTinyBlank hcmsButtonSizeSquare" onMouseOut="hcms_swapImgRestore()" onMouseOver="hcms_swapImage('Button','','<?php echo getthemelocation(); ?>img/button_ok_over.png',1)" alt="OK" title="OK" />
             </td>
          </tr>
        </table>
      </td>
    </tr>
    <?php if (empty ($mgmt_config[$site]['dam'])) { ?>
    <tr>
      <td colspan="2">&nbsp;</td>
    </tr>
    <tr>
      <td colspan="2" class="hcmsHeadlineTiny" style="white-space:nowrap;"><?php echo getescapedtext ($hcms_lang['condition-for-personalization'][$lang]); ?> </td>
    </tr>
    <tr>
      <td colspan="2" style="white-space:nowrap;">
        <?php echo getescapedtext ($hcms_lang['customer-profile'][$lang]); ?> 
        <select name="condition" style="width:220px;">
          <option value=""><?php echo getescapedtext ($hcms_lang['select'][$lang]); ?></option>
          <?php
          $dir_item = @dir ($mgmt_config['abs_path_data']."customer/".$site."/");

          $i = 0;
          $item_files = array();

          if ($dir_item != false)
          {
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

            if (sizeof ($item_files) > 0)
            {
              sort ($item_files);
              reset ($item_files);

              foreach ($item_files as $persfile)
              {
                $cond_name = substr ($persfile, 0, strpos ($persfile, ".prof.dat"));
                
                if ($cond_name == $condition) $selected = "selected=\"selected\"";
                else $selected = "";
                
                echo "
              <option value=\"".$cond_name."\" ".$selected.">".$cond_name."</option>";
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
