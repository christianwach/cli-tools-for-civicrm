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
   * @var string
   * The URL to check for CiviCRM upgrades.
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

    // Grab incoming params.
    $stability = \WP_CLI\Utils\get_flag_value($assoc_args, 'stability', 'stable');
    $raw = \WP_CLI\Utils\get_flag_value($assoc_args, 'raw', FALSE);

    // Look up the data.
    $url = $this->check_url . '?stability=' . $stability;
    $response = $this->json_get_response($url);
    $lookup = json_decode($response, TRUE);

    // Sanity checks.
    if (JSON_ERROR_NONE !== json_last_error()) {
      WP_CLI::error(sprintf(WP_CLI::colorize('Failed to decode JSON: %y%s.%n'), json_last_error_msg()));
    }
    if (empty($lookup)) {
      WP_CLI::error(sprintf(WP_CLI::colorize('Version not found at: %y%s%n'), $url));
    }
    if (empty($lookup['tar']['WordPress'])) {
      WP_CLI::error(sprintf(WP_CLI::colorize('No WordPress version found at: %y%s%n'), $url));
    }

    if ($raw) {
      WP_CLI::log($lookup['tar']['WordPress']);
    }
    else {
      WP_CLI::log($response);
    }

  }

}
