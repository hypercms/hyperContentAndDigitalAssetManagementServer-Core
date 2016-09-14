<?php
// function: authconnect()
// input: username, password
// output: true / false
// requires: config.inc.php to be loaded before

// description:
// Autehticates a user by username and password using LDAP.
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
  
function authconnect ($user, $password)
{
  global $mgmt_config;
  
	if ($user != "" && $password != "")
  {
   	// Active Directory server
  	$ldap_host = "ldapserver.name";
   
  	// Active Directory DN
  	$ldap_dn = "OU=Departments,DC=MYDOMAIN,DC=COM";
   
  	// Domain, for purposes of constructing $user
  	$ldap_usr_dom = '@domain';
   
  	// connect to active directory
  	$ldap = ldap_connect ($ldap_host);
   
  	// verify user and password
  	if ($bind = @ldap_bind ($ldap, $user.$ldap_usr_dom, $password))
    {
  		// check group membership
  		$filter = "(sAMAccountName=".$user.")";
  		$attr = array("memberof");
  		$result = ldap_search ($ldap, $ldap_dn, $filter, $attr);
  		$entries = ldap_get_entries ($ldap, $result);
      
  		ldap_unbind ($ldap);
   
  		// check groups
  		foreach ($entries[0]['memberof'] as $groups)
      {
  			// synchronize user groups of AD with hyperCMS
        // or return true or false, based on group membership
  		}
   
      // valid login
  		return true;
    }
    // user could not be authenticated
    else return false;
  }
  else return false;
}   
?>


