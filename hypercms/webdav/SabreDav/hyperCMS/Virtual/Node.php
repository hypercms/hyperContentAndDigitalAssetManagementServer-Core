<?php
abstract class Sabre_hyperCMS_Virtual_Node implements Sabre_DAV_INode
{
  private $_functions = NULL;
  
  public function __construct(Sabre_hyperCMS_Functions &$functions)
  {
    $this->_setFunctions($functions);
  }
  
  /**
   * Returns a class containing the specific hypercms functions
   * @return Sabre_hyperCMS_Functions
   */
  protected function &_getFunctions()
  {
    return $this->_functions;
  }
  
  protected function _setFunctions(Sabre_hyperCMS_Functions &$functions)
  {
    $this->_functions = &$functions;
  }
}
?>