<?php
/*
 * This file is part of
 * hyper Content & Digital Management Server - http://www.hypercms.com
 * Copyright (c) by hyper CMS Content Management Solutions GmbH
 *
 * You should have received a copy of the license (license.txt) along with hyper Content & Digital Management Server
 */

// ========================================= PLUGIN FUNCTIONS ============================================

// --------------------------------------- plugin_getdefaultconf -------------------------------------------
// function: plugin_getdefaultconf()
// input: %
// output: default value as array

function plugin_getdefaultconf ()
{
  $return = array();
  // Per default all plugins are inactive
  $return['active'] = false;

  return $return;
}

// --------------------------------------- plugin_readmenu -------------------------------------------
// function: plugin_readmenu()
// input: plugin xml [string], plugin directory [string]
// output: menu point array used by navigator

// description:
// Reads Menupoints and menugroups from the xml data.
// Be carefull with nesting, function getcontent is used and you can't nest groups inside of groups as a subpoint!
// Input parameter $pluginFolder contains the directory of the plugin.
// Returns an Array containing every group and menupoint with its configuration.

function plugin_readmenu ($xml, $pluginFolder)
{
  global $mgmt_config;

  $return = array();

  $groups = getcontent ($xml, "<group>");

  if (!empty ($groups) && is_array ($groups))
  {
    foreach ($groups as $group)
    {
      $returnvalue = array();

      // reading the name of the menugroup
      $tmp = getcontent ($group, '<name>');

      // stop parsing if there is no name
      if (!is_array ($tmp) || empty ($tmp[0])) continue;

      $returnvalue['name'] = trim ($tmp[0]);

      // reading the icon of the menugroup
      $tmp = getcontent ($group, '<icon>');

      // stop parsing if there is no icon
      if (!is_array ($tmp) || empty ($tmp[0])) continue;

      $returnvalue['icon'] = $mgmt_config['url_path_plugin'].$pluginFolder.'/'.trim ($tmp[0]);

      // reading points in this menugroup
      $tmp = getcontent ($group, "<subpoints>");

      // reading subpoints if there are no subpoints
      if (is_array ($tmp) && !empty ($tmp[0]))
      {
        $returnvalue['subpoints'] = plugin_readmenu (trim ($tmp[0]), $pluginFolder);
      }
      else continue;

      // adding it to the global data
      $return[] = $returnvalue;
    }

    // delete the group tags so their point tags don't interfere with the rest of the code
    $xml = deletecontent ($xml, "<group>", "", "");
  }

  $points = getcontent ($xml, "<point>");

  if (!empty ($points) && is_array ($points))
  {
    // Run through all found points
    foreach ($points as $point) 
    {
      $returnvalue = array();

      // reading the name of the menupoint
      $tmp = getcontent ($point, '<name>');

      // stop parsing if there is no name
      if (!is_array ($tmp) || empty ($tmp[0])) continue;

      $returnvalue['name'] = trim ($tmp[0]);

      // reading the icon of the menupoint
      $tmp = getcontent ($point, '<icon>');

      // stop parsing if there is no icon
      if (!is_array ($tmp) || empty ($tmp[0])) continue;

      $returnvalue['icon'] = $mgmt_config['url_path_plugin'].$pluginFolder.'/'.trim ($tmp[0]);
 
      // Reading the page of the menupoint
      $tmp = getcontent ($point, '<page>');

      // stop parsing if there is no page
      if (!is_array ($tmp) || empty ($tmp[0])) continue;

      $returnvalue['page'] = trim ($tmp[0]);

      // reading the control of the menupoint
      $tmp = getcontent ($point, '<control>');

      if (is_array ($tmp) && !empty ($tmp[0]))
      {
        $returnvalue['control'] = trim ($tmp[0]);
      }

      $return[] = $returnvalue;
    }
  }

  return $return;
}

// --------------------------------------- plugin_parse -------------------------------------------
// function: plugin_parse()
// input: mgmt_plugin [array] (optional)
// output: mgmt_plugin as array

// description:
// Reads the plugin configurations from the file system.
// Checks the folder defined in mgmt_config and searched for plugins and their configurations files.
// It either takes needed values from the configuration, from the $oldData or defaultConfiguration.

function plugin_parse ($oldData=array()) 
{
  global $mgmt_config;

  $scandir = scandir ($mgmt_config['abs_path_plugin']);

  if ($scandir)
  {
    // We must have an array here
    if (!is_array ($oldData)) $oldData = array();

    $return = array();

    foreach ($scandir as $file) 
    {
      // We only parse plugin.xml if present
      if ($file != '.' && $file != '..' && is_dir ($mgmt_config['abs_path_plugin'].$file) && is_file ($mgmt_config['abs_path_plugin'].$file.'/plugin.xml')) 
      {
        $tmp = getcontent (loadfile ($mgmt_config['abs_path_plugin'].$file.'/', 'plugin.xml'), '<plugin>');
        $pluginData = $tmp[0];

        // ---------------------------------------------------
        // Reading the definition containing basic definitions for this plugin
        // All basic informations are required so that the plugin is loaded
        $tmp = getcontent ($pluginData, '<definition>');

        // stop parsing if there is no definition
        if (!is_array($tmp) || empty ($tmp[0])) continue;

        $definition = trim ($tmp[0]);

        // reading the name of the plugin
        $tmp = getcontent ($definition, '<name>');

        // stop parsing if there is no name
        if (!is_array($tmp) || empty ($tmp[0])) continue;

        $name = trim ($tmp[0]);

        // reading the author of the plugin
        $tmp = getcontent ($definition, '<author>');

        // stop parsing if there is no name
        if (!is_array ($tmp) || empty ($tmp[0]))  continue;

        $author = trim ($tmp[0]);

        // reading the version of the plugin
        $tmp = getcontent ($definition, '<version>');

        // stop parsing if there is no name
        if (!is_array ($tmp) || empty ($tmp[0])) continue;

        $version = trim ($tmp[0]);

        // reading the description of the plugin
        $tmp = getcontent ($definition, '<description>');

        // stop parsing if there is no name
        if (!is_array ($tmp) || empty ($tmp[0])) continue;

        $description = trim ($tmp[0]);

        // clean
        unset ($definition);

        // ---------------------------------------------------
        // reading the menus for this plugin
        $tmp = getcontent ($pluginData, "<menus>");

        $mainmenu = array();
        $publicationmenu = array();
        $contextmenu = array();

        if (is_array ($tmp) && !empty ($tmp[0]))
        {
          $menu = trim ($tmp[0]);

          // reading the main menu for this plugin
          $tmp = getcontent ($menu, "<main>");

          if (!empty ($tmp[0]))
          {
            $mainmenu = plugin_readmenu (trim ($tmp[0]), $file);
          }

          // reading the publication menu for this plugin
          $tmp = getcontent ($menu, "<publication>");

          if (!empty ($tmp[0]))
          {
            $publicationmenu = plugin_readmenu (trim ($tmp[0]), $file);
          }

          // reading the publication menu for this plugin
          $tmp = getcontent ($menu, "<context>");

          if (!empty ($tmp[0]))
          {
            $contextmenu = plugin_readmenu (trim ($tmp[0]), $file);
          }
        }

        // default plugin configuration when no old data is present
        if (!array_key_exists ($file, $oldData)) $oldData[$file] = plugin_getdefaultconf ();

        $return[$file]['name'] = $name;
        $return[$file]['author'] = $author;
        $return[$file]['version'] = $version;
        $return[$file]['description'] = $description;
        
        // active is always taken from old data
        $return[$file]['active'] = $oldData[$file]['active'];
        $return[$file]['folder'] = $mgmt_config['abs_path_plugin'].$file."/";
        $return[$file]['menu'] = array();

        // navigation tree
        if (!empty ($mainmenu)) $return[$file]['menu']['main'] = $mainmenu;
        if (!empty ($publicationmenu)) $return[$file]['menu']['publication'] = $publicationmenu;

        // context menu entry
        if (!empty ($contextmenu)) $return[$file]['menu']['context'] = $contextmenu;
      }
    }

    ksort ($return);
    return $return;
  }
  else return false;
}

// --------------------------------------- plugin_generatedefinition -------------------------------------------
// function: plugin_generatedefinition()
// input: name of array holding the plugin definitions [string], configuration containing the values and keys [array]
// output: plugin array / false on error

// description:
// Generates the Array definition used in php for $array with the name of $arrayName.
// Run recursively through the array and supports boolean, numeric and string types for the key and value.

function plugin_generatedefinition ($arrayName, $array) 
{
  global $mgmt_config;

  if (!empty ($arrayName) && is_array ($array))
  {
    $return = '$'.$arrayName." = array();\n";

    foreach ($array as $key => $value) 
    {
      // ignore each key that is not a string, number or boolean
      if (is_string ($key)) $key = "'".$key."'";
      elseif (!is_numeric ($key) && !is_bool ($key)) continue;
 
      if (is_array ($value))
      {
        $return .= plugin_generatedefinition ($arrayName.'['.$key.']', $value);
      }
      else
      {
        if (is_string ($value)) $value = "'".$value."'";
        elseif (is_bool ($value)) $value = ($value ? 'true' : 'false');
        elseif (!is_numeric ($value)) continue;
 
        $return .= '$'.$arrayName.'['.$key.'] = '.$value.";\n";
      }
    }

    return $return;
  }
  else return false;
}

// --------------------------------------- plugin_saveconfig -------------------------------------------
// function: plugin_saveconfig()
// input: configuration [array]
// output: true / false on error

// description:
// Saves the plugin configuration $configuration into the configuration file.
// The configuration file is located in the data/config directory and is named plugin.global.php.

function plugin_saveconfig ($configuration)
{
  global $mgmt_config;

  $file = "plugin.global.php";

  $save = "<?php\n";
  $save .= plugin_generatedefinition ('mgmt_plugin', $configuration);
  $save .= "?>";

  // save file
  $result = savefile ($mgmt_config['abs_path_data']."config/", $file, $save);

  // empty file cache
  opcache_invalidate ($mgmt_config['abs_path_data']."config/".$file);

  return $result;
}

// --------------------------------------- plugin_generatelink -------------------------------------------
// function: plugin_generatelink()
// input: plugin name [string], plugin page (relative reference to the plugins main page) [string], control (relative reference to the plugins control page) [string], additional GET parameters [string]
// output: plugin link

// description:
// Generates a link to be used when linking to other pages inside of a plugin

function plugin_generatelink ($plugin, $page, $control=false, $additionalGetParameters=false)
{
  global $mgmt_config;

  return $mgmt_config['url_path_cms'].'plugin_showpage.php?plugin='.url_encode($plugin).'&page='.url_encode($page).($control ? '&control='.url_encode($control) : '').($additionalGetParameters ? '&'.$additionalGetParameters : '');
}
?>