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
        'filter' => false,
        'run' => false,
        'run-pre-wash' => false,
        'run-post-wash' => false,
        'dryrun' => false,
        'verbose' => false,
        'reset' => false,
    ),
    array(
        'h' => 'help',
        'v' => 'verbose',
    )
);

if ($unrecognized) {
    $unrecognized = implode("\n  ", $unrecognized);
    cli_error(get_string('cliunknowoption', 'admin', $unrecognized));
}

$help = "Perform a datawash.

To configure this plugin goto $CFG->wwwroot/local/datacleaner/

Options:
 -h, --help           Print out this help
     --filter         Filter to a single exact cleaner step name
     --run            Run the full datawashing process
     --run-pre-wash   Run the washing process for the pre-restore step
     --run-post-wash  Run the washing process for the post-restore step
     --dryrun         Print an overview of what would run
     --force          Skip all prod detection safety checks
 -v, --verbose        Be noisy about what is being done or would be done

Environment matrix options
     --reset          This will clear the configured items for other environments

Example:
\$sudo -u www-data /usr/bin/php local/datacleaner/cli/clean.php --run
";

if (!$options['run'] &&
    !$options['run-pre-wash'] &&
    !$options['run-post-wash'] &&
    !$options['filter'] &&
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

$filter = $options['filter'];
if ($filter) {
    echo "Filtering to ONLY run: $filter \n";
}

$cascade = null;

foreach ($plugins as $plugin) {
    // Get the class that does the work.
    $classname = 'cleaner_' . $plugin->name . '\clean';

    // Only run a certain cleaner.
    if ($filter) {
        if ($plugin->name != $filter) {
            continue;
        }
    }

    // Pre washing detection.
    // Skip subplugins that have a sort order that is greater or equal to 200.
    if ($options['run-pre-wash']) {
        if ($plugin->sortorder >= 200) {
            echo "NOTICE: Pre washing only. Skipping {$plugin->name} ({$plugin->sortorder}) cleaner.\n";
            continue;
        }
    }

    // Post washing detection.
    // Skip subplugins that have a sort order that is less than 200.
    if ($options['run-post-wash']) {
        if ($plugin->sortorder < 200) {
            echo "NOTICE: Post washing only. Skipping {$plugin->name} ({$plugin->sortorder}) cleaner.\n";
            continue;
        }
    }

    echo "== Running {$plugin->name} cleaner ==\n";
    if (!class_exists($classname)) {
        echo "ERROR: Unable to locate local/datacleaner/cleaner/{$plugin->name}/classes/clean.php class. Skipping.\n";
        continue;
    }

    $class = new $classname($options);

    if (is_null($cascade) && $class->needs_cascade_delete()) {
        $cascade = new \local_datacleaner\schema_add_cascade_delete($options);

        // Shutdown handler does the undo().
        $cascade->execute('user');
        $cascade->execute('course');
    }

    $class->execute();
}

echo "Done.\n";
