<?php
/**
 * Upgrade the CiviCRM plugin files and database.
 *
 * ## EXAMPLES
 *
 *     # Update to the version of CiviCRM in the supplied archive.
 *     $ wp civicrm upgrade --zipfile=~/civicrm-5.57.1-wordpress.zip
 *
 * @since 1.0.0
 */
class CLI_Tools_CiviCRM_Command_Upgrade extends CLI_Tools_CiviCRM_Command {

  /**
   * Upgrade the CiviCRM plugin files and database. Deprecated: use `wp civicrm core update` instead.
   *
   * ## OPTIONS
   *
   * [--zipfile=<zipfile>]
   * : Path to your CiviCRM zip file.
   *
   * [--tarfile=<tarfile>]
   * : Path to your CiviCRM .tar.gz file. Not currently available.
   *
   * [--backup-dir=<backup-dir>]
   * : Path to your CiviCRM backup directory. Default is one level above ABSPATH.
   *
   * ## EXAMPLES
   *
   *     # Update to the version of CiviCRM in the supplied archive.
   *     $ wp civicrm upgrade --zipfile=~/civicrm-5.57.1-wordpress.zip
   *
   * @since 1.0.0
   *
   * @param array $args The WP-CLI positional arguments.
   * @param array $assoc_args The WP-CLI associative arguments.
   */
  public function __invoke($args, $assoc_args) {

    WP_CLI::log(WP_CLI::colorize('%CDeprecated command:%n %cuse `wp civicrm core update` instead.%n'));

    // Grab associative arguments.
    $zipfile = (string) \WP_CLI\Utils\get_flag_value($assoc_args, 'zipfile', '');
    $tarfile = (string) \WP_CLI\Utils\get_flag_value($assoc_args, 'l10n-tarfile', '');
    $backup_dir = (string) \WP_CLI\Utils\get_flag_value($assoc_args, 'backup-dir', '');

    // Bail when .tar.gz archive is specified.
    if (!empty($tarfile)) {
      WP_CLI::error('CiviCRM .tar.gz archives are not supported.');
    }

    // Use "wp civicrm core backup" to backup the CiviCRM installation.
    $command = 'civicrm core backup' . (empty($backup_dir) ? '' : ' --backup-dir=' . $backup_dir);
    $options = ['launch' => FALSE, 'return' => FALSE];
    WP_CLI::runcommand($command, $options);

    // Use "wp civicrm core update" to upgrade CiviCRM.
    $command = 'civicrm core update' . (empty($zipfile) ? '' : ' --zipfile=' . $zipfile);
    $options = ['launch' => FALSE, 'return' => FALSE];
    WP_CLI::runcommand($command, $options);

    // Use "wp civicrm core update-db" to upgrade the CiviCRM database.
    $command = 'civicrm core update-db --v';
    $options = ['launch' => FALSE, 'return' => FALSE];
    WP_CLI::runcommand($command, $options);

  }

}
