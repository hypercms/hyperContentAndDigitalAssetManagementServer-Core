<?php
// function: authconnect()
// input: username [string], password [string], publication name [string] (optional)
// output: true / false
// requires: config.inc.php to be loaded before

// description:
// Autehticates a user by username and password using LDAP/AD.
// Authenticating to LDAP does not actually set up a session or perform any sort of login functionality.
// You need to add functionality if you want to synchronize user data, like the membership to user groups for instance.

//  ldap_connect()     establish connection to server
//     |
//  ldap_bind()        anonymous or authenticated "login"
//     |
//  do something like search or update the directory
//  and display the results
//     |
//  ldap_close()       logout
  
function authconnect ($user, $password, $site="")
{
  global $mgmt_config;
  
  if ($user != "" && $password != "")
  {
    // publication specific configuration
    if (valid_publicationname ($site) && !empty ($mgmt_config[$site]['ldap_servers']) && !empty ($mgmt_config[$site]['ldap_userdomain']) && !empty ($mgmt_config[$site]['ldap_base_dn']))
    {
      // LDAP / Active Directory server
      $ldap_servers = $mgmt_config[$site]['ldap_servers'];

      // LDAP / Active Directory DN
      $ldap_dn = $mgmt_config[$site]['ldap_base_dn'];
      
      // Domain for purposes of constructing $user
      $ldap_userdomain = $mgmt_config[$site]['ldap_userdomain'];

      // LDAP protocol version
      $ldap_version = intval ($mgmt_config[$site]['ldap_version']);

      // port
      $ldap_port = intval ($mgmt_config[$site]['ldap_port']);

      // use SSL
      if (!empty ($mgmt_config[$site]['ldap_use_ssl'])) $ldap_use_ssl = true;
      else $ldap_use_ssl = false;

      // use TLS
      if (!empty ($mgmt_config[$site]['ldap_use_tls'])) $ldap_use_tls = true;
      $ldap_use_tls = false;

      // sync users
      if (!empty ($mgmt_config[$site]['ldap_sync'])) $ldap_sync = true;
      else $ldap_sync = false;
    }
    // general configuration for all publications and users
    elseif (!empty ($mgmt_config['ldap_servers']) && !empty ($mgmt_config['ldap_userdomain']) && !empty ($mgmt_config['ldap_base_dn']))
    {	
      // LDAP / Active Directory server
      $ldap_servers = $mgmt_config['ldap_servers'];
    
      // LDAP / Active Directory DN
      $ldap_dn = $mgmt_config['ldap_base_dn'];
    
      // Domain for purposes of constructing $user
      $ldap_userdomain = $mgmt_config['ldap_userdomain'];

      // LDAP protocol version
      $ldap_version = intval ($mgmt_config['ldap_version']);

      // port
      $ldap_port = intval ($mgmt_config['ldap_port']);

      // use SSL
      if (!empty ($mgmt_config['ldap_use_ssl'])) $ldap_use_ssl = true;
      else $ldap_use_ssl = false;

      // use TLS
      if (!empty ($mgmt_config['ldap_use_tls'])) $ldap_use_tls = true;
      $ldap_use_tls = false;

      // sync users
      if (!empty ($mgmt_config['ldap_sync'])) $ldap_sync = true;
      else $ldap_sync = false;
    }
    // required input is missing
    else
    {
      $errcode = "20100";
      $error[] = $mgmt_config['today']."|ldap_connect.inc.php|error|$errcode|required input (LDAP servers, Base DN, User domain) is missing for LDAP connection";

      // save log
      savelog (@$error);

      return false;
    }

    // LDAP user domain
    if (substr ($ldap_userdomain, 0, 1) != "@") $ldap_userdomain = "@".$ldap_userdomain;

    // LDAP servers
    $ldap_servers = splitstring ($ldap_servers);

    foreach ($ldap_servers as $key=>$value)
    {
      // use SSL
      if (!empty ($ldap_use_ssl)) $ldap_servers[$key] = "ldaps://".$ldap_servers[$key];
      else $ldap_servers[$key] = "ldap://".$ldap_servers[$key];

      // add port
      if (!empty ($ldap_port)) $ldap_servers[$key] = $ldap_servers[$key].":".$mgmt_config['ldap_port'];
    }

    if (is_array ($ldap_servers)) $ldap_servers = implode (" ", $ldap_servers);
   
  	// connect to LDAP/AD server
    if (function_exists ("ldap_connect"))
    {
      $ldap = ldap_connect ($ldap_servers);
    }
    else
    {
      $errcode = "20201";
      $error[] = $mgmt_config['today']."|ldap_connect.inc.php|error|$errcode|PHP LDAP module is missing";

      // save log
      savelog (@$error);

      return false;
    }

    if ($ldap == false)
    {
      $errcode = "20101";
      $error[] = $mgmt_config['today']."|ldap_connect.inc.php|error|$errcode|could not connect to LDAP servers: ".$ldap_servers;
    }

    // LDAP protocol version
    if (!empty ($ldap_version) && $ldap_version > 1)
    {
      if (!ldap_set_option ($ldap, LDAP_OPT_PROTOCOL_VERSION, $ldap_version))
      {
        $errcode = "20102";
        $error[] = $mgmt_config['today']."|ldap_connect.inc.php|error|$errcode|could not set LDAPv3 for LDAP servers: ".$ldap_servers;
      }
    }

    // follow referrals
    if (!empty ($mgmt_config['ldap_follow_referrals']))
    {
      ldap_set_option ($ldap, LDAP_OPT_REFERRALS, 1);
    }
    else
    {
      ldap_set_option ($ldap, LDAP_OPT_REFERRALS, 0);
    }

    // max subtrees
    // ldap_set_option ($ldap, LDAP_SCOPE_SUBTREE, 5);

    // use TLS
    if (!empty ($mgmt_config['ldap_use_tls']))
    {
      if (!ldap_start_tls ($ldap))
      {
        $errcode = "20103";
        $error[] = $mgmt_config['today']."|ldap_connect.inc.php|error|$errcode|could not start secure TLS connection to LDAP servers: ".$ldap_servers;
      }
    }
   
  	// verify user and password
  	if ($bind = @ldap_bind ($ldap, $user.$ldap_userdomain, $password))
    {
      // get group membership and info of user
      $filter = "(sAMAccountName=".$user.")";
      $attr = array('memberof', 'givenname', 'telephonenumber', 'mail');
      $result = ldap_search ($ldap, $ldap_dn, $filter, $attr);
      $entries = ldap_get_entries ($ldap, $result);
		
      ldap_unbind ($ldap);

	    // information found (valid user)
      if ($entries['count'] > 0 && !empty ($ldap_sync))
      {
        // user info
        $realname = $entries[0]['givenname'][0];
        $phone = $entries[0]['telephonenumber'][0];
        $email = $entries[0]['mail'][0];

        // get groups
        foreach ($entries[0]['memberof'] as $groups)
        {
          // synchronize user groups of AD with hyperCMS
          // or return true or false, based on the group membership
          if (!empty ($groups))
          {
            // extract group names and mapping with user groups of the system
            // or leave the publicatzion and group membership of the user as is
            $usergroup = "*Leave*";
            $usersite = "*Leave*";
          }
          // if user is not a member of any group
          else
          {
            $errcode = "20104";
            $error[] = $mgmt_config['today']."|ldap_connect.inc.php|error|$errcode|no group membership found for user '".$user.$ldap_userdomain."' at LDAP servers: ".$ldap_servers;

            // save log
            savelog (@$error);

            return false;
          }
        }

        // update user
        edituser ($site, $user, $old_password="", $password, $password, $superadmin="0", $realname, $language="", $timezone="*Leave*", $theme="*Leave*", $email, $phone, $signature="*Leave*", $usergroup, $usersite, $validdatefrom="*Leave*", $validdateto="*Leave*", "sys");
      }
      // no information found (bad user)
      else
      {
        $errcode = "20105";
        $error[] = $mgmt_config['today']."|ldap_connect.inc.php|error|$errcode|no information found for user '".$user.$ldap_userdomain."' at LDAP servers: ".$ldap_servers;

        // save log
        savelog (@$error);

        return false;
      }

      ldap_close ($ldap);

      // valid login
      return true;
	  }
    // user could not be authenticated
    else
    {
      $errcode = "20106";
      $error[] = $mgmt_config['today']."|ldap_connect.inc.php|error|$errcode|could not authenticate user '".$user.$ldap_userdomain."' at LDAP servers: ".$ldap_servers;

      // save log
      savelog (@$error);

      return false;
    }
  }
  else return false;
}   
?>


