<?php
class Sabre_hyperCMS_Functions
{	
  const CAT_COMP = "comp";	
  const CAT_PAGE = "page";	
  private $_separator = ":||:";	
  private $_save = array();	
  private $_log = NULL;
  
  public function __construct()
  {
    $this->_log = new Sabre_hyperCMS_Log(true);
  }
  
  public function setGlobalsForConfig()
  {
    global $mgmt_config, $lang_shortcut_default, $appsupport, $mgmt_parser, $mgmt_uncompress, $mgmt_compress,
           $mgmt_docpreview, $mgmt_docoptions, $mgmt_docconvert, 
           $mgmt_imagepreview, $mgmt_imageoptions, $mgmt_mediapreview, $mgmt_mediaoptions, 
           $mgmt_mediametadata, $mgmt_maxsizepreview;
    
    $this->setGlobal('mgmt_config', $mgmt_config);
    $this->setGlobal('lang_shortcut_default', $lang_shortcut_default);
    $this->setGlobal('appsupport', $appsupport);
    $this->setGlobal('mgmt_parser', $mgmt_parser);
    $this->setGlobal('mgmt_uncompress', $mgmt_uncompress);
    $this->setGlobal('mgmt_compress', $mgmt_compress);
    $this->setGlobal('mgmt_docpreview', $mgmt_docpreview);
    $this->setGlobal('mgmt_docoptions', $mgmt_docoptions);
    $this->setGlobal('mgmt_docconvert', $mgmt_docconvert);
    $this->setGlobal('mgmt_imagepreview', $mgmt_imagepreview);
    $this->setGlobal('mgmt_imageoptions', $mgmt_imageoptions);
    $this->setGlobal('mgmt_mediapreview', $mgmt_mediapreview);
    $this->setGlobal('mgmt_mediaoptions', $mgmt_mediaoptions);
    $this->setGlobal('mgmt_mediametadata', $mgmt_mediametadata);
    $this->setGlobal('mgmt_maxsizepreview', $mgmt_maxsizepreview);
  }
  
  public function isLoggedInCmsUser($username, $passwd, $sessID)
  {
    $sessionFolder = $this->_runWithGlobals('$mgmt_config["abs_path_data"]."session/"');
    $filename = $sessionFolder.$username.".dat";
    
    if (file_exists($filename))
    {
      $session_array = @file ($filename);
      
      if ($session_array != false && sizeof ($session_array) >= 1)
      {
        foreach ($session_array as $session)
        {
          $session = trim ($session);
          list ($regsessionid, $regsessiontime, $regpasswd) = explode ("|", $session);
          // session is correct
          if ($regsessionid == $sessID && $regpasswd == $passwd) return true;
        }
      }
    }
    
    return false;
  }
  
  public function isLoggedInHyperdav($username)
  {
    $file = $this->_buildSessionFileName($username);
    
    if (file_exists($file)) return true;
    else return false;
  }
  
  public function hasAccessTo($siteName, $location, $cat)
  {
    $location = $this->_fixFolderName($location);
    //$this->getLog()->logInfo($location);
    
    $access = $this->_runFuncWithGlobals('accesspermission', array($siteName, $location, $cat));
      
    if ($this->getGlobal('user') == 'admin' || is_array($access) && !empty($access))
    {
      $localpermission = $this->_runFuncWithGlobals('setlocalpermission', array($siteName, $access, $cat));
      
      if ($this->_hasLocalAccess($siteName, $access, $cat, 'root')) return true;
    }
    
    return false;
  }
  
  public function hasAccessToComponents($siteName)
  {
    return $this->hasAccessTo($siteName, "%comp%/".$siteName."/", self::CAT_COMP);
  }
  
  public function hasAccessToPages($siteName)
  {
    return $this->hasAccessTo($siteName, "%page%/".$siteName."/", self::CAT_PAGE);
  }
  
  public function getComponentFolder($siteName)
  {
    return $this->_runWithGlobals('"{$mgmt_config["abs_path_comp"]}'.$siteName.'/"');		
  }
  
  public function getPageFolder($siteName)
  {
    $this->_loadSiteConfig($siteName);
    
    return $this->_runWithGlobals('$mgmt_config["'.$siteName.'"]["abs_path_page"]');
  }

  public function getFileContents($fileName, $site)
  {
    $result = $this->getRealFileName($fileName, $site);

    if ($result)
    {
      $fileName = $result['mediafile'];
      //return file_get_contents($fileName);
      return $this->_runFuncWithGlobals('downloadfile', array($fileName, getobject ($fileName), "noheader", $this->getGlobal('user')));
     }
    else
    {
      return NULL;
    }
  }

  public function getRealFileName($fileName, $site)
  {
    $directory = $this->extractFolderFromFileName($fileName);
    $file = $this->extractFileFromFileName($fileName);
    $filedata = $this->_runFuncWithGlobals('loadfile', array($directory, $file));
    $mediafile = $this->_runFuncWithGlobals('getfilename', array($filedata, 'media'));
    $contentfile = $this->_runFuncWithGlobals('getfilename', array($filedata, 'content'));
      
    if ($contentfile != false) $container_array = $this->_runFuncWithGlobals('getcontainername', array($contentfile));

    if ($mediafile == false || $contentfile == false || (!empty ($container_array['user']) && $container_array['user'] != $this->getGlobal('user')))
    {
      //$this->getLog()->logError("No mediafile found for {$fileName} in {$site}");
      return "";
    }
    
    $location = $this->_getMediaFolderForFile($site."/".$mediafile, $site);
    
    if ($location == "")
    {
      return "";
    }
    
    // create temp file if media file is encrypted
    $temp_source = $this->_runFuncWithGlobals('createtempfile', array($location.$site."/", $mediafile));

    if ($temp_source['result'] && $temp_source['crypted'])
    {
      $mediapath = $temp_source['templocation'].$temp_source['tempfile'];
    }
    else $mediapath = $location.$site."/".$mediafile;
    
    $result['mediafile'] = $mediapath;
    $result['mediafile_orig'] = $location.$site."/".$mediafile;
    $result['container'] = $contentfile;

    return $result;
  }
  
  public function getDisplayName($fileName)
  {
    $file = $this->extractFileFromFileName($fileName);		
    $displayName = $this->_runFuncWithGlobals('specialchr_decode', array($file));
    
    if ($displayName == false)
    {
      $displayName = utf8_encode($file);
    }
    
    if ($this->isMacFinder())
    {
      return utf8_decode($displayName);
    }
    else
    {
      return $displayName;
    }
  }  
  
  public function convertToLocal($file)
  {
    $array = explode("/", $file);
    
    foreach ($array as &$part)
    {
      $part = $this->_runFuncWithGlobals('specialchr_encode', array($part));
    }
    
    return implode("/", $array);
  }
  
  public function createFolder($site, $location, $foldername, $cat)
  {
    $this->_loadSiteConfig($site);
    $location = $this->_fixFolderName($location);

    $this->setGlobal("cat", $cat);
    
    $result = $this->_runFuncWithGlobals('createfolder', array($site, $location, $foldername, $this->getGlobal('user')));		

    $this->deleteGlobal("cat");
        
    if ($result['result'] == 1)
    {
      return true;
    }
    else
    {
      $this->getLog()->logError("Error while creating folder '{$foldername}' in {$location}");
      $this->getLog()->logError("Result: ".print_r($result, true));
      return false;
    }
  }

  public function renameFolder($site, $location, $folder, $newName, $cat)
  {
    $this->_loadSiteConfig($site);
    $location = $this->_fixFolderName($location);
    $result = $this->_runFuncWithGlobals('renamefolder', array($site, $location, $folder, $newName, $this->getGlobal('user')));
    
    if ($result['result'] == 1)
    {
      return true;
    }
    else
    {
      $this->getLog()->logError("Error while renaming folder '{$folder}' to '{$newName}' in {$location}");
      $this->getLog()->logError("Result: ".print_r($result, true));
      return false;
    }
  }

  public function deleteFolder($site, $location, $folder, $cat)
  {
    $this->_loadSiteConfig($site);    
    $this->setGlobal("cat", $cat);
    
    $compFolder = $this->getComponentFolder($site);
    $parentFolder = '%comp%/'.$site.'/'.substr($location, strpos($location, $compFolder) + strlen($compFolder));
    
    //$this->getLog()->logInfo("manipulateallobjects -> input: \"delete\", array({$parentFolder}{$folder}/), \"\", \"\", \"\", \"sys\"");

    $result = $this->_runFuncWithGlobals('manipulateallobjects', array("delete", array($parentFolder.$folder."/"), "", "start", "", $this->getGlobal('user')));
    
    $this->deleteGlobal("cat");
    
    if ($result['result'] == 1)
    {
      //$this->getLog()->logError("Result: ".print_r($result, true));
      return true;
    }
    else
    {
      $this->getLog()->logError("Error while deleting folder {$folder} in {$location}");
      $this->getLog()->logError("Result: ".print_r($result, true));
      return false;
    }
  }

  // createObject creates the file but doesn't write content to the file based on the provided resource ID by $objectContent
  public function createObject($site, $location, $objectName, $objectContent, $cat)
  {
    $location = $this->_fixFolderName($location);
    $result = $this->_createTempFile($location, $objectName, $objectContent);
    
    if ($result['result'] == 0)
    {
      $this->getLog()->logError("Error while creating tempfile for publication '{$site}', '{$cat}' in {$location}");
      $this->getLog()->logError("Result: ".print_r($result, true));
      return false;
    }
    
    $filename = $result['file'];

    $fileArray = array(
        'Filedata' => array (
            'error' => UPLOAD_ERR_OK,
            'name' => $objectName,
            'tmp_name' => $filename
          )
      );
    
    $storage = $this->_runWithGlobals('isset($mgmt_config["'.$site.'"]["storage"]) ? $mgmt_config["'.$site.'"]["storage"] : 0');
    
    if ($storage > 0)
    {
      $storage *= 1024;
      
      $filesize = $this->_runFuncWithGlobals('rdbms_getfilesize', array("", "%".$cat."%/".$site."/"));

      if ($filesize['filesize'] > $storage)
      {
        $this->getLog()->logError("Maximum Storage Limit ({$storage}|{$filesize['filesize']}) reached for publication '{$site}'");
        $this->_mailUser($site, "Storage Limit Exceeded", "You've reached the maximum amount of storage. \nThe file '".$objectName."' could not be saved.");
        return false;
      }
    }

    $result = $this->_runFuncWithGlobals('uploadfile', array($site, $location, $cat, $fileArray, "", "", "", "", "", $this->getGlobal('user'), false, false));		
    $this->_removeTempFile($location, $objectName);

    if ($result['header'] == "HTTP/1.1 200 OK")
    {
      return true;
    }
    else
    {
      $this->getLog()->logError("Error while creating new file '{$objectName}' in {$location}");
      $this->getLog()->logError("Result: ".print_r($result, true));
      return false;
    }
  }

  public function editObject($site, $location, $objectName, $objectContent, $cat)
  {
    $location = $this->_fixFolderName($location);
    $result = $this->_createTempFile($location, $objectName, $objectContent);

    if ($result['result'] == 0)
    {
      $this->getLog()->logError("Error while creating tempfile for publication '{$site}', '{$cat}' in {$location}");
      $this->getLog()->logError("Result: ".print_r($result, true));
      return false;
    }
    
    $filename = $result['file'];

    $fileArray = array(
      'Filedata' => array (
          'error' => UPLOAD_ERR_OK,
          'name' => $objectName,
          'tmp_name' => $filename
        )
    );

    // save updated media file
    $result = $this->_runFuncWithGlobals('uploadfile', array($site, $location, $cat, $fileArray, $objectName, 0, "", "", "", $this->getGlobal('user'), false, true));

    if ($result['header'] == "HTTP/1.1 200 OK")
    {
      return true;
    }
    else
    {
      $this->getLog()->logError("Error while editing '{$objectName}' in {$location}");
      $this->getLog()->logError("Result: ".print_r($result, true));
      return false;
    }
  }
  
  public function renameObject($site, $location, $object, $newName, $cat)
  {
    $this->_loadSiteConfig($site);
    $location = $this->_fixFolderName($location);		
    $dotpos = strrpos($newName, ".");
    
    if ($dotpos !== false)
    {
      $newName = substr($newName, 0, $dotpos);
    }

    $result = $this->_runFuncWithGlobals('renameobject', array($site, $location, $object, $newName, $this->getGlobal('user')));
    
    if ($result['result'] == true)
    {
      return true;
    }
    else
    {
      $this->getLog()->logError("Error renaming file {$object} to {$newName} in {$location}");
      $this->getLog()->logError("Result: ".print_r($result, true));
      return false;
    }
  }
  
  public function deleteObject ($site, $location, $object, $cat)
  {
    $this->_loadSiteConfig($site);
    $location = $this->_fixFolderName($location);

    $result = $this->_runFuncWithGlobals('deleteobject', array($site, $location, $object, $this->getGlobal('user')));
        
    if ($result['result'] == 1)
    {
      return true;
    }
    else
    {
      $this->getLog()->logError("Error deleting file {$object} in {$location}");
      $this->getLog()->logError("Result: ".print_r($result, true));
      return false;
    }
  }
  
  public function setGlobal($varName, $value)
  {
    $this->_save[$varName] = $value;
  }
  
  public function getGlobal($varName)
  {
    if (array_key_exists($varName, $this->_save))
    {
      return $this->_save[$varName];
    }
    return NULL;
  }
  
  public function deleteGlobal($varName)
  {
    if (array_key_exists($varName, $this->_save))
    {
      unset($this->_save[$varName]);
    }
  }
  
  public function writeSessionDataToFile($username)
  {
    $fileName = $this->_buildSessionFileName($username);
    $leaveAlone = array("auth", "html", 'passwd');		
    $result = $this->_runFuncWithGlobals("userlogin", array($username, "", NULL, NULL, NULL, true));
    
    if ($result['auth'])
    {
      $save = array();
      
      foreach($result as $key => $value)
      {
        if (!in_array($key, $leaveAlone))
        {
          $this->setGlobal($key, $value);
          $save[] = $key.$this->_separator.serialize($value);
        }
      }
      
      $return = file_put_contents($fileName, implode("\n", $save));			
      if ($return !== false) return true;
    }
    
    $this->getLog()->logError("User {$username} not correctly authenticated");
    
    return false;
  }
  
  public function readSessionDataFromFile($username)
  {
    $fileName = $this->_buildSessionFileName($username);		
    $filedata = file_get_contents($fileName);
    
    foreach(explode("\n", $filedata) as $line)
    {
      list($key, $value) = explode($this->_separator, $line);
      $value = unserialize($value);
      $this->setGlobal($key, $value);
    }
    
    return true;
  }
  
  /**
   * @return Sabre_hyperCMS_Log
   */
  public function &getLog()
  {
    return $this->_log;
  }
  
  public function convertFromWindows($name)
  {
    if ($name == "Neuer Ordner")
    {
      $newName = "NeuerOrdner";
    }
    else
    {
      $newName = preg_replace("/^Neuer Ordner( \(([0-9]+)\))*$/", "NeuerOrdner\${2}", $name);
    }
    //$this->getLog()->logInfo("Converting from Windows '{$name}' to our '{$newName}'");
    return $newName; 
  }
  
  public function convertToWindows($name)
  {
    if ($name == "NeuerOrdner")
    {
      $newName = "Neuer Ordner";
    }
    else
    {
      $newName = preg_replace("/^NeuerOrdner([0-9]+)*$/", "Neuer Ordner (\${1})", $name);
    }
    //$this->getLog()->logInfo("Converting from our '{$name}' to Windows '{$newName}'");
    return $newName;
  }
  
  public function isWindowsExplorer()
  {
    return preg_match("/Microsoft-WebDAV-MiniRedir[0-9\.]+/", $_SERVER['HTTP_USER_AGENT']);
  }
  
  public function isMacFinder()
  {
    //$this->getLog()->logInfo($_SERVER['HTTP_USER_AGENT']);
    return preg_match("/WebDAVFS\/[0-9\.]+ \([0-9]+\) Darwin\/[0-9\.]+/ ", $_SERVER['HTTP_USER_AGENT']);
  }
  
  public function extractFolderFromFileName($fileName)
  {
    return substr($fileName, 0, strrpos($fileName, "/")+1);
  }
  
  public function extractFileFromFileName($fileName)
  {
    return substr($fileName, strrpos($fileName, "/")+1);
  } 
  
  public function isWebdavActivated($publication)
  {
    $this->_loadSiteConfig($publication);
    $mgmt = $this->getGlobal('mgmt_config');
    return ($mgmt[$publication]['webdav']) ? true : false;
  }
  
  public function getChildsForLocation($site, $location, $cat)
  {
    $location = $this->_fixFolderName($location);
    $return = array();
    
    if ($this->hasAccessTo($site, $location, $cat))
    {
      $dirLink = opendir($location);
      
      $ignorefiles = array(
          '.',
          '..',
          '.folder'
      );			
      
      if (is_resource($dirLink))
      {
        while ($file = readdir($dirLink))
        {
          // ignore these files although hasAccessTo should already ignore them ....
          if (!in_array($file, $ignorefiles))
          {
            if (is_dir($location.$file))
            {
              // only where we have access
              if ($this->hasAccessTo($site, $location.$file, $cat))
              {
                $return[] = new Sabre_hyperCMS_Folder($site, $this, $location.$file, $cat);
              }
            }
            else
            {
              // only display media files, no cms files!
              $result = $this->getRealFileName($location.$file, $site);
              
              if (is_array ($result) && $result['mediafile'] != "")
              {
                $return[] = new Sabre_hyperCMS_File($site, $this, $location.$file, $cat);
              }
            }
          }
        }
          
      }
      else
      {
        throw new Sabre_DAV_Exception("Internal Error");
      }
      closedir($dirLink);
    }
    
    $compaccess = $this->getGlobal('compaccess');
    
    foreach($compaccess[$site] as $group => $path)
    {			
      if ($this->_hasLocalAccess($site, array($group), $cat, 'root'))
      {
        $pathArray = link_db_getobject ($path);
        
        foreach ($pathArray as $path2)
        {
          $path2 = $this->_runFuncWithGlobals('deconvertpath', array($path2, 'file'));
          
          if (substr($path2, 0, strlen($location)) == $location && $path2 !== $location)
          {
            $return[] = new Sabre_hyperCMS_Folder($site, $this, substr($path2, 0, strlen($path2)-1), $cat);
          }
        }
      } 
    }
    
    return $return;
  }
  
  public function canViewFile($site, $location, $cat)
  {
    return $this->_hasLocalAccessToLocation($site, $location, $cat, 'download');
  }
  
  public function canEditFile($site, $location, $cat)
  {
    return $this->_hasLocalAccessToLocation($site, $location, $cat, 'upload');
  }
  
  public function canDeleteFile($site, $location, $cat)
  {
    return $this->_hasLocalAccessToLocation($site, $location, $cat, 'delete');
  }
  
  public function canRenameFile($site, $location, $cat)
  {
    return $this->_hasLocalAccessToLocation($site, $location, $cat, 'rename');
  }
  
  public function canCreateFolder($site, $location, $cat)
  {
    return $this->_hasLocalAccessToLocation($site, $location, $cat, 'foldercreate');
  }
  
  public function canRenameFolder($site, $location, $cat)
  {
    return $this->_hasLocalAccessToLocation($site, $location, $cat, 'folderrename');
  }
  
  public function canDeleteFolder($site, $location, $cat)
  {
    return $this->_hasLocalAccessToLocation($site, $location, $cat, 'folderdelete');
  }
  
  protected function _hasLocalAccessToLocation($site, $location, $cat, $right)
  {
    $location = $this->_fixFolderName($location);
    $access = $this->_runFuncWithGlobals('accesspermission', array($site, $location, $cat));
    //$this->getLog()->logInfo("Access($site, $location, $cat, $right): ".print_r($access, true));
    if (is_array($access) && !empty($access))
    {
      return $this->_hasLocalAccess($site, $access, $cat, $right);
    }
    
    return false;
  }
  
  protected function _hasLocalAccess($site, $groupArray, $cat, $right)
  {
    if ($this->getGlobal('user') == 'admin') return true;
    
    $localpermission = $this->_runFuncWithGlobals('setlocalpermission', array($site, $groupArray, $cat));
    //$this->getLog()->logInfo("\$localpermission[$right] => {$localpermission[$right]}");
    if ($localpermission[$right] == 1) return true;
    else return false;
  }
  
  protected function _loadSiteConfig($siteName)
  {
    $mgmt_config = $this->getGlobal('mgmt_config');
    
    if ((!isset ($mgmt_config[$siteName]) || !is_array ($mgmt_config[$siteName])) && is_file ($mgmt_config['abs_path_data']."config/".$siteName.".conf.php"))
    {
      require ($mgmt_config['abs_path_data']."config/".$siteName.".conf.php");
      $this->setGlobal('mgmt_config', $mgmt_config);
      return true;
    }
    
    return false;
  }
  
  protected function _getMediaFolderForFile($fileName, $site)
  {
    $medialoc = $this->_runFuncWithGlobals('getmedialocation', array($site, $fileName, "abs_path_media"));
    $file2 = $this->_runWithGlobals('"{$mgmt_config["abs_path_tplmedia"]}'.$fileName.'"');
    
    if (@file_exists ($medialoc.$fileName))
    {
      return $medialoc;
    }
    elseif (@file_exists ($file2))
    {
      return $this->_runWithGlobals('$mgmt_config["abs_path_tplmedia"]');
    }
    else
    {
      return "";
    }
  }
  
  protected function _createTempFile($folder, $fileName, $content)
  {
    $tmpDir = $this->_getTmpFolder($folder, $fileName);
    @mkdir($tmpDir, $this->_runWithGlobals('$mgmt_config["fspermission"]'));
    
    if (!is_dir($tmpDir))
    {
      return array('result' => 0, 'message' => "Can't create temporary directory!");
    }
    
    $return = file_put_contents($tmpDir.$fileName, $content);

    if ($return === false)
    {
      return array('result' => 0, 'message' => "Could not put content into temporary file!");
    }
    
    return array('result' => 1, 'file' => $tmpDir.$fileName);
  }
  
  protected function _removeTempFile($folder, $fileName)
  {
    $tmpDir = $this->_getTmpFolder($folder, $fileName);
    
    if (is_file($tmpDir.$fileName))
    {
      unlink($tmpDir.$fileName);
    }
    
    rmdir($tmpDir);
  }
  
  protected function _getTmpFolder($folder, $fileName)
  {
    $folder = md5(md5($folder).md5($fileName));
    return $this->_runWithGlobals('"{$mgmt_config["abs_path_temp"]}'.$folder."/\"");
  }
  
  protected function _runWithGlobals($call)
  {
    foreach($this->_save as $name => $value)
    {
      if (!empty($value))
      {
        $$name = $value;
        $GLOBALS[$name] = $value;
      }
    }

    eval("\$return = ".$call.";");
    return $return;
  }
  
  protected function _runFuncWithGlobals($func, $params)
  {			
    foreach ($this->_save as $name => $value)
    {
      if ($pos = array_search('GLOB_'.$name, $params)) $params[$pos] = $value;

      $$name = $value;
      $GLOBALS[$name] = $value;
    }		
    
    require_once($mgmt_config['abs_path_cms']."function/hypercms_api.inc.php");
        
    //$this->getLog()->logInfo("CALLING: $func(".implode(", ", $params).")");
    $return = call_user_func_array($func, $params);
    
    return $return;
  }
  
  protected function _buildSessionFileName($username)
  {
    $sessionFolder = $this->_runWithGlobals('$mgmt_config["abs_path_data"]."session/"');
    $filepart[] = 'hyperdav';
    $filepart[] = $username;
        
    return $sessionFolder.implode("_", $filepart).".dat";
  }
  
  protected function _fixFolderName($folderName)
  {
    if (strrpos($folderName, "/") != (strlen($folderName)-1)) return $folderName."/";
    return $folderName;
  }
  
  protected function _mailUser($site, $title, $body) 
  {
    $datapath = $this->_runWithGlobals('$mgmt_config["abs_path_data"]');
    $userdata = $this->_runFuncWithGlobals('loadfile', array ($datapath."user/", "user.xml.php"));
    
    if ($userdata)
    {
      $userrecord = $this->_runFuncWithGlobals('selectcontent', array ($userdata, "<user>", "<login>", "GLOB_user"));
      
      if ($userrecord[0])
      {
        $emailarray = getcontent ($userrecord[0], "<email>");
        $email = $emailarray[0]; 
      }
    }
        
    // mailer class
    $this->_runWithGlobals('require_once ($mgmt_config["abs_path_cms"]."function/hypermailer.class.php")');

    $mailer = new HyperMailer();
    $codepage = $this->getGlobal('lang_codepage');
    $charset = $mailer->CharSet = $codepage[$this->getGlobal( 'lang' )];

    $mailer->From = $this->_runWithGlobals('"automailer@".$mgmt_config["'.$site.'"]["mailserver"]');
    $mailer->FromName = "hyperCMS Automailer";
    
    if ($email != "") $mailer->AddAddress($email);
    
    $mailer->AddCC ("support@hypercms.net", "HyperCMS Support");
    
    $mailer->Subject = html_decode ($title, $charset);
    $mailer->Body = html_decode ($body, $charset);
    
    if ($mailer->Send())
    {
      $this->getLog()->logInfo("Email successfully sent to support@hypercms.net".($email != "" ? "and ".$email : ""));
      return true;
    }
    else
    {
      $this->getLog()->logError("Could not send email to support@hypercms.net".($email != "" ? "and ".$email : ""));
      $this->getLog()->logError("Error: ".var_export($mailer->ErrorInfo, true));
      return false;
    }
  }
}
?>