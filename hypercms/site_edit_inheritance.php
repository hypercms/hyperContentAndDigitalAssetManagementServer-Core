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
$site_name = getrequest_esc ("site_name", "publicationname");
$site_parents = getrequest_esc ("site_parents");
$inherit_obj_new = getrequest ("inherit_obj_new");
$inherit_comp_new = getrequest ("inherit_comp_new");
$inherit_tpl_new = getrequest ("inherit_tpl_new");
$save = getrequest ("save");
$preview = getrequest ("preview");
$token = getrequest ("token");

// ------------------------------ permission section --------------------------------

// check permissions
if (!checkrootpermission ('site')) killsession ($user);

// check session of user
checkusersession ($user);

// --------------------------------- logic section ----------------------------------

// initialize
$show = "";
$parent_array = array();

// save inheritance settings
if (checkrootpermission ('site') && checkrootpermission ('siteedit') && $save == "yes" && checktoken ($token, $user))
{
  if ($site_name != "")
  {
    $inherit_db = inherit_db_load ($user);

    if ($site_parents == "*Null*") 
    {
      $parent_array[0] = "";
      $inherit_db = inherit_db_setparent ($inherit_db, $site_name, $parent_array);
    }
    else
    {
      $site_parents = substr ($site_parents, 0, strlen ($site_parents) - 1);    
      $parent_array = explode ("|", $site_parents);
      
      $inherit_db = inherit_db_setparent ($inherit_db, $site_name, $parent_array);
    }  

    if ($inherit_db != false) $test = inherit_db_save ($inherit_db, $user);
    else $test = false;

    if ($test == false)
    {
      $show = "<p class=\"hcmsHeadline\">".getescapedtext ($hcms_lang['the-publication-information-cannot-be-accessed'][$lang])."</p>\n".getescapedtext ($hcms_lang['the-publication-information-is-missing-or-you-do-not-have-write-permissions'][$lang])."\n";
    }

    // update the inheritance settings in the config file of the publication
    if (file_exists ($mgmt_config['abs_path_data']."config/".$site_name.".conf.php"))
    {  
      // load config file of the publication for management system
      $config_data = loadfile ($mgmt_config['abs_path_data']."config/", $site_name.".conf.php");

      // set boolean values
      if ($inherit_obj_new == true) $inherit_obj_new = "true";
      else $inherit_obj_new = "false"; 

      if ($inherit_comp_new == true) $inherit_comp_new = "true";
      else $inherit_comp_new = "false";

      if ($inherit_tpl_new == true) $inherit_tpl_new = "true";
      else $inherit_tpl_new = "false";    

      if ($config_data != false) 
      {
        $config_array = explode ("\n", trim ($config_data));

        if ($config_array != false)
        {
          for ($i = 0; $i < sizeof ($config_array); $i++)
          {
            if (strpos ($config_array[$i], "mgmt_config['".$site_name."']['inherit_obj']") > 0) 
              $config_array[$i] = "\$mgmt_config['".$site_name."']['inherit_obj'] = ".$inherit_obj_new.";";   
                   
            if (strpos ($config_array[$i], "mgmt_config['".$site_name."']['inherit_comp']") > 0) 
              $config_array[$i] = "\$mgmt_config['".$site_name."']['inherit_comp'] = ".$inherit_comp_new.";";
              
            if (strpos ($config_array[$i], "mgmt_config['".$site_name."']['inherit_tpl']") > 0)
              $config_array[$i] = "\$mgmt_config['".$site_name."']['inherit_tpl'] = ".$inherit_tpl_new.";";
          }

          $config_data = implode ("\n", $config_array);

          // eventsystem
          if (!empty ($eventsystem['onsavepublication_pre']) && empty ($eventsystem['hide']))
            onsavepublication_pre ($site_name, $config_data, "-", "-", $user);          

          // save config file
          if ($config_data != false) $test = savefile ($mgmt_config['abs_path_data']."config/", $site_name.".conf.php", $config_data);

          if ($test = true)
          {
            $show = "<p class=\"hcmsHeadline\">".getescapedtext ($hcms_lang['the-publication-configuration-was-saved-successfully'][$lang])."</p>\n";  

            // eventsystem
            if (!empty ($eventsystem['onsavepublication_post']) && empty ($eventsystem['hide']))
              onsavepublication_post ($site_name, $config_data, "-", "-", $user);           
          }
          else
          {
            $show = "<p class=\"hcmsHeadline\">".getescapedtext ($hcms_lang['the-publication-information-cannot-be-saved'][$lang])."</p>\n".getescapedtext ($hcms_lang['the-publication-information-is-corrupt-or-you-do-not-have-write-permissions'][$lang])."\n";      
          }
        }
      }
    }
    else
    {
      $show = "<p class=\"hcmsHeadline\">".getescapedtext ($hcms_lang['the-publication-information-cannot-be-accessed'][$lang])."</p>\n".getescapedtext ($hcms_lang['the-publication-information-is-missing-or-you-do-not-have-write-permissions'][$lang])."\n";
    }  
  }
}

// create secure token
$token_new = createtoken ($user);
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

function move (fbox, tbox)
{
  var arrFbox = new Array();
  var arrTbox = new Array();
  var arrLookup = new Array();
  var i;

  for (i = 0; i < tbox.options.length; i++)
  {
    arrLookup[tbox.options[i].text] = tbox.options[i].value;
    arrTbox[i] = tbox.options[i].text;
  }

  var fLength = 0;
  var tLength = arrTbox.length;

  for(i = 0; i < fbox.options.length; i++)
  {
    arrLookup[fbox.options[i].text] = fbox.options[i].value;
    if (fbox.options[i].selected && fbox.options[i].value != "")
    {
      arrTbox[tLength] = fbox.options[i].text;
      tLength++;
    }
    else
    {
      arrFbox[fLength] = fbox.options[i].text;
      fLength++;
    }
  }

  arrFbox.sort();
  arrTbox.sort();
  fbox.length = 0;
  tbox.length = 0;
  var c;

  for(c = 0; c < arrFbox.length; c++)
  {
    var no = new Option();
    no.value = arrLookup[arrFbox[c]];
    no.text = arrFbox[c];
    fbox[c] = no;
  }

  for(c = 0; c < arrTbox.length; c++)
  {
    var no = new Option();
    no.value = arrLookup[arrTbox[c]];
    no.text = arrTbox[c];
    tbox[c] = no;
  }
}

function selectAll ()
{
  var assigned = "";
  var form = document.forms['siteform'];
  var select = form.elements['list2'];

  if (select.options.length > 0)
  {
    for (var i=0; i<select.options.length; i++)
    {
      assigned = assigned + select.options[i].value + "|" ;
    }
  }
  else
  {
    assigned = "*Null*";
  }

  if (form.elements['site_name'].value == "*Null*")  
  {
    form.elements['site_parents'].value = assigned;  
  }

  form.submit();
}

function hcms_saveEvent ()
{
  selectAll();
}
</script>
</head>

<body class="hcmsWorkplaceGeneric" onload="hcms_preloadImages('<?php echo getthemelocation(); ?>img/button_ok_over.png')">
<div id="WorkplaceFrameLayer" class="hcmsWorkplaceFrame">

<?php
// show message
echo showmessage ($show, 600, 70, $lang, "position:fixed; left:10px; top:10px;");

if (checkrootpermission ('site') && checkrootpermission ('siteedit'))
{
  // define php script for form action
  if ($preview == "no")
  {
    $action = "site_edit_inheritance.php";
  }
  else
  {
    $action = "";
  }
  
  // include config
  if (valid_publicationname ($site_name)) include_once ($mgmt_config['abs_path_data']."config/".$site_name.".conf.php");
?>

<p class="hcmsHeadline"><?php echo getescapedtext ($hcms_lang['inheritance-setting-of-publication'][$lang]); ?> <?php echo $site_name; ?></p>

<form name="siteform" action="<?php echo $action; ?>" method="post">
  <input type="hidden" name="save" value="yes" />
  <input type="hidden" name="site_name" value="<?php echo $site_name; ?>" />
  <input type="hidden" name="site_parents" value="" />
  <input type="hidden" name="token" value="<?php echo $token_new; ?>">
  
  <table class="hcmsTableStandard">
    <?php
    if (checkrootpermission ('site') && checkrootpermission ('siteedit'))
    {
      echo "
    <tr>
      <td>
        <table class=\"hcmsTableNarrow\">
          <tr>
            <td>
              ".getescapedtext ($hcms_lang['publications'][$lang])."<br />
              <select multiple name=\"list1\" style=\"width:220px; height:180px;\" ".($preview == "yes" ? "disabled=\"disabled\"" : "").">";

              if (!isset ($inherit_db) || $inherit_db == false) $inherit_db = inherit_db_read ($user);
      
              if ($inherit_db != false)
              {
                $list1_array = array();
                $list2_array = array();
                          
                foreach ($inherit_db as $inherit_db_record)
                {
                  if (!empty ($inherit_db_record['parent']))
                  { 
                    if (substr_count ("|".$inherit_db_record['child'], "|".$site_name."|") == 0 && $inherit_db_record['parent'] != $site_name && in_array ($inherit_db_record['parent'], $siteaccess))
                    {
                      $list1_array[] = "
                      <option value=\"".$inherit_db_record['parent']."\">".$inherit_db_record['parent']."</option>";
                    }
                    elseif (substr_count ("|".$inherit_db_record['child'], "|".$site_name."|") == 1 && $inherit_db_record['parent'] != $site_name)
                    {
                      $list2_array[] = "
                      <option value=\"".$inherit_db_record['parent']."\">".$inherit_db_record['parent']."</option>";
                    }
                  }
                }
              }
              
              if (sizeof ($list1_array) >= 1)
              {
                natcasesort ($list1_array);
                
                foreach ($list1_array as $list1)
                {
                  echo $list1;
                }
              }

              echo "
              </select>
            </td>
            <td style=\"text-align:center; vertical-align:middle;\">
              <br />
              <input type=\"button\" class=\"hcmsButtonBlue\" style=\"width:40px; margin:5px; display:block;\" onClick=\"move(this.form.elements['list2'], this.form.elements['list1'])\" value=\"&lt;&lt;\" />
              <input type=\"button\" class=\"hcmsButtonBlue\" style=\"width:40px; margin:5px; display:block;\" onClick=\"move(this.form.elements['list1'], this.form.elements['list2'])\" value=\"&gt;&gt;\" />
            </td>
            <td>
              ".getescapedtext ($hcms_lang['assigned-publications'][$lang])."<br />
              <select multiple name=\"list2\" style=\"width:220px; height:180px;\" "; if ($preview == "yes") echo "disabled=\"disabled\""; echo ">\n";

              if (sizeof ($list2_array) >= 1)
              {
                natcasesort ($list2_array);
                
                foreach ($list2_array as $list2)
                {
                  echo $list2;
                }
              }

              echo "</select>
            </td>
          </tr>
        </table>      
      </td>
    </tr>\n";
    }    
    ?>
    <tr>
      <td>&nbsp;</td>
    </tr>
    <?php if (isset ($mgmt_config['not-supported'])) { ?>
    <tr>
      <td style="white-space:nowrap;">
        <label><input type="checkbox" name="inherit_obj_new" value="true" <?php if ($mgmt_config[$site_name]['inherit_obj'] == true) echo "checked=\"checked\""; if ($preview == "yes") echo " disabled=\"disabled\""; ?> /> <?php echo getescapedtext ($hcms_lang['cut-copy-and-paste-objects'][$lang]); ?></label>
      </td>
    </tr>
    <?php } ?>       
    <tr>
      <td style="white-space:nowrap;">
        <label><input type="checkbox" name="inherit_comp_new" value="true" <?php if ($mgmt_config[$site_name]['inherit_comp'] == true) echo "checked=\"checked\""; if ($preview == "yes") echo " disabled=\"disabled\""; ?> /> <?php echo getescapedtext ($hcms_lang['inherit-assets-content-not-editable'][$lang]); ?></label>
      </td>
      <td></td>
    </tr>   
    <tr>
      <td style="white-space:nowrap;">
        <label><input type="checkbox" name="inherit_tpl_new" value="true" <?php if ($mgmt_config[$site_name]['inherit_tpl'] == true) echo "checked=\"checked\""; if ($preview == "yes") echo " disabled=\"disabled\""; ?> /> <?php echo getescapedtext ($hcms_lang['inherit-templates-design-not-editable'][$lang]); ?></label>
      </td>
    </tr>       
    <tr>
      <td style="padding-top:10px; white-space:nowrap;">
        <?php echo getescapedtext ($hcms_lang['save-setting'][$lang]); ?>
        <img name="Button" src="<?php echo getthemelocation(); ?>img/button_ok.png" class="hcmsButtonTinyBlank hcmsButtonSizeSquare" onclick="selectAll();" onMouseOut="hcms_swapImgRestore()" onMouseOver="hcms_swapImage('Button','','<?php echo getthemelocation(); ?>img/button_ok_over.png',1)" title="OK" alt="OK" <?php if ($preview == "yes") echo " disabled"; ?> />
      </td>
    </tr>
  </table>
</form>
<?php } ?>

</div>

<?php includefooter(); ?>
</body>
</html>