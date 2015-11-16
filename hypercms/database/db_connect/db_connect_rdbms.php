<?php
// ================================================ db connect ================================================
// this file handles the data access and storage for relational database management systems. 
  
// ============================================ database functions ============================================
// the following input parameters are passed to the functions:

// $container_id: ID of the content container [integer]
// $object: converted path to the object [string]
// $template: name of the used template [string]
// $container: name of the content container: $container_id [string] (is unique inside hyperCMS over all sites)
// $text_array: content inside the XML-text-nodes of the content container 
// $user: name of the user who created the container [string]

// Class that manages the database access
class hcms_db
{
  private static $_ERR_TYPE = "Not supported type";
  
  // @var mysqli
  private $_db = NULL;
  
  private $_error = array();
  
  /**
   *
   * @var mysqli_result
   */
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
        break;
      case 'odbc':
        $this->_db = odbc_connect ($db, $user, $pass, SQL_CUR_USE_ODBC);
        if($this->_db == false) die ('Could not connect to odbc');
        break;
      default:
        die (self::$_ERR_TYPE.': '.$type);
    }
  }
  
  // Escapes the String according to the used dbtype
  // $string String to be escaped
  // Returns Escaped String
  public function escape_string ($string)
  {
    if ($this->_isMySqli())
    {
      return $this->_db->escape_string($string);
    }
    elseif ($this->_isODBC())
    {
      return odbc_escape_string ($this->_db, $string);
    }
    else
    {
      $this->_typeError();
    }
  }
  
  // Send a query to the database
  // $sql Statement to be sent to the server
  // $errCode Code for the Error which is inserted into the log
  // $date Date of the Query
  // $num Number where the result shall be stored. Needed for getRowCount and getResultRow
  // Returns true on success, false on failure
  public function query ($sql, $errCode, $date, $num=1)
  {
    global $mgmt_config;
    
    if ($this->_isMySqli ())
    {
      // log
      if ($mgmt_config['rdbms_log'])
      {
        $time_start = time();
        $log = array();
        $log[] = $mgmt_config['today']."|QUERY: ".$sql;
      }
    
      $result = $this->_db->query ($sql);
      
      // log
      if ($mgmt_config['rdbms_log'])
      {    
        $time_stop = time();
        $time = $time_stop - $time_start;
        $log[] = $mgmt_config['today']."|EXEC-TIME: ".$time." sec";
        savelog ($log, "sql");
      }   
      
      if ($result == false)
      {
        $this->_error[] = $date."|db_connect_rdbms.php|error|$errCode|".$this->_db->error;
        $this->_result[$num] = false;
        return false;
      }
      else
      {
        $this->_result[$num] = $result;
        return true;
      }
    }
    elseif ($this->_isODBC ())
    {
      $result = odbc_exec ($this->_db, $sql);
      
      if ($result == false)
      {
        $this->_error[] = $date."|db_connect_rdbms.php|error|$errCode|ODBC Error Number: ".odbc_error();
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
  public function getError ()
  {
    return $this->_error;
  }
  
  // Returns the number of rows from the result stored under $num
  // $num the number defined in the $query call
  public function getNumRows ($num=1)
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
  
  // Closes the database connection and frees all results
  public function close()
  {
    if($this->_isMySqli ())
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
  public function getResultRow ($num=1, $rowNumber=NULL)
  {
    if ($this->_result[$num] == false)
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
// alternative to mysql_real_escape_string (PHP odbc_prepare would be optimal)
function odbc_escape_string ($connection, $value)
{
  if ($value != "")
  {
    $value = addslashes ($value);
    return $value;
  }
  else return "";
}

// ------------------------------------------------ convert dbcharset ------------------------------------------------
// some conversions from mySQL charset names to PHP charset names
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
function rdbms_createobject ($container_id, $object, $template, $container, $user)
{
  global $mgmt_config;

  if ($container_id != "" && $object != "" && $template != "" && (substr_count ($object, "%page%") > 0 || substr_count ($object, "%comp%") > 0))
  {
    // correct object name 
    if (strtolower (@strrchr ($object, ".")) == ".off") $object = @substr ($object, 0, -4);
      
    $db = new hcms_db($mgmt_config['dbconnect'], $mgmt_config['dbhost'], $mgmt_config['dbuser'], $mgmt_config['dbpasswd'], $mgmt_config['dbname'], $mgmt_config['dbcharset']);
        
    $container_id = $db->escape_string($container_id);
    $object = $db->escape_string($object);
    $template = $db->escape_string($template);
    if ($container != "") $container = $db->escape_string($container);
    if ($user != "") $user = $db->escape_string($user);
        
    $date = date ("Y-m-d H:i:s", time());
    $hash = createuniquetoken ();
    $object = str_replace ("%", "*", $object);
    if (strtolower (strrchr ($object, ".")) == ".off") $object = substr ($object, 0, -4);
    
    // check for existing object with same path (duplicate due to possible database error)
    $container_id_duplicate = rdbms_getobject_id ($object);
    
    if ($container_id_duplicate != "")
    {
      $result_delete = rdbms_deleteobject ($object);
      
      if ($result_delete)
      {
        $errcode = "20911";
        $error[] = $mgmt_config['today']."|db_connect_rdbms.inc.php|error|$errcode|duplicate object $object (ID: $container_id_duplicate) already existed in database and has been deleted";
      
        savelog (@$error);
      }
    }
    
    // insert values in table object
    $sql = 'INSERT INTO object (id, hash, objectpath, template) ';
    $sql .= 'VALUES ('.intval ($container_id).', "'.$hash.'", "'.$object.'", "'.$template.'")';
    
    $errcode = "50001";
    $db->query ($sql, $errcode, $mgmt_config['today']);

    // insert filetype in table media
    $file_ext = strrchr ($object, ".");
    $filetype = getfiletype ($file_ext);
        
    // insert values in table container
    if (!empty ($container) && !empty ($user) && !empty ($_SESSION['hcms_temp_latitude']) && is_numeric ($_SESSION['hcms_temp_latitude']) && !empty ($_SESSION['hcms_temp_longitude']) && is_numeric ($_SESSION['hcms_temp_longitude']))
    {
      $sql = 'INSERT INTO container (id, container, createdate, date, latitude, longitude, user) ';
      $sql .= 'VALUES ('.intval ($container_id).', "'.$container.'", "'.$date.'", "'.$date.'", '.floatval($_SESSION['hcms_temp_latitude']).', '.floatval($_SESSION['hcms_temp_longitude']).', "'.$user.'")';
    }
    elseif (!empty ($container) && !empty ($user))
    {
      $sql = 'INSERT INTO container (id, container, date, user) ';
      $sql .= 'VALUES ('.intval ($container_id).', "'.$container.'", "'.$date.'", "'.$user.'")';
    }
    elseif (!empty ($user))
    {
      $sql = 'UPDATE container SET user="'.$user.'", date="'.$date.'" ';
      $sql .= 'WHERE id='.intval ($container_id).'';
    }
    else
    {
      $sql = 'UPDATE container SET date="'.$date.'" ';
      $sql .= 'WHERE id='.intval ($container_id).'';
    }
    
    $errcode = "50002";
    $db->query ($sql, $errcode, $mgmt_config['today']);      

    // save log
    savelog ($db->getError ());          
    $db->close();
   
    return true;
  }
  else return false;
}

// ----------------------------------------------- set content -------------------------------------------------
function rdbms_setcontent ($container_id, $text_array="", $user="")
{
  global $mgmt_config;
  
  if ($container_id != "")
  {
    $db = new hcms_db ($mgmt_config['dbconnect'], $mgmt_config['dbhost'], $mgmt_config['dbuser'], $mgmt_config['dbpasswd'], $mgmt_config['dbname'], $mgmt_config['dbcharset']);
    
    $container_id = $db->escape_string($container_id);
    if ($user != "") $user = $db->escape_string($user);
    
    $date = date ("Y-m-d H:i:s", time());
    
    // update container
    $sql_attr = null;
    if ($user != "") $sql_attr[0] = 'user="'.$user.'"';
    $sql_attr[1] = 'date="'.$date.'"';
    
    if (is_array ($sql_attr) && sizeof ($sql_attr) > 0)
    {
      $sql = 'UPDATE container SET ';
      $sql .= implode (", ", $sql_attr).' ';    
      $sql .= 'WHERE id='.intval ($container_id).'';
      
      $errcode = "50003";
      $db->query ($sql, $errcode, $mgmt_config['today'], 1);
    }
    
    // update text nodes
    if (is_array ($text_array) && sizeof ($text_array) > 0)
    {
      reset ($text_array);
      
      $i = 1;
      
      while (list ($key, $text) = each ($text_array))
      {
        $i++;
        if ($key != "") 
        {
          $sql = 'SELECT * FROM textnodes ';
          $sql .= 'WHERE id='.intval ($container_id).' AND text_id="'.$key.'"';
               
         $errcode = "50004";
         $done = $db->query ($sql, $errcode, $mgmt_config['today'], $i);

          if ($done)
          {
            $num_rows = $db->getNumRows ($i);          
          
            if ($num_rows > 0)
            {      
              $text = strip_tags ($text);
              $text = html_decode ($text, "UTF-8");

              $text = $db->escape_string($text);

              //query 
              $sql = 'UPDATE textnodes SET textcontent="'.$text.'" ';
              $sql .= 'WHERE id='.intval ($container_id).' AND text_id="'.$key.'"'; 

              $errcode = "50005";
              $db->query ($sql, $errcode, $mgmt_config['today'], ++$i);
            }
            elseif ($num_rows == 0)
            {
              $text = strip_tags ($text);
              $text = html_decode ($text, "UTF-8");

              $text = $db->escape_string ($text);  

              // query    
              $sql = 'INSERT INTO textnodes (id, text_id, textcontent) ';      
              $sql .= 'VALUES ('.intval ($container_id).', "'.$key.'", "'.$text.'")';  

              $errcode = "50006";
              $db->query ($sql, $errcode, $mgmt_config['today'], ++$i);
            }                 
          }
        }
      }
    }

    // save log
    savelog ($db->getError ());    
    $db->close();
    
    return true;
  }
  else return false;
} 

// ----------------------------------------------- set template -------------------------------------------------
function rdbms_settemplate ($object, $template)
{
  global $mgmt_config;
  
  if ($object != "" && $template != "" && (substr_count ($object, "%page%") > 0 || substr_count ($object, "%comp%") > 0))
  {    
    // correct object name 
    if (strtolower (@strrchr ($object, ".")) == ".off") $object = @substr ($object, 0, -4);
      
    $db = new hcms_db($mgmt_config['dbconnect'], $mgmt_config['dbhost'], $mgmt_config['dbuser'], $mgmt_config['dbpasswd'], $mgmt_config['dbname'], $mgmt_config['dbcharset']);
    
    $object = $db->escape_string ($object);
    $template = $db->escape_string ($template);
            
    $object = str_replace ("%", "*", $object);

    // update object
    $sql = 'UPDATE object SET template="'.$template.'" WHERE objectpath=_utf8"'.$object.'" COLLATE utf8_bin'; 
    
    $errcode = "50007";
    $db->query ($sql, $errcode, $mgmt_config['today']);

    // save log
    savelog ($db->getError ());    
    $db->close();
        
    return true;
  }
  else return false;
} 

// ----------------------------------------------- set media attributes -------------------------------------------------
function rdbms_setmedia ($id, $filesize="", $filetype="", $width="", $height="", $red="", $green="", $blue="", $colorkey="", $imagetype="", $md5_hash="")
{
  global $mgmt_config;
  
  if ($id != "" && ($filesize != "" || $filetype != "" || $width != "" || $height != "" || $red != "" || $green != "" || $blue != "" || $colorkey != "" || $imagetype != "" || $md5_hash != ""))
  {    
    
    $db = new hcms_db($mgmt_config['dbconnect'], $mgmt_config['dbhost'], $mgmt_config['dbuser'], $mgmt_config['dbpasswd'], $mgmt_config['dbname'], $mgmt_config['dbcharset']);
    
    if ($filesize != "") $filesize = $db->escape_string ($filesize);
    if ($width != "") $width = $db->escape_string ($width);
    if ($height != "") $height = $db->escape_string ($height);
    if ($red != "") $red = $db->escape_string ($red);
    if ($green != "") $green = $db->escape_string ($green);
    if ($blue != "") $blue = $db->escape_string ($blue);
    if ($colorkey != "") $colorkey = $db->escape_string ($colorkey);
    if ($imagetype != "") $imagetype = $db->escape_string ($imagetype);
    if ($md5_hash != "") $md5_hash = $db->escape_string ($md5_hash);
        
    // check for existing record
    $sql = 'SELECT id FROM media WHERE id='.intval($id); 
    
    $errcode = "50008";
    $done = $db->query($sql, $errcode, $mgmt_config['today'], 'select');

    if ($done)
    {
      $num_rows = $db->getNumRows('select');

      // insert media attributes
      if ($num_rows == 0)
      {
        $sql = 'INSERT INTO media (id, filesize, filetype, width, height, red, green, blue, colorkey, imagetype, md5_hash) '; 
        $sql .= 'VALUES ('.intval($id).','.intval($filesize).',"'.$filetype.'",'.intval($width).','.intval($height).','.intval($red).','.intval($green).','.intval($blue).',"'.$colorkey.'","'.$imagetype.'","'.$md5_hash.'")';      
      }
      // update media attributes
      else
      {
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

        if (sizeof ($sql_update) > 0)
        {
          $sql = 'UPDATE media SET ';
          $sql .= implode (", ", $sql_update);
          $sql .= ' WHERE id='.intval($id);
        }
      }

      $errcode = "50009";
      $db->query ($sql, $errcode, $mgmt_config['today'], 'update');
    }

    // save log
    savelog ($db->getError ());    
    $db->close();
        
    return true;
  }
  else return false;
} 

// ------------------------------------------------ get media attributes -------------------------------------------------
function rdbms_getmedia ($container_id, $extended=false)
{
  global $mgmt_config;

  if ($container_id != "")
  {
    $db = new hcms_db($mgmt_config['dbconnect'], $mgmt_config['dbhost'], $mgmt_config['dbuser'], $mgmt_config['dbpasswd'], $mgmt_config['dbname'], $mgmt_config['dbcharset']);
    
    // clean input
    $container_id = $db->escape_string ($container_id);  
    
    // get media info
    if ($extended == true) $sql = 'SELECT med.*, cnt.createdate, cnt.date, cnt.latitude, cnt.longitude, cnt.user FROM media AS med, container AS cnt WHERE med.id=cnt.id AND med.id='.intval($container_id).'';   
    else $sql = 'SELECT * FROM media WHERE id='.intval($container_id).'';   

    $errcode = "50067";
    $done = $db->query ($sql, $errcode, $mgmt_config['today']);
    
    if ($done && $row = $db->getResultRow ())
    {
      $media = $row;   
    }

    // save log
    savelog ($db->getError());    
    $db->close();      
         
    if (!empty ($media) && is_array ($media)) return $media;
    else return false;
  }
  else return false;
}

// ------------------------------------------------ get duplicate file -------------------------------------------------
function rdbms_getduplicate_file ($site, $md5_hash)
{
  global $mgmt_config;

  if ($site != "" && $md5_hash != "")
  {
    $db = new hcms_db($mgmt_config['dbconnect'], $mgmt_config['dbhost'], $mgmt_config['dbuser'], $mgmt_config['dbpasswd'], $mgmt_config['dbname'], $mgmt_config['dbcharset']);
    
    // clean input
    $md5_hash = $db->escape_string ($md5_hash);
    $site = $db->escape_string ($site);
    
    // get media info
    $sql = 'SELECT * FROM media INNER JOIN object ON object.id=media.id WHERE md5_hash="'.$md5_hash.'" AND objectpath LIKE "*comp*/'.$site.'/%"';

    $errcode = "50067";
    $done = $db->query ($sql, $errcode, $mgmt_config['today'], 'main');
    
    $media = array();
    
    if ($done)
    {
      while ($row = $db->getResultRow ('main'))
      {
        $row['objectpath'] = str_replace ("*", "%", $row['objectpath']);
        $media[] = $row;
      }
    }
    
    // save log
    savelog ($db->getError());    
    $db->close();      
         
    if (is_array ($media) && !empty($media)) return $media;
    else return false;
  }
  else return false;
}

// ----------------------------------------------- rename object -------------------------------------------------
function rdbms_renameobject ($object_old, $object_new)
{
  global $mgmt_config;
  
  if ($object_old != "" && $object_new != "" && (substr_count ($object_old, "%page%") > 0 || substr_count ($object_old, "%comp%") > 0) && (substr_count ($object_new, "%page%") > 0 || substr_count ($object_new, "%comp%") > 0))
  {  
    // correct object names
    if (strtolower (strrchr ($object_old, ".")) == ".off") $object_old = substr ($object_old, 0, -4);
    if (strtolower (strrchr ($object_new, ".")) == ".off") $object_new = substr ($object_new, 0, -4);
    
    $db = new hcms_db($mgmt_config['dbconnect'], $mgmt_config['dbhost'], $mgmt_config['dbuser'], $mgmt_config['dbpasswd'], $mgmt_config['dbname'], $mgmt_config['dbcharset']);
    
    // remove seperator
    $object_old = str_replace ("|", "", $object_old);
    $object_new = str_replace ("|", "", $object_new); 
    
    $object_old = $db->escape_string ($object_old);
    $object_new = $db->escape_string ($object_new);
       
    // replace %
    $object_old = str_replace ("%", "*", $object_old);
    $object_new = str_replace ("%", "*", $object_new);
    
    // query
    $sql = 'SELECT object_id, id, objectpath FROM object '; 
    $sql .= 'WHERE objectpath LIKE _utf8"'.$object_old.'%" COLLATE utf8_bin';
    
    $errcode = "50010";
    $done = $db->query($sql, $errcode, $mgmt_config['today'], 'select');

    $i = 1;
    
    if ($done)
    {
      while ($row = $db->getResultRow ('select'))
      {
        $object_id = $row['object_id'];
        $container_id = $row['id'];
        $object = $row['objectpath'];
        $object = str_replace ($object_old, $object_new, $object);
        $fileext = strrchr ($object, ".");
        $filetype = getfiletype ($fileext);

        // update object 
        $sql = 'UPDATE object SET objectpath="'.$object.'" WHERE object_id='.$object_id;
        
        $errcode = "50011";
        $db->query ($sql, $errcode, $mgmt_config['today'], $i++);        
        
        // update media file-type
        if ($filetype != "")
        {
          $sql = 'UPDATE media SET filetype="'.$filetype.'" WHERE id='.$container_id;
  
          $errcode = "50012";
          $db->query ($sql, $errcode, $mgmt_config['today'], $i++);
        }
      }
    }
    
    // save log
    savelog ($db->getError ());    
    $db->close();
         
    return true;
  }
  else return false;
} 

// ----------------------------------------------- delete object ------------------------------------------------- 
function rdbms_deleteobject ($object, $object_id="")
{
  global $mgmt_config;

  if (($object != "" && (substr_count ($object, "%page%") > 0 || substr_count ($object, "%comp%") > 0)) || $object_id > 0)
  {
    // correct object name 
    if (strtolower (@strrchr ($object, ".")) == ".off") $object = @substr ($object, 0, -4);
    
    $db = new hcms_db($mgmt_config['dbconnect'], $mgmt_config['dbhost'], $mgmt_config['dbuser'], $mgmt_config['dbpasswd'], $mgmt_config['dbname'], $mgmt_config['dbcharset']);
    
    if ($object != "")
    {
      $object = $db->escape_string ($object);
    
      // replace %
      $object = str_replace ("%", "*", $object);
    }
    
    // query
    $sql = 'SELECT id FROM object ';
    
    if ($object != "") $sql .= 'WHERE objectpath=_utf8"'.$object.'" COLLATE utf8_bin';
    elseif ($object_id > 0) $sql .= 'WHERE object_id='.intval ($object_id).'';
       
    $errcode = "50012";
    $done = $db->query($sql, $errcode, $mgmt_config['today'], 'select1');
    
    if ($done)
    {
      $row = $db->getResultRow('select1');
    
      if ($row)
      {
        $container_id = $row['id']; 

        $sql = 'SELECT object_id FROM object ';
        $sql .= 'WHERE id='.$container_id.'';

        $errcode = "50013";
        $done = $db->query($sql, $errcode, $mgmt_config['today'], 'select2');
        
        if ($done)
        {
          $row_id = $db->getResultRow ('select2');
          $num_rows = $db->getNumRows ('select2');
        }
        
        // delete all entries for this id since no connected objects exists
        if ($row_id && $num_rows == 1)
        {
          // delete object
          $sql = 'DELETE FROM object WHERE id='.$container_id;

          $errcode = "50014";
          $done = $db->query ($sql, $errcode, $mgmt_config['today'], 'delete1');

          // delete container
          $sql = 'DELETE FROM container WHERE id='.$container_id;   

          $errcode = "50014";
          $done = $db->query ($sql, $errcode, $mgmt_config['today'], 'delete2');

          // delete textnodes  
          $sql = 'DELETE FROM textnodes WHERE id='.$container_id;

          $errcode = "50015";
          $done = $db->query ($sql, $errcode, $mgmt_config['today'], 'delete3');

          // delete media attributes  
          $sql = 'DELETE FROM media WHERE id='.$container_id;

          $errcode = "50016";
          $done = $db->query ($sql, $errcode, $mgmt_config['today'], 'delete4');

          // delete dailytstat 
          $sql = 'DELETE FROM dailystat WHERE id='.$container_id;

          $errcode = "50017";
          $done = $db->query ($sql, $errcode, $mgmt_config['today'], 'delete5');        

          // delete queue
          $sql = 'DELETE FROM queue WHERE object_id='.$row_id['object_id'];

          $errcode = "50018";
          $done = $db->query ($sql, $errcode, $mgmt_config['today'], 'delete6');
          
          // delete accesslink
          $sql = 'DELETE FROM accesslink WHERE object_id='.$row_id['object_id'];

          $errcode = "50019";
          $done = $db->query ($sql, $errcode, $mgmt_config['today'], 'delete7');          
        }
        // delete only the object reference and queue entry
        elseif ($row_id && $num_rows > 1)
        {
          $sql = 'DELETE FROM object WHERE objectpath=_utf8"'.$object.'" COLLATE utf8_bin';

          $errcode = "50020";
          $done = $db->query ($sql, $errcode, $mgmt_config['today'], 'delete7');
        }

        // delete queue
        $sql = 'DELETE FROM queue WHERE object_id='.$row_id['object_id'];   

        $errcode = "50021";
        $done = $db->query ($sql, $errcode, $mgmt_config['today'], 'delete8');
        
        // delete notification
        $sql = 'DELETE FROM notify WHERE object_id='.$row_id['object_id'];   

        $errcode = "50022";
        $done = $db->query ($sql, $errcode, $mgmt_config['today'], 'delete9');
      }
    }

    // save log
    savelog ($db->getError ());    
    $db->close();
         
    return true;
  }
  else return false;
}

// ----------------------------------------------- delete content -------------------------------------------------
function rdbms_deletecontent ($container_id, $text_id, $user)
{
  global $mgmt_config;
  
  if ($container_id != "" && $text_id != "")
  {   
    $db = new hcms_db($mgmt_config['dbconnect'], $mgmt_config['dbhost'], $mgmt_config['dbuser'], $mgmt_config['dbpasswd'], $mgmt_config['dbname'], $mgmt_config['dbcharset']);
    
    $container_id = $db->escape_string ($container_id);
    $text_id = $db->escape_string ($text_id);
    if ($user != "") $user = $db->escape_string ($user);
    
    // query
    $sql = 'DELETE FROM textnodes WHERE id='.$container_id.' AND text_id="'.$text_id.'"';
       
    $errcode = "50021";
    $db->query ($sql, $errcode, $mgmt_config['today']);
    
    // save log
    savelog ($db->getError ());    
    $db->close();
        
    return true;
  }
  else return false;
}

// ----------------------------------------------- search content ------------------------------------------------- 
function rdbms_searchcontent ($folderpath, $excludepath, $object_type, $date_from, $date_to, $template, $expression_array, $expression_filename, $filesize, $imagewidth, $imageheight, $imagecolor, $imagetype, $geo_border_sw, $geo_border_ne, $maxhits=1000, $count=false)
{
  // user will be provided as global for search expression logging
  global $mgmt_config, $user;

  // set object_type if the search is image or video related
  if (!is_array ($object_type) && (!empty ($imagewidth) || !empty ($imageheight) || !empty ($imagecolor) || !empty ($imagetype) || !empty ($filesize)))
  {
    if (!is_array ($object_type)) $object_type = array();
    array_push ($object_type, "image", "video");
  }
  
  if (!empty ($folderpath) || is_array ($object_type) || !empty ($date_from) || !empty ($date_to) || !empty ($template) || is_array ($expression_array) || !empty ($expression_filename) || !empty ($filesize) || !empty ($imagewidth) || !empty ($imageheight) || !empty ($imagecolor) || !empty ($imagetype))
  {
    $db = new hcms_db($mgmt_config['dbconnect'], $mgmt_config['dbhost'], $mgmt_config['dbuser'], $mgmt_config['dbpasswd'], $mgmt_config['dbname'], $mgmt_config['dbcharset']);
    
    if (is_array ($object_type)) foreach ($object_type as &$value) $value = $db->escape_string ($value);
    if ($date_from != "") $date_from = $db->escape_string ($date_from);
    if ($date_to != "") $date_to = $db->escape_string ($date_to);
    if ($template != "") $template = $db->escape_string ($template);
    if ($maxhits != "")
    {
      if (strpos ($maxhits, ",") > 0)
      {
        list ($starthits, $endhits) = explode (",", $maxhits);
        $starthits = $db->escape_string (trim ($starthits));
        $endhits = $db->escape_string (trim ($endhits));
      }
      else $maxhits = $db->escape_string ($maxhits);
    }
    
    // AND/OR operator for the search in texnodes 
    if (isset ($mgmt_config['search_operator']) && (strtoupper ($mgmt_config['search_operator']) == "AND" || strtoupper ($mgmt_config['search_operator']) == "OR"))
    {
      $operator = strtoupper ($mgmt_config['search_operator']);
    }
    else $operator = "AND";
    
    // folder path => consider folderpath only when there is no filenamecheck
    if (!empty ($folderpath))
    {
      if (!is_array ($folderpath) && $folderpath != "") $folderpath = array ($folderpath);      
      $sql_puffer = array();
      
      foreach ($folderpath as $path)
      {
        if ($path != "")
        {
          //escape characters depending on dbtype
          $path = $db->escape_string ($path);
          // replace %
          $path = str_replace ("%", "*", $path);
          // where clause for folderpath
          $sql_puffer[] = 'obj.objectpath LIKE _utf8"'.$path.'%" COLLATE utf8_bin';
        }
      }
      
      if (is_array ($sql_puffer) && sizeof ($sql_puffer) > 0) $sql_where['folderpath'] = '('.implode (" OR ", $sql_puffer).')';
    }
    
    // excludepath path
    if (!empty ($excludepath))
    {
      if (!is_array ($excludepath) && $excludepath != "") $excludepath = array ($excludepath);
      $sql_puffer = array();
      
      foreach ($excludepath as $path)
      {
        if ($path != "")
        {
          // explicitly exclude folders from result
          if ($path == "/.folder")
          {
            // where clause for excludepath
            $sql_puffer[] = 'obj.objectpath NOT LIKE _utf8"%'.$path.'" COLLATE utf8_bin';
          }
          else
          {
            //escape characters depending on dbtype
            $path = $db->escape_string ($path);
            // replace %
            $path = str_replace ("%", "*", $path);
            // where clause for excludepath
            $sql_puffer[] = 'obj.objectpath NOT LIKE _utf8"'.$path.'%" COLLATE utf8_bin';
          }
        }
      }
      
      if (is_array ($sql_puffer) && sizeof ($sql_puffer) > 0) $sql_where['excludepath'] = '('.implode (" AND ", $sql_puffer).')';
    }
    
    // add file name search if expression array is of size 1
    if (empty ($expression_filename) && is_array ($expression_array) && sizeof ($expression_array) == 1 && !empty ($expression_array[0])) 
    {
      $expression_filename = $expression_array[0];
    }
    
    // define query for 
    
    // object type (only if less than 5 of total 5 arguments (component, audio, video, document, image), otherwise we look for all object types/formats).
    // for media reference the search can also include binary, flash, compressed and text.
    if (is_array ($object_type) && sizeof ($object_type) > 0 && sizeof ($object_type) < 5)
    {
      // add media table
      $sql_table['media'] = "";
      $sql_where['format'] = "";
      $sql_where['object'] = "";
      
      foreach ($object_type as $search_type)
      {
        if ($search_type == "page" || $search_type == "comp") 
        {
          if ($sql_where['object'] != "") $sql_where['object'] .= " OR ";
          $sql_where['object'] .= 'obj.template LIKE "%.'.$search_type.'.tpl"';
        }

        // file-type (audio, document, text, image, video, compressed, flash, binary)
        if (in_array ($search_type, array("audio","document","text","image","video","compressed","flash","binary"))) 
        {
          if (!empty ($sql_where['format'])) $sql_where['format'] .= " OR ";
          $sql_where['format'] .= 'med.filetype="'.$search_type.'"';
        }
      }

      // add meta as object type if formats are set
      if (!empty ($sql_where['format']))
      {
        if (!empty ($sql_where['object'])) $sql_where['object'] .= " OR ";
        $sql_where['object'] .= 'obj.template LIKE "%.meta.tpl"';
      }
      
      // add () for OR operators
      if (!empty ($sql_where['object'])) $sql_where['object'] = "(".$sql_where['object'].")";
      else unset ($sql_where['object']);
      
      if (!empty ($sql_where['format'])) $sql_where['format'] = "(".$sql_where['format'].")";
      else unset ($sql_where['format']);
      
      // join media table
      if (!empty ($sql_where['format'])) $sql_table['media'] = 'LEFT JOIN media AS med on obj.id=med.id';
    }     
    
    // file name
    if (!empty ($expression_filename))
    {
      $expression_filename = str_replace ("*", "-hcms_A-", $expression_filename); 
      $expression_filename = str_replace ("?", "-hcms_Q-", $expression_filename); 
      $expression_filename = specialchr_encode ($expression_filename); 
      $expression_filename = str_replace ("-hcms_A-", "*", $expression_filename); 
      $expression_filename = str_replace ("-hcms_Q-", "?", $expression_filename);
       
      $expression_filename_conv = $expression_filename;
      $expression_filename_conv = str_replace ("*", "%", $expression_filename_conv);
      $expression_filename_conv = str_replace ("?", "_", $expression_filename_conv);      
      if (substr_count ($expression_filename_conv, "%") == 0) $expression_filename_conv = "%".$expression_filename_conv."%";
      
      $expression_filename_conv = $db->escape_string ($expression_filename_conv);
      
       // folder path
      if (!empty ($folderpath))
      {
        if (!is_array ($folderpath )) $folderpath = array ($folderpath);
        
        $sql_puffer = array();
        
        foreach ($folderpath as $path)
        {
          //escape characters depending on dbtype
          $path = $db->escape_string ($path);
          // replace %
          $path = str_replace ("%", "*", $path);
          // where clause for folderpath
          if (substr ($expression_filename_conv, 0, 1) != "%") $folderpath_conv = $path."%";
          else $folderpath_conv = $path;
          
          $sql_puffer[] = 'obj.objectpath LIKE _utf8"'.$folderpath_conv.$expression_filename_conv.'"';
        }
        
        if (is_array ($sql_puffer) && sizeof ($sql_puffer) > 0) $sql_where['filename'] = '('.implode (" OR ", $sql_puffer).')';
      }
    }   
    
    // dates and geo locatiojn (add table container)
    if ((!empty ($date_from) || !empty ($date_to)) || (!empty ($geo_border_sw) && !empty ($geo_border_ne)))
    {
      $sql_table['container'] = "LEFT JOIN container AS cnt ON obj.id=cnt.id";
      
      // dates
      if ($date_from != "") $sql_where['datefrom'] = 'DATE(cnt.date)>="'.$date_from.'"';
      if ($date_to != "") $sql_where['dateto'] = 'DATE(cnt.date)<="'.$date_to.'"';
      
      // geo location
      if (!empty ($geo_border_sw) && !empty ($geo_border_ne))
      {
        if (!empty ($geo_border_sw))
        {
          $geo_border_sw = str_replace (array("(",")"), "", $geo_border_sw);
          list ($latitude, $longitude) = explode (",", $geo_border_sw);
          
          if (is_numeric ($latitude) && is_numeric ($longitude)) $sql_where['geo_border_sw'] = 'cnt.latitude>='.trim($latitude).' AND cnt.longitude>='.trim($longitude);
        }
        
        if (!empty ($geo_border_ne))
        {
          $geo_border_ne = str_replace (array("(",")"), "", $geo_border_ne);
          list ($latitude, $longitude) = explode (",", $geo_border_ne);
          
          if (is_numeric ($latitude) && is_numeric ($longitude)) $sql_where['geo_border_ne'] = 'cnt.latitude<='.trim($latitude).' AND cnt.longitude<='.trim($longitude);
        }
      }
    }

    // template
    if (!empty ($template))
    {
      $sql_where['template'] = 'obj.template="'.$template.'"';
    }
    
    // search expression
    $sql_table['textnodes'] = "";
    $sql_expr_advanced = array();
    $sql_expr_general = "";

    if (is_array ($expression_array) && sizeof ($expression_array) > 0)
    {
      $i = 1;
      reset ($expression_array);
      $expression_log = array();
      
      while (list ($key, $expression) = each ($expression_array))
      {
        // define search log entry
        if ($expression != "") $expression_log[] = $mgmt_config['today']."|".$user."|".$expression;
               
        // advanced text-id based search in textnodes
        if ($expression != "" && $key != "" && $key != "0")
        {        
          if ($i > 1)
          {
            $j = $i - 1;
            $sql_table['textnodes'] .= " LEFT JOIN textnodes AS tn".$i.' ON tn'.$j.'.id=tn'.$i.'.id';
          }
          
          $expression = str_replace ("%", '\%', $expression);
          $expression = str_replace ("_", '\_', $expression);          
          $expression = str_replace ("*", "%", $expression);
          $expression = str_replace ("?", "_", $expression);
          $expression_esc = htmlentities ($expression, ENT_QUOTES, convert_dbcharset ($mgmt_config['dbcharset']));
          $expression = $db->escape_string ($expression);

          if ($expression != $expression_esc) $sql_expr_advanced[$i] = '(tn'.$i.'.text_id="'.$key.'" AND (tn'.$i.'.textcontent LIKE _utf8"%'.$expression.'%" OR tn'.$i.'.textcontent LIKE _utf8"%'.$expression_esc.'%"))';
          else $sql_expr_advanced[$i] = '(tn'.$i.'.text_id="'.$key.'" AND tn'.$i.'.textcontent LIKE _utf8"%'.$expression.'%")';
          
          $i++;
        }
        // general search in all textnodes (only one search expression possible)
        elseif ($expression != "")
        {
          $expression = str_replace ("%", '\%', $expression);
          $expression = str_replace ("_", '\_', $expression);        
          $expression = str_replace ("*", "%", $expression);
          $expression = str_replace ("?", "_", $expression);
          $expression_esc = htmlentities ($expression, ENT_QUOTES, convert_dbcharset ($mgmt_config['dbcharset']));
          $expression = $db->escape_string ($expression);
           
          if ($expression != $expression_esc) $sql_expr_general = '(tn1.textcontent LIKE _utf8"%'.$expression.'%" OR tn1.textcontent LIKE _utf8"%'.$expression_esc.'%")';
          else $sql_expr_general = 'tn1.textcontent LIKE _utf8"%'.$expression.'%"';
          
          // add search in object names
          if (!empty ($sql_where['filename']))
          {
            $sql_where_filename = "(".$sql_expr_general." OR ".$sql_where['filename'].")";
            unset ($sql_where['filename']);
          }
          else $sql_where_filename = $sql_expr_general;
          
          $i++;
        } 
      }
      
      // save search expression in search expression log
      savelog ($expression_log, "search");
      
      // combine all text_id based search conditions using the operator (default is AND)
      if (isset ($sql_expr_advanced) && is_array ($sql_expr_advanced) && sizeof ($sql_expr_advanced) > 0) $sql_where_textnodes = "(".implode (" ".$operator." ", $sql_expr_advanced).")";
      
      // final SQL where statement for search in content and object names
      if (!empty ($sql_where_textnodes) && !empty ($sql_where_filename)) $sql_where['textnodes'] = "(".$sql_where_textnodes." AND ".$sql_where_filename.")";
      elseif (!empty ($sql_where_textnodes)) $sql_where['textnodes'] = $sql_where_textnodes;
      elseif (!empty ($sql_where_filename)) $sql_where['textnodes'] = $sql_where_filename;
    }
    
    // add table textnodes
    if (is_array ($sql_expr_advanced) || is_array ($sql_expr_general))
    {
      $sql_table['textnodes'] = "LEFT JOIN textnodes AS tn1 ON obj.id=tn1.id ".$sql_table['textnodes'];
    }
    
    // add table media
    if (isset ($object_type) && is_array ($object_type) && (in_array ("image", $object_type) || in_array ("video", $object_type)))
    {
      if (!empty ($filesize) || !empty ($imagewidth) || !empty ($imageheight) || (isset ($imagecolor) && is_array ($imagecolor)) || !empty ($imagetype))
      {      
        if (isset ($filesize) && $filesize > 0)
        {
          if (!empty ($sql_where['media'])) $sql_where['media'] .= ' AND ';
          else $sql_where['media'] = "";
          
          $sql_where['media'] .= 'med.filesize>='.intval($filesize);
        }
        
        // parameter imagewidth can be used as general image size parameter, only if height = ""
        // search for image_size (area)
        if (!empty ($imagewidth) && substr_count ($imagewidth, "-") == 1)
        {
          list ($imagewidth_min, $imagewidth_max) = explode ("-", $imagewidth);
          $sql_where['media'] .= (($sql_where['media'] == '') ? '' : ' AND ').'(med.width>='.intval($imagewidth_min).' OR med.height>='.intval($imagewidth_min).') AND (med.width<='.intval($imagewidth_max).' OR med.height<='.intval($imagewidth_max).')';
        }
        else
        {			
          //search for exact image width
          if (!empty ($imagewidth) && $imagewidth > 0)
          {
            if (!empty ($sql_where['media'])) $sql_where['media'] .= ' AND ';
            else $sql_where['media'] = "";
            
            $sql_where['media'] .= 'med.width='.intval($imagewidth);
          }
               
          // search for exact image height
          if (!empty ($imageheight) && $imageheight > 0)
          {
            if (!empty ($sql_where['media'])) $sql_where['media'] .= ' AND ';
            else $sql_where['media'] = "";
            
            $sql_where['media'] .= 'med.height='.intval($imageheight);
          }
        }
        
        if (isset ($imagecolor) && is_array ($imagecolor))
        {
          foreach ($imagecolor as $colorkey)
          {
            if (!empty ($sql_where['media'])) $sql_where['media'] .= ' AND ';
            else $sql_where['media'] = "";
            
            $sql_where['media'] .= 'INSTR(med.colorkey,"'.$colorkey.'")>0';
          }
        }
        
        if (!empty ($imagetype))
        {
          if (!empty ($sql_where['media'])) $sql_where['media'] .= ' AND ';
          else $sql_where['media'] = "";
          
          $sql_where['media'] .= 'med.imagetype="'.$imagetype.'"';
        }
      }
    }
    
    // build SQL statement
    $sql = 'SELECT DISTINCT obj.objectpath, obj.hash FROM object AS obj';
    if (isset ($sql_table) && is_array ($sql_table)) $sql .= ' '.implode (' ', $sql_table);
    $sql .= ' WHERE ';
    if (isset ($sql_where) && is_array ($sql_where)) $sql .= implode (' AND ', $sql_where);
    $sql .= ' ORDER BY obj.objectpath';
 
    if (isset ($starthits) && intval($starthits) >= 0 && isset ($endhits) && intval($endhits) > 0) $sql .= ' LIMIT '.intval($starthits).','.intval($endhits);
    elseif (isset ($maxhits) && intval($maxhits) > 0) $sql .= ' LIMIT 0,'.intval($maxhits);
    
    $errcode = "50022";
    $done = $db->query ($sql, $errcode, $mgmt_config['today']);

    if ($done)
    {    
      // for search after SQL-result in the file name
      if ($expression_filename != "")
      {
        $expression_filename = str_replace ("*", "", $expression_filename);
        $expression_filename = str_replace ("?", "", $expression_filename);
        $expression_filename = specialchr_encode ($expression_filename);        
      }
      
      while ($row = $db->getResultRow ())
      {
        if ($row['objectpath'] != "") $objectpath[$row['hash']] = str_replace ("*", "%", $row['objectpath']);
      }      
    }
    
    //count searchresults
    if (!empty ($count))
    {
      $sql = 'SELECT COUNT(DISTINCT obj.objectpath) as cnt FROM object AS obj';
      if (is_array ($sql_table)) $sql .= ' '.implode (" ", $sql_table);
      $sql .= ' WHERE ';
    
      if (isset ($sql_table) && is_array ($sql_where)) 
      {
        $sql .= implode (" AND ", $sql_where);
      }
      
      $errcode = "50022";
      $done = $db->query ($sql, $errcode, $mgmt_config['today']);

      if ($done && ($row = $db->getResultRow ()))
      {         
        if ($row['cnt'] != "") $objectpath['count'] = $row['cnt']; 
      }
    }

    // save log
    savelog ($db->getError ());    
    $db->close();
    
    if (isset ($objectpath) && is_array ($objectpath)) return $objectpath;
    else return false;
  }
  else return false;
}

// ----------------------------------------------- replace content ------------------------------------------------- 
function rdbms_replacecontent ($folderpath, $object_type, $date_from, $date_to, $search_expression, $replace_expression, $user="sys")
{
  global $mgmt_config;

  if ($folderpath != "" && $search_expression != "")
  {
    $db = new hcms_db ($mgmt_config['dbconnect'], $mgmt_config['dbhost'], $mgmt_config['dbuser'], $mgmt_config['dbpasswd'], $mgmt_config['dbname'], $mgmt_config['dbcharset']);
    
    $folderpath = $db->escape_string ($folderpath);
    if (is_array ($object_type)) foreach ($object_type as &$value) $value = $db->escape_string ($value);
    if ($date_from != "") $date_from = $db->escape_string ($date_from);
    if ($date_to != "") $date_to = $db->escape_string ($date_to);
    if ($user != "") $user = $db->escape_string ($user);
        
    // replace %
    $folderpath = str_replace ("%", "*", $folderpath);
    
    // define query for 
    
    // object type (only if less than 5 of total 5 arguments (component, audio, video, document, image), otherwise we look for all object types/formats).
    // for media reference the search can also include binary, flash, compressed and text.
    if (is_array ($object_type) && sizeof ($object_type) < 5)
    {
      $sql_where['object'] = "";
      $sql_where['format'] = "";
      
      // add media table
      $sql_table = ', media AS med'.$sql_table;        
      $sql_where['media'] = 'obj.id=med.id';          
      
      foreach ($object_type as $search_type)
      {
        if ($search_type == "page" || $search_type == "comp")
        {
          if ($sql_where['object'] != "") $sql_where['object'] .= " OR ";
          $sql_where['object'] .= 'obj.template LIKE "%.'.$search_type.'.tpl"';
        }
        
        // file-type (audio, document, text, image, video, compressed, flash, binary)
        if (in_array ($search_type, array("audio","document","text","image","video","compressed","flash","binary")))
        {
          if ($sql_where['format'] != "") $sql_where['format'] .= " OR ";        
          $sql_where['format'] .= 'med.filetype="'.$search_type.'"';
        }
      }
      
      // add meta as object type if formats are set
      if (!empty ($sql_where['format']))
      {
        if ($sql_where['object'] != "") $sql_where['object'] .= " OR ";
        $sql_where['object'] .= 'obj.template LIKE "%.meta.tpl"';
      }
      
      // add () for OR operators
      if ($sql_where['object'] != "") $sql_where['object'] = "(".$sql_where['object'].")";
      else unset ($sql_where['object']);
      if ($sql_where['format'] != "") $sql_where['format'] = "(".$sql_where['format'].")";
      else unset ($sql_where['format']);
    }    
    
    // folder path
    $sql_where['filename'] = 'obj.objectpath LIKE _utf8"'.$folderpath.'%" COLLATE utf8_bin';
 
    // dates
    if (!empty ($date_from)) $sql_where['datefrom'] = 'DATE(cnt.date)>="'.$date_from.'"';
    if (!empty ($date_to)) $sql_where['dateto'] = 'DATE(cnt.date)<="'.$date_to.'"'; 
    
    // search expression
    if ($search_expression != "")
    {
      $expression = $search_expression;
      
      $expression = str_replace ("%", '\%', $expression);
      $expression = str_replace ("_", '\_', $expression);
      
      $expression = str_replace ("*", "%", $expression);
      $expression = str_replace ("?", "_", $expression);
      //if (substr_count ($expression, "%") == 0) $expression = "%".$expression."%";
      $expression_esc = htmlentities ($expression, ENT_QUOTES, convert_dbcharset ($mgmt_config['dbcharset']));
      $expression = $db->escape_string ($expression);

      if ($expression != $expression_esc) $sql_where['expression'] = '(tn1.textcontent LIKE _utf8"%'.$expression.'%" COLLATE utf8_bin OR tn1.textcontent LIKE _utf8"%'.$expression_esc.'%" COLLATE utf8_bin)';
      else $sql_where['textnodes'] = 'tn1.textcontent LIKE _utf8"%'.$expression.'%" COLLATE utf8_bin';
    }    
    
    $sql = 'SELECT obj.objectpath, cnt.id, cnt.container, tn1.text_id, tn1.textcontent FROM object AS obj, container AS cnt, textnodes AS tn1 ';
    if (!empty ($sql_table)) $sql .= $sql_table.' ';
    $sql .= 'WHERE obj.id=cnt.id AND cnt.id=tn1.id AND ';    
    $sql .= implode (" AND ", $sql_where);
    $sql .= " ORDER BY id, text_id";

    $errcode = "50023";
    $done = $db->query ($sql, $errcode, $mgmt_config['today'], "select");
    
    $container_id_prev = "";
    $containerdata = "";

    if ($done)
    {
      // transform search expression
      $search_expression = str_replace ("*", "", $search_expression);
      $search_expression = str_replace ("?", "", $search_expression);
      $search_expression_esc = htmlentities ($search_expression, ENT_QUOTES, convert_dbcharset ($mgmt_config['dbcharset']));
      $search_expression = $db->escape_string ($search_expression);
      
      $replace_expression_esc = htmlentities ($replace_expression, ENT_QUOTES, convert_dbcharset ($mgmt_config['dbcharset']));
      $replace_expression = $db->escape_string ($replace_expression);
        
      $num_rows = $db->getNumRows ("select");
 
      if ($num_rows > 0)
      {
        for ($i = 0; $i < $num_rows; $i++)
        {
          $row = $db->getResultRow ("select", $i);
        
          $objectpath[] = str_replace ("*", "%", $row['objectpath']);
          $id = $row['id'];
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
                  $error[] = $mgmt_config['today']."|db_connect_rdbms.php|error|$errcode|container ".$container_id_prev." could not be saved\n";  
                  
                  // save log
                  savelog ($error);                                    
                }
                else
                {
                  // update content in database
                  $errcode = "50024";
                  
                  foreach ($sql_array as $sql)
                  {
                    $db->query ($sql, $errcode, $mgmt_config['today'], "update");
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
            if ($containerdata != "" && $containerdata != false)
            {       
              $xml_search_array = selectcontent ($containerdata, "<text>", "<text_id>", $text_id);
       
              if ($xml_search_array != false)
              {
                $xml_content = getxmlcontent ($xml_search_array[0], "<textcontent>");
                
                if ($xml_content != false && $xml_content[0] != "")
                {
                  if (substr_count ($xml_content[0], $search_expression_esc) > 0 || substr_count ($xml_content[0], $search_expression) > 0)
                  {
                    // replace expression in textcontent
                    $xml_replace = str_replace ($search_expression, $replace_expression_esc, $xml_content[0]);
                    
                    if ($search_expression != $search_expression_esc)
                    {
                      $xml_replace = str_replace ($search_expression_esc, $replace_expression_esc, $xml_replace);
                    }
                    
                    // replace textcontent in text
                    $xml_replace = str_replace($xml_content[0], $xml_replace, $xml_search_array[0]);

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
      $error[] = $mgmt_config['today']."|db_connect_rdbms.php|error|$errcode|container ".$container_id_prev." could not be saved\n";  
      
      // save log
      savelog ($error);                                    
    }
    else
    {
      // update content in database
      $errcode = "50040";
      
      foreach ($sql_array as $sql)
      {
        $db->query ($sql, $errcode, $mgmt_config['today'], "update");
      }
    }
    
    // save log
    savelog ($db->getError ());    
    $db->close(); 
    
    if (isset ($objectpath) && is_array ($objectpath))
    {
      $objectpath = array_unique ($objectpath);
      return $objectpath;
    }
    else return false;
  }
  else return false;
}

// ----------------------------------------------- search user ------------------------------------------------- 
function rdbms_searchuser ($site, $user, $maxhits=1000)
{
  global $mgmt_config;

  if ($user != "")
  {
    $db = new hcms_db($mgmt_config['dbconnect'], $mgmt_config['dbhost'], $mgmt_config['dbuser'], $mgmt_config['dbpasswd'], $mgmt_config['dbname'], $mgmt_config['dbcharset']);
    
    if ($site != "" && $site != "*Null*") $site = $db->escape_string ($site);
    $user = $db->escape_string ($user);
    if ($maxhits != "") $maxhits = $db->escape_string ($maxhits);
    
    $sql = 'SELECT obj.objectpath, obj.hash FROM object AS obj, container AS cnt WHERE obj.id=cnt.id AND cnt.user="'.$user.'"';
    if ($site != "" && $site != "*Null*") $sql .= ' AND (obj.objectpath LIKE _utf8"*page*/'.$site.'/%" COLLATE utf8_bin OR obj.objectpath LIKE _utf8"*comp*/'.$site.'/%" COLLATE utf8_bin)';
    $sql .= ' ORDER BY cnt.date DESC';
    if ($maxhits != "" && $maxhits > 0) $sql .= ' LIMIT 0,'.intval($maxhits);

    $errcode = "50025";
    $done = $db->query($sql, $errcode, $mgmt_config['today']);
    
    if ($done)
    {
      $objectpath = array();
      
      while ($row = $db->getResultRow ())
      {
        if ($row['objectpath'] != "")
        {
          $hash = $row['hash'];
          $objectpath[$hash] = str_replace ("*", "%", $row['objectpath']);
        }   
      }
    }
    else $objectpath = Null;

    // save log
    savelog ($db->getError ());    
    $db->close();
      
    if (is_array ($objectpath) && sizeof ($objectpath) > 0) return $objectpath;
    else return false;
  }
  else return false;
} 

// ----------------------------------------------- get object_id ------------------------------------------------- 
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
    
    $object = $db->escape_string ($object);
    
    // object path
    if (substr_count ($object, "%page%") > 0 || substr_count ($object, "%comp%") > 0)
    { 
      $object = str_replace ("%", "*", $object);

      $sql = 'SELECT object_id FROM object WHERE objectpath=_utf8"'.$object.'" COLLATE utf8_bin';
    }
    // object hash
    else
    {
      $sql = 'SELECT object_id FROM object WHERE hash=_utf8"'.$object.'" COLLATE utf8_bin';
    }
    
    $errcode = "50026";
    $done = $db->query ($sql, $errcode, $mgmt_config['today']);
    
    if ($done && $row = $db->getResultRow ())
    {
      $object_id = $row['object_id'];
    }
    
    // save log
    savelog ($db->getError ());
    $db->close();
      
    if (!empty ($object_id))
    {
      return $object_id;
    }
    else
    {
      // if object is a root folder (created since version 5.6.3)
      if (substr_count ($object, "/") == 2)
      {
        $object_esc = str_replace ("*", "%", $object);
        $createobject = createobject (getpublication ($object_esc), getlocation ($object_esc), ".folder", "default.meta.tpl", "sys");
 
        if ($createobject['result'] == true) return $object_id = rdbms_getobject_id ($object_esc);
        else return false;
      }
      else return false;
    }
  }
  else return false;
}

// ----------------------------------------------- get object_hash ------------------------------------------------- 
function rdbms_getobject_hash ($object="", $container_id="")
{
  global $mgmt_config;

  // object can be an object path or object ID, second input parameter can only be the container ID
  if ($object != "" || $container_id != "")
  {
    $db = new hcms_db($mgmt_config['dbconnect'], $mgmt_config['dbhost'], $mgmt_config['dbuser'], $mgmt_config['dbpasswd'], $mgmt_config['dbname'], $mgmt_config['dbcharset']);
      
    // if object path
    if (substr_count ($object, "%page%") > 0 || substr_count ($object, "%comp%") > 0)
    {
      // correct object name 
      if (strtolower (@strrchr ($object, ".")) == ".off") $object = @substr ($object, 0, -4);
      
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
      
      $object = $db->escape_string ($object);          
      $object = str_replace ("%", "*", $object);
  
      $sql = 'SELECT hash FROM object WHERE objectpath=_utf8"'.$object.'" COLLATE utf8_bin LIMIT 1';
    }
    // if object id
    elseif (intval ($object) > 0)
    {
      $sql = 'SELECT hash FROM object WHERE object_id='.intval($object).' LIMIT 1';
    }
    // if container id
    elseif (intval ($container_id) > 0)
    {
      $sql = 'SELECT hash FROM object WHERE id='.intval($container_id).' LIMIT 1';
    }

    if (!empty ($sql))
    {
      $errcode = "50026";
      $done = $db->query ($sql, $errcode, $mgmt_config['today']);
      
      if ($done && $row = $db->getResultRow ())
      {
        $hash = $row['hash'];   
      }

      // save log
      savelog ($db->getError ());    
      $db->close();
        
      if (!empty ($hash))
      {
        return $hash;
      }
      else
      {
        // if object is a root folder (created since version 5.6.3)
        if (substr_count ($object, "/") == 2)
        {
          $object_esc = str_replace ("*", "%", $object);
          $createobject = createobject (getpublication ($object_esc), getlocation ($object_esc), ".folder", "default.meta.tpl", "sys");
          
          if ($createobject['result'] == true) return $hash = rdbms_getobject_hash ($object_esc);
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
    $object_identifier = $db->escape_string ($object_identifier);
    
    // try table object if public download is allowed
    if ($mgmt_config['publicdownload'] == true)
    {
      if (is_numeric ($object_identifier)) $sql = 'SELECT objectpath FROM object WHERE object_id='.intval($object_identifier);
      else $sql = 'SELECT objectpath FROM object WHERE hash="'.$object_identifier.'"';
  
      $errcode = "50027";
      $done = $db->query ($sql, $errcode, $mgmt_config['today']);
      
      if ($done && $row = $db->getResultRow ())
      {
        if ($row['objectpath'] != "") $objectpath = str_replace ("*", "%", $row['objectpath']);  
      }
    }

    // try table accesslink
    if ($objectpath == "" && !is_numeric ($object_identifier))
    {
      $sql = 'SELECT obj.objectpath, al.deathtime, al.formats FROM accesslink AS al, object AS obj WHERE al.hash="'.$object_identifier.'" AND al.object_id=obj.object_id';
      
      $errcode = "50028";
      $done = $db->query ($sql, $errcode, $mgmt_config['today'], "select2");
      
      if ($done)
      {
        $row = $db->getResultRow ("select2");
        
        // if time of death for link is set
        if ($row['deathtime'] > 0)
        {
          // if deathtime was passed
          if ($row['deathtime'] < time())
          {
            $sql = 'DELETE FROM accesslink WHERE hash="'.$object_identifier.'"';
             
            $errcode = "50029";
            $db->query ($sql, $errcode, $mgmt_config['today'], "delete");
          }
          elseif ($row['objectpath'] != "") $objectpath = str_replace ("*", "%", $row['objectpath']);
        }
        elseif ($row['objectpath'] != "") $objectpath = str_replace ("*", "%", $row['objectpath']);  
      }
    }
    
    // save log
    savelog ($db->getError ());    
    $db->close();     
      
    if ($objectpath != "") return $objectpath;
    else return false;
  }
  else return false;
} 

// ----------------------------------------------- get objects by container_id ------------------------------------------------- 
function rdbms_getobjects ($container_id, $template="")
{
  global $mgmt_config;

  if ($container_id != "")
  {
    $db = new hcms_db($mgmt_config['dbconnect'], $mgmt_config['dbhost'], $mgmt_config['dbuser'], $mgmt_config['dbpasswd'], $mgmt_config['dbname'], $mgmt_config['dbcharset']);
    
    // clean input
    $container_id = $db->escape_string ($container_id);
    if ($template != "") $template = $db->escape_string ($template);
    
    $container_id = intval ($container_id);
    
    $sql = 'SELECT objectpath, hash FROM object WHERE id='.$container_id;
    if ($template != "") $sql .= ' AND template="'.$template.'"';
    
    $errcode = "50030";
    $done = $db->query ($sql, $errcode, $mgmt_config['today']);
    $objectpath = array();
    
    if ($done)  
    {
      while ($row = $db->getResultRow ())
      {
        if (trim ($row['objectpath']) != "")
        {
          $hash = $row['hash'];
          $objectpath[$hash] = str_replace ("*", "%", $row['objectpath']);
        }
      }
    }

    // save log
    savelog ($db->getError ());    
    $db->close();    
      
    if (sizeof ($objectpath) > 0) return $objectpath;
    else return false;
  }
  else return false;
}

// ----------------------------------------------- create accesslink -------------------------------------------------
function rdbms_createaccesslink ($hash, $object_id, $type="al", $user="", $lifetime=0, $formats="")
{
  global $mgmt_config;
  
  if ($hash != "" && $object_id != "" && (($type == "al" && valid_objectname ($user)) || $type == "dl"))
  { 
    $db = new hcms_db($mgmt_config['dbconnect'], $mgmt_config['dbhost'], $mgmt_config['dbuser'], $mgmt_config['dbpasswd'], $mgmt_config['dbname'], $mgmt_config['dbcharset']);
    
    $hash = $db->escape_string ($hash);
    $object_id = $db->escape_string ($object_id);
    $type = $db->escape_string ($type);
    if ($user != "") $user = $db->escape_string ($user);
    if ($lifetime != "") $lifetime = $db->escape_string ($lifetime);
    if ($formats != "") $formats = $db->escape_string ($formats);
    
    // date
    $date = date ("Y-m-d H:i", time());
    
    // define time of death based on lifetime
    if ($lifetime > 0) $deathtime = time() + intval ($lifetime);
    else $deathtime = 0;

    // insert access link info
    $sql = 'INSERT INTO accesslink (hash, date, object_id, type, user, deathtime, formats) ';    
    $sql .= 'VALUES ("'.$hash.'", "'.$date.'", '.intval ($object_id).', "'.$type.'", "'.$user.'", '.intval ($deathtime).', "'.$formats.'")';
         
    $errcode = "50007";
    $db->query ($sql, $errcode, $mgmt_config['today']);

    // save log
    savelog ($db->getError ());    
    $db->close();
        
    return true;
  }
  else return false;
} 

// ------------------------------------------------ get access info -------------------------------------------------
function rdbms_getaccessinfo ($hash)
{
  global $mgmt_config;
 
  if ($hash != "")
  {
    $result = array();
    
    $db = new hcms_db($mgmt_config['dbconnect'], $mgmt_config['dbhost'], $mgmt_config['dbuser'], $mgmt_config['dbpasswd'], $mgmt_config['dbname'], $mgmt_config['dbcharset']);
    
    $hash = $db->escape_string ($hash);
  
    $sql = 'SELECT date, object_id, type, user, deathtime, formats FROM accesslink WHERE hash="'.$hash.'"';

    $errcode = "50071";
    $done = $db->query ($sql, $errcode, $mgmt_config['today'], "select");

    if ($done)
    {
      $row = $db->getResultRow ("select");
      
      $result['date'] = $row['date'];
      $result['object_id'] = $row['object_id']; 
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
          $db->query ($sql, $errcode, $mgmt_config['today'], "delete");
          
          $result = false;
        }
      }
    }

    // save log
    savelog ($db->getError ());    
    $db->close();
    
    if (is_array ($result) && sizeof ($result) > 0) return $result;
    else return false;
  }
  else return false;
}

// ------------------------------------------------ create recipient -------------------------------------------------
function rdbms_createrecipient ($object, $sender, $user, $email)
{
  global $mgmt_config;
 
  if ($object != "" && $sender != "" && $user != "" && $email != "")
  {
    // correct object name 
    if (strtolower (@strrchr ($object, ".")) == ".off") $object = @substr ($object, 0, -4);
      
    $db = new hcms_db($mgmt_config['dbconnect'], $mgmt_config['dbhost'], $mgmt_config['dbuser'], $mgmt_config['dbpasswd'], $mgmt_config['dbname'], $mgmt_config['dbcharset']);

    $date = date ("Y-m-d H:i:s", time());
    
    $object = $db->escape_string ($object);
    $sender = $db->escape_string ($sender);
    $user = $db->escape_string ($user);
    $email = $db->escape_string ($email);
    
    $object = str_replace ("%", "*", $object);    
    
    // get object ids of all objects (also all object of folders)
    if (getobject ($object) == ".folder") $sql = 'SELECT object_id FROM object WHERE objectpath LIKE _utf8"'.substr (trim($object), 0, strlen (trim($object))-7).'%" COLLATE utf8_bin';
    else $sql = 'SELECT object_id FROM object WHERE objectpath=_utf8"'.$object.'" COLLATE utf8_bin';

    $errcode = "50029";
    $done = $db->query($sql, $errcode, $mgmt_config['today'], 'select');
    
    if ($done)
    {
      $i = 1;
      
      while ($object_id = $db->getResultRow ('select'))
      {
        $sql = 'INSERT INTO recipient (object_id, date, sender, user, email) ';    
        $sql .= 'VALUES ('.intval ($object_id['object_id']).', "'.$date.'", "'.$sender.'", "'.$user.'", "'.$email.'")';
        
        $errcode = "50030";
        $done = $db->query ($sql, $errcode, $mgmt_config['today'], $i++);
      }
    }

    // save log
    savelog ($db->getError());    
    $db->close();   
         
    return true;
  }
  else return false;
}

// ------------------------------------------------ get recipients -------------------------------------------------
function rdbms_getrecipients ($object)
{
  global $mgmt_config;

  if ($object != "")
  {
    // correct object name 
    if (strtolower (@strrchr ($object, ".")) == ".off") $object = @substr ($object, 0, -4);
      
    $db = new hcms_db($mgmt_config['dbconnect'], $mgmt_config['dbhost'], $mgmt_config['dbuser'], $mgmt_config['dbpasswd'], $mgmt_config['dbname'], $mgmt_config['dbcharset']);
    
    // clean input
    $object = $db->escape_string ($object);    
    $object = str_replace ("%", "*", $object);    
    
    // get recipients
    $sql = 'SELECT rec.recipient_id, rec.object_id, rec.date, rec.sender, rec.user, rec.email FROM recipient AS rec, object AS obj WHERE obj.object_id=rec.object_id AND obj.objectpath=_utf8"'.$object.'" COLLATE utf8_bin';   

    $errcode = "50031";
    $done = $db->query ($sql, $errcode, $mgmt_config['today'], 'select');
    
    if ($done)
    {
      $i = 0;
      $recipient = array();
      
      while ($row = $db->getResultRow ('select'))
      {
        $recipient[$i]['recipient_id'] = $row['recipient_id'];
        $recipient[$i]['object_id'] = $row['object_id'];
        $recipient[$i]['date'] = $row['date'];
        $recipient[$i]['sender'] = $row['sender']; 
        $recipient[$i]['user'] = $row['user'];  
        $recipient[$i]['email'] = $row['email'];
               
        $i++;
      }
    }

    // save log
    savelog ($db->getError());    
    $db->close();      
         
    if (!empty ($recipient) && sizeof ($recipient) > 0) return $recipient;
    else return false;
  }
  else return false;
}

// ----------------------------------------------- delete recipient -------------------------------------------------
function rdbms_deleterecipient ($recipient_id)
{
  global $mgmt_config;
  
  if ($recipient_id != "")
  {   
    $db = new hcms_db($mgmt_config['dbconnect'], $mgmt_config['dbhost'], $mgmt_config['dbuser'], $mgmt_config['dbpasswd'], $mgmt_config['dbname'], $mgmt_config['dbcharset']);
    
    // clean input
    $recipient_id = $db->escape_string ($recipient_id);
        
    $sql = 'DELETE FROM recipient WHERE recipient_id='.$recipient_id;
     
    $errcode = "50032";
    $db->query ($sql, $errcode, $mgmt_config['today']);
    
    // save log
    savelog ($db->getError ());    
    $db->close();      
         
    return true;
  }
  else return false;
}

// ----------------------------------------------- create queue entry -------------------------------------------------
function rdbms_createqueueentry ($action, $object, $date, $published_only=0, $user)
{
  global $mgmt_config;

  if ($action != "" && $object != "" && is_date ($date, "Y-m-d H:i") && $user != "" && (substr_count ($object, "%page%") > 0 || substr_count ($object, "%comp%") > 0))
  {
    // correct object name 
    if (strtolower (@strrchr ($object, ".")) == ".off") $object = @substr ($object, 0, -4);
 
    $object_id = rdbms_getobject_id ($object);
    
    if ($object_id != false)
    {
      $db = new hcms_db($mgmt_config['dbconnect'], $mgmt_config['dbhost'], $mgmt_config['dbuser'], $mgmt_config['dbpasswd'], $mgmt_config['dbname'], $mgmt_config['dbcharset']);    
      
      // clean input
      $action = $db->escape_string ($action);
      $object = $db->escape_string ($object);
      $date = $db->escape_string ($date);
      if ($published_only != "") $published_only = $db->escape_string ($published_only);
      $user = $db->escape_string ($user);
      
      $sql = 'INSERT INTO queue (object_id, action, date, published_only, user) ';    
      $sql .= 'VALUES ('.intval ($object_id).', "'.$action.'", "'.$date.'", '.intval ($published_only).', "'.$user.'")';
      
      $errcode = "50033";
      $done = $db->query ($sql, $errcode, $mgmt_config['today']); 
        
      // save log
      savelog ($db->getError());
    
      $db->close();
      
      return $done;
    }
    else return false;
  }
  else return false;
}

// ------------------------------------------------ get queue entries -------------------------------------------------
function rdbms_getqueueentries ($action="", $site="", $date="", $user="", $object="")
{
  global $mgmt_config;

  if (is_array ($mgmt_config))
  {
    $db = new hcms_db($mgmt_config['dbconnect'], $mgmt_config['dbhost'], $mgmt_config['dbuser'], $mgmt_config['dbpasswd'], $mgmt_config['dbname'], $mgmt_config['dbcharset']);    
    
    // check object (can be valid path or ID)
    if (substr_count ($object, "%page%") > 0 || substr_count ($object, "%comp%") > 0) $object_id = rdbms_getobject_id ($object);
    elseif (is_numeric ($object)) $object_id = $object; 
    elseif ($object != "") return false;  
    
    // clean input
    if (!empty ($action)) $action = $db->escape_string ($action);
    if (!empty ($site)) $site = $db->escape_string ($site);
    if (!empty ($date)) $date = $db->escape_string ($date);
    if (!empty ($user)) $user = $db->escape_string ($user);
    if (!empty ($object_id)) $object_id = $db->escape_string ($object_id);

    // get recipients
    $sql = 'SELECT que.queue_id, que.action, que.date, que.published_only, que.user, obj.objectpath FROM queue AS que, object AS obj WHERE obj.object_id=que.object_id';
    if (!empty ($action)) $sql .= ' AND que.action="'.$action.'"';
    if (!empty ($site)) $sql .= ' AND (obj.objectpath LIKE _utf8"*page*/'.$site.'/%" COLLATE utf8_bin OR obj.objectpath LIKE _utf8"*comp*/'.$site.'/%" COLLATE utf8_bin)';
    if (!empty ($date)) $sql .= ' AND que.date<="'.$date.'"'; 
    if (!empty ($user)) $sql .= ' AND que.user="'.$user.'"';
    if (!empty ($object_id)) $sql .= ' AND que.object_id="'.$object_id.'"';
    $sql .= ' ORDER BY que.date';
  
    $errcode = "50034";
    $done = $db->query($sql, $errcode, $mgmt_config['today'], 'select');
    
    $queue = array();
          
    if ($done)
    {  
      $i = 0;
      
      // insert recipients
      while ($row = $db->getResultRow ('select'))
      {
        $queue[$i]['queue_id'] = $row['queue_id'];
        $queue[$i]['action'] = $row['action'];
        $queue[$i]['objectpath'] = str_replace ("*", "%", $row['objectpath']);
        $queue[$i]['date'] = $row['date'];
        $queue[$i]['published_only'] = $row['published_only'];
        $queue[$i]['user'] = $row['user'];        
        $i++;
      }        
    }
  
    // save log
    savelog ($db->getError());
    
    $db->close();
    
    if (is_array ($queue) && sizeof ($queue) > 0) return $queue;
    else return false;
  }
  else return false;
}

// ----------------------------------------------- delete queue entry -------------------------------------------------
function rdbms_deletequeueentry ($queue_id)
{
  global $mgmt_config;
  
  if ($queue_id != "")
  {   
    $db = new hcms_db($mgmt_config['dbconnect'], $mgmt_config['dbhost'], $mgmt_config['dbuser'], $mgmt_config['dbpasswd'], $mgmt_config['dbname'], $mgmt_config['dbcharset']);    
    
    // clean input
    $queue_id = $db->escape_string ($queue_id);
    
    // query
    $sql = 'DELETE FROM queue WHERE queue_id='.$queue_id;
     
    $errcode = "50035";
    $db->query ($sql, $errcode, $mgmt_config['today']);
    
    // save log
    savelog ($db->getError ());

    $db->close();
         
    return true;
  }
  else return false;
}

// ----------------------------------------------- create notification -------------------------------------------------
function rdbms_createnotification ($object, $events, $user)
{
  global $mgmt_config;

  if ($object != "" && is_array ($events) && $user != "")
  {
    // correct object name 
    if (strtolower (@strrchr ($object, ".")) == ".off") $object = @substr ($object, 0, -4);
    
    // check object (can be path or ID)
    if (substr_count ($object, "%page%") > 0 || substr_count ($object, "%comp%") > 0) $object_id = rdbms_getobject_id ($object);
    elseif (is_numeric ($object)) $object_id = $object;
    else $object_id = false;
    
    if ($object_id != false)
    {
      $db = new hcms_db($mgmt_config['dbconnect'], $mgmt_config['dbhost'], $mgmt_config['dbuser'], $mgmt_config['dbpasswd'], $mgmt_config['dbname'], $mgmt_config['dbcharset']);    
      
      // clean input
      $user = $db->escape_string ($user);
      if (array_key_exists ("oncreate", $events) && $events['oncreate'] == 1) $oncreate = 1;
      else $oncreate = 0;
      if (array_key_exists ("onedit", $events) && $events['onedit'] == 1) $onedit = 1;
      else $onedit = 0;
      if (array_key_exists ("onmove", $events) && $events['onmove'] == 1) $onmove = 1;
      else $onmove = 0;
      if (array_key_exists ("ondelete", $events) && $events['ondelete'] == 1) $ondelete = 1;
      else $ondelete = 0;
      
      $sql = 'SELECT count(*) AS count FROM notify WHERE object_id='.$object_id.' AND user="'.$user.'"';
      
      $errcode = "50193";
      $done = $db->query ($sql, $errcode, $mgmt_config['today'], 'select');
      
      if ($done)
      {
        $result = $db->getResultRow ('select', 0);
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
        $done = $db->query ($sql, $errcode, $mgmt_config['today'], 'insert'); 
      }
      
      // save log
      savelog ($db->getError());
    
      $db->close();
      
      return $done;
    }
    else return false;
  }
  else return false;
}

// ------------------------------------------------ get notifications -------------------------------------------------
function rdbms_getnotification ($event="", $object="", $user="")
{
  global $mgmt_config;

  if (is_array ($mgmt_config))
  {
    $db = new hcms_db($mgmt_config['dbconnect'], $mgmt_config['dbhost'], $mgmt_config['dbuser'], $mgmt_config['dbpasswd'], $mgmt_config['dbname'], $mgmt_config['dbcharset']);    
    
    if (!empty($event))
    {
      $valid_events = array ("oncreate", "onedit", "onmove", "ondelete");
      if (!in_array (strtolower($event), $valid_events)) $event = "";
    }
    
    if ($object != "")
    {
      // correct object name 
      if (strtolower (@strrchr ($object, ".")) == ".off") $object = @substr ($object, 0, -4);
      // get publication
      $site = getpublication ($object);
      $fileinfo = getfileinfo ($site, $object, "");
      if (getobject ($object) == ".folder") $object = getlocation ($object);
      // clean input
      $object = $db->escape_string ($object);
      $object = str_replace ("%", "*", $object); 
    }
    
    if ($user != "") $user = $db->escape_string ($user);
        
    // get recipients
    $sql = 'SELECT nfy.notify_id, nfy.object_id, obj.objectpath, nfy.user, nfy.oncreate, nfy.onedit, nfy.onmove, nfy.ondelete FROM notify AS nfy, object AS obj WHERE obj.object_id=nfy.object_id';
    if ($event != "") $sql .= ' AND nfy.'.$event.'=1';
    if ($object != "") $sql .= ' AND (obj.objectpath="'.$object.'" || INSTR("'.$object.'", SUBSTR(obj.objectpath, 1, INSTR(obj.objectpath, ".folder") - 1))>0)';
    if ($user != "") $sql .= ' AND nfy.user="'.$user.'"';
    $sql .= ' ORDER BY obj.objectpath';

    $errcode = "50094";
    $done = $db->query($sql, $errcode, $mgmt_config['today'], 'select');

    if ($done)
    {  
      $i = 0;
      // insert recipients
      while ($row = $db->getResultRow ('select'))
      {
        $queue[$i]['notify_id'] = $row['notify_id'];
        $queue[$i]['object_id'] = $row['object_id'];
        $queue[$i]['objectpath'] = str_replace ("*", "%", $row['objectpath']);
        $queue[$i]['user'] = $row['user']; 
        $queue[$i]['oncreate'] = $row['oncreate'];
        $queue[$i]['onedit'] = $row['onedit'];
        $queue[$i]['onmove'] = $row['onmove'];
        $queue[$i]['ondelete'] = $row['ondelete'];

        $i++;
      }        
    }

    // save log
    savelog ($db->getError());    
    $db->close();
    
    if (is_array (@$queue)) return $queue;
    else return false;
  }
  else return false;
}

// ----------------------------------------------- delete notification -------------------------------------------------
function rdbms_deletenotification ($notify_id, $object="", $user="")
{
  global $mgmt_config;
  
  if ($notify_id != "" || $object != "" || $user != "")
  {   
    $db = new hcms_db($mgmt_config['dbconnect'], $mgmt_config['dbhost'], $mgmt_config['dbuser'], $mgmt_config['dbpasswd'], $mgmt_config['dbname'], $mgmt_config['dbcharset']);
    
    if ($object != "")
    {
      // check object (can be path or ID)
      if (substr_count ($object, "%page%") > 0 || substr_count ($object, "%comp%") > 0) $object_id = rdbms_getobject_id ($object);
      elseif (is_numeric ($object)) $object_id = $object;
      else $object_id = false;
    }
    
    // clean input
    if (!empty($notify_id)) $notify_id = $db->escape_string ($notify_id);
    elseif (!empty($object_id)) $object_id = $db->escape_string ($object_id);
    elseif (!empty($user)) $user = $db->escape_string ($user);
        
    if (!empty($notify_id)) $sql = 'DELETE FROM notify WHERE notify_id='.$notify_id;
    elseif (!empty($object_id)) $sql = 'DELETE FROM notify WHERE object_id='.$object_id;
    elseif (!empty($user)) $sql = 'DELETE FROM notify WHERE user="'.$user.'"';
     
    $errcode = "50092";
    $db->query ($sql, $errcode, $mgmt_config['today']);
    
    // save log
    savelog ($db->getError ());    
    $db->close();      
         
    return true;
  }
  else return false;
}

// ----------------------------------------------- license notification -------------------------------------------------
function rdbms_licensenotification ($folderpath, $text_id, $date_begin, $date_end, $format="%Y-%m-%d")
{
  global $mgmt_config;
  
  if ($folderpath != "" && $text_id != "" && $date_begin != "" && $date_end != "")
  {
    $db = new hcms_db ($mgmt_config['dbconnect'], $mgmt_config['dbhost'], $mgmt_config['dbuser'], $mgmt_config['dbpasswd'], $mgmt_config['dbname'], $mgmt_config['dbcharset']);    
    
    $folderpath = $db->escape_string ($folderpath);
    $text_id = $db->escape_string ($text_id);
    $date_begin = $db->escape_string ($date_begin);
    $date_end = $db->escape_string ($date_end);
    $format = $db->escape_string ($format);
    
    $folderpath = str_replace ("%", "*", $folderpath);
   
    $sql = 'SELECT DISTINCT obj.objectpath as path, tnd.textcontent as cnt FROM object AS obj, textnodes AS tnd ';
    $sql .= 'WHERE obj.id=tnd.id AND obj.objectpath LIKE _utf8"'.$folderpath.'%" COLLATE utf8_bin AND tnd.text_id=_utf8"'.$text_id.'" COLLATE utf8_bin  AND "'.$date_begin.'" <= STR_TO_DATE(tnd.textcontent, "'.$format.'") AND "'.$date_end.'" >= STR_TO_DATE(tnd.textcontent, "'.$format.'")';    
    $errcode = "50036";
    $done = $db->query($sql, $errcode, $mgmt_config['today']);

    if ($done)
    {
      $i = 0;
      
      while ($row = $db->getResultRow ())
      {
        $objectpath = str_replace ("*", "%", $row['path']);
        $licenseend = $row['cnt']; 
        $site = getpublication ($objectpath);
        $location = getlocation ($objectpath);    
        $object = getobject ($objectpath);
        $cat = getcategory ($site, $location);
     
        $result[$i]['publication'] = $site;
        $result[$i]['location'] = $location;
        $result[$i]['object'] = $object;
        $result[$i]['category'] = $cat;
        $result[$i]['date'] = $licenseend;
        $i++;
      }
    }

    // save log
    savelog ($db->getError());
    $db->close();
    
    if (is_array ($result)) return $result;
    else return false;
  }
  else return false;
}

// ----------------------------------------------- daily statistics -------------------------------------------------
// Update the daily statistics after a loggable event.
// The dailystat table contains a counter for each 'activity' (i.e. download) for each object (i.e. media file of container) per day.

function rdbms_insertdailystat ($activity, $container_id, $user="")
{
  global $mgmt_config;
  
  if ($activity != "" && $container_id != "")
  {
    $db = new hcms_db ($mgmt_config['dbconnect'], $mgmt_config['dbhost'], $mgmt_config['dbuser'], $mgmt_config['dbpasswd'], $mgmt_config['dbname'], $mgmt_config['dbcharset']);    
    
    // clean input    
    $activity = $db->escape_string ($activity);
    $container_id = $db->escape_string ($container_id);
    if ($user != "") $user = $db->escape_string ($user);

    // get current date
    $date = date ("Y-m-d", time());

    // set user if not defined
    if ($user == "")
    {
      if (!empty ($_SESSION['hcms_user'])) $user = $_SESSION['hcms_user'];
      else $user = getuserip ();
    }
    
    // check to see if there is a row
    $sql = 'SELECT count(*) AS count FROM dailystat WHERE date="'.$date.'" AND user="'.$user.'" AND activity="'.$activity.'" AND id='.$container_id;
    
    $errcode = "50037";
    $done = $db->query ($sql, $errcode, $mgmt_config['today'], 'select');
    
    if ($done)
    {
      $result = $db->getResultRow ('select', 0);
      $count = $result['count'];

      if ($count == 0)
      {
        // insert
        $sql = 'INSERT INTO dailystat (id,user,activity,date,count) VALUES ('.$container_id.',"'.$user.'","'.$activity.'","'.$date.'",1)';
      }
      else
      {
        // update
        $sql = 'UPDATE dailystat SET count=count+1 WHERE date="'.$date.'" AND user="'.$user.'" AND activity="'.$activity.'" AND id='.$container_id;
      }

      $errcode = "50038";
      $db->query ($sql, $errcode, $mgmt_config['today'], 'insertupdate');

      // save log
      savelog ($db->getError());
      $db->close();    

      return true;  
    }
    else
    {
      // save log
      savelog ($db->getError());
      $db->close();    
      
      return false;
    }
  }
  else return false;  
}

// ----------------------------------------------- get statistics from dailystat -------------------------------------------------

function rdbms_getmediastat ($date_from="", $date_to="", $activity="", $container_id="", $objectpath = "", $user="", $type="media")
{
  global $mgmt_config;

  // mySQL connect
  $db = new hcms_db ($mgmt_config['dbconnect'], $mgmt_config['dbhost'], $mgmt_config['dbuser'], $mgmt_config['dbpasswd'], $mgmt_config['dbname'], $mgmt_config['dbcharset']);    
  
  // clean input
  if ($date_from != "") $date_from = $db->escape_string ($date_from);
  if ($date_to != "") $date_to = $db->escape_string ($date_to);
  if ($activity != "") $activity = $db->escape_string ($activity);
  if ($container_id != "") $container_id = $db->escape_string (intval($container_id));
  if ($user != "") $user = $db->escape_string ($user);
  
  // media file
  if ($type == "media")
  {
    if ($objectpath != "")
    {
      $site = getpublication ($objectpath);
      $cat = getcategory ($site, $objectpath);
      $object_info = getfileinfo ($site, $objectpath, $cat);
      
      $objectpath = $db->escape_string ($objectpath);
      $objectpath = str_replace ('%', '*', $objectpath);
      
      if (getobject ($objectpath) == ".folder") $location = getlocation ($objectpath);
      
      $sqlfilesize = ', SUM(media.filesize) filesize';
      $sqltable = ", media, object";
      $sqlwhere = " WHERE dailystat.id = media.id";
    }
    else
    {
      $sqlfilesize = ', media.filesize';
      $sqltable = ", media";
      $sqlwhere = " WHERE dailystat.id = media.id";
    }
  }
  // object
  else
  {
    $sqlfilesize = "";
    $sqltable = "";
    $sqlwhere = " WHERE dailystat.id!=''";
  }
  
  $sql = 'SELECT dailystat.id, dailystat.date, dailystat.activity, SUM(dailystat.count) count'.$sqlfilesize.', user FROM dailystat'.$sqltable.' '.$sqlwhere; 
  
  if ($objectpath != "")
  {
    // search by objectpath
    $sql .= ' AND dailystat.id = object.id';
    
    if ($object_info['type'] == 'Folder') $sql .= ' AND object.objectpath like "'.$location.'%"';
    else $sql .= ' AND object.objectpath = "'.$objectpath.'"';
  }
  elseif ($container_id != "")
  { 
    // search by containerid
    $sql .= ' AND dailystat.id='.$container_id;
  }
  
  if ($date_from != "") $sql .= ' AND dailystat.date>="'.date("Y-m-d", strtotime($date_from)).'"';
  if ($date_to != "") $sql .= ' AND dailystat.date<="'.date("Y-m-d", strtotime($date_to)).'"';
  if ($activity != "") $sql .= ' AND dailystat.activity="'.$activity.'"';
  if ($user != "") $sql .= ' AND dailystat.user="'.$user.'"';
  $sql .= ' GROUP BY dailystat.date, dailystat.user ORDER BY dailystat.date';

  $errcode = "50039";
  $done = $db->query ($sql, $errcode, $mgmt_config['today']);

  if ($done)
  {
    $i = 0;
    
    // stats array
    while ($row = $db->getResultRow ())
    {
      $dailystat[$i]['container_id'] = $row['id'];
      $dailystat[$i]['date'] = $row['date'];
      $dailystat[$i]['activity'] = $row['activity'];
      $dailystat[$i]['count'] = $row['count'];
      $dailystat[$i]['filesize'] = $row['filesize'];
      $dailystat[$i]['user'] = $row['user'];
      $i++;
    }
  }     

  // save log
  savelog ($db->getError ());
  $db->close();
       
  if (is_array (@$dailystat)) return $dailystat;
  else return false;
}

// ----------------------------------------------- get filesize from media -------------------------------------------------

function rdbms_getfilesize ($container_id="", $objectpath="")
{
  global $mgmt_config;
  
  if ($container_id != "" || $objectpath != "")
  {
    // mySQL connect
    $db = new hcms_db ($mgmt_config['dbconnect'], $mgmt_config['dbhost'], $mgmt_config['dbuser'], $mgmt_config['dbpasswd'], $mgmt_config['dbname'], $mgmt_config['dbcharset']);    
    
    // get file size based on
    // container id
    if ($container_id != "")
    {
      $container_id = $db->escape_string ($container_id);
      
      $sqladd = ' WHERE media.id='.$container_id;
      
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
      
      $objectpath = $db->escape_string ($objectpath);
      $objectpath = str_replace ('%', '*', $objectpath);
      
      if (getobject ($objectpath) == ".folder") $objectpath = getlocation ($objectpath);
      
      $sqladd = ', object WHERE media.id = object.id';
      
      if ($object_info['type'] == "Folder") $sqladd .= ' AND object.objectpath LIKE "'.$objectpath.'%"';
      else $sqladd .= ' AND object.objectpath = "'.$objectpath.'"';
      
      $sqlfilesize = 'SUM(filesize) AS filesize';
    }
    
    $sql = 'SELECT '.$sqlfilesize.' FROM media '.$sqladd;
    
    $errcode = "50041";
    $done = $db->query ($sql, $errcode, $mgmt_config['today'], 'selectfilesize');
    
    if ($done)
    {
      $row = $db->getResultRow ('selectfilesize');
      $result['filesize'] = $row['filesize'];
      $result['count'] = 1;
    }
    
    // count files
    if ($objectpath != "" && isset ($object_info['type']) && $object_info['type'] == "Folder")
    {
      $sql = 'SELECT count(DISTINCT objectpath) AS count FROM object WHERE objectpath LIKE "'.$objectpath.'%"'; 

      $errcode = "50042";
      $done = $db->query ($sql, $errcode, $mgmt_config['today'], 'selectcount');
      
      if ($done)
      {
        $row = $db->getResultRow ('selectcount');
        $result['count'] = $row['count'];
      }
    }

    // save log
    savelog ($db->getError ());
    $db->close();
         
    if (isset ($result) && is_array ($result)) return $result;
    else return false;
  } 
  return false;
}
?>