<?php
function ldap_connect ($site, $user, $password)
{
  //  ldap_connect()     establish connection to server
  //     |
  //  ldap_bind()        anonymous or authenticated "login"
  //     |
  //  do something like search or update the directory
  //  and display the results
  //     |
  //  ldap_close()       "logout"
  
    // must be a valid LDAP server!
  $ldap_db_connect = ldap_connect ("localhost");
  
  if ($ldap_db_connect) 
  { 
    // this is an "anonymous" bind, typically
    // read-only access
    $ldap_db_bind = ldap_bind ($ldap_db_connect);     
  
    // Search surname entry
    $ldap_db_search = ldap_search ($ldap_db_bind, "o=My Company, c=US", "sn=$user");  
   
    // get entries
    if ($ldap_db_search != false && ldap_count_entries($ldap_db_connect, $ldap_db_search) == 1)
    {
      return true;
    }
    else 
    {
      return false;
    } 
  
    // close connection
    ldap_close ($ldap_db_connect);
  } 
  else 
  {
    return false;
  } 
}   
?>


