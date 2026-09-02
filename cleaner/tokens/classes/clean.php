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

use core\exception\moodle_exception;

/**
 * Data cleaner class for access tokens and keys.
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
        $config = get_config('cleaner_tokens');
        self::truncate_tables(self::parselist($config->tablestotruncate ?? null, defaults::DEFAULT_TABLES_TO_TRUNCATE));
        self::rehash_fields(self::parselist($config->fieldstorehash ?? null, defaults::DEFAULT_FIELDS_TO_REHASH));
        self::regenerate_fields(
            self::parselist($config->fieldstoregenerate ?? null, defaults::DEFAULT_FIELDS_TO_REGENERATE)
        );
    }

    /**
     * Parse a list of values. One value per line, separated by any EOLN value. Blanks lines are skipped.
     *
     * @param ?string $value The value to parse
     * @param array $defaults The default values to use if the value is null
     * @return array The parsed list of values
     */
    private static function parselist(?string $value, array $defaults): array {
        if ($value === null) {
            return $defaults;
        }
        $entries = array_map('trim', preg_split('/\r\n|\r|\n/', $value));
        return array_values(array_filter($entries, static fn($entry) => $entry !== ''));
    }

    /**
     * Truncate a list of tables.
     *
     * @param array $tables List of tables to truncate
     */
    public static function truncate_tables(array $tables) {
        global $DB;

        $dbman = $DB->get_manager();
        foreach ($tables as $table) {
            $table = trim($table);
            if (!$dbman->table_exists($table)) {
                mtrace("Table $table does not exist, skipping.");
                continue;
            }

            if (self::$options['dryrun']) {
                mtrace("Would truncate $table.");
            } else {
                $DB->delete_records($table);
                mtrace("Truncated table: $table");
            }
        }
    }

    /**
     * Deterministicly rehash values for a list of fields.
     *
     * @param array $fielddefs List of field definitions in the format "tablename:fieldname[:length]"
     */
    public static function rehash_fields(array $fielddefs) {
        global $DB;

        $dbman = $DB->get_manager();
        foreach ($fielddefs as $fielddef) {
            @[$tablename, $fieldname, $length] = explode(':', $fielddef);
            if (empty($fieldname)) {
                throw new moodle_exception('invalid_field_format', 'cleaner_tokens');
            }

            $tablename = trim($tablename);
            $fieldname = trim($fieldname);

            if (!$dbman->table_exists($tablename) || !$dbman->field_exists($tablename, $fieldname)) {
                mtrace("Field $tablename:$fieldname does not exist, skipping.");
                continue;
            }

            $numrecords = $DB->count_records($tablename);
            if (self::$options['dryrun']) {
                mtrace("Would rehash " . $numrecords . " value(s) for $tablename:$fieldname");
            } else {
                $sql = self::get_rehash_sql($fieldname);
                if ($length !== null) {
                    $sql = $DB->sql_substr($sql, 1, $length);
                }
                $DB->execute("UPDATE {{$tablename}} set $fieldname=($sql)");

                mtrace("Rehashed " . $numrecords . " value(s) for $tablename:$fieldname");
            }
        }
    }

    /**
     * Get SQL to rehash a field.
     *
     * @param string $fieldname
     * @return string
     * @throws moodle_exception
     */
    protected static function get_rehash_sql(string $fieldname): string {
        global $CFG;

        switch ($CFG->dbtype) {
            case 'pgsql':
                $sql = "encode(sha256($fieldname::bytea), 'hex')";
                break;
            case 'mysqli':
            case 'mariadb':
                $sql = "SHA2($fieldname, 256)";
                break;
            case 'sqlsrv':
                $sql = "CONVERT(varchar(64), HASHBYTES('SHA2_256', $fieldname), 2)";
                break;
            default:
                throw new moodle_exception('unsupported_dbtype', 'cleaner_tokens', null, $CFG->dbtype);
        }
        return $sql;
    }

    /**
     * Regenerate values for a list of fields.
     *
     * @param array $fielddefs List of field definitions in the format "tablename:fieldname[:length]"
     */
    public static function regenerate_fields(array $fielddefs) {
        global $DB;

        $dbman = $DB->get_manager();
        foreach ($fielddefs as $fielddef) {
            @[$tablename, $fieldname, $length] = explode(':', $fielddef);
            if (empty($fieldname)) {
                throw new moodle_exception('invalid_field_format', 'cleaner_tokens');
            }

            $tablename = trim($tablename);
            $fieldname = trim($fieldname);

            if (!$dbman->table_exists($tablename) || !$dbman->field_exists($tablename, $fieldname)) {
                mtrace("Field $tablename:$fieldname does not exist, skipping.");
                continue;
            }

            $numrecords = $DB->count_records($tablename);
            if (self::$options['dryrun']) {
                mtrace("Would regenerate " . $numrecords . " value(s) for $tablename:$fieldname");
            } else {
                $sql = self::get_random_string_sql();
                if ($length !== null) {
                    $sql = $DB->sql_substr($sql, 1, $length);
                }
                $DB->execute("UPDATE {{$tablename}} set $fieldname=($sql)");

                mtrace("Regenerated " . $numrecords . " value(s) for $tablename:$fieldname");
            }
        }
    }

    /**
     * Get SQL to generate a random string.
     *
     * @return string
     * @throws moodle_exception
     */
    protected static function get_random_string_sql(): string {
        global $CFG;

        switch ($CFG->dbtype) {
            case 'pgsql':
                $sql = "MD5(RANDOM()::text)";
                break;
            case 'mysqli':
            case 'mariadb':
                $sql = "MD5(RAND())";
                break;
            case 'sqlsrv':
                $sql = "replace(CONVERT(varchar(36), NEWID()) + CONVERT(varchar(36), NEWID()), '-','')";
                break;
            default:
                throw new moodle_exception('unsupported_dbtype', 'cleaner_tokens', null, $CFG->dbtype);
        }
        return $sql;
    }
}
