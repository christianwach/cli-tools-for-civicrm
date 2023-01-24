<?php
/**
 * Upgrade the CiviCRM plugin files and database.
 *
 * ## EXAMPLES
 *
 *     $ wp civicrm upgrade --zipfile=~/civicrm-5.57.1-wordpress.zip
 *
 * @since 1.0.0
 */
class CLI_Tools_CiviCRM_Command_Upgrade extends CLI_Tools_CiviCRM_Command {

  /**
   * Upgrade the CiviCRM plugin files and database.
   *
   * ## OPTIONS
   *
   * [--tarfile=<tarfile>]
   * : Path to your CiviCRM tar.gz file. Not currently available.
   *
   * [--zipfile=<zipfile>]
   * : Path to your CiviCRM zip file.
   *
   * ## EXAMPLES
   *
   *     $ wp civicrm upgrade --zipfile=~/civicrm-5.57.1-wordpress.zip
   *
   * @since 1.0.0
   *
   * @param array $args The WP-CLI positional arguments.
   * @param array $assoc_args The WP-CLI associative arguments.
   */
  public function __invoke($args, $assoc_args) {

  }

}
