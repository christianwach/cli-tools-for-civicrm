<?php

class CiviCRM_Enable_Debug extends WP_CLI_Command {

    public function __invoke($args, $assoc_args) {
        
        $params['debug']     = 1;
        $params['backtrace'] = 1;

        require_once 'CRM/Admin/Form/Setting.php';
        CRM_Admin_Form_Setting::commonProcess($params);

        WP_CLI::success('Debug setting enabled.');
        
    }

}

foreach (array(
    'civicrm-enable-debug' => 'CiviCRM_Enable_Debug',
) as $command => $class)
    WP_CLI::add_command($command, $class);