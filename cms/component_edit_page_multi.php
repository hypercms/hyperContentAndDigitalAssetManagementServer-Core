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
require_once ("language/component_edit_page_multi.inc.php");


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

// load object file and get container
$objectdata = loadfile ($location, $page);
$contentfile = getfilename ($objectdata, "content");

// get file info
$file_info = getfileinfo ($site, $location.$page, $cat);

// create secure token
$token = createtoken ($user);

if (substr_count ($tagname, "art") == 1) $art = "art";
else $art = "";
?>
<!DOCTYPE html>
<html>
<head>
<title>hyperCMS</title>
<meta http-equiv="Content-Type" content="text/html; charset=<?php echo $lang_codepage[$lang]; ?>">
<link rel="stylesheet" href="<?php echo getthemelocation(); ?>css/main.css">
<script src="javascript/main.js" type="text/javascript">
</script>
<script src="javascript/click.js" type="text/javascript">
</script>
<script language="JavaScript">
<!--
function correctnames ()
{
  if (eval (document.forms['component'].elements['component'])) document.forms['component'].elements['component'].name = "<?php echo $art; ?>component[<?php echo $id; ?>]";
  if (eval (document.forms['component'].elements['component_curr'])) document.forms['component'].elements['component_curr'].name = "<?php echo $art; ?>component_curr[<?php echo $id; ?>]";
  if (eval (document.forms['component'].elements['condition'])) document.forms['component'].elements['condition'].name = "<?php echo $art; ?>condition[<?php echo $id; ?>]";
  return true;
}

function insertOption(newtext, newvalue)
{
  newentry = new Option (newtext, newvalue, false, true);
  selectbox = document.forms['component'].elements['component_array'];
  var i;
  
  if(selectbox.length > 0)
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
  if (select.selectedIndex != -1) {
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
    // would not work in FireFox: swapOption.defaultSelected = select.options[select.selectedIndex].defaultSelected;

    for (var property in swapOption) select.options[select.selectedIndex][property] = select.options[i][property];
    for (var property in swapOption) select.options[i][property] = swapOption[property];
  }
}

function deleteSelected(select)
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
  else
  {
    component = "";
  }

  document.forms['component'].elements['component'].value = component;
  correctnames ();
  document.forms['component'].submit();
  return true;
}

function openBrWindowComp(winName, features, type)
{
  theURL = document.forms['component'].elements['component_array'].value;
  
  if (theURL != "")
  {
    if (type == "preview")
    {
      if (theURL.indexOf('://') == -1)
      {
        position1 = theURL.indexOf("/");
        theURL = '<?php echo $mgmt_config['url_path_comp']; ?>' + theURL.substring(position1+1, theURL.length);
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
        
        location_comp = "%comp%/" + theURL.substring (position1 + 1, position2 + 1);
        location_comp = escape (location_comp);
        
        location_site = theURL.substring (position1+1, theURL.length-position1);              
        location_site = location_site.substring(0, location_site.indexOf('/'));
        location_site = escape (location_site);
        
        page_comp = theURL.substr (position2 + 1, theURL.length);
        page_comp = escape (page_comp);
        
        theURL = '<?php echo $mgmt_config['url_path_cms']; ?>frameset_content.php?ctrlreload=yes&cat=comp&site=' + location_site + '&location=' + location_comp + '&page=' + page_comp + '&user=<?php echo $user; ?>';

        popup = window.open(theURL,winName,features);
        popup.moveTo(screen.width/2-800/2, screen.height/2-600/2);
        popup.focus();
      }
      else alert (hcms_entity_decode('<?php echo $text4[$lang]; ?>'));
    }
  }
  else alert (hcms_entity_decode('<?php echo $text2[$lang]; ?>'));  
}
//-->
</script>
</head>

<body class="hcmsWorkplaceGeneric">

<?php
$component = "";
$component_curr = "";

// read content using db_connect
if ($db_connect != false && valid_objectname ($db_connect) && file_exists ($mgmt_config['abs_path_data']."db_connect/".$db_connect)) 
{
  include ($mgmt_config['abs_path_data']."db_connect/".$db_connect);
  
  $db_connect_data = db_read_component ($site, $contentfile, "", $id, "", $user);
  
  if ($db_connect_data != false) $contentbot = $db_connect_data['file'];
  else $contentbot = false;
}  
else $contentbot = false;

// read content using db_connect_tamino
if ($contentbot == false && !empty ($mgmt_config['db_connect_tamino']) && file_exists ($mgmt_config['abs_path_data']."db_connect/".$mgmt_config['db_connect_tamino']))
{
  include ($mgmt_config['abs_path_data']."db_connect/".$mgmt_config['db_connect_tamino']);
  
  $db_connect_data = db_read_component ("work", $site, $contentfile, "", $id, "", $user);
  
  if ($db_connect_data != false) $contentbot = $db_connect_data['file'];
  else $contentbot = false;
}
else $contentbot = false;  

// read content from content container
if ($contentbot == false) 
{
  $container_id = substr ($contentfile, 0, strpos ($contentfile, ".xml")); 

  $filedata = loadfile (getcontentlocation ($container_id, 'abs_path_content'), $contentfile.".wrk");
  $contentarray = selectcontent ($filedata, "<component>", "<component_id>", $id);
  $contentarray = getcontent ($contentarray[0], "<componentfiles>");
  $contentbot = $contentarray[0];
}

// define current components string
$component_curr = $contentbot;
?>

<!-- top bar -->
<?php echo showtopbar ($id, $lang, $mgmt_config['url_path_cms']."page_view.php?view=".url_encode($view)."&site=".url_encode($site)."&cat=".url_encode($cat)."&location=".url_encode($location_esc)."&page=".url_encode($page), "objFrame"); ?>

<div class="hcmsWorkplaceFrame">
<form name="component" action="page_save.php" target="_parent" method="post">
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
  <input type="hidden" name="token" value="<?php echo $token; ?>" />
    
  <table border="0" cellspacing="2" cellpadding="0">  
    <tr>
      <td nowrap="nowrap" colspan="2" class="hcmsHeadlineTiny"><?php echo $text0[$lang]; ?></td>
    </tr>  
    <tr>
      <td colspan="2" nowrap="nowrap"><?php echo $text1[$lang]; ?>:</td>
    </tr>  
    <tr>
      <td colspan="2">
        <table border="0" cellspacing="1" cellpadding="0">
          <tr>
            <td>
              <select name="component_array" size="10" style="width:350px;">
                <?php
                if ($component_curr != false && $component_curr != "")
                {
                  $component = trim ($component_curr);
                  
                  // cut off last delimiter
                  if ($component[strlen ($component)-1] == "|") $component = substr ($component, 0, strlen ($component) - 1);
                  
                  // split component string into array
                  $component_array = explode ("|", $component);

                  foreach ($component_array as $comp_entry)
                  {
                    $comp_entry_name = getlocationname ($site, $comp_entry, "comp", "path");
                    
                    if (strlen ($comp_entry_name) > 50) $comp_entry_name = "...".substr (substr ($comp_entry_name, -50), strpos (substr ($comp_entry_name, -50), "/")); 
                                     
                    echo "<option value=\"".$comp_entry."\">".$comp_entry_name."</option>\n";
                  }
                }
                ?>
              </select>
            </td>
            <td align="left" valign="middle">
              <a href=# onClick="moveSelected(document.forms['component'].elements['component_array'], false)" onMouseOut="hcms_swapImgRestore()" onMouseOver="hcms_swapImage('ButtonUp','','<?php echo getthemelocation(); ?>img/button_moveup_over.gif',1)"><img name="ButtonUp" src="<?php echo getthemelocation(); ?>img/button_moveup.gif" class="hcmsButtonTinyBlank hcmsButtonSizeSquare" alt="<?php echo $text5[$lang]; ?>" title="<?php echo $text5[$lang]; ?>" /></a><br />
              <img onClick="openBrWindowComp('','scrollbars=yes,resizable=yes,width=800,height=600,status=yes', 'cmsview');" class="hcmsButtonTiny hcmsButtonSizeSquare" name="ButtonEdit" src="<?php echo getthemelocation(); ?>img/button_file_edit.gif" alt="<?php echo $text3[$lang]; ?>" title="<?php echo $text3[$lang]; ?>" /><br />                          
              <img onClick="deleteSelected(document.forms['component'].elements['component_array'])" class="hcmsButtonTiny hcmsButtonSizeSquare" border=0 name="ButtonDelete" src="<?php echo getthemelocation(); ?>img/button_delete.gif" alt="<?php echo $text6[$lang]; ?>" alt="<?php echo $text6[$lang]; ?>" title="<?php echo $text6[$lang]; ?>" /><br />            
              <a href=# onClick="moveSelected(document.forms['component'].elements['component_array'], true)" onMouseOut="hcms_swapImgRestore()" onMouseOver="hcms_swapImage('ButtonDown','','<?php echo getthemelocation(); ?>img/button_movedown_over.gif',1)"><img name="ButtonDown" src="<?php echo getthemelocation(); ?>img/button_movedown.gif" class="hcmsButtonTinyBlank hcmsButtonSizeSquare" alt="<?php echo $text7[$lang]; ?>" title="<?php echo $text7[$lang]; ?>" /></a><br /><br /><br />
              <img onclick="submitMultiComp(document.forms['component'].elements['component_array']);" align="absmiddle" name="Button" src="<?php echo getthemelocation(); ?>img/button_OK.gif" class="hcmsButtonTinyBlank hcmsButtonSizeSquare" onMouseOut="hcms_swapImgRestore()" onMouseOver="hcms_swapImage('Button','','<?php echo getthemelocation(); ?>img/button_OK_over.gif',1)" alt="OK" title="OK" />
             </td>
          </tr>
        </table>
      </td>
    </tr>
    <tr>
      <td nowrap colspan="2">&nbsp;</td>
    </tr>
    <tr>
      <td nowrap colspan="2" class="hcmsHeadlineTiny"><?php echo $text9[$lang]; ?></td>
    </tr>
    <tr>
      <td nowrap colspan="2">
        <?php echo $text10[$lang]; ?>:
        <select name="condition" style="width:265px;">
          <option value="">--------- <?php echo $text11[$lang]; ?> ---------</option>
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
                echo "<option value=\"".$cond_name."\" ".$selected.">".$cond_name."</option>\n";
              }
            }
          }
          ?>
        </select>
      </td>
    </tr>
  </table>  
</form>
</div>

</body>
</html>