# DataCleaner Moodle Module

Moodle DataCleaner is an anonymiser of your Moodle data.

Supported versions of Moodle: 2.5 to 2.9 inclusive

## Warning

Please never run in production.

How do we guarantee this never gets run in production? We will have a couple heuristics to ensure that this never runs:

* Only run from CLI. CataCleaner cannot be run from GUI.
* Store the hostname in the cleaning configuration data. If the hostname matches production, DataCleaner will not run.
* If a non admin user has logged in recently, DataCleaner will not run.
* If cron has run recently, DataCleaner will not run. This should only be run on a data washing instance, cron should not be needed here.

## Installation

The simplest method of installing the plugin is to choose "Download ZIP" on the right hand side of the Github page. Once you've done this, unzip the DataCleaner code and copy it to the local/datacleaner directory within your Moodle codebase. On most modern Linux systems, this can be accomplished with:

`unzip ./mdl-local_datacleaner-master.zip
cp -r ./mdl-local_datacleaner-master <your_moodle_directory>/local/datacleaner`

Once you've copied the plugin, you can finish the installation process by logging into your Moodle site as an administrator and visiting the "notifications" page:

`<your.moodle.url>/admin/index.php`

Your site should prompt you to upgrade.

## Configuration

Once the installation process is complete, you'll be prompted to fill in some configuration details.

 You can also find the DataCleaner configurations again at any time via the Moodle administration block:
 
`Site Adminstration > Plugins > Local plugins > Data cleaner`

### Sub-plugin options

Enable the sub-plugin options to clean the corresponding data area.

#### Cleanup core:

Enable this sub-plugin to clean core configuration settings.

#### Remove config:

Enable this sub-plugin to clean configuration settings. This has its own Settings page.

#### Remove standard logs:

Enable to truncate the standard log table.

#### Remove users:

This will remove users who have not logged in for a specific number of days. This has its own Settings page.

#### Remove courses:

Remove courses older than a specific number of days and/or in specific categories. This has its own Settings page.

#### Scramble user data:

Enable this sub-plugin to anonymise user data. This has its own Settings page.

#### Clean grades:

Enable to delete grade history or replace with fake data. This has its own Settings page.

#### Replace URLs:

Enable to replace all occurrences of the production URL with a another URl. This has its own Settings page.

#### Cleanup sitedata:

Clean orphaned files or replace with a generic file for the specific file type.

## Running

After installing and configuring DataCleaner, run the cli script. On most modern Linux systems, this can be accomplished with:

`sudo -u apache /usr/bin/php /<your_moodle_directory>/local/datacleaner/cli/clean.php --run`

### More options

Run the cli script with --help for more options:

`sudo -u apache /usr/bin/php /<your_moodle_directory>/local/datacleaner/cli/clean.php --help`
