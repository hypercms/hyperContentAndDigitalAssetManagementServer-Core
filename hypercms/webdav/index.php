<?php
/*
 * This file is part of
 * hyper Content Management Server - http://www.hypercms.com
 * Copyright (c) by hyper CMS Content Management Solutions GmbH
 *
 * You should have received a copy of the License along with hyperCMS.
 */
 
// start session
session_name ("hyperCMS");
session_start();

// load SabreDAV
require_once ("SabreDav/autoload.php");
// main management config
require_once ("../config.inc.php");

date_default_timezone_set ('Europe/Vienna');

// Which basefolder do we have
$base = $mgmt_config['url_path_cms']."webdav/";
$base = substr ($base, strpos ($base, "://") + 3);
$base = substr ($base, strpos ($base, "/"));

if (strpos ($_SERVER['REQUEST_URI'], $base) == 0) $here = $base;

// Configuring main Globals for our function calls
$hcms_func = new Sabre_hyperCMS_Functions();
$hcms_func->setGlobalsForConfig();
$hcms_func->getLog()->setLogFolder($mgmt_config["abs_path_data"].'/log/');

$auth = new Sabre_hyperCMS_Auth($hcms_func, 'hyperdav');
$root = new Sabre_hyperCMS_PublicationList($hcms_func);

$server = new Sabre_DAV_Server($root);
$server->setBaseUri($here);

// Authentication
$server->addPlugin($auth);

// Support for LOCK and UNLOCK
$lockBackend = new Sabre_DAV_Locks_Backend_File($mgmt_config["abs_path_cms"].'temp/.lockDB');
$lockPlugin = new Sabre_DAV_Locks_Plugin($lockBackend);
$server->addPlugin($lockPlugin);

// Support for html frontend
// $browser = new Sabre_DAV_Browser_Plugin();
// $server->addPlugin($browser);

// Start WebDAV Server
$server->exec();
?>
