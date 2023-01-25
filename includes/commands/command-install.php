<?php
/**
 * Install the CiviCRM plugin files and database.
 *
 * ## EXAMPLES
 *
 *     $ wp civicrm install --zipfile=~/civicrm-5.57.1-wordpress.zip --ssl=on
 *
 * @since 1.0.0
 */
class CLI_Tools_CiviCRM_Command_Install extends CLI_Tools_CiviCRM_Command {

  /**
   * Install the CiviCRM plugin files and database.
   *
   * ## OPTIONS
   *
   * [--dbhost=<dbhost>]
   * : MySQL host for your CiviCRM database. Defaults to the WordPress MySQL host.
   *
   * [--dbname=<dbname>]
   * : MySQL database name of your CiviCRM database. Defaults to the WordPress database name.
   *
   * [--dbpass=<dbpass>]
   * : MySQL password for your CiviCRM database. Defaults to the WordPress MySQL database password.
   *
   * [--dbuser=<dbuser>]
   * : MySQL username for your CiviCRM database. Defaults to the WordPress MySQL database username.
   *
   * [--lang=<lang>]
   * : Language to use for installation. Defaults to "en_US".
   *
   * [--langtarfile=<langtarfile>]
   * : Path to your l10n tar.gz file.
   *
   * [--ssl=<ssl>]
   * : The SSL setting for your website, e.g. '--ssl=on'. Defaults to the WordPress "is_ssl" value.
   *
   * [--site_url=<site_url>]
   * : Domain for your website, e.g. 'mysite.com'.
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
   * ## EXAMPLES
   *
   *     $ wp civicrm install --zipfile=~/civicrm-5.57.1-wordpress.zip --ssl=on
   *     CiviCRM database credentials:
   *     +----------+-----------------------+
   *     | Field    | Value                 |
   *     +----------+-----------------------+
   *     | Database | civicrm_database_name |
   *     | Username | foo                   |
   *     | Password | dbpassword            |
   *     | Host     | localhost             |
   *     +----------+-----------------------+
   *     CiviCRM install configuration:
   *     +----------------+------------------------------------------+
   *     | Field          | Value                                    |
   *     +----------------+------------------------------------------+
   *     | SSL            | On                                       |
   *     | Plugin path    | /www/httpdocs/wp-content/plugins/civicrm |
   *     | Plugin archive | ~/civicrm-5.57.1-wordpress.zip           |
   *     +----------------+------------------------------------------+
   *     Do you want to continue? [y/n]
   *
   * @since 1.0.0
   *
   * @param array $args The WP-CLI positional arguments.
   * @param array $assoc_args The WP-CLI associative arguments.
   */
  public function __invoke($args, $assoc_args) {

    // TODO: We shouldn't need this because `is_ssl()` can supply the answer.
    $ssl = \WP_CLI\Utils\get_flag_value($assoc_args, 'ssl', FALSE);
    if ('on' === $ssl) {
      $_SERVER['HTTPS'] = 'on';
    }

    // Validate database parameters.
    $dbuser = \WP_CLI\Utils\get_flag_value($assoc_args, 'dbuser', (defined('DB_USER') ? DB_USER : FALSE));
    if (!$dbuser) {
      WP_CLI::error('CiviCRM database username not specified.');
    }
    $dbpass = \WP_CLI\Utils\get_flag_value($assoc_args, 'dbpass', (defined('DB_PASSWORD') ? DB_PASSWORD : FALSE));
    if (!$dbpass) {
      WP_CLI::error('CiviCRM database password not specified.');
    }
    $dbhost = \WP_CLI\Utils\get_flag_value($assoc_args, 'dbhost', (defined('DB_HOST') ? DB_HOST : FALSE));
    if (!$dbhost) {
      WP_CLI::error('CiviCRM database host not specified.');
    }
    $dbname = \WP_CLI\Utils\get_flag_value($assoc_args, 'dbname', (defined('DB_NAME') ? DB_NAME : FALSE));
    if (!$dbname) {
      WP_CLI::error('CiviCRM database name not specified.');
    }

    // Show database parameters.
    WP_CLI::log(WP_CLI::colorize('%GCiviCRM database credentials:%n'));
    $assoc_args['format'] = 'table';
    $feedback = [
      'Database' => $dbname,
      'Username' => $dbuser,
      'Password' => $dbpass,
      'Host' => $dbhost,
    ];
    $assoc_args['fields'] = array_keys($feedback);
    $formatter = $this->get_formatter($assoc_args);
    $formatter->display_item($feedback);

    global $wp_filesystem;
    if (empty($wp_filesystem)) {
      WP_Filesystem();
    }

    // Get the path to the WordPress plugins directory.
    $plugins_dir = $wp_filesystem->wp_plugins_dir();
    if (empty($plugins_dir)) {
      WP_CLI::error('Unable to locate WordPress plugins directory.');
    }

    /*
     * Identify the destination.
     *
     * NOTE: We shouldn't need this flag because (unlike Drupal modules) WordPress plugins
     * can only be installed in one location - which is `$plugins_dir`.
     *
     * Leaving this intact, however, for further review.
     */
    $plugin_path = \WP_CLI\Utils\get_flag_value($assoc_args, 'destination', FALSE);
    if (!empty($plugin_path)) {
      // The undocumented "--destination" flag should not be used.
      $plugin_path = ABSPATH . $plugin_path;
    }
    else {
      // This default should always be used.
      $plugin_path = trailingslashit($plugins_dir) . 'civicrm';
    }

    /*
     * Check for an existing CiviCRM codebase.
     *
     * NOTE: This is *not* the CiviCRM plugin - it is the directory where the common
     * CiviCRM code lives. It always lives in a sub-directory of the plugin directory
     * called "civicrm". The "files present" check should probably be for the CiviCRM
     * plugin directory itself rather than the CiviCRM sub-directory.
     */
    global $crmPath;
    $crmPath = trailingslashit($plugin_path) . 'civicrm';
    $crm_files_present = is_dir($crmPath);

    // Validate localization parameters before extracting CiviCRM.
    $lang = \WP_CLI\Utils\get_flag_value($assoc_args, 'lang', '');
    $langtarfile = \WP_CLI\Utils\get_flag_value($assoc_args, 'langtarfile', FALSE);
    $mo_path = "$crmPath/l10n/$lang/LC_MESSAGES/civicrm.mo";
    if (!empty($lang)) {
      if (empty($langtarfile) && !file_exists($mo_path)) {
        WP_CLI::error(sprintf('Failed to find data for language "%s". Please specify a valid language data archive with --langtarfile=<langtarfile>.', $lang));
      }
    }

    // Get requested version.
    $stability = \WP_CLI\Utils\get_flag_value($assoc_args, 'stability', 'stable');

    // Get compressed archive location.
    $tarfile = \WP_CLI\Utils\get_flag_value($assoc_args, 'tarfile', FALSE);
    $zipfile = \WP_CLI\Utils\get_flag_value($assoc_args, 'zipfile', FALSE);

    // Show installation parameters.
    WP_CLI::log(WP_CLI::colorize('%GCiviCRM install configuration:%n'));
    $feedback = [
      'SSL' => ('on' === $ssl) ? 'On' : 'Off',
      'Plugin path' => $plugin_path,
    ];
    if ($crm_files_present) {
      $feedback['Existing plugin'] = 'Yes';
    }
    if (!empty($tarfile)) {
      $feedback['Plugin archive'] = $tarfile;
    }
    if (!empty($zipfile)) {
      $feedback['Plugin archive'] = $zipfile;
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
    if (!empty($lang)) {
      $feedback['Language'] = $lang;
      if (!empty($langtarfile)) {
        $feedback['Language data archive'] = $langtarfile;
      }
    }
    $assoc_args['fields'] = array_keys($feedback);
    $formatter = $this->get_formatter($assoc_args);
    $formatter->display_item($feedback);

    // Let's give folks a chance to exit now.
    WP_CLI::confirm(WP_CLI::colorize('%GDo you want to continue?%n'), $assoc_args);

    // ----------------------------------------------------------------------------
    // Handle archives.
    // ----------------------------------------------------------------------------

    // Extract the archive.
    if (!empty($tarfile)) {
      // Should probably never get to here because WordPress CiviCRM comes in a zip file.
      // Check anyway just in case that ever changes.
      if ($crm_files_present) {
        WP_CLI::log(WP_CLI::colorize('%GExisting CiviCRM found. Skipping archive extraction.%n'));
      }
      else {

        // phpcs:disable
        /*
        // Extractor requires an absolute path. Hmm.
        WP_CLI::log(WP_CLI::colorize('%GExtracting archive.%n'));
        \WP_CLI\Extractor::extract($tarfile, $plugin_path);
        */
        // phpcs:enable

        if (!$this->untar($tarfile, $plugins_dir)) {
          WP_CLI::error('Could not extract tarfile.');
        }

      }
    }
    elseif (!empty($zipfile)) {
      if ($crm_files_present) {
        WP_CLI::log(WP_CLI::colorize('%GExisting CiviCRM found. Skipping archive extraction.%n'));
      }
      else {

        // phpcs:disable
        /*
        // Extractor requires an absolute path. Hmm.
        WP_CLI::log(WP_CLI::colorize('%GExtracting archive.%n'));
        \WP_CLI\Extractor::extract($zipfile, $plugin_path);
        */
        // phpcs:enable

        if (!$this->unzip($zipfile, $plugins_dir)) {
          WP_CLI::error('Could not extract zipfile.');
        }

      }
    }
    elseif (!empty($stability) && empty($zipfile)) {
      if ($crm_files_present) {
        WP_CLI::log(WP_CLI::colorize('%GExisting CiviCRM found. Skipping archive retrieval and extraction.%n'));
      }
      else {

        WP_CLI::log(WP_CLI::colorize('%GDownloading archive.%n'));
        $options = ['launch' => FALSE, 'return' => TRUE];
        $archive = WP_CLI::runcommand('civicrm upgrade-dl --stability=' . $stability, $options);
        if (!$this->unzip($archive, $plugins_dir)) {
          WP_CLI::error('Could not extract archive.');
        }
        unlink($archive);

      }
    }
    elseif ($crm_files_present) {
      // CiviCRM is already extracted - we just need to run the installer.
    }
    else {
      WP_CLI::error('No archive specified. Use "--zipfile=<zipfile>" or extract archive ahead of time.');
    }

    // Include CiviCRM classloader - so that we can run `Civi\Setup`.
    $classLoaderPath = "$crmPath/CRM/Core/ClassLoader.php";
    if (!file_exists($classLoaderPath)) {
      WP_CLI::error('Archive could not be unpacked or CiviCRM installer helper file is missing.');
    }

    // Feedback.
    if ($crm_files_present) {
      // We are using a directory that was already there.
      WP_CLI::success('Using installer files found on the site.');
    }
    else {
      // We must have just unpacked the archive because it wasn't there before.
      WP_CLI::success('Archive unpacked.');
    }

    // Maybe extract l10n files into the common CiviCRM directory.
    if (!empty($langtarfile)) {
      WP_CLI::log(sprintf(WP_CLI::colorize('%GUnpacking language archive to:%n %y%s%n'), $crmPath));
      if (!$this->untar($langtarfile, $plugin_path)) {
        WP_CLI::error('Could not extract language data archive.');
      }
    }

    // Check if l10n extraction went okay.
    if (!empty($lang) && !file_exists($mo_path)) {
      WP_CLI::log(sprintf(WP_CLI::colorize('%RNo .mo file found:%n %s'), $mo_path));
      WP_CLI::error(sprintf('Failed to find data for language "%s". Please specify a valid language data archive with --langtarfile=<path/to/tarfile>.', $lang));
    }

    // ----------------------------------------------------------------------------
    // Activation and installation.
    // ----------------------------------------------------------------------------

    // Looking good, let's activate the CiviCRM plugin.
    WP_CLI::run_command(['plugin', 'activate', 'civicrm'], []);

    // Initialize civicrm-setup.
    require_once $classLoaderPath;
    CRM_Core_ClassLoader::singleton()->register();
    \Civi\Setup::assertProtocolCompatibility(1.0);
    \Civi\Setup::init(['cms' => 'WordPress', 'srcPath' => $crmPath]);
    $setup = \Civi\Setup::instance();
    $setup->getModel()->db = ['server' => $dbhost, 'username' => $dbuser, 'password' => $dbpass, 'database' => $dbname];
    $setup->getModel()->lang = (empty($lang) ? 'en_US' : $lang);

    /*
     * The "base URL" should already be known, either by:
     *
     * * The "site_url()" setting in WordPress standalone
     * * The URL flag in WordPress Multisite: --url=https://my-domain.com
     *
     * TODO: This means that the `--site_url` flag is basically redundant.
     */
    $base_url = \WP_CLI\Utils\get_flag_value($assoc_args, 'site_url', FALSE);
    if (!empty($base_url)) {
      $protocol = ('on' == $ssl ? 'https' : 'http');
      $base_url = $protocol . '://' . $base_url;
      $setup->getModel()->cmsBaseUrl = trailingslashit($base_url);
    }

    // Validate system requirements.
    $reqs = $setup->checkRequirements();
    foreach ($reqs->getWarnings() as $msg) {
      WP_CLI::log(sprintf(WP_CLI::colorize('%YWARNING:%n %y(%s) %s:%n %s'), $msg['section'], $msg['name'], $msg['message']));
    }
    $errors = $reqs->getErrors();
    if ($errors) {
      foreach ($errors as $msg) {
        WP_CLI::log(sprintf(WP_CLI::colorize('%RERROR:%n %r(%s) %s:%n %s'), $msg['section'], $msg['name'], $msg['message']));
      }
      WP_CLI::error('Requirements check failed.');
    }

    // Install data files.
    $installed = $setup->checkInstalled();
    if (!$installed->isSettingInstalled()) {
      WP_CLI::log(sprintf(WP_CLI::colorize('%GCreating file%n %Y%s%n'), $setup->getModel()->settingsPath));
      $setup->installFiles();
    }
    else {
      WP_CLI::log(sprintf(WP_CLI::colorize('%gFound existing%n %Y%s%n %Gin%n %Y%s%n'), basename($setup->getModel()->settingsPath), dirname($setup->getModel()->settingsPath)));
      switch ($this->pick_conflict_action('civicrm.settings.php')) {
        case 'abort':
          throw new \Exception("Aborted");

        case 'overwrite':
          WP_CLI::log(sprintf(WP_CLI::colorize('%GRemoving%n %Y%s%n %Gfrom%n %Y%s%n'), basename($setup->getModel()->settingsPath), dirname($setup->getModel()->settingsPath)));
          $setup->uninstallFiles();
          WP_CLI::log(sprintf(WP_CLI::colorize('%GCreating%n %Y%s%n %Gin%n %Y%s%n'), basename($setup->getModel()->settingsPath), dirname($setup->getModel()->settingsPath)));
          $setup->installFiles();
          break;

        case 'keep':
          break;

        default:
          WP_CLI::error('Unrecognized action');
      }
    }

    WP_CLI::success('CiviCRM data files initialized successfully.');

    // Install database.
    if (!$installed->isDatabaseInstalled()) {
      WP_CLI::log(sprintf(WP_CLI::colorize('%GCreating%n %Ycivicrm_*%n database tables in%n %Y%s%n'), $setup->getModel()->db['database']));
      $setup->installDatabase();
    }
    else {
      WP_CLI::log(sprintf(WP_CLI::colorize('%GFound existing%n %Ycivicrm_*%n database tables in%n %Y%s%n'), $setup->getModel()->db['database']));
      switch ($this->pick_conflict_action('database tables')) {
        case 'abort':
          throw new \Exception('Aborted');

        case 'overwrite':
          WP_CLI::log(sprintf(WP_CLI::colorize('%GRemoving%n %Ycivicrm_*%n database tables in%n %Y%s%n'), $setup->getModel()->db['database']));
          $setup->uninstallDatabase();
          WP_CLI::log(sprintf(WP_CLI::colorize('%GCreating%n %Ycivicrm_*%n database tables in%n %Y%s%n'), $setup->getModel()->db['database']));
          $setup->installDatabase();
          break;

        case 'keep':
          break;

        default:
          WP_CLI::error('Unrecognized action');
      }
    }

    WP_CLI::success('CiviCRM database loaded successfully.');

    WP_CLI::success('CiviCRM installed.');

  }

  /**
   * Determine what action to take to resolve a conflict.
   *
   * @since 1.0.0
   *
   * @param string $title The thing which had a conflict.
   * @return string One of 'abort', 'keep' or 'overwrite'.
   */
  private function pick_conflict_action($title) {

    WP_CLI::log(sprintf(WP_CLI::colorize('%GThe%n %Y%s%n %Galready exists.%n'), $title));
    WP_CLI::log(WP_CLI::colorize('%G[a]%n %gAbort. (Default.)%n'));
    WP_CLI::log(sprintf(WP_CLI::colorize('%G[k]%n %gKeep existing%n %y%s%n%g.%n %r(%n%RWARNING:%n %rThis may fail if the existing version is out-of-date.)%n'), $title));
    WP_CLI::log(sprintf(WP_CLI::colorize('%G[o]%n %gOverwrite with new%n %y%s%g.%n %r(%n%RWARNING:%n %rThis may destroy data.)%n'), $title));

    fwrite(STDOUT, WP_CLI::colorize('%GWhat you like to do?%n '));
    $action = strtolower(trim(fgets(STDIN)));
    switch ($action) {
      case 'k':
        return 'keep';

      case 'o':
        return 'overwrite';

      case 'a':
      default:
        return 'abort';
    }

  }

}
