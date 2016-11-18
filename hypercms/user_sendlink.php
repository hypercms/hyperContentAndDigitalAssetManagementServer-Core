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
// management configuration
require ("config.inc.php");
// hyperCMS API
require ("function/hypercms_api.inc.php");
// mailer class
require ("function/hypermailer.class.php");


// input parameters
$site = getrequest_esc ("site", "publicationname");
$cat = getrequest_esc ("cat", "objectname");
$location = getrequest_esc ("location", "locationname");
$folder = getrequest_esc ("folder", "objectname");
$page = getrequest_esc ("page", "objectname");
$pagename = getrequest_esc ("pagename");
$multiobject = getrequest ("multiobject");
// recipients
$intention = getrequest ("intention");
$language = getrequest ("language");
$user_login = getrequest_esc ("user_login");
$group_login = getrequest_esc ("group_login");
$user_group_dummy = getrequest ("user_group_dummy");
$user_group = getrequest_esc ("user_group");
// mail
$email_to = getrequest ("email_to");
$email_cc = getrequest ("email_cc");
$email_bcc = getrequest ("email_bcc");
$mail_title = getrequest_esc ("mail_title");
$mail_body = getrequest_esc ("mail_body");
// download
$download_type = getrequest ("download_type");
$format_img = getrequest ("format_img", "array");
$format_doc = getrequest ("format_doc", "array");
$valid_active = getrequest_esc ("valid_active");
$valid_days = getrequest_esc ("valid_days");
$valid_hours = getrequest_esc ("valid_hours");
$include_metadata = getrequest ("include_metadata");
// task
$create_task = getrequest ("create_task");
$priority = getrequest ("priority");
$startdate = getrequest ("startdate");
$finishdate = getrequest ("finishdate");

$token = getrequest ("token");

// publication management config
if (valid_publicationname ($site)) require ($mgmt_config['abs_path_data']."config/".$site.".conf.php");

// convert location
$location = deconvertpath ($location, "file");
$location_esc = convertpath ($site, $location, $cat);

// ------------------------------ permission section --------------------------------
   
// check access permissions
$ownergroup = accesspermission ($site, $location, $cat);
$setlocalpermission = setlocalpermission ($site, $ownergroup, $cat);  
if ($mgmt_config[$site]['sendmail'] == false || $ownergroup == false || $setlocalpermission['root'] != 1 || $setlocalpermission['sendlink'] != 1 || !valid_publicationname ($site) || !valid_locationname ($location)) killsession ($user);

// check session of user
checkusersession ($user);

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
    $alluseritem_array = selectxmlcontent ($userdata, "<user>", "<publication>", "$site");
    
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

// send mail
if ($intention == "sendmail" && checktoken ($token, $user))
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
        $password = $confirm_password = "P".substr (session_id(), 0, 7);
           
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
          $result = edituser ($site, $login, "", "", "", "", implode(", ", $realnames), $language, "", implode(", ", $email_to), "", $usergroup, "", $user);
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
                        
                        // convert file if format is not original (try image)
                        if ($format_img[0] != "original")
                        {
                          list ($format, $media_config) = explode ("|", $format_img[0]);
                          $mediafile_conv = convertmedia ($siteTemp, $mediadir.$siteTemp."/", $location_conv, $mediafile, $format, $media_config, true);
                        }
                        
                        // convert file if format is not original (try document)
                        if (!$mediafile_conv && $format_doc[0] != "original")
                        {
                          $mediafile_conv = convertmedia ($siteTemp, $mediadir.$siteTemp."/", $location_conv, $mediafile, $format_doc[0], "", true);
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
                          
                          if ($temp_source['result'] && $temp_source['crypted'])
                          {
                            $attachment = $temp_source['templocation'].$temp_source['tempfile'];
                          }
                          elseif ($temp_source['restored'])
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
        }
        else
        {
          $mail_error[] = implode (",", $email_to);
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
              $general_error[] = sprintf ($hcms_lang['e-mail-address-of-user-s-is-missing'][$lang], $realname_to);
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
                            
                            // convert file if format is not original (try image)
                            if ($format_img[0] != "original")
                            {
                              list ($format, $media_config) = explode ("|", $format_img[0]);
                              $mediafile_conv = convertmedia ($siteTemp, $mediadir.$siteTemp."/", $location_conv, $mediafile, $format, $media_config, true);
                            }
                            
                            // convert file if format is not original (try document)
                            if (!$mediafile_conv && $format_doc[0] != "original")
                            {
                              $mediafile_conv = convertmedia ($siteTemp, $mediadir.$siteTemp."/", $location_conv, $mediafile, $format_doc[0], "", true);
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
                              
                              if ($temp_source['result'] && $temp_source['crypted'])
                              {
                                $attachment = $temp_source['templocation'].$temp_source['tempfile'];
                              }
                              elseif ($temp_source['restored'])
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
            }
            else
            {
              $mail_error[] = $email_to;
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
          if ($create_task == true)
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
      if ($create_task == "yes")
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

// security token
$token_new = createtoken ($user);
?>
<!DOCTYPE html>
<html>
  <head>
    <title>hyperCMS</title>
    <meta charset="<?php echo getcodepage ($lang); ?>" />
    <meta name="viewport" content="width=580, initial-scale=0.9, maximum-scale=1.0, user-scalable=1" />
    <link rel="stylesheet" href="<?php echo getthemelocation(); ?>css/main.css" />
    <link rel="stylesheet" href="javascript/jquery-ui/jquery-ui-1.10.2.css">
    <script src="javascript/main.js" type="text/javascript"></script>
    <!-- Jquery and Jquery UI Autocomplete -->
    <script src="javascript/jquery/jquery-1.10.2.min.js" type="text/javascript"></script>
    <script src="javascript/jquery-ui/jquery-ui-1.10.2.min.js" type="text/javascript"></script>
        
    <link rel="STYLESHEET" type="text/css" href="javascript/rich_calendar/rich_calendar.css">
    <script type="text/javascript" src="javascript/rich_calendar/rich_calendar.js"></script>
    <script type="text/javascript" src="javascript/rich_calendar/rc_lang_en.js"></script>
    <script type="text/javascript" src="javascript/rich_calendar/rc_lang_de.js"></script>
    <script src="javascript/rich_calendar/domready.js"></script>
    <script type="text/javascript">
    <!--
    var cal_obj = null; 
    var cal_format = '%Y-%m-%d';
    var cal_field = null;
    
    // show calendar
    function show_cal (el, field_id, format)
    {
      if (cal_obj) return;
      
      cal_field = field_id;
      cal_format = format;
      var datefield = document.getElementById(field_id);
    
    	cal_obj = new RichCalendar();
    	cal_obj.start_week_day = 1;
    	cal_obj.show_time = false;
    	cal_obj.language = '<?php echo getcalendarlang ($lang); ?>';
      cal_obj.user_onchange_handler = cal_on_change;
      cal_obj.user_onclose_handler = cal_on_close;
      cal_obj.user_onautoclose_handler = cal_on_autoclose;
      cal_obj.parse_date(datefield.value, cal_format);
    	cal_obj.show_at_element(datefield, "adj_right-top");
    }
    
    // user defined onchange handler
    function cal_on_change(cal, object_code)
    {
    	if (object_code == 'day')
    	{
    		document.getElementById(cal_field).value = cal.get_formatted_date(cal_format);
    		cal.hide();
    		cal_obj = null;
    	}
    }
    
    // user defined onclose handler (used in pop-up mode - when auto_close is true)
    function cal_on_close(cal)
    {
    	cal.hide();
    	cal_obj = null;
    }
    
    // user defined onautoclose handler
    function cal_on_autoclose(cal)
    {
    	cal_obj = null;
    }
    //-->
    </script>
    
    <script type="text/javascript">
    <!--
    var maxoptions = <?php if (($maxoptions = max (array (sizeof ($mgmt_docoptions), sizeof ($mgmt_imageoptions)))) > 0) echo $maxoptions+1; else "1"; ?>;
    var singleselect = false;
    var folderincluded = <?php if ($allow_attachment) echo "0"; else echo "1"; ?>;
    
    function selectCheckbox (id_prefix, id)
    {
      // select a single checkbox by id
      if (singleselect)
      {
        // uncheck
        for (var i=1; i<=maxoptions; i++)
        {
          if (document.getElementById(id_prefix + i)) document.getElementById(id_prefix + i).checked = false;
        }
        
        // check
        document.getElementById(id).checked = true;
      }
      // select all checkboxes
      else if (id == "all")
      {
        for (var i=0; i<=maxoptions; i++)
        {
          // check
          if (document.getElementById(id_prefix + i)) document.getElementById(id_prefix + i).checked = true;
        }
      }
    }
    
    function selectLinkType(id)
    {
      // uncheck
      if (document.getElementById('type_download')) document.getElementById('type_download').checked = false;
      if (document.getElementById('type_access')) document.getElementById('type_access').checked = false;
      if (document.getElementById('type_attachment')) document.getElementById('type_attachment').checked = false;
      
      // check
      if (document.getElementById(id)) document.getElementById(id).checked = true;
    }
    
    function initLinkType(id)
    {
      // download link -> single select
      if (document.getElementById('type_download') && document.getElementById('type_download').checked == true)
      {
        singleselect = true;
        selectCheckbox('format_img', 'format_img1');
        selectCheckbox('format_doc', 'format_doc1');
        
        if (folderincluded)
        {
          // disable checkboxes except original
          for (var i=2; i<=maxoptions; i++)
          {
            if (document.getElementById('format_img' + i)) document.getElementById('format_img' + i).disabled = true;
            if (document.getElementById('format_doc' + i)) document.getElementById('format_doc' + i).disabled = true;
          }
        }
        
        if (document.getElementById('valid_active'))
        {
          document.getElementById('valid_active').disabled = false;
          
          if (document.getElementById('valid_active').checked == true)
          {
            document.getElementById('valid_days').disabled = false;
            document.getElementById('valid_hours').disabled = false;
          }
        }
      }
      // access link -> multi select
      else if (document.getElementById('type_access') && document.getElementById('type_access').checked == true)
      {
        singleselect = false;
        selectCheckbox('format_img', 'all');
        selectCheckbox('format_doc', 'all');
        
        // enable all checkboxes
        for (var i=1; i<=maxoptions; i++)
        {
          if (document.getElementById('format_img' + i)) document.getElementById('format_img' + i).disabled = false;
          if (document.getElementById('format_doc' + i)) document.getElementById('format_doc' + i).disabled = false;
        }
        
        if (document.getElementById('valid_active'))
        {
          document.getElementById('valid_active').disabled = false;
          
          if (document.getElementById('valid_active').checked == true)
          {
            document.getElementById('valid_days').disabled = false;
            document.getElementById('valid_hours').disabled = false;
          }
        }
      }
      // attachment -> single select
      else if (document.getElementById('type_attachment') && document.getElementById('type_attachment').checked == true)
      {
        singleselect = true;
        selectCheckbox('format_img', 'format_img1');
        selectCheckbox('format_doc', 'format_doc1');
        
        // enable all checkboxes
        for (var i=1; i<=maxoptions; i++)
        {
          if (document.getElementById('format_img' + i)) document.getElementById('format_img' + i).disabled = false;
          if (document.getElementById('format_doc' + i)) document.getElementById('format_doc' + i).disabled = false;
        }
        
        if (document.getElementById('valid_active'))
        {
          document.getElementById('valid_active').checked = false;
          document.getElementById('valid_active').disabled = true;
          document.getElementById('valid_days').disabled = true;
          document.getElementById('valid_hours').disabled = true;
        }
      }
    }
    
    function isIntegerValue(value)
    {
      if (value != "") return value % 1 == 0;
      else return true;
    }

    function checkForm()
    {  
      if ($("div#emails div").length < 1 && $("#group_login").val() == "")
      {
        alert (hcms_entity_decode("<?php echo getescapedtext ($hcms_lang['add-at-least-one-user-or-email'][$lang]); ?>"));
        $('input#selector').focus();
        return false;
      }
      
      if (document.getElementById("mail_title").value == "")
      {
        alert (hcms_entity_decode("<?php echo getescapedtext ($hcms_lang['please-define-a-mail-subject'][$lang]); ?>"));
        $("input#mail_title").focus();
        return false;
      }
      
      if (document.getElementById("valid_active"))
      {
        if (document.getElementById("valid_active").checked == true)
        {
          var valid_days = document.getElementById("valid_days").value;
          var valid_hours = document.getElementById("valid_hours").value;
          
          if (isIntegerValue(valid_days) == false || isIntegerValue(valid_hours) == false)
          {
            alert (hcms_entity_decode("<?php echo getescapedtext ($hcms_lang['period-of-validity-is-not-correct'][$lang]); ?>"));
            document.getElementById("valid_days").focus();
            return false;
          }
        }
      }       
      
      return true;
    }

    function remove_element(elname)
    {
      $('#'+elname).remove();
      
      if (!$('[id^="email_to_"]').length)
      {
        showHideLayers("attention_settings", 'invisible');
      }
    }

    // Hides or shows different elements on the page can have unlimited arguments which should be of the following order
    // Elementname, ("show", "hide", "visible", "invisible")
    // Example: showHideLayers('element1', 'show', 'element2', 'hide', 'element3', 'invisible', 'element4', 'visible')
    
    function showHideLayers()
    { 
      var i, show, args=showHideLayers.arguments;
      
      for (i=0; i<(args.length-1); i+=2)
      {
        var elem = $("#"+args[i]);
        if (elem)
        { 
          show = args[i+1];
          
          if (show == 'show') elem.show();
          else if (show == 'hide') elem.hide();
          else if (show == 'visible') elem.css({visibility: "visible"});
          else if (show == 'invisible') elem.css({visibility: "hidden"});
        }
      }
    }

    function close_selector()
    {
      $("input#selector").autocomplete( "close" );
    }
    
    $(document).ready(function()
    {
      initLinkType();
      
      <?php 
      $tmpuser = array();
      
      if (is_array ($alluser_array))
      {
        foreach ($alluser_array as $user_id => $user_login)
        {
          if (array_key_exists ($user_id, $allemail_array) && !empty ($allemail_array[$user_id]))
          {
            $username = (array_key_exists ($user_id, $allrealname_array) && !empty ($allrealname_array[$user_id])) ? $allrealname_array[$user_id] : $user_login;
            $tmpuser[] = "{ loginname: \"{$user_login}\", id: \"{$user_id}\", username:\"{$username}\", email:\"{$allemail_array[$user_id]}\", label: \"{$username} ({$allemail_array[$user_id]})\" }"; 
          }
        }
      }
      ?>
      var userlist = [<?php echo implode (",\n", $tmpuser); ?>];
      <?php
      unset ($tmpuser);
      // id for the special element
      $idspecial = "-99999999";
      ?>

      var noneFound = { id: "<?php echo $idspecial; ?>", label: hcms_entity_decode("<?php echo getescapedtext ($hcms_lang['add-as-recipient'][$lang]); ?>") };
      
      $("input#selector").autocomplete(
        { 
          source: function(request, response) {

            var found = $.ui.autocomplete.filter(userlist, request.term);

            if(found.length) {
              response(found);
            } else {
              response([noneFound]);
            }
          },
          select: function(event, ui)
          {
            var inputval = $(this).val();
            var fieldname = inputval.replace(/([\.\-\@])/g, "_");
            
            if (ui.item.id == "<?php echo $idspecial; ?>")
            {								
              var mainname = 'main_'+fieldname;
              var delname = 'delete_'+fieldname;
              var inputid = 'email_to_'+fieldname;
              var divtextid = 'divtext_'+fieldname;
              
              // We only add persons who aren't on the list already
              if (!$('#'+mainname).length)
              {
                // Check if e-mail address is valid
                var emailReg = /^([\w-\.]+@([\w-]+\.)+[\w-]{2,4})?$/;
                
                if (emailReg.test(inputval))
                {
                  var pre = "";
                  var img = '<div><img onclick="remove_element(\''+mainname+'\')" onmouseout="hcms_swapImgRestore();" onmouseover="hcms_swapImage(\''+delname+'\', \'\', \'<?php echo getthemelocation(); ?>img/button_close_over.gif\',1);" title="<?php echo getescapedtext ($hcms_lang['delete-recipient'][$lang]); ?>" alt="<?php echo getescapedtext ($hcms_lang['delete-recipient'][$lang]); ?>" src="<?php echo getthemelocation(); ?>img/button_close.gif" name="'+delname+'" style="width:16px; height:16px; border:0; float:right; display:inline; cursor:pointer;"></div>';
                  var input = '<input type="hidden" name="email_to[]" id="'+inputid+'" value="'+inputval+'"/>';
                  var divtext =  '<div id="'+divtextid+'"style="float:left">'+inputval+'&nbsp;</div>';
                  $("div#emails").append("<div id=\""+mainname+"\" style=\"width:355px; height:16px;\">"+input+divtext+img+"</br></div>");
                  showHideLayers("attention_settings", 'visible');
                  $(this).val("");
                }
                else
                {
                  alert (hcms_entity_decode("<?php echo getescapedtext ($hcms_lang['please-insert-a-valid-e-mail-adress'][$lang]); ?>"));
                }
              } 
              else
              {
                alert (hcms_entity_decode("<?php echo getescapedtext ($hcms_lang['recipient-already-added'][$lang]); ?>"));
                $(this).val("");
              }
            }
            else
            {
              var mainname = 'main_'+ui.item.loginname;
              var delname = 'delete_'+ui.item.loginname;
              var inputid = 'user_login_'+ui.item.loginname;
              var divtextid = 'divtext_'+ui.item.loginname;
              
              // only add persons who aren't on the list already
              if (!$('#'+mainname).length)
              {
                var pre = "";
                var img = '<div><img onclick="remove_element(\''+mainname+'\')" onmouseout="hcms_swapImgRestore();" onmouseover="hcms_swapImage(\''+delname+'\', \'\', \'<?php echo getthemelocation(); ?>img/button_close_over.gif\',1);" title="<?php echo getescapedtext ($hcms_lang['delete-recipient'][$lang]); ?>" alt="<?php echo getescapedtext ($hcms_lang['delete-recipient'][$lang]); ?>" src="<?php echo getthemelocation(); ?>img/button_close.gif" name="'+delname+'" style="width:16px; height:16px; border:0; float:right; display:inline; cursor:pointer;"></div>';
                var input = '<input type="hidden" name="user_login[]" id="'+inputid+'" value="'+ui.item.loginname+'"/>';
                var divtext =  '<div id="'+divtextid+'" style="float:left" title="'+ui.item.email+'">'+ui.item.username+'&nbsp;</div>';
                $("div#emails").append("<div id=\""+mainname+"\" style=\"width:355px; height:16px;\">"+input+divtext+img+"</br></div>");
              } 
              else
              {
                alert (hcms_entity_decode("<?php echo getescapedtext ($hcms_lang['recipient-already-added'][$lang]); ?>"));
              }
              $(this).val("");
            }
            // Returning false suppresses that the inputfield is updated with the selected value
            return false;
          },	
          minLength: 0,
          appendTo: '#selectbox',
          autoFocus: true
        }
        )
        // as soon as there is focus autocomplete window will be opened
        /*.focus(function()
      {
        $(this).autocomplete( "search" , this.value);
      })*/
      // only open autocomplete when it's not already shown
      .click(function()
      {
        var elem = $(this);
        
        if(elem.autocomplete( "widget").is(":hidden"))
        {
          elem.autocomplete( "search" , elem.value);
        }
      })
      ;
      // call click function for the first tap
      $("#menu-Recipient").click();
      
      $("#mailForm").keypress(function (key) 
      {
        if(key.keyCode === 13 &&  key.target.id != 'mail_body') return false;
        else return true;
      }
      );
    });    
    //-->
    </script>
  </head>
  
  <body class="hcmsWorkplaceGeneric" style="overflow:auto" onLoad="<?php echo $add_onload; ?>">
  
    <!-- top bar -->
    <?php
    if (isset ($multiobject_array) && is_array ($multiobject_array)) $title = sizeof ($multiobject_array).$hcms_lang['-files-selected'][$lang];
    else $title = $pagename;
                
    echo showtopbar ($hcms_lang['selected-object'][$lang].": ".$title, $lang);
    ?>
  
    <form id="mailForm" name="mailForm" action="" method="post">
      <input type="hidden" name="site" value="<?php echo $site; ?>" />
      <input type="hidden" name="cat" value="<?php echo $cat; ?>" />
      <input type="hidden" name="location" value="<?php echo $location_esc; ?>" />  
      <input type="hidden" name="folder" value="<?php echo $folder; ?>" /> 
      <input type="hidden" name="page" value="<?php echo $page; ?>" />
      <input type="hidden" name="pagename" value="<?php echo $pagename; ?>" />
      <input type="hidden" name="multiobject" value="<?php echo $multiobject; ?>" />
      <input type="hidden" name="token" value="<?php echo $token_new; ?>" />
      <input type="hidden" name="intention" value="sendmail" />
     
      <?php
      if (!empty ($mail_error) || !empty ($mail_success) || !empty ($general_error))
      {
        $show = "<div style=\"width:100%; max-height:190px; z-index:10; overflow:auto;\">\n";
              
        // success message
        if (!empty ($mail_success) && is_array ($mail_success))
        {
          $show .= "<strong>".getescapedtext ($hcms_lang['e-mail-was-sent-successfully-to-'][$lang])."</strong><br />\n".implode (", ", html_encode ($mail_success))."<br />\n";
        }
              
        // mail error message
        if (!empty ($mail_error) && is_array ($mail_error))
        {
          $show .= "<strong>".getescapedtext ($hcms_lang['there-was-an-error-sending-the-e-mail-to-'][$lang])."</strong><br />\n".implode ("<br />", html_encode ($mail_error))."<br />\n";
        }
              
        // general error message
        if (!empty ($general_error) && is_array ($general_error))
        {
          $show .= implode ("<br />", $general_error)."<br />\n";
        }
              
        // links
        if (($download_type == 'download') && is_array ($mail_links))
        {
          foreach ($mail_links as $link)
          {
            $show .= "<br />Link: <input type=\"text\" value=\"".$link."\" style=\"width:475px;\" />\n";
          }
        }
  
        $show .= "</div>";
              
        echo showmessage ($show, 540, 200, $lang, "position:fixed; left:5px; top:55px;");
      }
      ?>
      <br />
      <div id="LayerMenu" class="hcmsTabContainer" style="position:absolute; z-index:1; visibility:visible; left:0px; top:35px">
        <table border="0" cellspacing="0" cellpadding="0">
          <tr align="left" valign="top">
            <td style="width:3px;"><img src="<?php echo getthemelocation(); ?>img/backgrd_tabs_spacer.gif" style="width:3px; height:19px; border:0;" /></td>
            <td align="left" valign="top" class="hcmsTab">
              &nbsp;<a id="menu-Recipient" href="#" onClick="showHideLayers('LayerRecipient','show','line_Recipient','visible','LayerGroup','hide','line_Group','invisible','LayerSettings','hide','line_Settings','invisible','LayerFormats','hide','line_Formats','invisible'); close_selector();" title="<?php echo getescapedtext ($hcms_lang['recipients'][$lang]); ?>"><?php echo getescapedtext ($hcms_lang['recipients'][$lang]); ?></a>
            </td>
            <td style="width:3px;"><img src="<?php echo getthemelocation(); ?>img/backgrd_tabs_spacer.gif" style="width:3px; height:19px; border:0;" /></td>
            <td align="left" valign="top" class="hcmsTab">
              &nbsp;<a id="menu-Group" href="#" onClick="showHideLayers('LayerRecipient','hide','line_Recipient','invisible','LayerGroup','show','line_Group','visible','LayerSettings','hide','line_Settings','invisible','LayerFormats','hide','line_Formats','invisible'); close_selector();" title="<?php echo getescapedtext ($hcms_lang['user-group'][$lang]); ?>"><?php echo getescapedtext ($hcms_lang['user-group'][$lang]); ?></a>
            </td>
            <td style="width:3px;"><img src="<?php echo getthemelocation(); ?>img/backgrd_tabs_spacer.gif" style="width:3px; height:19px; border:0;" /></td>
            <td>
            <td align="left" valign="top" class="hcmsTab">
              &nbsp;<a id="menu-Settings" href="#" onClick="showHideLayers('LayerRecipient','hide','line_Recipient','invisible','LayerGroup','hide','line_Group','invisible','LayerSettings','show','line_Settings','visible','LayerFormats','hide','line_Formats','invisible'); close_selector();" title="<?php echo getescapedtext ($hcms_lang['settings'][$lang]); ?>"><?php echo getescapedtext ($hcms_lang['settings'][$lang]); ?><span id="attention_settings" style="color:red; visibility:hidden;">!</span></a>
            </td>
            <td style="width:3px;"><img src="<?php echo getthemelocation(); ?>img/backgrd_tabs_spacer.gif" style="width:3px; height:19px; border:0;" /></td>
            <td align="left" valign="top" class="hcmsTab">
              &nbsp;<a id="menu-Formats" href="#" onClick="showHideLayers('LayerRecipient','hide','line_Recipient','invisible','LayerGroup','hide','line_Group','invisible','LayerSettings','hide','line_Settings','invisible','LayerFormats','show','line_Formats','visible'); close_selector();" title="<?php echo getescapedtext ($hcms_lang['formats'][$lang]); ?>"><?php echo getescapedtext ($hcms_lang['formats'][$lang]); ?></a>
            </td>
          </tr>
        </table>
      </div>
      
      <div id="line_Recipient" class="hcmsWorkplaceGeneric" style="position:absolute; width:118px; height:2px; z-index:2; left:4px; top:57px; visibility:visible"> </div>
      <div id="line_Group" class="hcmsWorkplaceGeneric" style="position:absolute; width:118px; height:2px; z-index:2; left:127px; top:57px; visibility:hidden"> </div>
      <div id="line_Settings" class="hcmsWorkplaceGeneric" style="position:absolute; width:118px; height:2px; z-index:2; left:250px; top:57px; visibility:hidden"> </div>
      <div id="line_Formats" class="hcmsWorkplaceGeneric" style="position:absolute; width:118px; height:2px; z-index:2; left:373px; top:57px; visibility:hidden"> </div>
      
      <!-- Tabs for recipients, groups and settings --> 
      <div id="Tabs" style="width:100%; height:120px; margin:10px 0px;">
        
        <div id="LayerRecipient">
          <table width="100%" border="0" cellspacing="0" cellpadding="2">
            <tr>
              <td width="180" align="left" valign="top" nowrap="nowrap"><?php echo getescapedtext ($hcms_lang['send-e-mail-to'][$lang]); ?>:</td>
              <td id="selectbox" align="left" valign="top">
                <input type="text" value="" style="width:350px;" name="selector" id="selector" />
              </td>
            <tr>
              <td align="left" valign="top" nowrap="nowrap">
                <?php echo getescapedtext ($hcms_lang['recipients'][$lang]); ?>:
              </td>
              <td align="left" valign="top">
                <div style="overflow:auto; max-height:120px;" id="emails">
                </div>
              </td>
            </tr>
          </table>
        </div>
        
        <div id="LayerGroup">
          <table width="100%" border="0" cellspacing="0" cellpadding="2">
            <tr>
              <td width="180" align="left" valign="top" nowrap="nowrap">
                <?php echo getescapedtext ($hcms_lang['attention'][$lang]); ?>:
              </td>
              <td align="left" valign="top">
                <?php echo getescapedtext ($hcms_lang['the-message-will-be-sent-to-all-members-of-the-selected-group'][$lang]); ?>
              </td>
            </tr>
            <tr>
              <td width="180" align="left" valign="top" nowrap="nowrap">
                <?php echo getescapedtext ($hcms_lang['user-group'][$lang]); ?>:
              </td>
              <td align="left" valign="top">
                <select name="group_login" id="group_login" style="width:350px;">
                  <option value="" selected="selected">--- <?php echo getescapedtext ($hcms_lang['select'][$lang]); ?> ---</option>
                  <?php 
                  if ($allgroup_array != false && sizeof ($allgroup_array) > 0)
                  {
                    natcasesort($allgroup_array);
                    reset($allgroup_array);
                    
                    foreach ($allgroup_array as $allgroup)
                    {
                      echo "<option value=\"".$allgroup."\">".$allgroup."</option>\n";
                    }
                  }
                  ?>
                </select>
              </td>
            </tr>
          </table>
        </div>
        
        <div id="LayerSettings">
          <table width="100%" border="0" cellspacing="0" cellpadding="2">
            <tr>
              <td width="180" align="left" valign="top" nowrap="nowrap">
                <?php echo getescapedtext ($hcms_lang['attention'][$lang]); ?>:
              </td>
              <td align="left" valign="top">
                <?php echo getescapedtext ($hcms_lang['these-are-the-settings-which-will-only-be-assigned-to-new-users'][$lang]); ?>
              </td>
            </tr>
            <tr>
              <td align="left" valign="top" nowrap="nowrap"><?php echo getescapedtext ($hcms_lang['language-setting'][$lang]); ?>: </td>
              <td align="left" valign="top">
                <select name="language" style="width:350px;">
                <?php
                foreach ($hcms_lang_shortcut as $lang_opt)
                {
                  if ($language == $lang_opt)
                  {
                    echo "<option value=\"".$lang_opt."\" selected=\"selected\">".$hcms_lang_name[$lang_opt]."</option>\n";
                  }
                  else
                  {
                    echo "<option value=\"".$lang_opt."\">".$hcms_lang_name[$lang_opt]."</option>\n";
                  }
                }
                ?>
                </select>            
              </td>
            </tr>
            <tr>
              <td align="left" valign="top" nowrap="nowrap"><?php echo getescapedtext ($hcms_lang['member-of-user-group'][$lang]); ?>: </td>
              <td align="left" valign="top">
              <?php
                if ($allgroup_array != false && sizeof ($allgroup_array) > 0)
                {
                  $default = "";
                  
                  foreach ($allgroup_array as $allgroup)
                  {
                    if (strtolower ($allgroup) == "default")
                    {
                      $default = $allgroup;
                    }
                  }
                  // if a default group is given
                  if ($default != "")
                  {
                  ?>
                  <input type="text" name="user_group_dummy" value="<?php echo $default; ?>" class="hcmsWorkplaceGeneric" style="width:350px;" disabled="disabled" />
                  <input type="hidden" name="user_group" value="<?php echo $default; ?>" />
                  <?php 
                  }
                  else
                  { // otherwise a group can be selected
                  ?>
                  <select name="user_group" style="width:350px;">
                    <?php 
                    if ($allgroup_array != false && sizeof ($allgroup_array) > 0)
                    {
                      natcasesort ($allgroup_array);
                      reset ($allgroup_array);
                        
                      foreach ($allgroup_array as $allgroup)
                      { 
                        if ($allgroup == $user_group) $selected = "selected=\"selected\"";
                        else $selected = "";
                        ?>
                        <option value="<?php echo $allgroup; ?>" <?php echo $selected; ?>><?php echo $allgroup; ?></option>
                        <?php               
                      }
                    }
                    ?>
                    </select>
                    <?php 
                  }
                }
                ?>
              </td>
            </tr>
          </table>
        </div>
          
      </div>
      
      <!-- Download formats -->      
      <div id="LayerFormats" class="hcmsWorkplaceGeneric" style="position:absolute; z-index:3; visibility:visible; left:0px; top:60px; width:100%; height:340px;">
        <span class="hcmsHeadline"><?php echo getescapedtext ($hcms_lang['download-formats'][$lang]); ?><span>
        <table border="0" cellspacing="0" cellpadding="5">
          <tr>
            <td align="left" valign="top" nowrap="nowrap">
              <p><?php echo getescapedtext ($hcms_lang['image'][$lang]); ?></p>
              <?php 
              if (is_array ($mgmt_imageoptions) && sizeof ($mgmt_imageoptions) > 0)
              {
                $i = 1;
                
                echo "<input id=\"format_img".$i."\" name=\"format_img[]\" onclick=\"selectCheckbox('format_img', this.id)\" type=\"checkbox\" value=\"original\"> ".getescapedtext ($hcms_lang['original'][$lang])."<br />\n";
                $i++;
                
                foreach ($mgmt_imageoptions as $ext => $imageconfig_array)
                {
                  if (is_array ($imageconfig_array))
                  {
                    $ext_array = explode (".", trim ($ext, "."));
                    $image_type = $ext_array[0];
                    
                    foreach ($imageconfig_array as $image_config => $value)
                    {
                      if ($image_config != "original" && $image_config != "thumbnail")
                      {
                        $file_info = getfileinfo ($site, "file".$ext, "comp");
                        echo "<input id=\"format_img".$i."\" name=\"format_img[]\" onclick=\"selectCheckbox('format_img', this.id)\" type=\"checkbox\" value=\"".$image_type."|".$image_config."\"> <img src=\"".getthemelocation()."img/".$file_info['icon']."\" align=\"absmiddle\" /> ".strtoupper($image_type)." ".$file_info['type']." ".$image_config."<br />\n";
                        $i++;
                      }
                    }
                  }
                }
              }
              ?>
            </td>
            <td width="20">&nbsp;</td>
            <td align="left" valign="top">
              <p><?php echo getescapedtext ($hcms_lang['document'][$lang]); ?></p>
              <?php 
              if (is_array ($mgmt_docoptions) && sizeof ($mgmt_docoptions) > 0)
              {
                $i = 1;
                                 
                echo "<input id=\"format_doc".$i."\" name=\"format_doc[]\" onclick=\"selectCheckbox('format_doc', this.id)\" type=\"checkbox\" value=\"original\"> ".getescapedtext ($hcms_lang['original'][$lang])."<br />\n";
                $i++;
                
                foreach ($mgmt_docoptions as $ext => $value)
                {
                  if ($ext != "")
                  {
                    $ext_array = explode (".", trim ($ext, "."));
                    $doc_type = $ext_array[0];
                      
                    $file_info = getfileinfo ($site, "file".$ext, "comp");
                    echo "<input id=\"format_doc".$i."\" name=\"format_doc[]\" onclick=\"selectCheckbox('format_doc', this.id)\" type=\"checkbox\" value=\"".$doc_type."\"> <img src=\"".getthemelocation()."img/".$file_info['icon']."\" align=\"absmiddle\" /> ".$file_info['type']." (".strtoupper($doc_type).")<br />\n";
                    $i++;
                  }
                }
              }
              ?>
            </td>
          </tr>
        </table>
      </div>
      
      <!-- Mail Message -->      
      <div id="LayerMail" style="width:100%; height:270px;">
        <table width="100%" border="0" cellpadding="2" cellspacing="0">
          <tr>
            <td colspan="2" height="3" valign="bottom">
              <hr />
            </td>
          </tr>
          <!-- CC, BCC -->
          <tr>
            <td width="180" align="left" valign="top" nowrap="nowrap"><?php echo getescapedtext ($hcms_lang['cc-e-mail'][$lang]); ?>: </td>
            <td align="left" valign="top">
              <input type="text" name="email_cc" style="width:350px;" value="<?php echo $email_cc; ?>" />
            </td>
          </tr>
          <tr>
            <td align="left" valign="top" nowrap="nowrap"><?php echo getescapedtext ($hcms_lang['bcc-e-mail'][$lang]); ?>: </td>
            <td align="left" valign="top">
              <input type="text" name="email_bcc" style="width:350px;" value="<?php echo $email_bcc; ?>" />
            </td>
          </tr>
          <tr>
            <td colspan="2" height="3" valign="bottom">
              <hr />
            </td>
          </tr>
          <!-- TITLE and MESSAGE -->
          <tr>
            <td align="left" valign="top" nowrap="nowrap"><?php echo getescapedtext ($hcms_lang['subject'][$lang]); ?>:</td>
            <td align="left" valign="top">
              <input type="text" id="mail_title" name="mail_title" style="width:350px;" value="<?php echo $mail_title; ?>" />
            </td>
          </tr>
          <tr>
            <td align="left" valign="top" nowrap="nowrap"><?php echo getescapedtext ($hcms_lang['message'][$lang]); ?>:</td>
            <td align="left" valign="top">
              <textarea id="mail_body" name="mail_body" rows="6" style="width:350px;"><?php
                                      
              // define message if object will be deleted automatically
              if ($location_esc != "" && $folder != "") $objectpath = $location_esc.$folder."/.folder";
              elseif ($location_esc != "" && $page != "") $objectpath = $location_esc.$page;

              $queue = rdbms_getqueueentries ("delete", "", "", "", $objectpath);

              if (is_array ($queue) && !empty ($queue[0]['date']))
              {
                $message = str_replace ("%date%", substr ($queue[0]['date'], 0, -3), $hcms_lang['the-link-will-be-active-till-date'][$lang]);
              
                if (substr_count ($mail_body, $message) == 0)
                {                
                  $mail_body .= $message."\n";
                }
              }
              
              echo $mail_body;
              
              ?></textarea>
            </td>
          </tr>
          <!-- SEND FILES AS ATTACHMENT OR AS LINK -->
          <tr>
            <td colspan="2" height="3" valign="bottom">
              <hr />
            </td>
          </tr>
        <?php if ($page != "" || is_array ($multiobject_array)) { ?>
          <tr>
            <td align="left" valign="top" nowrap="nowrap"><?php echo getescapedtext ($hcms_lang['send-files-as'][$lang]); ?>:</td>
            <td align="left" valign="top">
              <table border="0" cellpadding="0" cellspacing="0">
                <?php if ($allow_download) { ?>
                <tr><td nowrap="nowrap"><label><input type="checkbox" name="download_type" id="type_download" onclick="selectLinkType(this.id); initLinkType();" value="download" <?php if ($mgmt_config['maillink'] == "download" || $mgmt_config['maillink'] == "") echo "checked=\"checked\""; ?> />&nbsp;<?php echo getescapedtext ($hcms_lang['download-link'][$lang]); ?></label>&nbsp;</td><td nowrap="nowrap"><div class="hcmsButtonTiny" onClick="$('#menu-Formats').click();">&nbsp; &#9654; <?php echo getescapedtext ($hcms_lang['download-formats'][$lang]); ?>&nbsp;</div></td></tr>
                <?php } ?>
                <tr><td nowrap="nowrap"><label><input type="checkbox" name="download_type" id="type_access" onclick="selectLinkType(this.id); initLinkType();" value="link" <?php if ($mgmt_config['maillink'] == "access") echo "checked=\"checked\""; ?> />&nbsp;<?php echo getescapedtext ($hcms_lang['access-link'][$lang]); ?></label>&nbsp;</td><td nowrap="nowrap"><div class="hcmsButtonTiny" onClick="$('#menu-Formats').click();">&nbsp; &#9654; <?php echo getescapedtext ($hcms_lang['download-formats'][$lang]); ?>&nbsp;</div></td></tr>
                <?php if ($allow_attachment) { ?>
                <tr><td nowrap="nowrap"><label><input type="checkbox" name="download_type" id="type_attachment" onclick="selectLinkType(this.id); initLinkType();" value="attachment" />&nbsp;<?php echo getescapedtext ($hcms_lang['attachment'][$lang]); ?></label>&nbsp;</td><td nowrap="nowrap"><div class="hcmsButtonTiny" onClick="$('#menu-Formats').click();">&nbsp; &#9654; <?php echo getescapedtext ($hcms_lang['download-formats'][$lang]); ?>&nbsp;</div></td></tr>
                <?php } ?>
              </table>
            </td>
          </tr>
          <tr>
            <td align="left" valign="top" nowrap="nowrap"><?php echo getescapedtext ($hcms_lang['period-of-validity'][$lang]); ?>:</td>
            <td align="left" valign="top">
              <label><input type="checkbox" name="valid_active" id="valid_active" value="yes" onclick="if (this.checked==true) { document.getElementById('valid_days').disabled=false; document.getElementById('valid_hours').disabled=false; } else { document.getElementById('valid_days').disabled=true; document.getElementById('valid_hours').disabled=true; }" />&nbsp;<?php echo getescapedtext ($hcms_lang['valid-for'][$lang]); ?></label>
              <input type="text" name="valid_days" id="valid_days" value="" style="width:40px;" disabled="disabled" />&nbsp;<?php echo getescapedtext ($hcms_lang['days-and'][$lang]); ?>&nbsp;
              <input type="text" name="valid_hours" id="valid_hours" value="" style="width:40px;" disabled="disabled" />&nbsp;<?php echo getescapedtext ($hcms_lang['hours'][$lang]); ?>
            </td>
          </tr>
          <tr>
            <td align="left" valign="top" nowrap="nowrap"><?php echo getescapedtext ($hcms_lang['meta-data'][$lang]); ?>:</td>
            <td align="left" valign="top">
              <label><input type="checkbox" name="include_metadata" value="yes" <?php if ($include_metadata == "yes") echo "checked=\"checked\""; ?>/> 
              <?php echo getescapedtext ($hcms_lang['include-in-message'][$lang]); ?></label>
            </td>
          </tr>
          <?php if (checkrootpermission ('desktoptaskmgmt') && is_file ($mgmt_config['abs_path_cms']."task/task_list.php")) { ?>
          <tr>
            <td align="left" valign="top" nowrap="nowrap"><?php echo getescapedtext ($hcms_lang['create-new-task'][$lang]); ?>:</td>
            <td align="left" valign="top">
              <label><input type="checkbox" name="create_task" value="yes" onclick="selectLinkType('type_access'); initLinkType();" <?php if ($create_task == "yes") echo "checked=\"checked\""; ?>/> 
              <?php echo getescapedtext ($hcms_lang['for-the-recipients-with-priority'][$lang]); ?></label>
              <select name="priority">
                <option value="low" selected="selected"><?php echo getescapedtext ($hcms_lang['low'][$lang]); ?></option>
                <option value="medium"><?php echo getescapedtext ($hcms_lang['medium'][$lang]); ?></option>
                <option value="high"><?php echo getescapedtext ($hcms_lang['high'][$lang]); ?></option>
              </select>
              <div style="margin:2px 0px 2px 0px;">
                <?php echo getescapedtext ($hcms_lang['start'][$lang]); ?>
                <input type="text" name="startdate" id="startdate" value="" readonly="readonly" style="width:80px;" />&nbsp;<img name="datepicker1" src="<?php echo getthemelocation(); ?>img/button_datepicker.gif" onclick="show_cal(this, 'startdate', '%Y-%m-%d');" alt="<?php echo getescapedtext ($hcms_lang['select-date'][$lang]); ?>" title="<?php echo getescapedtext ($hcms_lang['select-date'][$lang]); ?>" align="top" />
                <?php echo getescapedtext ($hcms_lang['end'][$lang]); ?>
                <input type="text" name="finishdate" id="finishdate" value="" readonly="readonly" style="width:80px;" />&nbsp;<img name="datepicker2" src="<?php echo getthemelocation(); ?>img/button_datepicker.gif" onclick="show_cal(this, 'finishdate', '%Y-%m-%d');" alt="<?php echo getescapedtext ($hcms_lang['select-date'][$lang]); ?>" title="<?php echo getescapedtext ($hcms_lang['select-date'][$lang]); ?>" align="top" />
              </div>
            </td>
          </tr>
          <?php } ?>        
        <?php } ?>
          <tr>
            <td align="left" valign="top" nowrap="nowrap"><?php echo getescapedtext ($hcms_lang['send-e-mail'][$lang]); ?>: </td>
            <td align="left" valign="top">
              <img name="ButtonSubmit" src="<?php echo getthemelocation(); ?>img/button_OK.gif" onClick="if (checkForm()) document.forms['mailForm'].submit();" onMouseOver="hcms_swapImage('ButtonSubmit','','<?php echo getthemelocation(); ?>img/button_OK_over.gif',1)" onMouseOut="hcms_swapImgRestore()" style="border:0; cursor:pointer;" align="absmiddle" title="OK" alt="OK" />
            </td>
          </tr>
        </table>
      </div>
      
    </form>
    
  </body>
</html>