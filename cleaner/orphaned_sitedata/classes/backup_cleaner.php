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
 * backup_cleaner class.
 *
 * @package     cleaner_orphaned_sitedata
 * @author      Ghada El-Zoghbi <ghada@catalyst-au.net>
 * @author      Daniel Thee Roperto <daniel.roperto@catalyst-au.net>
 * @copyright   2016 Catalyst IT Australia {@link http://www.catalyst-au.net}
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace cleaner_orphaned_sitedata;

defined('MOODLE_INTERNAL') || die();

/**
 * backup_cleaner class.
 *
 * @package     cleaner_orphaned_sitedata
 * @author      Ghada El-Zoghbi <ghada@catalyst-au.net>
 * @author      Daniel Thee Roperto <daniel.roperto@catalyst-au.net>
 * @copyright   2016 Catalyst IT Australia {@link http://www.catalyst-au.net}
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class backup_cleaner {
    private $dryrun;

    public function __construct($dryrun) {
        $this->dryrun = $dryrun;
    }

    public function execute() {
        $count = $this->get_backups_files_count();
        clean::println(
            get_string(
                $this->dryrun ? 'woulddeletebackups' : 'willdeletebackups',
                'cleaner_orphaned_sitedata',
                $count
            )
        );

        if ($count > 0) {
            $this->delete_backup_files($count);
        }
    }

    /**
     * Delete backup files if not dryrun.
     *
     * @param $countdown
     */
    private function delete_backup_files($countdown) {
        global $DB;

        $sql = $this->get_backup_files_sql(
            'SELECT f.id, f.filesize, f.filepath, f.filename, f.contextid, f.component, f.filearea, f.itemid');
        $results = $DB->get_recordset_sql($sql);
        $filestorage = get_file_storage();

        foreach ($results as $file) {
            clean::debug(sprintf("[%05d] #%10d (%9s): %s%s",
                                 $countdown,
                                 $file->id,
                                 display_size($file->filesize),
                                 $file->filepath,
                                 $file->filename));
            $countdown--;

            $fileref = $filestorage->get_file(
                $file->contextid,
                $file->component,
                $file->filearea,
                $file->itemid,
                $file->filepath,
                $file->filename
            );

            if (!$fileref) {
                cli_error('Cannot find and delete: '.$fileref);
            }

            if (!$this->dryrun) {
                $fileref->delete();
            }
        }
    }

    private function get_backup_files_sql($select) {
        return "$select
                FROM {files} f
                INNER JOIN {context} c on f.contextid = c.id
                WHERE f.filename<>'.' AND f.component = 'backup' AND c.contextlevel = ".CONTEXT_COURSE;
    }

    private function get_backups_files_count() {
        global $DB;
        $sql = $this->get_backup_files_sql('SELECT COUNT(f.contenthash) AS count');
        return (int)$DB->get_record_sql($sql)->count;
    }
}
