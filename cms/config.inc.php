<?php
/*
 * This file is part of
 * hyper Content Management Server - http://www.hypercms.com
 * Copyright (c) by hyper CMS Content Management Solutions GmbH
 *
 * You should have received a copy of the License along with hyperCMS.
 */
 
// Multiple hyperCMS instances (for seperated databases, internal and external repositories) 
$mgmt_config['instances'] = false;

// Include hyperCMS Main Configuration File
// if instances are used, load configuration file of the given instance
if ($mgmt_config['instances'] && !empty ($_SESSION['hcms_instance']) && preg_match ('/^[a-z0-9-_]+$/', $_SESSION['hcms_instance']))
{
  include ("config/".$_SESSION['hcms_instance'].".inc.php");
}
// load standard configuration file
else
{
  include ("config/config.inc.php");
}
?>