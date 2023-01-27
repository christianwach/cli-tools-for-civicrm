<?php
/**
 * Restore the CiviCRM plugin files and database.
 *
 * ## EXAMPLES
 *
 *     $ wp civicrm restore
 *
 * @since 1.0.0
 *
 * @package Command_Line_Tools_for_CiviCRM
 */
class CLI_Tools_CiviCRM_Command_Restore extends CLI_Tools_CiviCRM_Command {

  /**
   * Restore the CiviCRM plugin files and database.
   *
   * ## EXAMPLES
   *
   *     $ wp civicrm restore
   *
   * @since 1.0.0
   *
   * @param array $args The WP-CLI positional arguments.
   * @param array $assoc_args The WP-CLI associative arguments.
   */
  public function __invoke($args, $assoc_args) {

    // Validate.
    $restore_dir = \WP_CLI\Utils\get_flag_value('restore-dir', FALSE);
    $restore_dir = rtrim($restore_dir, '/');
    if (!$restore_dir) {
      WP_CLI::error('"restore-dir" not specified.');
    }

    $sql_file = $restore_dir . '/civicrm.sql';
    if (!file_exists($sql_file)) {
      WP_CLI::error('Could not locate "civicrm.sql" file in the restore directory.');
    }

    $code_dir = $restore_dir . '/civicrm';
    if (!is_dir($code_dir)) {
      WP_CLI::error('Could not locate the CiviCRM directory inside "restore-dir".');
    }
    elseif (!file_exists("$code_dir/civicrm/civicrm-version.txt") && !file_exists("$code_dir/civicrm/civicrm-version.php")) {
      WP_CLI::error('The CiviCRM directory inside "restore-dir" does not seem to be a valid CiviCRM codebase.');
    }

    // Prepare to restore.
    $date = date('YmdHis');

    civicrm_initialize();
    global $civicrm_root;

    $civicrm_root_base = explode('/', $civicrm_root);
    array_pop($civicrm_root_base);
    $civicrm_root_base = implode('/', $civicrm_root_base) . '/';

    $basepath = explode('/', $civicrm_root);

    if (!end($basepath)) {
      array_pop($basepath);
    }

    array_pop($basepath);
    $project_path = implode('/', $basepath) . '/';

    $restore_backup_dir = \WP_CLI\Utils\get_flag_value('backup-dir', ABSPATH . '../backup');
    $restore_backup_dir = rtrim($restore_backup_dir, '/');

    // Get confirmation from user.

    if (!defined('CIVICRM_DSN')) {
      WP_CLI::error('CIVICRM_DSN is not defined.');
    }

    $db_spec = DB::parseDSN(CIVICRM_DSN);
    WP_CLI::log('');
    WP_CLI::log('Process involves:');
    WP_CLI::log(sprintf("1. Restoring '\$restore-dir/civicrm' directory to '%s'.", $civicrm_root_base));
    WP_CLI::log(sprintf("2. Dropping and creating '%s' database.", $db_spec['database']));
    WP_CLI::log("3. Loading '\$restore-dir/civicrm.sql' file into the database.");
    WP_CLI::log('');
    WP_CLI::log(sprintf("Note: Before restoring, a backup will be taken in '%s' directory.", "$restore_backup_dir/plugins/restore"));
    WP_CLI::log('');

    WP_CLI::confirm('Do you really want to continue?');

    $restore_backup_dir .= '/plugins/restore/' . $date;

    if (!mkdir($restore_backup_dir, 0755, TRUE)) {
      WP_CLI::error(sprintf('Failed to create directory: %s', $restore_backup_dir));
    }

    // 1. Backup and restore codebase.
    WP_CLI::log('Restoring CiviCRM codebase...');
    if (is_dir($project_path) && !rename($project_path, $restore_backup_dir . '/civicrm')) {
      WP_CLI::error(sprintf("Failed to take backup for '%s' directory", $project_path));
    }

    if (!rename($code_dir, $project_path)) {
      WP_CLI::error(sprintf("Failed to restore CiviCRM directory '%s' to '%s'", $code_dir, $project_path));
    }

    WP_CLI::success('Codebase restored.');

    // 2. Backup, drop and create database.
    WP_CLI::run_command(
      ['civicrm', 'sql-dump'],
      ['result-file' => $restore_backup_dir . '/civicrm.sql']
    );

    WP_CLI::success('Database backed up.');

    // Prepare a mysql command-line string for issuing db drop/create commands.
    $command = sprintf(
      'mysql --user=%s --password=%s',
      $db_spec['username'],
      $db_spec['password']
    );

    if (isset($db_spec['hostspec'])) {
      $command .= ' --host=' . $db_spec['hostspec'];
    }

    if (isset($dsn['port']) && !mpty($dsn['port'])) {
      $command .= ' --port=' . $db_spec['port'];
    }

    // Attempt to drop old database.
    if (system($command . sprintf(' --execute="DROP DATABASE IF EXISTS %s"', $db_spec['database']))) {
      WP_CLI::error(sprintf('Could not drop database: %s', $db_spec['database']));
    }

    WP_CLI::success('Database dropped.');

    // Attempt to create new database.
    if (system($command . sprintf(' --execute="CREATE DATABASE %s"', $db_spec['database']))) {
      WP_CLI::error(sprintf('Could not create new database: %s', $db_spec['database']));
    }

    WP_CLI::success('Database created.');

    // 3. Restore database.
    WP_CLI::log('Loading "civicrm.sql" file from "restore-dir"...');
    system($command . ' ' . $db_spec['database'] . ' < ' . $sql_file);

    WP_CLI::success('Database restored.');

    WP_CLI::log('Clearing caches...');
    WP_CLI::run_command(['civicrm', 'cache-clear']);

    WP_CLI::success('Restore process completed.');

  }

}
