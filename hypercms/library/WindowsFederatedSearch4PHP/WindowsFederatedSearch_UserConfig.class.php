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
 * WindowsFederatedSearch_UserConfig.class.php
 * 
 * Proxy settings for cURL connection 
 * within WindowsFederatedSearch_DataSource.
 *
 * PHP Version 5
 * 
 */

/**
 * User Configuration Constants
 * 
 * If Internet access beyond local firewall needs proxy, then 
 * PHP cURL used by class WindowsFederatedSearch_DataSource 
 * may require settings as defined by SERVER_ENDPOINT_PROXY and
 * SERVER_ENDPOINT_PROXY_PORT.
 */
class UserConfigConstants
{
    const OPEN_SEARCH_SHORT_NAME            = 'Windows Federated Search 4 PHP';
    const OPEN_SEARCH_DESCRIPTION           = 'Windows Federated Search for PHP Developers Demo';
    
    const FILE_HTML_STYLESHEET              = 'style/opensearch_to_html.css';
    const FILE_RSS_STYLESHEET               = null;
    
    const SERVER_ENDPOINT_PROXY             = null;   /* CURLOPT_PROXY */
    const SERVER_ENDPOINT_PROXY_PORT        = null;   /* CURLOPT_PROXYPORT */
}
?>