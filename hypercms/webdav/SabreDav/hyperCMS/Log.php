<?php
class Sabre_hyperCMS_Log
{
	private $_log = array();	
	private $_folder = '/tmp/';	
	private $_prename = 'webdav_';	
	private $_postname = '.log';	
	private $_active = false;	
	private $_fallbackFolder = "/tmp/";
	
	public function __construct($active)
  {
		if ($active)
    {
			$this->_active = true;
		}
    else
    {
			$this->_active = false;
		}
	}
	
	protected function _logLine($type, $line)
  {
		if (!array_key_exists($type, $this->_log))
    {
			$this->_log[$type] = array();
		}
    
		$this->_log[$type][] = $line;
	}
	
	public function logError($msg)
  {
		$this->_logLine("error", $msg);
	}
	
	public function logInfo($msg)
  {
		$this->_logLine("info", $msg);
	}
	
	public function __destruct()
  {
		if ($this->_active)
    {
			foreach ($this->_log as $type => $msg)
      {
				if (!empty($msg))
        {
					$fname = $this->_prename.$type.$this->_postname;
					// if we can't open the file we fallback
					$handler = fopen($this->_folder.$fname, "a");
          
					if (!$handler)
          {
						$handler = fopen($this->_fallbackFolder."hyperdav_fallback_".$fname, "a");
					}
          
					fwrite($handler, date("Y-m-d H:i:s")."\n");
					fwrite($handler, implode(" \n", $msg));
					fwrite($handler, "\n----\n");
					fclose($handler);
				}
			}
		}
	}
	
	public function setLogFolder($folder)
  {
		if (is_dir($folder) && is_writable($folder))
    {
			$this->_folder = $folder;
		}
    else
    {
			throw new Sabre_DAV_Exception_Forbidden("Log Folder coulnd not be accessed. ($folder)");
		}
	}
}
?>