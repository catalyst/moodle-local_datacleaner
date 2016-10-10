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
 * Completion cleaner.
 *
 * @package    cleaner_completion
 * @copyright  2015 Dmitrii Metelkin <dmitriim@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace cleaner_completion;

defined('MOODLE_INTERNAL') || die();

class clean extends \local_datacleaner\clean {
    const TASK = 'Removing completion';

    static protected $courses = array();
    static protected $config;

    /**
     * Constructor.
     */
    public function __construct($options) {
        parent::__construct($options);

        self::$config = get_config('cleaner_completion');
        $criteria = self::get_courses_criteria(self::$config);
        self::$courses = self::get_courses($criteria);
    }

    /**
     * Delete course completion.
     *
     * @param array $courses A list of courses to delete completion for.
     */
    static public function delete_course_completion($courses = array()) {
        global $DB;

        if (!empty(self::$config->deletecoursecompletion)) {
            list($sql, $params) = $DB->get_in_or_equal(array_keys($courses));
            if (self::$options['dryrun']) {
                $coursecompletion = $DB->get_records_select('course_completion_crit_compl', 'course ' . $sql, $params, false, 'id');
                echo "\nWould delete " . count($coursecompletion) . " course completion records for "
                        . count($courses) . " courses. \n";
            } else {
                $DB->delete_records_select('course_completions', 'course ' . $sql, $params);
                $DB->delete_records_select('course_completion_crit_compl', 'course ' . $sql, $params);
            }
        }
    }

    /**
     * Delete activity completion.
     *
     * @param array $courses A list of courses to delete activity completion for.
     */
    static public function delete_activity_completion($courses = array()) {
        global $DB;

        if (!empty(self::$config->deleteactivitycompletion)) {
            list($sql, $params) = $DB->get_in_or_equal(array_keys($courses));

            if (!empty(self::$options['dryrun'])) {
                $coursecompletion = $DB->get_records_select('course_modules_completion',
                        "coursemoduleid IN (SELECT id FROM {course_modules} WHERE course $sql)",
                        $params, false, 'id');
                echo "\nWould delete " . count($coursecompletion) . " activity completion records for " .
                        count($courses) . " courses. \n";
            } else {
                $DB->delete_records_select('course_modules_completion',
                        "coursemoduleid IN (SELECT id FROM {course_modules} WHERE course $sql)",
                        $params);
            }
        }
    }

    /**
     * Do the work of deleting completion.
     */
    static public function execute() {
        $numcourses = count(self::$courses);

        if (!$numcourses) {
            echo "No courses need completion deletion.\n";
            return;
        }

        echo "Deleting completion for {$numcourses} courses.\n";

        self::new_task(2);
        self::delete_activity_completion(self::$courses);
        self::next_step();
        self::delete_course_completion(self::$courses);
        self::next_step();
    }
}