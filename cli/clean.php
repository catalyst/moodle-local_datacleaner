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

define('CLI_SCRIPT', true);
require(dirname(dirname(dirname(dirname(__FILE__)))).'/config.php');
require_once($CFG->libdir.'/clilib.php');
require_once($CFG->libdir.'/adminlib.php');
require_once(dirname(__FILE__) . '/lib.php');

// Now get cli options.
list($options, $unrecognized) = cli_get_params(
    array(
        'help' => false,
        'force' => false,
        'run' => false,
        'dryrun' => false,
        'verbose' => false,
    ),
    array('h' => 'help')
);

if ($unrecognized) {
    $unrecognized = implode("\n  ", $unrecognized);
    cli_error(get_string('cliunknowoption', 'admin', $unrecognized));
}

$help = "Perform a datawash.

To configure this plugin goto $CFG->wwwroot/local/datacleaner/

Options:
 -h, --help     Print out this help
     --run      Actually run the clean process
     --dryrun   Print an overview of what would run
     --force    Skip all prod detection safety checks
     --verbose  Be noisey about what is being done or would be done

Example:
\$sudo -u www-data /usr/bin/php local/datacleaner/cli/clean.php --run
";

if (!$options['run'] &&
    !$options['dryrun']) {
    echo $help;
    die;
}

if ($options['help']) {
    echo $help;
    die;
}

if ($options['force']) {
    print_message("Safety checks skipped due to --force command line option.\n", true);
} else {
    $wouldhavedied = safety_checks($options['dryrun']);
    if ($wouldhavedied) {
        print_message("Remaining output shows what will happen if you force execution or deal with safety issues.\n");
    }
}

$plugins = \local_datacleaner\plugininfo\cleaner::get_enabled_plugins_by_sortorder();

if (!$plugins) {
    echo get_string('noplugins', 'local_datacleaner') . "\n";
    exit;
}

if ($options['dryrun']) {
    echo "=== DRY RUN ===\n";
}

$cascade = null;

foreach ($plugins as $plugin) {
    // Get the class that does the work.
    $classname = 'cleaner_' . $plugin->name . '\clean';

    echo "== Running {$plugin->name} cleaner ==\n";
    if (!class_exists($classname)) {
        echo "ERROR: Unable to locate local/datacleaner/cleaner/{$plugin->name}/classes/clean.php class. Skipping.\n";
        continue;
    }

    $class = new $classname($options['dryrun'], $options['verbose']);
    if (is_null($cascade) && $class->needs_cascade_delete()) {
        $cascade = new \local_datacleaner\schema_add_cascade_delete($options['dryrun'], $options['verbose']);

        // Shutdown handler does the undo().
        $cascade->execute('user');
        $cascade->execute('course');
    }

    $class->execute();
}

echo "Done.\n";
