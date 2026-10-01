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
 * Upgrade script for clener_environment_matrix
 *
 * @package    cleaner_environment_matrix
 * @copyright  2017 Nicholas Hoobin <nicholashoobin@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Upgrade function for cleaner_environment_matrix.
 *
 * @param int $oldversion the version we are upgrading from
 * @return bool result
 */
function xmldb_cleaner_environment_matrix_upgrade($oldversion) {
    global $DB;

    $dbman = $DB->get_manager();

    if ($oldversion < 2017053000) {
        // Define field textarea to be added to cleaner_environment_matrixd.
        $table = new xmldb_table('cleaner_environment_matrixd');
        $field = new xmldb_field('textarea', XMLDB_TYPE_INTEGER, '10', null, null, null, '0', 'value');

        // Conditionally launch add field textarea.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Environment_matrix savepoint reached.
        upgrade_plugin_savepoint(true, 2017053000, 'cleaner', 'environment_matrix');
    }

    if ($oldversion < 2026010104) {
        // Define field classname to be added to cleaner_environment_matrixd.
        $table = new xmldb_table('cleaner_environment_matrixd');
        $field = new xmldb_field('classname', XMLDB_TYPE_CHAR, '100', null, null, null, null, 'textarea');

        // Conditionally launch add field classname.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Search each configs in database to find the classnames.

        $adminroot = admin_get_root();

        // Walk the admin tree once and build a lookup of [plugin][name] => classname.
        $classnames = [];
        $walk = function ($node) use (&$walk, &$classnames) {
            if ($node instanceof admin_settingpage) {
                foreach ($node->settings as $setting) {
                    $settingplugin = (empty($setting->plugin) ? 'core' : $setting->plugin);
                    $classnames[$settingplugin][$setting->name] = get_class($setting);
                }
            } else if ($node instanceof admin_category) {
                foreach ($node->get_children() as $child) {
                    $walk($child);
                }
            }
        };
        $walk($adminroot);

        // Walk through table records, updating the classname of each record.
        $records = $DB->get_records('cleaner_environment_matrixd');
        foreach ($records as $record) {
            $plugin = (empty($record->plugin) ? 'core' : $record->plugin);
            $name = $record->config;
            $classname = $classnames[$plugin][$name] ?? '';

            $record->classname = $classname;
            $DB->update_record('cleaner_environment_matrixd', $record);
        }

        // Environment_matrix savepoint reached.
        upgrade_plugin_savepoint(true, 2026010104, 'cleaner', 'environment_matrix');
    }

    return true;
}
