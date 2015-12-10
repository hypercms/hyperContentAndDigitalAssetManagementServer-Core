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
  `user` char(255) DEFAULT NULL,
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
  `template` varchar(60) NOT NULL default '',
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
  `sender` varchar(60) NOT NULL default '',
  `user` varchar(600) NOT NULL default '',
  `email` varchar(80) NOT NULL default '',
  PRIMARY KEY  (`recipient_id`),
  KEY `recipient` (`object_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `textnodes`;

DROP TABLE IF EXISTS `task`;

CREATE TABLE `task` (
  `task_id` int(11) NOT NULL auto_increment,
  `project_id` int(11) DEFAULT NULL,
  `object_id` int(11) DEFAULT NULL,
  `task` varchar(200) NOT NULL DEFAULT 'undefined',
  `from_user` varchar(200) NOT NULL default '',
  `to_user` varchar(200) NOT NULL default '',
  `startdate` date NOT NULL,
  `finishdate` date DEFAULT NULL,
  `category` varchar(20) NOT NULL default 'user',
  `description` varchar(3600),
  `priority` varchar(10) NOT NULL default 'low',
  `status` tinyint(3) NOT NULL,
  `duration` time DEFAULT NULL,
  PRIMARY KEY  (`task_id`),
  KEY `task` (`to_user`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

CREATE TABLE `textnodes` (
  `id` int(11) NOT NULL default '0',
  `text_id` varchar(120) NOT NULL default '',
  `textcontent` text,
  KEY `textnodes_id` (`id`),
  KEY `textnodes_text_id` (`text_id`),
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

DROP TABLE IF EXISTS `linkreference`;

CREATE TABLE `linkreference` (
  `from_object_id` int(11) default NULL,
  `to_object_id` int(11) default NULL
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