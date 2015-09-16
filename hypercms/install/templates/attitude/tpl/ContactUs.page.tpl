<?xml version="1.0" encoding="utf-8" ?>
<template>
<name>ContactUs</name>
<user>admin</user>
<category>page</category>
<extension>xhtml</extension>
<application>php</application>
<content><![CDATA[<?php if ("%view%" == "publish") session_start(); ?>
[hyperCMS:textc id='NavigationHide' label='Hide in Navigation' value='yes' infotype='meta' onPublish='hidden']
[hyperCMS:textu id='NavigationSortOrder' label='Navigation Sort Order' constraint='inRange0:1000' infotype='meta' onPublish='hidden' height='25']
[hyperCMS:fileinclude file='%abs_comp%/%publication%/configuration.php']
<!DOCTYPE html>
<html>
  <head>
    <title>[hyperCMS:textu id='Title' infotype='meta' height='25' label='Page Title' constraint='R']</title>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1" />
    <meta name="description" content="[hyperCMS:textu id='Description' infotype='meta' height='50']" />
    <meta name="keywords" content="[hyperCMS:textu id='Keywords' infotype='meta' height='50']" />
    <link rel="shortcut icon" href="%tplmedia%/favicon.ico"> 
    <!-- 57 x 57 Android and iPhone 3 icon -->
    <link rel="apple-touch-icon" media="screen and (resolution: 163dpi)" href="%tplmedia%/mobile_icon57.png" />
     <!-- 114 x 114 iPhone 4 icon -->
    <link rel="apple-touch-icon" media="screen and (resolution: 326dpi)" href="%tplmedia%/mobile_icon114.png" />
    <!-- 57 x 57 Nokia icon -->
    <link rel="shortcut icon" href="%tplmedia%/mobile_icon57.png" />
    <link rel="stylesheet" id="attitude_style-css" hypercms_href="%tplmedia%/style.css" type="text/css" media="all" />
    <script type="text/javascript" src="%tplmedia%/jquery-1.11.1.min.js"></script>
    <script type="text/javascript" src="%tplmedia%/jquery-migrate-1.2.1.min.js"></script>
    <script type="text/javascript" src="%tplmedia%/backtotop.js"></script>
    <script type="text/javascript" src="%tplmedia%/tinynav.js"></script>
  </head>
  <body class="no-sidebar-template">
    <div class="wrapper">
      <header id="branding">
        [hyperCMS:tplinclude file='Header.inc.tpl']
        <nav id="access" class="clearfix" >
          <div class="container clearfix">
            [hyperCMS:tplinclude file='Navigation.inc.tpl']
          </div>
        </nav><!-- #access -->
        <div class="page-title-wrap">
          <div class="container clearfix">
            [hyperCMS:tplinclude file='Breadcrumb.inc.tpl']			   
             <h3 class="page-title">[hyperCMS:textu id='Title' height='25' label='Page Title' constraint='R']</h3><!-- .page-title -->
          </div>
        </div>
      </header>
      <div id="main" class="container clearfix">
        <div id="container">
          <div id="primary" class="no-margin-left">
            <div id="content">
              <div class="entry_content clearfix">
                <h4>[hyperCMS:textu id='EntryTitle' label='Entry Title' height='25']</h4>
                <?php
                function getrequestvalue ($variable, $default="")
                {
                  if ($variable != "")
                  {
                    // get from request
                    if (array_key_exists ($variable, $_POST)) $result = $_POST[$variable];
                    elseif (array_key_exists ($variable, $_GET)) $result = $_GET[$variable];
                    else $result = $default;
                
                    return $result;
                  }
                  else return $default;
                }
                
                // input parameters
                $name	= getrequestvalue ('name');
                $email	= getrequestvalue ('email');
                $subject	= getrequestvalue ('subject');
                $message	= getrequestvalue ('message');
                $token = getrequestvalue ('token');

                // get session token if it exists
                if (isset ($_SESSION['token'])) $session_token = $_SESSION['token'];
                else $session_token = "";

                // check input parameters and send mail
                if ($token == $session_token && !empty ($config['contact']['email']) && !empty ($name) && !empty ($email) && !empty ($subject) && !empty ($message))
                {
                  // set language for hypermailer class, required to obtain character set (UTF-8)
                  $lang = "en";

                  // include hypermailer class
                  require ("%abs_hypercms%/config.inc.php");
                  if (!class_exists ("HyperMailer")) require ($mgmt_config['abs_path_cms']."function/hypermailer.class.php");
                    
                  $mailer = new HyperMailer();
                  $mailer->SetFrom ($email, $name);
                  $mailer->AddReplyTo ($email, $name);
                  $mailer->AddAddress ($config['contact']['email']);
                  $mailer->Subject = $subject;
                  $mailer->Body = $message;
                    
                  if (!$mailer->Send()) $message = "An error occured, please contact <a href=\"mailto:".$config['contact']['email']."\">".str_replace ("@", "(at)", $config['contact']['email'])."</a>";
                  else $message = "Thank you for your inquiry.";
                    
                  echo "<h5>".$message."</h5>";
                } 
                else
                {
                  // create security token
                  $token = md5(uniqid(mt_rand(), true));
                  $_SESSION['token'] = $token;					
                ?>
                
                <div id="error" style="display: none; padding:4px; border:1px solid red; background:#ffdcd5;">There were errors on the form</div>
                
                <form method="post" id="contact" name="contact">
                  <div style="display:none;">
                  <input type="hidden" name="token" value="<?php echo $token; ?>" />
                  </div>
                  <p>
                  Your Name (required)<br />
                  <span >
                  <input type="text" aria-required="true" size="40" value="" name="name" id="name" />
                  </span> 
                  </p>
                  <p>Your Email (required)<br />
                  <span >
                  <input type="text" aria-required="true" size="40" value="" name="email" id="email"/>
                  </span>
                  </p>
                  <p>Subject (required)<br />
                  <span >
                  <input type="text" aria-required="true" size="40" value="" name="subject" id="subject"/>
                  </span>
                  </p>
                  <p>Your Message (required)<br />
                  <span >
                  <textarea aria-required="true" rows="10" cols="40" name="message" id="message"></textarea>
                  </span>
                  </p>
                  <p>
                  <input type="submit" value="Send" name="send">
                  </p>
                </form>
                <?php } ?>
              </div>
            </div>
          </div>
          <div id="secondary">
            <aside class="widget widget_text" id="text-2">
              <h3 class="widget-title">[hyperCMS:textu id='ContactTitle' label='Contact Title' height='25']</h3>			
              <div class="textwidget">
                 [hyperCMS:textf id='ContactInfo' label='Contact Information']
              </div>
            </aside>
          </div>
        </div>
      </div><!-- #main -->
      [hyperCMS:tplinclude file='Footer.inc.tpl']
    </div><!-- .wrapper -->
    <script language="JavaScript">
    <!--
    $(function(){
      // Place ID's of all required fields here.
      required = ["name", "email", "subject", "message"];

      // If using an ID other than #email or #error then replace it here
      email = $("#email");
      errornotice = $("#error");

      // The text to show up within a field when it is incorrect
      emptyerror = "Please fill out this field";
      emailerror = "Please enter a valid e-mail";

      $("#contact").submit(function(){	
        //Validate required fields
        for (i=0;i<required.length;i++) {
          var input = $('#'+required[i]);
          if ((input.val().trim() == "") || (input.val() == emptyerror)) {
            input.addClass("needsfilled");
            input.val(emptyerror);
            errornotice.fadeIn(750);
          } else {
            input.removeClass("needsfilled");
          }
        }
        // Validate the e-mail.
        if (!/^([a-zA-Z0-9_\.\-])+\@(([a-zA-Z0-9\-])+\.)+([a-zA-Z0-9]{2,4})+$/.test(email.val())) {
          email.addClass("needsfilled");
          email.val(emailerror);
        }

        //if any inputs on the page have the class 'needsfilled' the form will not submit
        if ($(":input").hasClass("needsfilled")) {
          return false;
        } else {
          errornotice.hide();
          return true;
        }
      });
      
      // Clears any fields in the form when the user clicks on them
      $(":input").focus(function(){		
         if ($(this).hasClass("needsfilled") ) {
          $(this).val("");
          $(this).removeClass("needsfilled");
         }
      });
    });
    //-->
    </script>
  </body>
</html>]]></content>
</template>