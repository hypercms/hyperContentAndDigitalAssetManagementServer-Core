<?xml version="1.0" encoding="utf-8" ?>
<template>
<name>Navigation</name>
<user>admin</user>
<category>inc</category>
<extension></extension>
<application></application>
<content><![CDATA[<!-- Navigation -->
[hyperCMS:scriptbegin

global $mgmt_config;

// definitions
$root_doc = "%abs_page%/";
$root_url = "%url_page%/";
$class_active = "current-menu-item";
$tag = "<li class=\"%css%\"><a href=\"%link%\" target=\"_self\">%title%</a>%sub%</li>";

// check if directory/folder is not empty
function folder_notempty ($dir)
{
  if (!is_readable ($dir)) return false; 
  $handle = opendir ($dir);

  while (false !== ($entry = readdir ($handle)))
  {
    if ($entry != "." && $entry != ".." && substr ($entry, -4) != ".off") return true;
  }

  return false;
}

// readnavigation reads the content from the container
function readnavigation ($site, $docroot, $object, $user)
{
  if ($site != "" && $docroot != "" && $object != "" && $user != "")
  {
    $xmldata = getobjectcontainer ($site, $docroot, $object, $user);
  
    if ($xmldata != false)
    {
      // text_IDs to read
      $hidenode = selectcontent ($xmldata, "<text>", "<text_id>", "NavigationHide");
      $textnode = selectcontent ($xmldata, "<text>", "<text_id>", "Title");
      $sortordernode = selectcontent ($xmldata, "<text>", "<text_id>", "NavigationSortOrder");

      // show navigation item
      $hideitem = Null;

      if ($hidenode != false)
      {
        $hide = getcontent ($hidenode[0], "<textcontent>");
        if (!empty ($hide[0])) $hideitem = $hide[0];
      }

      if (!empty ($hideitem)) $hideitem = true;
      else $hideitem = false;

      // sort order
      if ($sortordernode != false)
      {
        $sortorder = getcontent ($sortordernode[0], "<textcontent>");
        if (!empty ($sortorder[0])) $sortorder_no = $sortorder[0];
        else $sortorder_no = "X";
      }
      else $sortorder_no = "X";

      // navigation item title
      if ($textnode != false)
      {
        $title = getcontent ($textnode[0], "<textcontent>");
        if (!empty ($title[0])) $navtitle = $title[0];
        else $navtitle = $object;
      }
      else $navtitle = $object;

      // result
      $result = array();
      $result['title'] = $navtitle;
      $result['order'] = $sortorder_no;
      $result['hide'] = $hideitem;

      return $result;
    }
    else return false;
  }
  else return false;
}

// createnavigation generates a assoc. array (item => nav-item, sub => array with sub-items)
function createnavigation ($docroot, $urlroot)
{
  global $root_doc, $root_url, $class_active;
  
  // collect navigation data
  $handler = opendir ($docroot);
  
  if ($handler!=false)
  {
    $i = 0;
    $fileitem = array(); 
    $navitem = array();  
    $path_now = "%abs_location%/";
    $file_now = "%object%";

    while ($file = readdir ($handler))
    {
      if ($file != "." && $file != ".." && substr ($file, -4) != ".off") $fileitem[] = $file;
    }

    natcasesort ($fileitem);
    reset ($fileitem);

    foreach ($fileitem as $object)
    {
      // PAGE OBJECT -> standard navigation item
      if (is_file ($docroot.$object) && $object != ".folder")
      {
        $navi = readnavigation ("%publication%", $docroot, $object, "sys");
  
        if ($navi != false && $navi['hide'] == false)
        {
          // navigation display5
          if (substr_count ($path_now.$file_now, $docroot.$object) == 1) $add_css = $class_active;
          else $add_css = ""; 

          $navitem[$navi['order'].'.'.$i]['item'] = $add_css."|".$urlroot.$object."|".$navi['title'];
          $navitem[$navi['order'].'.'.$i]['sub'] = "";

          $i++;
        }
      }
      // FOLDER -> next navigation level
      elseif (is_dir ($docroot.$object) && folder_notempty ($docroot.$object))
      {
        $navi = readnavigation ("%publication%", $docroot, $object, "sys");
  
        if ($navi != false && $navi['hide'] == false)
        {
          if ($navi['order'] == "X") $navi['order'] = $i;

          // create sub navigation
          $subnav = createnavigation ($docroot.$object."/", $urlroot.$object."/");

          if (is_array ($subnav))
          {
            ksort ($subnav, SORT_NUMERIC);
            reset ($subnav);
            $j = 1;

            foreach ($subnav as $key => $value)
            {
              if ($j == 1)
              {
                $navitem[$navi['order'].'.'.$i]['item'] = $value['item'];
                $navitem[$navi['order'].'.'.$i]['sub'] = "";
              }
              else
              {
                $navitem[$navi['order'].'.'.$i]['sub'][$key] = $value;
              }

              $j++;
            }
          }

          $i++;
        }
      }
    }

    closedir ($handler);
  }
  
  if (isset ($navitem) && is_array ($navitem)) return $navitem;
  else return Null;
}


// display navigation array
function displaynavigation ($navigation, $level=1)
{
  global $tag;

  $out = "";
  $sub = "";

  ksort ($navigation, SORT_NUMERIC);
  reset ($navigation);
  
  if ($level == 1) $out .= str_repeat ("\t", $level)."<ul class=\"root\">\n";
  else $out .= str_repeat ("\t", $level)."<ul class=\"sub-menu\">\n";

  foreach ($navigation as $key => $value)
  {
      if (is_array ($value['sub']))
      {
        list ($css, $link, $title) = explode ("|", $value['item']);
        $out .= str_repeat ("\t", $level) . str_replace (array("%css%", "%link%", "%title%"), array($css, $link, $title), $tag);
        $sub = displaynavigation ($value['sub'], ($level+1));

        if ($sub != "") $out = str_replace ("%sub%", $sub, $out);
        else$out = str_replace ("%sub%", "", $out);
      }
      else
      {
        list ($css, $link, $title) = explode ("|", $value['item']);
        $out .= str_repeat ("\t", $level) . str_replace (array("%css%", "%link%", "%title%"), array($css, $link, $title), $tag);
        $out = str_replace ("%sub%", "", $out);
      }
  }

  $out.= '</ul>';

  return $out;
}

$navigation = createnavigation ($root_doc, $root_url);
echo displaynavigation ($navigation);

scriptend]
<!-- Navigation -->]]></content>
</template>