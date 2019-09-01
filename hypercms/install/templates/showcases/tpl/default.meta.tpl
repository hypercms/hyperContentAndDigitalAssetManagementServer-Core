<?xml version="1.0" encoding="UTF-8" ?>
<template>
<name>default</name>
<user>admin</user>
<category>media</category>
<extension></extension>
<application>media</application>
<content><![CDATA[[hyperCMS:textu id='Title' label='Title' infotype='meta' height='30']
[hyperCMS:textu id='Description' label='Description' infotype='meta' height='90']
[hyperCMS:textu id='Keywords' label='Keywords' infotype='meta' height='90']
[hyperCMS:textu id='Copyright' label='Copyright' infotype='meta' height='30']
[hyperCMS:textu id='Creator' label='Creator' infotype='meta' height='30']
[hyperCMS:textd id='License' infotype='meta' label='License valid till']
[hyperCMS:textc id='FTP' value='Yes' infotype='meta' label='FTP Publish' groups='Administrator;admin']

[hyperCMS:scriptbegin
// Publish to FTP server
if ("[hyperCMS:textc id='FTP' onEdit='hidden']" == "Yes")
{
  $objectinfo = getobjectinfo ("%publication%", "%abs_location%", "%object%");
  
  if (!empty ($objectinfo['media']))
  {
    $message = array();
  
    // Global Connection Settings
    $ftp_server = "ftp.freezoy.com";
    $ftp_user_name = "frzoy_14879344";
    $ftp_user_pass = "rudolpho";
  
    // Path for File Upload (relative to your login dir)
    $destination_file = "/htdocs/".$objectinfo['media'];
  
    $local_file = getmedialocation ("%publication%", $objectinfo['media'], "abs_path_media")."%publication%/".$objectinfo['media'];
    
    // Connect to FTP Server
    $conn_id = ftp_connect ($ftp_server);
    
    // Verify Log In Status
    if (!$conn_id)
    {
      $message[] = date('Y-m-d H:i')."|default-template|error|20801|FTP connection to $ftp_server has failed";
    }
    else
    {
      // Login to FTP Server
      $login_result = ftp_login ($conn_id, $ftp_user_name, $ftp_user_pass);
  
      if (!$login_result)
      {
        $message[] = date('Y-m-d H:i')."|default-template|information|20802|FTP Logon to $ftp_server for user $ftp_user_name failed";
      }
      else
      {
        $message[] = date('Y-m-d H:i')."|default-template|information|20802|Connected to $ftp_server, for user $ftp_user_name";
  
        // Use passive mode
        ftp_pasv ($conn_id, true);
  
        // Upload the File
        if (is_file ($local_file))
        {
          $upload = ftp_put ($conn_id, $destination_file, $local_file, FTP_BINARY);
    
          // Verify Upload Status
          if (!$upload) $message[] = date('Y-m-d H:i')."|default-template|error|20803|FTP upload of ".$objectinfo['media']." to $destination_file has failed";
          else $message[] = date('Y-m-d H:i')."|default-template|information|20804|Success! " . $objectinfo['media'] . " has been uploaded to " . $ftp_server . $destination_file ;
        }
        else $message[] = date('Y-m-d H:i')."|default-template|error|20805|FTP local file ".$objectinfo['media']." does not exist";
      }
  
      // Close the FTP Connection
      ftp_close ($conn_id);
    }
    
    savelog ($message);
  }
}
scriptend]]]></content>
</template>