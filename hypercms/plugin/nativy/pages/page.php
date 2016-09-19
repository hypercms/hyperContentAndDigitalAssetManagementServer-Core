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
require ("../../../config.inc.php");
// hyperCMS API
require ("../../../function/hypercms_api.inc.php");
// nativy configuration
require ("../config.inc.php");


// input parameters
$site = getrequest ("site", "publicationname");
$location = getrequest ("location", "locationname");
$page = getrequest ("page", "objectname");
$language_from = getrequest ("language_from", "objectname", $nativy_config['language_from']);
$language_to = getrequest ("language_to", "objectname", $nativy_config['language_to']);
$token = getrequest ("token");

// ------------------------------ permission section --------------------------------

// check session of user
checkusersession ($user, false);

// ------------------------------ logic section --------------------------------

$show = "";

//  automatic logon to nativy is enabled
if (!empty ($nativy_config['autologon']) && checktoken ($token, $user) && !empty ($language_from) && !empty ($language_to))
{
  // partner API keys
  $partner_api_key = 'o&Tab-{z8de49nEN3P+CyaAfxr*6{}x%QJ1%hhnE5BE|T8RI#OW4UBcVIB9Volxa';
  // test: $partner_api_key = '/RN!0Q}B%ObI$V7tbI>R7+SFux8b-wZY^0My{Vc]6K2Xf{!vQ8k%4vKrxiB9+}Lh';
  $partner_private_key = 'L@GeRFbR:ox[y)A%pQ2LXx!+dXdPbD[Dy/{nBOMDt{H^2VQ8xxWY7D=s&A3v{kD$';
  // test: $partner_private_key = 'aiGspvBQ$dk5L3huua?PW5}#Jq+8Spk@}FVUveshcXO0^^Mx1t&cwW1A05*r*r55';
  $partner_timestamp = gmdate ('U');
  $partner_hmac = hash_hmac ('sha1', $partner_timestamp, $partner_private_key);
  $domain = 'https://www.nativy.com/';
  // test: $domain = 'http://www.nativytest.com/';

  // try to load user keys
  if (valid_objectname ($user) && is_file ($mgmt_config['abs_path_data']."checkout/".$user.".nativy.dat"))
  {
    $data = loadfile ($mgmt_config['abs_path_data']."checkout/", $user.".nativy.dat");
    
    list ($password, $api_public_key, $api_private_key) = explode ("<nativy/>", $data);
    
    // register in session
    if (!empty ($api_public_key) && !empty ($api_private_key))
    {
      setsession ('hcms_temp_nativy_private_key', $api_private_key);
      setsession ('hcms_temp_nativy_public_key', $api_public_key);
    }
  }

  // user POST service
  if (getsession ('hcms_temp_nativy_private_key') == "" && getsession('hcms_email') != "")
  {
    // define first and lastname
    if (strpos (getsession('hcms_realname'), " ") > 0)
    {
      list ($firstname, $lastname) = explode (" ", getsession('hcms_realname'));
    }
    elseif (strpos (getsession('hcms_realname'), ",") > 0)
    {
      list ($lastname, $firstname) = explode (",", getsession('hcms_realname'));
    }
    elseif (getsession ('hcms_realname') != "")
    {
      $firstname = "";
      $lastname = getsession ('hcms_realname');
    }
    else
    {
      $firstname = "";
      $lastname = $user;
    }
    
    $url = $domain.'connect/user';
                  
    $nativy_user = array(
        'nativy_partner_api_key' => $partner_api_key,
        'nativy_partner_api_sign' => $partner_hmac,
        'nativy_partner_timestamp' => $partner_timestamp,
        'gender' => '', 
        'firstname' => trim($firstname), 
        'lastname' => trim($lastname), 
        'email' => getsession('hcms_email'), 
        'company' => $nativy_config['company'], 
        'street' => $nativy_config['street'], 
        'zip' => $nativy_config['zip'], 
        'city' => $nativy_config['city'], 
        'country_code' => $nativy_config['country_code'], 
        'password' => $nativy_config['password'],
        'vatnumber' => $nativy_config['vatnumber'],
        'phone' => $nativy_config['phone'],
        'degree' => ''
    );
     
    $nativy_user = json_encode ($nativy_user, JSON_UNESCAPED_SLASHES);
  
    $ch = curl_init ($url);
    curl_setopt ($ch, CURLOPT_URL, $url); 
    curl_setopt ($ch, CURLOPT_HTTPHEADER, array('Content-type: application/json'));
    curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1); 
    curl_setopt ($ch, CURLOPT_POST, 1); 
    curl_setopt ($ch, CURLOPT_POSTFIELDS, $nativy_user);
    $response = curl_exec ($ch);
    curl_close ($ch);

    $nativy_response = json_decode ($response, true);

    // register in session
    if (valid_objectname ($user) && !empty ($nativy_response['api_private_key']))
    {
      // use the private key of the user to sign requests for this user
      setsession ('hcms_temp_nativy_private_key', $nativy_response['api_private_key']);
      setsession ('hcms_temp_nativy_public_key', $nativy_response['api_public_key']);
      
      // save user keys in data file
      savefile ($mgmt_config['abs_path_data']."checkout/", $user.".nativy.dat", $nativy_config['password']."<nativy/>".$nativy_response['api_public_key']."<nativy/>".$nativy_response['api_private_key']);
    }
    
    // service message
    if (!empty ($nativy_response['Explanation'])) $show .= $nativy_response['Explanation']."<br />";
  }
  
  // if private key is available
  if (getsession ('hcms_temp_nativy_private_key') != "")
  {
    // define keys for services
    $user_api_key = getsession ('hcms_temp_nativy_public_key');
    $user_private_key = getsession ('hcms_temp_nativy_private_key');
    $user_timestamp = gmdate ('U');
    $user_hmac = hash_hmac ('sha1', $user_timestamp, $user_private_key); 
    
    // authenticate user
    $url = $domain.'connect/user/authtoken';
             
    $tokenrequest = array(
        'api_key' => $partner_api_key,
        'api_sign' => $partner_hmac,
        'timestamp' => $partner_timestamp,
        'username' => getsession('hcms_email'), 
        'password' => $nativy_config['password']
    );
     
    $nativy_logon = json_encode ($tokenrequest, JSON_UNESCAPED_SLASHES);
 
    $ch = curl_init ($url);
    curl_setopt ($ch, CURLOPT_URL, $url); 
    curl_setopt ($ch, CURLOPT_HTTPHEADER, array('Content-type: application/json'));
    curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1); 
    curl_setopt ($ch, CURLOPT_POST, 1); 
    curl_setopt ($ch, CURLOPT_POSTFIELDS, $nativy_logon);
    $response = curl_exec ($ch);
    curl_close ($ch);

    $nativy_token = json_decode ($response, true);

    // service message
    if (!empty ($nativy_token['Explanation'])) $show .= $nativy_token['Explanation']."<br />";
    
    // nativy token (valid for 30 mins)
    if (!empty ($nativy_token['tmpl'])) $tmpl = $nativy_token['tmpl'];

    // get content
    if (valid_publicationname ($site) && valid_locationname ($location) && valid_objectname ($page))
    {
      $object_info = getobjectinfo ($site, $location, $page, "sys");
      
      // media file
      if (!empty ($object_info['media']))
      {
        $mediafile = getmedialocation ($site, $object_info['media'], "abs_path_media").$site."/".$object_info['media'];
        
        // base64 encode content of media file
        $mediadata = base64_encode (file_get_contents ($mediafile));
        
        // POST content file 
        if ($mediadata != "")
        {
          $url = $domain.'connect/contentfile';
        
          $nativy_contentfile = array(
              'api_key' => $user_api_key,
              'api_sign' => $user_hmac,
              'timestamp' => $user_timestamp,
              'language' => $language_from, 
              'file_name' => $object_info['name'],
              'file_base64_encoded' => $mediadata
          );
           
          $nativy_contentfile = json_encode ($nativy_contentfile, JSON_UNESCAPED_SLASHES);
           
          $ch = curl_init ($url); 
          curl_setopt($ch, CURLOPT_URL, $url); 
          curl_setopt ($ch, CURLOPT_HTTPHEADER, array('Content-type: application/json'));
          curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1); 
          curl_setopt ($ch, CURLOPT_POST, 1); 
          curl_setopt ($ch, CURLOPT_POSTFIELDS, $nativy_contentfile);
          $response = curl_exec ($ch);
          curl_close ($ch);

          $nativy_contentfile = json_decode ($response, true);
        
          // service message
          if (!empty ($nativy_contentfile['Explanation'])) $show .= $nativy_contentfile['Explanation']."<br />";
          
          // content ID
          if (!empty ($nativy_contentfile['contentid'])) $contentid = $nativy_contentfile['contentid'];
          
          // POST content bundle
          if (!empty ($contentid))
          {
            $url = $domain.'connect/contentbundle';
          
            $nativy_contentbundle = array(
                'api_key' => $user_api_key,
                'api_sign' => $user_hmac,
                'timestamp' => $user_timestamp,
                'language_from' => $language_from,
                'general_input_type' => 'contentid',
                'contentid_list' => array ($contentid)
            );
             
            $nativy_contentbundle = json_encode ($nativy_contentbundle, JSON_UNESCAPED_SLASHES);
             
            $ch = curl_init ($url); 
            curl_setopt($ch, CURLOPT_URL, $url); 
            curl_setopt ($ch, CURLOPT_HTTPHEADER, array('Content-type: application/json'));
            curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1); 
            curl_setopt ($ch, CURLOPT_POST, 1); 
            curl_setopt ($ch, CURLOPT_POSTFIELDS, $nativy_contentbundle);
            $response = curl_exec ($ch);
            curl_close ($ch);

            $nativy_contentbundle = json_decode ($response, true);
          
            // service message
            if (!empty ($nativy_contentbundle['Explanation'])) $show .= $nativy_contentbundle['Explanation']."<br />";
            
            // content bundle reference
            if (!empty ($nativy_contentbundle['contentbundlereference'])) $contentbundlereference = $nativy_contentbundle['contentbundlereference'];
          }
        }
      }
      // page or component
      elseif (!empty ($object_info['content']))
      {
        // load container
        $contentdata = loadcontainer ($object_info['content'], "work", "sys");
        
        if ($contentdata != "")
        {
          // get character set and content-type
          $charset_array = getcharset ($site, $contentdata);
                
          // set character set
          if (!empty ($charset_array['charset'])) $charset = $charset_array['charset'];
          else $charset = $mgmt_config[$site]['default_codepage'];
          
          // get metadata
          $text_array = getmetadata ("", "", $contentdata, "array", $site."/".$object_info['template']);
        
          $text_from = array();
        
          if (is_array ($text_array))
          {
            foreach ($text_array as $key => $value)
            {
              if (trim ($key) != "")
              {
                // convert text
                if (strtolower ($charset) != "utf-8")
                {
                  $key = convertchars ($key, "UTF-8", $charset);
                  $name = convertchars ($value, "UTF-8", $charset);
                }
                
                $text_from[] = array('Key' => $key, 'Value' => $value);
              }
            }
          }
        }
      }
    
      // POST to saveorder service
      $url = $domain.'connect/saveorder';
  
      // media file
      if (!empty ($contentbundlereference))
      {
        $nativy_order = array(
          'api_key' => $user_api_key,
          'api_sign' => $user_hmac,
          'timestamp' => $user_timestamp,
          'callbacklink' => $mgmt_config['url_path_cms'].'plugin/nativy/pages/page.php',
          'language_from' => $language_from,
          'language_to' => $language_to,
          'contentbundlereference' => $contentbundlereference
        );
      }
      // page or component
      elseif (!empty ($text_from) && is_array ($text_from) && sizeof ($text_from) > 0)
      {
        $nativy_order = array(
          'api_key' => $user_api_key,
          'api_sign' => $user_hmac,
          'timestamp' => $user_timestamp,
          'callbacklink' => $mgmt_config['url_path_cms'].'plugin/nativy/pages/page.php',
          'language_from' => $language_from, 
          'language_to' => $language_to,
          'text_from' => $text_from
        );
      }
      
      if (!empty ($nativy_order))
      {
        $nativy_order = json_encode ($nativy_order, JSON_UNESCAPED_SLASHES);
         
        $ch = curl_init ($url); 
        curl_setopt($ch, CURLOPT_URL, $url); 
        curl_setopt ($ch, CURLOPT_HTTPHEADER, array('Content-type: application/json'));
        curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1); 
        curl_setopt ($ch, CURLOPT_POST, 1); 
        curl_setopt ($ch, CURLOPT_POSTFIELDS, $nativy_order);
        $response = curl_exec ($ch);
        curl_close ($ch);

        $nativy_order = json_decode ($response, true);
    
        // service message
        if (!empty ($nativy_order['Explanation'])) $show .= $nativy_order['Explanation']."<br />";
      }
    }
  }
}
?>
<!DOCTYPE html>
<html>
<head>
<title>hyperCMS</title>
<meta charset="<?php echo getcodepage ($lang); ?>" />
<link rel="stylesheet" href="<?php echo getthemelocation(); ?>css/main.css" />
<script src="../../../javascript/main.js" type="text/javascript"></script>
<script src="../../../javascript/click.js" type="text/javascript"></script>
</head>

<body class="hcmsWorkplaceGeneric" background="<?php echo getthemelocation(); ?>img/backgrd_empty.png">

<!-- top bar -->
<?php echo showtopbar ($hcms_lang['translate'][$lang], $lang); ?>

<?php echo showmessage ($show, 460, 70, $lang, "position:fixed; left:15px; top:35px;"); ?>  

<div id="WorkplaceFrameLayer" class="hcmsWorkplaceFrame">
  <b>Order professional translations</b><br/>
  <b>Get free instant quotes on price and delivery for 500 language pairs.</b><br/>
  Please note that this is a manual translation service and is not part of the automated translation service of the system.<br/><br/>
  <iframe framborder="0" style="border:1px solid #000; width:92%; height:700px;"
     src="<?php echo $domain; ?>/publicinterface/npi?<?php if (!empty ($lang)) echo "destlocale=".url_encode($lang); ?><?php if (!empty ($nativy_order['orderid'])) echo "&orderid=".url_encode($nativy_order['orderid']); ?>&nxpartneruser=info@hypercms.net<?php if (!empty ($tmpl)) echo "&tmpl=".url_encode($tmpl); ?>">
  </iframe>
</div>

</body>
</html>