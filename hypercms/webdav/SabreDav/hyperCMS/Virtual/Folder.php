<?php
abstract class Sabre_hyperCMS_Virtual_Folder extends Sabre_hyperCMS_Virtual_Node implements Sabre_DAV_ICollection
{
  private $_childs = array();
  
  function createFile($name, $data = null)
  {
    throw new Sabre_DAV_Exception_Forbidden("Not Allowed!");
  }
  
  function createDirectory($name)
  {
    throw new Sabre_DAV_Exception_Forbidden("Not Allowed!");
  }
  
  function delete()
  {
    throw new Sabre_DAV_Exception_Forbidden("Not Allowed!");
  }
  
  function setName($name)
  {
    throw new Sabre_DAV_Exception_Forbidden("Not Allowed!");
  }
  
  function getChild($name)
  {
    $this->_beforeGet();
    
    if ($this->childExists($name))
    {
      return $this->_childs[$name];
    }
    else
    {
      throw new Sabre_DAV_Exception_FileNotFound(htmlentities($name)." doesn't exist!");
    }
  }
  
  function getChildren()
  {
    $this->_beforeGet();
    
    return array_values($this->_childs);
  }	
  
  function childExists($name)
  {
    $this->_beforeGet();
    
    return array_key_exists($name, $this->_childs);
  }

  function addChild(Sabre_DAV_INode $child)
  {
    $this->_childs[$child->getName()] = $child; 
  }

  protected function _resetChilds()
  {
    $this->_childs = array();
  }
  
  abstract protected function _beforeGet();

  function getLastModified()
  {
    return "";
  }
}
?>