<?php
/*
 * This file is part of
 * hyper Content & Digital Management Server - http://www.hypercms.com
 * Copyright (c) by hyper CMS Content Management Solutions GmbH
 */

// main configuration file must exist
if (is_file ("../config/config.inc.php"))
{
  // management configuration
  require ("../config.inc.php");
  // hyperCMS API
  require ("../function/hypercms_api.inc.php");

  // ------------------------------------------- SOFTWARE UPDATE ---------------------------------------------

  // software updates require the executing user to have write permissions in order to overwrite the existing system files
  update_software ("update");
}
else echo "Main configuration file is missing";
?>