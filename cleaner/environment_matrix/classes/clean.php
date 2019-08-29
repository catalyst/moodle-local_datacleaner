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

require_once($CFG->libdir.'/adminlib.php');

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
     * Get the settings section url.
     * @return \moodle_url the settings section URL
     */
    public static function get_settings_section_url($name) {
        return new \moodle_url('/local/datacleaner/cleaner/environment_matrix/index.php');
    }

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

                // Set Admin User for admin_write_settings perms
                \core\session\manager::set_user(get_admin());

                // Process settings.
                foreach ($matrixdata as $plugin => $items) {
                    foreach ($items as $name => $env) {
                        $config = $env[$environment->id];

                        // set_config requires a null 'plugin' value when updating core configuration values.
                        $config->plugin = ($config->plugin == 'core') ? null : $config->plugin;

                        // First, set config in database
                        if ($verbose) {
                            mtrace("set_config('{$config->config}', '{$config->value}', '{$config->plugin}')");
                        }
                        if (!$dryrun) {
                            set_config($config->config, $config->value, $config->plugin);
                        }

                        // Generate an admin settings tree
                        $admintree = admin_get_root(true);

                        // Get strings in nicer format for reuse
                        $configname = $config->config;
                        $pluginname = $config->plugin;
                        $elementname = $pluginname.$configname;

                        // Search the admintree for configname
                        $nodes = $admintree->search($configname);
                        $relevantobject = '';

                        // Iterate through tree for specific page and elementname
                        foreach ($nodes as $node) {
                            if ($node->page instanceof \admin_settingpage && isset($node->page->settings->$elementname)) {
                                // Should only ever be reached once, so break loop
                                $relevantobject = $node->page->settings->$elementname;
                                break;
                            }
                        }

                        // Now perform any additional validation
                        if ($verbose) {
                            // Show the additional write_settings
                            mtrace("{$config->plugin}:{$relevantobject->name}->write_setting('{$config->value}')");
                        }
                        if ($relevantobject != '' && $relevantobject->plugin == $pluginname) {
                            // Get setting object back out of config control
                            $settings = $relevantobject->get_setting();
                            // Reset to fire additional validation/actions
                            $errors = $relevantobject->write_setting($settings);
                            // log any errors that might have been thrown
                            if ($errors != '') {
                                mtrace($errors);
                            }
                        }
                    }

                }

                break;
            }

        }

        self::next_step();
    }
}
