<?php
/**
 * Plugin Name: Command Line Tools for CiviCRM
 * Plugin URI: https://github.com/christianwach/cli-tools-for-civicrm
 * GitHub Plugin URI: https://github.com/christianwach/cli-tools-for-civicrm
 * Description: Manage CiviCRM through the command line.
 * Author: Christian Wach
 * Version: 1.0.0a
 * Author URI: https://haystack.co.uk
 * Depends: CiviCRM
 *
 * @package Command_Line_Tools_for_CiviCRM
 */

// Exit if accessed directly.
defined('ABSPATH') || exit;

// Set our version here.
define('COMMAND_LINE_CIVICRM_VERSION', '1.0.0a');

// Store reference to this file.
if (!defined('COMMAND_LINE_CIVICRM_FILE')) {
  define('COMMAND_LINE_CIVICRM_FILE', __FILE__);
}

// Store URL to this plugin's directory.
if (!defined('COMMAND_LINE_CIVICRM_URL')) {
  define('COMMAND_LINE_CIVICRM_URL', plugin_dir_url(COMMAND_LINE_CIVICRM_FILE));
}

// Store PATH to this plugin's directory.
if (!defined('COMMAND_LINE_CIVICRM_PATH')) {
  define('COMMAND_LINE_CIVICRM_PATH', plugin_dir_path(COMMAND_LINE_CIVICRM_FILE));
}

/**
 * Command Line Tools for CiviCRM Class.
 *
 * A class that encapsulates plugin functionality.
 *
 * @since 1.0.0
 */
class Command_Line_Tools_for_CiviCRM {

  /**
   * Constructor.
   *
   * @since 1.0.0
   */
  public function __construct() {

    // Load wp-cli tools.
    $this->include_files();

  }

  /**
   * Loads the wp-cli tools.
   *
   * @since 1.0.0
   */
  public function include_files() {

    // Bail if not wp-cli context.
    if (!defined('WP_CLI')) {
      return;
    }

    // Bail if not PHP 5.6+.
    if (!version_compare(phpversion(), '5.6.0', '>=')) {
      return;
    }

    // Bail if wp-cli tools are already loaded.
    if (class_exists('CiviCRM_Command')) {
      return;
    }

    // Load our wp-cli tools.
    if (defined('COMMAND_LINE_CIVICRM_LEGACY')) {
      require COMMAND_LINE_CIVICRM_PATH . 'includes/wp-cli-civicrm-legacy.php';
    }
    else {
      require COMMAND_LINE_CIVICRM_PATH . 'includes/wp-cli-civicrm.php';
    }

  }

}

/**
 * Bootstrap plugin if not yet loaded and returns reference.
 *
 * @since 1.0.0
 *
 * @return CommentPress_Plugin $plugin The plugin reference.
 */
function command_line_civicrm() {

  // Maybe bootstrap plugin.
  static $plugin;
  if (!isset($plugin)) {
    $plugin = new Command_Line_Tools_for_CiviCRM();
  }

  // Return reference.
  return $plugin;

}

// Bootstrap immediately.
command_line_civicrm();
