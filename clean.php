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
 * Version details.
 *
 * @package    local_datacleaner
 * @copyright  2015 Brendan Heywood <brendan@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once($CFG->libdir.'/adminlib.php');

admin_externalpage_setup('local_datacleaner');

// Our module.js doesn't get autoloaded.
$PAGE->requires->js('/local/datacleaner/module.js');

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('cleaning', 'local_datacleaner'));

/// Main display starts here

/// Get and sort the existing plugins
$plugins = \local_datacleaner\plugininfo\cleaner::get_enabled_plugins_by_priority();

if (!$plugins) {
    print_error('noplugins', 'error');  // Should never happen
}

/// Print the table of all subplugins

$table = new html_table();
$table->head = array(
    get_string('plugin'),
    get_string('progress', 'local_datacleaner'),
    get_string('notes', 'local_datacleaner')
);
$table->attributes['class'] = 'admintable generaltable';
$data = array();

foreach ($plugins as $plugin) {
	// Build our progress divs
	$progress = html_writer::start_div('cleaner_progress ' . $plugin->name);
	$progress .= html_writer::div('', 'cleaner_progress_bar');
	$progress .= html_writer::span('0%', 'cleaner_label');
	$progress .= html_writer::end_div();

    $row = new html_table_row(array(
                $plugin->displayname,
                $progress,
                html_writer::div('', 'cleaner_progress_notes'),
    ));

    $row->id = 'cleaner_progress_' . $plugin->name;

    $data[] = $row;
}
$table->data = $data;
echo html_writer::table($table);

echo $OUTPUT->footer();

// Now that we have the page output, start the work. Ajax on the page will watch our progress.

foreach ($plugins as $plugin) {
    // Get the class that does the work.
    $classname = 'cleaner_' . $plugin->name . '\clean';

    if (!class_exists($classname)) {
        continue;
    }

    $class = new $classname;
    $class->execute();
}
