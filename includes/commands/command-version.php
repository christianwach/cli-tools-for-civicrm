<?php
/**
 * Get the current version of the CiviCRM codebase or database schema.
 *
 * ## EXAMPLES
 *
 *   $ wp civicrm version db
 *   Found CiviCRM database version: 5.47.1
 *
 *   $ wp civicrm version db --raw
 *   5.47.1
 *
 *   $ wp civicrm version code
 *   Found CiviCRM code version: 5.47.0
 *
 *   $ wp civicrm version code --raw
 *   5.47.0
 *
 * @since 1.0.0
 */
class CLI_Tools_CiviCRM_Command_Version extends CLI_Tools_CiviCRM_Command {

  /**
   * Dependency check.
   *
   * @since 1.0.0
   */
  public static function check_dependencies() {
    // Check for existence of CiviCRM.
    if (!function_exists('civicrm_initialize')) {
      WP_CLI::error('Unable to find CiviCRM install.');
    }
  }

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
   *   $ wp civicrm version db
   *   Found CiviCRM database version: 5.47.1
   *
   *   $ wp civicrm version db --raw
   *   5.47.1
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
      WP_CLI::line($db_version);
    }
    else {
      WP_CLI::line(sprintf('Found CiviCRM database version: %s', $db_version));
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
   *   $ wp civicrm version code
   *   Found CiviCRM code version: 5.47.0
   *
   *   $ wp civicrm version code --raw
   *   5.47.0
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
      WP_CLI::line($code_version);
    }
    else {
      WP_CLI::line(sprintf('Found CiviCRM code version: %s', $code_version));
    }

  }

}