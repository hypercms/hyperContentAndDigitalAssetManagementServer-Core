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
 * index.php
 * 
 * Home page of OpenSearch Web Service endpoint. When an client
 * access this endpoint by their Web browser, this will provide this search service
 * within its "Search" box.
 * 
 * PHP Version 5
 */

require_once 'WindowsFederatedSearch_UserConfig.class.php';

?>

<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en" dir="ltr">
  <head profile="http://a9.com/-/spec/opensearch/1.1/">
    <link rel="search" type="application/opensearchdescription+xml" 
      href="description.php" title="<?php print UserConfigConstants::OPEN_SEARCH_SHORT_NAME ?>" />
    <title><?php print UserConfigConstants::OPEN_SEARCH_DESCRIPTION ?></title>
  </head>
  <body>
      <p>New search connection labeled <b>&quot;<?php print UserConfigConstants::OPEN_SEARCH_DESCRIPTION ?>&quot;</b> within this browser.</p>
  </body>
</html>