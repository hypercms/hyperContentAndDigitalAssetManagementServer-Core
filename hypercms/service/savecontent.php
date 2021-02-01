<?php
/*
 * This file is part of
 * hyper Content & Digital Management Server - http://www.hypercms.com
 * Copyright (c) by hyper CMS Content Management Solutions GmbH
 */
 
// session
define ("SESSION", "create");
// management configuration
require ("../config.inc.php");
// hyperCMS API
require ("../function/hypercms_api.inc.php");


// input parameters
$savetype = getrequest ("savetype");
$location = getrequest_esc ("location", "locationname");
$page = getrequest ("page", "objectname");



// input parameters
$savetype = getrequest ("savetype");
$autosave = getrequest ("autosave");
$appendcontent = getrequest ("appendcontent");
$forward = getrequest ("forward");
$view = getrequest ("view");
$contenttype = getrequest_esc ("contenttype");
$service = getrequest ("service");
$location = getrequest_esc ("location", "locationname");
$page = getrequest_esc ("page", "objectname");
$db_connect = getrequest_esc ("db_connect", "objectname");
$ctrlreload = getrequest_esc ("ctrlreload");
$tagname = getrequest_esc ("tagname", "objectname");
$id = getrequest_esc ("id", "objectname");
$toolbar = getrequest_esc ("toolbar");
$width = getrequest_esc ("width", "numeric");
$height = getrequest_esc ("height", "numeric");
$constraint = getrequest_esc ("constraint");
// token
$wf_token = getrequest_esc ("wf_token");
$token = getrequest ("token");

// get publication and category
$site = getpublication ($location);
$cat = getcategory ($site, $location);

// convert location
$location = deconvertpath ($location, "file");
$location_esc = convertpath ($site, $location, $cat);

// load object file and get container and media file information
$objectdata = loadfile ($location, $page);
$object_contentfile = getfilename ($objectdata, "content");
$object_mediafile = getfilename ($objectdata, "media");

// publication management config
if (valid_publicationname ($site)) require ($mgmt_config['abs_path_data']."config/".$site.".conf.php");
// load publication configuration
if (valid_publicationname ($site)) $publ_config = parse_ini_file ($mgmt_config['abs_path_rep']."config/".$site.".ini");

// ------------------------------ permission section --------------------------------

// check access permissions
$ownergroup = accesspermission ($site, $location, $cat);
$setlocalpermission = setlocalpermission ($site, $ownergroup, $cat);

// set required permissions for the service user (required for service savecontent, function buildview and function showmedia)
if ($service == "recognizefaces" && !empty ($user) && is_facerecognitionservice ($user))
{
  $setlocalpermission['root'] = 1;
  $setlocalpermission['create'] = 1;
}
  
if ($setlocalpermission['root'] != 1 || $setlocalpermission['create'] != 1 || !valid_publicationname ($site) || !valid_locationname ($location) || !valid_objectname ($page)) killsession ($user);

// check session of user
checkusersession ($user, false);

// --------------------------------- logic section ----------------------------------

$error = array();

// helper function for appending text content in multiedit mode
function appendcontent_helper ($xmlcontent, $text, $delimiter=" ")
{
  if (is_array ($text) && sizeof ($text) > 0 && !empty ($xmlcontent))
  {
    foreach ($text as $key=>$value)
    {
      // get content
      $temp = selectcontent ($xmlcontent, "<text>", "<text_id>", $key);
      if (!empty ($temp[0])) $temp_content = getcontent ($temp[0], "<textcontent>");
      
      // append content
      if (!empty ($temp_content[0])) $text[$key] = $temp_content[0].$delimiter.$value;
    }
  }
  
  return $text;
}

// define character set
if (empty ($object_mediafile))
{
  // extract character set from content-type
  $result_charset = getcharset ($site, $contenttype);

  if ($result_charset != false) $charset = $result_charset['charset'];
  else $charset = "";
}
// media assets require UTF-8
else $charset = "utf-8";

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

// only face definitions will be saved if the face recognition service is used
if ($service != "recognizefaces")
{
  // head
  if (isset ($_REQUEST['pagetitle'])) $pagetitle = getrequest ("pagetitle");
  if (isset ($_REQUEST['pageauthor'])) $pageauthor = getrequest ("pageauthor");
  if (isset ($_REQUEST['pagedescription'])) $pagedescription = getrequest ("pagedescription");
  if (isset ($_REQUEST['pagekeywords'])) $pagekeywords = getrequest ("pagekeywords");
  if (isset ($_REQUEST['pagecontenttype'])) $pagecontenttype = getrequest ("pagecontenttype");
  if (isset ($_REQUEST['pagelanguage'])) $pagelanguage = getrequest ("pagelanguage");
  if (isset ($_REQUEST['pagerevisit'])) $pagerevisit = getrequest ("pagerevisit");
  if (isset ($_REQUEST['pagetracking'])) $pagetracking = getrequest ("pagetracking");

  // article
  $arttitle = getrequest ("arttitle", "array");
  $artstatus = getrequest ("artstatus", "array");
  $artdatefrom = getrequest ("artdatefrom", "array");
  $artdateto = getrequest ("artdateto", "array");
  
  // link
  $linkhref = getrequest ("linkhref", "array");
  $linktarget = getrequest_esc ("linktarget", "array");
  $targetlist = getrequest_esc ("targetlist", "array");
  $linktext = getrequest ("linktext", "array");
  $artlinkhref = getrequest ("artlinkhref", "array");
  $artlinktarget = getrequest ("artlinktarget", "array");
  $artlinktext = getrequest ("artlinktext", "array");

  // text
  $textf = getrequest ("textf", "array");
  $arttextf = getrequest ("arttextf", "array");
  $textu = getrequest ("textu", "array");
  $arttextu = getrequest ("arttextu", "array");
  $textl = getrequest ("textl", "array");
  $arttextl = getrequest ("arttextl", "array");
  $textc = getrequest ("textc", "array");
  $arttextc = getrequest ("arttextc", "array");
  $textk = getrequest ("textk", "array");
  $arttextk = getrequest ("arttextk", "array");
  $value = getrequest_esc ("value");
  $textd = getrequest ("textd", "array");
  $arttextd = getrequest ("arttextd", "array");
  $format = getrequest_esc ("format");
  $commentu = getrequest ("commentu", "array");
  $commentf = getrequest ("commentf", "array");
  $texts = getrequest ("texts", "array");
  $arttexts = getrequest ("arttexts", "array");

  // media
  $mediacat = getrequest ("mediacat", "array");
  $mediafile = getrequest ("mediafile", "array");
  $mediaobject = getrequest ("mediaobject", "array");
  $mediaalttext = getrequest ("mediaalttext", "array");
  $mediaalign = getrequest ("mediaalign", "array");
  $mediawidth = getrequest ("mediawidth", "array");
  $mediaheight = getrequest ("mediaheight", "array");
  $mediatype = getrequest ("mediatype", "array");
  $artmediafile = getrequest ("artmediafile", "array");
  $artmediaobject = getrequest ("artmediaobject", "array");
  $artmediaalttext = getrequest ("artmediaalttext", "array");
  $artmediaalign = getrequest ("artmediaalign", "array");
  $artmediawidth = getrequest ("artmediawidth", "array");
  $artmediaheight = getrequest ("artmediaheight", "array");

  // component
  $component = getrequest ("component", "array");
  $artcomponent = getrequest ("artcomponent", "array");
  $components = getrequest ("components", "array");
  $artcomponents = getrequest ("artcomponents", "array");
  $componentm = getrequest ("componentm", "array");
  $artcomponentm = getrequest ("artcomponentm", "array");
  $condition = getrequest ("condition", "array");

  // geolocation
  $geolocation = getrequest ("geolocation");
}

// face definitions
$faces = getrequest ("faces");

// temporary preview images used for face definitions
$facesimage = getrequest ("previewimage");

// base64 encoded JPEG annotation image
$medianame = getrequest ("medianame");
$mediadata = getrequest ("mediadata");

// check locked by user
$result_containername = getcontainername ($object_contentfile);
$usedby = $result_containername['user'];

// if not locked by another user, security token is available and matches the crypted location of the object (absolute path in file system is used as input for encryption!)
if (($usedby == "" || $usedby == $user) && checktoken ($token, $user) && valid_locationname ($location) && valid_objectname ($page))
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

  // load content container
  $container_id = substr ($object_contentfile, 0, strpos ($object_contentfile, ".xml"));
  $contentdata = loadcontainer ($container_id, "work", $user);

  // check if content is not empty
  if ($contentdata != false)
  {
    $contentdatanew = $contentdata;
  
    // check if date-from is greater than date-to in article content
    if (isset ($artstatus) && is_array ($artstatus))
    {
      foreach ($artstatus as $artid => $temp)
      {
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
            echo "<!DOCTYPE html>\n";
            echo "<html lang=\"".getsession("hcms_lang", "en")."\">\n";
            echo "<head>\n";
            echo "<title>hyperCMS</title>\n";
            echo "<meta charset=\"".getcodepage($lang)."\" />\n";
            echo "<link rel=\"stylesheet\" href=\"".getthemelocation()."css/main.css\">\n";
            echo "<link rel=\"stylesheet\" href=\"".getthemelocation()."css/".($is_mobile ? "mobile.css" : "desktop.css")."\" />\n";
            echo "</head>\n";
            echo "<body class=\"hcmsWorkplaceGeneric\">\n";
            echo "<p class=\"hcmsHeadline\">".$hcms_lang['the-end-date-is-before-the-start-date-of-the-article'][$lang]."</p>\n";
            echo $hcms_lang['please-go-back-and-correct-the-date-settings'][$lang]."\n";
            echo "<a href=\"#\" onlick=\"history.back();\">".$hcms_lang['back'][$lang]."</a><br />\n";
            echo "</body>\n</html>";
            exit;
          }
        }
        
        // correct dates based on users time zone
        if (!empty ($_SESSION['hcms_timezone']) && ini_get ('date.timezone'))
        {
          if ($artdatefrom[$artid] != "")
          {
            $datenew = convertdate ($artdatefrom[$artid], $_SESSION['hcms_timezone'], "Y-m-d H:i", ini_get ('date.timezone'), "Y-m-d H:i");
            if (!empty ($datenew)) $artdatefrom[$artid] = $datenew;
          }
          
          if ($artdateto[$artid] != "")
          {
            $datenew = convertdate ($artdateto[$artid], $_SESSION['hcms_timezone'], "Y-m-d H:i", ini_get ('date.timezone'), "Y-m-d H:i");
            if (!empty ($datenew)) $artdateto[$artid] = $datenew;
          }
        }
      }

      // set atricle
      if ($contentdatanew != false)
      {
        $contentdatanew = setarticle ($site, $contentdatanew, $object_contentfile, $arttitle, $artstatus, $artdatefrom, $artdateto, $user, $user);
      }
    }

    // ----------------------------------- write content -------------------------------------- 
  
    // face detection data
    if (isset ($faces))
    {
      // remove empty entries
      $textu['Faces-JSON'] = str_replace (", , ", ", ", $faces);
    }

    // taxonomy tree selector returns an array
    $lang_taxonomy = array();

    if (isset ($textk) && is_array ($textk))
    {
      foreach ($textk as $key => $value)
      {
        // get and set language for text ID
        if (!empty ($value['language']))
        {
          $lang_taxonomy[$key] = $value['language'];
          unset ($textk[$key]['language']);
          $value = $textk[$key];
        }

        // create unique keywords and convert array to comma separated keyword list
        if (is_array ($value))
        {
          $value = implode (",", $value);
          $value = explode (",", $value);
          $value = array_unique ($value);
          $textk[$key] = implode (",", $value);
        }
        else
        {
          $value = explode (",", $value);
          $value = array_unique ($value);
          $textk[$key] = implode (",", $value);
        }
      }
    }

    if (isset ($arttextk) && is_array ($arttextk))
    {
      foreach ($arttextk as $key => $value)
      {
        // get and set language for text ID
        if (!empty ($value['language']))
        {
          $lang_taxonomy[$key] = $value['language'];
          unset ($arttextk[$key]['language']);
          $value = $textk[$key];
        }

        // create unique keywords and convert array to comma separated keyword list
        if (is_array ($value))
        {
          $value = implode (",", $value);
          $value = explode (",", $value);
          $value = array_unique ($value);
          $arttextk[$key] = implode (",", $value);
        }
        else
        {
          $value = explode (",", $value);
          $value = array_unique ($value);
          $arttextk[$key] = implode (",", $value);
        }
      }
    }

    // append content (only for textu, textf, textk)
    if (!empty ($appendcontent))
    {
      $textf = appendcontent_helper ($contentdatanew, $textf);
      $arttextf = appendcontent_helper ($contentdatanew, $arttextf);
      $textu = appendcontent_helper ($contentdatanew, $textu);
      $arttextu = appendcontent_helper ($contentdatanew, $arttextu);
      $textk = appendcontent_helper ($contentdatanew, $textk, ",");
      $arttextk = appendcontent_helper ($contentdatanew, $arttextk, ",");
    }

    // text content
    if (isset ($textf) && is_array ($textf) && $contentdatanew != false) $contentdatanew = settext ($site, $contentdatanew, $object_contentfile, $textf, "f", "no", $user, $user, $charset);
    if (isset ($arttextf) && is_array ($arttextf) && $contentdatanew != false) $contentdatanew = settext ($site, $contentdatanew, $object_contentfile, $arttextf, "f", "yes", $user, $user, $charset);
    if (isset ($textu) && is_array ($textu) && $contentdatanew != false) $contentdatanew = settext ($site, $contentdatanew, $object_contentfile, $textu, "u", "no", $user, $user, $charset);
    if (isset ($arttextu) && is_array ($arttextu) && $contentdatanew != false) $contentdatanew = settext ($site, $contentdatanew, $object_contentfile, $arttextu, "u", "yes", $user, $user, $charset);
    if (isset ($textl) && is_array ($textl) && $contentdatanew != false) $contentdatanew = settext ($site, $contentdatanew, $object_contentfile, $textl, "l", "no", $user, $user, $charset);
    if (isset ($arttextl) && is_array ($arttextl) && $contentdatanew != false) $contentdatanew = settext ($site, $contentdatanew, $object_contentfile, $arttextl, "l", "yes", $user, $user, $charset);
    if (isset ($textc) && is_array ($textc) && $contentdatanew != false) $contentdatanew = settext ($site, $contentdatanew, $object_contentfile, $textc, "c", "no", $user, $user, $charset);
    if (isset ($arttextc) && is_array ($arttextc) && $contentdatanew != false) $contentdatanew = settext ($site, $contentdatanew, $object_contentfile, $arttextc, "c", "yes", $user, $user, $charset);
    if (isset ($textd) && is_array ($textd) && $contentdatanew != false) $contentdatanew = settext ($site, $contentdatanew, $object_contentfile, $textd, "d", "no", $user, $user, $charset);
    if (isset ($arttextd) && is_array ($arttextd) && $contentdatanew != false) $contentdatanew = settext ($site, $contentdatanew, $object_contentfile, $arttextd, "d", "yes", $user, $user, $charset);
    if (isset ($texts) && is_array ($texts) && $contentdatanew != false) $contentdatanew = settext ($site, $contentdatanew, $object_contentfile, $texts, "s", "no", $user, $user, $charset);
    if (isset ($arttexts) && is_array ($arttexts) && $contentdatanew != false) $contentdatanew = settext ($site, $contentdatanew, $object_contentfile, $arttexts, "s", "yes", $user, $user, $charset);
    // keywords usually only apply for metadata templates (support for articles added in version 8.1.3)
    if (isset ($textk) && is_array ($textk) && $contentdatanew != false) $contentdatanew = settext ($site, $contentdatanew, $object_contentfile, $textk, "k", "no", $user, $user, $charset);
    if (isset ($arttextk) && is_array ($arttextk) && $contentdatanew != false) $contentdatanew = settext ($site, $contentdatanew, $object_contentfile, $arttextk, "k", "yes", $user, $user, $charset);
    // only if autosaving is not used
    if (isset ($commentu) && is_array ($commentu) && $contentdatanew != false && $auto == false) $contentdatanew = settext ($site, $contentdatanew, $object_contentfile, $commentu, "u", "no", $user, $user, $charset, true);
    if (isset ($commentf) && is_array ($commentf) && $contentdatanew != false && $auto == false) $contentdatanew = settext ($site, $contentdatanew, $object_contentfile, $commentf, "f", "no", $user, $user, $charset, true);

    // write meta data to media file
    if (trim ($object_mediafile) != "")
    {
      // define text array
      $textmeta = array();
      
      if (isset ($textf) && is_array ($textf)) $textmeta = array_merge ($textmeta, $textf);
      if (isset ($textu) && is_array ($textu)) $textmeta = array_merge ($textmeta, $textu);
      if (isset ($textl) && is_array ($textl)) $textmeta = array_merge ($textmeta, $textl);
      if (isset ($textc) && is_array ($textc)) $textmeta = array_merge ($textmeta, $textc);
      if (isset ($textd) && is_array ($textd)) $textmeta = array_merge ($textmeta, $textd);
      if (isset ($textk) && is_array ($textk)) $textmeta = array_merge ($textmeta, $textk);

      // get media file location and name
      $mediafile_location = getmedialocation ($site, $object_mediafile, "abs_path_media");
      $mediafile_name = $object_mediafile;

      // get thumbnail file location
      $thumbfile_location = getmedialocation ($site, ".hcms.".$object_mediafile, "abs_path_media").$site."/";

      // correct path to media file
      if (!is_file ($mediafile_location.$mediafile_name)) $mediafile_location = $mediafile_location.$site."/";

      // prepare media file
      $temp = preparemediafile ($site, $mediafile_location, $mediafile_name, $user);
      
      // if encrypted
      if (!empty ($temp['result']) && !empty ($temp['crypted']))
      {
        $object_mediafile = $temp['templocation'].$temp['tempfile'];
      }
      // if restored
      elseif (!empty ($temp['result']) && !empty ($temp['restored']))
      {
        $object_mediafile = $temp['location'].$temp['file'];
      }
      else
      {
        $object_mediafile = $mediafile_location.$mediafile_name;
      }

      if (is_file ($object_mediafile))
      {
        // ------------------------------- write annotation image ----------------------------------   

        if (!empty ($medianame) && !empty ($mediadata))
        {
          // if symbolic link
          if (is_link ($thumbfile_location.$medianame))
          {
            $target_path = readlink ($thumbfile_location.$medianame);
            $target_location = getlocation ($target_path);
          }
          else $target_location = $thumbfile_location;
          
          $annotationfile = base64_to_file ($mediadata, $target_location, $medianame);

          // save to cloud storage
          if (!empty ($annotationfile) && function_exists ("savecloudobject")) savecloudobject ($site, $thumbfile_location, $medianame, $user);
        }

        // -------------------------- create face images for face recognition ----------------------------   

        if (!empty ($mgmt_config['facerecognition']) && valid_publicationname ($site) && !empty ($textu['Faces-JSON']) && intval ($container_id) > 0 && !empty ($facesimage))
        {
          // create directory if it doesn't exist and create one initial face description
          if (!is_dir ($mgmt_config['abs_path_rep']."faces/"))
          {
            mkdir ($mgmt_config['abs_path_rep']."faces", $mgmt_config['fspermission']);

            // save one initial face description
            mkdir ($mgmt_config['abs_path_rep']."faces/Linus Torvald", $mgmt_config['fspermission']);
            savefile ($mgmt_config['abs_path_rep']."faces/", "Linus Torvald", '{"0":-0.025545477867126465,"1":0.18196013569831848,"2":0.04162745177745819,"3":-0.015117228962481022,"4":-0.08639691770076752,"5":0.03754429891705513,"6":-0.11095122992992401,"7":-0.06387850642204285,"8":0.023463984951376915,"9":0.03505522012710571,"10":0.25074344873428345,"11":-0.05879448726773262,"12":-0.26391178369522095,"13":-0.046631697565317154,"14":-0.02024192176759243,"15":0.03481253609061241,"16":-0.1658017635345459,"17":-0.14006587862968445,"18":-0.1170262023806572,"19":-0.1377565860748291,"20":0.030783381313085556,"21":0.16799373924732208,"22":-0.028317002579569817,"23":-0.004740369506180286,"24":-0.17413951456546783,"25":-0.34339791536331177,"26":-0.037621356546878815,"27":-0.1476910263299942,"28":0.07011697441339493,"29":-0.1492389440536499,"30":0.05077751353383064,"31":0.028455648571252823,"32":-0.1764737069606781,"33":-0.03535778820514679,"34":0.10806383192539215,"35":0.06640634685754776,"36":-0.095072440803051,"37":-0.06606271117925644,"38":0.18334102630615234,"39":0.06914245337247849,"40":-0.08720245212316513,"41":0.06660261005163193,"42":-0.012647910043597221,"43":0.34849804639816284,"44":0.22583432495594025,"45":0.012289615347981453,"46":0.04521983861923218,"47":-0.10503574460744858,"48":0.07887865602970123,"49":-0.2374831587076187,"50":0.12012508511543274,"51":0.2592533528804779,"52":0.13322389125823975,"53":0.10802897065877914,"54":0.11885374784469604,"55":-0.19762767851352692,"56":-0.044888686388731,"57":0.09119429439306259,"58":-0.1342979520559311,"59":0.04342387616634369,"60":0.013162299990653992,"61":0.013764120638370514,"62":-0.011066252365708351,"63":-0.12576131522655487,"64":0.13944371044635773,"65":0.03497812896966934,"66":-0.10891371965408325,"67":-0.17591814696788788,"68":0.12249823659658432,"69":-0.16213296353816986,"70":-0.11607933044433594,"71":0.13246096670627594,"72":-0.1134871169924736,"73":-0.13450774550437927,"74":-0.278130441904068,"75":0.06644099950790405,"76":0.3682076930999756,"77":0.06290056556463242,"78":-0.28703638911247253,"79":-0.016227615997195244,"80":-0.01985451765358448,"81":-0.049234796315431595,"82":0.03776220604777336,"83":0.03873123973608017,"84":-0.10208074003458023,"85":-0.03294253721833229,"86":-0.06270529329776764,"87":0.01989150233566761,"88":0.21765193343162537,"89":-0.025546802207827568,"90":-0.08158847689628601,"91":0.19256293773651123,"92":-0.035967644304037094,"93":-0.004598066210746765,"94":0.1634657382965088,"95":0.042749859392642975,"96":-0.10293117165565491,"97":-0.059233326464891434,"98":-0.07690432667732239,"99":-0.029112277552485466,"100":-0.0024777711369097233,"101":-0.15303584933280945,"102":-0.09634450078010559,"103":0.1492297202348709,"104":-0.16349810361862183,"105":0.21243564784526825,"106":-0.01629539206624031,"107":-0.03927500545978546,"108":-0.05085344612598419,"109":-0.06510446965694427,"110":-0.08781851083040237,"111":0.007650856394320726,"112":0.2872977554798126,"113":-0.30488112568855286,"114":0.20648981630802155,"115":0.16686449944972992,"116":0.01211613230407238,"117":0.14038996398448944,"118":0.056123048067092896,"119":0.04359572380781174,"120":-0.07021744549274445,"121":0.0018601713236421347,"122":-0.20308788120746613,"123":-0.12833504378795624,"124":-0.028929460793733597,"125":-0.014384389854967594,"126":-0.0851394385099411,"127":-0.03180805593729019}');
          }

          // get image path
          $facesimage = hcms_decrypt ($facesimage);

          // decode JSON string
          $faces_array = json_decode ($textu['Faces-JSON'], true);

          // create face images
          if (is_array ($faces_array) && is_image ($facesimage) && is_file ($facesimage) && is_dir ($mgmt_config['abs_path_rep']."faces"))
          {
            foreach ($faces_array as $temp)
            {
              // only use face image that qualifies in size and have been verified/detected
              if (is_array ($temp) && !empty ($temp['face']) && !empty ($temp['name']) && !empty ($temp['width']) && $temp['width'] > 75 && !empty ($temp['height']) && $temp['height'] > 95 && !empty ($temp['y']) && !empty ($temp['y']))
              {
                // define path to the face image file that will be created
                $facefile = $mgmt_config['abs_path_rep']."faces/".$temp['name']."/".$container_id."@".$site.".jpg";

                if (!is_file ($facefile) || filemtime ($facefile) < filemtime ($facesimage))
                {
                  // dimensions and coordinates
                  $w = round (intval ($temp['width']) * 2);
                  $h = round (intval ($temp['height']) * 2);
                  $x = intval ($temp['x']) - intval ($temp['width']) / 2;
                  $y = intval ($temp['y']) - intval ($temp['height']) / 2;

                  // disable watermarking and crop image
                  $mgmt_imageoptions['.jpg.jpeg']['face'] = "-wm none -s ".$w."x".$h." -c ".$x."x".$y;

                  $faceimage = createmedia ($site, getlocation ($facesimage), $mgmt_config['abs_path_temp'], getobject ($facesimage), "jpg", "face", true, false);

                  // create directory if it doesn't exist (UTF-8 support required since special characters in the name will not be escaped)
                  if (!is_dir ($mgmt_config['abs_path_rep']."faces/".$temp['name']))
                  {
                    mkdir ($mgmt_config['abs_path_rep']."faces/".$temp['name'], $mgmt_config['fspermission']);

                    // all images need to be analyzed again after a new face label has been defined
                    rdbms_resetanalyzed ();
                  }

                  // set analyzed attribute
                  rdbms_setmedia ($container_id, "", "", "", "", "", "", "", "", "", "", true);

                  // move image to faces collection (save as file name: container_id@publication.jpg)
                  if (!empty ($faceimage) && is_file ($mgmt_config['abs_path_temp'].$faceimage) && is_dir ($mgmt_config['abs_path_rep']."faces/".$temp['name']))
                  {
                    rename ($mgmt_config['abs_path_temp'].$faceimage, $facefile);
                  }
                }
              }
            }
          }
        }

        // -------------------------- create marker images for videos ----------------------------   

        if ((!empty ($mgmt_config['facerecognition']) || !empty ($mgmt_config['annotation'])) && intval ($container_id) > 0)
        {
          // create face images
          if (!empty ($textu['Faces-JSON']) && is_video ($mediafile_name))
          {
            $file_info = getfileinfo ($site, $mediafile_name, $cat);

            // use preview video of original media file
            if (is_file ($thumbfile_location.$file_info['filename'].".orig.mp4") || is_cloudobject ($thumbfile_location.$file_info['filename'].".orig.mp4"))
            {
              $videofile_name = $file_info['filename'].".orig.mp4";
            }
            // use original video file
            else
            {
              $videofile_name = $mediafile_name;
            }

            // create directory for thumbnails
            if (!is_dir ($thumbfile_location.$container_id)) mkdir ($thumbfile_location.$container_id);

            if (is_dir ($thumbfile_location.$container_id))
            {
              // save JSON file for video player
              savefile ($thumbfile_location.$container_id."/", "faces.json", $textu['Faces-JSON']);

              // decode JSON string
              $faces = json_decode ($textu['Faces-JSON'], true);
              $temp_faces = array();

              if (is_array ($faces))
              {
                foreach ($faces as $face)
                {
                  if (!empty ($face['time']))
                  {
                    // create image named faces-[timestamp in sec].jpg for video player
                    if (!is_file ($thumbfile_location.$container_id."/face-".$face['time'].".jpg"))
                    {
                      // function createthumbnail_video also saves the created file in the cloud
                      // to keep the aspect ratio, we need to specify only one component, either width or height, and set the other component to -1
                      createthumbnail_video ($site, $mediafile_location, $thumbfile_location.$container_id."/", $videofile_name, $face['time'], -1, 240, "face-".$face['time']);
                    }

                    $temp_faces[] = "face-".$face['time'].".jpg";
                  }
                }
              }

              // remove existing old face images
              $scandir = scandir ($thumbfile_location.$container_id);

              if (is_array ($scandir) && sizeof ($scandir) > 0)
              {
                foreach ($scandir as $temp)
                {
                  if ($temp != "." && $temp != ".." && $temp != "faces.json" && substr ($temp, 0, 9) != "thumbnail" && is_file ($thumbfile_location.$container_id."/".$temp) && !in_array ($temp, $temp_faces))
                  {
                    deletefile ($thumbfile_location.$container_id."/", $temp, false);
                  }
                }
              }
            }
          }
          // remove face images
          elseif (is_dir ($thumbfile_location.$container_id))
          {
            // delete JSON file for video player
            deletefile ($thumbfile_location.$container_id."/", "faces.json", false);

            // remove existing face images
            $scandir = scandir ($thumbfile_location.$container_id);

            if (is_array ($scandir) && sizeof ($scandir) > 0)
            {
              foreach ($scandir as $temp)
              {
                if ($temp != "." && $temp != ".." && substr ($temp, 0, 9) != "thumbnail" && is_file ($thumbfile_location.$container_id."/".$temp))
                {
                  deletefile ($thumbfile_location.$container_id."/", $temp, false);
                }
              }
            }
          }
        }

        // ----------------------------------- write metadata --------------------------------------  
        
        // write IPTC data to media file
        $result_iptc = false;
        
        if (!empty ($mgmt_config['iptc_save']))
        {
          $iptc = iptc_create ($site, $textmeta);

          if (is_array ($iptc))
          {
            $result_iptc = iptc_writefile ($object_mediafile, $iptc, true, false);
          }
        }
        
        // write XMP data to media file
        $result_xmp = false;
        
        if (!empty ($mgmt_config['xmp_save']))
        {
          $xmp = xmp_create ($site, $textmeta);

          if (is_array ($xmp))
          {
            $result_xmp = xmp_writefile ($object_mediafile, $xmp, true, false);   
          }
        }
        
        // write ID3 data to media file
        $result_id3 = false;
        
        if (!empty ($mgmt_config['id3_save']))
        { 
          $id3 = id3_create ($site, $textmeta);

          if (is_array ($id3))
          {
            $result_id3 = id3_writefile ($object_mediafile, $id3, true, false);   
          }
        }
        
        // touch thumbnail file of documents to update the timestamp / avoid recreation of annotation images)
        if (is_document ($object_mediafile))
        {
          // get file name without extensions
          $object_thumbfile = strrev (substr (strstr (strrev ($object_mediafile), "."), 1)).".thump.pdf";
          
          // update timestamp
          if (is_file ($object_thumbfile)) touch ($object_thumbfile);
        }
          
        // save media stats and move temp file on success
        if (!empty ($result_iptc) || !empty ($result_xmp) || !empty ($result_id3))
        {
          // write updated media information to DB
          if (!empty ($container_id))
          {
            $md5_hash = md5_file ($object_mediafile);
            $filesize = round (@filesize ($object_mediafile) / 1024, 0);

            // don't save the actual MD5 hash of the file since the search for duplicates is based on the MD5 hash
            rdbms_setmedia ($container_id, $filesize, "", "", "", "", "", "", "", "", "");
          }
          
          // encrypt and save file if required
          if (!empty ($temp['result'])) movetempfile ($mediafile_location, $mediafile_name, true);

          // save to cloud storage
          if (function_exists ("savecloudobject")) savecloudobject ($site, $mediafile_location, $mediafile_name, $user);
        }
        
        // set modified date in DB
        rdbms_setcontent ($site, $container_id);
      }
    }

    // media content
    if ($contentdatanew != false && isset ($mediafile) && is_array ($mediafile)) $contentdatanew = setmedia ($site, $contentdatanew, $object_contentfile, $mediafile, $mediaobject, $mediaalttext, $mediaalign, $mediawidth, $mediaheight, "no", $user, $user, $charset);
    if ($contentdatanew != false && isset ($artmediafile) && is_array ($artmediafile)) $contentdatanew = setmedia ($site, $contentdatanew, $object_contentfile, $artmediafile, $artmediaobject, $artmediaalttext, $artmediaalign, $artmediawidth, $artmediaheight, "yes", $user, $user, $charset);

    // page link content
    if ($contentdatanew != false && isset ($linkhref) && is_array ($linkhref)) $contentdatanew = setpagelink ($site, $contentdatanew, $object_contentfile, $linkhref, $linktarget, $linktext, "no", $user, $user, $charset);
    if ($contentdatanew != false && isset ($artlinkhref) && is_array ($artlinkhref)) $contentdatanew = setpagelink ($site, $contentdatanew, $object_contentfile, $artlinkhref, $artlinktarget, $artlinktext, "yes", $user, $user, $charset);    

    // component content
    if ($contentdatanew != false && isset ($component) && is_array ($component)) $contentdatanew = setcomplink ($site, $contentdatanew, $object_contentfile, $component, $condition, "no", $user, $user);
    if ($contentdatanew != false && isset ($artcomponent) && is_array ($artcomponent)) $contentdatanew = setcomplink ($site, $contentdatanew, $object_contentfile, $artcomponent, $condition, "yes", $user, $user);    
    if ($contentdatanew != false && isset ($components) && is_array ($components)) $contentdatanew = setcomplink ($site, $contentdatanew, $object_contentfile, $components, $condition, "no", $user, $user);
    if ($contentdatanew != false && isset ($artcomponents) && is_array ($artcomponents)) $contentdatanew = setcomplink ($site, $contentdatanew, $object_contentfile, $artcomponents, $condition, "yes", $user, $user);    
    if ($contentdatanew != false && isset ($componentm) && is_array ($componentm)) $contentdatanew = setcomplink ($site, $contentdatanew, $object_contentfile, $componentm, $condition, "no", $user, $user);
    if ($contentdatanew != false && isset ($artcomponentm) && is_array ($artcomponentm)) $contentdatanew = setcomplink ($site, $contentdatanew, $object_contentfile, $artcomponentm, $condition, "yes", $user, $user);    

    // head content
    if (isset ($pagetitle)) $headcontent['pagetitle'] = $pagetitle;
    if (isset ($pageauthor)) $headcontent['pageauthor'] = $pageauthor;
    if (isset ($pagedescription)) $headcontent['pagedescription'] = $pagedescription;
    if (isset ($pagekeywords)) $headcontent['pagekeywords'] = $pagekeywords;
    if (isset ($pagecontenttype)) $headcontent['pagecontenttype'] = $pagecontenttype;
    if (isset ($pagelanguage)) $headcontent['pagelanguage'] = $pagelanguage;
    if (isset ($pagerevisit)) $headcontent['pagerevisit'] = $pagerevisit;
    if (isset ($pagetracking)) $headcontent['pagetracking'] = $pagetracking;

    if ($contentdatanew != false && isset ($headcontent) && is_array ($headcontent)) $contentdatanew = sethead ($site, $contentdatanew, $object_contentfile, $headcontent, $user);

    // geo location
    if (!empty ($geolocation))
    {
      list ($latitude, $longitude) = explode (",", $geolocation);

      $sql = "UPDATE container SET latitude=".floatval($latitude).", longitude=".floatval($longitude)." WHERE id=".intval($container_id);                
      $result = rdbms_externalquery ($sql);
    }

    // ----------------------------------- write data into content container --------------------------------------
    if ($contentdatanew != false)
    {
      // create new version of content on save
      if (!empty ($mgmt_config['contentversions']) && !empty ($mgmt_config['contentversions_all']))
      {
        createversion ($site, $object_contentfile);
      }
    
      // eventsystem
      if ($eventsystem['onsaveobject_pre'] == 1 && (!isset ($eventsystem['hide']) || $eventsystem['hide'] == 0)) 
      {
        $contentdataevent = onsaveobject_pre ($site, $cat, $location, $page, $object_contentfile, $contentdatanew, $user);

        // check if event returns a string, if so, the event returns the container and not true or false 
        if (!empty ($contentdataevent) && strlen ($contentdataevent) > 10) $contentdatanew = $contentdataevent;
      }

      // insert new date into content file
      $contentdatanew = setcontent ($contentdatanew, "<hyperCMS>", "<contentdate>", $mgmt_config['today'], "", "");

      // set/change encoding (not for assets)
      $charset_old = getcharset ("", $contentdatanew);

      if (empty ($charset_old['charset']) || strtolower ($charset_old['charset']) != strtolower ($charset))
      {
        // write XML declaration parameter for text encoding
        if ($charset != "") $contentdatanew = setxmlparameter ($contentdatanew, "encoding", $charset);
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
        	$message[] = $hcms_lang['you-do-not-have-write-permissions-for-the-content-container'][$lang];
        	$message[] = $hcms_lang['without-write-permission-the-content-cant-be-edited'][$lang];
        }
        else
        {
  	      //define message to display
      	  $message = "<p class=\"hcmsHeadline\">".$hcms_lang['you-do-not-have-write-permissions-for-the-content-container'][$lang]."</p>\n".$hcms_lang['without-write-permission-the-content-cant-be-edited'][$lang]."<br />\n";
        }
      }
      else
      {
        // set taxonomy
        settaxonomy ($site, $container_id, $lang_taxonomy);

        // set keywords
        rdbms_setkeywords ($site, $container_id);

        // eventsystem
        if ($eventsystem['onsaveobject_post'] == 1 && (!isset ($eventsystem['hide']) || $eventsystem['hide'] == 0))
        {
          $contentdataevent = onsaveobject_post ($site, $cat, $location, $page, $object_contentfile, $contentdatanew, $user);
        }
        
        // check if event returns a string, if so, the event returns the container and not true or false 
        if (!empty ($contentdataevent) && strlen ($contentdataevent) > 10) $contentdatanew = $contentdataevent;

        // information log
        $errcode = "00101";
        $error[] = date('Y-m-d H:i')."|savecontent.php|information|".$errcode."|object '".$location_esc.$page."' has been edited and saved by user '".$user."'";

        // notification
        notifyusers ($site, $location, $page, "onedit", $user);

      	if (!$auto)
        {
         	// define forward to URL
         	if ($savetype == "editorf_so" || $savetype == "editorf_wysiwyg")
          {
         	  $add_onload =  "document.location='".cleandomain ($mgmt_config['url_path_cms'])."text_edit_format.php?site=".url_encode($site)."&cat=".url_encode($cat)."&db_connect=".url_encode($db_connect)."&location=".url_encode($location_esc)."&page=".url_encode($page)."&tagname=".url_encode($tagname)."&id=".url_encode($id)."&contenttype=".url_encode($contenttype)."&width=".url_encode($width)."&height=".url_encode($height)."&toolbar=".url_encode($toolbar)."';\n";
         	}
          elseif ($savetype == "editoru_so")
         	{
            $add_onload =  "document.location='".cleandomain ($mgmt_config['url_path_cms'])."text_edit_unformat.php?site=".url_encode($site)."&cat=".url_encode($cat)."&db_connect=".url_encode($db_connect)."&location=".url_encode($location_esc)."&page=".url_encode($page)."&tagname=".url_encode($tagname)."&id=".url_encode($id)."&constraint=".url_encode($constraint)."&contenttype=".url_encode($contenttype)."&width=".url_encode($width)."&height=".url_encode($height)."';\n";
         	}
          elseif ($savetype == "editorl_so")
         	{
            $add_onload =  "document.location='".cleandomain ($mgmt_config['url_path_cms'])."text_editor_list.php?site=".url_encode($site)."&cat=".url_encode($cat)."&db_connect=".url_encode($db_connect)."&location=".url_encode($location_esc)."&page=".url_encode($page)."&tagname=".url_encode($tagname)."&id=".url_encode($id)."&list=".url_encode($list)."&contenttype=".url_encode($contenttype)."';\n";
         	}
          elseif ($savetype == "editorc_so")
         	{
            $add_onload =  "document.location='".cleandomain ($mgmt_config['url_path_cms'])."text_edit_checkbox.php?site=".url_encode($site)."&cat=".url_encode($cat)."&db_connect=".url_encode($db_connect)."&location=".url_encode($location_esc)."&page=".url_encode($page)."&tagname=".url_encode($tagname)."&id=".url_encode($id)."&value=".url_encode($value)."&contenttype=".url_encode($contenttype)."';\n";
         	}
          elseif ($savetype == "editord_so")
         	{
            $add_onload =  "document.location='".cleandomain ($mgmt_config['url_path_cms'])."text_edit_date.php?site=".url_encode($site)."&cat=".url_encode($cat)."&db_connect=".url_encode($db_connect)."&location=".url_encode($location_esc)."&page=".url_encode($page)."&tagname=".url_encode($tagname)."&id=".url_encode($id)."&format=".url_encode($format)."&contenttype=".url_encode($contenttype)."&wf_token=".url_encode($wf_token)."';\n";
          }
          elseif ($savetype == "editors_so")
         	{
            $add_onload =  "document.location='".cleandomain ($mgmt_config['url_path_cms'])."text_edit_signature.php?site=".url_encode($site)."&cat=".url_encode($cat)."&db_connect=".url_encode($db_connect)."&location=".url_encode($location_esc)."&page=".url_encode($page)."&tagname=".url_encode($tagname)."&id=".url_encode($id)."&constraint=".url_encode($constraint)."&contenttype=".url_encode($contenttype)."&width=".url_encode($width)."&height=".url_encode($height)."';\n";
         	}
          elseif ($savetype == "form_so")
         	{
            if ($forward == "") $add_onload =  "document.location='".cleandomain ($mgmt_config['url_path_cms'])."page_view.php?view=".url_encode($view)."&site=".url_encode($site)."&cat=".url_encode($cat)."&db_connect=".url_encode($db_connect)."&location=".url_encode($location_esc)."&page=".url_encode($page)."&wf_token=".url_encode($wf_token)."';\n";
            else $add_onload = "document.location='".$forward."';\n";
         	}
         	elseif ($savetype == "form_sc")
         	{
            $add_onload =  "document.location='".cleandomain ($mgmt_config['url_path_cms'])."page_view.php?view=cmsview&site=".url_encode($site)."&cat=".url_encode($cat)."&db_connect=".url_encode($db_connect)."&location=".url_encode($location_esc)."&page=".url_encode($page)."&wf_token=".url_encode($wf_token)."';\n";
          }
         	elseif ($savetype == "documentviewerconfig_so")
         	{
            $add_onload =  "document.location='".cleandomain ($mgmt_config['url_path_cms'])."document_viewerconfig.php?site=".url_encode($site)."&cat=".url_encode($cat)."&location=".url_encode(convertpath($site, $location, $cat)).'&page='.url_encode($page)."&title=".(!empty ($textu['Title']) ? url_encode($textu['Title']) : "")."&wf_token=".url_encode($wf_token)."';\n";
         	} 
         	elseif ($savetype == "imagerendering_so")
         	{
            // define image editor
            if (!empty ($mgmt_config['imageeditor']) && strtolower ($mgmt_config['imageeditor']) == "minipaint") $imageeditor = "image_minipaint.php";
            else $imageeditor = "image_rendering.php";

            $add_onload =  "document.location='".cleandomain ($mgmt_config['url_path_cms']).$imageeditor."?site=".url_encode($site)."&cat=".url_encode($cat)."&location=".url_encode(convertpath($site, $location, $cat))."&page=".url_encode($page)."&wf_token=".url_encode($wf_token)."';\n";
          }
         	elseif ($savetype == "imageviewerconfig_so")
         	{
            $add_onload =  "document.location='".cleandomain ($mgmt_config['url_path_cms'])."image_viewerconfig.php?site=".url_encode($site)."&cat=".url_encode($cat)."&location=".url_encode(convertpath($site, $location, $cat)).'&page='.url_encode($page)."&title=".(!empty ($textu['Title']) ? url_encode($textu['Title']) : "")."&wf_token=".url_encode($wf_token)."';\n";
         	} 
         	elseif ($savetype == "mediarendering_so")
         	{
            $add_onload =  "document.location='".cleandomain ($mgmt_config['url_path_cms'])."media_rendering.php?site=".url_encode($site)."&cat=".url_encode($cat)."&location=".url_encode(convertpath($site, $location, $cat))."&page=".url_encode($page)."&wf_token=".url_encode($wf_token)."';\n";
          } 
         	elseif ($savetype == "mediaplayerconfig_so")
         	{
            $add_onload =  "document.location='".cleandomain ($mgmt_config['url_path_cms'])."media_playerconfig.php?site=".url_encode($site)."&cat=".url_encode($cat)."&location=".url_encode(convertpath($site, $location, $cat)).'&page='.url_encode($page)."&title=".(!empty ($textu['Title']) ? url_encode($textu['Title']) : "")."&wf_token=".url_encode($wf_token)."';\n";
         	}                   
          else
         	{
            $add_onload =  "document.location='".cleandomain ($mgmt_config['url_path_cms'])."page_view.php?view=".url_encode($view)."&site=".url_encode($site)."&cat=".url_encode($cat)."&db_connect=".url_encode($db_connect)."&location=".url_encode($location_esc)."&page=".url_encode($page)."&ctrlreload=no';\n";
         	}

         	// define message to display
         	$message = "<p class=hcmsHeadline>".$hcms_lang['refreshing-view-'][$lang]."</p>\n";
         	$message .= "<a href=\"page_view.php?view=".url_encode($view)."&site=".url_encode($site)."&cat=".url_encode($cat)."&db_connect=".url_encode($db_connect)."&location=".url_encode($location_esc)."&page=".url_encode($page)."&ctrlreload=no\">".$hcms_lang['manual-refresh'][$lang]."</a>\n";
      	}
      }

      // ----------------------------------- DB Connectivity --------------------------------------   
      // db_connect will save content in provided database connectivity 
      if (!empty ($db_connect)) 
      {      
        // write data
        $test = db_write_container ($site, $object_contentfile, $contentdatanew, $user);    
        
        if ($test == false)
        {
          $errcode = "20101";
          $error[] = $mgmt_config['today']."|page_save.inc.php|error|".$errcode."|unable to save data of container '".$object_contentfile."' using db_connect '".$db_connect."'";          
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
      	$message[] = $hcms_lang['functional-error-occured'][$lang];
      }
      else
      {
      	$message = "<p class=hcmsHeadline>".$hcms_lang['functional-error-occured'][$lang]."</p>\n<a href=\"page_view.php?site=".$site."&location=".$location_esc."&page=".$page."\">".$hcms_lang['manual-refresh'][$lang]."</a>";
      }
    }
  }
  // if content file isn't available
  elseif (!is_file (getcontentlocation ($container_id, 'abs_path_content').$object_contentfile.".wrk"))
  {
    // define meta tag
    $add_onload =  "";

    // define message to display
    if ($auto)
    {
    	// define message to display
    	$message[] = $hcms_lang['content-container-is-missing'][$lang];
    	$message[] = $hcms_lang['the-content-of-this-object-is-missing'][$lang];
    	$message[] = $hcms_lang['to-create-a-new-content-container-please-delete-the-object-and-create-a-new-one'][$lang];
    }
    else
    {
    	$message = "<p class=hcmsHeadline>".$hcms_lang['content-container-is-missing'][$lang]."</p>\n".$hcms_lang['the-content-of-this-object-is-missing'][$lang]."<br />\n".$hcms_lang['to-create-a-new-content-container-please-delete-the-object-and-create-a-new-one'][$lang]."<br />\n";
    }
  }
  else
  {
    // define meta tag
    $add_onload = "";

    // define message to display
    if ($auto) 
    {
  	  $message[] = $hcms_lang['content-container-is-missing'][$lang];
    }
    else
    {
    	// define message to display
    	$message = "<p class=hcmsHeadline>".$hcms_lang['content-container-is-missing'][$lang]."</p>\n";
    }
  }
}
else
{
  // define meta tag
  $add_onload = "";

  // define message to display
  if ($auto) 
  {
	  $message[] = $hcms_lang['you-do-not-have-write-permissions-for-the-content-container'][$lang];
  }
  else
  {
  	// define message to display
  	$message = "<p class=hcmsHeadline>".$hcms_lang['you-do-not-have-write-permissions-for-the-content-container'][$lang]."</p>\n";
  }
}

// save log
savelog (@$error);

// json answer
if ($auto)
{
  if ($usedby != "" && $usedby != $user)
  {
    $message[] = $hcms_lang['you-do-not-have-write-permissions-for-the-content-container'][$lang];
  }

  // request from autosave
  header ('Content-Type: application/json; charset=utf-8');
	echo json_encode (array('message' => implode(", ", $message)));  
}
else
{
?>
<!DOCTYPE html>
<html>
<head>
<title>hyperCMS</title>
<meta charset="<?php echo getcodepage ($lang); ?>" />
<link rel="stylesheet" href="<?php echo getthemelocation(); ?>css/main.css">
<script type="text/javascript">
<?php echo $add_onload; ?>
</script>
<script type="text/javascript" src="../javascript/main.min.js"></script>
<script type="text/javascript" src="../javascript/click.min.js"></script>
</head>
<body class="hcmsWorkplaceGeneric">
<div style="padding:4px;">
  <?php echo $message; ?>
</div>
</body>
</html>
<?php 
}
?>