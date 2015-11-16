<?php
/*
 * This file is part of
 * hyper Content Management Server - http://www.hypercms.com
 * Copyright (c) by hyper CMS Content Management Solutions GmbH
 *
 * You should have received a copy of the License along with hyperCMS.
 */
 
// ======================================== API loader ==========================================

// include main management configuration
if (empty ($mgmt_config['abs_path_cms']) && is_file ("../config.inc.php"))
{
  require ("../config.inc.php");
}

// include get API
if (is_file ($mgmt_config['abs_path_cms']."function/hypercms_get.inc.php"))
{
  require_once ($mgmt_config['abs_path_cms']."function/hypercms_get.inc.php");
}

// include hyperCMS Event System
if (is_file ($mgmt_config['abs_path_data']."eventsystem/hypercms_eventsys.inc.php"))
{
  include_once ($mgmt_config['abs_path_data']."eventsystem/hypercms_eventsys.inc.php");
}

// include relational DB connectivity
if ($mgmt_config['db_connect_rdbms'] != "" && is_file ($mgmt_config['abs_path_cms']."database/db_connect/".$mgmt_config['db_connect_rdbms']))
{
  require_once ($mgmt_config['abs_path_cms']."database/db_connect/".$mgmt_config['db_connect_rdbms']);
}

// include main API
if (is_file ($mgmt_config['abs_path_cms']."function/hypercms_main.inc.php"))
{
  require_once ($mgmt_config['abs_path_cms']."function/hypercms_main.inc.php");
}

// include security API
if (is_file ($mgmt_config['abs_path_cms']."function/hypercms_sec.inc.php"))
{
  require_once ($mgmt_config['abs_path_cms']."function/hypercms_sec.inc.php");
}

// include set API
if (is_file ($mgmt_config['abs_path_cms']."function/hypercms_set.inc.php"))
{
  require_once ($mgmt_config['abs_path_cms']."function/hypercms_set.inc.php");
}

// include XML content API
if (is_file ($mgmt_config['abs_path_cms']."function/hypercms_xml.inc.php"))
{
  include_once ($mgmt_config['abs_path_cms']."function/hypercms_xml.inc.php");
}

// include media API
if (is_file ($mgmt_config['abs_path_cms']."function/hypercms_media.inc.php"))
{
  require_once ($mgmt_config['abs_path_cms']."function/hypercms_media.inc.php");
}

// include link management API
if (is_file ($mgmt_config['abs_path_cms']."function/hypercms_link.inc.php"))
{
  include_once ($mgmt_config['abs_path_cms']."function/hypercms_link.inc.php");
}

// include meta data API
if (is_file ($mgmt_config['abs_path_cms']."function/hypercms_meta.inc.php"))
{
  require_once ($mgmt_config['abs_path_cms']."function/hypercms_meta.inc.php");
}

// include plugin API
if (is_file ($mgmt_config['abs_path_cms']."function/hypercms_plugin.inc.php"))
{
  require_once ($mgmt_config['abs_path_cms']."function/hypercms_plugin.inc.php");
}

// include connect API
if (is_file ($mgmt_config['abs_path_cms']."function/hypercms_connect.inc.php"))
{
  require_once ($mgmt_config['abs_path_cms']."function/hypercms_connect.inc.php");
}

// include encryption API (not included in Free Edition)
if (is_file ($mgmt_config['abs_path_cms']."encryption/hypercms_encryption.inc.php"))
{
  require_once ($mgmt_config['abs_path_cms']."encryption/hypercms_encryption.inc.php");
}
// for Free Edition
elseif (is_file ($mgmt_config['abs_path_cms']."function/hypercms_encryption.inc.php"))
{
  require_once ($mgmt_config['abs_path_cms']."function/hypercms_encryption.inc.php");
}

// include update API
if (is_file ($mgmt_config['abs_path_cms']."function/hypercms_update.inc.php"))
{
  require_once ($mgmt_config['abs_path_cms']."function/hypercms_update.inc.php");
}

// include developer AddOns
if (is_file ($mgmt_config['abs_path_cms']."function/hypercms_dev.inc.php"))
{
  require_once ($mgmt_config['abs_path_cms']."function/hypercms_dev.inc.php");
}

// include session
if (defined ("SESSION") && constant ("SESSION") == "create" && is_file ($mgmt_config['abs_path_cms']."include/session.inc.php"))
{
  require_once ($mgmt_config['abs_path_cms']."include/session.inc.php");
  
  // get instance from session
  if (!empty ($_SESSION['hcms_instance']))
  {
    $instance_name = $_SESSION['hcms_instance'];
  }

  // if instances are used, load the main configuration file of the given instance
  if (!empty ($mgmt_config['instances']) && !empty ($instance_name) && valid_publicationname ($instance_name) && is_file ($mgmt_config['instances'].$instance_name.".inc.php"))
  {
    // in case a distributed system is used
    require_once ($mgmt_config['instances'].$instance_name.".inc.php");
  }
}

// include language file for API functions
if (empty ($hcms_lang) || !is_array ($hcms_lang))
{
  require_once ($mgmt_config['abs_path_cms']."language/".getlanguagefile (@$lang));
}
?>
