<?php
class Sabre_hyperCMS_Folder extends Sabre_hyperCMS_Virtual_PublicationFolder
{
	private $_directory;
	private $_category;
	
	public function __construct($publication, Sabre_hyperCMS_Functions $functions, $directory, $cat)
  {
		parent::__construct($publication, $functions);
		$this->_directory = $directory;
		$this->_category = $cat;
	}
	
	function getName()
  {
		$name = $this->_getFunctions()->getDisplayName($this->_directory);
		return $name;
	}
	
	public function getLastModified()
  {
		return filemtime($this->_directory);
	}

	function createDirectory($name)
  {
		if ($this->_getFunctions()->canCreateFolder($this->_getPublication(), $this->_directory, $this->_category))
    {
			return $this->_getFunctions()->createFolder($this->_getPublication(), $this->_directory, $name, $this->_category);
		}
    else
    {
			throw new Sabre_DAV_Exception_Forbidden();
		}
	}
	
	function delete()
  {
		if ($this->_getFunctions()->canDeleteFolder($this->_getPublication(), $this->_directory, $this->_category))
    {
			$dir = $this->_getFunctions()->extractFolderFromFileName($this->_directory);
			$dirName = $this->_getFunctions()->extractFileFromFileName($this->_directory);
			$this->_getFunctions()->getLog()->logInfo("DELETE on folder {$this->_directory} called ".$this->_getFunctions()->getGlobal('user'));
			return $this->_getFunctions()->deleteFolder($this->_getPublication(), $dir, $dirName, $this->_category);
		} else {
			throw new Sabre_DAV_Exception_Forbidden();
		}
	}
	
	function setName($name)
  {
		if ($this->_getFunctions()->canCreateFolder($this->_getPublication(), $this->_directory, $this->_category))
    {
			$dir = $this->_getFunctions()->extractFolderFromFileName($this->_directory);
			$dirName = $this->_getFunctions()->extractFileFromFileName($this->_directory);
			return $this->_getFunctions()->renameFolder($this->_getPublication(), $dir, $dirName, $name, $this->_category);
		}
    else
    {
			throw new Sabre_DAV_Exception_Forbidden();
		}
	}
	
	function createFile($name, $data = null)
  {
		if ($this->_getFunctions()->canEditFile($this->_getPublication(), $this->_directory, $this->_category))
    {
			$this->_getFunctions()->createObject($this->_getPublication(), $this->_directory, $name, $data, $this->_category);
		}
    else
    {
			$this->_getFunctions()->getLog()->logError("Creating {$this->_directory}/{$name} prevented for ".$this->_getFunctions()->getGlobal('user'));
			throw new Sabre_DAV_Exception_Forbidden();
		}
	}
	
	protected function _beforeGet()
  {
		$this->_addChildsFromDir($this->_directory, $this->_category);
	}
}
?>