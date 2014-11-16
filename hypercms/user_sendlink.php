<?php
/*
 * This file is part of
 * hyper Content Management Server - http://www.hypercms.com
 * Copyright (c) by hyper CMS Content Management Solutions GmbH
 *
 * You should have received a copy of the License along with hyperCMS.
 */

// session parameters
require ("include/session.inc.php");
// management configuration
require ("config.inc.php");
// hyperCMS API
require ("function/hypercms_api.inc.php");
// hyperCMS UI
require ("function/hypercms_ui.inc.php");
// mailer class
require ("function/hypermailer.class.php");
// language file
require_once ("language/user_sendlink.inc.php");


// input parameters
$site = getrequest_esc ("site", "publicationname");
$cat = getrequest_esc ("cat", "objectname");
$location = getrequest_esc ("location", "locationname");
$folder = getrequest_esc ("folder", "objectname");
$page = getrequest_esc ("page", "objectname");
$pagename = getrequest_esc ("pagename");
$multiobject = getrequest ("multiobject");
$intention = getrequest ("intention");
$language = getrequest ("language");
$user_login = getrequest_esc ("user_login");
$group_login = getrequest_esc ("group_login");
$user_group_dummy = getrequest ("user_group_dummy");
$user_group = getrequest_esc ("user_group");
$email_to = getrequest ("email_to");
$email_cc = getrequest ("email_cc");
$email_bcc = getrequest ("email_bcc");
$mail_title = getrequest_esc ("mail_title");
$mail_body = getrequest_esc ("mail_body");
$attachment_type = getrequest ("attachment_type");
$valid_active = getrequest_esc ("valid_active");
$valid_days = getrequest_esc ("valid_days");
$valid_hours = getrequest_esc ("valid_hours");
$include_metadata = getrequest ("include_metadata");
$create_task = getrequest ("create_task");
$priority = getrequest ("priority");
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
	if ($groupdata == "" && valid_publicationname ($site)) $groupdata = loadfile ($mgmt_config['abs_path_data']."user/", $site.".usergroup.xml.php");  
	
	if ($groupdata != false) $allgroup_array = getcontent ($groupdata, "<groupname>");  
	
	// load user data
	if ($userdata == "") $userdata = loadfile ($mgmt_config['abs_path_data']."user/", "user.xml.php");  
	                  
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
	$multiobject_array = explode ("|", $multiobject);
	array_shift ($multiobject_array);
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
				$password = $confirm_password = substr (session_id(), 0, 8);
           
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
					$mail_sender_array = selectcontent ($userdata, "<user>", "<login>", $_SESSION['hcms_user']);
          
					if ($mail_sender_array != false)
          {
						// real name
						$buffer_array = getcontent ($mail_sender_array[0], "<realname>");
            
						if ($buffer_array != false && $buffer_array[0] != "")
            {
							$realname_from = $buffer_array[0];
						}
            else $realname_from = "";
            
						// email
						$buffer_array = getcontent ($mail_sender_array[0], "<email>");
            
						if ($buffer_array != false && $buffer_array[0] != "")
            {
							$email_from = $buffer_array[0];
						}
            else
            {
							$email_from = "automailer@".$mgmt_config[$site]['mailserver'];
						}
            
						// signature
						$buffer_array = getcontent ($mail_sender_array[0], "<signature>");
            
						if ($buffer_array != false && $buffer_array[0] != "")
            {
							$mail_signature = $buffer_array[0];              
						}
            else $mail_signature = "";
					}
				}
        
				$mailer = new HyperMailer();
        $mailer->CharSet = $lang_codepage[$lang];
        
				// if the mailserver config entry is empty, the email address of the user will be used for FROM
				if ($email_from != "" && $mgmt_config[$site]['mailserver'] == "")
        {
					$mailer->From = $email_from;
					$mailer->FromName = $realname_from;
				}
        else
        {
					$mailer->From = "automailer@".$mgmt_config[$site]['mailserver'];
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
				if (!empty ($page) || is_array ($multiobject_array))
        {
          // send file as attachment in mail
					if ($attachment_type == "link" || $attachment_type == "download")
          {
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
  								if ($attachment_type == "link")
                  {
                    $link = createaccesslink ($siteTemp, $locationTemp, $pageTemp, $catTemp, "", $login, "al", $lifetime);
                  }
                  // create download link
                  elseif ($attachment_type == "download")
                  {
                    $link = createaccesslink ($siteTemp, $locationTemp, $pageTemp, $catTemp, "", $login, "dl", $lifetime);
                  } 
                  
                  if ($link != "")
                  {
    								if ($include_metadata == "yes")
                    {
    									$metadata = $text11[$lang].":\n".getmetadata ($locationTemp, $pageTemp);
    								}
                    else
                    {
    									$metadata = "";
    								}
                  }
                  else $link = $text42[$lang].$multiobject_entry;
                  
  								// links to send
  								$mail_link .= $link."\n\n".$metadata."\n\n";
  								// for mail report
  								$mail_links[] = $link;                   
                }
							}
						}
            // single object
            elseif ($location != "" && $page != "" && $site != "")
            {
							if ($folder != "")
              {
								$locationTemp = $location.$folder."/";
							}
              else $locationTemp = $location;

              // create link for hyperCMS access
							if ($attachment_type == "link")
              {
                $link = createaccesslink ($site, $locationTemp, $page, $cat, "", $login, "al", $lifetime);
              }
              // create download link
              elseif ($attachment_type == "download")
              {
                $link = createaccesslink ($site, $locationTemp, $page, $cat, "", $login, "dl", $lifetime);
              }

              if ($link != "")
              {
								if ($include_metadata == "yes")
                {
									$metadata = $text11[$lang].":\n".getmetadata ($locationTemp, $page);
								}
                else
                {
									$metadata = "";
								}
              }
              else $link = $text42[$lang].convertpath ($site, $locationTemp, $cat).$page;
              
							// links to send
							$mail_link .= $link."\n\n".$metadata."\n\n";
							// for mail report
							$mail_links[] = $link;
						}
					}
          // send attachments (no folders allowed!)
          else
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
                  
  								if ($pageTemp != ".folder" && @is_file ($locationTemp.$pageTemp))
                  {
  									$objectdata = loadfile ($locationTemp, $pageTemp);
                    
  									if ($objectdata != false)
                    {
  										$mediafile = getfilename ($objectdata, "media");
                      
  										if ($mediafile != false)
                      {
  											$mediadir = getmedialocation ($siteTemp, $mediafile, "abs_path_media");
  											// $media_info = getfileinfo ($site, $mediafile, $cat);
  											$mailer->AddAttachment ($mediadir.$siteTemp.'/'.$mediafile, $pageTemp);
   
  											if ($include_metadata == "yes")
                        {
  												$metadata_str .= specialchr_decode ($pageTemp)."\n-------------------\n".$text11[$lang].":\n".getmetadata ($locationTemp, $pageTemp)."\n\n";
  											}
  										}
  									}
  								}
                }
							}
						}
            // single object
            elseif ($location != "" && $page != "" && $folder == "")
            {              
							if (@is_file ($location.$page))
              {
								$objectdata = loadfile ($location, $page);
								$mediafile = getfilename ($objectdata, "media");
								$mediadir = getmedialocation ($site, $mediafile, "abs_path_media");
								$mailer->AddAttachment ($mediadir.$site.'/'.$mediafile, $page);
                
								if ($include_metadata == "yes")
                {
									$metadata_str .= specialchr_decode ($page)."\n-------------------\n".$text11[$lang].":\n".getmetadata ($location, $page)."\n\n";
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
					$mail_fullbody .= $text16[$language].":\n".$mail_link."\n\n";
				}
        
        $mail_fullbody .= $mail_signature;

        // subject and body
    		$mailer->Subject = html_decode ($mail_title, $lang_codepage[$lang]);
    		$mailer->Body = html_decode ($mail_fullbody, $lang_codepage[$lang]);        
        
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
				$mail_sender_array = selectcontent ($userdata, "<user>", "<login>", $_SESSION['hcms_user']);
        
				if ($mail_sender_array != false)
        {
					// real name
					$buffer_array = getcontent ($mail_sender_array[0], "<realname>");
          
					if ($buffer_array != false && $buffer_array[0] != "")
          {
						$realname_from = $buffer_array[0];
					}
          else $realname_from = "";
          
					// email
					$buffer_array = getcontent ($mail_sender_array[0], "<email>");
          
					if ($buffer_array != false && $buffer_array[0] != "")
          {
						$email_from = $buffer_array[0];
					}
          else
          {
						$email_from = "automailer@".$mgmt_config[$site]['mailserver'];
					}
          
					// signature
					$buffer_array = getcontent ($mail_sender_array[0], "<signature>");
          
					if ($buffer_array != false && $buffer_array[0] != "")
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
            
						if ($buffer_array != false && $buffer_array[0] != "")
            {
							$realname_to = $buffer_array[0];
						}
            
						// email
						$buffer_array = getcontent ($mail_receiver_array[0], "<email>");
            
						if ($buffer_array != false && $buffer_array[0] != "")
            {
							$email_to = $buffer_array[0];
						}
            else
            {
							$general_error[] = sprintf ($text37[$lang], $realname_to);
						}
            
						// language
						$buffer_array = getcontent ($mail_receiver_array[0], "<language>");
            
						if ($buffer_array != false && $buffer_array[0] != "")
            {
							$user_lang = $buffer_array[0];
						}
            else
            {
							$user_lang = $lang;            
						}
					}
          
					// send mail to receiver
					$mailer = new HyperMailer();
          $mailer->CharSet = $lang_codepage[$lang];
          
					$metadata = "";
					$mail_link = "";
          
					if ($email_to != "")
          {   
						// create links
						if ($page != "" || is_array ($multiobject_array))
            { 
              // send file as link
							if ($attachment_type == "link" || $attachment_type == "download")
              {
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
      								if ($attachment_type == "link")
                      {
                        $link = createaccesslink ($siteTemp, $locationTemp, $pageTemp, $catTemp, "", $user_to, "al", $lifetime);
                      }
                      // create download link
                      elseif ($attachment_type == "download")
                      {
                        $link = createdownloadlink ($siteTemp, $locationTemp, $pageTemp, $catTemp, "", $user_to, "dl", $lifetime);
                      }                     
                              
                      if ($link != "")
                      {
        								if ($include_metadata == "yes")
                        {
        									$metadata = $text11[$lang].":\n".getmetadata ($locationTemp, $pageTemp);
        								}
                        else
                        {
        									$metadata = "";
        								}
                      }
                      else $link = $text42[$lang].$multiobject_entry;
                      
  										// links to send
  										$mail_link .= $link."\n\n".$metadata."\n\n";
  										// for mail report
  										$mail_links[] = $link;
                    }
									}
								}
                // single object
                elseif ($location != "" && $page != "" && $site != "")
                {
									$locationTemp = $location;
                  
									if ($folder != "")
                  {
										$locationTemp = $location.$folder."/";
									}
                  
                  // create link for hyperCMS access
  								if ($attachment_type == "link")
                  {
                    $link = createaccesslink ($site, $locationTemp, $page, $cat, "", $user_to, "al", $lifetime);
                  }
                  // create download link
                  elseif ($attachment_type == "download")
                  {
                    $link = createaccesslink ($site, $locationTemp, $page, $cat, "", $user_to, "dl", $lifetime);
                  }                    
                  
                  if ($link != "")
                  {
    								if ($include_metadata == "yes")
                    {
    									$metadata = $text11[$lang].":\n".getmetadata ($locationTemp, $page);
    								}
                    else
                    {
    									$metadata = "";
    								}
                  }
                  else $link = $text42[$lang].convertpath ($site, $locationTemp, $cat).$page;
                  
									// links to send
									$mail_link .= $link."\n\n".$metadata."\n\n";
									// for mail report
									$mail_links[] = $link;
								}
							}
              // send attachments (no folders allowed!)
              else
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
  										$catTemp = getcategory ($site, $location);
  										$locationTemp = deconvertpath ($location, "file");
  										$pageTemp = getobject ($multiobject_entry);
                      
  										if ($pageTemp != ".folder" && @is_file ($locationTemp.$pageTemp))
                      {
  											$objectdata = loadfile ($locationTemp, $pageTemp);
  											$mediafile = getfilename ($objectdata, "media");
  											$mediadir = getmedialocation ($siteTemp, $mediafile, "abs_path_media");
  											$mailer->AddAttachment ($mediadir.$siteTemp.'/'.$mediafile, $pageTemp);
                        
  											if ($include_metadata == "yes")
                        {
  												$metadata_str .= specialchr_decode ($pageTemp)."\n-------------------\n".$text11[$lang].":\n".getmetadata ($locationTemp, $pageTemp)."\n\n";
  											}
  										}
                    }
									}
								}
                // single object
                elseif ($location != "" && $page != "" && $folder == "")
                {                                  
									if (@is_file ($location.$page))
                  {
										$objectdata = loadfile ($location, $page);
										$mediafile = getfilename ($objectdata, "media");
										$mediadir = getmedialocation ($site, $mediafile, "abs_path_media");
										$mailer->AddAttachment ($mediadir.$site.'/'.$mediafile, $page);
                    
										if ($include_metadata == "yes")
                    {
											$metadata_str .= specialchr_decode ($page)."\n-------------------\n".$text11[$lang].":\n".getmetadata ($location, $page)."\n\n";
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
							$mail_fullbody .= $text16[$user_lang].":\n".$mail_link."\n\n";
						}
            
						$mail_fullbody .= $mail_signature;
            
						// mail header
						// if the mailserver config entry is empty, the email address of the user will be used for FROM
						$mail_header = "";
            
						if ($email_from != "" && $mgmt_config[$site]['mailserver'] == "")
            {
							$mailer->From = $email_from;
							$mailer->FromName = $realname_from;
						}
            else
            { 
							$mailer->From = "automailer@".$mgmt_config[$site]['mailserver'];
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
						$mailer->Subject = html_decode ($mail_title, $lang_codepage[$lang]);
						$mailer->Body = html_decode ($mail_fullbody, $lang_codepage[$lang]);
            
            // create email recipient array
						$email_to_array = splitstring ($email_to);
            
						foreach ($email_to_array as $email_to_entry)
            {
							$mailer->AddAddress ($email_to_entry);
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
		$general_error = $text9[$lang];
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
              createtask ($site, $user, $email_from, $user_to, "", "user", $objectpath, $mail_title."\n\n".$mail_body, false, $priority);
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
          createtask ($site, $user, $email_from, $user_to, "", "user", $objectpath, $mail_title."\n\n".$mail_body, false, $priority);
        }
      }      
    }
  }
  
  // reset mail address
	$email_to = "";  
}

// security token
$token_new = createtoken ($user);
?>
<!DOCTYPE html>
<html>
	<head>
		<title>hyperCMS</title>
		<meta http-equiv="Content-Type" content="text/html; charset=<?php echo $lang_codepage[$lang]; ?>">
    <meta name="viewport" content="width=580; initial-scale=0.9; maximum-scale=1.0; user-scalable=1;" />
		<link rel="stylesheet" href="<?php echo getthemelocation(); ?>css/main.css">
		<link rel="stylesheet" href="javascript/jquery-ui/jquery-ui-1.10.2.css">
		<script src="javascript/main.js" type="text/javascript"></script>
		<!-- We use Jquery and Jquery UI Autocomplete -->
		<script src="javascript/jquery/jquery-1.9.1.min.js" type="text/javascript"></script>
		<script src="javascript/jquery-ui/jquery-ui-1.10.2.min.js" type="text/javascript"></script>
		<script type="text/javascript">
			<!--
      function isIntegerValue(value)
      {
        if (value != "") return value % 1 == 0;
        else return true;
      }

			function checkForm()
			{  
				if ($("div#emails div").length < 1 && $("#group_login").val() == "")
        {
					alert (hcms_entity_decode("<?php echo $text34[$lang]; ?>"));
					$('input#selector').focus();
					return false;
				}
        
				if (document.getElementById("mail_title").value == "")
        {
					alert (hcms_entity_decode("<?php echo $text12[$lang]; ?>"));
					$("input#mail_title").focus();
					return false;
				}
        
				if (document.getElementById("valid_active").checked == true)
        {
          var valid_days = document.getElementById("valid_days").value;
          var valid_hours = document.getElementById("valid_hours").value;
          
					if (isIntegerValue(valid_days) == false || isIntegerValue(valid_hours) == false)
          {
            alert (hcms_entity_decode("<?php echo $text52[$lang]; ?>"));
					  document.getElementById("valid_days").focus();
					  return false;
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
				<?php 
						$tmpuser = array();
            
						if(is_array($alluser_array))
            {
							foreach($alluser_array as $user_id => $user_login)
							{
								if(array_key_exists($user_id, $allemail_array) && !empty($allemail_array[$user_id]))
								{
									$username = (array_key_exists($user_id, $allrealname_array) && !empty($allrealname_array[$user_id])) ? $allrealname_array[$user_id] : $user_login;
									$tmpuser[] = "{ loginname: \"{$user_login}\", id: \"{$user_id}\", username:\"{$username}\", email:\"{$allemail_array[$user_id]}\", label: \"{$username} ({$allemail_array[$user_id]})\" }"; 
								}
							}
							
						}
					?>
				var userlist = [<?php echo implode(",\n", $tmpuser);?>];
				<?php
					unset($tmpuser);
					// id for the special element
					$idspecial = "-99999999";
				?>

				var noneFound = { id: "<?php echo $idspecial; ?>", label: hcms_entity_decode("<?php echo $text33[$lang]; ?>") };
				
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
										var img = '<div><img onclick="remove_element(\''+mainname+'\')" onmouseout="hcms_swapImgRestore();" onmouseover="hcms_swapImage(\''+delname+'\', \'\', \'<?php echo getthemelocation(); ?>img/button_close_over.gif\',1);" title="<?php echo $text30[$lang]; ?>" alt="<?php echo $text30[$lang]; ?>" src="<?php echo getthemelocation(); ?>img/button_close.gif" name="'+delname+'" style="width:16px; height:16px; border:0; float:right; display:inline; cursor:pointer;"></div>';
										var input = '<input type="hidden" name="email_to[]" id="'+inputid+'" value="'+inputval+'"/>';
										var divtext =  '<div id="'+divtextid+'"style="float:left">'+inputval+'&nbsp;</div>';
										$("div#emails").append("<div id=\""+mainname+"\" style=\"width:355px; height:16px;\">"+input+divtext+img+"</br></div>");
										showHideLayers("attention_settings", 'visible');
										$(this).val("");
									}
                  else
                  {
										alert (hcms_entity_decode("<?php echo $text8[$lang]; ?>"));
									}
								} 
								else
								{
									alert (hcms_entity_decode("<?php echo $text31[$lang]; ?>"));
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
									var img = '<div><img onclick="remove_element(\''+mainname+'\')" onmouseout="hcms_swapImgRestore();" onmouseover="hcms_swapImage(\''+delname+'\', \'\', \'<?php echo getthemelocation(); ?>img/button_close_over.gif\',1);" title="<?php echo $text30[$lang]; ?>" alt="<?php echo $text30[$lang]; ?>" src="<?php echo getthemelocation(); ?>img/button_close.gif" name="'+delname+'" style="width:16px; height:16px; border:0; float:right; display:inline; cursor:pointer;"></div>';
									var input = '<input type="hidden" name="user_login[]" id="'+inputid+'" value="'+ui.item.loginname+'"/>';
									var divtext =  '<div id="'+divtextid+'" style="float:left" title="'+ui.item.email+'">'+ui.item.username+'&nbsp;</div>';
									$("div#emails").append("<div id=\""+mainname+"\" style=\"width:355px; height:16px;\">"+input+divtext+img+"</br></div>");
								} 
								else
								{
									alert (hcms_entity_decode("<?php echo $text31[$lang]; ?>"));
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
				// call click function for the first tag!
				$("#menu-Recipient").click();
				$("#userform").keypress(function (key) 
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
  	if (isset ($multiobject_array) && is_array ($multiobject_array)) $title = sizeof ($multiobject_array) . $text22[$lang];
    else $title = $pagename;
                
    echo showtopbar ($text0[$lang].": ".$title, $lang);
    ?>
  
		<form id="userform" name="userform" action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post">
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
        if (!empty ($mail_success))
        {
					$show .= "<strong>".$text35[$lang]."</strong><br />\n".implode (", ", html_encode ($mail_success))."<br />\n";
  			}
              
        // mail error message
  			if (!empty ($mail_error))
        {
  			  $show .= "<strong>".$text36[$lang]."</strong><br />\n".implode ("<br />", html_encode ($mail_error))."<br />\n";
  			}
              
        // general error message
  			if (!empty ($general_error))
        {
  			  $show .= implode ("<br />", $general_error)."<br />\n";
  			}
              
        // links
				if (($attachment_type == 'download') && is_array ($mail_links))
        {
					foreach ($mail_links as $link)
          {
						$show .= "<br />Link: <input type=\"text\" value=\"".$link."\" style=\"width:475px;\" />\n";
					}
				}
  
  			$show .= "</div>";
              
        echo showmessage ($show, 570, 200, $lang, "position:fixed; left:5px; top:55px;");
			}
			?>
      <br />
			<div id="LayerMenu" class="hcmsTabContainer" style="position:absolute; z-index:1; visibility:visible; left:0px; top:35px">
  	    <table border="0" cellspacing="0" cellpadding="0">
  	    	<tr align="left" valign="top">
  	    		<td style="width:3px;"><img src="<?php echo getthemelocation(); ?>img/backgrd_tabs_spacer.gif" style="width:3px; height:19px; border:0;" /></td>
            <td align="left" valign="top" class="hcmsTab">
  	    			&nbsp;<a id="menu-Recipient" href="#" onClick="showHideLayers('LayerRecipient','show','line_Recipient','visible','LayerGroup','hide','line_Group','invisible','LayerSettings','hide','line_Settings','invisible'); close_selector();"><?php echo $text29[$lang]; ?></a>
  	    		</td>
  	    		<td style="width:3px;"><img src="<?php echo getthemelocation(); ?>img/backgrd_tabs_spacer.gif" style="width:3px; height:19px; border:0;" /></td>
            <td align="left" valign="top" class="hcmsTab">
  	    			&nbsp;<a id="menu-Group" href="#" onClick="showHideLayers('LayerRecipient','hide','line_Recipient','invisible','LayerGroup','show','line_Group','visible','LayerSettings','hide','line_Settings','invisible'); close_selector();"><?php echo $text20[$lang]; ?></a>
  	    		</td>
  	    		<td style="width:3px;"><img src="<?php echo getthemelocation(); ?>img/backgrd_tabs_spacer.gif" style="width:3px; height:19px; border:0;" /></td>
  	    		<td>
            <td align="left" valign="top" class="hcmsTab">
  	    			&nbsp;<a id="menu-Settings" href="#" onClick="showHideLayers('LayerRecipient','hide','line_Recipient','invisible','LayerGroup','hide','line_Group','invisible','LayerSettings','show','line_Settings','visible'); close_selector();"><?php echo $text32[$lang]; ?><span id="attention_settings" style="color:red; visibility:hidden;">!</span></a>
  	    		</td>
      		</tr>
      	</table>
			</div>
      
      <div id="line_Recipient" class="hcmsWorkplaceGeneric" style="position:absolute; width:118px; height:2px; z-index:2; left:4px; top:57px; visibility:visible"> </div> 
      <div id="line_Group" class="hcmsWorkplaceGeneric" style="position:absolute; width:118px; height:2px; z-index:2; left:127px; top:57px; visibility:hidden"> </div>
      <div id="line_Settings" class="hcmsWorkplaceGeneric" style="position:absolute; width:118px; height:2px; z-index:2; left:250px; top:57px; visibility:hidden"> </div>      
          
			<div id="Tabs" style="width:100%; height:120px; margin:30px 0;">
				<div id="tabs">
					<div id="LayerRecipient">
						<table width="100%" border="0" cellspacing="0" cellpadding="3">
							<tr>
							  <td width="180" align="left" valign="top" nowrap="nowrap"><?php echo $text1[$lang]; ?>:</td>
							  <td id="selectbox" align="left" valign="top">
								  <input type="text" value="" style="width:350px;" name="selector" id="selector" />
							  </td>
							<tr>
								<td align="left" valign="top" nowrap="nowrap">
									<?php echo $text29[$lang]; ?>:
								</td>
								<td align="left" valign="top">
									<div style="overflow:auto; max-height:120px;" id="emails">
									</div>
								</td>
							</tr>
						</table>
					</div>
					<div id="LayerGroup">
						<table width="100%" border="0" cellspacing="0" cellpadding="3">
							<tr>
								<td width="180" align="left" valign="top" nowrap="nowrap">
									<?php echo $text38[$lang]; ?>:
								</td>
								<td align="left" valign="top">
									<?php echo $text39[$lang]; ?>
								</td>
							</tr>
							<tr>
								<td width="180" align="left" valign="top" nowrap="nowrap">
									<?php echo $text20[$lang]; ?>:
								</td>
								<td align="left" valign="top">
									<select name="group_login" id="group_login" style="width:350px;">
										<option value="" selected="selected">--- <?php echo $text21[$lang]; ?> ---</option>
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
						<table width="100%" border="0" cellspacing="0" cellpadding="3">
							<tr>
								<td width="180" align="left" valign="top" nowrap="nowrap">
									<?php echo $text38[$lang]; ?>:
								</td>
								<td align="left" valign="top">
									<?php echo $text40[$lang]; ?>
								</td>
							</tr>
							<tr>
								<td align="left" valign="top" nowrap="nowrap"><?php echo $text19[$lang]; ?>: </td>
								<td align="left" valign="top">
									<select name="language" style="width:350px;">
									<?php
									foreach ($lang_shortcut as $lang_opt)
                  {
										if ($language == $lang_opt)
                    {
											echo "<option value=\"".$lang_opt."\" selected=\"selected\">".$lang_name[$lang_opt]."</option>\n";
										}
                    else
                    {
											echo "<option value=\"".$lang_opt."\">".$lang_name[$lang_opt]."</option>\n";
										}
									}
									?>
									</select>            
								</td>
							</tr>
							<tr>
								<td align="left" valign="top" nowrap="nowrap"><?php echo $text10[$lang]; ?>: </td>
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
    											if ($allgroup == $user_group)
                          {
    												$selected = "selected=\"selected\"";
    											}
                          else
                          {
    												$selected = "";
    											}
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
			</div>
			<div id="LayerMail">
				<table width="100%" border="0" cellpadding="3" cellspacing="0">
					<tr>
						<td colspan="2" height="3" valign="bottom">
							<hr />
						</td>
					</tr>
					<!-- CC, BCC -->
					<tr>
						<td width="180" align="left" valign="top" nowrap="nowrap"><?php echo $text17[$lang]; ?>: </td>
						<td align="left" valign="top">
							<input type="text" name="email_cc" style="width:350px;" value="<?php echo $email_cc; ?>" />
						</td>
					</tr>
					<tr>
						<td align="left" valign="top" nowrap="nowrap"><?php echo $text18[$lang]; ?>: </td>
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
						<td align="left" valign="top" nowrap="nowrap"><?php echo $text15[$lang]; ?>:</td>
						<td align="left" valign="top">
							<input type="text" id="mail_title" name="mail_title" style="width:350px;" value="<?php echo $mail_title; ?>" />
						</td>
					</tr>
					<tr>
						<td align="left" valign="top" nowrap="nowrap"><?php echo $text7[$lang]; ?>:</td>
						<td align="left" valign="top">
							<textarea id="mail_body" name="mail_body" rows="6" style="width:350px;"><?php echo $mail_body; ?></textarea>
						</td>
					</tr>
					<!-- SEND FILES AS ATTACHMENT OR AS LINK -->
					<tr>
						<td colspan="2" height="3" valign="bottom">
							<hr />
						</td>
					</tr>
					<?php 
					if ($page != "" || $multiobject_array)
          {
            // check if attachment can be added or files can be downloaded
					  $allow_attachment = true;
            $allow_download = true;
            
            // we only check components
            if ($cat == "comp")
            {
              // multiobjects
							if (isset ($multiobject_array) && is_array ($multiobject_array))
              {
								foreach ($multiobject_array as $multiobject)
                {
                  $filePath = deconvertpath ($multiobject, "file");
                  
                  // folder
                  if (getobject ($multiobject) == ".folder" || is_dir ($filePath))
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
					?>
					<tr>
						<td align="left" valign="top" nowrap="nowrap"><?php echo $text26[$lang]; ?>:</td>
						<td align="left" valign="top">
              <?php if ($allow_download) { ?>
              <input type="radio" name="attachment_type" id="type_download" onclick="document.getElementById('valid_active').disabled=false; if (document.getElementById('valid_active').checked==true) { document.getElementById('valid_days').disabled=false; document.getElementById('valid_hours').disabled=false; }" value="download" <?php if ($mgmt_config['maillink'] == "download" || $mgmt_config['maillink'] == "") echo "checked=\"checked\""; ?> /> <?php echo $text41[$lang]; ?><br />
              <?php } ?>
              <input type="radio" name="attachment_type" id="type_link" onclick="document.getElementById('valid_active').disabled=false; if (document.getElementById('valid_active').checked==true) { document.getElementById('valid_days').disabled=false; document.getElementById('valid_hours').disabled=false; }" value="link" <?php if ($mgmt_config['maillink'] == "access") echo "checked=\"checked\""; ?> /> <?php echo $text24[$lang]; ?><br />
              <?php if ($allow_attachment) { ?>
							<input type="radio" name="attachment_type" id="type_attachment" onclick="document.getElementById('valid_active').checked=false; document.getElementById('valid_active').disabled=true; document.getElementById('valid_days').disabled=true; document.getElementById('valid_hours').disabled=true;" value="attachment" /> <?php echo $text25[$lang]; ?>
						  <?php } ?>
						</td>
					</tr>
					<tr>
						<td align="left" valign="top" nowrap="nowrap"><?php echo $text48[$lang]; ?>:</td>
						<td align="left" valign="top">
              <input type="checkbox" name="valid_active" id="valid_active" value="yes" onclick="if (this.checked==true) { document.getElementById('valid_days').disabled=false; document.getElementById('valid_hours').disabled=false; } else { document.getElementById('valid_days').disabled=true; document.getElementById('valid_hours').disabled=true; }" /> <?php echo $text51[$lang]; ?>
							<input type="text" name="valid_days" id="valid_days" value="" style="width:40px;" disabled="disabled" /> <?php echo $text49[$lang]; ?>&nbsp;
              <input type="text" name="valid_hours" id="valid_hours" value="" style="width:40px;" disabled="disabled" /> <?php echo $text50[$lang]; ?>
						</td>
					</tr>          
					<tr>
						<td align="left" valign="top" nowrap="nowrap"><?php echo $text23[$lang]; ?>:</td>
						<td align="left" valign="top">
							<input type="checkbox" name="include_metadata" value="yes" <?php if ($include_metadata == "yes") echo "checked=\"checked\""; ?>/> 
							<?php echo $text28[$lang]; ?>
						</td>
					</tr>
					<tr>
						<td align="left" valign="top" nowrap="nowrap"><?php echo $text43[$lang]; ?>:</td>
						<td align="left" valign="top">
							<input type="checkbox" name="create_task" value="yes" onclick="document.getElementById('type_link').checked=true;" <?php if ($create_task == "yes") echo "checked=\"checked\""; ?>/> 
							<?php echo $text44[$lang]; ?>
              <select name="priority">
                <option value="low" selected="selected"><?php echo $text45[$lang]; ?></option>
                <option value="medium"><?php echo $text46[$lang]; ?></option>
                <option value="high"><?php echo $text47[$lang]; ?></option>
              </select>
						</td>
					</tr>          
					<?php 
					} 
					?>
					<tr>
						<td align="left" valign="top" nowrap="nowrap"><?php echo $text4[$lang]; ?>: </td>
						<td align="left" valign="top">
							<img name="ButtonSubmit" src="<?php echo getthemelocation(); ?>img/button_OK.gif" onClick="if (checkForm()) document.forms['userform'].submit();" onMouseOver="hcms_swapImage('ButtonSubmit','','<?php echo getthemelocation(); ?>img/button_OK_over.gif',1)" onMouseOut="hcms_swapImgRestore()" style="border:0; cursor:pointer;" align="absmiddle" title="OK" alt="OK" />
						</td>
					</tr>
				</table>
			</div>
		</form>
	</body>
</html>