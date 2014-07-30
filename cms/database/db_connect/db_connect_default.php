<?php
// ================================================ db connect ================================================
// this file allows you to access a database using the full PHP functionality.
// you can read or write data from or into a database:

// ============================================ read from database ============================================
// the following parameter values are passed to each function for retrieving data from the database:
// name of the site: $site [string]
// name of the content container: $container_id [string] (is unique inside hyperCMS over all sites)
// content container: $container_content [XML-string]
// identification name: $id [string]  
// name of the user who is editing the container [string]

  // ------------------------------------------------ article -------------------------------------------------
  // if content is article
  function db_read_article ($site, $container_id, $container_content, $art_id, $user)
  {
    //-------------------------------------------------------------------------------------------------------------------
    // input variables: $art_id [string], $user [string]
    // return value: $article [array]
    //               the array must exactly look like this:
    //               $article[arttitle], $article[artstatus], $article[artdatefrom], $article[artdateto]
    //               constraints/accepted values for article status:
    //               $article[artstatus] = active
    //               $article[artstatus] = inactive
    //               $article[artstatus] = timeswitched
    //               format for dates:
    //               artdatefrom [yyyy-mm-dd hh:mm]
    //               artdateto [yyyy-mm-dd hh:mm]
    // note: special characters in arttitle are escaped into their html/xml equivalents
    //-------------------------------------------------------------------------------------------------------------------  
 
 
    // set values
    $article['title'] = "";
    $article['status'] = "";
    $article['datefrom'] = "";
    $article['dateto'] = ""; 
 
    // if successful
    return $article;
  }

  // ------------------------------------------------- text ---------------------------------------------------
  // if content is text
  function db_read_text ($site, $container_id, $container_content, $id, $art_id, $user)
  {
    //---------------------------------------------------------------------------------------------------------
    // input variables: $id [string], optional: $art_id [string], $user [string]
    // return value: $text [array]
    //               the array must exactly look like this:
    //               $text[text], optional: $text[type]
    //               constraints/accepted values for article type, see note below
    // note: special characters in $text are escaped into their html/xml equivalents.
    //       types defines unformatted, formatted and optional text:
    //       unformatted text: $text[type] = textu 
    //       formatted text: $text[type] = textf
    //       text option: $text[type] = textl
    //---------------------------------------------------------------------------------------------------------  
 
 
    // set values
    $text['text'] = ""; 
    $text['type'] = ""; 
 
    // if successful
    return $text;
  }

  // ------------------------------------------------- media --------------------------------------------------
  // if content is media
  function db_read_media ($site, $container_id, $container_content, $id, $art_id, $user)
  {
    //------------------------------------------------------------------------------------------------------------
    // input variables: $id [string], optional: $art_id [string], $user [string]
    // return value: $media [array]
    //               the array must exactly look like this: 
    //               $media[file],$media[alttext], $media[align], $media[height], $media[width]  
    //               constraints/accepted values for media file:    
    //               must be an existing file stored in the hyperCMS media database    
    //               constraints/accepted values for media alignment:   
    //               please see any HTML reference
    //               constraints/accepted values for media width and height:
    //               must be of type integer  
    // note: special characters in mediaaltext are escaped into their html/xml equivalents
    //------------------------------------------------------------------------------------------------------------  
 
 
    // set values
    $media['file'] = "";
    $media['object'] = "";
    $media['alttext'] = "";
    $media['align'] = ""; 
    $media['height'] = ""; 
    $media['width'] = "";
    
    // if successful
    return $media;
  }

  // -------------------------------------------------- link -------------------------------------------------
  // if content is link
  function db_read_link ($site, $container_id, $container_content, $id, $art_id, $user)
  {
    //--------------------------------------------------------------------------------------------------------
    // input variables: $id [string], optional: $art_id [string], $user [string], $linkhref [string], 
    //                  $linktarget [string], $linktext [string]
    // return value: $link [array]
    //               the array must exactly look like this:  
    //               $link[href], $link[target], $link[text] 
    //               constraints/accepted values for link target:   
    //               please see any HTML reference    
    // note: special characters in linktext are escaped into their html/xml equivalents
    //--------------------------------------------------------------------------------------------------------  
 
 
    // set values
    $link['href'] = "";
    $link['target'] = "";
    $link['text'] = "";
 
    // if successful
    return $link;
  }

  // ------------------------------------------------ component ----------------------------------------------
  // if content is component
  function db_read_component ($site, $container_id, $container_content, $id, $art_id, $user)    
  {
    //--------------------------------------------------------------------------------------------------------
    // input variables: $id [string], optional: $art_id [string], $user [string], 
    // return value: $component [array]
    //               the array must exactly look like this:  
    //               $component[file], $component[condition], optional: $component[type]      
    // note: you can decide between single and multiple components:
    //       single component: $type = components
    //       multi component: $type = componentm
    //       multi components must be seperated using the delimiter "|"
    //--------------------------------------------------------------------------------------------------------- 
 
 
    // set values
    $component['file'] = "";
    $component['condition'] = "";
    $component['type'] = "";
 
    // if successful
    return $component;
  }

  // ------------------------------------------- meta information ---------------------------------------------
  // if content is meta information
  function db_read_metadata ($site, $container_id, $container_content, $id, $user)   
  {
    //---------------------------------------------------------------------------------------------------------
    // input variables: $id [string], $user [string]
    // return value: $metadata [array]
    //               the array must exactly look like this: 
    //               $metadata[content]
    // note: special characters in $metadata are escaped into their html/xml equivalents
    //---------------------------------------------------------------------------------------------------------
 

    // set value
    $metadata['content'] = "";
     
    // if successful
    return $metadata;
  }
  
  
// =========================================== write into database ============================================
// the following parameter values are passed as important input for all entries:
// name of the site: $site [string]
// name of the content container: $container_id [string] (is unique inside hyperCMS over all sites)
// content container: $container_content [XML-string]
// name of the user who is editing the container [string]

  // ------------------------------------------------ write container -------------------------------------------------
  // write content container
  function db_write_container ($site, $container_id, $container_content, $user)
  {
   
    // if successful
    return true;
  }
?>