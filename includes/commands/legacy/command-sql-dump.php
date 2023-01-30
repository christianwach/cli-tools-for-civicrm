<?php
/**
 * Export the whole CiviCRM database and print to STDOUT or save to a file.
 *
 * ## EXAMPLES
 *
 *     $ wp civicrm sql-dump
 *
 * @since 1.0.0
 */
class CLI_Tools_CiviCRM_Command_SQL_Dump extends CLI_Tools_CiviCRM_Command {

  /**
   * Export the whole CiviCRM database and print to STDOUT or save to a file. Deprecated: use `wp civicrm db dump` instead.
   *
   * ## OPTIONS
   *
   * [--result-file=<result-file>]
   * : The path to the saved file.
   *
   * ## EXAMPLES
   *
   *     $ wp civicrm sql-dump
   *
   *     $ wp civicrm sql-dump --result-file=/tmp/civi-db.sql
   *     Success: Exported to /tmp/civi-db.sql
   *
   * @since 1.0.0
   *
   * @param array $args The WP-CLI positional arguments.
   * @param array $assoc_args The WP-CLI associative arguments.
   */
  public function __invoke($args, $assoc_args) {

    WP_CLI::log(WP_CLI::colorize('%CDeprecated command:%n %cuse `wp civicrm db dump` instead.%n'));

    // Grab associative arguments.
    $result_file = (string) \WP_CLI\Utils\get_flag_value($assoc_args, 'result-file', '');

    // Pass on to "wp civicrm db dump".
    $options = ['launch' => FALSE, 'return' => FALSE];
    $command = 'civicrm db dump' . (empty($result_file) ? '' : ' --result-file=' . $result_file);
    WP_CLI::runcommand($command, $options);

  }

}
