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
if ($service == "recognizefaces" && !empty ($user) && is_facerecognition ($user))
{
  $setlocalpermission['root'] = 1;
  $setlocalpermission['create'] = 1;
}
  
if (empty ($setlocalpermission['root']) || empty ($setlocalpermission['create']) || !valid_publicationname ($site) || !valid_locationname ($location) || !valid_objectname ($page)) killsession ($user);

// check session of user
checkusersession ($user, false);

// --------------------------------- logic section ----------------------------------

// initialize
$error = array();
$usedby = "";

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
if (!empty ($result_containername['user'])) $usedby = $result_containername['user'];

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
    if (!empty ($faces))
    {
      // initialize
      if (empty ($textu) || !is_array ($textu)) $textu = array();

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
            mkdir ($mgmt_config['abs_path_rep']."faces/Albert Einstein", $mgmt_config['fspermission']);
            savefile ($mgmt_config['abs_path_rep']."faces/", "Albert Einstein", '{"0":-0.13147659599781036,"1":0.07497824728488922,"2":-0.014841041527688503,"3":-0.021328741684556007,"4":-0.0641128197312355,"5":-0.029178842902183533,"6":0.0018348869634792209,"7":-0.03617827594280243,"8":0.17785927653312683,"9":-0.12779074907302856,"10":0.15327978134155273,"11":0.030064530670642853,"12":-0.2577071785926819,"13":-0.05594765767455101,"14":-0.014355958439409733,"15":0.13236819207668304,"16":-0.09851830452680588,"17":-0.09827537834644318,"18":-0.17591413855552673,"19":-0.14049918949604034,"20":-0.057741791009902954,"21":0.1172868013381958,"22":-0.013467547483742237,"23":-0.09780023992061615,"24":-0.051875192672014236,"25":-0.34686803817749023,"26":-0.055836524814367294,"27":-0.06116286665201187,"28":0.0676673874258995,"29":-0.10466095060110092,"30":0.06006662920117378,"31":0.09138994663953781,"32":-0.13251398503780365,"33":-0.045636359602212906,"34":0.016113704070448875,"35":0.08037275075912476,"36":-0.04284144565463066,"37":-0.0109431566670537,"38":0.2057550698518753,"39":0.16983748972415924,"40":-0.20508909225463867,"41":0.04689915478229523,"42":0.02858395129442215,"43":0.35643163323402405,"44":0.20045557618141174,"45":-0.006746217608451843,"46":0.022421415895223618,"47":-0.062246229499578476,"48":0.04977443441748619,"49":-0.23329302668571472,"50":0.12069644778966904,"51":0.09467161446809769,"52":0.19453586637973785,"53":0.0749613493680954,"54":0.13695944845676422,"55":-0.13669194281101227,"56":-0.03505241870880127,"57":0.09217755496501923,"58":-0.17229163646697998,"59":0.06553469598293304,"60":0.09298641979694366,"61":-0.07370404154062271,"62":-0.07429861277341843,"63":0.00467686727643013,"64":0.16690684854984283,"65":0.11456635594367981,"66":-0.1012020856142044,"67":-0.12700290977954865,"68":0.16853168606758118,"69":-0.16607961058616638,"70":-0.06507300585508347,"71":0.09822794049978256,"72":-0.037632688879966736,"73":-0.12992754578590393,"74":-0.25457608699798584,"75":0.129130020737648,"76":0.3825928568840027,"77":0.15844924747943878,"78":-0.14956682920455933,"79":-0.04481545090675354,"80":-0.052733417600393295,"81":-0.09555477648973465,"82":0.08562593162059784,"83":0.12904676795005798,"84":-0.0679054781794548,"85":-0.10809406638145447,"86":-0.003031661733984947,"87":-0.011762604117393494,"88":0.16825149953365326,"89":0.02733340673148632,"90":-0.12536874413490295,"91":0.14648525416851044,"92":0.019232654944062233,"93":-0.05468397215008736,"94":-0.03971606492996216,"95":0.02955925092101097,"96":-0.11996565014123917,"97":-0.04779072478413582,"98":-0.05553288757801056,"99":-0.14564034342765808,"100":0.07412637025117874,"101":-0.14400643110275269,"102":-0.010789445601403713,"103":0.11235014349222183,"104":-0.17807239294052124,"105":0.20773574709892273,"106":-0.004384967498481274,"107":-0.04634798690676689,"108":0.07080687582492828,"109":0.048010602593421936,"110":0.029986586421728134,"111":0.0190870463848114,"112":0.12186603248119354,"113":-0.2264123409986496,"114":0.24001798033714294,"115":0.19070303440093994,"116":-0.03306982293725014,"117":0.15743005275726318,"118":0.06539849936962128,"119":0.07441409677267075,"120":0.03552107885479927,"121":0.0036798762157559395,"122":-0.11731418967247009,"123":-0.15613271296024323,"124":-0.057161230593919754,"125":0.05926265940070152,"126":0.04034542292356491,"127":0.039077337831258774}');
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
                // trim name
                $temp['name'] = trim ($temp['name']);

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

        if ((!empty ($mgmt_config['facerecognition']) || is_annotation ()) && intval ($container_id) > 0)
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
      if (!empty ($eventsystem['onsaveobject_pre']) && empty ($eventsystem['hide'])) 
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
        if (!empty ($eventsystem['onsaveobject_post']) && empty ($eventsystem['hide']))
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