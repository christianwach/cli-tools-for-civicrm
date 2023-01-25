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
   * [--raw]
   * : Print just the URL of the upgrade file instead of the full JSON data.
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

    // Let's look up the data.
    $stability = \WP_CLI\Utils\get_flag_value($assoc_args, 'stability', 'stable');
    $url = $this->check_url . "?stability=" . urlencode($stability);
    $result = file_get_contents($url);
    $lookup = json_decode($result, TRUE);

    // Error checking.
    if (empty($lookup)) {
      WP_CLI::error(sprintf('Version not found at %s'), $url);
    }
    if (empty($lookup['tar']['WordPress'])) {
      WP_CLI::error(sprintf('No WordPress version found at %s'), $url);
    }

    $raw = \WP_CLI\Utils\get_flag_value($assoc_args, 'raw', FALSE);
    if ($raw) {
      WP_CLI::log($lookup['tar']['WordPress']);
    }
    else {
      WP_CLI::log($result);
    }

  }

}
