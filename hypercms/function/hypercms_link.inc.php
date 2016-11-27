<?php
/*
 * This file is part of
 * hyper Content & Digital Management Server - http://www.hypercms.com
 * Copyright (c) by hyper CMS Content Management Solutions GmbH
 *
 * You should have received a copy of the License along with hyperCMS.
 */
 
// ===================================== LINK DATABASE FUNCTIONS =========================================

// ----------------------------------------- link_db_restore ---------------------------------------------
// function: link_db_restore()
// input: publication name (optional)
// output: true / false on error
// requires: hypercms_api.inc.php, config.inc.php

// description:
// This function restores a given or all link management index files

function link_db_restore ($site="")
{
  global $mgmt_config;
  
  if (is_array ($mgmt_config) && isset ($mgmt_config['abs_path_cms']))
  { 
    // content repository
    $loc = $mgmt_config['abs_path_data']."content/";
    
    // 1 st level (content container blocks)
    $blockdir = scandir ($loc);
    
    $i = 0;    
    $time_1 = time();
    $link_db_entry = null;

    // browse all containers in content repository
    foreach ($blockdir as $block)
    {
      if (is_dir ($loc.$block) && $block != "." && $block != ".." && is_numeric ($block))
      {
        // 2nd level (specific content container folder)
        $contdir = scandir ($loc.$block);
        
        foreach ($contdir as $container_id)
        {
          if (is_dir ($loc.$block."/".$container_id))
          {
            // update page links
            $contentdata = loadcontainer ($container_id, "work", "sys");     
            
            if ($contentdata != false) 
            {
              $i++;
            
              // extract publication vom origin location of the object
              $objorigin_array = getcontent ($contentdata, "<contentorigin>");
              $objref_array = getcontent ($contentdata, "<contentobjects>");
              
              $publication1 = getpublication ($objorigin_array[0]);
              $publication2 = getpublication ($objref_array[0]);
              
              if ($publication1 == $publication2) $publication = $publication1;
              elseif ($publication2 != "") $publication = $publication2;
              elseif ($publication1 != "") $publication = $publication1;
              else $publication = "";
              
              if ($publication != "" && ($site == "" || ($site != "" && $site == $publication)))
              {              
                if (!is_array ($publication_array)) $publication_array[] = $publication;
                elseif (!in_array ($publication, $publication_array)) $publication_array[] = $publication;
                
                // container reference
                $link_db_entry[$publication] .= "\n".$container_id.".xml:|";
                
                // object references
                $link_db_entry[$publication] .= $objref_array[0].":|";
                
                // page references
                $linkobj_array = getcontent ($contentdata, "<link>");
                
                if (is_array ($linkobj_array) && sizeof ($linkobj_array) > 0)
                {
                  foreach ($linkobj_array as $linkobj)  
                  {
                    $link_href = getcontent ($linkobj, "<linkhref>");
                    
                    if (!empty ($link_href[0]))
                    {
                      $link_db_entry[$publication] .= $link_href[0]."|";
                    }
                  }
                }
                
                // component references     
                $compobj_array = getcontent ($contentdata, "<component>");   
                
                if (is_array ($compobj_array) && sizeof ($compobj_array) > 0)
                {      
                  foreach ($compobj_array as $compobj)  
                  {
                    $component_files = getcontent ($compobj, "<componentfiles>");
                    
                    if (!empty ($component_files[0]))
                    {
                      $component_files = trim ($component_files[0]);
                      
                      // if multi component
                      if (@substr_count ($component_files, "|") >= 1)
                      {
                        if ($component_files[strlen ($component_files)-1] == "|")
                        { 
                          $component_files = substr ($component_files, 0, strlen ($component_files)-1);
                        }
                      }
                      
                      if ($component_files != "") $link_db_entry[$publication] .= $component_files."|"; 
                    }
                  }
                }
                
                // media references     
                $mediaobj_array = getcontent ($contentdata, "<media>");   
                
                if (is_array ($mediaobj_array) && sizeof ($mediaobj_array) > 0)
                {      
                  foreach ($mediaobj_array as $mediaobj)  
                  {
                    $media_files = getcontent ($mediaobj, "<mediaobject>");
                    
                    if (!empty ($media_files[0]))
                    {
                      $media_files = trim ($media_files[0]);
                      
                      // if multi component
                      if (@substr_count ($component_files, "|") >= 1)
                      {
                        if ($media_files[strlen ($media_files)-1] == "|")
                        { 
                          $media_files = substr ($media_files, 0, strlen ($media_files)-1);
                        }
                      }
                      
                      if ($media_files != "") $link_db_entry[$publication] .= $media_files."|"; 
                    }
                  }
                }
              }
            }
          }
        }
      }
    }
    
    $time_2 = time();    
    $duration = $time_2 - $time_1;    
    
    // copy all containers to new location
    if (is_array ($link_db_entry) && is_array ($publication_array))
    {
      foreach ($publication_array as $publication)
      {
        $link_database = "container:|ojbect|:|link|".$link_db_entry[$publication];
        
        $test = savefile ($mgmt_config['abs_path_data']."link/", $publication.".link.dat", $link_database);
    
        if ($test == true)
        {
          $errcode = "00810";
          $error[] = $mgmt_config['today']."|hypercms_link.inc.php|information|$errcode|regernated and saved link index for publication '".$publication."' successfully (execution time: ".$duration." sec)";
        }
        else
        {
          $errcode = "10810";
          $error[] = $mgmt_config['today']."|hypercms_link.inc.php|error|$errcode|could not regenerate and save link index for publication '".$publication."' (execution time: ".$duration." sec)";
        }                  
      }
      
      // save log
      savelog (@$error); 
      
      return true;
    }
    else return false;
  }
  else return false;
}

// ----------------------------------------- link_db_load ---------------------------------------------
// function: link_db_load()
// input: site, user
// output: link database [2 dim. array] or true / false on error
// requires: hypercms_api.inc.php, config.inc.php

// description:
// This function loads and locks the link management database
// each record of the link management database has the following design:
// xml-content container :| absolute path to 1-n objects :| 1-m links used by 1-n objects
// important: the link management database has to saved or closed after loading it.

function link_db_load ($site, $user)
{
  global $mgmt_config;  

    // if link management is enabled
  if ($mgmt_config[$site]['linkengine'] == true && valid_publicationname ($site) && valid_objectname ($user))
  {
    $link_db_data = loadlockfile ($user, $mgmt_config['abs_path_data']."link/", $site.".link.dat", 5);

    if ($link_db_data != false)
    {
      $link_db_array = explode ("\n", $link_db_data);
      
      foreach ($link_db_array as $link_db_record)
      {
        $link_db_record = trim ($link_db_record);
       
        if ($link_db_record != "")
        {
          list ($container, $objects, $links) = explode (":|", $link_db_record);
          
          $link_db[$container]['container'] = $container;
          $link_db[$container]['object'] = $objects;
          $link_db[$container]['link'] = $links;
        }        
      }
    }
    else 
    {
      $errcode = "10701";
      $error[] = $mgmt_config['today']."|hypercms_link.inc.php|error|$errcode|loadlockfile failed in link_db_load for site: ".$site;         
    
      $link_db = false;
    }
    
    // save log
    savelog (@$error);
    
    return $link_db;
  }
  else return true;  
}

// ----------------------------------------- link_db_read ---------------------------------------------
// function: link_db_read()
// input: site 
// output: link database [2 dim. array] or true / false on error
// requires: hypercms_api.inc.php, config.inc.php

// description:
// This function loads the link management database for reading without locking

function link_db_read ($site)
{
  global $mgmt_config;  

  // if link management is enabled
  if ($mgmt_config[$site]['linkengine'] == true && valid_publicationname ($site))
  {
    // get locked file name
    $locked_info = getlockedfileinfo ($mgmt_config['abs_path_data']."link/", $site.".link.dat");
    
    if ($locked_info != false)
    {
      // load file
      $link_db_data = loadfile ($mgmt_config['abs_path_data']."link/", $locked_info['file']);
      
      if ($link_db_data != false)
      {
        $link_db_array = explode ("\n", $link_db_data);
        
        foreach ($link_db_array as $link_db_record)
        {
          $link_db_record = trim ($link_db_record);
         
          if ($link_db_record != "")
          {
            list ($container, $objects, $links) = explode (":|", $link_db_record);
            
            $link_db[$container]['container'] = $container;
            $link_db[$container]['object'] = $objects;
            $link_db[$container]['link'] = $links;
          }
          else 
          {
            $errcode = "10712";
            $error[] = $mgmt_config['today']."|hypercms_link.inc.php|error|$errcode|link_db_record is corrupt (null), link_db_load failed for site: ".$site;           
          }        
        }
      }
      else 
      {
        $errcode = "10711";
        $error[] = $mgmt_config['today']."|hypercms_link.inc.php|error|$errcode|loadfile failed in link_db_load for site: ".$site;         
      
        $link_db = false;
      }
      
      // save log
      savelog (@$error);
      
      return $link_db;
    }
    else return false;
  } 
  else return true;
}

// ---------------------------------------- link_db_close --------------------------------------------
// function: link_db_close()
// input: site, user 
// output: true/false
// requires: hypercms_api.inc.php

// description:
// closes and unlocks the link management database.

function link_db_close ($site, $user)
{
  global $mgmt_config;  

  // if link management is enabled
  if ($mgmt_config[$site]['linkengine'] == true && valid_publicationname ($site) && valid_objectname ($user))
  {
    return unlockfile ($user, $mgmt_config['abs_path_data']."link/", $site.".link.dat");
  }
  else return true;
}  

// ---------------------------------------- link_db_save --------------------------------------------
// function: link_db_save()
// input: link database array, site, user 
// output: true/false on error
// requires: hypercms_api.inc.php, config.inc.php

// description:
// This function saves und unlocks the link management database

function link_db_save ($site, $link_db, $user)
{
  global $mgmt_config;
  
  // if link management is enabled
  if ($mgmt_config[$site]['linkengine'] == true && valid_publicationname ($site) && valid_objectname ($user))
  {  
    if (is_array ($link_db) && sizeof ($link_db) > 0)
    {    
      foreach ($link_db as $link_db_record)
      {
        if (is_array ($link_db_record))
        {
          $link_db_array[] = implode (":|", $link_db_record);
        }
      }
      
      if (is_array ($link_db_array)) 
      {      
        $link_db_data = implode ("\n", $link_db_array);
            
        if ($link_db_data != false) 
        {
          return savelockfile ($user, $mgmt_config['abs_path_data']."link/", $site.".link.dat", $link_db_data);
        } 
        else 
        {
          link_db_close ($site, $user);
          return false;
        } 
      }
      else
      {
        link_db_close ($site, $user);
        return false;
      }
    }  
    // link db is empty
    elseif (is_array ($link_db))
    {
      return savelockfile ($user, $mgmt_config['abs_path_data']."link/", $site.".link.dat", "container:|object|:|link|\n");
    }
    else
    {
      link_db_close ($site, $user);
      return false;
    }
  }
  else return true;
}

// ---------------------------------------- link_db_update --------------------------------------------
// function: link_db_update()
// input: publication name, link database [2 dim. array], attribute ['object', 'link'], content container [optional] [string], 
//        link category [optional] ['comp', 'page'], current link [optional, must be URL, abs. or rel. path], 
//        new link [optional, must be URL, abs. or rel. path], update option [all, unique]
// output: link database [2 dim. array] or true / false on error
// requires: hypercms_api.inc.php, config.inc.php

// description:
// This function inserts, updates and removes objects and their links from the link management database (add or update a link)
// depending on which link is left empty:
// link_curr = "": add new link (just one link matching given category!)
// link_new = "": delete current link in use (just one linkm matching given category!)
// link_curr & link_new are not empty: update current link with the new one

function link_db_update ($site, $link_db, $attribute, $contentfile, $cat, $link_curr, $link_new, $option)
{
  global $mgmt_config;
  
  // if link management is enabled
  if (valid_publicationname ($site) && $mgmt_config[$site]['linkengine'] == true && $attribute != "")
  {    
    if (is_array ($link_db))
    {  
      // remove last delimiter "|" if sent link is a list (multiple component)
      $link_curr = trim ($link_curr);
      $link_new = trim ($link_new);
      
      if ($link_curr != "")
      {
        // get char of last position in string
        $endchar_curr = substr ($link_curr, strlen ($link_curr)-1);
        
        // cut off last | in link list
        if ($endchar_curr == "|") $link_curr = substr ($link_curr, 0, strlen ($link_curr)-1);
        
        // add root directory constants
        // if the link variable stores more references sperated by |
        if (@substr_count ($link_curr, "|") >= 1)
        {
          $link_curr_array = explode ("|", $link_curr);
          
          if (sizeof ($link_curr_array) >= 1)
          {
            $link_curr = "";
          
            foreach ($link_curr_array as $link)
            {
              if ($link != "") 
              {
                $link = convertpath ($site, $link, $cat);
                $link_curr .= $link."|";
              }
            }
          }
        }
        // if object link
        elseif ($endchar_curr != "/")
        {
          $link_curr = convertpath ($site, $link_curr, $cat)."|";   
        }
        // if directory link
        else
        {
          $link_curr = convertpath ($site, $link_curr, $cat);
        }
      }
      
      if ($link_new != "")
      {
        // get char of last position in string
        $endchar_new = substr ($link_new, strlen ($link_new)-1);
        
        // cut off last | in link list
        if ($endchar_new == "|") $link_new = substr ($link_new, 0, strlen ($link_new)-1);      
        
        // if object link list
        if (@substr_count ($link_new, "|") >= 1)
        {    
          $link_new_array = explode ("|", $link_new);
        
          if (sizeof ($link_new_array) >= 1)
          {
            $link_new = "";
            
            foreach ($link_new_array as $link)
            {    
              if ($link != "") 
              {          
                $link = convertpath ($site, $link, $cat);
                $link_new .= $link."|";        
              }
            } 
          }
        }
        // if object link
        elseif ($endchar_new != "/")
        {
          $link_new = convertpath ($site, $link_new, $cat)."|";   
        }  
        // if directory link
        else
        {
          $link_new = convertpath ($site, $link_new, $cat);
        }  
      }    
  
      // add link to link management database
      if (sizeof ($link_db) > 0)
      {
        // if no content container is given, loop through all records
        if ($contentfile == "")
        {
          foreach ($link_db as $link_db_record)
          {
            // get container name of current record
            $contentfile = $link_db_record['container'];
            
            if ($contentfile != "")
            { 
              // check if current link in use exists
              if ($link_curr != "")
              {    
                // the same link may occure more than one time in link management record => just replace the first link
                if ($option == "unique")
                {
                  $link_startpos = strpos ($link_db[$contentfile][$attribute], $link_curr);
                  $link_length = strlen ($link_curr);   
                        
                  if (substr_count ($link_db[$contentfile][$attribute], $link_curr) > 0)
                  {              
                    // update link if new link is not empty
                    if ($link_new != "")
                    {
                      $link_db[$contentfile][$attribute] = substr_replace ($link_db[$contentfile][$attribute], $link_new, $link_startpos, $link_length);
                    }
                    // otherwise delete link
                    else
                    {
                      $link_db[$contentfile][$attribute] = substr_replace ($link_db[$contentfile][$attribute], "", $link_startpos, $link_length);
                    }  
                  }
                  // add link (current link was not found!)
                  elseif ($link_new != "") 
                  {
                    $link_db[$contentfile][$attribute] = $link_db[$contentfile][$attribute].$link_new;
                  }
                }
                // update all links in the link management record
                elseif ($option == "all")
                {
                  $link_db[$contentfile][$attribute] = str_replace ($link_curr, $link_new, $link_db[$contentfile][$attribute]);
                }
              }
              // new link in link management
              elseif ($link_new != "")
              {
                $link_db[$contentfile][$attribute] = chop ($link_db[$contentfile][$attribute]).$link_new;
              }             
            }        
          }
        }
        // if specific content container should be manipulated
        else
        {     
          // check if current link in use exists
          if ($link_curr != "")
          {
            // the same link may occure more than one time in link management record => just replace the first link
            if ($option == "unique")
            {
              $link_startpos = strpos ($link_db[$contentfile][$attribute], $link_curr);
              $link_length = strlen ($link_curr);     
                    
              if (substr_count ($link_db[$contentfile][$attribute], $link_curr) > 0)
              { 
                // update link if new link is not empty
                if ($link_new != "")
                {
                  $link_db[$contentfile][$attribute] = substr_replace ($link_db[$contentfile][$attribute], $link_new, $link_startpos, $link_length);
                }
                // otherwise delete link
                else
                {
                  $link_db[$contentfile][$attribute] = substr_replace ($link_db[$contentfile][$attribute], "", $link_startpos, $link_length);
                }
              }
              // new link (current link was not found!)
              elseif ($link_new != "")
              {
                $link_db[$contentfile][$attribute] = chop ($link_db[$contentfile][$attribute]).$link_new;
              }                       
            }
            // update all links in the link management record
            elseif ($option == "all")
            {
              $link_db[$contentfile][$attribute] = str_replace ($link_curr, $link_new, $link_db[$contentfile][$attribute]);
            }             
          }
          // new link in link management
          elseif ($link_new != "")        
          {
            $link_db[$contentfile][$attribute] = chop ($link_db[$contentfile][$attribute]).$link_new;
          }
        }
        
        return $link_db;
      }
      else return false;    
    }  
    else return false;
  }
  else return true;
}

// ---------------------------------------- link_db_insert --------------------------------------------
// function: link_db_insert()
// input: publication name, link database [2 dim. array], content container, link category ['comp, page'], object [optional] 
// output: link database [2 dim. array] or true / false
// requires: hypercms_api.inc.php, config.inc.php

// description:
// This function inserts a new record in the link management database
// optionally the created object can be also inserted

function link_db_insert ($site, $link_db, $contentfile, $cat, $object)
{
  global $mgmt_config;

  // if link management is enabled
  if ($mgmt_config[$site]['linkengine'] == true && valid_publicationname ($site))
  {    
    if (is_array ($link_db) && $contentfile != "" && !isset ($link_db[$contentfile]))
    {
      if ($object != "")
      {
        // remove last delimiter "|" if sent link is a list (multiple component)
        $object = trim ($object);
        if ($object[strlen($object)-1] == "|") $object = substr ($object, 0, strlen ($object)-1);
        
        // add root directory constants
        $object_array = explode ("|", $object);
        
        if ($object_array != false && sizeof ($object_array) >= 1)
        {
          $object = "";
        
          foreach ($object_array as $link)
          {
            $link = convertpath ($site, $link, $cat);
            
            if ($link != false) $object .= $link."|";
          }  
        }
      }
      
      // insert the new record
      $link_db[$contentfile]['container'] = $contentfile;
      $link_db[$contentfile]['object'] = $object;    
      $link_db[$contentfile]['link'] = "";
      
      return $link_db;
    }
    else return false;
  }
  else return true;
}

// ---------------------------------------- link_db_delete --------------------------------------------
// function: link_db_delete()
// input: link database [2 dim. array], content container 
// output: link database [2 dim. array] or true / false on error
// requires: hypercms_api.inc.php, config.inc.php

// description:
// This function deletes a record in the link management database

function link_db_delete ($site, $link_db, $contentfile)
{
  global $mgmt_config;
  
  // if link management is enabled
  if ($mgmt_config[$site]['linkengine'] == true && valid_publicationname ($site))
  {
    if (is_array ($link_db) && $contentfile != "" && isset ($link_db[$contentfile]))
    {
      $link_db[$contentfile] = null;
      
      return $link_db;
    }
    else return false;
  }
  else return true;
}  

// ---------------------------------------- link_db_getobject --------------------------------------------
// function: link_db_getobject()
// input: link database attribut (references to objects seperated by |)
// output: objects [array] / false on error 

// description:
// This function splits the object string into an array of objects.

function link_db_getobject ($multiobject)
{
  global $mgmt_config;
  
  if (!is_array ($multiobject) && $multiobject != "")
  {
    // cut off whitespaces at the begin and end of the string
    $multiobject = trim ($multiobject);
    
    // cut off | at the begin and end of the string
    $multiobject = trim ($multiobject, "|");  

    // remove empty string
    if (strpos ($multiobject, "||") > 0) $multiobject = str_replace (array("||||","|||","||"), array("|","|","|"), $multiobject);    

    $link_db_object = explode ("|", $multiobject); 
    
    if (is_array ($link_db_object)) return $link_db_object;
    else return false; 
  }
  else return false;
}  

// ---------------------------------------- link_update --------------------------------------------
// function: link_update()
// input: publication name, container name, old link (converted), new link (converted)
// output: true/false 

// description:
// This function updates the link of the published and working content container and link file

function link_update ($site, $container, $link_old, $link_new)
{
  global $user, $mgmt_config;

  if ($container != "" && $link_old != "" && (substr_count ($link_old, "%page%") > 0 || substr_count ($link_old, "%comp%") > 0) && ($link_new == "" || substr_count ($link_new, "%page%") > 0 || substr_count ($link_new, "%comp%") > 0))
  {
    // get container id
    $container_id = substr ($container, 0, strpos ($container, ".xml"));
        
    // load content container
    $container_data = loadcontainer ($container, "published", $user);

    // update link and save content container and link file 
    // published container
    if ($container_data != false)
    {
      // remove current link
      if ($link_new == "")
      {
        // try with delimiter first
        $container_data = str_replace ($link_old."|", "", $container_data);
        $container_data = str_replace ($link_old, "", $container_data);
      }
      // update link
      else
      {    
        $container_data = str_replace ($link_old, $link_new, $container_data);
      }
      
      $test1 = savecontainer ($container, "published", $container_data, $user);   
    }
    else $test1 = false;

    // get working container
    $result = getcontainername ($container);
    $containerwrk = $result['container'];
    
    // load working container
    $containerwrk_data = loadcontainer ($containerwrk, "work", $user);

    // working container
    if ($containerwrk_data != false)
    { 
      // remove current link
      if ($link_new == "")
      {
        // try with delimiter first
        $containerwrk_data = str_replace ($link_old."|", "", $containerwrk_data);
        $containerwrk_data = str_replace ($link_old, "", $containerwrk_data);        
      } 
      // update link
      else
      {          
        $containerwrk_data = str_replace ($link_old, $link_new, $containerwrk_data);
      }
       
      $test2 = savecontainer ($containerwrk, "work", $containerwrk_data, $user);
    }
    else $test2 = true;
    
    // update link index
    if ($mgmt_config[$site]['linkengine'] == true)
    {
      $linkindex = substr ($container, 0, strpos ($container, ".xml"));  
      $link_data = loadfile_fast ($mgmt_config['abs_path_link'], $linkindex);       
    
      if ($link_data != false)
      { 
        if (substr ($link_old, 0, 6) == "%page%")
        {      
          $link_old = deconvertlink (trim ($link_old));
        }
        elseif (substr ($link_old, 0, 6) == "%comp%")
        {
          $link_old = str_replace ("%comp%", "", trim ($link_old));
        }
        
        if (substr ($link_new, 0, 6) == "%page%")
        {
          $link_new = deconvertlink (trim ($link_new));
        }
        elseif (substr ($link_new, 0, 6) == "%comp%")
        {
          $link_new = str_replace ("%comp%", "", trim ($link_new));
        }        
      
        $link_data = str_replace ($link_old, $link_new, $link_data);
        $test3 = savefile ($mgmt_config['abs_path_link'], $linkindex, $link_data); 
        
        // remote client
        remoteclient ("save", "abs_path_link", $site, $mgmt_config['abs_path_link'], "", $linkindex, "");                
      }   
      else $test3 = true;    
    }  
    else $test3 = true;  
    
    if ($test1 != false && $test2 != false && $test3 != false) return true;
    else return false;    
  }
  else return false;
}  

// ---------------------------------------- getlinkedobject --------------------------------------------
// function: getlinkedobject()
// input: publication, location, object (name and extension), category [page, comp][optional]
// output: objects which link to the given object [array] or true / false

// description:
// This function gets all objects which link to the given object.
// works with pages (page links) and components (component links) if link management is enabled.

function getlinkedobject ($site, $location, $page, $cat)
{
  global $mgmt_config;
  
  if (valid_publicationname ($site) && valid_locationname ($location) && valid_objectname ($page))
  {
    // load link management database
    $inherit_db = inherit_db_read ();
    
    $site_array[0] = $site;
    $child_array = inherit_db_getchild ($inherit_db, $site);
    if ($child_array != false) $site_array = array_merge ($site_array, $child_array);
    
    // get category if not set
    if ($cat == "") $cat = getcategory ($site, $location); 
    
    // set result array counter
    $counter = 0;
    
    // loop to all publications
    foreach ($site_array as $site)
    {
      // load configuration of publication
      if (!isset ($mgmt_config[$site]) || !is_array ($mgmt_config[$site])) require ($mgmt_config['abs_path_data']."config/".$site.".conf.php");
  
      // load link management database
      $link_db = link_db_read ($site);
      
      if (is_array ($link_db) && sizeof ($link_db) > 0 && $location != "" && $page != "")
      { 
        // convert location
        $page_path = convertpath ($site, $location, $cat).$page;
  
        // explore each record in link management database
        foreach ($link_db as $link_db_record)
        {
          // check if page or component is included in the link collection
          if (@substr_count ($link_db_record['link'], $page_path."|") > 0)
          {  
            $object_array = link_db_getobject ($link_db_record['object']);
            
            if (is_array ($object_array))
            {
              // collect objects and form result
              foreach ($object_array as $object_path)
              {               
                // extract location and object name 
                $object_path = trim ($object_path);
                $result_location_converted = getlocation ($object_path);
                
                // get category of object
                $result_cat = getcategory ($site, $result_location_converted);  
                            
                $result_location = deconvertpath ($result_location_converted, "file");
                $result_page = getobject ($object_path);
                
                // result array
                $result[$counter]['publication']= $site;
                $result[$counter]['convertedlocation']= $result_location_converted;
                $result[$counter]['location']= $result_location;
                $result[$counter]['object']= $result_page;
                $result[$counter]['category']= $result_cat;
                
                $counter++;
              }
            }
          }
        }  
      }
      elseif ($mgmt_config[$site]['linkengine'] == true)
      {  
        $result = false;    
        $errcode = "10189";
        $error[] = $mgmt_config['today']."|hypercms_link.inc.php|error|$errcode|could not read link management database for publication $site";        
      }
      // link management is disabled
      elseif ($link_db == true)
      {
        $result = true;
      }
    }
    
    // save log
    savelog (@$error);
    
    // return result
    if (isset ($result) && is_array ($result) && sizeof ($result) > 0) return $result;
    elseif (isset ($result) && $result == true) return $result;
    else return false;
  }
  else return false;
}

// ---------------------------------------- getconnectedobject --------------------------------------------
// function: getconnectedobject()
// input: container name, container type [work,published,version] (optional)
// output: connected objects[array] 

// description:
// This function gets all objects which use the same content container and are therefore connected.

function getconnectedobject ($container, $type="work")
{
  global $mgmt_config, $user;
  
  if (valid_objectname ($container))
  {
    // load container
    $container_data = loadcontainer ($container, $type, $user);
 
    // extract object references
    if ($container_data != "")
    {
      $object_array = getcontent ($container_data, "<contentobjects>");
      
      if (!empty ($object_array[0])) $object_array = link_db_getobject ($object_array[0]);
      else $object_array = false;
    }
    else $object_array = false;

    // collect object information
    if (is_array ($object_array)) 
    {
      // set result array counter
      $counter = 0;
      $result = array();
  
      // collect objects and form result
      foreach ($object_array as $object_path)
      {  
        if ($object_path != "")
        {   
          // get category of object
          $site = getpublication ($object_path);
          $location_converted = getlocation ($object_path);
          $cat = getcategory ($site, $location_converted);                         
          $location = deconvertpath ($location_converted, "file");
          $page = getobject ($object_path);
         
          // result array
          $result[$counter]['publication']= $site;
          $result[$counter]['convertedlocation']= $location_converted;
          $result[$counter]['location']= $location;
          $result[$counter]['object']= $page;
          $result[$counter]['category']= $cat;   
         
          $counter++;
        }
      }
    }
    else
    {      
      $errcode = "10188";
      $error[] = $mgmt_config['today']."|hypercms_link.inc.php|error|$errcode|could not extract objects from container $container";        
    }      

    // save log
    savelog (@$error);
  }  
  
  // return result
  if (isset ($result) && is_array ($result) && sizeof ($result) > 0) return $result;
  else return false;
}

// ---------------------------------------- extractlinks --------------------------------------------
// function: extractlinks()
// input: text content as string, link identifiert ("href" for hyperreferences, "src" for image references or embed references (flash), "value" for paramter (flash))
// output: object links [array] / false on error

// description:
// This function extracts all links based on it's identifier from a text and returns an array of all links

function extractlinks ($textcontent, $identifier)
{
  global $mgmt_config;

  if ($textcontent != "" && $identifier != "")
  {
    // remove freespaces   
    $string = str_replace ("   ", " ", $textcontent);
    $string = str_replace ("  ", " ", $textcontent);
    $string = str_replace (" =", "=", $textcontent);
  
    // link identifier seach string, e.g. " href="
    $identifier_search = " ".$identifier."=";
    
    $subtext = $textcontent;
    $link_array = Null;
    $i = 0;
    
    while (strpos (strtolower ($subtext), strtolower ($identifier_search)) > 0)
    {
      $offset = strpos (strtolower ($subtext), strtolower ($identifier_search)) + strlen ($identifier_search);
      // extract html tag from text content
      $href_tag = gethtmltag ($subtext, $identifier_search);
 
      if ($href_tag != false)
      {
        // extract link from html tag
        $link = getattribute ($href_tag, $identifier);

        if ($link != false) $link_array[] = $link;
      }
      
      $i++;
      if ($i > 900) break;
      $subtext = substr ($subtext, $offset);
    }
    
    // return result
    if (is_array ($link_array)) return $link_array;
    else return false;
  }
  else return false;
}

// ---------------------------------------- medialink_to_complink --------------------------------------------
// function: medialink_to_complink()
// input: media link reference array (to media repository)
// output: object links [array] / false on error

// description:
// This function returns an array of objects by a given media link (used in formatted text)
// This function can be used to transform all links to media file of the repository into 
// components links.

function medialinks_to_complinks ($link_array)
{
  global $mgmt_config;
  
  $object_array = array();
  
  if (is_array ($link_array))
  {
    foreach ($link_array as $link)
    {
      // set default link for result
      $object_array[$link] = $link;
    
      // check if media link
      if (strpos ($link, "_hcm") > 0)
      {
        $container_id = getmediacontainerid ($link);
        
        if ($container_id != false)
        {
          if ($mgmt_config['db_connect_rdbms'] != "")
          {
            $temp_array = rdbms_getobjects ($container_id, "");

            if (is_array ($temp_array)) 
            {
              foreach ($temp_array as $temp_link)
              {
                if ($temp_link != "")
                {
                  $object_array[$link] = $temp_link;
                  break;
                }
              }
            }
          }
          else
          {
            if (substr_count ($link, "%media%") == 0) $link_converted = str_replace ($mgmt_config['url_path_media'], "%media%/", $link);
            else $link_converted = $link;
            
            $site = getpublication ($link_converted);
            $link_db = link_db_read ($site);
            
            if (is_array ($link_db))
            {
              $temp_array = link_db_getobject ($link_db[$container_id.".xml"]['object']);

              if (is_array ($temp_array)) 
              {
                foreach ($temp_array as $temp_link)
                {
                  if ($temp_link != "")
                  {
                    $object_array[$link] = $temp_link;
                    break;
                  }
                }
              }
            }
          }
        }
      }
    }
    
    // return result
    if (is_array ($object_array) && sizeof ($object_array) > 0) return $object_array;
    else return false;    
  }
  else return false;
}

// ---------------------------------------- medialink_to_complink --------------------------------------------
// function: medialink_to_complink()
// input: media link reference array (to media repository)
// output: media file links [array] / false on error

// description:
// This function returns an array of objects by a given media link (used in formatted text)
// This function can be used to transform all links to media file of the repository into components links.

function complinks_to_medialinks ($link_array)
{
  global $mgmt_config;
  
  if (is_array ($link_array))
  {
    foreach ($link_array as $link)
    {
      // set default link for result
      $mediafile_array[$link] = $link;
    
      // deconvert
      $link_converted = deconvertpath ($link, "file");
      
      if (@is_file ($link_converted))
      { 
        $site = getpublication ($link);
        $location = getlocation ($link_converted);
        $object = getobject ($link);
        $data = loadfile ($location, $object);
        
        if ($data != "")
        {
          // get media file name
          $mediafile = getfilename ($data, "media");
          
          if ($mediafile != false)
          {
            $mediafile_array[$link] = "%media%/".$site."/".$mediafile;
          }         
        }
      }
    }
    
    // return result
    if (is_array ($mediafile_array)) return $mediafile_array;
    else return false;    
  }
  else return false;
}
?>