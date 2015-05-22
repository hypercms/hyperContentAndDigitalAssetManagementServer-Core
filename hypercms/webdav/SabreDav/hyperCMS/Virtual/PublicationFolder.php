<?php
abstract class Sabre_hyperCMS_Virtual_PublicationFolder extends Sabre_hyperCMS_Virtual_Folder
{
  private $_publication;
  
  public function __construct($publication, Sabre_hyperCMS_Functions $functions)
  {
    parent::__construct($functions);
    $this->_publication = $publication;
  }
  
  protected function _getPublication()
  {
    return $this->_publication;
  }
  
  protected function _addChildsFromDir($dir, $cat)
  {
    //$this->_getFunctions()->getLog()->logInfo("Adding $dir");
    $childs = $this->_getFunctions()->getChildsForLocation($this->_getPublication(), $dir, $cat);
    
    foreach($childs as $child)
    {
      $this->addChild($child);
    }
  }
}
?>