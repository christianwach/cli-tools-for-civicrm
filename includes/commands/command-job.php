<?php
/**
 * Run CiviCRM cron jobs.
 *
 * ## EXAMPLES
 *
 *     $ wp civicrm job mailing
 *     Success: Executed 'process_mailing' job.
 *
 *     $ wp civicrm job membership
 *     Success: Executed 'process_membership' job.
 *
 * @since 1.0.0
 *
 * @package Command_Line_Tools_for_CiviCRM
 */
class CLI_Tools_CiviCRM_Command_Job extends CLI_Tools_CiviCRM_Command {

  /**
   * Process pending CiviMail mailing jobs.
   *
   * ## EXAMPLES
   *
   *     $ wp civicrm job mailing
   *     Success: Executed 'process_mailing' job.
   *
   * @alias process-mail-queue
   *
   * @since 1.0.0
   *
   * @param array $args The WP-CLI positional arguments.
   * @param array $assoc_args The WP-CLI associative arguments.
   */
  public function mailing($args, $assoc_args) {

    civicrm_initialize();

    $job = new CRM_Core_JobManager();
    $job->executeJobByAction('job', 'process_mailing');

    WP_CLI::success("Executed 'process_mailing' job.");

  }

  /**
   * Process pending CiviMember membership record update jobs.
   *
   * ## EXAMPLES
   *
   *     $ wp civicrm job membership
   *     Success: Executed 'process_membership' job.
   *
   * @alias member-records
   *
   * @since 1.0.0
   *
   * @param array $args The WP-CLI positional arguments.
   * @param array $assoc_args The WP-CLI associative arguments.
   */
  public function membership($args, $assoc_args) {

    civicrm_initialize();

    $job = new CRM_Core_JobManager();
    $job->executeJobByAction('job', 'process_membership');

    WP_CLI::success('Executed "process_membership" job.');

  }

}
