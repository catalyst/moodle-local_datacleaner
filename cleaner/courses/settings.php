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

require_once($CFG->dirroot . '/local/datacleaner/lib.php');
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
$categories = local_datacleaner_get_categories();

$defaultcategories = array();
$categoriesbyname = array();

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

$table = new html_table();
$table->data = array();
$table->head = array(
    get_string('coursename', 'cleaner_courses'),
    get_string('category', 'cleaner_courses'),
);

$config = get_config('cleaner_courses');

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
    $itemstoignore = $DB->get_records_sql("SELECT c.id, c.fullname, ca.name
                                             FROM {course} c
                                             JOIN {course_categories} ca
                                               ON ca.id = c.category
                                            WHERE ($where)
                                            ORDER BY c.fullname, ca.name");
    foreach ($itemstoignore as $r) {
        $table->data[] = array($r->fullname, $r->name);
    }
}

$settings->add(new admin_setting_configtextarea(
    'cleaner_courses/courses',
    new lang_string('courses', 'cleaner_courses'),
    new lang_string('coursesdesc', 'cleaner_courses') . "<br>\n" . html_writer::table($table),
    "", PARAM_RAW, 60, 5));
