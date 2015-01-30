<?php
// include search config
require ("search_config.inc.php");

// ---------------------- Search Engine API ------------------------

// ------------------------ loadindex ------------------------
// input: %
// output: search index as array/false

function loadindex ()
{
  global $config;
  
  if (file_exists ($config['indexfile']))
  {
    $data = file ($config['indexfile']);
    return $data = file ($config['indexfile']);
  }
  else return false;
}

// ------------------------ writeindex ------------------------
// input: search index data as string
// output: true/false

function writeindex ($data)
{
  global $config;
  
  if ($data != false)
  {    
    $filehandle = @fopen ($config['indexfile'], "w");

    if ($filehandle != false)
    {
      @flock ($filehandle, 2);   
      @fwrite ($filehandle, $data);
      @flock ($filehandle, 3);
      @fclose ($filehandle);
      return true;
    }
    else return false;
  }
  else return false;
}

// ------------------------ appendindex ------------------------
// input: search index data to append as string
// output: true/false

function appendindex ($data)
{
  global $config;

  if ($data != false && $data != "")
  {       
    $filehandle = @fopen ($config['indexfile'], "a");

    if ($filehandle != false)
    {    
      @flock ($filehandle, 2);
      @fwrite ($filehandle, $data);
      @flock ($filehandle, 3);
      @fclose ($filehandle);
      return true;
    }
    else return false;
  }
  else return false;
}

// ------------------------ insertvars ------------------------
// input: text as string
// output: text with variables inserted as string/false

function insertvars ($text)
{
  global $start, $end, $max, $query;

  if ($text!="")
  {
    $text = str_replace ("%query%", $query, $text);
    $text = str_replace ("%start%", $start, $text);
    $text = str_replace ("%end%", $end, $text);
    $text = str_replace ("%max%", $max, $text);
    return $text;
  }
  else return false;
}

// ------------------------ createquerypattern ------------------------
// input: query as string
// output: query pattern using regex as string/false

function createquerypattern ($query)
{
  if ($query != "" && is_string ($query))
  {
    // prepare query pattern
    $hquery = $query;
    $hquery = htmlspecialchars_decode ($hquery, ENT_QUOTES | ENT_HTML401);
    $hquery = preg_quote ($hquery, '#');
    $hquery = trim ($hquery);
  
    // reducing spaces to OR => "|" , except in literal search
    $exploded = explode ('"', $hquery);

    for ($i=0; $i<count($exploded); $i+=2)
    {
      $exploded[$i] = preg_replace ('# +#', "|", $exploded[$i]);
      $arr = explode ('|', $exploded[$i]);

      foreach($arr as $key => &$value)
      {
        if($value == "\*")
        {
          unset ($arr[$key]);
          continue;
        }

        // replacing escaped wildcard \* (on enter *), with '\S+' => then setting wordboundaries according the location of the wildcard
        if (substr_count ($value,"\*") > 0)
        {
          if (substr ($value,0,2) == "\*")
          {
            $value = str_replace ('\*', '\S+', $value).'\b';
          }
          elseif (substr ($value,-2,2) == "\*")
          {
            $value = '\b'.str_replace ('\*', '(.*?)', $value);
          }
          else
          {
            $value = '\b'.str_replace ('\*', '\S+', $value).'\b';
          }
        }
      }

      $exploded[$i] = implode('|', $arr);
    }

    $hquery = implode('"', $exploded);	
    $search = array('#["\'](.*?)["\']#');
    $replace = array('(\b${1}\b)');
    
    $hquery = '#'.preg_replace($search, $replace, $hquery).'#i';
  
    return $hquery;
  }
  else return false;
}

// ------------------------ searchindex ------------------------
// input: query as string, start position as string, exclude url as string, language as string, charset as string, query attributes as array
// output: echo of serach result and true/false

function searchindex ($query, $start, $exclude_url="", $lang="en", $charset="UTF-8", $query_attribute=null)
{
  global $config, $text, $start, $end, $max, $query;
  
  if ($query != "" && is_string ($query) && strlen ($query) < 800)
  {
    $data = loadindex ();
  
    if ($lang == "") $lang = "en";
    else $lang = htmlspecialchars (strtolower ($lang), ENT_QUOTES | ENT_HTML401, $charset);

    if (is_array ($data))
    {
      $hquery = createquerypattern ($query);
      $query = htmlspecialchars ($query, ENT_QUOTES | ENT_HTML401, $charset);
      $start = intval ($start, 10);
    
      $i = 0;
          
      foreach ($data as $page)
      {
        if (strlen ($page) > 3 && $i < ($config['maxpages'] * $config['results']))
        {
          $searchindex_record = explode ("|", trim ($page));

          if (is_array ($searchindex_record) && sizeof ($searchindex_record) >= 5)
          {
            $date = $searchindex_record[0];
            $url = $searchindex_record[1];
            $title = $searchindex_record[2];
            $description = $searchindex_record[3];
            $content = $searchindex_record[4];

            // query attributes if given (all attribute checks must be successful!)
            if (is_array ($query_attribute) && sizeof ($query_attribute) > 0)
            { 
              $qa = 0;

              for ($sa = 5; $sa < sizeof ($searchindex_record); $sa++)
              {
                $attribute[$qa] = $searchindex_record[$sa];

                if ($query_attribute[$qa] != "" && strtolower ($query_attribute[$qa]) == strtolower ($attribute[$qa])) $query_check = true;
                elseif ($query_attribute[$qa] == "") $query_check = true;
                else
                {
                  // failed
                  $query_check = false;
                  break;
                }

                $qa++;
              }
            }
            else $query_check = true;
          }

          if ($query_check && ($exclude_url == "" || (is_string ($exclude_url) && substr_count ($url, $exclude_url) == 0)))
          {
            if (strlen ($content) > 0) $hits = preg_match_all ($hquery, $content, $matches, PREG_OFFSET_CAPTURE);
            else $hits = 0;
  
            if ($hits > 0)
            {
              $hitpos = $matches[0][0][1];
              //var_dump($matches);
              if ($hitpos-100 < 0) $offset = 0;
              else $offset = $hitpos - 100; 
              $startpos = strpos (" ".$content." ", " ", $offset);
    
              if ($hitpos + strlen($query) + 100 > strlen($content)) $offset = strlen ($content);
              else $offset = $hitpos + strlen ($query) + 100;
              $endpos = strpos (" ".$content." ", " ", $offset);
    
              $extract = "...".substr ($content, $startpos, $endpos-$startpos)."...";
              $extract = str_ireplace ($matches[0][0][0], "<strong>".$matches[0][0][0]."</strong>", $extract);
    
              $result['hits'][$i] = $hits;
              $result['date'][$i] = $date;
              $result['url'][$i] = $url;
    
              if ($title == "")
              {
                if (strlen($content) > 40) $offset = 40;
                else  $offset = strlen ($content);
                $title = substr ($content, 0, strpos ($content." ", " ", $offset))."...";
              }
    
              $result['title'][$i] = $title;
              $result['description'][$i] = $description;
              $result['extract'][$i] = $extract;
    
              $i++;
            }
          }
        }
      }

      if ($i > 0)
      {
        reset ($result['hits']);
        arsort ($result['hits']); 
        
        if ($start == "" || $start == 0) $start = 1;  
        $end = $start + $config['results'] - 1;
        $max = sizeof ($result['hits']);
        $resultpages = round ($max / $config['results'] + 0.49);
        $currentpage = round ($start / $config['results'] + 0.49);
     
        if ($end > $max) $end = $max;
        
        echo "<div class='".$config['css_div']."'>\n";
        echo "<span class='".$config['css_headline']."'>".insertvars ($text[1][$lang])."</span><br /><br />\n";
        
        for ($i=1; $i<=$end; $i++)
        {
          if ($i>=$start && $i<=$end)
          {
            $id = key ($result['hits']);
          
            $title = $result['title'][$id];

            if (strlen ($result['url'][$id]) > 70) $url_short = substr ($result['url'][$id], 0, 70)."...";
            else $url_short = $result['url'][$id];

            $url_ext = strtolower (strrchr ($result['url'][$id], "."));

            if ($config['icon_pdf'] != "" && $url_ext == ".pdf")
            {
              $icon = "<span title='Icon'><img src=\"".$config['icon_pdf']."\" border=\"0\" align=\"absmiddle\" /></span>";
            }
            else $icon = "";

            if ($url_ext == ".pdf")
            {
              $linktarget = "target=_blank";
            }
            else $linktarget = "";
      
            echo "<span title='Hits'><strong>".$i.".</strong></span>&nbsp;".$icon."&nbsp;<span title='Title'><a href='".$result['url'][$id]."' class='".$config['css_title']."' ".$linktarget.">".$title."</a></span> <span title='Hits'>[".$result['hits'][$id]." ".$text[0][$lang]."]</span><br />\n";
            if ($config['showdescription'] == true) echo "<span title='Description'>".$result['description'][$id]."</span><br />\n";
            if ($config['showextract'] == true) echo "<span title='Extract'>".$result['extract'][$id]."</span><br />\n";
            if ($config['showurl'] == true) echo "<span title='URL' class='".$config['css_url']."' title='".$result['url'][$id]."'>".$url_short."</span>&nbsp;-&nbsp;".$result['date'][$id]."<br />\n";
            echo "<br />\n";
          }
          
          next ($result['hits']);
        }
        
        echo "<br />\n";
    
        if ($resultpages > 1)
        {
          echo "<span align='center'>".$text[2][$lang].": ";

         $exclude_url = htmlspecialchars ($exclude_url, ENT_QUOTES | ENT_HTML401, $charset);
         $charset = htmlspecialchars ($charset, ENT_QUOTES | ENT_HTML401, $charset);

          for ($i=1; $i<=$resultpages; $i++)
          {
            $startpos = ($config['results'] * $i) + 1 - $config['results'];
            $startpos = htmlspecialchars ($startpos, ENT_QUOTES | ENT_HTML401, $charset);
   
            if ($i != $currentpage) echo "<a href='".$_SERVER['PHP_SELF']."?query=".$query."&start=".$startpos."&language=".$lang."&exclude_url=".$exclude_url."&charset=".$charset."'>".$i."</a>&nbsp;";
            else echo "<strong>".$i."</strong>&nbsp;";
          }
        } 

        echo "</div>\n";   
    
        return true;
      }
      else
      {
        echo "<span class='".$config['css_headline']."'>".insertvars ($text[3][$lang])."</span><br />\n";

        return true;      
      } 
    }
    else
    {
      echo "<span class='".$config['css_headline']."'>".$text[4][$lang]."</span><br />\n";
  
      return false;
    }
  }
  else
  {
    echo "<span class='".$config['css_headline']."'>".$text[5][$lang]."</span><br />\n";

    return false;
  }
}

// ------------------------ cleancontent ------------------------
// input: content as string, charset as string
// output: cleaned content as string/false

function cleancontent ($content, $charset="UTF-8")
{
  if ($content != "" && $charset != "")
  {
    $content = strip_tags ($content);
    $content = str_replace ("\r\n", " ", $content);
    $content = str_replace ("\n\r", " ", $content);
    $content = str_replace ("\n", " ", $content);
    $content = preg_replace ('/\s+/', " ", $content);
    $content = preg_replace ('<!--(.*?)-->', "", $content);	
    $content = html_entity_decode ($content, ENT_NOQUOTES | ENT_HTML401, $charset);
    $content = trim ($content);

    return $content;
  }
  else return false;
}

// ------------------------ createindex ------------------------
// input: url as string, title as string, description as string, content as string, charset as string, attributes as array
// output: true/false

function createindex ($newurl, $newtitle, $newdescription, $newcontent, $charset="UTF-8", $query_attribute=null)
{
  global $config;
 
  if ($newurl!="")
  {
    // check if to exclude
    if (trim ($config['exclude_path']) != "" && substr_count ($newurl, $config['exclude_path']) > 0)
    {     
      return false;
    }

    // load index
    $data = loadindex ();

    $newdata = "";
    $update = false;
  
    $newtitle = cleancontent ($newtitle, $charset);  
    $newdescription = cleancontent ($newdescription, $charset);  
    $newcontent = cleancontent ($newcontent, $charset);

    // attributes string (add additional attributes to search index record, like language, group name, ...)
    if (is_array ($query_attribute)) $add_attributes = "|".implode ("|", $query_attribute);
    else $add_attributes = "";

    if (is_array ($data))
    {
      foreach ($data as $page)
      {
        if (strlen ($page) > 3)
        {
          $searchindex_record = explode ("|", trim ($page));

          if (is_array ($searchindex_record) && sizeof ($searchindex_record) >= 5)
          {
            $date = $searchindex_record[0];
            $url = $searchindex_record[1];
            $title = $searchindex_record[2];
            $description = $searchindex_record[3];
            $content = $searchindex_record[4];

            // check query attributes (all checks must be successful)
            if (is_array ($query_attribute) && sizeof ($query_attribute) > 0)
            {
              $j = 0;

              for ($i = 5; $i < sizeof ($searchindex_record); $i++)
              {
                $attribute[$j] = $searchindex_record[$i];

                if ($query_attribute[$j] != "" && strtolower ($query_attribute[$j]) == strtolower ($attribute[$j])) $query_check = true;
                elseif ($query_attribute[$j] == "") $query_check = true;
                else
                {
                  // failed
                  $query_check = false;
                  break;
                }

                $j++;
              }
            }
            else $query_check = true;
  
            if ($query_check && $newurl == $url)
            {
              if ($newtitle != false || $newdescription != false || $newcontent != false) $newdata .= date ("Y-m-d", time())."|$url|$newtitle|$newdescription|$newcontent".$add_attributes."\r\n";        
              $update = true;
            }
            else 
            {
              $newdata .= $page;
            }
          }
        }
      }
  
      if ($update == false)
      {
        return appendindex (date ("Y-m-d", time())."|$newurl|$newtitle|$newdescription|$newcontent".$add_attributes."\r\n");
      }
      elseif ($newdata != "" && $update == true)
      {
        return writeindex ($newdata);
      }
      else return false;
    }
    // index will be created the first time
    else
    {
      return writeindex (date ("Y-m-d", time())."|$newurl|$newtitle|$newdescription|$newcontent".$add_attributes."\r\n");
    }
  }
  else return false;
}

// ------------------------ renameindex ------------------------
// input: old url as string, new url as string
// output: true/false

function renameindex ($oldurl, $newurl)
{
  global $config;
  
  if ($oldurl!="" && $newurl!="")
  {
  $data = loadindex ();
    $newdata = "";
    $update = false;
    if (is_array ($data))
    {
      foreach ($data as $page)
      {
        if (strlen ($page) > 3)
        {
          list ($date, $url, $title, $description, $content) = explode ("|", trim ($page));
        
      if ($oldurl == $url)
          {
      // delete from index if contains exclude path
      if ($config['exclude_path'] != "" && substr_count ($newurl, $config['exclude_path']) > 0)
      {
          $update = true;
        continue;
      }
      $newdata .= date ("Y-m-d", time())."|$newurl|$title|$description|$content\r\n";
            $update = true;
          }
          elseif (substr_count ($url, $oldurl) == 1)
          {
        // delete from index if contains exclude path
            if ($config['exclude_path'] != "" && substr_count ($newurl, $config['exclude_path']) > 0)
            {
              $update = true;
        continue;
            }
            $insertUrl = str_replace ($oldurl, $newurl, $url);
            $newdata .= date ("Y-m-d", time())."|$insertUrl|$title|$description|$content\r\n";
            $update = true;
          }
          else $newdata .= $page;
      
        }
      }
  
      if ($newdata!="" && $update==true)
      {
        return writeindex ($newdata);
      }
      else return false;
    }
    else return false;
  }
  else return false;
}

// ------------------------ removeindex ------------------------
// input: url to be removed as string
// output: treu/false

function removeindex ($removeurl)
{
  global $config;

  if ($removeurl!="")
  {
    $data = loadindex ();
    $newdata = "";
    $update = false;
  
    if (is_array ($data))
    {
      foreach ($data as $page)
      {
        if (strlen ($page) > 3)
        {
          list ($date, $url, $title, $description, $content) = explode ("|", trim ($page));
          if ($removeurl!=$url)
      {
      $newdata .= $page;
      }
          else {
      $update = true;
      }
        }
      }
  
      if ($newdata!="" && $update==true)
      {
        return writeindex ($newdata);
      }
      else return false;
    }
    else return true;
  }
  else return true;
}

// ------------------------ collectcontent ------------------------
// input: container as string, condition for text_id as array, condition for component_id as array
// output: content from text nodes and recursively collected content from component nodes as string

function collectcontent ($container_content, $text_id="", $component_id="")
{
  global $mgmt_config;
  
  if ($container_content != "")
  {
    $content = "";
    
    // content from textnodes
    if (is_array ($text_id))
    {
      foreach ($text_id as $id)
      {
        $textnode = selectcontent ($container_content, "<text>", "<text_id>", $id);      
        if ($textnode != false) $contentslices = getcontent ($textnode[0], "<textcontent>");
      } 
    }
    else $contentslices = getcontent ($container_content, "<textcontent>");  
      
    if (isset ($contentslices) && is_array ($contentslices) && sizeof ($contentslices) > 0)
    {
      $content = implode (" ", $contentslices);
    }
    
    // content from componentnodes
    if (is_array ($component_id))
    {
      foreach ($component_id as $id)
      {
        $compnode = selectcontent ($container_content, "<component>", "<component_id>", $id);      
        if ($compnode != false) $compfiles = getcontent ($compnode[0], "<componentfiles>");
      }
    }
    else $compfiles = getcontent ($container_content, "<componentfiles>");
    
    if (isset ($compfiles) && is_array ($compfiles) && sizeof ($compfiles) > 0)
    {
      $compstr = "";
      
      foreach ($compfiles as $str)
      {
        if (substr ($str, strlen($str)-1, 1) == "|") $compstr .= $str;
        else $compstr .= $str."|";
      }

      $compfiles = explode ("|", $compstr);

      foreach ($compfiles as $path)
      {
        if ($path != "")
        {
          $file = basename ($path);
          $dir = dirname ($path)."/";
          $filedata = loadfile (deconvertpath ($dir, "file"), $file);
          $container = getfilename ($filedata, "content");
          $container_id = substr ($container, 0, strpos ($container, ".xml"));
          $container_content = loadfile (getcontentlocation ($container_id, 'abs_path_content'), $container);
          // no conditions of IDs in child container
          $content = $content." ".collectcontent ($container_content, $text_id, $component_id);
        }
      }
    }

    return $content;
  }
  else return "";
}
?>