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
 * @package     cleaner_scheduled_tasks
 * @copyright   2019 Catalyst IT Australia {@link http://www.catalyst-au.net}
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace cleaner_scheduled_tasks\form;
use html_writer;
use moodleform;
use stdClass;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once("{$CFG->libdir}/formslib.php");

class task_form extends moodleform {
	function definition() {
		$mform = $this->_form;
		$tasks = $this->_customdata;

		if (!$tasks) {
			throw new coding_exception('You have no scheduled tasks, cannot display the task disabling form.');
		}

		// Display a header on the page.
		// Need to put lang strings into lang folder later.
		$header = html_writer::tag('h2', 'Scheduled task disabling form.', ['class' => 'scheduled_task_header']);
		$header_subtitle = html_writer::tag('p', 'Select a task to disable it in the pre wash task. Unselected tasks will be unaffected.', ['class' => 'scheduled_task_header_subtitle']);

		$header_array = [];
		$header_array[] = &$mform->createElement('static', 'stitle', 'stitle', "$header $header_subtitle");
		$mform->addGroup($header_array, 'header_array', '' , ' ', false);


		// Should have a checkbox here to select/deselect all

		global $DB;
		$cleaner_tasks = $DB->get_records_sql("select * from {cleaner_scheduled_tasks} cs
												join {task_scheduled} ts on ts.id = cs.task_scheduled_id");

		$i = 0;
		$add_component = [];
		$render_tasks = [];

		// Now create an element for each task.
		foreach ($tasks as $task) {

			$component = $task->get_component();
			$name = $task->get_name();
			$class = get_class($task);


			$status = ($task->get_disabled() == 0) ? "enabled" : "disabled";


			// Key by which returned data is group on in the associative array, must be unique for each task.
			$cbkey = "$class";
			$render_tasks[] = &$mform->createElement('advcheckbox', $cbkey, '', "$name: Currently $status", ['group' => 1]);

			// We have our current saved settings as the default value.
			$default = 0;

			foreach ($cleaner_tasks as $cleaner_task) {
				if ($cleaner_task->component == $component && $cleaner_task->classname == "\\$class") {
					$default = 1;
				}
			}

			$mform->setDefault($cbkey, "$default");

			// Group everything by component here
			$add_component[] = $component;
			// If the component is the same as the previous component, we continue to make elements before adding as a group
			if (isset ($add_component[1]) && $component == $add_component[$i-1]) {
				$i++;
				continue;
			}
			$i++;

			// If this group we are about to add has more than 3 elements, let's add a check all box.


			// Add this group of elements to the form and display them.
			$mform->addGroup($render_tasks, "$class", "$component", array(' '), false);
			$render_tasks = [];
		}

		$this->add_checkbox_controller(1, null, null, 0);

		// Display save and cancel buttons at bottom of the form
		$buttonarray = array();
		$buttonarray[] = &$mform->createElement('submit', 'submitbutton', get_string('savechanges'), ['class' => 'cb_header']);
		$buttonarray[] = &$mform->createElement('cancel');
		$mform->addGroup($buttonarray, 'buttonar', '', array(' '), false);
		$mform->closeHeaderBefore('buttonar');
	}
}
