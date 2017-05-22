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
 * Settings for Environment matrix.
 *
 * @package    cleaner_environment_matrix
 * @author     Nicholas Hoobin <nicholashoobin@catalyst-au.net>
 * @copyright  2017 Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('CLI_SCRIPT', true);
require_once(__DIR__ . '/../../../../../config.php');
require_once($CFG->libdir.'/clilib.php');

// Now get cli options.
list($options, $unrecognized) = cli_get_params(
    array(
        'help' => false,
        'run' => false,
        'verbose' => false,
        'reset' => false,
    ),
    array('h' => 'help')
);

if ($unrecognized) {
    $unrecognized = implode("\n  ", $unrecognized);
    cli_error(get_string('cliunknowoption', 'admin', $unrecognized));
}

$help = "Standalone Environment matrix cleaner.

Options:
 -h, --help     Print out this help
     --run      Actually run the clean process
     --verbose  Be noisey about what is being done or would be done
     --reset    This will clear the configured items for other environments.

Example:
\$sudo -u www-data /usr/bin/php local/datacleaner/cleanerenvironment_matrix/cli/matrix_replace.php --run
";

if (!$options['run']) {
    echo $help;
    die;
}

if ($options['help']) {
    echo $help;
    die;
}

$options['dryrun'] = 0;

$cleaner = new cleaner_environment_matrix\clean();

// Cheat a little.
$reflection = new \ReflectionProperty(get_class($cleaner), 'options');
$reflection->setAccessible(true);
$reflection->setValue($cleaner, $options);

$cleaner::execute();
