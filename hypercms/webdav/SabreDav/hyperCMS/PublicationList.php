<?php
class Sabre_hyperCMS_PublicationList extends Sabre_hyperCMS_Virtual_Folder
{
	function getName()
  {
		return "root";
	}	

	protected function _beforeGet()
  {
		foreach ($this->_getFunctions()->getGlobal('siteaccess') as $publication)
    {
			if ($this->_getFunctions()->isWebdavActivated($publication))
      {
				$this->addChild(new Sabre_hyperCMS_Publication($publication, $this->_getFunctions()));
			}
		}
	}
}
?>