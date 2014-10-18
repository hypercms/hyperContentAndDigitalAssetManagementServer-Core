DROP TABLE IF EXISTS `container`;

CREATE TABLE `container` (
  `id` int(11) NOT NULL default '0',
  `container` varchar(20) NOT NULL default '',
  `date` date NOT NULL default '0000-00-00',
  `latitude` float(10,6) DEFAULT NULL,
  `longitude` float(10,6) DEFAULT NULL,
  `user` varchar(60) NOT NULL default '',
  PRIMARY KEY  (`id`),
  KEY `container` (`date`,`user`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `accesslink`;

CREATE TABLE `accesslink` (
  `hash` varchar(20) NOT NULL,
  `date` datetime NOT NULL,
  `object_id` int(11) NOT NULL,
  `type` varchar(2) DEFAULT NULL,
  `user` varchar(255) DEFAULT NULL,
  `deathtime` int(11) DEFAULT NULL,
  `formats` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`hash`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `object`;

CREATE TABLE `object` (
  `object_id` int(11) NOT NULL auto_increment,
  `hash` varchar(20) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL default '',
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
  `action` varchar(20) NOT NULL,
  `date` datetime default NULL,
  `published_only` tinyint(4) default NULL,
  `user` varchar(80) default NULL,
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
  `activity` varchar(255) DEFAULT NULL,
  `user` varchar(255) DEFAULT NULL,
  `count` int(11) NOT NULL,
  PRIMARY KEY (`stats_id`),
  KEY `dailystat` (`id`,`date`,`user`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `media`;

CREATE TABLE `media` (
  `id` int(11) NOT NULL,
  `filesize` int(11) NOT NULL,
  `filetype` varchar(20) NOT NULL,
  `width` int(11) DEFAULT NULL,
  `height` int(11) DEFAULT NULL,
  `red` smallint(6) NOT NULL,
  `green` smallint(6) NOT NULL,
  `blue` smallint(6) NOT NULL,
  `colorkey` varchar(8) NOT NULL,
  `imagetype` varchar(20) NOT NULL,
  `md5_hash` varchar(32) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `media` (`filesize`,`filetype`,`width`,`height`,`colorkey`,`imagetype`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `notify`;

CREATE TABLE `notify` (
  `notify_id` int(11) NOT NULL AUTO_INCREMENT,
  `object_id` int(11) NOT NULL default '0',
  `user` varchar(60) NOT NULL default '',
  `oncreate` smallint(1) NOT NULL default '0',
  `onedit` smallint(1) NOT NULL default '0',
  `onmove` smallint(1) NOT NULL default '0',
  `ondelete` smallint(1) NOT NULL default '0',
  PRIMARY KEY  (`notify_id`),
  KEY `notify` (`object_id`,`user`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;