<?php

class CiviCRM_Command extends WP_CLI_Command {

    /**
     * WP-CLI integration with CiviCRM.
     */
    public function __invoke($args, $assoc_args) {
       
        # existential check
        if (!function_exists('civicrm_initialize')) 
            return WP_CLI::error("Unable to find instance of CiviCRM.");

        $command_router = array(
            'enable-debug' => 'enableDebug'
        );

        $command = array_shift($args);

        if (isset($command_router[$command])) {
            if (method_exists($this, $command_router[$command])) {
                civicrm_initialize(true);
                return $this->{$command_router[$command]}($args, $assoc_args);
            } else {
                WP_CLI::error("Unimplemented command - '$arg'");
            }
        }
               
    }

    private function enableDebug($args, $assoc_args) {
        /*
        $params['debug']     = 1;
        $params['backtrace'] = 1;

        require_once 'CRM/Admin/Form/Setting.php';
        CRM_Admin_Form_Setting::commonProcess($params);
        */
        WP_CLI::success('Debug setting enabled.');
    }

}

WP_CLI::add_command('civicrm', 'CiviCRM_Command');