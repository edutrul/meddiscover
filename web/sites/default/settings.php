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
