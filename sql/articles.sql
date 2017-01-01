CREATE TABLE IF NOT EXISTS `@dansang_articles` (
  `no` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `content` text NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`no`)
) CHARSET=utf8 AUTO_INCREMENT=1 ;
