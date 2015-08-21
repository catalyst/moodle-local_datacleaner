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

defined('MOODLE_INTERNAL') || die;

if (!$ADMIN->fulltree) {
    return;
}

require_once($CFG->dirroot . '/course/externallib.php');

$settings->add(new admin_setting_configtext('cleaner_courses/minimumage',
            new lang_string('minimumage', 'cleaner_courses'),
            new lang_string('minimumagedesc', 'cleaner_courses'), 365, PARAM_INT));

// Categories of courses to delete.
// If $CFG->slasharguments is not set at all, this will trigger a warning in PHP unit testing
// when admin/tool/phpunit/cli/init.php is invoked.
if (!isset($CFG->slasharguments)) {
    $CFG->slasharguments = false;
}
$categories = core_course_external::get_categories();

$defaultcategories = array();

foreach ($categories as $category) {
    $categoriesbyname[$category['id']] = $category['name'];
    $defaultcategories[$category['id']] = 0;
}
asort($categoriesbyname, SORT_LOCALE_STRING);

$settings->add(new admin_setting_configmulticheckbox(
            'cleaner_courses/categories',
            new lang_string('categories', 'cleaner_courses'),
            new lang_string('categoriesdesc', 'cleaner_courses'),
            $defaultcategories,
            $categoriesbyname
            ));

// Courses to always keep.
// LaTrobe have a huge number of courses; it makes sense to add
// some caching to avoid having to regenerate the list every time someone
// visits a settings page.
$coursecache = cache::make('local_datacleaner', 'courses');
$coursesbyname = $coursecache->get('courses');
$courses = $DB->get_records_select('course', 'id > 1');
$defaultcourses = array();

// Only regenerate the cache if it's empty or a course has been added/deleted.
if (count($courses) != count($coursesbyname)) {

    $coursesbyname = array();

    foreach ($courses as $id => $course) {
        /*
         * Try to print the shortname and the fullname nicely. If one contains
         * the other, use the longer string. Otherwise enclose the shortname
         * in brackets. Finally, make the name a link to the course so that
         * further checking is easy.
         */
        if (strpos($course->fullname, $course->shortname) !== false) {
            $linktext = $course->fullname;
        } else if (strpos($course->shortname, $course->fullname) !== false) {
            $linktext = $course->shortname;
        } else {
            $linktext = $course->fullname . ' (\'' . $course->shortname . '\')';
        }
        $coursesbyname[$id] = $linktext;
        $defaultcourses[$id] = 0;
    }

    asort($coursesbyname, SORT_LOCALE_STRING);

    // Convert linktext to URL.
    $writer = new html_writer();
    foreach ($coursesbyname as $id => &$linktext) {
        $linktext = $writer->link(course_get_url($id), $linktext);
    }

    $coursecache->set('courses', $coursesbyname);
}

$settings->add(new admin_setting_configmulticheckbox(
    'cleaner_courses/courses',
    new lang_string('courses', 'cleaner_courses'),
    new lang_string('coursesdesc', 'cleaner_courses'),
    $defaultcourses,
    $coursesbyname
    ));
