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

This plugin loads the Command Line Tools in a multi-class structure that WP-CLI expects and which enables proper documentation of the `civicrm` command and all its sub-commands.

### New Commands

There is a new command `wp civicrm core <command>` which (sort of) mirrors the functionality in `wp core <command>`. It holds the commands that apply to CiviCRM as a whole.

| Command | Description | Old Command |
| --- | --- | --- |
| `wp civicrm core activate` | Activates the CiviCRM plugin and loads the database. | New |
| `wp civicrm core backup` | Back up the CiviCRM plugin, CiviCRM files and database. | New |
| `wp civicrm core check-update` | Checks for CiviCRM updates via Version Check API. | New |
| `wp civicrm core check-version` | Checks for a CiviCRM version or matching localization archive. | New |
| `wp civicrm core download` | Downloads core CiviCRM files. | New |
| `wp civicrm core install` | Installs the CiviCRM plugin. | `wp civicrm install` but without activation |
| `wp civicrm core is-installed` | Checks if CiviCRM is installed. | Not implemented yet |
| `wp civicrm core restore` | Restore the CiviCRM plugin, CiviCRM files and database from a backup. | Requires a backup made with `wp civicrm core backup` |
| `wp civicrm core update` | Updates CiviCRM to a newer version. | `wp civicrm upgrade` currently broken |
| `wp civicrm core update-cfg` | Reset paths to correct config settings. | Untested copy of `wp civicrm update-cfg` |
| `wp civicrm core update-db` | Runs the CiviCRM database update procedure. | `wp civicrm upgrade-db` |
| `wp civicrm core verify-checksums` | Verifies CiviCRM files against checksums via `googleapis`. | Not implemented yet |
| `wp civicrm core version` | Displays the CiviCRM version. | New |

Use `wp help civicrm <command>` or `wp help civicrm core <command>` for full details and examples.

There is a new command `wp civicrm db <command>` which (sort of) mirrors the functionality in `wp db <command>`. It holds the commands that apply to interaction with the CiviCRM database.

| Command | Description | Old Command |
| --- | --- | --- |
| `wp civicrm db cli` | Quickly enter the MySQL command line. | `wp civicrm sql-cli` |
| `wp civicrm db config` | Show the CiviCRM database connection details. | `wp civicrm sql-conf` |
| `wp civicrm db connect` | Get a string which connects to the CiviCRM database. | `wp civicrm sql-connect` |
| `wp civicrm db drop-tables` | Drop the CiviCRM tables from the database. | New |
| `wp civicrm db dump` | Export the whole CiviCRM database and print to STDOUT or save to a file.| `wp civicrm sql-dump` |
| `wp civicrm db load` | Loads a whole CiviCRM database. | New |
| `wp civicrm db query` | Perform a query on the CiviCRM database. | `wp civicrm sql-query` |
| `wp civicrm db tables` | Gets a set of CiviCRM tables in the database. | New |

Use `wp help civicrm db <command>` for full details and examples.

### Commands that have been deprecated

All existing commands still exist for the time being. But because they were attached to the top-level `wp civicrm` namespace, it seems sensible to deprecate them in favour of sensibly namespaced new commands.

| Old Command | New Command |
| --- | --- |
| `wp civicrm cache-clear` | `wp civicrm cache flush` |
| `wp civicrm disable-debug` | `wp civicrm debug disable` |
| `wp civicrm enable-debug` | `wp civicrm debug enable` |
| `wp civicrm member-records` | `wp civicrm job member-records` or `wp civicrm job membership` |
| `wp civicrm process-mail-queue` | `wp civicrm job process-mail-queue` or `wp civicrm job mailing` |
| `wp civicrm sql-conf` | `wp civicrm db config` or `wp civicrm db conf` |
| `wp civicrm sql-connect` | `wp civicrm db connect` |
| `wp civicrm sql-cli` | `wp civicrm db cli` |
| `wp civicrm sql-dump` | `wp civicrm db dump` |
| `wp civicrm sql-query` | `wp civicrm db query` |
| `wp civicrm update-cfg` | `wp civicrm core update-cfg` |
| `wp civicrm upgrade` | `wp civicrm core update` |
| `wp civicrm upgrade-db` | `wp civicrm core update-db` |

A special case is `wp civicrm upgrade` which is a composite command - it actually calls the following sequence:

1. `wp civicrm core backup`
2. `wp civicrm core update`
3. `wp civicrm core update-db`

It might be better to call the new commands in the same sequence, but there may also be a case for a set of composite commands like this for common task sequences. I'm open to persuasion on this.

As above, use `wp help civicrm <command>` for full details and examples.

### Commands that have not been updated yet

The following commands exist in this repo, but their code has simply been copied across from CiviCRM:

* `wp civicrm core update-cfg`

PRs would be welcome if you want to start improving (or fixing) them.

### Commands that have been dropped

* `wp civicrm rest`: Use [CiviCRM's WordPress REST API](https://github.com/civicrm/civicrm-wordpress/tree/master/wp-rest) or `wp civicrm api` instead.
