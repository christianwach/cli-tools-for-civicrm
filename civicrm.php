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
       
        // check for existence of Civi
        if (!function_exists('civicrm_initialize')) 
            return WP_CLI::error("Unable to find CiviCRM install.");

        // define command router
        $command_router = array(
            'api'          => 'api',
            'enable-debug' => 'enableDebug'
        );

        // get command
        $command = array_shift($args);

        // check existence of router entry / handler method
        if (!isset($command_router[$command]) or !method_exists($this, $command_router[$command]))
            return WP_CLI::error("Unrecognized command - '$command'");

        $this->args       = $args;
        $this->assoc_args = $assoc_args;

        // initialize Civi and run command
        //$this->_civicrm_init();
        return $this->{$command_router[$command]}($args, $assoc_args);
                   
    }

    /**
     * Implementation of command 'civicrm-api'
     */
    private function api() {
      $DEFAULTS = array('version' => 3);

      list($entity, $action) = explode('.', $this->args[0]);
      array_shift($this->args);

      // Parse $params

      switch (isset($this->assoc_args['in']) ? $this->assoc_args['in'] : 'args') {
        case 'args':
          $params = $DEFAULTS;
          foreach ($this->args as $arg) {
            preg_match('/^([^=]+)=(.*)$/', $arg, $matches);
            $params[$matches[1]] = $matches[2];
          }
          break;

        case 'json':
          $json = stream_get_contents(STDIN);
          if (empty($json)) {
            $params = $DEFAULTS;
          }
          else {
            $params = array_merge($DEFAULTS, json_decode($json, TRUE));
          }
          break;

        default:
          WP_CLI::error('Unknown format: ' . $format);
          break;
      }

      civicrm_initialize();
      $result = civicrm_api($entity, $action, $params);

      switch (isset($this->assoc_args['out']) ? $this->assoc_args['out'] : 'pretty') {
        case 'pretty':
          print_r($result);
          break;

        case 'json':
          print(json_encode($result));
          break;

        default:
          return WP_CLI::error('Unknown format: ' . $format);
      }
    }

    private function enableDebug($args, $assoc_args) {
        
        civicrm_initialize();

        $params['debug']     = 1;
        $params['backtrace'] = 1;

        require_once 'CRM/Admin/Form/Setting.php';
        CRM_Admin_Form_Setting::commonProcess($params);
    
        WP_CLI::success('Debug setting enabled.');
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
      print 'civicrm_root = ' . $civicrm_root . "\n";
      if (!is_dir($civicrm_root)) {
        return WP_CLI::error('Could not locate CiviCRM codebase. Make sure CiviCRM settings file has correct information.');
      }

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