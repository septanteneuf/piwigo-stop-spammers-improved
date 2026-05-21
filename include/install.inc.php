<?php
// +-----------------------------------------------------------------------+
// | Piwigo - a PHP based photo gallery                                    |
// +-----------------------------------------------------------------------+

function stop_spammers_install()
{
  global $prefixeTable;

  $query = '
CREATE TABLE IF NOT EXISTS '.$prefixeTable.'stop_spammers (
  id int(11) NOT NULL AUTO_INCREMENT,
  ip varchar(45) default NULL,
  blocker varchar(255) default NULL,
  since datetime not null,
  last_update datetime not null,
  occurrences int(11) not null,
  PRIMARY KEY (id),
  KEY ip (ip)
) ENGINE=MyISAM DEFAULT CHARSET=utf8
;';
  pwg_query($query);

  $query = '
ALTER TABLE '.$prefixeTable.'stop_spammers
  MODIFY ip varchar(45) default NULL
;';
  pwg_query($query);

  conf_update_param('stop_spammers_sfs_threshold', 20);
  conf_update_param('stop_spammers_cache_days', 30);
  conf_update_param('stop_spammers_max_links', 2);

  conf_update_param(
    'stop_spammers_keywords',
    serialize(array(
      'ai ads',
      'ai content',
      'generate ai',
      'publish easily',
      'free tools',
      'free plan',
      'traffic',
      'revenue',
      'backlinks',
      'seo',
      'marketing',
      'customers no longer',
      'static websites',
      'no obligations',
      'unsubscribe',
      'opt-out',
      'bit.ly',
      'systeme.io',
    ))
  );

  conf_update_param(
    'stop_spammers_whitelist',
    serialize(array())
  );
}