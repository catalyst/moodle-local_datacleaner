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
 * Upgrade logic.
 *
 * @package    local_datacleaner
 * @copyright  2024 Catalyst IT
 * @author     Scott Verbeek <scottverbeek@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


/**
 * Performs data migrations and updates on upgrade.
 *
 * @param int $oldversion
 * @return bool
 */
function xmldb_local_datacleaner_upgrade($oldversion = 0): bool{
    global $DB;
    $dbman = $DB->get_manager();
    if ($oldversion < 2022020301) {
        // Clean up table.
        $table = new xmldb_table('cleaner_muc_configs');
        if ($dbman->table_exists($table)) {
            $dbman->drop_table($table);
        }

        upgrade_plugin_savepoint(true, 2022020301, 'local', 'datacleaner');
    }

    return true;
}
