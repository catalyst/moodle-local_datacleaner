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

        $DB->delete_records('events_queue');
        $DB->delete_records('task_adhoc');
        $DB->delete_records('message');
        $DB->delete_records('back_');
        $DB->delete_records('sessions');
        $DB->delete_records('stats_');
        $DB->delete_records('tool_monitor');
        $DB->delete_records('webdav_locks');
    }
}
