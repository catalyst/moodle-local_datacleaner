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
 * Upgrade script for cleaner_users_upgrade.
 *
 * @package    cleaner_users
 * @author     Kevin Pham <kevinpham@catalyst-au.net>
 * @copyright  Catalyst IT, 2021
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

function xmldb_cleaner_users_upgrade($oldversion) {
    global $DB;

    if ($oldversion < 2021120100) {
        // Standardise user clean passwords on update.
        $sqllike = $DB->sql_like('email', ':email', false, false);
        $params = [
            'email' => '%cleaned@datacleaner.example%',
        ];
        $select = "$sqllike and password <> '" . AUTH_PASSWORD_NOT_CACHED . "'";
        $DB->set_field_select('user', 'password', AUTH_PASSWORD_NOT_CACHED, $select, $params);
        upgrade_plugin_savepoint(true, 2021120100, 'cleaner', 'users');
    }

    return true;
}
