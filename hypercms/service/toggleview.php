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


// input parameters
$explorerview = getrequest ("explorerview");
$taskview = getrequest ("taskview");

// ------------------------------ permission section --------------------------------

// check session of user
checkusersession ($user);

// --------------------------------- logic section ----------------------------------

if (!empty ($explorerview)) toggleexplorerview ($explorerview);
if (!empty ($taskview)) toggletaskview ($taskview);
?>