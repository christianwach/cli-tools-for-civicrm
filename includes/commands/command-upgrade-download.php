<?php
/**
 * Download CiviCRM code and put it in place for an upgrade.
 *
 * ## EXAMPLES
 *
 *     $ wp civicrm upgrade-dl --stability=rc
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
   * : Specify the location to put the temporary tarball.
   *
	 * [--insecure]
	 * : Retry without certificate validation if TLS handshake fails. Note: This makes the request vulnerable to a MITM attack.
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

    // Let's get the incoming data.
    $stability = \WP_CLI\Utils\get_flag_value($assoc_args, 'stability', 'stable');
    $destination = \WP_CLI\Utils\get_flag_value($assoc_args, 'destination', \WP_CLI\Utils\get_temp_dir());

    // Use "wp civicrm update-get" to find out which file to download.
    $options = ['launch' => FALSE, 'return' => TRUE];
    $data = WP_CLI::runcommand('civicrm upgrade-get --stability=' . $stability, $options);

    // Get the raw data and the archive URL.
    $lookup = json_decode($data, TRUE);
    $download_url = $lookup['tar']['WordPress'];

    // Define filename to save.
    $filename = basename($download_url);

    // Maybe strip all the Google authentication stuff if present.
    if (FALSE !== strpos($filename, '?')) {
      $arr = explode('?', $filename);
      $filename = $arr[0];
    }

    $filepath = trailingslashit($destination) . $filename;

    $headers = [];
    $options = [
      'timeout'  => 600,  // 10 minutes ought to be enough for everybody.
      'filename' => $filepath,
      'insecure' => (bool) \WP_CLI\Utils\get_flag_value($assoc_args, 'insecure', FALSE),
    ];

    // Okay, let's do the download.
    $response = \WP_CLI\Utils\http_request('GET', $download_url, NULL, $headers, $options);
    if (!$response->success || 200 !== (int) $response->status_code) {
      WP_CLI::error(sprintf("Couldn't fetch response from %s (HTTP code %s)."), $url, $response->status_code);
    }

    WP_CLI::log($filepath);

   }

}
