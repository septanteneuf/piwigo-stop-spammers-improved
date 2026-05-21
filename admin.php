<?php
if (!defined('PHPWG_ROOT_PATH') || !defined('STOP_SPAMMERS_ID'))
{
  die('Hacking attempt!');
}

global $template, $conf, $page;

if (isset($_POST['submit']))
{
  check_pwg_token();

  $threshold = isset($_POST['stop_spammers_sfs_threshold']) ? (int)$_POST['stop_spammers_sfs_threshold'] : 20;
  $cache_days = isset($_POST['stop_spammers_cache_days']) ? (int)$_POST['stop_spammers_cache_days'] : 30;
  $max_links = isset($_POST['stop_spammers_max_links']) ? (int)$_POST['stop_spammers_max_links'] : 2;

  $threshold = max(1, min(100, $threshold));
  $cache_days = max(1, $cache_days);
  $max_links = max(0, $max_links);

  $keywords_raw = isset($_POST['stop_spammers_keywords']) ? $_POST['stop_spammers_keywords'] : '';
  $whitelist_raw = isset($_POST['stop_spammers_whitelist']) ? $_POST['stop_spammers_whitelist'] : '';

  $keywords = array_filter(array_map('trim', explode("\n", $keywords_raw)));
  $whitelist = array_filter(array_map('trim', explode("\n", $whitelist_raw)));

  $valid_whitelist = array();

  foreach ($whitelist as $ip)
  {
    if (filter_var($ip, FILTER_VALIDATE_IP))
    {
      $valid_whitelist[] = $ip;
    }
  }

  conf_update_param('stop_spammers_sfs_threshold', $threshold);
  conf_update_param('stop_spammers_cache_days', $cache_days);
  conf_update_param('stop_spammers_max_links', $max_links);
  conf_update_param('stop_spammers_keywords', serialize($keywords));
  conf_update_param('stop_spammers_whitelist', serialize($valid_whitelist));

  $conf['stop_spammers_sfs_threshold'] = $threshold;
  $conf['stop_spammers_cache_days'] = $cache_days;
  $conf['stop_spammers_max_links'] = $max_links;
  $conf['stop_spammers_keywords'] = $keywords;
  $conf['stop_spammers_whitelist'] = $valid_whitelist;

  $page['infos'][] = l10n('Configuration saved');
}

$keywords = isset($conf['stop_spammers_keywords']) ? $conf['stop_spammers_keywords'] : array();
$whitelist = isset($conf['stop_spammers_whitelist']) ? $conf['stop_spammers_whitelist'] : array();

if (is_string($keywords))
{
  $tmp = @unserialize($keywords);
  $keywords = is_array($tmp) ? $tmp : array();
}

if (is_string($whitelist))
{
  $tmp = @unserialize($whitelist);
  $whitelist = is_array($tmp) ? $tmp : array();
}

$template->assign(array(
  'STOP_SPAMMERS_SFS_THRESHOLD' => isset($conf['stop_spammers_sfs_threshold']) ? (int)$conf['stop_spammers_sfs_threshold'] : 20,
  'STOP_SPAMMERS_CACHE_DAYS' => isset($conf['stop_spammers_cache_days']) ? (int)$conf['stop_spammers_cache_days'] : 30,
  'STOP_SPAMMERS_MAX_LINKS' => isset($conf['stop_spammers_max_links']) ? (int)$conf['stop_spammers_max_links'] : 2,
  'STOP_SPAMMERS_KEYWORDS' => implode("\n", is_array($keywords) ? $keywords : array()),
  'STOP_SPAMMERS_WHITELIST' => implode("\n", is_array($whitelist) ? $whitelist : array()),
  'PWG_TOKEN' => get_pwg_token(),
  'F_ACTION' => get_root_url().'admin.php?page=plugin-'.STOP_SPAMMERS_ID,
));

$template->set_filenames(array(
  'plugin_admin_content' => dirname(__FILE__).'/admin.tpl',
));

$template->assign_var_from_handle('ADMIN_CONTENT', 'plugin_admin_content');