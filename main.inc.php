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
  global $page;
  
  if (!stop_spammers_check_stopforumspam())
  {
    $page['errors'][] = l10n('IP address rejected');
    return 'reject';
  }

  return $action;
}

// Amelioratioj de la fonction
function stop_spammers_check_stopforumspam()
{
  global $conf;

  // utiliser un seui plus strict si l'on recoit encore du spam, descente a 20
  if (!isset($conf['stop_spammers_sfs_threshold']))
  {
    $conf['stop_spammers_sfs_threshold'] = 20;
  }

  // Eviter de bloquer localement un IP pour toujours
  if (!isset($conf['stop_spammers_cache_days']))
  {
    $conf['stop_spammers_cache_days'] = 30;
  }

  // Ajout d'une whitelist
  if (!isset($conf['stop_spammers_whitelist']))
  {
    $conf['stop_spammers_whitelist'] = array();
  }

  // Verifier si IP recuperee est valide
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

  //Echapper l'IP dans la requette SQL 
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

// http pas idéal, au moins chiffrer éa requette
  $sfs_url = 'https://www.stopforumspam.com/api?ip='.urlencode($ip).'&f=serial&confidence';

  // Gerer les erreur de l'API distante
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
?>

<!-- Config a ajouter dans local/config/config.inc.php

$conf['stop_spammers_sfs_threshold'] = 20;
$conf['stop_spammers_cache_days'] = 30;
$conf['stop_spammers_whitelist'] = array(
  '127.0.0.1',
  '::1',
  // 'IP.PUBLIQUE.ICI',
); -->