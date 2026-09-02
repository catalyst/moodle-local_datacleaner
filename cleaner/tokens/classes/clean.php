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
        self::truncate_tables(explode("\r\n", trim($config->tablestotruncate)));
        self::rehash_fields(explode("\r\n", trim($config->fieldstorehash)), $config->rehashseed);
        self::regenerate_fields(explode("\r\n", trim($config->fieldstoregenerate)));
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
     * @param int $seed The seed to use for the hash
     */
    public static function rehash_fields(array $fielddefs, int $seed) {
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

            $values = $DB->get_records_menu($tablename, null, null, "id," . $fieldname);
            if (self::$options['dryrun']) {
                mtrace("Would rehash " . count($values) . " value(s) for $tablename:$fieldname");
            } else {
                foreach ($values as $id => $value) {
                    $DB->set_field(
                        $tablename,
                        $fieldname,
                        self::rehash_value($value, $length ?? null, $seed),
                        ['id' => $id]
                    );
                }
                mtrace("Rehashed " . count($values) . " value(s) for $tablename:$fieldname");
            }
        }
    }

    /**
     * Generate a deterministic rehash of a value.
     *
     * @param string $value The value to rehash
     * @param int|null $length The length of the value to truncate to, if set.
     * @param int $seed The seed to use for the hash
     * @return string The generated value
     */
    public static function rehash_value(string $value, ?int $length, int $seed): string {
        $newvalue = hash('xxh128', $value, false, ['seed' => $seed]);
        if ($length !== null) {
            $newvalue = substr($newvalue, 0, $length);
        }
        return $newvalue;
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

            $values = $DB->get_records_menu($tablename, null, null, "id," . $fieldname);
            if (self::$options['dryrun']) {
                mtrace("Would regenerate " . count($values) . " value(s) for $tablename:$fieldname");
            } else {
                foreach ($values as $id => $value) {
                    $DB->set_field($tablename, $fieldname, self::regenerate_value($length ?? null), ['id' => $id]);
                }
                mtrace("Regenerated " . count($values) . " value(s) for $tablename:$fieldname");
            }
        }
    }

    /**
     * Generate a replacement value of the specified length.
     *
     * @param int|null $length the length of the value to generate
     * @return string the generated value
     */
    public static function regenerate_value(?int $length = null): string {
        return random_string($length ?? 64);
    }
}
