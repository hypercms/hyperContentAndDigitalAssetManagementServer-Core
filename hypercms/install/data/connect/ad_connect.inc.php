<?php
// function: authconnect()
// input: username, password
// output: true / false
// requires: config.inc.php to be loaded before

// description:
// Autehticates a user by username and password using Active Directory.
// Authenticating to Active Directory does not actually set up a session or perform any sort of login functionality.
// The attempt() method merely tries to bind to your LDAP server as the specified user and returns true / false on its result.
// You need to add functionality if you want to synchronize user data, like the membership to user groups for instance.  

function authconnect ($user, $password)
{
  global $mgmt_config;
  
	if ($user != "" && $password != "" && is_file ($mgmt_config['abs_path_cms']."connector/library/ad-ldap2-api/src/adLDAP.php"))
  {
		// include the class
    include ($mgmt_config['abs_path_cms']."connector/library/ad-ldap2-api/src/adLDAP.php");

    // create a configuration array using mandatory configuration options
    $config = [
      'account_suffix'        => '@corp.acme.org',
      'domain_controllers'    => ['ACME-DC01.corp.acme.org'],
      'base_dn'               => 'dc=corp,dc=acme,dc=org',
      'admin_username'        => $user,
      'admin_password'        => $password
    ];
    
    // create a new connection provider
    $provider = new \Adldap\Connections\Provider($config);
    
    try
    {
      if ($provider->auth()->attempt($user, $password))
      {
        // credentials were correct
        return true;
      }
      else
      {
        // credentials were incorrect
        return false;
      }
    }
  }
  else return false;
}
?>