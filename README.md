# Command Line Tools for CiviCRM

**Contributors:** [See full Contributor List](https://github.com/christianwach/cli-tools-for-civicrm)<br/>
**Tags:** civicrm, command-line, utility, wp-cli<br/>
**Requires at least:** 4.9<br/>
**Tested up to:** 6.1<br/>
**Stable tag:** 1.0.0a<br/>
**License:** GNU Affero General Public License v3.0<br/>
**License URI:** https://github.com/christianwach/cli-tools-for-civicrm/blob/master/LICENSE

Manage CiviCRM through the command line.

## Description

*Command Line Tools for CiviCRM* is a WordPress "Feature Plugin" whose primary purpose is for the development of the `wp civicrm` command provided by CiviCRM.

## Installation

*Important* Before activating this plugin, you must make sure that CiviCRM does not load its wp-cli tools. Add the following code to your `wp-config.php` file (or create a "Must Use" plugin that contains the code):

```php
/**
 * Prevent CiviCRM from loading its wp-cli tools.
 */
define( 'CIVICRM_WPCLI_LOADED', 1 );
```

There are two ways to install from GitHub:

### ZIP Download

If you have downloaded *Command Line Tools for CiviCRM* as a ZIP file from the GitHub repository, do the following to install and activate the plugin:

1. Unzip the .zip file and, if needed, rename the enclosing folder so that the plugin's files are located directly inside `/wp-content/plugins/cli-tools-for-civicrm`
2. Ensure CiviCRM does not load its wp-cli tools using the code above.
3. Activate the plugin.
4. You are done.

### git clone

If you have cloned the code from GitHub, it is assumed that you know what you're doing.

## Development

If you would like to help migrate the Command Line Tools to a more compatible structure, then add the Add the following code to your `wp-config.php` file (or create a "Must Use" plugin that contains the code):

```php
/**
 * Enable the latest Command Line Tools for CiviCRM.
 */
define( 'COMMAND_LINE_CIVICRM_LATEST', 1 );
```

This will enable the multi-class structure that WP-CLI expects.

### Commands that have changed

| Old Command | New Command |
| --- | --- |
| `wp civicrm disable-debug` | `wp civicrm debug disable` |
| `wp civicrm enable-debug` | `wp civicrm debug enable` |
| `wp civicrm member-records` | `wp civicrm job member-records` |
| `wp civicrm process-mail-queue` | `wp civicrm job process-mail-queue` |
| `wp civicrm sql-conf` | `wp civicrm sql config` |
| `wp civicrm sql-connect` | `wp civicrm sql connect` |
| `wp civicrm sql-connect` | `wp civicrm sql connect` |
| `wp civicrm sql-cli` | `wp civicrm sql cli` |
| `wp civicrm sql-dump` | `wp civicrm sql dump` |
| `wp civicrm sql-query` | `wp civicrm sql query` |
| `wp civicrm version-code` | `wp civicrm version code` |
| `wp civicrm version-db` | `wp civicrm version db` |

### Commands that have been dropped

* `wp civicrm rest`: use [CiviCRM's WordPress REST API](https://github.com/civicrm/civicrm-wordpress/tree/master/wp-rest) instead.

### Commands still to migrate

* `wp civicrm install`
* `wp civicrm restore`
* `wp civicrm upgrade`
* `wp civicrm update-cfg`
