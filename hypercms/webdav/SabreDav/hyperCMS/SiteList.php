<?php
class Sabre_hyperCMS_SiteList extends Sabre_hyperCMS_Virtual_PublicationFolder
{
	function getName()
  {
		return "Sites";
	}
	
	function createDirectory($name)
  {
		return $this->_getFunctions()->createFolder($this->_getPublication(), $this->_getFunctions()->getPageFolder($this->_getPublication()), $name, Sabre_hyperCMS_Functions::CAT_PAGE);
	}
	
	function createFile($name, $data = null)
  {
		$this->_getFunctions()->createObject($this->_getPublication(), $this->_getFunctions()->getPageFolder($this->_getPublication()), $name, $data, Sabre_hyperCMS_Functions::CAT_PAGE);
	}
	
	protected function _beforeGet()
  {
		$this->_addChildsFromDir($this->_getFunctions()->getPageFolder($this->_getPublication()), Sabre_hyperCMS_Functions::CAT_PAGE);
	}
}
?>