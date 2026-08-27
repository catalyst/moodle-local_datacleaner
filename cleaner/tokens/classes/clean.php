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

namespace cleaner_tokens;

/**
 * Data cleaner class for access tokens and keys.
 *
 * Simply seletes a
 *
 * Note: User passwords are cleaned in the cleaner_users plugin.
 *
 * @package    cleaner_tokens
 * @copyright  2026 Catalyst IT
 * @author     Jason den Dulk <jasondendulk@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class clean extends \local_datacleaner\clean {
    /**
     * Execute the cleaning process.
     */
    public static function execute() {
        self::clean_user_password_history();
        self::clean_user_password_resets();
        self::clean_external_tokens();
        self::clean_registration_hubs();
        self::clean_user_private_key();
        self::clean_oauth2();
        self::clean_extra_tables();
    }

    /**
     * Clean (truncate) user password history table.
     */
    public static function clean_user_password_history() {
        global $DB;

        if (self::$options['dryrun']) {
            mtrace("Would delete user password history.");
        } else {
            $DB->delete_records('user_password_history');
            mtrace("Deleted user password history.");
        }
    }

    /**
     * Clean (truncate) user password resets table.
     */
    public static function clean_user_password_resets() {
        global $DB;

        if (self::$options['dryrun']) {
            mtrace("Would delete user password resets.");
        } else {
            $DB->delete_records('user_password_resets');
            mtrace("Deleted user password resets.");
        }
    }

    /**
     * Clean (truncate) external tokens table.
     */
    public static function clean_external_tokens() {
        global $DB;

        if (self::$options['dryrun']) {
            mtrace("Would delete all web service tokens.");
        } else {
            $DB->delete_records('external_tokens');
            mtrace("Deleted all web service tokens.");
        }
    }

    /**
     * Clean (truncate) registration hubs table.
     */
    public static function clean_registration_hubs() {
        global $DB;

        if (self::$options['dryrun']) {
            mtrace("Would delete all registration hubs.");
        } else {
            $DB->delete_records('registration_hubs');
            mtrace("Deleted all registration hubs.");
        }
    }

    /**
     * Clean (truncate) private user access keys table.
     */
    public static function clean_user_private_key() {
        global $DB;

        if (self::$options['dryrun']) {
            mtrace("Would delete all private user access keys.");
        } else {
            $DB->delete_records('user_private_key');
            mtrace("Deleted all private user access keys.");
        }
    }

    /**
     * Clean (truncate) OAuth2 token data.
     */
    public static function clean_oauth2() {
        global $DB;

        if (self::$options['dryrun']) {
            mtrace("Would delete all OAuth2 token data.");
        } else {
            $DB->delete_records('oauth2_issuer');
            $DB->delete_records('oauth2_system_account');
            $DB->delete_records('oauth2_access_token');
            $DB->delete_records('oauth2_refresh_token');
            mtrace("Deleted all OAuth2 token data.");
        }
    }

    /**
     * Clean (truncate) the table specified in the config option.
     */
    public static function clean_extra_tables() {
        global $DB;

        $tables = trim(get_config('cleaner_tokens', 'extratables'));
        if (!empty($tables)) {
            $tables = str_replace("\r", "", $tables);
            $tables = explode("\n", $tables);
            $tables = array_map('trim', $tables);
            $dbman = $DB->get_manager();
            $goodtables = [];
            $badtables = [];
            foreach ($tables as $table) {
                if ($dbman->table_exists($table)) {
                    $goodtables[] = $table;
                } else {
                    $badtables[] = $table;
                }
            }
            $badtables = implode(', ', $badtables);
            if (self::$options['dryrun']) {
                $goodtables = implode(', ', $goodtables);
                mtrace("Would delete the following tables: $goodtables");
                if (!empty($badtables)) {
                    mtrace("The following tables do not exist and would be skipped: $badtables");
                }
            } else {
                foreach ($goodtables as $table) {
                    $DB->delete_records($table);
                }
                $goodtables = implode(', ', $goodtables);
                mtrace("Deleted the following tables: $goodtables");
                if (!empty($badtables)) {
                    mtrace("The following tables do not exist and were skipped: $badtables");
                }
            }
        }
    }
}
