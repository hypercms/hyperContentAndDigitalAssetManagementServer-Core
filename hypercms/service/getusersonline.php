<?php
/*
 * This file is part of
 * hyper Content & Digital Management Server - http://www.hypercms.com
 * Copyright (c) by hyper CMS Content Management Solutions GmbH
 */

// session
define ("SESSION", "create");
// management configuration
require ("../config.inc.php");
// hyperCMS API
require ("../function/hypercms_api.inc.php");


// ------------------------------ permission section --------------------------------

// check session of user
checkusersession ($user);

// --------------------------------- logic section ----------------------------------

$data = array();

$data['usersonline'] = getusersonline ($siteaccess);

if (!empty ($data['usersonline'])) $data['success'] = true;
else $data['success'] = false;

header ('Content-Type: application/json; charset=utf-8');
print json_encode ($data);
?>