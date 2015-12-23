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
 * Settings.
 *
 * @package    cleaner_completion
 * @copyright  2015 Dmitrii Metelkin <dmitriim@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

if (!$ADMIN->fulltree) {
    return;
}

$settings->add(new admin_setting_configcheckbox('cleaner_completion/deleteactivitycompletion',
            new lang_string('deleteactivitycompletion', 'cleaner_completion'),
            new lang_string('deleteactivitycompletiondesc', 'cleaner_completion'), 1));

$settings->add(new admin_setting_configcheckbox('cleaner_completion/deletecoursecompletion',
            new lang_string('deletecoursecompletion', 'cleaner_completion'),
            new lang_string('deletecoursecompletiondesc', 'cleaner_completion'), 1));

require_once($CFG->dirroot . '/course/externallib.php');

// Categories of courses to delete completon.
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
            'cleaner_completion/categories',
            new lang_string('categories', 'cleaner_completion'),
            new lang_string('categoriesdesc', 'cleaner_completion'),
            $defaultcategories,
            $categoriesbyname
            ));

$table = new html_table();
$table->data = array();
$table->head = array(
    get_string('coursename', 'cleaner_completion'),
    get_string('category', 'cleaner_completion'),
);

$config = get_config('cleaner_completion');

if (isset($config->courses)) {
    $shortnames = explode("\n", $config->courses);
} else {
    $shortnames = array();
}
$where = '';
foreach ($shortnames as $name) {
    $name = trim($name);
    if (empty($name)) {
        continue;
    }
    if ($where) {
        $where .= " OR ";
    }
    $where .= " shortname LIKE '$name'";
}

if ($where) {
    $itemstoignore = $DB->get_records_sql("SELECT c.id, c.fullname, c.category, ca.name
                                             FROM {course} c
                                             JOIN {course_categories} ca
                                               ON ca.id = c.category
                                            WHERE ($where)
                                            ORDER BY c.fullname, ca.name");
    foreach ($itemstoignore as $r) {
        $courselink = html_writer::link(new moodle_url('/course/view.php',
                            array('id' => $r->id)), $r->fullname, array('title' => 'View course'));
        $categorylink = html_writer::link(new moodle_url('/course/management.php',
                            array('categoryid' => $r->category)), $r->name, array('title' => 'View category'));
        $table->data[] = array($courselink, $categorylink);
    }
}

$settings->add(new admin_setting_configtextarea(
    'cleaner_completion/courses',
    new lang_string('courses', 'cleaner_completion'),
    new lang_string('coursesdesc', 'cleaner_completion') . "<br>\n" . html_writer::table($table),
    "", PARAM_RAW, 60, 5));
