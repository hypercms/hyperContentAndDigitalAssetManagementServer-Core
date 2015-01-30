<?php
class Sabre_hyperCMS_File extends Sabre_hyperCMS_Virtual_Node implements Sabre_DAV_IFile
{
  protected $_filename;
  protected $_publication;
  protected $_category;
  
  public function __construct($publication, Sabre_hyperCMS_Functions $functions, $filename, $cat)
  {
    parent::__construct($functions);
    $this->_publication = $publication;
    $this->_filename = $filename;
    $this->_category = $cat;
  }
  
  function put($data)
  {
    if ($this->_getFunctions()->canEditFile($this->_publication, $this->_filename, $this->_category))
    {
        $dir = $this->_getFunctions()->extractFolderFromFileName($this->_filename);
        $file = $this->_getFunctions()->extractFileFromFileName($this->_filename);
        $this->_getFunctions()->editObject($this->_publication, $dir, $file, $data, $this->_category);
    }
    else
    {
      $this->_getFunctions()->getLog()->logError("Putting content into '{$this->_filename}' prevented for user ".$this->_getFunctions()->getGlobal('user'));
      throw new Sabre_DAV_Exception_Forbidden();
    }
  }	
  
  function get()
  {
    if ($this->_getFunctions()->canViewFile($this->_publication, $this->_filename, $this->_category))
    {
      return $this->_getFunctions()->getFileContents($this->_filename, $this->_publication);
    }
    else
    {
      $this->_getFunctions()->getLog()->logError("Fetching content from '{$this->_filename}' prevented for user ".$this->_getFunctions()->getGlobal('user'));
      throw new Sabre_DAV_Exception_Forbidden();
    }
  }
  
  function getContentType()
  {
    $result = $this->_getFunctions()->getRealFileName($this->_filename, $this->_publication);
    
    if ($result)
    {
      $fileName = $result['mediafile'];
      
      // First approach only works when finfo is installed
      if (function_exists('finfo_open') && function_exists('finfo_file') && function_exists('finfo_close'))
      {
        $finfo = finfo_open(FILEINFO_MIME_TYPE); 
        $ftype = finfo_file($finfo, $fileName);
        finfo_close($finfo);
      }
      else
      {
        $ftype = mime_content_type($fileName);
      }
      
      return $ftype;
    }
    else
    {
      $this->_getFunctions()->getLog()->logError("Couldn't determine the mediafile for {$this->_filename}");
      throw new Sabre_DAV_Exception("Internal Error");
    }
  }
  
  function getETag()
  {
    $tag = md5($this->_filename.filemtime($this->_filename));
    return $tag;
  }
  
  function getSize()
  {
    $result = $this->_getFunctions()->getRealFileName($this->_filename, $this->_publication);

    if ($result)
    { 
      $fileName = $result['mediafile'];
      return filesize($fileName);
    }
    else
    {
      return 0;
    }
  }
  
  function delete()
  {
    if ($this->_getFunctions()->canDeleteFile($this->_publication, $this->_filename, $this->_category))
    {
      $dir = $this->_getFunctions()->extractFolderFromFileName($this->_filename);
      $file = $this->_getFunctions()->extractFileFromFileName($this->_filename);
      $this->_getFunctions()->getLog()->logInfo("Delete on file {$this->_filename} called by ".$this->_getFunctions()->getGlobal('user'));
      return $this->_getFunctions()->deleteObject($this->_publication, $dir, $file, $this->_category);
    }
    else
    {
      $this->_getFunctions()->getLog()->logError("Delete of {$this->_filename} prevented for ".$this->_getFunctions()->getGlobal('user'));
      throw new Sabre_DAV_Exception_Forbidden();
    }
  }
  
  function getName()
  {
    $name = $this->_getFunctions()->getDisplayName($this->_filename);
    //$this->_getFunctions()->getLog()->logInfo("Getting Name of {$this->_filename} = {$name} (".($this->_getFunctions()->isMacFinder() ? 'MacFinder' : 'other').")");
    return $name;
  }
  
  function setName($name)
  { 
    if ($this->_getFunctions()->canEditFile($this->_publication, $this->_filename, $this->_category))
    {
      $dir = $this->_getFunctions()->extractFolderFromFileName($this->_filename);
      $file = $this->_getFunctions()->extractFileFromFileName($this->_filename);
      return $this->_getFunctions()->renameObject($this->_publication, $dir, $file, $name, $this->_category);
    }
    else
    {
      $this->_getFunctions()->getLog()->logError("Setting Name prevented from '{$this->_filename}' to {$name} by ".$this->_getFunctions()->getGlobal('user'));
      throw new Sabre_DAV_Exception_Forbidden();
    }
  }
  
  function getLastModified()
  {
    $fileData = $this->_getFunctions()->getRealFileName($this->_filename, $this->_publication);
    
    if (!empty ($fileData['mediafile_orig'])) $time = filemtime($fileData['mediafile_orig']);
    else $time = filemtime($fileData['mediafile']);
    
    if ($this->_getFunctions()->isMacFinder())
    {
      $time = $time - (((int)date('P', $time))*60*60);
    }
    
    return $time;
  }
}
?>