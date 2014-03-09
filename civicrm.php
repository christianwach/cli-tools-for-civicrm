<?php

/**
 * WP-CLI port of drush-civicrm integration
 * andyw, 08/03/2014
 */
class CiviCRM_Command extends WP_CLI_Command {

    private $args, $assoc_args;

    /**
     * WP-CLI integration with CiviCRM.
     */
    public function __invoke($args, $assoc_args) {
       
        # check for existence of Civi
        if (!function_exists('civicrm_initialize')) 
            return WP_CLI::error("Unable to find CiviCRM install.");

        # define command router
        $command_router = array(
            'api'          => 'api',
            'cache-clear'  => 'cacheClear',
            'enable-debug' => 'enableDebug',
            'sql-cli'      => 'sqlCLI',
            'sql-conf'     => 'sqlConf',
            'sql-connect'  => 'sqlConnect',
            'sql-dump'     => 'sqlDump',
            'sql-query'    => 'sqlQuery',
            'update-cfg'   => 'updateConfig',
            'upgrade-db'   => 'upgradeDB'
        );

        # get command
        $command = array_shift($args);

        # check existence of router entry / handler method
        if (!isset($command_router[$command]) or !method_exists($this, $command_router[$command]))
            return WP_CLI::error("Unrecognized command - '$command'");

        $this->args       = $args;
        $this->assoc_args = $assoc_args;

        # run command
        return $this->{$command_router[$command]}();
                   
    }

    /**
     * Implementation of command 'api'
     */
    private function api() {
        
        $defaults = array('version' => 3);

        list($entity, $action) = explode('.', $this->args[0]);
        array_shift($this->args);

        # parse $params

        switch ($this->getOption('in', 'args')) {
            
            # input params supplied via args ..
            case 'args':
                $params = $defaults;
                foreach ($this->args as $arg) {
                    preg_match('/^([^=]+)=(.*)$/', $arg, $matches);
                    $params[$matches[1]] = $matches[2];
                }
                break;

            # input params supplied via json ..
            case 'json':
                $json   = stream_get_contents(STDIN);
                $params = (empty($json) ? $defaults : array_merge($defaults, json_decode($json, true)));
                break;

            default:
                WP_CLI::error('Unknown format: ' . $format);
                break;
        }

        civicrm_initialize();
        $result = civicrm_api($entity, $action, $params);

        switch ($this->getOption('out', 'pretty')) {
            
            # pretty-print output (default)
            case 'pretty':
                WP_CLI::line(print_r($result, true));
                break;

            # display output as json
            case 'json':
                WP_CLI::line(json_encode($result));
                break;

            default:
                return WP_CLI::error('Unknown format: ' . $format);
        
        }
    
    }

    /**
     * Implementation of command 'cache-clear'
     */
    private function cacheClear() {
        
        civicrm_initialize();
        require_once 'CRM/Core/Config.php';
        $config = CRM_Core_Config::singleton();

        # clear db caching
        $config->clearDBCache();

        # also cleanup the templates_c directory
        $config->cleanup(1, FALSE);

        # also cleanup the session object
        $session = CRM_Core_Session::singleton();
        $session->reset(1);
    
    }

    /**
     * Implementation of command 'enable-debug'
     */
    private function enableDebug() {
        
        civicrm_initialize();

        $params['debug']     = 1;
        $params['backtrace'] = 1;

        require_once 'CRM/Admin/Form/Setting.php';
        CRM_Admin_Form_Setting::commonProcess($params);
    
        WP_CLI::success('Debug setting enabled.');
    
    }

    /**
     * Implementation of command 'sql-conf'
     */
    private function sqlConf() {
        
        civicrm_initialize();
        if (!defined('CIVICRM_DSN'))
            WP_CLI::error('CIVICRM_DSN is not defined.');            

        WP_CLI::line(print_r(DB::parseDSN(CIVICRM_DSN), true));
    
    }

    /**
     * Implementation of command 'sql-connect'
     */
    private function sqlConnect() {
        
        civicrm_initialize();
        if (!defined('CIVICRM_DSN'))
            WP_CLI::error('CIVICRM_DSN is not defined.');            

        $dsn = DB::parseDSN(CIVICRM_DSN);
        
        $output = sprintf(
            "mysql --database=%s --host=%s --user=%s --password=%s",
            $dsn['database'],
            $dsn['hostspec'],
            $dsn['username'],
            $dsn['password']
        );

        if (isset($dsn['port']) and !empty($dsn['port']))
            $output .= ' --port=' . $dsn['port'];

        WP_CLI::line($output);
    
    }

    /**
     * Implementation of command 'sql-dump'
     */
    private function sqlDump() {

        civicrm_initialize();
        if (!defined('CIVICRM_DSN'))
            WP_CLI::error('CIVICRM_DSN is not defined.');            

        $dsn = DB::parseDSN(CIVICRM_DSN);
    
        $assoc_args       = $this->assoc_args;
        $stdout           = !isset($assoc_args['result-file']);
        $command          = "mysqldump --no-defaults --host={$dsn['host']} --user={$dsn['username']} --password={$dsn['password']} %s";
        $command_esc_args = array($dsn['database']);

        if (isset($assoc_args['tables'])) {
            $tables = explode(',', $assoc_args['tables'] );
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
                array($command), 
                $command_esc_args 
            )
        );

        \WP_CLI\Utils\run_mysql_command($escaped_command, $assoc_args);

        if (!$stdout) 
            WP_CLI::success(sprintf('Exported to %s', $assoc_args['result-file']));

    }

    /**
     * Implementation of command 'sql-query'
     */
    private function sqlQuery() {

        if (!isset($this->args[0])) {
            WP_CLI::error("No query specified.");
            return;
        }

        $query = $this->args[0];

        civicrm_initialize();
        if (!defined('CIVICRM_DSN'))
            WP_CLI::error('CIVICRM_DSN is not defined.');            

        $dsn = DB::parseDSN(CIVICRM_DSN);
        
        $mysql_args = array(
            'host'     => $dsn['hostspec'],
            'database' => $dsn['database'],
            'user'     => $dsn['username'],
            'password' => $dsn['password'],
            'execute'  => $query
        );

        \WP_CLI\Utils\run_mysql_command('mysql --no-defaults', $mysql_args);

    }

    /**
     * Implementation of command 'sql-cli'
     */
    private function sqlCLI() {

        civicrm_initialize();
        if (!defined('CIVICRM_DSN'))
            WP_CLI::error('CIVICRM_DSN is not defined.');            

        $dsn = DB::parseDSN(CIVICRM_DSN);
        
        $mysql_args = array(
            'host'     => $dsn['hostspec'],
            'database' => $dsn['database'],
            'user'     => $dsn['username'],
            'password' => $dsn['password']
        );

        \WP_CLI\Utils\run_mysql_command('mysql --no-defaults', $mysql_args);

    }

    /**
     * Implementation of command 'update-cfg'
     */
    private function updateConfig() {

        civicrm_initialize();

        $defaultValues = array();
        $states        = array('old', 'new');
        
        for ($i = 1; $i <= 3; $i++) {
            foreach ($states as $state) {
                $name = "{$state}Val_{$i}";
                $value = $this->getOption($name, NULL);
                if ($value) 
                    $defaultValues[$name] = $value;
                
            }
        }

        require_once 'CRM/Core/I18n.php';
        require_once 'CRM/Core/BAO/ConfigSetting.php';
        $result = CRM_Core_BAO_ConfigSetting::doSiteMove($defaultValues);

        if ($result) {
            WP_CLI::success('Config successfully updated.');
        } else {
            WP_CLI::error('Config update failed.');
        }
    
    }

    /**
     * Implementation of command 'upgrade-db'
     */
    private function upgradeDB() {

        civicrm_initialize();
        if (class_exists('CRM_Upgrade_Headless')) {
            // Note: CRM_Upgrade_Headless introduced in 4.2 -- at the same time as class auto-loading
            try {
                $upgradeHeadless = new CRM_Upgrade_Headless();
                $result = $upgradeHeadless->run();
                WP_CLI::line("Upgrade outputs: " . "\"" . $result['message'] . "\"");
            } catch (Exception $e) {
                WP_CLI::error($e->getMessage());
            }

        } else {
            
            require_once 'CRM/Core/Smarty.php';
            $template = CRM_Core_Smarty::singleton();

            require_once ('CRM/Upgrade/Page/Upgrade.php');
            $upgrade = new CRM_Upgrade_Page_Upgrade();

            // new since CiviCRM 4.1
            if (is_callable(array(
                $upgrade, 'setPrint'))) {
                $upgrade->setPrint(TRUE);
            }

            // to suppress html output /w source code.
            ob_start();
            $upgrade->run();
            // capture the required message.
            $result = $template->get_template_vars('message');
            ob_end_clean();
            WP_CLI::line("Upgrade outputs: " . "\"$result\"");
        
        }
    
    }

    /**
     * Helper function to replicate functionality of drush_get_option
     * @param  $name (string)
     * @return mixed - value if found or $default
     */
    private function getOption($name, $default) {
        return isset($this->assoc_args[$name]) ? $this->assoc_args[$name] : $default;
    }

    /**
     * Initializes the CiviCRM environment and configuration.
     * TODO: document why we can't call civicrm_initialize() directly.
     *
     * @param  bool fail
     *   If true, will halt drush. Otherwise, return false but do not interrupt.
     *
     * @return bool
     *   Returns TRUE if CiviCRM was initialized.
     */
    private function _civicrm_init($fail = TRUE) {
      
      static $init = NULL;

      // return if already initialized
      if ($init) {
        return $init;
      }

      global $cmsPath;
      $cmsPath             = $wp_root = ABSPATH;
      $civicrmSettingsFile = "$wp_root/wp-content/plugins/civicrm/civicrm.settings.php";

      if (!file_exists($civicrmSettingsFile)) {
        return WP_CLI::error('Could not locate civicrm settings file.');
      }

      // include settings file
      define('CIVICRM_SETTINGS_PATH', $civicrmSettingsFile);
      include_once $civicrmSettingsFile;
      global $civicrm_root;
      
      if (!is_dir($civicrm_root)) 
          return WP_CLI::error('Could not locate CiviCRM codebase. Make sure CiviCRM settings file has correct information.');

      // Autoload was added in 4.2
      require_once 'CRM/Utils/System.php';
      $codeVer = CRM_Utils_System::version();

      if (substr($codeVer, 0, 3) >= '4.2') {
        require_once $civicrm_root . '/CRM/Core/ClassLoader.php';
        CRM_Core_ClassLoader::singleton()->register();
      }

      // also initialize config object
      require_once 'CRM/Core/Config.php';
      $config = CRM_Core_Config::singleton();

      $init = TRUE; 
      return $init;
    }

}

WP_CLI::add_command('civicrm', 'CiviCRM_Command');