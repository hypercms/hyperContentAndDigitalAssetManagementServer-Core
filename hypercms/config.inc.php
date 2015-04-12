<?php
/*
 * This file is part of
 * hyper Content Management Server - http://www.hypercms.com
 * Copyright (c) by hyper CMS Content Management Solutions GmbH
 *
 * You should have received a copy of the License along with hyperCMS.
 */

// load standard main configuration file to read instance setting
require_once ("config/config.inc.php");

// get instance from session
if (!empty ($_SESSION['hcms_instance']))
{
  $instance_name = $_SESSION['hcms_instance'];
}

// if instances are used, load the main configuration file of the given instance
if (!empty ($mgmt_config['instances']) && !empty ($instance_name) && preg_match ('/^[a-z0-9-_]+$/', $instance_name) && is_file ($mgmt_config['instances'].$instance_name.".inc.php"))
{
  // in case a distributed system is used
  require_once ($mgmt_config['instances'].$instance_name.".inc.php");
}
?>