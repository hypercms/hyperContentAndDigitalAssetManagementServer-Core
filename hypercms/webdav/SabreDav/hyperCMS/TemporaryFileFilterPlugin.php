<?php
class Sabre_hyperCMS_TemporaryFileFilterPlugin extends Sabre_DAV_TemporaryFileFilterPlugin
{
	public function __construct($dataDir = null)
  {
		$this->temporaryFilePatterns[] = '/^\.(.*)-Spotlight$/';
		parent::__construct($dataDir);
	}
}
?>