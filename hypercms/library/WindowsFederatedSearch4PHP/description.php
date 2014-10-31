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
 * description.php
 * 
 * Generates a OpenSearch Description.
 * 
 * Performs search and renders output base upon format and source request.
 * 
 * PHP Version 5
 */

require_once 'WindowsFederatedSearch_UserConfig.class.php';
require_once 'lib/OpenSearch/DescriptionDoc.class.php';

$tmp=new OpenSearch_DescriptionDoc( 
        UserConfigConstants::OPEN_SEARCH_SHORT_NAME, 
        UserConfigConstants::OPEN_SEARCH_DESCRIPTION
    );

$strProtocol=ServiceConstants::PROTOCOL_HTTP;

$strHost = ServiceConstants::SERVER_NAME;
if (isset($_SERVER['SERVER_NAME'])) {
	$strHost=$_SERVER['SERVER_NAME'];
}

if (isset($_SERVER['SERVER_PORT'])) {
	$intPort=$_SERVER['SERVER_PORT'];
}
$strPort = ($intPort != 80) ? ":{$intPort}" : "";

$strRelativeDir=dirname($_SERVER['PHP_SELF']);
$strFullPath=$strProtocol.$strHost.$strPort.$strRelativeDir;

$strEndpointURL = "{$strFullPath}/search.php?query={searchTerms}";
$strEndpointURL .= "&amp;format=html";
$strEndpointURL .= "&amp;src={referrer:source?}";
$strEndpointURL .= "&amp;start={startIndex}";
$strEndpointURL .= "&amp;cnt={count}";
$tmp->addUrl($strEndpointURL, "text/html");

$strFaviconPath = "{$strFullPath}/favicon.ico";
$tmp->addImage($strFaviconPath, '', 16, 16);
$tmp->addLanguage('*');
$tmp->addNeedReferrerExtension(true);
$tmp->addNeedTimeExtension(true);

$tmp->serve();
?>