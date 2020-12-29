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
 * @package    cleaner_scheduled_tasks
 * @copyright  2019 Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace cleaner_scheduled_tasks;

defined('MOODLE_INTERNAL') || die();

class clean extends \local_datacleaner\clean {

    /**
     * Get the settings section url.
     * @return \moodle_url the settings section URL
     */
    public static function get_settings_section_url($name) {
        return new \moodle_url('/local/datacleaner/cleaner/scheduled_tasks/index.php');
    }

    /**
     * Disable the scheduled tasks that we don't want.
     */
    static public function execute() {
        global $DB;
        $dryrun = self::$options['dryrun'];

        $disabledtasks = $DB->get_records_sql("SELECT *
                                                FROM {cleaner_scheduled_tasks} cst
                                                JOIN {task_scheduled} ts ON ts.id=cst.taskscheduledid");

        $count = count($disabledtasks);
        $increment = 1;
        $taskstoupdate = array();

        if ($count == 0) {
            mtrace("No tasks selected to disable, skipping this task");
            return;
        }

        if ($dryrun) {
            mtrace("Would disable the {$count} following tasks:");
        } else {
            mtrace("Disabling the {$count} following tasks:");
        }

        // Disable every task that has a record in our table.
        foreach ($disabledtasks as $disabledtask) {
            if ($disabledtask->disabled == 1) {
                mtrace("Task $increment/$count: $disabledtask->classname selected to disable but is already disabled, skipping..");
                $increment++;
            } else {
                if ($dryrun) {
                    mtrace("Task $increment/$count: Would disable task: $disabledtask->classname");
                    $increment++;
                } else {
                    mtrace("Task $increment/$count: Disabling task: $disabledtask->classname");

                    // Collect all the tasks to update and update in one query.
                    $taskstoupdate[] = $disabledtask->taskscheduledid;
                    $increment++;
                }
            }
        }
        if ($taskstoupdate) {
            list($sql, $params) = $DB->get_in_or_equal($taskstoupdate);
            $DB->set_field_select('task_scheduled', 'disabled', 1, "id $sql", $params);
        }
    }
}
