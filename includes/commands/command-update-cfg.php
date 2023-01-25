<?php
/**
 * Upgrade the CiviCRM plugin files and database.
 *
 * ## EXAMPLES
 *
 *     $ wp civicrm update-cfg
 *
 * @since 1.0.0
 */
class CLI_Tools_CiviCRM_Command_Update_Config extends CLI_Tools_CiviCRM_Command {

  /**
   * Reset paths to correct config settings.
   *
   * This command can be useful when the CiviCRM site has been cloned or migrated.
   *
   * ## OPTIONS
   *
   * ## EXAMPLES
   *
   *     $ wp civicrm update-cfg
   *
   * @since 1.0.0
   *
   * @param array $args The WP-CLI positional arguments.
   * @param array $assoc_args The WP-CLI associative arguments.
   */
  public function __invoke($args, $assoc_args) {

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

}
