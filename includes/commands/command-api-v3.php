<?php
/**
 * Access the CiviCRM API v3.
 *
 * ## EXAMPLES
 *
 *     $ wp civicrm api contact.get id=10
 *     Output here.
 *
 * @since 1.0.0
 */
class CLI_Tools_CiviCRM_Command_API_V3 extends CLI_Tools_CiviCRM_Command {

  /**
   * Object fields.
   *
   * @var array
   */
  protected $obj_fields = array(
    'id',
  );

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
   * Access the CiviCRM API v3.
   *
   * ## OPTIONS
   *
   * <args>...
   * : The API query passed as arguments.
   *
   * [--in=<in>]
   * : Specify the input in a particular format.
   * ---
   * default: args
   * options:
   *   - args
   *   - json
   * ---
   *
   * [--out=<out>]
   * : Render output in a particular format. The "table" format can only be used when retrieving a single item.
   * ---
   * default: pretty
   * options:
   *   - pretty
   *   - json
   *   - table
   * ---
   *
   * ## EXAMPLES
   *
   *     $ wp civicrm api contact.get id=10
   *     $ wp civicrm api contact.get id=10 --out=json
   *     $ wp civicrm api group.get id=1 --out=table
   *     $ echo '{"id":10, "api.Email.get": 1}' | wp cv api contact.get --in=json
   *
   * @since 1.0.0
   *
   * @param array $args The WP-CLI positional arguments.
   * @param array $assoc_args The WP-CLI associative arguments.
   */
  public function __invoke($args, $assoc_args) {

    $defaults = ['version' => 3];

    // Get the Entity and Action from the first positional argument.
    list($entity, $action) = explode('.', $args[0]);
    array_shift($args);

    // Parse params.
    $in_format = \WP_CLI\Utils\get_flag_value($assoc_args, 'in', 'args');
    switch ($in_format) {

      // Input params supplied via args.
      case 'args':
        $params = $defaults;
        foreach ($args as $arg) {
          preg_match('/^([^=]+)=(.*)$/', $arg, $matches);
          $params[$matches[1]] = $matches[2];
        }
        break;

      // Input params supplied via json.
      case 'json':
        $json = stream_get_contents(STDIN);
        if (empty($json)) {
          $params = $defaults;
        }
        else {
          $params = array_merge($defaults, json_decode($json, TRUE));
        }
        break;

      default:
        WP_CLI::error(sprintf('Unknown format: %s', $in_format));
        break;

    }

    civicrm_initialize();

    // CRM-18062: Set CiviCRM timezone if any.
    $wp_base_timezone = date_default_timezone_get();
    $wp_user_timezone = \WP_CLI\Utils\get_flag_value($assoc_args, 'timezone', get_option('timezone_string'));
    if ($wp_user_timezone) {
      date_default_timezone_set($wp_user_timezone);
      CRM_Core_Config::singleton()->userSystem->setMySQLTimeZone();
    }

    $result = civicrm_api($entity, $action, $params);

    // Restore WordPress timezone.
    if ($wp_base_timezone) {
      date_default_timezone_set($wp_base_timezone);
    }

    $out_format = \WP_CLI\Utils\get_flag_value($assoc_args, 'out', 'pretty');
    switch ($out_format) {

      // Pretty-print output (default).
      case 'pretty':
        WP_CLI::log(print_r($result, TRUE));
        break;

      // Display output as json.
      case 'json':
        WP_CLI::log(json_encode($result));
        break;

      // Display output as table.
      case 'table':
        $assoc_args['format'] = $out_format;
        if (count($result['values']) === 1) {
          $item = array_pop($result['values']);
          $assoc_args['fields'] = array_keys($item);
          $formatter = $this->get_formatter($assoc_args);
          $formatter->display_item($item);
        }
        else {

          // Give up and log usual output.
          WP_CLI::log(print_r($result, TRUE));

          // phpcs:disable

          /*
          // Testing whether we can do this. It's hard, but kinda works.
          $fields_query = civicrm_api3($entity, 'getfields', [
            'api_action' => $action,
          ]);;
          $fields = array_keys($fields_query['values']);
          //WP_CLI::log(print_r($fields, TRUE));

          //WP_CLI::log(print_r($result['values'], TRUE));
          $assoc_args['fields'] = $fields;
          //WP_CLI::log(print_r($assoc_args['fields'], TRUE));

          // Cast items as objects.
          array_walk($result['values'], function( &$item ) use ( $fields ) {
            foreach ($fields as $field) {
              // Make sure the array has all keys.
              if (!array_key_exists($field, $item)) {
                $item[$field] = '';
              }
            }
            $item = (object) $item;
          });
          //WP_CLI::log(print_r($result['values'], TRUE));

          $formatter = $this->get_formatter($assoc_args);
          //WP_CLI::log(print_r($formatter, TRUE));

          $formatter->display_items($result['values']);
          */

          // phpcs:enable

        }
        break;

      default:
        WP_CLI::error(sprintf('Unknown format: %s', $format));

    }

  }

}
