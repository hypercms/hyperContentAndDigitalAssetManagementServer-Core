<?xml version="1.0" encoding="utf-8" ?>
<template>
<name>RSSReader</name>
<user>admin</user>
<category>comp</category>
<extension>php</extension>
<application>php</application>
<content><![CDATA[[hyperCMS:objectview name='formedit']
[hyperCMS:textu id='RSSFeedURL' label='RSS Feed URL' default='https://www.domain.com/rss.xml' height='20']
[hyperCMS:textk id='Keywords' label='Search for Keywords' default='' height='20']
[hyperCMS:scriptbegin
if ("%view%" == "publish")
{
  $user = "sys";

  $feed_url = htmlspecialchars ("[hyperCMS:textu id='RSSFeedURL' onEdit='hidden']");
  $feed_keywords = htmlspecialchars ("[hyperCMS:textk id='Keywords' onEdit='hidden']");

  if ($feed_url != "" && $feed_keywords != "")
  {
    $feed_keywords = preg_replace ('/\s+/', ' ', $feed_keywords);
    $feed_keywords = str_replace (',', '|', $feed_keywords);
    
    $rss = simplexml_load_file ($feed_url);
    
    foreach ($rss->channel->item as $feed_item)
    {
      $feed_title = $feed_item->title;
      $feed_date = $feed_item->pubDate;
      $feed_description = $feed_item->description;
      $feed_link = $feed_item->link;
      $feed_data = $feed_title.$feed_description;
      
      if (preg_match ('('.$feed_keywords.')', $feed_data) === 1) 
      {
        $hostname = parse_url ($feed_link, PHP_URL_HOST);
        $temp_array = explode ('/', $feed_link);
        $temp = end ($temp_array);
        list ($article_id) = explode ('.', $temp);
        $name = $hostname."_".$article_id;

        // load html
        $html = file_get_contents ($feed_link);
        $content = cleancontent ($html);

        // create PDF from HTML
        // if (!empty ($html)) $savehtml = file_put_contents ($mgmt_config['abs_path_temp'].$name.".html", $html);
        // if (!empty ($savehtml)) $name_pdf = createdocument ("%publication%", $mgmt_config['abs_path_temp'], $mgmt_config['abs_path_temp'], $name.".html", "pdf");

        if (!empty ($name))
        {
          // create new object
          $result = createobject ("%publication%", "%abs_location%/", $name, "News", $user);

          if (!empty ($result['result']))
          {
            // write data to container
            $text = array ("Title"=>$feed_title, "Date"=>$feed_date, "Description"=>$feed_description, "Link"=>$feed_link, "Content"=>$content);
            $xml = settext ("%publication%", $result['container_content'], $result['container'], $text, "u", "no", $user, $user, "UTF-8");

            // save working xml content container file
            if ($xml != "")
            {
              $savefile = savecontainer ($result['container_id'], "work", $xml, $user);
          
              if ($savefile) publishobject ("%publication%", "%abs_location%/", $result['object'], $user);
            }
          }
        }
      }
    }
  }
}
scriptend]]]></content>
</template>