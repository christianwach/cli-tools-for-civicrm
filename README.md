# Command Line Tools for CiviCRM

**Contributors:** [See full Contributor List](https://github.com/christianwach/cli-tools-for-civicrm)<br/>
**Donate link:** https://www.paypal.me/interactivist<br/>
**Tags:** civicrm, command-line, utility, wp-cli<br/>
**Requires at least:** 4.9<br/>
**Tested up to:** 6.1<br/>
**Stable tag:** 1.0.0a<br/>
**License:** GPLv2 or later<br/>
**License URI:** https://www.gnu.org/licenses/gpl-2.0.html

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

1. Unzip the .zip file and, if needed, rename the enclosing folder so that the plugin's files are located directly inside `/wp-content/plugins/wp-cli-civicrm`
2. Ensure CiviCRM does not load its wp-cli tools using the code above.
3. Activate the plugin.
4. You are done.

### git clone

If you have cloned the code from GitHub, it is assumed that you know what you're doing.
