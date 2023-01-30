<?php
/**
 * Downloads, installs, updates, and manages a CiviCRM installation.
 *
 * ## EXAMPLES
 *
 *     # Download the latest stable CiviCRM core archive.
 *     $ wp civicrm core download
 *     Checking file to download...
 *     Downloading file...
 *     Success: CiviCRM downloaded to /tmp/
 *
 *     # Install the current stable version of CiviCRM with localization files.
 *     $ wp civicrm core install --l10n
 *     Success: Installed 1 of 1 plugins.
 *     Success: CiviCRM localization downloaded and extracted to: /wp-content/plugins/civicrm
 *
 *     # Check for the latest stable version of CiviCRM.
 *     $ wp civicrm core check-update
 *     +-----------+---------+-------------------------------------------------------------------------------------------+
 *     | Package   | Version | Package URL                                                                               |
 *     +-----------+---------+-------------------------------------------------------------------------------------------+
 *     | WordPress | 5.57.2  | https://storage.googleapis.com/civicrm/civicrm-stable/5.57.2/civicrm-5.57.2-wordpress.zip |
 *     | L10n      | 5.57.2  | https://storage.googleapis.com/civicrm/civicrm-stable/5.57.2/civicrm-5.57.2-l10n.tar.gz   |
 *     +-----------+---------+-------------------------------------------------------------------------------------------+
 *
 * @since 1.0.0
 *
 * @package Command_Line_Tools_for_CiviCRM
 */
class CLI_Tools_CiviCRM_Command_Core extends CLI_Tools_CiviCRM_Command {

  /**
   * @var string
   * The URL to check for CiviCRM upgrades.
   * @since 1.0.0
   * @access private
   */
  private $upgrade_url = 'https://upgrade.civicrm.org/check';

  /**
   * @var string
   * The Google API URL to check for all top-level CiviCRM prefixes.
   * @since 1.0.0
   * @access private
   */
  private $google_url = 'https://storage.googleapis.com/storage/v1/b/civicrm/o/?delimiter=/';

  /**
   * @var string
   * The Google API query param to append for checking CiviCRM stable versions.
   * @since 1.0.0
   * @access private
   */
  private $google_prefix_stable = 'prefix=civicrm-stable/';

  /**
   * @var string
   * The common part of the Google API URL for CiviCRM release archive downloads.
   * @since 1.0.0
   * @access private
   */
  private $google_download_url = 'https://storage.googleapis.com/civicrm/';

  /**
   * Get the current version of the CiviCRM plugin and database.
   *
   * ## OPTIONS
   *
   * [--source=<source>]
   * : Specify the version to get.
   * ---
   * default: all
   * options:
   *   - all
   *   - plugin
   *   - db
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
   *     # Get all CiviCRM version information.
   *     $ wp civicrm core version
   *     +----------+---------+
   *     | Source   | Version |
   *     +----------+---------+
   *     | Plugin   | 5.57.1  |
   *     | Database | 5.46.3  |
   *     +----------+---------+
   *
   *     # Get just the CiviCRM database version number.
   *     $ wp civicrm core version --source=db --format=number
   *     5.46.3
   *
   *     # Get just the CiviCRM plugin version number.
   *     $ wp civicrm core version --source=plugin --format=number
   *     5.57.1
   *
   *     # Get all CiviCRM version information as JSON-formatted data.
   *     $ wp civicrm core version --format=json
   *     {"plugin":"5.57.1","db":"5.46.3"}
   *
   * @since 1.0.0
   *
   * @param array $args The WP-CLI positional arguments.
   * @param array $assoc_args The WP-CLI associative arguments.
   */
  public function version($args, $assoc_args) {

    // Grab associative arguments.
    $source = (string) \WP_CLI\Utils\get_flag_value($assoc_args, 'source', 'all');
    $format = (string) \WP_CLI\Utils\get_flag_value($assoc_args, 'format', 'table');

    // Bootstrap CiviCRM.
    $this->check_dependencies();
    civicrm_initialize();

    // Get the data we want.
    $plugin_version = CRM_Utils_System::version();
    $db_version = CRM_Core_BAO_Domain::version();

    switch ($format) {

      // Version number-only output.
      case 'number':
        if (!in_array($source, ['db', 'plugin'])) {
          WP_CLI::error(WP_CLI::colorize("You must specify %Y--source=plugin%n or %Y--source=db%n to use this output format."));
        }
        if ('plugin' === $source) {
          echo $plugin_version . "\n";
        }
        if ('db' === $source) {
          echo $db_version . "\n";
        }
        break;

      // Display output as json.
      case 'json':
        $info = [];
        if (in_array($source, ['all', 'plugin'])) {
          $info['plugin'] = $plugin_version;
        }
        if (in_array($source, ['all', 'db'])) {
          $info['db'] = $db_version;
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
        $fields = ['Source', 'Version'];
        if (in_array($source, ['all', 'plugin'])) {
          $rows[] = [
            'Source' => 'Plugin',
            'Version' => $plugin_version,
          ];
        }
        if (in_array($source, ['all', 'db'])) {
          $rows[] = [
            'Source' => 'Database',
            'Version' => $db_version,
          ];
        }

        // Display the rows.
        $args = ['format' => $format];
        $formatter = new \WP_CLI\Formatter($args, $fields);
        $formatter->display_items($rows);

    }

  }

  /**
   * Downloads CiviCRM core files or localization files.
   *
   * Downloads and extracts CiviCRM core files or localization files to the
   * specified path. Uses the local temp directory when no path is specified.
   *
   * ## OPTIONS
   *
   * [--version=<version>]
   * : Specify the CiviCRM version to get. Accepts a version number, 'stable', 'rc' or 'nightly'. Defaults to latest stable version.
   *
   * [--l10n]
   * : Get the localization file for the specified version.
   *
   * [--destination=<destination>]
   * : Specify the absolute path to put the archive file. Defaults to local temp directory.
   *
   * [--insecure]
   * : Retry without certificate validation if TLS handshake fails. Note: This makes the request vulnerable to a MITM attack.
   *
   * [--extract]
   * : Whether to extract the downloaded file. Defaults to false.
   *
   * ## EXAMPLES
   *
   *     # Download the latest stable CiviCRM core archive.
   *     $ wp civicrm core download
   *     Checking file to download...
   *     Downloading file...
   *     Success: CiviCRM downloaded to /tmp/
   *
   *     # Download and extract a stable CiviCRM core archive to a directory.
   *     $ wp civicrm core download --version=5.17.2 --extract --destination=/some/path
   *     Checking file to download...
   *     Downloading file...
   *     Extracting zip archive...
   *     Success: CiviCRM downloaded and extracted to: /some/path/
   *
   *     # Quietly download a stable CiviCRM core archive.
   *     $ wp civicrm core download --version=5.17.2 --quiet
   *     /tmp/civicrm-5.17.2-wordpress.zip
   *
   *     # Download and extract a stable CiviCRM localization archive to a directory.
   *     $ wp civicrm core download --version=5.17.2 --l10n --extract --destination=/some/path
   *     Checking file to download...
   *     Downloading file...
   *     Extracting tar.gz archive...
   *     Success: CiviCRM localization downloaded and extracted to: /some/path/
   *
   *     # Quietly download a stable CiviCRM localization archive.
   *     $ wp civicrm core download --version=5.17.2 --l10n --quiet
   *     /tmp/civicrm-5.17.2-l10n.tar.gz
   *
   * @since 1.0.0
   *
   * @param array $args The WP-CLI positional arguments.
   * @param array $assoc_args The WP-CLI associative arguments.
   */
  public function download($args, $assoc_args) {

    // Grab associative arguments.
    $version = (string) \WP_CLI\Utils\get_flag_value($assoc_args, 'version', 'stable');
    $l10n = (bool) \WP_CLI\Utils\get_flag_value($assoc_args, 'l10n', FALSE);
    $download_dir = (string) \WP_CLI\Utils\get_flag_value($assoc_args, 'destination', \WP_CLI\Utils\get_temp_dir());
    $insecure = (bool) \WP_CLI\Utils\get_flag_value($assoc_args, 'insecure', FALSE);
    $extract = (bool) \WP_CLI\Utils\get_flag_value($assoc_args, 'extract', FALSE);

    // Maybe create destination directory.
    if (!is_dir($download_dir)) {
      if (!is_writable(dirname($download_dir))) {
        WP_CLI::error("Insufficient permission to create directory '{$download_dir}'.");
      }
      WP_CLI::log("Creating directory '{$download_dir}'.");
      // Recursively create directory.
      if (!@mkdir( $download_dir, 0777, true )) {
        $error = error_get_last();
        WP_CLI::error( "Failed to create directory '{$download_dir}': {$error['message']}.");
      }
    }

    // Sanity check.
    if (!is_writable($download_dir)) {
      WP_CLI::error("'{$download_dir}' is not writable by current user.");
    }

    // Use "wp civicrm core check-version" to find out which file to download.
    WP_CLI::log(WP_CLI::colorize('%GChecking' . (empty($l10n) ? '' : ' localization') . ' file to download...%n'));
    $options = ['launch' => FALSE, 'return' => TRUE];
    $command = 'civicrm core check-version --version=' . $version . ' --format=url' . (empty($l10n) ? '' : ' --l10n');
    $url = WP_CLI::runcommand($command, $options);

    // Configure the download.
    $headers = [];
    $options = [
      'insecure' => (bool) $insecure,
    ];

    // Do the download now.
    WP_CLI::log(WP_CLI::colorize('%GDownloading file...%n'));
    $archive = $this->file_download($url, $download_dir, $headers, $options);

    // Stop early if not extracting.
    if (empty($extract)) {
      if (empty($l10n)) {
        WP_CLI::success(sprintf(WP_CLI::colorize('CiviCRM downloaded to: %Y%s%n'), $download_dir));
      }
      else {
        WP_CLI::success(sprintf(WP_CLI::colorize('CiviCRM localization downloaded to: %Y%s%n'), $download_dir));
      }
  		if (!empty(WP_CLI::get_config('quiet'))) {
        echo $archive . "\n";
  		}
      WP_CLI::halt(0);
    }

    // Extract the download.
    if (empty($l10n)) {
      if (!$this->unzip($archive, $download_dir)) {
        WP_CLI::error('Could not extract zipfile.');
      }
      WP_CLI::success(sprintf(WP_CLI::colorize('CiviCRM downloaded and extracted to: %Y%s%n'), $download_dir));
    }
    else {
      if (!$this->untar($archive, $download_dir)) {
        WP_CLI::error('Could not extract tarfile.');
      }
      WP_CLI::success(sprintf(WP_CLI::colorize('CiviCRM localization downloaded and extracted to: %Y%s%n'), $download_dir));
    }

  }

  /**
   * Install the CiviCRM plugin.
   *
   * ## OPTIONS
   *
   * [--version=<version>]
   * : Specify the CiviCRM version to get. Accepts a version number, 'stable', 'rc' or 'nightly'. Defaults to latest stable version.
   *
   * [--zipfile=<zipfile>]
   * : Path to your CiviCRM zip file. If specified --version is ignored.
   *
   * [--l10n]
   * : Additionally install the localization files for the specified version.
   *
   * [--l10n-tarfile=<l10n-tarfile>]
   * : Path to your l10n tar.gz file. If specified --l10n is ignored.
   *
   * [--force]
   * : If set, the command will overwrite any installed version of the plugin, without prompting for confirmation.
   *
   * ## EXAMPLES
   *
   *     # Install the current stable version of CiviCRM.
   *     $ wp civicrm core install
   *     Success: Installed 1 of 1 plugins.
   *
   *     # Install the current stable version of CiviCRM with localization files.
   *     $ wp civicrm core install --l10n
   *     Success: Installed 1 of 1 plugins.
   *     Success: CiviCRM localization downloaded and extracted to: /wp-content/plugins/civicrm
   *
   *     # Install a specific version of CiviCRM.
   *     $ wp civicrm core install --version=5.56.2
   *     Success: Installed 1 of 1 plugins.
   *
   * @since 1.0.0
   *
   * @param array $args The WP-CLI positional arguments.
   * @param array $assoc_args The WP-CLI associative arguments.
   */
  public function install($args, $assoc_args) {

    // Grab associative arguments.
    $version = (string) \WP_CLI\Utils\get_flag_value($assoc_args, 'version', 'stable');
    $zipfile = (string) \WP_CLI\Utils\get_flag_value($assoc_args, 'zipfile', '');
    $l10n = (string) \WP_CLI\Utils\get_flag_value($assoc_args, 'l10n', '');
    $l10n_tarfile = (string) \WP_CLI\Utils\get_flag_value($assoc_args, 'l10n-tarfile', '');
    $force = (bool) \WP_CLI\Utils\get_flag_value($assoc_args, 'force', FALSE);

    // Get the path to the CiviCRM plugin directory.
    $plugin_path = $this->get_plugin_path();

    // Only install plugin if not already installed.
    $fetcher = new \WP_CLI\Fetchers\Plugin();
    $installed = $fetcher->get('civicrm');
    if (!$installed || !empty($force)) {

      // When no zipfile is specified.
      if (!empty($version) && empty($zipfile)) {

        // Use "wp civicrm core check-version" to find out which file to download.
        WP_CLI::log(WP_CLI::colorize('%GChecking plugin file to download...%n'));
        $options = ['launch' => FALSE, 'return' => TRUE];
        $command = 'civicrm core check-version --version=' . $version . ' --format=url';
        $url = WP_CLI::runcommand($command, $options);

        // Use "wp plugin install" to install CiviCRM core.
        $options = ['launch' => FALSE, 'return' => FALSE];
        $command = 'plugin install ' . $url . (empty($force) ? '' : ' --force');
        WP_CLI::runcommand($command, $options);

      }
      elseif (!empty($zipfile)) {

        // If forcing, remove existing CiviCRM plugin directory.
        if (!empty($force)) {
          $cmd = 'rm -r ' . $plugin_path . '/civicrm';
          $process_run = WP_CLI::launch($cmd, $exit_on_error, $return_detailed);
          if (0 !== $process_run->return_code) {
            WP_CLI::error(sprintf(WP_CLI::colorize('Failed to delete existing CiviCRM plugin: %y%s.%n'), $this->tar_error_msg($process_run)));
          }
        }

        // Extract to plugin directory.
        WP_CLI::log(sprintf(WP_CLI::colorize('Extracting plugin archive to: %y%s%n'), $plugin_path));
        if (!$this->unzip($zipfile, $plugins_dir)) {
          WP_CLI::error('Could not extract plugin archive.');
        }
        WP_CLI::success(sprintf(WP_CLI::colorize('CiviCRM plugin extracted to: %Y%s%n'), $plugin_path));

      }

    }
    elseif (empty($l10n) && empty($l10n_tarfile)) {

      // Bail when plugin is installed and no localization archive is specified.
      WP_CLI::error('CiviCRM is already installed.');

    }

    // When localization is wanted but no archive is specified.
    if (!empty($l10n) && empty($l10n_tarfile)) {

      // Use "wp civicrm core check-version" to find out which file to download.
      $options = ['launch' => FALSE, 'return' => TRUE];
      $command = 'civicrm core check-version --version=' . $version . ' --l10n --format=url';
      $url = WP_CLI::runcommand($command, $options);

      // Use "wp civicrm core download" to download and extract.
      $options = ['launch' => FALSE, 'return' => FALSE];
      $command = 'civicrm core download --version=' . $version . ' --l10n --extract --destination=' . $plugin_path;
      WP_CLI::runcommand($command, $options);

    }
    elseif (!empty($l10n_tarfile)) {

      // Extract localization archive to plugin directory.
      WP_CLI::log(sprintf(WP_CLI::colorize('Extracting localization archive to: %y%s%n'), $plugin_path));
      if (!$this->untar($l10n_tarfile, $plugin_path)) {
        WP_CLI::error('Could not extract localization archive.');
      }
      WP_CLI::success(sprintf(WP_CLI::colorize('CiviCRM localization files extracted to: %Y%s%n'), $plugin_path));

    }

  }

  /**
   * Activates the CiviCRM plugin and loads the database.
   *
   * ## OPTIONS
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
   * [--dbhost=<dbhost>]
   * : MySQL host for your CiviCRM database. Defaults to the WordPress MySQL host.
   *
   * [--locale=<locale>]
   * : Locale to use for installation. Defaults to "en_US".
   *
   * [--ssl=<ssl>]
   * : The SSL setting for your website, e.g. '--ssl=on'. Defaults to "on".
   *
   * [--site-url=<site-url>]
   * : Domain for your website, e.g. 'mysite.com'.
   *
   * ## EXAMPLES
   *
   *     # Activate the CiviCRM plugin.
   *     $ wp civicrm core activate
   *     CiviCRM database credentials:
   *     +----------+-----------------------+
   *     | Field    | Value                 |
   *     +----------+-----------------------+
   *     | Database | civicrm_database_name |
   *     | Username | foo                   |
   *     | Password | dbpassword            |
   *     | Host     | localhost             |
   *     | Locale   | en_US                 |
   *     | SSL      | on                    |
   *     +----------+-----------------------+
   *     Do you want to continue? [y/n] y
   *     Creating file /httpdocs/wp-content/uploads/civicrm/civicrm.settings.php
   *     Success: CiviCRM data files initialized.
   *     Creating civicrm_* database tables in civicrm_database_name
   *     Success: CiviCRM database loaded.
   *     Plugin 'civicrm' activated.
   *     Success: Activated 1 of 1 plugins.
   *
   * @since 1.0.0
   *
   * @param array $args The WP-CLI positional arguments.
   * @param array $assoc_args The WP-CLI associative arguments.
   */
  public function activate($args, $assoc_args) {

    // Only install plugin if not already installed.
    $fetcher = new \WP_CLI\Fetchers\Plugin();
    $plugin_installed = $fetcher->get('civicrm');
    if (!$plugin_installed) {
      WP_CLI::error('You need to install CiviCRM first.');
    }

    // Get the path to the CiviCRM plugin directory.
    $plugin_path = $this->get_plugin_path();

    /*
     * Check for the presence of the CiviCRM core codebase.
     *
     * NOTE: This is *not* the CiviCRM plugin - it is the directory where the common
     * CiviCRM code lives. It always lives in a sub-directory of the plugin directory
     * called "civicrm".
     */
    global $crmPath;
    $crmPath = trailingslashit($plugin_path) . 'civicrm';
    if (!is_dir($crmPath)) {
      WP_CLI::error('CiviCRM core files are missing.');
    }

    // We need the CiviCRM classloader so that we can run `Civi\Setup`.
    $classLoaderPath = "$crmPath/CRM/Core/ClassLoader.php";
    if (!file_exists($classLoaderPath)) {
      WP_CLI::error('CiviCRM installer helper file is missing.');
    }

    // Grab associative arguments.
    $dbuser = (string) \WP_CLI\Utils\get_flag_value($assoc_args, 'dbuser', (defined('DB_USER') ? DB_USER : ''));
    $dbpass = (string) \WP_CLI\Utils\get_flag_value($assoc_args, 'dbpass', (defined('DB_PASSWORD') ? DB_PASSWORD : ''));
    $dbhost = (string) \WP_CLI\Utils\get_flag_value($assoc_args, 'dbhost', (defined('DB_HOST') ? DB_HOST : ''));
    $dbname = (string) \WP_CLI\Utils\get_flag_value($assoc_args, 'dbname', (defined('DB_NAME') ? DB_NAME : ''));
    $locale = (string) \WP_CLI\Utils\get_flag_value($assoc_args, 'locale', 'en_US');
    $ssl = (string) \WP_CLI\Utils\get_flag_value($assoc_args, 'ssl', 'on');
    $base_url = (string) \WP_CLI\Utils\get_flag_value($assoc_args, 'site-url', '');

    // Show database parameters.
    WP_CLI::log(WP_CLI::colorize('%GCiviCRM database credentials:%n'));
    $assoc_args['format'] = 'table';
    $feedback = [
      'Database' => $dbname,
      'Username' => $dbuser,
      'Password' => $dbpass,
      'Host' => $dbhost,
      'Locale' => $locale,
      'SSL' => $ssl,
    ];
    $assoc_args['fields'] = array_keys($feedback);
    $formatter = $this->get_formatter($assoc_args);
    $formatter->display_item($feedback);

    // Let's give folks a chance to exit now.
    WP_CLI::confirm(WP_CLI::colorize('%GDo you want to continue?%n'), $assoc_args);

    // ----------------------------------------------------------------------------
    // Activation and installation.
    // ----------------------------------------------------------------------------

    // Set some constants that CiviCRM requires.
    if (!defined('CIVICRM_PLUGIN_DIR')) {
      define('CIVICRM_PLUGIN_DIR', \WP_CLI\Utils\trailingslashit($plugin_path));
    }
    if (!defined('CIVICRM_PLUGIN_URL')) {
      define('CIVICRM_PLUGIN_URL', plugin_dir_url(CIVICRM_PLUGIN_DIR));
    }

    // Maybe set SSL.
    if ('on' === $ssl) {
      $_SERVER['HTTPS'] = 'on';
    }

    // Initialize civicrm-setup.
    require_once $classLoaderPath;
    CRM_Core_ClassLoader::singleton()->register();
    \Civi\Setup::assertProtocolCompatibility(1.0);
    \Civi\Setup::init(['cms' => 'WordPress', 'srcPath' => $crmPath]);
    $setup = \Civi\Setup::instance();

    // Apply essential arguments.
    $setup->getModel()->db = ['server' => $dbhost, 'username' => $dbuser, 'password' => $dbpass, 'database' => $dbname];
    $setup->getModel()->lang = $locale;

    /*
     * The "base URL" should already be known, either by:
     *
     * * The "site_url()" setting in WordPress standalone
     * * The URL flag in WordPress Multisite: --url=https://my-domain.com
     *
     * TODO: This means that the `--site_url` flag is basically redundant.
     */
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
          WP_CLI::log(WP_CLI::colorize('%CAborted%n'));
          WP_CLI::halt(0);

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

    WP_CLI::success('CiviCRM data files initialized.');

    // Clean the "templates_c" directory to avoid fatal error when overwriting the database.
    if (function_exists('civicrm_initialize')) {
      civicrm_initialize();
      $config = CRM_Core_Config::singleton();
      $config->cleanup(1, FALSE);
    }

    // Install database.
    if (!$installed->isDatabaseInstalled()) {
      WP_CLI::log(sprintf(WP_CLI::colorize('%GCreating%n %Ycivicrm_*%n %Gdatabase tables in%n %Y%s%n'), $setup->getModel()->db['database']));
      $setup->installDatabase();
    }
    else {
      WP_CLI::log(sprintf(WP_CLI::colorize('%GFound existing%n %Ycivicrm_*%n database tables in%n %Y%s%n'), $setup->getModel()->db['database']));
      switch ($this->pick_conflict_action('database tables')) {
        case 'abort':
          WP_CLI::log(WP_CLI::colorize('%CAborted%n'));
          WP_CLI::halt(0);

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

    WP_CLI::success('CiviCRM database loaded.');

    // Looking good, let's activate the CiviCRM plugin.
    WP_CLI::run_command(['plugin', 'activate', 'civicrm'], []);

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

  /**
   * Upgrade the CiviCRM plugin files and database.
   *
   * ## OPTIONS
   *
   * [--version=<version>]
   * : Specify the CiviCRM version to get. Accepts a version number, 'stable', 'rc' or 'nightly'. Defaults to latest stable version.
   *
   * [--zipfile=<zipfile>]
   * : Path to your CiviCRM zip file. If specified --version is ignored.
   *
   * [--l10n]
   * : Additionally install the localization files for the specified version.
   *
   * [--l10n-tarfile=<l10n-tarfile>]
   * : Path to your l10n tar.gz file. If specified --l10n is ignored.
   *
   * ## EXAMPLES
   *
   *     # Update to the current stable version of CiviCRM.
   *     $ wp civicrm core update
   *     Success: Installed 1 of 1 plugins.
   *
   * @since 1.0.0
   *
   * @param array $args The WP-CLI positional arguments.
   * @param array $assoc_args The WP-CLI associative arguments.
   */
  public function update($args, $assoc_args) {

    // Grab associative arguments.
    $version = (string) \WP_CLI\Utils\get_flag_value($assoc_args, 'version', 'stable');
    $zipfile = (string) \WP_CLI\Utils\get_flag_value($assoc_args, 'zipfile', '');
    $l10n = (string) \WP_CLI\Utils\get_flag_value($assoc_args, 'l10n', '');
    $l10n_tarfile = (string) \WP_CLI\Utils\get_flag_value($assoc_args, 'l10n-tarfile', '');

    WP_CLI::log(WP_CLI::colorize('%GGathering system information.%n'));

    // Get backup directory.
    $backup_dir = \WP_CLI\Utils\get_flag_value($assoc_args, 'backup-dir', trailingslashit(dirname(ABSPATH)) . 'civicrm');

    // Bootstrap CiviCRM.
    $this->check_dependencies();
    civicrm_initialize();

    // Let's have a look for some CiviCRM variables.
    global $civicrm_root;
    $config = CRM_Core_Config::singleton();
    //WP_CLI::log(print_r($config, TRUE));

    // ----------------------------------------------------------------------------
    // Build feedback table.
    // ----------------------------------------------------------------------------

    // Build feedback.
    $feedback = [];
    if (!empty($zipfile)) {
      $feedback['Plugin zip archive'] = $zipfile;
    }
    if (!empty($version) && empty($zipfile)) {
      $feedback['Requested version'] = $version;
      $options = ['launch' => FALSE, 'return' => TRUE];
      $command = 'civicrm core check-version --version=' . $version . ' --format=url';
      $archive = WP_CLI::runcommand($command, $options);
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

    // Let's give folks a chance to exit now.
    WP_CLI::confirm(WP_CLI::colorize('%GDo you want to continue?%n'), $assoc_args);

    // ----------------------------------------------------------------------------
    // Start upgrade.
    // ----------------------------------------------------------------------------

    // Enable Maintenance Mode.
    //WP_CLI::runcommand('maintenance-mode activate', ['launch' => FALSE, 'return' => FALSE]);

    // Build install command.
    $command = 'civicrm core install' .
      (empty($version) ? '' : ' --version=' . $version) .
      (empty($zipfile) ? '' : ' --zipfile=' . $zipfile) .
      (empty($l10n) ? '' : ' --l10n') .
      (empty($langtarfile) ? '' : ' --l10n-tarfile=' . $langtarfile) .
      ' --force';

    // Run "wp civicrm core install".
    $options = ['launch' => FALSE, 'return' => FALSE];
    WP_CLI::runcommand($command, $options);

    // Disable Maintenance Mode.
    //WP_CLI::runcommand('maintenance-mode deactivate', ['launch' => FALSE, 'return' => FALSE]);

  }

  /**
   * Upgrade the CiviCRM database schema.
   *
   * ## OPTIONS
   *
   * [--dry-run]
   * : Preview the list of upgrade tasks.
   *
   * [--retry]
   * : Resume a failed upgrade, retrying the last step.
   *
   * [--skip]
   * : Resume a failed upgrade, skipping the last step.
   *
   * [--step]
   * : Run the upgrade queue in steps, pausing before each step.
   *
   * [--v]
   * : Run the upgrade queue with verbose output.
   *
   * [--vv]
   * : Run the upgrade queue with extra verbose output.
   *
   * ## EXAMPLES
   *
   *     $ wp civicrm core update-db --dry-run --v
   *     Found CiviCRM code version: 5.57.1
   *     Found CiviCRM database version: 5.57.0
   *     Checking pre-upgrade messages.
   *     (No messages)
   *     Dropping SQL triggers.
   *     Preparing upgrade.
   *     Executing upgrade.
   *     Cleanup old files
   *     Cleanup old upgrade snapshots
   *     Checking extensions
   *     Finish Upgrade DB to 5.57.1
   *     Update all reserved message templates
   *     Finish core DB updates 5.57.1
   *     Assess extension upgrades
   *     Generate final messages
   *     Finishing upgrade.
   *     Upgrade to 5.57.1 completed.
   *     Checking post-upgrade messages.
   *     (No messages)
   *     Have a nice day.
   *
   * @subcommand update-db
   *
   * @since 1.0.0
   *
   * @param array $args The WP-CLI positional arguments.
   * @param array $assoc_args The WP-CLI associative arguments.
   */
  public function update_db($args, $assoc_args) {

    // Bootstrap CiviCRM.
    $this->check_dependencies();
    civicrm_initialize();

    if (!defined('CIVICRM_UPGRADE_ACTIVE')) {
      define('CIVICRM_UPGRADE_ACTIVE', 1);
    }

    // Check whether an upgrade is necessary.
    $code_version = CRM_Utils_System::version();
    WP_CLI::log(sprintf(WP_CLI::colorize('%GFound CiviCRM code version:%n %Y%s%n'), $code_version));
    $db_version = CRM_Core_BAO_Domain::version();
    WP_CLI::log(sprintf(WP_CLI::colorize('%GFound CiviCRM database version:%n %Y%s%n'), $db_version));
    if (version_compare($code_version, $db_version) == 0) {
      WP_CLI::success(sprintf('You are already upgraded to CiviCRM %s', $code_version));
      WP_CLI::halt(0);
    }

    // Get flags.
    $dry_run = \WP_CLI\Utils\get_flag_value($assoc_args, 'dry-run', FALSE);
    $retry = \WP_CLI\Utils\get_flag_value($assoc_args, 'retry', FALSE);
    $skip = \WP_CLI\Utils\get_flag_value($assoc_args, 'skip', FALSE);
    $step = \WP_CLI\Utils\get_flag_value($assoc_args, 'step', FALSE);
    $first_try = (empty($retry) && empty($skip)) ? TRUE : FALSE;

    // Get verbosity.
    $verbose = \WP_CLI\Utils\get_flag_value($assoc_args, 'v', FALSE);
    $verbose_extra = \WP_CLI\Utils\get_flag_value($assoc_args, 'vv', FALSE);

    // When stepping, we need at least "verbose".
    if (!empty($step)) {
      if (empty($verbose_extra) && empty($verbose)) {
        $verbose = TRUE;
      }
    }

    // Bail if incomplete upgrade.
    if ($first_try && FALSE !== stripos($db_version, 'upgrade')) {
      WP_CLI::error('Cannot begin upgrade: The database indicates that an incomplete upgrade is pending. If you would like to resume, use --retry or --skip.');
    }

    // Bootstrap upgrader.
    $upgrade = new CRM_Upgrade_Form();
    $error = $upgrade->checkUpgradeableVersion($db_version, $code_version);
    if (!empty($error)) {
      WP_CLI::error($error);
    }

    // Check pre-upgrade messages.
    if ($first_try) {
      WP_CLI::log(WP_CLI::colorize('%gChecking pre-upgrade messages.%n'));
      $preUpgradeMessage = NULL;
      $upgrade->setPreUpgradeMessage($preUpgradeMessage, $db_version, $code_version);
      if ($preUpgradeMessage) {
        WP_CLI::log(CRM_Utils_String::htmlToText($preUpgradeMessage));
        WP_CLI::confirm(WP_CLI::colorize('%GDo you want to continue?%n'), $assoc_args);
      }
      else {
        WP_CLI::log('(No messages)');
      }
    }

    // Why is dropTriggers() hard-coded? Can't we just enqueue this as part of buildQueue()?
    if ($first_try) {
      WP_CLI::log(WP_CLI::colorize('%gDropping SQL triggers.%n'));
      if (empty($dry_run)) {
        CRM_Core_DAO::dropTriggers();
      }
    }

    // Let's create a file for storing upgrade messages.
    $post_upgrade_message_file = CRM_Utils_File::tempnam('civicrm-post-upgrade');
    //WP_CLI::log(sprintf('Created upgrade message file: %s', $post_upgrade_message_file));

    // Build the queue.
    if ($first_try) {
      WP_CLI::log(WP_CLI::colorize('%gPreparing upgrade.%n'));
      $queue = CRM_Upgrade_Form::buildQueue($db_version, $code_version, $post_upgrade_message_file);
      // Sanity check - only SQL queues can be resumed.
      if (!($queue instanceof CRM_Queue_Queue_Sql)) {
        WP_CLI::error('The "upgrade-db" command only supports SQL-based queues.');
      }
    }
    else {
      WP_CLI::log(WP_CLI::colorize('%Resuming upgrade.%n'));
      $queue = CRM_Queue_Service::singleton()->load([
        'name' => CRM_Upgrade_Form::QUEUE_NAME,
        'type' => 'Sql',
      ]);
      if ($skip) {
        $item = $queue->stealItem();
        if (!empty($item->data->title)) {
          WP_CLI::log(sprintf('Skip task: %s', $item->data->title));
          $queue->deleteItem($item);
        }
      }
    }

    // Start the upgrade.
    WP_CLI::log(WP_CLI::colorize('%gExecuting upgrade.%n'));
    set_time_limit(0);

    // Mimic what "Console Queue Runner" does.
    $task_context = new CRM_Queue_TaskContext();
    $task_context->queue = $queue;

    // Maybe suppress Task Context logger output.
    if (empty($verbose_extra) && empty($verbose)) {
      $task_context->log = new class {

        public function info($param) {}

      };
    }
    else {
      $task_context->log = \Log::singleton('display');
    }

    while ($queue->numberOfItems()) {

      // In case we're retrying a failed job.
      $item = $queue->stealItem();
      $task = $item->data;

      // Feedback.
      if (!empty($verbose_extra)) {
        $feedback = self::format_task_callback($task);
        WP_CLI::log(WP_CLI::colorize('%g' . $task->title . '%n') . ' ' . WP_CLI::colorize($feedback));
      }
      elseif (!empty($verbose)) {
        WP_CLI::log(WP_CLI::colorize('%g' . $task->title . '%n'));
      }
      else {
        echo '.';
      }

      // Get action.
      $action = 'y';
      if (!empty($step)) {
        fwrite(STDOUT, 'Execute this step?' . ' [ y=yes / s=skip / a=abort ] ');
        $action = strtolower(trim(fgets(STDIN)));
      }

      // Bail if skip action is "abort".
      if ($action === 'a') {
        WP_CLI::halt(1);
      }

      // Run the task when action is "yes".
      if ($action === 'y' && empty($dry_run)) {
        try {
          $success = $task->run($task_context);
          if (!$success) {
            WP_CLI::error('Task returned false');
          }
        }
        catch (\Exception $e) {
          // WISHLIST: For interactive mode, perhaps allow retry/skip?
          WP_CLI::error(sprintf('Error executing task "%s"', $task->title));
        }
      }

      $queue->deleteItem($item);

    }

    // End feedback.
    if (empty($verbose_extra) && empty($verbose)) {
      echo "\n";
    }

    WP_CLI::log(WP_CLI::colorize('%gFinishing upgrade.%n'));
    if (empty($dry_run)) {
      CRM_Upgrade_Form::doFinish();
    }

    WP_CLI::log(sprintf(WP_CLI::colorize('%GUpgrade to%n %Y%s%n %Gcompleted.%n'), $code_version));

    if (version_compare($code_version, '5.26.alpha', '<')) {
      // Work-around for bugs like dev/core#1713.
      WP_CLI::log(WP_CLI::colorize('%GDetected CiviCRM 5.25 or earlier. Force flush.%n'));
      if (empty($dry_run)) {
        \Civi\Cv\Util\Cv::passthru('flush');
      }
    }

    WP_CLI::log(WP_CLI::colorize('%GChecking post-upgrade messages.%n'));
    $message = file_get_contents($post_upgrade_message_file);
    if ($message) {
      WP_CLI::log(CRM_Utils_String::htmlToText($message));
    }
    else {
      WP_CLI::log('(No messages)');
    }

    // Remove file for storing upgrade messages.
    unlink($post_upgrade_message_file);

    WP_CLI::log(WP_CLI::colorize('%GHave a nice day.%n'));

  }

  /**
   * Format the task for when run with extra verbosity.
   *
   * This method re-builds the task arguments because some of them may themselves be arrays.
   *
   * @since 1.0.0
   *
   * @param CRM_Queue_Task $task The CiviCRM task object.
   * @return string $task The CiviCRM task object.
   */
  private static function format_task_callback($task) {

    $callback_info = implode('::', (array) $task->callback);
    $args_info = self::implode_recursive((array) $task->arguments);

    // Build string with colorization tokens.
    $feedback = '%y' . $callback_info . '(' . $args_info . '%n)';

    return $feedback;

  }

  /**
   * Reset paths to correct config settings.
   *
   * This command can be useful when the CiviCRM site has been cloned or migrated.
   *
   * ## OPTIONS
   *
   * ## EXAMPLES
   *
   *     $ wp civicrm core update-cfg
   *
   * @subcommand update-cfg
   *
   * @since 1.0.0
   *
   * @param array $args The WP-CLI positional arguments.
   * @param array $assoc_args The WP-CLI associative arguments.
   */
  public function update_cfg($args, $assoc_args) {

    // Bootstrap CiviCRM.
    $this->check_dependencies();
    civicrm_initialize();

    $default_values = [];
    $states = ['old', 'new'];

    for ($i = 1; $i <= 3; $i++) {
      foreach ($states as $state) {
        $name = "{$state}Val_{$i}";
        $value = \WP_CLI\Utils\get_flag_value($name, NULL);
        if ($value) {
          $default_values[$name] = $value;
        }
      }
    }

    $webserver_user = $this->getWebServerUser();
    $webserver_group = $this->getWebServerGroup();

    require_once 'CRM/Core/I18n.php';
    require_once 'CRM/Core/BAO/ConfigSetting.php';
    $result = CRM_Core_BAO_ConfigSetting::doSiteMove($default_values);

    if ($result) {

      // Attempt to preserve webserver ownership of templates_c, civicrm/upload.
      if ($webserver_user && $webserver_group) {
        $upload_dir = wp_upload_dir();
        $civicrm_files_dir = $upload_dir['basedir'] . DIRECTORY_SEPARATOR . 'civicrm' . DIRECTORY_SEPARATOR;
        system(sprintf('chown -R %s:%s %s/templates_c', $webserver_user, $webserver_group, $civicrm_files_dir));
        system(sprintf('chown -R %s:%s %s/upload', $webserver_user, $webserver_group, $civicrm_files_dir));
      }

      WP_CLI::success('Config successfully updated.');

    }
    else {
      WP_CLI::error('Config update failed.');
    }

  }

  /**
   * Get the user the web server runs as - used to preserve file permissions on
   * templates_c, civicrm/upload etc when running as root. This is not a very
   * good check, but is good enough for what we want to do, which is to preserve
   * file permissions.
   *
   * @since 1.0.0
   *
   * @return string The user which owns templates_c. Empty string if not found.
   */
  private function getWebServerUser() {

    $plugins_dir_root = WP_PLUGIN_DIR;
    $upload_dir = wp_upload_dir();
    $tpl_path = $upload_dir['basedir'] . DIRECTORY_SEPARATOR . 'civicrm' . DIRECTORY_SEPARATOR . 'templates_c';
    $legacy_tpl_path = $plugins_dir_root . '/files/civicrm/templates_c';

    if (is_dir($legacy_tpl_path)) {
      $owner = posix_getpwuid(fileowner($legacy_tpl_path));
      if (isset($owner['name'])) {
        return $owner['name'];
      }
    }
    elseif (is_dir($tpl_path)) {
      $owner = posix_getpwuid(fileowner($tpl_path));
      if (isset($owner['name'])) {
        return $owner['name'];
      }
    }

    return '';

  }

  /**
   * Get the group the webserver runs as - as above, but for group.
   *
   * @since 1.0.0
   *
   * @return string The group the webserver runs as. Empty string if not found.
   */
  private function getWebServerGroup() {

    $plugins_dir_root = WP_PLUGIN_DIR;
    $upload_dir = wp_upload_dir();
    $tpl_path = $upload_dir['basedir'] . DIRECTORY_SEPARATOR . 'civicrm' . DIRECTORY_SEPARATOR . 'templates_c';
    $legacy_tpl_path = $plugins_dir_root . '/files/civicrm/templates_c';

    if (is_dir($legacy_tpl_path)) {
      $group = posix_getgrgid(filegroup($legacy_tpl_path));
      if (isset($group['name'])) {
        return $group['name'];
      }
    }
    elseif (is_dir($tpl_path)) {
      $group = posix_getgrgid(filegroup($tpl_path));
      if (isset($group['name'])) {
        return $group['name'];
      }
    }

    return '';

  }

  /**
   * Restore the CiviCRM plugin files and database.
   *
   * ## EXAMPLES
   *
   *     $ wp civicrm core restore
   *
   * @since 1.0.0
   *
   * @param array $args The WP-CLI positional arguments.
   * @param array $assoc_args The WP-CLI associative arguments.
   */
  public function restore($args, $assoc_args) {

    // Bootstrap CiviCRM.
    $this->check_dependencies();
    civicrm_initialize();

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

  /**
   * Checks for a CiviCRM version or matching localization archive.
   *
   * ## OPTIONS
   *
   * [--version=<version>]
   * : Specify the version to check. Accepts a version number, 'stable', 'rc' or 'nightly'.
   *
   * [--l10n]
   * : Get the localization file data for the specified version.
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
   * ## EXAMPLES
   *
   *     # Check for a stable version of CiviCRM
   *     $ wp civicrm core check-version --version=5.17.2
   *     +-----------+---------+-------------------------------------------------------------------------------------------+
   *     | Package   | Version | Package URL                                                                               |
   *     +-----------+---------+-------------------------------------------------------------------------------------------+
   *     | WordPress | 5.17.2  | https://storage.googleapis.com/civicrm/civicrm-stable/5.17.2/civicrm-5.17.2-wordpress.zip |
   *     | L10n      | 5.17.2  | https://storage.googleapis.com/civicrm/civicrm-stable/5.17.2/civicrm-5.17.2-l10n.tar.gz   |
   *     +-----------+---------+-------------------------------------------------------------------------------------------+
   *
   *     # Get the URL for a stable version of CiviCRM
   *     $ wp civicrm core check-version --version=5.17.2 --format=url
   *     https://storage.googleapis.com/civicrm/civicrm-stable/5.17.2/civicrm-5.17.2-wordpress.zip
   *
   *     # Get the URL for a stable version of the CiviCRM localisation archive
   *     $ wp civicrm core check-version --version=5.17.2 --format=url --l10n
   *     https://storage.googleapis.com/civicrm/civicrm-stable/5.17.2/civicrm-5.17.2-l10n.tar.gz
   *
   *     # Get the JSON-formatted data for a stable version of CiviCRM
   *     $ wp civicrm core check-version --version=5.17.2 --format=json
   *     {"version":"5.17.2","tar":{"L10n":"civicrm-stable\/5.17.2\/civicrm-5.17.2-l10n.tar.gz","WordPress":"civicrm-stable\/5.17.2\/civicrm-5.17.2-wordpress.zip"}}
   *
   *     # Get the latest nightly version of CiviCRM
   *     $ wp civicrm core check-version --version=nightly --format=version
   *     5.59.alpha1
   *
   * @subcommand check-version
   *
   * @since 1.0.0
   *
   * @param array $args The WP-CLI positional arguments.
   * @param array $assoc_args The WP-CLI associative arguments.
   */
  public function check_version($args, $assoc_args) {

    // Grab associative arguments.
    $version = (string) \WP_CLI\Utils\get_flag_value($assoc_args, 'version', 'stable');
    $l10n = (bool) \WP_CLI\Utils\get_flag_value($assoc_args, 'l10n', FALSE);
    $format = (string) \WP_CLI\Utils\get_flag_value($assoc_args, 'format', 'table');

    // Pass to "check-update" for "stable", "rc" or "nightly".
    if (in_array($version, ['stable', 'rc', 'nightly'])) {
      $options = ['launch' => FALSE, 'return' => FALSE];
      $command = 'civicrm core check-update --version=' . $version . ' --format=' . $format . (empty($l10n) ? '' : ' --l10n');
      WP_CLI::runcommand($command, $options);
      return;
    }

    // Check for valid release.
    $versions = $this->releases_get();
    if (!in_array($version, $versions)) {
      WP_CLI::error(sprintf(WP_CLI::colorize('Version %Y%s%n is not a valid CiviCRM version.'), $version));
    }

    // Get the release data.
    $data = $this->release_data_get($version);

    switch ($format) {

      // URL-only output.
      case 'url':
        if ($l10n) {
          echo $this->google_download_url . $data['L10n'] . "\n";
        }
        else {
          echo $this->google_download_url . $data['WordPress'] . "\n";
        }
        break;

      // Version-only output.
      case 'version':
        echo $version . "\n";
        break;

      // Display output as json.
      case 'json':
        // Use a similar format to the Version Check API.
        $info = [
          'version' => $version,
          'tar' => $data,
        ];
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
        $fields = ['Package', 'Version', 'Package URL'];
        $rows[] = [
          'Package' => 'WordPress',
          'Version' => $version,
          'Package URL' => $this->google_download_url . $data['WordPress'],
        ];
        $rows[] = [
          'Package'  => 'L10n',
          'Version' => $version,
          'Package URL' => $this->google_download_url . $data['L10n'],
        ];

        // Display the rows.
        $args = ['format' => $format];
        $formatter = new \WP_CLI\Formatter($args, $fields);
        $formatter->display_items($rows);

    }

  }

  /**
   * Gets the array of CiviCRM stable release versions.
   *
   * @since 1.0.0
   *
   * @return array The array of CiviCRM stable release versions.
   */
  private function releases_get() {

    // Get all release versions.
    $url = $this->google_url . '&' . $this->google_prefix_stable . '&maxResults=1000';
    $result = $this->json_get_request($url);
    if (empty($result['prefixes'])) {
      return [];
    }

    // Strip out all but the version.
    array_walk($result['prefixes'], function(&$item) {
      $item = trim(str_replace('civicrm-stable/', '', $item));
      $item = trim(str_replace('/', '', $item));
    });

    // Sort by version.
    usort($result['prefixes'], 'version_compare');

    return $result['prefixes'];

  }

  /**
   * Gets the array of CiviCRM release data.
   *
   * @since 1.0.0
   *
   * @param string The CiviCRM release.
   * @return array The array of CiviCRM release data.
   */
  private function release_data_get($release) {

    // Get the release data.
    $url = $this->google_url . '&' . $this->google_prefix_stable . $release . '/';
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

  /**
   * Checks for CiviCRM updates via Version Check API.
   *
   * ## OPTIONS
   *
   * [--version=<version>]
   * : Specify the version to get.
   * ---
   * default: stable
   * options:
   *   - nightly
   *   - rc
   *   - stable
   * ---
   *
   * [--l10n]
   * : Get the localization file data for the specified version.
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
   * ## EXAMPLES
   *
   *     # Check for the latest stable version of CiviCRM
   *     $ wp civicrm core check-update
   *     +-----------+---------+-------------------------------------------------------------------------------------------+
   *     | Package   | Version | Package URL                                                                               |
   *     +-----------+---------+-------------------------------------------------------------------------------------------+
   *     | WordPress | 5.57.2  | https://storage.googleapis.com/civicrm/civicrm-stable/5.57.2/civicrm-5.57.2-wordpress.zip |
   *     | L10n      | 5.57.2  | https://storage.googleapis.com/civicrm/civicrm-stable/5.57.2/civicrm-5.57.2-l10n.tar.gz   |
   *     +-----------+---------+-------------------------------------------------------------------------------------------+
   *
   *     # Get the URL for the latest stable version of CiviCRM core
   *     $ wp civicrm core check-update --format=url
   *     https://storage.googleapis.com/civicrm/civicrm-stable/5.57.2/civicrm-5.57.2-wordpress.zip
   *
   *     # Get the URL for the latest stable version of CiviCRM localisation archive
   *     $ wp civicrm core check-update --format=url --l10n
   *     https://storage.googleapis.com/civicrm/civicrm-stable/5.57.2/civicrm-5.57.2-l10n.tar.gz
   *
   *     # Get the complete JSON-formatted data for the latest RC version of CiviCRM core
   *     $ wp civicrm core check-update --version=rc --format=json
   *     {"version":"5.58.beta1","rev":"5.58.beta1-202301260741" [...] "pretty":"Thu, 26 Jan 2023 07:41:00 +0000"}}
   *
   * @subcommand check-update
   *
   * @since 1.0.0
   *
   * @param array $args The WP-CLI positional arguments.
   * @param array $assoc_args The WP-CLI associative arguments.
   */
  public function check_update($args, $assoc_args) {

    // Grab associative arguments.
    $version = (string) \WP_CLI\Utils\get_flag_value($assoc_args, 'version', 'stable');
    $l10n = (bool) \WP_CLI\Utils\get_flag_value($assoc_args, 'l10n', FALSE);
    $format = (string) \WP_CLI\Utils\get_flag_value($assoc_args, 'format', 'table');

    // Look up the data.
    $url = $this->upgrade_url . '?stability=' . $version;
    $response = $this->json_get_response($url);

    // Try and decode response.
    $lookup = json_decode($response, TRUE);
    if (JSON_ERROR_NONE !== json_last_error()) {
      WP_CLI::error(sprintf(WP_CLI::colorize('Failed to decode JSON: %Y%s.%n'), json_last_error_msg()));
    }

    // Sanity checks.
    if (empty($lookup)) {
      WP_CLI::error(sprintf(WP_CLI::colorize('Version not found at: %Y%s%n'), $url));
    }
    if (empty($lookup['tar']['WordPress'])) {
      WP_CLI::error(sprintf(WP_CLI::colorize('No WordPress version found at: %Y%s%n'), $url));
    }

    switch ($format) {

      // URL-only output.
      case 'url':
        if ($l10n) {
          echo $lookup['tar']['L10n'] . "\n";
        }
        else {
          echo $lookup['tar']['WordPress'] . "\n";
        }
        break;

      // Version-only output.
      case 'version':
        echo $lookup['version'] . "\n";
        break;

      // Display output as json.
      case 'json':
        echo $response . "\n";
        break;

      // Display output as table (default).
      case 'table':
      default:
        // Build the rows.
        $rows = [];
        $fields = ['Package', 'Version', 'Package URL'];
        $rows[] = [
          'Package' => 'WordPress',
          'Version' => $lookup['version'],
          'Package URL' => $lookup['tar']['WordPress'],
        ];
        $rows[] = [
          'Package' => 'L10n',
          'Version' => $lookup['version'],
          'Package URL' => $lookup['tar']['L10n'],
        ];

        // Display the rows.
        $args = ['format' => $format];
        $formatter = new \WP_CLI\Formatter($args, $fields);
        $formatter->display_items($rows);

    }

  }

}
