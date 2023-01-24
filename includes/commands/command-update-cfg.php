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

  }

}
