<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * @package    local_datacleaner
 * @copyright  2015 Brendan Heywood <brendan@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// Check if host name is prod.
// Check if cron is running or has run recently.
// Check last user login.
// If any of these are true then bail.

// Record time stamps.
//

define('CLI_SCRIPT', true);
require(dirname(dirname(dirname(dirname(__FILE__)))).'/config.php');
require_once($CFG->libdir.'/clilib.php');
require_once($CFG->libdir.'/adminlib.php');

list($options, $unrecognized) = cli_get_params(array('help' => false),
                                               array('h' => 'help'));

if ($unrecognized) {
    $unrecognized = implode("\n  ", $unrecognized);
    cli_error(get_string('cliunknowoption', 'admin', $unrecognized));
}

if ($options['help']) {
    $help = "Perform a datawash.

Options:
-h, --help            Print out this help

Example:
\$sudo -u www-data /usr/bin/php local/datacleaner/cli/clean.php
";

    echo $help;
    die;
}

$plugins = \local_datacleaner\plugininfo\cleaner::get_enabled_plugins_by_priority();

if (!$plugins) {
    echo "No cleaner plugins enabled\n";
    exit;
}

foreach ($plugins as $plugin) {
    // Get the class that does the work.
    $classname = 'cleaner_' . $plugin->name . '\clean';

    echo "== Running {$plugin->name} cleaner ==\n";
    if (!class_exists($classname)) {
        echo "ERROR: Unable to locate local/datacleaner/cleaner/{$plugin->name}/classes/clean.php class. Skipping.\n";
        continue;
    }

    $class = new $classname;
    $class->execute();
}

echo "Done.\n";

