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
 * Completion cleaner.
 *
 * @package    cleaner_backup
 * @copyright  2020 Peter Burnett <peterburnett@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace cleaner_backup;

defined('MOODLE_INTERNAL') || die();

class clean extends \local_datacleaner\clean {
    const TASK = 'Deleting course backups';

    /**
     * Execute the cleaner.
     */
    public static function execute() {
        self::new_task(1);
        self::delete_backups();
        self::next_step();
    }

    public static function delete_backups() {
        global $DB;
        $storage = get_file_storage();

        $fastdelete = get_config('cleaner_backup', 'fastdelete');
        $likefrag = $DB->sql_like('filename', ':like');
        $like = '%.mbz';

        // If this is a fast delete, do a quick delete from files table and return.
        if ($fastdelete) {
            $sql = "DELETE FROM {files}
                     WHERE " . $likefrag;

            $DB->execute($sql, ['like' => $like]);
            return;
        }

        // Do a "proper" delete.
        $sql = "SELECT *
                  FROM {files}
                 WHERE " . $likefrag;
        $rs = $DB->get_recordset_sql($sql, ['like' => $like]);

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
