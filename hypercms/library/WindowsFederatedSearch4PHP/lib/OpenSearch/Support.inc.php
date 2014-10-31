<?php
/**
 * Copyright (C) 2012 Jeff Tanner <jeff00seattle@gmail.com>
 * 
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 * lib/OpenSearch/Support.inc.php
 * 
 * Defines constants and error handler used by feed generation code.
 * 
 * PHP Version 5
 * 
 */

require_once 'lib/OpenSearch/ServiceConstants.class.php';


/**
 * Print string with line break.
 * 
 * @param string $strMessage Message
 * 
 * @return void
 */
function println($strMessage = "")
{
    isset($_SERVER['SERVER_PROTOCOL'])
      ? print "$strMessage<br />" . PHP_EOL 
      : print "$strMessage" . PHP_EOL;
}

/**
 * PHP custom error handler
 * 
 * @param string $error_level Specifies the error report level.
 * @param string $error_msg   Specifies the error message 
 * @param string $error_file  Specifies the filename
 * @param string $error_line  Specifies the line number
 * 
 * @return boolean Returns TRUE on success or FALSE on failure.
 */
function OpenSearch_errorHandler(
    $error_level,
    $error_msg,
    $error_file,
    $error_line
) {
    println("Error: {$error_file}::{$error_line}\n");
    
    switch ($error_level) {
    case E_USER_ERROR:
        println("APP ERROR: Fatal: \"{$error_msg}\"");
        println("PHP " . PHP_VERSION . " (" . PHP_OS . ")");
        println("Aborting...");
        exit(1);
        break;

    case E_USER_WARNING:
        println("APP WARNING: \"{$error_msg}\"");
        break;

    case E_USER_NOTICE:
        println("APP NOTICE: \"{$error_msg}\"");
        break;
        
    case E_NOTICE:
        println("RUN-TIME NOTICE: \"{$error_msg}\"");
        break;

    case E_WARNING:
        println("RUN-TIME WARNING: \"{$error_msg}\"");
        exit(1);
        break;

    default:
        println("UNKNOWN ERROR TYPE {$error_level}: \"{$error_msg}\"");
        break;
    }

    /* Don't execute PHP internal error handler */
    return true;
}
?>