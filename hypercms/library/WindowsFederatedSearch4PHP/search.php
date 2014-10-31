<?php
/**
 * Copyright (C) 2012 Jeff Tanner <jeff00seattle@gmail.com>
 * 
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 * search.php
 * 
 * Performs search and renders output base upon format and source request.
 * 
 * PHP Version 5
 * 
 */

error_reporting(E_ALL);

/*
 * Set error handler.
 */
require_once 'WindowsFederatedSearch_UserConfig.class.php';
require_once 'lib/OpenSearch/Support.inc.php';
set_error_handler("OpenSearch_errorHandler");

/*
 * Validate there is OpenSearch RESTful query string
 */
if ( empty($_GET['query']) ) {
    die(
        "Error: Received invalid OpenSearch request: "
        . "No query-string!"
        . PHP_EOL
    );
}

/*
 * Validate there is OpenSearch query parameter
 */
if (isset($_GET['query'])) {
    $strGetQuery=$_GET['query'];
} else {
    die(
        "Error: Received invalid OpenSearch request: "
        . "No \"query\" parameter in query-string!"
        . PHP_EOL
    );
}
/*
 * Create OpenSearch Results Handler
 * upon need: HTML or RSS
 */
require_once 'lib/OpenSearch/Result.class.php';
$objOpenSearchResult = OpenSearch_Result::createResultGenerator();

/*
 * Create Stock Search Data Source
 */
require_once 'WindowsFederatedSearch_DataSource.class.php';
$objOpenSearchDataSource = new WindowsFederatedSearch_DataSource(
    $objOpenSearchResult,
    $strGetQuery
);

/*
 * Render results.
 */
if (!$objOpenSearchDataSource->serve()) {
    die( "Error: Failed to build and render Yahoo Stock data source!");
}
?>