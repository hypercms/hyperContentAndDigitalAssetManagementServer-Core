DROP TABLE IF EXISTS `accesslink`;

CREATE TABLE `accesslink` (
  `hash` char(16) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL default '',
  `date` datetime NOT NULL,
  `object_id` int(11) NOT NULL,
  `type` char(2) DEFAULT NULL,
  `user` varchar(600) DEFAULT NULL,
  `deathtime` int(11) DEFAULT NULL,
  `formats` varchar(510) DEFAULT NULL,
  PRIMARY KEY (`hash`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

DROP TABLE IF EXISTS `object`;

CREATE TABLE `object` (
  `object_id` int(11) NOT NULL auto_increment,
  `hash` char(16) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL default '',
  `id` int(11) NOT NULL default '0',
  `createdate` datetime NOT NULL,
  `date` datetime NOT NULL,
  `publishdate` datetime DEFAULT NULL,
  `user` char(100) NOT NULL default '',
  `objectpath` varchar(16000) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL default '',
  `container` char(16) NOT NULL default '',
  `template` char(100) NOT NULL default '',
  `media` char(255) DEFAULT NULL,
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
  `md5_hash` char(32) DEFAULT NULL,
  `analyzed` tinyint(1) NOT NULL default '0',
  `deleteuser` char(100) DEFAULT '',
  `deletedate` date DEFAULT NULL,
  `workflowdate` datetime NOT NULL,
  `workflowstatus` char(5) DEFAULT NULL,
  `workflowuser` char(100) DEFAULT '',
  `textcontent` mediumtext DEFAULT NULL,
  PRIMARY KEY  (`object_id`),
  UNIQUE KEY `object_objecthash` (`hash`),
  KEY `object_multiple` (`id`,`date`,`template`,`latitude`,`longitude`,`filesize`,`filetype`,`width`,`height`,`colorkey`,`imagetype`,`deleteuser`),
  FULLTEXT KEY `object_objectpath` (`objectpath`),
  FULLTEXT KEY `object_textcontent` (`textcontent`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

DROP TABLE IF EXISTS `recipient`;

CREATE TABLE `recipient` (
  `recipient_id` int(11) NOT NULL auto_increment,
  `object_id` int(11) NOT NULL default '0',
  `date` datetime NOT NULL,
  `from_user` char(100) NOT NULL default '',
  `to_user` varchar(500) NOT NULL default '',
  `email` char(80) NOT NULL default '',
  PRIMARY KEY  (`recipient_id`),
  KEY `recipient_object_id` (`object_id`),
  KEY `recipient_multiple` (`object_id`,`date`,`from_user`,to_user(200))
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

DROP TABLE IF EXISTS `project`;

CREATE TABLE `project` (
  `project_id` int(11) NOT NULL auto_increment,
  `subproject_id` int(11) NOT NULL default '0',
  `object_id` int(11) NOT NULL default '0',
  `createdate` datetime NOT NULL, 
  `project` char(200) NOT NULL DEFAULT 'undefined',
  `description` varchar(3600),
  `user` char(100) NOT NULL default '',
  PRIMARY KEY  (`project_id`),
  KEY `project_user` (`user`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `task`;

CREATE TABLE `task` (
  `task_id` int(11) NOT NULL auto_increment,
  `project_id` int(11) NOT NULL default '0',
  `object_id` int(11) NOT NULL default '0',
  `task` char(200) NOT NULL DEFAULT 'undefined',
  `from_user` char(100) NOT NULL default '',
  `to_user` char(100) NOT NULL default '',
  `startdate` date DEFAULT NULL,
  `finishdate` date DEFAULT NULL,
  `category` char(20) NOT NULL default 'user',
  `description` varchar(3600),
  `priority` char(10) NOT NULL default 'low',
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
  `text_id` char(255) NOT NULL default '',
  `taxonomy_id` int(11) NOT NULL default '0',
  `lang` char(6) NOT NULL default '',
  PRIMARY KEY  (`taxonomykey_id`),
  KEY `taxonomy_multiple` (`id`,`taxonomy_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

DROP TABLE IF EXISTS `keywords`;

CREATE TABLE `keywords` (
  `keyword_id` int(11) NOT NULL auto_increment,
  `keyword` char (100) NOT NULL default '',
  PRIMARY KEY (`keyword_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `keywords_container`;

CREATE TABLE `keywords_container` (
  `id` int(11) NOT NULL default '0',
  `keyword_id` int(11) NOT NULL default '0',
  PRIMARY KEY (`id`,`keyword_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

DROP TABLE IF EXISTS `textnodes`;

CREATE TABLE `textnodes` (
  `textnodes_id` int(11) NOT NULL auto_increment,
  `id` int(11) NOT NULL default '0',
  `text_id` char(255) NOT NULL default '',
  `textcontent` mediumtext DEFAULT NULL,
  `object_id` int(11) DEFAULT NULL,
  `type` char(6) NOT NULL default '',
  `user` char(100) DEFAULT NULL,
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
  `date` datetime default NULL,
  `published_only` tinyint(4) default NULL,
  `cmd` varchar(21000) DEFAULT NULL,
  `user` char(100) default NULL,
  PRIMARY KEY  (`queue_id`),
  KEY `queue_multiple` (`date`,`user`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

DROP TABLE IF EXISTS `dailystat`;

CREATE TABLE `dailystat` (
  `stats_id` int(11) NOT NULL AUTO_INCREMENT,
  `id` int(11) NOT NULL,
  `date` date NOT NULL,
  `activity` char(10) DEFAULT NULL,
  `user` char(100) DEFAULT NULL,
  `count` int(11) NOT NULL,
  PRIMARY KEY (`stats_id`),
  KEY `dailystat_multiple` (`id`,`date`,`activity`,`user`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

DROP TABLE IF EXISTS `notify`;

CREATE TABLE `notify` (
  `notify_id` int(11) NOT NULL AUTO_INCREMENT,
  `object_id` int(11) NOT NULL default '0',
  `user` char(100) NOT NULL default '',
  `oncreate` tinyint(1) NOT NULL default '0',
  `onedit` tinyint(1) NOT NULL default '0',
  `onmove` tinyint(1) NOT NULL default '0',
  `ondelete` tinyint(1) NOT NULL default '0',
  PRIMARY KEY  (`notify_id`),
  KEY `notify_multiple` (`object_id`,`user`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_bin;