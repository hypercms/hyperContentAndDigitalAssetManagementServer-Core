<?php
/*
 * This file is part of
 * hyper Content Management Server - http://www.hypercms.com
 * Copyright (c) by hyper CMS Content Management Solutions GmbH
 *
 * You should have received a copy of the License along with hyperCMS.
 */

// Directory name of hyperCMS application
$hypercms_dir = "hypercms";

 // Depending how the user accessed our page we are setting our protocol
$mgmt_config['url_protocol'] = (!empty($_SERVER['HTTPS'])) ? 'https://' : 'http://';

// get current working directory
$base_url = $_SERVER['SERVER_NAME'];

if (dirname($_SERVER['REQUEST_URI']) != "")
{
  $dirname = dirname(dirname(dirname($_SERVER['REQUEST_URI'])));
  if ($dirname != "/" && $dirname != "") $base_url .= $dirname;
}

$base_path = dirname(dirname(getcwd()));

// url and asolute path to hyperCMS on your webserver (e.g. /home/domain/hyperCMS/)
$mgmt_config['url_path_cms'] = $mgmt_config['url_protocol'].$base_url."/".$hypercms_dir."/";
$mgmt_config['url_path_cms_sub'] = $base_url."/".$hypercms_dir."/";
$mgmt_config['abs_path_cms'] = $base_path."/".$hypercms_dir."/";

// url and absolute path to hyperCMS repository on your webserver (e.g. /home/domain/repository/)
// the repository includes the XML content repository, the component repository, 
// the content-media and template-media repository
$mgmt_config['url_path_rep'] = $mgmt_config['url_protocol'].$base_url."/repository/";
$mgmt_config['url_path_rep_sub'] = $base_url."/repository/";
$mgmt_config['abs_path_rep'] = $base_path."/repository/";

// url and absolute path to hyperCMS data on your webserver (e.g. /home/domain/hyperCMS/data/)
// data is used for the storage of internal content management information   
$mgmt_config['url_path_data'] = $mgmt_config['url_protocol'].$base_url."/data/";
$mgmt_config['url_path_data_sub'] = $base_url."/data/";
$mgmt_config['abs_path_data'] = $base_path."/data/";

// url and absolute path to MyPublication on your webserver (e.g. /home/domain/hyperCMS/mypublication/)
$mgmt_config['url_path_mypublication'] = $mgmt_config['url_protocol'].$base_url."/mypublication/";
$mgmt_config['abs_path_mypublication'] = $base_path."/mypublication/";

// set theme name
$mgmt_config['theme'] = "standard";

// set DB connectivity
$mgmt_config['db_connect_rdbms'] = "db_connect_rdbms.php";
$mgmt_config['dbconnect'] = "mysql";
$mgmt_config['dbcharset'] = "utf8";
$mgmt_config['rdbms_log'] = true;

// set user and language
$user = "sys";
$lang = "en";

// hyperCMS API
require ("../function/hypercms_api.inc.php");
// hyperCMS UI
require ("../function/hypercms_ui.inc.php");
// version info
require ("../version.inc.php");


// check for existing installation (using check.dat)
if (@is_file ($mgmt_config['abs_path_data']."check.dat"))
{
  $data = loadfile ($mgmt_config['abs_path_data'], "check.dat");
  
  if ($data != 0)
  {
?>
<!DOCTYPE html>
<html>
<head>
<title>hyperCMS</title>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<meta name="viewport" content="width=device-width; initial-scale=0.7; maximum-scale=1.0; user-scalable=1;" />
<link rel="stylesheet" href="../theme/standard/css/main.css">
</head>
<body class="hcmsWorkplaceGeneric">
<div class="hcmsHeadline" style="width:380px; margin: 20px auto 20px auto;">
  hyperCMS is already installed!
</div>
</body>
</html>
<?php
    exit;
  }
}

// input parameters
$action = getrequest ("action");

$password = getrequest ("password");
$confirm_password = getrequest ("confirm_password");
$realname = getrequest_esc ("realname");
$language = getrequest_esc ("language");
$email = getrequest_esc ("email");

$db_host = getrequest_esc ("db_host");
$db_username = getrequest_esc ("db_username");
$db_password = getrequest ("db_password");
$db_name = getrequest_esc ("db_name");

$smtp_host = getrequest_esc ("smtp_host");
$smtp_username = getrequest_esc ("smtp_username");
$smtp_password = getrequest_esc ("smtp_password");
$smtp_port = getrequest_esc ("smtp_port");
$smtp_sender = getrequest_esc ("smtp_sender");

$os_cms = getrequest_esc ("os_cms");
$pdftotext = getrequest_esc ("pdftotext");
$antiword = getrequest_esc ("antiword");
$gunzip = getrequest_esc ("gunzip");
$unzip = getrequest_esc ("unzip");
$zip = getrequest_esc ("zip");
$unoconv = getrequest_esc ("unoconv");
$convert = getrequest_esc ("convert");
$ffmpeg = getrequest_esc ("ffmpeg");
$yamdi = getrequest_esc ("yamdi");
$exiftool = getrequest_esc ("exiftool");

$setup_publication = getrequest_esc ("setup_publication");

$token = getrequest ("token");

// --------------------------------- logic section ----------------------------------

$show = "";

// install hyperCMS
if ($action == "install" && $mgmt_config['abs_path_cms'] != "" && $mgmt_config['abs_path_cms'] != "" && checktoken ($token, $user))
{
  // create data and repository file structure
  if ($show == "" && $mgmt_config['abs_path_data'] != "" && $mgmt_config['abs_path_rep'] != "" && $mgmt_config['abs_path_mypublication'] != "")
  {   
    if (!is_writeable ($mgmt_config['abs_path_cms']."config/")) $show .= "<li>Write perission for config-directory is missing (".$mgmt_config['abs_path_cms']."config/)!</li>\n";
    if (!is_writeable ($mgmt_config['abs_path_cms']."temp/")) $show .= "<li>Write perission for temp-directory is missing (".$mgmt_config['abs_path_cms']."temp/)!</li>\n";
    if (!is_writeable ($mgmt_config['abs_path_cms']."temp/view/")) $show .= "<li>Write perission for temp-directory is missing (".$mgmt_config['abs_path_cms']."temp/view/)!</li>\n";
    if (!is_writeable ($mgmt_config['abs_path_data'])) $show .= "<li>Write perission for data-directory is missing (".$mgmt_config['abs_path_data'].")!</li>\n";
    if (!is_writeable ($mgmt_config['abs_path_rep'])) $show .= "<li>Write perission for repository-directory is missing (".$mgmt_config['abs_path_rep'].")!</li>\n";
    if (!is_writeable ($mgmt_config['abs_path_mypublication'])) $show .= "<li>Write perissions for publication-directory is missing (".$mgmt_config['abs_path_mypublication'].")!</li>\n";

    // copy to internal repository
    if ($show == "")
    {
      $result = copyrecursive ($mgmt_config['abs_path_cms']."install/data/", $mgmt_config['abs_path_data']);
      if ($result == false) $show .= "<li>Data file structure could not be created!</li>\n";
    }
    
    // copy to external repository
    if ($show == "")
    {
      $result = copyrecursive ($mgmt_config['abs_path_cms']."install/repository/", $mgmt_config['abs_path_rep']);
      if ($result == false) $show .= "<li>Repository file structure could not be created!</li>\n";
    }
  }
  
  // create database
  if ($show == "" && $db_host != "" && $db_username != "" && $db_password != "" && $db_name != "")
  {
    // check for whitespaces
    if (preg_match ('/\s/', $db_host) > 0) $show .= "<li>Whitespaces in '".$db_host."' are not allowed!</li>";
    if (preg_match ('/\s/', $db_username) > 0) $show .= "<li>Whitespaces in '".$db_username."' are not allowed!</li>";
    if (preg_match ('/\s/', $db_name) > 0) $show .= "<li>Whitespaces in '".$db_name."' are not allowed!</li>";
  
    if ($show == "")
    {
      // connect to MySQL
      $mysqli = new mysqli ($db_host, $db_username, $db_password);      
      if ($mysqli->connect_errno) $show .= "<li>DB error (".$mysqli->connect_errno."): ".$mysqli->connect_error."</li>\n";
      
      if ($show == "")
      {
        // select and create database
        if (!$mysqli->select_db ($db_name))
        {
          $sql = "CREATE DATABASE ".$db_name;
        
          if (!$mysqli->query ($sql)) $show .= "<li>DB error (".$mysqli->errno."): ".$mysqli->error."</li>\n";
          elseif (!$mysqli->select_db ($db_name)) $show .= "<li>DB error (".$mysqli->errno."): ".$mysqli->error."</li>\n";
        }
        
        // create tables
        if ($show == "")
        {
          // check if objects exist already
          if ($result = $mysqli->query ('SELECT count(*) AS count FROM `object`'))
          {
            if ($row = $result->fetch_assoc()) $count = $row['count'];
            $result->free();
          }
          else $count = 0;
          
          // create tables
          if ($show == "" && $count < 1)
          {
            $sql = loadfile ($mgmt_config['abs_path_cms']."database/rdbms/", "createtables.sql");
            
            if ($sql != "")
            {
              if (!$mysqli->multi_query ($sql)) $show .= "<li>Error creating tables: (".$mysqli->errno.") ".$mysqli->error."</li>\n";
            }
            else $show .= "<li>Error creating tables: createtables.sql is missing</li>\n";
            
            $mysqli->close();
          }
        }
      }
    }
  }
  
  sleep (1);
  
  // edit admin user
  if ($show == "" && $password != "" && $confirm_password != "" && $language != "" && $email != "")
  {
    $result = edituser ("*Null*", "admin", "", $password, $confirm_password, "1", $realname, $language, "standard", $email, "", "", "", $user);
    if ($result['result'] == false) $show .= "<li>".$result['message']."</li>\n";
  }
  
  // create configs
  if ($show == "" && $os_cms != "" && $mgmt_config['url_path_cms'] != "" && $mgmt_config['abs_path_cms'] != "")
  {
    if (preg_match ('/\s/', $smtp_host) > 0) $show .= "<li>Whitespaces in '".$smtp_host."' are not allowed!</li>\n";
    if (preg_match ('/\s/', $smtp_username) > 0) $show .= "<li>Whitespaces in '".$smtp_username."' are not allowed!</li>\n";
    if (preg_match ('/\s/', $smtp_port) > 0) $show .= "<li>Whitespaces in '".$smtp_port."' are not allowed!</li>\n";
    if (preg_match ('/\s/', $smtp_sender) > 0) $show .= "<li>Whitespaces in '".$smtp_sender."' are not allowed!</li>\n";

    if ($show == "")
    {
      // create main config
      $config = loadfile ($mgmt_config['abs_path_cms']."install/", "config.inc.php");
      
      if ($config != "")
      {
        $config = str_replace ("%url_path_cms%", $mgmt_config['url_path_cms_sub'], $config);
        $config = str_replace ("%abs_path_cms%", $mgmt_config['abs_path_cms'], $config);
        $config = str_replace ("%url_path_rep%", $mgmt_config['url_path_rep_sub'], $config);
        $config = str_replace ("%abs_path_rep%", $mgmt_config['abs_path_rep'], $config);
        $config = str_replace ("%url_path_data%", $mgmt_config['url_path_data_sub'], $config);
        $config = str_replace ("%abs_path_data%", $mgmt_config['abs_path_data'], $config);
        
        $config = str_replace ("%os_cms%", $os_cms, $config);
        
        $config = str_replace ("%pdftotext%", $pdftotext, $config);
        $config = str_replace ("%antiword%", $antiword, $config);
        $config = str_replace ("%gunzip%", $gunzip, $config);
        $config = str_replace ("%unzip%", $unzip, $config);
        $config = str_replace ("%zip%", $zip, $config);
        $config = str_replace ("%unoconv%", $unoconv, $config);
        $config = str_replace ("%convert%", $convert, $config);
        $config = str_replace ("%ffmpeg%", $ffmpeg, $config);
        $config = str_replace ("%yamdi%", $yamdi, $config);
        $config = str_replace ("%exiftool%", $exiftool, $config);
        
        $config = str_replace ("%dbhost%", $db_host, $config);
        $config = str_replace ("%dbuser%", $db_username, $config);
        $config = str_replace ("%dbpasswd%", $db_password, $config);
        $config = str_replace ("%dbname%", $db_name, $config);
          
        $config = str_replace ("%smtp_host%", $smtp_host, $config);
        $config = str_replace ("%smtp_username%", $smtp_username, $config);
        $config = str_replace ("%smtp_password%", $smtp_password, $config);
        $config = str_replace ("%smtp_port%", $smtp_port, $config);
        $config = str_replace ("%smtp_sender%", $smtp_sender, $config);
        
        $result_config = savefile ($mgmt_config['abs_path_cms']."config/", "config.inc.php", $config);
        
        if ($result_config == false) $show .= "<li>Create of config file failed. Please check write permissions of config/config.inc.php.</li>\n";
      }
    }
    
    // set path for search engine
    if ($show == "")
    {
      // create main config
      $config = loadfile ($mgmt_config['abs_path_cms']."install/repository/search/", "search_config.inc.php");
      
      if ($config != "")
      {
        $config = str_replace ("%url_path_rep%/", $mgmt_config['url_path_rep_sub'], $config);
        $config = str_replace ("%abs_path_rep%/", $mgmt_config['abs_path_rep'], $config);
        
        $result_config = savefile ($mgmt_config['abs_path_rep']."search/", "search_config.inc.php", $config);
        
        if ($result_config == false) $show .= "<li>Create of search config file failed. Please check write permissions of repository/search/search_config.inc.php.</li>\n";
      }
    }
    
    // create publication
    if ($show == "")
    {
      // include main config
      require ("../config.inc.php");
      
      if ($setup_publication == "cms") $site = "MyHomepage";
      elseif ($setup_publication == "dam") $site = "MyDAM";
      
      // create publication
      $result = createpublication ($site, "admin");
      if (!$result['result']) $show .= "<li>".$result['message']."</li>\n"; 
      
      // edit publication settings
      if ($show == "")
      {
        if ($setup_publication == "cms")
        {
          $setting = array();
          $setting['site_admin'] = true;
          $setting['linkengine'] = true;
          $setting['sendmail'] = true;
          $setting['webdav'] = false;
          $setting['http_incl'] = false;
          $setting['inherit_obj'] = false;
          $setting['inherit_comp'] = false;
          $setting['inherit_tpl'] = false;
          $setting['specialchr_disable'] = true;
          $setting['dam'] = false;
          $setting['youtube'] = false;
          $setting['theme'] = "standard";
          $setting['storage'] = "";
          $setting['default_codepage'] = "UTF-8";
          $setting['url_path_page'] = $mgmt_config['url_path_mypublication'];
          $setting['abs_path_page'] = $mgmt_config['abs_path_mypublication'];
          $setting['exclude_folders'] = "";
          $setting['allow_ip'] = "";
          $setting['mailserver'] = $smtp_sender;
          $setting['publ_os'] = $os_cms;
          $setting['remoteclient'] = "";
          $setting['url_publ_page'] = $mgmt_config['url_path_mypublication'];
          $setting['abs_publ_page'] = $mgmt_config['abs_path_mypublication'];
          $setting['url_publ_rep'] = $mgmt_config['url_path_rep'];
          $setting['abs_publ_rep'] = $mgmt_config['abs_path_rep'];
          $setting['abs_publ_app'] = "";
        }
        elseif ($setup_publication == "dam")
        {
          $setting = array();
          $setting['site_admin'] = true;
          $setting['linkengine'] = false;
          $setting['sendmail'] = true;
          $setting['webdav'] = false;
          $setting['http_incl'] = false;
          $setting['inherit_obj'] = false;
          $setting['inherit_comp'] = false;
          $setting['inherit_tpl'] = false;
          $setting['specialchr_disable'] = false;
          $setting['dam'] = true;
          $setting['youtube'] = false;
          $setting['theme'] = "standard";
          $setting['storage'] = "";
          $setting['default_codepage'] = "UTF-8";
          $setting['url_path_page'] = "";
          $setting['abs_path_page'] = "";
          $setting['exclude_folders'] = "";
          $setting['allow_ip'] = "";
          $setting['mailserver'] = $smtp_sender;
          $setting['publ_os'] = $os_cms;
          $setting['remoteclient'] = "";
          $setting['url_publ_page'] = "";
          $setting['abs_publ_page'] = "";
          $setting['url_publ_rep'] = $mgmt_config['url_path_rep'];
          $setting['abs_publ_rep'] = $mgmt_config['abs_path_rep'];
          $setting['abs_publ_app'] = "";
        }
        
        $result = editpublication ($site, $setting, "admin");
        if (!$result['result']) $show .= "<li>".$result['message']."</li>\n"; 
      }
    }
  }
  
  // copy templates and template media files
  if ($show == "")
  {
    if ($setup_publication == "cms")
    {
      $result = copyrecursive ($mgmt_config['abs_path_cms']."install/templates/attitude/media/", $mgmt_config['abs_path_rep']."media_tpl/".$site."/");
      if ($result == false) $show .= "<li>Template media could not be created!</li>\n";
    
      $result = copyrecursive ($mgmt_config['abs_path_cms']."install/templates/attitude/tpl/", $mgmt_config['abs_path_data']."template/".$site."/");
      if ($result == false) $show .= "<li>Templates could not be created!</li>\n";
    }
  }
  
  // create example website  
  if ($show == "" && valid_publicationname ($site) && $setup_publication == "cms")
  {
    // load publication config
    require ($mgmt_config['abs_path_data']."config/".$site.".conf.php");
      
    // create folders
    if (!is_file (deconvertpath ("%comp%/".$site."/Multimedia/.folder")))
    {
      $result = createfolder ($site, "%comp%/".$site."/", "Multimedia", $user);
         
      if (isset ($result['result']) && !$result['result']) $show .= "<li>Multimedia folder could not be created:<br/>".$result['message']."</li>\n";
    }
    
    if (!is_file (deconvertpath ("%page%/".$site."/AboutUs/.folder")))
    {
      $result = createfolder ($site, "%page%/".$site."/", "AboutUs", $user);
         
      if (isset ($result['result']) && $result['result'])
      {
        $contentdata = settext ($site, $result['container_content'], $result['container'], array('NavigationSortOrder'=>'2'), "u", "no", $user, $user, "UTF-8");
        
        // save working xml content container
        if (!savecontainer ($result['container'], "work", $contentdata, $user)) $show .= "<li>Content could not be saved!</li>\n";
      }
      else $show .= "<li>About us folder could not be created:<br/>".$result['message']."</li>\n";
    }
    
    if (!is_file (deconvertpath ("%page%/".$site."/Products/.folder")))
    {
      $result = createfolder ($site, "%page%/".$site."/", "Products", $user);
      
      if (isset ($result['result']) && $result['result'])
      {
        $contentdata = settext ($site, $result['container_content'], $result['container'], array('NavigationSortOrder'=>'3'), "u", "no", $user, $user, "UTF-8");
        
        // save working xml content container
        if (!savecontainer ($result['container'], "work", $contentdata, $user)) $show .= "<li>Content could not be saved!</li>\n";
      }
      else $show .= "<li>Products folder could not be created:<br/>".$result['message']."</li>\n";
    }
    
    if (!is_file (deconvertpath ("%page%/".$site."/Contact/.folder")))
    {
      $result = createfolder ($site, "%page%/".$site."/", "Contact", $user);
      
      if (isset ($result['result']) && $result['result'])
      {
        $contentdata = settext ($site, $result['container_content'], $result['container'], array('NavigationSortOrder'=>'4'), "u", "no", $user, $user, "UTF-8");
        
        // save working xml content container
        if (!savecontainer ($result['container'], "work", $contentdata, $user)) $show .= "<li>Content could not be saved!</li>\n";
      }
      else $show .= "<li>Contact folder could not be created:<br/>".$result['message']."</li>\n";
    }
    
    // create objects
    if ($show == "")
    {
      if (!is_file (deconvertpath ("%comp%/".$site."/Multimedia/Slider1.jpg")))
      {
        $result = createmediaobject ($site, "%comp%/".$site."/Multimedia/", "Slider1.jpg", $mgmt_config['abs_path_rep']."media_tpl/".$site."/Slider1.jpg", $user);
        
        if (isset ($result['result']) && $result['result']) { $mediafile_1 = $site."/".$result['mediafile']; $mediaobject_1 = "%comp%/".$site."/Multimedia/".$result['object']; }
        else $show .= "<li>Slider image file could not be created:<br/>".$result['message']."</li>\n";
      }
      
      if (!is_file (deconvertpath ("%comp%/".$site."/Multimedia/Slider2.jpg")))
      {
        $result = createmediaobject ($site, "%comp%/".$site."/Multimedia/", "Slider2.jpg", $mgmt_config['abs_path_rep']."media_tpl/".$site."/Slider2.jpg", $user);
        
        if (isset ($result['result']) && $result['result']) { $mediafile_2 = $site."/".$result['mediafile']; $mediaobject_2 = "%comp%/".$site."/Multimedia/".$result['object']; }
        else $show .= "<li>Slider image file could not be created:<br/>".$result['message']."</li>\n";
      }
      
      if (!is_file (deconvertpath ("%comp%/".$site."/Multimedia/Slider3.jpg")))
      {
        $result = createmediaobject ($site, "%comp%/".$site."/Multimedia/", "Slider3.jpg", $mgmt_config['abs_path_rep']."media_tpl/".$site."/Slider3.jpg", $user);
        
        if (isset ($result['result']) && $result['result']) { $mediafile_3 = $site."/".$result['mediafile']; $mediaobject_3 = "%comp%/".$site."/Multimedia/".$result['object']; }
        else $show .= "<li>Slider image file could not be created:<br/>".$result['message']."</li>\n";
      }
    
      if (!is_file (deconvertpath ("%comp%/".$site."/configuration.php")))
      {
        $result = createobject ($site, "%comp%/".$site."/", "configuration", "Configuration", $user);
              
        if (isset ($result['result']) && $result['result'])
        {
          $contentdata = settext ($site, $result['container_content'], $result['container'], array('title'=>'Your Name', 'slogan'=>'Your Slogan ...'), "u", "no", $user, $user, "UTF-8");
        
          // save working xml content container
          if (!savecontainer ($result['container'], "work", $contentdata, $user)) $show .= "<li>Content could not be saved!</li>\n";
        }
        else $show .= "<li>Configuration component could not be created:<br/>".$result['message']."</li>\n";
        
        // publish object so the configuration will be activated
        publishobject ($site, "%comp%/".$site."/", "configuration.php", $user);
      }
      
      if (!is_file (deconvertpath ("%page%/".$site."/index.php")))
      {
        $result = createobject ($site, "%page%/".$site."/", "index", "Home", $user);
        
        if (isset ($result['result']) && $result['result'])
        {
          $contentdata = settext ($site, $result['container_content'], $result['container'], array('Title'=>'Home', 'NavigationSortOrder'=>'1'), "u", "no", $user, $user, "UTF-8");
          $contentdata = setmedia ($site, $contentdata, $result['container'], array('slide_1'=>$mediafile_1, 'slide_2'=>$mediafile_2, 'slide_3'=>$mediafile_3, 'slide_4'=>$mediafile_2, 'slide_5'=>$mediafile_1), "", array('slide_1'=>$mediaobject_1, 'slide_2'=>$mediaobject_2, 'slide_3'=>$mediaobject_3, 'slide_4'=>$mediaobject_2, 'slide_5'=>$mediaobject_1), "", "", "", "", "no", $user, $user, "UTF-8");
          
          // save working xml content container
          if (!savecontainer ($result['container'], "work", $contentdata, $user)) $show .= "<li>Content could not be saved!</li>\n";
        }
        else $show .= "<li>Homepage could not be created:<br/>".$result['message']."</li>\n";
        
        // publish object so the item will be displayed in the navigation 
        publishobject ($site, "%page%/".$site."/", "index.php", $user);
      }
      
      if (!is_file (deconvertpath ("%page%/".$site."/search.php")))
      {
        $result = createobject ($site, "%page%/".$site."/", "search", "SearchResult", $user);
        
        if (isset ($result['result']) && $result['result'])
        {
          $contentdata = settext ($site, $result['container_content'], $result['container'], array('Title'=>'Search result', 'NavigationHide'=>'yes'), "u", "no", $user, $user, "UTF-8");
        
          // save working xml content container
          if (!savecontainer ($result['container'], "work", $contentdata, $user)) $show .= "<li>Content could not be saved!</li>\n";
        }
        else $show .= "<li>Search page could not be created:<br/>".$result['message']."</li>\n";
        
        // publish object so the item will be displayed in the navigation 
        publishobject ($site, "%page%/".$site."/", "search.php", $user);
      }
      
      if (!is_file (deconvertpath ("%page%/".$site."/AboutUs/index.php")))
      {
        $result = createobject ($site, "%page%/".$site."/AboutUs/", "index", "Detail", $user);
        
        if (isset ($result['result']) && $result['result'])
        {
          $contentdata = settext ($site, $result['container_content'], $result['container'], array('Title'=>'About us', 'NavigationSortOrder'=>'2'), "u", "no", $user, $user, "UTF-8");
          
          // save working xml content container
          if (!savecontainer ($result['container'], "work", $contentdata, $user)) $show .= "<li>Content could not be saved!</li>\n";
        }
        else $show .= "<li>About us page could not be created:<br/>".$result['message']."</li>\n";
        
        // publish object so the item will be displayed in the navigation 
        publishobject ($site, "%page%/".$site."/AboutUs/", "index.php", $user);
      }
      
      if (!is_file (deconvertpath ("%page%/".$site."/Products/index.php")))
      {
        $result = createobject ($site, "%page%/".$site."/Products/", "index", "Detail", $user);
        
        if (isset ($result['result']) && $result['result'])
        {
          $contentdata = settext ($site, $result['container_content'], $result['container'], array('Title'=>'Products', 'NavigationSortOrder'=>'3'), "u", "no", $user, $user, "UTF-8");
        
          // save working xml content container
          if (!savecontainer ($result['container'], "work", $contentdata, $user)) $show .= "<li>Content could not be saved!</li>\n";
        }
        else $show .= "<li>Products page could not be created:<br/>".$result['message']."</li>\n";
        
        // publish object so the item will be displayed in the navigation 
        publishobject ($site, "%page%/".$site."/Products/", "index.php", $user);
      }
      
      if (!is_file (deconvertpath ("%page%/".$site."/Contact/index.php")))
      {
        $result = createobject ($site, "%page%/".$site."/Contact/", "index", "ContactUs", $user);
        
        if (isset ($result['result']) && $result['result'])
        {
          $contentdata = settext ($site, $result['container_content'], $result['container'], array('Title'=>'Contact', 'NavigationSortOrder'=>'4'), "u", "no", $user, $user, "UTF-8");
        
          // save working xml content container
          if (!savecontainer ($result['container'], "work", $contentdata, $user)) $show .= "<li>Content could not be saved!</li>\n";
        }
        else $show .= "<li>Contact page could not be created:<br/>".$result['message']."</li>\n";
        
        // publish object so the item will be displayed in the navigation 
        publishobject ($site, "%page%/".$site."/Contact/", "index.php", $user);
      }
    }
  }
  
  // show errors
  if ($show != "") $show = "<strong>The following errors occured:</strong><br/>\n<ul>".$show."</ul>";
  // forward on success
  else header ("Location: ".$mgmt_config['url_path_cms']);
}

// create secure token
$token_new = createtoken ($user);
?>
<!DOCTYPE html>
<html>
<head>
<title>hyperCMS</title>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<meta name="viewport" content="width=device-width; initial-scale=0.7; maximum-scale=1.0; user-scalable=1;" />
<link rel="stylesheet" href="../theme/standard/css/main.css">
<script src="../javascript/main.js" type="text/javascript"></script>
<style type="text/css">
<!--
#error { color:red; display:none; }
.needsfilled { color:red; }
-->
</style>
</head>

<body class="hcmsWorkplaceGeneric">

<script type="text/javascript" src="http://jqueryjs.googlecode.com/files/jquery-1.3.2.min.js"></script>
<script language="JavaScript">
$(document).ready(function(){
	// Place ID's of all required fields here.
	required = ["password", "confirm_password", "email", "db_host", "db_username", "db_password", "db_name", "smtp_host", "smtp_username", "smtp_password", "smtp_port", "smtp_sender"];

	// If using an ID other than #email or #error then replace it here
	email = $("#email");
	errornotice = $("#error");

	// The text to show up within a field when it is incorrect
	emptyerror = "Please fill out this field";
	emailerror = "Please enter a valid e-mail";

	$("#installform").submit(function(){	
		//Validate required fields
		for (i=0;i<required.length;i++) {
			var input = $('#'+required[i]);
			if ((input.val() == "") || (input.val() == emptyerror)) {
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
</script>

<!-- top bar -->
<?php echo showtopbar ("Installation of hyperCMS ".$version, "en"); ?>

<!-- content area -->
<div id="content" style="width:480px; margin:0 auto 10px auto;">

<div id="error" style="padding:4px; border:1px solid red; background:#ffdcd5;">There were errors on the form!</div>

<?php echo showmessage ($show, 480, 300, "en", "position:absolute; top:40px; margin-left:auto; margin-right:auto;"); ?>  

<form id="installform" name="installform" action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post">
  <input type="hidden" name="action" value="install">
  <input type="hidden" name="token" value="<?php echo $token_new; ?>">
  
  <table border="0" cellspacing="0" cellpadding="3">
    
    <!-- User -->
    <tr>
      <td colspan="2" nowrap="nowrap" class="hcmsHeadline">hyperCMS Administrator Account</td>
    </tr>
    <tr>
      <td colspan="2" nowrap="nowrap">You will need this account to log in to the system after installation.</td>
    </tr>
    <tr>
      <td nowrap="nowrap">User name: </td>
      <td align="left">
        <input type="text" id="user" name="user" value="admin" style="width:200px;" readonly="readonly" />
      </td>
    </tr>
    <tr>
      <td nowrap="nowrap">Password: </td>
      <td align="left">
        <input type="password" id="password" name="password" value="<?php echo $password; ?>" style="width:200px;" />
      </td>
    </tr>
    <tr>
      <td nowrap="nowrap">Confirm password: </td>
      <td align="left">
        <input type="password" id="confirm_password" name="confirm_password" value="<?php echo $confirm_password; ?>" style="width:200px;" />
      </td>
    </tr>
    <tr>
      <td nowrap="nowrap">Name: </td>
      <td align="left">
        <input type="text" id="realname" name="realname" style="width:200px;" value="<?php echo $realname; ?>" />
      </td>
    </tr>
    <tr>
      <td nowrap="nowrap">E-mail: </td>
      <td align="left">
        <input type="text" id="email" name="email" style="width:200px;" value="<?php echo $email; ?>" />
      </td>
    </tr>
    <tr>
      <td nowrap="nowrap">Language: </td>
      <td align="left">
        <select id="language" name="language" style="width:200px;">
        <?php
        $lang_name['en'] = "English";
        $lang_shortcut['en'] = "en";
        $lang_name['de'] = "German";
        $lang_shortcut['de'] = "de";
        
        if (is_array ($lang_shortcut))
        {
          foreach ($lang_shortcut as $lang_opt)
          {
            if ($language == $lang_opt)
            {
              echo "<option value=\"".$lang_opt."\" selected=\"selected\">".$lang_name[$lang_opt]."</option>\n";
            }
            else echo "<option value=\"".$lang_opt."\">".$lang_name[$lang_opt]."</option>\n";
          }
        }
        ?>
        </select>
      </td>
    </tr>
    <tr>
      <td colspan="2" nowrap="nowrap">&nbsp;</td>
    </tr>
    
    <!-- Database -->
    <tr>
      <td colspan="2" nowrap="nowrap" class="hcmsHeadline">MySQL Database</td>
    </tr>
    <tr>
      <td colspan="2" nowrap="nowrap">Please make sure that a database with the same name does not already exist.</td>
    </tr>
    <tr>
      <td nowrap="nowrap">Database host: </td>
      <td align="left">
        <input type="text" id="db_host" name="db_host" value="<?php echo $db_host; ?>" style="width:200px;" />
      </td>
    </tr>
    <tr>
      <td nowrap="nowrap">Database user name: </td>
      <td align="left">
        <input type="text" id="db_username" name="db_username" value="<?php echo $db_username; ?>" style="width:200px;" />
      </td>
    </tr>
    <tr>
      <td nowrap="nowrap">Database password: </td>
      <td align="left">
        <input type="password" id="db_password" name="db_password" value="<?php echo $db_password; ?>" style="width:200px;" />
      </td>
    </tr>
    <tr>
      <td nowrap="nowrap">Database name: </td>
      <td align="left">
        <input type="text" id="db_name" name="db_name" value="<?php echo $db_name; ?>" style="width:200px;" />
      </td>
    </tr>
    <tr>
      <td colspan="2" nowrap="nowrap">&nbsp;</td>
    </tr>
    
    <!-- SMTP -->
    <tr>
      <td colspan="2" nowrap="nowrap" class="hcmsHeadline">SMTP/Mail Server</td>
    </tr>
    <tr>
      <td colspan="2" nowrap="nowrap">Please provide a valid SMTP host for features like task management, <br/>
      workflow management, send mail-links and others.</td>
    </tr>
    <tr>
      <td nowrap="nowrap">SMTP host: </td>
      <td align="left">
        <input type="text" id="smtp_host" name="smtp_host" value="<?php echo $smtp_host; ?>" style="width:200px;" />
      </td>
    </tr>
    <tr>
      <td nowrap="nowrap">SMTP user name: </td>
      <td align="left">
        <input type="text" id="smtp_username" name="smtp_username" value="<?php echo $smtp_username; ?>" style="width:200px;" />
      </td>
    </tr>
    <tr>
      <td nowrap="nowrap">SMTP password: </td>
      <td align="left">
        <input type="password" id="smtp_password" name="smtp_password" value="<?php echo $smtp_password; ?>" style="width:200px;" />
      </td>
    </tr>
    <tr>
      <td nowrap="nowrap">SMTP port: </td>
      <td align="left">
        <input type="text" id="smtp_port" name="smtp_port" value="<?php if ($smtp_port != "") echo $smtp_port; else echo "25" ?>" style="width:200px;" />
      </td>
    </tr>
    <tr>
      <td nowrap="nowrap">SMTP sender (e-mail address): </td>
      <td align="left">
        <input type="text" id="smtp_sender" name="smtp_sender" value="<?php echo $smtp_sender; ?>" style="width:200px;" />
      </td>
    </tr>
    <tr>
      <td colspan="2" nowrap="nowrap">&nbsp;</td>
    </tr>

    <!-- OS -->
    <tr>
      <td colspan="2" nowrap="nowrap" class="hcmsHeadline">Operating System</td>
    </tr>
    <tr>
      <td colspan="2" nowrap="nowrap">Please specify the operating system.</td>
    </tr>
     <tr>
      <td nowrap="nowrap">Operating system: </td>
      <td align="left">
        <select id="os_cms" name="os_cms">
          <option value="UNIX" <?php if ($os_cms == "UNIX") echo "selected=\"selected\""; elseif ($os_cms == "") echo "selected=\"selected\"" ?>>UNIX / Linux</option>
          <option value="WIN" <?php if ($os_cms == "WIN") echo "selected=\"selected\""; ?>>Windows</option>
        </select>
      </td>
    </tr>
    <tr>
      <td colspan="2" nowrap="nowrap">&nbsp;</td>
    </tr>
    
    <!-- Executables -->
    <?php
    // disable open_basdir of php.ini
    @ini_set ("open_basedir" , NULL);
    ?>
    <tr>
      <td colspan="2" nowrap="nowrap" class="hcmsHeadline">Additional Software</td>
    </tr>
    <tr>
      <td colspan="2" nowrap="nowrap">In order to use the full set of Digital Asset Management features<br/>
      of hyperCMS, additional software packages are required.<br/>
      The following settings provide typical examples of pathes to the <br/>
      executables on Linux, if available. Please adopt them if not suitable.<br/>
      Attention: The open_basedir restriction might effect the check for executables.</td>
    </tr>
    <tr>
      <td nowrap="nowrap">Define path to XPDF (pdftotext): </td>
      <td align="left">
        <input type="text" id="pdftotext" name="pdftotext" placeholder="/usr/bin/pdftotext" value="<?php if ($pdftotext != "") echo $pdftotext; elseif (@is_executable ("/usr/bin/pdftotext")) echo "/usr/bin/pdftotext" ?>" style="width:200px;" />
      </td>
    </tr>
    <tr>
      <td nowrap="nowrap">Define path to AntiWord (antiword): </td>
      <td align="left">
        <input type="text" id="antiword" name="antiword" placeholder="/usr/bin/antiword" value="<?php if ($antiword != "") echo $antiword; elseif (@is_executable ("/usr/bin/antiword")) echo "/usr/bin/antiword" ?>" style="width:200px;" />
      </td>
    </tr>
    <tr>
      <td nowrap="nowrap">Define path to GUNZIP (gunzip): </td>
      <td align="left">
        <input type="text" id="gunzip" name="gunzip" placeholder="/usr/bin/gunzip" value="<?php if ($gunzip != "") echo $gunzip; elseif (@is_executable ("/usr/bin/gunzip")) echo "/usr/bin/gunzip" ?>" style="width:200px;" />
      </td>
    </tr>
    <tr>
      <td nowrap="nowrap">Define path to UNZIP (unzip): </td>
      <td align="left">
        <input type="text" id="unzip" name="unzip" placeholder="/usr/bin/unzip" value="<?php if ($unzip != "") echo $unzip; elseif (@is_executable ("/usr/bin/unzip")) echo "/usr/bin/unzip" ?>" style="width:200px;" />
      </td>
    </tr>
    <tr>
      <td nowrap="nowrap">Define path to ZIP (zip): </td>
      <td align="left">
        <input type="text" id="zip" name="zip" placeholder="/usr/bin/zip" value="<?php if ($zip != "") echo $zip; elseif (@is_executable ("/usr/bin/zip")) echo "/usr/bin/zip" ?>" style="width:200px;" />
      </td>
    </tr>
    <tr>
      <td nowrap="nowrap">Define path to UNOCONV (unoconv): </td>
      <td align="left">
        <input type="text" id="unoconv" name="unoconv" placeholder="/usr/bin/unoconv" value="<?php if ($unoconv != "") echo $unoconv; elseif (@is_executable ("/usr/bin/unoconv")) echo "/usr/bin/unoconv" ?>" style="width:200px;" />
      </td>
    </tr>
    <tr>
      <td nowrap="nowrap">Define path to ImageMagick (convert): </td>
      <td align="left">
        <input type="text" id="convert" name="convert" placeholder="/usr/bin/convert" value="<?php if ($convert != "") echo $convert; elseif (@is_executable ("/usr/bin/convert")) echo "/usr/bin/convert" ?>" style="width:200px;" />
      </td>
    </tr>
    <tr>
      <td nowrap="nowrap">Define path to FFMPEG (ffmpeg): </td>
      <td align="left">
        <input type="text" id="ffmpeg" name="ffmpeg" placeholder="/usr/bin/ffmpeg" value="<?php if ($ffmpeg != "") echo $ffmpeg; elseif (@is_executable ("/usr/bin/ffmpeg")) echo "/usr/bin/ffmpeg" ?>" style="width:200px;" />
      </td>
    </tr>
    <tr>
      <td nowrap="nowrap">Define path to YAMDI (yamdi): </td>
      <td align="left">
        <input type="text" id="yamdi" name="yamdi" placeholder="/usr/bin/yamdi" value="<?php if ($yamdi != "") echo $yamdi; elseif (@is_executable ("/usr/bin/yamdi")) echo "/usr/bin/yamdi" ?>" style="width:200px;" />
      </td>
    </tr>
    <tr>
      <td nowrap="nowrap">Define path to EXIFTOOL (exiftool): </td>
      <td align="left">
        <input type="text" id="exiftool" name="exiftool" placeholder="/usr/bin/exiftool" value="<?php if ($exiftool != "") echo $exiftool; elseif (@is_executable ("/usr/bin/exiftool"))  echo "/usr/bin/exiftool" ?>" style="width:200px;" />
      </td>
    </tr>
    <tr>
      <td colspan="2" nowrap="nowrap">&nbsp;</td>
    </tr>
    
    <!-- Main Purpose of first Publication -->
    <tr>
      <td colspan="2" nowrap="nowrap" class="hcmsHeadline">Set up your first Publication</td>
    </tr>
    <tr>
      <td colspan="2" nowrap="nowrap">You can create additional Publications any time after successful installation.</td>
    </tr>
    <tr>
      <td colspan="2" nowrap="nowrap"><input name="setup_publication" value="cms" type="radio" <?php if (empty ($setup_publication) || $setup_publication == "cms") echo "checked=\"checked\""; ?> /> as a Content Management Solution (Manage content of a website)</td>
    </tr>
    <tr>
      <td colspan="2" nowrap="nowrap"><input name="setup_publication" value="dam" type="radio" <?php if ($setup_publication == "dam") echo "checked=\"checked\""; ?> /> as a Digital Asset Management Solution (Manage and share multimedia files)</td>
    </tr>
    <tr>
      <td colspan="2" nowrap="nowrap">&nbsp;</td>
    </tr>
    
    <!-- Scheduled Tasks -->
    <tr>
      <td colspan="2" nowrap="nowrap" class="hcmsHeadline">Scheduled Tasks</td>
    </tr>
    <tr>
      <td colspan="2" nowrap="nowrap">After installation the following scheduled Cron Jobs (Linux/UNIX) <br/>
      or scheduled Tasks (MS Windows) need to be created manually:<br/>
      <strong>cms/job/daily.php</strong> ... needs to be executed daily (e.g. midnight)<br/>
      <strong>cms/job/minutely.php</strong> ... needs to be executed every minute</td>
    </tr>
    <tr>
      <td colspan="2" nowrap="nowrap">
        <input type="submit" class="button hcmsButtonGreen" style="width:100%; height:40px;" value="Start installation" />
      </td>
    </tr>
  </table>
</form>

</div>

</body>
</html>
