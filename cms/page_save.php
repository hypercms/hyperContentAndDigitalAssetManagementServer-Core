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
// language file
require_once ("language/page_save.inc.php");


// input parameters
$location = getrequest_esc ("location", "locationname");
$page = getrequest ("page", "objectname");
$contenttype = getrequest_esc ("contenttype");
$savetype = getrequest ("savetype");

// get publication and category
$site = getpublication ($location);
$cat = getcategory ($site, $location); 

// publication management config
if (valid_publicationname ($site)) require ($mgmt_config['abs_path_data']."config/".$site.".conf.php");
// load publication configuration
if (valid_publicationname ($site)) $publ_config = parse_ini_file ($mgmt_config['abs_path_rep']."config/".$site.".ini");

// ------------------------------ permission section --------------------------------

// check access permissions
$ownergroup = accesspermission ($site, $location, $cat);
$setlocalpermission = setlocalpermission ($site, $ownergroup, $cat);  
if ($ownergroup == false || $setlocalpermission['root'] != 1 || $setlocalpermission['create'] != 1 || !valid_publicationname ($site) || !valid_locationname ($location) || !valid_objectname ($page)) killsession ($user);

// check session of user
checkusersession ($user);

// --------------------------------- logic section ----------------------------------

// extract character set from content-type
$result_charset = getcharset ($site, $contenttype);

if ($result_charset != false) $charset = $result_charset['charset'];
else $charset = "";

// Autosave
// data submitted by jquery post need to be converted
if ($savetype == "auto")
{
	$auto = true;
	$message = array();

  // convert jquery post since it is always UTF-8
  if ($charset != "" && strtolower ($charset) != "utf-8")
  {
    $_POST = convertchars ($_POST, "UTF-8", $charset);
  }
}
else
{
	$auto = false;
}

// input parameters
$savetype = getrequest ("savetype");
$autosave = getrequest ("autosave");
$forward = getrequest ("forward");
$view = getrequest ("view");
$location = getrequest_esc ("location", "locationname");
$page = getrequest_esc ("page", "objectname");
$db_connect = getrequest_esc ("db_connect", "objectname");
$media = getrequest_esc ("media", "objectname");
$ctrlreload = getrequest_esc ("ctrlreload");
$tagname = getrequest_esc ("tagname", "objectname");
$id = getrequest_esc ("id", "objectname");
$toolbar = getrequest_esc ("toolbar");
$width = getrequest_esc ("width", "numeric");
$height = getrequest_esc ("height", "numeric");

$linkhref_curr = getrequest ("inkhref_curr", "array");
$linkhref = getrequest ("linkhref", "array");
$linktarget = getrequest_esc ("linktarget", "array");
$targetlist = getrequest_esc ("targetlist", "array");
$linktext = getrequest ("linktext", "array");
$artlinkhref_curr = getrequest ("artlinkhref_curr", "array");
$artlinkhref = getrequest ("artlinkhref", "array");
$artlinktarget = getrequest ("artlinktarget", "array");
$artlinktext = getrequest ("artlinktext", "array");

$arttitle = getrequest ("arttitle", "array");
$artstatus = getrequest ("artstatus", "array");
$artdatefrom = getrequest ("artdatefrom", "array");
$artdateto = getrequest ("artdateto", "array");

$textf = getrequest ("textf", "array");
$arttextf = getrequest ("arttextf", "array");
$textu = getrequest ("textu", "array");
$arttextu = getrequest ("arttextu", "array");
$textl = getrequest ("textl", "array");
$arttextl = getrequest ("arttextl", "array");
$textc = getrequest ("textc", "array");
$arttextc = getrequest ("arttextc", "array");
$value = getrequest_esc ("value");
$textd = getrequest ("textd", "array");
$arttextd = getrequest ("arttextd", "array");
$format = getrequest_esc ("format");
$commentu = getrequest ("commentu", "array");
$commentf = getrequest ("commentf", "array");

$mediacat = getrequest ("mediacat", "array");
$mediafile = getrequest ("mediafile", "array");
$mediaobject_curr = getrequest ("mediaobject_curr", "array");
$mediaobject = getrequest ("mediaobject", "array");
$mediaalttext = getrequest ("mediaalttext", "array");
$mediaalign = getrequest ("mediaalign", "array");
$mediawidth = getrequest ("mediawidth", "array");
$mediaheight = getrequest ("mediaheight", "array");
$mediatype = getrequest ("mediatype", "array");
$artmediafile = getrequest ("artmediafile", "array");
$artmediaobject_curr = getrequest ("artmediaobject_curr", "array");
$artmediaobject = getrequest ("artmediaobject", "array");
$artmediaalttext = getrequest ("artmediaalttext", "array");
$artmediaalign = getrequest ("artmediaalign", "array");
$artmediawidth = getrequest ("artmediawidth", "array");
$artmediaheight = getrequest ("artmediaheight", "array");

$component_curr = getrequest ("component_curr", "array");
$component = getrequest ("component", "array");
$artcomponent_curr = getrequest ("artcomponent_curr", "array");
$artcomponent = getrequest ("artcomponent", "array");
$components = getrequest ("components", "array");
$artcomponents = getrequest ("artcomponents", "array");
$componentm = getrequest ("componentm", "array");
$artcomponentm = getrequest ("artcomponentm", "array");
$condition = getrequest ("condition", "array");

if (isset ($_REQUEST['pagetitle'])) $pagetitle = getrequest ("pagetitle");
if (isset ($_REQUEST['pageauthor'])) $pageauthor = getrequest ("pageauthor");
if (isset ($_REQUEST['pagedescription'])) $pagedescription = getrequest ("pagedescription");
if (isset ($_REQUEST['pagekeywords'])) $pagekeywords = getrequest ("pagekeywords");
if (isset ($_REQUEST['pagecontenttype'])) $pagecontenttype = getrequest ("pagecontenttype");
if (isset ($_REQUEST['pagelanguage'])) $pagelanguage = getrequest ("pagelanguage");
if (isset ($_REQUEST['pagerevisit'])) $pagerevisit = getrequest ("pagerevisit");
if (isset ($_REQUEST['pagetracking'])) $pagetracking = getrequest ("pagetracking");

$token = getrequest ("token");

// convert location
$location = deconvertpath ($location, "file");
$location_esc = convertpath ($site, $location, $cat);

// load object file and get container
$objectdata = loadfile ($location, $page);
$contentfile = getfilename ($objectdata, "content");

// check locked by user
$result_containername = getcontainername ($contentfile);
$usedby = $result_containername['user'];

// if not locked by another user
if ($usedby == "" || $usedby == $user)
{
  // include hyperCMS Event System
  @include_once ($mgmt_config['abs_path_data']."eventsystem/hypercms_eventsys.inc.php");
  
  // ------------------------------------include db_connect functions ----------------------------------
  if (isset ($db_connect) && valid_objectname ($db_connect) && file_exists ($mgmt_config['abs_path_data']."db_connect/".$db_connect)) 
  {
    // include db_connect functions
    @include_once ($mgmt_config['abs_path_data']."db_connect/".$db_connect); 
  }
  
  // ---------------------------------------load content container -------------------------------------
  // if Tamino is used the same content container will be availbale in the database
  if (isset ($mgmt_config['db_connect_tamino']) && $mgmt_config['db_connect_tamino'] != "" && file_exists ($mgmt_config['abs_path_data']."db_connect/".$mgmt_config['db_connect_tamino']))
  {
    @include_once ($mgmt_config['abs_path_data']."db_connect/".$mgmt_config['db_connect_tamino']); 
    
    $db_container = db_read_container ("work", $site, $contentfile, $charset, $user);
    
    if ($db_container != false) $contentdata = $db_container['content'];
    else $contentdata = false;
  }
  else $contentdata = false;
  
  // if Tamino is not used read from content repository
  $container_id = substr ($contentfile, 0, strpos ($contentfile, ".xml")); 
  
  if ($contentdata == false)
  {
    // load content container
    $contentdata = loadcontainer ($contentfile, "work", $user);
  } 
  
  // check if content is not empty
  if ($contentdata != false)
  {
    $contentdatanew = $contentdata;
  
    // check if date-from is greater than date-to in article content
    if (is_array ($artstatus))
    {
      for ($i = 1; $i <= sizeof ($artstatus); $i++)
      {
        // get key (position) of array item
        $artid = key ($artstatus); 
        
        if (isset ($artdatefrom[$artid]) && $artdatefrom[$artid] != "" && isset ($artdateto[$artid]) && $artdateto[$artid] != "")
        {
          $artdatefromcheck = str_replace ("-", "", $artdatefrom[$artid]);
          $artdatefromcheck = str_replace (" ", "", $artdatefromcheck);
          $artdatefromcheck = str_replace (":", "", $artdatefromcheck);
          $artdatetocheck = str_replace ("-", "", $artdateto[$artid]);
          $artdatetocheck = str_replace (" ", "", $artdatetocheck);
          $artdatetocheck = str_replace (":", "", $artdatetocheck);
    
          // check if date-from is greater than date-to
          if ($artdatetocheck < $artdatefromcheck)
          {
            echo "<!DOCTYPE HTML PUBLIC \"-//W3C//DTD HTML 4.01 Transitional//EN\" \"http://www.w3.org/TR/html4/loose.dtd\">\n";
            echo "<html>\n<head>\n";
            echo "<title>hyperCMS</title>\n";
            echo "<meta http-equiv=\"Content-Type\" content=\"text/html; charset=".$lang_codepage[$lang]."\">\n";
            echo "<link rel=\"stylesheet\" href=\"".getthemelocation()."css/main.css\">\n";
            echo "</head>\n";
            echo "<body class=\"hcmsWorkplaceGeneric\">\n";
            echo "<p class=hcmsHeadline>".$text1[$lang]."</p>\n";
            echo $text2[$lang]."\n";
            echo "<a href=\"#\" onlick=\"history.back();\">".$text3[$lang]."</a><br />\n";
            echo "</body>\n</html>";
            exit;
          }
        }
        
        next ($artstatus);
      }
    }
  
    // write content in container if security token is available and matches the crypted location of the object (absolute path in file system is used as input for encryption!)
    if (checktoken ($token, $user) && valid_locationname ($location) && valid_objectname ($page))
    {
      if ($contentdatanew != false && is_array ($artstatus)) $contentdatanew = setarticle ($site, $contentdatanew, $contentfile, $arttitle, $artstatus, $artdatefrom, $artdateto, $user, $user);
    
      // text content
      if (isset ($textf) && is_array ($textf) && $contentdatanew != false) $contentdatanew = settext ($site, $contentdatanew, $contentfile, $textf, "f", "no", $user, $user, $charset);
      if (isset ($arttextf) && is_array ($arttextf) && $contentdatanew != false) $contentdatanew = settext ($site, $contentdatanew, $contentfile, $arttextf, "f", "yes", $user, $user, $charset);
      if (isset ($textu) && is_array ($textu) && $contentdatanew != false) $contentdatanew = settext ($site, $contentdatanew, $contentfile, $textu, "u", "no", $user, $user, $charset);
      if (isset ($arttextu) && is_array ($arttextu) && $contentdatanew != false) $contentdatanew = settext ($site, $contentdatanew, $contentfile, $arttextu, "u", "yes", $user, $user, $charset);
      if (isset ($textl) && is_array ($textl) && $contentdatanew != false) $contentdatanew = settext ($site, $contentdatanew, $contentfile, $textl, "l", "no", $user, $user, $charset);
      if (isset ($arttextl) && is_array ($arttextl) && $contentdatanew != false) $contentdatanew = settext ($site, $contentdatanew, $contentfile, $arttextl, "l", "yes", $user, $user, $charset);
      if (isset ($textc) && is_array ($textc) && $contentdatanew != false) $contentdatanew = settext ($site, $contentdatanew, $contentfile, $textc, "c", "no", $user, $user, $charset);
      if (isset ($arttextc) && is_array ($arttextc) && $contentdatanew != false) $contentdatanew = settext ($site, $contentdatanew, $contentfile, $arttextc, "c", "yes", $user, $user, $charset);
      if (isset ($textd) && is_array ($textd) && $contentdatanew != false) $contentdatanew = settext ($site, $contentdatanew, $contentfile, $textd, "d", "no", $user, $user, $charset);
      if (isset ($arttextd) && is_array ($arttextd) && $contentdatanew != false) $contentdatanew = settext ($site, $contentdatanew, $contentfile, $arttextd, "d", "yes", $user, $user, $charset);   
      // only when not autosaving
      if (isset ($commentu) && is_array ($commentu) && $contentdatanew != false && $auto == false) $contentdatanew = settext ($site, $contentdatanew, $contentfile, $commentu, "u", "no", $user, $user, $charset, true);
      if (isset ($commentf) && is_array ($commentf) && $contentdatanew != false && $auto == false) $contentdatanew = settext ($site, $contentdatanew, $contentfile, $commentf, "f", "no", $user, $user, $charset, true);
      
      // get media
      $object_info = getobjectinfo ($site, $location, $page, $user);
      
      // write meta data to media file
      if (trim ($object_info['media']) != "")
      {
        // define text array
        $textmeta = array();
        
        if (isset ($textf) && is_array ($textf)) $textmeta = array_merge ($textmeta, $textf);
        if (isset ($textu) && is_array ($textu)) $textmeta = array_merge ($textmeta, $textu);
        if (isset ($textl) && is_array ($textl)) $textmeta = array_merge ($textmeta, $textl);
        if (isset ($textc) && is_array ($textc)) $textmeta = array_merge ($textmeta, $textc);
        if (isset ($textd) && is_array ($textd)) $textmeta = array_merge ($textmeta, $textd);
        
        $object_mediafile = getmedialocation ($site, $object_info['media'], "abs_path_media").$site."/".$object_info['media'];
        
        // write IPTC data only to following file extensions
        $result_iptc = false;
        
        if ($mgmt_config['iptc_save'])
        {
          $allowed_ext = array('.jpg', '.jpeg', '.pjpeg');   
        
          // get file info
          $file_info = getfileinfo ($site, $object_info['media'], $cat);     
  
          if (in_array ($file_info['ext'], $allowed_ext))
          {
            $iptc = iptc_create ($site, $textmeta);
  
            // write IPTC tags to file
            if (is_array ($iptc))
            {
              $result_iptc = iptc_writefile ($object_mediafile, $iptc, 1);
                
              if ($result_iptc == false)
              {
                $errcode = "20119";
                $error[] = $mgmt_config['today']."|page_save.inc.php|error|$errcode|unable to save IPTC tags to file '".$object_info['media']."'";          
              }            
            }
          } 
        }
        
        // write XMP data only to following file extensions
        $result_xmp = false;
        
        if ($mgmt_config['xmp_save'])
        {
          $xmp = xmp_create ($site, $textmeta);

          // write IPTC tags to file
          if (is_array ($xmp))
          {
            $result_xmp = xmp_writefile ($object_mediafile, $xmp, 1);
              
            if ($result_xmp == false)
            {
              $errcode = "20119";
              $error[] = $mgmt_config['today']."|page_save.inc.php|error|$errcode|unable to save XMP tags to file '$media'";          
            }            
          }
        }
        
        // alternatively touch the file to change the modified date
        if (
             is_file ($object_mediafile) && 
             (
               (!$mgmt_config['iptc_save'] && !$mgmt_config['xmp_save']) || 
               ($mgmt_config['iptc_save'] && !$result_iptc) ||
               ($mgmt_config['xmp_save'] && !$result_xmp)
             )
           )
        {
          touch ($object_mediafile);
        }
      }
      
      // media content
      if ($contentdatanew != false && isset ($mediafile) && is_array ($mediafile)) $contentdatanew = setmedia ($site, $contentdatanew, $contentfile, $mediafile, $mediaobject_curr, $mediaobject, $mediaalttext, $mediaalign, $mediawidth, $mediaheight, "no", $user, $user, $charset);
      if ($contentdatanew != false && isset ($artmediafile) && is_array ($artmediafile)) $contentdatanew = setmedia ($site, $contentdatanew, $contentfile, $artmediafile, $artmediaobject_curr, $artmediaobject, $artmediaalttext, $artmediaalign, $artmediawidth, $artmediaheight, "yes", $user, $user, $charset);
  
      // page link content
      if ($contentdatanew != false && isset ($linkhref) && is_array ($linkhref)) $contentdatanew = setpagelink ($site, $contentdatanew, $contentfile, $linkhref_curr, $linkhref, $linktarget, $linktext, "no", $user, $user, $charset);
      if ($contentdatanew != false && isset ($artlinkhref) && is_array ($artlinkhref)) $contentdatanew = setpagelink ($site, $contentdatanew, $contentfile, $artlinkhref_curr, $artlinkhref, $artlinktarget, $artlinktext, "yes", $user, $user, $charset);    
  
      // component content
      if ($contentdatanew != false && isset ($component) && is_array ($component)) $contentdatanew = setcomplink ($site, $contentdatanew, $contentfile, $component_curr, $component, $condition, "no", $user, $user);
      if ($contentdatanew != false && isset ($artcomponent) && is_array ($artcomponent)) $contentdatanew = setcomplink ($site, $contentdatanew, $contentfile, $artcomponent_curr, $artcomponent, $condition, "yes", $user, $user);    
      if ($contentdatanew != false && isset ($components) && is_array ($components)) $contentdatanew = setcomplink ($site, $contentdatanew, $contentfile, $component_curr, $components, $condition, "no", $user, $user);
      if ($contentdatanew != false && isset ($artcomponents) && is_array ($artcomponents)) $contentdatanew = setcomplink ($site, $contentdatanew, $contentfile, $artcomponent_curr, $artcomponents, $condition, "yes", $user, $user);    
      if ($contentdatanew != false && isset ($componentm) && is_array ($componentm)) $contentdatanew = setcomplink ($site, $contentdatanew, $contentfile, $component_curr, $componentm, $condition, "no", $user, $user);
      if ($contentdatanew != false && isset ($artcomponentm) && is_array ($artcomponentm)) $contentdatanew = setcomplink ($site, $contentdatanew, $contentfile, $artcomponent_curr, $artcomponentm, $condition, "yes", $user, $user);    
  
      // head content
      if (isset ($pagetitle)) $headcontent['pagetitle'] = $pagetitle;
      if (isset ($pageauthor)) $headcontent['pageauthor'] = $pageauthor;
      if (isset ($pagedescription)) $headcontent['pagedescription'] = $pagedescription;
      if (isset ($pagekeywords)) $headcontent['pagekeywords'] = $pagekeywords;
      if (isset ($pagecontenttype)) $headcontent['pagecontenttype'] = $pagecontenttype;
      if (isset ($pagelanguage)) $headcontent['pagelanguage'] = $pagelanguage;
      if (isset ($pagerevisit)) $headcontent['pagerevisit'] = $pagerevisit;
      if (isset ($pagetracking)) $headcontent['pagetracking'] = $pagetracking;
  
      if ($contentdatanew != false && isset ($headcontent) && is_array ($headcontent)) $contentdatanew = sethead ($site, $contentdatanew, $contentfile, $headcontent, $user);
    }
   
    // ----------------------------------- write data into content container --------------------------------------
    if ($contentdatanew != false)
    {
      // eventsystem
      if ($eventsystem['onsaveobject_pre'] == 1 && (!isset ($eventsystem['hide']) || $eventsystem['hide'] == 0)) 
        $contentdataevent = onsaveobject_pre ($site, $cat, $location, $page, $contentfile, $contentdatanew, $user);
      
      // check if event returns a string, if so, the event returns the container and not true or false 
      if (!empty ($contentdataevent) && strlen ($contentdataevent) > 10) $contentdatanew = $contentdataevent;    
    
      // insert new date into content file
      $contentdatanew = setcontent ($contentdatanew, "<hyperCMS>", "<contentdate>", $mgmt_config['today'], "", "");
      
      // set encoding 
      $charset_old = getcharset ("", $contentdatanew); 
      
      if ($charset_old == false || $charset_old == "" || $charset_old != $charset)
      {
        // write XML declaration parameter for text encoding
        if ($charset != "") $contentdatanew = setxmlparameter ($contentdatanew, "encoding", $charset);
      }    
      
      // db_connect will save content in Tamino 
      if (isset ($mgmt_config['db_connect_tamino']) && $mgmt_config['db_connect_tamino'] != "") 
      {
        db_write_container ("work", $site, $contentfile, $contentdatanew, $user);      
      }    
      
      // save working xml content container file
      $savefile = savecontainer ($container_id, "work", $contentdatanew, $user);
  
      // test if file could be saved
      if ($savefile == false)
      {
        // define meta tag
        $add_onload =  "";
        
        if ($auto)
        {
        	$message[] = $text12[$lang];
        	$message[] = $text13[$lang];
        }
        else
        {
  	      //define message to display
      	  $message = "<p class=hcmsHeadline>".$text12[$lang]."</p>\n".$text13[$lang]."<br />\n";
        }
      }
      else
      {
        // eventsystem
        if ($eventsystem['onsaveobject_post'] == 1 && (!isset ($eventsystem['hide']) || $eventsystem['hide'] == 0)) 
          $contentdataevent = onsaveobject_post ($site, $cat, $location, $page, $contentfile, $contentdatanew, $user);
              
        // check if event returns a string, if so, the event returns the container and not true or false 
        if (strlen ($contentdataevent) > 10) $contentdatanew = $contentdataevent;
        
        // notification
        notifyusers ($site, $location, $page, "onedit", $user);
              
      	if (!$auto)
        {
         	// define meta tag
         	if ($savetype == "editorf_so" || $savetype == "editorf_wysiwyg")
          {
         	  $add_onload =  "document.location.href='".$mgmt_config['url_path_cms']."editor/editorf.php?site=".url_encode($site)."&cat=".url_encode($cat)."&db_connect=".url_encode($db_connect)."&location=".url_encode($location_esc)."&page=".url_encode($page)."&tagname=".url_encode($tagname)."&id=".url_encode($id)."&contenttype=".url_encode($contenttype)."&width=".url_encode($width)."&height=".url_encode($height)."&toolbar=".url_encode($toolbar)."';\n";
         	}
          elseif ($savetype == "editoru_so")
         	{
            $add_onload =  "document.location.href='".$mgmt_config['url_path_cms']."editor/editoru.php?site=".url_encode($site)."&cat=".url_encode($cat)."&db_connect=".url_encode($db_connect)."&location=".url_encode($location_esc)."&page=".url_encode($page)."&tagname=".url_encode($tagname)."&id=".url_encode($id)."&constraint=".url_encode($constraint)."&contenttype=".url_encode($contenttype)."&width=".url_encode($width)."&height=".url_encode($height)."';\n";
         	}
          elseif ($savetype == "editorl_so")
         	{
            $add_onload =  "document.location.href='".$mgmt_config['url_path_cms']."editor/editorl.php?site=".url_encode($site)."&cat=".url_encode($cat)."&db_connect=".url_encode($db_connect)."&location=".url_encode($location_esc)."&page=".url_encode($page)."&tagname=".url_encode($tagname)."&id=".url_encode($id)."&list=".url_encode($list)."&contenttype=".url_encode($contenttype)."';\n";
         	}
          elseif ($savetype == "editorc_so")
         	{
            $add_onload =  "document.location.href='".$mgmt_config['url_path_cms']."editor/editorc.php?site=".url_encode($site)."&cat=".url_encode($cat)."&db_connect=".url_encode($db_connect)."&location=".url_encode($location_esc)."&page=".url_encode($page)."&tagname=".url_encode($tagname)."&id=".url_encode($id)."&value=".url_encode($value)."&contenttype=".url_encode($contenttype)."';\n";
         	}
          elseif ($savetype == "editord_so")
         	{
            $add_onload =  "document.location.href='".$mgmt_config['url_path_cms']."editor/editord.php?site=".url_encode($site)."&cat=".url_encode($cat)."&db_connect=".url_encode($db_connect)."&location=".url_encode($location_esc)."&page=".url_encode($page)."&tagname=".url_encode($tagname)."&id=".url_encode($id)."&format=".url_encode($format)."&contenttype=".url_encode($contenttype)."';\n";
         	}        
          elseif ($savetype == "form_so")
         	{
            if ($forward == "") $add_onload =  "document.location.href='".$mgmt_config['url_path_cms']."page_view.php?view=".url_encode($view)."&site=".url_encode($site)."&cat=".url_encode($cat)."&db_connect=".url_encode($db_connect)."&location=".url_encode($location_esc)."&page=".url_encode($page)."';\n";
            else $add_onload = "document.location.href='".$forward."';\n";
         	}
         	elseif ($savetype == "form_sc")
         	{
            $add_onload =  "document.location.href='".$mgmt_config['url_path_cms']."page_view.php?view=cmsview&site=".url_encode($site)."&cat=".url_encode($cat)."&db_connect=".url_encode($db_connect)."&location=".url_encode($location_esc)."&page=".url_encode($page)."';\n";
         	}
         	elseif ($savetype == "imagerendering_so")
         	{
            $add_onload =  "document.location.href='".$mgmt_config['url_path_cms']."image_rendering.php?site=".url_encode($site)."&cat=".url_encode($cat)."&location=".url_encode(convertpath($site, $location, $cat))."&page=".url_encode($page)."';\n";
         	}
         	elseif ($savetype == "mediarendering_so")
         	{
            $add_onload =  "document.location.href='".$mgmt_config['url_path_cms']."media_rendering.php?site=".url_encode($site)."&cat=".url_encode($cat)."&location=".url_encode(convertpath($site, $location, $cat))."&page=".url_encode($page)."';\n";
         	}
         	elseif ($savetype == "mediaplayerconfig_so")
         	{
            $add_onload =  "document.location.href='".$mgmt_config['url_path_cms']."media_playerconfig.php?site=".url_encode($site)."&cat=".url_encode($cat)."&location=".url_encode(convertpath($site, $location, $cat)).'&page='.url_encode($page)."';\n";
         	}                   
          else
         	{
            $add_onload =  "document.location.href='".$mgmt_config['url_path_cms']."page_view.php?view=".url_encode($view)."&site=".url_encode($site)."&cat=".url_encode($cat)."&db_connect=".url_encode($db_connect)."&location=".url_encode($location_esc)."&page=".url_encode($page)."&ctrlreload=no';\n";
         	}
          
         	// define message to display
         	$message = "<p class=hcmsHeadline>".$text16[$lang]."</p>\n";
         	$message .= "<a href=\"page_view.php?view=".url_encode($view)."&site=".url_encode($site)."&cat=".url_encode($cat)."&db_connect=".url_encode($db_connect)."&location=".url_encode($location_esc)."&page=".url_encode($page)."&ctrlreload=no\">".$text18[$lang]."</a>\n";
      	}
      }
  
      // ----------------------------------- write content into database --------------------------------------   
      // db_connect will save content in database 
      if (isset ($db_connect) && $db_connect != "") 
      {      
        // write data
        $test = db_write_container ($site, $contentfile, $contentdatanew, $user);    
        
        if ($test == false)
        {
          $errcode = "20101";
          $error[] = $mgmt_config['today']."|page_save.inc.php|error|$errcode|unable to save data of container '$contentfile' using db_connect '$db_connect'";          
        }
      }    
    }
    else
    {
      // define meta tag
      $add_onload =  "";
      // define message to display
      if ($auto)
      {
      	$message[] = $text17[$lang];
      }
      else
      {
      	$message = "<p class=hcmsHeadline>".$text17[$lang]."</p>\n<a href=\"page_view.php?site=".$site."&location=".$location_esc."&page=".$page."\">".$text18[$lang]."</a>";
      }
    }
  }
  // if content file isn't available
  elseif (!file_exists (getcontentlocation ($container_id, 'abs_path_content').$contentfile.".wrk"))
  {
    // define meta tag
    $add_onload =  "";
    //define message to display
    if ($auto)
    {
    	//define message to display
    	$message[] = $text19[$lang];
    	$message[] = $text20[$lang];
    	$message[] = $text21[$lang];
    }
    else
    {
    	$message = "<p class=hcmsHeadline>".$text19[$lang]."</p>\n".$text20[$lang]."<br />\n".$text21[$lang]."<br />\n";
    }
  }
  else
  {
    // define meta tag
    $add_onload =  "";
    //define message to display
    if ($auto) 
    {
  	  $message[] = $text19[$lang];
    }
    else
    {
    	//define message to display
    	$message = "<p class=hcmsHeadline>".$text19[$lang]."</p>\n";
    }
  }
}
else
{
  // define meta tag
  $add_onload =  "";
  //define message to display
  if ($auto) 
  {
	  $message[] = $text12[$lang];
  }
  else
  {
  	//define message to display
  	$message = "<p class=hcmsHeadline>".$text12[$lang]."</p>\n";
  }
}

// save log
savelog (@$error);

if ($auto)
{
  if ($usedby != "" && $usedby != $user)
  {
    $message[] = $text12[$lang];
  }
  
  // answer request from autosave
	echo json_encode (array('message' => implode(", ", $message)));  
}
else
{
?>
<!DOCTYPE html>
<html>
<head>
<title>hyperCMS</title>
<meta http-equiv="Content-Type" content="text/html; charset=<?php echo $lang_codepage[$lang]; ?>">
<link rel="stylesheet" href="<?php echo getthemelocation(); ?>css/main.css">
<script language="JavaScript">
<!--
<?php echo $add_onload; ?>
//-->
</script>
<script src="javascript/main.js" type="text/javascript"></script>
<script src="javascript/click.js" type="text/javascript"></script>
</head>
<body class="hcmsWorkplaceGeneric">
<table border="0" cellspacing="4" cellpadding="0">
  <tr>
    <td>
      <?php echo $message; ?>
   </td>
  </tr>
</table>
</body>
</html>
<?php 
}
?>