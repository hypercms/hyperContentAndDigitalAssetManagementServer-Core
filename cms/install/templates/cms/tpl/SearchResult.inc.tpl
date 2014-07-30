<?xml version="1.0" encoding="utf-8" ?>
<template>
<name>SearchResult</name>
<user>admin</user>
<category>inc</category>
<extension></extension>
<application></application>
<content><![CDATA[<! -- search result begin -->
<?php
$site = "%publication%";

include_once ("%abs_rep%/search/search_api.inc.php");

$query = (!empty ($_REQUEST['s'])) ? $_REQUEST['s'] : "";
$start = (!empty ($_REQUEST['start'])) ? $_REQUEST['start'] : "";
$exclude_url = (!empty ($_REQUEST['exclude_url'])) ? $_REQUEST['exclude_url'] : "";

searchindex ($query, $start, $exclude_url, "en", "UTF-8");
?>
<! -- search result end -->]]></content>
</template>