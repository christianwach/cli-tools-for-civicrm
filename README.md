wp-cli-civicrm
==============

WP-CLI integration for CiviCRM


# Usage

Clone, copy or download civicrm.php to your server then tell wp-cli to include it with --require="/path/to/wp/cli/civicrm.php" and use `cv` or `civicrm` as the command.

# Example

`wp-cli --require="/path/to/wp/cli/civicrm.php civicrm upgrade`

# Commands

* api
* cache-clear
* enable-debug
* install
* member-records
* process-mail-queue
* rest
* restore
* sql-cli
* sql-conf
* sql-connect
* sql-dump
* sql-query
* update-cfg
* upgrade
* upgrade-db
