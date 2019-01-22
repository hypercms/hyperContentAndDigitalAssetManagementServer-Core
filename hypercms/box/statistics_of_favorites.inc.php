<?php
// ---------------------- STATS ---------------------
if (!$is_mobile && isset ($siteaccess) && is_array ($siteaccess))
{
  // language file
  require_once ("language/".getlanguagefile ($lang));
  
  $object_array = getfavorites ($user);
  
  if (is_array ($object_array) && sizeof ($object_array) > 0)
  {
    foreach ($object_array as $item_objectinfo)
    {
      if (!empty ($item_objectinfo['objectpath'])) $item_objectpath = $item_objectinfo['objectpath'];
      else $item_objectpath = $item_objectinfo;
      
      if ($item_objectpath != "")
      {
        $item_site = getpublication ($item_objectpath);
        $item_cat = getcategory ($item_site, $item_objectpath);
        $item_location = getlocation ($item_objectpath);
        $item_object = getobject ($item_objectpath);
        $item_fileinfo = getfileinfo ($item_site, $item_location.$item_object, $item_cat);
    
        // publication management config
        if (valid_publicationname ($item_site)) require ($mgmt_config['abs_path_data']."config/".$item_site.".conf.php");
  
        if ($is_mobile) $width = "92%";
        else $width = "670px";
        
        echo "
        <div id=\"stats_".$item_object."\" onclick=\"hcms_openWindow('frameset_content.php?site=".url_encode($item_site)."&ctrlreload=yes&cat=".url_encode($item_cat)."&location=".url_encode($item_location)."&page=".url_encode($item_object)."', '".$item_object."', 'location=no,status=yes,scrollbars=no,resizable=yes,titlebar=no', ".windowwidth ("object").", ".windowheight ("object").");\" class=\"hcmsHomeBox\" style=\"cursor:pointer; overflow:auto; margin:10px; width:".$width."; height:400px; float:left;\">
          <div class=\"hcmsHeadline\">".getescapedtext ($hcms_lang['access-statistics-for'][$lang])." ".showshorttext($item_fileinfo['name'], 40)."</div>";

        $date_from = date ("Y-m-01", time());
        $date_to = date ("Y-m-t", time());
        $date_year = date ("Y", time());
        $date_month = date ("m", time());
       
        $result_view = rdbms_getmediastat ($date_from, $date_to, "view", "", $item_objectpath, "");
        $result_download = rdbms_getmediastat ($date_from, $date_to, "download", "", $item_objectpath, "");
        $result_upload = rdbms_getmediastat ($date_from, $date_to, "upload", "", $item_objectpath, "");
        
        $date_axis = array();
        $view_axis = array();
        $download_axis = array();
        $upload_axis = array();
        $view_total_filesize = 0;
        $view_total_count = 0;
        $download_total_filesize = 0;
        $download_total_count = 0;
        $upload_total_filesize = 0;
        $upload_total_count = 0;
      
        // loop through days of month
        for ($i=1; $i<=date("t", strtotime($date_from)); $i++)
        {
          $date_axis[$i] = $i;
          
          if (strlen ($i) == 1) $day = "0".$i;
          else $day = $i;
          
          // views
          $view_axis[$i]['value'] = 0;
          $view_axis[$i]['text'] = "";
          
          if (isset ($result_view) && is_array ($result_view)) 
          { 
            foreach ($result_view as $row)
            {
              if ($row['date'] == $date_year."-".$date_month."-".$day)
              {
                if ($view_axis[$i]['text'] != "") $seperator = ", ";
                else $seperator = "";
         
                $view_axis[$i]['value'] = $view_axis[$i]['value'] + $row['count'];
                $view_axis[$i]['text'] = $view_axis[$i]['text'].$seperator.$row['user'];
                
                // total
                $view_total_count = $view_total_count + $row['count'];
                $view_total_filesize = $view_total_filesize + ($row['count'] * $row['filesize']);
              }
            }
            
            // bar text
            $view_axis[$i]['text'] = $date_year."-".$date_month."-".$day."   \n".$view_axis[$i]['value']." ".getescapedtext ($hcms_lang['views'][$lang])."   \n".getescapedtext ($hcms_lang['users'][$lang]).": ".$view_axis[$i]['text'];
          }
      
          // downloads
          $download_axis[$i]['value'] = 0;
          $download_axis[$i]['text'] = "";
      
          if (is_array ($result_download)) 
          { 
            foreach ($result_download as $row)
            {
              if ($row['date'] == $date_year."-".$date_month."-".$day)
              {
                if ($download_axis[$i]['text'] != "") $seperator = ", ";
                else $seperator = "";
         
                $download_axis[$i]['value'] = $download_axis[$i]['value'] + $row['count'];
                $download_axis[$i]['text'] = $download_axis[$i]['text'].$seperator.$row['user'];
                
                // total
                $download_total_count = $download_total_count + $row['count'];
                $download_total_filesize = $download_total_filesize + ($row['count'] * $row['filesize']);
              }
            }
            
            // bar text
            $download_axis[$i]['text'] = showdate ($date_year."-".$date_month."-".$day, "Y-m-d", $hcms_lang_date[$lang])."   \n".$download_axis[$i]['value']." ".$hcms_lang['downloads'][$lang]."   \n".$hcms_lang['users'][$lang].": ".$download_axis[$i]['text'];
          }
          
          // uploads
          $upload_axis[$i]['value'] = 0;
          $upload_axis[$i]['text'] = "";
            
          if (is_array ($result_upload)) 
          {
            foreach ($result_upload as $row)
            {
              if ($row['date'] == $date_year."-".$date_month."-".$day)
              {
                if ($upload_axis[$i]['text'] != "") $seperator = ", ";
                else $seperator = "";
                        
                $upload_axis[$i]['value'] = $upload_axis[$i]['value'] + $row['count'];
                $upload_axis[$i]['text'] = $upload_axis[$i]['text'].$seperator.$row['user'];
           
                // total
                $upload_total_count = $upload_total_count + $row['count'];
                $upload_total_filesize = $upload_total_filesize + ($row['count'] * $row['filesize']);
              }
            }
            
            // bar text
            $upload_axis[$i]['text'] = showdate ($date_year."-".$date_month."-".$day, "Y-m-d", $hcms_lang_date[$lang])."   \n".$upload_axis[$i]['value']." ".$hcms_lang['uploads'][$lang]."   \n".$hcms_lang['users'][$lang].": ".$upload_axis[$i]['text'];   
          }
        }
          
        if (is_array ($view_axis) || is_array ($download_axis) || is_array ($upload_axis))
        {
          $chart = buildbarchart ("chart", 600, 270, 8, 40, $date_axis, $view_axis, $download_axis, $upload_axis, "border:1px solid #666666; background:white;", "background:#6fae30; font-size:10px; cursor:pointer;", "background:#108ae7; font-size:10px; cursor:pointer;", "background:#ff8219; font-size:10px; cursor:pointer;");
          echo $chart;
        }
  
        echo '
        <div style="margin:35px 0px 0px 40px;">
          <div style="height:16px;"><div style="width:16px; height:16px; background:#6fae30; float:left;"></div>&nbsp;'.getescapedtext ($hcms_lang['views'][$lang]).' ('.number_format ($view_total_count, 0, ".", " ").' Hits / '.number_format (($view_total_filesize / 1024), 0, ".", " ").' MB)</div>
          <div style="height:16px; margin-top:2px;"><div style="width:16px; height:16px; background:#108ae7; float:left;"></div>&nbsp;'.getescapedtext ($hcms_lang['downloads'][$lang]).' ('.number_format ($download_total_count, 0, "", ".").' Hits / '.number_format (($download_total_filesize / 1024), 0, "", ".").' MB)</div>
          <div style="height:16px; margin-top:2px;"><div style="width:16px; height:16px; background:#ff8219; float:left;"></div>&nbsp;'.getescapedtext ($hcms_lang['uploads'][$lang])." (".number_format ($upload_total_count, 0, "", ".").' Hits / '.number_format (($upload_total_filesize / 1024), 0, "", ".").' MB)</div>
        </div>';
    
        echo "
        </div>\n";
      }
    }
  }
}
?>