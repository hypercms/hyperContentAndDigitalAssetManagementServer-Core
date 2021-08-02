<?php
/*
 * This file is part of
 * hyper Content & Digital Management Server - http://www.hypercms.com
 * Copyright (c) by hyper CMS Content Management Solutions GmbH
 *
 * You should have received a copy of the license (license.txt) along with hyper Content & Digital Management Server
 */
 
// =========================================== SESSION ==============================================

// ------------------------- setsession -----------------------------
// function: setsession()
// input: temporary hyperCMS variable name  [string or array], value [string or array] (optional), write session data for load balancer [boolean] (optional) 
// output: true / false on error

function setsession ($variable, $content="", $write=false)
{
  if ($variable != "" && session_id() != "")
  {
    if (is_string ($variable))
    {
      // define variable name (prefix hcms_ is required)
      if (strpos ("_".$variable, "hcms_") == 0) $variable = "hcms_".$variable;
      // set value for session variable
      $_SESSION[$variable] = $content;

      // write session data for load balancer
      if ($write == true)
      {
        return writesessiondata ();
      }
      else return true;
    }
    elseif (is_array ($variable))
    {
      $result = true;

      foreach ($variable as $key => &$value)
      {
        $value = setsession ($value, $content[$key], $write);
        if ($value == false) $result = false;
      }

      if ($result == true) return $variable;
      else return false;
    }
    else return false;
  }
  else return false;
}

// =========================================== TEMPLATE ==============================================

// -------------------------------- settemplate --------------------------------
// function: settemplate()
// input: publication name [string], location [string], object [string], template name [string], recursive [boolean] (optional)
// output: true/false

// description:
// This function sets the template for a single folder/object or all objects in a folder.

function settemplate ($site, $location, $object, $template, $recursive=false)
{
  global $mgmt_config;

  if (valid_publicationname ($site) && valid_locationname ($location) && valid_objectname ($object) && valid_objectname ($template))
  {
    // DB connectivity
    if (!empty ($mgmt_config['db_connect_rdbms']) && !function_exists ("rdbms_settemplate")) include_once ($mgmt_config['abs_path_cms']."database/db_connect/".$mgmt_config['db_connect_rdbms']);

    // add slash if not present at the end of the location string
    $location = correctpath ($location);

    // convert location
    $location = deconvertpath ($location, "file");

    // auto correct
    if (is_dir ($location.$object))
    {
      $location = $location.$object."/";
      $object = ".folder";
    }

    // check access permissions
    $cat = getcategory ($site, $location);
    $ownergroup = accesspermission ($site, $location, $cat);
    $setlocalpermission = setlocalpermission ($site, $ownergroup, $cat);
    if ($ownergroup == false || $setlocalpermission['root'] != 1 || $setlocalpermission['create'] != 1) return false;

    // set template recursive (only for folder)
    if ($recursive == true && $object == ".folder")
    {
      $scandir = scandir ($location);

      if ($scandir)
      {
        foreach ($scandir as $file)
        {
          if ($file != '.' && $file != '..')
          {
            if (is_dir ($location.$file)) $result = settemplate ($site, $location.$file."/", ".folder", $template, $recursive);
            else $result = settemplate ($site, $location, $file, $template, false);
          }
        }
      }

      return $result;
    }
    // set template for a single object
    else
    {
      // load object file and get media file
      $objectdata = loadfile ($location, $object);
      $container = getfilename ($objectdata, "content");
      $media = getfilename ($objectdata, "media");

      // container must be present
      if (!empty ($container))
      {
        // correct template category
        if ($media != "" || $object == ".folder") $cat = "meta";

        // define template file name
        if (strpos ($template, ".page.tpl") == false && $cat == "page") $template = $template.".page.tpl";
        elseif (strpos ($template, ".comp.tpl") == false && $cat == "comp") $template = $template.".comp.tpl";
        elseif (strpos ($template, ".meta.tpl") == false && $cat == "meta") $template = $template.".meta.tpl";

        // check if template exists
        $template_array = gettemplates ($site, $cat);
        if (!in_array ($template, $template_array)) return false;

        // set new template
        $objectdata = setfilename ($objectdata, "template", $template);

        if ($objectdata != false)
        {
          // DB connectivity
          if (function_exists ("rdbms_settemplate")) rdbms_settemplate (convertpath ($site, $location.$object, $cat), $template); 

          // save file
          return savefile ($location, $object, $objectdata);
        }
        else return false;
      }
      else return false;
    }
  }
  else return false;
}

// ========================================== CONTENT ===========================================

// ----------------------------------------- settaxonomy ------------------------------------------
// function: settaxonomy()
// input: publication name [string], container ID [string], 2-digit language code [string]][array] (optional), taxonomy definition [array] (optional)
// output: result array / false on error

// description:
// Analyzes the content regarding all taxonomy keywords, saves results in database and returns an array (multilingual support based on taxonomies).

function settaxonomy ($site, $container_id, $langcode="", $taxonomy="")
{
  global $mgmt_config;

  if (valid_publicationname ($site) && intval ($container_id) > 0 && is_array ($mgmt_config))
  {
    $langcount = array();

    // load publication management config
    if (!isset ($mgmt_config[$site]['taxonomy']) && is_file ($mgmt_config['abs_path_data']."config/".$site.".conf.php"))
    {
      require ($mgmt_config['abs_path_data']."config/".$site.".conf.php");
    }

    // if taxonomy is enabled
    if (strtolower ($site) == "default" || (valid_publicationname ($site) && !empty ($mgmt_config[$site]['taxonomy'])))
    { 
      // load taxonomy of publication
      if (!is_array ($taxonomy) && valid_publicationname ($site) && is_file ($mgmt_config['abs_path_data']."include/".$site.".taxonomy.inc.php"))
      {
        include ($mgmt_config['abs_path_data']."include/".$site.".taxonomy.inc.php");
      }
      // load default taxonomy
      elseif (!is_array ($taxonomy) && is_file ($mgmt_config['abs_path_data']."include/default.taxonomy.inc.php"))
      {
        include ($mgmt_config['abs_path_data']."include/default.taxonomy.inc.php");
      }
 
      // search for taxonomy keyword and its childs
      if (!empty ($taxonomy) && is_array ($taxonomy) && sizeof ($taxonomy) > 0)
      {
        $result = array();

        // get content of container
        $text_array = rdbms_getcontent ($site, $container_id);

        if (is_array ($text_array) && sizeof ($text_array) > 0)
        {
          foreach ($text_array as $text_id => $text)
          {
            $langcount = array();

            // content is not empty
            if ($text_id != "" && trim ($text) != "")
            {
              // clean text
              $text = cleancontent ($text, "UTF-8");
              $text = strtolower ($text);

              reset ($taxonomy);

              // return key = taxonomy ID and value = keyword
              foreach ($taxonomy as $tax_lang => $tax_array)
              {
                // language restriction
                if (
                     (is_string ($langcode) && $langcode != "" && $tax_lang == strtolower ($langcode)) || 
                     (!empty ($langcode[$text_id]) && $tax_lang == strtolower ($langcode[$text_id])) || 
                     (is_string ($langcode) && $langcode == "") || 
                     empty ($langcode[$text_id])
                )
                {
                  $langcount[$text_id][$tax_lang] = 0;

                  foreach ($tax_array as $path => $keyword)
                  {
                    // find taxonomy keyword in text
                    if ($keyword != "" && (strpos ("_ ".$text." ", strtolower (" ".$keyword)) > 0 || strpos ("_,".$text.",", strtolower (",".$keyword.",")) > 0))
                    {
                      // get taxonomy ID from taxonomy path (last item in path)
                      $path_temp = substr ($path, 0, -1);
                      $id = substr ($path_temp, strrpos ($path_temp, "/") + 1);

                      if (intval ($id) > 0)
                      {
                        // result array
                        $result[$text_id][$tax_lang][$id] = $keyword;

                        // count number of found expressions per language and text ID if keyword has more than 5 digits
                        if (strlen ($keyword) > 5) $langcount[$text_id][$tax_lang]++;
                      }
                    }
                  }
                }
              }

              // analyze languages for text ID
              if (is_array ($langcount) && sizeof ($langcount[$text_id]) > 0 && !empty ($result[$text_id]))
              {
                // get highest count
                $count = max ($langcount[$text_id]);

                // get language with highest count
                $selected_langcode = array_search ($count, $langcount[$text_id]);

                // remove other languages
                if (!empty ($selected_langcode))
                {
                  foreach ($result[$text_id] as $lang_delete => $array)
                  {
                    if ($lang_delete != $selected_langcode) unset ($result[$text_id][$lang_delete]);
                  }
                }
              }
            }
            // content is empty
            elseif ($text_id != "")
            {
              // result array in order to delete all taxonomy entries for the text ID
              $result[$text_id]['en'][0] = "";
            }
          }

          // write entries to database
          rdbms_settaxonomy ($site, $container_id, $result);
        }

        if (is_array ($result) && sizeof ($result) > 0) return $result;
        else return false;
      }
      else return false;
    }
    else return false;
  }
  else return false;
}

// ------------------------------------------ article ----------------------------------------------
// function: setarticle()
// input: publication name [string], container (XML) [string], container name [string], article title [array], article status [array], article beginn date [array] (optional), article end date [array] (optional), article user name [array or string] (optional), user name [string] (optional)
// output: updated content container (XML), false on error

// description:
// Set article content in container. The content container will be returned and not saved. 

function setarticle ($site, $contentdata, $contentfile, $arttitle=array(), $artstatus=array(), $artdatefrom=array(), $artdateto=array(), $artuser=array(), $user="sys")
{
  global $mgmt_config;

  if ($contentdata != "" && is_array ($artstatus) && valid_objectname ($user) && is_array ($mgmt_config))
  {
    // initialize
    if (!is_array ($arttitle)) $arttitle = array();
    if (!is_array ($artstatus)) $artstatus = array();
    if (!is_array ($artdatefrom)) $artdatefrom = array();
    if (!is_array ($artdateto)) $artdateto = array();

    // if article user is not an array
    if (!is_array ($artuser))
    {
      $userbuffer = $artuser;
      $artuser = Null;
    }

    // load xml schema
    $article_schema_xml = chop (loadfile ($mgmt_config['abs_path_cms']."xmlsubschema/", "article.schema.xml.php"));

    reset ($artstatus);

    foreach ($artstatus as $artid => $temp)
    {
      if ($artid != "")
      {
        if (!isset ($arttitle[$artid])) $arttitle[$artid] = "";
        if (!isset ($artdatefrom[$artid])) $artdatefrom[$artid] = "";
        if (!isset ($artdateto[$artid])) $artdateto[$artid] = "";

        // set array if input parameter is string
        if (!empty ($userbuffer)) $artuser[$artid] = $userbuffer;

        // escape special characters for article title (transform all special chararcters into their html/xml equivalents)
        $arttitle[$artid] = str_replace ("&", "&amp;", $arttitle[$artid]);
        $arttitle[$artid] = str_replace ("<", "&lt;", $arttitle[$artid]);
        $arttitle[$artid] = str_replace (">", "&gt;", $arttitle[$artid]);

        // set the new content
        $contentdatanew = setcontent ($contentdata, "<article>", "<articletitle>", trim ($arttitle[$artid]), "<article_id>", $artid, true);

        if ($contentdatanew == false)
        {
          $contentdatanew = addcontent ($contentdata, $article_schema_xml, "", "", "", "<articlecollection>", "<article_id>", $artid);
          $contentdatanew = setcontent ($contentdatanew, "<article>", "<articletitle>", trim ($arttitle[$artid]), "<article_id>", $artid, true);
        }

        $contentdatanew = setcontent ($contentdatanew, "<article>", "<articledatefrom>", $artdatefrom[$artid], "<article_id>", $artid, true);
        $contentdatanew = setcontent ($contentdatanew, "<article>", "<articledateto>", $artdateto[$artid], "<article_id>", $artid, true);
        $contentdatanew = setcontent ($contentdatanew, "<article>", "<articlestatus>", $artstatus[$artid], "<article_id>", $artid, true);
        if (!empty ($artuser[$artid])) $contentdatanew = setcontent ($contentdatanew, "<article>", "<articleuser>", $artuser[$artid], "<article_id>", $artid, true);

        $contentdata = $contentdatanew;
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
// input: publication name [string], container (XML) [string], container name [string], text with tag Id as key and text as value [array], text type [array or string] [u,f,l,c,d,k,s], article [array or string]  [yes,no] (optional), 
//          text user [array or string] (optional), user name [string] (optional), character set of text content [string] (optional), add microtime to ID used for comments [boolean] (optional)
// output: updated content container (XML), false on error

// description:
// Set text content in container and database. The content container will be returned and not saved. 

function settext ($site, $contentdata, $contentfile, $text=array(), $type=array(), $art="no", $textuser=array(), $user="sys", $charset="", $addmicrotime=false)
{
  global $mgmt_config, $publ_config;

  if (valid_publicationname ($site) && valid_objectname ($contentfile) && $contentdata != "" && is_array ($text) && (is_array ($type) || $type != "") && (is_array ($art) || $art != "") && valid_objectname ($user) && is_array ($mgmt_config))
  {
    // initialize
    $error = array();
    $link_db_updated = false;
    $continued = false;

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

    // load publication config
    if (!is_array ($publ_config)) $publ_config = parse_ini_file ($mgmt_config['abs_path_rep']."config/".$site.".ini"); 

    // publication management config
    if (!isset ($mgmt_config[$site]['url_path_page']) && is_file ($mgmt_config['abs_path_data']."config/".$site.".conf.php"))
    {
      require_once ($mgmt_config['abs_path_data']."config/".$site.".conf.php");
    } 

    // load xml schema
    $text_schema_xml = chop (loadfile ($mgmt_config['abs_path_cms']."xmlsubschema/", "text.schema.xml.php"));
    $article_schema_xml = chop (loadfile ($mgmt_config['abs_path_cms']."xmlsubschema/", "article.schema.xml.php"));

    reset ($text);

    // loop through all text nodes
    foreach ($text as $id => $temp)
    {
      if ($id != "")
      {
        // set array if input parameter is string
        if (!empty ($typebuffer)) $type[$id] = $typebuffer;
        if (!empty ($artbuffer)) $art[$id] = $artbuffer;
        if (!empty ($userbuffer)) $textuser[$id] = $userbuffer;

        // taxonomy tree selector returns an array
        if (is_array ($text[$id])) $text[$id] = implode (",", $text[$id]);

        // remove freespaces
        $textcontent = trim ($text[$id]);

        // if microtime is added
        if ($addmicrotime === true) 
        {
          // text comment is too short or too long
          if (strlen ($textcontent) < 2 || strlen ($textcontent) > 6800)
          {
            $continued = true;
            continue;
          }

          $elemid = $id.":".microtime (true);
        } 
        else 
        {
          $elemid = $id;
        }

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
        // date
        elseif ($type[$id] == "d")
        {
          // convert to international time format for database (deprecated since version 5.6.7)
          // $timestamp = strtotime ($textcontent);
          // if ($timestamp != "") $textcontent = date ("Y-m-d", $timestamp);

          // escape special characters (transform all special chararcters into their html/xml equivalents)
          $textcontent  = html_encode ($textcontent);
        }
        // keywords
        elseif ($type[$id] == "k")
        {
          $textcontent = trim ($textcontent);
        }
        // signature
        elseif ($type[$id] == "s")
        {
          // base64 encoded image (image/png;base64,image-string)
          $textcontent = trim ($textcontent);
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
            if (!empty ($temp_array) && is_array ($temp_array))
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

          if (!empty ($temp_array[0])) $temp_array = getcontent ($temp_array[0], "<textcontent>", true);

          if (!empty ($temp_array[0])) $textcontent_old = $temp_array[0];
          else $textcontent_old = "";

          $link_old_array = false;

          if (!empty ($textcontent_old) && (strpos ($textcontent_old, " href") > 0 || strpos ($textcontent_old, " src") > 0))
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
          $contentdatanew = setcontent ($contentdata, "<text>", "<textcontent>", "<![CDATA[".$textcontent."]]>", "<text_id>", $elemid, true);

          if ($contentdatanew == false)
          {
            $contentdatanew = addcontent ($contentdata, $text_schema_xml, "", "", "", "<textcollection>", "<text_id>", $elemid);
            $contentdatanew = setcontent ($contentdatanew, "<text>", "<textcontent>", "<![CDATA[".$textcontent."]]>", "<text_id>", $elemid, true);
          }

          if (!empty ($textuser[$id])) $contentdatanew = setcontent ($contentdatanew, "<text>", "<textuser>", $textuser[$id], "<text_id>", $elemid, true);
        }
        // check if article
        elseif ($art[$id] == "yes")
        {
          // get article id
          $artid = getartid ($id);

          // set the new content
          $contentdatanew = setcontent ($contentdata, "<text>", "<textcontent>", "<![CDATA[".$textcontent."]]>", "<text_id>", $elemid, true);
 
          if ($contentdatanew == false)
          {
            $contentdatanew = addcontent ($contentdata, $text_schema_xml, "<article>", "<article_id>", $artid, "<articletextcollection>", "<text_id>", $elemid);

            if ($contentdatanew == false)
            {
              $contentdatanew = addcontent ($contentdata, $article_schema_xml, "", "", "", "<articlecollection>", "<article_id>", $artid);
              $contentdatanew = addcontent ($contentdatanew, $text_schema_xml, "<article>", "<article_id>", $artid, "<articletextcollection>", "<text_id>", $elemid);
            }

            $contentdatanew = setcontent ($contentdatanew, "<text>", "<textcontent>", "<![CDATA[".$textcontent."]]>", "<text_id>", $elemid, true);
            if (!empty ($textuser[$id])) $contentdatanew = setcontent ($contentdatanew, "<text>", "<textuser>", $textuser[$id], "<text_id>", $elemid, true);
          }
        } 

        $contentdata = $contentdatanew;
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
        $error[] = $mgmt_config['today']."|hypercms_set.inc.php|error|".$errcode."|Link management file is missing or you do not have write permissions for '".$site.".link.dat'";
      }
    }

    // save log
    savelog (@$error);

    // return container
    if (!empty ($contentdatanew) && $contentdatanew != false)
    {
      // relational DB connectivity
      if (!empty ($mgmt_config['db_connect_rdbms']))
      {
        $container_id = substr ($contentfile, 0, strpos ($contentfile, ".xml")); 
 
        // set content in database
        rdbms_setcontent ($site, $container_id, $text, $type, $user);
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
// input: publication name [string], container (XML) [string], container name [string], media files with tag ID as key and reference as value [array] (optional), new media object references with tag ID as key and reference as value [array], 
//          media alternative text [array] (optional), media alignment [array] (optional), media width [array] (optional), media height [array] (optional), article [array or string] [yes,no] (optional), 
//          content user [array or string] (optional), user name [string] (optional), character set of text content [string] (optional)
// output: updated content container (XML), false on error

// description:
// Set media content in container and database. The content container will be returned and not saved. 

function setmedia ($site, $contentdata, $contentfile, $mediafile=array(), $mediaobject=array(), $mediaalttext=array(), $mediaalign=array(), $mediawidth=array(), $mediaheight=array(), $art="no", $mediauser="", $user="sys", $charset="")
{
  global $mgmt_config;

  if (valid_publicationname ($site) && $contentdata != "" && valid_objectname ($contentfile) && is_array ($mediafile) && (is_array ($art) || $art != "") && valid_objectname ($user) && is_array ($mgmt_config))
  {
    // initialize
    $error = array();
    if (!is_array ($mediaobject)) $mediaobject = array();
    if (!is_array ($mediaalttext)) $mediaalttext = array();
    if (!is_array ($mediaalign)) $mediaalign = array();
    if (!is_array ($mediawidth)) $mediawidth = array();
    if (!is_array ($mediaheight)) $mediaheight = array();

    if (!is_array ($mediauser))
    {
      $userbuffer = $mediauser;
      $mediauser = Null;
    }

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

    reset ($mediafile);

    foreach ($mediafile as $id => $temp)
    {
      if ($id != "")
      { 
        // set values if not set
        if (empty ($mediafile[$id])) $mediafile[$id] = "";
        if (empty ($mediaobject[$id])) $mediaobject[$id] = "";
        if (empty ($mediaalttext[$id])) $mediaalttext[$id] = "";
        if (empty ($mediaalign[$id])) $mediaalign[$id] = "";
        if (empty ($mediawidth[$id])) $mediawidth[$id] = "";
        if (empty ($mediaheight[$id])) $mediaheight[$id] = ""; 

        // set array if input parameter is string
        if (!empty ($artbuffer)) $art[$id] = $artbuffer;
        if (!empty ($userbuffer)) $mediauser[$id] = $userbuffer; 

        $mediafile[$id] = urldecode ($mediafile[$id]);

        // encode script code
        $mediafile[$id] = scriptcode_encode ($mediafile[$id]);
        
        // escape special characters (transform all special chararcters into their html/xml equivalents)
        $mediafile[$id] = str_replace (array("&", "<", ">"), array("&amp;", "&lt;", "&gt;"), $mediafile[$id]);
        $mediaalign[$id] = str_replace (array("&", "<", ">"), array("&amp;", "&lt;", "&gt;"), $mediaalign[$id]);
        $mediawidth[$id] = str_replace (array("&", "<", ">"), array("&amp;", "&lt;", "&gt;"), $mediawidth[$id]);
        $mediaheight[$id] = str_replace (array("&", "<", ">"), array("&amp;", "&lt;", "&gt;"), $mediaheight[$id]);

        // encode script code
        $mediaalttext[$id] = scriptcode_encode ($mediaalttext[$id]);

        // encode special characters in alternative text
        $mediaalttext[$id] = html_encode ($mediaalttext[$id]);

        // check media file (url or component link)
        if (substr_count ($mediafile[$id], "://") == 0)
        { 
          // load object file
          $location = substr ($mediaobject[$id], 0, strrpos ($mediaobject[$id], "/")+1);
          $pagedata = loadfile_fast ($location, getobject ($mediaobject[$id]));

          if ($pagedata != false) 
          {
            // get media file name
            $mediafile_name = getfilename ($pagedata, "media");

            // get publication from objectpath
            $site_media = getpublication ($mediaobject[$id]);

            // define media file
            if ($mediafile_name != false) $mediafile[$id] = $site_media."/".$mediafile_name;
          }
        }
        else $mediaobject[$id] = "";

        // convert path
        $mediaobject[$id] = convertpath ($site, $mediaobject[$id], "comp");

        // extract old object reference (for link management)
        $mediaobject_curr[$id] = "";
        $temp_array = selectcontent ($contentdata, "<media>", "<media_id>", $id);

        if (!empty ($temp_array[0]))
        {
          $temp_array = getcontent ($temp_array[0], "<mediaobject>");
          if (!empty ($temp_array[0])) $mediaobject_curr[$id] = $temp_array[0];
        }

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
          if (!empty ($mediauser[$id])) $contentdatanew = setcontent ($contentdatanew, "<media>", "<mediauser>", $mediauser[$id], "<media_id>", $id);
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
          if (!empty ($mediauser[$id])) $contentdatanew = setcontent ($contentdatanew, "<media>", "<mediauser>", $mediauser[$id], "<media_id>", $id, true);
        }

        // ------------------------- add link to link management file ---------------------------

        $link_db = link_db_update ($site, $link_db, "link", $contentfile, "comp", $mediaobject_curr[$id], $mediaobject[$id], "unique");

        $contentdata = $contentdatanew;
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

      $errcode = "20510";
      $error[] = $mgmt_config['today']."|hypercms_set.inc.php|error|".$errcode."|Link management file is missing or you do not have write permissions for ".$site.".link.dat";
    }

    // return container
    if ($contentdatanew != false)
    {
      // relational DB connectivity
      if (!empty ($mgmt_config['db_connect_rdbms']))
      {
        $container_id = substr ($contentfile, 0, strpos ($contentfile, ".xml"));

        $text_array = array();
        $type = array();

        foreach ($mediaalttext as $key => $value)
        {
          // get object ID
          if (!empty ($mediaobject[$key])) $object_id = $mediaobject[$key]."|";
          else $object_id = "";

          $text_array["media:".$key] = $object_id.$value;
          $type_array["media:".$key] = "media";
        }
 
        rdbms_setcontent ($site, $container_id, $text_array, $type_array, $user); 
      }

      return $contentdatanew;
    }
    else return false;
  }
  else return false;
}

// -------------------------------------------- setpagelink -----------------------------------------------
// function: setpagelink()
// input: publication name [string], container (XML) [string], container name [string], new link with tag ID as key and link reference as value [array], link target [array] (optional), link text [array] (optional), 
//          article [array or string] [yes,no] (optional), content user [array or string] (optional), user name [string] (optional), character set of text content [string] (optional)
// output: updated content container (XML), false on error

// description:
// Set link content in container and database. The content container will be returned and not saved. 

function setpagelink ($site, $contentdata, $contentfile, $linkhref=array(), $linktarget=array(), $linktext=array(), $art="no", $linkuser=array(), $user="sys", $charset="")
{
  global $mgmt_config;

  if (valid_publicationname ($site) && $contentdata != "" && valid_objectname ($contentfile) && is_array ($linkhref) && (is_array ($art) || $art != "") && valid_objectname ($user) && is_array ($mgmt_config))
  {
    // initialize
    $error = array();
    if (!is_array ($linktarget)) $linktarget = array();
    if (!is_array ($linktext)) $linktext = array();

    if (!is_array ($linkuser))
    {
      $userbuffer = $linkuser;
      $linkuser = Null;
    }

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

    reset ($linkhref);

    foreach ($linkhref as $id => $temp)
    {
      if ($id != "")
      {
        // set values if not set
        if (!isset ($linkhref[$id])) $linkhref[$id] = "";
        if (!isset ($linktarget[$id])) $linktarget[$id] = "";
        if (!isset ($linktext[$id])) $linktext[$id] = "";

        // set array if input parameter is string
        if (!empty ($artbuffer)) $art[$id] = $artbuffer;
        if (!empty ($userbuffer)) $linkuser[$id] = $userbuffer;

        $linkhref[$id] = urldecode ($linkhref[$id]);

        // convert path
        $linkhref[$id] = convertpath ($site, $linkhref[$id], "page");

        // encode script code
        $linkhref[$id] = scriptcode_encode ($linkhref[$id]);

        // escape special characters
        $linkhref[$id] = str_replace ("&", "&amp;", $linkhref[$id]);
        $linkhref[$id] = str_replace ("<", "&lt;", $linkhref[$id]);
        $linkhref[$id] = str_replace (">", "&gt;", $linkhref[$id]);

        // encode script code
        $linktext[$id] = urldecode ($linktext[$id]);
        $linktext[$id] = scriptcode_encode ($linktext[$id]);

        // escape special characters
        if (!empty ($charset))
        {
          $linktext[$id] = html_encode ($linktext[$id], $charset);
        }
        else
        {
          $linktext[$id] = str_replace ("&", "&amp;", $linktext[$id]);
          $linktext[$id] = str_replace ("<", "&lt;", $linktext[$id]);
          $linktext[$id] = str_replace (">", "&gt;", $linktext[$id]); 
        }

        // extract old object reference (for link management)
        $linkhref_curr[$id] = "";
        $temp_array = selectcontent ($contentdata, "<link>", "<link_id>", $id);

        if (!empty ($temp_array[0]))
        {
          $temp_array = getcontent ($temp_array[0], "<linkhref>");
          if (!empty ($temp_array[0])) $linkhref_curr[$id] = $temp_array[0];
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

          $contentdatanew = setcontent ($contentdatanew, "<link>", "<linktarget>", trim ($linktarget[$id]), "<link_id>", $id, true);
          $contentdatanew = setcontent ($contentdatanew, "<link>", "<linktext>", "<![CDATA[".trim ($linktext[$id])."]]>", "<link_id>", $id, true);
          if (!empty ($linkuser[$id])) $contentdatanew = setcontent ($contentdatanew, "<link>", "<linkuser>", $linkuser[$id], "<link_id>", $id, true);
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

          $contentdatanew = setcontent ($contentdatanew, "<link>", "<linktarget>", trim ($linktarget[$id]), "<link_id>", $id, true);
          $contentdatanew = setcontent ($contentdatanew, "<link>", "<linktext>", "<![CDATA[".trim ($linktext[$id])."]]>", "<link_id>", $id, true);
          if (!empty ($linkuser[$id])) $contentdatanew = setcontent ($contentdatanew, "<link>", "<linkuser>", $linkuser[$id], "<link_id>", $id, true);
        }

        // ------------------------- add link to link management file ---------------------------

        $link_db = link_db_update ($site, $link_db, "link", $contentfile, "page", $linkhref_curr[$id], $linkhref[$id], "unique"); 

        $contentdata = $contentdatanew;
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
      $error[] = $mgmt_config['today']."|hypercms_set.inc.php|error|".$errcode."|Link management file is missing or you do not have write permissions for ".$site.".link.dat";
    }

    // save log
    savelog (@$error); 

    // return container
    if ($contentdatanew != false)
    {
      // relational DB connectivity
      if (!empty ($mgmt_config['db_connect_rdbms']))
      {
        $container_id = substr ($contentfile, 0, strpos ($contentfile, ".xml"));

        $text_array = array();
        $type_array = array();

        foreach ($linktext as $key => $value)
        {
          // get object ID
          if (!empty ($linkhref[$key])) $object_id = $linkhref[$key]."|";
          else $object_id = "";

          $text_array["link:".$key] = $object_id.$value;
          $type_array["link:".$key] = "link";
        }

        rdbms_setcontent ($site, $container_id, $text_array, $type_array, $user); 
      }

      return $contentdatanew;
    }
    else return false;
  }
  else return false;
}

// -------------------------------------------- setcomplink -----------------------------------------------
// function: setcomplink()
// input: publication name [string], container (XML) [string], container name [string], new components with tag ID as key and component reference as value [array], conditions [array] (optional), 
//          article [array or string] [yes,no] (optional), content user [array or string] (optional), user name [string] (optional)
// output: updated content container (XML), false on error

// description:
// Set component link content in container and database. The content container will be returned and not saved. 

function setcomplink ($site, $contentdata, $contentfile, $component=array(), $condition=array(), $art="no", $compuser=array(), $user="sys")
{
  global $mgmt_config;

  if (valid_publicationname ($site) && $contentdata != "" && valid_objectname ($contentfile) && is_array ($component) && (is_array ($art) || $art != "") && valid_objectname ($user) && is_array ($mgmt_config))
  {
    // initialize
    $error = array();
    if (!is_array ($condition)) $condition = array();

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

    // load xml schema
    $component_schema_xml = chop (loadfile ($mgmt_config['abs_path_cms']."xmlsubschema/", "component.schema.xml.php"));
    $article_schema_xml = chop (loadfile ($mgmt_config['abs_path_cms']."xmlsubschema/", "article.schema.xml.php"));

    // load link db
    $link_db = link_db_load ($site, $user); 

    reset ($component);

    foreach ($component as $id => $temp)
    {
      if ($id != "")
      {
        // correct extension if object is unpublished
        if (strpos ($component[$id]."|", ".off|") > 0) $component[$id] = str_replace (".off|", "|", $component[$id]."|");

        // remove | at the end
        $component[$id] = trim ($component[$id], "|");

        // convert path
        $component[$id] = convertpath ($site, $component[$id], "comp");

        // convert object path to object ID if DAM
        $component_object_id[$id] = getobjectid ($component[$id]);

        // save object ID if DAM
        if ($mgmt_config[$site]['dam']) $component_conv[$id] = $component_object_id[$id];
        else $component_conv[$id] = $component[$id];

        // set array if input parameter is string
        if (!empty ($artbuffer)) $art[$id] = $artbuffer;
        if (!empty ($userbuffer)) $compuser[$id] = $userbuffer;

        // extract old object reference (for link management)
        $component_curr[$id] = "";
        $temp_array = selectcontent ($contentdata, "<component>", "<component_id>", $id);

        if (!empty ($temp_array[0]))
        {
          $temp_array = getcontent ($temp_array[0], "<componentfiles>");

          if (!empty ($temp_array[0]))
          {
            $temp_array[0] = trim ($temp_array[0]);
            // remove | at the end
            $temp_array[0] = trim ($temp_array[0], "|");
            $component_curr[$id] = $temp_array[0];
          }
        }

        // check if page component
        if ($art[$id] == "no")
        {
          // set the new content
          $contentdatanew = setcontent ($contentdata, "<component>", "<componentfiles>", trim ($component_conv[$id]), "<component_id>", $id);

          if ($contentdatanew == false)
          {
            $contentdatanew = addcontent ($contentdata, $component_schema_xml, "", "", "", "<componentcollection>", "<component_id>", $id);
            $contentdatanew = setcontent ($contentdatanew, "<component>", "<componentfiles>", trim ($component_conv[$id]), "<component_id>", $id);
          }

          if (!empty ($compuser[$id])) $contentdatanew = setcontent ($contentdatanew, "<component>", "<componentuser>", $compuser[$id], "<component_id>", $id);
          if (isset ($condition[$id])) $contentdatanew = setcontent ($contentdatanew, "<component>", "<componentcond>", $condition[$id], "<component_id>", $id);
        }
        // check if article component
        elseif ($art[$id] == "yes")
        {
          // get the id of the article
          $artid = getartid ($id);

          // set the new content
          $contentdatanew = setcontent ($contentdata, "<component>", "<componentfiles>", trim ($component_conv[$id]), "<component_id>", $id);

          if ($contentdatanew == false)
          {
            $contentdatanew = addcontent ($contentdata, $component_schema_xml, "<article>", "<article_id>", $artid, "<articlecomponentcollection>", "<component_id>", $id);

            if ($contentdatanew == false)
            {
              $contentdatanew = addcontent ($contentdata, $article_schema_xml, "", "", "", "<articlecollection>", "<article_id>", $artid);
              $contentdatanew = addcontent ($contentdatanew, $component_schema_xml, "<article>", "<article_id>", $artid, "<articlecomponentcollection>", "<component_id>", $id);
            }
            $contentdatanew = setcontent ($contentdatanew, "<component>", "<componentfiles>", trim ($component_conv[$id]), "<component_id>", $id);
          }

          if (!empty ($compuser[$id])) $contentdatanew = setcontent ($contentdatanew, "<component>", "<componentuser>", $compuser[$id], "<component_id>", $id);
          if (isset ($condition[$id])) $contentdatanew = setcontent ($contentdatanew, "<component>", "<componentcond>", $condition[$id], "<component_id>", $id);
        }

        // ------------------------- add link to link management file ---------------------------

        $link_db = link_db_update ($site, $link_db, "link", $contentfile, "comp", $component_curr[$id], $component[$id], "unique");

        if (!empty ($contentdatanew)) $contentdata = $contentdatanew;
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
      $error[] = $mgmt_config['today']."|hypercms_set.inc.php|error|".$errcode."|Link management file is missing or you do not have write permissions for ".$site.".link.dat";
    }

    // save log
    savelog (@$error);

    // return container
    if (!empty ($contentdatanew))
    {
      // relational DB connectivity
      if (!empty ($mgmt_config['db_connect_rdbms']))
      {
        $container_id = substr ($contentfile, 0, strpos ($contentfile, ".xml"));

        $text_array = array();
        $type_array = array();

        foreach ($component_object_id as $key => $value)
        {
          // multiple components
          if (!empty ($value) && strpos ("_".$value, "|") > 0)
          {
            $object_ids = explode ("|", $value);

            if (is_array ($object_ids) && sizeof ($object_ids) > 0)
            {
              foreach ($object_ids as $object_id)
              {
                $i = 0;

                if ($object_id != "")
                {
                  if (empty ($object_id)) $object_id = "0";
                  $text_array["comp:".$key.":".$i] = $object_id."|";
                  $type_array["comp:".$key.":".$i] = "comp";
                  $i++;
                }
              }
            }
          }
          // single component
          else
          {
            if (empty ($value)) $value = "0";
            $text_array["comp:".$key] = $value."|";
            $type_array["comp:".$key] = "comp";
          }
        }

        rdbms_setcontent ($site, $container_id, $text_array, $type_array, $user);
      }

      return $contentdatanew;
    }
    else return false;
  }
  else return false;
}

// ------------------------------------------- sethead -------------------------------------------
// function: sethead()
// input: publication name [string], container (XML) [string], container name [string], head content with tagname as ID and text as value [array], user name [string] (optional), character set of text content [string] (optional)
// output: updated content container (XML), false on error

// description:
// Only used for content in general head information of container.

function sethead ($site, $contentdata, $contentfile, $headcontent=array(), $user="sys", $charset="")
{
  global $mgmt_config;

  if (valid_publicationname ($site) && $contentdata != "" && valid_objectname ($contentfile) && is_array ($headcontent) && valid_objectname ($user) && is_array ($mgmt_config))
  {
    reset ($headcontent);

    foreach ($headcontent as $tagname => $content)
    {
      if ($tagname != "") 
      {
        // escape special characters (transform all special chararcters into their html/xml equivalents)
        // except the code of page customer tracking
        if ($tagname != "pagetracking" && $content != "")
        {
          // encode script code
          $content = scriptcode_encode ($content);

          // encode special characters
          $content = html_encode ($content);
        }

        $contentdata = setcontent ($contentdata, "<head>", "<".$tagname.">", "<![CDATA[".trim ($content)."]]>", "", "", true);

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
      if (!empty ($mgmt_config['db_connect_rdbms']))
      {
        $text_array = array();
        $type_array = array();

        // standard text-based meta information
        if (isset ($headcontent['pagetitle']))
        {
          $text_array['head:pagetitle'] = $headcontent['pagetitle'];
          $type_array['head:pagetitle'] = "head";
        }

        if (isset ($headcontent['pageauthor']))
        {
          $text_array['head:pageauthor'] = $headcontent['pageauthor'];
          $type_array['head:pagetitle'] = "head";
        }

        if (isset ($headcontent['pagedescription']))
        {
          $text_array['head:pagedescription'] = $headcontent['pagedescription'];
          $type_array['head:pagetitle'] = "head";
        }

        if (isset ($headcontent['pagekeywords']))
        {
          $text_array['head:pagekeywords'] = $headcontent['pagekeywords'];
          $type_array['head:pagetitle'] = "head";
        }

        $container_id = substr ($contentfile, 0, strpos ($contentfile, ".xml"));

        rdbms_setcontent ($site, $container_id, $text_array, $type_array, $user);
      }

      return $contentdata;
    }
    else return false;
  }
  else return false; 
}

// ---------------------------------------- setrelation --------------------------------------------
// function: setrelation()
// input: publication name [string], location path 1 [string], object name 1 for component link reference 2 [string], tag/content ID 1 for component reference to object 2 [string] (optional), 
//        location path 2 [string], object name 2 for component link reference 2 [string], tag/content ID 2 for component reference to object 1 [string] (optional), user name [string]
// output: true / false on error

// description:
// This function sets a relationship between two objects by adding the reference as a multi component link to the specified tag ID of both objects.

function setrelation ($site, $location_1="", $object_1="", $id_1="Related", $location_2="", $object_2="", $id_2="Related", $user="")
{
  global $mgmt_config;

  if (valid_publicationname ($site) && valid_locationname ($location_1) && valid_objectname ($object_1) && valid_locationname ($location_2) && valid_objectname ($object_2))
  {
    // initialize
    $error = array();

    // convert locations and get object IDs
    $location_1 = deconvertpath ($location_1, "file");
    $location_esc_1 = convertpath ($site, $location_1, "comp");
    $objectid_1 = getobjectid ($location_esc_1.$object_1);

    $location_2 = deconvertpath ($location_2, "file");
    $location_esc_2 = convertpath ($site, $location_2, "comp");
    $objectid_2 = getobjectid ($location_esc_2.$object_2);

    // set component link for object 1
    if (!empty ($id_1))
    {
      $component_1 = $objectid_2."|";
      $component_curr_1 = "";

      // load object file and get container
      $objectdata_1 = loadfile ($location_1, $object_1);
      $contentfile_1 = getfilename ($objectdata_1, "content");

      // read content from content container
      if (!empty ($contentfile_1))
      {
        $container_id_1 = substr ($contentfile_1, 0, strpos ($contentfile_1, ".xml"));
        $contentdata_1 = loadcontainer ($container_id_1, "work", $user);
        $temp_array = selectcontent ($contentdata_1, "<component>", "<component_id>", $id_1);

        if (!empty ($temp_array[0]))
        {
          $temp_array = getcontent ($temp_array[0], "<componentfiles>");
          if (!empty ($temp_array[0])) $component_curr_1 = trim ($temp_array[0], "|");
        }

        // set component link
        $contentdatanew_1 = setcomplink ($site, $contentdata_1, $contentfile_1, array($id_1 => $component_curr_1."|".$component_1), "", "no", $user, $user);

        // save working xml content container file
        if (!empty ($contentdatanew_1)) $save_1 = savecontainer ($container_id_1, "work", $contentdatanew_1, $user);

        if (empty ($save_1))
        {
          $errcode = "20601";
          $error[] = $mgmt_config['today']."|hypercms_set.inc.php|error|".$errcode."|Relation to ".$location_esc_2.$object_2." could not be saved for ".$location_esc_1.$object_1; 
        }
      }
    }

    // set component link for object 2
    if (!empty ($id_2))
    {
      $component_2 = $objectid_1."|";
      $component_curr_2 = "";

      // load object file and get container
      $objectdata_2 = loadfile ($location_2, $object_2);
      $contentfile_2 = getfilename ($objectdata_2, "content");

      // read content from content container
      if (!empty ($contentfile_2))
      {
        $container_id_2 = substr ($contentfile_2, 0, strpos ($contentfile_2, ".xml"));
        $contentdata_2 = loadcontainer ($container_id_2, "work", $user);
        $temp_array = selectcontent ($contentdata_2, "<component>", "<component_id>", $id_2);

        if (!empty ($temp_array[0]))
        {
          $temp_array = getcontent ($temp_array[0], "<componentfiles>");
          if (!empty ($temp_array[0])) $component_curr_2 = trim ($temp_array[0], "|");
        }

        // set component link
        $contentdatanew_2 = setcomplink ($site, $contentdata_2, $contentfile_2, array($id_2 => $component_curr_2."|".$component_2), "", "no", $user, $user);

        // save working xml content container file
        if (!empty ($contentdatanew_2)) $save_2 = savecontainer ($container_id_2, "work", $contentdatanew_2, $user);

        if (empty ($save_2))
        {
          $errcode = "20602";
          $error[] = $mgmt_config['today']."|hypercms_set.inc.php|error|".$errcode."|Relation to ".$location_esc_1.$object_1." could not be saved for ".$location_esc_2.$object_2; 
        }
      }
    }

    // save log
    savelog (@$error);

    if (!empty ($save_1) && !empty ($save_2)) return true;
    else return false;
  }
  else return false;
}

// ===================================== FILEPOINTER =======================================

// ------------------------------------- setfilename ------------------------------------------

// function: setfilename()
// input: file content [string], hyperCMS tag name in page or component [content,template,media,name], new value [string]
// output: filedata/false on error

// description:
// Sets or creates the file name of the hyperCMS content file, template file, media file or file name pointer

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

    // set file name in code
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

// ================================== USER GUI SETTINGS =====================================

// --------------------------------------- setuserboxes -------------------------------------------
// function: setuserboxes ()
// input: home box file names [array or string], user name [string]
// output: true / false

function setuserboxes ($name_array, $user)
{
  global $mgmt_config;

  if (valid_objectname ($user))
  {
    $dir = $mgmt_config['abs_path_data']."checkout/";
    $file = $user.".home.dat";

    // set input as array if it is string and not empty
    if (!is_array ($name_array))
    {
      $name_array = trim ($name_array, "|");

      // provided input has seperators
      if (substr_count ($name_array, "|") > 0)
      {
        $name_array = explode ("|", $name_array);
      }
      // provided input is single value as string
      else
      {
        $temp = $name_array;
        $name_array = array();
        $name_array[0] = $temp;
      }
    }

    $data = "|";

    // remove trailing slashes
    if (!empty ($mgmt_config['homeboxes_directory'])) $mgmt_config['homeboxes_directory'] = trim ($mgmt_config['homeboxes_directory'], "/");

    // prepare home boxes
    foreach ($name_array as $name)
    {
      if (valid_objectname ($name) && is_file ($mgmt_config['abs_path_cms']."box/".$name.".inc.php"))
      {
        $data .= $name."|";
      }
      elseif (!empty ($mgmt_config['homeboxes_directory']) && valid_locationname ($name) && strpos ($name, "/") > 0)
      {
        list ($site, $temp) = explode ("/", $name);

        if (is_file ($mgmt_config['abs_path_comp'].$site."/".$mgmt_config['homeboxes_directory']."/".$temp.".php"))
        {
          $data .= $name."|";
        }
      }
    }

    // save file
    return savefile ($dir, $file, $data);
  }
  else return false;
}

// --------------------------------------- setguiview -------------------------------------------
// function: setguiview ()
// input: object view name [formedit,cmsview,inlineview], explorer view name [detail,small,medium,large], show sidebar [true=1,false=0], user name [string]
// output: true / false

function setguiview ($objectview, $explorerview, $sidebar, $user)
{
  global $mgmt_config;

  if (($objectview == "formedit" || $objectview == "cmsview" || $objectview == "inlineview") && ($explorerview == "detail" || $explorerview == "small" || $explorerview == "medium" || $explorerview == "large") && valid_objectname ($user))
  {
    $dir = $mgmt_config['abs_path_data']."checkout/";
    $file = $user.".gui.dat";

    if (!empty ($sidebar)) $sidebar = "1";
    else $sidebar = "0";

    $view = $objectview."|".$explorerview."|".$sidebar;

    // save file
    return savefile ($dir, $file, $view);
  }
  else return false;
}

// --------------------------------------- settoolbarfunctions -------------------------------------------
// function: settoolbarfunctions ()
// input: toolbar functions [array], user name [string]
// output: true / false

function settoolbarfunctions ($toolbar, $user)
{
  global $mgmt_config;

  if (is_array ($toolbar) && valid_objectname ($user))
  {
    $dir = $mgmt_config['abs_path_data']."checkout/";
    $file = $user.".toolbar.json";

    // if toolbar personalization is enabled
    if (!empty ($mgmt_config['toolbar_functions']))
    {
      // JSON encode array
      $settings = json_encode ($toolbar);
    }
    // remove settings
    else
    {
      $toolbar = NULL;
      $settings = "";
    }

    // set in session
    if ($user == getsession ("hcms_user"))
    {
      setsession ("hcms_toolbarfunctions", $toolbar, true);
    }

    // save file
    return savefile ($dir, $file, $settings);
  }
  else return false;
}
?>