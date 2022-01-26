<?php
/*
 * This file is part of
 * hyper Content & Digital Management Server - http://www.hypercms.com
 * Copyright (c) by hyper CMS Content Management Solutions GmbH
 *
 * You should have received a copy of the license (license.txt) along with hyper Content & Digital Management Server
 */

// ========================================== MEDIA FUNCTIONS =======================================

// ---------------------------------------- valid_jpeg --------------------------------------------
// function: valid_jpeg()
// input: path to multimedia file [string]
// output: true / false

// description:
// Checks for the existence of the EOI segment header at the end of the file.
// Mainly used to verify JPEG images extracted from older Adobe InDesign files.

function valid_jpeg ($filepath)
{
  if (is_file ($filepath))
  {
    $filehandler = fopen ($filepath, "r");

    if (fseek ($filehandler, -2, SEEK_END) !== 0 || fread ($filehandler, 2) !== "\xFF\xD9")
    {
      fclose ($filehandler);
      return false;
    }
    else return true;
  }
  else return false;
}

// ---------------------------------------- ocr_extractcontent --------------------------------------------
// function: ocr_extractcontent()
// input: publication name [string], path to multimedia file [string], multimedia file name (file to be indexed) [string]
// output: extracted content as text string / false

// description:
// This function extracts the text content of multimedia objects using OCR and returns the text.
// It is a helper function for function indexcontent. Do not use function ocr_extractcontent directly since it will not support encrypted media files or media files in cloud storages.

function ocr_extractcontent ($site, $location, $file)
{
  global $mgmt_config, $mgmt_parser, $mgmt_imagepreview, $hcms_lang, $lang;

  // initialize
  $error = array();

  if (valid_publicationname ($site) && valid_locationname ($location) && valid_objectname ($file) && !empty ($mgmt_parser) && is_array ($mgmt_parser) && is_supported ($mgmt_parser, $file))
  {
    $usedby = "";
    $file_content = "";

    // load tesseract language mapping
    if (empty ($tesseract_lang) || !is_array ($tesseract_lang)) require ($mgmt_config['abs_path_cms']."include/tesseract_lang.inc.php");

    // add slash if not present at the end of the location string
    $location = correctpath ($location);

    // get file extension
    $file_info = getfileinfo ($site, $file, "comp");
    $file_ext = $file_info['ext'];

    // image is already of TIFF format or conversion to TIFF format not available
    $location_source = $location;
    $file_source = $file;

    // temporary directory for extracting file
    $temp_name = uniqid ("index");
    $temp_dir = $mgmt_config['abs_path_temp'];

    // convert to TIFF since Tesseract has best results with TIFF images
    if (($file_ext != ".tif" && $file_ext != ".tiff" && $file_ext != ".png") && !empty ($mgmt_imagepreview) && is_array ($mgmt_imagepreview) && sizeof ($mgmt_imagepreview) > 0)
    {
      $cmd = "";

      // find image converter
      foreach ($mgmt_imagepreview as $ext_image=>$converter)
      {
        if (substr_count (strtolower ($ext_image).".", $file_ext.".") > 0)
        {
          $cmd = $converter." -density 300 \"".shellcmd_encode ($location.$file)."\" -depth 8 -strip -background white -alpha off -auto-level -compress none \"".shellcmd_encode ($temp_dir.$temp_name).".temp-%0d.tiff\"";
          break;
        }
      }

      if (!empty ($cmd))
      {
        // execute and redirect stderr (2) to stdout (1)
        @exec ($cmd." 2>&1", $output, $errorCode);

        // on error
        if ($errorCode || !is_file ($temp_dir.$temp_name.".temp-0.tiff"))
        {
          $errcode = "20531";
          $error[] = $mgmt_config['today']."|hypercms_media.inc.php|error|".$errcode."|Execution of imagemagick (code:".$errorCode.", command:".$cmd.") failed for file '".$file."' \t".implode ("\t", $output);
        }
        // on success
        else
        {
          $location_source = $temp_dir;
          $file_source = $temp_name.".temp-0.tiff";

          // get new file extension
          $file_info = getfileinfo ($site, $file_source, "comp");
          $file_ext = $file_info['ext']; 
        }
      }
    }

    // extract text from image using OCR (if source is TIFF or PNG image)
    if (!empty ($file_source) && is_file ($location_source.$file_source) && ($file_ext == ".tif" || $file_ext == ".tiff" || $file_ext == ".png"))
    {
      $lang_options_array = array();

      // use -l lang-id to set the language that should be used for the OCR, by default it is English
      if (!empty ($lang)) $lang_options_array[] = $tesseract_lang[$lang];

      // scan for other languages
      if (!empty ($mgmt_config[$site]['ocr']))
      {
        $temp_array = explode (",", trim ($mgmt_config[$site]['ocr'], ","));

        // max 3 additional languages for OCR due to execution time
        $i = 1;

        foreach ($temp_array as $temp)
        {
          // get tesseract language code
          if (trim ($temp) != "" && !empty ($tesseract_lang[$temp]))
          {
            $lang_options_array[] = $tesseract_lang[$temp];
            $i++;
            if ($i > 3) break;
          }
        }
      }

      if (sizeof ($lang_options_array) > 0)
      {
        $lang_options_array = array_unique ($lang_options_array);
        $lang_options = " -l ".implode ("+", $lang_options_array);
      }
      else $lang_options = "";

      // OCR options:
      //   --tessdata-dir PATH   Specify the location of tessdata path.
      //   --user-words PATH     Specify the location of user words file.
      //   --user-patterns PATH  Specify the location of user patterns file.
      //   -l LANG[+LANG]        Specify language(s) used for OCR.
      //   -c VAR=VALUE          Set value for config variables. Multiple -c arguments are allowed.
      //   --psm NUM             Specify page segmentation mode.
      //   --oem NUM             Specify OCR Engine mode.
      // NOTE: These options must occur before any configfile.

      // PSM - Page segmentation modes:
      //   0    Orientation and script detection (OSD) only.
      //   1    Automatic page segmentation with OSD.
      //   2    Automatic page segmentation, but no OSD, or OCR.
      //   3    Fully automatic page segmentation, but no OSD. (Default)
      //   4    Assume a single column of text of variable sizes.
      //   5    Assume a single uniform block of vertically aligned text.
      //   6    Assume a single uniform block of text.
      //   7    Treat the image as a single text line.
      //   8    Treat the image as a single word.
      //  9    Treat the image as a single word in a circle.
      //  10    Treat the image as a single character.
      //  11    Sparse text. Find as much text as possible in no particular order.
      //  12    Sparse text with OSD.
      //  13    Raw line. Treat the image as a single text line, bypassing hacks that are Tesseract-specific.

      // OCR Engine modes:
      //   0    Legacy engine only.
      //   1    Neural nets LSTM engine only.
      //   2    Legacy + LSTM engines.
      //   3    Default, based on what is available.

      // find parser (avoid conflicts with other parsers for PDF or MS Word
      foreach ($mgmt_parser as $ext_parser=>$parser)
      {
        if (!empty ($file_ext) && substr_count (strtolower ($ext_parser).".", $file_ext.".") > 0 && trim ($parser) != "")
        {
          // temporary pages/images
          if (is_file ($temp_dir.$temp_name.".temp-0.tiff"))
          {
            // count pages
            for ($page_count = 0; $page_count <= 10000; $page_count++)
            {
              $temp_file = $temp_name.".temp-".$page_count.".tiff";

              // extract text from image using OCR
              if (is_file ($temp_dir.$temp_file))
              {
                // create temp text file from TIFF image (file extension for text file will be added by Tesseract)
                // using Orientation and script detection (OSD)
                $cmd = $parser." \"".shellcmd_encode ($temp_dir.$temp_file)."\" \"".shellcmd_encode ($temp_dir.$temp_name)."\"  ".$lang_options." --psm 1";

                // execute and redirect stderr (2) to stdout (1)
                @exec ($cmd." 2>&1", $output, $errorCode);

                // on error
                if ($errorCode || !is_file ($temp_dir.$temp_name.".txt"))
                {
                  $errcode = "20532";
                  $error[] = $mgmt_config['today']."|hypercms_media.inc.php|error|".$errcode."|Execution of tesseract (code:".$errorCode.", command:".$cmd.") failed for file '".$file."' \t".implode ("\t", $output);
                }
                // on success
                else
                {
                  $file_content .= loadfile_fast ($temp_dir, $temp_name.".txt")." ";

                  // remove temp files
                  if (is_file ($temp_dir.$temp_file)) deletefile ($temp_dir, $temp_file, false);
                  if (is_file ($temp_dir.$temp_name.".txt")) deletefile ($temp_dir, $temp_name.".txt", false);
                }
              }
              // no more temp files to scan
              else break;
            }
          }
          // original source
          else
          {
            // create temp text file from TIFF image (file extension for text file will be added by Tesseract)
            // using Orientation and script detection (OSD)
            $cmd = $parser." \"".shellcmd_encode ($location_source.$file_source)."\" \"".shellcmd_encode ($temp_dir.$temp_name)."\"  ".$lang_options." --psm 1";

            // execute and redirect stderr (2) to stdout (1)
            @exec ($cmd." 2>&1", $output, $errorCode);

            // on error
            if ($errorCode || !is_file ($temp_dir.$temp_name.".txt"))
            {
              $errcode = "20532";
              $error[] = $mgmt_config['today']."|hypercms_media.inc.php|error|".$errcode."|Execution of tesseract (code:".$errorCode.", command:".$cmd.") failed for file '".$file."' \t".implode ("\t", $output);
            }
            // on success
            else
            {
              $file_content = loadfile_fast ($temp_dir, $temp_name.".txt");

              // remove temp files
              if (is_file ($temp_dir.$temp_name.".txt")) deletefile ($temp_dir, $temp_name.".txt", false);
              break;
            }
          }
        }
      }
    }

    // save log
    savelog (@$error);

    if (!empty ($file_content)) return $file_content;
    else return false;
  }
  else return false;
}

// ---------------------------------------- indexcontent --------------------------------------------
// function: indexcontent()
// input: publication name [string], path to multimedia file [string], multimedia file name (file to be indexed) [string], container name or ID [string] (optional), container XML-content [string] (optional), user name [string], return the content without saving it in the system [boolean] (optonal)
// output: true / false

// description:
// This function extracts the text content of multimedia objects and writes it the text to the container.
// The given charset of the publication (not set by default), container or publication (not set by default) will be used.
// The default character set of default.meta.tpl is UTF-8, so all content should be saved in UTF-8.

function indexcontent ($site, $location, $file, $container="", $container_content="", $user="", $return_content=false)
{
  global $mgmt_config, $mgmt_parser, $mgmt_imagepreview, $mgmt_uncompress, $hcms_ext, $hcms_lang, $lang;

  $error = array();

  if (valid_publicationname ($site) && valid_locationname ($location) && valid_objectname ($file) && valid_objectname ($user))
  {
    $usedby = "";

    // load file extensions
    if (empty ($hcms_ext) || !is_array ($hcms_ext)) require ($mgmt_config['abs_path_cms']."include/format_ext.inc.php");

    // add slash if not present at the end of the location string
    $location = correctpath ($location);

    // if RAW image (use the converted JPEG as source file)
    if (is_rawimage ($file))
    {
      // get file name without extension
      $file_name = strrev (substr (strstr (strrev ($file), "."), 1));
      $file = $file_name.".jpg";
    }

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

      // if encrypted
      if (!empty ($temp['result']) && !empty ($temp['crypted']) && !empty ($temp['templocation']) && !empty ($temp['tempfile']))
      {
        $location = $temp['templocation'];
        $file = $temp['tempfile'];
      }
      // if restored
      elseif (!empty ($temp['result']) && !empty ($temp['restored']) && !empty ($temp['location']) && !empty ($temp['file']))
      {
        $location = $temp['location'];
        $file = $temp['file'];
      }

      // verify local media file
      if (!is_file ($location.$file)) return false;

      // ------------------------ Adobe PDF -----------------------
      // get file content from PDF
      if (($file_ext == ".pdf" || $file_ext == ".ai") && !empty ($mgmt_parser['.pdf']))
      {
        // use of XPDF to parse PDF files.
        // please note: the executable "pdftotext" must be located in the "bin" directory!
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
          $error[] = $mgmt_config['today']."|hypercms_media.inc.php|error|".$errcode."|Execution of pdftotext (code:".$errorCode.") failed in indexcontent for file '".$location.$file."'"; 
        }
        elseif (is_array ($file_content))
        {
          $file_content = implode ("\n", $file_content);
        }
        else $file_content = "";

        // try OCR if no content has been extracted and create annotation images from PDF
        if (trim ($file_content) == "")
        {
          $file_content = ocr_extractcontent ($site, $location, $file);
        }
      }

      // ------------------------ OPEN OFFICE -----------------------
      // get file content from Open Office Text (odt) in UTF-8
      elseif (($file_ext == ".odt" || $file_ext == ".ods" || $file_ext == ".odp") && !empty ($mgmt_uncompress['.zip'])) 
      {
        // temporary directory for extracting file
        $temp_name = uniqid ("index");
        $temp_dir = $mgmt_config['abs_path_temp'].$temp_name."/";

        // create temporary directory for extraction
        @mkdir ($temp_dir, $mgmt_config['fspermission']);

        // .odt is a ZIP-file with the content placed in the file content.xml
        $cmd = $mgmt_uncompress['.zip']." \"".shellcmd_encode ($location.$file)."\" content.xml -d \"".shellcmd_encode ($temp_dir)."\"";

        // execute and redirect stderr (2) to stdout (1)
        @exec ($cmd." 2>&1", $output, $errorCode);

        if ($errorCode && is_array ($output))
        {
          $errcode = "20133";
          $error[] = $mgmt_config['today']."|hypercms_media.inc.php|error|".$errcode."|Execution of unzip (code:".$errorCode.", command".$cmd.") failed for '".$location.$file."' \t".implode ("\t", $output); 
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
      elseif (($file_ext == ".doc") && !empty ($mgmt_parser['.doc']))
      {
        $cmd = $mgmt_parser['.doc']." -t -i 1 -m UTF-8.txt \"".shellcmd_encode ($location.$file)."\"";

        // execute and redirect stderr (2) to stdout (1)
        @exec ($cmd." 2>&1", $file_content, $errorCode); 

        if ($errorCode)
        {
          $file_content = ""; 

          $errcode = "20134";
          $error[] = $mgmt_config['today']."|hypercms_media.inc.php|error|".$errcode."|Execution of antiword (code:$errorCode) failed in indexcontent for file: ".$location.$file; 
        }
        elseif (is_array ($file_content))
        {
          $file_content = implode ("\n", $file_content);
        }
        else $file_content = "";
      }
      // get file content from MS Word 2007+ (docx) in UTF-8
      elseif (($file_ext == ".docx") && !empty ($mgmt_uncompress['.zip']))
      {
        // temporary directory for extracting file
        $temp_name = uniqid ("index");
        $temp_dir = $mgmt_config['abs_path_temp'].$temp_name."/";

        // create temporary directory for extraction
        @mkdir ($temp_dir, $mgmt_config['fspermission']);

        // docx is a ZIP-file with the content placed in the file word/document.xml
        $cmd = $mgmt_uncompress['.zip']." \"".shellcmd_encode ($location.$file)."\" word/document.xml -d \"".shellcmd_encode ($temp_dir)."\"";

        // execute and redirect stderr (2) to stdout (1)
        @exec ($cmd." 2>&1", $output, $errorCode);

        if ($errorCode && is_array ($output))
        {
          $errcode = "20134";
          $error[] = $mgmt_config['today']."|hypercms_media.inc.php|error|".$errcode."|Execution of unzip (code:".$errorCode.", command".$cmd.") failed for '".$location.$file."' \t".implode ("\t", $output); 
        } 
        else
        {
          $file_content = loadfile ($temp_dir."word/", "document.xml");

          if ($file_content != false)
          {
            // get encoding/charset
            $xml_encoding = gethtmltag ($file_content, "?xml");
            if ($xml_encoding != false) $charset_temp = getattribute ($xml_encoding, "encoding");
            
            // add whitespaces before newline
            $file_content = str_replace ("</", " </", $file_content);

            // replace paragraph and newline with real newlines
            $file_content = str_replace (array ("</w:p>", "<w:br/>"), array ("\n\n", "\n"), $file_content);

            // remove multiple white spaces
            $file_content = preg_replace ('/\s+/', ' ', $file_content);

            // convert content if source charset is not UTF-8 (XML-Containers of multimedia files must use UTF-8 encoding)
            if (!empty ($charset_temp) && strtolower ($charset_temp) != "utf-8")
            {
              $file_content = convertchars ($file_content, $charset_temp, "UTF-8");
            }
          }

          // remove temp directory
          deletefile ($mgmt_config['abs_path_temp'], $temp_name, 1);
        } 
      }
      // ------------------------ MS EXCEL -----------------------
      // get file content from MS EXCEL 2007 (xlsx) in UTF-8
      elseif (($file_ext == ".xlsx") && !empty ($mgmt_uncompress['.zip']))
      {
        // temporary directory for extracting file
        $temp_name = uniqid ("index");
        $temp_dir = $mgmt_config['abs_path_temp'].$temp_name."/";

        // create temporary directory for extraction
        @mkdir ($temp_dir, $mgmt_config['fspermission']);

        // xlsx is a ZIP-file with the content placed in the file xl/sharedStrings.xml
        $cmd = $mgmt_uncompress['.zip']." \"".shellcmd_encode ($location.$file)."\" xl/sharedStrings.xml -d \"".shellcmd_encode ($temp_dir)."\"";

        // execute and redirect stderr (2) to stdout (1)
        @exec ($cmd." 2>&1", $output, $errorCode);

        if ($errorCode && is_array ($output))
        {
          $errcode = "20134";
          $error[] = $mgmt_config['today']."|hypercms_media.inc.php|error|".$errcode."|Execution of unzip (code:".$errorCode.", command".$cmd.") failed for '".$location.$file."' \t".implode ("\t", $output);
        } 
        else
        {
          $file_content = loadfile ($temp_dir."xl/", "sharedStrings.xml");

          if ($file_content != false)
          {
            // get encoding/charset
            $xml_encoding = gethtmltag ($file_content, "?xml");
            if ($xml_encoding != false) $charset_temp = getattribute ($xml_encoding, "encoding");

            // add whitespaces
            $file_content = str_replace ("</", " </", $file_content);

            // strip tags
            $file_content = strip_tags ($file_content);

            // convert content if source charset is not UTF-8 (XML-Containers of multimedia files must use UTF-8 encoding)
            if (!empty ($charset_temp) && strtolower ($charset_temp) != "utf-8")
            {
              $file_content = convertchars ($file_content, $charset_temp, "UTF-8");
            }
          }
 
          // remove temp directory
          deletefile ($mgmt_config['abs_path_temp'], $temp_name, 1);
        } 
      }
      // ------------------------ MS Powerpoint -----------------------
      // get file content from MS Powerpoint before 2007 (ppt) in UTF-8
      elseif ($file_ext == ".ppt" || $file_ext == ".pps")
      {
        $file_content = "";

        // This approach uses detection of the string "chr(0f).Hex_value.chr(0x00).chr(0x00).chr(0x00)" to find text strings, 
        // which are then terminated by another NUL chr(0x00). [1] Get text between delimiters [2] 
        $filehandle = fopen ($location.$file, "r");

        if ($filehandle != false)
        {
          if (filesize ($location.$file) > 0)
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
          $error[] = $mgmt_config['today']."|hypercms_media.inc.php|error|".$errcode."|Extraction of content from powerpoint failed in indexcontent for file: ".$location.$file; 
        } 
      }
      // get file content from MS Powerpoint 2007 (pptx) in UTF-8
      elseif (($file_ext == ".pptx" || $file_ext == ".ppsx") && !empty ($mgmt_uncompress['.zip']))
      {
        $file_content = "";
        
        // temporary directory for extracting file
        $temp_name = uniqid ("index");
        $temp_dir = $mgmt_config['abs_path_temp'].$temp_name."/";

        // create temporary directory for extraction
        @mkdir ($temp_dir, $mgmt_config['fspermission']);

        // pptx is a ZIP-file with the content placed in the file ppt/slides/slide#.xml (# ... number of the slide)
        $cmd = $mgmt_uncompress['.zip']." \"".shellcmd_encode ($location.$file)."\" ppt/slides/slide* -d \"".shellcmd_encode ($temp_dir)."\"";

        // execute and redirect stderr (2) to stdout (1)
        @exec ($cmd." 2>&1", $output, $errorCode);

        if ($errorCode && is_array ($output))
        {
          $errcode = "20136";
          $error[] = $mgmt_config['today']."|hypercms_media.inc.php|error|".$errcode."|Execution of unzip (code:".$errorCode.", command".$cmd.") failed for '".$location.$file."' \t".implode ("\t", $output); 
        } 
        else
        {
          $scandir = @scandir ($temp_dir."ppt/slides/");

          if ($scandir)
          {
            // collect source files
            foreach ($scandir as $file)
            { 
              if (substr_count ($file, ".xml") == 1)
              {
                $file_temp = loadfile ($temp_dir."ppt/slides/", $file);

                if ($file_temp != false)
                {
                  // get encoding/charset
                  $xml_encoding = gethtmltag ($file_temp, "?xml");
                  if ($xml_encoding != false) $charset_temp = getattribute ($xml_encoding, "encoding");

                  // add whitespaces
                  $file_temp = str_replace ("</", " </", $file_temp);

                  // strip tags
                  $file_temp = strip_tags ($file_temp);

                  // convert content if source charset is not UTF-8 (XML-Containers of multimedia files must use UTF-8 encoding)
                  if (!empty ($charset_temp) && strtolower ($charset_temp) != "utf-8")
                  {
                    $file_temp = convertchars ($file_temp, $charset_temp, "UTF-8");
                  }

                  // merge
                  $file_content = $file_content." ".$file_temp;
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
      elseif ($file_ext != "" && substr_count (strtolower ($hcms_ext['image']).".", $file_ext.".") > 0 && !empty ($mgmt_parser))
      {
        $file_content = ocr_extractcontent ($site, $location, $file);
      }
      else $file_content = "";

      // ------------------------ AUDIO, IMAGES, VIDEOS ----------------------- 
      // SPECIAL CASE: the meta data attributes found in the file will be saved using a mapping.
      // get file content from image formats with meta data
      if ($file_ext != "" && substr_count (strtolower ($hcms_ext['audio'].$hcms_ext['image'].$hcms_ext['video']).".", $file_ext.".") > 0)
      {
        // function setmetadata provides metadata in the content container without saving the container
        $container_content_temp = setmetadata ($site, "", "", $file, "", $container_content, $user, false);
 
        if (!empty ($container_content_temp)) $container_content = $container_content_temp;
      } 

      // delete temp file
      if (!empty ($temp['result']) && !empty ($temp['created'])) deletefile ($temp['templocation'], $temp['tempfile'], 0);

      // write to content container and database
      if (empty ($return_content))
      {
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
            $container_contentnew = setxmlparameter ($container_content, "encoding", $charset_dest);
            if (!empty ($container_contentnew)) $container_content = $container_contentnew;
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
          $container_contentnew = setcontent ($container_content, "<multimedia>", "<file>", $file, "", "", true);

          if ($container_contentnew != false)
          {
            $container_contentnew = setcontent ($container_contentnew, "<multimedia>", "<content>", "<![CDATA[".cleancontent ($file_content, $charset_dest)."]]>", "", "", true);
          }
          // insert new multimedia xml-node
          else
          {
            $multimedia_schema_xml = chop (loadfile ($mgmt_config['abs_path_cms']."xmlsubschema/", "multimedia.schema.xml.php"));

            $multimedia_node = setcontent ($multimedia_schema_xml, "<multimedia>", "<file>", $file, "", "", true);
            $multimedia_node = setcontent ($multimedia_node, "<multimedia>", "<content>", "<![CDATA[".cleancontent ($file_content, $charset_dest)."]]>", "", "", true);
            if ($multimedia_node != false) $container_contentnew = insertcontent ($container_content, $multimedia_node, "<container>");
          }

          // save log
          savelog (@$error);

          // save container
          if ($container_contentnew != false)
          {
            // relational DB connectivity
            if (!empty ($mgmt_config['db_connect_rdbms'])) rdbms_setcontent ($site, $container_id, $text_array, $type_array, $user);

            // date 
            $date = date ("Y-m-d H:i:s", time());

            // set modified date in container
            $container_contentnew = setcontent ($container_contentnew, "<hyperCMS>", "<contentdate>", $date, "", "", true);

            // set owner (if not a system user)
            if ($container_contentnew != false && $user != "sys" && substr ($user, 0, 4) != "sys:") $container_contentnew = setcontent ($container_contentnew, "<hyperCMS>", "<contentuser>", $user, "", "", true);

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
          if (!empty ($mgmt_config['db_connect_rdbms'])) rdbms_setcontent ($site, $container_id, "", "", $user);

          // date 
          $date = date ("Y-m-d H:i:s", time());

          // set modified date in container
          $container_content = setcontent ($container_content, "<hyperCMS>", "<contentdate>", $date, "", "", true);

          // set owner (if not a system user)
          if ($container_content != false && $user != "sys" && substr ($user, 0, 4) != "sys:") $container_content = setcontent ($container_content, "<hyperCMS>", "<contentuser>", $user, "", "", true);

          // save container
          if ($container_content != false)
          {
            savecontainer ($container, "published", $container_content, $user);
            return savecontainer ($container, "work", $container_content, $user);
          }
        }
      }
      // return content
      elseif (!empty ($file_content))
      {
        // remove all tags
        $file_content = strip_tags ($file_content);
        $file_content = trim ($file_content);

        return $file_content;
      }
      else return false;
    }
  }

  return false;
}

// ---------------------------------------- unindexcontent --------------------------------------------
// function: unindexcontent()
// input: publication name [string], file location [string], file name [string], multimedia file to index [string], container name or ID [string], container XML-content [string], user name [string]
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
// input: publication name [string], container IDs [array] (optional)
// output: true / false

// description:
// Reindexes all media files of a publication. Optionally only for specific containers.

function reindexcontent ($site, $container_id_array="")
{
  global $mgmt_config;

  $error = array();

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

      $scandir = scandir ($location);

      foreach ($scandir as $file)
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
              $error[] = $mgmt_config['today']."|hypercms_media.inc.php|information|".$errcode."|Reindex of content was successful for: ".$site."/".$file; 
            }
            else
            {
              $errcode = "20501";
              $error[] = $mgmt_config['today']."|hypercms_media.inc.php|error|".$errcode."|Reindex of content failed for: ".$site."/".$file; 
            }

            // save log
            savelog (@$error);
          }
        }
      }
    }

    return true;
  }
  else return false;
}

// ---------------------- base64_to_file -----------------------------
// function: base64_to_file()
// input: base64 encoded [string], path to destination dir [string], file name [string]
// output: new file name / false on error

// description:
// Decodes a base64 encoded string and saves it to as a file.

function base64_to_file ($base64_string, $location, $file)
{
  if ($base64_string != "" && valid_locationname ($location) && valid_objectname ($file))
  {
      // add slash if not present at the end of the location string
    $location = correctpath ($location);

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

// ---------------------- exec_in_background -----------------------------
// function: exec_in_background()
// input: exec command [string
// output: %

// description:
// Executes a shell command in the background

function exec_in_background ($cmd)
{
  global $mgmt_config;

  if (substr (php_uname(), 0, 7) == "Windows")
  {
    pclose (popen ("start /B ". $cmd, "r"));
  }
  else
  {
    exec ($cmd . " > /dev/null &");  
  }
}

// ---------------------- createthumbnail_indesign -----------------------------
// function: createthumbnail_indesign()
// input: publication name [string], path to source dir [string], path to destination dir [string], file name [string]
// output: new file name / false on error (saves only thumbnail media file in destination location, only jpeg format is supported as output)

// description:
// Creates a thumbnail by extracting the thumbnail from an indesign file and transferes the generated image via remoteclient.
// For good results, InDesign Preferences must be set to save preview image at an extra large size.

function createthumbnail_indesign ($site, $location_source, $location_dest, $file)
{
  global $mgmt_config, $mgmt_mediametadata, $user;

  // initialize
  $error = array();
  $result = "";

  if (valid_publicationname ($site) && valid_locationname ($location_source) && valid_locationname ($location_dest) && valid_objectname ($file))
  {
    // add slash if not present at the end of the location string
    $location_source = correctpath ($location_source);
    $location_dest = correctpath ($location_dest);

    // prepare media file
    $temp_source = preparemediafile ($site, $location_source, $file, $user);

    // if encrypted
    if (!empty ($temp_source['result']) && !empty ($temp_source['crypted']) && !empty ($temp_source['templocation']) && !empty ($temp_source['tempfile']))
    {
      $location_source = $temp_source['templocation'];
      $file = $temp_source['tempfile'];
    }
    // if restored
    elseif (!empty ($temp_source['result']) && !empty ($temp_source['restored']) && !empty ($temp_source['location']) && !empty ($temp_source['file']))
    {
      $location_source = $temp_source['location'];
      $file = $temp_source['file'];
    }

    // verify local media file
    if (!is_file ($location_source.$file)) return false;

    // get file name without extension
    $file_name = strrev (substr (strstr (strrev ($file), "."), 1));

    // new file name
    $newfile = $file_name.".thumb.jpg"; 

    // get source file extension
    $file_ext = strtolower (strrchr ($file, "."));

    // try EXIFTOOL
    if (!empty ($mgmt_mediametadata) && is_array ($mgmt_mediametadata))
    {
      foreach ($mgmt_mediametadata as $extensions => $executable)
      {
        if (substr_count ($extensions.".", $file_ext.".") > 0 && $executable != "")
        {
          // extract thumbnail
          $cmd = $executable." -r -b -PageImage \"".shellcmd_encode ($location_source.$file)."\" > \"".shellcmd_encode ($location_dest.$newfile)."\"";

          // execute and redirect stderr (2) to stdout (1)
          @exec ($cmd." 2>&1", $output, $errorCode);

          // on error
          if ($errorCode)
          {
            $errcode = "20141";
            $error[] = $mgmt_config['today']."|hypercms_media.php|error|".$errcode."|Execution of EXIFTOOL (code:".$errorCode.", command:".$cmd.") failed to extract thumbnail from INDD file '".$file."' \t".implode("\t", $output);
  
            // save log
            savelog (@$error);

            return false;
          }
          // on success
          elseif (is_file ($location_dest.$newfile) && filesize ($location_dest.$newfile) > 0)
          {
            return $newfile;
          }
        }
      }
    }

    // try other methods
    $filedata = @file_get_contents ($location_source.$file);

    if ($filedata != "")
    {
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
      if (empty ($result))
      {
        $xmpdata = getcontent ($filedata, "<xmp:PageInfo>");

        if (!empty ($xmpdata[0]))
        {
          // get base64 encoded string from xml node
          $imgstr = getcontent ($xmpdata[0], "<xmpGImg:image>");

          if (empty ($imgstr[0]))
          {
            // try attribute
            $result = getattribute ($xmpdata[0], "xmpGImg:image"); 
          }
          else $result = $imgstr[0];
        }
      }

      // try to extract data from XAP node (deprecated)
      if (empty ($result))
      {
        $xapdata = getcontent ($filedata, "<xap:Thumbnails>");

        if (!empty ($xapdata[0]))
        {
          // get base64 encoded string from xml node
          $imgstr = getcontent ($xapdata[0], "<xapGImg:image>");

          if (empty ($imgstr[0]))
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
      if (!empty ($result))
      {
        // prepare base64 encoded image string
        if (substr (trim ($result), 0, 1) == "<") $result = strip_tags ($result);

        // remove decoded Line Feed character
        $result = str_replace ("#xA;", "", $result);

        $filehandler = fopen ($location_dest.$newfile, "wb");

        if ($filehandler && !empty ($result))
        {
          fwrite ($filehandler, base64_decode ($result));
          fclose ($filehandler);

          // remove thumbnail file if it is not a valid image
          if (exif_imagetype ($location_dest.$newfile) != IMAGETYPE_JPEG || valid_jpeg ($location_dest.$newfile) == false)
          {
            unlink ($location_dest.$newfile);

            return false;
          }
          // on success
          else
          {
            // save in cloud storage
            if (function_exists ("savecloudobject")) savecloudobject ($site, $location_dest, $newfile, $user);

            // remote client
            remoteclient ("save", "abs_path_media", $site, $location_dest, "", $newfile, "");

            return $newfile;
          }
        }
        else
        {
          $errcode = "20221";
          $error[] = $mgmt_config['today']."|hypercms_media.inc.php|error|".$errcode."|createthumbnail_indesign failed to save file: ".$location_dest.$newfile; 
 
          // save log
          savelog (@$error);
 
          return false;
        } 
      }
      else
      {
        $errcode = "20222";
        $error[] = $mgmt_config['today']."|hypercms_media.inc.php|error|".$errcode."|createthumbnail_indesign failed for file: ".$location_source.$file; 
 
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
// input: publication name [string], path to source dir [string], path to destination dir [string], file name [string], frame of video in seconds or hh:mm:ss.xxx [integer,time], 
//        image width in pixel and -1 to keep aspect ratio based on height [integer] (optional), image height in pixel and -1 to keep aspect ratio based on width [integer] (optional), individual file name without the file extension of the created image [string] (optional)
// output: new file name / false on error

// description:
// Creates a thumbnail picture of a video frame. Saves only the thumbnail media file in destination location. Only jpeg format is supported as output.
// Media files with a valid container identifier in their name will be saved in the cloud storage.

function createthumbnail_video ($site, $location_source, $location_dest, $file, $frame, $width=0, $height=0, $filename="")
{
  global $mgmt_config, $mgmt_mediapreview, $mgmt_mediaoptions, $user;

  // initialize
  $error = array();

  if (valid_publicationname ($site) && valid_locationname ($location_source) && valid_locationname ($location_dest) && valid_objectname ($file) && is_video ($file) && $frame != "")
  {
    // add slash if not present at the end of the location string
    $location_source = correctpath ($location_source);
    $location_dest = correctpath ($location_dest);

    // remove .orig sub-file-extension
    if (strpos ($file, ".orig.") > 0) $newfile = str_replace (".orig.", ".", $file);
    else $newfile = $file;

    // get file info
    $fileinfo = getfileinfo ($site, $location_source.$newfile, "comp");

    // default value for auto rotate video if a rotation has been detected (true) or leave video in it's original state (false)
    if (!isset ($mgmt_mediaoptions['autorotate-video'])) $mgmt_mediaoptions['autorotate-video'] = false;

    // noautoroate option for input video file is only supported by later FFMPEG versions
    // since the auto rotation is also taking care by the system FFMPEG should not autorotate the video
    if (!empty ($mgmt_mediaoptions['autorotate-video'])) $noautorotate = "-noautorotate";
    else $noautorotate = "";

    // thumbnail file name
    if (!empty ($filename)) $newfile = $filename.".jpg";
    else $newfile = $fileinfo['filename'].".thumb.jpg";

    // prepare media file
    $temp_source = preparemediafile ($site, $location_source, $file, $user);

    // if encrypted
    if (!empty ($temp_source['result']) && !empty ($temp_source['crypted']) && !empty ($temp_source['templocation']) && !empty ($temp_source['tempfile']))
    {
      $location_source = $temp_source['templocation'];
      $file = $temp_source['tempfile'];
    }
    // if restored
    elseif (!empty ($temp_source['result']) && !empty ($temp_source['restored']) && !empty ($temp_source['location']) && !empty ($temp_source['file']))
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
      // original video info
      $videoinfo = getvideoinfo ($location_source.$file);

      reset ($mgmt_mediapreview);

      // supported extensions for media rendering
      foreach ($mgmt_mediapreview as $mediapreview_ext => $mediapreview)
      {
        $correct = "";

        // rotate original preview video if rotation is used
        if (!empty ($videoinfo['rotate']) && !empty ($mgmt_mediaoptions['autorotate-video']))
        {
          // usage: transpose=1
          // for the transpose parameter you can pass:
          // 0 = 90CounterCLockwise and Vertical Flip (default)
          // 1 = 90Clockwise
          // 2 = 90CounterClockwise
          // 3 = 90Clockwise and Vertical Flip
          if ($videoinfo['rotate'] == "90") $correct = "-vf \"transpose=1\"";
          elseif ($videoinfo['rotate'] == "180") $correct = "-vf \"hflip,vflip\"";
          elseif ($videoinfo['rotate'] == "-90") $correct = "-vf \"transpose=2\"";
        }

        // image size
        $size = "";

        // to keep the aspect ratio, we need to specify only one component, either width or height, and set the other component to -1
        if (intval ($width) != 0 && intval ($height) != 0)
        {
          $size = "-vf scale=".intval($width).":".intval($height);
        }

        // check file extension
        if (!empty ($fileinfo['ext']) && substr_count ($mediapreview_ext.".", $fileinfo['ext'].".") > 0 && !empty ($mgmt_mediapreview[$mediapreview_ext]))
        {
          // remove destination file if it exists
          deletefile ($location_dest, $newfile, 0);

          // Removed option "-f image2" in version 9.0.2:
          // -f is the format of the input/output and image2 is the demuxer. See ffmpeg documenation for more info: http://www.ffmpeg.org/ffmpeg-formats.html#Demuxers
          $cmd = $mgmt_mediapreview[$mediapreview_ext]." ".$noautorotate." -i \"".shellcmd_encode ($location_source.$file)."\" ".$correct." -ss ".shellcmd_encode ($frame)." -vframes 1 ".$size." \"".shellcmd_encode ($location_dest.$newfile)."\"";

          // execute and redirect stderr (2) to stdout (1) 
          exec ($cmd." 2>&1", $output, $errorCode);

          $executed = true;
        }
      }
    }

    // delete temp file
    if ($temp_source['result'] && $temp_source['created']) deletefile ($temp_source['templocation'], $temp_source['tempfile'], 0);

    // if thumbnail creation has been executed
    if (!empty ($executed))
    {
      if (!is_file ($location_dest.$newfile) || $errorCode) 
      {
        $errcode = "20241";
        $error[] = $mgmt_config['today']."|hypercms_media.inc.php|error|".$errcode."|exec of ffmpeg (code:".$errorCode.", command:".$cmd.") failed for file '".$file."' and frame ".$frame." \t".implode ("\t", $output);

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
  else return false;
}

// ---------------------- createimages_video -----------------------------
// function: createimages_video()
// input: publication name [string], path to source dir [string], path to destination dir [string], file name [string], name for image files [string] (optional), frames per second to create from the video [number] (optional), 
//        image format [jpg,png,bmp] (optional), image width in pixel and -1 to keep aspect ratio based on height [integer] (optional), image height in pixel and -1 to keep aspect ratio based on width [integer] (optional)
// output: true / false on error

// description:
// Creates and saves images of video screen size from a video to a directory.
// The media files will be saved in the local repository and not in the cloud storage.

function createimages_video ($site, $location_source, $location_dest, $file, $name="", $fs=1, $format="jpg", $width=0, $height=0)
{
  global $mgmt_config, $mgmt_mediapreview, $mgmt_mediaoptions, $user;

  // initialize
  $error = array();

  if (valid_publicationname ($site) && valid_locationname ($location_source) && valid_locationname ($location_dest) && valid_objectname ($file) && is_video ($file) && $fs > 0)
  {
    // default format
    $format = strtolower (trim ($format));

    if ($format != "jpg" && $format != "png" && $format != "bmp") $format = "jpg";

    // add slash if not present at the end of the location string
    $location_source = correctpath ($location_source);
    $location_dest = correctpath ($location_dest);

    // define file name for images and remove .orig sub-file-extension
    if (strpos ($file, ".orig.") > 0) $newfile = str_replace (".orig.", ".", $file);
    else $newfile = $file;

    // get file info
    $fileinfo = getfileinfo ($site, $location_source.$newfile, "comp");

    // default value for auto rotate video if a rotation has been detected (true) or leave video in it's original state (false)
    if (!isset ($mgmt_mediaoptions['autorotate-video'])) $mgmt_mediaoptions['autorotate-video'] = false;

    // noautoroate option for input video file is only supported by later FFMPEG versions
    // since the auto rotation is also taking care by the system FFMPEG should not autorotate the video
    if (!empty ($mgmt_mediaoptions['autorotate-video'])) $noautorotate = "-noautorotate";
    else $noautorotate = "";

    // file name
    if (trim ($name) != "") $newfile = $name;
    else $newfile = $fileinfo['filename'];

    // prepare media file
    $temp_source = preparemediafile ($site, $location_source, $file, $user);

    // if encrypted
    if (!empty ($temp_source['result']) && !empty ($temp_source['crypted']) && !empty ($temp_source['templocation']) && !empty ($temp_source['tempfile']))
    {
      $location_source = $temp_source['templocation'];
      $file = $temp_source['tempfile'];
    }
    // if restored
    elseif (!empty ($temp_source['result']) && !empty ($temp_source['restored']) && !empty ($temp_source['location']) && !empty ($temp_source['file']))
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
      // original video info
      $videoinfo = getvideoinfo ($location_source.$file);

      reset ($mgmt_mediapreview);

      // supported extensions for media rendering
      foreach ($mgmt_mediapreview as $mediapreview_ext => $mediapreview)
      {
        $correct = "";

        // rotate original video if rotation is used
        if (!empty ($videoinfo['rotate']) && !empty ($mgmt_mediaoptions['autorotate-video']))
        {
          // usage: transpose=1
          // for the transpose parameter you can pass:
          // 0 = 90CounterCLockwise and Vertical Flip (default)
          // 1 = 90Clockwise
          // 2 = 90CounterClockwise
          // 3 = 90Clockwise and Vertical Flip
          if ($videoinfo['rotate'] == "90") $correct = "-vf \"transpose=1\"";
          elseif ($videoinfo['rotate'] == "180") $correct = "-vf \"hflip,vflip\"";
          elseif ($videoinfo['rotate'] == "-90") $correct = "-vf \"transpose=2\"";
        }

        // image size
        $size = "";

        // to keep the aspect ratio, we need to specify only one component, either width or height, and set the other component to -1
        if (intval ($width) != 0 && intval ($height) != 0)
        {
          $size = "-vf scale=".intval($width).":".intval($height);
        }

        // check file extension
        if (!empty ($fileinfo['ext']) && substr_count ($mediapreview_ext.".", $fileinfo['ext'].".") > 0 && !empty ($mgmt_mediapreview[$mediapreview_ext]))
        {
          // remove destination file if it exists
          deletefile ($location_dest, $newfile, 0);

          // -r option sets framerate per second
          $cmd = $mgmt_mediapreview[$mediapreview_ext]." ".$noautorotate." -i \"".shellcmd_encode ($location_source.$file)."\" ".$correct."  -r ".shellcmd_encode ($fs)." ".$size." \"".shellcmd_encode ($location_dest.$newfile)."-%05d.".$format."\"";

          // execute and redirect stderr (2) to stdout (1)
          exec ($cmd." 2>&1", $output, $errorCode);

          $executed = true;
        }
      }
    }

    // delete temp file
    if ($temp_source['result'] && $temp_source['created']) deletefile ($temp_source['templocation'], $temp_source['tempfile'], 0);

    // if thumbnail creation has been executed
    if (!empty ($executed))
    {
      if (!is_file ($location_dest.$newfile."-00001.".$format) || $errorCode) 
      {
        $errcode = "20341";
        $error[] = $mgmt_config['today']."|hypercms_media.inc.php|error|".$errcode."|exec of ffmpeg (code:".$errorCode.", command:".$cmd.") failed for file '".$file."' \t".implode ("\t", $output);

        // save log
        savelog (@$error);

        return false;
      } 
      else
      {
        return true;
      }
    }
    else return false;
  }
  else return false;
}

// ---------------------- createmedia -----------------------------
// function: createmedia()
// input: publication name [string], path to source dir [string], path to destination dir [string], file name [string], 
//        format (file extension w/o dot) [string] (optional), 
//        type of image/video/audio file [thumbnail(for thumbnails of images),origthumb(thumbnail made from original video/audio),original(to overwrite original video/audio file),annotation(for annotation images),any other string present in $mgmt_imageoptions/$mgmt_mediaoptions,temp(for temporary files)] (optional),
//        force the file to be not encrypted even if the content of the publication must be encrypted [boolean] (optional), set media information [boolean] (optional), create image files in the background [boolean] (optional)
// output: new file name / false on error

// description:
// Creates an new image or video from the original file or creates a thumbnail and transferes the generated image via remoteclient.
// Saves original or thumbnail media file in destination location. For the thumbnail only JPEG is supported as output format.

function createmedia ($site, $location_source, $location_dest, $file, $format="", $type="thumbnail", $force_no_encrypt=false, $setmediainfo=true, $exec_in_background=false)
{
  global $mgmt_config, $mgmt_imagepreview, $mgmt_mediapreview, $mgmt_mediaoptions, $mgmt_imageoptions, $mgmt_maxsizepreview, $mgmt_mediametadata, $hcms_ext, $user;

  // initialize
  $error = array();
  $converted = false;
  $skip = false;
  $temp_file_delete = array();
  $session_id = "";

  if (valid_publicationname ($site) && valid_locationname ($location_source) && valid_locationname ($location_dest) && valid_objectname ($file))
  {
    // appending data to a file ensures that the previous write process is finished (required due to issue when editing encrypted files)
    avoidfilecollision ($file);

    // get container ID
    $container_id = getmediacontainerid ($file);

    // remove all video thumbnails
    if ($type == "thumbnail" || $type == "origthumb")
    {
      if (is_dir ($location_dest.$container_id)) deletefile ($location_dest, $container_id, 1);
    }

    // load file extensions
    if (empty ($hcms_ext) || !is_array ($hcms_ext)) require ($mgmt_config['abs_path_cms']."include/format_ext.inc.php");

    // publication management config
    if (!isset ($mgmt_config[$site]['abs_path_page']) && is_file ($mgmt_config['abs_path_data']."config/".$site.".conf.php"))
    {
      require_once ($mgmt_config['abs_path_data']."config/".$site.".conf.php");
    }

    // save input type in new variable
    $type_memory = $type;

    // add slash if not present at the end of the location string
    $location_source = correctpath ($location_source);
    $location_dest = correctpath ($location_dest);

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

    // if encrypted
    if (!empty ($temp_source['result']) && !empty ($temp_source['crypted']) && !empty ($temp_source['templocation']) && !empty ($temp_source['tempfile']))
    {
      $location_source = $temp_source['templocation'];
      $file = $temp_source['tempfile'];
    }
    // if restored
    elseif (!empty ($temp_source['result']) && !empty ($temp_source['restored']) && !empty ($temp_source['location']) && !empty ($temp_source['file']))
    {
      $location_source = $temp_source['location'];
      $file = $temp_source['file'];
    }

    // check if symbolic link
    if (is_link ($location_source.$file)) 
    {
      // get the real file path
      $path_source = readlink ($location_source.$file);

      // change location
      $location_source = getlocation ($path_source);
      // reset destination location for the original image
      if ($type == "original") $location_dest = $location_source;

      $file = getobject ($path_source);
    }
    else
    {
      $path_source = $location_source.$file;
    }

    // check if source file exists and has a size of min. 100 bytes
    if (!is_file ($location_source.$file) || filesize ($location_source.$file) < 100) return false;

    // write and close session (important for non-blocking: any page that needs to access a session now has to wait for the long running script to finish execution before it can begin)
    if (session_id() != "")
    {
      $session_id = session_id();
      session_write_close();
    }

    // get file size of media file in kB
    $filesize_orig = round (@filesize ($location_source.$file) / 1024, 0);
    if ($filesize_orig < 1) $filesize_orig = 1;

    // get individual watermark
    if ($mgmt_config['publicdownload'] == true) $containerdata = loadcontainer ($container_id, "work", "sys");
    else $containerdata = loadcontainer ($container_id, "published", "sys");

    if ($containerdata != "")
    {
      $wmlocation = getmedialocation ($site, $file, "abs_path_media");
      $wmnode = selectcontent ($containerdata, "<media>", "<media_id>", "Watermark");

      if (!empty ($wmnode[0]))
      {
        $temp = getcontent ($wmnode[0], "<mediafile>");
        if (!empty ($temp[0])) $wmfile = $temp[0];

        $temp = getcontent ($wmnode[0], "<mediaalign>");
        if (!empty ($temp[0])) $wmalign = $temp[0];
        else $wmalign = "center";

        if (!empty ($wmfile))
        {
          // prepare media file
          $temp = preparemediafile ($site, $wmlocation, $wmfile, $user);

          // if encrypted
          if (!empty ($temp['result']) && !empty ($temp['crypted']) && !empty ($temp['templocation']) && !empty ($temp['tempfile']))
          {
            $wmlocation = $temp['templocation'];
            $wmfile = $temp['tempfile'];
          }
          // if restored
          elseif (!empty ($temp['result']) && !empty ($temp['restored']) && !empty ($temp['location']) && !empty ($temp['file']))
          {
            $wmlocation = $temp['location'];
            $wmfile = $temp['file'];
          }

          if (is_file ($wmlocation.$wmfile))
          {
            $mgmt_config[$site]['watermark_image'] = "-wm ".$wmlocation.$wmfile."->".$wmalign."->10";
            $mgmt_config[$site]['watermark_video'] = "-wm ".$wmlocation.$wmfile."->".$wmalign."->10";
          }
        }
      }
    }

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
            if (!empty ($file_ext) && substr_count (strtolower ($imagepreview_ext).".", $file_ext.".") > 0 && trim ($imagepreview) != "")
            {
              // using dcraw package and ImageMagick (Debian 11 does not provide package ufraw-patch anymore since it is not maintained since 2016)
              // don ot use is_executable since the path /usr/bin/dcraw might be outside the allowed pathes and will result in errors in the php error log
              if (!empty ($mgmt_imagepreview['rawimage']) && strtolower ($mgmt_imagepreview['rawimage']) == "dcraw")
              {
                $cmd = getlocation ($mgmt_imagepreview[$imagepreview_ext])."dcraw -c -w \"".shellcmd_encode ($path_source)."\" | ".$mgmt_imagepreview[$imagepreview_ext]." - \"".shellcmd_encode ($location_dest.$file_name).".jpg\"";
              }
              // using ImageMagick with ufraw-batch package
              else
              {
                $cmd = $mgmt_imagepreview[$imagepreview_ext]." \"".shellcmd_encode ($path_source)."\" \"".shellcmd_encode ($location_dest.$file_name).".jpg\"";
              }
 
              // asynchronous shell exec
              if (!empty ($exec_in_background)) exec_in_background ($cmd);
              // synchronous shell exec
              else @exec ($cmd." 2>&1", $output, $errorCode);

              // on error
              if ($errorCode)
              {
                $errcode = "20259";
                $error[] = $mgmt_config['today']."|hypercms_media.inc.php|error|".$errcode."|Execution of imagemagick (code:".$errorCode.", command:".str_replace ("|", "->", $cmd).") failed for file '".$file."' \t".implode ("\t", $output); 
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

        // if encrypted
        if (!empty ($temp_raw['result']) && !empty ($temp_raw['crypted']) && !empty ($temp_raw['templocation']) && !empty ($temp_raw['tempfile']))
        {
          $location_source = $temp_raw['templocation'];
          $file = $temp_raw['tempfile'];
        }
        // if restored
        elseif (!empty ($temp_raw['result']) && !empty ($temp_raw['restored']) && !empty ($temp_raw['location']) && !empty ($temp_raw['file']))
        {
          $location_source = $temp_raw['location'];
          $file = $temp_raw['file'];
        }

        // verify local media file
        if (!is_file ($location_source.$file)) 
        {
          // restart session (that has been previously closed for non-blocking procedure)
          if (empty (session_id()) && $session_id != "") createsession();

          return false;
        }
      }

      // reset source path to JPG file of RAW image
      $path_source = $location_source.$file;
    }

    // get file-type
    $filetype_orig = getfiletype ($file_ext);

    // MD5 hash of the original file
    $md5_hash = md5_file ($location_source.$file);

    // get original image width and heigth in pixels
    $temp = getmediasize ($location_source.$file);

    if ($temp != false)
    {
      $imagewidth_orig = $temp['width'];
      $imageheight_orig = $temp['height'];
    }
 
    // reset values if not available
    if (empty ($imagewidth_orig) || empty ($imageheight_orig))
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
      $mgmt_imageoptions['.jpg.jpeg']['thumbnail'] = "-s 220x220 -f jpg";

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
        if (!empty ($file_ext) && substr_count (strtolower ($maxsizepreview_ext).".", $file_ext.".") > 0 && $maxsizepreview > 0)
        {
          if (($filesize_orig / 1024) > $mgmt_maxsizepreview[$maxsizepreview_ext]) $skip = true;
        }
      }
    }
    
    if ($skip == false)
    {
      // ---------------------- if Document file ------------------------
      if (is_document ($file_ext) && ($type == "thumbnail" || $type == "origthumb"))
      {
        $newfile = createdocument ($site, $location_source, $location_dest, $file, "jpg");

        // write media information to container and DB
        // don not overwrite if the 
        if (!empty ($setmediainfo)) $setmedia = rdbms_setmedia ($container_id, $filesize_orig, $filetype_orig, $imagewidth_orig, $imageheight_orig, "", "", "", "", "", $md5_hash);
      }
      // ---------------------- if Audio file ------------------------
      // the extracted thumbnail will be used as it is and don't use the image data for table media
      elseif (is_audio ($file_ext) && ($type == "thumbnail" || $type == "origthumb"))
      {
        // new file name
        $newfile = $file_name.".thumb.jpg";

        $id3_data = id3_getdata ($location_source.$file);

        // if album art image is available (use as thumbnail for the audio file)
        if (!empty ($id3_data['imagedata']))
        {
          // convert album art if not a JPEG and image is too large in size
          if ($id3_data['imagemimetype'] != "image/jpeg" || $id3_data['imagewidth'] > 260 || $id3_data['imageheight'] > 260)
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
              $thumb_width = 260;
              $thumb_height = 260;
        
              if (!empty ($id3_data['imagewidth']) && intval ($id3_data['imagewidth']) > 0 && !empty ($id3_data['imageheight']) && intval ($id3_data['imageheight']) > 0)
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
              $temp_file_2 = convertimage ($site, $location_temp.$temp_file, $location_temp, "jpg", "RGB", "", $thumb_width, $thumb_height, 0, "px", 72, "", false);

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
      elseif ($file_ext == ".indd" && ($type == "thumbnail" || $type == "origthumb"))
      {
        $newfile = createthumbnail_indesign ($site, $location_source, $location_dest, $file);

        // get media information from thumbnail
        if ($newfile != false)
        {
          $converted = true;

          $imagecolor = getimagecolors ($site, $newfile);

          if ($imagewidth_orig < 1 || $imageheight_orig < 1)
          {
            $temp = getmediasize ($location_dest.$newfile);

            if ($temp != false)
            {
              $imagewidth_orig = $temp['width'];
              $imageheight_orig = $temp['height'];
            }
          }

          // write media information to container and DB
          if (!empty ($container_id) && !empty ($setmediainfo))
          {
            $setmedia = rdbms_setmedia ($container_id, $filesize_orig, $filetype_orig, $imagewidth_orig, $imageheight_orig, $imagecolor['red'], $imagecolor['green'], $imagecolor['blue'], $imagecolor['colorkey'], $imagecolor['imagetype'], $md5_hash);
          }
        }
      }

      // -------------- if Image conversion software is provided -----------------
      if (is_array ($mgmt_imagepreview) && sizeof ($mgmt_imagepreview) > 0)
      {
        // redefine type (for images thumbnail and origthumb are the same)
        if ($type == "origthumb") $type = "thumbnail";

        // define format if not set
        if ($format == "") $format_set = "jpg";
        else $format_set = $format;

        reset ($mgmt_imagepreview);

        // supported extensions for image rendering
        foreach ($mgmt_imagepreview as $imagepreview_ext => $imagepreview)
        {
          // check file extension
          if (!empty ($file_ext) && substr_count (strtolower ($imagepreview_ext).".", $file_ext.".") > 0 && trim ($imagepreview) != "")
          {
            reset ($mgmt_imageoptions);
            $i = 1;

            // extensions for certain image rendering options
            foreach ($mgmt_imageoptions as $imageoptions_ext => $imageoptions)
            {
              // if we create a thumbnail we always use the thumbnail configuration from the jpg
              $check1 = ($type == 'thumbnail' && substr_count ($imageoptions_ext.".", ".jpg.") > 0);

              // else we check the format we want to convert to
              $check2 = ($type != 'thumbnail' && substr_count ($imageoptions_ext.".", ".".$format_set.".") > 0);

              // check if the type array is present
              if (is_string ($type) && !empty ($type)) $check3 = array_key_exists ($type, $mgmt_imageoptions[$imageoptions_ext]);
              else $check3 = false;

              // get image rendering options based on given destination format
              if (($check1 || $check2) && $check3)
              {
                // Options:
                // -s ... output size in width x height in pixel (WxH), e.g. -s 1028x768
                // -f ... output format (file extension without dot: jpg, png, gif), e.g. -f png
                // -d ... image density (DPI) for vector graphics and EPS files, common values are 72, 96 dots per inch for screen, while printers typically support 150, 300, 600, or 1200 dots per inch, e.g. -d 300
                // -q ... quality for compressed image formats like JPEG (1 to 100), e.g. -q 95
                // -c ... crop x and y coordinates (XxY), e.g. -c 100x100
                // -g ... gravity (NorthWest, North, NorthEast, West, Center, East, SouthWest, South, SouthEast) for the placement of an image, e.g. -g west
                // -ex ... extent/enlarge image, unfilled areas are set to the background color, to position the image, use offsets in the geometry specification or precede with a gravity setting, -ex 1028x768
                // -bg ... background color, the default background color (if none is specified or found in the image) is white, e.g. -bg black
                // -b ... image brightness from -100 to 100, e.g. -b 10
                // -k .... image contrast from -100 to 100, e.g. -k 5
                // -cs ... color space of image, e.g. RGB, CMYK, gray, e.g. -cs CMYK
                // -rotate ... rotate image in positive degrees, e.g. -rotate 90
                // -fv ... flip image in the vertical direction (no value required)
                // -fh ... flip image in the horizontal direction (no value required)
                // -sh ... sharpen image, e.g. one pixel size sharpen, e.g. -sh 0x1.0
                // -bl ... blur image with a Gaussian or normal distribution using the given radius and sigma value, e.g. -bl 1x0.1
                // -pa ... apply paint effect by replacing each pixel by the most frequent color in a circular neighborhood whose width is specified with radius, e.g. -pa 2
                // -sk ... sketches an image, e.g. -sk 0x20+120
                // -sep ... apply sepia-tone on image from 0 to 99.9%, e.g. -sep 80%
                // -monochrome ... transform image to black and white (no value required)
                // -wm ... watermark image->positioning->geometry, e.g. /logo/image.png->topleft->+30

                // image size (in pixel) definition
                $imageresize = "";

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
                $imageformat = "jpg";

                if (strpos ("_".$mgmt_imageoptions[$imageoptions_ext][$type], "-f ") > 0)
                {
                  $imageformat = strtolower (getoption ($mgmt_imageoptions[$imageoptions_ext][$type], "-f"));
                  if (empty ($imageformat)) $imageformat = "jpg";
                }

                // image rotation
                $imagerotate = "";
                $imagerotation = "";

                if (strpos ("_".$mgmt_imageoptions[$imageoptions_ext][$type], "-rotate ") > 0) 
                {
                  $imagerotation = intval (getoption ($mgmt_imageoptions[$imageoptions_ext][$type], "-rotate"));

                  // ImageMagick rotate parameter
                  $imagerotate = "-rotate ".$imagerotation;

                  // no resize if rotation is used
                  $imageresize = "";
                }

                // image density (DPI)
                $imagedensity = "";

                if (strpos ("_".$mgmt_imageoptions[$imageoptions_ext][$type], "-d ") > 0) 
                {
                  $imagedensity = intval (getoption ($mgmt_imageoptions[$imageoptions_ext][$type], "-d"));

                  if ($imagedensity > 2400) $imagedensity = "-density 2400";
                  elseif ($imagedensity < 72) $imagedensity = "-density 72";
                  else $imagedensity = "-density ".$imagedensity;
                }

                // image quality / compression
                $imagequality = "";

                if (strpos ("_".$mgmt_imageoptions[$imageoptions_ext][$type], "-q ") > 0) 
                {
                  $imagequality = intval (getoption ($mgmt_imageoptions[$imageoptions_ext][$type], "-q"));

                  if ($imagequality > 100) $imagequality = "-quality 100";
                  elseif ($imagequality < 1) $imagequality = "-quality 1";
                  else $imagequality = "-quality ".$imagequality;
                }

                // image brightness
                $imagebrightness = 0;

                if (strpos ("_".$mgmt_imageoptions[$imageoptions_ext][$type], "-b ") > 0) 
                {
                  $imagebrightness = intval (getoption ($mgmt_imageoptions[$imageoptions_ext][$type], "-b"));

                  if ($imagebrightness > 100) $imagebrightness = 100;
                  elseif ($imagebrightness < -100) $imagebrightness = -100;
                }

                // image contrast
                $imagecontrast = 0;

                if (strpos ("_".$mgmt_imageoptions[$imageoptions_ext][$type], "-k ") > 0) 
                {
                  $imagecontrast = intval (getoption ($mgmt_imageoptions[$imageoptions_ext][$type], "-k"));

                  if ($imagecontrast > 100) $imagecontrast = 100;
                  elseif ($imagecontrast < -100) $imagecontrast = -100;
                }

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
                $imagecolorspace = "";

                if (strpos ("_".$mgmt_imageoptions[$imageoptions_ext][$type], "-cs ") > 0) 
                {
                  $imagecolorspace = getoption ($mgmt_imageoptions[$imageoptions_ext][$type], "-cs");

                  // enable alpha blending for colorspace transparent
                  if (strtolower ($imagecolorspace) == "transparent") $add = "-alpha on ";
                  else $add = "";

                  if (!empty ($imagecolorspace)) $imagecolorspace = $add."-colorspace ".shellcmd_encode ($imagecolorspace);
                }

                // set image icc profile
                $iccprofile = "";

                if (strpos ("_".$mgmt_imageoptions[$imageoptions_ext][$type], "-p ") > 0) 
                {
                  $iccprofile = getoption ($mgmt_imageoptions[$imageoptions_ext][$type], "-p");

                  if (!empty ($iccprofile)) $iccprofile = "-profile ".shellcmd_encode ($iccprofile);
                }

                // set flip
                $imageflipv = "";

                if (strpos ("_".$mgmt_imageoptions[$imageoptions_ext][$type], "-fv ") > 0) 
                {
                  $imageflipv = "-flop";
                }

                // set flop
                $imagefliph = "";

                if (strpos ("_".$mgmt_imageoptions[$imageoptions_ext][$type], "-fh ") > 0) 
                {
                  $imagefliph = "-flip";
                }

                // Combine flip and flop into one
                $imageflip = $imageflipv." ".$imagefliph;

                // set gravity
                $gravity = "";

                if (strpos ("_".$mgmt_imageoptions[$imageoptions_ext][$type], "-g ") > 0) 
                {
                  $gravity = getoption ($mgmt_imageoptions[$imageoptions_ext][$type], "-g");

                  if (!empty ($gravity)) $gravity = "-gravity ".shellcmd_encode ($gravity);
                }

                // set extent
                $extent = "";

                if (strpos ("_".$mgmt_imageoptions[$imageoptions_ext][$type], "-ex ") > 0) 
                {
                  $extent = getoption ($mgmt_imageoptions[$imageoptions_ext][$type], "-ex");

                  if (!empty ($extent)) $extent = "-extent ".shellcmd_encode ($extent); 
                }
               
                // set background
                $background = "";

                if (strpos ("_".$mgmt_imageoptions[$imageoptions_ext][$type], "-bg ") > 0) 
                {
                  $background = getoption ($mgmt_imageoptions[$imageoptions_ext][$type], "-bg");

                  if (!empty ($background)) $background = "-background ".shellcmd_encode ($background); 
                }

                // set sepia
                $sepia = "";

                if (strpos ("_".$mgmt_imageoptions[$imageoptions_ext][$type], "-sep ") > 0) 
                {
                  $sepia = getoption ($mgmt_imageoptions[$imageoptions_ext][$type], "-sep");

                  if (!empty ($sepia)) $sepia = "-sepia-tone ".shellcmd_encode ($sepia);
                }

                // set sharpen
                $sharpen = "";

                if (strpos ("_".$mgmt_imageoptions[$imageoptions_ext][$type], "-sh ") > 0) 
                {
                  $sharpen = getoption ($mgmt_imageoptions[$imageoptions_ext][$type], "-sh");

                  if (!empty ($sharpen)) $sharpen = "-sharpen ".shellcmd_encode ($sharpen);
                }

                // set blur
                $blur = "";

                if (strpos ("_".$mgmt_imageoptions[$imageoptions_ext][$type], "-bl ") > 0) 
                {
                  $blur = getoption ($mgmt_imageoptions[$imageoptions_ext][$type], "-bl");

                  if (!empty ($blur)) $blur = "-blur ".shellcmd_encode ($blur);
                }

                // set sketch
                $sketch = "";

                if (strpos ("_".$mgmt_imageoptions[$imageoptions_ext][$type], "-sk ") > 0) 
                {
                  $sketch = getoption ($mgmt_imageoptions[$imageoptions_ext][$type], "-sk");

                  if (!empty ($sketch)) $sketch = "-sketch ".shellcmd_encode ($sketch);
                }

                // set paint
                $paint = "";

                if (strpos ("_".$mgmt_imageoptions[$imageoptions_ext][$type], "-pa ") > 0) 
                {
                  $paint = getoption ($mgmt_imageoptions[$imageoptions_ext][$type], "-pa");

                  if (!empty ($paint)) $paint = "-paint ".shellcmd_encode ($paint);
                }

                // watermarking
                $watermark = "";

                // set watermark options if defined in publication settings and not already defined
                if (!empty ($mgmt_config[$site]['watermark_image']) && strpos ("_".$mgmt_imageoptions[$imageoptions_ext][$type], "-wm ") == 0)
                {
                  $mgmt_imageoptions[$imageoptions_ext][$type] .= " ".$mgmt_config[$site]['watermark_image'];
                }

                if (strpos ("_".$mgmt_imageoptions[$imageoptions_ext][$type], "-wm ") > 0) 
                {
                  $watermarking = strtolower (getoption ($mgmt_imageoptions[$imageoptions_ext][$type], "-wm"));

                  if (!empty ($watermarking) && $watermarking != "0" && $watermarking != "none" && strtolower ($watermarking) != "no" && strtolower ($watermarking) != "false")
                  {
                    // parameters:
                    // -watermark ... reference to watermark image
                    // -gravity ... position of the watermark image
                    // -geometry ... Can be used to modify the size of the watermark being passed in, and also the positioning of the watermark (relative to the gravity placement)
                    //               It is specified in the form width x height +/- horizontal offset +/- vertical offset (<width>x<height>{+-}<xoffset>{+-}<yoffset>)
                    // -composite ... parameter, which tells ImageMagick to add the watermark image we ve just specified to the image

                    list ($wmimage, $wmgravity, $wmgeometry) = explode ("->", $watermarking);

                    if (!empty ($wmgeometry)) $wmgeometry = intval ($wmgeometry);
                    else $wmgeometry = 0;
 
                    if (strtolower (trim ($wmgravity)) == "topleft")
                    {
                      $wmgravity = "northwest";
                      $wmgeometry = "+".$wmgeometry."+".$wmgeometry;
                    }
                    elseif (strtolower (trim ($wmgravity)) == "topright")
                    {
                      $wmgravity = "northeast";
                      $wmgeometry = "-".$wmgeometry."+".$wmgeometry;
                    }
                    elseif (strtolower (trim ($wmgravity)) == "bottomleft")
                    {
                      $wmgravity = "southwest";
                      $wmgeometry = "+".$wmgeometry."-".$wmgeometry;
                    }
                    elseif (strtolower (trim ($wmgravity)) == "bottomright")
                    {
                      $wmgravity = "southeast";
                      $wmgeometry = "-".$wmgeometry."-".$wmgeometry;
                    }
                    elseif (strtolower (trim ($wmgravity)) == "center")
                    {
                      $wmgravity = "center";
                      $wmgeometry = "";
                    }
                    // not valid
                    else
                    {
                      $wmgravity = "";
                      $wmgeometry = "";
                    }

                    if ($wmimage != "" && $wmgravity != "")
                    {
                      $watermark = "-compose multiply -gravity ".$wmgravity.($wmgeometry != "" ? " -geometry ".shellcmd_encode (trim ($wmgeometry)) : "")." -background none \"".shellcmd_encode (trim ($wmimage))."\"";
                    }
                  }
                }

                // -------------------- convert image using ImageMagick ----------------------
                if (!empty ($mgmt_imagepreview[$imagepreview_ext]) && $mgmt_imagepreview[$imagepreview_ext] != "GD")
                {
                  $buffer_file = $path_source;

                  // delete thumbnail
                  if ($type == "thumbnail" && is_file ($location_dest.$file_name.".thumb.jpg"))
                  {
                    unlink ($location_dest.$file_name.".thumb.jpg");
                  }
                  // copy original image before conversion to restore it if an error occured
                  elseif ($type != "thumbnail" && is_file ($path_source))
                  {
                    // create temp file
                    $buffer_file = $location_temp.$file_name.".temp".strrchr ($file, ".");;
                    copy ($path_source, $buffer_file);

                    // delete the old file if we overwrite the original file
                    if ($type == "original")
                    {
                      unlink ($path_source);
                    }
                  }

                  // set background properties for JPEG (thumbnail images, annotation images, preview images) 
                  if ($imageformat == "jpg") $background = "-background white -alpha remove";

                  // ---------------------- CASE: document-based formats (if converted to PDF), encapsulated post script (EPS) and vector graphics ----------------------
                  if (strpos ("_.pdf".$hcms_ext['vectorimage'].".", $file_ext.".") > 0)
                  {
                    // set size for thumbnails
                    if ($type == "thumbnail" && !empty ($imagewidth_orig) && !empty ($imageheight_orig))
                    {
                      // reduce thumbnail size if original image is smaller then the defined thumbnail image size
                      if ($imagewidth_orig > 0 && $imagewidth_orig < $imagewidth && $imageheight_orig > 0 && $imageheight_orig < $imageheight)
                      {
                        $imageresize = "-resize ".round ($imagewidth_orig, 0)."x".round ($imageheight_orig, 0);

                        // set the image density in oprder to keep the details in small graphics
                        $imagedensity = "-density 100";
                      }
                      else
                      {
                        $imageresize = "-resize ".$imagewidth."x".$imageheight;

                        // set size for for vector graphics like SVG in order to be rendered correctly (no density required in this case)
                        $imagedensity = "-size ".$imagewidth_orig."x".$imageheight_orig;
                      }
                    }
                    elseif (empty ($imagedensity))
                    {
                      // density for vector graphics (300 dpi for SVG only, do not use for EPS)
                      if ($file_ext == ".svg") $imagedensity = "-density 300";
                      elseif ($file_ext == ".pdf")  $imagedensity = "-density 150";
                    }

                    if ($type == "thumbnail")
                    {
                      $newfile = $file_name.".thumb.jpg";

                      $cmd = $mgmt_imagepreview[$imagepreview_ext]." ".$imagedensity." ".$iccprofile." ".$imagecolorspace." \"".shellcmd_encode ($path_source)."[0]\" ".$imageresize." ".$background." ".$gravity." ".$extent." ".$imagequality." \"".shellcmd_encode ($location_dest.$newfile)."\"";
                    }
                    elseif ($type == "annotation" && is_dir ($mgmt_config['abs_path_cms']."workflow/"))
                    {
                      // correct file name if thumbnail file is used as source
                      if (substr ($file_name, -6) == ".thumb") $newfile = substr ($file_name, 0, -6).".annotation";
                      else $newfile = $file_name.".annotation";

                      // remove old annotation image files
                      if ((is_file ($location_dest.$newfile."-0.jpg") || is_cloudobject ($location_dest.$newfile."-0.jpg")))
                      { 
                        for ($p=0; $p<=10000; $p++)
                        {
                          $temp = $newfile."-".$p.".jpg";
                          // local media file
                          $delete_1 = deletefile ($location_dest, $temp, 0);
                          // cloud storage
                          if (function_exists ("deletecloudobject")) $delete_2 = deletecloudobject ($site, $location_dest, $temp, $user);
                          // remote client
                          remoteclient ("delete", "abs_path_media", $site, $location_dest, "", $temp, "");
                          // break if no more page is available
                          if (empty ($delete_1) && empty ($delete_2)) break;
                        }
                      }

                      // render all pages from document as images for annotations
                      $cmd = $mgmt_imagepreview[$imagepreview_ext]." ".$imagedensity." ".$iccprofile." ".$imagecolorspace." \"".shellcmd_encode ($buffer_file)."\" ".$imageresize." ".$background." ".$gravity." ".$extent." ".$imagequality." \"".shellcmd_encode ($location_dest.$newfile."-%0d.jpg")."\"";
                    }
                    elseif ($type == "temp")
                    {
                      // correct file name if thumbnail file is used as source
                      if (substr ($file_name, -6) == ".thumb") $newfile = substr ($file_name, 0, -6).".temp";
                      else $newfile = $file_name.".temp";

                      // remove old temp image files
                      if (is_file ($location_dest.$newfile."-0.".$format))
                      { 
                        for ($p=0; $p<=10000; $p++)
                        {
                          $temp = $newfile."-".$p.".".$format;
                          deletefile ($location_dest, $temp, 0);
                        }
                      }

                      // render all pages from document as images
                      $cmd = $mgmt_imagepreview[$imagepreview_ext]." ".$imagedensity." ".$iccprofile." ".$imagecolorspace." \"".shellcmd_encode ($buffer_file)."\" ".$imageresize." ".$background." ".$gravity." ".$extent." ".$imagequality." \"".shellcmd_encode ($location_dest.$newfile."-%0d.".$format)."\"";
                    }
                    else
                    {
                      if ($type == "original") $newfile = $file_name.".".$imageformat;
                      else $newfile = $file_name.".".$type.".".$imageformat;

                      // use geometry instead of resize for EPS files
                      if ($file_ext == ".eps") $imageresize = $imagegeometry;

                      $cmd = $mgmt_imagepreview[$imagepreview_ext]." -background none ".$imagedensity." ".$iccprofile." ".$imagecolorspace." \"".shellcmd_encode ($buffer_file)."[0]\" ".$imagerotate." ".$imageBrightnessContrast." ".$imageresize." ".$background." ".$imageflip." ".$sepia." ".$sharpen." ".$blur." ".$sketch." ".$paint." ".$gravity." ".$extent." ".$imagequality." \"".shellcmd_encode ($location_dest.$newfile)."\"";
                    }

                    // asynchronous shell exec
                    if (!empty ($exec_in_background)) exec_in_background ($cmd);
                    // synchronous shell exec
                    else @exec ($cmd." 2>&1", $output, $errorCode);

                    // on error
                    if ($errorCode)
                    {
                      $errcode = "20231";
                      $error[] = $mgmt_config['today']."|hypercms_media.inc.php|error|".$errcode."|Execution of imagemagick (code:".$errorCode.", command:".$cmd.") failed for file '".$file."' \t".implode ("\t", $output);
                    }
                    // on success
                    else $converted = true;
                  }
                  // ---------------------- CASE: Adobe Photoshop / Adobe Illustrator: layered files ----------------------
                  elseif ($file_ext == ".ai" || $file_ext == ".psd")
                  {
                    if ($type == "thumbnail")
                    {
                      $newfile = $file_name.".thumb.jpg";

                      // reduce thumbnail size if original image is smaller then the defined thumbnail image size
                      if ($imagewidth_orig > 0 && $imagewidth_orig < $imagewidth && $imageheight_orig > 0 && $imageheight_orig < $imageheight)
                      {
                        $imageresize = "-resize ".round ($imagewidth_orig, 0)."x".round ($imageheight_orig, 0);
                      }
                      else
                      {
                        $imageresize = "-resize ".$imagewidth."x".$imageheight;
                      }

                      $cmd = $mgmt_imagepreview[$imagepreview_ext]." ".$iccprofile." ".$imagecolorspace." \"".shellcmd_encode ($path_source)."[0]\" -flatten ".$imageresize." ".$imagequality." \"".shellcmd_encode ($location_dest.$newfile)."\"";
                    }
                    else
                    {
                      if ($type == "original") $newfile = $file_name.".".$imageformat;
                      else $newfile = $file_name.".".$type.".".$imageformat;

                      if ($crop_mode)
                      {
                        $cmd = $mgmt_imagepreview[$imagepreview_ext]." ".$imagedensity." ".$iccprofile." ".$imagecolorspace." \"".shellcmd_encode ($buffer_file)."[0]\" -flatten -crop ".$imagewidth."x".$imageheight."+".$offsetX."+".$offsetY." ".$imageBrightnessContrast." ".$imagequality." \"".shellcmd_encode ($location_dest.$newfile)."\"";
                      }
                      else
                      {
                        // split layers into files if type is view (used for image editing)
                        // if (substr_count ($type, "view.") > 0)
                        // {
                        //   $cmd = $mgmt_imagepreview[$imagepreview_ext]." ".$imagedensity." ".$iccprofile." ".$imagecolorspace." \"".shellcmd_encode ($buffer_file)."\" ".$imageresize." ".$imagerotate." ".$imageBrightnessContrast." ".$imageflip." ".$sepia." ".$sharpen." ".$blur." ".$sketch." ".$paint." ".$imagequality." \"".shellcmd_encode ($location_dest.$newfile)."\"";
                        //   @exec ($cmd, $output, $errorCode);
                        // }
  
                        $cmd = $mgmt_imagepreview[$imagepreview_ext]." ".$imagedensity." ".$iccprofile." ".$imagecolorspace." \"".shellcmd_encode ($buffer_file)."[0]\" -flatten ".$imageresize." ".$imagerotate." ".$imageBrightnessContrast." ".$imageflip." ".$sepia." ".$sharpen." ".$blur." ".$sketch." ".$paint." ".$gravity." ".$extent." ".$imagequality." \"".shellcmd_encode ($location_dest.$newfile)."\"";
                      }
                    }

                    // asynchronous shell exec
                    if (!empty ($exec_in_background)) exec_in_background ($cmd);
                    // synchronous shell exec
                    else @exec ($cmd." 2>&1", $output, $errorCode);

                    // on error
                    if ($errorCode || !is_file ($location_dest.$newfile))
                    {
                      $errcode = "20232";
                      $error[] = $mgmt_config['today']."|hypercms_media.inc.php|error|".$errcode."|Execution of imagemagick (code:".$errorCode.", command:".$cmd.") failed for file '".$file."' \t".implode ("\t", $output); 
                    }
                    // on success
                    else $converted = true;
                  }
                  // ---------------------- CASE: Standard images ----------------------
                  else
                  {
                    // only for RAW image
                    if (is_rawimage ($file_ext))
                    {
                      $imagecolorspace = "";
                    }

                    // auto rotate the image
                    $autorotate = "-auto-orient";

                    if ($type == "thumbnail" || ($type == "annotation" && is_dir ($mgmt_config['abs_path_cms']."workflow/")))
                    {
                      if ($type == "annotation") $newfile = $file_name.".annotation.jpg";
                      else $newfile = $file_name.".thumb.jpg";

                      // reduce thumbnail size if original image is smaller then the defined thumbnail image size
                      if ($imagewidth_orig > 0 && $imagewidth_orig < $imagewidth && $imageheight_orig > 0 && $imageheight_orig < $imageheight)
                      {
                        $imageresize = "-resize ".round ($imagewidth_orig, 0)."x".round ($imageheight_orig, 0);
                      }

                      $cmd = $mgmt_imagepreview[$imagepreview_ext]." ".$iccprofile." ".$imagecolorspace." ".$autorotate." \"".shellcmd_encode ($path_source)."[0]\" -size ".$imagewidth."x".$imageheight." ".$imageresize." ".$background." ".$imagequality." \"".shellcmd_encode ($location_dest.$newfile)."\"";
                    }
                    else
                    {
                      if ($type == "original") $newfile = $file_name.".".$imageformat;
                      else $newfile = $file_name.".".$type.".".$imageformat;
    
                      if ($crop_mode)
                      {
                        $cmd = $mgmt_imagepreview[$imagepreview_ext]." ".$imagedensity." ".$iccprofile." ".$imagecolorspace." ".$autorotate." \"".shellcmd_encode ($buffer_file)."[0]\" -crop ".$imagewidth."x".$imageheight."+".$offsetX."+".$offsetY." ".$imagerotate." ".$imageBrightnessContrast." ".$imageflip." ".$sepia." ".$sharpen." ".$blur." ".$sketch." ".$paint." ".$background." ".$gravity." ".$extent." ".$imagequality." \"".shellcmd_encode ($location_dest.$newfile)."\"";
                      }
                      else
                      {
                        $cmd = $mgmt_imagepreview[$imagepreview_ext]." ".$imagedensity." ".$iccprofile." ".$imagecolorspace." ".$autorotate." \"".shellcmd_encode ($buffer_file)."[0]\" -size ".$imagewidth."x".$imageheight." ".$imageresize." ".$imagerotate." ".$imageBrightnessContrast." ".$imageflip." ".$sepia." ".$sharpen." ".$blur." ".$sketch." ".$paint." ".$imagecolorspace." ".$background." ".$gravity." ".$extent." ".$imagequality." \"".shellcmd_encode ($location_dest.$newfile)."\"";
                      }
                    }

                    // execute and redirect stderr (2) to stdout (1)
                    @exec ($cmd." 2>&1", $output, $errorCode);

                    // on error
                    if ($errorCode || !is_file ($location_dest.$newfile))
                    {
                      $errcode = "20234";
                      $error[] = $mgmt_config['today']."|hypercms_media.inc.php|error|".$errcode."|Execution of imagemagick (code:".$errorCode.", command:".$cmd.") failed for file '".$file."' \tE".implode ("\t", $output); 
                    }
                    // on success
                    else $converted = true;
                  }

                  // ------------- WATERMARK AND PROCESS IMAGE ON SUCCESS: if new file is larger than 5 bytes --------------
                  if ($converted == true && !empty ($newfile) && is_file ($location_dest.$newfile) && filesize ($location_dest.$newfile) > 5)
                  {
                    // watermark image using composite command (using a temporaray intermediate image file in order to restore image if watermarking failed)
                    if ($type != "thumbnail" && $type != "original" && $type != "annotation" && substr_count ($type, "preview.") == 0 && !empty ($watermark))
                    {
                      $cmd = getlocation ($mgmt_imagepreview[$imagepreview_ext])."composite ".$watermark." \"".shellcmd_encode ($location_dest.$newfile)."\" \"".shellcmd_encode ($location_temp."watermark.".$newfile)."\"";

                      // asynchronous shell exec
                      if (!empty ($exec_in_background)) exec_in_background ($cmd);
                      // synchronous shell exec
                      else @exec ($cmd." 2>&1", $output, $errorCode);

                      // on error
                      if ($errorCode)
                      {
                        // delete temporary watermarked image file
                        if (is_file ($location_temp."watermark.".$newfile)) unlink ($location_temp."watermark.".$newfile);

                        $errcode = "20262";
                        $error[] = $mgmt_config['today']."|hypercms_media.inc.php|error|".$errcode."|Execution of imagemagick (code:".$errorCode.", command:".$cmd.") failed in watermark file '".$newfile."' \t".implode ("\t", $output);
                      }
                      // on success
                      elseif (is_file ($location_temp."watermark.".$newfile))
                      {
                        // overwrite source file with watermarked image file
                        rename ($location_temp."watermark.".$newfile, $location_dest.$newfile);
                      }
                    }

                    // get media information from thumbnail
                    if ($type == "thumbnail" && !empty ($container_id) && !empty ($setmediainfo))
                    {
                      $imagecolor = getimagecolors ($site, $newfile);

                      if ($imagewidth_orig < 1 || $imageheight_orig < 1)
                      {
                        $temp = getmediasize ($location_dest.$newfile);
            
                        if ($temp != false)
                        {
                          $imagewidth_orig = $temp['width'];
                          $imageheight_orig = $temp['height'];
                        }
                      }

                      // write media information to container and DB
                      $setmedia = rdbms_setmedia ($container_id, $filesize_orig, $filetype_orig, $imagewidth_orig, $imageheight_orig, $imagecolor['red'], $imagecolor['green'], $imagecolor['blue'], $imagecolor['colorkey'], $imagecolor['imagetype'], $md5_hash);
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
                      copy ($buffer_file, $path_source);
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
                elseif ($imagewidth_orig > 0 && $imageheight_orig > 0 && (empty ($mgmt_imagepreview[$imagepreview_ext]) || $mgmt_imagepreview[$imagepreview_ext] == "GD") && in_array (strtolower($file_ext), $GD_allowed_ext) && function_exists ("imagecreatefromjpeg") && function_exists ("imagecreatefrompng") && function_exists ("imagecreatefromgif"))
                {
                  // initialize
                  $temp_file = $path_source;

                  // auto rotate image based on the orientation
                  // use EXIF image orientation in order to correct width and height
                  // orientation numbers used by EXIF:
                  // use EXIFTOOL to set the orinetation: -orientation#=6
                  // 1 = Horizontal (normal)
                  // 2 = Mirror horizontal
                  // 3 = Rotate 180
                  // 4 = Mirror vertical
                  // 5 = Mirror horizontal and rotate 270 CW
                  // 6 = Rotate 90 CW
                  // 7 = Mirror horizontal and rotate 90 CW
                  // 8 = Rotate 270 CW
                  $exif = @exif_read_data ($path_source);

                  if (!empty ($exif['Orientation']))
                  {
                    $orientation = $exif['Orientation'];

                    if ($orientation != 0)
                    {
                      // create temp file
                      $temp_file = $location_temp.$file_name.".temp".strrchr ($file, ".");;
                      copy ($path_source, $temp_file);

                      switch ($orientation)
                      {
                        // 180 rotate left
                        case 3:
                          $result = rotateimage ($site, $temp_file, 180, $format_set);
                          break;

                        // 90 rotate right
                        case 6:
                          $result = rotateimage ($site, $temp_file, 90, $format_set);
                          break;

                        // 90 rotate left
                        case 8:
                          $result = rotateimage ($site, $temp_file, 270, $format_set);
                          break;
                      }
                    }
                  }

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

                  // create new image from source file
                  if ($file_ext == ".jpg" || $file_ext == ".jpeg") $imgsource = @imagecreatefromjpeg ($temp_file);
                  elseif ($file_ext == ".png") $imgsource = @imagecreatefrompng ($temp_file);
                  elseif ($file_ext == ".gif") $imgsource = @imagecreatefromgif ($temp_file);
                  else
                  {
                    // restart session (that has been previously closed for non-blocking procedure)
                    if (empty (session_id()) && $session_id != "") createsession();

                    return false;
                  }

                  // crop image
                  if ($crop_mode)
                  {
                    // create new image resource
                    $imgresized = imagecreatetruecolor ($imagewidth, $imageheight);
                    @imagealphablending ($imgresized, false);
                    @imagesavealpha ($imgresized, true);

                    @imagecopyresampled ($imgresized, $imgsource, 0, 0, $offsetX, $offsetY, $imagewidth, $imageheight, $imagewidth, $imageheight);
                  }
                  // resize image
                  else
                  {
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

                    // create new image resource
                    $imgresized = imagecreatetruecolor  ($resizedwidth, $resizedheight);
                    @imagealphablending ($imgresized, false);
                    @imagesavealpha ($imgresized, true);

                    @ImageCopyResized ($imgresized, $imgsource, 0, 0, 0, 0, $resizedwidth, $resizedheight, $imagewidth_orig, $imageheight_orig);
                  }

                  // create image in defined file format
                  if ($imageformat == "jpg" && function_exists ("imagejpeg"))
                  {
                    if ($type == "thumbnail")
                    {
                      $newfile = $file_name.".thumb.jpg";
                      $result = @imagejpeg ($imgresized, $location_dest.$newfile, 95);
                    }
                    else
                    {
                      if ($type == "original") $newfile = $file_name.".jpg";
                      else $newfile = $file_name.".".$type.".jpg";

                      $result = @imagejpeg ($imgresized, $location_dest.$newfile, 95);
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

                    if ($type == "thumbnail" || $type == "origthumb" || $type == "original")
                    {
                      // save in cloud storage
                      if (function_exists ("savecloudobject")) savecloudobject ($site, $location_dest, $newfile, $user);

                      // remote client
                      remoteclient ("save", "abs_path_media", $site, $location_dest, "", $newfile, "");
                    }

                    // get media information from thumbnail
                    if ($type == "thumbnail" && !empty ($container_id) && !empty ($setmediainfo))
                    {
                      $imagecolor = getimagecolors ($site, $newfile);

                      if ($imagewidth_orig < 1 || $imageheight_orig < 1)
                      {
                        $temp = getmediasize ($location_dest.$newfile);
            
                        if ($temp != false)
                        {
                          $imagewidth_orig = $temp['width'];
                          $imageheight_orig = $temp['height'];
                        }
                      }

                      // write media information to container and DB
                      $setmedia = rdbms_setmedia ($container_id, $filesize_orig, $filetype_orig, $imagewidth_orig, $imageheight_orig, $imagecolor['red'], $imagecolor['green'], $imagecolor['blue'], $imagecolor['colorkey'], $imagecolor['imagetype'], $md5_hash);
                    }
                  }
                }

                // create new thumbnail (new preview of original image)
                if (!empty ($converted) && $type == "original")
                {
                  createmedia ($site, $location_dest, $location_dest, $newfile, "jpg", "thumbnail", true, true);
                }
              } 
            }
          }

          // remove existing thumbnail if image conversion is not supported
          if (empty ($converted) && $type == "original" && is_file ($location_dest.$file_name.".thumb.jpg"))
          {
            unlink ($location_dest.$file_name.".thumb.jpg");
          }
        }
      }

      // --------------- if media conversion software is given ----------------

      // do not convert MP3 files to preview MP3 files
      if (is_array ($mgmt_mediapreview) && sizeof ($mgmt_mediapreview) > 0 && ($file_ext != ".mp3" || ($file_ext == ".mp3" && $type != "origthumb" && $type != "thumbnail")))
      {
        // convert the media file with FFMPEG
        // Audio Options:
        // -ac ... number of audio channels
        // -an ... disable audio
        // -ar ... audio sampling frequency (default = 44100 Hz)
        // -b:a ... audio bitrate (default = 64k)
        // -c:a ... audio codec (e.g. aac, libmp3lame, libvorbis)
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
        // -wm .... watermark image and watermark positioning (PNG-file-reference->positioning [topleft, topright, bottomleft, bottomright, center] e.g. image.png->topleft)
        // -rotate ... rotate video by degrees
        // -fv ... flip video in vertical direction (no value required)
        // -fh ... flop video in horizontal direction (no value required)

        // define default option for support of versions before 5.3.4
        // note: audio codec could be "mp3" or in newer ffmpeg versions "libmp3lame"!
        if (empty ($mgmt_mediaoptions['thumbnail-video'])) $mgmt_mediaoptions_video = "-b:v 768k -s:v 576x432 -f mp4 -c:a aac -b:a 64k -ac 2 -c:v libx264 -mbd 2 -flags +loop+mv4 -cmp 2 -subcmp 2";
        else $mgmt_mediaoptions_video = $mgmt_mediaoptions['thumbnail-video'];

        if (empty ($mgmt_mediaoptions['thumbnail-audio'])) $mgmt_mediaoptions_audio = "-f mp3 -c:a libmp3lame -b:a 64k";
        else $mgmt_mediaoptions_audio = $mgmt_mediaoptions['thumbnail-audio'];

        // Default value for auto rotate video if a rotation has been detected (true) or leave video in it's original state (false)
        if (!isset ($mgmt_mediaoptions['autorotate-video'])) $mgmt_mediaoptions['autorotate-video'] = true;
        $mgmt_mediaoptions_autorotate = $mgmt_mediaoptions['autorotate-video'];

        // noautoroate option for input video file is only supported by later FFMPEG versions
        // since the auto rotation is also taking care by the system FFMPEG should not autorotate the video
        if (is_video ($file) && !empty ($mgmt_mediaoptions['autorotate-video'])) $noautorotate = "-noautorotate";
        else $noautorotate = "";

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
            if (empty ($mgmt_mediaoptions['thumbnail-audio']) || strpos ("_".$mgmt_mediaoptions['thumbnail-audio'], "-f ") < 1)
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
            if (empty ($mgmt_mediaoptions['thumbnail-video']) || strpos ("_".$mgmt_mediaoptions['thumbnail-video'], "-f ") < 1)
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
        // define target format if type is "original" (overwrite original file with same format)
        elseif ($format == "" || $type == "original")
        {
          $format_set = strtolower (substr ($file_ext, 1));
        }
        // use provided target format
        elseif ($format != "")
        {
          $format_set = strtolower ($format);
        }

        reset ($mgmt_mediapreview);

        // supported extensions for media rendering
        foreach ($mgmt_mediapreview as $mediapreview_ext => $mediapreview)
        {
          // check file extension
          if (!empty ($file_ext) && substr_count (strtolower ($mediapreview_ext).".", $file_ext.".") > 0 && !empty ($mediapreview))
          {
            reset ($mgmt_mediaoptions);

            // extensions for certain media rendering options
            foreach ($mgmt_mediaoptions as $mediaoptions_ext => $mediaoptions)
            {
              // get media rendering options based on given destination format (skip default setting extensions)
              if ($mediaoptions_ext != "thumbnail-video" && $mediaoptions_ext != "thumbnail-audio" && $mediaoptions_ext != "segments" && substr_count (strtolower ($mediaoptions_ext).".", ".".$format_set.".") > 0)
              {
                // original video info
                $videoinfo = getvideoinfo ($location_source.$file);

                // media format (media file extension) definition
                if (strpos ("_".$mgmt_mediaoptions[$mediaoptions_ext], "-f ") > 0)
                {
                  $videoformat = strtolower (getoption ($mgmt_mediaoptions[$mediaoptions_ext], "-f"));

                  if ($videoformat == "" || $videoformat == false) $videoformat = $format_set; 

                  $mgmt_mediaoptions[$mediaoptions_ext] = str_replace ("-f ".$videoformat, "-f ".shellcmd_encode (trim($videoformat)), $mgmt_mediaoptions[$mediaoptions_ext]);
                }
 
                // video filters
                $vfilter = array();

                // video size
                if (is_video (".hcms.".$format_set) && (strpos ("_".$mgmt_mediaoptions[$mediaoptions_ext], "-s:v ") > 0 || (!empty ($videoinfo['width']) && !empty ($videoinfo['height']))))
                {
                  // get video size defined by media option 
                  $mediasize = getoption ($mgmt_mediaoptions[$mediaoptions_ext], "-s:v");

                  if (!empty ($mediasize) && strpos ($mediasize, "x") > 0)
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
                  }
                  // keep original video size
                  else
                  { 
                    // set the video size
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

                  // libx264 requires width/height to be divisible by 2 when using the standard yuv420p pixel format
                  if (intval ($mediawidth) != 0 && intval ($mediaheight) != 0)
                  {
                    $vfilter[] = "scale=trunc(".intval($mediawidth)."/2)*2:trunc(".intval($mediaheight)."/2)*2";
                  }

                  // remove from options string since it will be added later as a video filter
                  if (!empty ($mediasize)) $mgmt_mediaoptions[$mediaoptions_ext] = str_replace ("-s:v ".$mediasize, "", $mgmt_mediaoptions[$mediaoptions_ext]);
                }

                // sharpness
                if (is_video (".hcms.".$format_set) && strpos ("_".$mgmt_mediaoptions[$mediaoptions_ext], "-sh ") > 0)
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
                if (is_video (".hcms.".$format_set) && strpos ("_".$mgmt_mediaoptions[$mediaoptions_ext], "-rotate ") > 0)
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
                // rotate original video if video has rotate metadata other than zero
                elseif (is_video (".hcms.".$format_set) && !empty ($mgmt_mediaoptions_autorotate) && !empty ($videoinfo['rotate']) && $videoinfo['rotate'] != "0")
                {
                  // usage: transpose=1
                  // for the transpose parameter you can pass:
                  // 0 = 90CounterCLockwise and Vertical Flip (default)
                  // 1 = 90Clockwise
                  // 2 = 90CounterClockwise
                  // 3 = 90Clockwise and Vertical Flip
                  if ($videoinfo['rotate'] == "90") $vfilter[] = "transpose=1";
                  elseif ($videoinfo['rotate'] == "180") $vfilter[] = "hflip,vflip";
                  elseif ($videoinfo['rotate'] == "-90") $vfilter[] = "transpose=2";
                }

                // flip vertically (using video filters)
                if (is_video (".hcms.".$format_set) && strpos ("_".$mgmt_mediaoptions[$mediaoptions_ext], "-fv ") > 0)
                {
                  // usage: hlfip (means horizontal direction = vertical flip)
                  $vfilter[] = "hflip";

                  // remove from options string since it will be added later as a video filter
                  $mgmt_mediaoptions[$mediaoptions_ext] = str_replace ("-fv ", "", $mgmt_mediaoptions[$mediaoptions_ext]);
                }

                // flip horizontally (using video filters)
                if (is_video (".hcms.".$format_set) && strpos ("_".$mgmt_mediaoptions[$mediaoptions_ext], "-fh ") > 0)
                {
                  // usage: vlfip (means vertical direction = horizontal flip)
                  $vfilter[] = "vflip";

                  // remove from options string since it will be added later as a video filter
                  $mgmt_mediaoptions[$mediaoptions_ext] = str_replace ("-fh ", "", $mgmt_mediaoptions[$mediaoptions_ext]);
                }

                // gamma, brigntness, contrast, saturation, red-, green-, blue-gamm (using video filters)
                if (is_video (".hcms.".$format_set) && strpos ("_".$mgmt_mediaoptions[$mediaoptions_ext], "-gbcs ") > 0)
                {
                  // get sharpness defined by media option 
                  $gbcs = getoption ($mgmt_mediaoptions[$mediaoptions_ext], "-gbcs");

                  // Values for EQ2 filter (has been changed to EQ)
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

                  $vfilter[] = "eq=gamma=".floatval($gamma).":contrast=".floatval($contrast).":brightness=".floatval($brightness).":saturation=".floatval($saturation);

                  // remove from options string since it will be added later as a video filter
                  $mgmt_mediaoptions[$mediaoptions_ext] = str_replace ("-gbcs ".$gbcs, "", $mgmt_mediaoptions[$mediaoptions_ext]);
                }

                // join filter options an add to options string
                if (is_video (".hcms.".$format_set) && sizeof ($vfilter) > 0)
                {
                  $mgmt_mediaoptions[$mediaoptions_ext] = " -vf \"".implode (", ", $vfilter)."\" ".$mgmt_mediaoptions[$mediaoptions_ext];
                }

                // split media file if requested
                if (!empty ($mgmt_mediaoptions['segments']) && !empty ($mgmt_mediapreview[$mediapreview_ext]))
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
                          // slice video
                          $cmd = $mgmt_mediapreview[$mediapreview_ext]." ".$noautorotate." -i \"".shellcmd_encode ($path_source)."\" -ss ".$segment_starttime." -t ".$segment_duration." -q:v 4 -f mpegts -target ntsc-vcd \"".$location_temp.shellcmd_encode ($file_name)."-".$i.".ts\"";

                          // execute and redirect stderr (2) to stdout (1)
                          @exec ($cmd." 2>&1", $output, $errorCode);

                          // on error
                          if ($errorCode || !is_file ($location_temp.shellcmd_encode ($file_name)."-1.ts"))
                          {
                            $errcode = "20239";
                            $error[] = $mgmt_config['today']."|hypercms_media.inc.php|error|".$errcode."|Execution of ffmpeg (code:".$errorCode.", command:".$cmd.") failed for file '".$location_source.$file."' \t".implode("\t", $output); 
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
                      $cmd = $mgmt_mediapreview[$mediapreview_ext]." ".$noautorotate." -i \"concat:".implode ("|", $temp_files)."\" -q:v 4 \"".$location_temp.shellcmd_encode ($file_orig)."\"";

                      // execute and redirect stderr (2) to stdout (1)
                      @exec ($cmd." 2>&1", $output, $errorCode);

                      // remove file slices
                      foreach ($temp_files as $temp_file)
                      {
                        unlink ($temp_file);
                      }
    
                      // on error
                      if ($errorCode || !is_file ($location_temp.shellcmd_encode ($file_orig)))
                      {
                        $errcode = "20240";
                        $error[] = $mgmt_config['today']."|hypercms_media.inc.php|error|".$errcode."|Execution of ffmpeg (code:".$errorCode.", command:".$cmd.") failed for file '".$location_source.$file_orig."' \t".implode("\t", $output); 
                      }
                      // reset media file source
                      else
                      {
                        $location_source = $location_temp;
                        $path_source = $location_temp.shellcmd_encode ($file_orig);
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
                elseif ($type == "temp") $newfile = $file_name.".".$format_set;
                else $newfile = $file_name.".media.".$format_set;

                // temp file name
                $tmpfile = $file_name.".tmp.".$format_set;
 
                // create version of original media file and container (only works for files in the media content repository)
                if ($type == "original")
                {
                  $createversion = createversion ($site, $file_orig);
                }

                // remove unset options
                $mgmt_mediaoptions[$mediaoptions_ext] = str_replace (array ("-b:v %videobitrate%", "-b:a %audiobitrate%", "-s:v %width%x%height%"), array ("", "", ""), $mgmt_mediaoptions[$mediaoptions_ext]);

                // render video before watermarking
                $cmd = $mgmt_mediapreview[$mediapreview_ext]." ".$noautorotate." -i \"".shellcmd_encode ($path_source)."\" ".$mgmt_mediaoptions[$mediaoptions_ext]." \"".shellcmd_encode ($location_temp.$tmpfile)."\"";

                // execute and redirect stderr (2) to stdout (1)
                @exec ($cmd." 2>&1", $output, $errorCode);

                // delete joined slice temp file
                if (is_file ($location_temp.$file_orig))
                {
                  unlink ($location_temp.$file_orig);
                }

                // on error for original thumbnail files only in order to save correct file name in config file
                if ($type == "origthumb" && ($errorCode || !is_file ($location_temp.$tmpfile)))
                {
                  // use original file name if rendering failed
                  $newfile = $file;

                  $errcode = "20277";
                  $error[] = $mgmt_config['today']."|hypercms_media.inc.php|error|".$errcode."|Execution ffmpeg (code:".$errorCode.", command:".$cmd.") failed to create original thumbnail file using orginal file '".$file."' \t".implode("\t>", $output);
                }
                // correct rotation metadata if necessary 
                elseif (is_video (".hcms.".$format_set) && is_file ($location_temp.$tmpfile) && !empty ($videoinfo['rotate']) && $videoinfo['rotate'] != "0")
                {
                  // check video info
                  $videoinfo_after = getvideoinfo ($location_temp.$tmpfile);

                  // correct rotate metadata
                  if ($videoinfo_after['rotate'] == $videoinfo['rotate'] && !empty ($mgmt_mediaoptions_autorotate))
                  {
                    $cmd = $mgmt_mediapreview[$mediapreview_ext]." ".$noautorotate." -i \"".shellcmd_encode ($location_temp.$tmpfile)."\" -c copy -metadata:s:v:0 rotate=0 \"".shellcmd_encode ($location_temp."meta.".$tmpfile)."\"";

                    // execute and redirect stderr (2) to stdout (1)
                    @exec ($cmd." 2>&1", $output, $errorCode);

                    // on error
                    if ($errorCode)
                    {
                      $errcode = "10338";
                      $error[] = $mgmt_config['today']."|hypercms_media.inc.php|error|".$errcode."|Metadata update (code:".$errorCode.", command:".$cmd.") failed for file '".$location_source.$file."' \t".implode ("\t", $output);
                    }
                    // replace video file
                    else
                    {
                      rename ($location_temp."meta.".$tmpfile, $location_temp.$tmpfile);
                    }
                  }
                }

                // watermarking (using video filters)
                // set watermark options if defined in publication settings and not already defined
                if (is_video (".hcms.".$format_set) && !empty ($mgmt_config[$site]['watermark_video']) && strpos ("_".$mgmt_mediaoptions[$mediaoptions_ext], "-wm ") == 0)
                {
                  $mgmt_mediaoptions[$mediaoptions_ext] .= " ".$mgmt_config[$site]['watermark_video'];
                }

                // dont watermark the thumbnail video, original video or audio file
                if (is_video (".hcms.".$format_set) && $type != "thumbnail" && $type != "origthumb" && $type != "original" && is_file ($location_temp.$tmpfile) && filesize ($location_temp.$tmpfile) > 100 && strpos ("_".$mgmt_mediaoptions[$mediaoptions_ext], "-wm ") > 0)
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
                  // bottom right corner
                  elseif (strtolower(trim($positioning)) == "center") $vfilter_wm = "movie=".shellcmd_encode (trim($watermark))." [watermark]; [in][watermark] overlay=(main_w-overlay_w)/2:(main_h-overlay_h)/2 [out]";

                  $tmpfile2 = $file_name.".tmp2.".$format_set;

                  // apply watermark as video filter
                  if (!empty ($vfilter_wm) && !empty ($mgmt_mediapreview[$mediapreview_ext]))
                  {
                    // render video with watermark
                    $cmd = $mgmt_mediapreview[$mediapreview_ext]." ".$noautorotate." -i \"".shellcmd_encode ($location_temp.$tmpfile)."\" -vf \"".$vfilter_wm."\" \"".shellcmd_encode ($location_temp.$tmpfile2)."\"";

                    // execute and redirect stderr (2) to stdout (1)
                    @exec ($cmd." 2>&1", $output, $errorCode);

                    // replace file
                    if (is_file ($location_temp.$tmpfile2)) rename ($location_temp.$tmpfile2, $location_temp.$tmpfile);
                  }
                }

                // on error
                if ($errorCode)
                {
                  @unlink ($location_temp.$tmpfile);

                  $errcode = "20236";
                  $error[] = $mgmt_config['today']."|hypercms_media.inc.php|error|".$errcode."|Execution of ffmpeg (code:".$errorCode.", command:".$cmd.") failed for file '".$location_source.$file."' \t".implode("\t", $output);
                } 
                elseif (is_file ($location_temp.$tmpfile))
                {
                  $converted = true;

                  // inject metadata into FLV file using YAMDI (/usr/bin/yamdi)
                  if ($mgmt_mediametadata['.flv'] != "" && $format_set == "flv")
                  {
                    $tmpfile2 = $file_name.".tmp2.".$format_set;

                    // inject meta data
                    $cmd = $mgmt_mediametadata['.flv']." -i \"".shellcmd_encode ($location_temp.$tmpfile)."\" -o \"".shellcmd_encode ($location_temp.$tmpfile2)."\"";

                    // execute and redirect stderr (2) to stdout (1)
                    @exec ($cmd." 2>&1", $output, $errorCode);

                    @unlink ($location_temp.$tmpfile);

                    // on error
                    if ($errorCode)
                    {
                      $errcode = "20237";
                      $error[] = $mgmt_config['today']."|hypercms_media.inc.php|error|".$errcode."|Execution of yamdi (code:".$errorCode.", command:".$cmd.") failed for file '".$location_source.$newfile."' \t".implode("\t", $output);
                    }
                    // on success
                    else
                    {
                      if (@filesize ($location_temp.$tmpfile2) > 10) 
                      {
                        if (is_file ($location_dest.$newfile)) @unlink ($location_dest.$newfile);
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
                    if (is_file ($location_dest.$newfile)) @unlink ($location_dest.$newfile);
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
                      if ($video_extension != "" && is_file ($location_dest.$file_name.".thumb.".$video_extension))
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
                // generate video player config code for individual video
                else
                {
                  // generate video file links for individual generated video formats
                  $videos = array();
                  $videos[$format_set] = $site."/".$newfile;

                  if ($type == "origthumb" || $type == "original") $config_extension = ".config.orig";
                  else $config_extension = ".config.".$format_set;
                }

                // new video info (only if it is not a thumbnail file of the original file)
                if ($type != "origthumb")
                {
                  $videoinfo = getvideoinfo ($location_dest.$newfile);
                }

                // capture screen from video to use as thumbnail image
                if (($type == "origthumb" || $type == "original") && is_video ($file_ext))
                {
                  $videothumbnail = createthumbnail_video ($site, $location_dest, $location_dest, $newfile, "00:00:01");

                  // get media information from thumbnail
                  $imagecolor = getimagecolors ($site, $videothumbnail);

                  // create preview images
                  if ($type == "origthumb" && intval ($container_id) > 0)
                  {
                    // create directory for thumbnails
                    if (!is_dir ($location_dest.$container_id)) mkdir ($location_dest.$container_id);

                    // define thumbnail size
                    if (!empty ($videoinfo['width']) && !empty ($videoinfo['height'])) 
                    {
                      $ratio = $videoinfo['width'] / $videoinfo['height'];

                      if ($ratio > 1)
                      {
                        $thumb_width = 120;
                        $thumb_height = intval ($thumb_width / $ratio);
                      }
                      else
                      {
                        $thumb_height = 120;
                        $thumb_width = intval ($thumb_height * $ratio);
                      }
                    }

                    createimages_video ($site, $location_dest, $location_dest.$container_id."/", $newfile, "thumbnail", 0.2, "jpg", $thumb_width, $thumb_height);
                  }
                }
                else
                {
                  $imagecolor = array();
                  $imagecolor['red'] = "";
                  $imagecolor['green'] = "";
                  $imagecolor['blue'] = "";
                  $imagecolor['colorkey'] = "";
                }

                // set media width and height to empty string
                if (empty ($videoinfo['width']) || $videoinfo['width'] < 1 || empty ($videoinfo['height']) || $videoinfo['height'] < 1)
                {
                  $videoinfo['width'] = "";
                  $videoinfo['height'] = "";
                }
 
                // save config
                if ($type != "temp" && is_file ($location_dest.$newfile) && is_array ($videoinfo))
                {
                  savemediaplayer_config ($location_dest, $file_name.$config_extension, $videos, $videoinfo['width'], $videoinfo['height'], $videoinfo['rotate'], $videoinfo['filesize'], $videoinfo['duration'], $videoinfo['videobitrate'], $videoinfo['audiobitrate'], $videoinfo['audiofrequenzy'], $videoinfo['audiochannels'], $videoinfo['videocodec'], $videoinfo['audiocodec']);
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
                  if (!empty ($container_id) && !empty ($setmediainfo))
                  {
                    // correct width and height for the database if autorotation has been applied (in order to provide the correct display size)
                    if (!empty ($noautorotate) && !empty ($videoinfo['rotate']) && ($videoinfo['rotate'] == "90" || $videoinfo['rotate'] == "270" || $videoinfo['rotate'] == "-90"))
                    {
                      $temp = $mediawidth_orig;
                      $mediawidth_orig = $mediaheight_orig;
                      $mediaheight_orig = $temp;
                    }

                    $setmedia = rdbms_setmedia ($container_id, $filesize_orig, $filetype_orig, $mediawidth_orig, $mediaheight_orig, $imagecolor['red'], $imagecolor['green'], $imagecolor['blue'], $imagecolor['colorkey'], $imagetype_orig, $md5_hash);
                  }

                  // create new preview (new preview for video/audio file)
                  if ($type == "original")
                  {
                    createmedia ($site, $location_dest, $location_dest, $newfile, "", "origthumb", true, true);
                  }
                }

                // remote client
                remoteclient ("save", "abs_path_media", $site, $location_dest, "", $newfile, "");
              }
            } 
          }
        }

        // remove existing thumbnail if image conversion is not supported
        if (is_file ($location_dest.$file_name.".thumb."))
        {
          unlink ($location_dest.$file_name.".thumb.jpg");
        }
      }
    }

    // delete temp files
    if (is_array ($temp_file_delete) && sizeof ($temp_file_delete) > 0)
    {
      foreach ($temp_file_delete as $temp_file)
      {
        if (is_file ($temp_file)) unlink ($temp_file);
      }
    }

    // no option was found for given format or no media conversion software defined
    if (empty ($setmedia) && ($type == "thumbnail" || $type == "origthumb") && !empty ($container_id) && !empty ($setmediainfo))
    {
      // write media information to container and DB
      $setmedia = rdbms_setmedia ($container_id, $filesize_orig, $filetype_orig, $imagewidth_orig, $imageheight_orig, "", "", "", "", "", $md5_hash);
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

    // restart session (that has been previously closed for non-blocking procedure)
    if (empty (session_id()) && $session_id != "") createsession();

    // return result
    if ($converted == true && !empty ($newfile)) return $newfile;
    else return false;
  }
  else return false;
}

// ---------------------- splitmedia -----------------------------
// function: splitmedia()
// input: publication name [string], path to source dir [string], path to destination dir [string], file name [string], seconds of a segment [integer] (optional), target format (file extension w/o dot) of destination file [string] (optional), 
//          force the file to be not encrypted even if the content of the publication must be encrypted [boolean] (optional)
// output: array of new file names / false on error

// description:
// Splits a video or audio file in segments measured in seconds. Used for synchronous Google Cloud Speech Service that only supports max. 1 minute audio files.

function splitmedia ($site, $location_source, $location_dest, $file, $sec=60, $format="", $force_no_encrypt=false)
{
  global $mgmt_config, $mgmt_imagepreview, $mgmt_mediapreview, $mgmt_mediaoptions, $mgmt_imageoptions, $mgmt_maxsizepreview, $mgmt_mediametadata, $hcms_ext, $user;

  if (valid_publicationname ($site) && valid_locationname ($location_source) && valid_locationname ($location_dest) && valid_objectname ($file) && $sec > 0)
  {
    // appending data to a file ensures that the previous write process is finished (required due to issue when editing encrypted files)
    avoidfilecollision ($file);

    // normalize format
    $format = strtolower ($format);

    // publication management config
    if (!isset ($mgmt_config[$site]['abs_path_page']) && is_file ($mgmt_config['abs_path_data']."config/".$site.".conf.php"))
    {
      require_once ($mgmt_config['abs_path_data']."config/".$site.".conf.php");
    } 

    // add slash if not present at the end of the location string
    $location_source = correctpath ($location_source);
    $location_dest = correctpath ($location_dest);

    // get file name without extension
    $file_name = strrev (substr (strstr (strrev ($file), "."), 1));

    // get the file extension
    $file_ext = strtolower (strrchr ($file, "."));

    // prepare media file
    $temp_source = preparemediafile ($site, $location_source, $file, $user);

    // if encrypted
    if (!empty ($temp_source['result']) && !empty ($temp_source['crypted']) && !empty ($temp_source['templocation']) && !empty ($temp_source['tempfile']))
    {
      $location_source = $temp_source['templocation'];
      $file = $temp_source['tempfile'];
    }
    // if restored
    elseif (!empty ($temp_source['result']) && !empty ($temp_source['restored']) && !empty ($temp_source['location']) && !empty ($temp_source['file']))
    {
      $location_source = $temp_source['location'];
      $file = $temp_source['file'];
    }

    // check if file exists
    if (!is_file ($location_source.$file)) return false;

    // check if symbolic link
    if (is_link ($location_source.$file)) 
    {
      // get the real file path
      $path_source = readlink ($location_source.$file);

      // change location
      $location_source = getlocation ($path_source);

      $file = getobject ($path_source);
    }
    else
    {
      $path_source = $location_source.$file;
    }

    // file name for split
    $splitfile = $file_name."-%d".$file_ext;

    // new file name
    if ($format == "") $newfile = $file_name."-%count%".$file_ext;
    else $newfile = $file_name."-%count%.".$format;

    reset ($mgmt_mediapreview);

    // split into file sgements
    // supported extensions for media rendering
    foreach ($mgmt_mediapreview as $mediapreview_ext => $mediapreview)
    {
      // check file extension
      if ($file_ext != "" && substr_count (strtolower ($mediapreview_ext).".", $file_ext.".") > 0 && !empty ($mediapreview))
      {
        reset ($mgmt_mediaoptions); 

        // use provided options
        if ($options != "")
        {
          $mgmt_mediaoptions = array();
          $mgmt_mediaoptions[".".$format] = shellcmd_encode ($options);
        }

        // extensions for certain media rendering options
        foreach ($mgmt_mediaoptions as $mediaoptions_ext => $mediaoptions)
        {
          // get media rendering options based on given destination format (skip default setting extensions)
          if ($mediaoptions_ext != "thumbnail-video" && $mediaoptions_ext != "thumbnail-audio" && $mediaoptions_ext != "segments" && substr_count (strtolower ($mediaoptions_ext).".", ".".$format.".") > 0)
          {
            // remove unset options
            $mgmt_mediaoptions[$mediaoptions_ext] = str_replace (array ("-b:v %videobitrate%", "-b:a %audiobitrate%", "-s:v %width%x%height%"), array ("", "", ""), $mgmt_mediaoptions[$mediaoptions_ext]);

            // remove existing file segments
            for ($d=0; $d<=10000; $d++)
            {
              $temp_path = $location_dest.$file_name."-".$d.$file_ext;;

              if (is_file ($temp_path)) @unlink ($temp_path);
              else break;
            }

            // render
            $cmd = $mgmt_mediapreview[$mediapreview_ext]." -i \"".shellcmd_encode ($path_source)."\" -segment_time ".shellcmd_encode ($sec)." -map 0 -c copy -f segment -reset_timestamps 1 \"".shellcmd_encode ($location_dest.$splitfile)."\"";

            // execute and redirect stderr (2) to stdout (1)
            @exec ($cmd." 2>&1", $output, $errorCode);

            // on error for original thumbnail files only in order to save correct file name in config file
            if ($errorCode || !is_file ($location_dest.$file_name."-0".$file_ext))
            {
              // use original file name if rendering failed
              $errcode = "20288";
              $error[] = $mgmt_config['today']."|hypercms_media.inc.php|error|".$errcode."|Execution of ffmpeg (code:".$errorCode.", command:".$cmd.") failed to split media file '".$file."' \t".implode("\t", $output);
            }
          }
        }
      }
    }

    // verify results
    $result = array();
    $d = 0;

    for ($d=0; $d<=10000; $d++)
    {
      // segment file path
      $temp_path = $location_dest.$file_name."-".$d.$file_ext;;

      if (is_file ($temp_path))
      {
        // convert segment files
        if (trim ($format) != "")
        {
          // convert media file
          $convfile = createmedia ($site, getlocation ($temp_path), $location_dest, getobject ($temp_path), $format, "temp", $force_no_encrypt, false);

          // remove temp file segment
          @unlink ($temp_path);

          if ($convfile) $result[] = $convfile;
        }
        // use segment file
        else $result[] = getobject ($temp_path);
      }
      else break;
    }
  }

  // write log
  savelog (@$error);

  if (sizeof ($result) > 0) return $result;
  else return false;
}

// ---------------------- convertmedia -----------------------------
// function: convertmedia()
// input: publication name [string], path to source dir [string], path to destination dir [string], file name [string], target format (file extension w/o dot) of destination file [string], media configuration to be used [string] (optional),
//        force the file to be not encrypted even if the content of the publication must be encrypted [boolean] (optional)
// output: new file name / false on error

// description:
// Converts and creates a new image, video, audio, or document from the source file. This is a wrapper function for createmedia, createimages_video and createdocument.
// If the destination media file exists already or is newer than the source file the file name will be returned without conversion.

function convertmedia ($site, $location_source, $location_dest, $mediafile, $format, $media_config="", $force_no_encrypt=false)
{
  global $mgmt_config, $mgmt_imagepreview, $mgmt_mediapreview, $mgmt_mediaoptions, $mgmt_imageoptions, $mgmt_maxsizepreview, $mgmt_mediametadata, $mgmt_compress, $hcms_ext;

  $error = array();

  if (valid_publicationname ($site) && valid_locationname ($location_source) && valid_locationname ($location_dest) && valid_objectname ($mediafile) && $format != "")
  {
    $result_conv = false;

    // load file extensions
    if (empty ($hcms_ext) || !is_array ($hcms_ext)) require ($mgmt_config['abs_path_cms']."include/format_ext.inc.php");

    // add slash if not present at the end of the location string
    $location_source = correctpath ($location_source);
    $location_dest = correctpath ($location_dest);

    // format
    $format = strtolower (trim ($format));

    // if watermark is used, force recreation
    if (!is_document ($format))
    {
      // get container ID
      $container_id = getmediacontainerid ($mediafile);

      // get individual watermark
      if ($mgmt_config['publicdownload'] == true) $containerdata = loadcontainer ($container_id, "work", "sys");
      else $containerdata = loadcontainer ($container_id, "published", "sys");

      if ($containerdata != "")
      {
        $wmlocation = getmedialocation ($site, $mediafile, "abs_path_media");
        $wmnode = selectcontent ($containerdata, "<media>", "<media_id>", "Watermark");

        if (!empty ($wmnode[0]))
        {
          $temp = getcontent ($wmnode[0], "<mediafile>");
          if (!empty ($temp[0])) $wmfile = $temp[0];

          $temp = getcontent ($wmnode[0], "<mediaalign>");
          if (!empty ($temp[0])) $wmalign = $temp[0];
          else $wmalign = "center";

          if (!empty ($wmfile)) $force_recreate = true;
        }
      }
    }

    // convert-config is not supported when createdocument is used
    if (is_document ($mediafile))
    {
      $result_conv = createdocument ($site, $location_source, $location_dest, $mediafile, $format, $force_no_encrypt);
    }
    // convert video to images
    elseif (is_video ($mediafile) && !empty ($mgmt_compress['.zip']) && ($format == "jpg" || $format == "png" || $format == "bmp"))
    {
      // default frames per second to export
      if (empty ($mgmt_config['export_frames_per_second'])) $mgmt_config['export_frames_per_second'] = 0.5;

      // information needed to extract the file name only
      $media_info = getfileinfo ($site, $mediafile, "comp");

      // zip file name
      $newname = $media_info['filename'].".".$format.".zip";

      // generate new file only if necessary
      if (!is_file ($location_dest.$newname) || @filemtime ($location_source.$mediafile) > @filemtime ($location_dest.$newname) || !empty ($force_recreate)) 
      {
        // temporary directory for collecting image files
        $temp_dir = $mgmt_config['abs_path_temp']."vid2jpg_".createuniquetoken()."/";

        // create temporary directory for image extraction
        if (!is_dir ($temp_dir)) $test = @mkdir ($temp_dir, $mgmt_config['fspermission']);
        else $test = true;

        if ($test == true)
        {
          // create images from video
          $result = createimages_video ($site, $location_source, $temp_dir, $mediafile, $media_info['filename'], $mgmt_config['export_frames_per_second'], $format);

          // zip images
          if ($result)
          {
            // delete old zip file
            deletefile ($location_dest, $newname, 0);

            // Windows
            if (!empty ($mgmt_config['os_cms']) && $mgmt_config['os_cms'] == "WIN")
            { 
              $cmd = "cd \"".shellcmd_encode ($temp_dir)."\" & ".$mgmt_compress['.zip']." -r -0 \"".shellcmd_encode ($location_dest.$newname)."\" *";
              $cmd = str_replace ("/", "\\", $cmd);
            }
            // UNIX
            else $cmd = "cd \"".shellcmd_encode ($temp_dir)."\" ; ".$mgmt_compress['.zip']." -r -0 \"".shellcmd_encode ($location_dest.$newname)."\" *";

            // compress files to ZIP format
            // execute and redirect stderr (2) to stdout (1)
            @exec ($cmd." 2>&1", $output, $errorCode);

            // remove temp files
            deletefile (getlocation ($temp_dir), getobject ($temp_dir), 1);

            // errors during compressions of files
            if ($errorCode && is_array ($output))
            {
              $error_message = implode ("\t", $output);
              $error_message = str_replace ($mgmt_config['abs_path_temp'], "/", $error_message);

              $errcode = "10445";
              $error[] = $mgmt_config['today']."|hypercms_media.inc.php|error|".$errcode."|Execution of zip (code:".$errorCode.", command:".$cmd.") failed for '".$newname."' \t".$error_message;
            }

            // on success
            if (is_file ($location_dest.$newname)) $result_conv = $newname;
            else $result_conv = false;
          }
        }
      }
      else $result_conv = $newname;
    }
    // image, video or audio
    else
    {
      // information needed to extract the file name only
      $media_info = getfileinfo ($site, $mediafile, "comp");

      // predict the name of the media file after createmedia based on media_config (images)
      if ($media_config != "")
      {
        $newname = $media_info['filename'].".".$media_config.".".$format;
      }
      // thumbnail video file if type=origthumb
      elseif (is_video ($mediafile) && strtolower ($format) == "origthumb")
      {
        $newname = $media_info['filename'].".orig.mp4";
      }
      // video
      elseif (is_video ($mediafile))
      {
        $newname = $media_info['filename'].".media.".$format;
      }
      // document or thumbnail image
      else
      {
        $newname = $media_info['filename'].".thumb.".$format;
      }

      // generate new file only if necessary
      if (!is_file ($location_dest.$newname) || @filemtime ($location_source.$mediafile) > @filemtime ($location_dest.$newname) || !empty ($force_recreate)) 
      {
        $result_conv = createmedia ($site, $location_source, $location_dest, $mediafile, $format, $media_config, $force_no_encrypt, false);
      }
      // use the existing file
      else $result_conv = $newname;
    }

    // save log
    savelog (@$error);

    return $result_conv;
  }
  else return false;
}

// ---------------------- convertimage -----------------------------
// function: convertimage()
// input: publication name [string], path to source image file [string], path to destination dir [string], format (file extension w/o dot) of destination file [string] (optional), 
//        colorspace of new image [CMY,CMYK,Gray,HCL,HCLp,HSB,HSI,HSL,HSV,HWB,Lab,LCHab,LCHuv,LMS,Log,Luv,OHTA,Rec601YCbCr,Rec709YCbCr,RGB,scRGB,sRGB,Transparent,XYZ,YCbCr,YCC,YDbDr,YIQ,YPbPr,YUV] (optional), 
//        width in pixel/mm/inch [integer] (optional), height in pixel/mm/inch [integer] (optional), slug in pixel/mm/inch [integer] (optional), units for width [string], height and slug [px,mm,inch] (optional),
//        dpi [integer] (optional), image quality [1 to 100], apply watermark [boolean] (optional)
// output: new file name / false on error

// description:
// Converts and creates a new image from original. The new image will be resized and cropped to fit width and height.
// This is a wrapper function of function createmedia.

function convertimage ($site, $file_source, $location_dest, $format="jpg", $colorspace="RGB", $iccprofile="", $width="", $height="", $slug=0, $units="px", $dpi=72, $quality="", $watermark=true)
{
  global $mgmt_config, $mgmt_imagepreview, $mgmt_mediapreview, $mgmt_mediaoptions, $mgmt_imageoptions, $mgmt_maxsizepreview, $mgmt_mediametadata, $hcms_ext, $user;

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

    // if encrypted
    if (!empty ($temp_source['result']) && !empty ($temp_source['crypted']) && !empty ($temp_source['templocation']) && !empty ($temp_source['tempfile']))
    {
      $location_source = $temp_source['templocation'];
      $file = $temp_source['tempfile'];
      $file_source = $location_source.$file;
    }
    // if restored
    elseif (!empty ($temp_source['result']) && !empty ($temp_source['restored']) && !empty ($temp_source['location']) && !empty ($temp_source['file']))
    {
      $location_source = $temp_source['location'];
      $file = $temp_source['file'];
      $file_source = $location_source.$file;
    }

    // add slash if not present at the end of the location string
    $location_dest = correctpath ($location_dest);

    // get file info
    $file_info = getfileinfo ($site, $file_source, "comp");

    // validate DPI value
    if ($dpi < 72) $dpi = 72;
    elseif ($dpi > 2400) $dpi = 2400;

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
      $imagesize = getmediasize ($file_source);

      // if image size is available
      if (!empty ($imagesize['width']) && !empty ($imagesize['height']))
      {
        // image ratios
        $imageratio = $imagesize['width'] / $imagesize['height'];
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

    // suppress watermark defined by publication or asset
    if (empty ($watermark)) 
    {
      $mgmt_config[$site]['watermark_image'] = "";
      $mgmt_imageoptions['.'.$format][$type] = "";
    }

    // check if image exists and is newer than the original image
    if (!is_file ($location_dest.$file_name) || @filemtime ($file_source) > @filemtime ($location_dest.$file_name))
    {
      // define image option
      $mgmt_imageoptions = array();
      $mgmt_imageoptions['.'.$format][$type] = $size_para.$color_para.$format_para.$density_para.$quality_para;

      // render image w/o crop since one step crop and resize are not supported by hyperCMS
      $file_name_new = createmedia ($site, $location_source, $location_dest, $file, $format, $type, true, false);

      // define image option for crop
      if (!empty ($crop_para) && $file_name_new != false)
      {
        // rename intermediate file to original file name with new extension
        rename ($location_dest.$file_name_new, $location_dest.$file_info['filename'].".".$format);

        $mgmt_imageoptions = array();
        $mgmt_imageoptions['.'.$format][$type] = $crop_para;

        // render image
        $file_name_new = createmedia ($site, $location_dest, $location_dest, $file_info['filename'].".".$format, $format, $type, true, false);

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
// input: publication name [string], path to source media file [string], rotation angle [integer], destination image format [jpg,png,gif]
// output: new image file name / false on error

// description:
// Rotates an image (must be jpg, png or gif) using GD library. not used if ImageMagick is available

function rotateimage ($site, $filepath, $angle, $imageformat)
{
  global $mgmt_config, $user;

  if (valid_publicationname ($site) && valid_locationname ($filepath) && $angle <= 360 && ($imageformat == "jpg" || $imageformat == "png" || $imageformat == "gif") && function_exists ("imagecreatefromjpeg") && function_exists ("imagecreatefrompng") && function_exists ("imagecreatefromgif"))
  {
    $file_info = getfileinfo ($site, $filepath, "comp");
    $location = getlocation ($filepath);
    $file = getobject ($filepath);

    // prepare media file
    $temp_source = preparemediafile ($site, $location, $file, $user);

    // if encrypted
    if (!empty ($temp_source['result']) && !empty ($temp_source['crypted']) && !empty ($temp_source['templocation']) && !empty ($temp_source['tempfile']))
    {
      $filepath = $temp_source['templocation'].$temp_source['tempfile'];
    }
    // if restored
    elseif (!empty ($temp_source['result']) && !empty ($temp_source['restored']) && !empty ($temp_source['location']) && !empty ($temp_source['file']))
    {
      $filepath = $temp_source['location'].$temp_source['file'];
    }
 
    if (is_file ($filepath))
    {
      // create image from file
      if ($file_info['ext'] == ".jpg" || $file_info['ext'] == ".jpeg") $image = imagecreatefromjpeg ($filepath);
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
        @imagealphablending ($rotate, false);
        @imagesavealpha ($rotate, true);

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

// ---------------------- hex2rgb -----------------------------
// function: hex2rgb()
// input: image color as hex-code [string]
// output: RGB-color values as array / false on error

function hex2rgb ($hex)
{
  if ($hex != "")
  {
    $hex = str_replace ("#", "", $hex);
    $color = array();
 
    if (strlen ($hex) == 3)
    {
      $color['r'] = hexdec (substr ($hex, 0, 1) . $r);
      $color['g'] = hexdec (substr ($hex, 1, 1) . $g);
      $color['b'] = hexdec (substr ($hex, 2, 1) . $b);
    }
    elseif (strlen ($hex) == 6)
    {
      $color['r'] = hexdec (substr ($hex, 0, 2));
      $color['g'] = hexdec (substr ($hex, 2, 2));
      $color['b'] = hexdec (substr ($hex, 4, 2));
    }
 
    return $color;
  }
  else return false;
}

// ---------------------- rgb2hex -----------------------------
// function: rgb2hex()
// input: image color in RGB [array] or red value [integer], green value [integer], blue value [integer]
// output: hex-color as string / false on error

function rgb2hex ($red, $green=0, $blue=0)
{
  // first parameter holds RGB color code
  if (is_array ($red) && isset ($red['r']) && isset ($red['g']) && isset ($red['b']))
  {
    $rgb = $red;
    $red = intval ($rgb['r']);
    $green = intval ($rgb['g']);
    $blue = intval ($rgb['b']);
  }

  // convert
  if (intval ($red) >= 0 && intval ($green) >= 0 && intval ($blue) >= 0)
  {
    $hex = "#";
    $hex .= str_pad (dechex ($red), 2, "0", STR_PAD_LEFT);
    $hex .= str_pad (dechex ($green), 2, "0", STR_PAD_LEFT);
    $hex .= str_pad (dechex ($blue), 2, "0", STR_PAD_LEFT);
 
    return $hex;
  }
  else return false;
}

// ---------------------- rgb2cmyk -----------------------------
// function: rgb2hex()
// input: image color in RGB [array] or red value [integer], green value [integer], blue value [integer]
// output: CMYK color percentage values as array / false on error

function rgb2cmyk ($red, $green=0, $blue=0)
{
  // first parameter holds RGB color code
  if (is_array ($red) && isset ($red['r']) && isset ($red['g']) && isset ($red['b']))
  {
    $rgb = $red;
    $red = intval ($rgb['r']);
    $green = intval ($rgb['g']);
    $blue = intval ($rgb['b']);
  }

  // convert
  if (intval ($red) >= 0 && intval ($green) >= 0 && intval ($blue) >= 0)
  {
    $red = intval ($red) / 255;
    $green = intval ($green) / 255;
    $blue = intval ($blue) / 255;
    $max = max ($red, $green, $blue);
    $black = 1 - $max;

    if ($black == 1)
    {
      $cyan = 0;
      $magenta = 0;
      $yellow = 0;
    }
    else
    {
      $cyan = (1 - $red - $black) / (1 - $black);
      $magenta = (1 - $green - $black) / (1 - $black);
      $yellow = (1 - $blue - $black) / (1 - $black);
    }

    $cyan = round ($cyan * 100);
    $magenta = round ($magenta * 100);
    $yellow = round ($yellow * 100);
    $black = round ($black * 100);

    return array(
      'c' => $cyan,
      'm' => $magenta,
      'y' => $yellow,
      'k' => $black,
    );
  }
  else return false;
}

// ====================================== VIDEO PLAYER =========================================

// ------------------------- readmediaplayer_config -----------------------------
// function: readmediaplayer_config()
// input: path to media config file [string], config file name [string]
// output: config array / false on error

function readmediaplayer_config ($location, $configfile)
{ 
  global $mgmt_config, $mgmt_mediaoptions, $user;

  if (valid_locationname ($location) && valid_objectname ($configfile))
  {
    // add slash if not present at the end of the location string
    $location = correctpath ($location);

    // get publication
    $site = getpublication ($location.$configfile);

    // prepare source media file
    $temp = preparemediafile ($site, $location, $configfile, $user);

    // if encrypted
    if (!empty ($temp['result']) && !empty ($temp['crypted']) && !empty ($temp['templocation']) && !empty ($temp['tempfile']))
    {
      $location = $temp['templocation'];
      $configfile = $temp['tempfile'];
    }
    // if restored
    elseif (!empty ($temp['result']) && !empty ($temp['restored']) && !empty ($temp['location']) && !empty ($temp['file']))
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
        // rotation in degrees
        elseif (strpos ("_".$value, "rotate=") > 0)
        {
          list ($name, $config['rotate']) = explode ("=", $value);

          // if auto rotation is enabled and the dimensions need to be corrected
          if (!empty ($mgmt_mediaoptions['autorotate-video']) && ($config['rotate'] == "90" || $config['rotate'] == "270" || $config['rotate'] == "-90"))
          {
            $temp = $config['width'];
            $config['width'] = $config['height'];
            $config['height'] = $temp;
          }
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
        // video codec name
        elseif (strpos ("_".$value, "videocodec=") > 0)
        {
          list ($name, $config['videocodec']) = explode ("videocodec=", $value);
        }
        // audio codec name
        elseif (strpos ("_".$value, "audiocodec=") > 0)
        {
          list ($name, $config['audiocodec']) = explode ("audiocodec=", $value);
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

      savemediaplayer_config ($location, $configfile, $media_array, $config['width'], $config['height'], $videoinfo['rotate'], $videoinfo['filesize'], $videoinfo['duration'], $videoinfo['videobitrate'], $videoinfo['audiobitrate'], $videoinfo['audiofrequenzy'], $videoinfo['audiochannels'], $videoinfo['videocodec'], $videoinfo['audiocodec']); 
    }

    // return video config
    return $config;
  }
  else return false;
}

// ------------------------- savemediaplayer_config -----------------------------
// function: savemediaplayer_config()
// input: path to media config file [string], media config file name [string], media file name [array or string], width in px [integer] (optional), height in px [integer] (optional), rotation in degree [integer] (optional), file size in kB [integer] (optional), 
//        duration [hh:mmm:ss] (optional), video bitrate in kb/s [string] (optional), audio bitrate in kb/s [string] (optional), audio frequenzy in Hz [string] (optional), audio channels [mono,stereo] (optional), video codec name [string] (optional), audio codec name [string] (optional)
// output: true / false on error

function savemediaplayer_config ($location, $configfile, $mediafiles, $width=320, $height=240, $rotation="", $filesize="", $duration="", $videobitrate="", $audiobitrate="", $audiofrequenzy="", $audiochannels="", $video_codec="", $audio_codec="")
{
  global $mgmt_config, $user;

  if (valid_locationname ($location) && valid_objectname ($configfile) && (is_array ($mediafiles) || $mediafiles != ""))
  {
    // add slash if not present at the end of the location string
    $location = correctpath ($location);

    // get publication
    $site = getpublication ($location.$configfile);

  	// set 'portrait', 'landscape' or 'square' for the image type
  	if ($width > $height) $imagetype = "landscape";
  	elseif ($height > $width) $imagetype = "portrait";
  	elseif ($height == $width) $imagetype = "square";

    $config = array();
    $config[0] = "V2.5";
    $config[1] = "width=".$width;
    $config[2] = "height=".$height;
    if ($width > 0 && $height > 0) $config[3] = "dimension=".$width."x".$height." px";
    else $config[3] = "dimension=";
    if ($height > 0) $config[4] = "ratio=".round (($width / $height), 5);
    else $result['ratio'] = "ratio=";
    $config[5] = "rotate=".$rotation;
    $config[6] = "filesize=".$filesize;
    $config[7] = "duration=".$duration;
    $config[8] = "videobitrate=".$videobitrate;
    $config[9] = "imagetype=".$imagetype;
    $config[10] = "audiobitrate=".$audiobitrate;
    $config[11] = "audiofrequenzy=".$audiofrequenzy;
    $config[12] = "audiochannels=".$audiochannels;
    $config[13] = "videocodec=".$video_codec;
    $config[14] = "audiocodec=".$audio_codec;

    // array
    if (is_array ($mediafiles)) 
    {
      $i = 15;

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
        $config[$i] = $mediafiles.$mimetype;
      }
      // dont add mime-type
      else $config[$i] = $mediafiles;
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
// input: publication name [string], path to source location [string], path to destination location [string], file name [string], destination file format (extension w/o dot) [string],
//        force the file to be not encrypted even if the content of the publication must be encrypted [boolean] (optional)
// output: new file name / false on error

// description:
// Creates a new multimedia file of given format at source destination using UNOCONV and saves it as a thumbnail file in the destination location

function createdocument ($site, $location_source, $location_dest, $file, $format="", $force_no_encrypt=false)
{
  global $mgmt_config, $mgmt_docpreview, $mgmt_docoptions, $mgmt_docconvert, $mgmt_maxsizepreview, $hcms_ext, $hcms_lang, $lang, $user;

  $error = array();
 
  if (valid_publicationname ($site) && valid_locationname ($location_source) && valid_locationname ($location_dest) && valid_objectname ($file))
  {
    $converted = false;

    set_time_limit (60);

    // add slash if not present at the end of the location string
    $location_source = correctpath ($location_source);
    $location_dest = correctpath ($location_dest);
 
    // for inital conversion request the temp directory will be defined as destination directory by default of the service mediadownload or mediawrapper
    // the converted files however should be placed in the media repository to avoid the recreation of the file over and over again
    if (trim ($location_source) == getmedialocation ($site, $file, "abs_path_media").$site."/" && trim ($location_dest) == trim ($mgmt_config['abs_path_temp']) && strpos ($file, "_hcm") > 0)
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
    if (is_array ($mgmt_docpreview) && sizeof ($mgmt_docpreview) > 0 && $format != trim ($file_ext, "."))
    {
      // prepare source media file
      $temp_source = preparemediafile ($site, $location_source, $file, $user);

      // if encrypted
      if (!empty ($temp_source['result']) && !empty ($temp_source['crypted']) && !empty ($temp_source['templocation']) && !empty ($temp_source['tempfile']))
      {
        $location_source = $temp_source['templocation'];
        $file = $temp_source['tempfile'];
      }
      // if restored
      elseif (!empty ($temp_source['result']) && !empty ($temp_source['restored']) && !empty ($temp_source['location']) && !empty ($temp_source['file']))
      {
        $location_source = $temp_source['location'];
        $file = $temp_source['file'];
      }

      // verify local media file
      if (!is_file ($location_source.$file)) return false;

      // get file size of media file in kB
      $filesize_orig = round (@filesize ($location_source.$file) / 1024, 0);
      if ($filesize_orig < 1) $filesize_orig = 1;

      // check max file size in MB for certain file extensions and skip rendering
      if (!empty ($mgmt_maxsizepreview) && is_array ($mgmt_maxsizepreview))
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
        if (!empty ($file_ext) && substr_count (strtolower ($docpreview_ext).".", $file_ext.".") > 0 && !empty ($docpreview))
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

              // set default format
              if (empty ($docformat)) $docformat = "pdf"; 

              // create new file
              $newfile = $file_name_orig.".thumb.".$docformat;

              // if thumbnail file exists in destination (temp) folder
              if (is_file ($location_dest.$newfile))
              {
                // delete existing destination file if it is older than the source file
                if (filemtime ($location_dest.$newfile) < filemtime ($location_source.$file)) 
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
                if (filemtime ($location_source.$newfile) >= filemtime ($location_source.$file) && $location_source != $location_dest) 
                {
                  // copy to destination directory
                  $converted = copy ($location_source.$newfile, $location_dest.$newfile);
                }
                // or we return the filename
                else $converted = true;
              }

              // if not already converted
              if (empty ($converted))
              {
                // if image file is the target format UNOCONV fails and therefore libreoffice will be used
                // exlude spreadsheets due to issues with libreoffice (will be created when opened using converion to PDF and PDF to image)
                if (is_image (".".$format) && strpos ("_.ods.xls.xlsx", $file_ext) < 1)
                {
                  // export filters for libreoffice
                  if ($file_ext == ".xlsx") $export_filter = ":\"MS Excel 2007 XML\"";
                  elseif ($file_ext == ".xls") $export_filter = ":\"MS Excel 95\"";
                  elseif ($file_ext == ".ods") $export_filter = ":\"OpenDocument Spreadsheet Flat XML\"";
                  else $export_filter = "";
 
                  $cmd = getlocation ($mgmt_docpreview[$docpreview_ext])."libreoffice --headless --convert-to ".shellcmd_encode ($docformat).$export_filter." \"".shellcmd_encode ($location_source.$file)."\" --outdir \"".shellcmd_encode ($location_source)."\"";
                }
                // default UNOCONV character set is UTF-8
                // convert only if $mgmt_docpreview mapping exists in $mgmt_docconvert 
                elseif (!is_image (".".$format) && !empty ($mgmt_docpreview[$docpreview_ext]) && !empty ($mgmt_docconvert[$file_ext]) && is_array ($mgmt_docconvert[$file_ext]))
                {
                  $cmd = $mgmt_docpreview[$docpreview_ext]." ".$mgmt_docoptions[$docoptions_ext]." \"".shellcmd_encode ($location_source.$file)."\"";
                }

                // execute code
                if (!empty ($cmd))
                {
                  // set environment variables
                  if (!empty ($mgmt_config['os_cms']) && $mgmt_config['os_cms'] == "WIN")
                  {
                    putenv ("HOME=C:\\WINDOWS\\TEMP");
                  }
                  else
                  {
                    putenv ("PATH=/usr/local/bin:/bin:/usr/bin:/usr/local/sbin:/usr/sbin:/sbin");
                    putenv ("HOME=/tmp");
                  }

                  // execute and redirect stderr (2) to stdout (1)
                  @exec ($cmd." 2>&1", $output, $errorCode);

                  // error if conversion failed
                  if (!empty ($errorCode) || !is_file ($location_source.$file_name.".".$docformat))
                  {
                    $errcode = "20276";
                    $error[] = $mgmt_config['today']."|hypercms_media.inc.php|error|".$errcode."|Execution of libreoffice/unoconv (code:".$errorCode.", command:".$cmd.") to '".$format."' failed for file '".$location_source.$file."' \t".implode("\t", $output);

                    // save log
                    savelog (@$error);
                  } 
                  else
                  {
                    // rename/move converted file to destination
                    $result_rename = @rename ($location_source.$file_name.".".$docformat, $location_dest.$newfile);

                    if ($result_rename == false)
                    {
                      $errcode = "20377";
                      $error[] = $mgmt_config['today']."|hypercms_media.inc.php|error|".$errcode."|Rename failed in createdocument for file: ".$location_source.$file_name.".".$docformat;

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

                    // create thumbnail image for document from converted PDF or image file
                    if (strpos ($file, "_hcm") > 0 && ($docformat == "pdf" || is_image ($newfile)))
                    {
                      $location_media = getmedialocation ($site, $file, "abs_path_media").$site."/";
                      $thumbnail = $file_name_orig.".thumb.jpg";

                      if (!is_file ($thumbnail) || filemtime ($location_source.$file) > filemtime ($location_media.$thumbnail))
                      {
                        $thumbnail_new = createmedia ($site, $location_dest, $location_media, $newfile, "jpg", "thumbnail", true, false);

                        // correct file name by removing double .thumb
                        if (!empty ($thumbnail_new) && is_file ($location_media.$thumbnail_new))
                        {
                          if (is_file ($location_media.$thumbnail)) deletefile ($location_media, $thumbnail, 0);
                          rename ($location_media.$thumbnail_new, $location_media.$thumbnail);
                        }
                      }
                    }
                  }
                }
              }
            }
          }
        }

        // remove existing thumbnail if document conversion is not supported
        if (empty ($converted) && is_file ($location_dest.$file_name.".thumb.jpg"))
        {
          unlink ($location_dest.$file_name.".thumb.jpg");
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
// input: publication name [string], path to source zip file [string], path to destination location [string], category [page,comp], name of file for extraction [string], user name [string]
// output: result array with all object paths / false

// description:
// Unpacks ZIP file and creates media files in destination location for components or unzips files directly for pages (not recommended due to the security risks by uploading files that can be executed).

function unzipfile ($site, $zipfilepath, $location, $filename, $cat="comp", $user="")
{
  global $mgmt_config, $mgmt_uncompress, $mgmt_imagepreview, $mgmt_mediapreview, $mgmt_mediaoptions;

  $error = array();

  if ($mgmt_uncompress['.zip'] != "" && valid_publicationname ($site) && $zipfilepath != "" && valid_locationname ($location) && valid_objectname ($filename) && ($cat == "page" || $cat == "comp") && valid_objectname ($user))
  {
      // add slash if not present at the end of the location string
    $location = correctpath ($location);

    // extension of zip file
    $file_ext = strtolower (strrchr ($filename, "."));

    // temporary directory for extracting files
    $location_temp = $mgmt_config['abs_path_temp'];
    $unzipname_temp = uniqid ("unzip");
    $unzippath_temp = $location_temp.$unzipname_temp."/";

    $location_zip = getlocation ($zipfilepath);
    $file_zip = getobject ($zipfilepath);

    // prepare media file
    $temp = preparemediafile ($site, $location_zip, $file_zip, $user);

    // if encrypted
    if (!empty ($temp['result']) && !empty ($temp['crypted']) && !empty ($temp['templocation']) && !empty ($temp['tempfile']))
    {
      $location_zip = $temp['templocation'];
      $file_zip = $temp['tempfile'];
      $zipfilepath = $location_zip.$file_zip;
    }
    // if restored
    elseif (!empty ($temp['result']) && !empty ($temp['restored']) && !empty ($temp['location']) && !empty ($temp['file']))
    {
      $location_zip = $temp['location'];
      $file_zip = $temp['file'];
      $zipfilepath = $location_zip.$file_zip;
    }

    reset ($mgmt_uncompress);

    foreach ($mgmt_uncompress as $extension => $execpath)
    {
      if (substr_count ($extension.".", $file_ext.".") > 0)
      {
        if ($cat == "page")
        {
          // extract files directly to page location
          // this will overwrite existing page files!
          $cmd = $execpath." \"".shellcmd_encode ($zipfilepath)."\" -d \"".shellcmd_encode ($location)."\"";

          // execute and redirect stderr (2) to stdout (1)
          @exec ($cmd." 2>&1", $output, $errorCode);

          if ($errorCode && is_array ($output))
          {
            $error_message = implode ("\t", $output);

            $errcode = "10639";
            $error[] = $mgmt_config['today']."|hypercms_media.inc.php|error|".$errcode."|Execution of unzip (code:".$errorCode.", command".$cmd.") failed for '".$filename."' \t".$error_message;

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

          if ($result == true)
          {
            // extract files to temporary location for media assets 
            $cmd = $execpath." \"".shellcmd_encode ($zipfilepath)."\" -d \"".shellcmd_encode ($unzippath_temp)."\"";

            // execute and redirect stderr (2) to stdout (1)
            @exec ($cmd." 2>&1", $output, $errorCode);

            if ($errorCode && is_array ($output))
            {
              $error_message = implode ("\t", $output);
              $error_message = str_replace ($unzippath_temp, "/".$site."/", $error_message);

              $errcode = "10640";
              $error[] = $mgmt_config['today']."|hypercms_media.inc.php|error|".$errcode."|Execution of unzip (code:".$errorCode.", command".$cmd.") failed for '".$filename."' \t". $error_message;

              // save log
              savelog (@$error); 
            }

            // check if files were extracted
            $scandir = scandir ($unzippath_temp);

            if ($scandir)
            {
              $check = 0;
              foreach ($scandir as $buffer) $check++;
              if ($check < 1) return false;
            }
            else return false;

            // create media objects 
            $result = createmediaobjects ($site, $unzippath_temp, $location, $user);

            // delete unzipped temporary files in temporary directory
            deletefile ($location_temp, $unzipname_temp, 1);

            // delete decrypted temporary file
            if ($temp['result'] && $temp['created']) deletefile ($temp['templocation'], $temp['tempfile'], 1);

            return $result;
          }
        }
      }
    }
  }
  else return false;
}

// ---------------------- clonefolder -----------------------------
// function: clonefolder()
// input: publication name [string], source location [string], destination location [string], user name [string], activity that need to be set for daily stats [download] (optional)
// output: container IDs as array / false

// description:
// Help function for function zipfiles that reads all multimedia files from their multimedia objects and copies them to the same folder structure using the object names.

function clonefolder ($site, $source, $destination, $user, $activity="")
{
  global $mgmt_config, $pageaccess, $compaccess, $hiddenfolder, $hcms_linking, $globalpermission, $setlocalpermission;

  if (is_array ($mgmt_config) && $source != "" && $destination != "")
  {
    $result = array();

    $destDir = $destination."/".specialchr_decode (getobject ($source));
    @mkdir ($destDir, $mgmt_config['fspermission'], true);

    if ($scandir = scandir ($source))
    {
      foreach ($scandir as $file)
      {
        // check access permissions
        if (!is_array ($setlocalpermission) && $user != "sys")
        {
          $ownergroup = accesspermission ($site, $source, "comp");
          $setlocalpermission = setlocalpermission ($site, $ownergroup, "comp");
        }

        if (substr_compare ($file, ".", 0, 1) != 0 && ($user == "sys" || ($setlocalpermission['root'] == 1 && $setlocalpermission['download'] == 1)))
        {
          // recursive for folders
          if (is_dir ($source."/".$file))
          {
            $result_add = clonefolder ($site, $source."/".$file, $destDir, $user, $activity);
            if (is_array ($result_add)) $result = array_merge ($result, $result_add);

            $folderinfo = getobjectinfo ($site, $source."/".$file."/", ".folder");

            if (!empty ($folderinfo['container_id']))
            {
              $result[] = $container_id = $folderinfo['container_id'];
            }
          }
          // exclude files matching the temp file pattern and in recycle bin (check for recycle bin object and location, since a folder can be in the recycle bin)
          elseif (!is_tempfile ($file) && strpos ($source.$file."/", ".recycle/") === false)
          {
            $objectdata = loadfile ($source."/", $file);

            if ($objectdata != false)
            {
              $mediafile = getfilename ($objectdata, "media");
              $contentfile = getfilename ($objectdata, "content");

              if (!empty ($contentfile))
              {
                $containerinfo = getcontainername ($contentfile);
              }

              // if container is not locked by a another user
              if (!empty ($mediafile) && (empty ($containerinfo['user']) || $user == $containerinfo['user']))
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

                // add container ID to result 
                if (!empty ($container_id) && !is_thumbnail ($mediafile, false))
                {
                  // write stats
                  if ($user == "sys") $user_stats = getuserip();
                  else $user_stats = $user;
                  
                  if ($activity == "download") rdbms_insertdailystat ($activity, intval($container_id), $user_stats, false);
                  
                  $result[] = $container_id;
                }
              }
            }
          }
        }
      }

      return $result;
    }
    else return false;
  }
  else return false;
}

// ---------------------- zipfiles_helper -----------------------------
// function: zipfiles_helper()
// input: source directory [string], destination directory [string], name of ZIP-file [string], remouse all files from source [boolean] (optional)
// output: true/false

// description:
// Compresses all files and includes their folder structure in a ZIP file. This function does not support multimedia objects and is only a helper function for native file system operations.

function zipfiles_helper ($source, $destination, $zipfilename, $remove=false)
{
 global $mgmt_config, $mgmt_compress;

 $error = array();
 
 if (!empty ($mgmt_compress['.zip']) && is_dir ($source) && is_dir ($destination) && valid_objectname ($zipfilename))
 {
    // ZIP files
    // Windows
    if ($mgmt_config['os_cms'] == "WIN")
    { 
      $cmd = "cd \"".shellcmd_encode ($source)."\" & ".$mgmt_compress['.zip']." -r -0 \"".shellcmd_encode ($destination.$zipfilename).".zip\" *";
      $cmd = str_replace ("/", "\\", $cmd);
    }
    // UNIX
    else $cmd = "cd \"".shellcmd_encode ($source)."\" ; ".$mgmt_compress['.zip']." -r -0 \"".shellcmd_encode ($destination.$zipfilename).".zip\" *";

    // compress files to ZIP format
    // execute and redirect stderr (2) to stdout (1)
    @exec ($cmd." 2>&1", $output, $errorCode);

    // remove temp files
    if ($remove == true) deletefile (getlocation ($source), getobject ($source), 1);

    // errors during compressions of files
    if ($errorCode && is_array ($output))
    {
      $error_message = implode ("\t", $output);
      $error_message = str_replace ($mgmt_config['abs_path_temp'], "/", $error_message);

      $errcode = "10545";
      $error[] = $mgmt_config['today']."|hypercms_media.inc.php|error|".$errcode."|Execution of zip (code:".$errorCode.", command".$cmd.") failed for '".$filename."' \t".$error_message;

      // save log
      savelog (@$error);

      return false; 
    }
    else return true;
  }
  return false;
}

// ---------------------- zipfiles -----------------------------
// function: zipfiles()
// input: publication name [string], array with path to source files [array], destination location (if this is null then the $location where the zip-file resists will be used) [string], 
//          name of ZIP-file [string], user name [string], activity that need to be set for daily stats [download] (optional), flat hierarchy means no directories [boolean] (optional) 
// output: true/false

// description:
// Compresses all media files and includes their folder structure in a ZIP file.

function zipfiles ($site, $multiobject_array, $destination="", $zipfilename="", $user="", $activity="", $flatzip=false)
{
  global $mgmt_config, $mgmt_compress, $pageaccess, $compaccess, $hiddenfolder, $hcms_linking, $globalpermission, $setlocalpermission, $hcms_lang, $lang;

  // initialize
  $error = array();
  $updates = array();

  // set default language
  if (empty ($lang)) $lang = "en";

  if (!empty ($mgmt_compress['.zip']) && valid_publicationname ($site) && is_array ($multiobject_array) && sizeof ($multiobject_array) > 0 && is_dir ($destination) && valid_locationname ($destination) && valid_objectname ($zipfilename) && valid_objectname ($user))
  {
    // check max file size (set default value to 2000 MB)
    if (!isset ($mgmt_config['maxzipsize'])) $mgmt_config['maxzipsize'] = 2000;

    if (!empty ($mgmt_config['db_connect_rdbms']))
    {
      $filesize = 0;

      $multiobject_array = array_unique ($multiobject_array);

      // get total file size of all files which will be zipped
      foreach ($multiobject_array as $multiobject)
      {
        // get publication from path
        $site_temp = getpublication ($multiobject);
        if (valid_publicationname ($site_temp)) $site = $site_temp;

        $multiobject = convertpath ($site, $multiobject, "comp");

        // get file size in KB
        $filesize_array = rdbms_getfilesize ("", $multiobject);
        if (is_array ($filesize_array)) $filesize = $filesize + $filesize_array['filesize'];
      }

      // return false if max file size limit in MB is exceeded
      if ($mgmt_config['maxzipsize'] > 0  && ($filesize / 1024) > $mgmt_config['maxzipsize']) return false;

      // check if ZIP file exists and there are no new files that need to be excluded or included based on the file size (important: the zip process must not use compression!) and the ZIP file size > 100 kB
      if (is_file ($destination.$zipfilename.".zip") && filesize ($destination.$zipfilename.".zip") > 100000)
      {
        // get ZIP file time
        $zipfiletime = filemtime ($destination.$zipfilename.".zip");
        $zipfiledate = date ("Y-m-d H:i:s", $zipfiletime);

        // get ZIP file size in kB
        $zipfilesize = round ((filesize ($destination.$zipfilename.".zip") / 1024), 0);

        // compare file sizes with 4% (-2%/+2%) tolerance
        if ($zipfilesize > ($filesize * 1.02) || $zipfilesize < ($filesize * 0.98)) $updates[] = "true";

        // query for files that are new or have been updated after the ZIP file has been created
        if (sizeof ($updates) < 1)
        {
          foreach ($multiobject_array as $multiobject)
          {
            $multiobject = convertpath ($site, $multiobject, "comp");

            // remove folder object
            if (getobject ($multiobject) == ".folder") $multiobject = getlocation ($multiobject);

            // if location path
            if (substr ($multiobject, -1) == "/")
            {
              $updates = rdbms_externalquery ("SELECT objectpath FROM object WHERE objectpath LIKE BINARY \"".$multiobject."%\" AND objectpath NOT LIKE BINARY \"%.recycle%\" AND date>=\"".$zipfiledate."\"");
            }
            // if object path
            else
            {
              $updates = rdbms_externalquery ("SELECT objectpath FROM object WHERE BINARY objectpath=\"".$multiobject."\" AND objectpath NOT LIKE BINARY \"%.recycle\" AND objectpath NOT LIKE BINARY \"%.recycle/%\" AND date>=\"".$zipfiledate."\"");
            }
          }
        }

        // if no changes have been found
        if (empty ($updates) || (is_array ($updates) && sizeof ($updates) < 1)) return true;
      }
    }

    // temporary directory for file collection
    $tempDir = $mgmt_config['abs_path_temp'];

    if ($flatzip == false)
    {
      $commonRoot = getlocation ($multiobject_array[0]);

      // find common root folder for different file paths
      if (sizeof ($multiobject_array) > 1)
      {
        for ($i=0; $i<sizeof($multiobject_array); $i++)
        {
          if ($multiobject_array[$i] != "")
          {
            // get publication from path
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
    }

    // create unique temp directory to collect the files for compression
    $tempFolderName = uniqid ("zip");
    $tempFolder = $tempDir.$tempFolderName;
    @mkdir ($tempFolder, $mgmt_config['fspermission'], true);

    // walk through objects and get the multimedia files reference
    for ($i=0; $i<sizeof($multiobject_array); $i++)
    {
      if ($multiobject_array[$i] != "")
      {
        $site_temp = getpublication ($multiobject_array[$i]);
        if (valid_publicationname ($site_temp)) $site = $site_temp;

        $filename = getobject ($multiobject_array[$i]);
        $location = getlocation ($multiobject_array[$i]);
        $location = deconvertpath ($location, "file");
        $cat = getcategory ($site, $multiobject_array[$i]);

        if (valid_locationname ($location) && valid_objectname ($filename))
        {
          if (!empty ($commonRoot))
          {
            $destinationFolder = str_replace ($commonRoot, "", $location);
            @mkdir ($tempFolder."/".$destinationFolder, $mgmt_config['fspermission'], true);
          }
          else $destinationFolder = "";

          // exclude files matching the temp file pattern and in recycle bin (check for recycle bin object and location, since a folder can be in the recycle bin)
          if (!is_tempfile ($filename) && strpos ($location.$filename."/", ".recycle/") === false)
          {
            if ($filename != ".folder" && is_file ($location.$filename))
            {
              $objectdata = loadfile ($location, $filename);

              if ($objectdata != false)
              {
                $mediafile = getfilename ($objectdata, "media");
                $contentfile = getfilename ($objectdata, "content");

                if (!empty ($contentfile))
                {
                  $containerinfo = getcontainername ($contentfile);
                }

                // if container is not locked by a another user
                if (!empty ($mediafile) && (empty ($containerinfo['user']) || $user == $containerinfo['user']))
                {
                  $mediadir = getmedialocation ($site, $mediafile, "abs_path_media").$site."/";

                  // prepare media file
                  $temp = preparemediafile ($site, $mediadir, $mediafile, $user);

                  // if encrypted
                  if (!empty ($temp['result']) && !empty ($temp['crypted']) && !empty ($temp['templocation']) && !empty ($temp['tempfile']))
                  {
                    $mediadir = $temp['templocation'];
                    $mediafile = $temp['tempfile'];
                  }
                  // if restored
                  elseif (!empty ($temp['result']) && !empty ($temp['restored']) && !empty ($temp['location']) && !empty ($temp['file']))
                  {
                    $mediadir = $temp['location'];
                    $mediafile = $temp['file'];
                  }

                  // copy file to new location
                  $mediatarget = $tempFolder."/".specialchr_decode ($destinationFolder.$filename);

                  for ($c=1; $c<=100; $c++)
                  {
                    if (is_file ($mediatarget))
                    {
                      $fileinfo = getfileinfo ($site, $filename, $cat);
                      $mediatarget = $tempFolder."/".specialchr_decode ($destinationFolder.$fileinfo['filename']."-".$c."".$fileinfo['ext']);
                    }
                    else break;
                  }

                  copy ($mediadir.$mediafile, $mediatarget);

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

              $container_id_array = clonefolder ($site, $location.$filename, $tempFolder."/".specialchr_decode ($destinationFolder), $user, $activity);

              // save container IDs for statistics in temp file
              if (is_array ($container_id_array) && sizeof ($container_id_array) > 0)
              {
                $temp_data = implode ("\n", $container_id_array);
                savefile ($mgmt_config['abs_path_temp'], $zipfilename.".zip.dat", $temp_data);
              }
            }
          }
        }
      }
    }

    // save info file if there is nothing to be packed
    if (is_emptyfolder ($tempFolder)) savefile ($tempFolder, $hcms_lang['no-results-available'][$lang], "");

    // remove old zip file
    if (is_file ($destination.$zipfilename.".zip")) deletefile ($destination, $zipfilename.".zip", false);

    // Windows
    if ($mgmt_config['os_cms'] == "WIN")
    {
      $cmd = "cd \"".shellcmd_encode ($tempFolder)."\" & ".$mgmt_compress['.zip']." -r -0 \"".shellcmd_encode ($destination.$zipfilename).".zip\" *";
      $cmd = str_replace ("/", "\\", $cmd);
    }
    // UNIX
    else
    {
      $cmd = "cd \"".shellcmd_encode ($tempFolder)."\" ; ".$mgmt_compress['.zip']." -r -0 \"".shellcmd_encode ($destination.$zipfilename).".zip\" *";
    }

    // compress files to ZIP format
    // execute and redirect stderr (2) to stdout (1)
    @exec ($cmd." 2>&1", $output, $errorCode);

    // remove temp files
    deletefile ($tempDir, $tempFolderName, 1);

    // errors during compressions of files
    if ($errorCode && is_array ($output))
    {
      $error_message = implode ("\t", $output);
      $error_message = str_replace ($mgmt_config['abs_path_temp'], "/", $error_message);

      $errcode = "10645";
      $error[] = $mgmt_config['today']."|hypercms_media.inc.php|error|".$errcode."|Execution of zip (code:".$errorCode.", command".$cmd.") failed for '".$filename."' \t".$error_message;

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
// input: pixel [integer], dpi [integer] (optional)
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
// input: pixel [integer], dpi [integer] (optional)
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
// input: pixel [integer], dpi [integer] (optional)
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
// input: pixel [integer], dpi [integer] (optional)
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

// ---------------------- sec2time -----------------------------
// function: sec2time()
// input: time in seconds [float]
// output: time in hh:mm:ss.mmm / false

// description:
// Convert seconds to time format hh:mm:ss.mmm

function sec2time ($input)
{
  if ($input >= 0)
  {
    $hours = floor ($input) / 3600 % 24;
    $minutes = floor ($input) / 60 % 24;
    $seconds = floor ($input) % 60;

    if ($seconds > 0) $milliseconds = floor (($input % $seconds) * 1000);
    else $milliseconds = 0;

    return sprintf ('%02d:%02d:%02d.%03d', $hours, $minutes, $seconds, $milliseconds);
  }
  else return false;
}

// ---------------------- mediasize2frame -----------------------------
// function: mediasize2frame()
// input: media width [integer], media height [integer], frame width [integer] (optional), frame height [integer] (optional), keep maximum media size based on original dimensions of media without stretching [boolean] (optional)
// output: width and height as array / false

// description:
// Calculates the width and height of a media to fit into a given frame size.

function mediasize2frame ($mediawidth, $mediaheight, $framewidth="", $frameheight="", $keepmaxsize=true)
{
  // new image size cant exceed the original image size
  if ($mediawidth > 0 && $mediaheight > 0)
  {
    $mediaratio = $mediawidth / $mediaheight;
    if ($framewidth > 0 && $frameheight > 0) $frameratio = $framewidth / $frameheight;

    if ((!empty ($frameratio) && $mediaratio >= $frameratio) || (empty ($frameratio) && $framewidth > 0))
    {
      if ($keepmaxsize && $mediawidth < $framewidth) $mediawidth = $mediawidth;
      else $mediawidth = $framewidth;

      $mediaheight = ($mediawidth / $mediaratio);
      $mediaheight = round ($mediaheight, 1);
    }
    elseif ((!empty ($frameratio) && $mediaratio < $frameratio) || (empty ($frameratio) && $frameheight > 0))
    {
      if ($keepmaxsize && $mediaheight < $frameheight) $mediaheight = $mediaheight;
      else $mediaheight = $frameheight;

      $mediawidth = ($mediaheight * $mediaratio);
      $mediawidth = round ($mediawidth, 1);
    }

    if ($mediawidth > 0 && $mediaheight > 0) return array ('width'=>$mediawidth, 'height'=>$mediaheight);
    else return false;
  }
  else return false;
}

// ---------------------- vtt2array -----------------------------
// function: vtt2array()
// input: video text track [string]
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

// -------------------------------------- html2pdf -----------------------------------------
// function: html2pdf ()
// input: URLs or pathes to html source files [array], path of pdf destination output file [string], cover page html file [string] (optional), create TOC table of contents [boolean] (optional), 
//        page orientation [Landscape,Portrait] (optional), page size like A4 or Letter [string] (optional), page margin in mm [integer] (optional), image DPI [integer] (optional), image quality 1-100 [integer] (optional), 
//        use smart shrinking of the content so it can fit in the page [boolean] (optional), additional WKHMTLTOPDF options [string] (optional)
// output: true / false on error

// description:
// Converts html to pdf using WKHTMLTOPDF and XSERVER. The CSS media print style will be applied.
// For full support you might want to install the package provided from WKHTMLTOPDF directly (patched QT).
// See the event log in case the function does not create a proper result since you are not using a patched QT version.
// See also: https://wkhtmltopdf.org/usage/wkhtmltopdf.txt

function html2pdf ($source, $dest, $cover="", $toc=false, $page_orientation="Portrait", $page_size="A4", $page_margin=10, $image_dpi=144, $image_quality=95, $smart_shrinking=true, $options="")
{
  global $mgmt_config;

  $error = array();

  // correct source
  $source = link_db_getobject ($source);
         
  if (is_array ($source) && sizeof ($source) > 0 && $dest != "" && !empty ($mgmt_config['html2pdf']))
  {
    // source pages
    $source_pages = "";
    $cover_page = "";
    $temp_files = array();

    // prepare source files
    foreach ($source as $temp)
    {
      $temp = deconvertpath ($temp, "file");
      $location = getlocation ($temp);
      $object = getobject ($temp);
      $file_ext = strrchr ($object, ".");
      $temp_name = uniqid();

      // temporary view file
      $temp_files[] = $temp_file = $mgmt_config['abs_path_view'].$temp_name.$file_ext;

      // session ID
      if (!session_id()) $add = "?PHPSESSID=".session_id();
      else $add = "";
  
      // copy to temp/view in order to access via https and be able to execute code
      if (is_file ($location.$object))
      {
        copy ($location.$object, $temp_file);
    
        // URL in order to get content via HTTP
        $source_url = $mgmt_config['url_path_view'].$temp_name.$file_ext.$add;

        $source_pages .= "\"".str_replace ("\~", "~", shellcmd_encode ($source_url))."\" ";
      }
      else
      {
        $errcode = "10671";
        $error[] = $mgmt_config['today']."|hypercms_media.inc.php|error|".$errcode."|PDF source page '".$object."' could not be created, path to source file is not correct";
      }
    }

    // destination PDF file
    $dest_page = "\"".str_replace ("\~", "~", shellcmd_encode ($dest))."\"";

    // cover page
    if (!empty ($cover))
    {
      $cover = deconvertpath ($cover, "file");
      $location = getlocation ($cover);
      $object = getobject ($cover);
      $file_ext = strrchr ($object, ".");
      $temp_name = uniqid();

      // temporary view file
      $temp_files[] = $temp_file = $mgmt_config['abs_path_view'].$temp_name.$file_ext;

      // session ID
      if (!session_id()) $add = "?PHPSESSID=".session_id();
      else $add = "";
  
      // copy to temp/view in order to access via https and be able to execute code
      if (is_file ($location.$object))
      {
        copy ($location.$object, $temp_file);
  
        // URL in order to get content via HTTP
        $cover_url = $mgmt_config['url_path_view'].$temp_name.$file_ext.$add;

        $cover_page = "cover ".shellcmd_encode($cover_url);
      }
      else
      {
        $errcode = "10672";
        $error[] = $mgmt_config['today']."|hypercms_media.inc.php|error|".$errcode."|PDF cover source page '".$object."' could not be created, path to source file is not correct";
      }
    }

    // TOC
    if (!empty ($toc)) $toc = "toc";
    else $toc = "";

    // page orientation
    if (!empty ($page_orientation)) $page_orientation = "--orientation ".shellcmd_encode ($page_orientation);
    else $page_orientation = "";

    // page margins
    if (!empty ($page_margin)) $page_margin = "-B ".intval($page_margin)." -L ".intval($page_margin)." -R ".intval($page_margin)." -T ".intval($page_margin);
    else $page_margin = "-B 0 -L 0 -R 0 -T 0";

    // smart shrinking of the content so it can fit in the page
    if (!empty ($smart_shrinking)) $smart_shrinking = "--enable-smart-shrinking";
    else $smart_shrinking = "--disable-smart-shrinking";

    // additional WKHMTLTOPDF options
    if (!empty ($options)) $options = shellcmd_encode ($options);
    else $options = "";

    // use X11-Server for not patched QT version
    if (!empty ($mgmt_config['x11'])) $x11 = $mgmt_config['x11']." --server-args=\"-screen 0, 1024x768x24\"";
    else $x11 = "";

    // command (use print CSS via option --print-media-type)
    $cmd = $x11." ".$mgmt_config['html2pdf']." --image-dpi ".intval($image_dpi)." --image-quality ".intval($image_quality)." --print-media-type --page-size ".shellcmd_encode($page_size)." ".$page_margin." ".$smart_shrinking." ".$page_orientation." ".$options." ".$cover_page." ".$toc." ".$source_pages." ".$dest_page;
    
    // execute and redirect stderr (2) to stdout (1)
    exec ($cmd." 2>&1", $output, $errorCode);

    if ($errorCode)
    {
      $errcode = "10674";
      $error[] = $mgmt_config['today']."|hypercms_media.inc.php|error|".$errcode."|Execution of html2pdf failed to create the PDF file (code:".$errorCode.", command:".$cmd.") \t".implode ("\t", $output);
    }

    // remove temp files
    if (!empty ($temp_files) && is_array ($temp_files) && sizeof ($temp_files) > 0)
    {
      foreach ($temp_files as $temp)
      {
        if (is_file ($temp)) unlink ($temp);
      }
    }

    // save log
    savelog (@$error);

    if (!is_file ($dest) || $errorCode) return false;
    else return true;
  }
  else return false;
}

// -------------------------------------- mergepdf -----------------------------------------
// function: mergepdf ()
// input: pathes to pdf source files [array], path of pdf destination output file [string]
// output: true / false on error

// description:
// Merges pdf files into one pdf file. Do NOT USE special characters in file names.
// See also: https://www.pdflabs.com/docs/pdftk-man-page/

function mergepdf ($source, $dest)
{
  global $mgmt_config;

  // initialize
  $error = array();

  // correct source
  $source = link_db_getobject ($source);

  if (is_array ($source) && sizeof ($source) > 0 && $dest != "" && !empty ($mgmt_config['mergepdf']))
  {
    // command
    $cmd = $mgmt_config['mergepdf']." '".str_replace ("\~", "~", shellcmd_encode (implode ("' '", $source)))."' cat output '".str_replace ("\~", "~", shellcmd_encode ($dest))."'";
    
    // execute and redirect stderr (2) to stdout (1)
    exec ($cmd." 2>&1", $output, $errorCode);

    if ($errorCode)
    {
      $errcode = "10675";
      $error[] = $mgmt_config['today']."|hypercms_media.inc.php|error|".$errcode."|Execution of pdftk failed to merge PDF files (code:".$errorCode.", command:".$cmd.") \t".implode ("\t", $output);

      // save log
      savelog (@$error);
    }

    if (!is_file ($dest) || $errorCode) return false;
    else return true;
  }
  else return false;
}
?>