<?php
/*
 * This file is part of
 * hyper Content & Digital Management Server - http://www.hypercms.com
 * Copyright (c) by hyper CMS Content Management Solutions GmbH
 *
 * You should have received a copy of the License along with hyperCMS.
 */
 
// include service
include ("service/sendmail.php");

// security token
$token_new = createtoken ($user);
?>
<!DOCTYPE html>
<html>
  <head>
    <title>hyperCMS</title>
    <meta charset="<?php echo getcodepage ($lang); ?>" />
    <meta name="viewport" content="width=device-width, initial-scale=0.62, maximum-scale=1.0, user-scalable=1" />
    <link rel="stylesheet" href="<?php echo getthemelocation(); ?>css/main.css" />
    <link rel="stylesheet" href="javascript/jquery-ui/jquery-ui-1.12.1.css">
    <script src="javascript/main.js" type="text/javascript"></script>
    <!-- Jquery and Jquery UI Autocomplete -->
    <script src="javascript/jquery/jquery-1.10.2.min.js" type="text/javascript"></script>
    <script src="javascript/jquery-ui/jquery-ui-1.12.1.min.js" type="text/javascript"></script>
        
    <link rel="stylesheet" type="text/css" href="javascript/rich_calendar/rich_calendar.css">
    <script type="text/javascript" src="javascript/rich_calendar/rich_calendar.js"></script>
    <script type="text/javascript" src="javascript/rich_calendar/rc_lang_en.js"></script>
    <script type="text/javascript" src="javascript/rich_calendar/rc_lang_de.js"></script>
    <script src="javascript/rich_calendar/domready.js"></script>
    <script type="text/javascript">
    
    var cal_obj = null; 
    var cal_format = '%Y-%m-%d';
    var cal_field = null;
    
    // show calendar
    function show_cal (el, field_id, format, time)
    {
      if (cal_obj) return;
      
      cal_field = field_id;
      cal_format = format;
      var datefield = document.getElementById(field_id);
    
    	cal_obj = new RichCalendar();
    	cal_obj.start_week_day = 1;
    	cal_obj.show_time = time;
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
        selectCheckbox('format_vid', 'format_vid1');
        
        // if a folder has been selected
        if (folderincluded)
        {
          // disable checkboxes except original
          for (var i=2; i<=maxoptions; i++)
          {
            if (document.getElementById('format_img' + i)) document.getElementById('format_img' + i).disabled = true;
            if (document.getElementById('format_doc' + i)) document.getElementById('format_doc' + i).disabled = true;
            if (document.getElementById('format_vid' + i)) document.getElementById('format_vid' + i).disabled = true;
          }
        }
        else
        {
          // disable checkboxes except original and PDF for documents
          for (var i=3; i<=maxoptions; i++)
          {
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
        selectCheckbox('format_vid', 'all');
        
        // enable all checkboxes
        for (var i=1; i<=maxoptions; i++)
        {
          if (document.getElementById('format_img' + i)) document.getElementById('format_img' + i).disabled = false;
          if (document.getElementById('format_doc' + i)) document.getElementById('format_doc' + i).disabled = false;
          if (document.getElementById('format_vid' + i)) document.getElementById('format_vid' + i).disabled = false;
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
        selectCheckbox('format_vid', 'format_vid1');
        
        // enable all checkboxes
        for (var i=1; i<=maxoptions; i++)
        {
          if (document.getElementById('format_img' + i)) document.getElementById('format_img' + i).disabled = false;
          if (document.getElementById('format_doc' + i)) document.getElementById('format_doc' + i).disabled = false;
          if (document.getElementById('format_vid' + i)) document.getElementById('format_vid' + i).disabled = false;
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
    
    function enablefield(id, enable)
    {
      if (document.getElementById(id))
      {
        if (enable == true) document.getElementById(id).disabled = false;
        else document.getElementById(id).disabled = true;
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
      
      if (document.getElementById("ondate") && document.getElementById("intention"))       
      {
        if (document.getElementById("ondate").checked == true && document.getElementById("maildate").value != "") document.getElementById("intention").value = "savequeue";
        else document.getElementById("intention").value = "sendmail";
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
    
    function openobjectview ()
    {
      var width = Math.max(document.documentElement.clientWidth, window.innerWidth || 0)
      var height = Math.max(document.documentElement.clientHeight, window.innerHeight || 0)

      hcms_showInfo('objectviewLayer',0);
    }
    
    function closeobjectview ()
    {
      hcms_hideInfo('objectviewLayer');
    }
    
    $(document).ready(function()
    {
      <?php if (empty ($format_img) && empty ($format_doc) && empty ($format_vid)) echo "initLinkType();"; ?>
      hcms_setViewportScale();
      
      <?php 
      $tmpuser = array();
      
      if (is_array ($alluser_array))
      {
        foreach ($alluser_array as $temp_id => $temp_user)
        {
          if (array_key_exists ($temp_id, $allemail_array) && !empty ($allemail_array[$temp_id]))
          {
            $temp_realname = (array_key_exists ($temp_id, $allrealname_array) && !empty ($allrealname_array[$temp_id])) ? $allrealname_array[$temp_id] : $temp_user;
            $tmpuser[] = "{ loginname: \"{$temp_user}\", id: \"{$temp_id}\", username:\"{$temp_realname}\", email:\"{$allemail_array[$temp_id]}\", label: \"{$temp_realname} ({$allemail_array[$temp_id]})\" }"; 
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
                  var img = '<div><img onclick="remove_element(\''+mainname+'\')" onmouseout="hcms_swapImgRestore();" onmouseover="hcms_swapImage(\''+delname+'\', \'\', \'<?php echo getthemelocation(); ?>img/button_close_over.png\',1);" title="<?php echo getescapedtext ($hcms_lang['delete-recipient'][$lang]); ?>" alt="<?php echo getescapedtext ($hcms_lang['delete-recipient'][$lang]); ?>" src="<?php echo getthemelocation(); ?>img/button_close.png" name="'+delname+'" style="width:16px; height:16px; border:0; float:right; display:inline; cursor:pointer;"></div>';
                  var input = '<input type="hidden" name="email_to[]" id="'+inputid+'" value="'+inputval+'"/>';
                  var divtext =  '<div id="'+divtextid+'"style="float:left">'+inputval+'&nbsp;</div>';
                  $("div#emails").append("<div id=\""+mainname+"\" style=\"width:355px; height:16px;\">"+input+divtext+img+"<br /></div>");
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
                var img = '<div><img onclick="remove_element(\''+mainname+'\')" onmouseout="hcms_swapImgRestore();" onmouseover="hcms_swapImage(\''+delname+'\', \'\', \'<?php echo getthemelocation(); ?>img/button_close_over.png\',1);" title="<?php echo getescapedtext ($hcms_lang['delete-recipient'][$lang]); ?>" alt="<?php echo getescapedtext ($hcms_lang['delete-recipient'][$lang]); ?>" src="<?php echo getthemelocation(); ?>img/button_close.png" name="'+delname+'" style="width:16px; height:16px; border:0; float:right; display:inline; cursor:pointer;"></div>';
                var input = '<input type="hidden" name="user_login[]" id="'+inputid+'" value="'+ui.item.loginname+'"/>';
                var divtext =  '<div id="'+divtextid+'" style="float:left" title="'+ui.item.email+'">'+ui.item.username+'&nbsp;</div>';
                $("div#emails").append("<div id=\""+mainname+"\" style=\"width:355px; height:16px;\">"+input+divtext+img+"<br /></div>");
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
    </script>
  </head>
  
  <body class="hcmsWorkplaceGeneric" style="overflow:auto" onload="<?php echo $add_onload; ?>">
  
    <!-- preview/live-view --> 
    <div id="objectviewLayer" class="hcmsWorkplaceExplorer" style="display:none; overflow:hidden; position:fixed; margin:0; padding:0; left:0; top:0; right:0; bottom:0; z-index:8000;">
      <div style="position:fixed; right:18px; top:10px; z-index:9000;">
        <img name="hcms_mediaClose" src="<?php echo getthemelocation(); ?>img/button_close.png" class="hcmsButtonTinyBlank hcmsButtonSizeSquare" alt="<?php echo getescapedtext ($hcms_lang['close'][$lang]); ?>" title="<?php echo getescapedtext ($hcms_lang['close'][$lang]); ?>" onMouseOut="hcms_swapImgRestore();" onMouseOver="hcms_swapImage('hcms_mediaClose','','<?php echo getthemelocation(); ?>img/button_close_over.png',1);" onClick="closeobjectview();" />
      </div>
      <div id="objectview" style="width:100%; height:100%; overflow:auto;">
      <?php
      echo showgallery ($multiobject_array, 140, true, $user);
      ?>
      </div>
    </div>
  
    <!-- top bar -->
    <?php
    if (isset ($multiobject_array) && is_array ($multiobject_array)) $title = sizeof ($multiobject_array)." ".$hcms_lang['objects-selected'][$lang];
    else $title = $pagename;
                
    echo showtopbar ($hcms_lang['message'][$lang].": <a href=\"#\" onclick=\"openobjectview()\">".$title."</a>", $lang);
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
      <input type="hidden" name="intention" id="intention" value="sendmail" />
     
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
        if (($download_type == 'download') && !empty ($mail_links) && is_array ($mail_links))
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
      
      <div id="LayerMenu" class="hcmsTabContainer" style="position:absolute; z-index:10; visibility:visible; left:0px; top:40px">
        <div id="tab1" class="hcmsTabActive">
          <a id="menu-Recipient" href="#" onClick="hcms_ElementbyIdStyle('tab1','hcmsTabActive'); hcms_ElementbyIdStyle('tab2','hcmsTabPassive'); hcms_ElementbyIdStyle('tab3','hcmsTabPassive'); hcms_ElementbyIdStyle('tab4','hcmsTabPassive'); showHideLayers('LayerRecipient','show','LayerGroup','hide','LayerSettings','hide','LayerFormats','hide'); close_selector();" title="<?php echo getescapedtext ($hcms_lang['recipients'][$lang]); ?>"><?php echo getescapedtext ($hcms_lang['recipients'][$lang]); ?></a>
        </div>
        <div id="tab2" class="hcmsTabPassive">
          <a id="menu-Group" href="#" onClick="hcms_ElementbyIdStyle('tab1','hcmsTabPassive'); hcms_ElementbyIdStyle('tab2','hcmsTabActive'); hcms_ElementbyIdStyle('tab3','hcmsTabPassive'); hcms_ElementbyIdStyle('tab4','hcmsTabPassive'); showHideLayers('LayerRecipient','hide','LayerGroup','show','LayerSettings','hide','LayerFormats','hide'); close_selector();" title="<?php echo getescapedtext ($hcms_lang['user-group'][$lang]); ?>"><?php echo getescapedtext ($hcms_lang['user-group'][$lang]); ?></a>
        </div>
        <div id="tab3" class="hcmsTabPassive">
          <a id="menu-Settings" href="#" onClick="hcms_ElementbyIdStyle('tab1','hcmsTabPassive'); hcms_ElementbyIdStyle('tab2','hcmsTabPassive'); hcms_ElementbyIdStyle('tab3','hcmsTabActive'); hcms_ElementbyIdStyle('tab4','hcmsTabPassive'); showHideLayers('LayerRecipient','hide','LayerGroup','hide','LayerSettings','show','LayerFormats','hide'); close_selector();" title="<?php echo getescapedtext ($hcms_lang['settings'][$lang]); ?>"><?php echo getescapedtext ($hcms_lang['settings'][$lang]); ?><span id="attention_settings" style="color:red; visibility:hidden;">!</span></a>
        </div>
        <div id="tab4" class="hcmsTabPassive">
          <a id="menu-Formats" href="#" onClick="hcms_ElementbyIdStyle('tab1','hcmsTabPassive'); hcms_ElementbyIdStyle('tab2','hcmsTabPassive'); hcms_ElementbyIdStyle('tab3','hcmsTabPassive'); hcms_ElementbyIdStyle('tab4','hcmsTabActive'); showHideLayers('LayerRecipient','hide','LayerGroup','hide','LayerSettings','hide','LayerFormats','show'); close_selector();" title="<?php echo getescapedtext ($hcms_lang['formats'][$lang]); ?>"><?php echo getescapedtext ($hcms_lang['formats'][$lang]); ?></a>
        </div>
      </div>
      
      <!-- Tabs for recipients, groups and settings --> 
      <div id="Tabs" style="position:absolute; z-index:5; visibility:visible; left:0px; top:70px; width:100%; height:200px;">
        
        <div id="LayerRecipient" style="padding-left:4px;">
          <table width="100%" border="0" cellspacing="0" cellpadding="2">
            <tr>
              <td width="120" align="left" valign="top"><?php echo getescapedtext ($hcms_lang['send-e-mail-to'][$lang]); ?> </td>
              <td id="selectbox" align="left" valign="top">
                <input type="text" value="" style="width:350px;" name="selector" id="selector" />
              </td>
            <tr>
              <td align="left" valign="top">
                <?php echo getescapedtext ($hcms_lang['recipients'][$lang]); ?> 
              </td>
              <td align="left" valign="top">
                <div style="overflow:auto; max-height:120px;" id="emails">
                <?php
                if (!empty ($user_login) && is_array ($user_login))
                {
                  foreach ($user_login as $temp_user)
                  {
                    // find user_id in alluser_array
                    if (!empty ($alluser_array) && is_array ($alluser_array)) $user_id = array_search ($temp_user, $alluser_array);
                    else $user_id = false;

                    if ($user_id && !empty ($allrealname_array[$user_id])) $temp_realname = $allrealname_array[$user_id];
                    else $temp_realname = $temp_user;
                    
                    if ($user_id && !empty ($allemail_array[$user_id])) $temp_email = $allemail_array[$user_id];
                    else $temp_email = "";
                    
                    echo "
                    <div id=\"main_".$temp_user."\" style=\"width:355px; height:16px;\"><input type=\"hidden\" name=\"user_login[]\" id=\"user_login_".$temp_user."\" value=\"".$temp_user."\"/><div id=\"divtext_".$temp_user."\" style=\"float:left\" title=\"".$temp_email."\">".$temp_realname."&nbsp;</div><div><img onclick=\"remove_element('main_".$temp_user."');\" onmouseout=\"hcms_swapImgRestore();\" onmouseover=\"hcms_swapImage('delete_".$temp_user."', '', '".getthemelocation()."img/button_close_over.png', 1);\" title=\"".getescapedtext ($hcms_lang['delete-recipient'][$lang])."\" alt=\"".getescapedtext ($hcms_lang['delete-recipient'][$lang])."\" src=\"".getthemelocation()."img/button_close.png\" name=\"delete_".$temp_user."\" style=\"width:16px; height:16px; border:0; float:right; display:inline; cursor:pointer;\"></div><br /></div>";
                  } 
                }
                ?>
                </div>
              </td>
            </tr>
          </table>
        </div>
        
        <div id="LayerGroup" style="padding-left:4px;">
          <table width="100%" border="0" cellspacing="0" cellpadding="2">
            <tr>
              <td width="120" align="left" valign="top">
                <?php echo getescapedtext ($hcms_lang['attention'][$lang]); ?> 
              </td>
              <td align="left" valign="top">
                <?php echo getescapedtext ($hcms_lang['the-message-will-be-sent-to-all-members-of-the-selected-group'][$lang]); ?>
              </td>
            </tr>
            <tr>
              <td width="120" align="left" valign="top">
                <?php echo getescapedtext ($hcms_lang['user-group'][$lang]); ?> 
              </td>
              <td align="left" valign="top">
                <select name="group_login" id="group_login" style="width:350px;">
                  <option value="">--- <?php echo getescapedtext ($hcms_lang['select'][$lang]); ?> ---</option>
                  <?php 
                  if ($allgroup_array != false && sizeof ($allgroup_array) > 0)
                  {
                    natcasesort ($allgroup_array);
                    reset ($allgroup_array);
                    
                    foreach ($allgroup_array as $allgroup)
                    {
                      echo "
                      <option value=\"".$allgroup."\" ".($allgroup == $group_login ? "selected=\"selected\"" : "").">".$allgroup."</option>";
                    }
                  }
                  ?>
                </select>
              </td>
            </tr>
          </table>
        </div>
        
        <div id="LayerSettings" style="padding-left:4px;">
          <table width="100%" border="0" cellspacing="0" cellpadding="2">
            <tr>
              <td width="120" align="left" valign="top" nowrap="nowrap">
                <?php echo getescapedtext ($hcms_lang['attention'][$lang]); ?> 
              </td>
              <td align="left" valign="top">
                <?php echo getescapedtext ($hcms_lang['these-are-the-settings-which-will-only-be-assigned-to-new-users'][$lang]); ?>
              </td>
            </tr>
            <tr>
              <td align="left" valign="top"><?php echo getescapedtext ($hcms_lang['language-setting'][$lang]); ?> </td>
              <td align="left" valign="top">
                <select name="language" style="width:350px;">
                <?php
                foreach ($hcms_lang_shortcut as $lang_opt)
                {
                  echo "
                  <option value=\"".$lang_opt."\" ".($language == $lang_opt ? "selected=\"selected\"" : "").">".$hcms_lang_name[$lang_opt]."</option>";
                }
                ?>
                </select>            
              </td>
            </tr>
            <tr>
              <td align="left" valign="top"><?php echo getescapedtext ($hcms_lang['member-of-user-group'][$lang]); ?> </td>
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
                        echo "
                        <option value=\"".$allgroup."\" ".($allgroup == $user_group ? "selected=\"selected\"" : "").">".$allgroup."</option>";          
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

        <!-- Download formats -->      
        <div id="LayerFormats" class="hcmsWorkplaceGeneric" style="padding-left:4px;">
          <table border="0" cellspacing="0" cellpadding="2">
            <tr>
              <td colspan="2" class="hcmsHeadline"><?php echo getescapedtext ($hcms_lang['download-formats'][$lang]); ?></td>
            </tr>
            <tr>
              <td align="left" valign="top" nowrap="nowrap">
                <span class="hcmsHeadline"><?php echo getescapedtext ($hcms_lang['image'][$lang]); ?></span><br/>
                <?php 
                if (is_array ($mgmt_imageoptions) && sizeof ($mgmt_imageoptions) > 0)
                {
                  $i = 1;
                  
                  if (!empty ($format_img) && is_array ($format_img) && in_array ("original", $format_img)) $checked = "checked=\"checked\"";
                  else $checked = "";
                  
                  echo "<label><input id=\"format_img".$i."\" name=\"format_img[]\" onclick=\"selectCheckbox('format_img', this.id)\" type=\"checkbox\" value=\"original\" ".$checked." /> <img src=\"".getthemelocation()."img/file_image.png\" align=\"absmiddle\" class=\"hcmsIconList\" /> ".getescapedtext ($hcms_lang['original'][$lang])."</label><br />\n";
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
                          
                          if (!empty ($format_img) && is_array ($format_img) && in_array ($image_type."|".$image_config, $format_img)) $checked = "checked=\"checked\"";
                          else $checked = "";
                          
                          echo "<label><input id=\"format_img".$i."\" name=\"format_img[]\" onclick=\"selectCheckbox('format_img', this.id)\" type=\"checkbox\" value=\"".$image_type."|".$image_config."\" ".$checked." /> <img src=\"".getthemelocation()."img/".$file_info['icon']."\" align=\"absmiddle\" class=\"hcmsIconList\" /> ".strtoupper($image_type)." ".$file_info['type']." ".$image_config."</label><br />\n";
                          
                          $i++;
                        }
                      }
                    }
                  }
                }
                ?>
              </td>
              <td align="left" valign="top" nowrap="nowrap" style="padding-left:10px;">
                <span class="hcmsHeadline"><?php echo getescapedtext ($hcms_lang['document'][$lang]); ?></span><br/>
                <?php 
                if (is_array ($mgmt_docoptions) && sizeof ($mgmt_docoptions) > 0)
                {
                  $print_first = "";
                  $print_next = "";
                  $i = 1;
                  
                  if (!empty ($format_doc) && is_array ($format_doc) && in_array ("original", $format_doc)) $checked = "checked=\"checked\"";
                  else $checked = "";
                                   
                  echo "<label><input id=\"format_doc".$i."\" name=\"format_doc[]\" onclick=\"selectCheckbox('format_doc', this.id)\" type=\"checkbox\" value=\"original\" ".$checked." /> <img src=\"".getthemelocation()."img/file_txt.png\" align=\"absmiddle\" class=\"hcmsIconList\" /> ".getescapedtext ($hcms_lang['original'][$lang])."</label><br />\n";
                  $i++;
                  
                  foreach ($mgmt_docoptions as $ext => $value)
                  {
                    if ($ext != "")
                    {
                      $ext_array = explode (".", trim ($ext, "."));
                      $doc_type = $ext_array[0];
                        
                      $file_info = getfileinfo ($site, "file".$ext, "comp");
                      
                      if (!empty ($format_doc) && is_array ($format_doc) && in_array ($doc_type, $format_doc)) $checked = "checked=\"checked\"";
                      else $checked = "";
                      
                      $temp = "<label><input id=\"format_doc".$i."\" name=\"format_doc[]\" onclick=\"selectCheckbox('format_doc', this.id)\" type=\"checkbox\" value=\"".$doc_type."\" ".$checked." /> <img src=\"".getthemelocation()."img/".$file_info['icon']."\" align=\"absmiddle\" class=\"hcmsIconList\" /> ".$file_info['type']." (".strtoupper($doc_type).")</label><br />\n";
                      
                      if (strtolower ($ext) == ".pdf") $print_first .= $temp;
                      else $print_next .= $temp;
                      
                      $i++;
                    }
                  }
                  
                  echo $print_first.$print_next;
                }
                ?>
              </td>
              <td align="left" valign="top" nowrap="nowrap">
                <span class="hcmsHeadline"><?php echo getescapedtext ($hcms_lang['video'][$lang]); ?></span><br/>
                <?php 
                if (is_array ($mgmt_mediaoptions) && sizeof ($mgmt_mediaoptions) > 0)
                {
                  $i = 1;
                  
                  if (!empty ($format_vid) && is_array ($format_vid) && in_array ("original", $format_vid)) $checked = "checked=\"checked\"";
                  else $checked = "";
                  
                  echo "<label><input id=\"format_vid".$i."\" name=\"format_vid[]\" onclick=\"selectCheckbox('format_vid', this.id)\" type=\"checkbox\" value=\"original\" ".$checked." /> <img src=\"".getthemelocation()."img/file_mpg.png\" align=\"absmiddle\" class=\"hcmsIconList\" /> ".getescapedtext ($hcms_lang['original'][$lang])."</label><br />\n";
                  $i++;
                  
                  if (!empty ($format_vid) && is_array ($format_vid) && in_array ("origthumb", $format_vid)) $checked = "checked=\"checked\"";
                  else $checked = "";
                  
                  echo "<label><input id=\"format_vid".$i."\" name=\"format_vid[]\" onclick=\"selectCheckbox('format_vid', this.id)\" type=\"checkbox\" value=\"origthumb\" ".$checked." /> <img src=\"".getthemelocation()."img/file_mpg.png\" align=\"absmiddle\" class=\"hcmsIconList\" /> ".getescapedtext ($hcms_lang['preview'][$lang])."</label><br />\n";
                  $i++;
                  
                  if (!empty ($format_vid) && is_array ($format_vid) && in_array ("jpg", $format_vid)) $checked = "checked=\"checked\"";
                  else $checked = "";
                  
                  echo "<label><input id=\"format_vid".$i."\" name=\"format_vid[]\" onclick=\"selectCheckbox('format_vid', this.id)\" type=\"checkbox\" value=\"jpg\" ".$checked." /> <img src=\"".getthemelocation()."img/file_image.png\" align=\"absmiddle\" class=\"hcmsIconList\" /> ".getescapedtext ($hcms_lang['images'][$lang])." (JPG)</label><br />\n";
                  $i++;
                  
                  if (!empty ($format_vid) && is_array ($format_vid) && in_array ("png", $format_vid)) $checked = "checked=\"checked\"";
                  else $checked = "";
                  
                  echo "<label><input id=\"format_vid".$i."\" name=\"format_vid[]\" onclick=\"selectCheckbox('format_vid', this.id)\" type=\"checkbox\" value=\"png\" ".$checked." /> <img src=\"".getthemelocation()."img/file_image.png\" align=\"absmiddle\" class=\"hcmsIconList\" /> ".getescapedtext ($hcms_lang['images'][$lang])." (PNG)</label><br />\n";
                  $i++;
                }
                ?>
              </td>
            </tr>
          </table>
        </div>
      
      </div>
      
      <!-- Mail Message -->      
      <div id="LayerMail" style="position:absolute; z-index:5; visibility:visible; left:0px; top:272px; width:100%; height:270px;">
        <div style="padding-left:4px;">
          <table width="100%" border="0" cellpadding="2" cellspacing="0">
            <tr>
              <td colspan="2" height="3" valign="bottom">
                <hr />
              </td>
            </tr>
            <!-- CC, BCC -->
            <tr>
              <td width="120" align="left" valign="top" nowrap="nowrap"><?php echo getescapedtext ($hcms_lang['cc-e-mail'][$lang]); ?> </td>
              <td align="left" valign="top">
                <input type="text" name="email_cc" style="width:350px;" value="<?php echo $email_cc; ?>" />
              </td>
            </tr>
            <tr>
              <td align="left" valign="top" nowrap="nowrap"><?php echo getescapedtext ($hcms_lang['bcc-e-mail'][$lang]); ?> </td>
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
              <td align="left" valign="top" nowrap="nowrap"><?php echo getescapedtext ($hcms_lang['subject'][$lang]); ?> </td>
              <td align="left" valign="top">
                <input type="text" id="mail_title" name="mail_title" style="width:350px;" value="<?php echo $mail_title; ?>" />
              </td>
            </tr>
            <tr>
              <td align="left" valign="top" nowrap="nowrap"><?php echo getescapedtext ($hcms_lang['message'][$lang]); ?> </td>
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
              <td align="left" valign="top" nowrap="nowrap"><?php echo getescapedtext ($hcms_lang['send-files-as'][$lang]); ?> </td>
              <td align="left" valign="top">
                <table border="0" cellpadding="0" cellspacing="0">
                  <?php if ($allow_download) { ?>
                  <tr><td nowrap="nowrap"><label><input type="checkbox" name="download_type" id="type_download" onclick="selectLinkType(this.id); initLinkType();" value="download" <?php if ($download_type == "download" || ($download_type == "" && ($mgmt_config['maillink'] == "download" || $mgmt_config['maillink'] == ""))) echo "checked=\"checked\""; ?> />&nbsp;<?php echo getescapedtext ($hcms_lang['download-link'][$lang]); ?></label>&nbsp;</td><td nowrap="nowrap"><div class="hcmsButtonTiny" onClick="$('#menu-Formats').click();"> <img src="<?php echo getthemelocation()."img/button_history_forward.png"; ?>" class="hcmsIconList" /> <?php echo getescapedtext ($hcms_lang['download-formats'][$lang]); ?>&nbsp;</div></td></tr>
                  <?php } ?>
                  <tr><td nowrap="nowrap"><label><input type="checkbox" name="download_type" id="type_access" onclick="selectLinkType(this.id); initLinkType();" value="link" <?php if ($download_type == "link" || ($download_type == "" && $mgmt_config['maillink'] == "access")) echo "checked=\"checked\""; ?> />&nbsp;<?php echo getescapedtext ($hcms_lang['access-link'][$lang]); ?></label>&nbsp;</td><td nowrap="nowrap"><div class="hcmsButtonTiny" onClick="$('#menu-Formats').click();">  <img src="<?php echo getthemelocation()."img/button_history_forward.png"; ?>" class="hcmsIconList" /> <?php echo getescapedtext ($hcms_lang['download-formats'][$lang]); ?>&nbsp;</div></td></tr>
                  <?php if ($allow_attachment) { ?>
                  <tr><td nowrap="nowrap"><label><input type="checkbox" name="download_type" id="type_attachment" onclick="selectLinkType(this.id); initLinkType();" value="attachment" <?php if ($download_type == "attachment") echo "checked=\"checked\""; ?> />&nbsp;<?php echo getescapedtext ($hcms_lang['attachment'][$lang]); ?></label>&nbsp;</td><td nowrap="nowrap"><div class="hcmsButtonTiny" onClick="$('#menu-Formats').click();">  <img src="<?php echo getthemelocation()."img/button_history_forward.png"; ?>" class="hcmsIconList" /> <?php echo getescapedtext ($hcms_lang['download-formats'][$lang]); ?>&nbsp;</div></td></tr>
                  <?php } ?>
                </table>
              </td>
            </tr>
            <tr>
              <td align="left" valign="top" nowrap="nowrap"><?php echo getescapedtext ($hcms_lang['period-of-validity'][$lang]); ?> </td>
              <td align="left" valign="top">
                <label><input type="checkbox" name="valid_active" id="valid_active" value="yes" onclick="if (this.checked==true) { document.getElementById('valid_days').disabled=false; document.getElementById('valid_hours').disabled=false; } else { document.getElementById('valid_days').disabled=true; document.getElementById('valid_hours').disabled=true; }" />&nbsp;<?php echo getescapedtext ($hcms_lang['valid-for'][$lang]); ?></label>
                <input type="number"  min="0" max="1000" name="valid_days" id="valid_days" value="" style="width:40px;" disabled="disabled" />&nbsp;<?php echo getescapedtext ($hcms_lang['days-and'][$lang]); ?>&nbsp;
                <input type="number"  min="0" max="24" name="valid_hours" id="valid_hours" value="" style="width:40px;" disabled="disabled" />&nbsp;<?php echo getescapedtext ($hcms_lang['hours'][$lang]); ?>
              </td>
            </tr>
            <tr>
              <td align="left" valign="top" nowrap="nowrap"><?php echo getescapedtext ($hcms_lang['meta-data'][$lang]); ?> </td>
              <td align="left" valign="top">
                <label><input type="checkbox" name="include_metadata" value="yes" <?php if ($include_metadata == "yes") echo "checked=\"checked\""; ?>/> 
                <?php echo getescapedtext ($hcms_lang['include-in-message'][$lang]); ?></label>
              </td>
            </tr>
            <?php if (checkrootpermission ('desktoptaskmgmt') && is_file ($mgmt_config['abs_path_cms']."task/task_list.php")) { ?>
            <tr>
              <td align="left" valign="top" nowrap="nowrap"><?php echo getescapedtext ($hcms_lang['create-new-task'][$lang]); ?> </td>
              <td align="left" valign="top">
                <label><input type="checkbox" name="create_task" value="yes" onclick="selectLinkType('type_access'); initLinkType(); enablefield('startdate', this.checked); enablefield('finishdate', this.checked);" <?php if ($create_task == "yes") echo "checked=\"checked\""; ?>/> 
                <?php echo getescapedtext ($hcms_lang['for-the-recipients-with-priority'][$lang]); ?></label>
                <select name="priority">
                  <option value="low" <?php if ($priority == "low" || $priority == "") echo "selected=\"selected\""; ?>><?php echo getescapedtext ($hcms_lang['low'][$lang]); ?></option>
                  <option value="medium" <?php if ($priority == "medium") echo "selected=\"selected\""; ?>><?php echo getescapedtext ($hcms_lang['medium'][$lang]); ?></option>
                  <option value="high" <?php if ($priority == "high") echo "selected=\"selected\""; ?>><?php echo getescapedtext ($hcms_lang['high'][$lang]); ?></option>
                </select>
                <div style="margin:2px 0px 2px 0px;">
                  <div style="float:left;">
                    &nbsp;&nbsp;&nbsp;&nbsp;<?php echo getescapedtext ($hcms_lang['start'][$lang]); ?> <input type="text" name="startdate" id="startdate" readonly="readonly" style="width:80px;" value="<?php echo $startdate; ?>" /><img name="datepicker1" src="<?php echo getthemelocation(); ?>img/button_datepicker.png" onclick="show_cal(this, 'startdate', '%Y-%m-%d', false);" class="hcmsButtonTiny hcmsButtonSizeSquare" align="absmiddle" alt="<?php echo getescapedtext ($hcms_lang['select-date'][$lang]); ?>" title="<?php echo getescapedtext ($hcms_lang['select-date'][$lang]); ?>" align="top" />
                  </div>
                  <div style="float:left;">
                    &nbsp;&nbsp;<?php echo getescapedtext ($hcms_lang['end'][$lang]); ?> <input type="text" name="finishdate" id="finishdate" readonly="readonly" style="width:80px;" value="<?php echo $finishdate; ?>" /><img name="datepicker2" src="<?php echo getthemelocation(); ?>img/button_datepicker.png" onclick="show_cal(this, 'finishdate', '%Y-%m-%d', false);" class="hcmsButtonTiny hcmsButtonSizeSquare" align="absmiddle" alt="<?php echo getescapedtext ($hcms_lang['select-date'][$lang]); ?>" title="<?php echo getescapedtext ($hcms_lang['select-date'][$lang]); ?>" align="top" />
                  </div>
                </div>
              </td>
            </tr>
            <?php } ?>        
          <?php } ?>
            <tr>
              <td colspan="2" height="3" valign="bottom">
                <hr />
              </td>
            </tr>
            <tr>
              <td align="left" valign="top" nowrap="nowrap"><?php echo getescapedtext ($hcms_lang['send-e-mail'][$lang]); ?> </td>
              <td align="left" valign="top">
                <?php if (is_file ($mgmt_config['abs_path_cms']."task/task_list.php")) { ?><label><input type="checkbox" name="ondate" id="ondate" value="yes" onclick="enablefield('maildate', this.checked);" <?php if ($ondate == "yes") echo "checked=\"checked\""; ?>/> <?php echo getescapedtext ($hcms_lang['on-date'][$lang]); ?></label> <input type="text" name="maildate" id="maildate" readonly="readonly" style="width:120px;" value="<?php echo $maildate; ?>" /><img name="datepicker3" src="<?php echo getthemelocation(); ?>img/button_datepicker.png" onclick="show_cal(this, 'maildate', '%Y-%m-%d %H:%i', true);" class="hcmsButtonTiny hcmsButtonSizeSquare" align="absmiddle" alt="<?php echo getescapedtext ($hcms_lang['select-date'][$lang]); ?>" title="<?php echo getescapedtext ($hcms_lang['select-date'][$lang]); ?>" align="top" /><?php } ?>
                <img name="ButtonSubmit" src="<?php echo getthemelocation(); ?>img/button_ok.png" onClick="if (checkForm()) document.forms['mailForm'].submit();" onMouseOver="hcms_swapImage('ButtonSubmit','','<?php echo getthemelocation(); ?>img/button_ok_over.png',1)" onMouseOut="hcms_swapImgRestore()"  class="hcmsButtonTinyBlank hcmsButtonSizeSquare" align="absmiddle" title="OK" alt="OK" />
              </td>
            </tr>
          </table>
        </div>
      </div>
      
    </form>
    
  </body>
</html>