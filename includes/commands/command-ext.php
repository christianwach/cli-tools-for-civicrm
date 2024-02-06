<?php
/**
 * Utilities for interacting with CiviCRM Extensions.
 *
 * ## EXAMPLES
 *
 *     $ wp civicrm ext list --local
 *     +----------+---------------+---------------+---------+--------------+-------------+--------+----------------------------+
 *     | Location | Key           | Name          | Version | Label        | Status      | Type   | Path                       |
 *     +----------+---------------+---------------+---------+--------------+-------------+--------+----------------------------+
 *     | local    | authx         | authx         | 5.71.0  | AuthX        | installed   | module | /path/to/ext/authx         |
 *     | local    | civi_campaign | civi_campaign | 5.71.0  | CiviCampaign | disabled    | module | /path/to/ext/civi_campaign |
 *     |                                              ... more rows ...                                                        |
 *     +----------+---------------+---------------+---------+--------------+-------------+--------+----------------------------+
 *
 *     $ wp civicrm ext list --refresh
 *     Success: CiviCRM Extensions refreshed.
 *
 *     $ wp civicrm ext download org.example.foobar
 *     Success: CiviCRM Extension downloaded.
 *
 * @since 1.0.0
 *
 * @package Command_Line_Tools_for_CiviCRM
 */
class CLI_Tools_CiviCRM_Command_Ext extends CLI_Tools_CiviCRM_Command {

  /**
   * Download a CiviCRM Extension.
   *
   * ## EXAMPLES
   *
   *     $ wp civicrm ext download org.example.foobar
   *     Success: CiviCRM Extension downloaded.
   *
   *     $ wp civicrm ext download foobar --install
   *     Success: CiviCRM Extension downloaded and installed.
   *
   * ## OPTIONS
   *
   * <key-or-name>
   * : The extension full key ("org.example.foobar") or short name ("foobar").
   *
   * [--install]
   * : Download and install the specified CiviCRM Extension.
   *
   * @alias dl
   *
   * @since 1.0.0
   *
   * @param array $args The WP-CLI positional arguments.
   * @param array $assoc_args The WP-CLI associative arguments.
   */
  public function download($args, $assoc_args) {

    // Grab associative arguments.
    $install = (bool) \WP_CLI\Utils\get_flag_value($assoc_args, 'install', FALSE);

    // Tease out URL when present.
    $key_or_name = $args[0];
    $url = '';
    if (FALSE !== strpos($args[0], '@')) {
      list ($key_or_name, $url) = explode('@', $args[0], 2);
    }

    // Build API vars.
    $vars = 'key=' . $key_or_name;
    if (!empty($url)) {
      $vars .= ' url=' . $url;
    }
    if (empty($install)) {
      $vars .= ' install=0';
    }

    // Use "wp civicrm api" to do the download.
    $command = 'civicrm api extension.download ' . $vars . ' --format=json --quiet';
    $options = ['launch' => FALSE, 'return' => TRUE];
    $result = WP_CLI::runcommand($command, $options);

    // Convert to array.
    $result = json_decode($result, TRUE);
    if (JSON_ERROR_NONE !== json_last_error()) {
      WP_CLI::error(sprintf(WP_CLI::colorize('Failed to decode JSON: %y%s.%n'), json_last_error_msg()));
    }

    // Show error if present.
    if (!empty($result['is_error']) && 1 === (int) $result['is_error']) {
      WP_CLI::error(sprintf(WP_CLI::colorize('Failed to download CiviCRM Extension: %y%s%n'), $result['error_message']));
    }

    if (empty($install)) {
      WP_CLI::success('CiviCRM Extension downloaded.');
    }
    else {
      WP_CLI::success('CiviCRM Extension downloaded and installed.');
    }

  }

  /**
   * List the set of CiviCRM Extensions.
   *
   * ## EXAMPLES
   *
   *     $ wp civicrm ext list --local
   *     +----------+---------------+---------------+---------+--------------+-------------+--------+----------------------------+
   *     | Location | Key           | Name          | Version | Label        | Status      | Type   | Path                       |
   *     +----------+---------------+---------------+---------+--------------+-------------+--------+----------------------------+
   *     | local    | authx         | authx         | 5.71.0  | AuthX        | installed   | module | /path/to/ext/authx         |
   *     | local    | civi_campaign | civi_campaign | 5.71.0  | CiviCampaign | disabled    | module | /path/to/ext/civi_campaign |
   *     |                                              ... more rows ...                                                        |
   *     +----------+---------------+---------------+---------+--------------+-------------+--------+----------------------------+
   *
   *     $ wp civicrm ext list --refresh
   *     Success: CiviCRM Extensions refreshed.
   *
   * ## OPTIONS
   *
   * [--local]
   * : List only locally installed CiviCRM Extensions.
   *
   * [--remote]
   * : List only remotely available CiviCRM Extensions.
   *
   * [--refresh]
   * : Refresh the list of CiviCRM Extensions.
   *
   * [--format=<format>]
   * : Render output in a particular format.
   * ---
   * default: table
   * options:
   *   - table
   *   - json
   *   - url
   *   - version
   * ---
   *
   * @since 1.0.0
   *
   * @param array $args The WP-CLI positional arguments.
   * @param array $assoc_args The WP-CLI associative arguments.
   */
  public function list($args, $assoc_args) {

    // Grab associative arguments.
    $format = (string) \WP_CLI\Utils\get_flag_value($assoc_args, 'format', 'table');
    $local = (bool) \WP_CLI\Utils\get_flag_value($assoc_args, 'local', FALSE);
    $remote = (bool) \WP_CLI\Utils\get_flag_value($assoc_args, 'remote', FALSE);
    $refresh = (bool) \WP_CLI\Utils\get_flag_value($assoc_args, 'refresh', FALSE);

    // Bootstrap CiviCRM.
    $this->bootstrap_civicrm();

    // Was a refresh requested?
    if (!empty($refresh)) {

      // Pass on to "wp civicrm api".
      $options = ['launch' => FALSE, 'return' => TRUE];
      $result = WP_CLI::runcommand('civicrm api extension.refresh --format=json --quiet', $options);

      // Convert to array.
      $result = json_decode($result, TRUE);
      if (JSON_ERROR_NONE !== json_last_error()) {
        WP_CLI::error(sprintf(WP_CLI::colorize('Failed to decode JSON: %y%s.%n'), json_last_error_msg()));
      }

      // How did we do?
      if (!empty($result['is_error']) && 1 === (int) $result['is_error']) {
        WP_CLI::error(sprintf(WP_CLI::colorize('Failed to refresh CiviCRM Extensions: %y%s%n'), $result['error_message']));
      }

      WP_CLI::success('CiviCRM Extensions refreshed.');
      return;

    }

    // When both args are missing, show all.
    if (empty($local) && empty($remote)) {
      $local = TRUE;
      $remote = TRUE;
    }

    $rows = [];

    if (!empty($local)) {
      $keys = \CRM_Extension_System::singleton()->getFullContainer()->getKeys();
      $statuses = \CRM_Extension_System::singleton()->getManager()->getStatuses();
      $mapper = \CRM_Extension_System::singleton()->getMapper();
      foreach ($keys as $key) {
        $info = $mapper->keyToInfo($key);
        if (in_array($format, ['pretty', 'json'])) {
          $row = [
            'location' => 'local',
            'key' => $key,
            'name' => $info->file,
            'version' => $info->version,
            'label' => $info->label,
            'status' => isset($statuses[$key]) ? $statuses[$key] : '',
            'type' => $info->type,
            'path' => $mapper->keyToBasePath($key),
          ];
          if (!empty($remote)) {
            $row['downloadUrl'] = !empty($info->downloadUrl) ? $info->downloadUrl : '';
          }
          $rows[] = $row;
        }
        else {
          $row = [
            'Location' => 'local',
            'Key' => trim($key),
            'Name' => $info->file,
            'Version' => $info->version,
            'Label' => $info->label,
            'Status' => isset($statuses[$key]) ? $statuses[$key] : '',
            'Type' => $info->type,
            'Path' => $mapper->keyToBasePath($key),
          ];
          if (!empty($remote)) {
            $row['Download URL'] = !empty($info->downloadUrl) ? $info->downloadUrl : '';
          }
          $rows[] = $row;
        }
      }
    }

    if ($remote) {
      foreach ($this->extensions_remote_get() as $info) {
        if (in_array($format, ['pretty', 'json'])) {
          $rows[] = [
            'location' => 'remote',
            'key' => $info->key,
            'name' => $info->file,
            'version' => $info->version,
            'label' => $info->label,
            'status' => '',
            'type' => $info->type,
            'path' => '',
            'downloadUrl' => $info->downloadUrl,
          ];
        }
        else {
          $rows[] = [
            'Location' => 'remote',
            'Key' => $info->key,
            'Name' => $info->file,
            'Version' => $info->version,
            'Label' => $info->label,
            'Status' => '',
            'Type' => $info->type,
            'Path' => '',
            'Download URL' => $info->downloadUrl,
          ];
        }
      }
    }

    switch ($format) {

      // Pretty-print output.
      case 'pretty':
        WP_CLI::log(print_r($rows, TRUE));
        break;

      // Display output as json.
      case 'json':
        $json = json_encode($rows);
        if (JSON_ERROR_NONE !== json_last_error()) {
          WP_CLI::error(sprintf(WP_CLI::colorize('Failed to encode JSON: %Y%s.%n'), json_last_error_msg()));
        }
        echo $json . "\n";
        break;

      // Display output as table.
      case 'table':
      default:
        $fields = ['Location', 'Key', 'Name', 'Version', 'Label', 'Status', 'Type', 'Path'];
        if (!empty($remote)) {
          $fields[] = 'Download URL';
        }
        $args = ['format' => $format];
        $formatter = new \WP_CLI\Formatter($args, $fields);
        $formatter->display_items($rows);
        break;

    }

  }

  /**
   * Get a list of all available Extensions.
   *
   * @since 1.0.0
   *
   * @return array $cache The array of CiviCRM Extension info objects.
   */
  private function extensions_remote_get() {
    static $cache = NULL;
    if ($cache === NULL) {
      $cache = \CRM_Extension_System::singleton()->getBrowser()->getExtensions();
    }
    return $cache;
  }

}