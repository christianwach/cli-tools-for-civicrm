<?php
/**
 * Command Line Tools for CiviCRM (MU Plugin)
 *
 * Plugin Name:       Command Line Tools for CiviCRM (MU Plugin)
 * Description:       Prevents CiviCRM from loading its command line tools and allows Command Line Tools for CiviCRM to do so instead.
 * Version:           1.0.3a
 * Plugin URI:        https://github.com/christianwach/cli-tools-for-civicrm
 * GitHub Plugin URI: https://github.com/christianwach/cli-tools-for-civicrm
 * Author:            Christian Wach
 * Author URI:        https://haystack.co.uk
 *
 * Put this plugin in /wp-content/mu-plugins/
 *
 * @package Command_Line_Tools_for_CiviCRM
 */

// Exit if accessed directly.
defined('ABSPATH') || exit;

/**
 * Prevent CiviCRM from loading its wp-cli tools.
 */
if (!defined('CIVICRM_WPCLI_LOADED')) {
  define('CIVICRM_WPCLI_LOADED', 1);
}
