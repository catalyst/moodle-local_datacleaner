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

admin_externalpage_setup('cleaner_scheduled_tasks');

$post = new moodle_url('/local/datacleaner/cleaner/scheduled_tasks/index.php');

$taskform = new \cleaner_scheduled_tasks\task_form();

// We have created the form with the correct fields and data, but we don't want to display this one.
if ($taskform->is_cancelled()) {
    // redirect to settings page if we cancelled.
    redirect($post);
} else if ($data = $taskform->get_data()) {
    // If we submit the form, then we should look at the data here and for each record insert the data into our cleaner_scheduled_tasks table.
    echo "great we are here";
    die;
}

