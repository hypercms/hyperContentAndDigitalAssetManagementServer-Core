<?xml version="1.0" encoding="utf-8" ?>
<template>
<name>Navigation</name>
<user>admin</user>
<category>inc</category>
<extension></extension>
<application></application>
<content><![CDATA[<!-- Navigation -->
[hyperCMS:scriptbegin

global $mgmt_config, $navi_config;

$navi_config = array();

// document root definitions
$navi_config['root_path'] = "%abs_page%/";
$navi_config['root_url'] = "%url_page%/";

// HTML / CSS class defintions
$navi_config['attr_ul_top'] = "class=\"root\"";
$navi_config['attr_ul_dropdown'] = "class=\"sub-menu\"";
$navi_config['attr_li_active'] = "class=\"current-menu-item\"";
$navi_config['attr_li_dropdown'] = "class=\"dropdown\"";
$navi_config['attr_href_dropdown'] = "";
$navi_config['tag_li'] = "<li %attr_li%><a href=\"%link%\" %attr_href%>%title%</a>%sub%</li>\n";
$navi_config['tag_ul'] = "<ul %attr_ul%>%list%</ul>\n";

// language definitions
// Session variable name that holds the language setting
$navi_config['lang_session'] = "";
// key = langcode & value = text_id of textnode
$navi_config['lang_text_id']['EN'] = "Title";

// PermaLink defintions
// key = langcode & value = text_id of textnode
$navi_config['permalink_text_id'] = "";

// Navigation hide and sort order defintions
$navi_config['hide_text_id'] = "NavigationHide";
$navi_config['sort_text_id'] = "NavigationSortOrder";

// Use the first item in a folder for the main navigation item and display all following as sub navigation items [true,false]
$navi_config['use_1st_folderitem'] = true;

// create navigation
$navigation = createnavigation ("%publication%", $navi_config['root_path'], $navi_config['root_url'], "%view%", "%abs_location%/%object%");

// display navigation
echo shownavigation ($navigation);

scriptend]
<!-- Navigation -->]]></content>
</template>