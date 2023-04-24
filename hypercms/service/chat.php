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
$function = getrequest ("function");
$state = getrequest ("state");
$message = getrequest ("message");

// ------------------------------ permission section --------------------------------

// check access to chat
if (!isset ($mgmt_config['chat']) || $mgmt_config['chat'] != true) killsession ($user);

// check session of user
checkusersession ($user, false);

// --------------------------------- logic section ----------------------------------

// write and close session (non-blocking other frames)
suspendsession ();

// chat log file
$chat_log = $mgmt_config['abs_path_data']."log/chat.log";

// chat relationships log file
$chat_relations_log = $mgmt_config['abs_path_temp']."/chat_relations.php";

// date and time
$date = date ("Y-m-d H:i:s", time());

// publication access of user
if (is_array ($siteaccess)) $sites = implode (";", array_keys ($siteaccess));
else $sites = "";

// if chat log file does not exist, create it
if (!is_file ($chat_log)) file_put_contents ($chat_log, "");

// load invited user names (relationships)
if (is_file ($chat_relations_log))
{
  $chat_relations = file_get_contents ($chat_relations_log);
  if (!empty ($chat_relations)) $chat_relations_array = unserialize ($chat_relations);
}

$log = array();

switch ($function)
{    
  // get current state of chat (number of lines in chat log)
  case ('getState'):
  
    if (file_exists ($chat_log)) $lines = file ($chat_log);
    $log['state'] = count ($lines);
    break;	
	
  // update chat with new messages after last state of client
  case ('update'):
  
    if (file_exists ($chat_log))
    {
      $lines = file ($chat_log);
      $count = count ($lines);
      
      if ($state == $count)
      {
        $log['state'] = $state;
        $log['text'] = false;    		 
      }
      else
      {
        $text = array();
        $log['state'] = $count;
        
        foreach ($lines as $line_num => $line)
        {
          if ($line_num >= $state)
          {
            $line = str_replace ("\n", "", $line);
            
            // get chat message log entries
            list ($chat_date, $chat_sites, $chat_user, $chat_text) = explode ("|", $line);
            
            foreach ($siteaccess as $site => $displayname)
            {
              // verify publication access
              if (substr_count (";".$chat_sites.";", ";".$site.";") > 0)
              {
                // users own messages
                if ($user == $chat_user)
                {
                  $text[] = $chat_text;
                }
                // private chat
                elseif (!empty ($mgmt_config['chat_type']) && strtolower ($mgmt_config['chat_type']) == "private" && !empty ($chat_relations_array) && is_array ($chat_relations_array))
                {
                  // verify host and invited users (guests) against chat user entry
                  foreach ($chat_relations_array as $host=>$guest_array)
                  {
                    if ((in_array ($user, $guest_array) && in_array ($chat_user, $guest_array)) || ($chat_user == $host && in_array ($user, $guest_array)) || ($user == $host && in_array ($chat_user, $guest_array)))
                    {
                      $text[] = $chat_text;
                    }
                  }
                }
                // public chat
                elseif (empty ($mgmt_config['chat_type']) || strtolower ($mgmt_config['chat_type']) == "public")
                {
                  $text[] = $chat_text;
                }
                
                break;
              } 
            }
          }
        }
        
        $log['text'] = $text; 
      }
    }
    	  
    break;
	
  // save message in chat log
  case ('send'):
  
    $nickname = htmlentities (strip_tags ($user));
    $reg_exUrl = "/(http|https|ftp|ftps)\:\/\/[a-zA-Z0-9\-\.]+\.[a-zA-Z]{2,3}(\/\S*)?/";
    $message = htmlentities (strip_tags ($message));
 
    if (($message) != "\n")
    {
      // add link to open URL
      if (preg_match ($reg_exUrl, $message, $url))
      {
        $message = preg_replace ($reg_exUrl, '<a href="'.$url[0].'" target="_blank">'.$url[0].'</a>', $message);
      } 
      
      // add link to open object
      if (substr_count ($message, "hcms_openWindow") > 0)
      {
        $message = str_replace ($message, '&gt;&gt; <a href="#" onclick="'.$message.'">Object-Link</a>', $message);
      }
 	
    	fwrite (fopen ($chat_log, 'a'), str_replace("|", "&#124;", $date)."|".str_replace("|", "&#124;", $sites)."|".str_replace("|", "&#124;", $user)."|<span>".str_replace("|", "&#124;", $nickname)."</span>".$message = str_replace ("\n", " ", str_replace("|", "&#124;", $message))."\n"); 
    }
    
    break;

  // save invitation message for a selected user in chat log
	case ('invite'):
  
    $from_user = $user;
    $from_user_clean = htmlentities (strip_tags ($from_user));
    $to_user = $message;
    $to_user_clean = htmlentities (strip_tags ($to_user));
 
    if ($to_user_clean != "\n")
    {
      // save invited user names (relationships)
      if (!empty ($chat_relations_array[$user]) && is_array ($chat_relations_array[$user]))
      {
        $chat_relations_array[$user][$to_user_clean] = $to_user_clean;
      }
      else
      {
        $chat_relations_array[$user] = array ();
        $chat_relations_array[$user][$to_user_clean] = $to_user_clean;
      }
      
      $chat_relations = serialize ($chat_relations_array);      
      if (!empty ($chat_relations)) file_put_contents ($chat_relations_log, $chat_relations);
      
      // write to chat log
    	fwrite (fopen ($chat_log, 'a'), str_replace("|", "&#124;", $date)."|".str_replace("|", "&#124;", $sites)."|".str_replace("|", "&#124;", $user)."|<span>".str_replace("|", "&#124;", $from_user_clean)."</span> &gt;&gt; <span data-action=\"invite\">".str_replace("|", "&#124;", $to_user_clean)."</span>\n");

      // send message to user
      sendmessage ($user, $to_user, str_replace ("%user%", "'".$user."'", $hcms_lang['user-wants-to-chat-with-you'][$lang]), $hcms_lang['open-link'][$lang].": ".$mgmt_config['url_path_cms']."\n\n".$hcms_lang['this-is-an-automatically-generated-mail-notification'][$lang]);
    }
    
    break;
    
  // uninvite all users (clear chat relationships of the host user to his guests)
  case ('uninvite'):
  
    $host_user = $message;
    $host_user_clean = htmlentities (strip_tags ($host_user));
  
    if (!empty ($chat_relations))
    {
      $chat_relations_array = unserialize ($chat_relations);
      
      if (!empty ($chat_relations_array[$host_user]))
      {
        unset ($chat_relations_array[$host_user]);
        $chat_relations = serialize ($chat_relations_array);      
        if (!empty ($chat_relations)) file_put_contents ($chat_relations_log, $chat_relations);
      }
    }
    
    // write to chat log
  	fwrite (fopen ($chat_log, 'a'), str_replace("|", "&#124;", $date)."|".str_replace("|", "&#124;", $sites)."|".str_replace("|", "&#124;", $user)."|<span>".str_replace("|", "&#124;", $host_user_clean)."</span> &lt;&lt; <span data-action=\"invite\">".str_replace("|", "&#124;", $hcms_lang['all-users'][$lang])."</span>\n");
      
    break;
    
  // check chat log for an invitation
  case ('check'):
  
    if (file_exists ($chat_log)) $lines = file ($chat_log);
    $count = count ($lines);
    
    if ($state == $count)
    {
      $log['state'] = $state;
      $log['text'] = false;    		 
    }
    else
    {
      $text = array();
      $log['state'] = $state + count ($lines) - $state;
      
      foreach ($lines as $line_num => $line)
      {
        if ($line_num >= $state && strpos ($line, "<span data-action=\"invite\">".$user."</span>") > 0)
        {
          $text[] = $line = str_replace ("\n", "", $line);
        }
      }
      
      $log['text'] = $text; 
    }
    	  
    break;
}

header ('Content-Type: application/json; charset=utf-8');
echo json_encode ($log);
exit;
?>