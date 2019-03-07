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
     * Disable the scheduled tasks that we don't want.
     */
    static public function execute() {
        global $DB;
		$disabled_tasks = $DB->get_records('cleaner_scheduled_tasks');

		// Disable every task that has a record in our table.
		foreach ($disabled_tasks as $disabled_task) {
			$update = new \stdClass();
			$update->id = $disabled_task->task_scheduled_id;
			$update->disabled = 1;

			$DB->update_record('task_scheduled', $update);
		}
    }
}
