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
 *
 * @package    cleaner_scheduled_tasks
 * @copyright  2019 Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../../../config.php');
require_once($CFG->libdir . '/adminlib.php');

admin_externalpage_setup('cleaner_scheduled_tasks_settings');

$PAGE->add_body_class('cleaner_scheduled_tasks');

// grab the data that we are going to display. this is a list of all scheduled tasks.
$tasks = \core\task\manager::get_all_scheduled_tasks();

// Grab this url to redirect to.
$post = new moodle_url('/local/datacleaner/cleaner/scheduled_tasks/index.php');

// Then send this data to the form
$taskform = new \cleaner_scheduled_tasks\form\task_form($post, $tasks);

// We have created the form with the correct fields and data, but we don't want to display this one.
if ($taskform->is_cancelled()) {
    // redirect to settings page if we cancelled.
    redirect($post);
} else if ($data = $taskform->get_data()) {
    // If we submit the form, then we should look at the data here and for each record insert the data into our cleaner_scheduled_tasks table.
    global $DB;

    $taskdata = isset($data->selected) ? $data->selected : false;
    $taskdata = $taskdata ? $taskdata : (array)$data;

    $scheduled_tasks = $DB->get_records('task_scheduled');

    foreach ($taskdata as $key => $task_enabled) {
        foreach ($scheduled_tasks as $scheduled_task) {
            if ("\\$key" == $scheduled_task->classname) {
                $record = $DB->get_record('cleaner_scheduled_tasks', ['task_scheduled_id' => $scheduled_task->id]);
                if ($record && $task_enabled == 0) {
                    // we have a record in our table but haven't selected it in our form. should be deleted.
                    $DB->delete_records('cleaner_scheduled_tasks', ['task_scheduled_id' => $scheduled_task->id]);
                } else if ($record && $task_enabled == 1) {
                    // The record already exists in our table with the correct setting, no update needed.
                    continue;
                } else if (!$record && $task_enabled == 1) {
                    // The record doesn't exist, but it should because we selected it, insert it
                    $task_insert = new stdClass;
                    $task_insert->task_scheduled_id = $scheduled_task->id;
                    $task_insert->lastmodified = time();

                    $DB->insert_record('cleaner_scheduled_tasks', $task_insert);
                }
            }
        }
    }
}

// If we are here, then we are just displaying the form, and haven't cancelled or submitted it on this page.
echo $OUTPUT->header();
$taskform->display();
echo $OUTPUT->footer();
