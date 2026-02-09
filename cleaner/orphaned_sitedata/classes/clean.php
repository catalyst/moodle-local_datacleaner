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

namespace cleaner_orphaned_sitedata;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/moodlelib.php');

/**
 * Data cleaner class for orphaned sitedata.
 *
 * @package    cleaner_orphaned_sitedata
 * @copyright  2015 Ghada El-Zoghbi <ghada@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class clean extends \local_datacleaner\clean {
    /**
     * Task name.
     */
    const TASK = 'Delete orphaned sitedata files';

    /**
     * Execute the cleaning process.
     */
    public static function execute() {
        $config = get_config('cleaner_orphaned_sitedata');
        $dryrun = (bool)self::$options['dryrun'];

        $cleaners = [
            'deletebackups' => backup_cleaner::class,
            'deletecachedfiles' => cache_cleaner::class,
            'deletetmpfiles' => tempfiles_cleaner::class,
            'deleteorphanedfiles' => orphan_cleaner::class,
        ];

        self::debugmemory();
        foreach ($cleaners as $option => $cleaner) {
            if (!isset($config->$option) || !$config->$option) {
                continue;
            }
            self::debug($cleaner . ' starting...');
            (new $cleaner($dryrun))->execute();
            self::debug($cleaner . ' finished!');
            self::debugmemory();
        }
    }
}
