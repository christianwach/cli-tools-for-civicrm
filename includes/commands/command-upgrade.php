<?php
/**
 * Upgrade the CiviCRM plugin files and database.
 *
 * ## EXAMPLES
 *
 *     $ wp civicrm upgrade --zipfile=~/civicrm-5.57.1-wordpress.zip
 *
 * @since 1.0.0
 */
class CLI_Tools_CiviCRM_Command_Upgrade extends CLI_Tools_CiviCRM_Command {

  /**
   * Upgrade the CiviCRM plugin files and database.
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
   * [--zipfile=<zipfile>]
   * : Path to your CiviCRM zip file. If specified --stability is ignored.
   *
   * [--tarfile=<tarfile>]
   * : Path to your CiviCRM tar.gz file. Not currently available.
   *
   * [--backup-dir=<backup-dir>]
   * : Path to your CiviCRM backup directory. Default is one level above ABSPATH.
   *
   * ## EXAMPLES
   *
   *     $ wp civicrm upgrade --zipfile=~/civicrm-5.57.1-wordpress.zip
   *
   * @since 1.0.0
   *
   * @param array $args The WP-CLI positional arguments.
   * @param array $assoc_args The WP-CLI associative arguments.
   */
  public function __invoke($args, $assoc_args) {

    WP_CLI::log(WP_CLI::colorize('%GGathering system information.%n'));

    // Get requested version.
    $stability = \WP_CLI\Utils\get_flag_value($assoc_args, 'stability', 'stable');

    // Get compressed archive location.
    $tarfile = \WP_CLI\Utils\get_flag_value($assoc_args, 'tarfile', FALSE);
    $zipfile = \WP_CLI\Utils\get_flag_value($assoc_args, 'zipfile', FALSE);

    // Get backup directory.
    $backup_dir = \WP_CLI\Utils\get_flag_value($assoc_args, 'backup-dir', trailingslashit(dirname(ABSPATH)) . 'civicrm');

    // Let's have a look for some CiviCRM variables.
    civicrm_initialize();
    global $civicrm_root;
    $config = CRM_Core_Config::singleton();
    //WP_CLI::log(print_r($config, TRUE));

    // ----------------------------------------------------------------------------
    // Build feedback table.
    // ----------------------------------------------------------------------------

    // Build feedback.
    $feedback = [];
    if (!empty($tarfile)) {
      $feedback['Plugin tar.gz archive'] = $tarfile;
    }
    if (!empty($zipfile)) {
      $feedback['Plugin zip archive'] = $tarfile;
    }
    if (!empty($stability) && empty($zipfile)) {
      $feedback['Requested version'] = $stability;
      $options = ['launch' => FALSE, 'return' => TRUE];
      $archive = WP_CLI::runcommand('civicrm upgrade-get --raw --stability=' . $stability, $options);
      // Maybe strip all the Google authentication stuff if present.
      if (FALSE !== strpos($archive, '?')) {
        $arr = explode('?', $archive);
        $archive = $arr[0];
      }
      $feedback['Requested archive'] = $archive;
    }
    if (defined('CIVICRM_PLUGIN_DIR')) {
      $feedback['Plugin path'] = CIVICRM_PLUGIN_DIR;
    }
    if (!empty($civicrm_root)) {
      $feedback['CiviCRM root'] = $civicrm_root;
    }
    if (defined('CIVICRM_DSN')) {
      $dsn = DB::parseDSN(CIVICRM_DSN);
      $feedback['Database name'] = $dsn['database'];
      $feedback['Database username'] = $dsn['username'];
      $feedback['Database password'] = $dsn['password'];
      $feedback['Database host'] = $dsn['hostspec'];
    }
    if (!empty($config->configAndLogDir)) {
      $feedback['Config and Log'] = $config->configAndLogDir;
    }
    if (!empty($config->customPHPPathDir)) {
      $feedback['Custom PHP'] = $config->customPHPPathDir;
    }
    if (!empty($config->customTemplateDir)) {
      $feedback['Custom templates'] = $config->customTemplateDir;
    }
    if (!empty($config->templateCompileDir)) {
      $feedback['Compiled templates'] = $config->templateCompileDir;
    }
    if (!empty($config->extensionsDir)) {
      $feedback['Extensions directory'] = $config->extensionsDir;
    }
    if (!empty($config->uploadDir)) {
      $feedback['Uploads directory'] = $config->uploadDir;
    }
    if (!empty($config->imageUploadDir)) {
      $feedback['Image upload directory'] = $config->imageUploadDir;
    }
    if (!empty($config->customFileUploadDir)) {
      $feedback['File upload directory'] = $config->customFileUploadDir;
    }

    // Render feedback.
    $assoc_args['fields'] = array_keys($feedback);
    $formatter = $this->get_formatter($assoc_args);
    $formatter->display_item($feedback);

    return;

    // ----------------------------------------------------------------------------
    // Start upgrade.
    // ----------------------------------------------------------------------------

    // Maybe use "wp civicrm update-dl" to get the CiviCRM archive.
    if (!empty($stability) && empty($zipfile)) {
      $options = ['launch' => FALSE, 'return' => TRUE];
      $archive = WP_CLI::runcommand('civicrm upgrade-dl --stability=' . $stability, $options);
      if (!$this->unzip($zipfile, $plugins_dir)) {
        WP_CLI::error('Could not extract zipfile.');
      }
    }
    elseif (!empty($tarfile)) {
      if (!$this->untar($tarfile, $plugins_dir)) {
        WP_CLI::error('Could not extract tarfile.');
      }
    }
    elseif (!empty($zipfile)) {
      if (!$this->unzip($zipfile, $plugins_dir)) {
        WP_CLI::error('Could not extract zipfile.');
      }
    }

  }

}
