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
 * @author     Nicholas Hoobin <nicholashoobin@catalyst-au.net>
 * @copyright  2017 Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.'); // It must be included from a Moodle page.
}

/**
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

    return true;
}
