<?php
/**
 * Upgrade the CiviCRM database schema.
 *
 * ## EXAMPLES
 *
 *     $ wp civicrm upgrade-db
 *     $ wp civicrm upgrade-db --dry-run
 *
 * @since 1.0.0
 */
class CLI_Tools_CiviCRM_Command_Upgrade_DB extends CLI_Tools_CiviCRM_Command {

  /**
   * Upgrade the CiviCRM database schema. Deprecated: use `wp civicrm core update-db` instead.
   *
   * ## OPTIONS
   *
   * [--dry-run]
   * : Preview the list of upgrade tasks.
   *
   * [--retry]
   * : Resume a failed upgrade, retrying the last step.
   *
   * [--skip]
   * : Resume a failed upgrade, skipping the last step.
   *
   * [--step]
   * : Run the upgrade queue in steps, pausing before each step.
   *
   * [--v]
   * : Run the upgrade queue with verbose output.
   *
   * [--vv]
   * : Run the upgrade queue with extra verbose output.
   *
   * ## EXAMPLES
   *
   *     $ wp civicrm upgrade-db --dry-run --v
   *     Deprecated command: use `wp civicrm core update-db` instead.
   *     Found CiviCRM code version: 5.57.1
   *     Found CiviCRM database version: 5.57.0
   *     Checking pre-upgrade messages.
   *     (No messages)
   *     Dropping SQL triggers.
   *     Preparing upgrade.
   *     Executing upgrade.
   *     Cleanup old files
   *     Cleanup old upgrade snapshots
   *     Checking extensions
   *     Finish Upgrade DB to 5.57.1
   *     Update all reserved message templates
   *     Finish core DB updates 5.57.1
   *     Assess extension upgrades
   *     Generate final messages
   *     Finishing upgrade.
   *     Upgrade to 5.57.1 completed.
   *     Checking post-upgrade messages.
   *     (No messages)
   *     Have a nice day.
   *
   * @since 1.0.0
   *
   * @param array $args The WP-CLI positional arguments.
   * @param array $assoc_args The WP-CLI associative arguments.
   */
  public function __invoke($args, $assoc_args) {

    WP_CLI::log(WP_CLI::colorize('%CDeprecated command:%n %cuse `wp civicrm core update-db` instead.%n'));

    // Grab associative arguments.
    $dry_run = (bool) \WP_CLI\Utils\get_flag_value($assoc_args, 'dry-run', FALSE);
    $retry = (bool) \WP_CLI\Utils\get_flag_value($assoc_args, 'retry', FALSE);
    $skip = (bool) \WP_CLI\Utils\get_flag_value($assoc_args, 'skip', FALSE);
    $step = (bool) \WP_CLI\Utils\get_flag_value($assoc_args, 'step', FALSE);
    $v = (bool) \WP_CLI\Utils\get_flag_value($assoc_args, 'v', FALSE);
    $vv = (bool) \WP_CLI\Utils\get_flag_value($assoc_args, 'vv', FALSE);

    // Build command.
    $command = 'civicrm core update-db' .
      (empty($dry_run) ? '' : ' --dry-run') .
      (empty($retry) ? '' : ' --retry') .
      (empty($skip) ? '' : ' --skip') .
      (empty($step) ? '' : ' --step') .
      (empty($v) ? '' : ' --v') .
      (empty($vv) ? '' : ' --vv');

    // Pass on to "wp civicrm core update-db".
    $options = ['launch' => FALSE, 'return' => FALSE];
    WP_CLI::runcommand($command, $options);

  }

}
