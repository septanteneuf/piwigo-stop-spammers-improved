<?php
/*
Plugin Name: Stop Spammers
Version: 14.a
Description: Fight against spammers
Plugin URI: http://piwigo.org/ext/extension_view.php?eid=721
Author: plg
Author URI: http://le-gall.net/pierrick
*/

if (!defined('PHPWG_ROOT_PATH'))
{
  die('Hacking attempt!');
}

global $prefixeTable;

// +-----------------------------------------------------------------------+
// | Define plugin constants                                               |
// +-----------------------------------------------------------------------+

defined('STOP_SPAMMERS_ID') or define('STOP_SPAMMERS_ID', basename(dirname(__FILE__)));
define('STOP_SPAMMERS_PATH' , PHPWG_PLUGINS_PATH.basename(dirname(__FILE__)).'/');
define('STOP_SPAMMERS_TABLE', $prefixeTable.'stop_spammers');
define('STOP_SPAMMERS_VERSION', '14.a');

// init the plugin
add_event_handler('init', 'stop_spammers_init');
/**
 * plugin initialization
 *   - check for upgrades
 *   - unserialize configuration
 *   - load language
 */
function stop_spammers_init()
{
  global $conf, $user, $pwg_loaded_plugins;

  // apply upgrade if needed
  if (
    STOP_SPAMMERS_VERSION == 'auto' or
    $pwg_loaded_plugins[STOP_SPAMMERS_ID]['version'] == 'auto' or
    version_compare($pwg_loaded_plugins[STOP_SPAMMERS_ID]['version'], STOP_SPAMMERS_VERSION, '<')
  )
  {
    // call install function
    include_once(STOP_SPAMMERS_PATH.'include/install.inc.php');
    stop_spammers_install();

    // update plugin version in database
    if ( $pwg_loaded_plugins[STOP_SPAMMERS_ID]['version'] != 'auto' and STOP_SPAMMERS_VERSION != 'auto' )
    {
      $query = '
UPDATE '. PLUGINS_TABLE .'
SET version = "'. STOP_SPAMMERS_VERSION .'"
WHERE id = "'. STOP_SPAMMERS_ID .'"';
      pwg_query($query);

      $pwg_loaded_plugins[STOP_SPAMMERS_ID]['version'] = STOP_SPAMMERS_VERSION;
    }
  }
}

add_event_handler('user_comment_check', 'stop_spammers_checks', EVENT_HANDLER_PRIORITY_NEUTRAL, 2);
add_event_handler('contact_form_check', 'stop_spammers_checks', EVENT_HANDLER_PRIORITY_NEUTRAL, 2);
function stop_spammers_checks($action, $comment)
{
  global $page, $conf;

  if (!empty($_POST['website_url']))
  {
    $page['errors'][] = l10n('Spam detected');
    return 'reject';
  }

  if (!isset($conf['stop_spammers_max_links']))
  {
    $conf['stop_spammers_max_links'] = 2;
  }

  if (!isset($conf['stop_spammers_keywords']))
  {
    $conf['stop_spammers_keywords'] = array(
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
    );
  }

  $content = stop_spammers_get_comment_content($comment);
  $link_count = preg_match_all(
    '#(?:https?://|www\.|(?:^|[\s<>\(\)\[\]"\'=])(?:[a-z0-9-]+\.)+[a-z]{2,63}(?:/[^\s<>\)]*)?)#i',
    $content
  );

  if ($link_count >= (int)$conf['stop_spammers_max_links'])
  {
    $page['errors'][] = l10n('Too many links');
    return 'reject';
  }

  $content_lower = function_exists('mb_strtolower') ? mb_strtolower($content, 'UTF-8') : strtolower($content);

  foreach ($conf['stop_spammers_keywords'] as $keyword)
  {
    $keyword_lower = function_exists('mb_strtolower') ? mb_strtolower($keyword, 'UTF-8') : strtolower($keyword);

    if ($keyword_lower !== '' && strpos($content_lower, $keyword_lower) !== false)
    {
      $page['errors'][] = l10n('Spam detected');
      return 'reject';
    }
  }

  if (!stop_spammers_check_stopforumspam())
  {
    $page['errors'][] = l10n('IP address rejected');
    return 'reject';
  }

  return $action;
}

function stop_spammers_get_comment_content($comment)
{
  if (is_string($comment))
  {
    return $comment;
  }

  $fields = array('content', 'comment', 'message', 'body', 'text');
  if (is_array($comment))
  {
    foreach ($fields as $field)
    {
      if (isset($comment[$field]) && is_string($comment[$field]))
      {
        return $comment[$field];
      }
    }
  }

  foreach ($fields as $field)
  {
    if (isset($_POST[$field]) && is_string($_POST[$field]))
    {
      return $_POST[$field];
    }
  }

  return '';
}

function stop_spammers_check_stopforumspam()
{
  global $conf;

  if (!isset($conf['stop_spammers_sfs_threshold']))
  {
    $conf['stop_spammers_sfs_threshold'] = 20;
  }

  if (!isset($conf['stop_spammers_cache_days']))
  {
    $conf['stop_spammers_cache_days'] = 30;
  }

  if (!isset($conf['stop_spammers_whitelist']))
  {
    $conf['stop_spammers_whitelist'] = array();
  }

  $ip = isset($_SERVER['REMOTE_ADDR']) ? trim($_SERVER['REMOTE_ADDR']) : '';

  if (!filter_var($ip, FILTER_VALIDATE_IP))
  {
    return true;
  }

  if (in_array($ip, $conf['stop_spammers_whitelist']))
  {
    return true;
  }

  list($dbnow) = pwg_db_fetch_row(pwg_query('SELECT NOW();'));

  $query = '
SELECT *
  FROM '.STOP_SPAMMERS_TABLE.'
  WHERE ip = \''.pwg_db_real_escape_string($ip).'\'
;';
  $blocked = pwg_db_fetch_assoc(pwg_query($query));

  if (!empty($blocked))
  {
    $blocked_since = strtotime($blocked['since']);
    $cache_limit = strtotime('-'.$conf['stop_spammers_cache_days'].' days');

    if ($blocked_since !== false && $blocked_since >= $cache_limit)
    {
      single_update(
        STOP_SPAMMERS_TABLE,
        array(
          'last_update' => $dbnow,
          'occurrences' => $blocked['occurrences'] + 1
        ),
        array('id' => $blocked['id'])
      );

      return false;
    }
    else
    {
      pwg_query('
DELETE FROM '.STOP_SPAMMERS_TABLE.'
WHERE id = '.(int)$blocked['id'].'
;');
    }
  }

  include_once(PHPWG_ROOT_PATH.'admin/include/functions.php');

  $sfs_url = 'https://www.stopforumspam.com/api?ip='.urlencode($ip).'&f=serial&confidence';

  $result = null;
  $fetch_ok = fetchRemote($sfs_url, $result);

  if (!$fetch_ok || empty($result))
  {
    return true;
  }

  $result = @unserialize($result);

  if (!is_array($result))
  {
    return true;
  }

  if (isset($result['ip']['confidence']))
  {
    if ((float)$result['ip']['confidence'] > (float)$conf['stop_spammers_sfs_threshold'])
    {
      single_insert(
        STOP_SPAMMERS_TABLE,
        array(
          'ip' => $ip,
          'blocker' => 'stopforumspam',
          'since' => $dbnow,
          'last_update' => $dbnow,
          'occurrences' => 1,
        )
      );

      return false;
    }
  }

  return true;
}
