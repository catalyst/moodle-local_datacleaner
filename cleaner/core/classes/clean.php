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
 * @package    cleaner_core
 * @copyright  2015 Catalyst IT
 * @author     Nigel Cunningham
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace cleaner_core;

defined('MOODLE_INTERNAL') || die();

class clean extends \local_datacleaner\clean {
    const TASK = 'Removing config settings';

    /**
     * Do the work of truncating any unneeded tables.
     */
    static public function execute() {
        global $DB, $CFG;

        // Get the settings.
        $config = get_config('cleaner_core');

        $delete_muc_file = isset($config->deletemucfile) && $config->deletemucfile == 1 ? true : false;

        // Set the default directories.
        $muc_directory = $CFG->dataroot . '/muc';

        $tables = $DB->get_tables();
        $tablelist = array();

        foreach ($tables as $table) {
            switch ($table) {
                case 'events_queue':
                case 'task_adhoc':
                case 'message':
                case 'tool_monitor':
                    $tablelist[] = $table;
                default:
                    if (substr($table, 0, 5) == 'back_' ||
                        substr($table, 0, 6) == 'stats_' ||
                        substr($table, 0, 9) == 'sessions_' ||
                        substr($table, 0, 13) == 'webdav_locks_') {
                        $tablelist[] = $table;
                    }
            }
        }

        if (self::$dryrun) {
            // This always gets run.
            printf("\n\r " . get_string('wouldtruncatetables', 'cleaner_core', count($tablelist)) . "\n");

            if ($delete_muc_file) {
                // There's only one file here.
                printf("\n\r " . get_string('woulddeletemuc', 'cleaner_core') . "\n");
            }

        } else {
            // This always gets run.
            printf("\n\r " . get_string('willtruncatetables', 'cleaner_core', count($tablelist)) . "\n");
            foreach ($tablelist as $table) {
                $DB->delete_records($table);
            }

            if ($delete_muc_file) {
                printf("\n\r " . get_string('willdeletemuc', 'cleaner_core') . "\n");
                if (!remove_dir($muc_directory, true)) {
                    printf("\r " . get_string('errordeletingdir', 'local_datacleaner', $muc_directory) . "\n");
                }
            }

        }

        printf("\n");

    }
}
