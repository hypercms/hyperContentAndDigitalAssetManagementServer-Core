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
// disk key
require ("include/diskkey.inc.php");
// language file
require_once ("language/explorer.inc.php");

// plugin file
if (file_exists ($mgmt_config['abs_path_data']."config/plugin.conf.php"))
{
  require ($mgmt_config['abs_path_data']."config/plugin.conf.php");
}
else $mgmt_plugin = array();

$location = getrequest_esc ("location", "locationname", false);
$rnr = getrequest_esc ("rnr", "locationname", false);

// ------------------------------ permission section --------------------------------

// kill session if object linking is used and explorer is accessed
if (!$is_mobile && is_array ($hcms_linking)) killsession ($user);

// check session of user
checkusersession ($user, false);

// --------------------------------- logic section ----------------------------------

// class that represents a single menupoint with possible hcms_menupoint subpoints
class hcms_menupoint
{
  private $link;
  private $name;
  private $id;
  private $image;
  private $subpoints;
  private $onclick = false;
  private $target = false;
  private $nodeCSSClass = '';
  private $ajax_location = '';
  private $ajax_rnr = '';
  private $onmouseover = '';
  private $onmouseout = '';
  
  const DEFAULT_IDPRE = 'hcms_menupoint_';
  
  private static $counter = 1;
  
  public function __construct ($name, $link, $image, $id = '') 
  {
    $this->name = $name;
    
    // If we start with a / we don't use a image from our theme location
    if (substr ($image, 0, strlen ('http://')) == 'http://' || substr ($image, 0, strlen ('https://')) == 'https://')
      $this->image = $image;
    elseif (!empty($image))
      $this->image = getthemelocation().'img/'.$image;
    else
      $this->image = false;
    
    // build own id if none is given
    if (empty ($id)) 
    {
      $this->id = self::DEFAULT_IDPRE.self::$counter++;
    }
    else
    {
      $this->id = $id;
    }
    
    // building of the link
    if ($link == "#") $link = "#".$this->id;
    
    $this->link = $link;  
    $this->subpoints = array();
  }
  
  // set the css class of the li node
  public function setNodeCSSClass ($newClass)
  {
    $this->nodeCSSClass = $newClass;
  }  
  
  // add a single hcms_menupoint as a subpoint of this point
  public function addSubPoint (hcms_menupoint $mp) 
  {
    $this->subpoints[] = $mp;
  }
  
  // set the onclick script for the a element
  public function setOnClick ($onclick)
  {
    if (!empty ($onclick)) $this->onclick = $this->fixScript($onclick);
  }
  
  // set the onmouseover script for the a element
  public function setOnMouseOver ($onmouseover)
  {
    if (!empty ($onmouseover)) $this->onmouseover = $this->fixScript($onmouseover);

  }
  
  // Set the onmouseout script for the a element
  public function setOnMouseOut ($onmouseout)
  {
    if (!empty ($onmouseout)) $this->onmouseout = $this->fixScript($onmouseout);
  }
  
  // set the target for the a element
  public function setTarget ($target)
  {
    if (!empty ($target)) 
    {
      $this->target = $target;
    }
  }
  
  // set the data for the AJAX request
  public function setAjaxData ($location, $rnr = '')
  {
    // Add / at the end if not present
    if (substr ($location, strlen ($location)-1) != "/") $location .= "/";    
    $this->ajax_location = $location;
    
    if (!empty ($rnr))
    {
      $this->ajax_rnr = $rnr;
    }
  }
  
  // generates the html code for this point
  public function generateHTML () 
  {
    $html = array();
    // list element
    $lipart = '<li id="'.$this->id.'"';
    
    if (!empty ($this->nodeCSSClass))
    {
      $lipart .= ' class="'.$this->nodeCSSClass.'"';
    }
    
    $lipart .= '>';
    $html[] = $lipart;
    
    // eventually the AJAX data
    if(!empty ($this->ajax_location)) 
    {
      $html[] = '<span id="ajax_location_'.$this->id.'" style="display:none;">'.url_encode($this->ajax_location).'</span>';
      
      if(!empty ($this->ajax_rnr))
      {
        $html[] = '<span id="ajax_rnr_'.$this->id.'" style="display:none;">'.url_encode($this->ajax_rnr).'</span>';
      }
    }
    
    // an element
    $apart = '<a style="padding-left: 0px;" id="a_'.$this->id.'" name="a_'.$this->id.'" ';
    
    if ($this->onclick) $apart .= 'onclick="'.$this->onclick.'" ';
    if ($this->onmouseover) $apart .= 'onmouseover="'.$this->onmouseover.'" ';
    if ($this->onmouseout) $apart .= 'onmouseout="'.$this->onmouseout.'" ';
    if ($this->target) $apart .= 'target="'.$this->target.'" ';
    
    $apart .= 'href="'.$this->link.'">';
    
    // generating the ins tag in the a tag
    if ($this->image) $apart .= '<ins style="background-image: url(\''.$this->image.'\');" class="hcmsIconTree">&#160;</ins>';
    // text output
    $apart .= '<span id="context_name_'.$this->id.'">'.$this->name.'</span>';
    $apart .= '</a>';
    $html[] = $apart;
    
    // eventually add the subpoints
    if (!empty ($this->subpoints)) 
    {
      $html[] = '<ul>';
      
      foreach ($this->subpoints as $point)
      {
        $html[] = $point->generateHTML();
      }
      
      $html[] = '</ul>';
    }
    
    $html[] = '</li>';
    
    return implode ("\n", $html)."\n";
  }
  
  // fixes the script so that it can be used in the on* event.
  // adds a ; at the end of the script if not present and
  // exchanges " to ' because we use on*="<script>".
  protected function fixScript ($script)
  {
    // insert ; at the end if not there already
    if (substr ($script, strlen ($script)-1) != ";") $script .= ";";
    // replace " with 'since mouse events (on<event>="") is used 
    return str_replace ('"', "'", $script);
  }
}

// function that reads the accessible subfolder for a folder and returns an array containing a hcms_menupoint for each of those
function generateExplorerTree ($location, $user, $runningNumber=1) 
{
  global $mgmt_config, $pageaccess, $compaccess, $localpermission, $hiddenfolder;
  
  $site = getpublication ($location);
  $cat = getcategory ($site, $location);

  if (($cat == "comp" || $cat == "page") && valid_publicationname ($site) && valid_locationname ($location))
  {
    $location_esc = $location;
    $location = deconvertpath($location);
    $id = "";
    $rnrid = "";

    // full access to the folder
    if (accesspermission ($site, $location, $cat))
    {
      // get all files in dir
      $dir = @opendir ($location);
    
      if ($dir != false)
      {   
        $folder_array = array ();
        
        while ($folder = @readdir ($dir)) 
        { 
          // if directory
          if ($folder != "" && $folder != '.' && $folder != '..' && is_dir ($location.$folder)) 
          { 
            // check access permission
            $ownergroup = accesspermission ($site, $location.$folder."/", $cat);
            $setlocalpermission = setlocalpermission ($site, $ownergroup, $cat); 
     
            if ($setlocalpermission['root'] == 1)
            {
              $folder_array[] = $folder;
              // create folder object if it does not exist
              if (!is_file ($location.$folder."/.folder")) createobject ($site, $location.$folder."/", ".folder", "default.meta.tpl", "sys");
            }
          }
        }
        
        $result = array();
        
        // if we have access
        if (sizeof ($folder_array) > 0)
        {
          $index = 0;
          
          natcasesort ($folder_array);
          reset ($folder_array);
          
          $i = 1;
          
          foreach ($folder_array as $folder)
          {
            $index++;
            
            $folderinfo = getfileinfo ($site, $location.$folder, $cat);
            $foldername = $folderinfo['name'];
            $icon = $folderinfo['icon'];
            
            // the folder to be used for the AJAX request
            $ajaxfolder = $location_esc.$folder;
            
            $id = $cat.'_'.$site.'_';
            
            // generating the id from the running number so we don't have any ID problems
            if (!empty ($runningNumber))
            {
              $id .= $runningNumber.'_';
              $rnrid = $runningNumber.'_';
            }
            
            $id .= $i;
            $rnrid .= $i++;

            // generating the menupoint object with the needed configuration
            $point = new hcms_menupoint($foldername, 'frameset_objectlist.php?site='.url_encode($site).'&cat='.url_encode($cat).'&location='.url_encode($location_esc.$folder.'/'), $icon, $id);
            $point->setOnClick('hcms_jstree_open("'.$id.'");');
            $point->setTarget('workplFrame');
            $point->setNodeCSSClass('jstree-closed jstree-reload');
            $point->setAjaxData($ajaxfolder, $rnrid);
            $point->setOnMouseOver('hcms_setObjectcontext("'.$site.'", "'.$cat.'", "'.$location_esc.'", ".folder", "'.$foldername.'", "Folder", "", "'.$folder.'", "'.$id.'", $("#context_token").text());');
            $point->setOnMouseOut('hcms_resetContext();');
            $result[] = $point;
          }
        }
        
        @closedir ($dir);
    
        return $result;
      }
      else 
      {
        $errcode = "10178";
        $error[] = $mgmt_config['today']."|explorer.php|error|$errcode|root directory for publication $site is missing";         
    
        // save log
        savelog (@$error);   
      
        return false;
      }
    } 
    // only display subfolders the user has access to
    else
    {
      // select the appropriate access list
      if ($cat == "comp") 
      {
        $access = $compaccess;
        $right = 'component';
      }
      elseif ($cat == "page")
      {
        $access = $pageaccess;
        $right = 'page';
      }
      
      if (isset ($access[$site]) && is_array ($access[$site]))
      {
        $folder = array();
        
        foreach ($access[$site] as $group => $value)
        {  
          if ($localpermission[$site][$group][$right] == 1 && $value != "")
          { 
            // create folder array
            $folder_new = link_db_getobject ($value);
            
            foreach ($folder_new as $key => $value)
            {
              // path must be inside the location, avoid double entries
              if ($value != "" && substr ($value, 0, strlen ($location)) == $location)
              { 
                $folder[] = $value;
              }               
            }  
          }
        }
        
        $result = array();
        
        // if we have access anywhere
        if (is_array ($folder) && sizeof ($folder) > 0)
        {
          // remove double entries 
          $folder = array_unique ($folder);
          
          natcasesort ($folder);
          reset ($folder);   

          $i = 1;
          
          foreach ($folder as $path)
          {             
            $location_esc = convertpath ($site, $path, $cat); 
            $folderpath = getlocation ($location_esc);
            $folder = getobject ($location_esc);
            
            $folderinfo = getfileinfo ($site, $path, $cat);
            $foldername = $folderinfo['name'];
            $icon = $folderinfo['icon'];
            
            // the folder to be used for the AJAX request
            $ajaxfolder = $location_esc;
            
            $id = $cat.'_'.$site.'_';

            // generating the id from the running number so we don't have any ID problems
            if (!empty ($runningNumber))
            {
              $id .= $runningNumber.'_';
              $rnrid = $runningNumber.'_';
            }
            
            $id .= $i;
            $rnrid .= $i++;

            // Generating the menupoint object with the needed configuration
            $point = new hcms_menupoint($foldername, 'frameset_objectlist.php?site='.url_encode($site).'&cat='.$cat.'&location='.url_encode($location_esc), $icon, $id);
            $point->setOnClick('hcms_jstree_open("'.$id.'");');
            $point->setTarget('workplFrame');
            $point->setNodeCSSClass('jstree-closed jstree-reload');
            $point->setAjaxData($ajaxfolder, $rnrid);
            $point->setOnMouseOver('hcms_setObjectcontext("'.$site.'", "'.$cat.'", "'.$folderpath.'", ".folder", "'.$foldername.'", "Folder", "", "'.$folder.'", "'.$id.'", $("#context_token").text());');
            $point->setOnMouseOut('hcms_resetContext();');
            $result[] = $point;
          } 
        }
        return $result;
      } 
      else return array();
    }
  }
  else return false;
} 

// Generates a list of menupoints based on the points array from the plugins Array
// array contains the array with the points or groups
// folder tells us which folder the images reside in (typically thats the value from $mgmt_plugin[name]['folder']
function generatePluginTree ($array, $pluginKey, $folder, $groupKey=false,$site=false)
{
  global $mgmt_config;
  
  $return = array();
  
  foreach ($array as $key => $point)
  {
    // Name, Icon and either link or subpoints must be present
    if ( is_array ($point) && array_key_exists ('name', $point) && array_key_exists ('icon', $point) && (array_key_exists ('page', $point) || array_key_exists ('subpoints', $point)))
    {
      $icon = $point['icon'];
      
      if (array_key_exists('subpoints', $point) && is_array($point['subpoints']))
      {
        $id = str_replace (array(" ", "/", '\\'), "_", $pluginKey.($groupKey !== false ? $groupKey : "").'_'.$key);
        $curr = new hcms_menupoint ($point['name'], '#'.$id, $icon, $id);
        $curr->setOnClick ('hcms_jstree_toggle_preventDefault("'.$id.'", event);');
        $curr->setOnMouseOver ('hcms_resetContext();');
        
        if ($groupKey !== false) $key = $groupKey.'_'.$key;
        $sub = generatePluginTree ($point['subpoints'], $pluginKey, $folder, $key);
        foreach ($sub as $subpoint) $curr->addSubPoint ($subpoint);
        $return[] = $curr;
      } 
      else
      {
        $link = 'plugin_showpage.php?plugin='.urlencode($pluginKey).'&page='.urlencode($point['page']);
        
        if (array_key_exists ('control', $point) && !empty ($point['control']) && $point['control'])
        {
          $link .= '&control='.urlencode($point['control']);
        }
        
        if ($site)
        {
          $link .= '&site='.urlencode($site);
        }
        
        $curr = new hcms_menupoint($point['name'], $link, $icon);
        $curr->setOnClick('changeSelection(this)');
        $curr->setTarget('workplFrame');
        $curr->setOnMouseOver('hcms_resetContext();');
        $return[] = $curr;
      }
    }
  }
  
  return $return;
}

// if requested via AJAX only generate the navigation tree to be included
if (valid_locationname ($location))
{
  $tree = generateExplorerTree ($location, $user, $rnr);

  if (is_array ($tree)) 
  {
    // Generate the html for each point
    foreach ($tree as $point) 
    {
      echo $point->generateHTML();
    }
  }
} 
else 
{
  $tree = "";
  $maintree = "";

  // create secure token
  $token = createtoken ($user);

  // create main Menu points
  // ----------------------------------------- logout ---------------------------------------------- 
  $point = new hcms_menupoint ($text0[$lang], '#', 'logout.gif');
  $point->setOnClick('javascript:top.location.href="userlogout.php"');
  $point->setOnMouseOver('hcms_resetContext();');
  $maintree .= $point->generateHTML();

  // ----------------------------------------- home ---------------------------------------------- 
  $point = new hcms_menupoint ($text42[$lang], 'home.php', 'home.gif');
  $point->setOnClick('changeSelection(this)');
  $point->setTarget('workplFrame');
  $point->setOnMouseOver('hcms_resetContext();');
  $maintree .= $point->generateHTML();
  
  // ----------------------------------------- chat ---------------------------------------------- 
  if (!$is_mobile && isset ($mgmt_config['chat']) && $mgmt_config['chat'] == true)
  {
    $point = new hcms_menupoint ($text43[$lang], '#', 'chat.gif');
    $point->setOnClick('changeSelection(this); hcms_openChat();');
    $point->setOnMouseOver('hcms_resetContext();');
    $maintree .= $point->generateHTML();
  }
  
  // ----------------------------------------- desktop ---------------------------------------------- 
  if (!isset ($hcms_linking['location']) && checkrootpermission ('desktop'))
  {
    $point = new hcms_menupoint($text27[$lang], '#desktop', 'desk.gif', 'desktop');
    $point->setOnClick('hcms_jstree_toggle_preventDefault("desktop", event);');
    $point->setOnMouseOver('hcms_resetContext();');
    
    if (checkrootpermission ('desktopsetting')) 
    {
      $subpoint = new hcms_menupoint($text1[$lang], "user_edit.php?site=*Null*&login=".$user."&login_cat=home", 'userhome.gif');
      $subpoint->setOnClick('changeSelection(this)');
      $subpoint->setTarget('workplFrame');
      $subpoint->setOnMouseOver('hcms_resetContext();');
      $point->addSubPoint($subpoint);
    }
    
    if (checkrootpermission ('desktoptaskmgmt'))
    {
      $subpoint = new hcms_menupoint($text2[$lang], "task_list.php?site=*Null*", 'task.gif');
      $subpoint->setOnClick('changeSelection(this)');
      $subpoint->setTarget('workplFrame');
      $subpoint->setOnMouseOver('hcms_resetContext();');
      $point->addSubPoint($subpoint);
    }
    
    if (checkrootpermission ('desktopcheckedout'))
    {
      $subpoint = new hcms_menupoint($text13[$lang], "frameset_objectlist.php?virtual=1&action=checkedout", 'file_locked.gif');
      $subpoint->setOnClick('changeSelection(this)');
      $subpoint->setTarget('workplFrame');
      $subpoint->setOnMouseOver('hcms_resetContext();');
      $point->addSubPoint($subpoint);
    }
    
    if ($mgmt_config['db_connect_rdbms'])
    {
      $subpoint = new hcms_menupoint($text33[$lang], "frameset_queue.php?queueuser=".$user, 'queue.gif');
      $subpoint->setOnClick('changeSelection(this)');
      $subpoint->setTarget('workplFrame');
      $subpoint->setOnMouseOver('hcms_resetContext();');
      $point->addSubPoint($subpoint);
    }
    
    if (checkrootpermission ('desktoptimetravel'))
    {
      $subpoint = new hcms_menupoint($text31[$lang], "history.php", 'history.gif');
      $subpoint->setOnClick('changeSelection(this)');
      $subpoint->setTarget('workplFrame');
      $subpoint->setOnMouseOver('hcms_resetContext();');
      $point->addSubPoint($subpoint);
    }
    
    $maintree .= $point->generateHTML();
  }

  // ----------------------------------------- plugins ----------------------------------------------
  if (!empty ($mgmt_plugin))
  { 
    foreach ($mgmt_plugin as $key => $data)
    {
      // Only active plugins which have the correct keys are used
      if (is_array ($data) && array_key_exists ('active', $data) && $data['active'] == true && array_key_exists ('menu', $data) && is_array ($data['menu']) && array_key_exists ('main', $data['menu']) && is_array ($data['menu']['main']))
      {
        $pluginmenu = generatePluginTree ($data['menu']['main'], $key, $data['folder']);
        foreach ($pluginmenu as $point) $maintree .= $point->generateHTML();
      }
    }
  }
  
  // redefine siteaccess if linking is used
  if (isset ($hcms_linking['publication']) && valid_publicationname ($hcms_linking['publication']))
  {
    $siteaccess = array ($hcms_linking['publication']);
  }
  else
  {
    natcasesort ($siteaccess);
    reset ($siteaccess);
  }
  
  $set_site_admin = false;
  $index = 0;

  // loop through all publications
  foreach ($siteaccess as $site)  
  { 
    $index++;

    if (valid_publicationname ($site) || $site == "hcms_empty")
    {
      // include configuration file of site
      if (valid_publicationname ($site) && @is_file ($mgmt_config['abs_path_data']."config/".$site.".conf.php"))
      {
        @require ($mgmt_config['abs_path_data']."config/".$site.".conf.php");  
      }
      // no publication available
      else
      {
        $mgmt_config[$site]['site_admin'] = true;
      }
    
      // Publication specific Menu Points
      // ----------------------------------------- main administration ----------------------------------------------  
      if (!isset ($hcms_linking['location']) && $set_site_admin == false && $mgmt_config[$site]['site_admin'] == true)
      {
        $set_site_admin = true;
      
        if ((checkrootpermission ('site') || checkrootpermission ('user')) && strtolower ($diskkey) == "server")
        {
          $point = new hcms_menupoint($text3[$lang], '#main', 'admin.gif', 'main');
          $point->setOnClick('hcms_jstree_toggle_preventDefault("main", event);');
          $point->setOnMouseOver('hcms_resetContext();');
          
          if ($mgmt_config['instances'] && checkadminpermission () && checkrootpermission ('site') && is_file ($mgmt_config['abs_path_cms']."connector/instance/frameset_instance.php"))
          {
            $subpoint = new hcms_menupoint($text41[$lang], "connector/instance/frameset_instance.php?site=*Null*", 'instance.gif');
            $subpoint->setOnClick('changeSelection(this)');
            $subpoint->setTarget('workplFrame');
            $subpoint->setOnMouseOver('hcms_resetContext();');
            $point->addSubPoint($subpoint);
          }
          
          if (checkrootpermission ('site'))
          {
            $subpoint = new hcms_menupoint($text4[$lang], "frameset_site.php?site=*Null*", 'site.gif');
            $subpoint->setOnClick('changeSelection(this)');
            $subpoint->setTarget('workplFrame');
            $subpoint->setOnMouseOver('hcms_resetContext();');
            $point->addSubPoint($subpoint);
          }
          
          if (checkrootpermission ('user'))
          {
            $subpoint = new hcms_menupoint($text5[$lang], "frameset_user.php?site=*Null*", 'user.gif');
            $subpoint->setOnClick('changeSelection(this)');
            $subpoint->setTarget('workplFrame');
            $subpoint->setOnMouseOver('hcms_resetContext();');
            $point->addSubPoint($subpoint);
          }
          
          if (checkrootpermission ('site'))
          {
            $subpoint = new hcms_menupoint($text14[$lang], "frameset_log.php?site=*Null*", 'event.gif');
            $subpoint->setOnClick('changeSelection(this)');
            $subpoint->setTarget('workplFrame');
            $subpoint->setOnMouseOver('hcms_resetContext();');
            $point->addSubPoint($subpoint);
          }
          
          if (checkrootpermission ('site'))
          {
            $subpoint = new hcms_menupoint($text33[$lang], "frameset_queue.php", 'queue.gif');
            $subpoint->setOnClick('changeSelection(this)');
            $subpoint->setTarget('workplFrame');
            $subpoint->setOnMouseOver('hcms_resetContext();');
            $point->addSubPoint($subpoint);
          }
          
          if ($site != "hcms_empty" && checkrootpermission ('site') && is_file ($mgmt_config['abs_path_cms']."connector/imexport/frameset_imexport.php"))
          {
            $subpoint = new hcms_menupoint($text39[$lang], "connector/imexport/frameset_imexport.php?site=*Null*", 'imexport.gif');
            $subpoint->setOnClick('changeSelection(this)');
            $subpoint->setTarget('workplFrame');
            $subpoint->setOnMouseOver('hcms_resetContext();');
            $point->addSubPoint($subpoint);
          }
          
          if (checkrootpermission ('site'))
          {
            $subpoint = new hcms_menupoint($text36[$lang], "plugin_management.php", 'plugin.gif');
            $subpoint->setOnClick('changeSelection(this)');
            $subpoint->setTarget('workplFrame');
            $subpoint->setOnMouseOver('hcms_resetContext();');
            $point->addSubPoint($subpoint);
          }
          
          $maintree .= $point->generateHTML();
        }  
      }   
      
      // ------------------------------------------- publication node -----------------------------------------------
      if ($site != "hcms_empty")
      {
        $publication = new hcms_menupoint($site, '#site_'.$site, 'site.gif', 'site_'.$site);
        $publication->setOnClick('hcms_jstree_toggle_preventDefault("site_'.$site.'", event);');
        $publication->setOnMouseOver('hcms_resetContext();');
      
        // -------------------------------------------- administration ------------------------------------------------
        if (!isset ($hcms_linking['location']) && (checkglobalpermission ($site, 'user') || checkglobalpermission ($site, 'group')))
        {
          $point = new hcms_menupoint($text3[$lang], '#admin_'.$site, 'admin.gif', 'admin_'.$site);
          $point->setOnMouseOver('hcms_resetContext();');
          $point->setOnClick('hcms_jstree_toggle_preventDefault("admin_'.$site.'", event);');
            
          if (checkglobalpermission ($site, 'user'))
          {
            $subpoint = new hcms_menupoint($text5[$lang], "frameset_user.php?site=".url_encode($site), 'user.gif');
            $subpoint->setOnClick('changeSelection(this)');
            $subpoint->setTarget('workplFrame');
            $subpoint->setOnMouseOver('hcms_resetContext();');
            $point->addSubPoint($subpoint);
          }
          
          if (checkglobalpermission ($site, 'group'))
          {
            $subpoint = new hcms_menupoint($text6[$lang], "frameset_group.php?site=".url_encode($site), 'usergroup.gif');
            $subpoint->setOnClick('changeSelection(this)');
            $subpoint->setTarget('workplFrame');
            $subpoint->setOnMouseOver('hcms_resetContext();');
            $point->addSubPoint($subpoint);
          }
          
          if (strtolower($diskkey) != "server")
          {
            $subpoint = new hcms_menupoint($text14[$lang], "frameset_log.php?site=".url_encode($site), 'event.gif');
            $subpoint->setOnClick('changeSelection(this)');
            $subpoint->setTarget('workplFrame');
            $subpoint->setOnMouseOver('hcms_resetContext();');
            $point->addSubPoint($subpoint);
          }
                
          $publication->addSubPoint($point);
        }
        
        // ------------------------------------------ personalization -------------------------------------------------
        if (!isset ($hcms_linking['location']) && checkglobalpermission ($site, 'pers') && $mgmt_config[$site]['dam'] == false)
        {
          $point = new hcms_menupoint($text7[$lang], '#pers_'.$site, 'pers_registration.gif', 'pers_'.$site);
          $point->setOnClick('hcms_jstree_toggle_preventDefault("pers_'.$site.'", event);');
            
          if (checkglobalpermission ($site, 'perstrack'))
          {
            $subpoint = new hcms_menupoint($text8[$lang], "frameset_pers.php?site=".url_encode($site)."&cat=tracking", 'pers_registration.gif');
            $subpoint->setOnClick('changeSelection(this)');
            $subpoint->setTarget('workplFrame');
            $subpoint->setOnMouseOver('hcms_resetContext();');
            $point->addSubPoint($subpoint);
          }
          
          if (checkglobalpermission ($site, 'persprof'))
          {
            $subpoint = new hcms_menupoint($text9[$lang], "frameset_pers.php?site=".url_encode($site)."&cat=profile", 'pers_profile.gif');
            $subpoint->setOnClick('changeSelection(this)');
            $subpoint->setTarget('workplFrame');
            $subpoint->setOnMouseOver('hcms_resetContext();');
            $point->addSubPoint($subpoint);
          }
          
          $publication->addSubPoint($point);
        }
        
        // --------------------------------------------- workflow -----------------------------------------------------
        if (!$is_mobile && !isset ($hcms_linking['location']) && checkglobalpermission ($site, 'workflow'))
        {
          $point = new hcms_menupoint($text10[$lang], '#wrkflw_'.$site, 'workflow.gif', 'wrkflw_'.$site);
          $point->setOnClick('hcms_jstree_toggle_preventDefault("wrkflw_'.$site.'", event);');
            
          if (checkglobalpermission ($site, 'workflowproc'))
          {
            $subpoint = new hcms_menupoint($text11[$lang], "frameset_workflow.php?site=".url_encode($site)."&cat=man", 'workflow.gif');
            $subpoint->setOnClick('changeSelection(this)');
            $subpoint->setTarget('workplFrame');
            $subpoint->setOnMouseOver('hcms_resetContext();');
            $point->addSubPoint($subpoint);
          }
          
          if (checkglobalpermission ($site, 'workflowscript'))
          {
            $subpoint = new hcms_menupoint($text12[$lang], "frameset_workflow.php?site=".url_encode($site)."&cat=script", 'workflowscript.gif');
            $subpoint->setOnClick('changeSelection(this)');
            $subpoint->setTarget('workplFrame');
            $subpoint->setOnMouseOver('hcms_resetContext();');
            $point->addSubPoint($subpoint);
          }
          
          $publication->addSubPoint($point);
        }
        
        // --------------------------------------------- template ---------------------------------------------------
        if (!isset ($hcms_linking['location']) && checkglobalpermission ($site, 'template'))
        {
          $point = new hcms_menupoint($text15[$lang], '#template_'.$site, 'template.gif', 'template_'.$site);
          $point->setOnMouseOver('hcms_resetContext();');
          $point->setOnClick('hcms_jstree_toggle_preventDefault("template_'.$site.'", event);');
            
          if (checkglobalpermission ($site, 'tpl') && $mgmt_config[$site]['dam'] == false)
          {
            $subpoint = new hcms_menupoint($text16[$lang], "frameset_template.php?site=".url_encode($site)."&cat=page", 'template_page.gif');
            $subpoint->setOnClick('changeSelection(this)');
            $subpoint->setTarget('workplFrame');
            $subpoint->setOnMouseOver('hcms_resetContext();');
            $point->addSubPoint($subpoint);
          }
          
          if (checkglobalpermission ($site, 'tpl') && $mgmt_config[$site]['dam'] == false)
          {
            $subpoint = new hcms_menupoint($text17[$lang], "frameset_template.php?site=".url_encode($site)."&cat=comp", 'template_comp.gif');
            $subpoint->setOnClick('changeSelection(this)');
            $subpoint->setTarget('workplFrame');
            $subpoint->setOnMouseOver('hcms_resetContext();');
            $point->addSubPoint($subpoint);
          }
          
          if (checkglobalpermission ($site, 'tpl') && $mgmt_config[$site]['dam'] == false)
          {
            $subpoint = new hcms_menupoint($text40[$lang], "frameset_template.php?site=".url_encode($site)."&cat=inc", 'template_inc.gif');
            $subpoint->setOnClick('changeSelection(this)');
            $subpoint->setTarget('workplFrame');
            $subpoint->setOnMouseOver('hcms_resetContext();');
            $point->addSubPoint($subpoint);
          }
          
          if (checkglobalpermission ($site, 'tpl'))
          {
            $subpoint = new hcms_menupoint($text32[$lang], "frameset_template.php?site=".url_encode($site)."&cat=meta", 'template_media.gif');
            $subpoint->setOnClick('changeSelection(this)');
            $subpoint->setTarget('workplFrame');
            $subpoint->setOnMouseOver('hcms_resetContext();');
            $point->addSubPoint($subpoint);
          }
          
          if (checkglobalpermission ($site, 'tplmedia') && $mgmt_config[$site]['dam'] == false)
          {
            $subpoint = new hcms_menupoint($text20[$lang], "frameset_media.php?site=".url_encode($site)."&mediacat=tpl", 'media.gif');
            $subpoint->setOnClick('changeSelection(this)');
            $subpoint->setTarget('workplFrame');
            $subpoint->setOnMouseOver('hcms_resetContext();');
            $point->addSubPoint($subpoint);
          }
          
          $publication->addSubPoint($point);
        }
        
        // ----------------------------------------- plugins ----------------------------------------------
        if (!empty ($mgmt_plugin))
        { 
          foreach ($mgmt_plugin as $key => $data)
          {
            // Only active plugins which have the correct keys are used
            if (is_array ($data) && array_key_exists ('active', $data) && $data['active'] == true && array_key_exists ('menu', $data) && is_array ($data['menu']) && array_key_exists ('publication', $data['menu']) && is_array ($data['menu']['publication']))
            {
              $pluginmenu = generatePluginTree ($data['menu']['publication'], $key, $data['folder'], false, $site);
              foreach ($pluginmenu as $point) $publication->addSubPoint ($point);
            }
          }
          
        }
        
        // --------------------------------------------- component --------------------------------------------------
        // category of content: cat=comp
        if (checkglobalpermission ($site, 'component') && (!isset ($hcms_linking['cat']) || $hcms_linking['cat'] == "comp"))
        {
          // since version 5.6.3 the root folders also need to have containers
          // update comp/assets root
          $comp_root = deconvertpath ("%comp%/".$site."/", "file");
          // create folder object if it does not exist  
          if (!is_file ($comp_root.".folder")) createobject ($site, $comp_root, ".folder", "default.meta.tpl", "sys");
          
          // reset root location if linking is used
          if (isset ($hcms_linking['location']) && valid_locationname ($hcms_linking['location']))
          {
            if (isset ($hcms_linking['object']) && valid_objectname ($hcms_linking['object']))
            {
              $file_info = getfileinfo ($site, $hcms_linking['location'].$hcms_linking['object'], "comp");
              if ($file_info['type'] == "Folder") $location_root = $hcms_linking['location'].$hcms_linking['object']."/";
            }
            else $location_root = $hcms_linking['location'];
            
            $location_root = convertpath ($site, $location_root, "comp");
          }
          // use component root
          else $location_root = "%comp%/".$site."/";
  
          $point = new hcms_menupoint($text21[$lang], "frameset_objectlist.php?site=".url_encode($site)."&cat=comp&location=".url_encode($location_root)."&virtual=1", 'folder_comp.gif', 'comp_'.$site);
          $point->setOnClick('hcms_jstree_open("comp_'.$site.'", event);');
          $point->setTarget('workplFrame');
          $point->setNodeCSSClass('jstree-closed jstree-reload');
          $point->setAjaxData($location_root);
          $point->setOnMouseOver('hcms_setObjectcontext("'.$site.'", "comp", "'.getlocation($location_root).'", ".folder", "'.$text21[$lang].'", "Folder", "", "'.getobject($location_root).'", "comp_'.$site.'", $("#context_token").text());');
          $point->setOnMouseOut('hcms_resetContext();');
          $publication->addSubPoint($point);
        }
        
        // ----------------------------------------------- page ----------------------------------------------------
        // category of content: cat=page
        if (checkglobalpermission ($site, 'page') && $mgmt_config[$site]['abs_path_page'] != "" && $mgmt_config[$site]['dam'] == false && (!isset ($hcms_linking['cat']) || $hcms_linking['cat'] == "page"))
        {
          // since version 5.6.3 the root folders also need to have containers
          // update page root
          $page_root = deconvertpath ("%page%/".$site."/", "file");
          // create folder object if it does not exist
          if (!is_file ($page_root.".folder")) createobject ($site, $page_root, ".folder", "default.meta.tpl", "sys");
            
          // reset root location if linking is used
          if (isset ($hcms_linking['location']) && valid_locationname ($hcms_linking['location']))
          {
            if (isset ($hcms_linking['object']) && valid_objectname ($hcms_linking['object']))
            {
              $file_info = getfileinfo ($site, $hcms_linking['location'].$hcms_linking['object'], "page");
              if ($file_info['type'] == "Folder") $location_root = $hcms_linking['location'].$hcms_linking['object']."/";
            }
            else $location_root = $hcms_linking['location'];
            
            $location_root = convertpath ($site, $location_root, "page");
          }
          // use page root
          else $location_root = "%page%/".$site."/";
          
          $point = new hcms_menupoint($text22[$lang], "frameset_objectlist.php?site=".url_encode($site)."&cat=page&location=".url_encode($location_root)."&virtual=1", 'folder_page.gif', 'page_'.$site);
          $point->setOnClick('hcms_jstree_open("page_'.$site.'", event);');
          $point->setTarget('workplFrame');
          $point->setNodeCSSClass('jstree-closed jstree-reload');
          $point->setAjaxData($location_root);
          $point->setOnMouseOver('hcms_setObjectcontext("'.$site.'", "page", "'.getlocation($location_root).'", ".folder", "'.$text22[$lang].'", "Folder", "", "'.getobject($location_root).'", "comp_'.$site.'", $("#context_token").text());');
          $point->setOnMouseOut('hcms_resetContext();');
          $publication->addSubPoint($point);
        }
        
        $tree .= $publication->generateHTML();
      }
    }
  }  
  ?>
<!DOCTYPE html>
<html>
  <head>
    <title>hyperCMS</title>
    <meta http-equiv="Content-Type" content="text/html; charset=<?php echo $lang_codepage[$lang]; ?>">
    <meta name="viewport" content="width=260; initial-scale=1.0; user-scalable=0;">
    <script type="text/javascript" src="javascript/jquery/jquery-1.9.1.min.js"></script>
    <script type="text/javascript" src="javascript/jquery/plugins/jquery.cookie.js"></script>
    <script type="text/javascript" src="javascript/jquery/plugins/jquery.hotkeys.js"></script>
    <script type="text/javascript" src="javascript/jstree/jquery.jstree.js"></script>
    <script type="text/javascript" src="javascript/main.js"></script>
    <script type="text/javascript" src="javascript/contextmenu.js"></script>
    <script type="text/javascript">
    <!--
      // Variable where lastSelected element is stored
      var lastSelected = "";

      // set contect menu option
      var contextxmove = 0;
      var contextymove = 1;
      var contextenable = 1;

      // define global variable for popup window name used in contextmenu.js
      var session_id = '<?php session_id(); ?>';
      
      $(function ()
      {
        // We need to fix the html of the existing menupoint for jstree to work correctly (no newline and no more than one space)
        var html = $('#menupointlist').html();
        html = html.replace('\n', '');
        html = html.replace(/ {2,}/, '');
        
        // JS-TREE Configuration
        $("#menu").jstree({
          "plugins" : ["themes", "html_data"],
          "html_data" : {
            "data" : html,
            "ajax" : {
              "url" : function(node) {
                // Setting up the ajax link to gather the subfolders
                var pagelink = '<?php echo $mgmt_config['url_path_cms']; ?>explorer.php';
                var id = node.attr('id');
                
                var location = $('#ajax_location_'+id).text();
                pagelink += '?location='+location;
                
                var rnr = $('#ajax_rnr_'+id).text();
                pagelink += '&rnr='+rnr;
                
                return pagelink;
              },
              "cache" : false,
              "dataType" : 'html',
              "type" : 'GET',
              "async" : true
            }
          },
          "themes" : {
            "theme" : "hypercms",
            "dots" : false
          }
          // Whenever a node is opened we reload the node as data could have changed
        }).bind("open_node.jstree", function (e, data) {
          reloadNode(data.args[0]);
        })
      });
      
      // toggle a single node 
      function hcms_jstree_toggle(nodeName) 
      {
        $("#menu").jstree("toggle_node","#"+nodeName);
        changeSelection($("#"+nodeName).children('a'));
      }
      
      function hcms_jstree_toggle_preventDefault(nodeName, event) 
      {
        hcms_jstree_toggle(nodeName);
        event.preventDefault();
      }
      
      // just open a single node
      function hcms_jstree_open(nodeName) 
      {
        // We need to reload here because the content could have changed
        reloadNode("#"+nodeName);
        $("#menu").jstree("open_node","#"+nodeName);
        changeSelection($("#"+nodeName).children('a'));
      }
      
      function hcms_jstree_open_preventDefault(nodeName, event) 
      {
        hcms_jstree_open(nodeName);
        event.preventDefault();
      }
      
      // Reloads the data for a node via jstree functions if the node has the class jstree-reload
      function reloadNode(node) 
      {
        if($(node).hasClass('jstree-reload') && $(node).has('ul').length != 0)
        {
          $("#menu").jstree('refresh', node);
        }
      }
      
      // Changes the class so the node appears to be selected and the old node is unselected
      function changeSelection(node)
      {
        if(lastSelected != "")
        {
          lastSelected.children("span").removeClass('hcmsObjectSelected');
        }
        
        lastSelected = $(node);
        lastSelected.children("span").addClass('hcmsObjectSelected');
      }
    -->
    </script>
    <link rel="stylesheet" href="<?php echo getthemelocation(); ?>css/navigator.css">
  </head>
  
  <body class="hcmsWorkplaceExplorer">
  
  <?php if (!$is_mobile) { ?>
  <div style="position:fixed; right:0; top:45%; margin:0; padding:0;">
    <img onclick="parent.minNavFrame();" class="hcmsButtonTinyBlank hcmsButtonSizeSquare" alt="<?php echo $text34[$lang]; ?>" title="<?php echo $text34[$lang]; ?>" src="<?php echo getthemelocation(); ?>img/button_arrow_left.png" /><br />
    <img onclick="parent.maxNavFrame();" class="hcmsButtonTinyBlank hcmsButtonSizeSquare" alt="<?php echo $text35[$lang]; ?>" title="<?php echo $text35[$lang]; ?>" src="<?php echo getthemelocation(); ?>img/button_arrow_right.png" />
  </div>
  <?php } ?>
  
  <?php /* Saves the token for the context menu */ ?>
  <span id="context_token" style="display:none;"><?php echo $token; ?></span>
  <div id="contextLayer" style="position:absolute; width:150px; height:128px; z-index:10; left:20px; top:20px; visibility:hidden;"> 
    <form name="contextmenu_object" action="" method="post" target="popup_explorer">
      <input type="hidden" name="contextmenustatus" value="" />
      <input type="hidden" name="action" value="" />  
      <input type="hidden" name="force" value="" />  
      <input type="hidden" name="contexttype" value="" />
      <input type="hidden" name="xpos" value="" />
      <input type="hidden" name="ypos" value="" />
      <input type="hidden" name="site" value="" />
      <input type="hidden" name="cat" value="" />
      <input type="hidden" name="location" value="" />
      <input type="hidden" name="page" value="" />
      <input type="hidden" name="pagename" value="" />
      <input type="hidden" name="filetype" value="" />
      <input type="hidden" name="media" value="" />
      <input type="hidden" name="folder" value="" /> 
      <input type="hidden" name="folder_id" value="" /> 
      <input type="hidden" name="multiobject" value="" />
      <input type="hidden" name="token" value="" />
      
      <table width="150px" cellspacing="0" cellpadding="3" class="hcmsContextMenu">
        <tr>
          <td>
            <a href=# onClick="parent.location.href='userlogout.php';"><img src="<?php echo getthemelocation(); ?>img/button_logout.gif" align="absmiddle" border=0 />&nbsp;<?php echo $text0[$lang]; ?></a>
            <hr/>
            <a href=# id="href_cmsview" onClick="if (document.forms['contextmenu_object'].elements['contexttype'].value != 'none') hcms_createContextmenuItem ('cmsview');"><img src="<?php echo getthemelocation(); ?>img/button_file_edit.gif" id="img_cmsview" align="absmiddle" border=0 class="hcmsIconOn" />&nbsp;<?php echo $text37[$lang]; ?></a><br />
            <a href=# id="_href_notify" onClick="if (document.forms['contextmenu_object'].elements['contexttype'].value != 'none') hcms_createContextmenuItem ('notify');"><img src="<?php echo getthemelocation(); ?>img/button_notify.gif" id="_img_notify" align="absmiddle" border=0 class="hcmsIconOn">&nbsp;<?php echo $text38[$lang]; ?></a><br />   
            <hr/>
            <a href=# onClick="if (document.forms['contextmenu_object'].elements['contexttype'].value != 'none') hcms_createContextmenuItem ('publish');"><img id="img_publish" src="<?php echo getthemelocation(); ?>img/button_file_publish.gif" align="absmiddle" border=0 />&nbsp;<?php echo $text29[$lang]; ?></a><br />  
            <a href=# onClick="if (document.forms['contextmenu_object'].elements['contexttype'].value != 'none') hcms_createContextmenuItem ('unpublish');"><img id="img_unpublish" src="<?php echo getthemelocation(); ?>img/button_file_unpublish.gif" align="absmiddle" border=0 />&nbsp;<?php echo $text30[$lang]; ?></a><br />        
            <hr/>
            <a href=# onClick="document.location.href='explorer.php?refresh=1';"><img src="<?php echo getthemelocation(); ?>img/button_view_refresh.gif" align="absmiddle" border=0 />&nbsp;<?php echo $text24[$lang]; ?></a>
          </td>
        </tr>    
      </table>
    </form>
    </div>

    <div id="menu" style="margin-top:4px;">
      <ul id="menupointlist">
        <?php echo $maintree.$tree; ?>
      </ul>
    </div>

  </body>
</html>
<?php 
} 
?>