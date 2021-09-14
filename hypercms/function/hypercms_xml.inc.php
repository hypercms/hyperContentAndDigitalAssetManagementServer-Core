<?php 
/*
 * This file is part of
 * hyper Content & Digital Management Server - http://www.hypercms.com
 * Copyright (c) by hyper CMS Content Management Solutions GmbH
 *
 * You should have received a copy of the license (license.txt) along with hyper Content & Digital Management Server
 */
 
// ==================================== XML CONTENT FUNCTIONS ========================================
// please note: 
// all functions require a XML-string in a single variable as input, no arrays!
// function getcontent, selectcontent, getxmlcontent, selectxmlcontent will return an array
// function setcontent, addcontent, updatecontent, deletecontent will return a XML-string in a single variable

// ------------------------------------ valid_tagname ----------------------------------------------

// function: valid_tagname()
// input: tag name [string]
// output: true / false on error

// description:
// Verifies a tag name

function valid_tagname ($tagname)
{
  if ($tagname != "")
  {
    $start = substr_count ($tagname, "<");
    $end = substr_count ($tagname, ">");
    $total = $start + $end;

    if ($total == 0 || $total == 2) return true;
    else return false;
  }
  else return true;
}

// ------------------------------------ escape_xmltags ----------------------------------------------

// function: escape_xmltags()
// input: XML content [string], XML schema [container,template] (optional)
// output: escaped hyperCMS tags in XML content [string] / false on error

// description:
// Escapes all XML tags used in containers since they could be used in the unescaped CDATA sections of a textcontent node.

function escape_xmltags ($xmldata, $schema="container")
{
  if ($xmldata != "")
  {
    if ($schema == "container")
    {
      $search = array("<container>", "<hyperCMS>", "<contentcontainer>", "</contentcontainer>", "<contentxmlschema>", "</contentxmlschema>", "<contentorigin>", "</contentorigin>", "<contentobjects>", "</contentobjects>", "<contentuser>", "</contentuser>", "<contentcreated>", "</contentcreated>", "<contentdate>", "</contentdate>", "<contentpublished>", "</contentpublished>", "<contentstatus>", "</contentstatus>", "<contentworkflow>", "</contentworkflow>", "</hyperCMS>", "<head>", "<pagetitle>", "</pagetitle>", "<pageauthor>", "</pageauthor>", "<pagedescription>", "</pagedescription>", "<pagekeywords>", "</pagekeywords>", "<pagecontenttype>", "</pagecontenttype>", "<pagelanguage>", "</pagelanguage>", "<pagerevisit>", "</pagerevisit>", "<pagetracking>", "</pagetracking>", "</head>", "<textcollection>", "</textcollection>", "<mediacollection>", "</mediacollection>", "<linkcollection>", "</linkcollection>", "<componentcollection>", "</componentcollection>", "<articlecollection>", "</articlecollection>", "</container>", "<text>", "<text_id>", "</text_id>", "<textuser>", "</textuser>", "<textcontent>", "</textcontent>", "</text>", "<article>", "<article_id>", "</article_id>", "<articletitle>", "</articletitle>", "<articledatefrom>", "</articledatefrom>", "<articledateto>", "</articledateto>", "<articlestatus>", "</articlestatus>", "<articleuser>", "</articleuser>", "<articletextcollection>", "</articletextcollection>", "<articlemediacollection>", "</articlemediacollection>", "<articlelinkcollection>", "</articlelinkcollection>", "<articlecomponentcollection>", "</articlecomponentcollection>", "</article>", "<component>", "<component_id>", "</component_id>", "<componentuser>", "</componentuser>", "<componentcond>", "</componentcond>", "<componentfiles>", "</componentfiles>", "</component>", "<link>", "<link_id>", "</link_id>", "<linkuser>", "</linkuser>", "<linkhref>", "</linkhref>", "<linktarget>", "</linktarget>", "<linktext>", "</linktext>", "</link>", "<media>", "<media_id>", "</media_id>", "<mediauser>", "</mediauser>", "<mediafile>", "</mediafile>", "<mediaobject>", "</mediaobject>", "<mediaalttext>", "</mediaalttext>", "<mediaalign>", "</mediaalign>", "<mediawidth>", "</mediawidth>", "<mediaheight>", "</mediaheight>", "</media>");
      $replace = array("&lt;container&gt;", "&lt;hyperCMS&gt;", "&lt;contentcontainer&gt;", "&lt;/contentcontainer&gt;", "&lt;contentxmlschema&gt;", "&lt;/contentxmlschema&gt;", "&lt;contentorigin&gt;", "&lt;/contentorigin&gt;", "&lt;contentobjects&gt;", "&lt;/contentobjects&gt;", "&lt;contentuser&gt;", "&lt;/contentuser&gt;", "&lt;contentcreated&gt;", "&lt;/contentcreated&gt;", "&lt;contentdate&gt;", "&lt;/contentdate&gt;", "&lt;contentpublished&gt;", "&lt;/contentpublished&gt;", "&lt;contentstatus&gt;", "&lt;/contentstatus&gt;", "&lt;contentworkflow&gt;", "&lt;/contentworkflow&gt;", "&lt;/hyperCMS&gt;", "&lt;head&gt;", "&lt;pagetitle&gt;", "&lt;/pagetitle&gt;", "&lt;pageauthor&gt;", "&lt;/pageauthor&gt;", "&lt;pagedescription&gt;", "&lt;/pagedescription&gt;", "&lt;pagekeywords&gt;", "&lt;/pagekeywords&gt;", "&lt;pagecontenttype&gt;", "&lt;/pagecontenttype&gt;", "&lt;pagelanguage&gt;", "&lt;/pagelanguage&gt;", "&lt;pagerevisit&gt;", "&lt;/pagerevisit&gt;", "&lt;pagetracking&gt;", "&lt;/pagetracking&gt;", "&lt;/head&gt;", "&lt;textcollection&gt;", "&lt;/textcollection&gt;", "&lt;mediacollection&gt;", "&lt;/mediacollection&gt;", "&lt;linkcollection&gt;", "&lt;/linkcollection&gt;", "&lt;componentcollection&gt;", "&lt;/componentcollection&gt;", "&lt;articlecollection&gt;", "&lt;/articlecollection&gt;", "&lt;/container&gt;", "&lt;text&gt;", "&lt;text_id&gt;", "&lt;/text_id&gt;", "&lt;textuser&gt;", "&lt;/textuser&gt;", "&lt;textcontent&gt;", "&lt;/textcontent&gt;", "&lt;/text&gt;", "&lt;article&gt;", "&lt;article_id&gt;", "&lt;/article_id&gt;", "&lt;articletitle&gt;", "&lt;/articletitle&gt;", "&lt;articledatefrom&gt;", "&lt;/articledatefrom&gt;", "&lt;articledateto&gt;", "&lt;/articledateto&gt;", "&lt;articlestatus&gt;", "&lt;/articlestatus&gt;", "&lt;articleuser&gt;", "&lt;/articleuser&gt;", "&lt;articletextcollection&gt;", "&lt;/articletextcollection&gt;", "&lt;articlemediacollection&gt;", "&lt;/articlemediacollection&gt;", "&lt;articlelinkcollection&gt;", "&lt;/articlelinkcollection&gt;", "&lt;articlecomponentcollection&gt;", "&lt;/articlecomponentcollection&gt;", "&lt;/article&gt;", "&lt;component&gt;", "&lt;component_id&gt;", "&lt;/component_id&gt;", "&lt;componentuser&gt;", "&lt;/componentuser&gt;", "&lt;componentcond&gt;", "&lt;/componentcond&gt;", "&lt;componentfiles&gt;", "&lt;/componentfiles&gt;", "&lt;/component&gt;", "&lt;link&gt;", "&lt;link_id&gt;", "&lt;/link_id&gt;", "&lt;linkuser&gt;", "&lt;/linkuser&gt;", "&lt;linkhref&gt;", "&lt;/linkhref&gt;", "&lt;linktarget&gt;", "&lt;/linktarget&gt;", "&lt;linktext&gt;", "&lt;/linktext&gt;", "&lt;/link&gt;", "&lt;media&gt;", "&lt;media_id&gt;", "&lt;/media_id&gt;", "&lt;mediauser&gt;", "&lt;/mediauser&gt;", "&lt;mediafile&gt;", "&lt;/mediafile&gt;", "&lt;mediaobject&gt;", "&lt;/mediaobject&gt;", "&lt;mediaalttext&gt;", "&lt;/mediaalttext&gt;", "&lt;mediaalign&gt;", "&lt;/mediaalign&gt;", "&lt;mediawidth&gt;", "&lt;/mediawidth&gt;", "&lt;mediaheight&gt;", "&lt;/mediaheight&gt;", "&lt;/media&gt;");
    }
    elseif ($schema == "template")
    {
      $search = array("<template>", "<name>", "</name>", "<user>", "</user>", "<category>", "</category>", "<extension>", "</extension>", "<application>", "</application>", "<content>", "</content>", "</template>");
      $replace = array("&lt;template&gt;", "&lt;name&gt;", "&lt;/name&gt;", "&lt;user&gt;", "&lt;/user&gt;", "&lt;category&gt;", "&lt;/category&gt;", "&lt;extension&gt;", "&lt;/extension&gt;", "&lt;application&gt;", "&lt;/application&gt;", "&lt;content&gt;", "&lt;/content&gt;", "&lt;/template&gt;");
    }

    $xmldata = str_replace ($search, $replace, $xmldata);
  }

  return $xmldata;
}

// ------------------------------------ setxmlparameter ----------------------------------------------

// function: setxmlparameter()
// input: XML content container [string], paramater name [string], paramater value [string]
// output: XML content container / false on error

// description:
// Set parameter values in XML declaration (e.g. encoding): encoding="UTF-8"

function setxmlparameter ($xmldata, $parameter, $value)
{
  if ($xmldata != "" && $parameter != "")
  {
    $xml_start = strpos ($xmldata, "<?");
    $xml_end = strpos ($xmldata, "?>") + 2;
    $xml_len = $xml_end - $xml_start;

    if ($xml_len >= 4)
    {
      $xml_str = substr ($xmldata, $xml_start, $xml_len);
    }
    else $xml_str = "<?xml ?>";

    // insert parameters & values into XML declaration
    if (strpos ($xml_str, $parameter) > 0)
    {
      $xml_str_start = strpos ($xml_str, $parameter) + strlen ($parameter);
      $xml_str_dq1 = strpos ($xml_str, "\"", $xml_str_start);
      $xml_str_end = strpos ($xml_str, "\"", $xml_str_dq1 + 1);

      if ($xml_str_start > 0 && $xml_str_dq1 > 0 && $xml_str_end > 0)
      {
        $xml_str_new = substr ($xml_str, 0, $xml_str_dq1 + 1).$value.substr ($xml_str, $xml_str_end);
      }
      elseif ($xml_str_start > 0)
      {
        $xml_str_new = chop (substr ($xml_str, 0, $xml_str_start))."=\"$value\" ?>";
      }
    }
    else
    {
      $xml_str_start = strpos ($xml_str, "?>");
      $xml_str_new = chop (substr ($xml_str, 0, $xml_str_start))." $parameter=\"$value\" ?>";
    }

    // insert xml declaration into xml code
    if (strlen ($xml_str_new) >= 4)
    {
      if ($xml_len >= 4)
      {
        $xmldata = substr_replace ($xmldata, $xml_str_new, $xml_start, $xml_len);
        return $xmldata;
      }
      else
      {
        $xmldata = $xml_str_new."\n".$xmldata;
        return $xmldata;
      }
    }
    else return false;
  }
  else return $xmldata;
}


// ------------------------------------ getcontent ----------------------------------------------

// function: getcontent()
// input: XML content container [string], tag name [string], unescape content [boolean] (optional) 
// output: result array with the content of the requested XML node (tag) / false on error

// description:
// <tagname>content</tagname>
// Extracts the content between the given $starttagname xml-tags.
// Only this function will decode special characters (&, <, >) in the content and removes CDATA.
// Function getcontent will only decode values if they are non-xml and non_html. so content inside child nodes including tags won't be decoded.
// Wild card character "*" can be used at the end of $starttagname.

function getcontent ($xmldata, $starttagname, $unescape_content=false)
{
  // missing input
  if ($xmldata == "" || $starttagname == "" || !is_string ($xmldata) || !is_string ($starttagname))
  {
    return false;
  }

  // add < and > for tag name
  if (@substr_count ($starttagname, "<") == 0 && @substr_count ($starttagname, ">") == 0) $starttagname = "<".trim ($starttagname).">";

  // define endtag
  if (@substr_count ($starttagname, " ") >= 1) $endtagname = "</".substr ($starttagname, 1, strpos ($starttagname, " ") - 1).">";
  elseif (@substr_count ($starttagname, "*") >= 1) $endtagname = "</".substr ($starttagname, 1, strpos ($starttagname, "*") - 1).">";
  else $endtagname = "</".substr ($starttagname, 1);

  // manipulate starttag if wild card character is used for attribute
  if (@substr_count ($starttagname, "*") >= 1) 
  {
    $starttagname = trim (substr ($starttagname, 0, strpos ($starttagname, "*") - 1));
    $wildcard = true;
  }
  else $wildcard = false;

  // extract content between tags
  $record_array = explode ($starttagname, $xmldata);

  if (!empty ($record_array) && is_array ($record_array) && sizeof ($record_array) > 0)
  {
    // do not accept first record (it is not a part of the query result! may even be empty, if starttag is the first value in the file)
    $i = -1;

    foreach ($record_array as $record)
    {
      if ($i != -1 && $record != "")
      {
        if (substr_count ($record, $endtagname) > 0) list ($content_record, $rest) = explode ($endtagname, $record);
        else $content_record = $record;

        // manipulate xml-string if wild card character is used for attribute
        if ($wildcard == true) 
        {
          $content_record = substr ($content_record, strpos ($content_record, ">") + 1);
        }

        // remove CDATA and leave code in CDATA section as it is
        if (substr (trim ($content_record), 0, 9) == "<![CDATA[" && substr (trim ($content_record), strlen (trim ($content_record))-3, 3) == "]]>")
        {
          $content_record = trim ($content_record);
          $content_record = substr ($content_record, 9, strlen ($content_record)-12);

          // unescape CDATA section (in template content) inside correct CDATA section
          $content_record = str_replace ("&lt;![CDATA[", "<![CDATA[", $content_record); 
          $content_record = str_replace ("]]&gt;", "]]>", $content_record); 
        }

        // unescape characters & < >
        if (!empty ($unescape_content))
        {
          $content_record = str_replace ("&amp;", "&", $content_record);
          $content_record = str_replace ("&lt;", "<", $content_record);
          $content_record = str_replace ("&gt;", ">", $content_record);
        } 

        $result_set[$i] = $content_record;
      }

      $i++;
    }
  }

  if (isset ($result_set)) return $result_set;
  else return false;
}

// ------------------------------------ geticontent ----------------------------------------------

// function: geticontent()
// input: XML content container [string], tag name [string], unescape content [boolean] (optional) 
// output: result array with the content of the requested XML node (tag) / false on error

// description:
// CASE-Insensitive version (XML parser are however always case-sensitive!)
//
// <tagname>content</tagname>
// Extracts the content between the given $starttagname xml-tags.
// Only this function will decode special characters (&, <, >) in the content and removes CDATA.
// getcontent will only decode values if they are non-xml and non_html. so content inside child nodes including tags won't be decoded.
// Wild card character "*" can be used at the end of $starttagname

function geticontent ($xmldata, $starttagname, $unescape_content=false)
{
  // missing input
  if ($xmldata == "" || $starttagname == "" || !is_string ($xmldata) || !is_string ($starttagname))
  {
    return false;
  }

  // add < and > for tag name
  if (@substr_count ($starttagname, "<") == 0 && @substr_count ($starttagname, ">") == 0) $starttagname = "<".trim ($starttagname).">";

  // define endtag
  if (@substr_count ($starttagname, " ") > 0) $endtagname = "</".substr ($starttagname, 1, strpos ($starttagname, " ") - 1).">";
  elseif (@substr_count ($starttagname, "*") > 0) $endtagname = "</".substr ($starttagname, 1, strpos ($starttagname, "*") - 1).">";
  else $endtagname = "</".substr ($starttagname, 1);

  // manipulate starttag if wild card character is used for attribute
  if (@substr_count ($starttagname, "*") > 0) 
  {
    $starttagname = trim (substr ($starttagname, 0, strpos ($starttagname, "*") - 1));
    $wildcard = true;
  }
  else $wildcard = false;

  // for case insensitive explode we need to replace the tags
  $xmldata = str_ireplace ($starttagname, strtolower ($starttagname), $xmldata);
  $xmldata = str_ireplace ($endtagname, strtolower ($endtagname), $xmldata);

  // extract content between tags
  $record_array = explode (strtolower ($starttagname), $xmldata);

  if (!empty ($record_array) && is_array ($record_array) && sizeof ($record_array) > 0)
  {
    // do not accept first record (it is not a part of the query result! may even be empty, if starttag is the first value in the file)
    $i = -1;

    foreach ($record_array as $record)
    {
      if ($i != -1 && $record != "")
      {
        if (substr_count ($record, $endtagname) > 0) list ($content_record, $rest) = explode (strtolower ($endtagname), $record);
        else $content_record = $record;

        // manipulate xml-string if wild card character is used for attribute
        if ($wildcard == true) 
        {
          $content_record = substr ($content_record, strpos ($content_record, ">") + 1);
        }

        // remove CDATA and leave code in CDATA section as it is
        if (substr (trim ($content_record), 0, 9) == "<![CDATA[" && substr (trim ($content_record), strlen (trim ($content_record))-3, 3) == "]]>")
        {
          $content_record = trim ($content_record);
          $content_record = substr ($content_record, 9, strlen ($content_record)-12);

          // unescape CDATA section (in template content) inside correct CDATA section
          $content_record = str_replace ("&lt;![CDATA[", "<![CDATA[", $content_record); 
          $content_record = str_replace ("]]&gt;", "]]>", $content_record); 
        }

        // unescape characters & < >
        if (!empty ($unescape_content))
        {
          $content_record = str_replace ("&amp;", "&", $content_record);
          $content_record = str_replace ("&lt;", "<", $content_record);
          $content_record = str_replace ("&gt;", ">", $content_record);
        } 

        $result_set[$i] = $content_record;
      }

      $i++;
    }
  }

  if (isset ($result_set)) return $result_set;
  else return false;
}

// ------------------------------------ getxmlcontent ----------------------------------------------

// function: getxmlcontent()
// input: XML content container [string], tag name [string]
// output: result array with the content of the requested XML node (tag) / false on error

// description:
// <tagname>content</tagname>
// Extracts the content together with the $starttagname xml tags.
// This function will NOT decode special characters like function getcontent!
// Wild card character "*" can be used at the end of $starttagname.

function getxmlcontent ($xmldata, $starttagname)
{
  // missing input
  if ($xmldata == "" || $starttagname == "" || !is_string ($xmldata) || !is_string ($starttagname))
  {
    return false;
  }

  // add < and > for tag name
  if (@substr_count ($starttagname, "<") == 0 && @substr_count ($starttagname, ">") == 0) $starttagname = "<".trim ($starttagname).">";

  // hold starttag in buffer
  $buffer = $starttagname;

  // define endtag
  if (@substr_count ($starttagname, " ") >= 1) $endtagname = "</".substr ($starttagname, 1, strpos ($starttagname, " ") - 1).">";
  elseif (@substr_count ($starttagname, "*") >= 1) $endtagname = "</".substr ($starttagname, 1, strpos ($starttagname, "*") - 1).">";
  else $endtagname = "</".substr ($starttagname, 1);

  // manipulate starttag if wild card character is used for attribute
  if (@substr_count ($starttagname, "*") >= 1) 
  {
    $starttagname = trim (substr ($starttagname, 0, strpos ($starttagname, "*")));
  }

  // extract content between tags
  $record_array = explode ($starttagname, $xmldata);

  if (!empty ($record_array) && is_array ($record_array) && sizeof ($record_array) > 0)
  {
    // do not accept first record (it is not a part of the query result! may even be empty, if starttag is the first value in the file)
    $i = -1;

    foreach ($record_array as $record)
    {
      if ($i > -1 && $record != "")
      {
        if (substr_count ($record, $endtagname) > 0) list ($content_record, $rest) = explode ($endtagname, $record);
        else $content_record = $record;

        $result_set[$i] = $starttagname.$content_record.$endtagname;
      }

      $i++;
    }
  }

  if (isset ($result_set)) return $result_set;
  else return false;
}

// ------------------------------------ getxmlicontent ----------------------------------------------

// function: getxmlicontent()
// input: XML content container [string], tag name [string]
// output: result array with the content of the requested XML node (tag) / false on error

// description:
// CASE-Insensitive version (XML parser are always case-sensitive!)
//
// <tagname>content</tagname>
// Extracts the content together with the $starttagname xml tags.
// This function will NOT decode special characters like function getcontent!
// Wild card character "*" can be used at the end of $starttagname.

function getxmlicontent ($xmldata, $starttagname)
{
  // missing input
  if ($xmldata == "" || $starttagname == "" || !is_string ($xmldata) || !is_string ($starttagname))
  {
    return false;
  }

  // add < and > for tag name
  if (@substr_count ($starttagname, "<") == 0 && @substr_count ($starttagname, ">") == 0) $starttagname = "<".trim ($starttagname).">";

  // hold starttag in buffer
  $buffer = $starttagname;

  // define endtag
  if (@substr_count ($starttagname, " ") > 0) $endtagname = "</".substr ($starttagname, 1, strpos ($starttagname, " ") - 1).">";
  elseif (@substr_count ($starttagname, "*") > 0) $endtagname = "</".substr ($starttagname, 1, strpos ($starttagname, "*") - 1).">";
  else $endtagname = "</".substr ($starttagname, 1);

  // manipulate starttag if wild card character is used for attribute
  if (@substr_count ($starttagname, "*") >= 1) 
  {
    $starttagname = trim (substr ($starttagname, 0, strpos ($starttagname, "*")));
  }

  // for case insensitive explode we need to replace the tags
  $xmldata = str_ireplace ($starttagname, strtolower ($starttagname), $xmldata);
  $xmldata = str_ireplace ($endtagname, strtolower ($endtagname), $xmldata);

  // extract content between tags
  $record_array = explode (strtolower ($starttagname), $xmldata);

  if (!empty ($record_array) && is_array ($record_array) && sizeof ($record_array) > 0)
  {
    // do not accept first record (it is not a part of the query result! may even be empty, if starttag is the first value in the file)
    $i = -1;

    foreach ($record_array as $record)
    {
      if ($i > -1 && $record != "")
      {
        if (substr_count ($record, $endtagname) > 0) list ($content_record, $rest) = explode (strtolower ($endtagname), $record); 
        else $content_record = $record;

        $result_set[$i] = $starttagname_.$content_record.$endtagname;
      }

      $i++;
    }
  }

  if (isset ($result_set)) return $result_set;
  else return false;
}


// ------------------------------------ selectcontent -------------------------------------

// function: selectcontent()
// input: XML content container [string], tag name of requested XML node [string], tag holding the conditional value inside the given starttagname [string], conditional value [string]
// output: result array with the content of the requested XML node (tag) / false on error

// description:
// <tagname>
//    .......
//    <condtag>condvalue</condtag>
//    .........
// </tagname>
//
// Extracts the content between the given $starttagname xml tags where the child xml tag $startcondtag value is equal with the target value $condvalue.
// Wild card character "*" can be used at the end of $starttagname.
// Wild card character "*" can be used at begin and end of $condvalue.
// Be Aware: $startcondtag must be a child of $starttagname!

function selectcontent ($xmldata, $starttagname, $startcondtag, $condvalue)
{
  // missing input
  if ($xmldata == "" || $starttagname == "" || !is_string ($xmldata) || !is_string ($starttagname))
  {
    return false;
  }

  // add < and > for tag name
  if (@substr_count ($starttagname, "<") == 0 && @substr_count ($starttagname, ">") == 0) $starttagname = "<".trim ($starttagname).">";

  // define endtag
  if (@substr_count ($starttagname, " ") >= 1) $endtagname = "</".substr ($starttagname, 1, strpos ($starttagname, " ") - 1).">";
  elseif (@substr_count ($starttagname, "*") >= 1) $endtagname = "</".substr ($starttagname, 1, strpos ($starttagname, "*") - 1).">";
  else $endtagname = "</".substr ($starttagname, 1);

  // manipulate starttag if wild card character is used for attribute
  if (@substr_count ($starttagname, "*") >= 1) 
  {
    $starttagname = trim (substr ($starttagname, 0, strpos ($starttagname, "*")));
    $wildcard = true;
  }
  else $wildcard = false;

  // if condition is set
  if ($startcondtag != "")
  { 
    $condvalue = trim ($condvalue);

    // check if wild card characters are used in condvalue
    if ($condvalue != "" && $condvalue[0] == "*") 
    {
      $wc_begin = true;
      $condvalue = substr ($condvalue, 1);
    }
    else $wc_begin = false;

    if ($condvalue != "" && $condvalue[strlen ($condvalue) - 1] == "*") 
    {
      $wc_end = true;
      $condvalue = substr ($condvalue, 0, strlen ($condvalue) - 1);
    }
    else $wc_end = false;

    // extract content between tags
    $record_array = explode ($starttagname, $xmldata);

    if (!empty ($record_array) && is_array ($record_array) && sizeof ($record_array) > 0)
    {
      // do not accept first record (it is not a part of the query result! may even be empty, if starttag is the first value in the file)
      $i = -1;
      $j = 0;

      foreach ($record_array as $record)
      {
        if ($i != -1 && $record != "")
        {
          if (substr_count ($record, $endtagname) > 0) list ($content_record, $rest) = explode ($endtagname, $record);
          else $content_record = $record;

          // get value of condtag
          $currentvalue_array = getcontent ($content_record, $startcondtag);

          // find all XML-object including a child that fulfils the condition
          if ($currentvalue_array != false)
          { 
            foreach ($currentvalue_array as $currentvalue) 
            {
              $currentvalue = trim ($currentvalue);
 
              if ($wc_begin == false && $wc_end == false && $currentvalue == $condvalue)
              {
                // manipulate xml-string if wild card character is used for attribute
                if ($wildcard == true) 
                {
                  $content_record = substr ($content_record, strpos ($content_record, ">") + 1);
                }
  
                $result_set[$j] = $content_record;
                $j++;

                break;
              }
              elseif ($wc_begin == true && $wc_end == true && @substr_count ($currentvalue, $condvalue) >= 1)
              {
                // manipulate xml-string if wild card character is used for attribute
                if ($wildcard == true) 
                {
                  $content_record = substr ($content_record, strpos ($content_record, ">") + 1);
                }

                $result_set[$j] = $content_record;
                $j++;

                break;
              }
              elseif ($wc_begin == true && $wc_end == false && substr ($currentvalue, strlen ($currentvalue) - strlen ($condvalue)) == $condvalue)
              {
                // manipulate xml-string if wild card character is used for attribute
                if ($wildcard == true) 
                {
                  $content_record = substr ($content_record, strpos ($content_record, ">") + 1);
                }

                $result_set[$j] = $content_record;
                $j++;

                break;
              }
              elseif ($wc_begin == false && $wc_end == true && substr ($currentvalue, 0, strlen ($condvalue)) == $condvalue)
              {
                // manipulate xml-string if wild card character is used for attribute
                if ($wildcard == true) 
                {
                  $content_record = substr ($content_record, strpos ($content_record, ">") + 1);
                }

                $result_set[$j] = $content_record;
                $j++;

                break;
              }
            }
          }
        }

        $i++;
      }
    }
  }
  // if there is no condition set
  else
  {
    $result_set = getcontent ($xmldata, $starttagname, false);
  }

  if (isset ($result_set)) return $result_set;
  else return false;
}

// ------------------------------------ selecticontent -------------------------------------

// function: selecticontent()
// input: XML content container [string], tag name of requested XML node [string], tag holding the conditional value inside the given starttagname [string]
// output: result array with the content of the requested XML node (tag) / false on error

// description:
// CASE-Insensitive version (XML parser are always case-sensitive!)
//
// <tagname>
//    .......
//    <condtag>condvalue</condtag>
//    .........
// </tagname>
//
// Extracts the content between the given $starttagname xml tags where the child xml tag $startcondtag value is equal with the target value $condvalue.
// Wild card character "*" can be used at the end of $starttagname.
// Wild card character "*" can be used at begin and end of $condvalue.
// Be Aware: $startcondtag must be a child of $starttagname!

function selecticontent ($xmldata, $starttagname, $startcondtag, $condvalue)
{
  // missing input
  if ($xmldata == "" || $starttagname == "" || !is_string ($xmldata) || !is_string ($starttagname))
  {
    return false;
  }

  // add < and > for tag name
  if (@substr_count ($starttagname, "<") == 0 && @substr_count ($starttagname, ">") == 0) $starttagname = "<".trim ($starttagname).">";

  // define endtag
  if (@substr_count ($starttagname, " ") >= 1) $endtagname = "</".substr ($starttagname, 1, strpos ($starttagname, " ") - 1).">";
  elseif (@substr_count ($starttagname, "*") >= 1) $endtagname = "</".substr ($starttagname, 1, strpos ($starttagname, "*") - 1).">";
  else $endtagname = "</".substr ($starttagname, 1);

  // manipulate starttag if wild card character is used for attribute
  if (@substr_count ($starttagname, "*") >= 1) 
  {
    $starttagname = trim (substr ($starttagname, 0, strpos ($starttagname, "*")));
    $wildcard = true;
  }
  else $wildcard = false; 

  // for case insensitive explode we need to replace the tags
  $xmldata = str_ireplace ($starttagname, strtolower ($starttagname), $xmldata);
  $xmldata = str_ireplace ($endtagname, strtolower ($endtagname), $xmldata);

  // if condition is set
  if ($startcondtag != "")
  { 
    $condvalue = trim (strtolower ($condvalue));

    // check if wild card characters are used in condvalue
    if ($condvalue != "" && $condvalue[0] == "*") 
    {
      $wc_begin = true;
      $condvalue = substr ($condvalue, 1);
    }
    else $wc_begin = false;

    if ($condvalue != "" && $condvalue[strlen ($condvalue) - 1] == "*") 
    {
      $wc_end = true;
      $condvalue = substr ($condvalue, 0, strlen ($condvalue) - 1);
    }
    else $wc_end = false;

    // extract content between tags
    $record_array = explode (strtolower ($starttagname), $xmldata);

    if (!empty ($record_array) && is_array ($record_array) && sizeof ($record_array) > 0)
    {
      // do not accept first record (it is not a part of the query result! may even be empty, if starttag is the first value in the file)
      $i = -1;
      $j = 0;

      foreach ($record_array as $record)
      {
        if ($i != -1 && $record != "")
        {
          if (substr_count ($record, $endtagname) > 0) list ($content_record, $rest) = explode (strtolower ($endtagname), $record);
          else $content_record = $record;

          // get value of condtag
          $currentvalue_array = geticontent ($content_record, $startcondtag);

          // find all XML-object including a child that fulfils the condition
          if ($currentvalue_array != false)
          { 
            foreach ($currentvalue_array as $currentvalue) 
            {
              $currentvalue = trim (strtolower ($currentvalue));
 
              if ($wc_begin == false && $wc_end == false && $currentvalue == $condvalue)
              {
                // manipulate xml-string if wild card character is used for attribute
                if ($wildcard == true) 
                {
                  $content_record = substr ($content_record, strpos ($content_record, ">") + 1);
                }

                $result_set[$j] = $content_record;
                $j++;

                break;
              }
              elseif ($wc_begin == true && $wc_end == true && @substr_count ($currentvalue, $condvalue) >= 1)
              {
                // manipulate xml-string if wild card character is used for attribute
                if ($wildcard == true) 
                {
                  $content_record = substr ($content_record, strpos ($content_record, ">") + 1);
                }

                $result_set[$j] = $content_record;
                $j++;

                break;
              }
              elseif ($wc_begin == true && $wc_end == false && substr ($currentvalue, strlen ($currentvalue) - strlen ($condvalue)) == $condvalue)
              {
                // manipulate xml-string if wild card character is used for attribute
                if ($wildcard == true) 
                {
                  $content_record = substr ($content_record, strpos ($content_record, ">") + 1);
                } 

                $result_set[$j] = $content_record;
                $j++;

                break;
              }
              elseif ($wc_begin == false && $wc_end == true && substr ($currentvalue, 0, strlen ($condvalue)) == $condvalue)
              {
                // manipulate xml-string if wild card character is used for attribute
                if ($wildcard == true) 
                {
                  $content_record = substr ($content_record, strpos ($content_record, ">") + 1);
                }

                $result_set[$j] = $content_record;
                $j++;

                break;
              }
            }
          }
        }

        $i++;
      }
    }
  }
  // if there is no condition set
  else
  {
    $result_set = geticontent ($xmldata, $starttagname, false);
  }

  if (isset ($result_set)) return $result_set;
  else return false;
}


// ------------------------------------ selectxmlcontent -------------------------------------

// function: selectxmlcontent()
// input: XML content container [string], tag name of requested XML node [string], tag holding the conditional value inside the given starttagname
// output: result array with the content of the requested XML node (tag) / false on error

// description:
// <tagname>
//    .......
//    <condtag>condvalue</condtag>
//    .......
// </tagname>
//
// Extracts the content between the given $starttagname xml tags where the child xml tag $startcondtag value is equal with the target value $condvalue
// Wild card character "*" can be used at begin and end of $condvalue.
// Be Aware: $startcondtag must be a child of $starttagname!

function selectxmlcontent ($xmldata, $starttagname, $startcondtag, $condvalue)
{
  // missing input
  if ($xmldata == "" || $starttagname == "" || !is_string ($xmldata) || !is_string ($starttagname))
  {
    return false;
  }

  // add < and > for tag name
  if (@substr_count ($starttagname, "<") == 0 && @substr_count ($starttagname, ">") == 0) $starttagname = "<".trim ($starttagname).">";

  // hold starttag in buffer
  $buffer = $starttagname;

  // define endtag
  if (@substr_count ($starttagname, " ") >= 1) $endtagname = "</".substr ($starttagname, 1, strpos ($starttagname, " ") - 1).">";
  elseif (@substr_count ($starttagname, "*") >= 1) $endtagname = "</".substr ($starttagname, 1, strpos ($starttagname, "*") - 1).">";
  else $endtagname = "</".substr ($starttagname, 1);

  // manipulate starttag if wild card character is used for attribute
  if (@substr_count ($starttagname, "*") >= 1) 
  {
    $starttagname = trim (substr ($starttagname, 0, strpos ($starttagname, "*")));
    $wildcard = true;
  }
  else $wildcard = false; 

  // if condition is set
  if ($startcondtag != "")
  {
    $condvalue = trim ($condvalue);

    // check if wild card characters are used in condvalue
    if ($condvalue != "" && $condvalue[0] == "*")
    {
      $wc_begin = true;
      $condvalue = substr ($condvalue, 1);
    }
    else $wc_begin = false;

    if ($condvalue != "" && $condvalue[strlen ($condvalue) - 1] == "*") 
    {
      $wc_end = true;
      $condvalue = substr ($condvalue, 0, strlen ($condvalue) - 1);
    }
    else $wc_end = false;

    // extract content between tags
    $record_array = explode ($starttagname, $xmldata);

    if (!empty ($record_array) && is_array ($record_array) && sizeof ($record_array) > 0)
    {
      // do not accept first record (it is not a part of the query result! may even be empty, if starttag is the first value in the file)
      $i = -1;
      $j = 0;

      foreach ($record_array as $record)
      {
        if ($i != -1 && $record != "")
        {
          if (substr_count ($record, $endtagname) > 0) list ($content_record, $rest) = explode ($endtagname, $record);
          else $content_record = $record;

          // get value of candtag
          $currentvalue_array = getcontent ($content_record, $startcondtag);

          // find all XML-object including a child that fulfils the condition
          if ($currentvalue_array != false)
          { 
            foreach ($currentvalue_array as $currentvalue) 
            {
              $currentvalue = trim ($currentvalue);

              if ($wc_begin == false && $wc_end == false && $currentvalue == $condvalue)
              {
                // manipulate xml-string if wild card character is used for attribute
                if ($wildcard == true) 
                {
                  $content_record = substr ($content_record, strpos ($content_record, ">") + 1);
                }

                $result_set[$j] = $buffer.$content_record.$endtagname;
                $j++;

                break;
              }
              elseif ($wc_begin == true && $wc_end == true && @substr_count ($currentvalue, $condvalue) >= 1)
              {
                // manipulate xml-string if wild card character is used for attribute
                if ($wildcard == true) 
                {
                  $content_record = substr ($content_record, strpos ($content_record, ">") + 1);
                }

                $result_set[$j] = $buffer.$content_record.$endtagname;
                $j++;

                break;
              }
              elseif ($wc_begin == true && $wc_end == false && substr ($currentvalue, strlen ($currentvalue) - strlen ($condvalue)) == $condvalue)
              {
                // manipulate xml-string if wild card character is used for attribute
                if ($wildcard == true) 
                {
                  $content_record = substr ($content_record, strpos ($content_record, ">") + 1);
                }

                $result_set[$j] = $buffer.$content_record.$endtagname;
                $j++;

                break;
              }
              elseif ($wc_begin == false && $wc_end == true && substr ($currentvalue, 0, strlen ($condvalue)) == $condvalue)
              {
                // manipulate xml-string if wild card character is used for attribute
                if ($wildcard == true) 
                {
                  $content_record = substr ($content_record, strpos ($content_record, ">") + 1);
                }

                $result_set[$j] = $buffer.$content_record.$endtagname;
                $j++;

                break;
              }
            }
          }
        }

        $i++;
      }
    }
  }
  // if there is no condition set
  else
  {
    $result_set = getxmlcontent ($xmldata, $starttagname);
  }

  if (isset ($result_set)) return $result_set;
  else return false;
}

// ------------------------------------ selectxmlicontent -------------------------------------

// function: selectxmlicontent()
// input: XML content container [string], tag name of requested XML node [string], tag holding the conditional value inside the given starttagname [string], conditional value [string]
// output: result array with the content of the requested XML node (tag) / false on error

// description:
// CASE-Insensitive version (XML parser are always case-sensitive!)
//
// <tagname>
//    .......
//    <condtag>condvalue</condtag>
//    .......
// </tagname>
//
// Extracts the content between the given $starttagname xml tags where the child xml tag $startcondtag value is equal with the target value $condvalue.
// Wild card character "*" can be used at begin and end of $condvalue.
// Be Aware: $startcondtag must be a child of $starttagname!

function selectxmlicontent ($xmldata, $starttagname, $startcondtag, $condvalue)
{
  // missing input
  if ($xmldata == "" || $starttagname == "" || !is_string ($xmldata) || !is_string ($starttagname))
  {
    return false;
  }

  // add < and > for tag name
  if (@substr_count ($starttagname, "<") == 0 && @substr_count ($starttagname, ">") == 0) $starttagname = "<".trim ($starttagname).">";

  // hold starttag in buffer
  $buffer = $starttagname;

  // define endtag
  if (@substr_count ($starttagname, " ") >= 1) $endtagname = "</".substr ($starttagname, 1, strpos ($starttagname, " ") - 1).">";
  elseif (@substr_count ($starttagname, "*") >= 1) $endtagname = "</".substr ($starttagname, 1, strpos ($starttagname, "*") - 1).">";
  else $endtagname = "</".substr ($starttagname, 1);

  // manipulate starttag if wild card character is used for attribute
  if (@substr_count ($starttagname, "*") >= 1) 
  {
    $starttagname = trim (substr ($starttagname, 0, strpos ($starttagname, "*")));
    $wildcard = true;
  }
  else $wildcard = false;

  // for case insensitive explode we need to replace the tags
  $xmldata = str_ireplace ($starttagname, strtolower ($starttagname), $xmldata);
  $xmldata = str_ireplace ($endtagname, strtolower ($endtagname), $xmldata);

  // if condition is set
  if ($startcondtag != "")
  {
    $condvalue = trim (strtolower ($condvalue));

    // check if wild card characters are used in condvalue
    if ($condvalue != "" && $condvalue[0] == "*") 
    {
      $wc_begin = true;
      $condvalue = substr ($condvalue, 1);
    }
    else $wc_begin = false;

    if ($condvalue != "" && $condvalue[strlen ($condvalue) - 1] == "*") 
    {
      $wc_end = true;
      $condvalue = substr ($condvalue, 0, strlen ($condvalue) - 1);
    }
    else $wc_end = false;

    // extract content between tags
    $record_array = explode ($starttagname, $xmldata);

    if (!empty ($record_array) && is_array ($record_array) && sizeof ($record_array) > 0)
    {
      // do not accept first record (it is not a part of the query result! may even be empty, if starttag is the first value in the file)
      $i = -1;
      $j = 0;

      foreach ($record_array as $record)
      {
        if ($i != -1 && $record != "")
        {
          if (substr_count ($record, $endtagname) > 0) list ($content_record, $rest) = explode (strtolower ($endtagname), $record);
          else $content_record = $record;

          // get value of candtag
          $currentvalue_array = geticontent ($content_record, $startcondtag);

          // find all XML-object including a child that fulfils the condition
          if ($currentvalue_array != false)
          { 
            foreach ($currentvalue_array as $currentvalue) 
            {
              $currentvalue = trim (strtolower ($currentvalue));

              if ($wc_begin == false && $wc_end == false && $currentvalue == $condvalue)
              {
                // manipulate xml-string if wild card character is used for attribute
                if ($wildcard == true) 
                {
                  $content_record = substr ($content_record, strpos ($content_record, ">") + 1);
                }

                $result_set[$j] = $buffer.$content_record.$endtagname;
                $j++;

                break;
              }
              elseif ($wc_begin == true && $wc_end == true && @substr_count ($currentvalue, $condvalue) >= 1)
              {
                // manipulate xml-string if wild card character is used for attribute
                if ($wildcard == true) 
                {
                  $content_record = substr ($content_record, strpos ($content_record, ">") + 1);
                }

                $result_set[$j] = $buffer.$content_record.$endtagname;
                $j++;

                break;
              }
              elseif ($wc_begin == true && $wc_end == false && substr ($currentvalue, strlen ($currentvalue) - strlen ($condvalue)) == $condvalue)
              {
                // manipulate xml-string if wild card character is used for attribute
                if ($wildcard == true) 
                {
                  $content_record = substr ($content_record, strpos ($content_record, ">") + 1);
                }

                $result_set[$j] = $buffer.$content_record.$endtagname;
                $j++;

                break;
              }
              elseif ($wc_begin == false && $wc_end == true && substr ($currentvalue, 0, strlen ($condvalue)) == $condvalue)
              {
                // manipulate xml-string if wild card character is used for attribute
                if ($wildcard == true) 
                {
                  $content_record = substr ($content_record, strpos ($content_record, ">") + 1);
                }

                $result_set[$j] = $buffer.$content_record.$endtagname;
                $j++;

                break;
              }
            }
          }
        }

        $i++;
      }
    }
  }
  // if there is no condition set
  else
  {
    $result_set = getxmlicontent ($xmldata, $starttagname);
  }

  if (isset ($result_set)) return $result_set;
  else return false;
}

// ------------------------------- deletecontent -------------------------------------------

// function: deletecontent()
// input: XML content container [string], tag name of requested XML node [string], tag holding the conditional value inside the given starttagname [string] (optional), conditional value [string] (optional)
// output: XML content container / false on error

// description:
// <tagname>
//    <condtag>condvalue</condtag>
// </tagname>
//
// Deletes the whole xml content including <tagname>.
// Wild card character "*" can be used at begin and end of $condvalue.

function deletecontent ($xmldata, $starttagname, $startcondtag="", $condvalue="")
{
  // if filedata contains no content
  if ($xmldata == "" || $starttagname == "" || !is_string ($xmldata) || !is_string ($starttagname))
  {
    return false;
  }

  // if condition is set
  if ($startcondtag != "")
  {
    // extract content between tags
    $record_array = selectxmlcontent ($xmldata, $starttagname, $startcondtag, $condvalue);
  }
  // else: if there is no condition set => every child will be deleted
  else 
  {
    // extract content between tags
    $record_array = getxmlcontent ($xmldata, $starttagname);
  }

  // delete childs 
  if (!empty ($record_array) && is_array ($record_array) && sizeof ($record_array) > 0)
  {
    foreach ($record_array as $record)
    {
      // PHP will transform newlines in the correct way for each OS, so deleting childs
      // and their appending newline will require to take different cases into account
      $xmldata = str_replace ($record."\r\n", "", $xmldata);  // WIN32
      $xmldata = str_replace ($record."\n", "", $xmldata);    // UNIX
      $xmldata = str_replace ($record."\r", "", $xmldata);    // MacOS
    }
  }

  if ($xmldata != false) return $xmldata;
  else return false;
}

// ------------------------------- deleteicontent -------------------------------------------

// function: deleteicontent()
// input: XML content container [string], tag name of requested XML node [string], tag holding the conditional value inside the given starttagname [string] (optional), conditional value [string] (optional)
// output: XML content container / false on error

// description:
// CASE-Insensitive version (XML parser are always case-sensitive!)
//
// <tagname>
//    <condtag>condvalue</condtag>
// </tagname>
//
// Deletes the whole xml content including <tagname>.
// Wild card character "*" can be used at begin and end of $condvalue.

function deleteicontent ($xmldata, $starttagname, $startcondtag="", $condvalue="")
{
  // if filedata contains no content
  if ($xmldata == "" || $starttagname == "" || !is_string ($xmldata) || !is_string ($starttagname))
  {
    return false;
  }

  // if condition is set
  if ($startcondtag != "")
  {
    // extract content between tags
    $record_array = selectxmlicontent ($xmldata, $starttagname, $startcondtag, $condvalue);
  }
  // else: if there is no condition set => every child will be deleted
  else 
  {
    // extract content between tags
    $record_array = getxmlicontent ($xmldata, $starttagname);
  }

  // delete childs 
  if (!empty ($record_array) && is_array ($record_array) && sizeof ($record_array) > 0)
  {
    foreach ($record_array as $record)
    {
      // PHP will transform newlines in the correct way for each OS, so deleting childs
      // and their appending newline will require to take different cases into account
      $xmldata = str_replace ($record."\r\n", "", $xmldata);  // WIN32
      $xmldata = str_replace ($record."\n", "", $xmldata);    // UNIX
      $xmldata = str_replace ($record."\r", "", $xmldata);    // MacOS
    }
  }

  if ($xmldata != false) return $xmldata;
  else return false;
}


// --------------------------------- setcontent --------------------------------------------

// function: setcontent()
// input: XML content container [string], parent tag name [string], tag name of XML node for the new content [string], new XML node to be inserted, 
//        tag holding the conditional value inside the given starttagname [string], conditional value [string], escape content [boolean] (optional) 
// output: XML content container / false on error

// description:
// <parenttagname>
//    <condtag>condvalue</condtag>
//    <tagname>contentnew</tagname>
// </parenttagname>
//
// $xmldata = data string to be parsed
// $startparenttagname = name of the tag that is a parent node of starttagname (necessary if condition has been set!)
// $starttagname = name of the tag (child node)
// $contentnew = the content that will be inserted between the child tags $starttagname
// $startcondtag = child xml tag where condition will be set
// $condvalue = value of the condition
//
// Wild card character "*" can be used at begin and end of $condvalue.

function setcontent ($xmldata, $startparenttagname="", $starttagname="", $contentnew="", $startcondtag="", $condvalue="", $escape_content=false)
{
  // if filedata contains no content
  if (!valid_tagname ($startparenttagname) || !valid_tagname ($starttagname) || !valid_tagname ($startcondtag) || $xmldata == "" || $starttagname == "" || !is_string ($xmldata) || !is_string ($starttagname))
  {
    return false;
  }

  // add < and > for tag name
  if (@substr_count ($starttagname, "<") == 0 && @substr_count ($starttagname, ">") == 0) $starttagname = "<".trim ($starttagname).">";

  // define endtag
  if (@substr_count ($starttagname, " ") >= 1) $endtagname = "</".substr ($starttagname, 1, strpos ($starttagname, " ")).">";
  else $endtagname = "</".substr ($starttagname, 1);

  // if xml data has no parents => $startparenttagname + condition is left empty
  // => just replace the old content
  if ($startparenttagname != "")
  {
    // get content between <parenttagname> including parenttags
    $record_array = selectxmlcontent ($xmldata, $startparenttagname, $startcondtag, $condvalue);
  }
  elseif ($startparenttagname == "" && $startcondtag == "")
  {
    // get content together with <tagname>
    $record_array = getxmlcontent ($xmldata, $starttagname);
  }
  else
  {
    return false;
  }

  if (!empty ($record_array) && is_array ($record_array) && sizeof ($record_array) > 0)
  {
    foreach ($record_array as $record)
    {
      // exclude the old content
      list ($xmlstringstart, $xmlstringrest) = explode ($starttagname, $record);
      if (!empty ($xmlstringrest)) list ($contentold, $xmlstringend) = explode ($endtagname, $xmlstringrest);

      // check if starttagname was found
      if (!empty ($xmlstringrest))
      {
        // escape content
        if (!empty ($escape_content))
        {
          if (substr (trim ($contentnew), 0, 9) == "<![CDATA[" && substr (trim ($contentnew), -3) == "]]>")
          {
            $contentnew = trim ($contentnew);

            // remove CDATA
            $contentnew = substr ($contentnew, 9, (strlen($contentnew) - 12));

            // escape < and >
            $contentnew = str_replace ("<", "&lt;", $contentnew);
            $contentnew = str_replace (">", "&gt;", $contentnew);

            // add CDATA
            $contentnew = "<![CDATA[".$contentnew."]]>";
          }
          else
          {
            // escape < and >
            $contentnew = str_replace ("<", "&lt;", $contentnew);
            $contentnew = str_replace (">", "&gt;", $contentnew);
          }
        }

        // build xml data including the new content $contentnew
        $record_new = $xmlstringstart.$starttagname.$contentnew.$endtagname.$xmlstringend;

        // replace/update the old xml content with the new xml content in $xmldata
        $xmldata = str_replace ($record, $record_new, $xmldata);
      }
      else
      {
        return false;
      }
    }

    return $xmldata;
  }
  else
  {
    return false;
  }
}

// --------------------------------- seticontent --------------------------------------------

// function: seticontent()
// input: XML content container [string], parent tag name [string], tag name of XML node for the new content [string], new XML node to be inserted [string], 
//        tag holding the conditional value inside the given starttagname [string], conditional value [string], escape content [boolean] (optional) 
// output: XML content container / false on error

// description:
// CASE-Insensitive version (XML parser are always case-sensitive!)
//
// <parenttagname>
//    <condtag>condvalue</condtag>
//    <tagname>contentnew</tagname>
// </parenttagname>
//
// $xmldata = data string to be parsed
// $startparenttagname = name of the tag that is the parent node of starttagname (necessary if condition has been set!)
// $starttagname = name of the tag (child node)
// $contentnew = the content that will be inserted between the child tags $starttagname
// $startcondtag = child xml tag where condition will be set
// $condvalue = value of the condition
//
// Wild card character "*" can be used at begin and end of $condvalue. 
function seticontent ($xmldata, $startparenttagname="", $starttagname="", $contentnew="", $startcondtag="", $condvalue="", $escape_content=false)
{
  // if filedata contains no content
  if (!valid_tagname ($startparenttagname) || !valid_tagname ($starttagname) || !valid_tagname ($startcondtag) || $xmldata == "" || $starttagname == "" || !is_string ($xmldata) || !is_string ($starttagname))
  {
    return false;
  }

  // add < and > for tag name
  if (@substr_count ($starttagname, "<") == 0 && @substr_count ($starttagname, ">") == 0) $starttagname = "<".trim ($starttagname).">";

  // define endtag
  if (@substr_count ($starttagname, " ") >= 1) $endtagname = "</".substr ($starttagname, 1, strpos ($starttagname, " ")).">";
  else $endtagname = "</".substr ($starttagname, 1);

  // if xml data has no parents => $startparenttagname + condition is left empty
  // => just replace the old content
  if ($startparenttagname != "")
  {
    // get content between <parenttagname> including parenttags
    $record_array = selectxmlicontent ($xmldata, $startparenttagname, $startcondtag, $condvalue);
  }
  elseif ($startparenttagname == "" && $startcondtag == "")
  {
    // get content together with <tagname>
    $record_array = getxmlicontent ($xmldata, $starttagname);
  }
  else
  {
    return false;
  }

  if (!empty ($record_array) && is_array ($record_array) && sizeof ($record_array) > 0)
  {
    foreach ($record_array as $record)
    {
      // for case insensitive explode we need to replace the tags
      $record = str_ireplace ($starttagname, strtolower ($starttagname), $record);
      $record = str_ireplace ($endtagname, strtolower ($endtagname), $record);

      // exclude the old content
      list ($xmlstringstart, $xmlstringrest) = explode (strtolower ($starttagname), $record);
      if (!empty ($xmlstringrest)) list ($contentold, $xmlstringend) = explode (strtolower ($endtagname), $xmlstringrest);

      // check if $starttagname was found
      if (!empty ($xmlstringrest))
      {
        // escape content
        if (!empty ($escape_content))
        {
          if (substr (trim ($contentnew), 0, 9) == "<![CDATA[" && substr (trim ($contentnew), -3) == "]]>")
          {
            $contentnew = trim ($contentnew);

            // remove CDATA
            $contentnew = substr ($contentnew, 9, (strlen($contentnew) - 12));

            // escape < and >
            $contentnew = str_replace ("<", "&lt;", $contentnew);
            $contentnew = str_replace (">", "&gt;", $contentnew);

            // add CDATA
            $contentnew = "<![CDATA[".$contentnew."]]>";
          }
          else
          {
            // escape < and >
            $contentnew = str_replace ("<", "&lt;", $contentnew);
            $contentnew = str_replace (">", "&gt;", $contentnew);
          }
        }
        
        // build xml data including the new content $contentnew
        $record_new = $xmlstringstart.$starttagname.$contentnew.$endtagname.$xmlstringend;

        // replace/update the old xml content with the new xml content in $xmldata
        $xmldata = str_replace ($record, $record_new, $xmldata);
      }
      else
      {
        return false;
      }
    }

    return $xmldata;
  }
  else
  {
    return false;
  }
}

// --------------------------------- setcontent_fast --------------------------------------------

// function: setcontent_fast()
// input: XML content container [string], parent tag name [string], tag name of XML node for the new content [string], new XML node to be inserted, 
//        tag holding the conditional value inside the given starttagname [string], conditional value [string], escape content [boolean] (optional) 
// output: XML content container / false on error

// description:
// function designed for link management, extremely fast but with limitations (only CASE-Sensitive!)
//
// <parenttagname>
//    <condtag>condvalue</condtag>
//    <tagname>contentnew</tagname>
// </parenttagname>
//
// $xmldata = data string to be parsed
// $startparenttagname = name of the tag that is the parent node of starttagname (necessary if condition has been set!)
// $starttagname = name of the tag (child node)
// $contentnew = the content that will be inserted between the child tags $starttagname
// $startcondtag = child xml tag where condition will be set
// $condvalue = value of the condition
//
// Wild card character "*" can be used at begin and end of $condvalue.

function setcontent_fast ($xmldata, $startparenttagname="", $starttagname="", $contentnew="", $startcondtag="", $condvalue="", $escape_content=false)
{
  // if filedata contains no content
  if (!valid_tagname ($startparenttagname) || !valid_tagname ($starttagname) || !valid_tagname ($startcondtag) || $xmldata == "" || $starttagname == "" || !is_string ($xmldata) || !is_string ($starttagname))
  {
    return false;
  }

  // add < and > for tag name
  if (@substr_count ($startparenttagname, "<") == 0 && @substr_count ($startparenttagname, ">") == 0) $startparenttagname = "<".trim ($startparenttagname).">";
  if (@substr_count ($starttagname, "<") == 0 && @substr_count ($starttagname, ">") == 0) $starttagname = "<".trim ($starttagname).">";

  // define endtag
  if (@substr_count ($starttagname, " ") >= 1) $endtagname = "</".substr ($starttagname, 1, strpos ($starttagname, " ")).">";
  else $endtagname = "</".substr ($starttagname, 1);

  // if xml data has no parents => $startparenttagname + condition is left empty
  // => just replace the old content
  if ($startparenttagname != "")
  {
    // get content between <parenttagname>
    $record_array = explode ($startparenttagname, $xmldata);
  }
  else
  {
    return false;
  }

  if (!empty ($record_array) && is_array ($record_array) && sizeof ($record_array) > 0)
  {
    $i = 0;

    foreach ($record_array as $record)
    {
      // check if $starttagname was found
      if (@substr_count ($record, $condvalue) == 1)
      {
        $contentold = getxmlcontent ($record, $starttagname);

        // escape content
        if (!empty ($escape_content))
        {
          if (substr (trim ($contentnew), 0, 9) == "<![CDATA[" && substr (trim ($contentnew), -3) == "]]>")
          {
            $contentnew = trim ($contentnew);

            // remove CDATA
            $contentnew = substr ($contentnew, 9, (strlen($contentnew) - 12));

            // escape < and >
            $contentnew = str_replace ("<", "&lt;", $contentnew);
            $contentnew = str_replace (">", "&gt;", $contentnew);

            // add CDATA
            $contentnew = "<![CDATA[".$contentnew."]]>";
          }
          else
          {
            // escape < and >
            $contentnew = str_replace ("<", "&lt;", $contentnew);
            $contentnew = str_replace (">", "&gt;", $contentnew);
          }
        }

        // replace/update the old xml content with the new xml content in $xmldata
        $record_array[$i] = str_replace ($contentold, $starttagname.$contentnew.$endtagname, $record);
      }

      $i++;
    }

    // implode $records
    $xmldata = implode ($startparenttagname, $record_array);

    return $xmldata;
  }
  else
  {
    return false;
  }
}

// --------------------------------------- updatecontent -----------------------------------------

// function: updatecontent()
// input: XML content container [string], XML node to be replaced [string], new XML node [string]
// output: XML content container / false on error

// description:
// Updates a given xml string $xmlnode in $xmldata with the content $xmlnodenew.
// This method provides a faster way to update xml nodes when the node was selected before.

function updatecontent ($xmldata, $xmlnode, $xmlnodenew)
{
  if ($xmldata == "" || $xmlnode == "" || !is_string ($xmldata))
  {
    return false;
  }
  else
  {
    $xmldata = str_replace ($xmlnode, $xmlnodenew, $xmldata);
    return $xmldata;
  }
}

// --------------------------------------- insertcontent -----------------------------------------

// function: insertcontent()
// input: XML content container [string], XML node to be inserted in starttagname [string], tag name of the parent XML node [string]
// output: XML content container / false on error

// description:
// .....................
//    .......................
//    <tagname>                      <- list start
//       ......................
//       ......................
//       insertxmldata               <- insertxmldata
//    </tagname>                     <- list end
// .....................
//
// Inserts $insertxmldata string at the end of all child between the parent $tagname .

function insertcontent ($xmldata, $insertxmldata, $starttagname)
{
  // if variables contain no content
  if (!valid_tagname ($starttagname) || $xmldata == "" || !is_string ($xmldata))
  {
    return false;
  }
  elseif ($insertxmldata == "" || !is_string ($insertxmldata))
  {
    return $xmldata;
  }

  // add < and > for tag name
  if (substr_count ($starttagname, "<") == 0 && substr_count ($starttagname, ">") == 0) $starttagname = "<".trim ($starttagname).">";

  // define endtag
  if (substr_count ($starttagname, " ") > 0) $endtagname = "</".substr ($starttagname, 1, strpos ($starttagname, " ")).">";
  else $endtagname = "</".substr ($starttagname, 1);

  if ($starttagname != "" && strpos ($xmldata, $endtagname) > 0)
  {
    // split xmldata
    $block_array = explode ($endtagname, $xmldata);

    // build xml data including the new xml node
    $xmldata_new = implode (chop ($insertxmldata)."\n".$endtagname, $block_array);

    return $xmldata_new;
  }
  else
  {
    // build xml data including the new content $contentnew at the end of xmldata
    $xmldata_new = chop ($xmldata)."\n".chop ($insertxmldata)."\n";

    return $xmldata_new;
  }
}

// --------------------------------------- inserticontent -----------------------------------------

// function: inserticontent()
// input: XML content container [string], XML node to be inserted in starttagname [string], tag name of the parent XML node [string]
// output: XML content container / false on error

// description:
// CASE-Insensitive version (XML parser are always case-sensitive!)
//
// .....................
//    .......................
//    <tagname>                      <- list start
//       ......................
//       ......................
//       insertxmldata               <- insertxmldata
//    </tagname>                     <- list end
// .....................
//
// Inserts $insertxmldata string at the end of all child between the parent $tagname.

function inserticontent ($xmldata, $insertxmldata, $starttagname)
{
  // if variables contain no content
  if (!valid_tagname ($starttagname) || $xmldata == "" || $insertxmldata == "" || !is_string ($xmldata) || !is_string ($insertxmldata))
  {
    return false;
  }

  // add < and > for tag name
  if (@substr_count ($starttagname, "<") == 0 && @substr_count ($starttagname, ">") == 0) $starttagname = "<".trim ($starttagname).">";

  // define endtag
  if (@substr_count ($starttagname, " ") > 0) $endtagname = "</".substr ($starttagname, 1, strpos ($starttagname, " ")).">";
  else $endtagname = "</".substr ($starttagname, 1);

  // for case insensitive explode we need to replace the tags
  $xmldata = str_ireplace ($starttagname, strtolower ($starttagname), $xmldata);
  $xmldata = str_ireplace ($endtagname, strtolower ($endtagname), $xmldata);

  if ($starttagname != "" && strpos ($xmldata, $endtagname) > 0)
  {
    // split xmldata
    $block_array = explode (strtolower ($endtagname), $xmldata);

    // build xml data including the new xml node
    $xmldata_new = implode (chop ($insertxmldata)."\n".$endtagname, $block_array);

    return $xmldata_new;
  }
  else
  {
    // build xml data including the new content $contentnew at the end of xmldata
    $xmldata_new = chop ($xmldata)."\n".chop ($insertxmldata)."\n";

    return $xmldata_new;
  }
}

// ------------------------------------ addcontent ---------------------------------------

// function: addcontent()
// input: XML content container [string], xml node to be inserted [string], grandparent tag name [string], tag holding the conditional value inside the given starttagname [string], conditional value [string], parent tag name [string], tag name of XML node for the new content [string], new XML node to be inserted [string]
// output: XML content container / false on error

// description:
// <grandtagname>
//    <condtag>condvalue</condtag>
//    <parenttagname>                      <- list start
//       ......................
//       ......................
//       ......................            }
//       <tagname>contentnew</tagname>     } <- sub_xmldata
//       ......................            }
//    </parenttagname>                     <- list end
// </grandtagname>
//
// $xmldata = data string to be parsed
// $sub_xmldata = xml node to be inserted
// $startgrandtagname (optional) = name of the grand xml tag of parent xml tag where (article)
// $startcondtag (optional) = xml tag inside the parent xml tags where condition will be set
// $condvalue (optional) = value of the condition
// $startparenttagname (optional) = name of the parent xml tag where the xml subschema should be added (list)
// $starttagname (optional) = name of the tag (child)
// $contentnew (optional) = the content that will be inserted between the child tags

function addcontent ($xmldata, $sub_xmldata, $startgrandtagname, $startcondtag, $condvalue, $startparenttagname, $starttagname, $contentnew)
{
  // if variables contain no content
  if (!valid_tagname ($startgrandtagname) || !valid_tagname ($startparenttagname) || !valid_tagname ($starttagname) || !valid_tagname ($startcondtag) || $xmldata == "" || $sub_xmldata == "" || !is_string ($xmldata) || !is_string ($sub_xmldata))
  {
    return false;
  }

  // add < and > for tag name
  if (@substr_count ($startparenttagname, "<") == 0 && @substr_count ($startparenttagname, ">") == 0) $startparenttagname = "<".trim ($startparenttagname).">";
  if (@substr_count ($starttagname, "<") == 0 && @substr_count ($starttagname, ">") == 0) $starttagname = "<".trim ($starttagname).">";

  // define endtag
  if (@substr_count ($starttagname, " ") > 0) $endtagname = "</".substr ($starttagname, 1, strpos ($starttagname, " ")).">";
  else $endtagname = "</".substr ($starttagname, 1);

  if (@substr_count ($starttagname, " ") > 0) $endparenttagname = "</".substr ($startparenttagname, 1, strpos ($startparenttagname, " ")).">";
  else $endparenttagname = "</".substr ($startparenttagname, 1);

  // insert the new content into the sub xml data
  if ($starttagname != "" && $contentnew != "")
  {
    $sub_xmldata_new = setcontent ($sub_xmldata, "", $starttagname, $contentnew, "", "");
    if ($sub_xmldata_new == false)
    {
      return false;
    }
  }
  else $sub_xmldata_new = $sub_xmldata;

  // ------------------------ differntiate parameter cases for better performance ---------------------
  // if parent xml tag and grand xml tag is not set => just append the new xml data and exit
  if ($startgrandtagname == "" && $startparenttagname == "")
  {
    // build xml data including the new content $contentnew
    $xmldata = insertcontent ($xmldata, $sub_xmldata_new, "");
    return $xmldata;
  }
  elseif ($startgrandtagname == "" && $startparenttagname != "")
  {
    // build xml data including the new content $contentnew
    $xmldata = insertcontent ($xmldata, $sub_xmldata_new, $startparenttagname);
    return $xmldata;
  }
  elseif ($startgrandtagname != "")
  {
    // extract content between xml tags $starttagname
    $grand_record_array = selectcontent ($xmldata, $startgrandtagname, $startcondtag, $condvalue);
    // if xml data was found
    if ($grand_record_array != false && sizeof ($grand_record_array) >= 1)
    {
      foreach ($grand_record_array as $grand_record)
      {
        $grand_record_new = insertcontent ($grand_record, $sub_xmldata_new, $startparenttagname);
        // update into content
        $xmldata = str_replace ($grand_record, $grand_record_new, $xmldata);
      }

      return $xmldata;
    }
    else
    {
      return false;
    }
  }
}

// ------------------------------------ addicontent ---------------------------------------

// function: addicontent()
// input: XML content container [string], xml node to be inserted [string], grandparent tag name [string], tag holding the conditional value inside the given starttagname [string], conditional value [string], parent tag name [string], tag name of XML node for the new content [string], new XML node to be inserted [string]
// output: XML content container / false on error

// description:
// CASE-Insensitive version (XML parser are always case-sensitive!)
//
// <grandtagname>
//    <condtag>condvalue</condtag>
//    <parenttagname>                      <- list start
//       ......................
//       ......................
//       ......................            }
//       <tagname>contentnew</tagname>     } <- sub_xmldata
//       ......................            }
//    </parenttagname>                     <- list end
// </grandtagname>
//
// $xmldata = data string to be parsed
// $sub_xmldata = xml subschema to be inserted
// $startgrandtagname (optional) = name of the grand xml tag of parent xml tag where (article)
// $startcondtag (optional) = xml tag inside the parent xml tags where condition will be set
// $condvalue (optional) = value of the condition
// $startparenttagname (optional) = name of the parent xml tag where the xml subschema should be added (list)
// $starttagname (optional) = name of the tag (child)
// $contentnew (optional) = the content that will be inserted between the child tags

function addicontent ($xmldata, $sub_xmldata, $startgrandtagname, $startcondtag, $condvalue, $startparenttagname, $starttagname, $contentnew)
{
  // if variables contain no content
  if (!valid_tagname ($startgrandtagname) || !valid_tagname ($startparenttagname) || !valid_tagname ($starttagname) || !valid_tagname ($startcondtag) || $xmldata == "" || $sub_xmldata == "" || !is_string ($xmldata) || !is_string ($sub_xmldata))
  {
    return false;
  }

  // add < and > for tag name
  if (@substr_count ($startparenttagname, "<") == 0 && @substr_count ($startparenttagname, ">") == 0) $startparenttagname = "<".trim ($startparenttagname).">";
  if (@substr_count ($starttagname, "<") == 0 && @substr_count ($starttagname, ">") == 0) $starttagname = "<".trim ($starttagname).">";

  // define endtag
  if (@substr_count ($starttagname, " ") > 0) $endtagname = "</".substr ($starttagname, 1, strpos ($starttagname, " ")).">";
  else $endtagname = "</".substr ($starttagname, 1);

  if (@substr_count ($starttagname, " ") > 0) $endparenttagname = "</".substr ($startparenttagname, 1, strpos ($startparenttagname, " ")).">";
  else $endparenttagname = "</".substr ($startparenttagname, 1);

  // insert the new content into the sub xml data
  if ($starttagname != "" && $contentnew != "")
  {
    $sub_xmldata_new = seticontent ($sub_xmldata, "", $starttagname, $contentnew, "", "");
    if ($sub_xmldata_new == false)
    {
      return false;
    }
  }
  else $sub_xmldata_new = $sub_xmldata;

  // ------------------------ differntiate parameter cases for better performance ---------------------
  // if parent xml tag and grand xml tag is not set => just append the new xml data and exit
  if ($startgrandtagname == "" && $startparenttagname == "")
  {
    // build xml data including the new content $contentnew
    $xmldata = inserticontent ($xmldata, $sub_xmldata_new, "");
    return $xmldata;
  }
  elseif ($startgrandtagname == "" && $startparenttagname != "")
  {
    // build xml data including the new content $contentnew
    $xmldata = inserticontent ($xmldata, $sub_xmldata_new, $startparenttagname);
    return $xmldata;
  }
  elseif ($startgrandtagname != "")
  {
    // extract content between xml tags $starttagname
    $grand_record_array = selecticontent ($xmldata, $startgrandtagname, $startcondtag, $condvalue);
    // if xml data was found
    if ($grand_record_array != false && sizeof ($grand_record_array) >= 1)
    {
      foreach ($grand_record_array as $grand_record)
      {
        $grand_record_new = insertcontent ($grand_record, $sub_xmldata_new, $startparenttagname);
        // update into content
        $xmldata = str_replace ($grand_record, $grand_record_new, $xmldata);
      }

      return $xmldata;
    }
    else
    {
      return false;
    }
  }
}
?>