<?php
/*
 * This file is part of
 * hyper Content Management Server - http://www.hypercms.com
 * Copyright (c) by hyper CMS Content Management Solutions GmbH
 *
 * You should have received a copy of the License along with hyperCMS.
 */
 
// ========================================= TASKMANAGEMENT ============================================

// ---------------------------------------------- createtask ----------------------------------------------
// function: createtask()
// input: publication name, from_user name, from_email [email-address], to_user name, to_email [email-address], start date (optional), finish date (optional),
//        category, object ID or object path, task name, message (optional), sendmail [true/false], priority [high,medium,low] (optional), project ID (optional)
// output: true/false
// requires: config.inc.php

// description:
// Creates a new user task and send optional e-mail to user.
// Since verion 5.8.4 the data will be stored in RDBMS instead of XML files.

function createtask ($site, $from_user, $from_email, $to_user, $to_email, $startdate="", $finishdate="", $category, $object_id, $taskname, $message="", $sendmail=true, $priority="low", $project_id=0)
{
  global $mgmt_config, $hcms_lang_codepage, $hcms_lang, $lang;
  
  // include hypermailer class
  if (!class_exists ("HyperMailer")) include_once ($mgmt_config['abs_path_cms']."function/hypermailer.class.php");  

  // ---------------------------------- create new task -----------------------------------
  // load task file of user, set new task and save task file
  if (is_file ($mgmt_config['abs_path_cms']."task/task_list.php") && valid_objectname ($to_user) && $taskname != "" && strlen ($taskname) <= 200 && strlen ($message) <= 3600)
  {
    // load user file
    $userdata = loadfile ($mgmt_config['abs_path_data']."user/", "user.xml.php");
      
    if ($userdata != "")
    {       
      // get user node and extract required information    
      $usernode = selectcontent ($userdata, "<user>", "<login>", $to_user);

      if (!empty ($usernode[0]))
      {
        // email
        if ($to_email == "")
        {
          $temp = getcontent ($usernode[0], "<email>");
          if (!empty ($temp[0])) $to_email = $temp[0];
          else $to_email = "";
        }
 
        // language
        $temp = getcontent ($usernode[0], "<language>");            
        if (!empty ($temp[0])) $to_lang = $temp[0];
        else $to_lang = "en";
      }
    }

    // load language of user if it has not been loaded
    if (!empty ($to_lang) && empty ($hcms_lang['new-task-from-user'][$to_lang]))
    {
      require_once ($mgmt_config['abs_path_cms']."language/".getlanguagefile ($to_lang));
    }

    // get local date today (jjjj-mm-dd hh:mm)
    $mgmt_config['today'] = date ("Y-m-d H:i", time());

    // try to get object_id from object path
    if ($object_id != "" && intval ($object_id) < 1)
    {
      // convert object path if necessary
      $object_esc = convertpath ($site, $object, "");
      
      // get object id
      $object_id = rdbms_getobject_id ($object_id);
    }
    
    // check priority
    if (!in_array ($priority, array("high","medium","low"))) $priority = "low";

    // save task in database
    $result = rdbms_createtask ($object_id, $project_id, $from_user, $to_user, $startdate, $finishdate, $category, $taskname, $message, $priority);

    // send mail
    if ($result == true && $sendmail == true && !empty ($to_email))
    {
      $location = getlocation ($object_esc);
      $page = getobject ($object_esc);
      $cat = getcategory ($site, $object_esc);
      $object_link = createaccesslink ($site, $location, $page, $cat, "", $to_user, "al");
      
      // email schema
      if ($from_email != "") $email_schema = " [<a href='mailto:".$from_email."'>".$from_email."</a>]";
      else $email_schema = "";
      
      $body = "<span style=\"font-family:Verdana, Arial, Helvetica, sans-serif; font-size:14px;\"><strong>".$hcms_lang['new-task-from-user'][$to_lang]." '".$from_user."'".$email_schema.":</strong>\n".$message."\n\n".$object_link."</span>";
  
      $mailer = new HyperMailer();
      $mailer->IsHTML(true);
      $mailer->AddAddress ($to_email, $to_user);
      $mailer->AddReplyTo ($from_email, $from_user);
      $mailer->From = $from_email;
      $mailer->Subject = "hyperCMS: ".$taskname."-".$hcms_lang['new-task-from-user'][$to_lang]." ".$from_user;
      $mailer->CharSet = $hcms_lang_codepage[$to_lang];
      $mailer->Body = html_decode (nl2br ($body), $hcms_lang_codepage[$to_lang]);
      
      // send mail
      if ($mailer->Send())
      {
        $errcode = "00202";
        $error[] = $mgmt_config['today']."|hypercms_main.inc.php|info|$errcode|task notification has been sent to ".$to_user." (".$to_email.") on object ".$object_esc; 
      }
      else
      {
        $errcode = "50202";
        $error[] = $mgmt_config['today']."|hypercms_main.inc.php|error|$errcode|task notification failed for ".$to_user." (".$to_email.") on object ".$object_esc." (mail could not be sent)";  
      }
      
      // save log
      savelog (@$error);
    }
    
    return $result;
  }
  else return false;
}

// ---------------------------------------------- settask ----------------------------------------------
// function: settask()
// input: task ID, to_user name (optional), start date (optional), finish date (optional),
//        category (optional), task name (optional), message (optional), 
//        sendmail [true/false], priority [high,medium,low] (optional), status [0-100] (optional), 
//        planned effort in taskunit (optional), actual effort in taskunit (optional), project ID (optional)
// output: true/false
// requires: config.inc.php

// description:
// Saves data of a user task and send optional e-mail to user.
// Since verion 5.8.4 the data will be stored in RDBMS instead of XML files.

function settask ($task_id, $to_user="", $startdate="", $finishdate="", $taskname="", $message="", $sendmail=true, $priority="", $status="", $planned="", $actual="", $project_id=0)
{
  global $mgmt_config, $hcms_lang_codepage, $hcms_lang, $lang;
  
  // include hypermailer class
  if (!class_exists ("HyperMailer")) include_once ($mgmt_config['abs_path_cms']."function/hypermailer.class.php");  
 
  if (is_file ($mgmt_config['abs_path_cms']."task/task_list.php") && $task_id != "")
  {
    // get task
    $gettask = rdbms_gettask ($task_id);
    
    if ($to_user == "") $to_user = $gettask[0]['to_user'];
    $from_user = $gettask[0]['from_user'];
    if (filter_var ($from_user, FILTER_VALIDATE_IP)) $from_user = "System";
    $to_user_old = $gettask[0]['to_user'];
    $object_id = $gettask[0]['object_id'];
    $startdate_old = $gettask[0]['startdate'];
    $finishdate_old = $gettask[0]['finishdate'];
    $taskname_old = $gettask[0]['taskname'];
    $message_old = $gettask[0]['description'];
    $priority_old = $gettask[0]['priority'];
    $status_old = $gettask[0]['status'];
    $planned_old = $gettask[0]['planned'];
    $actual_old = $gettask[0]['actual'];
  
    // get e-mail of user if the task description (message) has been changed
    if ($sendmail && valid_objectname ($to_user) && ($message != $message_old && $message != "" && strlen ($message) < 3600))
    {
      // load user file
      $userdata = loadfile ($mgmt_config['abs_path_data']."user/", "user.xml.php");
        
      if ($userdata != "")
      {       
        // get user node and extract required information    
        $usernode = selectcontent ($userdata, "<user>", "<login>", $to_user);
  
        if (!empty ($usernode[0]))
        {
          // email
          $temp = getcontent ($usernode[0], "<email>");
          if (!empty ($temp[0])) $to_email = $temp[0];
          else $to_email = "";
   
          // language
          $temp = getcontent ($usernode[0], "<language>");            
          if (!empty ($temp[0])) $to_lang = $temp[0];
          else $to_lang = "en";
        }
        
        // get user node and extract required information    
        $usernode = selectcontent ($userdata, "<user>", "<login>", $from_user);
  
        if (!empty ($usernode[0]))
        {
          // email
          $temp = getcontent ($usernode[0], "<email>");
          if (!empty ($temp[0])) $from_email = $temp[0];
          else $from_email = "";
        }
      }
  
      // load language of user if it has not been loaded
      if (!empty ($to_lang) && empty ($hcms_lang['new-task-from-user'][$to_lang]))
      {
        require_once ($mgmt_config['abs_path_cms']."language/".getlanguagefile ($to_lang));
      }
    }

    // check priority
    if ($priority != "" && !in_array ($priority, array("high","medium","low"))) $priority = "low";

    // if any value has been changed
    if ($to_user != $to_user_old || $startdate != $startdate_old || $finishdate != $finishdate_old || $taskname != $taskname_old || $message != $message_old || $priority != $priority_old || $status != $status_old || $planned != $planned_old || $actual != $actual_old)
    {
      $settask = rdbms_settask ($task_id, $project_id, $to_user, $startdate, $finishdate, $taskname, $message, $priority, $status, $planned, $actual);
      
      // send mail
      if ($settask && $sendmail == true && !empty ($to_email))
      {
        $object_link = createaccesslink ("", "", "", "", $object_id, $to_user, "al");
        
        // email schema
        if ($from_email != "") $email_schema = " [<a href='mailto:".$from_email."'>".$from_email."</a>]";
        else $email_schema = "";
      
        $body = "<span style=\"font-family:Verdana, Arial, Helvetica, sans-serif; font-size:14px;\"><strong>".$hcms_lang['new-task-from-user'][$to_lang]." '".$from_user."'".$email_schema.":</strong>\n".$message."\n\n".$object_link."</span>";
    
        $mailer = new HyperMailer();
        $mailer->IsHTML(true);
        $mailer->AddAddress ($to_email, $to_user);
        $mailer->AddReplyTo ($from_email, $from_user);
        $mailer->From = $from_email;
        $mailer->Subject = "hyperCMS: ".$hcms_lang['new-task-from-user'][$to_lang]." ".$from_user;
        $mailer->CharSet = $hcms_lang_codepage[$to_lang];
        $mailer->Body = html_decode (nl2br ($body), $hcms_lang_codepage[$to_lang]);
        
        // send mail
        if ($mailer->Send())
        {
          $errcode = "00205";
          $error[] = $mgmt_config['today']."|hypercms_main.inc.php|info|$errcode|updated task notification has been sent to ".$to_user." (".$to_email.")"; 
        }
        else
        {
          $errcode = "50205";
          $error[] = $mgmt_config['today']."|hypercms_main.inc.php|error|$errcode|updated task notification failed for ".$to_user." (".$to_email.")";  
        }
      }
    }
    // nothing has changed
    else $settask = true;
    
    if ($settask)
    {
      $add_onload = "";
      $show = "<span class=hcmsHeadline>".$hcms_lang['the-data-was-saved-successfully'][$lang]."</span>\n";
    }
    else
    {
      $add_onload = "";
      $show = "<span class=hcmsHeadline>".$hcms_lang['the-data-could-not-be-saved'][$lang]."</span>\n";
    }
  }
  else
  {
    $add_onload = "";
    $show = "<span class=hcmsHeadline>".$hcms_lang['required-input-is-missing'][$lang]."</span>\n";
  }
  
  // save log
  savelog (@$error); 
  
  $result = array();
  
  $result['add_onload'] = $add_onload;
  $result['message'] = $show;

  return $result;
}

// ---------------------------------------------- deletetask ----------------------------------------------
// function: deletetask()
// input: task ID or array of task IDs to be deleted
// output: true/false
// requires: config.inc.php

// description:
// Deletes user tasks.

function deletetask ($task_id)
{
  global $mgmt_config, $hcms_lang, $lang;

  if ($task_id != "" || is_array ($task_id))
  {
    if (!is_array ($task_id))
    {
      $temp_id = $task_id;
      $task_id = array();
      $task_id[0] = $temp_id;
    }
  
    if (is_array ($task_id) && sizeof ($task_id) > 0)
    {
      // delete tasks
      foreach ($task_id as $id)
      {
        if ($id != "")
        {
          $result = rdbms_deletetask ($id);
          
          if ($result == false)
          {
            $errcode = "10402";  
            $error[] = $mgmt_config['today']."|hypercms_main.inc.php|error|$errcode|task with ID ".$task_id." could not be deleted";
          }
        }
      }

      $add_onload = "";
      $show = "<span class=hcmsHeadline>".$hcms_lang['the-tasks-were-successfully-removed'][$lang]."</span>\n";
    }
    else
    {
      $add_onload = "";
      $show = "<span class=hcmsHeadline>".$hcms_lang['no-tasks-selected'][$lang]."</span>\n";
    }
  }
  else
  {
    $add_onload = "";
    $show = "<span class=hcmsHeadline>".$hcms_lang['required-input-is-missing'][$lang]."</span>\n";
  }
  
  // save log
  savelog (@$error); 
  
  $result = array();
  
  $result['add_onload'] = $add_onload;
  $result['message'] = $show;

  return $result;  
}

// ---------------------------------------------- tasknotification ----------------------------------------------
// function: tasknotification()
// input: date
// output: true/false
// requires: config.inc.php

// description:
// Sends e-mail notifications to users if a task starts or ends on the given date.

function tasknotification ($date)
{
  global $mgmt_config, $hcms_lang_codepage, $hcms_lang, $lang;
  
  // include hypermailer class
  if (!class_exists ("HyperMailer")) include_once ($mgmt_config['abs_path_cms']."function/hypermailer.class.php");  
 
  if (is_file ($mgmt_config['abs_path_cms']."task/task_list.php") && $date != "")
  {
    // load user file and define user array
    $userdata = loadfile ($mgmt_config['abs_path_data']."user/", "user.xml.php");
    
    $user_array = array();
    
    if ($userdata != "")
    {       
      // get user node and extract required information    
      $usernode = getcontent ($userdata, "<user>");
    
      foreach ($usernode as $temp)
      {
        if ($temp != "")
        {
          $login = getcontent ($temp, "<login>");
          $email = getcontent ($temp, "<email>");
          $realname = getcontent ($temp, "<realname>");
          $language = getcontent ($temp, "<language>");
          
          if (!empty ($login[0]))
          {
            $username = $login[0];
            $user_array[$username]['email'] = $email[0];
            $user_array[$username]['realname'] = $realname[0];
            $user_array[$username]['language'] = $language[0];
          }
        }
      }
    }
    else
    {
      $errcode = "50304";
      $error[] = $mgmt_config['today']."|hypercms_main.inc.php|error|$errcode|user data could not be loaded for task notification";  
    }
    
    if (sizeof ($user_array) > 0)
    {
      // send task start notification to users
      $task_start = rdbms_gettask ("", "", "", "", date("Y-m-d"));
      
      if (is_array ($task_start))
      { 
        foreach ($task_start as $task)
        {
          $task_id = $task['task_id'];
          $object_id = $task['object_id'];
          $taskname = $task['taskname'];
          $description = $task['description'];
          $from_user = $task['from_user'];
          $to_user = $task['to_user'];
          $to_lang = "en";

          if ($to_user != "" && !empty ($user_array[$to_user]['email']))
          {
            // set language for recipient
            if (!empty ($user_array[$to_user]['language'])) $to_lang = $user_array[$to_user]['language'];
          
            // send mail
            if ($object_id != "") $object_link = createaccesslink ("", "", "", "", $object_id, $to_user, "al");
            else $object_link = "";
            
            // email schema
            if ($from_user != "" && !empty ($user_array[$from_user]['email'])) $email_schema = " [<a href='mailto:".$user_array[$from_user]['email']."'>".$user_array[$from_user]['email']."</a>]";
            else $email_schema = "";
          
            $body = "<span style=\"font-family:Verdana, Arial, Helvetica, sans-serif; font-size:14px;\"><strong>".$hcms_lang['task-management'][$to_lang]."-".$hcms_lang['start'][$to_lang]." '".$taskname."' (".$task_id.")</strong>\n".$hcms_lang['from'][$to_lang]." '".$from_user."'".$email_schema."\n\n".$description."\n\n".$object_link."</span>";
        
            $mailer = new HyperMailer();
            $mailer->IsHTML(true);
            $mailer->AddAddress ($user_array[$to_user]['email'], $to_user);
            $mailer->AddReplyTo ($user_array[$from_user]['email'], $from_user);
            $mailer->From = $user_array[$from_user]['email'];
            $mailer->Subject = "hyperCMS: ".$hcms_lang['task-management'][$to_lang]."-".$hcms_lang['start'][$to_lang]." '".$taskname."' (".$task_id.")";
            $mailer->CharSet = $hcms_lang_codepage[$to_lang];
            $mailer->Body = html_decode (nl2br ($body), $hcms_lang_codepage[$to_lang]);
            
            // send mail
            if ($mailer->Send())
            {
              $errcode = "00305";
              $error[] = $mgmt_config['today']."|hypercms_main.inc.php|info|$errcode|task start notification has been sent to ".$to_user." (".$user_array[$to_user]['email'].")"; 
            }
            else
            {
              $errcode = "50305";
              $error[] = $mgmt_config['today']."|hypercms_main.inc.php|error|$errcode|task start notification failed for ".$to_user." (".$user_array[$to_user]['email'].")";  
            }
          }
        }
      }
      
      // send task end notification to users
      $task_end = rdbms_gettask ("", "", "", "", "", date("Y-m-d"));
      
      if (is_array ($task_end))
      {
        foreach ($task_end as $task)
        {
          $task_id = $task['task_id'];
          $object_id = $task['object_id'];
          $taskname = $task['taskname'];
          $description = $task['description'];
          $from_user = $task['from_user'];
          $to_user = $task['to_user'];
          $to_lang = "en";
          
          if ($to_user != "" && !empty ($user_array[$to_user]['email']))
          {
            // set language for recipient
            if (!empty ($user_array[$to_user]['language'])) $to_lang = $user_array[$to_user]['language'];
            
            // send mail
            if ($object_id != "") $object_link = createaccesslink ("", "", "", "", $object_id, $to_user, "al");
            else $object_link = "";
            
            // email schema
            if ($from_user != "" && !empty ($user_array[$from_user]['email'])) $email_schema = " [<a href='mailto:".$user_array[$from_user]['email']."'>".$user_array[$from_user]['email']."</a>]";
            else $email_schema = "";
          
            $body = "<span style=\"font-family:Verdana, Arial, Helvetica, sans-serif; font-size:14px;\"><strong>".$hcms_lang['task-management'][$to_lang]."-".$hcms_lang['end'][$to_lang]." '".$taskname."' (".$task_id.")</strong>\n".$hcms_lang['from'][$to_lang]." '".$from_user."'".$email_schema."\n\n".$description."\n\n".$object_link."</span>";
        
            $mailer = new HyperMailer();
            $mailer->IsHTML(true);
            $mailer->AddAddress ($user_array[$to_user]['email'], $to_user);
            $mailer->AddReplyTo ($user_array[$from_user]['email'], $from_user);
            $mailer->From = $user_array[$from_user]['email'];
            $mailer->Subject = "hyperCMS: ".$hcms_lang['task-management'][$to_lang]."-".$hcms_lang['end'][$to_lang]." '".$taskname."' (".$task_id.")";
            $mailer->CharSet = $hcms_lang_codepage[$to_lang];
            $mailer->Body = html_decode (nl2br ($body), $hcms_lang_codepage[$to_lang]);
            
            // send mail
            if ($mailer->Send())
            {
              $errcode = "00306";
              $error[] = $mgmt_config['today']."|hypercms_main.inc.php|info|$errcode|task end notification has been sent to ".$to_user." (".$user_array[$to_user]['email'].")"; 
            }
            else
            {
              $errcode = "50306";
              $error[] = $mgmt_config['today']."|hypercms_main.inc.php|error|$errcode|task end notification failed for ".$to_user." (".$user_array[$to_user]['email'].")";  
            }
          }
        }
      }
    }
  }
  
  // save log
  savelog (@$error);
  
  if (sizeof (@$error) > 0) return false;
  else return true;
}
?>