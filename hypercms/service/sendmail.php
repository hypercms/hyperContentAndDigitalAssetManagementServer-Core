<?php
/*
 * This file is part of
 * hyper Content & Digital Management Server - http://www.hypercms.com
 * Copyright (c) by hyper CMS Content Management Solutions GmbH
 */

// session
define ("SESSION", "create");

if (is_file ("config.inc.php"))
{
  // management configuration
  require_once ("config.inc.php");
  // hyperCMS API
  require_once ("function/hypercms_api.inc.php");
  // mailer class
  require_once ("function/hypermailer.class.php");
}
elseif (is_file ("../config.inc.php"))
{
  // management configuration
  require_once ("../config.inc.php");
  // hyperCMS API
  require_once ("../function/hypercms_api.inc.php");
  // mailer class
  require_once ("../function/hypermailer.class.php");
}


// input parameters
$service = getrequest ("service");
$mailfile = getrequest ("mailfile");
$token = getrequest ("token");
// objects
$site = getrequest_esc ("site", "publicationname");
$cat = getrequest_esc ("cat", "objectname");
$location = getrequest_esc ("location", "locationname");
$folder = getrequest_esc ("folder", "objectname");
$page = getrequest_esc ("page", "objectname");
$pagename = getrequest_esc ("pagename");
$multiobject = getrequest_esc ("multiobject");
// recipients
$action = getrequest_esc ("action");
$language = getrequest_esc ("language");
$user_login = getrequest_esc ("user_login");
$group_login = getrequest_esc ("group_login");
$user_group = getrequest_esc ("user_group");
// e-mail
$email_to = getrequest_esc ("email_to");
$email_cc = getrequest_esc ("email_cc");
$email_bcc = getrequest_esc ("email_bcc");
$email_title = getrequest_esc ("email_title");
$email_body = getrequest_esc ("email_body");
// download
$download_type = getrequest_esc ("download_type");
$format_img = getrequest_esc ("format_img", "array");
$format_doc = getrequest_esc ("format_doc", "array");
$format_vid = getrequest_esc ("format_vid", "array");
// validity
$valid_active = getrequest_esc ("valid_active");
$valid_days = getrequest_esc ("valid_days");
$valid_hours = getrequest_esc ("valid_hours");
// task
$task_create = getrequest_esc ("task_create");
$task_priority = getrequest_esc ("task_priority");
$task_startdate = getrequest_esc ("task_startdate");
$task_enddate = getrequest_esc ("task_enddate");
// send mail on date and time
$email_ondate = getrequest_esc ("email_ondate");
$email_date = getrequest_esc ("email_date");

// include values from mail file if no action has been requested
if ($mailfile != "" && valid_objectname ($mailfile) && $action == "")
{
  if (is_file ($mgmt_config['abs_path_data']."queue/".$mailfile.".php")) include ($mgmt_config['abs_path_data']."queue/".$mailfile.".php");
  elseif (is_file ($mgmt_config['abs_path_data']."message/".$mailfile.".php")) include ($mgmt_config['abs_path_data']."message/".$mailfile.".php");
  else $mailfile = "";
  
  // new variable names since version 8.0.0 (map old to new ones)
  if (!empty ($mail_title)) $email_title = $mail_title;
  if (!empty ($mail_body)) $email_body = $mail_body;
  if (!empty ($create_task)) $task_create = $create_task;
  if (!empty ($priority)) $task_priority = $priority;
  if (!empty ($startdate)) $task_startdate = $startdate;
  if (!empty ($finishdate)) $task_enddate = $finishdate;
  if (!empty ($ondate)) $email_ondate = $ondate;
  if (!empty ($maildate)) $email_date = $maildate;
  
  if (!empty ($multiobject_id))
  {
    $multiobject = getobjectlink ($multiobject_id);
  }
}

// publication management config
if (valid_publicationname ($site)) require ($mgmt_config['abs_path_data']."config/".$site.".conf.php");

// load language if it has not been loaded
if (!empty ($language) && empty ($hcms_lang['please-click-the-links-below-to-access-the-files'][$language]))
{
  require_once ($mgmt_config['abs_path_cms']."language/".getlanguagefile ($language));
}

// convert location
$location = deconvertpath ($location, "file");
$location_esc = convertpath ($site, $location, $cat);

// ------------------------------ permission section --------------------------------

// do not verify permissions if used as service call of minutely job
if (empty ($mailfile) || $action != "sendmail")
{ 
  // check access permissions
  $ownergroup = accesspermission ($site, $location, $cat);
  $setlocalpermission = setlocalpermission ($site, $ownergroup, $cat);  
  if (empty ($mgmt_config[$site]['sendmail']) || $ownergroup == false || $setlocalpermission['root'] != 1 || $setlocalpermission['sendlink'] != 1 || !valid_publicationname ($site) || !valid_locationname ($location)) killsession ($user);
  
  // check session of user
  checkusersession ($user);
}

// ---------------------------------- getallusers ----------------------------------
// function: getallusers()
// input: publication name [string,array] (optional)
// output: global arrays

function getallusers ($site)
{
  global $mgmt_config, $groupdata, $userdata, $allgroup_array, $alluseritem_array, $alluser_array, $allemail_array, $allrealname_array;

  // reinitalize (important!)
  $allgroup_array = array();
  $alluseritem_array = array();
  $alluser_array = array();
  $allemail_array = array();
  $allrealname_array = array();
  
  // load usergroup data
  if (empty ($groupdata) && valid_publicationname ($site)) $groupdata = loadfile ($mgmt_config['abs_path_data']."user/", $site.".usergroup.xml.php");  
  
  if (!empty ($groupdata)) $allgroup_array = getcontent ($groupdata, "<groupname>");
  
  // load user data
  if (empty ($userdata)) $userdata = loadfile ($mgmt_config['abs_path_data']."user/", "user.xml.php");  
                    
  if (!empty ($userdata))
  {
    // get user XML nodes
    if (valid_publicationname ($site)) $alluseritem_array = selectxmlcontent ($userdata, "<user>", "<publication>", $site);

    if (!empty ($alluseritem_array) && is_array ($alluseritem_array))
    {
      $i = 0;
      
      foreach ($alluseritem_array as $useritem)
      {
        if ($useritem != "")
        {
          $temp_array = getcontent ($useritem, "<login>");
          $alluser_array[$i] = $temp_array[0];
          $temp_array = getcontent ($useritem, "<realname>");
          $allrealname_array[$i] = $temp_array[0];
          $temp_array = getcontent ($useritem, "<email>");
          $allemail_array[$i] = $temp_array[0];
          
          $i++;
        }
      }
    }
  }
}

// --------------------------------- logic section ----------------------------------

// initalize
$add_onload = "";
$groupdata = "";
$userdata = "";
$allgroup_array = array();
$alluseritem_array = array();
$alluser_array = array();
$allemail_array = array();
$allrealname_array = array();
$memory_site = array();
$mail_error = array();
$mail_success = array();
$general_error = array();
$ccsent = false;
$bccsent = false;

// create multiobject_array if more than one file is selected
if ($multiobject != "")
{
  $multiobject_array = explode ("|", trim ($multiobject, "|"));
}

// collect publications from multiobject
if (isset ($multiobject_array) && is_array ($multiobject_array))
{
  foreach ($multiobject_array as $multiobject_entry)
  {  
    if (valid_locationname ($multiobject_entry))
    {                  
      $siteTemp = getpublication ($multiobject_entry);
      
      // collect publications
      if (!empty ($siteTemp) && !in_array ($siteTemp, $memory_site)) $memory_site[] = $siteTemp;
    }
  }
}

// define array if $user_login is not an array
if (!is_array ($user_login) && $user_login != "")
{
  $user_login_array = array ($user_login);
}
// define array if $user_login is empty
elseif (empty ($user_login))
{
  $user_login_array = array();
}
// assign input variable
else
{
  $user_login_array = $user_login;
}

// define data to be saved
if (valid_objectname ($user))
{
  // get object IDs
  if (!empty ($page) || !empty ($multiobject_array))
  { 
    // transform single object to multi object
    if (empty ($multiobject_array) && $location_esc != "" && $page != "")
    {
      if ($folder != "") $multiobject_temp = $location_esc.$folder."/".$page;
      else $multiobject_temp = $location_esc.$page;
      
      $multiobject_id = getobjectid ($multiobject_temp);
    }
    else
    {
      $multiobject_id = getobjectid ($multiobject);
    }
  }
  
  $message_data = array();
  // sender
  $message_data['user'] = $user;
  $message_data['site'] = $site;
  $message_data['cat'] = $cat;
  $message_data['location'] = $location_esc;
  $message_data['folder'] = $folder;
  $message_data['page'] = $page;
  $message_data['pagename'] = $pagename;
  $message_data['multiobject_id'] = $multiobject_id;
  // recipients
  $message_data['language'] = $language;
  $message_data['user_login'] = $user_login_array;
  $message_data['group_login'] = $group_login;
  $message_data['user_group'] = $user_group;
  // e-mail
  $message_data['email_to'] = $email_to;
  $message_data['email_cc'] = $email_cc;
  $message_data['email_bcc'] = $email_bcc;
  $message_data['email_title'] = $email_title;
  $message_data['email_body'] = $email_body;
  // download
  $message_data['download_type'] = $download_type;
  $message_data['format_img'] = $format_img;
  $message_data['format_doc'] = $format_doc;
  $message_data['format_vid'] = $format_vid;
  // validity
  $message_data['valid_active'] = $valid_active;
  $message_data['valid_days'] = $valid_days;
  $message_data['valid_hours'] = $valid_hours;
  // task
  $message_data['task_create'] = $task_create;
  $message_data['task_priority'] = $task_priority;
  $message_data['task_startdate'] = $task_startdate;
  $message_data['task_enddate'] = $task_enddate;
  // send mail on date and time
  $message_data['email_ondate'] = $email_ondate;
  $message_data['email_date'] = $email_date;
}

// ---------------------------------- save to queue ----------------------------------
if ($action == "savequeue" && checktoken ($token, $user) && valid_objectname ($user))
{
  // create queue entry
  $queue = createqueueentry ("mail", "", $email_date, 0, $message_data, $user);
      
  if ($queue == false) $general_error[] = getescapedtext ($hcms_lang['the-publishing-queue-could-not-be-saved'][$lang]);
  else $general_error[] = "<script language=\"JavaScript\" type=\"text/javascript\">window.close();</script>";
}
// ---------------------------------- send mail ----------------------------------
elseif ($action == "sendmail" && checktoken ($token, $user))
{
  if (valid_publicationname ($site) && valid_locationname ($location) && !empty ($mgmt_config['db_connect_rdbms']))
  {
    // --------------------------------- download formats ------------------------------
    
    $format_array = array();
    
    if (!empty ($format_img) && is_array ($format_img))
    {
      foreach ($format_img as $value)
      {
        // image format and config provided
        if (substr_count ($value, "|") > 0)
        {
          list ($ext, $config) = explode ("|", $value);        
          $format_array['image'][$ext][$config] = 1;
        }
        // only image format
        else
        {
          $format_array['image'][$value] = 1;
        }
      }
    }
    
    if (!empty ($format_doc) && is_array ($format_doc))
    {
      foreach ($format_doc as $ext)
      {
        // document format
        $format_array['document'][$ext] = 1;
      }
    }
    
    if (!empty ($format_vid) && is_array ($format_vid))
    {
      foreach ($format_vid as $ext)
      {
        // document format
        $format_array['video'][$ext] = 1;
      }
    }
    
    if (sizeof ($format_array) > 0) $formats = json_encode ($format_array);
    else $formats = "";

    // --------------------------- lifetime / period of validity ------------------------
    
    if ($valid_active == "yes")
    {
      // transform to seconds
      if ($valid_days != "") $valid_days = intval ($valid_days) * 24 * 60 * 60;
      if ($valid_hours != "") $valid_hours = intval ($valid_hours) * 60 * 60;
      
      // lifetime of token in seconds
      $lifetime = $valid_days + $valid_hours;
    }
    else $lifetime = 0;

    // ------------------------------------------- create new user account ------------------------------------------

    if (!empty ($email_to) && is_array ($email_to) && !empty ($user_group) && !empty ($language))
    {
      if (!empty ($page) || is_array ($multiobject_array))
      {
        $login = "User".date ("YmdHis", time());
        
        // add new user to login array
        $user_login_array[] = $login;
        
        // generate password from upper case letter and session ID
        $password = $confirm_password = "P".substr (session_id(), 0, 9);
           
        $result = createuser ($site, $login, $password, $confirm_password, $user);
        
        if ($result['result'] == true)
        {
          // create names form e-mails
          $realnames = array();
          
          $email_to = array_unique ($email_to);
          
          foreach ($email_to as $temp)
          {
            $realnames[] = substr ($temp, 0, strpos ($temp, "@"));
          }
          
          // publication membership
          $usersite = implode ("|", $memory_site);
          
          // user group membership
          $usergroup = $user_group;
          
          // assign publication access and group membership
          $result = edituser ($site, $login, "", "", "", "", implode (", ", $realnames), $language, "", "", implode (", ", $email_to), "", "", $usergroup, $usersite, $user);
        }
        else
        {
          $general_error[] = $result['message'];
        }
      }
    }

    // load user and group information for publication (used for sending mails and send mail form)
    getallusers ($site);
    
    // ------------------------------ send mail to existing users or members of user group ---------------------------
    
    $email_to_array = array();
    
    if ((!empty ($user_login_array) &&  is_array ($user_login_array) && sizeof ($user_login_array) > 0) || (!empty ($group_login) && is_string ($group_login)))
    {
      // get users of group
      if (!empty ($group_login))
      {
        foreach ($alluseritem_array as $useritem)
        {
          $temp_array = selectcontent ($useritem, "<memberof>", "<usergroup>", "*|".$group_login."|*");
          
          if (is_array ($temp_array) && sizeof ($temp_array) > 0)
          {
            foreach ($temp_array as $temp)
            {
              $site_array = getcontent ($temp, "<publication>");
              
              if (!empty ($site_array[0]) && $site_array[0] == $site)
              {  
                $temp_user_array = getcontent ($useritem, "<login>");
                if (!empty ($temp_user_array[0])) $user_login_array[] = $temp_user_array[0];
              }
            }
          }
        }
      }
      
      // email and signature of sender
      if (!empty ($_SESSION['hcms_user']))
      {
        $mail_sender_array = selectcontent ($userdata, "<user>", "<login>", getsession ('hcms_user'));
        
        if (!empty ($mail_sender_array))
        {
          // real name
          $temp_array = getcontent ($mail_sender_array[0], "<realname>");
          
          if (!empty ($temp_array[0]))
          {
            $realname_from = $temp_array[0];
          }
          else $realname_from = "";
          
          // email
          $temp_array = getcontent ($mail_sender_array[0], "<email>");
          
          if (!empty ($temp_array[0]))
          {
            $email_from = $temp_array[0];
          }
          elseif (!empty ($mgmt_config[$site]['mailserver']))
          {
            $email_from = "automailer@".$mgmt_config[$site]['mailserver'];
          }
          else $email_from = "automailer@hypercms.net";
          
          // signature
          $temp_array = getcontent ($mail_sender_array[0], "<signature>");
          
          if (!empty ($temp_array[0]))
          {
            $mail_signature = $temp_array[0];           
          }
          else $mail_signature = "";
        }
      }
      
      // email of receivers 
      if (is_array ($user_login_array) && !empty ($userdata))
      {
        $user_login_array = array_unique ($user_login_array);
        
        foreach ($user_login_array as $user_to)
        {
          // get user node and extract required information
          $mail_receiver_array = selectcontent ($userdata, "<user>", "<login>", $user_to);
          
          if ($mail_receiver_array != false)
          {
            // real name
            $buffer_array = getcontent ($mail_receiver_array[0], "<realname>");
            
            if (!empty ($buffer_array[0]))
            {
              $temp_realname_to = $buffer_array[0];
            }
            
            // email
            $temp_array = getcontent ($mail_receiver_array[0], "<email>");
            
            if (!empty ($temp_array[0]))
            {
              $temp_email_to = $email_to_array[] = $temp_array[0];
            }
            else
            {
              $general_error[] = str_replace ("%user%", $temp_realname_to, $hcms_lang['e-mail-address-of-user-s-is-missing'][$lang]);
            }
            
            // language
            $buffer_array = getcontent ($mail_receiver_array[0], "<language>");
            
            if (!empty ($buffer_array[0]))
            {
              $temp_user_lang = $buffer_array[0];

              if ($temp_user_lang != $lang) require_once ($mgmt_config['abs_path_cms']."language/".getlanguagefile ($temp_user_lang));
            }
            else
            {
              $temp_user_lang = $lang;            
            }
          }
          
          // send mail to receiver
          $mailer = new HyperMailer();
          $mailer->IsHTML(true);
          $mailer->CharSet = $hcms_lang_codepage[$lang];          
          $mail_link = "";
          
          if (!empty ($temp_email_to))
          {   
            // create links
            if (!empty ($page) || !empty ($multiobject_array))
            { 
              // send file as link
              if ($download_type == "link" || $download_type == "download")
              {
                // transform single object to multi object
                if (empty ($multiobject_array) && $location_esc != "" && $page != "")
                {
                  if ($folder != "") $multiobject_array[0] = $location_esc.$folder."/".$page;
                  else $multiobject_array[0] = $location_esc.$page;
                }
            
                $mail_links = array();

                // multi object
                if (isset ($multiobject_array) && is_array ($multiobject_array) && sizeof ($multiobject_array) > 0)
                {
                  // create access link
                  if ($download_type == "link")
                  {
                    $link = createmultiaccesslink ($multiobject_array, $user_to, "al", $lifetime, $formats);
                    
                    if (empty ($link)) $link = $hcms_lang['error-object-id-is-missing-for-'][$temp_user_lang].$hcms_lang['access-link'][$temp_user_lang];
                    
                    // links to send
                    $mail_link .= $link."\n\n";
                    // for mail report
                    $mail_links[] = $link;
                  }
                  // create download links
                  elseif ($download_type == "download")
                  {
                    foreach ($multiobject_array as $multiobject_entry)
                    {
                      if (valid_locationname ($multiobject_entry))
                      {
                        $siteTemp = getpublication ($multiobject_entry);
                        $locationTemp = getlocation ($multiobject_entry);
                        $catTemp = getcategory ($siteTemp, $locationTemp);
                        $pageTemp = getobject ($multiobject_entry);

                        $link = createaccesslink ($siteTemp, $locationTemp, $pageTemp, $catTemp, "", $user_to, "dl", $lifetime, $formats);                   

                        if (empty ($link)) $link = $hcms_lang['error-object-id-is-missing-for-'][$temp_user_lang].$multiobject_entry;
                        
                        // links to send
                        $mail_link .= specialchr_decode ($pageTemp)."\n".$link."\n\n";
                        // for mail report
                        $mail_links[] = $link;
                      }
                    }
                  }
                }
              }
              // send attachments (no folders allowed!)
              else
              {
                // transform single object to multi object (folders cant be attached!)
                if (empty ($multiobject_array) && $location_esc != "" && $page != "" && $folder == "")
                {
                  $multiobject_array[0] = $location_esc.$page;
                }
                
                // multi object
                if (isset ($multiobject_array) && is_array ($multiobject_array))
                {
                  foreach ($multiobject_array as $multiobject_entry)
                  {
                    if (valid_locationname ($multiobject_entry))
                    {
                      $siteTemp = getpublication ($multiobject_entry);
                      $locationTemp = getlocation ($multiobject_entry);
                      $catTemp = getcategory ($siteTemp, $locationTemp);
                      $locationTemp = deconvertpath ($locationTemp, "file");
                      $pageTemp = getobject ($multiobject_entry);

                      if ($pageTemp != ".folder" && is_file ($locationTemp.$pageTemp))
                      {
                        $objectdata = loadfile ($locationTemp, $pageTemp);
                        
                        if ($objectdata != false)
                        {
                          $mediafile = getfilename ($objectdata, "media");
                          $mediadir = getmedialocation ($siteTemp, $mediafile, "abs_path_media");
                          
                          if ($mediafile != false)
                          {
                            $mediafile_conv = false;
                            
                            // temp location
                            $location_conv = $mgmt_config['abs_path_temp'];
                            
                            // convert file if format is not original (image)
                            if ($format_img[0] != "original" && is_image ($mediafile))
                            {
                              list ($format, $media_config) = explode ("|", $format_img[0]);
                              $mediafile_conv = convertmedia ($siteTemp, $mediadir.$siteTemp."/", $location_conv, $mediafile, $format, $media_config, true);
                            }
                            
                            // convert file if format is not original (document)
                            if ($format_doc[0] != "original" && is_document ($mediafile))
                            {
                              $mediafile_conv = convertmedia ($siteTemp, $mediadir.$siteTemp."/", $location_conv, $mediafile, $format_doc[0], "", true);
                            }
                            
                            // convert file if format is not original (video)
                            if ($format_vid[0] != "original" && is_video ($mediafile))
                            {
                              $mediafile_conv = convertmedia ($siteTemp, $mediadir.$siteTemp."/", $location_conv, $mediafile, $format_vid[0], "", true);
                            }  

                            // if converted
                            if ($mediafile_conv != "")
                            {
                              $attachment = $location_conv.$mediafile_conv;
                            }
                            // use original file
                            else
                            {
                              // load from cloud storage
                              if (function_exists ("loadcloudobject")) loadcloudobject ($siteTemp, $mediadir.$siteTemp."/", $mediafile, $user);

                              // prepare media file
                              $temp_source = preparemediafile ($siteTemp, $mediadir.$siteTemp."/", $mediafile, $user);
                              
                              // if encrypted
                              if (!empty ($temp_source['result']) && !empty ($temp_source['crypted']) && !empty ($temp_source['templocation']) && !empty ($temp_source['tempfile']))
                              {
                                $attachment = $temp_source['templocation'].$temp_source['tempfile'];
                              }
                              // if restored
                              elseif (!empty ($temp_source['result']) && !empty ($temp_source['restored']) && !empty ($temp_source['location']) && !empty ($temp_source['file']))
                              {
                                $attachment = $temp_source['location'].$temp_source['file'];
                              }
                              else $attachment = $mediadir.$siteTemp."/".$mediafile;
                            }
                            
                            // define file name for attachment
                            $page_info = getfileinfo ($siteTemp, $pageTemp, $catTemp);
                            $attachment_ext = strtolower (strrchr ($attachment, "."));
                            $attachment_name = substr ($page_info['name'], 0, strrpos ($page_info['name'], ".")).$attachment_ext;

                            // attach file
                            $mailer->AddAttachment ($attachment, $attachment_name);
                            
                            // delete temp file
                            if (!empty ($temp_source['result']) && !empty ($temp_source['created'])) deletefile ($temp_source['templocation'], $temp_source['tempfile'], 0);
                          }
                        }
                      }
                    }
                  }
                }
              }
            }
            
            // mail body
            $mail_fullbody = $email_body."\n\n";
            
            if ($mail_link != "")
            {
              $mail_fullbody .= $hcms_lang['please-click-the-links-below-to-access-the-files'][$temp_user_lang].":\n\n".$mail_link."\n\n";
            }
            
            $mail_fullbody .= $mail_signature;
            $mail_fullbody = "<span style=\"font-family:Verdana, Arial, Helvetica, sans-serif; font-size:14px;\">".$mail_fullbody."</span>";
            
            // mail header
            // if the mailserver config entry is empty, the email address of the user will be used for FROM
            $mail_header = "";
            
            if (!empty ($email_from) && $mgmt_config[$site]['mailserver'] == "")
            {
              $mailer->From = $email_from;
              $mailer->FromName = $realname_from;
            }
            elseif (!empty ($mgmt_config[$site]['mailserver']))
            {
              $mailer->From = "automailer@".$mgmt_config[$site]['mailserver'];
              $mailer->FromName = "hyperCMS Automailer";
            }
            else
            {
              $mailer->From = "automailer@hypercms.net";
              $mailer->FromName = "hyperCMS Automailer";
            }
            
            // Reply-To
            if ($email_from != "")
            {
              $mailer->AddReplyTo ($email_from);
            }
            
            // CC
            if ($email_cc != "" && !$ccsent)
            {
              $ccsent = true;
              $mails = splitstring ($email_cc);
              
              if (is_array ($mails))
              {
                $mails = array_unique ($mails);
                
                foreach ($mails as $mail)
                {
                  $mailer->AddCC ($mail);
                }
              }
            }
            
            // BCC
            if ($email_bcc != "" && !$bccsent)
            {
              $bccsent = true;
              $mails = splitstring ($email_bcc);
              
              if (is_array ($mails))
              {
                $mails = array_unique ($mails);
                      
                foreach ($mails as $mail)
                {
                  $mailer->AddBCC ($mail);
                }
              }
            }
            
            // subject and body
            $mailer->Subject = html_decode ($email_title, $hcms_lang_codepage[$lang]);
            $mailer->Body = html_decode (nl2br ($mail_fullbody), $hcms_lang_codepage[$lang]);
            
            // create email recipient array
            $temp_email_to_array = splitstring ($temp_email_to);
            
            if (is_array ($temp_email_to_array))
            {
              $temp_email_to_array = array_unique ($temp_email_to_array);
              
              foreach ($temp_email_to_array as $temp)
              {
                $mailer->AddAddress ($temp);
              }
            }
            
            // send mail
            if ($mailer->Send())
            {
              $mail_success[] = $temp_email_to;
              
              // save message
              savemessage ($message_data, "mail", $user);
              
              $errcode = "00101";
              $error[] = $mgmt_config['today']."|user_sendlink.php|information|$errcode|e-mail message was sent to ".$temp_email_to." by user ".$user;
            }
            else
            {
              $mail_error[] = $temp_email_to;
              
              $errcode = "20101";
              $error[] = $mgmt_config['today']."|user_sendlink.php|error|$errcode|e-mail message could not be sent to ".$temp_email_to." by user ".$user;
            }
          }
        }
      }
    }
  }
  else
  {
    $general_error[] = $hcms_lang['object-is-not-defined'][$lang];
  }
  
  // ------------ save recipients and create new task for user on success --------------
  
  if (is_array ($mail_success))
  {
    // multi object
    if (isset ($multiobject_array) && is_array ($multiobject_array))
    {
      foreach ($multiobject_array as $multiobject_entry)
      {
        if (valid_locationname ($multiobject_entry))
        {
          $siteTemp = getpublication ($multiobject_entry);
          $locationTemp = getlocation ($multiobject_entry);
          $catTemp = getcategory ($siteTemp, $locationTemp);
          $pageTemp = getobject ($multiobject_entry);
          $objectpath = convertpath ($siteTemp, $locationTemp.$pageTemp, $catTemp);
          
          rdbms_createrecipient ($objectpath, $user, implode (",", $user_login_array), implode (",", $email_to_array));
          
          // create new task for each user
          if (!empty ($task_create))
          {
            foreach ($user_login_array as $user_to)
            {
              if (function_exists ("createtask")) createtask ($siteTemp, $user, $email_from, $user_to, "", $task_startdate, $task_enddate, "user", $objectpath, $email_title, $email_body, false, $task_priority);
            }
          }
        }
      }
    }
    // single object
    elseif ($location != "" && $page != "")
    {
      if ($folder != "")
      {
        $locationTemp = $location.$folder."/";
      }
      else $locationTemp = $location;
      
      $objectpath = convertpath ($site, $locationTemp.$page, $cat);
             
      rdbms_createrecipient ($objectpath, $user, implode (",", $user_login_array), implode (",", $email_to_array));
      
      // create new task for each user
      if (!empty ($task_create))
      {
        foreach ($user_login_array as $user_to)
        {
          if (function_exists ("createtask")) createtask ($siteTemp, $user, $email_from, $user_to, "", $task_startdate, $task_enddate, "user", $objectpath, $email_title, $email_body, false, $task_priority);
        }
      }      
    }
  }
  
  // reset mail address
  $email_to = "";  
}

// -------------------------------------- check if attachment can be added or files can be downloaded -----------------------------------------

// folders can not be send as attachments and can only be provided as download link of original files
$allow_attachment = true;
$allow_download = true;

if ($page != "" || is_array ($multiobject_array))
{
  // we only check components
  if ($cat == "comp")
  {
    // multiobjects
    if (!empty ($multiobject_array) && is_array ($multiobject_array))
    {
      foreach ($multiobject_array as $multiobject_entry)
      {
        $filePath = deconvertpath ($multiobject_entry, "file");
        
        // folder
        if (getobject ($multiobject_entry) == ".folder" || is_dir ($filePath))
        {
          $allow_attachment = false;
          break;
        }
        // component
        elseif (is_file ($filePath))
        {
          $filedata = loadfile (getlocation ($filePath), getobject ($filePath));
          $media = getfilename ($filedata, "media");
          
          if (empty ($media))
          {
            $allow_attachment = false;
            break;
          }
        }
      }
    }
    // folder
    elseif (getobject ($location.$page) == ".folder" || is_dir ($location.$page))
    {
      $allow_attachment = false;
    }
    // component
    elseif (is_file ($location.$page))
    {
      $filedata = loadfile ($location, $page);
      $media = getfilename ($filedata, "media");
      
      if (empty ($media))
      {
        $allow_attachment = false;
        $allow_download = false;
      }
    }
  }
  // no pages allowed
  else $allow_attachment = false;
}

// save log
savelog (@$error);

// ----------------------------------------------- return JSON encoded result if used as service ----------------------------------------------------
if ($service)
{
  // request from autosave
  header ('Content-Type: application/json; charset=utf-8');

  $result = array();
  
  if (!empty ($mail_success) && is_array ($mail_success)) $result['success'] = $mail_success;  
  if (!empty ($mail_error) && is_array ($mail_error)) $result['error'] = $mail_error;  
  if (!empty ($general_error) && is_array ($general_error)) $result['general'] = $general_error;  
  if (!empty ($mail_links) && is_array ($mail_links)) $result['links'] = $mail_links;  
  
  echo json_encode ($result);
}
?>