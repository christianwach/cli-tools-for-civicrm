<?php
/**
 * CiviCRM command class.
 *
 * @package Command_Line_Tools_for_CiviCRM
 */

// Exit if accessed directly.
defined('ABSPATH') || exit;

/**
 * Manage CiviCRM through the command-line.
 *
 * ## EXAMPLES
 *
 *     $ wp civicrm version db
 *     Found CiviCRM database version: 5.47.1
 *
 *     $ wp civicrm version db --raw
 *     5.47.1
 *
 * @since 1.0.0
 */
class CLI_Tools_CiviCRM_Command extends CLI_Tools_CiviCRM_Command_Base {

  /**
   * Adds our description and sub-commands.
   *
   * @since 1.0.0
   *
   * @param object $command The command.
   * @return array $info The array of information about the command.
   */
  private function command_to_array($command) {

    $info = [
      'name' => $command->get_name(),
      'description' => $command->get_shortdesc(),
      'longdesc' => $command->get_longdesc(),
    ];

    foreach ($command->get_subcommands() as $subcommand) {
      $info['subcommands'][] = $this->command_to_array($subcommand);
    }

    if (empty($info['subcommands'])) {
      $info['synopsis'] = (string) $command->get_synopsis();
    }

    return $info;

  }

}
