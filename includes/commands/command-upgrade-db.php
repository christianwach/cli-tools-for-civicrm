<?php
/**
 * Upgrade the CiviCRM database schema.
 *
 * ## EXAMPLES
 *
 *     $ wp civicrm upgrade-db
 *     $ wp civicrm upgrade-db --dry-run
 *
 * @since 1.0.0
 */
class CLI_Tools_CiviCRM_Command_Upgrade_DB extends CLI_Tools_CiviCRM_Command {

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
   * Upgrade the CiviCRM database schema.
   *
   * ## OPTIONS
   *
   * [--dry-run]
   * : Preview the list of upgrade tasks.
   *
   * [--retry]
   * : Resume a failed upgrade, retrying the last step.
   *
   * [--skip]
   * : Resume a failed upgrade, skipping the last step.
   *
   * [--step]
   * : Run the upgrade queue in steps, pausing before each step.
   *
   * ## EXAMPLES
   *
   *     $ wp civicrm upgrade-db --dry-run
   *     Found CiviCRM code version: 5.57.1
   *     Found CiviCRM database version: 5.57.0
   *     Checking pre-upgrade messages.
   *     (No messages)
   *     Dropping SQL triggers.
   *     Preparing upgrade.
   *     Executing upgrade.
   *     Cleanup old files
   *     Cleanup old upgrade snapshots
   *     Checking extensions
   *     Finish Upgrade DB to 5.57.1
   *     Update all reserved message templates
   *     Finish core DB updates 5.57.1
   *     Assess extension upgrades
   *     Generate final messages
   *     Finishing upgrade.
   *     Upgrade to 5.57.1 completed.
   *     Checking post-upgrade messages.
   *     (No messages)
   *     Have a nice day.
   *
   * @since 1.0.0
   *
   * @param array $args The WP-CLI positional arguments.
   * @param array $assoc_args The WP-CLI associative arguments.
   */
  public function __invoke($args, $assoc_args) {

    civicrm_initialize();

    if (!defined('CIVICRM_UPGRADE_ACTIVE')) {
      define('CIVICRM_UPGRADE_ACTIVE', 1);
    }

    // Check whether an upgrade is necessary.
    $code_version = CRM_Utils_System::version();
    WP_CLI::log(sprintf('Found CiviCRM code version: %s', $code_version));
    $db_version = CRM_Core_BAO_Domain::version();
    WP_CLI::log(sprintf('Found CiviCRM database version: %s', $db_version));
    if (version_compare($code_version, $db_version) == 0) {
      WP_CLI::success(sprintf('You are already upgraded to CiviCRM %s', $code_version));
      WP_CLI::halt(0);
    }

    // Get flags.
    $dry_run = \WP_CLI\Utils\get_flag_value($assoc_args, 'dry-run', FALSE);
    $retry = \WP_CLI\Utils\get_flag_value($assoc_args, 'retry', FALSE);
    $skip = \WP_CLI\Utils\get_flag_value($assoc_args, 'skip', FALSE);
    $step = \WP_CLI\Utils\get_flag_value($assoc_args, 'step', FALSE);
    $first_try = (empty($retry) && empty($skip)) ? TRUE : FALSE;

    // Bail if incomplete upgrade.
    if ($first_try && FALSE !== stripos($db_version, 'upgrade')) {
      WP_CLI::error('Cannot begin upgrade: The database indicates that an incomplete upgrade is pending. If you would like to resume, use --retry or --skip.');
    }

    // Bootstrap upgrader.
    $upgrade = new CRM_Upgrade_Form();
    $error = $upgrade->checkUpgradeableVersion($db_version, $code_version);
    if (!empty($error)) {
      WP_CLI::error($error);
    }

    // Check pre-upgrade messages.
    if ($first_try) {
      WP_CLI::log('Checking pre-upgrade messages.');
      $preUpgradeMessage = NULL;
      $upgrade->setPreUpgradeMessage($preUpgradeMessage, $db_version, $code_version);
      if ($preUpgradeMessage) {
        WP_CLI::log(CRM_Utils_String::htmlToText($preUpgradeMessage));
        WP_CLI::confirm('Do you want to continue?', $assoc_args);
      }
      else {
        WP_CLI::log('(No messages)');
      }
    }

    // Why is dropTriggers() hard-coded? Can't we just enqueue this as part of buildQueue()?
    if ($first_try) {
      WP_CLI::log('Dropping SQL triggers.');
      if (empty($dry_run)) {
        CRM_Core_DAO::dropTriggers();
      }
    }

    // Let's create a file for storing upgrade messages.
    $post_upgrade_message_file = CRM_Utils_File::tempnam('civicrm-post-upgrade');
    //WP_CLI::log(sprintf('Created upgrade message file: %s', $post_upgrade_message_file));

    // Build the queue.
    if ($first_try) {
      WP_CLI::log('Preparing upgrade.');
      $queue = CRM_Upgrade_Form::buildQueue($db_version, $code_version, $post_upgrade_message_file);
      // Sanity check - only SQL queues can be resumed.
      if (!($queue instanceof CRM_Queue_Queue_Sql)) {
        WP_CLI::error('The "upgrade-db" command only supports SQL-based queues.');
      }
    }
    else {
      WP_CLI::log('Resuming upgrade.');
      $queue = CRM_Queue_Service::singleton()->load([
        'name' => CRM_Upgrade_Form::QUEUE_NAME,
        'type' => 'Sql',
      ]);
      if ($skip) {
        $item = $queue->stealItem();
        if (!empty($item->data->title)) {
          WP_CLI::log(sprintf('Skip task: %s', $item->data->title));
          $queue->deleteItem($item);
        }
      }
    }

    // Start the upgrade.
    WP_CLI::log('Executing upgrade.');
    set_time_limit(0);

    // Mimic what "Console Queue Runner" does.
    $task_context = new CRM_Queue_TaskContext();
    $task_context->queue = $queue;
    $task_context->log = \Log::singleton('display');

    while ($queue->numberOfItems()) {

      // In case we're retrying a failed job.
      $item = $queue->stealItem();
      $task = $item->data;

      // Feedback.
      WP_CLI::log($task->title);

      // Get action.
      $action = 'y';
      if (!empty($step)) {
        fwrite(STDOUT, 'Execute this step?' . ' [ y=yes / s=skip / a=abort ] ');
        $action = strtolower(trim(fgets(STDIN)));
      }

      // Bail if skip action is "abort".
      if ($action === 'a') {
        WP_CLI::halt(1);
      }

      // Run the task when action is "yes".
      if ($action === 'y' && empty($dry_run)) {
        try {
          $success = $task->run($task_context);
          if (!$success) {
            WP_CLI::error('Task returned false');
          }
        }
        catch (\Exception $e) {
          // WISHLIST: For interactive mode, perhaps allow retry/skip?
          WP_CLI::error(sprintf('Error executing task "%s"', $task->title));
        }
      }

      $queue->deleteItem($item);

    }

    WP_CLI::log('Finishing upgrade.');
    if (empty($dry_run)) {
      CRM_Upgrade_Form::doFinish();
    }

    WP_CLI::log(sprintf('Upgrade to %s completed.', $code_version));

    if (version_compare($code_version, '5.26.alpha', '<')) {
      // Work-around for bugs like dev/core#1713.
      WP_CLI::log('Detected CiviCRM 5.25 or earlier. Force flush.');
      if (empty($dry_run)) {
        \Civi\Cv\Util\Cv::passthru('flush');
      }
    }

    WP_CLI::log('Checking post-upgrade messages.');
    $message = file_get_contents($post_upgrade_message_file);
    if ($message) {
      WP_CLI::log(CRM_Utils_String::htmlToText($message));
    }
    else {
      WP_CLI::log('(No messages)');
    }

    // Remove file for storing upgrade messages.
    unlink($post_upgrade_message_file);

    WP_CLI::log('Have a nice day.');

  }

}
