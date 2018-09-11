<?php
/*
 * This file is part of
 * hyper Content & Digital Management Server - http://www.hypercms.com
 * Copyright (c) by hyper CMS Content Management Solutions GmbH
 *
 * You should have received a copy of the License along with hyperCMS.
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
$intention = getrequest_esc ("intention");
$language = getrequest_esc ("language");
$user_login = getrequest_esc ("user_login");
$group_login = getrequest_esc ("group_login");
$user_group_dummy = getrequest_esc ("user_group_dummy");
$user_group = getrequest_esc ("user_group");
// mail
$email_to = getrequest_esc ("email_to");
$email_cc = getrequest_esc ("email_cc");
$email_bcc = getrequest_esc ("email_bcc");
$mail_title = getrequest_esc ("mail_title");
$mail_body = getrequest_esc ("mail_body");
// download
$download_type = getrequest_esc ("download_type");
$format_img = getrequest_esc ("format_img", "array");
$format_doc = getrequest_esc ("format_doc", "array");
$format_vid = getrequest_esc ("format_vid", "array");
$valid_active = getrequest_esc ("valid_active");
$valid_days = getrequest_esc ("valid_days");
$valid_hours = getrequest_esc ("valid_hours");
$include_metadata = getrequest_esc ("include_metadata");
// task
$create_task = getrequest_esc ("create_task");
$priority = getrequest_esc ("priority");
$startdate = getrequest_esc ("startdate");
$finishdate = getrequest_esc ("finishdate");
// send mail on date and time
$ondate = getrequest_esc ("ondate");
$maildate = getrequest_esc ("maildate");

// include values from mail file
if ($mailfile != "" && valid_objectname ($mailfile))
{
  if (is_file ($mgmt_config['abs_path_data']."queue/".$mailfile.".php")) include ($mgmt_config['abs_path_data']."queue/".$mailfile.".php");
  elseif (is_file ($mgmt_config['abs_path_data']."message/".$mailfile.".php")) include ($mgmt_config['abs_path_data']."message/".$mailfile.".php");
  else $mailfile = "";
  
  if (!empty ($multiobject_id))
  {
    $multiobject = getobjectlink ($multiobject_id);
  }
}

// publication management config
if (valid_publicationname ($site)) require ($mgmt_config['abs_path_data']."config/".$site.".conf.php");

// convert location
$location = deconvertpath ($location, "file");
$location_esc = convertpath ($site, $location, $cat);

// ------------------------------ permission section --------------------------------

// do not verify permissions if used as service call of minutely job
if (empty ($mailfile) || $intention != "sendmail")
{ 
  // check access permissions
  $ownergroup = accesspermission ($site, $location, $cat);
  $setlocalpermission = setlocalpermission ($site, $ownergroup, $cat);  
  if (empty ($mgmt_config[$site]['sendmail']) || $ownergroup == false || $setlocalpermission['root'] != 1 || $setlocalpermission['sendlink'] != 1 || !valid_publicationname ($site) || !valid_locationname ($location)) killsession ($user);
  
  // check session of user
  checkusersession ($user);
}

// --------------------------------- logic section ----------------------------------

$add_onload = "";
$groupdata = "";
$userdata = "";
$allgroup_array = array();
$alluseritem_array = array();
$allrealname_array = array();
$alluser_array = array();
$allemail_array = array();

// ---------------------------------- getallusers ----------------------------------
// function: getallusers()
// input: location, object
// output: global arrays

function getallusers ()
{
  global $groupdata, $userdata, $alluseritem_array, $alluser_array, $allemail_array, $allrealname_array, $allgroup_array, $mgmt_config, $site;
  
  // load usergroup data
  if (empty ($groupdata) && valid_publicationname ($site)) $groupdata = loadfile ($mgmt_config['abs_path_data']."user/", $site.".usergroup.xml.php");  
  
  if ($groupdata != false) $allgroup_array = getcontent ($groupdata, "<groupname>");
  
  // load user data
  if (empty ($userdata)) $userdata = loadfile ($mgmt_config['abs_path_data']."user/", "user.xml.php");  
                    
  if ($userdata != false)
  {
    $alluseritem_array = selectxmlcontent ($userdata, "<user>", "<publication>", $site);
    
    if ($alluseritem_array != false)
    {    
      $i = 0;
      
      foreach ($alluseritem_array as $useritem)
      {  
        $buffer_array = getcontent ($useritem, "<login>");
        $alluser_array[$i] = $buffer_array[0];
        $buffer_array = getcontent ($useritem, "<realname>");
        $allrealname_array[$i] = $buffer_array[0];
        $buffer_array = getcontent ($useritem, "<email>");
        $allemail_array[$i] = $buffer_array[0];
        
        $i++;
      }
    }
  }
}

// get all users
getallusers ();

// define array if $user_login is not an array
if (!is_array ($user_login) && $user_login != "")
{
  $user_login = array ($user_login);
}
// define array if $user_login is false
elseif (!$user_login)
{
  $user_login = array();
}

// create multiobject_array if more than one file is selected
if ($multiobject != "")
{
  $multiobject_array = explode ("|", trim ($multiobject, "|"));
}

$mail_error = array();
$mail_success = array();
$general_error = array();
$ccsent = false;
$bccsent = false;
$metadata_str = "";

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
  $message_data['user_login'] = $user_login;
  $message_data['group_login'] = $group_login;
  $message_data['user_group_dummy'] = $user_group_dummy;
  $message_data['user_group'] = $user_group;
  // mail
  $message_data['email_to'] = $email_to;
  $message_data['email_cc'] = $email_cc;
  $message_data['email_bcc'] = $email_bcc;
  $message_data['mail_title'] = $mail_title;
  $message_data['mail_body'] = $mail_body;
  // download
  $message_data['download_type'] = $download_type;
  $message_data['format_img'] = $format_img;
  $message_data['format_doc'] = $format_doc;
  $message_data['format_vid'] = $format_vid;
  $message_data['valid_active'] = $valid_active;
  $message_data['valid_days'] = $valid_days;
  $message_data['valid_hours'] = $valid_hours;
  $message_data['include_metadata'] = $include_metadata;
  // task
  $message_data['create_task'] = $create_task;
  $message_data['priority'] = $priority;
  $message_data['startdate'] = $startdate;
  $message_data['finishdate'] = $finishdate;
  // send mail on date and time
  $message_data['ondate'] = $ondate;
  $message_data['maildate'] = $maildate;
}

// ---------------------------------- save to queue ----------------------------------
if ($intention == "savequeue" && checktoken ($token, $user) && valid_objectname ($user))
{
  // create queue entry
  $queue = createqueueentry ("mail", "", $maildate, 0, $message_data, $user);
      
  if ($queue == false) $general_error[] = getescapedtext ($hcms_lang['the-publishing-queue-could-not-be-saved'][$lang]);
  else $general_error[] = "<script language=\"JavaScript\" type=\"text/javascript\">window.close();</script>";
}
// ---------------------------------- send mail ----------------------------------
elseif ($intention == "sendmail" && checktoken ($token, $user))
{
  if (valid_publicationname ($site) && valid_locationname ($location) && $mgmt_config['db_connect_rdbms'] != "")
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
  
    // ------------------------------------ to new user ---------------------------------

    if (is_array ($email_to) && !empty ($email_to) && $user_group != "" && $language != "")
    {
      // create new user if link will be send
      if (!empty ($page) || is_array ($multiobject_array))
      {
        $login = "User".date ("YmdHis", time());
        $user_login[] = $login;
        
        // generate password from upper case letter and session ID
        $password = $confirm_password = "P".substr (session_id(), 0, 9);
           
        $result = createuser ($site, $login, $password, $confirm_password, $user);
        
        if ($result['result'] == true)
        {
          // create names form e-mails
          $realnames = array();
          
          foreach ($email_to as $buffer)
          {
            $realnames[] = substr ($buffer, 0, strpos ($buffer, "@"));
          }
          
          $usergroup = "|".$user_group."|";
          $result = edituser ($site, $login, "", "", "", "", implode(", ", $realnames), $language, "", implode(", ", $email_to), "", "", $usergroup, "", $user);
        }
        else
        {
          $general_error[] = $result['message'];
        }
        
        getallusers ();
      }
      else
      {
        $result['result'] = true;
      }
       
      if ($result['result'] == true)
      {
        // email and signature of sender
        if ($userdata != false)
        {
          $mail_sender_array = selectcontent ($userdata, "<user>", "<login>",getsession ('hcms_user'));
          
          if ($mail_sender_array != false)
          {
            // real name
            $buffer_array = getcontent ($mail_sender_array[0], "<realname>");
            
            if (!empty ($buffer_array[0]))
            {
              $realname_from = $buffer_array[0];
            }
            else $realname_from = "";
            
            // email
            $buffer_array = getcontent ($mail_sender_array[0], "<email>");
            
            if (!empty ($buffer_array[0]))
            {
              $email_from = $buffer_array[0];
            }
            elseif (!empty ($mgmt_config[$site]['mailserver']))
            {
              $email_from = "automailer@".$mgmt_config[$site]['mailserver'];
            }
            else $email_from = "automailer@hypercms.net";
            
            // signature
            $buffer_array = getcontent ($mail_sender_array[0], "<signature>");
            
            if (!empty ($buffer_array[0]))
            {
              $mail_signature = $buffer_array[0];              
            }
            else $mail_signature = "";
          }
        }
        
        $mailer = new HyperMailer();
        $mailer->IsHTML(true);
        $mailer->CharSet = $hcms_lang_codepage[$lang];
        
        // if the mailserver config entry is empty, the email address of the user will be used for FROM
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
        if ($email_cc != "")
        {
          $ccsent = true;
          $mails = splitstring ($email_cc);
          
          if (is_array ($mails))
          {          
            foreach ($mails as $mail)
            {
              $mailer->AddCC ($mail);
            }
          }
        }
        
        // BCC
        if ($email_bcc != "")
        {
          $bccsent = true;
          $mails = splitstring ($email_bcc);
          
          if (is_array ($mails))
          {
            foreach ($mails as $mail)
            {
              $mailer->AddBCC ($mail);
            }
          }
        }
        
        $metadata = "";
        $mail_link = "";
      
        // create links
        if (!empty ($page) || !empty ($multiobject_array))
        {        
          // send file as download or access link
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

                  // create link for hyperCMS access
                  if ($download_type == "link")
                  {
                    $link = createaccesslink ($siteTemp, $locationTemp, $pageTemp, $catTemp, "", $login, "al", $lifetime, $formats);
                  }
                  // create download link
                  elseif ($download_type == "download")
                  {
                    $link = createaccesslink ($siteTemp, $locationTemp, $pageTemp, $catTemp, "", $login, "dl", $lifetime, $formats);
                  } 
                  
                  if ($link != "")
                  {
                    if ($include_metadata == "yes")
                    {
                      $metadata = $hcms_lang['meta-data'][$lang].":\n".getmetadata ($locationTemp, $pageTemp);
                    }
                    else
                    {
                      $metadata = "";
                    }
                  }
                  else $link = $hcms_lang['error-object-id-is-missing-for-'][$lang].$multiobject_entry;
                  
                  // links to send
                  $mail_link .= $link."\n\n".$metadata."\n\n";
                  // for mail report
                  $mail_links[] = $link;                   
                }
              }
            }
          }
          // send file as attachment in mail (no folders allowed!)
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
   
                        if ($include_metadata == "yes")
                        {
                          $metadata_str .= specialchr_decode ($pageTemp)."\n-------------------\n".$hcms_lang['meta-data'][$lang].":\n".getmetadata ($locationTemp, $pageTemp)."\n\n";
                        }
                      }
                    }
                  }
                }
              }
            }
          }
        }
        
        // mail body
        $mail_fullbody = $mail_body."\n\n";
        
        if ($include_metadata == "yes")
        {
          $mail_fullbody .= $metadata_str."\n";
        }
        
        if ($mail_link != "")
        {
          $mail_fullbody .= $hcms_lang['please-click-the-links-below-to-access-the-files'][$language].":\n".$mail_link."\n\n";
        }
        
        $mail_fullbody .= $mail_signature;
        $mail_fullbody = "<span style=\"font-family:Verdana, Arial, Helvetica, sans-serif; font-size:14px;\">".$mail_fullbody."</span>";

        // subject and body
        $mailer->Subject = html_decode ($mail_title, $hcms_lang_codepage[$lang]);
        $mailer->Body = html_decode (nl2br ($mail_fullbody), $hcms_lang_codepage[$lang]);        
        
        foreach ($email_to as $mail_address)
        {
          $mailer->AddAddress ($mail_address);
        }
        
        // send mail
        if ($mailer->Send())
        {
          $mail_success[] = implode (",", $email_to);
          
          // save message
          savemessage ($message_data, "mail", $user);
          
          $errcode = "00101";
          $error[] = $mgmt_config['today']."|user_sendlink.php|information|$errcode|e-mail message was sent to ".$email_to." by user ".$user;
        }
        else
        {
          $mail_error[] = implode (",", $email_to);
          
          $errcode = "20101";
          $error[] = $mgmt_config['today']."|user_sendlink.php|error|$errcode|e-mail message could not be sent to ".$email_to." by user ".$user;
        }
        
        $email_to = "";
      }
    } 
    
    // ------------------------------ to existing user or group ---------------------------
    
    if (is_array ($user_login) && (!empty ($user_login) || $group_login != ""))
    {        
      // get users of group
      if ($group_login != "")
      {
        foreach ($alluseritem_array as $useritem)
        {
          $buffer_array = selectcontent ($useritem, "<memberof>", "<usergroup>", "*|".$group_login."|*");
          
          if (is_array ($buffer_array))
          {
            foreach ($buffer_array as $buffer)
            {
              $site_array = getcontent ($buffer, "<publication>");
              
              if ($site_array != false && $site_array[0] == $site)
              {
                $buffer = getcontent ($useritem, "<login>");
                $user_login[] = $buffer[0];
              }
            }
          }
        }
      }
      
      // email of sender
      if (!empty ($_SESSION['hcms_user']))
      {
        $mail_sender_array = selectcontent ($userdata, "<user>", "<login>", getsession ('hcms_user'));
        
        if ($mail_sender_array != false)
        {
          // real name
          $buffer_array = getcontent ($mail_sender_array[0], "<realname>");
          
          if (!empty ($buffer_array[0]))
          {
            $realname_from = $buffer_array[0];
          }
          else $realname_from = "";
          
          // email
          $buffer_array = getcontent ($mail_sender_array[0], "<email>");
          
          if (!empty ($buffer_array[0]))
          {
            $email_from = $buffer_array[0];
          }
          elseif (!empty ($mgmt_config[$site]['mailserver']))
          {
            $email_from = "automailer@".$mgmt_config[$site]['mailserver'];
          }
          else $email_from = "automailer@hypercms.net";
          
          // signature
          $buffer_array = getcontent ($mail_sender_array[0], "<signature>");
          
          if (!empty ($buffer_array[0]))
          {
            $mail_signature = $buffer_array[0];           
          }
          else $mail_signature = "";
        }
      }
      
      // email of receivers 
      if (is_array ($user_login) && $userdata != false)
      {
        array_unique ($user_login);
        
        foreach ($user_login as $user_to)
        {
          // get user node and extract required information
          $mail_receiver_array = selectcontent ($userdata, "<user>", "<login>", $user_to);
          
          if ($mail_receiver_array != false)
          {
            // real name
            $buffer_array = getcontent ($mail_receiver_array[0], "<realname>");
            
            if (!empty ($buffer_array[0]))
            {
              $realname_to = $buffer_array[0];
            }
            
            // email
            $buffer_array = getcontent ($mail_receiver_array[0], "<email>");
            
            if (!empty ($buffer_array[0]))
            {
              $email_to = $buffer_array[0];
            }
            else
            {
              $general_error[] = str_replace ("%user%", $realname_to, $hcms_lang['e-mail-address-of-user-s-is-missing'][$lang]);
            }
            
            // language
            $buffer_array = getcontent ($mail_receiver_array[0], "<language>");
            
            if (!empty ($buffer_array[0]))
            {
              $user_lang = $buffer_array[0];

              if ($user_lang != $lang) require_once ($mgmt_config['abs_path_cms']."language/".getlanguagefile ($user_lang));
            }
            else
            {
              $user_lang = $lang;            
            }
          }
          
          // send mail to receiver
          $mailer = new HyperMailer();
          $mailer->IsHTML(true);
          $mailer->CharSet = $hcms_lang_codepage[$lang];
          
          $metadata = "";
          $mail_link = "";
          
          if ($email_to != "")
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
                      
                      // create link for hyperCMS access
                      if ($download_type == "link")
                      {
                        $link = createaccesslink ($siteTemp, $locationTemp, $pageTemp, $catTemp, "", $user_to, "al", $lifetime, $formats);
                      }
                      // create download link
                      elseif ($download_type == "download")
                      {
                        $link = createaccesslink ($siteTemp, $locationTemp, $pageTemp, $catTemp, "", $user_to, "dl", $lifetime, $formats);
                      }                     
                              
                      if ($link != "")
                      {
                        if ($include_metadata == "yes")
                        {
                          $metadata = $hcms_lang['meta-data'][$user_lang].":\n".getmetadata ($locationTemp, $pageTemp);
                        }
                        else
                        {
                          $metadata = "";
                        }
                      }
                      else $link = $hcms_lang['error-object-id-is-missing-for-'][$lang].$multiobject_entry;
                      
                      // links to send
                      $mail_link .= $link."\n\n".$metadata."\n\n";
                      // for mail report
                      $mail_links[] = $link;
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
                      
                      if ($pageTemp != ".folder" && @is_file ($locationTemp.$pageTemp))
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
                            
                            if ($include_metadata == "yes")
                            {
                              $metadata_str .= specialchr_decode ($pageTemp)."\n-------------------\n".$hcms_lang['meta-data'][$user_lang].":\n".getmetadata ($locationTemp, $pageTemp)."\n\n";
                            }
                          }
                        }
                      }
                    }
                  }
                }
              }
            }
            
            // mail body
            $mail_fullbody = $mail_body."\n\n";
            
            if ($include_metadata == "yes")
            {
              $mail_fullbody .= $metadata_str."\n";
            }
            
            if ($mail_link != "")
            {
              $mail_fullbody .= $hcms_lang['please-click-the-links-below-to-access-the-files'][$user_lang].":\n".$mail_link."\n\n";
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
              $mails1 = splitstring ($email_bcc);
              
              if (is_array ($mails))
              {              
                foreach ($mails as $mail)
                {
                  $mailer->AddBCC ($mail);
                }
              }
            }
            
            // subject and body
            $mailer->Subject = html_decode ($mail_title, $hcms_lang_codepage[$lang]);
            $mailer->Body = html_decode (nl2br ($mail_fullbody), $hcms_lang_codepage[$lang]);
            
            // create email recipient array
            $email_to_array = splitstring ($email_to);
            
            if (is_array ($email_to_array))
            {
              foreach ($email_to_array as $email_to_entry)
              {
                $mailer->AddAddress ($email_to_entry);
              }
            }
            
            // send mail
            if ($mailer->Send())
            {
              $mail_success[] = $email_to;
              
              // save message
              savemessage ($message_data, "mail", $user);
              
              $errcode = "00101";
              $error[] = $mgmt_config['today']."|user_sendlink.php|information|$errcode|e-mail message was sent to ".$email_to." by user ".$user;
            }
            else
            {
              $mail_error[] = $email_to;
              
              $errcode = "20101";
              $error[] = $mgmt_config['today']."|user_sendlink.php|error|$errcode|e-mail message could not be sent to ".$email_to." by user ".$user;
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
          $locationTemp = deconvertpath ($locationTemp, "file");
          $pageTemp = getobject ($multiobject_entry);
          $objectpath = convertpath ($siteTemp, $locationTemp.$pageTemp, $catTemp);
          
          rdbms_createrecipient ($objectpath, $user, implode(',', $user_login), $email_to);
          
          // create new task for each user
          if (!empty ($create_task))
          {
            foreach ($user_login as $user_to)
            {
              if (function_exists ("createtask")) createtask ($site, $user, $email_from, $user_to, "", $startdate, $finishdate, "user", $objectpath, $mail_title, $mail_body, false, $priority);
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
             
      rdbms_createrecipient ($objectpath, $user, implode(',', $user_login), $email_to);
      
      // create new task for each user
      if (!empty ($create_task))
      {
        foreach ($user_login as $user_to)
        {
          if (function_exists ("createtask")) createtask ($site, $user, $email_from, $user_to, "", $startdate, $finishdate, "user", $objectpath, $mail_title."\n\n".$mail_body, false, $priority);
        }
      }      
    }
  }
  
  // reset mail address
  $email_to = "";  
}

// check if attachment can be added or files can be downloaded
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
          
          if ($media == "" || $media == false)
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
      
      if ($media == "" || $media == false)
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

// return JSON encoded result if used as service
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