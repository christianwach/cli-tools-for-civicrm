<?php
/**
 * Get the current version of the CiviCRM codebase or database schema.
 *
 * ## EXAMPLES
 *
 *     $ wp civicrm version db
 *     Found CiviCRM database version: 5.47.1
 *
 *     $ wp civicrm version db --raw
 *     5.47.1
 *
 *     $ wp civicrm version code
 *     Found CiviCRM code version: 5.47.0
 *
 *     $ wp civicrm version code --raw
 *     5.47.0
 *
 * @since 1.0.0
 *
 * @package Command_Line_Tools_for_CiviCRM
 */
class CLI_Tools_CiviCRM_Command_Version extends CLI_Tools_CiviCRM_Command {

  /**
   * Get the current version of the CiviCRM database schema.
   *
   * ## OPTIONS
   *
   * [--raw]
   * : Print just the database version.
   *
   * ## EXAMPLES
   *
   *     $ wp civicrm version db
   *     Found CiviCRM database version: 5.47.1
   *
   *     $ wp civicrm version db --raw
   *     5.47.1
   *
   * @since 1.0.0
   *
   * @param array $args The WP-CLI positional arguments.
   * @param array $assoc_args The WP-CLI associative arguments.
   */
  public function db($args, $assoc_args) {

    civicrm_initialize();

    $db_version = CRM_Core_BAO_Domain::version();
    $raw = \WP_CLI\Utils\get_flag_value($assoc_args, 'raw', FALSE);

    if ($raw) {
      WP_CLI::log($db_version);
    }
    else {
      WP_CLI::log(sprintf(WP_CLI::colorize('%GFound CiviCRM database version:%n %Y%s%n'), $db_version));
    }

  }

  /**
   * Get the current version of the CiviCRM codebase.
   *
   * ## OPTIONS
   *
   * [--raw]
   * : Print just the version of the CiviCRM codebase.
   *
   * ## EXAMPLES
   *
   *     $ wp civicrm version code
   *     Found CiviCRM code version: 5.47.0
   *
   *     $ wp civicrm version code --raw
   *     5.47.0
   *
   * @since 1.0.0
   *
   * @param array $args The WP-CLI positional arguments.
   * @param array $assoc_args The WP-CLI associative arguments.
   */
  public function code($args, $assoc_args) {

    civicrm_initialize();

    $code_version = CRM_Utils_System::version();
    $raw = \WP_CLI\Utils\get_flag_value($assoc_args, 'raw', FALSE);

    if ($raw) {
      WP_CLI::log($code_version);
    }
    else {
      WP_CLI::log(sprintf(WP_CLI::colorize('%GFound CiviCRM code version:%n %Y%s%n'), $code_version));
    }

  }

}
