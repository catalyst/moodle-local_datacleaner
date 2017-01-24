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
 * orphan_cleaner class.
 *
 * @package     cleaner_orphaned_sitedata
 * @author      Ghada El-Zoghbi <ghada@catalyst-au.net>
 * @author      Daniel Thee Roperto <daniel.roperto@catalyst-au.net>
 * @copyright   2016 Catalyst IT Australia {@link http://www.catalyst-au.net}
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace cleaner_orphaned_sitedata;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

defined('MOODLE_INTERNAL') || die();

/**
 * orphan_cleaner class.
 *
 * @package     cleaner_orphaned_sitedata
 * @author      Ghada El-Zoghbi <ghada@catalyst-au.net>
 * @author      Daniel Thee Roperto <daniel.roperto@catalyst-au.net>
 * @copyright   2016 Catalyst IT Australia {@link http://www.catalyst-au.net}
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class orphan_cleaner {
    private $dryrun;

    private $deletecount = 0;

    private $deletebytes = 0;

    public function __construct($dryrun) {
        $this->dryrun = $dryrun;
    }

    public function execute() {
        clean::println(
            get_string(
                $this->dryrun ? 'woulddeleteorphanedfiles' : 'willdeleteorphanedfiles',
                'cleaner_orphaned_sitedata'
            )
        );

        $this->delete_orphaned_files();
    }

    private function delete_orphaned_files() {
        global $CFG;

        $directories = [$CFG->dataroot.'/filedir'];

        $backup = get_config('backup')->backup_auto_destination;
        if ($backup != '') {
            $directories[] = $backup;
        }

        foreach ($directories as $directory) {
            if (!is_dir($directory)) {
                clean::debug('Directory not found, skipping: '.$directory);
            }
            $this->delete_orphaned_files_from(realpath($directory));
        }
    }

    private function delete_orphaned_files_from($directory) {
        clean::debug(__METHOD__."('{$directory}')");

        for ($i = 0x00; $i <= 0xFF; $i++) {
            $hex = sprintf('%02x', $i);
            $subdir = $directory.DIRECTORY_SEPARATOR.$hex;
            if (!is_dir($subdir)) {
                continue;
            }
            $this->delete_orphaned_files_by_first_hash_byte($subdir);
        }
    }

    private function delete_orphaned_files_by_first_hash_byte($directory) {
        clean::debug(__FUNCTION__."('{$directory}')");

        $firstbyte = basename($directory);
        $dbfiles = $this->get_database_files_starting_with($firstbyte);

        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($directory, RecursiveDirectoryIterator::SKIP_DOTS)
        );
        foreach ($files as $file) {
            if (!is_file($file)) {
                continue;
            }
            $filename = basename($file);

            if (array_key_exists($filename, $dbfiles)) {
                continue;
            }

            $this->deletecount++;
            $this->deletebytes += filesize($file);
            clean::debug(sprintf('(%5d files, %7s) Orphaned: %s',
                                 $this->deletecount,
                                 display_size($this->deletebytes),
                                 $filename));
            if (!$this->dryrun) {
                unlink($file);
            }
        }
    }

    private function get_database_files_starting_with($firstbyte) {
        global $DB;
        $firstbyte .= '%';
        return $DB->get_records_select(
            'files',
            'contenthash LIKE ?',
            [$firstbyte],
            'contenthash ASC',
            'DISTINCT contenthash');
    }
}
