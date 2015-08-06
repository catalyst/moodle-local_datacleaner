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
 * @package    cleaner_courses
 * @copyright  2015 Brendan Heywood <brendan@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace cleaner_courses;

defined('MOODLE_INTERNAL') || die();

class clean extends \local_datacleaner\clean {

    /**
     * Delete a single course.
     *
     * @param int $id The course id.
     */
    static private function delete_course($id) {
        delete_course($id, false);
    }

    /**
     * Get an array of course objects meeting the criteria provided
     *
     * @param  array $criteria An array of criteria to apply.
     * @return array $result   The array of matching course objects.
     */
    private static function get_courses($criteria = array()) {
        global $DB;

        $extrasql = '';
        $params = array();

        if (isset($criteria['timestamp'])) {
            $extrasql .= ' AND startdate <= :startdate ';
            $params['startdate'] = $criteria['timestamp'];
        }

        return $DB->get_records_select_menu('course', 'id > 1 ' . $extrasql, $params, '', 'id, id');
    }

    static public function execute() {
        global $DB;

        $task = 'Removing old courses';

        // Get the settings, handling the case where new ones (dev) haven't been set yet.
        $config = get_config('cleaner_courses');

        $interval = isset($config->minimumage) ? $config->minimumage : 365;

        $criteria = array();
        $criteria['timestamp'] = time() - ($interval * 24 * 60 * 60);

        $courses = self::get_courses($criteria);
        $numcourses = count($courses);

        if (!$numcourses) {
            echo "No courses need deletion.\n";
            return;
        }

        self::update_status($task, 0, $numcourses);
        $done = 0;

        foreach ($courses as $id => $course) {
            self::delete_course($id);
            $done++;
        self::update_status($task, $done, $numcourses);
        }
    }
}

