<?php
/**
 * WP-CLI port of drush-civicrm integration.
 *
 * @see https://github.com/christianwach/wp-cli-civicrm
 *
 * @package WP_CLI_CiviCRM
 */

if (!defined('CIVICRM_WPCLI_LOADED')) {
  define('CIVICRM_WPCLI_LOADED', 1);

  /**
   * WP-CLI port of drush-civicrm integration.
   *
   * @since 4.5
   */
  class CiviCRM_Command extends WP_CLI_Command {

    private $args;
    private $assoc_args;

    /**
     * Manage CiviCRM through the command-line.
     *
     * wp civicrm api
     * ==============
     * Command for accessing the CiviCRM API. Syntax is identical to `drush cvap`.
     *
     * wp civicrm cache-clear
     * ======================
     * Command for accessing clearing cache.  Equivilant of running `civicrm/admin/setting/updateConfigBackend&reset=1`.
     *
     * wp civicrm enable-debug
     * =======================
     * Command for to turn debug on.
     *
     * wp civicrm disable-debug
     * ========================
     * Command for to turn debug off.
     *
     * wp civicrm member-records
     * =========================
     * Run the CiviMember UpdateMembershipRecord cron (civicrm member-records).
     *
     * wp civicrm pipe <connection-flags>
     * ==================
     * Start a Civi::pipe session (JSON-RPC 2.0)
     * See https://docs.civicrm.org/dev/en/latest/framework/pipe#flags
     *
     * wp civicrm process-mail-queue
     * =============================
     * Process pending CiviMail mailing jobs.
     * Example:
     * wp civicrm process-mail-queue -u admin
     *
     * wp civicrm rest
     * ===============
     * Rest interface for accessing CiviCRM APIs. It can return xml or json formatted data.
     *
     * wp civicrm restore
     * ==================
     * Restore CiviCRM codebase and database back from the specified backup directory.
     *
     * wp civicrm sql-conf
     * ===================
     * Show CiviCRM database connection details.
     *
     * wp civicrm sql-connect
     * ======================
     * A string which connects to the CiviCRM database.
     *
     * wp civicrm sql-cli
     * ==================
     * Quickly enter the mysql command line.
     *
     * wp civicrm sql-dump
     * ===================
     * Prints the whole CiviCRM database to STDOUT or save to a file.
     *
     * wp civicrm sql-query
     * ====================
     * Usage: wp civicrm sql-query <query> <options>...
     * <query> is a SQL statement, which can alternatively be passed via STDIN. Any additional arguments are passed to the mysql command directly.
     *
     * wp civicrm update-cfg
     * =====================
     * Update config_backend to correct config settings, especially when the CiviCRM site has been cloned or migrated.
     *
     * wp civicrm upgrade
     * ==================
     * Take backups, replace CiviCRM codebase with new specified tarfile and upgrade database by executing the CiviCRM upgrade process - civicrm/upgrade?reset=1. Use civicrm-restore to revert to previous state in case anything goes wrong.
     *
     * wp civicrm upgrade-db
     * =====================
     * Run civicrm/upgrade?reset=1 just as a web browser would.
     * Options:
     * --dry-run           Preview the list of upgrade tasks.
     * --retry             Resume a failed upgrade, retrying the last step.
     * --skip              Resume a failed upgrade, skipping the last step.
     * --step              Run the upgrade queue in steps, pausing before each step.
     *
     * wp civicrm install
     * ==================
     * Command for to install CiviCRM.  The install command requires that you have downloaded a tarball or zip file first.
     * Options:
     * --dbhost            MySQL host for your WordPress/CiviCRM database. Defaults to localhost.
     * --dbname            MySQL database name of your WordPress/CiviCRM database.
     * --dbpass            MySQL password for your WordPress/CiviCRM database.
     * --dbuser            MySQL username for your WordPress/CiviCRM database.
     * --lang              Default language to use for installation.
     * --langtarfile       Path to your l10n tar.gz file.
     * --site_url          Base Url for your WordPress/CiviCRM website without http (e.g. mysite.com)
     * --ssl               Using ssl for your WordPress/CiviCRM website if set to on (e.g. --ssl=on)
     * --tarfile           Path to your CiviCRM tar.gz file.
     * --zipfile           Path to your CiviCRM zip file.
     *
     */
    public function __invoke($args, $assoc_args) {

      $this->args       = $args;
      $this->assoc_args = $assoc_args;

      // Define command router.
      $command_router = [
        'api'                => 'api',
        'cache-clear'        => 'cacheClear',
        'enable-debug'       => 'enableDebug',
        'disable-debug'      => 'disableDebug',
        'install'            => 'install',
        'member-records'     => 'memberRecords',
        'pipe'               => 'pipe',
        'process-mail-queue' => 'processMailQueue',
        'rest'               => 'rest',
        'restore'            => 'restore',
        'sql-cli'            => 'sqlCLI',
        'sql-conf'           => 'sqlConf',
        'sql-connect'        => 'sqlConnect',
        'sql-dump'           => 'sqlDump',
        'sql-query'          => 'sqlQuery',
        'update-cfg'         => 'updateConfig',
        'upgrade'            => 'upgrade',
        'upgrade-db'         => 'upgradeDB',
      ];

      // Get the command.
      $command = array_shift($args);

      // Allow help to pass.
      if ('help' === $command) {
        return;
      }

      // Check for existence of CiviCRM (except for command 'install').
      if (!function_exists('civicrm_initialize') && 'install' != $command) {
        return WP_CLI::error('Unable to find CiviCRM install.');
      }

      // Check existence of router entry / handler method.
      if (!isset($command_router[$command]) || !method_exists($this, $command_router[$command])) {
        return WP_CLI::error(sprintf('Unrecognized command: %s', $command));
      }

      // Run command.
      return $this->{$command_router[$command]}();

    }

    /**
     * Implementation of command 'api'.
     *
     * @since 4.5
     */
    private function api() {

      $defaults = ['version' => 3];

      array_shift($this->args);
      list($entity, $action) = explode('.', $this->args[0]);
      array_shift($this->args);

      //  Parse params.
      $format = $this->getOption('in', 'args');
      switch ($format) {

        //  Input params supplied via args.
        case 'args':
          $params = $defaults;
          foreach ($this->args as $arg) {
            preg_match('/^([^=]+)=(.*)$/', $arg, $matches);
            $params[$matches[1]] = $matches[2];
          }
          break;

        //  Input params supplied via json.
        case 'json':
          $json   = stream_get_contents(STDIN);
          $params = (empty($json) ? $defaults : array_merge($defaults, json_decode($json, TRUE)));
          break;

        default:
          WP_CLI::error(sprintf('Unknown format: %s', $format));
          break;

      }

      civicrm_initialize();

      // CRM-18062: Set CiviCRM timezone if any.
      $wp_base_timezone = date_default_timezone_get();
      $wp_user_timezone = $this->getOption('timezone', get_option('timezone_string'));
      if ($wp_user_timezone) {
        date_default_timezone_set($wp_user_timezone);
        CRM_Core_Config::singleton()->userSystem->setMySQLTimeZone();
      }

      $result = civicrm_api($entity, $action, $params);

      // Restore WordPress's timezone.
      if ($wp_base_timezone) {
        date_default_timezone_set($wp_base_timezone);
      }

      switch ($this->getOption('out', 'pretty')) {

        // Pretty-print output (default).
        case 'pretty':
          WP_CLI::line(print_r($result, TRUE));
          break;

        // Display output as json.
        case 'json':
          WP_CLI::line(json_encode($result));
          break;

        default:
          return WP_CLI::error(sprintf('Unknown format: %s', $format));

      }

    }

    /**
     * Implementation of command 'cache-clear'.
     *
     * @since 4.5
     */
    private function cacheClear() {

      civicrm_initialize();
      require_once 'CRM/Core/Config.php';
      $config = CRM_Core_Config::singleton();

      // Clear db caching.
      $config->clearDBCache();

      // Also cleanup the templates_c directory.
      $config->cleanup(1, FALSE);

      // Also cleanup the session object.
      $session = CRM_Core_Session::singleton();
      $session->reset(1);

    }

    /**
     * Implementation of command 'enable-debug'.
     *
     * @since 4.5
     */
    private function enableDebug() {
      civicrm_initialize();
      Civi::settings()->add([
        'debug_enabled' => 1,
        'backtrace' => 1,
      ]);
      WP_CLI::success('Debug setting enabled.');
    }

    /**
     * Implementation of command 'disable-debug'.
     *
     * @since 4.7
     */
    private function disableDebug() {
      civicrm_initialize();
      Civi::settings()->add([
        'debug_enabled' => 0,
        'backtrace' => 0,
      ]);
      WP_CLI::success('Debug setting disabled.');
    }

    /**
     * Implementation of command 'install'.
     *
     * @since 4.5
     */
    private function install() {

      if ('on' === $this->getOption('ssl', FALSE)) {
        $_SERVER['HTTPS'] = 'on';
      }

      // Identify the destination.
      if ($plugin_path = $this->getOption('destination', FALSE)) {
        $plugin_path = ABSPATH . $plugin_path;
      }
      else {
        $plugin_path = WP_PLUGIN_DIR . '/civicrm';
      }

      global $crmPath;
      $crmPath = "$plugin_path/civicrm";
      $crm_files_present = is_dir($crmPath);

      // Validate install parameters.
      if (!$dbuser = $this->getOption('dbuser', FALSE)) {
        return WP_CLI::error('CiviCRM database username not specified.');
      }
      if (!$dbpass = $this->getOption('dbpass', FALSE)) {
        return WP_CLI::error('CiviCRM database password not specified.');
      }
      if (!$dbhost = $this->getOption('dbhost', FALSE)) {
        return WP_CLI::error('CiviCRM database host not specified.');
      }
      if (!$dbname = $this->getOption('dbname', FALSE)) {
        return WP_CLI::error('CiviCRM database name not specified.');
      }
      if ($lang = $this->getOption('lang', FALSE)) {
        $moPath = "$crmPath/l10n/$lang/LC_MESSAGES/civicrm.mo";

        if (!($langtarfile = $this->getOption('langtarfile', FALSE)) && !file_exists($moPath)) {
          return WP_CLI::error("Failed to find data for language ($lang). Please download valid language data with --langtarfile=<path/to/tarfile>.");
        }
      }

      // Extract the archive.
      if ($this->getOption('tarfile', FALSE)) {
        // Should probably never get to here as WordPress CiviCRM comes in a zip file.
        // Check anyway just in case that ever changes.
        if ($crm_files_present) {
          return WP_CLI::error('Existing CiviCRM found. No action taken.');
        }

        if (!$this->untar(dirname($plugin_path))) {
          return WP_CLI::error('Error extracting tarfile.');
        }
      }
      elseif ($this->getOption('zipfile', FALSE)) {
        if ($crm_files_present) {
          return WP_CLI::error('Existing CiviCRM found. No action taken.');
        }

        if (!$this->unzip(dirname($plugin_path))) {
          return WP_CLI::error('Error extracting zipfile.');
        }
      }
      elseif ($crm_files_present) {
        // Site is already extracted - which is how we're running this script.
        // We just need to run the installer.
      }
      else {
        return WP_CLI::error('No zipfile specified. Use "--zipfile=path/to/zipfile" or extract file ahead of time.');
      }

      // Include CiviCRM classloader - so that we can run `Civi\Setup`.
      $classLoaderPath = "$crmPath/CRM/Core/ClassLoader.php";

      if (!file_exists($classLoaderPath)) {
        return WP_CLI::error('Archive could not be unpacked or CiviCRM installer helper file is missing.');
      }

      if ($crm_files_present) {
        // We were using a directory that was already there.
        WP_CLI::success('Using installer files found on the site.');
      }
      else {
        // We must've just unpacked the archive because it wasn't there before.
        WP_CLI::success('Archive unpacked.');
      }

      if ($this->getOption('langtarfile', FALSE)) {
        if (!$this->untar($plugin_path, 'langtarfile')) {
          return WP_CLI::error('Error downloading langtarfile.');
        }
      }

      if (!empty($lang) && !file_exists($moPath)) {
        return WP_CLI::error("Failed to find data for language ($lang). Please download valid language data with \"--langtarfile=<path/to/tarfile>\".");
      }

      // Initialize civicrm-setup.
      @WP_CLI::run_command(['plugin', 'activate', 'civicrm'], []);
      require_once $classLoaderPath;
      CRM_Core_ClassLoader::singleton()->register();
      \Civi\Setup::assertProtocolCompatibility(1.0);
      \Civi\Setup::init(['cms' => 'WordPress', 'srcPath' => $crmPath]);
      $setup = \Civi\Setup::instance();
      $setup->getModel()->db = ['server' => $dbhost, 'username' => $dbuser, 'password' => $dbpass, 'database' => $dbname];
      $setup->getModel()->lang = (empty($lang) ? 'en_US' : $lang);
      if ($base_url = $this->getOption('site_url', FALSE)) {
        $ssl = $this->getOption('ssl', FALSE);
        $protocol = ('on' == $ssl ? 'https' : 'http');
        $base_url = $protocol . '://' . $base_url;
        if (substr($base_url, -1) != '/') {
          $base_url .= '/';
        }
        $setup->getModel()->cmsBaseUrl = $base_url;
      }

      // Check system requirements.
      $reqs = $setup->checkRequirements();
      array_map('WP_CLI::print_value', $this->formatRequirements(array_merge($reqs->getErrors(), $reqs->getWarnings())));
      if ($reqs->getErrors()) {
        WP_CLI::error(sprintf("Cannot install. Please check requirements and resolve errors.", count($reqs->getErrors()), count($reqs->getWarnings())));
      }

      $installed = $setup->checkInstalled();
      if ($installed->isSettingInstalled() || $installed->isDatabaseInstalled()) {
        WP_CLI::error("Cannot install. CiviCRM has already been installed.");
      }

      // Go time.
      $setup->installFiles();
      WP_CLI::success('CiviCRM data files initialized successfully.');
      $setup->installDatabase();
      WP_CLI::success('CiviCRM database loaded successfully.');
      WP_CLI::success('CiviCRM installed.');

    }

    /**
     * Format the requirements messages.
     *
     * @since 5.46
     *
     * @param array $messages The array of unformatted messages.
     * @return array $formatted The array of formatted messages.
     */
    private function formatRequirements(array $messages): array {
      $formatted = [];
      foreach ($messages as $message) {
        $formatted[] = sprintf("[%s] %s: %s", $message['severity'], $message['section'], $message['message']);
      }
      return array_unique($formatted);
    }

    /**
     * Implementation of command 'member-records'.
     *
     * @since 4.5
     */
    private function memberRecords() {

      civicrm_initialize();

      if (substr(CRM_Utils_System::version(), 0, 3) >= '4.3') {

        $job = new CRM_Core_JobManager();
        $job->executeJobByAction('job', 'process_membership');
        WP_CLI::success('Executed "process_membership" job.');

      }
      else {

        $_REQUEST['name'] = $this->getOption('civicrm_cron_username', NULL);
        $_REQUEST['pass'] = $this->getOption('civicrm_cron_password', NULL);
        $_REQUEST['key']  = $this->getOption('civicrm_sitekey', NULL);

        global $argv;
        $argv = [
          0 => 'drush',
          1 => '-u' . $_REQUEST['name'],
          2 => '-p' . $_REQUEST['pass'],
          3 => '-s' . $this->getOption('uri', FALSE),
        ];

        # if (!defined('CIVICRM_CONFDIR')) {
        # $plugins_dir = plugin_dir_path(__FILE__);
        #     define('CIVICRM_CONFDIR', $plugins_dir);
        # }

        include 'bin/UpdateMembershipRecord.php';

      }

    }

    /**
     * Implementation of command 'pipe'.
     *
     * @since 5.46
     */
    private function pipe() {

      civicrm_initialize();

      if (!is_callable(['Civi', 'pipe'])) {
        return WP_CLI::error('This version of CiviCRM does not include Civi::pipe() support.');
      }

      if (!empty($this->args[1])) {
        Civi::pipe($this->args[1]);
      }
      else {
        Civi::pipe();
      }

    }

    /**
     * Implementation of command 'process-mail-queue'.
     *
     * @since 4.5
     */
    private function processMailQueue() {

      civicrm_initialize();

      if (substr(CRM_Utils_System::version(), 0, 3) >= '4.3') {

        $job = new CRM_Core_JobManager();
        $job->executeJobByAction('job', 'process_mailing');
        WP_CLI::success("Executed 'process_mailing' job.");

      }
      else {

        $result = civicrm_api('Mailing', 'Process', ['version' => 3]);
        if ($result['is_error']) {
          WP_CLI::error($result['error_message']);
        }
      }

    }

    /**
     * Implementation of command 'rest'.
     *
     * @since 4.5
     */
    private function rest() {

      civicrm_initialize();

      if (!$query = $this->getOption('query', FALSE)) {
        return WP_CLI::error('query not specified.');
      }

      $query     = explode('&', $query);
      $_GET['q'] = array_shift($query);

      foreach ($query as $key_val) {
        list($key, $val) = explode('=', $key_val);
        $_REQUEST[$key]  = $val;
        $_GET[$key]      = $val;
      }

      require_once 'CRM/Utils/REST.php';
      $rest = new CRM_Utils_REST();

      require_once 'CRM/Core/Config.php';
      $config = CRM_Core_Config::singleton();

      global $civicrm_root;
      // Adding dummy script, since based on this api file path is computed.
      $_SERVER['SCRIPT_FILENAME'] = "$civicrm_root/extern/rest.php";

      if (isset($_GET['json']) && $_GET['json']) {
        header('Content-Type: text/javascript');
      }
      else {
        header('Content-Type: text/xml');
      }

      echo $rest->run($config);

    }

    /**
     * Implementation of command 'restore'.
     *
     * @since 4.5
     */
    private function restore() {

      // Validate.
      $restore_dir = $this->getOption('restore-dir', FALSE);
      $restore_dir = rtrim($restore_dir, '/');
      if (!$restore_dir) {
        return WP_CLI::error('"restore-dir" not specified.');
      }

      $sql_file = $restore_dir . '/civicrm.sql';
      if (!file_exists($sql_file)) {
        return WP_CLI::error('Could not locate "civicrm.sql" file in the restore directory.');
      }

      $code_dir = $restore_dir . '/civicrm';
      if (!is_dir($code_dir)) {
        return WP_CLI::error('Could not locate the CiviCRM directory inside "restore-dir".');
      }
      elseif (!file_exists("$code_dir/civicrm/civicrm-version.txt") && !file_exists("$code_dir/civicrm/civicrm-version.php")) {
        return WP_CLI::error('The CiviCRM directory inside "restore-dir" does not seem to be a valid CiviCRM codebase.');
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

      $restore_backup_dir = $this->getOption('backup-dir', ABSPATH . '../backup');
      $restore_backup_dir = rtrim($restore_backup_dir, '/');

      // Get confirmation from user.

      if (!defined('CIVICRM_DSN')) {
        WP_CLI::error('CIVICRM_DSN is not defined.');
      }

      $db_spec = DB::parseDSN(CIVICRM_DSN);
      WP_CLI::line('');
      WP_CLI::line('Process involves:');
      WP_CLI::line(sprintf("1. Restoring '\$restore-dir/civicrm' directory to '%s'.", $civicrm_root_base));
      WP_CLI::line(sprintf("2. Dropping and creating '%s' database.", $db_spec['database']));
      WP_CLI::line("3. Loading '\$restore-dir/civicrm.sql' file into the database.");
      WP_CLI::line('');
      WP_CLI::line(sprintf("Note: Before restoring, a backup will be taken in '%s' directory.", "$restore_backup_dir/plugins/restore"));
      WP_CLI::line('');

      WP_CLI::confirm('Do you really want to continue?');

      $restore_backup_dir .= '/plugins/restore/' . $date;

      if (!mkdir($restore_backup_dir, 0755, TRUE)) {
        return WP_CLI::error(sprintf('Failed to create directory: %s', $restore_backup_dir));
      }

      // 1. Backup and restore codebase.
      WP_CLI::line('Restoring CiviCRM codebase...');
      if (is_dir($project_path) && !rename($project_path, $restore_backup_dir . '/civicrm')) {
        return WP_CLI::error(sprintf("Failed to take backup for '%s' directory", $project_path));
      }

      if (!rename($code_dir, $project_path)) {
        return WP_CLI::error(sprintf("Failed to restore CiviCRM directory '%s' to '%s'", $code_dir, $project_path));
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
        return WP_CLI::error(sprintf('Could not drop database: %s', $db_spec['database']));
      }

      WP_CLI::success('Database dropped.');

      // Attempt to create new database.
      if (system($command . sprintf(' --execute="CREATE DATABASE %s"', $db_spec['database']))) {
        WP_CLI::error(sprintf('Could not create new database: %s', $db_spec['database']));
      }

      WP_CLI::success('Database created.');

      // 3. Restore database.
      WP_CLI::line('Loading "civicrm.sql" file from "restore-dir"...');
      system($command . ' ' . $db_spec['database'] . ' < ' . $sql_file);

      WP_CLI::success('Database restored.');

      WP_CLI::line('Clearing caches...');
      WP_CLI::run_command(['civicrm', 'cache-clear']);

      WP_CLI::success('Restore process completed.');

    }

    /**
     * Implementation of command 'sql-conf'.
     *
     * @since 4.5
     */
    private function sqlConf() {

      civicrm_initialize();
      if (!defined('CIVICRM_DSN')) {
        WP_CLI::error('CIVICRM_DSN is not defined.');
      }

      WP_CLI::line(print_r(DB::parseDSN(CIVICRM_DSN), TRUE));

    }

    /**
     * Implementation of command 'sql-connect'.
     *
     * @since 4.5
     */
    private function sqlConnect() {

      civicrm_initialize();
      if (!defined('CIVICRM_DSN')) {
        return WP_CLI::error('CIVICRM_DSN is not defined.');
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

      return WP_CLI::line($command);

    }

    /**
     * Implementation of command 'sql-dump'.
     *
     * @since 4.5
     */
    private function sqlDump() {

      // Bootstrap CiviCRM when we're not being called as part of an upgrade.
      if (!defined('CIVICRM_UPGRADE_ACTIVE')) {
        civicrm_initialize();
      }

      if (!defined('CIVICRM_DSN') && !defined('CIVICRM_OLD_DSN')) {
        WP_CLI::error('DSN is not defined.');
      }

      $dsn = self::parseDSN(defined('CIVICRM_DSN') ? CIVICRM_DSN : CIVICRM_OLD_DSN);

      $assoc_args       = $this->assoc_args;
      $stdout           = !isset($assoc_args['result-file']);
      $command          = "mysqldump --no-defaults --host={$dsn['hostspec']} --user={$dsn['username']} --password='{$dsn['password']}' %s";
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

      $escaped_command = call_user_func_array(
        '\WP_CLI\Utils\esc_cmd',
        array_merge(
          [$command],
          $command_esc_args
        )
      );

      \WP_CLI\Utils\run_mysql_command($escaped_command, $assoc_args);

      if (!$stdout) {
        WP_CLI::success(sprintf('Exported to %s', $assoc_args['result-file']));
      }

    }

    /**
     * Implementation of command 'sql-query'.
     *
     * @since 4.5
     */
    private function sqlQuery() {

      if (!isset($this->args[0])) {
        WP_CLI::error('No query specified.');
        return;
      }

      $query = $this->args[0];

      civicrm_initialize();
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

      \WP_CLI\Utils\run_mysql_command('mysql --no-defaults', $mysql_args);

    }

    /**
     * Implementation of command 'sql-cli'.
     *
     * @since 4.5
     */
    private function sqlCLI() {

      civicrm_initialize();
      if (!defined('CIVICRM_DSN')) {
        WP_CLI::error('CIVICRM_DSN is not defined.');
      }

      $dsn = DB::parseDSN(CIVICRM_DSN);

      $mysql_args = [
        'host'     => $dsn['hostspec'],
        'database' => $dsn['database'],
        'user'     => $dsn['username'],
        'password' => $dsn['password'],
      ];

      \WP_CLI\Utils\run_mysql_command('mysql --no-defaults', $mysql_args);

    }

    /**
     * Implementation of command 'update-cfg'.
     *
     * @since 4.5
     */
    private function updateConfig() {

      civicrm_initialize();

      $default_values = [];
      $states         = ['old', 'new'];

      for ($i = 1; $i <= 3; $i++) {
        foreach ($states as $state) {
          $name = "{$state}Val_{$i}";
          $value = $this->getOption($name, NULL);
          if ($value) {
            $default_values[$name] = $value;
          }
        }
      }

      $webserver_user  = $this->getWebServerUser();
      $webserver_group = $this->getWebServerGroup();

      require_once 'CRM/Core/I18n.php';
      require_once 'CRM/Core/BAO/ConfigSetting.php';
      $result = CRM_Core_BAO_ConfigSetting::doSiteMove($default_values);

      if ($result) {

        // Attempt to preserve webserver ownership of templates_c, civicrm/upload.
        if ($webserver_user && $webserver_group) {
          $upload_dir      = wp_upload_dir();
          $civicrm_files_dir      = $upload_dir['basedir'] . DIRECTORY_SEPARATOR . 'civicrm' . DIRECTORY_SEPARATOR;
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
     * Implementation of command 'upgrade'.
     *
     * @since 4.5
     */
    private function upgrade() {

      // TODO: Use wp-cli to download tarfile.
      // TODO: If tarfile is not specified, see if the code already exists and use that instead.
      if (!$this->getOption('tarfile', FALSE) && !$this->getOption('zipfile', FALSE)) {
        return WP_CLI::error('Must specify either --tarfile or --zipfile');
      }

      // FIXME: Throw error if tarfile is not in a valid format.
      if (!defined('CIVICRM_UPGRADE_ACTIVE')) {
        define('CIVICRM_UPGRADE_ACTIVE', 1);
      }

      // Build paths to legacy and standard settings files.
      if (defined('CIVICRM_PLUGIN_DIR')) {
        $legacy_settings_file = CIVICRM_PLUGIN_DIR . '/civicrm.settings.php';
      }
      else {
        $legacy_settings_file = WP_PLUGIN_DIR . DIRECTORY_SEPARATOR . 'civicrm' . DIRECTORY_SEPARATOR . 'civicrm.settings.php';
      }
      $upload_dir = wp_upload_dir();
      $settings_file = $upload_dir['basedir'] . DIRECTORY_SEPARATOR . 'civicrm' . DIRECTORY_SEPARATOR . 'civicrm.settings.php';
      if (!file_exists($legacy_settings_file) && !file_exists($settings_file)) {
        return WP_CLI::error(sprintf('Unable to locate settings file at "%s" or at "%s"', $legacy_settings_file, $settings_file));
      }

      /*
       * We don't want to require "civicrm.settings.php" here, because:
       *
       * a) This is the old environment we're going to replace.
       * b) upgrade-db needs to bootstrap the new environment, so requiring the file
       *    now will create multiple inclusion problems later on.
       *
       * However, all we're really after is $civicrm_root and CIVICRM_DSN, so we're going to
       * pull out the lines we need using a regex and run them - yes, it's pretty silly.
       * Don't try this at home, kids.
       */

      // Prefer the legacy settings file if present.
      if (file_exists($legacy_settings_file)) {
        $settings = file_get_contents($legacy_settings_file);
      }
      elseif (file_exists($settings_file)) {
        $settings = file_get_contents($settings_file);
      }
      if (empty($settings)) {
        return WP_CLI::error('Unable to read settings from civicrm.settings.php');
      }

      // Parse the content of the settings file.
      $settings = str_replace("\r", '', $settings);
      $settings = explode("\n", $settings);

      // Try and retrieve $civicrm_root.
      if ($civicrm_root_code = reset(preg_grep('/^\s*\$civicrm_root\s*=.*$/', $settings))) {
        // phpcs:disable
        eval($civicrm_root_code);
        // phpcs:enable
      }
      else {
        return WP_CLI::error('Unable to read $civicrm_root from civicrm.settings.php');
      }
      if (empty($civicrm_root)) {
        return WP_CLI::error('Unable to set $civicrm_root.');
      }

      // Try and retrieve CIVICRM_DSN.
      if ($civicrm_dsn_code = reset(preg_grep('/^\s*define.*CIVICRM_DSN.*$/', $settings))) {
        $civicrm_dsn_code = str_replace('CIVICRM_DSN', 'CIVICRM_OLD_DSN', $civicrm_dsn_code);
        // phpcs:disable
        eval($civicrm_dsn_code);
        // phpcs:enable
      }
      else {
        return WP_CLI::error('Unable to read CIVICRM_DSN from civicrm.settings.php');
      }
      if (!defined('CIVICRM_OLD_DSN')) {
        return WP_CLI::error('Unable to set CIVICRM_OLD_DSN.');
      }

      $date = date('YmdHis');
      $backup_file = 'civicrm';

      $basepath = explode('/', $civicrm_root);

      // Maybe remove empty item at end of array.
      if (!end($basepath)) {
        array_pop($basepath);
      }

      // Construct path to CiviCRM plugin.
      array_pop($basepath);
      // TODO: We already know this - it's `CIVICRM_PLUGIN_DIR`.
      $project_path = implode('/', $basepath) . '/';

      // Construct path to plugins directory.
      array_pop($basepath);
      // TODO: We already know this - it's `WP_PLUGIN_DIR`.
      $plugin_path = implode('/', $basepath) . '/';

      // Default backup location is one up from `ABSPATH`.
      $backup_dir = $this->getOption('backup-dir', ABSPATH . '../backup');
      $backup_dir = rtrim($backup_dir, '/');

      // Feedback.
      WP_CLI::line();
      WP_CLI::line('The upgrade process involves:');
      WP_CLI::line(sprintf('1. Backing up current CiviCRM code as => %s', "$backup_dir/plugins/$date/$backup_file"));
      WP_CLI::line(sprintf('2. Backing up database as => %s', "$backup_dir/plugins/$date/$backup_file.sql"));
      WP_CLI::line(sprintf('3. Unpacking tarfile to => %s', $plugin_path));
      WP_CLI::line('4. Executing "civicrm/upgrade?reset=1" just as a browser would.');
      WP_CLI::line();

      WP_CLI::confirm('Do you really want to continue?');

      // Begin upgrade.
      $backup_dir .= '/plugins/' . $date;
      if (!mkdir($backup_dir, 0755, TRUE)) {
        return WP_CLI::error(sprintf('Failed to create directory: %s', $backup_dir));
      }

      // Move current plugin directory to backup.
      $backup_target = $backup_dir . '/' . $backup_file;
      if (!rename($project_path, $backup_target)) {
        return WP_CLI::error(sprintf('Failed to backup CiviCRM project directory %s to %s', $project_path, $backup_target));
      }

      WP_CLI::line();
      WP_CLI::success('1. Code backed up.');

      // Export database.
      WP_CLI::run_command(
        ['civicrm', 'sql-dump'],
        ['result-file' => $backup_target . '.sql']
      );

      WP_CLI::success('2. Database backed up.');

      // Decompress.
      if ($this->getOption('tarfile', FALSE)) {
        // Should probably never get to here, because WordPress CiviCRM comes in a zip file.
        if (!$this->untar($plugin_path)) {
          return WP_CLI::error('Error extracting tarfile');
        }
      }
      elseif ($this->getOption('zipfile', FALSE)) {
        if (!$this->unzip($plugin_path)) {
          return WP_CLI::error('Error extracting zipfile');
        }
      }
      else {
        return WP_CLI::error('No zipfile specified, use --zipfile=path/to/zipfile');
      }

      WP_CLI::success('3. Archive unpacked.');

      WP_CLI::line(sprintf('Copying civicrm.settings.php to %s.', $project_path));
      define('CIVICRM_SETTINGS_PATH', $project_path . 'civicrm.settings.php');

      /*
       * This tries to place a copy of the previous settings file in the plugin
       * directory. This is incorrect.
       *
       * This is only appropriate for installs where the settings file was in the
       * legacy location. What should happen is that the file ought to be copied
       * to the new "standard" location in `wp-content/uploads/civicrm/`. This may
       * not yet exist, however.
       */
      if (!copy($backup_dir . '/civicrm/civicrm.settings.php', CIVICRM_SETTINGS_PATH)) {
        return WP_CLI::error('Failed to copy file.');
      }

      WP_CLI::success('4. Settings file copied.');

      WP_CLI::run_command(['civicrm', 'upgrade-db'], []);

      WP_CLI::success('Process completed.');

    }

    /**
     * Implementation of command 'upgrade-db'.
     *
     * @since 4.5
     */
    private function upgradeDB() {

      civicrm_initialize();

      if (!defined('CIVICRM_UPGRADE_ACTIVE')) {
        define('CIVICRM_UPGRADE_ACTIVE', 1);
      }

      // Check whether an upgrade is necessary.
      $code_version = CRM_Utils_System::version();
      WP_CLI::line(sprintf('Found CiviCRM code version: %s', $code_version));
      $db_version = CRM_Core_BAO_Domain::version();
      WP_CLI::line(sprintf('Found CiviCRM database version: %s', $db_version));
      if (version_compare($code_version, $db_version) == 0) {
        return WP_CLI::success(sprintf('You are already upgraded to CiviCRM %s', $code_version));
      }

      // Get options.
      $dry_run = $this->getOption('dry-run', FALSE);
      $retry = $this->getOption('retry', FALSE);
      $skip = $this->getOption('skip', FALSE);
      $step = $this->getOption('step', FALSE);
      $first_try = (empty($retry) && empty($skip)) ? TRUE : FALSE;

      WP_CLI::line(sprintf('Dry Run: %s', $dry_run));
      WP_CLI::line(sprintf('Retry: %s', $retry));
      WP_CLI::line(sprintf('Skip: %s', $skip));
      WP_CLI::line(sprintf('Step: %s', $step));

      // Bail if incomplete upgrade.
      if ($first_try && FALSE !== stripos($db_version, 'upgrade')) {
        return WP_CLI::error('Cannot begin upgrade: The database indicates that an incomplete upgrade is pending. If you would like to resume, use --retry or --skip.');
      }

      // Bootstrap upgrader.
      $upgrade = new CRM_Upgrade_Form();
      $error = $upgrade->checkUpgradeableVersion($db_version, $code_version);
      if (!empty($error)) {
        return WP_CLI::error($error);
      }

      if ($first_try) {
        WP_CLI::line('Checking pre-upgrade messages.');
        $pre_upgrade_message = NULL;
        $upgrade->setPreUpgradeMessage($pre_upgrade_message, $db_version, $code_version);
        if ($pre_upgrade_message) {
          WP_CLI::line(CRM_Utils_String::htmlToText($pre_upgrade_message));
          WP_CLI::confirm('Do you want to continue?', $this->assoc_args);
        }
        else {
          WP_CLI::line('(No messages)');
        }
      }

      // Why is dropTriggers() hard-coded? Can't we just enqueue this as part of buildQueue()?
      if ($first_try) {
        WP_CLI::line('Dropping SQL triggers.');
        if (empty($dry_run)) {
          CRM_Core_DAO::dropTriggers();
        }
      }

      // Let's create a file for storing upgrade messages.
      $post_upgrade_message_file = CRM_Utils_File::tempnam('civicrm-post-upgrade');
      WP_CLI::line(sprintf('Created upgrade message file: %s', $post_upgrade_message_file));

      // Build the queue.
      if ($first_try) {
        WP_CLI::line('Preparing upgrade.');
        $queue = CRM_Upgrade_Form::buildQueue($db_version, $code_version, $post_upgrade_message_file);
        // Sanity check - only SQL queues can be resumed.
        if (!($queue instanceof CRM_Queue_Queue_Sql)) {
          return WP_CLI::error('The "upgrade-db" command only supports SQL-based queues.');
        }
      }
      else {
        WP_CLI::line('Resuming upgrade.');
        $queue = CRM_Queue_Service::singleton()->load([
          'name' => CRM_Upgrade_Form::QUEUE_NAME,
          'type' => 'Sql',
        ]);
        if ($skip) {
          $item = $queue->stealItem();
          if (!empty($item->data->title)) {
            WP_CLI::line(sprintf('Skip task: %s', $item->data->title));
            $queue->deleteItem($item);
          }
        }
      }

      // Start the upgrade.
      WP_CLI::line('Executing upgrade.');
      set_time_limit(0);

      // Mimic what "Console Queue Runner" does.
      $task_context = new CRM_Queue_TaskContext();
      $task_context->queue = $queue;
      while ($queue->numberOfItems()) {

        // In case we're retrying a failed job.
        $item = $queue->stealItem();
        $task = $item->data;

        // Feedback.
        WP_CLI::line($task->title);

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
              return WP_CLI::error('Task returned false');
            }
          }
          catch (\Exception $e) {
            // WISHLIST: For interactive mode, perhaps allow retry/skip?
            return WP_CLI::error(sprintf('Error executing task "%s"', $task->title));
          }
        }

        $queue->deleteItem($item);

      }

      WP_CLI::line('Finishing upgrade.');
      if (empty($dry_run)) {
        CRM_Upgrade_Form::doFinish();
      }

      WP_CLI::line(sprintf('Upgrade to %s completed.', $code_version));

      if (version_compare($code_version, '5.26.alpha', '<')) {
        // Work-around for bugs like dev/core#1713.
        WP_CLI::line('Detected CiviCRM 5.25 or earlier. Force flush.');
        if (empty($dry_run)) {
          \Civi\Cv\Util\Cv::passthru('flush');
        }
      }

      WP_CLI::line('Checking post-upgrade messages.');
      $message = file_get_contents($post_upgrade_message_file);
      if ($message) {
        WP_CLI::line(CRM_Utils_String::htmlToText($message));
      }
      else {
        WP_CLI::line('(No messages)');
      }

      // Remove file for storing upgrade messages.
      unlink($post_upgrade_message_file);

      WP_CLI::line('Have a nice day.');

    }

    /**
     * DSN parser - this has been stolen from PEAR DB since we don't always have a
     * bootstrapped environment we can access this from, eg: when doing an upgrade.
     *
     * @since 4.5
     *
     * @param string|array $dsn
     * @return array $parsed The arry containing db connection details.
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

      // Get phptype and dbsyntax.
      // $str => phptype(dbsyntax)
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

      // Get (if found): username and password.
      // $dsn => username:password@protocol+hostspec/database
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

      // Get dabase if any.
      // $dsn => database
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

    /**
     * Helper function to replicate functionality of 'drush_get_option'.
     *
     * @since 4.5
     *
     * @param string $name
     * @param string $default
     * @return mixed The value if found or default if not.
     */
    private function getOption($name, $default) {
      return \WP_CLI\Utils\get_flag_value($this->assoc_args, $name, $default);
    }

    /**
     * Get the user the web server runs as - used to preserve file permissions on
     * templates_c, civicrm/upload etc when running as root. This is not a very
     * good check, but is good enough for what we want to do, which is to preserve
     * file permissions.
     *
     * @since 4.5
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
     * @since 4.5
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
     * Extracts a tar.gz archive.
     *
     * @since 4.5
     *
     * @param string $destination_path The path to extract to.
     * @param string $option The command line option to get input filename from, defaults to 'tarfile'.
     * @return bool True if successful, false otherwise.
     */
    private function untar($destination_path, $option = 'tarfile') {

      if ($tarfile = $this->getOption($option, FALSE)) {
        WP_CLI::line('Extracting tar.gz archive...');
        WP_CLI::launch("gzip -d $tarfile");
        $tarfile = substr($tarfile, 0, strlen($tarfile) - 3);
        WP_CLI::launch("tar -xf $tarfile -C \"$destination_path\"");
        return TRUE;
      }
      else {
        return FALSE;
      }

    }

    /**
     * Extracts a zip archive.
     *
     * @since 4.5
     *
     * @param string $destination_path The path to extract to.
     * @param string $option The command line option to get zip filename from, defaults to 'zipfile'.
     * @return bool True if successful, false otherwise.
     */
    private function unzip($destination_path, $option = 'zipfile') {

      if ($zipfile = $this->getOption($option, FALSE)) {
        WP_CLI::line('Extracting zip archive...');
        WP_CLI::launch("unzip -q $zipfile -d $destination_path");
        return TRUE;
      }
      else {
        return FALSE;
      }

    }

  }

  WP_CLI::add_command('civicrm', 'CiviCRM_Command');
  WP_CLI::add_command('cv', 'CiviCRM_Command');

  // Set path early.
  WP_CLI::add_hook('before_wp_load', function() {

    global $civicrm_paths;
    $wp_cli_config = WP_CLI::get_config();

    // If --path is set, save for later use by CiviCRM.
    if (!empty($wp_cli_config['path'])) {
      $civicrm_paths['cms.root']['path'] = $wp_cli_config['path'];
    }

    // If --url is set, save for later use by CiviCRM.
    if (!empty($wp_cli_config['url'])) {
      $civicrm_paths['cms.root']['url'] = $wp_cli_config['url'];
    }

  });

}
