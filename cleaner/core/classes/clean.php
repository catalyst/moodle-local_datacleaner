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
 * @package    cleaner_config
 * @copyright  2015 Catalyst IT
 * @author     Nigel Cunningham
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace cleaner_config;

defined('MOODLE_INTERNAL') || die();

class clean extends \local_datacleaner\clean {
    const TASK = 'Removing config settings';

    /**
     * Do the hard work of removing config settings.
     */
    static public function execute() {
        global $DB;

        $tables = $DB->get_tables();
        $tablelist = array();

        foreach($tables as $table) {
            switch ($table) {
                case 'events_queue':
                case 'task_adhoc':
                case 'message':
                case 'tool_monitor':
                case 'webdav_locks':
                    $tablelist[] = $table;
                default:
                    if (substr($table, 0, 5) == 'back_' ||
                        substr($table, 0, 6) == 'stats_') {
                        $tablelist[] = $table;
                    }
            }
        }

        if (self::$dryrun) {
            echo "Would truncate " . count($tablelist) . " tables.\n";
        } else {
            foreach($tablelist as $table) {
                $DB->delete_records($table);
            }
        }
    }
}
