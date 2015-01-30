<?php
class Sabre_hyperCMS_Publication extends Sabre_hyperCMS_Virtual_PublicationFolder
{
	function getName()
  {
		return $this->_getPublication();
	}
	
	function createDirectory($name)
  {
		$folder = $this->_getFunctions()->getComponentFolder($this->_getPublication());
    
		if ($this->_getFunctions()->canCreateFolder($this->_getPublication(), $folder, Sabre_hyperCMS_Functions::CAT_COMP))
    {
			return $this->_getFunctions()->createFolder($this->_getPublication(), $folder, $name, Sabre_hyperCMS_Functions::CAT_COMP);
		}
    else
    {
			throw new Sabre_DAV_Exception_Forbidden();
		}
	}
	
	function createFile($name, $data = null)
  {
		$folder = $this->_getFunctions()->getComponentFolder($this->_getPublication());

		if ($this->_getFunctions()->canEditFile($this->_getPublication(), $folder, Sabre_hyperCMS_Functions::CAT_COMP))
    {
			$this->_getFunctions()->createObject($this->_getPublication(), $folder, $name, $data, Sabre_hyperCMS_Functions::CAT_COMP);
		}
    else
    {
			throw new Sabre_DAV_Exception_Forbidden();
		}
	}
	
	protected function _beforeGet()
  {
		$this->_addChildsFromDir($this->_getFunctions()->getComponentFolder($this->_getPublication()), Sabre_hyperCMS_Functions::CAT_COMP);
	}
	
	/*
	 * Old Function
	protected function _beforeGet() {
		if($this->_getFunctions()->hasAccessToComponents($this->_getPublication())) {
			$this->addChild(new Sabre_hyperCMS_ComponentList($this->_getPublication(), $this->_getFunctions()));
		}
		if($this->_getFunctions()->hasAccessToPages($this->_getPublication())) {
			$this->addChild(new Sabre_hyperCMS_SiteList($this->_getPublication(), $this->_getFunctions()));
		}
	}
	*/
}
?>
