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

namespace cleaner_scheduled_tasks;
use moodleform;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once("{$CFG->libdir}/formslib.php");

class task_form extends moodleform {
    function definition() {
        $mform = $this->_form;


        echo "are we even here";
        die;
        // We want to display a disable setting for every scheduled task.
        $tasks = \core\task\manager::get_all_scheduled_tasks();
        $environmentheader = [];
        //$environmentheader[] = &$mform->createElement('advcheckbox', '', '', '', ['class' => 'hiddencb'], [0, 1]);
        $this->_form->addElement(
            'filemanager',
            'mucfiles',
            '',
            null,
            ['subdirs' => false]
        );


        foreach ($tasks as $task) {
            $class = preg_replace('{\\\}', '_', get_class($task));
            $component = $task->get_component();

            $taskname = substr(strchr($class, "_task_"),6);

            if ($taskname == false) {
                continue;
            }

        }

    }
}
