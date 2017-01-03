DROP TABLE IF EXISTS `container`;

CREATE TABLE `container` (
  `id` int(11) NOT NULL default '0',
  `container` char(20) NOT NULL default '',
  `createdate` datetime NOT NULL, 
  `date` datetime NOT NULL,
  `latitude` float(10,6) DEFAULT NULL,
  `longitude` float(10,6) DEFAULT NULL,
  `user` char(60) NOT NULL default '',
  PRIMARY KEY  (`id`),
  KEY `container` (`date`,`user`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `accesslink`;

CREATE TABLE `accesslink` (
  `hash` char(20) NOT NULL,
  `date` datetime NOT NULL,
  `object_id` int(11) NOT NULL,
  `type` char(2) DEFAULT NULL,
  `user` varchar(600) DEFAULT NULL,
  `deathtime` int(11) DEFAULT NULL,
  `formats` varchar(510) DEFAULT NULL,
  PRIMARY KEY (`hash`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `object`;

CREATE TABLE `object` (
  `object_id` int(11) NOT NULL auto_increment,
  `hash` char(20) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL default '',
  `id` int(11) NOT NULL default '0',
  `objectpath` varchar(21000) NOT NULL default '',
  `template` char(60) NOT NULL default '',
  PRIMARY KEY  (`object_id`),
  UNIQUE KEY `objecthash` (`hash`),
  KEY `object` (`id`,`template`),
  FULLTEXT KEY `objectpath` (`objectpath`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `recipient`;

CREATE TABLE `recipient` (
  `recipient_id` int(11) NOT NULL auto_increment,
  `object_id` int(11) NOT NULL default '0',
  `date` datetime NOT NULL,
  `from_user` char(60) NOT NULL default '',
  `to_user` varchar(600) NOT NULL default '',
  `email` char(80) NOT NULL default '',
  PRIMARY KEY  (`recipient_id`),
  KEY `recipient` (`object_id`),
  KEY `date` (object_id,`date`),
  KEY `from_user` (object_id,from_user),
  KEY `to_user` (object_id,to_user(200))
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `project`;

CREATE TABLE `project` (
  `project_id` int(11) NOT NULL auto_increment,
  `subproject_id` int(11) NOT NULL default '0',
  `object_id` int(11) NOT NULL default '0',
  `createdate` datetime NOT NULL, 
  `project` char(200) NOT NULL DEFAULT 'undefined',
  `description` varchar(3600),
  `user` char(60) NOT NULL default '',
  PRIMARY KEY  (`project_id`),
  KEY `project` (`user`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `task`;

CREATE TABLE `task` (
  `task_id` int(11) NOT NULL auto_increment,
  `project_id` int(11) NOT NULL default '0',
  `object_id` int(11) NOT NULL default '0',
  `task` char(200) NOT NULL DEFAULT 'undefined',
  `from_user` char(60) NOT NULL default '',
  `to_user` char(60) NOT NULL default '',
  `startdate` date NOT NULL,
  `finishdate` date DEFAULT NULL,
  `category` char(20) NOT NULL default 'user',
  `description` varchar(3600),
  `priority` char(10) NOT NULL default 'low',
  `status` tinyint(3) NOT NULL,
  `planned` float(6,2) DEFAULT NULL,
  `actual` float(6,2) DEFAULT NULL,
  PRIMARY KEY  (`task_id`),
  KEY `task` (`to_user`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `taxonomy`;

CREATE TABLE `taxonomy` (
  `id` int(11) NOT NULL,
  `text_id` char(120) NOT NULL default '',
  `taxonomy_id` int(11) NOT NULL default '0',
  `lang` char(6) NOT NULL default '',
  KEY `taxonomy` (`id`,`taxonomy_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `keywords`;

CREATE TABLE `keywords` (
  `keyword_id` int(11) NOT NULL auto_increment,
  `keyword` char (100) NOT NULL default '',
  PRIMARY KEY (`keyword_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `keywords_container`;

CREATE TABLE `keywords_container` (
  `id` int(11) NOT NULL default '0',
  `keyword_id` int(11) NOT NULL default '0',
  PRIMARY KEY (`id`,`keyword_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `textnodes`;

CREATE TABLE `textnodes` (
  `id` int(11) NOT NULL default '0',
  `text_id` char(120) NOT NULL default '',
  `textcontent` text,
  `object_id` int(11) DEFAULT NULL,
  `type` char(6) NOT NULL default '',
  `user` char(60) DEFAULT NULL,
  KEY `textnodes_id` (`id`),
  KEY `textnodes_text_id` (`text_id`),
  KEY `textnodes_object_id` (`object_id`),
  KEY `textnodes_id_type` (`id`,`type`),
  FULLTEXT KEY `textnodes_content` (`textcontent`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `queue`;

CREATE TABLE `queue` (
  `queue_id` int(11) NOT NULL auto_increment,
  `object_id` int(11) NOT NULL,
  `action` char(20) NOT NULL,
  `date` datetime default NULL,
  `published_only` tinyint(4) default NULL,
  `user` char(60) default NULL,
  PRIMARY KEY  (`queue_id`),
  KEY `queue` (`date`,`user`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `dailystat`;

CREATE TABLE `dailystat` (
  `stats_id` int(11) NOT NULL AUTO_INCREMENT,
  `id` int(11) NOT NULL,
  `date` date NOT NULL,
  `activity` char(20) DEFAULT NULL,
  `user` char(60) DEFAULT NULL,
  `count` int(11) NOT NULL,
  PRIMARY KEY (`stats_id`),
  KEY `dailystat` (`id`,`date`,`user`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `media`;

CREATE TABLE `media` (
  `id` int(11) NOT NULL,
  `filesize` int(11) NOT NULL,
  `filetype` char(20) NOT NULL,
  `width` smallint(6) DEFAULT NULL,
  `height` smallint(6) DEFAULT NULL,
  `red` smallint(6) NOT NULL,
  `green` smallint(6) NOT NULL,
  `blue` smallint(6) NOT NULL,
  `colorkey` char(8) NOT NULL,
  `imagetype` char(20) NOT NULL,
  `md5_hash` char(32) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `media` (`filesize`,`filetype`,`width`,`height`,`colorkey`,`imagetype`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `notify`;

CREATE TABLE `notify` (
  `notify_id` int(11) NOT NULL AUTO_INCREMENT,
  `object_id` int(11) NOT NULL default '0',
  `user` char(60) NOT NULL default '',
  `oncreate` tinyint(1) NOT NULL default '0',
  `onedit` tinyint(1) NOT NULL default '0',
  `onmove` tinyint(1) NOT NULL default '0',
  `ondelete` tinyint(1) NOT NULL default '0',
  PRIMARY KEY  (`notify_id`),
  KEY `notify` (`object_id`,`user`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;