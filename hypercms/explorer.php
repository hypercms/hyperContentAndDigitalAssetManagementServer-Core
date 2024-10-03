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
// version info
require ("version.inc.php");


// plugin config
if (is_file ($mgmt_config['abs_path_data']."config/plugin.global.php"))
{
  require ($mgmt_config['abs_path_data']."config/plugin.global.php");
}
else $mgmt_plugin = array();

// input parameters
$location = getrequest_esc ("location", "locationname", false);
$rnr = getrequest_esc ("rnr", "locationname", false);
$explorer_view = getrequest ("view", "objectname");
$search_delete = getrequest ("search_delete");
$token = getrequest ("token");

// field size definitions
$width_searchfield = 410;

// ------------------------------ permission section --------------------------------

// check session of user
checkusersession ($user, false);

// no access if linking is in use
if (linking_valid() == true)
{
  echo showinfopage ($hcms_lang['you-do-not-have-permissions-to-access-this-feature'][$lang], $lang);
  exit;
}

// --------------------------------- logic section ----------------------------------

// initialize
$error = array();

// write and close session (non-blocking other frames)
suspendsession ();

// delete entry from saved search log
function searchlog_delete ($search_delete_id, $user)
{
  global $mgmt_config;

  if (is_file ($mgmt_config['abs_path_data']."log/".$user.".search.log"))
  {
    $searchlog_array = file ($mgmt_config['abs_path_data']."log/".$user.".search.log");

    if ($searchlog_array != false && sizeof ($searchlog_array) > 0)
    {
      $data = "";

      foreach ($searchlog_array as $searchlog)
      {
        if (strpos ($searchlog, "|") > 0)
        {
          list ($search_id, $rest) = explode ("|", trim ($searchlog));

          if ($search_id != $search_delete_id) $data .= $searchlog."\n";
        }
      }

      // save search log
      return savefile ($mgmt_config['abs_path_data']."log/", $user.".search.log", $data);
    }
  }

  return false;
}

// delete saved search
if (!empty ($search_delete) && checktoken ($token, $user))
{
  searchlog_delete ($search_delete, $user);
}

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
  private $ondrag = '';
  private $ondragstart = '';
  private $ondragover = '';
  private $ondrop = '';
  private $objectpath = '';
  private $draggable = false;

  const DEFAULT_IDPRE = 'hcms_menupoint_';

  private static $counter = 1;

  public function __construct ($name, $link, $image, $id = '') 
  {
    $this->name = $name;

    // If we start with a / we don't use an image from our theme location
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

    // building the link
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
  public function setAjaxData ($location, $rnr="")
  {
    // Add / at the end if not present
    if (substr ($location, strlen ($location)-1) != "/") $location .= "/";    
    $this->ajax_location = $location;
    
    if (!empty ($rnr))
    {
      $this->ajax_rnr = $rnr;
    }
  }

  // Set the ondrag script for the a element
  public function setOnDrag ($ondrag)
  {
    if (!empty ($ondrag)) $this->ondrag = $this->fixScript($ondrag);
  }

  // Set the ondragstart script for the a element
  public function setOnDragStart ($ondragstart)
  {
    if (!empty ($ondragstart)) $this->oondragstartndrag = $this->fixScript($ondragstart);
  }

  // Set the ondragover script for the a element
  public function setOnDragOver ($ondragover)
  {
    if (!empty ($ondragover)) $this->ondragover = $this->fixScript($ondragover);
  }

  // Set the ondrop script for the a element
  public function setOnDrop ($ondrop)
  {
    if (!empty ($ondrop)) $this->ondrop = $this->fixScript($ondrop);
  }

  // Set the objectpath for the a element
  public function setObjectPath ($objectpath)
  {
    if (!empty ($objectpath)) $this->objectpath = str_replace ('"', "'", $objectpath);
  }

  // Set the objectpath for the a element
  public function setDraggable ($draggable)
  {
    if (!empty ($draggable)) $this->draggable = true;
    else $this->draggable = false;
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
    if (!empty ($this->ajax_location)) 
    {
      $html[] = '<span id="ajax_location_'.$this->id.'" style="display:none;">'.url_encode($this->ajax_location).'</span>';
      
      if (!empty ($this->ajax_rnr))
      {
        $html[] = '<span id="ajax_rnr_'.$this->id.'" style="display:none;">'.url_encode($this->ajax_rnr).'</span>';
      }
    }

    // an element
    $apart = '<a class="hcmsNavigator" id="a_'.$this->id.'" name="a_'.$this->id.'" ';

    if ($this->onclick) $apart .= 'onclick="'.$this->onclick.'" ';
    if ($this->onmouseover) $apart .= 'onmouseover="'.$this->onmouseover.'" ';
    if ($this->onmouseout) $apart .= 'onmouseout="'.$this->onmouseout.'" ';
    if ($this->target) $apart .= 'target="'.$this->target.'" ';
    if ($this->ondrag) $apart .= 'ondrag="'.$this->ondrag.'" ';
    if ($this->ondragstart) $apart .= 'ondragstart="'.$this->ondragstart.'" ';
    if ($this->ondragover) $apart .= 'ondragover="'.$this->ondragover.'" ';
    if ($this->ondrop) $apart .= 'ondrop="'.$this->ondrop.'" ';
    if ($this->objectpath) $apart .= 'data-objectpath="'.$this->objectpath.'" ';
    if ($this->draggable) $apart .= 'draggable="true" ondragstart="hcms_drag(event)" ';

    $apart .= 'href="'.$this->link.'">';

    // generating the ins tag in the a tag
    if ($this->image) $apart .= '<ins style="background-image:url(\''.$this->image.'\');" class="hcmsIconTree">&#160;</ins>';
    // text output
    $apart .= $this->name;
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

  $location = correctpath ($location);
  $site = getpublication ($location);
  $cat = getcategory ($site, $location);

  if (($cat == "comp" || $cat == "page") && valid_publicationname ($site) && valid_locationname ($location))
  {
    $location_esc = $location;
    $location = deconvertpath ($location);
    $id = "";
    $rnrid = "";

    // full access to the folder
    if (accesspermission ($site, $location, $cat) && is_dir ($location))
    {
      // get all files in dir
      $scandir = scandir ($location);

      if ($scandir)
      {   
        $folder_array = array();

        foreach ($scandir as $folder) 
        {
          // if directory
          if ($folder != "" && $folder != '.' && $folder != '..' && is_dir ($location.$folder)) 
          { 
            // check access permission
            $ownergroup = accesspermission ($site, $location.$folder."/", $cat);
            $setlocalpermission = setlocalpermission ($site, $ownergroup, $cat); 

            if ($setlocalpermission['root'] == 1)
            {
              // remove _gsdata_ directory created by Cyberduck
              if ($folder == "_gsdata_")
              {
                deletefolder ($site, $location, $folder, $user);
              }
              else
              {
                $folder_array[] = $folder;

                // create folder object if it does not exist
                if (!is_file ($location.$folder."/.folder")) createobject ($site, $location.$folder."/", ".folder", "default.meta.tpl", "sys");
              }
            }
          }
        }

        $result = array();

        // if we have access
        if (sizeof ($folder_array) > 0)
        {
          natcasesort ($folder_array);
          reset ($folder_array);

          $i = 1;

          foreach ($folder_array as $folder)
          {
            $folderinfo = getfileinfo ($site, $location.$folder, $cat);

            // verify that folder has not been marked as deleted
            if ($folder != "" && $folderinfo['deleted'] == false)
            {
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
              $point->setOnDrop('hcms_drop(event);'); 
              $point->setOnDragOver('hcms_allowDrop(event)');
              $point->setObjectPath($location_esc.$folder);
              $point->setDraggable(true);
              $result[] = $point;
            }
          }
        }

        return $result;
      }
      else 
      {
        $errcode = "00178";
        $error[] = $mgmt_config['today']."|explorer.php|warning|".$errcode."|Directory ".$location_esc." is missing";         

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
        $folder_array = array ();

        foreach ($access[$site] as $group => $value)
        {
          if ($localpermission[$site][$group][$right] == 1 && $value != "")
          { 
            // create folder array
            $accesspath_array = link_db_getobject ($value);
            
            foreach ($accesspath_array as $key => $value)
            {
              // path must be inside the location, avoid double entries
              if ($value != "" && substr ($value, 0, strlen ($location)) == $location)
              {
                $folder_array[] = $value;
              }    
            }
          }
        }

        $result = array();

        // if we have access anywhere
        if (is_array ($folder_array) && sizeof ($folder_array) > 0)
        {
          // remove double entries 
          $folder_array = array_unique ($folder_array);

          natcasesort ($folder_array);
          reset ($folder_array);

          $i = 1;

          foreach ($folder_array as $path)
          {
            $folderinfo = getfileinfo ($site, $path, $cat);

            // verify that folder has not been marked as deleted
            if (trim ($path) != "" && $folderinfo['deleted'] == false)
            {
              $location_esc = convertpath ($site, $path, $cat); 
              $folderpath = getlocation ($location_esc);
              $folder = getobject ($location_esc);
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
              $point->setOnDrop('hcms_drop(event);'); 
              $point->setOnDragOver('hcms_allowDrop(event)');
              $point->setObjectPath($location_esc);
              $point->setDraggable(false);
              $result[] = $point;
            } 
          }
        }

        return $result;
      } 
      else return array();
    }
  }
  else return false;
}

// function that reads the metadata schema and returns an array containing a hcms_menupoint for each of those
function generateTaxonomyTree ($site, $tax_id, $runningNumber=1) 
{
  global $mgmt_config, $lang;

  if (valid_publicationname ($site))
  {
    $id = "";
    $rnrid = "";
    $result = array();

    // get taxonomy keyword list
    $tax_array = gettaxonomy_sublevel ($site, $lang, $tax_id, false);

    if (is_array ($tax_array) && sizeof ($tax_array) > 0)
    {
      $i = 1;

      foreach ($tax_array as $tax_id => $tax_keyword)
      {
        if (trim ($tax_keyword) != "")
        {
          $id = 'tax_'.$site.'_';

          // generating the id from the running number so we don't have any ID problems
          if (!empty ($runningNumber))
          {
            $id .= $runningNumber.'_';
            $rnrid = $runningNumber.'_';
          }

          $id .= $i;
          $rnrid .= $i++;

          // generating the menupoint object with the needed configuration
          $point = new hcms_menupoint(showshorttext ($tax_keyword, 32, false), 'frameset_objectlist.php?action=base_search&site='.url_encode($site).'&search_expression='.url_encode("%taxonomy%/".$site."/all/".$tax_id."/0"), "folder_taxonomy.png", $id);
          $point->setOnClick('hcms_jstree_open("'.$id.'");');
          $point->setTarget('workplFrame');
          $point->setNodeCSSClass('jstree-closed jstree-reload');
          $point->setOnMouseOver('hcms_resetContext();');
          $point->setAjaxData('%taxonomy%/'.$site.'/'.$lang.'/'.$tax_id.'/0', $rnrid);
          $result[] = $point;
        }
      }
    }

    return $result;
  }
  else return false;
}

// function that reads a specific text ID and returns an array containing a hcms_menupoint for each of those
function generateHierarchyTree ($hierarchy_url, $runningNumber=1) 
{
  global $mgmt_config, $hcms_lang, $lang;

  if ($hierarchy_url != "" && strpos ($hierarchy_url, "/") > 0)
  {
    $id = "";
    $rnrid = "";
    $result = array();

    // analyze hierarchy URL
    $hierarchy_url = trim ($hierarchy_url, "/");
    $hierarchy_array = explode ("/", $hierarchy_url);
    $site = $hierarchy_array[1];
    $name = $hierarchy_array[2];

    // get hierarchy keyword list
    $text_array = gethierarchy_sublevel ($hierarchy_url);

    if (is_array ($text_array) && sizeof ($text_array) > 0)
    {
      $i = 1;

      foreach ($text_array as $hierarchy_url => $label)
      {
        $id = 'text_'.$site.'_'.$name.'_';
        
        // generating the id from the running number so we don't have any ID problems
        if (!empty ($runningNumber))
        {
          $id .= $runningNumber.'_';
          $rnrid = $runningNumber.'_';
        }

        $id .= $i;
        $rnrid .= $i++;

        $hierarchy_url = trim ($hierarchy_url, "/");
        $hierarchy_array = explode ("/", $hierarchy_url);

        $site = $hierarchy_array[1];
        $last_text_id = end ($hierarchy_array);

        // if no text content is availbale
        if (trim ($label) == "") $label = $hcms_lang['none'][$lang];
        
        // generating the menupoint object with the needed configuration
        if (strpos ($last_text_id, "=") > 0)
        {
          $point = new hcms_menupoint(showshorttext ($label, 32, false), 'frameset_objectlist.php?action=base_search&site='.url_encode($site).'&search_expression='.url_encode($hierarchy_url), "folder_taxonomy.png", $id);
          $point->setOnClick('hcms_jstree_open("'.$id.'");');
          $point->setTarget('workplFrame');
          $point->setNodeCSSClass('jstree-closed jstree-reload');
          $point->setOnMouseOver('hcms_resetContext();');
          $point->setAjaxData($hierarchy_url, $rnrid);
          $result[] = $point;
        }
        else
        {
          $point = new hcms_menupoint(showshorttext ($label, 32, false), '#'.$id, "folder.png", $id);
          $point->setOnClick('hcms_jstree_open("'.$id.'");');
          $point->setNodeCSSClass('jstree-closed jstree-reload');
          $point->setOnMouseOver('hcms_resetContext();');
          $point->setAjaxData($hierarchy_url, $rnrid);
          $result[] = $point;
        }
      }
    }

    return $result;
  }
  else return false;
} 

// generates a list of menupoints based on the points array from the plugins Array
// array contains the array with the points or groups
// folder tells us which folder the images reside in (typically thats the value from $mgmt_plugin[name]['folder']
function generatePluginTree ($array, $pluginKey, $folder, $groupKey=false, $site=false)
{
  global $mgmt_config;

  $return = array();

  if (is_array ($array) && sizeof ($array) > 0)
  {
    foreach ($array as $key => $point)
    {
      // Name, Icon and either link or subpoints must be present
      if (is_array ($point) && array_key_exists ('name', $point) && array_key_exists ('icon', $point) && (array_key_exists ('page', $point) || array_key_exists ('subpoints', $point)))
      {
        $icon = $point['icon'];

        if (array_key_exists ('subpoints', $point) && is_array ($point['subpoints']))
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
          $link = 'plugin_showpage.php?plugin='.url_encode($pluginKey).'&page='.url_encode($point['page']);
          
          if (array_key_exists ('control', $point) && !empty ($point['control']) && $point['control'])
          {
            $link .= '&control='.url_encode($point['control']);
          }

          if ($site)
          {
            $link .= '&site='.url_encode($site);
          }

          $curr = new hcms_menupoint($point['name'], $link, $icon);
          $curr->setOnClick('changeSelection(this); minNavFrame();');
          $curr->setTarget('workplFrame');
          $curr->setOnMouseOver('hcms_resetContext();');
          $return[] = $curr;
        }
      }
    }
  }

  return $return;
}

// if requested via AJAX only generate the navigation tree to be included
if ($location != "")
{
  // folder location
  if (substr_count ($location, "%comp%/") > 0 || substr_count ($location, "%page%/") > 0)
  {
    $tree = generateExplorerTree ($location, $user, $rnr);
  }
  // taxonomy location
  elseif (substr_count ($location, "%taxonomy%/") > 0)
  {
    list ($domain, $site, $lang, $tax_id, $levels) = explode ("/", $location);
    $tree = generateTaxonomyTree ($site, $tax_id, $rnr);
  }
  // metadata hierarchy location
  elseif (substr_count ($location, "%hierarchy%/") > 0)
  {
    $tree = generateHierarchyTree ($location, $rnr);
  }

  if (!empty ($tree) && is_array ($tree)) 
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
  $token_new = createtoken ($user);

  if (is_array ($siteaccess))
  {
    natcasesort ($siteaccess);
    reset ($siteaccess);
  }

  // create main Menu points

  // ----------------------------------------- home ---------------------------------------------- 
  if (empty ($hcms_assetbrowser) && linking_valid() == false && $is_mobile)
  {
    $point = new hcms_menupoint ($hcms_lang['home'][$lang], 'home.php', 'home.png');
    $point->setOnClick('changeSelection(this); minNavFrame();');
    $point->setTarget('workplFrame');
    $point->setOnMouseOver('hcms_resetContext();');
    $maintree .= $point->generateHTML();
  }

  // ----------------------------------------- favorites (only for portal) ---------------------------------------------- 

  if (!empty ($hcms_portal) && checkrootpermission ('desktopfavorites'))
  {
    $point = new hcms_menupoint($hcms_lang['favorites'][$lang], "frameset_objectlist.php?virtual=1&action=favorites", 'favorites.png');
    $point->setOnClick('changeSelection(this); minNavFrame();');
    $point->setTarget('workplFrame');
    $point->setOnMouseOver('hcms_resetContext();');
    $maintree .= $point->generateHTML();
  }
  
  // ----------------------------------------- desktop ---------------------------------------------- 
  if (empty ($hcms_portal) && empty ($hcms_assetbrowser) && checkrootpermission ('desktop'))
  {
    $point = new hcms_menupoint($hcms_lang['desktop'][$lang], '#desktop', 'desk.png', 'desktop');
    $point->setOnClick('hcms_jstree_toggle_preventDefault("desktop", event);');
    $point->setOnMouseOver('hcms_resetContext();');

    if (checkrootpermission ('desktopsetting')) 
    {
      $subpoint = new hcms_menupoint($hcms_lang['personal-settings'][$lang], "user_edit.php?site=*Null*&login=".$user."&login_cat=home", 'userhome.png');
      $subpoint->setOnClick('changeSelection(this); minNavFrame();');
      $subpoint->setTarget('workplFrame');
      $subpoint->setOnMouseOver('hcms_resetContext();');
      $point->addSubPoint($subpoint);
    }

    if (checkrootpermission ('desktopprojectmgmt') && is_file ($mgmt_config['abs_path_cms']."project/project_list.php") && $mgmt_config['db_connect_rdbms'] != "")
    {
      $subpoint = new hcms_menupoint($hcms_lang['project-management'][$lang], "project/project_list.php", 'project.png');
      $subpoint->setOnClick('changeSelection(this); minNavFrame();');
      $subpoint->setTarget('workplFrame');
      $subpoint->setOnMouseOver('hcms_resetContext();');
      $point->addSubPoint($subpoint);
    }

    if (checkrootpermission ('desktoptaskmgmt') && is_file ($mgmt_config['abs_path_cms']."task/task_list.php") && $mgmt_config['db_connect_rdbms'] != "")
    {
      $subpoint = new hcms_menupoint($hcms_lang['task-management'][$lang], "task/task_list.php", 'task.png');
      $subpoint->setOnClick('changeSelection(this); minNavFrame();');
      $subpoint->setTarget('workplFrame');
      $subpoint->setOnMouseOver('hcms_resetContext();');
      $point->addSubPoint($subpoint);
    }

    if (checkrootpermission ('desktopfavorites'))
    {
      $subpoint = new hcms_menupoint($hcms_lang['favorites'][$lang], "frameset_objectlist.php?virtual=1&action=favorites", 'favorites.png');
      $subpoint->setOnClick('changeSelection(this); minNavFrame();');
      $subpoint->setTarget('workplFrame');
      $subpoint->setOnMouseOver('hcms_resetContext();');
      $point->addSubPoint($subpoint);
    }

    if (checkrootpermission ('desktopcheckedout'))
    {
      $subpoint = new hcms_menupoint($hcms_lang['checked-out-items'][$lang], "frameset_objectlist.php?virtual=1&action=checkedout", 'file_locked.png');
      $subpoint->setOnClick('changeSelection(this); minNavFrame();');
      $subpoint->setTarget('workplFrame');
      $subpoint->setOnMouseOver('hcms_resetContext();');
      $point->addSubPoint($subpoint);
    }

    if (!empty ($mgmt_config['db_connect_rdbms']))
    {
      $subpoint = new hcms_menupoint($hcms_lang['check-for-duplicates'][$lang], "frameset_objectlist.php?virtual=1&action=duplicates", 'button_file_copy.png');
      $subpoint->setOnClick('changeSelection(this); minNavFrame();');
      $subpoint->setTarget('workplFrame');
      $subpoint->setOnMouseOver('hcms_resetContext();');
      $point->addSubPoint($subpoint);
    }

    if (!empty ($mgmt_config['db_connect_rdbms']) && !empty ($mgmt_config['showclipboard']))
    {
      $subpoint = new hcms_menupoint($hcms_lang['clipboard'][$lang], "frameset_objectlist.php?virtual=1&action=clipboard", 'button_file_paste.png');
      $subpoint->setOnClick('changeSelection(this); minNavFrame();');
      $subpoint->setTarget('workplFrame');
      $subpoint->setOnMouseOver('hcms_resetContext();');
      $point->addSubPoint($subpoint);
    }

    if (!empty ($mgmt_config['db_connect_rdbms']) && !empty ($mgmt_config['recyclebin']))
    {
      $subpoint = new hcms_menupoint($hcms_lang['recycle-bin'][$lang], "frameset_objectlist.php?virtual=1&action=recyclebin", 'recycle_bin.png');
      $subpoint->setOnClick('changeSelection(this); minNavFrame();');
      $subpoint->setTarget('workplFrame');
      $subpoint->setOnMouseOver('hcms_resetContext();');
      $point->addSubPoint($subpoint);
    }

    $messageaccess = false;

    if (is_array ($siteaccess))
    {
      reset ($siteaccess);

      foreach ($siteaccess as $site_name => $displayname)
      {
        // include configuration file of publication if not included already
        if (valid_publicationname ($site_name) && is_file ($mgmt_config['abs_path_data']."config/".$site_name.".conf.php"))
        {
          @require ($mgmt_config['abs_path_data']."config/".$site_name.".conf.php");  
        }

        if (!empty ($mgmt_config[$site_name]['sendmail'])) $messageaccess = true;
      }
    }

    if ($messageaccess == true)
    {
      $subpoint = new hcms_menupoint($hcms_lang['messages'][$lang], "frameset_message.php", 'button_user_sendlink.png');
      $subpoint->setOnClick('changeSelection(this); minNavFrame();');
      $subpoint->setTarget('workplFrame');
      $subpoint->setOnMouseOver('hcms_resetContext();');
      $point->addSubPoint($subpoint);
    }

    if (!empty ($mgmt_config['db_connect_rdbms']))
    {
      $subpoint = new hcms_menupoint($hcms_lang['publishing-queue'][$lang], "frameset_queue.php?queueuser=".$user, 'queue.png');
      $subpoint->setOnClick('changeSelection(this); minNavFrame();');
      $subpoint->setTarget('workplFrame');
      $subpoint->setOnMouseOver('hcms_resetContext();');
      $point->addSubPoint($subpoint);
    }

    if (checkrootpermission ('desktoptimetravel'))
    {
      $subpoint = new hcms_menupoint($hcms_lang['travel-through-time'][$lang], "history.php", 'history.png');
      $subpoint->setOnClick('changeSelection(this); minNavFrame();');
      $subpoint->setTarget('workplFrame');
      $subpoint->setOnMouseOver('hcms_resetContext();');
      $point->addSubPoint($subpoint);
    }

    $maintree .= $point->generateHTML();
  }

  // ----------------------------------------- plugins ----------------------------------------------
  // Plugins require plugin access permission since version 9.1.6
  if (empty ($hcms_assetbrowser) && !empty ($mgmt_plugin) && is_array ($mgmt_plugin))
  { 
    foreach ($mgmt_plugin as $key => $data)
    {
      // check permission and plugin menu keys
      if (checkpluginpermission ("", $key) && !empty ($data['menu']['main']))
      {
        $pluginmenu = generatePluginTree ($data['menu']['main'], $key, $data['folder']);

        foreach ($pluginmenu as $point)
        {
          $maintree .= $point->generateHTML();
        }
      }
    }
  }

  $set_site_admin = false;

  if (is_array ($siteaccess))
  {
    reset ($siteaccess);

    // loop through all publications
    foreach ($siteaccess as $site => $displayname)  
    {
      if (valid_publicationname ($site) || $site == "hcms_empty")
      {
        // include configuration file of publication if not included already
        if ((empty ($mgmt_config[$site]) || !is_array ($mgmt_config[$site])) && is_file ($mgmt_config['abs_path_data']."config/".$site.".conf.php"))
        {
          require ($mgmt_config['abs_path_data']."config/".$site.".conf.php");  
        }
        // no publication available
        else
        {
          $mgmt_config[$site]['site_admin'] = true;
        }

        // load portal template for navigation settings
        $portalnavigation = array();

        if (!empty ($hcms_portal))
        {
          list ($portalsite, $portaltemplate) = explode ("/", $hcms_portal);

          // go to next publication
          if ($site != $portalsite) continue;
          
          // get navigation settings
          if ($site == $portalsite && !empty ($portaltemplate))
          {
            $result_load = loadtemplate ($portalsite, $portaltemplate.".portal.tpl");
            
            if (!empty ($result_load['result']))
            {
              $temp = getcontent ($result_load['content'], "<navigation>");
              if (!empty ($temp[0])) $portalnavigation = explode ("|", $temp[0]);
            }
          }
        }

        // Publication specific Menu Points
        // ----------------------------------------- main administration ----------------------------------------------  
        if (empty ($hcms_assetbrowser) && $set_site_admin == false && $mgmt_config[$site]['site_admin'] == true)
        {
          $set_site_admin = true;

          if ((checkrootpermission ('site') || checkrootpermission ('user')))
          {
            $point = new hcms_menupoint($hcms_lang['administration'][$lang], '#main', 'admin.png', 'main');
            $point->setOnClick('hcms_jstree_toggle_preventDefault("main", event);');
            $point->setOnMouseOver('hcms_resetContext();');

            if (is_file ($mgmt_config['abs_path_cms']."connector/instance/frameset_instance.php") && $mgmt_config['instances'] && checkadminpermission () && checkrootpermission ('site'))
            {
              $subpoint = new hcms_menupoint($hcms_lang['instance-management'][$lang], "connector/instance/frameset_instance.php?site=*Null*", 'instance.png');
              $subpoint->setOnClick('changeSelection(this); minNavFrame();');
              $subpoint->setTarget('workplFrame');
              $subpoint->setOnMouseOver('hcms_resetContext();');
              $point->addSubPoint($subpoint);
            }

            if (checkrootpermission ('site'))
            {
              $subpoint = new hcms_menupoint($hcms_lang['publication-management'][$lang], "frameset_site.php?site=*Null*", 'site.png');
              $subpoint->setOnClick('changeSelection(this); minNavFrame();');
              $subpoint->setTarget('workplFrame');
              $subpoint->setOnMouseOver('hcms_resetContext();');
              $point->addSubPoint($subpoint);
            }

            if (checkrootpermission ('user'))
            {
              $subpoint = new hcms_menupoint($hcms_lang['user-management'][$lang], "frameset_user.php?site=*Null*", 'user.png');
              $subpoint->setOnClick('changeSelection(this); minNavFrame();');
              $subpoint->setTarget('workplFrame');
              $subpoint->setOnMouseOver('hcms_resetContext();');
              $point->addSubPoint($subpoint);
            }

            if (checkrootpermission ('site'))
            {
              $subpoint = new hcms_menupoint($hcms_lang['system-events'][$lang], "frameset_log.php", 'event.png');
              $subpoint->setOnClick('changeSelection(this); minNavFrame();');
              $subpoint->setTarget('workplFrame');
              $subpoint->setOnMouseOver('hcms_resetContext();');
              $point->addSubPoint($subpoint);
            }

            if (checkrootpermission ('site'))
            {
              $subpoint = new hcms_menupoint($hcms_lang['publishing-queue'][$lang], "frameset_queue.php", 'queue.png');
              $subpoint->setOnClick('changeSelection(this); minNavFrame();');
              $subpoint->setTarget('workplFrame');
              $subpoint->setOnMouseOver('hcms_resetContext();');
              $point->addSubPoint($subpoint);
            }

            if (!$is_mobile && is_file ($mgmt_config['abs_path_cms']."connector/imexport/frameset_imexport.php") && $site != "hcms_empty" && checkrootpermission ('site'))
            {
              $subpoint = new hcms_menupoint($hcms_lang['importexport'][$lang], "connector/imexport/frameset_imexport.php?site=*Null*", 'imexport.png');
              $subpoint->setOnClick('changeSelection(this); minNavFrame();');
              $subpoint->setTarget('workplFrame');
              $subpoint->setOnMouseOver('hcms_resetContext();');
              $point->addSubPoint($subpoint);
            }

            if (is_file ($mgmt_config['abs_path_cms']."report/frameset_report.php") && $site != "hcms_empty" && checkrootpermission ('site'))
            {
              $subpoint = new hcms_menupoint($hcms_lang['report-management'][$lang], "report/frameset_report.php?site=*Null*", 'template.png');
              $subpoint->setOnClick('changeSelection(this); minNavFrame();');
              $subpoint->setTarget('workplFrame');
              $subpoint->setOnMouseOver('hcms_resetContext();');
              $point->addSubPoint($subpoint);
            }

            if (checkrootpermission ('site'))
            {
              $subpoint = new hcms_menupoint($hcms_lang['plugins'][$lang], "plugin_management.php", 'plugin.png');
              $subpoint->setOnClick('changeSelection(this); minNavFrame();');
              $subpoint->setTarget('workplFrame');
              $subpoint->setOnMouseOver('hcms_resetContext();');
              $point->addSubPoint($subpoint);
            }

            $maintree .= $point->generateHTML();
          }  
        }   

        // ------------------------------------------- publication node -----------------------------------------------
        if ((empty ($hcms_portal) || !empty ($mgmt_config[$site]['portalaccesslink'])) && $site != "hcms_empty")
        {
          $publication = new hcms_menupoint($displayname, '#site_'.$site, 'site.png', 'site_'.$site);
          $publication->setOnClick('hcms_jstree_toggle_preventDefault("site_'.$site.'", event);');
          $publication->setOnMouseOver('hcms_resetContext();');

          // -------------------------------------------- administration ------------------------------------------------
          if (empty ($hcms_assetbrowser) && (checkglobalpermission ($site, 'user') || checkglobalpermission ($site, 'group')))
          {
            $point = new hcms_menupoint($hcms_lang['administration'][$lang], '#admin_'.$site, 'admin.png', 'admin_'.$site);
            $point->setOnMouseOver('hcms_resetContext();');
            $point->setOnClick('hcms_jstree_toggle_preventDefault("admin_'.$site.'", event);');

            if (checkglobalpermission ($site, 'user'))
            {
              $subpoint = new hcms_menupoint($hcms_lang['user-management'][$lang], "frameset_user.php?site=".url_encode($site), 'user.png');
              $subpoint->setOnClick('changeSelection(this); minNavFrame();');
              $subpoint->setTarget('workplFrame');
              $subpoint->setOnMouseOver('hcms_resetContext();');
              $point->addSubPoint($subpoint);
            }

            if (checkglobalpermission ($site, 'group'))
            {
              $subpoint = new hcms_menupoint($hcms_lang['group-management'][$lang], "frameset_group.php?site=".url_encode($site), 'usergroup.png');
              $subpoint->setOnClick('changeSelection(this); minNavFrame();');
              $subpoint->setTarget('workplFrame');
              $subpoint->setOnMouseOver('hcms_resetContext();');
              $point->addSubPoint($subpoint);
            }

            // display publication system log if a publication log file exists
            if (checkglobalpermission ($site, 'user') && is_file ($mgmt_config['abs_path_data']."log/".$site.".publication.log"))
            {
              $subpoint = new hcms_menupoint($hcms_lang['system-events'][$lang], "frameset_log.php?site=".url_encode($site), 'event.png');
              $subpoint->setOnClick('changeSelection(this); minNavFrame();');
              $subpoint->setTarget('workplFrame');
              $subpoint->setOnMouseOver('hcms_resetContext();');
              $point->addSubPoint($subpoint);
            }
    
            $publication->addSubPoint($point);
          }

          // ------------------------------------------ personalization -------------------------------------------------
          if (empty ($hcms_assetbrowser) && !$is_mobile && checkglobalpermission ($site, 'pers') && empty ($mgmt_config[$site]['dam']))
          {
            $point = new hcms_menupoint($hcms_lang['personalization'][$lang], '#pers_'.$site, 'pers_registration.png', 'pers_'.$site);
            $point->setOnClick('hcms_jstree_toggle_preventDefault("pers_'.$site.'", event);');

            if (checkglobalpermission ($site, 'perstrack'))
            {
              $subpoint = new hcms_menupoint($hcms_lang['customer-tracking'][$lang], "frameset_pers.php?site=".url_encode($site)."&cat=tracking", 'pers_registration.png');
              $subpoint->setOnClick('changeSelection(this); minNavFrame();');
              $subpoint->setTarget('workplFrame');
              $subpoint->setOnMouseOver('hcms_resetContext();');
              $point->addSubPoint($subpoint);
            }

            if (checkglobalpermission ($site, 'persprof'))
            {
              $subpoint = new hcms_menupoint($hcms_lang['customer-profiles'][$lang], "frameset_pers.php?site=".url_encode($site)."&cat=profile", 'pers_profile.png');
              $subpoint->setOnClick('changeSelection(this); minNavFrame();');
              $subpoint->setTarget('workplFrame');
              $subpoint->setOnMouseOver('hcms_resetContext();');
              $point->addSubPoint($subpoint);
            }

            $publication->addSubPoint($point);
          }

          // --------------------------------------------- workflow -----------------------------------------------------
          if (empty ($hcms_assetbrowser) && is_file ($mgmt_config['abs_path_cms']."workflow/frameset_workflow.php") && !$is_mobile && checkglobalpermission ($site, 'workflow'))
          {
            $point = new hcms_menupoint($hcms_lang['workflow'][$lang], '#wrkflw_'.$site, 'workflow.png', 'wrkflw_'.$site);
            $point->setOnClick('hcms_jstree_toggle_preventDefault("wrkflw_'.$site.'", event);');

            if (checkglobalpermission ($site, 'workflowproc'))
            {
              $subpoint = new hcms_menupoint($hcms_lang['workflow-management'][$lang], "workflow/frameset_workflow.php?site=".url_encode($site)."&cat=man", 'workflow.png');
              $subpoint->setOnClick('changeSelection(this); minNavFrame();');
              $subpoint->setTarget('workplFrame');
              $subpoint->setOnMouseOver('hcms_resetContext();');
              $point->addSubPoint($subpoint);
            }
            
            if (checkglobalpermission ($site, 'workflowscript'))
            {
              $subpoint = new hcms_menupoint($hcms_lang['workflow-scripts'][$lang], "workflow/frameset_workflow.php?site=".url_encode($site)."&cat=script", 'workflowscript.png');
              $subpoint->setOnClick('changeSelection(this); minNavFrame();');
              $subpoint->setTarget('workplFrame');
              $subpoint->setOnMouseOver('hcms_resetContext();');
              $point->addSubPoint($subpoint);
            }

            $publication->addSubPoint($point);
          }

          // --------------------------------------------- template ---------------------------------------------------
          if (empty ($hcms_assetbrowser) && !$is_mobile && checkglobalpermission ($site, 'template'))
          {
            $point = new hcms_menupoint($hcms_lang['templates'][$lang], '#template_'.$site, 'template.png', 'template_'.$site);
            $point->setOnMouseOver('hcms_resetContext();');
            $point->setOnClick('hcms_jstree_toggle_preventDefault("template_'.$site.'", event);');

            if (checkglobalpermission ($site, 'tpl') && empty ($mgmt_config[$site]['dam']))
            {
              $subpoint = new hcms_menupoint($hcms_lang['page-templates'][$lang], "frameset_template.php?site=".url_encode($site)."&cat=page", 'template_page.png');
              $subpoint->setOnClick('changeSelection(this); minNavFrame();');
              $subpoint->setTarget('workplFrame');
              $subpoint->setOnMouseOver('hcms_resetContext();');
              $point->addSubPoint($subpoint);
            }

            if (checkglobalpermission ($site, 'tpl'))
            {
              $subpoint = new hcms_menupoint($hcms_lang['component-templates'][$lang], "frameset_template.php?site=".url_encode($site)."&cat=comp", 'template_comp.png');
              $subpoint->setOnClick('changeSelection(this); minNavFrame();');
              $subpoint->setTarget('workplFrame');
              $subpoint->setOnMouseOver('hcms_resetContext();');
              $point->addSubPoint($subpoint);
            }

            if (checkglobalpermission ($site, 'tpl'))
            {
              $subpoint = new hcms_menupoint($hcms_lang['template-includes'][$lang], "frameset_template.php?site=".url_encode($site)."&cat=inc", 'template_inc.png');
              $subpoint->setOnClick('changeSelection(this); minNavFrame();');
              $subpoint->setTarget('workplFrame');
              $subpoint->setOnMouseOver('hcms_resetContext();');
              $point->addSubPoint($subpoint);
            }

            if (checkglobalpermission ($site, 'tpl'))
            {
              $subpoint = new hcms_menupoint($hcms_lang['meta-data-templates'][$lang], "frameset_template.php?site=".url_encode($site)."&cat=meta", 'template_media.png');
              $subpoint->setOnClick('changeSelection(this); minNavFrame();');
              $subpoint->setTarget('workplFrame');
              $subpoint->setOnMouseOver('hcms_resetContext();');
              $point->addSubPoint($subpoint);
            }

            if (checkglobalpermission ($site, 'tplmedia'))
            {
              $subpoint = new hcms_menupoint($hcms_lang['template-media'][$lang], "frameset_media.php?site=".url_encode($site)."&mediacat=tpl", 'media.png');
              $subpoint->setOnClick('changeSelection(this); minNavFrame();');
              $subpoint->setTarget('workplFrame');
              $subpoint->setOnMouseOver('hcms_resetContext();');
              $point->addSubPoint($subpoint);
            }

            if (!empty ($mgmt_config[$site]['portalaccesslink']) && checkglobalpermission ($site, 'tpl'))
            {
              $subpoint = new hcms_menupoint($hcms_lang['portal-templates'][$lang], "frameset_portal.php?site=".url_encode($site), 'template_media.png');
              $subpoint->setOnClick('changeSelection(this); minNavFrame();');
              $subpoint->setTarget('workplFrame');
              $subpoint->setOnMouseOver('hcms_resetContext();');
              $point->addSubPoint($subpoint);
            }
            
            $publication->addSubPoint($point);
          }

          // ----------------------------------------- plugins ----------------------------------------------
          // Plugins require plugin access permission since version 9.1.6
          if (empty ($hcms_assetbrowser) && !empty ($mgmt_plugin) && is_array ($mgmt_plugin))
          { 
            foreach ($mgmt_plugin as $key => $data)
            {
              // check permission and plugin menu keys
              if (checkpluginpermission ($site, $key) && !empty ($data['menu']['publication']))
              {
                $pluginmenu = generatePluginTree ($data['menu']['publication'], $key, $data['folder'], false, $site);

                foreach ($pluginmenu as $point)
                {
                  $publication->addSubPoint($point);
                }
              }
            }
          }

          // ----------------------------------------- taxonomy ----------------------------------------------
          if ((empty ($hcms_portal) || in_array ("taxonomy", $portalnavigation)) && !empty ($mgmt_config[$site]['taxonomy']) && (checkglobalpermission ($site, 'component') || checkglobalpermission ($site, 'page')))
          {
            $point = new hcms_menupoint($hcms_lang['taxonomy'][$lang], '#tax_'.$site, 'folder_taxonomy.png', 'tax_'.$site);
            $point->setOnClick('hcms_jstree_open("tax_'.$site.'", event);');
            $point->setNodeCSSClass('jstree-closed jstree-reload');
            $point->setAjaxData('%taxonomy%/'.$site.'/'.$lang.'/0/0');
            $publication->addSubPoint($point);
          }

          // --------------------------------- metadata/content hierarchy -------------------------------------
          if (checkglobalpermission ($site, 'component') || checkglobalpermission ($site, 'page'))
          {
            $hierarchy = gethierarchy_definition ($site);

            if (is_array ($hierarchy) && sizeof ($hierarchy) > 0)
            {
              foreach ($hierarchy as $name => $level_array)
              {
                if (empty ($hcms_portal) || in_array (getescapedtext ($name), $portalnavigation))
                {
                  foreach ($level_array[1] as $text_id => $label_array)
                  {
                    if ($text_id != "")
                    {
                      if (!empty ($label_array[$lang])) $label = $label_array[$lang];
                      elseif (!empty ($label_array['default'])) $label = $label_array['default'];
                      else $label = "undefined";

                      $point = new hcms_menupoint(getescapedtext ($label), '#text_'.$site.'_'.$name, 'folder.png', 'text_'.$site.'_'.$name);
                      $point->setOnClick('hcms_jstree_open("text_'.$site.'_'.$name.'", event);');
                      $point->setNodeCSSClass('jstree-closed jstree-reload');
                      $point->setAjaxData('%hierarchy%/'.$site.'/'.$name.'/1/'.$text_id);
                      $publication->addSubPoint($point);
                    }
                  }
                }
              }
            }
          }

          // ----------------------------------------- assets/components ---------------------------------------------
          // category of content: cat=comp
          if ((empty ($hcms_portal) || in_array ("assets", $portalnavigation)) && is_dir ($mgmt_config['abs_path_comp'].$site."/") && checkglobalpermission ($site, 'component'))
          {
            // since version 5.6.3 the root folders also need to have containers
            // update comp/assets root
            $comp_root = deconvertpath ("%comp%/".$site."/", "file");

            // create folder object if it does not exist  
            if (!is_file ($comp_root.".folder") && is_writable ($comp_root)) createobject ($site, $comp_root, ".folder", "default.meta.tpl", "sys");

            // use component root
            $location_root = "%comp%/".$site."/";

            $point = new hcms_menupoint($hcms_lang['assets'][$lang], "frameset_objectlist.php?site=".url_encode($site)."&cat=comp&location=".url_encode($location_root)."&virtual=1", 'folder_comp.png', 'comp_'.$site);
            $point->setOnClick('hcms_jstree_open("comp_'.$site.'", event);');
            $point->setTarget('workplFrame');
            $point->setNodeCSSClass('jstree-closed jstree-reload');
            $point->setAjaxData($location_root);
            $point->setOnMouseOver('hcms_setObjectcontext("'.$site.'", "comp", "'.getlocation($location_root).'", ".folder", "'.getescapedtext ($hcms_lang['assets'][$lang]).'", "Folder", "", "'.getobject($location_root).'", "comp_'.$site.'", $("#context_token").text());');
            $point->setOnMouseOut('hcms_resetContext();');
            $point->setOnDrop('hcms_drop(event);'); 
            $point->setOnDragOver('hcms_allowDrop(event)');
            $point->setObjectPath($location_root);
            $point->setDraggable(false);
            $publication->addSubPoint($point);
          }

          // ----------------------------------------- page ----------------------------------------------
          // category of content: cat=page
          if (empty ($hcms_assetbrowser) && !empty ($mgmt_config[$site]['abs_path_page']) && is_dir ($mgmt_config[$site]['abs_path_page']) && checkglobalpermission ($site, 'page') && empty ($mgmt_config[$site]['dam']))
          {
            // since version 5.6.3 the root folders also need to have containers
            // update page root
            $page_root = deconvertpath ("%page%/".$site."/", "file");

            // create folder object if it does not exist
            if (!is_file ($page_root.".folder") && is_writable ($page_root)) createobject ($site, $page_root, ".folder", "default.meta.tpl", "sys");

            // use page root
            $location_root = "%page%/".$site."/";

            $point = new hcms_menupoint($hcms_lang['pages'][$lang], "frameset_objectlist.php?site=".url_encode($site)."&cat=page&location=".url_encode($location_root)."&virtual=1", 'folder_page.png', 'page_'.$site);
            $point->setOnClick('hcms_jstree_open("page_'.$site.'", event);');
            $point->setTarget('workplFrame');
            $point->setNodeCSSClass('jstree-closed jstree-reload');
            $point->setAjaxData($location_root);
            $point->setOnMouseOver('hcms_setObjectcontext("'.$site.'", "page", "'.getlocation($location_root).'", ".folder", "'.getescapedtext ($hcms_lang['pages'][$lang]).'", "Folder", "", "'.getobject($location_root).'", "comp_'.$site.'", $("#context_token").text());');
            $point->setOnMouseOut('hcms_resetContext();');
            $point->setOnDrop('hcms_drop(event);'); 
            $point->setOnDragOver('hcms_allowDrop(event)');
            $point->setObjectPath($location_root);
            $point->setDraggable(false);
            $publication->addSubPoint($point);
          }

          $tree .= $publication->generateHTML();
        }
      }
    }
  }  
  ?>
<!DOCTYPE html>
<html>
  <head>
    <title>hyperCMS</title>
    <meta charset="<?php echo getcodepage ($lang); ?>" />
    <meta name="viewport" content="width=260, initial-scale=1.0, maximum-scale=1.0, user-scalable=0" />
    <link rel="stylesheet" href="<?php echo getthemelocation(); ?>css/main.css?v=<?php echo getbuildnumber(); ?>" />
    <link rel="stylesheet" href="<?php echo getthemelocation()."css/".($is_mobile ? "mobile.css" : "desktop.css"); ?>?v=<?php echo getbuildnumber(); ?>" />

    <!-- JQuery (for navigation tree and autocomplete) -->
    <script type="text/javascript" src="javascript/jquery/jquery.min.js"></script>
    <script type="text/javascript" src="javascript/jquery-ui/jquery-ui.min.js"></script>
    <link rel="stylesheet" href="javascript/jquery-ui/jquery-ui.css" />
    <script type="text/javascript" src="javascript/jquery/plugins/jquery.cookie.js"></script>
    <script type="text/javascript" src="javascript/jstree/jquery.jstree.min.js"></script>
    
    <!-- main and contextmenu library -->
    <script type="text/javascript" src="javascript/main.min.js?v=<?php echo getbuildnumber(); ?>"></script>
    <script type="text/javascript" src="javascript/contextmenu.min.js?v=<?php echo getbuildnumber(); ?>"></script>

    <!-- Rich calendar -->
    <link  rel="stylesheet" type="text/css" href="javascript/rich_calendar/rich_calendar.css" />
    <script type="text/javascript" src="javascript/rich_calendar/rich_calendar.min.js"></script>
    <script type="text/javascript" src="javascript/rich_calendar/rc_lang_en.js"></script>
    <script type="text/javascript" src="javascript/rich_calendar/rc_lang_de.js"></script>
    <script type="text/javascript" src="javascript/rich_calendar/rc_lang_fr.js"></script>
    <script type="text/javascript" src="javascript/rich_calendar/rc_lang_pt.js"></script>
    <script type="text/javascript" src="javascript/rich_calendar/rc_lang_ru.js"></script>
    <script type="text/javascript" src="javascript/rich_calendar/domready.js"></script>

    <!-- Google Maps -->
    <script src="https://maps.googleapis.com/maps/api/js?v=3&key=<?php if (!empty ($mgmt_config['googlemaps_appkey'])) echo $mgmt_config['googlemaps_appkey']; ?>&callback=Function.prototype"></script>

    <script type="text/javascript">

    // reassign permissions for main.js and contextmenu.js
    hcms_permission['shortcuts'] = false;
    hcms_permission['minnavframe'] = false;

    // variable where lastSelected element is stored
    var lastSelected = "";

    // design theme
    themelocation = '<?php echo getthemelocation(); ?>';

    // set contect menu option
    contextenable = true;
    contextxmove = false;
    contextymove = true;

    $(function ()
    {
      // fix the html of the existing menupoint for jstree to work correctly (no newline and no more than one space)
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
    function hcms_jstree_toggle (nodeName) 
    {
      $("#menu").jstree("toggle_node","#"+nodeName);
      changeSelection($("#"+nodeName).children('a'));
    }

    function hcms_jstree_toggle_preventDefault (nodeName, event) 
    {
      hcms_jstree_toggle(nodeName);
      event.preventDefault();
    }

    // just open a single node
    function hcms_jstree_open (nodeName) 
    {
      // no need to reload here because the content could have been changed
      reloadNode("#"+nodeName);
      $("#menu").jstree("open_node","#"+nodeName);
      changeSelection($("#"+nodeName).children('a'));
    }

    function hcms_jstree_open_preventDefault (nodeName, event) 
    {
      hcms_jstree_open(nodeName);
      event.preventDefault();
    }

    // Reloads the data for a node via jstree functions if the node has the class jstree-reload
    function reloadNode (node) 
    {
      if ($(node).hasClass('jstree-reload') && $(node).has('ul').length != 0)
      {
        $("#menu").jstree('refresh', node);
      }
    }

    // Changes the class so the node appears to be selected and the old node is unselected
    function changeSelection (node)
    {
      if (lastSelected != "")
      {
        lastSelected.children("span").removeClass('hcmsObjectSelected');
      }

      lastSelected = $(node);
      lastSelected.children("span").addClass('hcmsObjectSelected');
    }

    // calendar
    var cal_obj = null;
    var cal_format = null;
    var cal_field = null;

    function show_cal (el, field_id, format)
    {
      cal_field = field_id;
      cal_format = format;
      var datefield = document.getElementById(field_id);

      cal_obj = new RichCalendar();
      cal_obj.start_week_day = 1;
      cal_obj.show_time = false;
      cal_obj.language = '<?php echo getcalendarlang ($lang); ?>';
      cal_obj.user_onchange_handler = cal_on_change;
      cal_obj.user_onautoclose_handler = cal_on_autoclose;
      cal_obj.parse_date(datefield.value, cal_format);
      cal_obj.show_at_element(datefield, 'adj_left-top');
    }

    // onchange handler
    function cal_on_change (cal, object_code)
    {
      if (object_code == 'day')
      {
        document.getElementById(cal_field).value = cal.get_formatted_date(cal_format);
        cal.hide();
        cal_obj = null;
      }
    }

    // onautoclose handler
    function cal_on_autoclose (cal)
    {
      cal_obj = null;
    }

    // Google Maps JavaScript API v3: Map Simple
    var map;
    var dragging = false;
    var rightclick = false;
    var rect;
    var pos1, pos2;
    var latlng1, latlng2;

    function initRectangle ()
    {
      rect = new google.maps.Rectangle({
        map: map,
        strokeColor: '#359FFC', 
        fillColor: '#65B3FC',
        fillOpacity: 0.15,
        strokeWeight: 0.9,
        clickable: false
      });
    }

    function initMap ()
    {
      var mapOptions = {
          zoom: 1,
          scrollwheel: true,
          center: new google.maps.LatLng(0, 0),
          disableDefaultUI: true,
          mapTypeId: google.maps.MapTypeId.ROADMAP
      };

      map = new google.maps.Map(document.getElementById('map'), mapOptions);

      <?php if (!$is_mobile) { ?>  
      initRectangle();

      document.getElementById('map').onmousedown = function(e) {
        e = e || window.event;

        // right mouse click
        if ((e.which && e.which == 3) || (e.button && e.button == 2))
        {
          rightclick = true;

          // hide context menu
          setTimeout (hcms_hideContextmenu, 10);
        }
        // left mouse click
        else if ((e.which && (e.which == 0 || e.which == 1)) || (e.button && (e.button == 0 || e.button == 1)))
        {
          map.setOptions({draggable: true});

          if (rect)
          {
            // reset rectangle
            rect.setMap(null);
            initRectangle();

            document.forms['searchform_advanced'].elements['geo_border_sw'].value = '';
            document.forms['searchform_advanced'].elements['geo_border_ne'].value = '';
          }
        }
      }

      google.maps.event.addListener(map, 'mousedown', function(e) {
        map.setOptions({draggable: false});

        // current position on the map
        latlng1 = e.latLng;
        dragging = true;
        pos1 = e.pixel;
      });

      google.maps.event.addListener(map, 'mousemove', function(e) {
        // current position on the map
        latlng2 = e.latLng;
    
        // display rectangle
        if (dragging && rightclick)
        {
          if (rect === undefined)
          {
            rect = new google.maps.Rectangle({
                map: map
            });
          }
          
          var latLngBounds = new google.maps.LatLngBounds(latlng1, latlng2);
          rect.setBounds(latLngBounds);
        }
      });

      google.maps.event.addListener(map, 'mouseup', function(e) {
        map.setOptions({draggable: true});
        dragging = false;
        rightclick = false;

        if (rect && rect.getBounds() !== undefined)
        {
          var borderSW = rect.getBounds().getSouthWest();
          var borderNE = rect.getBounds().getNorthEast();

          document.forms['searchform_advanced'].elements['geo_border_sw'].value = borderSW;
          document.forms['searchform_advanced'].elements['geo_border_ne'].value = borderNE;
        }
      });

      <?php } else { ?>
      google.maps.event.addListener(map, 'bounds_changed', function() {
        if (map.getBounds() !== undefined)
        {
          var borderSW = map.getBounds().getSouthWest();
          var borderNE = map.getBounds().getNorthEast();
        
          document.forms['searchform_advanced'].elements['geo_border_sw'].value = borderSW;
          document.forms['searchform_advanced'].elements['geo_border_ne'].value = borderNE;
        }
      });
      <?php } ?>
    }

    // delete saved search entry
    function deleteSearch ()
    {
      var element = document.forms['searchform_advanced'].elements['search_execute'];

      if (element.options[element.selectedIndex].value != '')
      {
        check = confirm ("<?php echo getescapedtext ($hcms_lang['are-you-sure-you-want-to-remove-the-item'][$lang]); ?>");
      
        if (check == true)
        {
          document.location='?view=search&search_delete=' + element.options[element.selectedIndex].value + '&token=<?php echo $token_new; ?>';
        }
      }
    }

    // minimize navigation frame
    function minNavFrame ()
    {
      <?php if ($is_mobile) echo "parent.minNavFrame();"; else echo "return true;"; ?>
    }

    // show search layer
    function showSearch ()
    {
      document.getElementById('search').style.display = 'inline';
      document.getElementById('menu').style.display = 'none';
      parent.maxSearchFrame();
    }

    // show navigation layer
    function showNav ()
    {
      window.scrollTo (0, 0);
      document.getElementById('search').style.display = 'none';
      document.getElementById('menu').style.display = 'inline';
      parent.maxNavFrame();
    }

    // color search options
    function unsetColors ()
    {
      if (document.getElementById('unsetcolors').checked == true)
      {
        var colors = document.getElementsByClassName('hcmsColorKey');
        var i;

        for (i = 0; i < colors.length; i++)
        {
          colors[i].checked = false;
        }
      }
    }

    function setColors ()
    {
      document.getElementById('unsetcolors').checked = false;
    }

    // switch templates in the template select box of the advanced search form
    function switchTemplates ()
    {
      var selectbox_site = document.forms['searchform_advanced'].elements['site'];
      var site = selectbox_site.options[selectbox_site.selectedIndex].value;
      var selectbox_template = document.forms['searchform_advanced'].elements['template'];
      
      // skip first select option
      for (var i=1; i<=selectbox_template.options.length; i++)
      {
        if (selectbox_template.options[i])
        {
          var option = selectbox_template.options[i];

          if (site != "" && option.value.indexOf(site+"/") == -1) option.style.display = "none";
          else option.style.display = "inline";
        }
      }
    }

    // load advanced search form
    function loadForm ()
    {
      var selectbox = document.forms['searchform_advanced'].elements['template'];
      var template = selectbox.options[selectbox.selectedIndex].value;
      
      if (template != "")
      {
        hcms_loadPage('contentFrame', 'search_form_advanced.php?template=' + template + '&css_display=block');
        return true;
      }
      else
      {
        hcms_loadPage('contentFrame', 'search_form_advanced.php?template=&css_display=block');
        return true;
      }
    }

    // activate search options
    function activateFulltextSearch ()
    {
      if (document.getElementById('fulltextLayer').style.display == 'none')
      {
        document.forms['searchform_advanced'].elements['action'].value = 'base_search';
        hcms_showFormLayer('fulltextLayer',0);
        hcms_hideFormLayer('advancedLayer');
        hcms_hideFormLayer('searchreplaceLayer');
        hcms_hideFormLayer('keywordsLayer');
        hcms_hideFormLayer('idLayer');
      }
      else
      {
        hcms_hideFormLayer('fulltextLayer');
      }
    }

    function activateAdvancedSearch ()
    {
      if (document.getElementById('advancedLayer').style.display == 'none')
      {
        document.forms['searchform_advanced'].elements['action'].value = 'base_search';
        hcms_hideFormLayer('fulltextLayer');
        hcms_hideFormLayer('searchreplaceLayer');
        hcms_showFormLayer('advancedLayer',0);
        hcms_hideFormLayer('keywordsLayer');
        hcms_hideFormLayer('idLayer');
      }
      else
      {
        hcms_hideFormLayer('advancedLayer');
      }
    }

    function activateSearchReplace ()
    {
      if (document.getElementById('searchreplaceLayer').style.display == 'none')
      {
        document.forms['searchform_advanced'].elements['action'].value = 'search';
        hcms_hideFormLayer('fulltextLayer');
        hcms_hideFormLayer('advancedLayer');
        hcms_showFormLayer('searchreplaceLayer',0);
        hcms_hideFormLayer('keywordsLayer');
        hcms_hideFormLayer('imageLayer');
        hcms_hideFormLayer('mapLayer');
        hcms_hideFormLayer('idLayer');
        hcms_hideFormLayer('recipientLayer');
        hcms_hideFormLayer('saveLayer');
      }
      else
      {
        hcms_hideFormLayer('searchreplaceLayer');
      }
    }

    function activateKeywordSearch ()
    {
      if (document.getElementById('keywordsLayer').style.display == 'none')
      {
        if (document.getElementById('keywordsFrame').src.indexOf('explorer_keywords.php') == -1)
        {
          document.getElementById('keywordsFrame').src = "explorer_keywords.php?ts=<?php echo time(); ?>";
        }
      
        document.forms['searchform_advanced'].elements['action'].value = 'base_search';
        hcms_hideFormLayer('fulltextLayer');
        hcms_hideFormLayer('advancedLayer');
        hcms_hideFormLayer('searchreplaceLayer');
        hcms_showFormLayer('keywordsLayer',0);
        hcms_hideFormLayer('idLayer');
      }
      else
      {
        hcms_hideFormLayer('keywordsLayer');
      }
    }

    function activateFiletypeSearch ()
    {
      if (document.getElementById('filetypeLayer').style.display == 'none')
      {
        // do not set due to search & replace:
        // document.forms['searchform_advanced'].elements['action'].value = 'base_search';
        hcms_showFormLayer('filetypeLayer',0);
        hcms_hideFormLayer('idLayer');
      }
      else
      {
        hcms_hideFormLayer('filetypeLayer');
      }
    }

    function activateImageSearch ()
    {
      if (document.getElementById('imageLayer').style.display == 'none')
      {
        document.forms['searchform_advanced'].elements['action'].value = 'base_search';
        hcms_showFormLayer('imageLayer',0);
        hcms_hideFormLayer('idLayer');
      }
      else
      {
        hcms_hideFormLayer('imageLayer');
      }
    }

    function activateGeolocationSearch ()
    {
      document.forms['searchform_advanced'].elements['action'].value = 'base_search';
      hcms_hideFormLayer('searchreplaceLayer');
      hcms_switchFormLayer('mapLayer');
      initMap();
      hcms_hideFormLayer('idLayer');
    }

    function activateLastmodifiedSearch ()
    {
      // do not set due to search & replace:
      // document.forms['searchform_advanced'].elements['action'].value = 'base_search';
      hcms_switchFormLayer('dateLayer');
      hcms_hideFormLayer('idLayer');
      hcms_hideFormLayer('recipientLayer');
    }

    function activateIdSearch ()
    {
      if (document.getElementById('idLayer').style.display == 'none')
      {
        document.forms['searchform_advanced'].elements['action'].value = 'base_search';
        hcms_hideFormLayer('fulltextLayer',0);
        hcms_hideFormLayer('advancedLayer');
        hcms_hideFormLayer('searchreplaceLayer');
        hcms_hideFormLayer('keywordsLayer');
        hcms_hideFormLayer('filetypeLayer');
        hcms_hideFormLayer('imageLayer');
        hcms_hideFormLayer('mapLayer');
        hcms_hideFormLayer('dateLayer');
        hcms_showFormLayer('idLayer',0);
        hcms_hideFormLayer('recipientLayer');
      }
      else
      {
        hcms_hideFormLayer('idLayer');
      }
    }

    function activateRecipientSearch ()
    {
      if (document.getElementById('recipientLayer').style.display == 'none')
      {
        document.forms['searchform_advanced'].elements['action'].value = 'recipient';
        hcms_hideFormLayer('searchreplaceLayer');
        hcms_hideFormLayer('dateLayer');
        hcms_hideFormLayer('idLayer');
        hcms_showFormLayer('recipientLayer',0);
      }
      else
      {
        hcms_hideFormLayer('recipientLayer');
      }
    }

    function activateSaveSearch ()
    {
      hcms_switchFormLayer('saveLayer');
      hcms_hideFormLayer('searchreplaceLayer');
    }

    function selectedSavedSearch ()
    {
      if (document.getElementById('search_execute').value != "")
      {
        document.forms['searchform_advanced'].elements['action'].value = 'base_search';
        hcms_hideFormLayer('fulltextLayer');
        hcms_hideFormLayer('advancedLayer');
        hcms_hideFormLayer('searchreplaceLayer');
        hcms_hideFormLayer('keywordsLayer');
        hcms_hideFormLayer('filetypeLayer');
        hcms_hideFormLayer('imageLayer');
        hcms_hideFormLayer('mapLayer');
        hcms_hideFormLayer('dateLayer');
        hcms_hideFormLayer('idLayer');
        hcms_hideFormLayer('recipientLayer');
      }
    }

    function setSearchLocation (location, name)
    {
      // search form
      var form = document.forms['searchform_advanced'];

      if (form && location != '' && name != '')
      {
        // show location and hide publication select
        hcms_hideFormLayer('searchPublicationLayer');
        hcms_showFormLayer('searchLocationLayer',0);

        // set search_dir
        form.elements['search_dir'].value = location;
        form.elements['search_locationname'].value = name;

        // enable search and replace
        hcms_showFormLayer('searchreplaceFeatureLayer',0);

        // show search form
        showSearch();
      }
    }

    function deleteSearchLocation ()
    {
      // search form
      var form = document.forms['searchform_advanced'];

      if (form)
      {
        // show publication select and hide location
        hcms_hideFormLayer('searchLocationLayer');
        hcms_showFormLayer('searchPublicationLayer',0);

        // set search_dir
        form.elements['search_dir'].value = '';
        form.elements['search_locationname'].value = '';

        // disable search and replace
        hcms_hideFormLayer('searchreplaceFeatureLayer');
      }
    }

    function initializeSearch ()
    {
      hcms_hideFormLayer('advancedLayer');
      hcms_hideFormLayer('searchreplaceLayer');
      hcms_hideFormLayer('keywordsLayer');
      hcms_hideFormLayer('filetypeLayer');
      hcms_hideFormLayer('imageLayer');
      hcms_hideFormLayer('mapLayer');
      hcms_hideFormLayer('dateLayer');
      hcms_hideFormLayer('idLayer');
      hcms_hideFormLayer('recipientLayer');
      activateFulltextSearch();
    }

    function startSearch (type)
    {
      // iframe for search result
      var targetframe = parent.frames['workplFrame'].frames['mainFrame'];

      // search form
      var form = document.forms['searchform_advanced'];

      // verify if at least one search tab is open
      var opened = false;

      if (document.getElementById('fulltextLayer').style.display != 'none') opened = true;
      else if (document.getElementById('advancedLayer').style.display != 'none') opened = true;
      else if (document.getElementById('searchreplaceLayer').style.display != 'none') opened = true;
      else if (document.getElementById('keywordsLayer').style.display != 'none') opened = true;
      else if (document.getElementById('filetypeLayer').style.display != 'none') opened = true;
      else if (document.getElementById('imageLayer').style.display != 'none') opened = true;
      else if (document.getElementById('mapLayer').style.display != 'none') opened = true;
      else if (document.getElementById('dateLayer').style.display != 'none') opened = true;
      else if (document.getElementById('idLayer').style.display != 'none') opened = true;
      else if (document.getElementById('recipientLayer').style.display != 'none') opened = true;
      else if (document.getElementById('saveLayer').style.display != 'none') opened = true;

      // check if saved search has been selected (if no other search tab has been opened)
      if (form && opened == false && document.getElementById('saveLayer').style.display != 'none' && document.getElementById('search_execute') && document.getElementById('search_execute').value == "")
      {
        return false;
      }

      // if the saved search tab is open
      if (document.getElementById('saveLayer').style.display != 'none') opened = true;

      // if at least one search tab is open
      if (form && opened)
      {
        // if no saved search has been selected
        if (document.getElementById('saveLayer').style.display == 'none' || !document.getElementById('search_execute') || document.getElementById('search_execute').value == "")
        {
          // full text search
          if (document.getElementById('fulltextLayer').style.display != 'none' && document.getElementById('search_expression').value.trim().length < 3)
          {
            alert (hcms_entity_decode("<?php echo getescapedtext ($hcms_lang['please-insert-a-search-expression'][$lang]); ?>"));
            document.getElementById('search_expression').focus();
            return false;
          }

          // search and replace
          if (document.getElementById('searchreplaceLayer').style.display != 'none' && document.getElementById('find_expression').value.trim() == "")
          {
            alert (hcms_entity_decode("<?php echo getescapedtext ($hcms_lang['please-insert-a-search-expression'][$lang]); ?>"));
            document.getElementById('find_expression').focus();
            return false;
          }

          // set search_dir if it has not been set already
          if (form.elements['search_dir'].value == "")
          {
            // set search dir if a template has been selected
            if (document.getElementById('advancedLayer').style.display != 'none')
            {
              var selectbox = form.elements['template'];
              var template = form.elements['template'].options[selectbox.selectedIndex].value;

              if (template != "")
              {
                var parts = template.split("/");
                var domain = "%comp%";

                if (template.indexOf(".page.tpl") > 0) domain = "%page%";

                if (parts[0] != "") form.elements['search_dir'].value = domain + "/" + parts[0] + "/";
              }
            }
            // delete search_dir
            else
            {
              form.elements['search_dir'].value = "";
            }
          }

          // check if at least one keyword has been checked
          var keywordsLayer = document.getElementById('keywordsLayer');
          var keywordChecked = false;

          if (keywordsLayer && keywordsLayer.style.display != "none")
          {
            var unchecked = false;
            var childs = keywordsLayer.getElementsByTagName('INPUT');

            for (var i=0; i<childs.length; i++)
            {
              // found checked element
              if (childs[i].checked == true)
              {
                keywordChecked = true;
                break;
              }
            }

            if (keywordChecked == false)
            {
              return false;
            }
          }

          // check if file-types have been checked
          var filetypeLayer = document.getElementById('filetypeLayer');

          if (filetypeLayer && filetypeLayer.style.display != "none")
          {
            var filetypechecked = false;
            var childs = filetypeLayer.getElementsByTagName('INPUT');

            for (var i=0; i<childs.length; i++)
            {
              // found checked element
              if (childs[i].checked == true)
              {
                filetypechecked = true;
                break;
              }
            }

            if (filetypechecked == false)
            {
              alert (hcms_entity_decode("<?php echo getescapedtext ($hcms_lang['file-type'][$lang].": ".$hcms_lang['please-select-an-option'][$lang]); ?>"));
              return false;
            }
          }
        }

        // if the frameset has not been loaded
        if (parent.frames['workplFrame'].location.href.indexOf('frameset_objectlist.php') < 0)
        {
          // load frameset
          parent.frames['workplFrame'].location.href = '<?php echo $mgmt_config['url_path_cms']; ?>frameset_objectlist.php';
        }

        // reassign
        if (!targetframe && parent.frames['workplFrame'].frames['mainFrame'])
        {
          targetframe = parent.frames['workplFrame'].frames['mainFrame'];
        }

        // if frameset has been loaded
        if (targetframe && targetframe.location.href != "")
        {
          // workplace frame load screen
          if (parent.frames['workplFrame'].document.getElementById('hcmsLoadScreen')) parent.frames['workplFrame'].document.getElementById('hcmsLoadScreen').style.display='inline';

          // submit form (use delay due to issues with frames)
          window.setTimeout(function(){form.submit();}, 100);

          // reload page for a new saved search
          if (document.forms['searchform_advanced'].elements['search_save'].checked == true)
          {
            window.setTimeout(function(){location.href='explorer.php?view=search';}, 1000);
          }

          return true;
        }
        // wait and restart search
        else
        {
          window.setTimeout(startSearch, 1000);
        }
      }
      else return false;
    }

    // onload
    $(document).ready(function ()
    {
      <?php
      // display search form
      if ($explorer_view == "search") echo "showSearch();";
      // display navigation
      else echo "showNav();";
      ?>

      // search history
      <?php
      $keywords = getsearchhistory ($user, true);
      ?>
      var available_expressions = [<?php if (is_array ($keywords)) echo implode (",\n", $keywords); ?>];

      $("#search_expression").autocomplete({
        source: available_expressions
      });

      // user
      <?php
      $user_option = array();

      if (!empty ($siteaccess))
      {
        $user_array = getuserinformation ();      

        if (is_array ($user_array) && sizeof ($user_array) > 0)
        {
          foreach ($siteaccess as $site_name => $displayname)
          {
            if (!empty ($site_name) && !empty ($user_array[$site_name]) && is_array ($user_array[$site_name]))
            {
              foreach ($user_array[$site_name] as $login => $value)
              {           
                $text = $login;

                if (trim ($value['realname']) != "" && trim ($value['email']) != "") $text .= " (".trim ($value['realname']).", ".trim ($value['email']).")";
                elseif (trim ($value['realname']) != "") $text .= " (".trim ($value['realname']).")";
                elseif (trim ($value['email']) != "") $text .= " (".trim ($value['email']).")";

                $text = "'".str_replace (array("\\", "'"), array("", "\\'"), trim ($text))."'";                
                $user_option[$login] = $text;
              }
            }
          }

          ksort ($user_option, SORT_STRING | SORT_FLAG_CASE);
        }
      }
      ?>
      var user_options = [<?php if (is_array ($user_option)) echo implode (",\n", $user_option); ?>];

      // sender
      $("#from_user").autocomplete({
        source: user_options
      });

      // recipient
      $("#to_user").autocomplete({
        source: user_options
      });

      // initialize search form
      initializeSearch();
    });
    </script>
  </head>

  <body class="hcmsWorkplaceExplorer">

  <!-- load screen --> 
  <div id="hcmsLoadScreen" class="hcmsLoadScreen" style="display:inline;"></div>

  <!-- Saves the token for the context menu -->
  <span id="context_token" style="display:none;"><?php echo $token_new; ?></span>

  <!-- Memory (for drop event) -->
  <form name="memory" action="" method="post" target="popup_explorer" style="position:absolute; width:0; height:0; z-index:0; left:0; top:0; visibility:hidden;">
    <input type="hidden" name="action" value="" />
    <input type="hidden" name="force" value="" />
    <input type="hidden" name="contexttype" value="" />
    <input type="hidden" name="site" value="" />
    <input type="hidden" name="cat" value="" />
    <input type="hidden" name="location" value="" />
    <input type="hidden" name="targetlocation" value="" />
    <input type="hidden" name="page" value="" />
    <input type="hidden" name="pagename" value="" />
    <input type="hidden" name="filetype" value="" />
    <input type="hidden" name="media" value="" />
    <input type="hidden" name="folder" value="" /> 
    <input type="hidden" name="multiobject" value="" />
    <input type="hidden" name="token" value="<?php echo $token; ?>" />
    <input type="hidden" name="convert_type" value="" />
    <input type="hidden" name="convert_cfg" value="" />
  </form>

  <!-- Context menu -->
  <div id="contextLayer" style="position:absolute; min-width:160px; max-width:260px; height:128px; z-index:10; left:20px; top:20px; visibility:hidden;"> 
    <form name="contextmenu_object" action="" method="post" target="popup_explorer">
      <input type="hidden" name="contextmenustatus" value="" />
      <input type="hidden" name="contextmenulocked" value="false" />
      <input type="hidden" name="action" value="" />
      <input type="hidden" name="force" value="" />
      <input type="hidden" name="contexttype" value="" />
      <input type="hidden" name="xpos" value="" />
      <input type="hidden" name="ypos" value="" />
      <input type="hidden" name="site" value="" />
      <input type="hidden" name="cat" value="" />
      <input type="hidden" name="location" value="" />
      <input type="hidden" name="targetlocation" value="" />
      <input type="hidden" name="page" value="" />
      <input type="hidden" name="pagename" value="" />
      <input type="hidden" name="filetype" value="" />
      <input type="hidden" name="media" value="" />
      <input type="hidden" name="folder" value="" /> 
      <input type="hidden" name="folder_id" value="" /> 
      <input type="hidden" name="multiobject" value="" />
      <input type="hidden" name="token" value="" />
      <input type="hidden" name="convert_type" value="" />
      <input type="hidden" name="convert_cfg" value="" />

      <div class="hcmsContextMenu">
        <table class="hcmsTableStandard" style="width:100%;">
          <tr>
            <td>
              <a href="javascript:void(0);" onclick="parent.location='userlogout.php';"><img src="<?php echo getthemelocation(); ?>img/button_logout.png"  class="hcmsIconOn hcmsIconList" />&nbsp;<?php echo getescapedtext ($hcms_lang['logout'][$lang]); ?></a>
              <hr/>
              <a href="javascript:void(0);" id="href_cmsview" onclick="if (document.forms['contextmenu_object'].elements['contexttype'].value != 'none') hcms_createContextmenuItem ('cmsview');"><img src="<?php echo getthemelocation(); ?>img/button_edit.png" id="img_cmsview" class="hcmsIconOff hcmsIconList" />&nbsp;<?php echo getescapedtext ($hcms_lang['edit'][$lang]); ?></a><br />
              <a href="javascript:void(0);" id="href_notify" onclick="if (document.forms['contextmenu_object'].elements['contexttype'].value != 'none') hcms_createContextmenuItem ('notify');"><img src="<?php echo getthemelocation(); ?>img/button_notify.png" id="img_notify" class="hcmsIconOff hcmsIconList" />&nbsp;<?php echo getescapedtext ($hcms_lang['notify-me'][$lang]); ?></a><br />   
              <hr/>
              <a href="javascript:void(0);" onclick="if (document.forms['contextmenu_object'].elements['contexttype'].value != 'none') hcms_createContextmenuItem ('publish');"><img id="img_publish" src="<?php echo getthemelocation(); ?>img/button_file_publish.png" class="hcmsIconOff hcmsIconList" />&nbsp;<?php echo getescapedtext ($hcms_lang['publish'][$lang]); ?></a><br />  
              <a href="javascript:void(0);" onclick="if (document.forms['contextmenu_object'].elements['contexttype'].value != 'none') hcms_createContextmenuItem ('unpublish');"><img id="img_unpublish" src="<?php echo getthemelocation(); ?>img/button_file_unpublish.png"  class="hcmsIconOff hcmsIconList" />&nbsp;<?php echo getescapedtext ($hcms_lang['unpublish'][$lang]); ?></a><br />        
              <hr/>
              <a href="javascript:void(0);" onclick="document.location='explorer.php?refresh=1';"><img src="<?php echo getthemelocation(); ?>img/button_view_refresh.png"  class="hcmsIconOn hcmsIconList" />&nbsp;<?php echo getescapedtext ($hcms_lang['refresh'][$lang]); ?></a>
            </td>
          </tr>    
        </table>
      </div>
    </form>
  </div>

    <!-- navigator -->
    <div id="menu" style="position:absolute; top:4px; left:0px; min-width:200px; display:none;">
      <ul id="menupointlist">
        <?php echo $maintree.$tree; ?>
      </ul>
    </div>

    <!-- search form -->
    <div id="search" style="position:absolute; top:8px; left:4px; right:4px; text-align:top; min-width:420px; display:none;">
      <form name="searchform_advanced" method="post" action="search_objectlist.php" target="mainFrame" autocomplete="off">
        <input type="hidden" name="action" value="base_search" />
        <input type="hidden" name="search_dir" value="" />

        <div style="display:block; margin-bottom:3px;">
          <div id="searchLocationLayer" style="padding-bottom:3px; display:none;">
            <img src="<?php echo getthemelocation(); ?>img/button_filter.png" class="hcmsIconList" style="vertical-align:middle;" /><label for="search_locationname" class="hcmsHeadline"><?php echo getescapedtext ($hcms_lang['search-in-folder'][$lang]); ?></label><br />
            <input type="text" id="search_locationname" name="search_locationname" value="" style="width:<?php echo ($width_searchfield - 32); ?>px;" readonly="readonly" />
            <img onclick="deleteSearchLocation();" class="hcmsButtonTiny hcmsButtonSizeSquare" src="<?php echo getthemelocation(); ?>img/button_delete.png" title="<?php echo getescapedtext ($hcms_lang['delete'][$lang]); ?>" alt="<?php echo getescapedtext ($hcms_lang['delete'][$lang]); ?>" />
          </div>
          <div id="searchPublicationLayer" style="padding-bottom:3px;">
            <img src="<?php echo getthemelocation(); ?>img/button_filter.png" class="hcmsIconList" style="vertical-align:middle;" /><label for="publication" class="hcmsHeadline"><?php echo getescapedtext ($hcms_lang['publication'][$lang]); ?></label><br />
            <select id="publication" name="site" style="width:<?php echo $width_searchfield; ?>px;" onchange="switchTemplates();">
              <option value=""><?php echo getescapedtext ($hcms_lang['select-all'][$lang]); ?></option>
              <?php
              if (!empty ($siteaccess) && is_array ($siteaccess))
              {
                $template_array = array();

                foreach ($siteaccess as $site => $displayname)
                {
                  if (valid_publicationname ($site)) echo "
                <option value=\"".$site."\">".$displayname."</option>";
                }
              }
              ?>
            </select>
          </div>
        </div>
        <hr />

        <!-- fulltext search -->
        <div style="display:block; margin-bottom:3px;">
          <span class="hcmsHeadline"><?php echo getescapedtext ($hcms_lang['general-search'][$lang]); ?></span> <img src="<?php if ($mgmt_config['search_query_match'] == "match") echo getthemelocation(); ?>img/info.png" class="hcmsIconList" style="cursor:pointer;" title='<?php echo getescapedtext ($hcms_lang['search-wildcard-plus'][$lang]." \r\n".$hcms_lang['search-wildcard-minus'][$lang]." \r\n".$hcms_lang['search-wildcard-none'][$lang]." \r\n".$hcms_lang['search-wildcard-asterisk'][$lang]." \r\n".$hcms_lang['search-wildcard-doublequote'][$lang]); ?>' />
          <img onclick="activateFulltextSearch()" class="hcmsButtonTiny" src="<?php echo getthemelocation(); ?>img/button_plusminus.png" style="float:right; width:31px; height:16px;" alt="+/-" title="+/-" />
        </div>

        <div id="fulltextLayer" style="display:none; clear:right;"> 
          <div style="padding-bottom:3px;">
            <label for="search_expression"><?php echo getescapedtext ($hcms_lang['search-expression'][$lang]); ?></label><br />
            <input type="search" id="search_expression" name="search_expression" onkeydown="if (hcms_enterKeyPressed(event)) startSearch('post');" style="width:<?php echo $width_searchfield; ?>px; padding-right:30px;" maxlength="2000" autocomplete="off" />
            <img src="<?php echo getthemelocation(); ?>img/button_search_dark.png" style="cursor:pointer; width:22px; height:22px; margin-left:-30px;" onclick="startSearch('post');" title="<?php echo getescapedtext ($hcms_lang['search'][$lang]); ?>" alt="<?php echo getescapedtext ($hcms_lang['search'][$lang]); ?>" />
          </div>
          <div style="padding-bottom:3px;">
            <?php echo getescapedtext ($hcms_lang['search-restriction'][$lang]); ?><br/>
            <label><input type="checkbox" name="search_cat" id="search_cat_text" value="text" onclick="if (this.checked) document.getElementById('search_cat_file').checked=false; else document.getElementById('search_cat_file').checked=true;" checked /> <?php echo getescapedtext ($hcms_lang['text'][$lang]); ?></label><br />
            <label><input type="checkbox" name="search_cat" id="search_cat_file" value="file" onclick="if (this.checked) document.getElementById('search_cat_text').checked=false; else document.getElementById('search_cat_text').checked=true;" /> <?php echo getescapedtext ($hcms_lang['location'][$lang]."/".$hcms_lang['object'][$lang]." ".$hcms_lang['name'][$lang]); ?></label><br/>
          </div> 
        </div>
        <hr />

        <!-- advanced/detailed search -->
        <div style="display:block; margin-bottom:3px;">
          <span class="hcmsHeadline"><?php echo getescapedtext ($hcms_lang['advanced-search'][$lang]); ?></span>
          <img onclick="activateAdvancedSearch()" class="hcmsButtonTiny" src="<?php echo getthemelocation(); ?>img/button_plusminus.png" style="float:right; width:31px; height:16px;" alt="+/-" title="+/-" />
        </div>

        <div id="advancedLayer" style="display:none; clear:right;">
          <label for="template"><?php echo getescapedtext ($hcms_lang['based-on-template'][$lang]); ?></label><br />
          <select id="template" name="template" style="width:<?php echo $width_searchfield; ?>px;" onChange="loadForm();">
            <option value=""><?php echo getescapedtext ($hcms_lang['select'][$lang]); ?></option>
          <?php
          if (!empty ($siteaccess) && is_array ($siteaccess))
          {
            $template_array = array();

            foreach ($siteaccess as $site => $displayname)
            {
              $site_array = array();

              // load publication inheritance setting
              if (!empty ($mgmt_config[$site]['inherit_tpl']))
              {
                $inherit_db = inherit_db_read ();
                $site_array = inherit_db_getparent ($inherit_db, $site);

                // add own publication
                $site_array[] = $site;
              }
              else $site_array[] = $site;

              foreach ($site_array as $site_source)
              {
                if (is_dir ($mgmt_config['abs_path_template'].$site_source."/"))
                {
                  $dir_template = scandir ($mgmt_config['abs_path_template'].$site_source."/");

                  if ($dir_template)
                  {
                    foreach ($dir_template as $entry)
                    {
                      if ($entry != "." && $entry != ".." && !is_dir ($entry) && !preg_match ("/.inc.tpl/", $entry) && !preg_match ("/.tpl.v_/", $entry))
                      {
                        $template_array[] = $site_source."/".$entry;                
                      }
                    }
                  }
                }
              }
            }

            if (is_array ($template_array) && sizeof ($template_array) > 0)
            {
              // remove double entries (double entries due to parent publications won't be listed)
              $template_array = array_unique ($template_array);
              natcasesort ($template_array);
              reset ($template_array);
              
              foreach ($template_array as $value)
              {
                if (trim ($value) != "")
                {
                  $tpl_name = "";

                  if (strpos ($value, ".page.tpl") > 0) $tpl_name = substr ($value, 0, strpos ($value, ".page.tpl"))." (".getescapedtext ($hcms_lang['page'][$lang]).")";
                  elseif (strpos ($value, ".comp.tpl") > 0) $tpl_name = substr ($value, 0, strpos ($value, ".comp.tpl"))." (".getescapedtext ($hcms_lang['component'][$lang]).")";
                  elseif (strpos ($value, ".meta.tpl") > 0) $tpl_name = substr ($value, 0, strpos ($value, ".meta.tpl"))." (".getescapedtext ($hcms_lang['meta-data'][$lang]).")";

                  if ($tpl_name != "")
                  {
                    $tpl_name = str_replace ("/", " &gt; ", $tpl_name);

                    if (!empty ($tpl_name)) echo "<option value=\"".$value."\">".$tpl_name."</option>\n";
                  }
                }
              }
            }
          }
          ?>
          </select><br />
          <iframe id="contentFrame" name="contentFrame" width="0" height="0" frameborder="0"  style="width:0; height:0; frameborder:0;"></iframe> 
          <div class="hcmsWorkplaceObjectlist" style="box-sizing:border-box; border:1px solid #000000; width:<?php echo $width_searchfield; ?>px; height:420px; padding:2px; overflow:auto;">
            <div id="contentLayer"></div>
          </div>
          <div style="margin-top:5px;">
            <label for="search_operator"><?php echo getescapedtext ($hcms_lang['link-fields-with'][$lang]); ?></label> &nbsp;
            <label><input name="search_operator" id="search_operator_and" type="checkbox" value="AND" onclick="if (this.checked) document.getElementById('search_operator_or').checked = false;" <?php if (empty ($mgmt_config['search_operator']) || (!empty ($mgmt_config['search_operator']) && strtoupper ($mgmt_config['search_operator']) == "AND")) echo "checked"; ?> /> AND</label> &nbsp;
            <label><input name="search_operator" id="search_operator_or" type="checkbox" value="OR" onclick="if (this.checked) document.getElementById('search_operator_and').checked = false;" <?php if (!empty ($mgmt_config['search_operator']) && strtoupper ($mgmt_config['search_operator']) == "OR") echo "checked"; ?> /> OR</label>
          </div>   
        </div>
        <hr />

        <!-- search and replace (location must be provided) -->
        <div id="searchreplaceFeatureLayer" style="display:none;">
          <div style="display:inline; margin-bottom:3px;">
            <span class="hcmsHeadline"><?php echo getescapedtext ($hcms_lang['search-and-replace'][$lang]); ?></span> <img src="<?php echo getthemelocation(); ?>img/info.png" class="hcmsIconList" style="cursor:pointer;" title="<?php echo getescapedtext ($hcms_lang['the-replacement-is-case-sensitive'][$lang]); ?>" />
            <img onclick="activateSearchReplace()" class="hcmsButtonTiny" src="<?php echo getthemelocation(); ?>img/button_plusminus.png" style="float:right; width:31px; height:16px;" alt="+/-" title="+/-" />
          </div>

          <div id="searchreplaceLayer" style="display:none; clear:right;">
            <div style="padding-bottom:3px;">
              <label for="find_expression"><?php echo getescapedtext ($hcms_lang['search-expression'][$lang]); ?></label><br />
              <input type="text" id="find_expression" name="find_expression" style="width:<?php echo $width_searchfield; ?>px;" maxlength="2000" />
            </div>
            <div style="padding-bottom:3px;">
              <label for="replace_expression"><?php echo getescapedtext ($hcms_lang['replace-with'][$lang]); ?></label><br />
              <input type="text" id="replace_expression" name="replace_expression" style="width:<?php echo $width_searchfield; ?>px;" maxlength="2000" />
            </div>
          </div>
          <hr />
        </div>

        <!-- keyword search -->
        <div style="display:block; margin-bottom:3px;">
          <span class="hcmsHeadline"><?php echo getescapedtext ($hcms_lang['keywords'][$lang]); ?></span>
          <img onclick="activateKeywordSearch()" class="hcmsButtonTiny" src="<?php echo getthemelocation(); ?>img/button_plusminus.png" style="float:right; width:31px; height:16px;" alt="+/-" title="+/-" />
        </div>

        <div id="keywordsLayer" style="display:none; clear:right;">
          <iframe id="keywordsFrame" name="keywordsFrame" width="0" height="0" frameborder="0" style="width:0; height:0; frameborder:0;"></iframe> 
          <div id="keywordsTarget" style="width:100%; min-height:64px; max-height:500px; overflow:auto; background:url('<?php echo getthemelocation(); ?>/img/loading.gif') no-repeat center center;">
          </div>
        </div>
        <hr />

        <!-- filetype search -->
        <div style="display:block; margin-bottom:3px;">
          <span class="hcmsHeadline"><?php echo getescapedtext ($hcms_lang['file-type'][$lang]); ?></span>
          <img onclick="activateFiletypeSearch()" class="hcmsButtonTiny" src="<?php echo getthemelocation(); ?>img/button_plusminus.png" style="float:right; width:31px; height:16px;" alt="+/-" title="+/-" />
        </div>

        <div id="filetypeLayer" style="display:none; clear:right;">
          <div style="padding-bottom:3px;">
            <label><input type="checkbox" name="search_format[]" value="page" />&nbsp;<?php echo getescapedtext ($hcms_lang['page'][$lang]); ?></label><br />
            <label><input type="checkbox" name="search_format[]" value="comp" />&nbsp;<?php echo getescapedtext ($hcms_lang['component'][$lang]); ?></label><br />
            <label><input type="checkbox" name="search_format[]" value="image" />&nbsp;<?php echo getescapedtext ($hcms_lang['image'][$lang]); ?></label><br />
            <label><input type="checkbox" name="search_format[]" value="document" />&nbsp;<?php echo getescapedtext ($hcms_lang['document'][$lang]); ?></label><br />
            <label><input type="checkbox" name="search_format[]" value="video" />&nbsp;<?php echo getescapedtext ($hcms_lang['video'][$lang]); ?></label><br />
            <label><input type="checkbox" name="search_format[]" value="audio" />&nbsp;<?php echo getescapedtext ($hcms_lang['audio'][$lang]); ?></label><br />
            <label><input type="checkbox" name="search_format[]" value="folder" />&nbsp;<?php echo getescapedtext ($hcms_lang['folder'][$lang]); ?></label><br />
          </div>
        </div>
        <hr />

        <!-- media search -->
        <div style="display:block; margin-bottom:3px;">
          <span class="hcmsHeadline"><?php echo getescapedtext ($hcms_lang['media'][$lang]); ?></span>
          <img onclick="activateImageSearch()" class="hcmsButtonTiny" src="<?php echo getthemelocation(); ?>img/button_plusminus.png" style="float:right; width:31px; height:16px;" alt="+/-" title="+/-" />
        </div>

        <div id="imageLayer" style="display:none; clear:right;">
          <div style="padding-bottom:3px;">
            <?php echo getescapedtext ($hcms_lang['file-size'][$lang]); ?><br />
            <select name="search_filesize_operator">
              <option>&gt;=</option>
              <option>&gt;</option>
              <option>&lt;=</option>
              <option>&lt;</option>
            </select>
            <input type="number" name="search_filesize" style="width:70px;" maxlength="10" min="1" max="9999999999" /> KB
          </div>
          <div style="padding-bottom:3px;">
            <label for="search_imagesize"><?php echo getescapedtext ($hcms_lang['media-size'][$lang]); ?></label><br />
            <select id="search_imagesize" name="search_imagesize" style="width:<?php echo $width_searchfield; ?>px;" onchange="if (this.options[this.selectedIndex].value=='exact') document.getElementById('searchfield_imagesize').style.display='block'; else document.getElementById('searchfield_imagesize').style.display='none';">
              <option value="" selected="selected"><?php echo getescapedtext ($hcms_lang['all'][$lang]); ?></option>
              <option value="1024-9000000"><?php echo getescapedtext ($hcms_lang['big-1024px'][$lang]); ?></option>
              <option value="640-1024"><?php echo getescapedtext ($hcms_lang['medium-640-1024px'][$lang]); ?></option>
              <option value="0-640"><?php echo getescapedtext ($hcms_lang['small'][$lang]); ?></option>
              <option value="exact"><?php echo getescapedtext ($hcms_lang['exact-w-x-h'][$lang]); ?></option>
            </select>
            <div id="searchfield_imagesize" style="display:none; margin:3px 0px 0px 0px;">
              <input type="text" name="search_imagewidth" style="width:40px;" maxlength="8" /> x 
              <input type="text" name="search_imageheight" style="width:40px;" maxlength="8" /> px
            </div>
          </div>
          <div style="padding-bottom:3px;">
            <label for="search_imagetype"><?php echo getescapedtext ($hcms_lang['image-type'][$lang]); ?></label><br />
            <select id="search_imagetype" name="search_imagetype" style="width:<?php echo $width_searchfield; ?>px;">
              <option value="" selected="selected"><?php echo getescapedtext ($hcms_lang['all'][$lang]); ?></option>
              <option value="landscape"><?php echo getescapedtext ($hcms_lang['landscape'][$lang]); ?></option>
              <option value="portrait"><?php echo getescapedtext ($hcms_lang['portrait'][$lang]); ?></option>
              <option value="square"><?php echo getescapedtext ($hcms_lang['square'][$lang]); ?></option>
            </select>
          </div>
          <div style="padding-bottom:3px;">
            <label><?php echo getescapedtext ($hcms_lang['image-color'][$lang]); ?></label><br />
            <div style="display:block;">
              <div style="width:<?php echo $width_searchfield; ?>px; margin:1px; padding:0; float:left;"><label><div style="float:left;"><input id="unsetcolors" style="margin:2px; padding:0;" type="checkbox" name="search_imagecolor[]" value="" checked="checked" onclick="unsetColors()" /></div>&nbsp;<?php echo getescapedtext ($hcms_lang['all'][$lang]); ?></label></div>
              <div style="width:105px; margin:1px; padding:0; float:left;"><label><div style="border:1px solid #666666; background-color:#000000; padding:2px; float:left;"><input class="hcmsColorKey" style="margin:2px; padding:0;" type="checkbox" name="search_imagecolor[]" value="K" onclick="setColors()" /></div>&nbsp;<?php echo getescapedtext ($hcms_lang['black'][$lang]); ?></label></div>
              <div style="width:105px; margin:1px; padding:0; float:left;"><label><div style="border:1px solid #666666; background-color:#FFFFFF; padding:2px; float:left;"><input class="hcmsColorKey" style="margin:2px; padding:0;" type="checkbox" name="search_imagecolor[]" value="W" onclick="setColors()" /></div>&nbsp;<?php echo getescapedtext ($hcms_lang['white'][$lang]); ?></label></div>
              <div style="width:105px; margin:1px; padding:0; float:left;"><label><div style="border:1px solid #666666; background-color:#808080; padding:2px; float:left;"><input class="hcmsColorKey" style="margin:2px; padding:0;" type="checkbox" name="search_imagecolor[]" value="E" onclick="setColors()" /></div>&nbsp;<?php echo getescapedtext ($hcms_lang['grey'][$lang]); ?></label></div>
              <div style="width:105px; margin:1px; padding:0; float:left;"><label><div style="border:1px solid #666666; background-color:#FF0000; padding:2px; float:left;"><input class="hcmsColorKey" style="margin:2px; padding:0;" type="checkbox" name="search_imagecolor[]" value="R" onclick="setColors()" /></div>&nbsp;<?php echo getescapedtext ($hcms_lang['red'][$lang]); ?></label></div>
              <div style="width:105px; margin:1px; padding:0; float:left;"><label><div style="border:1px solid #666666; background-color:#00C000; padding:2px; float:left;"><input class="hcmsColorKey" style="margin:2px; padding:0;" type="checkbox" name="search_imagecolor[]" value="G" onclick="setColors()" /></div>&nbsp;<?php echo getescapedtext ($hcms_lang['green'][$lang]); ?></label></div>
              <div style="width:105px; margin:1px; padding:0; float:left;"><label><div style="border:1px solid #666666; background-color:#0000FF; padding:2px; float:left;"><input class="hcmsColorKey" style="margin:2px; padding:0;" type="checkbox" name="search_imagecolor[]" value="B" onclick="setColors()" /></div>&nbsp;<?php echo getescapedtext ($hcms_lang['blue'][$lang]); ?></label></div>
              <div style="width:105px; margin:1px; padding:0; float:left;"><label><div style="border:1px solid #666666; background-color:#00FFFF; padding:2px; float:left;"><input class="hcmsColorKey" style="margin:2px; padding:0;" type="checkbox" name="search_imagecolor[]" value="C" onclick="setColors()" /></div>&nbsp;<?php echo getescapedtext ($hcms_lang['cyan'][$lang]); ?></label></div>
              <div style="width:105px; margin:1px; padding:0; float:left;"><label><div style="border:1px solid #666666; background-color:#FF0090; padding:2px; float:left;"><input class="hcmsColorKey" style="margin:2px; padding:0;" type="checkbox" name="search_imagecolor[]" value="M" onclick="setColors()" /></div>&nbsp;<?php echo getescapedtext ($hcms_lang['magenta'][$lang]); ?></label></div>
              <div style="width:105px; margin:1px; padding:0; float:left;"><label><div style="border:1px solid #666666; background-color:#FFFF00; padding:2px; float:left;"><input class="hcmsColorKey" style="margin:2px; padding:0;" type="checkbox" name="search_imagecolor[]" value="Y" onclick="setColors()" /></div>&nbsp;<?php echo getescapedtext ($hcms_lang['yellow'][$lang]); ?></label></div>
              <div style="width:105px; margin:1px; padding:0; float:left;"><label><div style="border:1px solid #666666; background-color:#FF8A00; padding:2px; float:left;"><input class="hcmsColorKey" style="margin:2px; padding:0;" type="checkbox" name="search_imagecolor[]" value="O" onclick="setColors()" /></div>&nbsp;<?php echo getescapedtext ($hcms_lang['orange'][$lang]); ?></label></div>
              <div style="width:105px; margin:1px; padding:0; float:left;"><label><div style="border:1px solid #666666; background-color:#FFCCDD; padding:2px; float:left;"><input class="hcmsColorKey" style="margin:2px; padding:0;" type="checkbox" name="search_imagecolor[]" value="P" onclick="setColors()" /></div>&nbsp;<?php echo getescapedtext ($hcms_lang['pink'][$lang]); ?></label></div>
              <div style="width:105px; margin:1px; padding:0; float:left;"><label><div style="border:1px solid #666666; background-color:#A66500; padding:2px; float:left;"><input class="hcmsColorKey" style="margin:2px; padding:0;" type="checkbox" name="search_imagecolor[]" value="N" onclick="setColors()" /></div>&nbsp;<?php echo getescapedtext ($hcms_lang['brown'][$lang]); ?></label></div>
              <div style="clear:both;"></div>
            </div>
          </div>
        </div>
        <hr />

        <!-- geolocation search -->
        <?php if (!$is_mobile && !empty ($mgmt_config['googlemaps_appkey'])) { ?>
        <div style="display:block; margin-bottom:3px;">
          <span class="hcmsHeadline"><?php echo getescapedtext ($hcms_lang['geo-location'][$lang]); ?></span>
          <img onclick="activateGeolocationSearch()" class="hcmsButtonTiny" src="<?php echo getthemelocation(); ?>img/button_plusminus.png" style="float:right; width:31px; height:16px;" alt="+/-" title="+/-" />
        </div>

        <div id="mapLayer" style="display:none; clear:right;">
          <div style="position:relative; left:<?php echo ($width_searchfield - 42); ?>px; top:15px; width:22px; height:22px; z-index:1000;">
            <img src="<?php echo getthemelocation(); ?>img/info.png" title="<?php echo getescapedtext ($hcms_lang['help'][$lang]); ?>" onmouseover="hcms_showFormLayer('helpmapLayer');" onmouseout="hcms_hideFormLayer('helpmapLayer');" class="hcmsButtonSizeSquare" style="cursor:pointer;" />
            <div id="helpmapLayer" style="display:none; position:absolute; top:20px; right:10px;"><img src="<?php echo getthemelocation(); ?>img/info-right-click-drag.png" /></div>
          </div>
          <div id="map" style="width:<?php echo ($width_searchfield - 4); ?>px; height:<?php echo ($width_searchfield - 160); ?>px; margin-top:-15px; margin-bottom:3px; border:1px solid grey;"></div>
          <label for="geo_border_sw"><?php echo getescapedtext ($hcms_lang['sw-coordinates'][$lang]); ?></label><br />
          <input type="text" id="geo_border_sw" name="geo_border_sw" style="width:<?php echo $width_searchfield; ?>px;" maxlength="100" /><br />
          <label for="geo_border_ne"><?php echo getescapedtext ($hcms_lang['ne-coordinates'][$lang]); ?></label><br />
          <input type="text" id="geo_border_ne" name="geo_border_ne" style="width:<?php echo $width_searchfield; ?>px;" maxlength="100" /><br />
        </div>
        <hr />
        <?php } ?>

        <!-- last modified search -->
        <div style="display:block; margin-bottom:3px;">
          <span class="hcmsHeadline"><?php echo getescapedtext ($hcms_lang['last-modified'][$lang]); ?></span>
          <img onclick="activateLastmodifiedSearch()" class="hcmsButtonTiny" src="<?php echo getthemelocation(); ?>img/button_plusminus.png" style="float:right; width:31px; height:16px;" alt="+/-" title="+/-" />
        </div>

        <div id="dateLayer" style="display:none; clear:right;">        
          <table class="hcmsTableStandard">     
            <tr>
              <td> 
                <?php echo getescapedtext ($hcms_lang['from'][$lang]); ?>&nbsp;&nbsp;
              </td>
              <td>
                <input type="text" name="date_from" id="date_from" readonly="readonly" value="" style="width:92px;" /><img src="<?php echo getthemelocation(); ?>img/button_datepicker.png" onclick="show_cal(this, 'date_from', '%Y-%m-%d');" alt="<?php echo getescapedtext ($hcms_lang['select-date'][$lang]); ?>" title="<?php echo getescapedtext ($hcms_lang['select-date'][$lang]); ?>" class="hcmsButtonTiny hcmsButtonSizeSquare" />
              </td>
            </tr>
            <tr>
              <td>
              <?php echo getescapedtext ($hcms_lang['to'][$lang]); ?>&nbsp;&nbsp; 
              </td>
              <td>
                <input type="text" name="date_to" id="date_to" readonly="readonly" value="" style="width:92px;" /><img src="<?php echo getthemelocation(); ?>img/button_datepicker.png" onclick="show_cal(this, 'date_to', '%Y-%m-%d');" alt="<?php echo getescapedtext ($hcms_lang['select-date'][$lang]); ?>" title="<?php echo getescapedtext ($hcms_lang['select-date'][$lang]); ?>" class="hcmsButtonTiny hcmsButtonSizeSquare" />      
              </td>
            </tr>
          </table>          
        </div>
        <hr />

        <!-- ID based search -->
        <div style="display:block; margin-bottom:3px;">
          <span class="hcmsHeadline"><?php echo getescapedtext ($hcms_lang['object-id-link-id'][$lang]." ".$hcms_lang['and'][$lang]." ".$hcms_lang['container-id'][$lang]); ?></span>
          <img onclick="activateIdSearch()" class="hcmsButtonTiny" src="<?php echo getthemelocation(); ?>img/button_plusminus.png" style="float:right; width:31px; height:16px;" alt="+/-" title="+/-" />
        </div>

        <div id="idLayer" style="display:none; clear:right;">        
          <div style="padding-bottom:3px;">
            <label nowrap="object_id"><?php echo getescapedtext ($hcms_lang['object-id-link-id'][$lang]); ?></label><br />
            <input type="text" id="object_id" name="object_id" value="" style="width:<?php echo $width_searchfield; ?>px;" />
          </div>          
          <div style="padding-bottom:3px;">    
            <label nowrap="container_id"><?php echo getescapedtext ($hcms_lang['container-id'][$lang]); ?></label><br />
            <input type="text" id="container_id" name="container_id" value="" style="width:<?php echo $width_searchfield; ?>px;" />
          </div>          
        </div>
        <hr />

        <!-- recipient search -->
        <div style="display:block; margin-bottom:3px;">
          <span class="hcmsHeadline"><?php echo getescapedtext ($hcms_lang['recipient'][$lang]); ?></span>
          <img onclick="activateRecipientSearch()" class="hcmsButtonTiny" src="<?php echo getthemelocation(); ?>img/button_plusminus.png" style="float:right; width:31px; height:16px;" alt="+/-" title="+/-" />
        </div>

        <div id="recipientLayer" style="display:none; clear:right;">        
          <div style="padding-bottom:3px;">
            <label for="from_user"><?php echo getescapedtext ($hcms_lang['sender'][$lang]); ?></label><br />
            <input type="text" id="from_user" name="from_user" style="width:<?php echo $width_searchfield; ?>px;" maxlength="200" />
          </div>          
          <div style="padding-bottom:3px;">
            <label for="to_user"><?php echo getescapedtext ($hcms_lang['recipient'][$lang]); ?></label><br />
            <input type="text" id="to_user" name="to_user" style="width:<?php echo $width_searchfield; ?>px;" maxlength="200" />
          </div>          
          <table class="hcmsTableStandard" style="margin-top:4px;">     
            <tr>
              <td> 
                <?php echo getescapedtext ($hcms_lang['from'][$lang]); ?>&nbsp;&nbsp;
              </td>
              <td>
                <input type="text" name="date_from" id="date_sent_from" readonly="readonly" value="" style="width:92px;" /><img src="<?php echo getthemelocation(); ?>img/button_datepicker.png" onclick="show_cal(this, 'date_sent_from', '%Y-%m-%d');" alt="<?php echo getescapedtext ($hcms_lang['select-date'][$lang]); ?>" title="<?php echo getescapedtext ($hcms_lang['select-date'][$lang]); ?>" class="hcmsButtonTiny hcmsButtonSizeSquare" />
              </td>
            </tr>
            <tr>
              <td>
              <?php echo getescapedtext ($hcms_lang['to'][$lang]); ?>&nbsp;&nbsp; 
              </td>
              <td>
                <input type="text" name="date_to" id="date_sent_to" readonly="readonly" value="" style="width:92px;" /><img src="<?php echo getthemelocation(); ?>img/button_datepicker.png" onclick="show_cal(this, 'date_sent_to', '%Y-%m-%d');" alt="<?php echo getescapedtext ($hcms_lang['select-date'][$lang]); ?>" title="<?php echo getescapedtext ($hcms_lang['select-date'][$lang]); ?>" class="hcmsButtonTiny hcmsButtonSizeSquare" />      
              </td>
            </tr>
          </table>          
        </div>
        <hr />

        <!-- save search -->
        <div style="display:block; margin-bottom:3px;">
          <span class="hcmsHeadline"><?php echo getescapedtext ($hcms_lang['saved-searches'][$lang]); ?></span>
          <img onclick="activateSaveSearch()" class="hcmsButtonTiny" src="<?php echo getthemelocation(); ?>img/button_plusminus.png" style="float:right; width:31px; height:16px;" alt="+/-" title="+/-" />
        </div>
        <div id="saveLayer" style="display:none; clear:right;">
        
          <?php
          if (is_file ($mgmt_config['abs_path_data']."/log/".$user.".search.log"))
          {
            $searchlog_array = file ($mgmt_config['abs_path_data']."log/".$user.".search.log");

            if ($searchlog_array != false && sizeof ($searchlog_array) > 0)
            {
              echo "
          <div style=\"padding-bottom:3px;\">
            <select id=\"search_execute\" name=\"search_execute\" style=\"width:".($width_searchfield - 32)."px;\" onchange=\"selectedSavedSearch();\">
              <option value=\"\">".getescapedtext ($hcms_lang['select'][$lang])."</option>";
              
              foreach ($searchlog_array as $searchlog)
              {
                if (strpos ($searchlog, "|") > 0)
                {
                  // update to version 8.0.2 (unique id as new parameter)
                  if (substr_count ($searchlog, "|") == 19)
                  {
                    $searchlog = "|".$searchlog;
                  }

                  list ($uniqid, $date, $action, $site, $search_dir, $date_from, $date_to, $template, $search_textnode, $search_expression, $search_cat, $search_format, $search_filesize, $search_imagewidth, $search_imageheight, $search_imagecolor, $search_imagetype, $geo_border_sw, $geo_border_ne, $object_id, $container_id) = explode ("|", trim ($searchlog));
                  
                  // update to version 8.0.2 (use date and time as unique id)
                  if (empty ($uniqid)) $uniqid = $date;

                  // text based search
                  $search_parameter = array();
                  $search_textnode = json_decode ($search_textnode, true);
    
                  if (is_array ($search_textnode) && sizeof ($search_textnode) > 0)
                  {
                    $temp_array = array();
                    
                    foreach ($search_textnode as $key => $value)
                    {
                      if (!is_numeric ($key) && $value != "")
                      {
                        $temp_array[] = $key.":".$value;
                      }
                      elseif (strpos ("_".$value, "%keyword%/") > 0)
                      {
                        $sql_array = rdbms_externalquery ("SELECT keyword FROM keywords WHERE keyword_id=".getobject ($value));
                        if (!empty ($sql_array[0]['keyword'])) $temp_array[] = getescapedtext ($hcms_lang['keywords'][$lang])." (".$sql_array[0]['keyword'].")";
                      }
                    }

                    if (sizeof ($temp_array) > 0) $search_parameter['text'] = implode (", ", $temp_array);
                  }
                  elseif (!empty ($search_expression)) $search_parameter['text'] = ($search_cat == "text" ? getescapedtext ($hcms_lang['text'][$lang]) : getescapedtext ($hcms_lang['location'][$lang]))." ".getescapedtext ($hcms_lang['search-expression'][$lang])." (".$search_expression.")";

                  // file based search
                  $search_format = json_decode ($search_format, true);

                  if (is_array ($search_format) && sizeof ($search_format) > 0) $search_parameter['file'] = getescapedtext ($hcms_lang['file-type'][$lang])." (".implode (", ", $search_format).")";
                  if (!empty ($search_filesize)) $search_parameter['file'] = $search_filesize."KB";

                  // image based search
                  $search_imagecolor = json_decode ($search_imagecolor, true);

                  // translate colors
                  if (is_array ($search_imagecolor))
                  {
                    foreach ($search_imagecolor as &$value)
                    {
                      $value = getimagecolorname ($value, $lang);
                    }
                  }

                  if (!empty ($search_imagewidth) || !empty ($search_imageheight)) $search_parameter['imagesize'] = $search_imagewidth.($search_imageheight != "" ? "" : "x".$search_imageheight);
                  if (is_array ($search_imagecolor) && sizeof ($search_imagecolor) > 0) $search_parameter['imagecolor'] = getescapedtext ($hcms_lang['image-color'][$lang])." (".implode (", ", $search_imagecolor).")";
                  if (!empty ($search_imagetype)) $search_parameter['imagetype'] = getescapedtext ($hcms_lang['image-type'][$lang])." (".$search_imagetype.")";

                  // geo location based search
                  if (!empty ($geo_border_sw) && !empty ($geo_border_ne)) $search_parameter['geo'] = getescapedtext ($hcms_lang['geo-location'][$lang])." SW ".$geo_border_sw." NE ".$geo_border_ne."";

                  // specific search for ID
                  if (!empty ($object_id) || !empty ($container_id)) $search_parameter['id'] = getescapedtext ($hcms_lang['object-id-link-id'][$lang])." (".(!empty ($object_id) ? $object_id : $container_id).")";

                  echo "
                  <option value=\"".$uniqid."\">".htmlspecialchars (implode (", ", $search_parameter))."</option>";
                }
              }

              echo "
              </select>
              <img onclick=\"deleteSearch();\" class=\"hcmsButtonTiny hcmsButtonSizeSquare\" src=\"".getthemelocation()."img/button_delete.png\" title=\"".getescapedtext ($hcms_lang['delete'][$lang])."\" alt=\"".getescapedtext ($hcms_lang['delete'][$lang])."\" />
            </div>";
            }
          }
          ?>
        </div>
        <hr />
        <label><input type="checkbox" name="search_save" value="1" /> <?php echo getescapedtext ($hcms_lang['save-search'][$lang]); ?></label><br/><br/>
        <button type="button" class="hcmsButtonGreen" style="width:100%;" onclick="startSearch('post');"><?php echo getescapedtext ($hcms_lang['start'][$lang]); ?></button>
      </form>
    </div>

    <!-- initialize -->
    <script type="text/javascript">
    // load screen
    if (document.getElementById('hcmsLoadScreen')) document.getElementById('hcmsLoadScreen').style.display='none';

    // navigation tree
    showNav();
    </script>

  </body>
</html>
<?php 
} 
?>