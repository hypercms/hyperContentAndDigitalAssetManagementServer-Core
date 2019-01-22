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
// load formats/file extensions
require_once ("include/format_ext.inc.php");


// input parameters
$location = getrequest_esc ("location", "locationname");

// get publication and category
$site = getpublication ($location);
$cat = getcategory ($site, $location); 

// convert location
$location = deconvertpath ($location, "file");
$location_esc = convertpath ($site, $location, $cat);

// publication management config
if (valid_publicationname ($site)) require ($mgmt_config['abs_path_data']."config/".$site.".conf.php");

// ------------------------------ permission section --------------------------------

// check access permissions
$ownergroup = accesspermission ($site, $location, $cat);
$setlocalpermission = setlocalpermission ($site, $ownergroup, $cat);  

if (!valid_publicationname ($site) || !valid_locationname ($location)) killsession ($user);
// check session of user
checkusersession ($user);

// --------------------------------- logic section ----------------------------------

$searcharea = getlocationname ($site, $location, $cat, "path");

// define default templates for inital loading of advanced search form
if ($cat == "page" && is_file ($mgmt_config['abs_path_template'].$site."/default.page.tpl")) $template = "default.page.tpl";
elseif ($cat == "comp" && is_file ($mgmt_config['abs_path_template'].$site."/default.meta.tpl")) $template = "default.meta.tpl";
else $template = "";
?>
<!DOCTYPE html>
<html>
<head>
<title>hyperCMS</title>
<meta charset="<?php echo getcodepage ($lang); ?>" />

<link rel="stylesheet" href="<?php echo getthemelocation(); ?>css/main.css" />

<script src="javascript/main.js" type="text/javascript"></script>

<!-- Rich calendar -->
<link  rel="stylesheet" type="text/css" href="javascript/rich_calendar/rich_calendar.css" />
<script type="text/javascript" src="javascript/rich_calendar/rich_calendar.js"></script>
<script type="text/javascript" src="javascript/rich_calendar/rc_lang_en.js"></script>
<script type="text/javascript" src="javascript/rich_calendar/rc_lang_de.js"></script>
<script type="text/javascript" src="javascript/rich_calendar/rc_lang_fr.js"></script>
<script type="text/javascript" src="javascript/rich_calendar/rc_lang_pt.js"></script>
<script type="text/javascript" src="javascript/rich_calendar/rc_lang_ru.js"></script>
<script type="text/javascript" src="javascript/rich_calendar/domready.js"></script>

<!-- Jquery and Jquery UI Autocomplete -->
<script src="javascript/jquery/jquery-3.3.1.min.js" type="text/javascript"></script>
<script src="javascript/jquery-ui/jquery-ui-1.12.1.min.js" type="text/javascript"></script>
<link rel="stylesheet" href="javascript/jquery-ui/jquery-ui-1.12.1.css" />

<!-- Google Maps -->
<script src="https://maps.googleapis.com/maps/api/js?v=3&key=<?php echo $mgmt_config['googlemaps_appkey']; ?>"></script>
<script type="text/javascript">
function checkForm(select)
{
  if (select.elements['search_expression'].value == "")
  {
    alert (hcms_entity_decode("<?php echo getescapedtext ($hcms_lang['please-insert-a-search-expression'][$lang]); ?>"));
    select.elements['search_expression'].focus();
    return false;
  }
  
  // load screen
  if (parent.document.getElementById('hcmsLoadScreen')) parent.document.getElementById('hcmsLoadScreen').style.display='inline';
  
  select.submit();
}

function loadForm ()
{
  selectbox = document.forms['searchform_advanced'].elements['template'];
  template = selectbox.options[selectbox.selectedIndex].value;
  
  if (template != "")
  {
    hcms_loadPage('contentLayer',null,'search_form_advanced.php?location=<?php echo url_encode($location_esc); ?>&template=' + template + '&css_display=inline-block');
  }
}

function unsetColors ()
{
  if (document.getElementById('unsetcolors').checked == true)
  {
    var colors = document.getElementsByClassName('hcmsColorKey');
    var i;
    
    for (i = 0; i < colors.length; i++)
    {
      colors[i].checked = false;
    }
  }
}

function setColors ()
{
  document.getElementById('unsetcolors').checked = false;
}

function startSearch (form)
{
  if (eval (document.forms['searchform_'+form]))
  {
    parent.frames['controlFrame'].location = 'loading.php';
    
    // check if all file-types have been checked
    var filetypeLayer = document.getElementById('filetype_'+form);

    if (filetypeLayer && filetypeLayer.style.display != "none")
    {
      var unchecked = false;
      var childs = filetypeLayer.getElementsByTagName('*');
      
      for (var i=0; i<childs.length; i++)
      {
        // found unchecked element
        if (childs[i].tagName == "INPUT" && childs[i].checked == false)
        {
          unchecked = true;
        }
      }
      
      // disable checkboxes for file-type
      if (unchecked == false)
      {
        for (var i=0; i<childs.length; i++)
        {
          if (childs[i].tagName == "INPUT")
          {
            childs[i].disabled = true;
          }
        }
      }
    }
    
    // load screen
    if (parent.document.getElementById('hcmsLoadScreen')) parent.document.getElementById('hcmsLoadScreen').style.display='inline';
    
    // submit form
    document.forms['searchform_'+form].submit();
    
    // enable checkboxes for file-type
    if (filetypeLayer && filetypeLayer.style.display != "none")
    {
      for (var i=0; i<childs.length; i++)
      {
        if (childs[i].tagName == "INPUT")
        {
          childs[i].disabled = false;
        }
      }
    }
  }
  else return false;
}

// Google Maps JavaScript API v3: Map Simple
var map;
var dragging = false;
var rightclick = false;
var rect;
var pos1, pos2;
var latlng1, latlng2;

function initRectangle ()
{
  rect = new google.maps.Rectangle({
    map: map,
    strokeColor: '#359FFC', 
    fillColor: '#65B3FC',
    fillOpacity: 0.15,
    strokeWeight: 0.9,
    clickable: false
  });
}

function initMap ()
{  
  var mapOptions = {
      zoom: 1,
      center: new google.maps.LatLng(0, 0),
      disableDefaultUI: true,
      mapTypeId: google.maps.MapTypeId.ROADMAP
  };

  map = new google.maps.Map(document.getElementById('map'), mapOptions);
  
  <?php if (!$is_mobile) { ?>  
  initRectangle();
  
  document.getElementById('map').onmousedown = function(e) {
    e = e || window.event;
    
    // right mouse click
    if ((e.which && e.which == 3) || (e.button && e.button == 2))
    {
      rightclick = true;
    }
    // left mouse click
    else if ((e.which && (e.which == 0 || e.which == 1)) || (e.button && (e.button == 0 || e.button == 1)))
    {
      map.setOptions({draggable: true});

      if (rect)
      {
        // reset rectangle
        rect.setMap(null);
        initRectangle();
        
        document.forms['searchform_general'].elements['geo_border_sw'].value = '';
        document.forms['searchform_general'].elements['geo_border_ne'].value = '';
      }
    }
  }

  google.maps.event.addListener(map, 'mousedown', function(e) {
    map.setOptions({draggable: false});
    
    // current position on the map
    latlng1 = e.latLng;
    dragging = true;
    pos1 = e.pixel;
  });

  google.maps.event.addListener(map, 'mousemove', function(e) {
    // current position on the map
    latlng2 = e.latLng;

    // display rectangle
    if (dragging && rightclick)
    {
      if (rect === undefined)
      {
        rect = new google.maps.Rectangle({
            map: map
        });
      }
      
      var latLngBounds = new google.maps.LatLngBounds(latlng1, latlng2);
      rect.setBounds(latLngBounds);
    }
  });

  google.maps.event.addListener(map, 'mouseup', function(e) {
    map.setOptions({draggable: true});
    dragging = false;
    rightclick = false;

    if (rect && rect.getBounds() !== undefined)
    {
      var borderSW = rect.getBounds().getSouthWest();
      var borderNE = rect.getBounds().getNorthEast();

      document.forms['searchform_general'].elements['geo_border_sw'].value = borderSW;
      document.forms['searchform_general'].elements['geo_border_ne'].value = borderNE;
    }
  });
  
  <?php } else { ?>
  google.maps.event.addListener(map, 'bounds_changed', function() {
    if (map.getBounds() !== undefined)
    {
      var borderSW = map.getBounds().getSouthWest();
      var borderNE = map.getBounds().getNorthEast();
    
      document.forms['searchform_general'].elements['geo_border_sw'].value = borderSW;
      document.forms['searchform_general'].elements['geo_border_ne'].value = borderNE;
    }
  });
  <?php } ?>
}

// RichCalendar
var cal_obj = null;
var cal_format = null;
var cal_field = null;

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
  cal_obj.user_onautoclose_handler = cal_on_autoclose;
  cal_obj.parse_date(datefield.value, cal_format);
  cal_obj.show_at_element(datefield, 'adj_left-top');
}

// onchange handler
function cal_on_change (cal, object_code)
{
  if (object_code == 'day')
  {
    document.getElementById(cal_field).value = cal.get_formatted_date(cal_format);
    cal.hide();
    cal_obj = null;
  }
}

// onautoclose handler
function cal_on_autoclose (cal)
{
  cal_obj = null;
}

$(document).ready(function()
{
  // search history
  <?php
  $keywords = getsearchhistory ($user);
  ?>
  var available_expressions = [<?php if (is_array ($keywords)) echo implode (",\n", $keywords); ?>];

  $("#search_expression").autocomplete({
    source: available_expressions
  });
  
  $("#image_expression").autocomplete({
    source: available_expressions
  });

  initMap();
});
</script>

</head>
<body class="hcmsWorkplaceGeneric" onload="<?php if ($template != "") echo "hcms_loadPage('contentLayer',null,'search_form_advanced.php?location=".url_encode($location_esc)."&template=".url_encode($template)."&css_display=".url_encode("inline-block"); ?>');">

<!-- top bar -->
<?php echo showtopbar ($hcms_lang['search'][$lang], $lang, $mgmt_config['url_path_cms']."explorer_objectlist.php?site=".url_encode($site)."&cat=".url_encode($cat)."&location=".url_encode($location_esc)); ?>

<!-- search form -->
<div id="frame" style="position:absolute; left:0px; top:42px; width:100%; height:calc(100% - 42px); margin:0; padding:0; overflow:auto;">
  <div id="tabLayer" style="position:absolute; width:500px; height:22px; visibility:visible; z-index:10; left:8px; top:0px;">
    <div id="tab1" class="hcmsTabActive">
      <a href="#" onClick="hcms_ElementbyIdStyle('tab1','hcmsTabActive'); hcms_ElementbyIdStyle('tab2','hcmsTabPassive'); hcms_ElementbyIdStyle('tab3','hcmsTabPassive'); hcms_ElementbyIdStyle('tab4','hcmsTabPassive'); hcms_showHideLayers('searchtab_general','','show','searchtab_advanced','','hide','contentLayer','','hide','searchtab_replace','','hide','searchtab_images','','hide')"><?php echo getescapedtext ($hcms_lang['general'][$lang]); ?></a>
    </div>
    <div id="tab2" class="hcmsTabPassive">
      <a href="#" onClick="hcms_ElementbyIdStyle('tab1','hcmsTabPassive'); hcms_ElementbyIdStyle('tab2','hcmsTabActive'); hcms_ElementbyIdStyle('tab3','hcmsTabPassive'); hcms_ElementbyIdStyle('tab4','hcmsTabPassive'); hcms_showHideLayers('searchtab_general','','hide','searchtab_advanced','','show','contentLayer','','show','searchtab_replace','','hide','searchtab_images','','hide')"><?php echo getescapedtext ($hcms_lang['advanced'][$lang]); ?></a>
    </div>
    <?php if ($setlocalpermission['create'] == 1) { ?>
    <div id="tab3" class="hcmsTabPassive">
      <a href="#" onClick="hcms_ElementbyIdStyle('tab1','hcmsTabPassive'); hcms_ElementbyIdStyle('tab2','hcmsTabPassive'); hcms_ElementbyIdStyle('tab3','hcmsTabActive'); hcms_ElementbyIdStyle('tab4','hcmsTabPassive'); hcms_showHideLayers('searchtab_general','','hide','searchtab_advanced','','hide','contentLayer','','hide','searchtab_replace','','show','searchtab_images','','hide')"><?php echo getescapedtext ($hcms_lang['replace'][$lang]); ?></a>
    </div>
    <?php } ?> 
    <?php if ($cat == "comp") { ?>
    <div id="tab4" class="hcmsTabPassive">
      <a href="#" onClick="hcms_ElementbyIdStyle('tab1','hcmsTabPassive'); hcms_ElementbyIdStyle('tab2','hcmsTabPassive'); hcms_ElementbyIdStyle('tab3','hcmsTabPassive'); hcms_ElementbyIdStyle('tab4','hcmsTabActive'); hcms_showHideLayers('searchtab_general','','hide','searchtab_advanced','','hide','contentLayer','','hide','searchtab_replace','','hide','searchtab_images','','show')"><?php echo getescapedtext ($hcms_lang['media'][$lang]); ?></a>
    </div>
    <?php } ?>
  </div>
  
  <div id="searchtab_general" style="position:absolute; width:520px; height:580px; z-index:1; left:8px; top:22px; visibility:visible;"> 
    <form name="searchform_general" method="post" action="search_objectlist.php">
      <input type="hidden" name="search_dir" value="<?php echo $location_esc; ?>" />
      <?php if ($cat == "page") { ?><input type="hidden" name="search_format[]" value="page" /><?php } ?>
       
      <table class="hcmsTableStandard" style="table-layout:auto; width:100%; border:1px solid #000000; border-collapse:collapse; border-spacing:0;">       
        <tr>
          <td style="width:180px; white-space:nowrap; vertical-align:top; padding-top:10px;"><?php echo getescapedtext ($hcms_lang['search-expression'][$lang]); ?> </td>
          <td style="padding-top:10px;">
            <input type="text" name="search_expression" id="search_expression" style="width:220px;" maxlength="200" />
          </td>
        </tr>
        <tr>
          <td style="white-space:nowrap;"><?php echo getescapedtext ($hcms_lang['search-in-folder'][$lang]); ?> </td>
          <td>
            <input type="text" name="folder" value="<?php echo $searcharea; ?>" style="width:220px;" disabled="disabled" />
          </td>
        </tr>
        <tr>
          <td style="white-space:nowrap; vertical-align:top;"><?php echo getescapedtext ($hcms_lang['search-restriction'][$lang]); ?> </td>
          <td class="hcmsHeadlineTiny" style="vertical-align:top;">
            <label><input type="checkbox" name="search_cat" value="file" /> <?php echo getescapedtext ($hcms_lang['only-object-names'][$lang]); ?></label>
          </td>
        </tr>
        <?php if ($cat == "comp") { ?>
        <tr id="row_searchformat">
          <td style="vertical-align:top;"><?php echo getescapedtext ($hcms_lang['file-type'][$lang]); ?> </td>
          <td id="filetype_general" style="vertical-align:top;">
            <label><input type="checkbox" name="search_format[]" value="comp" checked="checked" />&nbsp;<?php echo getescapedtext ($hcms_lang['component'][$lang]); ?></label><br />
            <label><input type="checkbox" name="search_format[]" value="image" checked="checked" />&nbsp;<?php echo getescapedtext ($hcms_lang['image'][$lang]); ?></label><br />
            <label><input type="checkbox" name="search_format[]" value="document" checked="checked" />&nbsp;<?php echo getescapedtext ($hcms_lang['document'][$lang]); ?></label><br />
            <label><input type="checkbox" name="search_format[]" value="video" checked="checked" />&nbsp;<?php echo getescapedtext ($hcms_lang['video'][$lang]); ?></label><br />
            <label><input type="checkbox" name="search_format[]" value="audio" checked="checked" />&nbsp;<?php echo getescapedtext ($hcms_lang['audio'][$lang]); ?></label><br />
          </td>
        </tr>
        <tr id="row_searchfilesize">
          <td><?php echo getescapedtext ($hcms_lang['file-size'][$lang]); ?> </td>
          <td id="filetype_general">
            <select name="search_filesize_operator"><option>&gt;=</option><option>&gt;</option><option>&lt;=</option><option>&lt;</option></select>
            <input type="number" name="search_filesize" style="width:70px;" maxlength="10" min="1" max="9999999999" /> KB
          </td>
        </tr>   
        <?php } ?>
        <tr>
          <td style="white-space:nowrap; vertical-align:top;"><?php echo getescapedtext ($hcms_lang['last-modified'][$lang]); ?> </td>
          <td style="white-space:nowrap; vertical-align:top;">
            <table class="hcmsTableNarrow">     
              <tr>
                <td> 
                  <?php echo getescapedtext ($hcms_lang['from'][$lang]); ?>&nbsp;
                </td>
                <td>
                  <input type="text" name="date_from" id="date_from_1" readonly="readonly" value="" /><img src="<?php echo getthemelocation(); ?>img/button_datepicker.png" onclick="show_cal(this, 'date_from_1', '%Y-%m-%d');" alt="<?php echo getescapedtext ($hcms_lang['select-date'][$lang]); ?>" title="<?php echo getescapedtext ($hcms_lang['select-date'][$lang]); ?>" class="hcmsButtonTiny hcmsButtonSizeSquare" />
                </td>
              </tr>
              <tr>
                <td>
                <?php echo getescapedtext ($hcms_lang['to'][$lang]); ?>&nbsp;
                </td>
                <td>
                  <input type="text" name="date_to" id="date_to_1" readonly="readonly" value="" /><img src="<?php echo getthemelocation(); ?>img/button_datepicker.png" onclick="show_cal(this, 'date_to_1', '%Y-%m-%d');" alt="<?php echo getescapedtext ($hcms_lang['select-date'][$lang]); ?>" title="<?php echo getescapedtext ($hcms_lang['select-date'][$lang]); ?>" class="hcmsButtonTiny hcmsButtonSizeSquare" />      
                </td>
              </tr>
            </table>
          </td>
        </tr>
        <tr>
          <td style="white-space:nowrap;" colspan="2">
            <?php echo getescapedtext ($hcms_lang['geo-location'][$lang]); ?><br />
            <div style="position:relative; left:460px; top:15px; width:22px; height:22px; z-index:1000;">
              <img src="<?php echo getthemelocation(); ?>img/info.png" title="<?php echo getescapedtext ($hcms_lang['help'][$lang]); ?>" onmouseover="hcms_showInfo('helpmapLayer');" onmouseout="hcms_hideInfo('helpmapLayer');" class="hcmsButtonSizeSquare" style="cursor:pointer;" />
              <div id="helpmapLayer" style="display:none; position:absolute; top:50px; right:140px;"><img src="<?php echo getthemelocation(); ?>img/info-right-click-drag.png" /></div>
            </div>
            <div id="map" style="width:500px; height:240px; margin-top:-15px; border:1px solid grey;"></div>         
          </td>
        </tr>
        <tr>
          <td style="white-space:nowrap;"><?php echo getescapedtext ($hcms_lang['sw-coordinates'][$lang]); ?> </td>
          <td style="white-space:nowrap;"><input type="text" name="geo_border_sw" style="width:220px;" maxlength="100" /></td>
        </tr>
        <tr>
          <td style="white-space:nowrap;"><?php echo getescapedtext ($hcms_lang['ne-coordinates'][$lang]); ?> </td>
          <td style="white-space:nowrap;"><input type="text" name="geo_border_ne" style="width:220px;" maxlength="100" /></td>
        </tr>
        <tr>
          <td style="white-space:nowrap;"><?php echo getescapedtext ($hcms_lang['start-search'][$lang]); ?> </td>
          <td style="white-space:nowrap;">
      			<img name="Button1" src="<?php echo getthemelocation(); ?>img/button_ok.png" class="hcmsButtonTinyBlank hcmsButtonSizeSquare" onclick="startSearch('general');" onMouseOut="hcms_swapImgRestore()" onMouseOver="hcms_swapImage('Button1','','<?php echo getthemelocation(); ?>img/button_ok_over.png',1)" title="OK" alt="OK" />
          </td>
        </tr>			
      </table>  
    </form>
  </div>
  
  <div id="searchtab_advanced" style="position:absolute; width:520px; height:580px; z-index:1; left:8px; top:22px; visibility:hidden;"> 
    <form name="searchform_advanced" method="post" action="search_objectlist.php">
      <input type="hidden" name="search_dir" value="<?php echo $location_esc; ?>" />
      <?php if ($cat == "page") { ?><input type="hidden" name="search_format[]" value="page" /><?php } ?>
  
      <table class="hcmsTableStandard" style="table-layout:auto; width:100%; border:1px solid #000000; border-collapse:collapse; border-spacing:0;">
        <tr> 
          <td style="width:180px; white-space:nowrap; padding-top:10px;"><?php echo getescapedtext ($hcms_lang['based-on-template'][$lang]); ?> </td>
          <td style="padding-top:10px;">
            <select name="template" onChange="loadForm();" style="width:220px;">
          <?php
          // load publication inheritance setting
          $site_array = array();
          
          if ($mgmt_config[$site]['inherit_tpl'] == true)
          {
            $inherit_db = inherit_db_read ();
            $site_array = inherit_db_getparent ($inherit_db, $site);
            
            // add own publication
            $site_array[] = $site;
          }
          else $site_array[] = $site;
          
          $template_array = array();
          
          if (is_array ($site_array) && sizeof ($site_array) > 0)
          {
            foreach ($site_array as $site_source)
            {
              $dir_template = dir ($mgmt_config['abs_path_template'].$site_source."/");
    
              if ($dir_template != false)
              {
                while ($entry = $dir_template->read())
                {
                  if ($entry != "." && $entry != ".." && !is_dir ($entry) && !preg_match ("/.inc.tpl/", $entry) && !preg_match ("/.tpl.v_/", $entry))
                  {
                    if ($cat == "page" && strpos ($entry, ".page.tpl") > 0)
                    {
                      $template_array[] = $entry;
                    }
                    elseif ($cat == "comp" && strpos ($entry, ".comp.tpl") > 0)
                    {
                      $template_array[] = $entry;
                    }
                    elseif ($cat == "comp" && strpos ($entry, ".meta.tpl") > 0)
                    {
                      $template_array[] = $entry;
                    }                  
                  }
                }
    
                $dir_template->close();
              }
            }
          }
  
          if (is_array ($template_array) && sizeof ($template_array) >= 1)
          {
            // remove double entries (double entries due to parent publications won't be listed)
            $template_array = array_unique ($template_array);
            sort ($template_array);
            reset ($template_array);
            
            foreach ($template_array as $value)
            {
              if (strpos ($value, ".page.tpl") > 0) $tpl_name = substr ($value, 0, strpos ($value, ".page.tpl"))." (".getescapedtext ($hcms_lang['page'][$lang]).")";
              elseif (strpos ($value, ".comp.tpl") > 0) $tpl_name = substr ($value, 0, strpos ($value, ".comp.tpl"))." (".getescapedtext ($hcms_lang['component'][$lang]).")";
              elseif (strpos ($value, ".meta.tpl") > 0) $tpl_name = substr ($value, 0, strpos ($value, ".meta.tpl"))." (".getescapedtext ($hcms_lang['meta-data'][$lang]).")";
              
              echo "<option value=\"".$value."\""; if ($value == $template) echo " selected=\"selected\""; echo ">".$tpl_name."</option>\n";
            }
          }
          else 
          {
            echo "<option value=\"\">&nbsp;</option>\n";
          }
          ?>
            </select>
          </td>
        </tr>
        <tr> 
          <td  style="white-space:nowrap;"><?php echo getescapedtext ($hcms_lang['search-in-folder'][$lang]); ?> </td>
          <td>
            <input type="text" name="folder" value="<?php echo $searcharea; ?>" style="width:220px;" disabled="disabled" /> 
          </td>
        </tr>
        <tr>
          <td colspan="2" style="white-space:nowrap; vertical-align:top;">
            <iframe id="contentFRM" name="contentFRM" width="0" height="0" frameborder="0"></iframe> 
            <div id="contentLayer" class="hcmsWorkplaceExplorer" style="border:1px solid #000000; width:486px; height:150px; padding:2px; overflow:auto; visibility:hidden;"></div>
          </td>			  
        </tr>
        <tr>
          <td style="white-space:nowrap;"><?php echo getescapedtext ($hcms_lang['link-fields-with'][$lang]); ?> </td>
          <td style="white-space:nowrap;">
            <select id="search_operator" name="search_operator" style="width:220px;">
                <option value="AND" <?php if (empty ($mgmt_config['search_operator']) || (!empty ($mgmt_config['search_operator']) && strtoupper ($mgmt_config['search_operator'])== "AND")) echo "selected"; ?>>AND</option>
                <option value="OR" <?php if (!empty ($mgmt_config['search_operator']) && strtoupper ($mgmt_config['search_operator'])== "OR") echo "selected"; ?>>OR</option>
            </select>
          </td>			  
        </tr>
        <?php if ($cat == "comp") { ?>
        <tr>
          <td style="white-space:nowrap; vertical-align:top;"><?php echo getescapedtext ($hcms_lang['file-type'][$lang]); ?> </td>
          <td id="filetype_advanced" style="white-space:nowrap; vertical-align:top;">
            <label><input type="checkbox" name="search_format[]" value="comp" checked="checked" />&nbsp;<?php echo getescapedtext ($hcms_lang['component'][$lang]); ?></label><br />
            <label><input type="checkbox" name="search_format[]" value="image" checked="checked" />&nbsp;<?php echo getescapedtext ($hcms_lang['image'][$lang]); ?></label><br />
            <label><input type="checkbox" name="search_format[]" value="document" checked="checked" />&nbsp;<?php echo getescapedtext ($hcms_lang['document'][$lang]); ?></label><br />
            <label><input type="checkbox" name="search_format[]" value="video" checked="checked" />&nbsp;<?php echo getescapedtext ($hcms_lang['video'][$lang]); ?></label><br />
            <label><input type="checkbox" name="search_format[]" value="audio" checked="checked" />&nbsp;<?php echo getescapedtext ($hcms_lang['audio'][$lang]); ?></label><br />
          </td>
        </tr>
        <tr>
          <td style="white-space:nowrap; vertical-align:top;"><?php echo getescapedtext ($hcms_lang['file-size'][$lang]); ?> </td>
          <td id="filetype_general" style="white-space:nowrap; vertical-align:top;">
            <select name="search_filesize_operator"><option>&gt;=</option><option>&gt;</option><option>&lt;=</option><option>&lt;</option></select>
            <input type="number" name="search_filesize" style="width:70px;" maxlength="10" min="1" max="9999999999" /> KB
          </td>
        </tr> 
        <?php } ?>  
        <tr> 
          <td style="white-space:nowrap;"><?php echo getescapedtext ($hcms_lang['object-id-link-id'][$lang]); ?> </td>
          <td style="white-space:nowrap;">
            <input type="text" name="object_id" value="" style="width:220px;" /> 
          </td>
        </tr>
        <tr> 
          <td style="white-space:nowrap;"><?php echo getescapedtext ($hcms_lang['container-id'][$lang]); ?> </td>
          <td style="white-space:nowrap;">
            <input type="text" name="container_id" value="" style="width:220px;" /> 
          </td>
        </tr>
        <tr>
          <td style="white-space:nowrap; vertical-align:top;"><?php echo getescapedtext ($hcms_lang['last-modified'][$lang]); ?> </td>
          <td style="white-space:nowrap; vertical-align:top;">
            <table class="hcmsTableNarrow">     
              <tr>
                <td> 
                  <?php echo getescapedtext ($hcms_lang['from'][$lang]); ?>&nbsp;
                </td>
                <td>
                  <input type="text" name="date_from" id="date_from_2" readonly="readonly" value="" /><img src="<?php echo getthemelocation(); ?>img/button_datepicker.png" onclick="show_cal(this, 'date_from_2', '%Y-%m-%d');" alt="<?php echo getescapedtext ($hcms_lang['select-date'][$lang]); ?>" title="<?php echo getescapedtext ($hcms_lang['select-date'][$lang]); ?>" class="hcmsButtonTiny hcmsButtonSizeSquare" />
                </td>
              </tr>
              <tr>
                <td>
                <?php echo getescapedtext ($hcms_lang['to'][$lang]); ?>&nbsp; 
                </td>
                <td>
                  <input type="text" name="date_to" id="date_to_2" readonly="readonly" value="" /><img src="<?php echo getthemelocation(); ?>img/button_datepicker.png" onclick="show_cal(this, 'date_to_2', '%Y-%m-%d');" alt="<?php echo getescapedtext ($hcms_lang['select-date'][$lang]); ?>" title="<?php echo getescapedtext ($hcms_lang['select-date'][$lang]); ?>" class="hcmsButtonTiny hcmsButtonSizeSquare" />      
                </td>
              </tr>
            </table>
          </td>
        </tr>           		
        <tr>
          <td style="white-space:nowrap;"><?php echo getescapedtext ($hcms_lang['start-search'][$lang]); ?> </td>
          <td style="white-space:nowrap;">
      			<img name="Button2" src="<?php echo getthemelocation(); ?>img/button_ok.png" class="hcmsButtonTinyBlank hcmsButtonSizeSquare" onclick="startSearch('advanced');" onMouseOut="hcms_swapImgRestore()" onMouseOver="hcms_swapImage('Button2','','<?php echo getthemelocation(); ?>img/button_ok_over.png',1)" title="OK" alt="OK" />
          </td>
        </tr>			
      </table>
    </form>
  </div>
  
  <?php if ($setlocalpermission['create'] == 1) { ?>
  <div id="searchtab_replace" style="position:absolute; width:520px; height:580px; z-index:1; left:8px; top:22px; visibility:hidden;"> 
    <form name="searchform_replace" method="post" action="search_objectlist.php">
      <input type="hidden" name="search_dir" value="<?php echo $location_esc; ?>" />
      <?php if ($cat == "page") { ?><input type="hidden" name="search_format[]" value="page" /><?php } ?>
      
      <table class="hcmsTableStandard" style="table-layout:auto; width:100%; border:1px solid #000000; border-collapse:collapse; border-spacing:0;">
        <tr> 
          <td style="width:180px; white-space:nowrap; padding-top:10px;"><?php echo getescapedtext ($hcms_lang['search-expression'][$lang]); ?> </td>
          <td style="white-space:nowrap; padding-top:10px;"> 
            <input type="text" name="search_expression" style="width:220px;" />
          </td>
        </tr>
        <tr> 
          <td style="white-space:nowrap;"><?php echo getescapedtext ($hcms_lang['replace-with'][$lang]); ?> </td>
          <td style="white-space:nowrap;"> 
            <input type="text" name="replace_expression" style="width:220px;" />
          </td>
        </tr>          
        <tr> 
          <td style="white-space:nowrap;"><?php echo getescapedtext ($hcms_lang['search-in-folder'][$lang]); ?> </td>
          <td style="white-space:nowrap;"> 
            <input type="text" name="folder" value="<?php echo $searcharea; ?>" style="width:220px;" disabled="disabled" />
          </td>
        </tr>
        <?php if ($cat == "comp") { ?>
        <tr>
          <td style="white-space:nowrap; vertical-align:top;"><?php echo getescapedtext ($hcms_lang['file-type'][$lang]); ?> </td>
          <td id ="filetype_replace" style="white-space:nowrap; vertical-align:top;">
            <label><input type="checkbox" name="search_format[]" value="comp" checked="checked" />&nbsp;<?php echo getescapedtext ($hcms_lang['component'][$lang]); ?></label><br />
            <label><input type="checkbox" name="search_format[]" value="image" checked="checked" />&nbsp;<?php echo getescapedtext ($hcms_lang['image'][$lang]); ?></label><br />
            <label><input type="checkbox" name="search_format[]" value="document" checked="checked" />&nbsp;<?php echo getescapedtext ($hcms_lang['document'][$lang]); ?></label><br />
            <label><input type="checkbox" name="search_format[]" value="video" checked="checked" />&nbsp;<?php echo getescapedtext ($hcms_lang['video'][$lang]); ?></label><br />
            <label><input type="checkbox" name="search_format[]" value="audio" checked="checked" />&nbsp;<?php echo getescapedtext ($hcms_lang['audio'][$lang]); ?></label><br />
          </td>
        </tr>
        <?php } ?>   
        <tr>
          <td style="white-space:nowrap; vertical-align:top;"><?php echo getescapedtext ($hcms_lang['last-modified'][$lang]); ?> </td>
          <td style="white-space:nowrap; vertical-align:top;">
            <table class="hcmsTableNarrow">     
              <tr>
                <td> 
                  <?php echo getescapedtext ($hcms_lang['from'][$lang]); ?>&nbsp;
                </td>
                <td>
                  <input type="text" name="date_from" id="date_from_3" readonly="readonly" value="" /><img src="<?php echo getthemelocation(); ?>img/button_datepicker.png" onclick="show_cal(this, 'date_from_3', '%Y-%m-%d');" alt="<?php echo getescapedtext ($hcms_lang['select-date'][$lang]); ?>" title="<?php echo getescapedtext ($hcms_lang['select-date'][$lang]); ?>" class="hcmsButtonTiny hcmsButtonSizeSquare" />
                </td>
              </tr>
              <tr>
                <td>
                <?php echo getescapedtext ($hcms_lang['to'][$lang]); ?>&nbsp; 
                </td>
                <td>
                  <input type="text" name="date_to" id="date_to_3" readonly="readonly" value="" /><img src="<?php echo getthemelocation(); ?>img/button_datepicker.png" onclick="show_cal(this, 'date_to_3', '%Y-%m-%d');" alt="<?php echo getescapedtext ($hcms_lang['select-date'][$lang]); ?>" title="<?php echo getescapedtext ($hcms_lang['select-date'][$lang]); ?>" class="hcmsButtonTiny hcmsButtonSizeSquare" />      
                </td>
              </tr>
            </table>
          </td>
        </tr>             
        <tr>
          <td style="white-space:nowrap;"><?php echo getescapedtext ($hcms_lang['start-search'][$lang]); ?> </td>
          <td style="white-space:nowrap;">
            <img name="Button3" src="<?php echo getthemelocation(); ?>img/button_ok.png" class="hcmsButtonTinyBlank hcmsButtonSizeSquare" onclick="checkForm(document.forms['searchform_replace']);" onMouseOut="hcms_swapImgRestore()" onMouseOver="hcms_swapImage('Button3','','<?php echo getthemelocation(); ?>img/button_ok_over.png',1)" title="OK" alt="OK" />
          </td>
        </tr>		            
        <tr> 
          <td colspan="2" style="white-space:nowrap;">
            <img src="<?php echo getthemelocation(); ?>img/info.png" class="hcmsIconList" style="display:inline-block;" /><div style="display:inline-block; margin-left:4px;" class="hcmsTextSmall"><?php echo getescapedtext ($hcms_lang['the-replacement-is-case-sensitive'][$lang]); ?></div>
          </td>
        </tr>   	         
      </table>
    </form>  
  </div>
  <?php } ?>
  
  <?php if ($cat == "comp") { ?>
  <div id="searchtab_images" style="position:absolute; width:520px; height:580px; z-index:1; left:8px; top:22px; visibility:hidden;"> 
    <form name="searchform_images" method="post" action="search_objectlist.php">
      <input type="hidden" name="search_dir" value="<?php echo $location_esc; ?>" />
  
      <table class="hcmsTableStandard" style="table-layout:auto; width:100%; border:1px solid #000000; border-collapse:collapse; border-spacing:0;">        
        <tr>
          <td style="width:180px; white-space:nowrap; padding-top:10px;"><?php echo getescapedtext ($hcms_lang['search-expression'][$lang]); ?> </td>
          <td style="white-space:nowrap; padding-top:10px;">
            <input type="text" name="search_expression" id="image_expression" style="width:220px;" maxlength="60" />
          </td>
        </tr>
        <tr>
          <td style="white-space:nowrap;"><?php echo getescapedtext ($hcms_lang['search-in-folder'][$lang]); ?> </td>
          <td style="white-space:nowrap;">
            <input type="text" name="folder" value="<?php echo $searcharea; ?>" style="width:220px;" disabled="disabled" />
          </td>
        </tr>
        <tr>
          <td style="white-space:nowrap;"><?php echo getescapedtext ($hcms_lang['search-restriction'][$lang]); ?> </td>
          <td class="hcmsHeadlineTiny" style="white-space:nowrap;">
            <input type="checkbox" name="search_cat" value="file" />&nbsp;<?php echo getescapedtext ($hcms_lang['only-object-names'][$lang]); ?>
          </td>
        </tr>                     
        <tr id="row_imagesize">
          <td style="white-space:nowrap;"><?php echo getescapedtext ($hcms_lang['media-size'][$lang]); ?> </td>
          <td style="white-space:nowrap;"><input type="hidden" name="search_format[image]" value="image" />
            <select name="search_imagesize" style="width:140px;" onchange="if (this.options[this.selectedIndex].value=='exact') document.getElementById('searchfield_imagesize').style.display='inline'; else document.getElementById('searchfield_imagesize').style.display='none';">
              <option value="" selected="selected"><?php echo getescapedtext ($hcms_lang['all'][$lang]); ?></option>
              <option value="1024-9000000"><?php echo getescapedtext ($hcms_lang['big-1024px'][$lang]); ?></option>
              <option value="640-1024"><?php echo getescapedtext ($hcms_lang['medium-640-1024px'][$lang]); ?></option>
              <option value="0-640"><?php echo getescapedtext ($hcms_lang['small'][$lang]); ?></option>
              <option value="exact"><?php echo getescapedtext ($hcms_lang['exact-w-x-h'][$lang]); ?></option>
            </select>
            <div id="searchfield_imagesize" style="display:none;">
              <input type="text" name="search_imagewidth" style="width:40px;" maxlength="8" /> x 
              <input type="text" name="search_imageheight" style="width:40px;" maxlength="8" /> px
            </div>
          </td>
        </tr>
        <tr id="row_searchfilesize">
          <td style="white-space:nowrap;"><?php echo getescapedtext ($hcms_lang['file-size'][$lang]); ?> </td>
          <td id="filetype_general" style="white-space:nowrap;">
            <select name="search_filesize_operator">
              <option>&gt;=</option>
              <option>&gt;</option>
              <option>&lt;=</option>
              <option>&lt;</option>
            </select>
            <input type="number" name="search_filesize" style="width:70px;" maxlength="10" min="1" max="9999999999" /> KB
          </td>
        </tr> 
        <tr id="row_imagecolor">
          <td style="white-space:nowrap; vertical-align:top;"><?php echo getescapedtext ($hcms_lang['image-color'][$lang]); ?> </td>
          <td style="vertical-align:top;">
            <div style="width:320px; margin:1px; padding:0; float:left;"><div style="float:left;"><input id="unsetcolors" style="margin:2px; padding:0;" type="checkbox" name="search_imagecolor" value="" checked="checked" onclick="unsetColors()" /></div>&nbsp;<?php echo getescapedtext ($hcms_lang['all'][$lang]); ?></div>
            <div style="width:85px; margin:1px; padding:0; float:left;"><div style="border:1px solid #999999; background:#000000; float:left;"><input class="hcmsColorKey" style="margin:2px; padding:0;" type="checkbox" name="search_imagecolor[]" value="K" onclick="setColors()" /></div>&nbsp;<?php echo getescapedtext ($hcms_lang['black'][$lang]); ?></div>
            <div style="width:85px; margin:1px; padding:0; float:left;"><div style="border:1px solid #999999; background:#FFFFFF; float:left;"><input class="hcmsColorKey" style="margin:2px; padding:0;" type="checkbox" name="search_imagecolor[]" value="W" onclick="setColors()" /></div>&nbsp;<?php echo getescapedtext ($hcms_lang['white'][$lang]); ?></div>
            <div style="width:85px; margin:1px; padding:0; float:left;"><div style="border:1px solid #999999; background:#808080; float:left;"><input class="hcmsColorKey" style="margin:2px; padding:0;" type="checkbox" name="search_imagecolor[]" value="E" onclick="setColors()" /></div>&nbsp;<?php echo getescapedtext ($hcms_lang['grey'][$lang]); ?></div>
            <div style="width:85px; margin:1px; padding:0; float:left;"><div style="border:1px solid #999999; background:#FF0000; float:left;"><input class="hcmsColorKey" style="margin:2px; padding:0;" type="checkbox" name="search_imagecolor[]" value="R" onclick="setColors()" /></div>&nbsp;<?php echo getescapedtext ($hcms_lang['red'][$lang]); ?></div>
            <div style="width:85px; margin:1px; padding:0; float:left;"><div style="border:1px solid #999999; background:#00C000; float:left;"><input  class="hcmsColorKey"style="margin:2px; padding:0;" type="checkbox" name="search_imagecolor[]" value="G" onclick="setColors()" /></div>&nbsp;<?php echo getescapedtext ($hcms_lang['green'][$lang]); ?></div>
            <div style="width:85px; margin:1px; padding:0; float:left;"><div style="border:1px solid #999999; background:#0000FF; float:left;"><input class="hcmsColorKey" style="margin:2px; padding:0;" type="checkbox" name="search_imagecolor[]" value="B" onclick="setColors()" /></div>&nbsp;<?php echo getescapedtext ($hcms_lang['blue'][$lang]); ?></div>
            <div style="width:85px; margin:1px; padding:0; float:left;"><div style="border:1px solid #999999; background:#00FFFF; float:left;"><input class="hcmsColorKey" style="margin:2px; padding:0;" type="checkbox" name="search_imagecolor[]" value="C" onclick="setColors()" /></div>&nbsp;<?php echo getescapedtext ($hcms_lang['cyan'][$lang]); ?></div>
            <div style="width:85px; margin:1px; padding:0; float:left;"><div style="border:1px solid #999999; background:#FF0090; float:left;"><input class="hcmsColorKey" style="margin:2px; padding:0;" type="checkbox" name="search_imagecolor[]" value="M" onclick="setColors()" /></div>&nbsp;<?php echo getescapedtext ($hcms_lang['magenta'][$lang]); ?></div>
            <div style="width:85px; margin:1px; padding:0; float:left;"><div style="border:1px solid #999999; background:#FFFF00; float:left;"><input class="hcmsColorKey" style="margin:2px; padding:0;" type="checkbox" name="search_imagecolor[]" value="Y" onclick="setColors()" /></div>&nbsp;<?php echo getescapedtext ($hcms_lang['yellow'][$lang]); ?></div>
            <div style="width:85px; margin:1px; padding:0; float:left;"><div style="border:1px solid #999999; background:#FF8A00; float:left;"><input class="hcmsColorKey" style="margin:2px; padding:0;" type="checkbox" name="search_imagecolor[]" value="O" onclick="setColors()" /></div>&nbsp;<?php echo getescapedtext ($hcms_lang['orange'][$lang]); ?></div>
            <div style="width:85px; margin:1px; padding:0; float:left;"><div style="border:1px solid #999999; background:#FFCCDD; float:left;"><input class="hcmsColorKey" style="margin:2px; padding:0;" type="checkbox" name="search_imagecolor[]" value="P" onclick="setColors()" /></div>&nbsp;<?php echo getescapedtext ($hcms_lang['pink'][$lang]); ?></div>
            <div style="width:85px; margin:1px; padding:0; float:left;"><div style="border:1px solid #999999; background:#A66500; float:left;"><input class="hcmsColorKey" style="margin:2px; padding:0;" type="checkbox" name="search_imagecolor[]" value="N" onclick="setColors()" /></div>&nbsp;<?php echo getescapedtext ($hcms_lang['brown'][$lang]); ?></div>
          </td>
        </tr>
        <tr id="row_imagetype">
          <td style="white-space:nowrap; vertical-align:top;"><?php echo getescapedtext ($hcms_lang['image-type'][$lang]); ?> </td>
          <td style="vertical-align:top;">
            <select name="search_imagetype" style="width:140px;">
              <option value="" selected="selected"><?php echo getescapedtext ($hcms_lang['all'][$lang]); ?></option>
              <option value="landscape"><?php echo getescapedtext ($hcms_lang['landscape'][$lang]); ?></option>
              <option value="portrait"><?php echo getescapedtext ($hcms_lang['portrait'][$lang]); ?></option>
              <option value="square"><?php echo getescapedtext ($hcms_lang['square'][$lang]); ?></option>
            </select>
          </td>
        </tr>            
        <tr>
          <td style="white-space:nowrap; vertical-align:top;"><?php echo getescapedtext ($hcms_lang['last-modified'][$lang]); ?> </td>
          <td style="white-space:nowrap; vertical-align:top;">
            <table class="hcmsTableHNarrow">     
              <tr>
                <td> 
                  <?php echo getescapedtext ($hcms_lang['from'][$lang]); ?>&nbsp;
                </td>
                <td>
                  <input type="text" name="date_from" id="date_from_4" readonly="readonly" value="" /><img src="<?php echo getthemelocation(); ?>img/button_datepicker.png" onclick="show_cal(this, 'date_from_4', '%Y-%m-%d');" alt="<?php echo getescapedtext ($hcms_lang['select-date'][$lang]); ?>" title="<?php echo getescapedtext ($hcms_lang['select-date'][$lang]); ?>" class="hcmsButtonTiny hcmsButtonSizeSquare" />
                </td>
              </tr>
              <tr>
                <td>
                <?php echo getescapedtext ($hcms_lang['to'][$lang]); ?>&nbsp; 
                </td>
                <td>
                  <input type="text" name="date_to" id="date_to_4" readonly="readonly" value="" /><img src="<?php echo getthemelocation(); ?>img/button_datepicker.png" onclick="show_cal(this, 'date_to_4', '%Y-%m-%d');" alt="<?php echo getescapedtext ($hcms_lang['select-date'][$lang]); ?>" title="<?php echo getescapedtext ($hcms_lang['select-date'][$lang]); ?>" class="hcmsButtonTiny hcmsButtonSizeSquare" />      
                </td>
              </tr>
            </table>
          </td>
        </tr>
        <tr>
          <td style="white-space:nowrap;"><?php echo getescapedtext ($hcms_lang['start-search'][$lang]); ?> </td>
          <td>
      			<img name="Button4" src="<?php echo getthemelocation(); ?>img/button_ok.png" class="hcmsButtonTinyBlank hcmsButtonSizeSquare" onclick="startSearch('images');" onMouseOut="hcms_swapImgRestore()" onMouseOver="hcms_swapImage('Button4','','<?php echo getthemelocation(); ?>img/button_ok_over.png',1)" title="OK" alt="OK" />
          </td>
        </tr>			
      </table> 
    </form>
  </div>
  <?php } ?>
</div>

</body>
</html>
