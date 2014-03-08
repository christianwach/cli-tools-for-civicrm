<?php

class CiviCRM_Command extends WP_CLI_Command {

    public function __invoke($args, $assoc_args) {
        
        $command_router = array(
        	'enable-debug' => 'enableDebug'
        );

        # using global $argv to support drush style command aliases
        # eg: 'wp cvapi' as an alias for 'wp civicrm api'
        global $argv;
        foreach ($argv as $arg) {
        	if (isset($command_router[$arg])) {
        		if (method_exists($this, $command_router[$arg])) {
        			return $this->{$command_router[$arg]}($args, $assoc_args);
        		} else {
        			WP_CLI::error("Unimplemented command - '$arg'");
        		}
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