![Build Status](https://github.com/catalyst/moodle-local_datacleaner//actions/workflows/ci.yml/badge.svg?branch=MOODLE_310_STABLE)

# DataCleaner Moodle Module

Moodle DataCleaner is an anonymiser of your Moodle data.

## Branches ##

The following maps the plugin version to use depending on your Moodle version.

| Moodle verion     | Branch             |
| ----------------- | -------------------|
| Moodle up to 3.9  | master             |
| Moodle 3.10       | MOODLE_310_STABLE  |

## How it works

Standard practice when hosting most applications, Moodle included, is to have
various environments in a 'pipeline' leading to production at the end. eg a
typical flow might be `dev > stage > prod` but there could be as many as
you want for various reasons, like load testing, penetration testing etc.

To test properly it's often useful to have real production data in these other
environments, but there are downsides:

* Usually production can be quite massive, we don't need or want it all and
  disk space can be a pain with multiple copies.
* There may be sensitive data we don't want to expose to developers or
  testers, eg personal data, grades, uploaded assignments etc
* Moodle is integrated with 3rd party systems and we don't want test systems
  interacting with real systems, eg sending emails, or touching assignments in
  Turnitin etc, ie we want to remove any API keys and other related config

So we need a way to 'clean' the database after a refresh, to reduce the size of
the data, to remove anything sensitive, and to ensure it's not going to touch
any other real system. This also needs to be configurable because every Moodle
instance has different needs and there is no one-size-fits all approach. This
could be configured outside Moodle in the deployments tools, but over time we
have found the most flexible and easiest approach is to have this configuration
inside Moodle itself, so our clients can directly make these decisions, and not
be exposed to any of the complexity of our internal processes around continous
integration and deployment.

Practically this means the cleaning configuration needs to be added into the
production system (which initially sounds scary but isn't), then you refresh
the database to another environment where it can be washed. There are multiple
levels of safeguards in place to ensure this never gets run in production,
which would of course be catastrophic:

* It can only be run from the CLI. There is no GUI.
* We store the hostname in the cleaning configuration data. If the hostname
  matches production, DataCleaner will not run. If this data is missing then
  it will not run.
* Typically a refreshed database will be from a nightly snapshot and so the
  data should be slightly stale. If a non admin user has logged in recently,
  that's a sign this Moodle is being used, and the DataCleaner will not run.
* If cron has run recently, DataCleaner will not run. This should only be run
  on a data washing instance, cron should not be needed here.
* It can only be run if and only if a 'local_datacleaner_allowexecution = true;'
  has been added to config.php

## Installation

The simplest method of installing the plugin is to choose "Download ZIP" on the
right hand side of the Github page. Once you've done this, unzip the
DataCleaner code and copy it to the local/datacleaner directory within your
Moodle codebase. On most modern Linux systems, this can be accomplished with:

```sh
unzip ./mdl-local_datacleaner-master.zip
cp -r ./mdl-local_datacleaner-master <your_moodle_directory>/local/datacleaner
```

Once you've copied the plugin, you can finish the installation process by
logging into your Moodle site as an administrator and visiting the
"notifications" page:

`<your.moodle.url>/admin/index.php`

Your site should prompt you to upgrade.

## Configuration

Once the installation process is complete, you'll be prompted to fill in some
configuration details. Note that you MUST visit the DataCleaner config page to
save the current wwwroot, or the cleaner will not run later in the other
environments.

```php
$CFG->local_datacleaner_allowexecution = true;
```

You have to add the config item above to your config.php in each of the environments you
want the cleaner to run. DO NOT add that config setting to a Production environment!

There are multiple 'cleaners' which process different types of data in Moodle.
Each one can be enabled individually and may have additional config settings.

You can find the DataCleaner configuration via the Moodle administration block:

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

Enable to replace all occurrences of the production URL with another URL. This has its own Settings page.

#### Cleanup sitedata:

Clean orphaned files or replace with a generic file for the specific file type.

#### Cleanup email:

When a suffix has been configured in the settings, this will append that value to all emails.
There is also a regular expression field that will ignore users when appending the suffix.

Also this will allow you to configure following Moodle settings:
 - noemailever
 - divertallemailsto
 - divertallemailsexcept

#### Environment matrix:

**Notice**: A soft dependency on local_envbar is required for populating the available environments that can be configured.

This facilitates searching values in the {config} and {config_plugins} tables to allow setting those values. Useful for scrubbing API keys to prevent them calling home on a development environment.

A CLI script exists to run the Environment matrix cleaner as a standalone operation.

```sh
sudo -u apache /usr/bin/php /<your_moodle_directory>/local/datacleaner/environment_matrix/cli/matrix_replace.php --run
```

An additional CLI flag has been implemented. --reset.

This flag will purge all other saved environment configuration so that the new instance only has one set of environment data.

## Running

After installing and configuring DataCleaner, copy your database and optionally your site data to another Moodle instance.

From here run the cli script. On most modern Linux systems, this can be accomplished with:

```sh
sudo -u apache /usr/bin/php /<your_moodle_directory>/local/datacleaner/cli/clean.php --run
```

There are protections in place which prevent accidental running on this on your production system - which would of course be catastrophic!

### More options

Run the cli script with --help for more options:

```sh
sudo -u apache /usr/bin/php /<your_moodle_directory>/local/datacleaner/cli/clean.php --help
```

