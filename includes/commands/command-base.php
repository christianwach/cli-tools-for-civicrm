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
    } elseif (is_email($identifier)) {
      $user = get_user_by('email', $identifier);
    } else {
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

}
