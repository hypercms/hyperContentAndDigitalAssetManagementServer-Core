<?php
/*
 * This file is part of
 * hyper Content & Digital Management Server - http://www.hypercms.com
 * Copyright (c) by hyper CMS Content Management Solutions GmbH
 *
 * You should have received a copy of the license (license.txt) along with hyper Content & Digital Management Server
 */
 
// ========================================= FTP FUNCTIONS =========================================

// ----------------------------------------- ftp_userlogon -----------------------------------------
// function: ftp_userlogon()
// input: FTP servername or IP [string], user name [string], password [string], SSL [true,false] (optional)
// output: true / false on error

// description:
// This function connects and performs logon to an FTP server.

function ftp_userlogon ($server, $user, $passwd, $ssl=false)
{
  global $mgmt_config;

  if ($server != "" && $user != "" && $passwd != "")
  {
    $conn_id = false;

    // connect to FTP server
    if ($ssl) $conn_id = ftp_ssl_connect ($server);
    else $conn_id = ftp_connect ($server);

    // verify connection
    if (!$conn_id)
    {
      $error[] = date('Y-m-d H:i:s')."|hypercms_connect.inc.php|error|20101|FTP: connection to ".$server." failed";
    }
    else
    {
      // login to FTP server
      $login_result = ftp_login ($conn_id, $user, $passwd);

      if (!$login_result)
      {
        $error[] = date('Y-m-d H:i')."|hypercms_connect.inc.php|information|20102|FTP: logon to ".$server." for FTP user ".$user." failed";

        // close connection
        ftp_close ($conn_id);

        $conn_id = false;
      }
    }

    // save log
    savelog (@$error);

    return $conn_id;
  }
  else return false;
}

// ----------------------------------------- ftp_userlogout ---------------------------------------------
// function: ftp_userlogout()
// input: FTP connection [resource]
// output: true / false on error

// description:
// This function disconnects from an FTP server.

function ftp_userlogout ($conn_id)
{
  global $mgmt_config;

  if ($conn_id != "")
  {
    // close the FTP Connection
    return ftp_close ($conn_id);
  }
  else return false;
}

// ----------------------------------------- ftp_getfile ---------------------------------------------
// function: ftp_getfile()
// input: FTP connection [resource], path to file on FTP server [string], passive mode [true,false] (optional)
// output: true / false on error

// description:
// This function gets a file from the FTP server.

function ftp_getfile ($conn_id, $remote_file, $local_file, $passive=true)
{
  global $mgmt_config;

  if ($conn_id != "" && $local_file != "" && $remote_file != "" && ($passive == true || $passive == false))
  {
    $download = false;

    // set mode
    ftp_pasv ($conn_id, $passive);

    // download file
    $download = ftp_get ($conn_id, $local_file, $remote_file, FTP_BINARY);

    // verify download
    if (!$download) $error[] = date('Y-m-d H:i')."|hypercms_connect.inc.php|error|20201|FTP: download of ".$remote_file." to ".$local_file." has failed";

    // save log
    savelog (@$error);

    return $download;
  }
  else return false;
}

// ----------------------------------------- ftp_putfile ---------------------------------------------
// function: ftp_putfile()
// input: FTP connection [resource], path to local file [string], path to file on FTP server [string], passive mode [true,false] (optional)
// output: true / false on error

// description:
// This function puts a file to the FTP server.

function ftp_putfile ($conn_id, $local_file, $remote_file, $passive=true)
{
  global $mgmt_config;

  if ($conn_id != "" && $local_file != "" && $remote_file != "" && ($passive == true || $passive == false))
  {
    $upload = false;

    // set mode
    ftp_pasv ($conn_id, $passive);

    // upload file
    if (is_file ($local_file))
    {
      $upload = ftp_put ($conn_id, $remote_file, $local_file, FTP_BINARY);

      // verify upload
      if (!$upload) $error[] = date('Y-m-d H:i')."|hypercms_connect.inc.php|error|20103|FTP: upload of ".$local_file." to ".$remote_file." has failed";
    }
    else $error[] = date('Y-m-d H:i')."|hypercms_connect.inc.php|error|20105|FTP: local file ".$local_file." does not exist";

    // save log
    savelog (@$error);

    return $upload;
  }
  else return false;
}

// ----------------------------------------- ftp_deletefile ---------------------------------------------
// function: ftp_deletefile()
// input: FTP connection [resource], path to file on FTP server [string], passive mode [true,false] (optional)
// output: true / false on error

// description:
// This function deletes a file from the FTP server.

function ftp_deletefile ($conn_id, $remote_file, $passive=true)
{
  global $mgmt_config;

  if ($conn_id != "" && $remote_file != "" && ($passive == true || $passive == false))
  {
    $delete = false;

    // set mode
    ftp_pasv ($conn_id, $passive);

    // delete file
    $delete = ftp_delete ($conn_id, $remote_file);

    // verify upload
    if (!$delete) $error[] = date('Y-m-d H:i')."|hypercms_connect.inc.php|error|20103|FTP: delete of ".$remote_file." has failed";

    // save log
    savelog (@$error);

    return $delete;
  }
  else return false;
}

// ----------------------------------------- ftp_filelist ---------------------------------------------
// function: ftp_filelist()
// input: FTP connection [resource], path to remote directory [string] (optional), passive mode [true,false] (optional)
// output: result array / false on error

// description:
// This function gets a file/directory listing of the FTP server.

function ftp_filelist ($conn_id, $path=".", $passive=true)
{
  global $mgmt_config;

  if ($conn_id != "")
  {
    ftp_pasv ($conn_id, true);

    if (is_array ($children = @ftp_rawlist ($conn_id, $path)))
    {
      $folders = array();
      $files = array();
      $items = array();

      foreach ($children as $child)
      {
        $chunks = preg_split ("/\s+/", $child);

        list ($item['rights'], $item['number'], $item['user'], $item['group'], $item['size'], $item['month'], $item['day'], $item['time']) = $chunks;

        // file or directory
        $item['type'] = $chunks[0]{0} === 'd' ? 'directory' : 'file';

        array_splice ($chunks, 0, 8);

        $name = implode (" ", $chunks);

        if ($item['type'] == "directory") $folders[$name] = $item;
        else $files[$name] = $item;
      }

      ksort ($folders, SORT_NATURAL);
      ksort ($files, SORT_NATURAL);

      $items = array_merge ($folders, $files);

      return $items;
    }
    else return false;
  }
  else return false;
}

// ----------------------------------------- createsharelink_facebook ---------------------------------------------
// function: createsharelink_facebook()
// input: URL to share [string]
// output: Share URL / false on error

function createsharelink_facebook ($site, $url)
{
  global $mgmt_config;

  if (is_dir ($mgmt_config['abs_path_cms']."connector/") && !empty ($mgmt_config[$site]['sharesociallink']) && $url != "")
  {
    return "https://www.facebook.com/sharer/sharer.php?u=".url_encode($url);
  }
  else return false;
}

// ----------------------------------------- createsharelink_twitter ---------------------------------------------
// function: createsharelink_twitter()
// input: URL to share [string], message to share [string]
// output: Share URL / false on error

function createsharelink_twitter ($site, $url, $text)
{
  global $mgmt_config;

  if (is_dir ($mgmt_config['abs_path_cms']."connector/") && !empty ($mgmt_config[$site]['sharesociallink']) && $url != "" && $text != "")
  {
    return "https://twitter.com/intent/tweet?text=".url_encode($text)."&source=hypercms&related=hypercms&url=".url_encode($url);
  }
  else return false;
}

// ----------------------------------------- createsharelink_googleplus ---------------------------------------------
// function: createsharelink_googleplus()
// input: URL to share [string]
// output: Share URL / false on error

function createsharelink_googleplus ($site, $url)
{
  global $mgmt_config;

  if (is_dir ($mgmt_config['abs_path_cms']."connector/") && !empty ($mgmt_config[$site]['sharesociallink']) && $url != "")
  {
    return "https://plus.google.com/share?url=".url_encode($url);
  }
  else return false;
}

// ----------------------------------------- createsharelink_linkedin ---------------------------------------------
// function: createsharelink_linkedin()
// input: URL to share [string], title [string], summary [string] (optional), source [string] (optional)
// output: Share URL / false on error

function createsharelink_linkedin ($site, $url, $title, $summary, $source)
{
  global $mgmt_config;

  if (is_dir ($mgmt_config['abs_path_cms']."connector/") && !empty ($mgmt_config[$site]['sharesociallink']) && $url != "" && $title != "")
  {
    return "https://www.linkedin.com/shareArticle?mini=true&url=".url_encode($url)."&title=".url_encode($title)."&summary=".url_encode($summary)."&source=".url_encode($source);
  }
  else return false;
}

// ----------------------------------------- createsharelink_pinterest ---------------------------------------------
// function: createsharelink_pinterest()
// input: image URL to share [string], title [string], description [string] (optional)
// output: Share URL / false on error

function createsharelink_pinterest ($site, $image_url, $title, $description)
{
  global $mgmt_config;

  if (is_dir ($mgmt_config['abs_path_cms']."connector/") && !empty ($mgmt_config[$site]['sharesociallink']) && $image_url != "" && $title != "")
  {
    return "https://pinterest.com/pin/create/button/?url=".url_encode($image_url)."&media=".url_encode($title)."&description=".url_encode($description);
  }
  else return false;
}
?>