<?php
/*
 * This file is part of
 * hyper Content Management Server - http://www.hypercms.com
 * Copyright (c) by hyper CMS Content Management Solutions GmbH
 *
 * You should have received a copy of the License along with hyperCMS.
 */
 
// ======================================== API loader ==========================================

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

// include get information API
if (is_file ($mgmt_config['abs_path_cms']."function/hypercms_get.inc.php"))
{
  require_once ($mgmt_config['abs_path_cms']."function/hypercms_get.inc.php");
}

// include set information API
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

// include update API
if (is_file ($mgmt_config['abs_path_cms']."function/hypercms_update.inc.php"))
{
  require_once ($mgmt_config['abs_path_cms']."function/hypercms_update.inc.php");
}

// include developer AddOn
if (is_file ($mgmt_config['abs_path_cms']."function/hypercms_dev.inc.php"))
{
  require_once ($mgmt_config['abs_path_cms']."function/hypercms_dev.inc.php");
}
?>
