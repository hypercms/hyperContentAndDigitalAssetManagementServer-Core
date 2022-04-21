<?php
/*
 * This file is part of
 * hyper Content & Digital Management Server - http://www.hypercms.com
 * Copyright (c) by hyper CMS Content Management Solutions GmbH
 *
 * You should have received a copy of the license (license.txt) along with hyper Content & Digital Management Server
 */
 
// include service
include ("service/sendmail.php");

// get all users (only for the same publication)
if (sizeof ($memory_site) <= 1 && !empty ($memory_site[0])) getallusers ($memory_site[0]);

// definitions for fields
if (!empty ($is_mobile))
{
  $css_width_field = "310px";
  $css_width_selectbox = "320px";
}
else
{
  $css_width_field = "450px";
  $css_width_selectbox = "460px";
}

// security token
$token_new = createtoken ($user);
?>
<!DOCTYPE html>
<html>
<head>
  <title>hyperCMS</title>
  <meta charset="<?php echo getcodepage ($lang); ?>" />
  <meta name="viewport" content="width=device-width, initial-scale=0.62, maximum-scale=1.0, user-scalable=1" />
  <link rel="stylesheet" href="<?php echo getthemelocation(); ?>css/main.css?v=<?php echo getbuildnumber(); ?>" />
  <link rel="stylesheet" href="<?php echo getthemelocation()."css/".($is_mobile ? "mobile.css" : "desktop.css"); ?>?v=<?php echo getbuildnumber(); ?>" />
  <script type="text/javascript" src="javascript/main.min.js?v=<?php echo getbuildnumber(); ?>"></script>

  <!-- Jquery and Jquery UI Autocomplete -->
  <script src="javascript/jquery/jquery-3.5.1.min.js" type="text/javascript"></script>
  <script src="javascript/jquery-ui/jquery-ui-1.12.1.min.js" type="text/javascript"></script>
  <link rel="stylesheet" href="javascript/jquery-ui/jquery-ui-1.12.1.css">

  <link rel="stylesheet" type="text/css" href="javascript/rich_calendar/rich_calendar.css" />
  <script type="text/javascript" src="javascript/rich_calendar/rich_calendar.min.js"></script>
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

  function initLinkType()
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
    
    if (document.getElementById("email_title").value == "")
    {
      alert (hcms_entity_decode("<?php echo getescapedtext ($hcms_lang['please-define-a-mail-subject'][$lang]); ?>"));
      $("input#email_title").focus();
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
    
    if (document.getElementById("email_ondate") && document.getElementById("action"))       
    {
      if (document.getElementById("email_ondate").checked == true && document.getElementById("email_date").value != "") document.getElementById("action").value = "savequeue";
      else document.getElementById("action").value = "sendmail";
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
  
  function switchSelector (id)
  {
    // uses visibilty
    var selector = document.getElementById(id);
    
    if (selector)
    {
      if (selector.style.visibility == 'hidden')
      {
        selector.style.visibility = 'visible';
        selector.style.height = '';
        selector.style.position = 'static'; 
      }
      else
      {
        selector.style.visibility = 'hidden';
        selector.style.height = '0px';
        selector.style.position = 'absolute'; 
      }
  
      return true;
    }
    else return false;
  }
  
  function openPopup ()
  {
    var width = Math.max(document.documentElement.clientWidth, window.innerWidth || 0)
    var height = Math.max(document.documentElement.clientHeight, window.innerHeight || 0)

    hcms_showFormLayer('objectviewLayer',0);
  }
  
  function closePopup ()
  {
    hcms_hideFormLayer('objectviewLayer');
  }

  function addAsRecipient()
  {
    if (document.getElementById('ui-id-1'))
    {
      var ul = document.getElementById('ui-id-1');
      var ul_li_id = ul.firstChild;
      var ul_li_div_id = ul_li_id.firstChild.id;
      
      if (ul_li_div_id != "" && document.getElementById(ul_li_div_id)) 
      {
        document.getElementById(ul_li_div_id).click();
        document.getElementById('addasrecipient').style.display='none';
      }
    }
  }
  
  $(document).ready(function()
  {
    // initialize fields
    if (document.getElementById('email_ondate')) enablefield('email_date', document.getElementById('email_ondate').checked);

    <?php
    // prevent reseting the checked boxes to default values
    if (empty ($format_img) && empty ($format_doc) && empty ($format_vid)) echo "initLinkType();";
    ?>
    
    hcms_setViewportScale();
    
    <?php 
    $temp_array = array();
    
    if (is_array ($alluser_array) && sizeof ($alluser_array) > 0)
    {
      foreach ($alluser_array as $temp_id => $temp_user)
      {
        if (array_key_exists ($temp_id, $allemail_array) && !empty ($allemail_array[$temp_id]))
        {
          $temp_realname = (array_key_exists ($temp_id, $allrealname_array) && !empty ($allrealname_array[$temp_id])) ? $allrealname_array[$temp_id] : $temp_user;
          $temp_array[] = "{ loginname: \"".$temp_user."\", id: \"".$temp_id."\", username:\"".$temp_realname."\", email:\"".$allemail_array[$temp_id]."\", label: \"".$temp_realname." (".$allemail_array[$temp_id].")\" }"; 
        }
      }
    }
    ?>
    var userlist = [<?php echo implode (",\n", $temp_array); ?>];
    <?php
    unset ($temp_array);

    // id for the special element
    $idspecial = "-99999999";
    ?>

    var noneFound = { id: "<?php echo $idspecial; ?>", label: hcms_entity_decode("<?php echo getescapedtext ($hcms_lang['add-as-recipient'][$lang]); ?>") };

    $("input#selector").autocomplete(
    { 
        source: function(request, response) {

          var found = $.ui.autocomplete.filter(userlist, request.term);

          if (found.length)
          {
            response(found);
            document.getElementById('addasrecipient').style.display = "none";
          }
          else
          {
            response([noneFound]);
            document.getElementById('addasrecipient').style.display = "inline";
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
                $("div#emails").append("<div id=\""+mainname+"\" style=\"display:block; width:100%; height:16px;\">"+input+divtext+img+"</div>");
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
            var fieldname = ui.item.loginname.replace(/([\.\-\@])/g, "_");

            var mainname = 'main_'+fieldname;
            var delname = 'delete_'+fieldname;
            var inputid = 'user_login_'+fieldname;
            var divtextid = 'divtext_'+fieldname;
            
            // only add persons who aren't on the list already
            if (!$('#'+mainname).length)
            {
              var pre = "";
              var img = '<div><img onclick="remove_element(\''+mainname+'\')" onmouseout="hcms_swapImgRestore();" onmouseover="hcms_swapImage(\''+delname+'\', \'\', \'<?php echo getthemelocation(); ?>img/button_close_over.png\',1);" title="<?php echo getescapedtext ($hcms_lang['delete-recipient'][$lang]); ?>" alt="<?php echo getescapedtext ($hcms_lang['delete-recipient'][$lang]); ?>" src="<?php echo getthemelocation(); ?>img/button_close.png" name="'+delname+'" style="width:16px; height:16px; border:0; float:right; display:inline; cursor:pointer;"></div>';
              var input = '<input type="hidden" name="user_login[]" id="'+inputid+'" value="'+ui.item.loginname+'"/>';
              var divtext =  '<div id="'+divtextid+'" style="float:left" title="'+ui.item.email+'">'+ui.item.username+'&nbsp;</div>';
              $("div#emails").append("<div id=\""+mainname+"\" style=\"display:block; width:100%; height:16px;\">"+input+divtext+img+"</div>");
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
      
      if (elem.autocomplete("widget").is(":hidden"))
      {
        elem.autocomplete("search" , elem.value);
      }
    });

    // call click function for the first tap
    $("#menu-Recipient").click();
    
    $("#mailForm").keypress(function (key) 
    {
      if (key.keyCode === 13 && key.target.id != 'email_body') return false;
      else return true;
    }
    );
  });
  </script>
</head>

<body class="hcmsWorkplaceGeneric" style="overflow:auto" onload="<?php echo $add_onload; ?>">

  <!-- preview (do not used nested fixed positioned div-layers due to MS IE and Edge issue) --> 
  <div id="objectviewLayer" style="display:none;">
    <div style="position:fixed; right:5px; top:45px; z-index:8001;">
      <img name="hcms_mediaClose" src="<?php echo getthemelocation(); ?>img/button_close.png" class="hcmsButtonTinyBlank hcmsButtonSizeSquare" alt="<?php echo getescapedtext ($hcms_lang['close'][$lang]); ?>" title="<?php echo getescapedtext ($hcms_lang['close'][$lang]); ?>" onMouseOut="hcms_swapImgRestore();" onMouseOver="hcms_swapImage('hcms_mediaClose','','<?php echo getthemelocation(); ?>img/button_close_over.png',1);" onClick="closePopup();" />
    </div>
    <div id="objectview" class="hcmsWorkplaceExplorer" style="overflow:auto; position:fixed; margin:0; padding:0; left:0; top:40px; right:0; bottom:0; z-index:8000;">
    <?php
    // transform single object to multi object
    if (empty ($multiobject_array) && $location_esc != "" && $page != "")
    {
      $multiobject_array = array();
      
      if ($folder != "") $multiobject_array[0] = $location_esc.$folder."/".$page;
      else $multiobject_array[0] = $location_esc.$page;
    }
    
    echo showgallery ($multiobject_array, 140, true, $user);
    ?>
    </div>
  </div>

  <!-- top bar -->
  <?php
  if (isset ($multiobject_array) && is_array ($multiobject_array)) $title = sizeof ($multiobject_array)." ".$hcms_lang['objects-selected'][$lang];
  else $title = $pagename;
              
  echo showtopbar ($hcms_lang['message'][$lang].": <a href=\"#\" onclick=\"openPopup()\">".$title."</a>", $lang);
  ?>
  
  <!-- message -->
  <?php
  if (isset ($multiobject_array) && is_array ($multiobject_array) && sizeof ($multiobject_array) > 500) echo showmessage ($hcms_lang['storage-limit-exceeded'][$lang]." (max. 500)", 540, 200, $lang, "position:fixed; left:10px; top:55px;");
  
  if (!empty ($mail_error) || !empty ($mail_success) || !empty ($general_error))
  {
    $show = "<div style=\"width:100%; max-height:190px; z-index:10; overflow:auto;\">\n";
          
    // success message
    if (!empty ($mail_success) && is_array ($mail_success))
    {
      $mail_success = array_unique ($mail_success);
      
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
      $show .= "<br /><strong>Links</strong>";
      
      foreach ($mail_links as $link)
      {
        $show .= "<br />".$link."\n";
      }
    }

    $show .= "</div>";
          
    echo showmessage ($show, 540, 200, $lang, "position:fixed; left:10px; top:55px;");
  }
  ?>
  
  <!-- mail form -->
  <div style="position:absolute; left:0px; top:5px; width:100%;">
    <form id="mailForm" name="mailForm" action="" method="post" autocomplete="off">
      <input type="hidden" name="site" value="<?php echo $site; ?>" />
      <input type="hidden" name="cat" value="<?php echo $cat; ?>" />
      <input type="hidden" name="location" value="<?php echo $location_esc; ?>" />  
      <input type="hidden" name="folder" value="<?php echo $folder; ?>" /> 
      <input type="hidden" name="page" value="<?php echo $page; ?>" />
      <input type="hidden" name="pagename" value="<?php echo $pagename; ?>" />
      <input type="hidden" name="multiobject" value="<?php echo $multiobject; ?>" />
      <input type="hidden" name="token" value="<?php echo $token_new; ?>" />
      <input type="hidden" name="action" id="action" value="sendmail" />
      <!-- mailfile need to be empty in order to save new message data -->
      <input type="hidden" name="mailfile" value="" />
      
      <div id="LayerMenu" class="hcmsTabContainer" style="position:absolute; z-index:10; left:0px; top:40px; min-width:380px;">
        <div id="tab1" class="hcmsTabActive">
          <a id="menu-Recipient" href="#" onClick="hcms_elementbyIdStyle('tab1','hcmsTabActive'); hcms_elementbyIdStyle('tab2','hcmsTabPassive'); hcms_elementbyIdStyle('tab3','hcmsTabPassive'); hcms_elementbyIdStyle('tab4','hcmsTabPassive'); showHideLayers('LayerRecipient','show','LayerGroup','hide','LayerSettings','hide'); close_selector();" title="<?php echo getescapedtext ($hcms_lang['recipients'][$lang]); ?>"><?php echo getescapedtext ($hcms_lang['recipients'][$lang]); ?></a>
        </div>
        <div id="tab2" class="hcmsTabPassive">
          <a id="menu-Group" href="#" onClick="hcms_elementbyIdStyle('tab1','hcmsTabPassive'); hcms_elementbyIdStyle('tab2','hcmsTabActive'); hcms_elementbyIdStyle('tab3','hcmsTabPassive'); hcms_elementbyIdStyle('tab4','hcmsTabPassive'); showHideLayers('LayerRecipient','hide','LayerGroup','show','LayerSettings','hide'); close_selector();" title="<?php echo getescapedtext ($hcms_lang['user-group'][$lang]); ?>"><?php echo getescapedtext ($hcms_lang['user-group'][$lang]); ?></a>
        </div>
        <div id="tab3" class="hcmsTabPassive">
          <a id="menu-Settings" href="#" onClick="hcms_elementbyIdStyle('tab1','hcmsTabPassive'); hcms_elementbyIdStyle('tab2','hcmsTabPassive'); hcms_elementbyIdStyle('tab3','hcmsTabActive'); hcms_elementbyIdStyle('tab4','hcmsTabPassive'); showHideLayers('LayerRecipient','hide','LayerGroup','hide','LayerSettings','show'); close_selector();" title="<?php echo getescapedtext ($hcms_lang['settings'][$lang]); ?>"><?php echo getescapedtext ($hcms_lang['settings'][$lang]); ?><span id="attention_settings" style="color:red; font-weight:bold; visibility:hidden;">!</span></a>
        </div>
      </div>
      
      <!-- Tabs for recipients, groups and settings --> 
      <div id="Tabs" style="position:absolute; z-index:10; visibility:visible; left:0px; top:70px; max-width:380px; height:200px;">
        
        <div id="LayerRecipient" style="padding-left:6px;">
          <table class="hcmsTableNarrow">
            <tr>
              <td class="hcmsHeadline"><?php echo getescapedtext ($hcms_lang['send-e-mail-to'][$lang]); ?> </td>
            </tr>
            <tr>
              <td id="selectbox">
                <input id="selector" name="selector" type="search" value="" maxlength="500" style="width:<?php echo $css_width_field; ?>;" autocomplete="false" />
                <img id="addasrecipient" src="<?php echo getthemelocation("day"); ?>img/button_user_new.png" onclick="addAsRecipient();" style="display:none; cursor:pointer; width:22px; height:22px; padding:0; margin-left:-32px;" title="<?php echo getescapedtext ($hcms_lang['add-as-recipient'][$lang]); ?>" alt="<?php echo getescapedtext ($hcms_lang['add-as-recipient'][$lang]); ?>" />
              </td>
            <tr>
              <td class="hcmsHeadline">
                <?php echo getescapedtext ($hcms_lang['recipients'][$lang]); ?> 
              </td>
            </tr>
            <tr>
              <td>
                <div style="overflow:auto; max-height:120px;" id="emails">
                <?php
                if (!empty ($user_login) && is_array ($user_login))
                {
                  foreach ($user_login as $temp_user)
                  {
                    // find user_id in alluser_array
                    if (!empty ($alluser_array) && is_array ($alluser_array)) $temp_id = array_search ($temp_user, $alluser_array);
                    else $temp_id = false;

                    if ($temp_id && !empty ($allrealname_array[$temp_id])) $temp_realname = $allrealname_array[$temp_id];
                    else $temp_realname = $temp_user;
                    
                    if ($temp_id && !empty ($allemail_array[$temp_id])) $temp_email = $allemail_array[$temp_id];
                    else $temp_email = "";
                    
                    echo "
                    <div id=\"main_".$temp_user."\" style=\"display:block; width:100%; height:16px;\"><input type=\"hidden\" name=\"user_login[]\" id=\"user_login_".$temp_user."\" value=\"".$temp_user."\"/><div id=\"divtext_".$temp_user."\" style=\"float:left\" title=\"".$temp_email."\">".$temp_realname."&nbsp;</div><div><img onclick=\"remove_element('main_".$temp_user."');\" onmouseout=\"hcms_swapImgRestore();\" onmouseover=\"hcms_swapImage('delete_".$temp_user."', '', '".getthemelocation()."img/button_close_over.png', 1);\" title=\"".getescapedtext ($hcms_lang['delete-recipient'][$lang])."\" alt=\"".getescapedtext ($hcms_lang['delete-recipient'][$lang])."\" src=\"".getthemelocation()."img/button_close.png\" name=\"delete_".$temp_user."\" style=\"width:16px; height:16px; border:0; float:right; display:inline; cursor:pointer;\"></div></div>";
                  } 
                }
                ?>
                </div>
              </td>
            </tr>
          </table>
        </div>
        
        <div id="LayerGroup" style="padding-left:6px;">
          <table class="hcmsTableNarrow">
            <tr>
              <td>
                <span class="hcmsHeadline"><?php echo getescapedtext ($hcms_lang['attention'][$lang]); ?> </span><br/>
                <?php echo getescapedtext ($hcms_lang['the-message-will-be-sent-to-all-members-of-the-selected-group'][$lang]); ?>
              </td>
            </tr>
            <tr>
              <td class="hcmsHeadline">
                <?php echo getescapedtext ($hcms_lang['user-group'][$lang]); ?> 
              </td>
            </tr>
            <tr>
              <td>
                <select name="group_login" id="group_login" style="width:<?php echo $css_width_selectbox; ?>;">
                  <option value=""><?php echo getescapedtext ($hcms_lang['select'][$lang]); ?></option>
                  <?php 
                  if (!empty ($allgroup_array) && sizeof ($allgroup_array) > 0)
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
        
        <div id="LayerSettings" style="padding-left:6px;">
          <table class="hcmsTableNarrow">
            <tr>
              <td>
                <span class="hcmsHeadline"><?php echo getescapedtext ($hcms_lang['attention'][$lang]); ?> </span><br/>
                <?php echo getescapedtext ($hcms_lang['these-are-the-settings-which-will-only-be-assigned-to-new-users'][$lang]); ?>
              </td>
            </tr>
            <tr>
              <td class="hcmsHeadline"><?php echo getescapedtext ($hcms_lang['language-setting'][$lang]); ?> </td>
            </tr>
            <tr>
              <td>
                <select name="language" style="width:<?php echo $css_width_selectbox; ?>;">
                <?php
                if (!empty ($mgmt_lang_shortcut) && is_array ($mgmt_lang_shortcut))
                {
                  foreach ($mgmt_lang_shortcut as $lang_opt)
                  {
                    echo "
                    <option value=\"".$lang_opt."\" ".(((!empty ($language) && $language == $lang_opt) || (empty ($language) && $lang == $lang_opt)) ? "selected=\"selected\"" : "").">".$mgmt_lang_name[$lang_opt]."</option>";
                  }
                }
                ?>
                </select>            
              </td>
            </tr>
            <tr>
              <td class="hcmsHeadline"><?php echo getescapedtext ($hcms_lang['member-of-user-group'][$lang]); ?> </td>
            </tr>
            <tr>
              <td>
              <?php
                // if objects from multiple publications are selected (a default group must exist i order to provide access)
                if (empty ($allgroup_array) || sizeof ($allgroup_array) < 1 || sizeof ($memory_site) > 1)
                {
                ?>
                <input type="text" name="user_group_dummy" value="default" class="hcmsTextArea" style="width:<?php echo $css_width_field; ?>;" disabled="disabled" />
                <input type="hidden" name="user_group" value="default" />
                <?php 
                }
                // if user groups exist
                elseif (!empty ($allgroup_array) && sizeof ($allgroup_array) > 0)
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
                  <input type="text" name="user_group_dummy" value="<?php echo $default; ?>" class="hcmsTextArea" style="width:<?php echo $css_width_field; ?>;" disabled="disabled" />
                  <input type="hidden" name="user_group" value="<?php echo $default; ?>" />
                  <?php 
                  }
                  // otherwise a group can be selected
                  else
                  {
                  ?>
                  <select name="user_group" style="width:<?php echo $css_width_selectbox; ?>;">
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

      </div>
     
      <div id="LayerMail" style="position:absolute; z-index:5; visibility:visible; left:0px; top:275px; padding-left:6px;">
      
        <hr/>
        <!-- Mail Message -->  
        <table class="hcmsTableNarrow">
          <tr>
            <td class="hcmsHeadline"><?php echo getescapedtext ($hcms_lang['cc-e-mail'][$lang]); ?> </td>
          </tr>
          <tr>
            <td>
              <input type="text" name="email_cc" style="width:<?php echo $css_width_field; ?>;" value="<?php echo $email_cc; ?>" />
            </td>
          </tr>
          <tr>
            <td class="hcmsHeadline"><?php echo getescapedtext ($hcms_lang['bcc-e-mail'][$lang]); ?> </td>
          </tr>
          <tr>
            <td>
              <input type="text" name="email_bcc" style="width:<?php echo $css_width_field; ?>;" value="<?php echo $email_bcc; ?>" />
            </td>
          </tr>
          <tr>
            <td class="hcmsHeadline"><?php echo getescapedtext ($hcms_lang['subject'][$lang]); ?> </td>
          </tr>
          <tr>
            <td>
              <input type="text" id="email_title" name="email_title" style="width:<?php echo $css_width_field; ?>;" value="<?php echo $email_title; ?>" />
            </td>
          </tr>
          <tr>
            <td class="hcmsHeadline"><?php echo getescapedtext ($hcms_lang['message'][$lang]); ?> </td>
          </tr>
          <tr>
            <td>
              <textarea id="email_body" name="email_body" rows="6" style="width:<?php echo $css_width_field; ?>;"><?php
                                      
              // define message if object will be deleted automatically
              if ($location_esc != "" && $folder != "") $objectpath = $location_esc.$folder."/.folder";
              elseif ($location_esc != "" && $page != "") $objectpath = $location_esc.$page;

              if (!empty ($objectpath))
              {
                $queue = rdbms_getqueueentries ("delete", "", "", "", $objectpath);

                if (is_array ($queue) && !empty ($queue[0]['date']))
                {
                  $message = str_replace ("%date%", substr ($queue[0]['date'], 0, -3), $hcms_lang['the-link-will-be-active-till-date'][$lang]);
                
                  if (substr_count ($email_body, $message) == 0)
                  {                
                    $email_body .= $message."\n";
                  }
                }

                echo $email_body;
              }
              ?></textarea>
            </td>
          </tr>
        </table>
        <hr/>

      <?php if ($page != "" || is_array ($multiobject_array)) { ?>
        <!-- Links -->
        <div style="display:block; margin-bottom:3px;">
          <span class="hcmsHeadline"><?php echo getescapedtext ($hcms_lang['send-files-as'][$lang]); ?></span>
          <img onClick="switchSelector('linkLayer')" class="hcmsButtonTiny" src="<?php echo getthemelocation(); ?>img/button_plusminus.png" style="float:right; width:31px; height:16px;" alt="+/-" title="+/-" />
        </div>        
        <div id="linkLayer" style="clear:right;">
          <table class="hcmsTableNarrow">
            <tr>
              <td>
                <table class="hcmsTableNarrow">
                  <?php if ($allow_download) { ?>
                  <tr><td style="white-space:nowrap;"><label><input type="checkbox" name="download_type" id="type_download" onclick="selectLinkType(this.id); initLinkType();" value="download" <?php if ($download_type == "download" || ($download_type == "" && ($mgmt_config['maillink'] == "download" || $mgmt_config['maillink'] == ""))) echo "checked=\"checked\""; ?> />&nbsp;<?php echo getescapedtext ($hcms_lang['download-link'][$lang]); ?></label>&nbsp;</td><td style="white-space:nowrap;"><div class="hcmsButtonTiny" onClick="switchSelector('formatsLayer')"> <img src="<?php echo getthemelocation()."img/button_history_forward.png"; ?>" class="hcmsIconList" /> <?php echo getescapedtext ($hcms_lang['download-formats'][$lang]); ?>&nbsp;</div></td></tr>
                  <?php } ?>
                  <tr><td style="white-space:nowrap;"><label><input type="checkbox" name="download_type" id="type_access" onclick="selectLinkType(this.id); initLinkType();" value="link" <?php if ($download_type == "link" || ($download_type == "" && $mgmt_config['maillink'] == "access")) echo "checked=\"checked\""; ?> />&nbsp;<?php echo getescapedtext ($hcms_lang['access-link'][$lang]); ?></label>&nbsp;</td><td style="white-space:nowrap;"><div class="hcmsButtonTiny" onClick="switchSelector('formatsLayer');">  <img src="<?php echo getthemelocation()."img/button_history_forward.png"; ?>" class="hcmsIconList" /> <?php echo getescapedtext ($hcms_lang['download-formats'][$lang]); ?>&nbsp;</div></td></tr>
                  <?php if ($allow_attachment) { ?>
                  <tr><td style="white-space:nowrap;"><label><input type="checkbox" name="download_type" id="type_attachment" onclick="selectLinkType(this.id); initLinkType();" value="attachment" <?php if ($download_type == "attachment") echo "checked=\"checked\""; ?> />&nbsp;<?php echo getescapedtext ($hcms_lang['attachment'][$lang]); ?></label>&nbsp;</td><td style="white-space:nowrap;"><div class="hcmsButtonTiny" onClick="switchSelector('formatsLayer')">  <img src="<?php echo getthemelocation()."img/button_history_forward.png"; ?>" class="hcmsIconList" /> <?php echo getescapedtext ($hcms_lang['download-formats'][$lang]); ?>&nbsp;</div></td></tr>
                  <?php } ?>
                </table>
              </td>
            </tr>
          </table>
        </div>
        <hr/>

        <!-- Formats -->
        <div style="display:block; margin-bottom:3px;">
          <span class="hcmsHeadline"><?php echo getescapedtext ($hcms_lang['download-formats'][$lang]); ?></span>
          <img onClick="switchSelector('formatsLayer')" class="hcmsButtonTiny" src="<?php echo getthemelocation(); ?>img/button_plusminus.png" style="float:right; width:31px; height:16px;" alt="+/-" title="+/-" />
        </div>        
        <div id="formatsLayer" style="position:absolute; visibility:hidden; height:0px; max-width:<?php echo $css_width_selectbox; ?>; clear:right; scrolling:auto;">
          <div style="padding:0px 6px 4px 0px; float:left;">
            <span class="hcmsHeadline"><?php echo getescapedtext ($hcms_lang['image'][$lang]); ?></span><br/>
            <?php 
            if (is_array ($mgmt_imageoptions) && sizeof ($mgmt_imageoptions) > 0)
            {
              $i = 1;
              
              if (!empty ($format_img) && is_array ($format_img) && in_array ("original", $format_img)) $checked = "checked=\"checked\"";
              else $checked = "";
              
              echo "<label><input id=\"format_img".$i."\" name=\"format_img[]\" onclick=\"selectCheckbox('format_img', this.id)\" type=\"checkbox\" value=\"original\" ".$checked." /> <img src=\"".getthemelocation()."img/file_image.png\" class=\"hcmsIconList\" /> ".getescapedtext ($hcms_lang['original'][$lang])."</label><br />\n";
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

                      echo "<label><input id=\"format_img".$i."\" name=\"format_img[]\" onclick=\"selectCheckbox('format_img', this.id)\" type=\"checkbox\" value=\"".$image_type."|".$image_config."\" ".$checked." /> <img src=\"".getthemelocation()."img/".$file_info['icon']."\" class=\"hcmsIconList\" /> ".strtoupper($image_type)." ".$file_info['type']." ".$image_config."</label><br />\n";

                      $i++;
                    }
                  }
                }
              }
            }
            ?>
          </div>
          <div style="padding:0px 6px 4px 0px; float:left;">
            <span class="hcmsHeadline"><?php echo getescapedtext ($hcms_lang['video'][$lang]); ?></span><br/>
            <?php 
            if (is_array ($mgmt_mediaoptions) && sizeof ($mgmt_mediaoptions) > 0)
            {
              $i = 1;

              if (!empty ($format_vid) && is_array ($format_vid) && in_array ("original", $format_vid)) $checked = "checked=\"checked\"";
              else $checked = "";

              echo "
              <label><input id=\"format_vid".$i."\" name=\"format_vid[]\" onclick=\"selectCheckbox('format_vid', this.id)\" type=\"checkbox\" value=\"original\" ".$checked." /> <img src=\"".getthemelocation()."img/file_mpg.png\" class=\"hcmsIconList\" /> ".getescapedtext ($hcms_lang['original'][$lang])."</label><br />";
              $i++;

              if (!empty ($format_vid) && is_array ($format_vid) && in_array ("origthumb", $format_vid)) $checked = "checked=\"checked\"";
              else $checked = "";

              echo "
              <label><input id=\"format_vid".$i."\" name=\"format_vid[]\" onclick=\"selectCheckbox('format_vid', this.id)\" type=\"checkbox\" value=\"origthumb\" ".$checked." /> <img src=\"".getthemelocation()."img/file_mpg.png\" class=\"hcmsIconList\" /> ".getescapedtext ($hcms_lang['preview'][$lang])."</label><br />";
              $i++;

              if (!empty ($format_vid) && is_array ($format_vid) && in_array ("jpg", $format_vid)) $checked = "checked=\"checked\"";
              else $checked = "";

              echo "
              <label><input id=\"format_vid".$i."\" name=\"format_vid[]\" onclick=\"selectCheckbox('format_vid', this.id)\" type=\"checkbox\" value=\"jpg\" ".$checked." /> <img src=\"".getthemelocation()."img/file_image.png\" class=\"hcmsIconList\" /> ".getescapedtext ($hcms_lang['images'][$lang])." (JPG)</label><br />";
              $i++;

              if (!empty ($format_vid) && is_array ($format_vid) && in_array ("png", $format_vid)) $checked = "checked=\"checked\"";
              else $checked = "";

              echo "
              <label><input id=\"format_vid".$i."\" name=\"format_vid[]\" onclick=\"selectCheckbox('format_vid', this.id)\" type=\"checkbox\" value=\"png\" ".$checked." /> <img src=\"".getthemelocation()."img/file_image.png\" class=\"hcmsIconList\" /> ".getescapedtext ($hcms_lang['images'][$lang])." (PNG)</label><br />";
              $i++;
            }
            ?>
          </div>
          <div style="padding:0px 6px 4px 0px; float:left;">
            <span class="hcmsHeadline"><?php echo getescapedtext ($hcms_lang['document'][$lang]); ?></span><br/>
            <?php 
            if (is_array ($mgmt_docoptions) && sizeof ($mgmt_docoptions) > 0)
            {
              $print_first = "";
              $print_next = "";
              $i = 1;

              if (!empty ($format_doc) && is_array ($format_doc) && in_array ("original", $format_doc)) $checked = "checked=\"checked\"";
              else $checked = "";
                
              echo "<label><input id=\"format_doc".$i."\" name=\"format_doc[]\" onclick=\"selectCheckbox('format_doc', this.id)\" type=\"checkbox\" value=\"original\" ".$checked." /> <img src=\"".getthemelocation()."img/file_txt.png\" class=\"hcmsIconList\" /> ".getescapedtext ($hcms_lang['original'][$lang])."</label><br />\n";
              $i++;

              foreach ($mgmt_docoptions as $ext => $value)
              {
                if ($ext != "" && !is_image ("_".$ext))
                {
                  $ext_array = explode (".", trim ($ext, "."));
                  $doc_type = $ext_array[0];
 
                  $file_info = getfileinfo ($site, "file".$ext, "comp");

                  if (!empty ($format_doc) && is_array ($format_doc) && in_array ($doc_type, $format_doc)) $checked = "checked=\"checked\"";
                  else $checked = "";

                  $temp = "<label><input id=\"format_doc".$i."\" name=\"format_doc[]\" onclick=\"selectCheckbox('format_doc', this.id)\" type=\"checkbox\" value=\"".$doc_type."\" ".$checked." /> <img src=\"".getthemelocation()."img/".$file_info['icon']."\" class=\"hcmsIconList\" /> ".$file_info['type']." (".strtoupper($doc_type).")</label><br />\n";

                  if (strtolower ($ext) == ".pdf") $print_first .= $temp;
                  else $print_next .= $temp;
                  
                  $i++;
                }
              }

              echo $print_first.$print_next;
            }
            ?>
          </div> 
          <div style="clear:both;"></div>
        </div>
        <hr/>

        <!-- Validity -->
        <div style="display:block; margin-bottom:3px;">
          <span class="hcmsHeadline"><?php echo getescapedtext ($hcms_lang['period-of-validity'][$lang]); ?></span>
          <img onClick="switchSelector('validityLayer')" class="hcmsButtonTiny" src="<?php echo getthemelocation(); ?>img/button_plusminus.png" style="float:right; width:31px; height:16px;" alt="+/-" title="+/-" />
        </div>        
        <div id="validityLayer" style="position:absolute; visibility:hidden; height:0px; clear:right;">
          <table class="hcmsTableStandard">
            <tr>
              <td>
                <label><input type="checkbox" name="valid_active" id="valid_active" value="yes" onclick="if (this.checked==true) { document.getElementById('valid_days').disabled=false; document.getElementById('valid_hours').disabled=false; } else { document.getElementById('valid_days').disabled=true; document.getElementById('valid_hours').disabled=true; }" <?php if (!empty ($valid_active)) echo "checked=\"checked\""; ?>/>&nbsp;<?php echo getescapedtext ($hcms_lang['valid-for'][$lang]); ?></label>
                <input type="number" min="0" max="1000" name="valid_days" id="valid_days" value="<?php if ($valid_days > 0) echo $valid_days; ?>" style="width:60px; padding:2px;" disabled="disabled" />&nbsp;<?php echo getescapedtext ($hcms_lang['days-and'][$lang]); ?>&nbsp;
                <input type="number" min="0" max="24" name="valid_hours" id="valid_hours" value="<?php if ($valid_hours > 0) echo $valid_hours; ?>" style="width:60px; padding:2px;" disabled="disabled" />&nbsp;<?php echo getescapedtext ($hcms_lang['hours'][$lang]); ?>
              </td>
            </tr>
          </table>
        </div>
        <hr/>
        
        <?php if (checkrootpermission ('desktoptaskmgmt') && is_file ($mgmt_config['abs_path_cms']."task/task_list.php")) { ?>
        <!-- Tasks -->
        <div style="display:block; margin-bottom:3px;">
          <span class="hcmsHeadline"><?php echo getescapedtext ($hcms_lang['create-new-task'][$lang]); ?></span>
          <img onClick="switchSelector('taskLayer')" class="hcmsButtonTiny" src="<?php echo getthemelocation(); ?>img/button_plusminus.png" style="float:right; width:31px; height:16px;" alt="+/-" title="+/-" />
        </div>        
        <div id="taskLayer" style="position:absolute; visibility:hidden; height:0px; clear:right;">
          <table class="hcmsTableStandard">
            <tr>
              <td>
                <label><input type="checkbox" name="task_create" value="yes" onclick="selectLinkType('type_access'); initLinkType(); enablefield('task_startdate', this.checked); enablefield('task_enddate', this.checked);" <?php if ($task_create == "yes") echo "checked=\"checked\""; ?>/> 
                <?php echo getescapedtext ($hcms_lang['for-the-recipients-with-priority'][$lang]); ?></label>
                <select name="task_priority" style="width:80px; padding:2px;">
                  <option value="low" <?php if ($task_priority == "low" || $task_priority == "") echo "selected=\"selected\""; ?>><?php echo getescapedtext ($hcms_lang['low'][$lang]); ?></option>
                  <option value="medium" <?php if ($task_priority == "medium") echo "selected=\"selected\""; ?>><?php echo getescapedtext ($hcms_lang['medium'][$lang]); ?></option>
                  <option value="high" <?php if ($task_priority == "high") echo "selected=\"selected\""; ?>><?php echo getescapedtext ($hcms_lang['high'][$lang]); ?></option>
                </select>
                <table class="hcmsTableStandard" style="margin:0px 0px 0px 16px;">
                  <tr>
                    <td><?php echo getescapedtext ($hcms_lang['start'][$lang]); ?> </td>
                    <td><input type="text" name="task_startdate" id="task_startdate" readonly="readonly" style="width:90px;" value="<?php echo showdate ($task_startdate, "Y-m-d", "Y-m-d"); ?>" /><img src="<?php echo getthemelocation(); ?>img/button_datepicker.png" onclick="show_cal(this, 'task_startdate', '%Y-%m-%d', false);" class="hcmsButtonTiny hcmsButtonSizeSquare" style="vertical-align:top;" alt="<?php echo getescapedtext ($hcms_lang['select-date'][$lang]); ?>" title="<?php echo getescapedtext ($hcms_lang['select-date'][$lang]); ?>" /></td>
                  </tr>
                  <tr>
                    <td><?php echo getescapedtext ($hcms_lang['end'][$lang]); ?> </td>
                    <td><input type="text" name="task_enddate" id="task_enddate" readonly="readonly" style="width:90px;" value="<?php echo showdate ($task_enddate, "Y-m-d", "Y-m-d"); ?>" /><img src="<?php echo getthemelocation(); ?>img/button_datepicker.png" onclick="show_cal(this, 'task_enddate', '%Y-%m-%d', false);" class="hcmsButtonTiny hcmsButtonSizeSquare" style="vertical-align:top;" alt="<?php echo getescapedtext ($hcms_lang['select-date'][$lang]); ?>" title="<?php echo getescapedtext ($hcms_lang['select-date'][$lang]); ?>" /></td>
                    </td>
                  </tr>
              </table>
            </tr>
          </table>
        </div>
        <hr/> 
        <?php } ?>

      <?php } ?>

        <table class="hcmsTableNarrow">
          <tr>
            <td>
              <?php if (is_file ($mgmt_config['abs_path_cms']."task/task_list.php")) { ?>
              <span class="hcmsHeadline"><?php echo getescapedtext ($hcms_lang['send-e-mail'][$lang]); ?> </span><br/>
              <label><input type="checkbox" name="email_ondate" id="email_ondate" value="yes" onclick="enablefield('email_date', this.checked);" <?php if ($email_ondate == "yes") echo "checked=\"checked\""; ?>/> <?php echo getescapedtext ($hcms_lang['on-date'][$lang]); ?></label> <input type="text" name="email_date" id="email_date" readonly="readonly" style="width:140px;" value="<?php echo showdate ($email_date, "Y-m-d H:i", "Y-m-d H:i"); ?>" /><img id="email_datepicker" src="<?php echo getthemelocation(); ?>img/button_datepicker.png" onclick="show_cal(this, 'email_date', '%Y-%m-%d %H:%i', true); document.getElementById('email_ondate').checked=true; enablefield('email_date', true);" class="hcmsButtonTiny hcmsButtonSizeSquare" alt="<?php echo getescapedtext ($hcms_lang['select-date'][$lang]); ?>" title="<?php echo getescapedtext ($hcms_lang['select-date'][$lang]); ?>" />
              <?php } else { ?>
              <span class="hcmsHeadline"><?php echo getescapedtext ($hcms_lang['send-e-mail'][$lang]); ?> </span>
              <?php } ?>
              <img name="ButtonSubmit" src="<?php echo getthemelocation(); ?>img/button_ok.png" onclick="if (checkForm()) document.forms['mailForm'].submit();" onMouseOver="hcms_swapImage('ButtonSubmit','','<?php echo getthemelocation(); ?>img/button_ok_over.png',1)" onMouseOut="hcms_swapImgRestore()"  class="hcmsButtonTinyBlank hcmsButtonSizeSquare" title="OK" alt="OK" />
            </td>
          </tr>
        </table>

      </div>

    </form>
  </div>

<?php includefooter(); ?>

</body>
</html>