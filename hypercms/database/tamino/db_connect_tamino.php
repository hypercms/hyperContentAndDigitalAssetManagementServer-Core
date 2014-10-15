<?php
// ================================================ db connect ================================================
// this file allows you to access a database using the full PHP functionality.
// you can read or write data from or into a database.
// when reading: return false means no data will be read from the database.

// ============================================ read from database ============================================
// the following parameter values are passed to each function for retrieving data from the database:
// name of the tamino collection: $collection [work,live]
// name of the publication: $site [string]
// name of the content container: $container_id [string] (is unique inside hyperCMS over all publications)
// content container: $container_content [XML-string]
// identification name: $id [string]  
// encoding of the XML document: $encoding [string with valid character set name]
// name of the user who is editing the container [string]

  // ------------------------------------------------ container -------------------------------------------------
  // if whole content container should be read (used just for db_connect_tamino.php)
  function db_read_container ($collection, $site, $container_id, $encoding, $user)
  {
    global $mgmt_config;
      
    //-------------------------------------------------------------------------------------------------------------------
    // input variables: see parameters on top
    // return value: $container [array]
    //-------------------------------------------------------------------------------------------------------------------  
    
    if ($encoding == "") $encoding = $mgmt_config[$site]['default_codepage'];
    
    $content = @implode ("", @file ($mgmt_config['url_tamino'].$collection."?_encoding=$encoding&_xql=/container[/container/hyperCMS/contentcontainer=\"$container_id\"]"));

    if ($content != false)
    {
      $inoid = getattribute ($content, "ino:id");
      $encoding = getattribute ($content, "encoding");
      $container_node = getcontent ($content, "<container ino:id=\"$inoid\">");
      
      if ($container_node != false)
      {
        $content = "<?xml version=\"1.0\" encoding=\"$encoding\" ?>\n<container>".$container_node[0]."</container>";   

        // set values
        $container['content'] = $content;

        // if successful
        return $container;        
      }
      else return false; 
    }
    else return false;
  }

  // ------------------------------------------------ article -------------------------------------------------
  // if content is article
  function db_read_article ($collection, $site, $container_id, $container_content, $art_id, $encoding, $user)
  {
    global $mgmt_config;
      
    //-------------------------------------------------------------------------------------------------------------------
    // input variables: $art_id [string], $user [string]
    // return value: $article [array]
    //               the array must exactly look like this:
    //               $article['title'], $article['status'], $article['datefrom'], $article['dateto']
    //               constraints/accepted values for article status:
    //               $article['status'] = active
    //               $article['status'] = inactive
    //               $article['status'] = timeswitched
    //               format for dates:
    //               $article['datefrom'] = [yyyy-mm-dd hh:mm]
    //               $article['dateto'] = [yyyy-mm-dd hh:mm]
    // note: special characters in arttitle are escaped into their html/xml equivalents
    //-------------------------------------------------------------------------------------------------------------------  
    
    if ($encoding == "") $encoding = $mgmt_config[$site]['default_codepage'];

    $id = urlencode ($id);
    $art_id = urlencode ($art_id);
    
    $xquery = $mgmt_config['url_tamino'].$collection."?_encoding=$encoding&_xql=/container/articlecollection[/container/articlecollection/article/article_id=\"$art_id\"%20and%20/container/hyperCMS/contentcontainer=\"$container_id\"]";
    
    $content = @implode ("", @file ($xquery));
 
    if ($content != false)
    {
      $art_node = selectcontent ($content, "<article>", "<article_id>", "$art_id");
      
      if ($art_node != false)
      {
        $title = getcontent ($art_node[0], "<articletitle>");   
        $status = getcontent ($art_node[0], "<articlestatus>"); 
        $datefrom = getcontent ($art_node[0], "<articledatefrom>"); 
        $dateto = getcontent ($art_node[0], "<articledateto>"); 
   
        // set values
        $article['title'] = $title[0];
        $article['status'] = $status[0];
        $article['datefrom'] = $datefrom[0];
        $article['dateto'] = $dateto[0]; 
        
        // if successful
        return $article;        
      }
      else return false; 
    }
    else return false;
  }

  // ------------------------------------------------- text ---------------------------------------------------
  // if content is text
  function db_read_text ($collection, $site, $container_id, $container_content, $id, $art_id, $encoding, $user)
  {
    global $mgmt_config;
  
    //---------------------------------------------------------------------------------------------------------
    // input variables: $id [string], optional: $art_id [string], $user [string]
    // return value: $text [array]
    //               the array must exactly look like this:
    //               $text['text'], optional: $text['type']
    //               constraints/accepted values for article type, see note below
    // note: special characters in $text are escaped into their html/xml equivalents.
    //       types defines unformatted, formatted and optional text:
    //       unformatted text: $text['type'] = textu 
    //       formatted text: $text['type'] = textf
    //       text option: $text['type'] = textl
    //---------------------------------------------------------------------------------------------------------  
 
    if ($encoding == "") $encoding = $mgmt_config[$site]['default_codepage'];

    $id = urlencode ($id);
    $art_id = urlencode ($art_id);
    
    $xquery = $mgmt_config['url_tamino'].$collection."?_encoding=$encoding&_xql=/container/textcollection[/container/textcollection/text/text_id=\"$id\"%20and%20/container/hyperCMS/contentcontainer=\"$container_id\"]";
 
    $content = implode ("", file ($xquery));
 
    if ($content != false)
    {
      $text_node = selectcontent ($content, "<text>", "<text_id>", "$id");
      
      if ($text_node != false)
      {
        $textcontent = getcontent ($text_node[0], "<textcontent>");
     
        // set values
        $text['text'] = $textcontent[0]; 
        $text['type'] = ""; 
        
        // if successful
        return $text;
      }
      else return false;
    }
    else return false;
  }

  // ------------------------------------------------- media --------------------------------------------------
  // if content is media
  function db_read_media ($collection, $site, $container_id, $container_content, $id, $art_id, $encoding, $user)
  {
    global $mgmt_config;
      
    //------------------------------------------------------------------------------------------------------------
    // input variables: $id [string], optional: $art_id [string], $user [string]
    // return value: $cntmedia[array]
    //               the array must exactly look like this: 
    //               $media['file'], $media['alttext'], $media['align'], $media['height'], $media['width']  
    //               constraints/accepted values for media file:    
    //               must be an existing file stored in the hyperCMS media database    
    //               constraints/accepted values for media alignment:   
    //               please see any HTML reference
    //               constraints/accepted values for media width and height:
    //               must be of type integer  
    // note: special characters in mediaaltext are escaped into their html/xml equivalents
    //------------------------------------------------------------------------------------------------------------  
    
    if ($encoding == "") $encoding = $mgmt_config[$site]['default_codepage'];

    $id = urlencode ($id);
    $art_id = urlencode ($art_id);
    
    $xquery = $mgmt_config['url_tamino'].$collection."?_encoding=$encoding&_xql=/container/mediacollection[/container/mediacollection/media/media_id=\"$id\"%20and%20/container/hyperCMS/contentcontainer=\"$container_id\"]";
 
    $content = @implode ("", @file ($xquery)); 
 
    if ($content != false)
    {
      $media_node = selectcontent ($content, "<media>", "<media_id>", "$id");
      
      if ($media_node != false)
      {
        $file = getcontent ($media_node[0], "<mediafile>");
        $alttext = getcontent ($media_node[0], "<mediaalttext>");
        $align = getcontent ($media_node[0], "<mediaalign>");
        $height = getcontent ($media_node[0], "<mediaheight>"); 
        $width = getcontent ($media_node[0], "<mediawidth>");
     
        // set values
        $media['file'] = $file[0];
        $media['alttext'] = $alttext[0];
        $media['align'] = $align[0]; 
        $media['height'] = $height[0]; 
        $media['width'] = $width[0];
        
        // if successful
        return $media;
      }
      else return false; 
    }
    else return false; 
  }

  // -------------------------------------------------- link -------------------------------------------------
  // if content is link
  function db_read_link ($collection, $site, $container_id, $container_content, $id, $art_id, $encoding, $user)
  {
    global $mgmt_config;
      
    //--------------------------------------------------------------------------------------------------------
    // input variables: $id [string, optional: $art_id [string], $user [string], $linkhref [string], 
    //                  $linktarget [string], $linktext [string]
    // return value: $link [array]
    //               the array must exactly look like this:  
    //               $link['href'], $link['target'], $link['text'] 
    //               constraints/accepted values for link target:   
    //               please see any HTML reference    
    // note: special characters in linktext are escaped into their html/xml equivalents
    //--------------------------------------------------------------------------------------------------------  
   
    if ($encoding == "") $encoding = $mgmt_config[$site]['default_codepage'];

    $id = urlencode ($id);
    $art_id = urlencode ($art_id);
    
    $xquery = $mgmt_config['url_tamino'].$collection."?_encoding=$encoding&_xql=/container/linkcollection[/container/linkcollection/link/link_id=\"$id\"%20and%20/container/hyperCMS/contentcontainer=\"$container_id\"]";
 
    $content = @implode ("", @file ($xquery));  
 
    if ($content != false)
    {
      $link_node = selectcontent ($content, "<link>", "<link_id>", "$id");
      
      $href = getcontent ($link_node[0], "<linkhref>");
      $target = getcontent ($link_node[0], "<linktarget>");
      $text = getcontent ($link_node[0], "<linktext>");
   
      // set values
      $link['href'] = $href[0];
      $link['target'] = $target[0];
      $link['text'] = $text[0];
   
      // if successful
      return $link;
    }
    else return false; 
  }

  // ------------------------------------------------ component ----------------------------------------------
  // if content is component
  function db_read_component ($collection, $site, $container_id, $container_content, $id, $art_id, $encoding, $user)    
  {
    global $mgmt_config;
      
    //--------------------------------------------------------------------------------------------------------
    // input variables: $id [string], optional: $art_id [string], $user [string], 
    // return value: $component [array]
    //               the array must exactly look like this:  
    //               $component['file'], $component['condition'], optional: $component['type']      
    // note: you can decide between single and multiple components:
    //       single component: $type = components
    //       multi component: $type = componentm
    //       multi components must be seperated using the delimiter "|"
    //--------------------------------------------------------------------------------------------------------- 
    
    if ($encoding == "") $encoding = $mgmt_config[$site]['default_codepage'];

    $id = urlencode ($id);
    $art_id = urlencode ($art_id);
    
    $xquery = $mgmt_config['url_tamino'].$collection."?_encoding=$encoding&_xql=/container/componentcollection[/container/componentcollection/component/component_id=\"$id\"%20and%20/container/hyperCMS/contentcontainer=\"$container_id\"]";

    $content = @implode ("", @file ($xquery));     
    
    if ($content != false)
    {    
      $comp_node = selectcontent ($content, "<component>", "<component_id>", "$id");     
      
      if ($comp_node != false)
      { 
        $file = getcontent ($comp_node[0], "<componentfiles>"); 
        $condition = getcontent ($comp_node[0], "<componentcond>");   
         
        // set values
        $component['file'] = $file[0];
        $component['condition'] = $condition[0];
        $component['type'] = "";  
             
        // if successful
        return $component;
      }
      else return false; 
    }
    else return false;
  }

  // ------------------------------------------- meta information ---------------------------------------------
  // if content is meta information
  function db_read_metadata ($collection, $site, $container_id, $container_content, $id, $encoding, $user)   
  {
    global $mgmt_config;
      
    //---------------------------------------------------------------------------------------------------------
    // input variables: $id [string], $user [string]
    // return value: $metadata [array]
    //               the array must exactly look like this: 
    //               $metadata['content']
    // note: special characters in $metadata are escaped into their html/xml equivalents
    //---------------------------------------------------------------------------------------------------------
    
    if ($encoding == "") $encoding = $mgmt_config[$site]['default_codepage'];

    $id = urlencode ($id);
    $art_id = urlencode ($art_id);
    
    $content = @implode ("", @file ($mgmt_config['url_tamino'].$collection."?_encoding=$encoding&_xql=/container/head[/container/hyperCMS/contentcontainer=\"$container_id\"]"));  
    
    if ($content != false)
    {    
      $metacontent = getcontent ($content, "<$id>");  
      
      if ($metacontent != false)
      {    
        $metadata['content'] = $metacontent[0];
         
        // if successful
        return $metadata;
      }
      else return false;
    }
    else return false;
  }
  
  
  
// =========================================== write into database ============================================
// the following parameter values are passed as important input for all entries:
// name of the tamino collection: $collection [work,live]
// name of the publication: $site [string]
// name of the content container: $container_id [string] (is unique inside hyperCMS over all publications)
// content container: $container_content [XML-string]
// name of the user who is editing the container [string]

  // ------------------------------------------- write container ---------------------------------------------
  // if whole content container should be written (used just for db_connect_tamino.php)
  function db_write_container ($collection, $site, $container_id, $container_content, $user)   
  {
    global $mgmt_config;

    // get answer from tamino including ino:id of container 
    $answer = @implode ("", @file ($mgmt_config['url_tamino'].$collection."?_xql=/container/@ino:id[/container/hyperCMS/contentcontainer=\"$container_id\"]"));

    // get ino:id
    if ($answer != false) $inoid = getattribute ($answer, "ino:id");
    else $inoid = false;
    
    // get the encoding from the content container
    $encoding = getattribute ($container_content, "encoding");
    if ($encoding == "") $encoding = $mgmt_config[$site]['default_codepage'];
    
    // if container does not exist => insert
    if ($inoid == false)
    {
      $data = array();
      $data["_encoding"] = $encoding;  
      $data["_process"] = $container_content;  
      
      $result = HTTP_Post ($mgmt_config['url_tamino'].$collection."?", $data, "text/xml", $encoding); 
    }
    // if container exists => update
    else
    {
      // set ino:id for container
      $start = strpos ($container_content, "<container") + 10;
      $container_content = substr_replace ($container_content, " ino:id=\"$inoid\"", $start, 0);
    
      $data = array();
      $data["_process"] = $container_content;  
      
      $result = HTTP_Post ($mgmt_config['url_tamino'].$collection."?", $data, "text/xml", $encoding);   
    } 
     
    // if successful
    return true;
  }
  
  
// ============================================ delete from database ============================================
// the following parameter values are passed to each function for deleting data from the database:
// name of the tamino collection: $collection [work,live]
// name of the site: $site [string]
// name of the content container: $container_id [string (is unique inside hyperCMS over all publications) 
// name of the user who is editing the container [string]

  // ------------------------------------------- write container ---------------------------------------------
  // if whole content container should be deleted (used just for db_connect_tamino.php)
  function db_delete_container ($collection, $site, $container_id, $user)   
  {
    global $mgmt_config;
 
    // get answer from tamino including ino:id of container 
    $answer = @implode ("", @file ($mgmt_config['url_tamino'].$collection."?_delete=/container[hyperCMS/contentcontainer=\"$container_id\"]"));
     
    // if successful
    return true;
  }  
?>