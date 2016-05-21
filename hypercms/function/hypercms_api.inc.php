<?php
/*
 * This file is part of
 * hyper Content & Digital Management Server - http://www.hypercms.com
 * Copyright (c) by hyper CMS Content Management Solutions GmbH
 *
 * You should have received a copy of the License along with hyperCMS.
 */
 
// ======================================== API loader ==========================================

// include main management configuration
if (empty ($mgmt_config['abs_path_cms']) && is_file ("../config.inc.php"))
{
  require_once ("../config.inc.php");
}

// include Get API
if (is_file ($mgmt_config['abs_path_cms']."function/hypercms_get.inc.php"))
{
  require_once ($mgmt_config['abs_path_cms']."function/hypercms_get.inc.php");
}

// include hyperCMS Event System
if (is_file ($mgmt_config['abs_path_data']."eventsystem/hypercms_eventsys.inc.php"))
{
  include_once ($mgmt_config['abs_path_data']."eventsystem/hypercms_eventsys.inc.php");
}

// include Relational DB Connectivity
if ($mgmt_config['db_connect_rdbms'] != "" && is_file ($mgmt_config['abs_path_cms']."database/db_connect/".$mgmt_config['db_connect_rdbms']))
{
  require_once ($mgmt_config['abs_path_cms']."database/db_connect/".$mgmt_config['db_connect_rdbms']);
}

// include Main API
if (is_file ($mgmt_config['abs_path_cms']."function/hypercms_main.inc.php"))
{
  require_once ($mgmt_config['abs_path_cms']."function/hypercms_main.inc.php");
}

// include Security API
if (is_file ($mgmt_config['abs_path_cms']."function/hypercms_sec.inc.php"))
{
  require_once ($mgmt_config['abs_path_cms']."function/hypercms_sec.inc.php");
}

// include Set API
if (is_file ($mgmt_config['abs_path_cms']."function/hypercms_set.inc.php"))
{
  require_once ($mgmt_config['abs_path_cms']."function/hypercms_set.inc.php");
}

// include XML API
if (is_file ($mgmt_config['abs_path_cms']."function/hypercms_xml.inc.php"))
{
  include_once ($mgmt_config['abs_path_cms']."function/hypercms_xml.inc.php");
}

// include Media API
if (is_file ($mgmt_config['abs_path_cms']."function/hypercms_media.inc.php"))
{
  require_once ($mgmt_config['abs_path_cms']."function/hypercms_media.inc.php");
}

// include Link Management API
if (is_file ($mgmt_config['abs_path_cms']."function/hypercms_link.inc.php"))
{
  include_once ($mgmt_config['abs_path_cms']."function/hypercms_link.inc.php");
}

// include Metadata API
if (is_file ($mgmt_config['abs_path_cms']."function/hypercms_meta.inc.php"))
{
  require_once ($mgmt_config['abs_path_cms']."function/hypercms_meta.inc.php");
}

// include Plugin API
if (is_file ($mgmt_config['abs_path_cms']."function/hypercms_plugin.inc.php"))
{
  require_once ($mgmt_config['abs_path_cms']."function/hypercms_plugin.inc.php");
}

// include Connect API
if (is_file ($mgmt_config['abs_path_cms']."function/hypercms_connect.inc.php"))
{
  require_once ($mgmt_config['abs_path_cms']."function/hypercms_connect.inc.php");
}

// include Task API (not included in Free Edition)
if (is_file ($mgmt_config['abs_path_cms']."task/hypercms_task.inc.php"))
{
  require_once ($mgmt_config['abs_path_cms']."task/hypercms_task.inc.php");
}

// include Project API (not included in Free Edition)
if (is_file ($mgmt_config['abs_path_cms']."project/hypercms_project.inc.php"))
{
  require_once ($mgmt_config['abs_path_cms']."project/hypercms_project.inc.php");
}

// include Workflow API (not included in Free Edition)
if (is_file ($mgmt_config['abs_path_cms']."workflow/hypercms_workflow.inc.php"))
{
  require_once ($mgmt_config['abs_path_cms']."workflow/hypercms_workflow.inc.php");
}

// include Encryption API (not included in Free Edition)
if (is_file ($mgmt_config['abs_path_cms']."encryption/hypercms_encryption.inc.php"))
{
  require_once ($mgmt_config['abs_path_cms']."encryption/hypercms_encryption.inc.php");
}
// for Free Edition
elseif (is_file ($mgmt_config['abs_path_cms']."function/hypercms_encryption.inc.php"))
{
  require_once ($mgmt_config['abs_path_cms']."function/hypercms_encryption.inc.php");
}

// include Cloud Storage API (not included in Free Edition)
if (is_file ($mgmt_config['abs_path_cms']."connector/cloud/hypercms_cloud.inc.php"))
{
  require_once ($mgmt_config['abs_path_cms']."connector/cloud/hypercms_cloud.inc.php");
}

// include UI API
if (is_file ($mgmt_config['abs_path_cms']."function/hypercms_ui.inc.php"))
{
  require_once ($mgmt_config['abs_path_cms']."function/hypercms_ui.inc.php");
}

// include Report API (not included in Free Edition)
if (is_file ($mgmt_config['abs_path_cms']."report/hypercms_report.inc.php"))
{
  require_once ($mgmt_config['abs_path_cms']."report/hypercms_report.inc.php");
}

// include Im/Export API (not included in Free Edition)
if (is_file ($mgmt_config['abs_path_cms']."connector/imexport/hypercms_imexport.inc.php"))
{
  require_once ($mgmt_config['abs_path_cms']."connector/imexport/hypercms_imexport.inc.php");
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
if ((empty ($hcms_lang) || is_string ($hcms_lang)) && !empty ($lang) && is_file ($mgmt_config['abs_path_cms']."language/".getlanguagefile ($lang)))
{
  require_once ($mgmt_config['abs_path_cms']."language/".getlanguagefile ($lang));
}
?>