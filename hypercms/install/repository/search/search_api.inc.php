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
  
  if (is_file ($config['indexfile']))
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

// ------------------------ searchindex (old version) ------------------------
// input: query as string, start position as string, exclude url as string, language as string, charset as string, query attributes as array
// output: echo of search result and true/false

function searchindex ($query, $start, $exclude_url="", $lang="en", $charset="UTF-8", $query_attribute=null)
{
  return searchinindex ($query, $start, $exclude_url, "", "html", $lang, $charset, $query_attribute);
}

// ------------------------ searchinindex (new version) ------------------------
// input: query as string, start position as string, exclude url as string, include url as string, result-type [html,array], language as string, charset as string, query attributes as array
// output: echo of search result and true/false

function searchinindex ($query, $start, $exclude_url="", $include_url="", $result_type="html", $lang="en", $charset="UTF-8", $query_attribute=null)
{
  global $config, $text, $start, $end, $max, $query;
  
  if ($query != "" && is_string ($query) && strlen ($query) < 800)
  {
    $data = loadindex ();

    if ($lang == "" || !is_string ($lang)) $lang = "en";
    else $lang = htmlspecialchars (strtolower ($lang), ENT_QUOTES | ENT_HTML401, $charset);

    // results per page
    if (empty ($config['results']) || intval ($config['results']) < 1) $config['results'] = 10;
    // max result pages
    if (empty ($config['maxpages']) || intval ($config['maxpages']) < 1) $config['maxpages'] = 20;
    // save search history
    if (!empty ($config['search_log'])) writehistory ($query);

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

            $query_check = true;

            // query attributes is given (all attribute checks must be successful!)
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
          }

          if (!empty ($query_check) && ($exclude_url == "" || (is_string ($exclude_url) && substr_count ($url, $exclude_url) == 0)) && ($include_url == "" || (is_string ($include_url) && substr_count ($url, $include_url) > 0)))
          {
            if (strlen ($content) > 0 && strlen ($content) < 90000)
            {
              $hits = preg_match_all ($hquery, $url." ".$content, $matches, PREG_OFFSET_CAPTURE);
            }
            // split content into pieces
            elseif (strlen ($content) > 0)
            {
              $slice = "init";
              $slice_count = 0;
              $hits = 0;

              while (strlen ($slice) > 1)
              {
                if (strlen ($content) > (($slice_count+1)*90000)) $slice = @substr ($url." ".$content, ($slice_count*90000), @strpos ($content, " ", (($slice_count+1)*90000)));
                else $slice = @substr ($content, ($slice_count*90000));

                $hits_new = preg_match_all ($hquery, $slice, $slice_matches, PREG_OFFSET_CAPTURE);
                if ($hits_new > 0) $matches = $slice_matches;
                $hits = $hits + $hits_new;
                $slice_count++;
              }
            }
            else $hits = 0;

            if ($hits > 0)
            {
              $hitpos = $matches[0][0][1];
              //var_dump($matches);

              if (empty ($config['maxextract']) || intval ($config['maxextract']) < 1) $config['maxextract'] = 100;
              else $config['maxextract'] = intval ($config['maxextract']);

              if ($hitpos - $config['maxextract'] < 0) $offset = 0;
              else $offset = $hitpos - $config['maxextract'];

              $startpos = strpos (" ".$content." ", " ", $offset);

              if ($hitpos + strlen($query) + $config['maxextract'] > strlen($content)) $offset = strlen ($content);
              else $offset = $hitpos + strlen ($query) + $config['maxextract'];

              $endpos = strpos (" ".$content." ", " ", $offset);

              $extract = "...".substr ($content, $startpos, $endpos - $startpos)."...";
              $extract = str_ireplace ($matches[0][0][0], "<strong>".$matches[0][0][0]."</strong>", $extract);

              $result['hits'][$i] = $hits;
              $result['date'][$i] = $date;
              $result['url'][$i] = $url;

              if ($title == "")
              {
                if (strlen ($content) > 40) $offset = 40;
                else $offset = strlen ($content);

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

        if ($result_type == "html")
        {
          echo "<div class='".(!empty ($config['css_div']) ? $config['css_div'] : "")."'>\n";
          echo "<span class='".(!empty ($config['css_headline']) ? $config['css_headline'] : "")."'>".insertvars ($text[1][$lang])."</span><br /><br />\n";
        }

        for ($i=1; $i<=$end; $i++)
        {
          if ($i>=$start && $i<=$end)
          {
            $id = key ($result['hits']);

            $title = $result['title'][$id];

            if (strlen ($result['url'][$id]) > 70) $url_short = substr ($result['url'][$id], 0, 70)."...";
            else $url_short = $result['url'][$id];

            $url_ext = strtolower (strrchr ($result['url'][$id], "."));

            if (!empty ($config['icon_pdf']) && $url_ext == ".pdf")
            {
              $icon = "<span title='Icon'><img src=\"".$config['icon_pdf']."\" style=\"height:14px; border:0;\" align=\"absmiddle\" /></span>";
            }
            else $icon = "";

            if ($url_ext == ".pdf")
            {
              $linktarget = "target=_blank";
            }
            else $linktarget = "";

            if ($result_type == "html")
            {
              echo "<div class='".$config['css_result']."'><span title='Hits'><strong>".$i.".</strong></span>&nbsp;".$icon."&nbsp;<span title='Title'><a href='".$result['url'][$id]."' class='".$config['css_title']."' ".$linktarget.">".$title."</a></span> <span title='Hits'>[".$result['hits'][$id]." ".$text[0][$lang]."]</span><br />\n";
              if (!empty ($config['showdescription'])) echo "<span title='Description'>".$result['description'][$id]."</span><br />\n";
              if (!empty ($config['showextract'])) echo "<span title='Extract'>".$result['extract'][$id]."</span><br />\n";
              if (!empty ($config['showurl'])) echo "<span title='URL' class='".$config['css_url']."' title='".$result['url'][$id]."'>".$url_short."</span>&nbsp;-&nbsp;".$result['date'][$id]."<br />\n";
              echo "</div><br />\n";
            }
          }
          
          next ($result['hits']);
        }
        
        if ($result_type == "html") echo "<br />\n";
    
        if ($resultpages > 1)
        {
          if ($result_type == "html") echo "<span align='center'>".$text[2][$lang].": ";

         $exclude_url = htmlspecialchars ($exclude_url, ENT_QUOTES | ENT_HTML401, $charset);
         $charset = htmlspecialchars ($charset, ENT_QUOTES | ENT_HTML401, $charset);

          for ($i=1; $i<=$resultpages; $i++)
          {
            $startpos = ($config['results'] * $i) + 1 - $config['results'];
            $startpos = htmlspecialchars ($startpos, ENT_QUOTES | ENT_HTML401, $charset);
   
            if ($i != $currentpage) $footer = "<a href='".$_SERVER['PHP_SELF']."?query=".$query."&start=".$startpos."&language=".$lang."&exclude_url=".$exclude_url."&include_url=".$include_url."&charset=".$charset."'>".$i."</a>&nbsp;";
            else $footer = "<strong>".$i."</strong>&nbsp;";

            if ($result_type == "html") echo $footer;
            else $result['footer'] = $footer;
          }
        } 

        if ($result_type == "html") echo "</div>\n";
    
        if ($result_type == "html") return true;
        else return $result;
      }
      else
      {
        if ($result_type == "html") echo "<span class='".(!empty ($config['css_headline']) ? $config['css_headline'] : "")."'>".insertvars ($text[3][$lang])."</span><br />\n";

        return true;      
      } 
    }
    else
    {
      if ($result_type == "html") echo "<span class='".(!empty ($config['css_headline']) ? $config['css_headline'] : "")."'>".$text[4][$lang]."</span><br />\n";
  
      return false;
    }
  }
  else
  {
    if ($result_type == "html") echo "<span class='".(!empty ($config['css_headline']) ? $config['css_headline'] : "")."'>".$text[5][$lang]."</span><br />\n";

    return false;
  }
}

// ------------------------ cleantext ------------------------
// input: content as string, charset as string
// output: cleaned content as string/false

function cleantext ($content, $charset="UTF-8")
{
  if ($content != "")
  {
    $content = strip_tags ($content);
    if ($charset != "") $content = html_entity_decode ($content, ENT_NOQUOTES | ENT_HTML401, $charset);
    $content = str_replace (array(".....", "....", "...", ".."), ".", $content);
    $content = str_replace (array("_____", "____", "___", "__"), "_", $content);
    $content = preg_replace ('<!--(.*?)-->', "", $content);
    $content = str_replace ("|", "&#124;", $content);
    $content = str_replace (array("\"", "'", "(", ")", "{", "}", "[", "]", ".", ",", ";", "_", "\t", "\r\n", "\r", "\n"), " ", $content);
    $content = preg_replace ('/\s+/', " ", $content);
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
  
    $newtitle = cleantext ($newtitle, $charset);  
    $newdescription = cleantext ($newdescription, $charset);  
    $newcontent = cleantext ($newcontent, $charset);

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
            
            $query_check = true;

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
  
            if (!empty ($query_check) && $newurl == $url)
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
 
        if ($textnode != false && is_array ($textnode)) 
        {
          $i = 0;

          foreach ($textnode as $temp)
          {
            if ($temp != "")
            {
              $textcontent = getcontent ($temp, "<textcontent>", true);

              if (!empty ($textcontent[0]))
              {
                $contentslices[$i] = $textcontent[0];
                $i++;
              }
            }
          }
        }
      } 
    }
    else $contentslices = getcontent ($container_content, "<textcontent>", true);  
      
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
    
        if ($compnode != false && is_array ($compnode))
        {
          $i = 0;

          foreach ($compnode as $temp)
          {
            if ($temp != "")
            {
              $componentfiles = getcontent ($temp, "<componentfiles>");

              if (!empty ($componentfiles[0]))
              {
                $compfiles[$i] = $componentfiles[0];
                $i++;
              }
            }
          }
        }
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

    // escape "|" since it is used as seperator in the search index
    return $content;
  }
  else return "";
}

// ----------------------------------------- writehistory ------------------------------------------
// input: search expression
// output: true / false on error

function writehistory ($expression)
{
  global $config;
         
  if ($expression != "")
  {
    // replace newlines with tab space
    $expression= str_replace ("\n\r", "\t", $expression);
    $expression= str_replace ("\r\n", "\t", $expression);
    $expression= str_replace ("\r", "\t", $expression);
    $expression= str_replace ("\n", "\t", $expression);

    // client IP
    if (!isset($_SERVER['HTTP_X_FORWARDED_FOR'])) $client_ip = $_SERVER['REMOTE_ADDR'];
    else $client_ip = $_SERVER['HTTP_X_FORWARDED_FOR'];

    // record
    $record = date ("Y-m-d H:i", time())."|".$client_ip."|".$expression."\n";
  
    if (is_file ($config['search_log']))
    { 
      return file_put_contents ($config['search_log'], $record, FILE_APPEND | LOCK_EX);
    }
    else
    {
      return file_put_contents ($config['search_log'], $record);
    }
  }  
  else return false;
}

// ----------------------------------------- readhistory ------------------------------------------
// input: %
// output: array holding all expressions of the search history / false on error

function readhistory ()
{
  global $config;

  if (is_file ($config['search_log']))
  {
    // load search log
    $data = file ($config['search_log']);
  
    if (is_array ($data) && sizeof ($data) > 0)
    {
      $keywords = array();
      
      foreach ($data as $record)
      {
        if (substr_count ($record, "|") > 0)
        {
          list ($date, $ip, $keyword_add) = explode ("|", $record);
    
          $keywords[] = "'".str_replace (array("\\", "'"), array("", "\\'"), trim ($keyword_add))."'";
        }
      }
      
      // only unique expressions
      $keywords = array_unique ($keywords);
      
      return $keywords;
    }
    else return false;
  }
  else return false;
}
?>