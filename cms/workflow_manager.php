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
require_once ("language/workflow_manager.inc.php");


// input parameters
$site = getrequest_esc ("site", "publicationname");
$wf_file = getrequest ("wf_file", "locationname");
$wf_name = getrequest_esc ("wf_name", "objectname");

// publication management config
if (valid_publicationname ($site)) require ($mgmt_config['abs_path_data']."config/".$site.".conf.php");

// ------------------------------ permission section --------------------------------

// check permissions
if ($globalpermission[$site]['workflow'] != 1 || $globalpermission[$site]['workflowproc'] != 1 || $globalpermission[$site]['workflowprocedit'] != 1 || !valid_publicationname ($site)) killsession ($user);

// check session of user
checkusersession ($user);

// --------------------------------- logic section ----------------------------------

$show = "";

// get workflow file name
if (strpos ($wf_file, ".php?") > 0)
{
  // extract file name
  $wf_file = getattribute ($wf_file, "wf_file");
}
elseif ($wf_name != "" && $wf_file == "")
{
  // define workflow file
  if (valid_publicationname ($site) && valid_objectname ($wf_name)) $wf_file = $site.".".$wf_name.".xml";
  else $wf_file = "";
}

// get items if workflow already exists
if (isset ($wf_file) && @is_file ($mgmt_config['abs_path_data']."workflow_master/".$wf_file))
{
  // load workflow
  $workflow_data = loadfile ($mgmt_config['abs_path_data']."workflow_master/", $wf_file);
  
  if ($workflow_data != false)
  {  
    // build workflow stages
    $item_allstages_array = buildworkflow ($workflow_data);
    
    // count stages (1st dimension)
    $stage_max = sizeof ($item_allstages_array);
    
    // get max. count of user and script item in workflow (active and passive items)
    $wfusermax_array = getcontent ($workflow_data, "<usermax>"); 
    $wfusermax = $wfusermax_array[0];
    $scriptmax_array = getcontent ($workflow_data, "<scriptmax>"); 
    $scriptmax = $scriptmax_array[0];     
    
   // set start stage (stage 0 can only exist if passive items exist)
   if (isset ($item_allstages_array[0]) && sizeof ($item_allstages_array[0]) >= 1) 
   {
     $stage_start = 0;
     $stage_max = $stage_max - 1;
   }
   else 
   {
     $stage_start = 1;
   }
   
   // collect all id's for the select tags
   for ($stage=$stage_start; $stage<=$stage_max; $stage++)
   {
     foreach ($item_allstages_array[$stage] as $item)
     {
       $id_array = getcontent ($item, "<id>");
       $id_collect[] = $id_array[0];
     }
   }    
 }
 else $show = $text0[$lang]; 
}

// create secure token
$token_new = createtoken ($user);
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"
   "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<title>hyperCMS</title>
<meta http-equiv="Content-Type" content="text/html; charset=<?php echo $lang_codepage[$lang]; ?>">
<link rel="stylesheet" href="<?php echo getthemelocation(); ?>css/main.css" />
<script src="javascript/main.js" type="text/javascript"></script>
<script src="javascript/click.js" type="text/javascript"></script>
<script language="JavaScript">
<!--
function adduseritem()
{
  $count = document.forms['workflow'].elements['usermax'].value;
  document.forms['workflow'].elements['usermax'].value = ++$count;
  document.forms['workflow'].submit();
}

function addscriptitem()
{
  $count = document.forms['workflow'].elements['scriptmax'].value;
  document.forms['workflow'].elements['scriptmax'].value = ++$count;
  document.forms['workflow'].submit();
}

function removeuseritem()
{
  $count = document.forms['workflow'].elements['usermax'].value;
  document.forms['workflow'].elements['usermax'].value = --$count;
  document.forms['workflow'].submit();
}

function removescriptitem()
{
  $count = document.forms['workflow'].elements['scriptmax'].value;
  document.forms['workflow'].elements['scriptmax'].value = --$count;
  document.forms['workflow'].submit();
}
//-->
</script>
</head>

<body class="hcmsWorkplaceGeneric" leftmargin=0 topmargin=0 marginwidth=0 marginheight=0 onLoad="hcms_preloadImages('<?php echo getthemelocation(); ?>img/button_close_over.gif')">

<form name="workflow" method="post" action="workflow_build.php" style="height:100%">
  <input type="hidden" name="site" value="<?php echo $site; ?>" />
  <input type="hidden" name="wf_name" value="<?php echo $wf_name; ?>" />
  <input type="hidden" name="usermax" value="<?php echo $wfusermax; ?>" />
  <input type="hidden" name="scriptmax" value="<?php echo $scriptmax; ?>" />
  <input type="hidden" name="token" value="<?php echo $token_new; ?>">
  
<?php
if (isset ($stage_max) && $stage_max >= 0)
{  
  // collect all scripts
  $dir_item = @dir ($mgmt_config['abs_path_data']."workflow_master/");

  if ($dir_item != false)
  {
    while ($entry = $dir_item->read())
    {
      if ($entry != "." && $entry != ".." && !is_dir ($entry) && substr ($entry, 0, strpos ($entry, ".")) == $site)
      {
        if (strpos ($entry, ".inc.php") > strlen ($site."."))
        {
          $script_files[] = $entry;
        }
      }
    }

    $dir_item->close();
  }  
  
  // load user data
  $userdata = loadfile ($mgmt_config['abs_path_data']."user/", "user.xml.php");  
                    
  if ($userdata != false)
  {
    $alluseritem_array = selectcontent ($userdata, "<user>", "<publication>", "$site");

    if ($alluseritem_array != false)
    {    
      foreach ($alluseritem_array as $useritem)
      {  
        $buffer_array = getcontent ($useritem, "<login>");
        $alluser_array[] = $buffer_array[0];
      } 
    }
  }  
  
  // load usergroup data
  $groupdata = loadfile ($mgmt_config['abs_path_data']."user/", $site.".usergroup.xml.php");  
                    
  if ($groupdata != false)
  {
    $allgroup_array = getcontent ($groupdata, "<groupname>");
  }    
 
  // define item width and height including freespace
  $itemheight = 95;
  $itemwidth = 95;
  
  // define offset position
  $y = 10 - $itemheight;
  $y_offset = 150;
  $x_offset = 335; 
  
  $correct = false; 
   
  for ($stage=$stage_start; $stage<=$stage_max; $stage++)
  {
    // define y coordinate
    if ($stage == 0) 
    {
      $y = $y_offset;
      $correct = true;
    }
    else 
    {
      if ($correct == true) 
      {
        $y = 10 - $itemheight;
        $correct = false;
      }
      
      $y = $y + $itemheight;
    }
    
    // define offset x coordinate
    if ($stage == 0) 
    {
      $x = 70;
    }
    else
    {
      $itemcount = sizeof ($item_allstages_array[$stage]); 
      $x =  $x_offset - ($itemcount-1)/2 * $itemwidth;  
      if ($x < 200) $x = 200;
    }
  
    foreach ($item_allstages_array[$stage] as $item)
    {
      // get item data
      $buffer_array = getcontent ($item, "<id>"); // unique
      $id = $buffer_array[0];
      $id_suffix = substr ($id, strpos ($id, ".")+1);
      $buffer_array = getcontent ($item, "<type>"); // unique
      $type = $buffer_array[0];
      $buffer_array = getcontent ($item, "<user>"); // unique
      $wfuser = $buffer_array[0]; 
      $buffer_array = getcontent ($item, "<group>"); // unique
      $wfgroup = $buffer_array[0];      
      $buffer_array = getcontent ($item, "<script>"); // unique
      $script = $buffer_array[0];   
         
      $pre_array = getcontent ($item, "<pre>");
      if ($pre_array == false) $pre_array[] = "";
      $suc_array = getcontent ($item, "<suc>");
      if ($suc_array == false) $suc_array[] = "";
      
      $buffer_array = getcontent ($item, "<role>"); // unique
      $role = $buffer_array[0];
    
      // define type dependent data
      if ($type == "user" || $type == "usergroup")
      {        
        $itemtype = $text4[$lang];
        $itemimage = "workflow_user.gif";
      }
      elseif ($type == "script")
      {
        // collect script item id's for predecessor selection 
        $id_script_collect[] = $id;
        
        $itemtype = $text5[$lang];
        $itemimage = "workflow_script.gif";
      }
               
      // define z-index for layers
      $z1 = 1;
      $z2 = 100;
      
      // define y coordinates 
      if ($stage == 0) $y = $y + 20;   
      
      // define x coordinates  
      if ($stage != 0) $x = $x + $itemwidth;   
      
      // item layer
      echo "<div id=\"LayerItem".$id."\" style=\"position:absolute; width:70px; height:70px; z-index:".$z1."; left:".$x."px; top:".$y."px; visibility:visible\">
              <div id=\"LayerDrag".$id."\">
                <table width=\"80px\" height=\"80px\" cellspacing=0 cellpadding=2 class=\"hcmsContextMenu\">
                  <tr>
                    <td align=middle>
                      <a href=\"#\" onClick=\"hcms_showHideLayers('LayerProp".$id."','','show')\"><b>"; if ($id == "u.1") echo $text3[$lang]; else echo $itemtype." ".$id_suffix; echo "</b></a>
                    </td>
                  </tr>
                  <tr>
                    <td valign=\"top\" align=\"center\" class=\"hcmsRowHead1\"><img src=\"".getthemelocation()."img/".$itemimage."\" width=60 height=60 /></td>
                  </tr>
                </table>
              </div>\n";
      
      $z1++;
      
        // context menu with properties in item layer
        echo "<div id=\"LayerProp".$id."\" style=\"position:absolute; width:210px; height:115px; z-index:".$z2."; left: 10px; top:20px; visibility:hidden\">
          <table width=\"100%\" height=\"100%\" cellspacing=0 class=\"hcmsContextMenu\">
          <tr>
            <td align=\"left\" valign=\"top\">
                <table width=\"100%\" border=0 cellspacing=0 cellpadding=0 class=\"hcmsWorkplaceWorkflow\">
                  <tr>
                    <td class=\"hcmsHeadline\" style=\"text-align:left; vertical-align:middle; padding:2px 1px 1px 6px\">".$text7[$lang]." ".$itemtype." ".$id_suffix."</td>
                    <td style=\"width:26px; text-align:right; vertical-align:middle;\">
                      <a href=\"#\" onMouseOut=\"hcms_swapImgRestore()\" onMouseOver=\"hcms_swapImage('mediaClose".$id."','','".getthemelocation()."img/button_close_over.gif',1)\">
                        <img name=\"mediaClose".$id."\" src=\"".getthemelocation()."img/button_close.gif\" class=\"hcmsButtonBlank hcmsButtonSizeSquare\" onClick=\"hcms_showHideLayers('LayerProp".$id."','','hide')\" title=\"".$text16[$lang]."\" alt=\"".$text16[$lang]."\" />
                      </a>
                    </td>
                  </tr>
                </table>
                <input type=\"hidden\" name=\"item[".$id."]\" value=\"".$id."\" />\n";
                
                // user
                if ($type == "user" || $type == "usergroup")
                {
                  if ($type == "user") 
                  {
                    $selected_usr = " checked=\"checked\"";
                    $selected_grp = "";
                  }
                  elseif ($type == "usergroup")  
                  {
                    $selected_usr = "";
                    $selected_grp = " checked=\"checked\"";
                  }
                  else
                  {
                    $selected_usr = " checked=\"checked\"";
                    $selected_grp = "";                  
                  }
                
                  // user
                  echo "<input type=\"radio\" name=\"type[".$id."]\" value=\"user\" ".$selected_usr." /> ".$text4[$lang].":<br />\n";
                  echo "<select name=\"wfuser[".$id."]\" style=\"width:200px\">\n";                
                  echo "<option value=\"\">----- ".$text23[$lang]." -----</option>\n";
                     
                  if ($id == "u.1") 
                  {
                    if ($wfuser == "") $selected = " selected=\"selected\"";
                    else $selected = "";
                    echo "<option value=\"\" ".$selected.">".$text26[$lang]."</option>\n"; 
                  }                    
              
                  if ($alluser_array != false && sizeof ($alluser_array) >= 1)
                  {
                    sort ($alluser_array);
                    reset ($alluser_array);
                    
                    foreach ($alluser_array as $alluser)
                    {
                      if ($alluser == $wfuser) $selected = " selected=\"selected\"";
                      else $selected = "";
                    
                      if ($alluser != "admin" && $alluser != "sys" && $alluser != "hcms_download") echo "<option value=\"".$alluser."\" ".$selected.">".$alluser."</option>\n";
                    }
                  }                 
                  echo "</select><br />\n";  
                  
                  // user group
                  echo "<input type=\"radio\" name=\"type[".$id."]\" value=\"usergroup\" ".$selected_grp." /> ".$text27[$lang].":<br />\n";
                  echo "<select name=\"wfgroup[".$id."]\" style=\"width:200px;\">\n";                
                  echo "<option value=\"\">----- ".$text23[$lang]." -----</option>\n";
                     
                  if ($id == "u.1") 
                  {
                    if ($wfgroup == "") $selected = " selected=\"selected\"";
                    else $selected = "";
                    echo "<option value=\"\" ".$selected.">".$text26[$lang]."</option>\n"; 
                  }                    
              
                  if ($allgroup_array != false && sizeof ($allgroup_array) >= 1)
                  {
                    sort ($allgroup_array);
                    reset ($allgroup_array);
                    
                    foreach ($allgroup_array as $allgroup)
                    {
                      if ($allgroup == $wfgroup) $selected = " selected=\"selected\"";
                      else $selected = "";
                    
                      echo "<option value=\"".$allgroup."\" ".$selected.">".$allgroup."</option>\n";
                    }
                  }                 
                  echo "</select><br />\n";                    
                }
                // script file
                elseif ($type == "script")
                {
                  echo "".$text8[$lang].":<br />\n";
                  echo "<input type=\"hidden\" name=\"type[".$id."]\" value=\"script\" />\n";
                  echo "<select name=\"file[".$id."]\" style=\"width:200px;\">\n";                
                  echo "<option value=\"\">----- ".$text23[$lang]." -----</option>\n";                  
                  
                  if (sizeof ($script_files) >= 1)
                  {
                    sort ($script_files);
                    reset ($script_files);
      
                    foreach ($script_files as $value)
                    {
                      $script_name = substr ($value, strpos ($value, ".")+1);
                      $script_name = substr ($script_name, 0, strpos ($script_name, ".inc.php"));
                      
                      if ($script == $value) $selected = " selected=\"selected\"";
                      else $selected = "";
                      
                      echo "<option value=\"".$value."\" ".$selected.">".$script_name."</option>\n";
                    }
                  }     
                  echo "</select><br />\n";              
                }                  
                
                // predecessors
                if ($id != "u.1") 
                {                
                  echo "<img src=\"".getthemelocation()."img/workflow_positive.gif\" style=\"width:21px; height:16px;\" /> ".$text6[$lang].":<br />\n";
                  
                  if (in_array ("u.1", $pre_array)) $selected = " selected=\"selected\"";
                  else $selected = "";
                                    
                  echo "<select name=\"predecessor[".$id."][]\" size=\"3\" style=\"width:200px;\" multiple>\n";                
                  echo "<option value=\"u.1\" ".$selected.">".$text3[$lang]."</option>\n";
                    
                  $select_user = null;  
                  $select_script = null;
      
                  foreach ($id_collect as $id_single)
                  {
                    if ($id_single != $id && $id_single != "u.1")
                    {
                      if (in_array ($id_single, $pre_array)) $selected = " selected=\"selected\"";
                      else $selected = "";
                      
                      $id_single_suffix = substr ($id_single, strpos ($id_single, ".")+1);
                      
                      if ($id_single[0] == "u")
                      $select_user[$id_single_suffix] = "<option value=\"".$id_single."\" ".$selected.">".$text4[$lang]." ".$id_single_suffix."</option>\n";
                      elseif ($id_single[0] == "s")
                      $select_script[$id_single_suffix] = "<option value=\"".$id_single."\" ".$selected.">".$text5[$lang]." ".$id_single_suffix."</option>\n";
                    }
                  }

                  if (isset ($select_user))
                  {
                    sort ($select_user);
                    reset ($select_user);
                    
                    foreach ($select_user as $select) echo $select;
                  }
                  
                  if (isset ($select_script))
                  {
                    sort ($select_script);
                    reset ($select_script);
                    
                    foreach ($select_script as $select) echo $select;
                  }             
                  
                  echo "</select><br />\n";
                
                  // sucessors            
                  echo "<img src=\"".getthemelocation()."img/workflow_negative.gif\" style=\"width:21px; height:16px;\" /> <font color=\"#000000\">".$text15[$lang].":</font><br />\n";
                  
                  if (in_array ("u.1", $suc_array)) $selected = " selected=\"selected\"";
                  else $selected = "";
                                    
                  echo "<select name=\"successor[".$id."][]\" size=\"3\" style=\"width:200px;\" multiple>\n";                
                  echo "<option value=\"u.1\" ".$selected.">".$text3[$lang]."</option>\n";
      
                  $select_user = null;  
                  $select_script = null;
      
                  foreach ($id_collect as $id_single)
                  {
                    if ($id_single != $id && $id_single != "u.1")
                    {
                      if (in_array ($id_single, $suc_array)) $selected = " selected=\"selected\"";
                      else $selected = "";
                      
                      $id_single_suffix = substr ($id_single, strpos ($id_single, ".")+1);
                      
                      if ($id_single[0] == "u")
                      $select_user[$id_single_suffix] = "<option value=\"".$id_single."\" ".$selected.">".$text4[$lang]." ".$id_single_suffix."</option>\n";
                    }
                  }

                  if (isset ($select_user))
                  {
                    sort ($select_user);
                    reset ($select_user);
                    
                    foreach ($select_user as $select) echo $select;
                  }
      
                  echo "</select><br />\n";
                }                
                
                // role
                if ($type == "user" || $type == "usergroup")
                {    
                  $sel_r = "";
                  $sel_rw = "";
                  $sel_rx = "";
                  $sel_rwx = "";
                                     
                  if ($role == "r") $sel_r = " selected=\"selected\"";
                  elseif ($role == "rw") $sel_rw = " selected=\"selected\"";
                  elseif ($role == "x") $sel_rx = " selected=\"selected\"";
                  elseif ($role == "rx") $sel_rx = " selected=\"selected\"";
                  elseif ($role == "rwx") $sel_rwx = " selected=\"selected\"";
                          
                  echo "<img src=\"".getthemelocation()."img/workflow_permission.gif\" style=\"width:21px; height:16px;\" /> <font color=\"#000000\">".$text10[$lang].":</font><br />
                  <select name=\"role[".$id."]\" style=\"width:200px\">
                    <option value=\"r\" ".$sel_r.">".$text11[$lang]."</option>
                    <option value=\"rw\" ".$sel_rw.">".$text12[$lang]."</option>
                    <option value=\"rx\" ".$sel_rx.">".$text13[$lang]."</option>
                    <option value=\"rwx\" ".$sel_rwx.">".$text14[$lang]."</option>
                  </select><br />\n";
                }        
                
                // active
                if (($type == "user" || $type == "usergroup") && $id == "u.1") 
                {
                  echo "<input type=\"hidden\" name=\"active[".$id."]\" value=\"1\">\n";
                }
                else 
                {
                  // active (all items on stages 1 to n) 
                  if ($stage != 0) $checked = " checked=\"checked\"";
                  else $checked = "";                 
                
                  echo "<input type=\"checkbox\" name=\"active[".$id."]\" value=\"1\" ".$checked." />\n";
                  echo "<font color=\"#000000\">".$text9[$lang]."</font>\n";                          
                }
                
              echo "</td>
          </tr>
        </table>
        </div>
        <script type=\"text/javascript\">
        drag = document.getElementById('LayerDrag".$id."');
        elem = document.getElementById('LayerItem".$id."');
        hcms_drag(drag, elem);
        elem.onmouseover = function(e) {
          
          this.style.zIndex = 999;
          
        }
        elem.onmouseout = function(e) {
          
          this.style.zIndex = ".$z1.";
          
        }
        
        </script>
      </div> \n";
      
      $z2++;
    }
  }
}
?>

<?php
echo showmessage ($show, 600, 70, $lang, "position:absolute; left:5px; top:100px;")
?>
            
  <table width="100%" height="100%" border="0" cellspacing="0" cellpadding="0">
    <tr>
      <td class="hcmsWorkplaceWorkflow" width="220" align="left" valign="top">
        <table width="100%" height="100%" border="0" cellspacing="2" cellpadding="0">
          <tr>
            <td align="left" valign="top">
              <p class="hcmsHeadline"><?php echo $text17[$lang]; ?>:</span> <span class="hcmsHeadlineTiny"><?php echo $wf_name; ?></p>
                <!-- toolbar -->
                <div class="hcmsToolbar">
                  <div class="hcmsToolbarBlock">
                    <img onclick="document.forms['workflow'].submit();" class="hcmsButton hcmsButtonSizeSquare" name="Button1" src="<?php echo getthemelocation(); ?>img/button_save.gif" title="<?php echo $text22[$lang]; ?>" alt="<?php echo $text22[$lang]; ?>" />
                    
                  </div>
                  <div class="hcmsToolbarBlock">
                    <img onClick="adduseritem();" class="hcmsButton hcmsButtonSizeSquare" name="Button2" src="<?php echo getthemelocation(); ?>img/button_user_new.gif" title="<?php echo $text20[$lang]; ?>" alt="<?php echo $text20[$lang]; ?>" />
                    <img onClick="removeuseritem();" class="hcmsButton hcmsButtonSizeSquare" name="Button3" src="<?php echo getthemelocation(); ?>img/button_user_delete.gif" title="<?php echo $text24[$lang]; ?>" alt="<?php echo $text24[$lang]; ?>" />
                    
                  </div>
                  <div class="hcmsToolbarBlock">
                    <img onClick="addscriptitem();" class="hcmsButton hcmsButtonSizeSquare" name="Button4" src="<?php echo getthemelocation(); ?>img/button_script_new.gif" title="<?php echo $text21[$lang]; ?>" alt="<?php echo $text21[$lang]; ?>" />
                    <img onClick="removescriptitem();" class="hcmsButton hcmsButtonSizeSquare" name="Button5" src="<?php echo getthemelocation(); ?>img/button_script_delete.gif" title="<?php echo $text25[$lang]; ?>" alt="<?php echo $text25[$lang]; ?>" />                                  
                </div>
              </div>
              <p><span class="hcmsHeadline"><?php echo $text18[$lang]; ?>:</span><br />
              <span class="hcmsHeadlineTiny"><font size="1"><?php echo $text19[$lang]; ?></font></span></p><br />
            </td>
          </tr>
        </table>
      </td>
      <td valign="top" align="left" style="height:100%;">
        <table width="500px" height="100%" border="0" cellspacing="0" cellpadding="0">
          <tr>
            <td background="<?php echo getthemelocation(); ?>img/workflow_line.gif" valign="top" align="right">            
              &nbsp;
            </td>
          </tr>
          <tr>
            <td align="left" valign="top" height="199"><img src="<?php echo getthemelocation(); ?>img/workflow_pointer.gif" style="width:500px; height:199px;" /></td>
          </tr>
        </table>
      </td>
    </tr>
  </table>
</form>

</body>
</html>