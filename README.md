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

By default, this plugin will load the Command Line Tools in a multi-class structure that WP-CLI expects and which enables proper documentation of the `civicrm` command and all its sub-commands. It is not, however, complete yet.

### Commands that have not been updated

The following commands exist in this repo, but their code has simply been copied across from CiviCRM:

* `wp civicrm restore`
* `wp civicrm update-cfg`

PRs would be welcome if you want to start improving (or fixing) them.

### Commands that are being updated

The following commands exist in this repo, but are not currently functional:

* `wp civicrm upgrade`

PRs would be welcome if you want to help improve them.

If you need the old `wp civicrm upgrade` command then simply comment out your `CIVICRM_WPCLI_LOADED` define and then you can use it as supplied by CiviCRM. Please bear in mind that it [seems to be broken](https://lab.civicrm.org/dev/wordpress/-/issues/123) at the moment.

### New Commands

* `wp civicrm upgrade-get`: Find out what file you should use to upgrade CiviCRM.
* `wp civicrm upgrade-dl`: Download CiviCRM code and put it in place for an upgrade.
* `wp civicrm version-get`: Get the URL for a CiviCRM stable release or matching language archive.
* `wp civicrm version-dl`: Download a CiviCRM stable release archive or language archive.

Use `wp help civicrm command-name` for further details.

### Commands that have changed

| Old Command | New Command |
| --- | --- |
| `wp civicrm disable-debug` | `wp civicrm debug disable` |
| `wp civicrm enable-debug` | `wp civicrm debug enable` |
| `wp civicrm member-records` | `wp civicrm job member-records` or `wp civicrm job membership` |
| `wp civicrm process-mail-queue` | `wp civicrm job process-mail-queue` or `wp civicrm job mailing` |
| `wp civicrm sql-conf` | `wp civicrm sql config` or `wp civicrm sql conf` |
| `wp civicrm sql-connect` | `wp civicrm sql connect` |
| `wp civicrm sql-cli` | `wp civicrm sql cli` |
| `wp civicrm sql-dump` | `wp civicrm sql dump` |
| `wp civicrm sql-query` | `wp civicrm sql query` |
| `wp civicrm version-code` | `wp civicrm version code` |
| `wp civicrm version-db` | `wp civicrm version db` |

### Commands that have been dropped

* `wp civicrm rest`: Use [CiviCRM's WordPress REST API](https://github.com/civicrm/civicrm-wordpress/tree/master/wp-rest) or `wp civicrm api` instead.
