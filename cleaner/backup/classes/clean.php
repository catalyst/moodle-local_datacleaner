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

namespace cleaner_backup;

/**
 * Data cleaner class for backup.
 *
 * @package    cleaner_backup
 * @copyright  2020 Peter Burnett <peterburnett@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class clean extends \local_datacleaner\clean {
    /**
     * Task.
     */
    const TASK = 'Deleting course backups';

    /**
     * Returns the SQL fragment and params for matching backup files by filename.
     *
     * @return array [$sql_fragment, $params]
     */
    private static function get_backup_like_sql(): array {
        global $DB;
        return [$DB->sql_like('filename', ':like'), ['like' => '%.mbz']];
    }

    /**
     * Execute the cleaning process.
     */
    public static function execute() {
        global $DB;

        $dryrun = (bool)self::$options['dryrun'];

        if ($dryrun) {
            [$likefrag, $params] = self::get_backup_like_sql();
            $count = $DB->count_records_select('files', $likefrag, $params);
            echo "Would delete {$count} backup (.mbz) file records.\n";
            return;
        }

        self::new_task(1);
        self::delete_backups();
        self::next_step();
    }

    /**
     * Delete backup files.
     */
    public static function delete_backups() {
        global $DB;
        $storage = get_file_storage();

        $fastdelete = get_config('cleaner_backup', 'fastdelete');
        [$likefrag, $params] = self::get_backup_like_sql();

        // If this is a fast delete, do a quick delete from files table and return.
        if ($fastdelete) {
            $DB->execute("DELETE FROM {files} WHERE " . $likefrag, $params);
            return;
        }

        // Do a "proper" delete.
        $rs = $DB->get_recordset_select('files', $likefrag, $params);

        foreach ($rs as $record) {
            // Get the file record, then delete it from table.
            $file = $storage->get_file_instance($record);

            if ($file) {
                $file->delete();
            }
        }
        $rs->close();
    }
}
