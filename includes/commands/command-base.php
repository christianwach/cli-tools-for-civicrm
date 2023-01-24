<?php
/**
 * Base command class.
 *
 * @package Command_Line_Tools_for_CiviCRM
 */

// Exit if accessed directly.
defined('ABSPATH') || exit;

/**
 * Base command class.
 *
 * @since 1.0.0
 */
abstract class CLI_Tools_CiviCRM_Command_Base extends \WP_CLI\CommandWithDBObject {

  /**
   * Default dependency check.
   *
   * @since 1.0.0
   */
  public static function check_dependencies() {
    // Implement this for commands that require it.
  }

  /**
   * Gets the Formatter object for a given set of arguments.
   *
   * @since 1.0.0
   *
   * @param array $assoc_args The params passed to a command. Determines the formatting.
   * @return \WP_CLI\Formatter
   */
  protected function get_formatter(&$assoc_args) {
    return new \WP_CLI\Formatter($assoc_args, $this->obj_fields);
  }

  /**
   * Verifies the User for a given identifier.
   *
   * @since 1.0.0
   *
   * @param mixed $identifier The User ID, email or username.
   * @return WP_User $user The WordPress User object.
   */
  protected function get_user_id_from_identifier($identifier) {

    // Get user depending on type of param.
    if (is_numeric($identifier)) {
      $user = get_user_by('id', $identifier);
    }
    elseif (is_email($identifier)) {
      $user = get_user_by('email', $identifier);
    }
    else {
      $user = get_user_by('login', $identifier);
    }

    if (!$user) {
      WP_CLI::error(sprintf('No user found by that username, email or ID (%s).', $identifier));
    }

    return $user;
  }

  /**
   * String sanitization.
   *
   * @since 1.0.0
   *
   * @param string $type The string to sanitize.
   * @return string The sanitized string.
   */
  protected function sanitize_string($type) {
    return strtolower(str_replace('-', '_', $type));
  }

  /**
   * Helper method to replicate functionality of 'drush_get_option'.
   *
   * @since 1.0.0
   *
   * @param array $assoc_args The WP-CLI associative arguments.
   * @param string $name The name of the argument to find.
   * @param string $default The default value if the argument is not.
   * @return mixed The value if found or default if not.
   */
  protected function get_option($assoc_args, $name, $default) {
    return \WP_CLI\Utils\get_flag_value($assoc_args, $name, $default);
  }

  /**
   * Extracts a tar.gz archive.
   *
   * @since 1.0.0
   *
   * @param string $destination The path to extract to.
   * @param array $assoc_args The WP-CLI associative arguments.
   * @param string $option The command line option to get input filename from, defaults to 'tarfile'.
   * @return bool True if successful, false otherwise.
   */
  public function untar($destination, $assoc_args, $option = 'tarfile') {

    // Grab path to tarfile.
    $tarfile = \WP_CLI\Utils\get_flag_value($assoc_args, $option, FALSE);
    if (empty($tarfile)) {
      return FALSE;
    }

    // Let's handle errors here.
    $exit_on_error = false;
    $return_detailed = true;

    WP_CLI::log(WP_CLI::colorize('%GExtracting tar.gz archive.%n'));

    // First unzip the gz archive.
    $cmd = "gzip -d $tarfile";
    $process_run = WP_CLI::launch($cmd, $exit_on_error, $return_detailed);
    //WP_CLI::log(print_r($process_run, TRUE));
    if (0 !== $process_run->return_code) {
      WP_CLI::error(sprintf(WP_CLI::colorize('Failed to extract gz archive: %y%s.%n'), $this->tar_error_msg($process_run)));
    }

    // Next untar the tarball.
    $tarfile = substr($tarfile, 0, strlen($tarfile) - 3);
    $cmd = "tar -xf $tarfile -C \"$destination\"";
    $process_run = WP_CLI::launch($cmd, $exit_on_error, $return_detailed);
    //WP_CLI::log(print_r($process_run, TRUE));
    if (0 !== $process_run->return_code) {
      WP_CLI::error(sprintf(WP_CLI::colorize('Failed to extract tarball: %y%s.%n'), $this->tar_error_msg($process_run)));
    }

    return TRUE;

  }

  /**
   * Extracts a zip archive.
   *
   * Note: if no extension is supplied, `unzip` will check for "filename.zip" and "filename.ZIP"
   * in the same location.
   *
   * @since 1.0.0
   *
   * @param string $destination The path to extract to.
   * @param array $assoc_args The WP-CLI associative arguments.
   * @param string $option The command line option to get zip filename from, defaults to 'zipfile'.
   * @return bool True if successful, false otherwise.
   */
  public function unzip($destination, $assoc_args, $option = 'zipfile') {

    // Grab path to zipfile.
    $zipfile = \WP_CLI\Utils\get_flag_value($assoc_args, $option, FALSE);
    if (empty($zipfile)) {
      return FALSE;
    }

    WP_CLI::log(WP_CLI::colorize('%GExtracting zip archive.%n'));

    // Let's handle errors here.
    $exit_on_error = false;
    $return_detailed = true;

    // Run the command.
    $cmd = "unzip -q $zipfile -d $destination";
    $process_run = WP_CLI::launch($cmd, $exit_on_error, $return_detailed);
    //WP_CLI::log(print_r($process_run, TRUE));
    if (0 !== $process_run->return_code) {
      WP_CLI::error(sprintf(WP_CLI::colorize('Failed to extract zip archive: %y%s.%n'), $this->zip_error_msg($process_run->return_code)));
    }

    return TRUE;

  }

  /**
   * Returns a formatted error message from a ProcessRun command.
   *
   * @since 1.0.0
   *
   * @param object $process_run The ProcessRun object.
   * @return string|int The error message of the process if available, otherwise the return code.
   */
  private function tar_error_msg($process_run) {
    $stderr = trim($process_run->stderr);
    $nl_pos = strpos($stderr, "\n");
    if (FALSE !== $nl_pos) {
      $stderr = trim(substr($stderr, 0, $nl_pos));
    }
    if ($stderr) {
      return sprintf('%s (%d)', $stderr, $process_run->return_code);
    }

    return $process_run->return_code;

  }

  /**
   * Returns a formatted `unzip` error message for a given error code.
   *
   * @since 1.0.0
   *
   * @param int $error_code The error code.
   * @return string $error_code The formatted error code.
   */
  private function zip_error_msg($error_code) {

    $zip_err_msgs = [
      0 => 'No errors or warnings detected.',
      1 => 'One or more warning errors were encountered, but processing completed successfully anyway. This includes zipfiles where one or more files was skipped due to unsupported compression method or encryption with an unknown password.',
      2 => 'A generic error in the zipfile format was detected. Processing may have completed successfully anyway; some broken zipfiles created by other archivers have simple work-arounds.',
      3 => 'A severe error in the zipfile format was detected. Processing probably failed immediately.',
      4 => 'unzip was unable to allocate memory for one or more buffers during program initialization.',
      5 => 'unzip was unable to allocate memory or unable to obtain a tty to read the decryption password(s).',
      6 => 'unzip was unable to allocate memory during decompression to disk.',
      7 => 'unzip was unable to allocate memory during in-memory decompression.',
      8 => '[currently not used]',
      9 => 'The specified zipfiles were not found.',
      10 => 'Invalid options were specified on the command line.',
      11 => 'No matching files were found.',
      50 => 'The disk is (or was) full during extraction.',
      51 => 'The end of the ZIP archive was encountered prematurely.',
      80 => 'The user aborted unzip prematurely with control-C (or similar)',
      81 => 'Testing or extraction of one or more files failed due to unsupported compression methods or unsupported decryption.',
      82 => 'No files were found due to bad decryption password(s). (If even one file is successfully processed, however, the exit status is 1.)',
    ];

    if (isset($zip_err_msgs[$error_code])) {
      return sprintf('%s (%d)', $zip_err_msgs[$error_code], $error_code);
    }

    return $error_code;

  }

}
