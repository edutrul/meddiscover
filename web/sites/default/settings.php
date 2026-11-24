<?php

/**
 * @file
 * Environment-neutral medDiscover settings; local credentials are not tracked.
 */

if (getenv('IS_DDEV_PROJECT') === 'true' && file_exists(__DIR__ . '/settings.ddev.php')) {
  require __DIR__ . '/settings.ddev.php';
}
$settings['config_sync_directory'] = '../config/sync';
if (file_exists(__DIR__ . '/settings.local.php')) {
  require __DIR__ . '/settings.local.php';
}
if (getenv('IS_DDEV_PROJECT') === 'true') {
  // Public local defaults of the ddev/ddev-solr add-on, not secrets. Other
  // environments set their own Solr credentials in settings.local.php.
  $config['search_api.server.med_solr']['backend_config']['connector_config']['username'] = 'solr';
  $config['search_api.server.med_solr']['backend_config']['connector_config']['password'] = 'SolrRocks';
}
