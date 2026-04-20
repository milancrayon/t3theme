CREATE TABLE `cache_cratelimit` (
  `identifier` varchar(250) NOT NULL DEFAULT '',
  `expires` int(11) NOT NULL DEFAULT '0',
  `content` mediumblob,
  PRIMARY KEY (`identifier`),
  KEY `expires` (`expires`)
);