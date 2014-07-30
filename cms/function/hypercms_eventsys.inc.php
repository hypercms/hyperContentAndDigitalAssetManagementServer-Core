<?php
/*
 * This file is part of
 * hyper Content Management Server - http://www.hypercms.com
 * Copyright (c) by hyper CMS Content Management Solutions GmbH
 *
 * You should have received a copy of the License along with hyperCMS.
 */
 
// set functions active (1) or inactive (0)
// these settings are valid for all publications
$eventsystem['onaccess'] = 0;
$eventsystem['onlogon_pre'] = 0;
$eventsystem['onlogon_post'] = 0;
$eventsystem['onobjectlist_pre'] = 0;
$eventsystem['onobjectlist_post'] = 0;
$eventsystem['oncreatefolder_pre'] = 0;
$eventsystem['oncreatefolder_post'] = 0;
$eventsystem['ondeletefolder_pre'] = 0;
$eventsystem['ondeletefolder_post'] = 0;
$eventsystem['onrenamefolder_pre'] = 0;
$eventsystem['onrenamefolder_post'] = 0;
$eventsystem['oncreateobject_pre'] = 0;
$eventsystem['oncreateobject_post'] = 0;
$eventsystem['onfileupload_pre'] = 0;
$eventsystem['onfileupload_post'] = 0;
$eventsystem['onfiledownload_pre'] = 0;
$eventsystem['onfiledownload_post'] = 0;
$eventsystem['oneditobject_pre'] = 0;
$eventsystem['oneditobject_post'] = 0;
$eventsystem['onsaveobject_pre'] = 0;
$eventsystem['onsaveobject_post'] = 0;
$eventsystem['onrenameobject_pre'] = 0;
$eventsystem['onrenameobject_post'] = 0;
$eventsystem['ondeleteobject_pre'] = 0;
$eventsystem['ondeleteobject_post'] = 0;
$eventsystem['oncutobject_pre'] = 0;
$eventsystem['oncutobject_post'] = 0;
$eventsystem['oncopyobject_pre'] = 0;
$eventsystem['oncopyobject_post'] = 0;
$eventsystem['oncopyconnectedobject_pre'] = 0;
$eventsystem['oncopyconnectedobject_post'] = 0;
$eventsystem['onpasteobject_pre'] = 0;
$eventsystem['onpasteobject_post'] = 0;
$eventsystem['onlockobject_pre'] = 0;
$eventsystem['onlockobject_post'] = 0;
$eventsystem['onunlockobject_pre'] = 0;
$eventsystem['onunlockobject_post'] = 0;
$eventsystem['onpublishobject_pre'] = 0;
$eventsystem['onpublishobject_post'] = 0;
$eventsystem['onunpublishobject_pre'] = 0;
$eventsystem['onunpublishobject_post'] = 0;
$eventsystem['oncreatepublication_pre'] = 0;
$eventsystem['oncreatepublication_post'] = 0;
$eventsystem['onsavepublication_pre'] = 0;
$eventsystem['onsavepublication_post'] = 0;
$eventsystem['ondeletepublication_pre'] = 0;
$eventsystem['ondeletepublication_post'] = 0;
$eventsystem['oncreateuser_pre'] = 0;
$eventsystem['oncreateuser_post'] = 0;
$eventsystem['onsaveuser_pre'] = 0;
$eventsystem['onsaveuser_post'] = 0;
$eventsystem['ondeleteuser_pre'] = 0;
$eventsystem['ondeleteuser_post'] = 0;
$eventsystem['oncreategroup_pre'] = 0;
$eventsystem['oncreategroup_post'] = 0;
$eventsystem['onsavegroup_pre'] = 0;
$eventsystem['onsavegroup_post'] = 0;
$eventsystem['ondeletegroup_pre'] = 0;
$eventsystem['ondeletegroup_post'] = 0;

// description:
// with the help of the hyperCMS Event System you can automize operations on the basis
// of user interaction. e.g. if an object is published successfully by a user you start another action 
// using the function "onpublishobject_post". there you can define other actions on any items.
// for each action you have a pre and a post event. a pre event will be executed before the action takes
// place on an item. the post event will be exuted only if the action on an item was successfully.
// please note that the input parameter "$cat" describes the category of an object in terms of, is 
// it a component [comp] or a page [page].
// please note that the manually user interaction on folders, e.g. publish a folder and all
// its items will cause all objects to be published. after each successfully published object the action
// "onpublishobject_post" will be started if it is set to 1.
// if you are using the hyperCMS API you wont need to include the hyperCMS Event System because
// the API includes it automatically.
// please note that it is required to have all additional necessary variables to be declared as
// global inside the function, so the action will have access to these variables besides the function input.
// please note that you can easily produce endless loops if you trigger the same action that
// was started in your action. to avoid this you have to set the variable "$eventsystem['hide']" to 1.
// please note that post events will only be executed if the action did not fail.
// if you want to use pre events for data verification you may want to stop the action before it will
// be executed in case the data failed your verification. for instance before the content of an
// object will be saved you want to verify it and stop the process if a stop-word occurs.
// this can not be done without the command "exit" that will stop the further exection, which is not the proper
// solution. instead a log, mail notification or cleaning the content in the event should be executed.
// keep in mind that the eventsystem has no impact on the execution of the code of the hyper Content Management 
// Server. So a return value (true or false) has no impact.

// disable exit and die to prevent the termination of hyper Content Management Server by Event System
ini_set ('disable_functions', 'exit, die');

// --------------------------------------------- on access event ----------------------------------------------
// this function will be executed for each user accessing the system by a wrapper, download or access link
function onaccess ($_REQUEST)
{
  global $eventsystem, $mgmt_config;

  // hide the event used in your action (1) otherwise execute event (0)
  $eventsystem['hide'] = 0; 
  
  // insert your program code here

  // return true if successful
  return true;
}

// --------------------------------------------- on logon PRE event ----------------------------------------------
// this function will be executed for each user before logon
function onlogon_pre ($user)
{
  global $eventsystem, $mgmt_config;

  // hide the event used in your action (1) otherwise execute event (0)
  $eventsystem['hide'] = 0; 
  
  // insert your program code here  
  
  // return true if successful
  return true;
}

// --------------------------------------------- on logon POST event ----------------------------------------------
// this function will be executed for each user after logon
function onlogon_post ($user, $result="")
{
  global $eventsystem, $mgmt_config;

  // hide the event used in your action (1) otherwise execute event (0)
  $eventsystem['hide'] = 0; 
  
  // insert your program code here  
  
  // return true if successful
  return true;
}

// --------------------------------------------- on objectlist PRE event ----------------------------------------------
// this function will be executed for each object in the detailed or gallery view
function onobjectlist_pre ($site, $cat, $location, $object, $user)
{
  global $eventsystem, $mgmt_config;

  // hide the event used in your action (1) otherwise execute event (0)
  $eventsystem['hide'] = 0; 
  
  // insert your program code here  
  
  // return true if successful
  return true;
  // or return manipulated container content
  // return $container_content;
}

// --------------------------------------------- on objectlist POST event ----------------------------------------------
// this function will be executed for each object in the detailed or gallery view
function onobjectlist_post ($site, $cat, $location, $object, $container_name, $container_content, $usedby, $user)
{
  global $eventsystem, $mgmt_config;

  // hide the event used in your action (1) otherwise execute event (0)
  $eventsystem['hide'] = 0; 
  
  // insert your program code here  
  
  // return true if successful
  return true;
  // or return manipulated container content
  // return $container_content;
}

// -------------------------------------------- on create folder PRE event ---------------------------------------------
function oncreatefolder_pre ($site, $cat, $location, $folder, $user)
{
  global $eventsystem, $mgmt_config;

  // hide the event used in your action (1) otherwise execute event (0)
  $eventsystem['hide'] = 0; 
  
  // insert your program code here

  // return true if successful
  return true;
}

// -------------------------------------------- on create folder POST event ---------------------------------------------
function oncreatefolder_post ($site, $cat, $location, $folder, $user)
{
  global $eventsystem, $mgmt_config;

  // hide the event used in your action (1) otherwise execute event (0)
  $eventsystem['hide'] = 0; 
  
  // insert your program code here

  // return true if successful
  return true;
}

// -------------------------------------------- on delete folder PRE event ---------------------------------------------
function ondeletefolder_pre ($site, $cat, $location, $folder, $user)
{
  global $eventsystem, $mgmt_config;

  // hide the event used in your action (1) otherwise execute event (0)
  $eventsystem['hide'] = 0; 
  
  // insert your program code here  
  
  // return true if successful
  return true;
}

// -------------------------------------------- on delete folder POST event ---------------------------------------------
function ondeletefolder_post ($site, $cat, $location, $folder, $user)
{
  global $eventsystem, $mgmt_config;

  // hide the event used in your action (1) otherwise execute event (0)
  $eventsystem['hide'] = 0; 
  
  // insert your program code here  
  
  // return true if successful
  return true;
}

// -------------------------------------------- on rename folder PRE event ---------------------------------------------
function onrenamefolder_pre ($site, $cat, $location, $folder, $foldernew, $user)
{
  global $eventsystem, $mgmt_config;

  // hide the event used in your action (1) otherwise execute event (0)
  $eventsystem['hide'] = 0; 
  
  // insert your program code here  
  
  // return true if successful
  return true;
}

// -------------------------------------------- on rename folder POST event ---------------------------------------------
function onrenamefolder_post ($site, $cat, $location, $folder, $foldernew, $user)
{
  global $eventsystem, $mgmt_config;

  // hide the event used in your action (1) otherwise execute event (0)
  $eventsystem['hide'] = 0; 
  
  // insert your program code here  
  
  // return true if successful
  return true;
}

// -------------------------------------------- on create object PRE event ---------------------------------------------
function oncreateobject_pre ($site, $cat, $location, $object, $template, $user)
{
  global $eventsystem, $mgmt_config;

  // hide the event used in your action (1) otherwise execute event (0)
  $eventsystem['hide'] = 0; 
  
  // insert your program code here    
  
  // return true if successful
  return true;
}

// -------------------------------------------- on create object POST event ---------------------------------------------
function oncreateobject_post ($site, $cat, $location, $object, $template, $user)
{
  global $eventsystem, $mgmt_config;

  // hide the event used in your action (1) otherwise execute event (0)
  $eventsystem['hide'] = 0; 
  
  // insert your program code here    
  
  // return true if successful
  return true;
}

// --------------------------------------------- on file upload PRE event ----------------------------------------------
function onfileupload_pre ($site, $cat, $location, $mediafile, $user)
{
  global $eventsystem, $mgmt_config;

  // hide the event used in your action (1) otherwise execute event (0)
  $eventsystem['hide'] = 0; 
  
  // insert your program code here    
  
  // return true if successful
  return true;
}

// --------------------------------------------- on file upload POST event ----------------------------------------------
function onfileupload_post ($site, $cat, $location, $object, $mediafile, $container, $user)
{
  global $eventsystem, $mgmt_config;

  // hide the event used in your action (1) otherwise execute event (0)
  $eventsystem['hide'] = 0; 
  
  // insert your program code here    
  
  // return true if successful
  return true;
}

// --------------------------------------------- on file download PRE event ----------------------------------------------
function onfiledownload_pre ($site, $medialocation, $mediafile, $name, $user)
{
  global $eventsystem, $mgmt_config;

  // hide the event used in your action (1) otherwise execute event (0)
  $eventsystem['hide'] = 0; 
  
  // insert your program code here    
  
  // return true if successful
  return true;
}

// --------------------------------------------- on file download POST event ----------------------------------------------
function onfiledownload_post ($site, $medialocation, $mediafile, $name, $user)
{
  global $eventsystem, $mgmt_config;

  // hide the event used in your action (1) otherwise execute event (0)
  $eventsystem['hide'] = 0; 
  
  // insert your program code here    
  
  // return true if successful
  return true;
}

// --------------------------------------------- on save object PRE event ----------------------------------------------
function onsaveobject_pre ($site, $cat, $location, $object, $container_name, $container_content, $user)
{
  global $eventsystem, $mgmt_config;

  // hide the event used in your action (1) otherwise execute event (0)
  $eventsystem['hide'] = 0; 
  
  // insert your program code here  
  
  // return true if successful
  return true;
  // or return manipulated container content
  // return $container_content;
}

// --------------------------------------------- on save object POST event ----------------------------------------------
function onsaveobject_post ($site, $cat, $location, $object, $container_name, $container_content, $user)
{
  global $eventsystem, $mgmt_config;

  // hide the event used in your action (1) otherwise execute event (0)
  $eventsystem['hide'] = 0; 
  
  // insert your program code here  
  
  // return true if successful
  return true;
  // or return manipulated container content
  // return $container_content;
}

// --------------------------------------------- on edit object PRE event ----------------------------------------------
function oneditobject_pre ($site, $cat, $location, $object, $objectname, $filetype)
{
  global $eventsystem, $mgmt_config;

  // hide the event used in your action (1) otherwise execute event (0)
  $eventsystem['hide'] = 0; 
  
  // insert your program code here  
  
  // return true if successful
  return true;
}

// --------------------------------------------- on edit object POST event ----------------------------------------------
function oneditobject_post ($site, $cat, $location, $object, $objectname, $filetype)
{
  global $eventsystem, $mgmt_config;

  // hide the event used in your action (1) otherwise execute event (0)
  $eventsystem['hide'] = 0; 
  
  // insert your program code here  
  
  // return true if successful
  return true;
}

// --------------------------------------------- on rename object PRE event ---------------------------------------------
function onrenameobject_pre ($site, $cat, $location, $object, $objectnew, $user)
{
  global $eventsystem, $mgmt_config;

  // hide the event used in your action (1) otherwise execute event (0)
  $eventsystem['hide'] = 0; 
  
  // insert your program code here  
  
  // return true if successful
  return true;
}

// --------------------------------------------- on rename object POST event ---------------------------------------------
function onrenameobject_post ($site, $cat, $location, $object, $objectnew, $user)
{
  global $eventsystem, $mgmt_config;

  // hide the event used in your action (1) otherwise execute event (0)
  $eventsystem['hide'] = 0; 
  
  // insert your program code here  
  
  // return true if successful
  return true;
}

// --------------------------------------------- on delete object PRE event ---------------------------------------------
function ondeleteobject_pre ($site, $cat, $location, $object, $user)
{
  global $eventsystem, $mgmt_config;

  // hide the event used in your action (1) otherwise execute event (0)
  $eventsystem['hide'] = 0; 
  
  // insert your program code here  
  
  // return true if successful
  return true;
}

// --------------------------------------------- on delete object POST event ---------------------------------------------
function ondeleteobject_post ($site, $cat, $location, $object, $user)
{
  global $eventsystem, $mgmt_config;

  // hide the event used in your action (1) otherwise execute event (0)
  $eventsystem['hide'] = 0; 
  
  // insert your program code here  
  
  // return true if successful
  return true;
}

// ---------------------------------------------- on cut object PRE event -----------------------------------------------
// please note: object can be an object (page, component, file) or folder!
// you can use the function is_file ($location.$object) == true
// or is_dir ($location.$object) == true to distinguish between objects and folders.
function oncutobject_pre ($site, $cat, $location, $object, $user)
{
  global $eventsystem, $mgmt_config;

  // hide the event used in your action (1) otherwise execute event (0)
  $eventsystem['hide'] = 0; 
  
  // insert your program code here  
  
  // return true if successful
  return true;
}

// ---------------------------------------------- on cut object POST event -----------------------------------------------
// please note: object can be an object (page, component, file) or folder!
// you can use the function is_file ($location.$object) == true
// or is_dir ($location.$object) == true to distinguish between objects and folders.
function oncutobject_post ($site, $cat, $location, $object, $user)
{
  global $eventsystem, $mgmt_config;

  // hide the event used in your action (1) otherwise execute event (0)
  $eventsystem['hide'] = 0; 
  
  // insert your program code here  
  
  // return true if successful
  return true;
}

// ---------------------------------------------- on copy object PRE event ----------------------------------------------
// please note: object can be an object (page, component, file) or folder!
// you can use the function is_file ($location.$object) == true
// or is_dir ($location.$object) == true to distinguish between objects and folders.
function oncopyobject_pre ($site, $cat, $location, $object, $user)
{
  global $eventsystem, $mgmt_config;
  
  // hide the event used in your action (1) otherwise execute event (0)
  $eventsystem['hide'] = 0; 
  
  // insert your program code here  
  
  // return true if successful
  return true;
}

// ---------------------------------------------- on copy object POST event ----------------------------------------------
// please note: object can be an object (page, component, file) or folder!
// you can use the function is_file ($location.$object) == true
// or is_dir ($location.$object) == true to distinguish between objects and folders.
function oncopyobject_post ($site, $cat, $location, $object, $user)
{
  global $eventsystem, $mgmt_config;
  
  // hide the event used in your action (1) otherwise execute event (0)
  $eventsystem['hide'] = 0; 
  
  // insert your program code here  
  
  // return true if successful
  return true;
}

// ------------------------------------------ on copy connected object PRE event -------------------------------------------
// please note: object can be an object (page, component, file) or folder!
// you can use the function is_file ($location.$object) == true
// or is_dir ($location.$object) == true to distinguish between objects and folders.
function oncopyconnectedobject_pre ($site, $cat, $location, $object, $user)
{
  global $eventsystem, $mgmt_config;
  
  // hide the event used in your action (1) otherwise execute event (0)
  $eventsystem['hide'] = 0; 
  
  // insert your program code here  
  
  // return true if successful
  return true;
}

// ------------------------------------------ on copy connected object POST event -------------------------------------------
// please note: object can be an object (page, component, file) or folder!
// you can use the function is_file ($location.$object) == true
// or is_dir ($location.$object) == true to distinguish between objects and folders.
function oncopyconnectedobject_post ($site, $cat, $location, $object, $user)
{
  global $eventsystem, $mgmt_config;
  
  // hide the event used in your action (1) otherwise execute event (0)
  $eventsystem['hide'] = 0; 
  
  // insert your program code here  
  
  // return true if successful
  return true;
}

// --------------------------------------------- on paste object PRE event ----------------------------------------------
// please note: only objects will be handled by action "pasteobject"!
function onpasteobject_pre ($site, $cat, $location, $locationnew, $object, $user)
{
  global $eventsystem, $mgmt_config;
  
  // hide the event used in your action (1) otherwise execute event (0)
  $eventsystem['hide'] = 0; 
  
  // insert your program code here  
  
  // return true if successful
  return true;
}

// --------------------------------------------- on paste object POST event ----------------------------------------------
// please note: only objects will be handled by action "pasteobject"!
function onpasteobject_post ($site, $cat, $location, $locationnew, $object, $user)
{
  global $eventsystem, $mgmt_config;
  
  // hide the event used in your action (1) otherwise execute event (0)
  $eventsystem['hide'] = 0; 
  
  // insert your program code here  
  
  // return true if successful
  return true;
}

// ---------------------------------------------- on lock object PRE event ----------------------------------------------
function onlockobject_pre ($site, $cat, $location, $object, $user)
{
  global $eventsystem, $mgmt_config;
  
  // hide the event used in your action (1) otherwise execute event (0)
  $eventsystem['hide'] = 0; 
  
  // insert your program code here  
  
  // return true if successful
  return true;
}

// ---------------------------------------------- on lock object POST event ----------------------------------------------
function onlockobject_post ($site, $cat, $location, $object, $user)
{
  global $eventsystem, $mgmt_config;
  
  // hide the event used in your action (1) otherwise execute event (0)
  $eventsystem['hide'] = 0; 
  
  // insert your program code here  
  
  // return true if successful
  return true;
}

// -------------------------------------------- on unlock object PRE event ----------------------------------------------
function onunlockobject_pre ($site, $cat, $location, $object, $user)
{
  global $eventsystem, $mgmt_config;
  
  // hide the event used in your action (1) otherwise execute event (0)
  $eventsystem['hide'] = 0; 
  
  // insert your program code here  
  
  // return true if successful
  return true;
}

// -------------------------------------------- on unlock object POST event ----------------------------------------------
function onunlockobject_post ($site, $cat, $location, $object, $user)
{
  global $eventsystem, $mgmt_config;
  
  // hide the event used in your action (1) otherwise execute event (0)
  $eventsystem['hide'] = 0; 
  
  // insert your program code here  
  
  // return true if successful
  return true;
}

// -------------------------------------------- on publish object PRE event ---------------------------------------------
function onpublishobject_pre ($site, $cat, $location, $object, $container_name, $container_content, $template_name, $template_content, $rendered_content, $user)
{
  global $eventsystem, $mgmt_config;
  
  // hide the event used in your action (1) otherwise execute event (0)
  $eventsystem['hide'] = 0; 

  // insert your program code here  
    
  // return true if successful
  return true;
}
 
// -------------------------------------------- on publish object POST event ---------------------------------------------
function onpublishobject_post ($site, $cat, $location, $object, $container_name, $container_content, $template_name, $template_content, $rendered_content, $user)
{
  global $eventsystem, $mgmt_config;
  
  // hide the event used in your action (1) otherwise execute event (0)
  $eventsystem['hide'] = 0; 

  // insert your program code here  
    
  // return true if successful
  return true;
}

// ------------------------------------------ on unpublish object PRE event ---------------------------------------------
function onunpublishobject_pre ($site, $cat, $location, $object, $user)
{
  global $eventsystem, $mgmt_config;
  
  // hide the event used in your action (1) otherwise execute event (0)
  $eventsystem['hide'] = 0; 
  
  // insert your program code here  
  
  // return true if successful
  return true;
}

// ------------------------------------------ on unpublish object POST event ---------------------------------------------
function onunpublishobject_post ($site, $cat, $location, $object, $user)
{
  global $eventsystem, $mgmt_config;
  
  // hide the event used in your action (1) otherwise execute event (0)
  $eventsystem['hide'] = 0; 
  
  // insert your program code here  
  
  // return true if successful
  return true;
}

// ------------------------------------------ on create publication PRE event ---------------------------------------------
// description of the input parameters:
// $site ... selected publication
// $user ... user which started the action
function oncreatepublication_pre ($site, $user)
{
  global $eventsystem, $mgmt_config;
  
  // hide the event used in your action (1) otherwise execute event (0)
  $eventsystem['hide'] = 0; 
  
  // insert your program code here  
  
  // return true if successful
  return true;
}

// ------------------------------------------ on create publication POST event ---------------------------------------------
// description of the input parameters:
// $site ... selected publication
// $user ... user which started the action
function oncreatepublication_post ($site, $user)
{
  global $eventsystem, $mgmt_config;
  
  // hide the event used in your action (1) otherwise execute event (0)
  $eventsystem['hide'] = 0; 
  
  // insert your program code here  
  
  // return true if successful
  return true;
}

// ------------------------------------------ on save publication PRE event ---------------------------------------------
// description of the input parameters:
// $config_mgmt ... configuration of the management system (PHP-file)
// $config_publ_ini ... configuration of the publication system (INI-file)
// $config_publ_prop ... configuration of the publication system (Properties-file)
function onsavepublication_pre ($site, $config_mgmt, $config_publ_ini, $config_publ_prop, $user)
{
  global $eventsystem, $mgmt_config;
  
  // hide the event used in your action (1) otherwise execute event (0)
  $eventsystem['hide'] = 0; 
  
  // insert your program code here  
  
  // return true if successful
  return true;
}

// ------------------------------------------ on save publication POST event ---------------------------------------------
// description of the input parameters:
// $site ... selected publication
// $config_mgmt ... configuration of the management system (PHP-file)
// $config_publ_ini ... configuration of the publication system (INI-file)
// $config_publ_prop ... configuration of the publication system (Properties-file)
// $user ... user which started the action
function onsavepublication_post ($site, $config_mgmt, $config_publ_ini, $config_publ_prop, $user)
{
  global $eventsystem, $mgmt_config;
  
  // hide the event used in your action (1) otherwise execute event (0)
  $eventsystem['hide'] = 0; 
  
  // insert your program code here  
  
  // return true if successful
  return true;
}

// ------------------------------------------ on delete publication PRE event ---------------------------------------------
// description of the input parameters:
// $site ... selected publication
// $user ... user which started the action
function ondeletepublication_pre ($site, $user)
{
  global $eventsystem, $mgmt_config;
  
  // hide the event used in your action (1) otherwise execute event (0)
  $eventsystem['hide'] = 0; 
  
  // insert your program code here  
  
  // return true if successful
  return true;
}

// ------------------------------------------ on delete publication POST event ---------------------------------------------
// description of the input parameters:
// $site ... selected publication
// $user ... user which started the action
function ondeletepublication_post ($site, $user)
{
  global $eventsystem, $mgmt_config;
  
  // hide the event used in your action (1) otherwise execute event (0)
  $eventsystem['hide'] = 0; 
  
  // insert your program code here  
  
  // return true if successful
  return true;
}

// ------------------------------------------ on create user PRE event ---------------------------------------------
// description of the input parameters:
// $login ... login/user name of the selected user 
// $user ... user which started the action
function oncreateuser_pre ($login, $user)
{
  global $eventsystem, $mgmt_config;
  
  // hide the event used in your action (1) otherwise execute event (0)
  $eventsystem['hide'] = 0; 
  
  // insert your program code here  
  
  // return true if successful
  return true;
}

// ------------------------------------------ on create user POST event ---------------------------------------------
// description of the input parameters:
// $login ... login/user name of the selected user 
// $user ... user which started the action
function oncreateuser_post ($login, $user)
{
  global $eventsystem, $mgmt_config;
  
  // hide the event used in your action (1) otherwise execute event (0)
  $eventsystem['hide'] = 0; 
  
  // insert your program code here  
  
  // return true if successful
  return true;
}


// ------------------------------------------ on save user PRE event ---------------------------------------------
// description of the input parameters:
// $login ... login/user name of the selected user 
// $usercontent ... XML data of the user
// $user ... user which started the action
function onsaveuser_pre ($login, $usercontent, $user)
{
  global $eventsystem, $mgmt_config;
  
  // hide the event used in your action (1) otherwise execute event (0)
  $eventsystem['hide'] = 0; 
  
  // insert your program code here  
  
  // return true if successful
  return true;
}

// ------------------------------------------ on save user POST event ---------------------------------------------
// description of the input parameters:
// $login ... login/user name of the selected user 
// $usercontent ... XML data of the user
// $user ... user which started the action
function onsaveuser_post ($login, $usercontent, $user)
{
  global $eventsystem, $mgmt_config;
  
  // hide the event used in your action (1) otherwise execute event (0)
  $eventsystem['hide'] = 0; 
  
  // insert your program code here  
  
  // return true if successful
  return true;
}

// ------------------------------------------ on delete user PRE event ---------------------------------------------
// description of the input parameters:
// $login ... login/user name of the selected user 
// $user ... user which started the action
function ondeleteuser_pre ($login, $user)
{
  global $eventsystem, $mgmt_config;
  
  // hide the event used in your action (1) otherwise execute event (0)
  $eventsystem['hide'] = 0; 
  
  // insert your program code here  
  
  // return true if successful
  return true;
}

// ------------------------------------------ on delete user POST event ---------------------------------------------
// description of the input parameters:
// $login ... login/user name of the selected user 
// $user ... user which started the action
function ondeleteuser_post ($login, $user)
{
  global $eventsystem, $mgmt_config;
  
  // hide the event used in your action (1) otherwise execute event (0)
  $eventsystem['hide'] = 0; 
  
  // insert your program code here  
  
  // return true if successful
  return true;
}

// ------------------------------------------ on create group PRE event ---------------------------------------------
// description of the input parameters:
// $groupname ... login/user name of the selected user 
// $user ... user which started the action
function oncreategroup_pre ($groupname, $user)
{
  global $eventsystem, $mgmt_config;
  
  // hide the event used in your action (1) otherwise execute event (0)
  $eventsystem['hide'] = 0; 
  
  // insert your program code here  
  
  // return true if successful
  return true;
}

// ------------------------------------------ on create group POST event ---------------------------------------------
// description of the input parameters:
// $groupname ... login/user name of the selected user 
// $user ... user which started the action
function oncreategroup_post ($groupname, $user)
{
  global $eventsystem, $mgmt_config;
  
  // hide the event used in your action (1) otherwise execute event (0)
  $eventsystem['hide'] = 0; 
  
  // insert your program code here  
  
  // return true if successful
  return true;
}


// ------------------------------------------ on save group PRE event ---------------------------------------------
// description of the input parameters:
// $groupname ... login/user name of the selected user 
// $groupcontent ... XML data of the user
// $user ... user which started the action
function onsavegroup_pre ($groupname, $groupcontent, $user)
{
  global $eventsystem, $mgmt_config;
  
  // hide the event used in your action (1) otherwise execute event (0)
  $eventsystem['hide'] = 0; 
  
  // insert your program code here  
  
  // return true if successful
  return true;
}

// ------------------------------------------ on save group POST event ---------------------------------------------
// description of the input parameters:
// $groupname ... login/user name of the selected user 
// $groupcontent ... XML data of the user
// $user ... user which started the action
function onsavegroup_post ($groupname, $groupcontent, $user)
{
  global $eventsystem, $mgmt_config;
  
  // hide the event used in your action (1) otherwise execute event (0)
  $eventsystem['hide'] = 0; 
  
  // insert your program code here  
  
  // return true if successful
  return true;
}

// ------------------------------------------ on delete group PRE event ---------------------------------------------
// description of the input parameters:
// $groupname ... login/user name of the selected user 
// $user ... user which started the action
function ondeletegroup_pre ($groupname, $user)
{
  global $eventsystem, $mgmt_config;
  
  // hide the event used in your action (1) otherwise execute event (0)
  $eventsystem['hide'] = 0; 
  
  // insert your program code here  
  
  // return true if successful
  return true;
}

// ------------------------------------------ on delete group POST event ---------------------------------------------
// description of the input parameters:
// $groupname ... login/user name of the selected user 
// $user ... user which started the action
function ondeletegroup_post ($groupname, $user)
{
  global $eventsystem, $mgmt_config;
  
  // hide the event used in your action (1) otherwise execute event (0)
  $eventsystem['hide'] = 0; 
  
  // insert your program code here  
  
  // return true if successful
  return true;
}
?>
