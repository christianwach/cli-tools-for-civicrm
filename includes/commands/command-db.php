<?php
/**
 * Utilities for interacting with the CiviCRM database.
 *
 * ## EXAMPLES
 *
 *     $ wp civicrm db cli
 *
 *     Welcome to the MySQL monitor.  Commands end with ; or \g.
 *     Your MySQL connection id is 180
 *     Server version: 5.7.34 MySQL Community Server (GPL)
 *
 *     mysql>
 *
 *     $ wp civicrm db config --format=table
 *     +----------+----------------+
 *     | Field    | Value          |
 *     +----------+----------------+
 *     | phptype  | mysqli         |
 *     | dbsyntax | mysqli         |
 *     | username | db_username    |
 *     | password | db_password    |
 *     | protocol | tcp            |
 *     | hostspec | localhost      |
 *     | port     | false          |
 *     | socket   | false          |
 *     | database | civicrm_dbname |
 *     | new_link | true           |
 *     +----------+----------------+
 *
 *     $ wp civicrm db query 'select id,name from civicrm_group;'
 *     +----+---------------------------+
 *     | id | name                      |
 *     +----+---------------------------+
 *     |  1 | Administrators            |
 *     |  4 | Advisory Board            |
 *     |  2 | Newsletter Subscribers    |
 *     |  3 | Summer Program Volunteers |
 *     +----+---------------------------+
 *
 * @since 1.0.0
 *
 * @package Command_Line_Tools_for_CiviCRM
 */
class CLI_Tools_CiviCRM_Command_DB extends CLI_Tools_CiviCRM_Command {

  /**
   * Quickly enter the MySQL command line.
   *
   * ## EXAMPLES
   *
   *     $ wp civicrm db cli
   *
   *     Welcome to the MySQL monitor.  Commands end with ; or \g.
   *     Your MySQL connection id is 180
   *     Server version: 5.7.34 MySQL Community Server (GPL)
   *
   *     mysql>
   *
   * @since 1.0.0
   *
   * @param array $args The WP-CLI positional arguments.
   * @param array $assoc_args The WP-CLI associative arguments.
   */
  public function cli($args, $assoc_args) {

    // Bootstrap CiviCRM.
    $this->bootstrap_civicrm();

    if (!defined('CIVICRM_DSN')) {
      WP_CLI::error('CIVICRM_DSN is not defined.');
    }

    $dsn = DB::parseDSN(CIVICRM_DSN);

    $mysql_args = [
      'host' => $dsn['hostspec'],
      'database' => $dsn['database'],
      'user' => $dsn['username'],
      'password' => $dsn['password'],
    ];

    \WP_CLI\Utils\run_mysql_command('mysql --no-defaults', $mysql_args);

  }

  /**
   * Show the CiviCRM database connection details.
   *
   * ## OPTIONS
   *
   * [--format=<format>]
   * : Render output in a particular format.
   * ---
   * default: table
   * options:
   *   - table
   *   - json
   *   - pretty
   * ---
   *
   * ## EXAMPLES
   *
   *     $ wp civicrm db config --format=table
   *     +----------+----------------+
   *     | Field    | Value          |
   *     +----------+----------------+
   *     | phptype  | mysqli         |
   *     | dbsyntax | mysqli         |
   *     | username | db_username    |
   *     | password | db_password    |
   *     | protocol | tcp            |
   *     | hostspec | localhost      |
   *     | port     | false          |
   *     | socket   | false          |
   *     | database | civicrm_dbname |
   *     | new_link | true           |
   *     +----------+----------------+
   *
   * @alias conf
   *
   * @since 1.0.0
   *
   * @param array $args The WP-CLI positional arguments.
   * @param array $assoc_args The WP-CLI associative arguments.
   */
  public function config($args, $assoc_args) {

    // Bootstrap CiviCRM.
    $this->bootstrap_civicrm();

    if (!defined('CIVICRM_DSN')) {
      WP_CLI::error('CIVICRM_DSN is not defined.');
    }

    $dsn = DB::parseDSN(CIVICRM_DSN);

    $format = \WP_CLI\Utils\get_flag_value($assoc_args, 'format', 'table');
    switch ($format) {

      // Pretty-print output.
      case 'pretty':
        WP_CLI::log(print_r($dsn, TRUE));
        break;

      // Display output as json.
      case 'json':
        WP_CLI::log(json_encode($dsn));
        break;

      // Display output as table (default).
      case 'table':
      default:
        $assoc_args['format'] = $format;
        $assoc_args['fields'] = array_keys($dsn);
        $formatter = $this->get_formatter($assoc_args);
        $formatter->display_item($dsn);

    }

  }

  /**
   * Get a string which connects to the CiviCRM database.
   *
   * ## EXAMPLES
   *
   *     $ wp civicrm db connect
   *     mysql --database=civicrm_db_name --host=db_host --user=db_username --password=db_password
   *
   * @since 1.0.0
   *
   * @param array $args The WP-CLI positional arguments.
   * @param array $assoc_args The WP-CLI associative arguments.
   */
  public function connect($args, $assoc_args) {

    // Bootstrap CiviCRM.
    $this->bootstrap_civicrm();

    if (!defined('CIVICRM_DSN')) {
      WP_CLI::error('CIVICRM_DSN is not defined.');
    }

    $dsn = DB::parseDSN(CIVICRM_DSN);

    $command = sprintf(
      'mysql --database=%s --host=%s --user=%s --password=%s',
      $dsn['database'],
      $dsn['hostspec'],
      $dsn['username'],
      $dsn['password']
    );

    if (isset($dsn['port']) && !empty($dsn['port'])) {
      $command .= ' --port=' . $dsn['port'];
    }

    WP_CLI::log($command);

  }

  /**
   * Export the whole CiviCRM database and print to STDOUT or save to a file.
   *
   * ## OPTIONS
   *
   * [--result-file=<result-file>]
   * : The path to the saved file.
   *
   * ## EXAMPLES
   *
   *     $ wp civicrm db dump
   *
   *     $ wp civicrm db dump --result-file=/tmp/civi-db.sql
   *     Success: Exported to /tmp/civi-db.sql
   *
   * @since 1.0.0
   *
   * @param array $args The WP-CLI positional arguments.
   * @param array $assoc_args The WP-CLI associative arguments.
   */
  public function dump($args, $assoc_args) {

    // Bootstrap CiviCRM when not called as part of an upgrade.
    if (!defined('CIVICRM_UPGRADE_ACTIVE')) {
      $this->bootstrap_civicrm();
    }

    if (!defined('CIVICRM_DSN') && !defined('CIVICRM_OLD_DSN')) {
      WP_CLI::error('DSN is not defined.');
    }

    $mysqldump_binary = \WP_CLI\Utils\force_env_on_nix_systems( 'mysqldump' );
    $dsn = self::parseDSN(defined('CIVICRM_DSN') ? CIVICRM_DSN : CIVICRM_OLD_DSN);

    // Build command and escaped shell arguments.
    $command = $mysqldump_binary . " --no-defaults --host={$dsn['hostspec']} --user={$dsn['username']} --password='{$dsn['password']}' %s";
    $command_esc_args = [$dsn['database']];
    if (isset($assoc_args['tables'])) {
      $tables = explode(',', $assoc_args['tables']);
      unset($assoc_args['tables']);
      $command .= ' --tables';
      foreach ($tables as $table) {
        $command .= ' %s';
        $command_esc_args[] = trim($table);
      }
    }

    // Process command and escaped shell arguments.
    $escaped_command = call_user_func_array(
      '\WP_CLI\Utils\esc_cmd',
      array_merge(
        [$command],
        $command_esc_args
      )
    );

    \WP_CLI\Utils\run_mysql_command($escaped_command, $assoc_args);

    // Maybe show some feedback.
    $result_file = \WP_CLI\Utils\get_flag_value($assoc_args, 'result-file', FALSE);
    if (!empty($result_file)) {
      WP_CLI::success(sprintf('Exported to %s', $assoc_args['result-file']));
    }

  }

  /**
   * Loads a whole CiviCRM database.
   *
   * ## OPTIONS
   *
   * [--load-file=<load-file>]
   * : The path to the database file.
   *
   * ## EXAMPLES
   *
   *     $ wp civicrm db load /tmp/civicrm.sql
   *
   * @since 1.0.0
   *
   * @param array $args The WP-CLI positional arguments.
   * @param array $assoc_args The WP-CLI associative arguments.
   */
  public function load($args, $assoc_args) {

    // Grab associative arguments.
    $load_file = \WP_CLI\Utils\get_flag_value($assoc_args, 'load-file', FALSE);

    // Bootstrap CiviCRM.
    $this->bootstrap_civicrm();

    if (!defined('CIVICRM_DSN')) {
      WP_CLI::error('CIVICRM_DSN is not defined.');
    }

    $dsn = DB::parseDSN(CIVICRM_DSN);

    $mysql_args = [
      'host'     => $dsn['hostspec'],
      'database' => $dsn['database'],
      'user'     => $dsn['username'],
      'password' => $dsn['password'],
      'execute'  => 'SOURCE ' . $load_file,
    ];

    \WP_CLI\Utils\run_mysql_command('/usr/bin/env mysql', $mysql_args);

  }

  /**
   * Perform a query on the CiviCRM database.
   *
   * ## OPTIONS
   *
   * <query>
   * : The SQL query to perform.
   *
   * ## EXAMPLES
   *
   *     $ wp civicrm db query 'select id,name from civicrm_group;'
   *     +----+---------------------------+
   *     | id | name                      |
   *     +----+---------------------------+
   *     |  1 | Administrators            |
   *     |  4 | Advisory Board            |
   *     |  2 | Newsletter Subscribers    |
   *     |  3 | Summer Program Volunteers |
   *     +----+---------------------------+
   *
   * @since 1.0.0
   *
   * @param array $args The WP-CLI positional arguments.
   * @param array $assoc_args The WP-CLI associative arguments.
   */
  public function query($args, $assoc_args) {

    if (!isset($args[0])) {
      WP_CLI::error('No query specified.');
    }

    $query = $args[0];

    // Bootstrap CiviCRM.
    $this->bootstrap_civicrm();

    if (!defined('CIVICRM_DSN')) {
      WP_CLI::error('CIVICRM_DSN is not defined.');
    }

    $dsn = DB::parseDSN(CIVICRM_DSN);

    $mysql_args = [
      'host'     => $dsn['hostspec'],
      'database' => $dsn['database'],
      'user'     => $dsn['username'],
      'password' => $dsn['password'],
      'execute'  => $query,
    ];

    \WP_CLI\Utils\run_mysql_command('/usr/bin/env mysql --no-defaults', $mysql_args);

  }

  /**
   * DSN parser.
   *
   * This is based on PEAR DB since we don't always have a bootstrapped environment that
   * we can access this from, eg: when doing an upgrade.
   *
   * @since 1.0.0
   *
   * @param string|array $dsn The database connection details.
   * @return array $parsed The array of parsed database connection details.
   */
  private static function parseDSN($dsn) {

    $parsed = [
      'phptype'  => FALSE,
      'dbsyntax' => FALSE,
      'username' => FALSE,
      'password' => FALSE,
      'protocol' => FALSE,
      'hostspec' => FALSE,
      'port'     => FALSE,
      'socket'   => FALSE,
      'database' => FALSE,
    ];

    // Process and return early when dsn is an array.
    if (is_array($dsn)) {
      $dsn = array_merge($parsed, $dsn);
      if (!$dsn['dbsyntax']) {
        $dsn['dbsyntax'] = $dsn['phptype'];
      }
      return $dsn;
    }

    // Find phptype and dbsyntax.
    if (($pos = strpos($dsn, '://')) !== FALSE) {
      $str = substr($dsn, 0, $pos);
      $dsn = substr($dsn, $pos + 3);
    }
    else {
      $str = $dsn;
      $dsn = NULL;
    }

    /*
     * Get phptype and dbsyntax.
     * $str => phptype(dbsyntax)
     */
    if (preg_match('|^(.+?)\((.*?)\)$|', $str, $arr)) {
      $parsed['phptype']  = $arr[1];
      $parsed['dbsyntax'] = !$arr[2] ? $arr[1] : $arr[2];
    }
    else {
      $parsed['phptype']  = $str;
      $parsed['dbsyntax'] = $str;
    }

    if (empty($dsn)) {
      return $parsed;
    }

    /*
     * Get (if found): username and password.
     * $dsn => username:password@protocol+hostspec/database
     */
    if (($at = strrpos($dsn, '@')) !== FALSE) {
      $str = substr($dsn, 0, $at);
      $dsn = substr($dsn, $at + 1);
      if (($pos = strpos($str, ':')) !== FALSE) {
        $parsed['username'] = rawurldecode(substr($str, 0, $pos));
        $parsed['password'] = rawurldecode(substr($str, $pos + 1));
      }
      else {
        $parsed['username'] = rawurldecode($str);
      }
    }

    // Find protocol and hostspec.
    if (preg_match('|^([^(]+)\((.*?)\)/?(.*?)$|', $dsn, $match)) {
      // $dsn => proto(proto_opts)/database
      $proto       = $match[1];
      $proto_opts  = $match[2] ? $match[2] : FALSE;
      $dsn         = $match[3];

    }
    else {
      // $dsn => protocol+hostspec/database (old format)
      if (strpos($dsn, '+') !== FALSE) {
        list($proto, $dsn) = explode('+', $dsn, 2);
      }
      if (strpos($dsn, '/') !== FALSE) {
        list($proto_opts, $dsn) = explode('/', $dsn, 2);
      }
      else {
        $proto_opts = $dsn;
        $dsn = NULL;
      }
    }

    // Process the different protocol options.
    $parsed['protocol'] = (!empty($proto)) ? $proto : 'tcp';
    $proto_opts = rawurldecode($proto_opts);
    if (strpos($proto_opts, ':') !== FALSE) {
      list($proto_opts, $parsed['port']) = explode(':', $proto_opts);
    }
    if ('tcp' == $parsed['protocol']) {
      $parsed['hostspec'] = $proto_opts;
    }
    elseif ('unix' == $parsed['protocol']) {
      $parsed['socket'] = $proto_opts;
    }

    /*
     * Get database if any.
     * $dsn => database
     */
    if ($dsn) {
      if (($pos = strpos($dsn, '?')) === FALSE) {
        // /database
        $parsed['database'] = rawurldecode($dsn);
      }
      else {
        // /database?param1=value1&param2=value2
        $parsed['database'] = rawurldecode(substr($dsn, 0, $pos));
        $dsn = substr($dsn, $pos + 1);
        if (strpos($dsn, '&') !== FALSE) {
          $opts = explode('&', $dsn);
        }
        else {
          // database?param1=value1
          $opts = [$dsn];
        }
        foreach ($opts as $opt) {
          list($key, $value) = explode('=', $opt);
          if (!isset($parsed[$key])) {
            // Don't allow params overwrite.
            $parsed[$key] = rawurldecode($value);
          }
        }
      }
    }

    return $parsed;

  }

}
