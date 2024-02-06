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
 * @since 1.0.0
 *
 * @package Command_Line_Tools_for_CiviCRM
 */
class CLI_Tools_CiviCRM_Command_Ext extends CLI_Tools_CiviCRM_Command {

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
   * ## OPTIONS
   *
   * [--local]
   * : List only locally installed CiviCRM Extensions.
   *
   * [--remote]
   * : List only remotely available CiviCRM Extensions.
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
    if (empty($local) && empty($remote)) {
      $local = TRUE;
      $remote = TRUE;
    }

    // Bootstrap CiviCRM.
    $this->bootstrap_civicrm();

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
