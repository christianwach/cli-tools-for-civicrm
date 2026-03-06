<?php
/**
 * Get an overview of CiviCRM and its environment.
 *
 * ## EXAMPLES
 *
 *     $ wp civicrm status
 *     Output here.
 *
 * @since 1.0.3
 *
 * @package Command_Line_Tools_for_CiviCRM
 */
class CLI_Tools_CiviCRM_Command_Status extends CLI_Tools_CiviCRM_Command {

  /**
   * Get an overview of CiviCRM and its environment.
   *
   * ## OPTIONS
   *
   * [--variable=<variable>]
   * : Specify the variable to get.
   * ---
   * default: all
   * options:
   *   - all
   *   - civicrm
   *   - db
   *   - mysql
   *   - wp
   *   - smarty
   *
   * [--format=<format>]
   * : Render output in a particular format.
   * ---
   * default: table
   * options:
   *   - table
   *   - json
   *   - number
   * ---
   *
   * ## EXAMPLES
   *
   *     $ wp civicrm status
   *     $ wp civicrm status --format=json
   *     $ wp civicrm status --variable=smarty --format=table
   *
   * @since 1.0.3
   *
   * @param array $args The WP-CLI positional arguments.
   * @param array $assoc_args The WP-CLI associative arguments.
   */
  public function __invoke($args, $assoc_args) {

    // Grab associative arguments.
    $variable = (string) \WP_CLI\Utils\get_flag_value($assoc_args, 'variable', 'all');
    $format = (string) \WP_CLI\Utils\get_flag_value($assoc_args, 'format', 'table');

    // Bootstrap CiviCRM.
    $this->bootstrap_civicrm();

    // Compile basic information.
    $civicrm_version = CRM_Utils_System::version();
    $db_version = CRM_Core_BAO_Domain::version();
    $mysql_version = CRM_Utils_SQL::getDatabaseVersion();
    $wp_version = \CRM_Core_Config::singleton()->userSystem->getVersion();

    // Get Smarty if we can.
    $smarty_version = 'Unknown';
    if (method_exists(CRM_Core_Smarty::singleton(), 'getVersion')) {
      $smarty_version = CRM_Core_Smarty::singleton()->getVersion();
    }

    // Get verbose PHP version.
    $php_version = $this->php_long();

    switch ($format) {

      // Number-only output.
      case 'number':
        if (!in_array($variable, ['civicrm', 'db', 'mysql', 'wp', 'smarty'])) {
          WP_CLI::error(WP_CLI::colorize('You must specify %Y--variable=<variable>%n%n to use this output format.'));
        }
        if ('civicrm' === $variable) {
          echo $civicrm_version . "\n";
        }
        if ('db' === $variable) {
          echo $db_version . "\n";
        }
        if ('smarty' === $variable) {
          echo $smarty_version . "\n";
        }
        if ('wp' === $variable) {
          echo $wp_version . "\n";
        }
        if ('mysql' === $variable) {
          echo $mysql_version . "\n";
        }
        if ('php' === $variable) {
          echo $php_version . "\n";
        }
        break;

      // Display output as json.
      case 'json':
        $info = [];
        if (in_array($variable, ['all', 'civicrm'])) {
          $info['civicrm'] = $civicrm_version;
        }
        if (in_array($variable, ['all', 'db'])) {
          $info['db'] = $db_version;
        }
        if (in_array($variable, ['all', 'smarty'])) {
          $info['smarty'] = $smarty_version;
        }
        if (in_array($variable, ['all', 'wp'])) {
          $info['wp'] = $wp_version;
        }
        if (in_array($variable, ['all', 'mysql'])) {
          $info['mysql'] = $mysql_version;
        }
        if (in_array($variable, ['all', 'php'])) {
          $info['php'] = $php_version;
        }
        $json = json_encode($info);
        if (JSON_ERROR_NONE !== json_last_error()) {
          WP_CLI::error(sprintf(WP_CLI::colorize('Failed to encode JSON: %Y%s.%n'), json_last_error_msg()));
        }
        echo $json . "\n";
        break;

      // Display output as table (default).
      case 'table':
      default:
        // Build the rows.
        $rows = [];
        $fields = ['Name', 'Value'];
        if (in_array($variable, ['all', 'civicrm'])) {
          $rows[] = [
            'Name' => 'CiviCRM',
            'Value' => $civicrm_version,
          ];
        }
        if (in_array($variable, ['all', 'db'])) {
          $rows[] = [
            'Name' => 'Database',
            'Value' => $db_version,
          ];
        }
        if (in_array($variable, ['all', 'smarty'])) {
          $rows[] = [
            'Name' => 'Smarty',
            'Value' => $smarty_version,
          ];
        }
        if (in_array($variable, ['all', 'wp'])) {
          $rows[] = [
            'Name' => 'WordPress',
            'Value' => $wp_version,
          ];
        }
        if (in_array($variable, ['all', 'php'])) {
          $rows[] = [
            'Name' => 'PHP',
            'Value' => $php_version,
          ];
        }
        if (in_array($variable, ['all', 'mysql'])) {
          $rows[] = [
            'Name' => 'MySQL',
            'Value' => $mysql_version,
          ];
        }

        // Display the rows.
        $args = ['format' => $format];
        $formatter = new \WP_CLI\Formatter($args, $fields);
        $formatter->display_items($rows);

    }

  }

  // ----------------------------------------------------------------------------
  // Private methods.
  // ----------------------------------------------------------------------------

  /**
   * Gets the PHP version and tries to establish the environment.
   *
   * @see Civi\Cv\Command\StatusCommand
   *
   * @since 1.0.0
   *
   * @return string $string The PHP version with environment in parentheses.
   */
  private function php_long() {

    // Init info array.
    $info = [PHP_SAPI => 1];

    // Check for Docker.
    if (file_exists('/.dockerenv')) {
      $info['docker'] = 1;
    }

    // Check for other environments.
    $info['other'] = 1;
    foreach ([PHP_BINARY, realpath(PHP_BINARY)] as $binary) {
      if (preg_match(';^/nix/;', $binary)) {
        $info['nix'] = 1;
        unset($info['other']);
      }
      if (preg_match(';/homebrew/;', $binary)) {
        // Newer deployments use /opt/homebrew. Dunno how to check older deployments in /usr/local.
        $info['homebrew'] = 1;
        unset($info['other']);
      }
      if (preg_match(';MAMP;', $binary)) {
        $info['mamp'] = 1;
        unset($info['other']);
      }
      if (preg_match(';^/usr/bin/;', $binary)) {
        $info['usr-bin'] = 1;
        unset($info['other']);
      }
      if (preg_match(';^/opt/plesk/;', $binary)) {
        $info['plesk'] = 1;
        unset($info['other']);
      }
    }

    // Build info string.
    $string = sprintf(
      '%s (%s)',
      PHP_VERSION,
      implode(', ', array_keys($info))
    );

    return $string;

  }

}
