<?php
/*
 * This file is part of
 * hyper Content & Digital Management Server - http://www.hypercms.com
 * Copyright (c) by hyper CMS Content Management Solutions GmbH
 *
 * You should have received a copy of the license (license.txt) along with hyper Content & Digital Management Server
 */

// session
define ("SESSION", "create");
// management configuration
require ("../config.inc.php");
// hyperCMS API
require ("../function/hypercms_api.inc.php");
// template engine
require ("../function/hypercms_tplengine.inc.php");


// ------------------------------ permission section --------------------------------

// check session of service
checkusersession ($user);

// --------------------------------- logic section ----------------------------------

// write and close session (non-blocking other frames)
$session_id = suspendsession ();

if (is_facerecognition ($user))
{
  // exclude publications
  $sql_where = "";

  if (!empty ($mgmt_config['facerecognition_service_exclude']))
  {
    $temp_array = explode (";", trim ($mgmt_config['facerecognition_service_exclude'], ";"));

    foreach ($temp_array as $temp)
    {
      $sql_where .= ' AND object.objectpath NOT LIKE "*comp*/'.trim($temp).'/%"';
    }
  }

  // get object that needs to be analyzed
  $sql = 'SELECT id, objectpath FROM object WHERE (filetype="image" OR filetype="video") AND analyzed=0 '.$sql_where.' LIMIT 1';
  $analyze = rdbms_externalquery ($sql);

  // set object media as analyzed (important in order to avoid analyzing the same media without faces)
  if (!empty ($analyze[0]['id']))
  {
    rdbms_setmedia ($analyze[0]['id'], "", "", "", "", "", "", "", "", "", "", true);
  }

  if (!empty ($analyze[0]['objectpath']))
  {
    $location = getlocation ($analyze[0]['objectpath']);
    $page = getobject ($analyze[0]['objectpath']);

    // get publication and category
    $site = getpublication ($location);
    $cat = getcategory ($site, $location);

    // publication management config
    if (valid_publicationname ($site)) require ($mgmt_config['abs_path_data']."config/".$site.".conf.php");

    // if link refers to a managed object (internal page)
    if (valid_publicationname ($site) && valid_locationname ($location) && valid_objectname ($page))
    {
      // build view of object
      $result = buildview ($site, $location, $page, $user, "formedit", "no", "", "", "", false, true);

      $charset = $result['charset'];
      $viewstore = $result['view'];
      $contentfile = $result['container'];

      // object is managed by the system
      if (!empty ($contentfile))
      {
        header ('Content-Type: text/html; charset='.$charset);
        echo $viewstore;
        exit;
      }
    }
  }
  else
  {
    echo "<!DOCTYPE html>\n";
    echo "<html lang=\"".(!empty ($lang) ? $lang : "en")."\">\n";
    echo "<head>\n";
    echo "<title>hyperCMS</title>\n";
    echo "<meta charset=\"".getcodepage ($lang)."\" />\n";
    echo "<link rel=\"stylesheet\" href=\"".getthemelocation()."css/main.css?v=".getbuildnumber()."\" />\n";
    echo "<link rel=\"stylesheet\" href=\"".getthemelocation()."css/".($is_mobile ? "mobile.css" : "desktop.css")."?v=".getbuildnumber()."\" />\n";
    echo "<script>setTimeout (function() {document.location.reload();}, 30000);</script>\n";
    echo "</head>\n";
    echo "<body class=\"hcmsWorkplaceGeneric\">\n";
    echo "<div class=\"hcmsWorkplaceFrame\">\n";
    echo "<p class=\"hcmsHeadline\"><img src=\"".getthemelocation()."img/info.png\" class=\"hcmsIconList\" /> ".getescapedtext ($hcms_lang['loading-'][$lang])."</p>\n";
    echo "</div>\n";
    echo "</body>\n";
    echo "</html>";
    exit;
  }
}
?>