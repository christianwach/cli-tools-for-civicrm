<?php
/**
 * WP-CLI port of drush-civicrm integration.
 *
 * @package Command_Line_Tools_for_CiviCRM
 */

// Exit if accessed directly.
defined('ABSPATH') || exit;

// Bail if WP-CLI is not present.
if (!class_exists('WP_CLI')) {
  return;
}

// Set up commands.
WP_CLI::add_hook('before_wp_load', function() {

  // Include files.
  require_once __DIR__ . '/commands/command-base.php';
  require_once __DIR__ . '/commands/command-civicrm.php';
  require_once __DIR__ . '/commands/command-api-v3.php';
  require_once __DIR__ . '/commands/command-cache-clear.php';
  require_once __DIR__ . '/commands/command-debug.php';
  require_once __DIR__ . '/commands/command-upgrade-db.php';
  require_once __DIR__ . '/commands/command-version.php';

  // Add top-level commands.
  WP_CLI::add_command('civicrm', 'CLI_Tools_CiviCRM_Command');
  WP_CLI::add_command('cv', 'CLI_Tools_CiviCRM_Command');

  // Add default API command.
  WP_CLI::add_command('civicrm api', 'CLI_Tools_CiviCRM_Command_API_V3', ['before_invoke' => 'CLI_Tools_CiviCRM_Command_API_V3::check_dependencies']);
  WP_CLI::add_command('cv api', 'CLI_Tools_CiviCRM_Command_API_V3', ['before_invoke' => 'CLI_Tools_CiviCRM_Command_API_V3::check_dependencies']);

  // Add API v3 command.
  WP_CLI::add_command('civicrm api3', 'CLI_Tools_CiviCRM_Command_API_V3', ['before_invoke' => 'CLI_Tools_CiviCRM_Command_API_V3::check_dependencies']);
  WP_CLI::add_command('cv api3', 'CLI_Tools_CiviCRM_Command_API_V3', ['before_invoke' => 'CLI_Tools_CiviCRM_Command_API_V3::check_dependencies']);

  // Add Cache Clear command.
  WP_CLI::add_command('civicrm cache-clear', 'CLI_Tools_CiviCRM_Command_Cache_Clear', ['before_invoke' => 'CLI_Tools_CiviCRM_Command_Cache_Clear::check_dependencies']);
  WP_CLI::add_command('cv cache-clear', 'CLI_Tools_CiviCRM_Command_Cache_Clear', ['before_invoke' => 'CLI_Tools_CiviCRM_Command_Cache_Clear::check_dependencies']);

  // Add Debug command.
  WP_CLI::add_command('civicrm debug', 'CLI_Tools_CiviCRM_Command_Debug', ['before_invoke' => 'CLI_Tools_CiviCRM_Command_Debug::check_dependencies']);
  WP_CLI::add_command('cv debug', 'CLI_Tools_CiviCRM_Command_Debug', ['before_invoke' => 'CLI_Tools_CiviCRM_Command_Debug::check_dependencies']);

  // Add database upgrade command.
  WP_CLI::add_command('civicrm upgrade-db', 'CLI_Tools_CiviCRM_Command_Upgrade_DB', ['before_invoke' => 'CLI_Tools_CiviCRM_Command_Upgrade_DB::check_dependencies']);
  WP_CLI::add_command('cv upgrade-db', 'CLI_Tools_CiviCRM_Command_Upgrade_DB', ['before_invoke' => 'CLI_Tools_CiviCRM_Command_Upgrade_DB::check_dependencies']);

  // Add version command.
  WP_CLI::add_command('civicrm version', 'CLI_Tools_CiviCRM_Command_Version', ['before_invoke' => 'CLI_Tools_CiviCRM_Command_Version::check_dependencies']);
  WP_CLI::add_command('cv version', 'CLI_Tools_CiviCRM_Command_Version', ['before_invoke' => 'CLI_Tools_CiviCRM_Command_Version::check_dependencies']);

  // Set paths early.
  global $civicrm_paths;
  $wp_cli_config = WP_CLI::get_config();

  // If --path is set, save for later use by CiviCRM.
  if (!empty($wp_cli_config['path'])) {
    $civicrm_paths['cms.root']['path'] = $wp_cli_config['path'];
  }

  // If --url is set, save for later use by CiviCRM.
  if (!empty($wp_cli_config['url'])) {
    $civicrm_paths['cms.root']['url'] = $wp_cli_config['url'];
  }

});
