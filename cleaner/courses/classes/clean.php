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
    const TASK = 'Removing old courses';
    protected $needscascadedelete = true;

    static protected $courses = array();

    /**
     * Constructor.
     */
    public function __construct($options['dryrun'] = true, $verbose = false) {
        parent::__construct($options['dryrun'], $verbose);
        // Get the settings, handling the case where new ones (dev) haven't been set yet.
        $config = get_config('cleaner_courses');

        $criteria = self::get_courses_criteria($config);
        self::$courses = self::get_courses($criteria);

        $this->needscascadedelete = !empty(self::$courses);
    }

    /**
     * Delete a bunch of courses at once.
     *
     * delete_course is faaaaaaaaaaaaaaaar too slow. This plugin gets around this by using the XML schema
     * info to set up cascade deletion, use it to delete the affected courses and then revert the schema changes.
     */
    static public function delete_courses($courses = array()) {
        global $DB;

        if (self::$options['dryrun']) {
            echo "\nWould delete " . count($courses) . " courses (plus cascade deletions).\n";
        } else {
            list($sql, $params) = $DB->get_in_or_equal(array_keys($courses));
            $DB->delete_records_select('course', 'id ' . $sql, $params);
        }
    }

    /**
     * Delete course contexts that are left dangling after deleting courses.
     *
     */
    static public function delete_dangling_course_contexts() {
        global $DB;

        if (self::$options['dryrun']) {
            $count = $DB->count_records_sql(
                    "SELECT COUNT('x') FROM {context}
                                  LEFT JOIN {course}
                                         ON {context}.instanceid = {course}.id
                                      WHERE contextlevel = 50
                                        AND {course}.id IS NULL");
            echo "\nWould delete " . $count . " context records that are currently lacking matching courses " .
                    "and those from courses to be deleted.\n";
        } else {
            $DB->execute("DELETE FROM {context} USING {course}
                                WHERE contextlevel = 50
                                  AND {context}.instanceid = {course}.id
                                  AND {course}.id IS NULL");
        }
    }

    /**
     * Do the work of deleting courses.
     */
    static public function execute() {
        $numcourses = count(self::$courses);

        if (!$numcourses) {
            echo "No courses need deletion.\n";
            return;
        }

        echo "Deleting {$numcourses} courses.\n";

        self::new_task(2);

        self::delete_courses(self::$courses);
        self::next_step();

        self::delete_dangling_course_contexts(self::$courses);
        self::next_step();
    }
}
