<?php
/*
 * This file is part of
 * hyper Content & Digital Management Server - http://www.hypercms.com
 * Copyright (c) by hyper CMS Content Management Solutions GmbH
 */


// initialize
$mgmt_config = array();
$publ_config = array();

 // Depending how the user accessed our page we are setting our protocol
$mgmt_config['url_protocol'] = (!empty($_SERVER['HTTPS'])) ? 'https://' : 'http://';

// get current working directory
$base_url = $_SERVER['SERVER_NAME'];

if (dirname ($_SERVER['REQUEST_URI']) != "")
{
  // get root URI (index.php must be removed!)
  $dirname = dirname (dirname (str_replace ("index.php", "", $_SERVER['REQUEST_URI'])));
  if ($dirname != "/" && $dirname != "") $base_url .= $dirname;
}

$base_path = dirname (dirname (getcwd()));

// Directory name of hyperCMS application
$hypercms_dir = basename (dirname (getcwd()));

// correct path if Windows is used (backslash instead of forward slash)
$base_path = rtrim (str_replace ("\\", "/", $base_path), "/");

// URL and absolute path to hyperCMS on your webserver (e.g. /home/domain/hyperCMS/)
$mgmt_config['url_path_cms'] = $mgmt_config['url_protocol'].$base_url."/".$hypercms_dir."/";
$mgmt_config['url_path_cms_sub'] = $base_url."/".$hypercms_dir."/";
$mgmt_config['abs_path_cms'] = $base_path."/".$hypercms_dir."/";

// URL and absolute path to hyperCMS repository on your webserver (e.g. /home/domain/repository/)
// the repository includes the XML content repository, the component repository, 
// the content-media and template-media repository
$mgmt_config['url_path_rep'] = $mgmt_config['url_protocol'].$base_url."/repository/";
$mgmt_config['url_path_rep_sub'] = $base_url."/repository/";
$mgmt_config['abs_path_rep'] = $base_path."/repository/";

// URL and absolute path to hyperCMS data on your webserver (e.g. /home/domain/hyperCMS/data/)
// data is used for the storage of internal content management information   
$mgmt_config['url_path_data'] = $mgmt_config['url_protocol'].$base_url."/data/";
$mgmt_config['url_path_data_sub'] = $base_url."/data/";
$mgmt_config['abs_path_data'] = $base_path."/data/";

// Absolute path to the temporary directory
// Used for the storage of temporary data
$mgmt_config['url_path_temp'] = $mgmt_config['url_path_data']."temp/";
$mgmt_config['abs_path_temp'] = $mgmt_config['abs_path_data']."temp/";

// URL and absolute path to MyPublication on your webserver (e.g. /home/domain/hyperCMS/mypublication/)
$publ_config['url_path_mypublication'] = $mgmt_config['url_protocol'].$base_url."/mypublication/";
$publ_config['abs_path_mypublication'] = $base_path."/mypublication/";

// set theme name
$mgmt_config['theme'] = "standard";

// set DB connectivity
$mgmt_config['db_connect_rdbms'] = "db_connect_rdbms.php";
$mgmt_config['dbconnect'] = "mysql";
$mgmt_config['dbcharset'] = "utf8";
$mgmt_config['rdbms_log'] = true;

// define crypt level
$mgmt_config['crypt_level'] = "strong";

// set user and language
$user = "sys";
$lang = "en";

// hyperCMS API
require ($mgmt_config['abs_path_cms']."function/hypercms_api.inc.php");

// version info
require ($mgmt_config['abs_path_cms']."version.inc.php");

// extract version number (important since $mgmt_config array will be reset during the installation process)
if (!empty ($mgmt_config['version'])) $version_number = substr ($mgmt_config['version'], (strpos ($mgmt_config['version'], " ") + 1));

// detect browser
if (is_mobilebrowser ()) $is_mobile = 1;
else $is_mobile = 0;

// check for existing installation (using check.dat)
if (is_file ($mgmt_config['abs_path_data']."check.dat"))
{
  $check = loadfile ($mgmt_config['abs_path_data'], "check.dat");

  if ($check != "")
  {
?>
<!DOCTYPE html>
<html lang="en">
<head>
<title>hyperCMS</title>
<meta charset="UTF-8" />
<meta name="theme-color" content="#000000" />
<meta name="viewport" content="width=device-width; initial-scale=0.7; maximum-scale=1.0; user-scalable=1;" />
<link rel="stylesheet" href="../theme/standard/css/main.css?v=<?php echo getbuildnumber(); ?>" />
<link rel="stylesheet" href="../theme/standard/css/<?php echo ($is_mobile ? "mobile.css" : "desktop.css"); ?>?v=<?php echo getbuildnumber(); ?>" />
</head>
<body class="hcmsStartScreen">
  <div style="width:420px; margin:120px auto;">
    <img src="<?php echo $mgmt_config['url_path_cms']."theme/standard/img/logo.png"; ?>" style="max-width:420px; max-height:45px; border:0; margin:10px 0px 20px 0px;" /><br />
    <span style="font-size:16px; position:relative;">The hyper Content &amp; Digital Asset Management Server is already installed</span>
    <br/><br/>
    <span style="font-size:12px; position:relative;">Visit <a href="https://www.hypercms.com">www.hypercms.com</a> for more information</span>
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
$language = getrequest_esc ("language", "objectname", "en");
$email = getrequest_esc ("email");

$db_host = getrequest_esc ("db_host");
$db_username = getrequest_esc ("db_username");
$db_password = getrequest ("db_password");
$db_name = getrequest_esc ("db_name");

$smtp_host = getrequest_esc ("smtp_host");
$smtp_username = getrequest_esc ("smtp_username");
$smtp_password = getrequest_esc ("smtp_password");
$smtp_port = getrequest_esc ("smtp_port", "numeric", 25);
$smtp_sender = getrequest_esc ("smtp_sender");

$os_cms = getrequest_esc ("os_cms");

$pdftotext = getrequest_esc ("pdftotext");
$antiword = getrequest_esc ("antiword");
$unzip = getrequest_esc ("unzip");
$zip = getrequest_esc ("zip");
$unoconv = getrequest_esc ("unoconv");
$convert = getrequest_esc ("convert");
$ffmpeg = getrequest_esc ("ffmpeg");
$yamdi = getrequest_esc ("yamdi");
$exiftool = getrequest_esc ("exiftool");
$tesseract = getrequest_esc ("tesseract");
$html2pdf = getrequest_esc ("html2pdf");
$x11 = getrequest_esc ("x11");
$mergepdf = getrequest_esc ("mergepdf");

$setup_publication = getrequest_esc ("setup_publication");

$token = getrequest ("token");

// --------------------------------- logic section ----------------------------------

// initialize
$show = "";

// ------------------- basic checks -------------------

// file structure
if (empty ($mgmt_config['abs_path_cms']."config/") || !is_writeable ($mgmt_config['abs_path_cms']."config/")) $show .= "<li>Write permission for config-directory is missing (".$mgmt_config['abs_path_cms']."config/). Please make the Webserver user the owner of this directory.</li>\n";
if (empty ($mgmt_config['abs_path_data']) || !is_writeable ($mgmt_config['abs_path_data'])) $show .= "<li>Write permission for data-directory is missing (".$mgmt_config['abs_path_data']."). Please make the Webserver user the owner of this directory.</li>\n";
if (empty ($mgmt_config['abs_path_rep']) || !is_writeable ($mgmt_config['abs_path_rep'])) $show .= "<li>Write permission for repository-directory is missing (".$mgmt_config['abs_path_rep']."). Please make the Webserver user the owner of this directory.</li>\n";
if (empty ($publ_config['abs_path_mypublication']) || !is_writeable ($publ_config['abs_path_mypublication'])) $show .= "<li>Write permissions for publication-directory is missing (".$publ_config['abs_path_mypublication']."). Please make the Webserver user the owner of this directory.</li>\n";

// mbstring support
if (!function_exists ("mb_detect_encoding")) $show .= "<li>The PHP mbstring extension is disabled or missing.</li>\n";

// mysqli support
if (!function_exists ("mysqli_connect")) $show .= "<li>The PHP mysqli extension is disabled or missing.</li>\n";

// ldap support
if (is_dir ($mgmt_config['abs_path_cms']."connector/authconnect") && !function_exists ("ldap_add")) $show .= "<li>The PHP ldap extension is disabled or missing.</li>\n";

// bcmath support required by TCPDF and Azure, Google Cloud libraries
if (!function_exists ("bcadd")) $show .= "<li>The PHP bcmath extension is disabled or missing.</li>\n";

// ----------------- install hyperCMS -----------------

if ($action == "install" && !empty ($mgmt_config['abs_path_cms']) && checktoken ($token, $user))
{
  // check version
  if (empty ($version_number))
  {
    $show .= "<li>The version information is missing!</li>\n";
  }

  // create data and repository file structure
  if (!empty ($mgmt_config['abs_path_data']) && !empty ($mgmt_config['abs_path_rep']) && !empty ($publ_config['abs_path_mypublication']))
  {   
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

    // create contentcount.dat file
    if (!is_file ($mgmt_config['abs_path_data']."contentcount.dat")) 
    {
      $result = savefile ($mgmt_config['abs_path_data'], "contentcount.dat", "0");
      if ($result == false) $show .= "<li>contentcount.dat file could not be created!</li>\n";
    }
  }

  // create database
  if ($show == "")
  {
    if (trim ($db_host) != "" && trim ($db_username) != "" && trim ($db_password) != "" && trim ($db_name) != "")
    {
      // check for whitespaces
      if (preg_match ('/\s/', $db_host) > 0) $show .= "<li>Whitespaces in '".$db_host."' are not allowed!</li>";
      if (preg_match ('/\s/', $db_username) > 0) $show .= "<li>Whitespaces in '".$db_username."' are not allowed!</li>";
      if (preg_match ('/\s/', $db_name) > 0) $show .= "<li>Whitespaces in '".$db_name."' are not allowed!</li>";
    
      if ($show == "")
      {
        // connect to MySQL
        try
        {
          $mysqli = new mysqli (trim ($db_host), trim ($db_username), trim ($db_password));      
          if ($mysqli->connect_errno) $show .= "<li>DB error (".$mysqli->connect_errno."): ".$mysqli->connect_error."</li>\n";
        }
        catch (Exception $e)
        {
          $show .= "<li>DB error: Connection failed ".$e->getMessage()."</li>\n";
        }
        
        if ($show == "")
        {
          // select and create database
          if (!$mysqli->select_db (trim ($db_name)))
          {
            $sql = "CREATE DATABASE ".trim ($db_name);
          
            if (!$mysqli->query ($sql)) $show .= "<li>DB error (".$mysqli->errno."): ".$mysqli->error."</li>\n";
            elseif (!$mysqli->select_db ($db_name)) $show .= "<li>DB error (".$mysqli->errno."): ".$mysqli->error."</li>\n";
          }
          
          // create tables
          if ($show == "")
          {
            // check if objects exist already
            if ($result = $mysqli->query ('SELECT count(*) AS count FROM object'))
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
            else $show .= "<li>Error creating tables: the database is not empty</li>\n";
          }
        }
      }
    }
    else
    {
      $show .= "<li>Error creating database: Please provide all database credentials</li>\n";
    }
  }
  
  sleep (1);
  
  // edit admin user
  if ($show == "")
  {
    if (trim ($password) != "" && trim ($confirm_password) != "" && trim ($language) != "" && trim ($email) != "")
    {
      $result = edituser ("*Null*", "admin", "", trim ($password), trim ($confirm_password), 1, 0, $realname, $language, "*Leave*", "standard", trim ($email), "*Leave*", "*Leave*", "*Leave*", "*Leave*", "*Leave*", "*Leave*", $user);
      if (empty ($result['result'])) $show .= "<li>".strip_tags ($result['message'])."</li>\n";
    }
    else $show .= "<li>Please provide all information for the Administrator Account</li>\n";
  }

  // create configs
  if ($show == "" && $os_cms != "" && !empty ($mgmt_config['url_path_cms']) && !empty ($mgmt_config['abs_path_cms']))
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
        $config = str_replace ("%unzip%", $unzip, $config);
        $config = str_replace ("%zip%", $zip, $config);
        $config = str_replace ("%unoconv%", $unoconv, $config);
        $config = str_replace ("%convert%", $convert, $config);
        $config = str_replace ("%ffmpeg%", $ffmpeg, $config);
        $config = str_replace ("%yamdi%", $yamdi, $config);
        $config = str_replace ("%exiftool%", $exiftool, $config);
        $config = str_replace ("%tesseract%", $tesseract, $config);
        $config = str_replace ("%html2pdf%", $html2pdf, $config);
        $config = str_replace ("%x11%", $x11, $config);
        $config = str_replace ("%mergepdf%", $mergepdf, $config);
        
        $config = str_replace ("%dbhost%", $db_host, $config);
        $config = str_replace ("%dbuser%", $db_username, $config);
        $config = str_replace ("%dbpasswd%", $db_password, $config);
        $config = str_replace ("%dbname%", $db_name, $config);
          
        $config = str_replace ("%smtp_host%", $smtp_host, $config);
        $config = str_replace ("%smtp_username%", $smtp_username, $config);
        $config = str_replace ("%smtp_password%", $smtp_password, $config);
        $config = str_replace ("%smtp_port%", intval ($smtp_port), $config);
        $config = str_replace ("%smtp_sender%", $smtp_sender, $config);
        
        $config = str_replace ("%instances%", "", $config);
        
        // enable the GD library and disable ImageMagick
        if ($convert == "") $config = str_replace (array("// \$mgmt_imagepreview['.gif", "\$mgmt_imagepreview['.ai"), array("\$mgmt_imagepreview['.gif", "// \$mgmt_imagepreview['.ai"), $config);
        
        $result_config = savefile ($mgmt_config['abs_path_cms']."config/", "config.inc.php", $config);
        
        if ($result_config == false) $show .= "<li>Create of config file failed. Please check write permissions of config/config.inc.php.</li>\n";
      }
    }
    else $show .= "<li>The path to your installation directory could not be determinded!</li>\n";
    
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
      if (!$result['result']) $show .= "<li>".strip_tags ($result['message'])."</li>\n"; 

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
          $setting['taxonomy'] = false;
          $setting['upload_userinput'] = false;
          $setting['upload_pages'] = false;
          $setting['youtube'] = false;
          $setting['theme'] = "standard";
          $setting['storage'] = "";
          $setting['default_codepage'] = "UTF-8";
          $setting['url_path_page'] = $publ_config['url_path_mypublication'];
          $setting['abs_path_page'] = $publ_config['abs_path_mypublication'];
          $setting['exclude_folders'] = "";
          $setting['allow_ip'] = "";
          $setting['mailserver'] = $smtp_sender;
          $setting['publ_os'] = $os_cms;
          $setting['remoteclient'] = "";
          $setting['url_publ_page'] = $publ_config['url_path_mypublication'];
          $setting['abs_publ_page'] = $publ_config['abs_path_mypublication'];
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
          $setting['taxonomy'] = false;
          $setting['upload_userinput'] = false;
          $setting['upload_pages'] = false;
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
        if (!$result['result']) $show .= "<li>".strip_tags ($result['message'])."</li>\n"; 
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
    
    // connect to MySQL
    try
    {
      $mysqli = new mysqli ($db_host, $db_username, $db_password);      
      if ($mysqli->connect_errno) $show .= "<li>DB error (".$mysqli->connect_errno."): ".$mysqli->connect_error."</li>\n";
    }
    catch (Exception $e)
    {
      $show .= "<li>DB error: Connection failed ".$e->getMessage()."</li>\n";
    }
    
    if ($show == "")
    {
      // create folders
      if (!is_dir (deconvertpath ("%comp%/".$site."/Multimedia")))
      {
        $result = createfolder ($site, "%comp%/".$site."/", "Multimedia", $user);
           
        if (isset ($result['result']) && !$result['result']) $show .= "<li>Multimedia folder could not be created:<br/>".strip_tags ($result['message'])."</li>\n";
      }
      
      if (!is_dir (deconvertpath ("%page%/".$site."/AboutUs")))
      {
        $result = createfolder ($site, "%page%/".$site."/", "AboutUs", $user);
           
        if (isset ($result['result']) && $result['result'])
        {
          $contentdata = settext ($site, $result['container_content'], $result['container'], array('NavigationSortOrder'=>'2'), "u", "no", $user, $user, "UTF-8");
          
          // save working xml content container
          if (!savecontainer ($result['container'], "work", $contentdata, $user)) $show .= "<li>Content of folder 'AboutUs' could not be saved!</li>\n";
        }
        else $show .= "<li>About us folder could not be created:<br/>".strip_tags ($result['message'])."</li>\n";
      }
      
      if (!is_dir (deconvertpath ("%page%/".$site."/Products")))
      {
        $result = createfolder ($site, "%page%/".$site."/", "Products", $user);
        
        if (isset ($result['result']) && $result['result'])
        {
          $contentdata = settext ($site, $result['container_content'], $result['container'], array('NavigationSortOrder'=>'3'), "u", "no", $user, $user, "UTF-8");
          
          // save working xml content container
          if (!savecontainer ($result['container'], "work", $contentdata, $user)) $show .= "<li>Content of folder 'Products' could not be saved!</li>\n";
        }
        else $show .= "<li>Products folder could not be created:<br/>".strip_tags ($result['message'])."</li>\n";
      }
      
      if (!is_dir (deconvertpath ("%page%/".$site."/Contact")))
      {
        $result = createfolder ($site, "%page%/".$site."/", "Contact", $user);
        
        if (isset ($result['result']) && $result['result'])
        {
          $contentdata = settext ($site, $result['container_content'], $result['container'], array('NavigationSortOrder'=>'4'), "u", "no", $user, $user, "UTF-8");
          
          // save working xml content container
          if (!savecontainer ($result['container'], "work", $contentdata, $user)) $show .= "<li>Content of folder 'Contact' could not be saved!</li>\n";
        }
        else $show .= "<li>Contact folder could not be created:<br/>".strip_tags ($result['message'])."</li>\n";
      }
      
      // create objects
      if ($show == "")
      {
        if (!is_file (deconvertpath ("%comp%/".$site."/Multimedia/Slider1.jpg")))
        {
          $result = createmediaobject ($site, "%comp%/".$site."/Multimedia/", "Slider1.jpg", $mgmt_config['abs_path_rep']."media_tpl/".$site."/Slider1.jpg", $user);
          
          if (isset ($result['result']) && $result['result']) { $mediafile_1 = $site."/".$result['mediafile']; $mediaobject_1 = "%comp%/".$site."/Multimedia/".$result['object']; }
          else $show .= "<li>'Slider1' image could not be created:<br/>".strip_tags ($result['message'])."</li>\n";
        }
        
        if (!is_file (deconvertpath ("%comp%/".$site."/Multimedia/Slider2.jpg")))
        {
          $result = createmediaobject ($site, "%comp%/".$site."/Multimedia/", "Slider2.jpg", $mgmt_config['abs_path_rep']."media_tpl/".$site."/Slider2.jpg", $user);
          
          if (isset ($result['result']) && $result['result']) { $mediafile_2 = $site."/".$result['mediafile']; $mediaobject_2 = "%comp%/".$site."/Multimedia/".$result['object']; }
          else $show .= "<li>'Slider2' image could not be created:<br/>".strip_tags ($result['message'])."</li>\n";
        }
        
        if (!is_file (deconvertpath ("%comp%/".$site."/Multimedia/Slider3.jpg")))
        {
          $result = createmediaobject ($site, "%comp%/".$site."/Multimedia/", "Slider3.jpg", $mgmt_config['abs_path_rep']."media_tpl/".$site."/Slider3.jpg", $user);
          
          if (isset ($result['result']) && $result['result']) { $mediafile_3 = $site."/".$result['mediafile']; $mediaobject_3 = "%comp%/".$site."/Multimedia/".$result['object']; }
          else $show .= "<li>'Slider3' image could not be created:<br/>".strip_tags ($result['message'])."</li>\n";
        }
      
        if (!is_file (deconvertpath ("%comp%/".$site."/configuration.php")))
        {
          $result = createobject ($site, "%comp%/".$site."/", "configuration", "Configuration", $user);
                
          if (isset ($result['result']) && $result['result'])
          {
            $contentdata = settext ($site, $result['container_content'], $result['container'], array('title'=>'Your Name', 'slogan'=>'Your Slogan ...'), "u", "no", $user, $user, "UTF-8");
          
            // save working xml content container
            if (!savecontainer ($result['container'], "work", $contentdata, $user)) $show .= "<li>Content of object 'configuration' could not be saved!</li>\n";
          }
          else $show .= "<li>Configuration component could not be created:<br/>".strip_tags ($result['message'])."</li>\n";
          
          // publish object so the configuration will be activated
          publishobject ($site, "%comp%/".$site."/", "configuration.php", $user);
        }
        
        if (!is_file (deconvertpath ("%page%/".$site."/index.php")))
        {
          $result = createobject ($site, "%page%/".$site."/", "index", "Home", $user);
          
          if (isset ($result['result']) && $result['result'])
          {
            $contentdata = settext ($site, $result['container_content'], $result['container'], array('Title'=>'Home', 'NavigationSortOrder'=>'1'), "u", "no", $user, $user, "UTF-8");
            $contentdata = setmedia ($site, $contentdata, $result['container'], array('slide_1'=>$mediafile_1, 'slide_2'=>$mediafile_2, 'slide_3'=>$mediafile_3, 'slide_4'=>$mediafile_2, 'slide_5'=>$mediafile_1), array('slide_1'=>$mediaobject_1, 'slide_2'=>$mediaobject_2, 'slide_3'=>$mediaobject_3, 'slide_4'=>$mediaobject_2, 'slide_5'=>$mediaobject_1), "", "", "", "", "no", $user, $user, "UTF-8");

            // save working xml content container
            if (!savecontainer ($result['container'], "work", $contentdata, $user)) $show .= "<li>Content of object 'index' could not be saved!</li>\n";
          }
          else $show .= "<li>Homepage could not be created:<br/>".strip_tags ($result['message'])."</li>\n";
          
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
            if (!savecontainer ($result['container'], "work", $contentdata, $user)) $show .= "<li>Content of object 'search' could not be saved!</li>\n";
          }
          else $show .= "<li>Search page could not be created:<br/>".strip_tags ($result['message'])."</li>\n";
          
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
            if (!savecontainer ($result['container'], "work", $contentdata, $user)) $show .= "<li>Content of object 'AboutUs/index' could not be saved!</li>\n";
          }
          else $show .= "<li>About us page could not be created:<br/>".strip_tags ($result['message'])."</li>\n";
          
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
            if (!savecontainer ($result['container'], "work", $contentdata, $user)) $show .= "<li>Content of object 'Products/index' could not be saved!</li>\n";
          }
          else $show .= "<li>Products page could not be created:<br/>".strip_tags ($result['message'])."</li>\n";
          
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
            if (!savecontainer ($result['container'], "work", $contentdata, $user)) $show .= "<li>Content of object 'Contact/index' could not be saved!</li>\n";
          }
          else $show .= "<li>Contact page could not be created:<br/>".strip_tags ($result['message'])."</li>\n";
          
          // publish object so the item will be displayed in the navigation 
          publishobject ($site, "%page%/".$site."/Contact/", "index.php", $user);
        }
      }
    }
  }
  
  // show errors
  if ($show != "")
  {
    $show = "<strong>The following errors occured:</strong><br/>\n<ul>".$show."</ul>";  
  }
  // on success
  else
  {
    // update log
    if (!empty ($version_number))
    {
      savelog (array($mgmt_config['today']."|hypercms_update.inc.php|installation|".trim($version_number)."|installed version ".trim($version_number)), "update");
    }

    // forward on success
    header ("Location: ".cleandomain ($mgmt_config['url_path_cms']));
  }
}

// create secure token
$token_new = createtoken ($user);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<title>hyperCMS</title>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width; initial-scale=0.7; maximum-scale=1.0; user-scalable=1;" />
<link rel="stylesheet" href="../theme/standard/css/main.css?v=<?php echo getbuildnumber(); ?>">
<link rel="stylesheet" href="../theme/standard/css/<?php echo ($is_mobile ? "mobile.css" : "desktop.css"); ?>?v=<?php echo getbuildnumber(); ?>" />
<script type="text/javascript" src="../javascript/main.min.js"></script>

<style type="text/css">
#error { color:red; display:none; }
.needsfilled { color:red; }
</style>

</head>

<body class="hcmsStartScreen" style="font-size:12px; position:relative;">

<script type="text/javascript" src="../javascript/jquery/jquery-3.5.1.min.js"></script>
<script type="text/javascript">
$(document).ready(function(){
	// place ID's of all required fields here.
	required = ["password", "confirm_password", "email", "db_host", "db_username", "db_password", "db_name", "smtp_host", "smtp_port", "smtp_sender"];

	// if using an ID other than #email or #error then replace it here
	email = $("#email");
	errornotice = $("#error");

	// the text to show up within a field when it is incorrect
	emptyerror = "Please fill out this field";
	emailerror = "Please enter a valid e-mail";

	$("#installform").submit(function(){	
		// validate required fields
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

		// validate the e-mail
		if (!/^([a-zA-Z0-9_\.\-])+\@(([a-zA-Z0-9\-])+\.)+([a-zA-Z0-9]{2,4})+$/.test(email.val())) {
			email.addClass("needsfilled");
			email.val(emailerror);
		}

		// if any inputs on the page have the class 'needsfilled' the form will not submit
		if ($(":input").hasClass("needsfilled")) {
			return false;
		} else {
			errornotice.hide();
			return true;
		}
	});
	
	// clear any fields in the form when the user clicks on them
	$(":input").focus(function(){		
	   if ($(this).hasClass("needsfilled") ) {
			$(this).val("");
			$(this).removeClass("needsfilled");
	   }
	});
});
</script>

<!-- top bar -->
<?php if (!empty ($mgmt_config['version'])) echo showtopbar ("Installation of ".$mgmt_config['version'], "en"); else echo showtopbar ("Version information is missing", "en"); ?>

<!-- content area -->
<div id="content" style="max-width:580px; margin:0 auto;">

<div id="error" style="padding:4px; border:1px solid red; background:#ffdcd5;">There were errors on the form!</div>

<div style="margin:10px 0px;">
<img src="<?php echo $mgmt_config['url_path_cms']."theme/standard/img/logo.png"; ?>" style="max-width:420px; max-height:45px; border:0; margin:10px 0px 20px 0px;" /><br />
Welcome to the one-step hyper Content &amp; Digital Asset Management Server installation. 
You may want to read the <a href="<?php echo $mgmt_config['url_path_cms']; ?>help/installationguide_en.pdf" target="_blank">installation guide</a> or watch the <a href="https://youtu.be/qR_wZBSw9Ao" target="_blank">installation tutorial</a> at your leisure.<br/>
Otherwise just provide the information below and install the most powerful Content and Digital Asset Management System.
</div>

<?php if (!empty ($show)) echo showmessage ("<ul style=\"margin:10px 10px 10px -10px;\">".$show."</ul>", 580, 300, "en", "position:fixed; top:120px; margin-left:auto; margin-right:auto;"); ?>  

<form id="installform" name="installform" action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post">
  <input type="hidden" name="action" value="install" />
  <input type="hidden" name="language" value="en" />
  <input type="hidden" name="token" value="<?php echo $token_new; ?>" />
  
  <table class="hcmsTableStandard" style="width:100%;"> 
  
    <!-- Main Purpose of first Publication -->
    <tr>
      <td colspan="2" style="white-space:nowrap;" class="hcmsHeadline hcmsTextOrange">Set up your first Publication</td>
    </tr>
    <tr>
      <td colspan="2" style="white-space:nowrap;"><label><input name="setup_publication" value="cms" type="radio" <?php if (empty ($setup_publication) || $setup_publication == "cms") echo "checked=\"checked\""; ?> /> as a Content Management Solution (Manage content of a website)</label></td>
    </tr>
    <tr>
      <td colspan="2" style="white-space:nowrap;"><label><input name="setup_publication" value="dam" type="radio" <?php if ($setup_publication == "dam") echo "checked=\"checked\""; ?> /> as a Digital Asset Management Solution (Manage and share multimedia files)</label></td>
    </tr>
    <tr>
      <td colspan="2">You can create additional Publications any time after the successful installation.</td>
    </tr>
    <tr>
      <td colspan="2" style="white-space:nowrap;"><hr /></td>
    </tr>
       
    <!-- User -->
    <tr>
      <td colspan="2" style="white-space:nowrap;" class="hcmsHeadline hcmsTextOrange">hyperCMS Administrator Account</td>
    </tr>
    <tr>
      <td colspan="2">You will need this account to log in to the system after installation.</td>
    </tr>
    <tr>
      <td style="white-space:nowrap;">User name </td>
      <td style="width:300px;">
        <input type="text" id="user" name="user" value="admin" style="width:300px;" readonly="readonly" />
      </td>
    </tr>
    <tr>
      <td style="white-space:nowrap;">Password </td>
      <td>
        <input type="password" id="password" name="password" value="<?php echo $password; ?>" style="width:300px;" />
      </td>
    </tr>
    <tr>
      <td style="white-space:nowrap;">Confirm password </td>
      <td>
        <input type="password" id="confirm_password" name="confirm_password" value="<?php echo $confirm_password; ?>" style="width:300px;" />
      </td>
    </tr>
    <tr>
      <td style="white-space:nowrap;">Name </td>
      <td>
        <input type="text" id="realname" name="realname" style="width:300px;" value="<?php echo $realname; ?>" />
      </td>
    </tr>
    <tr>
      <td style="white-space:nowrap;">E-mail </td>
      <td>
        <input type="text" id="email" name="email" style="width:300px;" value="<?php echo $email; ?>" />
      </td>
    </tr>
    <tr>
      <td colspan="2" style="white-space:nowrap;"><hr /></td>
    </tr>
    
    <!-- Database -->
    <tr>
      <td colspan="2" style="white-space:nowrap;" class="hcmsHeadline hcmsTextOrange">MariaDB/MySQL Database</td>
    </tr>
    <tr>
      <td colspan="2">Please make sure that a database with the same name does not exist.</td>
    </tr>
    <tr>
      <td style="white-space:nowrap;">Database host </td>
      <td>
        <input type="text" id="db_host" name="db_host" value="<?php echo $db_host; ?>" style="width:300px;" />
      </td>
    </tr>
    <tr>
      <td style="white-space:nowrap;">Database user name </td>
      <td>
        <input type="text" id="db_username" name="db_username" value="<?php echo $db_username; ?>" style="width:300px;" />
      </td>
    </tr>
    <tr>
      <td style="white-space:nowrap;">Database password </td>
      <td>
        <input type="password" id="db_password" name="db_password" value="<?php echo $db_password; ?>" style="width:300px;" />
      </td>
    </tr>
    <tr>
      <td style="white-space:nowrap;">Database name </td>
      <td>
        <input type="text" id="db_name" name="db_name" value="<?php echo $db_name; ?>" style="width:300px;" />
      </td>
    </tr>
    <tr>
      <td colspan="2" style="white-space:nowrap;"><hr /></td>
    </tr>
    
    <!-- SMTP -->
    <tr>
      <td colspan="2" style="white-space:nowrap;" class="hcmsHeadline hcmsTextOrange">SMTP/Mail Server</td>
    </tr>
    <tr>
      <td colspan="2">Please provide a valid SMTP host for features like task management, 
      workflow management, send mail-links and others.</td>
    </tr>
    <tr>
      <td style="white-space:nowrap;">SMTP host </td>
      <td>
        <input type="text" id="smtp_host" name="smtp_host" value="<?php echo $smtp_host; ?>" style="width:300px;" />
      </td>
    </tr>
    <tr>
      <td style="white-space:nowrap;">SMTP user name </td>
      <td>
        <input type="text" id="smtp_username" name="smtp_username" value="<?php echo $smtp_username; ?>" style="width:300px;" />
      </td>
    </tr>
    <tr>
      <td style="white-space:nowrap;">SMTP password </td>
      <td>
        <input type="password" id="smtp_password" name="smtp_password" value="<?php echo $smtp_password; ?>" style="width:300px;" />
      </td>
    </tr>
    <tr>
      <td style="white-space:nowrap;">SMTP port </td>
      <td>
        <input type="text" id="smtp_port" name="smtp_port" value="<?php if ($smtp_port != "") echo intval ($smtp_port); else echo "25" ?>" style="width:300px;" />
      </td>
    </tr>
    <tr>
      <td style="white-space:nowrap;">SMTP sender (e-mail address) </td>
      <td>
        <input type="text" id="smtp_sender" name="smtp_sender" value="<?php echo $smtp_sender; ?>" style="width:300px;" />
      </td>
    </tr>
    <tr>
      <td colspan="2" style="white-space:nowrap;"><hr /></td>
    </tr>

    <!-- OS -->
    <tr>
      <td colspan="2" style="white-space:nowrap;" class="hcmsHeadline hcmsTextOrange">Operating System</td>
    </tr>
    <tr>
      <td colspan="2" style="white-space:nowrap;">Please specify the operating system.</td>
    </tr>
     <tr>
      <td style="white-space:nowrap;">Operating system </td>
      <td>
        <select id="os_cms" name="os_cms">
          <option value="UNIX" <?php if ($os_cms == "UNIX") echo "selected=\"selected\""; elseif ($os_cms == "") echo "selected=\"selected\"" ?>>UNIX / Linux</option>
          <option value="WIN" <?php if ($os_cms == "WIN") echo "selected=\"selected\""; ?>>Windows</option>
        </select>
      </td>
    </tr>
    <tr>
      <td colspan="2" style="white-space:nowrap;"><hr /></td>
    </tr>
    
    <!-- Executables -->
    <?php
    // disable open_basdir of php.ini
    @ini_set ("open_basedir", NULL);
    ?>
    <tr>
      <td colspan="2" style="white-space:nowrap;" class="hcmsHeadline hcmsTextOrange">Additional Software</td>
    </tr>
    <tr>
      <td colspan="2">In order to use the full set of Digital Asset Management features 
      of the system, additional software packages are required.<br/>
      The following settings provide typical examples of pathes to the 
      executables on Linux, if available. Please adopt them if not suitable.<br/>
      Attention: The open_basedir restriction might effect the search for executables.</td>
    </tr>
    <tr>
      <td style="white-space:nowrap;">Define path to XPDF (pdftotext) </td>
      <td>
        <input type="text" id="pdftotext" name="pdftotext" placeholder="autodetection failed for /usr/bin/pdftotext" value="<?php if ($pdftotext != "") echo $pdftotext; elseif (@is_executable ("/usr/bin/pdftotext")) echo "/usr/bin/pdftotext" ?>" style="width:300px;" />
      </td>
    </tr>
    <tr>
      <td style="white-space:nowrap;">Define path to AntiWord (antiword) </td>
      <td>
        <input type="text" id="antiword" name="antiword" placeholder="autodetection failed for  /usr/bin/antiword" value="<?php if ($antiword != "") echo $antiword; elseif (@is_executable ("/usr/bin/antiword")) echo "/usr/bin/antiword" ?>" style="width:300px;" />
      </td>
    </tr>
    <tr>
      <td style="white-space:nowrap;">Define path to UNZIP (unzip) </td>
      <td>
        <input type="text" id="unzip" name="unzip" placeholder="autodetection failed for /usr/bin/unzip" value="<?php if ($unzip != "") echo $unzip; elseif (@is_executable ("/usr/bin/unzip")) echo "/usr/bin/unzip" ?>" style="width:300px;" />
      </td>
    </tr>
    <tr>
      <td style="white-space:nowrap;">Define path to ZIP (zip) </td>
      <td>
        <input type="text" id="zip" name="zip" placeholder="autodetection failed for /usr/bin/zip" value="<?php if ($zip != "") echo $zip; elseif (@is_executable ("/usr/bin/zip")) echo "/usr/bin/zip" ?>" style="width:300px;" />
      </td>
    </tr>
    <tr>
      <td style="white-space:nowrap;">Define path to UNOCONV (unoconv) </td>
      <td>
        <input type="text" id="unoconv" name="unoconv" placeholder="autodetection failed for /usr/bin/unoconv" value="<?php if ($unoconv != "") echo $unoconv; elseif (@is_executable ("/usr/bin/unoconv")) echo "/usr/bin/unoconv" ?>" style="width:300px;" />
      </td>
    </tr>
    <tr>
      <td style="white-space:nowrap;">Define path to ImageMagick (convert) </td>
      <td>
        <input type="text" id="convert" name="convert" placeholder="autodetection failed for /usr/bin/convert" value="<?php if ($convert != "") echo $convert; elseif (@is_executable ("/usr/bin/convert")) echo "/usr/bin/convert" ?>" style="width:300px;" />
      </td>
    </tr>
    <tr>
      <td style="white-space:nowrap;">Define path to FFMPEG (ffmpeg) </td>
      <td>
        <input type="text" id="ffmpeg" name="ffmpeg" placeholder="autodetection failed for /usr/bin/ffmpeg" value="<?php if ($ffmpeg != "") echo $ffmpeg; elseif (@is_executable ("/usr/bin/ffmpeg")) echo "/usr/bin/ffmpeg" ?>" style="width:300px;" />
      </td>
    </tr>
    <tr>
      <td style="white-space:nowrap;">Define path to YAMDI (yamdi) </td>
      <td>
        <input type="text" id="yamdi" name="yamdi" placeholder="autodetection failed for /usr/bin/yamdi" value="<?php if ($yamdi != "") echo $yamdi; elseif (@is_executable ("/usr/bin/yamdi")) echo "/usr/bin/yamdi" ?>" style="width:300px;" />
      </td>
    </tr>
    <tr>
      <td style="white-space:nowrap;">Define path to EXIFTOOL (exiftool) </td>
      <td>
        <input type="text" id="exiftool" name="exiftool" placeholder="autodetection failed for /usr/bin/exiftool" value="<?php if ($exiftool != "") echo $exiftool; elseif (@is_executable ("/usr/bin/exiftool"))  echo "/usr/bin/exiftool" ?>" style="width:300px;" />
      </td>
    </tr>
    <tr>
      <td style="white-space:nowrap;">Define path to Tesseract OCR (tesseract) </td>
      <td>
        <input type="text" id="tesseract" name="tesseract" placeholder="autodetection failed for /usr/bin/tesseract" value="<?php if ($tesseract != "") echo $tesseract; elseif (@is_executable ("/usr/bin/tesseract")) echo "/usr/bin/tesseract" ?>" style="width:300px;" />
      </td>
    </tr>
    <tr>
      <td style="white-space:nowrap;">Define path to HTML2PDF (wkhtmltopdf) </td>
      <td>
        <input type="text" id="html2pdf" name="html2pdf" placeholder="autodetection failed for /usr/bin/wkhtmltopdf" value="<?php if ($html2pdf != "") echo $html2pdf; elseif (@is_executable ("/usr/bin/wkhtmltopdf")) echo "/usr/bin/wkhtmltopdf" ?>" style="width:300px;" />
      </td>
    </tr>
    <tr>
      <td style="white-space:nowrap;">Define path to X11-Server (xvfb-run) </td>
      <td>
        <input type="text" id="x11" name="x11" placeholder="autodetection failed for /usr/bin/xvfb-run" value="<?php if ($x11 != "") echo $x11; elseif (@is_executable ("/usr/bin/xvfb-run")) echo "/usr/bin/xvfb-run" ?>" style="width:300px;" />
      </td>
    </tr>
    <tr>
      <td style="white-space:nowrap;">Define path to PDF toolkit (pdftk) </td>
      <td>
        <input type="text" id="mergepdf" name="mergepdf" placeholder="autodetection failed for /usr/bin/pdftk" value="<?php if ($yamdi != "") echo $yamdi; elseif (@is_executable ("/usr/bin/pdftk")) echo "/usr/bin/pdftk" ?>" style="width:300px;" />
      </td>
    </tr>
    <tr>
      <td colspan="2" style="white-space:nowrap;"><hr /></td>
    </tr>
    <tr>
      <td colspan="2">The following information does not apply to the installation with Docker!</td>
    </tr>
    <tr>
      <td colspan="2" style="white-space:nowrap;"><hr /></td>
    </tr>
    <!-- Scheduled Tasks -->
    <tr>
      <td colspan="2" style="white-space:nowrap;" class="hcmsHeadline hcmsTextOrange">Scheduled Tasks</td>
    </tr>
    <tr>
      <td colspan="2">
      After installation the following scheduled Cron Jobs (Linux/UNIX) <br/>
      or scheduled Tasks (MS Windows) need to be created manually:<br/>
      <strong>hypercms/job/daily.php</strong> ... executed daily by the webserver user (e.g. midnight)<br/>
      <strong>hypercms/job/minutely.php</strong> ... executed every minute by the webserver user<br/>
      <strong>hypercms/job/update.php</strong> ... executed daily by a user with write permissions in directory 'hypercms'<br/>
      <hr />
      Please make sure that the .htaccess files of the system are supported by adding<br/>
      these directives to your Apache 2.4 configuration of your virtual host:<br/>
  		<strong>Require all granted<br/>
  		AllowOverride All<br/>
  		Options -Indexes +FollowSymLinks</strong><br/>
      <br/>
      If you are using earlier Apache versions you need to remove the Apache 2.4 directives in the .htaccess files of the system.
      </td>
    </tr>
    <tr>
      <td colspan="2" style="white-space:nowrap; padding:10px 0px;">
        <input type="submit" class="button hcmsButtonGreen" style="width:100%; height:40px;" value="Install now" />
      </td>
    </tr>
  </table>
</form>

</div>

</body>
</html>
