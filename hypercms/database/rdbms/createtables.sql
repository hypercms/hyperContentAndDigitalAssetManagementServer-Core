DROP TABLE IF EXISTS `accesslink`;

CREATE TABLE `accesslink` (
  `hash` char(16) BINARY CHARACTER SET utf8 COLLATE utf8_bin NOT NULL DEFAULT '',
  `date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `object_id` int(11) NOT NULL,
  `type` char(2) DEFAULT NULL,
  `user` varchar(600) BINARY DEFAULT NULL,
  `deathtime` int(11) DEFAULT NULL,
  `formats` varchar(510) DEFAULT NULL,
  PRIMARY KEY (`hash`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

DROP TABLE IF EXISTS `object`;

CREATE TABLE `object` (
  `object_id` int(11) NOT NULL auto_increment,
  `hash` char(16) BINARY NOT NULL DEFAULT '',
  `id` int(11) NOT NULL DEFAULT '0',
  `level` smallint(6) DEFAULT NULL,
  `createdate` datetime NOT NULL default CURRENT_TIMESTAMP,
  `date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `publishdate` datetime DEFAULT NULL,
  `user` char(100) NOT NULL DEFAULT '',
  `objectpath` varchar(4096) NOT NULL DEFAULT '',
  `objectpathname` varchar(4096) NOT NULL DEFAULT '',
  `container` char(16) NOT NULL DEFAULT '',
  `template` char(100) BINARY NOT NULL DEFAULT '',
  `media` char(255) BINARY DEFAULT NULL,
  `latitude` float(10,6) DEFAULT NULL,
  `longitude` float(10,6) DEFAULT NULL,
  `filesize` int(11) DEFAULT NULL,
  `filetype` char(16) DEFAULT NULL,
  `width` smallint(6) DEFAULT NULL,
  `height` smallint(6) DEFAULT NULL,
  `red` smallint(3) DEFAULT NULL,
  `green` smallint(3) DEFAULT NULL,
  `blue` smallint(3) DEFAULT NULL,
  `colorkey` char(8) DEFAULT NULL,
  `imagetype` char(16) DEFAULT NULL,
  `md5_hash` char(32) BINARY DEFAULT NULL,
  `analyzed` tinyint(1) NOT NULL DEFAULT '0',
  `deleteuser` char(100) BINARY DEFAULT '',
  `deletedate` date DEFAULT NULL,
  `workflowdate` datetime DEFAULT NULL,
  `workflowstatus` char(5) DEFAULT NULL,
  `workflowuser` char(100) BINARY DEFAULT '',
  `textcontent` mediumtext DEFAULT NULL,
  PRIMARY KEY  (`object_id`),
  UNIQUE KEY `object_objecthash` (`hash`),
  KEY `object_id` (`id`),
  KEY `object_date` (`date`),
  KEY `object_media` (`filesize`,`filetype`,`width`,`height`,`imagetype`),
  KEY `object_lat_lng` (`latitude`,`longitude`),
  KEY `object_objectpath` (`objectpath`),
  KEY `object_deleteuser` (`deleteuser`),
  FULLTEXT KEY `object_objectpathname` (`objectpathname`),
  FULLTEXT KEY `object_textcontent` (`textcontent`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `recipient`;

CREATE TABLE `recipient` (
  `recipient_id` int(11) NOT NULL auto_increment,
  `object_id` int(11) NOT NULL DEFAULT '0',
  `date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `from_user` char(100) BINARY NOT NULL DEFAULT '',
  `to_user` varchar(500) BINARY NOT NULL DEFAULT '',
  `email` char(80) NOT NULL DEFAULT '',
  PRIMARY KEY  (`recipient_id`),
  KEY `recipient_object_id` (`object_id`),
  KEY `recipient_multiple` (`object_id`,`date`,`from_user`,to_user(200))
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

DROP TABLE IF EXISTS `project`;

CREATE TABLE `project` (
  `project_id` int(11) NOT NULL auto_increment,
  `subproject_id` int(11) NOT NULL DEFAULT '0',
  `object_id` int(11) NOT NULL DEFAULT '0',
  `createdate` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP, 
  `project` char(200) NOT NULL DEFAULT 'undefined',
  `description` varchar(3600),
  `user` char(100) BINARY NOT NULL default '',
  PRIMARY KEY  (`project_id`),
  KEY `project_user` (`user`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `task`;

CREATE TABLE `task` (
  `task_id` int(11) NOT NULL auto_increment,
  `project_id` int(11) NOT NULL DEFAULT '0',
  `object_id` int(11) NOT NULL DEFAULT '0',
  `task` char(200) NOT NULL DEFAULT 'undefined',
  `from_user` char(100) BINARY NOT NULL DEFAULT '',
  `to_user` char(100) BINARY NOT NULL DEFAULT '',
  `startdate` date DEFAULT NULL,
  `finishdate` date DEFAULT NULL,
  `category` char(20) NOT NULL DEFAULT 'user',
  `description` varchar(3600),
  `priority` char(10) NOT NULL DEFAULT 'low',
  `status` tinyint(3) NOT NULL,
  `planned` float(6,2) DEFAULT NULL,
  `actual` float(6,2) DEFAULT NULL,
  PRIMARY KEY  (`task_id`),
  KEY `task_to_user` (`to_user`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `taxonomy`;

CREATE TABLE `taxonomy` (
  `taxonomykey_id` int(11) NOT NULL auto_increment,
  `id` int(11) NOT NULL,
  `text_id` char(255) BINARY NOT NULL DEFAULT '',
  `taxonomy_id` int(11) NOT NULL DEFAULT '0',
  `lang` char(6) NOT NULL DEFAULT '',
  PRIMARY KEY  (`taxonomykey_id`),
  KEY `taxonomy_multiple` (`id`,`text_id`,`taxonomy_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

DROP TABLE IF EXISTS `keywords`;

CREATE TABLE `keywords` (
  `keyword_id` int(11) NOT NULL auto_increment,
  `keyword` char (100) NOT NULL DEFAULT '',
  PRIMARY KEY (`keyword_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `keywords_container`;

CREATE TABLE `keywords_container` (
  `id` int(11) NOT NULL DEFAULT '0',
  `keyword_id` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`,`keyword_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

DROP TABLE IF EXISTS `textnodes`;

CREATE TABLE `textnodes` (
  `textnodes_id` int(11) NOT NULL auto_increment,
  `id` int(11) NOT NULL DEFAULT '0',
  `text_id` char(255) BINARY NOT NULL DEFAULT '',
  `textcontent` mediumtext DEFAULT NULL,
  `object_id` int(11) DEFAULT NULL,
  `type` char(6) NOT NULL DEFAULT '',
  `user` char(100) BINARY DEFAULT NULL,
  PRIMARY KEY  (`textnodes_id`),
  KEY `textnodes_id` (`id`),
  KEY `textnodes_text_id` (`text_id`),
  KEY `textnodes_object_id` (`object_id`),
  KEY `textnodes_multiple` (`id`,`type`),
  FULLTEXT KEY `textnodes_textcontent` (`textcontent`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `queue`;

CREATE TABLE `queue` (
  `queue_id` int(11) NOT NULL auto_increment,
  `object_id` int(11) NOT NULL,
  `action` char(20) NOT NULL,
  `date` datetime DEFAULT NULL,
  `published_only` tinyint(4) DEFAULT NULL,
  `cmd` varchar(21000) DEFAULT NULL,
  `user` char(100) BINARY DEFAULT NULL,
  PRIMARY KEY  (`queue_id`),
  KEY `queue_multiple` (`date`,`user`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

DROP TABLE IF EXISTS `dailystat`;

CREATE TABLE `dailystat` (
  `stats_id` int(11) NOT NULL AUTO_INCREMENT,
  `id` int(11) NOT NULL,
  `date` date NOT NULL,
  `activity` char(10) DEFAULT NULL,
  `user` char(100) BINARY DEFAULT NULL,
  `count` int(11) NOT NULL,
  PRIMARY KEY (`stats_id`),
  KEY `dailystat_multiple` (`id`,`date`,`activity`,`user`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

DROP TABLE IF EXISTS `notify`;

CREATE TABLE `notify` (
  `notify_id` int(11) NOT NULL AUTO_INCREMENT,
  `object_id` int(11) NOT NULL DEFAULT '0',
  `user` char(100) BINARY NOT NULL DEFAULT '',
  `oncreate` tinyint(1) NOT NULL DEFAULT '0',
  `onedit` tinyint(1) NOT NULL DEFAULT '0',
  `onmove` tinyint(1) NOT NULL DEFAULT '0',
  `ondelete` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY  (`notify_id`),
  KEY `notify_multiple` (`object_id`,`user`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_bin;