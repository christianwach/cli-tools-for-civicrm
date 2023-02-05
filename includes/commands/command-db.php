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
        $formatter = $this->formatter_get($assoc_args);
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
   * Drop the CiviCRM tables from the database.
   *
   * ## EXAMPLES
   *
   *     $ wp civicrm db drop-tables
   *
   * @subcommand drop-tables
   *
   * @since 1.0.0
   *
   * @param array $args The WP-CLI positional arguments.
   * @param array $assoc_args The WP-CLI associative arguments.
   */
  public function drop_tables($args, $assoc_args) {

    // Use "wp civicrm db tables" to find the CiviCRM core tables.
    $command = "civicrm db tables 'civicrm_*' 'log_civicrm_*' 'snap_civicrm_*' --base-tables-only --format=json";
    $options = ['launch' => FALSE, 'return' => TRUE];
    $core_tables = WP_CLI::runcommand($command, $options);

    // Convert to array.
    $tables = json_decode($core_tables, TRUE);
    if (JSON_ERROR_NONE !== json_last_error()) {
      WP_CLI::error(sprintf(WP_CLI::colorize('Failed to decode JSON: %y%s.%n'), json_last_error_msg()));
    }

    // Use "wp civicrm db tables" to find the CiviCRM core views.
    $command = "civicrm db tables 'civicrm_*' 'log_civicrm_*' 'snap_civicrm_*' --views-only --format=json";
    $options = ['launch' => FALSE, 'return' => TRUE];
    $core_views = WP_CLI::runcommand($command, $options);

    // Convert to array.
    $views = json_decode($core_views, TRUE);
    if (JSON_ERROR_NONE !== json_last_error()) {
      WP_CLI::error(sprintf(WP_CLI::colorize('Failed to decode JSON: %y%s.%n'), json_last_error_msg()));
    }

    // Get an instance of wpdb with CiviCRM credentials.
    $cividb = $this->cividb_get();
    $cividb->query('SET FOREIGN_KEY_CHECKS = 0');

    // Drop all the CiviCRM core tables.
    if (!empty($tables)) {
      WP_CLI::log('Dropping CiviCRM core tables...');
      foreach ($tables as $table) {
        $query = 'DROP TABLE IF EXISTS ' . \WP_CLI\Utils\esc_sql_ident($table);
        WP_CLI::debug($query);
        $cividb->query($query);
      }
      WP_CLI::success('CiviCRM core tables dropped.');
    }

    // Drop all the the CiviCRM core views.
    if (!empty($views)) {
      WP_CLI::log('Dropping CiviCRM core views...');
      foreach ($views as $view) {
        $query = 'DROP VIEW ' . \WP_CLI\Utils\esc_sql_ident($view);
        WP_CLI::debug($query);
        $cividb->query($query);
      }
      WP_CLI::success('CiviCRM core views dropped.');
    }

    // TODO: Perhaps we should also remove stored-procedures/functions?

  }

  /**
   * Export the whole CiviCRM database and print to STDOUT or save to a file.
   *
   * By default, CiviCRM loads its tables into the WordPress database but it is also possible
   * to configure CiviCRM to have its own database. To keep things contained, this command
   * only exports the tables, views, triggers, routines and events that are part of CiviCRM.
   *
   * ## OPTIONS
   *
   * [--tables=<tables>]
   * : The comma separated list of specific tables to export. Excluding this parameter will export all tables in the database.
   *
   * [--result-file=<result-file>]
   * : The path to the saved file. Excluding this parameter will export to STDOUT.
   *
   * ## EXAMPLES
   *
   *     # Export database to STDOUT.
   *     $ wp civicrm db dump
   *     -- MySQL dump 10.13  Distrib 5.7.34, for osx11.0 (x86_64)
   *     --
   *     -- Host: localhost    Database: civicrm_db
   *     -- ------------------------------------------------------
   *     -- Server version  5.7.34
   *     ...
   *
   *     # Export database to file.
   *     $ wp civicrm db dump --result-file=/tmp/civi-db.sql
   *     Success: Exported to /tmp/civi-db.sql
   *
   * @since 1.0.0
   *
   * @param array $args The WP-CLI positional arguments.
   * @param array $assoc_args The WP-CLI associative arguments.
   */
  public function dump($args, $assoc_args) {

    // Grab associative arguments.
    $tables = \WP_CLI\Utils\get_flag_value($assoc_args, 'tables', FALSE);

    // Bootstrap CiviCRM.
    $this->bootstrap_civicrm();

    if (!defined('CIVICRM_DSN')) {
      WP_CLI::error('CIVICRM_DSN is not defined.');
    }

    $mysqldump_binary = \WP_CLI\Utils\force_env_on_nix_systems('mysqldump');
    $dsn = DB::parseDSN(CIVICRM_DSN);

    // Do we want only certain CiviCRM tables?
    $runcommand_args = '';
    if (!empty($tables)) {
      $requested_tables = explode(',', $tables);
      foreach ($requested_tables as $table) {
        $runcommand_args .= " '" . trim($table) . "'";
      }
      unset($assoc_args['tables']);
    }

    // Get the list of tables.
    $options = ['launch' => FALSE, 'return' => TRUE];
    $tables = WP_CLI::runcommand("civicrm db tables{$runcommand_args} --format=csv", $options);
    $tables = explode(',', $tables);

    // Build command and escaped shell arguments.
    $command = $mysqldump_binary . " --opt --triggers --routines --events --host={$dsn['hostspec']} --user={$dsn['username']} --password='{$dsn['password']}' %s";
    $command_esc_args = [$dsn['database']];
    $command .= ' --tables';
    foreach ($tables as $table) {
      $command .= ' %s';
      $command_esc_args[] = trim($table);
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
   * Get the list of CiviCRM functions in the database.
   *
   * ## OPTIONS
   *
   * [<function>...]
   * : List functions based on wildcard search, e.g. 'civicrm_*' or 'civicrm_event?'.
   *
   * [--format=<format>]
   * : Render output in a particular format.
   * ---
   * default: list
   * options:
   *   - list
   *   - json
   *   - csv
   * ---
   *
   * ## EXAMPLES
   *
   *     $ wp civicrm db functions
   *     civicrm_strip_non_numeric
   *
   * @since 1.0.0
   *
   * @param array $args The WP-CLI positional arguments.
   * @param array $assoc_args The WP-CLI associative arguments.
   */
  public function functions($args, $assoc_args) {

    // Grab associative arguments.
    $format = \WP_CLI\Utils\get_flag_value($assoc_args, 'format', 'list');

    // Let's use an instance of wpdb with CiviCRM credentials.
    $cividb = $this->cividb_get();

    // Default query.
    $dsn = $this->cividb_dsn_get();
    $functions_sql = "SHOW FUNCTION STATUS WHERE db = '{$dsn['database']}'";

    // Perform query.
    $functions = $cividb->get_col($functions_sql, 1);

    // Filter by `$args` wildcards.
    if ($args) {
      $functions = $this->names_filter($args, $functions);
    }

    // Render output.
    if ('csv' === $format) {
      WP_CLI::log(implode(',', $functions));
    }
    elseif ('json' === $format) {
      $json = json_encode($functions);
      if (JSON_ERROR_NONE !== json_last_error()) {
        WP_CLI::error(sprintf(WP_CLI::colorize('Failed to encode JSON: %Y%s.%n'), json_last_error_msg()));
      }
      echo $json . "\n";
    }
    else {
      foreach ($functions as $function) {
        WP_CLI::log($function);
      }
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
   * Gets a set of CiviCRM tables in the database.
   *
   * ## OPTIONS
   *
   * [<table>...]
   * : List tables based on wildcard search, e.g. 'civicrm_*_group' or 'civicrm_event?'.
   *
   * [--base-tables-only]
   * : Restrict returned tables to those that are not views.
   *
   * [--views-only]
   * : Restrict returned tables to those that are views.
   *
   * [--format=<format>]
   * : Render output in a particular format.
   * ---
   * default: list
   * options:
   *   - list
   *   - json
   *   - csv
   * ---
   *
   * ## EXAMPLES
   *
   *     $ wp civicrm db tables 'civicrm_*_group' --base-tables-only
   *     civicrm_campaign_group
   *     civicrm_custom_group
   *     civicrm_dedupe_rule_group
   *     civicrm_mailing_group
   *     civicrm_option_group
   *     civicrm_uf_group
   *
   * @since 1.0.0
   *
   * @param array $args The WP-CLI positional arguments.
   * @param array $assoc_args The WP-CLI associative arguments.
   */
  public function tables($args, $assoc_args) {

    // Grab associative arguments.
    $base_tables_only = \WP_CLI\Utils\get_flag_value($assoc_args, 'base-tables-only');
    $views_only = \WP_CLI\Utils\get_flag_value($assoc_args, 'views-only');
    $format = \WP_CLI\Utils\get_flag_value($assoc_args, 'format', 'list');

    // Bail if incompatible args have been supplied.
    if (!empty($base_tables_only) && !empty($views_only)) {
      WP_CLI::error('You cannot supply --base-tables-only and --views-only at the same time.');
    }

    // Let's use an instance of wpdb with CiviCRM credentials.
    $cividb = $this->cividb_get();

    // Default query.
    $tables_sql = 'SHOW TABLES';

    // Override query with table type restriction if needed.
    if (!empty($base_tables_only)) {
      $tables_sql = 'SHOW FULL TABLES WHERE Table_Type = "BASE TABLE"';
    }
    elseif (!empty($views_only)) {
      $tables_sql = 'SHOW FULL TABLES WHERE Table_Type = "VIEW"';
    }

    // Perform query.
    $tables = $cividb->get_col($tables_sql, 0);

    // Pre-filter with CiviCRM tables and views only.
    $pre_filter = [
      'civicrm_*',
      'log_civicrm_*',
      'snap_civicrm_*',
    ];
    $tables = $this->names_filter($pre_filter, $tables);

    // Filter by `$args` wildcards.
    if ($args) {
      $tables = $this->names_filter($args, $tables);
    }

    // Render output.
    if ('csv' === $format) {
      WP_CLI::log(implode(',', $tables));
    }
    elseif ('json' === $format) {
      $json = json_encode($tables);
      if (JSON_ERROR_NONE !== json_last_error()) {
        WP_CLI::error(sprintf(WP_CLI::colorize('Failed to encode JSON: %Y%s.%n'), json_last_error_msg()));
      }
      echo $json . "\n";
    }
    else {
      foreach ($tables as $table) {
        WP_CLI::log($table);
      }
    }

  }

  // ----------------------------------------------------------------------------
  // Private methods.
  // ----------------------------------------------------------------------------

  /**
   * Gets the instance of wpdb with CiviCRM credentials.
   *
   * @since 1.0.0
   *
   * @return object $cividb The instance of wpdb with CiviCRM credentials.
   */
  private function cividb_get() {

    // Return instance if we have it.
    static $cividb;
    if (isset($cividb)) {
      return $cividb;
    }

    // Let's use an instance of wpdb with CiviCRM credentials.
    $dsn = $this->cividb_dsn_get();
    $cividb = new wpdb($dsn['username'], $dsn['password'], $dsn['database'], $dsn['hostspec']);

    return $cividb;

  }

  /**
   * Gets the CiviCRM database credentials.
   *
   * @since 1.0.0
   *
   * @return array $dsn The array of CiviCRM database credentials.
   */
  private function cividb_dsn_get() {

    // Bootstrap CiviCRM.
    $this->bootstrap_civicrm();

    // Bail if we can't fetch database credentials.
    if (!defined('CIVICRM_DSN')) {
      WP_CLI::error('CIVICRM_DSN is not defined.');
    }

    // Parse the CiviCRM credentials.
    $dsn = DB::parseDSN(CIVICRM_DSN);

    return $dsn;

  }

  /**
   * Filters an array of CiviCRM database entity names.
   *
   * @since 1.0.0
   *
   * @param array $wildcards The array of wildcards.
   * @param array $tables The array of CiviCRM table names.
   * @return array $filtered The filtered array of CiviCRM table names.
   */
  private function names_filter($wildcards, $tables) {

    // Build filtered array.
    $args_tables = [];
    foreach ($wildcards as $wildcard) {
      if (FALSE !== strpos($wildcard, '*') || FALSE !== strpos($wildcard, '?')) {
        $args_tables = array_merge(
          $args_tables,
          array_filter(
            $tables,
            function ($v) use ($wildcard) {
              // WP-CLI itself uses fnmatch() so ignore the civilint warning.
              // phpcs:disable
              return fnmatch($wildcard, $v);
              // phpcs:enable
            }
          )
        );
      }
      else {
        $args_tables[] = $wildcard;
      }
    }

    // Clean up.
    $args_tables = array_values(array_unique($args_tables));
    $filtered = array_values(array_intersect($tables, $args_tables));

    return $filtered;

  }

}
