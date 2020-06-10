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


// input parameters
$site = getrequest ("site", "publicationname");
$location = getrequest ("location", "locationname");
$page = getrequest ("page", "objectname");
$company = getrequest ("company");
$street = getrequest ("street");
$zip = getrequest ("zip");
$city = getrequest ("city");
$country_code = getrequest ("country_code");
$vatnumber = getrequest ("vatnumber");
$phone = getrequest ("phone");
$password = getrequest ("password");
$token = getrequest ("token");

// ------------------------------ permission section --------------------------------

// check session of user
checkusersession ($user, false);

// ------------------------------ logic section --------------------------------

$show = "";

// partner API keys
$partner_api_key = 'o&Tab-{z8de49nEN3P+CyaAfxr*6{}x%QJ1%hhnE5BE|T8RI#OW4UBcVIB9Volxa';
// test: $partner_api_key = '/RN!0Q}B%ObI$V7tbI>R7+SFux8b-wZY^0My{Vc]6K2Xf{!vQ8k%4vKrxiB9+}Lh';
$partner_private_key = 'L@GeRFbR:ox[y)A%pQ2LXx!+dXdPbD[Dy/{nBOMDt{H^2VQ8xxWY7D=s&A3v{kD$';
// test: $partner_private_key = 'aiGspvBQ$dk5L3huua?PW5}#Jq+8Spk@}FVUveshcXO0^^Mx1t&cwW1A05*r*r55';
$partner_timestamp = gmdate ('U');
$partner_hmac = hash_hmac ('sha1', $partner_timestamp, $partner_private_key);
$domain = 'https://www.nativy.com/';
// test: $domain = 'http://www.nativytest.com/';


// =============== save general billing information for each publication ===============
if (checktoken ($token, $user))
{
  if (!is_file ($mgmt_config['abs_path_data']."checkout/".$user.".nativy.inc.php") && $company != "" && $street != "" && $password != "")
  {
    $data = "<?php
// Please provide your organizations data for the nativy account
\$nativy_config = array();
\$nativy_config['language_from'] = 'en';
\$nativy_config['language_to'] = 'de';
\$nativy_config['company'] = '".$company."';
\$nativy_config['street'] = '".$street."';
\$nativy_config['zip'] = '".$zip."';
\$nativy_config['city'] = '".$city."'; 
\$nativy_config['country_code'] = '".$country_code."'; 
\$nativy_config['vatnumber'] = '".$vatnumber."';
\$nativy_config['phone'] = '".$phone."';
\$nativy_config['password'] = '".$password."';
?>";

    // save config
    savefile ($mgmt_config['abs_path_data']."checkout/", $user.".nativy.inc.php", $data); 
  }
}

// =============== authorize user ===============
if (is_file ($mgmt_config['abs_path_data']."checkout/".$user.".nativy.inc.php"))
{
  // load config
  require ($mgmt_config['abs_path_data']."checkout/".$user.".nativy.inc.php");
  
  // set default languages
  $language_from = $nativy_config['language_from'];
  $language_to = $nativy_config['language_to'];

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

  // -------------------------------- connect ----------------------------------------
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

  // ------------------------------------- get authtoken -----------------------------------------
  // if private key is available and a company name is provided
  if (getsession ('hcms_temp_nativy_private_key') != "" && !empty ($nativy_config['company']))
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
  }

  // =============== post content and order translation ===============
  if (checktoken ($token, $user) && !empty ($language_from) && !empty ($language_to))
  {
    // validate users API key
    if (empty ($user_api_key)) 
    {
      $user_api_key = $partner_api_key;
      $user_hmac = $partner_hmac;
      $user_timestamp = $partner_timestamp;
    }
    
    // ---------------------------------------- post content ----------------------------------------
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
    }

    // -------------------------------------- saveorder ----------------------------------------
    // if automatic logon to nativy is enabled and company name is provided
    if (!empty ($user_api_key) && !empty ($user_hmac) && !empty ($user_timestamp))
    {
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
?>
<!DOCTYPE html>
<html>
<head>
<title>hyperCMS</title>
<meta charset="<?php echo getcodepage ($lang); ?>" />
<link rel="stylesheet" href="<?php echo getthemelocation(); ?>css/main.css" />
<link rel="stylesheet" href="<?php echo getthemelocation()."css/".($is_mobile ? "mobile.css" : "desktop.css"); ?>" />
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
<?php
}

// =============== define company/billing information if undefined ===============
if (!is_file ($mgmt_config['abs_path_data']."checkout/".$user.".nativy.inc.php"))
{
?>
<!DOCTYPE html>
<html>
<head>
<title>hyperCMS</title>
<meta charset="<?php echo getcodepage ($lang); ?>" />
<link rel="stylesheet" href="<?php echo getthemelocation(); ?>css/main.css" />
<link rel="stylesheet" href="<?php echo getthemelocation()."css/".($is_mobile ? "mobile.css" : "desktop.css"); ?>" />
<script src="../../../javascript/main.js" type="text/javascript"></script>
<script src="../../../javascript/click.js" type="text/javascript"></script>
<script type="text/javascript">
function checkForm()
{ 
  var form = document.forms['nativy'];
  var street = form.elements['street'];
  var zip = form.elements['zip'];
  var password = form.elements['password'];
  var terms = form.elements['terms'];
  
  if (street.value.trim() == "" || zip.value.trim() == "" || password.value.trim() == "" || terms.checked == false)
  {
    alert (hcms_entity_decode("<?php echo getescapedtext ($hcms_lang['required-parameters-are-missing'][$lang]); ?>"));
    return false;
  }
  else form.submit();
}
</script>
</head>

<body class="hcmsWorkplaceGeneric" background="<?php echo getthemelocation(); ?>img/backgrd_empty.png">

<!-- top bar -->
<?php echo showtopbar ($hcms_lang['translate'][$lang], $lang); ?>

<?php echo showmessage ($show, 460, 70, $lang, "position:fixed; left:15px; top:35px;"); ?>  

<div id="WorkplaceFrameLayer" class="hcmsWorkplaceFrame">
  <b>Order professional translations</b><br/>
  <b>Get free instant quotes on price and delivery for 500 language pairs.</b><br/>
  Please note that this is a manual translation service and is not part of the automated translation service of the system.<br/><br/>
  <form name="nativy" action="" method="post">
    <input type="hidden" name="site" value="<?php echo $site; ?>" />
    <input type="hidden" name="location" value="<?php echo $location; ?>" />
    <input type="hidden" name="page" value="<?php echo $page; ?>">
    <input type="hidden" name="token" value="<?php echo createtoken ($user); ?>">
    
    <b>Please provide the billing information for your orders:</b><br/>
    <label for="company">Company name</label><br/>
    <input type="text" name="company" id="company" value="<?php echo $company; ?>" maxlength="200" style="width:300px;" /><br/>
    <label for="street">Street*</label><br/>
    <input type="text" name="street" id="street" value="<?php echo $street; ?>" maxlength="200" style="width:300px;" /><br/>
    <label for="zip">ZIP*</label><br/>
    <input type="text" name="zip" id="zip" value="<?php echo $zip; ?>" maxlength="10" style="width:300px;" /><br/>
    <label for="city">City*</label><br/>
    <input type="text" name="city" id="city" value="<?php echo $city; ?>" maxlength="100" style="width:300px;" /><br/>
    <label for="country_code">Country code</label><br/>
    <input type="text" name="country_code" id="country_code" value="<?php echo $country_code; ?>" maxlength="4" style="width:300px;" /><br/>
    <label for="vatnumber">VAT number</label><br/>
    <input type="text" name="vatnumber" id="vatnumber" value="<?php echo $vatnumber; ?>" maxlength="20" style="width:300px;" /><br/>
    <label for="phone">Phone</label><br/>
    <input type="text" name="phone" id="phone" value="<?php echo $phone; ?>" maxlength="20" style="width:300px;" /><br/>
    <label for="password">Password*</label><br/>
    <input type="text" name="password" id="password" value="<?php echo $password; ?>" maxlength="80" style="width:300px;" /><br/>    
    <label><input type="checkbox" name="terms" id="terms" value="1" />* I have read and agree to the <a href="https://www.nativy.com/information/terms#client" target="_blank">terms of use</a>.</label><br/><br/>
    <button type="button" class="hcmsButtonGreen" onclick="checkForm()">Save and continue</button>
  </form>
</div>

<?php include_once ($mgmt_config['abs_path_cms']."include/footer.inc.php"); ?>
</body>
</html>
<?php
}
?>