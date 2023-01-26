<?php
/**
 * Download CiviCRM code and put it in place for an upgrade.
 *
 * ## EXAMPLES
 *
 *     $ wp civicrm upgrade-dl --lang
 *     $ wp civicrm upgrade-dl --stability=rc
 *     $ wp civicrm upgrade-dl --stability=rc --destination=/some/path
 *
 * @since 1.0.0
 */
class CLI_Tools_CiviCRM_Command_Upgrade_Download extends CLI_Tools_CiviCRM_Command {

  /**
   * Download CiviCRM code and put it in place for an upgrade.
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
   * [--destination=<destination>]
   * : Specify the absolute path to put the archive file. Defaults to local temp dir.
   *
   * [--insecure]
   * : Retry without certificate validation if TLS handshake fails. Note: This makes the request vulnerable to a MITM attack.
   *
   * [--lang]
   * : Get the localization file for the specified upgrade. Only applies when `--raw` is specified.
   *
   * ## EXAMPLES
   *
   *     $ wp civicrm upgrade-dl --lang
   *     /tmp/civicrm-5.57.2-l10n.tar.gz
   *
   *     $ wp civicrm upgrade-dl --stability=rc
   *     /tmp/civicrm-5.58.beta1-wordpress-202301260741.zip
   *
   *     $ wp civicrm upgrade-dl --stability=rc --lang --destination=/some/path
   *     /some/path/civicrm-5.58.beta1-l10n-202301260741.tar.gz
   *
   * @since 1.0.0
   *
   * @param array $args The WP-CLI positional arguments.
   * @param array $assoc_args The WP-CLI associative arguments.
   */
  public function __invoke($args, $assoc_args) {

    // Grab incoming data.
    $stability = \WP_CLI\Utils\get_flag_value($assoc_args, 'stability', 'stable');
    $destination = \WP_CLI\Utils\get_flag_value($assoc_args, 'destination', \WP_CLI\Utils\get_temp_dir());
    $lang = \WP_CLI\Utils\get_flag_value($assoc_args, 'lang', FALSE);
    $insecure = \WP_CLI\Utils\get_flag_value($assoc_args, 'insecure', FALSE);

    // Use "wp civicrm upgrade-get" to find out which file to download.
    $options = ['launch' => FALSE, 'return' => TRUE];
    $data = WP_CLI::runcommand('civicrm upgrade-get --stability=' . $stability, $options);

    // Get the raw data.
    $lookup = json_decode($data, TRUE);
    if (JSON_ERROR_NONE !== json_last_error()) {
      WP_CLI::error(sprintf(WP_CLI::colorize('Failed to decode JSON: %y%s.%n'), json_last_error_msg()));
    }

    // Grab either release or language archive URL.
    if ($lang) {
      $url = $lookup['tar']['L10n'];
    }
    else {
      $url = $lookup['tar']['WordPress'];
    }

    // Configure the download.
    $headers = [];
    $options = [
      'insecure' => (bool) $insecure,
    ];

    // Do the download now.
    $response = $this->file_download($url, $destination, $headers, $options);
    echo $response . "\n";

  }

}
