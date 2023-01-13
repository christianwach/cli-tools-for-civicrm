<?php
/**
 * Clear the CiviCRM cache.
 *
 * ## EXAMPLES
 *
 *     $ wp civicrm cache-clear
 *
 * @since 1.0.0
 */
class CLI_Tools_CiviCRM_Command_Cache_Clear extends CLI_Tools_CiviCRM_Command {

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
   * Clear the CiviCRM cache.
   *
   * ## EXAMPLES
   *
   *     $ wp civicrm cache-clear
   *
   * @since 1.0.0
   *
   * @param array $args The WP-CLI positional arguments.
   * @param array $assoc_args The WP-CLI associative arguments.
   */
  public function __invoke($args, $assoc_args) {

    civicrm_initialize();

    $config = CRM_Core_Config::singleton();

    // Clear db caching.
    $config->clearDBCache();

    // Also cleanup the templates_c directory.
    $config->cleanup(1, FALSE);

    // Also cleanup the session object.
    $session = CRM_Core_Session::singleton();
    $session->reset(1);

    WP_CLI::success('CiviCRM cache cleared.');

  }

}
