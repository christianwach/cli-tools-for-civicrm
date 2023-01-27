<?php
/**
 * WP-CLI port of drush-civicrm integration.
 *
 * @package Command_Line_Tools_for_CiviCRM
 */

// Bail if WP-CLI is not present.
if (!class_exists('WP_CLI')) {
  return;
}

// Set up commands.
WP_CLI::add_hook('before_wp_load', function() {

  // Include files.
  require_once __DIR__ . '/commands/command-base.php';
  require_once __DIR__ . '/commands/command-civicrm.php';
  require_once __DIR__ . '/commands/command-core.php';
  require_once __DIR__ . '/commands/command-api-v3.php';
  require_once __DIR__ . '/commands/command-cache.php';
  require_once __DIR__ . '/commands/command-db.php';
  require_once __DIR__ . '/commands/command-debug.php';
  require_once __DIR__ . '/commands/command-job.php';
  require_once __DIR__ . '/commands/command-pipe.php';

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
  WP_CLI::add_command('civicrm cache', 'CLI_Tools_CiviCRM_Command_Cache', ['before_invoke' => 'CLI_Tools_CiviCRM_Command_Cache::check_dependencies']);
  WP_CLI::add_command('cv cache', 'CLI_Tools_CiviCRM_Command_Cache', ['before_invoke' => 'CLI_Tools_CiviCRM_Command_Cache::check_dependencies']);

  // Add Core command.
  WP_CLI::add_command('civicrm core', 'CLI_Tools_CiviCRM_Command_Core');
  WP_CLI::add_command('cv core', 'CLI_Tools_CiviCRM_Command_Core');

  // Add DB command.
  WP_CLI::add_command('civicrm db', 'CLI_Tools_CiviCRM_Command_DB', ['before_invoke' => 'CLI_Tools_CiviCRM_Command_DB::check_dependencies']);
  WP_CLI::add_command('cv db', 'CLI_Tools_CiviCRM_Command_DB', ['before_invoke' => 'CLI_Tools_CiviCRM_Command_DB::check_dependencies']);

   // Add Debug command.
  WP_CLI::add_command('civicrm debug', 'CLI_Tools_CiviCRM_Command_Debug', ['before_invoke' => 'CLI_Tools_CiviCRM_Command_Debug::check_dependencies']);
  WP_CLI::add_command('cv debug', 'CLI_Tools_CiviCRM_Command_Debug', ['before_invoke' => 'CLI_Tools_CiviCRM_Command_Debug::check_dependencies']);

  // Add Job command.
  WP_CLI::add_command('civicrm job', 'CLI_Tools_CiviCRM_Command_Job', ['before_invoke' => 'CLI_Tools_CiviCRM_Command_Job::check_dependencies']);
  WP_CLI::add_command('cv job', 'CLI_Tools_CiviCRM_Command_Job', ['before_invoke' => 'CLI_Tools_CiviCRM_Command_Job::check_dependencies']);

  // Add Pipe command.
  WP_CLI::add_command('civicrm pipe', 'CLI_Tools_CiviCRM_Command_Pipe', ['before_invoke' => 'CLI_Tools_CiviCRM_Command_Pipe::check_dependencies']);
  WP_CLI::add_command('cv pipe', 'CLI_Tools_CiviCRM_Command_Pipe', ['before_invoke' => 'CLI_Tools_CiviCRM_Command_Pipe::check_dependencies']);

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
