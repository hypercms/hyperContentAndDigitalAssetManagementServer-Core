<?php
/*
 * This file is part of
 * hyper Content & Digital Management Server - http://www.hypercms.com
 * Copyright (c) by hyper CMS Content Management Solutions GmbH
 *
 * You should have received a copy of the License along with hyperCMS.
 */
  
// ========================================== MEDIA FUNCTIONS =======================================

// ---------------------------------------- indexcontent --------------------------------------------
// function: indexcontent()
// input: publication name, path to multimedia file, multimedia file name (file to be indexed), container name or ID (optional), container XML-content (optional), user name
// output: true / false

// description:
// This function extracts the text content of multimedia objects and writes it the text to the container.
// The given charset of the publication (not set by default), container or publication (not set by default) will be used.
// The default character set of default.meta.tpl is UTF-8, so all content should be saved in UTF-8.

function indexcontent ($site, $location, $file, $container="", $container_content="", $user)
{
  global $mgmt_config, $mgmt_parser, $mgmt_uncompress, $hcms_ext, $hcms_lang, $lang;

  if (valid_publicationname ($site) && valid_locationname ($location) && valid_objectname ($file) && valid_objectname ($user))
  {
    $usedby = "";

    // load file extensions
    if (empty ($hcms_ext) || !is_array ($hcms_ext)) require ($mgmt_config['abs_path_cms']."include/format_ext.inc.php");
    
    // add slash if not present at the end of the location string
    if (substr ($location, -1) != "/") $location = $location."/";            
  
    // get file extension
    $file_ext = strtolower (strrchr ($file, "."));
    
    // get container from media file
    if (!valid_objectname ($container))
    {
      $container = getmediacontainername ($file);
    }
    
    // get container id
    if (substr_count ($container, ".xml") > 0)
    {
      $container_id = substr ($container, 0, strpos ($container, ".xml"));
    }
    elseif (is_numeric ($container))
    {
      $container_id = $container;
      $container = $container.".xml";
    }
    
    // read content container
    if ($container_content == "")
    {
      $result = getcontainername ($container);

      if (!empty ($result['container']))
      {
        $container = $result['container'];
        $usedby = $result['user'];
        $container_content = loadcontainer ($container, "work", $user);
      }
    }

    if (!empty ($container_content) && ($usedby == "" || $usedby == $user) && $file_ext != "")
    {
      // prepare media file
      $temp = preparemediafile ($site, $location, $file, $user);

      if ($temp['result'] && $temp['crypted'])
      {
        $location = $temp['templocation'];
        $file = $temp['tempfile'];
      }
      elseif ($temp['restored'])
      {
        $location = $temp['location'];
        $file = $temp['file'];
      }

      // verify local media file
      if (!is_file ($location.$file)) return false;

      // ------------------------ Adobe PDF -----------------------
      // get file content from PDF
      if (($file_ext == ".pdf" || $file_ext == ".ai") && $mgmt_parser['.pdf'] != "")
      {
        // use of XPDF to parse PDF files.
        // please note: the executable "pdftotext" must be copied to "bin" directory!
        // as pdftotext is compiled for several platforms you have to know which
        // OS you are using for the content management server.
        // known problems: MS IIS causes troubles executing XPDF (unable to fork...), set permissions for cmd.exe  
        // the second argument "-" tells XPDF to output the text to stdout.
        // content should be provided using UTF-8 as charset.
        @exec ($mgmt_parser['.pdf']." -enc UTF-8 \"".shellcmd_encode ($location.$file)."\" -", $file_content, $errorCode); 

        if ($errorCode)
        {
          $file_content = "";
          
          $errcode = "20132";
          $error[] = $mgmt_config['today']."|hypercms_media.inc.php|error|$errcode|exec of pdftotext (code:$errorCode) failed in indexcontent for file: ".$location.$file;   
        }
        elseif (is_array ($file_content))
        {
          $file_content = implode ("\n", $file_content);
        }
        else $file_content = "";  
      }
      
      // ------------------------ OPEN OFFICE -----------------------
      // get file content from Open Office Text (odt) in UTF-8
      elseif (($file_ext == ".odt" || $file_ext == ".ods" || $file_ext == ".odp") && $mgmt_uncompress['.zip'] != "")   
      {
        // temporary directory for extracting file
        $temp_name = uniqid ("index");
        $temp_dir = $mgmt_config['abs_path_temp'].$temp_name."/";
        
        // create temporary directory for extraction
        @mkdir ($temp_dir, $mgmt_config['fspermission']);
          
        // .odt is a ZIP-file with the content placed in the file content.xml
        $cmd = $mgmt_uncompress['.zip']." \"".shellcmd_encode ($location.$file)."\" content.xml -d \"".shellcmd_encode ($temp_dir)."\"";
        
        @exec ($cmd, $error_array);

        if (is_array ($error_array) && substr_count (implode ("<br />", $error_array), "error") > 0)
        {
          $errcode = "20133";
          $error[] = $mgmt_config['today']."|hypercms_media.inc.php|error|$errcode|unzip failed for: ".$location.$file."<br />".implode ("<br />", $error_array);   
        } 
        else
        {
          $file_content = loadfile ($temp_dir, "content.xml");
          
          if ($file_content != false)
          {
            // add whitespaces before newline
            $file_content = str_replace ("</", " </", $file_content);
            
            // replace paragraph and newline with real newlines
            $file_content = str_replace (array ("</text:p>", "<text:line-break/>"), array ("\n\n", "\n"), $file_content);
            
            // remove multiple white spaces
            $file_content = preg_replace ('/\s+/', ' ', $file_content);
          }
   
          // remove temp directory
          deletefile ($mgmt_config['abs_path_temp'], $temp_name, 1);
        }
      }      
      // ------------------------ MS WORD -----------------------
      // get file content from MS Word before 2007 (doc) in UTF-8
      elseif (($file_ext == ".doc") && $mgmt_parser['.doc'] != "")
      {
        @exec ($mgmt_parser['.doc']." -t -i 1 -m UTF-8.txt \"".shellcmd_encode ($location.$file)."\"", $file_content, $errorCode); 

        if ($errorCode)
        {
          $file_content = ""; 
          
          $errcode = "20134";
          $error[] = $mgmt_config['today']."|hypercms_media.inc.php|error|$errcode|exec of antiword (code:$errorCode) failed in indexcontent for file: ".$location.$file;   
        }
        elseif (is_array ($file_content))
        {
          $file_content = implode ("\n", $file_content);
        }
        else $file_content = "";        
      }
      // get file content from MS Word 2007+ (docx) in UTF-8
      elseif (($file_ext == ".docx") && $mgmt_uncompress['.zip'] != "")
      {
        // temporary directory for extracting file
        $temp_name = uniqid ("index");
        $temp_dir = $mgmt_config['abs_path_temp'].$temp_name."/";
        
        // create temporary directory for extraction
        @mkdir ($temp_dir, $mgmt_config['fspermission']);
        
        // docx is a ZIP-file with the content placed in the file word/document.xml
        $cmd = $mgmt_uncompress['.zip']." \"".shellcmd_encode ($location.$file)."\" word/document.xml -d \"".shellcmd_encode ($temp_dir)."\"";
        
        @exec ($cmd, $error_array);

        if (is_array ($error_array) && substr_count (implode ("<br />", $error_array), "error") > 0)
        {
          $errcode = "20134";
          $error[] = $mgmt_config['today']."|hypercms_media.inc.php|error|$errcode|unzip failed for: ".$location.$file."<br />".implode ("<br />", $error_array);   
        } 
        else
        {
          $file_content = loadfile ($temp_dir."word/", "document.xml");
          
          if ($file_content != false)
          {
            // add whitespaces before newline
            $file_content = str_replace ("</", " </", $file_content);
            
            // replace paragraph and newline with real newlines
            $file_content = str_replace (array ("</w:p>", "<w:br/>"), array ("\n\n", "\n"), $file_content);
            
            // remove multiple white spaces
            $file_content = preg_replace ('/\s+/', ' ', $file_content);
          }
   
          // remove temp directory
          deletefile ($mgmt_config['abs_path_temp'], $temp_name, 1);
        }             
      }
      // ------------------------ MS EXCEL -----------------------
      // get file content from MS EXCEL 2007 (xlsx) in UTF-8
      elseif (($file_ext == ".xlsx") && $mgmt_uncompress['.zip'] != "")
      {
        // temporary directory for extracting file
        $temp_name = uniqid ("index");
        $temp_dir = $mgmt_config['abs_path_temp'].$temp_name."/";
        
        // create temporary directory for extraction
        @mkdir ($temp_dir, $mgmt_config['fspermission']);
        
        // xlsx is a ZIP-file with the content placed in the file xl/sharedStrings.xml
        $cmd = $mgmt_uncompress['.zip']." \"".shellcmd_encode ($location.$file)."\" xl/sharedStrings.xml -d \"".shellcmd_encode ($temp_dir)."\"";
        
        @exec ($cmd, $error_array);

        if (is_array ($error_array) && substr_count (implode ("<br />", $error_array), "error") > 0)
        {
          $errcode = "20134";
          $error[] = $mgmt_config['today']."|hypercms_media.inc.php|error|$errcode|unzip failed for: ".$location.$file."<br />".implode ("<br />", $error_array);   
        } 
        else
        {
          $file_content = loadfile ($temp_dir."xl/", "sharedStrings.xml");
          
          if ($file_content != false)
          {
            // add whitespaces
            $file_content = str_replace ("</", " </", $file_content);
          }
   
          // remove temp directory
          deletefile ($mgmt_config['abs_path_temp'], $temp_name, 1);
        }             
      }      
      // ------------------------ MS Powerpoint -----------------------
      // get file content from MS Powerpoint before 2007 (ppt) in UTF-8
      elseif ($file_ext == ".ppt" || $file_ext == ".pps")
      {
        // This approach uses detection of the string "chr(0f).Hex_value.chr(0x00).chr(0x00).chr(0x00)" to find text strings, 
        // which are then terminated by another NUL chr(0x00). [1] Get text between delimiters [2] 
        $filehandle = fopen ($location.$file, "r");
        
        if ($filehandle != false)
        {
          $line = @fread ($filehandle, filesize ($location.$file));
          $lines = explode (chr(0x0f), $line);
          
          foreach ($lines as $thisline)
          {
            if (strpos ($thisline, chr(0x00).chr(0x00).chr(0x00)) == 1)
            {
              $text_line = substr ($thisline, 4);
              $end_pos   = strpos ($text_line, chr(0x00));
              $text_line = substr ($text_line, 0, $end_pos);              
              $text_line = preg_replace ("/[^a-zA-Z0-9Ã€ÃÃ‚ÃƒÃ„Ã…Ã†Ã‡ÃˆÃ‰ÃŠÃ‹ÃŒÃÃŽÃÃÃ‘Ã’Ã“Ã”Ã•Ã–Ã™ÃšÃ›ÃœÃÃŸÃ Ã¡Ã¢Ã£Ã¤Ã¥Ã¦Ã§Ã¨Ã©ÃªÃ«Ã¬Ã­Ã®Ã¯Ã±Ã²Ã³Ã´ÃµÃ¶Ã¹ÃºÃ»Ã¼Ã½Ã¾Ã¿\s\,\.\-\n\r\t@\/\_\(\)]/", "", $text_line);
              
              if (strlen ($text_line) > 1)
              {
                $file_content .= substr ($text_line, 0, $end_pos)."\n";
              }
            }
          } 
        }  
        
        if ($file_content != "")
        {        
          // detect charset
          if (function_exists ("mb_detect_encoding")) $charset_source = mb_detect_encoding ($file_content);
          elseif (is_latin1 ($file_content)) $charset_source = "ISO-8859-1";
          
          // convert to UTF-8
          if ($charset_source != "" && $charset_source != "UTF-8")
          {
            $file_content = convertchars ($file_content, $charset_source, "UTF-8");
          }
        }
        else
        {
          $errcode = "20135";
          $error[] = $mgmt_config['today']."|hypercms_media.inc.php|error|$errcode|extraction of content from powerpoint failed in indexcontent for file: ".$location.$file;   
        }           
      }      
      // get file content from MS Powerpoint 2007 (pptx) in UTF-8
      elseif (($file_ext == ".pptx" || $file_ext == ".ppsx") && $mgmt_uncompress['.zip'] != "")
      {
        // temporary directory for extracting file
        $temp_name = uniqid ("index");
        $temp_dir = $mgmt_config['abs_path_temp'].$temp_name."/";
        
        // create temporary directory for extraction
        @mkdir ($temp_dir, $mgmt_config['fspermission']);
        
        // pptx is a ZIP-file with the content placed in the file ppt/slides/slide#.xml (# ... number of the slide)
        $cmd = $mgmt_uncompress['.zip']." \"".shellcmd_encode ($location.$file)."\" ppt/slides/slide* -d \"".shellcmd_encode ($temp_dir)."\"";
        
        @exec ($cmd, $error_array);

        if (is_array ($error_array) && substr_count (implode ("<br />", $error_array), "error") > 0)
        {
          $errcode = "20136";
          $error[] = $mgmt_config['today']."|hypercms_media.inc.php|error|$errcode|unzip failed for: ".$location.$file."<br />".implode ("<br />", $error_array);   
        } 
        else
        {
          $dir = @opendir ($temp_dir."ppt/slides/");
      
          if ($dir != false)
          {
            // collect source files
            while ($file = @readdir ($dir))
            { 
              if (substr_count ($file, ".xml") == 1)
              {    
                $file_temp = loadfile ($temp_dir."ppt/slides/", $file);
                
                if ($file_temp != false)
                {
                  // add whitespaces
                  $file_temp = str_replace ("</", " </", $file_temp);
                  $file_content = $file_content." ".strip_tags ($file_temp);
                }
              }
            }
          }
   
          // remove temp directory
          deletefile ($mgmt_config['abs_path_temp'], $temp_name, 1);
        }             
      }
      // -------------------------- TEXT -------------------------    
      // get file content from readable formats
      elseif ($file_ext != "" && substr_count (strtolower ($hcms_ext['cleartxt']).".", $file_ext.".") > 0)
      {
        $file_content = loadfile_fast ($location, $file);
        
        // detect charset
        if (function_exists ("mb_detect_encoding")) $charset_source = strtoupper (mb_detect_encoding ($file_content));
        elseif (is_latin1 ($file_content)) $charset_source = "ISO-8859-1";
        
        // convert to UTF-8
        if ($charset_source != "" && $charset_source != "UTF-8")
        {
          $file_content = convertchars ($file_content, $charset_source, "UTF-8");
        }        
      }
      // ----------------------- HTML/SCRIPTS ----------------------    
      // get file content from html/script formats
      elseif ($file_ext != "" && substr_count (strtolower ($hcms_ext['cms']).".", $file_ext.".") > 0)
      {
        $file_content = loadfile_fast ($location, $file);
        
        // detect charset
        if (function_exists ("mb_detect_encoding")) $charset_source = strtoupper (mb_detect_encoding ($file_content));
        elseif (is_latin1 ($file_content)) $charset_source = "ISO-8859-1";
        
        // convert to UTF-8
        if ($charset_source != "" && $charset_source != "UTF-8")
        {
          $file_content = convertchars ($file_content, $charset_source, "UTF-8");
        }        
      }
      // -------------------------- OCR FOR IMAGES -------------------------
      // get file content from image formats using OCR
      elseif ($file_ext != "" && substr_count (strtolower ($hcms_ext['image']).".", $file_ext.".") > 0 && !empty ($mgmt_parser) && is_array ($mgmt_parser))
      {
        foreach ($mgmt_parser as $ext_parser=>$parser)
        {
          if (substr_count (strtolower ($ext_parser).".", $file_ext.".") > 0)
          {
            // temporary directory for extracting file
            $temp_name = uniqid ("index");
            $temp_dir = $mgmt_config['abs_path_temp'];
        
            // convert image to TIFF since Tesseract can only scan TIFF images
            if (($file_ext != ".tif" || $file_ext != ".tiff") && !empty ($mgmt_imagepreview) && is_array ($mgmt_imagepreview))
            {
              // find image converter
              foreach ($mgmt_imagepreview as $ext_image=>$converter)
              {
                if (substr_count (strtolower ($ext_image).".", $file_ext.".") > 0)
                {
                  $cmd = $converter." \"".shellcmd_encode ($location.$file)."\" -auto-level -compress none \"".shellcmd_encode ($temp_dir.$temp_name).".tif\"";
                  break;
                }
              }
              
              @exec ($cmd, $error_array);
              
              // on error
              if ($errorCode || !is_file ($temp_dir.$temp_name.".tif"))
              {
                $errcode = "20531";
                $error[] = $mgmt_config['today']."|hypercms_media.inc.php|error|$errcode|exec of imagemagick (code:$errorCode, command:$cmd) failed in indexcontent for file: ".$file;
              }
              // on success
              else
              {
                $location_source = $temp_dir;
                $file_source = $temp_name.".tif";
              }
            }
            // image is already of TIFF format
            else
            {
              $location_source = $location;
              $file_source = $file;
            }

            // extract text from image using OCR
            if (is_file ($location_source.$file_source))
            {
              // create temp text file from TIFF image
              $cmd = $parser." \"".shellcmd_encode ($location_source.$file_source)."\" \"".shellcmd_encode ($temp_dir.$temp_name)."\"";
              
              @exec ($cmd);

              // on error
              if (!is_file ($temp_dir.$temp_name.".txt"))
              {
                $errcode = "20532";
                $error[] = $mgmt_config['today']."|hypercms_media.inc.php|error|$errcode|exec of tesseract (command:$cmd) failed in indexcontent for file: ".$file;
              }
              // on success
              else
              {
                $file_content = loadfile_fast ($temp_dir, $temp_name.".txt");
                
                // remove temp files
                if (is_file ($temp_dir.$temp_name.".tif")) deletefile ($temp_dir, $temp_name.".tif", 0);
                if (is_file ($temp_dir.$temp_name.".txt")) deletefile ($temp_dir, $temp_name.".txt", 0);
                
                break;
              }
            }
          }
        }
      }
      else $file_content = "";

      // ------------------------ AUDIO, IMAGES, VIDEOS -----------------------   
      // SPECIAL CASE: the meta data attributes found in the file will be saved using a mapping.
      // get file content from image formats with meta data
      if ($file_ext != "" && substr_count (strtolower ($hcms_ext['audio'].$hcms_ext['image'].$hcms_ext['video']).".", $file_ext.".") > 0)
      {
        // function setmetadata provides metadata in the content container without saving the container
        $container_content = setmetadata ($site, "", "", $file, "", $container_content, $user, false);
      } 

      // delete temp file
      if ($temp['result'] && $temp['created']) deletefile ($temp['templocation'], $temp['tempfile'], 0);

      // write to content container
      if (!empty ($file_content))
      {
        // remove all tags
        $file_content = strip_tags ($file_content);
        $file_content = trim ($file_content);
        $file = trim ($file);
            
        // get destination character set
        $charset_array = getcharset ($site, $container_content);
        
        // or set to UTF-8 if not available
        if (is_array ($charset_array)) $charset_dest = strtoupper ($charset_array['charset']);
        else $charset_dest = "UTF-8";
        
        // get encoding/charset of container
        $xml_encoding = gethtmltag ($container_content, "?xml");
            
        if ($xml_encoding != false) $charset_container = getattribute ($xml_encoding, "encoding");
        else $charset_container = "";
               
        // set character set / encoding of content container of not set already
        if ($charset_container == "" || $charset_container != $charset_dest)
        {
          $container_content = setxmlparameter ($container_content, "encoding", $charset_dest);
        }
        
        // set array to save content as UTF-8 in database before converting it
        $text_array[$file] = $file_content;
        $type_array[$file] = "file";
        
        // convert content if destination charset is not UTF-8     
        if ($charset_dest != "UTF-8")
        {
          $file_content = convertchars ($file_content, "UTF-8", $charset_dest);
        }

        // update existing content
        $container_contentnew = setcontent ($container_content, "<multimedia>", "<file>", $file, "", "");
        
        if ($container_contentnew != false)
        {          
          $container_contentnew = setcontent ($container_contentnew, "<multimedia>", "<content>", "<![CDATA[".$file_content."]]>", "", "");
        }
        // insert new multimedia xml-node
        else
        {
          $multimedia_schema_xml = chop (loadfile ($mgmt_config['abs_path_cms']."xmlsubschema/", "multimedia.schema.xml.php"));
          
          $multimedia_node = setcontent ($multimedia_schema_xml, "<multimedia>", "<file>", $file, "", "");
          $multimedia_node = setcontent ($multimedia_node, "<multimedia>", "<content>", "<![CDATA[".$file_content."]]>", "", "");
                  
          if ($multimedia_node != false) $container_contentnew = insertcontent ($container_content, $multimedia_node, "<container>");
        }

        // save log
        savelog (@$error);

        // save container
        if ($container_contentnew != false)
        {
          // relational DB connectivity
          if ($mgmt_config['db_connect_rdbms'] != "") rdbms_setcontent ($site, $container_id, $text_array, $type_array, $user);  

          // set modified date in container
          $container_contentnew = setcontent ($container_contentnew, "<hyperCMS>", "<contentdate>", $mgmt_config['today'], "", "");
          if ($container_content != false) $container_content = setcontent ($container_content, "<hyperCMS>", "<contentuser>", $user, "", "");
          
          // save container
          if ($container_contentnew != false)
          {
            savecontainer ($container, "published", $container_contentnew, $user);
            return savecontainer ($container, "work", $container_contentnew, $user);
          }
        }
      }
      // if no content has been extracted, save user and date information
      else
      {
        // relational DB connectivity
        if ($mgmt_config['db_connect_rdbms'] != "") rdbms_setcontent ($site, $container_id, "", "", $user);

        // set modified date in container
        $container_content = setcontent ($container_content, "<hyperCMS>", "<contentdate>", $mgmt_config['today'], "", "");
        if ($container_content != false) $container_content = setcontent ($container_content, "<hyperCMS>", "<contentuser>", $user, "", "");
        
        // save container
        if ($container_content != false)
        {
          savecontainer ($container, "published", $container_content, $user);
          return savecontainer ($container, "work", $container_content, $user);
        }
      }
    }
  }
  
  return false;
}

// ---------------------------------------- unindexcontent --------------------------------------------
// function: unindexcontent()
// input: publication name, file location, file name, multimedia file to index, container name or ID, container XML-content, user name
// output: true / false

// description:
// This function removes media objects from the container

function unindexcontent ($site, $location, $file, $container, $container_content, $user)
{
  global $mgmt_config, $mgmt_parser, $hcms_lang, $lang;

  if (valid_publicationname ($site) && valid_locationname ($location) && valid_objectname ($file) && valid_objectname ($container))
  {    
    // get container id
    if (substr_count ($container, ".xml") > 0)
    {
      $container_id = substr ($container, 0, strpos ($container, ".xml"));
    }
    elseif (is_numeric ($container))
    {
      $container_id = $container;
      $container = $container.".xml";
    }
    
    // read working content container if no container is provided
    if ($container_content == "")
    {
      $result = getcontainername ($container);
      $container = $result['container'];
      $usedby = $result['user'];
      $container_content = loadcontainer ($container_id, "work", $user);
      
      $type = "work";
    }
    elseif (strpos ($container, ".v_") > 0)
    {
      $type = "version";
    }
    elseif (strpos ($container, ".xml.wrk") > 0)
    {
      $type = "work";
    }
    elseif (strpos ($container, ".xml") > 0)
    {
      $type = "published";
    }
    else $type = "version";

    if ($container_content != false && $container_content != "" && ($usedby == "" || $usedby == $user))
    {
      $container_contentnew = deletecontent ($container_content, "<multimedia>", "", "");
      
      // relational DB connectivity
      if ($mgmt_config['db_connect_rdbms'] != "")
      {
        rdbms_deletecontent ($site, $container_id, $file);
      }

      // save container
      if ($container_contentnew != false)
      {
        // save published container if working container
        if ($type == "work")
        {
          savecontainer ($container, "published", $container_contentnew, $user);
          return savecontainer ($container, $type, $container_contentnew, $user);
        }
        else return savecontainer ($container, $type, $container_contentnew, $user);
      }
      else return false;
    }  
  }
}

// ------------------------------------------ reindexcontent --------------------------------------------- 

// function: reindexcontent()
// input: publication name, container IDs as array (optional)
// output: true / false

// description:
// Reindexes all media files of a publication and only for specific containers.

function reindexcontent ($site, $container_id_array="")
{
  global $mgmt_config;

  if (valid_publicationname ($site) && !empty ($mgmt_config['abs_path_media']))
  {
    $mediadir_array = array();
    
    // convert to integer
    if (is_array ($container_id_array))
    {
      foreach ($container_id_array as &$value)
      {
        $value = intval ($value);
      }
    }
    
    // create array for media repository path
    if (!is_array ($mgmt_config['abs_path_media']))
    {
      $mediadir_array[] = $mgmt_config['abs_path_media'];
    }
    else $mediadir_array = $mgmt_config['abs_path_media'];
    
    // walk the media directory
    foreach ($mediadir_array as $mediadir)
    {
      $location = $mediadir.$site."/";
      
      $handle = opendir ($location);

      while (false !== ($file = readdir ($handle)))
      {
        if (is_file ($location.$file) && !is_thumbnail ($file, false) && !is_config ($file) && !is_tempfile ($file))
        {
          if (is_array ($container_id_array))
          {
            $id = getmediacontainerid ($file);
            
            if (in_array (intval ($id), $container_id_array)) $found = true;
            else $found = false;
          }
          else $found = true;
          
          if ($found)
          {
            $result = indexcontent ($site, $location, $file, "", "", "sys");
  
            if ($result)
            {
              $errcode = "00501";
              $error[] = $mgmt_config['today']."|hypercms_media.inc.php|information|$errcode|reindex of content was successful for: ".$site."/".$file;             
            }
            else
            {
              $errcode = "20501";
              $error[] = $mgmt_config['today']."|hypercms_media.inc.php|error|$errcode|reindex of content failed for: ".$site."/".$file;   
            }
            
            // save log
            savelog (@$error);
          }
        }
      }
      
      closedir ($handle);
    }

    return true;
  }
  else return false;
}

// ---------------------- base64_to_file -----------------------------
// function: createthumbnail_indesign()
// input: base64 encoded string, path to destination dir, file name
// output: new file name / false on error

// description:
// Decodes a base64 encoded straing and saves it to the file system.

function base64_to_file ($base64_string, $location, $file)
{
  if ($base64_string != "" && valid_locationname ($location) && valid_objectname ($file))
  {
      // add slash if not present at the end of the location string
    if (substr ($location, -1) != "/") $location = $location."/";

    // exctract image data from string (image/jpg;base64,$data)
    if (strpos ("_".$base64_string, ",") > 0) list ($format, $data) = explode (",", $base64_string);
    else $data = $base64_string;
  
    $filehandler = fopen ($location.$file, "wb"); 
    
    if ($filehandler)
    {
      fwrite ($filehandler, base64_decode ($data)); 
      fclose ($filehandler); 

      return $file;
    }
    else return false;
  }
  else return false; 
}

// ---------------------- createthumbnail_indesign -----------------------------
// function: createthumbnail_indesign()
// input: publication, path to source dir, path to destination dir, file name
// output: new file name / false on error (saves only thumbnail media file in destination location, only jpeg format is supported as output)

// description:
// Creates a thumbnail by extracting the thumbnail from an indesign file and transferes the generated image via remoteclient.
// For good results, InDesign Preferences must be set to save preview image and at extra large size.

function createthumbnail_indesign ($site, $location_source, $location_dest, $file)
{
  global $mgmt_config, $user;
  
  if (valid_publicationname ($site) && valid_locationname ($location_source) && valid_locationname ($location_dest) && valid_objectname ($file))
  {
    // add slash if not present at the end of the location string
    if (substr ($location_source, -1) != "/") $location_source = $location_source."/";
    if (substr ($location_dest, -1) != "/") $location_dest = $location_dest."/";

    // prepare media file
    $temp_source = preparemediafile ($site, $location_source, $file, $user);
    
    if ($temp_source['result'] && $temp_source['crypted'])
    {
      $location_source = $temp_source['templocation'];
      $file = $temp_source['tempfile'];
    }
    elseif ($temp_source['restored'])
    {
      $location_source = $temp_source['location'];
      $file = $temp_source['file'];
    }
    
    // verify local media file
    if (!is_file ($location_source.$file)) return false;
    
    $filedata = file_get_contents ($location_source.$file);

    if ($filedata != "")
    {
      $result = "";
      
      // get file name without extension
      $file_name = strrev (substr (strstr (strrev ($file), "."), 1));      

      // try to extract data from XMP node
      // new method for XMP thumbnail extraction
      $regexp = "/<xmpGImg:image>.+<\/xmpGImg:image>/";
      preg_match_all ($regexp, $filedata, $result_array);

      if (isset ($result_array[0]) && count ($result_array[0]) > 0)
      {     
        $i = 0;
           
        foreach ($result_array[0] as $result_code)
        {
          // first thumbnails can not be properly extracted, so we use last thumbnail in indesign file
          $result = $result_code;
          $i++;
        }
      }
        
      // old method for XMP thumbnail extraction (deprecated)
      if ($result == "")
      {
        $xmpdata = getcontent ($filedata, "<xmp:PageInfo>");
        
        if ($xmpdata != false && $xmpdata[0] != "")
        {
          // get base64 encoded string from xml node
          $imgstr = getcontent ($xmpdata[0], "<xmpGImg:image>");

          if ($imgstr == false || $imgstr[0] == "")
          {
            // try attribute
            $result = getattribute ($xmpdata[0], "xmpGImg:image");           
          }
          else $result = $imgstr[0];
        }
      }
      
      // try to extract data from XAP node (deprecated)
      if ($result == "")
      {
        $xapdata = getcontent ($filedata, "<xap:Thumbnails>");
        
        if ($xapdata != false && $xapdata[0] != "")
        {
          // get base64 encoded string from xml node
          $imgstr = getcontent ($xapdata[0], "<xapGImg:image>");
  
          if ($imgstr == false || $imgstr[0] == "")
          {
            // try attribute
            $result = getattribute ($xapdata[0], "xapGImg:image");           
          }
          else $result = $imgstr[0]; 
        }
      }
      
      // delete temp file
      if ($temp_source['result'] && $temp_source['created']) deletefile ($temp_source['templocation'], $temp_source['tempfile'], 0);

      // save thumbnail file
      if ($result != "")
      {
        // prepare base64 encoded image string
        if ($result != false && $result != "")
        {
          $indd_thumbnail = strip_tags ($result);
          // remove decoded Line Feed character
          $indd_thumbnail = str_replace ("#xA;", "", $indd_thumbnail);
        }
        
        // new file name
        $newfile = $file_name.".thumb.jpg";     
        $filehandler = fopen ($location_dest.$newfile, "wb");
      
        if ($filehandler)
        {
          fwrite ($filehandler, base64_decode ($indd_thumbnail));
          fclose ($filehandler);
          
          // save in cloud storage
          if (function_exists ("savecloudobject")) savecloudobject ($site, $location_dest, $newfile, $user);
          
          // remote client
          remoteclient ("save", "abs_path_media", $site, $location_dest, "", $newfile, "");
          
          return $newfile;
        }
        else
        {
          $errcode = "20221";
          $error[] = $mgmt_config['today']."|hypercms_media.inc.php|error|$errcode|createthumbnail_indesign failed to save file: ".$location_dest.$newfile;           
       
          // save log
          savelog (@$error);
                 
          return false;
        }   
      }
      else
      {
        $errcode = "20222";
        $error[] = $mgmt_config['today']."|hypercms_media.inc.php|error|$errcode|createthumbnail_indesign failed for file: ".$location_source.$file;           
     
        // save log
        savelog (@$error);
               
        return false;
      }     
    }
    else return false;
  }
  else return false;
}

// ---------------------- createthumbnail_video -----------------------------
// function: createthumbnail_video()
// input: publication, path to source dir, path to destination dir, file name, frame of video in the seconds or hh:mm:ss[.xxx]
// output: new file name / false on error (saves only thumbnail media file in destination location, only jpeg format is supported as output)

// description:
// Creates a thumbnail picture of a video frame 

function createthumbnail_video ($site, $location_source, $location_dest, $file, $frame)
{
  global $mgmt_config, $mgmt_mediapreview, $user;
  
  if (valid_publicationname ($site) && valid_locationname ($location_source) && valid_locationname ($location_dest) && valid_objectname ($file) && $frame != "")
  {
    // add slash if not present at the end of the location string
    if (substr ($location_source, -1) != "/") $location_source = $location_source."/";
    if (substr ($location_dest, -1) != "/") $location_dest = $location_dest."/";
    
    // remove .orig sub-file-extension
    if (strpos ($file, ".orig.") > 0) $newfile = str_replace (".orig.", ".", $file);
    else $newfile = $file;
    
    // get file info
    $fileinfo = getfileinfo ($site, $location_source.$newfile, "comp");
    
    // thumbnail file name
    $newfile = $fileinfo['filename'].".thumb.jpg";

    // prepare media file
    $temp_source = preparemediafile ($site, $location_source, $file, $user);
    
    if ($temp_source['result'] && $temp_source['crypted'])
    {
      $location_source = $temp_source['templocation'];
      $file = $temp_source['tempfile'];
    }
    elseif ($temp_source['restored'])
    {
      $location_source = $temp_source['location'];
      $file = $temp_source['file'];
    }
    
    // verify local media file
    if (!is_file ($location_source.$file)) return false;
    
    // create thumbnail
    $errorCode = "video not valid";
    
    if (is_file ($location_source.$file))
    {
      reset ($mgmt_mediapreview);
        
      // supported extensions for media rendering
      foreach ($mgmt_mediapreview as $mediapreview_ext => $mediapreview)
      {        
        // check file extension
        if ($fileinfo['ext'] != "" && substr_count ($mediapreview_ext.".", $fileinfo['ext'].".") > 0)
        {
          $cmd = $mgmt_mediapreview[$mediapreview_ext]." -i \"".shellcmd_encode ($location_source.$file)."\" -ss ".shellcmd_encode ($frame)." -f image2 -vframes 1 \"".shellcmd_encode ($location_dest.$newfile)."\"";

          // execute 
          exec ($cmd, $error_array, $errorCode);
        }
      }
    }
    
    // delete temp file
    if ($temp_source['result'] && $temp_source['created']) deletefile ($temp_source['templocation'], $temp_source['tempfile'], 0);

    if (!is_file ($location_dest.$newfile) || $errorCode) 
    {
      $errcode = "20241";
      
      $error = array($mgmt_config['today'].'|hypercms_media.inc.php|error|'.$errcode.'|exec of ffmpeg (code:'.$errorCode.') (command:'.$cmd.') failed in createthumbnail_video for file '.$file.' and frame '.$frame);
      // save log
      savelog (@$error);
      return false;
    } 
    else
    {
      // save in cloud storage
      if (function_exists ("savecloudobject")) savecloudobject ($site, $location_dest, $newfile, $user);
      
      // remote client
      remoteclient ("save", "abs_path_media", $site, $location_dest, "", $newfile, "");
    
      return $newfile;
    }
  }
  else return false;
}

// ---------------------- createmedia -----------------------------
// function: createmedia()
// input: publication, path to source dir, path to destination dir, file name, 
//        format (file extension w/o dot) (optional), 
//        type of image/video/audio file [thumbnail(for thumbnails of images),origthumb(thumbnail made from original video/audio),original(to overwrite original video/audio file),any other string present in $mgmt_imageoptions/$mgmt_mediaoptions] (optional),
//        force the file to be not encrypted even if the content of the publication must be encrypted [true,false] (optional)
// output: new file name / false on error

// description:
// Creates an new image from original or creates a thumbnail and transferes the generated image via remoteclient.
// Saves original or thumbnail media file in destination location, for thumbnail only jpeg format is supported as output.

function createmedia ($site, $location_source, $location_dest, $file, $format="", $type="thumbnail", $force_no_encrypt=false)
{
  global $mgmt_config, $mgmt_imagepreview, $mgmt_mediapreview, $mgmt_mediaoptions, $mgmt_imageoptions, $mgmt_maxsizepreview, $mgmt_mediametadata, $hcms_ext, $user;

  if (valid_publicationname ($site) && valid_locationname ($location_source) && valid_locationname ($location_dest) && valid_objectname ($file))
  {
    // appending data to a file ensures that the previous write process is finished (required due to issue when editing encrypted files)
    avoidfilecollision ($file);
    
    // load file extensions
    if (empty ($hcms_ext) || !is_array ($hcms_ext)) require ($mgmt_config['abs_path_cms']."include/format_ext.inc.php");
    
    // publication management config
    if (valid_publicationname ($site) && !is_array ($mgmt_config[$site])) require ($mgmt_config['abs_path_data']."config/".$site.".conf.php");
    
    $converted = false;
    $skip = false;
    
    // save input type in new variable
    $type_memory = $type;

    // add slash if not present at the end of the location string
    if (substr ($location_source, -1) != "/") $location_source = $location_source."/";
    if (substr ($location_dest, -1) != "/") $location_dest = $location_dest."/";

    // save original file source location and file name
    $location_source_orig = $location_source;
    $file_orig = $file;

    //The GD Libary only supports jpg, png and gif
    $GD_allowed_ext = array (".jpg", ".jpeg", ".gif", ".png");
    
    // get file name without extension
    $file_name = strrev (substr (strstr (strrev ($file), "."), 1));
    
    // get the file extension
    $file_ext = strtolower (strrchr ($file, "."));
    
    // normalize format
    if ($format != "") $format = strtolower ($format);

    // define temporary media file location
    $location_temp = $mgmt_config['abs_path_temp'];

    // prepare media file
    $temp_source = preparemediafile ($site, $location_source, $file, $user);
    
    if ($temp_source['result'] && $temp_source['crypted'])
    {
      $location_source = $temp_source['templocation'];
      $file = $temp_source['tempfile'];
    }
    elseif ($temp_source['restored'])
    {
      $location_source = $temp_source['location'];
      $file = $temp_source['file'];
    }
    
    // check if file exists and has content
    if (!is_file ($location_source.$file) || filesize ($location_source.$file) < 10) return false;

    // get file size of media file in kB
    $filesize_orig = round (@filesize ($location_source.$file) / 1024, 0);

    // convert RAW image to equivalent JPEG image if not already converted
    if (is_rawimage ($file_ext))
    {
      if  (!is_file ($location_dest.$file_name.".jpg") || filemtime ($location_dest.$file_name.".jpg") < filemtime ($location_source_orig.$file_orig))
      {
        // if image conversion software is given
        if (is_array ($mgmt_imagepreview) && sizeof ($mgmt_imagepreview) > 0)
        {            
          reset ($mgmt_imagepreview);    
      
          // supported extensions for image rendering
          foreach ($mgmt_imagepreview as $imagepreview_ext => $imagepreview)
          {
            // check file extension
            if ($file_ext != "" && substr_count (strtolower ($imagepreview_ext).".", $file_ext.".") > 0)
            {
              $cmd = $mgmt_imagepreview[$imagepreview_ext]." \"".shellcmd_encode ($location_source.$file)."\" \"".shellcmd_encode ($location_dest.$file_name).".jpg\"";

              @exec ($cmd, $error_array, $errorCode);

              // on error
              if ($errorCode)
              {          
                $errcode = "20259";
                $error[] = $mgmt_config['today']."|hypercms_media.inc.php|error|$errcode|exec of imagemagick (code:$errorCode) (command:$cmd) failed in createmedia for file: ".$file."<br />".implode ("<br />", $error_array);   
              }
              else
              {
                // copy met data
                copymetadata ($location_source.$file, $location_dest.$file_name.".jpg");

                $location_source = $location_dest;
                $file = $file_name.".jpg";
              }
            }
          }
        }
      }
      // use existing converted image file
      else
      {
        $location_source = $location_dest;
        $file = $file_name.".jpg";

        // prepare media file
        $temp_raw = preparemediafile ($site, $location_source, $file, $user);
    
        if ($temp_raw['result'] && $temp_raw['crypted'])
        {
          $location_source = $temp_raw['templocation'];
          $file = $temp_raw['tempfile'];
        }
        elseif ($temp_raw['restored'])
        {
          $location_source = $temp_raw['location'];
          $file = $temp_raw['file'];
        }
        
        // verify local media file
        if (!is_file ($location_source.$file)) return false;
      }
    }
    
    // get file width and heigth in pixels
    $imagesize_orig = @getimagesize ($location_source.$file);
    
    // get file-type
    $filetype_orig = getfiletype ($file_ext);
    
    // MD5 hash of the original file
    $md5_hash = md5_file ($location_source.$file);
    
    // get container ID
    $container_id = getmediacontainerid ($file);

    if ($imagesize_orig != false)
    {
      $imagewidth_orig = $imagesize_orig[0];
      $imageheight_orig = $imagesize_orig[1];
    }
    else
    {
      $imagewidth_orig = 0;
      $imageheight_orig = 0;    
    }

    // Default jpg/jpeg options
    if (!array_key_exists ('.jpg.jpeg', $mgmt_imageoptions))
      $mgmt_imageoptions['.jpg.jpeg']= array();
    
    if (!array_key_exists ('original', $mgmt_imageoptions['.jpg.jpeg'])) 
      $mgmt_imageoptions['.jpg.jpeg']['original'] = "-f jpg";
      
    if (!array_key_exists ('thumbnail', $mgmt_imageoptions['.jpg.jpeg'])) 
      $mgmt_imageoptions['.jpg.jpeg']['thumbnail'] = "-s 180x180 -f jpg";
      
    // Default gif options
    if (!array_key_exists ('.gif', $mgmt_imageoptions))
      $mgmt_imageoptions['.gif']= array();
    
    if (!array_key_exists('original', $mgmt_imageoptions['.gif'])) 
      $mgmt_imageoptions['.gif']['original'] = "-f gif";
      
    // Default png options
    if (!array_key_exists ('.png', $mgmt_imageoptions))
      $mgmt_imageoptions['.png']= array();
    
    if (!array_key_exists ('original', $mgmt_imageoptions['.png'])) 
      $mgmt_imageoptions['.png']['original'] = "-f png";
   
    // check max file size in MB for certain file extensions and skip rendering
    if (is_array ($mgmt_maxsizepreview))
    {
      reset ($mgmt_maxsizepreview);   
      
      // defined extension for maximum file size restriction in MB
      foreach ($mgmt_maxsizepreview as $maxsizepreview_ext => $maxsizepreview)
      {        
        if ($file_ext != "" && substr_count (strtolower ($maxsizepreview_ext).".", $file_ext.".") > 0)
        {
          if ($mgmt_maxsizepreview[$maxsizepreview_ext] > 0 && ($filesize_orig / 1024) > $mgmt_maxsizepreview[$maxsizepreview_ext]) $skip = true;
        }
      }
    }

    if ($skip == false)
    {
      // ---------------------- if Audio file ------------------------
      // the extracted thumbnail will be used as it is and don't use the image data for table media
      if (is_audio ($file_ext) && ($type == "thumbnail" || $type == "origthumb"))
      {        
        // new file name
        $newfile = $file_name.".thumb.jpg";
        
        $id3_data = id3_getdata ($location_source.$file);
        
        // if album art image is available
        if (!empty ($id3_data['imagedata']))
        {
          // convert album art if not a JPEG and image is too large in size
          if ($id3_data['imagemimetype'] != "image/jpeg" || $id3_data['imagewidth'] > 240 || $id3_data['imageheight'] > 240)
          {
            // save temp file
            if (strpos ("_".$id3_data['imagemimetype'], "/") > 0)
            {
              list ($temp_type, $temp_ext) = explode ("/", $id3_data['imagemimetype']);
              
              if ($temp_ext != "") $temp_ext = ".".$temp_ext;
              else $temp_ext = ".jpg";
            }
            
            $temp_file = $file_name.$temp_ext;          
            $filehandler = fopen ($location_temp.$temp_file, "wb");
            
            if ($filehandler)
            {
              // write binary data to file
              fwrite ($filehandler, $id3_data['imagedata']);
              fclose ($filehandler);
              
              // calculate new width and height
              $thumb_width = 180;
              $thumb_height = 180;
                              
              if ($id3_data['imagewidth'] > 0 && $id3_data['imageheight'] > 0)
              {
                $imgratio = $id3_data['imagewidth'] / $id3_data['imageheight'];
  
                if ($id3_data['imagewidth'] > $id3_data['imageheight'])
                {
                  $thumb_height = round (($thumb_width / $imgratio), 0);
                }
                else
                {
                  $thumb_width = round (($thumb_height * $imgratio), 0);
                }
              }
              
              // convert thumbnail to proper format and size
              $temp_file_2 = convertimage ($site, $location_temp.$temp_file, $location_temp, "jpg", "RGB", "", $thumb_width, $thumb_height);
  
              // remove temp file
              deletefile ($location_temp, $temp_file, 0);
              
              // move temporary thumbnail file to destination
              if ($temp_file_2 != "" && is_file ($location_temp.$temp_file_2)) rename ($location_temp.$temp_file_2, $location_dest.$newfile);       
            }
          }
          // save binary image in destination
          else 
          {
            $filehandler = fopen ($location_dest.$newfile, "wb");
            
            if ($filehandler)
            {
              // write binary data to file
              fwrite ($filehandler, $id3_data['imagedata']);
              fclose ($filehandler);
            }
          }
        }
      }
      
      // ---------------------- if Adobe InDesign file ------------------------
      // the extracted thumbnail will be used as it is
      if ($file_ext == ".indd" && ($type == "thumbnail" || $type == "origthumb"))
      {
        $newfile = createthumbnail_indesign ($site, $location_source, $location_dest, $file);
        
        // get media information from thumbnail
        if ($newfile != false)
        {
          $converted = true;
                    
          $imagecolor = getimagecolors ($site, $newfile);
        
          // write media information to container and DB
          if (!empty ($container_id))
          {
            $setmedia = rdbms_setmedia ($container_id, $filesize_orig, $filetype_orig, $imagewidth_orig, $imageheight_orig, $imagecolor['red'], $imagecolor['green'], $imagecolor['blue'], $imagecolor['colorkey'], $imagecolor['imagetype'], $md5_hash);
          }
        }
      }
  
      // -------------- if image conversion software is given -----------------
      if (is_array ($mgmt_imagepreview) && sizeof ($mgmt_imagepreview) > 0)
      {
        // redefine type (for images thumbnail and preview are the same)
        if ($type == "origthumb") $type = "thumbnail";
              
        // define format if not set
        if ($format == "") $format_set = "jpg";
        else $format_set = $format;
            
        reset ($mgmt_imagepreview);    
        
        // supported extensions for image rendering
        foreach ($mgmt_imagepreview as $imagepreview_ext => $imagepreview)
        {
          // check file extension
          if ($file_ext != "" && substr_count (strtolower ($imagepreview_ext).".", $file_ext.".") > 0)
          {
            reset ($mgmt_imageoptions);  
            $i = 1;
  
            // extensions for certain image rendering options
            foreach ($mgmt_imageoptions as $imageoptions_ext => $imageoptions)
            {
              // if we make a thumbnail we always use the thumbnail configuration from the jpg
              $check1 = $type == 'thumbnail' && substr_count ($imageoptions_ext.".", ".jpg.") > 0;
              // else we check the format we convert to
              $check2 = $type != 'thumbnail' && substr_count ($imageoptions_ext.".", ".".$format_set.".") > 0;
              // we also need to check if the type array is present
              $check3 = array_key_exists ($type, $mgmt_imageoptions[$imageoptions_ext]);
  
              // get image rendering options based on given destination format
              if (($check1 || $check2) && $check3)
              {
                // Options:
                // -s ... output size in width x height in pixel (WxH)
                // -f ... output format (file extension without dot [jpg, png, gif])
                // -d ... image density (DPI) for vector graphics and EPS files, common values are 72, 96 dots per inch for screen, while printers typically support 150, 300, 600, or 1200 dots per inch
                // -q ... quality for compressed image formats like JPEG (1 to 100)
                // -c ... crop x and y coordinates (XxY)
                // -b ... image brightness
                // -k .... image contrast
                // -cs ... color space of image, e.g. RGB, CMYK, gray
                // -rotate ... rotate image
                // -fv ... flip image in the vertical direction
                // -fh ... flop image in the horizontal direction
                // -sharpen ... sharpen image, e.g. one pixel size sharpen: -sharpen 0x1.0
                // -sketch ... skecthes an image, e.g. -sketch 0x20+120
                // -sepia-tone ... apply -sepia-tone on image, e.g. -sepia-tone 80%
                // -monochrome ... transform image to black and white
                // -wm ... watermark in watermark image->positioning->geometry, e.g. image.png->topleft->+30
              
                // image size (in pixel) definition
                if (strpos ("_".$mgmt_imageoptions[$imageoptions_ext][$type], "-s ") > 0)
                {
                  $imagesize = getoption ($mgmt_imageoptions[$imageoptions_ext][$type], "-s");
                  list ($imagewidth, $imageheight) = explode ("x", $imagesize);
                  
                  $imagewidth = intval ($imagewidth);
                  $imageheight = intval ($imageheight);
                  // ImageMagick resize parameter (resize will fit the image into the requested size, aspect ratio is preserved)
                  $imageresize = "-resize ".$imagewidth."x".$imageheight;
                  // Imagemagick geometry parameter for EPS
                  $imagegeometry = "-geometry ".$imagewidth."x".$imageheight; 
                }
                else $imageresize = "";
                
                // if no size parameters are provided we use the original size for the new image
                if (empty ($imagewidth) || empty ($imageheight))
                {
                  $imagewidth = $imagewidth_orig;
                  $imageheight = $imageheight_orig;
                }
             
                // image crop
                if (strpos ("_".$mgmt_imageoptions[$imageoptions_ext][$type], "-c ") > 0) $crop_mode = true;
                else $crop_mode = false;
                
                if ($crop_mode)
                {
                  $cropoffset = getoption ($mgmt_imageoptions[$imageoptions_ext][$type], "-c");
                  list ($offsetX, $offsetY) = explode ("x", $cropoffset);
                  
                  $offsetX = intval ($offsetX);
                  $offsetY = intval ($offsetY);
                }                
  
                // image format (image file extension) definition
                if (strpos ("_".$mgmt_imageoptions[$imageoptions_ext][$type], "-f ") > 0)
                {
                  $imageformat = strtolower (getoption ($mgmt_imageoptions[$imageoptions_ext][$type], "-f"));
                  if (empty ($imageformat) || $imageformat == false) $imageformat = "jpg";
                }
                else $imageformat = "jpg";
                
                // image rotation
                if (strpos ("_".$mgmt_imageoptions[$imageoptions_ext][$type], "-rotate ") > 0) 
                {
                  $imagerotation = intval (getoption ($mgmt_imageoptions[$imageoptions_ext][$type], "-rotate"));
                  // ImageMagick rotate parameter
                  $imagerotate = "-rotate ".$imagerotation;
                  
                  // no resize if rotation is used
                  $imageresize = "";
                }
                else
                {
                  $imagerotate = "";
                  $imagerotation = "";
                }
                
                // image density (DPI)
                if (strpos ("_".$mgmt_imageoptions[$imageoptions_ext][$type], "-d ") > 0) 
                {
                  $imagedensity = intval (getoption ($mgmt_imageoptions[$imageoptions_ext][$type], "-d"));
                  
                  if ($imagedensity > 1200) $imagedensity = "-density 1200";
                  elseif ($imagedensity < 72) $imagedensity = "-density 72";
                  else $imagedensity = "-density ".$imagedensity;
                }
                else $imagedensity = "";
                
                // image quality / compression
                if (strpos ("_".$mgmt_imageoptions[$imageoptions_ext][$type], "-q ") > 0) 
                {
                  $imagequality = intval (getoption ($mgmt_imageoptions[$imageoptions_ext][$type], "-q"));
                  
                  if ($imagequality > 100) $imagequality = "-quality 100";
                  elseif ($imagequality < 1) $imagequality = "-quality 1";
                  else $imagequality = "-quality ".$imagequality;
                }
                else $imagequality = "";
                
                // image brightness
                if (strpos ("_".$mgmt_imageoptions[$imageoptions_ext][$type], "-b ") > 0) 
                {
                  $imagebrightness = intval (getoption ($mgmt_imageoptions[$imageoptions_ext][$type], "-b"));
                  
                  if ($imagebrightness > 100) $imagebrightness = 100;
                  elseif ($imagebrightness < -100) $imagebrightness = -100;
                }
                else $imagebrightness = 0;
                
                // image contrast
                if (strpos ("_".$mgmt_imageoptions[$imageoptions_ext][$type], "-k ") > 0) 
                {
                  $imagecontrast = intval (getoption ($mgmt_imageoptions[$imageoptions_ext][$type], "-k"));
  
                  if ($imagecontrast > 100) $imagecontrast = 100;
                  elseif ($imagecontrast < -100) $imagecontrast = -100;
                }
                else $imagecontrast = 0;
                
                // set image brightness parameters for ImageMagick
                $imageBrightnessContrast = "";
                
                if ($imagebrightness != 0 || $imagecontrast != 0)
                {
                  $imageBrightnessContrast = "-brightness-contrast ";
                  
                  if ($imagebrightness == 0) $imageBrightnessContrast .= "0x";
                  else $imageBrightnessContrast .= shellcmd_encode ($imagebrightness)."x";
                  
                  if ($imagecontrast == 0) $imageBrightnessContrast .= "0";
                  else $imageBrightnessContrast .= shellcmd_encode ($imagecontrast);
                }
                
                // set image color space
                if (strpos ("_".$mgmt_imageoptions[$imageoptions_ext][$type], "-cs ") > 0) 
                {
                  $imagecolorspace = getoption ($mgmt_imageoptions[$imageoptions_ext][$type], "-cs");
                  
                  // enable alpha blending for colorspace transparent
                  if (strtolower ($imagecolorspace) == "transparent") $add = "-alpha on ";
                  else $add = "";
                  
                  if ($imagecolorspace == "" || $imagecolorspace == false) $imagecolorspace = "";
                  else $imagecolorspace = $add."-colorspace ".shellcmd_encode ($imagecolorspace);
                }
                else $imagecolorspace = "";
                
                // set image icc profile 
                if (strpos ("_".$mgmt_imageoptions[$imageoptions_ext][$type], "-p ") > 0) 
                {
                  $iccprofile = getoption ($mgmt_imageoptions[$imageoptions_ext][$type], "-p");
                  
                  if ($iccprofile == "" || $iccprofile == false) $iccprofile = "";
                  else $iccprofile = "-profile ".shellcmd_encode ($iccprofile);
                }
                else $iccprofile = "";
                
                // set flip
                if (strpos ("_".$mgmt_imageoptions[$imageoptions_ext][$type], "-fv ") > 0) 
                {
                  $imageflipv = "-flop";
                }
                else $imageflipv = "";
                
                // set flop
                if (strpos ("_".$mgmt_imageoptions[$imageoptions_ext][$type], "-fh ") > 0) 
                {
                  $imagefliph = "-flip";
                }
                else $imagefliph = "";
                
                // Combine flip and flop into one
                $imageflip = $imageflipv." ".$imagefliph;
                
                // set sepia
                if (strpos ("_".$mgmt_imageoptions[$imageoptions_ext][$type], "-sep ") > 0) 
                {
                  $sepia = getoption ($mgmt_imageoptions[$imageoptions_ext][$type], "-sep");
                  
                  if ($sepia == "" || $sepia == false) $sepia = "";
                  else $sepia = "-sepia-tone ".shellcmd_encode ($sepia);
                }
                else $sepia = "";
                
                // set sharpen
                if (strpos ("_".$mgmt_imageoptions[$imageoptions_ext][$type], "-sh ") > 0) 
                {
                  $sharpen = getoption ($mgmt_imageoptions[$imageoptions_ext][$type], "-sh");
                  
                  if ($sharpen == "" || $sharpen == false) $sharpen = "";
                  else $sharpen = "-sharpen ".shellcmd_encode ($sharpen);
                }
                else $sharpen = "";
                
                // set blur
                if (strpos ("_".$mgmt_imageoptions[$imageoptions_ext][$type], "-bl ") > 0) 
                {
                  $blur = getoption ($mgmt_imageoptions[$imageoptions_ext][$type], "-bl");
                  
                  if ($blur == "" || $blur == false) $blur = "";
                  else $blur = "-blur ".shellcmd_encode ($blur);
                }
                else $blur = "";
                
                // set sketch
                if (strpos ("_".$mgmt_imageoptions[$imageoptions_ext][$type], "-sk ") > 0) 
                {
                  $sketch = getoption ($mgmt_imageoptions[$imageoptions_ext][$type], "-sk");
                  
                  if ($sketch == "" || $sketch == false) $sketch = "";
                  else $sketch = "-sketch ".shellcmd_encode ($sketch);
                }
                else $sketch = "";
                
                // set paint
                if (strpos ("_".$mgmt_imageoptions[$imageoptions_ext][$type], "-pa ") > 0) 
                {
                  $paint = getoption ($mgmt_imageoptions[$imageoptions_ext][$type], "-pa");
                  
                  if ($paint == "" || $paint == false) $paint = "";
                  else $paint = "-paint ".shellcmd_encode ($paint);
                }
                else $paint = "";
                
                // watermarking
                // set watermark options if defined in publication settings and not already defined
                if (!empty ($mgmt_config[$site]['watermark_image']) && strpos ("_".$mgmt_imageoptions[$imageoptions_ext][$type], "-wm ") == 0)
                {
                  $mgmt_imageoptions[$imageoptions_ext][$type] .= " ".$mgmt_config[$site]['watermark_image'];
                }
                 
                if (strpos ("_".$mgmt_imageoptions[$imageoptions_ext][$type], "-wm ") > 0) 
                {
                  $watermarking = getoption ($mgmt_imageoptions[$imageoptions_ext][$type], "-wm");
                  
                  if ($watermarking == "" || $watermarking == false)
                  {
                    $watermark = "";
                  }
                  else
                  {
                    // parameters:
                    // watermark ... reference to watermark PNG image
                    // -gravity ... sets where in the image the watermark should be added
                    // -geometry ... Can be used to modify the size of the watermark being passed in, and also the positioning of the watermark (relative to the gravity placement). 
                    //               It is specified in the form width x height +/- horizontal offset +/- vertical offset (<width>x<height>{+-}<xoffset>{+-}<yoffset>).
                    // -composite ... parameter, which tells ImageMagick to add the watermark image we�ve just specified to the image. 
                    list ($watermark, $gravity, $geometry) = explode ("->", $watermarking);
                    
                    if (!empty ($geometry)) $geometry = intval ($geometry);
                    else $geometry = 0;
                    
                    if (strtolower(trim($gravity)) == "topleft")
                    {
                      $gravity = "northwest";
                      $geometry = "+".$geometry."+".$geometry;
                    }
                    elseif (strtolower(trim($gravity)) == "topright")
                    {
                      $gravity = "northeast";
                      $geometry = "-".$geometry."+".$geometry;
                    }
                    elseif (strtolower(trim($gravity)) == "bottomleft")
                    {
                      $gravity = "southwest";
                      $geometry = "+".$geometry."-".$geometry;
                    }
                    elseif (strtolower(trim($gravity)) == "bottomleft")
                    {
                      $gravity = "southeast";
                      $geometry = "-".$geometry."-".$geometry;
                    }
                    
                    if ($watermark != "" && $gravity != "" && $geometry != "")
                    {
                      $watermark = "-compose multiply -gravity ".$gravity." -geometry ".shellcmd_encode(trim($geometry))." -background none \"".shellcmd_encode(trim($watermark))."\"";
                    }
                    else $watermark = "";
                  }                  
                }
                else $watermark = "";

                // -------------------- convert image using ImageMagick ----------------------
                if (!empty ($mgmt_imagepreview[$imagepreview_ext]) && $mgmt_imagepreview[$imagepreview_ext] != "GD")
                {
                  // delete thumbnail
                  if ($type == "thumbnail" && @is_file ($location_dest.$file_name.".thumb.jpg"))
                  {
                    unlink ($location_dest.$file_name.".thumb.jpg");
                  }
                  // copy original image before conversion to restore it if an error occured
                  elseif ($type != "thumbnail" && @is_file ($location_source.$file))
                  {
                    // create buffer file
                    $buffer_file = $location_dest.$file_name.".buffer".strrchr ($file, ".");;
                    copy ($location_source.$file, $buffer_file);
  
                    // delete the old file if we overwrite the original file
                    if ($type == "original")
                    {
                      unlink ($location_source.$file);
                    }
                  }

                  // CASE: document-based formats, encapsulated post script and vector graphics
                  if ($file_ext == ".pdf" || $file_ext == ".eps" || $file_ext == ".svg")
                  {
                    if ($type == "thumbnail" || $type == "annotation")
                    {
                      if ($type == "annotation") $newfile = $file_name.".annotation.jpg";
                      else $newfile = $file_name.".thumb.jpg";
                      
                      $cmd = $mgmt_imagepreview[$imagepreview_ext]." ".$iccprofile." ".$imagecolorspace." \"".shellcmd_encode ($location_source.$file)."[0]\" ".$imageresize." -background white -alpha remove ".$imagequality." \"".shellcmd_encode ($location_dest.$newfile)."\"";
                    }
                    else 
                    {
                      if ($type == "original") $newfile = $file_name.".".$imageformat;
                      else $newfile = $file_name.".".$type.".".$imageformat;
                      
                      // use geometry instead of resize for EPS files
                      if ($file_ext == ".eps") $imageresize = $imagegeometry;
  
                      $cmd = $mgmt_imagepreview[$imagepreview_ext]." -background none ".$imagedensity." ".$iccprofile." ".$imagecolorspace." \"".shellcmd_encode ($buffer_file)."[0]\" ".$imagerotate." ".$imageBrightnessContrast." ".$imageresize." ".$imageflip." ".$sepia." ".$sharpen." ".$blur." ".$sketch." ".$paint." ".$imagequality." \"".shellcmd_encode ($location_dest.$newfile)."\"";
                    }
                    
                    @exec ($cmd, $buffer, $errorCode);
  
                    // on error
                    if ($errorCode)
                    {
                      $errcode = "20231";
                      $error[] = $mgmt_config['today']."|hypercms_media.inc.php|error|$errcode|exec of imagemagick (code:$errorCode, command:$cmd) failed in createmedia for file: ".$file;
                    }
                    // on success
                    else $converted = true;
                  }
                  // CASE: Adobe Photoshop / Adobe Illustrator: layered files
                  elseif ($file_ext == ".ai" || $file_ext == ".psd")
                  {
                    if ($type == "thumbnail")
                    {
                      $newfile = $file_name.".thumb.jpg";
                      
                      $cmd = $mgmt_imagepreview[$imagepreview_ext]." ".$iccprofile." ".$imagecolorspace." \"".shellcmd_encode ($location_source.$file)."\" -flatten ".$imageresize." ".$imagequality." \"".shellcmd_encode ($location_dest.$newfile)."\"";
                    }
                    else
                    {
                      if ($type == "original") $newfile = $file_name.".".$imageformat;
                      else $newfile = $file_name.".".$type.".".$imageformat;
  
                      if ($crop_mode)
                      {
                        $cmd = $mgmt_imagepreview[$imagepreview_ext]." ".$imagedensity." ".$iccprofile." ".$imagecolorspace." \"".shellcmd_encode ($buffer_file)."\" -flatten -crop ".$imagewidth."x".$imageheight."+".$offsetX."+".$offsetY." ".$imageBrightnessContrast." ".$imagequality." \"".shellcmd_encode ($location_dest.$newfile)."\"";
                      }
                      else
                      {
                        // split layers into files if type is view (used for image editing)
                        // if (substr_count ($type, "view.") > 0)
                        // {
                        //   $cmd = $mgmt_imagepreview[$imagepreview_ext]." ".$imagedensity." ".$iccprofile." ".$imagecolorspace." \"".shellcmd_encode ($buffer_file)."\" ".$imageresize." ".$imagerotate." ".$imageBrightnessContrast." ".$imageflip." ".$sepia." ".$sharpen." ".$blur." ".$sketch." ".$paint." ".$imagequality." \"".shellcmd_encode ($location_dest.$newfile)."\"";
                        //   @exec ($cmd, $buffer, $errorCode);
                        // }
                        
                        $cmd = $mgmt_imagepreview[$imagepreview_ext]." ".$imagedensity." ".$iccprofile." ".$imagecolorspace." \"".shellcmd_encode ($buffer_file)."\" -flatten ".$imageresize." ".$imagerotate." ".$imageBrightnessContrast." ".$imageflip." ".$sepia." ".$sharpen." ".$blur." ".$sketch." ".$paint." ".$imagequality." \"".shellcmd_encode ($location_dest.$newfile)."\"";
                      }
                    }

                    @exec ($cmd, $buffer, $errorCode);
      
                    // on error
                    if ($errorCode)
                    {
                      $errcode = "20232";
                      $error[] = $mgmt_config['today']."|hypercms_media.inc.php|error|$errcode|exec of imagemagick (code:$errorCode, command:$cmd) failed in createmedia for file: ".$file;   
                    }
                    // on success
                    else $converted = true;
                  }
                  // CASE: Standard images
                  else
                  {
                    // only for RAW image
                    if (is_rawimage ($file_ext))
                    {
                      $imagecolorspace = "";
                    }
  
                    if ($type == "thumbnail" || $type == "annotation")
                    {
                      if ($type == "annotation") $newfile = $file_name.".annotation.jpg";
                      else $newfile = $file_name.".thumb.jpg";
                      
                      // reduce thumbnail size if original image is smaller then the defined thumbnail image size
                      if ($imagewidth_orig > 0 && $imagewidth_orig < $imagewidth && $imageheight_orig > 0 && $imageheight_orig < $imageheight)
                      {
                        $imageresize = "-resize ".round ($imagewidth_orig, 0)."x".round ($imageheight_orig, 0);
                      }
                       
                      $cmd = $mgmt_imagepreview[$imagepreview_ext]." ".$iccprofile." ".$imagecolorspace." \"".shellcmd_encode ($location_source.$file)."[0]\" -size ".$imagewidth."x".$imageheight." ".$imageresize." -background white -alpha remove ".$imagequality." \"".shellcmd_encode ($location_dest.$newfile)."\"";
                    }
                    else
                    {
                      if ($type == "original") $newfile = $file_name.".".$imageformat;
                      else $newfile = $file_name.".".$type.".".$imageformat;
                          
                      if ($crop_mode)
                      {
                        $cmd = $mgmt_imagepreview[$imagepreview_ext]." ".$imagedensity." ".$iccprofile." ".$imagecolorspace." \"".shellcmd_encode ($buffer_file)."[0]\" -crop ".$imagewidth."x".$imageheight."+".$offsetX."+".$offsetY." ".$imagerotate." ".$imageBrightnessContrast." ".$imageflip." ".$sepia." ".$sharpen." ".$blur." ".$sketch." ".$paint." ".$imagequality." \"".shellcmd_encode ($location_dest.$newfile)."\"";
                      }
                      else
                      {
                        $cmd = $mgmt_imagepreview[$imagepreview_ext]." ".$imagedensity." ".$iccprofile." ".$imagecolorspace." \"".shellcmd_encode ($buffer_file)."[0]\" -size ".$imagewidth."x".$imageheight." ".$imageresize." ".$imagerotate." ".$imageBrightnessContrast." ".$imageflip." ".$sepia." ".$sharpen." ".$blur." ".$sketch." ".$paint." ".$imagecolorspace." ".$imagequality." \"".shellcmd_encode ($location_dest.$newfile)."\"";
                      }
                    }
  
                    @exec ($cmd, $error_array, $errorCode);

                    // on error
                    if ($errorCode)
                    {            
                      $errcode = "20234";
                      $error[] = $mgmt_config['today']."|hypercms_media.inc.php|error|$errcode|exec of imagemagick (code:$errorCode) (command:$cmd) failed in createmedia for file: ".$file."<br />".implode ("<br />", $error_array);   
                    }
                    // on success
                    else $converted = true;
                  }
  
                  // WATERMARK AND PROCESS IMAGE ON SUCCESS: if new file is larger than 5 bytes
                  if ($converted == true && !empty ($newfile) && is_file ($location_dest.$newfile) && filesize ($location_dest.$newfile) > 5)
                  {
                    // watermark image using composite command (using a temporaray intermediate image file in order to restore image if watermarking failed)
                    if ($type != "thumbnail" && $type != "original" && !empty ($watermark))
                    {
                      $cmd = getlocation ($mgmt_imagepreview[$imagepreview_ext])."composite ".$watermark." \"".shellcmd_encode ($location_dest.$newfile)."\" \"".shellcmd_encode ($location_temp."watermark.".$newfile)."\"";
  
                      @exec ($cmd, $error_array, $errorCode);

                      // on error
                      if ($errorCode)
                      {
                        // delete temporary watermarked image file
                        if (is_file ($location_temp."watermark.".$newfile)) unlink ($location_temp."watermark.".$newfile);
  
                        $errcode = "20262";
                        $error[] = $mgmt_config['today']."|hypercms_media.inc.php|error|$errcode|exec of imagemagick (code:$errorCode, command:$cmd) failed in watermark file: ".$newfile."<br />".implode ("<br />", $error_array);
                      }
                      // on success
                      else
                      {
                        // overwrite source file with watermarked image file
                        @rename ($location_temp."watermark.".$newfile, $location_dest.$newfile);
                      }
                    }
                    
                    // get media information from thumbnail
                    if ($type == "thumbnail")
                    {
                      $imagecolor = getimagecolors ($site, $newfile);
                    
                      // write media information to container and DB
                      if (!empty ($container_id))
                      {
                        $setmedia = rdbms_setmedia ($container_id, $filesize_orig, $filetype_orig, $imagewidth_orig, $imageheight_orig, $imagecolor['red'], $imagecolor['green'], $imagecolor['blue'], $imagecolor['colorkey'], $imagecolor['imagetype'], $md5_hash);
                      }
                    }

                    // on success
                    if (is_file ($location_dest.$newfile))
                    {
                      // get new file extension
                      $newfile_ext = strtolower (strrchr ($newfile, "."));
    
                      // copy metadata from original file using EXIFTOOL
                      if ($type != "thumbnail" && $type != "origthumb") copymetadata ($buffer_file, $location_dest.$newfile);

                      if ($type == "thumbnail" || $type == "origthumb" || $type == "original")
                      {
                        // remote client
                        remoteclient ("save", "abs_path_media", $site, $location_dest, "", $newfile, "");
                      }
                      
                      // delete original image, if it has been converted to another format (another file extension)
                      if ($type == "original" && !empty ($file_ext) && !empty ($newfile_ext) && $file_ext != $newfile_ext)
                      {
                        deletefile ($location_source_orig, $file_orig, 0);
                      
                        // delete from cloud storage
                        if (function_exists ("deletecloudobject")) deletecloudobject ($site, $location_source_orig, $file_orig, $user);
                        
                        // remote client
                        remoteclient ("delete", "abs_path_media", $site, $location_source_orig, "", $file_orig, ""); 
                      }
                    }
                  }
                  // on conversion error
                  else
                  {
                    // restore original image from buffer file
                    if ($type == "original" && is_file ($buffer_file))
                    {
                      copy ($buffer_file, $location_source.$file);
                    }
                    // delete failed thumbnail image file
                    elseif ($type == "thumbnail" && is_file ($location_dest.$file_name.".thumb.jpg"))
                    {
                      // delete thumbnail file
                      unlink ($location_dest.$file_name.".thumb.jpg");
                      
                      // delete from cloud storage
                      if (function_exists ("deletecloudobject")) deletecloudobject ($site, $location_dest, $file_name.".thumb.jpg", $user);
                      
                      // remote client
                      if ($type == "thumbnail" || $type == "origthumb" ||$type == "original") remoteclient ("delete", "abs_path_media", $site, $location_dest, "", $file_name.".thumb.jpg", "");
                    }
                  }

                  // delete buffer file
                  if ($type != "thumbnail" && is_file ($buffer_file)) unlink ($buffer_file);          
                }
                // -------------------- convert image using GD-Library (no watermarking supported) -----------------------
                elseif ($imagewidth_orig > 0 && $imageheight_orig > 0 && (empty ($mgmt_imagepreview[$imagepreview_ext]) || $mgmt_imagepreview[$imagepreview_ext] == "GD") && in_array (strtolower($file_ext), $GD_allowed_ext))
                {
                  // calculate aspect ratio
                  $imageratio_orig = $imagewidth_orig / $imageheight_orig;
                  $imageratio = $imagewidth / $imageheight;
                  
                  if ($type == "thumbnail")
                  {
                    // reduce thumbnail size, if original image will be smaller then the defined thumbnail image size
                    if ($imagewidth_orig < $imagewidth && $imageheight_orig < $imageheight)
                    {
                      $resizedwidth = intval ($imagewidth_orig);
                      $resizedheight = intval ($imageheight_orig);
                    }
                  }
                  
                  // calculate image size to fit image in the given image size frame (imagewidth x imageheight), original aspect ratio will be kept
                  if (empty ($resizedwidth) || empty ($resizedheight))
                  {    
                    if ($imageratio_orig >= $imageratio)
                    {
                      $resizedwidth = intval ($imagewidth);
                      $resizedheight = round (($resizedwidth / $imageratio_orig), 0);
                    }
                    else
                    {
                      $resizedheight = intval ($imageheight);
                      $resizedwidth = round (($resizedheight * $imageratio_orig), 0);
                    }
                  }
                    
                  $imgresized = @ImageCreateTrueColor ($resizedwidth, $resizedheight);
              
                  if ($file_ext == ".gif") $imgsource = @ImageCreateFromGif ($location_source.$file);
                  elseif ($file_ext == ".jpg" || $file_ext == ".jpeg") $imgsource = @ImageCreateFromJpeg ($location_source.$file);
                  elseif ($file_ext == ".png") $imgsource = @ImageCreateFromPng ($location_source.$file);
                  else return false;
              
                  // crop image
                  if ($crop_mode)
                  {
                    @imagecopyresampled ($imgresized, $imgsource, 0, 0, $offsetX, $offsetY, $imagewidth, $imageheight, $imagewidth, $imageheight);
                  }
                  // resize image
                  else
                  {
                    @ImageCopyResized ($imgresized, $imgsource, 0, 0, 0, 0, $resizedwidth, $resizedheight, $imagewidth_orig, $imageheight_orig);
                  }
  
                  // create image in defined file format
                  if ($imageformat == "jpg" && function_exists ("imagejpeg"))
                  {
                    if ($type == "thumbnail")
                    {
                      $newfile = $file_name.".thumb.jpg";
                      $result = @imagejpeg ($imgresized, $location_dest.$newfile);
                    }
                    else
                    {
                      if ($type == "original") $newfile = $file_name.".jpg";
                      else $newfile = $file_name.".".$type.".jpg";
                      
                      $result = @imagejpeg ($imgresized, $location_dest.$newfile);
                    }
                  }
                  elseif ($imageformat == "png" && function_exists ("imagepng"))
                  {
                    if ($type == "thumbnail")
                    {
                      $newfile = $file_name.".thumb.png";
                      $result = @imagepng ($imgresized, $location_dest.$newfile);
                    }
                    else
                    {
                      if ($type == "original") $newfile = $file_name.".png";
                      else $newfile = $file_name.".".$type.".png";
                        
                      $result = @imagepng ($imgresized, $location_dest.$newfile);
                    }
                  }
                  elseif ($imageformat == "gif" && function_exists ("imagegif"))
                  {
                    if ($type == "thumbnail")
                    {
                      $newfile = $file_name.".thumb.gif";
                      $result = @imagegif ($imgresized, $location_dest.$newfile);
                    }
                    else
                    {
                      if ($type == "original") $newfile = $file_name.".gif";
                      else $newfile = $file_name.".".$type.".gif";
                        
                      $result = @imagegif ($imgresized, $location_dest.$newfile);
                    }
                  }
                  else $result = false;
  
                  // delete original file if file extension has been changed
                  if ($result == true && $file_ext != ".".$format_set && $type == "original" && is_file ($location_orig.$file_orig))
                  {
                    unlink ($location_orig.$file_orig);
                  }
              
                  @ImageDestroy ($imgsource);
                  @ImageDestroy ($imgresized);
  
                  if ($result == true)
                  {
                    $converted = true;
                    
                    // rotate image
                    if ($type != "thumbnail" && $imagerotation != "") $result = rotateimage ($site, $location_dest.$newfile, $imagerotation, $format_set);

                    if ($type == "thumbnail" || $type == "origthumb" ||$type == "original")
                    {
                      // save in cloud storage
                      if (function_exists ("savecloudobject")) savecloudobject ($site, $location_dest, $newfile, $user);
                      
                      // remote client
                      remoteclient ("save", "abs_path_media", $site, $location_dest, "", $newfile, "");
                    }
                    
                    // get media information from thumbnail
                    if ($type == "thumbnail")
                    {
                      $imagecolor = getimagecolors ($site, $newfile);
                      
                      // write media information to container and DB
                      if (!empty ($container_id))
                      {
                        $setmedia = rdbms_setmedia ($container_id, $filesize_orig, $filetype_orig, $imagewidth_orig, $imageheight_orig, $imagecolor['red'], $imagecolor['green'], $imagecolor['blue'], $imagecolor['colorkey'], $imagecolor['imagetype'], $md5_hash);
                      }
                    }
                  }
                }
                
                // create thumbnail (new preview of original image)
                if ($converted == true && $type == "original")
                {
                  createmedia ($site, $location_dest, $location_dest, $newfile, "jpg", "thumbnail");
                }    
              }               
            }
          }
        }    
      }
      
      // --------------- if media conversion software is given ----------------
      if (is_array ($mgmt_mediapreview) && sizeof ($mgmt_mediapreview) > 0)
      {
        // convert the media file with FFMPEG
        // Audio Options:
        // -ac ... number of audio channels
        // -an ... disable audio
        // -ar ... audio sampling frequency (default = 44100 Hz)
        // -b:a ... audio bitrate (default = 64k)
        // -c:a ... audio codec (e.g. libmp3lame, libfaac, libvorbis)
        // Video Options:
        // -b:v ... video bitrate in bit/s (default = 200 kb/s)
        // -c:v ... video codec (e.g. libx264)
        // -cmp ... full pel motion estimation compare function (used for mp4)
        // -f ... force file format (like flv, mp4, ogv, webm, mp3)
        // -flags ... specific options for video encoding
        // -mbd ... macroblock decision algorithm (high quality mode)
        // -r ... frame rate in Hz (default = 25)
        // -s:v ... frame size in pixel (WxH)
        // -sh ... sharpness (blur -1 up to 1 sharpen)
        // -gbcs ... gamma, brightness, contrast, saturation (neutral values are 0.0:1:0:0.0:1.0)
        // -wm .... watermark image and watermark positioning (PNG-file-reference->positioning [topleft, topright, bottomleft, bottomright] e.g. image.png->topleft)
        // -rotate ... rotate video
        // -fv ... flip video in vertical direction
        // -fh ... flop video in horizontal direction
        
        // define default option for support of versions before 5.3.4
        // note: audio codec could be "mp3" or in newer ffmpeg versions "libmp3lame"!
        $mgmt_mediaoptions_video = "-b:v 768k -s:v 480x320 -f mp4 -c:a libfaac -b:a 64k -ac 2 -c:v libx264 -mbd 2 -flags +loop+mv4 -cmp 2 -subcmp 2";
        $mgmt_mediaoptions_audio = "-f mp3 -c:a libmp3lame -b:a 64k -ar 22500";
        
        // reset type to input value
        $type = $type_memory;

        // define format if not set or 'origthumb' for preview is requested (this defines the file extension and the rendering options)
        if ($format == "" || $type == "origthumb")
        {
          // reset media options array to use default options for rendering of the preview video/audio
          if ($type == "origthumb") $mgmt_mediaoptions = array();

          // get default options for audio file
          if (is_audio ($file_ext))
          {
            // set default options string if no valid one is provided
            if (empty ($mgmt_mediaoptions['thumbnail-audio']) || strpos ("_".$mgmt_mediaoptions['thumbnail-audio'], "-f ") == 0)
            {
              $mgmt_mediaoptions['thumbnail-audio'] = $mgmt_mediaoptions_audio;
            }
           
            // get format from options string
            $format_set = getoption ($mgmt_mediaoptions['thumbnail-audio'], "-f");
            
            // set options string
            if ($format_set != "") $mgmt_mediaoptions['.'.$format_set] = $mgmt_mediaoptions['thumbnail-audio'];
            else $mgmt_mediaoptions['.mp3'] = $mgmt_mediaoptions_audio;
          }
          // get default options for video file
          else
          {
            // set default options string if no valid one is provided
            if (empty ($mgmt_mediaoptions['thumbnail-video']) || strpos ("_".$mgmt_mediaoptions['thumbnail-video'], "-f ") == 0)
            {
              $mgmt_mediaoptions['thumbnail-video'] = $mgmt_mediaoptions_video;
            }
          
            // get format from options string
            $format_set = getoption ($mgmt_mediaoptions['thumbnail-video'], "-f");
            
            // set options string
            if ($format_set != "") $mgmt_mediaoptions['.'.$format_set] = $mgmt_mediaoptions['thumbnail-video'];
            else $mgmt_mediaoptions['.mp4'] = $mgmt_mediaoptions_video;
          }
        }
        // define target format if type is "original" (overwrite originla file with same format)
        elseif ($format == "" || $type == "original")
        {
          $format_set = strtolower (substr ($file_ext, 1));
        }
        // use provided target format
        elseif ($format != "")
        {
          $format_set = strtolower ($format);
        }

        // original video info
        $videoinfo = getvideoinfo ($location_source.$file);
              
        reset ($mgmt_mediapreview);
        
        // supported extensions for media rendering
        foreach ($mgmt_mediapreview as $mediapreview_ext => $mediapreview)
        {        
          // check file extension
          if ($file_ext != "" && substr_count (strtolower ($mediapreview_ext).".", $file_ext.".") > 0)
          {
            reset ($mgmt_mediaoptions);  

            // extensions for certain media rendering options
            foreach ($mgmt_mediaoptions as $mediaoptions_ext => $mediaoptions)
            {
              // get media rendering options based on given destination format
              if ($mediaoptions_ext != "thumbnail-video" && $mediaoptions_ext != "thumbnail-audio" && $mediaoptions_ext != "segments" && substr_count (strtolower ($mediaoptions_ext).".", ".".$format_set.".") > 0)
              {
                // media format (media file extension) definition
                if (strpos ("_".$mgmt_mediaoptions[$mediaoptions_ext], "-f ") > 0)
                {
                  $videoformat = strtolower (getoption ($mgmt_mediaoptions[$mediaoptions_ext], "-f"));
                  
                  if ($videoformat == "" || $videoformat == false) $videoformat = $format_set; 
                  
                  $mgmt_mediaoptions[$mediaoptions_ext] = str_replace ("-f ".$videoformat, "-f ".shellcmd_encode (trim($videoformat)), $mgmt_mediaoptions[$mediaoptions_ext]);
                }
           
                // video filters
                $vfilter = array();
                
                // sharpness
                if (strpos ("_".$mgmt_mediaoptions[$mediaoptions_ext], "-sh ") > 0)
                {
                  // Luminance is the video level of the black and white part of a video signal.
                  // Chroma is just another word for color.
                  
                  // Values for unsharp mask:
                  // luma_msize_x:luma_msize_y:luma_amount:chroma_msize_x:chroma_msize_y:chroma_amount
                  
                  // Negative values for the amount will blur the input video, while positive values will sharpen. 
                  // All parameters are optional and default to the equivalent of the string '5:5:1.0:0:0:0.0'.
                  // luma_msize_x ... set the luma matrix horizontal size. It can be an integer between 3 and 13, default value is 5
                  // luma_msize_y ... set the luma matrix vertical size. It can be an integer between 3 and 13, default value is 5
                  // luma_amount ... set the luma effect strength. It can be a float number between -2.0 and 5.0, default value is 1.0
                  // chroma_msize_x ... set the chroma matrix horizontal size. It can be an integer between 3 and 13, default value is 0
                  // chroma_msize_y ... set the chroma matrix vertical size. It can be an integer between 3 and 13, default value is 0
                  // luma_amount ... set the chroma effect strength. It can be a float number between -2.0 and 5.0, default value is 0.0
  
                  // get sharpness defined by media option (represents chorma and luma amount)
                  $sharpness = getoption ($mgmt_mediaoptions[$mediaoptions_ext], "-sh");
                  
                  // default value
                  if ($sharpness < -1 || $sharpness > 1) $amount = "1";
                  // blur
                  elseif ($sharpness < 0) $amount = round ($sharpness * 2, 2);
                  // sharpen
                  elseif ($sharpness > 0) $amount = round ($sharpness * 5, 2);
  
                  $vfilter[] = "unsharp=5:5:".floatval($amount).":5:5:".floatval($amount);
                  
                  // remove from options string since it will be added later as a video filter
                  $mgmt_mediaoptions[$mediaoptions_ext] = str_replace ("-sh ".$sharpness, "", $mgmt_mediaoptions[$mediaoptions_ext]);
                }
                
                // rotate (using video filters)
                if (strpos ("_".$mgmt_mediaoptions[$mediaoptions_ext], "-rotate ") > 0)
                {
                  // get degrees defined by media option 
                  $rotate = getoption ($mgmt_mediaoptions[$mediaoptions_ext], "-rotate");
                  
                  // usage: transpose=1
                  // for the transpose parameter you can pass:
                  // 0 = 90CounterCLockwise and Vertical Flip (default)
                  // 1 = 90Clockwise
                  // 2 = 90CounterClockwise
                  // 3 = 90Clockwise and Vertical Flip
                  
                  if ($rotate == "90") $vfilter[] = "transpose=1";
                  elseif ($rotate == "180") $vfilter[] = "hflip,vflip";
                  elseif ($rotate == "-90") $vfilter[] = "transpose=2";
                  
                  // remove from options string since it will be added later as a video filter
                  $mgmt_mediaoptions[$mediaoptions_ext] = str_replace ("-rotate ".$rotate, "", $mgmt_mediaoptions[$mediaoptions_ext]);
                }
                
                // flip vertically (using video filters)
                if (strpos ("_".$mgmt_mediaoptions[$mediaoptions_ext], "-fv ") > 0)
                {
                  // usage: hlfip (means horizontal direction = vertical flip)
                  $vfilter[] = "hflip";
                  
                  // remove from options string since it will be added later as a video filter
                  $mgmt_mediaoptions[$mediaoptions_ext] = str_replace ("-fv ", "", $mgmt_mediaoptions[$mediaoptions_ext]);
                }
                
                // flip horizontally (using video filters)
                if (strpos ("_".$mgmt_mediaoptions[$mediaoptions_ext], "-fh ") > 0)
                {
                  // usage: vlfip (means vertical direction = horizontal flip)
                  $vfilter[] = "vflip";
                  
                  // remove from options string since it will be added later as a video filter
                  $mgmt_mediaoptions[$mediaoptions_ext] = str_replace ("-fh ", "", $mgmt_mediaoptions[$mediaoptions_ext]);
                }
  
                // gamma, brigntness, contrast, saturation, red-, green-, blue-gamm (using video filters)
                if (strpos ("_".$mgmt_mediaoptions[$mediaoptions_ext], "-gbcs ") > 0)
                {
                  // get sharpness defined by media option 
                  $gbcs = getoption ($mgmt_mediaoptions[$mediaoptions_ext], "-gbcs");
                  
                  // Values for EQ2 filter
                  // gamma:contrast:brightness:saturation:rg:gg:bg:weight
                  // (note that the FFMPEG docs show this incorrectly as gamma, brightness, contrast)
                  
                  // initial gamma value (default: 1.0 = gamma correction is off)
                  // initial contrast, where negative values result in a negative image (default: 1.0)
                  // initial brightness (default: 0.0)
                  // initial saturation (default: 1.0)
                  // gamma value for the red component (default: 1.0) not supported by hyperCMS
                  // gamma value for the green component (default: 1.0) not supported by hyperCMS
                  // gamma value for the blue component (default: 1.0) not supported by hyperCMS
                  // The weight parameter can be used to reduce the effect of a high gamma value on bright image areas, e.g. keep them from getting overamplified and just plain white.
                  // A value of 0.0 turns the gamma correction all the way down while 1.0 leaves it at its full strength (default: 1.0).
                  // Weight is not supported by hyperCMS.
                  
                  list ($gamma, $brightness, $contrast, $saturation) = explode (":", $gbcs);
                  
                  if ($gamma < 0 || $gamma > 2) $gamma = "1";
                  if ($brightness < -1 || $brightness > 1) $brightness = "0";
                  if ($contrast < 0 || $contrast > 2) $contrast = "1";
                  if ($saturation < 0 || $saturation > 2) $saturation = "1";
                  
                  $vfilter[] = "mp=eq2=".floatval($gamma).":".floatval($contrast).":".floatval($brightness).":".floatval($saturation);
  
                  // remove from options string since it will be added later as a video filter
                  $mgmt_mediaoptions[$mediaoptions_ext] = str_replace ("-gbcs ".$gbcs, "", $mgmt_mediaoptions[$mediaoptions_ext]);
                }
                
                // join filter options an add to options string
                if (sizeof ($vfilter) > 0)
                {
                  $mgmt_mediaoptions[$mediaoptions_ext] = " -vf \"".implode (", ", $vfilter)."\" ".$mgmt_mediaoptions[$mediaoptions_ext];
                }
                
                // video size
                if (strpos ("_".$mgmt_mediaoptions[$mediaoptions_ext], "-s:v ") > 0 || (!empty ($videoinfo['width']) && !empty ($videoinfo['height'])))
                {
                  // get video size defined by media option 
                  $mediasize = getoption ($mgmt_mediaoptions[$mediaoptions_ext], "-s:v");
                  
                  if (strpos ($mediasize, "x") > 0)
                  {
                    list ($mediawidth, $mediaheight) = explode ("x", $mediasize);
                  }
                  else
                  {
                    $mediawidth = $videoinfo['width'];
                    $mediaheight = $videoinfo['height'];
                  }
  
                  // if valid size is provided
                  if ($mediawidth > 0 && $mediaheight > 0)
                  {
                    // keep video ratio for original thumbnail video
                    if ($type == "origthumb" && $videoinfo['ratio'] != "")
                    {
                      // if original video size is smaller than the defined size, the size will be reduced to the original size
                      if ($videoinfo['width'] > 0 && $videoinfo['height'] > 0 && $videoinfo['width'] < $mediawidth && $videoinfo['height'] < $mediaheight)
                      {
                        $mediawidth = $videoinfo['width'];
                        $mediaheight = $videoinfo['height'];
                      }
                      // use input size defined by media option
                      else
                      {
                        // use mediawidth and calculate height
                        if ($videoinfo['ratio'] > 1)
                        {
                          $mediaheight = round((intval($mediawidth)/$videoinfo['ratio']), 0);
                        }
                        // use mediaheight and calculate width
                        else
                        {
                          $mediawidth = round((intval($mediaheight) * $videoinfo['ratio']), 0);
                        }
                      }
                    }                  
                    // else we use provided size (without keeping the original aspect ratio)
                    
                    // switch width and height for video rotation
                    if (!empty ($rotate) && ($rotate == "90" || $rotate == "-90"))
                    {
                      $temp = $mediawidth;
                      $mediawidth = $mediaheight;
                      $mediaheight = $temp;
                    }
                  
                    $mediasize_new = intval($mediawidth)."x".intval($mediaheight);
                    
                    $mgmt_mediaoptions[$mediaoptions_ext] = str_replace ("-s:v ".$mediasize, "-s:v ".$mediasize_new, $mgmt_mediaoptions[$mediaoptions_ext]);
                  }
                  // remove mediasize option and keep original video size
                  else
                  {
                    $mgmt_mediaoptions[$mediaoptions_ext] = str_replace ("-s:v ".$mediasize, "", $mgmt_mediaoptions[$mediaoptions_ext]);
                    
                    // set the video size for the video config file
                    if ($videoinfo['width'] > 0 && $videoinfo['height'] > 0)
                    {
                      if (!empty ($rotate) && ($rotate == "90" || $rotate == "-90"))
                      {
                        $mediawidth = $videoinfo['height'];
                        $mediaheight = $videoinfo['width'];
                      }
                      else
                      {
                        $mediawidth = $videoinfo['width'];
                        $mediaheight = $videoinfo['height'];
                      }
                    }
                  }  
                }

                // split media file if requested
                if (!empty ($mgmt_mediaoptions['segments']))
                {
                  // decode JSON string to array
                  $segments = json_decode ($mgmt_mediaoptions['segments'], true);
                
                  // split media file
                  if (is_array ($segments) && sizeof ($segments) > 0)
                  {
                    // count segments to be kept
                    $segment_counter = 0;
                    
                    foreach ($segments as $segment)
                    {                      
                      if ($segment['keep'] == 1) $segment_counter++;
                    }
                  
                    // if there is more than 1 segment
                    if ($segment_counter > 1)
                    {
                      $segment_seconds = 0;
                      $segment_starttime = "00:00:00.000";
                      $temp_files = array();
                      $i = 1;
                      
                      // slice file into segments
                      foreach ($segments as $id => $segment)
                      {                      
                        $segment_duration = $segment['seconds'] - $segment_seconds;

                        if ($segment['keep'] == 1)
                        {
                          $cmd = $mgmt_mediapreview[$mediapreview_ext]." -i \"".shellcmd_encode ($location_source.$file)."\" -ss ".$segment_starttime." -t ".$segment_duration." -sameq -f mpegts -target ntsc-vcd \"".$location_temp.shellcmd_encode ($file_name)."-".$i.".ts\"";

                          @exec ($cmd, $buffer, $errorCode);

                          // on error
                          if ($errorCode)
                          {
                            $errcode = "20239";
                            $error[] = $mgmt_config['today']."|hypercms_media.inc.php|error|$errcode|exec of ffmpeg (code:$errorCode, $cmd) failed in createmedia for file: ".$location_source.$file;           
                          }
                          //
                          else
                          {
                            $temp_files[] = $location_temp.$file_name."-".$i.".ts";
                            $i++;
                          }
                        }
                        
                        $segment_seconds = $segment['seconds'];
                        $segment_starttime = $segment['time'];
                      }
                    }
  
                    // join media file slices
                    if (sizeof ($temp_files) > 0)
                    {
                      $cmd = $mgmt_mediapreview[$mediapreview_ext]." -i \"concat:".implode ("|", $temp_files)."\" -sameq \"".$location_temp.shellcmd_encode ($file_orig)."\"";

                      @exec ($cmd, $buffer, $errorCode);

                      // remove file slices
                      foreach ($temp_files as $temp_file)
                      {
                        unlink ($temp_file);
                      }
                          
                      // on error
                      if ($errorCode)
                      {
                        $errcode = "20240";
                        $error[] = $mgmt_config['today']."|hypercms_media.inc.php|error|$errcode|exec of ffmpeg (code:$errorCode, $cmd) failed in createmedia for file: ".$location_source.$file_orig;           
                      }
                      // reset media file source
                      else
                      {
                        $location_source = $location_temp;
                        $file = $file_orig;
                      }
                    }
                    // only 1 segment
                    else
                    {
                      $segment_seconds = 0;
                      $segment_starttime = "00:00:00.000";
                        
                      foreach ($segments as $id => $segment)
                      {
                        if ($segment['keep'] == 1)
                        {
                          $segment_starttime = $segment_seconds;
                          $segment_endtime = $segment['seconds'];
                          $segment_duration = $segment_endtime - $segment_starttime;
                          break;
                        }
                        
                        $segment_seconds = $segment['seconds'];
                        $segment_starttime = $segment['time'];
                      }
                    
                      // rename file
                      $cut_add = "-ss ".$segment_starttime." -t ".$segment_duration." ";
                      
                      // add to options
                      $mgmt_mediaoptions[$mediaoptions_ext] = $cut_add.$mgmt_mediaoptions[$mediaoptions_ext];
                    }
                  }
                }
                
                // new file name
                if ($type == "original") $newfile = $file_orig;
                elseif ($type == "thumbnail") $newfile = $file_name.".thumb.".$format_set;
                elseif ($type == "origthumb") $newfile = $file_name.".orig.".$format_set;
                else $newfile = $file_name.".media.".$format_set;

                // temp file name
                $tmpfile = $file_name.".tmp.".$format_set;
                
                // create version of original media file and container (only works for files in the media content repository)
                if ($type == "original")
                {
                  $createversion = createversion ($site, $file_orig);
                }

                // render video
                $cmd = $mgmt_mediapreview[$mediapreview_ext]." -i \"".shellcmd_encode ($location_source.$file)."\" ".$mgmt_mediaoptions[$mediaoptions_ext]." \"".shellcmd_encode ($location_temp.$tmpfile)."\"";

                @exec ($cmd, $buffer, $errorCode);

                // delete joined slice temp file
                if (is_file ($location_temp.$file_orig))
                {
                  unlink ($location_temp.$file_orig);
                }
  
                // watermarking (using video filters)
                // set watermark options if defined in publication settings and not already defined
                if (!empty ($mgmt_config[$site]['watermark_video']) && strpos ("_".$mgmt_mediaoptions[$mediaoptions_ext], "-wm ") == 0 && !is_audio ($file_ext))
                {
                  $mgmt_mediaoptions[$mediaoptions_ext] .= " ".$mgmt_config[$site]['watermark_video'];
                }
                
                // dont watermark original file
                if ($type != "original" && is_file ($location_temp.$tmpfile) && filesize ($location_temp.$tmpfile) > 100 && strpos ("_".$mgmt_mediaoptions[$mediaoptions_ext], "-wm ") > 0)
                {
                  // get watermark defined by media option 
                  $watermarking = getoption ($mgmt_mediaoptions[$mediaoptions_ext], "-wm");
                  list ($watermark, $positioning, $geometry) = explode ("->", $watermarking);

                  if (!empty ($geometry))
                  {
                    $geometry = intval (@$geometry);
                    $geometry_x = $geometry_y = $geometry;
                  }
                  else
                  {
                    $geometry_x = 0;
                    $geometry_y = 0;
                  }
                  
                  // top left corner
                  if (strtolower(trim($positioning)) == "topleft") $vfilter_wm = "movie=".shellcmd_encode (trim($watermark))." [watermark]; [in][watermark] overlay=".shellcmd_encode (trim($geometry_x)).":".shellcmd_encode (trim($geometry_y))." [out]";
                  // top right corner
                  elseif (strtolower(trim($positioning)) == "topright") $vfilter_wm = "movie=".shellcmd_encode (trim($watermark))." [watermark]; [in][watermark] overlay=main_w-overlay_w-".shellcmd_encode (trim($geometry_x)).":".shellcmd_encode (trim($geometry_y))." [out]";
                  // bottom left corner
                  elseif (strtolower(trim($positioning)) == "bottomleft") $vfilter_wm = "movie=".shellcmd_encode (trim($watermark))." [watermark]; [in][watermark] overlay=".shellcmd_encode (trim($geometry_x)).":main_h-overlay_h-".shellcmd_encode (trim($geometry_y))." [out]";
                  // bottom right corner
                  elseif (strtolower(trim($positioning)) == "bottomright") $vfilter_wm = "movie=".shellcmd_encode (trim($watermark))." [watermark]; [in][watermark] overlay=main_w-overlay_w:main_h-overlay_h-".shellcmd_encode (trim($geometry_y))." [out]";
  
                  $tmpfile2 = $file_name.".tmp2.".$format_set;
                  
                  // apply watermark as video filter
                  if (!empty ($vfilter_wm))
                  {
                    // render video with watermark
                    $cmd = $mgmt_mediapreview[$mediapreview_ext]." -i \"".shellcmd_encode ($location_temp.$tmpfile)."\" -vf \"".$vfilter_wm."\" \"".shellcmd_encode ($location_temp.$tmpfile2)."\"";
                  
                    @exec ($cmd, $buffer, $errorCode);

                    // replace file
                    if (is_file ($location_temp.$tmpfile2)) rename ($location_temp.$tmpfile2, $location_temp.$tmpfile);
                  }
                }
  
                // on error or new file is smaller than 100 bytes
                if ($errorCode || filesize ($location_temp.$tmpfile) < 100)
                {
                  @unlink ($location_temp.$tmpfile);
                
                  $errcode = "20236";
                  $error[] = $mgmt_config['today']."|hypercms_media.inc.php|error|$errcode|exec of ffmpeg (code:$errorCode, $cmd) failed in createmedia for file: ".$location_source.$file;
                } 
                else
                {
                  $converted = true;
  
                  // inject metadata into FLV file using YAMDI (/usr/bin/yamdi)
                  if ($mgmt_mediametadata['.flv'] != "" && $format_set == "flv")
                  {
                    $tmpfile2 = $file_name.".tmp2.".$format_set;
                    
                    // inject meta data
                    $cmd = $mgmt_mediametadata['.flv']." -i \"".shellcmd_encode ($location_temp.$tmpfile)."\" -o \"".shellcmd_encode ($location_temp.$tmpfile2)."\"";
                    
                    @exec ($cmd, $buffer, $errorCode);

                    @unlink ($location_temp.$tmpfile);
                    
                    // on error
                    if ($errorCode)
                    {
                      $errcode = "20237";
                      $error[] = $mgmt_config['today']."|hypercms_media.inc.php|error|$errcode|exec of yamdi (code:$errorCode, $cmd) failed in createmedia for file: ".$location_source.$newfile;
                    }
                    // on success
                    else
                    {                  
                      if (@filesize ($location_temp.$tmpfile2) > 10) 
                      {
                        if (@is_file ($location_dest.$newfile)) @unlink ($location_dest.$newfile);
                        @rename ($location_temp.$tmpfile2, $location_dest.$newfile);
                      }
                      else 
                      {
                        @unlink ($location_temp.$tmpfile2);
                      }
                    }
                  }
                  // rename temp file to new file
                  else
                  {
                    if (@is_file ($location_dest.$newfile)) @unlink ($location_dest.$newfile);
                    @rename ($location_temp.$tmpfile, $location_dest.$newfile);
                  }
                }
                
                // generate video player config code for all video formats (thumbnails)
                if ($type == "thumbnail")
                {
                  // video thumbnail files
                  $video_extension_array = explode (".", substr (strtolower ($hcms_ext['video']), 1));
                  
                  if (is_array ($video_extension_array))
                  {
                    // generate video file links for all available formats
                    $videos = array();

                    foreach ($video_extension_array as $video_extension)
                    {
                      if ($video_extension != "" && @is_file ($location_dest.$file_name.".thumb.".$video_extension))
                      {
                        // thumbnail video
                        $videos[$video_extension] = $site."/".$file_name.".thumb.".$video_extension;
                      }
                    }
                  }
                  
                  // define config extension (audio or video)
                  if (is_audio ($file_ext)) $config_extension = ".config.audio";
                  else $config_extension = ".config.video";
                }
                // generate video player config code for indivdual video
                else
                {
                  // generate video file links for individual generated video formats
                  $videos = array();
                  $videos[$format_set] = $site."/".$newfile;
                  
                  if ($type == "origthumb" || $type == "original") $config_extension = ".config.orig";
                  else $config_extension = ".config.".$format_set;
                }
  
                // capture screen from video to use as thumbnail image
                if ($type == "origthumb" || $type == "original") createthumbnail_video ($site, $location_dest, $location_dest, $newfile, "00:00:01");
     
                // new video info (only if it is not a thumbnail file of the original file)
                if ($type == "original") $videoinfo = getvideoinfo ($location_dest.$newfile);
                
                // set media width and height to empty string
                if ($mediawidth < 1 || $mediaheight < 1)
                {
                  $mediawidth = "";
                  $mediaheight = "";
                }

                // save config
                if ($type == "origthumb" || $type == "original")
                {
                  savemediaplayer_config ($location_dest, $file_name.$config_extension, $videos, $videoinfo['width'], $videoinfo['height'], $videoinfo['filesize'], $videoinfo['duration'], $videoinfo['videobitrate'], $videoinfo['audiobitrate'], $videoinfo['audiofrequenzy'], $videoinfo['audiochannels']);
                }
                else
                {
                  savemediaplayer_config ($location_dest, $file_name.$config_extension, $videos, $mediawidth, $mediaheight, $videoinfo['filesize'], $videoinfo['duration'], $videoinfo['videobitrate'], $videoinfo['audiobitrate'], $videoinfo['audiofrequenzy'], $videoinfo['audiochannels']);
                }
                
                // get video information to save in DB
                if ($type == "origthumb" || $type == "original")
                { 
                  if (is_array ($videoinfo))
                  {
                    $mediawidth_orig = $videoinfo['width'];
                    $mediaheight_orig = $videoinfo['height'];
                    $imagetype_orig = $videoinfo['imagetype'];
                  }
                  else 
                  {
                    $mediawidth_orig = "";
                    $mediaheight_orig = "";
                    $imagetype_orig = "";
                  }
                  
                  // write media information to DB
                  if (!empty ($container_id))
                  {
                    $setmedia = rdbms_setmedia ($container_id, $filesize_orig, $filetype_orig, $mediawidth_orig, $mediaheight_orig, "", "", "", "", $imagetype_orig, $md5_hash);
                  }
                  
                  // create preview (new preview for video/audio file)
                  if ($type == "original")
                  {
                    createmedia ($site, $location_dest, $location_dest, $newfile, "", "origthumb");
                  }
                }
                
                // save in cloud storage
                if (function_exists ("savecloudobject")) savecloudobject ($site, $location_dest, $newfile, $user);
                
                // remote client
                remoteclient ("save", "abs_path_media", $site, $location_dest, "", $newfile, "");
              }
            }     
          }
        }
      }
    }
        
    // no option was found for given format or no media conversion software defined
    if (empty ($setmedia) && ($type == "thumbnail" || $type == "origthumb"))
    {
      // write media information to container and DB
      if (!empty ($container_id) && !empty ($md5_hash))
      {
        $setmedia = rdbms_setmedia ($container_id, $filesize_orig, $filetype_orig, $imagewidth_orig, $imageheight_orig, "", "", "", "", "", $md5_hash);
      }
    }

    // delete temp files
    if ($temp_source['result'] && $temp_source['created']) deletefile ($temp_source['templocation'], $temp_source['tempfile'], 0);
    if (!empty ($temp_raw) && $temp_raw['result'] && $temp_raw['created']) deletefile ($temp_raw['templocation'], $temp_raw['tempfile'], 0);
    
    // encrypt and save data if media file is not a thumbnail image
    if (is_file ($mgmt_config['abs_path_cms']."encryption/hypercms_encryption.inc.php") && $force_no_encrypt == false && !empty ($newfile) && !is_thumbnail ($newfile) && isset ($mgmt_config[$site]['crypt_content']) && $mgmt_config[$site]['crypt_content'] == true)
    {
      // encrypt new file
      $data = encryptfile ($location_dest, $newfile);
      if (!empty ($data)) savefile ($location_dest, $newfile, $data);

      // encrypt original image file, required in case of a RAW image
      if (!is_encryptedfile ($location_dest, $file))
      {
        $data = encryptfile ($location_dest, $file);
        if (!empty ($data)) savefile ($location_dest, $file, $data);
      }
    }

    // save in cloud storage
    if (!empty ($newfile) && is_file ($location_dest.$newfile) && ($type == "thumbnail" || $type == "origthumb" || $type == "original"))
    {
      if (function_exists ("savecloudobject")) savecloudobject ($site, $location_dest, $newfile, $user);
    }
    
    // save log
    savelog (@$error); 

    // return result
    if ($converted == true && !empty ($newfile)) return $newfile;
    else return false;    
  }
  else return false;
}

// ---------------------- convertmedia -----------------------------
// function: convertmedia()
// input: publication name, path to source dir, path to destination dir, file name, target format (file extension w/o dot) of destination file, media configuration to be used (optional),
//        force the file to be not encrypted even if the content of the publication must be encrypted [true,false] (optional)
// output: new file name / false on error

// description:
// Converts and creates a new image/video/audio or document from original. this is a wrapper function for createmedia and createdocument

function convertmedia ($site, $location_source, $location_dest, $mediafile, $format, $media_config="", $force_no_encrypt=false)
{
  global $mgmt_config, $mgmt_imagepreview, $mgmt_mediapreview, $mgmt_mediaoptions, $mgmt_imageoptions, $mgmt_maxsizepreview, $mgmt_mediametadata, $hcms_ext;
  
  if (valid_publicationname ($site) && valid_locationname ($location_source) && valid_locationname ($location_dest) && valid_objectname ($mediafile) && $format != "")
  {
    // load file extensions
    if (empty ($hcms_ext) || !is_array ($hcms_ext)) require ($mgmt_config['abs_path_cms']."include/format_ext.inc.php");

    // add slash if not present at the end of the location string
    if (substr ($location_source, -1) != "/") $location_source = $location_source."/";
    if (substr ($location_dest, -1) != "/") $location_dest = $location_dest."/";

    // convert-config is not supported when we are using createdocument
    if (is_document ($format))
    {
      $result_conv = createdocument ($site, $location_source, $location_dest, $mediafile, $format, $force_no_encrypt);
    }
    // image, video or audio
    elseif ($media_config != "")
    {
      // information needed to extract the file name only
      $media_info = getfileinfo ($site, $mediafile, "comp");
      
      // predicting the name the file will get by createmedia
      $newname = $media_info['filename'].".".$media_config.".".$format;

      // generate new file only if necessary
      if (!is_file ($location_dest.$newname) || @filemtime ($location_source.$mediafile) > @filemtime ($location_dest.$newname)) 
      {
        $result_conv = createmedia ($site, $location_source, $location_dest, $mediafile, $format, $media_config, $force_no_encrypt);
      }
      // use the existing file
      else $result_conv = $newname;
    }
    else $result_conv = false;

    return $result_conv;
  }
  else return false;
}

// ---------------------- convertimage -----------------------------
// function: convertimage()
// input: publication name, path to source image file, path to destination dir, format (file extension w/o dot) of destination file (optional), 
//        colorspace of new image [CMY,CMYK,Gray,HCL,HCLp,HSB,HSI,HSL,HSV,HWB,Lab,LCHab,LCHuv,LMS,Log,Luv,OHTA,Rec601YCbCr,Rec709YCbCr,RGB,scRGB,sRGB,Transparent,XYZ,YCbCr,YCC,YDbDr,YIQ,YPbPr,YUV] (optional), 
//        width in pixel/mm/inch (optional), height in pixel/mm/inch (optional), slug in pixel/mm/inch (optional), units for width, height and slug [px,mm,inch] (optional),
//        dpi (optional), image quality (1 to 100)    
// output: new file name / false on error

// description:
// Converts and creates a new image from original. the new image keeps will be resized and cropped to fit width and height.
// This is a wrapper function for createmedia.

function convertimage ($site, $file_source, $location_dest, $format="jpg", $colorspace="RGB", $iccprofile="", $width="", $height="", $slug=0, $units="px", $dpi=72, $quality="")
{
  global $mgmt_config, $mgmt_imagepreview, $mgmt_mediapreview, $mgmt_mediaoptions, $mgmt_imageoptions, $mgmt_maxsizepreview, $mgmt_mediametadata, $hcms_ext;

  if (valid_publicationname ($site) && valid_locationname ($file_source) && valid_locationname ($location_dest))
  {
    // include ICC profile list
    include ($mgmt_config['abs_path_cms']."library/ICC_Profiles/ICC_Profiles.php");
    
    // load file extensions
    if (empty ($hcms_ext) || !is_array ($hcms_ext)) require ($mgmt_config['abs_path_cms']."include/format_ext.inc.php");
    
    // if no absolute path has been provided, try to get absolute path of the source media file
    if (!is_file ($file_source) && strpos ($file_source, "_hcm") > 0)
    {
      $file_source = getmedialocation ($site, $file_source, "abs_path_media").$site."/".getobject ($file_source);
    } 
    
    // get location and file name
    $file = getobject ($file_source);
    $location_source = getlocation ($file_source);
    
    // prepare media file
    $temp_source = preparemediafile ($site, $location_source, $file, $user);
    
    if ($temp_source['result'] && $temp_source['crypted'])
    {
      $location_source = $temp_source['templocation'];
      $file = $temp_source['tempfile'];
      $file_source = $location_source.$file;
    }
    elseif ($temp_source['restored'])
    {
      $location_source = $temp_source['location'];
      $file = $temp_source['file'];
      $file_source = $location_source.$file;
    }

    // add slash if not present at the end of the location string
    if (substr ($location_dest, -1) != "/") $location_dest = $location_dest."/";  
    
    // get file info
    $file_info = getfileinfo ($site, $file_source, "comp");
    
    // validate DPI value
    if ($dpi < 72) $dpi = 72;
    elseif ($dpi > 1200) $dpi = 1200;
    
    $density_para = " -d ".$dpi;
    
    // validate quality value
    if ($quality >= 1 && $quality <= 100)
    {
      $quality_para = " -q ".$quality;
    }
    else $quality_para = "";

    // new image dimensions
    if ($width > 0 && $height > 0)
    {
      // convert width and height to px
      if (strtolower ($units) == "mm")
      {
        $width = mm2px ($width, $dpi);
        $height = mm2px ($height, $dpi);
      }
      elseif (strtolower ($units) == "inch")
      {
        $width = inch2px ($width, $dpi);
        $height = inch2px ($height, $dpi);
      }
        
      $size = $width."x".$height;
              
      // get image size in px
      $imagesize = getimagesize ($file_source);
      
      // if image size is available
      if (!empty ($imagesize[0]) && !empty ($imagesize[1]))
      {
        // image ratios
        $imageratio = $imagesize[0] / $imagesize[1];
        $ratio = $width / $height;

        // if image has larger ratio we define new height
        if ($imageratio > $ratio)
        {
          $height_render = $height + $slug;
          $width_render = round (($height_render * $imageratio), 0);
        }
        elseif ($imageratio < $ratio)
        {
          $width_render = $width + $slug;
          $height_render = round (($width_render / $imageratio), 0);
        }
        else
        {
          $width_render = $width + $slug;
          $height_render = $height + $slug;
        }
        
        // define crop x and y coordinates
        $x = intval ($width_render / 2 - $width / 2);
        $y = intval ($height_render / 2 - $height / 2);
        if ($x < 0) $x = 0;
        if ($y < 0) $y = 0;
        
        // resize image
        $size_para = " -s ".$width_render."x".$height_render;
        
        // crop image
        if ($x > 0 || $y > 0)
        {
          $crop_para = " -c ".$x."x".$y." -s ".$width."x".$height;
        }
      }
      // image size is not available
      else
      {
        $width_render = $width + $slug;
        $height_render = $height + $slug;
          
        $size_para = " -s ".$width_render."x".$height_render;
      }
    }
    else
    {
      $size = "orig";
      $size_para = "";
    }
    
    // iccprofile or color space
    if ($iccprofile != "")
    {
      $color_para = " -p \"".$mgmt_config['abs_path_cms']."library/ICC_Profiles/".$ICC_Profiles[$iccprofile]."\"";
      $color = $iccprofile;
    }
    elseif ($colorspace != "")
    {
      $color_para = " -cs ".$colorspace;
      $color = $colorspace;
    }
    else
    {
      $color_para = "";
      $color = "orig";
    }
    
    // format
    if ($format != "")
    {
      $format_para = " -f ".$format;
    }
    else
    {
      // get the file extension
      $format = substr ($file_info['ext'], 1);
      $format_para = " -f ".$format;
    }
    
    // define type
    $type = $size."-".$color."-".$dpi."dpi";
    
    // new file name
    $file_name = $file_info['filename'].".".$type.".".$format;
    
    // check if image exists and is newer than the original image
    if (!is_file ($location_dest.$file_name) || @filemtime ($file_source) > @filemtime ($location_dest.$file_name))
    {
      // define image option
      $mgmt_imageoptions = array();
      $mgmt_imageoptions['.'.$format][$type] = $size_para.$color_para.$format_para.$density_para.$quality_para;

      // render image w/o crop since one step crop and resize are not supported by hyperCMS
      $file_name_new = createmedia ($site, $location_source, $location_dest, $file, $format, $type);

      // define image option for crop
      if (!empty ($crop_para) && $file_name_new != false)
      {
        // rename intermediate file to original file name with new extension
        rename ($location_dest.$file_name_new, $location_dest.$file_info['filename'].".".$format);
        
        $mgmt_imageoptions = array();
        $mgmt_imageoptions['.'.$format][$type] = $crop_para;

        // render image
        $file_name_new = createmedia ($site, $location_dest, $location_dest, $file_info['filename'].".".$format, $format, $type);

        // delete intermediate file
        if (is_file ($location_dest.$file_info['filename'].".".$format)) unlink ($location_dest.$file_info['filename'].".".$format);
      }

      return $file_name_new;
    }
    else return $file_name;
  }
  else return false;
}

// ---------------------- rotateimage -----------------------------
// function: rotateimage()
// input: publication, path to source media file, rotation angle, destination image format [jpg,png,gif]
// output: new image file name / false on error

// description:
// Rotates an image (must be jpg, png or gif) using GD library. not used if ImageMagick is available

function rotateimage ($site, $filepath, $angle, $imageformat)
{
  global $mgmt_config;
  
  if (valid_publicationname ($site) && valid_locationname ($filepath) && $angle <= 360 && ($imageformat == "jpg" || $imageformat == "png" || $imageformat == "gif"))
  {
    $file_info = getfileinfo ($site, $filepath, "comp");
    $location = getlocation ($filepath);
    $file = getobject ($filepath);
    
    // prepare media file
    $temp_source = preparemediafile ($site, $location, $file, $user);
    
    if ($temp_source['result'] && $temp_source['crypted'])
    {
      $filepath = $temp_source['templocation'].$temp_source['tempfile'];
    }
    elseif ($temp_source['restored'])
    {
      $filepath = $temp_source['location'].$temp_source['file'];
    }
     
    if (is_file ($filepath))
    {
      // create image from file
      if ($file_info['ext'] == ".jpg") $image = imagecreatefromjpeg ($filepath);
      elseif ($file_info['ext'] == ".png") $image = imagecreatefrompng ($filepath);
      elseif ($file_info['ext'] == ".gif") $image = imagecreatefromgif ($filepath);

      // if image resource 
      if ($image != false)
      {
        if ($angle == 270) $angle = -90;
    
        $src_x = imagesx ($image);
        $src_y = imagesy ($image);
        
        if ($angle == 90 || $angle == -90)
        {
          $dest_x = $src_y;
          $dest_y = $src_x;
        }
        else
        {
          $dest_x = $src_x;
          $dest_y = $src_y;
        }
    
        // create new image
        $rotate = imagecreatetruecolor ($dest_x, $dest_y);
        imagealphablending ($rotate, false);
    
        switch ($angle)
        {
          case 90:
            for ($y = 0; $y < ($src_y); $y++)
            {
              for ($x = 0; $x < ($src_x); $x++)
              {
                $color = imagecolorat ($image, $x, $y);
                imagesetpixel ($rotate, $dest_x - $y - 1, $x, $color);
              }
            }
            break;
            
          case -90:
            for ($y = 0; $y < ($src_y); $y++)
            {
              for ($x = 0; $x < ($src_x); $x++)
              {
                $color = imagecolorat ($image, $x, $y);
                imagesetpixel ($rotate, $y, $dest_y - $x - 1, $color);
              }
            }
            break;
            
          case 180:
            for ($y = 0; $y < ($src_y); $y++)
            {
              for ($x = 0; $x < ($src_x); $x++)
              { 
                $color = imagecolorat ($image, $x, $y); 
                imagesetpixel ($rotate, $dest_x - $x - 1, $dest_y - $y - 1, $color);
              }
            }
            break;
            
          default: $rotate = $image;
        };
        
        // save image
        if ($imageformat == "jpg" && function_exists ("imagejpeg"))
        {
          $result = @imagejpeg ($rotate, $location.$file_info['filename'].".".$imageformat);
        }
        elseif ($imageformat == "png" && function_exists ("imagepng"))
        {
          $result = @imagepng ($rotate, $location.$file_info['filename'].".".$imageformat);
        }
        elseif ($imageformat == "gif" && function_exists ("imagegif"))
        {
          $result = @imagegif ($rotate, $location.$file_info['filename'].".".$imageformat);
        }
        else $result = false;
        
        // delete original file if file extension has changed
        if ($result == true && ".".$imageformat != $file_info['ext']) @unlink ($filepath);

        // return result
        if ($result == true) return $file_info['filename'].".".$imageformat;
        else false;
      }
      // image resource error
      else return false;
    }
    // file does not exist
    else return false;
  }
  else return false;
}

// ---------------------- getimagecolors -----------------------------
// function: getimagecolors()
// input: publication, media file name
// output: result array / false on error

// description:
// Uses the thumbnail image to calculate the mean color (red, green, blue), defines the colorkey (5 most commonly used colors) and the image type (landscape, portrait, square)

function getimagecolors ($site, $file)
{
  global $mgmt_config, $user;
  
  if ($mgmt_config['db_connect_rdbms'] != "" && valid_publicationname ($site) && valid_objectname ($file))
  {  
    $media_root = getmedialocation ($site, $file, "abs_path_media").$site."/";
    $file_info = getfileinfo ($site, $file, "comp");
    $file = $file_info['file'];
    
    // try thumbnail image first
    $thumbnail = $file_info['filename'].".thumb.jpg";
    
    // prepare media file
    $temp_source = preparemediafile ($site, $media_root, $thumbnail, $user);
    
    if ($temp_source['result'] && $temp_source['crypted'])
    {
      $media_root = $temp_source['templocation'];
      $thumb = $temp_source['tempfile'];
    }
    elseif ($temp_source['restored'])
    {
      $media_root = $temp_source['location'];
      $thumb = $temp_source['file'];
    }
    
    // use thumbnail image file
    if (is_file ($media_root.$thumbnail))
    {
      $image = imagecreatefromjpeg ($media_root.$thumbnail);
    }
    // try original image
    else
    {
      // prepare media file
      $temp_source = preparemediafile ($site, $media_root, $file, $user);
      
      if ($temp_source['result'] && $temp_source['crypted'])
      {
        $media_root = $temp_source['templocation'];
        $file = $temp_source['tempfile'];
      }
      elseif ($temp_source['restored'])
      {
        $media_root = $temp_source['location'];
        $file = $temp_source['file'];
      }
      
      // verify local media file
      if (!is_file ($media_root.$file)) return false;
    
      if ($file_info['ext'] == ".jpg") $image = imagecreatefromjpeg ($media_root.$file);
      elseif ($file_info['ext'] == ".png") $image = imagecreatefrompng ($media_root.$file);
      elseif ($file_info['ext'] == ".gif") $image = imagecreatefromgif ($media_root.$file);
      else $image = false;
      
      // delete temp file
      if ($temp_source['result'] && $temp_source['created']) deletefile ($temp_source['templocation'], $temp_source['tempfile'], 0);
    }

    if (is_resource ($image))
    {
      $width = imagesx ($image);
      $height = imagesy ($image);
      $totalred = 0;
      $totalgreen = 0;
      $totalblue = 0;
      $total = 0;
      
      for ($y=0; $y<20; $y++)
      {
        for ($x=0; $x<20; $x++)
        {
          $rgb = imagecolorat ($image, $x*($width/20), $y*($height/20));
          $red = ($rgb >> 16) & 0xFF;
          $green = ($rgb >> 8) & 0xFF;
          $blue = $rgb & 0xFF;
    
          // calculate deltas (remove brightness factor)
          $cmax = max ($red, $green, $blue);
          $cmin = min ($red, $green, $blue);
          // avoid division errors
          if ($cmax == $cmin)
          {
            $cmax = 10;
            $cmin = 0;
          } 
          
          // ignore gray, white and black
          if (abs ($cmax - $cmin) >= 20) 
          {
            $red = floor ((($red - $cmin) /($cmax - $cmin)) * 255);
            $green = floor ((($green - $cmin) / ($cmax - $cmin)) * 255);
            $blue = floor ((($blue - $cmin) / ($cmax - $cmin)) * 255);
    
            $total++;
            $totalred += $red;
            $totalgreen += $green;
            $totalblue += $blue;
          }
        }
      }
      
      if ($total == 0) $total = 1;
      $totalred = floor ($totalred / $total);
      $totalgreen = floor ($totalgreen / $total);
      $totalblue = floor ($totalblue / $total);
      
      $colorkey = getimagecolorkey ($image);
    
      // set 'portrait', 'landscape' or 'square' for the image type
      if ($width > $height) $imagetype = "landscape";
      elseif ($height > $width) $imagetype = "portrait";
      elseif ($height == $width) $imagetype = "square";
      
      // destroy image resource
      if (is_resource ($image)) imagedestroy ($image);
      
      $result = array();
      $result['red'] = $totalred;
      $result['green'] = $totalgreen;
      $result['blue'] = $totalblue;
      $result['colorkey'] = $colorkey;
      $result['imagetype'] = $imagetype;
      
      return $result;
    }
    else return false;
  }
  else return false;
}

// ---------------------- getimagecolorkey -----------------------------
// function: getimagecolorkey()
// input: image resource
// output: color key of image / false on error

// description:
// Extracts the color key for an image that represents the 5 mostly used colors:
// K...black
// W...white
// E...grey
// R...red
// G...green
// B...blue
// C...cyan
// M...magenta
// Y...yellow
// O...orange
// P...pink
// N...brown

function getimagecolorkey ($image)
{
  global $mgmt_config;
  
  if ($image)
  {
    $width = imagesx ($image);
    $height = imagesy ($image);
    
    $colors = array (
    "K"=>array(0,0,0), 			// Black
    "W"=>array(255,255,255),	// White
    "E"=>array(200,200,200),	// Grey
    "E"=>array(140,140,140),	// Grey
    "E"=>array(100,100,100),	// Grey
    "R"=>array(255,0,0),		// Red
    "R"=>array(128,0,0),		// Dark Red
    "R"=>array(180,0,40),		// Dark Red
    "G"=>array(0,255,0),		// Green
    "G"=>array(0,128,0),		// Dark Green
    "G"=>array(80,120,90),		// Faded Green
    "G"=>array(140,170,90),		// Pale Green
    "B"=>array(0,0,255),		// Blue
    "B"=>array(0,0,128),		// Dark Blue
    "B"=>array(90,90,120),		// Dark Blue
    "B"=>array(60,60,90),		// Dark Blue
    "B"=>array(90,140,180),		// Light Blue
    "C"=>array(0,255,255),		// Cyan
    "C"=>array(0,200,200),		// Cyan
    "M"=>array(255,0,255),		// Magenta
    "Y"=>array(255,255,0),		// Yellow
    "Y"=>array(180,160,40),		// Yellow
    "Y"=>array(210,190,60),		// Yellow
    "O"=>array(255,128,0),		// Orange
    "O"=>array(200,100,60),		// Orange
    "P"=>array(255,128,128),	// Pink
    "P"=>array(200,180,170),	// Pink
    "P"=>array(200,160,130),	// Pink
    "P"=>array(190,120,110),	// Pink
    "N"=>array(110,70,50),		// Brown
    "N"=>array(180,160,130),	// Pale Brown
    "N"=>array(170,140,110),	// Pale Brown
    );
    
    $table = array();
    $depth = 50;
    
    for ($y=0; $y<$depth; $y++)
    {
      for ($x=0; $x<$depth; $x++)
      {
        $rgb = imagecolorat ($image, $x*($width/$depth), $y*($height/$depth));
        $red = ($rgb >> 16) & 0xFF;
        $green = ($rgb >> 8) & 0xFF;
        $blue = $rgb & 0xFF;
        // which color
        $bestdist = 99999;
        $bestkey = "";
        
        reset ($colors);
        
        foreach ($colors as $key=>$value)
        {
          $distance = sqrt (pow (abs ($red - $value[0]), 2) + pow (abs ($green - $value[1]), 2) + pow (abs ($blue - $value[2]), 2));
          
          if ($distance < $bestdist)
          {
            $bestdist = $distance;
            $bestkey = $key;
          }
        }
        
        // add this color to the color table
        if (array_key_exists ($bestkey, $table)) $table[$bestkey]++;
        else $table[$bestkey] = 1;
      }
    }
    
    asort ($table);
    reset ($table);
    $colorkey = "";
    foreach ($table as $key=>$value) $colorkey .= $key;
    
    // color key with the 5 mostyl used colors in the image
    $colorkey = substr (strrev ($colorkey), 0, 5);
    
    return $colorkey;
  }
  else return false;
}

// ---------------------- hex2rgb -----------------------------
// function: hex2rgb()
// input: image color as hex-code
// output: RGB-color as array / false on error

function hex2rgb ($hex)
{
  if ($hex != "")
  {
    $hex = ereg_replace ("#", "", $hex);
    $color = array();
     
    if (strlen($hex) == 3)
    {
      $color['red'] = hexdec (substr ($hex, 0, 1) . $r);
      $color['green'] = hexdec (substr ($hex, 1, 1) . $g);
      $color['blue'] = hexdec (substr ($hex, 2, 1) . $b);
    }
    elseif(strlen($hex) == 6)
    {
      $color['red'] = hexdec(substr($hex, 0, 2));
      $color['green'] = hexdec(substr($hex, 2, 2));
      $color['blue'] = hexdec(substr($hex, 4, 2));
    }
     
    return $color;
  }
  else return false;
}

// ---------------------- rgb2hex -----------------------------
// function: rgb2hex()
// input: image color in RGB
// output: hex-color as string / false on error

function rgb2hex ($red, $green, $blue)
{
  if ($red >= 0 && $green >= 0 && $blue >= 0)
  {
    $hex = "#";
    $hex.= str_pad (dechex ($red), 2, "0", STR_PAD_LEFT);
    $hex.= str_pad (dechex ($green), 2, "0", STR_PAD_LEFT);
    $hex.= str_pad (dechex ($blue), 2, "0", STR_PAD_LEFT);
     
    return $hex;
  }
  else return false;
}

// ====================================== VIDEO PLAYER =========================================

// ------------------------- readmediaplayer_config -----------------------------
// function: readmediaplayer_config()
// input: path to media config file, config file name 
// output: config array / false on error

function readmediaplayer_config ($location, $configfile)
{ 
  global $mgmt_config, $user;
  
  if (valid_locationname ($location) && valid_objectname ($configfile))
  {
    // add slash if not present at the end of the location string
    if (substr ($location, -1) != "/") $location = $location."/";
  
    // get publication
    $site = getpublication ($location.$configfile);
    
    // prepare source media file
    $temp = preparemediafile ($site, $location, $configfile, $user);
    
    if ($temp['result'] && $temp['crypted'])
    {
      $location = $temp['templocation'];
      $configfile = $temp['tempfile'];
    }
    elseif ($temp['restored'])
    {
      $location = $temp['location'];
      $configfile = $temp['file'];
    }
    
    // verify file
    if (!is_file ($location.$configfile)) return false;
  
    // load config
    $configstring = loadfile ($location, $configfile);
    
    // Check which configuration is used
    $config = array();
    $media_array = array();
    $update = false;
    
    $test = explode ("\n", $configstring);
    
    // V2.0+ video player parameters in config
    if (substr ($test[0], 0, 1) == "V" && intval (substr ($test[0], 1, 1)) >= 2)
    {
      // new since version 5.5.13
      foreach ($test as $key => $value)
      {
        // config version
        if ($key == 0)
        {
          $config['version'] = substr ($value, 1);
        }
        // width
        elseif ($key == 1)
        {
          // V2.2
          if (strpos ("_".$value, "width=") > 0) list ($name, $config['width']) = explode ("width=", $value);
          // V2.1
          else $config['width'] = $value;
        }
        // height
        elseif ($key == 2)
        {
          // V2.2
          if (strpos ("_".$value, "height=") > 0) list ($name, $config['height']) = explode ("height=", $value);
          // V2.1
          else $config['height'] = $value;
        }
        // dimension in width x height
        elseif (strpos ("_".$value, "dimension=") > 0)
        {
          list ($name, $config['dimension']) = explode ("dimension=", $value);
          
          if (!empty ($config['dimension']) && strpos ($config['dimension'], "px") == 0) $config['dimension'] = $config['dimension']." px";
        }
        // ratio in width / height
        elseif (strpos ("_".$value, "ratio=") > 0)
        {
          list ($name, $config['ratio']) = explode ("=", $value);
        }
        // file size in kB
        elseif (strpos ("_".$value, "filesize=") > 0)
        {
          list ($name, $config['filesize']) = explode ("filesize=", $value);
          
          if (!empty ($config['filesize']) && strpos ($config['filesize'], "MB") == 0) $config['filesize'] = $config['filesize']." MB";
        }
        // duration in hh:mm:ss.ms
        elseif (strpos ("_".$value, "duration=") > 0)
        {
          list ($name, $config['duration']) = explode ("duration=", $value);
          
          // cut of milliseconds
          if (strpos ($config['duration'], ".") > 6) $config['duration_no_ms'] = substr ($config['duration'], 0, -3);
          else $config['duration_no_ms'] = $config['duration'];
        }
        // bitrate in kb/s
        elseif (strpos ("_".$value, "videobitrate=") > 0)
        {
          list ($name, $config['videobitrate']) = explode ("videobitrate=", $value);
        }
        // image type
        elseif (strpos ("_".$value, "imagetype=") > 0)
        {
          list ($name, $config['imagetype']) = explode ("imagetype=", $value);
        }
        // audio bitrate in kb/s
        elseif (strpos ("_".$value, "audiobitrate=") > 0)
        {
          list ($name, $config['audiobitrate']) = explode ("audiobitrate=", $value);
        }
        // audio frequenzy in Hz
        elseif (strpos ("_".$value, "audiofrequenzy=") > 0)
        {
          list ($name, $config['audiofrequenzy']) = explode ("audiofrequenzy=", $value);
        }
        // audio frequenzy in Hz
        elseif (strpos ("_".$value, "audiochannels=") > 0)
        {
          list ($name, $config['audiochannels']) = explode ("audiochannels=", $value);
        }
        // video sources (V2.1+: video-file;mime-type)
        elseif (strpos ($value, ";") > 0)
        {
          $media_array[] = $value;
        }
        // video sources (V2.0: video-file in wrapper-URL)
        elseif ($value != "" && strpos ($value, "?media=") > 0)
        {
          $media = getattribute ($value, "media");
          
          if ($media != "")
          {
            $type = ";".getmimetype ($media);
            $media_array[] = $media.$type;
            $update = true;
          }
        }
        // video sources (with missing mime-type)
        elseif ($value != "" && strpos ("_".$value, "/") > 0)
        {
          $type = ";".getmimetype ($value);
          $media_array[] = $value.$type;
          $update = true;
        }
      }

      $config['mediafiles'] = $media_array;
    }
    // V0.0 / V1.0 older versions with video player code in config
    elseif (substr_count ($configstring, '<') > 0)
    {
      // V1.0 projekktor video player code in config
      if (substr_count ($configstring, '<span id="hcms_div_projekktor_') > 0) $config['version'] = '1.0';
      // old video player code in config
      else $config['version'] = '0.0';
      
      $config['width'] = getattribute ($configstring, "width");
      $config['height'] = getattribute ($configstring, "height"); 
      $config['data'] = $configstring;
      $media_array = array();

      if ($config['data'] != "")
      {
        $offset = 0;
        
        while (strpos ($config['data'], "?media=", $offset) > 0)
        {
          $start = strpos ($config['data'], "?media=", $offset);
          $stop = strpos ($config['data'], "\"", $start);
          $length = $stop - $start;
          $offset = $stop;
          
          if ($length > 0)
          {
            $uri = ".php".substr ($config['data'], $start, $length);                  
            $media = getattribute ($uri, "media");
            
            if ($media != "") $type = ";".getmimetype ($media);
            else $type = "";
            
            $media_array[] = $media.$type;
          }
        }
        
        $config['mediafiles'] = $media_array;
        $update = true;
      }
    }
    
    // update video config file
    if ($update && sizeof ($media_array) > 0)
    {
      list ($videofile, $type) = explode (";", $media_array[0]);
      
      if (!is_file ($videofile))
      {
        $site = substr ($videofile, 0, strpos ($videofile, "/"));
        $videofile = getmedialocation ($site, $videofile, "abs_path_media").$videofile;
      }
      
      // get video info
      $videoinfo = getvideoinfo ($videofile);
        
      savemediaplayer_config ($location, $configfile, $media_array, $config['width'], $config['height'], $videoinfo['filesize'], $videoinfo['duration'], $videoinfo['videobitrate'], $videoinfo['audiobitrate'], $videoinfo['audiofrequenzy'], $videoinfo['audiochannels']); 
    }

    // return video config
    return $config;
  }
  else return false;
}

// ------------------------- savemediaplayer_config -----------------------------
// function: savemediaplayer_config()
// input: path to media config file, media config file name, media file name array or string, width in px (optional), height in px (optional), file size in kB (optional), 
//        duration in hh:mmm:ss (optional), video bitrate in kb/s (optional), audio bitrate in kb/s (optional), audio frequenzy in Hz (optional), audio channels [mono, stereo] (optional)
// output: true / false on error

function savemediaplayer_config ($location, $configfile, $mediafiles, $width=320, $height=240, $filesize="", $duration="", $videobitrate="", $audiobitrate="", $audiofrequenzy="", $audiochannels="")
{
  global $mgmt_config, $user;

  if (valid_locationname ($location) && valid_objectname ($configfile) && (is_array ($mediafiles) || $mediafiles != ""))
  {
    // add slash if not present at the end of the location string
    if (substr ($location, -1) != "/") $location = $location."/";
    
    // get publication
    $site = getpublication ($location.$configfile);
  
  	// set 'portrait', 'landscape' or 'square' for the image type
  	if ($width > $height) $imagetype = "landscape";
  	elseif ($height > $width) $imagetype = "portrait";
  	elseif ($height == $width) $imagetype = "square";
            
    $config = array();
    $config[0] = "V2.3";
    $config[1] = "width=".$width;
    $config[2] = "height=".$height;
    if ($width > 0 && $height > 0) $config[3] = "dimension=".$width."x".$height." px";
    else $config[3] = "dimension=";
    if ($height > 0) $config[4] = "ratio=".round (($width / $height), 5);
    else $result['ratio'] = "ratio=";   
    $config[5] = "filesize=".$filesize;
    $config[6] = "duration=".$duration;
    $config[7] = "videobitrate=".$videobitrate;
    $config[8] = "imagetype=".$imagetype;
    $config[9] = "audiobitrate=".$audiobitrate;
    $config[10] = "audiofrequenzy=".$audiofrequenzy;
    $config[11] = "audiochannels=".$audiochannels;
    
    // array
    if (is_array ($mediafiles)) 
    {
      $i = 12;
      
      foreach ($mediafiles as $media)
      {
        if ($media != "")
        {
          // if mime-type is not supplied (standard case) 
          if (strpos ($media, ";") < 1)
          {
            $mimetype = ";".getmimetype ($media);
            $config[$i] = $media.$mimetype;
          }
          // dont add mime-type
          else $config[$i] = $media;

          $i++;
        }
      }
    }
    // string
    else
    {
      // if mime-type is not supplied (standard case) 
      if (strpos ($mediafiles, ";") < 1)
      {
        $mimetype = ";".getmimetype ($mediafiles);
        $config[12] = $mediafiles.$mimetype;
      }
      // dont add mime-type
      else $config[12] = $mediafiles;
    }
    
    // save config
    $result = savefile ($location, $configfile, implode ("\n", $config));
    
    // save in cloud storage
    if ($result && function_exists ("savecloudobject")) savecloudobject ($site, $location, $configfile, $user);

    return $result;
  }
  else return false;  
}

// ========================================== DOCUMENT CREATION =======================================

// ---------------------- createdocument -----------------------------
// function: createdocument()
// input: publication, path to source location, path to destination location, file name, destination file format (extension w/o dot),
//        force the file to be not encrypted even if the content of the publication must be encrypted [true,false] (optional)
// output: new file name / false on error

// description:
// Creates a new multimedia file of given format at source destination using UNOCONV and saves it as thumbnail file at the desitnation location

function createdocument ($site, $location_source, $location_dest, $file, $format="", $force_no_encrypt=false)
{
  global $mgmt_config, $mgmt_docpreview, $mgmt_docoptions, $mgmt_docconvert, $mgmt_maxsizepreview, $hcms_ext, $hcms_lang, $lang, $user;
  
  if (valid_publicationname ($site) && valid_locationname ($location_source) && valid_locationname ($location_dest) && valid_objectname ($file))
  {
    $converted = false;
    
    set_time_limit (60);
    
    // add slash if not present at the end of the location string
    if (substr ($location_source, -1) != "/") $location_source = $location_source."/";
    if (substr ($location_dest, -1) != "/") $location_dest = $location_dest."/";
         
    // for inital conversion request the temp directory will be defined as destination directory by default of the service mediadownload or mediawrapper
    // the converted files however should be placed in the media repository to avoid the recreation of the file over and over again
    if (trim ($location_dest) == trim ($mgmt_config['abs_path_temp']) && strpos ($file, "_hcm") > 0)
    {
      $location_dest = getmedialocation ($site, $file, "abs_path_media").$site."/";
    }
    
    // get file name without extension
    $file_name_orig = strrev (substr (strstr (strrev ($file), "."), 1));
    
    // get the file extension
    $file_ext = strtolower (strrchr ($file, "."));

    // define format if not set
    if ($format == "") $format = "pdf";
    else $format = strtolower ($format);
    
    // if media conversion software is given, conversion supported and destination format is not the source format
    if (is_array ($mgmt_docpreview) && sizeof ($mgmt_docpreview) > 0 && !empty ($mgmt_docconvert[$file_ext]) && is_array ($mgmt_docconvert[$file_ext]) && $format != trim ($file_ext, ".") && in_array (".".$format, $mgmt_docconvert[$file_ext]))
    {
      // prepare source media file
      $temp_source = preparemediafile ($site, $location_source, $file, $user);
      
      if ($temp_source['result'] && $temp_source['crypted'])
      {
        $location_source = $temp_source['templocation'];
        $file = $temp_source['tempfile'];
      }
      elseif ($temp_source['restored'])
      {
        $location_source = $temp_source['location'];
        $file = $temp_source['file'];
      }
      
      // verify local media file
      if (!is_file ($location_source.$file)) return false;
      
      // get file size of media file in kB
      $filesize_orig = round (@filesize ($location_source.$file) / 1024, 0);
      
      // check max file size in MB for certain file extensions and skip rendering
      if (is_array ($mgmt_maxsizepreview))
      {
        reset ($mgmt_maxsizepreview);   
        
        // defined extension for maximum file size restriction in MB
        foreach ($mgmt_maxsizepreview as $maxsizepreview_ext => $maxsizepreview)
        {        
          if ($file_ext != "" && substr_count (strtolower ($maxsizepreview_ext).".", $file_ext.".") > 0)
          {
            if ($mgmt_maxsizepreview[$maxsizepreview_ext] > 0 && ($filesize_orig / 1024) > $mgmt_maxsizepreview[$maxsizepreview_ext]) return false;
          }
        }
      }
      
      // get file name without extension
      $file_name = strrev (substr (strstr (strrev ($file), "."), 1));
    
      // convert the media file with UNOCONV
      // unoconv is a command line utility that can convert any file format that OpenOffice can import, to any file format that OpenOffice is capable of exporting.
      // -d, --doctype ... Specify the OpenOffice document type of the backend format. Possible document types are: document, graphics, presentation, spreadsheet. Default document type is 'document'.
      // -e, --export ... Set specific export filter options (related to the used OpenOffice filter). eg. for the PDF output filter one can specify: -e PageRange=1-2
      // -f, --format ... Specify the output format for the document. You can get a list of possible output formats per document type by using the --show option. Default document type is 'pdf'.
      // -i, --import ... Set specific import filters options (related to the used OpenOffice filter). eg. for some input filters one can specify: -i utf8
      
      reset ($mgmt_docpreview);
      
      // supported extensions for document rendering
      foreach ($mgmt_docpreview as $docpreview_ext => $docpreview)
      { 
        // check file extension
        if ($file_ext != "" && substr_count (strtolower ($docpreview_ext).".", $file_ext.".") > 0)
        {
          reset ($mgmt_docoptions);  
          
          // extensions for certain document rendering options
          foreach ($mgmt_docoptions as $docoptions_ext => $docoptions)
          { 
            // get media rendering options based on given destination format
            if (substr_count (strtolower ($docoptions_ext).".", ".".$format.".") > 0)
            {
              // document format (document file extension) definition
              $docformat = strtolower (getoption ($mgmt_docoptions[$docoptions_ext], "-f"));
              
              if ($docformat == "" || $docformat == false) $docformat = "pdf";   
              
              // create new file
              $newfile = $file_name_orig.".thumb.".$docformat;  
              
              // if thumbnail file exists in destination (temp) folder
              if (is_file ($location_dest.$newfile))
              {
                // delete existing destination file if it is older than the source file
                if (@filemtime ($location_dest.$newfile) < filemtime ($location_source.$file)) 
                {
                  unlink ($location_dest.$newfile);
                }
                // or we return the filename
                else $converted = true;
              }

              // if thumbnail file exits in source folder 
              if (is_file ($location_source.$newfile))
              {
                // if existing thumbnail file is newer than the source file
                if (filemtime ($location_source.$newfile) >= filemtime ($location_source.$file)) 
                {
                  // copy to destination directory
                  $result_rename = copy ($location_source.$newfile, $location_dest.$newfile);
                  
                  if ($result_rename == true)
                  {
                    $converted = true;
                  }
                }
              }
              
              $cmd = $mgmt_docpreview[$docpreview_ext]." ".$mgmt_docoptions[$docoptions_ext]." \"".shellcmd_encode ($location_source.$file)."\"";
                         
              @exec ($cmd." 2>&1", $error_array, $errorCode);

              // error if conersion failed
              if ($errorCode || !is_file ($location_source.$file_name.".".$docformat))
              {
                $errcode = "20236";
                $error[] = $mgmt_config['today']."|hypercms_media.inc.php|error|$errcode|exec of unoconv (code:$errorCode) to '$format' failed in createdocument for file: ".$location_source.$file." with message: ".implode("<br />", $error_array);
                
                // save log
                savelog (@$error);          
              } 
              else
              {  
                // rename/move converted file to destination
                $result_rename = @rename ($location_source.$file_name.".".$docformat, $location_dest.$newfile);

                if ($result_rename == false)
                {
                  $errcode = "20337";
                  $error[] = $mgmt_config['today']."|hypercms_media.inc.php|error|$errcode|rename failed in createdocument for file: ".$location_source.$file_name.".".$docformat;
                  
                  // save log
                  savelog (@$error);            
                }
                else 
                {
                  $converted = true;
                  
                  // copy metadata from original file using EXIFTOOL
                  $result_copy = copymetadata ($location_source.$file, $location_dest.$newfile);

                  // remote client
                  remoteclient ("save", "abs_path_media", $site, $location_dest, "", $newfile, "");

                  // encrypt and save data
                  if (is_file ($mgmt_config['abs_path_cms']."encryption/hypercms_encryption.inc.php") && $force_no_encrypt == false && !empty ($result_rename) && isset ($mgmt_config[$site]['crypt_content']) && $mgmt_config[$site]['crypt_content'] == true)
                  {
                    $data = encryptfile ($location_dest, $newfile);
                    if (!empty ($data)) savefile ($location_dest, $newfile, $data);
                  }

                  // save in cloud storage
                  if (is_file ($location_dest.$newfile) && function_exists ("savecloudobject")) savecloudobject ($site, $location_dest, $newfile, $user);
                }
              }
            }
          }
        }
      }
      
      // delete temp file
      if ($temp_source['result'] && $temp_source['created']) deletefile ($temp_source['templocation'], $temp_source['tempfile'], 0);
    }

    // on success
    if ($converted == true && !empty ($newfile)) return $newfile;
    // no option was found for given format or no media conversion software defined
    else return false;    
  }
  else return false;
}

// ====================================== COMPRESSED FILE HANDLING ===================================

// ---------------------- unzipfile -----------------------------
// function: unzipfile()
// input: publication, path to source zip file, path to destination location, category [page,comp], name of file for extraction, user
// output: result array with all object paths / false

// description:
// Unpacks ZIP file and creates media files in destination location for components or unzips files directly for pages.

function unzipfile ($site, $zipfilepath, $location, $filename, $cat="comp", $user)
{
  global $mgmt_config, $mgmt_uncompress, $mgmt_imagepreview, $mgmt_mediapreview, $mgmt_mediaoptions;

  if ($mgmt_uncompress['.zip'] != "" && valid_publicationname ($site) && $zipfilepath != "" && valid_locationname ($location) && valid_objectname ($filename) && ($cat == "page" || $cat == "comp") && valid_objectname ($user))
  {
      // add slash if not present at the end of the location string
    if (substr ($location, -1) != "/") $location = $location."/";
    
    // folder name for extraction
    $folder = substr ($filename, 0, strrpos ($filename, "."));
    
    // test if folder name includes special characters
    if (specialchr ($folder, ".-_") == true)
    {
      $folder = specialchr_encode ($folder, "no");
    }
      
    // extension of zip file
    $file_ext = strtolower (strrchr ($filename, "."));      

    // directory with name of the folder must not exist
    if (!is_dir ($location.$folder."/"))
    {
      // temporary directory for extracting files
      $location_temp = $mgmt_config['abs_path_temp'];
      $unzipname_temp = uniqid ("unzip");
      $unzippath_temp = $location_temp.$unzipname_temp."/";
      
      $location_zip = getlocation ($zipfilepath);
      $file_zip = getobject ($zipfilepath);
      
      // prepare media file
      $temp = preparemediafile ($site, $location_zip, $file_zip, $user);
      
      if ($temp['result'] && $temp['crypted'])
      {
        $location_zip = $temp['templocation'];
        $file_zip = $temp['tempfile'];
        $zipfilepath = $location_zip.$file_zip;
      }
      elseif ($temp['restored'])
      {
        $location_zip = $temp['location'];
        $file_zip = $temp['file'];
        $zipfilepath = $location_zip.$file_zip;
      }

      reset ($mgmt_uncompress);
      
      for ($i = 1; $i <= sizeof ($mgmt_uncompress); $i++)
      {
        // supported extension
        $extension = key ($mgmt_uncompress);
        
        if (substr_count ($extension.".", $file_ext.".") > 0)
        {
          if ($cat == "page")
          {
            // extract files directly to page location
            $cmd = $mgmt_uncompress[$extension]." \"".shellcmd_encode ($zipfilepath)."\" -d \"".shellcmd_encode ($location)."\"";
            
            @exec ($cmd, $error_array);

            if (is_array ($error_array) && substr_count (implode ("<br />", $error_array), "error") > 0)
            {
              $error_message = implode ("<br />", $error_array);

              $errcode = "10639";
              $error[] = $mgmt_config['today']."|hypercms_media.inc.php|error|$errcode|unzipfile failed for $filename: $error_message";
              
              // save log
              savelog (@$error);                       
            }
          
            // collect extracted files
            $object_array = collectobjects (1, $site, $cat, $location);

            if (is_array ($object_array) && sizeof ($object_array) > 0)
            {
              $result = array();
              
              foreach ($object_array as $object_record)
              {
                list ($root_id, $site_record, $location_record, $object_record) = explode ("|", $object_record);
                $result[] = $location_record.$object_record;
              }
              
              return $result;
            }
            else return false;
          }
          elseif ($cat == "comp")
          {
            // create temporary directory for extraction
            $result = @mkdir ($unzippath_temp, $mgmt_config['fspermission']);
  
            // create destination folder for extraction
            if ($result == true)
            {
              $result = createfolder ($site, $location, $folder, $user);
            
              // remote client
              remoteclient ("save", "abs_path_comp", $site, $location, "", $folder, "");           
            }
  
            if ($result['result'] == true)
            {
              // extract files to temporary location for media assets (components)         
              $cmd = $mgmt_uncompress[$extension]." \"".shellcmd_encode ($zipfilepath)."\" -d \"".shellcmd_encode ($unzippath_temp)."\"";
  
              @exec ($cmd, $error_array);
  
              if (is_array ($error_array) && substr_count (implode ("<br />", $error_array), "error") > 0)
              {
                $error_message = implode ("<br />", $error_array);
                $error_message = str_replace ($unzippath_temp, "/".$site."/", $error_message);
  
                $errcode = "10640";
                $error[] = $mgmt_config['today']."|hypercms_media.inc.php|error|$errcode|unzipfile failed for $filename: $error_message";
                
                // save log
                savelog (@$error);                       
              }
            
              // check if files were extracted
              $handle = opendir ($unzippath_temp);
              
              if ($handle != false)
              {    
                $check = 0;
                while ($buffer = @readdir ($handle)) $check++;
                closedir ($handle);              
                if ($check < 1) return false;
              }
              else return false;            
              
              // create media objects       
              $result = createmediaobjects ($site, $unzippath_temp, $location.$result['folder']."/", $user);
              
              // delete unzipped temporary files in temporary directory
              deletefile ($location_temp, $unzipname_temp, 1);
              
              // delete decrypted temporary file
              if ($temp['result'] && $temp['created']) deletefile ($temp['templocation'], $temp['tempfile'], 1);
              
              return $result;  
            }
          }
        }
        
        next ($mgmt_uncompress);
      }
    }
    else return false;
  }
  else return false;
}    

// ---------------------- clonefolder -----------------------------
// function: clonefolder()
// input: publication name, source location, destination location, user name, activity that need to be set for daily stats [download] (optional)
// output: true/false

// description:
// Help function for function zipfiles that reads all multimedia files from their multimedia objects and copies them to the same folder structure using the object names.

function clonefolder ($site, $source, $destination, $user, $activity="")
{
  global $mgmt_config, $pageaccess, $compaccess, $hiddenfolder, $hcms_linking, $globalpermission, $setlocalpermission;
  
  if (is_array ($mgmt_config) && $source != "" && $destination != "")
  {  
    $destDir = $destination."/".specialchr_decode (getobject ($source));
    @mkdir ($destDir, $mgmt_config['fspermission'], true);
    
    if ($dir = opendir ($source))
    {
      while (($file = readdir ($dir)) !== false)
      {
        // check access permissions
        if (!is_array ($setlocalpermission) && $user != "sys")
        {
          $ownergroup = accesspermission ($site, $source, "comp");
          $setlocalpermission = setlocalpermission ($site, $ownergroup, "comp");
        }

        if (substr_compare ($file, ".", 0, 1) != 0 && ($user == "sys" || ($setlocalpermission['root'] == 1 && $setlocalpermission['download'] == 1)))
        {
          if (is_dir ($source."/".$file))
          {
            clonefolder ($site, $source."/".$file, $destDir, $user, $activity);
          }
          else
          {  
            $objectdata = loadfile ($source."/", $file);
            
            if ($objectdata != false)
            {
              $mediafile = getfilename ($objectdata, "media");
              
              if ($mediafile != false)
              {
                $container_id = getmediacontainerid ($mediafile);
                $mediadir = getmedialocation ($site, $mediafile, "abs_path_media").$site."/";
                
                // decrypt and save file if media file is encypted
                if (is_encryptedfile ($mediadir, $mediafile))
                {
                  $data = decryptfile ($mediadir, $mediafile);
                  
                  if (!empty ($data))
                  {
                    savefile ($destDir."/", specialchr_decode ($file), $data);
                  }
                }
                // copy file to new location
                else copy ($mediadir.$mediafile, $destDir."/".specialchr_decode ($file));
                
                // write stats
                if ($container_id != "" && !is_thumbnail ($mediafile, false)) rdbms_insertdailystat ("download", $container_id, $user);
              }
            }        
          }
        }
      }
      
      closedir ($dir);
      return true;
    }
    else return false;    
  }
  else return false;
}

// ---------------------- zipfiles -----------------------------
// function: zipfiles()
// input: publication, array with path to source zip files, destination location (if this is null then the $location where the zip-file resists will be used), 
//        name of ZIP-file, user name, activity that need to be set for daily stats [download] (optional)
// output: true/false

// description:
// Compresses all media files and includes their folder structure in a ZIP file.

function zipfiles ($site, $multiobject_array, $destination="", $zipfilename, $user, $activity="")
{
  global $mgmt_config, $mgmt_compress, $pageaccess, $compaccess, $hiddenfolder, $hcms_linking, $globalpermission, $setlocalpermission;

  // valid_locationname ($destination) && valid_objectname ($zipfilename) && valid_objectname ($user)
  if (is_array ($mgmt_config) && $mgmt_compress['.zip'] != "" && valid_publicationname ($site) && is_array ($multiobject_array) && sizeof ($multiobject_array) > 0 && is_dir ($destination) && valid_locationname ($destination) && valid_objectname ($zipfilename) && valid_objectname ($user))
  {  
    // check max file size (set default value to 2000 MB)
    if (!isset ($mgmt_config['maxzipsize'])) $mgmt_config['maxzipsize'] = 2000;
    
    if ($mgmt_config['maxzipsize'] > 0 && $mgmt_config['db_connect_rdbms'] != "")
    {
      $filesize = 0;
      
      foreach ($multiobject_array as $multiobject)
      {
        $multiobject = convertpath ($site, $multiobject, "comp");
        // get file size in KB
        $filesize_array = rdbms_getfilesize ("", $multiobject);
        if (is_array ($filesize_array)) $filesize = $filesize + $filesize_array['filesize'];                
        // return false if max file size limit in MB is exceeded
        if (($filesize / 1024) > $mgmt_config['maxzipsize']) return false;
      }
    }  
    
    // check if ZIP file exists and there are no new files that need to be included
    if (is_file ($destination.$zipfilename.".zip"))
    {
      // get ZIP file time
      $zipfiletime = filemtime ($destination.$zipfilename.".zip");
      $zipfiledate = date ("Y-m-d H:i:s", $zipfiletime);
      
      // query for files that are new or have been updated after the ZIP file has been created
      foreach ($multiobject_array as $multiobject)
      {
        $multiobject = convertpath ($site, $multiobject, "comp");
        
        // remove folder object
        if (getobject ($multiobject) == ".folder") $multiobject = getlocation ($multiobject);
        
        // if location path
        if (substr ($multiobject, -1) == "/")
        {
          $updates = rdbms_externalquery ("SELECT object.objectpath FROM object INNER JOIN container ON object.id=container.id WHERE object.objectpath LIKE \"".$multiobject."%\" AND container.date>=\"".$zipfiledate."\"");
        }
        // if object path
        else
        {
          $updates = rdbms_externalquery ("SELECT object.objectpath FROM object INNER JOIN container ON object.id=container.id WHERE object.objectpath=\"".$multiobject."\" AND container.date>=\"".$zipfiledate."\"");
        }
      }
      
      // if no objects have been found
      if (!is_array ($updates) || sizeof ($updates) < 1) return true;
    }

    // temporary directory for file collection
    $tempDir = $mgmt_config['abs_path_temp'];
  
    $commonRoot = getlocation ($multiobject_array[0]);
  
    // find common root folder for different file paths
    if (sizeof ($multiobject_array) > 1)
    {
      for ($i=0; $i<sizeof($multiobject_array); $i++)
      {
        if ($multiobject_array[$i] != "")
        {
          $fileParts = explode ("/", $multiobject_array[$i]);
          $commonRootParts = explode ("/", $commonRoot);
    
          $commonRoot = "";
          $j = 0;
        
          while ($fileParts[$j] == $commonRootParts[$j] && $j < sizeof ($fileParts))
          {
            $commonRoot .= $fileParts[$j]."/";
            $j++;
          }
        }
      }
    }
   
    $commonRoot = deconvertpath ($commonRoot, "file");

    // create unique temp directory to collect the files for compression
    $tempFolderName = uniqid ("zip");
    $tempFolder = $tempDir.$tempFolderName;
    @mkdir ($tempFolder, $mgmt_config['fspermission'], true);
    
    // walk through objects and get the multimedia files reference
    for ($i=0; $i<sizeof($multiobject_array); $i++)
    {
      if ($multiobject_array[$i] != "")
      {    
        $filename = getobject ($multiobject_array[$i]);
        $location = getlocation ($multiobject_array[$i]);
        $location = deconvertpath ($location, "file");
        
        if (valid_locationname ($location) && valid_objectname ($filename))
        {
          $destinationFolder = str_replace ($commonRoot, "", $location);
          @mkdir ($tempFolder."/".$destinationFolder, $mgmt_config['fspermission'], true);
      
          if ($filename != ".folder" && is_file ($location.$filename))
          {
            $objectdata = loadfile ($location, $filename);
        
            if ($objectdata != false)
            {
              $mediafile = getfilename ($objectdata, "media");
              
              if ($mediafile != false)
              {
                $mediadir = getmedialocation ($site, $mediafile, "abs_path_media").$site."/";
                
                // prepare media file
                $temp = preparemediafile ($site, $mediadir, $mediafile, $user);
                
                if ($temp['result'] && $temp['crypted'])
                {
                  $mediadir = $temp['templocation'];
                  $mediafile = $temp['tempfile'];
                }
                elseif ($temp['restored'])
                {
                  $mediadir = $temp['location'];
                  $mediafile = $temp['file'];
                }

                // copy file to new location
                copy ($mediadir.$mediafile, $tempFolder."/".specialchr_decode ($destinationFolder.$filename));
                
                // remove decrypted temporary file
                if ($temp['result'] && $temp['created']) deletefile ($temp['templocation'], $temp['tempfile'], 0);
              }
            }
          }
          elseif ($filename == ".folder" || is_dir ($location.$filename))
          {
            if ($filename == ".folder")
            {
              $filename = "";
              // cut off last /
              $location = substr ($location, 0, -1);
            }
            
            clonefolder ($site, $location.$filename, $tempFolder."/".specialchr_decode ($destinationFolder), $user, $activity);
          }
        }
      }
    }
    
    // ZIP files
    // Windows
    if ($mgmt_config['os_cms'] == "WIN")
    { 
      $cmd = "cd \"".shellcmd_encode ($location)."\" & ".$mgmt_compress['.zip']." -r \"".shellcmd_encode ($destination.$zipfilename).".zip\" ".shellcmd_encode ($filesToZip);  
      $cmd = str_replace ("/", "\\", $cmd);
    }
    // UNIX
    else $cmd = "cd \"".shellcmd_encode ($tempFolder)."\" ; ".$mgmt_compress['.zip']." -r \"".shellcmd_encode ($destination.$zipfilename).".zip\" *";
    
    // compress files to ZIP format
    @exec ($cmd, $error_array);
  
    // remove temp files
    deletefile ($tempDir, $tempFolderName, 1);
    
    // errors during compressions of files
    if (is_array ($error_array) && substr_count (implode ("<br />", $error_array), "error") > 0)
    {
      $error_message = implode ("<br />", $error_array);
      $error_message = str_replace ($mgmt_config['abs_path_temp'], "/", $error_message);
  
      $errcode = "10645";
      $error[] = $mgmt_config['today']."|hypercms_media.inc.php|error|$errcode|zipfiles failed for $filename: $error_message";
      
      // save log
      savelog (@$error);
      
      return false;                     
    }
    else return true;
  }
  return false;
}

// ---------------------- px2mm -----------------------------
// function: px2mm()
// input: pixel, dpi (optional)
// output: mm / false

// description:
// Convert pixel to mm

function px2mm ($pixel, $dpi=72)
{
  if ($pixel > 0 && $dpi > 0)
  {
    return round (($pixel * 25.4 / $dpi), 0);
  }
  else return false;
}

// ---------------------- mm2px -----------------------------
// function: px2mm()
// input: pixel, dpi (optional)
// output: pixel / false

// description:
// Convert mm to pixel

function mm2px ($mm, $dpi=72)
{
  if ($mm > 0 && $dpi > 0)
  {
    return round (($mm * $dpi / 25.4), 0);
  }
  else return false;
}

// ---------------------- px2inch -----------------------------
// function: px2inch()
// input: pixel, dpi (optional)
// output: inch / false

// description:
// Convert pixel to inches

function px2inch ($pixel, $dpi=72)
{
  if ($pixel > 0 && $dpi > 0)
  {
    return round (($pixel / $dpi), 0);
  }
  else return false;
}

// ---------------------- inch2px -----------------------------
// function: inch2px()
// input: pixel, dpi (optional)
// output: pixel / false

// description:
// Convert inches to pixel

function inch2px ($inch, $dpi=72)
{
  if ($inch > 0 && $dpi > 0)
  {
    return round (($inch * $dpi), 0);
  }
  else return false;
}

// ---------------------- vtt2array -----------------------------
// function: vtt2array()
// input: VTT string
// output: array / false

// description:
// Converts VTT string to array

function vtt2array ($vtt)
{
  $result = array();
  
  if ($vtt != "")
  {
    $vtt_lines = explode ("\n", $vtt);
    
    if (sizeof ($vtt_lines) > 0)
    {
      $start = "00:00:00.000";
      
      foreach ($vtt_lines as $vtt_line)
      {
        if (strpos ($vtt_line, "-->") > 0)
        {
          list ($start, $stop) = explode ("-->", $vtt_line);
          $start = trim ($start);
          
          $result[$start]['start'] = $start;
          $result[$start]['stop'] = trim ($stop);
        }
        elseif (trim ($vtt_line) != "" && strtoupper (trim ($vtt_line)) != "WEBVTT")
        {
          $result[$start]['text'] = trim ($vtt_line);
        }
      }
    }
  }
  
  if (sizeof ($result) > 0) return $result;
  else return false;
}
?>