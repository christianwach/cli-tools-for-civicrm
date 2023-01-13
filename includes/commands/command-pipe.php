<?php
/**
 * Start a Civi::pipe session (JSON-RPC 2.0)
 *
 * The Civi::pipe protocol provides a line-oriented session for executing multiple requests in a single CiviCRM instance.
 *
 * Callers may request <connection-flags>, such as:
 *
 * v: Show version
 * l: Show login support
 * t: Enable trusted mode
 * u: Enable untrusted mode
 *
 * ## EXAMPLES
 *
 *     $ wp civicrm pipe
 *     {"Civi::pipe":{"v":"5.47.alpha1","t":"trusted","l":["nologin"]}}
 *
 * @since 1.0.0
 */
class CLI_Tools_CiviCRM_Command_Pipe extends CLI_Tools_CiviCRM_Command {

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
   * Start a Civi::pipe session (JSON-RPC 2.0)
   *
   * The Civi::pipe protocol provides a line-oriented session for executing multiple requests in a single CiviCRM instance.
   *
   * Callers may request <connection-flags>, such as:
   *
   * v: Show version
   * l: Show login support
   * t: Enable trusted mode
   * u: Enable untrusted mode
   *
   * See https://docs.civicrm.org/dev/en/latest/framework/pipe#flags
   *
   * ## EXAMPLES
   *
   *     $ wp civicrm pipe
   *     {"Civi::pipe":{"v":"5.47.alpha1","t":"trusted","l":["nologin"]}}
   *
   *     $ wp civicrm pipe vu
   *     {"Civi::pipe":{"v":"5.57.1","u":"untrusted"}}
   *
   * @since 1.0.0
   *
   * @param array $args The WP-CLI positional arguments.
   * @param array $assoc_args The WP-CLI associative arguments.
   */
  public function __invoke($args, $assoc_args) {

    civicrm_initialize();

    if (!is_callable(['Civi', 'pipe'])) {
      WP_CLI::error('This version of CiviCRM does not include Civi::pipe() support.');
    }

    if (!empty($args[0])) {
      Civi::pipe($args[0]);
    }
    else {
      Civi::pipe();
    }

  }

}
