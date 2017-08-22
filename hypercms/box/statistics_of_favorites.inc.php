<?php
// ---------------------- STATS ---------------------
if (!$is_mobile && isset ($siteaccess) && is_array ($siteaccess))
{
  // language file
  require_once ("language/".getlanguagefile ($lang));
  
  $object_array = getfavorites ($user);
  
  if (is_array ($object_array) && sizeof ($object_array) > 0)
  {
    foreach ($object_array as $item_objectpath)
    {
      if ($item_objectpath != "")
      {
        $item_site = getpublication ($item_objectpath);
        $item_object = getobject ($item_objectpath);
    
        // publication management config
        if (valid_publicationname ($item_site)) require ($mgmt_config['abs_path_data']."config/".$item_site.".conf.php");
  
        if ($is_mobile) $width = "92%";
        else $width = "670px";
        
        echo "
        <div id=\"stats_".$item_object."\" class=\"hcmsHomeBox\" style=\"overflow:auto; margin:10px; width:".$width."; height:400px; float:left;\">
          <div class=\"hcmsHeadline\" style=\"margin:2px;\">".getescapedtext ($hcms_lang['access-statistics-for'][$lang])." ".specialchr_decode ($item_object)."</div>";

        $date_from = date ("Y-m-01", time());
        $date_to = date ("Y-m-t", time());
        $date_year = date ("Y", time());
        $date_month = date ("m", time());
              
        $result_download = rdbms_getmediastat ($date_from, $date_to, "download", "", $item_objectpath, "");
        $result_upload = rdbms_getmediastat ($date_from, $date_to, "upload", "", $item_objectpath, "");
        
        $date_axis = array();
        $download_axis = array();
        $upload_axis = array();
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
            $download_axis[$i]['text'] = $date_year."-".$date_month."-".$day."   \n".$download_axis[$i]['value']." ".$hcms_lang['downloads'][$lang]."   \n".$hcms_lang['users'][$lang].": ".$download_axis[$i]['text'];
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
            $upload_axis[$i]['text'] = $date_year."-".$date_month."-".$day."   \n".$upload_axis[$i]['value']." ".$hcms_lang['uploads'][$lang]."   \n".$hcms_lang['users'][$lang].": ".$upload_axis[$i]['text'];   
          }
        }
          
        if (is_array ($download_axis) || is_array ($upload_axis))
        {
          $chart = buildbarchart ("chart", 600, 300, 8, 40, $date_axis, $download_axis, $upload_axis, "", "border:1px solid #666666; background:white;", "background:#3577ce; font-size:8px; cursor:pointer;", "background:#ff8219; font-size:8px; cursor:pointer;", "background:#73bd73; font-size:8px; cursor:pointer;");
          echo $chart;
        }
  
        echo '
        <div style="margin:35px 0px 0px 40px;">
          <div style="height:16px;"><div style="width:16px; height:16px; background:#3577ce; float:left;"></div>&nbsp;'.getescapedtext ($hcms_lang['downloads'][$lang]).' ('.number_format ($download_total_count, 0, "", ".").' Hits / '.number_format (($download_total_filesize / 1024), 0, "", ".").' MB)</div>
          <div style="height:16px; margin-top:2px;"><div style="width:16px; height:16px; background:#ff8219; float:left;"></div>&nbsp;'.getescapedtext ($hcms_lang['uploads'][$lang])." (".number_format ($upload_total_count, 0, "", ".").' Hits / '.number_format (($upload_total_filesize / 1024), 0, "", ".").' MB)</div>
        </div>';
    
        echo "
        </div>\n";
      }
    }
  }
}
?>