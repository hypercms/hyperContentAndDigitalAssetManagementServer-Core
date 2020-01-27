<?php
/*
 * This file is part of
 * hyper Content Management Server - http://www.hypercms.com
 * Copyright (c) by hyper CMS Content Management Solutions GmbH
 *
 * You should have received a copy of the License along with hyperCMS.
 */

// ================= include code before boy end tag (footer) ===================

if (!empty ($mgmt_config['googleanalytics_key'])) echo getgoogleanalytics ($mgmt_config['googleanalytics_key']);
?>