<?php
/**
 * Backup restorer class.
 *
 * @package Command_Line_Tools_for_CiviCRM
 */

// Make sure WP_Upgrader exists.
if (!class_exists('WP_Upgrader')) {
  require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
}

/**
 * Backup restorer class.
 *
 * @since 1.0.0
 */
class CLI_Tools_CiviCRM_WP_Upgrader extends WP_Upgrader {

  /**
   * Whether a bulk upgrade/installation is being performed.
   *
   * @since 1.0.0
   * @var bool $bulk
   */
  public $bulk = false;

  /**
   * Initialize the backup strings.
   *
   * @since 1.0.0
   */
  public function backup_strings() {
    $this->strings['unpack_package'] = 'Unpacking the backup...';
    $this->strings['installing_package'] = 'Restoring the backup...';
    $this->strings['remove_old'] = 'Removing the existing directory...';
    $this->strings['remove_old_failed'] = 'Could not remove the existing directory.';
    $this->strings['process_failed'] = 'Backup failed.';
    $this->strings['process_success'] = 'Backup restored successfully.';
  }

  /**
   * Restore a directory from a backup.
   *
   * @since 1.0.0
   *
   * @param string $zipfile The path to the zipfile.
   * @param string $destination The directory name to extract to.
   * @return array|false|WP_Error The result on success, otherwise a WP_Error, or false if unable to connect to the filesystem.
   */
  public function restore($zipfile, $destination) {

    $this->backup_strings();

    $options = [
      'package' => $zipfile,
      'destination' => untrailingslashit($destination),
      'clear_destination' => TRUE,
      'clear_working' => FALSE,
      'abort_if_destination_exists' => FALSE,
    ];

    return $this->run($options);

  }

}
