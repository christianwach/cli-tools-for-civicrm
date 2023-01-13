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
  require_once __DIR__ . '/commands/command-version.php';

  // Add top-level commands.
  WP_CLI::add_command('civicrm', 'CLI_Tools_CiviCRM_Command');
  WP_CLI::add_command('cv', 'CLI_Tools_CiviCRM_Command');

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
