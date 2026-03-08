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

namespace cleaner_core;

/**
 * Data cleaner class for core.
 *
 * @package    cleaner_core
 * @copyright  2015 Nigel Cunningham <nigelc@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class clean extends \local_datacleaner\clean {
    /**
     * Task.
     */
    const TASK = 'Cleaning core tables';

    /**
     * Execute the cleaning process.
     */
    public static function execute() {
        global $DB, $CFG;

        // Get the settings.
        $config = get_config('cleaner_core');

        $deletemucfile = isset($config->deletemucfile) && $config->deletemucfile == 1 ? true : false;

        // Set the default directories.
        $mucdirectory = $CFG->dataroot . '/muc';

        $tables = $DB->get_tables();
        $tablelist = [];

        foreach ($tables as $table) {
            switch ($table) {
                case 'events_queue':
                case 'events_queue_handlers':
                case 'events_handlers':
                case 'task_adhoc':
                case 'message':
                case 'message_popup':
                case 'message_read':
                case 'message_working':
                case 'tool_monitor':
                    $tablelist[] = $table;
                    break;
                default:
                    if (
                        substr($table, 0, 5) == 'back_' ||
                        substr($table, 0, 6) == 'stats_' ||
                        substr($table, 0, 9) == 'sessions_' ||
                        substr($table, 0, 13) == 'webdav_locks_'
                    ) {
                        $tablelist[] = $table;
                    }
            }
        }

        sort($tablelist);

        if (self::$options['dryrun']) {
            // This always gets run.
            printf("\n\r " . get_string('wouldtruncatetables', 'cleaner_core', count($tablelist)) . "\n");
            foreach ($tablelist as $table) {
                printf("\r   - %s\n", $table);
            }

            if ($deletemucfile) {
                // There's only one file here.
                printf("\n\r " . get_string('woulddeletemuc', 'cleaner_core') . "\n");
            }
        } else {
            // This always gets run.
            printf("\n\r " . get_string('willtruncatetables', 'cleaner_core', count($tablelist)) . "\n");
            foreach ($tablelist as $table) {
                printf("\r   - %s\n", $table);
                $DB->delete_records($table);
            }

            if ($deletemucfile) {
                printf("\n\r " . get_string('willdeletemuc', 'cleaner_core') . "\n");
                if (!remove_dir($mucdirectory, true)) {
                    printf("\r " . get_string('errordeletingdir', 'local_datacleaner', $mucdirectory) . "\n");
                }
            }
        }

        printf("\n");
    }
}
