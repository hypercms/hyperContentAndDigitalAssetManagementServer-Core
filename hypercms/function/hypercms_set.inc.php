<?php
/*
 * This file is part of
 * hyper Content Management Server - http://www.hypercms.com
 * Copyright (c) by hyper CMS Content Management Solutions GmbH
 *
 * You should have received a copy of the License along with hyperCMS.
 */
 
// =========================================== SESSION ==============================================

// ------------------------- setsession -----------------------------
// function: setsession()
// input: temporary hyperCMS variable name or array, value as string or array (optional)
// output: true / false on error

function setsession ($variable, $content="")
{
  if ($variable != "" && !is_array ($variable) && session_id() != "")
  {
    // define variable name (prefix hcms_ is required)
    if (strpos ("_".$variable, "hcms_") == 0) $variable = "hcms_".$variable;
    // set value for session variable
    $_SESSION[$variable] = $content;

    return true;    
  }
  else return false;
}

// ========================================= SAVE CONTENT ============================================

// ------------------------------------------ article ----------------------------------------------
// function: setarticle()
// input: publication name, container (XML), container name, article title array, article status array, article beginn date array, article end date array, user array or string, user name
// output: updated content container (XML), false on error

function setarticle ($site, $contentdata, $contentfile, $arttitle, $artstatus, $artdatefrom, $artdateto, $artuser, $user)
{
  global $mgmt_config;

  if ($contentdata != "" && is_array ($artstatus) && valid_objectname ($user))
  {
    // load xml schema
    $article_schema_xml = chop (loadfile ($mgmt_config['abs_path_cms']."xmlsubschema/", "article.schema.xml.php"));
    
    if (!is_array ($artuser))
    {
      $userbuffer = $artuser;
      $artuser = Null;
    }
    
    reset ($artstatus);
    
    for ($i = 1; $i <= sizeof ($artstatus); $i++)
    {
      // get key (position) of array item
      $artid = key ($artstatus);
      
      if ($artid != "")
      {
        if (!isset ($arttitle[$artid])) $arttitle[$artid] = "";
        if (!isset ($artdatefrom[$artid])) $artdatefrom[$artid] = "";
        if (!isset ($artdateto[$artid])) $artdateto[$artid] = "";
        
        // set array if input parameter is string
        if ($userbuffer != "") $artuser[$artid] = $userbuffer;
  
        // escape special characters for article title (transform all special chararcters into their html/xml equivalents)
        $arttitle[$artid] = str_replace ("&", "&amp;", $arttitle[$artid]);
        $arttitle[$artid] = str_replace ("<", "&lt;", $arttitle[$artid]);
        $arttitle[$artid] = str_replace (">", "&gt;", $arttitle[$artid]);
    
        // set the new content
        $contentdatanew = setcontent ($contentdata, "<article>", "<articletitle>", trim ($arttitle[$artid]), "<article_id>", $artid);
        
        if ($contentdatanew == false)
        {
          $contentdatanew = addcontent ($contentdata, $article_schema_xml, "", "", "", "<articlecollection>", "<article_id>", $artid);
          $contentdatanew = setcontent ($contentdatanew, "<article>", "<articletitle>", trim ($arttitle[$artid]), "<article_id>", $artid);
        }
        
        $contentdatanew = setcontent ($contentdatanew, "<article>", "<articledatefrom>", $artdatefrom[$artid], "<article_id>", $artid);
        $contentdatanew = setcontent ($contentdatanew, "<article>", "<articledateto>", $artdateto[$artid], "<article_id>", $artid);
        $contentdatanew = setcontent ($contentdatanew, "<article>", "<articlestatus>", $artstatus[$artid], "<article_id>", $artid);
        if ($artuser[$artid] != "") $contentdatanew = setcontent ($contentdatanew, "<article>", "<articleuser>", $artuser[$artid], "<article_id>", $artid);
  
        $contentdata = $contentdatanew;
        next ($artstatus);
      }
    }
      
    // return container
    if ($contentdatanew != false) return $contentdatanew;
    else return false;      
  }
  else return false;
}

// -------------------------------------------- settext -----------------------------------------------
// function: settext()
// input: publication name, container (XML), container name, text array, type array or string of text [u,f,l,c,d], article array or string [yes, no], 
//        text user array or string, user name, character set of text content, add microtime to ID [true,false] used for comments
// output: updated content container (XML), false on error

function settext ($site, $contentdata, $contentfile, $text, $type, $art, $textuser, $user, $charset="", $addmicrotime=false)
{
  global $mgmt_config, $publ_config;

  if (valid_publicationname ($site) && valid_objectname ($contentfile) && $contentdata != "" && is_array ($text) && (is_array ($type) || $type != "") && (is_array ($art) || $art != "") && valid_objectname ($user))
  {
    $link_db_updated = false;
    
    // load publication config
    if (!is_array ($publ_config)) $publ_config = parse_ini_file ($mgmt_config['abs_path_rep']."config/".$site.".ini"); 
    // publication management config
    if (!isset ($mgmt_config[$site]['url_path_page'])) require ($mgmt_config['abs_path_data']."config/".$site.".conf.php");    

    // load xml schema
    $text_schema_xml = chop (loadfile ($mgmt_config['abs_path_cms']."xmlsubschema/", "text.schema.xml.php"));
    $article_schema_xml = chop (loadfile ($mgmt_config['abs_path_cms']."xmlsubschema/", "article.schema.xml.php"));
  
    if (!is_array ($type))
    {
      $typebuffer = $type;
      $type = Null;
    }
    
    if (!is_array ($art))
    {
      $artbuffer = $art;
      $art = Null;
    }
    
    if (!is_array ($textuser))
    {
      $userbuffer = $textuser;
      $textuser = Null;
    }
  
    reset ($text);
    $continued = false;

    // loop through all text nodes
    for ($i = 1; $i <= sizeof ($text); $i++)
    {
      // get key (position) of array item
      $id = key ($text); 
          
      if ($id != "")
      {
        // set array if input parameter is string
        if ($typebuffer != "") $type[$id] = $typebuffer;
        if ($artbuffer != "") $art[$id] = $artbuffer;
        if ($userbuffer != "") $textuser[$id] = $userbuffer;  
        
        // remove freespaces
        $textcontent = trim ($text[$id]);  
  
        // if microtime is added
        if ($addmicrotime === true) 
        {
          if (strlen($textcontent) < 2 || strlen($textcontent) > 6800)
          {
            $continued = true;
            continue;
          }
          
          $elemid = $id.":".microtime(true);
        } 
        else 
        {
          $elemid = $id;
        }
        
        // formatted text
        // formatted text by CKEditor is already escaped!
        if ($type[$id] == "f")
        {
          // cut off <p> at the beginning of the content
          if (strtoupper (substr ($textcontent, 0, 3)) == "<P>" && strtoupper (substr ($textcontent, strlen ($textcontent) - 4)) == "</P>" && substr_count (strtoupper ($textcontent), "<P>") == 1 && substr_count (strtoupper ($textcontent), "</P>") == 1)
          {
            // cut off <p> at the beginning of the content
            $textcontent =  substr ($textcontent, 3);  
            // cut off </p> at the end of the content
            $textcontent =  substr ($textcontent, 0, strlen ($textcontent) - 4);   
          }  
          
          // correct \" of richtext editor
          $textcontent = str_replace ("\\\"", "\"", $textcontent);
        }
        // unformatted text
        elseif ($type[$id] == "u")
        {
          // html tags are not allowed
          if ($mgmt_config['editoru_html'] == false) 
          {
            // remove all html tags
            $textcontent = strip_tags ($textcontent);

            // correct quotes
            $textcontent = str_replace (array ("\\'", "\\\""), array ("'", "\""), $textcontent);
          }
        }
        // if date
        elseif ($type[$id] == "d")
        {
          // convert to international time format for database (deprecated since version 5.6.7)
          // $timestamp = strtotime ($textcontent);
          // if ($timestamp != "") $textcontent = date ("Y-m-d", $timestamp);
          
          // escape special characters (transform all special chararcters into their html/xml equivalents)
          $textcontent  = html_encode ($textcontent);          
        }
        // checkbox value, text options
        else
        {
          // escape special characters
          $textcontent  = html_encode ($textcontent);
        }

        // replace all characters that invoke a server-side script parser with "<" and ">"
        // (MS Word also produces XML declarations in html-Code, php tries to parse this declarations. this will cause a parse error)
        $textcontent = scriptcode_encode ($textcontent);
        
        // correct hypercms script video tags (to support older versions of video player)
        $textcontent = str_replace ("<hcms_script", "<script", $textcontent);
        $textcontent = str_replace ("</hcms_script", "</script", $textcontent);
        
        // convertpath to template variables for all page and image links
        if (!empty ($mgmt_config['url_path_media']) && $mgmt_config['url_path_media'] != "/") $textcontent = str_replace ($mgmt_config['url_path_media'], "%media%/", $textcontent);
        if (!empty ($mgmt_config[$site]['url_path_page']) && $mgmt_config[$site]['url_path_page'] != "/") $textcontent = str_replace ($mgmt_config[$site]['url_path_page'], "%page%/".$site."/", $textcontent);
        if (!empty ($mgmt_config['url_path_comp']) && $mgmt_config['url_path_comp'] != "/") $textcontent = str_replace ($mgmt_config['url_path_comp'], "%comp%/", $textcontent);
        if (!empty ($mgmt_config['url_path_cms']) && $mgmt_config['url_path_cms'] != "/") $textcontent = str_replace ($mgmt_config['url_path_cms'], "%hcms%/", $textcontent);
        
        if (!empty ($publ_config['url_publ_media']) && $publ_config['url_publ_media'] != "/") $textcontent = str_replace ($publ_config['url_publ_media'], "%media%/", $textcontent);
        if (!empty ($mgmt_config[$site]['url_publ_page']) && $mgmt_config[$site]['url_publ_page'] != "/") $textcontent = str_replace ($publ_config['url_publ_page'], "%page%/".$site."/", $textcontent);
        if (!empty ($publ_config['url_publ_comp']) && $publ_config['url_publ_comp'] != "/") $textcontent = str_replace ($publ_config['url_publ_comp'], "%comp%/", $textcontent);
        
        // if link management is enabled
        if ($mgmt_config[$site]['linkengine'] == true)
        {
          $link_array = false;
          
          // extract links (only page links "href" as identifier) from text content
          if ($textcontent != "" && (strpos ($textcontent, " href") > 0 || strpos ($textcontent, " src") > 0))
          {
            // extract new links    
            $temp_array_href = extractlinks ($textcontent, "href");
            $temp_array_src = extractlinks ($textcontent, "src");

            if (is_array ($temp_array_href) && is_array ($temp_array_src)) $temp_array = array_merge ($temp_array_href, $temp_array_src);
            elseif (is_array ($temp_array_href)) $temp_array = $temp_array_href;
            elseif (is_array ($temp_array_src)) $temp_array = $temp_array_src;

            // if links were found
            if (is_array ($temp_array))
            {
              $link_array = array();
              
              foreach ($temp_array as $link)
              {
                // page or component links
                if (strpos ("_".$link, "%page%") == 1 || strpos ("_".$link, "%comp%") == 1)
                {
                  $link_array[] = $link;
                }
                // multimedia links (holding component-ID)
                elseif (substr_count ($link, "_hcm") == 1)
                {
                  $container_id = getmediacontainerid ($link);
                  $container_data = loadcontainer ($container_id, "work", "sys");
                  $temp_array = getcontent ($container_data, "<contentobjects>");
                  $link_temp_array = link_db_getobject ($temp_array[0]);
                  if (is_array ($link_temp_array)) $link_array = array_merge ($link_array, $link_temp_array);
                }
              }
              
              if (is_array ($link_array) && sizeof ($link_array) > 0) $link_array = array_unique ($link_array);
              else $link_array = false;
            }
          }       
       
          // exctract previous (old) links
          $temp_array = selectcontent ($contentdata, "<text>", "<text_id>", $id);
          if ($temp_array != false && $temp_array[0] != "") $temp_array = getcontent ($temp_array[0], "<textcontent>");
          if ($temp_array != false && $temp_array[0] != "") $textcontent_old = $temp_array[0];
          else $textcontent_old = "";
          
          $link_old_array = false;
          
          if ($textcontent_old != "" && (strpos ($textcontent_old, " href") > 0 || strpos ($textcontent_old, " src") > 0))
          {
            $temp_array_href = extractlinks ($textcontent_old, "href");
            $temp_array_src = extractlinks ($textcontent_old, "src");

            if (is_array ($temp_array_href) && is_array ($temp_array_src)) $temp_array = array_merge ($temp_array_href, $temp_array_src);
            elseif (is_array ($temp_array_href)) $temp_array = $temp_array_href;
            elseif (is_array ($temp_array_src)) $temp_array = $temp_array_src;

            if (is_array ($temp_array))
            {
              $link_old_array = array();
              
              foreach ($temp_array as $link)
              { 
                // page or component links
                if (strpos ("_".$link, "%page%") == 1 || strpos ("_".$link, "%comp%") == 1)
                {
                  $link_old_array[] = $link;
                }
                // multimedia links (holding component-ID)
                elseif (substr_count ($link, "_hcm") == 1)
                {
                  $container_id = getmediacontainerid ($link);
                  $container_data = loadcontainer ($container_id, "work", "sys");
                  $temp_array = getcontent ($container_data, "<contentobjects>");
                  $link_temp_array = link_db_getobject ($temp_array[0]);
                  if (is_array ($link_temp_array)) $link_old_array = array_merge ($link_old_array, $link_temp_array);
                }
              }
              
              if (is_array ($link_old_array) && sizeof ($link_old_array) > 0) $link_old_array = array_unique ($link_old_array);
              else $link_old_array = false;
            }           
          }
          
          // update links
          if (is_array ($link_array) || is_array ($link_old_array))
          {
            // load link db
            if ((!isset ($link_db) || !is_array ($link_db)) && $link_db_updated != true)
            {
              $link_db = link_db_load ($site, $user);
            }
         
            // compare to previous (old) links
            if (is_array ($link_array) && is_array ($link_db))
            {
              foreach ($link_array as $link)
              {
                // link is a new one
                if ($link_old_array == false || (is_array ($link_old_array) && !in_array ($link, $link_old_array)))
                {
                  $site_link = getpublication ($link);
                  $cat_link = getcategory ($site_link, $link);
                  // insert link in link DB
                  $link_db = link_db_update ($site, $link_db, "link", $contentfile, $cat_link, "", $link, "unique");
                  $link_db_updated = true;
                }
              }
            }
            
            if (is_array ($link_old_array) && is_array ($link_db))
            {
              foreach ($link_old_array as $link)
              {
                // link does not exist any more
                if ($link_array == false || (is_array ($link_array) && !in_array ($link, $link_array)))
                {
                  $site_link = getpublication ($link);
                  $cat_link = getcategory ($site_link, $link);
                  // remove link in link DB
                  $link_db = link_db_update ($site, $link_db, "link", $contentfile, $cat_link, $link, "", "unique");
                  $link_db_updated = true;               
                }
              }
            }           
          }
        }

        // check if text
        if ($art[$id] == "no")
        {
          // set the new content
          $contentdatanew = setcontent ($contentdata, "<text>", "<textcontent>", "<![CDATA[".$textcontent."]]>", "<text_id>", $elemid);
          
          if ($contentdatanew == false)
          {
            $contentdatanew = addcontent ($contentdata, $text_schema_xml, "", "", "", "<textcollection>", "<text_id>", $elemid);
            $contentdatanew = setcontent ($contentdatanew, "<text>", "<textcontent>", "<![CDATA[".$textcontent."]]>", "<text_id>", $elemid);
          }
          
          if ($textuser[$id] != "") $contentdatanew = setcontent ($contentdatanew, "<text>", "<textuser>", $textuser[$id], "<text_id>", $elemid);
        }
        // check if article
        elseif ($art[$id] == "yes")
        {
          // get article id
          $artid = getartid ($id);
        
          // set the new content
          $contentdatanew = setcontent ($contentdata, "<text>", "<textcontent>", "<![CDATA[".$textcontent."]]>", "<text_id>", $elemid);
         
          if ($contentdatanew == false)
          {
            $contentdatanew = addcontent ($contentdata, $text_schema_xml, "<article>", "<article_id>", $artid, "<articletextcollection>", "<text_id>", $elemid);
    
            if ($contentdatanew == false)
            {
              $contentdatanew = addcontent ($contentdata, $article_schema_xml, "", "", "", "<articlecollection>", "<article_id>", $artid);
              $contentdatanew = addcontent ($contentdatanew, $text_schema_xml, "<article>", "<article_id>", $artid, "<articletextcollection>", "<text_id>", $elemid);          
            }
            
            $contentdatanew = setcontent ($contentdatanew, "<text>", "<textcontent>", "<![CDATA[".$textcontent."]]>", "<text_id>", $elemid);
            if ($textuser[$id] != "") $contentdatanew = setcontent ($contentdatanew, "<text>", "<textuser>", $textuser[$id], "<text_id>", $elemid);
          }
        }       
        
        $contentdata = $contentdatanew;
        next ($text);        
      } 
    }
    
    // if link management is enabled
    if ($mgmt_config[$site]['linkengine'] == true)
    {
      // if no link has been changed
      if ($link_db_updated == false) $test = true;
      // save link db after updates        
      elseif (isset ($link_db) && is_array ($link_db) && $link_db_updated == true) $test = link_db_save ($site, $link_db, $user);
      // link management is disabled
      elseif (isset ($link_db) && $link_db == true) $test = true;
      // error occured
      else $test = false;

      if ($test == false)
      {
        // unlock file
        link_db_close ($site, $user);    
           
        $errcode = "20522";
        $error[] = $mgmt_config['today']."|hypercms_set.inc.php|error|$errcode|link management file is missing or you do not have write permissions for ".$site.".link.dat";  
      }
    }
    
    // save log
    savelog (@$error);    

    // return container
    if (!empty ($contentdatanew) && $contentdatanew != false)
    {
      // relational DB connectivity
      if ($mgmt_config['db_connect_rdbms'] != "")
      {
        $container_id = substr ($contentfile, 0, strpos ($contentfile, ".xml")); 
       
        rdbms_setcontent ($container_id, $text, $user);                     
      }       
      
      return $contentdatanew;
    }
    elseif ($addmicrotime === true && $continued === true)
    {
      return $contentdata;
    }
    else return false;
  }
  else return false;
}

// -------------------------------------------- setmedia -----------------------------------------------
// function: setmedia()
// input: publication name, container (XML), container name, media arrays (some are optional), article array or string [yes, no], content user array or string, user name, chracter set of text content
// output: updated content container (XML), false on error

function setmedia ($site, $contentdata, $contentfile, $mediafile, $mediaobject_curr, $mediaobject, $mediaalttext, $mediaalign, $mediawidth, $mediaheight, $art, $mediauser, $user, $charset="")
{
  global $mgmt_config;

  if (valid_publicationname ($site) && $contentdata != "" && valid_objectname ($contentfile) && is_array ($mediafile) && (is_array ($art) || $art != "") && valid_objectname ($user))
  {
    // load xml schema
    $media_schema_xml = chop (loadfile ($mgmt_config['abs_path_cms']."xmlsubschema/", "media.schema.xml.php"));
    $article_schema_xml = chop (loadfile ($mgmt_config['abs_path_cms']."xmlsubschema/", "article.schema.xml.php"));
    
    // load link db
    $link_db = link_db_load ($site, $user);  
    
    if (!is_array ($art))
    {
      $artbuffer = $art;
      $art = Null;
    }
    
    if (!is_array ($mediauser))
    {
      $userbuffer = $mediauser;
      $mediauser = Null;
    }
        
    reset ($mediafile);
    
    for ($i = 1; $i <= sizeof ($mediafile); $i++)
    {
      // get key (position) of array item
      $id = key ($mediafile);   

      if ($id != "")
      { 
        // set values if not set
        if (!isset ($mediafile[$id])) $mediafile[$id] = "";
        if (!isset ($mediaobject[$id])) $mediaobject[$id] = "";
        if (!isset ($mediaalttext[$id])) $mediaalttext[$id] = "";
        if (!isset ($mediaalign[$id])) $mediaalign[$id] = "";
        if (!isset ($mediawidth[$id])) $mediawidth[$id] = "";
        if (!isset ($mediaheight[$id])) $mediaheight[$id] = ""; 
        
        // set array if input parameter is string
        if ($artbuffer != "") $art[$id] = $artbuffer;
        if ($userbuffer != "") $mediauser[$id] = $userbuffer;       
        
        $mediafile[$id] = urldecode ($mediafile[$id]);
        // remove dangerous script code
        $mediafile[$id] = scriptcode_encode ($mediafile[$id]);
        // escape special characters (transform all special chararcters into their html/xml equivalents)
        $mediafile[$id] = str_replace ("&", "&amp;", $mediafile[$id]);
        $mediafile[$id] = str_replace ("<", "&lt;", $mediafile[$id]);
        $mediafile[$id] = str_replace (">", "&gt;", $mediafile[$id]);  
        
        // remove dangerous script code
        $mediaalttext[$id] = scriptcode_encode ($mediaalttext[$id]);
        
        // encode special characters in alternative text
        $mediaalttext[$id] = html_encode ($mediaalttext[$id]);
    
        // check media file (url or component link)
        if (substr_count ($mediafile[$id], "://") == 0)
        { 
          // load object file
          $location = substr ($mediaobject[$id], 0, strrpos ($mediaobject[$id], "/")+1);
          $pagedata = loadfile ($location, getobject ($mediaobject[$id]));  
  
          if ($pagedata != false) 
          {
            // get media file name
            $mediafile_name = getfilename ($pagedata, "media");
            
            // get publication from objectpath
            $site_media = getpublication ($mediaobject[$id]);
            
            if ($mediafile_name != false) $mediafile[$id] = $site_media."/".$mediafile_name;
          }
        }
        else $mediaobject[$id] = "";
  
        // check if article
        if ($art[$id] == "no")
        {
          // set the new content
          $contentdatanew = setcontent ($contentdata, "<media>", "<mediafile>", trim ($mediafile[$id]), "<media_id>", $id);
          
          if ($contentdatanew == false)
          {
            $contentdatanew = addcontent ($contentdata, $media_schema_xml, "", "", "", "<mediacollection>", "<media_id>", $id);
            $contentdatanew = setcontent ($contentdatanew, "<media>", "<mediafile>", trim ($mediafile[$id]), "<media_id>", $id);
          }
          
          $contentdatanew = setcontent ($contentdatanew, "<media>", "<mediaobject>", trim ($mediaobject[$id]), "<media_id>", $id);
          $contentdatanew = setcontent ($contentdatanew, "<media>", "<mediaalttext>", "<![CDATA[".trim ($mediaalttext[$id])."]]>", "<media_id>", $id);
          $contentdatanew = setcontent ($contentdatanew, "<media>", "<mediaalign>", trim ($mediaalign[$id]), "<media_id>", $id);
          $contentdatanew = setcontent ($contentdatanew, "<media>", "<mediawidth>", trim ($mediawidth[$id]), "<media_id>", $id);
          $contentdatanew = setcontent ($contentdatanew, "<media>", "<mediaheight>", trim ($mediaheight[$id]), "<media_id>", $id);
          if ($mediauser[$id] != "") $contentdatanew = setcontent ($contentdatanew, "<media>", "<mediauser>", $mediauser[$id], "<media_id>", $id);
        }
        // check if article media
        elseif ($art[$id] == "yes")
        {
          // get the id of the article
          $artid = getartid ($id);
            
          // set the new content
          $contentdatanew = setcontent ($contentdata, "<media>", "<mediafile>", trim ($mediafile[$id]), "<media_id>", $id);
          
          if ($contentdatanew == false)
          {
            $contentdatanew = addcontent ($contentdata, $media_schema_xml, "<article>", "<article_id>", $artid, "<articlemediacollection>", "<media_id>", $id);
          
            if ($contentdatanew == false)
            {
              $contentdatanew = addcontent ($contentdata, $article_schema_xml, "", "", "", "<articlecollection>", "<article_id>", $artid);
              $contentdatanew = addcontent ($contentdatanew, $media_schema_xml, "<article>", "<article_id>", $artid, "<articlemediacollection>", "<media_id>", $id);
            }
            $contentdatanew = setcontent ($contentdatanew, "<media>", "<mediafile>", trim ($mediafile[$id]), "<media_id>", $id);
          }
          
          $contentdatanew = setcontent ($contentdatanew, "<media>", "<mediaobject>", trim ($mediaobject[$id]), "<media_id>", $id);
          $contentdatanew = setcontent ($contentdatanew, "<media>", "<mediaalttext>", "<![CDATA[".trim ($mediaalttext[$id])."]]>", "<media_id>", $id);
          $contentdatanew = setcontent ($contentdatanew, "<media>", "<mediaalign>", trim ($mediaalign[$id]), "<media_id>", $id);
          $contentdatanew = setcontent ($contentdatanew, "<media>", "<mediawidth>", trim ($mediawidth[$id]), "<media_id>", $id);
          $contentdatanew = setcontent ($contentdatanew, "<media>", "<mediaheight>", trim ($mediaheight[$id]), "<media_id>", $id);
          if ($mediauser[$id] != "") $contentdatanew = setcontent ($contentdatanew, "<media>", "<mediauser>", $mediauser[$id], "<media_id>", $id);
        }
  
        // ------------------------- add link to link management file ---------------------------  
        $link_db = link_db_update ($site, $link_db, "link", $contentfile, "comp", $mediaobject_curr[$id], $mediaobject[$id], "unique");        
        
        $contentdata = $contentdatanew;
        next ($mediafile);
      }
    }
    
    // save link db    
    if (is_array ($link_db)) $test = link_db_save ($site, $link_db, $user);
    elseif ($link_db == true) $test = true;
    else $test = false;
    
    if ($test == false)
    {
      // unlock file
      link_db_close ($site, $user);

      //define message to display
      $message = "<span class=hcmsHeadline>".$text8[$lang]."</span><br />\n".$text9[$lang]."<br />\n";
    }
  
    // return container
    if ($contentdatanew != false)
    {
      // relational DB connectivity
      if ($mgmt_config['db_connect_rdbms'] != "")
      {
        $container_id = substr ($contentfile, 0, strpos ($contentfile, ".xml"));        
        while (list ($key, $val) = each ($mediaalttext)) $text_array["media:".$key] = $val;
                     
        rdbms_setcontent ($container_id, $text_array, $user);                     
      }      
      
      return $contentdatanew;
    }
    else return false;      
  }
  else return false;
}

// -------------------------------------------- setpagelink -----------------------------------------------
// function: setpagelink()
// input: publication name, container (XML), container name, current link array, new link array, link target array, link text array, article array or string [yes, no], content user array or string, user name, chracter set of text content
// output: updated content container (XML), false on error

function setpagelink ($site, $contentdata, $contentfile, $linkhref_curr, $linkhref, $linktarget, $linktext, $art, $linkuser, $user, $charset="")
{
  global $mgmt_config;
  
  if (valid_publicationname ($site) && $contentdata != "" && valid_objectname ($contentfile) && is_array ($linkhref) && (is_array ($art) || $art != "") && valid_objectname ($user))
  {
    // load xml schema
    $link_schema_xml = chop (loadfile ($mgmt_config['abs_path_cms']."xmlsubschema/", "link.schema.xml.php"));
    $article_schema_xml = chop (loadfile ($mgmt_config['abs_path_cms']."xmlsubschema/", "article.schema.xml.php"));
  
    // load link db
    $link_db = link_db_load ($site, $user);  
    
    if (!is_array ($art))
    {
      $artbuffer = $art;
      $art = Null;
    }
    
    if (!is_array ($linkuser))
    {
      $userbuffer = $linkuser;
      $linkuser = Null;
    }
  
    reset ($linkhref);
    
    for ($i = 1; $i <= sizeof ($linkhref); $i++)
    {
      // get key (position) of array item
      $id = key ($linkhref);
      
      if ($id != "")
      {            
        // set values if not set
        if (!isset ($linkhref[$id])) $linkhref[$id] = "";
        if (!isset ($linktarget[$id])) $linktarget[$id] = "";
        if (!isset ($linktext[$id])) $linktext[$id] = "";
        // set array if input parameter is string
        if ($artbuffer != "") $art[$id] = $artbuffer;
        if ($userbuffer != "") $linkuser[$id] = $userbuffer;        
            
        $linkhref[$id] = urldecode ($linkhref[$id]);
        // remove dangerous scipt code
        $linkhref[$id] = scriptcode_encode ($linkhref[$id]);
        // escape special characters (transform all special chararcters into their html/xml equivalents) 
        $linkhref[$id] = str_replace ("&", "&amp;", $linkhref[$id]);
        $linkhref[$id] = str_replace ("<", "&lt;", $linkhref[$id]);
        $linkhref[$id] = str_replace (">", "&gt;", $linkhref[$id]); 
         
        $linktext[$id] = urldecode ($linktext[$id]);
        // remove dangerous scipt code
        $linktext[$id] = scriptcode_encode ($linktext[$id]);
        // escape special characters (transform all special chararcters into their html/xml equivalents)  
        if ($charset != "")
        {
          $linktext[$id] = html_encode ($linktext[$id], $charset);
        }  
        else
        {        
          $linktext[$id] = str_replace ("&", "&amp;", $linktext[$id]);
          $linktext[$id] = str_replace ("<", "&lt;", $linktext[$id]);
          $linktext[$id] = str_replace (">", "&gt;", $linktext[$id]); 
        } 
    
        // check if page link
        if ($art[$id] == "no")
        {
          // set the new content
          $contentdatanew = setcontent ($contentdata, "<link>", "<linkhref>", trim ($linkhref[$id]), "<link_id>", $id);
          
          if ($contentdatanew == false)
          {
            $contentdatanew = addcontent ($contentdata, $link_schema_xml, "", "", "", "<linkcollection>", "<link_id>", $id);
            $contentdatanew = setcontent ($contentdatanew, "<link>", "<linkhref>", trim ($linkhref[$id]), "<link_id>", $id);
          }
          
          $contentdatanew = setcontent ($contentdatanew, "<link>", "<linktarget>", trim ($linktarget[$id]), "<link_id>", $id);
          $contentdatanew = setcontent ($contentdatanew, "<link>", "<linktext>", "<![CDATA[".trim ($linktext[$id])."]]>", "<link_id>", $id);
          if ($linkuser[$id] != "") $contentdatanew = setcontent ($contentdatanew, "<link>", "<linkuser>", $linkuser[$id], "<link_id>", $id);
        }
        // check if article link
        elseif ($art[$id] == "yes")
        {
          // get the id of the article
          $artid = getartid ($id);
        
          // set the new content
          $contentdatanew = setcontent ($contentdata, "<link>", "<linkhref>", trim ($linkhref[$id]), "<link_id>", $id);
          
          if ($contentdatanew == false)
          {
            $contentdatanew = addcontent ($contentdata, $link_schema_xml, "<article>", "<article_id>", $artid, "<articlelinkcollection>", "<link_id>", $id);
            
            if ($contentdatanew == false)
            {
              $contentdatanew = addcontent ($contentdata, $article_schema_xml, "", "", "", "<articlecollection>", "<article_id>", $artid);
              $contentdatanew = addcontent ($contentdatanew, $link_schema_xml, "<article>", "<article_id>", $artid, "<articlelinkcollection>", "<link_id>", $id);
            }
            $contentdatanew = setcontent ($contentdatanew, "<link>", "<linkhref>", trim ($linkhref[$id]), "<link_id>", $id);
          }
          
          $contentdatanew = setcontent ($contentdatanew, "<link>", "<linktarget>", trim ($linktarget[$id]), "<link_id>", $id);
          $contentdatanew = setcontent ($contentdatanew, "<link>", "<linktext>", "<![CDATA[".trim ($linktext[$id])."]]>", "<link_id>", $id);
          if ($linkuser[$id] != "") $contentdatanew = setcontent ($contentdatanew, "<link>", "<linkuser>", $linkuser[$id], "<link_id>", $id);
        }
        
        // ------------------------- add link to link management file ---------------------------
        if (empty ($linkhref_curr[$id])) $linkhref_curr[$id] = "";        
        $link_db = link_db_update ($site, $link_db, "link", $contentfile, "page", $linkhref_curr[$id], $linkhref[$id], "unique"); 
          
        $contentdata = $contentdatanew;
        next ($linkhref);
      }
    }
    
    // save link db    
    if (is_array ($link_db)) $test = link_db_save ($site, $link_db, $user);
    elseif ($link_db == true) $test = true;
    else $test = false;
    
    if ($test == false)
    {
      // unlock file
      link_db_close ($site, $user);
         
      $errcode = "20511";
      $error[] = $mgmt_config['today']."|hypercms_set.inc.php|error|$errcode|link management file is missing or you do not have write permissions for ".$site.".link.dat";  
    }
    
    // save log
    savelog (@$error); 
    
    // return container
    if ($contentdatanew != false)
    {
      // relational DB connectivity
      if ($mgmt_config['db_connect_rdbms'] != "")
      {
        $container_id = substr ($contentfile, 0, strpos ($contentfile, ".xml")); 
        while (list ($key, $val) = each ($linktext)) $text_array["link:".$key] = $val;
                
        rdbms_setcontent ($container_id, $text_array, $user);                     
      }  
    
      return $contentdatanew;
    }
    else return false;      
  }
  else return false;
}

// -------------------------------------------- setcomponent -----------------------------------------------
// function: setcomplink()
// input: publication name, container (XML), container name, component arrays (some are optional), article array or string [yes, no], content user array or string, user name
// output: updated content container (XML), false on error

function setcomplink ($site, $contentdata, $contentfile, $component_curr, $component, $condition, $art, $compuser, $user)
{
  global $mgmt_config;
  
  if (valid_publicationname ($site) && $contentdata != "" && valid_objectname ($contentfile) && is_array ($component) && (is_array ($art) || $art != "") && valid_objectname ($user))
  {
    // load xml schema
    $component_schema_xml = chop (loadfile ($mgmt_config['abs_path_cms']."xmlsubschema/", "component.schema.xml.php"));
    $article_schema_xml = chop (loadfile ($mgmt_config['abs_path_cms']."xmlsubschema/", "article.schema.xml.php"));
    
    // load link db
    $link_db = link_db_load ($site, $user);   
    
    if (!is_array ($art))
    {
      $artbuffer = $art;
      $art = Null;
    }
    
    if (!is_array ($compuser))
    {
      $userbuffer = $compuser;
      $compuser = Null;
    }  
  
    reset ($component);
    
    for ($i = 1; $i <= sizeof ($component); $i++)
    {
      // get key (position) of array item
      $id = key ($component);
      
      if ($id != "")
      {  
        // set array if input parameter is string
        if ($artbuffer != "") $art[$id] = $artbuffer;
        if ($userbuffer != "") $compuser[$id] = $userbuffer;
  
        // check if page component
        if ($art[$id] == "no")
        {
          // set the new content
          $contentdatanew = setcontent ($contentdata, "<component>", "<componentfiles>", trim ($component[$id]), "<component_id>", $id);
          
          if ($contentdatanew == false)
          {
            $contentdatanew = addcontent ($contentdata, $component_schema_xml, "", "", "", "<componentcollection>", "<component_id>", $id);
            $contentdatanew = setcontent ($contentdatanew, "<component>", "<componentfiles>", trim ($component[$id]), "<component_id>", $id);
          }
          
          if ($compuser[$id] != "") $contentdatanew = setcontent ($contentdatanew, "<component>", "<componentuser>", $compuser[$id], "<component_id>", $id);
          if (isset ($condition[$id])) $contentdatanew = setcontent ($contentdatanew, "<component>", "<componentcond>", $condition[$id], "<component_id>", $id);        
        }
        // check if article component
        elseif ($art[$id] == "yes")
        {
          // get the id of the article
          $artid = getartid ($id);
    
          // set the new content
          $contentdatanew = setcontent ($contentdata, "<component>", "<componentfiles>", trim ($component[$id]), "<component_id>", $id);
          
          if ($contentdatanew == false)
          {
            $contentdatanew = addcontent ($contentdata, $component_schema_xml, "<article>", "<article_id>", $artid, "<articlecomponentcollection>", "<component_id>", $id);
            
            if ($contentdatanew == false)
            {
              $contentdatanew = addcontent ($contentdata, $article_schema_xml, "", "", "", "<articlecollection>", "<article_id>", $artid);
              $contentdatanew = addcontent ($contentdatanew, $component_schema_xml, "<article>", "<article_id>", $artid, "<articlecomponentcollection>", "<component_id>", $id);
            }
            $contentdatanew = setcontent ($contentdatanew, "<component>", "<componentfiles>", trim ($component[$id]), "<component_id>", $id);
          }
          
          if ($compuser[$id] != "") $contentdatanew = setcontent ($contentdatanew, "<component>", "<componentuser>", $compuser[$id], "<component_id>", $id);
          if (isset ($condition[$id])) $contentdatanew = setcontent ($contentdatanew, "<component>", "<componentcond>", $condition[$id], "<component_id>", $id);        
        }
    
        // ------------------------- add link to link management file ---------------------------
        if (empty ($component_curr[$id])) $component_curr[$id] = "";
        $link_db = link_db_update ($site, $link_db, "link", $contentfile, "comp", $component_curr[$id], $component[$id], "unique"); 
          
        $contentdata = $contentdatanew;
        next ($component);
      }
    }

    // save link db    
    if (is_array ($link_db)) $test = link_db_save ($site, $link_db, $user);
    elseif ($link_db == true) $test = true;
    else $test = false;
    
    if ($test == false)
    {
      // unlock file
      link_db_close ($site, $user);
         
      $errcode = "20512";
      $error[] = $mgmt_config['today']."|hypercms_set.inc.php|error|$errcode|link management file is missing or you do not have write permissions for ".$site.".link.dat";  
    }
    
    // save log
    savelog (@$error);    
    
    // return container
    if ($contentdatanew != false) return $contentdatanew;
    else return false;      
  }
  else return false;
}

// ------------------------------------------- sethead -------------------------------------------
// function: sethead()
// input: publication name, container (XML), container name, content array, user name, chracter set of text content
// output: updated content container (XML), false on error

// description: if content is general meta information

function sethead ($site, $contentdata, $contentfile, $headcontent, $user, $charset="")
{
  global $mgmt_config;
  
  if (valid_publicationname ($site) && $contentdata != "" && valid_objectname ($contentfile) && is_array ($headcontent) && valid_objectname ($user))
  {
    reset ($headcontent);
  
    while (list ($tagname, $content) = each ($headcontent))
    {
      if ($tagname != "") 
      {
        // escape special characters (transform all special chararcters into their html/xml equivalents)
        // except the code of page customer tracking
        if ($tagname != "pagetracking" && $content != "")
        {
          // remove dangerous script code
          $content = scriptcode_encode ($content);
          
          // encode special characters
          $content = html_encode ($content);
        }
        
        $contentdata = setcontent ($contentdata, "<head>", "<".$tagname.">", "<![CDATA[".trim ($content)."]]>", "", "");

        // set xml encoding if content-type has changed and is not empty
        if ($tagname == "pagecontenttype" && $contentdata != false)
        {
          $charset = substr ($content, strpos ($content, "charset"));
          $charset = trim (substr ($charset, strpos ($charset, "=") + 1));
      
          if ($charset != "") $contentdata = setxmlparameter ($contentdata, "encoding", $charset);
        }
      }
    }
    
    // return container
    if ($contentdata != false)
    {
      // relational DB connectivity
      if ($mgmt_config['db_connect_rdbms'] != "")
      {      
        // standard text-based meta information
        if (isset ($headcontent['pagetitle'])) $text_array['head:pagetitle'] = $headcontent['pagetitle'];
        if (isset ($headcontent['pageauthor'])) $text_array['head:pageauthor'] = $headcontent['pageauthor'];
        if (isset ($headcontent['pagedescription'])) $text_array['head:pagedescription'] = $headcontent['pagedescription'];
        if (isset ($headcontent['pagekeywords'])) $text_array['head:pagekeywords'] = $headcontent['pagekeywords'];

        $container_id = substr ($contentfile, 0, strpos ($contentfile, ".xml"));
                    
        rdbms_setcontent ($container_id, $text_array, $user);
      }  
    
      return $contentdata;
    }
    else return false;    
  }
  else return false; 
}

// ==================================== SET FILEPOINTER =======================================

// ------------------------------------- setfilename ------------------------------------------

// function: setfilename()
// input: file content, hyperCMS tag name in page or component [content, template, media, name], new value 
// output: filedata/false on error

// description:
// sets or creates the file name of the hyperCMS content file, template file, media file or file name pointer

function setfilename ($filedata, $tagname, $value)
{
  if ($filedata != "" && $tagname != "" && $value != "")
  {
    // define comment tag for file pointers (changed since version 4.1 to <!-- pointer -->)
    if (strpos (strtolower ("_".$filedata), "<!-- hypercms:") > 0) 
    {
      $ctagbegin = "<!-- ";
      $ctagend = " -->";
    }
    else 
    {
      $ctagbegin = "<!";
      $ctagend = ">";
    }
    
    // find first positions of xml schema file name
    if (strpos (strtolower ($filedata), "hypercms:".strtolower ($tagname)." file=\"") > 0)
    {
      $len = strlen ($ctagbegin."hypercms:".$tagname." file=\"");
      $namestart = strpos (strtolower ($filedata), $ctagbegin."hypercms:".strtolower ($tagname)." file=\"") + $len;
      $nameend = strpos ($filedata, "\"".$ctagend, $namestart);
    }
    elseif (strpos (strtolower ($filedata), "hypercms:".strtolower ($tagname)." file = \"") > 0)
    {
      $len = strlen ($ctagbegin."hypercms:".$tagname." file = \"");
      $namestart = strpos (strtolower ($filedata), $ctagbegin."hypercms:".strtolower ($tagname)." file = \"") + $len;
      $nameend = strpos ($filedata, "\"".$ctagend, $namestart);
    }
    else $namestart = 0;
  
    if ($namestart > 0)
    {
      // get file name
      $filedata = substr_replace ($filedata, $value, $namestart, $nameend-$namestart);
      
      return $filedata;
    }
    else
    {
      // define comment tag
      $sourcefile = "<!-- hypercms:".strtolower ($tagname)." file=\"".$value."\" -->\n";
      
      // insert hypercms comment tags for reference
      $bodytag = gethtmltag ($filedata, "<body");
      if ($bodytag == false || $bodytag == "") $bodytag = gethtmltag ($filedata, "<?xml");
      
      if ($bodytag != "") $filedata = str_replace ($bodytag, $bodytag.$sourcefile, $filedata);
      else $filedata = $sourcefile.$filedata; 
      
      return $filedata;   
    }
  }
  else return false;
}
?>