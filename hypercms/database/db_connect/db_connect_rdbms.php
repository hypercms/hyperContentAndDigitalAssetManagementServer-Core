<?php
// ================================================ db connect ================================================
// this file handles the data access and storage for relational database management systems. 
  
// ============================================ database functions ============================================

// Class that manages the database access
class hcms_db
{
  private static $_ERR_TYPE = "Not supported type";  
  private $_db = NULL;  
  private $_error = array();
  public $_result = array();

  // Constructor that builds up the database connection
  // $type = Name of the database type
  // $user = Username who has access
  // $pass = Password for the user
  // $db = Name of the database
  // $host = Hostname of the database Server
  // $charset = Charset if applicable
  
  public function __construct ($type, $host, $user, $pass, $db, $charset="")
  {
    switch ($type)
    {
      case 'mysql':
        $this->_db = new mysqli ($host, $user, $pass, $db);
        if (mysqli_connect_error()) die ('Could not connect: ('.mysqli_connect_errno().') '.mysqli_connect_error());
        
        if ($charset != "") $this->_db->set_charset ($charset);
        else $this->_db->set_charset ("utf8");
        
        // set time zone
        $offset = offsettime();
        $this->_db->query ("SET time_zone='".$offset."'");

        // set sql_mode to TRADITIONAL
        $this->_db->query ("SET SESSION sql_mode = 'STRICT_TRANS_TABLES, STRICT_ALL_TABLES, NO_AUTO_CREATE_USER, NO_ENGINE_SUBSTITUTION'");

        break;
      case 'odbc':
        $this->_db = odbc_connect ($db, $user, $pass, SQL_CUR_USE_ODBC);
        if ($this->_db == false) die ('Could not connect to odbc');
        
        break;
      default:
        die (self::$_ERR_TYPE.': '.$type);
    }
  }
  
  // Escapes the String according to the used dbtype
  // $string String to be escaped
  // Returns Escaped String
  
  public function rdbms_escape_string ($string)
  {
    if ($this->_isMySqli())
    {
      if (is_array ($string))
      {
        foreach ($string as &$value) $value = $this->_db->rdbms_escape_string($string);
        return $string;
      }
      else
      {
        return $this->_db->real_escape_string($string);
      }
    }
    elseif ($this->_isODBC())
    {
      if (is_array ($string))
      {
        foreach ($string as &$value) $value = rdbms_escape_string ($this->_db, $string);
        return $string;
      }
      else
      {
        return odbc_escape_string ($this->_db, $string);
      }
    }
    else
    {
      $this->_typeError();
    }
  }
  
  // Send a query to the database
  // $sql Statement to be sent to the server
  // $errcode Code for the Error which is inserted into the log
  // $date Date of the Query
  // $num Number where the result shall be stored. Needed for getRowCount and rdbms_getresultrow
  // Returns true on success, false on failure
  
  public function rdbms_query ($sql, $errcode, $date, $num=1)
  {
    global $mgmt_config;
    
    if (!is_string ($sql) || trim ($sql) == "")
    {
      $this->_typeError ();
    }
    elseif ($this->_isMySqli())
    {
      // log
      if ($mgmt_config['rdbms_log'])
      {
        $time_start = microtime(true);
        $log = array();
        $log[] = $mgmt_config['today']."|QUERY: ".$sql;
      }
    
      $result = $this->_db->query ($sql);
      
      // log
      if ($mgmt_config['rdbms_log'])
      {    
        $time_stop = microtime(true);
        $time = $time_stop - $time_start;
        $log[] = $mgmt_config['today']."|EXEC-TIME: ".round ($time, 4)." sec";
        savelog ($log, "sql");
      }   
      
      if ($result == false)
      {
        $this->_error[] = $date."|db_connect_rdbms.php|error|".$errcode."|".$this->_db->error.", SQL: ".$sql;
        $this->_result[$num] = false;
        return false;
      }
      else
      {
        $this->_result[$num] = $result;
        return true;
      }
    }
    elseif ($this->_isODBC())
    {
      $result = odbc_exec ($this->_db, $sql);
      
      if ($result == false)
      {
        $this->_error[] = $date."|db_connect_rdbms.php|error|".$errcode."|ODBC Error Number: ".odbc_error().", SQL: ".$sql;
        $this->_result[$num] = false;
        return false;
      }
      else
      {
        $this->_result[$num] = $result;
        return $result;
      }
    }
    else
    {
      $this->_typeError();
    }
  }
  
  // Returns the Errors that happened
  public function rdbms_geterror ()
  {
    return $this->_error;
  }
  
  // Returns the number of rows from the result stored under $num
  // $num the number defined in the $query call
  public function rdbms_getnumrows ($num=1)
  {
    if ($this->_result[$num] == false)
    {
       return 0;
    }
    
    if ($this->_isMySqli ())
    {
      return $this->_db->affected_rows;
    }
    elseif ($this->_isODBC ())
    {
      return odbc_num_rows ($this->_result);
    }
    else
    {
      $this->_typeError ();
    }
  }
  
  // Returns the last inserted key (ID)
  public function rdbms_getinsertid ()
  {
    global $mgmt_config;
    
    if ($this->_isMySqli ())
    {
      return $this->_db->insert_id;
    }
    elseif ($this->_isODBC ())
    {
      return odbc_cursor ($this->_result);
    }
    else
    {
      $this->_typeError ();
    }
  }
  
  // Closes the database connection and frees all results
  public function rdbms_close()
  {
    if ($this->_isMySqli ())
    {
      foreach ($this->_result as $result)
      {
        if ($result instanceof mysqli_result) $result->free ();
      }
      
      $this->_db->close();
    }
    elseif ($this->_isODBC ())
    {
      foreach ($this->_result as $result)
      {
        if ($result != false) @odbc_free_result ($result);
      }
      
      odbc_close ($this->_db);
    }
    else
    {
      $this->_typeError ();
    }
  }
  
  // Returns a row from the result set
  // $num the number defined in the $query call
  // $rowNumber optionally a rownumber
  // Returns the resultArray or NULL
  public function rdbms_getresultrow ($num=1, $rowNumber=NULL)
  {
    if (empty ($this->_result[$num]) || !is_object ($this->_result[$num]))
    {
       return NULL;
    }
    
    if ($this->_isMySqli ())
    {
      if (!is_null ($rowNumber))
      {
        $this->_result[$num]->data_seek ($rowNumber);
      }
      
      $return = $this->_result[$num]->fetch_array (MYSQLI_ASSOC);
           
      return $return;
    }
    elseif ($this->_isODBC ())
    {
      if (is_null ($rowNumber))
      {
        return @odbc_fetch_array ($this->_result[$num]);
      }
      else
      {
        return @odbc_fetch_array ($this->_result[$num], $rowNumber);
      }
    }
    else
    {
      $this->_typeError ();
    }
  }
  
  protected function _isMySqli ()
  {
    return ($this->_db instanceof mysqli);
  }
  
  protected function _isODBC ()
  {
    return (is_resource ($this->_db) && get_resource_type ($this->_db) == 'odbc link' );
  }
  
  protected function _typeError ()
  {
    die (self::$_ERR_TYPE);
  }
}

// ------------------------------------------------ ODBC escape string ------------------------------------------------
// function: odbc_rdbms_escape_string()
// input: DB connection [resource], value [string]
// output: escaped value as string

// description:
// Alternative to mysql_real_rdbms_escape_string (PHP odbc_prepare would be optimal)

function odbc_rdbms_escape_string ($connection, $value)
{
  if ($value != "")
  {
    $value = addslashes ($value);
    return $value;
  }
  else return "";
}

// ------------------------------------------------ convert dbcharset ------------------------------------------------
// function: convert_dbcharset()
// input: character set [string]
// output: true / false

// description:
// Conversions from mySQL charset names to PHP charset names.

function convert_dbcharset ($charset)
{
  if ($charset != "")
  {
    $charset = strtolower ($charset);
    
    if ($charset == "utf8") $result = "UTF-8";
    elseif ($charset == "latin1") $result = "ISO-8859-1";
    elseif ($charset == "latin2") $result = "ISO-8859-2";
    else $result = false;
    
    return $result;
  }
  else return false;
}
 
// ------------------------------------------------ create object -------------------------------------------------
// function: rdbms_createobject()
// input: container ID [integer], object path [string], template name [string], media name [string] (optional), content container name [string] (optional), user name [string] (optional), 
//        latitude [float] (optional), longitude [float] (optional), copy media data [boolean] (optional)
// output: true / false

// description:
// Creates a new object in the database.

function rdbms_createobject ($container_id, $object, $template, $media="", $container="", $user="", $latitude="", $longitude="", $copydata=false)
{
  global $mgmt_config;

  $error = array();

  if (intval ($container_id) > 0 && $object != "" && (substr_count ($object, "%page%/") > 0 || substr_count ($object, "%comp%/") > 0) && $template != "" && $user != "")
  {
    // remove tailing slash
    $object = trim ($object);
    $object = trim ($object, "/");

    $db = new hcms_db($mgmt_config['dbconnect'], $mgmt_config['dbhost'], $mgmt_config['dbuser'], $mgmt_config['dbpasswd'], $mgmt_config['dbname'], $mgmt_config['dbcharset']);
        
    $container_id = intval ($container_id);
    $object = $db->rdbms_escape_string($object);
    $template = $db->rdbms_escape_string($template);
    if ($media != "") $media = $db->rdbms_escape_string($media);
    if ($container != "") $container = $db->rdbms_escape_string($container);
    if ($user != "") $user = $db->rdbms_escape_string($user);
        
    // current date
    $date = date ("Y-m-d H:i:s", time());

    // unique hash
    $hash = createuniquetoken ();
    
    // correct object name
    $object = str_replace (array("%page%/", "%comp%/"), array("*page*/", "*comp*/"), $object);
    if (strtolower (strrchr ($object, ".")) == ".off") $object = substr ($object, 0, -4);

    // create default container name
    if (empty ($container)) $container = correctcontainername ($container_id).".xml";

    // create filetype from file extension
    $file_ext = strrchr ($object, ".");
    $filetype = getfiletype ($file_ext);
    
    // check for existing object with same path (duplicate due to possible database error)
    $container_id_duplicate = rdbms_getobject_id ($object);
    
    // remove duplicate object
    if ($container_id_duplicate != "")
    {
      $result_delete = rdbms_deleteobject ($object);
      
      if ($result_delete)
      {
        $errcode = "20911";
        $error[] = $mgmt_config['today']."|db_connect_rdbms.inc.php|error|".$errcode."|Duplicate object '".$object."' (ID: ".$container_id_duplicate.") already existed in database and has been deleted";
      
        savelog (@$error);
      }
    }

    // copy media data from table object if the container ID exists already (connected copy)
    if (!empty ($copydata))
    {
      $sql = 'SELECT filesize, width, height, red, green, blue, colorkey, imagetype, md5_hash FROM object WHERE id='.$container_id;               
      $errcode = "50103";
      $mediacopy = $db->rdbms_query ($sql, $errcode, $mgmt_config['today'], 'media');
    }

    // GPS coordinates
    if (!empty ($latitude) && !empty ($longitude))
    {
      $latitude = floatval ($latitude);
      $longitude = floatval ($longitude);
    }
    elseif (!empty ($_SESSION['hcms_temp_latitude']) && is_numeric ($_SESSION['hcms_temp_latitude']) && !empty ($_SESSION['hcms_temp_longitude']) && is_numeric ($_SESSION['hcms_temp_longitude']))
    {
      $latitude = floatval ($_SESSION['hcms_temp_latitude']);
      $longitude = floatval ($_SESSION['hcms_temp_longitude']);
    }
    else
    {
      $latitude = "NULL";
      $longitude = "NULL";
    }
  
    // insert and paste media data from table object of the existing object with the same container ID (connected copy)
    if (!empty ($copydata) && $mediacopy && !empty ($container))
    {
      if ($row = $db->rdbms_getresultrow ('media'))
      {
        $sql = 'INSERT INTO object (id, hash, objectpath, md5_objectpath, template, media, container, createdate, date, latitude, longitude, filesize, filetype, width, height, red, green, blue, colorkey, imagetype, md5_hash, user) ';
        $sql .= 'VALUES ('.$container_id.', "'.$hash.'", "'.$object.'", "'.md5 ($object).'", "'.$template.'", "'.$media.'", "'.$container.'", "'.$date.'", "'.$date.'", '.$latitude.', '.$longitude.', '.intval($row['filesize']).', "'.$filetype.'", '.intval($row['width']).', '.intval($row['height']).', '.intval($row['red']).', '.intval($row['green']).', '.intval($row['blue']).', "'.$db->rdbms_escape_string($row['colorkey']).'", "'.$db->rdbms_escape_string($row['imagetype']).'", "'.$db->rdbms_escape_string($row['md5_hash']).'", "'.$user.'")';
      }
    }
    // insert values in table object (new object)
    elseif (!empty ($container))
    {
      $sql = 'INSERT INTO object (id, hash, objectpath, md5_objectpath, template, media, container, createdate, date, latitude, longitude, filetype, user) ';
      $sql .= 'VALUES ('.$container_id.', "'.$hash.'", "'.$object.'", "'.md5 ($object).'", "'.$template.'", "'.$media.'", "'.$container.'", "'.$date.'", "'.$date.'", '.$latitude.', '.$longitude.', "'.$filetype.'", "'.$user.'")';
    }

    $errcode = "50001";
    $db->rdbms_query ($sql, $errcode, $mgmt_config['today']);

    // save log
    savelog ($db->rdbms_geterror ());          
    $db->rdbms_close();
   
    return true;
  }
  else return false;
}

// ----------------------------------------------- copy content -------------------------------------------------
// function: rdbms_copycontent()
// input: source container ID [integer], destination container ID [integer], user name [string]
// output: true / false

// description:
// Selects the contents of a container/object and inserts them into another container/object.

function rdbms_copycontent ($container_id_source, $container_id_dest, $user)
{
  global $mgmt_config;

  if (intval ($container_id_source) > 0 && intval ($container_id_dest) > 0 && valid_objectname ($user))
  {
    $db = new hcms_db($mgmt_config['dbconnect'], $mgmt_config['dbhost'], $mgmt_config['dbuser'], $mgmt_config['dbpasswd'], $mgmt_config['dbname'], $mgmt_config['dbcharset']);
    
    $container_id_source = intval ($container_id_source);  
    $container_id_dest = intval ($container_id_dest);
    $user = $db->rdbms_escape_string($user);

    // copy textnodes
    $sql = 'SELECT * FROM textnodes WHERE id="'.$container_id_source.'"';
               
    $errcode = "50101";
    $done = $db->rdbms_query ($sql, $errcode, $mgmt_config['today'], 'textnodes');

    if ($done)
    {
      while ($row = $db->rdbms_getresultrow ('textnodes'))
      {
        $sql = 'INSERT INTO textnodes (id, text_id, textcontent, object_id, type, user) ';
        $sql .= 'VALUES ('.$container_id_dest.', "'.$db->rdbms_escape_string($row['text_id']).'", "'.$db->rdbms_escape_string($row['textcontent']).'", '.intval($row['object_id']).', "'.$db->rdbms_escape_string($row['type']).'", "'.$user.'")';
        
        $errcode = "50102";
        $db->rdbms_query ($sql, $errcode, $mgmt_config['today']);
      }
    }
    
    // copy media data from table object
    $sql = 'SELECT * FROM object WHERE id='.$container_id_source;
               
    $errcode = "50103";
    $done = $db->rdbms_query ($sql, $errcode, $mgmt_config['today'], 'media');

    if ($done)
    {
      if ($row = $db->rdbms_getresultrow ('media'))
      {
        $sql = 'UPDATE object SET filesize='.intval($row['filesize']).', filetype="'.$db->rdbms_escape_string($row['filetype']).'", width='.intval($row['width']).', height='.intval($row['height']).', red='.intval($row['red']).', green='.intval($row['green']).', blue='.intval($row['blue']).', colorkey="'.$db->rdbms_escape_string($row['colorkey']).'", imagetype="'.$db->rdbms_escape_string($row['imagetype']).'", md5_hash="'.$db->rdbms_escape_string($row['md5_hash']).'" ';
        $sql .= 'WHERE id='.$container_id_dest;

        $errcode = "50104";
        $db->rdbms_query ($sql, $errcode, $mgmt_config['today']);
      }
    }
    
    // copy keywords
    $sql = 'SELECT * FROM keywords_container WHERE id='.$container_id_source;
               
    $errcode = "50105";
    $done = $db->rdbms_query ($sql, $errcode, $mgmt_config['today'], 'keywords');

    if ($done)
    {
      while ($row = $db->rdbms_getresultrow ('keywords'))
      {
        $sql = 'INSERT INTO keywords_container (id, keyword_id) ';
        $sql .= 'VALUES ('.$container_id_dest.', '.intval($row['keyword_id']).')';
        
        $errcode = "50106";
        $db->rdbms_query ($sql, $errcode, $mgmt_config['today']);
      }
    }
    
    // copy taxonomy
    $sql = 'SELECT * FROM taxonomy WHERE id='.$container_id_source;
               
    $errcode = "50107";
    $done = $db->rdbms_query ($sql, $errcode, $mgmt_config['today'], 'taxonomy');

    if ($done)
    {
      while ($row = $db->rdbms_getresultrow ('taxonomy'))
      {
        $sql = 'INSERT INTO taxonomy (id, text_id, taxonomy_id, lang) ';
        $sql .= 'VALUES ('.$container_id_dest.', "'.$db->rdbms_escape_string($row['text_id']).'", '.intval($row['taxonomy_id']).', "'.$db->rdbms_escape_string($row['lang']).'")';
        
        $errcode = "50108";
        $db->rdbms_query ($sql, $errcode, $mgmt_config['today']);
      }
    }

    // save log
    savelog ($db->rdbms_geterror ());    
    $db->rdbms_close();

    return true;
  }
  else return false;
}

// ----------------------------------------------- set content -------------------------------------------------
// function: rdbms_setcontent()
// input: publication name [string], container ID [integer], content as array in form of array[text-ID]=text-content [array] (optional), type as array in form of array[text-ID]=type [array] (optional), 
//        user name [string] (optional), save modified date [boolean] (optional), save published date [null,true,false] (optional)
// output: true / false

// description:
// Saves the content in the database.

function rdbms_setcontent ($site, $container_id, $text_array="", $type_array="", $user="", $modifieddate=true, $publishdate=false)
{
  global $mgmt_config;

  if (intval ($container_id) > 0)
  {
    $db = new hcms_db ($mgmt_config['dbconnect'], $mgmt_config['dbhost'], $mgmt_config['dbuser'], $mgmt_config['dbpasswd'], $mgmt_config['dbname'], $mgmt_config['dbcharset']);
    
    $container_id = intval ($container_id);
    if ($user != "") $user = $db->rdbms_escape_string($user);
    
    $date = date ("Y-m-d H:i:s", time());

    $object_id = 0;
    
    // update container
    $sql_attr = array();
    if ($modifieddate == true) $sql_attr[0] = 'date="'.$date.'"';
    if ($publishdate == true) $sql_attr[1] = 'publishdate="'.$date.'"';
    elseif (strtolower ($publishdate) == "null") $sql_attr[1] = 'publishdate=""';
    if ($user != "") $sql_attr[2] = 'user="'.$user.'"';
    
    if (is_array ($sql_attr) && sizeof ($sql_attr) > 0)
    {
      $sql = 'UPDATE object SET ';
      $sql .= implode (", ", $sql_attr).' ';    
      $sql .= 'WHERE id='.$container_id;
      
      $errcode = "50003";
      $db->rdbms_query ($sql, $errcode, $mgmt_config['today'], 1);
    }

    // update text nodes
    if (is_array ($text_array) && sizeof ($text_array) > 0)
    {
      reset ($text_array);
      
      $i = 1;
      $update = false;
      
      foreach ($text_array as $text_id => $text)
      {
        $i++;
        
        if ($text_id != "") 
        {
          $text_id = $db->rdbms_escape_string($text_id);

          $sql = 'SELECT id, textcontent, object_id FROM textnodes WHERE id='.$container_id.' AND text_id="'.$text_id.'"';
               
          $errcode = "50004";
          $done = $db->rdbms_query ($sql, $errcode, $mgmt_config['today'], $i);

          if ($done)
          {
            $row = $db->rdbms_getresultrow ($i);
            
            // define type
            if (!empty ($type_array[$text_id]))
            {
              $type = $db->rdbms_escape_string($type_array[$text_id]);
              
              // add text prefix only if a text type has been provided
              if ($type == "u" || $type == "f" || $type == "l" || $type == "c" || $type == "d" || $type == "k") $type = "text".$type;
            }
            else $type = "";
            
            // only save content in database if content has been changed
            if (empty ($row['text_id']) || ($row['textcontent'] != cleancontent ($text, convert_dbcharset ($mgmt_config['dbcharset'])) && $row['object_id']."|".$row['textcontent'] != cleancontent ($text, convert_dbcharset ($mgmt_config['dbcharset']))))
            {
              // content has been changed
              $update = true;

              // prepare text value for link and media items
              if ((strpos ("_".$text_id, "link:") > 0 || strpos ("_".$text_id, "media:") > 0 || strpos ("_".$text_id, "comp:") > 0) && strpos ("_".$text, "|") > 0)
              {
                // delete entries for multiple components, example for text ID: comp:compname:0
                if (strpos ("_".$text_id, "comp:") > 0 && substr_count ($text_id, ":") == 2)
                {
                  $text_id_base = substr ($text_id, 0, strrpos ($text_id, ":"));
                  $sql = 'DELETE FROM textnodes WHERE id='.$container_id.' AND text_id LIKE "'.$text_id.':%"';
                       
                  $errcode = "50007";
                  $done = $db->rdbms_query ($sql, $errcode, $mgmt_config['today'], $i);
                }
              
                // extract object ID
                $object_id = substr ($text, 0, strpos ($text, "|"));
                $text = substr ($text, strpos ($text, "|") + 1);
                
                // check and get object ID from object path
                if (intval ($object_id) < 1) $object_id = rdbms_getobject_id ($object_id);
              }

              $object_id = intval ($object_id);

              // clean text (includes HTML decode)
              if ($text != "")
              {
                $text = cleancontent ($text, convert_dbcharset ($mgmt_config['dbcharset']));
                $text = $db->rdbms_escape_string($text);
              }
              
              $num_rows = $db->rdbms_getnumrows ($i);          
            
              // update exiting content
              if ($num_rows > 0)
              {
                // query 
                $sql = 'UPDATE textnodes SET textcontent="'.$text.'", object_id='.$object_id.', user="'.$user.'" ';
                if ($type != "") $sql .= ', type="'.$type.'" ';
                $sql .= 'WHERE id='.$container_id.' AND text_id="'.$text_id.'"';
  
                $errcode = "50005";
                $db->rdbms_query ($sql, $errcode, $mgmt_config['today'], ++$i);
              }
              // insert new content
              elseif ($num_rows == 0)
              {
                // query    
                $sql = 'INSERT INTO textnodes (id, text_id, textcontent, object_id'.($type != "" ? ', type' : '').', user) ';
                $sql .= 'VALUES ('.$container_id.', "'.$text_id.'", "'.$text.'", '.$object_id.''.($type != "" ? ', "'.$type.'"' : '').', "'.$user.'")';
  
                $errcode = "50006";
                $db->rdbms_query ($sql, $errcode, $mgmt_config['today'], ++$i);
              }

              // update textcontent in table object since version 10.0.2
              if ($num_rows >= 0)
              {
                // select textcontent
                $sql = 'SELECT textcontent FROM textnodes WHERE id='.$container_id;

                $errcode = "50007";
                $done = $db->rdbms_query ($sql, $errcode, $mgmt_config['today'], 'select_textcontent');
    
                $textcontent = "";

                if ($done)
                {
                  while ($row = $db->rdbms_getresultrow ('select_textcontent'))
                  {
                    if (trim ($row['textcontent']) != "") $textcontent .= $row['textcontent']." ";
                  }
                }            

                // escape text
                if (trim ($textcontent) != "") $textcontent = $db->rdbms_escape_string (trim ($textcontent));

                // query
                $sql = 'UPDATE object SET textcontent="'.$textcontent.'" WHERE id='.$container_id;

                $errcode = "50008";
                $db->rdbms_query ($sql, $errcode, $mgmt_config['today'], ++$i);
              }
            }
          }
        }
      }
    }

    // save log
    savelog ($db->rdbms_geterror ());    
    $db->rdbms_close();
    
    return true;
  }
  else return false;
}

// ----------------------------------------------- set keywords -------------------------------------------------
// function: rdbms_setkeywords()
// input: publication name [string], container ID [integer]
// output: true / false

// description:
// Analyzes the keyword content regarding its keywords, saves results in database.

function rdbms_setkeywords ($site, $container_id)
{
  global $mgmt_config;

  if (valid_publicationname ($site) && intval ($container_id) > 0)
  {
    $db = new hcms_db ($mgmt_config['dbconnect'], $mgmt_config['dbhost'], $mgmt_config['dbuser'], $mgmt_config['dbpasswd'], $mgmt_config['dbname'], $mgmt_config['dbcharset']);

    $container_id = intval ($container_id);

    // memory for all used keyword IDs
    $memory = array();
    $keywords_array = array();
    
    // select keyword content for container
    $sql = 'SELECT textcontent FROM textnodes WHERE id='.$container_id.' AND type="textk"';
    
    $errcode = "50300";
    $done = $db->rdbms_query ($sql, $errcode, $mgmt_config['today'], 'select1');
    
    if ($done)
    {
      while ($row = $db->rdbms_getresultrow ('select1'))
      {
        // extract keywords
        if (trim ($row['textcontent']) != "")
        {
          $keywords_add = splitkeywords ($row['textcontent']);

          if (is_array ($keywords_add)) $keywords_array = array_merge ($keywords_array, $keywords_add);
        }
      }
    }

    // if keywords have been extracted
    if (is_array ($keywords_array) && sizeof ($keywords_array) > 0)
    {
      // remove duplicates
      $keywords_array = array_unique ($keywords_array);

      foreach ($keywords_array as $keyword)
      {
        // select keyword ID
        $sql = 'SELECT keyword_id FROM keywords WHERE keyword="'.$keyword.'"';
             
        $errcode = "50301";
        $done = $db->rdbms_query ($sql, $errcode, $mgmt_config['today'], 'select2');

        // keyword exists  
        if ($done && $db->rdbms_getnumrows ('select2') > 0)
        {
          $row = $db->rdbms_getresultrow ('select2');
          
          $memory[] = $keyword_id = $row['keyword_id'];

          // select container ID
          $sql = 'SELECT id FROM keywords_container WHERE keyword_id='.$keyword_id.' AND id='.$container_id;
        
          $errcode = "50302";
          $db->rdbms_query ($sql, $errcode, $mgmt_config['today'], 'select2');
        
          // container ID does not exist
          if ($db->rdbms_getnumrows ('select2') < 1)
          {
            // insert new taxonomy entries    
            $sql = 'INSERT INTO keywords_container (id, keyword_id) VALUES ('.$container_id.', '.$keyword_id.')';

            $errcode = "50303";
            $db->rdbms_query ($sql, $errcode, $mgmt_config['today'], 'insert1');
          }
        }
        // keyword does not exist
        else
        {
          $keyword = $db->rdbms_escape_string($keyword);
          
          // insert new keyword  
          $sql = 'INSERT INTO keywords (keyword) VALUES ("'.$keyword.'");';
          
          $errcode = "50304";
          $db->rdbms_query ($sql, $errcode, $mgmt_config['today'], 'insert2');

          // get last keyword ID
          $memory[] = $keyword_id = $db->rdbms_getinsertid();
          
          // insert new keyword container relationship    
          $sql = 'INSERT INTO keywords_container (id, keyword_id) VALUES ('.$container_id.', '.$keyword_id.')';

          $errcode = "50305";
          $db->rdbms_query ($sql, $errcode, $mgmt_config['today'], 'insert3');
        }
      }
    }

    // remove all unused keywords for container
    if (sizeof ($memory) > 0)
    {
      $sql = 'DELETE FROM keywords_container WHERE id='.$container_id.' AND keyword_id NOT IN ('.implode (",", $memory).')';
    }
    // no keywords provided by container
    else
    {
      $sql = 'DELETE FROM keywords_container WHERE id='.$container_id;
    }

    $errcode = "50307";
    $db->rdbms_query ($sql, $errcode, $mgmt_config['today'], 'delete');

    // save log
    savelog ($db->rdbms_geterror ());    
    $db->rdbms_close();
  
    return true;
  }
  else return false;
}

// ----------------------------------------------- set keywords for a publication ------------------------------------------------- 
// function: rdbms_setpublicationkeywords()
// input: publication name [string], recreate [boolean] (optional)
// output: true / false

// description:
// Saves all keywords of a publication in the database.

function rdbms_setpublicationkeywords ($site, $recreate=false)
{
  global $mgmt_config;

  if (valid_publicationname ($site))
  {
    // remove all taxonomy entries from publication
    if ($recreate == true) rdbms_deletepublicationkeywords ($site);
    
    $db = new hcms_db($mgmt_config['dbconnect'], $mgmt_config['dbhost'], $mgmt_config['dbuser'], $mgmt_config['dbpasswd'], $mgmt_config['dbname'], $mgmt_config['dbcharset']);

    // clean input
    $site_escaped = $db->rdbms_escape_string ($site);

    // select container IDs with keywords
    $sql = "SELECT DISTINCT textnodes.id FROM textnodes INNER JOIN object ON textnodes.id=object.id WHERE textnodes.textcontent!='' AND textnodes.type='textk'";
    $sql .= " AND (object.objectpath LIKE BINARY '*page*/".$site_escaped."/%' OR object.objectpath LIKE BINARY '*comp*/".$site_escaped."/%')";

    $errcode = "50033";
    $done = $db->rdbms_query ($sql, $errcode, $mgmt_config['today']);
  
    if ($done)  
    {
      while ($row = $db->rdbms_getresultrow ())
      {
        rdbms_setkeywords ($site, $row['id']);
      }
    }

    // save log
    savelog ($db->rdbms_geterror ());    
    $db->rdbms_close();    
      
    return true;
  }
  else return false;
}

// ----------------------------------------------- set taxonomy -------------------------------------------------
// function: rdbms_settaxonomy()
// input: publication name [string], container ID [integer], taxonomy array in form of array[text-ID][lang][taxonomy-ID]=keyword [array]
// output: true / false

// description:
// Saves the used taxonomy IDs of a container in database if the taxonomy is enabled for the publication.

function rdbms_settaxonomy ($site, $container_id, $taxonomy_array)
{
  global $mgmt_config;
  
  if (valid_publicationname ($site) && intval ($container_id) > 0 && is_array ($taxonomy_array) && is_array ($mgmt_config))
  {
    // load publication management config
    if (!isset ($mgmt_config[$site]['taxonomy']) && is_file ($mgmt_config['abs_path_data']."config/".$site.".conf.php"))
    {
      require ($mgmt_config['abs_path_data']."config/".$site.".conf.php");
    }
    
    // taxonomy is enabled
    if (!empty ($mgmt_config[$site]['taxonomy']))
    {
      $db = new hcms_db ($mgmt_config['dbconnect'], $mgmt_config['dbhost'], $mgmt_config['dbuser'], $mgmt_config['dbpasswd'], $mgmt_config['dbname'], $mgmt_config['dbcharset']);
  
      $container_id = intval ($container_id);
      
      // taxonomy_array syntax:
      // $taxonomy_array[text_id][lang][taxonomy_id] = taxonomy_keyword
      foreach ($taxonomy_array as $text_id=>$tx_lang_array)
      {
        // delete taxonomy entries with same text ID
        $sql = 'DELETE FROM taxonomy WHERE id='.$container_id.' AND text_id="'.$text_id.'"';
             
        $errcode = "50201";
        $db->rdbms_query ($sql, $errcode, $mgmt_config['today'], 'delete');
              
        foreach ($tx_lang_array as $lang=>$tx_keyword_array)
        {
          foreach ($tx_keyword_array as $taxonomy_id=>$taxonomy_keyword)
          {
            if ($text_id != "" && intval ($taxonomy_id) > 0 && $lang != "")
            {
              $text_id = $db->rdbms_escape_string($text_id);
              $taxonomy_id = intval ($taxonomy_id);
              $lang = $db->rdbms_escape_string($lang);

              // insert new taxonomy entries    
              $sql = 'INSERT INTO taxonomy (id, text_id, taxonomy_id, lang) ';      
              $sql .= 'VALUES ('.$container_id.', "'.$text_id.'", '.$taxonomy_id.', "'.$lang.'")';  

              $errcode = "50202";
              $db->rdbms_query ($sql, $errcode, $mgmt_config['today'], 'insert');
            }
          }
        }
      }
      

      // save log
      savelog ($db->rdbms_geterror ());    
      $db->rdbms_close();
    
      return true;
    }
    return false;
  }
  else return false;
}

// ----------------------------------------- set taxonomy for a publication --------------------------------------------
// function: rdbms_setpublicationtaxonomy()
// input: publication name [string] (optional), recreate [boolean] (optional)
// output: true / false

// description:
// Saves all taxonomy keywords of a publication in the database.

function rdbms_setpublicationtaxonomy ($site="", $recreate=false) 
{
  global $mgmt_config;

  $site_array = array();

  if (valid_publicationname ($site))
  {
    $site_array[0] = $site; 
  }
  elseif ($site == "")
  {
    $inherit_db = inherit_db_read ();

    if (is_array ($inherit_db) && sizeof ($inherit_db) > 0)
    {
      foreach ($inherit_db as $site => $array)
      {
        if ($site != "") $site_array[] = trim ($site);
      }
    }
  }

  if (is_array ($site_array) && sizeof ($site_array) > 0)
  {
    $db = new hcms_db($mgmt_config['dbconnect'], $mgmt_config['dbhost'], $mgmt_config['dbuser'], $mgmt_config['dbpasswd'], $mgmt_config['dbname'], $mgmt_config['dbcharset']);

    $site_array = array_unique ($site_array);

    foreach ($site_array as $site)
    {
      // load publication management config
      if (valid_publicationname ($site) && !isset ($mgmt_config[$site]['taxonomy']) && is_file ($mgmt_config['abs_path_data']."config/".$site.".conf.php"))
      {
        require ($mgmt_config['abs_path_data']."config/".$site.".conf.php");
      }

      // if taxonomy is enabled
      if (valid_publicationname ($site) && !empty ($mgmt_config[$site]['taxonomy']))
      {
        // remove all taxonomy entries from publication
        if ($recreate == true) rdbms_deletepublicationtaxonomy ($site, true);

        $site = $db->rdbms_escape_string ($site);

        // select containers of publication
        if (trim ($site) != "")
        {
          if ($recreate == true)
          {
            $sql = 'SELECT id FROM object WHERE objectpath LIKE BINARY "*comp*/'.$site.'/%" OR objectpath LIKE BINARY "*page*/'.$site.'/%"';
          }
          else
          {
            $sql = 'SELECT object.id FROM object INNER JOIN textnodes ON textnodes.id=object.id LEFT JOIN taxonomy ON taxonomy.id=object.id WHERE (object.objectpath LIKE BINARY "*comp*/'.$site.'/%" OR object.objectpath LIKE BINARY "*page*/'.$site.'/%") AND textnodes.textcontent!="" AND taxonomy.id IS NULL';
          }

          $errcode = "50353";
          $containers = $db->rdbms_query($sql, $errcode, $mgmt_config['today'], 'containers');

          if ($containers)
          {
            // load taxonomy of publication
            if (valid_publicationname ($site) && is_file ($mgmt_config['abs_path_data']."include/".$site.".taxonomy.inc.php"))
            {
              include ($mgmt_config['abs_path_data']."include/".$site.".taxonomy.inc.php");
            }
            // load default taxonomy
            elseif (is_file ($mgmt_config['abs_path_data']."include/default.taxonomy.inc.php"))
            {
              include ($mgmt_config['abs_path_data']."include/default.taxonomy.inc.php");
            }

            while ($row = $db->rdbms_getresultrow ('containers'))
            {
              // set taxonomy for container
              if (!empty ($row['id'])) settaxonomy ($site, $row['id'], "", $taxonomy);
            }
          }
        }
      }

      // save log
      savelog ($db->rdbms_geterror ());    
      $db->rdbms_close();
    }

    return true;
  }
  else return false;
}

// ----------------------------------------------- get taxonomy -------------------------------------------------
// function: rdbms_gettaxonomy()
// input: container ID [integer], text ID [string]
// output: result array / false

// description:
// Returns the taxonomy data for a specific text ID of a container.

function rdbms_gettaxonomy ($container_id, $text_id)
{
  global $mgmt_config;

  // taxonomy is enabled
  if (intval ($container_id) > 0 && $text_id != "")
  {
    $db = new hcms_db ($mgmt_config['dbconnect'], $mgmt_config['dbhost'], $mgmt_config['dbuser'], $mgmt_config['dbpasswd'], $mgmt_config['dbname'], $mgmt_config['dbcharset']);

    $container_id = intval ($container_id);
    $text_id = $db->rdbms_escape_string ($text_id);

    $sql = 'SELECT * FROM taxonomy WHERE id='.$container_id.' AND text_id="'.$text_id.'"';
    
    $errcode = "50203";
    $done = $db->rdbms_query ($sql, $errcode, $mgmt_config['today'], 'taxonomy');

    $result = array();
    $i = 0;

    if ($done)
    {
      while ($row = $db->rdbms_getresultrow ('taxonomy'))
      {
        $result[$i] = $row;
        $i++;
      }
    }

    // save log
    savelog ($db->rdbms_geterror ());    
    $db->rdbms_close();

    return $result;
  }
  return false;
}

// ----------------------------------------------- set template -------------------------------------------------
// function: rdbms_settemplate()
// input: object path [string], template file name [string]
// output: true / false

// description:
// Saves the template for an object in the database.

function rdbms_settemplate ($object, $template)
{
  global $mgmt_config;
  
  if ($object != "" && $template != "" && (substr_count ($object, "%page%/") > 0 || substr_count ($object, "%comp%/") > 0))
  {    
    // correct object name 
    if (strtolower (@strrchr ($object, ".")) == ".off") $object = @substr ($object, 0, -4);

    $db = new hcms_db($mgmt_config['dbconnect'], $mgmt_config['dbhost'], $mgmt_config['dbuser'], $mgmt_config['dbpasswd'], $mgmt_config['dbname'], $mgmt_config['dbcharset']);

    $object = str_replace (array("%page%/", "%comp%/"), array("*page*/", "*comp*/"), $object);
    $object = $db->rdbms_escape_string ($object);
    $template = $db->rdbms_escape_string ($template);

    // update object
    $sql = 'UPDATE object SET template="'.$template.'" WHERE md5_objectpath="'.md5 ($object).'"'; 

    $errcode = "50007";
    $db->rdbms_query ($sql, $errcode, $mgmt_config['today']);

    // save log
    savelog ($db->rdbms_geterror ());    
    $db->rdbms_close();
 
    return true;
  }
  else return false;
}

// ----------------------------------------------- set media name -------------------------------------------------
// function: rdbms_setmedianame()
// input: container ID [integer], media file name [string]
// output: true / false

// description:
// Saves the media file name for an object in the database.

function rdbms_setmedianame ($id, $media)
{
  global $mgmt_config;
  
  if ($id != "" && $media != "")
  {
    $db = new hcms_db($mgmt_config['dbconnect'], $mgmt_config['dbhost'], $mgmt_config['dbuser'], $mgmt_config['dbpasswd'], $mgmt_config['dbname'], $mgmt_config['dbcharset']);
    
    $id = intval ($id);
    $media = $db->rdbms_escape_string ($media);

    // update object
    $sql = 'UPDATE object SET media="'.$media.'" WHERE id='.$id; 

    $errcode = "50308";
    $db->rdbms_query ($sql, $errcode, $mgmt_config['today']);

    // save log
    savelog ($db->rdbms_geterror ());    
    $db->rdbms_close();
 
    return true;
  }
  else return false;
} 

// ----------------------------------------------- set media attributes -------------------------------------------------
// function: rdbms_setmedia()
// input: container ID [integer], file size in KB [integer] (optional), file type [string] (optional), width in pixel [integer] (optional), heigth in pixel [integer] (optional), 
//        red color [integer] (optional), green color [integer] (optional), blue color [integer] (optional), colorkey [string] (optional), image type [string] (optional), MD5 hash [string] (optional), analyzed [boolean] (optional)
// output: true / false

// description:
// Saves media attributes in the database.

function rdbms_setmedia ($id, $filesize="", $filetype="", $width="", $height="", $red="", $green="", $blue="", $colorkey="", $imagetype="", $md5_hash="", $analyzed="")
{
  global $mgmt_config;
  
  if ($id != "" && ($filesize != "" || $filetype != "" || $width != "" || $height != "" || $red != "" || $green != "" || $blue != "" || $colorkey != "" || $imagetype != "" || $md5_hash != "" || $analyzed == true || $analyzed == false))
  {    
    $db = new hcms_db($mgmt_config['dbconnect'], $mgmt_config['dbhost'], $mgmt_config['dbuser'], $mgmt_config['dbpasswd'], $mgmt_config['dbname'], $mgmt_config['dbcharset']);

    if ($filetype != "") $filetype = $db->rdbms_escape_string ($filetype);
    if ($colorkey != "") $colorkey = $db->rdbms_escape_string ($colorkey);
    if ($imagetype != "") $imagetype = $db->rdbms_escape_string ($imagetype);
    if ($md5_hash != "") $md5_hash = $db->rdbms_escape_string ($md5_hash);
    if ($analyzed === true) $analyzed = 1;
    elseif ($analyzed === false) $analyzed = 0;

    // update media attributes in table object
    $sql_update = array();

    if ($filesize != "") $sql_update[] = 'filesize='.intval($filesize);
    if ($filetype != "") $sql_update[] = 'filetype="'.$filetype.'"'; 
    if ($width != "") $sql_update[] = 'width='.intval($width);
    if ($height != "") $sql_update[] = 'height='.intval($height);
    if ($red != "") $sql_update[] = 'red='.intval($red);
    if ($green != "") $sql_update[] = 'green='.intval($green);
    if ($blue != "") $sql_update[] = 'blue='.intval($blue);
    if ($colorkey != "") $sql_update[] = 'colorkey="'.$colorkey.'"';
    if ($imagetype != "") $sql_update[] = 'imagetype="'.$imagetype.'"';
    if ($md5_hash != "") $sql_update[] = 'md5_hash="'.$md5_hash.'"';
    if ($analyzed != "") $sql_update[] = 'analyzed='.intval($analyzed);

    if (sizeof ($sql_update) > 0)
    {
      $sql = 'UPDATE object SET ';
      $sql .= implode (", ", $sql_update);
      $sql .= ' WHERE id="'.intval($id).'"';
    }

    $errcode = "50009";
    $db->rdbms_query ($sql, $errcode, $mgmt_config['today'], 'update');

    // save log
    savelog ($db->rdbms_geterror ());    
    $db->rdbms_close();
 
    return true;
  }
  else return false;
}

// ------------------------------------------------ get media attributes -------------------------------------------------
// function: rdbms_resetanalyzed()
// input: %
// output: true / false

// description:
// Resets the analyzed attribute in the media table for all assets if a new face label has been added

function rdbms_resetanalyzed ()
{
  global $mgmt_config;

  $db = new hcms_db($mgmt_config['dbconnect'], $mgmt_config['dbhost'], $mgmt_config['dbuser'], $mgmt_config['dbpasswd'], $mgmt_config['dbname'], $mgmt_config['dbcharset']);
    
  if ($db)
  {    
    $sql = 'UPDATE object SET analyzed=0 WHERE analyzed=1';

    $errcode = "50219";
    $db->rdbms_query ($sql, $errcode, $mgmt_config['today'], 'update');

    // save log
    savelog ($db->rdbms_geterror ());    
    $db->rdbms_close();

    return true;
  }
  else return false;
}

// ------------------------------------------------ get media attributes -------------------------------------------------
// function: rdbms_getmedia()
// input: container ID [integer]
// output: result array with media object details / false on error

// description:
// Reads all media object details.

function rdbms_getmedia ($container_id)
{
  global $mgmt_config;

  if (intval ($container_id) > 0)
  {
    $db = new hcms_db($mgmt_config['dbconnect'], $mgmt_config['dbhost'], $mgmt_config['dbuser'], $mgmt_config['dbpasswd'], $mgmt_config['dbname'], $mgmt_config['dbcharset']);

    // clean input
    $container_id = intval ($container_id);  

    // get media info
    $sql = 'SELECT * FROM object WHERE id='.$container_id;   

    $errcode = "50067";
    $done = $db->rdbms_query ($sql, $errcode, $mgmt_config['today']);

    if ($done && $row = $db->rdbms_getresultrow ())
    {
      $media = $row;   
    }

    // save log
    savelog ($db->rdbms_geterror());    
    $db->rdbms_close();
   
    if (!empty ($media) && is_array ($media)) return $media;
    else return false;
  }
  else return false;
}

// ------------------------------------------------ get duplicate file -------------------------------------------------
// function: rdbms_getduplicate_file()
// input: publication name [string], MD5 hash of the file content [string]
// output: object path array / false

// description:
// Returns the objects with the same file content as array. Objects in the recylce bin are excluded.

function rdbms_getduplicate_file ($site, $md5_hash)
{
  global $mgmt_config;

  if ($site != "" && $md5_hash != "")
  {
    $db = new hcms_db($mgmt_config['dbconnect'], $mgmt_config['dbhost'], $mgmt_config['dbuser'], $mgmt_config['dbpasswd'], $mgmt_config['dbname'], $mgmt_config['dbcharset']);

    // clean input
    $md5_hash = $db->rdbms_escape_string ($md5_hash);
    $site = $db->rdbms_escape_string ($site);

    // get media info
    $sql = 'SELECT objectpath FROM object WHERE md5_hash="'.$md5_hash.'" AND objectpath LIKE "*comp*/'.$site.'/%" AND deleteuser=""';

    $errcode = "50067";
    $done = $db->rdbms_query ($sql, $errcode, $mgmt_config['today'], 'main');

    $media = array();

    if ($done)
    {
      while ($row = $db->rdbms_getresultrow ('main'))
      {
        if (!empty ($row['objectpath']))
        {
          $row['objectpath'] = str_replace (array("*page*/", "*comp*/"), array("%page%/", "%comp%/"), $row['objectpath']);
          $media[] = $row;
        }
      }
    }

    // save log
    savelog ($db->rdbms_geterror());    
    $db->rdbms_close();      
   
    if (is_array ($media) && !empty($media)) return $media;
    else return false;
  }
  else return false;
}

// ----------------------------------------------- rename object -------------------------------------------------
// function: rdbms_renameobject()
// input: location path of object [string], location path of object with new object name [string]
// output: true / false

// description:
// Renames an object or a folder (.folder file name must be provided for folder).

function rdbms_renameobject ($object_old, $object_new)
{
  global $mgmt_config;

  if ($object_old != "" && $object_new != "" && (substr_count ($object_old, "%page%/") > 0 || substr_count ($object_old, "%comp%/") > 0) && (substr_count ($object_new, "%page%/") > 0 || substr_count ($object_new, "%comp%/") > 0))
  {
    // initialize
    $type = "object";

    // correct object names
    if (strtolower (strrchr ($object_old, ".")) == ".off") $object_old = substr ($object_old, 0, -4);
    if (strtolower (strrchr ($object_new, ".")) == ".off") $object_new = substr ($object_new, 0, -4);

    // correct folder names
    if (substr ($object_old, -8) == "/.folder") 
    {
      $object_old = substr ($object_old, 0, -8);
      $type = "folder";
    }

    if (substr ($object_new, -8) == "/.folder") $object_new = substr ($object_new, 0, -8);

    $db = new hcms_db($mgmt_config['dbconnect'], $mgmt_config['dbhost'], $mgmt_config['dbuser'], $mgmt_config['dbpasswd'], $mgmt_config['dbname'], $mgmt_config['dbcharset']);

    // remove separator
    $object_old = str_replace ("|", "", $object_old);
    $object_new = str_replace ("|", "", $object_new); 

    $object_old = $db->rdbms_escape_string ($object_old);
    $object_new = $db->rdbms_escape_string ($object_new);

    // replace %
    $object_old = str_replace (array("%page%/", "%comp%/"), array("*page*/", "*comp*/"), $object_old);
    $object_new = str_replace (array("%page%/", "%comp%/"), array("*page*/", "*comp*/"), $object_new);

    // query
    $sql = 'SELECT object_id, id, objectpath FROM object ';
    // for folder
    if ($type == "folder") $sql .= 'WHERE objectpath LIKE BINARY "'.$object_old.'/%"';
    // for object
    else $sql .= 'WHERE md5_objectpath="'.md5 ($object_old).'"';;

    $errcode = "50010";
    $done = $db->rdbms_query($sql, $errcode, $mgmt_config['today'], 'select');

    $i = 1;

    if ($done)
    {
      while ($row = $db->rdbms_getresultrow ('select'))
      {
        if (!empty ($row['object_id']))
        {
          $object_id = intval ($row['object_id']);
          $container_id = intval ($row['id']);
          $object = $row['objectpath'];
          $object = str_replace ($object_old, $object_new, $object);
          $fileext = strrchr ($object, ".");
          $filetype = getfiletype ($fileext);

          // update object 
          if ($filetype != "") $sql = 'UPDATE object SET objectpath="'.$object.'", md5_objectpath="'.md5 ($object).'", filetype="'.$filetype.'" WHERE object_id='.$object_id.'';
          else $sql = 'UPDATE object SET objectpath="'.$object.'", md5_objectpath="'.md5 ($object).'" WHERE object_id='.$object_id.'';

          $errcode = "50011";
          $db->rdbms_query ($sql, $errcode, $mgmt_config['today'], $i++);
        }
      }
    }

    // save log
    savelog ($db->rdbms_geterror ());    
    $db->rdbms_close();
 
    return true;
  }
  else return false;
} 

// ----------------------------------------------- delete object ------------------------------------------------- 
// function: rdbms_deleteobject()
// input: location path of object [string] (optional) OR object ID [integer] (optional)
// output: true / false

// description:
// Deletes an object.

function rdbms_deleteobject ($object="", $object_id="")
{
  global $mgmt_config;

  // clean input
  $object_id = intval ($object_id);  

  if (($object != "" && (substr_count ($object, "%page%/") > 0 || substr_count ($object, "%comp%/") > 0)) || intval ($object_id) > 0)
  {
    // correct object name 
    if (strtolower (@strrchr ($object, ".")) == ".off") $object = @substr ($object, 0, -4);

    $db = new hcms_db($mgmt_config['dbconnect'], $mgmt_config['dbhost'], $mgmt_config['dbuser'], $mgmt_config['dbpasswd'], $mgmt_config['dbname'], $mgmt_config['dbcharset']);

    if ($object != "")
    {
      $object = str_replace (array("%page%/", "%comp%/"), array("*page*/", "*comp*/"), $object);
      $object = $db->rdbms_escape_string ($object);
    }

    // query
    $sql = 'SELECT id FROM object ';

    if ($object != "") $sql .= 'WHERE md5_objectpath="'.md5 ($object).'" OR objectpath= BINARY "'.$object.'"';
    elseif (intval ($object_id) > 0) $sql .= 'WHERE object_id='.intval ($object_id).'';
  
    $errcode = "50012";
    $done = $db->rdbms_query($sql, $errcode, $mgmt_config['today'], 'select1');

    if ($done)
    {
      $row = $db->rdbms_getresultrow('select1');

      if ($row)
      {
        $container_id = intval ($row['id']); 

        $sql = 'SELECT object_id FROM object ';
        $sql .= 'WHERE id='.$container_id;

        $errcode = "50013";
        $done = $db->rdbms_query($sql, $errcode, $mgmt_config['today'], 'select2');

        if ($done)
        {
          $row_id = $db->rdbms_getresultrow ('select2');
          $num_rows = $db->rdbms_getnumrows ('select2');
        }

        // delete all entries for this id since no connected objects exists
        if ($row_id && $num_rows == 1)
        {
          // delete object
          $sql = 'DELETE FROM object WHERE id='.$container_id;

          $errcode = "50014";
          $done = $db->rdbms_query ($sql, $errcode, $mgmt_config['today'], 'delete1');

          // delete textnodes
          $sql = 'DELETE FROM textnodes WHERE id='.$container_id;

          $errcode = "50015";
          $done = $db->rdbms_query ($sql, $errcode, $mgmt_config['today'], 'delete3');

          // delete taxonomy
          $sql = 'DELETE FROM taxonomy WHERE id='.$container_id;

          $errcode = "50024";
          $done = $db->rdbms_query ($sql, $errcode, $mgmt_config['today'], 'delete3');
          
          // delete keywords
          $sql = 'DELETE FROM keywords_container WHERE id='.$container_id;

          $errcode = "50025";
          $done = $db->rdbms_query ($sql, $errcode, $mgmt_config['today'], 'delete4');

          // delete dailytstat
          $sql = 'DELETE FROM dailystat WHERE id='.$container_id;

          $errcode = "50017";
          $done = $db->rdbms_query ($sql, $errcode, $mgmt_config['today'], 'delete6');        

          // delete queue
          $sql = 'DELETE FROM queue WHERE object_id='.$row_id['object_id'];

          $errcode = "50018";
          $done = $db->rdbms_query ($sql, $errcode, $mgmt_config['today'], 'delete7');
          
          // delete accesslink
          $sql = 'DELETE FROM accesslink WHERE object_id='.$row_id['object_id'];

          $errcode = "50019";
          $done = $db->rdbms_query ($sql, $errcode, $mgmt_config['today'], 'delete8');
          
          // delete task
          $sql = 'DELETE FROM task WHERE object_id='.$row_id['object_id'];

          $errcode = "50023";
          $done = $db->rdbms_query ($sql, $errcode, $mgmt_config['today'], 'delete9');    
        }
        // delete only the object reference and queue entry
        elseif ($row_id && $num_rows > 1)
        {
          $sql = 'DELETE FROM object WHERE md5_objectpath="'.md5 ($object).'" OR objectpath= BINARY "'.$object.'"';

          $errcode = "50020";
          $done = $db->rdbms_query ($sql, $errcode, $mgmt_config['today'], 'delete10');
        }

        // delete queue
        $sql = 'DELETE FROM queue WHERE object_id='.$row_id['object_id'];   

        $errcode = "50021";
        $done = $db->rdbms_query ($sql, $errcode, $mgmt_config['today'], 'delete11');

        // delete notification
        $sql = 'DELETE FROM notify WHERE object_id='.$row_id['object_id'];   

        $errcode = "50022";
        $done = $db->rdbms_query ($sql, $errcode, $mgmt_config['today'], 'delete12');

        // delete/update textnodes (object_id for link)
        $sql = 'UPDATE textnodes SET object_id=NULL WHERE object_id='.$row_id['object_id'];   

        $errcode = "50023";
        $done = $db->rdbms_query ($sql, $errcode, $mgmt_config['today'], 'update1');
      }
    }

    // save log
    savelog ($db->rdbms_geterror ());    
    $db->rdbms_close();

    return true;
  }
  else return false;
}

// ----------------------------------------------- delete content -------------------------------------------------
// function: rdbms_deletecontent()
// input: publication name [string], container ID [integer], text ID [string]
// output: true / false

// description:
// Deletes the content of an object/container based on the text ID.

function rdbms_deletecontent ($site, $container_id, $text_id)
{
  global $mgmt_config;

  if (intval ($container_id) > 0 && $text_id != "")
  {   
    $db = new hcms_db($mgmt_config['dbconnect'], $mgmt_config['dbhost'], $mgmt_config['dbuser'], $mgmt_config['dbpasswd'], $mgmt_config['dbname'], $mgmt_config['dbcharset']);

    $container_id = intval ($container_id);
    $text_id = $db->rdbms_escape_string ($text_id);

    // delete textnodes
    $sql = 'DELETE FROM textnodes WHERE id='.$container_id.' AND text_id="'.$text_id.'"';

    $errcode = "50021";
    $db->rdbms_query ($sql, $errcode, $mgmt_config['today']);

    // delete taxonomy
    $sql = 'DELETE FROM taxonomy WHERE id='.$container_id.' AND text_id="'.$text_id.'"';

    $errcode = "50028";
    $db->rdbms_query ($sql, $errcode, $mgmt_config['today']);

    // save log
    savelog ($db->rdbms_geterror ());    
    $db->rdbms_close();

    return true;
  }
  else return false;
}

// ------------------------------------------ delete keywords of a publication --------------------------------------------
// function: rdbms_deletepublicationkeywords()
// input: publication name [string]
// output: true / false

// description:
// Deletes all keywords of a publication.

function rdbms_deletepublicationkeywords ($site)
{
  global $mgmt_config;
  
  // load publication management config
  if (valid_publicationname ($site))
  {
    $db = new hcms_db($mgmt_config['dbconnect'], $mgmt_config['dbhost'], $mgmt_config['dbuser'], $mgmt_config['dbpasswd'], $mgmt_config['dbname'], $mgmt_config['dbcharset']);
  
    $site = $db->rdbms_escape_string ($site);
  
    // select containers of publication
    $sql = 'SELECT DISTINCT id FROM object WHERE objectpath LIKE BINARY "*comp*/'.$site.'/%" OR objectpath LIKE BINARY "*page*/'.$site.'/%"';

    $errcode = "50053";
    $done = $db->rdbms_query($sql, $errcode, $mgmt_config['today'], 'select');

    if ($done)
    {
      while ($row = $db->rdbms_getresultrow ('select'))
      {
        if (!empty ($row['id']))
        {
          // delete taxonomy
          $sql = 'DELETE FROM keywords_container WHERE id="'.$row['id'].'"';
 
          $errcode = "50054";
          $db->rdbms_query ($sql, $errcode, $mgmt_config['today']);
        }
      }
    }

    // save log
    savelog ($db->rdbms_geterror ());    
    $db->rdbms_close();

    return true;
  }
  else return false;
}

// ------------------------------------------ delete taxonomy of a publication --------------------------------------------
// function: rdbms_deletepublicationtaxonomy()
// input: publication name [string], force delete if the taxonomy is disabled in the publication [boolean] (optional)
// output: true / false

// description:
// Deletes the taxonomy definition of a publication.

function rdbms_deletepublicationtaxonomy ($site, $force=false)
{
  global $mgmt_config;

  // load publication management config
  if (valid_publicationname ($site) && !isset ($mgmt_config[$site]['taxonomy']))
  {
    require ($mgmt_config['abs_path_data']."config/".$site.".conf.php");
  }

  // if taxonomy is disabled
  if (empty ($mgmt_config[$site]['taxonomy']) || $force == true)
  {
    $db = new hcms_db($mgmt_config['dbconnect'], $mgmt_config['dbhost'], $mgmt_config['dbuser'], $mgmt_config['dbpasswd'], $mgmt_config['dbname'], $mgmt_config['dbcharset']);

    $site = $db->rdbms_escape_string ($site);

    // select containers of publication
    $sql = 'SELECT DISTINCT id FROM object WHERE objectpath LIKE BINARY "*comp*/'.$site.'/%" OR objectpath LIKE BINARY "*page*/'.$site.'/%"';

    $errcode = "50053";
    $done = $db->rdbms_query($sql, $errcode, $mgmt_config['today'], 'select');

    if ($done)
    {
      while ($row = $db->rdbms_getresultrow ('select'))
      {
        if (!empty ($row['id']))
        {
          // delete taxonomy
          $sql = 'DELETE FROM taxonomy WHERE id="'.$row['id'].'"';

          $errcode = "50054";
          $db->rdbms_query ($sql, $errcode, $mgmt_config['today']);
        }
      }
    }
    
    // save log
    savelog ($db->rdbms_geterror ());    
    $db->rdbms_close();

    return true;
  }
  else return false;
}

// ----------------------------------------------- search content ------------------------------------------------- 
// function: rdbms_searchcontent()
// input: location [string,array] (optional), exclude locations/folders [string,array] (optional), object-type [audio,binary,compressed,document,flash,image,text,video,unknown] (optional), filter for start modified date [date] (optional), filter for end modified date [date] (optional), 
//        filter for template name [string] (optional), search expression [array] (optional), search expression for object/file name [string] (optional), file extensions without dot [array] (optional)
//        filter for files size in KB in form of [>=,<=]file-size-in-KB (optional), image width in pixel [integer] (optional), image height in pixel [integer] (optional), primary image color [array] (optional), image-type [portrait,landscape,square] (optional), 
//        SW geo-border [float] (optional), NE geo-border [float] (optional), maximum search results/hits to return [integer] (optional), text IDs to be returned e.g. text:Title [array] (optional), count search result entries [boolean] (optional), 
//        log search expression [true/false] (optional), taxonomy level to include [integer] (optional), order by for sorting of the result [string] (optional)
// output: result array with object hash as 1st key and object information as 2nd key of all found objects / false

// description:
// Searches one or more expressions in the content, objectpath or other attributes of objects which are not in the recycle bin.

function rdbms_searchcontent ($folderpath="", $excludepath="", $object_type="", $date_from="", $date_to="", $template="", $expression_array="", $expression_filename="", $fileextension="", $filesize="", $imagewidth="", $imageheight="", $imagecolor="", $imagetype="", $geo_border_sw="", $geo_border_ne="", $maxhits=300, $return_text_id=array(), $count=false, $search_log=true, $taxonomy_level=2, $order_by="")
{
  // user will be provided as global for search expression logging
  global $mgmt_config, $lang, $user;

  // enable search log by default if not set
  if (!isset ($mgmt_config['search_log'])) $mgmt_config['search_log'] = true;

  // define default search query syntax "like" or "match"
  if (!isset ($mgmt_config['search_query_match'])) $mgmt_config['search_query_match'] = "match";

  // set object_type if the search is image or video related
  if (!is_array ($object_type) && (!empty ($imagewidth) || !empty ($imageheight) || !empty ($imagecolor) || !empty ($imagetype)))
  {
    $object_type = array("image", "video", "flash");
  }

  // if hierarchy URL has been provided
  if (!empty ($expression_array[0]) && strpos ("_".$expression_array[0], "%hierarchy%/") > 0)
  {
    // disable search log
    $mgmt_config['search_log'] = false;

    // analyze hierarchy
    $hierarchy_url = trim ($expression_array[0], "/");
    $hierarchy_array = explode ("/", $hierarchy_url);

    if (is_array ($hierarchy_array))
    {
      // look for the exact expression
      $mgmt_config['search_exact'] = true;

      // analyze hierarchy URL
      $domain = $hierarchy_array[0];
      $site = $hierarchy_array[1];
      $name = $hierarchy_array[2];
      $level = $hierarchy_array[3];

      $expression_array = array();

      foreach ($hierarchy_array as $hierarchy_element)
      {
        if (strpos ($hierarchy_element, "=") > 0)
        {
          list ($key, $value) = explode ("=", $hierarchy_element);

          // unescape /, : and = in value
          $value = str_replace ("&#47;", "/", $value);
          $value = str_replace ("&#58;", ":", $value);
          $value = str_replace ("&#61;", "=", $value);

          $expression_array[$key] = $value;
        }
      }
    }
  }
  else
  {
    // get publication for taxonomy based search (only applies if search is publication specific)
    if (!empty ($folderpath))
    {
      if (is_string ($folderpath))
      {
        $site = getpublication ($folderpath);
      }
      elseif (is_array ($folderpath) && sizeof ($folderpath) > 0)
      {
        foreach ($folderpath as $temp)
        {
          $site = getpublication ($temp);
  
          // if the name changed
          if (!empty ($site_prev) && $site != $site_prev)
          {
            unset ($site);
            break;
          }

          // remember as previous name
          $site_prev = $site;
        }
      }
    }
  }

  if (!empty ($folderpath) || is_array ($object_type) || !empty ($date_from) || !empty ($date_to) || !empty ($template) || is_array ($expression_array) || !empty ($expression_filename) || !empty ($filesize) || !empty ($imagewidth) || !empty ($imageheight) || !empty ($imagecolor) || !empty ($imagetype))
  {
    $db = new hcms_db($mgmt_config['dbconnect'], $mgmt_config['dbhost'], $mgmt_config['dbuser'], $mgmt_config['dbpasswd'], $mgmt_config['dbname'], $mgmt_config['dbcharset']);

    if (is_array ($object_type)) foreach ($object_type as &$value) $value = $db->rdbms_escape_string ($value);
    if ($date_from != "") $date_from = $db->rdbms_escape_string ($date_from);
    if ($date_to != "") $date_to = $db->rdbms_escape_string ($date_to);
    if ($template != "") $template = $db->rdbms_escape_string ($template);
    if ($maxhits != "")
    {
      if (strpos ($maxhits, ",") > 0)
      {
        list ($starthits, $endhits) = explode (",", $maxhits);
        $starthits = $db->rdbms_escape_string (trim ($starthits));
        $endhits = $db->rdbms_escape_string (trim ($endhits));
      }
      else $maxhits = $db->rdbms_escape_string ($maxhits);
    }

    // AND/OR operator for the search in texnodes 
    if (isset ($mgmt_config['search_operator']) && (strtoupper ($mgmt_config['search_operator']) == "AND" || strtoupper ($mgmt_config['search_operator']) == "OR"))
    {
      $operator = strtoupper ($mgmt_config['search_operator']);
    }
    else $operator = "AND";

    $sql_table = array();
    $sql_where = array();

    if (!empty ($folderpath))
    {
      // folder path => consider folderpath only when there is no filenamecheck
      if (!is_array ($folderpath) && trim ($folderpath) != "")
      {
        $folderpath = trim ($folderpath);
        $folderpath = array ($folderpath);      
      }

      $sql_temp = array();

      foreach ($folderpath as $path)
      {
        if ($path != "")
        {
          // escape characters depending on dbtype
          $path = $db->rdbms_escape_string ($path);
          // replace %
          $path = str_replace (array("%page%/", "%comp%/"), array("*page*/", "*comp*/"), $path);          
          // where clause for folderpath
          // search only on the same level of the path
          if (!empty ($mgmt_config['search_folderpath_level'])) $sql_temp[] = '(obj.objectpath LIKE BINARY "'.$path.'%" AND obj.objectpath NOT LIKE BINARY "'.$path.'%/%") OR (obj.objectpath LIKE BINARY "'.$path.'%/.folder" AND obj.objectpath NOT LIKE BINARY "'.$path.'%/%/.folder")';
          // all objects that are located in the path
          else $sql_temp[] = 'obj.objectpath LIKE BINARY "'.$path.'%"';
        }
      }

      if (is_array ($sql_temp) && sizeof ($sql_temp) > 0) $sql_where['folderpath'] = '('.implode (" OR ", $sql_temp).')';
    }

    // exclude path
    if (!empty ($excludepath))
    {
      if (!is_array ($excludepath) && trim ($excludepath) != "")
      {
        $excludepath = trim ($excludepath);
        $excludepath = array ($excludepath);
      }

      $sql_temp = array();

      foreach ($excludepath as $path)
      {
        if ($path != "")
        {
          // explicitly exclude folders from result
          if ($path == "/.folder")
          {
            // where clause for excludepath
            $sql_temp[] = 'obj.objectpath NOT LIKE BINARY "%'.$path.'"';
          }
          else
          {
            // escape characters depending on dbtype
            $path = $db->rdbms_escape_string ($path);
            // replace %
            $path = str_replace (array("%page%/", "%comp%/"), array("*page*/", "*comp*/"), $path);
            // where clause for excludepath
            $sql_temp[] = 'obj.objectpath NOT LIKE BINARY "'.$path.'%"';
          }
        }
      }

      if (is_array ($sql_temp) && sizeof ($sql_temp) > 0) $sql_where['excludepath'] = '('.implode (" AND ", $sql_temp).')';
    }

    // add file name search if expression array is of size 1 and is not a taxonomy, keyword or hierarchy path
    if (empty ($expression_filename) && is_array ($expression_array) && sizeof ($expression_array) == 1 && !empty ($expression_array[0]) && strpos ("_".$expression_array[0], "%taxonomy%/") < 1 && strpos ("_".$expression_array[0], "%keyword%/") < 1 && strpos ("_".$expression_array[0], "%hierarchy%/") < 1) 
    {
      $expression_string = $expression_array[0];
    }
    // use file name expression for search
    else $expression_string = $expression_filename;

    // prepare search expression (transform special characters)
    if (!empty ($expression_string))
    {
      $temp_array = array();
      $sql_where['filename'] = "";

      if (substr_count ($expression_string, " AND ") > 0)
      {
        $temp_array[' AND '] = explode (" AND ", $expression_string);
      }
      
      if (substr_count ($expression_string, " OR ") > 0)
      {
        $temp_array[' OR '] = explode (" OR ", $expression_string);
      }
      
      if (empty ($temp_array[' AND ']) && empty ($temp_array[' OR '])) $temp_array['none'][0] = $expression_string;
      
      foreach ($temp_array as $temp_operator => $temp2_array)
      {
        foreach ($temp2_array as $temp_expression)
        {
          $temp_expression_2 = "";
          
          $temp_expression = $db->rdbms_escape_string ($temp_expression);

          // escape asterisk to avoid manipulation by specialchr_encode
          $temp_expression = str_replace ("*", "-hcms_A-", $temp_expression);
          $temp_expression = str_replace ("?", "-hcms_Q-", $temp_expression);
          $temp_expression = str_replace ("\"", "-hcms_DQ-", $temp_expression);
          
          // encode special characters for the search in objectpath
          $temp_expression = specialchr_encode ($temp_expression);
          
          // unescape asterisk
          $temp_expression = str_replace ("-hcms_DQ-", "\"", $temp_expression);
          $temp_expression = str_replace ("-hcms_A-", "*", $temp_expression);
          $temp_expression = str_replace ("-hcms_Q-", "?", $temp_expression);
           
          // transform asterisks from search to SQL standard
          $temp_expression = trim ($temp_expression);
          $temp_expression = str_replace ("%", '\%', $temp_expression);
          $temp_expression = str_replace ("_", '\_', $temp_expression);
          $temp_expression = str_replace ("*", "%", $temp_expression);
          $temp_expression = str_replace ("?", "_", $temp_expression);
          if (substr_count ($temp_expression, "%") == 0) $temp_expression = "%".$temp_expression."%";
          
          // no exact expression
          if (substr_count ($temp_expression, "\"") < 2)
          {
            // replace spaces with wildcard
            if (strpos ($temp_expression, "~20") > 0)
            {
              $temp_expression_2 = str_replace ("~20", "%", $temp_expression);
            }
          }
          
          // remove double quotes
          $temp_expression = str_replace ("\"", "", $temp_expression);
           
          // operator
          if ($temp_operator != "none" && $sql_where['filename'] != "") $sql_where['filename'] .= $temp_operator;
          
          // search in location and object name
          if ($expression_filename != "*Null*")
          {
            // must be case insensitive
            if (!empty ($temp_expression_2)) $sql_where['filename'] .= '(obj.objectpath LIKE "'.$temp_expression.'" OR obj.objectpath LIKE "'.$temp_expression_2.'")';
            else $sql_where['filename'] .= 'obj.objectpath LIKE "'.$temp_expression.'"';
          }
        }
      }
    }

    // file extensions
    if (is_array ($fileextension) && sizeof ($fileextension) > 0)
    {
      $sql_temp = array();

      foreach ($fileextension as $temp)
      {
        $temp = trim ($temp, ".");
        if (trim ($temp) != "") $sql_temp[] = 'obj.objectpath LIKE "%.'.$temp.'"';
      }

      $sql_where['fileextension'] = '('.implode (" OR ", $sql_temp).' '.(!empty ($mgmt_config['search_folderpath_level']) ? ' OR obj.objectpath LIKE "%/.folder"' : "").')';
    }
    
    // query dates
    if (!empty ($date_from) || !empty ($date_to))
    {
      if ($date_from != "") $sql_where['datefrom'] = 'DATE(obj.date)>="'.$date_from.'"';
      if ($date_to != "") $sql_where['dateto'] = 'DATE(obj.date)<="'.$date_to.'"';
    }
      
    // query geo location
    if (!empty ($geo_border_sw) && !empty ($geo_border_ne))
    {
      if (!empty ($geo_border_sw))
      {
        $geo_border_sw = str_replace (array("(",")"), "", $geo_border_sw);
        list ($latitude, $longitude) = explode (",", $geo_border_sw);
        
        if (is_numeric ($latitude) && is_numeric ($longitude)) $sql_where['geo_border_sw'] = 'obj.latitude>='.trim($latitude).' AND obj.longitude>='.trim($longitude);
      }
      
      if (!empty ($geo_border_ne))
      {
        $geo_border_ne = str_replace (array("(",")"), "", $geo_border_ne);
        list ($latitude, $longitude) = explode (",", $geo_border_ne);
        
        if (is_numeric ($latitude) && is_numeric ($longitude)) $sql_where['geo_border_ne'] = 'obj.latitude<='.trim($latitude).' AND obj.longitude<='.trim($longitude);
      }
    }

    // query template
    if (!empty ($template))
    {
      $sql_where['template'] = 'obj.template="'.$template.'"';
    }
    
    // query search expression
    $sql_table['textnodes'] = "";
    $sql_expr_advanced = array();
    $sql_where_textnodes = "";

    if (is_array ($expression_array) && sizeof ($expression_array) > 0)
    {
      $i = 1;
      $i_kc = 1;
      $i_tx = 1;
      $i_tn = 1;
      
      reset ($expression_array);
      $expression_log = array();
      
      foreach ($expression_array as $key => $expression)
      {
        $sql_expr_advanced[$i] = "";
        
        // define search log entry
        if (!empty ($mgmt_config['search_log']) && $expression != "" && is_string ($expression) && strlen ($expression) < 800 && !is_numeric (trim ($expression)) && strpos ("_".$expression, "%taxonomy%/") < 1 && strpos ("_".$expression, "%keyword%/") < 1)
        {
          // clean expression replace | with space
          $expression = str_replace ("|", " ", strip_tags($expression));

          $expression_log[] = $mgmt_config['today']."|".$user."|".$expression;
        }
        
        // extract type from text ID
        if (strpos ($key, ":") > 0)
        {
          list ($type, $key) = explode (":", $key);
        }
        else $type = "";

        // search for specific keyword
        if (strpos ("_".$expression, "%keyword%/") > 0)
        {
          $keyword_id = getobject ($expression);
        
          if ($keyword_id > 0)
          {
            // add keywords_container table
            if ($i_kc == 1)
            {
              $sql_table['textnodes'] .= ' LEFT JOIN keywords_container AS kc1 ON obj.id=kc1.id';
            }
            elseif ($i_kc > 1)
            {
              $j = $i_kc - 1;
              $sql_table['textnodes'] .= ' LEFT JOIN keywords_container AS kc'.$i_kc.' ON kc'.$j.'.id=kc'.$i_kc.'.id';
            }
          
            $sql_expr_advanced[$i] .= 'kc'.$i_kc.'.keyword_id='.intval ($keyword_id);
            
            $i_kc++;
          }
          // objects with no keywords
          else
          {
            $sql_table['textnodes'] .= ' INNER JOIN textnodes AS tn1 ON obj.id=tn1.id';
            $sql_expr_advanced[$i] .= 'tn'.$i_kc.'.type="textk" AND tn'.$i_kc.'.textcontent=""';
          }
        }
        // search for expression (using taxonomy if enabled or full text index)
        else
        {
          $taxonomy_ids = array();

          // search in taxonomy (if no operator is used)
          if (strpos ($expression, " AND ") < 1 && strpos ($expression, " OR ") < 1 && !empty ($site) && !empty ($mgmt_config[$site]['taxonomy']))
          {
            // if no exact search for the expression is requested, use taxonomy
            if (empty ($mgmt_config['search_exact']))
            {
              // look up expression in taxonomy (in all languages)
              $taxonomy_ids = gettaxonomy_childs ($site, "", $expression, 1, true);
            }
          }

          // search in taxonomy table
          if (!empty ($taxonomy_ids) && is_array ($taxonomy_ids) && sizeof ($taxonomy_ids) > 0)
          {
            // advanced text-ID based search in taxonomy
            if ($expression != "")
            {
              if ($key != "" && $key != "0")
              {
                // add taxonomy table
                if ($i_tx == 1)
                {
                  $sql_table['textnodes'] .= ' LEFT JOIN taxonomy AS tx1 ON obj.id=tx1.id';
                }
                elseif ($i_tx > 1)
                {
                  $j = $i_tx - 1;
                  $sql_table['textnodes'] .= ' LEFT JOIN taxonomy AS tx'.$i_tx.' ON tx'.$j.'.id=tx'.$i_tx.'.id';
                }
  
                $sql_expr_advanced[$i] .= '(tx'.$i_tx.'.text_id="'.$key.'" AND tx'.$i_tx.'.taxonomy_id IN ('.implode (",", array_keys ($taxonomy_ids)).'))';
                  
                $i_tx++;
              }
              // general search in taxonomy (only one search expression possible -> break out of loop)
              else
              {
                // add taxonomy table
                $sql_table['textnodes'] .= ' LEFT JOIN taxonomy AS tx1 ON obj.id=tx1.id';
                
                $sql_expr_advanced[$i] = 'tx1.taxonomy_id IN ('.implode (",", array_keys ($taxonomy_ids)).')';
    
                break;
              }
            }
          }
          // search in textnodes table
          else
          {
            // advanced text-ID based search in textnodes (if no operator is used)
            if (strpos ($expression, " AND ") < 1 && strpos ($expression, " OR ") < 1 && (!empty ($mgmt_config['search_exact']) || $expression != "") && $key != "" && $key != "0")
            {        
              // get synonyms
              if (empty ($mgmt_config['search_exact'])) $synonym_array = getsynonym ($expression, @$lang);
              else $synonym_array = array ($expression);
    
              $r = 0;
              $sql_expr_advanced[$i] = "";

              if (is_array ($synonym_array) && sizeof ($synonym_array) > 0)
              {
                // add textnodes table
                if ($i_tn == 1)
                {
                  $sql_table['textnodes'] .= ' INNER JOIN textnodes AS tn1 ON obj.id=tn1.id';
                }
                elseif ($i_tn > 1)
                {
                  $j = $i_tn - 1;
                  $sql_table['textnodes'] .= ' INNER JOIN textnodes AS tn'.$i_tn.' ON tn'.$j.'.id=tn'.$i_tn.'.id';
                }

                foreach ($synonym_array as $synonym_expression)
                {
                  $synonym_expression_2 = "";
                  
                  $synonym_expression = trim ($synonym_expression);
                  $synonym_expression = html_decode ($synonym_expression, convert_dbcharset ($mgmt_config['dbcharset']));

                  // if LIKE query
                  if (strtolower ($mgmt_config['search_query_match']) == "like")
                  {
                    // transform wild card characters for search
                    $synonym_expression = str_replace ("%", '\%', $synonym_expression);
                    $synonym_expression = str_replace ("_", '\_', $synonym_expression);
                    $synonym_expression = str_replace ("*", "%", $synonym_expression);
                    $synonym_expression = str_replace ("?", "_", $synonym_expression);
                    
                    // replace space with wildcard for 2nd search expression
                    if (substr_count ($synonym_expression, "\"") < 2) $synonym_expression_2 = str_replace (" ", "%", $synonym_expression);
                  
                    // remove double quotes
                    $synonym_expression = str_replace ("\"", "", $synonym_expression);
                  }

                  $synonym_expression = $db->rdbms_escape_string ($synonym_expression);

                  // use OR for synonyms
                  if ($r > 0) $sql_expr_advanced[$i] .= ' OR ';

                  // look for exact expression except for keyword
                  if (!empty ($mgmt_config['search_exact']) && $type != "textk")
                  {
                    $sql_expr_advanced[$i] .= '(tn'.$i_tn.'.text_id="'.$key.'" AND LOWER(tn'.$i_tn.'.textcontent)=LOWER("'.$synonym_expression.'"))';
                  }
                  // look for expression in content
                  else
                  {
                    // search for path in textcontent requires LIKE
                    if (strpos ("_".$synonym_expression, "/") > 0) $mgmt_config['search_query_match'] = "like";

                    // LIKE search does not use stopwords or wildcards supported by MATCH AGAINST
                    if (strtolower ($mgmt_config['search_query_match']) == "like")
                    {
                      if (!empty ($synonym_expression_2)) $sql_expr_advanced[$i] .= '(tn'.$i_tn.'.text_id="'.$key.'" AND (tn'.$i_tn.'.textcontent LIKE "%'.$synonym_expression.'%" OR tn'.$i_tn.'.textcontent LIKE "%'.$synonym_expression_2.'%"))';
                      else $sql_expr_advanced[$i] .= '(tn'.$i_tn.'.text_id="'.$key.'" AND tn'.$i_tn.'.textcontent LIKE "%'.$synonym_expression.'%")';
                    }
                    else
                    {
                      // Boolean search permits the use of special operators:
                      // +	The word is mandatory in all rows returned.
                      // -	The word cannot appear in any row returned.
                      // <	The word that follows has a lower relevance than other words, although rows containing it will still match
                      // >	The word that follows has a higher relevance than other words.
                      // ()	Used to group words into subexpressions.
                      // ~	The word following contributes negatively to the relevance of the row (which is different to the '-' operator, which specifically excludes the word, or the '<' operator, which still causes the word to contribute positively to the relevance of the row.
                      // *	The wildcard, indicating zero or more characters. It can only appear at the end of a word.
                      // "	Anything enclosed in the double quotes is taken as a whole (so you can match phrases, for example).
                      if (preg_match('/["*()@~<>+-]/', $synonym_expression))
                      {
                        $search_mode = " IN BOOLEAN MODE";
                        $search_like = "";
                      }
                      // Use LIKE in order to get the desired result if MATCH AGAINST fails due to stopword restrictions
                      else
                      {
                        $search_mode = " IN NATURAL LANGUAGE MODE";
                        $search_like = ' OR tn'.$i_tn.'.textcontent LIKE "%'.$synonym_expression.'%"';
                      }
                      
                      // MATCH AGAINST uses stop words (e.g. search for "hello" will not be included in the search result)
                      $sql_expr_advanced[$i] .= '(tn'.$i_tn.'.text_id="'.$key.'" AND (MATCH (tn'.$i_tn.'.textcontent) AGAINST ("'.$synonym_expression.'"'.$search_mode.')'.$search_like.'))';
                    }
                  }

                  $r++;
                }

                $i_tn++;
              }
              
              // operator
              if (!empty ($temp_operator) && $temp_operator != "none" && $sql_expr_advanced[$i] != "") $sql_expr_advanced[$i] .= $temp_operator;
            }
            // general search in all textnodes/textcontent in table object since version 10.0.2
            elseif (!empty ($mgmt_config['search_exact']) || !empty ($temp_expression))
            {
              $temp_array = array();
              $sql_where_textnodes = "";

              if (substr_count ($expression, " AND ") > 0)
              {
                $temp_array[' AND '] = explode (" AND ", $expression);
              }

              if (substr_count ($expression, " OR ") > 0)
              {
                $temp_array[' OR '] = explode (" OR ", $expression);
              }

              if (empty ($temp_array[' AND ']) && empty ($temp_array[' OR '])) $temp_array['none'][0] = $expression;

              foreach ($temp_array as $temp_operator => $temp2_array)
              {
                foreach ($temp2_array as $temp_expression)
                {
                  // get synonyms
                  if (empty ($mgmt_config['search_exact'])) $synonym_array = getsynonym ($temp_expression, @$lang);
                  else $synonym_array = array ($temp_expression);

                  $r = 0;

                  if (is_array ($synonym_array) && sizeof ($synonym_array) > 0)
                  {
                    // deprecated since version 10.0.2: 
                    // add textnodes table (LEFT JOIN is important!)
                    // if (strpos ($sql_table['textnodes'], "LEFT JOIN textnodes AS tng ON") < 1) $sql_table['textnodes'] .= ' LEFT JOIN textnodes AS tng ON obj.id=tng.id '.$sql_table['textnodes'];

                    foreach ($synonym_array as $synonym_expression)
                    {
                      $synonym_expression = html_decode ($synonym_expression, convert_dbcharset ($mgmt_config['dbcharset']));
                      $synonym_expression = $db->rdbms_escape_string ($synonym_expression);

                      // if LIKE query
                      if (strtolower ($mgmt_config['search_query_match']) == "like")
                      {
                        // transform wild card characters for search
                        $synonym_expression = str_replace ("%", '\%', $synonym_expression);
                        $synonym_expression = str_replace ("_", '\_', $synonym_expression);        
                        $synonym_expression = str_replace ("*", "%", $synonym_expression);
                        $synonym_expression = str_replace ("?", "_", $synonym_expression);
                        
                        // replace space with wildcard for 2nd search expression
                        if (substr_count ($synonym_expression, "\"") < 2) $synonym_expression_2 = str_replace (" ", "%", $synonym_expression);
                      
                        // remove double quotes
                        $synonym_expression = str_replace ("\"", "", $synonym_expression);
                      }

                      // operator
                      if ($temp_operator != "none" && $sql_where_textnodes != "") $sql_where_textnodes .= $temp_operator;

                      // use OR for synonyms
                      if ($r > 0) $sql_where_textnodes .= ' OR ';

                      // look for exact expression
                      if (!empty ($mgmt_config['search_exact']))
                      {
                        $sql_where_textnodes .= 'obj.textcontent="'.$synonym_expression.'"';
                      }
                      // look for expression in content
                      else
                      {
                        // search for path in textcontent
                        if (strpos ("_".$synonym_expression, "/") > 0) $mgmt_config['search_query_match'] = "like";

                        // LIKE search does not use stopwords or wildcards supported by MATCH AGAINST
                        if (strtolower ($mgmt_config['search_query_match']) == "like")
                        {
                          if (!empty ($synonym_expression_2)) $sql_where_textnodes .= '(obj.textcontent LIKE "%'.$synonym_expression.'%" OR obj.textcontent LIKE "%'.$synonym_expression_2.'%")';
                          else $sql_where_textnodes .= 'obj.textcontent LIKE "%'.$synonym_expression.'%"';
                        }
                        else
                        {
                          // Boolean search permits the use of special operators:
                          // +	The word is mandatory in all rows returned.
                          // -	The word cannot appear in any row returned.
                          // <	The word that follows has a lower relevance than other words, although rows containing it will still match
                          // >	The word that follows has a higher relevance than other words.
                          // ()	Used to group words into subexpressions.
                          // ~	The word following contributes negatively to the relevance of the row (which is different to the '-' operator, which specifically excludes the word, or the '<' operator, which still causes the word to contribute positively to the relevance of the row.
                          // *	The wildcard, indicating zero or more characters. It can only appear at the end of a word.
                          // "	Anything enclosed in the double quotes is taken as a whole (so you can match phrases, for example).
                          if (preg_match('/["*()@~<>+-]/', $synonym_expression))
                          {
                            $search_mode = " IN BOOLEAN MODE";
                            $search_like = "";
                          }
                          // Use LIKE in order to get the desired result if MATCH AGAINST fails due to stopword restrictions
                          else
                          {
                            $search_mode = " IN NATURAL LANGUAGE MODE";
                            $search_like = ' OR obj.textcontent LIKE "%'.$synonym_expression.'%"';
                          }

                          // MATCH AGAINST uses stop words (e.g. search for "hello" will not be included in the search result)
                          $sql_where_textnodes .= 'MATCH (obj.textcontent) AGAINST ("'.$synonym_expression.'"'.$search_mode.')'.$search_like.'';
                        }
                      }

                      $r++;
                    }

                    // add brackets since OR is used
                    if (!empty ($sql_where_textnodes)) $sql_where_textnodes = "(".$sql_where_textnodes.")";
                  }

                  // only one search expression possible -> break out of loop (disabled in version 6.1.35 to support the combination of a general search and detailed search)
                  // break;
                }
              }
            }
          }
        }

        $i++;
      }

      // save search expression in search expression log
      if (!empty ($search_log)) savelog ($expression_log, "search");

      // remove empty array elements
      $sql_expr_advanced = array_filter ($sql_expr_advanced);

      // combine all text_id based search conditions using the operator (default is AND)
      if (isset ($sql_expr_advanced) && is_array ($sql_expr_advanced) && sizeof ($sql_expr_advanced) > 0)
      {
        // add where clause for general search term
        if (!empty ($sql_where_textnodes)) $add = $sql_where_textnodes." ".$operator." ";
        else $add = "";

        $sql_where_textnodes = "(".$add.implode (" ".$operator." ", $sql_expr_advanced).")";
      }

      // add search in object names and create final SQL where statement for search in content and object names
      if (!empty ($sql_where['filename']))
      {
        $sql_where['textnodes'] = "(".$sql_where_textnodes." OR (".$sql_where['filename']."))";
        // clear where condition for file name
        unset ($sql_where['filename']);
      }
      else $sql_where['textnodes'] = $sql_where_textnodes;
    }

    // query object type
    if (!empty ($filesize) || (is_array ($object_type) && sizeof ($object_type) > 0))
    {
      // add media table
      $sql_table['media'] = "";
      $sql_where['format'] = "";
      $sql_where['object'] = "";

      if (is_array ($object_type) && sizeof ($object_type) > 0)
      {
        foreach ($object_type as $search_type)
        {
          $search_type = strtolower ($search_type);

          // page or component object
          if ($search_type == "page" || $search_type == "comp") 
          {
            if ($sql_where['object'] != "") $sql_where['object'] .= " OR ";
            $sql_where['object'] .= 'obj.template LIKE BINARY "%.'.$search_type.'.tpl"';
          }

          // media file-type (audio, document, text, image, video, compressed, flash, binary, unknown)
          if (in_array ($search_type, array("audio","document","text","image","video","compressed","flash","binary","unknown"))) 
          {
            if (!empty ($sql_where['format'])) $sql_where['format'] .= " OR ";
            $sql_where['format'] .= 'obj.filetype="'.$search_type.'"';
          }
        }
      }

      // add brackets for OR operators for media format
      if (!empty ($sql_where['format']))
      {
        // add meta as object type if formats are set
        $sql_where['format'] = '(('.$sql_where['format'].') AND obj.template LIKE BINARY "%.meta.tpl")';

        if (!empty ($sql_where['object']))
        {
          $sql_where['format'] = '('.$sql_where['format'].' OR ('.$sql_where['object'].'))';
          unset ($sql_where['object']);
        }
      }
      else unset ($sql_where['format']);

      // if object conditions still exist, use brackets
      if (!empty ($sql_where['object']))
      {
        $sql_where['object'] = '('.$sql_where['object'].')';
      }
    }

    $sql_where['media'] = "";

    // query file size
    if (!empty ($filesize))
    {
      // set default operator
      $filesize_operator = ">=";

      // filesize includes operator
      if ($filesize < 1)
      {
        // >=
        if (strpos ("_".$filesize, "&gt;=") > 0)
        {
          $filesize_operator = substr ($filesize, 0, 5);
          $filesize = substr ($filesize, 5);
        }
        // >=
        elseif (strpos ("_".$filesize, ">=") > 0)
        {
          $filesize_operator = substr ($filesize, 0, 2);
          $filesize = substr ($filesize, 2);
        }
        // <=
        elseif (strpos ("_".$filesize, "&lt;=") > 0)
        {
          $filesize_operator = substr ($filesize, 0, 5);
          $filesize = substr ($filesize, 5);
        }
        // <=
        elseif (strpos ("_".$filesize, "<=") > 0)
        {
          $filesize_operator = substr ($filesize, 0, 2);
          $filesize = substr ($filesize, 2);
        }
        // >
        elseif (strpos ("_".$filesize, "&gt;") > 0)
        {
          $filesize_operator = substr ($filesize, 0, 4);
          $filesize = substr ($filesize, 4);
        }
        // >
        elseif (strpos ("_".$filesize, ">") > 0)
        {
          $filesize_operator = substr ($filesize, 0, 1);
          $filesize = substr ($filesize, 1);
        }
        // <
        elseif (strpos ("_".$filesize, "&lt;") > 0)
        {
          $filesize_operator = substr ($filesize, 0, 4);
          $filesize = substr ($filesize, 4);
        }
        // <
        elseif (strpos ("_".$filesize, "<") > 0)
        {
          $filesize_operator = substr ($filesize, 0, 1);
          $filesize = substr ($filesize, 1);
        }
      }

      if ($filesize > 0)
      {
        if (!empty ($sql_where['media'])) $sql_where['media'] .= ' AND ';
      
        $sql_where['media'] .= 'obj.filesize'.$filesize_operator.intval($filesize);
      }
    }

    // query image and video
    if (isset ($object_type) && is_array ($object_type) && (in_array ("image", $object_type) || in_array ("video", $object_type)))
    {
      if (!empty ($filesize) || !empty ($imagewidth) || !empty ($imageheight) || (isset ($imagecolor) && is_array ($imagecolor)) || !empty ($imagetype))
      {
        // parameter imagewidth can be used as general image size parameter
        // search for image_size (defined by min-max value)
        if (!empty ($imagewidth) && substr_count ($imagewidth, "-") == 1)
        {
          list ($imagewidth_min, $imagewidth_max) = explode ("-", $imagewidth);
          $sql_where['media'] .= (($sql_where['media'] == '') ? '' : ' AND ').'(obj.width>='.intval($imagewidth_min).' OR obj.height>='.intval($imagewidth_min).') AND (obj.width<='.intval($imagewidth_max).' OR obj.height<='.intval($imagewidth_max).')';
        }
        else
        {			
          //search for exact image width
          if (!empty ($imagewidth) && $imagewidth > 0)
          {
            if (!empty ($sql_where['media'])) $sql_where['media'] .= ' AND ';
            
            $sql_where['media'] .= 'obj.width='.intval($imagewidth);
          }
     
          // search for exact image height
          if (!empty ($imageheight) && $imageheight > 0)
          {
            if (!empty ($sql_where['media'])) $sql_where['media'] .= ' AND ';
            
            $sql_where['media'] .= 'obj.height='.intval($imageheight);
          }
        }

        if (isset ($imagecolor) && is_array ($imagecolor))
        {
          foreach ($imagecolor as $colorkey)
          {
            if (!empty ($colorkey))
            {
              if (!empty ($sql_where['media'])) $sql_where['media'] .= ' AND ';

              $sql_where['media'] .= 'INSTR(obj.colorkey,"'.$colorkey.'")>0';
            }
          }
        }

        if (!empty ($imagetype))
        {
          if (!empty ($sql_where['media'])) $sql_where['media'] .= ' AND ';
          
          $sql_where['media'] .= 'obj.imagetype="'.$imagetype.'"';
        }
      }
    }

    // remove empty array elements
    $sql_table = array_filter ($sql_table);
    $sql_where = array_filter ($sql_where);

    // add rows to the result array
    $sql_attr = array();
    $sql_add_attr = "";
    $sql_where_text_id = "";

    if (is_array ($return_text_id) && sizeof ($return_text_id) > 0)
    {
      // add object information to the result array
      if (in_array ("date", $return_text_id) || in_array ("modifieddate", $return_text_id))
      {
        $sql_attr[] = "obj.date";
      }

      if (in_array ("createdate", $return_text_id))
      {
        $sql_attr[] = "obj.createdate";
      }

      if (in_array ("publishdate", $return_text_id))
      {
        $sql_attr[] = "obj.publishdate";
      }

      if (in_array ("user", $return_text_id) || in_array ("owner", $return_text_id))
      {
        $sql_attr[] = "obj.user";
      }

      if (in_array ("filesize", $return_text_id) || in_array ("width", $return_text_id) || in_array ("height", $return_text_id))
      {
        $sql_attr[] = "obj.filesize";
        $sql_attr[] = "obj.width";
        $sql_attr[] = "obj.height";
      }

      // add text IDs and content to the result array
      foreach ($return_text_id as $text_id)
      {
        if (substr ($text_id, 0, 5) == "text:")
        {
          $sql_add_text_id = true;
          break;
        }
      }

      // add join for textnodes table (due to DISTINCT objectpath the results will only include text according to the WHERE condition)
      if (!empty ($sql_add_text_id))
      {
        $sql_attr[] = "tng.text_id";
        $sql_attr[] = "tng.textcontent";
        if (empty ($sql_table['textnodes'])) $sql_table['textnodes'] = "";
        if (strpos ($sql_table['textnodes'], " ON obj.id=tng.id ") < 1) $sql_table['textnodes']  .= ' LEFT JOIN textnodes AS tng ON obj.id=tng.id';
      }

      // add attributes to search query
      if (sizeof ($sql_attr) > 0) $sql_add_attr = ", ".implode (", ", $sql_attr);
    }

    // order by
    if (empty ($order_by)) $order_by = "obj.objectpath";
    else $order_by = $db->rdbms_escape_string (trim ($order_by));

    // build SQL statement
    $sql = 'SELECT obj.objectpath, obj.hash, obj.id, obj.media, obj.workflowstatus'.$sql_add_attr .' FROM object AS obj ';
    if (isset ($sql_table) && is_array ($sql_table) && sizeof ($sql_table) > 0) $sql .= implode (' ', $sql_table).' ';
    $sql .= 'WHERE obj.deleteuser="" ';
    if (isset ($sql_where) && is_array ($sql_where) && sizeof ($sql_where) > 0) $sql .= 'AND '.implode (' AND ', $sql_where).' ';
    // removed "order by substring_index()" due to poor DB performance and moved to array sort
    // $sql .= ' ORDER BY SUBSTRING_INDEX(obj.objectpath,"/",-1)';
    $sql .= 'GROUP BY obj.hash ORDER BY '.$order_by;

    if (isset ($starthits) && intval ($starthits) >= 0 && isset ($endhits) && intval ($endhits) > 0) $sql .= ' LIMIT '.intval ($starthits).','.intval ($endhits);
    elseif (isset ($maxhits) && intval ($maxhits) > 0) $sql .= ' LIMIT 0,'.intval ($maxhits);

    $errcode = "50082";
    $done = $db->rdbms_query ($sql, $errcode, $mgmt_config['today']);

    // prepare result
    $objectpath = array();

    if ($done)
    {      
      while ($row = $db->rdbms_getresultrow ())
      {
        if (!empty ($row['hash']) && !empty ($row['objectpath']))
        {
          $hash = $row['hash'];

          $objectpath[$hash]['objectpath'] = str_replace (array("*page*/", "*comp*/"), array("%page%/", "%comp%/"), $row['objectpath']);
          $objectpath[$hash]['type'] = (substr ($row['objectpath'], -7) == ".folder" ? "folder" : "object");
          $objectpath[$hash]['container_id'] =  sprintf ("%07d", $row['id']);
          $objectpath[$hash]['media'] =  $row['media'];
          $objectpath[$hash]['workflowstatus'] =  $row['workflowstatus'];

          if (!empty ($row['date'])) $objectpath[$hash]['date'] = $row['date'];
          if (!empty ($row['createdate'])) $objectpath[$hash]['createdate'] = $row['createdate'];
          if (!empty ($row['publishdate'])) $objectpath[$hash]['publishdate'] = $row['publishdate'];
          if (!empty ($row['user'])) $objectpath[$hash]['user'] = $row['user'];
          if (!empty ($row['filesize'])) $objectpath[$hash]['filesize'] = $row['filesize'];
          if (!empty ($row['width'])) $objectpath[$hash]['width'] = $row['width'];
          if (!empty ($row['height'])) $objectpath[$hash]['height'] = $row['height'];
          if (!empty ($row['text_id'])) $objectpath[$hash]['text:'.$row['text_id']] = $row['textcontent'];

          // object and location name
          if (is_array ($return_text_id) && (in_array ("object", $return_text_id) || in_array ("location", $return_text_id)))
          {
            $temp_site = getpublication ($objectpath[$hash]['objectpath']);
            $temp_cat = getcategory ($temp_site, $objectpath[$hash]['objectpath']);

            // folder
            if (getobject ($objectpath[$hash]['objectpath']) == ".folder")
            {
              $temp_objectpath = getlocationname ($temp_site, getlocation ($objectpath[$hash]['objectpath']), $temp_cat, "path");
            }
            // object
            else
            {
              $temp_objectpath = getlocationname ($temp_site, $objectpath[$hash]['objectpath'], $temp_cat, "path");
            }

            $objectpath[$hash]['location'] = getlocation ($temp_objectpath);
            $objectpath[$hash]['object'] = getobject ($temp_objectpath);
          }

          if (!empty ($row['media']))
          {
            // links
            if (in_array ("wrapperlink", $return_text_id)) $objectpath[$hash]['wrapperlink'] = $mgmt_config['url_path_cms']."?wl=".$hash;
            if (in_array ("downloadlink", $return_text_id)) $objectpath[$hash]['downloadlink'] = $mgmt_config['url_path_cms']."?dl=".$hash;

            // thumbnail
            if (in_array ("thumbnail", $return_text_id))
            {
              $temp_site = getpublication ($objectpath[$hash]['objectpath']);
              $mediadir = getmedialocation ($temp_site, $row['media'], "abs_path_media");
              $media_info = getfileinfo ($temp_site, $row['media'], "comp");

              // try to create the thumbnail if not available
              if (!empty ($mgmt_config['recreate_preview']) && !file_exists ($mediadir.$temp_site."/".$media_info['filename'].".thumb.jpg"))
              {
                createmedia ($temp_site, $mediadir.$temp_site."/", $mediadir.$temp_site."/", $media_info['file'], "", "thumbnail", false, true);
              }
              
              if (is_file ($mediadir.$temp_site."/".$media_info['filename'].".thumb.jpg") && filesize ($mediadir.$temp_site."/".$media_info['filename'].".thumb.jpg") > 100)
              {
                $objectpath[$hash]['thumbnail'] = createviewlink ($temp_site, $media_info['filename'].".thumb.jpg");
              }
            }
          }
        }
      }      
    }

    // count searchresults
    if (!empty ($count))
    {
      $sql = 'SELECT COUNT(DISTINCT obj.hash) as cnt FROM object AS obj';
      if (isset ($sql_table) && is_array ($sql_table) && sizeof ($sql_table) > 0) $sql .= ' '.implode (' ', $sql_table).' ';
      $sql .= ' WHERE obj.deleteuser="" ';
      if (isset ($sql_where) && is_array ($sql_where) && sizeof ($sql_where) > 0) $sql .= ' AND '.implode (' AND ', $sql_where);

      $errcode = "50081";
      $done = $db->rdbms_query ($sql, $errcode, $mgmt_config['today']);

      if ($done && ($row = $db->rdbms_getresultrow ()))
      {         
        if ($row['cnt'] != "") $objectpath['count'] = $row['cnt']; 
      }
    }

    // save log
    savelog ($db->rdbms_geterror ());    
    $db->rdbms_close();

    if (!empty ($objectpath) && is_array ($objectpath) && sizeof ($objectpath) > 0)
    {
      return $objectpath;
    }
    else return false;
  }
  else return false;
}

// ----------------------------------------------- replace content -------------------------------------------------
// function: rdbms_replacecontent()
// input: location path [string], object-type [string] (optional), filter for start modified date [date] (optional), filter for end modified date [date] (optional), search expression [string], replace expression [string], user name [string] (optional)
// output: result array with object paths of all touched objects / false

// description:
// Replaces an expression by another in the content of object which are not in the recycle bin.

function rdbms_replacecontent ($folderpath, $object_type="", $date_from="", $date_to="", $search_expression="", $replace_expression="", $user="sys")
{
  global $mgmt_config;

  $error = array();

  if ($folderpath != "" && $search_expression != "")
  {
    $db = new hcms_db ($mgmt_config['dbconnect'], $mgmt_config['dbhost'], $mgmt_config['dbuser'], $mgmt_config['dbpasswd'], $mgmt_config['dbname'], $mgmt_config['dbcharset']);

    $folderpath = $db->rdbms_escape_string ($folderpath);
    if (is_array ($object_type)) foreach ($object_type as &$value) $value = $db->rdbms_escape_string ($value);
    if ($date_from != "") $date_from = $db->rdbms_escape_string ($date_from);
    if ($date_to != "") $date_to = $db->rdbms_escape_string ($date_to);
    if ($user != "") $user = $db->rdbms_escape_string ($user);
 
    // replace %
    $folderpath = str_replace (array("%page%/", "%comp%/"), array("*page*/", "*comp*/"), $folderpath);

    // query object type
    if (is_array ($object_type) && sizeof ($object_type) > 0)
    {
      // add media table
      $sql_table['media'] = "";
      $sql_where['format'] = "";
      $sql_where['object'] = "";

      if (is_array ($object_type) && sizeof ($object_type) > 0)
      {
        foreach ($object_type as $search_type)
        {
          $search_type = strtolower ($search_type);

          // page or component object
          if ($search_type == "page" || $search_type == "comp") 
          {
            if ($sql_where['object'] != "") $sql_where['object'] .= " OR ";
            $sql_where['object'] .= 'obj.template LIKE BINARY "%.'.$search_type.'.tpl"';
          }

          // media file-type (audio, document, text, image, video, compressed, flash, binary, unknown)
          if (in_array ($search_type, array("audio","document","text","image","video","compressed","flash","binary","unknown"))) 
          {
            if (!empty ($sql_where['format'])) $sql_where['format'] .= " OR ";
            $sql_where['format'] .= 'obj.filetype="'.$search_type.'"';
          }
        }
      }

      // add brackets for OR operators for media format
      if (!empty ($sql_where['format']))
      {
        // add meta as object type if formats are set
        $sql_where['format'] = '(('.$sql_where['format'].') AND obj.template LIKE BINARY "%.meta.tpl")';

        if (!empty ($sql_where['object']))
        {
          $sql_where['format'] = '('.$sql_where['format'].' OR ('.$sql_where['object'].'))';
          unset ($sql_where['object']);
        }
      }
      else unset ($sql_where['format']);

      // if object conditions still exist, use brackets
      if (!empty ($sql_where['object']))
      {
        $sql_where['object'] = '('.$sql_where['object'].')';
      }
    }  

    // folder path
    $sql_where['filename'] = 'obj.objectpath LIKE BINARY "'.$folderpath.'%"';

    // dates
    if (!empty ($date_from)) $sql_where['datefrom'] = 'DATE(obj.date)>="'.$date_from.'"';
    if (!empty ($date_to)) $sql_where['dateto'] = 'DATE(obj.date)<="'.$date_to.'"'; 

    // search expression
    if ($search_expression != "")
    {
      $expression = $search_expression;

      $expression = html_decode ($expression, convert_dbcharset ($mgmt_config['dbcharset']));
      $expression = $db->rdbms_escape_string ($expression);

      // transform wild card characters for search
      $expression = str_replace ("%", '\%', $expression);
      $expression = str_replace ("_", '\_', $expression);      
      $expression = str_replace ("*", "%", $expression);
      $expression = str_replace ("?", "_", $expression);

      $sql_where['textnodes'] = 'tn1.textcontent LIKE "%'.$expression.'%"';
    }    

    $sql = 'SELECT obj.objectpath, obj.hash, obj.id, obj.container, obj.media, obj.createdate, obj.date, obj.publishdate, obj.user, tn1.text_id, tn1.textcontent FROM object AS obj ';
    if (is_array ($sql_table) && sizeof ($sql_table) > 0) $sql .= implode (" ", $sql_table).' ';
    $sql .= 'WHERE obj.deleteuser="" AND ';    
    if (is_array ($sql_where) && sizeof ($sql_where) > 0) $sql .= implode (" AND ", $sql_where).' ';

    $errcode = "50063";
    $done = $db->rdbms_query ($sql, $errcode, $mgmt_config['today'], "select");

    $container_id_prev = "";
    $containerdata = "";

    if ($done)
    {
      // transform search expression
      $search_expression = str_replace ("*", "", $search_expression);
      $search_expression = str_replace ("?", "", $search_expression);

      $search_expression_esc = html_encode ($search_expression, convert_dbcharset ($mgmt_config['dbcharset']));
      $search_expression = $db->rdbms_escape_string ($search_expression);

      // transform replace expression
      $replace_expression_esc = html_encode ($replace_expression, convert_dbcharset ($mgmt_config['dbcharset']));
      $replace_expression = $db->rdbms_escape_string ($replace_expression);

      $num_rows = $db->rdbms_getnumrows ("select");

      if ($num_rows > 0)
      {
        for ($i = 0; $i < $num_rows; $i++)
        {
          $row = $db->rdbms_getresultrow ("select", $i);

          $hash = $row['hash'];
          $id = intval ($row['id']);

          $objectpath[$hash]['objectpath'] = str_replace (array("*page*/", "*comp*/"), array("%page%/", "%comp%/"), $row['objectpath']);
          $objectpath[$hash]['container_id']  = sprintf ("%07d", $row['id']);
          $objectpath[$hash]['media'] =  $row['media'];
          if (!empty ($row['date'])) $objectpath[$hash]['date'] = $row['date'];
          if (!empty ($row['createdate'])) $objectpath[$hash]['createdate'] = $row['createdate'];
          if (!empty ($row['publishdate'])) $objectpath[$hash]['publishdate'] = $row['publishdate'];
          if (!empty ($row['user'])) $objectpath[$hash]['user'] = $row['user'];

          $container_file = $row['container'];
          $text_id = $row['text_id'];
          $textcontent = $row['textcontent'];

          if ($id != "")
          {
            // replace expression for update in RDBMS
            $textcontent = str_replace ($search_expression, $replace_expression_esc, $textcontent); 
            $textcontent = str_replace ($search_expression_esc, $replace_expression_esc, $textcontent);                 

            // replace expression in container
            $container_id = substr ($container_file, 0, strpos ($container_file, ".xml"));

            // save container, execute query and load container if container ID changed
            if ($container_id != $container_id_prev)
            {
              if ($containerdata != "" && $containerdata != false)
              {
                // save container
                $result_save = savecontainer ($container_id_prev, "work", $containerdata, $user);
                
                if ($result_save == false)
                {
                  $errcode = "10911";
                  $error[] = $mgmt_config['today']."|db_connect_rdbms.php|error|".$errcode."|Container '".$container_id_prev."' could not be saved";  

                  // save log
                  savelog ($error);                                    
                }
                else
                {
                  // update content in database
                  $errcode = "50024";
                  
                  foreach ($sql_array as $sql)
                  {
                    $db->rdbms_query ($sql, $errcode, $mgmt_config['today'], "update");
                  }
                }
              }
              
              // Emptying collected sql statements
              $sql_array = array();
              
              // load container
              $containerdata = loadcontainer ($container_id, "work", $user);
            }

            // set previous container ID
            $container_id_prev = $container_id;

            // save content container
            if (!empty ($containerdata))
            {       
              $xml_search_array = selectcontent ($containerdata, "<text>", "<text_id>", $text_id);

              if (!empty ($xml_search_array[0]))
              {
                $xml_content = getxmlcontent ($xml_search_array[0], "<textcontent>");

                if (!empty ($xml_content[0]))
                {
                  if (substr_count ($xml_content[0], $search_expression_esc) > 0 || substr_count ($xml_content[0], $search_expression) > 0)
                  {
                    // replace expression in textcontent
                    $xml_replace = str_replace ($search_expression, $replace_expression, $xml_content[0]);
                    
                    if ($search_expression != $search_expression_esc)
                    {
                      $xml_replace = str_replace ($search_expression_esc, $replace_expression_esc, $xml_replace);
                    }

                    // replace textcontent in text
                    $xml_replace = str_replace ($xml_content[0], $xml_replace, $xml_search_array[0]);

                    // replace text in container
                    $containerdata = str_replace ($xml_search_array[0], $xml_replace, $containerdata);
                  }

                  // update content in database
                  $sql_array[] = 'UPDATE textnodes SET textcontent="'.$textcontent.'" WHERE id='.$id.' AND text_id="'.$text_id.'"';
                }  
              }       
            }       
          }  
        }
      }
    }

    // save last container
    $result_save = savecontainer ($container_id_prev, "work", $containerdata, $user);

    if ($result_save == false)
    {
      $errcode = "10911";
      $error[] = $mgmt_config['today']."|db_connect_rdbms.php|error|".$errcode."|Container '".$container_id_prev."' could not be saved";  

      // save log
      savelog ($error);                                    
    }
    else
    {
      // update content in database
      $errcode = "50040";

      foreach ($sql_array as $sql)
      {
        $db->rdbms_query ($sql, $errcode, $mgmt_config['today'], "update");
      }
    }

    // save log
    savelog ($db->rdbms_geterror ());    
    $db->rdbms_close(); 

    if (isset ($objectpath) && is_array ($objectpath))
    {
      return $objectpath;
    }
    else return false;
  }
  else return false;
}

// ----------------------------------------------- search user ------------------------------------------------- 
// function: rdbms_searchuser()
// input: publication name [string] (optional), user name [string], max. hits [integer] (optional), text IDs to be returned [array] (optional), count search result entries [boolean] (optional)
// output: objectpath array with hashcode as key and path as value / false

// description:
// Queries all objects of a user.

function rdbms_searchuser ($site="", $user="", $maxhits=300, $return_text_id=array(), $count=false)
{
  global $mgmt_config;

  if ($user != "")
  {
    $db = new hcms_db($mgmt_config['dbconnect'], $mgmt_config['dbhost'], $mgmt_config['dbuser'], $mgmt_config['dbpasswd'], $mgmt_config['dbname'], $mgmt_config['dbcharset']);

    if ($site != "" && $site != "*Null*") $site = $db->rdbms_escape_string ($site);
    $user = $db->rdbms_escape_string ($user);
    $maxhits = intval ($maxhits);

    $sql_add_attr = "";
    $sql_attr = array();
    $sql_table = array();

    if (is_array ($return_text_id) && sizeof ($return_text_id) > 0)
    {
      // add object information to the result array
      if (in_array ("date", $return_text_id) || in_array ("modifieddate", $return_text_id))
      {
        $sql_attr[] = "obj.date";
      }

      if (in_array ("createdate", $return_text_id))
      {
        $sql_attr[] = "obj.createdate";
      }

      if (in_array ("publishdate", $return_text_id))
      {
        $sql_attr[] = "obj.publishdate";
      }
      
      if (in_array ("user", $return_text_id) || in_array ("owner", $return_text_id))
      {
        $sql_attr[] = "obj.user";
      }

      if (in_array ("filesize", $return_text_id))
      {
        $sql_attr[] = "obj.filesize";
      }

      // add text IDs and content to the result array
      foreach ($return_text_id as $text_id)
      {
        if (substr ($text_id, 0, 5) == "text:")
        {
          $sql_add_text_id = true;
          break;
        }
      }

      // add join for textnodes table
      if (!empty ($sql_add_text_id))
      {
        $sql_attr[] = "tng.text_id";
        $sql_attr[] = "tng.textcontent";
        if (empty ($sql_table['textnodes'])) $sql_table['textnodes'] = "";
        if (strpos ($sql_table['textnodes'], " ON obj.id=tng.id ") < 1) $sql_table['textnodes']  .= ' LEFT JOIN textnodes AS tng ON obj.id=tng.id';
      }

      // add attributes to search query
      if (sizeof ($sql_attr) > 0) $sql_add_attr = ", ".implode (", ", $sql_attr);
    }  

    $sql = 'SELECT obj.objectpath, obj.hash, obj.id, obj.media'.$sql_add_attr.' FROM object AS obj ';
    if (isset ($sql_table) && is_array ($sql_table) && sizeof ($sql_table) > 0) $sql .= implode (' ', $sql_table).' ';
    $sql .= 'WHERE obj.objectpath!="" AND obj.user="'.$user.'" ';
    if ($site != "" && $site != "*Null*") $sql .= 'AND (obj.objectpath LIKE BINARY "*page*/'.$site.'/%" OR obj.objectpath LIKE BINARY "*comp*/'.$site.'/%") ';
    $sql .= 'ORDER BY obj.date DESC ';
    if ($maxhits > 0) $sql .= 'LIMIT 0,'.intval($maxhits);

    $errcode = "50025";
    $done = $db->rdbms_query($sql, $errcode, $mgmt_config['today']);

    $objectpath = array();

    if ($done)
    {
      while ($row = $db->rdbms_getresultrow ())
      {
        if (!empty ($row['hash']) && !empty ($row['objectpath']))
        {
          $hash = $row['hash'];

          $objectpath[$hash]['objectpath'] = str_replace (array("*page*/", "*comp*/"), array("%page%/", "%comp%/"), $row['objectpath']);
          $objectpath[$hash]['container_id'] =  sprintf ("%07d", $row['id']);
          $objectpath[$hash]['media'] =  $row['media'];
          if (!empty ($row['date'])) $objectpath[$hash]['date'] = $row['date'];
          if (!empty ($row['createdate'])) $objectpath[$hash]['createdate'] = $row['createdate'];
          if (!empty ($row['publishdate'])) $objectpath[$hash]['publishdate'] = $row['publishdate'];
          if (!empty ($row['user'])) $objectpath[$hash]['user'] = $row['user'];
          if (!empty ($row['filesize'])) $objectpath[$hash]['filesize'] = $row['filesize'];
          if (!empty ($row['text_id'])) $objectpath[$hash]['text:'.$row['text_id']] = $row['textcontent'];
        }
      }
    }

    // count searchresults
    if (!empty ($count))
    {
      $sql = 'SELECT COUNT(DISTINCT obj.objectpath) as rowcount FROM object AS obj ';
      if (isset ($sql_table) && is_array ($sql_table) && sizeof ($sql_table) > 0) $sql .= implode (' ', $sql_table).' ';
      $sql .= 'WHERE obj.objectpath!="" AND obj.user="'.$user.'" ';
      if ($site != "" && $site != "*Null*") $sql .= 'AND (obj.objectpath LIKE BINARY "*page*/'.$site.'/%" OR obj.objectpath LIKE BINARY "*comp*/'.$site.'/%")';

      $errcode = "50021";
      $done = $db->rdbms_query ($sql, $errcode, $mgmt_config['today']);

      if ($done && ($row = $db->rdbms_getresultrow ()))
      {         
        if ($row['cnt'] != "") $objectpath['count'] = $row['rowcount']; 
      }
    }

    // save log
    savelog ($db->rdbms_geterror ());    
    $db->rdbms_close();

    if (!empty ($objectpath) && is_array ($objectpath) && sizeof ($objectpath) > 0) return $objectpath;
    else return false;
  }
  else return false;
}

// ----------------------------------------------- search recipient ------------------------------------------------- 
// function: rdbms_searchrecipient()
// input: publication name [string], sender user name [string], recpient user name or e-mail address [string], from date [date], to date [date], max. hits [integer] (optional), text IDs to be returned [array] (optional), count search result entries [boolean] (optional)
// output: objectpath array with hashcode as key and path as value / false

// description:
// Queries all objects of a sender, recipient or by date.

function rdbms_searchrecipient ($site, $from_user, $to_user_email, $date_from, $date_to, $maxhits=300, $return_text_id=array(), $count=false)
{
  global $mgmt_config;

  if ($site != "" || $from_user != "" || $to_user_email != "" || $date_from != "" || $date_to != "")
  {
    $db = new hcms_db($mgmt_config['dbconnect'], $mgmt_config['dbhost'], $mgmt_config['dbuser'], $mgmt_config['dbpasswd'], $mgmt_config['dbname'], $mgmt_config['dbcharset']);

    if ($site != "" && $site != "*Null*") $site = $db->rdbms_escape_string ($site);

    if ($from_user != "")
    {
      // cut off additional information in brackets after the user name
      if (strpos ($from_user, "(") > 0) $from_user = trim (substr ($from_user, 0, strpos ($from_user, "(")));
      $from_user = $db->rdbms_escape_string ($from_user);
    }

    if ($to_user_email != "")
    {
      // cut off additional information in brackets after the user name
      if (strpos ($to_user_email, "(") > 0) $to_user_email = trim (substr ($to_user_email, 0, strpos ($to_user_email, "(")));
      $to_user_email = $db->rdbms_escape_string ($to_user_email);
    }

    if ($date_from != "") $date_from = $db->rdbms_escape_string ($date_from);
    if ($date_to != "") $date_to = $db->rdbms_escape_string ($date_to);
    $maxhits = intval ($maxhits);

    $sql_add_attr = "";
    $sql_attr = array();
    $sql_table = array();

    if (is_array ($return_text_id) && sizeof ($return_text_id) > 0)
    {
      // add object information to the result array
      if (in_array ("date", $return_text_id) || in_array ("modifieddate", $return_text_id))
      {
        $sql_attr[] = "obj.date";
      }
  
      if (in_array ("createdate", $return_text_id))
      {
        $sql_attr[] = "obj.createdate";
      }

      if (in_array ("publishdate", $return_text_id))
      {
        $sql_attr[] = "obj.publishdate";
      }

      if (in_array ("user", $return_text_id) || in_array ("owner", $return_text_id))
      {
        $sql_attr[] = "obj.user";
      }

      if (in_array ("filesize", $return_text_id))
      {
        $sql_attr[] = "obj.filesize";
      }

      // add text IDs and content to the result array
      foreach ($return_text_id as $text_id)
      {
        if (substr ($text_id, 0, 5) == "text:")
        {
          $sql_add_text_id = true;
          break;
        }
      }

      // add join for textnodes table
      if (!empty ($sql_add_text_id))
      {
        $sql_attr[] = "tng.text_id";
        $sql_attr[] = "tng.textcontent";
        if (empty ($sql_table['textnodes'])) $sql_table['textnodes'] = "";
        if (strpos ($sql_table['textnodes'], " ON obj.id=tng.id ") < 1) $sql_table['textnodes']  .= ' LEFT JOIN textnodes AS tng ON obj.id=tng.id';
      }

      // add attributes to search query
      if (sizeof ($sql_attr) > 0) $sql_add_attr = ", ".implode (", ", $sql_attr);
    }  

    // build select query
    $sql = 'SELECT obj.objectpath, obj.hash, obj.id, obj.media'.$sql_add_attr.' FROM object AS obj INNER JOIN recipient AS rec ON obj.object_id=rec.object_id ';

    if (isset ($sql_table) && is_array ($sql_table) && sizeof ($sql_table) > 0) $sql .= implode (' ', $sql_table).' ';

    $sql .= 'WHERE obj.objectpath!="" ';

    if ($site != "" && $site != "*Null*") $sql .= 'AND (obj.objectpath LIKE BINARY "*page*/'.$site.'/%" OR obj.objectpath LIKE BINARY "*comp*/'.$site.'/%") ';
    if ($from_user != "") $sql .= 'AND rec.from_user LIKE BINARY "%'.$from_user.'%" ';
    if ($to_user_email != "") $sql .= 'AND (rec.to_user LIKE "%'.$to_user_email.'%" OR rec.email LIKE "%'.$to_user_email.'%") ';
    if ($date_from != "") $sql .= 'AND DATE(rec.date)>="'.$date_from.'" ';
    if ($date_to != "") $sql .= 'AND DATE(rec.date)<="'.$date_to.'" ';

    $sql .= 'ORDER BY rec.date DESC ';

    if ($maxhits > 0) $sql .= 'LIMIT 0,'.intval($maxhits);

    $errcode = "50026";
    $done = $db->rdbms_query($sql, $errcode, $mgmt_config['today']);

    $objectpath = array();

    if ($done)
    {
      while ($row = $db->rdbms_getresultrow ())
      {
        if (!empty ($row['hash']) && !empty ($row['objectpath']))
        {
          $hash = $row['hash'];

          $objectpath[$hash]['objectpath'] = str_replace (array("*page*/", "*comp*/"), array("%page%/", "%comp%/"), $row['objectpath']);
          $objectpath[$hash]['container_id'] =  sprintf ("%07d", $row['id']);
          $objectpath[$hash]['media'] =  $row['media'];
          if (!empty ($row['date'])) $objectpath[$hash]['date'] = $row['date'];
          if (!empty ($row['createdate'])) $objectpath[$hash]['createdate'] = $row['createdate'];
          if (!empty ($row['publishdate'])) $objectpath[$hash]['publishdate'] = $row['publishdate'];
          if (!empty ($row['user'])) $objectpath[$hash]['user'] = $row['user'];
          if (!empty ($row['filesize'])) $objectpath[$hash]['filesize'] = $row['filesize'];
          if (!empty ($row['text_id'])) $objectpath[$hash]['text:'.$row['text_id']] = $row['textcontent'];
        } 
      }
    }

    // count searchresults
    if (!empty ($count))
    {
      $sql = 'SELECT COUNT(DISTINCT obj.objectpath) as rowcount FROM object AS obj ';
      if (isset ($sql_table) && is_array ($sql_table) && sizeof ($sql_table) > 0) $sql .= implode (' ', $sql_table).' ';
      if ($site != "" && $site != "*Null*") $sql .= ' WHERE obj.user="'.$user.'" AND (obj.objectpath LIKE BINARY "*page*/'.$site.'/%" OR obj.objectpath LIKE BINARY "*comp*/'.$site.'/%")';

      $errcode = "50027";
      $done = $db->rdbms_query ($sql, $errcode, $mgmt_config['today']);

      if ($done && ($row = $db->rdbms_getresultrow ()))
      {         
        if ($row['cnt'] != "") $objectpath['count'] = $row['rowcount']; 
      }
    }

    // save log
    savelog ($db->rdbms_geterror ());    
    $db->rdbms_close();

    if (!empty ($objectpath) && is_array ($objectpath) && sizeof ($objectpath) > 0) return $objectpath;
    else return false;
  }
  else return false;
} 

// ----------------------------------------------- get content -------------------------------------------------
// function: rdbms_getcontent()
// input: publication name [string], container ID [integer], filter for text-ID [string] (optional), filter for type [string] (optional), filter for user name [string] (optional)
// output: result array with text ID as key and content as value / false

// description:
// Selects content for a container in the database.

function rdbms_getcontent ($site, $container_id, $text_id="", $type="", $user="")
{
  global $mgmt_config;

  if (intval ($container_id) > 0)
  {
    $result = array();

    $db = new hcms_db ($mgmt_config['dbconnect'], $mgmt_config['dbhost'], $mgmt_config['dbuser'], $mgmt_config['dbpasswd'], $mgmt_config['dbname'], $mgmt_config['dbcharset']);

    if ($type == "u" || $type == "f" || $type == "l" || $type == "c" || $type == "d" || $type == "k") $type = "text".$type;

    $container_id = intval ($container_id);
    if ($text_id != "") $text_id = $db->rdbms_escape_string($text_id);
    if ($type != "") $type = $db->rdbms_escape_string($type);
    if ($user != "") $user = $db->rdbms_escape_string($user);

    $sql = 'SELECT text_id, textcontent FROM textnodes WHERE id='.$container_id;
    if ($text_id != "") $sql .= ' AND text_id="'.$text_id.'"';
    if ($type != "") $sql .= ' AND type="'.$type.'"';
    if ($user != "") $sql .= ' AND user="'.$user.'"';
          
    $errcode = "50199";
    $done = $db->rdbms_query ($sql, $errcode, $mgmt_config['today']);

    if ($done)
    {
      while ($row = $db->rdbms_getresultrow ())
      {
        if (!empty ($row['text_id']))
        {
          $result[$row['text_id']] = $row['textcontent'];
        }
      }
    }

    // save log
    savelog ($db->rdbms_geterror ());    
    $db->rdbms_close();

    if (is_array ($result) && sizeof ($result) > 0) return $result;
    else return false;
  }
  else return false;
}

// ----------------------------------------------- get keywords ------------------------------------------------- 
// function: rdbms_getkeywords()
// input: publication names as string [string] or array [array] (optional)
// output: result array with keyword ID as key and keyword and count as value / false

// description:
// Selects all keywords in the database.

function rdbms_getkeywords ($sites="")
{
  global $mgmt_config;

  $result = array();

  $db = new hcms_db($mgmt_config['dbconnect'], $mgmt_config['dbhost'], $mgmt_config['dbuser'], $mgmt_config['dbpasswd'], $mgmt_config['dbname'], $mgmt_config['dbcharset']);

  $sql = 'SELECT keywords.keyword_id, keywords.keyword, COUNT(keywords_container.id) AS count FROM keywords INNER JOIN keywords_container ON keywords.keyword_id=keywords_container.keyword_id';

  if (is_array ($sites) && sizeof ($sites) > 0)
  {
    $i = 0;

    foreach ($sites as $site)
    {
      $site = $db->rdbms_escape_string ($site);

      if ($i < 1) $sql .= ' INNER JOIN object ON object.id=keywords_container.id WHERE (object.objectpath LIKE BINARY "*page*/'.$site.'/%" OR object.objectpath LIKE BINARY "*comp*/'.$site.'/%")';
      else $sql .= ' OR (object.objectpath LIKE BINARY "*page*/'.$site.'/%" OR object.objectpath LIKE BINARY "*comp*/'.$site.'/%")';

      $i++;
    }
  }
  else if ($sites != "" && $sites != "*Null*")
  {
    $site = $db->rdbms_escape_string ($sites);
    $sql .= ' INNER JOIN object ON object.id=keywords_container.id WHERE (object.objectpath LIKE BINARY "*page*/'.$site.'/%" OR object.objectpath LIKE BINARY "*comp*/'.$site.'/%")';
  }

  $sql .= ' GROUP BY keywords.keyword_id ORDER BY keywords.keyword';

  $errcode = "50541";
  $done = $db->rdbms_query($sql, $errcode, $mgmt_config['today']);

  if ($done)
  {
    while ($row = $db->rdbms_getresultrow ())
    {
      if (!empty ($row['keyword_id']) && is_keyword ($row['keyword']) && $row['count'] > 0)
      {
        $id = $row['keyword_id'];
        $count = $row['count'];

        $result[$id] = array();
        $result[$id][$count] = $row['keyword'];
      }
    }
  }

  // save log
  savelog ($db->rdbms_geterror ());    
  $db->rdbms_close();
 
  if (is_array ($result) && sizeof ($result) > 0) return $result;
  else return false;
}

// ----------------------------------------------- get empty keywords ------------------------------------------------- 
// function: rdbms_getemptykeywords()
// input: publication names as string [string] or array [array] (optional)
// output: number of objects without keywords / false

// description:
// Queries the number of objects without keywords.

function rdbms_getemptykeywords ($sites="")
{
  global $mgmt_config;

  $result = array();

  $db = new hcms_db($mgmt_config['dbconnect'], $mgmt_config['dbhost'], $mgmt_config['dbuser'], $mgmt_config['dbpasswd'], $mgmt_config['dbname'], $mgmt_config['dbcharset']);

  $sql = 'SELECT COUNT(object.id) AS count FROM object INNER JOIN textnodes ON textnodes.id=object.id WHERE';

  if (is_array ($sites))
  {
    $i = 0;
    $sql_objectpath = "";

    foreach ($sites as $site)
    {
      $site = $db->rdbms_escape_string ($site);
      
      if ($i < 1) $sql_objectpath .= ' (object.objectpath LIKE BINARY "*page*/'.$site.'/%" OR object.objectpath LIKE BINARY "*comp*/'.$site.'/%")';
      else $sql_objectpath .= ' OR (object.objectpath LIKE BINARY "*page*/'.$site.'/%" OR object.objectpath LIKE BINARY "*comp*/'.$site.'/%")';

      $i++;
    }

    $sql .= '('.$sql_objectpath.')';
  }
  else if ($sites != "" && $sites != "*Null*")
  {
    $site = $db->rdbms_escape_string ($sites);
    $sql .= ' (object.objectpath LIKE BINARY "*page*/'.$site.'/%" OR object.objectpath LIKE BINARY "*comp*/'.$site.'/%")';
  }

  $sql .= ' AND textnodes.type="textk" AND textnodes.textcontent=""';

  $errcode = "50542";
  $done = $db->rdbms_query($sql, $errcode, $mgmt_config['today']);

  if ($done)
  {
    if ($row = $db->rdbms_getresultrow ())
    {
      if ($row['count']) $result = $row['count'];
    }
  }

  // save log
  savelog ($db->rdbms_geterror ());    
  $db->rdbms_close();

  if (!empty ($result)) return $result;
  else return 0;
}

// ----------------------------------------------- get hierarchy sublevel ------------------------------------------------- 
// function: rdbms_gethierarchy_sublevel()
// input: publication name [string], text ID that holds the content [string], conditions array with text ID as key and content as value [array] (optional)
// output: array with hashcode as key and path as value / false

// description:
// Queries all values for a hierarachy level.

function rdbms_gethierarchy_sublevel ($site, $get_text_id, $text_id_array=array())
{
  global $mgmt_config;

  if ($site != "" && $get_text_id != "")
  {
    $result = array();
    $sql_textnodes = array();

    $db = new hcms_db($mgmt_config['dbconnect'], $mgmt_config['dbhost'], $mgmt_config['dbuser'], $mgmt_config['dbpasswd'], $mgmt_config['dbname'], $mgmt_config['dbcharset']);

    $site = $db->rdbms_escape_string ($site);
    $get_text_id = $db->rdbms_escape_string ($get_text_id);

    // extract type from text ID
    if (strpos ($get_text_id, ":") > 0)
    {
      list ($type, $get_text_id) = explode (":", $get_text_id);
    }

    // query database
    $sql = 'SELECT DISTINCT tn1.textcontent, tn1.type FROM textnodes AS tn1';

    if (is_array ($text_id_array) && sizeof ($text_id_array) > 0)
    {
      $i = 2;

      foreach ($text_id_array as $text_id => $value)
      {
        // extract type from text ID
        if (strpos ($text_id, ":") > 0)
        {
          list ($type, $text_id) = explode (":", $text_id);
        }
        else $type = "textu";

        $j = $i - 1;

        $sql .= ' INNER JOIN textnodes AS tn'.$i.' ON tn'.$j.'.id=tn'.$i.'.id';

        $value = html_decode ($value, convert_dbcharset ($mgmt_config['dbcharset']));
        $value_esc = html_encode ($value, convert_dbcharset ($mgmt_config['dbcharset']));

        $text_id = $db->rdbms_escape_string ($text_id);
        $value = $db->rdbms_escape_string ($value);

        // search for exact expression except for keyword
        if ($type != "textk")
        {
          if ($value !=  $value_esc) $sql_textnodes[] = 'tn'.$i.'.text_id="'.$text_id.'" AND (LOWER(tn'.$i.'.textcontent)=LOWER("'.$value.'") OR LOWER(tn'.$i.'.textcontent)=LOWER("'.$value_esc.'"))';
          else $sql_textnodes[] = 'tn'.$i.'.text_id="'.$text_id.'" AND LOWER(tn'.$i.'.textcontent)=LOWER("'.$value.'")';
        }
        else
        {
          if ($value !=  $value_esc) $sql_textnodes[] = 'tn'.$i.'.text_id="'.$text_id.'" AND (tn'.$i.'.textcontent LIKE "%'.$value.'%" OR tn'.$i.'.textcontent LIKE "%'.$value_esc.'%")';
          else $sql_textnodes[] = 'tn'.$i.'.text_id="'.$text_id.'" AND tn'.$i.'.textcontent LIKE "%'.$value.'%"';
        }
        
        $i++;
      }
    }

    $sql .= ' INNER JOIN object ON object.id=tn1.id';
    $sql .= ' WHERE (tn1.type="textu" OR tn1.type="textl" OR tn1.type="textc" OR tn1.type="textd" OR tn1.type="textk")';
    $sql .= ' AND (object.objectpath LIKE BINARY "*page*/'.$site.'/%" OR object.objectpath LIKE BINARY "*comp*/'.$site.'/%")';
    $sql .= ' AND tn1.text_id="'.$get_text_id.'"';
    if (is_array ($sql_textnodes) && sizeof ($sql_textnodes) > 0) $sql .= ' AND '.implode (" AND ", $sql_textnodes);

    $errcode = "50542";
    $done = $db->rdbms_query($sql, $errcode, $mgmt_config['today']);

    if ($done)
    {
      while ($row = $db->rdbms_getresultrow ())
      {
        // split keywords
        if ($row['type'] == "textk")
        {
          $result_add = splitkeywords ($row['textcontent']);

          if (is_array ($result_add)) $result = array_merge ($result, $result_add);
        }
        else $result[] = $row['textcontent'];
      }
    }

    // save log
    savelog ($db->rdbms_geterror ());    
    $db->rdbms_close();

    if (!empty ($result) && is_array ($result) && sizeof ($result) > 0)
    {
      $result = array_iunique ($result);
      return $result;
    }
    else return false;
  }
  else return false;
}

// ----------------------------------------------- get object_id ------------------------------------------------- 
// function: rdbms_getobject_id()
// input: location path or hash of an object [string]
// output: object ID / false

// description:
// Returns the ID of an object.

function rdbms_getobject_id ($object)
{
  global $mgmt_config;

  if ($object != "")
  {
    // correct object name
    // if unpublished object
    if (strtolower (strrchr ($object, ".")) == ".off")
    {
      $object = substr ($object, 0, -4);
    }
    // if object is a folder
    elseif (is_dir (deconvertpath ($object, "file")))
    {
      if (substr ($object, -1) != "/") $object = $object."/.folder";
      else $object = $object.".folder";
    }

    $db = new hcms_db($mgmt_config['dbconnect'], $mgmt_config['dbhost'], $mgmt_config['dbuser'], $mgmt_config['dbpasswd'], $mgmt_config['dbname'], $mgmt_config['dbcharset']);

    $object = $db->rdbms_escape_string ($object);

    // object path
    if (substr_count ($object, "%page%/") > 0 || substr_count ($object, "%comp%/") > 0 || substr_count ($object, "*page*/") > 0 || substr_count ($object, "*comp*/") > 0)
    { 
      $object = str_replace (array("%page%/", "%comp%/"), array("*page*/", "*comp*/"), $object);

      $sql = 'SELECT object_id, deleteuser FROM object WHERE md5_objectpath="'.md5 ($object).'"';
    }
    // object hash
    else
    {
      $sql = 'SELECT object_id FROM object WHERE hash= BINARY "'.$object.'"';
    }

    $errcode = "50027";
    $done = $db->rdbms_query ($sql, $errcode, $mgmt_config['today']);
    
    if ($done && $row = $db->rdbms_getresultrow ())
    {
      if ($row['deleteuser'] == "") $object_id = $row['object_id'];
      else $object_id = "hcms:deleted";
    }

    // save log
    savelog ($db->rdbms_geterror ());
    $db->rdbms_close();
      
    if (!empty ($object_id))
    {
      return $object_id;
    }
    else
    {
      // if object is a root folder (created since version 5.6.3)
      if (substr_count ($object, "/") == 2)
      {
        $object_esc = str_replace (array("*page*/", "*comp*/"), array("%page%/", "%comp%/"), $object);
        $createobject = createobject (getpublication ($object_esc), getlocation ($object_esc), ".folder", "default.meta.tpl", "sys");

        if (!empty ($createobject['result'])) return $object_id = rdbms_getobject_id ($object_esc);
        else return false;
      }
      else return false;
    }
  }
  else return false;
}

// ----------------------------------------------- get object_hash ------------------------------------------------- 
// function: object_hash()
// input: location path of an object [string] (optional) OR container ID of an object [integer] (optional)
// output: object hash / false

// description:
// Returns the hash of an object.

function rdbms_getobject_hash ($object="", $container_id="")
{
  global $mgmt_config;

  // object can be an object path or object ID, second input parameter can only be the container ID
  if ($object != "" || intval ($container_id) > 0)
  {
    $db = new hcms_db($mgmt_config['dbconnect'], $mgmt_config['dbhost'], $mgmt_config['dbuser'], $mgmt_config['dbpasswd'], $mgmt_config['dbname'], $mgmt_config['dbcharset']);

    // if object path
    if (substr_count ($object, "%page%/") > 0 || substr_count ($object, "%comp%/") > 0)
    {
      // correct object name
      // if unpublished object
      if (strtolower (strrchr ($object, ".")) == ".off")
      {
        $object = substr ($object, 0, -4);
      }
      // if object is a folder
      elseif (is_dir (deconvertpath ($object, "file")))
      {
        if (substr ($object, -1) != "/") $object = $object."/.folder";
        else $object = $object.".folder";
      }

      $object = str_replace (array("%page%/", "%comp%/"), array("*page*/", "*comp*/"), $object);
      $object = $db->rdbms_escape_string ($object);          

      $sql = 'SELECT hash, deleteuser FROM object WHERE md5_objectpath="'.md5 ($object).'" LIMIT 1';
    }
    // if object id
    elseif (intval ($object) > 0)
    {
      $sql = 'SELECT hash, deleteuser FROM object WHERE object_id="'.intval($object).'" LIMIT 1';
    }
    // if container id
    elseif (intval ($container_id) > 0)
    {
      $sql = 'SELECT hash, deleteuser FROM object WHERE id="'.intval($container_id).'" LIMIT 1';
    }

    if (!empty ($sql))
    {
      $errcode = "50029";
      $done = $db->rdbms_query ($sql, $errcode, $mgmt_config['today']);

      if ($done && $row = $db->rdbms_getresultrow ())
      {
        if ($row['deleteuser'] == "") $hash = $row['hash'];
        else $hash = "hcms:deleted";
      }

      // save log
      savelog ($db->rdbms_geterror ());    
      $db->rdbms_close();

      if (!empty ($hash))
      {
        return $hash;
      }
      else
      {
        // if object is a root folder (created since version 5.6.3)
        if (substr_count ($object, "/") == 2)
        {
          $object_esc = str_replace (array("*page*/", "*comp*/"), array("%page%/", "%comp%/"), $object);
          $createobject = createobject (getpublication ($object_esc), getlocation ($object_esc), ".folder", "default.meta.tpl", "sys");

          if (!empty ($createobject['result'])) return $hash = rdbms_getobject_hash ($object_esc);
          else return false;
        }
        else return false;
      }
    }
    else return false;
  }
  else return false;
} 

// -------------------------------------------- get object by unique id or hash ----------------------------------------------- 
// function: rdbms_getobject()
// input: object identifier (object hash OR object ID OR access hash) [string]
// output: object path / false

// description:
// Returns the location path of an object as string.

function rdbms_getobject ($object_identifier)
{
  global $mgmt_config;

  if ($object_identifier != "")
  {
    $objectpath = "";

    // if object identifier is already a location
    if (strpos ("_".$object_identifier, "%page%") > 0 || strpos ("_".$object_identifier, "%comp%") > 0) return $object_identifier;

    $db = new hcms_db ($mgmt_config['dbconnect'], $mgmt_config['dbhost'], $mgmt_config['dbuser'], $mgmt_config['dbpasswd'], $mgmt_config['dbname'], $mgmt_config['dbcharset']);

    // clean input
    $object_identifier = $db->rdbms_escape_string ($object_identifier);

    // try table object if public download is enabled
    if (!empty ($mgmt_config['publicdownload']))
    {
      if (is_numeric ($object_identifier)) $sql = 'SELECT objectpath FROM object WHERE deleteuser="" AND object_id='.intval($object_identifier).' LIMIT 1';
      else $sql = 'SELECT objectpath FROM object WHERE deleteuser="" AND hash="'.$object_identifier.'" LIMIT 1';

      $errcode = "50030";
      $done = $db->rdbms_query ($sql, $errcode, $mgmt_config['today']);

      if ($done && $row = $db->rdbms_getresultrow ())
      {
        if ($row['objectpath'] != "") $objectpath = str_replace (array("*page*/", "*comp*/"), array("%page%/", "%comp%/"), $row['objectpath']);  
      }
    }

    // try table accesslink
    if (empty ($objectpath) && !is_numeric ($object_identifier))
    {
      $sql = 'SELECT obj.objectpath, al.deathtime, al.formats FROM accesslink AS al, object AS obj WHERE obj.deleteuser="" AND al.hash="'.$object_identifier.'" AND al.object_id=obj.object_id LIMIT 1';

      $errcode = "50031";
      $done = $db->rdbms_query ($sql, $errcode, $mgmt_config['today'], "select2");

      if ($done)
      {
        $row = $db->rdbms_getresultrow ("select2");

        // if time of death for link is set
        if ($row['deathtime'] > 0)
        {
          // if deathtime was passed
          if ($row['deathtime'] < time())
          {
            $sql = 'DELETE FROM accesslink WHERE hash="'.$object_identifier.'"';
 
            $errcode = "50739";
            $db->rdbms_query ($sql, $errcode, $mgmt_config['today'], "delete");
          }
          elseif ($row['objectpath'] != "") $objectpath = str_replace (array("*page*/", "*comp*/"), array("%page%/", "%comp%/"), $row['objectpath']);
        }
        elseif ($row['objectpath'] != "") $objectpath = str_replace (array("*page*/", "*comp*/"), array("%page%/", "%comp%/"), $row['objectpath']);  
      }
    }

    // save log
    savelog ($db->rdbms_geterror ());    
    $db->rdbms_close();     
      
    if ($objectpath != "") return $objectpath;
    else return false;
  }
  else return false;
}

// -------------------------------------------- get object info by unique id or hash ----------------------------------------------- 
// function: rdbms_getobject_info()
// input: object identifier (object path Or object hash OR object ID OR access hash) [string], text IDs to be returned [array] (optional)
// output: array with object info / false

// description:
// Returns the location path, hash, container ID, template, and media information of an object as array.

function rdbms_getobject_info ($object_identifier, $return_text_id=array())
{
  global $mgmt_config;

  if ($object_identifier != "")
  {
    $objectpath = array();

    // if object identifier is already a location and no object info besides the object path has been rerquested
    if ((strpos ("_".$object_identifier, "%page%/") > 0 || strpos ("_".$object_identifier, "%comp%/") > 0) && sizeof ($return_text_id) < 1) return $object_identifier;

    $db = new hcms_db ($mgmt_config['dbconnect'], $mgmt_config['dbhost'], $mgmt_config['dbuser'], $mgmt_config['dbpasswd'], $mgmt_config['dbname'], $mgmt_config['dbcharset']);

    // clean input
    $object_identifier = $db->rdbms_escape_string ($object_identifier);

    $sql_add_attr = "";
    $sql_attr = array();
    $sql_table = array();

    if (is_array ($return_text_id) && sizeof ($return_text_id) > 0)
    {
      // add object information to the result array
      if (in_array ("date", $return_text_id) || in_array ("modifieddate", $return_text_id))
      {
        $sql_attr[] = "obj.date";
      }

      if (in_array ("createdate", $return_text_id))
      {
        $sql_attr[] = "obj.createdate";
      }

      if (in_array ("publishdate", $return_text_id))
      {
        $sql_attr[] = "obj.publishdate";
      }

      if (in_array ("user", $return_text_id) || in_array ("owner", $return_text_id))
      {
        $sql_attr[] = "obj.user";
      }

      if (in_array ("filesize", $return_text_id) || in_array ("width", $return_text_id) || in_array ("height", $return_text_id))
      {
        $sql_attr[] = "obj.filesize";
        $sql_attr[] = "obj.width";
        $sql_attr[] = "obj.height";
      }

      // add text IDs and content to the result array
      foreach ($return_text_id as $text_id)
      {
        if (substr ($text_id, 0, 5) == "text:")
        {
          $sql_add_text_id = true;
          break;
        }
      }

      // add join for textnodes table
      if (!empty ($sql_add_text_id))
      {
        $sql_attr[] = "tng.text_id";
        $sql_attr[] = "tng.textcontent";
        if (empty ($sql_table['textnodes'])) $sql_table['textnodes'] = "";
        if (strpos ($sql_table['textnodes'], " ON obj.id=tng.id ") < 1) $sql_table['textnodes']  .= ' LEFT JOIN textnodes AS tng ON obj.id=tng.id';
      }

      // add attributes to search query
      if (sizeof ($sql_attr) > 0) $sql_add_attr = ", ".implode (", ", $sql_attr);
    }

    // try table object if public download is allowed
    if (!empty ($mgmt_config['publicdownload']))
    {
      if (is_numeric ($object_identifier))
      {
        $sql = 'SELECT obj.objectpath, obj.hash, obj.id, obj.template, obj.media'.$sql_add_attr.' FROM object AS obj ';
        if (isset ($sql_table) && is_array ($sql_table) && sizeof ($sql_table) > 0) $sql .= implode (' ', $sql_table).' ';
        $sql .= 'WHERE obj.deleteuser="" AND obj.object_id='.intval($object_identifier).' LIMIT 1';
      }
      elseif (strpos ("_".$object_identifier, "%comp%/") > 0 || strpos ("_".$object_identifier, "%page%/") > 0)
      {
        $sql = 'SELECT obj.objectpath, obj.hash, obj.id, obj.template, obj.media'.$sql_add_attr.' FROM object AS obj ';
        if (isset ($sql_table) && is_array ($sql_table) && sizeof ($sql_table) > 0) $sql .= implode (' ', $sql_table).' ';
        $sql .= 'WHERE obj.deleteuser="" AND obj.md5_objectpath="'.md5 (str_replace (array("%page%/", "%comp%/"), array("*page*/", "*comp*/"), $object_identifier)).'" LIMIT 1';
      }
      else
      {
        $sql = 'SELECT obj.objectpath, obj.hash, obj.id, obj.template, obj.media'.$sql_add_attr.' FROM object AS obj ';
        if (isset ($sql_table) && is_array ($sql_table) && sizeof ($sql_table) > 0) $sql .= implode (' ', $sql_table).' ';
        $sql .= 'WHERE obj.deleteuser="" AND obj.hash="'.$object_identifier.'" LIMIT 1';
      }

      $errcode = "50030";
      $done = $db->rdbms_query ($sql, $errcode, $mgmt_config['today']);

      if ($done && $row = $db->rdbms_getresultrow ())
      {
        if (!empty ($row['objectpath']))
        {
          $objectpath['objectpath'] = str_replace (array("*page*/", "*comp*/"), array("%page%/", "%comp%/"), $row['objectpath']);
          $objectpath['container_id'] = sprintf ("%07d", $row['id']);
          $objectpath['template'] = $row['template'];
          $objectpath['hash'] = $row['hash'];
          $objectpath['media'] = $row['media'];
          if (!empty ($row['date'])) $objectpath['date'] = $row['date'];
          if (!empty ($row['createdate'])) $objectpath['createdate'] = $row['createdate'];
          if (!empty ($row['publishdate'])) $objectpath['publishdate'] = $row['publishdate'];
          if (!empty ($row['user'])) $objectpath['user'] = $row['user'];
          if (!empty ($row['filesize'])) $objectpath['filesize'] = $row['filesize'];
          if (!empty ($row['width'])) $objectpath['width'] = $row['width'];
          if (!empty ($row['height'])) $objectpath['height'] = $row['height'];
          if (!empty ($row['text_id'])) $objectpath['text:'.$row['text_id']] = $row['textcontent'];
        }
      }
    }

    // try table accesslink
    if ((!is_array ($objectpath) || sizeof ($objectpath) < 1) && !is_numeric ($object_identifier))
    {
      $sql = 'SELECT obj.objectpath, obj.hash, obj.id, obj.template, obj.media, al.deathtime, al.formats'.$sql_add_attr.' FROM accesslink AS al INNER JOIN object AS obj ON al.object_id=obj.object_id ';
      if (isset ($sql_table) && is_array ($sql_table) && sizeof ($sql_table) > 0) $sql .= implode (' ', $sql_table).' ';
      $sql .= 'WHERE obj.deleteuser="" AND al.hash="'.$object_identifier.'" LIMIT 1';

      $errcode = "50031";
      $done = $db->rdbms_query ($sql, $errcode, $mgmt_config['today'], "select2");

      if ($done)
      {
        $row = $db->rdbms_getresultrow ("select2");

        // if time of death for link is set
        if (!empty ($row['deathtime']) && $row['deathtime'] > 0)
        {
          // if deathtime was passed
          if ($row['deathtime'] < time())
          {
            $sql = 'DELETE FROM accesslink WHERE hash="'.$object_identifier.'"';

            $errcode = "50749";
            $db->rdbms_query ($sql, $errcode, $mgmt_config['today'], "delete");
          }
          elseif (!empty ($row['objectpath'])) 
          {
            $objectpath['objectpath'] = str_replace (array("*page*/", "*comp*/"), array("%page%/", "%comp%/"), $row['objectpath']);
            $objectpath['container_id'] = sprintf ("%07d", $row['id']);
            $objectpath['template'] = $row['template'];
            $objectpath['hash'] = $row['hash'];
            $objectpath['media'] = $row['media'];
            if (!empty ($row['date'])) $objectpath['date'] = $row['date'];
            if (!empty ($row['createdate'])) $objectpath['createdate'] = $row['createdate'];
            if (!empty ($row['publishdate'])) $objectpath['publishdate'] = $row['publishdate'];
            if (!empty ($row['user'])) $objectpath['user'] = $row['user'];
            if (!empty ($row['filesize'])) $objectpath['filesize'] = $row['filesize'];
            if (!empty ($row['width'])) $objectpath['width'] = $row['width'];
            if (!empty ($row['height'])) $objectpath['height'] = $row['height'];
            if (!empty ($row['text_id'])) $objectpath['text:'.$row['text_id']] = $row['textcontent'];
          }
        }
        elseif (!empty ($row['objectpath']))
        {
          $objectpath['objectpath'] = str_replace (array("*page*/", "*comp*/"), array("%page%/", "%comp%/"), $row['objectpath']);
          $objectpath['container_id'] = sprintf ("%07d", $row['id']);
          $objectpath['template'] = $row['template'];
          $objectpath['hash'] = $row['hash'];
          $objectpath['media'] = $row['media'];
          if (!empty ($row['date'])) $objectpath['date'] = $row['date'];
          if (!empty ($row['createdate'])) $objectpath['createdate'] = $row['createdate'];
          if (!empty ($row['publishdate'])) $objectpath['publishdate'] = $row['publishdate'];
          if (!empty ($row['user'])) $objectpath['user'] = $row['user'];
          if (!empty ($row['filesize'])) $objectpath['filesize'] = $row['filesize'];
          if (!empty ($row['width'])) $objectpath['width'] = $row['width'];
          if (!empty ($row['height'])) $objectpath['height'] = $row['height'];
          if (!empty ($row['text_id'])) $objectpath['text:'.$row['text_id']] = $row['textcontent'];
        }  
      }
    }

    // save log
    savelog ($db->rdbms_geterror ());    
    $db->rdbms_close();     

    if ($objectpath != "") return $objectpath;
    else return false;
  }
  else return false;
} 

// ------------------------------------------ get objects by container_id or temlpate name -------------------------------------------- 
// function: rdbms_getobjects()
// input: container ID supports multiple values if | is used as separator [string,integer] (optional), template name [string] (optional), text IDs to be returned [array] (optional)
// output: 2 dimensional object path array / false

// description:
// Returns all queried objects as array with the object hash as array key.

function rdbms_getobjects ($container_id="", $template="", $return_text_id=array())
{
  global $mgmt_config;

  if ($container_id != "" || $template != "")
  {
    $objectpath = array();

    $db = new hcms_db($mgmt_config['dbconnect'], $mgmt_config['dbhost'], $mgmt_config['dbuser'], $mgmt_config['dbpasswd'], $mgmt_config['dbname'], $mgmt_config['dbcharset']);

    // split
    if ($container_id != "") $container_id_array = link_db_getobject ($container_id);
    else $container_id_array = array(0 => "");

    foreach ($container_id_array as $container_id)
    {
      // clean input
      if (intval ($container_id) > 0) $container_id = intval ($container_id);
      if ($template != "") $template = $db->rdbms_escape_string ($template);

      $sql_add_attr = "";
      $sql_attr = array();
      $sql_table = array();

      if (is_array ($return_text_id) && sizeof ($return_text_id) > 0)
      {
        // add object information to the result array
        if (in_array ("date", $return_text_id) || in_array ("modifieddate", $return_text_id))
        {
          $sql_attr[] = "obj.date";
        }

        if (in_array ("createdate", $return_text_id))
        {
          $sql_attr[] = "obj.createdate";
        }

        if (in_array ("publishdate", $return_text_id))
        {
          $sql_attr[] = "obj.publishdate";
        }

        if (in_array ("user", $return_text_id) || in_array ("owner", $return_text_id))
        {
          $sql_attr[] = "obj.user";
        }

        if (in_array ("filesize", $return_text_id))
        {
          $sql_attr[] = "obj.filesize";
        }

        // add text IDs and content to the result array
        foreach ($return_text_id as $text_id)
        {
          if (substr ($text_id, 0, 5) == "text:")
          {
            $sql_add_text_id = true;
            break;
          }
        }

        // add join for textnodes table
        if (!empty ($sql_add_text_id))
        {
          $sql_attr[] = "tng.text_id";
          $sql_attr[] = "tng.textcontent";
          if (empty ($sql_table['textnodes'])) $sql_table['textnodes'] = "";
          if (strpos ($sql_table['textnodes'], " ON obj.id=tng.id ") < 1) $sql_table['textnodes']  .= ' LEFT JOIN textnodes AS tng ON obj.id=tng.id';
        }

        // add attributes to search query
        if (sizeof ($sql_attr) > 0) $sql_add_attr = ", ".implode (", ", $sql_attr);
      }

      $sql = 'SELECT obj.objectpath, obj.hash, obj.id, obj.media'.$sql_add_attr.' FROM object AS obj ';
      if (isset ($sql_table) && is_array ($sql_table) && sizeof ($sql_table) > 0) $sql .= implode (' ', $sql_table).' ';
      $sql .= 'WHERE obj.deleteuser="" ';
      if (intval ($container_id) > 0) $sql .= 'AND obj.id='.$container_id.' ';
      if ($template != "") $sql .= 'AND obj.template="'.$template.'" ';
      $sql .= 'ORDER BY obj.objectpath';

      $errcode = "50040";
      $done = $db->rdbms_query ($sql, $errcode, $mgmt_config['today']);

      if ($done)  
      {
        while ($row = $db->rdbms_getresultrow ())
        {
          if (!empty ($row['hash']) && !empty ($row['objectpath']))
          {
            $hash = $row['hash'];

            $objectpath[$hash]['objectpath'] = str_replace (array("*page*/", "*comp*/"), array("%page%/", "%comp%/"), $row['objectpath']);
            $objectpath[$hash]['container_id'] = sprintf ("%07d", $row['id']);
            $objectpath[$hash]['media'] = $row['media'];
            if (!empty ($row['date'])) $objectpath[$hash]['date'] = $row['date'];
            if (!empty ($row['createdate'])) $objectpath[$hash]['createdate'] = $row['createdate'];
            if (!empty ($row['publishdate'])) $objectpath[$hash]['publishdate'] = $row['publishdate'];
            if (!empty ($row['user'])) $objectpath[$hash]['user'] = $row['user'];
            if (!empty ($row['filesize'])) $objectpath[$hash]['filesize'] = $row['filesize'];
            if (!empty ($row['text_id'])) $objectpath[$hash]['text:'.$row['text_id']] = $row['textcontent'];
          }
        }
      }
    }

    // save log
    savelog ($db->rdbms_geterror ());    
    $db->rdbms_close();    

    if (!empty ($objectpath) && is_array ($objectpath) && sizeof ($objectpath) > 0) return $objectpath;
    else return false;
  }
  else return false;
}

// ----------------------------------------------- get deleted objects ------------------------------------------------- 
// function: rdbms_getdeletedobjects()
// input: user name [string] (optional), older than date [date] (optional), max. hits [integer] (optional), text IDs to be returned [array] (optional), count search result entries [boolean] (optional), return sub items [boolean] (optional)
// output: objectpath array with hashcode as key and path as value / false

// description:
// Queries all marked as deleted objects of a user. Subitems are marked displaying the user name in brackets [username].

function rdbms_getdeletedobjects ($user="", $date="", $maxhits=500, $return_text_id=array(), $count=false, $subitems=false)
{
  global $mgmt_config;

  $db = new hcms_db($mgmt_config['dbconnect'], $mgmt_config['dbhost'], $mgmt_config['dbuser'], $mgmt_config['dbpasswd'], $mgmt_config['dbname'], $mgmt_config['dbcharset']);

  if ($user != "") $user = $db->rdbms_escape_string ($user);
  if ($date != "") $date = date ("Y-m-d", strtotime ($date));

  $sql_add_attr = "";
  $sql_attr = array();
  $sql_table = array();

  if (is_array ($return_text_id) && sizeof ($return_text_id) > 0)
  {
    // add object information to the result array
    if (in_array ("date", $return_text_id) || in_array ("modifieddate", $return_text_id))
    {
      $sql_attr[] = "obj.date";
    }

    if (in_array ("createdate", $return_text_id))
    {
      $sql_attr[] = "obj.createdate";
    }

    if (in_array ("publishdate", $return_text_id))
    {
      $sql_attr[] = "obj.publishdate";
    }

    if (in_array ("user", $return_text_id) || in_array ("owner", $return_text_id))
    {
      $sql_attr[] = "obj.user";
    }

    if (in_array ("filesize", $return_text_id))
    {
      $sql_attr[] = "obj.filesize";
    }

    // add text IDs and content to the result array
    foreach ($return_text_id as $text_id)
    {
      if (substr ($text_id, 0, 5) == "text:")
      {
        $sql_add_text_id = true;
        break;
      }
    }

    // add join for textnodes table
    if (!empty ($sql_add_text_id))
    {
      $sql_attr[] = "tng.text_id";
      $sql_attr[] = "tng.textcontent";
      if (empty ($sql_table['textnodes'])) $sql_table['textnodes'] = "";
      if (strpos ($sql_table['textnodes'], " ON obj.id=tng.id ") < 1) $sql_table['textnodes']  .= ' LEFT JOIN textnodes AS tng ON obj.id=tng.id';
    }

    // add attributes to search query
    if (sizeof ($sql_attr) > 0) $sql_add_attr = ", ".implode (", ", $sql_attr);
  } 

  $sql = 'SELECT obj.objectpath, obj.hash, obj.id, obj.media'.$sql_add_attr.' FROM object AS obj ';

  if (isset ($sql_table) && is_array ($sql_table) && sizeof ($sql_table) > 0) $sql .= implode (' ', $sql_table).' ';

  // users objects in the recycle bin without subitems
  if ($user != "" && $subitems == false) $sql .= 'WHERE obj.deleteuser="'.$user.'" ';
  // users objects including subitems in the recycle bin
  elseif ($user != "" && $subitems == true) $sql .= 'WHERE (obj.deleteuser="'.$user.'" OR obj.deleteuser="['.$user.']") ';
  // all objects in the recycle bin without subitems
  elseif ($subitems == false) $sql .= 'WHERE obj.deleteuser!="" AND obj.deleteuser NOT LIKE "[%]" ';
  // all objects including subitems in the recycle bin
  else $sql .= 'WHERE obj.deleteuser!="" ';

  if ($date != "") $sql .= 'AND obj.deletedate<"'.$date.'" ';  
  if ($maxhits > 0) $sql .= 'GROUP BY obj.hash LIMIT 0,'.intval($maxhits);

  $errcode = "50325";
  $done = $db->rdbms_query($sql, $errcode, $mgmt_config['today']);

  $objectpath = array();

  if ($done)
  {
    while ($row = $db->rdbms_getresultrow ())
    {
      if (!empty ($row['hash']) && !empty ($row['objectpath']))
      {
        $hash = $row['hash'];

        $objectpath[$hash]['objectpath'] = str_replace (array("*page*/", "*comp*/"), array("%page%/", "%comp%/"), $row['objectpath']);
        $objectpath[$hash]['container_id'] =  sprintf ("%07d", $row['id']);
        $objectpath[$hash]['media'] =  $row['media'];
        if (!empty ($row['date'])) $objectpath[$hash]['date'] = $row['date'];
        if (!empty ($row['createdate'])) $objectpath[$hash]['createdate'] = $row['createdate'];
        if (!empty ($row['publishdate'])) $objectpath[$hash]['publishdate'] = $row['publishdate'];
        if (!empty ($row['user'])) $objectpath[$hash]['user'] = $row['user'];
        if (!empty ($row['filesize'])) $objectpath[$hash]['filesize'] = $row['filesize'];
        if (!empty ($row['text_id'])) $objectpath[$hash]['text:'.$row['text_id']] = $row['textcontent'];
      } 
    }
  }

  // count searchresults
  if (!empty ($count))
  {
    $sql = 'SELECT COUNT(obj.hash) as rowcount FROM object AS obj ';

    if (isset ($sql_table) && is_array ($sql_table) && sizeof ($sql_table) > 0) $sql .= implode (' ', $sql_table).' ';

    if ($user != "") $sql .= 'WHERE obj.deleteuser="'.$user.'" ';
    elseif ($subitems == true) $sql .= 'WHERE obj.deleteuser!="" ';
    else $sql .= 'WHERE obj.deleteuser!="" AND obj.deleteuser NOT LIKE "[%]" ';

    if ($date != "") $sql .= 'AND deletedate<"'.$date.'" ';

    $sql .= 'GROUP BY obj.hash';

    $errcode = "50327";
    $done = $db->rdbms_query ($sql, $errcode, $mgmt_config['today']);

    if ($done && ($row = $db->rdbms_getresultrow ()))
    {         
      if ($row['cnt'] != "") $objectpath['count'] = $row['rowcount']; 
    }
  }

  // save log
  savelog ($db->rdbms_geterror ());    
  $db->rdbms_close();

  if (!empty ($objectpath) && is_array ($objectpath) && sizeof ($objectpath) > 0) return $objectpath;
  else return false;
}

// ----------------------------------------------- set deleted objects ------------------------------------------------- 
// function: rdbms_setdeletedobjects()
// input: 1 or 2 dimensional objects array [array], user name [string], mark or unmark as deleted [set,unset] (optional)
// output: true / false

// description:
// Marks objects as deleted for a specific user. Subitems will be marked as well and the user name is set in brackets [username]. 

function rdbms_setdeletedobjects ($objects, $user, $mark="set")
{
  global $mgmt_config;

  $error = array();

  if (is_array ($objects) && sizeof ($objects) > 0 && $user != "" && (strtolower ($mark) == "set" || strtolower ($mark) == "unset"))
  {
    $result = true;
    $session_id = "";

    $db = new hcms_db($mgmt_config['dbconnect'], $mgmt_config['dbhost'], $mgmt_config['dbuser'], $mgmt_config['dbpasswd'], $mgmt_config['dbname'], $mgmt_config['dbcharset']);

    $user = $db->rdbms_escape_string ($user);

    // get current date
    $date = date ("Y-m-d", time());

    $mark = strtolower ($mark);

    foreach ($objects as $object_info)
    {
      if (!empty ($object_info['objectpath'])) $object = $object_info['objectpath'];
      else $object = $object_info;

      if ($object != "" && valid_locationname (getlocation ($object)) && valid_objectname (getobject ($object)))
      {
        // correct object name 
        if (strtolower (strrchr ($object, ".")) == ".off") $object = substr ($object, 0, -4);

        // get publication
        $site = getpublication ($object);

        // get absolute path in file system
        $object_abs = deconvertpath ($object, "file");

        // ------------------------- for folders -----------------------
        if (getobject ($object) == ".folder" || is_dir ($object_abs))
        {
          // remove .folder file
          if (getobject ($object) == ".folder") $object = getlocation ($object);

          // remove tailing slash
          if (substr ($object, -1) == "/") $object = substr ($object, 0, -1);

          // get absolute path in file system without the .folder file
          $object_abs = deconvertpath ($object, "file");

          // clean input
          $object_folder = str_replace (array("%page%/", "%comp%/"), array("*page*/", "*comp*/"), $object);
          $object_folder = $db->rdbms_escape_string ($object_folder);

          // mark as deleted
          if ($mark == "set" && substr ($object_abs, -8) != ".recycle")
          {
            // new name
            $new_abs = $object_abs.".recycle";

            // remove previously deleted objects
            if (is_dir ($new_abs)) $deletebin = processobjects ("delete", $site, getlocation ($new_abs), getobject ($new_abs), false, $user);

            // if the same folder does not exist
            if (is_dir ($object_abs) && !is_dir ($new_abs))
            {
              // rename folder
              $rename = rename ($object_abs, $new_abs);

              if (empty ($rename))
              {
                $errcode = "10901";
                $error[] = $mgmt_config['today']."|db_connect_rdbms.inc.php|error|".$errcode."|Delete mark (rename) failed for folder '".$object."'";
                $result = false;
              }
              // update query
              else $sql = 'UPDATE object SET deleteuser="'.$user.'", deletedate="'.$date.'", objectpath="'.$object_folder.'.recycle/.folder", md5_objectpath="'.md5 ($object_folder.'.recycle/.folder').'" WHERE md5_objectpath="'.md5 ($object_folder.'/.folder').'"';
            }
            else
            {
              $errcode = "10902";
              $error[] = $mgmt_config['today']."|db_connect_rdbms.inc.php|error|".$errcode."|Delete mark failed for folder '".$object."' since a folder with the same name exists already";
              $result = false;
            }
          }
          // restore
          elseif ($mark == "unset" && substr ($object_abs, -8) == ".recycle")
          {
            // new name
            $new_abs = substr ($object_abs, 0, -8);

            // if folder can be restored
            if (is_dir ($object_abs) && !is_dir ($new_abs))
            {
              // rename folder
              $rename = rename ($object_abs, $new_abs);

              if (empty ($rename))
              {
                $errcode = "10903";
                $error[] = $mgmt_config['today']."|db_connect_rdbms.inc.php|error|".$errcode."|Restore failed (rename) for folder '".$object."' in recycle bin";
              }
              // update query
              else $sql = 'UPDATE object SET deleteuser="", deletedate="", objectpath="'.substr ($object_folder, 0, -8).'/.folder", md5_objectpath="'.md5 (substr ($object_folder, 0, -8)).'" WHERE md5_objectpath="'.md5 ($object_folder.'/.folder').'"';
            }
            else
            {
              $errcode = "10904";
              $error[] = $mgmt_config['today']."|db_connect_rdbms.inc.php|error|".$errcode."|Restore failed for folder '".$object."' since a folder with the same name exists already";
              $result = false;
            }
          }

          if (!empty ($sql))
          {
            // write and close session (important for non-blocking: any page that needs to access a session now has to wait for the long running script to finish execution before it can begin)
            if (session_id() != "")
            {
              $session_id = session_id();
              session_write_close();
            }

            $errcode = "50071";
            $done = $db->rdbms_query($sql, $errcode, $mgmt_config['today']);

            // for all subitems of the selected folder
            if ($mark == "set" && substr ($object_abs, -8) != ".recycle")
            {
              $sql = 'SELECT object_id, objectpath FROM object WHERE objectpath!= BINARY "'.$object_folder.'/" AND objectpath LIKE BINARY "'.$object_folder.'/%"';

              $done = $db->rdbms_query ($sql, $errcode, $mgmt_config['today'], 'select');

              if ($done)
              {
                while ($row = $db->rdbms_getresultrow ('select'))
                {
                  if (!empty ($row['object_id']))
                  {
                    $temp_objectpath = str_replace ($object_folder."/", $object_folder.".recycle/", $row['objectpath']);
                    $sql = 'UPDATE object SET deleteuser="['.$user.']", deletedate="'.$date.'", objectpath="'.$temp_objectpath.'", md5_objectpath="'.md5 ($temp_objectpath).'" WHERE object_id='.intval($row['object_id']);

                    $errcode = "50072";
                    $update = $db->rdbms_query($sql, $errcode, $mgmt_config['today'], 'update');
                  }
                }
              }
            }
            elseif ($mark == "unset" && substr ($object_abs, -8) == ".recycle")
            {
              $sql = 'SELECT object_id, objectpath FROM object WHERE objectpath LIKE BINARY "'.$object_folder.'/%"';

              $done = $db->rdbms_query ($sql, $errcode, $mgmt_config['today'], 'select');

              if ($done)
              {
                while ($row = $db->rdbms_getresultrow ('select'))
                {
                  if (!empty ($row['object_id']))
                  {
                    $temp_objectpath = str_replace ($object_folder."/", substr ($object_folder, 0, -8)."/", $row['objectpath']);
                    $sql = 'UPDATE object SET deleteuser="", deletedate="", objectpath="'.$temp_objectpath.'", md5_objectpath="'.md5 ($temp_objectpath).'" WHERE object_id='.intval($row['object_id']);

                    $errcode = "50073";
                    $update = $db->rdbms_query($sql, $errcode, $mgmt_config['today'], 'update');
                  }
                }
              }
            }

            // restart session (that has been previously closed for non-blocking procedure)
            if (empty (session_id()) && $session_id != "") createsession();
          }
        }
        // -------------------- for objects ---------------------
        else
        {
          // clean input
          $object_file = str_replace (array("%page%/", "%comp%/"), array("*page*/", "*comp*/"), $object);
          $object_file = $db->rdbms_escape_string ($object_file);

          // correct file name
          $object_corr = getlocation ($object_abs).correctfile (getlocation ($object_abs), getobject ($object_abs), $user);

          if ($mark == "set" && substr ($object_abs, -8) != ".recycle")
          {
            // new file name
            $new_abs = $object_abs.".recycle";
            
            // remove previously deleted object
            if (is_file ($new_abs)) deleteobject ($site, getlocation ($new_abs), getobject ($new_abs), $user);

            if (is_file ($object_corr) && !is_file ($new_abs))
            {
              // rename folder file
              $rename = rename ($object_corr, $new_abs);
            
              if (empty ($rename))
              {
                $errcode = "10905";
                $error[] = $mgmt_config['today']."|db_connect_rdbms.inc.php|error|".$errcode."|Delete mark (rename) failed for object '".$object."'";
                $result = false;
              }
              // update query
              else $sql = 'UPDATE object SET deleteuser="'.$user.'", deletedate="'.$date.'", objectpath="'.$object_file.'.recycle", md5_objectpath="'.md5 ($object_file.'.recycle').'" WHERE md5_objectpath="'.md5 ($object_file).'"';
            }
          }
          elseif ($mark == "unset" && substr ($object_abs, -8) == ".recycle")
          {
            // new file name
            $new_abs = substr ($object_abs, 0, -8);

            // if the same file name does not exist
            if (is_file ($object_corr) && !is_file ($new_abs))
            {
              // rename file
              $rename = rename ($object_corr, $new_abs);

              if (empty ($rename))
              {
                $errcode = "10906";
                $error[] = $mgmt_config['today']."|db_connect_rdbms.inc.php|error|".$errcode."|Restore failed (rename) for object '".$object."' in recycle bin";
                $result = false;
              }
              // update query
              else $sql = 'UPDATE object SET deleteuser="", deletedate="", objectpath="'.substr ($object_file, 0, -8).'", md5_objectpath="'.md5 (substr ($object_file, 0, -8)).'" WHERE md5_objectpath="'.md5 ($object_file).'"';
            }
            else
            {
              $errcode = "10907";
              $error[] = $mgmt_config['today']."|db_connect_rdbms.inc.php|error|".$errcode."|Restore failed for object '".$object."' in recycle bin since an object with the same name exists already";
              $result = false;
            }
          }

          if (!empty ($sql))
          {
            $errcode = "50073";
            $done = $db->rdbms_query($sql, $errcode, $mgmt_config['today']);
          }
        }
      }
      else
      {
        $errcode = "30901";
        $error[] = $mgmt_config['today']."|db_connect_rdbms.inc.php|error|".$errcode."|Reference to object $object is not valid";
        $result = false;
      }  
    }

    // save log
    savelog ($db->rdbms_geterror ());
    savelog (@$error);
    $db->rdbms_close();

    return $result;
  }
  else return false;
}

// ----------------------------------------------- create accesslink -------------------------------------------------
// function: rdbms_createaccesslink()
// input: object hash [string], object-ID [string], link type [al,dl] (optional), user login name [string] (optional), token lifetime in seconds [integer] (optional), formats [string] (optional)
// output: true / false on error

// description:
// Creates a new access link in the database.

function rdbms_createaccesslink ($hash, $object_id, $type="al", $user="", $lifetime=0, $formats="")
{
  global $mgmt_config;

  if ($hash != "" && (is_array ($object_id) || $object_id != "") && (($type == "al" && valid_objectname ($user)) || $type == "dl"))
  { 
    $db = new hcms_db($mgmt_config['dbconnect'], $mgmt_config['dbhost'], $mgmt_config['dbuser'], $mgmt_config['dbpasswd'], $mgmt_config['dbname'], $mgmt_config['dbcharset']);
    
    $hash = $db->rdbms_escape_string ($hash);
    $type = $db->rdbms_escape_string ($type);
    if ($user != "") $user = $db->rdbms_escape_string ($user);
    if ($lifetime != "") $lifetime = $db->rdbms_escape_string ($lifetime);
    if ($formats != "") $formats = $db->rdbms_escape_string ($formats);

    // date
    $date = date ("Y-m-d H:i", time());

    // define time of death based on lifetime
    if ($lifetime > 0) $deathtime = time() + intval ($lifetime);
    else $deathtime = 0;

    // get object IDs
    $object_id_array = array();

    // check if object is folder or page/component
    if (!is_array ($object_id) && substr_count ($object_id, "|") > 0)
    {
      // split multiobject into array
      $object_id = link_db_getobject ($object_id);
    }
    // create array for single element
    elseif (!is_array ($object_id)) $object_id = array ($object_id);

    // get object IDs from object path
    if (is_array ($object_id) && sizeof ($object_id) > 0)
    {
      foreach ($object_id as $temp)
      {
        if (trim ($temp) != "")
        {
          if ($temp > 0)
          {
            $object_id_array[] = intval ($temp);
          }
          else
          {
            $temp = rdbms_getobject_id ($temp);
            if ($temp > 0) $object_id_array[] = intval ($temp);
          }
        }
      }
    }

    if (sizeof ($object_id_array) > 0)
    {
      // insert access link info
      $sql = 'INSERT INTO accesslink (hash, date, object_id, type, user, deathtime, formats) ';    
      $sql .= 'VALUES ("'.$hash.'", "'.$date.'", "'.implode ("|", $object_id_array).'", "'.$type.'", "'.$user.'", '.intval ($deathtime).', "'.$formats.'")';
  
      $errcode = "50007";
      $db->rdbms_query ($sql, $errcode, $mgmt_config['today']);

      // save log
      savelog ($db->rdbms_geterror ());    
      $db->rdbms_close();
  
      return true;
    }
    else return false;
  }
  else return false;
} 

// ------------------------------------------------ get access info -------------------------------------------------
// function: rdbms_getaccessinfo()
// input: object hash [string]
// output: result array / false on error

// description:
// Returns all data for an access link as an array.

function rdbms_getaccessinfo ($hash)
{
  global $mgmt_config;

  if ($hash != "")
  {
    $result = array();

    $db = new hcms_db($mgmt_config['dbconnect'], $mgmt_config['dbhost'], $mgmt_config['dbuser'], $mgmt_config['dbpasswd'], $mgmt_config['dbname'], $mgmt_config['dbcharset']);

    $hash = $db->rdbms_escape_string ($hash);

    $sql = 'SELECT date, object_id, type, user, deathtime, formats FROM accesslink WHERE hash="'.$hash.'"';

    $errcode = "50071";
    $done = $db->rdbms_query ($sql, $errcode, $mgmt_config['today'], "select");

    if ($done)
    {
      $row = $db->rdbms_getresultrow ("select");

      $result['date'] = $row['date'];
      $result['object_id'] = $row['object_id'];
      $result['object_ids'] = explode ("|", $row['object_id']);
      $result['type'] = $row['type']; 
      $result['user'] = $row['user']; 
      $result['deathtime'] = $row['deathtime'];
      $result['formats'] = $row['formats'];

      // if time of death vor link is set
      if ($result['deathtime'] > 0)
      {
        // if deathtime was passed
        if ($result['deathtime'] < time())
        {
          $sql = 'DELETE FROM accesslink WHERE hash="'.$hash.'"';

          $errcode = "50072";
          $db->rdbms_query ($sql, $errcode, $mgmt_config['today'], "delete");

          $result = false;
        }
      }
    }

    // save log
    savelog ($db->rdbms_geterror ());    
    $db->rdbms_close();

    if (is_array ($result) && sizeof ($result) > 0) return $result;
    else return false;
  }
  else return false;
}

// ------------------------------------------------ create recipient -------------------------------------------------
// function: rdbms_createrecipient()
// input: object path [string], senders user name [string], recipients user name [string], recipients e-mail [string]
// output: result array / false on error

// description:
// Creates a new recipient entry in the database.

function rdbms_createrecipient ($object, $from_user, $to_user, $email)
{
  global $mgmt_config;

  if ($object != "" && $from_user != "" && $to_user != "" && $email != "")
  {
    // correct object name 
    if (strtolower (@strrchr ($object, ".")) == ".off") $object = @substr ($object, 0, -4);

    $db = new hcms_db($mgmt_config['dbconnect'], $mgmt_config['dbhost'], $mgmt_config['dbuser'], $mgmt_config['dbpasswd'], $mgmt_config['dbname'], $mgmt_config['dbcharset']);

    $date = date ("Y-m-d H:i:s", time());

    $object = $db->rdbms_escape_string ($object);
    $from_user = $db->rdbms_escape_string ($from_user);
    $to_user = $db->rdbms_escape_string ($to_user);
    $email = $db->rdbms_escape_string ($email);

    $object = str_replace (array("%page%/", "%comp%/"), array("*page*/", "*comp*/"), $object);    

    // get object ids of all objects (also all object of folders)
    if (getobject ($object) == ".folder") $sql = 'SELECT object_id FROM object WHERE objectpath LIKE BINARY "'.substr (trim($object), 0, strlen (trim($object))-7).'%"';
    else $sql = 'SELECT object_id FROM object WHERE md5_objectpath="'.md5 ($object).'"';

    $errcode = "50049";
    $done = $db->rdbms_query($sql, $errcode, $mgmt_config['today'], 'select');

    if ($done)
    {
      $i = 1;

      while ($object_id = $db->rdbms_getresultrow ('select'))
      {
        $sql = 'INSERT INTO recipient (object_id, date, from_user, to_user, email) ';    
        $sql .= 'VALUES ("'.intval ($object_id['object_id']).'", "'.$date.'", "'.$from_user.'", "'.$to_user.'", "'.$email.'")';
        
        $errcode = "50050";
        $done = $db->rdbms_query ($sql, $errcode, $mgmt_config['today'], $i++);
      }
    }

    // save log
    savelog ($db->rdbms_geterror());    
    $db->rdbms_close();   
   
    return true;
  }
  else return false;
}

// ------------------------------------------------ get recipients -------------------------------------------------
// function: rdbms_getrecipients()
// input: object path [string]
// output: result array / false on error

// description:
// Returns the recipients data as an array.

function rdbms_getrecipients ($object)
{
  global $mgmt_config;

  if ($object != "")
  {
    // correct object name 
    if (strtolower (@strrchr ($object, ".")) == ".off") $object = @substr ($object, 0, -4);

    $db = new hcms_db($mgmt_config['dbconnect'], $mgmt_config['dbhost'], $mgmt_config['dbuser'], $mgmt_config['dbpasswd'], $mgmt_config['dbname'], $mgmt_config['dbcharset']);

    // clean input
    $object = str_replace (array("%page%/", "%comp%/"), array("*page*/", "*comp*/"), $object); 
    $object = $db->rdbms_escape_string ($object);   

    // get recipients
    $sql = 'SELECT rec.recipient_id, rec.object_id, rec.date, rec.from_user, rec.to_user, rec.email FROM recipient AS rec INNER JOIN object AS obj ON obj.object_id=rec.object_id WHERE obj.md5_objectpath="'.md5 ($object).'"';   

    $errcode = "50041";
    $done = $db->rdbms_query ($sql, $errcode, $mgmt_config['today'], 'select');

    if ($done)
    {
      $i = 0;
      $recipient = array();

      while ($row = $db->rdbms_getresultrow ('select'))
      {
        if (!empty ($row['recipient_id']))
        {
          $recipient[$i]['recipient_id'] = $row['recipient_id'];
          $recipient[$i]['object_id'] = $row['object_id'];
          $recipient[$i]['date'] = $row['date'];
          $recipient[$i]['from_user'] = $row['from_user']; 
          $recipient[$i]['to_user'] = $row['to_user'];  
          $recipient[$i]['email'] = $row['email'];
     
          $i++;
        }
      }
    }

    // save log
    savelog ($db->rdbms_geterror());    
    $db->rdbms_close();      

    if (!empty ($recipient) && sizeof ($recipient) > 0) return $recipient;
    else return false;
  }
  else return false;
}

// ----------------------------------------------- delete recipient -------------------------------------------------
// function: rdbms_deleterecipient()
// input: recipient ID [integer]
// output: true / false on error

// description:
// Deletes a recipient entry from the database.

function rdbms_deleterecipient ($recipient_id)
{
  global $mgmt_config;

  if ($recipient_id != "")
  {   
    $db = new hcms_db($mgmt_config['dbconnect'], $mgmt_config['dbhost'], $mgmt_config['dbuser'], $mgmt_config['dbpasswd'], $mgmt_config['dbname'], $mgmt_config['dbcharset']);

    $sql = 'DELETE FROM recipient WHERE recipient_id='.intval($recipient_id);

    $errcode = "50032";
    $db->rdbms_query ($sql, $errcode, $mgmt_config['today']);

    // save log
    savelog ($db->rdbms_geterror ());    
    $db->rdbms_close();      
   
    return true;
  }
  else return false;
}

// ----------------------------------------------- create queue entry -------------------------------------------------
// function: rdbms_createqueueentry()
// input: action [string], converted object path [string], execution date for the action [YYYY-MM-DD hh:mm], apply for published objects only [boolean], PHP command [string] (optional), user name [string]
// output: true / false on error

// description:
// Creates a new action in the queue.

function rdbms_createqueueentry ($action, $object, $date, $published_only, $cmd, $user)
{
  global $mgmt_config;

  if ($action != "" && is_date ($date, "Y-m-d H:i") && $user != "" && (($object != "" && (substr_count ($object, "%page%/") > 0 || substr_count ($object, "%comp%/") > 0 || intval ($object) > 0)) || $cmd != ""))
  {
    // correct object name 
    if (strtolower (@strrchr ($object, ".")) == ".off") $object = @substr ($object, 0, -4);

    // get object ID
    if (substr_count ($object, "%page%/") > 0 || substr_count ($object, "%comp%/") > 0) $object_id = rdbms_getobject_id ($object);
    else $object_id = $object;

    if ($object_id != false)
    {
      $db = new hcms_db($mgmt_config['dbconnect'], $mgmt_config['dbhost'], $mgmt_config['dbuser'], $mgmt_config['dbpasswd'], $mgmt_config['dbname'], $mgmt_config['dbcharset']);

      // clean input
      $action = $db->rdbms_escape_string ($action);
      $object = $db->rdbms_escape_string ($object);
      $date = date ("Y-m-d H:i", strtotime ($date));
      if (!empty ($published_only)) $published_only = 1;
      else $published_only = 0;
      if (!empty ($cmd)) $cmd = $db->rdbms_escape_string ($cmd);
      $user = $db->rdbms_escape_string ($user);

      $sql = 'INSERT INTO queue (object_id, action, date, published_only, cmd, user) ';    
      $sql .= 'VALUES ('.intval ($object_id).', "'.$action.'", "'.$date.'", '.intval ($published_only).', "'.$cmd.'", "'.$user.'")';

      $errcode = "50033";
      $done = $db->rdbms_query ($sql, $errcode, $mgmt_config['today']); 
 
      // save log
      savelog ($db->rdbms_geterror());

      $db->rdbms_close();

      return $done;
    }
    else return false;
  }
  else return false;
}

// ----------------------------------------------- set date of queue entry -------------------------------------------------
// function: rdbms_setqueueentry()
// input: queue ID [integer], execution date for the action [YYYY-MM-DD hh:mm]
// output: true / false on error

// description:
// Sets the new execution date for a queue entry.

function rdbms_setqueueentry ($queue_id, $date)
{
  global $mgmt_config;

  if (intval ($queue_id) > 0 && is_date ($date, "Y-m-d H:i"))
  {   
    $db = new hcms_db($mgmt_config['dbconnect'], $mgmt_config['dbhost'], $mgmt_config['dbuser'], $mgmt_config['dbpasswd'], $mgmt_config['dbname'], $mgmt_config['dbcharset']);    

    // clean input
    $queue_id = intval ($queue_id);
    $date = date ("Y-m-d H:i", strtotime ($date));

    // query
    $sql = 'UPDATE queue SET date="'.$date.'" WHERE queue_id='.$queue_id;

    $errcode = "50045";
    $done = $db->rdbms_query($sql, $errcode, $mgmt_config['today'], 'update');

    // save log
    savelog ($db->rdbms_geterror ());

    $db->rdbms_close();
     
    return true;
  }
  else return false;
}

// ------------------------------------------------ get queue entries -------------------------------------------------
// function: rdbms_getqueueentries()
// input: action [string], publication name [string] (optional), execution date for the action [YYYY-MM-DD hh:mm] (optional), user name [string] (optional), converted object path [string] (optional)
// output: queue elements as array / false on error

// description:
// Returns the queue entries.

function rdbms_getqueueentries ($action="", $site="", $date="", $user="", $object="")
{
  global $mgmt_config;

  if (is_array ($mgmt_config))
  {
    $db = new hcms_db($mgmt_config['dbconnect'], $mgmt_config['dbhost'], $mgmt_config['dbuser'], $mgmt_config['dbpasswd'], $mgmt_config['dbname'], $mgmt_config['dbcharset']);    

    // check object (can be valid path or mail ID)
    if (substr_count ($object, "%page%/") > 0 || substr_count ($object, "%comp%/") > 0) $object_id = rdbms_getobject_id ($object);
    elseif (is_numeric ($object)) $object_id = intval ($object); 
    elseif ($object != "") return false;  

    // clean input
    if (!empty ($action)) $action = $db->rdbms_escape_string ($action);
    if (!empty ($site)) $site = $db->rdbms_escape_string ($site);
    if (!empty ($date)) $date = date ("Y-m-d H:i", strtotime ($date));
    if (!empty ($user)) $user = $db->rdbms_escape_string ($user);
    if (!empty ($object_id)) $object_id = $db->rdbms_escape_string ($object_id);

    // get recipients
    $sql = 'SELECT que.queue_id, que.action, que.date, que.published_only, que.cmd, que.user, que.object_id, obj.objectpath FROM queue AS que LEFT JOIN object AS obj ON obj.object_id=que.object_id WHERE 1=1';
    if (!empty ($action)) $sql .= ' AND que.action="'.$action.'"';
    if (!empty ($site)) $sql .= ' AND (obj.objectpath LIKE BINARY "*page*/'.$site.'/%" OR obj.objectpath LIKE BINARY "*comp*/'.$site.'/%")';
    if (!empty ($date)) $sql .= ' AND que.date<="'.$date.'"'; 
    if (!empty ($user)) $sql .= ' AND que.user="'.$user.'"';
    if (!empty ($object_id)) $sql .= ' AND que.object_id="'.$object_id.'"';
    $sql .= ' ORDER BY que.date';

    $errcode = "50034";
    $done = $db->rdbms_query($sql, $errcode, $mgmt_config['today'], 'select');

    $queue = array();
   
    if ($done)
    {  
      $i = 0;

      // insert recipients
      while ($row = $db->rdbms_getresultrow ('select'))
      {
        if (!empty ($row['queue_id']))
        {
          $queue[$i]['queue_id'] = $row['queue_id'];
          $queue[$i]['action'] = $row['action'];
          $queue[$i]['object_id'] = $row['object_id'];
          $queue[$i]['objectpath'] = str_replace (array("*page*/", "*comp*/"), array("%page%/", "%comp%/"), $row['objectpath']);
          $queue[$i]['date'] = $row['date'];
          $queue[$i]['published_only'] = $row['published_only'];
          $queue[$i]['cmd'] = $row['cmd'];
          $queue[$i]['user'] = $row['user'];

          $i++;
        }
      }        
    }

    // save log
    savelog ($db->rdbms_geterror());

    $db->rdbms_close();

    if (is_array ($queue) && sizeof ($queue) > 0) return $queue;
    else return false;
  }
  else return false;
}

// ----------------------------------------------- delete queue entry -------------------------------------------------
// function: rdbms_deletequeueentry()
// input: queue ID [integer]
// output: true / false on error

// description:
// Deletes an element from the queue by its ID.

function rdbms_deletequeueentry ($queue_id)
{
  global $mgmt_config;

  if (intval ($queue_id) > 0)
  {   
    $db = new hcms_db($mgmt_config['dbconnect'], $mgmt_config['dbhost'], $mgmt_config['dbuser'], $mgmt_config['dbpasswd'], $mgmt_config['dbname'], $mgmt_config['dbcharset']);    

    // clean input
    $queue_id = intval ($queue_id);

    // query
    $sql = 'SELECT action, object_id, user FROM queue WHERE queue_id='.$queue_id;

    $errcode = "50035";
    $done = $db->rdbms_query($sql, $errcode, $mgmt_config['today'], 'select');

    if ($done)
    {
      $row = $db->rdbms_getresultrow ("select");
      
      // remove queue file
      if ($row['action'] == "mail") 
      {
        $mailfile = $row['object_id'].".".$row['user'].".mail.php";
        deletefile ($mgmt_config['abs_path_data']."queue/", $mailfile, 0);
      }
    }

    // query
    $sql = 'DELETE FROM queue WHERE queue_id='.$queue_id;

    $errcode = "50036";
    $db->rdbms_query ($sql, $errcode, $mgmt_config['today']);

    // save log
    savelog ($db->rdbms_geterror ());

    $db->rdbms_close();
     
    return true;
  }
  else return false;
}

// ----------------------------------------------- create notification -------------------------------------------------
// function: rdbms_createnotification()
// input: object ID or path [integer or string], event names [array], user name [string]
// output: true / false on error

// description:
// Creates a new notification for the requested object and events for a user. 

function rdbms_createnotification ($object, $events, $user)
{
  global $mgmt_config;

  if ($object != "" && is_array ($events) && $user != "")
  {
    // correct object name 
    if (strtolower (strrchr ($object, ".")) == ".off") $object = substr ($object, 0, -4);

    // check object (can be path or ID)
    if (substr_count ($object, "%page%/") > 0 || substr_count ($object, "%comp%/") > 0) $object_id = rdbms_getobject_id ($object);
    elseif (is_numeric ($object)) $object_id = $object;
    else $object_id = false;

    if ($object_id != false)
    {
      $db = new hcms_db($mgmt_config['dbconnect'], $mgmt_config['dbhost'], $mgmt_config['dbuser'], $mgmt_config['dbpasswd'], $mgmt_config['dbname'], $mgmt_config['dbcharset']);    

      // clean input
      $user = $db->rdbms_escape_string ($user);
      if (array_key_exists ("oncreate", $events) && $events['oncreate'] == 1) $oncreate = 1;
      else $oncreate = 0;
      if (array_key_exists ("onedit", $events) && $events['onedit'] == 1) $onedit = 1;
      else $onedit = 0;
      if (array_key_exists ("onmove", $events) && $events['onmove'] == 1) $onmove = 1;
      else $onmove = 0;
      if (array_key_exists ("ondelete", $events) && $events['ondelete'] == 1) $ondelete = 1;
      else $ondelete = 0;

      $sql = 'SELECT count(*) AS count FROM notify WHERE object_id="'.$object_id.'" AND user="'.$user.'"';

      $errcode = "50193";
      $done = $db->rdbms_query ($sql, $errcode, $mgmt_config['today'], 'select');

      if ($done)
      {
        $result = $db->rdbms_getresultrow ('select', 0);
        $count = $result['count'];
  
        if ($count == 0)
        {
          $sql = 'INSERT INTO notify (object_id, user, oncreate, onedit, onmove, ondelete) ';    
          $sql .= 'VALUES ('.intval ($object_id).', "'.$user.'", '.$oncreate.', '.$onedit.', '.$onmove.', '.$ondelete.')';
        }
        else
        {
          $sql = 'UPDATE notify SET oncreate='.$oncreate.', onedit='.$onedit.', onmove='.$onmove.', ondelete='.$ondelete.' WHERE object_id='.$object_id.' AND user="'.$user.'"';
        }

        $errcode = "50093";
        $done = $db->rdbms_query ($sql, $errcode, $mgmt_config['today'], 'insert'); 
      }

      // save log
      savelog ($db->rdbms_geterror());

      $db->rdbms_close();

      return $done;
    }
    else return false;
  }
  else return false;
}

// ------------------------------------------------ get notifications -------------------------------------------------
// function: rdbms_getnotification()
// input: event name [string] (optional), object path [string] (optional), user name [string] (optional)
// output: true / false on error

// description:
// Creates a new notification for the requested object and events for a user. 

function rdbms_getnotification ($event="", $object="", $user="")
{
  global $mgmt_config;

  if (is_array ($mgmt_config))
  {
    //initialize
    $queue = array();

    $db = new hcms_db($mgmt_config['dbconnect'], $mgmt_config['dbhost'], $mgmt_config['dbuser'], $mgmt_config['dbpasswd'], $mgmt_config['dbname'], $mgmt_config['dbcharset']);    
  
    if (!empty ($event))
    {
      $valid_events = array ("oncreate", "onedit", "onmove", "ondelete");
      if (!in_array (strtolower($event), $valid_events)) $event = "";
    }

    if ($object != "")
    {
      $object_id_array = array();

      // correct object name 
      if (strtolower (@strrchr ($object, ".")) == ".off") $object = @substr ($object, 0, -4);

      // get publication
      $site = getpublication ($object);
      if (getobject ($object) == ".folder") $object = getlocation ($object);

      // clean input
      $object = str_replace (array("%page%/", "%comp%/"), array("*page*/", "*comp*/"), $object);
      $object = $db->rdbms_escape_string ($object);

      // get connected objects
      $sql = 'SELECT DISTINCT object_id, id FROM object WHERE md5_objectpath="'.md5 ($object).'"';

      $errcode = "50097";
      $done = $db->rdbms_query($sql, $errcode, $mgmt_config['today'], 'connected');

      if ($done)
      {
        // get object ID and container ID of object
        if ($row = $db->rdbms_getresultrow ('connected'))
        {
          $object_id = intval ($row['object_id']);
          $container_id = intval ($row['id']);
        }

        // get object IDs of connected objects
        if (!empty ($container_id) && $container_id > 0 && !empty ($object_id))
        {
          $sql = 'SELECT DISTINCT object_id FROM object WHERE id='.$container_id.' AND object_id!="'.$object_id.'"';

          $errcode = "50298";
          $done = $db->rdbms_query($sql, $errcode, $mgmt_config['today'], 'connected');

          if ($done)
          {
            while ($row = $db->rdbms_getresultrow ('connected'))
            {
              if (!empty ($row['object_id'])) $object_id_array[] = $row['object_id'];
            }
          }
        }
      }

      // get objects that referred to the object
      if (!empty ($object_id))
      {
        $sql = 'SELECT object.object_id FROM textnodes INNER JOIN object ON object.id=textnodes.id WHERE textnodes.object_id="'.$object_id.'"';

        $errcode = "50299";
        $done = $db->rdbms_query($sql, $errcode, $mgmt_config['today'], 'linked');

        if ($done)
        {
          while ($row = $db->rdbms_getresultrow ('linked'))
          {
            if (!empty ($row['object_id'])) $object_id_array[] = $row['object_id'];
          }
        }
      }
    }

    if ($user != "") $user = $db->rdbms_escape_string ($user);
  
    // get recipients
    $sql = 'SELECT nfy.notify_id, nfy.object_id, obj.objectpath, nfy.user, nfy.oncreate, nfy.onedit, nfy.onmove, nfy.ondelete FROM notify AS nfy, object AS obj WHERE obj.object_id=nfy.object_id';
    if ($event != "") $sql .= ' AND nfy.'.$event.'=1';
    if ($object != "") $sql .= ' AND (obj.objectpath="'.$object.'" OR (INSTR(obj.objectpath, ".folder") > 0 AND INSTR("'.$object.'", SUBSTR(obj.objectpath, 1, INSTR(obj.objectpath, ".folder") - 1)) > 0))';
    if (!empty ($object_id_array) && sizeof ($object_id_array) > 0) $sql .= ' OR nfy.object_id IN ('.implode (",", $object_id_array).')'; 
    if ($user != "") $sql .= ' AND nfy.user="'.$user.'"';
    $sql .= ' ORDER BY obj.objectpath';

    $errcode = "50094";
    $done = $db->rdbms_query($sql, $errcode, $mgmt_config['today'], 'select');

    if ($done)
    {  
      $i = 0;

      // insert recipients
      while ($row = $db->rdbms_getresultrow ('select'))
      {
        if (!empty ($row['notify_id'])) 
        {
          $queue[$i] = array();
          $queue[$i]['notify_id'] = $row['notify_id'];
          $queue[$i]['object_id'] = $row['object_id'];
          $queue[$i]['objectpath'] = str_replace (array("*page*/", "*comp*/"), array("%page%/", "%comp%/"), $row['objectpath']);
          $queue[$i]['user'] = $row['user']; 
          $queue[$i]['oncreate'] = $row['oncreate'];
          $queue[$i]['onedit'] = $row['onedit'];
          $queue[$i]['onmove'] = $row['onmove'];
          $queue[$i]['ondelete'] = $row['ondelete'];

          $i++;
        }
      }        
    }

    // save log
    savelog ($db->rdbms_geterror());    
    $db->rdbms_close();

    if (is_array ($queue) && sizeof ($queue) > 0) return $queue;
    else return false;
  }
  else return false;
}

// ----------------------------------------------- delete notification -------------------------------------------------
// function: rdbms_deletenotification()
// input: notification ID [integer] (optional), object ID or path [integer or string] (optional), user name [string] (optional)
// output: true / false on error

// description:
// Deletes a notification for the requested notification ID, object, or user. 

function rdbms_deletenotification ($notify_id="", $object="", $user="")
{
  global $mgmt_config;

  if ($notify_id != "" || $object != "" || $user != "")
  {   
    $db = new hcms_db($mgmt_config['dbconnect'], $mgmt_config['dbhost'], $mgmt_config['dbuser'], $mgmt_config['dbpasswd'], $mgmt_config['dbname'], $mgmt_config['dbcharset']);

    if ($object != "")
    {
      // check object (can be path or ID)
      if (substr_count ($object, "%page%/") > 0 || substr_count ($object, "%comp%/") > 0) $object_id = rdbms_getobject_id ($object);
      elseif (is_numeric ($object)) $object_id = $object;
      else $object_id = false;
    }

    // clean input
    if (intval ($notify_id) > 0) $notify_id = intval ($notify_id);
    elseif (!empty ($object_id)) $object_id = $db->rdbms_escape_string ($object_id);
    elseif (!empty ($user)) $user = $db->rdbms_escape_string ($user);

    if (!empty ($notify_id)) $sql = 'DELETE FROM notify WHERE notify_id="'.$notify_id.'"';
    elseif (!empty ($object_id)) $sql = 'DELETE FROM notify WHERE object_id="'.$object_id.'"';
    elseif (!empty ($user)) $sql = 'DELETE FROM notify WHERE user="'.$user.'"';

    $errcode = "50092";
    $db->rdbms_query($sql, $errcode, $mgmt_config['today']);

    // save log
    savelog ($db->rdbms_geterror ());    
    $db->rdbms_close();      
    
    return true;
  }
  else return false;
}

// ----------------------------------------------- license notification -------------------------------------------------
// function: rdbms_licensenotification()
// input: location path [string], text ID [string], date beginn [date], date end [date], date format [string]
// output: result array / false on error

// description:
// This function looks up all objects with a date in a defined text field that has to be between the defined date limits.

function rdbms_licensenotification ($folderpath, $text_id, $date_begin, $date_end, $format="%Y-%m-%d")
{
  global $mgmt_config;

  if ($folderpath != "" && $text_id != "" && $date_begin != "" && $date_end != "")
  {
    // initialize
    $result = array();

    $db = new hcms_db ($mgmt_config['dbconnect'], $mgmt_config['dbhost'], $mgmt_config['dbuser'], $mgmt_config['dbpasswd'], $mgmt_config['dbname'], $mgmt_config['dbcharset']);    

    $folderpath = $db->rdbms_escape_string ($folderpath);
    $text_id = $db->rdbms_escape_string ($text_id);
    $date_begin = $db->rdbms_escape_string ($date_begin);
    $date_end = $db->rdbms_escape_string ($date_end);
    $format = $db->rdbms_escape_string ($format);

    $folderpath = str_replace (array("%page%/", "%comp%/"), array("*page*/", "*comp*/"), $folderpath);

    $sql = 'SELECT DISTINCT obj.objectpath as path, tnd.textcontent as cnt FROM object AS obj, textnodes AS tnd ';
    $sql .= 'WHERE obj.id=tnd.id AND obj.objectpath LIKE BINARY "'.$folderpath.'%" AND tnd.text_id="'.$text_id.'" AND "'.$date_begin.'" <= STR_TO_DATE(tnd.textcontent, "'.$format.'") AND "'.$date_end.'" >= STR_TO_DATE(tnd.textcontent, "'.$format.'")';    
    $errcode = "50036";
    $done = $db->rdbms_query($sql, $errcode, $mgmt_config['today']);

    if ($done)
    {
      $i = 0;

      while ($row = $db->rdbms_getresultrow ())
      {
        if (!empty ($row['path'])) 
        {
          $objectpath = str_replace (array("*page*/", "*comp*/"), array("%page%/", "%comp%/"), $row['path']);
          $licenseend = $row['cnt']; 
          $site = getpublication ($objectpath);
          $location = getlocation ($objectpath);    
          $object = getobject ($objectpath);
          $cat = getcategory ($site, $location);
       
          $result[$i] = array();
          $result[$i]['publication'] = $site;
          $result[$i]['location'] = $location;
          $result[$i]['object'] = $object;
          $result[$i]['category'] = $cat;
          $result[$i]['date'] = $licenseend;
          
          $i++;
        }
      }
    }

    // save log
    savelog ($db->rdbms_geterror());
    $db->rdbms_close();

    if (is_array ($result) && sizeof ($result) > 0) return $result;
    else return false;
  }
  else return false;
}

// ----------------------------------------------- daily statistics -------------------------------------------------
// function: rdbms_insertdailystat()
// input: activity [string], container ID [integer,array], user name [string] (optional), include all sub objects in a folder if the container ID is not an array [boolean]
// output: true / false on error

// description:
// Updates the daily access statistics.
// The dailystat table contains a counter for each 'activity' (upload, download, view) for each object (i.e. media file of container) per day.

function rdbms_insertdailystat ($activity, $container_id, $user="", $include_all=false)
{
  global $mgmt_config;

  if ($activity != "" && (is_array ($container_id) || intval ($container_id) > 0))
  {
    $db = new hcms_db ($mgmt_config['dbconnect'], $mgmt_config['dbhost'], $mgmt_config['dbuser'], $mgmt_config['dbpasswd'], $mgmt_config['dbname'], $mgmt_config['dbcharset']);    

    // clean input    
    $activity = $db->rdbms_escape_string ($activity);
    if ($user != "") $user = $db->rdbms_escape_string ($user);

    // get current date
    $date = date ("Y-m-d", time());

    // set user if not defined
    if (trim ($user) == "")
    {
      if (!empty ($_SESSION['hcms_user'])) $user = $_SESSION['hcms_user'];
      else $user = getuserip();
      
      if (empty ($user)) $user = "unknown";
    }

    // add input object to array
    if (!is_array ($container_id)) $container_id_array = array ($container_id);
    else $container_id_array = $container_id;

    // get all container IDs of the objects in the folder
    if ($include_all == true && !is_array ($container_id))
    {
      $sql = 'SELECT objectpath FROM object WHERE id='.intval($container_id);

      $errcode = "50759";
      $done = $db->rdbms_query ($sql, $errcode, $mgmt_config['today'], 'objectpath');

      if ($done)
      {
        $result = $db->rdbms_getresultrow ('objectpath', 0);
        $objectpath = $result['objectpath'];

        if (strpos ($objectpath, ".folder") > 0)
        {
          // select all sub-objects that have not been deleted
          $sql = 'SELECT id FROM object WHERE objectpath LIKE "'.getlocation ($objectpath).'%" AND deleteuser=""';

          $errcode = "50040";
          $done = $db->rdbms_query ($sql, $errcode, $mgmt_config['today'], 'ids');

          if ($done)
          {
            // stats array
            while ($row = $db->rdbms_getresultrow ('ids'))
            {
              if (!empty ($row['id']))
              {
                $container_id_array[] = $row['id'];
              }
            }
          }
        }
      }
    }

    // set access stats for each container ID
    if (is_array ($container_id_array) && sizeof ($container_id_array) > 0)
    {
      foreach ($container_id_array as $container_id)
      {
        if (intval ($container_id) > 0)
        {
          $container_id = intval ($container_id);

          // check to see if there is a row
          $sql = 'SELECT count(*) AS count FROM dailystat WHERE date="'.$date.'" AND user="'.$user.'" AND activity="'.$activity.'" AND id='.$container_id;

          $errcode = "50037";
          $done = $db->rdbms_query ($sql, $errcode, $mgmt_config['today'], 'select');

          if ($done)
          {
            $result = $db->rdbms_getresultrow ('select', 0);
            $count = $result['count'];

            if ($count == 0)
            {
              // insert
              $sql = 'INSERT INTO dailystat (id, user, activity, date, count) VALUES ('.$container_id.',"'.$user.'","'.$activity.'","'.$date.'",1)';
            }
            else
            {
              // update
              $sql = 'UPDATE dailystat SET count=count+1 WHERE date="'.$date.'" AND user="'.$user.'" AND activity="'.$activity.'" AND id='.$container_id;
            }

            $errcode = "50038";
            $db->rdbms_query ($sql, $errcode, $mgmt_config['today'], 'insertupdate');
          }
        }
      }
    }

    // save log
    savelog ($db->rdbms_geterror());
    $db->rdbms_close();    

    return true;
  }
  else return false;  
}

// ----------------------------------------------- get statistics from dailystat -------------------------------------------------
// function: rdbms_getmediastat()
// input: date from [date] (optional), date to [date] (optional), activity [string] (optional), container ID [integer] (optional), object path [string] (optional), user name [string] (optional), 
//        return file size of objects [boolean] (optional), limit returned results [integer] (optional), cache timeout in seconds using 0 for no caching [integer] (optional)
// output: result array of objects / false on error

// description:
// Provides the data of objects based on the information of table dailystats. The results will be sorted by date in descending order (most recent first).

function rdbms_getmediastat ($date_from="", $date_to="", $activity="", $container_id="", $objectpath="", $user="", $return_filesize=true, $maxhits=0, $cache_timeout=0)
{
  global $mgmt_config;

  // define file name for cache (MD5 hash identifies the input parameters)
  $filename = md5 ($date_from.$date_to.$activity.$container_id.$objectpath.$user.$return_filesize).".stat.json";

  // initialize
  $dailystat = array();
  $cached = false;

  // load and use cached results
  if (intval ($cache_timeout) > 0 && is_file ($mgmt_config['abs_path_temp'].$filename) && filemtime ($mgmt_config['abs_path_temp'].$filename) > (time() - $cache_timeout))
  {
    $json = loadfile ($mgmt_config['abs_path_temp'], $filename);

    if ($json != "")
    {
      $dailystat = json_decode ($json, true);
      $cached = true;
    }
  }

  // get data from database if no cached data has been loaded
  if (empty ($cached))
  {
    // mySQL connect
    $db = new hcms_db ($mgmt_config['dbconnect'], $mgmt_config['dbhost'], $mgmt_config['dbuser'], $mgmt_config['dbpasswd'], $mgmt_config['dbname'], $mgmt_config['dbcharset']);    

    // clean input
    if ($date_from != "") $date_from = $db->rdbms_escape_string ($date_from);
    if ($date_to != "") $date_to = $db->rdbms_escape_string ($date_to);
    if ($activity != "") $activity = $db->rdbms_escape_string ($activity);
    if (intval ($container_id) > 0) $container_id = intval ($container_id);
    if ($objectpath != "") $objectpath = $db->rdbms_escape_string ($objectpath);
    if ($user != "") $user = $db->rdbms_escape_string ($user);

    if ($maxhits != "" || intval ($maxhits) > 0)
    {
      if (strpos ($maxhits, ",") > 0)
      {
        list ($starthits, $endhits) = explode (",", $maxhits);
        $starthits = $db->rdbms_escape_string (trim ($starthits));
        $endhits = $db->rdbms_escape_string (trim ($endhits));
      }
      else $maxhits = $db->rdbms_escape_string ($maxhits);
    }

    // get object info
    if ($objectpath != "")
    {
      $site = getpublication ($objectpath);
      $cat = getcategory ($site, $objectpath);
      $object_info = getfileinfo ($site, $objectpath, $cat);
      $objectpath = str_replace ('%', '*', $objectpath);
      if (getobject ($objectpath) == ".folder") $location = getlocation ($objectpath);
    }

    $sqlfilesize = "";
    $sqltable = "";
    $sqlwhere = "";
    $sqlgroup = "";
    $limit = "";

    // include media table for file size
    if (!empty ($return_filesize))
    {
      // search by objectpath
      if ($objectpath != "")
      {
        $sqlfilesize = ', SUM(object.filesize) AS filesize';
        $sqltable = "INNER JOIN object ON dailystat.id=object.id";
        if ($object_info['type'] == 'Folder') $sqlwhere = 'AND object.objectpath LIKE "'.$location.'%"';
        else $sqlwhere = 'AND object.md5_objectpath="'.md5 ($objectpath).'"';
        $sqlgroup = 'GROUP BY dailystat.date, dailystat.id, dailystat.user';
      }
      // search by container id
      elseif (intval ($container_id) > 0)
      {
        $sqlfilesize = ', object.filesize AS filesize';
        $sqltable = "INNER JOIN object ON dailystat.id=object.id";
        $sqlwhere = 'AND dailystat.id='.$container_id;
        $sqlgroup = 'GROUP BY dailystat.date, dailystat.user';
      }
    }
    else
    {
      // search by objectpath
      if ($objectpath != "")
      {
        $sqlfilesize = "";
        $sqltable = 'INNER JOIN object ON dailystat.id=object.id';
        if ($object_info['type'] == 'Folder') $sqlwhere = 'AND object.objectpath LIKE "'.$location.'%"';
        else $sqlwhere = 'AND object.md5_objectpath="'.md5 ($objectpath).'"';
        $sqlgroup = 'GROUP BY dailystat.date, dailystat.user';
      }
      // search by container id
      elseif (intval ($container_id) > 0)
      { 
        $sqlfilesize = "";
        $sqltable = '';
        $sqlwhere = 'AND dailystat.id='.$container_id;
        $sqlgroup = 'GROUP BY dailystat.date, dailystat.user';
      }
    }

    if ($date_from != "") $sqlwhere .= ' AND dailystat.date>="'.date("Y-m-d", strtotime($date_from)).'"';
    if ($date_to != "") $sqlwhere .= ' AND dailystat.date<="'.date("Y-m-d", strtotime($date_to)).'"';
    if ($activity != "") $sqlwhere .= ' AND dailystat.activity="'.$activity.'"';
    if ($user != "") $sqlwhere .= ' AND dailystat.user="'.$user.'"';

    if (empty ($sqlgroup)) $sqlgroup = 'GROUP BY dailystat.id';

    if (isset ($starthits) && intval ($starthits) >= 0 && isset ($endhits) && intval ($endhits) > 0) $limit = 'LIMIT '.intval ($starthits).','.intval ($endhits);
    elseif (isset ($maxhits) && intval ($maxhits) > 0) $limit = 'LIMIT 0,'.intval ($maxhits);

    $sql = 'SELECT dailystat.id, dailystat.date, dailystat.activity, SUM(dailystat.count) AS count'.$sqlfilesize.', dailystat.user FROM dailystat '.$sqltable.' WHERE dailystat.id!="" '.$sqlwhere.' '.$sqlgroup.' ORDER BY dailystat.date DESC '.$limit;

    $errcode = "50039";
    $done = $db->rdbms_query ($sql, $errcode, $mgmt_config['today']);

    if ($done)
    {
      $i = 0;

      // stats array
      while ($row = $db->rdbms_getresultrow ())
      {
        if (!empty ($row['id']))
        {
          $dailystat[$i]['container_id'] = sprintf ("%07d", $row['id']);
          $dailystat[$i]['date'] = $row['date'];
          $dailystat[$i]['activity'] = $row['activity'];
          $dailystat[$i]['count'] = $row['count'];
          $dailystat[$i]['filesize'] = @$row['filesize'];
          $dailystat[$i]['totalsize'] = (@$row['filesize'] > 0 ? ($row['count'] * @$row['filesize']) : 0);
          $dailystat[$i]['user'] = $row['user'];
          
          $i++;
        }
      }
    }

    // save log
    savelog ($db->rdbms_geterror ());
    $db->rdbms_close();
  }

  // cache results if no cached data has been loaded
  if (intval ($cache_timeout) > 0 && empty ($cached))
  {
    $json = json_encode ($dailystat);
    savefile ($mgmt_config['abs_path_temp'], $filename, $json);
  }

  if (!empty ($dailystat) && is_array ($dailystat)) return $dailystat;
  else return false;
}

// ----------------------------------------------- get filesize from media -------------------------------------------------
// function: rdbms_getfilesize()
// input: container ID [integer] (optional), object or location path [string] (optional), include space used by assets in recycle bin [boolean] (optional)
// output: result array / false on error

// description:
// Provides the filesize and count of objects for a requested object or location.

function rdbms_getfilesize ($container_id="", $objectpath="", $recylcebin=false)
{
  global $mgmt_config;

  if (intval ($container_id) > 0 || $objectpath != "")
  {
    // initialize
    $result = array();

    // mySQL connect
    $db = new hcms_db ($mgmt_config['dbconnect'], $mgmt_config['dbhost'], $mgmt_config['dbuser'], $mgmt_config['dbpasswd'], $mgmt_config['dbname'], $mgmt_config['dbcharset']);    

    // get file size based on
    // container id
    if (intval ($container_id) > 0)
    {
      $container_id = intval ($container_id);

      $sqladd = 'WHERE id='.$container_id;
      $sqlfilesize = 'filesize';
    }
    // full media storage
    elseif ($objectpath == "%hcms%")
    {
      $sqladd = '';
      $sqlfilesize = 'SUM(filesize) AS filesize';
    }
    // object path
    elseif ($objectpath != "")
    {
      $site = getpublication ($objectpath);
      $cat = getcategory ($site, $objectpath);
      $object_info = getfileinfo ($site, $objectpath, $cat);

      $objectpath = $db->rdbms_escape_string ($objectpath);
      $objectpath = str_replace ('%', '*', $objectpath);

      if (getobject ($objectpath) == ".folder") $objectpath = getlocation ($objectpath);

      $sqladd = '';

      // query objectpath
      if ($object_info['type'] == "Folder") $sqladd .= 'WHERE objectpath LIKE "'.$objectpath.'%"';
      else $sqladd .= 'WHERE objectpath="'.$objectpath.'"';

      // exclude recycled files
      if (empty ($recylcebin)) $sqladd .= ' AND objectpath NOT LIKE "%.recycle" AND objectpath NOT LIKE "%.recycle%"';

      $sqlfilesize = 'SUM(filesize) AS filesize';
    }

    $sql = 'SELECT '.$sqlfilesize.' FROM object '.$sqladd;

    $errcode = "50543";
    $done = $db->rdbms_query ($sql, $errcode, $mgmt_config['today'], 'selectfilesize');

    if ($done)
    {
      $row = $db->rdbms_getresultrow ('selectfilesize');
      $result['filesize'] = $row['filesize'];
      $result['count'] = 1;
    }

    // count files (exclude recycled files)
    if ($objectpath != "" && !empty ($object_info['type']) && $object_info['type'] == "Folder")
    {
      $sql = 'SELECT count(objectpath) AS count FROM object WHERE objectpath LIKE "'.$objectpath.'%" AND objectpath NOT LIKE "%.recycle" AND objectpath NOT LIKE "%.recycle%"'; 

      $errcode = "50042";
      $done = $db->rdbms_query ($sql, $errcode, $mgmt_config['today'], 'selectcount');

      if ($done)
      {
        $row = $db->rdbms_getresultrow ('selectcount');
        $result['count'] = $row['count'];
      }
    }

    // save log
    savelog ($db->rdbms_geterror ());
    $db->rdbms_close();
 
    if (is_array ($result) && sizeof ($result) > 0) return $result;
    else return false;
  } 
  else return false;
}

// ----------------------------------------------- create task -------------------------------------------------
// function: rdbms_createtask()
// input: object ID or object path [string], project/subproject ID if the task should be assigned to a project [integer] (optional), from user name [string], to user name [email-address] (optional), start date [yyyy-mm-dd] (optional), finish date [yyyy-mm-dd] (optional),
//        category [link,user,workflow] (optional), task name [string], task description [string] (optional), priority [high,medium,low] (optional), planned effort in taskunit [integer] (optional)
// output: true/false
// requires: config.inc.php

// description:
// Creates a new user task and send optional e-mail to user.
// Since verion 5.8.4 the data will be stored in RDBMS instead of XML files.

function rdbms_createtask ($object_id, $project_id=0, $from_user="", $to_user="", $startdate="", $finishdate="", $category="", $taskname="", $description="", $priority="low", $planned="")
{
  global $mgmt_config;

  if (is_file ($mgmt_config['abs_path_cms']."task/task_list.php") && $taskname != "" && strlen ($taskname) <= 200 && strlen ($description) <= 3600 && in_array (strtolower ($priority), array("low","medium","high")))
  {
    $db = new hcms_db ($mgmt_config['dbconnect'], $mgmt_config['dbhost'], $mgmt_config['dbuser'], $mgmt_config['dbpasswd'], $mgmt_config['dbname'], $mgmt_config['dbcharset']);    

    // try to get object_id from object path
    if ($object_id != "" && intval ($object_id) < 1)
    {
      // get object id
      $object_id = rdbms_getobject_id ($object_id);
    }

    // clean input
    if ($object_id != "") $object_id = intval ($object_id);
    else $object_id = 0;
    if ($project_id != "") $project_id = intval ($project_id);
    else $project_id = 0;
    if ($from_user != "") $from_user = $db->rdbms_escape_string ($from_user);
    if ($to_user != "") $to_user = $db->rdbms_escape_string ($to_user);
    if ($startdate != "") $startdate = date ("Y-m-d", strtotime ($startdate));
    else $startdate = "0000-00-00";
    if ($finishdate != "") $finishdate = date ("Y-m-d", strtotime ($finishdate));
    else $startdate = "0000-00-00";
    if ($category != "") $category = $db->rdbms_escape_string ($category);
    else $category = "user";
    $taskname = $db->rdbms_escape_string ($taskname);
    if ($description != "") $description = $db->rdbms_escape_string ($description);
    if ($priority != "") $priority = $db->rdbms_escape_string (strtolower ($priority));
    if ($planned != "") $planned = $db->rdbms_escape_string (correctnumber ($planned));

    // set user if not defined
    if ($from_user == "")
    {
      if (!empty ($_SESSION['hcms_user'])) $from_user = $_SESSION['hcms_user'];
      elseif (getuserip () != "") $from_user = getuserip ();
      else $from_user = "System";
    }

    // insert
    $sql = 'INSERT INTO task (object_id, project_id, task, from_user, to_user, startdate, finishdate, category, description, priority, planned, status) VALUES ('.$object_id.','.$project_id.',"'.$taskname.'","'.$from_user.'","'.$to_user.'","'.$startdate.'","'.$finishdate.'","'.$category.'","'.$description.'","'.$priority.'","'.$planned.'",0)';

    $errcode = "50048";
    $db->rdbms_query ($sql, $errcode, $mgmt_config['today'], 'insert');

    // save log
    savelog ($db->rdbms_geterror());
    $db->rdbms_close();

    return true;
  } 
  else return false;
}

// ----------------------------------------------- set task -------------------------------------------------
// function: rdbms_settask()
// input: task ID [integer], object ID [integer or string] (optional), project/subproject ID the task belongs to [integer or string] (optional), to_user name [string] (optional), start date [yyyy-mm-dd or string] (optional), finish date [yyyy-mm-dd or string] (optional),
//        name of task [string] (optional), task message/description [string] (optional), sendmail [true/false], priority [high,medium,low] (optional), status in percent [0-100] (optional), 
//        planned effort in taskunit [float] (optional), actual effort in taskunit [float] (optional)
// output: true/false
// requires: config.inc.php

// description:
// Saves data of a user task and send optional e-mail to user.
// Since verion 5.8.4 the data will be stored in RDBMS instead of XML files.
// Use *Leave* as input if a value should not be changed. 

function rdbms_settask ($task_id, $object_id="*Leave*", $project_id="*Leave*", $to_user="*Leave*", $startdate="*Leave*", $finishdate="*Leave*", $taskname="*Leave*", $description="*Leave*", $priority="*Leave*", $status="*Leave*", $planned="*Leave*", $actual="*Leave*")
{
  global $mgmt_config;

  if (is_file ($mgmt_config['abs_path_cms']."task/task_list.php") && intval ($task_id) > 0 && ($taskname == "" || strlen ($taskname) <= 200) && ($description == "" || strlen ($description) <= 3600) && ($priority == "*Leave*" || in_array (strtolower ($priority), array("low","medium","high"))))
  {
    $db = new hcms_db ($mgmt_config['dbconnect'], $mgmt_config['dbhost'], $mgmt_config['dbuser'], $mgmt_config['dbpasswd'], $mgmt_config['dbname'], $mgmt_config['dbcharset']);

    // clean input
    $sql_update = array();

    if ($object_id != "*Leave*" && intval ($object_id) >= 0) $sql_update[] = 'object_id="'.intval($object_id).'"';
    if ($project_id != "*Leave*" && intval ($project_id) >= 0) $sql_update[] = 'project_id="'.intval($project_id).'"';
    if ($to_user != "*Leave*" && $to_user != "") $sql_update[] = 'to_user="'.$db->rdbms_escape_string ($to_user).'"';
    if ($startdate != "*Leave*") $sql_update[] = 'startdate="'.$db->rdbms_escape_string ($startdate).'"';
    if ($finishdate != "*Leave*") $sql_update[] = 'finishdate="'.$db->rdbms_escape_string ($finishdate).'"';
    if ($taskname != "*Leave*" && $taskname != "") $sql_update[] = 'task="'.$db->rdbms_escape_string ($taskname).'"';
    if ($description != "*Leave*") $sql_update[] = 'description="'.$db->rdbms_escape_string ($description).'"';
    if ($priority != "*Leave*" && $priority != "") $sql_update[] = 'priority="'.$db->rdbms_escape_string (strtolower ($priority)).'"';
    if ($status != "*Leave*" && intval ($status) >= 0) $sql_update[] = 'status="'.intval ($status).'"';
    if ($planned != "*Leave*" && floatval ($planned) >= 0) $sql_update[] = 'planned="'.correctnumber($planned).'"';
    if ($actual != "*Leave*" && floatval ($actual) >= 0) $sql_update[] = 'actual="'.correctnumber($actual).'"';

    // insert
    $sql = 'UPDATE task SET ';
    $sql .= implode (", ", $sql_update);
    $sql .= ' WHERE task_id='.intval($task_id);

    $errcode = "50058";
    $db->rdbms_query ($sql, $errcode, $mgmt_config['today'], 'update');

    // save log
    savelog ($db->rdbms_geterror());
    $db->rdbms_close();

    return true;
  } 
  else return false;
}

// ------------------------------------------------ get task -------------------------------------------------
// function: rdbms_gettask()
// input: task ID [integer] (optional), object ID [integer] (optional), project/subproject ID the task belongs to [integer] (optional), from user name [string] (optional), to user name [string] (optional), start date [yyyy-mm-dd] (optional), finish date [yyyy-mm-dd] (optional),
//        attributes for the order by SQL statement [string] (optional)
// output: result array / false on error
// requires: config.inc.php

// description:
// Reads all values of a task.

function rdbms_gettask ($task_id="", $object_id="", $project_id="", $from_user="", $to_user="", $startdate="", $finishdate="", $order_by="startdate DESC")
{
  global $mgmt_config;

  if (is_file ($mgmt_config['abs_path_cms']."task/task_list.php") && is_array ($mgmt_config))
  {
    $db = new hcms_db($mgmt_config['dbconnect'], $mgmt_config['dbhost'], $mgmt_config['dbuser'], $mgmt_config['dbpasswd'], $mgmt_config['dbname'], $mgmt_config['dbcharset']);    

    // try to get object_id from object path
    if ($object_id != "" && intval ($object_id) < 1)
    {
      // get object id
      $object_id = rdbms_getobject_id ($object_id);
    }

    // clean input
    if ($task_id > 0) $task_id = intval ($task_id);
    if ($object_id > 0) $object_id = intval ($object_id);
    if ($project_id > 0) $project_id = intval ($project_id);
    if ($from_user != "") $from_user = $db->rdbms_escape_string ($from_user);
    if ($to_user != "") $to_user = $db->rdbms_escape_string ($to_user);
    if ($startdate != "") $startdate = date ("Y-m-d", strtotime ($startdate));
    if ($finishdate != "") $finishdate = date ("Y-m-d", strtotime ($finishdate));
    if ($order_by != "") $order_by = $db->rdbms_escape_string ($order_by);
  
    // get recipients
    $sql = 'SELECT task_id, object_id, project_id, task, from_user, to_user, startdate, finishdate, category, description, priority, status, planned, actual FROM task';

    if ($task_id > 0)
    {
      $sql .= ' WHERE task_id="'.$task_id.'"';
    }
    else
    {
      $sql .= ' WHERE 1=1';
      if ($object_id > 0) $sql .= ' AND object_id="'.$object_id.'"';
      if ($project_id > 0) $sql .= ' AND project_id="'.$project_id.'"';  
      if ($from_user != "") $sql .= ' AND from_user="'.$from_user.'"';
      if ($to_user != "") $sql .= ' AND to_user="'.$to_user.'"';
      if ($startdate != "") $sql .= ' AND startdate="'.$startdate.'"';
      if ($finishdate != "") $sql .= ' AND finishdate="'.$finishdate.'"';
    }
    if ($order_by != "") $sql .= ' ORDER BY '.$order_by;

    $errcode = "50094";
    $done = $db->rdbms_query($sql, $errcode, $mgmt_config['today'], 'select');

    if ($done)
    {
      $result = array();
      $i = 0;

      // insert recipients
      while ($row = $db->rdbms_getresultrow ('select'))
      {
        if (!empty ($row['task_id']))
        {
          $result[$i]['task_id'] = $row['task_id'];
          $result[$i]['object_id'] = $row['object_id'];
          $result[$i]['objectpath'] = rdbms_getobject($row['object_id']);
          $result[$i]['project_id'] = $row['project_id'];
          $result[$i]['taskname'] = $row['task'];
          $result[$i]['from_user'] = $row['from_user']; 
          $result[$i]['to_user'] = $row['to_user'];
          $result[$i]['startdate'] = $row['startdate'];
          $result[$i]['finishdate'] = $row['finishdate'];
          $result[$i]['category'] = $row['category'];
          $result[$i]['description'] = $row['description'];
          $result[$i]['priority'] = $row['priority'];
          $result[$i]['status'] = $row['status'];
          $result[$i]['planned'] = $row['planned'];
          $result[$i]['actual'] = $row['actual'];

          $i++;
        }
      }        
    }

    // save log
    savelog ($db->rdbms_geterror());    
    $db->rdbms_close();

    if (!empty ($result) && is_array (@$result)) return $result;
    else return false;
  }
  else return false;
}

// ----------------------------------------------- delete task -------------------------------------------------
// function: rdbms_deletetask()
// input: task ID or array of task IDs to be deleted [integer] (optional), object ID [integer] (optional), user name [string] (optional)
// output: true/false
// requires: config.inc.php

// description:
// Deletes the requested tasks.

function rdbms_deletetask ($task_id="", $object_id="", $to_user="")
{
  global $mgmt_config;
  
  if (is_file ($mgmt_config['abs_path_cms']."task/task_list.php") && $task_id != "" || $object_id != "" || $to_user != "")
  {   
    $db = new hcms_db($mgmt_config['dbconnect'], $mgmt_config['dbhost'], $mgmt_config['dbuser'], $mgmt_config['dbpasswd'], $mgmt_config['dbname'], $mgmt_config['dbcharset']);

    // try to get object_id from object path
    if ($object_id != "" && intval ($object_id) < 1)
    {
      // get object id
      $object_id = rdbms_getobject_id ($object_id);
    }

    // clean input
    if (intval ($task_id) > 0) $task_id = intval ($task_id);
    elseif (intval ($object_id) > 0) $object_id = intval ($object_id);
    elseif (trim ($to_user) != "") $to_user = $db->rdbms_escape_string ($to_user);

    if (!empty ($task_id)) $sql = 'DELETE FROM task WHERE task_id="'.$task_id.'"';
    elseif (!empty ($object_id)) $sql = 'DELETE FROM task WHERE object_id="'.$object_id.'"';
    elseif (!empty ($to_user)) $sql = 'DELETE FROM task WHERE to_user="'.$to_user.'"';

    $errcode = "50098";
    $db->rdbms_query ($sql, $errcode, $mgmt_config['today']);

    // save log
    savelog ($db->rdbms_geterror ());    
    $db->rdbms_close();      
 
    return true;
  }
  else return false;
}

// ----------------------------------------------- create project -------------------------------------------------
// function: rdbms_createproject()
// input: ID of main project (only if the project is a subproject) [integer], object ID or path to object [string] (optional), user name of sub/project owner [string], project name [string], project description [string] (optional)
// output: true/false
// requires: config.inc.php

// description:
// This function creates a new project.

function rdbms_createproject ($subproject_id, $object_id=0, $user="", $projectname="", $description="")
{
  global $mgmt_config;

  if (is_file ($mgmt_config['abs_path_cms']."project/project_list.php") && $projectname != "" && strlen ($projectname) <= 200 && strlen ($description) <= 3600)
  {
    $db = new hcms_db ($mgmt_config['dbconnect'], $mgmt_config['dbhost'], $mgmt_config['dbuser'], $mgmt_config['dbpasswd'], $mgmt_config['dbname'], $mgmt_config['dbcharset']);    

    // try to get object_id from object path
    if ($object_id != "" && intval ($object_id) < 1)
    {
      // get object id
      $object_id = rdbms_getobject_id ($object_id);
    }

    // clean input
    if ($subproject_id != "") $subproject_id = intval ($subproject_id);
    else $subproject_id = 0;
    if ($object_id != "") $object_id = intval ($object_id);
    else $object_id = 0;
    if ($user != "") $user = $db->rdbms_escape_string ($user);
    $projectname = $db->rdbms_escape_string ($projectname);
    if ($description != "") $description = $db->rdbms_escape_string ($description);

    // set user if not defined
    if ($user == "")
    {
      if (!empty ($_SESSION['hcms_user'])) $user = $_SESSION['hcms_user'];
      elseif (getuserip () != "") $user = getuserip ();
      else $user = "System";
    }

    // insert
    $sql = 'INSERT INTO project (subproject_id, object_id, createdate, project ,user, description) VALUES ('.$subproject_id.', '.$object_id.', "'.date ("Y-m-d H:i:s", time()).'", "'.$projectname.'", "'.$user.'", "'.$description.'")';

    $errcode = "50068";
    $db->rdbms_query ($sql, $errcode, $mgmt_config['today'], 'insert');

    // save log
    savelog ($db->rdbms_geterror());
    $db->rdbms_close();

    return true;
  } 
  else return false;
}

// ----------------------------------------------- edit project -------------------------------------------------
// function: rdbms_setproject()
// input: project ID [integer], ID of main project (only if project is a subproject) [integer or string], object ID or path to object [string] (optional), user name of sub/project owner [string] (optional), project name [string] (optional), project description [string] (optional)
// output: true/false
// requires: config.inc.php

// description:
// This function saves data of an existing project.
// Use *Leave* as input if a value should not be changed. 

function rdbms_setproject ($project_id, $subproject_id="*Leave*", $object_id="*Leave*", $user="*Leave*", $projectname="*Leave*", $description="*Leave*")
{
  global $mgmt_config;

  if (is_file ($mgmt_config['abs_path_cms']."project/project_list.php") && intval ($project_id) > 0 && $projectname != "" && strlen ($projectname) <= 200 && strlen ($description) <= 3600)
  {
    $db = new hcms_db ($mgmt_config['dbconnect'], $mgmt_config['dbhost'], $mgmt_config['dbuser'], $mgmt_config['dbpasswd'], $mgmt_config['dbname'], $mgmt_config['dbcharset']);    

    // try to get object_id from object path
    if ($object_id != "*Leave*" && intval ($object_id) < 1)
    {      
      $object_id = rdbms_getobject_id ($object_id);
    }

    // clean input
    $sql_update = array();

    if ($subproject_id != "*Leave*" && intval ($subproject_id) >= 0) $sql_update[] = 'subproject_id="'.intval($subproject_id).'"';
    if ($object_id != "*Leave*" && intval ($object_id) >= 0) $sql_update[] = 'object_id="'.intval ($object_id).'"';
    if ($user != "*Leave*") $sql_update[] = 'user="'.$db->rdbms_escape_string ($user).'"';
    if ($projectname != "*Leave*") $sql_update[] = 'project="'.$db->rdbms_escape_string ($projectname).'"';
    if ($description != "*Leave*") $sql_update[] = 'description="'.$db->rdbms_escape_string ($description).'"';

    // insert
    $sql = 'UPDATE project SET ';
    $sql .= implode (", ", $sql_update);
    $sql .= ' WHERE project_id='.intval($project_id);

    $errcode = "50069";
    $db->rdbms_query ($sql, $errcode, $mgmt_config['today'], 'update');

    // save log
    savelog ($db->rdbms_geterror());
    $db->rdbms_close();

    return true;
  } 
  else return false;
}

// ------------------------------------------------ get project -------------------------------------------------
// function: rdbms_getproject()
// input: task ID [integer] (optional), object ID [integer] (optional), subproject ID the task belongs to [integer] (optional), user name [string] (optional), attributes for order by SQL statement [string] (optional)    
// output: result array / false on error
// requires: config.inc.php

// description:
// Reads all values of a project.

function rdbms_getproject ($project_id="", $subproject_id="", $object_id="", $user="", $order_by="project")
{
  global $mgmt_config;

  if (is_file ($mgmt_config['abs_path_cms']."project/project_list.php") && is_array ($mgmt_config))
  {
    $db = new hcms_db($mgmt_config['dbconnect'], $mgmt_config['dbhost'], $mgmt_config['dbuser'], $mgmt_config['dbpasswd'], $mgmt_config['dbname'], $mgmt_config['dbcharset']);    
    
    // try to get object_id from object path
    if ($object_id != "" && intval ($object_id) < 1)
    {      
      $object_id = rdbms_getobject_id ($object_id);
    }

    // clean input
    if ($project_id != "") $project_id = intval ($project_id);
    if (is_int ($subproject_id)) $subproject_id = intval ($subproject_id);
    if ($object_id != "") $object_id = intval ($object_id);
    if ($user != "") $user = $db->rdbms_escape_string ($user);
    if ($order_by != "") $order_by = $db->rdbms_escape_string ($order_by);

    // get recipients
    $sql = 'SELECT project_id, subproject_id, object_id, project, user, description FROM project WHERE 1=1';

    if ($project_id > 0 && $subproject_id < 1) $sql .= ' AND project_id="'.$project_id.'"';
    elseif ($project_id < 1 && $subproject_id >= 0) $sql .= ' AND subproject_id="'.$subproject_id.'"';
    elseif ($project_id > 0 && $subproject_id >= 0) $sql .= ' AND (project_id="'.$project_id.'" OR subproject_id="'.$subproject_id.'")';

    if ($object_id != "") $sql .= ' AND object_id="'.$object_id.'"';    
    if ($user != "") $sql .= ' AND user="'.$user.'"';
    if ($order_by != "") $sql .= ' ORDER BY '.$order_by;

    $errcode = "50064";
    $done = $db->rdbms_query($sql, $errcode, $mgmt_config['today'], 'select');

    if ($done)
    {
      $result = array();
      $i = 0;

      // insert recipients
      while ($row = $db->rdbms_getresultrow ('select'))
      {
        if (!empty ($row['project_id']))
        {
          $result[$i]['project_id'] = $row['project_id'];
          $result[$i]['subproject_id'] = $row['subproject_id'];
          $result[$i]['object_id'] = $row['object_id'];
          $result[$i]['objectpath'] = rdbms_getobject ($row['object_id']);
          $result[$i]['projectname'] = $row['project'];
          $result[$i]['user'] = $row['user']; 
          $result[$i]['description'] = $row['description'];
          if ($row['subproject_id'] > 0) $result[$i]['type'] = "Subproject";
          else $result[$i]['type'] = "Project";

          $i++;
        }
      }
    }

    // save log
    savelog ($db->rdbms_geterror());    
    $db->rdbms_close();
    
    if (!empty ($result) && is_array ($result)) return $result;
    else return false;
  }
  else return false;
}

// ----------------------------------------------- delete project -------------------------------------------------
// function: rdbms_deleteproject()
// input: project ID or array of project IDs to be deleted [integer] (optional), object ID [integer] (optional), user name [string] (optional)
// output: true/false
// requires: config.inc.php

// description:
// This function removes projects.

function rdbms_deleteproject ($project_id="", $object_id="", $user="")
{
  global $mgmt_config;
  
  if (is_file ($mgmt_config['abs_path_cms']."project/project_list.php") && ($project_id != "" || $object_id != "" || $user != ""))
  {   
    $db = new hcms_db($mgmt_config['dbconnect'], $mgmt_config['dbhost'], $mgmt_config['dbuser'], $mgmt_config['dbpasswd'], $mgmt_config['dbname'], $mgmt_config['dbcharset']);

    // try to get object_id from object path
    if ($object_id != "" && intval ($object_id) < 1)
    {      
      $object_id = rdbms_getobject_id ($object_id);
    }

    // clean input
    if (intval ($project_id) > 0) $project_id = intval ($project_id);
    elseif (!intval ($object_id) > 0) $object_id = intval ($object_id);
    elseif (trim ($user) != "") $user = $db->rdbms_escape_string ($user);

    if (!empty ($project_id)) $sql = 'DELETE FROM project WHERE project_id="'.$project_id.'"';
    elseif (!empty ($object_id)) $sql = 'DELETE FROM project WHERE object_id="'.$object_id.'"';
    elseif (!empty ($to_user)) $sql = 'DELETE FROM project WHERE user="'.$user.'"';
     
    $errcode = "50070";
    $db->rdbms_query ($sql, $errcode, $mgmt_config['today']);

    // save log
    savelog ($db->rdbms_geterror ());    
    $db->rdbms_close();      

    return true;
  }
  else return false;
}

// ----------------------------------------------- edit workflow status -------------------------------------------------
// function: rdbms_setworkflow()
// input: container ID [integer], workflow date [datetime], workflow status [string], workflow user name [string]
// output: true/false
// requires: config.inc.php

// description:
// This function saves the status of the workflow for an object.
// Use *Leave* as input if a value should not be changed. 

function rdbms_setworkflow ($container_id, $workflowdate, $workflowstatus, $workflowuser)
{
  global $mgmt_config;

  if (is_file ($mgmt_config['abs_path_cms']."workflow/frameset_workflow.php") && intval ($container_id) > 0 && $workflowdate != "" && strpos ($workflowstatus, "/") > 0 && strlen ($workflowuser) > 0)
  {
    $db = new hcms_db ($mgmt_config['dbconnect'], $mgmt_config['dbhost'], $mgmt_config['dbuser'], $mgmt_config['dbpasswd'], $mgmt_config['dbname'], $mgmt_config['dbcharset']);    

    // clean input
    $sql_update = array();

    $sql_update[] = 'workflowdate="'.date ("Y-m-d H:i", strtotime ($workflowdate)).'"';
    $sql_update[] = 'workflowstatus="'.$db->rdbms_escape_string ($workflowstatus).'"';
    $sql_update[] = 'workflowuser="'.$db->rdbms_escape_string ($workflowuser).'"';

    // update
    $sql = 'UPDATE object SET ';
    $sql .= implode (", ", $sql_update);
    $sql .= ' WHERE id='.intval($container_id);

    $errcode = "50079";
    $db->rdbms_query ($sql, $errcode, $mgmt_config['today'], 'update');

    // save log
    savelog ($db->rdbms_geterror());
    $db->rdbms_close();

    return true;
  } 
  else return false;
}

// ----------------------------------------------- get workflow status -------------------------------------------------
// function: rdbms_getworkflow()
// input: container ID [integer], workflow date [datetime], workflow status [string], workflow user name [string]
// output: true/false
// requires: config.inc.php

// description:
// This function saves the status of the workflow for an object.
// Use *Leave* as input if a value should not be changed. 

function rdbms_getworkflow ($container_id)
{
  global $mgmt_config;

  if (is_file ($mgmt_config['abs_path_cms']."workflow/frameset_workflow.php") && intval ($container_id) > 0)
  {
    $db = new hcms_db ($mgmt_config['dbconnect'], $mgmt_config['dbhost'], $mgmt_config['dbuser'], $mgmt_config['dbpasswd'], $mgmt_config['dbname'], $mgmt_config['dbcharset']);    

    // select
    $sql = 'SELECT workflowdate, workflowstatus, workflowuser FROM object WHERE id='.intval($container_id);

    $errcode = "50080";
    $done = $db->rdbms_query($sql, $errcode, $mgmt_config['today'], 'select');

    if ($done)
    {
      $result = array();

      // insert recipients
      while ($row = $db->rdbms_getresultrow ('select'))
      {
        if (!empty ($row['workflowstatus']) && strpos ($row['workflowstatus'], "/") > 0)
        {
          $result['workflowdate'] = $row['workflowdate'];
          $result['workflowstatus'] = $row['workflowstatus'];
          $result['workflowuser'] = $row['workflowuser'];

          break;
        }
      }
    }

    // save log
    savelog ($db->rdbms_geterror());
    $db->rdbms_close();

    if (!empty ($result) && is_array ($result)) return $result;
    else return false;
  } 
  else return false;
}

// ----------------------------------------------- get table information -------------------------------------------------
// function: rdbms_gettableinfo()
// input: SQL table name [string]
// output: result array / false on error
// requires: config.inc.php

// description:
// This function provides information of all table columns.

function rdbms_gettableinfo ($table)
{
  global $mgmt_config;
  
  if ($table != "")
  {
    $db = new hcms_db($mgmt_config['dbconnect'], $mgmt_config['dbhost'], $mgmt_config['dbuser'], $mgmt_config['dbpasswd'], $mgmt_config['dbname'], $mgmt_config['dbcharset']);

    $table = $db->rdbms_escape_string ($table);
    $sql = 'SHOW COLUMNS FROM `'.$table.'`';

    $errcode = "50099";
    $done = $db->rdbms_query($sql, $errcode, $mgmt_config['today'], 'select');

    if ($done)
    { 
      $info = array();
      $i = 0;

      while ($row = $db->rdbms_getresultrow ('select'))
      {
        if (!empty ($row['Field']))
        {
          $info[$i]['name'] = $row['Field'];
          $info[$i]['type'] = $row['Type'];
          $info[$i]['key'] = $row['Key'];
          $info[$i]['default'] = $row['Default'];
          $info[$i]['extra'] = $row['Extra'];
          
          $i++;
        }
      }
    } 

    // save log
    savelog ($db->rdbms_geterror());    
    $db->rdbms_close();

    if (!empty ($info) && is_array (@$info)) return $info;
    else return false;
  }
  else return false;
}

// ----------------------------------------------- optimize database -------------------------------------------------
// function: rdbms_optimizedatabase()
// input: %
// output: true / false
// requires: config.inc.php

// description:
// This function removes dead datasets from the database and optimizes the tables.
// It is recommended to create a backup of the database before the execution of this function.

function rdbms_optimizedatabase ()
{
  global $mgmt_config;

  $inherit_db = inherit_db_read ();

  if (!empty ($inherit_db) && sizeof ($inherit_db) > 0)
  {
    $db = new hcms_db($mgmt_config['dbconnect'], $mgmt_config['dbhost'], $mgmt_config['dbuser'], $mgmt_config['dbpasswd'], $mgmt_config['dbname'], $mgmt_config['dbcharset']);

    $exclude = array();

    foreach ($inherit_db as $inherit_db_record)
    {
      if (!empty ($inherit_db_record['parent']))
      {
        $exclude[] = 'object.objectpath NOT LIKE "*page*/'.$db->rdbms_escape_string (trim ($inherit_db_record['parent'])).'/%" AND object.objectpath NOT LIKE "*comp*/'.$db->rdbms_escape_string (trim ($inherit_db_record['parent'])).'/%"';
      }              
    }

    // remove dead datasets
    if (sizeof ($exclude) > 0)
    {
      $sql = 'DELETE object, textnodes, keywords_container, accesslink, task, taxonomy, dailystat, queue, notify 
      FROM object 
      LEFT JOIN textnodes on object.id=textnodes.id 
      LEFT JOIN keywords_container on object.id=keywords_container.id 
      LEFT JOIN accesslink on object.object_id=accesslink.object_id 
      LEFT JOIN task on object.object_id=task.object_id 
      LEFT JOIN taxonomy on object.id=taxonomy.id 
      LEFT JOIN dailystat on object.id=dailystat.id 
      LEFT JOIN queue on object.object_id=queue.object_id 
      LEFT JOIN notify on object.object_id=notify.object_id  
      WHERE '.implode (" AND ", $exclude);

      $errcode = "50911";
      $done = $db->rdbms_query($sql, $errcode, $mgmt_config['today'], 'delete');
    }

    // optimize database tables
    $sql = 'OPTIMIZE TABLE object';

    $errcode = "50912";
    $done = $db->rdbms_query($sql, $errcode, $mgmt_config['today'], 'optimize_object');

    $sql = 'OPTIMIZE TABLE textnodes';

    $errcode = "50913";
    $done = $db->rdbms_query($sql, $errcode, $mgmt_config['today'], 'optimize_textnodes');

    $sql = 'OPTIMIZE TABLE keywords';

    $errcode = "50914";
    $done = $db->rdbms_query($sql, $errcode, $mgmt_config['today'], 'optimize_keywords');
    
    $sql = 'OPTIMIZE TABLE taxonomy';

    $errcode = "50915";
    $done = $db->rdbms_query($sql, $errcode, $mgmt_config['today'], 'optimize_taxonomy');
    
    // save log
    savelog ($db->rdbms_geterror());    
    $db->rdbms_close();

    savelog ("database has been optimized");

    return true;
  }
  else return false;
}

// -----------------------------------------------  external SQL query-------------------------------------------------
// function: rdbms_gettableinfo()
// input: SQL statement [string], cancat by column/attribute name [string] (optional)
// output: result array / false on error
// requires: config.inc.php

// description:
// This function executes a SQL statement and returns the result as array.

function rdbms_externalquery ($sql, $concat_by="")
{
  global $mgmt_config;

  if ($sql != "")
  {
    // anaylze SQL query regarding write operations
    $check_query = sql_clean_functions ($sql);
    
    if (!empty ($check_query['result']))
    {
      $db = new hcms_db($mgmt_config['dbconnect'], $mgmt_config['dbhost'], $mgmt_config['dbuser'], $mgmt_config['dbpasswd'], $mgmt_config['dbname'], $mgmt_config['dbcharset']);

      // correct %comp% and %page% for query
      $sql = str_replace (array("%comp%/", "%page%/"), array("*comp*/", "*page*/"), $sql);

      $errcode = "50101";
      $done = $db->rdbms_query($sql, $errcode, $mgmt_config['today'], 'select');

      if ($done)
      {
        $result = array();
        $i = 0;

        while ($row = $db->rdbms_getresultrow ('select'))
        {
          // transform objectpath in different name variants
          if (!empty ($row['objectpath'])) $row['objectpath'] = str_replace (array("*comp*/","*page*/"), array("%comp%/","%page%/"), $row['objectpath']);
          if (!empty ($row['Objectpath'])) $row['Objectpath'] = str_replace (array("*comp*/","*page*/"), array("%comp%/","%page%/"), $row['Objectpath']);
          if (!empty ($row['Location'])) $row['Location'] = str_replace (array("*comp*/","*page*/"), array("%comp%/","%page%/"), $row['Location']);
        
          if ($concat_by != "" && !empty ($row[$concat_by]))
          {
            $i = $row[$concat_by];

            foreach ($row as $key=>$value)
            {
              if (!isset ($result[$i])) $result[$i] = array();

              // if result item is not set
              if (!isset ($result[$i][$key])) $result[$i][$key] = $value;
              // if value is number
              elseif (!empty ($result[$i][$key]) && is_numeric ($value)) $result[$i][$key] += $value;
              // if value is string
              elseif (empty ($result[$i][$key]) && $value != "") $result[$i][$key] .= $value;
            }
          }
          else
          {
            $result[$i] = $row;
            $i++;
          }
        }
      }

      // save log
      savelog ($db->rdbms_geterror());    
      $db->rdbms_close();

      if (!empty ($result) && is_array ($result)) return $result;
      else return false;
    }
    else return false;
  }
  else return false;
}
?>