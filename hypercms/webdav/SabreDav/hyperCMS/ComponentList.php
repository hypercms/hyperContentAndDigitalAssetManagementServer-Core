<?php
class Sabre_hyperCMS_ComponentList extends Sabre_hyperCMS_Virtual_PublicationFolder
{
	function getName()
  {
		return "Components";
	}
	
	function createDirectory($name)
  {
		return $this->_getFunctions()->createFolder($this->_getPublication(), $this->_getFunctions()->getComponentFolder($this->_getPublication()), $name, Sabre_hyperCMS_Functions::CAT_COMP);
	}
	
	function createFile($name, $data = null)
  {
		$this->_getFunctions()->createObject($this->_getPublication(), $this->_getFunctions()->getComponentFolder($this->_getPublication()), $name, $data, Sabre_hyperCMS_Functions::CAT_COMP);
	}
	
	protected function _beforeGet()
  {
		$this->_addChildsFromDir($this->_getFunctions()->getComponentFolder($this->_getPublication()), Sabre_hyperCMS_Functions::CAT_COMP);
	}
}
?>