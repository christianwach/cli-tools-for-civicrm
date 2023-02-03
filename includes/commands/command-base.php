<?php
/**
 * Base command class.
 *
 * @since 1.0.0
 *
 * @package Command_Line_Tools_for_CiviCRM
 */
abstract class CLI_Tools_CiviCRM_Command_Base extends \WP_CLI\CommandWithDBObject {

  /**
   * Dependency check.
   *
   * @since 1.0.0
   */
  public static function check_dependencies() {
    // Check for existence of CiviCRM.
    if (!function_exists('civicrm_initialize')) {
      WP_CLI::error('Unable to find CiviCRM install.');
    }
  }

  /**
   * Bootstrap CiviCRM.
   *
   * @since 1.0.0
   */
  public static function bootstrap_civicrm() {
    self::check_dependencies();
    if (!civicrm_initialize()) {
      WP_CLI::error('Unable to initialize CiviCRM.');
    }
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
   * Extracts a tar.gz archive.
   *
   * @since 1.0.0
   *
   * @param string $tarfile The path to the tarfile.
   * @param string $destination The path to extract to.
   * @param bool $delete True deletes the zip archive once extracted. Default to true.
   * @return bool True if successful, false otherwise.
   */
  protected function untar($tarfile, $destination, $delete = TRUE) {

    // Sanity check tarfile.
    if (empty($tarfile)) {
      return FALSE;
    }

    // Sanity check destination.
    if (empty($destination)) {
      return FALSE;
    }

    // Let's handle errors here.
    $exit_on_error = FALSE;
    $return_detailed = TRUE;

    WP_CLI::log(WP_CLI::colorize('%GExtracting tar.gz archive...%n'));

    // First unpack the gz archive.
    $command = "gzip -d $tarfile";
    $process_run = WP_CLI::launch($command, $exit_on_error, $return_detailed);
    //WP_CLI::log(print_r($process_run, TRUE));
    if (0 !== $process_run->return_code) {
      WP_CLI::error(sprintf(WP_CLI::colorize('Failed to extract gz archive: %y%s.%n'), $this->stderr_error_msg($process_run)));
    }

    // Next unpack the tarball.
    $tarfile = substr($tarfile, 0, strlen($tarfile) - 3);
    $command = "tar -xf $tarfile -C \"$destination\"";
    $process_run = WP_CLI::launch($command, $exit_on_error, $return_detailed);
    //WP_CLI::log(print_r($process_run, TRUE));
    if (0 !== $process_run->return_code) {
      WP_CLI::error(sprintf(WP_CLI::colorize('Failed to extract tarball: %y%s.%n'), $this->stderr_error_msg($process_run)));
    }

    // Delete the tar archive.
    if (!empty($delete)) {
      global $wp_filesystem;
      if (empty($wp_filesystem)) {
        WP_Filesystem();
      }
      $wp_filesystem->delete($tarfile, TRUE);
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
   * @param string $zipfile The path to the zipfile.
   * @param string $destination The path to extract to.
   * @param bool $delete True deletes the zip archive once extracted. Defaults to true.
   * @return bool True if successful, false otherwise.
   */
  protected function unzip($zipfile, $destination, $delete = TRUE) {

    // Sanity check zipfile.
    if (empty($zipfile)) {
      return FALSE;
    }

    // Sanity check destination.
    if (empty($destination)) {
      return FALSE;
    }

    WP_CLI::log(WP_CLI::colorize('%GExtracting zip archive...%n'));

    // Let's handle errors here.
    $exit_on_error = FALSE;
    $return_detailed = TRUE;

    // Run the command.
    $command = "unzip -q $zipfile -d $destination";
    $process_run = WP_CLI::launch($command, $exit_on_error, $return_detailed);
    //WP_CLI::log(print_r($process_run, TRUE));
    if (0 !== $process_run->return_code) {
      WP_CLI::error(sprintf(WP_CLI::colorize('Failed to extract zip archive: %y%s.%n'), $this->unzip_error_msg($process_run->return_code)));
    }

    // Delete the zip archive.
    if (!empty($delete)) {
      global $wp_filesystem;
      if (empty($wp_filesystem)) {
        WP_Filesystem();
      }
      $wp_filesystem->delete($zipfile, TRUE);
    }

    return TRUE;

  }

  /**
   * Compresses a zip archive.
   *
   * @since 1.0.0
   *
   * @param string $directory The directory to compress.
   * @param string $destination The path to the directory where the compressed archive will be saved.
   * @return bool True if successful, false otherwise.
   */
  protected function zip($directory, $destination) {

    // Sanity check directory.
    if (empty($directory)) {
      return FALSE;
    }

    // Sanity check destination.
    if (empty($destination)) {
      return FALSE;
    }

    WP_CLI::log(WP_CLI::colorize('%GCompressing zip archive...%n'));

    // Let's handle errors here.
    $exit_on_error = FALSE;
    $return_detailed = TRUE;

    // Run the command.
    $command = 'pushd ' . dirname($directory) . '; ' . "zip -rq {$destination} ./" . basename($directory) . '; popd';
    WP_CLI::log(print_r($command, TRUE));
    $process_run = WP_CLI::launch($command, $exit_on_error, $return_detailed);
    //WP_CLI::log(print_r($process_run, TRUE));
    if (0 !== $process_run->return_code) {
      WP_CLI::error(sprintf(WP_CLI::colorize('Failed to compress zip archive: %y%s.%n'), $this->zip_error_msg($process_run->return_code)));
    }

    return TRUE;

  }

  /**
   * Extracts a zip archive to a destination directory and removes zip archive.
   *
   * This is useful if we want to extract a zip archive and know the name we want to give
   * the enclosing directory. It's better to use `self::unzip()` if we want to leave the
   * enclosing directory with its given directory name, e.g. when extracting the CiviCRM
   * plugin archive.
   *
   * @since 1.0.0
   *
   * @param string $zipfile The path to the zipfile.
   * @param string $destination The directory name to extract to.
   * @return bool True if successful, false otherwise.
   */
  protected function zip_extract($zipfile, $destination) {

    // Let's use a custom WP_Upgrader object.
    require_once __DIR__ . '/utilities/class-zip-extractor.php';
    $extractor = \WP_CLI\Utils\get_upgrader('CLI_Tools_CiviCRM_Zip_Extractor');

    // Go ahead and restore from backup.
    $extractor->init();
    $result = $extractor->extract($zipfile, $destination);

    // Trap any problems.
    if ($result === FALSE) {
      WP_CLI::error('Unable to connect to the filesystem.');
    }
    if (is_wp_error($result)) {
      WP_CLI::error(sprintf(WP_CLI::colorize('Failed to extract zip archive: %y%s.%n'), $result->get_error_message()));
    }

    return TRUE;

  }

  /**
   * Extracts a zip archive and overwrites a destination directory.
   *
   * @since 1.0.0
   *
   * @param string $zipfile The path to the zipfile.
   * @param string $destination The directory name to extract to.
   * @return bool True if successful, false otherwise.
   */
  protected function zip_overwrite($zipfile, $destination) {

    // Let's use a custom WP_Upgrader object.
    require_once __DIR__ . '/utilities/class-backup-restorer.php';
    $overwriter = \WP_CLI\Utils\get_upgrader('CLI_Tools_CiviCRM_WP_Upgrader');

    // Go ahead and restore from backup.
    $overwriter->init();
    $result = $overwriter->restore($zipfile, $destination);

    // Trap any problems.
    if ($result === FALSE) {
      WP_CLI::error('Unable to connect to the filesystem.');
    }
    if (is_wp_error($result)) {
      WP_CLI::error(sprintf(WP_CLI::colorize('Failed to extract zip archive: %y%s.%n'), $result->get_error_message()));
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
  private function stderr_error_msg($process_run) {

    // Grab error string.
    $stderr = trim($process_run->stderr);
    $nl_pos = strpos($stderr, "\n");
    if (FALSE !== $nl_pos) {
      $stderr = trim(substr($stderr, 0, $nl_pos));
    }

    // Return formatted string if possible.
    if ($stderr) {
      return sprintf('%s (%d)', $stderr, $process_run->return_code);
    }

    // Fall back to raw error code.
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
  private function unzip_error_msg($error_code) {

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

  /**
   * Returns a formatted `zip` error message for a given error code.
   *
   * @since 1.0.0
   *
   * @param int $error_code The error code.
   * @return string $error_code The formatted error code.
   */
  private function zip_error_msg($error_code) {

    $zip_err_msgs = [
      0 => 'No errors or warnings detected.',
      2 => 'Unexpected end of zip file.',
      3 => 'A generic error in the zipfile format was detected. Processing may have completed successfully anyway; some broken zipfiles created by other archivers have simple work-arounds..',
      4 => 'zip was unable to allocate memory for one or more buffers during program initialization.',
      5 => 'A severe error in the zipfile format was detected. Processing probably failed immediately.',
      6 => 'Entry too large to be processed (such as input files larger than 2 GB when not using Zip64 or trying to read an existing archive that is too large) or entry too large to be split with zipsplit',
      7 => 'Invalid comment format.',
      8 => 'zip -T failed or out of memory',
      9 => 'The user aborted zip prematurely with control-C (or similar).',
      10 => 'zip encountered an error while using a temp file.',
      11 => 'read or seek error.',
      12 => 'zip has nothing to do.',
      13 => 'Missing or empty zip file.',
      14 => 'Error writing to a file.',
      15 => 'zip was unable to create a file to write to.',
      16 => 'Bad command line parameters.',
      18 => 'zip could not open a specified file to read.',
      19 => 'zip was compiled with options not supported on this system.',
    ];

    if (isset($zip_err_msgs[$error_code])) {
      return sprintf('%s (%d)', $zip_err_msgs[$error_code], $error_code);
    }

    return $error_code;

  }

  /**
   * Performs a remote GET request that requires JSON data in response.
   *
   * @since 1.0.0
   *
   * @param string $url The URL to execute the GET request on.
   * @param array $headers Optional. Associative array of headers.
   * @param array $options Optional. Associative array of options.
   * @return mixed|false False on failure. Decoded JSON on success.
   */
  protected function json_get_request($url, $headers = [], $options = []) {

    $headers = array_merge(
      ['Accept' => 'application/json'],
      $headers
    );

    $response = $this->json_get_response($url, $headers, $options);
    if (FALSE === $response) {
      return $response;
    }

    $data = json_decode($response, TRUE);
    if (JSON_ERROR_NONE !== json_last_error()) {
      WP_CLI::error(sprintf(WP_CLI::colorize('Failed to decode JSON: %y%s.%n'), json_last_error_msg()));
    }

    return $data;

  }

  /**
   * Performs a remote GET request.
   *
   * @since 1.0.0
   *
   * @param string $url The URL to execute the GET request on.
   * @param array $headers Optional. Associative array of headers.
   * @param array $options Optional. Associative array of options.
   * @return object $response The response object.
   */
  protected function json_get_response($url, $headers = [], $options = []) {

    $options = array_merge(
      ['halt_on_error' => FALSE],
      $options
    );

    $response = \WP_CLI\Utils\http_request('GET', $url, NULL, $headers, $options);
    if (!$response->success || 200 > (int) $response->status_code || 300 <= $response->status_code) {
      WP_CLI::error(sprintf(WP_CLI::colorize("Couldn't fetch response from %y%s%n (HTTP code %y%s%n)."), $url, $response->status_code));
    }

    return trim($response->body);

  }

  /**
   * Downloads a remote file with a GET request.
   *
   * @since 1.0.0
   *
   * @param string $url The URL to execute the GET request on.
   * @param string $destination Optional. The path to the download directory. Default is local temp dir.
   * @param array $headers Optional. Associative array of headers.
   * @param array $options Optional. Associative array of options.
   * @return string $filepath The path to the downloaded file.
   */
  protected function file_download($url, $destination = '', $headers = [], $options = []) {

    // Set default destination.
    if (empty($destination)) {
      $destination = \WP_CLI\Utils\get_temp_dir();
    }

    // Extract filename, stripping query variables if present.
    $filename = basename($url);
    if (FALSE !== strpos($filename, '?')) {
      $arr = explode('?', $filename);
      $filename = $arr[0];
    }

    // Build final path to file.
    $filepath = trailingslashit($destination) . $filename;

    // Build request options.
    $options = array_merge(
      [
        'timeout'  => 600,
        'filename' => $filepath,
        'insecure' => FALSE,
      ],
      $options
    );

    // Okay, do the download.
    $response = \WP_CLI\Utils\http_request('GET', $url, NULL, $headers, $options);
    if (!$response->success || 200 !== (int) $response->status_code) {
      WP_CLI::error(sprintf(WP_CLI::colorize("Couldn't fetch response from %y%s%n (HTTP code %y%s%n)."), $url, $response->status_code));
    }

    return $filepath;

  }

  /**
   * Gets the path to the CiviCRM plugin directory.
   *
   * @since 1.0.0
   *
   * @return string|bool $plugin_path The path to the CiviCRM plugin directory.
   */
  protected function get_plugin_path() {

    global $wp_filesystem;
    if (empty($wp_filesystem)) {
      WP_Filesystem();
    }

    // Get the path to the WordPress plugins directory.
    $plugins_dir = $wp_filesystem->wp_plugins_dir();
    if (empty($plugins_dir)) {
      WP_CLI::error('Unable to locate WordPress plugins directory.');
    }

    // The path to the CiviCRM plugin directory.
    $plugin_path = trailingslashit($plugins_dir) . 'civicrm';

    return $plugin_path;

  }

  /**
   * Recursively implode an array.
   *
   * @since 1.0.0
   *
   * @param array $value The array to implode.
   * @param integer $level The current level.
   * @return string
   */
  protected static function implode_recursive($value, $level = 0) {

    // Maybe recurse.
    $array = [];
    if (is_array($value)) {
      foreach ($value as $val) {
        if (is_array($val)) {
          $array[] = self::implode_recursive($val, $level + 1);
        }
        else {
          $array[] = $val;
        }
      }
    }
    else {
      $array[] = $value;
    }

    // Wrap sub-arrays but leave top level alone.
    if ($level > 0) {
      $string = '[' . implode(',', $array) . ']';
    }
    else {
      $string = implode(',', $array);
    }

    return $string;

  }

}
