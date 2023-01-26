<?php
/**
 * Get the URL for a CiviCRM stable release or matching language archive.
 *
 * ## EXAMPLES
 *
 *     $ wp civicrm version-get --release=5.57.2
 *     https://storage.googleapis.com/civicrm/civicrm-stable/5.57.2/civicrm-5.57.2-wordpress.zip
 *
 *     $ wp civicrm version-get --release=5.57.2 --l10n
 *     https://storage.googleapis.com/civicrm/civicrm-stable/5.57.2/civicrm-5.57.2-l10n.tar.gz
 *
 * @since 1.0.0
 */
class CLI_Tools_CiviCRM_Command_Version_Get extends CLI_Tools_CiviCRM_Command {

  /**
   * @var string
   * The URL to check for all top-level CiviCRM prefixes.
   * @since 1.0.0
   * @access private
   */
  private $check_url = 'https://storage.googleapis.com/storage/v1/b/civicrm/o/?delimiter=/';

  /**
   * @var string
   * The query param to append for checking CiviCRM stable versions.
   * @since 1.0.0
   * @access private
   */
  private $stable_prefix = 'prefix=civicrm-stable/';

  /**
   * @var string
   * The common part of the URL for CiviCRM release archive downloads.
   * @since 1.0.0
   * @access private
   */
  private $download_url = 'https://storage.googleapis.com/civicrm/';

  /**
   * Get the URL for a CiviCRM stable release or matching language archive.
   *
   * ## OPTIONS
   *
   * [--release=<release>]
   * : Specify the CiviCRM stable version to get. Defaults to latest stable version.
   *
   * [--l10n]
   * : Get the localization file for the specified version.
   *
   * ## EXAMPLES
   *
   *     $ wp civicrm version-get --release=5.57.2
   *     https://storage.googleapis.com/civicrm/civicrm-stable/5.57.2/civicrm-5.57.2-wordpress.zip
   *
   *     $ wp civicrm version-get --release=5.57.2 --l10n
   *     https://storage.googleapis.com/civicrm/civicrm-stable/5.57.2/civicrm-5.57.2-l10n.tar.gz
   *
   * @since 1.0.0
   *
   * @param array $args The WP-CLI positional arguments.
   * @param array $assoc_args The WP-CLI associative arguments.
   */
  public function __invoke($args, $assoc_args) {

    // Grab incoming data.
    $release = \WP_CLI\Utils\get_flag_value($assoc_args, 'release', 'latest');
    $l10n = \WP_CLI\Utils\get_flag_value($assoc_args, 'l10n', FALSE);

    // Pass to "upgrade-get" for latest CiviCRM stable release or language archive.
    if ('latest' === $release) {
      $options = ['launch' => FALSE, 'return' => TRUE];
      $command = 'civicrm upgrade-get --stability=stable --raw' . (empty($l10n) ? '' : ' --l10n');
      $url = WP_CLI::runcommand($command, $options);
      echo $url . "\n";
      return;
    }

    // Check for valid release.
    $versions = $this->versions_get();
    if (!in_array($release, $versions)) {
      WP_CLI::error(sprintf(WP_CLI::colorize('Version %y%s%n is not a valid CiviCRM release.'), $release));
    }

    // Get the release data.
    $data = $this->release_data_get($release);

    if ($l10n) {
      echo $this->download_url . $data['L10n'] . "\n";
    }
    else {
      echo $this->download_url . $data['WordPress'] . "\n";
    }

  }

  /**
   * Gets the array of CiviCRM stable release versions.
   *
   * @since 1.0.0
   *
   * @return array The array of CiviCRM stable release versions.
   */
  private function versions_get() {

    // Get all release versions.
    $url = $this->check_url . '&' . $this->stable_prefix . '&maxResults=1000';
    $result = $this->json_get_request($url);
    if (empty($result['prefixes'])) {
      return [];
    }

    // Strip out all but the version.
    array_walk($result['prefixes'], function(&$item) {
      $item = trim(str_replace('civicrm-stable/', '', $item));
      $item = trim(str_replace('/', '', $item));
    });

    return $result['prefixes'];

  }

  /**
   * Gets the array of CiviCRM release data.
   *
   * @since 1.0.0
   *
   * @return array The array of CiviCRM release data.
   */
  private function release_data_get($release) {

    // Get the release data.
    $url = $this->check_url . '&' . $this->stable_prefix . $release . '/';
    $result = $this->json_get_request($url);
    if (empty($result['items'])) {
      return [];
    }

    // Strip out all but the WordPress and l10n data.
    $data = [];
    foreach ($result['items'] as $item) {
      if (!empty($item['name'])) {
        if (FALSE !== strpos($item['name'], 'wordpress.zip')) {
          $data['WordPress'] = $item['name'];
        }
        if (FALSE !== strpos($item['name'], 'l10n.tar.gz')) {
          $data['L10n'] = $item['name'];
        }
      }
    }

    return $data;

  }

}
