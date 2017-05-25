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
 * Cleaner.
 *
 * @package    cleaner_environment_matrix
 * @author     Nicholas Hoobin <nicholashoobin@catalyst-au.net>
 * @copyright  2017 Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


namespace cleaner_environment_matrix;

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.'); // It must be included from a Moodle page.
}

/**
 * Clean class for Environment matrix.
 *
 * @package    cleaner_environment_matrix
 * @author     Nicholas Hoobin <nicholashoobin@catalyst-au.net>
 * @copyright  2017 Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class clean extends \local_datacleaner\clean {
    /** @var string The name of the task. */
    const TASK = 'Environment matrix configuration';

    /**
     * Do the work.
     */
    static public function execute() {
        global $CFG;

        $dryrun = (bool)self::$options['dryrun'];
        $verbose = (bool)self::$options['verbose'];
        $reset = (bool)self::$options['reset'];

        self::debugmemory();

        $environments = local\matrix::get_environments();

        self::new_task(1);

        foreach ($environments as $environment) {

            // This should only match once.
            if ($environment->wwwroot == $CFG->wwwroot) {
                // Lets clean up the rest of the data.
                if ($reset) {
                    mtrace("Purging other environments data.");

                    if (!$dryrun) {
                        local\matrix::purge_data_except_environment($environment->id);
                    }

                }

                // Obtain the data for this environment only.
                $matrixdata = local\matrix::get_matrix_data($environment);

                // Process settings.
                foreach ($matrixdata as $plugin => $items) {
                    foreach ($items as $name => $env) {
                        $config = $env[$environment->id];

                        // set_config requires a null 'plugin' value when updating core configuration values.
                        $config->plugin = ($config->plugin == 'core') ? null : $config->plugin;

                        if ($verbose) {
                            mtrace("set_config('{$config->config}', '{$config->value})'");
                        }

                        if (!$dryrun) {
                            set_config($config->config, $config->value, $config->plugin);
                        }

                    }

                }

                break;
            }

        }

        self::next_step();
    }
}
