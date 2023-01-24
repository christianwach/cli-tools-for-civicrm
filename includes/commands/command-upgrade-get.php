<?php
/**
 * Find out what file you should use to upgrade CiviCRM.
 *
 * ## EXAMPLES
 *
 *     $ wp civicrm upgrade-get --stability=rc
 *
 * @since 1.0.0
 */
class CLI_Tools_CiviCRM_Command_Upgrade_Get extends CLI_Tools_CiviCRM_Command {

  /**
   * @var array
   * The URL to check for CiviCRM.
   * @since 1.0.0
   * @access private
   */
  private $check_url = 'https://upgrade.civicrm.org/check';

  /**
   * Find out what file you should use to upgrade CiviCRM.
   *
   * ## OPTIONS
   *
   * [--stability=<stability>]
   * : Specify the stability of the version to get.
   * ---
   * default: stable
   * options:
   *   - nightly
   *   - rc
   *   - stable
   * ---
   *
   * ## EXAMPLES
   *
   *     $ wp civicrm upgrade-get --stability=rc
   *
   * @since 1.0.0
   *
   * @param array $args The WP-CLI positional arguments.
   * @param array $assoc_args The WP-CLI associative arguments.
   */
  public function __invoke($args, $assoc_args) {

  }

}
